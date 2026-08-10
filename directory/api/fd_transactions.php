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
                $sql = "DELETE FROM tbl_fd_transactions WHERE id = ?";
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
                $fd_no = $_POST['fd_no'];
                $bank_name = $_POST['bank_name'];
                $amount = (int)$_POST['amount'];
                $interest_rate = (float)$_POST['interest_rate'];
                $maturity_date = $_POST['maturity_date'];
                $maturity_amount = (int)$_POST['maturity_amount'];
                $status = $_POST['status'];
                $description = $_POST['description'];

                if($id == 0){
                    $sql = "INSERT INTO tbl_fd_transactions (date, fd_no, bank_name, amount, interest_rate, maturity_date, maturity_amount, status, description) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)";
                    $parameters = [$date, $fd_no, $bank_name, $amount, $interest_rate, $maturity_date, $maturity_amount, $status, $description];
                    app_exec_nonquery($sql, $parameters, "sssidsiss");
                    echo 'Saved Successfully';
                } else {
                    $sql = "UPDATE tbl_fd_transactions SET date = ?, fd_no = ?, bank_name = ?, amount = ?, interest_rate = ?, maturity_date = ?, maturity_amount = ?, status = ?, description = ? WHERE id = ?";
                    $parameters = [$date, $fd_no, $bank_name, $amount, $interest_rate, $maturity_date, $maturity_amount, $status, $description, $id];
                    app_exec_nonquery($sql, $parameters, "sssidsissi");
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
                
                $sqldatarows = "SELECT id, date, fd_no, bank_name, amount, interest_rate, maturity_date, maturity_amount, status, description, 
                                COALESCE((SELECT SUM(amount) FROM tbl_fd_interest_credits WHERE fd_id = tbl_fd_transactions.id), 0) AS interest_received 
                                FROM tbl_fd_transactions ORDER BY date DESC, id DESC LIMIT $offset , $rowsPerPage";
                $sqlcountrows = "SELECT COUNT(*) AS total FROM tbl_fd_transactions";
                
                $result = app_exec_query($sqldatarows);
                $totalRowsResult = app_exec_query($sqlcountrows);
                $totalRows = $totalRowsResult->fetch_assoc()['total'];
                
                $qrydata = [];
                if ($result && $result->num_rows > 0) {
                    while ($row = $result->fetch_assoc()) {
                        $qrydata[] = $row;
                    }
                }
        
                $sql_totals = "SELECT 
                    COALESCE((SELECT SUM(amount) FROM tbl_fd_transactions), 0) AS total_principal,
                    COALESCE((SELECT SUM(amount) FROM tbl_fd_interest_credits), 0) AS total_interest";
                $totalsResult = app_exec_query($sql_totals);
                $totalsRow = $totalsResult ? $totalsResult->fetch_assoc() : null;
                $totalPrincipal = $totalsRow ? (float)$totalsRow['total_principal'] : 0;
                $totalInterest = $totalsRow ? (float)$totalsRow['total_interest'] : 0;
        
                $pagination = array("total_rows" => $totalRows);
                $resdata = array(
                    $pagination, 
                    $qrydata, 
                    array(
                        "total_principal" => $totalPrincipal,
                        "total_interest" => $totalInterest,
                        "total_count" => $totalRows
                    )
                );
        
                echo json_encode($resdata);
                exit();
            }
            catch (Exception $e) {
                http_response_code(500);   
                echo "Oops! Something went wrong: " . $e->getMessage();
            }
        }

        // load interest credits
        if($action=="load_interest_credits"){
            try{
                $fd_id = (int)$_POST['fd_id'];
                $sql = "SELECT id, date, amount, description FROM tbl_fd_interest_credits WHERE fd_id = ? ORDER BY date DESC, id DESC";
                $result = app_exec_getresult($sql, [$fd_id], "i");
                $qrydata = [];
                if ($result && $result->num_rows > 0) {
                    while ($row = $result->fetch_assoc()) {
                        $qrydata[] = $row;
                    }
                }
                echo json_encode($qrydata);
                exit();
            }
            catch (Exception $e) {
                http_response_code(500);
                echo "Oops! Something went wrong: " . $e->getMessage();
            }
        }

        // save interest credit
        if($action=="save_interest_credit"){
            try{
                $fd_id = (int)$_POST['fd_id'];
                $date = $_POST['date'];
                $amount = (int)$_POST['amount'];
                $description = $_POST['description'];

                $sql = "INSERT INTO tbl_fd_interest_credits (fd_id, date, amount, description) VALUES (?, ?, ?, ?)";
                $parameters = [$fd_id, $date, $amount, $description];
                app_exec_nonquery($sql, $parameters, "isis");
                echo 'Saved Interest Successfully';
                exit();
            }
            catch (Exception $e) {
                http_response_code(500);
                echo "Oops! Something went wrong: " . $e->getMessage();
            }
        }

        // delete interest credit
        if($action=="delete_interest_credit"){
            try{
                $id = (int)$_POST['id'];
                $sql = "DELETE FROM tbl_fd_interest_credits WHERE id = ?";
                app_exec_nonquery($sql, [$id], "i");
                echo 'Deleted Successfully';
                exit();
            }
            catch (Exception $e) {
                http_response_code(500);
                echo "Oops! Something went wrong: " . $e->getMessage();
            }
        }

        // close fixed deposit
        if($action=="close_fd"){
            $db = new Database();
            $conn = $db->getConnection();
            $tm = new TransactionManager($conn);
            
            try{
                $fd_id = (int)$_POST['fd_id'];
                $date = $_POST['date'];
                $amount = (int)$_POST['amount'];
                $reference_no = $_POST['reference_no'];
                $description = $_POST['description'];

                $tm->begin();

                // 1. Update status of the FD to Closed
                $sql1 = "UPDATE tbl_fd_transactions SET status = 'Closed' WHERE id = ?";
                if (!app_exec_roll_back_nonquery($conn, $sql1, [$fd_id], "i")) {
                    throw new Exception("Failed to update FD status");
                }

                // 2. Insert the closed amount as Deposit in bank transactions
                $sql2 = "INSERT INTO tbl_bank_transactions (date, type, amount, reference_no, description) VALUES (?, 'Deposit', ?, ?, ?)";
                if (!app_exec_roll_back_nonquery($conn, $sql2, [$date, $amount, $reference_no, $description], "siss")) {
                    throw new Exception("Failed to record bank transaction");
                }

                $tm->commit();
                echo 'FD Closed and Amount Transferred to Bank Successfully';
                exit();
            }
            catch (Exception $e) {
                $tm->rollback();
                http_response_code(500);
                header('Content-Type: application/json');
                echo json_encode(array('Message' => $e->getMessage()));
                exit();
            }
        }
    }
?>
