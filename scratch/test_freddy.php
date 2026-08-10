<?php
session_start();
$_SESSION['login_id'] = 10;
$_SESSION['user_id'] = 22; // Freddy Joseph
$_POST['action'] = 'get_member_cashbook';
$_POST['member_id'] = 22;
$_POST['year'] = 2025;

ob_start();
include 'directory/api/member_cashbook_report.php';
$out = ob_get_clean();
$data = json_decode($out, true);

echo "=== FREDDY JOSEPH (FY 2025) ===\n";
foreach (($data['transactions'] ?? []) as $t) {
    echo "  " . $t['date'] . " | " . sprintf("%-10s", $t['type']) . " | " . sprintf("%-30s", $t['particulars']) . " | Deb: " . sprintf("%6.2f", $t['debit']) . " | Cred: " . sprintf("%6.2f", $t['credit']) . "\n";
}
