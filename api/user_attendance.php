<?php
session_start();
include '../../app_common/db_connect.php';
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
            try{
                $member_id = $_POST['member_id'];
                $date = $_POST['date'];

                $sql2 = "SELECT id FROM tbl_attendance WHERE member_id = ? AND date = ?";
                $types="is";
                $parameters = [
                    $member_id,
                    $date,
                ];
    
               $result=app_exec_getresult($sql2, $parameters, $types);
                $row = $result->fetch_assoc();
                if ($row === null){
                    $sql ="INSERT INTO tbl_attendance(member_id, group_id,date) VALUES (?, 0, ?)"; 
                    $parameters = [
                        $member_id,
                        $date,
                        
                    ];

                    $types="is";
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
                    $sqldatarows = "SELECT d.id, d.date,
                    (SELECT id FROM tbl_members WHERE email = 'user1@gmail.com') AS user_id
                    FROM tbl_dates d ORDER BY d.date DESC";
                     
                     $sqlcountrows = "SELECT  Count(id) as total FROM tbl_dates";
                }
                else{            

                    $date = $_POST['val'];
                    $month = date('m', strtotime($date));  //selecting the month from a given date
                    $year = date('Y', strtotime($date));   //selecting the year from a given date

                    $sqldatarows ="SELECT d.id, d.date,
                    (SELECT id FROM tbl_members WHERE email = 'user1@gmail.com') AS user_id
                    FROM tbl_dates d WHERE MONTH(d.date) = ? AND YEAR(d.date) = ? ORDER BY d.date DESC";

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

?>