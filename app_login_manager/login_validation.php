<?php
session_start();

if ($_SERVER["REQUEST_METHOD"] == "POST") {    
    include '../app_common/db_connect.php';
    include '../app_common/auth_helper.php';

    try {
        $sql = "SELECT l.login_id, l.name, m.id AS user_id 
                FROM tbl_login AS l 
                LEFT JOIN tbl_members AS m ON LTRIM(RTRIM(LOWER(l.email))) = LTRIM(RTRIM(LOWER(m.email))) 
                WHERE LTRIM(RTRIM(LOWER(l.email))) = LTRIM(RTRIM(LOWER(?))) AND l.password = ?";
        $types = "ss";
        $parameters = [
            $_POST['email'],
            $_POST['password']
        ];
        
        $result = app_exec_getresult($sql, $parameters, $types);
    
        while ($row = $result->fetch_assoc()) {
            $_SESSION['name']     = $row['name'];
            $_SESSION['email']    = $_POST['email'];
            $_SESSION['id']       = $row["login_id"];
            $_SESSION['login_id'] = $row["login_id"];
            $_SESSION['user_id']  = $row["user_id"];
        }
        
        if ($result->num_rows > 0) {
            $login_id = (int)$_SESSION['login_id'];
            
            // Initialize Role & Group Session Permissions
            $_SESSION['primary_role'] = getUserPrimaryRoleName($login_id);
            $_SESSION['allowed_groups'] = getUserAllowedGroupIds($login_id);
            
            if (isset($_POST['remember_me'])) {
                $expiry    = time() + (30 * 24 * 60 * 60);
                $signature = hash_hmac('sha256', $login_id, 'ymca-secure-secret-key-9988');
                setcookie('remember_user', $login_id . ':' . $signature, $expiry, '/');
            }

            // Smart Routing Based on Role
            if (isSuperAdmin($login_id)) {
                header("Location: ../directory/dashboard.php");
                exit();
            }

            // Auto-detect mobile device login for non-super-admins
            $user_agent = $_SERVER['HTTP_USER_AGENT'] ?? '';
            $is_mobile = (bool)preg_match('/Mobile|Android|iPhone|iPad|iPod|webOS|BlackBerry|IEMobile|Opera Mini/i', $user_agent);

            if ($is_mobile) {
                header("Location: ../directory/mobile/home.php");
                exit();
            }

            if (isGroupAdmin($login_id)) {
                header("Location: ../directory/group_dashboard.php");
            } else if (isAttendanceMaster($login_id)) {
                header("Location: ../directory/attendance.php");
            } else {
                header("Location: ../directory/user_attendance.php");
            }
            exit();
        } else {
            // Wrong credentials — redirect back to login with error flag
            header("Location: ../index.php?error=invalid");
            exit();
        }

    } catch (Throwable $e) {
        // Server error — redirect back with generic error flag
        header("Location: ../index.php?error=server");
        exit();
    }
} else {
    // Direct access without POST — send back to login
    header("Location: ../index.php");
    exit();
}
?>
