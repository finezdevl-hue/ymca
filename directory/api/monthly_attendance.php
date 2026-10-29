<?php
require_once __DIR__ . '/api_bootstrap.php';
require_attendance_master();
include_once __DIR__ . '/attendance_fix_helper.php';
    include_once __DIR__ . '/../../app_pagination/pagination.php';

    // actions start
    if(isset($_POST['action']) && !empty($_POST['action'])) {
        $action = $_POST['action'];

        // action to save monthly attendance start
        if($action=="save_attendance"){ 
            // Auto-fix previous month when processing current month
            autoFixPreviousMonth($_POST['from_date']);
            
            // Limit attendance compiled to a single calendar month at a time
            $from_yr_mo = date('Y-m', strtotime($_POST['from_date']));
            $to_yr_mo   = date('Y-m', strtotime($_POST['to_date']));
            if ($from_yr_mo !== $to_yr_mo) {
                http_response_code(400);
                header('Content-Type: application/json');
                echo json_encode(array('Message' => "You can only compile attendance for one calendar month at a time."));
                exit;
            }

            // Ensure to_date is strictly the end of the month
            $expected_to_date = date('Y-m-t', strtotime($_POST['from_date']));
            if ($_POST['to_date'] !== $expected_to_date) {
                $_POST['to_date'] = $expected_to_date;
            }

            // Block processing attendance before the month has ended
            if (!isMonthEnded($_POST['from_date'])) {
                $month_name = date('F Y', strtotime($_POST['from_date']));
                http_response_code(400);
                header('Content-Type: application/json');
                echo json_encode(array('Message' => "Attendance for $month_name can only be processed after the month ends."));
                exit;
            }
            
            // Block closed financial years
            $active_yr_res = app_exec_query("SELECT from_year FROM tbl_closing ORDER BY from_year DESC LIMIT 1");
            if ($active_yr_res && $row_yr = $active_yr_res->fetch_assoc()) {
                $active_year_start = $row_yr['from_year'] . "-04-01";
                if ($_POST['from_date'] < $active_year_start) {
                    http_response_code(400);
                    header('Content-Type: application/json');
                    echo json_encode(array('Message' => "Cannot set monthly attendance for closed financial years."));
                    exit;
                }
            }

            // Block processing if attendance for this month has already been processed once
            $chk_already_sql = "SELECT id FROM tbl_monthly_attendance WHERE from_date = ? AND to_date = ?";
            $chk_already_params = [$_POST['from_date'], $_POST['to_date']];
            $chk_already_types = "ss";
            if (isset($_POST['group_id']) && (int)$_POST['group_id'] > 0) {
                $chk_already_sql .= " AND member_id IN (SELECT member_id FROM tbl_group_member_map WHERE group_id = ?)";
                $chk_already_params[] = (int)$_POST['group_id'];
                $chk_already_types .= "i";
            }
            $chk_already_res = app_exec_getresult($chk_already_sql, $chk_already_params, $chk_already_types);
            if ($chk_already_res && $chk_already_res->num_rows > 0) {
                $month_name = date('F Y', strtotime($_POST['from_date']));
                http_response_code(400);
                header('Content-Type: application/json');
                echo json_encode(array('Message' => "Attendance for $month_name has already been processed once and cannot be processed again."));
                exit;
            }

            try{
                $from_date = $_POST['from_date'];
                $to_date   = $_POST['to_date'];
                $group_id  = isset($_POST['group_id']) ? (int)$_POST['group_id'] : 0;
                $login_id  = !empty($_SESSION['login_id']) ? (int)$_SESSION['login_id'] : 1;

                date_default_timezone_set('Asia/Kolkata');
                $currentDate = date('Y-m-d');
                $month_label = date('F Y', strtotime($from_date));
                $row_description = "Monthly Fee - " . $month_label;

                // Active financial year & closing year ID
                $active_year_start = '2000-04-01';
                $selected_year = 1;
                $active_yr_res = app_exec_query("SELECT id, from_year FROM tbl_closing ORDER BY from_year DESC LIMIT 1");
                if ($active_yr_res && $row_yr = $active_yr_res->fetch_assoc()) {
                    $active_year_start = $row_yr['from_year'] . "-04-01";
                    $selected_year = (int)$row_yr['id'];
                }

                // Default monthly fee head with exact matching priority
                $head = 1;
                $head_res = app_exec_query("SELECT id FROM tbl_payment_head_master WHERE LOWER(name) = 'monthly fee' OR name LIKE '%monthly%' ORDER BY (LOWER(name) = 'monthly fee') DESC, id ASC LIMIT 1");
                if ($head_res && $row_head = $head_res->fetch_assoc()) {
                    $head = (int)$row_head['id'];
                }

                // Custom guest fees if passed
                $guest_fees = [];
                if (!empty($_POST['custom_guest_fees'])) {
                    $guest_fees = is_array($_POST['custom_guest_fees']) ? $_POST['custom_guest_fees'] : json_decode($_POST['custom_guest_fees'], true);
                    if (!is_array($guest_fees)) $guest_fees = [];
                }

                if ($group_id > 0) {
                    $sql = "SELECT DISTINCT a.member_id AS id, COUNT(DISTINCT a.date) AS totalattendance 
                            FROM tbl_attendance AS a
                            INNER JOIN tbl_members AS m ON a.member_id = m.id AND (m.member_type = 0 OR m.member_type IS NULL)
                            WHERE (a.date BETWEEN ? AND ?)
                              AND (a.group_id = ? OR a.member_id IN (SELECT member_id FROM tbl_group_member_map WHERE group_id = ?))
                            GROUP BY a.member_id";
                    $parameters = [$from_date, $to_date, $group_id, $group_id];
                    $types = "ssii";
                } else {
                    $sql = "SELECT DISTINCT a.member_id AS id, COUNT(DISTINCT a.date) AS totalattendance 
                            FROM tbl_attendance AS a
                            INNER JOIN tbl_members AS m ON a.member_id = m.id AND (m.member_type = 0 OR m.member_type IS NULL)
                            WHERE (a.date BETWEEN ? AND ?) 
                            GROUP BY a.member_id";
                    $parameters = [$from_date, $to_date];
                    $types = "ss";
                }

                $result = app_exec_getresult($sql, $parameters, $types);
                $processed_members = [];

                if ($result && mysqli_num_rows($result) > 0) {
                    while($row = mysqli_fetch_assoc($result)) {
                        $member_id = (int)$row["id"];
                        $totalattendance = (int)$row["totalattendance"];
                        $processed_members[] = $member_id;

                        // Determine fee amount using configured fee rule / method
                        $custom_fee = null;
                        if (isset($guest_fees[$member_id]) && is_numeric($guest_fees[$member_id])) {
                            $custom_fee = (float)$guest_fees[$member_id];
                        }

                        // Determine member group id if general (ordered deterministically)
                        $mem_group_id = $group_id;
                        if ($mem_group_id <= 0) {
                            $g_res = app_exec_getresult("SELECT group_id FROM tbl_group_member_map WHERE member_id = ? ORDER BY id ASC LIMIT 1", [$member_id], "i");
                            if ($g_res && $g_row = $g_res->fetch_assoc()) {
                                $mem_group_id = (int)$g_row['group_id'];
                            }
                        }

                        $fee_calc = computeMemberMonthlyFee($member_id, $mem_group_id, $from_date, $to_date, $totalattendance, $custom_fee, $active_year_start);
                        $receiveble = $fee_calc['fee'];

                        // Check if record already exists in monthly attendance for this member and period
                        $chk_sql = "SELECT id, isreceiveble FROM tbl_monthly_attendance WHERE member_id = ? AND from_date = ? AND to_date = ?";
                        $chk_res = app_exec_getresult($chk_sql, [$member_id, $from_date, $to_date], "iss");
                        
                        $is_already_receivable = 0;
                        $monthly_att_id = 0;

                        if ($chk_res && $chk_row = $chk_res->fetch_assoc()) {
                            $monthly_att_id = (int)$chk_row['id'];
                            $is_already_receivable = (int)$chk_row['isreceiveble'];
                            // Update existing record
                            $upd_sql = "UPDATE tbl_monthly_attendance SET attendance = ?, isreceiveble = 1 WHERE id = ?";
                            app_exec_nonquery($upd_sql, [$totalattendance, $monthly_att_id], "ii");
                        } else {
                            // Insert new record with isreceiveble = 1
                            $ins_sql = "INSERT INTO tbl_monthly_attendance (member_id, from_date, to_date, attendance, isreceiveble) VALUES (?, ?, ?, ?, 1)";
                            $monthly_att_id = app_exec_getlast_id($ins_sql, [$member_id, $from_date, $to_date, $totalattendance], "issi");
                        }

                        // Automatically create fee receivable if not already created and member attended
                        if ($totalattendance > 0 && $is_already_receivable === 0) {
                            // Check if receivable already exists in tbl_member_recievable for this member & description to prevent duplicate
                            $chk_rec_sql = "SELECT id FROM tbl_member_recievable WHERE member_id = ? AND head = ? AND discription = ? AND flag = ? LIMIT 1";
                            $chk_rec_res = app_exec_getresult($chk_rec_sql, [$member_id, $head, $row_description, $selected_year], "iisi");
                            
                            if (!$chk_rec_res || $chk_rec_res->num_rows === 0) {
                                $sql_rec = "INSERT INTO tbl_member_recievable (member_id, fees, date, login_id, head, discription, iscomplete, flag, group_id) VALUES (?, ?, ?, ?, ?, ?, 0, ?, ?)";
                                $parameters_rec = [
                                    $member_id,
                                    $receiveble,
                                    $currentDate,
                                    $login_id,
                                    $head,
                                    $row_description,
                                    $selected_year,
                                    $mem_group_id
                                ];
                                $types_rec = "idsiisii";
                                $recieveble_id = app_exec_getlast_id($sql_rec, $parameters_rec, $types_rec);

                                if ($recieveble_id !== null) {
                                    $sql_rev = "INSERT INTO tbl_member_recieved (member_id, fees, date, login_id, head, discription, receiveble_id, flag, group_id) VALUES (?, 0.00, ?, ?, ?, ?, ?, ?, ?)";
                                    $parameters_rev = [
                                        $member_id,
                                        $currentDate,
                                        $login_id,
                                        $head,
                                        $row_description,
                                        $recieveble_id,
                                        $selected_year,
                                        $mem_group_id
                                    ];
                                    $types_rev = "isiisiii";
                                    app_exec_nonquery($sql_rev, $parameters_rev, $types_rev);
                                }
                            }
                        }
                    }
                }

                // Update any members who previously had attendance records in this period but now have 0
                if (!empty($processed_members)) {
                    $in_clause = implode(',', $processed_members);
                    if ($group_id > 0) {
                        $zero_sql = "UPDATE tbl_monthly_attendance SET attendance = 0 
                                     WHERE from_date = ? AND to_date = ? AND member_id NOT IN ($in_clause)
                                       AND (member_id IN (SELECT member_id FROM tbl_group_member_map WHERE group_id = ?))";
                        app_exec_nonquery($zero_sql, [$from_date, $to_date, $group_id], "ssi");
                    } else {
                        $zero_sql = "UPDATE tbl_monthly_attendance SET attendance = 0 WHERE from_date = ? AND to_date = ? AND member_id NOT IN ($in_clause)";
                        app_exec_nonquery($zero_sql, [$from_date, $to_date], "ss");
                    }
                } else {
                    if ($group_id > 0) {
                        $zero_sql = "UPDATE tbl_monthly_attendance SET attendance = 0 
                                     WHERE from_date = ? AND to_date = ?
                                       AND (member_id IN (SELECT member_id FROM tbl_group_member_map WHERE group_id = ?))";
                        app_exec_nonquery($zero_sql, [$from_date, $to_date, $group_id], "ssi");
                    } else {
                        $zero_sql = "UPDATE tbl_monthly_attendance SET attendance = 0 WHERE from_date = ? AND to_date = ?";
                        app_exec_nonquery($zero_sql, [$from_date, $to_date], "ss");
                    }
                }

                echo json_encode(["status" => "success", "message" => "Monthly attendance processed and fee receivables generated successfully."]);
                exit;
            }
            catch (Exception $e) {
                http_response_code(500);   
                echo json_encode([
                    "Message" => "Oops! Something went wrong.",
                    "errmsg"  => $e->getMessage()
                ]);
                exit;
            }
        }
        // action to save mothly attendance end

        if ($action == "check_processed_status") {
            try {
                $month = $_POST['month'];
                $group_id = isset($_POST['group_id']) ? (int)$_POST['group_id'] : 0;
                
                $sql = "SELECT COUNT(id) AS cnt FROM tbl_monthly_attendance WHERE DATE_FORMAT(from_date, '%Y-%m') = ?";
                $params = [$month];
                $types = "s";
                if ($group_id > 0) {
                    $sql .= " AND member_id IN (SELECT member_id FROM tbl_group_member_map WHERE group_id = ?)";
                    $params[] = $group_id;
                    $types .= "i";
                }
                $res = app_exec_getresult($sql, $params, $types);
                $cnt = $res ? (int)($res->fetch_assoc()['cnt'] ?? 0) : 0;
                
                header('Content-Type: application/json');
                echo json_encode(["is_processed" => ($cnt > 0), "count" => $cnt]);
                exit();
            } catch (Exception $e) {
                header('Content-Type: application/json');
                echo json_encode(["is_processed" => false, "count" => 0]);
                exit();
            }
        }

        // action to undo / reset processed monthly attendance
        if ($action == "undo_monthly_attendance") {
            try {
                $from_date = $_POST['from_date'] ?? '';
                $to_date   = $_POST['to_date'] ?? '';
                $month     = $_POST['month'] ?? '';
                $group_id  = isset($_POST['group_id']) ? (int)$_POST['group_id'] : 0;

                if (empty($from_date) && !empty($month)) {
                    $from_date = $month . '-01';
                    $to_date   = date('Y-m-t', strtotime($from_date));
                }

                if (empty($from_date) || empty($to_date)) {
                    http_response_code(400);
                    echo json_encode(["Message" => "Please select a valid month to undo."]);
                    exit();
                }

                $month_label = date('F Y', strtotime($from_date));

                // 1. Find member IDs affected if group_id is specified
                $mem_filter_sql = "";
                $mem_params = [];
                $mem_types = "";
                if ($group_id > 0) {
                    $mem_filter_sql = " AND member_id IN (SELECT member_id FROM tbl_group_member_map WHERE group_id = ?)";
                    $mem_params = [$group_id];
                    $mem_types = "i";
                }

                // 2. Check if any paid records exist for this month
                $chk_paid_sql = "SELECT r.id, r.fees, r.member_id FROM tbl_member_recieved r 
                                 INNER JOIN tbl_member_recievable rec ON r.receiveble_id = rec.id 
                                 WHERE (rec.discription LIKE ? OR rec.discription LIKE ?) AND r.fees > 0" . ($group_id > 0 ? " AND rec.member_id IN (SELECT member_id FROM tbl_group_member_map WHERE group_id = ?)" : "");
                $chk_paid_params = ["%" . $month_label . "%", "%" . date('Y-m', strtotime($from_date)) . "%"];
                $chk_paid_types = "ss";
                if ($group_id > 0) {
                    $chk_paid_params[] = $group_id;
                    $chk_paid_types .= "i";
                }
                $chk_paid_res = app_exec_getresult($chk_paid_sql, $chk_paid_params, $chk_paid_types);
                if ($chk_paid_res && $chk_paid_res->num_rows > 0) {
                    http_response_code(400);
                    echo json_encode(["Message" => "Cannot undo processing for $month_label because some members have already made payments towards their fees. Please reverse the payments first."]);
                    exit();
                }

                // 3. Find matching receivables to delete
                $find_rec_sql = "SELECT id FROM tbl_member_recievable WHERE (discription LIKE ? OR discription LIKE ?) AND iscomplete = 0" . $mem_filter_sql;
                $find_params = array_merge(["%" . $month_label . "%", "%" . date('Y-m', strtotime($from_date)) . "%"], $mem_params);
                $find_types = "ss" . $mem_types;
                $rec_res = app_exec_getresult($find_rec_sql, $find_params, $find_types);

                $del_recv_ids = [];
                if ($rec_res && $rec_res->num_rows > 0) {
                    while ($r = $rec_res->fetch_assoc()) {
                        $del_recv_ids[] = (int)$r['id'];
                    }
                }

                if (!empty($del_recv_ids)) {
                    $ids_str = implode(',', array_map('intval', $del_recv_ids));
                    // Delete placeholder received records
                    app_exec_query("DELETE FROM tbl_member_recieved WHERE receiveble_id IN ($ids_str) AND fees = 0");
                    // Delete receivables
                    app_exec_query("DELETE FROM tbl_member_recievable WHERE id IN ($ids_str)");
                }

                // 4. Delete monthly attendance records
                $del_att_sql = "DELETE FROM tbl_monthly_attendance WHERE from_date = ? AND to_date = ?" . $mem_filter_sql;
                $del_att_params = array_merge([$from_date, $to_date], $mem_params);
                $del_att_types = "ss" . $mem_types;
                app_exec_nonquery($del_att_sql, $del_att_params, $del_att_types);

                echo json_encode(["status" => "success", "message" => "Monthly attendance and fee receivables for $month_label have been reset successfully."]);
                exit();
            } catch (Exception $e) {
                http_response_code(500);
                echo json_encode(["Message" => "Error: " . $e->getMessage()]);
                exit();
            }
        }

        // action to load pending months to process attendance start
        if ($action == "load_pending_months") {
            try {
                $login_id = !empty($_SESSION['login_id']) ? (int)$_SESSION['login_id'] : 0;
                $allowed = getUserAllowedGroupIds($login_id);

                $active_year_start = '2000-04-01';
                $active_yr_res = app_exec_query("SELECT from_year FROM tbl_closing ORDER BY from_year DESC LIMIT 1");
                if ($active_yr_res && $row_yr = $active_yr_res->fetch_assoc()) {
                    $active_year_start = $row_yr['from_year'] . "-04-01";
                }

                $group_id = isset($_POST['group_id']) ? (int)$_POST['group_id'] : 0;

                $where_clauses = ["a.date >= ?", "a.member_id IN (SELECT id FROM tbl_members WHERE member_type = 0 OR member_type IS NULL)"];
                $params = [$active_year_start];
                $types = "s";

                if ($group_id > 0) {
                    if (in_array('ALL', $allowed, true) || hasGroupAccess($login_id, $group_id)) {
                        $where_clauses[] = "(gmm.group_id = ? OR a.group_id = ?)";
                        $params[] = $group_id;
                        $params[] = $group_id;
                        $types .= "ii";
                    } else {
                        echo json_encode([]);
                        exit();
                    }
                } else if (!in_array('ALL', $allowed, true)) {
                    if (!empty($allowed)) {
                        $placeholders = implode(',', array_fill(0, count($allowed), '?'));
                        $where_clauses[] = "(gmm.group_id IN ($placeholders) OR a.group_id IN ($placeholders))";
                        foreach ($allowed as $gid) { $params[] = (int)$gid; }
                        foreach ($allowed as $gid) { $params[] = (int)$gid; }
                        $types .= str_repeat('i', count($allowed) * 2);
                    } else {
                        echo json_encode([]);
                        exit();
                    }
                }

                $where_sql = implode(" AND ", $where_clauses);

                $sql = "SELECT 
                            DATE_FORMAT(a.date, '%Y-%m') AS month_val,
                            DATE_FORMAT(a.date, '%M %Y') AS month_label,
                            MIN(a.date) AS min_date,
                            MAX(a.date) AS max_date,
                            COALESCE(g.id, 0) AS group_id,
                            COALESCE(g.name, 'General') AS group_name,
                            COUNT(DISTINCT a.member_id) AS total_members,
                            COUNT(DISTINCT a.date) AS attendance_days
                        FROM tbl_attendance AS a
                        LEFT JOIN tbl_group_member_map AS gmm ON a.member_id = gmm.member_id
                        LEFT JOIN tbl_groups AS g ON gmm.group_id = g.id
                        WHERE $where_sql
                          AND NOT EXISTS (
                              SELECT 1 FROM tbl_monthly_attendance AS ma 
                              WHERE ma.member_id = a.member_id 
                                AND DATE_FORMAT(ma.from_date, '%Y-%m') = DATE_FORMAT(a.date, '%Y-%m')
                          )
                        GROUP BY DATE_FORMAT(a.date, '%Y-%m'), g.id
                        ORDER BY month_val DESC, group_name ASC";

                $res = app_exec_getresult($sql, $params, $types);
                $data = [];
                if ($res && $res->num_rows > 0) {
                    while ($row = $res->fetch_assoc()) {
                        $row['is_fixed'] = isMonthFixed($row['month_val']);
                        $row['is_month_ended'] = isMonthEnded($row['month_val']);
                        $data[] = $row;
                    }
                }
                echo json_encode($data);
                exit();
            } catch (Exception $e) {
                http_response_code(500);
                echo json_encode(["Message" => "Error: " . $e->getMessage()]);
                exit();
            }
        }
        // action to load pending months to process attendance end

        // action to load groups start
        if($action=="load_groups"){
            try{
                $sql = "SELECT id, name FROM tbl_groups WHERE status = 1 ORDER BY name ASC";
                $result = app_exec_query($sql);

                $qrydata = [];
                if ($result && $result->num_rows > 0) {
                    while ($row = $result->fetch_assoc()) {
                        $qrydata[] = $row;
                    }
                }
                echo json_encode(array($qrydata));
                exit();
            }
            catch (Throwable $e) {
                throw new Exception('Oops! Something went wrong.');
            }
        }
        // action to load groups end
        if($action=="load_heads"){
            try{
                $sql = "SELECT id, name FROM tbl_payment_head_master WHERE name LIKE '%monthly%'";
                $result = app_exec_query($sql);

                if ($result && $result->num_rows > 0) {
                    // Fetch all rows as an array
                    $qrydata = [];
                    while ($row = $result->fetch_assoc()) {
                        $qrydata[] = $row; // Add each row to the array
                    }
                } else {
                    $qrydata = []; // If no data is found
                }
        
                // $pagination = array("total_rows"=>$totalRows);
                $resdata=array($qrydata);
        
                echo json_encode($resdata);
                exit();
                
            }
            catch (Throwable $e) {
                throw new Exception('Oops! Something went wrong.');
            }
        }

        if($action=="save_all_receivables"){
            try{
                $db = new Database();
                $conn = $db->getConnection();
                $tm = new TransactionManager($conn);

                $head = $_POST['head'];
                $description = $_POST['discription'];
                $selected_year = $_POST['selected_year'];
                $group_id = isset($_POST['group_id']) ? (int)$_POST['group_id'] : 0;

                // 1. Fetch active year start
                $active_year_start = '2000-04-01';
                $active_yr_res = app_exec_query("SELECT from_year FROM tbl_closing ORDER BY from_year DESC LIMIT 1");
                if ($active_yr_res && $row_yr = $active_yr_res->fetch_assoc()) {
                    $active_year_start = $row_yr['from_year'] . "-04-01";
                }

                // 2. Fetch pending monthly attendance records (where isreceiveble = 0 and attendance > 0)
                if ($group_id > 0) {
                    $sql_pending = "SELECT DISTINCT ma.id, ma.member_id, ma.attendance, ma.from_date, ma.to_date 
                                    FROM tbl_monthly_attendance ma
                                    INNER JOIN tbl_group_member_map gmm ON ma.member_id = gmm.member_id
                                    WHERE ma.isreceiveble = 0 
                                      AND ma.attendance > 0 
                                      AND ma.from_date >= ?
                                      AND gmm.group_id = ?";
                    $res_pending = app_exec_getresult($sql_pending, [$active_year_start, $group_id], "si");
                } else {
                    $sql_pending = "SELECT id, member_id, attendance, from_date, to_date 
                                    FROM tbl_monthly_attendance 
                                    WHERE isreceiveble = 0 
                                      AND attendance > 0 
                                      AND from_date >= ?";
                    $res_pending = app_exec_getresult($sql_pending, [$active_year_start], "s");
                }

                if ($res_pending && $res_pending->num_rows > 0) {
                    $tm->begin();
                    date_default_timezone_set('Asia/Kolkata');
                    $currentDate = date('Y-m-d');

                    while ($row = $res_pending->fetch_assoc()) {
                        $id = $row['id'];
                        $member_id = $row['member_id'];
                        $attendance = $row['attendance'];
                        $from_date = $row['from_date'];
                        $to_date = $row['to_date'];

                        // Check if custom guest fee is provided for this member
                        $custom_fee = null;
                        if (!empty($_POST['custom_guest_fees'])) {
                            $guest_fees = is_array($_POST['custom_guest_fees']) ? $_POST['custom_guest_fees'] : json_decode($_POST['custom_guest_fees'], true);
                            if (isset($guest_fees[$member_id]) && is_numeric($guest_fees[$member_id])) {
                                $custom_fee = (float)$guest_fees[$member_id];
                            }
                        }

                        // Calculate fees using configured fee rule / method
                        $rec_group_id = $group_id;
                        if ($rec_group_id <= 0) {
                            $g_res = app_exec_getresult("SELECT group_id FROM tbl_group_member_map WHERE member_id = ? ORDER BY id ASC LIMIT 1", [$member_id], "i");
                            if ($g_res && $g_row = $g_res->fetch_assoc()) {
                                $rec_group_id = (int)$g_row['group_id'];
                            }
                        }

                        $fee_calc = computeMemberMonthlyFee($member_id, $rec_group_id, $from_date, $to_date, $attendance, $custom_fee, $active_year_start);
                        $receiveble = (float)$fee_calc['fee'];

                        // Determine formatted description (e.g. "Monthly Fee August 2026")
                        $month_label = date('F Y', strtotime($from_date));
                        $row_description = trim($description ?? '');
                        if (empty($row_description) || preg_match('/\btest\b/i', $row_description) || $row_description === 'Monthly Fee') {
                            $row_description = "Monthly Fee - " . $month_label;
                        } else if (stripos($row_description, $month_label) === false && stripos($row_description, date('M Y', strtotime($from_date))) === false) {
                            $row_description = $row_description . " - " . $month_label;
                        }

                        // Insert into tbl_member_recievable with today's processing date
                        $sql_rec = "INSERT INTO tbl_member_recievable (member_id, fees, date, login_id, head, discription, iscomplete, flag, group_id) VALUES (?, ?, ?, ?, ?, ?, 0, ?, ?)";
                        $parameters_rec = [
                            $member_id,
                            $receiveble,
                            $currentDate,
                            $_SESSION['login_id'],
                            $head,
                            $row_description,
                            $selected_year,
                            $rec_group_id
                        ];
                        $types_rec = "idsiisii";

                        $recieveble_id = app_exec_getlast_id($sql_rec, $parameters_rec, $types_rec);

                        if ($recieveble_id !== null) {
                            // Insert into tbl_member_recieved
                            $sql_rev = "INSERT INTO tbl_member_recieved (member_id, fees, date, login_id, head, discription, receiveble_id, flag, group_id) VALUES (?, 0.00, ?, ?, ?, ?, ?, ?, ?)";
                            $parameters_rev = [
                                $member_id,
                                $currentDate,
                                $_SESSION['login_id'],
                                $head,
                                $row_description,
                                $recieveble_id,
                                $selected_year,
                                $rec_group_id
                            ];
                            $types_rev = "isiisiii";
                            app_exec_nonquery($sql_rev, $parameters_rev, $types_rev);

                            // Update isreceiveble in tbl_monthly_attendance
                            $sql_upd = "UPDATE tbl_monthly_attendance SET isreceiveble = 1 WHERE id = ?";
                            app_exec_nonquery($sql_upd, [$id], "i");
                        }
                    }
                    $tm->commit();
                    echo json_encode(["status" => "success", "message" => "Pending receivables created successfully."]);
                    exit;
                } else {
                    echo json_encode(["status" => "success", "message" => "No pending records found for the selected batch."]);
                    exit;
                }
            } catch (Exception $e) {
                if (isset($tm)) {
                    $tm->rollback();
                }
                http_response_code(500);
                echo "Oops! Something went wrong: " . $e->getMessage();
                exit;
            }
        }

        if($action=="save_recieveble"){ 
            try{
                $id = (int)($_POST['id'] ?? 0);
                $res_chk = app_exec_getresult("SELECT from_date, to_date FROM tbl_monthly_attendance WHERE id = ?", [$id], "i");
                $from_date = '';
                $to_date = '';
                if ($res_chk && $row_chk = $res_chk->fetch_assoc()) {
                    $from_date = $row_chk['from_date'];
                    $to_date = !empty($row_chk['to_date']) ? $row_chk['to_date'] : date('Y-m-t', strtotime($from_date));
                }
                
                $active_year_start = '2000-04-01';
                $active_yr_res = app_exec_query("SELECT from_year FROM tbl_closing ORDER BY from_year DESC LIMIT 1");
                if ($active_yr_res && $row_yr = $active_yr_res->fetch_assoc()) {
                    $active_year_start = $row_yr['from_year'] . "-04-01";
                }

                $attendance = (int)($_POST['attendance'] ?? 0);
                $mem_id = (int)$_POST['member_id'];
                $gid_chk = app_exec_getresult("SELECT group_id FROM tbl_group_member_map WHERE member_id = ? ORDER BY id ASC LIMIT 1", [$mem_id], "i");
                $mem_group_id = ($gid_chk && $grow = $gid_chk->fetch_assoc()) ? (int)$grow['group_id'] : 0;
                
                $fee_calc = computeMemberMonthlyFee($mem_id, $mem_group_id, $from_date, $to_date, $attendance, null, $active_year_start);
                $receiveble = (float)$fee_calc['fee'];

                date_default_timezone_set('Asia/Kolkata');
                $currentDate = date('Y-m-d');
                $rec_date = !empty($_POST['date']) ? trim($_POST['date']) : $currentDate;

                $month_label = !empty($from_date) ? date('F Y', strtotime($from_date)) : date('F Y');
                $row_description = trim($_POST['discription'] ?? '');
                if (empty($row_description) || preg_match('/\btest\b/i', $row_description) || $row_description === 'Monthly Fee') {
                    $row_description = "Monthly Fee - " . $month_label;
                } else if (stripos($row_description, $month_label) === false && stripos($row_description, date('M Y', strtotime($from_date))) === false) {
                    $row_description = $row_description . " - " . $month_label;
                }
            
                $tm->begin();

                $sql = "INSERT INTO tbl_member_recievable (member_id, fees, date, login_id, head, discription, iscomplete, flag, group_id) VALUES (?, ?, ?, ?, ?, ?, 0, ?, ?)";
                
                $parameters = [
                    $mem_id,
                    $receiveble,
                    $rec_date,
                    $_SESSION['login_id'],
                    $_POST['head'],
                    $row_description,
                    $_POST['selected_year'],
                    $mem_group_id
                ];
                $types="idsiisii";
                    
                $recieveble_id = app_exec_getlast_id_roll_back($conn, $sql, $parameters, $types);
                if ($recieveble_id === null) {
                    throw new Exception("Error creating fee receivable");
                }

                $received_date = !empty($_POST['received_date']) 
                    ? $_POST['received_date'] 
                    : $rec_date;

                $sql_rev = "INSERT INTO tbl_member_recieved (member_id, fees, date, login_id, head, discription, receiveble_id, flag, group_id) VALUES (?, 0.00, ?, ?, ?, ?, ?, ?, ?)";
                $parameters_rev = [
                    $mem_id,
                    $received_date,
                    $_SESSION['login_id'],
                    $_POST['head'],
                    $row_description,
                    $recieveble_id,
                    $_POST['selected_year'],
                    $mem_group_id
                ];
                $types_rev="isiisiii";

                if (!app_exec_roll_back_nonquery($conn, $sql_rev, $parameters_rev, $types_rev)) {
                    throw new Exception("Error creating received placeholder record");
                }

                $sql_upd = "UPDATE tbl_monthly_attendance SET isreceiveble = 1 WHERE id = ?";
                if (!app_exec_roll_back_nonquery($conn, $sql_upd, [$id], "i")) {
                    throw new Exception("Error updating monthly attendance status");
                }
                
                $tm->commit();
                echo json_encode(["status" => "success", "message" => "Fee receivable created successfully."]);
                exit;
            }
             catch (Exception $e) {
                $tm->rollback();
                http_response_code(500);   
                echo json_encode(["status" => "error", "Message" => "Oops! Something went wrong: " . $e->getMessage()]);
                exit;
            }
    
        }

        // action to load monthly attendance details start
        if($action=="load_data"){
            try{
                $no_limit = isset($_POST['no_limit']) && ($_POST['no_limit'] == 1 || $_POST['no_limit'] === 'true');
                $rowsPerPage = isset($_POST['rows_per_page']) && (int)$_POST['rows_per_page'] > 0 ? (int)$_POST['rows_per_page'] : 8;
                $current_page = max(1, isset($_POST['page']) && (int)$_POST['page'] > 0 ? (int)$_POST['page'] : 1);
                $offset = ($current_page - 1) * $rowsPerPage;

                // Pagination logic
                $sql="";  
                $sqlcountrows="";
                $sqldatarows="";   
                
                $group_id = isset($_POST['group_id']) ? (int)$_POST['group_id'] : 0;
                $status   = isset($_POST['status']) ? trim($_POST['status']) : 'all';

                $where_cond = " WHERE ma.attendance > 0 
                                  AND (m.member_type = 0 OR m.member_type IS NULL)
                                  AND ma.from_date >= (SELECT CONCAT(from_year, '-04-01') FROM tbl_closing ORDER BY from_year DESC LIMIT 1)";
                $params = [];
                $types = "";

                if (!empty($_POST['from_date'])) {
                    $where_cond .= " AND ma.from_date = ?";
                    $params[] = $_POST['from_date'];
                    $types .= "s";
                } elseif (!empty($_POST['month'])) {
                    $where_cond .= " AND DATE_FORMAT(ma.from_date, '%Y-%m') = ?";
                    $params[] = $_POST['month'];
                    $types .= "s";
                }

                if ($group_id > 0) {
                    $where_cond .= " AND ma.member_id IN (SELECT member_id FROM tbl_group_member_map WHERE group_id = ?)";
                    $params[] = $group_id;
                    $types .= "i";
                }

                if ($status === 'pending') {
                    $where_cond .= " AND ma.isreceiveble = 0";
                } elseif ($status === 'created') {
                    $where_cond .= " AND ma.isreceiveble = 1";
                }

                $sqldatarows = "SELECT ma.id, ma.from_date, ma.to_date, ma.member_id, ma.attendance, ma.isreceiveble, 
                                       m.first_name, m.middle_name, m.last_name, m.member_type, 
                                       GROUP_CONCAT(DISTINCT g.name SEPARATOR ', ') AS group_name 
                                FROM tbl_monthly_attendance AS ma 
                                LEFT JOIN tbl_members as m ON ma.member_id=m.id 
                                LEFT JOIN tbl_group_member_map AS gmm ON ma.member_id = gmm.member_id 
                                LEFT JOIN tbl_groups AS g ON gmm.group_id = g.id 
                                $where_cond 
                                GROUP BY ma.id 
                                ORDER BY ma.from_date DESC, ma.attendance DESC, m.first_name, m.middle_name, m.last_name";

                $sqlcountrows = "SELECT COUNT(DISTINCT ma.id) AS total 
                                 FROM tbl_monthly_attendance AS ma 
                                 INNER JOIN tbl_members AS m ON ma.member_id = m.id
                                 $where_cond";

                if (!$no_limit) {
                    $sqldatarows .= " LIMIT $offset , $rowsPerPage ";
                }

                if (!empty($params)) {
                    $result = app_exec_getresult($sqldatarows, $params, $types);
                    $totalRowsResult = app_exec_getresult($sqlcountrows, $params, $types);
                } else {
                    $result = app_exec_query($sqldatarows);
                    $totalRowsResult = app_exec_query($sqlcountrows);
                }

                $totalRows = $totalRowsResult->fetch_assoc()['total'];
                
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
        
                $pagination = array("total_rows"=>$totalRows);
                $resdata=array($pagination,$qrydata);
        
                echo json_encode($resdata);
                exit();
            }
            // catch (Throwable $e) {
            //     throw new Exception('Oops! Something went wrong.');
            // }
            catch (Exception $e) {
                http_response_code(500);   
                echo "Oops! Something went wrong." . $e->getMessage();
                return;
            }
        }
        // action to load monthly attendance details  end

        // action to delete monthly attendance and receiveble start
        if($action=="delete_payment"){
            checkMonthFixed($_POST['from_date']);
            // $amount=$_POST['amount'];
            date_default_timezone_set('Asia/Kolkata');
            $currentDate = date('Y-m-d');
            try{
                $tm->begin();

                $sql = "DELETE FROM tbl_monthly_attendance WHERE id = ?";
                $types="i";
                $parameters = [
                    $_POST['id'],
                ];
                
                app_exec_roll_back_nonquery($conn,$sql, $parameters, $types);

                $sql = "DELETE FROM tbl_member_recievable WHERE from_date = ? AND to_date=? AND member_id = ?";
               
                $parameters = [
                    $_POST['from_date'],
                    $_POST['to_date'],
                    $_POST['member_id'],
                ];
                $types="ssi";
                app_exec_roll_back_nonquery($conn,$sql, $parameters, $types);

                $sql = "SELECT SUM(rd.fees) as total_recieved_amount,r.id as receiveble_id FROM tbl_member_recieved as rd LEFT JOIN tbl_member_recievable as r ON rd.receiveble_id=r.id 
                WHERE r.from_date = ? AND r.to_date= ? AND r.member_id = ?";
                $parameters = [
                    $_POST['from_date'],
                    $_POST['to_date'],
                    $_POST['member_id'],
                ];
                 $types="ssi";
                
                $result = app_exec_getresult($sql, $parameters, $types);

                if (mysqli_num_rows($result) > 0) {
                    // output data of each row
                    while($row = mysqli_fetch_assoc($result)) {
                        
                        $total_recieved_amount = $row["total_recieved_amount"];
                        $receiveble_id =  $row["receiveble_id"];
                        
                        $sql = "DELETE FROM tbl_member_recieved WHERE receiveble_id = ?";
                       
                        $parameters = [
                            $receiveble_id
                        ];
                        $types="i";
                        
                        if (!app_exec_roll_back_nonquery($conn, $sql, $parameters, $types)) {
                            throw new Exception("Error in Updation");
                        }

                        if ($total_recieved_amount > 0) {
                            $sql = "INSERT INTO tbl_wallet (client_id, date, amount, type) 
                                    VALUES (?, ?, ?,'credit')";

                            $parameters = [
                                $_POST['member_id'],
                                $currentDate,   
                                (float)$total_recieved_amount,
                            ];
                            $types = "isd"; // int, string, double (decimal)

                            if (!app_exec_roll_back_nonquery($conn, $sql, $parameters, $types)) {
                                throw new Exception("Error inserting into wallet");
                            }
                        }
                    }
                }
                $tm->commit();
                echo 'Deleted Sucessfully';
            }
            catch (Exception $e) {
                $tm->rollback();
                echo $e->getMessage();
            }
        }
        // action to delete monthly attendance and receiveble end

        if($action=="load_closing_years"){
            try{
                $sql = "SELECT id, from_year, to_year FROM tbl_closing ORDER BY from_year DESC LIMIT 1";
                $result = app_exec_query($sql);

                if ($result && $result->num_rows > 0) {
                    // Fetch all rows as an array
                    $qrydata = [];
                    while ($row = $result->fetch_assoc()) {
                        $qrydata[] = $row; // Add each row to the array
                    }
                } else {
                    $qrydata = []; // If no data is found
                }
        
                // $pagination = array("total_rows"=>$totalRows);
                $resdata=array($qrydata);
        
                echo json_encode($resdata);
                exit();
                
            }
            catch (Throwable $e) {
                throw new Exception('Oops! Something went wrong.');
            }
        }
    }
?>