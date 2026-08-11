<?php
    session_start();
    include '../../app_common/db_connect.php';
    include '../../app_common/enums.php';
    include '../../app_pagination/pagination.php';
    include '../../app_common/db_name.php';

    $db = new Database();
    $conn = $db->getConnection();
    $tm = new TransactionManager($conn);

    $member_id = 0;
    if (!empty($_SESSION['member_id'])) {
        $member_id = (int)$_SESSION['member_id'];
    } elseif (!empty($_SESSION['user_id'])) {
        $member_id = (int)$_SESSION['user_id'];
    }

    // actions start
    if(isset($_POST['action']) && !empty($_POST['action'])) {
        $action = $_POST['action'];

        if ($action == "add_custom_guest_receivable") {
            try {
                $target_member_id = isset($_POST['member_id']) ? (int)$_POST['member_id'] : 0;
                $amount = isset($_POST['amount']) ? (float)$_POST['amount'] : 0.0;
                $head = isset($_POST['head']) ? (int)$_POST['head'] : 12;
                $discription = isset($_POST['discription']) ? trim($_POST['discription']) : 'Guest Custom Fee';
                $date = !empty($_POST['date']) ? $_POST['date'] : date('Y-m-d');

                if ($target_member_id <= 0 || $amount <= 0) {
                    http_response_code(400);
                    header('Content-Type: application/json');
                    echo json_encode(["Message" => "Invalid member ID or amount."]);
                    exit();
                }

                $sql = "INSERT INTO tbl_member_recievable (member_id, fees, date, head, discription, iscomplete) VALUES (?, ?, ?, ?, ?, 0)";
                app_exec_nonquery($sql, [$target_member_id, $amount, $date, $head, $discription], "idiss");

                header('Content-Type: application/json');
                echo json_encode(["success" => true, "Message" => "Custom fee receivable added successfully."]);
                exit();
            } catch (Exception $e) {
                http_response_code(500);
                header('Content-Type: application/json');
                echo json_encode(["Message" => "Error adding custom fee: " . $e->getMessage()]);
                exit();
            }
        }

        // Load members list for admin member selector panel
        if ($action == "load_members_for_select") {
            try {
                $rowsPerPage = 8;
                $current_page = isset($_POST['page']) ? (int)$_POST['page'] : 1;
                $offset = ($current_page - 1) * $rowsPerPage;
                $search = isset($_POST['val']) ? trim($_POST['val']) : '';

                $sqldatarows = "SELECT m.id, m.first_name, m.middle_name, m.last_name, m.phone, m.img, m.member_type,
                                       GROUP_CONCAT(g.name SEPARATOR ', ') AS group_names
                                FROM tbl_members AS m
                                LEFT JOIN tbl_group_member_map AS gmm ON m.id = gmm.member_id
                                LEFT JOIN tbl_groups AS g ON gmm.group_id = g.id
                                WHERE m.inactive = 0";
                $sqlcountrows = "SELECT COUNT(DISTINCT m.id) AS total
                                 FROM tbl_members AS m
                                 LEFT JOIN tbl_group_member_map AS gmm ON m.id = gmm.member_id
                                 LEFT JOIN tbl_groups AS g ON gmm.group_id = g.id
                                 WHERE m.inactive = 0";

                $parameters = [];
                $types = "";

                if ($search !== '') {
                    $sqldatarows .= " AND (m.first_name LIKE ? OR m.middle_name LIKE ? OR m.last_name LIKE ? OR m.phone LIKE ?)";
                    $sqlcountrows .= " AND (m.first_name LIKE ? OR m.middle_name LIKE ? OR m.last_name LIKE ? OR m.phone LIKE ?)";
                    $search_param = "%" . $search . "%";
                    $parameters = [$search_param, $search_param, $search_param, $search_param];
                    $types = "ssss";
                }

                $sqldatarows .= " GROUP BY m.id, m.first_name, m.middle_name, m.last_name, m.phone, m.img ORDER BY m.first_name, m.middle_name, m.last_name LIMIT ?, ?";
                $parameters_data = $parameters;
                $parameters_data[] = $offset;
                $parameters_data[] = $rowsPerPage;
                $types_data = $types . "ii";

                $result = app_exec_getresult($sqldatarows, $parameters_data, $types_data);
                if ($types !== "") {
                    $totalRowsResult = app_exec_getresult($sqlcountrows, $parameters, $types);
                } else {
                    $totalRowsResult = app_exec_query($sqlcountrows);
                }

                $totalRows = $totalRowsResult->fetch_assoc()['total'];
                $qrydata = [];
                if ($result && $result->num_rows > 0) {
                    while ($row = $result->fetch_assoc()) {
                        $qrydata[] = $row;
                    }
                }
                $pagination = ["total_rows" => $totalRows];
                echo json_encode([$pagination, $qrydata]);
                exit();
            } catch (Exception $e) {
                http_response_code(500);
                echo json_encode(["Message" => "Error: " . $e->getMessage()]);
                exit();
            }
        }

        // Switch the active member in session (admin only)
        if ($action == "switch_member") {
            if (!isset($_SESSION['login_id']) || $_SESSION['login_id'] != 1) {
                http_response_code(403);
                echo json_encode(["Message" => "Unauthorized."]);
                exit();
            }
            $member_id = isset($_POST['member_id']) ? (int)$_POST['member_id'] : 0;
            if ($member_id > 0) {
                $res = app_exec_getresult("SELECT id, first_name, middle_name, last_name FROM tbl_members WHERE id = ?", [$member_id], "i");
                if ($res && $row = $res->fetch_assoc()) {
                    $_SESSION['member_id'] = $row['id'];
                    $_SESSION['first_name'] = $row['first_name'];
                    $_SESSION['middle_name'] = $row['middle_name'];
                    $_SESSION['last_name'] = $row['last_name'];
                    echo json_encode(["success" => true, "member_id" => $row['id']]);
                    exit();
                }
            }
            echo json_encode(["success" => false, "Message" => "Member not found."]);
            exit();
        }


        if($action=="save_recieveble"){ 
            try{
            
                $sql = "UPDATE tbl_member_recievable SET fees=?, date=?,  head=?, discription=? WHERE id=?";
                
                $parameters = [
                    $_POST['amount'],
                    $_POST['date'],
                    $_POST['head'],
                    $_POST['discription'],
                    $_POST['id'],
                ];
                $types="isisi";
        
                // Saving Query as  text file end
                app_exec_nonquery($sql, $parameters, $types);

            }
             catch (Exception $e) {
                http_response_code(500);   
                echo "Oops! Something went wrong." . $e->getMessage();
                return;
            }
    
        }

        // action to save payment details start
        if($action=="save_payment"){ 
            $current_payment = $_POST['amount'];
            try{
                $tm->begin();

                // Get the total receivable and total received amount so far (excluding canceled payments)
                $sql = "SELECT p.fees as total_recieveble_Amount, COALESCE(SUM(pd.fees), 0) AS total_recieved_amount
                        FROM tbl_member_recievable AS p
                        LEFT JOIN tbl_member_recieved AS pd ON p.id = pd.receiveble_id AND (pd.cancel = 0 OR pd.cancel IS NULL)
                        WHERE p.id = ?
                        GROUP BY p.id";
                $parameters = [$_POST['id']];
                $types = "i";
                $res_balance = app_exec_getresult($sql, $parameters, $types);
                $row_balance = $res_balance ? $res_balance->fetch_assoc() : null;
                
                $total_recieveble_Amount = $row_balance ? (float)$row_balance['total_recieveble_Amount'] : 0.0;
                $total_recieved_amount = $row_balance ? (float)$row_balance['total_recieved_amount'] : 0.0;
                
                $outstanding = $total_recieveble_Amount - $total_recieved_amount;
                if ($outstanding < 0) $outstanding = 0.0;
                
                $amount_to_pay = (float)$current_payment;
                $amount_to_wallet = 0.0;
                
                if ($amount_to_pay > $outstanding && $outstanding > 0) {
                    $amount_to_wallet = $amount_to_pay - $outstanding;
                    $amount_to_pay = $outstanding;
                }

                $m_group_id = 2;
                $res_mg = app_exec_getresult("SELECT group_id FROM tbl_group_member_map WHERE member_id = ? LIMIT 1", [$_POST['member_id']], "i");
                if ($res_mg && $rmg = $res_mg->fetch_assoc()) {
                    $m_group_id = (int)$rmg['group_id'];
                }

                $sql = "INSERT INTO tbl_member_recieved (member_id, fees, date, login_id, head, discription, receiveble_id, iswallet, flag, transaction_type, group_id) VALUES (?, ?, ?, ?, ?, ?, ?, 0, ?, ?, ?)";
                $parameters = [
                    $_POST['member_id'],
                    $amount_to_pay,
                    $_POST['date'],
                    $_SESSION['login_id'],
                    $_POST['head'],
                    $_POST['discription'],
                    $_POST['id'],
                    $_POST['flag'],
                    $_POST['transaction_type'],
                    $m_group_id
                ];
                $types = "iisiisiiii";
                
                app_exec_roll_back_nonquery($conn, $sql, $parameters, $types);

                if ($amount_to_wallet > 0) {
                    $sql_wallet = "INSERT INTO tbl_wallet (client_id, date, amount, type, group_id) VALUES (?, ?, ?, 'credit', ?)";
                    $params_wallet = [
                        $_POST['member_id'],
                        $_POST['date'],
                        $amount_to_wallet,
                        $m_group_id
                    ];
                    $types_wallet = "isdi";
                    if (!app_exec_roll_back_nonquery($conn, $sql_wallet, $params_wallet, $types_wallet)) {
                        throw new Exception("Error in Wallet Topup");
                    }
                }

                // Update the completeness of the receivable
                $isComplete = ($total_recieveble_Amount - ($total_recieved_amount + $amount_to_pay) == 0) ? 1 : 0;
                $sql = "UPDATE tbl_member_recievable SET iscomplete = ? WHERE id = ?";
                $parameters = [$isComplete, $_POST['id']];
                $types = "ii";
                if (!app_exec_roll_back_nonquery($conn, $sql, $parameters, $types)) {
                    throw new Exception("Error in Updation");
                }
                // update query using the result of another query
                if($_POST['transaction_type']==2){
                    $sql = "SELECT AUTO_INCREMENT FROM INFORMATION_SCHEMA.TABLES WHERE TABLE_SCHEMA = '$db_name' AND TABLE_NAME = 'tbl_payable'";
                    $result1 = app_exec_query($sql);
                    $row1 = $result1->fetch_assoc();
                    $payable_id = htmlspecialchars($row1['AUTO_INCREMENT']);


                    $sql = "INSERT INTO tbl_payable (date, head, particuler, amount, iscomplete, flag, invoice_id,is_member_trans) VALUES (?, 2, ?, ?, 1, ?, ?,?)";
                    $parameters = [
                        $_POST['date'],
                        $_POST['discription'],
                        $amount_to_pay,
                        $_POST['flag'],
                        $_POST['id'],
                        MemberTransaction::member,
                    ];
                    $types="sssiii";
                    if (!app_exec_roll_back_nonquery($conn, $sql, $parameters, $types)) {
                        throw new Exception("Error in Updation");
                    }

                    $sql = "INSERT INTO tbl_paid (date, head, particuler, amount, payable_id, flag,transaction_type, invoice_id, is_member_trans) VALUES (?, 2, ?, ?, ?, ?, ?, ?, ?)";
                    $parameters = [
                        $_POST['date'],
                        $_POST['discription'],
                        $amount_to_pay,
                        $payable_id,
                        $_POST['flag'],
                        $_POST['transaction_type'],
                        $_POST['id'],
                        MemberTransaction::member,
                    ];
                    $types="sssiiiii";
                    if (!app_exec_roll_back_nonquery($conn, $sql, $parameters, $types)) {
                        throw new Exception("Error in Updation");
                    }
                }

                $tm->commit();
                echo 'Updated Sucessfully';
            }
            catch (Exception $e) {
                $tm->rollback();
                echo $e->getMessage();
            }
        }
        // action to save payment details ends

        // action to delete recieved amount start
        if($action=="cancel_payment"){
            
            try{
                $tm->begin();

                $sql = "UPDATE tbl_member_recievable SET cancel = ? WHERE id = ?";
                $types="ii";
                $parameters = [
                    $_POST['status'],
                    $_POST['id'],
                ];
                
                if (!app_exec_roll_back_nonquery($conn, $sql, $parameters, $types)) {
                     throw new Exception("Error in Updation");
                }

                $sql = "UPDATE tbl_member_recieved SET cancel = ? WHERE receiveble_id = ?";
                $types="ii";
                $parameters = [
                    $_POST['status'],
                    $_POST['id'],
                ];
                
                if (!app_exec_roll_back_nonquery($conn, $sql, $parameters, $types)) {
                     throw new Exception("Error in Updation");
                }


                $sql = "UPDATE tbl_payable SET cancel = ? WHERE invoice_id = ? AND is_member_trans = ?";
                $types="iii";
                $parameters = [
                    $_POST['status'],
                    $_POST['id'],
                    MemberTransaction::member,
                ];
                
                if (!app_exec_roll_back_nonquery($conn, $sql, $parameters, $types)) {
                     throw new Exception("Error in Updation");
                }

                $sql = "UPDATE tbl_paid SET cancel = ? WHERE invoice_id = ? AND is_member_trans = ?";
                $types="iii";
                $parameters = [
                    $_POST['status'],
                    $_POST['id'],
                    MemberTransaction::member,
                ];
                
                if (!app_exec_roll_back_nonquery($conn, $sql, $parameters, $types)) {
                     throw new Exception("Error in Updation");
                }

              
                $tm->commit();
                echo 'Updated Sucessfully';
            }
            catch (Exception $e) {
                $tm->rollback();
                echo $e->getMessage();
            }
        }
        // action to delete recieved amount end

        // action to load  heads for dropdown in the popup start
        if($action=="load_heads"){
            try{
                $sql = "SELECT id, name FROM tbl_payment_head_master WHERE type = 'Credit'";
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
        // action to load heads for dropdown in the popup ends

        if($action=="load_closing_years"){
            try{
                $sql = "SELECT id, from_year, to_year FROM tbl_closing ORDER BY from_year DESC";
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

        
        // action to load all recieveble amount details start
        if($action=="load_data"){
            try{
                $rowsPerPage = 8;
                $current_page = (int)$_POST['page'];
                // Pagination logic
                $sql="";  
                $sqlcountrows="";
                $sqldatarows="";   
        
                $offset = ($current_page - 1) * $rowsPerPage;
                
                $sqldatarows ="SELECT p.id AS recieveble_id,p.member_id,p.login_id,p.date,p.fees AS receiveble_fees,
                p.head,p.discription,p.iscomplete,p.flag,p.cancel,g.type,g.name,
                GROUP_CONCAT(DISTINCT grp.name SEPARATOR ', ') AS group_name,
                COALESCE(SUM(pd.fees), 0) AS total_received_fees FROM tbl_member_recievable AS p 
                LEFT JOIN tbl_payment_head_master AS g ON p.head=g.id 
                LEFT JOIN tbl_member_recieved AS pd ON p.id=pd.receiveble_id AND (pd.cancel = 0 OR pd.cancel IS NULL)
                LEFT JOIN tbl_group_member_map AS gmm ON p.member_id = gmm.member_id 
                LEFT JOIN tbl_groups AS grp ON gmm.group_id = grp.id 
                WHERE p.member_id = ? 
                GROUP BY p.id,p.date,p.discription,p.fees,p.head,g.type,g.name ORDER BY p.iscomplete, p.date";
                 

                    $parameters = [
                        $member_id, // assuming the member inserting
                    ];

                    $types = "i";
        
                $sqlcountrows = "SELECT COUNT(*) AS total FROM tbl_member_recievable WHERE member_id=?";
                
                $sqldatarows .= " LIMIT $offset , $rowsPerPage ";
                
                $result = app_exec_getresult( $sqldatarows, $parameters, $types);
                    
                $totalRowsResult = app_exec_getresult($sqlcountrows, $parameters, $types);
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
            catch (Exception $e) {
                http_response_code(500);   
                echo "Oops! Something went wrong." . $e->getMessage();
                return;
            }
        }
        // action to load all recieveble amount details end

        // action to load wallet balance amount start
         // action to load wallet balance amount start
        if($action=="load_wallet_balance"){
            try{
                $member_id = isset($_POST['id']) ? (int)$_POST['id'] : 0;
                
                if ($member_id <= 0) {
                    echo json_encode([[["wallet_balance" => 0]]]);
                    exit();
                }
                
                // Query to calculate wallet balance (credits - debits)
                $sql = "SELECT 
                        COALESCE(
                            SUM(CASE 
                                WHEN type = 'credit' THEN amount
                                WHEN type = 'debit' THEN -amount
                                ELSE 0 
                            END), 
                            0
                        ) AS wallet_balance
                        FROM tbl_wallet 
                        WHERE client_id = ?";

                $result = app_exec_getresult($sql, [$member_id], "i");
                
                // Always return a result, even if no rows found in tbl_wallet
                $wallet_balance = 0;
                if ($result) {
                    if ($result->num_rows > 0) {
                        $row = $result->fetch_assoc();
                        $wallet_balance = (float)$row['wallet_balance'];
                    }
                }
                
                // Return wallet balance as array (maintaining original response format)
                $qrydata = [["wallet_balance" => $wallet_balance]];
                $resdata = array($qrydata);
        
                echo json_encode($resdata);
                exit();
                
            }
            catch (Exception $e) {
                http_response_code(500);   
                echo json_encode(["error" => "Oops! Something went wrong. " . $e->getMessage()]);
                exit();
            }
        }
        // action to load wallet balance amount end
        
         if($action=="save_payment_from_wallet"){ 
             $current_payment = (float)$_POST['amount'];
             $m_id = (int)$_POST['member_id'];
             
             // Check member wallet balance
             $sql_wal = "SELECT COALESCE(SUM(CASE WHEN type = 'credit' THEN amount WHEN type = 'debit' THEN -amount ELSE 0 END), 0) AS wallet_balance FROM tbl_wallet WHERE client_id = ?";
             $res_wal = app_exec_getresult($sql_wal, [$m_id], "i");
             $wal_bal = $res_wal ? (float)($res_wal->fetch_assoc()['wallet_balance'] ?? 0) : 0.0;
             
             if ($wal_bal <= 0 || $current_payment > $wal_bal) {
                 http_response_code(400);
                 header('Content-Type: application/json');
                 echo json_encode(["Message" => "Insufficient wallet balance! Current wallet balance is ₹" . number_format($wal_bal, 2)]);
                 exit();
             }

             try{
                 $tm->begin();
                $sql = "INSERT INTO tbl_member_recieved (member_id, fees, date, login_id, head, discription, receiveble_id, iswallet,flag,transaction_type) VALUES (?, ?, ?, ?, ?, ?,?,1,?,?)";
                $parameters = [
                    $_POST['member_id'],
                    $_POST['amount'],
                    $_POST['date'],
                    $_SESSION['login_id'],
                    $_POST['head'],
                    $_POST['discription'],
                    $_POST['id'],
                    $_POST['flag'],
                    $_POST['transaction_type'],
                ];
                $types = "iisiisiii";
                
                app_exec_roll_back_nonquery($conn, $sql, $parameters, $types);

                // select query to get the total recieved and total recieveble amount start
                $sql = "SELECT p.fees as total_recieveble_Amount,SUM(pd.fees) AS total_recieved_amount
                FROM tbl_member_recievable AS p
                LEFT JOIN tbl_payment_head_master AS g ON p.head = g.id
                LEFT JOIN tbl_member_recieved AS pd ON p.id = pd.receiveble_id where pd.receiveble_id = ?
                GROUP BY p.id, p.date, p.discription, p.fees, p.head, g.type, g.name";
                $parameters = [
                    $_POST['id'],
                ];
                $types = "i";
                
                $result = app_exec_getresult($sql, $parameters, $types);
                // select query to get the total recieved ans total recieveble amount end

                // update query using the result of another query start
                if (mysqli_num_rows($result) > 0) {
                    // output data of each row
                    while($row = mysqli_fetch_assoc($result)) {
                        $total_recieveble_Amount = (int)$row["total_recieveble_Amount"];
                        $total_recieved_amount = $row["total_recieved_amount"] !== null ? (int)$row["total_recieved_amount"] : 0;
                        $isComplete = ($total_recieveble_Amount - $total_recieved_amount == 0) ? 1 : 0;

                        $sql = "UPDATE tbl_member_recievable SET iscomplete = ?  WHERE id = ?";
                        $parameters = [
                            $isComplete,
                            $_POST['id'],
                        ];
                        $types="ii";
                        
                        // app_exec_nonquery($sql, $parameters, $types);
                        if (!app_exec_roll_back_nonquery($conn, $sql, $parameters, $types)) {
                            throw new Exception("Error in Updation");
                        }
                       
                    }
                }

                $sql = "INSERT INTO tbl_wallet (client_id ,date, amount, type) VALUES (?, ?, ?, 'debit')";
                $parameters = [
                    $_POST['member_id'],
                    $_POST['date'],
                    $_POST['amount'],
                ];
                $types = "isi";
                if (!app_exec_roll_back_nonquery($conn, $sql, $parameters, $types)) {
                    throw new Exception("Error in Insertion in wallet");
                }
               
                $tm->commit();
                echo 'Updated Sucessfully';
            }
            catch (Exception $e) {
               
                $tm->rollback();
                http_response_code(500);
                // echo http_response_code();
                echo $e->getMessage();
               
            }
        }

        if ($action == "setoff_receiveble") {
            try {
                $tm->begin();
                $target_member_id = !empty($_POST['member_id']) ? (int)$_POST['member_id'] : $member_id;

                // Handle file upload
                $bill_photo_name = null;
                if (isset($_FILES['bill_photo']) && $_FILES['bill_photo']['error'] === UPLOAD_ERR_OK) {
                    $file = $_FILES['bill_photo'];
                    $uploadDir = '../../image_upload/payments/';
                    
                    if (!is_dir($uploadDir)) {
                        mkdir($uploadDir, 0777, true);
                    }
                    
                    $mimeType = mime_content_type($file['tmp_name']);
                    $allowedMimeTypes = ['image/jpeg', 'image/png', 'image/gif', 'image/webp', 'application/pdf'];
                    
                    if (in_array($mimeType, $allowedMimeTypes)) {
                        date_default_timezone_set('Asia/Kolkata');
                        $extension = pathinfo($file['name'], PATHINFO_EXTENSION);
                        $bill_photo_name = 'bill_' . date('Ymd_His') . '_' . uniqid() . '.' . $extension;
                        $targetPath = $uploadDir . $bill_photo_name;
                        
                        if (!move_uploaded_file($file['tmp_name'], $targetPath)) {
                            throw new Exception("Failed to save uploaded bill file.");
                        }
                    } else {
                        throw new Exception("Unsupported file format. Only JPEG, PNG, GIF, WebP, and PDF are allowed.");
                    }
                }

                // Get the array and decode it
                $receivedArray = json_decode($_POST['received_array'], true);
                if (!is_array($receivedArray)) {
                    throw new Exception("Invalid received array data.");
                }

                foreach ($receivedArray as $item) {
                    $receiveble_id = $item['receiveble_id'];
                    $flag = $item['flag'];
                    $received = $item['received'];
                    $balance = $item['balance'];

                    $sql = "INSERT INTO tbl_member_recieved (member_id, fees, date,login_id , receiveble_id, flag, transaction_type, bill_photo) 
                            VALUES (?, ?, ?, ?, ?, ?, ?, ?)";

                    $parameters = [
                        $target_member_id,
                        $received,
                        $_POST['date'],
                        $_SESSION['login_id'],
                        $receiveble_id,
                        $flag,
                        $_POST['transaction_type'],
                        $bill_photo_name
                    ];

                    $types = "idsiiiis"; // i=int, d=double, s=string

                    if (!app_exec_roll_back_nonquery($conn, $sql, $parameters, $types)) {
                        throw new Exception("Error inserting received item with ID: $receiveble_id");
                    }

                    if($balance==0){
                         $sql = "UPDATE tbl_member_recievable SET iscomplete = 1  WHERE id = ? ";

                        $parameters = [
                            $receiveble_id,
                        ];

                        $types = "i";
                        if (!app_exec_roll_back_nonquery($conn, $sql, $parameters, $types)) {
                            throw new Exception("Error in Updation: $receiveble_id");
                        }
                    }

                    if($_POST['transaction_type']==2){
                        $sql = "SELECT AUTO_INCREMENT FROM INFORMATION_SCHEMA.TABLES WHERE TABLE_SCHEMA = '$db_name' AND TABLE_NAME = 'tbl_payable'";
                        $result1 = app_exec_query($sql);
                        $row1 = $result1->fetch_assoc();
                        $payable_id = htmlspecialchars($row1['AUTO_INCREMENT']);


                        $sql = "INSERT INTO tbl_payable (date, head, amount, iscomplete, flag, invoice_id,is_member_trans) VALUES (?, 2, ?, 1, ?, ?,?)";
                        $parameters = [
                            $_POST['date'],
                            $received,
                            $flag,
                            $receiveble_id,
                            MemberTransaction::member,
                        ];
                        $types="siiii";
                        if (!app_exec_roll_back_nonquery($conn, $sql, $parameters, $types)) {
                            throw new Exception("Error in Updation");
                        }

                        $sql = "INSERT INTO tbl_paid (date, head, amount, payable_id, flag,transaction_type, invoice_id, is_member_trans) VALUES (?, 2, ?, ?, ?, ?, ?, ?)";
                        $parameters = [
                            $_POST['date'],
                            $received,
                            $payable_id,
                            $flag,
                            $_POST['transaction_type'],
                            $receiveble_id,
                            MemberTransaction::member,
                        ];
                        $types="siiiiii";
                        if (!app_exec_roll_back_nonquery($conn, $sql, $parameters, $types)) {
                            throw new Exception("Error in Updation");
                        }
                    }
                }

                $tm->commit();
                echo "Transaction Successful!";

            } catch (Exception $e) {
                $tm->rollback();
                http_response_code(500);
                header('Content-Type: application/json');
                echo json_encode(array('Message' => $e->getMessage()));
            }
        }

        if ($action == "setoff_receiveble_from_wallet") {
            try {
                $tm->begin();
                $target_member_id = !empty($_POST['member_id']) ? (int)$_POST['member_id'] : $member_id;

                // Get the array and decode it
                $receivedArray = json_decode($_POST['received_array'], true);
                if (!is_array($receivedArray)) {
                    throw new Exception("Invalid received array data.");
                }

                foreach ($receivedArray as $item) {
                    $receiveble_id = $item['receiveble_id'];
                    $flag = $item['flag'];
                    $received = $item['received'];
                    $balance = $item['balance'];

                    $sql = "INSERT INTO tbl_member_recieved (member_id, fees, date,login_id , receiveble_id, iswallet, flag, transaction_type) 
                            VALUES (?, ?, ?, ?, ?, 1, ?, ?)";

                    $parameters = [
                        $target_member_id,
                        $received,
                        $_POST['date'],
                        $_SESSION['login_id'],
                        $receiveble_id,
                        $flag,
                        $_POST['transaction_type']
                    ];

                    $types = "idsiiii"; // i=int, d=double, s=string

                    if (!app_exec_roll_back_nonquery($conn, $sql, $parameters, $types)) {
                        throw new Exception("Error inserting received item with ID: $receiveble_id");
                    }

                    if($balance==0){
                         $sql = "UPDATE tbl_member_recievable SET iscomplete = 1  WHERE id = ? ";

                        $parameters = [
                            $receiveble_id,
                        ];

                        $types = "i";
                        if (!app_exec_roll_back_nonquery($conn, $sql, $parameters, $types)) {
                            throw new Exception("Error in Updation: $receiveble_id");
                        }
                    }

                    $sql = "INSERT INTO tbl_wallet (client_id,date, amount,type) VALUES (?, ?, ?, 'debit')";
                    $parameters = [
                        $target_member_id,
                        $_POST['date'],
                        $received,
                    ];
                    $types="isd";
                    if (!app_exec_roll_back_nonquery($conn, $sql, $parameters, $types)) {
                        throw new Exception("Error updating wallet balance.");
                    }

                    if($_POST['transaction_type']==2){
                        $sql = "SELECT AUTO_INCREMENT FROM INFORMATION_SCHEMA.TABLES WHERE TABLE_SCHEMA = '$db_name' AND TABLE_NAME = 'tbl_payable'";
                        $result1 = app_exec_query($sql);
                        $row1 = $result1->fetch_assoc();
                        $payable_id = htmlspecialchars($row1['AUTO_INCREMENT']);


                        $sql = "INSERT INTO tbl_payable (date, head, amount, iscomplete, flag, invoice_id,is_member_trans) VALUES (?, 2, ?, 1, ?, ?,?)";
                        $parameters = [
                            $_POST['date'],
                            $received,
                            $flag,
                            $receiveble_id,
                            MemberTransaction::member,
                        ];
                        $types="siiii";
                        if (!app_exec_roll_back_nonquery($conn, $sql, $parameters, $types)) {
                            throw new Exception("Error in Updation");
                        }

                        $sql = "INSERT INTO tbl_paid (date, head, amount, payable_id, flag,transaction_type, invoice_id, is_member_trans) VALUES (?, 2, ?, ?, ?, ?, ?, ?)";
                        $parameters = [
                            $_POST['date'],
                            $received,
                            $payable_id,
                            $flag,
                            $_POST['transaction_type'],
                            $receiveble_id,
                            MemberTransaction::member,
                        ];
                        $types="siiiiii";
                        if (!app_exec_roll_back_nonquery($conn, $sql, $parameters, $types)) {
                            throw new Exception("Error in Updation");
                        }
                    }
                }

                $tm->commit();
                echo "Transaction Successful!";

            } catch (Exception $e) {
                $tm->rollback();
                http_response_code(500);
                header('Content-Type: application/json');
                echo json_encode(array('Message' => $e->getMessage()));
            }
        }

        // action to load  heads for dropdown in the popup start
        if($action=="load_wallet_amount"){
            try{
                $sql = "SELECT 
                    COALESCE(
                        SUM(CASE WHEN type = 'credit' THEN amount ELSE 0 END) -
                        SUM(CASE WHEN type = 'debit' THEN amount ELSE 0 END)
                    , 0) AS wallet_balance
                FROM tbl_wallet
                WHERE client_id = ?";

                $parameters = [
                    $member_id,
                ];
                $types="i";

                $result = app_exec_getresult($sql,$parameters,$types);

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
        // action to load heads for dropdown in the popup end

        // action to load pending payments of a member start
        if($action=="load_pending_payment"){
            try{
                            $sql = "SELECT
                    (SELECT COALESCE(SUM(fees), 0) FROM tbl_member_recievable WHERE member_id = ? AND cancel = 0) AS receivable_fees,
                    (SELECT COALESCE(SUM(fees), 0) FROM tbl_member_recieved WHERE member_id = ? AND (cancel = 0 OR cancel IS NULL)) AS received_fees
            ";

                $parameters = [
                    $member_id,
                    $member_id,
                ];
                $types="ii";

                $result = app_exec_getresult($sql,$parameters,$types);

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
        // action to load pending payments of a member end

        // action to save edited receivable amount start
        if ($action == "save_receiveble_amount") {
            try {
                $sql = "UPDATE tbl_member_recievable SET fees = ?, date = ?, discription = ? WHERE id = ?";
                $parameters = [
                    $_POST['amount'],
                    $_POST['date'],
                    $_POST['discription'],
                    $_POST['id']
                ];
                $types = "issi";
                app_exec_nonquery($sql, $parameters, $types);
                echo "Updated Successfully";
                exit();
            } catch (Exception $e) {
                http_response_code(500);
                echo "Oops! Something went wrong: " . $e->getMessage();
                exit();
            }
        }
        // action to save edited receivable amount end

        if ($action == "load_member_pending_receivables") {
            try {
                $target_member_id = isset($_POST['member_id']) ? (int)$_POST['member_id'] : $member_id;
                $sql = "SELECT p.id, p.date, p.fees, p.head, p.discription, p.flag, fhm.name as head_name,
                               COALESCE(SUM(pd.fees), 0) AS total_received
                        FROM tbl_member_recievable AS p 
                        LEFT JOIN tbl_fees_head_master AS fhm ON p.head = fhm.id
                        LEFT JOIN tbl_member_recieved AS pd ON p.id = pd.receiveble_id AND (pd.cancel = 0 OR pd.cancel IS NULL)
                        WHERE p.member_id = ? AND p.cancel = 0 AND p.iscomplete = 0
                        GROUP BY p.id
                        ORDER BY p.date ASC";
                $res = app_exec_getresult($sql, [$target_member_id], "i");
                $pending = [];
                if ($res && $res->num_rows > 0) {
                    while ($r = $res->fetch_assoc()) {
                        $due = (float)$r['fees'] - (float)$r['total_received'];
                        if ($due > 0) {
                            $r['pending_amount'] = $due;
                            $pending[] = $r;
                        }
                    }
                }
                echo json_encode($pending);
                exit();
            } catch (Throwable $e) {
                http_response_code(500);
                echo json_encode(["error" => $e->getMessage()]);
                exit();
            }
        }

    }

    



    
?>