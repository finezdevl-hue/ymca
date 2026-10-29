<?php
require_once __DIR__ . '/api_bootstrap.php';
include '../../app_pagination/pagination.php';

    // action start
    if(isset($_POST['action']) && !empty($_POST['action'])) {
    //   echo $_POST['client_name'];
        $action = $_POST['action'];

        //action to delete family starts
        if($action=="delete_family"){
            try{
                $target_group_id = (int)($_SESSION['selected_group_id'] ?? 0);
                $family_id = (int)($_POST['id'] ?? 0);
                $sql = "DELETE FROM tbl_family_group_map WHERE family_id = ? AND group_id = ?";
                $types="ii";
                $parameters = [$family_id, $target_group_id];
                app_exec_nonquery($sql, $parameters, $types);
                echo json_encode(['status' => 'success']);
                exit();
            }
            catch (Throwable $e) {
                throw new Exception('Oops! Something went wrong.');
            }
        }
        //action to delte family ends
        

      
        // action to load family details starts
        if($action=="load_family_data"){
            try{
                $target_group_id = (int)($_SESSION['selected_group_id'] ?? 0);
                $rowsPerPage = 8;
                $current_page = (int)$_POST['page'];
                $offset = ($current_page - 1) * $rowsPerPage;
                
                if($_POST['val']== ''){
                    $sqldatarows ="SELECT f.id,f.name FROM tbl_family AS f JOIN tbl_family_group_map AS gmm ON f.id = gmm.family_id
                    WHERE gmm.group_id = ? LIMIT ?, ?";
                     
                    $sqlcountrows = "SELECT COUNT(*) AS total FROM tbl_family m
                    JOIN tbl_family_group_map gmm ON m.id = gmm.family_id WHERE gmm.group_id = ?";

                    $result = app_exec_getresult($sqldatarows, [$target_group_id, $offset, $rowsPerPage], "iii");
                    $totalRowsResult = app_exec_getresult($sqlcountrows, [$target_group_id], "i");
                    $totalRows = ($totalRowsResult && $tr = $totalRowsResult->fetch_assoc()) ? $tr['total'] : 0;
                }
                else{            
                    $search = '%' . $_POST['val'] . '%';
                    $sqldatarows ="SELECT f.id,f.name FROM tbl_family AS f JOIN tbl_family_group_map AS gmm ON f.id = gmm.family_id
                    WHERE name LIKE ? AND gmm.group_id = ? LIMIT ?, ?";

                    $sqlcountrows = "SELECT COUNT(*) AS total FROM tbl_family m
                    JOIN tbl_family_group_map gmm ON m.id = gmm.family_id WHERE name LIKE ? AND gmm.group_id = ?";

                    $result = app_exec_getresult($sqldatarows, [$search, $target_group_id, $offset, $rowsPerPage], "siii");
                    $totalRowsResult = app_exec_getresult($sqlcountrows, [$search, $target_group_id], "si");
                    $totalRows = ($totalRowsResult && $tr = $totalRowsResult->fetch_assoc()) ? $tr['total'] : 0;
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