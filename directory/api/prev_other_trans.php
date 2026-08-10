<?php
    session_start();
    include '../../app_common/db_connect.php';
    include '../../app_pagination/pagination.php';
    include '../../app_common/db_name.php';
    include '../../app_common/enums.php';

    // actions start
    if(isset($_POST['action']) && !empty($_POST['action'])) {
        $action = $_POST['action'];

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
                
                $sqldatarows ="SELECT p.id,p.date,p.particuler,p.amount,p.head,p.recieveble_id,g.type,g.name,cl.from_year,cl.to_year
                FROM tbl_other_recieved_old as p LEFT JOIN tbl_closing AS cl ON p.flag=cl.id
                LEFT JOIN tbl_payment_head_master AS g ON p.head = g.id order by p.date DESC";
        
                $sqlcountrows = "SELECT COUNT(*) AS total FROM tbl_other_recieved_old ";
                
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