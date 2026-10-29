<?php
session_start();
include '../../app_common/db_connect.php';
include '../../app_common/auth_helper.php';
include '../../app_common/enums.php';

if (empty($_SESSION['login_id'])) {
    header("Location: ../../index.php");
    exit();
}

$login_id = (int)$_SESSION['login_id'];
if (isNormalMember($login_id)) {
    header("Location: home.php");
    exit();
}
$is_admin = isSuperAdmin($login_id) || isGroupAdmin($login_id);
$allowed_groups = getUserAllowedGroupIds($login_id);
$primary_role = getUserPrimaryRoleName($login_id);

$active_tab = 'accounts';

// Financial Year Logic: Default to active/current financial year from tbl_closing or date calculation
$sql_closing = "SELECT id, from_year, to_year FROM tbl_closing ORDER BY from_year DESC";
$res_closing = app_exec_query($sql_closing);
$closing_years = [];
if ($res_closing && $res_closing->num_rows > 0) {
    while ($row_cl = $res_closing->fetch_assoc()) {
        $closing_years[] = $row_cl;
    }
}

$selected_closing_id = isset($_GET['closing_id']) ? (int)$_GET['closing_id'] : 0;

$active_from_year = null;
$active_to_year = null;

if ($selected_closing_id > 0) {
    foreach ($closing_years as $cy) {
        if ((int)$cy['id'] === $selected_closing_id) {
            $active_from_year = (int)$cy['from_year'];
            $active_to_year = !empty($cy['to_year']) ? (int)$cy['to_year'] : ($active_from_year + 1);
            break;
        }
    }
}

if (!$active_from_year) {
    if (!empty($closing_years)) {
        $latest_cy = $closing_years[0];
        $selected_closing_id = (int)$latest_cy['id'];
        $active_from_year = (int)$latest_cy['from_year'];
        $active_to_year = !empty($latest_cy['to_year']) ? (int)$latest_cy['to_year'] : ($active_from_year + 1);
    } else {
        $cur_y = (int)date('Y');
        $cur_m = (int)date('m');
        $active_from_year = ($cur_m >= 4) ? $cur_y : ($cur_y - 1);
        $active_to_year = $active_from_year + 1;
    }
}

$fy_start = $active_from_year . "-04-01";
$fy_end = $active_to_year . "-03-31";
$fy_label = "FY " . $active_from_year . "-" . substr($active_to_year, -2);

// Payment heads for Add Receivable modal
$sql_heads = "SELECT id, name FROM tbl_payment_head_master WHERE type = 'Credit' ORDER BY name";
$res_heads = app_exec_query($sql_heads);
$heads_list = [];
if ($res_heads && $res_heads->num_rows > 0) {
    while ($h = $res_heads->fetch_assoc()) {
        $heads_list[] = $h;
    }
}

// 1. Fetch Members and their Dues & Wallet balances
$sql_members = "
    SELECT 
        m.id,
        m.first_name,
        m.middle_name,
        m.last_name,
        m.phone,
        m.img,
        m.member_type,
        GROUP_CONCAT(DISTINCT g.name SEPARATOR ', ') AS group_names,
        (
            SELECT COALESCE(SUM(CASE 
                WHEN type = 'credit' THEN amount 
                WHEN type = 'debit' THEN -amount 
                ELSE 0 
            END), 0) 
            FROM tbl_wallet 
            WHERE client_id = m.id
        ) AS wallet_amount,
        (
            COALESCE((SELECT SUM(fees) FROM tbl_member_recievable r WHERE r.member_id = m.id AND r.cancel = 0), 0)
            -
            COALESCE((SELECT SUM(fees) FROM tbl_member_recieved rd WHERE rd.member_id = m.id AND rd.cancel = 0), 0)
        ) AS pending_balance
    FROM tbl_members AS m
    LEFT JOIN tbl_group_member_map gmm ON m.id = gmm.member_id
    LEFT JOIN tbl_groups g ON gmm.group_id = g.id AND g.status = 1
    WHERE m.inactive = 0
";
if (!in_array('ALL', $allowed_groups, true) && !empty($allowed_groups)) {
    $in = implode(',', array_map('intval', $allowed_groups));
    $sql_members .= " AND gmm.group_id IN ($in)";
}
$sql_members .= " GROUP BY m.id ORDER BY (pending_balance > 0) DESC, pending_balance DESC, m.first_name, m.last_name";
$res_members = app_exec_query($sql_members);

$members_data = [];
$tot_pending_all = 0.0;
$members_with_due_count = 0;
$members_total_count = 0;

if ($res_members && $res_members->num_rows > 0) {
    while ($m_row = $res_members->fetch_assoc()) {
        $due = (float)$m_row['pending_balance'];
        if ($due > 0) {
            $tot_pending_all += $due;
            $members_with_due_count++;
        }
        $members_total_count++;
        $members_data[] = $m_row;
    }
}

// 2. KPI Metrics for Current Financial Year Receipts
$sql_tot = "SELECT SUM(mr.fees) as total_amount, COUNT(DISTINCT mr.id) as total_count 
            FROM tbl_member_recieved mr 
            LEFT JOIN tbl_group_member_map gmm ON mr.member_id = gmm.member_id 
            WHERE mr.cancel = 0 AND mr.fees > 0 
              AND mr.date BETWEEN '$fy_start' AND '$fy_end'";
if (!in_array('ALL', $allowed_groups, true) && !empty($allowed_groups)) {
    $in = implode(',', array_map('intval', $allowed_groups));
    $sql_tot .= " AND gmm.group_id IN ($in)";
}
$res_tot = app_exec_query($sql_tot);
$tot_row = $res_tot ? $res_tot->fetch_assoc() : [];
$total_amount = (float)($tot_row['total_amount'] ?? 0);
$total_count = (int)($tot_row['total_count'] ?? 0);

// Current Month Collection for Financial Year
$current_month = date('Y-m');
$sql_m_tot = "SELECT SUM(mr.fees) as month_amount 
              FROM tbl_member_recieved mr 
              LEFT JOIN tbl_group_member_map gmm ON mr.member_id = gmm.member_id 
              WHERE mr.cancel = 0 AND mr.fees > 0 
                AND DATE_FORMAT(mr.date, '%Y-%m') = '$current_month'
                AND mr.date BETWEEN '$fy_start' AND '$fy_end'";
if (!in_array('ALL', $allowed_groups, true) && !empty($allowed_groups)) {
    $in = implode(',', array_map('intval', $allowed_groups));
    $sql_m_tot .= " AND gmm.group_id IN ($in)";
}
$res_m_tot = app_exec_query($sql_m_tot);
$month_amount = $res_m_tot ? (float)($res_m_tot->fetch_assoc()['month_amount'] ?? 0) : 0;

if (!function_exists('getFormattedReceiptDescription')) {
    function getFormattedReceiptDescription($desc, $rec_desc, $head_name, $date, $rec_date = '') {
        $candidates = [trim((string)$desc), trim((string)$rec_desc)];
        $month_names = ['january', 'february', 'march', 'april', 'may', 'june', 'july', 'august', 'september', 'october', 'november', 'december'];
        
        foreach ($candidates as $c) {
            if (!empty($c)) {
                $c_lower = strtolower($c);
                foreach ($month_names as $mn) {
                    if (strpos($c_lower, $mn) !== false) {
                        return $c;
                    }
                }
            }
        }
        
        $target_date = (!empty($rec_date) && $rec_date !== '0000-00-00' && strtotime($rec_date) !== false) ? $rec_date : $date;
        $month_str = (!empty($target_date) && $target_date !== '0000-00-00' && strtotime($target_date) !== false) ? date('F Y', strtotime($target_date)) : '';
        
        foreach ($candidates as $c) {
            if (!empty($c) && !in_array(strtolower($c), ['fee collection', 'fee received', 'monthly fee', 'fee', 'fee receipt'])) {
                return !empty($month_str) ? ($c . ' - ' . $month_str) : $c;
            }
        }
        
        if (!empty($month_str)) {
            $prefix = (!empty($head_name) && !in_array(strtolower($head_name), ['fee collection', 'fee receipt', 'fee'])) ? $head_name : 'Monthly Fee';
            return $prefix . ' - ' . $month_str;
        }
        
        return !empty($head_name) ? $head_name : 'Monthly Fee';
    }
}

// Fetch fee receipts list for Current Financial Year
$sql = "
    SELECT mr.id, mr.date, mr.fees as amount, mr.discription, mr.transaction_type,
           rec.discription as rec_discription, rec.date as rec_date,
           fhm.name as head_name,
           m.id as member_id, m.first_name, m.middle_name, m.last_name, m.img,
           GROUP_CONCAT(DISTINCT g.name SEPARATOR ', ') AS group_names
    FROM tbl_member_recieved mr
    JOIN tbl_members m ON mr.member_id = m.id
    LEFT JOIN tbl_member_recievable rec ON mr.receiveble_id = rec.id
    LEFT JOIN tbl_fees_head_master fhm ON mr.head = fhm.id
    LEFT JOIN tbl_group_member_map gmm ON m.id = gmm.member_id
    LEFT JOIN tbl_groups g ON gmm.group_id = g.id
    WHERE mr.cancel = 0 AND mr.fees > 0 
      AND mr.date BETWEEN '$fy_start' AND '$fy_end'
";
if (!in_array('ALL', $allowed_groups, true) && !empty($allowed_groups)) {
    $in = implode(',', array_map('intval', $allowed_groups));
    $sql .= " AND gmm.group_id IN ($in)";
}
$sql .= " GROUP BY mr.id ORDER BY mr.date DESC, mr.id DESC";
$res = app_exec_query($sql);

// Members list for selector modal
$sql_m = "SELECT id, first_name, middle_name, last_name, phone FROM tbl_members WHERE inactive = 0 ORDER BY first_name, middle_name, last_name";
$res_m = app_exec_query($sql_m);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
    <title>Member Fees & Receipts - YMCA Mobile</title>
    
    <link href="../../css/bootstrap.min.css" rel="stylesheet">
    <link href="../../font-awesome/css/font-awesome.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <link href="../../css/plugins/sweetalert/sweetalert.css" rel="stylesheet">
    <link href="mobile_layout.css" rel="stylesheet">

    <style>
        body { font-family: 'Plus Jakarta Sans', sans-serif !important; background: #f8fafc !important; }
        
        .acc-hero {
            background: linear-gradient(135deg, #059669 0%, #047857 100%);
            border-radius: 20px; padding: 18px 20px; color: #ffffff; margin-bottom: 16px;
            box-shadow: 0 8px 20px rgba(5, 150, 105, 0.22);
        }
        .acc-hero h2 { margin: 0 0 4px 0; font-weight: 800; font-size: 20px; }
        .acc-hero p { margin: 0; font-size: 12px; opacity: 0.92; }

        .kpi-grid { display: grid; grid-template-columns: repeat(3, 1fr); gap: 10px; margin-bottom: 16px; }
        .kpi-card-sm {
            background: #ffffff; border-radius: 14px; border: 1px solid #e2e8f0; padding: 10px 12px;
            box-shadow: 0 2px 6px rgba(0,0,0,0.02); text-align: left;
        }
        .kpi-lbl { font-size: 9px; font-weight: 800; color: #64748b; text-transform: uppercase; letter-spacing: 0.3px; }
        .kpi-val { font-size: 14px; font-weight: 800; color: #0f172a; margin-top: 2px; }

        /* Segmented Tabs */
        .seg-tabs-wrapper {
            background: #e2e8f0;
            padding: 4px;
            border-radius: 14px;
            display: flex;
            margin-bottom: 14px;
            gap: 4px;
        }
        .seg-tab-btn {
            flex: 1;
            border: none;
            background: transparent;
            padding: 9px 12px;
            border-radius: 10px;
            font-size: 12.5px;
            font-weight: 800;
            color: #64748b;
            cursor: pointer;
            transition: all 0.2s ease;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: 6px;
            outline: none;
        }
        .seg-tab-btn.active {
            background: #ffffff;
            color: #0f172a;
            box-shadow: 0 2px 8px rgba(0,0,0,0.08);
        }

        /* Filter Chips */
        .filter-chips-row {
            display: flex;
            gap: 6px;
            overflow-x: auto;
            padding-bottom: 8px;
            margin-bottom: 12px;
            -webkit-overflow-scrolling: touch;
        }
        .filter-chips-row::-webkit-scrollbar { display: none; }
        .filter-chip {
            padding: 6px 12px;
            border-radius: 20px;
            background: #ffffff;
            border: 1px solid #e2e8f0;
            font-size: 11.5px;
            font-weight: 700;
            color: #64748b;
            white-space: nowrap;
            cursor: pointer;
            transition: all 0.15s ease;
        }
        .filter-chip.active {
            background: #0f172a;
            color: #ffffff;
            border-color: #0f172a;
        }

        /* Search input */
        .search-box { position: relative; margin-bottom: 12px; }
        .search-box input {
            width: 100%; height: 42px; border-radius: 12px; border: 1px solid #e2e8f0;
            padding: 0 14px 0 38px; font-size: 13px; font-weight: 600; color: #1e293b;
            background: #ffffff; box-shadow: 0 2px 6px rgba(0,0,0,0.02); outline: none;
        }
        .search-box i { position: absolute; left: 14px; top: 13px; color: #94a3b8; font-size: 14px; }

        /* Member Card */
        .mem-fee-card {
            background: #ffffff;
            border-radius: 16px;
            border: 1px solid #e2e8f0;
            padding: 14px 14px 12px 14px;
            margin-bottom: 12px;
            box-shadow: 0 2px 8px rgba(0,0,0,0.025);
            transition: transform 0.15s ease, box-shadow 0.15s ease;
        }
        .mem-fee-header {
            display: flex;
            align-items: center;
            gap: 12px;
        }
        .mem-avatar-box {
            width: 46px;
            height: 46px;
            border-radius: 50%;
            object-fit: cover;
            border: 2px solid #e2e8f0;
            background: linear-gradient(135deg, #10b981, #059669);
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: 800;
            color: #ffffff;
            font-size: 16px;
            flex-shrink: 0;
        }
        .mem-fee-info {
            flex: 1;
            min-width: 0;
        }
        .mem-fee-name {
            font-size: 14.5px;
            font-weight: 800;
            color: #0f172a;
            margin: 0 0 2px 0;
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
            display: flex;
            align-items: center;
            gap: 6px;
            flex-wrap: wrap;
        }
        .guest-badge {
            background: linear-gradient(135deg, #fff7ed 0%, #ffedd5 100%);
            color: #c2410c;
            border: 1px solid rgba(249, 115, 22, 0.3);
            font-size: 9.5px;
            font-weight: 800;
            padding: 2px 6px;
            border-radius: 10px;
            letter-spacing: 0.3px;
            display: inline-flex;
            align-items: center;
            gap: 3px;
        }
        .mem-fee-meta {
            font-size: 11.5px;
            color: #64748b;
            font-weight: 600;
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
        }

        /* Balance Pill Strip */
        .mem-bal-row {
            display: flex;
            gap: 8px;
            margin-top: 10px;
        }
        .bal-badge-pill {
            flex: 1;
            padding: 6px 10px;
            border-radius: 10px;
            display: flex;
            align-items: center;
            justify-content: space-between;
            font-size: 11.5px;
            font-weight: 700;
        }
        .bal-badge-pill .pill-tag {
            font-size: 9px;
            text-transform: uppercase;
            font-weight: 800;
            opacity: 0.85;
            letter-spacing: 0.3px;
        }
        .bal-badge-pill .pill-val {
            font-size: 13.5px;
            font-weight: 800;
        }
        .pill-due-has {
            background: #fef2f2;
            color: #dc2626;
            border: 1px solid rgba(239, 68, 68, 0.2);
        }
        .pill-due-zero {
            background: #f0fdf4;
            color: #166534;
            border: 1px solid rgba(34, 197, 94, 0.2);
        }
        .pill-wallet-has {
            background: #ecfdf5;
            color: #047857;
            border: 1px solid rgba(16, 185, 129, 0.25);
        }
        .pill-wallet-empty {
            background: #f8fafc;
            color: #64748b;
            border: 1px solid #e2e8f0;
        }

        /* 4 Action Buttons Grid */
        .mem-actions-grid {
            display: grid;
            grid-template-columns: repeat(4, 1fr);
            gap: 6px;
            margin-top: 10px;
            padding-top: 10px;
            border-top: 1px dashed #f1f5f9;
        }
        .btn-action-fee {
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            gap: 4px;
            padding: 8px 4px;
            border-radius: 12px;
            font-size: 11px;
            font-weight: 800;
            text-decoration: none !important;
            transition: all 0.18s ease;
            border: 1px solid transparent;
            cursor: pointer;
            text-align: center;
            white-space: nowrap;
            outline: none;
            background: transparent;
        }
        .btn-action-fee i {
            font-size: 13px;
        }
        .btn-action-fee:active {
            transform: scale(0.96);
        }

        .btn-action-fee.pay {
            background: rgba(16, 185, 129, 0.1);
            color: #059669;
            border-color: rgba(16, 185, 129, 0.2);
        }
        .btn-action-fee.pay:hover, .btn-action-fee.pay:active {
            background: #10b981;
            color: #ffffff;
        }

        .btn-action-fee.ledger {
            background: rgba(59, 130, 246, 0.1);
            color: #2563eb;
            border-color: rgba(59, 130, 246, 0.2);
        }
        .btn-action-fee.ledger:hover, .btn-action-fee.ledger:active {
            background: #3b82f6;
            color: #ffffff;
        }

        .btn-action-fee.receivable {
            background: rgba(168, 85, 247, 0.1);
            color: #9333ea;
            border-color: rgba(168, 85, 247, 0.2);
        }
        .btn-action-fee.receivable:hover, .btn-action-fee.receivable:active {
            background: #a855f7;
            color: #ffffff;
        }

        .btn-action-fee.report {
            background: rgba(239, 68, 68, 0.1);
            color: #dc2626;
            border-color: rgba(239, 68, 68, 0.2);
        }
        .btn-action-fee.report:hover, .btn-action-fee.report:active {
            background: #ef4444;
            color: #ffffff;
        }

        /* Receipts tab list styling */
        .rcp-card {
            background: #ffffff; border-radius: 16px; border: 1px solid #e2e8f0; padding: 14px 16px;
            margin-bottom: 10px; box-shadow: 0 2px 6px rgba(0,0,0,0.02);
            transition: transform 0.15s ease;
        }
        .rcp-card:active { transform: scale(0.99); }
        .rcp-header { display: flex; align-items: center; justify-content: space-between; margin-bottom: 8px; }
        .rcp-date { font-size: 11px; font-weight: 700; color: #64748b; text-transform: uppercase; }
        .rcp-badge { font-size: 10px; font-weight: 800; padding: 3px 8px; border-radius: 6px; text-transform: uppercase; }
        .rcp-badge-cash { background: #dcfce7; color: #166534; }
        .rcp-badge-bank { background: #e0e7ff; color: #3730a3; }

        .rcp-body { display: flex; align-items: center; gap: 12px; }
        .rcp-avatar {
            width: 42px; height: 42px; border-radius: 50%; object-fit: cover;
            border: 2px solid #e2e8f0; background: #f1f5f9; display: flex;
            align-items: center; justify-content: center; font-weight: 800; color: #059669; font-size: 14px;
            flex-shrink: 0;
        }
        .rcp-info { flex: 1; min-width: 0; }
        .rcp-name { font-size: 14px; font-weight: 800; color: #0f172a; margin: 0; white-space: nowrap; overflow: hidden; text-overflow: ellipsis; }
        .rcp-meta { font-size: 11.5px; color: #64748b; font-weight: 600; margin-top: 2px; }
        .rcp-amt { font-size: 16px; font-weight: 800; color: #059669; text-align: right; white-space: nowrap; }

        .rcp-footer {
            margin-top: 10px; padding-top: 10px; border-top: 1px dashed #f1f5f9;
            display: flex; align-items: center; justify-content: space-between;
        }
        .rcp-num { font-size: 11px; font-weight: 700; color: #94a3b8; }
        .rcp-btn-view {
            background: rgba(16, 185, 129, 0.1); color: #059669; border: none; border-radius: 8px;
            padding: 5px 12px; font-size: 12px; font-weight: 800; display: inline-flex; align-items: center; gap: 5px;
            cursor: pointer; transition: background 0.2s ease;
        }
        .rcp-btn-view:hover { background: rgba(16, 185, 129, 0.2); }

        /* Printable Receipt Modal Styling */
        .printable-receipt {
            background: #ffffff; border-radius: 16px; padding: 20px; font-family: 'Plus Jakarta Sans', sans-serif;
            color: #0f172a; border: 2px solid #059669; position: relative;
        }
        .receipt-watermark {
            position: absolute; right: 20px; bottom: 20px; opacity: 0.05; font-size: 120px; color: #059669;
            pointer-events: none;
        }

        /* Add Receivable Modal Styling */
        .modal-body label {
            font-weight: 700 !important;
            font-size: 11.5px;
            color: #64748b;
            margin-bottom: 5px;
            text-transform: uppercase;
            letter-spacing: 0.4px;
        }
        .form-control-mob {
            width: 100%;
            border-radius: 12px !important;
            border: 1.5px solid #e2e8f0 !important;
            padding: 10px 14px !important;
            background-color: #ffffff !important;
            color: #0f172a !important;
            font-size: 13.5px !important;
            font-weight: 600 !important;
            outline: none !important;
            transition: all 0.2s ease;
        }
        .form-control-mob:focus {
            border-color: #a855f7 !important;
            box-shadow: 0 0 0 4px rgba(168, 85, 247, 0.12) !important;
        }

        /* Tool Split Container matching directory/fees_receiveble */
        .tool-panel-container {
            display: flex;
            flex-direction: column;
            gap: 12px;
            margin-bottom: 14px;
        }

        .tool-card-box {
            background: #ffffff;
            border-radius: 18px;
            border: 1.5px solid #e2e8f0;
            box-shadow: 0 2px 8px rgba(0,0,0,0.03);
            padding: 14px 16px;
            display: flex;
            flex-direction: column;
            gap: 10px;
        }

        .tool-card-box h4 {
            font-weight: 800;
            font-size: 13px;
            margin: 0 !important;
            color: #0f172a;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            display: flex;
            align-items: center;
            gap: 8px;
        }

        /* Action form layout */
        .action-input-group {
            display: flex;
            align-items: center;
            background: #f8fafc !important;
            border: 1.5px solid #e2e8f0 !important;
            border-radius: 14px !important;
            padding: 4px 6px !important;
            transition: all 0.25s ease;
        }

        .action-input-group:focus-within {
            border-color: #6366f1 !important;
            box-shadow: 0 0 0 4px rgba(99, 102, 241, 0.12) !important;
        }

        .action-input-group input.form-control {
            background: transparent !important;
            border: none !important;
            padding: 8px 10px !important;
            height: auto !important;
            color: #0f172a !important;
            font-size: 14.5px !important;
            font-weight: 800 !important;
            flex: 1 !important;
            min-width: 0 !important;
        }

        .action-btn-group {
            display: flex;
            align-items: center;
            flex-shrink: 0;
        }

        .btn-set-fees-custom {
            background: #ffffff !important;
            color: #0f172a !important;
            border: 1.5px solid #e2e8f0 !important;
            border-radius: 10px !important;
            padding: 8px 12px !important;
            font-weight: 800 !important;
            font-size: 12px !important;
            transition: all 0.2s ease;
            white-space: nowrap !important;
            flex-shrink: 0 !important;
            cursor: pointer;
        }

        .btn-set-fees-custom:hover, .btn-set-fees-custom:active {
            border-color: #6366f1 !important;
            color: #6366f1 !important;
            background: #f8faff !important;
        }

        .btn-setoff-custom {
            background: linear-gradient(135deg, #6366f1 0%, #4f46e5 100%) !important;
            color: #ffffff !important;
            border-radius: 10px !important;
            padding: 8px 14px !important;
            font-weight: 800 !important;
            font-size: 12px !important;
            border: none !important;
            box-shadow: 0 4px 12px rgba(99, 102, 241, 0.28) !important;
            transition: all 0.2s ease;
            margin-left: 6px;
            white-space: nowrap !important;
            flex-shrink: 0 !important;
            cursor: pointer;
        }

        .btn-setoff-custom:hover, .btn-setoff-custom:active {
            transform: translateY(-1px);
            opacity: 0.95;
            box-shadow: 0 6px 16px rgba(99, 102, 241, 0.35) !important;
        }

        .quick-rec-card {
            background: #ffffff; border-radius: 14px; border: 1px solid #e2e8f0; padding: 12px;
            margin-bottom: 8px; box-shadow: 0 2px 6px rgba(0,0,0,0.02);
            transition: all 0.25s ease;
        }
        .quick-rec-card.selected-setoff-card {
            border: 2px solid #6366f1 !important;
            background: #f8faff !important;
            box-shadow: 0 4px 14px rgba(99, 102, 241, 0.12) !important;
        }
        .quick-rec-card.is-canceled {
            opacity: 0.78;
            background: #f8fafc !important;
            border: 1.5px dashed #cbd5e1 !important;
        }
        .quick-rec-card .rec-item-header { display: flex; align-items: center; justify-content: space-between; margin-bottom: 4px; }
        .quick-rec-card .rec-item-date { font-size: 10.5px; font-weight: 700; color: #64748b; text-transform: uppercase; }
        .quick-rec-card .status-pill { font-size: 9.5px; font-weight: 800; padding: 2px 6px; border-radius: 6px; text-transform: uppercase; }
        .quick-rec-card .status-pill-paid { background: #dcfce7; color: #166534; }
        .quick-rec-card .status-pill-pending { background: #fef3c7; color: #92400e; }
        .quick-rec-card .status-pill-canceled { background: #f1f5f9; color: #64748b; }

        .btn-table-action-edit {
            background-color: rgba(59, 130, 246, 0.1) !important;
            color: #2563eb !important;
            border-radius: 8px !important;
            font-size: 11px !important;
            font-weight: 700 !important;
            padding: 4px 10px !important;
            border: none !important;
            transition: all 0.2s ease;
            display: inline-flex;
            align-items: center;
            gap: 4px;
            cursor: pointer;
        }
        .btn-table-action-edit:hover, .btn-table-action-edit:active {
            background-color: #2563eb !important;
            color: #ffffff !important;
        }
        .btn-table-action-cancel {
            border-radius: 8px !important;
            font-size: 11px !important;
            font-weight: 700 !important;
            padding: 4px 10px !important;
            border: none !important;
            transition: all 0.2s ease;
            display: inline-flex;
            align-items: center;
            gap: 4px;
            cursor: pointer;
        }
        .btn-table-action-cancel.cancel {
            background-color: rgba(239, 68, 68, 0.1) !important;
            color: #ef4444 !important;
        }
        .btn-table-action-cancel.cancel:hover, .btn-table-action-cancel.cancel:active {
            background-color: #ef4444 !important;
            color: #ffffff !important;
        }
        .btn-table-action-cancel.proceed {
            background-color: rgba(16, 185, 129, 0.1) !important;
            color: #10b981 !important;
        }
        .btn-table-action-cancel.proceed:hover, .btn-table-action-cancel.proceed:active {
            background-color: #10b981 !important;
            color: #ffffff !important;
        }

        .quick-rec-card .rec-item-title { font-size: 13px; font-weight: 800; color: #0f172a; margin: 0 0 6px 0; }
        .quick-rec-card .rec-item-grid { display: grid; grid-template-columns: repeat(4, 1fr); gap: 4px; font-size: 10.5px; background: #f8fafc; padding: 6px 4px; border-radius: 8px; text-align: center; }
        .quick-rec-card .rec-item-lbl { font-size: 9px; font-weight: 700; color: #64748b; text-transform: uppercase; }
        .quick-rec-card .rec-item-val { font-weight: 800; color: #0f172a; margin-top: 1px; font-size: 11px; }
    </style>
</head>
<body class="mob-body">

    <!-- Header -->
    <header class="mob-header">
        <div class="mob-header-brand">
            <a href="accounts.php" style="color:#ffffff; margin-right:8px; font-size:18px;"><i class="fa fa-arrow-left"></i></a>
            <div class="mob-header-title">
                Member <span>Fees & Dues</span>
            </div>
        </div>
        <div class="mob-header-actions">
            <a href="../../app_login_manager/logout.php" class="mob-header-btn" title="Logout"><i class="fa fa-sign-out"></i></a>
        </div>
    </header>

    <div class="mob-page">

        <!-- Hero Card -->
        <div class="acc-hero">
            <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:8px;">
                <div>
                    <h2>Member Fees & Receipts</h2>
                    <p>Track member dues, collect fees & download reports</p>
                </div>
                <button class="btn btn-light btn-sm" style="border-radius:12px; font-weight:800; color:#059669; padding:8px 12px; box-shadow: 0 4px 10px rgba(0,0,0,0.1); white-space:nowrap;" data-toggle="modal" data-target="#recordPaymentModal">
                    <i class="fa fa-plus-circle"></i> Quick Pay
                </button>
            </div>
            <!-- Financial Year Indicator / Selector -->
            <?php if (!empty($closing_years)): ?>
                <div style="margin-top:10px; display:flex; align-items:center; gap:8px; background:rgba(255,255,255,0.15); border-radius:12px; padding:6px 12px; backdrop-filter:blur(4px);">
                    <i class="fa fa-calendar" style="font-size:13px;"></i>
                    <span style="font-size:11.5px; font-weight:700;">Financial Year:</span>
                    <select onchange="location.href='fee_receipts.php?closing_id=' + this.value" style="background:transparent; color:#ffffff; border:none; font-size:12px; font-weight:800; outline:none; cursor:pointer;">
                        <?php foreach ($closing_years as $cy): ?>
                            <?php 
                            $c_id = (int)$cy['id'];
                            $c_from = (int)$cy['from_year'];
                            $c_to = !empty($cy['to_year']) ? (int)$cy['to_year'] : ($c_from + 1);
                            $c_label = "FY " . $c_from . "-" . substr($c_to, -2);
                            $sel = ($c_id === $selected_closing_id) ? 'selected' : '';
                            ?>
                            <option value="<?php echo $c_id; ?>" <?php echo $sel; ?> style="color:#0f172a; font-weight:600;"><?php echo $c_label; ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
            <?php else: ?>
                <div style="margin-top:8px; font-size:11.5px; font-weight:800; background:rgba(255,255,255,0.15); padding:4px 10px; border-radius:8px; display:inline-block;">
                    <i class="fa fa-calendar"></i> <?php echo $fy_label; ?>
                </div>
            <?php endif; ?>
        </div>

        <!-- KPI Summary Strip -->
        <div class="kpi-grid">
            <div class="kpi-card-sm" style="border-left: 3px solid #ef4444;">
                <div class="kpi-lbl">Total Dues</div>
                <div class="kpi-val" style="color:#dc2626;">₹<?php echo number_format($tot_pending_all, 0); ?></div>
            </div>
            <div class="kpi-card-sm" style="border-left: 3px solid #f59e0b;">
                <div class="kpi-lbl">With Dues</div>
                <div class="kpi-val" style="color:#d97706;"><?php echo $members_with_due_count; ?> / <?php echo $members_total_count; ?></div>
            </div>
            <div class="kpi-card-sm" style="border-left: 3px solid #10b981;">
                <div class="kpi-lbl">Collected (FY)</div>
                <div class="kpi-val" style="color:#059669;">₹<?php echo number_format($total_amount, 0); ?></div>
            </div>
        </div>

        <!-- Segmented Tab Navigation -->
        <div class="seg-tabs-wrapper">
            <button type="button" class="seg-tab-btn active" id="tab_btn_members" onclick="switchMainTab('members')">
                <i class="fa fa-users"></i> Members & Dues (<?php echo $members_total_count; ?>)
            </button>
            <button type="button" class="seg-tab-btn" id="tab_btn_receipts" onclick="switchMainTab('receipts')">
                <i class="fa fa-file-text-o"></i> Receipts Log (<?php echo $total_count; ?>)
            </button>
        </div>

        <!-- ================= TAB 1: MEMBERS & DUES ================= -->
        <div id="section_members">

            <!-- Search Bar -->
            <div class="search-box">
                <i class="fa fa-search"></i>
                <input type="text" id="search_members" placeholder="Search by member name, phone, or group..." onkeyup="filterMembers()">
            </div>

            <!-- Filter Chips -->
            <div class="filter-chips-row">
                <button type="button" class="filter-chip active" onclick="setFilterChip('all', this)">All (<?php echo $members_total_count; ?>)</button>
                <button type="button" class="filter-chip" onclick="setFilterChip('due', this)">With Dues (<?php echo $members_with_due_count; ?>)</button>
                <button type="button" class="filter-chip" onclick="setFilterChip('nodue', this)">No Dues (<?php echo ($members_total_count - $members_with_due_count); ?>)</button>
                <button type="button" class="filter-chip" onclick="setFilterChip('guest', this)">Guests</button>
            </div>

            <!-- Members List Cards -->
            <div id="members_cards_container">
                <?php if (!empty($members_data)): ?>
                    <?php foreach ($members_data as $m): ?>
                        <?php 
                        $mId = (int)$m['id'];
                        $mFullName = trim($m['first_name'] . ' ' . $m['middle_name'] . ' ' . $m['last_name']);
                        $dueVal = (float)$m['pending_balance'];
                        $walletVal = (float)$m['wallet_amount'];
                        $isGuest = ((int)($m['member_type'] ?? 0) === 1);
                        $hasDue = ($dueVal > 0);
                        $groupsStr = !empty($m['group_names']) ? $m['group_names'] : 'Member';
                        $phoneStr = !empty($m['phone']) ? $m['phone'] : '';

                        // Member image path
                        $imgFile = trim((string)($m['img'] ?? ''));
                        $imgSrc = '';
                        if (!empty($imgFile) && $imgFile !== '0' && $imgFile !== 'customer.png') {
                            if (file_exists('../../image_upload/members/uploads/' . $imgFile)) {
                                $imgSrc = '../../image_upload/members/uploads/' . $imgFile;
                            } elseif (file_exists('../../image_upload/member/' . $imgFile)) {
                                $imgSrc = '../../image_upload/member/' . $imgFile;
                            }
                        }
                        $initial = strtoupper(substr(trim($m['first_name']), 0, 1) ?: 'M');

                        // Search string
                        $searchData = strtolower($mFullName . ' ' . $groupsStr . ' ' . $phoneStr);
                        ?>
                        <div class="mem-fee-card member-item-card" 
                             data-search="<?php echo htmlspecialchars($searchData); ?>" 
                             data-has-due="<?php echo $hasDue ? '1' : '0'; ?>"
                             data-is-guest="<?php echo $isGuest ? '1' : '0'; ?>">
                            
                            <!-- Header: Avatar, Name, Group -->
                            <div class="mem-fee-header">
                                <?php if (!empty($imgSrc)): ?>
                                    <img src="<?php echo $imgSrc; ?>" class="mem-avatar-box" alt="<?php echo htmlspecialchars($mFullName); ?>" onerror="this.onerror=null; this.src='../../img/customer.png';">
                                <?php else: ?>
                                    <div class="mem-avatar-box"><?php echo $initial; ?></div>
                                <?php endif; ?>

                                <div class="mem-fee-info">
                                    <h4 class="mem-fee-name">
                                        <?php echo htmlspecialchars($mFullName); ?>
                                        <?php if ($isGuest): ?>
                                            <span class="guest-badge"><i class="fa fa-star" style="color:#f59e0b; font-size:9px;"></i> GUEST</span>
                                        <?php endif; ?>
                                    </h4>
                                    <div class="mem-fee-meta">
                                        <i class="fa fa-users" style="color:#10b981;"></i> <?php echo htmlspecialchars($groupsStr); ?>
                                        <?php if (!empty($phoneStr)): ?>
                                            &bull; <i class="fa fa-phone"></i> <?php echo htmlspecialchars($phoneStr); ?>
                                        <?php endif; ?>
                                    </div>
                                </div>
                            </div>

                            <!-- Balances Row: Pending Dues & Wallet Balance -->
                            <div class="mem-bal-row">
                                <?php if ($hasDue): ?>
                                    <div class="bal-badge-pill pill-due-has">
                                        <span class="pill-tag"><i class="fa fa-clock-o"></i> Pending Due</span>
                                        <span class="pill-val">₹ <?php echo number_format($dueVal, 2); ?></span>
                                    </div>
                                <?php else: ?>
                                    <div class="bal-badge-pill pill-due-zero">
                                        <span class="pill-tag"><i class="fa fa-check-circle"></i> Dues</span>
                                        <span class="pill-val">₹ 0.00 (Clear)</span>
                                    </div>
                                <?php endif; ?>

                                <?php if ($walletVal > 0): ?>
                                    <div class="bal-badge-pill pill-wallet-has">
                                        <span class="pill-tag"><i class="fa fa-credit-card"></i> Wallet</span>
                                        <span class="pill-val">₹ <?php echo number_format($walletVal, 2); ?></span>
                                    </div>
                                <?php else: ?>
                                    <div class="bal-badge-pill pill-wallet-empty">
                                        <span class="pill-tag">Wallet</span>
                                        <span class="pill-val">₹ 0.00</span>
                                    </div>
                                <?php endif; ?>
                            </div>

                            <!-- 4 Action Buttons Row: Pay, Ledger, Receivable, Report -->
                            <div class="mem-actions-grid">
                                <button type="button" onclick="openPayModal(<?php echo $mId; ?>, '<?php echo addslashes($mFullName); ?>', <?php echo $dueVal; ?>, <?php echo $walletVal; ?>)" class="btn-action-fee pay" title="Pay Fees">
                                    <i class="fa fa-money"></i>
                                    <span>Pay</span>
                                </button>
                                <a href="member_ledger.php?member_id=<?php echo $mId; ?>&from=fee_receipts" class="btn-action-fee ledger" title="Member Ledger">
                                    <i class="fa fa-history"></i>
                                    <span>Ledger</span>
                                </a>
                                <button type="button" onclick="openAddReceivableModal(<?php echo $mId; ?>, '<?php echo addslashes($mFullName); ?>', <?php echo $dueVal; ?>, <?php echo $walletVal; ?>)" class="btn-action-fee receivable" title="Add Receivable">
                                    <i class="fa fa-plus-circle"></i>
                                    <span>Receivable</span>
                                </button>
                                <button type="button" onclick="downloadMemberReport(<?php echo $mId; ?>, this)" class="btn-action-fee report" title="Download PDF Payment Report">
                                    <i class="fa fa-file-pdf-o"></i>
                                    <span>Report</span>
                                </button>
                            </div>

                        </div>
                    <?php endforeach; ?>
                <?php else: ?>
                    <div style="text-align:center; padding:40px 20px; color:#94a3b8;">
                        <i class="fa fa-users" style="font-size:36px; margin-bottom:10px; display:block;"></i>
                        No active members found.
                    </div>
                <?php endif; ?>
            </div>

        </div>

        <!-- ================= TAB 2: RECEIPTS LOG ================= -->
        <div id="section_receipts" style="display:none;">

            <!-- Search Bar -->
            <div class="search-box">
                <i class="fa fa-search"></i>
                <input type="text" id="search_receipts" placeholder="Search by member name, category, or date..." onkeyup="filterReceipts()">
            </div>

            <!-- Receipts List -->
            <div id="receipts_container">
                <?php if ($res && $res->num_rows > 0): ?>
                    <?php while ($r = $res->fetch_assoc()): ?>
                        <?php 
                        $name = trim($r['first_name'] . ' ' . $r['middle_name'] . ' ' . $r['last_name']);
                        $desc_raw = getFormattedReceiptDescription($r['discription'] ?? '', $r['rec_discription'] ?? '', $r['head_name'] ?? '', $r['date'] ?? '', $r['rec_date'] ?? '');
                        $desc = htmlspecialchars($desc_raw);
                        $date_formatted = ($r['date'] && $r['date'] !== '0000-00-00' && strtotime($r['date']) !== false) ? date('d M Y', strtotime($r['date'])) : '--';
                        $amount_formatted = number_format((float)$r['amount'], 2);
                        $group = htmlspecialchars($r['group_names'] ?: 'Member');
                        $mode = ((int)$r['transaction_type'] === 2) ? 'Bank' : 'Cash';
                        $badge_class = ((int)$r['transaction_type'] === 2) ? 'rcp-badge-bank' : 'rcp-badge-cash';
                        
                        $rImg = trim((string)($r['img'] ?? ''));
                        $rImgSrc = '';
                        if (!empty($rImg)) {
                            if (file_exists('../../image_upload/members/uploads/' . $rImg)) {
                                $rImgSrc = '../../image_upload/members/uploads/' . $rImg;
                            } elseif (file_exists('../../image_upload/member/' . $rImg)) {
                                $rImgSrc = '../../image_upload/member/' . $rImg;
                            }
                        }
                        $rInitial = strtoupper(substr($r['first_name'], 0, 1));
                        ?>
                        <div class="rcp-card rcp-item-row" data-search="<?php echo strtolower($name . ' ' . $desc . ' ' . $date_formatted . ' ' . $group); ?>">
                            <div class="rcp-header">
                                <div class="rcp-date"><i class="fa fa-calendar"></i> <?php echo $date_formatted; ?></div>
                                <span class="rcp-badge <?php echo $badge_class; ?>"><?php echo $mode; ?></span>
                            </div>
                            <div class="rcp-body" onclick="location.href='member_receivable.php?member_id=<?php echo $r['member_id']; ?>'" style="cursor:pointer;">
                                <?php if (!empty($rImgSrc)): ?>
                                    <img src="<?php echo $rImgSrc; ?>" class="rcp-avatar" alt="<?php echo htmlspecialchars($name); ?>" onerror="this.onerror=null; this.src='../../img/customer.png';">
                                <?php else: ?>
                                    <div class="rcp-avatar"><?php echo $rInitial; ?></div>
                                <?php endif; ?>
                                <div class="rcp-info">
                                    <h4 class="rcp-name"><?php echo htmlspecialchars($name); ?></h4>
                                    <div class="rcp-meta"><i class="fa fa-tag" style="color:#10b981;"></i> <?php echo $desc; ?> &bull; <?php echo $group; ?></div>
                                </div>
                                <div class="rcp-amt">
                                    +₹<?php echo $amount_formatted; ?>
                                </div>
                            </div>
                            <div class="rcp-footer">
                                <span class="rcp-num">Receipt #REC-<?php echo str_pad($r['id'], 4, '0', STR_PAD_LEFT); ?></span>
                                <div style="display:flex; gap:6px;">
                                    <a href="member_receivable.php?member_id=<?php echo $r['member_id']; ?>" class="rcp-btn-view" style="background:rgba(59,130,246,0.1); color:#2563eb; text-decoration:none;">
                                        <i class="fa fa-user"></i> Dues
                                    </a>
                                    <button class="rcp-btn-view" onclick="openReceiptModal('<?php echo $r['id']; ?>', '<?php echo addslashes($name); ?>', '<?php echo addslashes($group); ?>', '<?php echo $date_formatted; ?>', '<?php echo addslashes($desc); ?>', '<?php echo $amount_formatted; ?>', '<?php echo (float)$r['amount']; ?>', '<?php echo $mode; ?>', '<?php echo addslashes($desc); ?>')">
                                        <i class="fa fa-print"></i> View
                                    </button>
                                </div>
                            </div>
                        </div>
                    <?php endwhile; ?>
                <?php else: ?>
                    <div style="text-align:center; padding:40px 20px; color:#94a3b8;">
                        <i class="fa fa-file-text-o" style="font-size:36px; margin-bottom:10px; display:block;"></i>
                        No fee receipts recorded for <?php echo $fy_label; ?>.
                    </div>
                <?php endif; ?>
            </div>

        </div>

    </div>

    <!-- ================= ADD RECEIVABLE MODAL ================= -->
    <div class="modal fade" id="recievebleModel" tabindex="-1" role="dialog" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered" role="document">
            <div class="modal-content" style="border-radius:24px; border:none; box-shadow:0 25px 50px -12px rgba(0,0,0,0.25); overflow:hidden;">
                <form method="POST" id="recieveble_form" onsubmit="event.preventDefault(); saveRecieveble();">
                    <input type="hidden" id="hdn_rec_member_id" name="member_id" value="0">
                    <input type="hidden" id="hdn_rec_member_wallet" value="0">

                    <div class="modal-header" style="background: linear-gradient(135deg, #8b5cf6 0%, #6d28d9 100%); padding: 18px 20px; color: #ffffff; border-bottom: none;">
                        <button type="button" class="close" data-dismiss="modal" style="color:#ffffff; opacity:0.8; font-size:22px;">&times;</button>
                        <h4 style="margin: 0; font-weight: 800; font-size: 17px;"><i class="fa fa-plus-circle"></i> Add Receivable Fee</h4>
                        <div id="modal_rec_member_name" style="margin-top: 4px; font-size: 13px; opacity: 0.95; font-weight: 700;">--</div>
                    </div>

                    <div class="modal-body" style="padding: 18px 20px; max-height: 70vh; overflow-y: auto; background:#ffffff;">
                        
                        <!-- Mini Balance Strip inside Modal -->
                        <div style="background:#f8fafc; border:1px solid #e2e8f0; border-radius:12px; padding:8px 12px; display:flex; justify-content:space-between; margin-bottom:14px; font-size:12px;">
                            <div><span style="color:#64748b; font-weight:700;">Current Due:</span> <strong id="modal_rec_curr_due" style="color:#dc2626;">₹0.00</strong></div>
                            <div><span style="color:#64748b; font-weight:700;">Wallet:</span> <strong id="modal_rec_curr_wallet" style="color:#059669;">₹0.00</strong></div>
                        </div>

                        <div class="row">
                            <div class="col-xs-6" style="padding-right:6px; margin-bottom:12px;">
                                <label>Receivable Fee (₹)*</label>
                                <input type="number" step="0.01" id="receiveble" name="receiveble" placeholder="Amount (e.g. 500)" class="form-control-mob" required>
                            </div>
                            <div class="col-xs-6" style="padding-left:6px; margin-bottom:12px;">
                                <label>Paid Now (₹)</label>
                                <input type="number" step="0.01" id="received" name="received" placeholder="Paid now (optional)" class="form-control-mob">
                            </div>
                        </div>

                        <div class="row">
                            <div class="col-xs-6" style="padding-right:6px; margin-bottom:12px;">
                                <label>Receivable Date*</label>
                                <input type="date" id="txt_receiveble_date" name="receiveble_date" value="<?php echo date('Y-m-d'); ?>" class="form-control-mob" required>
                            </div>
                            <div class="col-xs-6" style="padding-left:6px; margin-bottom:12px;">
                                <label>Received Date</label>
                                <input type="date" id="txt_received_date" name="received_date" value="<?php echo date('Y-m-d'); ?>" class="form-control-mob">
                            </div>
                        </div>

                        <!-- Dynamic Wallet Deduction option -->
                        <div id="modal_rec_wallet_opt" style="margin-bottom:12px; display:none;">
                            <label class="head-check-card" style="display:flex; align-items:center; gap:8px; background:#ecfdf5; border:1.5px solid #10b981; border-radius:12px; padding:10px 12px; cursor:pointer; margin:0;">
                                <input type="checkbox" id="use_wallet" name="use_wallet" value="1" style="width:18px; height:18px; margin:0; cursor:pointer;">
                                <span style="font-size:12px; font-weight:800; color:#065f46;">
                                    <i class="fa fa-credit-card"></i> Pay received amount from Wallet (<span id="modal_rec_wallet_lbl">₹0.00</span> available)
                                </span>
                            </label>
                        </div>

                        <div style="margin-bottom:12px;">
                            <label>Payment Head*</label>
                            <select id="selected_recieveble_head" name="head" class="form-control-mob" required>
                                <option value="" disabled selected>-- Select Fee Category --</option>
                                <?php foreach ($heads_list as $head): ?>
                                    <option value="<?php echo $head['id']; ?>" <?php echo (strtolower($head['name']) === 'monthly fee') ? 'selected' : ''; ?>>
                                        <?php echo htmlspecialchars(ucwords($head['name'])); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>

                        <div class="row">
                            <div class="col-xs-6" style="padding-right:6px; margin-bottom:12px;">
                                <label>Payment Mode</label>
                                <select id="selected_transaction" name="selected_transaction" class="form-control-mob">
                                    <option value="1">Cash</option>
                                    <option value="2">Bank / UPI</option>
                                </select>
                            </div>
                            <div class="col-xs-6" style="padding-left:6px; margin-bottom:12px;">
                                <label>Financial Year*</label>
                                <select id="selected_year" name="flag" class="form-control-mob" required>
                                    <?php foreach ($closing_years as $cy): ?>
                                        <?php 
                                        $c_id = (int)$cy['id'];
                                        $c_from = (int)$cy['from_year'];
                                        $c_to = !empty($cy['to_year']) ? (int)$cy['to_year'] : ($c_from + 1);
                                        $c_label = "FY " . $c_from . "-" . substr($c_to, -2);
                                        $sel = ($c_id === $selected_closing_id) ? 'selected' : '';
                                        ?>
                                        <option value="<?php echo $c_id; ?>" <?php echo $sel; ?>><?php echo $c_label; ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                        </div>

                        <div style="margin-bottom:6px;">
                            <label>Description (Optional)</label>
                            <textarea id="txt_reciveble_discription" name="discription" rows="2" placeholder="e.g. Monthly Badminton Fee for current month" class="form-control-mob" maxlength="150"></textarea>
                        </div>

                    </div>

                    <div class="modal-footer" style="border-top:1px solid #e2e8f0; padding:12px 20px; display:flex; justify-content:flex-end; gap:8px;">
                        <button type="button" class="btn btn-light" data-dismiss="modal" style="border-radius:10px; font-weight:700; padding:8px 16px;">Cancel</button>
                        <button type="submit" id="btn_save_receivable" class="btn btn-primary" style="border-radius:10px; font-weight:800; padding:8px 22px; background:linear-gradient(135deg, #8b5cf6, #6d28d9); border:none;">
                            <i class="fa fa-check"></i> Save Receivable
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- ================= PRINTABLE RECEIPT MODAL ================= -->
    <div class="modal fade" id="viewReceiptModal" tabindex="-1" role="dialog" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered" role="document">
            <div class="modal-content" style="border-radius:24px; border:none; background:#f8fafc;">
                <div class="modal-header" style="border-bottom:1px solid #e2e8f0; padding:16px 20px;">
                    <h5 class="modal-title" style="font-weight:800; color:#0f172a; font-size:16px;">
                        <i class="fa fa-file-text" style="color:#10b981;"></i> Fee Receipt Preview
                    </h5>
                    <button type="button" class="close" data-dismiss="modal">&times;</button>
                </div>
                <div class="modal-body" style="padding:20px;">
                    
                    <div class="printable-receipt" id="printable_receipt_area">
                        <i class="fa fa-certificate receipt-watermark"></i>
                        <div style="text-align:center; border-bottom:2px solid #10b981; padding-bottom:12px; margin-bottom:14px;">
                            <h3 style="margin:0; font-weight:800; font-size:17px; color:#059669;">YMCA BADMINTON CLUB</h3>
                            <p style="margin:2px 0 0 0; font-size:11px; color:#64748b; font-weight:600;">POOVATHUSSERY — OFFICIAL FEE RECEIPT</p>
                        </div>

                        <div style="display:flex; justify-content:space-between; margin-bottom:12px; font-size:12px;">
                            <div><strong>Receipt No:</strong> <span id="m_rec_no" style="color:#059669; font-weight:800;">#REC-0000</span></div>
                            <div><strong>Date:</strong> <span id="m_rec_date">--</span></div>
                        </div>

                        <div style="background:#f1f5f9; border-radius:12px; padding:12px; margin-bottom:12px; font-size:12.5px;">
                            <div style="margin-bottom:4px;"><strong>Received From:</strong> <span id="m_rec_name" style="font-weight:700; color:#0f172a;">--</span></div>
                            <div style="margin-bottom:4px;"><strong>Group:</strong> <span id="m_rec_group">--</span></div>
                            <div style="margin-bottom:4px;"><strong>Payment Category:</strong> <span id="m_rec_head" style="color:#059669; font-weight:700;">--</span></div>
                            <div><strong>Payment Mode:</strong> <span id="m_rec_mode" class="rcp-badge rcp-badge-cash">--</span></div>
                        </div>

                        <div style="font-size:12px; color:#475569; margin-bottom:14px;">
                            <strong>Particulars:</strong> <span id="m_rec_desc">--</span>
                        </div>

                        <div style="background:#ecfdf5; border:1.5px solid #10b981; border-radius:12px; padding:12px 16px; display:flex; justify-content:space-between; align-items:center;">
                            <div>
                                <div style="font-size:10px; font-weight:800; color:#047857; text-transform:uppercase;">Amount Received</div>
                                <div id="m_rec_words" style="font-size:10.5px; font-weight:700; color:#065f46; margin-top:2px;">--</div>
                            </div>
                            <div id="m_rec_amt" style="font-size:20px; font-weight:800; color:#047857;">₹0.00</div>
                        </div>

                        <div style="margin-top:20px; display:flex; justify-content:space-between; align-items:flex-end; font-size:11px; color:#64748b;">
                            <div>Status: <span style="color:#10b981; font-weight:800;"><i class="fa fa-check-circle"></i> VERIFIED PAID</span></div>
                            <div style="text-align:right;">
                                <div style="font-weight:800; color:#0f172a;">Authorized Signature</div>
                                YMCA Management
                            </div>
                        </div>
                    </div>

                </div>
                <div class="modal-footer" style="border-top:1px solid #e2e8f0; padding:12px 20px;">
                    <button type="button" class="btn btn-light" data-dismiss="modal" style="border-radius:10px; font-weight:700;">Close</button>
                    <button type="button" class="btn btn-success" onclick="printReceiptArea()" style="border-radius:10px; font-weight:800;">
                        <i class="fa fa-print"></i> Print Receipt
                    </button>
                </div>
            </div>
        </div>
    </div>

    <!-- ================= QUICK PAY MEMBER SELECTOR MODAL ================= -->
    <div class="modal fade" id="recordPaymentModal" tabindex="-1" role="dialog" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered" role="document">
            <div class="modal-content" style="border-radius:24px; border:none; box-shadow:0 25px 50px -12px rgba(0,0,0,0.25);">
                <div class="modal-header" style="border-bottom:1px solid #f1f5f9; padding:16px 20px;">
                    <h5 class="modal-title" style="font-weight:800; color:#0f172a;"><i class="fa fa-user-plus" style="color:#10b981;"></i> Select Member to Pay Fees</h5>
                    <button type="button" class="close" data-dismiss="modal">&times;</button>
                </div>
                <div class="modal-body" style="padding:16px;">
                    <input type="text" class="form-control-mob" id="search_rec_member" placeholder="Search member by name or phone..." onkeyup="filterRecordMemberPicker()" style="margin-bottom:12px;">
                    <div style="max-height:340px; overflow-y:auto;" id="record_member_picker_list">
                        <?php if (!empty($members_data)): ?>
                            <?php foreach ($members_data as $rm): ?>
                                <?php 
                                $rmName = trim($rm['first_name'] . ' ' . $rm['middle_name'] . ' ' . $rm['last_name']); 
                                $rmDue = (float)$rm['pending_balance'];
                                $rmWal = (float)($rm['wallet_amount'] ?? 0);
                                ?>
                                <div class="mem-pick-row record-mem-row" onclick="openPayModal(<?php echo $rm['id']; ?>, '<?php echo addslashes($rmName); ?>', <?php echo $rmDue; ?>, <?php echo $rmWal; ?>); $('#recordPaymentModal').modal('hide');" style="padding:12px 14px; border-bottom:1px solid #f1f5f9; display:flex; align-items:center; justify-content:space-between; cursor:pointer;">
                                    <div>
                                        <div style="font-size:14px; font-weight:800; color:#0f172a;"><?php echo htmlspecialchars($rmName); ?></div>
                                        <div style="font-size:11px; color:#64748b; font-weight:600;">
                                            <?php echo htmlspecialchars($rm['phone'] ?: 'No Phone'); ?>
                                            <?php if ($rmDue > 0): ?>
                                                &bull; <span style="color:#dc2626; font-weight:800;">Due: ₹<?php echo number_format($rmDue, 2); ?></span>
                                            <?php endif; ?>
                                        </div>
                                    </div>
                                    <span style="font-size:12px; font-weight:800; color:#059669; background:#ecfdf5; padding:4px 10px; border-radius:8px;">
                                        Pay <i class="fa fa-chevron-right"></i>
                                    </span>
                                </div>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- ================= QUICK PAY MODAL (Matching directory/fees_receiveble) ================= -->
    <div class="modal fade" id="quickPayModal" tabindex="-1" role="dialog" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered" role="document">
            <div class="modal-content" style="border-radius:24px; border:none; box-shadow:0 25px 50px -12px rgba(0,0,0,0.25); max-height:92vh; display:flex; flex-direction:column;">
                <div class="modal-header" style="border-bottom:1px solid #f1f5f9; padding:16px 20px; background:#ffffff; flex-shrink:0;">
                    <div style="display:flex; align-items:center; justify-content:space-between; width:100%;">
                        <div>
                            <h4 style="font-weight:800; color:#0f172a; margin:0 0 2px 0; font-size:16px;" id="qpay_member_name">Member Name</h4>
                            <div style="display:flex; gap:8px; align-items:center; margin-top:4px;">
                                <span class="badge" id="qpay_due_badge" style="background:#fef2f2; color:#dc2626; border:1px solid rgba(239,68,68,0.2); font-size:11px; font-weight:800; padding:4px 8px; border-radius:8px;">Due: ₹0.00</span>
                                <span class="badge" id="qpay_wallet_badge" style="background:#ecfdf5; color:#059669; border:1px solid rgba(16,185,129,0.25); font-size:11px; font-weight:800; padding:4px 8px; border-radius:8px;">Wallet: ₹0.00</span>
                            </div>
                        </div>
                        <button type="button" class="close" data-dismiss="modal" style="font-size:24px; color:#64748b; opacity:0.8;">&times;</button>
                    </div>
                </div>

                <div class="modal-body" style="padding:16px; overflow-y:auto; flex:1;">
                    <!-- Tools Panel matching directory/fees_receiveble -->
                    <div class="tool-panel-container">
                        <!-- Cash/Bank Setoff Panel -->
                        <div class="tool-card-box">
                            <h4><i class="fa fa-money" style="color:#10b981;"></i> Set-Off Receivable (Cash/Bank)</h4>
                            <div class="action-input-group">
                                <input type="number" placeholder="Enter amount to pay" id="quick_txt_AmountPaid" name="submit" class="form-control" step="0.01">
                                <div class="action-btn-group">
                                    <button class="btn-set-fees-custom" onclick="quickSetFees()" type="button">Set Fees</button>
                                    <button type="button" id="quick_setoff_button" class="btn-setoff-custom" onclick="quickSetOffForm()" style="display:none;">SetOff</button>
                                </div>
                            </div>
                            <div id="quick_excess_wallet_msg" style="display:none; margin-top:10px;"></div>
                        </div>

                        <!-- Wallet Setoff Panel -->
                        <div class="tool-card-box">
                            <h4><i class="fa fa-credit-card" style="color:#6366f1;"></i> Set-Off Receivable (Wallet)</h4>
                            <div class="action-input-group">
                                <input type="number" placeholder="Wallet set-off amount" id="quick_txt_wallet_amount_input" name="wallet" class="form-control" step="0.01">
                                <div class="action-btn-group">
                                    <button class="btn-set-fees-custom" onclick="quickSetFeesFromWallet()" id="quick_setFeesFromWallet_button" type="button">Set Fees</button>
                                    <button type="button" id="quick_setoff_button_wallet" class="btn-setoff-custom" onclick="quickSetOffFormWallet()" style="display:none;">SetOff</button>
                                </div>
                            </div>
                            <div id="quick_add_from_wallet" style="margin-top:-2px;">
                                <span style="font-size:11.5px; font-weight:700; color:#6366f1; background:rgba(99,102,241,0.08); padding:5px 12px; border-radius:10px; display:inline-flex; align-items:center; gap:6px;">
                                    <i class="fa fa-credit-card"></i> Available Wallet Balance: <strong id="quick_txt_wallet_avail">₹0.00</strong>
                                </span>
                            </div>
                        </div>
                    </div>

                    <!-- Pending Receivables Breakdown -->
                    <div style="margin: 14px 0 8px 0; display:flex; justify-content:space-between; align-items:center;">
                        <h5 style="font-size:13px; font-weight:800; color:#0f172a; margin:0; text-transform:uppercase;">Pending Receivables Log</h5>
                        <span id="quick_dues_count" style="font-size:11px; font-weight:700; color:#64748b;">0 Records</span>
                    </div>
                    <div id="quick_receivables_container">
                        <div style="text-align:center; padding:20px; color:#94a3b8;">
                            <i class="fa fa-spinner fa-spin" style="font-size:20px; margin-bottom:6px; display:block;"></i>
                            Loading receivables...
                        </div>
                    </div>

                    <!-- Link to full page -->
                    <a id="link_quick_full_ledger" href="#" class="btn btn-block" style="border:1.5px solid #e2e8f0; border-radius:12px; font-weight:800; font-size:12px; color:#6366f1; padding:10px; margin-top:14px; text-decoration:none; display:flex; align-items:center; justify-content:center; gap:6px; background:#f8fafc;">
                        <i class="fa fa-external-link"></i> Open Full Receivables Ledger & Page
                    </a>
                </div>
            </div>
        </div>
    </div>

    <!-- modal for quick setoff date/transaction type (Cash/Bank) -->
    <div class="modal inmodal fade" id="quickSetOffModel" tabindex="-1" role="dialog" aria-hidden="true" style="z-index: 1060;">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content" style="border-radius: 20px; overflow: hidden; border: none; box-shadow: 0 20px 40px rgba(0,0,0,0.25);">
                <form method="POST" id="quick_setoff_form" onsubmit="event.preventDefault();">
                    <div class="modal-header" style="background: linear-gradient(135deg, #6366f1 0%, #4f46e5 100%); padding: 18px 22px; color: #ffffff; text-align: left;">
                        <button type="button" class="close" data-dismiss="modal" style="color: #ffffff; opacity: 0.9; font-size: 24px;" onclick="$('#quickSetOffModel').modal('hide');">&times;</button>
                        <h4 style="margin: 0; font-weight: 800; font-size: 17px;"><i class="fa fa-exchange"></i> Set-Off Cash/Bank</h4>
                        <p style="margin: 3px 0 0 0; opacity: 0.9; font-size: 12px;">Finalize date and transaction parameters for chronological allocation</p>
                    </div>

                    <div class="modal-body" style="padding: 20px 22px; background: #ffffff;">
                        <div id="quick_modal_excess_info" style="display:none; margin-bottom:14px;"></div>
                        <div class="form-group" style="margin-bottom: 14px;">
                            <label style="font-size:11px; font-weight:800; color:#64748b; text-transform:uppercase;">Payment Date</label>
                            <input type="date" id="quick_setoff_date" name="quick_setoff_date" value="<?php echo date('Y-m-d'); ?>" class="form-control" style="border-radius:12px; font-weight:700;">
                        </div>
                        
                        <div class="form-group" style="margin-bottom: 14px;">
                            <label style="font-size:11px; font-weight:800; color:#64748b; text-transform:uppercase;">Transaction Type</label>
                            <select class="dropdown form-control" name="quick_selected_transaction" id="quick_selected_transaction_type" style="border-radius:12px; font-weight:700;">
                                <?php foreach (TransactionType::all() as $key => $label): ?>
                                    <option value="<?= $key ?>"><?= htmlspecialchars($label) ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>

                        <div class="form-group" style="margin-top: 14px; margin-bottom: 6px;">
                            <label style="font-size:11px; font-weight:800; color:#64748b; text-transform:uppercase;">Upload Bill Photo (Optional)</label>
                            <input type="file" id="quick_bill_photo" name="quick_bill_photo" class="form-control" accept="image/*,application/pdf" style="padding: 6px 10px; border-radius: 12px; height:auto;">
                        </div>
                    </div>
                    
                    <div class="modal-footer" style="background: #ffffff; border-top: 1px solid #f1f5f9; padding: 14px 22px; display: flex; justify-content: flex-end; gap: 10px;">
                        <button type="button" class="btn btn-default" style="border-radius: 10px; font-weight: 700; padding: 8px 16px;" onclick="$('#quickSetOffModel').modal('hide');">Close</button>
                        <button type="button" class="btn btn-primary" style="background: linear-gradient(135deg, #6366f1 0%, #4f46e5 100%); border: none; border-radius: 10px; font-weight: 800; padding: 8px 20px;" onclick="quickSubmitSetOff();">Save & Settle</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- modal for quick setoff date/transaction type (Wallet) -->
    <div class="modal inmodal fade" id="quickSetOffwalletModel" tabindex="-1" role="dialog" aria-hidden="true" style="z-index: 1060;">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content" style="border-radius: 20px; overflow: hidden; border: none; box-shadow: 0 20px 40px rgba(0,0,0,0.25);">
                <form method="POST" id="quick_setoff_form_wallet" onsubmit="event.preventDefault();">
                    <div class="modal-header" style="background: linear-gradient(135deg, #6366f1 0%, #4f46e5 100%); padding: 18px 22px; color: #ffffff; text-align: left;">
                        <button type="button" class="close" data-dismiss="modal" style="color: #ffffff; opacity: 0.9; font-size: 24px;" onclick="$('#quickSetOffwalletModel').modal('hide');">&times;</button>
                        <h4 style="margin: 0; font-weight: 800; font-size: 17px;"><i class="fa fa-credit-card"></i> Set-Off From Wallet</h4>
                        <p style="margin: 3px 0 0 0; opacity: 0.9; font-size: 12px;">Confirm date and wallet deduction parameters</p>
                    </div>

                    <div class="modal-body" style="padding: 20px 22px; background: #ffffff;">
                        <div class="form-group" style="margin-bottom: 14px;">
                            <label style="font-size:11px; font-weight:800; color:#64748b; text-transform:uppercase;">Deduction Date</label>
                            <input type="date" id="quick_setoff_date_wallet" name="quick_setoff_date_wallet" value="<?php echo date('Y-m-d'); ?>" class="form-control" style="border-radius:12px; font-weight:700;">
                        </div>
                        
                        <div class="form-group" style="margin-bottom: 6px;">
                            <label style="font-size:11px; font-weight:800; color:#64748b; text-transform:uppercase;">Transaction Type</label>
                            <select class="dropdown form-control" name="quick_selected_transaction" id="quick_selected_transaction_type_wallet" style="border-radius:12px; font-weight:700;">
                                <?php foreach (TransactionType::all() as $key => $label): ?>
                                    <option value="<?= $key ?>"><?= htmlspecialchars($label) ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                    </div>
                    
                    <div class="modal-footer" style="background: #ffffff; border-top: 1px solid #f1f5f9; padding: 14px 22px; display: flex; justify-content: flex-end; gap: 10px;">
                        <button type="button" class="btn btn-default" style="border-radius: 10px; font-weight: 700; padding: 8px 16px;" onclick="$('#quickSetOffwalletModel').modal('hide');">Close</button>
                        <button type="button" class="btn btn-primary" style="background: linear-gradient(135deg, #6366f1 0%, #4f46e5 100%); border: none; border-radius: 10px; font-weight: 800; padding: 8px 20px;" onclick="quickSubmitSetOffWallet();">Save & Settle</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
    <!-- modal for quick setoff date/transaction type (Wallet) end -->

    <!-- Quick Edit Receivable Modal -->
    <div class="modal inmodal fade" id="quickEditReceivebleModel" tabindex="-1" role="dialog" aria-hidden="true" style="z-index: 1070;">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content" style="border-radius: 20px; overflow: hidden; border: none; box-shadow: 0 20px 40px rgba(0,0,0,0.25);">
                <form method="POST" id="quick_receiveble_form" onsubmit="event.preventDefault();">
                    <input type="hidden" id="quick_rec_id" name="quick_rec_id">
                    <div class="modal-header" style="background: linear-gradient(135deg, #6366f1 0%, #4f46e5 100%); padding: 18px 22px; color: #ffffff; text-align: left;">
                        <button type="button" class="close" data-dismiss="modal" style="color: #ffffff; opacity: 0.9; font-size: 24px;" onclick="$('#quickEditReceivebleModel').modal('hide');">&times;</button>
                        <h4 style="margin: 0; font-weight: 800; font-size: 17px;"><i class="fa fa-pencil"></i> Edit Receivable Amount</h4>
                        <p style="margin: 3px 0 0 0; opacity: 0.9; font-size: 12px;">Modify date, amount or description parameters</p>
                    </div>
                    <div class="modal-body" style="padding: 20px 22px; background: #ffffff;">
                        <div class="form-group" style="margin-bottom: 14px;">
                            <label style="font-size:11px; font-weight:800; color:#64748b; text-transform:uppercase;">Date</label>
                            <input type="date" id="quick_rec_date" name="quick_rec_date" class="form-control" style="border-radius:12px; font-weight:700;">
                        </div>
                        <div class="form-group" style="margin-bottom: 14px;">
                            <label style="font-size:11px; font-weight:800; color:#64748b; text-transform:uppercase;">Amount</label>
                            <input type="number" id="quick_rec_amount" name="quick_rec_amount" placeholder="Receivable amount" class="form-control" step="0.01" style="border-radius:12px; font-weight:700;">
                        </div>
                        <div class="form-group" style="margin-bottom: 6px;">
                            <label style="font-size:11px; font-weight:800; color:#64748b; text-transform:uppercase;">Description</label>
                            <textarea id="quick_rec_discription" name="quick_rec_discription" rows="3" placeholder="Maximum 150 characters" class="form-control" style="border-radius:12px; font-weight:600;"></textarea>
                        </div>
                    </div>
                    <div class="modal-footer" style="background: #ffffff; border-top: 1px solid #f1f5f9; padding: 14px 22px; display: flex; justify-content: flex-end; gap: 10px;">
                        <button type="button" class="btn btn-default" style="border-radius: 10px; font-weight: 700; padding: 8px 16px;" onclick="$('#quickEditReceivebleModel').modal('hide');">Close</button>
                        <button type="button" class="btn btn-primary" style="background: linear-gradient(135deg, #6366f1 0%, #4f46e5 100%); border: none; border-radius: 10px; font-weight: 800; padding: 8px 20px;" onclick="quickSaveReceiveble();">Save</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
    <!-- Quick Edit Receivable Modal end -->

    <!-- Mobile Bottom Navigation (5 Tabs) -->
    <?php include 'mobile_bottom_nav.php'; ?>

    <script src="../../js/jquery-3.1.1.min.js"></script>
    <script src="../../js/bootstrap.min.js"></script>
    <script src="../../js/plugins/sweetalert/sweetalert.min.js"></script>
    <script src="../../app_js/sweetalert-finez.js"></script>

    <script>
    // Tab Switching
    function switchMainTab(tab) {
        if (tab === 'members') {
            $('#tab_btn_members').addClass('active');
            $('#tab_btn_receipts').removeClass('active');
            $('#section_members').show();
            $('#section_receipts').hide();
        } else {
            $('#tab_btn_receipts').addClass('active');
            $('#tab_btn_members').removeClass('active');
            $('#section_receipts').show();
            $('#section_members').hide();
        }
    }

    // Member Filter by Search and Chips
    let currentChip = 'all';

    function setFilterChip(chip, elem) {
        currentChip = chip;
        $('.filter-chip').removeClass('active');
        $(elem).addClass('active');
        filterMembers();
    }

    function filterMembers() {
        let q = ($('#search_members').val() || '').toLowerCase().trim();

        $('.member-item-card').each(function() {
            let $card = $(this);
            let s = $card.data('search') || '';
            let hasDue = $card.data('has-due') == '1';
            let isGuest = $card.data('is-guest') == '1';

            let matchesSearch = (q === '' || s.indexOf(q) !== -1);
            let matchesChip = true;

            if (currentChip === 'due') {
                matchesChip = hasDue;
            } else if (currentChip === 'nodue') {
                matchesChip = !hasDue;
            } else if (currentChip === 'guest') {
                matchesChip = isGuest;
            }

            if (matchesSearch && matchesChip) {
                $card.show();
            } else {
                $card.hide();
            }
        });
    }

    // Receipts filter
    function filterReceipts() {
        let q = $('#search_receipts').val().toLowerCase().trim();
        $('.rcp-item-row').each(function() {
            let s = $(this).data('search') || '';
            if (q === '' || s.indexOf(q) !== -1) {
                $(this).show();
            } else {
                $(this).hide();
            }
        });
    }

    // Modal Member Picker search
    function filterRecordMemberPicker() {
        let q = $('#search_rec_member').val().toLowerCase().trim();
        $('.record-mem-row').each(function() {
            let t = $(this).text().toLowerCase();
            if (q === '' || t.indexOf(q) !== -1) $(this).show(); else $(this).hide();
        });
    }

    // Open Add Receivable Modal
    function openAddReceivableModal(memberId, memberName, dueVal, walletVal) {
        $('#hdn_rec_member_id').val(memberId);
        $('#hdn_rec_member_wallet').val(walletVal);
        $('#modal_rec_member_name').text(memberName);
        $('#modal_rec_curr_due').text('₹ ' + parseFloat(dueVal).toFixed(2));
        $('#modal_rec_curr_wallet').text('₹ ' + parseFloat(walletVal).toFixed(2));
        
        // Reset form inputs
        $('#receiveble').val('');
        $('#received').val('');
        $('#txt_receiveble_date').val('<?php echo date('Y-m-d'); ?>');
        $('#txt_received_date').val('<?php echo date('Y-m-d'); ?>');
        $('#txt_reciveble_discription').val('');
        $('#use_wallet').prop('checked', false);

        if (walletVal > 0) {
            $('#modal_rec_wallet_lbl').text('₹ ' + parseFloat(walletVal).toFixed(2));
            $('#modal_rec_wallet_opt').show();
        } else {
            $('#modal_rec_wallet_opt').hide();
        }

        $('#recievebleModel').modal('show');
    }

    // Save Receivable via AJAX
    function saveRecieveble() {
        let memberId = $('#hdn_rec_member_id').val();
        let receiveble = $('#receiveble').val();
        let received = $('#received').val() || 0;
        let receiveble_date = $('#txt_receiveble_date').val();
        let received_date = $('#txt_received_date').val() || receiveble_date;
        let head = $('#selected_recieveble_head').val();
        let flag = $('#selected_year').val();
        let discription = $('#txt_reciveble_discription').val();
        let transaction_type = $('#selected_transaction').val();
        let isWalletChecked = $('#use_wallet').is(':checked');
        let walletAmount = parseFloat($('#hdn_rec_member_wallet').val()) || 0;

        if (!receiveble || parseFloat(receiveble) <= 0) {
            swal("Invalid Amount", "Please enter a valid receivable fee amount.", "warning");
            return;
        }

        if (!receiveble_date) {
            swal("Date Required", "Please select a receivable date.", "warning");
            return;
        }

        if (!head) {
            swal("Category Required", "Please select a payment head category.", "warning");
            return;
        }

        if (isWalletChecked) {
            if (parseFloat(received) <= 0) {
                swal("Amount Required", "Please enter the amount paid now to deduct from wallet.", "warning");
                return;
            }
            if (parseFloat(received) > walletAmount) {
                swal("Insufficient Wallet", "Paid amount (₹" + received + ") exceeds available wallet balance (₹" + walletAmount.toFixed(2) + ").", "error");
                return;
            }
        }

        swal({
            title: "Confirm Receivable",
            text: "Do you want to save this receivable fee entry for this member?",
            type: "info",
            showCancelButton: true,
            confirmButtonColor: "#8b5cf6",
            confirmButtonText: "Yes, Save!",
            cancelButtonText: "Cancel",
            closeOnConfirm: false
        }, function(isConfirm) {
            if (isConfirm) {
                let data = {
                    action: isWalletChecked ? 'save_payment_from_wallet' : 'save_recieveble',
                    member_id: memberId,
                    receiveble: receiveble,
                    received: received,
                    receiveble_date: receiveble_date,
                    received_date: received_date,
                    head: head,
                    flag: flag,
                    discription: discription,
                    transaction_type: transaction_type
                };

                $.ajax({
                    type: "POST",
                    url: "../api/member_fees.php",
                    data: data,
                    success: function(response) {
                        $('#recievebleModel').modal('hide');
                        swal({
                            title: "Saved!",
                            text: "Receivable fee has been saved successfully.",
                            type: "success"
                        }, function() {
                            location.reload();
                        });
                    },
                    error: function(xhr) {
                        let errMsg = "Unable to save receivable entry.";
                        try {
                            let obj = JSON.parse(xhr.responseText);
                            if (obj.Message) errMsg = obj.Message;
                        } catch(e) {}
                        swal("Error", errMsg, "error");
                    }
                });
            }
        });
    }

    // PDF Payment Report Downloader
    function downloadMemberReport(memberId, btn) {
        let $btn = $(btn);
        let origHtml = $btn.html();
        $btn.prop('disabled', true).html('<i class="fa fa-spinner fa-spin"></i> <span>...</span>');

        let url = '../../invoice/payment_report.php';
        let formData = new FormData();
        formData.append("member_id", memberId);

        fetch(url, {
            method: "POST",
            body: formData
        })
        .then(async (response) => {
            $btn.prop('disabled', false).html(origHtml);
            const contentType = response.headers.get("Content-Type") || '';

            if (!response.ok || !contentType.includes("application/pdf")) {
                const text = await response.text();
                swal("Report Notice", text || "No ledger transactions found to generate payment report for this member.", "info");
                return;
            }

            const blob = await response.blob();
            const link = document.createElement("a");
            link.href = URL.createObjectURL(blob);
            link.download = "payment_report_member_" + memberId + ".pdf";
            link.click();
        })
        .catch((error) => {
            $btn.prop('disabled', false).html(origHtml);
            swal("Error", "Error generating PDF report: " + error, "error");
        });
    }

    // Convert Number to Words (Indian Currency Format)
    function numberToWords(num) {
        var a = ['','One ','Two ','Three ','Four ', 'Five ','Six ','Seven ','Eight ','Nine ','Ten ','Eleven ','Twelve ','Thirteen ','Fourteen ','Fifteen ','Sixteen ','Seventeen ','Eighteen ','Nineteen '];
        var b = ['', '', 'Twenty','Thirty','Forty','Fifty', 'Sixty','Seventy','Eighty','Ninety'];

        num = Math.floor(num);
        if (num === 0) return 'ZERO RUPEES ONLY';
        if ((num = num.toString()).length > 9) return 'OVERFLOW';
        var n = ('000000000' + num).substr(-9).match(/^(\d{2})(\d{2})(\d{2})(\d{1})(\d{2})$/);
        if (!n) return ''; 
        var str = '';
        str += (n[1] != 0) ? (a[Number(n[1])] || b[n[1][0]] + ' ' + a[n[1][1]]) + 'Crore ' : '';
        str += (n[2] != 0) ? (a[Number(n[2])] || b[n[2][0]] + ' ' + a[n[2][1]]) + 'Lakh ' : '';
        str += (n[3] != 0) ? (a[Number(n[3])] || b[n[3][0]] + ' ' + a[n[3][1]]) + 'Thousand ' : '';
        str += (n[4] != 0) ? (a[Number(n[4])] || b[n[4][0]] + ' ' + a[n[4][1]]) + 'Hundred ' : '';
        str += (n[5] != 0) ? ((str != '') ? 'and ' : '') + (a[Number(n[5])] || b[n[5][0]] + ' ' + a[n[5][1]]) : '';
        return (str.trim() + ' Rupees Only').toUpperCase();
    }

    // Open Printable Fee Receipt Modal
    function openReceiptModal(id, name, group, date, head, amountStr, rawAmt, mode, desc) {
        $('#m_rec_no').text('#REC-' + String(id).padStart(4, '0'));
        $('#m_rec_date').text(date);
        $('#m_rec_name').text(name);
        $('#m_rec_group').text(group);
        $('#m_rec_head').text(head);
        $('#m_rec_mode').text(mode).attr('class', mode === 'Bank' ? 'rcp-badge rcp-badge-bank' : 'rcp-badge rcp-badge-cash');
        $('#m_rec_desc').text(desc || 'Fee Received');
        $('#m_rec_amt').text('₹' + amountStr);
        $('#m_rec_words').text(numberToWords(rawAmt));
        $('#viewReceiptModal').modal('show');
    }

    // Print Receipt Area
    function printReceiptArea() {
        var printContents = document.getElementById('printable_receipt_area').innerHTML;
        var originalContents = document.body.innerHTML;
        document.body.innerHTML = '<div style="padding:30px; font-family:sans-serif;">' + printContents + '</div>';
        window.print();
        document.body.innerHTML = originalContents;
        location.reload();
    }

    // ================= QUICK PAY LOGIC (Matching directory/fees_receiveble) =================
    let currentQuickMemberId = 0;
    let currentQuickWallet = 0;
    let currentQuickDue = 0;

    function openPayModal(mId, mName, dueVal, walletVal) {
        currentQuickMemberId = mId;
        currentQuickDue = parseFloat(dueVal) || 0;
        currentQuickWallet = parseFloat(walletVal) || 0;

        $('#qpay_member_name').text(mName);
        $('#qpay_due_badge').text('Due: ₹' + currentQuickDue.toFixed(2));
        $('#qpay_wallet_badge').text('Wallet: ₹' + currentQuickWallet.toFixed(2));
        $('#quick_txt_wallet_avail').text('₹' + currentQuickWallet.toFixed(2));

        $('#quick_txt_AmountPaid').val('');
        $('#quick_txt_wallet_amount_input').val('');
        $('#quick_excess_wallet_msg').hide();
        window.quickExcessAmount = 0;
        window.quickTotalPaid = 0;
        $('#quick_setoff_button').hide();
        $('#quick_setoff_button_wallet').hide();
        $('#link_quick_full_ledger').attr('href', 'member_receivable.php?member_id=' + mId);

        $('#quick_receivables_container').html('<div style="text-align:center; padding:20px; color:#94a3b8;"><i class="fa fa-spinner fa-spin" style="font-size:20px; margin-bottom:6px; display:block;"></i>Loading receivables...</div>');
        $('#quickPayModal').modal('show');

        refreshQuickPayData();
    }

    function refreshQuickPayData() {
        if (!currentQuickMemberId) return;

        // Refresh due and wallet metrics
        $.post('../api/fees_receiveble.php', { action: 'load_pending_payment', id: currentQuickMemberId }, function(res) {
            try {
                let parsed = typeof res === 'string' ? JSON.parse(res) : res;
                let data = (Array.isArray(parsed) && parsed[0] && parsed[0][0]) ? parsed[0][0] : (parsed[0] || {});
                let recFees = parseFloat(data.receivable_fees || 0);
                let rcvFees = parseFloat(data.received_fees || 0);
                let due = recFees - rcvFees;
                if (due < 0) due = 0;
                currentQuickDue = due;
                $('#qpay_due_badge').text('Due: ₹' + due.toFixed(2));
            } catch(e) {}
        });

        $.post('../api/fees_receiveble.php', { action: 'load_wallet_balance', id: currentQuickMemberId }, function(res) {
            try {
                let parsed = typeof res === 'string' ? JSON.parse(res) : res;
                let data = (Array.isArray(parsed) && parsed[0] && parsed[0][0]) ? parsed[0][0] : (parsed[0] || {});
                let wal = parseFloat(data.wallet_balance || 0);
                currentQuickWallet = wal;
                $('#qpay_wallet_badge').text('Wallet: ₹' + wal.toFixed(2));
                $('#quick_txt_wallet_avail').text('₹' + wal.toFixed(2));
            } catch(e) {}
        });

        $.post('../api/fees_receiveble.php', { action: 'load_data', page: 1, member_id: currentQuickMemberId, no_limit: 1 }, function(res) {
            try {
                let parsed = typeof res === 'string' ? JSON.parse(res) : res;
                let items = parsed[1] || [];
                window.quick_receivables_data = items;
                $('#quick_dues_count').text(items.length + ' Records');

                if (!items || items.length === 0) {
                    $('#quick_receivables_container').html('<div style="text-align:center; padding:20px; color:#94a3b8;"><i class="fa fa-check-circle" style="color:#10b981; font-size:24px; margin-bottom:4px; display:block;"></i>No pending dues recorded for this member.</div>');
                    return;
                }

                let htm = '';
                items.forEach(function(r, index) {
                    let totalRec = parseFloat(r.receiveble_fees || 0);
                    let paid = parseFloat(r.total_received_fees || 0);
                    let due = totalRec - paid;
                    if (due < 0) due = 0;
                    let isCanceled = (parseInt(r.cancel, 10) === 1);
                    let isComplete = (parseInt(r.iscomplete, 10) === 1 || due === 0);
                    let statusClass = isCanceled ? 'status-pill-canceled' : (isComplete ? 'status-pill-paid' : 'status-pill-pending');
                    let statusText = isCanceled ? 'CANCELED' : (isComplete ? 'SETTLED' : 'PENDING');
                    let dateStr = r.date ? new Date(r.date).toLocaleDateString('en-GB', { day:'numeric', month:'short', year:'numeric' }) : '--';

                    htm += `
                        <div class="rec-item-card quick-rec-card ${isCanceled ? 'is-canceled' : (isComplete ? 'is-complete' : 'is-pending')}" id="q_card_${r.recieveble_id}" data-receiveble-id="${r.recieveble_id}" data-flag="${r.flag || 1}" data-remaining-balance="${isCanceled ? 0 : due}">
                            <div class="rec-item-header">
                                <span class="rec-item-date"><i class="fa fa-calendar"></i> ${dateStr}</span>
                                <span class="status-pill ${statusClass}">${statusText}</span>
                            </div>
                            <h4 class="rec-item-title">${r.name || r.head_name || 'Fee Receivable'} ${r.discription ? '— ' + r.discription : ''}</h4>
                            <div class="rec-item-grid">
                                <div>
                                    <div class="rec-item-lbl">Total</div>
                                    <div class="rec-item-val">₹${totalRec.toFixed(2)}</div>
                                </div>
                                <div>
                                    <div class="rec-item-lbl">Paid</div>
                                    <div class="rec-item-val" style="color:#059669;">₹${paid.toFixed(2)}</div>
                                </div>
                                <div>
                                    <div class="rec-item-lbl">Set-Off</div>
                                    <div class="rec-item-val card-setoff-val" style="color:#64748b;">₹0.00</div>
                                </div>
                                <div>
                                    <div class="rec-item-lbl">Due</div>
                                    <div class="rec-item-val card-bal-val" style="color:${isCanceled ? '#94a3b8' : (due > 0 ? '#ef4444' : '#10b981')};">₹${due.toFixed(2)}</div>
                                </div>
                            </div>
                            <div class="rec-card-actions" style="display:flex; justify-content:flex-end; gap:8px; margin-top:8px; padding-top:8px; border-top:1px dashed #f1f5f9;">
                                <button type="button" class="btn btn-table-action-edit" onclick="quickEditReceiveble(${index});"><i class="fa fa-pencil"></i> Edit</button>
                                ${isCanceled ? 
                                    `<button type="button" class="btn btn-table-action-cancel proceed" onclick="quickCancelFeeDetails(${r.recieveble_id}, 1);"><i class="fa fa-refresh"></i> Re-open</button>` :
                                    `<button type="button" class="btn btn-table-action-cancel cancel" onclick="quickCancelFeeDetails(${r.recieveble_id}, 0);"><i class="fa fa-ban"></i> Cancel</button>`
                                }
                            </div>
                        </div>
                    `;
                });

                $('#quick_receivables_container').html(htm);
            } catch(e) {
                $('#quick_receivables_container').html('<div style="text-align:center; padding:20px; color:#ef4444;">Error loading dues.</div>');
            }
        });
    }

    function quickEditReceiveble(index) {
        if (!window.quick_receivables_data || !window.quick_receivables_data[index]) return;
        let item = window.quick_receivables_data[index];
        $("#quick_rec_id").val(item.recieveble_id);
        $("#quick_rec_date").val(item.date);
        $("#quick_rec_amount").val(item.receiveble_fees);
        $("#quick_rec_discription").val(item.discription || '');
        $('#quickEditReceivebleModel').modal('show');
    }

    function quickSaveReceiveble() {
        let amount = $('#quick_rec_amount').val();
        let date = $('#quick_rec_date').val();
        let discription = $("#quick_rec_discription").val();

        if (!amount || parseFloat(amount) <= 0) {
            swal("Notice", "Please enter a valid receivable amount.", "info");
            return;
        }
        if (!date) {
            swal("Notice", "Date cannot be empty.", "info");
            return;
        }
        if (discription.length >= 150) {
            swal("Notice", "Description must be less than 150 characters.", "info");
            return;
        }

        swal({
            title: "Are you sure?",
            text: "Do you want to update this receivable record?",
            type: "warning",
            showCancelButton: true,
            confirmButtonColor: "#DD6B55",
            confirmButtonText: "Yes, Update!",
            cancelButtonText: "Cancel",
            closeOnConfirm: false,
            closeOnCancel: true
        }, function(isConfirm) {
            if (isConfirm) {
                $.ajax({
                    type: "POST",
                    url: "../api/fees_receiveble.php",
                    data: {
                        action: 'save_receiveble_amount',
                        id: $("#quick_rec_id").val(),
                        amount: amount,
                        date: date,
                        discription: discription
                    },
                    success: function() {
                        $('#quickEditReceivebleModel').modal('hide');
                        swal("Success", "Receivable record updated successfully!", "success");
                        refreshQuickPayData();
                    },
                    error: function(xhr) {
                        $('#quickEditReceivebleModel').modal('hide');
                        let msg = "Oops! Something went wrong.";
                        try {
                            let msgObj = JSON.parse(xhr.responseText);
                            if (msgObj && msgObj.Message) msg = msgObj.Message;
                        } catch(e) {}
                        swal("Error", msg, "error");
                    }
                });
            }
        });
    }

    function quickCancelFeeDetails(receiveble_id, cancel) {
        let status;
        let text = '';
        if (parseInt(cancel) === 1) {
            status = 0;
            text = 'Do you want to re-open the payment?';
        } else {
            status = 1;
            text = 'Do you want to cancel/complete the payment?';
        }
        swal({
            title: "Are you sure?",
            text: text,
            type: "warning",
            showCancelButton: true,
            confirmButtonColor: "#DD6B55",
            confirmButtonText: "Yes!",
            cancelButtonText: "Cancel",
            closeOnConfirm: false,
            closeOnCancel: true
        }, function(isConfirm) {
            if (isConfirm) {
                $.ajax({
                    type: "POST",
                    url: "../api/fees_receiveble.php",
                    data: {
                        action: 'cancel_payment',
                        id: receiveble_id,
                        status: status
                    },
                    success: function(response) {
                        swal("Updated!", response || "Payment status updated.", "success");
                        refreshQuickPayData();
                    },
                    error: function(xhr) {
                        let msg = "Error updating payment status";
                        try {
                            let msgObj = JSON.parse(xhr.responseText);
                            if (msgObj && msgObj.Message) msg = msgObj.Message;
                        } catch(e) {}
                        swal("Error", msg, "error");
                    }
                });
            }
        });
    }

    function quickSetFees() {
        let balAmt = 0;
        let initialAmount = parseFloat($('#quick_txt_AmountPaid').val()) || 0;
        let TotalAmount = initialAmount;
        if (TotalAmount <= 0) {
            swal("Amount Required", "Please enter an amount before clicking Set Fees.", "warning");
            return;
        }

        $('.quick-rec-card').each(function() {
            let remaining_balance = parseFloat($(this).data('remaining-balance')) || 0;
            $(this).find('.card-setoff-val').text('₹0.00').css({ 'color': '#64748b', 'font-weight': '600' });
            $(this).find('.card-bal-val').text('₹' + remaining_balance.toFixed(2)).css({ 'color': (remaining_balance > 0 ? '#ef4444' : '#10b981'), 'font-weight': '800' });
            $(this).removeClass('selected-setoff-card');
        });

        let totalSettled = 0;
        $('.quick-rec-card').each(function() {
            let remaining_balance = parseFloat($(this).data('remaining-balance')) || 0;
            if (remaining_balance <= 0) return;

            if (TotalAmount >= remaining_balance) {
                $(this).find('.card-bal-val').text('₹0.00').css({ 'color': '#10b981', 'font-weight': '800' });
                $(this).find('.card-setoff-val').text('₹' + remaining_balance.toFixed(2)).css({ 'color': '#10b981', 'font-weight': '800' });
                $(this).addClass('selected-setoff-card');
                TotalAmount = TotalAmount - remaining_balance;
                totalSettled += remaining_balance;
            } else {
                balAmt = remaining_balance - TotalAmount;
                $(this).find('.card-bal-val').text('₹' + balAmt.toFixed(2)).css({ 'color': '#ef4444', 'font-weight': '800' });
                $(this).find('.card-setoff-val').text('₹' + TotalAmount.toFixed(2)).css({ 'color': '#10b981', 'font-weight': '800' });
                $(this).addClass('selected-setoff-card');
                totalSettled += TotalAmount;
                TotalAmount = 0;
                return false;
            }
        });

        let excessAmount = TotalAmount;
        window.quickExcessAmount = excessAmount;
        window.quickTotalPaid = initialAmount;

        if (excessAmount > 0) {
            $('#quick_excess_wallet_msg').html(
                '<div style="background:rgba(16,185,129,0.1); border:1.5px solid rgba(16,185,129,0.3); border-radius:12px; padding:10px 14px; color:#065f46; display:flex; align-items:center; justify-content:space-between; gap:10px; font-weight:600;">' +
                    '<div style="display:flex; align-items:center; gap:8px;">' +
                        '<span style="background:#10b981; color:#fff; width:26px; height:26px; border-radius:50%; display:flex; align-items:center; justify-content:center; font-size:12px; flex-shrink:0;"><i class="fa fa-google-wallet"></i></span>' +
                        '<div style="font-size:12px; line-height:1.3;">' +
                            '<strong style="color:#047857; font-size:12.5px;">Excess Payment Detected</strong><br>' +
                            '<span>Dues Settled: <strong>₹' + totalSettled.toFixed(2) + '</strong> &nbsp;|&nbsp; To Wallet: <strong style="color:#059669;">₹' + excessAmount.toFixed(2) + '</strong></span>' +
                        '</div>' +
                    '</div>' +
                    '<span style="background:#10b981; color:#fff; font-size:11px; font-weight:800; padding:5px 10px; border-radius:12px; white-space:nowrap;">+ ₹' + excessAmount.toFixed(2) + '</span>' +
                '</div>'
            ).show();
        } else {
            $('#quick_excess_wallet_msg').hide();
        }

        $('#quick_setoff_button').show();
        $('#quick_setoff_button_wallet').hide();
    }

    function quickSetFeesFromWallet() {
        let balAmt = 0;
        let TotalAmount = parseFloat($('#quick_txt_wallet_amount_input').val()) || 0;
        if (TotalAmount <= 0) {
            swal("Amount Required", "Please enter a wallet amount before clicking Set Fees.", "warning");
            return;
        }

        if (TotalAmount > currentQuickWallet) {
            swal("Insufficient Balance", "Amount (₹" + TotalAmount.toFixed(2) + ") exceeds available wallet balance (₹" + currentQuickWallet.toFixed(2) + ").", "warning");
            return;
        }

        $('.quick-rec-card').each(function() {
            let remaining_balance = parseFloat($(this).data('remaining-balance')) || 0;
            $(this).find('.card-setoff-val').text('₹0.00').css({ 'color': '#64748b', 'font-weight': '600' });
            $(this).find('.card-bal-val').text('₹' + remaining_balance.toFixed(2)).css({ 'color': (remaining_balance > 0 ? '#ef4444' : '#10b981'), 'font-weight': '800' });
            $(this).removeClass('selected-setoff-card');
        });

        $('.quick-rec-card').each(function() {
            let remaining_balance = parseFloat($(this).data('remaining-balance')) || 0;
            if (remaining_balance <= 0) return;

            if (TotalAmount >= remaining_balance) {
                $(this).find('.card-bal-val').text('₹0.00').css({ 'color': '#10b981', 'font-weight': '800' });
                $(this).find('.card-setoff-val').text('₹' + remaining_balance.toFixed(2)).css({ 'color': '#10b981', 'font-weight': '800' });
                $(this).addClass('selected-setoff-card');
                TotalAmount = TotalAmount - remaining_balance;
            } else {
                balAmt = remaining_balance - TotalAmount;
                $(this).find('.card-bal-val').text('₹' + balAmt.toFixed(2)).css({ 'color': '#ef4444', 'font-weight': '800' });
                $(this).find('.card-setoff-val').text('₹' + TotalAmount.toFixed(2)).css({ 'color': '#10b981', 'font-weight': '800' });
                $(this).addClass('selected-setoff-card');
                return false;
            }
        });

        $('#quick_setoff_button_wallet').show();
        $('#quick_setoff_button').hide();
    }

    function quickSetOffForm() {
        let excessAmount = window.quickExcessAmount || 0;
        let initialPaid = window.quickTotalPaid || 0;
        if (excessAmount > 0) {
            $('#quick_modal_excess_info').html(
                '<div style="background:#ecfdf5; border:1.5px solid #a7f3d0; border-radius:12px; padding:10px 14px; color:#065f46;">' +
                '<div style="font-weight:800; font-size:12.5px;"><i class="fa fa-google-wallet"></i> Wallet Credit Summary</div>' +
                '<div style="font-size:12px; margin-top:3px;">' +
                '• Settling Dues: <strong>₹' + (initialPaid - excessAmount).toFixed(2) + '</strong><br>' +
                '• Crediting to Wallet: <strong style="color:#059669;">₹' + excessAmount.toFixed(2) + '</strong><br>' +
                '• Total Payment: <strong>₹' + initialPaid.toFixed(2) + '</strong>' +
                '</div>' +
                '</div>'
            ).show();
        } else {
            $('#quick_modal_excess_info').hide();
        }
        $('#quickSetOffModel').modal('show');
    }

    function quickSetOffFormWallet() {
        $('#quickSetOffwalletModel').modal('show');
    }

    function quickSubmitSetOff() {
        let receivedArray = [];
        $('.quick-rec-card').each(function() {
            let recId = $(this).data('receiveble-id');
            let flag = $(this).data('flag');
            let received = parseFloat($(this).find('.card-setoff-val').text().replace(/[₹,\s]/g, '')) || 0;
            let balance = parseFloat($(this).find('.card-bal-val').text().replace(/[₹,\s]/g, '')) || 0;

            if (received > 0) {
                receivedArray.push({ receiveble_id: recId, flag: flag, received: received, balance: balance });
            }
        });

        let excessAmount = window.quickExcessAmount || 0;
        let initialPaid = window.quickTotalPaid || 0;

        if (receivedArray.length === 0 && excessAmount <= 0) {
            swal("Notice", "Please set fees before saving.", "info");
            return;
        }

        let dt = $('#quick_setoff_date').val();
        if (!dt) {
            swal("Date Required", "Please select a payment date.", "warning");
            return;
        }

        let confirmText = excessAmount > 0
            ? "Total Payment: ₹" + initialPaid.toFixed(2) + " (Dues Settled: ₹" + (initialPaid - excessAmount).toFixed(2) + ", Credited to Wallet: ₹" + excessAmount.toFixed(2) + "). Do you want to save?"
            : "Do you want to save this payment data?";

        swal({
            title: "Are you sure?",
            text: confirmText,
            type: "warning",
            showCancelButton: true,
            confirmButtonColor: "#DD6B55",
            confirmButtonText: "Yes, Save!",
            cancelButtonText: "Cancel",
            closeOnConfirm: false
        }, function(isConfirm) {
            if (isConfirm) {
                let formData = new FormData();
                formData.append('action', 'setoff_receiveble');
                formData.append('member_id', currentQuickMemberId);
                formData.append('received_array', JSON.stringify(receivedArray));
                formData.append('amount_to_wallet', excessAmount);
                formData.append('total_amount_paid', initialPaid);
                formData.append('date', dt);
                formData.append('transaction_type', $('#quick_selected_transaction_type').val());

                let fileInput = document.getElementById('quick_bill_photo');
                if (fileInput && fileInput.files.length > 0) {
                    formData.append('bill_photo', fileInput.files[0]);
                }

                $.ajax({
                    type: "POST",
                    url: "../api/fees_receiveble.php",
                    data: formData,
                    processData: false,
                    contentType: false,
                    success: function(response) {
                        $('#quickSetOffModel').modal('hide');
                        $('#quickPayModal').modal('hide');
                        let successMsg = excessAmount > 0
                            ? "Payment recorded successfully! ₹" + excessAmount.toFixed(2) + " credited to member's wallet."
                            : (response || "Payment recorded successfully!");
                        swal({ title: "Saved!", text: successMsg, type: "success" }, function() {
                            location.reload();
                        });
                    },
                    error: function(xhr) {
                        let msg = xhr.responseText || 'Error saving payment';
                        try {
                            let obj = JSON.parse(xhr.responseText);
                            if (obj && obj.Message) msg = obj.Message;
                        } catch(e) {}
                        swal("Error", msg, "error");
                    }
                });
            }
        });
    }

    function quickSubmitSetOffWallet() {
        let receivedArray = [];
        $('.quick-rec-card').each(function() {
            let recId = $(this).data('receiveble-id');
            let flag = $(this).data('flag');
            let received = parseFloat($(this).find('.card-setoff-val').text().replace(/[₹,\s]/g, '')) || 0;
            let balance = parseFloat($(this).find('.card-bal-val').text().replace(/[₹,\s]/g, '')) || 0;

            if (received > 0) {
                receivedArray.push({ receiveble_id: recId, flag: flag, received: received, balance: balance });
            }
        });

        if (receivedArray.length === 0) {
            swal("Notice", "Please set fees before saving.", "info");
            return;
        }

        let dt = $('#quick_setoff_date_wallet').val();
        if (!dt) {
            swal("Date Required", "Please select a deduction date.", "warning");
            return;
        }

        swal({
            title: "Are you sure?",
            text: "Do you want to deduct from wallet balance?",
            type: "warning",
            showCancelButton: true,
            confirmButtonColor: "#DD6B55",
            confirmButtonText: "Yes, Save!",
            cancelButtonText: "Cancel",
            closeOnConfirm: false
        }, function(isConfirm) {
            if (isConfirm) {
                let payload = {
                    action: 'setoff_receiveble_from_wallet',
                    member_id: currentQuickMemberId,
                    received_array: JSON.stringify(receivedArray),
                    date: dt,
                    transaction_type: $('#quick_selected_transaction_type_wallet').val()
                };

                $.ajax({
                    type: "POST",
                    url: "../api/fees_receiveble.php",
                    data: payload,
                    success: function() {
                        $('#quickSetOffwalletModel').modal('hide');
                        $('#quickPayModal').modal('hide');
                        swal({ title: "Saved!", text: "Wallet deduction processed successfully!", type: "success" }, function() {
                            location.reload();
                        });
                    },
                    error: function(xhr) {
                        let msg = xhr.responseText || 'Error processing wallet deduction';
                        try {
                            let obj = JSON.parse(xhr.responseText);
                            if (obj && obj.Message) msg = obj.Message;
                        } catch(e) {}
                        swal("Error", msg, "error");
                    }
                });
            }
        });
    }
    </script>
</body>
</html>
