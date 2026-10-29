<?php
require_once __DIR__ . '/api_bootstrap.php';
include '../../app_pagination/pagination.php';

// actions start
if(isset($_POST['action']) && !empty($_POST['action'])) {
    $action = $_POST['action'];

    // action load member details starts
    if($action=="load_member_data"){
        try{
            $rowsPerPage = 8;
            $current_page = (int)$_POST['page'];
            // Pagination logic
            $sql="";  
            $sqlcountrows="";
            $sqldatarows="";   
    
            $offset = ($current_page - 1) * $rowsPerPage;
       
           
            $target_id = (int)($_SESSION['selected_member_id'] ?? ($_SESSION['selected_id'] ?? 0));
            if ($target_id <= 0 && !empty($_SESSION['user_id'])) {
                $target_id = (int)$_SESSION['user_id'];
            }
           
            $sqldatarows = "SELECT f.id, f.first_name, f.middle_name, f.last_name, f.gender, f.father_name, 
            f.mother_name, f.dob, f.phone, f.whtsapp, f.email, f.p_street, f.p_city, f.p_pincode, 
            f.p_country, f.img, f.inactive, b.name AS blood_group FROM tbl_members AS f 
            LEFT JOIN tbl_bloodgroup_master AS b ON f.blood_group = b.id WHERE f.id = ? LIMIT 1"; 

            $sqlcountrows = "SELECT Count(id) as total FROM tbl_members WHERE id = ?";
            
            $result = app_exec_getresult($sqldatarows, [$target_id], "i");
            $totalRowsResult = app_exec_getresult($sqlcountrows, [$target_id], "i");
            $totalRows = ($totalRowsResult && $tr_row = $totalRowsResult->fetch_assoc()) ? $tr_row['total'] : 0;
           
            
    
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
    // action load member details ends
}
// action ends
?>