<?php
    session_start();
    include_once __DIR__ . '/../app_common/db_connect.php';
    include_once __DIR__ . '/../app_common/auth_helper.php';
    include_once __DIR__ . '/../app_pagination/pagination.php';

    $current_login = (int)($_SESSION['login_id'] ?? 0);
    if (empty($current_login) || isNormalMember($current_login)) {
        http_response_code(403);
        echo json_encode(['status' => 'error', 'message' => 'Unauthorized access.']);
        exit();
    }

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

        // action to add new payment details and get the last inserted id en start
        if($action=="save_payment"){ 

            // Start Transaction
            // $tm->begin(); // begin transaction 
            try{
                if($_POST['id']==0){
                    if (!isset($_POST['payable']) || trim($_POST['payable']) === '') {
                        $_POST['payable'] = $_POST['paid'];
                    }
                    if (!isset($_POST['payable_date']) || trim($_POST['payable_date']) === '') {
                        $_POST['payable_date'] = $_POST['paid_date'];
                    }

                    $group_id = !empty($_POST['group_id']) ? (int)$_POST['group_id'] : 0;

                   if($_POST['payable']==$_POST['paid']){
                        $sql = "INSERT INTO tbl_payable (date, head, particuler, amount, iscomplete, group_id) VALUES (?, ?, ?, ?, 1, ?)";
                   }
                   else{
                        $sql = "INSERT INTO tbl_payable (date, head, particuler, amount, iscomplete, group_id) VALUES (?, ?, ?, ?, 0, ?)";
                   }
                    $parameters = [
                        $_POST['payable_date'],
                        $_POST['head'],
                        $_POST['particuler'],
                        $_POST['payable'],
                        $group_id
                    ];
                    $types = "sisii";
                   
                    $payable_id = app_exec_getlast_id($sql, $parameters, $types);
                    

                    if ($payable_id !== null) {
                        $sql = "INSERT INTO tbl_paid (date, head, particuler, amount, payable_id, group_id) VALUES (?, ?, ?, ?, ?, ?)";
                        $parameters = [
                            $_POST['paid_date'],
                            $_POST['head'],
                            $_POST['particuler'],
                            $_POST['paid'],
                            $payable_id,
                            $group_id
                        ];
                        $types = "sisiii";
                    
                        app_exec_nonquery($sql, $parameters, $types);
                         
                        
                    }
                   
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
                        $sql_paid = "UPDATE tbl_paid SET date = ?, head = ?, particuler = ?, amount = ? WHERE id = ?";
                        $params_paid = [
                            $_POST['paid_date'],
                            $_POST['head'],
                            $_POST['particuler'],
                            $_POST['paid'],
                            $paid_id
                        ];
                        if (!app_exec_nonquery($sql_paid, $params_paid, "sisii")) {
                            throw new Exception("Error updating paid record");
                        }
                        
                        // 3. Update tbl_payable
                        $sql_payable = "UPDATE tbl_payable SET date = ?, head = ?, particuler = ?, amount = ? WHERE id = ?";
                        $params_payable = [
                            $_POST['payable_date'],
                            $_POST['head'],
                            $_POST['particuler'],
                            $_POST['payable'],
                            $payable_id
                        ];
                        if (!app_exec_nonquery($sql_payable, $params_payable, "sisii")) {
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
                            if (!app_exec_nonquery($sql_up_comp, [$isComplete, $payable_id], "ii")) {
                                throw new Exception("Error updating completeness");
                            }
                        }
                    }
                    echo 'Updated Sucessfully';
                }
                
            }
            catch (Exception $e) {
                http_response_code(500);   
                echo "Oops! Something went wrong." . $e->getMessage();
                return;
            }
        }
        // action to add new payment details and get the last inserted id end

        // action to update payment details start
        if($action=="update_payment"){ 

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
                    app_exec_nonquery($sql_paid, $params_paid, "sisii");
                    
                    // Update tbl_payable automatically setting amount to the new paid amount
                    $sql_payable = "UPDATE tbl_payable SET date = ?, head = ?, particuler = ?, amount = ? WHERE id = ?";
                    $params_payable = [
                        $_POST['date'],
                        $_POST['head'],
                        $_POST['particuler'],
                        $_POST['amount'],
                        $payable_id
                    ];
                    app_exec_nonquery($sql_payable, $params_payable, "sisii");
                    
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
                        app_exec_nonquery($sql_up_comp, [$isComplete, $payable_id], "ii");
                    }
                }
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
                
                $sqldatarows ="SELECT p.id, p.date, p.particuler, p.amount, p.head, p.payable_id,
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