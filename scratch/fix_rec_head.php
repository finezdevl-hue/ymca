<?php
require_once __DIR__ . '/../app_common/db_connect.php';
app_exec_query("UPDATE tbl_member_recievable SET head = 12 WHERE head = 1 AND discription LIKE '%Guest%'");
echo "Updated guest fee records from head=1 to head=12 (Guest Fee).\n";
?>
