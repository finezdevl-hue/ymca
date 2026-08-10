<?php
require_once __DIR__ . '/../app_common/db_connect.php';

$res = app_exec_query("SELECT id, date, amount, bill_photo FROM tbl_paid WHERE bill_photo IS NOT NULL AND bill_photo != '' LIMIT 10");
if ($res && $res->num_rows > 0) {
    while ($r = $res->fetch_assoc()) {
        echo "ID: {$r['id']} | bill_photo: '{$r['bill_photo']}'\n";
        $p_payments = __DIR__ . '/../image_upload/payments/' . $r['bill_photo'];
        $p_bills    = __DIR__ . '/../image_upload/bills/' . $r['bill_photo'];
        echo "   image_upload/payments/ : " . (file_exists($p_payments) ? "EXISTS" : "NOT FOUND") . "\n";
        echo "   image_upload/bills/    : " . (file_exists($p_bills) ? "EXISTS" : "NOT FOUND") . "\n";
    }
}
?>
