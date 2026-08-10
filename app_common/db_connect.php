<?php
if (defined('APP_DB_CONNECT_LOADED')) {
    return;
}
define('APP_DB_CONNECT_LOADED', true);

date_default_timezone_set('Asia/Kolkata');

include_once 'database_class.php';
include 'save_query.php';

// ── Auto-migration: ensure required tables and columns exist ──────────────────
// This runs silently on every environment (local & production).
(function () {
    try {
        $db   = new Database();
        $conn = $db->getConnection();

        // 1. Column migrations for existing tables
        $col_migrations = [
            // reset_token column in tbl_login
            "SELECT COLUMN_NAME FROM INFORMATION_SCHEMA.COLUMNS
             WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'tbl_login' AND COLUMN_NAME = 'reset_token'" =>
            "ALTER TABLE tbl_login ADD COLUMN reset_token VARCHAR(100) NOT NULL DEFAULT ''",

            // reset_token_expires column in tbl_login
            "SELECT COLUMN_NAME FROM INFORMATION_SCHEMA.COLUMNS
             WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'tbl_login' AND COLUMN_NAME = 'reset_token_expires'" =>
            "ALTER TABLE tbl_login ADD COLUMN reset_token_expires DATETIME NOT NULL DEFAULT '2000-01-01 00:00:00'",

            // group_id in tbl_payable
            "SELECT COLUMN_NAME FROM INFORMATION_SCHEMA.COLUMNS
             WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'tbl_payable' AND COLUMN_NAME = 'group_id'" =>
            "ALTER TABLE tbl_payable ADD COLUMN group_id INT NOT NULL DEFAULT 0 AFTER head",

            // group_id in tbl_paid
            "SELECT COLUMN_NAME FROM INFORMATION_SCHEMA.COLUMNS
             WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'tbl_paid' AND COLUMN_NAME = 'group_id'" =>
            "ALTER TABLE tbl_paid ADD COLUMN group_id INT NOT NULL DEFAULT 0 AFTER head",

            // group_id in tbl_cashbook
            "SELECT COLUMN_NAME FROM INFORMATION_SCHEMA.COLUMNS
             WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'tbl_cashbook' AND COLUMN_NAME = 'group_id'" =>
            "ALTER TABLE tbl_cashbook ADD COLUMN group_id INT NOT NULL DEFAULT 0 AFTER id",
        ];

        foreach ($col_migrations as $check => $alter) {
            $res = $conn->query($check);
            if ($res && $res->num_rows === 0) {
                $conn->query($alter);
            }
        }

        // 2. Table migrations for RBAC
        $table_migrations = [
            "CREATE TABLE IF NOT EXISTS tbl_roles (
                role_id INT PRIMARY KEY AUTO_INCREMENT,
                role_name VARCHAR(50) NOT NULL UNIQUE,
                description VARCHAR(255) DEFAULT NULL,
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4",

            "CREATE TABLE IF NOT EXISTS tbl_user_group_roles (
                id INT PRIMARY KEY AUTO_INCREMENT,
                login_id INT NOT NULL,
                group_id INT NOT NULL DEFAULT 0,
                role_id INT NOT NULL DEFAULT 4,
                assigned_by INT DEFAULT NULL,
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                UNIQUE KEY idx_user_group_role (login_id, group_id, role_id)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4",

            "CREATE TABLE IF NOT EXISTS tbl_temp_attendance_status (
                id INT PRIMARY KEY AUTO_INCREMENT,
                login_id INT NOT NULL,
                member_id INT NOT NULL DEFAULT 0,
                group_id INT NOT NULL DEFAULT 0,
                status VARCHAR(20) NOT NULL,
                expected_time VARCHAR(20) DEFAULT NULL,
                status_date DATE NOT NULL,
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                UNIQUE KEY idx_user_date_group (login_id, status_date, group_id)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4",

            "ALTER TABLE tbl_groups ADD COLUMN IF NOT EXISTS allow_tomorrow_attendance TINYINT(1) DEFAULT 0",
        ];

        foreach ($table_migrations as $sql) {
            $conn->query($sql);
        }

        // 3. Seed default roles
        $conn->query("INSERT IGNORE INTO tbl_roles (role_id, role_name, description) VALUES
            (1, 'Super Admin', 'Full system access across all groups and settings'),
            (2, 'Group Admin', 'Manages income, expenses, members, and attendance for assigned groups'),
            (3, 'Attendance Master', 'Delegated member who takes/manages attendance for assigned groups'),
            (4, 'Member', 'General member with self-service dashboard and payment access'),
            (5, 'Executive Member', 'Executive member with access to view all club reports and analytics')");

        // 4. Ensure Super Admin role exists for login_id = 1
        $conn->query("INSERT IGNORE INTO tbl_user_group_roles (login_id, group_id, role_id) VALUES (1, 0, 1)");

        // 5. Seed Role & Group Manager menu item (ID 53) under Account Settings (ID 51)
        $conn->query("INSERT IGNORE INTO tbl_menu (id, name, nav_url, parent_id, menu_level, sub_menu, icon) VALUES (53, 'Role & Group Manager', 'role_management.php', 51, 1, 0, 'fa fa-shield')");
        $conn->query("INSERT IGNORE INTO tbl_menu_map (login_id, menu_id, is_active) VALUES (1, 53, 1)");

        $db->closeConnection();
    } catch (Throwable $e) {
        // Silently ignore — do not break the app if migration fails
    }
})();
// ─────────────────────────────────────────────────────────────────────────────


function app_exec_query($sql) {
    try {
        $db = new Database();
        $conn = $db->getConnection();
        $result = $conn->query($sql);
        $db->closeConnection();
        return $result;
    } catch (Exception $e) {
        http_response_code(500);
    }
}

function app_exec_nonquery(string $sql, array $parameters, string $types) {
    try {
        $db = new Database();
        $conn = $db->getConnection();
        $stmt = $conn->prepare($sql);
        
        if (!$stmt) {
            
            throw new Exception("Prepare failed: " . $conn->error);
            echo "Error preparing statement: " . $conn->error;
            return;
        }

        $stmt->bind_param($types, ...$parameters);

        if ($stmt->execute()) {
            saveQuerylog($sql, $parameters);  // Make sure this function exists
            // echo "Success";
        } else {
            
            echo "Error: " . $stmt->error;
        }

        $stmt->close();
        $db->closeConnection();
    } catch (Exception $e) {
        saveErrorlog($e->getMessage());
        http_response_code(500);
        // echo $e->getMessage();
        header('Content-Type: application/json');
        echo json_encode(array('Message' => $e->getMessage()));
        exit;
    }
}

function app_exec_getresult(string $sql, array $parameters, string $types): ?mysqli_result {
    try {
        $db = new Database();
        $conn = $db->getConnection();
        $stmt = $conn->prepare($sql);

        if (!$stmt) {
            return null;
        }

        $stmt->bind_param($types, ...$parameters);
        $stmt->execute();
        $result = $stmt->get_result();
        $stmt->close();
        $db->closeConnection();
        return $result ?: null;
    } catch (Exception $e) {
        http_response_code(500);
        return null;
    }
}


function getresult(string $sql, array $values, ?string $types = null): ?mysqli_result {
    try {
        $db = new Database();
        $conn = $db->getConnection();
        $stmt = $conn->prepare($sql);

        if (!$stmt) {
            return null;
        }

        if ($types !== null) {
            $stmt->bind_param($types, ...$values);
        }

        $stmt->execute();
        $result = $stmt->get_result();
        $stmt->close();
        $db->closeConnection();
        return $result ?: null;
    } catch (Exception $e) {
        http_response_code(500);
        return null;
    }
}

function app_exec_roll_back_nonquery(mysqli $conn, string $sql, array $parameters, string $types): bool {
    try {
        saveQuerylog($sql, $parameters);
        $stmt = $conn->prepare($sql);
        if (!$stmt) {
            throw new Exception("Prepare failed: " . $conn->error);
        }

        $stmt->bind_param($types, ...$parameters);
        if (!$stmt->execute()) {
            
            throw new Exception("Execute failed: " . $stmt->error);
        }

        $stmt->close();
        return true;
    } catch (Exception $e) {
        http_response_code(500);
        // echo json_encode(['success' => false, 'message' => $e->getMessage()]);
        // echo $e->getMessage();
        saveErrorlog($e->getMessage());
        header('Content-Type: application/json');
        echo json_encode(array('Message' => $e->getMessage()));
        exit;
    }
}

function rollback_app_exec_query(mysqli $conn, string $sql): bool {
    try {
        return $conn->query($sql) !== false;
    } catch (Exception $e) {
        return false;
    }
}
class TransactionManager {
    private $conn;

    public function __construct(mysqli $conn) {
        $this->conn = $conn;
    }

    public function begin(): void {
        $this->conn->begin_transaction();
    }

    public function commit(): void {
        $this->conn->commit();
    }

    public function rollback(): void {
        $this->conn->rollback();
    }
}
// creating class for rollback ends

function app_exec_getlast_id(string $sql, array $parameters, string $types): ?int {
    try {
        $db = new Database();
        $conn = $db->getConnection();
        $stmt = $conn->prepare($sql);

        if (!$stmt) {
            return null;
        }

        $stmt->bind_param($types, ...$parameters);
        saveQuerylog($sql, $parameters); // Make sure this function is defined

        if ($stmt->execute()) {
            $lastId = $conn->insert_id;
            $stmt->close();
            $db->closeConnection();
            return $lastId;
        } else {
            $stmt->close();
            $db->closeConnection();
            return null;
        }
    } catch (Exception $e) {
        http_response_code(500);
        return null;
    }
}

function app_exec_getlast_id_roll_back(mysqli $conn,string $sql, array $parameters, string $types): ?int {
    try {
        $db = new Database();
        //$conn = $db->getConnection();
        $stmt = $conn->prepare($sql);

        if (!$stmt) {
            return null;
        }

        $stmt->bind_param($types, ...$parameters);
        saveQuerylog($sql, $parameters); // Make sure this function is defined

        if ($stmt->execute()) {
            $lastId = $conn->insert_id;
            $stmt->close();
            $db->closeConnection();
            return $lastId;
        } else {
            $stmt->close();
            $db->closeConnection();
            return null;
        }
    } catch (Exception $e) {
        http_response_code(500);
        return null;
    }
}

?>
