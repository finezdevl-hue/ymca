<?php
session_start();
include_once __DIR__ . '/../../app_common/db_connect.php';
include_once __DIR__ . '/../../app_pagination/pagination.php';

// Check user authentication
if (empty($_SESSION['login_id'])) {
    http_response_code(401);
    echo json_encode(["Message" => "Unauthorized access."]);
    exit();
}

if (isset($_POST['action']) && !empty($_POST['action'])) {
    $action = $_POST['action'];

    // Action 1: Load members with search and pagination
    if ($action == "load_members") {
        try {
            $rowsPerPage = 8;
            $current_page = isset($_POST['page']) ? (int)$_POST['page'] : 1;
            $offset = ($current_page - 1) * $rowsPerPage;

            $search = isset($_POST['val']) ? trim($_POST['val']) : '';

            // Construct queries
            $sqldatarows = "SELECT m.id, m.first_name, m.middle_name, m.last_name, m.phone, m.img,
                                   GROUP_CONCAT(g.name SEPARATOR ', ') AS group_names
                            FROM tbl_members AS m
                            LEFT JOIN tbl_group_member_map AS gmm ON m.id = gmm.member_id
                            LEFT JOIN tbl_groups AS g ON gmm.group_id = g.id AND g.status = 1
                            WHERE m.inactive = 0";
            
            $sqlcountrows = "SELECT COUNT(DISTINCT m.id) AS total
                             FROM tbl_members AS m
                             LEFT JOIN tbl_group_member_map AS gmm ON m.id = gmm.member_id
                             LEFT JOIN tbl_groups AS g ON gmm.group_id = g.id AND g.status = 1
                             WHERE m.inactive = 0";

            $parameters = [];
            $types = "";

            if ($search !== '') {
                $sqldatarows .= " AND (m.first_name LIKE ? OR m.middle_name LIKE ? OR m.last_name LIKE ? OR m.phone LIKE ?)";
                $sqlcountrows .= " AND (m.first_name LIKE ? OR m.middle_name LIKE ? OR m.last_name LIKE ? OR m.phone LIKE ?)";
                $search_param = "%" . $search . "%";
                $parameters = [$search_param, $search_param, $search_param, $search_param];
                $types = "ssss";
            }

            $sqldatarows .= " GROUP BY m.id ORDER BY m.first_name, m.middle_name, m.last_name";
            
            $parameters_data = $parameters;
            $types_data = $types;

            if ($types !== "") {
                // With search: use prepared statement with LIMIT ?, ?
                $sqldatarows .= " LIMIT ?, ?";
                $parameters_data[] = $offset;
                $parameters_data[] = $rowsPerPage;
                $types_data = $types . "ii";

                $result = app_exec_getresult($sqldatarows, $parameters_data, $types_data);
                $totalRowsResult = app_exec_getresult($sqlcountrows, $parameters, $types);
            } else {
                // No search: use direct query with explicit LIMIT values
                $sqldatarows .= " LIMIT " . $offset . ", " . $rowsPerPage;
                $result = app_exec_query($sqldatarows);
                $totalRowsResult = app_exec_query($sqlcountrows);
            }

            $totalRows = $totalRowsResult->fetch_assoc()['total'];

            $qrydata = [];
            if ($result && $result->num_rows > 0) {
                while ($row = $result->fetch_assoc()) {
                    $qrydata[] = $row;
                }
            }

            $pagination = array("total_rows" => $totalRows);
            echo json_encode([$pagination, $qrydata]);
            exit();

        } catch (Exception $e) {
            http_response_code(500);
            echo json_encode(["Message" => "Error loading members: " . $e->getMessage()]);
            exit();
        }
    }

    // Action 2: Get member cash book ledger statement
    if ($action == "get_member_cashbook") {
        try {
            $member_id = isset($_POST['member_id']) ? (int)$_POST['member_id'] : 0;
            if ($member_id <= 0) {
                $member_id = !empty($_SESSION['user_id']) ? (int)$_SESSION['user_id'] : 0;
            }
            if ($member_id <= 0 && !empty($_SESSION['email'])) {
                $sess_email = trim($_SESSION['email']);
                $res_u = app_exec_getresult("SELECT id FROM tbl_members WHERE LTRIM(RTRIM(LOWER(email))) = LTRIM(RTRIM(LOWER(?))) AND inactive = 0 LIMIT 1", [$sess_email], "s");
                if ($res_u && $ru = $res_u->fetch_assoc()) {
                    $member_id = (int)$ru['id'];
                }
            }
            if ($member_id <= 0 && !empty($_SESSION['name'])) {
                $sess_name = trim($_SESSION['name']);
                $first_w = explode(' ', $sess_name)[0];
                $res_u = app_exec_getresult("SELECT id FROM tbl_members WHERE (first_name LIKE ? OR CONCAT(first_name, ' ', last_name) LIKE ?) AND inactive = 0 LIMIT 1", ['%' . $first_w . '%', '%' . $sess_name . '%'], "ss");
                if ($res_u && $ru = $res_u->fetch_assoc()) {
                    $member_id = (int)$ru['id'];
                }
            }
            if ($member_id <= 0) {
                $res_u = app_exec_query("SELECT member_id FROM tbl_member_recievable WHERE cancel = 0 GROUP BY member_id ORDER BY SUM(fees) DESC LIMIT 1");
                if ($res_u && $ru = $res_u->fetch_assoc()) {
                    $member_id = (int)$ru['member_id'];
                }
            }

            $year = isset($_POST['year']) ? (int)$_POST['year'] : 0;
            if ($year <= 0) {
                $year = ((int)date('n') >= 4) ? (int)date('Y') : (int)date('Y') - 1;
            }

            if ($member_id <= 0 || $year <= 0) {
                http_response_code(400);
                echo json_encode(["Message" => "Invalid parameters."]);
                exit();
            }

            $start_date = $year . "-04-01";
            $end_date = ($year + 1) . "-03-31";

            // 1. Get member details
            $details_sql = "SELECT m.id, m.first_name, m.middle_name, m.last_name, m.email, m.phone, m.img,
                                   GROUP_CONCAT(g.name SEPARATOR ', ') AS group_names
                            FROM tbl_members AS m
                            LEFT JOIN tbl_group_member_map AS gmm ON m.id = gmm.member_id
                            LEFT JOIN tbl_groups AS g ON gmm.group_id = g.id AND g.status = 1
                            WHERE m.id = ?
                            GROUP BY m.id";
            $details_res = app_exec_getresult($details_sql, [$member_id], "i");
            $member_info = null;
            if ($details_res && $row = $details_res->fetch_assoc()) {
                $member_info = $row;
            }

            if (!$member_info) {
                http_response_code(404);
                echo json_encode(["Message" => "Member not found."]);
                exit();
            }

            // 2. Calculate Opening Balance prior to start_date (including previous wallet transactions)
            $op_sql = "SELECT 
                        (
                            (SELECT IFNULL(SUM(fees), 0) FROM tbl_member_recievable WHERE member_id = ? AND date < ? AND cancel = 0) +
                            (SELECT IFNULL(SUM(fees), 0) FROM tbl_member_recievable_old WHERE member_id = ? AND date < ? AND cancel = 0)
                        ) -
                        (
                            (SELECT IFNULL(SUM(fees), 0) FROM tbl_member_recieved WHERE member_id = ? AND date < ? AND cancel = 0) +
                            (SELECT IFNULL(SUM(fees), 0) FROM tbl_member_recieved_old WHERE member_id = ? AND date < ? AND cancel = 0)
                        ) +
                        (SELECT COALESCE(SUM(CASE WHEN type = 'debit' THEN amount ELSE 0 END) - SUM(CASE WHEN type = 'credit' THEN amount ELSE 0 END), 0) FROM tbl_wallet WHERE client_id = ? AND date < ?)
                       AS opening_balance";
            $op_res = app_exec_getresult($op_sql, [
                $member_id, $start_date,
                $member_id, $start_date,
                $member_id, $start_date,
                $member_id, $start_date,
                $member_id, $start_date
            ], "isisisisis");
            $opening_balance = 0;
            if ($op_res && $row = $op_res->fetch_assoc()) {
                $opening_balance = (double)$row['opening_balance'];
            }

            // 3. Generate the 12 months of the financial year
            $months = [];
            for ($m = 4; $m <= 15; $m++) {
                $monthNum = $m > 12 ? $m - 12 : $m;
                $yearNum = $m > 12 ? $year + 1 : $year;
                
                $monthKey = sprintf("%d-%02d", $yearNum, $monthNum);
                $monthName = date('F', mktime(0, 0, 0, $monthNum, 1));
                
                $months[$monthKey] = [
                    "month_name" => $monthName,
                    "year" => $yearNum,
                    "month_num" => $monthNum,
                    "attendance" => null,
                    "is_receiveble_set" => false,
                    "is_processed" => false
                ];
            }

            // Get monthly attendance records
            $att_sql = "SELECT id, from_date, to_date, attendance, isreceiveble 
                        FROM tbl_monthly_attendance 
                        WHERE member_id = ? AND from_date BETWEEN ? AND ?";
            $att_res = app_exec_getresult($att_sql, [$member_id, $start_date, $end_date], "iss");
            if ($att_res && $att_res->num_rows > 0) {
                while ($row = $att_res->fetch_assoc()) {
                    $m_key = substr($row['from_date'], 0, 7);
                    if (isset($months[$m_key])) {
                        $months[$m_key]['attendance'] = (int)$row['attendance'];
                        $months[$m_key]['is_receiveble_set'] = (int)$row['isreceiveble'];
                        $months[$m_key]['is_processed'] = true;
                    }
                }
            }

            // Check overall fixed or processed months
            $fixed_months_map = [];
            $fix_res = app_exec_query("SELECT fixed_month FROM tbl_fixed_months");
            if ($fix_res && $fix_res->num_rows > 0) {
                while ($frow = $fix_res->fetch_assoc()) {
                    $fixed_months_map[$frow['fixed_month']] = true;
                }
            }
            $proc_res = app_exec_query("SELECT DISTINCT DATE_FORMAT(from_date, '%Y-%m') AS ym FROM tbl_monthly_attendance");
            if ($proc_res && $proc_res->num_rows > 0) {
                while ($prow = $proc_res->fetch_assoc()) {
                    $fixed_months_map[$prow['ym']] = true;
                }
            }
            foreach ($months as $m_key => $m_val) {
                if (!empty($fixed_months_map[$m_key])) {
                    $months[$m_key]['is_processed'] = true;
                }
            }

            // Live count from tbl_attendance
            $att_live_sql = "SELECT DATE_FORMAT(date, '%Y-%m') AS ym, COUNT(DISTINCT date) AS cnt
                             FROM tbl_attendance
                             WHERE member_id = ? AND date BETWEEN ? AND ?
                             GROUP BY DATE_FORMAT(date, '%Y-%m')";
            $att_live_res = app_exec_getresult($att_live_sql, [$member_id, $start_date, $end_date], "iss");
            if ($att_live_res && $att_live_res->num_rows > 0) {
                while ($r = $att_live_res->fetch_assoc()) {
                    if (isset($months[$r['ym']])) {
                        $months[$r['ym']]['attendance'] = max((int)($months[$r['ym']]['attendance'] ?? 0), (int)$r['cnt']);
                    }
                }
            }

            // Get all receivables in financial year
            $rec_sql = "SELECT id, date, fees, discription, head 
                        FROM tbl_member_recievable 
                        WHERE member_id = ? AND date BETWEEN ? AND ? AND cancel = 0
                        UNION ALL
                        SELECT id, date, fees, discription, head 
                        FROM tbl_member_recievable_old 
                        WHERE member_id = ? AND date BETWEEN ? AND ? AND cancel = 0";
            $rec_res = app_exec_getresult($rec_sql, [
                $member_id, $start_date, $end_date,
                $member_id, $start_date, $end_date
            ], "ississ");
            $all_rec = [];
            if ($rec_res && $rec_res->num_rows > 0) {
                while ($row = $rec_res->fetch_assoc()) {
                    $all_rec[] = $row;
                }
            }

            // Get all payments in financial year
            $pay_sql = "SELECT 
                          r.id, 
                          r.date, 
                          r.fees, 
                          IF(r.discription IS NOT NULL AND r.discription != '', r.discription, COALESCE(b.discription, bold.discription, '')) AS particulars, 
                          COALESCE(b.date, bold.date) AS fee_date,
                          IF(r.head IS NOT NULL AND r.head != '', r.head, COALESCE(b.head, bold.head)) AS head,
                          r.receiveble_id,
                          r.iswallet
                        FROM tbl_member_recieved AS r
                        LEFT JOIN tbl_member_recievable AS b ON r.receiveble_id = b.id
                        LEFT JOIN tbl_member_recievable_old AS bold ON r.receiveble_id = bold.id
                        WHERE r.member_id = ? AND r.date BETWEEN ? AND ? AND r.cancel = 0
                        UNION ALL
                        SELECT 
                          r.id, 
                          r.date, 
                          r.fees, 
                          IF(r.discription IS NOT NULL AND r.discription != '', r.discription, COALESCE(b.discription, bold.discription, '')) AS particulars, 
                          COALESCE(b.date, bold.date) AS fee_date,
                          IF(r.head IS NOT NULL AND r.head != '', r.head, COALESCE(b.head, bold.head)) AS head,
                          r.receiveble_id,
                          r.iswallet
                        FROM tbl_member_recieved_old AS r
                        LEFT JOIN tbl_member_recievable AS b ON r.receiveble_id = b.id
                        LEFT JOIN tbl_member_recievable_old AS bold ON r.receiveble_id = bold.id
                        WHERE r.member_id = ? AND r.date BETWEEN ? AND ? AND r.cancel = 0";
            $pay_res = app_exec_getresult($pay_sql, [
                $member_id, $start_date, $end_date,
                $member_id, $start_date, $end_date
            ], "ississ");
            $all_pay = [];
            if ($pay_res && $pay_res->num_rows > 0) {
                while ($row = $pay_res->fetch_assoc()) {
                    $all_pay[] = $row;
                }
            }

            // Get all wallet transactions in financial year
            $wallet_sql = "SELECT id, date, amount, type 
                           FROM tbl_wallet 
                           WHERE client_id = ? AND date BETWEEN ? AND ?";
            $wallet_res = app_exec_getresult($wallet_sql, [$member_id, $start_date, $end_date], "iss");
            $all_wallet = [];
            if ($wallet_res && $wallet_res->num_rows > 0) {
                while ($row = $wallet_res->fetch_assoc()) {
                    $all_wallet[] = $row;
                }
            }

            $matched_rec_ids = [];
            $final_transactions = [];

            $current_ym = date('Y-m');

            // 1. Process the 12 months for receivables (debits)
            foreach ($months as $m_key => &$m_info) {
                // Hide upcoming/future months
                if ($m_key > $current_ym) {
                    continue;
                }

                $month_name = $m_info['month_name'];
                $m_year = $m_info['year'];
                $att_count = (int)($m_info['attendance'] ?? 0);
                
                $found_recs = [];
                $short_month = substr($month_name, 0, 3);
                $all_month_names = ['january','february','march','april','may','june','july','august','september','october','november','december',
                                    'jan','feb','mar','apr','may','jun','jul','aug','sep','oct','nov','dec'];

                foreach ($all_rec as $rec) {
                    if (in_array($rec['id'], $matched_rec_ids)) continue;
                    
                    $desc_lower = strtolower($rec['discription'] ?? '');
                    $desc_match = (stripos($rec['discription'], $month_name) !== false || stripos($rec['discription'], $short_month) !== false);
                    
                    $has_explicit_month = false;
                    foreach ($all_month_names as $mn) {
                        if (strpos($desc_lower, $mn) !== false) {
                            $has_explicit_month = true;
                            break;
                        }
                    }

                    if ($has_explicit_month) {
                        // Fee description names a specific month -> match strictly by description
                        if ($desc_match) {
                            $found_recs[] = $rec;
                            $matched_rec_ids[] = $rec['id'];
                        }
                    } else {
                        // Fee description has no month name -> match by record date
                        $rec_ym = substr($rec['date'], 0, 7);
                        if ($rec_ym == $m_key) {
                            $found_recs[] = $rec;
                            $matched_rec_ids[] = $rec['id'];
                        }
                    }
                }

                if (count($found_recs) > 0) {
                    foreach ($found_recs as $fr) {
                        $trans_date = !empty($m_key) ? ($m_key . "-01") : $fr['date'];
                        $rec_title = trim($fr['discription'] ?? '');
                        if (empty($rec_title) && !empty($fr['date'])) {
                            $rtime = strtotime($fr['date']);
                            if ($rtime) $rec_title = date('F Y', $rtime) . " Fee";
                        }
                        if (empty($rec_title)) $rec_title = "$month_name $m_year Fee";

                        $final_transactions[] = [
                            "date" => $trans_date,
                            "type" => "Receivable",
                            "particulars" => $rec_title,
                            "debit" => (double)$fr['fees'],
                            "credit" => 0.0,
                            "sort_date" => $trans_date,
                            "attendance" => $att_count
                        ];
                    }
                } else if ($m_key <= $current_ym) {
                    // Show past or current months
                    $particulars = "$month_name $m_year Fee";
                    $final_transactions[] = [
                        "date" => $m_key . "-01",
                        "type" => "Receivable",
                        "particulars" => $particulars,
                        "debit" => 0.0,
                        "credit" => 0.0,
                        "sort_date" => $m_key . "-01",
                        "attendance" => $att_count
                    ];
                }
            }

            // 2. Add other unmatched receivables
            foreach ($all_rec as $rec) {
                if (!in_array($rec['id'], $matched_rec_ids)) {
                    $rec_title = trim($rec['discription'] ?? '');
                    if (empty($rec_title) && !empty($rec['date'])) {
                        $rtime = strtotime($rec['date']);
                        if ($rtime) $rec_title = date('F Y', $rtime) . " Fee";
                    }
                    if (empty($rec_title)) $rec_title = "Other Fee";

                    $final_transactions[] = [
                        "date" => $rec['date'],
                        "type" => "Receivable",
                        "particulars" => $rec_title,
                        "debit" => (double)$rec['fees'],
                        "credit" => 0.0,
                        "sort_date" => $rec['date']
                    ];
                }
            }

            // 3. Group payments by date and payment type (Cash/Bank vs Wallet)
            $grouped_payments = [];
            foreach ($all_pay as $pay) {
                $fees = (double)$pay['fees'];
                if ($fees <= 0) continue; // Skip zero/placeholder payments
                
                $pay_date = $pay['date'];
                $is_wallet = (int)$pay['iswallet'];
                $group_key = $pay_date . "_" . $is_wallet;
                
                if (!isset($grouped_payments[$group_key])) {
                    $grouped_payments[$group_key] = [
                        "date" => $pay_date,
                        "iswallet" => $is_wallet,
                        "fees" => 0.0,
                        "particulars_arr" => []
                    ];
                }
                
                $grouped_payments[$group_key]['fees'] += $fees;
                $pay_part = trim($pay['particulars'] ?? '');
                if (empty($pay_part) && !empty($pay['fee_date'])) {
                    $ftime = strtotime($pay['fee_date']);
                    if ($ftime) {
                        $pay_part = date('F Y', $ftime) . " Fee";
                    }
                }
                if (!empty($pay_part)) {
                    $grouped_payments[$group_key]['particulars_arr'][] = $pay_part;
                }
            }

            // Add grouped payments to transaction list
            foreach ($grouped_payments as $group_key => $p_info) {
                $desc = "Payment";
                if (!empty($p_info['particulars_arr'])) {
                    $desc .= " (" . implode(", ", array_unique($p_info['particulars_arr'])) . ")";
                }
                
                if ($p_info['iswallet'] == 1) {
                    $desc .= " (Paid from Wallet)";
                    $final_transactions[] = [
                        "date" => $p_info['date'],
                        "type" => "Payment",
                        "particulars" => $desc,
                        "debit" => 0.0,
                        "credit" => (double)$p_info['fees'],
                        "sort_date" => $p_info['date']
                    ];
                } else {
                    $desc .= " (Cash/Bank)";
                    $final_transactions[] = [
                        "date" => $p_info['date'],
                        "type" => "Payment",
                        "particulars" => $desc,
                        "debit" => 0.0,
                        "credit" => (double)$p_info['fees'],
                        "sort_date" => $p_info['date']
                    ];
                }
            }

            // 4. Process wallet transactions
            foreach ($all_wallet as $w) {
                $amount = (double)$w['amount'];
                if ($w['type'] == 'credit') {
                    $final_transactions[] = [
                        "date" => $w['date'],
                        "type" => "Wallet Credit",
                        "particulars" => "Wallet Deposit (Credit)",
                        "debit" => 0.0,
                        "credit" => $amount,
                        "sort_date" => $w['date']
                    ];
                } else if ($w['type'] == 'debit') {
                    // Check if this wallet debit was used for fee payment (iswallet = 1)
                    $is_used_for_payment = false;
                    foreach ($all_pay as $pay) {
                        if (!empty($pay['iswallet']) && (int)$pay['iswallet'] === 1 && $pay['date'] == $w['date'] && abs((double)$pay['fees'] - $amount) < 0.01) {
                            $is_used_for_payment = true;
                            break;
                        }
                    }
                    if (!$is_used_for_payment) {
                        $final_transactions[] = [
                            "date" => $w['date'],
                            "type" => "Wallet Debit",
                            "particulars" => "Wallet Charge (Debit)",
                            "debit" => $amount,
                            "credit" => 0.0,
                            "sort_date" => $w['date']
                        ];
                    }
                }
            }

            // Sort chronologically
            usort($final_transactions, function($a, $b) {
                return strcmp($a['sort_date'], $b['sort_date']);
            });

            // Calculate running balance and totals for summary
            $transactions = [];
            $total_debit = 0;
            $total_credit = 0;
            $running_balance = $opening_balance;

            // Build monthly attendance map & processed months map
            $monthly_attendance = [];
            $processed_months = [];
            foreach ($months as $m_key => $m_val) {
                $monthly_attendance[$m_key] = (int)($m_val['attendance'] ?? 0);
                $processed_months[$m_key] = !empty($m_val['is_processed']);
            }

            foreach ($final_transactions as $trans) {
                $debit = (double)$trans['debit'];
                $credit = (double)$trans['credit'];
                $is_wallet_payment = (stripos($trans['particulars'] ?? '', 'Paid from Wallet') !== false);
                
                $total_debit += $debit;
                $total_credit += $credit;
                
                if ($is_wallet_payment) {
                    // Paid from wallet consumes opening wallet credit; net dues balance remains unchanged
                    $running_balance += $debit;
                } else {
                    $running_balance += ($debit - $credit);
                }

                $t_ym = substr($trans['date'], 0, 7);

                $transactions[] = [
                    "date" => $trans['date'],
                    "type" => $trans['type'],
                    "particulars" => $trans['particulars'],
                    "debit" => $debit,
                    "credit" => $credit,
                    "balance" => $running_balance,
                    "attendance" => isset($monthly_attendance[$t_ym]) ? $monthly_attendance[$t_ym] : 0,
                    "is_processed" => !empty($processed_months[$t_ym])
                ];
            }

            // Fetch payment / UPI settings
            $payment_settings = [
                "upi_id" => "ymcabcp@okaxis",
                "payee_name" => "YMCA BCP Poovathussery",
                "payment_note" => "YMCA Member Fee Payment",
                "is_active" => 1
            ];
            $pay_res = app_exec_query("SELECT upi_id, payee_name, payment_note, is_active FROM tbl_payment_settings WHERE id = 1");
            if ($pay_res && $p_row = $pay_res->fetch_assoc()) {
                $payment_settings = [
                    "upi_id" => $p_row['upi_id'],
                    "payee_name" => $p_row['payee_name'],
                    "payment_note" => $p_row['payment_note'],
                    "is_active" => (int)$p_row['is_active']
                ];
            }

            $summary = [
                "opening_balance" => $opening_balance,
                "total_debit" => $total_debit,
                "total_credit" => $total_credit,
                "closing_balance" => $running_balance,
                "monthly_attendance" => $monthly_attendance,
                "processed_months" => $processed_months,
                "payment_settings" => $payment_settings
            ];

            // Reverse transactions array to show newest months first (descending chronological order)
            $transactions = array_reverse($transactions);

            echo json_encode([
                "member_info" => $member_info,
                "summary" => $summary,
                "transactions" => $transactions
            ]);
            exit();

        } catch (Exception $e) {
            http_response_code(500);
            echo json_encode(["Message" => "Error loading cash book: " . $e->getMessage()]);
            exit();
        }
    }
} else {
    http_response_code(400);
    echo json_encode(["Message" => "No action specified or invalid request."]);
    exit();
}
?>
