<?php
session_start();
$_SESSION['login_id'] = 10;
$_SESSION['user_id'] = 1;
$_SESSION['email'] = 'simonthomasadvocate@gmail.com';

$_POST['action'] = 'get_member_cashbook';
$_POST['member_id'] = 1;
$_POST['year'] = 2026;

chdir(__DIR__ . '/../directory/api');

ob_start();
include 'member_cashbook_report.php';
$output = ob_get_clean();

echo "API RESPONSE:\n";
echo $output . "\n";
