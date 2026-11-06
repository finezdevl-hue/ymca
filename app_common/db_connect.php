<?php
/**
 * Backward compatibility bridge for DB execution helpers.
 * Canonical implementation in includes/db.php
 */
require_once __DIR__ . '/../includes/db.php';

try {
    $db = new Database();
    $conn = $db->getConnection();
    $tm = new TransactionManager($conn);
} catch (Throwable $e) {
    error_log("Database connection error in db_connect.php: " . $e->getMessage());
    if (!headers_sent() && PHP_SAPI !== 'cli') {
        http_response_code(500);
        die("<!DOCTYPE html>
        <html lang='en'>
        <head>
            <meta charset='utf-8'>
            <title>YMCA ERP - Database Notice</title>
            <meta name='viewport' content='width=device-width, initial-scale=1.0'>
            <style>
                body { background: #0b1120; color: #f1f5f9; font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif; padding: 40px 20px; }
                .card { background: #1e293b; border: 1px solid #334155; border-radius: 12px; max-width: 620px; margin: 40px auto; padding: 28px; }
                h2 { color: #f87171; margin-top: 0; font-size: 20px; }
                p { font-size: 14px; line-height: 1.6; color: #cbd5e1; }
                code { background: #0f172a; padding: 3px 8px; border-radius: 4px; color: #38bdf8; font-size: 13px; word-break: break-all; }
                .btn { display: inline-block; background: #2563eb; color: #fff; padding: 10px 18px; border-radius: 8px; text-decoration: none; font-size: 14px; font-weight: 600; margin-top: 14px; }
            </style>
        </head>
        <body>
            <div class='card'>
                <h2>Database Connection Failed</h2>
                <p>The system was unable to establish a connection to the MySQL database.</p>
                <p><strong>Server Message:</strong><br><code>" . htmlspecialchars($e->getMessage()) . "</code></p>
                <p>Please check your <code>.env</code> file or verify database credentials and user permissions in cPanel.</p>
                <a href='../index.php' class='btn'>Return to Login</a>
                <a href='../test.php' class='btn' style='background:#334155; margin-left:8px;'>Run Diagnostics</a>
            </div>
        </body>
        </html>");
    }
    throw $e;
}
