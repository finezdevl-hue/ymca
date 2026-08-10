<?php
session_start();
session_write_close();

if (empty($_SESSION['login_id'])) {
    header("Location: ../app_login_manager/logout.php");
    exit();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="description" content="Yearly Attendance Report - YMCA Management System">
    <title>YMCA | Yearly Attendance Report</title>

    <link href="../css/bootstrap.min.css" rel="stylesheet">
    <link href="../font-awesome/css/font-awesome.css" rel="stylesheet">
    <link href="../css/animate.css" rel="stylesheet">
    <link href="../css/style.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <link href="../css/plugins/sweetalert/sweetalert.css" rel="stylesheet">
    
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
        .rep-filter-field input[type="text"] {
            padding: 10px 14px; border-radius: 10px;
            border: 1.5px solid #e2e8f0; background: #f8faff;
            font-size: 14px; font-weight: 500; color: #1e293b;
            font-family: 'Inter', sans-serif;
            outline: none; transition: border-color 0.2s, box-shadow 0.2s;
            min-width: 180px;
        }
        .rep-filter-field select:focus,
        .rep-filter-field input[type="text"]:focus {
            border-color: #3b82f6;
            box-shadow: 0 0 0 3px rgba(59,130,246,0.1);
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

        /* ---- Members Grid Table ---- */
        .rep-table-card {
            background: #fff; border-radius: 18px;
            border: 1px solid #e8edf5;
            box-shadow: 0 2px 12px rgba(0,0,0,0.05);
            overflow: hidden;
            width: 100%;
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
        .rep-table-wrap {
            overflow-x: auto;
            width: 100%;
        }
        .table-custom {
            width: 100%;
            border-collapse: collapse;
            font-size: 13px;
        }
        .table-custom th {
            background: #f8fafc;
            padding: 11px 10px;
            font-size: 10px;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 0.05em;
            color: #475569;
            border-bottom: 1.5px solid #e8edf5;
            text-align: left;
            white-space: nowrap;
        }
        .month-col-head {
            text-align: center !important;
            padding: 11px 6px !important;
            min-width: 36px;
            background: #f0f4ff !important;
            color: #3b82f6 !important;
            font-size: 10px !important;
            font-weight: 700 !important;
            letter-spacing: 0.04em;
            border-left: 1px solid #e8edf5 !important;
        }
        .total-col-head {
            text-align: center !important;
            padding: 11px 8px !important;
            min-width: 52px;
            background: linear-gradient(135deg,#eff6ff,#f5f3ff) !important;
            color: #4338ca !important;
            font-size: 10px !important;
            font-weight: 800 !important;
            line-height: 1.3;
            border-left: 2px solid #c7d2fe !important;
        }
        .table-custom td {
            padding: 12px 10px;
            color: #334155;
            border-bottom: 1px solid #e8edf5;
            vertical-align: middle;
        }
        .table-custom tbody tr { transition: background 0.15s; }
        .table-custom tbody tr:hover { background: #f8faff; }
        .month-cell {
            text-align: center;
            border-left: 1px solid #f1f5f9;
            padding: 10px 4px !important;
        }
        .month-dot {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            width: 22px; height: 22px;
            border-radius: 50%;
            background: linear-gradient(135deg,#10b981,#059669);
            color: #fff;
            font-size: 9px;
            box-shadow: 0 2px 5px rgba(16,185,129,0.35);
            cursor: default;
        }
        .total-cell {
            text-align: center;
            border-left: 2px solid #e0e7ff !important;
            background: #f8faff;
            font-size: 14px !important;
            font-weight: 800 !important;
            color: #3730a3 !important;
            padding: 12px 6px !important;
        }

        .member-profile-cell {
            display: flex;
            align-items: center;
            gap: 12px;
        }
        .member-avatar {
            width: 42px; height: 42px;
            border-radius: 50%;
            object-fit: cover;
            border: 2px solid #e2e8f0;
        }
        .member-name-text {
            font-weight: 600;
            color: #0f172a;
        }
        .member-email-text {
            font-size: 12px;
            color: #64748b;
        }

        .group-badge {
            background: #eff6ff;
            color: #2563eb;
            padding: 4px 10px;
            border-radius: 20px;
            font-size: 12px;
            font-weight: 500;
            border: 1px solid #dbeafe;
        }

        /* ---- Modal Details ---- */
        .modal-header-custom {
            background: linear-gradient(135deg,#3b82f6,#6366f1);
            color: #fff;
            border-top-left-radius: 12px;
            border-top-right-radius: 12px;
            padding: 16px 24px;
        }
        .modal-header-custom .close {
            color: #fff;
            opacity: 0.8;
            font-size: 24px;
            outline: none;
        }
        .modal-header-custom .close:hover { opacity: 1; }

        .modal-profile-header {
            display: flex;
            align-items: center;
            gap: 20px;
            padding: 20px 24px;
            border-bottom: 1px solid #e2e8f0;
            background: #f8fafc;
            flex-wrap: wrap;
        }
        .modal-avatar {
            width: 80px; height: 80px;
            border-radius: 50%;
            object-fit: cover;
            border: 3px solid #fff;
            box-shadow: 0 4px 10px rgba(0,0,0,0.1);
        }
        .modal-member-info h3 {
            margin: 0 0 4px 0;
            font-weight: 700;
            color: #0f172a;
        }
        
        /* Stats strip */
        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(130px, 1fr));
            gap: 12px;
            padding: 20px 24px;
        }
        .stat-card-custom {
            background: #fff;
            border: 1.5px solid #e2e8f0;
            border-radius: 12px;
            padding: 14px;
            text-align: center;
            box-shadow: 0 1px 4px rgba(0,0,0,0.02);
        }
        .stat-card-custom.overall {
            background: linear-gradient(135deg, #eff6ff 0%, #f5f3ff 100%);
            border-color: #bfdbfe;
        }
        .stat-card-custom .stat-val {
            font-size: 24px;
            font-weight: 800;
            color: #1e293b;
            margin-bottom: 2px;
        }
        .stat-card-custom.overall .stat-val {
            color: #2563eb;
        }
        .stat-card-custom .stat-label {
            font-size: 11px;
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: 0.05em;
            color: #64748b;
        }

        /* Grid attendance styles */
        .table-grid-attendance th, .table-grid-attendance td {
            border: 1px solid #e2e8f0;
            padding: 8px 4px;
            vertical-align: middle;
            text-align: center;
        }
        .table-grid-attendance th {
            background: #f8fafc;
            font-weight: 700;
            font-size: 11px;
            text-transform: uppercase;
        }
        .cell-p {
            color: #10b981 !important;
            font-weight: bold;
        }
        .cell-a {
            color: #ef4444 !important;
            font-weight: bold;
        }
        .cell-empty-dash {
            color: #cbd5e1;
        }
        .cell-out-of-bounds {
            background: #f1f5f9;
        }
        .grid-total-val {
            font-weight: bold;
            color: #1e293b;
        }
        .grid-rate-val {
            font-weight: bold;
            padding: 2px 6px;
            border-radius: 4px;
        }
        
        .rate-high { background: #dcfce7; color: #15803d; }
        .rate-medium { background: #fef9c3; color: #b45309; }
        .rate-low { background: #fee2e2; color: #b91c1c; }
        .rate-zero { background: #f1f5f9; color: #64748b; }

        .rep-empty {
            text-align: center; padding: 40px 20px;
            color: #94a3b8;
        }
        .rep-empty i { font-size: 36px; margin-bottom: 8px; display: block; color: #cbd5e1; }

        /* ---- Print Section Layout ---- */
        #print_layout {
            display: none;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            color: #000;
            padding: 16px;
        }
        
        /* ---- Print CSS styles ---- */
        @media print {
            @page { size: landscape; margin: 10mm; }
            body {
                background: #fff !important;
                color: #000 !important;
                font-size: 11px !important;
            }
            #wrapper, .modal-backdrop, .modal, .rep-topbar, .rep-filter-card, .rep-table-card, .btn-print-hide, #report_details_view,
            .loadingoverlay, [class*="loadingoverlay"] {
                display: none !important;
            }
            #print_layout {
                display: block !important;
            }
            .p-header {
                text-align: center;
                border-bottom: 3px double #333;
                padding-bottom: 10px;
                margin-bottom: 14px;
            }
            .p-header h1 { margin:0; font-size:18px; font-weight:bold; }
            .p-header p  { margin:3px 0 0; font-size:12px; }
            .p-legend {
                display: flex; gap: 20px; margin-bottom: 10px;
                font-size: 10px; color: #333;
            }
            .p-legend-dot {
                display:inline-block; width:12px; height:12px;
                border-radius:50%; background:#10b981;
                border:1px solid #059669;
                margin-right:4px; vertical-align:middle;
            }
            /* The consolidated matrix print table */
            .p-matrix {
                width: 100%;
                border-collapse: collapse;
                font-size: 9px;
                page-break-inside: auto;
            }
            .p-matrix th {
                background: #f2f2f2 !important;
                border: 1px solid #999;
                padding: 5px 4px;
                text-align: center;
                font-weight: bold;
                white-space: nowrap;
                -webkit-print-color-adjust: exact;
                print-color-adjust: exact;
            }
            .p-matrix th.p-mth-head {
                background: #dbeafe !important;
                color: #1e3a8a;
                -webkit-print-color-adjust: exact;
                print-color-adjust: exact;
            }
            .p-matrix th.p-tot-head {
                background: #ede9fe !important;
                color: #3730a3;
                -webkit-print-color-adjust: exact;
                print-color-adjust: exact;
            }
            .p-matrix td {
                border: 1px solid #ccc;
                padding: 5px 4px;
                text-align: center;
                vertical-align: middle;
            }
            .p-matrix td.p-name-cell {
                text-align: left;
                font-weight: 600;
                min-width: 120px;
                max-width: 160px;
                white-space: nowrap;
                overflow: hidden;
                text-overflow: ellipsis;
            }
            .p-matrix td.p-present {
                background: #d1fae5 !important;
                color: #065f46;
                font-weight: 700;
                font-size: 10px;
                -webkit-print-color-adjust: exact;
                print-color-adjust: exact;
            }
            .p-matrix td.p-absent {
                color: #94a3b8;
            }
            .p-matrix td.p-total {
                background: #ede9fe !important;
                color: #3730a3;
                font-weight: 800;
                -webkit-print-color-adjust: exact;
                print-color-adjust: exact;
            }
            .p-matrix tfoot td {
                background: #dbeafe !important;
                font-weight: 800;
                color: #1e3a8a;
                border-top: 2px solid #666;
                -webkit-print-color-adjust: exact;
                print-color-adjust: exact;
            }
            .p-matrix tbody tr:nth-child(even) td {
                background: #fafafa !important;
                -webkit-print-color-adjust: exact;
                print-color-adjust: exact;
            }
        }
    </style>
</head>

<body>
    <input type="hidden" id="hdn_current_page" value="1">

    <div id="wrapper">
        <!-- Sidebar Navigation -->
        <nav class="navbar-default navbar-static-side" role="navigation">
            <div class="dropdown profile-element">
                <center>
                    <span><img alt="image" class="img-circle" src="../img/customer.png" style="padding-top:20px;"/></span>
                    <span class="clear"><span class="block m-t-xs"><strong class="font-bold"><?php echo htmlspecialchars($_SESSION['name']); ?></strong></span></span>
                </center>
            </div>
            <div class="sidebar-collapse" id="divMenuContainer">
                <!-- Injected via app_menu/menu.js -->
            </div>
        </nav>

        <!-- Main Workspace -->
        <div id="page-wrapper" style="background:#f0f4ff; padding:0; min-height:100vh;">
            <!-- Header bar -->
            <div class="rep-topbar">
                <div class="rep-topbar-left">
                    <button class="navbar-minimalize minimalize-styl-2 rep-hamburger"><i class="fa fa-bars"></i></button>
                    <span class="rep-topbar-title">YMCA <span>Admin</span></span>
                </div>
                <a href="../app_login_manager/logout.php" class="rep-logout">
                    <i class="fa fa-sign-out"></i> Log out
                </a>
            </div>

            <!-- Page Contents -->
            <div class="rep-content">
                
                <!-- 1. Members List View -->
                <div id="members_list_view">
                    <!-- Filters Card -->
                    <div class="rep-filter-card">
                        <h2><i class="fa fa-filter"></i> Yearly Attendance Report Filters</h2>
                        <div class="rep-filter-row">
                            <div class="rep-filter-field">
                                <label for="select_year">Year</label>
                                <select id="select_year" onchange="loadData(1)">
                                    <!-- Populated dynamically in script -->
                                </select>
                            </div>
                            <div class="rep-filter-field">
                                <label for="txt_search">Search Member</label>
                                <input type="text" id="txt_search" placeholder="Type member name...">
                            </div>
                            <div style="display:flex; gap:10px;">
                                <button class="rep-btn rep-btn-primary" onclick="loadData(1)">
                                    <i class="fa fa-search"></i> Search
                                </button>
                                <button class="rep-btn rep-btn-outline" onclick="clearFilters()">
                                    <i class="fa fa-times"></i> Clear
                                </button>
                                <button class="rep-btn" id="btn_print_consolidated" onclick="printConsolidatedReport()" style="background: linear-gradient(135deg,#f59e0b,#d97706); color:#fff; box-shadow:0 3px 12px rgba(245,158,11,0.3);">
                                    <i class="fa fa-print"></i> Print Report
                                </button>
                            </div>
                        </div>
                    </div>

                    <!-- Members List Card -->
                    <div class="rep-table-card">
                        <div class="rep-table-header">
                            <h3 class="rep-table-title">
                                <i class="fa fa-calendar-check-o"></i>
                                Yearly Attendance Matrix &nbsp;<span id="lbl_found_count" style="font-size:12px; font-weight:500; background:#eff6ff; color:#2563eb; border:1px solid #dbeafe; border-radius:20px; padding:3px 12px;">0 members</span>
                            </h3>
                            <div style="font-size:12px; color:#64748b; display:flex; align-items:center; gap:12px; flex-wrap:wrap;">
                                <span><span style="display:inline-block; width:16px; height:16px; border-radius:50%; background:#10b981; text-align:center; line-height:16px; color:#fff; font-size:9px;"><i class="fa fa-check"></i></span> &nbsp;Present (days shown on hover)</span>
                                <span style="color:#cbd5e1; font-weight:600;">—</span> Not present
                            </div>
                        </div>
                        <div class="rep-table-wrap">
                            <table class="table-custom">
                                <thead>
                                    <tr>
                                        <th style="width: 50px;">No</th>
                                        <th style="min-width: 160px;">Member</th>
                                        <th class="month-col-head">Apr</th>
                                        <th class="month-col-head">May</th>
                                        <th class="month-col-head">Jun</th>
                                        <th class="month-col-head">Jul</th>
                                        <th class="month-col-head">Aug</th>
                                        <th class="month-col-head">Sep</th>
                                        <th class="month-col-head">Oct</th>
                                        <th class="month-col-head">Nov</th>
                                        <th class="month-col-head">Dec</th>
                                        <th class="month-col-head">Jan</th>
                                        <th class="month-col-head">Feb</th>
                                        <th class="month-col-head">Mar</th>
                                        <th class="total-col-head">Total<br>Months</th>
                                        <th class="total-col-head">Total<br>Days</th>
                                        <th style="text-align: center; width: 90px;">Details</th>
                                    </tr>
                                </thead>
                                <tbody id="tbl_members_body">
                                    <!-- Injected dynamically -->
                                </tbody>
                                <tfoot id="tbl_members_foot" style="display:none;">
                                    <tr style="background: linear-gradient(135deg,#eff6ff,#f5f3ff); border-top:2px solid #c7d2fe;">
                                        <td colspan="2" style="padding:12px 18px; font-weight:700; color:#1e293b; font-size:13px;"><i class="fa fa-bar-chart" style="color:#6366f1; margin-right:6px;"></i>Page Summary</td>
                                        <td colspan="12" style="text-align:center; color:#475569; font-size:12px; padding:12px 8px;">&nbsp;</td>
                                        <td style="text-align:center; font-weight:800; color:#2563eb; font-size:15px; padding:12px 8px;" id="foot_total_months">—</td>
                                        <td style="text-align:center; font-weight:800; color:#2563eb; font-size:15px; padding:12px 8px;" id="foot_total_days">—</td>
                                        <td></td>
                                    </tr>
                                </tfoot>
                            </table>
                        </div>
                        <div id="pagination_container" style="padding:15px; text-align:right;">
                            <!-- Pagination injected dynamically -->
                        </div>
                    </div>
                </div><!-- end #members_list_view -->

                <!-- 2. Report Details View -->
                <div id="report_details_view" style="display: none; background: #fff; border-radius: 18px; border: 1px solid #e8edf5; box-shadow: 0 2px 12px rgba(0,0,0,0.05); overflow: hidden; padding-bottom: 24px;">
                    <!-- Back & Control Bar -->
                    <div class="btn-print-hide" style="background:#f8fafc; border-bottom:1px solid #e2e8f0; padding:16px 24px; display:flex; align-items:center; justify-content:space-between; flex-wrap:wrap; gap:10px;">
                        <button class="rep-btn rep-btn-outline" onclick="showMembersList()" <?php 
                            $lid = (int)$_SESSION['login_id'];
                            if (!isSuperAdmin($lid) && !isGroupAdmin($lid) && !isAttendanceMaster($lid)) echo 'style="display:none;"'; 
                        ?>><i class="fa fa-arrow-left"></i> Back to Members</button>
                        <h4 style="font-weight: 700; margin:0; color:#1e293b;"><i class="fa fa-bar-chart" style="color:#3b82f6;"></i> Yearly Attendance Summary (<span id="details_year_label"></span>)</h4>
                        <?php if ($_SESSION['login_id'] != 1): ?>
                        <div style="display:flex; align-items:center; gap:8px;">
                            <label style="font-size:12px; font-weight:600; color:#64748b; margin:0; text-transform:uppercase;">Select Year:</label>
                            <select id="details_select_year" class="form-control" style="width:140px; padding:6px 12px; border-radius:8px; height:34px; font-weight:600;" onchange="viewYearlyReport(<?php echo $_SESSION['user_id'] ?? 0; ?>)">
                                <!-- Populated dynamically in script -->
                            </select>
                        </div>
                        <?php endif; ?>
                        <button class="rep-btn rep-btn-primary" onclick="printReport()"><i class="fa fa-print"></i> Print Report</button>
                    </div>

                    <!-- Profile summary -->
                    <div class="modal-profile-header">
                        <img id="m_img" src="../img/customer.png" class="modal-avatar" onerror="this.src='../img/customer.png'">
                        <div class="modal-member-info">
                            <h3 id="m_name" style="margin: 0; font-weight:700; color:#0f172a;">Member Name</h3>
                            <div style="margin-top:6px; display:flex; flex-direction:column; gap:4px;">
                                <div><span class="group-badge" id="m_group">Group: —</span></div>
                                <div style="font-size:13px; color:#64748b; margin-top:2px;">
                                    <i class="fa fa-envelope" style="width: 16px;"></i> <span id="m_email">email@domain.com</span>
                                </div>
                                <div style="font-size:13px; color:#64748b;">
                                    <i class="fa fa-phone" style="width: 16px;"></i> <span id="m_phone">0000000000</span>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Stats summary strip -->
                    <div class="stats-grid">
                        <div class="stat-card-custom overall">
                            <div class="stat-val" id="stat_pct">0%</div>
                            <div class="stat-label">Yearly Rate</div>
                        </div>
                        <div class="stat-card-custom">
                            <div class="stat-val" id="stat_sessions">0</div>
                            <div class="stat-label">Total Sessions</div>
                        </div>
                        <div class="stat-card-custom">
                            <div class="stat-val" id="stat_present" style="color: #10b981;">0</div>
                            <div class="stat-label">Days Present</div>
                        </div>
                        <div class="stat-card-custom">
                            <div class="stat-val" id="stat_absent" style="color: #ef4444;">0</div>
                            <div class="stat-label">Days Absent</div>
                        </div>
                    </div>

                    <!-- Month-by-month grid breakdown -->
                    <h5 class="monthly-breakdown-title" style="padding: 18px 24px 8px; font-weight:700; color:#334155; border-bottom: 1.5px solid #e2e8f0; margin-bottom:0;">Day-by-Day Yearly Attendance Grid</h5>
                    <div class="rep-table-wrap" style="padding: 16px 24px 24px; overflow-x: auto;">
                        <table class="table-grid-attendance" style="width: max-content; min-width: 100%; border-collapse: collapse; font-size: 11px; text-align: center; border: 1.5px solid #e2e8f0;">
                            <thead>
                                <tr style="background: #f8fafc; border-bottom: 1.5px solid #cbd5e1;">
                                    <th style="padding: 10px 8px; text-align: left; font-weight: bold; border-right: 1.5px solid #cbd5e1; font-size: 12px; width: 100px;">Month</th>
                                    <?php for ($d=1; $d<=31; $d++): ?>
                                    <th style="padding: 10px 4px; font-weight: bold; border-right: 1px solid #e2e8f0; width: 28px;"><?php echo $d; ?></th>
                                    <?php endfor; ?>
                                    <th style="padding: 10px 6px; font-weight: bold; border-right: 1px solid #e2e8f0; width: 50px;">Total</th>
                                    <th style="padding: 10px 6px; font-weight: bold; width: 60px;">Rate</th>
                                </tr>
                            </thead>
                            <tbody id="grid_attendance_body">
                                <!-- Injected dynamically -->
                            </tbody>
                        </table>
                    </div>
                </div><!-- end #report_details_view -->

            </div><!-- end .rep-content -->
        </div><!-- end page-wrapper -->
    </div><!-- end wrapper -->

    <!-- Hidden Printer-Friendly Layout (Consolidated Matrix) -->
    <div id="print_layout">
        <!-- 1. Consolidated Report Section -->
        <div id="print_consolidated_section">
            <div class="p-header">
                <h1>YMCA Badminton Club Poovathussery — Yearly Attendance Report</h1>
                <p>Financial Year: <strong id="pl_year">—</strong> &nbsp;|&nbsp; Generated: <span id="pl_date"></span></p>
            </div>
            <div class="p-legend">
                <span><span class="p-legend-dot"></span> Present (number = days attended that month)</span>
                <span style="color:#94a3b8;">— Not present that month</span>
            </div>
            <table class="p-matrix">
                <thead>
                    <tr>
                        <th style="text-align:left; width:35px;">#</th>
                        <th style="text-align:left; min-width:120px;">Member Name</th>
                        <th style="text-align:left; min-width:80px;">Group</th>
                        <th class="p-mth-head">Apr</th>
                        <th class="p-mth-head">May</th>
                        <th class="p-mth-head">Jun</th>
                        <th class="p-mth-head">Jul</th>
                        <th class="p-mth-head">Aug</th>
                        <th class="p-mth-head">Sep</th>
                        <th class="p-mth-head">Oct</th>
                        <th class="p-mth-head">Nov</th>
                        <th class="p-mth-head">Dec</th>
                        <th class="p-mth-head">Jan</th>
                        <th class="p-mth-head">Feb</th>
                        <th class="p-mth-head">Mar</th>
                        <th class="p-tot-head">Total<br>Months</th>
                        <th class="p-tot-head">Total<br>Days</th>
                    </tr>
                </thead>
                <tbody id="pl_tbody">
                    <!-- Injected by printConsolidatedReport() -->
                </tbody>
                <tfoot id="pl_tfoot" style="display:none;">
                    <tr>
                        <td colspan="3" style="text-align:left; font-weight:700;">TOTAL (all members)</td>
                        <td colspan="12"></td>
                        <td id="pl_foot_months" style="text-align:center; font-weight:800;">—</td>
                        <td id="pl_foot_days"   style="text-align:center; font-weight:800;">—</td>
                    </tr>
                </tfoot>
            </table>
        </div>

        <!-- 2. Individual Member Report Section -->
        <div id="print_individual_section" style="display:none;">
            <div class="p-header">
                <h1>YMCA Badminton Club Poovathussery</h1>
                <p>Individual Yearly Attendance Report &nbsp;|&nbsp; Financial Year: <strong id="print_year">—</strong></p>
            </div>
            
            <div style="display:flex; justify-content:space-between; border:1px solid #cbd5e1; border-radius:8px; padding:12px; margin-bottom:15px; background:#f8fafc; font-size:12px;">
                <div>
                    <h3 id="print_name" style="margin:0 0 6px 0; font-weight:700;">—</h3>
                    <p style="margin:0;">Group: <strong id="print_group">—</strong> &nbsp;|&nbsp; Email: <strong id="print_email">—</strong> &nbsp;|&nbsp; Phone: <strong id="print_phone">—</strong></p>
                </div>
                <div style="text-align:right;">
                    <p style="margin:0;">Yearly Rate: <strong id="print_pct">—</strong></p>
                    <p style="margin:3px 0 0 0;">Present: <strong id="print_present">—</strong> &nbsp;|&nbsp; Absent: <strong id="print_absent">—</strong> &nbsp;|&nbsp; Total Sessions: <strong id="print_total_sessions">—</strong></p>
                </div>
            </div>

            <table class="p-matrix" style="width:100%;">
                <thead>
                    <tr>
                        <th style="text-align:left; font-weight:bold; border: 1px solid #333; padding:6px; background:#f2f2f2; width:100px;">Month</th>
                        <?php for ($d=1; $d<=31; $d++): ?>
                        <th style="font-weight:bold; border: 1px solid #333; padding:6px; text-align:center; width:22px;"><?php echo $d; ?></th>
                        <?php endfor; ?>
                        <th style="font-weight:bold; border: 1px solid #333; padding:6px; text-align:center; width:45px;">Total</th>
                        <th style="font-weight:bold; border: 1px solid #333; padding:6px; text-align:center; width:50px;">Rate</th>
                    </tr>
                </thead>
                <tbody id="ind_tbody">
                    <!-- Injected by printReport() -->
                </tbody>
            </table>
        </div>
    </div>

    <!-- Scripts -->
    <script src="../js/jquery-3.1.1.min.js"></script>
    <script src="../js/bootstrap.min.js"></script>
    <script src="../js/plugins/metisMenu/jquery.metisMenu.js"></script>
    <script src="../js/plugins/slimscroll/jquery.slimscroll.min.js"></script>
    <script src="../js/inspinia.js"></script>
    <script src="../js/plugins/sweetalert/sweetalert.min.js"></script>
    <script src="../app_js/sweetalert-finez.js"></script>
    <script src="../app_pagination/pagination.js"></script>
    <script src="../app_menu/menu.js"></script>
    <script src="../js/loadingoverlay.min.js"></script>

    <script>
        $(document).ready(function() {
            // Populate Year Dropdown (Financial Years April to March)
            var currentYear = new Date().getFullYear();
            var currentMonth = new Date().getMonth(); // 0-indexed (3 is April)
            var defaultFinancialYear = (currentMonth < 3) ? currentYear - 1 : currentYear;
            
            var htmYear = "";
            for (var y = defaultFinancialYear + 1; y >= 2020; y--) {
                var nextY = y + 1;
                htmYear += '<option value="' + y + '">' + y + ' - ' + nextY + '</option>';
            }
            $('#select_year, #details_select_year').html(htmYear);
            $('#select_year, #details_select_year').val(defaultFinancialYear);

            loadMenu(true);
            <?php 
                include_once '../app_common/auth_helper.php';
                $lid = (int)$_SESSION['login_id'];
                if (isSuperAdmin($lid) || isGroupAdmin($lid) || isAttendanceMaster($lid)) { 
            ?>
                loadData(1);
            <?php } else { ?>
                viewYearlyReport(<?php echo $_SESSION['user_id'] ?? 0; ?>);
            <?php } ?>
        });

        // Clear all filter values
        function clearFilters() {
            $('#txt_search').val('');
            var currentYear = new Date().getFullYear();
            var currentMonth = new Date().getMonth();
            var defaultFinancialYear = (currentMonth < 3) ? currentYear - 1 : currentYear;
            $('#select_year').val(defaultFinancialYear);
            loadData(1);
        }

        // Load members list with pagination
        function loadData(page) {
            $('#hdn_current_page').val(page);
            console.log("Loading consolidated yearly attendance report, page: " + page);
            
            $.ajax({
                type: "POST",
                url: "api/yearly_attendance_report.php",
                data: {
                    action: 'load_yearly_consolidated_report',
                    year: $('#select_year').val(),
                    page: page,
                    search: $('#txt_search').val()
                },
                success: function(response) {
                    var data = JSON.parse(response);
                    var totalrows = data.total_rows;
                    var members = data.members;
                    var htm = "";

                    if (!members || members.length === 0) {
                        htm = '<tr><td colspan="17" class="rep-empty"><i class="fa fa-user-times"></i><br>No members with attendance found for the selected year.</td></tr>';
                        $('#tbl_members_body').html(htm);
                        $('#pagination_container').html('');
                        $('#lbl_found_count').text('0 members');
                        $('#tbl_members_foot').hide();
                        return;
                    }

                    var pageMonthsTotal = 0, pageDaysTotal = 0;

                    for (var i = 0; i < members.length; i++) {
                        var slno = ((page - 1) * 8) + (i + 1);
                        var m = members[i];
                        
                        var imgSrc = '../img/customer.png';
                        if (m.img && m.img != 0 && m.img != '0' && typeof m.img === 'string' && m.img.trim() !== '') {
                            imgSrc = '../image_upload/members/thumbnails/' + m.img;
                        }

                        var fullName = [m.first_name, m.middle_name, m.last_name]
                            .filter(function(p){ return p && p.trim(); }).join(' ');

                        htm += '<tr>';
                        htm += '<td><strong>' + slno + '</strong></td>';
                        
                        // Profile cell
                        htm += '<td>';
                        htm += '  <div class="member-profile-cell">';
                        htm += '    <img src="' + imgSrc + '" class="member-avatar" onerror="this.src=\'../img/customer.png\'">';
                        htm += '    <div>';
                        htm += '      <div class="member-name-text">' + fullName + '</div>';
                        htm += '      <div class="member-email-text">ID: #' + m.id + ' &nbsp;|&nbsp; <i class="fa fa-users" style="color:#6366f1;"></i> ' + (m.group_names ? m.group_names : 'No Group') + '</div>';
                        htm += '    </div>';
                        htm += '  </div>';
                        htm += '</td>';

                        // 12 Months matrix columns (Financial year order: Apr=4 ... Mar=3)
                        var monthsOrder = [4, 5, 6, 7, 8, 9, 10, 11, 12, 1, 2, 3];
                        for (var mIdx = 0; mIdx < monthsOrder.length; mIdx++) {
                            var mNum = monthsOrder[mIdx];
                            var days = m.months[mNum] ? parseInt(m.months[mNum]) : 0;
                            if (days > 0) {
                                htm += '<td class="month-cell"><span class="month-dot" title="' + days + ' day' + (days > 1 ? 's' : '') + ' present"><i class="fa fa-check"></i></span></td>';
                            } else {
                                htm += '<td class="month-cell" style="color:#e2e8f0; font-size:16px; font-weight:300;">—</td>';
                            }
                        }

                        var mths = parseInt(m.total_months_present);
                        var dys  = parseInt(m.total_days_present);
                        pageMonthsTotal += mths;
                        pageDaysTotal   += dys;

                        // Total Months & Days Present
                        htm += '<td class="total-cell">' + mths + '</td>';
                        htm += '<td class="total-cell">' + dys + '</td>';

                        // Action button
                        htm += '<td style="text-align: center;">';
                        htm += '  <button class="rep-btn rep-btn-outline btn-xs" type="button" onclick="viewYearlyReport(' + m.id + ')" title="View Detailed Calendar">';
                        htm += '    <i class="fa fa-bar-chart"></i> Details';
                        htm += '  </button>';
                        htm += '</td>';
                        htm += '</tr>';
                    }

                    $('#tbl_members_body').html(htm);
                    $('#lbl_found_count').text(totalrows + ' member' + (totalrows != 1 ? 's' : ''));
                    // Update footer totals
                    $('#foot_total_months').text(pageMonthsTotal);
                    $('#foot_total_days').text(pageDaysTotal);
                    $('#tbl_members_foot').show();
                    var htmpage = paginate(totalrows, page);
                    $('#pagination_container').html(htmpage);
                },
                error: function(xhr, status, error) {
                    console.log('AJAX Error loading members: ', status, error);
                }
            });
        }

        // View specific member's yearly report
        var currentMemberReportData = null; // Store globally for printing
        function viewYearlyReport(memberId) {
            var selectedYear = $('#select_year').val();
            console.log("Loading yearly summary for member ID " + memberId + ", year: " + selectedYear);
            
            // Show loading state
            $.LoadingOverlay("show");

            $.ajax({
                type: "POST",
                url: "api/yearly_attendance_report.php",
                data: {
                    action: 'get_yearly_summary',
                    member_id: memberId,
                    year: selectedYear
                },
                success: function(response) {
                    $.LoadingOverlay("hide");
                    var data = JSON.parse(response);
                    
                    if (data.error) {
                        swal("Error", data.error, "error");
                        return;
                    }

                    currentMemberReportData = data;
                    
                    // Populate Profile Card
                    var info = data.member_info;
                    var fullName = [info.first_name, info.middle_name, info.last_name]
                        .filter(function(p){ return p && p.trim(); }).join(' ');

                    var imgSrc = '../img/customer.png';
                    if (info.img && info.img != 0 && info.img != '0' && typeof info.img === 'string' && info.img.trim() !== '') {
                        imgSrc = '../image_upload/members/thumbnails/' + info.img;
                    }
                    $('#m_img').attr('src', imgSrc);
                    $('#m_name').text(fullName);
                    $('#m_group').text('Group: ' + (info.group_names ? info.group_names : 'None'));
                    $('#m_email').text(info.email ? info.email : '—');
                    $('#m_phone').text(info.phone ? info.phone : '—');
                    $('#details_year_label').text(selectedYear + ' - ' + (parseInt(selectedYear) + 1));

                    // Populate Summary Stats Card
                    var summary = data.summary;
                    $('#stat_pct').text(summary.percentage + '%');
                    $('#stat_sessions').text(summary.total_sessions);
                    $('#stat_present').text(summary.total_present);
                    $('#stat_absent').text(summary.total_absent);

                    // Populate Month-by-month details grid
                    var htmBreakdown = "";
                    var stats = data.monthly_stats;
                    
                    for (var i = 0; i < stats.length; i++) {
                        var m = stats[i];
                        var rate = m.percentage;
                        var rateClass = 'rate-zero';
                        if (m.sessions > 0) {
                            rateClass = rate >= 75 ? 'rate-high' : (rate >= 50 ? 'rate-medium' : 'rate-low');
                        }

                        htmBreakdown += '<tr style="border-bottom:1px solid #e2e8f0;">';
                        htmBreakdown += '  <td style="padding: 10px 8px; text-align: left; font-weight: 600; border-right: 1.5px solid #cbd5e1; background:#f8fafc; font-size:12px;">' + m.month_name + '</td>';
                        
                        // 31 days columns
                        for (var d = 1; d <= 31; d++) {
                            var status = m.days[d]; // 'P', 'A', '—', or ''
                            var cellClass = "";
                            var statusText = status;
                            
                            if (status === 'P') {
                                cellClass = 'cell-p';
                            } else if (status === 'A') {
                                cellClass = 'cell-a';
                            } else if (status === '—') {
                                cellClass = 'cell-empty-dash';
                            } else {
                                cellClass = 'cell-out-of-bounds';
                                statusText = '';
                            }
                            
                            htmBreakdown += '  <td class="' + cellClass + '" style="border-right: 1px solid #e2e8f0; font-weight:bold; font-size:12px;">' + statusText + '</td>';
                        }
                        
                        // Total present
                        htmBreakdown += '  <td class="grid-total-val" style="border-right: 1px solid #e2e8f0; font-size:12px;">' + m.present + '</td>';
                        
                        // Rate
                        var rateDisplay = m.sessions > 0 ? rate + '%' : '—';
                        htmBreakdown += '  <td><span class="grid-rate-val ' + rateClass + '" style="font-size:11.5px; display:inline-block; min-width:38px;">' + rateDisplay + '</span></td>';
                        htmBreakdown += '</tr>';
                    }

                    $('#grid_attendance_body').html(htmBreakdown);
                    $('#members_list_view').hide();
                    $('#report_details_view').show();
                },
                error: function(xhr, status, error) {
                    $.LoadingOverlay("hide");
                    console.log('AJAX error: ', status, error);
                    swal("Error", "Failed to fetch yearly summary.", "error");
                }
            });
        }

        // Show members list view and hide details
        function showMembersList() {
            $('#report_details_view').hide();
            $('#members_list_view').show();
        }

        // Print function
        function printReport() {
            if (!currentMemberReportData) return;

            var info = currentMemberReportData.member_info;
            var summary = currentMemberReportData.summary;
            var stats = currentMemberReportData.monthly_stats;
            
            var fullName = [info.first_name, info.middle_name, info.last_name]
                .filter(function(p){ return p && p.trim(); }).join(' ');

            var imgSrc = '../img/customer.png';
            if (info.img && info.img != 0 && info.img != '0' && typeof info.img === 'string' && info.img.trim() !== '') {
                imgSrc = '../image_upload/members/thumbnails/' + info.img;
            }

            // Populate Print Layout
            $('#print_year').text(summary.year + ' - ' + (parseInt(summary.year) + 1));
            $('#print_img').attr('src', imgSrc);
            $('#print_name').text(fullName);
            $('#print_group').text(info.group_names ? info.group_names : 'None');
            $('#print_email').text(info.email ? info.email : '—');
            $('#print_phone').text(info.phone ? info.phone : '—');
            
            $('#print_pct').text(summary.percentage + '%');
            $('#print_total_sessions').text(summary.total_sessions);
            $('#print_present').text(summary.total_present);
            $('#print_absent').text(summary.total_absent);

            // Populate Table
            var htmTable = "";
            for (var i = 0; i < stats.length; i++) {
                var m = stats[i];
                var rate = m.percentage;
                var rateClass = 'rate-zero';
                if (m.sessions > 0) {
                    rateClass = rate >= 75 ? 'rate-high' : (rate >= 50 ? 'rate-medium' : 'rate-low');
                }

                htmTable += '<tr>';
                htmTable += '  <td style="text-align: left; font-weight: bold; background:#f2f2f2; border: 1px solid #333;">' + m.month_name + '</td>';
                
                for (var d = 1; d <= 31; d++) {
                    var status = m.days[d]; // 'P', 'A', '—', or ''
                    var textClass = "";
                    var displayStatus = status;
                    if (status === 'P') {
                        textClass = 'color: #10b981; font-weight: bold;';
                    } else if (status === 'A') {
                        textClass = 'color: #ef4444; font-weight: bold;';
                    } else if (status === '—') {
                        textClass = 'color: #94a3b8;';
                    } else {
                        textClass = 'background: #f1f5f9;';
                        displayStatus = '';
                    }
                    htmTable += '  <td style="border: 1px solid #333; ' + textClass + '">' + displayStatus + '</td>';
                }
                
                htmTable += '  <td style="border: 1px solid #333; font-weight: bold;">' + m.present + '</td>';
                
                var rateDisplay = m.sessions > 0 ? rate + '%' : '—';
                htmTable += '  <td style="border: 1px solid #333; font-weight: bold;"><span class="' + rateClass + '" style="padding: 2px 6px; border-radius: 4px; display:inline-block; font-size:10px;">' + rateDisplay + '</span></td>';
                htmTable += '</tr>';
            }
            
            // Toggle sections for individual print
            $('#print_consolidated_section').hide();
            $('#print_individual_section').show();
            $('#ind_tbody').html(htmTable);
            
            // Trigger standard print window
            window.print();
        }

        // ---- Print CONSOLIDATED matrix report (all members for the selected year) ----
        function printConsolidatedReport() {
            var year   = $('#select_year').val();
            var search = $('#txt_search').val();
            var yearLabel = year + ' - ' + (parseInt(year) + 1);

            $.LoadingOverlay("show");

            $.ajax({
                type: "POST",
                url: "api/yearly_attendance_report.php",
                data: {
                    action: 'load_all_for_print',
                    year:   year,
                    search: search
                },
                success: function(response) {
                    $.LoadingOverlay("hide");
                    var data = JSON.parse(response);

                    if (data.error) {
                        swal("Error", data.error, "error");
                        return;
                    }

                    var members = data.members;
                    if (!members || members.length === 0) {
                        swal("No Data", "No members with attendance found for the selected year.", "info");
                        return;
                    }

                    // Toggle sections for consolidated print
                    $('#print_individual_section').hide();
                    $('#print_consolidated_section').show();

                    // Populate print header
                    var now = new Date();
                    var dateStr = now.getDate() + '/' + (now.getMonth() + 1) + '/' + now.getFullYear();
                    $('#pl_year').text(yearLabel);
                    $('#pl_date').text(dateStr);

                    // Build table rows
                    var htm = "";
                    var monthsOrder = [4, 5, 6, 7, 8, 9, 10, 11, 12, 1, 2, 3];
                    var grandMonths = 0, grandDays = 0;

                    for (var i = 0; i < members.length; i++) {
                        var m = members[i];
                        var fullName = [m.first_name, m.middle_name, m.last_name]
                            .filter(function(p){ return p && p.trim(); }).join(' ');

                        htm += '<tr>';
                        htm += '<td style="text-align:center;">' + (i + 1) + '</td>';
                        htm += '<td class="p-name-cell">' + fullName + '</td>';
                        htm += '<td class="p-name-cell" style="font-size:8px; color:#475569;">' + (m.group_names ? m.group_names : '—') + '</td>';

                        for (var mIdx = 0; mIdx < monthsOrder.length; mIdx++) {
                            var mNum  = monthsOrder[mIdx];
                            var days  = m.months[mNum] ? parseInt(m.months[mNum]) : 0;
                            if (days > 0) {
                                htm += '<td class="p-present">' + days + '</td>';
                            } else {
                                htm += '<td class="p-absent">\u2014</td>';
                            }
                        }

                        var mths = parseInt(m.total_months_present);
                        var dys  = parseInt(m.total_days_present);
                        grandMonths += mths;
                        grandDays   += dys;

                        htm += '<td class="p-total">' + mths + '</td>';
                        htm += '<td class="p-total">' + dys  + '</td>';
                        htm += '</tr>';
                    }

                    $('#pl_tbody').html(htm);
                    $('#pl_foot_months').text(grandMonths);
                    $('#pl_foot_days').text(grandDays);
                    $('#pl_tfoot').show();

                    // Hide overlay fully before triggering print
                    $.LoadingOverlay("hide");
                    $('.loadingoverlay').remove();
                    // Small delay so overlay teardown completes before print dialog opens
                    setTimeout(function() { window.print(); }, 400);
                },
                error: function(xhr, status, error) {
                    $.LoadingOverlay("hide");
                    swal("Error", "Failed to load print data. Please try again.", "error");
                }
            });
        }
    </script>
</body>
</html>
