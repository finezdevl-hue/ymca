<?php
    session_start();
    include '../../app_common/db_connect.php';
    include '../../app_pagination/pagination.php';

    // actions start
    if(isset($_POST['action']) && !empty($_POST['action'])) {
        $action = $_POST['action'];

        // action to load attendance details of the user who logged in starts
        if($action=="load_data"){
            try{
                $sqldatarows="";

                if($_POST['val']== ''){

                    $sqldatarows ="SELECT DATE_FORMAT(f.date, '%Y-%m') AS month_year,
                    COUNT(f.date) AS total_attendance FROM tbl_attendance AS f
                    JOIN tbl_members AS m ON f.member_id = m.id
                    WHERE m.email = '" . $_SESSION['email'] . "' AND m.first_name = '" . $_SESSION['name'] . "'
                    GROUP BY DATE_FORMAT(f.date, '%Y-%m') ORDER BY month_year DESC";
                    
                }
                else{
                    $date = $_POST['val'];
                    $month = date('m', strtotime($date));  //selecting the month from a given date
                    $year = date('Y', strtotime($date));   //selecting the year from a given date

                    $sqldatarows ="SELECT DATE_FORMAT(f.date, '%Y-%m') AS month_year,
                    COUNT(f.date) AS total_attendance FROM tbl_attendance AS f
                    JOIN tbl_members AS m ON f.member_id = m.id
                    WHERE m.email = '" . $_SESSION['email'] . "' AND m.first_name = '" . $_SESSION['name'] . "' AND MONTH(f.date) = ? AND YEAR(f.date) = ?
                    GROUP BY DATE_FORMAT(f.date, '%Y-%m') ORDER BY month_year DESC";
                    
                }
           
                if($_POST['val']== '') {
                    $result = app_exec_query($sqldatarows);
                }
                else{

                    $parameters = [
                        $month,
                        $year,
                    ];
                    
                    $types="ss";
                    $result=app_exec_getresult($sqldatarows,$parameters,$types);

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
        // action to load attendance details of the user who logged in ends

        // action to load attendance details of the user who logged in start
        if($action=="load_attendance"){
            try{
                $sqldatarows="";
                
                    $date = $_POST['val'];
                    $month = date('m', strtotime($date));  //selecting the month from a given date
                    $year = date('Y', strtotime($date));   //selecting the year from a given date

                    $sqldatarows ="SELECT f.date FROM tbl_attendance as f JOIN tbl_members AS m ON f.member_id = m.id
                    WHERE m.email = '" . $_SESSION['email'] . "'  AND m.first_name = '" . $_SESSION['name'] . "' AND MONTH(f.date) = ? AND YEAR(f.date) = ? order by f.date DESC";
                    $searchvalue= $_POST['val'];
                    $parameters = [
                        $month,
                        $year,
                    ];
                    
                    $types="ss";
                    $result = app_exec_getresult($sqldatarows,$parameters,$types);
                
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
        // action to load attendance details of the user who logged in end
    }
    // action end
?>