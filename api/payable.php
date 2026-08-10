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

        // action to save paymet details start
        if($action=="save_payment"){ 

            $current_payment = $_POST['amount'];
            try{
               $tm->begin();
                $sql = "INSERT INTO tbl_paid (date, head, particuler, amount, payable_id) VALUES (?, ?, ?, ?, ?)";
                $parameters = [
                    $_POST['date'],
                    $_POST['head'],
                    $_POST['particuler'],
                    $_POST['amount'],
                    $_POST['id'],
                ];
                $types = "sisii";
               
                app_exec_roll_back_nonquery($conn, $sql, $parameters, $types);

                // query to select total payable and total paid amount start
                $sql = "SELECT p.amount AS total_payable_amount,
                SUM(pd.amount) AS total_paid_amount
                FROM tbl_payable AS p
                LEFT JOIN tbl_payment_head_master AS g ON p.head = g.id
                LEFT JOIN tbl_paid AS pd ON p.id = pd.payable_id where pd.payable_id = ?
                GROUP BY p.id, p.date, p.particuler, p.amount, p.head, g.type, g.name ";
                $parameters = [
                    $_POST['id'],
                ];
                $types = "i";
                
                $result = app_exec_getresult($sql, $parameters, $types);
                // Query to select total payable and total paid amount end

                // update query to using the result of another query start
                if (mysqli_num_rows($result) > 0) {
                    // output data of each row
                    while($row = mysqli_fetch_assoc($result)) {
                        $total_payable_amount = (int)$row["total_payable_amount"];
                        $total_paid_amount = $row["total_paid_amount"] !== null ? (int)$row["total_paid_amount"] : 0;
                        $isComplete = ($total_payable_amount - $total_paid_amount == 0) ? 1 : 0;
                        $sql = "UPDATE tbl_payable SET iscomplete = ? WHERE id = ?";
                        
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
                // update query to using the result of another query end
                $tm->commit();
                echo 'Updated Sucessfully';
            }
            catch (Exception $e) {
                $tm->rollback();
                echo $e->getMessage();
            }
        }
        // action to save payment details ends

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
        // action to load all payable amount and paid amount details start
        if($action=="load_data"){
            try{
                $rowsPerPage = 8;
                $current_page = (int)$_POST['page'];
                // Pagination logic
                $sql="";  
                $sqlcountrows="";
                $sqldatarows="";   
        
                $offset = ($current_page - 1) * $rowsPerPage;
                
                $sqldatarows ="SELECT p.id AS payable_id, p.date, p.particuler, p.amount AS payable_amount,
                p.head, p.iscomplete, g.type, g.name, SUM(pd.amount) AS total_paid_amount
                FROM tbl_payable AS p
                LEFT JOIN tbl_payment_head_master AS g ON p.head = g.id
                LEFT JOIN tbl_paid AS pd ON p.id = pd.payable_id
                GROUP BY p.id, p.date, p.particuler, p.amount, p.head, g.type, g.name
                ORDER BY p.date DESC";
        
                $sqlcountrows = "SELECT COUNT(*) AS total FROM tbl_payable ";
                
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
        // action to load all payable amount and paid amount details end
    }
?>