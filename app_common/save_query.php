<?php

$login_id = $_SESSION['login_id'] ?? 'guest';

function saveQuerylog($stmt, $parameters = []) {
    global $login_id; // Access the session variable inside the function
    $folder = "../../Query_log";

    if (!is_dir($folder)) {
        mkdir($folder, 0777, true);
    }

    date_default_timezone_set('Asia/Kolkata');

    $current_date = date('Y-m-d');
    $current_time = date('H:i:s');

    $filename = "$folder/" . str_replace('-', '', $current_date) . ".txt";
    $paramString = !empty($parameters) ? ' | Values: ' . json_encode($parameters) : '';
    
    // Prepend login_id
    $entry = "[$current_time] login_id: $login_id | SQL: $stmt$paramString" . PHP_EOL;

    file_put_contents($filename, $entry, FILE_APPEND);
}

function saveErrorlog($error) {
    global $login_id; // Access the session variable inside the function
    $folder = "../../Error_log";

    if (!is_dir($folder)) {
        mkdir($folder, 0777, true);
    }

    date_default_timezone_set('Asia/Kolkata');

    $current_date = date('Y-m-d');
    $current_time = date('H:i:s');

    $filename = "$folder/" . str_replace('-', '', $current_date) . ".txt";
    // $paramString = !empty($parameters) ? ' | Values: ' . json_encode($parameters) : '';

    
    // Prepend login_id
    $entry = "[$current_time] login_id: $login_id | Error: $error" . PHP_EOL;

    file_put_contents($filename, $entry, FILE_APPEND);
}

?>
