<?php
require_once __DIR__ . '/api_bootstrap.php';
require_attendance_master();

include_once __DIR__ . '/../../app_pagination/pagination.php';

header('Content-Type: application/json');

if (!isset($_SESSION['login_id'])) {
    http_response_code(401);
    echo json_encode(['error' => 'Unauthorized']);
    exit();
}

$login_id = (int)$_SESSION['login_id'];
$allowed_groups = getUserAllowedGroupIds($login_id);
$is_all_groups = in_array('ALL', $allowed_groups, true);

$in_clause = "0";
if (!$is_all_groups && !empty($allowed_groups)) {
    $in_clause = implode(',', array_map('intval', $allowed_groups));
}

if (!function_exists('getShuttleReadingFeeLockStatus')) {
    function getShuttleReadingFeeLockStatus($reading_id_or_data) {
        if (is_array($reading_id_or_data)) {
            $reading = $reading_id_or_data;
        } else {
            $id = (int)$reading_id_or_data;
            if ($id <= 0) return ['is_locked' => false, 'reason' => '', 'month' => ''];
            $res = app_exec_getresult("SELECT id, reading_date, group_id, brand_name, start_shuttle, end_shuttle, shuttles_used, per_shuttle_price, total_amount FROM tbl_shuttle_cock_readings WHERE id = ? LIMIT 1", [$id], "i");
            if (!$res || !$reading = $res->fetch_assoc()) {
                return ['is_locked' => false, 'reason' => '', 'month' => ''];
            }
        }

        $reading_date = $reading['reading_date'] ?? date('Y-m-d');
        $group_id = (int)($reading['group_id'] ?? 0);
        $month = substr($reading_date, 0, 7);
        if (empty($month) || !preg_match('/^\d{4}-\d{2}$/', $month)) {
            return ['is_locked' => false, 'reason' => '', 'month' => ''];
        }

        $from_date = $month . '-01';
        $to_date = date('Y-m-t', strtotime($from_date));
        $month_label = date('F Y', strtotime($from_date));

        // 1. Is month fixed/locked?
        if (function_exists('isMonthFixed') && isMonthFixed($month)) {
            return [
                'is_locked' => true,
                'reason' => "Monthly attendance and accounts for {$month_label} are locked/fixed. Shuttle readings cannot be modified.",
                'month' => $month_label,
                'reading' => $reading
            ];
        }
        $fx_res = app_exec_getresult("SELECT id FROM tbl_fixed_months WHERE fixed_month = ? LIMIT 1", [$month], "s");
        if ($fx_res && $fx_res->num_rows > 0) {
            return [
                'is_locked' => true,
                'reason' => "Monthly attendance and accounts for {$month_label} are locked/fixed. Shuttle readings cannot be modified.",
                'month' => $month_label,
                'reading' => $reading
            ];
        }

        // 2. Check if monthly fee receivables have been generated for this month
        $gid_clause = ($group_id > 0) 
            ? " AND (ma.member_id IN (SELECT member_id FROM tbl_group_member_map WHERE group_id = {$group_id}))" 
            : "";
        $ma_res = app_exec_getresult("SELECT COUNT(ma.id) as cnt FROM tbl_monthly_attendance ma WHERE ma.from_date = ? AND ma.to_date = ? AND ma.isreceiveble = 1{$gid_clause}", [$from_date, $to_date], "ss");
        $has_ma_fees = ($ma_res && (int)$ma_res->fetch_assoc()['cnt'] > 0);

        $mr_res = app_exec_getresult("SELECT COUNT(id) as cnt FROM tbl_member_recievable WHERE (discription LIKE ? OR discription LIKE ?) AND fees > 0", ["%{$month_label}%", "%{$month}%"], "ss");
        $has_mr_fees = ($mr_res && (int)$mr_res->fetch_assoc()['cnt'] > 0);

        if ($has_ma_fees || $has_mr_fees) {
            return [
                'is_locked' => true,
                'reason' => "Monthly fee for {$month_label} has been generated. Shuttle readings for this month cannot be modified.",
                'month' => $month_label,
                'reading' => $reading
            ];
        }

        return ['is_locked' => false, 'reason' => '', 'month' => ''];
    }
}

$action = $_POST['action'] ?? $_GET['action'] ?? '';

if ($action == 'get_last_reading') {
    try {
        $group_id = (int)($_POST['group_id'] ?? 0);
        
        $sql = "SELECT end_shuttle FROM tbl_shuttle_cock_readings WHERE group_id = ? ORDER BY reading_date DESC, id DESC LIMIT 1";
        $res = app_exec_getresult($sql, [$group_id], "i");
        $last_end = 0;
        if ($res && $r = $res->fetch_assoc()) {
            $last_end = (int)$r['end_shuttle'];
        } else {
            // Fallback to global last end_shuttle if group has no prior readings
            $res2 = app_exec_query("SELECT end_shuttle FROM tbl_shuttle_cock_readings ORDER BY reading_date DESC, id DESC LIMIT 1");
            if ($res2 && $r2 = $res2->fetch_assoc()) {
                $last_end = (int)$r2['end_shuttle'];
            }
        }

        // Fetch latest unit price from stock inward
        $price_res = app_exec_query("SELECT per_shuttle_price, brand_name FROM tbl_shuttle_cock_stock_in ORDER BY purchase_date DESC, id DESC LIMIT 1");
        $latest_price = 0.00;
        $brand_name = 'Standard';
        if ($price_res && $pr = $price_res->fetch_assoc()) {
            $latest_price = (float)$pr['per_shuttle_price'];
            $brand_name = $pr['brand_name'] ?: 'Standard';
        }

        echo json_encode([
            'status' => 'success',
            'last_end_shuttle' => $last_end,
            'latest_price' => $latest_price,
            'brand_name' => $brand_name
        ]);
        exit();
    } catch (Exception $e) {
        http_response_code(500);
        echo json_encode(['status' => 'error', 'message' => $e->getMessage()]);
        exit();
    }
}

if ($action == 'save_reading') {
    try {
        $id = isset($_POST['id']) ? (int)$_POST['id'] : 0;
        $reading_date = trim($_POST['reading_date'] ?? date('Y-m-d'));
        $group_id = (int)($_POST['group_id'] ?? 0);
        $brand_name = trim($_POST['brand_name'] ?? 'Standard');
        $start_shuttle = (int)($_POST['start_shuttle'] ?? 0);
        $end_shuttle = (int)($_POST['end_shuttle'] ?? 0);
        $per_shuttle_price = (float)($_POST['per_shuttle_price'] ?? 0.00);
        $notes = trim($_POST['notes'] ?? '');
        $created_by = (int)$_SESSION['login_id'];

        if ($id === 0) {
            $chk_exist = app_exec_getresult("SELECT id, start_shuttle FROM tbl_shuttle_cock_readings WHERE group_id = ? AND reading_date = ? LIMIT 1", [$group_id, $reading_date], "is");
            if ($chk_exist && $ex = $chk_exist->fetch_assoc()) {
                $id = (int)$ex['id'];
                if ($start_shuttle == 0 && (int)$ex['start_shuttle'] > 0) {
                    $start_shuttle = (int)$ex['start_shuttle'];
                    $end_shuttle = $start_shuttle + $shuttles_used;
                }
            }
        }

        // Fee lock check for readings
        if ($id === 0) {
            $lock = getShuttleReadingFeeLockStatus(['reading_date' => $reading_date, 'group_id' => $group_id]);
            if ($lock['is_locked']) {
                echo json_encode(['status' => 'error', 'message' => $lock['reason']]);
                exit();
            }
        } else {
            $lock = getShuttleReadingFeeLockStatus($id);
            if ($lock['is_locked']) {
                $orig_r = $lock['reading'] ?? [];
                if ($orig_r['reading_date'] !== $reading_date || 
                    (int)$orig_r['group_id'] !== $group_id ||
                    (int)$orig_r['start_shuttle'] !== $start_shuttle ||
                    (int)$orig_r['end_shuttle'] !== $end_shuttle ||
                    abs((float)$orig_r['per_shuttle_price'] - $per_shuttle_price) > 0.001) {
                    echo json_encode(['status' => 'error', 'message' => $lock['reason'] . ' Reading numbers, date, and price cannot be altered.']);
                    exit();
                }
            }
        }

        if ($per_shuttle_price <= 0.00) {
            $price_res = app_exec_query("SELECT per_shuttle_price, brand_name FROM tbl_shuttle_cock_stock_in ORDER BY purchase_date DESC, id DESC LIMIT 1");
            if ($price_res && $pr = $price_res->fetch_assoc()) {
                $per_shuttle_price = (float)$pr['per_shuttle_price'];
                if (empty($brand_name) || $brand_name === 'Standard') {
                    $brand_name = $pr['brand_name'] ?: 'Standard';
                }
            }
        }

        $shuttles_used = isset($_POST['shuttles_used']) && $_POST['shuttles_used'] !== '' ? (int)$_POST['shuttles_used'] : 0;
        if ($shuttles_used > 0 && $end_shuttle <= $start_shuttle) {
            $end_shuttle = $start_shuttle + $shuttles_used;
        } else {
            if ($end_shuttle < $start_shuttle) {
                echo json_encode(['status' => 'error', 'message' => 'End reading cannot be less than start reading']);
                exit();
            }
            $shuttles_used = max(0, $end_shuttle - $start_shuttle);
        }

        if ($shuttles_used > 0) {
            $sum_in_res = app_exec_query("SELECT SUM(total_shuttles) as tot_in FROM tbl_shuttle_cock_stock_in");
            $total_in = (int)($sum_in_res ? $sum_in_res->fetch_assoc()['tot_in'] : 0);

            $where_id = ($id > 0) ? " WHERE id != $id" : "";
            $sum_used_res = app_exec_query("SELECT SUM(shuttles_used) as tot_used FROM tbl_shuttle_cock_readings{$where_id}");
            $total_used = (int)($sum_used_res ? $sum_used_res->fetch_assoc()['tot_used'] : 0);

            $stock_remaining = max(0, $total_in - $total_used);

            if ($stock_remaining <= 0) {
                echo json_encode(['status' => 'error', 'message' => 'Out of Stock! Available shuttle cock inventory is 0. Please add inward stock first.']);
                exit();
            }
            if ($shuttles_used > $stock_remaining) {
                echo json_encode(['status' => 'error', 'message' => "Insufficient Stock! Only {$stock_remaining} shuttles remaining in inventory."]);
                exit();
            }
        }

        $total_amount = $shuttles_used * $per_shuttle_price;

        if ($id === 0) {
            $sql = "INSERT INTO tbl_shuttle_cock_readings 
                    (reading_date, group_id, brand_name, start_shuttle, end_shuttle, shuttles_used, per_shuttle_price, total_amount, notes, created_by) 
                    VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
            $params = [
                $reading_date,
                $group_id,
                $brand_name,
                $start_shuttle,
                $end_shuttle,
                $shuttles_used,
                $per_shuttle_price,
                $total_amount,
                $notes,
                $created_by
            ];
            $types = "sisiiiddsi";
        } else {
            $updated_by = (int)($_SESSION['login_id'] ?? 1);
            $sql = "UPDATE tbl_shuttle_cock_readings 
                    SET reading_date = ?, group_id = ?, brand_name = ?, start_shuttle = ?, end_shuttle = ?, shuttles_used = ?, per_shuttle_price = ?, total_amount = ?, notes = ?, updated_by = ?, updated_at = NOW() 
                    WHERE id = ?";
            $params = [
                $reading_date,
                $group_id,
                $brand_name,
                $start_shuttle,
                $end_shuttle,
                $shuttles_used,
                $per_shuttle_price,
                $total_amount,
                $notes,
                $updated_by,
                $id
            ];
            $types = "sisiiiddsii";
        }

        app_exec_nonquery($sql, $params, $types);

        echo json_encode(['status' => 'success', 'message' => 'Shuttle reading saved successfully']);
        exit();
    } catch (Exception $e) {
        http_response_code(500);
        echo json_encode(['status' => 'error', 'message' => 'Database error: ' . $e->getMessage()]);
        exit();
    }
}

if ($action == 'delete_reading') {
    try {
        $id = (int)($_POST['id'] ?? 0);
        if ($id > 0) {
            $lock = getShuttleReadingFeeLockStatus($id);
            if ($lock['is_locked']) {
                echo json_encode(['status' => 'error', 'message' => $lock['reason'] . ' Reading record cannot be deleted.']);
                exit();
            }

            $sql = "DELETE FROM tbl_shuttle_cock_readings WHERE id = ?";
            app_exec_nonquery($sql, [$id], "i");
            echo json_encode(['status' => 'success', 'message' => 'Reading record deleted successfully']);
        } else {
            echo json_encode(['status' => 'error', 'message' => 'Invalid ID']);
        }
        exit();
    } catch (Exception $e) {
        http_response_code(500);
        echo json_encode(['status' => 'error', 'message' => $e->getMessage()]);
        exit();
    }
}

if ($action == 'load_data') {
    try {
        $db = new Database();
        $conn = $db->getConnection();
        $rowsPerPage = 8;
        $current_page = (int)($_POST['page'] ?? 1);
        if ($current_page < 1) $current_page = 1;
        $offset = ($current_page - 1) * $rowsPerPage;
        $selected_month = trim($_POST['selected_month'] ?? 'all');
        $is_print = (!empty($_POST['is_print']) && $_POST['is_print'] == '1') || (!empty($_POST['no_limit']) && $_POST['no_limit'] == '1');

        $where_conds = [];
        if (!empty($selected_month) && $selected_month !== 'all') {
            $safe_month = $conn ? $conn->real_escape_string($selected_month) : addslashes($selected_month);
            $where_conds[] = "DATE_FORMAT(r.reading_date, '%Y-%m') = '" . $safe_month . "'";
        }
        if (!$is_all_groups) {
            $where_conds[] = "r.group_id IN ($in_clause)";
        }
        $where_clause = !empty($where_conds) ? " WHERE " . implode(" AND ", $where_conds) : "";

        // Fetch Total Rows
        $count_sql = "SELECT COUNT(*) as cnt FROM tbl_shuttle_cock_readings r" . $where_clause;
        $count_res = app_exec_query($count_sql);
        $total_rows = 0;
        if ($count_res && $row = $count_res->fetch_assoc()) {
            $total_rows = (int)$row['cnt'];
        }

        // Fetch Rows with Group Name (Paginated if normal view, All rows if printing)
        $limit_clause = $is_print ? "" : " LIMIT $offset, $rowsPerPage";
        $sql = "SELECT r.id, r.reading_date, r.group_id, r.brand_name, r.start_shuttle, r.end_shuttle, r.shuttles_used, r.per_shuttle_price, r.total_amount, r.notes, g.name as group_name 
                FROM tbl_shuttle_cock_readings r 
                LEFT JOIN tbl_groups g ON r.group_id = g.id 
                $where_clause
                ORDER BY r.reading_date DESC, r.id DESC" . $limit_clause;
        $result = app_exec_query($sql);
        $data = [];
        if ($result) {
            while ($row = $result->fetch_assoc()) {
                if (empty($row['group_name'])) {
                    $row['group_name'] = 'General / All Groups';
                }
                $data[] = $row;
            }
        }

        // Fetch List of Active Groups for dropdown
        if (!$is_all_groups) {
            $groups_sql = "SELECT id, name as group_name FROM tbl_groups WHERE id IN ($in_clause) ORDER BY name ASC";
        } else {
            $groups_sql = "SELECT id, name as group_name FROM tbl_groups ORDER BY name ASC";
        }
        $groups_res = app_exec_query($groups_sql);
        $groups = [];
        if ($groups_res) {
            while ($g = $groups_res->fetch_assoc()) {
                $groups[] = $g;
            }
        }

        // Read overall stock balance info
        if (!$is_all_groups) {
            $sum_in_res = app_exec_query("SELECT SUM(total_shuttles) as tot_in FROM tbl_shuttle_cock_stock_in WHERE (group_id IN ($in_clause) OR group_id = 0)");
            $overall_used_res = app_exec_query("SELECT SUM(shuttles_used) as tot_used FROM tbl_shuttle_cock_readings WHERE group_id IN ($in_clause)");
            
            $r_conds = ["group_id IN ($in_clause)"];
            if (!empty($selected_month) && $selected_month !== 'all') {
                $r_conds[] = "DATE_FORMAT(reading_date, '%Y-%m') = '" . $conn->real_escape_string($selected_month) . "'";
            }
            $sum_used_sql = "SELECT SUM(shuttles_used) as tot_used, SUM(total_amount) as tot_used_cost FROM tbl_shuttle_cock_readings WHERE " . implode(" AND ", $r_conds);
        } else {
            $sum_in_res = app_exec_query("SELECT SUM(total_shuttles) as tot_in FROM tbl_shuttle_cock_stock_in");
            $overall_used_res = app_exec_query("SELECT SUM(shuttles_used) as tot_used FROM tbl_shuttle_cock_readings");
            
            $where_m_str = (!empty($selected_month) && $selected_month !== 'all') ? " WHERE DATE_FORMAT(reading_date, '%Y-%m') = '" . $conn->real_escape_string($selected_month) . "'" : "";
            $sum_used_sql = "SELECT SUM(shuttles_used) as tot_used, SUM(total_amount) as tot_used_cost FROM tbl_shuttle_cock_readings" . $where_m_str;
        }

        $total_in = (int)($sum_in_res ? $sum_in_res->fetch_assoc()['tot_in'] : 0);
        $sum_used_res = app_exec_query($sum_used_sql);
        $sum_row = $sum_used_res ? $sum_used_res->fetch_assoc() : [];
        $total_used = (int)($sum_row['tot_used'] ?? 0);
        $total_used_cost = (float)($sum_row['tot_used_cost'] ?? 0.00);

        $overall_used = (int)($overall_used_res ? $overall_used_res->fetch_assoc()['tot_used'] : 0);
        $stock_remaining = max(0, $total_in - $overall_used);

        // Fetch Available Months List for Dropdown Filter
        $m_where = "";
        if (!$is_all_groups) {
            $m_where = " WHERE group_id IN ($in_clause) ";
        }
        $months_res = app_exec_query("SELECT DISTINCT DATE_FORMAT(reading_date, '%Y-%m') as month_key, DATE_FORMAT(reading_date, '%M %Y') as month_label FROM tbl_shuttle_cock_readings $m_where ORDER BY month_key DESC");
        $available_months = [];
        if ($months_res) {
            while ($m = $months_res->fetch_assoc()) {
                $available_months[] = $m;
            }
        }

        // Fetch Month-Wise Consumption Breakdown Table Data
        $mb_where = "";
        if (!$is_all_groups) {
            $mb_where = " WHERE r.group_id IN ($in_clause) ";
        }
        $mb_sql = "SELECT 
                    DATE_FORMAT(r.reading_date, '%Y-%m') as month_key,
                    DATE_FORMAT(r.reading_date, '%M %Y') as month_label,
                    COUNT(r.id) as session_count,
                    SUM(r.shuttles_used) as shuttles_used,
                    SUM(r.total_amount) as total_cost,
                    AVG(r.per_shuttle_price) as avg_price
                FROM tbl_shuttle_cock_readings r
                $mb_where
                GROUP BY DATE_FORMAT(r.reading_date, '%Y-%m'), DATE_FORMAT(r.reading_date, '%M %Y')
                ORDER BY month_key DESC";
        $mb_res = app_exec_query($mb_sql);
        $month_breakdown = [];
        if ($mb_res) {
            while ($mb = $mb_res->fetch_assoc()) {
                $month_breakdown[] = $mb;
            }
        }

        $summary = [
            'total_rows' => $total_rows,
            'stock_remaining' => $stock_remaining,
            'total_used' => $total_used,
            'total_used_cost' => $total_used_cost,
            'selected_month' => $selected_month,
            'available_months' => $available_months,
            'month_breakdown' => $month_breakdown,
            'groups' => $groups,
            'is_all_groups' => $is_all_groups
        ];

        echo json_encode([$summary, $data]);
        exit();
    } catch (Exception $e) {
        http_response_code(500);
        echo json_encode(['status' => 'error', 'message' => $e->getMessage()]);
        exit();
    }
}
