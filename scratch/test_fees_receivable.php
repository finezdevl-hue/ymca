<?php
chdir(__DIR__ . '/../directory');
$_SESSION['login_id'] = 1;
$_SESSION['name'] = 'Admin';

ob_start();
require 'fees_receiveble.php';
$out = ob_get_clean();
echo "Rendered " . strlen($out) . " bytes successfully without any errors!\n";
?>
