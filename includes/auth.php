<?php
if (defined('APP_AUTH_HELPER_LOADED')) {
    return;
}
define('APP_AUTH_HELPER_LOADED', true);

require_once __DIR__ . '/../config/app.php';
require_once __DIR__ . '/db.php';
require_once __DIR__ . '/api_auth.php';
require_once __DIR__ . '/auth_security.php';

/**
 * Detect if the client request is coming from a mobile device or Chrome installed PWA
 */
if (!function_exists('isMobileClient')) {
    function isMobileClient() {
        if (isset($_GET['pwa']) || isset($_GET['mobile'])) {
            if (session_status() === PHP_SESSION_ACTIVE) {
                $_SESSION['is_pwa_mobile'] = true;
            }
            return true;
        }
        if (session_status() === PHP_SESSION_ACTIVE && !empty($_SESSION['is_pwa_mobile'])) {
            return true;
        }
        $user_agent = $_SERVER['HTTP_USER_AGENT'] ?? '';
        if (preg_match('/Mobile|Android|iPhone|iPad|iPod|webOS|BlackBerry|IEMobile|Opera Mini/i', $user_agent)) {
            return true;
        }
        if (isset($_SERVER['HTTP_SEC_CH_UA_MOBILE']) && strpos($_SERVER['HTTP_SEC_CH_UA_MOBILE'], '?1') !== false) {
            return true;
        }
        return false;
    }
}

/**
 * Fetch all role & group assignments for a given login ID
 */
if (!function_exists('getUserRolesAndGroups')) {
    function getUserRolesAndGroups($login_id) {
        if (empty($login_id)) return [];
        
        $sql = "SELECT ugr.id, ugr.group_id, ugr.role_id, r.role_name, r.description 
                FROM tbl_user_group_roles ugr 
                JOIN tbl_roles r ON ugr.role_id = r.role_id 
                WHERE ugr.login_id = ?";
        $result = app_exec_getresult($sql, [$login_id], "i");
        
        $assignments = [];
        if ($result && $result->num_rows > 0) {
            while ($row = $result->fetch_assoc()) {
                $assignments[] = $row;
            }
        }
        
        // Default fallback for legacy logins: login_id 1 is Super Admin, others are Member
        if (empty($assignments)) {
            if ($login_id == 1) {
                $assignments[] = ['group_id' => 0, 'role_id' => 1, 'role_name' => 'Super Admin', 'description' => 'Full system access'];
            } else {
                $assignments[] = ['group_id' => 0, 'role_id' => 4, 'role_name' => 'Member', 'description' => 'General member'];
            }
        }
        
        return $assignments;
    }
}

/**
 * Check if a user is Super Admin
 */
if (!function_exists('isSuperAdmin')) {
    function isSuperAdmin($login_id) {
        static $cache = [];
        $lid = (int)$login_id;
        if ($lid <= 0) return false;
        if ($lid === 1) return true;
        if (isset($cache[$lid])) return $cache[$lid];
        
        $sql = "SELECT COUNT(*) as cnt FROM tbl_user_group_roles WHERE login_id = ? AND role_id = 1 AND group_id = 0";
        $res = app_exec_getresult($sql, [$lid], "i");
        $val = false;
        if ($res && $row = $res->fetch_assoc()) {
            $val = ((int)$row['cnt']) > 0;
        }
        return $cache[$lid] = $val;
    }
}

/**
 * Check if a user is a Group Admin (for any group or a specific group)
 */
if (!function_exists('isGroupAdmin')) {
    function isGroupAdmin($login_id, $group_id = null) {
        static $cache = [];
        $lid = (int)$login_id;
        $gid_key = ($group_id !== null) ? (int)$group_id : 'all';
        $key = "{$lid}_{$gid_key}";
        if (isset($cache[$key])) return $cache[$key];

        if (isSuperAdmin($lid)) return $cache[$key] = true;
        if ($lid <= 0) return $cache[$key] = false;
        
        if ($group_id !== null) {
            $sql = "SELECT COUNT(*) as cnt FROM tbl_user_group_roles WHERE login_id = ? AND role_id = 2 AND (group_id = ? OR group_id = 0)";
            $res = app_exec_getresult($sql, [$lid, (int)$group_id], "ii");
        } else {
            $sql = "SELECT COUNT(*) as cnt FROM tbl_user_group_roles WHERE login_id = ? AND role_id = 2";
            $res = app_exec_getresult($sql, [$lid], "i");
        }
        
        $val = false;
        if ($res && $row = $res->fetch_assoc()) {
            $val = ((int)$row['cnt']) > 0;
        }
        return $cache[$key] = $val;
    }
}

/**
 * Check if a user is an Attendance Master (for any group or a specific group)
 */
if (!function_exists('isAttendanceMaster')) {
    function isAttendanceMaster($login_id, $group_id = null) {
        static $cache = [];
        $lid = (int)$login_id;
        $gid_key = ($group_id !== null) ? (int)$group_id : 'all';
        $key = "{$lid}_{$gid_key}";
        if (isset($cache[$key])) return $cache[$key];

        if (isSuperAdmin($lid) || isGroupAdmin($lid, $group_id)) return $cache[$key] = true;
        if ($lid <= 0) return $cache[$key] = false;
        
        if ($group_id !== null) {
            $sql = "SELECT COUNT(*) as cnt FROM tbl_user_group_roles WHERE login_id = ? AND role_id = 3 AND (group_id = ? OR group_id = 0)";
            $res = app_exec_getresult($sql, [$lid, (int)$group_id], "ii");
        } else {
            $sql = "SELECT COUNT(*) as cnt FROM tbl_user_group_roles WHERE login_id = ? AND role_id = 3";
            $res = app_exec_getresult($sql, [$lid], "i");
        }
        
        $val = false;
        if ($res && $row = $res->fetch_assoc()) {
            $val = ((int)$row['cnt']) > 0;
        }
        return $cache[$key] = $val;
    }
}

/**
 * Check if a user is an Executive Member (role_id = 5)
 */
if (!function_exists('isExecutiveMember')) {
    function isExecutiveMember($login_id) {
        static $cache = [];
        $lid = (int)$login_id;
        if ($lid <= 0) return false;
        if (isset($cache[$lid])) return $cache[$lid];
        if (isSuperAdmin($lid)) return $cache[$lid] = true;
        
        $sql = "SELECT COUNT(*) as cnt FROM tbl_user_group_roles WHERE login_id = ? AND role_id = 5";
        $res = app_exec_getresult($sql, [$lid], "i");
        $val = false;
        if ($res && $row = $res->fetch_assoc()) {
            $val = ((int)$row['cnt']) > 0;
        }
        return $cache[$lid] = $val;
    }
}

/**
 * Get list of group IDs accessible by the logged-in user.
 * Returns array of group IDs, or ['ALL'] for Super Admin.
 */
if (!function_exists('getUserAllowedGroupIds')) {
    function getUserAllowedGroupIds($login_id) {
        $groups = [];
        if (!empty($login_id)) {
            $sql = "SELECT DISTINCT group_id FROM tbl_user_group_roles WHERE login_id = ? AND role_id IN (1, 2, 3) AND group_id > 0";
            $res = app_exec_getresult($sql, [$login_id], "i");
            if ($res && $res->num_rows > 0) {
                while ($row = $res->fetch_assoc()) {
                    $groups[] = (int)$row['group_id'];
                }
                if (!empty($groups)) {
                    return array_values(array_unique($groups));
                }
            }
        }

        if (isSuperAdmin($login_id) || isExecutiveMember($login_id)) {
            return ['ALL'];
        }

        $member_id = 0;
        if (!empty($_SESSION['user_id'])) {
            $member_id = (int)$_SESSION['user_id'];
        } elseif (!empty($_SESSION['email'])) {
            $m_res = app_exec_getresult("SELECT id FROM tbl_members WHERE LTRIM(RTRIM(LOWER(email))) = LTRIM(RTRIM(LOWER(?))) LIMIT 1", [$_SESSION['email']], "s");
            if ($m_res && $m_row = $m_res->fetch_assoc()) {
                $member_id = (int)$m_row['id'];
            }
        }
        if ($member_id > 0) {
            $g_res = app_exec_getresult("SELECT group_id FROM tbl_group_member_map WHERE member_id = ?", [$member_id], "i");
            if ($g_res) {
                while ($g_row = $g_res->fetch_assoc()) {
                    $gid = (int)$g_row['group_id'];
                    if ($gid > 0 && !in_array($gid, $groups, true)) {
                        $groups[] = $gid;
                    }
                }
            }
        }

        return array_values(array_unique($groups));
    }
}

/**
 * Verify if user has permission for a specific group ID
 */
if (!function_exists('hasGroupAccess')) {
    function hasGroupAccess($login_id, $group_id) {
        if (isSuperAdmin($login_id)) return true;
        $allowed = getUserAllowedGroupIds($login_id);
        if (in_array('ALL', $allowed, true)) return true;
        return in_array((int)$group_id, $allowed, true);
    }
}

/**
 * Check if a user is a Normal Member (not Super Admin, Group Admin, Attendance Master, or Executive Member)
 */
if (!function_exists('isNormalMember')) {
    function isNormalMember($login_id) {
        if (empty($login_id)) return true;
        return !isSuperAdmin($login_id) && !isGroupAdmin($login_id) && !isAttendanceMaster($login_id) && !isExecutiveMember($login_id);
    }
}

/**
 * Get user's primary role name
 */
if (!function_exists('getUserPrimaryRoleName')) {
    function getUserPrimaryRoleName($login_id) {
        if (isSuperAdmin($login_id)) return 'Super Admin';
        if (isGroupAdmin($login_id)) return 'Group Admin';
        if (isAttendanceMaster($login_id)) return 'Attendance Master';
        if (isExecutiveMember($login_id)) return 'Executive Member';
        return 'Member';
    }
}

/**
 * Delete old profile/member image files and thumbnails from server disk
 */
if (!function_exists('deleteOldMemberImage')) {
    function deleteOldMemberImage($old_img) {
        if (empty($old_img) || $old_img === '0' || strtolower($old_img) === 'customer.png' || strtolower($old_img) === 'default.png') {
            return;
        }

        $filename = basename($old_img);
        if (empty($filename)) {
            return;
        }

        $dirs = [
            __DIR__ . '/../image_upload/members/uploads/',
            __DIR__ . '/../image_upload/members/thumbnails/',
            __DIR__ . '/../image_upload/profile/uploads/',
            __DIR__ . '/../image_upload/profile/thumbnails/'
        ];

        foreach ($dirs as $dir) {
            $filePath = $dir . $filename;
            if (file_exists($filePath) && is_file($filePath)) {
                @unlink($filePath);
            }
        }
    }
}

/**
 * Format standard Member ID string (e.g. CLUB-1010), hidden for guest members
 */
if (!function_exists('getMemberCode')) {
    function getMemberCode($id, $isGuest = 0) {
        if (empty($id) || !empty($isGuest)) return '';
        $num = 1000 + (int)$id;
        $prefix = defined('CLUB_MEMBER_PREFIX') ? CLUB_MEMBER_PREFIX : 'CLUB-';
        return $prefix . $num;
    }
}
?>
