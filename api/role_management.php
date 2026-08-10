<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
error_reporting(0);
ini_set('display_errors', '0');
header('Content-Type: application/json; charset=utf-8');

include_once __DIR__ . '/../app_common/db_connect.php';
include_once __DIR__ . '/../app_common/auth_helper.php';

$login_id = !empty($_SESSION['login_id']) ? (int)$_SESSION['login_id'] : 0;
if (!isSuperAdmin($login_id)) {
    http_response_code(403);
    echo json_encode(['status' => 'error', 'message' => 'Unauthorized access. Only Super Admin can manage roles.']);
    exit();
}

if (isset($_POST['action']) && !empty($_POST['action'])) {
    $action = $_POST['action'];

    // 1. Fetch available roles (exclude default Member role 4)
    if ($action == "load_roles") {
        try {
            $res = app_exec_query("SELECT role_id, role_name, description FROM tbl_roles WHERE role_id != 4 ORDER BY role_id ASC");
            $roles = [];
            if ($res) {
                while ($row = $res->fetch_assoc()) {
                    $roles[] = $row;
                }
            }
            echo json_encode(['status' => 'success', 'data' => $roles]);
            exit();
        } catch (Throwable $e) {
            echo json_encode(['status' => 'error', 'message' => $e->getMessage()]);
            exit();
        }
    }

    // 2. Fetch available groups
    if ($action == "load_groups") {
        try {
            $res = app_exec_query("SELECT id as group_id, name as group_name FROM tbl_groups WHERE status = 1 ORDER BY name ASC");
            $groups = [];
            if ($res) {
                while ($row = $res->fetch_assoc()) {
                    $groups[] = $row;
                }
            }
            echo json_encode(['status' => 'success', 'data' => $groups]);
            exit();
        } catch (Throwable $e) {
            echo json_encode(['status' => 'error', 'message' => $e->getMessage()]);
            exit();
        }
    }

    // 3. Search Users with Role Assignments
    if ($action == "load_users") {
        try {
            $search = !empty($_POST['search']) ? trim($_POST['search']) : '';
            if ($search !== '') {
                $sql = "SELECT l.login_id, l.name, l.email, m.id as member_id 
                        FROM tbl_login l 
                        LEFT JOIN tbl_members m ON LTRIM(RTRIM(LOWER(l.email))) = LTRIM(RTRIM(LOWER(m.email))) 
                        WHERE l.name LIKE ? OR l.email LIKE ? 
                        ORDER BY l.login_id ASC LIMIT 50";
                $res = app_exec_getresult($sql, ['%' . $search . '%', '%' . $search . '%'], "ss");
            } else {
                $sql = "SELECT l.login_id, l.name, l.email, m.id as member_id 
                        FROM tbl_login l 
                        LEFT JOIN tbl_members m ON LTRIM(RTRIM(LOWER(l.email))) = LTRIM(RTRIM(LOWER(m.email))) 
                        ORDER BY l.login_id ASC LIMIT 50";
                $res = app_exec_query($sql);
            }

            $users = [];
            if ($res) {
                while ($row = $res->fetch_assoc()) {
                    $row_login_id = (int)$row['login_id'];
                    $assignments = getUserRolesAndGroups($row_login_id);
                    $primary_role = getUserPrimaryRoleName($row_login_id);
                    
                    $row['assignments'] = $assignments;
                    $row['primary_role'] = $primary_role;
                    $users[] = $row;
                }
            }

            echo json_encode(['status' => 'success', 'data' => $users]);
            exit();
        } catch (Throwable $e) {
            echo json_encode(['status' => 'error', 'message' => $e->getMessage()]);
            exit();
        }
    }

    // 4. Save User Role Assignment (Single Role per User)
    if ($action == "save_assignment") {
        try {
            $target_login_id = (int)($_POST['target_login_id'] ?? ($_POST['login_id'] ?? 0));
            
            // Extract role_id
            $role_id = 0;
            if (isset($_POST['role_id']) && (int)$_POST['role_id'] > 0) {
                $role_id = (int)$_POST['role_id'];
            } elseif (!empty($_POST['role_ids'])) {
                $r_arr = is_array($_POST['role_ids']) ? $_POST['role_ids'] : explode(',', $_POST['role_ids']);
                $r_arr = array_filter(array_map('intval', $r_arr), function($r) { return $r > 0 && $r != 4; });
                $role_id = !empty($r_arr) ? reset($r_arr) : 0;
            }

            // Extract group_id
            $group_id = 0;
            if (isset($_POST['group_id'])) {
                $group_id = (int)$_POST['group_id'];
            } elseif (!empty($_POST['group_ids'])) {
                $g_arr = is_array($_POST['group_ids']) ? $_POST['group_ids'] : explode(',', $_POST['group_ids']);
                $group_id = !empty($g_arr) ? (int)reset($g_arr) : 0;
            }

            if (empty($target_login_id)) {
                echo json_encode(['status' => 'error', 'message' => 'Missing target user.']);
                exit();
            }

            // Clear all existing special role assignments for this user
            $del_sql = "DELETE FROM tbl_user_group_roles WHERE login_id = ?";
            app_exec_nonquery($del_sql, [$target_login_id], "i");

            // If no special role is selected or role_id == 4 (Member), user remains default Member
            if (empty($role_id) || $role_id == 4) {
                echo json_encode(['status' => 'success', 'message' => 'User set to default Member role.']);
                exit();
            }

            // Super Admin (1) and Executive Member (5) are always global (group_id = 0)
            if ($role_id == 1 || $role_id == 5) {
                $group_id = 0;
            }

            // Insert single role mapping
            $ins_sql = "INSERT INTO tbl_user_group_roles (login_id, group_id, role_id, assigned_by) VALUES (?, ?, ?, ?)";
            app_exec_nonquery($ins_sql, [$target_login_id, $group_id, $role_id, $login_id], "iiii");

            echo json_encode(['status' => 'success', 'message' => 'Role permission updated successfully.']);
            exit();
        } catch (Throwable $e) {
            echo json_encode(['status' => 'error', 'message' => $e->getMessage()]);
            exit();
        }
    }

    // 5. Delete User Role Assignment
    if ($action == "delete_assignment") {
        try {
            $id = (int)$_POST['id'];
            if ($id > 0) {
                $del_sql = "DELETE FROM tbl_user_group_roles WHERE id = ?";
                app_exec_nonquery($del_sql, [$id], "i");
                echo json_encode(['status' => 'success', 'message' => 'Role assignment removed.']);
            } else {
                echo json_encode(['status' => 'error', 'message' => 'Invalid ID']);
            }
            exit();
        } catch (Throwable $e) {
            echo json_encode(['status' => 'error', 'message' => $e->getMessage()]);
            exit();
        }
    }
}
?>
