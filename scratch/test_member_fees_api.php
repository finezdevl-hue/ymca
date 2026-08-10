<?php
chdir(__DIR__ . '/../directory/api');
$_POST['action'] = 'load_members_data';
$_POST['page'] = 1;
$_POST['val'] = '';

ob_start();
require 'member_fees.php';
$out = ob_get_clean();
$data = json_decode($out, true);
if (!empty($data[1])) {
    echo "Sample member row:\n";
    print_r($data[1][0]);
}
?>
