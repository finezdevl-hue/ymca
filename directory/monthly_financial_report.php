<?php
session_start();
session_write_close();

if (empty($_SESSION['login_id'])) {
    header("Location: ../app_login_manager/logout.php");
    exit();
}
include_once '../app_common/auth_helper.php';
$lid = (int)$_SESSION['login_id'];
$is_admin = isSuperAdmin($lid) || isGroupAdmin($lid) || isAttendanceMaster($lid) || isExecutiveMember($lid);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="description" content="YMCA Monthly Financial Report">
    <title>YMCA | Monthly Financial Report</title>

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
            text-decoration: none; transition: all .18s;
        }
        .rep-logout:hover { border-color: #cbd5e1; color: #1e293b; }

        /* ---- Content Area ---- */
        .rep-content { padding: 28px; max-width: 1380px; margin: 0 auto; }

        /* ---- Filter Card ---- */
        .rep-filter-card {
            background: #fff; border-radius: 18px;
            padding: 22px 28px; margin-bottom: 24px;
            border: 1px solid #e8edf5;
            box-shadow: 0 2px 12px rgba(0,0,0,0.05);
        }
        .rep-filter-card h2 {
            font-size: 17px; font-weight: 700; color: #1e293b;
            margin: 0 0 16px 0; display: flex; align-items: center; gap: 8px;
        }
        .rep-filter-card h2 i { color: #3b82f6; }
        .rep-filter-row { display: flex; gap: 16px; align-items: flex-end; flex-wrap: wrap; }
        .rep-filter-field { display: flex; flex-direction: column; gap: 6px; }
        .rep-filter-field label {
            font-size: 12px; font-weight: 600; color: #64748b;
            text-transform: uppercase; letter-spacing: .04em; margin: 0;
        }
        .rep-filter-field input, .rep-filter-field select {
            padding: 10px 14px; border: 1.5px solid #cbd5e1;
            border-radius: 10px; font-size: 13.5px; color: #1e293b;
            background: #f8fafc; outline: none; min-width: 170px;
            transition: all .18s; font-family: inherit; font-weight: 600;
        }
        .rep-filter-field input:focus, .rep-filter-field select:focus {
            border-color: #3b82f6; background: #fff;
            box-shadow: 0 0 0 3px rgba(59,130,246,0.12);
        }
        .rep-btn {
            padding: 10px 22px; border-radius: 10px; border: none;
            font-size: 13.5px; font-weight: 600; cursor: pointer;
            display: inline-flex; align-items: center; gap: 8px;
            transition: all .18s; font-family: inherit;
        }
        .rep-btn-primary {
            background: linear-gradient(135deg,#3b82f6,#2563eb);
            color: #fff; box-shadow: 0 3px 10px rgba(59,130,246,0.3);
        }
        .rep-btn-primary:hover { transform: translateY(-1px); box-shadow: 0 5px 14px rgba(59,130,246,0.4); }
        .rep-btn-green {
            background: linear-gradient(135deg,#10b981,#059669);
            color: #fff; box-shadow: 0 3px 10px rgba(16,185,129,0.3);
        }
        .rep-btn-green:hover { transform: translateY(-1px); box-shadow: 0 5px 14px rgba(16,185,129,0.4); }

        /* ---- Primary & Secondary KPI Grid ---- */
        .kpi-row {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(240px, 1fr));
            gap: 16px; margin-bottom: 24px;
        }
        .kpi-card {
            background: #fff; border-radius: 16px;
            padding: 18px 20px; border: 1px solid #e8edf5;
            box-shadow: 0 2px 8px rgba(0,0,0,0.03);
            display: flex; flex-direction: column; gap: 5px;
            position: relative; overflow: hidden;
            transition: transform 0.2s, box-shadow 0.2s;
        }
        .kpi-card:hover {
            transform: translateY(-2px);
            box-shadow: 0 6px 16px rgba(0,0,0,0.06);
        }
        .kpi-card .kpi-label {
            font-size: 11.5px;
            font-weight: 700;
            color: #64748b;
            text-transform: uppercase;
            letter-spacing: 0.05em;
        }
        .kpi-card .kpi-val {
            font-size: 22px;
            font-weight: 800;
            color: #1e293b;
        }
        .kpi-card .kpi-sub {
            font-size: 11.5px;
            font-weight: 500;
            color: #94a3b8;
        }
        .kpi-card .kpi-icon {
            position: absolute;
            right: 18px;
            bottom: 14px;
            font-size: 28px;
            opacity: 0.15;
            color: #3b82f6;
        }
        .kpi-card.gradient-fee-receivable { border-left: 5px solid #4f46e5; }
        .kpi-card.gradient-fee-receivable .kpi-icon { color: #4f46e5; }
        .kpi-card.gradient-total-income { border-left: 5px solid #10b981; }
        .kpi-card.gradient-total-income .kpi-icon { color: #10b981; }
        .kpi-card.gradient-expense { border-left: 5px solid #ef4444; }
        .kpi-card.gradient-expense .kpi-icon { color: #ef4444; }
        .kpi-card.gradient-net { border-left: 5px solid #6366f1; }
        .kpi-card.gradient-net .kpi-icon { color: #6366f1; }
        .kpi-card.gradient-member-fee { border-left: 5px solid #3b82f6; }
        .kpi-card.gradient-member-fee .kpi-icon { color: #3b82f6; }
        .kpi-card.gradient-guest-fee { border-left: 5px solid #f59e0b; }
        .kpi-card.gradient-guest-fee .kpi-icon { color: #f59e0b; }
        .kpi-card.gradient-members { border-left: 5px solid #ec4899; }
        .kpi-card.gradient-members .kpi-icon { color: #ec4899; }
        .kpi-card.gradient-shuttle { border-left: 5px solid #06b6d4; }
        .kpi-card.gradient-shuttle .kpi-icon { color: #06b6d4; }

        /* Net Balance Dynamic Badges */
        .badge-surplus { background: #dcfce7; color: #15803d; padding: 4px 8px; border-radius: 6px; font-size: 11px; font-weight: 700; }
        .badge-deficit { background: #fee2e2; color: #b91c1c; padding: 4px 8px; border-radius: 6px; font-size: 11px; font-weight: 700; }

        /* ---- Tables ---- */
        .rep-table-card {
            background: #fff; border-radius: 18px;
            border: 1px solid #e8edf5;
            box-shadow: 0 2px 12px rgba(0,0,0,0.05);
            overflow: hidden;
            margin-bottom: 24px;
        }
        .rep-table-header {
            padding: 16px 24px;
            border-bottom: 1px solid #f1f5f9;
            display: flex; align-items: center; justify-content: space-between;
            flex-wrap: wrap; gap: 10px;
        }
        .rep-table-title {
            font-size: 15px; font-weight: 700; color: #1e293b;
            display: flex; align-items: center; gap: 9px; margin: 0;
        }
        .rep-table-title i {
            width: 32px; height: 32px; border-radius: 8px;
            background: linear-gradient(135deg,#3b82f6,#6366f1);
            display: flex; align-items: center; justify-content: center;
            color: #fff; font-size: 14px;
        }
        .rep-table-title.title-income i {
            background: linear-gradient(135deg, #10b981, #059669);
        }
        .rep-table-title.title-expense i {
            background: linear-gradient(135deg, #ef4444, #dc2626);
        }
        .rep-table-title.title-members i {
            background: linear-gradient(135deg, #3b82f6, #2563eb);
        }
        .rep-badge-pill {
            font-size: 12px; font-weight: 700; padding: 5px 12px;
            border-radius: 20px; background: #f1f5f9; color: #475569;
        }
        .rep-badge-pill.pill-danger { background: #fee2e2; color: #b91c1c; }
        .rep-badge-pill.pill-amber { background: #fef3c7; color: #b45309; }
        .rep-badge-pill.pill-success { background: #dcfce7; color: #15803d; }
        .rep-badge-pill.pill-primary { background: #e0e7ff; color: #4338ca; }

        .rep-table-wrap {
            overflow-x: auto;
            width: 100%;
        }
        .table-custom {
            width: 100%;
            border-collapse: collapse;
            font-size: 13.5px;
        }
        .table-custom th {
            background: #f8fafc;
            padding: 12px 16px;
            font-size: 11px;
            font-weight: 700;
            color: #475569;
            text-transform: uppercase;
            letter-spacing: 0.04em;
            border-bottom: 1.5px solid #e2e8f0;
            white-space: nowrap;
        }
        .table-custom td {
            padding: 12px 16px;
            border-bottom: 1px solid #f1f5f9;
            color: #1e293b;
            vertical-align: middle;
        }
        .table-custom tbody tr:hover td {
            background-color: #f8faff;
        }
        .table-custom tfoot td {
            background: #f8fafc;
            font-weight: 800;
            font-size: 13.5px;
            border-top: 2px solid #cbd5e1;
            padding: 14px 16px;
        }

        .head-badge {
            display: inline-flex; align-items: center; gap: 5px;
            padding: 3px 8px; border-radius: 6px; font-size: 11px; font-weight: 700;
        }
        .head-badge.head-member { background: #dcfce7; color: #15803d; border: 1px solid #bbf7d0; }
        .head-badge.head-shuttle { background: #e0f2fe; color: #0369a1; border: 1px solid #bae6fd; }
        .head-badge.head-guest { background: #fef3c7; color: #b45309; border: 1px solid #fde68a; }
        .head-badge.head-coaching { background: #ede9fe; color: #6d28d9; border: 1px solid #ddd6fe; }
        .head-badge.head-expense { background: #fee2e2; color: #b91c1c; border: 1px solid #fecaca; }
        .head-badge.head-other { background: #f1f5f9; color: #475569; border: 1px solid #e2e8f0; }

        .btn-view-bill {
            display: inline-flex; align-items: center; gap: 4px;
            padding: 4px 10px; border-radius: 6px;
            background: #e0e7ff; color: #4338ca;
            font-size: 11.5px; font-weight: 600;
            text-decoration: none; border: none; cursor: pointer;
            transition: all 0.15s;
        }
        .btn-view-bill:hover { background: #c7d2fe; color: #3730a3; }

        .rep-empty {
            text-align: center; padding: 48px 24px; color: #94a3b8;
        }
        .rep-empty i { font-size: 40px; margin-bottom: 12px; display: block; color: #cbd5e1; }

        /* Print only elements hidden by default on screen */
        .print-only-block { display: none; }
        .print-sig-block { display: none; }

        /* ==========================================================================
           PRINT STYLING (A4 Clean Executive Statement)
           ========================================================================== */
        @media print {
            @page {
                size: A4 portrait;
                margin: 10mm 10mm 12mm 10mm;
            }
            *, *::before, *::after {
                box-shadow: none !important;
                text-shadow: none !important;
            }
            html, body, #wrapper, #page-wrapper {
                background: #ffffff !important;
                color: #0f172a !important;
                margin: 0 !important;
                padding: 0 !important;
                width: 100% !important;
                font-family: 'Inter', -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, Arial, sans-serif !important;
                font-size: 8.5pt !important;
            }

            /* Hide screen controls and non-printable blocks */
            .btn-print-hide, .rep-topbar, .rep-filter-card, .rep-logout, .navbar-static-side,
            .sidebar-collapse, .nav-topbar, .navbar-default, #status_wrapper, .btn-view-bill,
            #dashboard_screen_banner, .kpi-row, .col-bill {
                display: none !important;
            }

            .rep-content {
                padding: 0 !important;
                margin: 0 !important;
                width: 100% !important;
            }
            #dashboard_wrapper {
                display: block !important;
                width: 100% !important;
            }

            /* Show Print Letterhead & Summary */
            .print-only-block {
                display: block !important;
                margin-bottom: 12px !important;
            }

            /* Print Tables Container */
            .rep-table-card {
                border: none !important;
                background: transparent !important;
                box-shadow: none !important;
                margin: 0 0 16px 0 !important;
                padding: 0 !important;
                page-break-inside: auto;
                break-inside: auto;
            }
            .rep-table-header {
                padding: 4px 0 !important;
                border-bottom: 1.5px solid #0f172a !important;
                margin-bottom: 6px !important;
                display: flex !important;
                justify-content: space-between !important;
                align-items: center !important;
            }
            .rep-table-title {
                font-size: 9.5pt !important;
                font-weight: 800 !important;
                color: #0f172a !important;
                text-transform: uppercase !important;
                letter-spacing: 0.3px !important;
            }
            .rep-table-title i {
                display: none !important;
            }
            .rep-badge-pill {
                border: 1px solid #94a3b8 !important;
                background: #f8fafc !important;
                color: #0f172a !important;
                font-size: 7.5pt !important;
                padding: 2px 7px !important;
                font-weight: 700 !important;
                -webkit-print-color-adjust: exact !important;
                print-color-adjust: exact !important;
            }

            .rep-table-wrap {
                overflow: visible !important;
                overflow-x: visible !important;
                width: 100% !important;
                margin: 0 !important;
                padding: 0 !important;
            }

            .table-custom {
                width: 100% !important;
                max-width: 100% !important;
                table-layout: fixed !important;
                border-collapse: collapse !important;
                border: 1px solid #94a3b8 !important;
                font-size: 8pt !important;
            }
            .table-custom th {
                background-color: #1e293b !important;
                color: #ffffff !important;
                -webkit-print-color-adjust: exact !important;
                print-color-adjust: exact !important;
                font-size: 7.5pt !important;
                font-weight: 800 !important;
                padding: 5px 6px !important;
                border: 1px solid #0f172a !important;
                text-transform: uppercase !important;
                letter-spacing: 0.3px !important;
            }
            .table-custom td {
                padding: 4px 6px !important;
                font-size: 8pt !important;
                color: #0f172a !important;
                border: 1px solid #cbd5e1 !important;
                vertical-align: middle !important;
                word-wrap: break-word !important;
                overflow-wrap: break-word !important;
                line-height: 1.3 !important;
            }
            .table-custom tfoot td {
                background-color: #f1f5f9 !important;
                -webkit-print-color-adjust: exact !important;
                print-color-adjust: exact !important;
                font-weight: 800 !important;
                font-size: 8pt !important;
                border-top: 1.5px solid #0f172a !important;
                border-bottom: 1px solid #94a3b8 !important;
                padding: 5px 6px !important;
            }
            .table-custom tr {
                page-break-inside: avoid !important;
                break-inside: avoid !important;
            }
            thead {
                display: table-header-group !important;
            }
            tfoot {
                display: table-footer-group !important;
            }

            .head-badge {
                border: 1px solid #cbd5e1 !important;
                padding: 1px 4px !important;
                font-size: 7pt !important;
                font-weight: 700 !important;
                background: #f1f5f9 !important;
                color: #0f172a !important;
                -webkit-print-color-adjust: exact !important;
                print-color-adjust: exact !important;
            }
            .head-badge i { display: none !important; }

            .badge {
                border: none !important;
                background: transparent !important;
                color: #0f172a !important;
                padding: 0 !important;
                font-size: 8pt !important;
            }

            /* Signature Blocks */
            .print-sig-block {
                display: flex !important;
                justify-content: space-between !important;
                margin-top: 35px !important;
                padding-top: 10px !important;
                page-break-inside: avoid !important;
                break-inside: avoid !important;
            }
            .print-sig-col {
                width: 28% !important;
                text-align: center !important;
            }
            .print-sig-line {
                border-top: 1.2px solid #475569 !important;
                margin-bottom: 5px !important;
            }
            .print-sig-title {
                font-size: 8pt !important;
                font-weight: 700 !important;
                color: #334155 !important;
                text-transform: uppercase !important;
            }
        }
    </style>
</head>

<body class="fixed-navigation">
<div id="wrapper">
    <nav class="navbar-default navbar-static-side btn-print-hide" role="navigation">
        <div class="dropdown profile-element">
            <center>
                <span><img alt="image" class="img-circle" src="../img/customer.png" style="padding-top: 20px; width: 64px; height: 64px; object-fit: cover; border-radius: 50%;"/></span>
                <span class="clear"> <span class="block m-t-xs"> <strong class="font-bold" style="color: #fff; display: block; margin-top: 8px;"><?php echo htmlspecialchars($_SESSION['name'] ?? 'Admin'); ?></strong></span></span>
            </center>
        </div>
        <div class="sidebar-collapse" id="divMenuContainer">
            <!-- menu injected via ajax -->
        </div>
    </nav>

    <div id="page-wrapper" class="gray-bg">
        
        <!-- Topbar -->
        <div class="rep-topbar">
            <div class="rep-topbar-left">
                <a class="navbar-minimalize minimalize-styl-2 rep-hamburger" href="#"><i class="fa fa-bars"></i></a>
                <span class="rep-topbar-title">YMCA <span>Admin</span></span>
            </div>
            <a href="../app_login_manager/logout.php" class="rep-logout">
                <i class="fa fa-sign-out"></i> Log out
            </a>
        </div>

        <div class="rep-content">
            
            <!-- Filters -->
            <div class="rep-filter-card btn-print-hide">
                <h2><i class="fa fa-filter"></i> Generate Monthly Financial Report</h2>
                <div class="rep-filter-row">
                    <div class="rep-filter-field">
                        <label>Month</label>
                        <input type="month" id="filter_month">
                    </div>
                    <div class="rep-filter-field" <?php if (!$is_admin) echo 'style="display:none;"'; ?>>
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
                    </div>
                </div>
            </div>

            <!-- Dedicated Executive Print Header & KPI Summary Table -->
            <div class="print-only-block">
                <!-- 1. Club Header Banner -->
                <div style="text-align:center; padding-bottom:8px; border-bottom:2px solid #1e3a8a; margin-bottom:8px;">
                    <div style="font-size:16pt; font-weight:800; color:#1e3a8a; letter-spacing:0.5px; text-transform:uppercase;">
                        YMCA Badminton Club Poovathussery
                    </div>
                    <div style="font-size:8.5pt; color:#64748b; margin-top:2px;">
                        Poovathussery, Parakkadavu P.O., Ernakulam Dist., Kerala &bull; Phone: +91 94951 83688
                    </div>
                    <div style="background-color:#1e3a8a; color:#ffffff; font-weight:700; font-size:10pt; padding:4px 8px; margin-top:6px; letter-spacing:0.5px; text-transform:uppercase; -webkit-print-color-adjust:exact; print-color-adjust:exact;">
                        Monthly Financial & Operations Statement
                    </div>
                </div>

                <!-- 2. Statement Metadata Table -->
                <table style="width:100%; border:1px solid #cbd5e1; background-color:#f8fafc; margin-bottom:10px; font-size:8pt; border-collapse:collapse; -webkit-print-color-adjust:exact; print-color-adjust:exact;">
                    <tr>
                        <td style="padding:4px 8px; color:#64748b; font-weight:700; width:15%; border:1px solid #e2e8f0;">STATEMENT MONTH:</td>
                        <td style="padding:4px 8px; color:#0f172a; font-weight:800; width:35%; border:1px solid #e2e8f0;" id="print_meta_month">—</td>
                        <td style="padding:4px 8px; color:#64748b; font-weight:700; width:15%; border:1px solid #e2e8f0;">BATCH / GROUP:</td>
                        <td style="padding:4px 8px; color:#0f172a; font-weight:800; width:35%; border:1px solid #e2e8f0;" id="print_meta_group">—</td>
                    </tr>
                    <tr>
                        <td style="padding:4px 8px; color:#64748b; font-weight:700; border:1px solid #e2e8f0;">DATE GENERATED:</td>
                        <td style="padding:4px 8px; color:#0f172a; border:1px solid #e2e8f0;" id="print_meta_date">—</td>
                        <td style="padding:4px 8px; color:#64748b; font-weight:700; border:1px solid #e2e8f0;">SESSIONS HELD:</td>
                        <td style="padding:4px 8px; color:#0f172a; border:1px solid #e2e8f0;" id="print_meta_sessions">—</td>
                    </tr>
                </table>

                <!-- 3. Financial KPI Summary Cards (Compact 5-cell table) -->
                <table style="width:100%; border-collapse:collapse; margin-bottom:12px; font-size:8pt; table-layout:fixed; -webkit-print-color-adjust:exact; print-color-adjust:exact;">
                    <tr>
                        <td style="width:20%; border:1px solid #cbd5e1; background-color:#eef2ff; padding:6px 8px; text-align:center;">
                            <div style="font-size:7pt; font-weight:800; color:#4338ca; text-transform:uppercase;">TOTAL FEE RECEIVABLE</div>
                            <div style="font-size:11.5pt; font-weight:800; color:#3730a3; margin:2px 0;" id="print_kpi_receivable">₹ 0.00</div>
                            <div style="font-size:7pt; color:#4f46e5;" id="print_kpi_rec_sub">0 Members Billed</div>
                        </td>
                        <td style="width:20%; border:1px solid #cbd5e1; background-color:#ecfdf5; padding:6px 8px; text-align:center;">
                            <div style="font-size:7pt; font-weight:800; color:#047857; text-transform:uppercase;">TOTAL AMOUNT COLLECTED</div>
                            <div style="font-size:11.5pt; font-weight:800; color:#065f46; margin:2px 0;" id="print_kpi_income">₹ 0.00</div>
                            <div style="font-size:7pt; color:#059669;" id="print_kpi_inc_sub">0 Collections</div>
                        </td>
                        <td style="width:20%; border:1px solid #cbd5e1; background-color:#fef2f2; padding:6px 8px; text-align:center;">
                            <div style="font-size:7pt; font-weight:800; color:#b91c1c; text-transform:uppercase;">TOTAL EXPENSES</div>
                            <div style="font-size:11.5pt; font-weight:800; color:#991b1b; margin:2px 0;" id="print_kpi_expense">₹ 0.00</div>
                            <div style="font-size:7pt; color:#dc2626;" id="print_kpi_exp_sub">0 Vouchers Paid</div>
                        </td>
                        <td style="width:20%; border:1px solid #cbd5e1; background-color:#ecfeff; padding:6px 8px; text-align:center;">
                            <div style="font-size:7pt; font-weight:800; color:#0e7490; text-transform:uppercase;">SHUTTLE COST</div>
                            <div style="font-size:11.5pt; font-weight:800; color:#0891b2; margin:2px 0;" id="print_kpi_shuttle">₹ 0.00</div>
                            <div style="font-size:7pt; color:#06b6d4;" id="print_kpi_shuttle_sub">0 Shuttles Used</div>
                        </td>
                        <td style="width:20%; border:1px solid #cbd5e1; background-color:#f0fdf4; padding:6px 8px; text-align:center;">
                            <div style="font-size:7pt; font-weight:800; color:#15803d; text-transform:uppercase;">PRESENT MEMBERS</div>
                            <div style="font-size:11.5pt; font-weight:800; color:#166534; margin:2px 0;" id="print_kpi_members">0 Members</div>
                            <div style="font-size:7pt; color:#15803d;" id="print_kpi_mem_sub">0 Sessions Attended</div>
                        </td>
                    </tr>
                </table>
            </div>

            <!-- Dashboard Content Wrapper (Initially hidden) -->
            <div id="dashboard_wrapper" style="display:none;">
                
                <!-- Report Header Banner with Month Name (Screen Only) -->
                <div id="dashboard_screen_banner" style="background: #fff; border-radius: 18px; border: 1px solid #e8edf5; box-shadow: 0 2px 12px rgba(0,0,0,0.05); padding: 18px 24px; margin-bottom: 20px; display:flex; justify-content:space-between; align-items:center; flex-wrap:wrap; gap:12px;">
                    <div>
                        <h2 style="margin: 0; font-size: 20px; font-weight: 800; color: #1e293b;">
                            Financial Statement - <span id="dashboard_month_name" style="color: #3b82f6;"></span>
                        </h2>
                        <p id="dashboard_group_name" style="margin: 4px 0 0; font-size: 13px; color: #64748b; font-weight: 500;"></p>
                    </div>
                </div>

                <!-- Primary Screen KPI Row (Financial Metrics) -->
                <div class="kpi-row">
                    <!-- 1. Total Fee Receivable of that Month -->
                    <div class="kpi-card gradient-fee-receivable">
                        <span class="kpi-label">Total Fee Receivable</span>
                        <span class="kpi-val" id="val_fee_receivable" style="color:#4f46e5;">₹ 0.00</span>
                        <span class="kpi-sub" id="sub_fee_receivable">0 members billed</span>
                        <i class="fa fa-calculator kpi-icon"></i>
                    </div>

                    <!-- 2. Total Income -->
                    <div class="kpi-card gradient-total-income">
                        <span class="kpi-label">Total Amount Collected</span>
                        <span class="kpi-val" id="val_total_income" style="color:#059669;">₹ 0.00</span>
                        <span class="kpi-sub" id="sub_income_count">0 collections recorded</span>
                        <i class="fa fa-money kpi-icon"></i>
                    </div>

                    <!-- 3. Total Expenses -->
                    <div class="kpi-card gradient-expense">
                        <span class="kpi-label">Total Monthly Expenses</span>
                        <span class="kpi-val" id="val_total_expense" style="color:#dc2626;">₹ 0.00</span>
                        <span class="kpi-sub" id="sub_expense_count">0 vouchers paid</span>
                        <i class="fa fa-credit-card kpi-icon"></i>
                    </div>

                    <!-- 4. Member Monthly Fees Collected -->
                    <div class="kpi-card gradient-member-fee">
                        <span class="kpi-label">Member Fees Collected</span>
                        <span class="kpi-val" id="val_member_fee_total" style="color:#2563eb;">₹ 0.00</span>
                        <span class="kpi-sub" id="sub_member_fee_count">0 member receipts</span>
                        <i class="fa fa-users kpi-icon"></i>
                    </div>

                    <!-- 5. Total Guest Fees -->
                    <div class="kpi-card gradient-guest-fee">
                        <span class="kpi-label">Guest Fees Collected</span>
                        <span class="kpi-val" id="val_guest_fee_total" style="color:#d97706;">₹ 0.00</span>
                        <span class="kpi-sub" id="sub_guest_fee_count">0 transactions</span>
                        <i class="fa fa-user-plus kpi-icon"></i>
                    </div>
                </div>

                <!-- Secondary Screen KPI Row (Operations & Shuttles) -->
                <div class="kpi-row">
                    <!-- 6. Present Members -->
                    <div class="kpi-card gradient-members">
                        <span class="kpi-label">Present Members</span>
                        <span class="kpi-val" id="val_member_count">0 Members</span>
                        <span class="kpi-sub" id="sub_member_att_total">Active This Month</span>
                        <i class="fa fa-check-square-o kpi-icon"></i>
                    </div>

                    <!-- 7. Sessions & Attendance Marks -->
                    <div class="kpi-card">
                        <span class="kpi-label">Sessions Held</span>
                        <span class="kpi-val" id="val_sessions">0 Sessions</span>
                        <span class="kpi-sub" id="sub_marks">0 check-in marks</span>
                        <i class="fa fa-clock-o kpi-icon"></i>
                    </div>

                    <!-- 8. Shuttlecock Usage & Cost (Price Above, Shuttles Used Below) -->
                    <div class="kpi-card gradient-shuttle">
                        <span class="kpi-label">Shuttles Used & Cost</span>
                        <span class="kpi-val" id="val_coke_cost" style="color:#0891b2;">₹ 0.00</span>
                        <span class="kpi-sub" id="sub_cokes_used">0 Shuttles Used</span>
                        <i class="fa fa-shopping-cart kpi-icon"></i>
                    </div>
                </div>

                <!-- SECTION 1: Monthly Income Received & Collections Table -->
                <div class="rep-table-card">
                    <div class="rep-table-header">
                        <h3 class="rep-table-title title-income">
                            <i class="fa fa-money"></i> Monthly Income Received & Collections
                        </h3>
                        <span class="rep-badge-pill pill-success" id="pill_income_total">Total Collected: ₹ 0.00</span>
                    </div>
                    <div class="rep-table-wrap">
                        <table class="table-custom" id="tbl_income">
                            <thead>
                                <tr>
                                    <th style="width:5%; text-align:center;">#</th>
                                    <th style="width:13%;">Date</th>
                                    <th style="width:24%;">Who Paid (Member / Payer)</th>
                                    <th style="width:14%;">Category</th>
                                    <th style="width:24%;">Description / Particulars</th>
                                    <th style="width:10%;">Group</th>
                                    <th style="width:14%; text-align:right;">Amount (₹)</th>
                                </tr>
                            </thead>
                            <tbody id="income_tbody">
                                <!-- Dynamic Income Rows -->
                            </tbody>
                            <tfoot id="income_tfoot" style="display:none;">
                                <tr>
                                    <td colspan="6" style="text-align:right; font-weight:800;">TOTAL AMOUNT COLLECTED:</td>
                                    <td style="text-align:right; color:#059669; font-weight:800;" id="foot_total_income">₹ 0.00</td>
                                </tr>
                            </tfoot>
                        </table>
                    </div>
                </div>

                <!-- SECTION 2: Monthly Expenses Paid Table -->
                <div class="rep-table-card">
                    <div class="rep-table-header">
                        <h3 class="rep-table-title title-expense">
                            <i class="fa fa-credit-card"></i> Monthly Expenses Paid
                        </h3>
                        <span class="rep-badge-pill pill-danger" id="pill_expense_total">Total Expenses: ₹ 0.00</span>
                    </div>
                    <div class="rep-table-wrap">
                        <table class="table-custom" id="tbl_expenses">
                            <thead>
                                <tr>
                                    <th style="width:5%; text-align:center;">#</th>
                                    <th style="width:13%;">Date</th>
                                    <th style="width:18%;">Expense Head</th>
                                    <th style="width:36%;">Particulars</th>
                                    <th style="width:10%;">Group</th>
                                    <th class="col-bill" style="width:12%; text-align:center;">Bill / Receipt</th>
                                    <th style="width:14%; text-align:right;">Amount (₹)</th>
                                </tr>
                            </thead>
                            <tbody id="expenses_tbody">
                                <!-- Dynamic Expense Rows -->
                            </tbody>
                            <tfoot id="expenses_tfoot" style="display:none;">
                                <tr>
                                    <td colspan="5" style="text-align:right; font-weight:800;">TOTAL EXPENSES:</td>
                                    <td class="col-bill"></td>
                                    <td style="text-align:right; color:#dc2626; font-weight:800;" id="foot_total_expense">₹ 0.00</td>
                                </tr>
                            </tfoot>
                        </table>
                    </div>
                </div>

                <!-- SECTION 3: Monthly Present Members & Fee Status Table -->
                <div class="rep-table-card">
                    <div class="rep-table-header">
                        <h3 class="rep-table-title title-members">
                            <i class="fa fa-users"></i> Monthly Present Members & Fee Status
                        </h3>
                        <span class="rep-badge-pill pill-primary" id="pill_member_count">0 Members Present</span>
                    </div>
                    <div class="rep-table-wrap">
                        <table class="table-custom" id="tbl_members_summary">
                            <thead>
                                <tr>
                                    <th style="width:5%; text-align:center;">#</th>
                                    <th style="width:32%;">Member Name</th>
                                    <th style="width:18%;">Group(s)</th>
                                    <th style="width:15%; text-align:center;">Sessions Attended</th>
                                    <th style="width:16%; text-align:right;">Fee Receivable (₹)</th>
                                    <th style="width:14%; text-align:center;">Fee Status</th>
                                </tr>
                            </thead>
                            <tbody id="members_tbody">
                                <!-- Dynamic Member Rows -->
                            </tbody>
                            <tfoot id="members_tfoot" style="display:none;">
                                <tr>
                                    <td colspan="3" style="text-align:right; font-weight:800;">TOTALS:</td>
                                    <td style="text-align:center; font-weight:800;" id="foot_total_sessions_att">0</td>
                                    <td style="text-align:right; color:#4f46e5; font-weight:800;" id="foot_total_fee_rec">₹ 0.00</td>
                                    <td style="text-align:center; font-weight:700; color:#64748b;" id="foot_fee_rec_summary">—</td>
                                </tr>
                            </tfoot>
                        </table>
                    </div>
                </div>

                <!-- Print Signatures Block -->
                <div class="print-sig-block">
                    <div class="print-sig-col">
                        <div class="print-sig-line"></div>
                        <div class="print-sig-title">Prepared By (Treasurer)</div>
                    </div>
                    <div class="print-sig-col">
                        <div class="print-sig-line"></div>
                        <div class="print-sig-title">Audited / Verified By</div>
                    </div>
                    <div class="print-sig-col">
                        <div class="print-sig-line"></div>
                        <div class="print-sig-title">President / Secretary</div>
                    </div>
                </div>

            </div> <!-- end #dashboard_wrapper -->

            <!-- Loading & Empty States -->
            <div id="status_wrapper">
                <div class="rep-table-card">
                    <div class="rep-empty">
                        <i class="fa fa-bar-chart"></i>
                        <p>Select a month and group, then click <strong>Generate</strong>.</p>
                    </div>
                </div>
            </div>

        </div> <!-- end .rep-content -->
    </div> <!-- end #page-wrapper -->
</div>

<script src="../js/bootstrap.min.js"></script>
<script src="../js/plugins/metisMenu/jquery.metisMenu.js"></script>
<script src="../js/plugins/slimscroll/jquery.slimscroll.min.js"></script>
<script src="../js/inspinia.js"></script>

<script>
$(document).ready(function() {
    loadMenu();

    // Default to current month
    var now = new Date();
    var monthStr = now.getFullYear() + '-' + ('0' + (now.getMonth() + 1)).slice(-2);
    $('#filter_month').val(monthStr);

    // Load groups
    $.ajax({
        type: 'POST',
        url: 'api/attendance.php',
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
            generateReport(); // auto-load on start
        }
    });
});

function formatCurrency(value) {
    return new Intl.NumberFormat('en-IN', { style: 'currency', currency: 'INR' }).format(value || 0);
}

function formatDate(dateStr) {
    if (!dateStr || dateStr === '0000-00-00') return '—';
    var parts = dateStr.split('-');
    if (parts.length === 3) {
        var d = new Date(parts[0], parts[1] - 1, parts[2]);
        if (!isNaN(d.getTime())) {
            return d.toLocaleDateString('en-IN', { day: '2-digit', month: 'short', year: 'numeric' });
        }
    }
    return dateStr;
}

function openBillInvoice(fileName) {
    if (!fileName) return;
    var path = "../image_upload/payments/" + encodeURIComponent(fileName);
    if (fileName.indexOf('shuttle_bill_') === 0) {
        path = "../image_upload/shuttle_cock_bills/" + encodeURIComponent(fileName);
    }
    window.open(path, "_blank");
}

function getHeadBadge(headName) {
    var h = (headName || '').toLowerCase();
    if (h.indexOf('member') !== -1) {
        return '<span class="head-badge head-member"><i class="fa fa-user" style="font-size:8px;"></i> ' + headName + '</span>';
    } else if (h.indexOf('guest') !== -1) {
        return '<span class="head-badge head-guest"><i class="fa fa-user-plus" style="font-size:9px;"></i> ' + headName + '</span>';
    } else if (h.indexOf('shuttle') !== -1) {
        return '<span class="head-badge head-shuttle"><i class="fa fa-circle" style="font-size:7px;"></i> ' + headName + '</span>';
    } else if (h.indexOf('coaching') !== -1) {
        return '<span class="head-badge head-coaching"><i class="fa fa-graduation-cap" style="font-size:9px;"></i> ' + headName + '</span>';
    } else if (h.indexOf('expense') !== -1) {
        return '<span class="head-badge head-expense"><i class="fa fa-tag" style="font-size:8px;"></i> ' + headName + '</span>';
    } else {
        return '<span class="head-badge head-other"><i class="fa fa-folder-open-o" style="font-size:8px;"></i> ' + headName + '</span>';
    }
}

function generateReport() {
    var month = $('#filter_month').val();
    var group_id = $('#filter_group').val();
    if (!month) {
        swal("Warning", "Please select a month.", "warning");
        return;
    }

    var dateObj = new Date(month + '-01');
    var monthName = dateObj.toLocaleString('default', { month: 'long', year: 'numeric' });
    var groupName = $('#filter_group option:selected').text();
    var currentDateStr = new Date().toLocaleDateString('en-IN', { day: '2-digit', month: 'short', year: 'numeric', hour: '2-digit', minute: '2-digit', hour12: true });

    $('#dashboard_month_name').text(monthName);
    $('#dashboard_group_name').text('Group: ' + groupName);

    // Update Print Metadata Fields
    $('#print_meta_month').text(monthName);
    $('#print_meta_group').text(groupName);
    $('#print_meta_date').text(currentDateStr);

    // Show loading spinner
    $('#status_wrapper').html('<div class="rep-table-card"><div class="rep-empty"><i class="fa fa-spinner fa-spin" style="color:#3b82f6; font-size:32px;"></i><p style="margin-top:12px; font-weight:600; color:#475569;">Calculating monthly financial metrics...</p></div></div>').show();
    $('#dashboard_wrapper').hide();

    $.ajax({
        type: 'POST',
        url: 'api/monthly_financial_report.php',
        data: {
            action: 'load_report',
            month: month,
            group_id: group_id
        },
        success: function(res) {
            var response = typeof res === 'string' ? JSON.parse(res) : res;
            if (response.error) {
                $('#status_wrapper').html('<div class="rep-table-card"><div class="rep-empty"><i class="fa fa-exclamation-triangle" style="color:#ef4444;"></i><p>' + response.error + '</p></div></div>');
                return;
            }

            var summary = response.summary || {};
            var incomeItems = response.income_items || [];
            var expenses = response.expense_items || [];
            var members = response.members || [];

            // Populate Screen Primary KPI Cards
            $('#val_fee_receivable').text(formatCurrency(summary.total_fee_receivable));
            var feeSub = (summary.fee_receivable_count || 0) + ' members billed';
            if (summary.fee_pending_for_month > 0) {
                feeSub += ' • Pending: ' + formatCurrency(summary.fee_pending_for_month);
            } else if (summary.total_fee_receivable > 0) {
                feeSub += ' • All Settled';
            }
            if (summary.is_fee_estimated) {
                feeSub += ' (Estimated)';
            }
            $('#sub_fee_receivable').text(feeSub);

            $('#val_total_income').text(formatCurrency(summary.total_income));
            $('#sub_income_count').text((summary.income_count || 0) + ' collections recorded');

            $('#val_total_expense').text(formatCurrency(summary.total_expense));
            $('#sub_expense_count').text((summary.expense_count || 0) + ' vouchers recorded');
            
            $('#val_member_fee_total').text(formatCurrency(summary.member_fees_total));
            $('#sub_member_fee_count').text((summary.member_fees_count || 0) + ' member receipts');

            $('#val_guest_fee_total').text(formatCurrency(summary.guest_fee_total));
            $('#sub_guest_fee_count').text((summary.guest_fee_count || 0) + ' guest transactions');

            // Populate Screen Secondary KPI Cards
            $('#val_member_count').text((summary.member_count || 0) + ' Members');
            $('#sub_member_att_total').text('Active This Month');

            $('#val_sessions').text((summary.total_sessions || 0) + ' Sessions');
            $('#sub_marks').text((summary.total_attendance_marks || 0) + ' total attendance marks');

            // Shuttle Usage & Cost (Price Above, Shuttles Used Below)
            $('#val_coke_cost').text(formatCurrency(summary.shuttle_total_cost));
            $('#sub_cokes_used').text((summary.shuttle_total_used || 0) + ' Shuttles Used');

            // Populate Dedicated Print KPI Table
            $('#print_meta_sessions').text((summary.total_sessions || 0) + ' Sessions (' + (summary.total_attendance_marks || 0) + ' Marks)');
            $('#print_kpi_receivable').text(formatCurrency(summary.total_fee_receivable));
            $('#print_kpi_rec_sub').text((summary.fee_receivable_count || 0) + ' Members Billed');
            $('#print_kpi_income').text(formatCurrency(summary.total_income));
            $('#print_kpi_inc_sub').text((summary.income_count || 0) + ' Collections');
            $('#print_kpi_expense').text(formatCurrency(summary.total_expense));
            $('#print_kpi_exp_sub').text((summary.expense_count || 0) + ' Vouchers Paid');
            $('#print_kpi_shuttle').text(formatCurrency(summary.shuttle_total_cost));
            $('#print_kpi_shuttle_sub').text((summary.shuttle_total_used || 0) + ' Shuttles Used');
            $('#print_kpi_members').text((summary.member_count || 0) + ' Active');
            $('#print_kpi_mem_sub').text((summary.total_attendance_marks || 0) + ' Total Check-ins');

            // Section 1: Income Received & Collections Table
            var incHtm = '';
            var incSum = 0;
            if (incomeItems && incomeItems.length > 0) {
                for (var i = 0; i < incomeItems.length; i++) {
                    var inc = incomeItems[i];
                    incSum += parseFloat(inc.amount || 0);

                    incHtm += '<tr>';
                    incHtm += '<td style="color:#64748b; font-weight:600; text-align:center;">' + (i + 1) + '</td>';
                    incHtm += '<td style="font-weight:600; color:#1e293b; white-space:nowrap;">' + formatDate(inc.date) + '</td>';
                    incHtm += '<td style="font-weight:700; color:#0f172a;">' + inc.payer_name + '</td>';
                    incHtm += '<td>' + getHeadBadge(inc.category) + '</td>';
                    incHtm += '<td style="font-weight:500;">' + (inc.description || '—') + '</td>';
                    incHtm += '<td><span class="badge badge-default" style="background:#f1f5f9; color:#475569; font-weight:500; font-size:11px;">' + (inc.group_name || 'General') + '</span></td>';
                    incHtm += '<td style="text-align:right; font-weight:700; color:#059669;">' + formatCurrency(inc.amount) + '</td>';
                    incHtm += '</tr>';
                }
                $('#income_tbody').html(incHtm);
                $('#foot_total_income').text(formatCurrency(incSum));
                $('#income_tfoot').show();
            } else {
                $('#income_tbody').html('<tr><td colspan="7" class="rep-empty"><i class="fa fa-money"></i>No income or collections recorded for this month.</td></tr>');
                $('#income_tfoot').hide();
            }
            $('#pill_income_total').text('Total Collected: ' + formatCurrency(incSum) + ' (' + (incomeItems ? incomeItems.length : 0) + ')');

            // Section 2: Expenses Table
            var expHtm = '';
            var expSum = 0;
            if (expenses && expenses.length > 0) {
                for (var j = 0; j < expenses.length; j++) {
                    var exp = expenses[j];
                    expSum += parseFloat(exp.amount || 0);
                    var billBtn = exp.bill_photo 
                        ? '<button type="button" class="btn-view-bill" onclick="openBillInvoice(\'' + exp.bill_photo + '\')"><i class="fa fa-file-text-o"></i> View Bill</button>'
                        : '<span style="color:#94a3b8; font-size:12px;">—</span>';

                    expHtm += '<tr>';
                    expHtm += '<td style="color:#64748b; font-weight:600; text-align:center;">' + (j + 1) + '</td>';
                    expHtm += '<td style="font-weight:600; color:#1e293b; white-space:nowrap;">' + formatDate(exp.date) + '</td>';
                    expHtm += '<td>' + getHeadBadge(exp.head_name) + '</td>';
                    expHtm += '<td style="font-weight:500;">' + (exp.particulars || '—') + '</td>';
                    expHtm += '<td><span class="badge badge-default" style="background:#f1f5f9; color:#475569; font-weight:500; font-size:11px;">' + (exp.group_name || 'General') + '</span></td>';
                    expHtm += '<td class="col-bill" style="text-align:center;">' + billBtn + '</td>';
                    expHtm += '<td style="text-align:right; font-weight:700; color:#dc2626;">' + formatCurrency(exp.amount) + '</td>';
                    expHtm += '</tr>';
                }
                $('#expenses_tbody').html(expHtm);
                $('#foot_total_expense').text(formatCurrency(expSum));
                $('#expenses_tfoot').show();
            } else {
                $('#expenses_tbody').html('<tr><td colspan="7" class="rep-empty"><i class="fa fa-credit-card"></i>No expenses recorded for this month.</td></tr>');
                $('#expenses_tfoot').hide();
            }
            $('#pill_expense_total').text('Total Expenses: ' + formatCurrency(expSum) + ' (' + (expenses ? expenses.length : 0) + ')');

            // Section 3: Monthly Present Members & Fee Status Table
            var memHtm = '';
            var memTotalAtt = 0;
            var memTotalRec = 0;
            var memTotalPaid = 0;
            if (members && members.length > 0) {
                for (var k = 0; k < members.length; k++) {
                    var m = members[k];
                    var attVal = parseInt(m.attendance || 0);
                    var recVal = parseFloat(m.fee_receivable || 0);
                    var paidVal = parseFloat(m.fee_paid || 0);
                    memTotalAtt += attVal;
                    memTotalRec += recVal;
                    memTotalPaid += paidVal;

                    var statusBadge = '';
                    if (m.fee_status === 'Paid') {
                        statusBadge = '<span class="badge" style="background:#dcfce7; color:#15803d; padding:3px 8px; border-radius:6px; font-weight:700; font-size:11px;">Paid</span>';
                    } else if (m.fee_status === 'Partial') {
                        statusBadge = '<span class="badge" style="background:#fef3c7; color:#b45309; padding:3px 8px; border-radius:6px; font-weight:700; font-size:11px;">Paid ' + formatCurrency(paidVal) + '</span>';
                    } else if (m.fee_status === 'Exempt') {
                        statusBadge = '<span class="badge" style="background:#f1f5f9; color:#64748b; padding:3px 8px; border-radius:6px; font-weight:600; font-size:11px;">Exempt</span>';
                    } else {
                        statusBadge = '<span class="badge" style="background:#fee2e2; color:#b91c1c; padding:3px 8px; border-radius:6px; font-weight:700; font-size:11px;">Pending</span>';
                    }

                    memHtm += '<tr>';
                    memHtm += '<td style="color:#64748b; font-weight:600; text-align:center;">' + (k + 1) + '</td>';
                    memHtm += '<td style="font-weight:700; color:#0f172a;">' + m.name + '</td>';
                    memHtm += '<td><span class="badge badge-primary" style="background:#e0e7ff; color:#4f46e5; border:none; padding:4px 8px; font-weight:500;">' + (m.group_names || '—') + '</span></td>';
                    memHtm += '<td style="text-align:center; font-weight:800; color:#1e293b;">' + attVal + '</td>';
                    memHtm += '<td style="text-align:right; font-weight:800; color:#4f46e5;">' + formatCurrency(recVal) + '</td>';
                    memHtm += '<td style="text-align:center;">' + statusBadge + '</td>';
                    memHtm += '</tr>';
                }
                $('#members_tbody').html(memHtm);
                $('#foot_total_sessions_att').text(memTotalAtt);
                $('#foot_total_fee_rec').text(formatCurrency(memTotalRec));
                $('#foot_fee_rec_summary').text(formatCurrency(memTotalPaid) + ' collected');
                $('#members_tfoot').show();
            } else {
                $('#members_tbody').html('<tr><td colspan="6" class="rep-empty"><i class="fa fa-users"></i>No members were present for this month.</td></tr>');
                $('#members_tfoot').hide();
            }
            $('#pill_member_count').text(members.length + ' Members • Total Receivable: ' + formatCurrency(summary.total_fee_receivable));

            $('#status_wrapper').hide();
            $('#dashboard_wrapper').show();
        },
        error: function(xhr, status, error) {
            $('#status_wrapper').html('<div class="rep-table-card"><div class="rep-empty"><i class="fa fa-exclamation-triangle" style="color:#ef4444;"></i><p>Failed to load report data: ' + error + '</p></div></div>');
        }
    });
}
</script>
</body>
</html>
