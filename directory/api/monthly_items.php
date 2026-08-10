<?php
    session_start();
    include '../../app_common/db_connect.php';
    include '../../app_pagination/pagination.php';

    // actions start
    if(isset($_POST['action']) && !empty($_POST['action'])) {
        $action = $_POST['action'];

        // action to add the new payment details or update the details starts
        if($action=="save_items"){ 
            try{
                if($_POST['id']==0){
                    $sql ="INSERT INTO tbl_items (used_date, no_of_shuttle, total_item_amount, item_number) VALUES (?, ?, ?, ?)";

                    $parameters = [
                        $_POST['used_date'],
                        $_POST['no_of_shuttle'],
                        $_POST['total_item_amount'],
                        $_POST['item_number'],
                    
                    ];

                    $types="siis";
                }
                else{
                    $sql = "UPDATE tbl_items SET  used_date = ?, no_of_shuttle = ?, total_item_amount = ?, item_number = ? WHERE id = ?";

                    
                    $parameters = [
                        $_POST['used_date'],
                        $_POST['no_of_shuttle'],
                        $_POST['total_item_amount'],
                        $_POST['item_number'],
                        $_POST['id'],
                    ];

                    $types="siisi";
                }
                app_exec_nonquery($sql, $parameters, $types);
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
        // action to add the new payment details or update the details ends

        //action to delete payment details start
        if($action=="delete_item_details"){
            try{
               
                $sql = "DELETE FROM tbl_items WHERE id = ?";
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
        //action to delete payment details end

        // action to load all payment details of master table starts
        if($action=="load_data"){
            try{
                $rowsPerPage = 8;
                $current_page = (int)$_POST['page'];
                // Pagination logic
                $sql="";  
                $sqlcountrows="";
                $sqldatarows="";   
        
                $offset = ($current_page - 1) * $rowsPerPage;
                
                $sqldatarows ="SELECT 
                    i.month,
                    i.total_amount,
                    a.member_count,
                    ma.isreceiveble
                FROM 
                    (
                        SELECT DATE_FORMAT(used_date, '%Y-%m') AS month,
                            SUM(total_item_amount) AS total_amount
                        FROM tbl_items
                        GROUP BY DATE_FORMAT(used_date, '%Y-%m')
                    ) AS i
                LEFT JOIN 
                    (
                        SELECT DATE_FORMAT(date, '%Y-%m') AS month,
                            COUNT(DISTINCT member_id) AS member_count
                        FROM tbl_attendance
                        GROUP BY DATE_FORMAT(date, '%Y-%m')
                    ) AS a
                    ON i.month = a.month
                LEFT JOIN 
                    (
                        SELECT DATE_FORMAT(from_date, '%Y-%m') AS month,
                            isreceiveble
                        FROM tbl_monthly_attendance
                        GROUP BY DATE_FORMAT(from_date, '%Y-%m')
                    ) AS ma
                    ON i.month = ma.month
                ORDER BY i.month DESC";
        
                $sqlcountrows = "SELECT COUNT(id) AS total FROM tbl_items ";
                
                
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
        // action to load all payment details of master table ends
    }

    if($action=="show_members"){
        try{
            $sqldatarows="";
                
                    $date = $_POST['val'];
                    $month = date('m', strtotime($date));  //selecting the month from a given date
                    $year = date('Y', strtotime($date));   //selecting the year from a given date

                    $sqldatarows ="SELECT m.id,m.first_name,m.middle_name,m.last_name FROM tbl_attendance as f JOIN tbl_members AS m ON f.member_id = m.id
                    WHERE MONTH(f.date) = ? AND YEAR(f.date) = ? GROUP BY m.id ORDER BY f.date DESC";
                   
                    $parameters = [
                        // $_SESSION['user_id'],
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

    if($action=="save_attendance"){ 
        try{
                
            $sql = "SELECT COUNT(id) AS num FROM tbl_monthly_attendance WHERE from_date = ? AND to_date = ?";

                $parameters = [
                    $_POST['from_date'],
                    $_POST['to_date'],
                ];
                $types = "ss";

                $result1 = app_exec_getresult($sql, $parameters, $types);

                // fetch row instead of mysqli_num_rows
                if ($result1) {
                    $rows = mysqli_fetch_assoc($result1);
                    if ($rows['num'] > 0) {
                        throw new Exception("Data already Exists");
                    }
                }
                
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
            catch (Exception $e) {
                http_response_code(500);   
                echo json_encode([
                    "Message" => "Oops! Something went wrong.",
                    "errmsg"  => $e->getMessage()
                ]);
                exit;
            }
        }

        // if($action=="set_receiveble"){
        //     try{
        //         $sqldatarows="";
                    
        //                 $date = $_POST['val'];
        //                 $month = date('m', strtotime($date));  //selecting the month from a given date
        //                 $year = date('Y', strtotime($date));   //selecting the year from a given date

        //                 $sqldatarows ="SELECT m.idFROM tbl_attendance as f JOIN tbl_members AS m ON f.member_id = m.id
        //                 WHERE MONTH(f.date) = ? AND YEAR(f.date) = ? GROUP BY m.id ORDER BY f.date DESC";
                    
        //                 $parameters = [
        //                     // $_SESSION['user_id'],
        //                     $month,
        //                     $year,
        //                 ];
                        
        //                 $types="ss";
        //                 $result = app_exec_getresult($sqldatarows,$parameters,$types);
                    
        //             //stringify
        //             if ($result && $result->num_rows > 0) {
        //                 // Fetch all rows as an array
        //                 $qrydata = [];
        //                 while ($row = $result->fetch_assoc()) {
        //                     $qrydata[] = $row; // Add each row to the array
        //                 }
        //             } else {
        //                 $qrydata = []; // If no data is found
        //             }
                
        //             $resdata=array($qrydata);
            
        //             echo json_encode($resdata);
        //             exit();
        //         }
        //         catch (Exception $e) {
        //             http_response_code(500);   
        //             echo "Oops! Something went wrong." . $e->getMessage();
        //             return;
        //         }
        // }


    if($action=="save_recieveble"){
        //  echo $_POST['month'];
         $date= $_POST['date'];
            $discription= $_POST['discription'];
            $head=$_POST['head'];
            $selected_year= $_POST['selected_year'];
            $month_year= $_POST['month'];
        try{

            $tm->begin();
            $sqldatarows="";


            // $date = $_POST['month'];
            $month = date('m', strtotime($month_year));  
            $year = date('Y', strtotime($month_year));   //selecting the year from a given date

            
            

           
                
            

            $sqldatarows ="SELECT m.id FROM tbl_attendance as f JOIN tbl_members AS m ON f.member_id = m.id
            WHERE MONTH(f.date) = ? AND YEAR(f.date) = ? GROUP BY m.id ORDER BY f.date DESC";
                   
            $parameters = [
                // $_SESSION['user_id'],
                $month,
                $year,
            ];
                    
            $types="ss";
            $result_members_list = app_exec_getresult($sqldatarows,$parameters,$types);

            $total_list_count = $result_members_list->num_rows;

            $sqldatarows ="SELECT 
                DATE_FORMAT(used_date, '%Y-%m') AS month,
                SUM(total_item_amount) AS total_amount
                FROM tbl_items WHERE MONTH(used_date) = ? AND YEAR(used_date) = ?
                GROUP BY DATE_FORMAT(used_date, '%Y-%m')
                ORDER BY month DESC";
                   
            $parameters = [
                // $_SESSION['user_id'],
                $month,
                $year,
            ];
                    
            $types="ss";
            $result_total_amount = app_exec_getresult($sqldatarows,$parameters,$types);

            

            if (mysqli_num_rows($result_total_amount) > 0) {
                // output data of each row
                while($row = mysqli_fetch_assoc($result_total_amount)) {
                    $total_amount = $row["total_amount"];
                }
            }
            $perhead_amount = round($total_amount / $total_list_count, 2);



            if (mysqli_num_rows($result_members_list) > 0) {
                // output data of each row
                while($row = mysqli_fetch_assoc($result_members_list)) {
                    $member_id = $row["id"];
                           
                    $sql = "INSERT INTO tbl_member_recievable (member_id, fees, date, login_id, head, discription, iscomplete, flag) VALUES (?, ?, ?, ?, ?, ?, 0,?)";
                
                    $parameters = [
                        $member_id,
                        $perhead_amount,
                        $date,
                        $_SESSION['login_id'],
                        $head,
                        $discription,
                        $selected_year,
                                
                    ];
                    $types="iisiisi";
                            
                    // Use custom insert function that returns insert ID
                    $recieveble_id = app_exec_getlast_id_roll_back($conn, $sql, $parameters, $types);

                    $received_date = !empty($_POST['received_date']) 
                    ? $_POST['received_date'] 
                    : '0000-00-00';
                    if ($recieveble_id !== null) {
                        $sql = "INSERT INTO tbl_member_recieved (member_id, fees, date, login_id, head, discription, receiveble_id, flag) VALUES (?, 0, ?, ?, ?, ?, ?, ?)";
                        $parameters = [
                            $member_id,
                            $received_date,
                            $_SESSION['login_id'],
                            $head,
                            $discription,
                            $recieveble_id,
                            $selected_year,
                        ];
                        $types="isiisii";

                        // Saving Query as  text file end
                        if (!app_exec_roll_back_nonquery($conn, $sql, $parameters, $types)) {
                            throw new Exception("Error in Updation");
                        }

                        $sql = "UPDATE tbl_monthly_attendance SET isreceiveble = 1 WHERE MONTH(from_date) = ? AND YEAR(from_date) = ? AND  MONTH(to_date) = ? AND YEAR(to_date) = ? AND member_id = ?";
                        $parameters = [
                            $month,
                            $year,
                            $month,
                            $year,
                            $member_id,
                        ];
                        $types="ssssi";

                        // Saving Query as  text file end
                        if (!app_exec_roll_back_nonquery($conn, $sql, $parameters, $types)) {
                            throw new Exception("Error in Updation");
                        }

                    }
                            
                }
            } 
                    
                    
            $tm->commit();
            echo 'Updated Sucessfully';      
                    
        }
        catch (Exception $e) {
            $tm->rollback();
            echo $e->getMessage();
        }
    }


?>