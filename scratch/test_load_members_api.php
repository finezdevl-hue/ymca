<?php
chdir(__DIR__ . '/../directory/api');
$_POST['action'] = 'load_members_data';
$_POST['page'] = 1;

ob_start();
require 'members.php';
$out = ob_get_clean();
print_r($out);
?>
