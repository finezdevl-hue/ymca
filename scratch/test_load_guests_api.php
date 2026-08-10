<?php
chdir(__DIR__ . '/../directory/api');
$_POST['action'] = 'load_members_data';
$_POST['member_type'] = 1;
$_POST['page'] = 1;

ob_start();
require 'members.php';
$out = ob_get_clean();
print_r($out);
?>
