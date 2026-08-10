<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

if (empty($_SESSION['login_id'])) {
    http_response_code(401);
    echo json_encode(['error' => 'Unauthorized']);
    exit();
}

include_once __DIR__ . '/../../app_common/db_connect.php';
include_once __DIR__ . '/attendance_fix_helper.php';

if (!function_exists('getLoggedInMemberId')) {
    function getLoggedInMemberId() {
        if (!empty($_SESSION['user_id']) && (int)$_SESSION['user_id'] > 0) {
            return (int)$_SESSION['user_id'];
        }
        if (!empty($_SESSION['email'])) {
            $res = app_exec_getresult(
                "SELECT id FROM tbl_members WHERE LTRIM(RTRIM(LOWER(email))) = LTRIM(RTRIM(LOWER(?))) AND inactive = 0 LIMIT 1",
                [$_SESSION['email']], "s"
            );
            if ($res && $row = $res->fetch_assoc()) {
                $_SESSION['user_id'] = (int)$row['id'];
                return (int)$row['id'];
            }
        }
        if (!empty($_SESSION['name'])) {
            $resName = app_exec_getresult(
                "SELECT id FROM tbl_members WHERE LTRIM(RTRIM(LOWER(CONCAT(first_name, ' ', last_name)))) = LTRIM(RTRIM(LOWER(?))) AND inactive = 0 LIMIT 1",
                [$_SESSION['name']], "s"
            );
            if ($resName && $rowName = $resName->fetch_assoc()) {
                $_SESSION['user_id'] = (int)$rowName['id'];
                return (int)$rowName['id'];
            }
        }
        $resFirst = app_exec_query("SELECT id FROM tbl_members WHERE inactive = 0 ORDER BY id ASC LIMIT 1");
        if ($resFirst && $rowFirst = $resFirst->fetch_assoc()) {
            return (int)$rowFirst['id'];
        }
        return 0;
    }
}

if (isset($_POST['action'])) {
    $action = $_POST['action'];

    if ($action == 'save_shuttle_readings') {
        try {
            $group_id = (int)$_POST['group_id'];
            $month = $_POST['month']; // YYYY-MM
            
            $start_shuttle = $_POST['start_shuttle'] !== '' ? (int)$_POST['start_shuttle'] : null;
            $end_shuttle = $_POST['end_shuttle'] !== '' ? (int)$_POST['end_shuttle'] : null;
            
            $shuttle_price = null;
            if (isset($_POST['shuttle_price']) && $_POST['shuttle_price'] !== '') {
                $shuttle_price = (double)$_POST['shuttle_price'];
            } elseif (isset($_POST['avg_shuttle_price']) && $_POST['avg_shuttle_price'] !== '') {
                $shuttle_price = (double)$_POST['avg_shuttle_price'];
            }
            
            $chk_sql = "SELECT id FROM tbl_monthly_shuttle_readings WHERE group_id = ? AND month = ?";
            $chk_res = app_exec_getresult($chk_sql, [$group_id, $month], "is");
            
            if ($chk_res && $chk_row = $chk_res->fetch_assoc()) {
                $sql = "UPDATE tbl_monthly_shuttle_readings SET start_shuttle = ?, end_shuttle = ?, shuttle_price = ? WHERE id = ?";
                app_exec_nonquery($sql, [$start_shuttle, $end_shuttle, $shuttle_price, $chk_row['id']], "iidi");
            } else {
                $sql = "INSERT INTO tbl_monthly_shuttle_readings (group_id, month, start_shuttle, end_shuttle, shuttle_price) VALUES (?, ?, ?, ?, ?)";
                app_exec_nonquery($sql, [$group_id, $month, $start_shuttle, $end_shuttle, $shuttle_price], "isiid");
            }
            
            echo json_encode(['success' => true]);
            exit();
        } catch (Throwable $e) {
            http_response_code(500);
            echo json_encode(['error' => $e->getMessage()]);
            exit();
        }
    }

    if ($action == 'load_report' || $action == 'load_monthly_report') {
        try {
            $month    = $_POST['month'];      // YYYY-MM
            $group_id = (int)$_POST['group_id'];

            $start_date = $month . "-01";
            $end_date   = date("Y-m-t", strtotime($start_date));
            $total_days = (int)date("t", strtotime($start_date));

            // 0. Get monthly shuttle cost summary from item entries
            $item_sql = "SELECT SUM(no_of_shuttle) AS total_shuttles,
                                SUM(total_item_amount) AS total_amount
                         FROM tbl_items
                         WHERE used_date BETWEEN ? AND ?";
            $res_items = app_exec_getresult($item_sql, [$start_date, $end_date], "ss");
            $month_total_shuttles = 0;
            $month_total_amount = 0.0;
            $month_avg_shuttle_price = null;
            if ($res_items && $row_item = $res_items->fetch_assoc()) {
                $month_total_shuttles = !empty($row_item['total_shuttles']) ? (int)$row_item['total_shuttles'] : 0;
                $month_total_amount = !empty($row_item['total_amount']) ? (double)$row_item['total_amount'] : 0.0;
                if ($month_total_shuttles > 0) {
                    $month_avg_shuttle_price = round($month_total_amount / $month_total_shuttles, 2);
                }
            }

            // 1. Get all distinct session dates for each group in the month
            $session_sql = "SELECT DISTINCT group_id, DATE_FORMAT(date,'%d') as day_num
                            FROM tbl_attendance
                            WHERE date BETWEEN ? AND ?";
            $res_sessions = app_exec_getresult($session_sql, [$start_date, $end_date], "ss");

            // Map: group_id => [day_num => true]
            $session_days_map = [];
            if ($res_sessions) {
                while ($row = $res_sessions->fetch_assoc()) {
                    $gid = $row['group_id'];
                    $day = (int)$row['day_num'];
                    if (!isset($session_days_map[$gid])) $session_days_map[$gid] = [];
                    $session_days_map[$gid][$day] = true;
                }
            }

            // 2. Get all members (filtered by group)
            include_once __DIR__ . '/../../app_common/auth_helper.php';
            $lid = (int)$_SESSION['login_id'];
            // Super Admin, Group Admin, and Executive Member view all members' reports. Attendance Masters view only their own report.
            $is_admin = isSuperAdmin($lid) || isGroupAdmin($lid) || isExecutiveMember($lid);
            $allowed_groups = getUserAllowedGroupIds($lid);

            $member_sql = "SELECT m.id, m.first_name, m.middle_name, m.last_name,
                                  gmm.group_id, g.name as group_name
                           FROM tbl_members m
                           JOIN tbl_group_member_map gmm ON m.id = gmm.member_id
                           JOIN tbl_groups g ON gmm.group_id = g.id AND g.status = 1
                           WHERE m.inactive = 0 AND (? = 0 OR gmm.group_id = ?)";

            if (!$is_admin) {
                $my_member_id = getLoggedInMemberId();
                $member_sql .= " AND m.id = ?";
                $member_sql .= " ORDER BY g.name, m.first_name, m.middle_name, m.last_name";
                $res_members = app_exec_getresult($member_sql, [$group_id, $group_id, $my_member_id], "iii");
            } else {
                if (!in_array('ALL', $allowed_groups, true) && !empty($allowed_groups)) {
                    $in = implode(',', array_map('intval', $allowed_groups));
                    $member_sql .= " AND gmm.group_id IN ($in)";
                }
                $member_sql .= " ORDER BY g.name, m.first_name, m.middle_name, m.last_name";
                $res_members = app_exec_getresult($member_sql, [$group_id, $group_id], "ii");
            }

            $members = [];
            if ($res_members) {
                while ($row = $res_members->fetch_assoc()) {
                    $members[] = $row;
                }
            }

            if (empty($members)) {
                echo json_encode([
                    'members'               => [],
                    'days'                  => [],
                    'start_date'            => $start_date,
                    'end_date'              => $end_date,
                    'month_total_shuttles'  => $month_total_shuttles,
                    'month_total_amount'    => $month_total_amount,
                    'month_avg_shuttle_price' => $month_avg_shuttle_price
                ]);
                exit();
            }

            // 3. Get all attendance records for the month (filtered by group)
            $att_sql = "SELECT a.member_id, gmm.group_id, DATE_FORMAT(a.date,'%d') as day_num
                        FROM tbl_attendance a
                        JOIN tbl_group_member_map gmm ON a.member_id = gmm.member_id AND a.group_id = gmm.group_id
                        WHERE a.date BETWEEN ? AND ? AND (? = 0 OR a.group_id = ?)";
            $res_att = app_exec_getresult($att_sql, [$start_date, $end_date, $group_id, $group_id], "ssii");

            // Map: member_id => group_id => [day_num => true]
            $att_map = [];
            if ($res_att) {
                while ($row = $res_att->fetch_assoc()) {
                    $mid = $row['member_id'];
                    $gid = $row['group_id'];
                    $day = (int)$row['day_num'];
                    if (!isset($att_map[$mid])) $att_map[$mid] = [];
                    if (!isset($att_map[$mid][$gid])) $att_map[$mid][$gid] = [];
                    $att_map[$mid][$gid][$day] = true;
                }
            }

            // 3.5 Get group/monthly shuttle readings
            $shuttle_group_sql = "SELECT start_shuttle, end_shuttle, shuttle_price FROM tbl_monthly_shuttle_readings WHERE group_id = ? AND month = ?";
            $res_shuttle_group = app_exec_getresult($shuttle_group_sql, [$group_id, $month], "is");
            $group_start_shuttle = null;
            $group_end_shuttle = null;
            $group_shuttle_price = null;
            $group_avg_shuttle_price = null;
            if ($res_shuttle_group && $row_sg = $res_shuttle_group->fetch_assoc()) {
                $group_start_shuttle = $row_sg['start_shuttle'] !== null ? (int)$row_sg['start_shuttle'] : null;
                $group_end_shuttle = $row_sg['end_shuttle'] !== null ? (int)$row_sg['end_shuttle'] : null;
                $group_shuttle_price = $row_sg['shuttle_price'] !== null ? (double)$row_sg['shuttle_price'] : null;
                if ($group_start_shuttle !== null && $group_end_shuttle !== null && $group_end_shuttle >= $group_start_shuttle && $group_shuttle_price !== null) {
                    $group_used_shuttles = ($group_end_shuttle + 1) - $group_start_shuttle;
                    if ($group_used_shuttles > 0) {
                        $group_avg_shuttle_price = round($group_shuttle_price / $group_used_shuttles, 2);
                    }
                }
            }

            // 4. Build result — per member, per day: P / A / null (no session)
            $result = [];
            foreach ($members as $m) {
                $mid = $m['id'];
                $gid = $m['group_id'];
                $session_days = isset($session_days_map[$gid]) ? $session_days_map[$gid] : [];
                $present_days = isset($att_map[$mid][$gid]) ? $att_map[$mid][$gid] : [];

                $days_data    = [];
                $present_count = 0;
                $session_count = 0;

                for ($d = 1; $d <= $total_days; $d++) {
                    if (isset($session_days[$d])) {
                        $session_count++;
                        if (isset($present_days[$d])) {
                            $days_data[$d] = 'P';
                            $present_count++;
                        } else {
                            $days_data[$d] = 'A';
                        }
                    } else {
                        $days_data[$d] = null; // no session
                    }
                }

                $percentage = $session_count > 0 ? round(($present_count / $session_count) * 100) : 0;

                if ($is_admin && $present_count === 0) {
                    continue; // Skip members with 0 attendance in admin report
                }

                $result[] = [
                    'id'           => $mid,
                    'first_name'   => $m['first_name'],
                    'middle_name'  => $m['middle_name'],
                    'last_name'    => $m['last_name'],
                    'group_id'     => $gid,
                    'group_name'   => $m['group_name'],
                    'days'         => $days_data,
                    'present'      => $present_count,
                    'sessions'     => $session_count,
                    'percentage'   => $percentage
                ];
            }

        // Collect all days of the month as column headers
        $day_cols = range(1, $total_days);

            echo json_encode([
                'members'             => $result,
                'day_cols'            => $day_cols,
                'total_days'          => $total_days,
                'start_date'          => $start_date,
                'end_date'            => $end_date,
                'month_total_shuttles' => $month_total_shuttles,
                'month_total_amount'   => $month_total_amount,
                'month_avg_shuttle_price' => $month_avg_shuttle_price,
                'group_start_shuttle' => $group_start_shuttle,
                'group_end_shuttle'   => $group_end_shuttle,
                'group_shuttle_price' => $group_shuttle_price,
                'group_avg_shuttle_price' => $group_avg_shuttle_price
            ]);
        exit();

    } catch (Throwable $e) {
        http_response_code(500);
        echo json_encode(['error' => $e->getMessage()]);
        exit();
    }
}
}

if (isset($_POST['action']) && $_POST['action'] == 'check_fixed_status') {
    try {
        $month = $_POST['month']; // YYYY-MM
        $sql = "SELECT id FROM tbl_fixed_months WHERE fixed_month = ?";
        $res = app_exec_getresult($sql, [$month], "s");
        $is_fixed = ($res && $res->num_rows > 0) ? true : false;
        echo json_encode(['is_fixed' => $is_fixed]);
        exit();
    } catch (Throwable $e) {
        http_response_code(500);
        echo json_encode(['error' => $e->getMessage()]);
        exit();
    }
}

if (isset($_POST['action']) && $_POST['action'] == 'toggle_fixed_status') {
    try {
        $lid = (int)$_SESSION['login_id'];
        if (!isSuperAdmin($lid) && !isGroupAdmin($lid) && !isExecutiveMember($lid)) {
            http_response_code(403);
            echo json_encode(['error' => 'Forbidden']);
            exit();
        }
        
        $month = $_POST['month']; // YYYY-MM
        $sql = "SELECT id FROM tbl_fixed_months WHERE fixed_month = ?";
        $res = app_exec_getresult($sql, [$month], "s");
        
        $dateObj = DateTime::createFromFormat('Y-m', $month);
        $monthName = $dateObj ? $dateObj->format('F Y') : $month;
        
        if ($res && $res->num_rows > 0) {
            http_response_code(400);
            echo json_encode(['success' => false, 'error' => "Attendance for $monthName is already fixed and cannot be unfixed or changed."]);
        } else {
            // Check if Coke readings are saved for this month
            $chk_readings_sql = "SELECT id FROM tbl_monthly_shuttle_readings WHERE month = ? AND start_shuttle IS NOT NULL AND end_shuttle IS NOT NULL";
            $chk_readings_res = app_exec_getresult($chk_readings_sql, [$month], "s");
            if (!$chk_readings_res || $chk_readings_res->num_rows == 0) {
                http_response_code(400);
                echo json_encode(['success' => false, 'error' => "Attendance can only be fixed after adding the Coke Readings. Please enter and save the Coke Readings first."]);
                exit();
            }

            $sql_ins = "INSERT INTO tbl_fixed_months (fixed_month) VALUES (?)";
            app_exec_nonquery($sql_ins, [$month], "s");
            echo json_encode(['success' => true, 'message' => "Attendance for $monthName has been locked/fixed successfully."]);
        }
        exit();
    } catch (Throwable $e) {
        http_response_code(500);
        echo json_encode(['error' => $e->getMessage()]);
        exit();
    }
}
?>
