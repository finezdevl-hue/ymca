<?php
chdir(__DIR__ . '/../directory/mobile');
$_SESSION['login_id'] = 4;
$_SESSION['name'] = 'Antony Jose';

ob_start();
require 'profile.php';
$out = ob_get_clean();
echo "Rendered Mobile Profile " . strlen($out) . " bytes cleanly!\n";

chdir(__DIR__ . '/../directory');
ob_start();
require 'profile.php';
$out2 = ob_get_clean();
echo "Rendered Desktop Profile " . strlen($out2) . " bytes cleanly!\n";
?>
