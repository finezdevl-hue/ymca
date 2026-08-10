<?php
session_start();
include '../../app_common/db_connect.php';
include_once __DIR__ . '/attendance_fix_helper.php';
include '../../app_pagination/pagination.php';


    // action start
    if(isset($_POST['action']) && !empty($_POST['action'])) {
        $action = $_POST['action'];  
        
        // action load group status
        if($action=="load_group_status"){
            try{
                echo "<div class='dropdown'><select id='select_status' class='status-dropdown' onchange='loadData(1)'>";
                echo "<option  value='0'>All</option>";
                echo "<option  value='1'>Active</option>";
                echo "<option  value='2'>Not Active</option>";
                echo "</select></div>";
                exit();

            }
            catch (Throwable $e) {
                throw new Exception('Oops! Something went wrong.');
            }

           
        }
        // action load group status end

        // action update group status start
        if($action=="update_group_status"){
            try{
                if (isset($_POST['id'], $_POST['status'])) {    
                
                    $sql = "UPDATE tbl_groups SET status=? where id=?";
                    $parameters = [
                        $_POST['status'],
                        $_POST['id'],        
                    ];
                    $types="ii";
                    $result=app_exec_nonquery($sql,$parameters,$types);
                
                }
            }
            catch (Throwable $e) {
                throw new Exception('Oops! Something went wrong.');
            }
        }
        // action update group status end

        // action delete group start
        if($action=="delete_date"){
            try{
                $id = $_POST['id'];
                // $sql = "UPDATE tbl_members SET group_id = 0 where id=?";
                $sql = "DELETE FROM tbl_dates WHERE id = ?";
                $types="i";
                $parameters = [
                $_POST['id'],
                ];
    
                app_exec_nonquery($sql, $parameters, $types);
    
            }
            catch (Throwable $e) {
                throw new Exception('Oops! Something went wrong.');
            }
        }
        // action delete group end

        if($action=="add_attendance"){
            checkMonthFixed($_POST['date']);
            try{
               
                $sql1 = "SELECT id, date FROM tbl_dates WHERE date = ?";
                $types = "s"; // date is a string
                $parameters = [
                    $_POST['date'],
                ];

                $result1 = app_exec_getresult($sql1, $parameters, $types);
                $row1 = $result1->fetch_assoc();

                // If a row exists, throw exception
                if ($row1 !== null) {
                    throw new Exception("Today is a Holiday / Leave.");
                }

                $sql2 = "SELECT id FROM tbl_attendance WHERE member_id = ? AND date = ? AND group_id=?";
                $types="isi";
                $parameters = [
                    $_SESSION['user_id'],
                    $_POST['date'],
                    $_POST['group'],
                ];
    
               $result=app_exec_getresult($sql2, $parameters, $types);
                $row = $result->fetch_assoc();
                if ($row === null){
                    $sql ="INSERT INTO tbl_attendance(member_id, group_id,date) VALUES (?, ?, ?)"; 
                    $parameters = [
                        $_SESSION['user_id'],
                        $_POST['group'],
                        $_POST['date'],
                        
                    ];

                    $types="iis";
                    app_exec_nonquery($sql, $parameters, $types);

                }
                else{
                    throw new Exception("Already Attendance Marked");
                }
            }
            catch (Exception $e) {
                $tm->rollback();
                http_response_code(500);
                echo json_encode([
                    'Message' => $e->getMessage(),
                ]);
            } 
        }

        // action add new group start
        if($action=="save_group"){ 
            try{
                
                if($_POST['id']==0){
                    $sql ="INSERT INTO tbl_dates (date) VALUES (?)";
                    $types="s";
                    $parameters = [
                    $_POST['date'],
                    ];
                }
                else{
                    $sql = "UPDATE tbl_groups SET name = ?  WHERE id = ?";
                    $types="si";
                    $parameters = [
                    $_POST['group_name'],
                    $_POST['id'],
                    ];
                }
                app_exec_nonquery($sql, $parameters, $types);
                
            }
            catch (Throwable $e) {
                throw new Exception('Oops! Something went wrong.');
            }
        }
        // action add new group end

        // action load groups start
        // if($action=="load_data"){
        //      try{
        //         $rowsPerPage = 8;

        //         $current_page = (int)$_POST['page'];

        //         // Pagination logic
        //         $sql="";  
        //         $sqlcountrows="";
        //         $sqldatarows="";   
                
        //         $offset = ($current_page - 1) * $rowsPerPage;
                
        //         if($_POST['val']== ''){
        //             // $sqldatarows = "SELECT f.id, f.first_name, f.middle_name, f.last_name, b.name AS blood_group FROM tbl_members AS f JOIN tbl_bloodgroup_master AS b ON f.blood_group = b.id";
        //             $sqldatarows = "SELECT  id,date FROM tbl_dates ";
                     
        //             $sqlcountrows = "SELECT  Count(id) as total FROM tbl_dates";
        //         }
        //         else{            
        //             $date = $_POST['val'];
        //             $month = date('m', strtotime($date));  //selecting the month from a given date
        //             $year = date('Y', strtotime($date));
                    
        //             $sqldatarows = "SELECT id, date FROM tbl_dates
        //             WHERE  MONTH(date) = ? AND YEAR(date) = ?
        //             ORDER BY date DESC"; 

        //             $sqlcountrows = "SELECT  Count(id) as total
        //             FROM tbl_dates
        //             WHERE  MONTH(date) = ? AND YEAR(date) = ?";         
        //         }    
                
        //         $sqldatarows .= " LIMIT $offset , $rowsPerPage ";
            
        //         if($_POST['val']== '') {
        //             $result = app_exec_query($sqldatarows);
                    
        //             $totalRowsResult = app_exec_query($sqlcountrows);
        //             $totalRows = $totalRowsResult->fetch_assoc()['total'];
        //         }
        //         else{
        //             $searchvalue= $_POST['val'];
        //             $parameters = [
        //                 '%' . $searchvalue . '%',
        //                 '%' . $searchvalue . '%',
        //                 '%' . $searchvalue . '%',
        //                  $searchvalue,
        //             ];
                    
        //             $types="ssss";
        //             $result=app_exec_getresult($sqldatarows,$parameters,$types);

        //             $totalRowsResult = app_exec_getresult($sqlcountrows,$parameters,$types);
        //             $totalRows = $totalRowsResult->fetch_assoc()['total'];
        //         }

        //         //stringify
        //         if ($result && $result->num_rows > 0) {
        //             // Fetch all rows as an array
        //             $qrydata = [];
        //             while ($row = $result->fetch_assoc()) {
        //                 $qrydata[] = $row; // Add each row to the array
        //             }
        //         } else {
        //             $qrydata = []; // If no data is found
        //         }

        //         $pagination = array("total_rows"=>$totalRows);
        //         $resdata=array($pagination,$qrydata);

        //         echo json_encode($resdata);
        //         exit();
        //     }
        //     catch (Throwable $e) {
        //         throw new Exception('Oops! Something went wrong.');
        //     }
            
        // }

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
                    $sqldatarows = "SELECT id, date
                    
                    FROM tbl_dates  ORDER BY date DESC";
                     
                     $sqlcountrows = "SELECT  Count(id) as total FROM tbl_dates";
                }
                else{            

                    $date = $_POST['val'];
                    $month = date('m', strtotime($date));  //selecting the month from a given date
                    $year = date('Y', strtotime($date));   //selecting the year from a given date

                    $sqldatarows ="SELECT d.id, d.date
                    
                    FROM tbl_dates  WHERE MONTH(date) = ? AND YEAR(date) = ? ORDER BY date DESC";

                    $sqlcountrows = "SELECT  COUNT(id) as total FROM tbl_dates
                    WHERE MONTH(date) = ? AND YEAR(date) = ?";         
                }    
                
                $sqldatarows .= " LIMIT $offset , $rowsPerPage ";
            
                if($_POST['val']== '') {
                    $result = app_exec_query($sqldatarows);
                    
                    $totalRowsResult = app_exec_query($sqlcountrows);
                    $totalRows = $totalRowsResult->fetch_assoc()['total'];
                }
                else{
                    $searchvalue= $_POST['val'];
                    $parameters = [
                        $month,
                        $year,
                    ];
                    
                    $types="ii";
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
            catch (Throwable $e) {
                throw new Exception('Oops! Something went wrong.');
            }
            
        }
        // action load groups end
   
    }

    if($action=="load_groups"){
        try{
            $sql = "SELECT g.id, g.name FROM tbl_groups AS g LEFT JOIN tbl_group_member_map as gm ON g.id=gm.group_id WHERE gm.member_id = ? AND g.status = 1";
            // $result = app_exec_query($sql);
            $parameters = [
                $_SESSION['user_id'],
            ];
                    
            $types="i";
            $result=app_exec_getresult($sql,$parameters,$types);

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
    if ($action == "get_user_pending_balance") {
        try {
            $user_id = !empty($_POST['member_id']) ? (int)$_POST['member_id'] : (!empty($_SESSION['user_id']) ? (int)$_SESSION['user_id'] : 0);

            if ($user_id <= 0 && !empty($_SESSION['email'])) {
                $sess_email = trim($_SESSION['email']);
                $res_u = app_exec_getresult("SELECT id FROM tbl_members WHERE LTRIM(RTRIM(LOWER(email))) = LTRIM(RTRIM(LOWER(?))) AND inactive = 0 LIMIT 1", [$sess_email], "s");
                if ($res_u && $ru = $res_u->fetch_assoc()) {
                    $user_id = (int)$ru['id'];
                }
            }
            if ($user_id <= 0 && !empty($_SESSION['name'])) {
                $sess_name = trim($_SESSION['name']);
                $first_w = explode(' ', $sess_name)[0];
                $res_u = app_exec_getresult("SELECT id FROM tbl_members WHERE (first_name LIKE ? OR CONCAT(first_name, ' ', last_name) LIKE ?) AND inactive = 0 LIMIT 1", ['%' . $first_w . '%', '%' . $sess_name . '%'], "ss");
                if ($res_u && $ru = $res_u->fetch_assoc()) {
                    $user_id = (int)$ru['id'];
                }
            }
            if ($user_id <= 0) {
                $res_u = app_exec_query("SELECT member_id FROM tbl_member_recievable WHERE cancel = 0 GROUP BY member_id ORDER BY SUM(fees) DESC LIMIT 1");
                if ($res_u && $ru = $res_u->fetch_assoc()) {
                    $user_id = (int)$ru['member_id'];
                }
            }
            
            $current_month = (int)date('n');
            $year = ($current_month >= 4) ? (int)date('Y') : (int)date('Y') - 1;
            $start_date = $year . "-04-01";
            $end_date = ($year + 1) . "-03-31";

            // 1. Calculate Opening Balance prior to start_date
            $op_sql = "SELECT 
                        (
                            (SELECT IFNULL(SUM(fees), 0) FROM tbl_member_recievable WHERE member_id = ? AND date < ? AND cancel = 0) +
                            (SELECT IFNULL(SUM(fees), 0) FROM tbl_member_recievable_old WHERE member_id = ? AND date < ? AND cancel = 0)
                        ) -
                        (
                            (SELECT IFNULL(SUM(fees), 0) FROM tbl_member_recieved WHERE member_id = ? AND date < ? AND cancel = 0) +
                            (SELECT IFNULL(SUM(fees), 0) FROM tbl_member_recieved_old WHERE member_id = ? AND date < ? AND cancel = 0)
                        ) +
                        (SELECT COALESCE(SUM(CASE WHEN type = 'debit' THEN amount ELSE 0 END) - SUM(CASE WHEN type = 'credit' THEN amount ELSE 0 END), 0) FROM tbl_wallet WHERE client_id = ? AND date < ?)
                       AS opening_balance";
            $op_res = app_exec_getresult($op_sql, [
                $user_id, $start_date,
                $user_id, $start_date,
                $user_id, $start_date,
                $user_id, $start_date,
                $user_id, $start_date
            ], "isisisisis");
            $opening_balance = 0;
            if ($op_res && $row = $op_res->fetch_assoc()) {
                $opening_balance = (double)$row['opening_balance'];
            }

            // 2. Get total debits (receivables in FY)
            $rec_sql = "SELECT 
                        COALESCE((SELECT SUM(fees) FROM tbl_member_recievable WHERE member_id = ? AND date BETWEEN ? AND ? AND cancel = 0), 0)
                        +
                        COALESCE((SELECT SUM(fees) FROM tbl_member_recievable_old WHERE member_id = ? AND date BETWEEN ? AND ? AND cancel = 0), 0)
                        AS total_rec";
            $rec_res = app_exec_getresult($rec_sql, [$user_id, $start_date, $end_date, $user_id, $start_date, $end_date], "ississ");
            $total_rec = 0;
            if ($rec_res && $row = $rec_res->fetch_assoc()) {
                $total_rec = (double)$row['total_rec'];
            }

            // 3. Get total credits (payments in FY)
            $pay_sql = "SELECT 
                        COALESCE((SELECT SUM(fees) FROM tbl_member_recieved WHERE member_id = ? AND date BETWEEN ? AND ? AND cancel = 0), 0)
                        +
                        COALESCE((SELECT SUM(fees) FROM tbl_member_recieved_old WHERE member_id = ? AND date BETWEEN ? AND ? AND cancel = 0), 0)
                        AS total_pay";
            $pay_res = app_exec_getresult($pay_sql, [$user_id, $start_date, $end_date, $user_id, $start_date, $end_date], "ississ");
            $total_pay = 0;
            if ($pay_res && $row = $pay_res->fetch_assoc()) {
                $total_pay = (double)$row['total_pay'];
            }

            // 4. Wallet debits and credits in FY
            $w_sql = "SELECT 
                        COALESCE(SUM(CASE WHEN type = 'debit' THEN amount ELSE 0 END), 0) AS w_debit,
                        COALESCE(SUM(CASE WHEN type = 'credit' THEN amount ELSE 0 END), 0) AS w_credit
                      FROM tbl_wallet WHERE client_id = ? AND date BETWEEN ? AND ?";
            $w_res = app_exec_getresult($w_sql, [$user_id, $start_date, $end_date], "iss");
            $w_debit = 0;
            $w_credit = 0;
            if ($w_res && $row = $w_res->fetch_assoc()) {
                $w_debit = (double)$row['w_debit'];
                $w_credit = (double)$row['w_credit'];
            }

            $pending_balance = $opening_balance + $total_rec + $w_debit - $total_pay - $w_credit;

            // Get payment / UPI settings
            $payment_settings = [
                "upi_id" => "ymcabcp@okaxis",
                "payee_name" => "YMCA BCP Poovathussery",
                "payment_note" => "YMCA Member Fee Payment",
                "is_active" => 1
            ];
            $pay_res = app_exec_query("SELECT upi_id, payee_name, payment_note, is_active FROM tbl_payment_settings WHERE id = 1");
            if ($pay_res && $p_row = $pay_res->fetch_assoc()) {
                $payment_settings = [
                    "upi_id" => $p_row['upi_id'],
                    "payee_name" => $p_row['payee_name'],
                    "payment_note" => $p_row['payment_note'],
                    "is_active" => (int)$p_row['is_active']
                ];
            }

            echo json_encode([
                "status" => "success",
                "pending_balance" => $pending_balance,
                "payment_settings" => $payment_settings
            ]);
            exit();
        } catch (Throwable $e) {
            http_response_code(500);
            echo json_encode(["status" => "error", "message" => $e->getMessage()]);
            exit();
        }
    }

}
?>