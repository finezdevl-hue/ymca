<?php
session_start();
include_once __DIR__ . '/../app_common/db_connect.php';
include_once __DIR__ . '/../app_common/auth_helper.php';
include_once __DIR__ . '/../app_pagination/pagination.php';

$current_login = (int)($_SESSION['login_id'] ?? 0);
if (empty($current_login) || isNormalMember($current_login)) {
    http_response_code(403);
    echo json_encode(['status' => 'error', 'message' => 'Unauthorized access.']);
    exit();
}


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
                if($id == 0){
                    $sql ="INSERT INTO tbl_dates (date) VALUES (?)";
                    $types="s";
                    $parameters = [
                        $_POST['date'],
                    ];
                }
                else{
                    $sql = "UPDATE tbl_dates SET date = ?  WHERE id = ?";
                    $types="si";
                    $parameters = [
                        $_POST['date'],
                        $id,
                    ];
                }
                app_exec_nonquery($sql, $parameters, $types);
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

        if ($action == "load_data") {
            try {
                $rowsPerPage = 8;
                $current_page = isset($_POST['page']) ? (int)$_POST['page'] : 1;
                if ($current_page < 1) $current_page = 1;
                $offset = ($current_page - 1) * $rowsPerPage;
                $searchVal = trim($_POST['val'] ?? '');

                if (empty($searchVal)) {
                    $sqldatarows = "SELECT id, date FROM tbl_dates ORDER BY date DESC LIMIT $offset, $rowsPerPage";
                    $sqlcountrows = "SELECT COUNT(id) as total FROM tbl_dates";

                    $result = app_exec_query($sqldatarows);
                    $totalRowsResult = app_exec_query($sqlcountrows);
                    $totalRows = ($totalRowsResult && $row = $totalRowsResult->fetch_assoc()) ? (int)$row['total'] : 0;
                } else {
                    $month = (int)date('m', strtotime($searchVal));
                    $year  = (int)date('Y', strtotime($searchVal));

                    $sqldatarows = "SELECT id, date FROM tbl_dates WHERE MONTH(date) = ? AND YEAR(date) = ? ORDER BY date DESC LIMIT $offset, $rowsPerPage";
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