<?php
session_start();
include '../../app_common/db_connect.php';
include '../../app_pagination/pagination.php';

    // action start
    if(isset($_POST['action']) && !empty($_POST['action'])) {
        $action = $_POST['action'];

        // action to save amount to the wallet of a specific client start
        if($action == "save_wallet") { 
            try {
               
                $sql = "INSERT INTO tbl_wallet (client_id, date, amount, type) VALUES (?, ?, ?, ?)";
                $parameters = [
                    $_POST['id'],
                    $_POST['date'],
                    $_POST['amount'],
                    $_POST['selected_type'],
                ];
                $types = "isis";
                app_exec_nonquery($sql, $parameters, $types);
               
            } catch (Exception $e) {
                http_response_code(500);   
                echo "Oops! Something went wrong." . $e->getMessage();
                return;
            }
        }
        // action to save amount to the wallet of a specific client end
        
        // acion to load client details start
        if($action=="load_clients_data"){
            try{
                $rowsPerPage = 8;

                $current_page = (int)$_POST['page'];

                // Pagination logic
                $sql="";  
                $sqlcountrows="";
                $sqldatarows="";   
                
                $offset = ($current_page - 1) * $rowsPerPage;
                
                if($_POST['val']== ''){
                    // $sqldatarows = "SELECT f.id, f.first_name, f.middle_name, f.last_name, b.name AS blood_group FROM tbl_seo_members AS f JOIN tbl_bloodgroup_master AS b ON f.blood_group = b.id";
                    $sqldatarows = "SELECT  id,first_name,last_name,middle_name FROM tbl_members";
                     
                    $sqlcountrows = "SELECT  Count(id) as total FROM tbl_members";
                }
                else{            

                    $sqldatarows = "SELECT  id,first_name,last_name,middle_name FROM tbl_members WHERE first_name LIKE ? OR middle_name LIKE ? OR last_name like ?"; 

                    $sqlcountrows = "SELECT  Count(id) as total
                    FROM tbl_members WHERE first_name LIKE ? OR middle_name LIKE ? OR last_name like ?";         
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
        // action to load client details end
    }
?>