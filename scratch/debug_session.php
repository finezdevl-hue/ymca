<?php
// Run via browser: http://localhost/ymca_new/scratch/debug_session.php
session_start();
header('Content-Type: text/plain');

echo "login_id = " . var_export($_SESSION['login_id'] ?? null, true) . "\n";
echo "user_id  = " . var_export($_SESSION['user_id']  ?? null, true) . "\n";
echo "email    = " . var_export($_SESSION['email']    ?? null, true) . "\n";
echo "name     = " . var_export($_SESSION['name']     ?? null, true) . "\n";
echo "\n";

if (empty($_SESSION['login_id'])) {
    echo "NOT LOGGED IN - no session found\n";
    exit;
}

include_once '../app_common/db_connect.php';

$member_id = (int)($_SESSION['user_id'] ?? 0);
echo "member_id = $member_id\n\n";

if ($member_id <= 0) {
    echo "user_id is 0! Trying email lookup...\n";
    if (!empty($_SESSION['email'])) {
        $res = app_exec_getresult("SELECT id FROM tbl_members WHERE LTRIM(RTRIM(LOWER(email))) = LTRIM(RTRIM(LOWER(?))) AND inactive=0 LIMIT 1", [$_SESSION['email']], "s");
        if ($res && $r = $res->fetch_assoc()) {
            echo "Found member by email: " . $r['id'] . "\n";
        } else {
            echo "No member found by email: " . $_SESSION['email'] . "\n";
        }
    }
}

// Check what the balance calculation gives directly
$year = ((int)date('n') >= 4) ? (int)date('Y') : (int)date('Y') - 1;
$start = $year."-04-01";
$end   = ($year+1)."-03-31";

$res2 = app_exec_getresult("SELECT IFNULL(SUM(fees),0) AS total FROM tbl_member_recievable WHERE member_id=? AND cancel=0", [$member_id], "i");
$total_recv = $res2 ? (float)$res2->fetch_assoc()['total'] : 0;
echo "Total receivables for member $member_id: $total_recv\n";

$res3 = app_exec_getresult("SELECT IFNULL(SUM(amount),0) AS total FROM tbl_cashbook WHERE member_id=? AND cancel=0", [$member_id], "i");
$total_paid = $res3 ? (float)$res3->fetch_assoc()['total'] : 0;
echo "Total payments for member $member_id: $total_paid\n";
echo "Raw balance (recv - paid) = " . ($total_recv - $total_paid) . "\n";
