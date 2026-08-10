<?php
session_start();
$_SESSION['login_id'] = 3;
$_SESSION['user_id'] = 22;
$_POST['action'] = 'get_member_cashbook';
$_POST['member_id'] = 22;
$year = isset($argv[1]) ? (int)$argv[1] : 2025;
$_POST['year'] = $year;

ob_start();
include 'member_cashbook_report.php';
$out = ob_get_clean();
$data = json_decode($out, true);

echo "=== YEAR $year ===\n";
echo "Summary: " . json_encode($data['summary'] ?? null) . "\n";
echo "Transactions count: " . count($data['transactions'] ?? []) . "\n";
foreach (($data['transactions'] ?? []) as $t) {
    echo "  " . $t['date'] . " | " . sprintf("%-10s", $t['type']) . " | " . sprintf("%-30s", $t['particulars']) . " | Deb: " . sprintf("%6.2f", $t['debit']) . " | Cred: " . sprintf("%6.2f", $t['credit']) . "\n";
}
