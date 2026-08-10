<?php
include 'app_common/db_connect.php';

echo "--- TBL_LOGIN VS TBL_MEMBERS MATCHING ---\n";
$res = app_exec_query("SELECT id, first_name, last_name, email FROM tbl_members");
while($m = $res->fetch_assoc()) {
    $email = trim($m['email']);
    $l_res = app_exec_getresult("SELECT login_id, name, email FROM tbl_login WHERE LTRIM(RTRIM(LOWER(email))) = LTRIM(RTRIM(LOWER(?))) OR name LIKE ?", [$email, '%' . $m['first_name'] . '%'], "ss");
    $matched_logins = [];
    while($l = $l_res->fetch_assoc()) {
        $matched_logins[] = "Login ID {$l['login_id']} ({$l['email']})";
    }
    $log_str = count($matched_logins) > 0 ? implode(', ', $matched_logins) : "NO MATCHING LOGIN";
    echo "Member ID {$m['id']}: {$m['first_name']} {$m['last_name']} (Email: '{$m['email']}') => {$log_str}\n";
}
