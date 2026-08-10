<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
include_once __DIR__ . '/../../app_common/db_connect.php';
include_once __DIR__ . '/attendance_fix_helper.php';
include_once __DIR__ . '/../../app_pagination/pagination.php';
include_once __DIR__ . '/../../app_common/auth_helper.php';

$is_admin = false;
if (isset($_SESSION['login_id'])) {
    $login_id = (int)$_SESSION['login_id'];
    if (isSuperAdmin($login_id) || isGroupAdmin($login_id) || isAttendanceMaster($login_id)) {
        $is_admin = true;
    } else {
        $sql_perm = "SELECT id FROM tbl_menu_map WHERE login_id = ? AND (menu_id = 11 OR menu_id = 12) AND is_active = 1";
        $res_perm = app_exec_getresult($sql_perm, [$login_id], "i");
        if ($res_perm && $res_perm->num_rows > 0) {
            $is_admin = true;
        }
    }
}

// Helper to get logged-in member's ID from tbl_members
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
    // Fallback: pick first active member
    $resFirst = app_exec_getresult("SELECT id FROM tbl_members WHERE inactive = 0 ORDER BY id ASC LIMIT 1");
    if ($resFirst && $rowFirst = $resFirst->fetch_assoc()) {
        return (int)$rowFirst['id'];
    }
    return 0;
}

// Helper to auto-purge temporary status records older than 2 days
function cleanupExpiredTempAttendance() {
    try {
        app_exec_query("DELETE FROM tbl_temp_attendance_status WHERE status_date < DATE_SUB(CURDATE(), INTERVAL 2 DAY)");
        app_exec_query("DELETE FROM tbl_temp_attendance_status WHERE status = 'present' AND NOT EXISTS (SELECT 1 FROM tbl_attendance att WHERE (att.member_id = tbl_temp_attendance_status.member_id OR att.member_id = (SELECT id FROM tbl_members WHERE email = (SELECT email FROM tbl_login WHERE login_id = tbl_temp_attendance_status.login_id LIMIT 1) LIMIT 1)) AND att.group_id = tbl_temp_attendance_status.group_id AND att.date = tbl_temp_attendance_status.status_date)");
    } catch (Throwable $e) {}
}

// Helper to validate if tomorrow attendance is allowed for group
function isTomorrowAttendanceAllowed($group_id, $target_date) {
    date_default_timezone_set('Asia/Kolkata');
    $today = date('Y-m-d');
    $tomorrow = date('Y-m-d', strtotime('+1 day'));
    
    // Any date today or earlier (IST) is always allowed (not tomorrow)
    if ($target_date <= $today) {
        return true;
    }
    
    if ($target_date === $tomorrow) {
        $res = app_exec_getresult("SELECT allow_tomorrow_attendance FROM tbl_groups WHERE id = ? LIMIT 1", [(int)$group_id], "i");
        if ($res && $row = $res->fetch_assoc()) {
            return ((int)$row['allow_tomorrow_attendance'] === 1);
        }
        return false;
    }
    if ($target_date > $tomorrow) {
        return false;
    }
    return true;
}

// actions start
if(isset($_POST['action']) && !empty($_POST['action'])) {
    $action = $_POST['action'];

    //action load members in a selected group start
    if($action=="load_member_data"){
        try{
            
            $sql="";  
            $sqlcountrows="";
            $sqldatarows="";   
    
            $sqldatarows = "SELECT m.id, m.first_name, m.middle_name, m.last_name, m.img,
            COUNT(att.member_id) AS total_att
            FROM tbl_members AS m JOIN tbl_group_member_map AS gmm 
            ON m.id = gmm.member_id
            LEFT JOIN tbl_attendance AS att ON m.id = att.member_id 
            AND att.date >= DATE_SUB(CURDATE(), INTERVAL 3 MONTH)
            WHERE gmm.group_id = ? AND m.inactive = 0";
            
            if (!$is_admin) {
                $sqldatarows .= " AND m.id = ?";
            }
            
            $sqldatarows .= " GROUP BY m.id, m.first_name, m.middle_name, m.last_name, m.img
            ORDER BY m.first_name, m.middle_name, m.last_name";

            if ($is_admin) {
                $parameters = [
                    $_POST['val'],
                ];
                $types = "i";
            } else {
                $parameters = [
                    $_POST['val'],
                    getLoggedInMemberId(),
                ];
                $types = "ii";
            }

            $result = app_exec_getresult($sqldatarows,$parameters,$types);
                
            //stringify
            if ($result && $result->num_rows > 0) {
                // Fetch all rows as an array
                $qrydata = [];
                while ($row = $result->fetch_assoc()) {
                    $qrydata[] = $row; // Add each row to the array
                }
            } else {
                $qrydata = []; // If no data is found
            }
    
            $resdata=array($qrydata);
    
            echo json_encode($resdata);
            exit();
        }
        catch (Throwable $e) {
            throw new Exception('Oops! Something went wrong.');
        }
    }
    //action load members in a selected group end

    // action to load groups for load members start
    if($action=="load_groups"){
        try{
            if ($is_admin) {
                $sql = "SELECT id, name, allow_tomorrow_attendance FROM tbl_groups WHERE status = 1 ORDER BY id DESC";
                $result = app_exec_query($sql);
            } else {
                $member_id = getLoggedInMemberId();
                $sql = "SELECT g.id, g.name, g.allow_tomorrow_attendance FROM tbl_groups AS g LEFT JOIN tbl_group_member_map as gm ON g.id=gm.group_id WHERE gm.member_id = ? AND g.status = 1";
                $result = app_exec_getresult($sql, [$member_id], "i");
                // Fallback to all active groups if member has no mapped group
                if (!$result || $result->num_rows == 0) {
                    $sql = "SELECT id, name, allow_tomorrow_attendance FROM tbl_groups WHERE status = 1 ORDER BY id ASC";
                    $result = app_exec_query($sql);
                }
            }

            if ($result && $result->num_rows > 0) {
                $groups = [];
                while ($row = $result->fetch_assoc()) {
                    $groups[] = $row;
                }
            } else {
                $groups = [];
            }
            $data = array($groups);
            echo json_encode($data);
            exit();
        }
        catch (Exception $e) {
            http_response_code(500);   
            echo "Oops! Something went wrong." . $e->getMessage();
            return;
        }
    }
    // action to load groups for load members end

    //action to add attendance of slected members start
    if($action=="add_attendance"){
        checkMonthFixed($_POST['date']);
        
        // Block marking if date is a holiday/leave for this group OR all groups (group_id = 0)
        $group_val = isset($_POST['val']) ? (int)$_POST['val'] : 0;
        $checkHoliday = "SELECT id FROM tbl_dates WHERE date = ? AND (group_id = 0 OR group_id = ?)";
        $resHoliday = app_exec_getresult($checkHoliday, [$_POST['date'], $group_val], "si");
        if ($resHoliday && $resHoliday->num_rows > 0) {
            http_response_code(400);
            echo "Attendance cannot be marked because today is a Holiday / Leave for this session.";
            exit();
        }

        try{
            if (!isTomorrowAttendanceAllowed($_POST['val'], $_POST['date'])) {
                echo "Tomorrow's attendance is not enabled for this group.";
                exit();
            }

            $db = new Database();
            $conn = $db->getConnection();
            $tm = new TransactionManager($conn);
            $tm->begin();

            if (isset($_POST['val'])) {
                $is_single = (isset($_POST['single_member']) && $_POST['single_member'] == '1');

                if ($is_admin && !$is_single) {
                    $sql = "DELETE FROM tbl_attendance WHERE date = ? AND group_id = ?";
                    $parameters = [$_POST['date'], $_POST['val']];
                    $types = "si";
                    if (!app_exec_roll_back_nonquery($conn, $sql, $parameters, $types)) {
                        throw new Exception("Error saving attendance");
                    }
                    $member_ids = $_POST['member_ids'] ?? [];
                    foreach ($member_ids as $mid) {
                        $sql = "INSERT INTO tbl_attendance (member_id, group_id, date) VALUES (?, ?, ?)";
                        app_exec_roll_back_nonquery($conn, $sql, [$mid, $_POST['val'], $_POST['date']], "iis");
                    }
                } else {
                    $member_id = getLoggedInMemberId();
                    if (!empty($_POST['member_ids']) && is_array($_POST['member_ids'])) {
                        $m_array = $_POST['member_ids'];
                        if (count($m_array) > 0 && (int)$m_array[0] > 0) {
                            $member_id = (int)$m_array[0];
                        }
                    }

                    if ($member_id > 0) {
                        $sql = "DELETE FROM tbl_attendance WHERE date = ? AND group_id = ? AND member_id = ?";
                        $parameters = [$_POST['date'], $_POST['val'], $member_id];
                        $types = "sii";
                        app_exec_roll_back_nonquery($conn, $sql, $parameters, $types);

                        // Insert if marking (member_ids non-empty)
                        if (isset($_POST['member_ids']) && is_array($_POST['member_ids']) && count($_POST['member_ids']) > 0) {
                            $sql = "INSERT INTO tbl_attendance (member_id, group_id, date) VALUES (?, ?, ?)";
                            app_exec_roll_back_nonquery($conn, $sql, [$member_id, $_POST['val'], $_POST['date']], "iis");

                            // Save expected arrival time in temporary status table if provided, or clear if empty
                            $expected_time = !empty($_POST['expected_time']) ? trim($_POST['expected_time']) : null;
                            $login_id = !empty($_SESSION['login_id']) ? (int)$_SESSION['login_id'] : 0;
                            if (!empty($expected_time)) {
                                $ins_temp = "INSERT INTO tbl_temp_attendance_status (login_id, member_id, group_id, status, expected_time, status_date) 
                                            VALUES (?, ?, ?, 'present', ?, ?) 
                                            ON DUPLICATE KEY UPDATE status = 'present', expected_time = VALUES(expected_time), created_at = CURRENT_TIMESTAMP";
                                app_exec_roll_back_nonquery($conn, $ins_temp, [$login_id, $member_id, $_POST['val'], $expected_time, $_POST['date']], "iiiss");
                            } else {
                                $del_temp = "DELETE FROM tbl_temp_attendance_status WHERE (login_id = ? OR member_id = ?) AND status_date = ? AND group_id = ?";
                                app_exec_roll_back_nonquery($conn, $del_temp, [$login_id, $member_id, $_POST['date'], $_POST['val']], "iisi");
                            }
                        } else {
                            $login_id = !empty($_SESSION['login_id']) ? (int)$_SESSION['login_id'] : 0;
                            $del_temp = "DELETE FROM tbl_temp_attendance_status WHERE (login_id = ? OR member_id = ?) AND status_date = ? AND group_id = ?";
                            app_exec_roll_back_nonquery($conn, $del_temp, [$login_id, $member_id, $_POST['date'], $_POST['val']], "iisi");
                        }
                    } else {
                        throw new Exception("Member ID not found for logged in user");
                    }
                }
                
                $tm->commit();
                echo "Transaction Successful!";
                exit();
            }
        }
        catch (Exception $e) {
            $tm->rollback();
            echo $e->getMessage();
            exit();
        }
    }
    //action to add attendance of selected members end

    //action to fetch the attendance details of a given date start
    if($action=="fetch_Attendance_details"){
        try{
            if (isset($_POST['date'])) {
                $sql = "SELECT member_id FROM tbl_attendance WHERE group_id = ? AND date = ?";
                $searchvalue = $_POST['date'];
                $parameters = [
                    $_POST['group'],
                    $searchvalue,
                ];
                $types = "is";
                $result = app_exec_getresult($sql, $parameters, $types);
            }
            if ($result && $result->num_rows > 0) {
                $data = [];
                while ($row = $result->fetch_assoc()) {
                    $data[] = $row;
                }
            } else {
                $data = [];
            }
            echo json_encode($data);
            exit();
        }
        catch (Exception $e) {
            http_response_code(500);   
            echo "Oops! Something went wrong." . $e->getMessage();
            return;
        }
    }
    // action to fetch the attendance details of a given date end

    if($action=="check_holiday"){
        try {
            $group_id = isset($_POST['group']) ? (int)$_POST['group'] : 0;
            $date = $_POST['date'];
            $sql = "SELECT id FROM tbl_dates WHERE date = ? AND (group_id = 0 OR group_id = ?)";
            $res = app_exec_getresult($sql, [$date, $group_id], "si");
            $is_holiday = ($res && $res->num_rows > 0);
            $is_fixed = isMonthFixed($date);
            echo json_encode(["is_holiday" => $is_holiday, "is_fixed" => $is_fixed]);
            exit();
        } catch (Exception $e) {
            echo json_encode(["is_holiday" => false, "is_fixed" => false]);
            exit();
        }
    }



    // Action to save/clear temporary status (Absent / Half Chance) - Stored in separate table for 2 days only
    if ($action == "save_temp_status") {
        cleanupExpiredTempAttendance();
        try {
            $date = $_POST['date'] ?? date('Y-m-d');
            $group_id = (int)($_POST['group'] ?? 0);
            $status = trim($_POST['status'] ?? '');
            $expected_time = !empty($_POST['expected_time']) ? trim($_POST['expected_time']) : null;
            $login_id = !empty($_SESSION['login_id']) ? (int)$_SESSION['login_id'] : 0;
            $member_id = getLoggedInMemberId();

            if (empty($login_id)) {
                echo json_encode(['status' => 'error', 'message' => 'User not logged in']);
                exit();
            }

            if (!isTomorrowAttendanceAllowed($group_id, $date)) {
                echo json_encode(['status' => 'error', 'message' => "Tomorrow's attendance is not enabled for this group."]);
                exit();
            }

            if ($status === 'clear' || empty($status)) {
                $del_sql = "DELETE FROM tbl_temp_attendance_status WHERE login_id = ? AND status_date = ? AND group_id = ?";
                app_exec_nonquery($del_sql, [$login_id, $date, $group_id], "isi");
                echo json_encode(['status' => 'success', 'message' => 'Status cleared.']);
                exit();
            }

            if (!in_array($status, ['absent', 'half_chance'])) {
                echo json_encode(['status' => 'error', 'message' => 'Invalid status option.']);
                exit();
            }

            // Unmark present from tbl_attendance if marking absent or half chance
            $del_att = "DELETE FROM tbl_attendance WHERE date = ? AND group_id = ? AND member_id = ?";
            app_exec_nonquery($del_att, [$date, $group_id, $member_id], "sii");

            // Save in separate temporary table tbl_temp_attendance_status
            $ins_sql = "INSERT INTO tbl_temp_attendance_status (login_id, member_id, group_id, status, expected_time, status_date) 
                        VALUES (?, ?, ?, ?, ?, ?) 
                        ON DUPLICATE KEY UPDATE status = VALUES(status), expected_time = VALUES(expected_time), created_at = CURRENT_TIMESTAMP";
            app_exec_nonquery($ins_sql, [$login_id, $member_id, $group_id, $status, $expected_time, $date], "iiisss");

            $status_label = ($status === 'absent') ? 'Marked Absent (Temporary 2 Days)' : 'Marked Half Chance (Temporary 2 Days)';
            echo json_encode(['status' => 'success', 'message' => $status_label, 'temp_status' => $status, 'expected_time' => $expected_time]);
            exit();
        } catch (Throwable $e) {
            echo json_encode(['status' => 'error', 'message' => $e->getMessage()]);
            exit();
        }
    }

    // Action to fetch temporary status for logged-in user
    if ($action == "fetch_temp_status") {
        cleanupExpiredTempAttendance();
        try {
            $date = $_POST['date'] ?? date('Y-m-d');
            $group_id = (int)($_POST['group'] ?? 0);
            $login_id = !empty($_SESSION['login_id']) ? (int)$_SESSION['login_id'] : 0;

            $sql = "SELECT status, expected_time FROM tbl_temp_attendance_status WHERE login_id = ? AND status_date = ? AND group_id = ? LIMIT 1";
            $res = app_exec_getresult($sql, [$login_id, $date, $group_id], "isi");
            $temp_status = null;
            $expected_time = null;
            if ($res && $row = $res->fetch_assoc()) {
                $temp_status = $row['status'];
                $expected_time = $row['expected_time'];
            }
            $member_id = getLoggedInMemberId();
            echo json_encode(['status' => 'success', 'temp_status' => $temp_status, 'expected_time' => $expected_time, 'member_id' => $member_id]);
            exit();
        } catch (Throwable $e) {
            echo json_encode(['status' => 'error', 'temp_status' => null, 'expected_time' => null, 'member_id' => 0]);
            exit();
        }
    }

    // Action to load present, half-chance, and absent members for selected date and group
    if ($action == "load_today_present_members" || $action == "load_today_attendance_summary") {
        cleanupExpiredTempAttendance();
        try {
            $date = $_POST['date'] ?? date('Y-m-d');
            $group = (int)($_POST['group'] ?? 0);

            // 1. Fetch Present Members from tbl_attendance (with expected_time if recorded in temp status)
            $sqlPresent = "SELECT m.id AS member_id, m.first_name, m.middle_name, m.last_name, m.img, 'present' AS status, t.expected_time
                           FROM tbl_attendance att
                           JOIN tbl_members m ON att.member_id = m.id
                           LEFT JOIN tbl_login l ON LTRIM(RTRIM(LOWER(l.email))) = LTRIM(RTRIM(LOWER(m.email)))
                           LEFT JOIN tbl_temp_attendance_status t ON (t.member_id = m.id OR t.login_id = l.login_id) AND t.group_id = att.group_id AND t.status_date = att.date
                           WHERE att.group_id = ? AND att.date = ?
                           ORDER BY m.first_name, m.middle_name, m.last_name";
            $resPresent = app_exec_getresult($sqlPresent, [$group, $date], "is");

            $data = [];
            $presentMemberIds = [];
            if ($resPresent && $resPresent->num_rows > 0) {
                while ($row = $resPresent->fetch_assoc()) {
                    $data[] = $row;
                    $presentMemberIds[] = (int)$row['member_id'];
                }
            }

            // 2. Fetch Temp Status Members (Half Chance / Absent) from tbl_temp_attendance_status
            $sqlTemp = "SELECT t.member_id, t.status, t.expected_time,
                               COALESCE(NULLIF(m.first_name, ''), l.name) AS first_name, 
                               m.middle_name, 
                               m.last_name, 
                               m.img
                        FROM tbl_temp_attendance_status t
                        LEFT JOIN tbl_members m ON t.member_id = m.id OR LTRIM(RTRIM(LOWER(m.email))) = (SELECT LTRIM(RTRIM(LOWER(email))) FROM tbl_login WHERE login_id = t.login_id LIMIT 1)
                        LEFT JOIN tbl_login l ON t.login_id = l.login_id
                        WHERE t.group_id = ? AND t.status_date = ? AND t.status IN ('half_chance', 'absent')
                        ORDER BY FIELD(t.status, 'half_chance', 'absent'), first_name ASC";
            $resTemp = app_exec_getresult($sqlTemp, [$group, $date], "is");

            if ($resTemp && $resTemp->num_rows > 0) {
                while ($trow = $resTemp->fetch_assoc()) {
                    if (!in_array((int)$trow['member_id'], $presentMemberIds)) {
                        $data[] = $trow;
                    }
                }
            }

            echo json_encode($data);
            exit();
        } catch (Exception $e) {
            http_response_code(500);
            echo json_encode([]);
            exit();
        }
    }

    if($action=="verify_attendance"){
        checkMonthFixed($_POST['date']);
        try{
            $sql = "SELECT id FROM tbl_attendance WHERE date = ? ";
            $parameters = [
                $_POST['date'],
            ];
        
            $types="s";

            $result = app_exec_getresult($sql,$parameters,$types);
           
            $row = $result->fetch_assoc();

            if ($row === null) {
                throw new Exception("Attendance not marked on this date");
            }

            $sql = "SELECT id FROM tbl_attendance WHERE date = ? AND verified = 1";
            $parameters = [
                $_POST['date'],
            ];
        
            $types="s";

            $result = app_exec_getresult($sql,$parameters,$types);
           
            $row1 = $result->fetch_assoc();

            if ($row1 !== null) {
                throw new Exception("Attendance already verified for this date");
            }

            $sql = "UPDATE tbl_attendance SET verified=1 WHERE date=?";
            $parameters = [
                $_POST['date'],
            ];
            $types="s";
            $result=app_exec_nonquery($sql,$parameters,$types);
                
        }
        catch (Exception $e) {
            http_response_code(500);
            header('Content-Type: application/json');
            echo json_encode(array('Message' => $e->getMessage()));
            return;
        }
    }
}

// ---- load_member_month_attendance (used by mobile home.php calendar dots) ----
if (isset($_POST['action']) && $_POST['action'] === 'load_member_month_attendance') {
    $member_id = (int)($_POST['member_id'] ?? getLoggedInMemberId());
    $year      = preg_replace('/[^0-9]/', '', $_POST['year']  ?? date('Y'));
    $month     = preg_replace('/[^0-9]/', '', $_POST['month'] ?? date('m'));

    $month_start = $year . '-' . str_pad($month, 2, '0', STR_PAD_LEFT) . '-01';
    $month_end   = date('Y-m-t', strtotime($month_start));

    $sql    = "SELECT DAY(date) AS day FROM tbl_attendance WHERE member_id = ? AND date BETWEEN ? AND ? ORDER BY date";
    $result = app_exec_getresult($sql, [$member_id, $month_start, $month_end], "iss");

    $days = [];
    if ($result) {
        while ($row = $result->fetch_assoc()) {
            $days[] = ['day' => $row['day']];
        }
    }
    header('Content-Type: application/json');
    echo json_encode($days);
    exit();
}

?>