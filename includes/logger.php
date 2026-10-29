<?php
/**
 * Application Query and Error Logger
 * Writes sanitized logs to logs/ directory with strict file permissions.
 */

$login_id = $_SESSION['login_id'] ?? 'guest';

if (!function_exists('saveQuerylog')) {
    function saveQuerylog($stmt, $parameters = []) {
        // Skip routine query logging unless explicitly enabled to avoid disk I/O bottlenecks
        if (!defined('APP_ENABLE_QUERY_LOG') || !APP_ENABLE_QUERY_LOG) {
            return;
        }

        // Never log sensitive queries containing auth credentials, passwords, or tokens
        if (preg_match('/(tbl_login|password|reset_token|tbl_auth_tokens|hashed_validator|tbl_login_attempts)/i', $stmt)) {
            $paramString = ' | Values: [REDACTED_AUTH_QUERY]';
        } else {
            $paramString = !empty($parameters) ? ' | Values: ' . json_encode($parameters) : '';
        }

        $current_login_id = $_SESSION['login_id'] ?? 'guest';
        $folder = __DIR__ . "/../logs/queries";

        if (!is_dir($folder)) {
            @mkdir($folder, 0755, true);
        }

        date_default_timezone_set('Asia/Kolkata');

        $current_date = date('Y-m-d');
        $current_time = date('H:i:s');

        $filename = "$folder/" . str_replace('-', '', $current_date) . ".txt";
        
        $entry = "[$current_time] login_id: $current_login_id | SQL: $stmt$paramString" . PHP_EOL;

        @file_put_contents($filename, $entry, FILE_APPEND);
    }
}

if (!function_exists('saveErrorlog')) {
    function saveErrorlog($error) {
        $current_login_id = $_SESSION['login_id'] ?? 'guest';
        $folder = __DIR__ . "/../logs/errors";

        if (!is_dir($folder)) {
            @mkdir($folder, 0755, true);
        }

        date_default_timezone_set('Asia/Kolkata');

        $current_date = date('Y-m-d');
        $current_time = date('H:i:s');

        $filename = "$folder/" . str_replace('-', '', $current_date) . ".txt";
        
        $entry = "[$current_time] login_id: $current_login_id | Error: $error" . PHP_EOL;

        @file_put_contents($filename, $entry, FILE_APPEND);
    }
}
