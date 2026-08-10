<?php
session_start();
require_once('../../invoice/tcpdf/tcpdf.php');
include_once __DIR__ . '/../app_common/db_connect.php';
include_once __DIR__ . '/../app_common/auth_helper.php';
include_once __DIR__ . '/../app_pagination/pagination.php';

$current_login = (int)($_SESSION['login_id'] ?? 0);
if (empty($current_login) || isNormalMember($current_login)) {
    http_response_code(403);
    echo json_encode(['status' => 'error', 'message' => 'Unauthorized access.']);
    exit();
}

    // action start
    if(isset($_POST['action']) && !empty($_POST['action'])) {
        $action = $_POST['action'];

        // action to load members for pay fees start
        if($action=="load_members_data"){
            try{
                $rowsPerPage = 8;

                $current_page = (int)$_POST['page'];

                // Pagination logic
                $sql="";  
                $sqlcountrows="";
                $sqldatarows="";   
                
                $offset = ($current_page - 1) * $rowsPerPage;
                
                if($_POST['val']== ''){
                    $sqldatarows = "SELECT  id,first_name,last_name,middle_name,father_name,mother_name,dob,
                    blood_group,phone,whtsapp,email,p_street,p_city,p_pincode,p_country,img FROM tbl_members";
                     
                    $sqlcountrows = "SELECT  Count(id) as total FROM tbl_members";
                }
                else{            

                    $sqldatarows = "SELECT  id,first_name,last_name,middle_name,father_name,mother_name,dob,
                    blood_group,phone,whtsapp,email,p_street,p_city,p_pincode,p_country,img FROM tbl_members WHERE first_name LIKE ? OR middle_name LIKE ? OR last_name like ?"; 

                    $sqlcountrows = "SELECT  Count(id) as total
                    FROM tbl_members WHERE first_name LIKE ? OR middle_name LIKE ? OR last_name like ?";         
                }    
                
                $sqldatarows .= " LIMIT $offset , $rowsPerPage ";
            
                if($_POST['val']== '') {
                    $result = app_exec_query($sqldatarows);
                    
                    $totalRowsResult = app_exec_query($sqlcountrows);
                    $totalRows = $totalRowsResult->fetch_assoc()['total'];
                }
                else{
                    $searchvalue= $_POST['val'];
                    $parameters = [
                        '%' . $searchvalue . '%',
                        '%' . $searchvalue . '%',
                        '%' . $searchvalue . '%' 
                    ];
                    
                    $types="sss";
                    $result=app_exec_getresult($sqldatarows,$parameters,$types);

                    $totalRowsResult = app_exec_getresult($sqlcountrows,$parameters,$types);
                    $totalRows = $totalRowsResult->fetch_assoc()['total'];
                }

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
            catch (Throwable $e) {
                throw new Exception('Oops! Something went wrong.');
            }
            
        }
        // action to load members for pay fees end

        // action to save fees of a member starts
        if($action=="save_fees"){ 
            try{
                
                // $id=$_POST['member_id'];
                // $fees=$_POST['fees'];
                // $date=$_POST['date'];
                // $discription=$_POST['discription'];
                
                // $sql ="INSERT INTO tbl_member_recieved (member_id, fees, date, login_id, head, discription) VALUES (?, ?, ?, ?, ?, ?)";
                    
                // $parameters = [
                //     $_POST['member_id'],
                //     $_POST['fees'],
                //     $_POST['date'],
                //     $_SESSION['login_id'],
                //     $_POST['head'],
                //     $_POST['discription'],
                // ];
    
                // $types="iisiss";
                // // saveQuery($sql);
                // app_exec_nonquery($sql, $parameters, $types);
              
                // if($_POST['id']==0){
                    
                   if($_POST['recieveble']==$_POST['recieved']){
                        $sql = "INSERT INTO tbl_member_recievable (member_id, fees, date, login_id, head, discription, iscomplete) VALUES (?, ?, ?, ?, ?, ?, 1)";
                   }
                   else{
                    $sql = "INSERT INTO tbl_member_recievable (date, head, particuler, amount, iscomplete) VALUES (?, ?, ?, ?, ?, ?, 0)";
                   }
                    $parameters = [
                        $_POST['member_id'],
                    $_POST['fees'],
                    $_POST['date'],
                    $_SESSION['login_id'],
                    $_POST['head'],
                    $_POST['discription'],
                    ];
                    $types="iisiss";
                    
                    // Use custom insert function that returns insert ID
                    $recieveble_id = app_exec_getlast_id($sql, $parameters, $types);
                    

                    if ($recieveble_id !== null) {
                        $sql = "INSERT INTO tbl_member_recieved (member_id, fees, date, login_id, head, discription, recieveble_id) VALUES (?, ?, ?, ?, ?, ?, ?)";
                        $parameters = [
                            $_POST['member_id'],
                            $_POST['fees'],
                            $_POST['date'],
                            $_SESSION['login_id'],
                            $_POST['head'],
                            $_POST['discription'],
                            $recieveble_id,
                        ];
                        $types="iisissi";

                        // Saving Query as  text file end

                       
                        app_exec_nonquery($sql, $parameters, $types);
                    }
                   
                // }
                // downloadInvoice($html);
            }

            catch (Exception $e) {
                http_response_code(500);   
                echo "Oops! Something went wrong." . $e->getMessage();
                return;
            }
        }

        // action to save recieveble fee of a member start
        if($action=="save_recieveble"){ 
            try{
                $amount_to_receive = (double)$_POST['receiveble'];
                $amount_received = (double)$_POST['received'];
                
                $amount_to_wallet = 0.0;
                $amount_to_pay = $amount_received;
                
                if ($amount_received > $amount_to_receive) {
                    $amount_to_wallet = $amount_received - $amount_to_receive;
                    $amount_to_pay = $amount_to_receive;
                }
                
                $isComplete = ($amount_received >= $amount_to_receive) ? 1 : 0;

                $sql = "INSERT INTO tbl_member_recievable (member_id, fees, date, login_id, head, discription, iscomplete) VALUES (?, ?, ?, ?, ?, ?, ?)";
                $parameters = [
                    $_POST['member_id'],
                    $_POST['receiveble'],
                    $_POST['receiveble_date'],
                    $_SESSION['login_id'],
                    $_POST['head'],
                    $_POST['discription'],
                    $isComplete,
                ];
                $types="iisissi";
                    
                // Use custom insert function that returns insert ID
                $recieveble_id = app_exec_getlast_id($sql, $parameters, $types);

                if ($recieveble_id !== null) {
                    $sql = "INSERT INTO tbl_member_recieved (member_id, fees, date, login_id, head, discription, receiveble_id, iswallet) VALUES (?, ?, ?, ?, ?, ?, ?,0)";
                    $parameters = [
                        $_POST['member_id'],
                        $amount_to_pay,
                        $_POST['received_date'],
                        $_SESSION['login_id'],
                        $_POST['head'],
                        $_POST['discription'],
                        $recieveble_id,
                    ];
                    $types="iisissi";

                    app_exec_nonquery($sql, $parameters, $types);

                    if ($amount_to_wallet > 0) {
                        $sql_wallet = "INSERT INTO tbl_wallet (client_id, date, amount, type) VALUES (?, ?, ?, 'credit')";
                        $params_wallet = [
                            $_POST['member_id'],
                            $_POST['received_date'],
                            $amount_to_wallet
                        ];
                        $types_wallet = "isd";
                        app_exec_nonquery($sql_wallet, $params_wallet, $types_wallet);
                    }
                }
                
            }
             catch (Exception $e) {
                http_response_code(500);   
                echo "Oops! Something went wrong." . $e->getMessage();
                return;
            }
    
        }
        // action to save recieveble fee of a member end

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
        // action to save recieveble fee of a member start
        if($action=="save_payment_from_wallet"){ 
            try{

                
                if($_POST['receiveble']==$_POST['received']){
                    $sql = "INSERT INTO tbl_member_recievable (member_id, fees, date, login_id, head, discription, iscomplete) VALUES (?, ?, ?, ?, ?, ?, 1)";
                }
                else{
                    $sql = "INSERT INTO tbl_member_recievable (member_id, fees, date, login_id, head, discription, iscomplete) VALUES (?, ?, ?, ?, ?, ?, 0)";
                }
                    $parameters = [
                        $_POST['member_id'],
                        $_POST['receiveble'],
                        $_POST['receiveble_date'],
                        $_SESSION['login_id'],
                        $_POST['head'],
                        $_POST['discription'],
                    ];
                    $types="iisiss";
                    
                    // Use custom insert function that returns insert ID
                    $recieveble_id = app_exec_getlast_id($sql, $parameters, $types);
                    

                    if ($recieveble_id !== null) {
                        $sql = "INSERT INTO tbl_member_recieved (member_id, fees, date, login_id, head, discription, receiveble_id, iswallet) VALUES (?, ?, ?, ?, ?, ?,?,1)";
                        $parameters = [
                            $_POST['member_id'],
                            $_POST['received'],
                            $_POST['received_date'],
                            $_SESSION['login_id'],
                            $_POST['head'],
                            $_POST['discription'],
                            $recieveble_id,
                        ];
                        $types="iisissi";

                        // Saving Query as  text file end

                       
                        app_exec_nonquery($sql, $parameters, $types);

                        $sql = "INSERT INTO tbl_wallet (client_id ,date, amount, type) VALUES (?, ?, ?, 'debit')";
                        $parameters = [
                            $_POST['member_id'],
                            $_POST['received_date'],
                            $_POST['received'],
                        ];
                        $types = "isi";
                        app_exec_nonquery($sql, $parameters, $types);
                    }
                
            }
             catch (Exception $e) {
                http_response_code(500);   
                echo "Oops! Something went wrong." . $e->getMessage();
                return;
            }
    
        }

    }
    // action end
?>