<?php
require_once __DIR__ . '/../includes/api_auth.php';
require_once __DIR__ . '/../includes/auth_security.php';
require_once __DIR__ . '/../includes/auth.php';

start_secure_session();

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $email = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';

    if (empty($email) || empty($password)) {
        header("Location: ../index.php?error=invalid");
        exit();
    }

    // 1. Brute-force protection: check rate limit (max 5 failed attempts in 15 mins)
    if (!check_rate_limit($email, 'login', 5, 15)) {
        header("Location: ../index.php?error=locked");
        exit();
    }

    try {
        $sql = "SELECT l.login_id, l.name, l.email, l.password, m.id AS user_id 
                FROM tbl_login AS l 
                LEFT JOIN tbl_members AS m ON LTRIM(RTRIM(LOWER(l.email))) = LTRIM(RTRIM(LOWER(m.email))) 
                WHERE LTRIM(RTRIM(LOWER(l.email))) = LTRIM(RTRIM(LOWER(?))) 
                LIMIT 1";
        $result = app_exec_getresult($sql, [$email], "s");

        $authenticated = false;
        $row = null;

        if ($result && $row = $result->fetch_assoc()) {
            $db_password = $row['password'];

            // Check hashed password first
            if (password_verify($password, $db_password)) {
                $authenticated = true;
                // Rehash if algorithm/cost parameters changed
                if (password_needs_rehash($db_password, PASSWORD_DEFAULT)) {
                    $new_hash = password_hash($password, PASSWORD_DEFAULT);
                    app_exec_nonquery("UPDATE tbl_login SET password = ? WHERE login_id = ?", [$new_hash, (int)$row['login_id']], "si");
                }
            } elseif (hash_equals((string)$db_password, (string)$password)) {
                // Legacy plaintext match: authenticate and immediately upgrade to secure hash
                $authenticated = true;
                $new_hash = password_hash($password, PASSWORD_DEFAULT);
                app_exec_nonquery("UPDATE tbl_login SET password = ? WHERE login_id = ?", [$new_hash, (int)$row['login_id']], "si");
            }
        }

        if ($authenticated && $row) {
            // Clear failed attempts upon successful login
            clear_login_attempts($email, 'login');

            // Prevent session fixation
            session_regenerate_id(true);

            $login_id = (int)$row['login_id'];
            $_SESSION['name']          = $row['name'];
            $_SESSION['email']         = $row['email']; // from DB row, NOT from POST
            $_SESSION['id']            = $login_id;
            $_SESSION['login_id']      = $login_id;
            $_SESSION['user_id']       = !empty($row['user_id']) ? (int)$row['user_id'] : 0;
            $_SESSION['primary_role']  = getUserPrimaryRoleName($login_id);
            $_SESSION['allowed_groups'] = getUserAllowedGroupIds($login_id);

            // Handle secure remember-me
            if (isset($_POST['remember_me'])) {
                create_remember_cookie($login_id);
            } else {
                clear_remember_cookie();
            }

            // Smart Routing Based on Device & Role
            if (isMobileClient()) {
                header("Location: ../directory/mobile/home.php");
                exit();
            }

            if (isSuperAdmin($login_id)) {
                header("Location: ../directory/dashboard.php");
            } elseif (isGroupAdmin($login_id)) {
                header("Location: ../directory/group_dashboard.php");
            } elseif (isAttendanceMaster($login_id)) {
                header("Location: ../directory/attendance.php");
            } else {
                header("Location: ../directory/user_attendance.php");
            }
            exit();
        } else {
            // Failed login: record attempt and return error
            record_failed_attempt($email, 'login');
            header("Location: ../index.php?error=invalid");
            exit();
        }

    } catch (Throwable $e) {
        header("Location: ../index.php?error=server");
        exit();
    }
} else {
    header("Location: ../index.php");
    exit();
}
