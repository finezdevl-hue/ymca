<?php
chdir(__DIR__ . '/../directory/api');
$_POST['action'] = 'save_members';
$_POST['id'] = 0;
$_POST['member_type'] = 1;
$_POST['first_name'] = 'TestGuest';
$_POST['last_name'] = 'Demo';
$_POST['phone'] = '9998887776';

ob_start();
require 'members.php';
$out = ob_get_clean();

require_once __DIR__ . '/../app_common/db_connect.php';
$res = app_exec_query("SELECT id, first_name, last_name, phone, member_type FROM tbl_members WHERE first_name = 'TestGuest' ORDER BY id DESC LIMIT 1");
$row = $res ? $res->fetch_assoc() : null;
print_r($row);

// Clean up test guest
if ($row) {
    app_exec_query("DELETE FROM tbl_members WHERE id = " . (int)$row['id']);
    echo "Test guest deleted cleanly after verification.\n";
}
?>
