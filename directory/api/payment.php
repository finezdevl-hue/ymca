<?php
    session_start();
    include '../../app_common/db_connect.php';

    $db = new Database();
    $conn = $db->getConnection();
    $tm = new TransactionManager($conn);

    include '../../app_pagination/pagination.php';

    // actions start
    if(isset($_POST['action']) && !empty($_POST['action'])) {
        $action = $_POST['action'];

        // action to delete payment start
        if($action=="delete_payment"){
            $amount=$_POST['amount'];
            try{
                $tm->begin();
                
                $sql = "DELETE FROM tbl_paid WHERE id = ?";
                $types="i";
                $parameters = [
                $_POST['id'],
                ];
                
                app_exec_roll_back_nonquery($conn,$sql, $parameters, $types);

                $sql = "SELECT p.amount AS total_payable_amount,
                SUM(pd.amount) AS total_paid_amount
                FROM tbl_payable AS p
                LEFT JOIN tbl_payment_head_master AS g ON p.head = g.id
                LEFT JOIN tbl_paid AS pd ON p.id = pd.payable_id where pd.payable_id = ?
                GROUP BY p.id, p.date, p.particuler, p.amount, p.head, g.type, g.name ";

                $parameters = [
                    $_POST['payable_id'],
                ];
                $types = "i";
               
                $result = app_exec_getresult($sql, $parameters, $types);
                
                if (mysqli_num_rows($result) > 0) {
                    // output data of each row
                    while($row = mysqli_fetch_assoc($result)) {
                        $total_payable_amount = (int)$row["total_payable_amount"];
                        $total_paid_amount = $row["total_paid_amount"] !== null ? (int)$row["total_paid_amount"] : 0;

                        $isComplete = ($total_payable_amount - $total_paid_amount == 0) ? 1 : 0;
                        $sql = "UPDATE tbl_payable SET iscomplete = ? WHERE id = ?";
                        $parameters = [
                            $isComplete,
                            $_POST['payable_id'],
                        ];
                        $types="ii";
                        // app_exec_nonquery($sql, $parameters, $types);
                        
                        if (!app_exec_roll_back_nonquery($conn, $sql, $parameters, $types)) {
                            throw new Exception("Error in Updation");
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
        // action to delete payment end

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

        // action to add new payment details and get the last inserted id en start
        if($action=="save_payment"){ 

            // Start Transaction
            $tm->begin(); // begin transaction 
            try{
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

                $group_id = !empty($_POST['group_id']) ? (int)$_POST['group_id'] : 2;

                if($_POST['id']==0){
                    if (!isset($_POST['payable']) || trim($_POST['payable']) === '') {
                        $_POST['payable'] = $_POST['paid'];
                    }
                    if (!isset($_POST['payable_date']) || trim($_POST['payable_date']) === '') {
                        $_POST['payable_date'] = $_POST['paid_date'];
                    }

                    $isCompleteVal = ($_POST['payable'] == $_POST['paid']) ? 1 : 0;
                    $sql = "INSERT INTO tbl_payable (date, head, particuler, amount, iscomplete, flag, group_id) VALUES (?, ?, ?, ?, ?, ?, ?)";
                    $parameters = [
                        $_POST['payable_date'],
                        $_POST['head'],
                        $_POST['particuler'],
                        $_POST['payable'],
                        $isCompleteVal,
                        $_POST['flag'],
                        $group_id
                    ];
                    $types = "sisiiii";
                    $payable_id = app_exec_getlast_id_roll_back($conn, $sql, $parameters, $types);
                    
                    if ($payable_id !== null) {
                        $sql = "INSERT INTO tbl_paid (date, head, particuler, amount, payable_id, flag, transaction_type, bill_photo, group_id) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)";
                        $parameters = [
                            $_POST['paid_date'],
                            $_POST['head'],
                            $_POST['particuler'],
                            $_POST['paid'],
                            $payable_id,
                            $_POST['flag'],
                            $_POST['transaction_type'],
                            $bill_photo_name,
                            $group_id
                        ];
                        $types = "sisiiiisi";
                    
                        if (!app_exec_roll_back_nonquery($conn, $sql, $parameters, $types)) {
                            throw new Exception("Error in Updation");
                        }
                    }
                    $tm->commit();
                    echo 'Updated Sucessfully';
                   
                } else {
                    // Update existing payment
                    $paid_id = $_POST['id'];
                    
                    // Fallback logic for payable amount if not specified
                    if (!isset($_POST['payable']) || trim($_POST['payable']) === '') {
                        $_POST['payable'] = $_POST['paid'];
                    }
                    if (!isset($_POST['payable_date']) || trim($_POST['payable_date']) === '') {
                        $_POST['payable_date'] = $_POST['paid_date'];
                    }
                    
                    // 1. Get payable_id from tbl_paid
                    $sql_sel = "SELECT payable_id FROM tbl_paid WHERE id = ?";
                    $res_sel = app_exec_getresult($sql_sel, [$paid_id], "i");
                    if ($res_sel && mysqli_num_rows($res_sel) > 0) {
                        $row_sel = mysqli_fetch_assoc($res_sel);
                        $payable_id = $row_sel['payable_id'];
                        
                        // 2. Update tbl_paid
                        if ($bill_photo_name !== null) {
                            $sql_paid = "UPDATE tbl_paid SET date = ?, head = ?, particuler = ?, amount = ?, flag = ?, transaction_type = ?, bill_photo = ? WHERE id = ?";
                            $params_paid = [
                                $_POST['paid_date'],
                                $_POST['head'],
                                $_POST['particuler'],
                                $_POST['paid'],
                                $_POST['flag'],
                                $_POST['transaction_type'],
                                $bill_photo_name,
                                $paid_id
                            ];
                            $types_paid = "sisiiisi";
                        } else {
                            $sql_paid = "UPDATE tbl_paid SET date = ?, head = ?, particuler = ?, amount = ?, flag = ?, transaction_type = ? WHERE id = ?";
                            $params_paid = [
                                $_POST['paid_date'],
                                $_POST['head'],
                                $_POST['particuler'],
                                $_POST['paid'],
                                $_POST['flag'],
                                $_POST['transaction_type'],
                                $paid_id
                            ];
                            $types_paid = "sisiiii";
                        }
                        if (!app_exec_roll_back_nonquery($conn, $sql_paid, $params_paid, $types_paid)) {
                            throw new Exception("Error updating paid record");
                        }
                        
                        // 3. Update tbl_payable
                        $sql_payable = "UPDATE tbl_payable SET date = ?, head = ?, particuler = ?, amount = ?, flag = ? WHERE id = ?";
                        $params_payable = [
                            $_POST['payable_date'],
                            $_POST['head'],
                            $_POST['particuler'],
                            $_POST['payable'],
                            $_POST['flag'],
                            $payable_id
                        ];
                        if (!app_exec_roll_back_nonquery($conn, $sql_payable, $params_payable, "sisiii")) {
                            throw new Exception("Error updating payable record");
                        }
                        
                        // 4. Recalculate iscomplete in tbl_payable
                        $sql_sum = "SELECT pay.amount AS total_payable, SUM(pd.amount) AS total_paid 
                                    FROM tbl_payable pay 
                                    LEFT JOIN tbl_paid pd ON pay.id = pd.payable_id 
                                    WHERE pay.id = ? 
                                    GROUP BY pay.id";
                        $res_sum = app_exec_getresult($sql_sum, [$payable_id], "i");
                        if ($res_sum && mysqli_num_rows($res_sum) > 0) {
                            $row_sum = mysqli_fetch_assoc($res_sum);
                            $total_payable = (int)$row_sum['total_payable'];
                            $total_paid = $row_sum['total_paid'] !== null ? (int)$row_sum['total_paid'] : 0;
                            $isComplete = ($total_payable - $total_paid == 0) ? 1 : 0;
                            
                            $sql_up_comp = "UPDATE tbl_payable SET iscomplete = ? WHERE id = ?";
                            if (!app_exec_roll_back_nonquery($conn, $sql_up_comp, [$isComplete, $payable_id], "ii")) {
                                throw new Exception("Error updating completeness");
                            }
                        }
                    }
                    $tm->commit();
                    echo 'Updated Sucessfully';
                }
                
            }
            catch (Exception $e) {
                $tm->rollback();
                echo $e->getMessage();
            }
        }
        // action to add new payment details and get the last inserted id end

        // action to update payment details start
        if($action=="update_payment"){ 

            $tm->begin(); // begin transaction 
            try{
                $paid_id = $_POST['id'];
                
                // Get payable_id from tbl_paid
                $sql_sel = "SELECT payable_id FROM tbl_paid WHERE id = ?";
                $res_sel = app_exec_getresult($sql_sel, [$paid_id], "i");
                if ($res_sel && mysqli_num_rows($res_sel) > 0) {
                    $row_sel = mysqli_fetch_assoc($res_sel);
                    $payable_id = $row_sel['payable_id'];
                    
                    // Update tbl_paid
                    $sql_paid = "UPDATE tbl_paid SET date = ?, head = ?, particuler = ?, amount = ? WHERE id = ?";
                    $params_paid = [
                        $_POST['date'],
                        $_POST['head'],
                        $_POST['particuler'],
                        $_POST['amount'],
                        $paid_id
                    ];
                    if (!app_exec_roll_back_nonquery($conn, $sql_paid, $params_paid, "sisii")) {
                        throw new Exception("Error updating paid record");
                    }
                    
                    // Update tbl_payable automatically setting amount to the new paid amount
                    $sql_payable = "UPDATE tbl_payable SET date = ?, head = ?, particuler = ?, amount = ? WHERE id = ?";
                    $params_payable = [
                        $_POST['date'],
                        $_POST['head'],
                        $_POST['particuler'],
                        $_POST['amount'],
                        $payable_id
                    ];
                    if (!app_exec_roll_back_nonquery($conn, $sql_payable, $params_payable, "sisii")) {
                        throw new Exception("Error updating payable record");
                    }
                    
                    // Recalculate completeness
                    $sql_sum = "SELECT pay.amount AS total_payable, SUM(pd.amount) AS total_paid 
                                FROM tbl_payable pay 
                                LEFT JOIN tbl_paid pd ON pay.id = pd.payable_id 
                                WHERE pay.id = ? 
                                GROUP BY pay.id";
                    $res_sum = app_exec_getresult($sql_sum, [$payable_id], "i");
                    if ($res_sum && mysqli_num_rows($res_sum) > 0) {
                        $row_sum = mysqli_fetch_assoc($res_sum);
                        $total_payable = (int)$row_sum['total_payable'];
                        $total_paid = $row_sum['total_paid'] !== null ? (int)$row_sum['total_paid'] : 0;
                        $isComplete = ($total_payable - $total_paid == 0) ? 1 : 0;
                        
                        $sql_up_comp = "UPDATE tbl_payable SET iscomplete = ? WHERE id = ?";
                        if (!app_exec_roll_back_nonquery($conn, $sql_up_comp, [$isComplete, $payable_id], "ii")) {
                            throw new Exception("Error updating completeness");
                        }
                    }
                }
                $tm->commit();
                echo 'Updated Sucessfully';
            }
            catch (Exception $e) {
                http_response_code(500);   
                echo "Oops! Something went wrong." . $e->getMessage();
                return;
            }
        }
        // action to update payment details end

        // action to load  heads for dropdown in the popup starts
        if($action=="load_heads"){
            try{
                $sql = "SELECT id, name FROM tbl_payment_head_master WHERE type = 'Debit'";
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

        // action to load all payment details starts
        if($action=="load_data"){
            try{
                $rowsPerPage = 8;
                $current_page = (int)$_POST['page'];
                // Pagination logic
                $sql="";  
                $sqlcountrows="";
                $sqldatarows="";   
        
                $offset = ($current_page - 1) * $rowsPerPage;
                
                $sqldatarows ="SELECT p.id, p.date, p.particuler, p.amount, p.head, p.payable_id, p.flag, p.transaction_type, p.bill_photo,
                                      pay.date AS payable_date, pay.amount AS payable, g.type, g.name
                FROM tbl_paid as p 
                JOIN tbl_payment_head_master AS g ON p.head = g.id 
                LEFT JOIN tbl_payable AS pay ON p.payable_id = pay.id
                order by p.date DESC";
        
                $sqlcountrows = "SELECT COUNT(*) AS total FROM tbl_paid ";
                
                
                $sqldatarows .= " LIMIT $offset , $rowsPerPage ";
        
                
                $result = app_exec_query($sqldatarows);
                    
                $totalRowsResult = app_exec_query($sqlcountrows);
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
        // action to load all payment details ends
    }
?>