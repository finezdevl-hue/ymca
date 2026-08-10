<?php
require_once __DIR__ . '/../app_common/db_connect.php';

echo "--- 1. Checking tbl_members columns ---\n";
$res1 = app_exec_query("SHOW COLUMNS FROM tbl_members LIKE 'member_type'");
if ($res1 && $res1->num_rows > 0) {
    echo "member_type column EXISTS in tbl_members.\n";
} else {
    echo "member_type column MISSING in tbl_members! Adding it now...\n";
    app_exec_query("ALTER TABLE tbl_members ADD COLUMN member_type tinyint(1) NOT NULL DEFAULT 0");
    echo "member_type column added successfully.\n";
}

echo "\n--- 2. Checking Guest Fee head in tbl_payment_head_master ---\n";
$res2 = app_exec_query("SELECT id, name FROM tbl_payment_head_master WHERE id = 12 OR name LIKE '%guest%'");
if ($res2 && $res2->num_rows > 0) {
    while ($r = $res2->fetch_assoc()) {
        echo "Head ID {$r['id']}: {$r['name']}\n";
    }
} else {
    echo "Guest Fee head missing! Adding head ID 12...\n";
    app_exec_query("INSERT INTO tbl_payment_head_master (id, name, type) VALUES (12, 'Guest Fee', 1) ON DUPLICATE KEY UPDATE name='Guest Fee'");
    echo "Guest Fee head ID 12 added.\n";
}

echo "\n--- 3. Total Members Count ---\n";
$res3 = app_exec_query("SELECT COUNT(*) AS total FROM tbl_members");
$total = $res3 ? $res3->fetch_assoc()['total'] : 0;
echo "Total members in database: $total\n";
?>
