<?php
    session_start();
    include '../../app_common/db_connect.php';
    include '../../app_pagination/pagination.php';

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
                if($_POST['id']==0){
                    
                    $rec_amt = $_POST['recieveble'];
                    if (empty($rec_amt) || (float)$rec_amt == 0) {
                        $rec_amt = $_POST['recieved'];
                    }
                    $iscomp = ($rec_amt == $_POST['recieved']) ? 1 : 0;

                    $sql = "INSERT INTO tbl_other_recieveble (date, head, particuler, amount, iscomplete) VALUES (?, ?, ?, ?, ?)";
                    $parameters = [
                        $_POST['recieveble_date'],
                        $_POST['head'],
                        $_POST['particuler'],
                        $rec_amt,
                        $iscomp,
                    ];
                    $types = "sisii";
                    
                    // Use custom insert function that returns insert ID
                    $recieveble_id = app_exec_getlast_id($sql, $parameters, $types);
                    

                    if ($recieveble_id !== null) {
                        $sql = "INSERT INTO tbl_other_recieved (date, head, particuler, amount, recieveble_id) VALUES (?, ?, ?, ?, ?)";
                        $parameters = [
                            $_POST['recieved_date'],
                            $_POST['head'],
                            $_POST['particuler'],
                            $_POST['recieved'],
                            $recieveble_id,
                        ];
                        $types = "sisii";

                        // Saving Query as  text file end

                       
                        app_exec_nonquery($sql, $parameters, $types);
                    }
                   
                }
                
            }
            catch (Exception $e) {
                http_response_code(500);   
                echo "Oops! Something went wrong." . $e->getMessage();
                return;
            }
        }
        // action to add new recieved amount details and get the last inserted id end

        // action to load heads for dropdown details start
        if($action=="load_heads"){
            try{
                $sql = "SELECT id, name FROM tbl_payment_head_master";
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
        if($action=="load_fees"){
            try{
               
                $sql="";  
                $sqlcountrows="";
                $sqldatarows="";   
        
                $sqldatarows ="SELECT 
                    (COALESCE((SELECT SUM(fees) FROM tbl_member_recievable), 0) + 
                     COALESCE((SELECT SUM(fees) FROM tbl_member_recievable_old), 0)) AS receivable,
                    (COALESCE((SELECT SUM(fees) FROM tbl_member_recieved), 0) + 
                     COALESCE((SELECT SUM(fees) FROM tbl_member_recieved_old), 0)) AS received";
        
                $sqlcountrows = "SELECT COUNT(*) AS total FROM tbl_member_recievable";
                
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
        
                // $pagination = array("total_rows"=>$totalRows);
                $resdata=array($qrydata);
        
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

        if($action=="load_receiveble"){
            try{
               
                $sql="";  
                $sqlcountrows="";
                $sqldatarows="";   
        
                $sqldatarows ="SELECT (SELECT SUM(amount) FROM tbl_other_recieveble) AS receivable,
                (SELECT SUM(amount) FROM tbl_other_recieved) AS received";
        
                $sqlcountrows = "SELECT COUNT(*) AS total FROM tbl_other_recieveble";
                
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
        
                // $pagination = array("total_rows"=>$totalRows);
                $resdata=array($qrydata);
        
                echo json_encode($resdata);
                exit();
            }
            catch (Exception $e) {
                http_response_code(500);   
                echo "Oops! Something went wrong." . $e->getMessage();
                return;
            }
        }

        if($action=="load_payable"){
            try{
               
                $sql="";  
                $sqlcountrows="";
                $sqldatarows="";   
        
                $sqldatarows ="SELECT (SELECT SUM(amount) FROM tbl_payable) AS payable,
                (SELECT SUM(amount) FROM tbl_paid) AS paid";
        
                $sqlcountrows = "SELECT COUNT(*) AS total FROM tbl_payable";
                
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
        
                // $pagination = array("total_rows"=>$totalRows);
                $resdata=array($qrydata);
        
                echo json_encode($resdata);
                exit();
            }
            catch (Exception $e) {
                http_response_code(500);   
                echo "Oops! Something went wrong." . $e->getMessage();
                return;
            }
        }

        if ($action == "load_heads_breakdown") {
            try {
                $sql = "SELECT id, name, type FROM tbl_payment_head_master ORDER BY type DESC, name ASC";
                $result = app_exec_query($sql);
                
                $qrydata = [];
                if ($result && $result->num_rows > 0) {
                    while ($row = $result->fetch_assoc()) {
                        $headId = $row['id'];
                        $headType = $row['type'];
                        
                        // Calculate Receivable / Payable
                        if ($headType == 'Credit') {
                            $rec_sql = "
                                SELECT 
                                    COALESCE((SELECT SUM(fees) FROM tbl_member_recievable WHERE head=? AND (cancel=0 OR cancel IS NULL)), 0) +
                                    COALESCE((SELECT SUM(fees) FROM tbl_member_recievable_old WHERE head=? AND (cancel=0 OR cancel IS NULL)), 0) +
                                    COALESCE((SELECT SUM(amount) FROM tbl_other_recieveble WHERE head=? AND (cancel=0 OR cancel IS NULL)), 0) AS total_receivable
                            ";
                            $res_rec = app_exec_getresult($rec_sql, [$headId, $headId, $headId], "iii");
                            $row_rec = $res_rec ? $res_rec->fetch_assoc() : null;
                            $target_amount = $row_rec ? (float)$row_rec['total_receivable'] : 0.00;
                            
                            $act_sql = "
                                SELECT 
                                    COALESCE((
                                        SELECT SUM(r.fees) 
                                        FROM tbl_member_recieved r 
                                        LEFT JOIN tbl_member_recievable b ON b.id = r.receiveble_id 
                                        WHERE (r.head = ? OR b.head = ?) AND (r.cancel=0 OR r.cancel IS NULL)
                                    ), 0) +
                                    COALESCE((
                                        SELECT SUM(r.fees) 
                                        FROM tbl_member_recieved_old r 
                                        LEFT JOIN tbl_member_recievable_old b ON b.id = r.receiveble_id 
                                        WHERE (r.head = ? OR b.head = ?) AND (r.cancel=0 OR r.cancel IS NULL)
                                    ), 0) +
                                    COALESCE((
                                        SELECT SUM(amount) 
                                        FROM tbl_other_recieved 
                                        WHERE head = ? AND (cancel=0 OR cancel IS NULL)
                                    ), 0) AS total_received
                            ";
                            $res_act = app_exec_getresult($act_sql, [$headId, $headId, $headId, $headId, $headId], "iiiii");
                            $row_act = $res_act ? $res_act->fetch_assoc() : null;
                            $actual_amount = $row_act ? (float)$row_act['total_received'] : 0.00;
                        } else {
                            // Debit
                            $rec_sql = "
                                SELECT 
                                    COALESCE((SELECT SUM(amount) FROM tbl_payable WHERE head=? AND (cancel=0 OR cancel IS NULL)), 0) AS total_payable
                            ";
                            $res_rec = app_exec_getresult($rec_sql, [$headId], "i");
                            $row_rec = $res_rec ? $res_rec->fetch_assoc() : null;
                            $target_amount = $row_rec ? (float)$row_rec['total_payable'] : 0.00;
                            
                            $act_sql = "
                                SELECT 
                                    COALESCE((SELECT SUM(amount) FROM tbl_paid WHERE head=? AND (cancel=0 OR cancel IS NULL)), 0) AS total_paid
                            ";
                            $res_act = app_exec_getresult($act_sql, [$headId], "i");
                            $row_act = $res_act ? $res_act->fetch_assoc() : null;
                            $actual_amount = $row_act ? (float)$row_act['total_paid'] : 0.00;
                        }
                        
                        // We only include heads that have non-zero amounts to keep the breakdown list focused
                        if ($target_amount > 0 || $actual_amount > 0) {
                            $qrydata[] = [
                                'id' => $headId,
                                'name' => $row['name'],
                                'type' => $headType,
                                'target' => $target_amount,
                                'actual' => $actual_amount,
                                'balance' => $target_amount - $actual_amount
                            ];
                        }
                    }
                }
                
                echo json_encode([$qrydata]);
                exit();
            } catch (Exception $e) {
                http_response_code(500);
                echo json_encode(['Message' => $e->getMessage()]);
                exit();
            }
        }
    }
?>