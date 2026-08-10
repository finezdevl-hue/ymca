<?php
session_start();
include '../../app_common/db_connect.php';
include '../../app_common/auth_helper.php';

if (empty($_SESSION['login_id'])) {
    header("Location: ../../index.php");
    exit();
}

$login_id = (int)$_SESSION['login_id'];
$allowed_groups = getUserAllowedGroupIds($login_id);
$primary_role = getUserPrimaryRoleName($login_id);

$active_tab = 'reports';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
    <title>Monthly Attendance - YMCA Mobile</title>
    
    <link href="../../css/bootstrap.min.css" rel="stylesheet">
    <link href="../../font-awesome/css/font-awesome.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <link href="mobile_layout.css" rel="stylesheet">

    <style>
        body { font-family: 'Plus Jakarta Sans', sans-serif !important; background: #f8fafc !important; }
        .rep-hero {
            background: linear-gradient(135deg, #4f46e5 0%, #6366f1 100%);
            border-radius: 20px; padding: 18px 20px; color: #ffffff; margin-bottom: 16px;
            box-shadow: 0 8px 20px rgba(79, 70, 229, 0.2);
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

        .mob-mem-att-card {
            background: #ffffff; border-radius: 16px; border: 1px solid #e2e8f0;
            padding: 14px; margin-bottom: 10px; box-shadow: 0 2px 8px rgba(0,0,0,0.02);
        }
        .mem-head { display: flex; align-items: center; justify-content: space-between; margin-bottom: 10px; }
        .mem-name { font-size: 14px; font-weight: 800; color: #0f172a; margin: 0; }
        .mem-days-pill { font-size: 12px; font-weight: 800; background: #ecfdf5; color: #059669; padding: 4px 10px; border-radius: 10px; }

        .days-grid { display: flex; flex-wrap: wrap; gap: 4px; }
        .day-dot {
            width: 26px; height: 26px; border-radius: 6px; display: flex; align-items: center; justify-content: center;
            font-size: 10px; font-weight: 700; color: #ffffff;
        }
        .day-dot.p   { background: #10b981; color: #fff; }
        .day-dot.abs { background: #ef4444; color: #fff; }
        .day-dot.off { background: #f1f5f9; color: #94a3b8; }
        .day-dot.a   { background: #f1f5f9; color: #94a3b8; }
    </style>
</head>
<body class="mob-body">

    <!-- Header -->
    <header class="mob-header">
        <div class="mob-header-brand">
            <a href="reports.php" style="color:#ffffff; margin-right:8px; font-size:18px;"><i class="fa fa-arrow-left"></i></a>
            <div class="mob-header-title">
                Monthly Attendance <span>Report</span>
            </div>
        </div>
        <div class="mob-header-actions">
            <a href="../../app_login_manager/logout.php" class="mob-header-btn" title="Logout"><i class="fa fa-sign-out"></i></a>
        </div>
    </header>

    <div class="mob-page">

        <div class="rep-hero">
            <h2>Monthly Attendance Matrix</h2>
            <p>Day-by-day attendance log for your group members</p>
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

        <?php 
        include_once '../../app_common/auth_helper.php';
        $login_id = (int)$_SESSION['login_id'];
        if (isSuperAdmin($login_id) || isGroupAdmin($login_id) || isExecutiveMember($login_id)): 
        ?>
        <!-- Summary Stat Cards -->
        <div class="stat-card-grid" style="display: grid; grid-template-columns: repeat(2, 1fr); gap: 10px; margin-bottom: 16px;">
            <div class="stat-box" style="border-left: 4px solid #3b82f6; background: #fff; border-radius: 12px; padding: 10px 12px; box-shadow: 0 2px 8px rgba(0,0,0,0.03);">
                <div class="stat-lbl" style="font-size: 10px; font-weight: 800; color: #64748b; text-transform: uppercase;">Total Members</div>
                <div class="stat-val" id="stat_total_members" style="font-size: 16px; font-weight: 800; color: #1e293b; margin-top: 2px;">0</div>
            </div>
            <div class="stat-box" style="border-left: 4px solid #f59e0b; background: #fff; border-radius: 12px; padding: 10px 12px; box-shadow: 0 2px 8px rgba(0,0,0,0.03);">
                <div class="stat-lbl" style="font-size: 10px; font-weight: 800; color: #64748b; text-transform: uppercase;">Total Sessions</div>
                <div class="stat-val" id="stat_total_sessions" style="font-size: 16px; font-weight: 800; color: #1e293b; margin-top: 2px;">0</div>
            </div>
            <div class="stat-box" style="border-left: 4px solid #10b981; background: #fff; border-radius: 12px; padding: 10px 12px; box-shadow: 0 2px 8px rgba(0,0,0,0.03);">
                <div class="stat-lbl" style="font-size: 10px; font-weight: 800; color: #64748b; text-transform: uppercase;">Avg Attendance</div>
                <div class="stat-val" id="stat_avg_attendance" style="font-size: 16px; font-weight: 800; color: #1e293b; margin-top: 2px;">0%</div>
            </div>
            <div class="stat-box" style="border-left: 4px solid #8b5cf6; background: #fff; border-radius: 12px; padding: 10px 12px; box-shadow: 0 2px 8px rgba(0,0,0,0.03);">
                <div class="stat-lbl" style="font-size: 10px; font-weight: 800; color: #64748b; text-transform: uppercase;">100% Attendance</div>
                <div class="stat-val" id="stat_full_attendance" style="font-size: 16px; font-weight: 800; color: #1e293b; margin-top: 2px;">0</div>
            </div>
        </div>
        <?php endif; ?>

        <?php if (isSuperAdmin($login_id) || isGroupAdmin($login_id)): ?>
        <!-- Coke Readings Box -->
        <div class="rep-coke-card" style="background: #ffffff; border-radius: 16px; border: 1px solid #e2e8f0; padding: 14px 16px; margin-bottom: 16px; box-shadow: 0 2px 8px rgba(0,0,0,0.02);">
            <div style="font-size: 13.5px; font-weight: 800; color: #0f172a; margin-bottom: 12px; display: flex; align-items: center; justify-content: space-between;">
                <span><i class="fa fa-glass" style="color:#8b5cf6;"></i> Coke Readings</span>
                <span style="font-size: 12px; font-weight: 700; color: #4f46e5; background: rgba(79,70,229,0.1); padding: 3px 8px; border-radius: 6px;">
                    Used: <span id="coke_used_badge">0</span>
                </span>
            </div>

            <div style="display: grid; grid-template-columns: repeat(2, 1fr); gap: 10px; margin-bottom: 10px;">
                <div class="rep-field">
                    <label style="font-size: 11px; font-weight: 700; color: #64748b; text-transform: uppercase;">Coke Starts</label>
                    <input type="number" id="start_shuttle_no" class="form-control" placeholder="Starts" oninput="calcCokeReadings()">
                </div>
                <div class="rep-field">
                    <label style="font-size: 11px; font-weight: 700; color: #64748b; text-transform: uppercase;">Coke Ends</label>
                    <input type="number" id="end_shuttle_no" class="form-control" placeholder="Ends" oninput="calcCokeReadings()">
                </div>
            </div>

            <div style="display: grid; grid-template-columns: repeat(2, 1fr); gap: 10px; margin-bottom: 12px; align-items: flex-end;">
                <div class="rep-field">
                    <label style="font-size: 11px; font-weight: 700; color: #64748b; text-transform: uppercase;">Avg Coke Price (₹)</label>
                    <input type="number" id="shuttle_price_input" class="form-control" placeholder="0.00" step="0.01" min="0" oninput="calcCokeReadings()">
                </div>
                <div class="rep-field" style="display: flex; flex-direction: column; justify-content: flex-end; padding-bottom: 4px;">
                    <div style="font-size: 11px; font-weight: 700; color: #059669; text-transform: uppercase;">Total Coke Cost</div>
                    <div id="coke_total_cost_badge" style="font-size: 15px; font-weight: 800; color: #059669; margin-top: 2px;">₹0.00</div>
                </div>
            </div>

            <div style="display: flex; justify-content: flex-end;">
                <button type="button" class="btn" id="btn_save_readings" onclick="saveCokeReadings()" style="background: #10b981; color: #fff; border: none; border-radius: 10px; padding: 8px 14px; font-weight: 700; font-size: 12.5px; display: inline-flex; align-items: center; gap: 6px; box-shadow: 0 2px 6px rgba(16,185,129,0.2); cursor: pointer;">
                    <i class="fa fa-save"></i> Save Coke Readings
                </button>
            </div>
        </div>

        <!-- Fix Attendance Action Bar -->
        <div style="margin-bottom: 16px; display: flex; justify-content: flex-end;">
            <button class="btn" id="btn_fix_attendance" onclick="toggleFixAttendance()" style="background: linear-gradient(135deg, #4f46e5, #3b82f6); color: #fff; border: none; border-radius: 12px; padding: 10px 18px; font-weight: 800; font-size: 13.5px; display: inline-flex; align-items: center; gap: 8px; box-shadow: 0 4px 12px rgba(79,70,229,0.22); cursor: pointer;">
                <i class="fa fa-wrench"></i> Fix Attendance
            </button>
        </div>
        <?php endif; ?>

        <div id="members_att_cards">
            <div style="text-align:center; padding:40px 20px; color:#94a3b8;">
                <i class="fa fa-spinner fa-spin" style="font-size:24px; margin-bottom:8px;"></i><br>
                Loading monthly attendance...
            </div>
        </div>

    </div>

    <!-- Mobile Bottom Navigation (5 Tabs) -->
    <?php include 'mobile_bottom_nav.php'; ?>

    <script src="../../js/jquery-3.1.1.min.js"></script>
    <script src="../../js/bootstrap.min.js"></script>
    <script src="../../app_js/sweetalert-finez.js"></script>
    <script>
    const IS_MANAGEMENT = <?php echo (isSuperAdmin($login_id) || isGroupAdmin($login_id) || isExecutiveMember($login_id)) ? 'true' : 'false'; ?>;

    $(document).ready(function() {
        $.post('../api/attendance.php', { action: 'load_groups' }, function(data) {
            try {
                let parsed = typeof data === 'string' ? JSON.parse(data) : data;
                let groups = Array.isArray(parsed[0]) ? parsed[0] : (Array.isArray(parsed) ? parsed : []);
                let htm = '';
                groups.forEach(function(g) { htm += '<option value="' + g.id + '">' + g.name + '</option>'; });
                $('#rep_group').html(htm);
                if (groups.length <= 1) {
                    $('#rep_group_container').hide();
                } else {
                    $('#rep_group_container').show();
                }
                loadReport();
            } catch(e) {}
        });
    });

    function loadReport() {
        let month = $('#rep_month').val();
        let group_id = $('#rep_group').val();
        if (!month) return;

        $('#members_att_cards').html('<div style="text-align:center; padding:40px 20px; color:#94a3b8;"><i class="fa fa-spinner fa-spin" style="font-size:24px; margin-bottom:8px;"></i><br>Loading...</div>');

        $.post('../api/monthly_attendance_report.php', { action: 'load_monthly_report', month: month, group_id: group_id }, function(res) {
            try {
                let data = typeof res === 'string' ? JSON.parse(res) : res;
                let members = data.members || [];
                let days_in_month = data.total_days || data.days_in_month || 30;
                let htm = '';

                // Calculate summary stats
                let totalMembers = members.length;
                let totalSessions = members.length > 0 ? (members[0].sessions || 0) : 0;
                let pctSum = 0;
                let fullCount = 0;

                if (members && members.length > 0) {
                    members.forEach(function(m) {
                        let pct = parseFloat(m.percentage || 0);
                        pctSum += pct;
                        if (pct >= 100) fullCount++;
                    });
                }

                let avgPct = totalMembers > 0 ? Math.round(pctSum / totalMembers) : 0;

                $('#stat_total_members').text(totalMembers);
                $('#stat_total_sessions').text(totalSessions);
                $('#stat_avg_attendance').text(avgPct + '%');
                $('#stat_full_attendance').text(fullCount);

                if (data.group_start_shuttle !== undefined && data.group_start_shuttle !== null) {
                    $('#start_shuttle_no').val(data.group_start_shuttle);
                } else {
                    $('#start_shuttle_no').val('');
                }
                if (data.group_end_shuttle !== undefined && data.group_end_shuttle !== null) {
                    $('#end_shuttle_no').val(data.group_end_shuttle);
                } else {
                    $('#end_shuttle_no').val('');
                }
                let priceVal = data.group_shuttle_price !== null && data.group_shuttle_price !== undefined ? data.group_shuttle_price : (data.month_avg_shuttle_price !== null && data.month_avg_shuttle_price !== undefined ? data.month_avg_shuttle_price : '');
                $('#shuttle_price_input').val(priceVal);
                calcCokeReadings();

                checkFixedStatus(month);

                if (!members || members.length === 0) {
                    $('#members_att_cards').html('<div style="text-align:center; padding:40px 20px; color:#94a3b8;"><i class="fa fa-info-circle" style="font-size:24px; margin-bottom:8px; display:block;"></i>No attendance records found for this month.</div>');
                    return;
                }

                members.forEach(function(m) {
                    let totalP = m.present !== undefined ? m.present : (m.total_present || 0);
                    if (IS_MANAGEMENT && parseInt(totalP, 10) <= 0) return; // Exclude 0 attendance members in management overview
                    let dotsHtm = '';
                    for (let d = 1; d <= days_in_month; d++) {
                        let dayVal = m.days ? m.days[d] : null;
                        let dotClass = 'off';
                        if (dayVal === 'P' || dayVal === 1) {
                            dotClass = 'p';
                        } else if (dayVal === 'A' || dayVal === 0) {
                            dotClass = 'abs';
                        }
                        dotsHtm += `<div class="day-dot ${dotClass}" title="Day ${d}">${d}</div>`;
                    }

                    let fullName = [m.first_name, m.middle_name, m.last_name].filter(Boolean).join(' ');

                    htm += `
                        <div class="mob-mem-att-card">
                            <div class="mem-head">
                                <h4 class="mem-name">${fullName}</h4>
                                <span class="mem-days-pill">${totalP} Days Present</span>
                            </div>
                            <div class="days-grid">${dotsHtm}</div>
                        </div>
                    `;
                });

                if (!htm || htm.trim() === '') {
                    htm = '<div style="text-align:center; padding:40px 20px; color:#94a3b8;"><i class="fa fa-info-circle" style="font-size:24px; margin-bottom:8px; display:block;"></i>No attendance records found for this month.</div>';
                }

                $('#members_att_cards').html(htm);
            } catch(e) {
                console.error(e);
                $('#members_att_cards').html('<div style="text-align:center; padding:40px 20px; color:#ef4444;">Error loading monthly attendance.</div>');
            }
        }).fail(function(){
            $('#members_att_cards').html('<div style="text-align:center; padding:40px 20px; color:#ef4444;">Failed to connect to server.</div>');
        });
    }

    function calcCokeReadings() {
        let start = parseInt($('#start_shuttle_no').val(), 10);
        let end = parseInt($('#end_shuttle_no').val(), 10);
        let used = 0;
        if (!isNaN(start) && !isNaN(end) && end >= start) {
            used = (end + 1) - start;
        }
        $('#coke_used_badge').text(used);

        let price = parseFloat($('#shuttle_price_input').val()) || 0;
        let totalCost = used * price;
        $('#coke_total_cost_badge').text('₹' + totalCost.toLocaleString('en-IN', {minimumFractionDigits: 2, maximumFractionDigits: 2}));
    }

    function saveCokeReadings() {
        let group_id = $('#rep_group').val() || 0;
        let month = $('#rep_month').val();
        if (!month) return;

        let startVal = $('#start_shuttle_no').val();
        let endVal = $('#end_shuttle_no').val();
        let priceVal = $('#shuttle_price_input').val();

        $.post('../api/monthly_attendance_report.php', {
            action: 'save_shuttle_readings',
            group_id: group_id,
            month: month,
            start_shuttle: startVal,
            end_shuttle: endVal,
            shuttle_price: priceVal,
            avg_shuttle_price: priceVal
        }, function(res) {
            try {
                let data = typeof res === 'string' ? JSON.parse(res) : res;
                if (data.success) {
                    if (typeof swal !== 'undefined') {
                        swal("Success", "Coke readings saved successfully.", "success");
                    } else {
                        alert("Coke readings saved successfully.");
                    }
                } else {
                    if (typeof swal !== 'undefined') {
                        swal("Error", data.error || "Failed to save coke readings.", "error");
                    } else {
                        alert(data.error || "Failed to save coke readings.");
                    }
                }
            } catch(e) {
                alert("Error saving coke readings.");
            }
        });
    }

    function checkFixedStatus(month) {
        $.post('../api/monthly_attendance_report.php', { action: 'check_fixed_status', month: month }, function(res) {
            try {
                let data = typeof res === 'string' ? JSON.parse(res) : res;
                if (data.is_fixed) {
                    $('#btn_fix_attendance').html('<i class="fa fa-lock"></i> Attendance Fixed')
                        .css({'background': '#94a3b8', 'cursor': 'not-allowed', 'box-shadow': 'none'})
                        .prop('disabled', true);
                } else {
                    $('#btn_fix_attendance').html('<i class="fa fa-wrench"></i> Fix Attendance')
                        .css({'background': 'linear-gradient(135deg, #4f46e5, #3b82f6)', 'cursor': 'pointer', 'box-shadow': '0 4px 12px rgba(79,70,229,0.22)'})
                        .prop('disabled', false);
                }
            } catch(e) {}
        });
    }

    function toggleFixAttendance() {
        let month = $('#rep_month').val();
        if (!month) return;

        if (typeof swal !== 'undefined') {
            swal({
                title: "Fix Attendance?",
                text: "Are you sure you want to fix/lock attendance for " + month + "? Once fixed, attendance for this month cannot be changed.",
                type: "warning",
                showCancelButton: true,
                confirmButtonColor: "#4f46e5",
                confirmButtonText: "Yes, Fix Attendance",
                closeOnConfirm: false
            }, function() {
                $.post('../api/monthly_attendance_report.php', { action: 'toggle_fixed_status', month: month }, function(res) {
                    try {
                        let data = typeof res === 'string' ? JSON.parse(res) : res;
                        if (data.success) {
                            swal("Fixed!", data.message || "Attendance has been fixed successfully.", "success");
                            checkFixedStatus(month);
                        } else {
                            swal("Cannot Fix", data.error || "Could not fix attendance.", "warning");
                        }
                    } catch(e) {
                        swal("Error", "Server error while fixing attendance.", "error");
                    }
                });
            });
        } else {
            if (confirm("Are you sure you want to fix attendance for " + month + "?")) {
                $.post('../api/monthly_attendance_report.php', { action: 'toggle_fixed_status', month: month }, function(res) {
                    try {
                        let data = typeof res === 'string' ? JSON.parse(res) : res;
                        alert(data.message || data.error);
                        checkFixedStatus(month);
                    } catch(e) {}
                });
            }
        }
    }
    </script>
</body>
</html>
