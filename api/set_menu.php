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

    // action starts
    if(isset($_POST['action']) && !empty($_POST['action'])) {
        $action = $_POST['action'];

        // action to load user data starts
        if($action=="load_login_data"){
            try{
                $rowsPerPage = 8;

                $current_page = (int)$_POST['page'];

                // Pagination logic
                $sql="";  
                $sqlcountrows="";
                $sqldatarows="";   
                
                $offset = ($current_page - 1) * $rowsPerPage;
                
                if($_POST['val']== ''){
                    $sqldatarows = "SELECT  login_id,name,email FROM tbl_login ";
                     
                    $sqlcountrows = "SELECT  Count(login_id) as total FROM tbl_login";
                }
                else{            

                    $sqldatarows = "SELECT  login_id,name,email FROM tbl_login WHERE name LIKE ?"; 

                    $sqlcountrows = "SELECT  Count(login_id) as total
                    FROM tbl_login WHERE name LIKE ? ";         
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
            
        }
        // action to load user data ends

        // function to load all menu starts
        if($action=="load_menu"){
            try{
                
                $sql = "SELECT id as menu_id,name FROM tbl_menu";

                $result = app_exec_query($sql);

                if ($result && $result->num_rows > 0) {
                    // Fetch all rows as an array
                    $qrydata = [];
                    while ($row = $result->fetch_assoc()) {
                        $qrydata[] = $row; // Add each row to the array
                    }
                }
                else {
                    $qrydata = []; // If no data is found
                }
                
                $resdata=array($qrydata);
                
                echo json_encode($resdata);
                exit();
                

            }
            catch (Throwable $e) {
                throw new Exception('Oops! Something went wrong.');
            }
           
        }
        //function to load all menu ends

        //fetch menu details of a user starts
        if($action=="fetch_menu_details"){
            try{
                
                if (isset($_POST['id'])) {
                    
                    $sql = "SELECT  menu_id FROM tbl_menu_map WHERE login_id=" . $_POST['id'];

                    $result = app_exec_query($sql);
                    
                }
                if ($result && $result->num_rows > 0) {
                    // Fetch all rows as an array
                    $data = [];
                    while ($row = $result->fetch_assoc()) {
                        $data[] = $row; // Add each row to the array
                    }
                } else {
                    $data = []; // If no data is found
                }
                
                echo json_encode($data);
                exit();
                
            }
            catch (Throwable $e) {
                throw new Exception('Oops! Something went wrong.');
            }
        }
        //fetch menu details of a user ends

        //action save menu for a user strats
        if($action=="save_menu"){
           
            try{
                if (isset($_POST['id'])) { 

                    $sql = "DELETE FROM tbl_menu_map WHERE login_id = ?";
                    $parameters = [
                        $_POST['id'],
                    ];
                    $types = "i";
                    $result = app_exec_nonquery($sql, $parameters, $types);

                    $menu_ids = $_POST['menu_ids']; 
                    $length = count($menu_ids);
                    for ($i = 0; $i < $length; $i++) {

                        $current_menu_ids = $menu_ids[$i]; 
                        
                        $sql = "INSERT INTO tbl_menu_map(login_id, menu_id, is_active) VALUES (?, ?, 1)";

                        $parameters = [
                            $_POST['id'],
                            $current_menu_ids,
                        ];

                        $types = "ii";

                        $result = app_exec_nonquery($sql, $parameters, $types);
                    }
                }
            }
            catch (Throwable $e) {
                throw new Exception('Oops! Something went wrong.');
            }
           
        }
        //action save menu for a user ends
    }
    // action ends
?>