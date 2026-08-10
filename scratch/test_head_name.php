<?php
chdir(__DIR__ . '/../directory/api');
$_POST['action'] = 'load_data';
$_POST['page'] = 1;
$_SESSION['member_id'] = 40;
$_SESSION['login_id'] = 1;

ob_start();
require 'fees_receiveble.php';
$out = ob_get_clean();
$data = json_decode($out, true);
print_r($data);
?>
