<?php
session_start();
include_once __DIR__ . '/../../app_common/db_connect.php';
include_once __DIR__ . '/../../app_common/auth_helper.php';

if (empty($_SESSION['login_id'])) {
    http_response_code(401);
    echo "Unauthorized access.";
    exit();
}

$login_id = (int)$_SESSION['login_id'];
if (!isSuperAdmin($login_id)) {
    http_response_code(403);
    echo "Access denied. Only Super Admin can download database backups.";
    exit();
}

try {
    $db = new Database();
    $conn = $db->getConnection();

    $tables = [];
    $result = $conn->query("SHOW TABLES");
    while ($row = $result->fetch_array(MYSQLI_NUM)) {
        $tables[] = $row[0];
    }

    $dump  = "-- YMCA Management System Database Backup\n";
    $dump .= "-- Generated on: " . date('Y-m-d H:i:s') . "\n";
    $dump .= "-- Host: " . $conn->host_info . "\n\n";
    $dump .= "SET FOREIGN_KEY_CHECKS = 0;\n";
    $dump .= "SET SQL_MODE = 'NO_AUTO_VALUE_ON_ZERO';\n";
    $dump .= "SET time_zone = '+05:30';\n\n";

    foreach ($tables as $table) {
        // Table Structure
        $resCreate = $conn->query("SHOW CREATE TABLE `" . $conn->real_escape_string($table) . "`");
        if ($resCreate && $rowCreate = $resCreate->fetch_array(MYSQLI_NUM)) {
            $dump .= "-- --------------------------------------------------------\n";
            $dump .= "-- Table structure for `" . $table . "`\n";
            $dump .= "-- --------------------------------------------------------\n\n";
            $dump .= "DROP TABLE IF EXISTS `" . $table . "`;\n";
            $dump .= $rowCreate[1] . ";\n\n";
        }

        // Table Data
        $resData = $conn->query("SELECT * FROM `" . $conn->real_escape_string($table) . "`");
        if ($resData && $resData->num_rows > 0) {
            $dump .= "-- Dumping data for `" . $table . "`\n\n";
            $numFields = $resData->field_count;

            while ($row = $resData->fetch_array(MYSQLI_NUM)) {
                $dump .= "INSERT INTO `" . $table . "` VALUES(";
                for ($j = 0; $j < $numFields; $j++) {
                    if (is_null($row[$j])) {
                        $dump .= "NULL";
                    } elseif ($row[$j] !== "") {
                        $dump .= "'" . $conn->real_escape_string($row[$j]) . "'";
                    } else {
                        $dump .= "''";
                    }
                    if ($j < ($numFields - 1)) {
                        $dump .= ", ";
                    }
                }
                $dump .= ");\n";
            }
            $dump .= "\n";
        }
    }

    $dump .= "SET FOREIGN_KEY_CHECKS = 1;\n";

    $filename = "ymca_db_backup_" . date('Y-m-d_H-i-s') . ".sql";

    // Set headers to trigger file download
    header('Content-Description: File Transfer');
    header('Content-Type: application/sql');
    header('Content-Disposition: attachment; filename="' . $filename . '"');
    header('Content-Transfer-Encoding: binary');
    header('Expires: 0');
    header('Cache-Control: must-revalidate');
    header('Pragma: public');
    header('Content-Length: ' . strlen($dump));

    echo $dump;
    exit();

} catch (Throwable $e) {
    http_response_code(500);
    echo "Backup generation failed: " . $e->getMessage();
    exit();
}
