<?php
require_once __DIR__ . '/api_bootstrap.php';
include '../../app_pagination/pagination.php';

    // actions start
    if(isset($_POST['action']) && !empty($_POST['action'])) {
        $action = $_POST['action'];

        // action to update the feee details starts
        if($action=="save_fees"){ 
            try{
                
                $sql ="UPDATE tbl_member_recieved SET date = ?,fees = ?, head = ?, discription = ? WHERE id = ? ";
                
                $parameters = [
                $_POST['date'],
                $_POST['fees'],
                $_POST['head'],
                $_POST['discription'],
                $_POST['id'],
                ];

                $types="ssisi";
                
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
        // action to update the fee details ends

        // action to delete fee details starts
        // if($action=="delete_fee_details"){
        //     try{
        //         $id = $_POST['id'];
        //         $sql = "DELETE FROM tbl_member_recieved WHERE id = ?";
        //         $types="i";
        //         $parameters = [
        //             $_POST['id'],
        //         ];
    
        //         app_exec_nonquery($sql, $parameters, $types);
    
        //     }
        //     catch (Throwable $e) {
        //         throw new Exception('Oops! Something went wrong.');
        //     }
        // }
        // action to delete fee details ends
         // action to delete recieved amount start
        if($action=="delete_fee_details"){
            try{
                $tm->begin();

                $del_id = (int)$_POST['id'];
                $rec_chk = app_exec_getresult("SELECT receiveble_id FROM tbl_member_recieved WHERE id = ?", [$del_id], "i");
                $receiveble_id = ($rec_chk && $rrow = $rec_chk->fetch_assoc()) ? (int)$rrow['receiveble_id'] : (int)($_POST['receiveble_id'] ?? 0);

                $sql = "DELETE FROM tbl_member_recieved WHERE id = ?";
                $types = "i";
                $parameters = [$del_id];
                
                if (!app_exec_roll_back_nonquery($conn, $sql, $parameters, $types)) {
                    throw new Exception("Error deleting payment record");
                }

                if ($receiveble_id > 0) {
                    $sql = "SELECT p.id, p.fees AS total_recieveble_Amount, COALESCE(SUM(pd.fees), 0.00) AS total_recieved_amount
                            FROM tbl_member_recievable AS p
                            LEFT JOIN tbl_member_recieved AS pd ON p.id = pd.receiveble_id AND (pd.cancel = 0 OR pd.cancel IS NULL)
                            WHERE p.id = ?
                            GROUP BY p.id";
                    $result = app_exec_getresult($sql, [$receiveble_id], "i");

                    if ($result && $row = $result->fetch_assoc()) {
                        $total_recieveble_Amount = (float)$row["total_recieveble_Amount"];
                        $total_recieved_amount = (float)$row["total_recieved_amount"];
                        $isComplete = ((int)round($total_recieved_amount * 100) >= (int)round($total_recieveble_Amount * 100)) ? 1 : 0;
                        
                        $sql_up = "UPDATE tbl_member_recievable SET iscomplete = ? WHERE id = ?";
                        if (!app_exec_roll_back_nonquery($conn, $sql_up, [$isComplete, $receiveble_id], "ii")) {
                            throw new Exception("Error in Updation");
                        }
                    }
                }

                $tm->commit();
                echo 'Deleted Sucessfully';
            }
            catch (Exception $e) {
                $tm->rollback();
                echo $e->getMessage();
            }
        }
        // action to delete recieved amount end

        // action to load fee details of a member starts
        if($action=="load_data"){
            try{
                $rowsPerPage = 8;
                $current_page = (int)$_POST['page'];
                // Pagination logic
                $sql="";  
                $sqlcountrows="";
                $sqldatarows="";   
        
                $offset = ($current_page - 1) * $rowsPerPage;

                $active_year_start = '2000-04-01';
                $active_yr_res = app_exec_query("SELECT from_year FROM tbl_closing ORDER BY from_year DESC LIMIT 1");
                if ($active_yr_res && $row_yr = $active_yr_res->fetch_assoc()) {
                    $active_year_start = $row_yr['from_year'] . "-04-01";
                }
        
                $sqldatarows = "SELECT
                    mr.date AS date,
                    mr.fees AS billed_amount,
                    COALESCE(SUM(CASE WHEN f.fees > 0 THEN f.fees ELSE 0 END), 0) AS amount_received,
                    g.name AS head,
                    mr.discription,
                    mr.iscomplete,
                    mr.cancel,
                    mr.id AS receivable_id
                FROM tbl_member_recievable AS mr
                LEFT JOIN tbl_payment_head_master AS g ON mr.head = g.id
                LEFT JOIN tbl_member_recieved AS f ON mr.id = f.receiveble_id AND f.fees > 0 AND (f.cancel = 0 OR f.cancel IS NULL)
                WHERE mr.member_id = ? AND (mr.date >= ? OR (mr.iscomplete = 0 AND (mr.cancel = 0 OR mr.cancel IS NULL)))
                GROUP BY mr.id
                ORDER BY mr.cancel ASC, mr.date DESC";

                $sqlcountrows = "SELECT COUNT(*) AS total
                FROM tbl_member_recievable
                WHERE member_id = ? AND (date >= ? OR (iscomplete = 0 AND (cancel = 0 OR cancel IS NULL)))";
                
                $sqldatarows_query = $sqldatarows . " LIMIT $offset , $rowsPerPage ";
                $target_member_id = !empty($_POST['member_id']) ? (int)$_POST['member_id'] : (!empty($_SESSION['member_id']) ? (int)$_SESSION['member_id'] : 0);
        
                $result = app_exec_getresult($sqldatarows_query, [$target_member_id, $active_year_start], "is");
                    
                $totalRowsResult = app_exec_getresult($sqlcountrows, [$target_member_id, $active_year_start], "is");
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
        // action to load fee details of a member ends

        if($action=="load_heads"){
            try{
                $sql = "SELECT id, name FROM tbl_payment_head_master WHERE type = 'Credit'";
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
    }
?>