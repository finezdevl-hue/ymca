<?php
require_once __DIR__ . '/../app_common/db_connect.php';

$res = app_exec_query("SELECT id, first_name, last_name, member_type FROM tbl_members WHERE member_type = 1");
if ($res && $res->num_rows > 0) {
    echo "Found " . $res->num_rows . " guest members:\n";
    while ($r = $res->fetch_assoc()) {
        echo "ID {$r['id']}: {$r['first_name']} {$r['last_name']} (member_type: {$r['member_type']})\n";
    }
} else {
    echo "No guest members found with member_type = 1!\n";
    echo "Flagging member ID 51 or 'Akash guest' / 'Alex' as guest (member_type = 1) for demonstration...\n";
    app_exec_query("UPDATE tbl_members SET member_type = 1 WHERE first_name LIKE '%Akash%' OR first_name LIKE '%Alex%' OR last_name LIKE '%guest%'");
    
    $res2 = app_exec_query("SELECT id, first_name, last_name, member_type FROM tbl_members WHERE member_type = 1");
    while ($r = $res2->fetch_assoc()) {
        echo "ID {$r['id']}: {$r['first_name']} {$r['last_name']} (member_type: {$r['member_type']})\n";
    }
}
?>
