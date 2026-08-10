<?php
    session_start();
    include '../../app_common/db_connect.php';
    include '../../app_pagination/pagination.php';

    // actions start
    if(isset($_POST['action']) && !empty($_POST['action'])) {
        $action = $_POST['action'];

        // delete transaction
        if($action=="delete_transaction"){
            try{
                $sql = "DELETE FROM tbl_bank_transactions WHERE id = ?";
                $parameters = [ $_POST['id'] ];
                app_exec_nonquery($sql, $parameters, "i");
                echo 'Deleted Successfully';
            }
            catch (Exception $e) {
                http_response_code(500);
                echo "Oops! Something went wrong: " . $e->getMessage();
            }
        }

        // save transaction
        if($action=="save_transaction"){ 
            try{
                $id = (int)$_POST['id'];
                $date = $_POST['date'];
                $type = $_POST['type'];
                $amount = (int)$_POST['amount'];
                $reference_no = $_POST['reference_no'];
                $description = $_POST['description'];

                if($id == 0){
                    $sql = "INSERT INTO tbl_bank_transactions (date, type, amount, reference_no, description) VALUES (?, ?, ?, ?, ?)";
                    $parameters = [$date, $type, $amount, $reference_no, $description];
                    app_exec_nonquery($sql, $parameters, "ssiss");
                    echo 'Saved Successfully';
                } else {
                    $sql = "UPDATE tbl_bank_transactions SET date = ?, type = ?, amount = ?, reference_no = ?, description = ? WHERE id = ?";
                    $parameters = [$date, $type, $amount, $reference_no, $description, $id];
                    app_exec_nonquery($sql, $parameters, "ssissi");
                    echo 'Updated Successfully';
                }
            }
            catch (Exception $e) {
                http_response_code(500);   
                echo "Oops! Something went wrong: " . $e->getMessage();
            }
        }

        // load data
        if($action=="load_data"){
            try{
                $rowsPerPage = 8;
                $current_page = (int)$_POST['page'];
                $offset = ($current_page - 1) * $rowsPerPage;
                
                $sqldatarows = "SELECT id, date, type, amount, reference_no, description FROM tbl_bank_transactions ORDER BY date DESC, id DESC LIMIT $offset , $rowsPerPage";
                $sqlcountrows = "SELECT COUNT(*) AS total FROM tbl_bank_transactions";
                
                $sqlbalance = "SELECT 
                                  COALESCE((SELECT SUM(amount) FROM tbl_bank_transactions WHERE type = 'Deposit'), 0) +
                                  COALESCE((SELECT SUM(amount) FROM tbl_bank_transactions WHERE type = 'Interest'), 0) -
                                  COALESCE((SELECT SUM(amount) FROM tbl_bank_transactions WHERE type = 'Withdrawal'), 0) AS bank_balance";
                
                $result = app_exec_query($sqldatarows);
                $totalRowsResult = app_exec_query($sqlcountrows);
                $totalRows = $totalRowsResult->fetch_assoc()['total'];
                
                $balanceResult = app_exec_query($sqlbalance);
                $bankBalance = $balanceResult ? $balanceResult->fetch_assoc()['bank_balance'] : 0;
                if ($bankBalance === null) $bankBalance = 0;
                
                $qrydata = [];
                if ($result && $result->num_rows > 0) {
                    while ($row = $result->fetch_assoc()) {
                        $qrydata[] = $row;
                    }
                }
        
                $pagination = array("total_rows" => $totalRows);
                $resdata = array($pagination, $qrydata, array("bank_balance" => $bankBalance));
        
                echo json_encode($resdata);
                exit();
            }
            catch (Exception $e) {
                http_response_code(500);   
                echo "Oops! Something went wrong: " . $e->getMessage();
            }
        }
    }
?>
