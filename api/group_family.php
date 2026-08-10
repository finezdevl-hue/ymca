<?php
session_start();
include '../../app_common/db_connect.php';
include '../../app_pagination/pagination.php';

    // action start
    if(isset($_POST['action']) && !empty($_POST['action'])) {
    //   echo $_POST['client_name'];
        $action = $_POST['action'];

        //action to delete family starts
        if($action=="delete_family"){
            try{
                echo $_POST['id'];
                // $sql = "UPDATE tbl_members SET group_id = 0 where id=?";
                $sql = "DELETE FROM tbl_family_group_map WHERE family_id = ? AND group_id= ". $_SESSION['id'];
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
        //action to delte family ends
        

      
        // action to load family details starts
        if($action=="load_family_data"){
            try{
                $rowsPerPage = 8;

                $current_page = (int)$_POST['page'];

                // Pagination logic
                $sql="";  
                $sqlcountrows="";
                $sqldatarows="";   
                
                $offset = ($current_page - 1) * $rowsPerPage;
                
                if($_POST['val']== ''){
                    $sqldatarows ="SELECT f.id,f.name FROM tbl_family AS f JOIN tbl_family_group_map AS gmm ON f.id = gmm.family_id
                    WHERE gmm.group_id =" . $_SESSION['id'];
                     
                    $sqlcountrows = "SELECT COUNT(*) AS total FROM tbl_family m
                    JOIN tbl_family_group_map gmm ON m.id = gmm.family_id WHERE gmm.group_id = " . $_SESSION['id'];
                }
                else{            

                    $sqldatarows ="SELECT f.id,f.name FROM tbl_family AS f JOIN tbl_family_group_map AS gmm ON f.id = gmm.family_id
                    WHERE name LIKE ? AND gmm.group_id =" . $_SESSION['id'];

                    $sqlcountrows = "SELECT COUNT(*) AS total FROM tbl_family m
                    JOIN tbl_family_group_map gmm ON m.id = gmm.family_id WHERE name LIKE ? AND gmm.group_id = " . $_SESSION['id'];       
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
                        '%' . $searchvalue . '%' 
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
            // catch (Throwable $e) {
            //     throw new Exception('Oops! Something went wrong.');
            // }
            catch (Exception $e) {
                http_response_code(500);   
                echo "Oops! Something went wrong." . $e->getMessage();
                return;
            }
        }
        // action to load family details ends

    }
?>