<?php
/**
 * Auth Security Helper
 * Handles secure Remember Me tokens (selector + validator pattern),
 * rate limiting, and brute-force protection.
 */

if (defined('APP_AUTH_SECURITY_LOADED')) {
    return;
}
define('APP_AUTH_SECURITY_LOADED', true);

require_once __DIR__ . '/db.php';
require_once __DIR__ . '/../config/env.php';

if (!function_exists('is_request_https')) {
    function is_request_https(): bool {
        return (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off')
            || (isset($_SERVER['SERVER_PORT']) && $_SERVER['SERVER_PORT'] == 443)
            || (isset($_SERVER['HTTP_X_FORWARDED_PROTO']) && strtolower($_SERVER['HTTP_X_FORWARDED_PROTO']) === 'https');
    }
}

/**
 * Creates and sets a secure Remember-Me cookie using the selector/validator pattern.
 */
if (!function_exists('create_remember_cookie')) {
    function create_remember_cookie(int $login_id, int $days = 30): bool {
        try {
            $selector = bin2hex(random_bytes(16)); // 32 hex chars
            $validator = bin2hex(random_bytes(32)); // 64 hex chars
            $hashed_validator = hash('sha256', $validator);

            $expires_at = date('Y-m-d H:i:s', time() + ($days * 86400));

            $sql = "INSERT INTO tbl_auth_tokens (selector, hashed_validator, login_id, expires_at) VALUES (?, ?, ?, ?)";
            $ok = app_exec_nonquery($sql, [$selector, $hashed_validator, $login_id, $expires_at], "ssis");

            if ($ok) {
                $cookie_value = $selector . ':' . $validator;
                $options = [
                    'expires'  => time() + ($days * 86400),
                    'path'     => '/',
                    'domain'   => '',
                    'secure'   => is_request_https(),
                    'httponly' => true,
                    'samesite' => 'Lax'
                ];
                @setcookie('remember_user', $cookie_value, $options);
                $_COOKIE['remember_user'] = $cookie_value;
                return true;
            }
            return false;
        } catch (Throwable $e) {
            return false;
        }
    }
}

/**
 * Validates the Remember-Me cookie, rotates the token on use, and returns the login_id or null.
 */
if (!function_exists('verify_and_rotate_remember_cookie')) {
    function verify_and_rotate_remember_cookie(): ?int {
        if (empty($_COOKIE['remember_user']) || !is_string($_COOKIE['remember_user'])) {
            return null;
        }

        $parts = explode(':', $_COOKIE['remember_user']);
        if (count($parts) !== 2) {
            clear_remember_cookie();
            return null;
        }

        $selector = $parts[0];
        $validator = $parts[1];

        if (strlen($selector) !== 32 || strlen($validator) !== 64) {
            clear_remember_cookie();
            return null;
        }

        try {
            $now = date('Y-m-d H:i:s');
            $sql = "SELECT id, selector, hashed_validator, login_id FROM tbl_auth_tokens WHERE selector = ? AND expires_at > ? LIMIT 1";
            $res = app_exec_getresult($sql, [$selector, $now], "ss");

            if (!$res || $res->num_rows === 0) {
                clear_remember_cookie();
                return null;
            }

            $token_row = $res->fetch_assoc();
            $expected_hash = hash('sha256', $validator);

            if (!hash_equals($token_row['hashed_validator'], $expected_hash)) {
                // Token mismatch: potential theft! Invalidate all tokens for this user
                app_exec_nonquery("DELETE FROM tbl_auth_tokens WHERE login_id = ?", [(int)$token_row['login_id']], "i");
                clear_remember_cookie();
                return null;
            }

            $login_id = (int)$token_row['login_id'];

            // Rotate token on each use
            app_exec_nonquery("DELETE FROM tbl_auth_tokens WHERE id = ?", [(int)$token_row['id']], "i");
            create_remember_cookie($login_id);

            return $login_id;
        } catch (Throwable $e) {
            return null;
        }
    }
}

/**
 * Revokes the current Remember-Me cookie and deletes token from database.
 */
if (!function_exists('clear_remember_cookie')) {
    function clear_remember_cookie(): void {
        if (!empty($_COOKIE['remember_user']) && is_string($_COOKIE['remember_user'])) {
            $parts = explode(':', $_COOKIE['remember_user']);
            if (count($parts) === 2 && strlen($parts[0]) === 32) {
                try {
                    app_exec_nonquery("DELETE FROM tbl_auth_tokens WHERE selector = ?", [$parts[0]], "s");
                } catch (Throwable $e) {}
            }
        }

        $options = [
            'expires'  => time() - 3600,
            'path'     => '/',
            'domain'   => '',
            'secure'   => is_request_https(),
            'httponly' => true,
            'samesite' => 'Lax'
        ];
        @setcookie('remember_user', '', $options);
        unset($_COOKIE['remember_user']);
    }
}

/**
 * Check if the given action exceeds the rate limit (Brute-force protection).
 */
if (!function_exists('check_rate_limit')) {
    function check_rate_limit(string $email, string $type = 'login', int $max_attempts = 5, int $window_minutes = 15): bool {
        try {
            $ip = $_SERVER['REMOTE_ADDR'] ?? '127.0.0.1';
            $cutoff = date('Y-m-d H:i:s', time() - ($window_minutes * 60));

            // Clean up stale attempts periodically
            if (mt_rand(1, 20) === 1) {
                app_exec_nonquery("DELETE FROM tbl_login_attempts WHERE attempted_at < DATE_SUB(NOW(), INTERVAL 2 HOUR)", [], "");
            }

            $sql = "SELECT COUNT(*) AS cnt FROM tbl_login_attempts 
                    WHERE (ip_address = ? OR email = ?) AND attempt_type = ? AND attempted_at >= ?";
            $res = app_exec_getresult($sql, [$ip, $email, $type, $cutoff], "ssss");
            if ($res && $row = $res->fetch_assoc()) {
                if ((int)$row['cnt'] >= $max_attempts) {
                    return false; // Rate limit exceeded!
                }
            }
            return true;
        } catch (Throwable $e) {
            return true;
        }
    }
}

/**
 * Records a failed attempt for rate limiting.
 */
if (!function_exists('record_failed_attempt')) {
    function record_failed_attempt(string $email, string $type = 'login'): void {
        try {
            $ip = $_SERVER['REMOTE_ADDR'] ?? '127.0.0.1';
            $now = date('Y-m-d H:i:s');
            $sql = "INSERT INTO tbl_login_attempts (ip_address, email, attempt_type, attempted_at) VALUES (?, ?, ?, ?)";
            app_exec_nonquery($sql, [$ip, $email, $type, $now], "ssss");
        } catch (Throwable $e) {}
    }
}

/**
 * Clears recorded attempts upon successful authentication.
 */
if (!function_exists('clear_login_attempts')) {
    function clear_login_attempts(string $email, string $type = 'login'): void {
        try {
            $ip = $_SERVER['REMOTE_ADDR'] ?? '127.0.0.1';
            $sql = "DELETE FROM tbl_login_attempts WHERE (ip_address = ? OR email = ?) AND attempt_type = ?";
            app_exec_nonquery($sql, [$ip, $email, $type], "sss");
        } catch (Throwable $e) {}
    }
}
