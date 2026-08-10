<?php
    session_start();
    include '../../app_common/db_connect.php';
    include '../../app_pagination/pagination.php';

    // actions start
    if(isset($_POST['action']) && !empty($_POST['action'])) {
        $action = $_POST['action'];

        // action to load fees details of all members starts
        if($action=="load_data"){
            try{
                $rowsPerPage = 8;
                $current_page = (int)$_POST['page'];
                // Pagination logic
                $sql="";  
                $sqlcountrows="";
                $sqldatarows="";   
        
                $offset = ($current_page - 1) * $rowsPerPage;

                $active_year_start = '2000-04-01';
                $active_yr_res = app_exec_query("SELECT from_year FROM tbl_closing ORDER BY from_year DESC LIMIT 1");
                if ($active_yr_res && $row_yr = $active_yr_res->fetch_assoc()) {
                    $active_year_start = $row_yr['from_year'] . "-04-01";
                }
        
                $sqldatarows ="SELECT m.first_name,m.middle_name,m.last_name,f.date, SUM(f.fees) as fees, GROUP_CONCAT(DISTINCT COALESCE(g.name, 'monthly fee') SEPARATOR ', ') as head
                FROM tbl_members AS m JOIN tbl_member_recieved AS f ON m.id = f.member_id LEFT JOIN tbl_payment_head_master AS g ON f.head = g.id
                WHERE f.fees!=0 AND (f.cancel = 0 OR f.cancel IS NULL) AND f.date >= ?
                GROUP BY m.id, f.date
                ORDER BY f.date DESC";
        
                $sqlcountrows = "SELECT COUNT(*) AS total FROM (
                    SELECT m.id FROM tbl_members m
                    JOIN tbl_member_recieved f ON m.id = f.member_id
                    WHERE f.fees!=0 AND (f.cancel = 0 OR f.cancel IS NULL) AND f.date >= ?
                    GROUP BY m.id, f.date
                ) temp";
                
                $sqldatarows_query = $sqldatarows . " LIMIT $offset , $rowsPerPage ";
        
                $result = app_exec_getresult($sqldatarows_query, [$active_year_start], "s");
                    
                $totalRowsResult = app_exec_getresult($sqlcountrows, [$active_year_start], "s");
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
        // action to load fees details of all members ends
    }
?>