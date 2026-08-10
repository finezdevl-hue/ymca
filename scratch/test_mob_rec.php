<?php
chdir(__DIR__ . '/../directory/mobile');
$_SESSION['login_id'] = 1;
$_GET['member_id'] = 40;

ob_start();
require 'member_receivable.php';
$out = ob_get_clean();
echo "Rendered mobile ledger " . strlen($out) . " bytes cleanly without warnings!\n";
?>
