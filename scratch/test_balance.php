<?php
include 'app_common/db_connect.php';

$res = app_exec_query("SELECT id, first_name, last_name FROM tbl_members LIMIT 10");
while($m = $res->fetch_assoc()) {
    $user_id = $m['id'];
    
    // FY calculation
    $current_month = (int)date('n');
    $year = ($current_month >= 4) ? (int)date('Y') : (int)date('Y') - 1;
    $start_date = $year . "-04-01";
    $end_date = ($year + 1) . "-03-31";

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
    $op_res = app_exec_getresult($op_sql, [$user_id, $start_date, $user_id, $start_date, $user_id, $start_date, $user_id, $start_date, $user_id, $start_date], "isisisisis");
    $op = 0;
    if ($op_res && $row = $op_res->fetch_assoc()) { $op = (double)$row['opening_balance']; }

    $rec_sql = "SELECT 
                COALESCE((SELECT SUM(fees) FROM tbl_member_recievable WHERE member_id = ? AND date BETWEEN ? AND ? AND cancel = 0), 0)
                +
                COALESCE((SELECT SUM(fees) FROM tbl_member_recievable_old WHERE member_id = ? AND date BETWEEN ? AND ? AND cancel = 0), 0)
                AS total_rec";
    $rec_res = app_exec_getresult($rec_sql, [$user_id, $start_date, $end_date, $user_id, $start_date, $end_date], "ississ");
    $rec = 0;
    if ($rec_res && $row = $rec_res->fetch_assoc()) { $rec = (double)$row['total_rec']; }

    $pay_sql = "SELECT 
                COALESCE((SELECT SUM(fees) FROM tbl_member_recieved WHERE member_id = ? AND date BETWEEN ? AND ? AND cancel = 0), 0)
                +
                COALESCE((SELECT SUM(fees) FROM tbl_member_recieved_old WHERE member_id = ? AND date BETWEEN ? AND ? AND cancel = 0), 0)
                AS total_pay";
    $pay_res = app_exec_getresult($pay_sql, [$user_id, $start_date, $end_date, $user_id, $start_date, $end_date], "ississ");
    $pay = 0;
    if ($pay_res && $row = $pay_res->fetch_assoc()) { $pay = (double)$row['total_pay']; }

    // Direct sum without FY filter
    $all_rec = app_exec_getresult("SELECT SUM(fees) AS s FROM tbl_member_recievable WHERE member_id = ? AND cancel = 0", [$user_id], "i")->fetch_assoc()['s'] ?? 0;
    $all_rec_old = app_exec_getresult("SELECT SUM(fees) AS s FROM tbl_member_recievable_old WHERE member_id = ? AND cancel = 0", [$user_id], "i")->fetch_assoc()['s'] ?? 0;
    $all_pay = app_exec_getresult("SELECT SUM(fees) AS s FROM tbl_member_recieved WHERE member_id = ? AND cancel = 0", [$user_id], "i")->fetch_assoc()['s'] ?? 0;
    $all_pay_old = app_exec_getresult("SELECT SUM(fees) AS s FROM tbl_member_recieved_old WHERE member_id = ? AND cancel = 0", [$user_id], "i")->fetch_assoc()['s'] ?? 0;
    $direct_bal = ($all_rec + $all_rec_old) - ($all_pay + $all_pay_old);

    echo "Member ID {$user_id} ({$m['first_name']} {$m['last_name']}): FY Opening={$op}, FY Rec={$rec}, FY Pay={$pay} => FY Balance=" . ($op + $rec - $pay) . " | All-Time Direct Balance={$direct_bal}\n";
}
