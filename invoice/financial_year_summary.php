<?php
    ob_start();
    error_reporting(E_ERROR | E_PARSE);
    ini_set('display_errors', 0);

    include '../app_common/db_connect.php';
    require_once('tcpdf/tcpdf.php');

    function get_year_closing_balance($year_id) {
        if ($year_id <= 0) return 0.0;
        
        // Get year details
        $sql = "SELECT id, from_year, to_year FROM tbl_closing WHERE id = ?";
        $yearRow = app_exec_getresult($sql, [$year_id], "i")->fetch_assoc();
        if (!$yearRow) return 0.0;
        
        $from_year = (int)$yearRow['from_year'];
        $to_year = (int)$yearRow['to_year'];
        $start_date = $from_year . "-04-01";
        $end_date = $to_year . "-03-31";
        
        // Opening balance: initial OB for year 1, or previous year's closing for year N
        if ($year_id == 1) {
            $init_ob_res = app_exec_query("SELECT amount FROM tbl_opening_balance WHERE isactive = 1 LIMIT 1");
            $opening_balance = $init_ob_res ? (double)$init_ob_res->fetch_assoc()['amount'] : 6780.00;
        } else {
            $opening_balance = get_year_closing_balance($year_id - 1);
        }

        // Member Fees Received (excluding Guest Fee Head 12) - active + archived
        $res_member = app_exec_getresult("
            SELECT SUM(fees) as total FROM (
                SELECT d.fees FROM tbl_member_recieved d
                LEFT JOIN tbl_member_recievable p ON d.receiveble_id = p.id
                WHERE d.date BETWEEN ? AND ? AND COALESCE(NULLIF(d.head, ''), NULLIF(p.head, ''), '3') != '12' AND (d.cancel = 0 OR d.cancel IS NULL)
                UNION ALL
                SELECT d.fees FROM tbl_member_recieved_old d
                LEFT JOIN tbl_member_recievable_old p ON d.receiveble_id = p.id
                WHERE d.date BETWEEN ? AND ? AND COALESCE(NULLIF(d.head, ''), NULLIF(p.head, ''), '3') != '12' AND (d.cancel = 0 OR d.cancel IS NULL)
            ) AS combined
        ", [$start_date, $end_date, $start_date, $end_date], "ssss");
        $member_fees = $res_member ? (double)$res_member->fetch_assoc()['total'] : 0.0;

        // Guest Fees Received (Head 12) - active + archived
        $res_guest = app_exec_getresult("
            SELECT SUM(fees) as total FROM (
                SELECT d.fees FROM tbl_member_recieved d
                LEFT JOIN tbl_member_recievable p ON d.receiveble_id = p.id
                WHERE d.date BETWEEN ? AND ? AND COALESCE(NULLIF(d.head, ''), NULLIF(p.head, ''), '3') = '12' AND (d.cancel = 0 OR d.cancel IS NULL)
                UNION ALL
                SELECT d.fees FROM tbl_member_recieved_old d
                LEFT JOIN tbl_member_recievable_old p ON d.receiveble_id = p.id
                WHERE d.date BETWEEN ? AND ? AND COALESCE(NULLIF(d.head, ''), NULLIF(p.head, ''), '3') = '12' AND (d.cancel = 0 OR d.cancel IS NULL)
            ) AS combined
        ", [$start_date, $end_date, $start_date, $end_date], "ssss");
        $guest_fees = $res_guest ? (double)$res_guest->fetch_assoc()['total'] : 0.0;

        // Other Payments Received (Other Income + Net Wallet credits/debits in the year) - active + archived
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
        $wallet_amount = $row_wallet ? ((double)$row_wallet['credits'] - (double)$row_wallet['debits']) : 0.0;

        $other_payments_received = $other_rec_amount + $wallet_amount;

        // Calculate current year expenses (paid) - active + archived
        $res_paid = app_exec_getresult("
            SELECT SUM(amount) as total FROM (
                SELECT amount FROM tbl_paid WHERE date BETWEEN ? AND ? AND (cancel = 0 OR cancel IS NULL)
                UNION ALL
                SELECT amount FROM tbl_paid_old WHERE date BETWEEN ? AND ? AND (cancel = 0 OR cancel IS NULL)
            ) AS combined
        ", [$start_date, $end_date, $start_date, $end_date], "ssss");
        $other_payments_paid = $res_paid ? (double)$res_paid->fetch_assoc()['total'] : 0.0;
        
        $closing_cashbook = $opening_balance + $member_fees + $guest_fees + $other_payments_received - $other_payments_paid;
        
        $res_bank_dep_cum = app_exec_getresult("
            SELECT SUM(amount) as total FROM tbl_bank_transactions WHERE type = 'Deposit' AND date <= ?
        ", [$end_date], "s");
        $bank_dep_cum = $res_bank_dep_cum ? (double)$res_bank_dep_cum->fetch_assoc()['total'] : 0.0;

        $res_bank_wth_cum = app_exec_getresult("
            SELECT SUM(amount) as total FROM tbl_bank_transactions WHERE type = 'Withdrawal' AND date <= ?
        ", [$end_date], "s");
        $bank_wth_cum = $res_bank_wth_cum ? (double)$res_bank_wth_cum->fetch_assoc()['total'] : 0.0;

        $total_bank_balance = $bank_dep_cum - $bank_wth_cum;
        
        // Mirror the exact closing_balance formula used in the dashboard
        if ($year_id == 1) {
            $init_ob_res = app_exec_query("SELECT amount FROM tbl_opening_balance WHERE isactive = 1 LIMIT 1");
            $init_ob = $init_ob_res ? (double)$init_ob_res->fetch_assoc()['amount'] : 6780.00;
            return $total_bank_balance + $init_ob;
        } else {
            return $closing_cashbook;
        }
    }

    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        $selected_year = $_POST['selected_year'];

        // Get year details
        $sqlYear = "SELECT from_year, to_year FROM tbl_closing WHERE id = ?";
        $yearRow = app_exec_getresult($sqlYear, [$selected_year], "i")->fetch_assoc();

        if ($yearRow) {
            $from_year = (int)$yearRow['from_year'];
            $to_year = (int)$yearRow['to_year'];
            $yearLabel = htmlspecialchars($from_year . ' - ' . $to_year);
            $start_date = $from_year . "-04-01";
            $end_date = $to_year . "-03-31";
        } else {
            ob_end_clean();
            exit("Invalid financial year selected.");
        }

        // 1. Calculate Opening Balance prior to selected year start date
        // Fetch the initial opening balance
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

        // Member Fees Received (excluding Guest Fee Head 12) - active + archived
        $res_member = app_exec_getresult("
            SELECT SUM(fees) as total FROM (
                SELECT d.fees FROM tbl_member_recieved d
                LEFT JOIN tbl_member_recievable p ON d.receiveble_id = p.id
                WHERE d.date BETWEEN ? AND ? AND COALESCE(NULLIF(d.head, ''), NULLIF(p.head, ''), '3') != '12' AND (d.cancel = 0 OR d.cancel IS NULL)
                UNION ALL
                SELECT d.fees FROM tbl_member_recieved_old d
                LEFT JOIN tbl_member_recievable_old p ON d.receiveble_id = p.id
                WHERE d.date BETWEEN ? AND ? AND COALESCE(NULLIF(d.head, ''), NULLIF(p.head, ''), '3') != '12' AND (d.cancel = 0 OR d.cancel IS NULL)
            ) AS combined
        ", [$start_date, $end_date, $start_date, $end_date], "ssss");
        $member_fees = $res_member ? (double)$res_member->fetch_assoc()['total'] : 0.0;

        // Guest Fees Received (Head 12) - active + archived
        $res_guest = app_exec_getresult("
            SELECT SUM(fees) as total FROM (
                SELECT d.fees FROM tbl_member_recieved d
                LEFT JOIN tbl_member_recievable p ON d.receiveble_id = p.id
                WHERE d.date BETWEEN ? AND ? AND COALESCE(NULLIF(d.head, ''), NULLIF(p.head, ''), '3') = '12' AND (d.cancel = 0 OR d.cancel IS NULL)
                UNION ALL
                SELECT d.fees FROM tbl_member_recieved_old d
                LEFT JOIN tbl_member_recievable_old p ON d.receiveble_id = p.id
                WHERE d.date BETWEEN ? AND ? AND COALESCE(NULLIF(d.head, ''), NULLIF(p.head, ''), '3') = '12' AND (d.cancel = 0 OR d.cancel IS NULL)
            ) AS combined
        ", [$start_date, $end_date, $start_date, $end_date], "ssss");
        $guest_fees = $res_guest ? (double)$res_guest->fetch_assoc()['total'] : 0.0;

        // Other Payments Received (Other Income + Net Wallet credits/debits in the year) - active + archived
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
        $wallet_amount = $row_wallet ? ((double)$row_wallet['credits'] - (double)$row_wallet['debits']) : 0.0;

        $other_payments_received = $other_rec_amount + $wallet_amount;

        // 3. Calculate current year expenses (paid) - active + archived
        $res_paid = app_exec_getresult("
            SELECT SUM(amount) as total FROM (
                SELECT amount FROM tbl_paid WHERE date BETWEEN ? AND ? AND (cancel = 0 OR cancel IS NULL)
                UNION ALL
                SELECT amount FROM tbl_paid_old WHERE date BETWEEN ? AND ? AND (cancel = 0 OR cancel IS NULL)
            ) AS combined
        ", [$start_date, $end_date, $start_date, $end_date], "ssss");
        $other_payments_paid = $res_paid ? (double)$res_paid->fetch_assoc()['total'] : 0.0;

        // 4. Calculate Closing Balance
        if ($selected_year == 1) {
            $total_inflow = $init_ob + $member_fees + $guest_fees + $other_payments_received;
        } else {
            $total_inflow = $opening_balance + $member_fees + $guest_fees + $other_payments_received;
        }
        $closing_balance = $total_inflow - $other_payments_paid;

        // Bank and Cash in Hand calculations
        // A. Total Bank Deposit + Interest of that year
        $res_bank_dep_year = app_exec_getresult("
            SELECT SUM(amount) as total FROM tbl_bank_transactions WHERE type IN ('Deposit', 'Interest') AND date BETWEEN ? AND ?
        ", [$start_date, $end_date], "ss");
        $total_bank_deposit_year = $res_bank_dep_year ? (double)$res_bank_dep_year->fetch_assoc()['total'] : 0.0;

        // A2. Total Bank Withdrawal of that year
        $res_bank_wth_year = app_exec_getresult("
            SELECT SUM(amount) as total FROM tbl_bank_transactions WHERE type = 'Withdrawal' AND date BETWEEN ? AND ?
        ", [$start_date, $end_date], "ss");
        $total_bank_withdrawal_year = $res_bank_wth_year ? (double)$res_bank_wth_year->fetch_assoc()['total'] : 0.0;

        // B. Cumulative Bank Balance up to end of that financial year (Deposits + Interest - Withdrawals)
        $res_bank_dep_cum = app_exec_getresult("
            SELECT SUM(amount) as total FROM tbl_bank_transactions WHERE type IN ('Deposit', 'Interest') AND date <= ?
        ", [$end_date], "s");
        $bank_dep_cum = $res_bank_dep_cum ? (double)$res_bank_dep_cum->fetch_assoc()['total'] : 0.0;

        $res_bank_wth_cum = app_exec_getresult("
            SELECT SUM(amount) as total FROM tbl_bank_transactions WHERE type = 'Withdrawal' AND date <= ?
        ", [$end_date], "s");
        $bank_wth_cum = $res_bank_wth_cum ? (double)$res_bank_wth_cum->fetch_assoc()['total'] : 0.0;

        $total_bank_balance = $bank_dep_cum - $bank_wth_cum;

        // B2. Bank balance from CASH movements only (Deposits - Withdrawals, NO Interest)
        // Interest goes to bank directly — it never came from physical cash, so it must NOT reduce cash in hand
        $res_bank_dep_cash_cum = app_exec_getresult("
            SELECT SUM(amount) as total FROM tbl_bank_transactions WHERE type = 'Deposit' AND date <= ?
        ", [$end_date], "s");
        $bank_dep_cash_cum = $res_bank_dep_cash_cum ? (double)$res_bank_dep_cash_cum->fetch_assoc()['total'] : 0.0;
        $bank_balance_cash_movement = $bank_dep_cash_cum - $bank_wth_cum;

        // C. Cash in Hand and final Closing Balance
        if ($selected_year == 1) {
            $cash_in_hand = $init_ob;
            $bank_deposit = $total_bank_balance;
        } else {
            $bank_deposit = $total_bank_balance;
            $cash_in_hand = $closing_balance - $bank_balance_cash_movement;
        }
        $closing_balance = $bank_deposit + $cash_in_hand;

        // Fetch Savings Interest Received this year
        $res_sav_int = app_exec_getresult("
            SELECT COALESCE(SUM(amount), 0) as total FROM tbl_bank_transactions WHERE type = 'Interest' AND date BETWEEN ? AND ?
        ", [$start_date, $end_date], "ss");
        $savings_interest_year = $res_sav_int ? (double)$res_sav_int->fetch_assoc()['total'] : 0.0;

        // Fetch FD Interest Received this year
        $res_fd_int = app_exec_getresult("
            SELECT COALESCE(SUM(amount), 0) as total FROM tbl_fd_interest_credits WHERE date BETWEEN ? AND ?
        ", [$start_date, $end_date], "ss");
        $fd_interest_year = $res_fd_int ? (double)$res_fd_int->fetch_assoc()['total'] : 0.0;

        // Fetch Savings Interest details (credits list)
        $res_sav_details = app_exec_getresult("
            SELECT date, amount, description FROM tbl_bank_transactions WHERE type = 'Interest' AND date BETWEEN ? AND ? ORDER BY date DESC
        ", [$start_date, $end_date], "ss");
        $savings_interest_details = [];
        while ($row = $res_sav_details->fetch_assoc()) {
            $savings_interest_details[] = $row;
        }

        // Fetch FD Interest details (credits list)
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

        // Merge and sort in PHP
        $interest_list = [];
        foreach ($savings_interest_details as $item) {
            $interest_list[] = [
                'date' => $item['date'],
                'source' => 'Savings Account',
                'description' => $item['description'] ? $item['description'] : 'Savings Interest Credit',
                'amount' => (double)$item['amount']
            ];
        }
        foreach ($fd_interest_details as $item) {
            $interest_list[] = [
                'date' => $item['date'],
                'source' => 'FD (No: ' . $item['fd_no'] . ', ' . $item['bank_name'] . ')',
                'description' => $item['description'] ? $item['description'] : 'Fixed Deposit Interest Credit',
                'amount' => (double)$item['amount']
            ];
        }

        // Sort by date DESC
        usort($interest_list, function($a, $b) {
            return strcmp($b['date'], $a['date']);
        });

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

        $res_fd_count = app_exec_getresult("
            SELECT COUNT(*) as total FROM tbl_fd_transactions WHERE date <= ?
        ", [$end_date], "s");
        $total_fd_prev_count = $res_fd_count ? (int)$res_fd_count->fetch_assoc()['total'] : 0;

        // Pending Payment Amount (Cumulative up to the end of the selected year)
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

        $total_pending_receivables = $pending_member_fees + $pending_other_receivable;

        $total_assets = $bank_deposit + $cash_in_hand + $total_fd_prev;

        $interestRowsHtml = '';
        if (count($interest_list) > 0) {
            $rowColor = '#f8fafc';
            foreach ($interest_list as $item) {
                $interestRowsHtml .= '
                    <tr bgcolor="' . $rowColor . '">
                        <td align="left" style="border-bottom: 1px solid #e2e8f0; color: #334155; line-height: 1.45;">' . htmlspecialchars($item['date']) . '</td>
                        <td align="left" style="border-bottom: 1px solid #e2e8f0; color: #334155; line-height: 1.45;"><b>' . htmlspecialchars($item['source']) . '</b></td>
                        <td align="left" style="border-bottom: 1px solid #e2e8f0; color: #334155; line-height: 1.45;">' . htmlspecialchars($item['description']) . '</td>
                        <td align="right" style="border-bottom: 1px solid #e2e8f0; color: #047857; text-align: right; line-height: 1.45; font-weight: bold;">₹' . number_format($item['amount'], 2) . '</td>
                    </tr>';
                $rowColor = ($rowColor === '#f8fafc') ? '#ffffff' : '#f8fafc';
            }
        } else {
            $interestRowsHtml = '
                <tr>
                    <td colspan="4" align="center" style="color: #64748b; line-height: 2.0; padding: 10px;">No interest transaction entries recorded for this period.</td>
                </tr>';
        }

        $interestTableHtml = '
            <!-- Row 9.5: Interest Credits Breakdown Table -->
            <tr><td height="22"></td></tr>
            <tr>
                <td style="line-height: 1.5;">
                    <span style="font-size: 11pt; color: #0f172a; font-weight: bold;">Interest Credits Received Breakdown (by Date)</span>
                </td>
            </tr>
            <tr><td height="8"></td></tr>
            <tr>
                <td>
                    <table cellpadding="8" cellspacing="0" style="width: 100%; border: 1px solid #cbd5e1; font-size: 9pt;">
                        <thead>
                            <tr bgcolor="#0f172a" style="color: #ffffff; font-weight: bold;">
                                <th width="18%" align="left" style="font-weight: bold; color: #ffffff; padding: 8px;">Date</th>
                                <th width="32%" align="left" style="font-weight: bold; color: #ffffff; padding: 8px;">Source / Account</th>
                                <th width="32%" align="left" style="font-weight: bold; color: #ffffff; padding: 8px;">Description</th>
                                <th width="18%" align="right" style="font-weight: bold; color: #ffffff; text-align: right; padding: 8px;">Amount</th>
                            </tr>
                        </thead>
                        <tbody>
                            ' . $interestRowsHtml . '
                        </tbody>
                    </table>
                </td>
            </tr>
        ';

        // --- Generate PDF ---
        $pdf = new TCPDF('P', 'mm', 'A4');
        $pdf->setPrintHeader(false);
        $pdf->setPrintFooter(false);
        
        // Set standard margins (15mm left/right, 15mm top)
        $pdf->SetMargins(15, 15, 15);
        $pdf->AddPage();
        
        // Set unicode font to render ₹ symbol properly
        $pdf->SetFont('dejavusans', '', 10);

        // Portrait KPI Metrics Grid (3 rows)
        $kpiHtml = '
            <table cellpadding="12" cellspacing="0" border="0" style="width: 100%;">
                <tr>
                    <td width="48%" style="background-color: #f8fafc; border: 1px solid #cbd5e1; text-align: left; line-height: 1.45;">
                        <span style="font-size: 8pt; color: #64748b; font-weight: bold; text-transform: uppercase;">Opening Balance</span><br>
                        <span style="font-size: 14pt; font-weight: bold; color: #0f172a;">₹' . number_format($opening_balance, 2) . '</span><br>
                        <span style="font-size: 7.5pt; color: #94a3b8;">Initial Admin Cash in Hand</span>
                    </td>
                    <td width="4%" style="background-color: #ffffff; border: none;"></td>
                    <td width="48%" style="background-color: #ecfdf5; border: 1px solid #a7f3d0; text-align: left; line-height: 1.45;">
                        <span style="font-size: 8pt; color: #047857; font-weight: bold; text-transform: uppercase;">Total Available Funds</span><br>
                        <span style="font-size: 14pt; font-weight: bold; color: #065f46;">₹' . number_format($total_inflow, 2) . '</span><br>
                        <span style="font-size: 7.5pt; color: #059669;">Opening Balance + Total Inflow</span>
                    </td>
                </tr>
                <tr>
                    <td colspan="3" height="12" style="background-color: #ffffff; border: none;"></td>
                </tr>
                <tr>
                    <td width="48%" style="background-color: #fef2f2; border: 1px solid #fecaca; text-align: left; line-height: 1.45;">
                        <span style="font-size: 8pt; color: #b91c1c; font-weight: bold; text-transform: uppercase;">Total Expense Paid</span><br>
                        <span style="font-size: 14pt; font-weight: bold; color: #991b1b;">₹' . number_format($other_payments_paid, 2) . '</span><br>
                        <span style="font-size: 7.5pt; color: #dc2626;">Expenses Settled in Period</span>
                    </td>
                    <td width="4%" style="background-color: #ffffff; border: none;"></td>
                    <td width="48%" style="background-color: #eff6ff; border: 1px solid #bfdbfe; text-align: left; line-height: 1.45;">
                        <span style="font-size: 8pt; color: #1d4ed8; font-weight: bold; text-transform: uppercase;">Net Closing Balance</span><br>
                        <span style="font-size: 14pt; font-weight: bold; color: #1e40af;">₹' . number_format($closing_balance, 2) . '</span><br>
                        <span style="font-size: 7.5pt; color: #2563eb;">Bank Balance + Cash in Hand</span>
                    </td>
                </tr>
                <tr>
                    <td width="48%" style="background-color: #f8fafc; border: 1px solid #cbd5e1; text-align: left; line-height: 1.45;">
                        <span style="font-size: 8pt; color: #64748b; font-weight: bold; text-transform: uppercase;">Bank Deposit</span><br>
                        <span style="font-size: 14pt; font-weight: bold; color: #0f172a;">₹' . number_format($bank_deposit, 2) . '</span><br>
                        <span style="font-size: 7.5pt; color: #94a3b8;">Bank Balance</span>
                    </td>
                    <td width="4%" style="background-color: #ffffff; border: none;"></td>
                    <td width="48%" style="background-color: #fefcf0; border: 1px solid #fef08a; text-align: left; line-height: 1.45;">
                        <span style="font-size: 8pt; color: #854d0e; font-weight: bold; text-transform: uppercase;">Cash in Hand</span><br>
                        <span style="font-size: 14pt; font-weight: bold; color: #713f12;">₹' . number_format($cash_in_hand, 2) . '</span><br>
                        <span style="font-size: 7.5pt; color: #a16207;">Closing Balance - Bank Deposits</span>
                    </td>
                </tr>
                <tr><td height="8" colspan="3" style="background-color: #ffffff; border: none; line-height: 1px;"></td></tr>
                <tr>
                    <td width="48%" style="background-color: #f5f3ff; border: 1px solid #ddd6fe; text-align: left; line-height: 1.35;">
                        <span style="font-size: 8pt; color: #6d28d9; font-weight: bold; text-transform: uppercase;">FD Summary (Up to Year End)</span><br>
                        <span style="font-size: 8.5pt; color: #5b21b6;">Principal: <b>₹' . number_format($total_fd_prev_principal, 2) . '</b></span><br>
                        <span style="font-size: 8.5pt; color: #5b21b6;">Interest: <b>₹' . number_format($total_fd_prev_interest, 2) . '</b></span><br>
                        <span style="font-size: 10pt; font-weight: bold; color: #4c1d95; border-top: 1px solid #ddd6fe; padding-top: 3px;">Total: ₹' . number_format($total_fd_prev, 2) . '</span>
                    </td>
                    <td width="4%" style="background-color: #ffffff; border: none;"></td>
                    <td width="48%" style="background-color: #eff6ff; border: 1px solid #bfdbfe; text-align: left; line-height: 1.45; vertical-align: top;">
                        <span style="font-size: 8pt; color: #1d4ed8; font-weight: bold; text-transform: uppercase;">Total FD Count</span><br>
                        <span style="font-size: 14pt; font-weight: bold; color: #1e40af;">' . $total_fd_prev_count . '</span><br>
                        <span style="font-size: 7.5pt; color: #2563eb;">Total FD Certificates</span>
                    </td>
                </tr>
                <tr><td height="8" colspan="3" style="background-color: #ffffff; border: none; line-height: 1px;"></td></tr>
                <tr>
                    <td width="48%" style="background-color: #f0fdf4; border: 1px solid #bbf7d0; text-align: left; line-height: 1.45;">
                        <span style="font-size: 8pt; color: #166534; font-weight: bold; text-transform: uppercase;">Savings Interest Received (FY)</span><br>
                        <span style="font-size: 14pt; font-weight: bold; color: #14532d;">₹' . number_format($savings_interest_year, 2) . '</span><br>
                        <span style="font-size: 7.5pt; color: #15803d;">Earned in Savings Account</span>
                    </td>
                    <td width="4%" style="background-color: #ffffff; border: none;"></td>
                    <td width="48%" style="background-color: #faf5ff; border: 1px solid #e9d5ff; text-align: left; line-height: 1.45;">
                        <span style="font-size: 8pt; color: #6b21a8; font-weight: bold; text-transform: uppercase;">FD Interest Received (FY)</span><br>
                        <span style="font-size: 14pt; font-weight: bold; color: #581c87;">₹' . number_format($fd_interest_year, 2) . '</span><br>
                        <span style="font-size: 7.5pt; color: #7e22ce;">Earned on FD accounts</span>
                    </td>
                </tr>
                <tr><td height="8" colspan="3" style="background-color: #ffffff; border: none; line-height: 1px;"></td></tr>
                <tr>
                    <td width="48%" style="background-color: #fff7ed; border: 1px solid #ffedd5; text-align: left; line-height: 1.45;">
                        <span style="font-size: 8pt; color: #c2410c; font-weight: bold; text-transform: uppercase;">Total Pending Payments</span><br>
                        <span style="font-size: 14pt; font-weight: bold; color: #9a3412;">₹' . number_format($total_pending_receivables, 2) . '</span><br>
                        <span style="font-size: 7.5pt; color: #ea580c;">All-time unpaid receivables</span>
                    </td>
                    <td width="4%" style="background-color: #ffffff; border: none;"></td>
                    <td width="48%" style="background-color: #ffffff; border: none;"></td>
                </tr>
                <tr><td height="8" colspan="3" style="background-color: #ffffff; border: none; line-height: 1px;"></td></tr>
                <tr>
                    <td colspan="3" style="background-color: #fef3c7; border: 1.5px solid #f59e0b; text-align: center; padding: 12px; line-height: 1.45;">
                        <span style="font-size: 8.5pt; color: #92400e; font-weight: bold; text-transform: uppercase;">Total Assets (Bank Deposit + Cash in Hand + FD Amount)</span><br>
                        <span style="font-size: 16pt; font-weight: bold; color: #78350f;">₹' . number_format($total_assets, 2) . '</span><br>
                        <span style="font-size: 7.5pt; color: #b45309;">Total accumulated assets at the end of the financial year</span>
                    </td>
                </tr>
            </table>
        ';

        // Unified Outer Layout Table to ensure perfect vertical flow and prevent overlaps in TCPDF
        $html = '
            <table border="0" cellpadding="0" cellspacing="0" style="width: 100%;">
                <!-- Row 1: Header Banner -->
                <tr>
                    <td>
                        <table cellpadding="12" cellspacing="0" border="0" style="width: 100%; background-color: #0f172a;">
                            <tr>
                                <td style="width: 60%; vertical-align: middle;">
                                    <span style="font-size: 18pt; font-weight: bold; color: #ffffff; letter-spacing: -0.5px;">YMCA Badminton Club</span><br>
                                    <span style="font-size: 10pt; color: #94a3b8; font-weight: 500;">Poovathussery</span>
                                </td>
                                <td style="width: 40%; text-align: right; vertical-align: middle;">
                                    <span style="font-size: 11pt; font-weight: bold; color: #3b82f6; text-transform: uppercase; letter-spacing: 0.5px;">Financial Year Report</span><br>
                                    <span style="font-size: 9pt; color: #94a3b8; font-weight: bold;">FY: ' . $yearLabel . '</span>
                                </td>
                            </tr>
                        </table>
                    </td>
                </tr>
                
                <!-- Row 2: Margin space -->
                <tr><td height="5"></td></tr>
                
                <!-- Row 3: Metadata Info Bar -->
                <tr>
                    <td>
                        <table cellpadding="8" cellspacing="0" border="0" style="width: 100%; background-color: #f8fafc; border-bottom: 1px solid #e2e8f0; font-size: 8.5pt;">
                            <tr>
                                <td style="width: 35%; color: #64748b;">
                                    <b>GENERATED ON:</b> ' . date('d-M-Y H:i') . '
                                </td>
                                <td style="width: 35%; text-align: center; color: #64748b;">
                                    <b>SCOPE:</b> Annual Summary Statement
                                </td>
                                <td style="width: 30%; text-align: right; color: #10b981; font-weight: bold;">
                                    ● ACTIVE / CLOSED
                                </td>
                            </tr>
                        </table>
                    </td>
                </tr>
                
                <!-- Row 4: Margin space -->
                <tr><td height="20"></td></tr>
                
                <!-- Row 5: KPI Metrics Grid -->
                <tr>
                    <td>
                        ' . $kpiHtml . '
                    </td>
                </tr>
                
                <!-- Row 6: Margin space -->
                <tr><td height="22"></td></tr>
                
                <!-- Row 7: Detailed Summary Ledger Title -->
                <tr>
                    <td style="line-height: 1.5;">
                        <span style="font-size: 11pt; color: #0f172a; font-weight: bold;">Detailed Summary Ledger</span>
                    </td>
                </tr>
                
                <!-- Row 8: Margin space -->
                <tr><td height="8"></td></tr>
                
                <!-- Row 9: Detailed Summary Ledger Table -->
                <tr>
                    <td>
                        <table cellpadding="9" cellspacing="0" style="width: 100%; border: 1px solid #cbd5e1; font-size: 9.5pt;">
                            <thead>
                                <tr bgcolor="#1e293b" style="color: #ffffff; font-weight: bold;">
                                    <th width="70%" align="left" style="font-weight: bold; color: #ffffff; padding: 10px;">Category / Transaction Head</th>
                                    <th width="30%" align="right" style="font-weight: bold; color: #ffffff; text-align: right; padding: 10px;">Amount (INR)</th>
                                </tr>
                            </thead>
                            <tbody>
                                <tr bgcolor="#f8fafc">
                                    <td align="left" style="border-bottom: 1px solid #e2e8f0; color: #475569; font-weight: bold; line-height: 1.45;">Opening Balance (Cash in Hand)</td>
                                    <td align="right" style="border-bottom: 1px solid #e2e8f0; color: #475569; font-weight: bold; text-align: right; line-height: 1.45;">₹' . number_format($opening_balance, 2) . '</td>
                                </tr>
                                <tr>
                                    <td align="left" style="border-bottom: 1px solid #e2e8f0; color: #334155; line-height: 1.45;">Member Fees Received</td>
                                    <td align="right" style="border-bottom: 1px solid #e2e8f0; color: #334155; text-align: right; line-height: 1.45;">₹' . number_format($member_fees, 2) . '</td>
                                </tr>
                                <tr bgcolor="#f8fafc">
                                    <td align="left" style="border-bottom: 1px solid #e2e8f0; color: #334155; line-height: 1.45;">Guest Fees Received</td>
                                    <td align="right" style="border-bottom: 1px solid #e2e8f0; color: #334155; text-align: right; line-height: 1.45;">₹' . number_format($guest_fees, 2) . '</td>
                                </tr>
                                <tr>
                                    <td align="left" style="border-bottom: 1px solid #e2e8f0; color: #334155; line-height: 1.45;">Other Income Received <span style="font-size: 8pt; color: #64748b;">(Coaching, Interest, Sponsor, etc.)</span></td>
                                    <td align="right" style="border-bottom: 1px solid #e2e8f0; color: #334155; text-align: right; line-height: 1.45;">₹' . number_format($other_rec_amount, 2) . '</td>
                                </tr>
                                <tr bgcolor="#f8fafc">
                                    <td align="left" style="border-bottom: 1px solid #e2e8f0; color: #334155; line-height: 1.45;">Wallet Net Credits</td>
                                    <td align="right" style="border-bottom: 1px solid #e2e8f0; color: #334155; text-align: right; line-height: 1.45;">₹' . number_format($wallet_amount, 2) . '</td>
                                </tr>
                                <tr bgcolor="#ecfdf5" style="font-weight: bold; color: #047857;">
                                    <td align="left" style="border-bottom: 2px solid #10b981; color: #065f46; font-weight: bold; line-height: 1.45;">Total Available Funds (Inflow)</td>
                                    <td align="right" style="border-bottom: 2px solid #10b981; color: #065f46; font-weight: bold; text-align: right; line-height: 1.45;">₹' . number_format($total_inflow, 2) . '</td>
                                </tr>
                                <tr>
                                    <td align="left" style="border-bottom: 1px solid #e2e8f0; color: #334155; line-height: 1.45;">Other Payments Paid <span style="font-size: 8pt; color: #64748b;">(Expenses / Bank Deposits)</span></td>
                                    <td align="right" style="border-bottom: 1px solid #e2e8f0; color: #b91c1c; text-align: right; line-height: 1.45;">₹' . number_format($other_payments_paid, 2) . '</td>
                                </tr>
                                <tr bgcolor="#eff6ff" style="font-weight: bold; color: #1d4ed8;">
                                    <td align="left" style="border-bottom: 1px solid #cbd5e1; color: #1e40af; font-weight: bold; line-height: 1.45;">Closing Balance</td>
                                    <td align="right" style="border-bottom: 1px solid #cbd5e1; color: #1e40af; font-weight: bold; text-align: right; line-height: 1.45;">₹' . number_format($closing_balance, 2) . '</td>
                                </tr>
                                <tr>
                                    <td align="left" style="border-bottom: 1px solid #cbd5e1; color: #475569; padding-left: 20px; line-height: 1.45;">↳ Bank Deposit</td>
                                    <td align="right" style="border-bottom: 1px solid #cbd5e1; color: #475569; text-align: right; line-height: 1.45;">₹' . number_format($bank_deposit, 2) . '</td>
                                </tr>
                                <tr bgcolor="#f8fafc">
                                    <td align="left" style="border-bottom: 1px solid #cbd5e1; color: #475569; padding-left: 20px; line-height: 1.45;">↳ Cash in Hand</td>
                                    <td align="right" style="border-bottom: 1px solid #cbd5e1; color: #475569; text-align: right; line-height: 1.45;">₹' . number_format($cash_in_hand, 2) . '</td>
                                </tr>
                                <tr bgcolor="#fff7ed">
                                    <td align="left" style="border-bottom: 1px solid #cbd5e1; color: #c2410c; font-weight: bold; padding-left: 20px; line-height: 1.45;">↳ Total Pending Payments <span style="font-size: 8pt; color: #ea580c;">(All-time unpaid receivables)</span></td>
                                    <td align="right" style="border-bottom: 1px solid #cbd5e1; color: #c2410c; font-weight: bold; text-align: right; line-height: 1.45;">₹' . number_format($total_pending_receivables, 2) . '</td>
                                </tr>
                                <tr>
                                    <td align="left" style="border-bottom: 1px solid #cbd5e1; color: #475569; padding-left: 20px; line-height: 1.45;">↳ Savings Account Interest Received (FY)</td>
                                    <td align="right" style="border-bottom: 1px solid #cbd5e1; color: #475569; text-align: right; line-height: 1.45;">₹' . number_format($savings_interest_year, 2) . '</td>
                                </tr>
                                <tr bgcolor="#f8fafc">
                                    <td align="left" style="border-bottom: 1px solid #cbd5e1; color: #475569; padding-left: 20px; line-height: 1.45;">↳ FD Account Interest Received (FY)</td>
                                    <td align="right" style="border-bottom: 1px solid #cbd5e1; color: #475569; text-align: right; line-height: 1.45;">₹' . number_format($fd_interest_year, 2) . '</td>
                                </tr>
                                <tr bgcolor="#f5f5f4" style="font-weight: bold; color: #1c1917;">
                                    <td align="left" style="border-top: 1.5px solid #a8a29e; border-bottom: 2px double #44403c; color: #1c1917; font-weight: bold; line-height: 1.5;">Total Assets (Bank + Cash + FD)</td>
                                    <td align="right" style="border-top: 1.5px solid #a8a29e; border-bottom: 2px double #44403c; color: #1c1917; font-weight: bold; text-align: right; line-height: 1.5;">₹' . number_format($total_assets, 2) . '</td>
                                </tr>
                            </tbody>
                        </table>
                    </td>
                </tr>
                ' . $interestTableHtml . '
                
                <!-- Row 10: Margin space -->
                <tr><td height="32"></td></tr>
                
                <!-- Row 11: Formal Closing Sign-Off -->
                <tr>
                    <td>
                        <table cellpadding="0" cellspacing="0" border="0" style="width: 100%;">
                            <tr>
                                <td style="width: 45%; border-top: 1px solid #cbd5e1; text-align: center; padding-top: 8px; line-height: 1.4;">
                                    <span style="font-size: 8.5pt; color: #475569; font-weight: bold;">Prepared By</span><br>
                                    <span style="font-size: 8pt; color: #64748b;">Club Treasurer / Administrator</span>
                                </td>
                                <td style="width: 10%;"></td>
                                <td style="width: 45%; border-top: 1px solid #cbd5e1; text-align: center; padding-top: 8px; line-height: 1.4;">
                                    <span style="font-size: 8.5pt; color: #475569; font-weight: bold;">Approved By</span><br>
                                    <span style="font-size: 8pt; color: #64748b;">President / General Secretary</span>
                                </td>
                            </tr>
                        </table>
                    </td>
                </tr>
                
                <!-- Row 12: Margin space -->
                <tr><td height="30"></td></tr>
                
                <!-- Row 13: Footer Text -->
                <tr>
                    <td align="center" style="line-height: 1.4;">
                        <font size="2" color="#94a3b8"><i>This is an officially generated financial statement of YMCA Badminton Club Poovathussery.</i></font>
                    </td>
                </tr>
            </table>
        ';

        $pdf->writeHTML($html, true, false, true, false, '');

        ob_end_clean();

        header('Content-Type: application/pdf');
        header('Content-Disposition: inline; filename="Financial_Year_Summary.pdf"');

        $pdf->Output('Financial_Year_Summary.pdf', 'I');
        exit;
    } else {
        ob_end_clean();
        http_response_code(500);
        exit("Invalid request method.");
    }
?>
