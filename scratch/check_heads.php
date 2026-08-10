<?php
require_once __DIR__ . '/../app_common/db_connect.php';

echo "=== tbl_fees_head_master ===\n";
$r1 = app_exec_query("SELECT id, name FROM tbl_fees_head_master");
print_r($r1 ? $r1->fetch_all(MYSQLI_ASSOC) : []);

echo "\n=== tbl_payment_head_master ===\n";
$r2 = app_exec_query("SELECT id, name FROM tbl_payment_head_master");
print_r($r2 ? $r2->fetch_all(MYSQLI_ASSOC) : []);
?>
