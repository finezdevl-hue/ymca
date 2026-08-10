<?php
if (defined('APP_AUTH_HELPER_LOADED')) {
    return;
}
define('APP_AUTH_HELPER_LOADED', true);

include_once __DIR__ . '/db_connect.php';

/**
 * Fetch all role & group assignments for a given login ID
 */
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

/**
 * Check if a user is Super Admin
 */
function isSuperAdmin($login_id) {
    if (empty($login_id)) return false;
    if ($login_id == 1) return true;
    
    $sql = "SELECT COUNT(*) as cnt FROM tbl_user_group_roles WHERE login_id = ? AND role_id = 1 AND group_id = 0";
    $res = app_exec_getresult($sql, [$login_id], "i");
    if ($res) {
        $row = $res->fetch_assoc();
        return ((int)$row['cnt']) > 0;
    }
    return false;
}

/**
 * Check if a user is a Group Admin (for any group or a specific group)
 */
function isGroupAdmin($login_id, $group_id = null) {
    if (isSuperAdmin($login_id)) return true;
    if (empty($login_id)) return false;
    
    if ($group_id !== null) {
        $sql = "SELECT COUNT(*) as cnt FROM tbl_user_group_roles WHERE login_id = ? AND role_id = 2 AND (group_id = ? OR group_id = 0)";
        $res = app_exec_getresult($sql, [$login_id, $group_id], "ii");
    } else {
        $sql = "SELECT COUNT(*) as cnt FROM tbl_user_group_roles WHERE login_id = ? AND role_id = 2";
        $res = app_exec_getresult($sql, [$login_id], "i");
    }
    
    if ($res) {
        $row = $res->fetch_assoc();
        return ((int)$row['cnt']) > 0;
    }
    return false;
}

/**
 * Check if a user is an Attendance Master (for any group or a specific group)
 */
function isAttendanceMaster($login_id, $group_id = null) {
    if (isSuperAdmin($login_id) || isGroupAdmin($login_id, $group_id)) return true;
    if (empty($login_id)) return false;
    
    if ($group_id !== null) {
        $sql = "SELECT COUNT(*) as cnt FROM tbl_user_group_roles WHERE login_id = ? AND role_id = 3 AND (group_id = ? OR group_id = 0)";
        $res = app_exec_getresult($sql, [$login_id, $group_id], "ii");
    } else {
        $sql = "SELECT COUNT(*) as cnt FROM tbl_user_group_roles WHERE login_id = ? AND role_id = 3";
        $res = app_exec_getresult($sql, [$login_id], "i");
    }
    
    if ($res) {
        $row = $res->fetch_assoc();
        return ((int)$row['cnt']) > 0;
    }
    return false;
}

/**
 * Check if a user is an Executive Member (role_id = 5)
 */
function isExecutiveMember($login_id) {
    if (empty($login_id)) return false;
    if (isSuperAdmin($login_id)) return true;
    
    $sql = "SELECT COUNT(*) as cnt FROM tbl_user_group_roles WHERE login_id = ? AND role_id = 5";
    $res = app_exec_getresult($sql, [$login_id], "i");
    if ($res) {
        $row = $res->fetch_assoc();
        return ((int)$row['cnt']) > 0;
    }
    return false;
}

/**
 * Get list of group IDs accessible by the logged-in user.
 * Returns array of group IDs, or ['ALL'] for Super Admin.
 */
function getUserAllowedGroupIds($login_id) {
    if (isSuperAdmin($login_id) || isExecutiveMember($login_id)) {
        return ['ALL'];
    }
    
    $sql = "SELECT DISTINCT group_id FROM tbl_user_group_roles WHERE login_id = ?";
    $res = app_exec_getresult($sql, [$login_id], "i");
    $groups = [];
    if ($res) {
        while ($row = $res->fetch_assoc()) {
            if ($row['group_id'] == 0) return ['ALL'];
            $groups[] = (int)$row['group_id'];
        }
    }

    if (empty($groups)) {
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
                    $groups[] = (int)$g_row['group_id'];
                }
            }
        }
    }

    return $groups;
}

/**
 * Verify if user has permission for a specific group ID
 */
function hasGroupAccess($login_id, $group_id) {
    if (isSuperAdmin($login_id)) return true;
    $allowed = getUserAllowedGroupIds($login_id);
    if (in_array('ALL', $allowed, true)) return true;
    return in_array((int)$group_id, $allowed, true);
}

/**
 * Check if a user is a Normal Member (not Super Admin, Group Admin, Attendance Master, or Executive Member)
 */
function isNormalMember($login_id) {
    if (empty($login_id)) return true;
    return !isSuperAdmin($login_id) && !isGroupAdmin($login_id) && !isAttendanceMaster($login_id) && !isExecutiveMember($login_id);
}

/**
 * Get user's primary role name
 */
function getUserPrimaryRoleName($login_id) {
    if (isSuperAdmin($login_id)) return 'Super Admin';
    if (isGroupAdmin($login_id)) return 'Group Admin';
    if (isAttendanceMaster($login_id)) return 'Attendance Master';
    if (isExecutiveMember($login_id)) return 'Executive Member';
    return 'Member';
}

/**
 * Delete old profile/member image files and thumbnails from server disk
 */
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

/**
 * Format standard Member ID string (e.g. YMCA-BCP-1010), hidden for guest members
 */
function getMemberCode($id, $isGuest = 0) {
    if (empty($id) || !empty($isGuest)) return '';
    $num = 1000 + (int)$id;
    return "YMCA-BCP-" . $num;
}
?>
