<?php
require_once __DIR__ . '/../app_common/auth_helper.php';

echo "Regular Member ID 10 => '" . getMemberCode(10, 0) . "'\n";
echo "Regular Member ID 40 => '" . getMemberCode(40, 0) . "'\n";
echo "Guest Member ID 51 => '" . getMemberCode(51, 1) . "' (Should be empty)\n";
?>
