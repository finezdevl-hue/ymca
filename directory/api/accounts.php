<?php
    session_start();
    
    include '../../app_common/db_connect.php';
    include '../../app_pagination/pagination.php';

    // actions start
    if(isset($_POST['action']) && !empty($_POST['action'])) {
        $action = $_POST['action'];

        // action to load fee details of the member who logged in starts
        if($action=="load_data"){
            try{
                $rowsPerPage = 8;
                $current_page = (int)$_POST['page'];

                // Pagination logic
                $sql="";  
                $sqlcountrows="";
                $sqldatarows="";   
                
                $offset = ($current_page - 1) * $rowsPerPage;
                
                if($_POST['val']== ''){
                    $sqldatarows = "SELECT mr.fees AS receivable,COALESCE(SUM(mrd.fees), 0) AS received,mr.date,m.name AS head,mr.discription
                    FROM tbl_member_recievable AS mr LEFT JOIN tbl_member_recieved AS mrd ON mr.id = mrd.receiveble_id
                    LEFT JOIN tbl_payment_head_master AS m ON mr.head = m.id
                    WHERE mr.member_id = ? AND mr.cancel = 0
                    GROUP BY mr.id,mr.fees,mr.date,m.name,mr.discription ORDER BY mr.date DESC";
                        
                    // $sqlcountrows = "SELECT COUNT(*) AS total
                    // FROM tbl_member_recieved
                    // WHERE member_id = ?
                    // AND fees <> 0";
                        
                    $sqlcountrows = "SELECT COUNT(*) AS total
                    FROM tbl_member_recievable
                    WHERE member_id = ?
                    AND  cancel = 0";
                }
                else{            
                    $date = $_POST['val'];
                    $month = date('m', strtotime($date));  //selecting the month from a given date
                    $year = date('Y', strtotime($date));

                    $sqldatarows = "SELECT 
                        mr.fees AS receivable,
                        COALESCE(SUM(mrd.fees), 0) AS received,
                        mr.date,
                        m.name AS head,
                        mr.discription
                    FROM tbl_member_recievable AS mr
                    LEFT JOIN tbl_member_recieved AS mrd 
                        ON mr.id = mrd.receiveble_id
                    LEFT JOIN tbl_payment_head_master AS m 
                        ON mr.head = m.id
                    WHERE mr.member_id = ?
                        AND mr.cancel = 0
                        AND MONTH(mr.date) = ?
                        AND YEAR(mr.date) = ?
                    GROUP BY 
                        mr.id,
                        mr.fees,
                        mr.date,
                        m.name,
                        mr.discription
                    ORDER BY mr.date DESC"; 

                    $sqlcountrows = "SELECT 
                        COUNT(*) AS total
                    FROM tbl_member_recievable 
                    WHERE 
                        member_id = ?
                        AND MONTH(date) = ?
                        AND YEAR(date) = ?";         
                }    
                
                $sqldatarows .= " LIMIT $offset , $rowsPerPage ";
            
                if($_POST['val']== '') {
                    $parameters = [
                        $_SESSION['user_id'],
                    ];
                    
                    $types="i";
                    $result=app_exec_getresult($sqldatarows,$parameters,$types);

                    $totalRowsResult = app_exec_getresult($sqlcountrows,$parameters,$types);
                    $totalRows = $totalRowsResult->fetch_assoc()['total'];
                }
                else{
                    $parameters = [
                        $_SESSION['user_id'],
                        $month,
                        $year,
                    ];
                    
                    $types="iss";
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
            catch (Exception $e) {
                http_response_code(500);   
                echo "Oops! Something went wrong." . $e->getMessage();
                return;
            }
        }
        // action to load fee details of the member who logged in ends

        // action to load pending payments of a member start
        if($action=="load_pending_payment"){
            try{
                $sql = "SELECT
                -- Total receivable
                (SELECT SUM(fees) 
                FROM tbl_member_recievable 
                WHERE member_id = ?
                AND cancel = 0
                ) AS receivable_fees,

                -- Total received
                (SELECT SUM(fees) 
                FROM tbl_member_recieved 
                WHERE member_id = ? 
                AND cancel = 0
                ) AS received_fees,

                -- Wallet balance
                (SELECT 
                    COALESCE(
                        SUM(CASE WHEN type = 'credit' THEN amount ELSE 0 END) -
                        SUM(CASE WHEN type = 'debit' THEN amount ELSE 0 END),
                    0) AS wallet_balance
                FROM tbl_wallet
                WHERE client_id = ?
                ) AS wallet_balance

                ";

                $parameters = [
                    $_SESSION['user_id'],
                    $_SESSION['user_id'],
                    $_SESSION['user_id'],
                ];
                $types="iii";

                $result = app_exec_getresult($sql,$parameters,$types);

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
        // action to load pending payments of a member end

         if($action=="payments_history_data"){
            try{
                // $rowsPerPage = 8;
                // $current_page = (int)$_POST['page'];

                // // Pagination logic
                // $sql="";  
                // $sqlcountrows="";
                // $sqldatarows="";   
                
                // $offset = ($current_page - 1) * $rowsPerPage;
                
                if($_POST['val']== ''){
                    $sqldatarows = "SELECT SUM(`fees`) as payment, `date`,`iswallet`,`transaction_type` FROM tbl_member_recieved WHERE member_id= ? AND  cancel = 0 AND fees <> 0 GROUP BY `date`,`transaction_type`";
                       
                    // $sqlcountrows = "SELECT COUNT(*) AS total
                    // FROM tbl_member_recieved
                    // WHERE member_id = ?
                    // AND  cancel = 0 AND fees <> 0 GROUP BY `date`,`transaction_type`";
                }
                else{            
                    $date = $_POST['val'];
                    $month = date('m', strtotime($date));  //selecting the month from a given date
                    $year = date('Y', strtotime($date));

                    $sqldatarows = "SELECT SUM(`fees`) as payment, `date`,`iswallet`,`transaction_type` FROM tbl_member_recieved WHERE member_id= ? AND  cancel = 0 AND fees <> 0  AND MONTH(date) = ?
                        AND YEAR(date) = ? GROUP BY `date`,`transaction_type`"; 

                    // $sqlcountrows = "SELECT 
                    //     COUNT(*) AS total
                    // FROM tbl_member_recievable 
                    // WHERE 
                    //     member_id = ?
                    //     AND MONTH(date) = ?
                    //     AND YEAR(date) = ?";         
                }    
                
                // $sqldatarows .= " LIMIT $offset , $rowsPerPage ";
            
                if($_POST['val']== '') {
                    $parameters = [
                        $_SESSION['user_id'],
                    ];
                    
                    $types="i";
                    $result=app_exec_getresult($sqldatarows,$parameters,$types);

                    // $totalRowsResult = app_exec_getresult($sqlcountrows,$parameters,$types);
                    // $totalRows = $totalRowsResult->fetch_assoc()['total'];
                }
                else{
                    $parameters = [
                        $_SESSION['user_id'],
                        $month,
                        $year,
                    ];
                    
                    $types="iss";
                    $result=app_exec_getresult($sqldatarows,$parameters,$types);

                    // $totalRowsResult = app_exec_getresult($sqlcountrows,$parameters,$types);
                    // $totalRows = $totalRowsResult->fetch_assoc()['total'];
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

                // $pagination = array("total_rows"=>$totalRows);
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
        // action to load fee details of the member who logged in ends


    }
    // actions end
?>