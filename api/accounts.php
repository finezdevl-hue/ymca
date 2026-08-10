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
                    $sqldatarows = "SELECT f.date,f.fees,f.id,g.name AS head,f.discription
                    FROM tbl_member_recieved AS f LEFT JOIN tbl_payment_head_master AS g 
                    ON f.head = g.id WHERE f.member_id = ? ORDER BY f.date DESC";
                        
                    $sqlcountrows = "SELECT COUNT(*) AS total FROM tbl_member_recieved 
                    WHERE member_id = ?";
                }
                else{            
                    $date = $_POST['val'];
                    $month = date('m', strtotime($date));  //selecting the month from a given date
                    $year = date('Y', strtotime($date));

                    $sqldatarows = "SELECT date,f.fees,f.id,g.name as head,f.discription
                    FROM tbl_members AS m JOIN 	tbl_member_recieved AS f ON m.id = f.member_id LEFT JOIN tbl_payment_head_master AS g ON f.head = g.id
                    WHERE m.email = '" . $_SESSION['email'] . "' AND m.first_name = '" . $_SESSION['name'] . "' AND MONTH(f.date) = ? AND YEAR(f.date) = ? order by f.date DESC"; 

                    $sqlcountrows = "SELECT COUNT(*) AS total FROM tbl_member_recieved as f JOIN tbl_members AS m ON f.member_id = m.id
                    WHERE m.email = '" . $_SESSION['email'] ."' AND m.first_name = '" . $_SESSION['name'] . "' AND MONTH(f.date) = ? AND YEAR(f.date) = ?";         
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
                        $month,
                        $year,
                    ];
                    
                    $types="ss";
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

    }
    // actions end
?>