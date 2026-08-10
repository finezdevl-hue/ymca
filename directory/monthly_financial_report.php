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
            padding: 10px 14px; border-radius: 10px;
            border: 1.5px solid #e2e8f0; background: #f8faff;
            font-size: 14px; font-weight: 500; color: #1e293b;
            font-family: 'Inter', sans-serif;
            outline: none; transition: border-color 0.2s, box-shadow 0.2s;
            min-width: 180px;
        }
        .rep-filter-field select:focus,
        .rep-filter-field input[type="month"]:focus {
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
        .rep-btn-green {
            background: linear-gradient(135deg,#10b981,#059669);
            color: #fff; box-shadow: 0 3px 12px rgba(16,185,129,0.3);
        }
        .rep-btn-green:hover { opacity: 0.9; transform: translateY(-1px); }

        /* ---- KPI Cards ---- */
        .kpi-row {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(220px, 1fr));
            gap: 20px;
            margin-bottom: 24px;
        }
        .kpi-card {
            background: #fff;
            border-radius: 16px;
            border: 1px solid #e8edf5;
            padding: 20px 24px;
            box-shadow: 0 4px 10px rgba(0,0,0,0.02);
            position: relative;
            overflow: hidden;
            display: flex;
            flex-direction: column;
            gap: 6px;
            transition: transform 0.2s, box-shadow 0.2s;
        }
        .kpi-card:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 20px rgba(99, 102, 241, 0.08);
        }
        .kpi-card .kpi-label {
            font-size: 12px;
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
        .kpi-card .kpi-icon {
            position: absolute;
            right: 20px;
            bottom: 16px;
            font-size: 28px;
            opacity: 0.15;
            color: #3b82f6;
        }
        .kpi-card.gradient-1 { border-left: 5px solid #3b82f6; }
        .kpi-card.gradient-2 { border-left: 5px solid #10b981; }
        .kpi-card.gradient-3 { border-left: 5px solid #ef4444; }
        .kpi-card.gradient-4 { border-left: 5px solid #f59e0b; }
        .kpi-card.gradient-5 { border-left: 5px solid #8b5cf6; }
        .kpi-card.gradient-6 { border-left: 5px solid #06b6d4; }

        /* ---- Tables ---- */
        .rep-table-card {
            background: #fff; border-radius: 18px;
            border: 1px solid #e8edf5;
            box-shadow: 0 2px 12px rgba(0,0,0,0.05);
            overflow: hidden;
            margin-bottom: 24px;
        }
        .rep-table-header {
            padding: 18px 24px;
            border-bottom: 1px solid #f1f5f9;
            display: flex; align-items: center; justify-content: space-between;
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
        }
        .table-custom {
            width: 100%;
            border-collapse: collapse;
            font-size: 14px;
        }
        .table-custom th {
            background: #f8fafc;
            padding: 12px 20px;
            font-size: 11px;
            font-weight: 700;
            color: #475569;
            text-transform: uppercase;
            letter-spacing: 0.05em;
            border-bottom: 1.5px solid #e8edf5;
            text-align: left;
        }
        .table-custom td {
            padding: 14px 20px;
            border-bottom: 1px solid #e8edf5;
            color: #334155;
        }
        .table-custom tbody tr:hover {
            background: #f8faff;
        }
        
        .progress-bar-container {
            width: 100%;
            background-color: #e2e8f0;
            border-radius: 8px;
            overflow: hidden;
            height: 8px;
            display: inline-block;
        }
        .progress-bar-fill {
            height: 100%;
            background: linear-gradient(90deg, #10b981, #059669);
            border-radius: 8px;
        }

        .rep-empty {
            text-align: center; padding: 50px 20px;
            color: #94a3b8;
        }
        .rep-empty i { font-size: 40px; margin-bottom: 12px; display: block; color: #dbeafe; }

        @media print {
            body { background: #fff !important; color: #000 !important; }
            .rep-topbar, .rep-filter-card, .rep-btn, .nav-topbar, .navbar-default, .sidebar-collapse, .navbar-static-side {
                display: none !important;
            }
            #page-wrapper { margin: 0 !important; padding: 0 !important; }
            .rep-content { padding: 0 !important; }
            .rep-table-card { border: none !important; box-shadow: none !important; }
        }
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

    <!-- Main Content -->
    <div id="page-wrapper" style="background:#f0f4ff; padding:0; min-height:100vh;">
        
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

            <!-- Print Header -->
            <div class="print-only" style="display:none; text-align:center; margin-bottom:20px;">
                <h2 style="margin:0; font-size:20px; font-weight:700;">YMCA Poovathussery - Monthly Financial Report</h2>
                <p id="print_meta" style="font-size:14px; margin:6px 0 0; color:#475569;"></p>
            </div>

            <!-- Dashboard Content Wrapper (Initially hidden) -->
            <div id="dashboard_wrapper" style="display:none;">
                
                <!-- Report Header with Month Name -->
                <div style="background: #fff; border-radius: 18px; border: 1px solid #e8edf5; box-shadow: 0 2px 12px rgba(0,0,0,0.05); padding: 20px 26px; margin-bottom: 22px;">
                    <h2 style="margin: 0; font-size: 20px; font-weight: 700; color: #1e293b;">
                        Monthly Financial Report - <span id="dashboard_month_name" style="color: #3b82f6;"></span>
                    </h2>
                    <p id="dashboard_group_name" style="margin: 6px 0 0; font-size: 13px; color: #64748b;"></p>
                </div>

                <!-- KPI Row -->
                <div class="kpi-row">
                    <div class="kpi-card gradient-1">
                        <span class="kpi-label">Total Monthly Fee Received</span>
                        <span class="kpi-val" id="val_received_total">₹ 0.00</span>
                        <i class="fa fa-calendar-check-o kpi-icon"></i>
                    </div>
                    <div class="kpi-card gradient-2">
                        <span class="kpi-label">Member Fees Receivable</span>
                        <span class="kpi-val" id="val_receivable">₹ 0.00</span>
                        <i class="fa fa-calendar-minus-o kpi-icon"></i>
                    </div>
                    <div class="kpi-card gradient-4">
                        <span class="kpi-label">Total Sessions</span>
                        <span class="kpi-val" id="val_sessions">0</span>
                        <i class="fa fa-clock-o kpi-icon"></i>
                    </div>
                    <div class="kpi-card gradient-5">
                        <span class="kpi-label">Cokes Used</span>
                        <span class="kpi-val" id="val_cokes_used">0</span>
                        <i class="fa fa-glass kpi-icon"></i>
                    </div>
                    <div class="kpi-card gradient-6">
                        <span class="kpi-label">Total Coke Cost</span>
                        <span class="kpi-val" id="val_coke_cost">₹ 0.00</span>
                        <i class="fa fa-shopping-cart kpi-icon"></i>
                    </div>
                </div>

                <!-- Members Summary Table -->
                <div class="rep-table-card">
                    <div class="rep-table-header">
                        <h3 class="rep-table-title">
                            <i class="fa fa-users"></i> Monthly Present Members
                        </h3>
                    </div>
                    <div class="rep-table-wrap">
                        <table class="table-custom" id="tbl_members_summary">
                            <thead>
                                <tr>
                                    <th>Member Name</th>
                                    <th>Group(s)</th>
                                    <th style="text-align:center;">Sessions Attended</th>
                                    <th>Receivable</th>
                                </tr>
                            </thead>
                            <tbody id="members_tbody">
                                <!-- Dynamic entries -->
                            </tbody>
                        </table>
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

<script src="../js/jquery-3.1.1.min.js"></script>
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
    return new Intl.NumberFormat('en-IN', { style: 'currency', currency: 'INR' }).format(value);
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

    $('#print_meta').text('Month: ' + monthName + ' | Group: ' + groupName);
    $('#dashboard_month_name').text(monthName);
    $('#dashboard_group_name').text('Group: ' + groupName);

    // Show loading spinner
    $('#status_wrapper').html('<div class="rep-table-card"><div class="rep-empty"><i class="fa fa-spinner fa-spin" style="color:#3b82f6; font-size:28px;"></i><p>Loading financial data...</p></div></div>').show();
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
            var response = JSON.parse(res);
            if (response.error) {
                $('#status_wrapper').html('<div class="rep-table-card"><div class="rep-empty"><i class="fa fa-exclamation-triangle" style="color:#ef4444;"></i><p>' + response.error + '</p></div></div>');
                return;
            }

            var summary = response.summary;
            var members = response.members;

            // Populate KPI Cards
            $('#val_received_total').text(formatCurrency(summary.member_received_total));
            $('#val_receivable').text(formatCurrency(summary.member_receivable_total));
            $('#val_sessions').text(summary.total_sessions);
            $('#val_cokes_used').text(summary.shuttle_total_used);
            $('#val_coke_cost').text(formatCurrency(summary.shuttle_total_cost));

            // Populate Members Table
            var tbodyHtml = '';
            if (members && members.length > 0) {
                for (var i = 0; i < members.length; i++) {
                    var m = members[i];
                    tbodyHtml += '<tr>';
                    tbodyHtml += '<td style="font-weight:600; color:#0f172a;">' + m.name + '</td>';
                    tbodyHtml += '<td><span class="badge badge-primary" style="background:#e0e7ff; color:#4f46e5; border:none; padding:4px 8px; font-weight:500;">' + m.group_names + '</span></td>';
                    tbodyHtml += '<td style="text-align:center; font-weight:700;">' + m.attendance + '</td>';
                    tbodyHtml += '<td style="color:#1e293b; font-weight:600;">' + formatCurrency(m.receivable) + '</td>';
                    tbodyHtml += '</tr>';
                }
            } else {
                tbodyHtml = '<tr><td colspan="4" class="text-center text-muted" style="padding:30px;">No members were present for this month.</td></tr>';
            }

            $('#members_tbody').html(tbodyHtml);
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
