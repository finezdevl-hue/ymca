<?php
require_once __DIR__ . '/api_bootstrap.php';
require_attendance_master();
include_once __DIR__ . '/attendance_fix_helper.php';
include_once __DIR__ . '/../../app_pagination/pagination.php';
include_once __DIR__ . '/../../app_common/auth_helper.php';

$is_admin = false;
$is_only_attendance_master = false;
if (isset($_SESSION['login_id'])) {
    $login_id = (int)$_SESSION['login_id'];
    if (isSuperAdmin($login_id) || isGroupAdmin($login_id)) {
        $is_admin = true;
    } else if (isAttendanceMaster($login_id) || isExecutiveMember($login_id)) {
        $is_admin = true;
        $is_only_attendance_master = true;
    } else {
        $sql_perm = "SELECT id FROM tbl_menu_map WHERE login_id = ? AND (menu_id = 11 OR menu_id = 12) AND is_active = 1";
        $res_perm = app_exec_getresult($sql_perm, [$login_id], "i");
        if ($res_perm && $res_perm->num_rows > 0) {
            $is_admin = true;
        }
    }
}

// Release session lock early for read-only actions so concurrent browser AJAX requests run in parallel
$req_action = $_POST['action'] ?? $_GET['action'] ?? '';
$read_only_actions = [
    'load_groups', 'load_game_status', 'check_holiday',
    'get_shuttle_reading', 'fetch_Attendance_details',
    'fetch_temp_status', 'load_member_data', 'load_all_today_members',
    'load_today_present'
];
if (in_array($req_action, $read_only_actions, true)) {
    session_write_close();
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
    $resFirst = app_exec_query("SELECT id FROM tbl_members WHERE inactive = 0 ORDER BY id ASC LIMIT 1");
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

// Helper to save or update shuttle cock reading
function saveShuttleReadingInternal(mysqli $conn, int $group_id, string $date, int $shuttles_used, ?TransactionManager $tm = null, ?string $notes = null): array {
    if ($shuttles_used < 0) {
        return ['status' => 'error', 'message' => 'Shuttle count cannot be negative.'];
    }

    // Check stock balance for this group (or shared pool)
    $sum_in_res = app_exec_getresult("SELECT SUM(total_shuttles) as tot_in FROM tbl_shuttle_cock_stock_in WHERE group_id = ? OR group_id = 0", [$group_id], "i");
    $total_in = (int)($sum_in_res ? $sum_in_res->fetch_assoc()['tot_in'] : 0);

    $chk_shuttle = app_exec_getresult("SELECT id, start_shuttle FROM tbl_shuttle_cock_readings WHERE group_id = ? AND reading_date = ? LIMIT 1", [$group_id, $date], "is");
    $ex_id = 0;
    $ex_start_shuttle = 0;
    if ($chk_shuttle && $sh_row = $chk_shuttle->fetch_assoc()) {
        $ex_id = (int)$sh_row['id'];
        $ex_start_shuttle = (int)$sh_row['start_shuttle'];
    }

    $where_id_clause = ($ex_id > 0) ? " AND id != $ex_id" : "";
    $sum_used_res = app_exec_getresult("SELECT SUM(shuttles_used) as tot_used FROM tbl_shuttle_cock_readings WHERE group_id = ?{$where_id_clause}", [$group_id], "i");
    $total_used = (int)($sum_used_res ? $sum_used_res->fetch_assoc()['tot_used'] : 0);

    $stock_remaining = max(0, $total_in - $total_used);

    if ($shuttles_used > 0) {
        if ($stock_remaining <= 0) {
            return ['status' => 'error', 'message' => 'Out of Stock! Available shuttle cock inventory is 0. Please add inward stock first.'];
        }
        if ($shuttles_used > $stock_remaining) {
            return ['status' => 'error', 'message' => "Insufficient Stock! Only {$stock_remaining} shuttles remaining in inventory."];
        }
    }

    $price_res = app_exec_query("SELECT per_shuttle_price, brand_name FROM tbl_shuttle_cock_stock_in ORDER BY purchase_date DESC, id DESC LIMIT 1");
    $unit_price = 0.00;
    $brand_name = 'Standard';
    if ($price_res && $pr = $price_res->fetch_assoc()) {
        $unit_price = (float)$pr['per_shuttle_price'];
        $brand_name = $pr['brand_name'] ?: 'Standard';
    }

    if ($ex_id > 0) {
        $end_shuttle = $ex_start_shuttle + $shuttles_used;
        $total_amount = $shuttles_used * $unit_price;
        $updated_by = (int)($_SESSION['login_id'] ?? 1);
        if ($notes !== null) {
            if ($tm !== null) {
                app_exec_roll_back_nonquery($conn, "UPDATE tbl_shuttle_cock_readings SET shuttles_used = ?, end_shuttle = ?, per_shuttle_price = ?, total_amount = ?, notes = ?, updated_by = ?, updated_at = NOW() WHERE id = ?", [$shuttles_used, $end_shuttle, $unit_price, $total_amount, $notes, $updated_by, $ex_id], "iiddsii");
            } else {
                $stmt = $conn->prepare("UPDATE tbl_shuttle_cock_readings SET shuttles_used = ?, end_shuttle = ?, per_shuttle_price = ?, total_amount = ?, notes = ?, updated_by = ?, updated_at = NOW() WHERE id = ?");
                if ($stmt) {
                    $stmt->bind_param("iiddsii", $shuttles_used, $end_shuttle, $unit_price, $total_amount, $notes, $updated_by, $ex_id);
                    $stmt->execute();
                    $stmt->close();
                }
            }
        } else {
            if ($tm !== null) {
                app_exec_roll_back_nonquery($conn, "UPDATE tbl_shuttle_cock_readings SET shuttles_used = ?, end_shuttle = ?, per_shuttle_price = ?, total_amount = ?, updated_by = ?, updated_at = NOW() WHERE id = ?", [$shuttles_used, $end_shuttle, $unit_price, $total_amount, $updated_by, $ex_id], "iiddii");
            } else {
                $stmt = $conn->prepare("UPDATE tbl_shuttle_cock_readings SET shuttles_used = ?, end_shuttle = ?, per_shuttle_price = ?, total_amount = ?, updated_by = ?, updated_at = NOW() WHERE id = ?");
                if ($stmt) {
                    $stmt->bind_param("iiddii", $shuttles_used, $end_shuttle, $unit_price, $total_amount, $updated_by, $ex_id);
                    $stmt->execute();
                    $stmt->close();
                }
            }
        }
    } else {
        $last_res = app_exec_getresult("SELECT end_shuttle FROM tbl_shuttle_cock_readings WHERE group_id = ? ORDER BY reading_date DESC, id DESC LIMIT 1", [$group_id], "i");
        $start_shuttle = 0;
        if ($last_res && $lr = $last_res->fetch_assoc()) {
            $start_shuttle = (int)$lr['end_shuttle'];
        }
        $end_shuttle = $start_shuttle + $shuttles_used;
        $total_amount = $shuttles_used * $unit_price;
        $created_by = (int)($_SESSION['login_id'] ?? 1);
        $insert_notes = ($notes !== null) ? $notes : '';
        if ($tm !== null) {
            app_exec_roll_back_nonquery($conn, "INSERT INTO tbl_shuttle_cock_readings (reading_date, group_id, brand_name, start_shuttle, end_shuttle, shuttles_used, per_shuttle_price, total_amount, notes, created_by) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)", [$date, $group_id, $brand_name, $start_shuttle, $end_shuttle, $shuttles_used, $unit_price, $total_amount, $insert_notes, $created_by], "sisiiiddsi");
        } else {
            $stmt = $conn->prepare("INSERT INTO tbl_shuttle_cock_readings (reading_date, group_id, brand_name, start_shuttle, end_shuttle, shuttles_used, per_shuttle_price, total_amount, notes, created_by) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
            if ($stmt) {
                $stmt->bind_param("sisiiiddsi", $date, $group_id, $brand_name, $start_shuttle, $end_shuttle, $shuttles_used, $unit_price, $total_amount, $insert_notes, $created_by);
                $stmt->execute();
                $stmt->close();
            }
        }
    }

    return ['status' => 'success', 'message' => 'Shuttle count saved successfully.'];
}

// actions start
if(isset($_POST['action']) && !empty($_POST['action'])) {
    $action = $_POST['action'];

    //action load members in a selected group start
    if($action=="load_member_data"){
        try{
            $group_id = (int)$_POST['val'];
            
            // Allow full group member view if user is Super Admin, Executive Member, or has permission for this specific group (Group Cashier, Attendance Master)
            $can_manage_group = (isSuperAdmin($login_id) || isExecutiveMember($login_id) || hasGroupAccess($login_id, $group_id));

            $sql="";  
            $sqlcountrows="";
            $sqldatarows="";   
    
            $sqldatarows = "SELECT m.id, m.first_name, m.middle_name, m.last_name, m.img, gmm.group_id,
            COUNT(att.member_id) AS total_att,
            COUNT(prev_att.member_id) AS was_prev_present
            FROM tbl_members AS m 
            JOIN tbl_group_member_map AS gmm ON m.id = gmm.member_id
            LEFT JOIN tbl_attendance AS att ON m.id = att.member_id 
                 AND att.date >= DATE_SUB(CURDATE(), INTERVAL 3 MONTH)
            LEFT JOIN tbl_attendance AS prev_att ON m.id = prev_att.member_id 
                 AND prev_att.group_id = gmm.group_id 
                 AND prev_att.date = (SELECT MAX(date) FROM tbl_attendance WHERE group_id = ?)
            WHERE gmm.group_id = ? AND m.inactive = 0";
            
            if (!$can_manage_group) {
                $sqldatarows .= " AND m.id = ?";
            }
            
            $sqldatarows .= " GROUP BY m.id, gmm.group_id, m.first_name, m.middle_name, m.last_name, m.img
            ORDER BY was_prev_present DESC, total_att DESC, m.first_name, m.middle_name, m.last_name";

            if ($can_manage_group) {
                $parameters = [
                    $group_id,
                    $group_id,
                ];
                $types = "ii";
            } else {
                $parameters = [
                    $group_id,
                    $group_id,
                    getLoggedInMemberId(),
                ];
                $types = "iii";
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
            $allowed = getUserAllowedGroupIds($login_id);
            if (in_array('ALL', $allowed, true)) {
                $sql = "SELECT id, name, allow_tomorrow_attendance FROM tbl_groups WHERE status = 1 ORDER BY id DESC";
                $result = app_exec_query($sql);
            } else if (!empty($allowed)) {
                $placeholders = implode(',', array_fill(0, count($allowed), '?'));
                $types = str_repeat('i', count($allowed));
                $sql = "SELECT id, name, allow_tomorrow_attendance FROM tbl_groups WHERE id IN ($placeholders) AND status = 1 ORDER BY name ASC";
                $result = app_exec_getresult($sql, $allowed, $types);
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
        
        // Attendance Master restriction: only today / current date (or tomorrow if enabled) allowed, not previous/past dates
        date_default_timezone_set('Asia/Kolkata');
        $today = date('Y-m-d');
        if ($is_only_attendance_master && $_POST['date'] < $today) {
            http_response_code(403);
            echo "Attendance Masters are only permitted to mark attendance for today's date.";
            exit();
        }

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
                        if ((int)$mid > 0) {
                            $sql = "INSERT INTO tbl_attendance (member_id, group_id, date) VALUES (?, ?, ?)";
                            app_exec_roll_back_nonquery($conn, $sql, [(int)$mid, $_POST['val'], $_POST['date']], "iis");
                        }
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

                // ===== Save Shuttle Cock Reading (if provided) =====
                if (isset($_POST['shuttles_used']) && $_POST['shuttles_used'] !== '') {
                    $att_shuttles_used = (int)$_POST['shuttles_used'];
                    $group_val_sh = (int)$_POST['val'];
                    $date_sh = $_POST['date'];
                    $att_notes = isset($_POST['notes']) ? trim($_POST['notes']) : null;

                    if ($att_shuttles_used >= 0) {
                        $sh_res = saveShuttleReadingInternal($conn, $group_val_sh, $date_sh, $att_shuttles_used, $tm, $att_notes);
                        if ($sh_res['status'] !== 'success') {
                            $tm->rollback();
                            http_response_code(400);
                            echo $sh_res['message'];
                            exit();
                        }
                    }
                }

                $tm->commit();

                // Dynamic fee sync for processed, unfixed months
                $sync_mids = !empty($member_ids) ? $member_ids : (isset($member_id) ? [$member_id] : []);
                $sync_gid = isset($_POST['val']) ? (int)$_POST['val'] : 0;
                syncMonthlyAttendanceAndReceivables($_POST['date'], $sync_mids, $sync_gid);

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

    // Action to save ONLY shuttle cock reading (e.g. from Mobile Home card without touching attendance)
    if ($action == "save_shuttle_reading") {
        try {
            $group_val_sh = (int)($_POST['group_id'] ?? $_POST['val'] ?? 0);
            $date_sh = trim($_POST['date'] ?? date('Y-m-d'));
            $att_shuttles_used = isset($_POST['shuttles_used']) && $_POST['shuttles_used'] !== '' ? (int)$_POST['shuttles_used'] : 0;
            $att_notes = isset($_POST['notes']) ? trim($_POST['notes']) : null;

            if ($group_val_sh <= 0) {
                http_response_code(400);
                echo "Invalid group selected.";
                exit();
            }

            $db = new Database();
            $conn = $db->getConnection();
            $result = saveShuttleReadingInternal($conn, $group_val_sh, $date_sh, $att_shuttles_used, null, $att_notes);
            if ($result['status'] !== 'success') {
                http_response_code(400);
                echo $result['message'];
                exit();
            }

            echo json_encode($result);
            exit();
        } catch (Exception $e) {
            http_response_code(500);
            echo $e->getMessage();
            exit();
        }
    }

    // Action to fetch shuttle cock reading for attendance page
    if ($action == "get_shuttle_reading") {
        try {
            $group_id = (int)($_POST['group_id'] ?? 0);
            $date = trim($_POST['date'] ?? date('Y-m-d'));
            $res = app_exec_getresult("SELECT id, shuttles_used, notes, created_by, updated_by FROM tbl_shuttle_cock_readings WHERE group_id = ? AND reading_date = ? LIMIT 1", [$group_id, $date], "is");
            $used = 0;
            $notes = '';
            $has_record = false;
            $ex_id = 0;
            $created_by_id = 0;
            $created_by_name = '';
            $updated_by_id = 0;
            $updated_by_name = '';
            $is_edited = false;
            $last_user_id = 0;
            $last_user_name = '';
            $current_user_id = (int)($_SESSION['login_id'] ?? 0);

            if (!function_exists('resolveShuttleCreatorName')) {
                function resolveShuttleCreatorName($cid) {
                    if ($cid <= 0) return 'Admin';
                    $res = app_exec_getresult("SELECT email, name FROM tbl_login WHERE login_id = ? LIMIT 1", [$cid], "i");
                    if ($res && $lrow = $res->fetch_assoc()) {
                        $email = $lrow['email'] ?? '';
                        $lname = trim($lrow['name'] ?? '');
                        if (!empty($email)) {
                            $mres = app_exec_getresult("SELECT CONCAT(first_name, IF(middle_name IS NOT NULL AND middle_name != '', CONCAT(' ', middle_name), ''), IF(last_name IS NOT NULL AND last_name != '', CONCAT(' ', last_name), '')) as full_name FROM tbl_members WHERE LTRIM(RTRIM(LOWER(email))) = LTRIM(RTRIM(LOWER(?))) LIMIT 1", [$email], "s");
                            if ($mres && $mrow = $mres->fetch_assoc()) {
                                $fname = trim($mrow['full_name'] ?? '');
                                if (!empty($fname)) return $fname;
                            }
                        }
                        if (!empty($lname)) return $lname;
                    }
                    $mres2 = app_exec_getresult("SELECT CONCAT(first_name, IF(middle_name IS NOT NULL AND middle_name != '', CONCAT(' ', middle_name), ''), IF(last_name IS NOT NULL AND last_name != '', CONCAT(' ', last_name), '')) as full_name FROM tbl_members WHERE id = ? LIMIT 1", [$cid], "i");
                    if ($mres2 && $mrow2 = $mres2->fetch_assoc()) {
                        $fname2 = trim($mrow2['full_name'] ?? '');
                        if (!empty($fname2)) return $fname2;
                    }
                    return 'Admin';
                }
            }

            if ($res && $row = $res->fetch_assoc()) {
                $used = (int)$row['shuttles_used'];
                $notes = (string)($row['notes'] ?? '');
                $has_record = true;
                $ex_id = (int)$row['id'];
                $created_by_id = (int)($row['created_by'] ?? 0);
                $updated_by_id = (int)($row['updated_by'] ?? 0);

                $created_by_name = resolveShuttleCreatorName($created_by_id);

                if ($updated_by_id > 0) {
                    $is_edited = true;
                    $updated_by_name = resolveShuttleCreatorName($updated_by_id);
                    $last_user_id = $updated_by_id;
                    $last_user_name = $updated_by_name;
                } else {
                    $last_user_id = $created_by_id;
                    $last_user_name = $created_by_name;
                }
            }

            $is_created_by_me = ($last_user_id > 0 && $last_user_id === $current_user_id);
            $action_word = $is_edited ? 'updated' : 'added';

            // Query previous session's note (most recent before current date for this group or general)
            $prev_notes = '';
            $prev_reading_date = '';
            $prev_formatted_date = '';
            $prev_user_name = '';
            $prev_shuttles_used = 0;

            $prev_res = app_exec_getresult(
                "SELECT reading_date, shuttles_used, notes, created_by, updated_by 
                 FROM tbl_shuttle_cock_readings 
                 WHERE (group_id = ? OR group_id = 0) 
                   AND reading_date < ? 
                   AND notes IS NOT NULL 
                   AND TRIM(notes) != '' 
                 ORDER BY reading_date DESC, id DESC LIMIT 1",
                [$group_id, $date],
                "is"
            );

            if ($prev_res && $prow = $prev_res->fetch_assoc()) {
                $prev_notes = trim((string)$prow['notes']);
                $prev_reading_date = (string)$prow['reading_date'];
                $prev_formatted_date = date('d M Y', strtotime($prev_reading_date));
                $prev_shuttles_used = (int)$prow['shuttles_used'];
                $p_uid = (int)($prow['updated_by'] ?: $prow['created_by'] ?: 0);
                $prev_user_name = resolveShuttleCreatorName($p_uid);
            }

            if (empty($prev_notes)) {
                $prev_res2 = app_exec_getresult(
                    "SELECT reading_date, shuttles_used, notes, created_by, updated_by 
                     FROM tbl_shuttle_cock_readings 
                     WHERE reading_date < ? 
                       AND notes IS NOT NULL 
                       AND TRIM(notes) != '' 
                     ORDER BY reading_date DESC, id DESC LIMIT 1",
                    [$date],
                    "s"
                );
                if ($prev_res2 && $prow2 = $prev_res2->fetch_assoc()) {
                    $prev_notes = trim((string)$prow2['notes']);
                    $prev_reading_date = (string)$prow2['reading_date'];
                    $prev_formatted_date = date('d M Y', strtotime($prev_reading_date));
                    $prev_shuttles_used = (int)$prow2['shuttles_used'];
                    $p_uid = (int)($prow2['updated_by'] ?: $prow2['created_by'] ?: 0);
                    $prev_user_name = resolveShuttleCreatorName($p_uid);
                }
            }

            // Calculate stock remaining for this group
            $sum_in_res = app_exec_getresult("SELECT SUM(total_shuttles) as tot_in FROM tbl_shuttle_cock_stock_in WHERE group_id = ? OR group_id = 0", [$group_id], "i");
            $total_in = (int)($sum_in_res ? $sum_in_res->fetch_assoc()['tot_in'] : 0);

            $where_id_clause = ($ex_id > 0) ? " AND id != $ex_id" : "";
            $sum_used_res = app_exec_getresult("SELECT SUM(shuttles_used) as tot_used FROM tbl_shuttle_cock_readings WHERE group_id = ?{$where_id_clause}", [$group_id], "i");
            $total_used = (int)($sum_used_res ? $sum_used_res->fetch_assoc()['tot_used'] : 0);

            $stock_remaining = max(0, $total_in - $total_used);

            echo json_encode([
                'status' => 'success',
                'shuttles_used' => $used,
                'notes' => $notes,
                'has_record' => $has_record,
                'created_by_id' => $created_by_id,
                'created_by_name' => $created_by_name,
                'updated_by_id' => $updated_by_id,
                'updated_by_name' => $updated_by_name,
                'is_edited' => $is_edited,
                'last_user_id' => $last_user_id,
                'last_user_name' => $last_user_name,
                'action_word' => $action_word,
                'is_created_by_me' => $is_created_by_me,
                'stock_remaining' => $stock_remaining,
                'prev_notes' => $prev_notes,
                'prev_reading_date' => $prev_reading_date,
                'prev_formatted_date' => $prev_formatted_date,
                'prev_user_name' => $prev_user_name,
                'prev_shuttles_used' => $prev_shuttles_used
            ]);
            exit();
        } catch (Exception $e) {
            echo json_encode(['status' => 'error', 'message' => $e->getMessage()]);
            exit();
        }
    }

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

            if ($status !== 'absent') {
                echo json_encode(['status' => 'error', 'message' => 'Invalid status option.']);
                exit();
            }

            // Unmark present from tbl_attendance if marking absent
            $del_att = "DELETE FROM tbl_attendance WHERE date = ? AND group_id = ? AND member_id = ?";
            app_exec_nonquery($del_att, [$date, $group_id, $member_id], "sii");

            // Save in separate temporary table tbl_temp_attendance_status
            $ins_sql = "INSERT INTO tbl_temp_attendance_status (login_id, member_id, group_id, status, expected_time, status_date) 
                        VALUES (?, ?, ?, ?, ?, ?) 
                        ON DUPLICATE KEY UPDATE status = VALUES(status), expected_time = VALUES(expected_time), created_at = CURRENT_TIMESTAMP";
            app_exec_nonquery($ins_sql, [$login_id, $member_id, $group_id, $status, $expected_time, $date], "iiisss");

            $status_label = 'Marked Absent (Temporary 2 Days)';
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

            // 2. Fetch Temp Status Members (Absent) from tbl_temp_attendance_status
            $sqlTemp = "SELECT t.member_id, t.status, t.expected_time,
                               COALESCE(NULLIF(m.first_name, ''), l.name) AS first_name, 
                               m.middle_name, 
                               m.last_name, 
                               m.img
                        FROM tbl_temp_attendance_status t
                        LEFT JOIN tbl_members m ON t.member_id = m.id OR LTRIM(RTRIM(LOWER(m.email))) = (SELECT LTRIM(RTRIM(LOWER(email))) FROM tbl_login WHERE login_id = t.login_id LIMIT 1)
                        LEFT JOIN tbl_login l ON t.login_id = l.login_id
                        WHERE t.group_id = ? AND t.status_date = ? AND t.status = 'absent'
                        ORDER BY first_name ASC";
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