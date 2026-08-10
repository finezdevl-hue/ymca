<?php
// Quick debug script - run via browser: http://localhost/ymca_new/scratch/debug_home.php
session_start();
header('Content-Type: text/plain');

echo "=== SESSION DATA ===\n";
echo "login_id: " . ($_SESSION['login_id'] ?? 'NOT SET') . "\n";
echo "user_id:  " . ($_SESSION['user_id']  ?? 'NOT SET') . "\n";
echo "email:    " . ($_SESSION['email']    ?? 'NOT SET') . "\n";
echo "name:     " . ($_SESSION['name']     ?? 'NOT SET') . "\n";
echo "\n";

include_once '../app_common/db_connect.php';

$member_id = (int)($_SESSION['user_id'] ?? 0);
echo "member_id from session: $member_id\n\n";

// Simulate what member_cashbook_report.php does
$year = ((int)date('n') >= 4) ? (int)date('Y') : (int)date('Y') - 1;
$start_date = $year . "-04-01";
$end_date   = ($year+1) . "-03-31";
echo "FY: $start_date to $end_date\n\n";

// Opening balance
$op_sql = "SELECT 
    (SELECT IFNULL(SUM(fees),0) FROM tbl_member_recievable     WHERE member_id=? AND date < ? AND cancel=0) +
    (SELECT IFNULL(SUM(fees),0) FROM tbl_member_recievable_old WHERE member_id=? AND date < ? AND cancel=0) -
    (SELECT IFNULL(SUM(amount),0) FROM tbl_cashbook            WHERE member_id=? AND date < ? AND cancel=0) -
    (SELECT IFNULL(SUM(fees),0)   FROM tbl_wallet              WHERE member_id=? AND date < ? AND cancel=0 AND type='credit') +
    (SELECT IFNULL(SUM(fees),0)   FROM tbl_wallet              WHERE member_id=? AND date < ? AND cancel=0 AND type='debit')
    AS opening_balance";

$op_res = app_exec_getresult($op_sql, 
    [$member_id,$start_date,$member_id,$start_date,$member_id,$start_date,$member_id,$start_date,$member_id,$start_date],
    "sisisisisisi"
);
if($op_res && $row = $op_res->fetch_assoc()) {
    $opening = (float)$row['opening_balance'];
} else {
    $opening = 0;
}
echo "Opening balance: $opening\n";

// FY receivables
$r1 = app_exec_getresult("SELECT IFNULL(SUM(fees),0) AS v FROM tbl_member_recievable     WHERE member_id=? AND date BETWEEN ? AND ? AND cancel=0", [$member_id,$start_date,$end_date],"iss");
$r2 = app_exec_getresult("SELECT IFNULL(SUM(fees),0) AS v FROM tbl_member_recievable_old WHERE member_id=? AND date BETWEEN ? AND ? AND cancel=0", [$member_id,$start_date,$end_date],"iss");
$r3 = app_exec_getresult("SELECT IFNULL(SUM(amount),0) AS v FROM tbl_cashbook            WHERE member_id=? AND date BETWEEN ? AND ? AND cancel=0", [$member_id,$start_date,$end_date],"iss");

$fy_recv  = $r1 ? (float)$r1->fetch_assoc()['v'] : 0;
$fy_recv += $r2 ? (float)$r2->fetch_assoc()['v'] : 0;
$fy_paid  = $r3 ? (float)$r3->fetch_assoc()['v'] : 0;

echo "FY Receivables: $fy_recv\n";
echo "FY Payments:    $fy_paid\n";

$closing = $opening + $fy_recv - $fy_paid;
echo "Closing balance: $closing\n\n";

// Now call API directly
echo "=== CALLING API DIRECTLY ===\n";
$_POST['action']    = 'get_member_cashbook';
$_POST['member_id'] = $member_id;
$_POST['year']      = $year;
session_write_close();
ob_start();
chdir(__DIR__ . '/../directory/api');
include 'member_cashbook_report.php';
$out = ob_get_clean();
$d = json_decode($out, true);
echo "API closing_balance: " . ($d['summary']['closing_balance'] ?? 'NOT IN RESPONSE') . "\n";
echo "Raw API (first 500 chars): " . substr($out, 0, 500) . "\n";
