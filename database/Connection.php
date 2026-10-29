<?php
/**
 * Database Connection Manager
 * Manages singleton MySQLi connection configured via config/database.php.
 */

require_once __DIR__ . '/../config/database.php';

if (!class_exists('Database')) {
    class Database {
        private static ?mysqli $connection = null;

        public function __construct() {
            if (self::$connection instanceof mysqli && @self::$connection->ping()) {
                return;
            }

            $config = require __DIR__ . '/../config/database.php';

            mysqli_report(MYSQLI_REPORT_OFF);

            $connection = mysqli_init();
            if ($connection === false) {
                throw new RuntimeException('Failed to initialize MySQL connection.');
            }

            $connection->options(MYSQLI_OPT_CONNECT_TIMEOUT, 5);
            $connected = @$connection->real_connect(
                $config['host'],
                $config['username'],
                $config['password'],
                $config['database'],
                $config['port']
            );

            if (!$connected) {
                throw new RuntimeException('Database connection failed: ' . $connection->connect_error);
            }

            $charset = $config['charset'] ?? 'utf8mb4';
            $connection->set_charset($charset);
            @$connection->query("SET time_zone = '+05:30'");
            
            self::$connection = $connection;
        }

        public function getConnection(): mysqli {
            return self::$connection;
        }

        public function closeConnection(): void {
            // Reusable request-level connection kept open; closed automatically on shutdown.
        }
    }
}
