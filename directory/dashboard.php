<?php
session_start();
session_write_close();
include_once '../app_common/db_connect.php';
include_once '../app_common/auth_helper.php';

if (empty($_SESSION['login_id'])) {
    header("Location: ../app_login_manager/logout.php");
    exit();
}

$login_id = (int)$_SESSION['login_id'];
if (!isSuperAdmin($login_id)) {
    if (isGroupAdmin($login_id)) {
        header("Location: group_dashboard.php");
    } else if (isAttendanceMaster($login_id)) {
        header("Location: attendance.php");
    } else {
        header("Location: member_dashboard.php");
    }
    exit();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="description" content="YMCA Admin Dashboard - Overview & Analytics">
    <link rel="shortcut icon" href="../favicon.ico" type="image/x-icon">
    <title>YMCA | Dashboard</title>

    <link href="../css/bootstrap.min.css" rel="stylesheet">
    <link href="../font-awesome/css/font-awesome.css" rel="stylesheet">
    <link href="../css/animate.css" rel="stylesheet">
    <link href="../css/style.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <script src="https://cdnjs.cloudflare.com/ajax/libs/jquery/3.6.0/jquery.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <script src="../app_js/sweetalert-finez.js"></script>
    <script src="../app_menu/menu.js"></script>

    <style>
        /* ===== DASHBOARD — MODERN REDESIGN ===== */
        *, *::before, *::after { box-sizing: border-box; }

        body, #wrapper {
            font-family: 'Inter', 'Segoe UI', sans-serif !important;
            background: #f0f4ff !important;
        }

        /* ---- Top Navbar ---- */
        .dash-topbar {
            background: #fff;
            border-bottom: 1px solid #e8edf5;
            padding: 0 28px;
            height: 62px;
            display: flex;
            align-items: center;
            justify-content: space-between;
            box-shadow: 0 1px 6px rgba(59,130,246,0.06);
            position: sticky;
            top: 0;
            z-index: 100;
        }
        .dash-topbar-left { display: flex; align-items: center; gap: 14px; }
        .dash-hamburger {
            width: 38px; height: 38px;
            background: linear-gradient(135deg, #3b82f6, #6366f1);
            border: none;
            border-radius: 10px;
            color: #fff;
            font-size: 15px;
            cursor: pointer;
            display: flex; align-items: center; justify-content: center;
            transition: opacity 0.2s;
        }
        .dash-hamburger:hover { opacity: 0.85; }
        .dash-topbar-title {
            font-size: 17px;
            font-weight: 700;
            color: #1e293b;
        }
        .dash-topbar-title span { color: #3b82f6; }
        .dash-logout {
            display: inline-flex; align-items: center; gap: 7px;
            padding: 8px 18px;
            background: #fff;
            border: 1.5px solid #e8edf5;
            border-radius: 10px;
            color: #64748b;
            font-size: 13.5px;
            font-weight: 500;
            text-decoration: none;
            transition: all 0.18s;
        }
        .dash-logout:hover {
            border-color: #3b82f6;
            color: #3b82f6;
            text-decoration: none;
        }

        /* ---- Page Content ---- */
        .dash-content { padding: 28px 30px; }

        /* ---- Welcome Banner ---- */
        .dash-welcome {
            background: linear-gradient(135deg, #1e3a8a 0%, #3b82f6 50%, #6366f1 100%);
            border-radius: 20px;
            padding: 28px 32px;
            margin-bottom: 28px;
            display: flex;
            align-items: center;
            justify-content: space-between;
            overflow: hidden;
            position: relative;
            box-shadow: 0 8px 32px rgba(59,130,246,0.28);
        }
        .dash-welcome::before {
            content: '';
            position: absolute;
            top: -60px; right: -60px;
            width: 220px; height: 220px;
            border-radius: 50%;
            background: rgba(255,255,255,0.06);
        }
        .dash-welcome::after {
            content: '';
            position: absolute;
            bottom: -40px; right: 120px;
            width: 140px; height: 140px;
            border-radius: 50%;
            background: rgba(255,255,255,0.04);
        }
        .dash-welcome-text h1 {
            font-size: 22px;
            font-weight: 800;
            color: #fff;
            margin: 0 0 6px;
        }
        .dash-welcome-text p {
            font-size: 14px;
            color: rgba(255,255,255,0.75);
            margin: 0;
        }
        .dash-welcome-date {
            background: rgba(255,255,255,0.12);
            border: 1px solid rgba(255,255,255,0.18);
            border-radius: 14px;
            padding: 12px 20px;
            text-align: center;
            color: #fff;
            flex-shrink: 0;
            backdrop-filter: blur(4px);
            z-index: 1;
        }
        .dash-welcome-date .date-day { font-size: 32px; font-weight: 800; line-height: 1; }
        .dash-welcome-date .date-month { font-size: 13px; font-weight: 500; opacity: 0.85; margin-top: 2px; }

        /* ---- KPI Cards Grid ---- */
        .dash-kpi-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(220px, 1fr));
            gap: 18px;
            margin-bottom: 28px;
        }
        .dash-kpi-card {
            background: #fff;
            border-radius: 18px;
            padding: 22px 22px 18px;
            display: flex;
            flex-direction: column;
            gap: 14px;
            box-shadow: 0 2px 12px rgba(0,0,0,0.05);
            border: 1px solid #e8edf5;
            transition: all 0.22s ease;
            position: relative;
            overflow: hidden;
        }
        .dash-kpi-card:hover {
            transform: translateY(-3px);
            box-shadow: 0 8px 28px rgba(59,130,246,0.12);
            border-color: #bfdbfe;
        }
        .dash-kpi-card::before {
            content: '';
            position: absolute;
            top: 0; left: 0; right: 0;
            height: 3px;
            border-radius: 18px 18px 0 0;
        }
        .dash-kpi-card.blue::before   { background: linear-gradient(90deg, #3b82f6, #60a5fa); }
        .dash-kpi-card.green::before  { background: linear-gradient(90deg, #10b981, #34d399); }
        .dash-kpi-card.purple::before { background: linear-gradient(90deg, #8b5cf6, #a78bfa); }
        .dash-kpi-card.amber::before  { background: linear-gradient(90deg, #f59e0b, #fbbf24); }
        .dash-kpi-card.red::before    { background: linear-gradient(90deg, #ef4444, #f87171); }
        .dash-kpi-card.teal::before   { background: linear-gradient(90deg, #14b8a6, #2dd4bf); }

        .dash-kpi-top { display: flex; align-items: flex-start; justify-content: space-between; }
        .dash-kpi-icon {
            width: 48px; height: 48px;
            border-radius: 14px;
            display: flex; align-items: center; justify-content: center;
            font-size: 20px;
            color: #fff;
            flex-shrink: 0;
        }
        .dash-kpi-card.blue   .dash-kpi-icon { background: linear-gradient(135deg,#3b82f6,#1d4ed8); box-shadow: 0 4px 14px rgba(59,130,246,0.3); }
        .dash-kpi-card.green  .dash-kpi-icon { background: linear-gradient(135deg,#10b981,#059669); box-shadow: 0 4px 14px rgba(16,185,129,0.3); }
        .dash-kpi-card.purple .dash-kpi-icon { background: linear-gradient(135deg,#8b5cf6,#6d28d9); box-shadow: 0 4px 14px rgba(139,92,246,0.3); }
        .dash-kpi-card.amber  .dash-kpi-icon { background: linear-gradient(135deg,#f59e0b,#d97706); box-shadow: 0 4px 14px rgba(245,158,11,0.3); }
        .dash-kpi-card.red    .dash-kpi-icon { background: linear-gradient(135deg,#ef4444,#dc2626); box-shadow: 0 4px 14px rgba(239,68,68,0.3); }
        .dash-kpi-card.teal   .dash-kpi-icon { background: linear-gradient(135deg,#14b8a6,#0d9488); box-shadow: 0 4px 14px rgba(20,184,166,0.3); }

        .dash-kpi-value {
            font-size: 28px;
            font-weight: 800;
            color: #1e293b;
            line-height: 1;
            letter-spacing: -0.5px;
        }
        .dash-kpi-label {
            font-size: 12.5px;
            font-weight: 500;
            color: #94a3b8;
            text-transform: uppercase;
            letter-spacing: 0.05em;
        }
        .dash-kpi-trend {
            display: inline-flex; align-items: center; gap: 4px;
            font-size: 12px;
            font-weight: 600;
            padding: 3px 8px;
            border-radius: 20px;
        }
        .dash-kpi-trend.up   { background: #dcfce7; color: #16a34a; }
        .dash-kpi-trend.info { background: #eff6ff; color: #3b82f6; }
        .dash-kpi-trend.warn { background: #fef9c3; color: #ca8a04; }

        /* ---- Charts Row ---- */
        .dash-charts-row {
            display: grid;
            grid-template-columns: 1fr 340px;
            gap: 20px;
            margin-bottom: 28px;
        }
        @media (max-width: 992px) {
            .dash-charts-row { grid-template-columns: 1fr; }
        }

        .dash-card {
            background: #fff;
            border-radius: 18px;
            border: 1px solid #e8edf5;
            box-shadow: 0 2px 12px rgba(0,0,0,0.05);
            overflow: hidden;
        }
        .dash-card-header {
            padding: 18px 22px 14px;
            border-bottom: 1px solid #f1f5f9;
            display: flex;
            align-items: center;
            justify-content: space-between;
        }
        .dash-card-title {
            font-size: 15px;
            font-weight: 700;
            color: #1e293b;
            display: flex;
            align-items: center;
            gap: 9px;
            margin: 0;
        }
        .dash-card-title i {
            width: 32px; height: 32px;
            border-radius: 9px;
            background: linear-gradient(135deg,#3b82f6,#6366f1);
            color: #fff;
            display: flex; align-items: center; justify-content: center;
            font-size: 14px;
        }
        .dash-card-body { padding: 20px 22px; }
        .dash-chart-wrap { height: 280px; position: relative; }

        /* ---- Quick Actions ---- */
        .quick-action-list { display: flex; flex-direction: column; gap: 10px; }
        .quick-action-btn {
            display: flex;
            align-items: center;
            gap: 13px;
            padding: 13px 16px;
            border-radius: 13px;
            border: 1.5px solid #e8edf5;
            background: #fafbff;
            text-decoration: none;
            color: #1e293b;
            font-size: 14px;
            font-weight: 500;
            transition: all 0.18s ease;
        }
        .quick-action-btn:hover {
            border-color: #3b82f6;
            background: #eff6ff;
            color: #1e40af;
            text-decoration: none;
            transform: translateX(3px);
        }
        .quick-action-btn .qa-icon {
            width: 38px; height: 38px;
            border-radius: 10px;
            display: flex; align-items: center; justify-content: center;
            font-size: 16px;
            color: #fff;
            flex-shrink: 0;
        }
        .quick-action-btn:nth-child(1) .qa-icon { background: linear-gradient(135deg,#3b82f6,#6366f1); }
        .quick-action-btn:nth-child(2) .qa-icon { background: linear-gradient(135deg,#10b981,#059669); }
        .quick-action-btn:nth-child(3) .qa-icon { background: linear-gradient(135deg,#f59e0b,#d97706); }
        .quick-action-btn:nth-child(4) .qa-icon { background: linear-gradient(135deg,#8b5cf6,#6d28d9); }
        .qa-chevron { margin-left: auto; color: #cbd5e1; font-size: 13px; transition: transform 0.18s; }
        .quick-action-btn:hover .qa-chevron { transform: translateX(3px); color: #3b82f6; }

        /* ---- Bottom Row ---- */
        .dash-bottom-row {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 20px;
        }
        @media (max-width: 768px) {
            .dash-bottom-row { grid-template-columns: 1fr; }
        }

        /* Donut chart wrapper */
        .dash-donut-wrap {
            height: 220px;
            position: relative;
            display: flex;
            align-items: center;
            justify-content: center;
        }
        .dash-donut-legend {
            display: flex;
            flex-direction: column;
            gap: 10px;
            margin-top: 16px;
        }
        .dash-legend-item {
            display: flex;
            align-items: center;
            gap: 9px;
            font-size: 13px;
            color: #475569;
            font-weight: 500;
        }
        .dash-legend-dot {
            width: 11px; height: 11px;
            border-radius: 3px;
            flex-shrink: 0;
        }
        .dash-legend-item .legend-val {
            margin-left: auto;
            font-weight: 700;
            color: #1e293b;
        }

        /* Animated count-up */
        .dash-kpi-value { transition: all 0.3s; }

        /* Responsive */
        @media (max-width: 600px) {
            .dash-content { padding: 14px; }
            .dash-welcome { padding: 20px; }
            .dash-welcome-date { display: none; }
            .dash-kpi-grid { grid-template-columns: 1fr 1fr; gap: 12px; }
        }
    </style>
</head>

<body>
<div id="wrapper">

    <!-- Sidebar -->
    <nav class="navbar-default navbar-static-side" role="navigation">
        <div class="dropdown profile-element">
            <center>
                <span><img alt="image" class="img-circle" src="../img/customer.png" style="padding-top: 20px;"/></span>
                <span class="clear">
                    <span class="block m-t-xs">
                        <strong class="font-bold"><?php echo htmlspecialchars($_SESSION['name']); ?></strong>
                    </span>
                </span>
            </center>
        </div>
        <div class="sidebar-collapse" id="divMenuContainer"></div>
    </nav>

    <!-- Main Page -->
    <div id="page-wrapper" style="background:#f0f4ff; padding:0; min-height:100vh;">

        <!-- Top Navbar -->
        <div class="dash-topbar">
            <div class="dash-topbar-left">
                <a class="navbar-minimalize minimalize-styl-2 dash-hamburger" href="#"><i class="fa fa-bars"></i></a>
                <span class="dash-topbar-title">YMCA <span>Admin</span></span>
            </div>
            <div class="dash-topbar-right" style="display: flex; align-items: center; gap: 14px;">
                <!-- Month Selection Filter -->
                <div class="dash-month-selector" style="display: flex; align-items: center; gap: 8px;">
                    <label for="dashboard-month" style="margin: 0; font-size: 13px; font-weight: 600; color: #64748b; display: flex; align-items: center; gap: 6px;">
                        <i class="fa fa-calendar" style="color: #3b82f6;"></i> Month:
                    </label>
                    <input type="month" id="dashboard-month" class="form-control" style="width: auto; display: inline-block; padding: 6px 12px; height: 38px; border-radius: 10px; border: 1.5px solid #e8edf5; font-size: 13.5px; font-weight: 500; color: #1e293b; background: #fff; cursor: pointer; outline: none; transition: border-color 0.2s;" onchange="filterDashboard()">
                </div>
                <a href="../app_login_manager/logout.php" class="dash-logout">
                    <i class="fa fa-sign-out"></i> Log out
                </a>
            </div>
        </div>

        <!-- Dashboard Content -->
        <div class="dash-content">

            <!-- Welcome Banner -->
            <div class="dash-welcome">
                <div class="dash-welcome-text">
                    <h1>Welcome back, <?php echo htmlspecialchars($_SESSION['name']); ?>! 👋</h1>
                    <p id="welcome-sub">Here's what's happening at YMCA today. All stats are live.</p>
                </div>
                <div style="display:flex; align-items:center; gap:12px; z-index:1;">
                    <a href="api/download_backup.php" class="btn" style="background:rgba(255,255,255,0.18); border:1.5px solid rgba(255,255,255,0.35); border-radius:12px; color:#ffffff; padding:10px 18px; font-weight:800; font-size:13.5px; display:inline-flex; align-items:center; gap:8px; backdrop-filter:blur(4px); text-decoration:none; transition:all 0.2s;" onmouseover="this.style.background='rgba(255,255,255,0.3)'" onmouseout="this.style.background='rgba(255,255,255,0.18)'">
                        <i class="fa fa-database" style="font-size:16px;"></i> Download Backup
                    </a>
                    <div class="dash-welcome-date">
                        <div class="date-day" id="welcome-day">—</div>
                        <div class="date-month" id="welcome-month">—</div>
                    </div>
                </div>
            </div>

            <!-- KPI Cards -->
            <div class="dash-kpi-grid">
                <div class="dash-kpi-card blue">
                    <div class="dash-kpi-top">
                        <div>
                            <div class="dash-kpi-label">Total Members</div>
                            <div class="dash-kpi-value" id="stat_members">—</div>
                        </div>
                        <div class="dash-kpi-icon"><i class="fa fa-users"></i></div>
                    </div>
                    <div><span class="dash-kpi-trend info"><i class="fa fa-circle"></i> Active</span></div>
                </div>

                <div class="dash-kpi-card purple">
                    <div class="dash-kpi-top">
                        <div>
                            <div class="dash-kpi-label">Active Groups</div>
                            <div class="dash-kpi-value" id="stat_groups">—</div>
                        </div>
                        <div class="dash-kpi-icon"><i class="fa fa-object-group"></i></div>
                    </div>
                    <div><span class="dash-kpi-trend info"><i class="fa fa-circle"></i> Running</span></div>
                </div>

                <div class="dash-kpi-card amber">
                    <div class="dash-kpi-top">
                        <div>
                            <div class="dash-kpi-label">Total Collected</div>
                            <div class="dash-kpi-value" id="stat_received">—</div>
                        </div>
                        <div class="dash-kpi-icon"><i class="fa fa-inr"></i></div>
                    </div>
                    <div><span class="dash-kpi-trend up"><i class="fa fa-arrow-up"></i> Revenue</span></div>
                </div>

                <div class="dash-kpi-card red">
                    <div class="dash-kpi-top">
                        <div>
                            <div class="dash-kpi-label">Pending Dues</div>
                            <div class="dash-kpi-value" id="stat_pending">—</div>
                        </div>
                        <div class="dash-kpi-icon"><i class="fa fa-exclamation-circle"></i></div>
                    </div>
                    <div><span class="dash-kpi-trend warn"><i class="fa fa-clock-o"></i> Overdue</span></div>
                </div>
            </div>

            <!-- Charts Row -->
            <div class="dash-charts-row">
                <!-- Bar Chart -->
                <div class="dash-card">
                    <div class="dash-card-header">
                        <h2 class="dash-card-title">
                            <i class="fa fa-bar-chart"></i>
                            Collections vs Dues
                        </h2>
                        <span style="font-size:12px; color:#94a3b8; font-weight:500;">Last 6 Months</span>
                    </div>
                    <div class="dash-card-body">
                        <div class="dash-chart-wrap">
                            <canvas id="chartCollections"></canvas>
                        </div>
                    </div>
                </div>

                <!-- Quick Actions -->
                <div class="dash-card">
                    <div class="dash-card-header">
                        <h2 class="dash-card-title">
                            <i class="fa fa-bolt"></i>
                            Quick Actions
                        </h2>
                    </div>
                    <div class="dash-card-body">
                        <div class="quick-action-list">
                            <a href="members.php" class="quick-action-btn">
                                <span class="qa-icon"><i class="fa fa-users"></i></span>
                                <span>Manage Members</span>
                                <i class="fa fa-chevron-right qa-chevron"></i>
                            </a>
                            <a href="member_fees.php" class="quick-action-btn">
                                <span class="qa-icon"><i class="fa fa-credit-card"></i></span>
                                <span>Fees Collection</span>
                                <i class="fa fa-chevron-right qa-chevron"></i>
                            </a>
                            <a href="attendance.php" class="quick-action-btn">
                                <span class="qa-icon"><i class="fa fa-calendar-check-o"></i></span>
                                <span>Mark Attendance</span>
                                <i class="fa fa-chevron-right qa-chevron"></i>
                            </a>
                            <a href="reports.php" class="quick-action-btn">
                                <span class="qa-icon"><i class="fa fa-file-pdf-o"></i></span>
                                <span>Reports & Cash Book</span>
                                <i class="fa fa-chevron-right qa-chevron"></i>
                            </a>
                            <a href="api/download_backup.php" class="quick-action-btn" style="background:#eff6ff; border-color:#93c5fd;">
                                <span class="qa-icon" style="background:#3b82f6; color:#fff;"><i class="fa fa-database"></i></span>
                                <span style="color:#1d4ed8; font-weight:800;">Download Database Backup</span>
                                <i class="fa fa-download qa-chevron" style="color:#1d4ed8;"></i>
                            </a>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Bottom Row: Donut + Collection Summary -->
            <div class="dash-bottom-row">

                <!-- Financial Summary Donut -->
                <div class="dash-card">
                    <div class="dash-card-header">
                        <h2 class="dash-card-title">
                            <i class="fa fa-pie-chart"></i>
                            Financial Overview
                        </h2>
                    </div>
                    <div class="dash-card-body">
                        <div class="dash-donut-wrap">
                            <canvas id="chartDonut"></canvas>
                        </div>
                        <div class="dash-donut-legend">
                            <div class="dash-legend-item">
                                <div class="dash-legend-dot" style="background:#3b82f6;"></div>
                                Collected
                                <span class="legend-val" id="leg_received">—</span>
                            </div>
                            <div class="dash-legend-item">
                                <div class="dash-legend-dot" style="background:#f87171;"></div>
                                Pending Dues
                                <span class="legend-val" id="leg_pending">—</span>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Monthly Trend Line -->
                <div class="dash-card">
                    <div class="dash-card-header">
                        <h2 class="dash-card-title">
                            <i class="fa fa-line-chart"></i>
                            Collection Trend
                        </h2>
                        <span style="font-size:12px; color:#94a3b8; font-weight:500;">6-Month Line</span>
                    </div>
                    <div class="dash-card-body">
                        <div class="dash-chart-wrap">
                            <canvas id="chartTrend"></canvas>
                        </div>
                    </div>
                </div>
            </div>

        </div><!-- end dash-content -->
    </div><!-- end page-wrapper -->
</div><!-- end wrapper -->

<!-- Scripts -->
<script src="../js/jquery-3.1.1.min.js"></script>
<script src="../js/bootstrap.min.js"></script>
<script src="../js/plugins/metisMenu/jquery.metisMenu.js"></script>
<script src="../js/plugins/slimscroll/jquery.slimscroll.min.js"></script>
<script src="../js/inspinia.js"></script>

<script>
// Set welcome date
(function() {
    var now = new Date();
    var days = ['Sunday','Monday','Tuesday','Wednesday','Thursday','Friday','Saturday'];
    var months = ['January','February','March','April','May','June','July','August','September','October','November','December'];
    document.getElementById('welcome-day').textContent = now.getDate();
    document.getElementById('welcome-month').textContent = months[now.getMonth()] + ' ' + now.getFullYear();
})();

// Animated counter
function animateCount(el, target, prefix, decimals) {
    var start = 0;
    var duration = 900;
    var step = target / (duration / 16);
    var current = 0;
    var timer = setInterval(function() {
        current = Math.min(current + step, target);
        if (decimals) {
            el.textContent = prefix + Math.floor(current).toLocaleString('en-IN');
        } else {
            el.textContent = Math.floor(current).toLocaleString('en-IN');
        }
        if (current >= target) clearInterval(timer);
    }, 16);
}

var chartCollectionsObj = null;
var chartDonutObj = null;
var chartTrendObj = null;

function loadDashboard(monthVal) {
    if (monthVal) {
        var dateParts = monthVal.split('-');
        var year = dateParts[0];
        var monthNames = ["January", "February", "March", "April", "May", "June", "July", "August", "September", "October", "November", "December"];
        var monthName = monthNames[parseInt(dateParts[1], 10) - 1];

        // Update titles to reflect selected month
        $('.dash-kpi-card.amber .dash-kpi-label').text('Collected in ' + monthName);
        $('.dash-kpi-card.amber .dash-kpi-trend').html('<i class="fa fa-arrow-up"></i> Month Revenue');
        
        $('.dash-kpi-card.red .dash-kpi-label').text('Dues for ' + monthName);
        $('.dash-kpi-card.red .dash-kpi-trend').html('<i class="fa fa-clock-o"></i> Month Overdue');

        document.getElementById('welcome-sub').innerHTML = "Showing stats for the month of <strong>" + monthName + " " + year + "</strong>. Clear the calendar to view overall stats.";
    } else {
        // Reset to original labels
        $('.dash-kpi-card.amber .dash-kpi-label').text('Total Collected');
        $('.dash-kpi-card.amber .dash-kpi-trend').html('<i class="fa fa-arrow-up"></i> Revenue');
        
        $('.dash-kpi-card.red .dash-kpi-label').text('Pending Dues');
        $('.dash-kpi-card.red .dash-kpi-trend').html('<i class="fa fa-clock-o"></i> Overdue');

        document.getElementById('welcome-sub').innerHTML = "Here's what's happening at YMCA today. All stats are live.";
    }

    $.ajax({
        type: "POST",
        url: "api/dashboard.php",
        data: { 
            action: 'load_dashboard_data',
            month: monthVal || ''
        },
        success: function(response) {
            try {
                var data = typeof response === 'object' ? response : JSON.parse(response);

                // Animate KPIs
                animateCount(document.getElementById('stat_members'),    data.members,    '', false);
                animateCount(document.getElementById('stat_groups'),     data.groups,     '', false);
                animateCount(document.getElementById('stat_received'),   data.received,   '₹', true);
                animateCount(document.getElementById('stat_pending'),    data.pending,    '₹', true);

                // Legend values
                document.getElementById('leg_received').textContent = '₹' + data.received.toLocaleString('en-IN');
                document.getElementById('leg_pending').textContent  = '₹' + data.pending.toLocaleString('en-IN');

                // Prepare chart data
                var labels       = data.chartData.map(function(d){ return d.month; });
                var receivedArr  = data.chartData.map(function(d){ return d.received; });
                var receivableArr= data.chartData.map(function(d){ return d.receivable; });

                var fontFamily = "'Inter', 'Segoe UI', sans-serif";

                // ---- Bar Chart: Collections vs Dues ----
                var barCtx = document.getElementById('chartCollections').getContext('2d');
                if (chartCollectionsObj) {
                    chartCollectionsObj.destroy();
                }
                chartCollectionsObj = new Chart(barCtx, {
                    type: 'bar',
                    data: {
                        labels: labels,
                        datasets: [
                            {
                                label: 'Collected (₹)',
                                data: receivedArr,
                                backgroundColor: 'rgba(59,130,246,0.85)',
                                borderRadius: 7,
                                borderSkipped: false
                            },
                            {
                                label: 'Expected (₹)',
                                data: receivableArr,
                                backgroundColor: 'rgba(226,232,240,0.9)',
                                borderRadius: 7,
                                borderSkipped: false
                            }
                        ]
                    },
                    options: {
                        responsive: true,
                        maintainAspectRatio: false,
                        plugins: {
                            legend: { position: 'top', labels: { font: { family: fontFamily, size: 12 }, padding: 16 } },
                            tooltip: { callbacks: { label: function(c){ return ' ₹' + c.raw.toLocaleString('en-IN'); } } }
                        },
                        scales: {
                            y: {
                                beginAtZero: true,
                                grid: { color: '#f1f5f9' },
                                ticks: { font: { family: fontFamily }, callback: function(v){ return '₹' + v.toLocaleString('en-IN'); } }
                            },
                            x: { grid: { display: false }, ticks: { font: { family: fontFamily } } }
                        }
                    }
                });

                // ---- Donut Chart: Financial Overview ----
                var donutCtx = document.getElementById('chartDonut').getContext('2d');
                if (chartDonutObj) {
                    chartDonutObj.destroy();
                }
                chartDonutObj = new Chart(donutCtx, {
                    type: 'doughnut',
                    data: {
                        labels: ['Collected', 'Pending Dues'],
                        datasets: [{
                            data: [data.received, data.pending],
                            backgroundColor: ['#3b82f6', '#f87171'],
                            hoverBackgroundColor: ['#2563eb', '#ef4444'],
                            borderWidth: 0,
                            borderRadius: 4
                        }]
                    },
                    options: {
                        responsive: true,
                        maintainAspectRatio: false,
                        cutout: '68%',
                        plugins: {
                            legend: { display: false },
                            tooltip: { callbacks: { label: function(c){ return ' ₹' + c.raw.toLocaleString('en-IN'); } } }
                        }
                    }
                });

                // ---- Line Chart: Trend ----
                var lineCtx = document.getElementById('chartTrend').getContext('2d');
                var gradientBlue = lineCtx.createLinearGradient(0, 0, 0, 260);
                gradientBlue.addColorStop(0, 'rgba(59,130,246,0.2)');
                gradientBlue.addColorStop(1, 'rgba(59,130,246,0)');

                if (chartTrendObj) {
                    chartTrendObj.destroy();
                }
                chartTrendObj = new Chart(lineCtx, {
                    type: 'line',
                    data: {
                        labels: labels,
                        datasets: [{
                            label: 'Collected (₹)',
                            data: receivedArr,
                            borderColor: '#3b82f6',
                            backgroundColor: gradientBlue,
                            borderWidth: 2.5,
                            pointBackgroundColor: '#3b82f6',
                            pointRadius: 4,
                            pointHoverRadius: 6,
                            fill: true,
                            tension: 0.4
                        }]
                    },
                    options: {
                        responsive: true,
                        maintainAspectRatio: false,
                        plugins: {
                            legend: { display: false },
                            tooltip: { callbacks: { label: function(c){ return ' ₹' + c.raw.toLocaleString('en-IN'); } } }
                        },
                        scales: {
                            y: {
                                beginAtZero: true,
                                grid: { color: '#f1f5f9' },
                                ticks: { font: { family: fontFamily }, callback: function(v){ return '₹' + v.toLocaleString('en-IN'); } }
                            },
                            x: { grid: { display: false }, ticks: { font: { family: fontFamily } } }
                        }
                    }
                });
            } catch (e) {
                console.error("Dashboard success processing error:", e);
                var errDiv = document.createElement('div');
                errDiv.style.position = 'fixed';
                errDiv.style.top = '10px';
                errDiv.style.left = '10px';
                errDiv.style.right = '10px';
                errDiv.style.background = '#fee2e2';
                errDiv.style.color = '#991b1b';
                errDiv.style.padding = '15px';
                errDiv.style.border = '1px solid #f87171';
                errDiv.style.borderRadius = '8px';
                errDiv.style.zIndex = '99999';
                errDiv.innerHTML = '<strong>Dashboard JS Processing Error:</strong> ' + e.message + '<br><small>Response: ' + (typeof response === 'string' ? response.substring(0, 300) : '[Object]') + '</small>';
                document.body.appendChild(errDiv);
            }
        },
        error: function(xhr, status, error) {
            console.error("Dashboard data HTTP error:", error);
            var errDiv = document.createElement('div');
            errDiv.style.position = 'fixed';
            errDiv.style.top = '10px';
            errDiv.style.left = '10px';
            errDiv.style.right = '10px';
            errDiv.style.background = '#fee2e2';
            errDiv.style.color = '#991b1b';
            errDiv.style.padding = '15px';
            errDiv.style.border = '1px solid #f87171';
            errDiv.style.borderRadius = '8px';
            errDiv.style.zIndex = '99999';
            errDiv.innerHTML = '<strong>Dashboard HTTP Error:</strong> Status ' + xhr.status + ' (' + error + ')<br><small>Response text: ' + (xhr.responseText ? xhr.responseText.substring(0, 300) : 'none') + '</small>';
            document.body.appendChild(errDiv);
        }
    });
}

function filterDashboard() {
    var monthVal = document.getElementById('dashboard-month').value;
    loadDashboard(monthVal);
}

$(document).ready(function() {
    loadMenu();
    loadDashboard('');
});
</script>

</body>
</html>
