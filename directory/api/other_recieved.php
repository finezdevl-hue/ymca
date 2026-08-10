<?php
    session_start();
    include '../../app_common/db_connect.php';
    include '../../app_pagination/pagination.php';
    include '../../app_common/db_name.php';
    include '../../app_common/enums.php';

    // actions start
    if(isset($_POST['action']) && !empty($_POST['action'])) {
        $action = $_POST['action'];

        // action to delete recieved amount start
        if($action=="delete_payment"){
            $amount=$_POST['amount'];
            try{
                $tm->begin();

                $sql = "DELETE FROM tbl_other_recieved WHERE id = ?";
                $types="i";
                $parameters = [
                $_POST['id'],
                ];
                
                app_exec_roll_back_nonquery($conn,$sql, $parameters, $types);

                $sql = "SELECT p.amount as total_recieveble_Amount,SUM(pd.amount) AS total_recieved_amount
                FROM tbl_other_recieveble AS p
                LEFT JOIN tbl_payment_head_master AS g ON p.head = g.id
                LEFT JOIN tbl_other_recieved AS pd ON p.id = pd.recieveble_id where pd.recieveble_id = ?
                GROUP BY p.id, p.date, p.particuler, p.amount, p.head, g.type, g.name";
                $parameters = [
                    $_POST['recieveble_id'],
                ];
                $types = "i";
                
                $result = app_exec_getresult($sql, $parameters, $types);

                if (mysqli_num_rows($result) > 0) {
                    // output data of each row
                    while($row = mysqli_fetch_assoc($result)) {
                        $total_recieveble_Amount = (int)$row["total_recieveble_Amount"];
                        $total_recieved_amount = $row["total_recieved_amount"] !== null ? (int)$row["total_recieved_amount"] : 0;
                        $isComplete = ($total_recieveble_Amount - $total_recieved_amount == 0) ? 1 : 0;
                        
                        $sql = "UPDATE tbl_other_recieveble SET iscomplete = ?  WHERE id = ?";
                       
                        $parameters = [
                            $isComplete,
                            $_POST['recieveble_id'],
                        ];
                        $types="ii";
                        
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
        // action to delete recieved amount end

        // action to add new recieved amount details and get the last inserted id start
        if($action=="save_payment"){ 

            try{

                $tm->begin();
                
                // $sql = "SELECT AUTO_INCREMENT FROM INFORMATION_SCHEMA.TABLES
                // WHERE TABLE_SCHEMA = '$db_name' AND TABLE_NAME = 'tbl_other_recieveble'";
                // $result = app_exec_query($sql);
                // $row = $result->fetch_assoc();

                // $recieveble_id = htmlspecialchars($row['AUTO_INCREMENT']);

              
                

                $rec_amt = $_POST['recieveble'];
                if (empty($rec_amt) || (float)$rec_amt == 0) {
                    $rec_amt = $_POST['recieved'];
                }
                $iscomp = ($rec_amt == $_POST['recieved']) ? 1 : 0;

                $sql = "INSERT INTO tbl_other_recieveble (date, head, particuler, amount, iscomplete,flag) VALUES (?, ?, ?, ?, ?,?)";
                $parameters = [
                    $_POST['recieveble_date'],
                    $_POST['head'],
                    $_POST['particuler'],
                    $rec_amt,
                    $iscomp,
                    $_POST['flag'],
                ];
                $types = "sisiii";
                    
                // Use custom insert function that returns insert ID
                // if (!app_exec_roll_back_nonquery($conn, $sql, $parameters, $types)) {
                //     throw new Exception("Error in Updation");
                // }
                $recieveble_id = app_exec_getlast_id_roll_back($conn, $sql, $parameters, $types);
                    

                if ($recieveble_id !== null) {
                    $sql = "INSERT INTO tbl_other_recieved (date, head, particuler, amount, recieveble_id,flag,transaction_type) VALUES (?, ?, ?, ?, ?,?,?)";
                    $parameters = [
                        $_POST['recieved_date'],
                        $_POST['head'],
                        $_POST['particuler'],
                        $_POST['recieved'],
                        $recieveble_id,
                        $_POST['flag'],
                        $_POST['transaction_type'],
                    ];
                    $types = "sisiiii";

                    // Saving Query as  text file end

                       
                    if (!app_exec_roll_back_nonquery($conn, $sql, $parameters, $types)) {
                        throw new Exception("Error in Updation");
                    }
                }

                if($_POST['transaction_type']==2){
                    // $sql = "SELECT AUTO_INCREMENT FROM INFORMATION_SCHEMA.TABLES WHERE TABLE_SCHEMA = '$db_name' AND TABLE_NAME = 'tbl_payable'";
                    // $result1 = app_exec_query($sql);
                    // $row1 = $result1->fetch_assoc();
                    // $payable_id = htmlspecialchars($row1['AUTO_INCREMENT']);


                    $sql = "INSERT INTO tbl_payable (date, head, particuler, amount, iscomplete, flag, invoice_id) VALUES (?, 2, ?, ?, 1, ?, ?)";
                    $parameters = [
                        $_POST['recieved_date'],
                        $_POST['particuler'],
                        $_POST['recieved'],
                        $_POST['flag'],
                        $recieveble_id,
                    ];
                    $types="sssii";
                    $payable_id = app_exec_getlast_id_roll_back($conn, $sql, $parameters, $types);
                    // if (!app_exec_roll_back_nonquery($conn, $sql, $parameters, $types)) {
                    //     throw new Exception("Error in Updation");
                    // }

                    $sql = "INSERT INTO tbl_paid (date, head, particuler, amount, payable_id, flag,transaction_type, invoice_id,is_member_trans) VALUES (?, 2, ?, ?, ?, ?, ?, ?, ?)";
                    $parameters = [
                        $_POST['recieved_date'],
                        $_POST['particuler'],
                        $_POST['recieved'],
                        $payable_id,
                        $_POST['flag'],
                        $_POST['transaction_type'],
                        $recieveble_id,
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
        // action to add new recieved amount details and get the last inserted id end

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

        // action to load heads for dropdown details start
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
        
                $resdata=array($qrydata);
        
                echo json_encode($resdata);
                exit();
                
            }
            catch (Throwable $e) {
                throw new Exception('Oops! Something went wrong.');
            }
        }
        // action to load heads for dropdown details end

        // action to load all recieved amount details start
        if($action=="load_data"){
            try{
                $rowsPerPage = 8;
                $current_page = (int)$_POST['page'];
                // Pagination logic
                $sql="";  
                $sqlcountrows="";
                $sqldatarows="";   
        
                $offset = ($current_page - 1) * $rowsPerPage;
                
                $sqldatarows ="SELECT p.id,p.date,p.particuler,p.amount,p.head,p.recieveble_id,g.type,g.name
                FROM tbl_other_recieved as p 
                LEFT JOIN tbl_payment_head_master AS g ON p.head = g.id order by p.date DESC";
        
                $sqlcountrows = "SELECT COUNT(*) AS total FROM tbl_other_recieved ";
                
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
        // action to load all recieved amount details end
    }
?>