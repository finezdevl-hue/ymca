<?php
include 'app_common/db_connect.php';

echo "=== Tables containing 'email' column ===\n";
$sql = "SELECT TABLE_NAME, COLUMN_NAME FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND COLUMN_NAME = 'email'";
$result = app_exec_query($sql);
if ($result) {
    while ($row = $result->fetch_assoc()) {
        print_r($row);
    }
}
?>
