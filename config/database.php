<?php
/**
 * Database Configuration
 * Retrieves connection parameters from .env with secure local defaults.
 */

require_once __DIR__ . '/env.php';

return [
    // 'host'     => app_env('DB_HOST', '127.0.0.1'),
    // 'username' => app_env('DB_USER', 'root'),
    // 'password' => app_env('DB_PASS', ''),
    // 'database' => app_env('DB_NAME', 'ymca_test'),

    'host'     => app_env('DB_HOST', 'localhost'),
    'username' => app_env('DB_USER', 'finezin_dbu_ymca'),
    'password' => app_env('DB_PASS', 'Ymca@2025*!'),
    'database' => app_env('DB_NAME', 'finezin_db_ymca'),

    'port'     => (int)app_env('DB_PORT', 3306),
    'charset'  => 'utf8mb4',
    'collation'=> 'utf8mb4_unicode_ci'
];
