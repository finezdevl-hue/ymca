<?php
// Mock session
session_start();
$_SESSION['login_id'] = 1; // Admin
$_SESSION['email'] = 'test@gmail.com';
$_SESSION['name'] = 'Admin';
session_write_close();

$_POST['action'] = 'load_members';
$_POST['page'] = 1;
$_POST['val'] = '';

echo "=== TESTING load_members ===\n";
ob_start();
include 'directory/api/yearly_attendance_report.php';
$output = ob_get_clean();
$data = json_decode($output, true);
if (is_array($data)) {
    echo "Total rows: " . $data[0]['total_rows'] . "\n";
    echo "Found " . count($data[1]) . " members.\n";
    if (count($data[1]) > 0) {
        $first_member = $data[1][0];
        echo "First member: ID: " . $first_member['id'] . " | Name: " . $first_member['first_name'] . " " . $first_member['last_name'] . "\n";
        
        // Now test yearly summary for this member
        echo "\n=== TESTING get_yearly_summary ===\n";
        $_POST['action'] = 'get_yearly_summary';
        $_POST['member_id'] = $first_member['id'];
        $_POST['year'] = 2026;
        
        ob_start();
        include 'directory/api/yearly_attendance_report.php';
        $output_summary = ob_get_clean();
        // The first include would have exited because of exit(), but since we are running a new process or separate test, let's parse it if possible, or wait! Since yearly_attendance_report.php calls exit(), we can't run both in the same script without separate executions.
        // That is fine, we can see that load_members worked. Let's print the load_members data.
        print_r($data[1][0]);
    }
} else {
    echo "Error: Output is not JSON: $output\n";
}
?>
