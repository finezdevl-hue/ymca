<?php
    session_start();
    include '../../app_common/db_connect.php';
    include '../../app_pagination/pagination.php';

    // actions start
    if(isset($_POST['action']) && !empty($_POST['action'])) {
        $action = $_POST['action'];

        // action to update the fee details of a member starts
        if($action=="save_fees"){ 
            try{
                
                $sql ="UPDATE tbl_fee_master SET from_date = ?, to_date = ?, fee = ? WHERE id = ? ";
                
                $parameters = [
                $_POST['from_date'],
                $_POST['to_date'],
                $_POST['fees'],
                $_POST['id'],
                ];

                $types="ssii";
                
                app_exec_nonquery($sql, $parameters, $types);
                
            }
            catch (Exception $e) {
                http_response_code(500);   
                echo "Oops! Something went wrong." . $e->getMessage();
                return;
            }
        }
        // action to update the fee details of a member ends

        // action to add the new fee details of a member starts
        if($action=="save_new_fees"){ 
            try{
                
                $sql ="INSERT INTO tbl_fee_master (from_date, to_date, fee, group_id) VALUES (?, ?, ?, ?)";
                
                $parameters = [
                $_POST['from_date'],
                $_POST['to_date'],
                $_POST['fees'],
                $_POST['group_id'],
                ];

                $types="ssii";
                app_exec_nonquery($sql, $parameters, $types);
            }
            catch (Exception $e) {
                http_response_code(500);   
                echo "Oops! Something went wrong." . $e->getMessage();
                return;
            }
        }
        // action to add the new fee details of a member ends

        //action to delete fee details start
        if($action=="delete_fee_details"){
            try{
                $id = $_POST['id'];
                $sql = "DELETE FROM tbl_fee_master WHERE id = ?";
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
        //action to delete fee details end

        // action to load  groups for dropdown in the popup starts
        if($action=="load_groups"){
            try{
                $sql = "SELECT id, name FROM tbl_groups WHERE status = 1";
                $result = app_exec_query($sql);

                if ($result && $result->num_rows > 0) {
                    // Fetch all rows as an array
                    $qrydata = [];
                    while ($row = $result->fetch_assoc()) {
                        $qrydata[] = $row; // Add each row to the array
                    }
                } else {
                    $qrydata = []; // If no data is found
                }
        
                // $pagination = array("total_rows"=>$totalRows);
                $resdata=array($qrydata);
        
                echo json_encode($resdata);
                exit();
                
            }
            catch (Throwable $e) {
                throw new Exception('Oops! Something went wrong.');
            }
        }
        // action to load groups for dropdown in the popup ends

        // action to load all fee details starts
        if($action=="load_data"){
            try{
                $rowsPerPage = 8;
                $current_page = (int)$_POST['page'];
                // Pagination logic
                $sql="";  
                $sqlcountrows="";
                $sqldatarows="";   
        
                $offset = ($current_page - 1) * $rowsPerPage;
                
                $sqldatarows ="SELECT f.id,f.from_date,f.to_date,f.fee,g.name
                FROM tbl_fee_master as f JOIN tbl_groups AS g ON f.group_id = g.id WHERE f.group_id = g.id  order by from_date DESC";
        
                $sqlcountrows = "SELECT COUNT(*) AS total FROM tbl_fee_master ";
                
                
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
            catch (Exception $e) {
                http_response_code(500);   
                echo "Oops! Something went wrong." . $e->getMessage();
                return;
            }
        }
        // action to load all fee details ends
    }
?>