<?php
/**
 * Database Migration Script
 * Run from CLI: php db/migrate.php
 */

require_once __DIR__ . '/Connection.php';

try {
    $db = new Database();
    $conn = $db->getConnection();
    echo "Running migrations...\n";

    // 1. Column updates for tbl_login (expand password, allow NULL on reset tokens)
    $login_mods = [
        "ALTER TABLE tbl_login MODIFY COLUMN password VARCHAR(255) NOT NULL",
        "ALTER TABLE tbl_login MODIFY COLUMN reset_token VARCHAR(100) NULL DEFAULT NULL",
        "ALTER TABLE tbl_login MODIFY COLUMN reset_token_expires DATETIME NULL DEFAULT NULL"
    ];
    foreach ($login_mods as $sql) {
        if (!$conn->query($sql)) {
            echo "Notice on login mod: " . $conn->error . "\n";
        }
    }
    echo "tbl_login columns updated successfully.\n";

    // Upgrade existing plaintext passwords to secure bcrypt hashes
    $res = $conn->query("SELECT login_id, password FROM tbl_login");
    $upgraded = 0;
    if ($res) {
        $stmt_up = $conn->prepare("UPDATE tbl_login SET password = ? WHERE login_id = ?");
        while ($row = $res->fetch_assoc()) {
            $info = password_get_info($row['password']);
            if (!$info['algo']) {
                $hash = password_hash($row['password'], PASSWORD_DEFAULT);
                $stmt_up->bind_param("si", $hash, $row['login_id']);
                $stmt_up->execute();
                $upgraded++;
            }
        }
    }
    echo "Upgraded $upgraded plaintext passwords to secure bcrypt hashes.\n";

    // 2. Auth Tokens table for secure Remember Me
    $conn->query("CREATE TABLE IF NOT EXISTS tbl_auth_tokens (
        id INT AUTO_INCREMENT PRIMARY KEY,
        selector VARCHAR(32) NOT NULL UNIQUE,
        hashed_validator VARCHAR(64) NOT NULL,
        login_id INT NOT NULL,
        expires_at DATETIME NOT NULL,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        INDEX idx_selector (selector),
        INDEX idx_login (login_id)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
    echo "tbl_auth_tokens ready.\n";

    // 3. Login Attempts table for Brute-force Protection & Rate Limiting
    $conn->query("CREATE TABLE IF NOT EXISTS tbl_login_attempts (
        id INT AUTO_INCREMENT PRIMARY KEY,
        ip_address VARCHAR(45) NOT NULL,
        email VARCHAR(100) NOT NULL,
        attempt_type VARCHAR(20) NOT NULL DEFAULT 'login',
        attempted_at DATETIME NOT NULL,
        INDEX idx_ip_time (ip_address, attempted_at),
        INDEX idx_email_time (email, attempted_at)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
    echo "tbl_login_attempts ready.\n";

    // 4. Columns for existing tables
    $col_migrations = [
        "SELECT COLUMN_NAME FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'tbl_payable' AND COLUMN_NAME = 'group_id'" =>
        "ALTER TABLE tbl_payable ADD COLUMN group_id INT NOT NULL DEFAULT 0 AFTER head",

        "SELECT COLUMN_NAME FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'tbl_paid' AND COLUMN_NAME = 'group_id'" =>
        "ALTER TABLE tbl_paid ADD COLUMN group_id INT NOT NULL DEFAULT 0 AFTER head",

        "SELECT COLUMN_NAME FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'tbl_cashbook' AND COLUMN_NAME = 'group_id'" =>
        "ALTER TABLE tbl_cashbook ADD COLUMN group_id INT NOT NULL DEFAULT 0 AFTER id",

        "SELECT COLUMN_NAME FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'tbl_shuttle_cock_stock_in' AND COLUMN_NAME = 'group_id'" =>
        "ALTER TABLE tbl_shuttle_cock_stock_in ADD COLUMN group_id INT NOT NULL DEFAULT 0 AFTER purchase_date",

        "SELECT COLUMN_NAME FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'tbl_shuttle_cock_stock_in' AND COLUMN_NAME = 'bill_file'" =>
        "ALTER TABLE tbl_shuttle_cock_stock_in ADD COLUMN bill_file VARCHAR(255) DEFAULT NULL AFTER invoice_no",

        "SELECT COLUMN_NAME FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'tbl_shuttle_cock_stock_in' AND COLUMN_NAME = 'payable_id'" =>
        "ALTER TABLE tbl_shuttle_cock_stock_in ADD COLUMN payable_id INT NOT NULL DEFAULT 0 AFTER bill_file",
    ];

    foreach ($col_migrations as $check => $alter) {
        $res = $conn->query($check);
        if ($res && $res->num_rows === 0) {
            $conn->query($alter);
        }
    }

    // 5. Table migrations for RBAC & Features
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

        "CREATE TABLE IF NOT EXISTS tbl_shuttle_cock_stock_in (
            id INT PRIMARY KEY AUTO_INCREMENT,
            purchase_date DATE NOT NULL,
            group_id INT NOT NULL DEFAULT 0,
            brand_name VARCHAR(100) NOT NULL DEFAULT 'Standard',
            no_of_boxes INT NOT NULL DEFAULT 0,
            shuttles_per_box INT NOT NULL DEFAULT 12,
            total_shuttles INT NOT NULL DEFAULT 0,
            box_price DECIMAL(10,2) NOT NULL DEFAULT 0.00,
            per_shuttle_price DECIMAL(10,2) NOT NULL DEFAULT 0.00,
            total_cost DECIMAL(10,2) NOT NULL DEFAULT 0.00,
            supplier_name VARCHAR(150) DEFAULT NULL,
            invoice_no VARCHAR(50) DEFAULT NULL,
            notes TEXT DEFAULT NULL,
            created_by INT DEFAULT NULL,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4",

        "CREATE TABLE IF NOT EXISTS tbl_shuttle_cock_readings (
            id INT PRIMARY KEY AUTO_INCREMENT,
            reading_date DATE NOT NULL,
            group_id INT NOT NULL DEFAULT 0,
            brand_name VARCHAR(100) DEFAULT NULL,
            start_shuttle INT NOT NULL DEFAULT 0,
            end_shuttle INT NOT NULL DEFAULT 0,
            shuttles_used INT NOT NULL DEFAULT 0,
            per_shuttle_price DECIMAL(10,2) NOT NULL DEFAULT 0.00,
            total_amount DECIMAL(10,2) NOT NULL DEFAULT 0.00,
            notes TEXT DEFAULT NULL,
            created_by INT DEFAULT NULL,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4",

        "CREATE TABLE IF NOT EXISTS tbl_fee_master (
            id INT PRIMARY KEY AUTO_INCREMENT,
            from_date DATE NOT NULL,
            to_date DATE NOT NULL,
            fee INT(20) NOT NULL DEFAULT 300,
            fee_method VARCHAR(50) NOT NULL DEFAULT 'fixed',
            group_id INT(11) NOT NULL DEFAULT 0
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4",
    ];

    foreach ($table_migrations as $sql) {
        $conn->query($sql);
    }

    // Fee method column check
    $col_chk = $conn->query("SHOW COLUMNS FROM tbl_fee_master LIKE 'fee_method'");
    if ($col_chk && $col_chk->num_rows === 0) {
        $conn->query("ALTER TABLE tbl_fee_master ADD COLUMN fee_method VARCHAR(50) NOT NULL DEFAULT 'fixed' AFTER fee");
    }

    // 6. Decimal money column updates and utf8mb4 charset conversion
    $money_mods = [
        "ALTER TABLE tbl_member_recievable MODIFY COLUMN fees DECIMAL(10,2) NOT NULL DEFAULT 0.00, CONVERT TO CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci",
        "ALTER TABLE tbl_member_recieved MODIFY COLUMN fees DECIMAL(10,2) NOT NULL DEFAULT 0.00, CONVERT TO CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci",
        "ALTER TABLE tbl_payable MODIFY COLUMN amount DECIMAL(10,2) NOT NULL DEFAULT 0.00, CONVERT TO CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci",
        "ALTER TABLE tbl_paid MODIFY COLUMN amount DECIMAL(10,2) NOT NULL DEFAULT 0.00, CONVERT TO CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci",
        "ALTER TABLE tbl_wallet MODIFY COLUMN amount DECIMAL(10,2) NOT NULL DEFAULT 0.00, CONVERT TO CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci",
        "ALTER TABLE tbl_other_recieveble MODIFY COLUMN amount DECIMAL(10,2) NOT NULL DEFAULT 0.00, CONVERT TO CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci",
        "ALTER TABLE tbl_other_recieved MODIFY COLUMN amount DECIMAL(10,2) NOT NULL DEFAULT 0.00, CONVERT TO CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci",
        "ALTER TABLE tbl_fee_master MODIFY COLUMN fee DECIMAL(10,2) NOT NULL DEFAULT 300.00, CONVERT TO CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci"
    ];
    foreach ($money_mods as $sql) {
        if (!$conn->query($sql)) {
            echo "Notice on money mod: " . $conn->error . "\n";
        }
    }
    echo "Financial decimal column and charset migrations completed.\n";

    // Seed default roles
    $conn->query("INSERT IGNORE INTO tbl_roles (role_id, role_name, description) VALUES
        (1, 'Super Admin', 'Full system access across all groups and settings'),
        (2, 'Group Admin', 'Manages income, expenses, members, and attendance for assigned groups'),
        (3, 'Attendance Master', 'Delegated member who takes/manages attendance for assigned groups'),
        (4, 'Member', 'General member with self-service dashboard and payment access'),
        (5, 'Executive Member', 'Executive member with access to view all club reports and analytics')");

    $conn->query("INSERT IGNORE INTO tbl_user_group_roles (login_id, group_id, role_id) VALUES (1, 0, 1)");

    $conn->query("INSERT IGNORE INTO tbl_menu (id, name, nav_url, parent_id, menu_level, sub_menu, icon) VALUES (53, 'Role & Group Manager', 'role_management.php', 51, 1, 0, 'fa fa-shield')");
    $conn->query("INSERT IGNORE INTO tbl_menu_map (login_id, menu_id, is_active) VALUES (1, 53, 1)");

    $conn->query("INSERT INTO tbl_fee_master (from_date, to_date, fee, fee_method, group_id)
        SELECT '2024-04-01', '2030-03-31', 300, 'fixed', 0
        WHERE NOT EXISTS (SELECT 1 FROM tbl_fee_master LIMIT 1)");

    $lock_file = __DIR__ . '/.schema_migrated.lock';
    @file_put_contents($lock_file, date('Y-m-d H:i:s'));
    echo "All migrations applied successfully!\n";
} catch (Throwable $e) {
    echo "Migration failed: " . $e->getMessage() . "\n";
    exit(1);
}
