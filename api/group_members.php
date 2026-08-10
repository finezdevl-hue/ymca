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

// actions start
if(isset($_POST['action']) && !empty($_POST['action'])) {
    $action = $_POST['action'];

    // action remove member from group starts
    if($action=="remove_member"){
        try{
            $member_id = $_POST['id'];
            // $sql = "UPDATE tbl_members SET group_id = 0 where id=?";
            $sql = "DELETE FROM tbl_group_member_map WHERE member_id = ? AND group_id=" . $_SESSION['id'];
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
    // action remove member from groups end

    // action to save fees of a member starts
    if($action=="save_fees"){ 
        try{
            
            // if($_POST['id']==0){
                $sql ="INSERT INTO tbl_member_recieved (member_id, fees, date, login_id, head, discription) VALUES (?, ?, ?, ?, ?, ?)";
                
                $parameters = [
                    $_POST['member_id'],
                    $_POST['fees'],
                    $_POST['date'],
                    // $_POST['to_date'],
                    $_SESSION['login_id'],
                    $_POST['head'],
                    $_POST['discription'],
                ];

                $types="iisiss";
            
            app_exec_nonquery($sql, $parameters, $types);
            
        }
        catch (Throwable $e) {
            throw new Exception('Oops! Something went wrong.');
        }
    }
    // action to save fees of a member ends

    //action load group members data start
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
                $sqldatarows ="SELECT m.first_name,m.middle_name,m.last_name,m.id,m.email,m.phone,whtsapp,img
                FROM tbl_members AS m JOIN tbl_group_member_map AS gmm ON m.id = gmm.member_id
                WHERE gmm.group_id =" . $_SESSION['id'];  
    
                $sqlcountrows = "SELECT COUNT(*) AS total FROM tbl_members m
                JOIN tbl_group_member_map gmm ON m.id = gmm.member_id WHERE gmm.group_id = " . $_SESSION['id'];
            }
            else{            
    
                $sqldatarows = "SELECT m.first_name,m.middle_name,m.last_name,m.id,m.email,m.phone,whtsapp,img
                FROM tbl_members AS m JOIN tbl_group_member_map AS gmm ON m.id = gmm.member_id
                WHERE first_name LIKE ? AND gmm.group_id =" . $_SESSION['id'];
    
                $sqlcountrows = "SELECT COUNT(*) AS total FROM tbl_members m
                JOIN tbl_group_member_map gmm ON m.id = gmm.member_id WHERE first_name LIKE ? AND gmm.group_id = " . $_SESSION['id'];
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
                    '%' . $searchvalue . '%', 
                                        
                ];
                
                $types="s";
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
        // catch (Exception $e) {
        //     http_response_code(500);   
        //     echo "Oops! Something went wrong." . $e->getMessage();
        //     return;
        // }
    }
    //action load group members data ends
}

?>