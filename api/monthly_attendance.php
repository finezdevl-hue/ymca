<?php
    session_start();
    include '../../app_common/db_connect.php';
    include '../../app_pagination/pagination.php';

    // actions start
    if(isset($_POST['action']) && !empty($_POST['action'])) {
        $action = $_POST['action'];

        // action to save monthly attendance start
        if($action=="save_attendance"){ 
            $compile_month_end = date('Y-m-t', strtotime($_POST['from_date']));
            $current_date = date('Y-m-d');
            if ($current_date <= $compile_month_end) {
                $month_name = date('F Y', strtotime($_POST['from_date']));
                http_response_code(400);
                header('Content-Type: application/json');
                echo json_encode(array('Message' => "Attendance for $month_name can only be processed after the month ends."));
                exit;
            }

            try{
                // $from_date = date("Y-m-d", strtotime($_POST['from_date']));
                // $to_date = date("Y-m-d", strtotime($_POST['to_date']));
                
                $sql = "SELECT DISTINCT member_id AS id, COUNT(member_id) AS totalattendance FROM tbl_attendance
                WHERE (date BETWEEN ?  AND ?) GROUP BY member_id";
                $parameters = [
                    $_POST['from_date'],
                    $_POST['to_date'],
                ];
        
                $types="ss";

                $result = app_exec_getresult($sql,$parameters,$types);
                // echo $sql;
                if (mysqli_num_rows($result) > 0) {
                // output data of each row
                    while($row = mysqli_fetch_assoc($result)) {
                        $member_id = $row["id"];
                        $totalattendance = $row["totalattendance"];
                
                        $sql = "INSERT INTO tbl_monthly_attendance (member_id, from_date, to_date, attendance) VALUES (?, ?, ?, ?)";
                        $parameters = [
                            $member_id,
                            $_POST['from_date'],
                            $_POST['to_date'],
                            $totalattendance,
                        ];
                
                        $types="issi";
                
                        app_exec_nonquery($sql, $parameters, $types);
                        
                    }
                } 
            }
            catch (Throwable $e) {
                throw new Exception('Oops! Something went wrong.');
            }
        }
        // action to save mothly attendance end
        if($action=="load_groups"){
            try{
                include_once __DIR__ . '/../app_common/auth_helper.php';
                $current_login = isset($_SESSION['login_id']) ? (int)$_SESSION['login_id'] : 0;
                $allowed = getUserAllowedGroupIds($current_login);

                if (in_array('ALL', $allowed, true)) {
                    $sql = "SELECT id, name FROM tbl_groups WHERE status = 1 ORDER BY name ASC";
                    $result = app_exec_query($sql);
                } else if (!empty($allowed)) {
                    $in = implode(',', array_map('intval', $allowed));
                    $sql = "SELECT id, name FROM tbl_groups WHERE id IN ($in) AND status = 1 ORDER BY name ASC";
                    $result = app_exec_query($sql);
                } else {
                    $result = false;
                }

                $qrydata = [];
                if ($result && $result->num_rows > 0) {
                    while ($row = $result->fetch_assoc()) {
                        $qrydata[] = $row;
                    }
                }
                echo json_encode(array($qrydata));
                exit();
            }
            catch (Throwable $e) {
                throw new Exception('Oops! Something went wrong.');
            }
        }
        if($action=="load_heads"){
            try{
                $sql = "SELECT id, name FROM tbl_payment_head_master WHERE name LIKE '%monthly%'";
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

        if($action=="save_recieveble"){ 
            try{
                $active_year_start = '2000-04-01';
                $active_yr_res = app_exec_query("SELECT from_year FROM tbl_closing ORDER BY from_year DESC LIMIT 1");
                if ($active_yr_res && $row_yr = $active_yr_res->fetch_assoc()) {
                    $active_year_start = $row_yr['from_year'] . "-04-01";
                }

                $attendance = $_POST['attendance'];
                $rec_date = $_POST['date'];
                if ($rec_date >= $active_year_start) {
                    $receiveble = 300;
                } else {
                    if ($attendance <= 5) {
                         $receiveble = 150;
                    } else {
                        $receiveble = 300;
                    }
                }

                date_default_timezone_set('Asia/Kolkata');
                $currentDate = date('Y-m-d');
            
                $sql = "INSERT INTO tbl_member_recievable (member_id, fees, date, login_id, head, discription, iscomplete) VALUES (?, ?, ?, ?, ?, ?, 0)";
                
                $parameters = [
                    $_POST['member_id'],
                    $receiveble,
                    $_POST['date'],
                    $_SESSION['login_id'],
                    $_POST['head'],
                    $_POST['discription'],
                ];
                $types="iisiis";
                    
                // Use custom insert function that returns insert ID
                $recieveble_id = app_exec_getlast_id($sql, $parameters, $types);
                    $received_date = !empty($_POST['received_date']) 
                    ? $_POST['received_date'] 
                    : '0000-00-00';
                if ($recieveble_id !== null) {
                    $sql = "INSERT INTO tbl_member_recieved (member_id, fees, date, login_id, head, discription, receiveble_id) VALUES (?, 0, ?, ?, ?, ?,?)";
                    $parameters = [
                        $_POST['member_id'],
                        $received_date,
                        $_SESSION['login_id'],
                        $_POST['head'],
                        $_POST['discription'],
                        $recieveble_id,
                    ];
                    $types="isiisi";

                    // Saving Query as  text file end
                    app_exec_nonquery($sql, $parameters, $types);

                    $sql = "UPDATE tbl_monthly_attendance SET isreceiveble = 1 WHERE id= ?";
                    $parameters = [
                        $_POST['id'],
                       
                    ];
                    $types="i";

                    // Saving Query as  text file end
                    app_exec_nonquery($sql, $parameters, $types);
                }
                
            }
             catch (Exception $e) {
                http_response_code(500);   
                echo "Oops! Something went wrong." . $e->getMessage();
                return;
            }
    
        }

        // action to load monthly attendance details start
        if($action=="load_data"){
            try{
                $rowsPerPage = 8;
                $current_page = (int)$_POST['page'];
                // Pagination logic
                $sql="";  
                $sqlcountrows="";
                $sqldatarows="";   
        
                $offset = ($current_page - 1) * $rowsPerPage;
                
                $sqldatarows ="SELECT  ma.id,ma.from_date, ma.to_date,ma.member_id,ma.attendance,ma.isreceiveble,m.first_name,m.middle_name,m.last_name FROM tbl_monthly_attendance AS ma LEFT JOIN tbl_members as m ON ma.member_id=m.id ORDER BY ma.from_date DESC,m.first_name,m.middle_name,m.last_name";
        
                $sqlcountrows = "SELECT COUNT(id) AS total FROM tbl_monthly_attendance";
                
                
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
        // action to load monthly attendance details  end
    }
?>