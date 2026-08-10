<?php
// Test the exact AJAX call that home.php makes
// Run: http://localhost/ymca_new/scratch/debug_ajax.php
session_start();
header('Content-Type: text/plain');

if (empty($_SESSION['login_id'])) {
    echo "NOT LOGGED IN\n";
    exit;
}

echo "Logged in as: " . ($_SESSION['name'] ?? '?') . " (login_id=" . $_SESSION['login_id'] . ", user_id=" . ($_SESSION['user_id'] ?? 'NULL') . ")\n\n";

// Simulate what the AJAX call does - use curl internally
$year = ((int)date('n') >= 4) ? (int)date('Y') : (int)date('Y') - 1;
$member_id = (int)($_SESSION['user_id'] ?? 0);

echo "Posting to: http://localhost/ymca_new/directory/api/member_cashbook_report.php\n";
echo "member_id=$member_id, year=$year\n\n";

// Use file_get_contents with POST
$postdata = http_build_query([
    'action' => 'get_member_cashbook',
    'member_id' => $member_id,
    'year' => $year
]);

// Get current session cookie
$session_name = session_name();
$session_id = session_id();
session_write_close();

$context = stream_context_create([
    'http' => [
        'method' => 'POST',
        'header' => "Content-Type: application/x-www-form-urlencoded\r\n" .
                    "Cookie: {$session_name}={$session_id}\r\n",
        'content' => $postdata,
        'timeout' => 10
    ]
]);

$response = @file_get_contents('http://localhost/ymca_new/directory/api/member_cashbook_report.php', false, $context);

echo "HTTP Response Code: " . ($http_response_header[0] ?? 'unknown') . "\n\n";
echo "Raw Response:\n";
echo $response . "\n\n";

$d = json_decode($response, true);
echo "closing_balance: " . ($d['summary']['closing_balance'] ?? 'NOT FOUND') . "\n";
echo "payment_settings: " . json_encode($d['summary']['payment_settings'] ?? null) . "\n";
