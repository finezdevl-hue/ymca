<?php
include 'app_common/db_connect.php';
$member_id = 31;
$sql = "SELECT COUNT(*) as cnt FROM tbl_member_recieved WHERE member_id = ?";
$res = app_exec_getresult($sql, [$member_id], "i");
if($res && $row = $res->fetch_assoc()) {
    echo "Received rows count for member $member_id: " . $row['cnt'] . "\n";
} else {
    echo "Query failed or no result.\n";
}
?>
