<?php
require_once __DIR__ . '/api_bootstrap.php';
require_admin();

include_once __DIR__ . '/../../app_pagination/pagination.php';

$db = new Database();
$conn = $db->getConnection();

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

$action = $_POST['action'] ?? $_GET['action'] ?? '';

if (!function_exists('getShuttlePurchaseFeeLockStatus')) {
    function getShuttlePurchaseFeeLockStatus($stock_id_or_data) {
        if (is_array($stock_id_or_data)) {
            $stock = $stock_id_or_data;
        } else {
            $id = (int)$stock_id_or_data;
            if ($id <= 0) return ['is_locked' => false, 'reason' => '', 'month' => ''];
            $res = app_exec_getresult("SELECT id, purchase_date, group_id, total_cost, box_price, per_shuttle_price, no_of_boxes, shuttles_per_box, total_shuttles FROM tbl_shuttle_cock_stock_in WHERE id = ? LIMIT 1", [$id], "i");
            if (!$res || !$stock = $res->fetch_assoc()) {
                return ['is_locked' => false, 'reason' => '', 'month' => ''];
            }
        }

        $purchase_date = $stock['purchase_date'] ?? date('Y-m-d');
        $group_id = (int)($stock['group_id'] ?? 0);
        $month = substr($purchase_date, 0, 7);
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
                'reason' => "Monthly attendance and accounts for {$month_label} are locked/fixed. Shuttle purchase amount cannot be changed.",
                'month' => $month_label,
                'stock' => $stock
            ];
        }
        $fx_res = app_exec_getresult("SELECT id FROM tbl_fixed_months WHERE fixed_month = ? LIMIT 1", [$month], "s");
        if ($fx_res && $fx_res->num_rows > 0) {
            return [
                'is_locked' => true,
                'reason' => "Monthly attendance and accounts for {$month_label} are locked/fixed. Shuttle purchase amount cannot be changed.",
                'month' => $month_label,
                'stock' => $stock
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
                'reason' => "Monthly fee for {$month_label} has been generated. The shuttle purchase amount cannot be changed.",
                'month' => $month_label,
                'stock' => $stock
            ];
        }

        return ['is_locked' => false, 'reason' => '', 'month' => ''];
    }
}

if ($action == 'save_stock_in') {
    try {
        $id = isset($_POST['id']) ? (int)$_POST['id'] : 0;
        $purchase_date = trim($_POST['purchase_date'] ?? date('Y-m-d'));
        $group_id = (int)($_POST['group_id'] ?? 0);

        if (!$is_all_groups) {
            if ($group_id > 0 && !in_array($group_id, array_map('intval', $allowed_groups), true)) {
                echo json_encode(['status' => 'error', 'message' => 'Unauthorized group selection']);
                exit();
            }
            if ($group_id == 0 && !empty($allowed_groups)) {
                $group_id = (int)$allowed_groups[0];
            }
        }

        $brand_name = trim($_POST['brand_name'] ?? 'Standard');
        $no_of_boxes = (int)($_POST['no_of_boxes'] ?? 0);
        $shuttles_per_box = (int)($_POST['shuttles_per_box'] ?? 12);
        $total_cost = (float)($_POST['total_cost'] ?? 0.00);
        if ($total_cost <= 0.00 && isset($_POST['box_price'])) {
            $bPrice = (float)$_POST['box_price'];
            $total_cost = $no_of_boxes * $bPrice;
        }
        $supplier_name = trim($_POST['supplier_name'] ?? '');
        $invoice_no = trim($_POST['invoice_no'] ?? '');
        $notes = trim($_POST['notes'] ?? '');
        $created_by = (int)($_SESSION['login_id'] ?? 1);

        if ($no_of_boxes <= 0) {
            echo json_encode(['status' => 'error', 'message' => 'Number of boxes must be greater than 0']);
            exit();
        }

        if ($shuttles_per_box <= 0) {
            echo json_encode(['status' => 'error', 'message' => 'Shuttles per box must be greater than 0']);
            exit();
        }

        $bill_file_name = null;
        if (isset($_FILES['bill_file']) && $_FILES['bill_file']['error'] === UPLOAD_ERR_OK) {
            $upload_dir = __DIR__ . '/../../image_upload/shuttle_cock_bills/';
            if (!is_dir($upload_dir)) {
                @mkdir($upload_dir, 0755, true);
            }
            $file_tmp = $_FILES['bill_file']['tmp_name'];
            $mimeType = mime_content_type($file_tmp);
            $mimeToExt = [
                'image/jpeg' => 'jpg',
                'image/png'  => 'png',
                'image/gif'  => 'gif',
                'image/webp' => 'webp',
                'application/pdf' => 'pdf'
            ];
            if (isset($mimeToExt[$mimeType])) {
                $ext = $mimeToExt[$mimeType];
                $new_filename = 'shuttle_bill_' . time() . '_' . bin2hex(random_bytes(6)) . '.' . $ext;
                $target_path = $upload_dir . $new_filename;
                if (move_uploaded_file($file_tmp, $target_path)) {
                    $bill_file_name = $new_filename;
                    @mkdir(__DIR__ . '/../../image_upload/payments/', 0755, true);
                    @mkdir(__DIR__ . '/../../image_upload/bills/', 0755, true);
                    @copy($target_path, __DIR__ . '/../../image_upload/payments/' . $new_filename);
                    @copy($target_path, __DIR__ . '/../../image_upload/bills/' . $new_filename);
                }
            }
        }

        $total_shuttles = $no_of_boxes * $shuttles_per_box;
        $box_price = $no_of_boxes > 0 ? ($total_cost / $no_of_boxes) : 0.00;
        $per_shuttle_price = $total_shuttles > 0 ? ($total_cost / $total_shuttles) : 0.00;

        if ($id === 0) {
            $lock = getShuttlePurchaseFeeLockStatus(['purchase_date' => $purchase_date, 'group_id' => $group_id]);
            if ($lock['is_locked']) {
                echo json_encode([
                    'status' => 'error',
                    'message' => 'Cannot add shuttle purchase for ' . $lock['month'] . ': ' . $lock['reason']
                ]);
                exit();
            }

            $particuler = "Shuttle Cock Stock: " . $no_of_boxes . " boxes of " . $brand_name;
            if (!empty($supplier_name)) $particuler .= " from " . $supplier_name;
            if (!empty($invoice_no)) $particuler .= " (Inv: " . $invoice_no . ")";
            
            $head_id = 1;
            $bill_file_str = $bill_file_name ?? '';

            $sql_pay = "INSERT INTO tbl_payable (date, head, particuler, amount, iscomplete, flag, group_id, bill_photo) VALUES (?, ?, ?, ?, 1, 0, ?, ?)";
            $payable_id = (int)app_exec_getlast_id($sql_pay, [$purchase_date, $head_id, $particuler, $total_cost, $group_id, $bill_file_str], "sisdis");

            if ($payable_id > 0) {
                $sql_paid = "INSERT INTO tbl_paid (date, head, group_id, particuler, amount, payable_id, flag, transaction_type, bill_photo) VALUES (?, ?, ?, ?, ?, ?, 0, 1, ?)";
                app_exec_nonquery($sql_paid, [$purchase_date, $head_id, $group_id, $particuler, $total_cost, $payable_id, $bill_file_str], "siisdis");
            }

            $sql = "INSERT INTO tbl_shuttle_cock_stock_in 
                    (purchase_date, group_id, brand_name, no_of_boxes, shuttles_per_box, total_shuttles, box_price, per_shuttle_price, total_cost, supplier_name, invoice_no, bill_file, payable_id, notes, created_by) 
                    VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
            $params = [
                $purchase_date,
                $group_id,
                $brand_name,
                $no_of_boxes,
                $shuttles_per_box,
                $total_shuttles,
                $box_price,
                $per_shuttle_price,
                $total_cost,
                $supplier_name,
                $invoice_no,
                $bill_file_str,
                $payable_id,
                $notes,
                $created_by
            ];
            $types = "sisiiddddsssisi";
            app_exec_nonquery($sql, $params, $types);

            echo json_encode(['status' => 'success', 'message' => 'Stock inward record added successfully']);
            exit();
        } else {
            $lock = getShuttlePurchaseFeeLockStatus($id);
            if ($lock['is_locked']) {
                $orig_stock = $lock['stock'] ?? [];
                $orig_cost = (float)($orig_stock['total_cost'] ?? 0);
                $orig_boxes = (int)($orig_stock['no_of_boxes'] ?? 0);
                $orig_shuttles_box = (int)($orig_stock['shuttles_per_box'] ?? 0);
                $orig_date = $orig_stock['purchase_date'] ?? '';
                $orig_gid = (int)($orig_stock['group_id'] ?? 0);

                // If user attempted to alter any amount, quantities, purchase date, or group
                if (abs($total_cost - $orig_cost) > 0.001 || 
                    $no_of_boxes !== $orig_boxes || 
                    $shuttles_per_box !== $orig_shuttles_box || 
                    $purchase_date !== $orig_date || 
                    $group_id !== $orig_gid) {
                    echo json_encode([
                        'status' => 'error',
                        'message' => $lock['reason'] . ' Shuttle purchase amount, box quantities, purchase date, and group cannot be altered.'
                    ]);
                    exit();
                }

                // Force strictly original values for locked fields to safeguard data integrity
                $purchase_date = $orig_date;
                $group_id = $orig_gid;
                $no_of_boxes = $orig_boxes;
                $shuttles_per_box = $orig_shuttles_box;
                $total_cost = $orig_cost;
                $total_shuttles = (int)($orig_stock['total_shuttles'] ?? ($orig_boxes * $orig_shuttles_box));
                $box_price = (float)($orig_stock['box_price'] ?? ($orig_boxes > 0 ? $orig_cost / $orig_boxes : 0));
                $per_shuttle_price = (float)($orig_stock['per_shuttle_price'] ?? ($total_shuttles > 0 ? $orig_cost / $total_shuttles : 0));
            }

            $sql_get = "SELECT payable_id, bill_file FROM tbl_shuttle_cock_stock_in WHERE id = ?";
            $res_get = app_exec_getresult($sql_get, [$id], "i");
            $ex_payable_id = 0;
            $ex_bill_file = '';
            if ($res_get && $r_get = $res_get->fetch_assoc()) {
                $ex_payable_id = (int)$r_get['payable_id'];
                $ex_bill_file = $r_get['bill_file'];
            }
            $final_bill_file = $bill_file_name ? $bill_file_name : $ex_bill_file;

            $sql = "UPDATE tbl_shuttle_cock_stock_in 
                    SET purchase_date = ?, group_id = ?, brand_name = ?, no_of_boxes = ?, shuttles_per_box = ?, total_shuttles = ?, box_price = ?, per_shuttle_price = ?, total_cost = ?, supplier_name = ?, invoice_no = ?, bill_file = ?, notes = ? 
                    WHERE id = ?";
            $params = [
                $purchase_date,
                $group_id,
                $brand_name,
                $no_of_boxes,
                $shuttles_per_box,
                $total_shuttles,
                $box_price,
                $per_shuttle_price,
                $total_cost,
                $supplier_name,
                $invoice_no,
                $final_bill_file,
                $notes,
                $id
            ];
            $types = "sisiiddddssssi";
            app_exec_nonquery($sql, $params, $types);

            if ($ex_payable_id > 0) {
                $particuler = "Shuttle Cock Stock: " . $no_of_boxes . " boxes of " . $brand_name;
                if (!empty($supplier_name)) $particuler .= " from " . $supplier_name;
                if (!empty($invoice_no)) $particuler .= " (Inv: " . $invoice_no . ")";

                if ($lock['is_locked']) {
                    $sql_u_pay = "UPDATE tbl_payable SET particuler = ?, bill_photo = ? WHERE id = ?";
                    app_exec_nonquery($sql_u_pay, [$particuler, $final_bill_file, $ex_payable_id], "ssi");

                    $sql_u_paid = "UPDATE tbl_paid SET particuler = ?, bill_photo = ? WHERE payable_id = ?";
                    app_exec_nonquery($sql_u_paid, [$particuler, $final_bill_file, $ex_payable_id], "ssi");
                } else {
                    $sql_u_pay = "UPDATE tbl_payable SET date = ?, particuler = ?, amount = ?, group_id = ?, bill_photo = ? WHERE id = ?";
                    app_exec_nonquery($sql_u_pay, [$purchase_date, $particuler, $total_cost, $group_id, $final_bill_file, $ex_payable_id], "sdsisi");

                    $sql_u_paid = "UPDATE tbl_paid SET date = ?, group_id = ?, particuler = ?, amount = ?, bill_photo = ? WHERE payable_id = ?";
                    app_exec_nonquery($sql_u_paid, [$purchase_date, $group_id, $particuler, $total_cost, $final_bill_file, $ex_payable_id], "sisdsi");
                }
            }

            echo json_encode(['status' => 'success', 'message' => 'Stock record updated successfully']);
            exit();
        }
    } catch (Exception $e) {
        http_response_code(500);
        echo json_encode(['status' => 'error', 'message' => $e->getMessage()]);
        exit();
    }
}

if ($action == 'delete_stock_in') {
    try {
        $id = (int)($_POST['id'] ?? 0);
        if ($id > 0) {
            $lock = getShuttlePurchaseFeeLockStatus($id);
            if ($lock['is_locked']) {
                echo json_encode([
                    'status' => 'error',
                    'message' => $lock['reason'] . ' Shuttle purchase record cannot be deleted.'
                ]);
                exit();
            }

            $stk_res = app_exec_getresult("SELECT payable_id FROM tbl_shuttle_cock_stock_in WHERE id = ?", [$id], "i");
            if ($stk_res && $stk_row = $stk_res->fetch_assoc()) {
                $p_id = (int)$stk_row['payable_id'];
                if ($p_id > 0) {
                    app_exec_nonquery("DELETE FROM tbl_paid WHERE payable_id = ?", [$p_id], "i");
                    app_exec_nonquery("DELETE FROM tbl_payable WHERE id = ?", [$p_id], "i");
                }
            }
            app_exec_nonquery("DELETE FROM tbl_shuttle_cock_stock_in WHERE id = ?", [$id], "i");
            echo json_encode(['status' => 'success', 'message' => 'Stock record and associated accounting entries deleted successfully']);
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

if ($action == 'record_stock_payment') {
    try {
        $stock_id = (int)($_POST['stock_id'] ?? 0);
        $payable_id = (int)($_POST['payable_id'] ?? 0);
        $payment_date = trim($_POST['payment_date'] ?? date('Y-m-d'));
        $amount_paid = (float)($_POST['amount_paid'] ?? 0.00);
        $transaction_type = (int)($_POST['transaction_type'] ?? 1);
        $notes = trim($_POST['notes'] ?? '');
        $created_by = (int)$_SESSION['login_id'];

        if ($amount_paid <= 0) {
            echo json_encode(['status' => 'error', 'message' => 'Payment amount must be greater than 0']);
            exit();
        }

        if ($payable_id === 0 && $stock_id > 0) {
            $s_res = app_exec_query("SELECT * FROM tbl_shuttle_cock_stock_in WHERE id = $stock_id");
            if ($s_res && $stk = $s_res->fetch_assoc()) {
                $particuler = "Shuttle Cock Stock: " . $stk['no_of_boxes'] . " boxes of " . $stk['brand_name'];
                if (!empty($stk['supplier_name'])) $particuler .= " from " . $stk['supplier_name'];
                if (!empty($stk['invoice_no'])) $particuler .= " (Inv: " . $stk['invoice_no'] . ")";
                
                $sql_p = "INSERT INTO tbl_payable (date, head, particuler, amount, iscomplete, flag, group_id, bill_photo) VALUES (?, 1, ?, ?, 0, 0, ?, ?)";
                $payable_id = (int)app_exec_getlast_id_roll_back($conn, $sql_p, [$stk['purchase_date'], $particuler, $stk['total_cost'], $stk['group_id'], $stk['bill_file']], "sisiis");
                
                if ($payable_id > 0) {
                    app_exec_query("UPDATE tbl_shuttle_cock_stock_in SET payable_id = $payable_id WHERE id = $stock_id");
                }
            }
        }

        if ($payable_id <= 0) {
            echo json_encode(['status' => 'error', 'message' => 'Invalid payable ID']);
            exit();
        }

        $p_res = app_exec_getresult("SELECT * FROM tbl_payable WHERE id = ? LIMIT 1", [$payable_id], "i");
        $payable_row = $p_res ? $p_res->fetch_assoc() : null;
        if (!$payable_row) {
            echo json_encode(['status' => 'error', 'message' => 'Payable record not found']);
            exit();
        }

        $head_id = (int)($payable_row['head'] ?: 1);
        $group_id = (int)($payable_row['group_id'] ?: 0);
        $particuler = !empty($notes) ? $notes : $payable_row['particuler'];
        $bill_photo = $payable_row['bill_photo'];

        $sql_paid = "INSERT INTO tbl_paid (date, head, group_id, particuler, amount, payable_id, flag, transaction_type, bill_photo) VALUES (?, ?, ?, ?, ?, ?, 0, ?, ?)";
        app_exec_nonquery($sql_paid, [$payment_date, $head_id, $group_id, $particuler, $amount_paid, $payable_id, $transaction_type, $bill_photo], "siisdiis");

        $sum_paid_res = app_exec_getresult("SELECT SUM(amount) as tot_p FROM tbl_paid WHERE payable_id = ? AND cancel != 1", [$payable_id], "i");
        $tot_p = (float)($sum_paid_res ? $sum_paid_res->fetch_assoc()['tot_p'] : 0);
        $tot_payable = (float)$payable_row['amount'];

        $is_complete = ($tot_p >= $tot_payable) ? 1 : 0;
        app_exec_nonquery("UPDATE tbl_payable SET iscomplete = ? WHERE id = ?", [$is_complete, $payable_id], "ii");

        echo json_encode(['status' => 'success', 'message' => 'Payment recorded successfully']);
        exit();
    } catch (Exception $e) {
        http_response_code(500);
        echo json_encode(['status' => 'error', 'message' => $e->getMessage()]);
        exit();
    }
}

if ($action == 'load_data') {
    try {
        $rowsPerPage = 8;
        $current_page = (int)($_POST['page'] ?? 1);
        if ($current_page < 1) $current_page = 1;
        $offset = ($current_page - 1) * $rowsPerPage;
        $is_print = (!empty($_POST['is_print']) && $_POST['is_print'] == '1') || (!empty($_POST['no_limit']) && $_POST['no_limit'] == '1');

        $where_stock = "";
        if (!$is_all_groups) {
            $where_stock = " WHERE s.group_id IN ($in_clause) ";
        }

        $count_sql = "SELECT COUNT(*) as cnt FROM tbl_shuttle_cock_stock_in s" . $where_stock;
        $count_res = app_exec_query($count_sql);
        $total_rows = 0;
        if ($count_res && $row = $count_res->fetch_assoc()) {
            $total_rows = (int)$row['cnt'];
        }

        $limit_clause = $is_print ? "" : " LIMIT $offset, $rowsPerPage";
        $sql = "SELECT s.id, s.purchase_date, s.group_id, s.brand_name, s.no_of_boxes, s.shuttles_per_box, s.total_shuttles, s.box_price, s.per_shuttle_price, s.total_cost, s.supplier_name, s.invoice_no, s.bill_file, s.payable_id, s.notes, g.name as group_name,
                       p.iscomplete,
                       COALESCE(SUM(pd.amount), 0) as paid_amount
                FROM tbl_shuttle_cock_stock_in s 
                LEFT JOIN tbl_groups g ON s.group_id = g.id 
                LEFT JOIN tbl_payable p ON s.payable_id = p.id
                LEFT JOIN tbl_paid pd ON (p.id = pd.payable_id AND pd.cancel != 1)
                $where_stock
                GROUP BY s.id
                ORDER BY s.purchase_date DESC, s.id DESC" . $limit_clause;
        $result = app_exec_query($sql);
        $data = [];
        if ($result) {
            while ($r = $result->fetch_assoc()) {
                if (empty($r['group_name'])) {
                    $r['group_name'] = 'General / All Groups';
                }
                $r['paid_amount'] = (float)$r['paid_amount'];
                $r['due_amount'] = max(0, (float)$r['total_cost'] - $r['paid_amount']);
                if ($r['paid_amount'] >= (float)$r['total_cost'] && (float)$r['total_cost'] > 0) {
                    $r['payment_status'] = 'paid';
                } else if ($r['paid_amount'] > 0) {
                    $r['payment_status'] = 'partial';
                } else {
                    $r['payment_status'] = 'unpaid';
                }
                $lock_status = getShuttlePurchaseFeeLockStatus($r);
                $r['is_fee_locked'] = !empty($lock_status['is_locked']);
                $r['fee_lock_reason'] = $lock_status['reason'] ?? '';
                $r['fee_locked_month'] = $lock_status['month'] ?? '';

                $data[] = $r;
            }
        }

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

        if (!$is_all_groups) {
            $sum_in_res = app_exec_query("SELECT SUM(total_shuttles) as tot_in, SUM(no_of_boxes) as tot_boxes, SUM(total_cost) as tot_cost FROM tbl_shuttle_cock_stock_in WHERE group_id IN ($in_clause)");
            $sum_used_res = app_exec_query("SELECT SUM(shuttles_used) as tot_used, SUM(total_amount) as tot_used_val FROM tbl_shuttle_cock_readings WHERE group_id IN ($in_clause)");
        } else {
            $sum_in_res = app_exec_query("SELECT SUM(total_shuttles) as tot_in, SUM(no_of_boxes) as tot_boxes, SUM(total_cost) as tot_cost FROM tbl_shuttle_cock_stock_in");
            $sum_used_res = app_exec_query("SELECT SUM(shuttles_used) as tot_used, SUM(total_amount) as tot_used_val FROM tbl_shuttle_cock_readings");
        }

        $sum_in = $sum_in_res ? $sum_in_res->fetch_assoc() : [];
        $total_shuttles_in = (int)($sum_in['tot_in'] ?? 0);
        $total_boxes_in = (int)($sum_in['tot_boxes'] ?? 0);
        $total_cost_in = (float)($sum_in['tot_cost'] ?? 0.00);

        $sum_used = $sum_used_res ? $sum_used_res->fetch_assoc() : [];
        $total_shuttles_used = (int)($sum_used['tot_used'] ?? 0);
        $total_used_value = (float)($sum_used['tot_used_val'] ?? 0.00);

        $stock_remaining = max(0, $total_shuttles_in - $total_shuttles_used);

        $latest_price_res = app_exec_query("SELECT per_shuttle_price FROM tbl_shuttle_cock_stock_in ORDER BY purchase_date DESC, id DESC LIMIT 1");
        $latest_unit_price = 0.00;
        if ($latest_price_res && $l_row = $latest_price_res->fetch_assoc()) {
            $latest_unit_price = (float)$l_row['per_shuttle_price'];
        }

        $group_breakdown = [];
        
        if ($is_all_groups) {
            $gen_in_res = app_exec_query("SELECT SUM(total_shuttles) as tot_in, SUM(total_cost) as tot_cost FROM tbl_shuttle_cock_stock_in WHERE group_id = 0");
            $gen_in = (int)($gen_in_res ? $gen_in_res->fetch_assoc()['tot_in'] : 0);

            $gen_used_res = app_exec_query("SELECT SUM(shuttles_used) as tot_used, SUM(total_amount) as tot_val FROM tbl_shuttle_cock_readings WHERE group_id = 0");
            $gen_used_row = $gen_used_res ? $gen_used_res->fetch_assoc() : [];
            $gen_used = (int)($gen_used_row['tot_used'] ?? 0);
            $gen_val = (float)($gen_used_row['tot_val'] ?? 0.00);

            if ($gen_in > 0 || $gen_used > 0) {
                $group_breakdown[] = [
                    'group_id' => 0,
                    'group_name' => 'General / Shared Pool',
                    'shuttles_in' => $gen_in,
                    'shuttles_used' => $gen_used,
                    'shuttles_remaining' => max(0, $gen_in - $gen_used),
                    'total_used_cost' => $gen_val
                ];
            }
        }

        foreach ($groups as $grp) {
            $gid = (int)$grp['id'];
            $grp_name = $grp['group_name'];

            $grp_in_res = app_exec_getresult("SELECT SUM(total_shuttles) as tot_in, SUM(total_cost) as tot_cost FROM tbl_shuttle_cock_stock_in WHERE group_id = ?", [$gid], "i");
            $grp_in = (int)($grp_in_res ? $grp_in_res->fetch_assoc()['tot_in'] : 0);

            $grp_used_res = app_exec_getresult("SELECT SUM(shuttles_used) as tot_used, SUM(total_amount) as tot_val FROM tbl_shuttle_cock_readings WHERE group_id = ?", [$gid], "i");
            $grp_used_row = $grp_used_res ? $grp_used_res->fetch_assoc() : [];
            $grp_used = (int)($grp_used_row['tot_used'] ?? 0);
            $grp_val = (float)($grp_used_row['tot_val'] ?? 0.00);

            $grp_rem = $grp_in > 0 ? max(0, $grp_in - $grp_used) : 0;

            if ($grp_in > 0 || $grp_used > 0 || !$is_all_groups) {
                $group_breakdown[] = [
                    'group_id' => $gid,
                    'group_name' => $grp_name,
                    'shuttles_in' => $grp_in,
                    'shuttles_used' => $grp_used,
                    'shuttles_remaining' => $grp_rem,
                    'total_used_cost' => $grp_val
                ];
            }
        }

        $summary = [
            'total_rows' => $total_rows,
            'total_shuttles_in' => $total_shuttles_in,
            'total_boxes_in' => $total_boxes_in,
            'total_cost_in' => $total_cost_in,
            'total_shuttles_used' => $total_shuttles_used,
            'stock_remaining' => $stock_remaining,
            'latest_unit_price' => $latest_unit_price,
            'groups' => $groups,
            'group_breakdown' => $group_breakdown,
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
?>
