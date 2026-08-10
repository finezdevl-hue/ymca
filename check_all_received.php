<?php
include 'app_common/db_connect.php';
$member_id = 31;
$sql = "SELECT * FROM tbl_member_recieved WHERE member_id = ?";
$res = app_exec_getresult($sql, [$member_id], "i");
if($res) {
    while($row = $res->fetch_assoc()) {
        print_r($row);
    }
} else {
    echo "Query failed.\n";
}
?>
