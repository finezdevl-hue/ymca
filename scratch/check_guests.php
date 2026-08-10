<?php
require_once __DIR__ . '/../app_common/db_connect.php';
$res = app_exec_query("SELECT id, first_name, last_name, member_type FROM tbl_members WHERE member_type = 1");
print_r($res ? $res->fetch_all(MYSQLI_ASSOC) : []);
?>
