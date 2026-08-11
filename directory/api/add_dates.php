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

        // action add new date start
        if($action=="save_date"){ 
            try{
                $id = isset($_POST['id']) ? (int)$_POST['id'] : 0;
                $groupId = isset($_POST['group_id']) ? (int)$_POST['group_id'] : 0;
                $dateVal = $_POST['date'];

                if($id == 0){
                    $sql ="INSERT INTO tbl_dates (date, group_id) VALUES (?, ?)";
                    $types="si";
                    $parameters = [
                        $dateVal,
                        $groupId,
                    ];
                }
                else{
                    $sql = "UPDATE tbl_dates SET date = ?, group_id = ? WHERE id = ?";
                    $types="sii";
                    $parameters = [
                        $dateVal,
                        $groupId,
                        $id,
                    ];
                }
                app_exec_nonquery($sql, $parameters, $types);

                // Automatically clear any pre-existing attendance records marked prior to declaring holiday
                if ($groupId == 0) {
                    app_exec_nonquery("DELETE FROM tbl_attendance WHERE date = ?", [$dateVal], "s");
                } else {
                    app_exec_nonquery("DELETE FROM tbl_attendance WHERE date = ? AND group_id = ?", [$dateVal, $groupId], "si");
                }

                echo "Saved successfully";
                exit();
            }
            catch (Throwable $e) {
                http_response_code(500);
                echo "Oops! Something went wrong: " . $e->getMessage();
                exit();
            }
        }
        // action add new date end

        // action add range dates start
        if($action=="save_range_dates"){ 
            try{
                $start_date = new DateTime($_POST['start_date']);
                $end_date = new DateTime($_POST['end_date']);
                $groupId = isset($_POST['group_id']) ? (int)$_POST['group_id'] : 0;
                
                if ($start_date <= $end_date) {
                    $interval = new DateInterval('P1D');
                    $period = new DatePeriod($start_date, $interval, $end_date->modify('+1 day'));
                    
                    foreach ($period as $date) {
                        $dateStr = $date->format('Y-m-d');
                        
                        // Check if exists for this date and group_id
                        $checkSql = "SELECT id FROM tbl_dates WHERE date = ? AND group_id = ?";
                        $resCheck = app_exec_getresult($checkSql, [$dateStr, $groupId], "si");
                        if ($resCheck && $resCheck->num_rows == 0) {
                            $insertSql = "INSERT INTO tbl_dates (date, group_id) VALUES (?, ?)";
                            app_exec_nonquery($insertSql, [$dateStr, $groupId], "si");
                        }

                        // Automatically clear any pre-existing attendance records for each holiday date
                        if ($groupId == 0) {
                            app_exec_nonquery("DELETE FROM tbl_attendance WHERE date = ?", [$dateStr], "s");
                        } else {
                            app_exec_nonquery("DELETE FROM tbl_attendance WHERE date = ? AND group_id = ?", [$dateStr, $groupId], "si");
                        }
                    }
                    echo "Saved successfully";
                } else {
                    http_response_code(400);
                    echo "Start date must be before or equal to End date.";
                }
                exit();
            }
            catch (Throwable $e) {
                http_response_code(500);
                echo "Oops! Something went wrong: " . $e->getMessage();
                exit();
            }
        }
        // action add range dates end

        if ($action == "load_data") {
            try {
                $rowsPerPage = 8;
                $current_page = isset($_POST['page']) ? (int)$_POST['page'] : 1;
                if ($current_page < 1) $current_page = 1;
                $offset = ($current_page - 1) * $rowsPerPage;
                $searchVal = trim($_POST['val'] ?? '');

                if (empty($searchVal)) {
                    $sqldatarows = "SELECT d.id, d.date, d.group_id, COALESCE(g.name, 'All Groups') AS group_name 
                                    FROM tbl_dates d 
                                    LEFT JOIN tbl_groups g ON d.group_id = g.id 
                                    ORDER BY d.date DESC 
                                    LIMIT $offset, $rowsPerPage";
                    $sqlcountrows = "SELECT COUNT(id) as total FROM tbl_dates";

                    $result = app_exec_query($sqldatarows);
                    $totalRowsResult = app_exec_query($sqlcountrows);
                    $totalRows = ($totalRowsResult && $row = $totalRowsResult->fetch_assoc()) ? (int)$row['total'] : 0;
                } else {
                    $month = (int)date('m', strtotime($searchVal));
                    $year  = (int)date('Y', strtotime($searchVal));

                    $sqldatarows = "SELECT d.id, d.date, d.group_id, COALESCE(g.name, 'All Groups') AS group_name 
                                    FROM tbl_dates d 
                                    LEFT JOIN tbl_groups g ON d.group_id = g.id 
                                    WHERE MONTH(d.date) = ? AND YEAR(d.date) = ? 
                                    ORDER BY d.date DESC 
                                    LIMIT $offset, $rowsPerPage";
                    $sqlcountrows = "SELECT COUNT(id) as total FROM tbl_dates WHERE MONTH(date) = ? AND YEAR(date) = ?";

                    $result = app_exec_getresult($sqldatarows, [$month, $year], "ii");
                    $totalRowsResult = app_exec_getresult($sqlcountrows, [$month, $year], "ii");
                    $totalRows = ($totalRowsResult && $row = $totalRowsResult->fetch_assoc()) ? (int)$row['total'] : 0;
                }

                $qrydata = [];
                if ($result && $result->num_rows > 0) {
                    while ($row = $result->fetch_assoc()) {
                        $qrydata[] = $row;
                    }
                }

                $pagination = array("total_rows" => $totalRows);
                $resdata = array($pagination, $qrydata);

                echo json_encode($resdata);
                exit();
            } catch (Throwable $e) {
                echo json_encode([["total_rows" => 0], []]);
                exit();
            }
        }
        // action load groups end
   
    }

?>