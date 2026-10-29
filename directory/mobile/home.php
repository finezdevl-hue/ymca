<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
include_once __DIR__ . '/../../app_common/db_connect.php';
include_once __DIR__ . '/../../app_common/auth_helper.php';

// Only allow non-admin member logins
if (empty($_SESSION['login_id'])) {
    header("Location: ../../index.php");
    exit();
}

$page_title  = 'Attendance';
$active_tab  = 'home';

$login_id = (int)$_SESSION['login_id'];
$is_group_admin = isSuperAdmin($login_id) || isGroupAdmin($login_id);
$is_only_attendance_master = !$is_group_admin && (isAttendanceMaster($login_id) || isExecutiveMember($login_id));
$can_mark_all_att = $is_group_admin || isAttendanceMaster($login_id) || isExecutiveMember($login_id);

// Resolve member_id — set at login via login_validation.php
$member_id   = (int)($_SESSION['user_id'] ?? 0);
$member_name = $_SESSION['name'] ?? 'Member';

// Fallback: look up by email or name if user_id not in session
if ($member_id <= 0 && !empty($_SESSION['email'])) {
    $m_res = app_exec_getresult("SELECT id FROM tbl_members WHERE LTRIM(RTRIM(LOWER(email))) = LTRIM(RTRIM(LOWER(?))) AND inactive = 0 LIMIT 1", [$_SESSION['email']], "s");
    if ($m_res && $row = $m_res->fetch_assoc()) {
        $member_id = (int)$row['id'];
        $_SESSION['user_id'] = $member_id;
    }
}
if ($member_id <= 0 && !empty($_SESSION['name'])) {
    $m_res = app_exec_getresult("SELECT id FROM tbl_members WHERE LTRIM(RTRIM(LOWER(CONCAT(first_name, ' ', last_name)))) = LTRIM(RTRIM(LOWER(?))) AND inactive = 0 LIMIT 1", [$_SESSION['name']], "s");
    if ($m_res && $row = $m_res->fetch_assoc()) {
        $member_id = (int)$row['id'];
        $_SESSION['user_id'] = $member_id;
    }
}
if ($member_id <= 0) {
    $m_first = app_exec_getresult("SELECT id FROM tbl_members WHERE inactive = 0 ORDER BY id ASC LIMIT 1");
    if ($m_first && $rf = $m_first->fetch_assoc()) {
        $member_id = (int)$rf['id'];
    }
}

// --- Calculate pending balance directly in PHP using EXACT same formula as member_cashbook_report.php ---
$php_pending_balance = 0.0;
if ($member_id > 0) {
    $yr = ((int)date('n') >= 4) ? (int)date('Y') : (int)date('Y') - 1;
    $sd = $yr . "-04-01";
    $ed = ($yr + 1) . "-03-31";

    // Opening balance (before FY start) — matches member_cashbook_report.php exactly
    // Receivables: tbl_member_recievable + tbl_member_recievable_old
    // Payments:    tbl_member_recieved   + tbl_member_recieved_old
    // Wallet:      tbl_wallet.client_id  (debit - credit)
    $op_res = app_exec_getresult(
        "SELECT
            (
                (SELECT IFNULL(SUM(fees),0) FROM tbl_member_recievable     WHERE member_id=? AND date < ? AND cancel=0) +
                (SELECT IFNULL(SUM(fees),0) FROM tbl_member_recievable_old WHERE member_id=? AND date < ? AND cancel=0)
            ) - (
                (SELECT IFNULL(SUM(fees),0) FROM tbl_member_recieved       WHERE member_id=? AND date < ? AND cancel=0) +
                (SELECT IFNULL(SUM(fees),0) FROM tbl_member_recieved_old   WHERE member_id=? AND date < ? AND cancel=0)
            ) +
            (SELECT COALESCE(
                SUM(CASE WHEN type='debit'  THEN amount ELSE 0 END) -
                SUM(CASE WHEN type='credit' THEN amount ELSE 0 END), 0)
             FROM tbl_wallet WHERE client_id=? AND date < ?)
            AS ob",
        [
            $member_id, $sd,
            $member_id, $sd,
            $member_id, $sd,
            $member_id, $sd,
            $member_id, $sd
        ],
        "isisisisis"
    );
    $opening = ($op_res && $r = $op_res->fetch_assoc()) ? (float)$r['ob'] : 0.0;

    // FY receivables (debit)
    $r1 = app_exec_getresult("SELECT IFNULL(SUM(fees),0) AS v FROM tbl_member_recievable     WHERE member_id=? AND date BETWEEN ? AND ? AND cancel=0", [$member_id,$sd,$ed], "iss");
    $r2 = app_exec_getresult("SELECT IFNULL(SUM(fees),0) AS v FROM tbl_member_recievable_old WHERE member_id=? AND date BETWEEN ? AND ? AND cancel=0", [$member_id,$sd,$ed], "iss");
    // FY payments (credit)
    $r3 = app_exec_getresult("SELECT IFNULL(SUM(fees),0) AS v FROM tbl_member_recieved       WHERE member_id=? AND date BETWEEN ? AND ? AND cancel=0", [$member_id,$sd,$ed], "iss");
    $r4 = app_exec_getresult("SELECT IFNULL(SUM(fees),0) AS v FROM tbl_member_recieved_old   WHERE member_id=? AND date BETWEEN ? AND ? AND cancel=0", [$member_id,$sd,$ed], "iss");
    // FY wallet (debit - credit)
    $r5 = app_exec_getresult(
        "SELECT COALESCE(SUM(CASE WHEN type='debit' THEN amount ELSE 0 END) - SUM(CASE WHEN type='credit' THEN amount ELSE 0 END),0) AS v FROM tbl_wallet WHERE client_id=? AND date BETWEEN ? AND ?",
        [$member_id,$sd,$ed], "iss"
    );

    $fy_recv   = ($r1 ? (float)$r1->fetch_assoc()['v'] : 0) + ($r2 ? (float)$r2->fetch_assoc()['v'] : 0);
    $fy_paid   = ($r3 ? (float)$r3->fetch_assoc()['v'] : 0) + ($r4 ? (float)$r4->fetch_assoc()['v'] : 0);
    $fy_wallet = ($r5 ? (float)$r5->fetch_assoc()['v'] : 0);

    $closing = $opening + $fy_recv - $fy_paid + $fy_wallet;
    $php_pending_balance = max(0.0, $closing);
}

// Load payment settings
$upi_id       = 'ymcabcp@okaxis';
$payee_name   = 'YMCA BCP Poovathussery';
$payment_note = 'YMCA Member Fee Payment';
$upi_active   = 1;
$ps = app_exec_query("SELECT upi_id, payee_name, payment_note, is_active FROM tbl_payment_settings LIMIT 1");
if ($ps && $pr = $ps->fetch_assoc()) {
    $upi_id       = $pr['upi_id']       ?: $upi_id;
    $payee_name   = $pr['payee_name']   ?: $payee_name;
    $payment_note = $pr['payment_note'] ?: $payment_note;
    $upi_active   = (int)$pr['is_active'];
}

// Release session lock for subsequent AJAX calls
session_write_close();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta http-equiv="Cache-Control" content="no-cache, no-store, must-revalidate">
    <meta http-equiv="Pragma" content="no-cache">
    <meta http-equiv="Expires" content="0">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
    <meta name="description" content="Mark Attendance - YMCA Member Portal">
    <title>YMCA | Attendance</title>
    <link rel="shortcut icon" href="../../favicon.ico" type="image/x-icon">

    <link href="../../font-awesome/css/font-awesome.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800;900&display=swap" rel="stylesheet">
    <link href="../../css/plugins/sweetalert/sweetalert.css" rel="stylesheet">
    <link href="mobile_layout.css" rel="stylesheet">

    <style>
        /* Attendance page specific */
        .att-status-banner {
            border-radius: 14px;
            padding: 16px 18px;
            display: flex;
            align-items: center;
            gap: 12px;
            font-weight: 700;
            font-size: 14.5px;
        }
        .att-status-banner.checked-in {
            background: rgba(16,185,129,0.10);
            color: #059669;
            border: 1px solid rgba(16,185,129,0.25);
        }
        .att-status-banner.not-checked-in {
            background: rgba(79,70,229,0.07);
            color: #4f46e5;
            border: 1px solid rgba(79,70,229,0.15);
        }
        .att-status-banner.holiday-banner {
            background: rgba(245,158,11,0.10);
            color: #d97706;
            border: 1px solid rgba(245,158,11,0.2);
        }
        .att-status-icon {
            width: 36px; height: 36px;
            border-radius: 10px;
            display: flex; align-items: center; justify-content: center;
            font-size: 18px;
            flex-shrink: 0;
        }
        .checked-in .att-status-icon  { background: rgba(16,185,129,0.15); }
        .not-checked-in .att-status-icon { background: rgba(79,70,229,0.10); }
        .holiday-banner .att-status-icon { background: rgba(245,158,11,0.15); }

        /* Session pill buttons */
        .session-pills { display: flex; gap: 8px; flex-wrap: wrap; }
        .session-pill {
            padding: 8px 18px;
            border-radius: 30px;
            border: 2px solid rgba(79,70,229,0.25);
            background: #fff;
            color: #4f46e5;
            font-size: 13px;
            font-weight: 700;
            font-family: 'Inter', sans-serif;
            cursor: pointer;
            transition: all 0.2s;
        }
        .session-pill.active, .session-pill:active {
            background: #4f46e5;
            color: #fff;
            border-color: #4f46e5;
        }

        /* Date badge */
        .date-badge {
            display: inline-flex;
            align-items: center;
            gap: 7px;
            background: rgba(59,130,246,0.08);
            color: #3b82f6;
            border: 1.5px solid rgba(59,130,246,0.15);
            border-radius: 30px;
            padding: 7px 16px;
            font-size: 13.5px;
            font-weight: 700;
        }

        /* ─── 2-Column Mark Attendance Buttons ─── */
        .att-grid-2 {
            display: grid;
            grid-template-columns: repeat(2, 1fr);
            gap: 12px;
            width: 100%;
        }
        .att-big-btn {
            border: none;
            border-radius: 16px;
            padding: 18px 6px 14px;
            font-size: 12.5px;
            font-weight: 800;
            cursor: pointer;
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            gap: 8px;
            font-family: 'Inter', sans-serif;
            transition: all 0.22s ease;
            color: white;
        }
        .att-big-btn .btn-icon-wrap {
            width: 44px; height: 44px;
            border-radius: 50%;
            background: rgba(255,255,255,0.22);
            display: flex; align-items: center; justify-content: center;
            font-size: 20px;
        }
        .att-big-btn:hover  { transform: translateY(-3px) scale(1.03); }
        .att-big-btn:active { transform: scale(0.95); }
        .btn-present { background: linear-gradient(145deg,#10b981,#059669); box-shadow: 0 6px 18px rgba(16,185,129,0.35); }
        .btn-absent  { background: linear-gradient(145deg,#ef4444,#dc2626); box-shadow: 0 6px 18px rgba(239,68,68,0.35); }
        .btn-half    { background: linear-gradient(145deg,#f59e0b,#d97706); box-shadow: 0 6px 18px rgba(245,158,11,0.35); }

        /* Section label divider */
        .att-section-divider {
            font-size: 11px;
            font-weight: 700;
            color: #94a3b8;
            text-transform: uppercase;
            letter-spacing: 0.8px;
            text-align: center;
            margin-bottom: 12px;
            display: flex;
            align-items: center;
            gap: 8px;
        }
        .att-section-divider::before,
        .att-section-divider::after {
            content: '';
            flex: 1;
            height: 1px;
            background: #e2e8f0;
        }

        /* Status result card */
        .status-result-card {
            border-radius: 18px;
            padding: 22px 18px;
            display: flex;
            flex-direction: column;
            align-items: center;
            gap: 10px;
            text-align: center;
        }
        .src-present { background: linear-gradient(135deg,#ecfdf5,#d1fae5); border:1px solid rgba(16,185,129,0.2); }
        .src-absent  { background: linear-gradient(135deg,#fef2f2,#fee2e2); border:1px solid rgba(239,68,68,0.2); }
        .src-half    { background: linear-gradient(135deg,#fffbeb,#fef3c7); border:1px solid rgba(245,158,11,0.2); }

        .src-icon {
            width: 60px; height: 60px;
            border-radius: 50%;
            display: flex; align-items: center; justify-content: center;
            font-size: 26px;
        }
        .src-present .src-icon { background: rgba(16,185,129,0.15); color: #10b981; }
        .src-absent  .src-icon { background: rgba(239,68,68,0.15);   color: #ef4444; }
        .src-half    .src-icon { background: rgba(245,158,11,0.15);  color: #f59e0b; }

        .src-title { font-size: 17px; font-weight: 800; }
        .src-present .src-title { color: #065f46; }
        .src-absent  .src-title { color: #991b1b; }
        .src-half    .src-title { color: #92400e; }

        .src-sub { font-size: 12px; font-weight: 500; color: #64748b; }
        .src-eta {
            font-size: 12.5px; font-weight: 700;
            padding: 5px 14px; border-radius: 30px;
        }
        .src-present .src-eta { background: rgba(16,185,129,0.15); color: #10b981; }
        .src-half    .src-eta { background: rgba(245,158,11,0.15);  color: #d97706; }

        .src-actions { display: flex; gap: 10px; flex-wrap: wrap; justify-content: center; }
        .src-btn-green {
            background: #10b981; color: #fff; border: none;
            padding: 9px 20px; border-radius: 50px;
            font-weight: 700; font-size: 13px;
            font-family: 'Inter', sans-serif; cursor: pointer;
            box-shadow: 0 3px 10px rgba(16,185,129,0.3);
            display: inline-flex; align-items: center; gap: 6px;
        }
        .src-btn-ghost {
            background: none; border: none; color: #94a3b8;
            font-size: 12px; font-weight: 600; cursor: pointer;
            text-decoration: underline; font-family: 'Inter', sans-serif;
        }

        /* Present member list */
        .present-member-row {
            display: flex;
            align-items: center;
            gap: 10px;
            padding: 10px 0;
            border-bottom: 1px solid #f1f5f9;
        }
        .present-member-row:last-child { border-bottom: none; }
        .present-avatar {
            width: 38px; height: 38px;
            border-radius: 50%;
            object-fit: cover;
            border: 2px solid #e2e8f0;
            flex-shrink: 0;
        }
        .present-name {
            font-size: 13.5px;
            font-weight: 600;
            color: #1e293b;
            flex: 1;
        }

        /* Unmark link */
        .unmark-link {
            background: none;
            border: none;
            color: #ef4444;
            font-size: 11.5px;
            font-weight: 600;
            cursor: pointer;
            font-family: 'Inter', sans-serif;
            text-decoration: underline;
            padding: 0;
            margin-top: 8px;
        }

        /* Half-chance bottom sheet modal */
        .hc-modal-overlay {
            display: none;
            position: fixed; inset: 0;
            background: rgba(0,0,0,0.55);
            backdrop-filter: blur(6px);
            z-index: 1000000 !important;
            align-items: flex-end;
            justify-content: center;
        }
        .hc-modal-overlay.open,
        .hc-modal-overlay.active { display: flex; }
        .hc-sheet {
            background: #fff;
            border-radius: 28px 28px 0 0;
            padding: 20px 22px 28px;
            width: 100%; max-width: 500px;
            animation: slideUpSheet 0.28s ease;
            box-shadow: 0 -10px 35px rgba(0,0,0,0.25);
            position: relative;
            z-index: 1000001 !important;
        }
        @keyframes slideUpSheet { from { transform: translateY(60px); opacity:0; } to { transform: translateY(0); opacity:1; } }
        .hc-handle { width:40px; height:4px; border-radius:4px; background:#e2e8f0; margin:0 auto 18px; }
        .hc-title  { font-size:17px; font-weight:800; color:#0f172a; margin-bottom:4px; }
        .hc-sub    { font-size:12.5px; color:#64748b; margin-bottom:18px; }
        .hc-time-wrap {
            background: #f8fafc; border: 2px solid #e2e8f0; border-radius: 14px;
            padding: 14px 16px; display: flex; align-items: center; gap: 10px; margin-bottom: 18px;
            justify-content: center;
        }
        .hc-time-wrap i { font-size:20px; color:#4f46e5; }
        .hc-select {
            border: 1.5px solid #cbd5e1;
            background: #ffffff;
            border-radius: 10px;
            padding: 8px 12px;
            font-size: 17px;
            font-weight: 800;
            font-family: 'Inter', sans-serif;
            color: #0f172a;
            outline: none;
            cursor: pointer;
            box-shadow: 0 1px 3px rgba(0,0,0,0.05);
            transition: all 0.2s;
        }
        .hc-select:focus {
            border-color: #4f46e5;
            box-shadow: 0 0 0 3px rgba(79,70,229,0.15);
        }
        .hc-select-ampm {
            background: #eef2ff;
            color: #4f46e5;
            border-color: #c7d2fe;
        }
        .hc-actions { display:flex; gap:10px; }
        .hc-btn {
            flex:1; padding:13px; border-radius:14px; border:none;
            font-weight:800; font-size:14px; font-family:'Inter',sans-serif; cursor:pointer;
        }
        .hc-btn-cancel  { background:#f1f5f9; color:#64748b; }
        .hc-btn-confirm { background: linear-gradient(135deg,#f59e0b,#d97706); color:#fff; box-shadow:0 4px 14px rgba(245,158,11,0.35); }
        @keyframes shimmer { to { background-position: -200% 0; } }

        /* ─── Game Status Card ─── */
        .game-status-card {
            background: linear-gradient(135deg, #1e1b4b 0%, #312e81 60%, #4338ca 100%);
            border-radius: 20px;
            padding: 18px 20px;
            margin-bottom: 16px;
            position: relative;
            overflow: hidden;
            box-shadow: 0 8px 28px rgba(79,70,229,0.35);
        }
        .game-status-card::before {
            content: '';
            position: absolute;
            top: -30px; right: -30px;
            width: 130px; height: 130px;
            border-radius: 50%;
            background: rgba(255,255,255,0.05);
        }
        .game-status-card::after {
            content: '';
            position: absolute;
            bottom: -40px; left: -20px;
            width: 100px; height: 100px;
            border-radius: 50%;
            background: rgba(255,255,255,0.04);
        }
        .gs-header {
            display: flex;
            align-items: center;
            gap: 10px;
            margin-bottom: 14px;
        }
        .gs-icon {
            width: 38px; height: 38px;
            border-radius: 10px;
            background: rgba(255,255,255,0.15);
            display: flex; align-items: center; justify-content: center;
            font-size: 18px; color: #fde68a;
            flex-shrink: 0;
        }
        .gs-label {
            font-size: 11px;
            font-weight: 700;
            color: rgba(255,255,255,0.6);
            text-transform: uppercase;
            letter-spacing: 0.8px;
        }
        .gs-title {
            font-size: 14.5px;
            font-weight: 800;
            color: #fff;
        }
        .gs-start-wrap {
            background: rgba(255,255,255,0.1);
            border: 1px solid rgba(255,255,255,0.15);
            border-radius: 14px;
            padding: 12px 16px;
            display: flex;
            align-items: center;
            justify-content: space-between;
            margin-bottom: 12px;
        }
        .gs-start-label {
            font-size: 11.5px;
            font-weight: 600;
            color: rgba(255,255,255,0.65);
        }
        .gs-start-time {
            font-size: 22px;
            font-weight: 900;
            color: #fde68a;
            letter-spacing: 0.5px;
        }
        .gs-start-sub {
            font-size: 10px;
            font-weight: 600;
            color: rgba(255,255,255,0.45);
            margin-top: 1px;
        }
        .gs-stats {
            display: flex;
            gap: 8px;
        }
        .gs-stat-chip {
            flex: 1;
            background: rgba(255,255,255,0.08);
            border: 1px solid rgba(255,255,255,0.12);
            border-radius: 10px;
            padding: 8px 10px;
            text-align: center;
        }
        .gs-stat-chip .sval {
            font-size: 17px;
            font-weight: 900;
            color: #fff;
        }
        .gs-stat-chip .slbl {
            font-size: 9.5px;
            font-weight: 600;
            color: rgba(255,255,255,0.5);
            text-transform: uppercase;
            letter-spacing: 0.4px;
        }
        .gs-timeline {
            margin-top: 12px;
            display: flex;
            flex-direction: column;
            gap: 6px;
        }
        .gs-tl-row {
            display: flex;
            align-items: center;
            gap: 10px;
        }
        .gs-tl-time {
            font-size: 12px;
            font-weight: 800;
            color: #fde68a;
            min-width: 58px;
        }
        .gs-tl-dot {
            width: 7px; height: 7px;
            border-radius: 50%;
            flex-shrink: 0;
        }
        .gs-tl-name {
            font-size: 12.5px;
            font-weight: 600;
            color: rgba(255,255,255,0.85);
            flex: 1;
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
        }
        .gs-tl-badge {
            font-size: 10px;
            font-weight: 700;
            padding: 2px 8px;
            border-radius: 20px;
        }

        @media (max-width: 360px) {
            #home-shuttle-card {
                padding: 10px 10px !important;
            }
            #home_shuttle_prev_note_section {
                padding: 8px 10px !important;
            }
        }
    </style>
</head>

<body class="mob-body">

<!-- Half-chance time picker sheet -->
<div class="hc-modal-overlay" id="hc-modal">
    <div class="hc-sheet">
        <div class="hc-handle"></div>
        <div class="hc-title" id="hc-modal-title">⏰ Expected Arrival Time</div>
        <div class="hc-sub" id="hc-modal-sub">Let your group know when you'll likely arrive (optional)</div>
        <div class="hc-time-wrap">
            <i class="fa fa-clock-o"></i>
            <div style="display:flex; align-items:center; gap:6px; flex:1; justify-content:center;">
                <select id="hc-hour-select" class="hc-select">
                    <option value="01">01</option>
                    <option value="02">02</option>
                    <option value="03">03</option>
                    <option value="04">04</option>
                    <option value="05">05</option>
                    <option value="06">06</option>
                    <option value="07">07</option>
                    <option value="08">08</option>
                    <option value="09">09</option>
                    <option value="10">10</option>
                    <option value="11">11</option>
                    <option value="12">12</option>
                </select>
                <span style="font-size:20px; font-weight:800; color:#475569;">:</span>
                <select id="hc-min-select" class="hc-select">
                    <option value="00">00</option>
                    <option value="05">05</option>
                    <option value="10">10</option>
                    <option value="15">15</option>
                    <option value="20">20</option>
                    <option value="25">25</option>
                    <option value="30">30</option>
                    <option value="35">35</option>
                    <option value="40">40</option>
                    <option value="45">45</option>
                    <option value="50">50</option>
                    <option value="55">55</option>
                </select>
                <select id="hc-ampm-select" class="hc-select hc-select-ampm">
                    <option value="AM">AM</option>
                    <option value="PM">PM</option>
                </select>
            </div>
        </div>
        <div class="hc-actions">
            <button class="hc-btn hc-btn-cancel"  onclick="closeHCModal()">Cancel</button>
            <button class="hc-btn hc-btn-confirm" id="hc-btn-confirm" onclick="submitHalfChance()">⚡ Confirm Half Chance</button>
        </div>
    </div>
</div>

<?php include 'mobile_header.php'; ?>

<div class="mob-page">

    <!-- Pending Balance & Quick Pay Card -->
    <div class="mob-card" id="home-pending-balance-card" style="background:#ffffff; border-radius:18px; border:1px solid #edf2f7; box-shadow: 0 4px 20px rgba(0,0,0,0.035); margin-bottom:16px; overflow:hidden;">
        <div style="padding:16px 18px; display:flex; align-items:center; justify-content:space-between; flex-wrap:wrap; gap:12px;">
            <div style="display:flex; align-items:center; gap:12px;">
                <div style="width:44px; height:44px; border-radius:12px; background:linear-gradient(135deg, #1e1b4b, #312e81); color:#ffffff; display:flex; align-items:center; justify-content:center; font-size:18px; box-shadow:0 4px 12px rgba(30,27,75,0.2); flex-shrink:0;">
                    <i class="fa fa-credit-card"></i>
                </div>
                <div>
                    <div style="font-size:11px; font-weight:800; color:#64748b; text-transform:uppercase; letter-spacing:0.5px; font-family:'Inter',sans-serif;">Pending Balance</div>
                    <div style="font-size:19px; font-weight:900; margin-top:2px; font-family:'Inter',sans-serif;<?php echo $php_pending_balance > 0 ? ' color:#ef4444;' : ' color:#10b981;'; ?>" id="home-pending-balance-val">₹<?php echo number_format($php_pending_balance, 2); ?></div>
                </div>
            </div>
            <div style="display:flex; align-items:center; gap:8px;">
                <button onclick="openHomePayModal()" class="mob-btn" style="background:linear-gradient(135deg, #10b981, #059669); color:#ffffff; border:none; padding:9px 18px; border-radius:30px; font-size:13px; font-weight:800; display:inline-flex; align-items:center; gap:6px; box-shadow:0 4px 12px rgba(16,185,129,0.35); cursor:pointer;">
                    <i class="fa fa-mobile" style="font-size:16px;"></i> Pay
                </button>
                <a href="ledger.php" class="mob-btn" style="background:#f1f5f9; color:#334155; border:1px solid #cbd5e1; padding:9px 16px; border-radius:30px; font-size:13px; font-weight:700; display:inline-flex; align-items:center; gap:5px; text-decoration:none;">
                    <i class="fa fa-file-text-o" style="color:#3b82f6;"></i> View
                </a>
            </div>
        </div>
    </div>

    <!-- Today's Game Status Card -->
    <div class="game-status-card" id="mob-game-status-card">
        <div class="gs-header">
            <div class="gs-icon"><i class="fa fa-futbol-o"></i></div>
            <div>
                <div class="gs-title">Today's Game Status</div>
            </div>
        </div>

        <div class="gs-start-wrap">
            <div>
                <div class="gs-start-label">⏰ Expected Start Time </div>
                <div class="gs-start-time" id="gs-start-time">--:--</div>
                <div class="gs-start-sub" id="gs-start-sub">Waiting for member ETAs...</div>
            </div>
        </div>

        <div class="gs-stats">
            <div class="gs-stat-chip">
                <div class="sval" id="gs-present-count">0</div>
                <div class="slbl">✅ Present</div>
            </div>
            <div class="gs-stat-chip">
                <div class="sval" id="gs-absent-count">0</div>
                <div class="slbl">❌ Absent</div>
            </div>
        </div>
    </div>

    <!-- Attendance Card -->
    <div class="mob-card">
        <div class="mob-card-body" style="text-align:center;">
            <div class="mob-page-title" style="text-align:center;">Mark My Attendance</div>
           
            <!-- Date display -->
            <div style="margin: 14px 0 6px; display: flex; justify-content: center;">
                <span class="date-badge">
                    <i class="fa fa-calendar"></i>
                    <span id="mob-today-date">--</span>
                </span>
            </div>

            <!-- Today / Tomorrow toggle -->
            <div id="mob-date-toggle-container" style="display:none; margin: 8px 0 14px; justify-content: center;">
                <div style="display:inline-flex; background:#f1f5f9; border-radius:50px; padding:4px; gap:0;">
                    <button id="mob_btn_date_today" type="button" onclick="selectMobAttDate('today')"
                        style="padding:8px 20px; border-radius:46px; border:none; font-weight:700; font-size:13px; font-family:'Inter',sans-serif; cursor:pointer; background:#4f46e5; color:#fff; box-shadow:0 3px 10px rgba(79,70,229,0.3); transition:all 0.2s;">
                        📅 Today
                    </button>
                    <button id="mob_btn_date_tomorrow" type="button" onclick="selectMobAttDate('tomorrow')"
                        style="padding:8px 20px; border-radius:46px; border:none; font-weight:700; font-size:13px; font-family:'Inter',sans-serif; cursor:pointer; background:transparent; color:#64748b; transition:all 0.2s;">
                        🌙 Tomorrow
                    </button>
                </div>
            </div>

            <!-- Session selector -->
            <div id="mob-session-selector-container" style="margin-bottom: 14px; text-align: center;">
                <div class="mob-label" style="margin-bottom:8px;">Select Session</div>
                <div class="session-pills" id="mob-session-pills" style="justify-content: center;">
                    <span style="color:#94a3b8; font-size:13px;">Loading sessions…</span>
                </div>
            </div>

            <!-- hidden inputs -->
            <select id="selected_group" style="display:none;"></select>
            <input type="date" id="date_search" style="display:none;">

            <!-- Action Area -->
            <div id="mob-action-area" style="width:100%; margin-top:4px;">
                <div style="height:90px;border-radius:16px;background:linear-gradient(90deg,#f1f5f9 25%,#e2e8f0 50%,#f1f5f9 75%);background-size:200% 100%;animation:shimmer 1.4s infinite;"></div>
            </div>

            <?php if ($can_mark_all_att) { ?>
            <!-- Session Shuttles Used Card (Red Alert Theme) -->
            <div id="home-shuttle-card" style="margin-top:14px; background: linear-gradient(135deg, #fef2f2 0%, #ffe4e6 100%); border: 1.5px solid #ef4444; border-radius: 18px; padding: 12px 14px; box-shadow: 0 4px 14px rgba(239, 68, 68, 0.15); text-align: left;">
                <div style="display:flex; flex-direction:column; gap:9px;">
                    <div>
                        <div style="font-size:11.5px; font-weight:800; color:#991b1b; text-transform:uppercase; letter-spacing:0.5px; font-family:'Inter',sans-serif; text-align:left;">🔴 Enter Shuttles Used Today <span id="home_stock_status_lbl" style="display:none;"></span></div>
                        <div id="home_shuttle_creator_notice" style="display:none; font-size:11.5px; font-weight:700; color:#1d4ed8; margin-top:4px; background:rgba(37,99,235,0.08); padding:4px 8px; border-radius:6px; border:1px solid #bfdbfe; text-align:left;"></div>
                    </div>
                    <!-- Optional Short Note Field (Above the save & count row) -->
                    <div style="position:relative; width:100%;">
                        <i class="fa fa-pencil" style="position:absolute; left:11px; top:50%; transform:translateY(-50%); font-size:12px; color:#ef4444; pointer-events:none;"></i>
                        <input type="text" id="home_shuttle_notes" maxlength="150" placeholder="Short note (optional, e.g. brand, broken, court)..." style="width:100%; height:34px; padding:6px 12px 6px 32px; border-radius:10px; border:1.5px solid #fca5a5; background:#ffffff; font-size:12px; font-weight:600; color:#7f1d1d; outline:none; box-sizing:border-box; box-shadow:inset 0 1px 2px rgba(0,0,0,0.03); font-family:'Inter',sans-serif;">
                    </div>
                    <div style="display:flex; align-items:center; justify-content:space-between; gap:6px; flex-wrap:wrap;">
                        <div style="display:flex; align-items:center; gap:5px; flex-shrink:0;">
                            <!-- Vertical Stock Box on the left side of [-] [ 0 ] [+] -->
                            <div id="home_stock_box" style="height:36px; min-width:38px; padding:2px 6px; border-radius:9px; border:1.5px solid #059669; background:#fff; display:flex; flex-direction:column; align-items:center; justify-content:center; user-select:none; box-shadow:0 1px 3px rgba(0,0,0,0.06); cursor:default; flex-shrink:0; margin-right:4px;" title="Remaining stock of shuttles">
                                <div id="home_stock_box_lbl" style="font-size:8px; font-weight:800; letter-spacing:0.5px; text-transform:uppercase; line-height:1; color:#059669;">STOCK</div>
                                <div id="home_stock_box_val" style="font-size:14px; font-weight:900; line-height:1.1; color:#059669; margin-top:1px;">0</div>
                            </div>
                            <button type="button" onclick="adjustShuttleCount('home_shuttles_used', -1)" style="width:34px; height:36px; border-radius:9px; border:1.5px solid #fca5a5; background:#fff; color:#991b1b; font-weight:900; font-size:19px; display:flex; align-items:center; justify-content:center; cursor:pointer; padding:0; user-select:none; flex-shrink:0;">-</button>
                            <input type="number" id="home_shuttles_used" min="0" max="6" placeholder="0" readonly style="width:42px; height:36px; padding:2px; border-radius:9px; border:1.5px solid #fca5a5; background:#fff; font-weight:900; font-size:17px; color:#991b1b; text-align:center; outline:none; cursor:default; flex-shrink:0;">
                            <button type="button" onclick="adjustShuttleCount('home_shuttles_used', 1)" style="width:34px; height:36px; border-radius:9px; border:1.5px solid #fca5a5; background:#fff; color:#991b1b; font-weight:900; font-size:19px; display:flex; align-items:center; justify-content:center; cursor:pointer; padding:0; user-select:none; flex-shrink:0;">+</button>
                        </div>
                        <button type="button" onclick="saveHomeShuttleReading()" style="height:36px; background:linear-gradient(135deg,#dc2626,#b91c1c); color:#fff; border:none; border-radius:9px; padding:0 14px; font-size:13px; font-weight:800; cursor:pointer; box-shadow:0 3px 10px rgba(220,38,38,0.3); display:inline-flex; align-items:center; justify-content:center; gap:5px; flex-shrink:0;" title="Save Shuttles Used">
                            <i class="fa fa-save"></i> Save
                        </button>
                    </div>
                </div>
            </div>

            <!-- Previous Added Note Section (Displayed after shuttle section) -->
            <div id="home_shuttle_prev_note_section" style="display:none; margin-top:10px; background:#fffdf7; border:1.5px solid #fed7aa; border-left:4px solid #f97316; border-radius:14px; padding:10px 12px; box-shadow:0 2px 8px rgba(249,115,22,0.08); font-family:'Inter',sans-serif; text-align:left;">
                <div style="display:flex; align-items:center; justify-content:space-between; gap:6px; margin-bottom:4px;">
                    <div style="font-size:11px; font-weight:800; color:#c2410c; text-transform:uppercase; letter-spacing:0.5px; display:inline-flex; align-items:center; gap:5px; white-space:nowrap; flex-shrink:0;">
                        <i class="fa fa-sticky-note" style="font-size:12px; color:#ea580c;"></i> <span id="home_shuttle_prev_note_label" style="white-space:nowrap;">Previous Added Note</span>
                    </div>
                    <button type="button" id="home_btn_use_note" onclick="copyShuttleNoteToInput()" style="display:none; background:#ffedd5; border:1px solid #fed7aa; color:#9a3412; border-radius:6px; padding:2px 8px; font-size:10.5px; font-weight:700; cursor:pointer; align-items:center; gap:3px; white-space:nowrap; flex-shrink:0;" title="Copy note into input field">
                        <i class="fa fa-copy"></i> Use
                    </button>
                </div>
                <div style="margin-bottom:6px; text-align:left;">
                    <span id="home_shuttle_prev_note_badge" style="font-size:10px; font-weight:700; color:#9a3412; background:#ffedd5; padding:2px 8px; border-radius:8px; display:inline-block; max-width:100%; word-break:break-word; text-align:left;"></span>
                </div>
                <div id="home_shuttle_prev_note_text" style="font-size:12.5px; font-weight:600; color:#431407; line-height:1.45; word-break:break-word; text-align:left; background:rgba(254,215,170,0.18); padding:7px 10px; border-radius:8px; border:1px solid rgba(254,215,170,0.45);"></div>
                
                <!-- Prior Session Note (Shown if today ALSO has a note) -->
                <div id="home_shuttle_older_note_wrap" style="display:none; margin-top:8px; padding-top:7px; border-top:1px dashed #fed7aa; font-size:11px; color:#7c2d12; text-align:left;">
                    <div style="display:flex; align-items:center; justify-content:space-between; gap:6px; flex-wrap:wrap; margin-bottom:3px;">
                        <div style="font-weight:700; font-size:10.5px; color:#9a3412; white-space:nowrap;">
                            <i class="fa fa-history" style="color:#ea580c; margin-right:3px;"></i> Prior Session Note
                        </div>
                        <span id="home_shuttle_older_note_badge" style="font-size:9.5px; font-weight:700; color:#9a3412; opacity:0.85; white-space:nowrap;"></span>
                    </div>
                    <div id="home_shuttle_older_note_text" style="font-weight:600; font-size:12px; color:#431407; word-break:break-word; text-align:left; background:rgba(254,215,170,0.12); padding:5px 8px; border-radius:6px;"></div>
                </div>
            </div>
            <?php } ?>
        </div>
    </div>

    <!-- Today's Member Status List Card -->
    <div class="mob-card" id="mob-present-card" style="display:none;">
        <div class="mob-card-header" style="display:flex; align-items:center; gap:8px;">
            <div class="mob-card-icon" style="background: linear-gradient(135deg,#3b82f6,#2563eb);"><i class="fa fa-users"></i></div>
            <div class="mob-card-title">Today's Member Status</div>
            <span class="mob-chip mob-chip-green" id="mob-present-count" style="margin-left:auto;">0</span>
            <button type="button" id="btn-share-match" onclick="shareTodayMatchImage()" style="background:linear-gradient(135deg,#10b981,#059669); color:#fff; border:none; border-radius:10px; padding:6px 12px; font-size:12px; font-weight:800; cursor:pointer; display:inline-flex; align-items:center; gap:5px; box-shadow:0 2px 6px rgba(16,185,129,0.3); font-family:'Inter',sans-serif;" title="Share Match & Attendance Summary as Image">
                <i class="fa fa-share-alt"></i> Share
            </button>
        </div>
        <div class="mob-card-body" style="padding-top:12px;">
            <div id="mob-present-list">
                <div style="color:#94a3b8; font-size:13px; text-align:center; padding:10px 0;">Loading…</div>
            </div>
            <button type="button" onclick="shareTodayMatchImage()" style="width:100%; margin-top:14px; background:linear-gradient(135deg,#2563eb,#1d4ed8); color:#fff; border:none; border-radius:12px; padding:12px 16px; font-size:13px; font-weight:800; cursor:pointer; display:inline-flex; align-items:center; justify-content:center; gap:8px; box-shadow:0 3px 10px rgba(37,99,235,0.25); font-family:'Inter',sans-serif;">
                <i class="fa fa-share-alt"></i> Share Match Report as Image
            </button>
        </div>
    </div>

</div><!-- /.mob-page -->

<!-- Match Summary Share Preview Modal -->
<div id="mob-share-preview-modal" style="display:none; position:fixed; z-index:99999; left:0; top:0; width:100%; height:100%; background:rgba(15,23,42,0.8); backdrop-filter:blur(4px); align-items:center; justify-content:center; padding:16px; box-sizing:border-box;">
    <div style="background:#fff; border-radius:20px; max-width:420px; width:100%; max-height:90vh; overflow-y:auto; padding:20px; box-sizing:border-box; text-align:center; box-shadow:0 20px 40px rgba(0,0,0,0.3);">
        <div style="display:flex; align-items:center; justify-content:space-between; margin-bottom:14px;">
            <div style="font-size:15px; font-weight:900; color:#1e293b;"><i class="fa fa-image" style="color:#2563eb;"></i> Match Report Preview</div>
            <button type="button" onclick="$('#mob-share-preview-modal').css('display','none')" style="background:#f1f5f9; border:none; width:32px; height:32px; border-radius:50%; font-size:14px; color:#64748b; cursor:pointer; display:flex; align-items:center; justify-content:center;">✕</button>
        </div>
        <div style="border:1.5px solid #e2e8f0; border-radius:14px; overflow:hidden; margin-bottom:14px; background:#f8fafc;">
            <img id="mob-share-preview-img" src="" alt="Match Report Preview" style="width:100%; height:auto; display:block;">
        </div>
        <div style="display:flex; flex-direction:column; gap:8px;">
            <a id="mob-share-download-btn" href="#" download="YMCA_Match_Report.png" style="background:linear-gradient(135deg,#10b981,#059669); color:#fff; text-decoration:none; padding:12px; border-radius:12px; font-weight:800; font-size:14px; display:flex; align-items:center; justify-content:center; gap:8px; box-shadow:0 3px 10px rgba(16,185,129,0.3);">
                <i class="fa fa-download"></i> Download Report Image
            </a>
            <button type="button" onclick="shareImageViaNativeOrWhatsApp()" style="background:linear-gradient(135deg,#2563eb,#1d4ed8); color:#fff; border:none; padding:12px; border-radius:12px; font-weight:800; font-size:14px; display:flex; align-items:center; justify-content:center; gap:8px; cursor:pointer; box-shadow:0 3px 10px rgba(37,99,235,0.3);">
                <i class="fa fa-share-alt"></i> Share to WhatsApp / Apps
            </button>
            <button type="button" onclick="$('#mob-share-preview-modal').css('display','none')" style="background:#f1f5f9; color:#64748b; border:none; padding:10px; border-radius:12px; font-weight:700; font-size:13px; cursor:pointer; margin-top:2px;">
                Close
            </button>
        </div>
    </div>
</div>

<!-- Off-screen Match Report Template (Rendered as Image) -->
<div id="match-share-template" style="position:fixed; left:-9999px; top:0; width:440px; background:#ffffff; font-family:'Inter',-apple-system,BlinkMacSystemFont,sans-serif; color:#0f172a; padding:16px; box-sizing:border-box;">
    <div style="background:linear-gradient(180deg,#ffffff 0%,#f8fafc 100%); border:1.5px solid #cbd5e1; border-radius:18px; padding:18px; box-sizing:border-box;">
        <!-- Header -->
        <div style="display:flex; align-items:center; justify-content:space-between; border-bottom:1.5px solid #e2e8f0; padding-bottom:12px; margin-bottom:14px;">
            <div style="display:flex; align-items:center; gap:10px;">
                <div style="width:40px; height:40px; border-radius:12px; background:linear-gradient(135deg,#2563eb,#4f46e5); color:#fff; display:flex; align-items:center; justify-content:center; font-size:18px; font-weight:900;">
                    🏸
                </div>
                <div>
                    <div style="font-size:15px; font-weight:900; color:#1e293b; letter-spacing:-0.2px;">YMCA BADMINTON CLUB</div>
                    <div style="font-size:11px; font-weight:700; color:#64748b;" id="share-card-session-date">Date</div>
                </div>
            </div>
            <div style="background:#eff6ff; color:#2563eb; font-size:11px; font-weight:800; padding:4px 10px; border-radius:16px; border:1px solid #bfdbfe; text-transform:uppercase;" id="share-card-group-name">
                Session
            </div>
        </div>

        <!-- 3 Highlights Cards (Present, Shuttles Used, Stock) -->
        <div style="display:grid; grid-template-columns:repeat(3, 1fr); gap:8px; margin-bottom:14px;">
            <div style="background:#ecfdf5; border:1.5px solid #a7f3d0; border-radius:12px; padding:10px 6px; text-align:center;">
                <div style="font-size:9.5px; font-weight:800; color:#047857; text-transform:uppercase;">PLAYERS</div>
                <div style="font-size:20px; font-weight:900; color:#065f46; margin-top:2px;" id="share-card-present-count">0</div>
                <div style="font-size:9.5px; font-weight:700; color:#059669;">Present</div>
            </div>
            <div style="background:#fef2f2; border:1.5px solid #fca5a5; border-radius:12px; padding:10px 6px; text-align:center;">
                <div style="font-size:9.5px; font-weight:800; color:#991b1b; text-transform:uppercase;">SHUTTLES USED</div>
                <div style="font-size:20px; font-weight:900; color:#dc2626; margin-top:2px;" id="share-card-shuttles-used">0</div>
                <div style="font-size:9.5px; font-weight:700; color:#b91c1c;">Today</div>
            </div>
            <div id="share-card-stock-box" style="background:#ffffff; border:1.5px solid #059669; border-radius:12px; padding:10px 6px; text-align:center;">
                <div style="font-size:9.5px; font-weight:800; color:#059669; text-transform:uppercase;" id="share-card-stock-lbl">STOCK LEFT</div>
                <div style="font-size:20px; font-weight:900; color:#059669; margin-top:2px;" id="share-card-stock-val">0</div>
                <div style="font-size:9.5px; font-weight:700; color:#059669;" id="share-card-stock-sub">Inventory</div>
            </div>
        </div>

        <!-- Start Time Bar -->
        <div style="background:#f8fafc; border:1px solid #e2e8f0; border-radius:10px; padding:8px 12px; display:flex; align-items:center; justify-content:space-between; margin-bottom:14px;">
            <span style="font-size:11px; font-weight:700; color:#64748b;"><i class="fa fa-clock-o" style="color:#3b82f6;"></i> Expected Start Time</span>
            <span style="font-size:12px; font-weight:900; color:#1e293b;" id="share-card-start-time">--:--</span>
        </div>

        <!-- Added Match Note -->
        <div id="share-card-notes-wrap" style="display:none; background:#fffdf7; border:1.5px solid #fed7aa; border-left:4px solid #f97316; border-radius:12px; padding:10px 12px; margin-bottom:14px; font-family:'Inter',sans-serif; text-align:left;">
            <div style="display:flex; align-items:center; justify-content:space-between; margin-bottom:4px;">
                <span style="font-size:10px; font-weight:800; color:#c2410c; text-transform:uppercase; letter-spacing:0.5px; display:inline-flex; align-items:center; gap:5px;">
                    <i class="fa fa-sticky-note" style="color:#ea580c; font-size:11px;"></i> <span id="share-card-notes-title">Match Note</span>
                </span>
                <span id="share-card-notes-badge" style="font-size:9.5px; font-weight:700; color:#9a3412; background:#ffedd5; padding:2px 7px; border-radius:8px; display:none;"></span>
            </div>
            <div id="share-card-notes-val" style="font-size:11.5px; font-weight:600; color:#431407; line-height:1.45; word-break:break-word;"></div>
        </div>

        <!-- Attendance Members List -->
        <div style="margin-bottom:10px;">
            <div style="font-size:11.5px; font-weight:800; color:#334155; text-transform:uppercase; letter-spacing:0.4px; margin-bottom:8px;">
                <i class="fa fa-check-circle" style="color:#10b981;"></i> Present Players (<span id="share-card-list-count">0</span>)
            </div>
            <div id="share-card-members-list" style="display:grid; grid-template-columns:repeat(2, 1fr); gap:6px;">
                <!-- dynamic member chips -->
            </div>
        </div>

        <!-- Absent Members List (if any) -->
        <div id="share-card-absent-wrap" style="display:none; margin-top:10px; border-top:1px dashed #cbd5e1; padding-top:8px;">
            <div style="font-size:11px; font-weight:800; color:#ef4444; text-transform:uppercase; margin-bottom:6px;">
                <i class="fa fa-times-circle"></i> Absent Players (<span id="share-card-absent-count">0</span>)
            </div>
            <div id="share-card-absent-list" style="display:flex; flex-wrap:wrap; gap:5px;">
                <!-- dynamic absent chips -->
            </div>
        </div>

        <!-- Footer -->
        <div style="margin-top:14px; padding-top:10px; border-top:1.5px solid #e2e8f0; display:flex; align-items:center; justify-content:space-between; font-size:9.5px; color:#94a3b8; font-weight:600;">
            <span>YMCA Badminton Match Summary</span>
            <span id="share-card-timestamp">Report</span>
        </div>
    </div>
</div>

<?php include 'mobile_bottom_nav.php'; ?>

<script src="https://cdnjs.cloudflare.com/ajax/libs/jquery/3.6.0/jquery.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/html2canvas/1.4.1/html2canvas.min.js"></script>
<script src="../../js/plugins/sweetalert/sweetalert.min.js"></script>
<script src="../../app_js/sweetalert-finez.js"></script>

<script>
const IS_ONLY_ATTENDANCE_MASTER = <?php echo $is_only_attendance_master ? 'true' : 'false'; ?>;
const TODAY_DATE_STR = '<?php echo date('Y-m-d'); ?>';

function load_overlay() {
    if ($('#mob-global-loader').length === 0) {
        $('body').append(`
            <div id="mob-global-loader">
                <div class="mob-spinner-box">
                    <div class="mob-spinner-wrapper">
                        <div class="mob-spinner-ring"></div>
                        <img src="../../favicon.png" class="mob-spinner-logo" alt="YMCA Logo" onerror="this.onerror=null; this.src='../../favicon.ico';">
                    </div>
                    <div class="mob-spinner-text">Please wait...</div>
                </div>
            </div>
        `);
    }
    $('#mob-global-loader').addClass('active');
}

function close_overlay() {
    $('#mob-global-loader').removeClass('active');
    if (typeof $.LoadingOverlay === 'function') {
        try { $.LoadingOverlay('hide'); } catch(e){}
    }
}

$(document).ready(function(){
    const today = new Date();
    const opts = { weekday:'long', year:'numeric', month:'short', day:'numeric' };
    $('#mob-today-date').text(today.toLocaleDateString('en-US', opts));

    const yyyy = today.getFullYear();
    const mm   = String(today.getMonth()+1).padStart(2,'0');
    const dd   = String(today.getDate()).padStart(2,'0');
    $('#date_search').val(yyyy+'-'+mm+'-'+dd);

    loadGroups();
});

var mob_groups_map = {};
var mob_current_member_id = <?php echo (int)($member_id); ?>;

function loadGroups(){
    $.post('../api/attendance.php', { action:'load_groups' }, function(data){
        try {
            const obj = JSON.parse(data);
            const groups = obj[0] || [];
            let pillsHtml = '', selectHtml = '';
            mob_groups_map = {};
            groups.forEach(function(g, idx){
                mob_groups_map[g.id] = g;
                const icon   = g.name.toLowerCase().includes('evening') ? '🌙' : '☀️';
                const active = idx === 0 ? ' active' : '';
                pillsHtml  += '<button type="button" class="session-pill'+active+'" id="pill_'+g.id+'" onclick="selectPill('+g.id+')">' + icon + ' ' + g.name + '</button>';
                selectHtml += '<option value="'+g.id+'">'+g.name+'</option>';
            });
            $('#mob-session-pills').html(pillsHtml);
            $('#selected_group').html(selectHtml);
            if (groups.length > 0) $('#selected_group').val(groups[0].id);
            if (groups.length === 0) $('#mob-session-selector-container').hide();
            else                     $('#mob-session-selector-container').show();
            if (groups.length > 0) updateMobTomorrowDateToggle();
            else fetchStatus();
        } catch(e) { console.error(e); fetchStatus(); }
    });
}

function selectPill(groupId){
    $('#selected_group').val(groupId);
    $('.session-pill').removeClass('active');
    $('#pill_'+groupId).addClass('active');
    updateMobTomorrowDateToggle();
}

function updateMobTomorrowDateToggle() {
    var grp = mob_groups_map[$('#selected_group').val()];
    if (grp && parseInt(grp.allow_tomorrow_attendance) === 1) {
        $('#mob-date-toggle-container').css('display', 'flex');
    } else {
        $('#mob-date-toggle-container').hide();
    }
    selectMobAttDate('today');
}

function selectMobAttDate(type) {
    var targetDate = new Date();
    if (type === 'tomorrow') {
        targetDate.setDate(targetDate.getDate() + 1);
        $('#mob_btn_date_today').css({ background:'transparent', color:'#64748b', boxShadow:'none' });
        $('#mob_btn_date_tomorrow').css({ background:'#4f46e5', color:'#fff', boxShadow:'0 3px 10px rgba(79,70,229,0.3)' });
    } else {
        $('#mob_btn_date_tomorrow').css({ background:'transparent', color:'#64748b', boxShadow:'none' });
        $('#mob_btn_date_today').css({ background:'#4f46e5', color:'#fff', boxShadow:'0 3px 10px rgba(79,70,229,0.3)' });
    }
    var yyyy = targetDate.getFullYear();
    var mm   = String(targetDate.getMonth()+1).padStart(2,'0');
    var dd   = String(targetDate.getDate()).padStart(2,'0');
    $('#date_search').val(yyyy+'-'+mm+'-'+dd);
    const opts = { weekday:'long', year:'numeric', month:'short', day:'numeric' };
    $('#mob-today-date').text(targetDate.toLocaleDateString('en-US', opts) + (type==='tomorrow' ? ' 🌙' : ''));
    fetchStatus();
}

function formatTime12h(time24) {
    if (!time24) return '';
    var parts = time24.split(':');
    var h = parseInt(parts[0], 10), m = parts[1];
    var ampm = h >= 12 ? 'PM' : 'AM';
    h = h % 12 || 12;
    return h + ':' + m + ' ' + ampm;
}

var mob_stock_base = 0;
var mob_stock_remaining = 0;

function updateHomeStockLabel(currentVal) {
    var val = parseInt(currentVal) || 0;
    var netRemaining = Math.max(0, mob_stock_base - val);
    var isLow = (mob_stock_base <= 0 || netRemaining < 3);
    var color = isLow ? '#dc2626' : '#059669';
    var bg = (isLow && netRemaining <= 0) ? '#fef2f2' : '#ffffff';

    $('#home_stock_box').css({
        'border-color': color,
        'background-color': bg,
        'box-shadow': isLow ? '0 1px 3px rgba(220,38,38,0.2)' : '0 1px 3px rgba(5,150,105,0.2)'
    });
    $('#home_stock_box_lbl').css('color', color);
    $('#home_stock_box_val').css('color', color).text(netRemaining);

    if (mob_stock_base <= 0 || netRemaining <= 0) {
        $('#home_stock_box').attr('title', 'OUT OF STOCK (0 shuttles remaining)');
    } else {
        $('#home_stock_box').attr('title', 'Remaining Stock: ' + netRemaining + ' shuttles');
    }
}

function fetchHomeShuttleReading() {
    var date = $('#date_search').val();
    var group = $('#selected_group').val();
    if (!date || !group) return;
    $.post('../api/attendance.php', { action: 'get_shuttle_reading', group_id: group, date: date }, function(res) {
        try {
            var r = typeof res === 'string' ? JSON.parse(res) : res;
            if (r.status === 'success') {
                window.currentShuttleReadingData = r;
                window._currentShuttleReadingNotes = (r.notes || '').trim();
                window._prevShuttleReadingNotes = (r.prev_notes || '').trim();

                mob_stock_base = parseInt(r.stock_remaining || 0);
                mob_stock_remaining = mob_stock_base;
                var currentUsed = r.shuttles_used > 0 ? r.shuttles_used : '';
                $('#home_shuttles_used').val(currentUsed);
                updateHomeStockLabel(currentUsed);
                $('#home_shuttle_notes').val(r.notes || '');

                if (r.has_record && (r.last_user_name || r.created_by_name)) {
                    var lastUser = r.last_user_name || r.created_by_name;
                    var creatorLabel = r.is_created_by_me ? 'You' : lastUser;
                    var actWord = r.action_word || (r.is_edited ? 'updated' : 'added');
                    var noticeHtml = '<i class="fa fa-info-circle" style="color:#2563eb; margin-right:4px;"></i> <strong>' + creatorLabel + '</strong> ' + actWord + ' count (' + r.shuttles_used + ' shuttles). Do you want to edit?';
                    $('#home_shuttle_creator_notice').html(noticeHtml).show();
                } else {
                    $('#home_shuttle_creator_notice').hide().html('');
                }

                // Render Previous Added Note Section
                var currentNote = (r.notes || '').trim();
                var prevNote    = (r.prev_notes || '').trim();
                var $noteCard   = $('#home_shuttle_prev_note_section');
                window._lastAvailableShuttleNote = '';

                if (currentNote !== '') {
                    window._lastAvailableShuttleNote = currentNote;
                    var noteAuthor = r.is_created_by_me ? 'You' : (r.last_user_name || r.created_by_name || 'Admin');
                    var noteStatus = r.is_edited ? 'Updated' : 'Added';

                    $('#home_shuttle_prev_note_label').text('Previously Added Note');
                    $('#home_shuttle_prev_note_badge').text(noteAuthor + ' • ' + noteStatus).show();
                    $('#home_shuttle_prev_note_text').text(currentNote);

                    if (prevNote !== '') {
                        var olderMeta = (r.prev_formatted_date || r.prev_reading_date || '');
                        if (r.prev_user_name) olderMeta += ' • ' + r.prev_user_name;
                        $('#home_shuttle_older_note_text').text(prevNote);
                        $('#home_shuttle_older_note_badge').text(olderMeta);
                        $('#home_shuttle_older_note_wrap').show();
                    } else {
                        $('#home_shuttle_older_note_wrap').hide();
                    }

                    if (!$('#home_shuttle_notes').val()) {
                        $('#home_btn_use_note').css('display', 'inline-flex');
                    } else {
                        $('#home_btn_use_note').hide();
                    }

                    $noteCard.fadeIn(150);
                } else if (prevNote !== '') {
                    window._lastAvailableShuttleNote = prevNote;
                    var prevMeta = (r.prev_formatted_date || r.prev_reading_date || '');
                    if (r.prev_user_name) prevMeta += ' • ' + r.prev_user_name;

                    $('#home_shuttle_prev_note_label').text('Previous Added Note');
                    $('#home_shuttle_prev_note_badge').text(prevMeta).show();
                    $('#home_shuttle_prev_note_text').text(prevNote);
                    $('#home_shuttle_older_note_wrap').hide();

                    if (!$('#home_shuttle_notes').val()) {
                        $('#home_btn_use_note').css('display', 'inline-flex');
                    } else {
                        $('#home_btn_use_note').hide();
                    }

                    $noteCard.fadeIn(150);
                } else {
                    $noteCard.hide();
                    $('#home_btn_use_note').hide();
                }
            }
        } catch(e){}
    });
}

function copyShuttleNoteToInput() {
    if (window._lastAvailableShuttleNote) {
        $('#home_shuttle_notes').val(window._lastAvailableShuttleNote).focus();
        $('#home_btn_use_note').hide();
    }
}

function saveHomeShuttleReading() {
    var date = $('#date_search').val();
    var group = $('#selected_group').val();
    var shuttlesUsed = $('#home_shuttles_used').val();
    var shuttleNotes = $('#home_shuttle_notes').val() ? $('#home_shuttle_notes').val().trim() : '';
    if (!date || !group) return;

    if (IS_ONLY_ATTENDANCE_MASTER && date < TODAY_DATE_STR) {
        swal('Restricted', 'Attendance Masters can only record for today.', 'warning');
        return;
    }

    var countVal = parseInt(shuttlesUsed) || 0;
    if (countVal > 0 && mob_stock_remaining <= 0) {
        swal('Out of Stock!', 'Available shuttle cock inventory is 0. Please add inward stock first before recording shuttle count.', 'error');
        return;
    }
    if (countVal > mob_stock_remaining && mob_stock_remaining > 0) {
        swal('Insufficient Stock!', 'Only ' + mob_stock_remaining + ' shuttles remaining in inventory.', 'error');
        return;
    }

    if (typeof load_overlay === 'function') load_overlay();
    $.post('../api/attendance.php', {
        action: 'save_shuttle_reading',
        group_id: group,
        date: date,
        shuttles_used: shuttlesUsed,
        notes: shuttleNotes
    }, function(res) {
        if (typeof close_overlay === 'function') close_overlay();
        swal('Saved!', 'Session shuttle count saved successfully.', 'success');
        fetchHomeShuttleReading();
    }).fail(function(xhr) {
        if (typeof close_overlay === 'function') close_overlay();
        var msg = xhr.responseText || 'Could not save shuttle count.';
        swal('Error', msg, 'error');
    });
}

function fetchStatus(){
    const date  = $('#date_search').val();
    const group = $('#selected_group').val();

    $('#mob-action-area').html('<div style="height:90px;border-radius:16px;background:linear-gradient(90deg,#f1f5f9 25%,#e2e8f0 50%,#f1f5f9 75%);background-size:200% 100%;animation:shimmer 1.4s infinite;"></div>');

    // Always load game status alongside
    loadGameStatus(date, group);
    fetchHomeShuttleReading();

    $.post('../api/attendance.php', { action:'check_holiday', date:date, group:group }, function(res){
        const r = JSON.parse(res);
        if(r.is_holiday){
            $('#mob-action-area').html('<div class="att-status-banner holiday-banner"><span class="att-status-icon"><i class="fa fa-moon-o"></i></span><div><div>Holiday / Leave</div><div style="font-size:11px;font-weight:500;margin-top:2px;">No attendance for this session today</div></div></div>');
            $('#mob-present-card').hide();
        } else {
            $.post('../api/attendance.php', { action:'fetch_Attendance_details', date:date, group:group }, function(data2){
                const members = JSON.parse(data2);

                $.post('../api/attendance.php', { action:'fetch_temp_status', date:date, group:group }, function(resData){
                    const tempRes    = (typeof resData === 'string') ? JSON.parse(resData) : resData;
                    const tempStatus = tempRes ? tempRes.temp_status   : null;
                    const expTime    = tempRes ? tempRes.expected_time : null;
                    const etaHtml    = expTime ? '<div class="src-eta">⏰ Time: ' + formatTime12h(expTime) + '</div>' : '';

                    if (tempRes && tempRes.member_id && parseInt(tempRes.member_id) > 0) {
                        mob_current_member_id = parseInt(tempRes.member_id);
                    }

                    let amPresent = false;
                    members.forEach(function(m){ if(parseInt(m.member_id) === mob_current_member_id) amPresent = true; });

                    let actionHtm = '';
                    const canMarkAllAtt = <?php echo $can_mark_all_att ? 'true' : 'false'; ?>;

                    if(amPresent){
                        actionHtm  = '<div class="status-result-card src-present">';
                        actionHtm += '  <div class="src-icon"><i class="fa fa-check-circle"></i></div>';
                        actionHtm += '  <div class="src-title">Checked In ✓</div>';
                        actionHtm += '  <div class="src-sub">Your attendance is marked for today</div>';
                        actionHtm += etaHtml;
                        actionHtm += '  <div class="src-actions"><button class="src-btn-ghost" onclick="mobUnmarkAttendance()"><i class="fa fa-times"></i> Unmark Attendance</button></div>';
                        actionHtm += '</div>';

                    } else if(tempStatus === 'absent'){
                        actionHtm  = '<div class="status-result-card src-absent">';
                        actionHtm += '  <div class="src-icon"><i class="fa fa-times-circle"></i></div>';
                        actionHtm += '  <div class="src-title">Marked Absent</div>';
                        // actionHtm += '  <div class="src-sub">Temporary · auto-clears in 2 days</div>';
                        actionHtm += '  <div class="src-actions"><button class="src-btn-green" onclick="mobMarkAttendance()"><i class="fa fa-check-circle"></i> Mark Present</button><button class="src-btn-ghost" onclick="mobClearTempStatus()"><i class="fa fa-refresh"></i> Clear</button></div>';
                        actionHtm += '</div>';

                    } else {
                        actionHtm  = '<div style="display:flex;flex-direction:column;gap:14px;width:100%;">';
                        actionHtm += '  <div class="att-section-divider"><i class="fa fa-calendar-check-o" style="color:#4f46e5;"></i> Mark Attendance</div>';
                        actionHtm += '  <div class="att-grid-2">';
                        actionHtm += '    <button class="att-big-btn btn-present" onclick="openHCModal(\'present\')"><div class="btn-icon-wrap"><i class="fa fa-check-circle"></i></div>Present</button>';
                        actionHtm += '    <button class="att-big-btn btn-absent"  onclick="mobMarkTempStatus(\'absent\')"><div class="btn-icon-wrap"><i class="fa fa-times-circle"></i></div>Absent</button>';
                        actionHtm += '  </div>';
                        actionHtm += '</div>';
                    }

                    if(canMarkAllAtt){
                        actionHtm += '<div style="margin-top:12px;"><a href="group_attendance.php" style="background:linear-gradient(135deg,#059669,#10b981);border:none;border-radius:14px;padding:14px;color:#fff;text-align:center;text-decoration:none;display:flex;align-items:center;justify-content:center;gap:8px;font-weight:800;font-size:13.5px;font-family:\'Inter\',sans-serif;"><i class="fa fa-users"></i> Mark All Group Attendance</a></div>';
                    }

                    $('#mob-action-area').html(actionHtm);
                    loadPresentMembers(date, group, members.length);
                    $('#mob-present-card').show();
                });
            });
        }
    });
}

function mobMarkAttendance(expectedTime){
    if (mob_current_member_id <= 0) {
        swal('Notice', 'Your user account is not linked to a member profile.', 'info');
        return;
    }
    var groupId    = $('#selected_group').val();
    var dateVal    = $('#date_search').val();

    if (IS_ONLY_ATTENDANCE_MASTER && dateVal < TODAY_DATE_STR) {
        swal('Restricted', 'Attendance Masters can only mark attendance for today.', 'warning');
        return;
    }

    if(typeof load_overlay === 'function') load_overlay();

    var postData = {
        action        : 'add_attendance',
        val           : groupId,
        date          : dateVal,
        single_member : '1',
        expected_time : expectedTime || '',
        'member_ids[]': mob_current_member_id
    };

    $.post('../api/attendance.php', postData, function(res){
        if(typeof close_overlay === 'function') close_overlay();
        fetchStatus();
        swal({
            title: "Checked In!",
            text: "Attendance marked present.\n\n🔴 REMINDER: Don't forget to enter the count of shuttles used after today's game!",
            type: "warning",
            confirmButtonColor: "#dc2626",
            confirmButtonText: "OK, Got It!"
        });
    }).fail(function(xhr){
        if(typeof close_overlay === 'function') close_overlay();
        var msg = xhr.responseText || 'Could not mark attendance. Please try again.';
        swal('Error', msg, 'error');
    });
}

function mobMarkTempStatus(status){
    if(typeof load_overlay === 'function') load_overlay();
    $.post('../api/attendance.php', {
        action        : 'save_temp_status',
        group         : $('#selected_group').val(),
        date          : $('#date_search').val(),
        status        : status,
        expected_time : ''
    }, function(res){
        if(typeof close_overlay === 'function') close_overlay();
        var r = (typeof res === 'string') ? JSON.parse(res) : res;
        if(r && r.status === 'success'){
            fetchStatus();
        } else {
            swal('Error', (r && r.message) || 'Failed to update status', 'error');
        }
    }).fail(function(){
        if(typeof close_overlay === 'function') close_overlay();
        swal('Error', 'Network error. Please try again.', 'error');
    });
}

function mobClearTempStatus(){
    mobMarkTempStatus('clear');
}

/* ── Today's Game Status ──────────────────────────────── */
function loadGameStatus(date, group) {
    if (!date || !group) return;
    $.post('../api/attendance.php', { action:'load_today_attendance_summary', date:date, group:group }, function(data){
        var list = [];
        try { list = (typeof data === 'string') ? JSON.parse(data) : data; } catch(e) { list = []; }

        var presentCount = 0, absentCount = 0;
        var timings = [];

        if (list && list.length > 0) {
            list.forEach(function(m){
                var name = [m.first_name, m.middle_name, m.last_name].filter(Boolean).join(' ');
                if (m.status === 'present') {
                    presentCount++;
                } else if (m.status === 'absent') {
                    absentCount++;
                }

                if (m.expected_time && m.status === 'present') {
                    timings.push({ time: m.expected_time, name: name, status: m.status });
                }
            });
        }

        $('#gs-present-count').text(presentCount);
        $('#gs-absent-count').text(absentCount);

        // Sort timings chronologically ascending (e.g. 11:00 AM, 11:15 AM)
        timings.sort(function(a, b){ return a.time.localeCompare(b.time); });

        // Expected Start Time is the 2nd earliest expected arrival time
        var gameStartTime = null;
        if (presentCount >= 2) {
            if (timings.length >= 2) {
                gameStartTime = timings[1].time; // 2nd earliest time (e.g. 11:15 AM)
            } else if (timings.length === 1) {
                gameStartTime = timings[0].time;
            }
        }

        if (presentCount >= 2 && gameStartTime) {
            $('#gs-start-time').text(formatTime12h(gameStartTime));
            $('#gs-start-sub').text('When 2nd member is expected to arrive');
        } else if (presentCount === 1) {
            $('#gs-start-time').text('TBD');
            $('#gs-start-sub').text('1 member present · Minimum 2 members required');
        } else if (presentCount > 0) {
            $('#gs-start-time').text('TBD');
            $('#gs-start-sub').text('Minimum 2 present members required');
        } else {
            $('#gs-start-time').text('TBD');
            $('#gs-start-sub').text('No member status recorded yet');
        }

        $('#mob-game-status-card').show();
    }).fail(function(){
        // Show card even on failure with default state
        $('#gs-start-time').text('TBD');
        $('#gs-start-sub').text('Unable to load status');
        $('#mob-game-status-card').show();
    });
}

function mobUnmarkAttendance(){
    swal({
        title:"Unmark Attendance?",
        text:"Remove your attendance for today?",
        type:"warning",
        showCancelButton:true,
        confirmButtonColor:"#ef4444",
        confirmButtonText:"Yes, Unmark",
        closeOnConfirm:true
    }, function(ok){
        if(!ok) return;
        if(typeof load_overlay === 'function') {
            load_overlay();
        } else if(typeof $.LoadingOverlay === 'function') {
            $.LoadingOverlay('show');
        }

        $.post('../api/attendance.php', {
            action        : 'add_attendance',
            val           : $('#selected_group').val(),
            date          : $('#date_search').val(),
            single_member : '1',
            member_ids    : []
        }, function(){
            if(typeof close_overlay === 'function') {
                close_overlay();
            } else if(typeof $.LoadingOverlay === 'function') {
                $.LoadingOverlay('hide');
            }
            fetchStatus();
        }).fail(function(xhr){
            if(typeof close_overlay === 'function') close_overlay();
            swal('Error', xhr.responseText || 'Could not unmark attendance.', 'error');
        });
    });
}

function loadPresentMembers(date, group, count){
    $.post('../api/attendance.php', { action:'load_today_attendance_summary', date:date, group:group }, function(data){
        const list = (typeof data === 'string') ? JSON.parse(data) : data;
        window.currentMemberList = list || [];
        if(!list || list.length === 0){
            $('#mob-present-list').html('<div style="color:#94a3b8;font-size:13px;text-align:center;padding:10px 0;">No member status recorded today.</div>');
            $('#mob-present-count').text(0);
            return;
        }
        let html = '';
        list.forEach(function(m){
            const img = (m.img && m.img != '0') ? '../../image_upload/members/thumbnails/'+m.img : '../../img/customer.png';
            const name = [m.first_name, m.middle_name, m.last_name].filter(Boolean).join(' ');
            var timeStr = m.expected_time ? (' (Time: ' + formatTime12h(m.expected_time) + ')') : '';

            var badgeHtm = '';
            if (m.status === 'present') {
                badgeHtm = '<span class="mob-chip mob-chip-green"><i class="fa fa-check"></i>' + timeStr + '</span>';
            } else if (m.status === 'absent') {
                badgeHtm = '<span class="mob-chip" style="background:rgba(239,68,68,0.12); color:#dc2626; border:1px solid rgba(239,68,68,0.25);"><i class="fa fa-times"></i></span>';
            }

            html += '<div class="present-member-row">' +
                    '<img class="present-avatar" src="'+img+'" onerror="this.src=\'../../img/customer.png\'">' +
                    '<span class="present-name">'+name+'</span>' +
                    badgeHtm +
                    '</div>';
        });
        $('#mob-present-list').html(html);
        $('#mob-present-count').text(list.length);
    });
}

/* ── Match Summary Image Sharing ──────────────────────────── */
var lastGeneratedShareFile = null;
var lastGeneratedDataUrl = null;

function generateMatchSummaryShareText() {
    var dateStr = $('#mob-today-date').text() || TODAY_DATE_STR;
    var sessionName = $('#mob-session-pills .session-pill.active').text().trim() || $('#selected_group option:selected').text() || 'Session';
    var readingData = window.currentShuttleReadingData || {};
    var shuttlesUsed = ($('#home_shuttles_used').length && $('#home_shuttles_used').val())
        ? $('#home_shuttles_used').val()
        : (readingData.shuttles_used || '0');
    var stock = ($('#home_stock_box_val').length && $('#home_stock_box_val').text())
        ? $('#home_stock_box_val').text()
        : (readingData.stock_remaining !== undefined ? String(readingData.stock_remaining) : '0');

    var note = '';
    if ($('#home_shuttle_notes').length && $('#home_shuttle_notes').val() && $('#home_shuttle_notes').val().trim()) {
        note = $('#home_shuttle_notes').val().trim();
    } else if (window._currentShuttleReadingNotes && window._currentShuttleReadingNotes.trim()) {
        note = window._currentShuttleReadingNotes.trim();
    } else if (readingData.notes && String(readingData.notes).trim()) {
        note = String(readingData.notes).trim();
    } else if (window._lastAvailableShuttleNote && window._lastAvailableShuttleNote.trim()) {
        note = window._lastAvailableShuttleNote.trim();
    } else if (readingData.prev_notes && String(readingData.prev_notes).trim()) {
        note = String(readingData.prev_notes).trim();
    }

    var members = window.currentMemberList || [];
    var presentList = members.filter(function(m){ return m.status === 'present'; });
    var absentList = members.filter(function(m){ return m.status === 'absent'; });

    var text = "🏸 *YMCA BADMINTON MATCH REPORT*\n";
    text += "📅 *Date:* " + dateStr + "\n";
    text += "⏰ *Session:* " + sessionName + "\n";
    text += "👥 *Players Present:* " + presentList.length + "\n";
    text += "🏸 *Shuttles Used:* " + shuttlesUsed + "\n";
    text += "📦 *Remaining Stock:* " + stock + "\n";
    if (note) {
        text += "📝 *Note:* " + note + "\n";
    }
    text += "\n";

    if (presentList.length > 0) {
        text += "*Present Players:*\n";
        presentList.forEach(function(m, idx){
            var name = [m.first_name, m.middle_name, m.last_name].filter(Boolean).join(' ');
            var timeStr = m.expected_time ? (" (" + formatTime12h(m.expected_time)) + ")" : "";
            text += (idx + 1) + ". " + name + timeStr + "\n";
        });
    }

    if (absentList.length > 0) {
        text += "\n*Absent Players:*\n";
        absentList.forEach(function(m, idx){
            var name = [m.first_name, m.middle_name, m.last_name].filter(Boolean).join(' ');
            text += "• " + name + "\n";
        });
    }

    return text;
}

function openImageFallbackModal(dataUrl, fileName) {
    lastGeneratedDataUrl = dataUrl;
    $('#mob-share-preview-img').attr('src', dataUrl);
    $('#mob-share-download-btn').attr('href', dataUrl).attr('download', fileName);
    $('#mob-share-preview-modal').css('display', 'flex');
}

function shareImageViaNativeOrWhatsApp() {
    if (lastGeneratedShareFile && navigator.canShare && navigator.canShare({ files: [lastGeneratedShareFile] })) {
        navigator.share({
            title: 'YMCA Badminton Match Report',
            text: generateMatchSummaryShareText(),
            files: [lastGeneratedShareFile]
        }).catch(function(e){ console.log(e); });
    } else {
        var text = generateMatchSummaryShareText();
        var waUrl = 'https://api.whatsapp.com/send?text=' + encodeURIComponent(text);
        window.open(waUrl, '_blank');
    }
}

function shareTodayMatchImage() {
    var dateVal = $('#date_search').val() || TODAY_DATE_STR;
    var groupVal = $('#selected_group').val();
    if (!groupVal) {
        swal('Notice', 'Please select a session group first.', 'info');
        return;
    }

    if (typeof load_overlay === 'function') load_overlay();

    // Fetch fresh shuttle reading & notes for this group and date to guarantee the latest added note is included
    $.post('../api/attendance.php', { action: 'get_shuttle_reading', group_id: groupVal, date: dateVal }, function(res) {
        try {
            var r = typeof res === 'string' ? JSON.parse(res) : res;
            if (r.status === 'success') {
                window.currentShuttleReadingData = r;
                window._currentShuttleReadingNotes = (r.notes || '').trim();
                window._prevShuttleReadingNotes = (r.prev_notes || '').trim();
            }
        } catch(e) {}
        proceedToGenerateShareImage(dateVal, groupVal);
    }).fail(function() {
        proceedToGenerateShareImage(dateVal, groupVal);
    });
}

function proceedToGenerateShareImage(dateVal, groupVal) {
    var members = window.currentMemberList || [];
    var presentList = members.filter(function(m){ return m.status === 'present'; });
    var absentList = members.filter(function(m){ return m.status === 'absent'; });

    var dateStr = $('#mob-today-date').text() || dateVal;
    var sessionName = $('#mob-session-pills .session-pill.active').text().trim() || $('#selected_group option:selected').text() || 'Session';
    var readingData = window.currentShuttleReadingData || {};

    var shuttlesUsed = ($('#home_shuttles_used').length && $('#home_shuttles_used').val())
        ? $('#home_shuttles_used').val()
        : (readingData.shuttles_used || '0');
    var stock = ($('#home_stock_box_val').length && $('#home_stock_box_val').text())
        ? $('#home_stock_box_val').text()
        : (readingData.stock_remaining !== undefined ? String(readingData.stock_remaining) : '0');
    var startTime = $('#gs-start-time').text() || '--:--';
    var isLow = parseInt(stock) < 3;

    // Resolve added note:
    // 1. Current value typed into the input field
    // 2. Saved note from readingData (r.notes from tbl_shuttle_cock_readings)
    // 3. Or last available note / previous session note (r.prev_notes)
    var note = '';
    var noteBadge = '';
    var noteTitle = 'Match Note';

    if ($('#home_shuttle_notes').length && $('#home_shuttle_notes').val() && $('#home_shuttle_notes').val().trim()) {
        note = $('#home_shuttle_notes').val().trim();
        var author = readingData.is_created_by_me ? 'You' : (readingData.last_user_name || readingData.created_by_name || '');
        if (author) {
            noteBadge = author + (readingData.is_edited ? ' • Updated' : ' • Added');
        }
    } else if (window._currentShuttleReadingNotes && window._currentShuttleReadingNotes.trim()) {
        note = window._currentShuttleReadingNotes.trim();
        var author = readingData.is_created_by_me ? 'You' : (readingData.last_user_name || readingData.created_by_name || '');
        if (author) {
            noteBadge = author + (readingData.is_edited ? ' • Updated' : ' • Added');
        }
    } else if (readingData.notes && String(readingData.notes).trim()) {
        note = String(readingData.notes).trim();
        var author = readingData.is_created_by_me ? 'You' : (readingData.last_user_name || readingData.created_by_name || '');
        if (author) {
            noteBadge = author + (readingData.is_edited ? ' • Updated' : ' • Added');
        }
    } else if (window._lastAvailableShuttleNote && window._lastAvailableShuttleNote.trim()) {
        note = window._lastAvailableShuttleNote.trim();
        noteTitle = 'Previous Session Note';
        var prevMeta = (readingData.prev_formatted_date || readingData.prev_reading_date || '');
        if (readingData.prev_user_name) prevMeta += ' • ' + readingData.prev_user_name;
        noteBadge = prevMeta;
    } else if (readingData.prev_notes && String(readingData.prev_notes).trim()) {
        note = String(readingData.prev_notes).trim();
        noteTitle = 'Previous Session Note';
        var prevMeta = (readingData.prev_formatted_date || readingData.prev_reading_date || '');
        if (readingData.prev_user_name) prevMeta += ' • ' + readingData.prev_user_name;
        noteBadge = prevMeta;
    }

    // Populate Template
    $('#share-card-session-date').text(dateStr);
    $('#share-card-group-name').text(sessionName);
    $('#share-card-present-count').text(presentList.length);
    $('#share-card-shuttles-used').text(shuttlesUsed);
    $('#share-card-stock-val').text(stock);
    $('#share-card-start-time').text(startTime);

    if (note) {
        $('#share-card-notes-title').text(noteTitle);
        $('#share-card-notes-val').text(note);
        if (noteBadge) {
            $('#share-card-notes-badge').text(noteBadge).show();
        } else {
            $('#share-card-notes-badge').hide().text('');
        }
        $('#share-card-notes-wrap').show();
    } else {
        $('#share-card-notes-wrap').hide();
    }

    var stockColor = isLow ? '#dc2626' : '#059669';
    var stockBg = (isLow && parseInt(stock) <= 0) ? '#fef2f2' : '#ffffff';
    $('#share-card-stock-box').css({
        'border-color': stockColor,
        'background': stockBg
    });
    $('#share-card-stock-lbl, #share-card-stock-val, #share-card-stock-sub').css('color', stockColor);

    // Populate Present Players
    $('#share-card-list-count').text(presentList.length);
    var pListHtm = '';
    if (presentList.length === 0) {
        pListHtm = '<div style="grid-column:span 2; color:#94a3b8; font-size:11px; text-align:center; padding:10px 0;">No players marked present yet</div>';
    } else {
        presentList.forEach(function(m, idx){
            var name = [m.first_name, m.middle_name, m.last_name].filter(Boolean).join(' ');
            var timeStr = m.expected_time ? (' ' + formatTime12h(m.expected_time)) : '';
            pListHtm += '<div style="display:flex; align-items:center; gap:5px; background:#f1f5f9; padding:5px 8px; border-radius:8px; border:1px solid #e2e8f0; font-size:10.5px; font-weight:700; color:#1e293b; overflow:hidden;">' +
                        '<i class="fa fa-check-circle" style="color:#10b981; font-size:11px; flex-shrink:0;"></i>' +
                        '<span style="white-space:nowrap; overflow:hidden; text-overflow:ellipsis; flex:1;">' + (idx + 1) + '. ' + name + '</span>' +
                        (timeStr ? '<span style="font-size:9px; color:#64748b; font-weight:600; flex-shrink:0;">' + timeStr + '</span>' : '') +
                        '</div>';
        });
    }
    $('#share-card-members-list').html(pListHtm);

    // Populate Absent Players
    if (absentList.length > 0) {
        $('#share-card-absent-count').text(absentList.length);
        var aListHtm = '';
        absentList.forEach(function(m){
            var name = [m.first_name, m.middle_name, m.last_name].filter(Boolean).join(' ');
            aListHtm += '<span style="background:#fef2f2; border:1px solid #fecaca; color:#dc2626; padding:3px 7px; border-radius:6px; font-size:10px; font-weight:700;">' + name + '</span>';
        });
        $('#share-card-absent-list').html(aListHtm);
        $('#share-card-absent-wrap').show();
    } else {
        $('#share-card-absent-wrap').hide();
    }

    var now = new Date();
    $('#share-card-timestamp').text('Generated ' + now.toLocaleTimeString([], { hour:'2-digit', minute:'2-digit' }));

    if (typeof html2canvas === 'undefined') {
        if (typeof close_overlay === 'function') close_overlay();
        swal('Loading...', 'Rendering library is loading. Please try again in a moment.', 'info');
        return;
    }

    var targetEl = document.getElementById('match-share-template');

    setTimeout(function(){
        html2canvas(targetEl, {
            scale: 2,
            useCORS: true,
            allowTaint: true,
            backgroundColor: '#ffffff'
        }).then(function(canvas){
            if (typeof close_overlay === 'function') close_overlay();

            canvas.toBlob(function(blob){
                if (!blob) {
                    swal('Error', 'Could not create image blob.', 'error');
                    return;
                }
                var fileName = 'YMCA_Match_Report_' + dateVal + '.png';
                var file = new File([blob], fileName, { type: 'image/png' });
                lastGeneratedShareFile = file;

                var dataUrl = canvas.toDataURL('image/png');
                lastGeneratedDataUrl = dataUrl;

                // Try Web Share API with file support first
                if (navigator.canShare && navigator.canShare({ files: [file] })) {
                    navigator.share({
                        files: [file],
                        title: 'YMCA Badminton Match Report',
                        text: '🏸 Match Report for ' + dateStr + ' (' + sessionName + ')'
                    }).catch(function(err){
                        if (err.name !== 'AbortError') {
                            openImageFallbackModal(dataUrl, fileName);
                        }
                    });
                } else {
                    // Fallback modal for desktop or devices without navigator.share files
                    openImageFallbackModal(dataUrl, fileName);
                }
            }, 'image/png');
        }).catch(function(err){
            if (typeof close_overlay === 'function') close_overlay();
            console.error(err);
            swal('Error', 'Failed to generate report image: ' + (err.message || ''), 'error');
        });
    }, 120);
}

/* ── Expected Time & Present Modal ──────────────────────────── */
var currentAttModalStatus = 'present';

function openHCModal(status) {
    currentAttModalStatus = 'present';

    $('#hc-modal-title').text('⏰ Expected Arrival Time');
    $('#hc-modal-sub').text('Let your group know when you will likely arrive for Present check-in');
    $('#hc-btn-confirm').html('<i class="fa fa-check-circle"></i> Confirm Present').css('background', 'linear-gradient(135deg,#10b981,#059669)');

    var now = new Date();
    var hours24 = now.getHours();
    var minutes = now.getMinutes();

    // Auto-select closest 5-minute interval to current time
    var roundedMin = Math.round(minutes / 5) * 5;
    if (roundedMin >= 60) {
        roundedMin = 0;
        hours24 = (hours24 + 1) % 24;
    }

    var ampm = hours24 >= 12 ? 'PM' : 'AM';
    var hours12 = hours24 % 12;
    if (hours12 === 0) hours12 = 12;

    var hhStr = String(hours12).padStart(2, '0');
    var mmStr = String(roundedMin).padStart(2, '0');

    $('#hc-hour-select').val(hhStr);
    $('#hc-min-select').val(mmStr);
    $('#hc-ampm-select').val(ampm);

    $('#hc-modal').addClass('active');
}

function closeHCModal() {
    $('#hc-modal').removeClass('active');
}

function submitHalfChance() {
    var h12 = parseInt($('#hc-hour-select').val(), 10);
    var mmInt = parseInt($('#hc-min-select').val(), 10);
    var mmStr = $('#hc-min-select').val();
    var ampm = $('#hc-ampm-select').val();

    var h24 = h12;
    if (ampm === 'PM' && h12 < 12) h24 += 12;
    if (ampm === 'AM' && h12 === 12) h24 = 0;

    var timeVal = String(h24).padStart(2, '0') + ':' + mmStr;

    // Validate that expected arrival time is not in the past for today's date
    var selectedDateStr = $('#date_search').val();
    var now = new Date();
    var todayYyyy = now.getFullYear();
    var todayMm = String(now.getMonth() + 1).padStart(2, '0');
    var todayDd = String(now.getDate()).padStart(2, '0');
    var todayStr = todayYyyy + '-' + todayMm + '-' + todayDd;

    if (!selectedDateStr || selectedDateStr === todayStr) {
        var currentH24 = now.getHours();
        var currentMin = now.getMinutes();

        var selectedTotalMin = h24 * 60 + mmInt;
        var currentTotalMin  = currentH24 * 60 + currentMin;

        if (selectedTotalMin < currentTotalMin - 2) { // 2 minute grace window
            swal('Invalid Time', 'Expected arrival time cannot be in the past. Please select the current or a future time.', 'warning');
            return;
        }
    }

    closeHCModal();
    mobMarkAttendance(timeVal);
}

</script>

<!-- UPI Payment Modal Container -->
<div id="upi-modal-container" style="display:none; position:fixed; top:0; left:0; width:100%; height:100%; z-index:1000000 !important; overflow-y:auto;">
    <div style="position:fixed; top:0; left:0; width:100%; height:100%; background:rgba(15,23,42,0.65); backdrop-filter:blur(4px);" onclick="closeHomePayModal(event)"></div>
    <div style="position:relative; max-width:480px; margin:40px auto; background:#ffffff; border-radius:24px; box-shadow:0 20px 40px rgba(0,0,0,0.2); overflow:hidden; z-index:10000; font-family:'Inter', sans-serif;">
        
        <!-- Header -->
        <div style="background:linear-gradient(135deg, #1e1b4b, #312e81); padding:24px 20px; color:#ffffff; position:relative;">
            <button onclick="closeHomePayModal()" style="position:absolute; top:16px; right:16px; background:rgba(255,255,255,0.15); border:none; color:#fff; width:32px; height:32px; border-radius:50%; display:flex; align-items:center; justify-content:center; cursor:pointer;">
                <i class="fa fa-times"></i>
            </button>
            <div style="display:flex; align-items:center; gap:12px;">
                <div style="width:42px; height:42px; border-radius:12px; background:rgba(255,255,255,0.15); display:flex; align-items:center; justify-content:center; font-size:20px; color:#60a5fa;">
                    <i class="fa fa-mobile"></i>
                </div>
                <div>
                    <h3 style="font-size:17px; font-weight:800; margin:0; color:#ffffff;">Instant UPI Payment</h3>
                    <p style="font-size:12px; color:#93c5fd; margin:2px 0 0;" id="upi-modal-payee">YMCA BCP Poovathussery</p>
                </div>
            </div>
        </div>

        <!-- Body -->
        <div style="padding:24px 20px;">
            
            <!-- Important Cashier Screenshot Notice -->
            <div style="background:#fffbebf5; border:1px solid #fef08a; border-radius:14px; padding:12px 14px; margin-bottom:18px; display:flex; align-items:center; gap:10px; box-shadow:0 2px 8px rgba(234,179,8,0.08);">
                <div style="width:34px; height:34px; border-radius:10px; background:#fef08a; color:#a16207; display:flex; align-items:center; justify-content:center; font-size:16px; flex-shrink:0;">
                    <i class="fa fa-info-circle"></i>
                </div>
                <div style="font-size:12.5px; font-weight:700; color:#854d0e; line-height:1.4;">
                    Please share the payment screenshot with the cashier after completing payment.
                </div>
            </div>

            <!-- Amount Mode Selection -->
            <label style="display:block; font-size:11px; font-weight:800; color:#64748b; text-transform:uppercase; letter-spacing:0.5px; margin-bottom:12px;">SELECT PAYMENT AMOUNT</label>
            
            <div style="display:grid; grid-template-columns:1fr 1fr; gap:12px; margin-bottom:16px;">
                <div id="mode-card-full" onclick="selectHomeUpiMode('full')" style="border:2px solid #3b82f6; background:#eff6ff; border-radius:14px; padding:14px 12px; cursor:pointer; text-align:center; transition:all 0.2s;">
                    <div style="font-size:11px; font-weight:700; color:#2563eb;">PAY FULL BALANCE</div>
                    <div style="font-size:16px; font-weight:900; color:#1e3a8a; margin-top:4px;" id="upi-mode-full-val">₹0.00</div>
                </div>
                <div id="mode-card-custom" onclick="selectHomeUpiMode('custom')" style="border:2px solid #e2e8f0; background:#f8fafc; border-radius:14px; padding:14px 12px; cursor:pointer; text-align:center; transition:all 0.2s;">
                    <div style="font-size:11px; font-weight:700; color:#64748b;">PAY CUSTOM AMOUNT</div>
                    <div style="font-size:14px; font-weight:800; color:#0f172a; margin-top:4px;">Enter Amount</div>
                </div>
            </div>

            <!-- Custom Amount Input Field -->
            <div id="custom-amount-wrap" style="display:none; margin-bottom:20px;">
                <label style="display:block; font-size:11px; font-weight:800; color:#475569; margin-bottom:6px;">TYPE CUSTOM AMOUNT (₹)</label>
                <div style="position:relative;">
                    <span style="position:absolute; left:14px; top:12px; font-size:16px; font-weight:800; color:#64748b;">₹</span>
                    <input type="number" id="upi-custom-input" placeholder="Enter amount (e.g. 500)" style="width:100%; height:46px; border-radius:12px; border:2px solid #3b82f6; padding:0 14px 0 32px; font-size:16px; font-weight:800; color:#0f172a; outline:none;" oninput="onHomeCustomAmountChange()">
                </div>
            </div>

            <!-- Mobile App Selector Buttons -->
            <div id="upi-mobile-section" style="margin-bottom:20px;">
                <label style="display:block; font-size:11px; font-weight:800; color:#64748b; text-transform:uppercase; letter-spacing:0.5px; margin-bottom:10px;">CHOOSE YOUR UPI PAY APP</label>
                <div style="display:grid; grid-template-columns:1fr 1fr; gap:10px; margin-bottom:10px;">
                    <button type="button" onclick="launchHomeUpiApp('tez')" style="display:flex; align-items:center; justify-content:center; gap:8px; height:46px; background:#ffffff; border:1.5px solid #4285f4; color:#1a73e8; border-radius:14px; font-size:13.5px; font-weight:800; font-family:'Inter',sans-serif; cursor:pointer;">
                        <i class="fa fa-google" style="font-size:16px; color:#4285f4;"></i> Google Pay
                    </button>
                    <button type="button" onclick="launchHomeUpiApp('phonepe')" style="display:flex; align-items:center; justify-content:center; gap:8px; height:46px; background:#ffffff; border:1.5px solid #5f259f; color:#5f259f; border-radius:14px; font-size:13.5px; font-weight:800; font-family:'Inter',sans-serif; cursor:pointer;">
                        <i class="fa fa-mobile" style="font-size:18px; color:#5f259f;"></i> PhonePe
                    </button>
                    <button type="button" onclick="launchHomeUpiApp('paytmmp')" style="display:flex; align-items:center; justify-content:center; gap:8px; height:46px; background:#ffffff; border:1.5px solid #00baf2; color:#002e6e; border-radius:14px; font-size:13.5px; font-weight:800; font-family:'Inter',sans-serif; cursor:pointer;">
                        <i class="fa fa-credit-card" style="font-size:15px; color:#00baf2;"></i> Paytm
                    </button>
                    <button type="button" onclick="launchHomeUpiApp('')" style="display:flex; align-items:center; justify-content:center; gap:8px; height:46px; background:#f1f5f9; border:1.5px solid #cbd5e1; color:#334155; border-radius:14px; font-size:13.5px; font-weight:800; font-family:'Inter',sans-serif; cursor:pointer;">
                        <i class="fa fa-shield" style="font-size:16px; color:#3b82f6;"></i> Any UPI App
                    </button>
                </div>
                <a id="upi-deep-link-btn" href="#" style="display:none;"></a>
            </div>

            <!-- Desktop QR Code & Copy Options -->
            <div style="background:#f8fafc; border:1px solid #e2e8f0; border-radius:16px; padding:16px; text-align:center;">
                <div style="font-size:11px; font-weight:800; color:#64748b; text-transform:uppercase; margin-bottom:10px;"><i class="fa fa-qrcode"></i> SCAN QR CODE TO PAY</div>
                
                <!-- Dynamic QR Code Image -->
                <div style="display:inline-block; background:#ffffff; padding:10px; border-radius:14px; border:1px solid #cbd5e1; box-shadow:0 2px 8px rgba(0,0,0,0.05); margin-bottom:12px;">
                    <img id="upi-qr-image" src="" alt="UPI QR Code" style="width:180px; height:180px; display:block;">
                </div>
                
                <div style="font-size:13px; font-weight:800; color:#0f172a; margin-bottom:12px;">
                    Amount to Pay: <span id="upi-qr-amount-text" style="color:#2563eb;">₹0.00</span>
                </div>

                <!-- Copy Action Buttons -->
                <div style="display:flex; gap:8px; justify-content:center; flex-wrap:wrap;">
                    <button onclick="copyHomeUpiId()" style="background:#ffffff; border:1px solid #cbd5e1; color:#1e293b; padding:8px 14px; border-radius:20px; font-size:12px; font-weight:700; cursor:pointer; display:inline-flex; align-items:center; gap:6px;">
                        <i class="fa fa-copy" style="color:#3b82f6;"></i> Copy UPI ID
                    </button>
                    <button onclick="copyHomeUpiLink()" style="background:#ffffff; border:1px solid #cbd5e1; color:#1e293b; padding:8px 14px; border-radius:20px; font-size:12px; font-weight:700; cursor:pointer; display:inline-flex; align-items:center; gap:6px;">
                        <i class="fa fa-link" style="color:#10b981;"></i> Copy Link
                    </button>
                </div>
            </div>

        </div>

    </div>
</div>

<script>
function adjustShuttleCount(inputId, delta) {
    var $el = $('#' + inputId);
    var val = parseInt($el.val()) || 0;
    val = Math.min(6, Math.max(0, val + delta));
    $el.val(val).trigger('change').trigger('input');
    if (inputId === 'home_shuttles_used') {
        updateHomeStockLabel(val);
    }
}

// Balance & UPI settings are calculated server-side in PHP - no AJAX needed
var homePendingBalance = <?php echo json_encode($php_pending_balance); ?>;
var activeHomeUpiAmount = 0;
var currentUpiSettings = {
    upi_id:       <?php echo json_encode($upi_id); ?>,
    payee_name:   <?php echo json_encode($payee_name); ?>,
    payment_note: <?php echo json_encode($payment_note); ?>,
    is_active:    <?php echo (int)$upi_active; ?>
};

$(document).ready(function(){
    loadGroups();
});


function openHomePayModal() {
    if (!currentUpiSettings) {
        currentUpiSettings = {
            upi_id: 'ymcabcp@okaxis',
            payee_name: 'YMCA BCP Poovathussery',
            payment_note: 'YMCA Member Fee Payment',
            is_active: 1
        };
    }
    $('#upi-modal-payee').text(currentUpiSettings.payee_name || 'YMCA BCP Poovathussery');
    $('#upi-mode-full-val').text('₹' + homePendingBalance.toFixed(2));
    
    if (homePendingBalance > 0) {
        selectHomeUpiMode('full');
    } else {
        selectHomeUpiMode('custom');
    }
    $('#upi-modal-container').fadeIn(200);
}

function closeHomePayModal(e) {
    if (e && e.target !== e.currentTarget) return;
    $('#upi-modal-container').fadeOut(200);
}

function selectHomeUpiMode(mode) {
    if (mode === 'full') {
        $('#mode-card-full').css({'border-color': '#3b82f6', 'background': '#eff6ff'});
        $('#mode-card-custom').css({'border-color': '#e2e8f0', 'background': '#f8fafc'});
        $('#custom-amount-wrap').hide();
        activeHomeUpiAmount = homePendingBalance;
    } else {
        $('#mode-card-custom').css({'border-color': '#3b82f6', 'background': '#eff6ff'});
        $('#mode-card-full').css({'border-color': '#e2e8f0', 'background': '#f8fafc'});
        $('#custom-amount-wrap').show();
        $('#upi-custom-input').focus();
        onHomeCustomAmountChange();
        return;
    }
    updateHomeUpiLinksAndQr();
}

function onHomeCustomAmountChange() {
    var val = parseFloat($('#upi-custom-input').val() || 0);
    activeHomeUpiAmount = val > 0 ? val : 0;
    updateHomeUpiLinksAndQr();
}

function updateHomeUpiLinksAndQr() {
    if (!currentUpiSettings) return;
    var upiId = currentUpiSettings.upi_id || 'ymcabcp@okaxis';
    var payee = encodeURIComponent(currentUpiSettings.payee_name || 'YMCA BCP');
    var note = encodeURIComponent(currentUpiSettings.payment_note || 'YMCA Fee Payment');
    var amtStr = activeHomeUpiAmount > 0 ? activeHomeUpiAmount.toFixed(2) : '0.00';

    var upiUrl = 'upi://pay?pa=' + upiId + '&pn=' + payee + '&am=' + amtStr + '&cu=INR&tn=' + note;

    $('#upi-deep-link-btn').attr('href', upiUrl);
    $('#upi-qr-amount-text').text('₹' + amtStr);

    var qrImgUrl = 'https://api.qrserver.com/v1/create-qr-code/?size=200x200&data=' + encodeURIComponent(upiUrl);
    $('#upi-qr-image').attr('src', qrImgUrl);
}

function launchHomeUpiApp(scheme) {
    if (!currentUpiSettings) return;
    var upiId = currentUpiSettings.upi_id || 'ymcabcp@okaxis';
    var payee = encodeURIComponent(currentUpiSettings.payee_name || 'YMCA BCP');
    var note = encodeURIComponent(currentUpiSettings.payment_note || 'YMCA Fee Payment');
    var amtStr = activeHomeUpiAmount > 0 ? activeHomeUpiAmount.toFixed(2) : '0.00';
    var params = 'pa=' + upiId + '&pn=' + payee + '&am=' + amtStr + '&cu=INR&tn=' + note;

    var url = 'upi://pay?' + params;
    if (scheme === 'tez') {
        url = 'tez://upi/pay?' + params;
    } else if (scheme === 'phonepe') {
        url = 'phonepe://pay?' + params;
    } else if (scheme === 'paytmmp') {
        url = 'paytmmp://pay?' + params;
    }

    window.location.href = url;
}

function copyHomeUpiId() {
    if (!currentUpiSettings || !currentUpiSettings.upi_id) return;
    navigator.clipboard.writeText(currentUpiSettings.upi_id);
    alert("UPI ID (" + currentUpiSettings.upi_id + ") copied to clipboard.");
}

function copyHomeUpiLink() {
    var upiUrl = $('#upi-deep-link-btn').attr('href');
    if (upiUrl) {
        navigator.clipboard.writeText(upiUrl);
        alert("UPI Payment link copied to clipboard.");
    }
}
</script>

</body>
</html>
