<?php
session_start();
include_once __DIR__ . '/../../app_common/db_connect.php';
include_once __DIR__ . '/../../app_common/auth_helper.php';

$login_id = !empty($_SESSION['login_id']) ? (int)$_SESSION['login_id'] : 0;
if (!isSuperAdmin($login_id)) {
    http_response_code(403);
    echo json_encode(['status' => 'error', 'message' => 'Unauthorized access. Only Super Admin can manage roles.']);
    exit();
}

if (isset($_POST['action']) && !empty($_POST['action'])) {
    $action = $_POST['action'];

    // 1. Fetch available roles
    if ($action == "load_roles") {
        try {
            $res = app_exec_query("SELECT role_id, role_name, description FROM tbl_roles ORDER BY role_id ASC");
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

    // 4. Save User Role Assignment
    if ($action == "save_assignment") {
        try {
            $target_login_id = (int)$_POST['target_login_id'];
            $role_id = (int)$_POST['role_id'];
            $group_id = (int)$_POST['group_id']; // 0 for All Groups

            if (empty($target_login_id) || empty($role_id)) {
                echo json_encode(['status' => 'error', 'message' => 'Missing target user or role.']);
                exit();
            }

            // Remove existing mapping for same user & group if exists
            $del_sql = "DELETE FROM tbl_user_group_roles WHERE login_id = ? AND group_id = ?";
            app_exec_nonquery($del_sql, [$target_login_id, $group_id], "ii");

            // Insert new mapping
            $ins_sql = "INSERT INTO tbl_user_group_roles (login_id, group_id, role_id, assigned_by) VALUES (?, ?, ?, ?)";
            app_exec_nonquery($ins_sql, [$target_login_id, $group_id, $role_id, $login_id], "iiii");

            echo json_encode(['status' => 'success', 'message' => 'Role assignment saved successfully.']);
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
