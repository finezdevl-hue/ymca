<?php
session_start();
include '../app_common/db_connect.php';
include '../app_common/auth_helper.php';

if (empty($_SESSION['login_id'])) {
    header("Location: ../index.php");
    exit();
}

$login_id = (int)$_SESSION['login_id'];
if (!isSuperAdmin($login_id) && !isGroupAdmin($login_id)) {
    header("Location: user_attendance.php");
    exit();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Group Admin Dashboard - YMCA</title>

    <!-- FontAwesome & Google Fonts -->
    <link href="../font-awesome/css/font-awesome.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&display=swap" rel="stylesheet">

    <!-- Framework Styles -->
    <link href="../css/bootstrap.min.css" rel="stylesheet">
    <link href="../css/animate.css" rel="stylesheet">
    <link href="../css/style.css" rel="stylesheet">
    
    <!-- Modern Dark Mode Core -->
    <link href="../css/modern_dark_mode.css" rel="stylesheet">
    <script src="../js/modern_dark_mode.js"></script>

    <style>
        :root {
            --brand-primary: #2563eb;
            --brand-gradient: linear-gradient(135deg, #1e3a8a 0%, #2563eb 50%, #3b82f6 100%);
            --card-bg: #ffffff;
            --border-color: #e2e8f0;
            --text-main: #0f172a;
            --text-sub: #64748b;
        }

        body {
            font-family: 'Plus Jakarta Sans', sans-serif;
            background-color: #f1f5f9;
            color: var(--text-main);
            min-height: 100vh;
        }

        /* Dark Mode Overrides */
        body.dark-mode {
            background-color: #0b1329 !important;
            color: #f8fafc !important;
        }
        body.dark-mode #page-wrapper {
            background-color: #0b1329 !important;
        }
        body.dark-mode .hero-card {
            background: linear-gradient(135deg, #1e1b4b 0%, #311b92 50%, #1e293b 100%) !important;
            border-color: rgba(255, 255, 255, 0.1) !important;
        }
        body.dark-mode .stat-card-item,
        body.dark-mode .action-card-item,
        body.dark-mode .panel-card {
            background: #111c3a !important;
            border-color: #1e293b !important;
            color: #f8fafc !important;
        }

        .dashboard-wrapper {
            padding: 30px 40px;
            max-width: 1400px;
            margin: 0 auto;
        }

        /* Hero Card Header */
        .hero-card {
            background: linear-gradient(135deg, #0f172a 0%, #1e293b 50%, #1d4ed8 100%);
            border-radius: 24px;
            padding: 36px 40px;
            color: #ffffff;
            margin-bottom: 30px;
            box-shadow: 0 20px 40px rgba(15, 23, 42, 0.15);
            display: flex;
            justify-content: space-between;
            align-items: center;
            flex-wrap: wrap;
            gap: 24px;
            position: relative;
            overflow: hidden;
        }

        .hero-card::before {
            content: '';
            position: absolute;
            top: -40%;
            right: -10%;
            width: 380px;
            height: 380px;
            background: radial-gradient(circle, rgba(59, 130, 246, 0.35) 0%, rgba(255, 255, 255, 0) 70%);
            pointer-events: none;
        }

        .hero-welcome-badge {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            background: rgba(255, 255, 255, 0.12);
            backdrop-filter: blur(10px);
            padding: 6px 14px;
            border-radius: 20px;
            font-size: 12px;
            font-weight: 700;
            letter-spacing: 0.5px;
            text-transform: uppercase;
            margin-bottom: 12px;
            border: 1px solid rgba(255, 255, 255, 0.2);
        }

        .hero-title-text h1 {
            margin: 0;
            font-weight: 800;
            font-size: 30px;
            letter-spacing: -0.5px;
            line-height: 1.2;
        }

        .hero-title-text p {
            margin: 8px 0 0 0;
            color: #94a3b8;
            font-size: 14px;
            font-weight: 500;
        }

        .group-select-wrap {
            position: relative;
        }

        .group-select-box {
            background: rgba(255, 255, 255, 0.12);
            border: 1.5px solid rgba(255, 255, 255, 0.25);
            backdrop-filter: blur(12px);
            color: #ffffff;
            height: 52px;
            border-radius: 16px;
            padding: 0 20px;
            font-size: 15px;
            font-weight: 800;
            outline: none;
            cursor: pointer;
            min-width: 240px;
            transition: all 0.25s ease;
        }

        .group-select-box option {
            color: #0f172a;
            background: #ffffff;
            font-weight: 700;
        }

        .group-select-box:focus {
            border-color: #60a5fa;
            background: rgba(255, 255, 255, 0.2);
            box-shadow: 0 0 0 4px rgba(96, 165, 250, 0.25);
        }

        /* Stat Grid */
        .stat-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(230px, 1fr));
            gap: 20px;
            margin-bottom: 30px;
        }

        .stat-card-item {
            background: #ffffff;
            border: 1px solid #e2e8f0;
            border-radius: 20px;
            padding: 24px;
            display: flex;
            flex-direction: column;
            justify-content: space-between;
            transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
            box-shadow: 0 4px 16px rgba(0, 0, 0, 0.02);
            position: relative;
            overflow: hidden;
        }

        .stat-card-item:hover {
            transform: translateY(-4px);
            box-shadow: 0 12px 28px rgba(15, 23, 42, 0.08);
            border-color: #cbd5e1;
        }

        .stat-card-top {
            display: flex;
            align-items: center;
            justify-content: space-between;
            margin-bottom: 16px;
        }

        .stat-icon-wrap {
            width: 52px;
            height: 52px;
            border-radius: 16px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 22px;
            flex-shrink: 0;
        }

        .icon-blue { background: #eff6ff; color: #2563eb; }
        .icon-emerald { background: #ecfdf5; color: #10b981; }
        .icon-purple { background: #f3e8ff; color: #7c3aed; }
        .icon-amber { background: #fffbeb; color: #d97706; }
        .icon-rose { background: #ffe4e6; color: #e11d48; }

        .stat-meta-title {
            font-size: 12px;
            font-weight: 700;
            color: var(--text-sub);
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }

        .stat-big-val {
            font-size: 26px;
            font-weight: 800;
            color: var(--text-main);
            margin-top: 4px;
            line-height: 1;
        }

        .stat-progress-bar {
            height: 6px;
            border-radius: 10px;
            background: #e2e8f0;
            margin-top: 14px;
            overflow: hidden;
        }

        .stat-progress-fill {
            height: 100%;
            border-radius: 10px;
            background: linear-gradient(90deg, #10b981, #059669);
            width: 0%;
            transition: width 0.6s ease;
        }

        /* Quick Action Section */
        .section-header-title {
            font-size: 20px;
            font-weight: 800;
            color: var(--text-main);
            margin-bottom: 18px;
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .actions-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(240px, 1fr));
            gap: 20px;
            margin-bottom: 35px;
        }

        .action-card-item {
            background: #ffffff;
            border: 1px solid #e2e8f0;
            border-radius: 20px;
            padding: 26px;
            transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
            text-decoration: none;
            color: inherit;
            display: flex;
            flex-direction: column;
            align-items: flex-start;
            box-shadow: 0 4px 16px rgba(0, 0, 0, 0.02);
        }

        .action-card-item:hover {
            transform: translateY(-4px);
            border-color: #2563eb;
            box-shadow: 0 16px 32px rgba(37, 99, 235, 0.12);
            text-decoration: none;
            color: inherit;
        }

        .action-icon-circle {
            width: 54px;
            height: 54px;
            border-radius: 16px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 24px;
            margin-bottom: 16px;
            transition: transform 0.3s ease;
        }

        .action-card-item:hover .action-icon-circle {
            transform: scale(1.1);
        }

        .act-blue { background: #eff6ff; color: #2563eb; }
        .act-emerald { background: #ecfdf5; color: #10b981; }
        .act-amber { background: #fffbeb; color: #d97706; }
        .act-purple { background: #f3e8ff; color: #9333ea; }
        .act-indigo { background: #e0e7ff; color: #4338ca; }

        .action-card-title {
            font-size: 16px;
            font-weight: 800;
            color: var(--text-main);
            margin: 0 0 6px 0;
        }

        .action-card-desc {
            font-size: 13px;
            color: var(--text-sub);
            margin: 0;
            line-height: 1.4;
            font-weight: 500;
        }
    </style>
</head>
<body>

<div id="wrapper">

    <!-- Navigation Sidebar -->
    <nav class="navbar-default navbar-static-side" role="navigation">
        <div class="dropdown profile-element">
            <center>
                <span><img alt="image" class="img-circle" src="../img/customer.png" style="padding-top: 20px; width:64px; height:64px; object-fit:cover;"/></span>
                <span class="clear"> <span class="block m-t-xs"> <strong class="font-bold"><?php echo htmlspecialchars($_SESSION['name']); ?></strong></span></span>
            </center>
        </div>
        <div class="sidebar-collapse" id="divMenuContainer">
            <!-- menu injected dynamically via menu.js -->
        </div>
    </nav>

    <!-- Page Wrapper -->
    <div id="page-wrapper" class="gray-bg">

        <!-- Top Navbar -->
        <div class="row border-bottom">
            <nav class="navbar navbar-static-top" role="navigation" style="margin-bottom: 0">
                <div class="navbar-header">
                    <a class="navbar-minimalize minimalize-styl-2 btn btn-primary" href="#"><i class="fa fa-bars"></i></a>
                </div>
                <ul class="nav navbar-top-links navbar-right">     
                    <li>
                        <span style="font-weight:700; color:#475569; margin-right:15px;">
                            <i class="fa fa-user-circle" style="color:#2563eb;"></i> <?php echo htmlspecialchars($_SESSION['name']); ?>
                        </span>
                    </li>
                    <li>
                        <a href="../app_login_manager/logout.php" style="color: #147ad1;">
                            <i class="fa fa-sign-out"></i> Log out
                        </a>
                    </li>
                </ul>
            </nav>
        </div>

        <div class="dashboard-wrapper">
            
            <!-- Hero Card -->
            <div class="hero-card">
                <div>
                    <div class="hero-welcome-badge">
                        <i class="fa fa-shield"></i> Group Admin Command Center
                    </div>
                    <div class="hero-title-text">
                        <h1>Welcome Back, <?php echo htmlspecialchars($_SESSION['name']); ?></h1>
                        <p>Managing group attendance, income collections, and expenses</p>
                    </div>
                </div>
                <div>
                    <label style="font-size:11px; font-weight:800; text-transform:uppercase; letter-spacing:0.5px; display:block; margin-bottom:6px; opacity:0.9;">Selected Group:</label>
                    <select id="dashboard-group-select" class="group-select-box" onchange="onGroupChange()">
                        <option value="">Loading assigned groups...</option>
                    </select>
                </div>
            </div>

            <!-- Stats Grid -->
            <div class="stat-grid">
                <div class="stat-card-item">
                    <div class="stat-card-top">
                        <div class="stat-meta-title">Group Members</div>
                        <div class="stat-icon-wrap icon-blue"><i class="fa fa-users"></i></div>
                    </div>
                    <div class="stat-big-val" id="stat-members">0</div>
                    <div style="font-size:12px; color:var(--text-sub); margin-top:8px; font-weight:600;">Active enrolled members</div>
                </div>

                <div class="stat-card-item">
                    <div class="stat-card-top">
                        <div class="stat-meta-title">Today's Attendance</div>
                        <div class="stat-icon-wrap icon-emerald"><i class="fa fa-calendar-check-o"></i></div>
                    </div>
                    <div class="stat-big-val" id="stat-today-att">0</div>
                    <div class="stat-progress-bar">
                        <div class="stat-progress-fill" id="att-progress-bar"></div>
                    </div>
                </div>

                <div class="stat-card-item">
                    <div class="stat-card-top">
                        <div class="stat-meta-title">Month Income</div>
                        <div class="stat-icon-wrap icon-purple"><i class="fa fa-money"></i></div>
                    </div>
                    <div class="stat-big-val" id="stat-income">₹0.00</div>
                    <div style="font-size:12px; color:var(--text-sub); margin-top:8px; font-weight:600;">Fee collections this month</div>
                </div>

                <div class="stat-card-item">
                    <div class="stat-card-top">
                        <div class="stat-meta-title">Month Expenses</div>
                        <div class="stat-icon-wrap icon-amber"><i class="fa fa-credit-card"></i></div>
                    </div>
                    <div class="stat-big-val" id="stat-exp">₹0.00</div>
                    <div style="font-size:12px; color:var(--text-sub); margin-top:8px; font-weight:600;">Recorded group payables</div>
                </div>

                <div class="stat-card-item">
                    <div class="stat-card-top">
                        <div class="stat-meta-title">Pending Dues</div>
                        <div class="stat-icon-wrap icon-rose"><i class="fa fa-exclamation-triangle"></i></div>
                    </div>
                    <div class="stat-big-val" id="stat-dues">₹0.00</div>
                    <div style="font-size:12px; color:var(--text-sub); margin-top:8px; font-weight:600;">Outstanding receivables</div>
                </div>
            </div>

            <!-- Quick Action Grid -->
            <div class="section-header-title">
                <i class="fa fa-bolt" style="color:#2563eb;"></i> Quick Management Shortcuts
            </div>
            <div class="actions-grid">
                <a href="attendance.php" class="action-card-item">
                    <div class="action-icon-circle act-blue"><i class="fa fa-calendar-check-o"></i></div>
                    <h4 class="action-card-title">Mark Group Attendance</h4>
                    <p class="action-card-desc">Record & edit daily attendance for your group members</p>
                </a>

                <a href="fees_receiveble.php" class="action-card-item">
                    <div class="action-icon-circle act-emerald"><i class="fa fa-inr"></i></div>
                    <h4 class="action-card-title">Group Fee Receipts</h4>
                    <p class="action-card-desc">Collect member payments and issue fee receipts</p>
                </a>

                <a href="payable.php" class="action-card-item">
                    <div class="action-icon-circle act-amber"><i class="fa fa-file-text-o"></i></div>
                    <h4 class="action-card-title">Record Expense</h4>
                    <p class="action-card-desc">Add group expenses and manage vendor payables</p>
                </a>

                <a href="cashbook.php" class="action-card-item">
                    <div class="action-icon-circle act-purple"><i class="fa fa-book"></i></div>
                    <h4 class="action-card-title">Group Cashbook</h4>
                    <p class="action-card-desc">View group ledger transactions and cash balance</p>
                </a>

                <a href="members_list.php" class="action-card-item">
                    <div class="action-icon-circle act-indigo"><i class="fa fa-id-card-o"></i></div>
                    <h4 class="action-card-title">Group Members List</h4>
                    <p class="action-card-desc">View roster and contact details of group members</p>
                </a>
            </div>

        </div>
    </div>
</div>

<!-- Scripts -->
<script src="../js/jquery-3.1.1.min.js"></script>
<script src="../js/bootstrap.min.js"></script>
<script src="../js/plugins/metisMenu/jquery.metisMenu.js"></script>
<script src="../js/plugins/slimscroll/jquery.slimscroll.min.js"></script>
<script src="../js/inspinia.js"></script>
<script src="../app_menu/menu.js"></script>

<script>
$(document).ready(function() {
    loadMenu(true);
    loadAssignedGroups();
});

function loadAssignedGroups() {
    $.post('api/group_dashboard.php', { action: 'load_assigned_groups' }, function(res) {
        try {
            var d = (typeof res === 'string') ? JSON.parse(res) : res;
            if (d.status === 'success' && d.data.length > 0) {
                var html = '';
                d.data.forEach(function(g) {
                    html += '<option value="' + g.group_id + '">' + g.group_name + '</option>';
                });
                $('#dashboard-group-select').html(html);
                onGroupChange();
            } else {
                $('#dashboard-group-select').html('<option value="">No assigned groups found</option>');
            }
        } catch(e) {}
    });
}

function onGroupChange() {
    var groupId = $('#dashboard-group-select').val();
    if (!groupId) return;

    $.post('api/group_dashboard.php', { action: 'load_group_metrics', group_id: groupId }, function(res) {
        try {
            var d = (typeof res === 'string') ? JSON.parse(res) : res;
            if (d.status === 'success') {
                var m = d.metrics;
                $('#stat-members').text(m.total_members);
                $('#stat-today-att').text(m.today_present + ' / ' + m.total_members);
                $('#stat-income').text('₹' + m.monthly_income);
                $('#stat-exp').text('₹' + m.monthly_exp);
                $('#stat-dues').text('₹' + m.pending_dues);

                var pct = m.total_members > 0 ? Math.round((m.today_present / m.total_members) * 100) : 0;
                $('#att-progress-bar').css('width', pct + '%');
            }
        } catch(e) {}
    });
}
</script>

</body>
</html>
