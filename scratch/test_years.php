<?php
session_start();
$_SESSION['login_id'] = 10;
$_SESSION['user_id'] = 1;
include_once '../../app_common/db_connect.php';
$_POST['action'] = 'get_member_cashbook';
$_POST['member_id'] = 1;

foreach ([2024, 2025, 2026] as $year) {
    $_POST['year'] = $year;
    ob_start();
    include 'member_cashbook_report.php';
    $out = ob_get_clean();
    $data = json_decode($out, true);
    echo "=== YEAR $year ===\n";
    echo "Summary: " . json_encode($data['summary'] ?? null) . "\n";
    echo "Transactions count: " . count($data['transactions'] ?? []) . "\n";
    foreach (($data['transactions'] ?? []) as $t) {
        echo "  " . $t['date'] . " | " . $t['type'] . " | " . $t['particulars'] . " | Deb: " . $t['debit'] . " | Cred: " . $t['credit'] . "\n";
    }
    echo "\n";
}
