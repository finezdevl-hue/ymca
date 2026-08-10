<?php
    session_start();
    include '../../app_common/db_connect.php';
    include '../../app_pagination/pagination.php';

    // actions start
    if(isset($_POST['action']) && !empty($_POST['action'])) {
        $action = $_POST['action'];

        // action to add the new payment details or update the details starts
        if($action=="save_items"){ 
            try{
                if($_POST['id']==0){
                    $sql ="INSERT INTO tbl_items (used_date, no_of_shuttle, total_item_amount, item_number) VALUES (?, ?, ?, ?)";

                    $parameters = [
                        $_POST['used_date'],
                        $_POST['no_of_shuttle'],
                        $_POST['total_item_amount'],
                        $_POST['item_number'],
                    
                    ];

                    $types="siis";
                }
                else{
                    $sql = "UPDATE tbl_items SET  used_date = ?, no_of_shuttle = ?, total_item_amount = ?, item_number = ? WHERE id = ?";

                    
                    $parameters = [
                        $_POST['used_date'],
                        $_POST['no_of_shuttle'],
                        $_POST['total_item_amount'],
                        $_POST['item_number'],
                        $_POST['id'],
                    ];

                    $types="siisi";
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
        if($action=="delete_item_details"){
            try{
               
                $sql = "DELETE FROM tbl_items WHERE id = ?";
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
                
                $sqldatarows ="SELECT id,used_date,no_of_shuttle, total_item_amount, item_number FROM tbl_items ORDER BY used_date DESC";
        
                $sqlcountrows = "SELECT COUNT(id) AS total FROM tbl_items ";
                
                
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