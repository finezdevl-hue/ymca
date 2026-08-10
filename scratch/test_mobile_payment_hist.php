<?php
chdir(__DIR__ . '/../directory/mobile');
$_SESSION['login_id'] = 1;
$_SESSION['name'] = 'Super Admin';

ob_start();
require 'payment_history.php';
$out = ob_get_clean();
echo "Rendered Mobile Payment History " . strlen($out) . " bytes cleanly!\n";
?>
