<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
include_once __DIR__ . '/../../app_common/db_connect.php';
include_once __DIR__ . '/../../app_pagination/pagination.php';


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

        // action update group tomorrow attendance start
        if($action=="update_group_tomorrow_attendance"){
            try{
                if (isset($_POST['id'], $_POST['allow_tomorrow_attendance'])) {    
                    $sql = "UPDATE tbl_groups SET allow_tomorrow_attendance=? WHERE id=?";
                    $parameters = [
                        (int)$_POST['allow_tomorrow_attendance'],
                        (int)$_POST['id'],        
                    ];
                    $types="ii";
                    app_exec_nonquery($sql,$parameters,$types);
                    echo json_encode(['status'=>'success', 'message'=>'Tomorrow attendance setting updated successfully.']);
                    exit();
                }
            }
            catch (Throwable $e) {
                echo json_encode(['status'=>'error', 'message'=>$e->getMessage()]);
                exit();
            }
        }
        // action update group tomorrow attendance end

        // action delete group start
        if($action=="delete_group"){
            try{
                $id = $_POST['id'];
                // $sql = "UPDATE tbl_members SET group_id = 0 where id=?";
                $sql = "DELETE FROM tbl_groups WHERE id = ?";
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

        // action add new group start
        if($action=="save_group"){ 
            try{
                
                if($_POST['id']==0){
                    $sql ="INSERT INTO tbl_groups (name, status) VALUES (?,1)";
                    $types="s";
                    $parameters = [
                    $_POST['group_name'],
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
        if($action=="load_group_data"){
            try{
                $rowsPerPage = 8;

                $current_page = (int)$_POST['page']; //isset($_GET['page']) ? (int)$_GET['page'] : 1;
                $group_status = (int)$_POST['group_status']; // isset($_GET['']) ? (int)$_GET['group_status'] : ;

                if($group_status==0){
                    $sql="SELECT COUNT(id) as total FROM tbl_groups";
                }
                else{
                    $sql="SELECT COUNT(id) as total FROM tbl_groups WHERE status=".$group_status;
                }

                $totalRowsResult = app_exec_query($sql);
                $totalRows = $totalRowsResult->fetch_assoc()['total'];

                $offset = ($current_page - 1) * $rowsPerPage;

                if($group_status==0){
                    $sql = "SELECT g.id, g.name, g.status, g.allow_tomorrow_attendance, (SELECT COUNT(gm.member_id) FROM tbl_group_member_map gm WHERE gm.group_id = g.id) AS member_count FROM tbl_groups g ORDER BY g.id DESC";
                }
                else{
                    $sql = "SELECT g.id, g.name, g.status, g.allow_tomorrow_attendance, (SELECT COUNT(gm.member_id) FROM tbl_group_member_map gm WHERE gm.group_id = g.id) AS member_count FROM tbl_groups g WHERE g.status=". $group_status . " ORDER BY g.id DESC";
                }
                
                $sql .= " LIMIT $offset, $rowsPerPage";
                $result = app_exec_query($sql);

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