<?php
chdir(__DIR__ . '/../directory');
$_SESSION['login_id'] = 1;
$_SESSION['name'] = 'Super Admin';

ob_start();
require 'guests.php';
$out = ob_get_clean();
echo "Rendered Desktop Guests Page " . strlen($out) . " bytes cleanly without errors!\n";
?>
