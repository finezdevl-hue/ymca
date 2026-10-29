<?php
/**
 * API Authentication & Role Guard
 * Enforces deny-by-default access control for all API endpoints.
 */

if (defined('APP_API_AUTH_LOADED')) {
    return;
}
define('APP_API_AUTH_LOADED', true);

require_once __DIR__ . '/auth.php';

if (!function_exists('start_secure_session')) {
    function start_secure_session(): void {
        if (session_status() === PHP_SESSION_ACTIVE) {
            return;
        }

        if (!headers_sent()) {
            $is_https = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off')
                || (isset($_SERVER['SERVER_PORT']) && $_SERVER['SERVER_PORT'] == 443)
                || (isset($_SERVER['HTTP_X_FORWARDED_PROTO']) && strtolower($_SERVER['HTTP_X_FORWARDED_PROTO']) === 'https');

            $current_params = session_get_cookie_params();
            session_set_cookie_params([
                'lifetime' => $current_params['lifetime'],
                'path'     => '/',
                'domain'   => $current_params['domain'] ?? '',
                'secure'   => $is_https,
                'httponly' => true,
                'samesite' => 'Lax'
            ]);
        }

        @session_start();
    }
}

if (!function_exists('send_json_error')) {
    function send_json_error(int $status_code, string $message): void {
        http_response_code($status_code);
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode([
            'status'  => 'error',
            'message' => $message
        ]);
        exit();
    }
}

if (!function_exists('require_login')) {
    /**
     * Ensure the user is logged in. Denies access by default (HTTP 401).
     * @return int The authenticated user's login_id
     */
    function require_login(): int {
        start_secure_session();

        if (empty($_SESSION['login_id'])) {
            send_json_error(401, 'Unauthorized. Please log in.');
        }

        return (int)$_SESSION['login_id'];
    }
}

if (!function_exists('require_role')) {
    /**
     * Enforce role authorization. Denies access (HTTP 403) if user lacks role.
     * @param string|array $allowed_roles Allowed roles: 'super_admin', 'admin', 'attendance', 'executive', 'member', or array of role names
     * @param int|null $group_id Optional group_id for group-scoped checks
     * @return int The authenticated user's login_id
     */
    function require_role($allowed_roles = 'admin', ?int $group_id = null): int {
        $login_id = require_login();

        if (isSuperAdmin($login_id)) {
            return $login_id;
        }

        if (is_string($allowed_roles)) {
            $allowed_roles = [$allowed_roles];
        }

        $is_authorized = false;
        foreach ($allowed_roles as $role) {
            $r = strtolower(trim($role));
            if ($r === 'super_admin' && isSuperAdmin($login_id)) {
                $is_authorized = true;
                break;
            }
            if (($r === 'admin' || $r === 'group_admin') && isGroupAdmin($login_id, $group_id)) {
                $is_authorized = true;
                break;
            }
            if ($r === 'attendance' && isAttendanceMaster($login_id, $group_id)) {
                $is_authorized = true;
                break;
            }
            if ($r === 'executive' && isExecutiveMember($login_id)) {
                $is_authorized = true;
                break;
            }
            if ($r === 'member' || $r === 'all') {
                $is_authorized = true;
                break;
            }
        }

        if (!$is_authorized) {
            send_json_error(403, 'Forbidden. You do not have permission to access this resource.');
        }

        return $login_id;
    }
}

if (!function_exists('require_admin')) {
    function require_admin(?int $group_id = null): int {
        return require_role(['super_admin', 'admin'], $group_id);
    }
}

if (!function_exists('require_super_admin')) {
    function require_super_admin(): int {
        return require_role(['super_admin']);
    }
}

if (!function_exists('require_attendance_master')) {
    function require_attendance_master(?int $group_id = null): int {
        return require_role(['super_admin', 'admin', 'attendance'], $group_id);
    }
}
