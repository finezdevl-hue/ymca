<?php
session_start();
require_once('../../invoice/tcpdf/tcpdf.php');
include '../../app_common/db_connect.php';
include '../../app_common/db_name.php';
include '../../app_common/enums.php';
// include '../../invoice/pdf.php';
include '../../app_pagination/pagination.php';

    

    // action start
    if(isset($_POST['action']) && !empty($_POST['action'])) {
        $action = $_POST['action'];

        // action to load members for pay fees start
        if($action=="load_members_data"){
            try{
                $rowsPerPage = 8;

                $current_page = (int)$_POST['page'];

                // Pagination logic
                $sql="";  
                $sqlcountrows="";
                $sqldatarows="";   
                
                $offset = ($current_page - 1) * $rowsPerPage;
                
                if($_POST['val']== ''){
                    $sqldatarows = "SELECT id,first_name,middle_name,last_name,img FROM tbl_members";
                     
                    $sqlcountrows = "SELECT COUNT(*) AS total FROM tbl_members";
                }
                else{            

                    $sqldatarows = "SELECT id,first_name,middle_name,last_name,img FROM tbl_members
                        WHERE first_name LIKE ? OR middle_name LIKE ? OR last_name like ?"; 

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
                        '%' . $searchvalue . '%',
                        '%' . $searchvalue . '%' 
                    ];
                    
                    $types="sss";
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
        // action to load members for pay fees end

    }
    // action end
?>