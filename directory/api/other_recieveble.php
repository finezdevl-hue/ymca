<?php
    session_start();
    include '../../app_common/db_connect.php';
    include '../../app_common/db_name.php';
    include '../../app_pagination/pagination.php';
    include '../../app_common/enums.php';

    // actions start
    if(isset($_POST['action']) && !empty($_POST['action'])) {
        $action = $_POST['action'];

        // action to save payment details start
        if($action=="save_payment"){ 
            $current_payment = $_POST['amount'];
            try{
                $tm->begin();
                $group_id = !empty($_POST['group_id']) ? (int)$_POST['group_id'] : 2;
                $sql = "INSERT INTO tbl_other_recieved (date, head, particuler, amount, recieveble_id, flag, transaction_type, group_id) VALUES (?, ?, ?, ?, ?, ?, ?, ?)";
                $parameters = [
                    $_POST['date'],
                    $_POST['head'],
                    $_POST['particuler'],
                    $_POST['amount'],
                    $_POST['id'],
                    $_POST['flag'],
                    $_POST['transaction_type'],
                    $group_id
                ];
                $types = "sisiiiii";
                
                app_exec_roll_back_nonquery($conn, $sql, $parameters, $types);

                // select query to get the total recieved and total recieveble amount start
                $sql = "SELECT p.amount as total_recieveble_Amount,SUM(pd.amount) AS total_recieved_amount
                FROM tbl_other_recieveble AS p
                LEFT JOIN tbl_payment_head_master AS g ON p.head = g.id
                LEFT JOIN tbl_other_recieved AS pd ON p.id = pd.recieveble_id where pd.recieveble_id = ?
                GROUP BY p.id, p.date, p.particuler, p.amount, p.head, g.type, g.name";
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

                        $sql = "UPDATE tbl_other_recieveble SET iscomplete = ?  WHERE id = ?";
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
                // update query using the result of another query
                if($_POST['transaction_type']==2){
                    $sql = "SELECT AUTO_INCREMENT FROM INFORMATION_SCHEMA.TABLES WHERE TABLE_SCHEMA = '$db_name' AND TABLE_NAME = 'tbl_payable'";
                    $result1 = app_exec_query($sql);
                    $row1 = $result1->fetch_assoc();
                    $payable_id = htmlspecialchars($row1['AUTO_INCREMENT']);


                    $sql = "INSERT INTO tbl_payable (date, head, particuler, amount, iscomplete, flag, invoice_id) VALUES (?, 2, ?, ?, 1, ?, ?)";
                    $parameters = [
                        $_POST['date'],
                        $_POST['particuler'],
                        $_POST['amount'],
                        $_POST['flag'],
                        $_POST['id'],
                    ];
                    $types="sssii";
                    if (!app_exec_roll_back_nonquery($conn, $sql, $parameters, $types)) {
                        throw new Exception("Error in Updation");
                    }

                    $sql = "INSERT INTO tbl_paid (date, head, particuler, amount, payable_id, flag,transaction_type, invoice_id,is_member_trans) VALUES (?, 2, ?, ?, ?, ?, ?, ?, ?)";
                    $parameters = [
                        $_POST['date'],
                        $_POST['particuler'],
                        $_POST['amount'],
                        $payable_id,
                        $_POST['flag'],
                        $_POST['transaction_type'],
                        $_POST['id'],
                        MemberTransaction::other, // class for get the value of other transaction
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

        // action to delete recieved amount start
        if($action=="delete_payment"){
            
            try{
                $tm->begin();

                $sql = "DELETE FROM tbl_other_recieveble WHERE id = ?";
                $types="i";
                $parameters = [
                    $_POST['id'],
                ];
                
                if (!app_exec_roll_back_nonquery($conn, $sql, $parameters, $types)) {
                     throw new Exception("Error in Deletion");
                }

                $sql = "DELETE FROM tbl_other_recieved WHERE recieveble_id = ?";
                $types="i";
                $parameters = [
                    $_POST['id'],
                ];
                
                if (!app_exec_roll_back_nonquery($conn, $sql, $parameters, $types)) {
                     throw new Exception("Error in Deletion");
                }

              
                $tm->commit();
                echo 'Deleted Sucessfully';
            }
            catch (Exception $e) {
                $tm->rollback();
                echo $e->getMessage();
            }
        }
        // action to delete recieved amount end

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
                
                $sqldatarows ="SELECT p.id AS recieveble_id,p.date,p.particuler,p.amount AS recieveble_amount,
                p.head,p.iscomplete,p.flag,p.cancel,g.type,g.name,SUM(pd.amount) AS total_recieved_amount
                FROM tbl_other_recieveble AS p
                LEFT JOIN tbl_payment_head_master AS g ON p.head = g.id
                LEFT JOIN tbl_other_recieved AS pd ON p.id = pd.recieveble_id
                GROUP BY p.id, p.date, p.particuler, p.amount, p.head, g.type, g.name
                ORDER BY p.date DESC";
        
                $sqlcountrows = "SELECT COUNT(*) AS total FROM tbl_other_recieveble ";
                
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
            catch (Exception $e) {
                http_response_code(500);   
                echo "Oops! Something went wrong." . $e->getMessage();
                return;
            }
        }
        // action to load all recieveble amount details end

        if($action=="cancel_payment"){
            
            try{
                $tm->begin();

                $sql = "UPDATE tbl_other_recieveble SET cancel = ? WHERE id = ?";
                $types="ii";
                $parameters = [
                    $_POST['status'],
                    $_POST['id'],
                ];
                
                if (!app_exec_roll_back_nonquery($conn, $sql, $parameters, $types)) {
                     throw new Exception("Error in Updation");
                }

                $sql = "UPDATE tbl_other_recieved SET cancel = ? WHERE recieveble_id = ?";
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
                    MemberTransaction::other,
                ];
                
                if (!app_exec_roll_back_nonquery($conn, $sql, $parameters, $types)) {
                     throw new Exception("Error in Updation");
                }

                $sql = "UPDATE tbl_paid SET cancel = ? WHERE invoice_id = ? AND is_member_trans = ?";
                $types="iii";
                $parameters = [
                    $_POST['status'],
                    $_POST['id'],
                    MemberTransaction::other,
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
    }
?>