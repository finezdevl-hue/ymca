<?php
session_start();
require_once('../../invoice/tcpdf/tcpdf.php');
include '../../app_common/db_connect.php';
include '../../app_common/db_name.php';
include '../../app_common/enums.php';
// include '../../invoice/pdf.php';
include '../../app_pagination/pagination.php';

    

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
                    $sqldatarows = "SELECT 
                    m.id,
                    m.first_name,
                    m.middle_name,
                    m.last_name,
                    m.img,
                    m.member_type,

                    -- Wallet amount (calculated as subquery to avoid multiplication with receivables)
                    (SELECT COALESCE(SUM(CASE 
                        WHEN type = 'credit' THEN amount
                        WHEN type = 'debit' THEN -amount
                        ELSE 0 
                    END), 0) FROM tbl_wallet WHERE client_id = m.id) AS wallet_amount,

                    -- Count incomplete
                    COALESCE(SUM(CASE WHEN mr.iscomplete = 0 THEN 1 ELSE 0 END), 0) AS total_incomplete,

                    -- Pending balance = total receivable - total received
                    (
                        COALESCE((SELECT SUM(fees) 
                                FROM tbl_member_recievable r 
                                WHERE r.member_id = m.id), 0)
                        -
                        COALESCE((SELECT SUM(fees) 
                                FROM tbl_member_recieved rd 
                                WHERE rd.member_id = m.id), 0)
                    ) AS pending_balance

                FROM tbl_members AS m
                LEFT JOIN tbl_member_recievable AS mr ON m.id = mr.member_id
                GROUP BY 
                    m.id, m.first_name, m.middle_name, m.last_name, m.img, m.member_type
                HAVING total_incomplete > 0
                ORDER BY m.id";
                     
                    $sqlcountrows = "SELECT COUNT(*) AS total
                    FROM (
                        SELECT 
                            m.id
                        FROM tbl_members AS m
                        LEFT JOIN tbl_member_recievable AS mr ON m.id = mr.member_id
                        GROUP BY 
                            m.id, m.first_name, m.middle_name, m.last_name, m.img, m.member_type
                        HAVING COALESCE(SUM(CASE WHEN mr.iscomplete = 0 THEN 1 ELSE 0 END), 0) > 0
                    ) AS x
                    ";
                }
                else{            

                    $sqldatarows = "SELECT 
                        m.id,
                        m.first_name,
                        m.middle_name,
                        m.last_name,
                        m.img,
                        m.member_type,
                        (SELECT COALESCE(SUM(CASE 
                            WHEN type = 'credit' THEN amount
                            WHEN type = 'debit' THEN -amount
                            ELSE 0
                        END), 0) FROM tbl_wallet WHERE client_id = m.id) AS wallet_amount,
                         (
                        COALESCE((SELECT SUM(fees) 
                                FROM tbl_member_recievable r 
                                WHERE r.member_id = m.id), 0)
                        -
                        COALESCE((SELECT SUM(fees) 
                                FROM tbl_member_recieved rd 
                                WHERE rd.member_id = m.id), 0)
                    ) AS pending_balance

                    FROM tbl_members AS m
                    WHERE m.first_name LIKE ? OR m.middle_name LIKE ? OR m.last_name like ?
                    GROUP BY 
                        m.id, 
                        m.first_name, 
                        m.middle_name, 
                        m.last_name, 
                        m.img,
                        m.member_type
                    ORDER BY m.id "; 

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
        // if($action=="save_fees"){ 
        //     try{
        //         $tm->begin();
        //            if($_POST['recieveble']==$_POST['recieved']){
        //                 $sql = "INSERT INTO tbl_member_recievable (member_id, fees, date, login_id, head, discription, iscomplete) VALUES (?, ?, ?, ?, ?, ?, 1)";
        //            }
        //            else{
        //             $sql = "INSERT INTO tbl_member_recievable (date, head, particuler, amount, iscomplete) VALUES (?, ?, ?, ?, ?, ?, 0)";
        //            }
        //             $parameters = [
        //                 $_POST['member_id'],
        //             $_POST['fees'],
        //             $_POST['date'],
        //             $_SESSION['login_id'],
        //             $_POST['head'],
        //             $_POST['discription'],
        //             ];
        //             $types="iisiss";
                    
        //             // Use custom insert function that returns insert ID
        //             $recieveble_id = app_exec_getlast_id_roll_back($rollback_conn, $sql, $parameters, $types);
                    

        //             if ($recieveble_id !== null) {
        //                 $sql = "INSERT INTO tbl_member_recieved (member_id, fees, date, login_id, head, discription, recieveble_ids) VALUES (?, ?, ?, ?, ?, ?, ?)";
        //                 $parameters = [
        //                     $_POST['member_id'],
        //                     $_POST['fees'],
        //                     $_POST['date'],
        //                     $_SESSION['login_id'],
        //                     $_POST['head'],
        //                     $_POST['discription'],
        //                     $recieveble_id,
        //                 ];
        //                 $types="iisissi";

        //                 // Saving Query as  text file end

        //                 if (!app_exec_roll_back_nonquery($rollback_conn, $sql, $parameters, $types)) {
        //                     throw new Exception("Error in Updation");
        //                 }
        //                 // app_exec_nonquery($sql, $parameters, $types);
        //             }
                   
        //             $tm->commit();
        //             echo 'Deleted Sucessfully';
        //         // }
        //         // downloadInvoice($html);
        //     }

        //     catch (Exception $e) {
        //         $tm->rollback();
        //         echo $e->getMessage();
        //     }
        // }

        // action to save recieveble fee of a member start
        if($action=="save_recieveble"){ 
            try{
                $tm->begin();

                $amount_to_receive = (double)$_POST['receiveble'];
                $amount_received = (double)$_POST['received'];
                
                $amount_to_wallet = 0.0;
                $amount_to_pay = $amount_received;
                
                if ($amount_received > $amount_to_receive) {
                    $amount_to_wallet = $amount_received - $amount_to_receive;
                    $amount_to_pay = $amount_to_receive;
                }
                
                $isComplete = ($amount_received >= $amount_to_receive) ? 1 : 0;

                $sql = "INSERT INTO tbl_member_recievable (member_id, fees, date, login_id, head, discription, iscomplete,flag) VALUES (?, ?, ?, ?, ?, ?, ?, ?)";
                $parameters = [
                    $_POST['member_id'],
                    $_POST['receiveble'],
                    $_POST['receiveble_date'],
                    $_SESSION['login_id'],
                    $_POST['head'],
                    $_POST['discription'],
                    $isComplete,
                    $_POST['flag'],
                ];
                $types="iisissii";
                    
                // Use custom insert function that returns insert ID
                $recieveble_id = app_exec_getlast_id_roll_back($conn, $sql, $parameters, $types);

                if ($recieveble_id !== null) {
                    $sql = "INSERT INTO tbl_member_recieved (member_id, fees, date, login_id, head, discription, receiveble_id, iswallet,flag,transaction_type) VALUES (?, ?, ?, ?, ?, ?, ?,0,?,?)";
                    $parameters = [
                        $_POST['member_id'],
                        $amount_to_pay,
                        $_POST['received_date'],
                        $_SESSION['login_id'],
                        $_POST['head'],
                        $_POST['discription'],
                        $recieveble_id,
                        $_POST['flag'],
                        $_POST['transaction_type'],
                    ];
                    $types="iisissiii";

                    if (!app_exec_roll_back_nonquery($conn, $sql, $parameters, $types)) {
                        throw new Exception("Error in Updation");
                    }

                    if ($amount_to_wallet > 0) {
                        $sql_wallet = "INSERT INTO tbl_wallet (client_id, date, amount, type) VALUES (?, ?, ?, 'credit')";
                        $params_wallet = [
                            $_POST['member_id'],
                            $_POST['received_date'],
                            $amount_to_wallet
                        ];
                        $types_wallet = "isd";
                        if (!app_exec_roll_back_nonquery($conn, $sql_wallet, $params_wallet, $types_wallet)) {
                            throw new Exception("Error in Wallet Topup");
                        }
                    }

                    if($_POST['transaction_type']==2){
                        $sql = "INSERT INTO tbl_payable (date, head, particuler, amount, iscomplete, flag, invoice_id, is_member_trans) VALUES (?, 2, ?, ?, 1, ?, ?, ?)";
                        $parameters = [
                            $_POST['received_date'],
                            $_POST['discription'],
                            $amount_to_pay,
                            $_POST['flag'],
                            $recieveble_id,
                            MemberTransaction::member,
                        ];
                        $types="sssiii";
                        $payable_id = app_exec_getlast_id_roll_back($conn, $sql, $parameters, $types);

                        $sql = "INSERT INTO tbl_paid (date, head, particuler, amount, payable_id, flag,transaction_type, invoice_id, is_member_trans) VALUES (?, 2, ?, ?, ?, ?, ?, ?, ?)";
                        $parameters = [
                            $_POST['received_date'],
                            $_POST['discription'],
                            $amount_to_pay,
                            $payable_id,
                            $_POST['flag'],
                            $_POST['transaction_type'],
                            $recieveble_id,
                            MemberTransaction::member,
                        ];
                        $types="sssiiiii";
                        if (!app_exec_roll_back_nonquery($conn, $sql, $parameters, $types)) {
                            throw new Exception("Error in Updation");
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
                        $sql = "INSERT INTO tbl_member_recieved (member_id, fees, date, login_id, head, discription, receiveble_id, iswallet, transaction_type) VALUES (?, ?, ?, ?, ?, ?,?,1,?)";
                        $parameters = [
                            $_POST['member_id'],
                            $_POST['received'],
                            $_POST['received_date'],
                            $_SESSION['login_id'],
                            $_POST['head'],
                            $_POST['discription'],
                            $recieveble_id,
                            $_POST['transaction_type'],
                        ];
                        $types="iisissii";

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