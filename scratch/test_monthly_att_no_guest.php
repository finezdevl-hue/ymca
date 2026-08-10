<?php
chdir(__DIR__ . '/../directory/api');
$_POST['action'] = 'load_data';
$_POST['page'] = 1;

ob_start();
require 'monthly_attendance.php';
$out = ob_get_clean();
$data = json_decode($out, true);
echo "Monthly attendance rows returned: " . (empty($data[1]) ? 0 : count($data[1])) . "\n";
?>
