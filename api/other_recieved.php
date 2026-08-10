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
                    
                   if($_POST['recieveble']==$_POST['recieved']){
                        $sql = "INSERT INTO tbl_other_recieveble (date, head, particuler, amount, iscomplete) VALUES (?, ?, ?, ?, 1)";
                   }
                   else{
                    $sql = "INSERT INTO tbl_other_recieveble (date, head, particuler, amount, iscomplete) VALUES (?, ?, ?, ?, 0)";
                   }
                    $parameters = [
                        $_POST['recieveble_date'],
                        $_POST['head'],
                        $_POST['particuler'],
                        $_POST['recieveble'],
                    ];
                    $types = "sisi";
                    
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