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
        
                $sqldatarows ="SELECT m.first_name,m.middle_name,m.last_name,w.date,w.amount,w.type FROM tbl_wallet AS W LEFT JOIN tbl_members AS m ON w.client_id=m.id ORDER BY w.id DESC";
        
                $sqlcountrows = "SELECT COUNT(*) AS total FROM tbl_wallet";
                
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
        // action to load all payment details ends

        // action to load  heads for dropdown in the popup start
        if($action=="load_wallet_amount"){
            try{
                $sql = "SELECT 
                    COALESCE(
                        SUM(CASE WHEN type = 'credit' THEN amount ELSE 0 END) -
                        SUM(CASE WHEN type = 'debit' THEN amount ELSE 0 END)
                    , 0) AS wallet_balance FROM tbl_wallet";

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
        // action to load heads for dropdown in the popup end

    }
?>
