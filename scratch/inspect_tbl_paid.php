<?php
require_once __DIR__ . '/../app_common/db_connect.php';
$res = app_exec_query("SHOW COLUMNS FROM tbl_paid");
print_r($res ? $res->fetch_all(MYSQLI_ASSOC) : []);
?>
