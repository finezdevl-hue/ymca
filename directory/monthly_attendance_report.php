<?php
session_start();
session_write_close();

if (empty($_SESSION['login_id'])) {
    header("Location: ../app_login_manager/logout.php");
    exit();
}
$is_admin = ($_SESSION['login_id'] == 1);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="description" content="YMCA Monthly Attendance Report">
    <title>YMCA | Monthly Attendance Report</title>

    <link href="../css/bootstrap.min.css" rel="stylesheet">
    <link href="../font-awesome/css/font-awesome.css" rel="stylesheet">
    <link href="../css/animate.css" rel="stylesheet">
    <link href="../css/style.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <link href="../css/plugins/sweetalert/sweetalert.css" rel="stylesheet">
    <script src="https://cdnjs.cloudflare.com/ajax/libs/jquery/3.6.0/jquery.min.js"></script>
    <script src="../js/plugins/sweetalert/sweetalert.min.js"></script>
    <script src="../app_js/sweetalert-finez.js"></script>
    <script src="../app_menu/menu.js"></script>

    <style>
        *, *::before, *::after { box-sizing: border-box; }
        body, #wrapper { font-family: 'Inter','Segoe UI',sans-serif !important; background: #f0f4ff !important; }

        /* ---- Top Bar ---- */
        .rep-topbar {
            background: #fff;
            border-bottom: 1px solid #e8edf5;
            padding: 0 28px; height: 62px;
            display: flex; align-items: center; justify-content: space-between;
            box-shadow: 0 1px 6px rgba(59,130,246,0.06);
            position: sticky; top: 0; z-index: 100;
        }
        .rep-topbar-left { display: flex; align-items: center; gap: 14px; }
        .rep-hamburger {
            width: 38px; height: 38px;
            background: linear-gradient(135deg,#3b82f6,#6366f1);
            border: none; border-radius: 10px; color: #fff;
            font-size: 15px; cursor: pointer;
            display: flex; align-items: center; justify-content: center;
        }
        .rep-topbar-title { font-size: 17px; font-weight: 700; color: #1e293b; }
        .rep-topbar-title span { color: #3b82f6; }
        .rep-logout {
            display: inline-flex; align-items: center; gap: 7px;
            padding: 8px 18px; background: #fff;
            border: 1.5px solid #e8edf5; border-radius: 10px;
            color: #64748b; font-size: 13.5px; font-weight: 500;
            text-decoration: none; transition: all 0.18s;
        }
        .rep-logout:hover { border-color: #3b82f6; color: #3b82f6; text-decoration: none; }

        /* ---- Content ---- */
        .rep-content { padding: 24px 28px; }

        /* ---- Filter Card ---- */
        .rep-filter-card {
            background: #fff; border-radius: 18px;
            border: 1px solid #e8edf5;
            box-shadow: 0 2px 12px rgba(0,0,0,0.05);
            padding: 22px 26px; margin-bottom: 22px;
        }
        .rep-filter-card h2 {
            font-size: 16px; font-weight: 700; color: #1e293b;
            display: flex; align-items: center; gap: 9px;
            margin: 0 0 18px;
        }
        .rep-filter-card h2 i {
            width: 32px; height: 32px; border-radius: 9px;
            background: linear-gradient(135deg,#3b82f6,#6366f1);
            display: flex; align-items: center; justify-content: center;
            color: #fff; font-size: 14px;
        }
        .rep-filter-row {
            display: flex; align-items: flex-end; gap: 16px; flex-wrap: wrap;
        }
        .rep-filter-field { display: flex; flex-direction: column; gap: 5px; }
        .rep-filter-field label {
            font-size: 12px; font-weight: 600;
            color: #64748b; text-transform: uppercase; letter-spacing: 0.05em;
        }
        .rep-filter-field select,
        .rep-filter-field input[type="month"] {
            padding: 10px 40px 10px 14px; border-radius: 10px;
            border: 1.5px solid #e2e8f0; background: #ffffff url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='18' height='18' viewBox='0 0 24 24' fill='none' stroke='%233b82f6' stroke-width='2' stroke-linecap='round' stroke-linejoin='round'%3E%3Crect x='3' y='4' width='18' height='18' rx='2' ry='2'%3E%3C/rect%3E%3Cline x1='16' y1='2' x2='16' y2='6'%3E%3C/line%3E%3Cline x1='8' y1='2' x2='8' y2='6'%3E%3C/line%3E%3Cline x1='3' y1='10' x2='21' y2='10'%3E%3C/line%3E%3C/svg%3E") no-repeat right 12px center;
            background-size: 18px 18px;
            font-size: 14px; font-weight: 700; color: #1e293b;
            font-family: 'Inter', sans-serif;
            outline: none; transition: border-color 0.2s, box-shadow 0.2s;
            min-width: 180px; position: relative;
            appearance: none; -webkit-appearance: none;
        }
        .rep-filter-field select:focus,
        .rep-filter-field input[type="month"]:focus {
            border-color: #3b82f6;
            box-shadow: 0 0 0 3px rgba(59,130,246,0.1);
        }
        .rep-filter-field input[type="month"]::-webkit-calendar-picker-indicator {
            position: absolute; top: 0; left: 0; right: 0; bottom: 0;
            width: 100%; height: 100%; opacity: 0; cursor: pointer;
        }
        .rep-btn {
            display: inline-flex; align-items: center; gap: 7px;
            padding: 10px 20px; border-radius: 10px;
            font-size: 13.5px; font-weight: 600; cursor: pointer;
            transition: all 0.18s; border: none;
            font-family: 'Inter', sans-serif;
        }
        .rep-btn-primary {
            background: linear-gradient(135deg,#3b82f6,#6366f1);
            color: #fff; box-shadow: 0 3px 12px rgba(59,130,246,0.3);
        }
        .rep-btn-primary:hover { opacity: 0.9; transform: translateY(-1px); }
        .rep-btn-outline {
            background: #fff; color: #475569;
            border: 1.5px solid #e2e8f0;
        }
        .rep-btn-outline:hover { border-color: #94a3b8; background: #f8faff; }
        .rep-btn-green {
            background: linear-gradient(135deg,#10b981,#059669);
            color: #fff; box-shadow: 0 3px 12px rgba(16,185,129,0.3);
        }
        .rep-btn-green:hover { opacity: 0.9; transform: translateY(-1px); }
        .rep-btn-red {
            background: linear-gradient(135deg,#ef4444,#dc2626) !important;
            color: #fff !important; box-shadow: 0 3px 12px rgba(239,68,68,0.3) !important;
        }
        .rep-btn-red:hover { opacity: 0.9 !important; transform: translateY(-1px) !important; }
        .rep-btn-orange {
            background: linear-gradient(135deg,#f97316,#ea580c) !important;
            color: #fff !important; box-shadow: 0 3px 12px rgba(249,115,22,0.3) !important;
        }
        .rep-btn-orange:hover { opacity: 0.9 !important; transform: translateY(-1px) !important; }

        /* ---- Stats Strip ---- */
        .rep-stats-strip {
            display: flex; gap: 14px; flex-wrap: wrap; margin-bottom: 22px;
        }
        .rep-stat-chip {
            display: flex; align-items: center; gap: 9px;
            padding: 10px 16px; background: #fff;
            border-radius: 12px; border: 1px solid #e8edf5;
            box-shadow: 0 1px 6px rgba(0,0,0,0.04);
            font-size: 13px; font-weight: 600; color: #475569;
        }
        .rep-stat-chip .chip-dot {
            width: 10px; height: 10px; border-radius: 3px;
        }
        .rep-stat-chip .chip-val { font-weight: 800; color: #1e293b; font-size: 15px; }

        /* ---- Report Table Card ---- */
        .rep-table-card {
            background: #fff; border-radius: 18px;
            border: 1px solid #e8edf5;
            box-shadow: 0 2px 12px rgba(0,0,0,0.05);
            overflow: hidden;
            width: 100%;
            max-width: 100%;
        }
        .rep-table-header {
            padding: 18px 24px 14px;
            border-bottom: 1px solid #f1f5f9;
            display: flex; align-items: center; justify-content: space-between; flex-wrap: wrap; gap: 10px;
        }
        .rep-table-title {
            font-size: 15px; font-weight: 700; color: #1e293b;
            display: flex; align-items: center; gap: 9px; margin: 0;
        }
        .rep-table-title i {
            width: 30px; height: 30px; border-radius: 8px;
            background: linear-gradient(135deg,#3b82f6,#6366f1);
            display: flex; align-items: center; justify-content: center;
            color: #fff; font-size: 13px;
        }
        .rep-month-badge {
            background: linear-gradient(135deg,#3b82f6,#6366f1);
            color: #fff; padding: 5px 14px; border-radius: 20px;
            font-size: 12.5px; font-weight: 600;
        }
        .rep-price-badge {
            background: linear-gradient(135deg,#10b981,#059669);
            color: #fff; padding: 5px 14px; border-radius: 20px;
            font-size: 12.5px; font-weight: 600;
        }

        /* ---- Attendance Day Grid Table ---- */
        .rep-table-wrap {
            overflow-x: auto;
            border-top: 1px solid #e8edf5;
            width: 100%;
            max-width: 100%;
            display: block;
        }
        /* Custom scrollbar for horizontal scroll */
        .rep-table-wrap::-webkit-scrollbar {
            height: 8px;
        }
        .rep-table-wrap::-webkit-scrollbar-track {
            background: #f1f5f9;
        }
        .rep-table-wrap::-webkit-scrollbar-thumb {
            background: #cbd5e1;
            border-radius: 4px;
        }
        .rep-table-wrap::-webkit-scrollbar-thumb:hover {
            background: #94a3b8;
        }
        #tbl_attendance_grid {
            width: max-content;
            min-width: 100%;
            border-collapse: separate;
            border-spacing: 0;
            font-size: 13.5px;
            font-family: 'Inter', sans-serif;
        }
        #tbl_attendance_grid thead tr {
            background: #f8fafc;
        }
        #tbl_attendance_grid thead th {
            padding: 14px 10px;
            font-size: 11px;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 0.06em;
            color: #475569;
            text-align: center;
            border-bottom: 1.5px solid #e8edf5;
            border-right: 1px solid #e8edf5;
            white-space: nowrap;
        }
        #tbl_attendance_grid thead th:last-child {
            border-right: none;
        }
        #tbl_attendance_grid thead th.col-name {
            text-align: left;
            min-width: 180px;
            padding-left: 24px;
            position: sticky;
            left: 0;
            background: #f8fafc;
            z-index: 2;
            border-right: 1px solid #e8edf5;
        }
        #tbl_attendance_grid thead th.col-total {
            min-width: 80px;
            border-right: 1px solid #e8edf5;
        }
        #tbl_attendance_grid thead th.col-rate {
            min-width: 80px;
            border-right: none;
        }
        /* Day number header */
        #tbl_attendance_grid thead th.col-day {
            min-width: 42px;
        }
        #tbl_attendance_grid thead th.col-day.today {
            color: #3b82f6;
            background: #eff6ff;
        }

        /* Body rows */
        #tbl_attendance_grid tbody tr {
            transition: background 0.15s;
        }
        #tbl_attendance_grid tbody tr:hover {
            background: #f8faff;
        }

        #tbl_attendance_grid tbody td {
            padding: 13px 10px;
            text-align: center;
            color: #334155;
            border-bottom: 1px solid #e8edf5;
            border-right: 1px solid #e8edf5;
        }
        #tbl_attendance_grid tbody td:last-child {
            border-right: none;
        }
        #tbl_attendance_grid tbody td.cell-name {
            text-align: left;
            padding-left: 24px;
            font-weight: 600;
            color: #0f172a;
            position: sticky;
            left: 0;
            background: #fff;
            z-index: 1;
            border-right: 1px solid #e8edf5;
        }
        #tbl_attendance_grid tbody tr:hover td.cell-name {
            background: #f8faff;
        }

        /* P / A / — cells */
        .cell-present {
            color: #10b981;
            font-weight: 700;
            font-size: 13px;
        }
        .cell-absent {
            color: #ef4444;
            font-weight: 700;
            font-size: 13px;
        }
        .cell-nosession {
            color: #94a3b8;
            font-size: 13px;
        }

        /* Rate badge */
        .rate-badge {
            display: inline-block;
            font-weight: 700;
            font-size: 13px;
            color: #0f172a;
        }
        .rate-high   { background: #dcfce7; color: #15803d; }
        .rate-medium { background: #fef9c3; color: #b45309; }
        .rate-low    { background: #fee2e2; color: #b91c1c; }
        .rate-zero   { background: #f1f5f9; color: #94a3b8; }

        /* Group divider row */
        .group-header-row td {
            background: linear-gradient(135deg, #eff6ff, #f5f3ff);
            color: #3b82f6; font-size: 11px; font-weight: 700;
            text-transform: uppercase; letter-spacing: 0.07em;
            padding: 8px 22px !important; text-align: left !important;
        }

        /* Empty / loading state */
        .rep-empty {
            text-align: center; padding: 60px 20px;
            color: #94a3b8;
        }
        .rep-empty i { font-size: 44px; margin-bottom: 12px; display: block; color: #dbeafe; }
        .rep-empty p { font-size: 14px; margin: 0; }

        /* ---- Print ---- */
        @media print {
            @page {
                size: landscape;
                margin: 0.5cm;
            }
            body {
                background: #ffffff !important;
                color: #000000 !important;
            }
            .rep-topbar, .rep-filter-card, .nav-topbar, .navbar-default,
            .sidebar-collapse, .navbar-static-side, .rep-btn-outline,
            .rep-btn-primary, .rep-btn-green, .btn-print-hide,
            .rep-stats-strip { 
                display: none !important; 
            }
            #page-wrapper {
                margin: 0 !important;
                padding: 0 !important;
                background: #ffffff !important;
                min-width: 0 !important;
            }
            .rep-content {
                padding: 0 !important;
            }
            .rep-table-card {
                border: none !important;
                box-shadow: none !important;
                border-radius: 0 !important;
                width: 100% !important;
                max-width: 100% !important;
            }
            .rep-table-header {
                border-bottom: 2px solid #333 !important;
                padding: 10px 0 !important;
            }
            .print-only {
                display: block !important;
            }
            .rep-table-wrap {
                overflow: visible !important;
                width: 100% !important;
                max-width: 100% !important;
            }
            #tbl_attendance_grid {
                width: 100% !important;
                min-width: 100% !important;
                table-layout: fixed !important;
                font-size: 8px !important;
            }
            #tbl_attendance_grid thead th,
            #tbl_attendance_grid tbody td {
                padding: 4px 2px !important;
                border: 1.5px solid #bbb !important;
                font-size: 8px !important;
                word-wrap: break-word !important;
            }
            #tbl_attendance_grid thead th.col-name,
            #tbl_attendance_grid tbody td.cell-name {
                position: static !important;
                background: #ffffff !important;
                font-size: 8.5px !important;
                font-weight: bold !important;
                padding-left: 4px !important;
                width: 150px !important;
                min-width: 150px !important;
                max-width: 150px !important;
                overflow: hidden !important;
                text-overflow: ellipsis !important;
                white-space: nowrap !important;
            }
            #tbl_attendance_grid thead th.col-day {
                width: 25px !important;
                min-width: 25px !important;
                max-width: 25px !important;
            }
            #tbl_attendance_grid thead th.col-total {
                width: 45px !important;
                min-width: 45px !important;
                max-width: 45px !important;
            }
            #tbl_attendance_grid thead th.col-rate {
                width: 50px !important;
                min-width: 50px !important;
                max-width: 50px !important;
            }
            .cell-present { color: #000000 !important; font-weight: bold !important; }
            .cell-absent { color: #000000 !important; font-weight: bold !important; }
            .cell-nosession { color: #777777 !important; }
        }
        .print-only { display: none; }
    </style>
</head>

<body>
<div id="wrapper">

    <!-- Sidebar -->
    <nav class="navbar-default navbar-static-side" role="navigation">
        <div class="dropdown profile-element">
            <center>
                <span><img alt="image" class="img-circle" src="../img/customer.png" style="padding-top:20px;"/></span>
                <span class="clear"><span class="block m-t-xs"><strong class="font-bold"><?php echo htmlspecialchars($_SESSION['name']); ?></strong></span></span>
            </center>
        </div>
        <div class="sidebar-collapse" id="divMenuContainer"></div>
    </nav>

    <!-- Main -->
    <div id="page-wrapper" style="background:#f0f4ff; padding:0; min-height:100vh;">

        <!-- Top Navbar -->
        <div class="rep-topbar">
            <div class="rep-topbar-left">
                <a class="navbar-minimalize minimalize-styl-2 rep-hamburger" href="#"><i class="fa fa-bars"></i></a>
                <span class="rep-topbar-title">YMCA <span>Admin</span></span>
            </div>
            <a href="../app_login_manager/logout.php" class="rep-logout">
                <i class="fa fa-sign-out"></i> Log out
            </a>
        </div>

        <!-- Content -->
        <div class="rep-content">

            <!-- Print header (hidden on screen) -->
            <div class="print-only" style="text-align:center; margin-bottom:20px;">
                <h2 style="margin:0; font-size:20px;">YMCA Badminton Club Poovathussery — Monthly Attendance Report</h2>
                <p id="print_meta" style="font-size:14px; margin:6px 0 0;"></p>
            </div>

            <!-- Filters -->
            <div class="rep-filter-card btn-print-hide">
                <h2><i class="fa fa-filter"></i> Generate Attendance Report</h2>
                <div class="rep-filter-row">
                    <div class="rep-filter-field">
                        <label>Month</label>
                        <input type="month" id="filter_month">
                    </div>
                    <div class="rep-filter-field" <?php 
                        include_once '../app_common/auth_helper.php';
                        $lid = (int)$_SESSION['login_id'];
                        if (!isSuperAdmin($lid) && !isGroupAdmin($lid) && !isAttendanceMaster($lid)) echo 'style="display:none;"'; 
                    ?>>
                        <label>Group</label>
                        <select id="filter_group">
                            <option value="0">All Groups</option>
                        </select>
                    </div>
                    <div style="display:flex; gap:10px; align-items:flex-end;">
                        <button class="rep-btn rep-btn-primary" onclick="generateReport()">
                            <i class="fa fa-refresh"></i> Generate
                        </button>
                        <button class="rep-btn rep-btn-green" onclick="window.print()">
                            <i class="fa fa-print"></i> Print
                        </button>
                        <?php if (isset($_SESSION['login_id']) && $_SESSION['login_id'] == 1): ?>
                        <button class="rep-btn" id="btn_lock_status" style="display:none;" onclick="toggleLockMonth()">
                            <i class="fa fa-lock" id="icon_lock"></i> <span id="lbl_lock">Lock Month</span>
                        </button>
                        <?php endif; ?>
                    </div>
                    </div>
                </div>
            </div>

            <!-- Coke Readings Panel -->
            <div class="rep-filter-card btn-print-hide shuttle-field" id="coke_readings_panel" style="display:none;">
                <h2><i class="fa fa-glass"></i> Coke Readings</h2>
                <div class="rep-filter-row" style="align-items: center;">
                    <div class="rep-filter-field">
                        <label>Coke no Starts</label>
                        <input type="number" id="start_shuttle_no" class="form-control text-center" style="width:110px; height: 38px;" placeholder="Starts">
                    </div>
                    <div class="rep-filter-field" style="margin-left: 10px;">
                        <label>Coke no ends</label>
                        <input type="number" id="end_shuttle_no" class="form-control text-center" style="width:110px; height: 38px;" placeholder="Ends">
                    </div>
                    <div class="rep-filter-field" style="justify-content: flex-end; height: 58px; margin-left: 15px;">
                        <span style="font-size: 13px; font-weight: 700; color: #4f46e5; margin-bottom: 10px;">
                            Cokes Used: <span id="total_shuttles_used" style="font-size:15px; background:rgba(79,70,229,0.1); padding:4px 10px; border-radius:6px;">0</span>
                        </span>
                    </div>
                    <?php if ($is_admin): ?>
                    <div class="rep-filter-field" style="margin-left: 15px;">
                        <label>Avg coke Price</label>
                        <input type="number" id="shuttle_price_input" class="form-control text-center" style="width:140px; height: 38px;" placeholder="0.00" step="0.01" min="0">
                    </div>
                    <?php endif; ?>
                    <input type="hidden" id="month_avg_shuttle_price" value="">
                    <input type="hidden" id="calculated_shuttle_price" value="">
                    <div class="rep-filter-field" style="justify-content: flex-end; height: 58px; margin-left: 15px;">
                        <span style="font-size: 13px; font-weight: 700; color: #059669; margin-bottom: 10px;">
                            Coke Price: <span id="calculated_shuttle_price_badge" class="rep-price-badge">Rs. 0.00</span>
                        </span>
                    </div>
                    <?php if ($is_admin): ?>
                    <div class="rep-filter-field" style="justify-content: flex-end; height: 58px; margin-left: auto;">
                        <button type="button" class="rep-btn rep-btn-primary" id="btn_save_readings" style="height: 38px; margin-bottom: 10px;">
                            <i class="fa fa-save"></i> Save Readings
                        </button>
                    </div>
                    <?php endif; ?>
                </div>
            </div>

            <!-- Stats Strip -->
            <div class="rep-stats-strip" id="rep-stats-strip" style="display:none;">
                <div class="rep-stat-chip">
                    <span class="chip-dot" style="background:#3b82f6;"></span>
                    Members: <span class="chip-val" id="stat-members">0</span>
                </div>
                <div class="rep-stat-chip">
                    <span class="chip-dot" style="background:#10b981;"></span>
                    Avg Attendance: <span class="chip-val" id="stat-avg">0%</span>
                </div>
                <div class="rep-stat-chip">
                    <span class="chip-dot" style="background:#8b5cf6;"></span>
                    Session Days: <span class="chip-val" id="stat-sessions">0</span>
                </div>
                <div class="rep-stat-chip">
                    <span class="chip-dot" style="background:#f59e0b;"></span>
                    <span id="stat-full-label"><?php echo $is_admin ? '100% Attendance:' : 'Days Present:'; ?></span> <span class="chip-val" id="stat-full">0</span>
                </div>
            </div>

            <!-- Report Table -->
            <div class="rep-table-card">
                <div class="rep-table-header">
                    <h3 class="rep-table-title" style="display:flex; align-items:center;">
                        <i class="fa fa-calendar-check-o"></i>
                        Day-by-Day Attendance
                        <span id="table_shuttle_badge" style="display:none; font-size: 13px; font-weight: 700; color: #4f46e5; background: rgba(79,70,229,0.1); padding: 4px 10px; border-radius: 6px; margin-left: 15px;">Shuttles Used: 0</span>
                        <span id="table_avg_shuttle_badge" style="display:none; font-size: 13px; font-weight: 700; color: #059669; background: rgba(16,185,129,0.1); padding: 4px 10px; border-radius: 6px; margin-left: 10px;">Avg Shuttle Price: Rs. 0.00</span>
                        <span id="table_total_price_badge" style="display:none; font-size: 13px; font-weight: 700; color: #ea580c; background: rgba(249,115,22,0.12); padding: 4px 10px; border-radius: 6px; margin-left: 10px;">Shuttle Price: Rs. 0.00</span>
                    </h3>
                    <div style="display:flex; align-items:center; gap:10px;">
                        <span class="rep-month-badge" id="report_month_label" style="display:none;">Month</span>
                        <!-- Legend -->
                        <span style="display:flex; align-items:center; gap:12px; font-size:12px; color:#64748b;">
                            <span><strong style="color:#10b981;">P</strong> Present</span>
                            <span><strong style="color:#ef4444;">A</strong> Absent</span>
                            <span><strong style="color:#cbd5e1;">—</strong> No Session</span>
                        </span>
                    </div>
                </div>
                <div class="rep-table-wrap">
                    <table id="tbl_attendance_grid">
                        <thead id="grid-thead">
                            <tr>
                                <th class="col-name">Member Name</th>
                                <!-- Day columns injected dynamically -->
                                <th class="col-total">Total</th>
                                <th class="col-rate">Rate</th>
                            </tr>
                        </thead>
                        <tbody id="grid-tbody">
                            <tr>
                                <td colspan="35" class="rep-empty">
                                    <i class="fa fa-calendar-o"></i>
                                    <p>Select a month and group, then click <strong>Generate</strong>.</p>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>

        </div><!-- end .rep-content -->
    </div><!-- end page-wrapper -->
</div><!-- end wrapper -->

<!-- Scripts -->
<script src="../js/jquery-3.1.1.min.js"></script>
<script src="../js/bootstrap.min.js"></script>
<script src="../js/plugins/metisMenu/jquery.metisMenu.js"></script>
<script src="../js/plugins/slimscroll/jquery.slimscroll.min.js"></script>
<script src="../js/inspinia.js"></script>

<script>
$(document).ready(function() {
    loadMenu();

    // Set month from URL parameter if present, otherwise default to current month
    var urlParams = new URLSearchParams(window.location.search);
    var paramMonth = urlParams.get('month');
    if (paramMonth) {
        $('#filter_month').val(paramMonth);
    } else {
        var now = new Date();
        var monthStr = now.getFullYear() + '-' + ('0' + (now.getMonth() + 1)).slice(-2);
        $('#filter_month').val(monthStr);
    }

    checkMonthLockStatus();
    $('#filter_month').on('change', checkMonthLockStatus);

    // Load groups
    $.ajax({
        type: 'POST', url: 'api/attendance.php',
        data: { action: 'load_groups' },
        success: function(res) {
            var obj = JSON.parse(res);
            var groups = (obj && obj[0]) ? obj[0] : [];
            var htm = groups.length > 1 ? "<option value='0'>All Groups</option>" : "";
            for (var i = 0; i < groups.length; i++) {
                htm += "<option value='" + groups[i].id + "'>" + groups[i].name + "</option>";
            }
            $('#filter_group').html(htm);
            if (groups.length <= 1) {
                $('#filter_group').closest('.col-md-3, .form-group').hide();
                if (groups.length === 1) {
                    $('#filter_group').val(groups[0].id);
                }
            } else {
                $('#filter_group').closest('.col-md-3, .form-group').show();
            }
            generateReport(); // auto-generate on load
        }
    });
});

function formatCokePrice(value) {
    var num = parseFloat(value);
    if (isNaN(num)) {
        return 'Rs. 0.00';
    }
    return 'Rs. ' + num.toFixed(2);
}

function getUsedCokes() {
    var used = parseInt($('#total_shuttles_used').text(), 10);
    return isNaN(used) ? 0 : used;
}

function updateCokePriceSummary() {
    var used = getUsedCokes();
    var avgPrice = parseFloat($('#shuttle_price_input').val());
    if (isNaN(avgPrice)) {
        avgPrice = parseFloat($('#month_avg_shuttle_price').val());
    }

    var totalPrice = (!isNaN(avgPrice) && used > 0) ? avgPrice * used : 0;
    $('#calculated_shuttle_price').val(totalPrice > 0 ? totalPrice.toFixed(2) : '');
    $('#calculated_shuttle_price_badge').text(formatCokePrice(totalPrice));

    if (!isNaN(avgPrice)) {
        $('#table_avg_shuttle_badge').text('Avg Coke Price: ' + formatCokePrice(avgPrice)).show();
    } else {
        $('#table_avg_shuttle_badge').hide();
    }

    if (used > 0) {
        $('#table_total_price_badge').text('Coke Price: ' + formatCokePrice(totalPrice)).show();
    } else {
        $('#table_total_price_badge').hide();
    }

    return totalPrice;
}

function generateReport() {
    var month    = $('#filter_month').val();
    var group_id = $('#filter_group').val();
    if (!month) { alert('Please select a month.'); return; }

    var dateObj   = new Date(month + '-01');
    var monthName = dateObj.toLocaleString('default', { month: 'long', year: 'numeric' });
    var todayDay  = new Date().getDate();
    var todayMonth = new Date().getFullYear() + '-' + ('0' + (new Date().getMonth()+1)).slice(-2);
    var isCurrentMonth = (month === todayMonth);

    $('#report_month_label').text(monthName).show();
    $('#print_meta').text('Month: ' + monthName + '  |  Group: ' + ($('#filter_group option:selected').text()));

    // Show loading
    $('#grid-tbody').html('<tr><td colspan="35" class="rep-empty"><i class="fa fa-spinner fa-spin" style="color:#3b82f6; font-size:28px;"></i><p>Loading report…</p></td></tr>');
    $('#rep-stats-strip').hide();

    $.ajax({
        type: 'POST', url: 'api/monthly_attendance_report.php',
        data: { action: 'load_report', month: month, group_id: group_id },
        success: function(res) {
            var data    = JSON.parse(res);
            var members = data.members;
            var dayCols = data.day_cols;   // [1..31]
            var avgShuttlePrice = data.month_avg_shuttle_price !== null && data.month_avg_shuttle_price !== undefined
                ? parseFloat(data.month_avg_shuttle_price)
                : null;
            var savedShuttlePrice = data.group_shuttle_price !== null && data.group_shuttle_price !== undefined
                ? parseFloat(data.group_shuttle_price)
                : null;
            var savedAvgShuttlePrice = data.group_avg_shuttle_price !== null && data.group_avg_shuttle_price !== undefined
                ? parseFloat(data.group_avg_shuttle_price)
                : null;

            $('#month_avg_shuttle_price').val(avgShuttlePrice !== null && !isNaN(avgShuttlePrice) ? avgShuttlePrice : '');
            if ($('#shuttle_price_input').length) {
                var priceToShow = savedAvgShuttlePrice !== null && !isNaN(savedAvgShuttlePrice)
                    ? savedAvgShuttlePrice
                    : avgShuttlePrice;
                $('#shuttle_price_input').val(priceToShow !== null && !isNaN(priceToShow) ? priceToShow : '');
            }
            var initialPrintMeta = 'Month: ' + monthName + '  |  Group: ' + ($('#filter_group option:selected').text());
            $('#print_meta').text(initialPrintMeta);

            if (!members || members.length === 0) {
                $('#grid-thead tr').html('<th class="col-name">Member Name</th><th class="col-total">Total</th><th class="col-rate">Rate</th>');
                $('#grid-tbody').html('<tr><td colspan="35" class="rep-empty"><i class="fa fa-user-times"></i><p>No attendance records found for this period.</p></td></tr>');
                $('.shuttle-field').hide();
                return;
            }

            // Populate monthly/group shuttle readings
            $('#start_shuttle_no').val(data.group_start_shuttle !== null ? data.group_start_shuttle : '');
            $('#end_shuttle_no').val(data.group_end_shuttle !== null ? data.group_end_shuttle : '');
            calculateGroupShuttles();
            $('.shuttle-field').show();
            updateCokePriceSummary();

            // Build header row
            var theadHtml = '<tr><th class="col-name">Member Name</th>';
            for (var d = 0; d < dayCols.length; d++) {
                var day = dayCols[d];
                var todayCls = (isCurrentMonth && day === todayDay) ? ' today' : '';
                theadHtml += '<th class="col-day' + todayCls + '">' + (day < 10 ? '0'+day : day) + '</th>';
            }
            theadHtml += '<th class="col-total">Total</th>';
            theadHtml += '<th class="col-rate">Rate</th></tr>';
            $('#grid-thead').html(theadHtml);

            // Stats
            var totalMembers  = members.length;
            var totalPct      = 0;
            var fullAtt       = 0;
            var maxSessions   = 0;

            // Group by group_name
            var groups = {};
            for (var i = 0; i < members.length; i++) {
                var gn = members[i].group_name || 'General';
                if (!groups[gn]) groups[gn] = [];
                groups[gn].push(members[i]);
                totalPct += members[i].percentage;
                if (members[i].percentage === 100) fullAtt++;
                if (members[i].sessions > maxSessions) maxSessions = members[i].sessions;
            }

            // Build body
            var colCount = dayCols.length + 3;
            var tbodyHtml = '';

            for (var grpName in groups) {
                // Group header row
                tbodyHtml += '<tr class="group-header-row"><td colspan="' + colCount + '"><i class="fa fa-users" style="margin-right:6px;"></i>' + grpName + '</td></tr>';

                var grpMembers = groups[grpName];
                for (var j = 0; j < grpMembers.length; j++) {
                    var m = grpMembers[j];
                    var fullName = [m.first_name, m.middle_name, m.last_name]
                        .filter(function(p){ return p && p.trim(); }).join(' ');

                    tbodyHtml += '<tr>';
                    tbodyHtml += '<td class="cell-name">' + fullName + '</td>';

                    for (var d = 0; d < dayCols.length; d++) {
                        var day  = dayCols[d];
                        var status = m.days[day]; // 'P', 'A', or null
                        if (status === 'P') {
                            tbodyHtml += '<td><span class="cell-present">P</span></td>';
                        } else if (status === 'A') {
                            tbodyHtml += '<td><span class="cell-absent">A</span></td>';
                        } else {
                            tbodyHtml += '<td><span class="cell-nosession">—</span></td>';
                        }
                    }

                    // Total & Rate badge
                    tbodyHtml += '<td class="cell-total"><strong>' + m.present + '</strong></td>';
                    var pct = m.percentage;
                    var rateClass = pct >= 75 ? 'rate-high' : (pct >= 50 ? 'rate-medium' : (pct > 0 ? 'rate-low' : 'rate-zero'));
                    tbodyHtml += '<td><span class="rate-badge ' + rateClass + '">' + pct + '%</span></td>';
                    
                    tbodyHtml += '</tr>';
                }
            }

            $('#grid-tbody').html(tbodyHtml);

            // Update stats strip
            var avgPct = totalMembers > 0 ? Math.round(totalPct / totalMembers) : 0;
            $('#stat-members').text(totalMembers);
            $('#stat-avg').text(avgPct + '%');
            $('#stat-sessions').text(maxSessions);

            if (!<?php echo $is_admin ? 'true' : 'false'; ?> || totalMembers === 1) {
                $('#stat-full-label').text('Days Present:');
                $('#stat-full').text(totalMembers === 1 && members.length > 0 ? members[0].present : 0);
            } else {
                $('#stat-full-label').text('100% Attendance:');
                $('#stat-full').text(fullAtt);
            }
            $('#rep-stats-strip').show();
        },
        error: function(xhr, status, error) {
            console.error('Report load failed:', error);
            $('#grid-tbody').html('<tr><td colspan="35" class="rep-empty"><i class="fa fa-exclamation-triangle" style="color:#ef4444;"></i><p>Failed to load report. Please try again.</p></td></tr>');
        }
    });
}

function checkMonthLockStatus() {
    var month = $('#filter_month').val();
    if (!month) return;
    $.ajax({
        type: 'POST',
        url: 'api/monthly_attendance_report.php',
        data: { action: 'check_fixed_status', month: month },
        success: function(res) {
            var data = JSON.parse(res);
            var btn = $('#btn_lock_status');
            var icon = $('#icon_lock');
            var lbl = $('#lbl_lock');
            
            btn.show();
            
            if (data.is_fixed) {
                btn.removeClass('rep-btn-red rep-btn-orange').addClass('rep-btn-green').prop('disabled', true).css('cursor', 'default');
                icon.removeClass('fa-lock fa-unlock').addClass('fa-check-circle');
                lbl.text('Attendance Fixed');
                btn.data('status', 'fixed');
                $('#start_shuttle_no, #end_shuttle_no').prop('disabled', true);
            } else {
                btn.removeClass('rep-btn-orange rep-btn-green').addClass('rep-btn-red').prop('disabled', false).css('cursor', 'pointer');
                icon.removeClass('fa-check-circle fa-unlock').addClass('fa-lock');
                lbl.text('Fix Attendance');
                btn.data('status', 'not_fixed');
                if (<?php echo $is_admin ? 'true' : 'false'; ?>) {
                    $('#start_shuttle_no, #end_shuttle_no').prop('disabled', false);
                }
            }
        }
    });
}

function toggleLockMonth() {
    var month = $('#filter_month').val();
    if (!month) return;
    var status = $('#btn_lock_status').data('status');
    if (status === 'fixed') {
        swal("Attendance Fixed", "Attendance for this month is already fixed and cannot be unfixed or changed.", "info");
        return;
    }

    var startVal = $('#start_shuttle_no').val();
    var endVal = $('#end_shuttle_no').val();
    if (!startVal || !endVal || $.trim(startVal) === '' || $.trim(endVal) === '') {
        swal("Coke Readings Required", "Attendance can only be fixed after adding the Coke Readings. Please enter and save the Coke Readings first.", "warning");
        return;
    }
    
    var dateObj = new Date(month + '-01');
    var monthName = dateObj.toLocaleString('default', { month: 'long', year: 'numeric' });
    var confirmText = "Do you want to fix attendance for " + monthName + "? Once fixed, attendance for this month cannot be unfixed or changed.";
    
    swal({
        title: "Fix Attendance?",
        text: confirmText,
        type: "warning",
        showCancelButton: true,
        confirmButtonColor: "#10b981",
        confirmButtonText: "Yes, Fix Attendance!",
        cancelButtonText: "Cancel",
        closeOnConfirm: false,
        closeOnCancel: true
    },
    function (isConfirm) {
        if (isConfirm) {
            $.ajax({
                type: 'POST',
                url: 'api/monthly_attendance_report.php',
                data: { action: 'toggle_fixed_status', month: month },
                success: function(res) {
                    var data = typeof res === 'object' ? res : JSON.parse(res);
                    if (data.success) {
                        swal("Fixed!", data.message, "success");
                        checkMonthLockStatus();
                    } else {
                        swal("Error", data.error || "Failed to update status", "error");
                    }
                },
                error: function(xhr, status, error) {
                    var errmsg = "Request failed: " + error;
                    try {
                        var obj = JSON.parse(xhr.responseText);
                        if (obj.error) errmsg = obj.error;
                    } catch(e) {}
                    swal("Error", errmsg, "error");
                }
            });
        }
    });
}

function calculateGroupShuttles() {
    var startVal = $('#start_shuttle_no').val();
    var endVal = $('#end_shuttle_no').val();
    
    var start = parseInt(startVal, 10);
    var end = parseInt(endVal, 10);
    var used = 0;
    if (!isNaN(start) && !isNaN(end)) {
        used = (end + 1) - start;
        $('#table_shuttle_badge').text('Cokes Used: ' + used).show();
    } else {
        $('#table_shuttle_badge').hide();
    }
    $('#total_shuttles_used').text(used);
    updateCokePriceSummary();

    // Update print meta
    var monthName = $('#report_month_label').text();
    var groupName = $('#filter_group option:selected').text();
    var printMetaText = 'Month: ' + monthName + '  |  Group: ' + groupName;
    if (used > 0) {
        printMetaText += '  |  Total Cokes Used: ' + used;
    }
    var avgPrice = parseFloat($('#shuttle_price_input').val());
    if (isNaN(avgPrice)) {
        avgPrice = parseFloat($('#month_avg_shuttle_price').val());
    }
    if (!isNaN(avgPrice)) {
        printMetaText += '  |  Avg Coke Price: ' + formatCokePrice(avgPrice);
        printMetaText += '  |  Coke Price: ' + formatCokePrice(avgPrice * used);
    }
    $('#print_meta').text(printMetaText);
    return used;
}

// Compute coke readings when changed in UI
$(document).on('change input', '#start_shuttle_no, #end_shuttle_no', function() {
    calculateGroupShuttles();
});

$(document).on('change input', '#shuttle_price_input', function() {
    updateCokePriceSummary();
    calculateGroupShuttles();
});

// Explicit save button click handler
$(document).on('click', '#btn_save_readings', function() {
    saveCokeReadings();
});

function saveCokeReadings() {
    var group_id = $('#filter_group').val();
    var month = $('#filter_month').val();
    if (!group_id || group_id == '0') {
        swal("Warning", "Please select a specific group before saving readings.", "warning");
        return;
    }
    var startVal = $('#start_shuttle_no').val();
    var endVal = $('#end_shuttle_no').val();
    var avgPriceVal = $('#shuttle_price_input').length ? $('#shuttle_price_input').val() : $('#month_avg_shuttle_price').val();

    $.ajax({
        type: 'POST',
        url: 'api/monthly_attendance_report.php',
        data: {
            action: 'save_shuttle_readings',
            group_id: group_id,
            month: month,
            start_shuttle: startVal,
            end_shuttle: endVal,
            avg_shuttle_price: avgPriceVal
        },
        success: function(res) {
            swal("Success", "Coke readings saved successfully.", "success");
        },
        error: function(xhr, status, error) {
            swal("Error", "Failed to save coke readings: " + error, "error");
        }
    });
}

$(document).on('change', '#filter_group, #filter_month', function() {
    if ($('#shuttle_price_input').length) {
        $('#shuttle_price_input').val('');
    }
});
</script>
</body>
</html>
