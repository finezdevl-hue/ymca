<?php
    session_start();
    include '../../app_common/db_connect.php';
    // include '../../app_common/save_query.php';
    include '../../app_pagination/pagination.php';

    $member_id = isset($_SESSION['member_id']) ? (int)$_SESSION['member_id'] : 0;

    // actions start
    if(isset($_POST['action']) && !empty($_POST['action'])) {
        $action = $_POST['action'];

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

                $sql = "INSERT INTO tbl_member_recieved (member_id, fees, date, login_id, head, discription, receiveble_id, iswallet) VALUES (?, ?, ?, ?, ?, ?,?,0)";
                $parameters = [
                    $_POST['member_id'],
                    $amount_to_pay,
                    $_POST['date'],
                    $_SESSION['login_id'],
                    $_POST['head'],
                    $_POST['discription'],
                    $_POST['id'],
                ];
                $types = "iisiisi";
                
                app_exec_roll_back_nonquery($conn, $sql, $parameters, $types);

                if ($amount_to_wallet > 0) {
                    $sql_wallet = "INSERT INTO tbl_wallet (client_id, date, amount, type) VALUES (?, ?, ?, 'credit')";
                    $params_wallet = [
                        $_POST['member_id'],
                        $_POST['date'],
                        $amount_to_wallet
                    ];
                    $types_wallet = "isd";
                    if (!app_exec_roll_back_nonquery($conn, $sql_wallet, $params_wallet, $types_wallet)) {
                        throw new Exception("Error in Wallet Topup");
                    }
                }

                // Update completeness
                $isComplete = ($total_recieveble_Amount - ($total_recieved_amount + $amount_to_pay) == 0) ? 1 : 0;
                $sql = "UPDATE tbl_member_recievable SET iscomplete = ? WHERE id = ?";
                $parameters = [$isComplete, $_POST['id']];
                $types = "ii";
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
                
                $sqldatarows ="SELECT p.id AS recieveble_id,p.member_id,p.login_id,p.member_id,p.date,p.fees AS receiveble_fees,p.login_id,
                p.head,p.discription,p.iscomplete,g.type,g.name,SUM(pd.fees) AS total_received_fees FROM tbl_member_recievable AS p 
                LEFT JOIN tbl_payment_head_master AS g ON p.head=g.id LEFT JOIN tbl_member_recieved AS pd ON p.id=pd.receiveble_id WHERE p.member_id= ".$member_id."
                GROUP BY p.id,p.date,p.discription,p.fees,p.head,g.type,g.name ORDER BY p.iscomplete, p.date DESC";
        
                $sqlcountrows = "SELECT COUNT(*) AS total FROM tbl_member_recievable WHERE member_id=".$member_id;
                
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

         // action to load wallet balance amount start
         // action to load wallet balance amount start
        if($action=="load_wallet_balance"){
            try{
                // echo $_POST['id'];
                $sql = "SELECT client_id, SUM(CASE WHEN type = 'credit' THEN amount ELSE 0 END) -
                SUM(CASE WHEN type = 'debit' THEN amount ELSE 0 END) AS wallet_balance
                FROM tbl_wallet WHERE client_id =? GROUP BY client_id";

                $parameters = [
                    $_POST['id'],
                ];
                $types = "i";
                $result=app_exec_getresult($sql,$parameters,$types);
                // $result=app_exec_query($sql);
                


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
            catch (Exception $e) {
                http_response_code(500);   
                echo "Oops! Something went wrong." . $e->getMessage();
                return;
            }
        }
        // action to load wallet balance amount end
        
         if($action=="save_payment_from_wallet"){ 
             $current_payment = $_POST['amount'];
            try{
                $tm->begin();
                $sql = "INSERT INTO tbl_member_recieved (member_id, fees, date, login_id, head, discription, receiveble_id, iswallet) VALUES (?, ?, ?, ?, ?, ?,?,1)";
                $parameters = [
                    $_POST['member_id'],
                    $_POST['amount'],
                    $_POST['date'],
                    $_SESSION['login_id'],
                    $_POST['head'],
                    $_POST['discription'],
                    $_POST['id'],
                ];
                $types = "iisiisi";
                
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
    }
?>