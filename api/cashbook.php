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

        // action to load payment details start
        if($action=="load_payment"){
            try{
                $sql="";  
                $sqldatarows=""; 
                if($_POST['from_date']== '' && $_POST['to_date']== ''){
                    $sqldatarows ="SELECT SUM(pd.amount) AS paid_amount,sum(p.amount)as payable_amount
                    FROM tbl_paid as pd JOIN tbl_payable AS p ON pd.payable_id = p.id";
                
                    $result = app_exec_query($sqldatarows);
                }
                
                else{
                    $sqldatarows="SELECT SUM(pd.amount) AS paid_amount,SUM(p.amount) AS payable_amount FROM 
                    tbl_paid AS pd JOIN tbl_payable AS p ON pd.payable_id = p.id
                    WHERE  p.date BETWEEN ? AND ? AND pd.date BETWEEN ? AND ? ";
                    $parameters = [
                        $_POST['from_date'],
                        $_POST['to_date'],
                        $_POST['from_date'],
                        $_POST['to_date'],
                        ];

                        $types="ssss";

                    $result = app_exec_getresult($sqldatarows, $parameters, $types);
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
        // action to load payment details end

        // action to load fees details start
        if($action=="load_fees"){
            try{
                $sql="";  
                $sqldatarows=""; 
                if($_POST['from_date']== '' && $_POST['to_date']== ''){
                    $sqldatarows ="SELECT 
                    (SELECT SUM(fees) FROM tbl_member_recieved) AS received_amount,
                    (SELECT SUM(fees) FROM tbl_member_recievable) AS receivable_amount";
                    $result = app_exec_query($sqldatarows);
                }
                else{
                    $sqldatarows ="SELECT 
                    (SELECT SUM(fees) FROM tbl_member_recieved WHERE date BETWEEN ? AND ?) AS received_amount,
                    (SELECT SUM(fees) FROM tbl_member_recievable WHERE date BETWEEN ? AND ?) AS receivable_amount";

                    $parameters = [
                        $_POST['from_date'],
                        $_POST['to_date'],
                        $_POST['from_date'],
                        $_POST['to_date'],
                        ];

                        $types="ssss";

                    $result = app_exec_getresult($sqldatarows, $parameters, $types);
                    
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
        // action to load fees details end

        // action to load balance or cash in hand start
        if($action=="load_balance"){
            try{
                $sql="";  
                $sqldatarows=""; 
                if($_POST['from_date']== '' && $_POST['to_date']== ''){
                    $sqldatarows ="SELECT
                    (SELECT SUM(amount) FROM tbl_opening_balance) AS Opening_balance,
                    (SELECT SUM(amount) FROM tbl_other_recieved) AS other_recieved,
                    (SELECT SUM(fees) FROM tbl_member_recieved) AS received_amount,
                    (SELECT SUM(amount) FROM tbl_paid)  AS paid_amount";
                    $result = app_exec_query($sqldatarows);
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
        // action to load balance or cash in hand end
    }
    // action end
?>