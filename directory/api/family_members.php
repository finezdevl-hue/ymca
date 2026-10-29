<?php
require_once __DIR__ . '/api_bootstrap.php';
include '../../app_pagination/pagination.php';

// actions start
if(isset($_POST['action']) && !empty($_POST['action'])) {
    $action = $_POST['action'];

    // action remove a member from family starts
    if($action=="remove_member"){
        try{
            $member_id = (int)$_POST['id'];
            $target_family_id = (int)($_SESSION['selected_family_id'] ?? 0);
            $sql = "DELETE FROM tbl_family_member_map WHERE member_id = ? AND family_id = ?";
            $types="ii";
            $parameters = [$member_id, $target_family_id];
            app_exec_nonquery($sql, $parameters, $types);
            echo json_encode(['status' => 'success']);
            exit();
        }
        catch (Throwable $e) {
            throw new Exception('Oops! Something went wrong.');
        }
    }
    // action remove a member from family ends

    //action load family members data starts
    if($action=="load_data"){
        try{
            $target_family_id = (int)($_SESSION['selected_family_id'] ?? 0);
            $rowsPerPage = 8;
            $current_page = (int)$_POST['page'];
            $offset = ($current_page - 1) * $rowsPerPage;
       
            if($_POST['val']== ''){
                $sqldatarows ="SELECT m.first_name,m.middle_name,m.last_name,m.id,m.email,m.phone,m.whtsapp,m.img
                FROM tbl_members AS m JOIN tbl_family_member_map AS fm ON m.id = fm.member_id
                WHERE fm.family_id = ? order by m.dob LIMIT ?, ?";
    
                $sqlcountrows = "SELECT COUNT(*) AS total FROM tbl_members m
                JOIN tbl_family_member_map fm ON m.id = fm.member_id WHERE fm.family_id = ?";

                $result = app_exec_getresult($sqldatarows, [$target_family_id, $offset, $rowsPerPage], "iii");
                $totalRowsResult = app_exec_getresult($sqlcountrows, [$target_family_id], "i");
                $totalRows = ($totalRowsResult && $tr = $totalRowsResult->fetch_assoc()) ? $tr['total'] : 0;
            }
            else{            
                $search = '%' . $_POST['val'] . '%';
                $sqldatarows = "SELECT m.first_name,m.middle_name,m.last_name,m.id,m.email,m.phone,m.whtsapp,m.img
                FROM tbl_members AS m JOIN tbl_family_member_map AS fm ON m.id = fm.member_id
                WHERE m.first_name LIKE ? AND fm.family_id = ? order by m.dob LIMIT ?, ?";
    
                $sqlcountrows = "SELECT COUNT(*) AS total FROM tbl_members m
                JOIN tbl_family_member_map fm ON m.id = fm.member_id Where m.first_name LIKE ? AND family_id = ?";

                $result = app_exec_getresult($sqldatarows, [$search, $target_family_id, $offset, $rowsPerPage], "siii");
                $totalRowsResult = app_exec_getresult($sqlcountrows, [$search, $target_family_id], "si");
                $totalRows = ($totalRowsResult && $tr = $totalRowsResult->fetch_assoc()) ? $tr['total'] : 0;
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
        // catch (Throwable $e) {
        //     throw new Exception('Oops! Something went wrong.');
        // }
        catch (Exception $e) {
            http_response_code(500);   
            echo "Oops! Something went wrong." . $e->getMessage();
            return;
        }
    }
    //action load family members data ends
}
// action ends
?>