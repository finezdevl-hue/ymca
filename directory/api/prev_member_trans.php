<?php
    session_start();
    include '../../app_common/db_connect.php';
    include '../../app_pagination/pagination.php';

    // actions start
    if(isset($_POST['action']) && !empty($_POST['action'])) {
        $action = $_POST['action'];

        // action to load fee details of a member starts
        if($action=="load_data"){
            try{
                $rowsPerPage = 8;
                $current_page = (int)$_POST['page'];
                // Pagination logic
                $sql="";  
                $sqlcountrows="";
                $sqldatarows="";   
        
                $offset = ($current_page - 1) * $rowsPerPage;
        
                $sqldatarows ="SELECT f.date,f.fees,f.id,g.name AS head,mr.head AS head_id,mr.discription,f.receiveble_id,f.iswallet,f.transaction_type,cl.from_year,cl.to_year FROM
                tbl_member_recieved_old AS f  LEFT JOIN tbl_member_recievable_old AS mr ON f.receiveble_id=mr.id
                LEFT JOIN tbl_payment_head_master AS g ON mr.head = g.id LEFT JOIN tbl_closing AS cl ON f.flag=cl.id
                WHERE f.member_id = " . $_SESSION['member_id'] . " AND f.fees <> 0 ORDER BY f.date DESC";
        
                $sqlcountrows = "SELECT COUNT(*) AS total
                FROM tbl_members AS m
                JOIN tbl_member_recieved_old AS f ON m.id = f.member_id
                LEFT JOIN tbl_payment_head_master AS g ON f.head = g.id
                WHERE m.id = " . $_SESSION['member_id'] . " AND f.fees <> 0";
                
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
        // action to load fee details of a member ends

       
    }
?>