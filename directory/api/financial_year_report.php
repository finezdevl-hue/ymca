<?php
    session_start();
    include_once __DIR__ . '/../../app_common/db_connect.php';
    include_once __DIR__ . '/../../app_pagination/pagination.php';

    function get_year_closing_balance($year_id) {
        if ($year_id <= 0) return 0.0;

        // Get year dates
        $sql = "SELECT id, from_year, to_year FROM tbl_closing WHERE id = ?";
        $yearRow = app_exec_getresult($sql, [$year_id], "i")->fetch_assoc();
        if (!$yearRow) return 0.0;

        $from_year = (int)$yearRow['from_year'];
        $to_year   = (int)$yearRow['to_year'];
        $start_date = $from_year . "-04-01";
        $end_date   = $to_year   . "-03-31";

        // Opening balance: initial OB for year 1, or previous year's closing for year N
        if ($year_id == 1) {
            $init_ob_res = app_exec_query("SELECT amount FROM tbl_opening_balance WHERE isactive = 1 LIMIT 1");
            $opening_balance = $init_ob_res ? (double)$init_ob_res->fetch_assoc()['amount'] : 6780.00;
        } else {
            $opening_balance = get_year_closing_balance($year_id - 1);
        }

        // Current year: all member fees received (active + archived)
        $res_member = app_exec_getresult("
            SELECT SUM(fees) as total FROM (
                SELECT fees FROM tbl_member_recieved WHERE date BETWEEN ? AND ? AND (cancel = 0 OR cancel IS NULL)
                UNION ALL
                SELECT fees FROM tbl_member_recieved_old WHERE date BETWEEN ? AND ? AND (cancel = 0 OR cancel IS NULL)
            ) AS combined
        ", [$start_date, $end_date, $start_date, $end_date], "ssss");
        $total_member_fees = $res_member ? (double)$res_member->fetch_assoc()['total'] : 0.0;

        // Current year: other income received (active + archived)
        $res_other = app_exec_getresult("
            SELECT SUM(amount) as total FROM (
                SELECT amount FROM tbl_other_recieved WHERE date BETWEEN ? AND ? AND (cancel = 0 OR cancel IS NULL)
                UNION ALL
                SELECT amount FROM tbl_other_recieved_old WHERE date BETWEEN ? AND ? AND (cancel = 0 OR cancel IS NULL)
            ) AS combined
        ", [$start_date, $end_date, $start_date, $end_date], "ssss");
        $other_rec = $res_other ? (double)$res_other->fetch_assoc()['total'] : 0.0;

        // Current year: wallet net credits
        $res_wallet = app_exec_getresult("
            SELECT
                SUM(CASE WHEN type = 'credit' THEN amount ELSE 0 END) as credits,
                SUM(CASE WHEN type = 'debit'  THEN amount ELSE 0 END) as debits
            FROM tbl_wallet WHERE date BETWEEN ? AND ?
        ", [$start_date, $end_date], "ss");
        $row_wallet = $res_wallet ? $res_wallet->fetch_assoc() : null;
        $wallet_net = $row_wallet ? ((double)$row_wallet['credits'] - (double)$row_wallet['debits']) : 0.0;

        // Current year: expenses paid (active + archived)
        $res_paid = app_exec_getresult("
            SELECT SUM(amount) as total FROM (
                SELECT amount FROM tbl_paid WHERE date BETWEEN ? AND ? AND (cancel = 0 OR cancel IS NULL)
                UNION ALL
                SELECT amount FROM tbl_paid_old WHERE date BETWEEN ? AND ? AND (cancel = 0 OR cancel IS NULL)
            ) AS combined
        ", [$start_date, $end_date, $start_date, $end_date], "ssss");
        $paid = $res_paid ? (double)$res_paid->fetch_assoc()['total'] : 0.0;

        $closing_cashbook = $opening_balance + $total_member_fees + $other_rec + $wallet_net - $paid;

        // Year 1 closing = bank_balance + initial cash (matches dashboard formula)
        // Year N closing = opening + received - paid (matches dashboard formula)
        // NOTE: Interest is excluded from the deposit sum because interest accrues directly
        // in the bank — it never came from physical cash, so it must NOT affect cash in hand.
        if ($year_id == 1) {
            $res_bank_dep = app_exec_getresult("
                SELECT SUM(amount) as total FROM tbl_bank_transactions WHERE type = 'Deposit' AND date <= ?
            ", [$end_date], "s");
            $bank_dep = $res_bank_dep ? (float)$res_bank_dep->fetch_assoc()['total'] : 0.0;

            $res_bank_wth = app_exec_getresult("
                SELECT SUM(amount) as total FROM tbl_bank_transactions WHERE type = 'Withdrawal' AND date <= ?
            ", [$end_date], "s");
            $bank_wth = $res_bank_wth ? (float)$res_bank_wth->fetch_assoc()['total'] : 0.0;

            return ($bank_dep - $bank_wth) + $opening_balance;
        } else {
            return $closing_cashbook;
        }
    }

    // actions start
    if(isset($_POST['action']) && !empty($_POST['action'])) {
        $action = $_POST['action'];

        // action to delete recieved amount start
        if($action=="delete_payment"){
            $amount=$_POST['amount'];
            try{
                $tm->begin();

                $sql = "DELETE FROM tbl_other_recieved WHERE id = ?";
                $types="i";
                $parameters = [
                $_POST['id'],
                ];
                
                app_exec_roll_back_nonquery($conn,$sql, $parameters, $types);

                $sql = "SELECT p.amount as total_recieveble_Amount,SUM(pd.amount) AS total_recieved_amount
                FROM tbl_other_recieveble AS p
                LEFT JOIN tbl_payment_head_master AS g ON p.head = g.id
                LEFT JOIN tbl_other_recieved AS pd ON p.id = pd.recieveble_id where pd.recieveble_id = ?
                GROUP BY p.id, p.date, p.particuler, p.amount, p.head, g.type, g.name";
                $parameters = [
                    $_POST['recieveble_id'],
                ];
                $types = "i";
                
                $result = app_exec_getresult($sql, $parameters, $types);

                if (mysqli_num_rows($result) > 0) {
                    // output data of each row
                    while($row = mysqli_fetch_assoc($result)) {
                        $total_recieveble_Amount = $row["total_recieveble_Amount"];
                        $total_recieved_amount = $row["total_recieved_amount"];
                        $isComplete = ($total_recieveble_Amount - ($total_recieved_amount-$amount) == 0) ? 1 : 0;
                        
                        $sql = "UPDATE tbl_other_recieveble SET iscomplete = ?  WHERE id = ?";
                       
                        $parameters = [
                            $isComplete,
                            $_POST['recieveble_id'],
                        ];
                        $types="ii";
                        
                        if (!app_exec_roll_back_nonquery($conn, $sql, $parameters, $types)) {
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

        // action to add new recieved amount details and get the last inserted id start
        if($action=="save_payment"){ 

            try{
                if($_POST['id']==0){
                    
                   if($_POST['recieveble']==$_POST['recieved']){
                        $sql = "INSERT INTO tbl_other_recieveble (date, head, particuler, amount, iscomplete) VALUES (?, ?, ?, ?, 1)";
                   }
                   else{
                    $sql = "INSERT INTO tbl_other_recieveble (date, head, particuler, amount, iscomplete) VALUES (?, ?, ?, ?, 0)";
                   }
                    $parameters = [
                        $_POST['recieveble_date'],
                        $_POST['head'],
                        $_POST['particuler'],
                        $_POST['recieveble'],
                    ];
                    $types = "sisi";
                    
                    // Use custom insert function that returns insert ID
                    $recieveble_id = app_exec_getlast_id($sql, $parameters, $types);
                    

                    if ($recieveble_id !== null) {
                        $sql = "INSERT INTO tbl_other_recieved (date, head, particuler, amount, recieveble_id) VALUES (?, ?, ?, ?, ?)";
                        $parameters = [
                            $_POST['recieved_date'],
                            $_POST['head'],
                            $_POST['particuler'],
                            $_POST['recieved'],
                            $recieveble_id,
                        ];
                        $types = "sisii";

                        // Saving Query as  text file end

                       
                        app_exec_nonquery($sql, $parameters, $types);
                    }
                   
                }
                
            }
            catch (Exception $e) {
                http_response_code(500);   
                echo "Oops! Something went wrong." . $e->getMessage();
                return;
            }
        }
        // action to add new recieved amount details and get the last inserted id end

        // action to load heads for dropdown details start
        if($action=="load_heads"){
            try{
                $sql = "SELECT id, name FROM tbl_payment_head_master";
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
        
                $resdata=array($qrydata);
        
                echo json_encode($resdata);
                exit();
                
            }
            catch (Throwable $e) {
                throw new Exception('Oops! Something went wrong.');
            }
        }
        // action to load heads for dropdown details end

        // action to load all recieved amount details start
        if($action=="load_fees"){
            try{
               
                $sql="";  
                $sqlcountrows="";
                $sqldatarows="";   
        
                $sqldatarows ="SELECT (SELECT SUM(fees) FROM tbl_member_recievable) AS receivable,
                (SELECT SUM(fees) FROM tbl_member_recieved) AS received";
        
                $sqlcountrows = "SELECT COUNT(*) AS total FROM tbl_member_recievable";
                
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
        
                // $pagination = array("total_rows"=>$totalRows);
                $resdata=array($qrydata);
        
                echo json_encode($resdata);
                exit();
            }
            catch (Exception $e) {
                http_response_code(500);   
                echo "Oops! Something went wrong." . $e->getMessage();
                return;
            }
        }
        // action to load all recieved amount details end

        if($action=="load_receiveble"){
            try{
               
                $sql="";  
                $sqlcountrows="";
                $sqldatarows="";   
        
                $sqldatarows ="SELECT (SELECT SUM(amount) FROM tbl_other_recieveble) AS receivable,
                (SELECT SUM(amount) FROM tbl_other_recieved) AS received";
        
                $sqlcountrows = "SELECT COUNT(*) AS total FROM tbl_other_recieveble";
                
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
        
                // $pagination = array("total_rows"=>$totalRows);
                $resdata=array($qrydata);
        
                echo json_encode($resdata);
                exit();
            }
            catch (Exception $e) {
                http_response_code(500);   
                echo "Oops! Something went wrong." . $e->getMessage();
                return;
            }
        }

        if($action=="load_payable"){
            try{
               
                $sql="";  
                $sqlcountrows="";
                $sqldatarows="";   
        
                $sqldatarows ="SELECT (SELECT SUM(amount) FROM tbl_payable) AS payable,
                (SELECT SUM(amount) FROM tbl_paid) AS paid";
        
                $sqlcountrows = "SELECT COUNT(*) AS total FROM tbl_payable";
                
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
        
                // $pagination = array("total_rows"=>$totalRows);
                $resdata=array($qrydata);
        
                echo json_encode($resdata);
                exit();
            }
            catch (Exception $e) {
                http_response_code(500);   
                echo "Oops! Something went wrong." . $e->getMessage();
                return;
            }
        }

        if ($action == "load_financial_dashboard") {
            try {
                $selected_year = isset($_POST['year_id']) ? (int)$_POST['year_id'] : 0;
                
                // Get year details
                $sqlYear = "SELECT id, from_year, to_year FROM tbl_closing WHERE id = ?";
                $yearRow = app_exec_getresult($sqlYear, [$selected_year], "i")->fetch_assoc();
                
                if (!$yearRow) {
                    http_response_code(400);
                    echo json_encode(["error" => "Invalid financial year selected."]);
                    exit;
                }
                
                $from_year = (int)$yearRow['from_year'];
                $to_year = (int)$yearRow['to_year'];
                $start_date = $from_year . "-04-01";
                $end_date = $to_year . "-03-31";
                
                // 1. Calculate Opening Balance prior to selected year start date
                $init_ob_res = app_exec_query("SELECT amount FROM tbl_opening_balance WHERE isactive = 1 LIMIT 1");
                $init_ob = $init_ob_res ? (double)$init_ob_res->fetch_assoc()['amount'] : 6780.00;

                // Previous member received (active + archived)
                $res_prev_member = app_exec_getresult("
                    SELECT SUM(fees) as total FROM (
                        SELECT fees FROM tbl_member_recieved WHERE date < ? AND (cancel = 0 OR cancel IS NULL)
                        UNION ALL
                        SELECT fees FROM tbl_member_recieved_old WHERE date < ? AND (cancel = 0 OR cancel IS NULL)
                    ) AS combined
                ", [$start_date, $start_date], "ss");
                $prev_member = $res_prev_member ? (double)$res_prev_member->fetch_assoc()['total'] : 0.0;

                // Previous other received (active + archived)
                $res_prev_other = app_exec_getresult("
                    SELECT SUM(amount) as total FROM (
                        SELECT amount FROM tbl_other_recieved WHERE date < ? AND (cancel = 0 OR cancel IS NULL)
                        UNION ALL
                        SELECT amount FROM tbl_other_recieved_old WHERE date < ? AND (cancel = 0 OR cancel IS NULL)
                    ) AS combined
                ", [$start_date, $start_date], "ss");
                $prev_other = $res_prev_other ? (double)$res_prev_other->fetch_assoc()['total'] : 0.0;

                // Previous wallet net credits
                $res_prev_wallet = app_exec_getresult("
                    SELECT 
                        SUM(CASE WHEN type = 'credit' THEN amount ELSE 0 END) as credits,
                        SUM(CASE WHEN type = 'debit' THEN amount ELSE 0 END) as debits 
                    FROM tbl_wallet 
                    WHERE date < ?
                ", [$start_date], "s");
                $row_prev_wallet = $res_prev_wallet ? $res_prev_wallet->fetch_assoc() : null;
                $prev_wallet = $row_prev_wallet ? ((double)$row_prev_wallet['credits'] - (double)$row_prev_wallet['debits']) : 0.0;

                // Previous paid expenses (active + archived)
                $res_prev_paid = app_exec_getresult("
                    SELECT SUM(amount) as total FROM (
                        SELECT amount FROM tbl_paid WHERE date < ? AND (cancel = 0 OR cancel IS NULL)
                        UNION ALL
                        SELECT amount FROM tbl_paid_old WHERE date < ? AND (cancel = 0 OR cancel IS NULL)
                    ) AS combined
                ", [$start_date, $start_date], "ss");
                $prev_paid = $res_prev_paid ? (double)$res_prev_paid->fetch_assoc()['total'] : 0.0;

                if ($selected_year == 1) {
                    $opening_balance = $init_ob;
                } else {
                    $opening_balance = get_year_closing_balance($selected_year - 1);
                }

                // 2. Member Monthly Fees Received (Head = 3, 6 and empty/NULL) - active + archived
                $res_monthly = app_exec_getresult("
                    SELECT SUM(fees) as total FROM (
                        SELECT d.fees FROM tbl_member_recieved d
                        LEFT JOIN tbl_member_recievable p ON d.receiveble_id = p.id
                        WHERE d.date BETWEEN ? AND ? AND COALESCE(NULLIF(d.head, ''), NULLIF(p.head, ''), '3') IN ('3', '6') AND (d.cancel = 0 OR d.cancel IS NULL)
                        UNION ALL
                        SELECT d.fees FROM tbl_member_recieved_old d
                        LEFT JOIN tbl_member_recievable_old p ON d.receiveble_id = p.id
                        WHERE d.date BETWEEN ? AND ? AND COALESCE(NULLIF(d.head, ''), NULLIF(p.head, ''), '3') IN ('3', '6') AND (d.cancel = 0 OR d.cancel IS NULL)
                    ) AS combined
                ", [$start_date, $end_date, $start_date, $end_date], "ssss");
                $monthly_fees = $res_monthly ? (double)$res_monthly->fetch_assoc()['total'] : 0.0;

                // 3. Membership Fees Received (Head = 4) - active + archived
                $res_membership = app_exec_getresult("
                    SELECT SUM(fees) as total FROM (
                        SELECT d.fees FROM tbl_member_recieved d
                        LEFT JOIN tbl_member_recievable p ON d.receiveble_id = p.id
                        WHERE d.date BETWEEN ? AND ? AND COALESCE(NULLIF(d.head, ''), NULLIF(p.head, ''), '3') = '4' AND (d.cancel = 0 OR d.cancel IS NULL)
                        UNION ALL
                        SELECT d.fees FROM tbl_member_recieved_old d
                        LEFT JOIN tbl_member_recievable_old p ON d.receiveble_id = p.id
                        WHERE d.date BETWEEN ? AND ? AND COALESCE(NULLIF(d.head, ''), NULLIF(p.head, ''), '3') = '4' AND (d.cancel = 0 OR d.cancel IS NULL)
                    ) AS combined
                ", [$start_date, $end_date, $start_date, $end_date], "ssss");
                $membership_fees = $res_membership ? (double)$res_membership->fetch_assoc()['total'] : 0.0;

                // Other Member Fees Received (excluding Heads 3, 6 and 4) - active + archived
                $res_other_member = app_exec_getresult("
                    SELECT SUM(fees) as total FROM (
                        SELECT d.fees FROM tbl_member_recieved d
                        LEFT JOIN tbl_member_recievable p ON d.receiveble_id = p.id
                        WHERE d.date BETWEEN ? AND ? AND COALESCE(NULLIF(d.head, ''), NULLIF(p.head, ''), '3') NOT IN ('3', '4', '6') AND (d.cancel = 0 OR d.cancel IS NULL)
                        UNION ALL
                        SELECT d.fees FROM tbl_member_recieved_old d
                        LEFT JOIN tbl_member_recievable_old p ON d.receiveble_id = p.id
                        WHERE d.date BETWEEN ? AND ? AND COALESCE(NULLIF(d.head, ''), NULLIF(p.head, ''), '3') NOT IN ('3', '4', '6') AND (d.cancel = 0 OR d.cancel IS NULL)
                    ) AS combined
                ", [$start_date, $end_date, $start_date, $end_date], "ssss");
                $other_member_fees = $res_other_member ? (double)$res_other_member->fetch_assoc()['total'] : 0.0;

                // Total Member Fees Received
                $total_member_fees_received = $monthly_fees + $membership_fees + $other_member_fees;

                // 4. Other Payments Received (Other Income + Wallet net credits) - active + archived
                $res_other = app_exec_getresult("
                    SELECT SUM(amount) as total FROM (
                        SELECT amount FROM tbl_other_recieved WHERE date BETWEEN ? AND ? AND (cancel = 0 OR cancel IS NULL)
                        UNION ALL
                        SELECT amount FROM tbl_other_recieved_old WHERE date BETWEEN ? AND ? AND (cancel = 0 OR cancel IS NULL)
                    ) AS combined
                ", [$start_date, $end_date, $start_date, $end_date], "ssss");
                $other_rec_amount = $res_other ? (double)$res_other->fetch_assoc()['total'] : 0.0;

                $res_wallet = app_exec_getresult("
                    SELECT 
                        SUM(CASE WHEN type = 'credit' THEN amount ELSE 0 END) as credits,
                        SUM(CASE WHEN type = 'debit' THEN amount ELSE 0 END) as debits 
                    FROM tbl_wallet 
                    WHERE date BETWEEN ? AND ?
                ", [$start_date, $end_date], "ss");
                $row_wallet = $res_wallet ? $res_wallet->fetch_assoc() : null;
                $wallet_net_amount = $row_wallet ? ((double)$row_wallet['credits'] - (double)$row_wallet['debits']) : 0.0;

                $other_payments_received = $other_rec_amount + $wallet_net_amount;

                // 5. Total Wallet Balance (cumulative up to the end of selected year)
                $res_cumulative_wallet = app_exec_getresult("
                    SELECT 
                        SUM(CASE WHEN type = 'credit' THEN amount ELSE 0 END) as credits,
                        SUM(CASE WHEN type = 'debit' THEN amount ELSE 0 END) as debits 
                    FROM tbl_wallet 
                    WHERE date <= ?
                ", [$end_date], "s");
                $row_cumulative_wallet = $res_cumulative_wallet ? $res_cumulative_wallet->fetch_assoc() : null;
                $total_wallet_balance = $row_cumulative_wallet ? ((double)$row_cumulative_wallet['credits'] - (double)$row_cumulative_wallet['debits']) : 0.0;

                // 6. Current Year Expenses (Paid) - active + archived
                $res_paid = app_exec_getresult("
                    SELECT SUM(amount) as total FROM (
                        SELECT amount FROM tbl_paid WHERE date BETWEEN ? AND ? AND (cancel = 0 OR cancel IS NULL)
                        UNION ALL
                        SELECT amount FROM tbl_paid_old WHERE date BETWEEN ? AND ? AND (cancel = 0 OR cancel IS NULL)
                    ) AS combined
                ", [$start_date, $end_date, $start_date, $end_date], "ssss");
                $other_payments_paid = $res_paid ? (double)$res_paid->fetch_assoc()['total'] : 0.0;

                // 7. Calculate Closing Balance
                if ($selected_year == 1) {
                    $closing_balance = $init_ob + $total_member_fees_received + $other_payments_received - $other_payments_paid;
                } else {
                    $closing_balance = $opening_balance + $total_member_fees_received + $other_payments_received - $other_payments_paid;
                }

                // 8. Pending Payment Amount (Cumulative up to the end of the selected year)
                // A. Cumulative Member Fees
                $res_cum_rec_member = app_exec_getresult("
                    SELECT SUM(fees) as total FROM (
                        SELECT fees FROM tbl_member_recievable WHERE date <= ? AND (cancel = 0 OR cancel IS NULL)
                        UNION ALL
                        SELECT fees FROM tbl_member_recievable_old WHERE date <= ? AND (cancel = 0 OR cancel IS NULL)
                    ) AS combined
                ", [$end_date, $end_date], "ss");
                $cumulative_member_receivable = $res_cum_rec_member ? (double)$res_cum_rec_member->fetch_assoc()['total'] : 0.0;

                $res_cum_rcv_member = app_exec_getresult("
                    SELECT SUM(fees) as total FROM (
                        SELECT fees FROM tbl_member_recieved WHERE date <= ? AND (cancel = 0 OR cancel IS NULL)
                        UNION ALL
                        SELECT fees FROM tbl_member_recieved_old WHERE date <= ? AND (cancel = 0 OR cancel IS NULL)
                    ) AS combined
                ", [$end_date, $end_date], "ss");
                $cumulative_member_received = $res_cum_rcv_member ? (double)$res_cum_rcv_member->fetch_assoc()['total'] : 0.0;

                $pending_member_fees = $cumulative_member_receivable - $cumulative_member_received;

                // B. Cumulative Other Receivables
                $res_cum_rec_other = app_exec_getresult("
                    SELECT SUM(amount) as total FROM (
                        SELECT amount FROM tbl_other_recieveble WHERE date <= ? AND (cancel = 0 OR cancel IS NULL)
                        UNION ALL
                        SELECT amount FROM tbl_other_recieveble_old WHERE date <= ? AND (cancel = 0 OR cancel IS NULL)
                    ) AS combined
                ", [$end_date, $end_date], "ss");
                $cumulative_other_receivable = $res_cum_rec_other ? (double)$res_cum_rec_other->fetch_assoc()['total'] : 0.0;

                $res_cum_rcv_other = app_exec_getresult("
                    SELECT SUM(amount) as total FROM (
                        SELECT amount FROM tbl_other_recieved WHERE date <= ? AND (cancel = 0 OR cancel IS NULL)
                        UNION ALL
                        SELECT amount FROM tbl_other_recieved_old WHERE date <= ? AND (cancel = 0 OR cancel IS NULL)
                    ) AS combined
                ", [$end_date, $end_date], "ss");
                $cumulative_other_received = $res_cum_rcv_other ? (double)$res_cum_rcv_other->fetch_assoc()['total'] : 0.0;

                $pending_other_receivable = $cumulative_other_receivable - $cumulative_other_received;

                // C. Pending Payables - active + archived
                $res_payable = app_exec_getresult("
                    SELECT SUM(amount) as total FROM (
                        SELECT amount FROM tbl_payable WHERE flag = ? AND (cancel = 0 OR cancel IS NULL)
                        UNION ALL
                        SELECT t.amount FROM tbl_payable_old t
                        JOIN tbl_closing cl ON t.flag = CAST(cl.id AS CHAR)
                        WHERE cl.id = ? AND (t.cancel = 0 OR t.cancel IS NULL)
                    ) AS combined
                ", [$selected_year, $selected_year], "ii");
                $payable_total = $res_payable ? (double)$res_payable->fetch_assoc()['total'] : 0.0;
                $pending_payable = $payable_total - $other_payments_paid;

                $total_pending_receivables = $pending_member_fees + $pending_other_receivable;

                // 9. Detailed Breakdown by Head (for the Tables)
                // A. Fees by Head (Receivable vs Received) - active + archived
                $fees_breakdown_sql = "
                    SELECT 
                        h.id as head_id,
                        h.name as head_name,
                        (
                            COALESCE((
                                SELECT SUM(fees) FROM tbl_member_recievable r 
                                WHERE r.flag = ? AND COALESCE(NULLIF(r.head, ''), '3') = CAST(h.id AS CHAR) AND (r.cancel = 0 OR r.cancel IS NULL)
                            ), 0)
                            +
                            COALESCE((
                                SELECT SUM(t.fees) FROM tbl_member_recievable_old t
                                JOIN tbl_closing cl ON t.flag = CAST(cl.id AS CHAR)
                                WHERE cl.id = ? AND COALESCE(NULLIF(t.head, ''), '3') = CAST(h.id AS CHAR) AND (t.cancel = 0 OR t.cancel IS NULL)
                            ), 0)
                        ) as receivable,
                        (
                            COALESCE((
                                SELECT SUM(d.fees) FROM tbl_member_recieved d 
                                LEFT JOIN tbl_member_recievable p ON d.receiveble_id = p.id 
                                WHERE d.date BETWEEN ? AND ? AND COALESCE(NULLIF(d.head, ''), NULLIF(p.head, ''), '3') = CAST(h.id AS CHAR) AND (d.cancel = 0 OR d.cancel IS NULL)
                            ), 0)
                            +
                            COALESCE((
                                SELECT SUM(d.fees) FROM tbl_member_recieved_old d
                                LEFT JOIN tbl_member_recievable_old p ON d.receiveble_id = p.id
                                WHERE d.date BETWEEN ? AND ? AND COALESCE(NULLIF(d.head, ''), NULLIF(p.head, ''), '3') = CAST(h.id AS CHAR) AND (d.cancel = 0 OR d.cancel IS NULL)
                            ), 0)
                        ) as received
                    FROM tbl_payment_head_master h
                    WHERE h.type = 'Credit' AND h.id IN (3, 4, 5, 6, 12)
                    ORDER BY h.id
                ";
                $fees_breakdown = [];
                $fees_breakdown_res = app_exec_getresult($fees_breakdown_sql, [$selected_year, $selected_year, $start_date, $end_date, $start_date, $end_date], "iissss");
                while ($row = $fees_breakdown_res->fetch_assoc()) {
                    $fees_breakdown[] = $row;
                }

                // B. Other Receivables by Head (Receivable vs Received) - active + archived
                $other_breakdown_sql = "
                    SELECT 
                        h.id as head_id,
                        h.name as head_name,
                        (
                            COALESCE((
                                SELECT SUM(amount) FROM tbl_other_recieveble r 
                                WHERE r.flag = ? AND r.head = h.id AND (r.cancel = 0 OR r.cancel IS NULL)
                            ), 0)
                            +
                            COALESCE((
                                SELECT SUM(t.amount) FROM tbl_other_recieveble_old t
                                JOIN tbl_closing cl ON t.flag = CAST(cl.id AS CHAR)
                                WHERE cl.id = ? AND t.head = h.id AND (t.cancel = 0 OR t.cancel IS NULL)
                            ), 0)
                        ) as receivable,
                        (
                            COALESCE((
                                SELECT SUM(amount) FROM tbl_other_recieved d 
                                WHERE d.date BETWEEN ? AND ? AND d.head = h.id AND (d.cancel = 0 OR d.cancel IS NULL)
                            ), 0)
                            +
                            COALESCE((
                                SELECT SUM(t.amount) FROM tbl_other_recieved_old t
                                WHERE t.date BETWEEN ? AND ? AND t.head = h.id AND (t.cancel = 0 OR t.cancel IS NULL)
                            ), 0)
                        ) as received
                    FROM tbl_payment_head_master h
                    WHERE h.type = 'Credit' AND h.id NOT IN (3, 4, 5, 6, 12)
                    ORDER BY h.name
                ";
                $other_breakdown = [];
                $other_breakdown_res = app_exec_getresult($other_breakdown_sql, [$selected_year, $selected_year, $start_date, $end_date, $start_date, $end_date], "iissss");
                while ($row = $other_breakdown_res->fetch_assoc()) {
                    $other_breakdown[] = $row;
                }

                // C. Payables/Paid by Head (Payable vs Paid) - active + archived
                $payable_breakdown_sql = "
                    SELECT 
                        h.id as head_id,
                        h.name as head_name,
                        (
                            COALESCE((
                                SELECT SUM(amount) FROM tbl_payable p 
                                WHERE p.flag = ? AND p.head = h.id AND (p.cancel = 0 OR p.cancel IS NULL)
                            ), 0)
                            +
                            COALESCE((
                                SELECT SUM(t.amount) FROM tbl_payable_old t
                                JOIN tbl_closing cl ON t.flag = CAST(cl.id AS CHAR)
                                WHERE cl.id = ? AND t.head = h.id AND (t.cancel = 0 OR t.cancel IS NULL)
                            ), 0)
                        ) as payable,
                        (
                            COALESCE((
                                SELECT SUM(amount) FROM tbl_paid d 
                                WHERE d.date BETWEEN ? AND ? AND d.head = h.id AND (d.cancel = 0 OR d.cancel IS NULL)
                            ), 0)
                            +
                            COALESCE((
                                SELECT SUM(t.amount) FROM tbl_paid_old t
                                WHERE t.date BETWEEN ? AND ? AND t.head = h.id AND (t.cancel = 0 OR t.cancel IS NULL)
                            ), 0)
                        ) as paid
                    FROM tbl_payment_head_master h
                    WHERE h.type = 'Debit'
                    ORDER BY h.name
                ";
                 $payable_breakdown = [];
                 $payable_breakdown_res = app_exec_getresult($payable_breakdown_sql, [$selected_year, $selected_year, $start_date, $end_date, $start_date, $end_date], "iissss");
                 while ($row = $payable_breakdown_res->fetch_assoc()) {
                     $payable_breakdown[] = $row;
                 }

                 // Bank and Cash in Hand calculations
                 // A. Total Bank Deposit + Interest of that year
                 $res_bank_dep_year = app_exec_getresult("
                     SELECT SUM(amount) as total FROM tbl_bank_transactions WHERE type IN ('Deposit', 'Interest') AND date BETWEEN ? AND ?
                 ", [$start_date, $end_date], "ss");
                 $total_bank_deposit_year = $res_bank_dep_year ? (float)$res_bank_dep_year->fetch_assoc()['total'] : 0.0;

                 // A2. Total Bank Withdrawal of that year
                 $res_bank_wth_year = app_exec_getresult("
                     SELECT SUM(amount) as total FROM tbl_bank_transactions WHERE type = 'Withdrawal' AND date BETWEEN ? AND ?
                 ", [$start_date, $end_date], "ss");
                 $total_bank_withdrawal_year = $res_bank_wth_year ? (float)$res_bank_wth_year->fetch_assoc()['total'] : 0.0;

                 // B. Cumulative Bank Balance up to end of that financial year (Deposits + Interest - Withdrawals)
                 $res_bank_dep_cum = app_exec_getresult("
                     SELECT SUM(amount) as total FROM tbl_bank_transactions WHERE type IN ('Deposit', 'Interest') AND date <= ?
                 ", [$end_date], "s");
                 $bank_dep_cum = $res_bank_dep_cum ? (float)$res_bank_dep_cum->fetch_assoc()['total'] : 0.0;

                 $res_bank_wth_cum = app_exec_getresult("
                     SELECT SUM(amount) as total FROM tbl_bank_transactions WHERE type = 'Withdrawal' AND date <= ?
                 ", [$end_date], "s");
                 $bank_wth_cum = $res_bank_wth_cum ? (float)$res_bank_wth_cum->fetch_assoc()['total'] : 0.0;

                 $total_bank_balance = $bank_dep_cum - $bank_wth_cum;
                 // B2. Bank balance from CASH movements only (Deposits - Withdrawals, NO Interest)
                 // Interest goes to bank directly — it never came from physical cash, so it must NOT reduce cash in hand
                 $res_bank_dep_cash_cum = app_exec_getresult("
                     SELECT SUM(amount) as total FROM tbl_bank_transactions WHERE type = 'Deposit' AND date <= ?
                 ", [$end_date], "s");
                 $bank_dep_cash_cum = $res_bank_dep_cash_cum ? (float)$res_bank_dep_cash_cum->fetch_assoc()['total'] : 0.0;

                 // bank_balance_cash_movement = only the cash that physically moved from hand to bank
                 $bank_balance_cash_movement = $bank_dep_cash_cum - $bank_wth_cum;

                 // C. Cash in Hand and final Closing Balance
                 if ($selected_year == 1) {
                     $cash_in_hand = $init_ob;
                     $bank_deposit = $total_bank_balance;
                 } else {
                     $bank_deposit = $total_bank_balance;
                     // Use cash-movement-only bank balance so Interest does NOT reduce Cash in Hand
                     $cash_in_hand = $closing_balance - $bank_balance_cash_movement;
                 }
                 $closing_balance = $bank_deposit + $cash_in_hand;

                  // D. Total FD amount (principal + interest) up to the end of the financial year
                  $res_fd_prev = app_exec_getresult("
                      SELECT SUM(amount) as total FROM tbl_fd_transactions WHERE date <= ?
                  ", [$end_date], "s");
                  $total_fd_prev_principal = $res_fd_prev ? (double)$res_fd_prev->fetch_assoc()['total'] : 0.0;

                  $res_fd_int_prev = app_exec_getresult("
                      SELECT SUM(amount) as total FROM tbl_fd_interest_credits WHERE date <= ?
                  ", [$end_date], "s");
                  $total_fd_prev_interest = $res_fd_int_prev ? (double)$res_fd_int_prev->fetch_assoc()['total'] : 0.0;

                  $total_fd_prev = $total_fd_prev_principal + $total_fd_prev_interest;

                  // Savings Account Interest Received in the year
                  $res_sav_int = app_exec_getresult("
                      SELECT COALESCE(SUM(amount), 0) as total FROM tbl_bank_transactions WHERE type = 'Interest' AND date BETWEEN ? AND ?
                  ", [$start_date, $end_date], "ss");
                  $savings_interest_year = $res_sav_int ? (double)$res_sav_int->fetch_assoc()['total'] : 0.0;

                  // FD Interest Received in the year
                  $res_fd_int = app_exec_getresult("
                      SELECT COALESCE(SUM(amount), 0) as total FROM tbl_fd_interest_credits WHERE date BETWEEN ? AND ?
                  ", [$start_date, $end_date], "ss");
                  $fd_interest_year = $res_fd_int ? (double)$res_fd_int->fetch_assoc()['total'] : 0.0;

                  // Savings Account Interest details (credits list)
                  $res_sav_details = app_exec_getresult("
                      SELECT date, amount, description FROM tbl_bank_transactions WHERE type = 'Interest' AND date BETWEEN ? AND ? ORDER BY date DESC
                  ", [$start_date, $end_date], "ss");
                  $savings_interest_details = [];
                  while ($row = $res_sav_details->fetch_assoc()) {
                      $savings_interest_details[] = $row;
                  }

                  // FD Interest details (credits list)
                  $res_fd_details = app_exec_getresult("
                      SELECT c.date, c.amount, c.description, t.fd_no, t.bank_name 
                      FROM tbl_fd_interest_credits c 
                      JOIN tbl_fd_transactions t ON c.fd_id = t.id 
                      WHERE c.date BETWEEN ? AND ? 
                      ORDER BY c.date DESC
                  ", [$start_date, $end_date], "ss");
                  $fd_interest_details = [];
                  while ($row = $res_fd_details->fetch_assoc()) {
                      $fd_interest_details[] = $row;
                  }
                  $total_assets = $bank_deposit + $cash_in_hand + $total_fd_prev;

                  $response = [
                      "year_label" => $from_year . " - " . $to_year,
                      "opening_balance" => $opening_balance,
                      "monthly_fees" => $monthly_fees,
                      "membership_fees" => $membership_fees,
                      "other_member_fees" => $other_member_fees,
                      "total_member_fees_received" => $total_member_fees_received,
                      "other_payments_received" => $other_payments_received,
                      "other_rec_amount" => $other_rec_amount,
                      "wallet_net_amount" => $wallet_net_amount,
                      "total_wallet_balance" => $total_wallet_balance,
                      "other_payments_paid" => $other_payments_paid,
                      "closing_balance" => $closing_balance,
                      "pending_member_fees" => $pending_member_fees,
                      "pending_other_receivable" => $pending_other_receivable,
                      "pending_payable" => $pending_payable,
                      "total_pending_receivables" => $total_pending_receivables,
                      "fees_breakdown" => $fees_breakdown,
                      "other_breakdown" => $other_breakdown,
                      "payable_breakdown" => $payable_breakdown,
                      "total_bank_deposit_year" => $bank_deposit,
                      "total_bank_balance" => $total_bank_balance,
                      "cash_in_hand" => $cash_in_hand,
                      "total_fd_prev" => $total_fd_prev,
                      "savings_interest_year" => $savings_interest_year,
                      "fd_interest_year" => $fd_interest_year,
                      "savings_interest_details" => $savings_interest_details,
                      "fd_interest_details" => $fd_interest_details,
                      "total_assets" => $total_assets
                  ];

                 echo json_encode($response);
                 exit;
             }
            catch (Exception $e) {
                http_response_code(500);
                echo json_encode(["error" => "Oops! Something went wrong: " . $e->getMessage()]);
                exit;
            }
        }
    }

    if($action=="load_closing_years"){
            try{
                $sql = "SELECT id, from_year, to_year FROM tbl_closing ORDER BY from_year DESC";
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

    // Global all-time totals: total pending payments and wallet net balance
    if ($action == "load_global_totals") {
        try {
            // --- All-time Member Pending ---
            $r_mr_a = app_exec_query("SELECT COALESCE(SUM(fees),0) as t FROM tbl_member_recievable WHERE cancel=0 OR cancel IS NULL");
            $r_mr_o = app_exec_query("SELECT COALESCE(SUM(fees),0) as t FROM tbl_member_recievable_old WHERE cancel=0 OR cancel IS NULL");
            $r_mc_a = app_exec_query("SELECT COALESCE(SUM(fees),0) as t FROM tbl_member_recieved WHERE cancel=0 OR cancel IS NULL");
            $r_mc_o = app_exec_query("SELECT COALESCE(SUM(fees),0) as t FROM tbl_member_recieved_old WHERE cancel=0 OR cancel IS NULL");
            $member_receivable = (double)$r_mr_a->fetch_assoc()['t'] + (double)$r_mr_o->fetch_assoc()['t'];
            $member_received   = (double)$r_mc_a->fetch_assoc()['t'] + (double)$r_mc_o->fetch_assoc()['t'];
            $pending_member = $member_receivable - $member_received;

            // --- All-time Other Pending ---
            $r_or_a = app_exec_query("SELECT COALESCE(SUM(amount),0) as t FROM tbl_other_recieveble WHERE cancel=0 OR cancel IS NULL");
            $r_or_o = app_exec_query("SELECT COALESCE(SUM(amount),0) as t FROM tbl_other_recieveble_old WHERE cancel=0 OR cancel IS NULL");
            $r_oc_a = app_exec_query("SELECT COALESCE(SUM(amount),0) as t FROM tbl_other_recieved WHERE cancel=0 OR cancel IS NULL");
            $r_oc_o = app_exec_query("SELECT COALESCE(SUM(amount),0) as t FROM tbl_other_recieved_old WHERE cancel=0 OR cancel IS NULL");
            $other_receivable = (double)$r_or_a->fetch_assoc()['t'] + (double)$r_or_o->fetch_assoc()['t'];
            $other_received   = (double)$r_oc_a->fetch_assoc()['t'] + (double)$r_oc_o->fetch_assoc()['t'];
            $pending_other = $other_receivable - $other_received;

            $total_pending = $pending_member + $pending_other;

            // --- All-time Wallet Balance ---
            $r_w = app_exec_query("
                SELECT
                    COALESCE(SUM(CASE WHEN type='credit' THEN amount ELSE 0 END),0) as credits,
                    COALESCE(SUM(CASE WHEN type='debit'  THEN amount ELSE 0 END),0) as debits
                FROM tbl_wallet
            ");
            $w = $r_w->fetch_assoc();
            $wallet_balance = (double)$w['credits'] - (double)$w['debits'];

            echo json_encode([
                "total_pending"    => $total_pending,
                "wallet_balance"   => $wallet_balance,
            ]);
            exit();
        } catch (Throwable $e) {
            throw new Exception('Oops! Something went wrong.');
        }
    }
?>