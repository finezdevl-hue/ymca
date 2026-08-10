<?php
session_start();
include '../../app_common/db_connect.php';
include '../../app_pagination/pagination.php';

    // action start
    if(isset($_POST['action']) && !empty($_POST['action'])) {

        $action = $_POST['action'];

        // action to save new profile or update new profile starts
        if($action=="save_profile"){
            try{
                
                if($_POST['id']==0){
                    $sql ="INSERT INTO tbl_profile (name,phone,email,street,city,pincode,country,img) VALUES (?, ?, ?, ?, ?, ?, ?, ?)";
                    $parameters = [
                    $_POST['name'],
                    $_POST['phone'],
                    $_POST['email'],
                    
                    $_POST['street'],
                    $_POST['city'],
                    $_POST['pincode'],
                    $_POST['country'],

                    $_POST['img'],
                    ];

                    $types="sssssiss";
                
                }

                else{
                    $sql = "UPDATE tbl_profile SET  name = ?, phone = ?, email = ?, street =  ?, city = ?, pincode = ?, country = ?, img = ?  WHERE id = ?";
                    
                    $parameters = [
                    $_POST['name'],

                    $_POST['phone'],
                    $_POST['email'],

                    $_POST['street'],
                    $_POST['city'],
                    $_POST['pincode'],
                    $_POST['country'],
                    $_POST['img'],

                    $_POST['id'],
                    ];

                    $types="sssssissi";
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
        // action to save new profile or update new profile ends
             
        // action to load all profile starts
        if($action=="load_profile_data"){
            try{
                $rowsPerPage = 8;

                $current_page = (int)$_POST['page'];

                // Pagination logic
                $sql="";  
                $sqlcountrows="";
                $sqldatarows="";   
                
                $offset = ($current_page - 1) * $rowsPerPage;
                
               
                $sqldatarows = "SELECT  id,name,phone,email,street,city,pincode,country,img FROM tbl_profile ";
                     
                $sqlcountrows = "SELECT  Count(id) as total FROM tbl_profile";
               
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
            catch (Throwable $e) {
                throw new Exception('Oops! Something went wrong.');
            }
            
        }
        // action to load all profile ends

    }
?>