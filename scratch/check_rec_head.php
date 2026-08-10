<?php
require_once __DIR__ . '/../app_common/db_connect.php';
$res = app_exec_query("SELECT id, member_id, date, fees, head, discription FROM tbl_member_recievable WHERE head = 1");
print_r($res ? $res->fetch_all(MYSQLI_ASSOC) : []);
?>
