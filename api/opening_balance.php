<?php
    session_start();
    include '../../app_common/db_connect.php';
    include '../../app_pagination/pagination.php';

    // actions start
    if(isset($_POST['action']) && !empty($_POST['action'])) {
        $action = $_POST['action'];

        // action to add the new Opening Balance start
        if($action=="save_payment"){ 
            try{
                if($_POST['id']==0){
                    $sql ="INSERT INTO tbl_opening_balance (date, amount) VALUES (?, ?)";

                    $parameters = [
                    $_POST['date'],
                    // $_POST['to_date'],
                    $_POST['amount'],
                    ];

                    $types="si";
                }
                else{
                    $sql = "UPDATE tbl_opening_balance SET  date = ?, amount = ? WHERE id = ?";

                    
                    $parameters = [
                    $_POST['date'],
                    // $_POST['to_date'],
                    $_POST['amount'],
                    $_POST['id'],
                    ];

                    $types="sii";
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
        // action to add the new Opening Balance end

        //action to delete Opening Balance start
        if($action=="delete_payment_details"){
            try{
                $id = $_POST['id'];
                $sql = "DELETE FROM tbl_opening_balance WHERE id = ?";
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
        //action to delete Opening Balance end

        // action to load all Opening Balance by date start
        if($action=="load_data"){
            try{
                $rowsPerPage = 8;
                $current_page = (int)$_POST['page'];
                // Pagination logic
                $sql="";  
                $sqlcountrows="";
                $sqldatarows="";   
        
                $offset = ($current_page - 1) * $rowsPerPage;
                
                $sqldatarows ="SELECT id, date, amount FROM tbl_opening_balance ORDER BY id DESC";
        
                $sqlcountrows = "SELECT COUNT(id) AS total FROM tbl_opening_balance ";
                
                
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
        // action to load all Openeing Balance by date end
    }
?>