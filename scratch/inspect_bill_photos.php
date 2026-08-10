<?php
require_once __DIR__ . '/../app_common/db_connect.php';

$res = app_exec_query("SELECT id, date, head, particuler, amount, bill_photo FROM tbl_paid ORDER BY id DESC LIMIT 20");
if ($res && $res->num_rows > 0) {
    while ($r = $res->fetch_assoc()) {
        echo "ID: {$r['id']} | Amount: {$r['amount']} | bill_photo: '{$r['bill_photo']}'\n";
        if (!empty($r['bill_photo'])) {
            $p1 = __DIR__ . '/../image_upload/bills/' . $r['bill_photo'];
            $p2 = __DIR__ . '/../pdf_upload/' . $r['bill_photo'];
            echo "   Path 1 (image_upload/bills/): " . (file_exists($p1) ? "EXISTS" : "NOT FOUND") . "\n";
            echo "   Path 2 (pdf_upload/): " . (file_exists($p2) ? "EXISTS" : "NOT FOUND") . "\n";
        }
    }
} else {
    echo "No rows in tbl_paid!\n";
}
?>
