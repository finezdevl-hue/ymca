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
include_once __DIR__ . '/../../app_pagination/pagination.php';

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
        $resFirst = app_exec_getresult("SELECT id FROM tbl_members WHERE inactive = 0 ORDER BY id ASC LIMIT 1");
        if ($resFirst && $rowFirst = $resFirst->fetch_assoc()) {
            return (int)$rowFirst['id'];
        }
        return 0;
    }
}

if (isset($_POST['action']) && !empty($_POST['action'])) {
    $action = $_POST['action'];

    // Action to load members with pagination and search
    if ($action == "load_members") {
        try {
            $rowsPerPage = 8;
            $current_page = isset($_POST['page']) ? (int)$_POST['page'] : 1;
            $offset = ($current_page - 1) * $rowsPerPage;
            $search = isset($_POST['val']) ? trim($_POST['val']) : '';

            $sqlcountrows = "SELECT COUNT(id) as total FROM tbl_members WHERE inactive = 0";
            if ($search !== '') {
                $sqlcountrows .= " AND (first_name LIKE ? OR middle_name LIKE ? OR last_name LIKE ?)";
            }

            $sqldatarows = "SELECT m.id, m.first_name, m.middle_name, m.last_name, m.email, m.phone, m.img,
                            GROUP_CONCAT(g.name SEPARATOR ', ') AS group_names
                            FROM tbl_members m
                            LEFT JOIN tbl_group_member_map gmm ON m.id = gmm.member_id
                            LEFT JOIN tbl_groups g ON gmm.group_id = g.id AND g.status = 1
                            WHERE m.inactive = 0";
            if ($search !== '') {
                $sqldatarows .= " AND (m.first_name LIKE ? OR m.middle_name LIKE ? OR m.last_name LIKE ?)";
            }
            $sqldatarows .= " GROUP BY m.id ORDER BY m.first_name, m.middle_name, m.last_name LIMIT $offset, $rowsPerPage";

            if ($search === '') {
                $result = app_exec_query($sqldatarows);
                $totalRowsResult = app_exec_query($sqlcountrows);
                $totalRows = $totalRowsResult->fetch_assoc()['total'];
            } else {
                $searchParam = '%' . $search . '%';
                $params = [$searchParam, $searchParam, $searchParam];
                $types = "sss";

                $result = app_exec_getresult($sqldatarows, $params, $types);
                $totalRowsResult = app_exec_getresult($sqlcountrows, $params, $types);
                $totalRows = $totalRowsResult->fetch_assoc()['total'];
            }

            $qrydata = [];
            if ($result && $result->num_rows > 0) {
                while ($row = $result->fetch_assoc()) {
                    $qrydata[] = $row;
                }
            }

            $pagination = ["total_rows" => $totalRows];
            echo json_encode([$pagination, $qrydata]);
            exit();

        } catch (Throwable $e) {
            http_response_code(500);
            echo json_encode(['error' => $e->getMessage()]);
            exit();
        }
    }

    if ($action == "load_yearly_consolidated_report") {
        try {
            $year = !empty($_POST['year']) ? (int)$_POST['year'] : ((int)date('n') < 4 ? (int)date('Y') - 1 : (int)date('Y'));
            $search = isset($_POST['search']) ? trim($_POST['search']) : '';
            $page = isset($_POST['page']) ? (int)$_POST['page'] : 0;
            $limit = isset($_POST['limit']) ? (int)$_POST['limit'] : 8;

            include_once __DIR__ . '/../../app_common/auth_helper.php';
            $lid = (int)$_SESSION['login_id'];
            // Super Admin, Group Admin, and Executive Member view all members' reports. Attendance Masters view only their own report.
            $is_admin = isSuperAdmin($lid) || isGroupAdmin($lid) || isExecutiveMember($lid);
            $allowed_groups = getUserAllowedGroupIds($lid);

            $target_member_id = 0;
            if (!$is_admin) {
                $target_member_id = getLoggedInMemberId();
            } elseif (!empty($_POST['member_id'])) {
                $target_member_id = (int)$_POST['member_id'];
            }

            $start_date = "$year-04-01";
            $end_date   = ($year + 1) . "-03-31";

            // Count query to get total number of members in the allowed group(s) with attendance > 0
            $count_sql = "
                SELECT COUNT(DISTINCT m.id) as total 
                FROM tbl_members m
                LEFT JOIN tbl_group_member_map gmm ON m.id = gmm.member_id
                JOIN tbl_attendance a ON m.id = a.member_id AND a.date >= ? AND a.date <= ?
                WHERE m.inactive = 0
            ";
            $params_count = [$start_date, $end_date];
            $types_count = "ss";

            if ($target_member_id > 0) {
                $count_sql .= " AND m.id = ?";
                $params_count[] = $target_member_id;
                $types_count .= "i";
            } elseif ($is_admin && !in_array('ALL', $allowed_groups, true) && !empty($allowed_groups)) {
                $in = implode(',', array_map('intval', $allowed_groups));
                $count_sql .= " AND gmm.group_id IN ($in)";
            }

            if ($search !== '') {
                $count_sql .= " AND (m.first_name LIKE ? OR m.middle_name LIKE ? OR m.last_name LIKE ?)";
                $search_param = '%' . $search . '%';
                $params_count[] = $search_param;
                $params_count[] = $search_param;
                $params_count[] = $search_param;
                $types_count .= "sss";
            }

            if (!empty($params_count) && !empty($types_count)) {
                $total_res = app_exec_getresult($count_sql, $params_count, $types_count);
            } else {
                $total_res = app_exec_query($count_sql);
            }
            $total_rows = $total_res ? (int)$total_res->fetch_assoc()['total'] : 0;

            // Data query to get all members with attendance > 0
            $data_sql = "
                SELECT m.id, m.first_name, m.middle_name, m.last_name, m.email, m.phone, m.img,
                       GROUP_CONCAT(DISTINCT g.name SEPARATOR ', ') AS group_names,
                       COUNT(DISTINCT a.date) as total_days_present,
                       COUNT(DISTINCT DATE_FORMAT(a.date, '%Y-%m')) as total_months_present
                FROM tbl_members m
                LEFT JOIN tbl_group_member_map gmm ON m.id = gmm.member_id
                LEFT JOIN tbl_groups g ON gmm.group_id = g.id AND g.status = 1
                LEFT JOIN tbl_attendance a ON m.id = a.member_id AND a.date >= ? AND a.date <= ?
                WHERE m.inactive = 0
            ";
            $params_data = [$start_date, $end_date];
            $types_data = "ss";

            if ($target_member_id > 0) {
                $data_sql .= " AND m.id = ?";
                $params_data[] = $target_member_id;
                $types_data .= "i";
            } elseif ($is_admin && !in_array('ALL', $allowed_groups, true) && !empty($allowed_groups)) {
                $in = implode(',', array_map('intval', $allowed_groups));
                $data_sql .= " AND gmm.group_id IN ($in)";
            }

            if ($search !== '') {
                $data_sql .= " AND (m.first_name LIKE ? OR m.middle_name LIKE ? OR m.last_name LIKE ?)";
                $search_param = '%' . $search . '%';
                $params_data[] = $search_param;
                $params_data[] = $search_param;
                $params_data[] = $search_param;
                $types_data .= "sss";
            }

            $data_sql .= " GROUP BY m.id HAVING total_days_present > 0 ORDER BY m.first_name, m.middle_name, m.last_name";
            if ($target_member_id <= 0 && $page > 0 && $limit > 0) {
                $offset = ($page - 1) * $limit;
                $data_sql .= " LIMIT $offset, $limit";
            }
            if (!empty($params_data) && !empty($types_data)) {
                $data_res = app_exec_getresult($data_sql, $params_data, $types_data);
            } else {
                $data_res = app_exec_query($data_sql);
            }

            $members = [];
            $member_ids = [];
            if ($data_res) {
                while ($row = $data_res->fetch_assoc()) {
                    if ((int)$row['total_days_present'] <= 0) {
                        continue;
                    }
                    $members[$row['id']] = [
                        'id' => $row['id'],
                        'first_name' => $row['first_name'],
                        'middle_name' => $row['middle_name'],
                        'last_name' => $row['last_name'],
                        'email' => $row['email'],
                        'phone' => $row['phone'],
                        'img' => $row['img'],
                        'group_names' => $row['group_names'],
                        'total_days_present' => $row['total_days_present'],
                        'total_months_present' => $row['total_months_present'],
                        'months' => array_fill(1, 12, 0)
                    ];
                    $member_ids[] = (int)$row['id'];
                }
            }

            // Fetch detail month-wise counts for the matching members
            if (!empty($member_ids)) {
                $ids_clause = implode(',', $member_ids);
                $months_sql = "
                    SELECT member_id, MONTH(date) as month_num, COUNT(DISTINCT date) as days_count
                    FROM tbl_attendance
                    WHERE member_id IN ($ids_clause) AND date >= ? AND date <= ?
                    GROUP BY member_id, MONTH(date)
                ";
                $months_res = app_exec_getresult($months_sql, [$start_date, $end_date], "ss");
                if ($months_res) {
                    while ($row = $months_res->fetch_assoc()) {
                        $m_id = (int)$row['member_id'];
                        $month_num = (int)$row['month_num'];
                        $fy_index = ($month_num >= 4) ? ($month_num - 3) : ($month_num + 9);
                        $days_count = (int)$row['days_count'];
                        if (isset($members[$m_id])) {
                            $members[$m_id]['months'][$fy_index] = $days_count;
                        }
                    }
                }
            }

            echo json_encode([
                'total_rows' => $total_rows,
                'members' => array_values($members)
            ]);
            exit();

        } catch (Throwable $e) {
            http_response_code(500);
            echo json_encode(['error' => $e->getMessage()]);
            exit();
        }
    }

    // Action to fetch ALL members for print (no pagination)
    if ($action == "load_all_for_print") {
        try {
            $year   = (int)$_POST['year'];
            $search = isset($_POST['search']) ? trim($_POST['search']) : '';

            $start_date = "$year-04-01";
            $end_date   = ($year + 1) . "-03-31";

            $data_sql = "
                SELECT m.id, m.first_name, m.middle_name, m.last_name,
                       GROUP_CONCAT(DISTINCT g.name SEPARATOR ', ') AS group_names,
                       COUNT(DISTINCT a.date) as total_days_present,
                       COUNT(DISTINCT DATE_FORMAT(a.date, '%Y-%m')) as total_months_present
                FROM tbl_members m
                JOIN tbl_attendance a ON m.id = a.member_id
                LEFT JOIN tbl_group_member_map gmm ON m.id = gmm.member_id
                LEFT JOIN tbl_groups g ON gmm.group_id = g.id AND g.status = 1
                WHERE m.inactive = 0 AND a.date >= ? AND a.date <= ?
            ";
            $params_data = [$start_date, $end_date];
            $types_data  = "ss";

            if ($search !== '') {
                $data_sql .= " AND (m.first_name LIKE ? OR m.middle_name LIKE ? OR m.last_name LIKE ?)";
                $sp = '%' . $search . '%';
                $params_data[] = $sp;
                $params_data[] = $sp;
                $params_data[] = $sp;
                $types_data .= "sss";
            }

            $data_sql .= " GROUP BY m.id ORDER BY m.first_name, m.middle_name, m.last_name";
            $data_res  = app_exec_getresult($data_sql, $params_data, $types_data);

            $members    = [];
            $member_ids = [];
            if ($data_res) {
                while ($row = $data_res->fetch_assoc()) {
                    $members[$row['id']] = [
                        'id'                   => $row['id'],
                        'first_name'           => $row['first_name'],
                        'middle_name'          => $row['middle_name'],
                        'last_name'            => $row['last_name'],
                        'group_names'          => $row['group_names'],
                        'total_days_present'   => $row['total_days_present'],
                        'total_months_present' => $row['total_months_present'],
                        'months'               => array_fill(1, 12, 0)
                    ];
                    $member_ids[] = (int)$row['id'];
                }
            }

            if (!empty($member_ids)) {
                $ids_clause = implode(',', $member_ids);
                $months_sql = "
                    SELECT member_id, MONTH(date) as month_num, COUNT(DISTINCT date) as days_count
                    FROM tbl_attendance
                    WHERE member_id IN ($ids_clause) AND date >= ? AND date <= ?
                    GROUP BY member_id, MONTH(date)
                ";
                $months_res = app_exec_getresult($months_sql, [$start_date, $end_date], "ss");
                if ($months_res) {
                    while ($row = $months_res->fetch_assoc()) {
                        $m_id      = (int)$row['member_id'];
                        $month_num = (int)$row['month_num'];
                        $fy_index  = ($month_num >= 4) ? ($month_num - 3) : ($month_num + 9);
                        if (isset($members[$m_id])) {
                            $members[$m_id]['months'][$fy_index] = (int)$row['days_count'];
                        }
                    }
                }
            }

            echo json_encode(['members' => array_values($members), 'year' => $year]);
            exit();

        } catch (Throwable $e) {
            http_response_code(500);
            echo json_encode(['error' => $e->getMessage()]);
            exit();
        }
    }

    // Action to calculate and retrieve a member's yearly attendance summary
    if ($action == "get_yearly_summary") {
        try {
            $member_id = (int)$_POST['member_id'];
            $year = (int)$_POST['year'];

            // 1. Get member's details
            $member_info_sql = "SELECT m.first_name, m.middle_name, m.last_name, m.email, m.phone, m.img,
                                GROUP_CONCAT(g.name SEPARATOR ', ') AS group_names
                                FROM tbl_members m
                                LEFT JOIN tbl_group_member_map gmm ON m.id = gmm.member_id
                                LEFT JOIN tbl_groups g ON gmm.group_id = g.id
                                WHERE m.id = ? AND m.inactive = 0
                                GROUP BY m.id";
            $res_info = app_exec_getresult($member_info_sql, [$member_id], "i");
            if (!$res_info || $res_info->num_rows === 0) {
                echo json_encode(['error' => 'Member not found or inactive.']);
                exit();
            }
            $member_info = $res_info->fetch_assoc();

            // 2. Fetch the group IDs mapped to this member
            $group_sql = "SELECT group_id FROM tbl_group_member_map WHERE member_id = ?";
            $res_groups = app_exec_getresult($group_sql, [$member_id], "i");
            $group_ids = [];
            if ($res_groups) {
                while ($row = $res_groups->fetch_assoc()) {
                    $group_ids[] = (int)$row['group_id'];
                }
            }

            // Initialize monthly container in Financial Year order (April to March)
            $financial_months = [4, 5, 6, 7, 8, 9, 10, 11, 12, 1, 2, 3];
            $months_names = [
                1 => 'January', 2 => 'February', 3 => 'March', 4 => 'April',
                5 => 'May', 6 => 'June', 7 => 'July', 8 => 'August',
                9 => 'September', 10 => 'October', 11 => 'November', 12 => 'December'
            ];

            $monthly_stats = [];
            foreach ($financial_months as $m) {
                $monthly_stats[$m] = [
                    'month_num'  => $m,
                    'month_name' => $months_names[$m],
                    'sessions'   => 0,
                    'present'    => 0,
                    'absent'     => 0,
                    'percentage' => 0,
                    'present_dates' => [],
                    'absent_dates'  => [],
                    'days'          => array_fill(1, 31, '—') // default to dash
                ];
            }

            // If the member belongs to groups, calculate stats
            if (!empty($group_ids)) {
                $in_clause = implode(',', $group_ids);
                $start_date = "$year-04-01";
                $end_date   = ($year + 1) . "-03-31";

                // Fetch all unique session dates in the selected financial year for these groups
                $sessions_sql = "SELECT DISTINCT date FROM tbl_attendance 
                                 WHERE group_id IN ($in_clause) AND date >= ? AND date <= ? 
                                 ORDER BY date ASC";
                $res_sessions = app_exec_getresult($sessions_sql, [$start_date, $end_date], "ss");
                $session_dates_by_month = array_fill(1, 12, []);
                if ($res_sessions) {
                    while ($row = $res_sessions->fetch_assoc()) {
                        $date_val = $row['date'];
                        $m = (int)date('n', strtotime($date_val));
                        $session_dates_by_month[$m][] = $date_val;
                    }
                }

                // Fetch all unique dates in the selected financial year where the member was present
                $present_sql = "SELECT DISTINCT date FROM tbl_attendance 
                                WHERE member_id = ? AND group_id IN ($in_clause) AND date >= ? AND date <= ? 
                                ORDER BY date ASC";
                $res_present = app_exec_getresult($present_sql, [$member_id, $start_date, $end_date], "iss");
                $present_dates_by_month = array_fill(1, 12, []);
                if ($res_present) {
                    while ($row = $res_present->fetch_assoc()) {
                        $date_val = $row['date'];
                        $m = (int)date('n', strtotime($date_val));
                        $present_dates_by_month[$m][$date_val] = true;
                    }
                }

                // Calculate counts, percentages, and lists for each month
                foreach ($financial_months as $m) {
                    $month_sessions = $session_dates_by_month[$m];
                    $month_presents = $present_dates_by_month[$m];

                    $present_list = [];
                    $absent_list  = [];

                    foreach ($month_sessions as $d) {
                        $formatted_date = date('d-M-Y', strtotime($d));
                        if (isset($month_presents[$d])) {
                            $present_list[] = $formatted_date;
                        } else {
                            $absent_list[] = $formatted_date;
                        }
                    }

                    $total_s = count($month_sessions);
                    $total_p = count($present_list);
                    $total_a = count($absent_list);
                    $pct = $total_s > 0 ? round(($total_p / $total_s) * 100) : 0;

                    // Compute day-by-day status (1 to 31)
                    $target_year = ($m >= 4) ? $year : $year + 1;
                    $days_in_month = (int)date('t', strtotime(sprintf('%04d-%02d-01', $target_year, $m)));
                    $days_data = [];
                    for ($d = 1; $d <= 31; $d++) {
                        if ($d > $days_in_month) {
                            $days_data[$d] = ''; // Blank for out-of-bounds (e.g. Feb 30th)
                            continue;
                        }
                        
                        $date_str = sprintf('%04d-%02d-%02d', $target_year, $m, $d);
                        if (in_array($date_str, $month_sessions)) {
                            if (isset($month_presents[$date_str])) {
                                $days_data[$d] = 'P';
                            } else {
                                $days_data[$d] = 'A';
                            }
                        } else {
                            $days_data[$d] = '—';
                        }
                    }

                    $monthly_stats[$m]['sessions']   = $total_s;
                    $monthly_stats[$m]['present']    = $total_p;
                    $monthly_stats[$m]['absent']     = $total_a;
                    $monthly_stats[$m]['percentage'] = $pct;
                    $monthly_stats[$m]['present_dates'] = $present_list;
                    $monthly_stats[$m]['absent_dates']  = $absent_list;
                    $monthly_stats[$m]['days']          = $days_data;
                }
            }

            // Calculate overall yearly summary
            $yearly_sessions = 0;
            $yearly_present = 0;
            $yearly_absent = 0;
            foreach ($monthly_stats as $m_data) {
                $yearly_sessions += $m_data['sessions'];
                $yearly_present  += $m_data['present'];
                $yearly_absent   += $m_data['absent'];
            }
            $yearly_percentage = $yearly_sessions > 0 ? round(($yearly_present / $yearly_sessions) * 100) : 0;

            echo json_encode([
                'member_info' => $member_info,
                'monthly_stats' => array_values($monthly_stats),
                'summary' => [
                    'year' => $year,
                    'total_sessions' => $yearly_sessions,
                    'total_present' => $yearly_present,
                    'total_absent' => $yearly_absent,
                    'percentage' => $yearly_percentage
                ]
            ]);
            exit();

        } catch (Throwable $e) {
            http_response_code(500);
            echo json_encode(['error' => $e->getMessage()]);
            exit();
        }
    }
}
?>
