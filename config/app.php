<?php
/**
 * Application Configuration
 * Global settings, timezone, app secrets, and runtime parameters.
 */

require_once __DIR__ . '/env.php';

// Set application default timezone
$appTimezone = app_env('APP_TIMEZONE', 'Asia/Kolkata');
date_default_timezone_set($appTimezone);

// Database & Application Navigation
$app_url = app_env('APP_URL', 'http://localhost/ymca_test');

// Club Profile & Branding Configuration
if (!defined('CLUB_NAME')) {
    define('CLUB_NAME', app_env('CLUB_NAME', 'ClubPulse Sports Management'));
}
if (!defined('CLUB_SHORT_NAME')) {
    define('CLUB_SHORT_NAME', app_env('CLUB_SHORT_NAME', 'ClubPulse'));
}
if (!defined('CLUB_MEMBER_PREFIX')) {
    define('CLUB_MEMBER_PREFIX', app_env('CLUB_MEMBER_PREFIX', 'CLUB-'));
}
if (!defined('DEFAULT_UPI_ID')) {
    define('DEFAULT_UPI_ID', app_env('DEFAULT_UPI_ID', 'clubpay@okaxis'));
}
if (!defined('DEFAULT_PAYEE_NAME')) {
    define('DEFAULT_PAYEE_NAME', app_env('DEFAULT_PAYEE_NAME', 'Club Management'));
}
if (!defined('APP_SECRET_KEY')) {
    $secret = app_env('APP_SECRET_KEY', '0d6f4c7d21626c8153da27c9c3b5d251887470e26f74b46c6dc8756449dbce2c');
    define('APP_SECRET_KEY', $secret);
}

// SMTP Mail Configuration
if (!defined('SMTP_HOST')) {
    define('SMTP_HOST', app_env('SMTP_HOST', 'smtp.gmail.com'));
}
if (!defined('SMTP_PORT')) {
    define('SMTP_PORT', (int)app_env('SMTP_PORT', 465));
}
if (!defined('SMTP_SECURE')) {
    define('SMTP_SECURE', app_env('SMTP_SECURE', 'ssl')); // 'ssl' or 'tls'
}
if (!defined('SMTP_USERNAME')) {
    define('SMTP_USERNAME', app_env('SMTP_USERNAME', ''));
}
if (!defined('SMTP_PASSWORD')) {
    define('SMTP_PASSWORD', app_env('SMTP_PASSWORD', ''));
}
if (!defined('SMTP_FROM_EMAIL')) {
    define('SMTP_FROM_EMAIL', app_env('SMTP_FROM_EMAIL', ''));
}
if (!defined('SMTP_FROM_NAME')) {
    define('SMTP_FROM_NAME', app_env('SMTP_FROM_NAME', 'YMCA BCP'));
}
if (!defined('MAIL_ENABLED')) {
    define('MAIL_ENABLED', filter_var(app_env('MAIL_ENABLED', true), FILTER_VALIDATE_BOOLEAN));
}

return [
    'name'        => app_env('APP_NAME', 'YMCA Badminton Club Management System'),
    'env'         => app_env('APP_ENV', 'production'),
    'debug'       => filter_var(app_env('APP_DEBUG', false), FILTER_VALIDATE_BOOLEAN),
    'url'         => $app_url,
    'timezone'    => $appTimezone,
    'secret_key'  => APP_SECRET_KEY,
    'session'     => [
        'lifetime' => (int)app_env('SESSION_LIFETIME', 86400),
        'secure'   => isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on',
        'httponly' => true,
        'samesite' => 'Lax'
    ],
    'pagination'  => [
        'default_rows_per_page' => 8
    ]
];
