<?php
chdir(__DIR__ . '/../directory/api');
$_POST['action'] = 'load_members_data';
$_POST['page'] = 1;
$_POST['val'] = '';

ob_start();
require 'members.php';
$out = ob_get_clean();
$data = json_decode($out, true);
echo "Total Rows: " . $data[0]['total_rows'] . "\n";
echo "Loaded Count: " . count($data[1]) . "\n";
if (count($data[1]) > 0) {
    echo "First Member: " . $data[1][0]['first_name'] . " " . $data[1][0]['last_name'] . "\n";
}
?>
