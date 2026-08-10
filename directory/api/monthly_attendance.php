<?php
    session_start();
    include '../../app_common/db_connect.php';
    include_once __DIR__ . '/attendance_fix_helper.php';
    include '../../app_pagination/pagination.php';

    // actions start
    if(isset($_POST['action']) && !empty($_POST['action'])) {
        $action = $_POST['action'];

        // action to save monthly attendance start
        if($action=="save_attendance"){ 
            requireMonthFixed($_POST['from_date']);
            
            // Limit attendance compiled to a single calendar month at a time
            $from_yr_mo = date('Y-m', strtotime($_POST['from_date']));
            $to_yr_mo   = date('Y-m', strtotime($_POST['to_date']));
            if ($from_yr_mo !== $to_yr_mo) {
                http_response_code(400);
                header('Content-Type: application/json');
                echo json_encode(array('Message' => "You can only compile attendance for one calendar month at a time."));
                exit;
            }

            // Block processing attendance before the month has ended
            $compile_month_end = date('Y-m-t', strtotime($_POST['from_date']));
            $current_date = date('Y-m-d');
            if ($current_date <= $compile_month_end) {
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
                        $member_id = $row["id"];
                        $totalattendance = $row["totalattendance"];
                        $processed_members[] = $member_id;

                        // Check if record already exists in monthly attendance for this member and period
                        $chk_sql = "SELECT id FROM tbl_monthly_attendance WHERE member_id = ? AND from_date = ? AND to_date = ?";
                        $chk_res = app_exec_getresult($chk_sql, [$member_id, $from_date, $to_date], "iss");
                        
                        if ($chk_res && $chk_row = $chk_res->fetch_assoc()) {
                            // Update existing record
                            $upd_sql = "UPDATE tbl_monthly_attendance SET attendance = ? WHERE id = ?";
                            app_exec_nonquery($upd_sql, [$totalattendance, $chk_row['id']], "ii");
                        } else {
                            // Insert new record
                            $ins_sql = "INSERT INTO tbl_monthly_attendance (member_id, from_date, to_date, attendance, isreceiveble) VALUES (?, ?, ?, ?, 0)";
                            app_exec_nonquery($ins_sql, [$member_id, $from_date, $to_date, $totalattendance], "issi");
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

                echo json_encode(["status" => "success", "message" => "Monthly attendance saved successfully."]);
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

        // action to load pending months to process attendance start
        if ($action == "load_pending_months") {
            try {
                $active_year_start = '2000-04-01';
                $active_yr_res = app_exec_query("SELECT from_year FROM tbl_closing ORDER BY from_year DESC LIMIT 1");
                if ($active_yr_res && $row_yr = $active_yr_res->fetch_assoc()) {
                    $active_year_start = $row_yr['from_year'] . "-04-01";
                }

                $sql = "SELECT 
                            DATE_FORMAT(a.date, '%Y-%m') AS month_val,
                            DATE_FORMAT(a.date, '%M %Y') AS month_label,
                            MIN(a.date) AS min_date,
                            MAX(a.date) AS max_date,
                            COALESCE(g.id, 0) AS group_id,
                            COALESCE(g.name, 'General / All Members') AS group_name,
                            COUNT(DISTINCT a.member_id) AS total_members,
                            COUNT(DISTINCT a.date) AS attendance_days
                        FROM tbl_attendance AS a
                        LEFT JOIN tbl_group_member_map AS gmm ON a.member_id = gmm.member_id
                        LEFT JOIN tbl_groups AS g ON gmm.group_id = g.id
                        WHERE a.date >= ?
                          AND a.member_id IN (SELECT id FROM tbl_members WHERE member_type = 0 OR member_type IS NULL)
                          AND NOT EXISTS (
                              SELECT 1 FROM tbl_monthly_attendance AS ma 
                              WHERE ma.member_id = a.member_id 
                                AND DATE_FORMAT(ma.from_date, '%Y-%m') = DATE_FORMAT(a.date, '%Y-%m')
                          )
                        GROUP BY DATE_FORMAT(a.date, '%Y-%m'), g.id
                        ORDER BY month_val DESC, group_name ASC";

                $res = app_exec_getresult($sql, [$active_year_start], "s");
                $data = [];
                if ($res && $res->num_rows > 0) {
                    while ($row = $res->fetch_assoc()) {
                        $row['is_fixed'] = isMonthFixed($row['month_val']);
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

                        // Calculate fees
                        if ($custom_fee !== null && $custom_fee >= 0) {
                            $receiveble = $custom_fee;
                        } elseif ($from_date >= $active_year_start) {
                            $receiveble = 300;
                        } else {
                            if ($attendance <= 5) {
                                $receiveble = 150;
                            } else {
                                $receiveble = 300;
                            }
                        }

                        // Insert into tbl_member_recievable
                        $sql_rec = "INSERT INTO tbl_member_recievable (member_id, fees, date, login_id, head, discription, iscomplete, flag) VALUES (?, ?, ?, ?, ?, ?, 0, ?)";
                        $parameters_rec = [
                            $member_id,
                            $receiveble,
                            $to_date,
                            $_SESSION['login_id'],
                            $head,
                            $description,
                            $selected_year
                        ];
                        $types_rec = "iisiisi";

                        $recieveble_id = app_exec_getlast_id($sql_rec, $parameters_rec, $types_rec);

                        if ($recieveble_id !== null) {
                            // Insert into tbl_member_recieved
                            $sql_rev = "INSERT INTO tbl_member_recieved (member_id, fees, date, login_id, head, discription, receiveble_id, flag) VALUES (?, 0, '0000-00-00', ?, ?, ?, ?, ?)";
                            $parameters_rev = [
                                $member_id,
                                $_SESSION['login_id'],
                                $head,
                                $description,
                                $recieveble_id,
                                $selected_year
                            ];
                            $types_rev = "iiisii";
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
                $id = $_POST['id'];
                $res_chk = app_exec_query("SELECT from_date FROM tbl_monthly_attendance WHERE id = $id");
                $from_date = '';
                if ($res_chk && $row_chk = $res_chk->fetch_assoc()) {
                    $from_date = $row_chk['from_date'];
                }
                
                $active_year_start = '2000-04-01';
                $active_yr_res = app_exec_query("SELECT from_year FROM tbl_closing ORDER BY from_year DESC LIMIT 1");
                if ($active_yr_res && $row_yr = $active_yr_res->fetch_assoc()) {
                    $active_year_start = $row_yr['from_year'] . "-04-01";
                }

                $attendance = $_POST['attendance'];
                if ($from_date >= $active_year_start) {
                    $receiveble = 300;
                } else {
                    if ($attendance <= 5) {
                         $receiveble = 150;
                    } else {
                        $receiveble = 300;
                    }
                }

                date_default_timezone_set('Asia/Kolkata');
                $currentDate = date('Y-m-d');
            
                $sql = "INSERT INTO tbl_member_recievable (member_id, fees, date, login_id, head, discription, iscomplete, flag) VALUES (?, ?, ?, ?, ?, ?, 0,?)";
                
                $parameters = [
                    $_POST['member_id'],
                    $receiveble,
                    $_POST['date'],
                    $_SESSION['login_id'],
                    $_POST['head'],
                    $_POST['discription'],
                    $_POST['selected_year'],
                    
                ];
                $types="iisiisi";
                    
                // Use custom insert function that returns insert ID
                $recieveble_id = app_exec_getlast_id($sql, $parameters, $types);
                    $received_date = !empty($_POST['received_date']) 
                    ? $_POST['received_date'] 
                    : '0000-00-00';
                if ($recieveble_id !== null) {
                    $sql = "INSERT INTO tbl_member_recieved (member_id, fees, date, login_id, head, discription, receiveble_id, flag) VALUES (?, 0, ?, ?, ?, ?, ?, ?)";
                    $parameters = [
                        $_POST['member_id'],
                        $received_date,
                        $_SESSION['login_id'],
                        $_POST['head'],
                        $_POST['discription'],
                        $recieveble_id,
                        $_POST['selected_year'],
                    ];
                    $types="isiisii";

                    // Saving Query as  text file end
                    app_exec_nonquery($sql, $parameters, $types);

                    $sql = "UPDATE tbl_monthly_attendance SET isreceiveble = 1 WHERE id= ?";
                    $parameters = [
                        $_POST['id'],
                       
                    ];
                    $types="i";

                    // Saving Query as  text file end
                    app_exec_nonquery($sql, $parameters, $types);
                }
                
            }
             catch (Exception $e) {
                http_response_code(500);   
                echo "Oops! Something went wrong." . $e->getMessage();
                return;
            }
    
        }

        // action to load monthly attendance details start
        if($action=="load_data"){
            try{
                $rowsPerPage = 8;
                $current_page = (int)$_POST['page'];
                $offset = ($current_page - 1) * $rowsPerPage;

                // Get the distinct periods for the current page to only sync those (highly optimized)
                $periods_res = app_exec_getresult(
                    "SELECT DISTINCT from_date, to_date FROM (
                        SELECT from_date, to_date FROM tbl_monthly_attendance 
                        ORDER BY from_date DESC, id
                        LIMIT ?, ?
                    ) AS tmp_p",
                    [$offset, $rowsPerPage],
                    "ii"
                );

                if ($periods_res && $periods_res->num_rows > 0) {
                    while ($period = $periods_res->fetch_assoc()) {
                        $f_date = $period['from_date'];
                        $t_date = $period['to_date'];
                        
                        // Skip if month is fixed/locked
                        if (isMonthFixed($f_date)) {
                            continue;
                        }
                        
                        // 1. Bulk insert any missing members for this period (highly optimized)
                        $ins_sql = "INSERT INTO tbl_monthly_attendance (member_id, from_date, to_date, attendance, isreceiveble)
                                    SELECT a.member_id, ? AS from_date, ? AS to_date, COUNT(DISTINCT a.date) AS attendance, 0 AS isreceiveble
                                    FROM tbl_attendance AS a
                                    WHERE a.date BETWEEN ? AND ?
                                      AND a.member_id NOT IN (
                                          SELECT tmp.member_id FROM (
                                              SELECT member_id FROM tbl_monthly_attendance WHERE from_date = ? AND to_date = ?
                                          ) AS tmp
                                      )
                                    GROUP BY a.member_id";
                        app_exec_nonquery($ins_sql, [$f_date, $t_date, $f_date, $t_date, $f_date, $t_date], "ssssss");
                        
                        // 2. Bulk update attendance counts for all existing members in this period (highly optimized)
                        $upd_sql = "UPDATE tbl_monthly_attendance AS ma
                                    SET ma.attendance = (
                                        SELECT COUNT(DISTINCT a.date)
                                        FROM tbl_attendance AS a 
                                        WHERE a.member_id = ma.member_id 
                                          AND a.date BETWEEN ma.from_date AND ma.to_date
                                    )
                                    WHERE ma.from_date = ? AND ma.to_date = ?";
                        app_exec_nonquery($upd_sql, [$f_date, $t_date], "ss");
                    }
                }

                // Pagination logic
                $sql="";  
                $sqlcountrows="";
                $sqldatarows="";   
        
                $offset = ($current_page - 1) * $rowsPerPage;
                
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
                                ORDER BY ma.from_date DESC, m.first_name, m.middle_name, m.last_name";

                $sqlcountrows = "SELECT COUNT(DISTINCT ma.id) AS total 
                                 FROM tbl_monthly_attendance AS ma 
                                 INNER JOIN tbl_members AS m ON ma.member_id = m.id
                                 $where_cond";

                $sqldatarows .= " LIMIT $offset , $rowsPerPage ";

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
                                $total_recieved_amount,
                            ];
                            $types = "isi"; // int, int, double (decimal)

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