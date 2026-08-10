<?php
    session_start();
    include '../../app_common/db_connect.php';
    include '../../app_pagination/pagination.php';

    // actions start
    if(isset($_POST['action']) && !empty($_POST['action'])) {
        $action = $_POST['action'];


        // action to delete payment from wallet start
        if($action=="delete_payment"){
            try{
                $sql = "DELETE FROM tbl_wallet WHERE id = ?";
                $types="i";
                $parameters = [
                $_POST['id'],
                ];
                app_exec_nonquery($sql, $parameters, $types);


            }
            catch (Exception $e) {
                http_response_code(500);   
                echo "Oops! Something went wrong." . $e->getMessage();
                return;
            }
        }
        // action to delete payment from wallet end


        // action to load wallet amount details of a specific client start
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
                    // $sqldatarows = "SELECT f.id, f.first_name, f.middle_name, f.last_name, b.name AS blood_group FROM tbl_seo_members AS f JOIN tbl_bloodgroup_master AS b ON f.blood_group = b.id";
                    $sqldatarows = "SELECT id,date,amount,type FROM tbl_wallet WHERE client_id = ?";
                     
                    $sqlcountrows = "SELECT COUNT(*) AS total
                    FROM tbl_wallet WHERE client_id = ?";
                }
                else{            

                    $sqldatarows = "SELECT id,date,amount,type FROM tbl_wallet WHERE date = ? AND client_id = ?"; 

                    $sqlcountrows = "SELECT COUNT(*) AS total FROM tbl_wallet WHERE date = ? AND client_id = ?";         
                }    
                
                $sqldatarows .= " LIMIT $offset , $rowsPerPage ";
            
                if($_POST['val']== '') {
                    
                   $parameters = [
                      $_SESSION['id']  ,
                    ];
                    
                    $types="i";
                    $result=app_exec_getresult($sqldatarows,$parameters,$types);
                    
                    $totalRowsResult = app_exec_getresult($sqlcountrows,$parameters,$types);
                    $totalRows = $totalRowsResult->fetch_assoc()['total'];
                }
                else{
                    $searchvalue= $_POST['val'];
                    $parameters = [
                        $searchvalue,
                        $_SESSION['id'],
                    ];
                    
                    $types="si";
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
            catch (Exception $e) {
                http_response_code(500);   
                echo "Oops! Something went wrong." . $e->getMessage();
                return;
            }
        }
        // action to load proposal details of a specific client ends

    }
?>