<?php
    session_start();
    include '../../app_common/db_connect.php';
    include '../../app_pagination/pagination.php';

    // actions start
    if(isset($_POST['action']) && !empty($_POST['action'])) {
        $action = $_POST['action'];

        // action to add the new payment details or update the details starts
        if($action=="save_year"){ 
            try{
                if($_POST['id']==0){
                    $sql ="INSERT INTO tbl_closing (from_year, to_year, hide) VALUES (?, ?, 0)";

                    $parameters = [
                        $_POST['from_year'],
                        $_POST['to_year'],
                    ];

                    $types="ii";
                }
                else{
                    $sql = "UPDATE tbl_closing SET  from_year = ?, to_year = ? WHERE id = ?";

                    
                    $parameters = [
                        $_POST['from_year'],
                        $_POST['to_year'],
                        $_POST['id'],
                    ];

                    $types="iii";
                }
                app_exec_nonquery($sql, $parameters, $types);
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
        // action to add the new payment details or update the details ends

        //action to delete payment details start
        if($action=="hide_closing_year"){
            try{
                echo $_POST['hide'];
            
                $sql = "UPDATE  tbl_closing SET hide = ? WHERE id = ?";
                
                $parameters = [
                    $_POST['hide'],
                    $_POST['id'],
                ];
                $types="ii";
                app_exec_nonquery($sql, $parameters, $types);
    
            }
            catch (Throwable $e) {
                throw new Exception('Oops! Something went wrong.');
            }
        }
        //action to delete payment details end

        // action to load all payment details of master table starts
        if($action=="load_data"){
            try{
                $rowsPerPage = 8;
                $current_page = (int)$_POST['page'];
                // Pagination logic
                $sql="";  
                $sqlcountrows="";
                $sqldatarows="";   
        
                $offset = ($current_page - 1) * $rowsPerPage;
                
                $sqldatarows ="SELECT id, from_year, to_year, hide FROM tbl_closing";
        
                $sqlcountrows = "SELECT COUNT(id) AS total FROM tbl_closing ";
                
                
                $sqldatarows .= " LIMIT $offset , $rowsPerPage ";
        
                
                $result = app_exec_query($sqldatarows);
                    
                $totalRowsResult = app_exec_query($sqlcountrows);
                $totalRows = $totalRowsResult->fetch_assoc()['total'];
                
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
        // action to load all payment details of master table ends
    }
?>