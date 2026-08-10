<?php
    session_start();
    include '../../app_common/db_connect.php';
    include '../../app_pagination/pagination.php';

    // actions start
    if(isset($_POST['action']) && !empty($_POST['action'])) {
        $action = $_POST['action'];

        // action to load all payment details  starts
        if($action=="load_data"){
            try{
                $rowsPerPage = 8;
                $current_page = (int)$_POST['page'];
                // Pagination logic
                $sql="";  
                $sqlcountrows="";
                $sqldatarows="";   
        
                $offset = ($current_page - 1) * $rowsPerPage;
        
                $sqldatarows ="SELECT m.date, m.particuler, m.amount, f.name,f.type
                FROM tbl_paid AS m LEFT JOIN tbl_payment_head_master AS f ON m.head = f.id
                UNION ALL
                SELECT r.date, r.particuler, r.amount, f.name,f.type
                FROM tbl_other_recieved AS r LEFT JOIN tbl_payment_head_master AS f ON r.head = f.id
                ORDER BY date DESC";
        
                $sqlcountrows = "SELECT COUNT(*) AS total
                FROM (SELECT m.date, m.particuler, m.amount, f.name FROM tbl_paid AS m
                LEFT JOIN tbl_payment_head_master AS f ON m.head = f.id
                UNION ALL
                SELECT r.date, r.particuler, r.amount, f.name FROM tbl_other_recieved AS r
                LEFT JOIN tbl_payment_head_master AS f ON r.head = f.id) AS combined_data";
                
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
        // action to load all payment details ends
    }
?>