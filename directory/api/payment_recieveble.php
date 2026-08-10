<?php
    session_start();
    include '../../app_common/db_connect.php';
    include '../../app_pagination/pagination.php';

    // actions start
    if(isset($_POST['action']) && !empty($_POST['action'])) {
        $action = $_POST['action'];

        // action to add new recieveble payment details start
        if($action=="save_payment"){ 
            try{

                if($_POST['id']==0){

                    $sql ="INSERT INTO tbl_recieveble (date, head, particuler, amount) VALUES (?, ?, ?, ?)";
                    
                    $parameters = [
                    $_POST['date'],
                    $_POST['head'],
                    $_POST['particuler'],
                    $_POST['recieveble'],
                    ];
                    $types="sisi";
                    app_exec_nonquery($sql, $parameters, $types);

                    $sql ="INSERT INTO tbl_recieved (date, head, particuler, amount) VALUES (?, ?, ?, ?)";
                    
                    $parameters = [
                    $_POST['date'],
                    $_POST['head'],
                    $_POST['particuler'],
                    $_POST['recieved'],
                    ];

                    $types="sisi";
                }
                
                app_exec_nonquery($sql, $parameters, $types);
            }
           
            catch (Exception $e) {
                http_response_code(500);   
                echo "Oops! Something went wrong." . $e->getMessage();
                return;
            }
        }
        // action to add new recieveble payment details end

        // action to load  heads with type credit for dropdown in the popup start
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
        // action to load heads with type credit for dropdown in the popup end

        // action to load all recieveble payment details start
        if($action=="load_data"){
            try{
                $rowsPerPage = 8;
                $current_page = (int)$_POST['page'];
                // Pagination logic
                $sql="";  
                $sqlcountrows="";
                $sqldatarows="";   
        
                $offset = ($current_page - 1) * $rowsPerPage;
                
                $sqldatarows ="SELECT p.id,p.date,p.particuler,p.amount,p.head,g.type,g.name
                FROM tbl_recieved as p JOIN tbl_payment_head_master AS g ON p.head = g.id WHERE p.head = g.id  order by p.date DESC";
        
                $sqlcountrows = "SELECT COUNT(*) AS total FROM tbl_recieved ";
                
                
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
            // catch (Throwable $e) {
            //     throw new Exception('Oops! Something went wrong.');
            // }
            catch (Exception $e) {
                http_response_code(500);   
                echo "Oops! Something went wrong." . $e->getMessage();
                return;
            }
        }
        // action to load all recieveblepayment details end
    }
?>