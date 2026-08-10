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

        // action to copy all the payment tables start
        if($action=="close_account"){
            $now  = new DateTime('now', new DateTimeZone('Asia/Kolkata'));   // your TZ
            $yr   = (int)$now->format('Y');
            $mon  = (int)$now->format('n');

            $flag = ($yr - 1) . '-' . $yr;
            try{
                $is_complete= 1;
                $tm->begin();
                $sql="";
                $sqldatarows="";
               
                $sql ="INSERT INTO tbl_member_recieved_old (id, member_id, fees, date, login_id, head, discription, flag,receiveble_id)
                SELECT m.id, m.member_id, m.fees, m.date, m.login_id, m.head, m.discription, ?, m.receiveble_id
                FROM tbl_member_recieved AS m JOIN tbl_other_recieveble AS gmm ON m.receiveble_id = gmm.id
                WHERE gmm.iscomplete = ?";
                $parameters = [
                    $flag,
                    $is_complete,
                ];
                $types="si";
                if (!app_exec_roll_back_nonquery($conn, $sql, $parameters, $types)) {
                    throw new Exception("Error in Saving Data");
                }

                $sql ="INSERT INTO tbl_member_recievable_old (id, member_id, fees, date, login_id, head, discription, flag,iscomplete)
                SELECT id, member_id, fees, date, login_id, head, discription, ? FROM tbl_member_recievable WHERE iscomplete = ?";
                $parameters = [
                    $flag,
                    $is_complete,
                ];
                $types="si";
                if (!app_exec_roll_back_nonquery($conn, $sql, $parameters, $types)) {
                    throw new Exception("Error in Saving Data");
                }

                $sql ="INSERT INTO tbl_other_recieveble_old (id, date, head,particuler,amount,iscomplete,flag)
                SELECT id, date, head, particuler, amount, iscomplete,? FROM tbl_other_recieveble WHERE iscomplete = ?";
                $parameters = [
                    $flag,
                    $is_complete,
                ];
                $types="si";
                if (!app_exec_roll_back_nonquery($conn, $sql, $parameters, $types)) {
                    throw new Exception("Error in Saving Data");
                }

                $sql ="INSERT INTO tbl_other_recieved_old (id,date,head,particuler,amount,recieveble_id,flag)
                SELECT m.id, m.date, m.head, m.particuler, m.amount, m.recieveble_id,?
                FROM tbl_other_recieved AS m JOIN tbl_other_recieveble AS gmm ON m.recieveble_id = gmm.id
                WHERE gmm.iscomplete = ?";
                $parameters = [
                    $flag,
                    $is_complete,
                ];
                $types="si";
                if (!app_exec_roll_back_nonquery($conn, $sql, $parameters, $types)) {
                    throw new Exception("Error in Saving Data");
                }

                $sql ="INSERT INTO tbl_paid_old (id,date,head,particuler,amount,payable_id,flag)
                SELECT m.id, m.date, m.head, m.particuler, m.amount, m.payable_id, ?
                FROM tbl_paid AS m JOIN tbl_payable AS gmm ON m.payable_id = gmm.id
                WHERE gmm.iscomplete = ?";
                $parameters = [
                    $flag,
                    $is_complete,
                ];
                $types="si";
                if (!app_exec_roll_back_nonquery($conn, $sql, $parameters, $types)) {
                    throw new Exception("Error in Saving Data");
                }

                $sql ="INSERT INTO tbl_payable_old (id, date, head, particuler, amount, iscomplete, flag)
                SELECT id, date, head, particuler, amount, iscomplete, ? FROM tbl_payable WHERE iscomplete = ?";
                $parameters = [
                    $flag,
                    $is_complete,
                ];
                $types="si";
                if (!app_exec_roll_back_nonquery($conn, $sql, $parameters, $types)) {
                    throw new Exception("Error in Saving Data");
                }

                $tm->commit();
                echo 'Saved Old Data Sucessfully';
            }
            catch (Exception $e) {
                $tm->rollback();
                echo $e->getMessage();
            }
        }
        // action to copy all the payment tables end

    
        // action to reset the tables start
        if($action=="reset_tables"){ 
            try{
                $is_complete= 1;
                $tm->begin();

                $sql = "DELETE tbl_paid
                FROM tbl_paid
                JOIN tbl_payable ON tbl_paid.payable_id = tbl_payable.id
                WHERE tbl_payable.iscomplete = ?";
                $parameters = [
                   $is_complete,
                ];
                $types="i";
                if (!app_exec_roll_back_nonquery($conn, $sql, $parameters, $types)) {
                    throw new Exception("Error in delete tbl_paid");
                }

                $sql = "DELETE FROM tbl_payable
                WHERE iscomplete = ?";
                $parameters = [
                   $is_complete,
                ];
                $types="i";
                if (!app_exec_roll_back_nonquery($conn, $sql, $parameters, $types)) {
                    throw new Exception("Error in delete tbl_payable");
                }


                $sql = "DELETE tbl_other_recieved
                FROM tbl_other_recieved
                JOIN tbl_other_recieveble ON tbl_other_recieved.recieveble_id = tbl_other_recieveble.id
                WHERE tbl_other_recieveble.iscomplete = ?";
                $parameters = [
                   $is_complete,
                ];
                $types="i";

                if (!app_exec_roll_back_nonquery($conn, $sql, $parameters, $types)) {
                    throw new Exception("Error in tbl_other_recieved Updation");
                }

                $sql = "DELETE FROM tbl_other_recieveble
                WHERE iscomplete = ?";
                $parameters = [
                   $is_complete,
                ];
                $types="i";

                if (!app_exec_roll_back_nonquery($conn, $sql, $parameters, $types)) {
                    throw new Exception("Error in tbl_other_recieveble Updation");
                }

                $sql = "DELETE FROM tbl_member_recievable WHERE iscomplete = ?";
                // app_exec_query($sql);
                 $parameters = [
                   $is_complete,
                ];
                $types="i";
                if (!app_exec_roll_back_nonquery($conn, $sql, $parameters, $types)) {
                    throw new Exception("Error in tbl_member_recievable Updation");
                }

                $sql = "DELETE tbl_member_recieved
                FROM tbl_member_recieved
                JOIN tbl_member_recievable ON tbl_member_recieved.receiveble_id = tbl_member_recievable.id
                WHERE tbl_member_recievable.iscomplete = ?";
                $parameters = [
                   $is_complete,
                ];
                $types="i";

                if (!app_exec_roll_back_nonquery($conn, $sql, $parameters, $types)) {
                    throw new Exception("Error in tbl_member_recieved Updation");
                }

            
                $tm->commit();
                // resetMemberFees();
                echo 'Updated Sucessfully';
            }
            catch (Exception $e) {
                $tm->rollback();
                echo $e->getMessage();
            }
        }
        // action to reset the tables end

        // action to reset the tables tbl_member_received and tbl_member_receiveble start
        // if($action=="save_memeber_fees"){
        function resetMemberFees(){
            try{
                $sql = "DELETE FROM tbl_member_recievable WHERE iscomplete=1";
                // app_exec_query($sql);
                $parameters = [];
                $types="";
                app_exec_roll_back_nonquery($conn, $sql, $parameters, $types);

                date_default_timezone_set('Asia/Kolkata');
                $date = date('Y-m-d');
                $tm->begin();

                // insertion into tbl_member_recieveble start
                $sql = "SELECT DISTINCT member_id, SUM(fees) AS total_fee FROM tbl_member_recievable_old WHERE flag = ? GROUP BY member_id";
                $parameters = [
                    $flag,
                ];
                $types="s";
                 
                $result = app_exec_roll_back_nonquery($conn, $sql, $parameters, $types);
                // echo $sql;

                if (mysqli_num_rows($result) > 0) {
                // output data of each row
                    while($row = mysqli_fetch_assoc($result)) {
                        $member_id = $row["member_id"];
                        $total_fee = $row["total_fee"];
                
                        $sql = "INSERT INTO tbl_member_recievable (member_id, fees,date, login_id, head) VALUES (?, ?, ?, ?, 6)";
                        $parameters = [
                            $member_id,
                            $total_fee,
                            $date,
                            $_SESSION['login_id'],
                            
                        ];
                
                        $types="iisi";
                
                        if (!app_exec_roll_back_nonquery($conn, $sql, $parameters, $types)) {
                            throw new Exception("Error in Updation");
                        }
                        
                    }
                } 
                // insertion into tbl_member_recieveble end

                $sql = "DELETE FROM tbl_member_recieved";
                app_exec_query($sql);

                // insertion into tbl_member_received start
                $sql = "SELECT DISTINCT member_id, SUM(fees) AS total_fee FROM tbl_member_recieved_old WHERE flag = ? GROUP BY member_id";
                $parameters = [
                    $flag,
                ];
                $types="s";
                $result = app_exec_roll_back_nonquery($conn, $sql, $parameters, $types);
                // echo $sql;
                if (mysqli_num_rows($result) > 0) {
                // output data of each row
                    while($row = mysqli_fetch_assoc($result)) {
                        $member_id = $row["member_id"];
                        $total_fee = $row["total_fee"];
                
                        $sql = "INSERT INTO tbl_other_recieved (member_id, fees,date, login_id, head) VALUES (?, ?, ?, ?, 6)";
                        $parameters = [
                            $member_id,
                            $total_fee,
                            $date,
                            $_SESSION['login_id'],
                        ];
                
                        $types="iisi";
                
                        if (!app_exec_roll_back_nonquery($conn, $sql, $parameters, $types)) {
                            throw new Exception("Error in Updation");
                        }
                        
                    }
                } 
                // Insert into tbl_member_recieved end

                $tm->commit();
                echo 'Updated Sucessfully';
            }
            catch (Exception $e) {
                $tm->rollback();
                echo $e->getMessage();
            }
        }
        // action to reset the table tbl_member_received and tbl_member_receiveble end

        // action to fetch the last closed years start
        if($action=="load_closed_date"){
        // function resetPayments(){
            try{
                $sql="";  
                $sqldatarows=""; 
                
                    $sqldatarows ="SELECT DISTINCT flag FROM tbl_member_recieved_old";
                    $result = app_exec_query($sqldatarows);
                
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
        // action to fetch the last closed years end
    }
    // action end
?>