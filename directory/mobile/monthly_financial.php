<?php
session_start();
include '../../app_common/db_connect.php';
include '../../app_common/auth_helper.php';

if (empty($_SESSION['login_id'])) {
    header("Location: ../../index.php");
    exit();
}

$login_id = (int)$_SESSION['login_id'];
if (isNormalMember($login_id)) {
    header("Location: home.php");
    exit();
}
$is_admin = isSuperAdmin($login_id) || isGroupAdmin($login_id) || isAttendanceMaster($login_id) || isExecutiveMember($login_id);
$allowed_groups = getUserAllowedGroupIds($login_id);
$primary_role = getUserPrimaryRoleName($login_id);

$active_tab = 'reports';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
    <title>Monthly Financial Report - YMCA Mobile</title>
    
    <link href="../../css/bootstrap.min.css" rel="stylesheet">
    <link href="../../font-awesome/css/font-awesome.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <link href="mobile_layout.css" rel="stylesheet">

    <style>
        body { font-family: 'Plus Jakarta Sans', sans-serif !important; background: #f8fafc !important; }
        .rep-hero {
            background: linear-gradient(135deg, #7c3aed 0%, #a855f7 100%);
            border-radius: 20px; padding: 18px 20px; color: #ffffff; margin-bottom: 16px;
            box-shadow: 0 8px 20px rgba(124, 58, 237, 0.2);
        }
        .rep-hero h2 { margin: 0 0 4px 0; font-weight: 800; font-size: 20px; }
        .rep-hero p { margin: 0; font-size: 12.5px; opacity: 0.9; }

        .rep-filter-box {
            background: #ffffff; border-radius: 16px; border: 1px solid #e2e8f0;
            padding: 14px; margin-bottom: 14px; box-shadow: 0 2px 8px rgba(0,0,0,0.02);
            display: flex; flex-direction: column; gap: 10px;
        }
        .rep-field { display: flex; flex-direction: column; gap: 4px; }
        .rep-field label { font-size: 11px; font-weight: 700; color: #64748b; text-transform: uppercase; margin: 0; }
        .rep-field input, .rep-field select {
            padding: 10px 14px; border-radius: 12px; border: 1.5px solid #cbd5e1;
            background: #f8fafc; font-size: 13.5px; font-weight: 600; color: #0f172a; outline: none;
        }

        .stat-card-row { display: grid; grid-template-columns: repeat(2, 1fr); gap: 10px; margin-bottom: 16px; }
        .stat-box {
            background: #ffffff; border-radius: 14px; border: 1px solid #e2e8f0; padding: 12px 14px;
            box-shadow: 0 2px 6px rgba(0,0,0,0.02);
        }
        .stat-lbl { font-size: 11px; font-weight: 700; color: #64748b; text-transform: uppercase; }
        .stat-val { font-size: 17px; font-weight: 800; color: #0f172a; margin-top: 2px; }

        .mob-tbl-card {
            background: #ffffff; border-radius: 16px; border: 1px solid #e2e8f0; overflow: hidden;
            box-shadow: 0 2px 8px rgba(0,0,0,0.02); margin-bottom: 20px;
        }
        .table-mob { width: 100%; border-collapse: collapse; font-size: 13px; }
        .table-mob th { background: #f8fafc; padding: 10px 14px; font-size: 11px; font-weight: 700; color: #475569; text-transform: uppercase; border-bottom: 1px solid #e2e8f0; }
        .table-mob td { padding: 12px 14px; border-bottom: 1px solid #f1f5f9; color: #1e293b; font-weight: 500; }
    </style>
</head>
<body class="mob-body">

    <!-- Header -->
    <header class="mob-header">
        <div class="mob-header-brand">
            <a href="reports.php" style="color:#ffffff; margin-right:8px; font-size:18px;"><i class="fa fa-arrow-left"></i></a>
            <div class="mob-header-title">
                Monthly Financial <span>Report</span>
            </div>
        </div>
        <div class="mob-header-actions">
            <a href="../../app_login_manager/logout.php" class="mob-header-btn" title="Logout"><i class="fa fa-sign-out"></i></a>
        </div>
    </header>

    <div class="mob-page">

        <div class="rep-hero">
            <h2>Monthly Financial Report</h2>
            <p>Monthly group income, collections, and expense totals</p>
        </div>

        <div class="rep-filter-box">
            <div class="rep-field">
                <label>Select Month</label>
                <input type="month" id="rep_month" value="<?php echo date('Y-m'); ?>" onchange="loadReport()">
            </div>
            <div class="rep-field" id="rep_group_container">
                <label>Select Group</label>
                <select id="rep_group" onchange="loadReport()"></select>
            </div>
        </div>

        <div class="stat-card-grid" style="display: grid; grid-template-columns: repeat(2, 1fr); gap: 10px; margin-bottom: 18px;">
            <!-- 1. Total Monthly Fee Received -->
            <div class="stat-box" style="border-left: 4px solid #3b82f6; background: #fff; border-radius: 12px; padding: 12px 14px; box-shadow: 0 2px 8px rgba(0,0,0,0.03); position: relative;">
                <div class="stat-lbl" style="font-size: 10px; font-weight: 800; color: #64748b; text-transform: uppercase;">Total Monthly Fee Received</div>
                <div class="stat-val" id="lbl_monthly_received" style="font-size: 15px; font-weight: 800; color: #1e293b; margin-top: 4px;">₹0.00</div>
                <i class="fa fa-calendar-check-o" style="position: absolute; right: 12px; bottom: 12px; opacity: 0.15; font-size: 20px; color: #3b82f6;"></i>
            </div>

            <!-- 2. Member Fees Receivable -->
            <div class="stat-box" style="border-left: 4px solid #10b981; background: #fff; border-radius: 12px; padding: 12px 14px; box-shadow: 0 2px 8px rgba(0,0,0,0.03); position: relative;">
                <div class="stat-lbl" style="font-size: 10px; font-weight: 800; color: #64748b; text-transform: uppercase;">Member Fees Receivable</div>
                <div class="stat-val" id="lbl_member_receivable" style="font-size: 15px; font-weight: 800; color: #1e293b; margin-top: 4px;">₹0.00</div>
                <i class="fa fa-calendar-minus-o" style="position: absolute; right: 12px; bottom: 12px; opacity: 0.15; font-size: 20px; color: #10b981;"></i>
            </div>

            <!-- 3. Total Sessions -->
            <div class="stat-box" style="border-left: 4px solid #f59e0b; background: #fff; border-radius: 12px; padding: 12px 14px; box-shadow: 0 2px 8px rgba(0,0,0,0.03); position: relative;">
                <div class="stat-lbl" style="font-size: 10px; font-weight: 800; color: #64748b; text-transform: uppercase;">Total Sessions</div>
                <div class="stat-val" id="lbl_total_sessions" style="font-size: 15px; font-weight: 800; color: #1e293b; margin-top: 4px;">0</div>
                <i class="fa fa-clock-o" style="position: absolute; right: 12px; bottom: 12px; opacity: 0.15; font-size: 20px; color: #f59e0b;"></i>
            </div>

            <!-- 4. Cokes Used -->
            <div class="stat-box" style="border-left: 4px solid #8b5cf6; background: #fff; border-radius: 12px; padding: 12px 14px; box-shadow: 0 2px 8px rgba(0,0,0,0.03); position: relative;">
                <div class="stat-lbl" style="font-size: 10px; font-weight: 800; color: #64748b; text-transform: uppercase;">Cokes Used</div>
                <div class="stat-val" id="lbl_cokes_used" style="font-size: 15px; font-weight: 800; color: #1e293b; margin-top: 4px;">0</div>
                <i class="fa fa-glass" style="position: absolute; right: 12px; bottom: 12px; opacity: 0.15; font-size: 20px; color: #8b5cf6;"></i>
            </div>

            <!-- 5. Total Coke Cost -->
            <div class="stat-box" style="border-left: 4px solid #06b6d4; background: #fff; border-radius: 12px; padding: 12px 14px; box-shadow: 0 2px 8px rgba(0,0,0,0.03); position: relative; grid-column: span 2;">
                <div class="stat-lbl" style="font-size: 10px; font-weight: 800; color: #64748b; text-transform: uppercase;">Total Coke Cost</div>
                <div class="stat-val" id="lbl_coke_cost" style="font-size: 15px; font-weight: 800; color: #1e293b; margin-top: 4px;">₹0.00</div>
                <i class="fa fa-shopping-cart" style="position: absolute; right: 12px; bottom: 12px; opacity: 0.15; font-size: 20px; color: #06b6d4;"></i>
            </div>
        </div>

        <div class="mob-tbl-card">
            <div style="padding:12px 14px; border-bottom:1px solid #e2e8f0; font-size:13px; font-weight:800; color:#0f172a; display:flex; align-items:center; justify-content:space-between;">
                <span><i class="fa fa-users" style="color:#3b82f6;"></i> Monthly Present Members</span>
            </div>
            <div style="overflow-x:auto;">
                <table class="table-mob">
                    <thead>
                        <tr>
                            <th>Member Name</th>
                            <th style="text-align:center;">Sessions</th>
                            <th style="text-align:right;">Receivable (₹)</th>
                        </tr>
                    </thead>
                    <tbody id="tbl_report_body">
                        <tr><td colspan="3" style="text-align:center; color:#94a3b8; padding:30px;">Loading...</td></tr>
                    </tbody>
                </table>
            </div>
        </div>

    </div>

    <!-- Mobile Bottom Navigation (5 Tabs) -->
    <?php include 'mobile_bottom_nav.php'; ?>

    <script src="../../js/jquery-3.1.1.min.js"></script>
    <script src="../../js/bootstrap.min.js"></script>
    <script>
    $(document).ready(function() {
        $.post('../api/attendance.php', { action: 'load_groups' }, function(data) {
            try {
                let parsed = typeof data === 'string' ? JSON.parse(data) : data;
                let groups = Array.isArray(parsed[0]) ? parsed[0] : (Array.isArray(parsed) ? parsed : []);
                let htm = groups.length > 1 ? '<option value="0">All Groups</option>' : '';
                groups.forEach(function(g) { htm += '<option value="' + g.id + '">' + g.name + '</option>'; });
                $('#rep_group').html(htm);
                if (groups.length <= 1) {
                    $('#rep_group_container').hide();
                    if (groups.length === 1) {
                        $('#rep_group').val(groups[0].id);
                    }
                } else {
                    $('#rep_group_container').show();
                }
                loadReport();
            } catch(e) {}
        });
    });

    function formatCurr(num) {
        return '₹' + parseFloat(num || 0).toLocaleString('en-IN', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
    }

    function loadReport() {
        let month = $('#rep_month').val();
        let group_id = $('#rep_group').val();
        if (!month) return;

        $.post('../api/monthly_financial_report.php', { action: 'load_report', month: month, group_id: group_id }, function(res) {
            try {
                let data = typeof res === 'string' ? JSON.parse(res) : res;
                let summary = data.summary || {};
                let members = data.members || [];

                $('#lbl_monthly_received').text(formatCurr(summary.member_received_total));
                $('#lbl_member_receivable').text(formatCurr(summary.member_receivable_total));
                $('#lbl_total_sessions').text(summary.total_sessions || 0);
                $('#lbl_cokes_used').text(summary.shuttle_total_used || 0);
                $('#lbl_coke_cost').text(formatCurr(summary.shuttle_total_cost));

                let htm = '';
                if (members.length > 0) {
                    members.forEach(function(m) {
                        htm += `<tr>
                            <td>
                                <strong style="color:#0f172a;">${m.name}</strong>
                                <div style="font-size:11px; color:#64748b;">${m.group_names || ''}</div>
                            </td>
                            <td style="text-align:center; font-weight:700;">${m.attendance}</td>
                            <td style="text-align:right; font-weight:700; color:#1e293b;">${formatCurr(m.receivable)}</td>
                        </tr>`;
                    });
                } else {
                    htm = '<tr><td colspan="3" style="text-align:center; color:#94a3b8; padding:30px;">No members were present for this month.</td></tr>';
                }
                $('#tbl_report_body').html(htm);
            } catch(e) {
                $('#tbl_report_body').html('<tr><td colspan="3" style="text-align:center; color:#ef4444; padding:30px;">Error loading report data.</td></tr>');
            }
        });
    }
    </script>
</body>
</html>
