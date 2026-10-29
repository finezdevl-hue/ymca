<?php
require_once __DIR__ . '/api_bootstrap.php';
require_admin();

include_once __DIR__ . '/../../app_common/db_connect.php';
include_once __DIR__ . '/../../app_pagination/pagination.php';

if (empty($_SESSION['login_id'])) {
    http_response_code(401);
    echo json_encode(["status" => "error", "message" => "Unauthorized"]);
    exit;
}

$login_id = (int)$_SESSION['login_id'];
$is_admin = isSuperAdmin($login_id) || isGroupAdmin($login_id);

if (!$is_admin) {
    http_response_code(403);
    echo json_encode(["status" => "error", "message" => "Access denied"]);
    exit;
}

if (isset($_POST['action']) && !empty($_POST['action'])) {
    $action = $_POST['action'];

    // 1. Load Groups for dropdown
    if ($action == "load_groups") {
        try {
            $allowed = getUserAllowedGroupIds($login_id);
            if (in_array('ALL', $allowed, true)) {
                $sql = "SELECT id, name FROM tbl_groups WHERE status = 1 ORDER BY name ASC";
                $result = app_exec_query($sql);
            } else {
                if (empty($allowed)) {
                    echo json_encode([[]]);
                    exit();
                }
                $placeholders = implode(',', array_fill(0, count($allowed), '?'));
                $sql = "SELECT id, name FROM tbl_groups WHERE status = 1 AND id IN ($placeholders) ORDER BY name ASC";
                $result = app_exec_getresult($sql, $allowed, str_repeat('i', count($allowed)));
            }

            $qrydata = [];
            if ($result && $result->num_rows > 0) {
                while ($row = $result->fetch_assoc()) {
                    $qrydata[] = $row;
                }
            }
            echo json_encode([$qrydata]);
            exit();
        } catch (Throwable $e) {
            http_response_code(500);
            echo json_encode(["status" => "error", "message" => $e->getMessage()]);
            exit();
        }
    }

    // 2. Load Fee Rules (with pagination & group details)
    if ($action == "load_data") {
        try {
            $rowsPerPage = 10;
            $current_page = isset($_POST['page']) && (int)$_POST['page'] > 0 ? (int)$_POST['page'] : 1;
            $offset = ($current_page - 1) * $rowsPerPage;

            $today = date('Y-m-d');
            $allowed = getUserAllowedGroupIds($login_id);

            $where_clauses = ["1=1"];
            $params = [];
            $types = "";

            if (!in_array('ALL', $allowed, true)) {
                if (!empty($allowed)) {
                    $placeholders = implode(',', array_fill(0, count($allowed), '?'));
                    $where_clauses[] = "(f.group_id = 0 OR f.group_id IN ($placeholders))";
                    foreach ($allowed as $gid) { $params[] = (int)$gid; }
                    $types .= str_repeat('i', count($allowed));
                }
            }

            $where_sql = implode(" AND ", $where_clauses);

            // Total count
            $sql_count = "SELECT COUNT(*) AS total FROM tbl_fee_master f WHERE $where_sql";
            $res_count = !empty($params) ? app_exec_getresult($sql_count, $params, $types) : app_exec_query($sql_count);
            $totalRows = ($res_count && ($row_c = $res_count->fetch_assoc())) ? (int)$row_c['total'] : 0;

            // Fetch rows
            $sql_data = "SELECT f.id, f.from_date, f.to_date, f.fee, f.fee_method, f.group_id,
                                COALESCE(g.name, 'All Groups (General)') AS group_name
                         FROM tbl_fee_master f
                         LEFT JOIN tbl_groups g ON f.group_id = g.id
                         WHERE $where_sql
                         ORDER BY f.from_date DESC, f.id DESC
                         LIMIT $offset, $rowsPerPage";

            $res_data = !empty($params) ? app_exec_getresult($sql_data, $params, $types) : app_exec_query($sql_data);

            // Active rules determination: for each group, the highest from_date <= today is the currently active rule
            $active_rule_ids = [];
            $groups_res = app_exec_query("SELECT DISTINCT group_id FROM tbl_fee_master");
            if ($groups_res) {
                while ($grp_row = $groups_res->fetch_assoc()) {
                    $gid = (int)$grp_row['group_id'];
                    $act_res = app_exec_getresult("SELECT id FROM tbl_fee_master WHERE group_id = ? AND from_date <= ? ORDER BY from_date DESC, id DESC LIMIT 1", [$gid, $today], "is");
                    if ($act_res && $act_row = $act_res->fetch_assoc()) {
                        $active_rule_ids[] = (int)$act_row['id'];
                    }
                }
            }

            $qrydata = [];
            if ($res_data && $res_data->num_rows > 0) {
                while ($row = $res_data->fetch_assoc()) {
                    $row_id = (int)$row['id'];
                    $row['fee_method'] = !empty($row['fee_method']) ? $row['fee_method'] : 'fixed';
                    
                    if ($row['fee_method'] === 'shuttle_cost') {
                        $row['method_label'] = 'Shuttle Cost (Pro-rata)';
                    } elseif ($row['fee_method'] === 'shuttle_equal') {
                        $row['method_label'] = 'Shuttle Cost (Equal Split)';
                    } else {
                        $row['method_label'] = 'Fixed Monthly Fee';
                    }

                    if (in_array($row_id, $active_rule_ids, true)) {
                        $row['status'] = 'active';
                    } elseif ($today < $row['from_date']) {
                        $row['status'] = 'upcoming';
                    } else {
                        $row['status'] = 'replaced';
                    }
                    if ($row['to_date'] >= '2035-01-01') {
                        $row['to_date_display'] = 'Ongoing';
                    } else {
                        $row['to_date_display'] = $row['to_date'];
                    }
                    $qrydata[] = $row;
                }
            }

            // Summary active fee
            $current_active_fee = getMonthlyFeeAmount(0, $today);

            $pagination = ["total_rows" => $totalRows, "active_fee" => $current_active_fee];
            echo json_encode([$pagination, $qrydata]);
            exit();
        } catch (Throwable $e) {
            http_response_code(500);
            echo json_encode(["status" => "error", "message" => $e->getMessage()]);
            exit();
        }
    }

    // 3. Save New Fee Rule
    if ($action == "save_new_fees") {
        try {
            $from_date = trim($_POST['from_date'] ?? '');
            if (empty($from_date)) {
                $from_date = date('Y-m-01');
            }
            $to_date = trim($_POST['to_date'] ?? '');
            if (empty($to_date) || $to_date >= '2035-01-01') {
                $to_date = '2099-12-31';
            }
            $fees = (float)($_POST['fees'] ?? 0);
            $group_id = isset($_POST['group_id']) ? (int)$_POST['group_id'] : 0;
            $fee_method = trim($_POST['fee_method'] ?? 'fixed');
            if (!in_array($fee_method, ['fixed', 'shuttle_cost', 'shuttle_equal'], true)) {
                $fee_method = 'fixed';
            }

            if ($fee_method === 'fixed' && $fees <= 0) {
                http_response_code(400);
                echo json_encode(["status" => "error", "message" => "Please enter a valid monthly fee amount greater than 0."]);
                exit();
            }

            if ($fee_method !== 'fixed' && $fees < 0) {
                http_response_code(400);
                echo json_encode(["status" => "error", "message" => "Base fee amount cannot be negative."]);
                exit();
            }

            // Scoping check for Group Admins
            if (!isSuperAdmin($login_id)) {
                $allowed_gids = getUserAllowedGroupIds($login_id);
                if (!in_array('ALL', $allowed_gids, true)) {
                    if ($group_id === 0 && !empty($allowed_gids)) {
                        $group_id = (int)$allowed_gids[0]; // Default to assigned group
                    } else if (!in_array($group_id, $allowed_gids, true)) {
                        http_response_code(403);
                        echo json_encode(["status" => "error", "message" => "You are only permitted to configure fees for your assigned group."]);
                        exit();
                    }
                }
            }

            // Auto-close any previous ongoing rule for this group to day before the new from_date
            $prev_day = date('Y-m-d', strtotime($from_date . ' - 1 day'));
            $sql_close_prev = "UPDATE tbl_fee_master SET to_date = ? WHERE group_id = ? AND to_date > ? AND from_date < ?";
            app_exec_nonquery($sql_close_prev, [$prev_day, $group_id, $prev_day, $from_date], "siss");

            $sql = "INSERT INTO tbl_fee_master (from_date, to_date, fee, fee_method, group_id) VALUES (?, ?, ?, ?, ?)";
            app_exec_nonquery($sql, [$from_date, $to_date, $fees, $fee_method, $group_id], "ssdss");

            $method_display = ($fee_method === 'shuttle_cost') ? "Shuttle Cost (Pro-rata)" : (($fee_method === 'shuttle_equal') ? "Shuttle Cost (Equal Split)" : "₹$fees");
            echo json_encode(["status" => "success", "message" => "Monthly fee rule ($method_display) set successfully (Active from $from_date onwards)."]);
            exit();
        } catch (Throwable $e) {
            http_response_code(500);
            echo json_encode(["status" => "error", "message" => "Failed to save: " . $e->getMessage()]);
            exit();
        }
    }

    // 4. Update Existing Fee Rule
    if ($action == "save_fees") {
        try {
            $id = (int)($_POST['id'] ?? 0);
            $from_date = trim($_POST['from_date'] ?? '');
            if (empty($from_date)) {
                $from_date = date('Y-m-01');
            }
            $to_date = trim($_POST['to_date'] ?? '');
            if (empty($to_date) || $to_date >= '2035-01-01') {
                $to_date = '2099-12-31';
            }
            $fees = (float)($_POST['fees'] ?? 0);
            $group_id = isset($_POST['group_id']) ? (int)$_POST['group_id'] : 0;
            $fee_method = trim($_POST['fee_method'] ?? 'fixed');
            if (!in_array($fee_method, ['fixed', 'shuttle_cost', 'shuttle_equal'], true)) {
                $fee_method = 'fixed';
            }

            if ($id <= 0) {
                http_response_code(400);
                echo json_encode(["status" => "error", "message" => "Invalid fee record ID."]);
                exit();
            }

            if ($fee_method === 'fixed' && $fees <= 0) {
                http_response_code(400);
                echo json_encode(["status" => "error", "message" => "Please enter a valid monthly fee amount greater than 0."]);
                exit();
            }

            if ($fee_method !== 'fixed' && $fees < 0) {
                http_response_code(400);
                echo json_encode(["status" => "error", "message" => "Base fee amount cannot be negative."]);
                exit();
            }

            // Scoping check for Group Admins
            if (!isSuperAdmin($login_id)) {
                $allowed_gids = getUserAllowedGroupIds($login_id);
                if (!in_array('ALL', $allowed_gids, true)) {
                    if ($group_id === 0 && !empty($allowed_gids)) {
                        $group_id = (int)$allowed_gids[0]; // Default to assigned group
                    } else if (!in_array($group_id, $allowed_gids, true)) {
                        http_response_code(403);
                        echo json_encode(["status" => "error", "message" => "You are only permitted to configure fees for your assigned group."]);
                        exit();
                    }
                }
            }

            $sql = "UPDATE tbl_fee_master SET from_date = ?, to_date = ?, fee = ?, fee_method = ?, group_id = ? WHERE id = ?";
            app_exec_nonquery($sql, [$from_date, $to_date, $fees, $fee_method, $group_id, $id], "ssdssi");

            echo json_encode(["status" => "success", "message" => "Monthly fee rule updated successfully."]);
            exit();
        } catch (Throwable $e) {
            http_response_code(500);
            echo json_encode(["status" => "error", "message" => "Failed to update: " . $e->getMessage()]);
            exit();
        }
    }

    // 5. Delete Fee Rule
    if ($action == "delete_fee_details") {
        try {
            $id = (int)($_POST['id'] ?? 0);
            if ($id <= 0) {
                http_response_code(400);
                echo json_encode(["status" => "error", "message" => "Invalid fee record ID."]);
                exit();
            }

            // Ensure at least one default fee rule remains
            $cnt_res = app_exec_query("SELECT COUNT(*) AS total FROM tbl_fee_master");
            $total_cnt = ($cnt_res && ($row_c = $cnt_res->fetch_assoc())) ? (int)$row_c['total'] : 0;
            if ($total_cnt <= 1) {
                http_response_code(400);
                echo json_encode(["status" => "error", "message" => "At least one monthly fee rule must remain configured."]);
                exit();
            }

            $sql = "DELETE FROM tbl_fee_master WHERE id = ?";
            app_exec_nonquery($sql, [$id], "i");

            echo json_encode(["status" => "success", "message" => "Fee rule deleted successfully."]);
            exit();
        } catch (Throwable $e) {
            http_response_code(500);
            echo json_encode(["status" => "error", "message" => "Failed to delete: " . $e->getMessage()]);
            exit();
        }
    }
}