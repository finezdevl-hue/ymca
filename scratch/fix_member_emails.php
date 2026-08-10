<?php
include 'app_common/db_connect.php';

// Link emails in tbl_members from tbl_login
app_exec_query("UPDATE tbl_members SET email = 'saneesh@gmail.com' WHERE id = 7 AND (email IS NULL OR email = '')");
app_exec_query("UPDATE tbl_members SET email = 'jibin309johnson@gmail.com' WHERE id = 41 AND (email IS NULL OR email = '')");
app_exec_query("UPDATE tbl_members SET email = 'stephinjoseph602@gmail.com' WHERE id = 28 AND (email IS NULL OR email = '')");

echo "Successfully updated member emails for logins!\n";
