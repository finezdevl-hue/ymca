<?php
class Database {
    private $servername = "127.0.0.1";
    private $username = "root";
    private $password = "";
    private $dbname = "ymca_new";

        // private $servername = "localhost";
        // private $username = "finezin_dbu_ymca";
        // private $password = "Ymca@2025*!";
        // private $dbname = "finezin_db_ymca";


    private static ?mysqli $connection = null;

    public function __construct() {
        if (self::$connection instanceof mysqli) {
            return;
        }

        mysqli_report(MYSQLI_REPORT_OFF);

        $connection = mysqli_init();
        if ($connection === false) {
            throw new RuntimeException('Failed to initialize MySQL connection.');
        }

        $connection->options(MYSQLI_OPT_CONNECT_TIMEOUT, 5);
        $connected = @$connection->real_connect(
            $this->servername,
            $this->username,
            $this->password,
            $this->dbname
        );

        if (!$connected) {
            throw new RuntimeException('Connection failed: ' . $connection->connect_error);
        }

        $connection->set_charset('utf8mb4');
        @$connection->query("SET time_zone = '+05:30'");
        self::$connection = $connection;
    }

    public function getConnection() {
        return self::$connection;
    }

    public function closeConnection() {
        // Intentionally keep the shared request-level connection open.
        // PHP closes it automatically when the request ends.
    }
}
?>
