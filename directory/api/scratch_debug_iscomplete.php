<?php
include '../../app_common/db_connect.php';

$ids = [14, 15, 16, 17];
foreach ($ids as $id) {
    echo "=== debug id = $id ===\n";
    $sql_sum = "SELECT pay.amount AS total_payable, SUM(pd.amount) AS total_paid 
                FROM tbl_payable pay 
                LEFT JOIN tbl_paid pd ON pay.id = pd.payable_id 
                WHERE pay.id = ? 
                GROUP BY pay.id";
    $res = app_exec_getresult($sql_sum, [$id], "i");
    if ($res) {
        $row = $res->fetch_assoc();
        print_r($row);
        $diff = $row['total_payable'] - $row['total_paid'];
        echo "Diff: $diff | isComplete: " . ($diff == 0 ? 1 : 0) . "\n\n";
    } else {
        echo "Query returned null or failed.\n\n";
    }
}
?>
