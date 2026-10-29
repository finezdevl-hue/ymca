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

$is_admin = isSuperAdmin($login_id) || isGroupAdmin($login_id);
$allowed_groups = getUserAllowedGroupIds($login_id);
$primary_role = getUserPrimaryRoleName($login_id);

$active_tab = 'accounts';

// Default compile month to last month
$default_month = date('Y-m', strtotime('first day of last month'));
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
    <title>Process Monthly Attendance - YMCA Mobile</title>
    
    <link href="../../css/bootstrap.min.css" rel="stylesheet">
    <link href="../../font-awesome/css/font-awesome.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <link href="mobile_layout.css" rel="stylesheet">
    <link href="../../css/plugins/sweetalert/sweetalert.css" rel="stylesheet">

    <style>
        body { font-family: 'Plus Jakarta Sans', sans-serif !important; background: #f8fafc !important; }
        .acc-hero {
            background: linear-gradient(135deg, #10b981 0%, #047857 100%);
            border-radius: 20px; padding: 18px 20px; color: #ffffff; margin-bottom: 16px;
            box-shadow: 0 8px 20px rgba(16, 185, 129, 0.2);
        }
        .acc-hero h2 { margin: 0 0 4px 0; font-weight: 800; font-size: 20px; }
        .acc-hero p { margin: 0; font-size: 12.5px; opacity: 0.9; }

        .ctrl-card {
            background: #ffffff; border-radius: 18px; border: 1px solid #e2e8f0; padding: 16px;
            margin-bottom: 16px; box-shadow: 0 2px 8px rgba(0,0,0,0.03);
        }
        
        .kpi-grid { display: grid; grid-template-columns: repeat(3, 1fr); gap: 10px; margin-bottom: 16px; }
        .kpi-card-sm {
            background: #ffffff; border-radius: 14px; border: 1px solid #e2e8f0; padding: 10px 12px;
            box-shadow: 0 2px 6px rgba(0,0,0,0.02); text-align: left;
        }
        .kpi-lbl { font-size: 9.5px; font-weight: 800; color: #64748b; text-transform: uppercase; letter-spacing: 0.3px; }
        .kpi-val { font-size: 14px; font-weight: 800; color: #0f172a; margin-top: 2px; }

        .att-row-card {
            background: #ffffff; border-radius: 16px; border: 1px solid #e2e8f0; padding: 14px;
            margin-bottom: 10px; box-shadow: 0 2px 6px rgba(0,0,0,0.02); display: flex; align-items: center; justify-content: space-between; gap: 12px;
        }
        .att-row-info { flex: 1; min-width: 0; }
        .att-row-name { font-size: 14px; font-weight: 800; color: #0f172a; margin: 0 0 2px 0; white-space: nowrap; overflow: hidden; text-overflow: ellipsis; }
        .att-row-sub { font-size: 11.5px; color: #64748b; font-weight: 600; }

        .pill-badge { font-size: 10.5px; font-weight: 800; padding: 4px 10px; border-radius: 8px; text-transform: uppercase; }
        .pill-done { background: #dcfce7; color: #166534; }
        .pill-pending { background: #fef3c7; color: #92400e; }

        .btn-action-primary {
            background: linear-gradient(135deg, #10b981 0%, #059669 100%); color: #ffffff; border: none;
            border-radius: 12px; padding: 10px 16px; font-size: 13px; font-weight: 800; width: 100%;
            box-shadow: 0 4px 10px rgba(16, 185, 129, 0.2); transition: all 0.2s ease;
        }
        .btn-action-secondary {
            background: linear-gradient(135deg, #6366f1 0%, #4f46e5 100%); color: #ffffff; border: none;
            border-radius: 12px; padding: 10px 16px; font-size: 13px; font-weight: 800; width: 100%;
            box-shadow: 0 4px 10px rgba(99, 102, 241, 0.2); transition: all 0.2s ease;
        }
    </style>
</head>
<body class="mob-body">

    <!-- Header -->
    <header class="mob-header">
        <div class="mob-header-brand">
            <a href="accounts.php" style="color:#ffffff; margin-right:8px; font-size:18px;"><i class="fa fa-arrow-left"></i></a>
            <div class="mob-header-title">
                Process <span>Monthly Attendance</span>
            </div>
        </div>
        <div class="mob-header-actions">
            <a href="../../app_login_manager/logout.php" class="mob-header-btn" title="Logout"><i class="fa fa-sign-out"></i></a>
        </div>
    </header>

    <div class="mob-page">

        <div class="acc-hero">
            <h2>Process Monthly Attendance</h2>
            <p>Compile attendance logs & generate monthly fee receivables</p>
        </div>

        <!-- Unprocessed Months Banner -->
        <div id="pending_months_wrapper" style="display:none; background:#fff7ed; border:1px solid #ffedd5; border-radius:16px; padding:14px; margin-bottom:14px; box-shadow:0 2px 6px rgba(249,115,22,0.05);">
            <div style="font-size:11px; font-weight:800; color:#c2410c; text-transform:uppercase; margin-bottom:8px; display:flex; align-items:center; gap:6px;">
                <i class="fa fa-exclamation-circle" style="font-size:14px;"></i> Unprocessed Attendance Months
            </div>
            <div id="pending_months_pills" style="display:flex; flex-wrap:wrap; gap:6px;"></div>
        </div>

        <!-- Controls Card -->
        <div class="ctrl-card">
            <div style="display:flex; flex-direction:column; gap:12px;">
                <div>
                    <label style="font-size:11px; font-weight:700; color:#64748b; text-transform:uppercase;">Select Month to Process</label>
                    <input type="month" id="proc_month" value="<?php echo $default_month; ?>" class="form-control" style="border-radius:10px; font-weight:600;" onchange="loadCompiledList()">
                </div>

                <!-- Fix Attendance Status & Action Bar -->
                <div style="background:#f8fafc; border:1px solid #e2e8f0; border-radius:14px; padding:12px; display:flex; align-items:center; justify-content:space-between; gap:10px;">
                    <div>
                        <div style="font-size:10px; font-weight:800; color:#64748b; text-transform:uppercase;">Attendance Lock Status</div>
                        <div id="fix_status_badge" style="font-size:12px; font-weight:800; color:#d97706; margin-top:2px;">
                            <i class="fa fa-unlock"></i> Checking Lock Status...
                        </div>
                    </div>
                    <button type="button" id="btn_fix_attendance" class="btn btn-warning btn-sm" onclick="goToFixAttendancePage()" style="border-radius:10px; font-weight:800; padding:7px 14px; white-space:nowrap; background:#f59e0b; border:none; color:#fff;">
                        <i class="fa fa-lock"></i> Fix Attendance
                    </button>
                </div>

                <div>
                    <label style="font-size:11px; font-weight:700; color:#64748b; text-transform:uppercase;">Select Group</label>
                    <select id="proc_group" class="form-control" style="border-radius:10px; font-weight:600;" onchange="loadCompiledList()">
                        <?php
                        $g_sql = "SELECT id, name FROM tbl_groups WHERE status = 1 ORDER BY name ASC";
                        if (!in_array('ALL', $allowed_groups, true) && !empty($allowed_groups)) {
                            $ph = implode(',', array_fill(0, count($allowed_groups), '?'));
                            $types = str_repeat('i', count($allowed_groups));
                            $g_res = app_exec_getresult("SELECT id, name FROM tbl_groups WHERE id IN ($ph) AND status = 1 ORDER BY name ASC", $allowed_groups, $types);
                        } else {
                            $g_res = app_exec_query($g_sql);
                        }
                        if ($g_res && $g_res->num_rows > 0) {
                            while ($grp = $g_res->fetch_assoc()) {
                                echo '<option value="' . $grp['id'] . '">' . htmlspecialchars($grp['name']) . '</option>';
                            }
                        } else {
                            $fallback_res = app_exec_query("SELECT id, name FROM tbl_groups WHERE status = 1 ORDER BY name ASC");
                            if ($fallback_res) {
                                while ($grp = $fallback_res->fetch_assoc()) {
                                    echo '<option value="' . $grp['id'] . '">' . htmlspecialchars($grp['name']) . '</option>';
                                }
                            }
                        }
                        ?>
                    </select>
                </div>
                <div style="display:flex; flex-direction:column; gap:8px; margin-top:4px;">
                    <button type="button" id="btn_process_monthly_att" class="btn-action-primary" onclick="compileMonthlyAttendance()">
                        <i class="fa fa-cogs"></i> Process Monthly Attendance & Receivables
                    </button>
                    <button type="button" id="btn_undo_monthly_att" class="btn btn-outline-danger" style="border-radius:12px; font-size:13px; font-weight:800; width:100%; border:1.5px solid #ef4444; color:#ef4444; background:#fff5f5; display:none;" onclick="undoMonthlyAttendanceMobile()">
                        <i class="fa fa-undo"></i> Undo Processed Month
                    </button>
                </div>
            </div>
        </div>

        <!-- Summary KPIs -->
        <div class="kpi-grid">
            <div class="kpi-card-sm" style="border-left: 3px solid #10b981;">
                <div class="kpi-lbl">Total Members</div>
                <div class="kpi-val" id="stat_members_count">0</div>
            </div>
            <div class="kpi-card-sm" style="border-left: 3px solid #3b82f6;">
                <div class="kpi-lbl">Total Attended</div>
                <div class="kpi-val" id="stat_attended_count" style="color:#2563eb;">0</div>
            </div>
            <div class="kpi-card-sm" style="border-left: 3px solid #8b5cf6;">
                <div class="kpi-lbl">Receivables</div>
                <div class="kpi-val" id="stat_receivable_count" style="color:#7c3aed;">0 Done</div>
            </div>
        </div>

        <!-- Roster List -->
        <div style="margin-bottom:12px; display:flex; justify-content:space-between; align-items:center;">
            <h4 style="font-size:14.5px; font-weight:800; color:#0f172a; margin:0;">Compiled Attendance Log</h4>
            <span style="font-size:11px; font-weight:700; color:#64748b;" id="lbl_month_tag">--</span>
        </div>

        <div id="compiled_roster_container">
            <div style="text-align:center; padding:30px; color:#94a3b8;">
                <i class="fa fa-spinner fa-spin" style="font-size:24px; margin-bottom:8px; display:block;"></i>
                Loading compiled monthly attendance...
            </div>
        </div>

    </div>

    <!-- Hidden Modal Elements for Receivables Generation -->
    <input type="hidden" id="hdn_head_id" value="1">
    <input type="hidden" id="hdn_flag_id" value="1">

    <!-- Mobile Bottom Navigation (5 Tabs) -->
    <?php include 'mobile_bottom_nav.php'; ?>

    <script src="../../js/jquery-3.1.1.min.js"></script>
    <script src="../../js/bootstrap.min.js"></script>
    <script src="../../js/plugins/sweetalert/sweetalert.min.js"></script>
    <script>
    let isCurrentMonthFixed = false;
    let isCurrentMonthProcessed = false;
    let isCurrentMonthEnded = false;

    function checkFixedStatus() {
        let monthVal = $('#proc_month').val();
        if (!monthVal) return;

        let parts = monthVal.split('-');
        let year = parseInt(parts[0], 10);
        let month = parseInt(parts[1], 10);
        let daysInMonth = new Date(year, month, 0).getDate();
        let monthEndStr = monthVal + '-' + (daysInMonth < 10 ? '0' + daysInMonth : daysInMonth);
        
        let todayStr = new Date().toISOString().slice(0, 10);
        
        // Month has ended if today's date > monthEndStr
        isCurrentMonthEnded = (todayStr > monthEndStr);

        if (!isCurrentMonthEnded) {
            isCurrentMonthFixed = false;
            $('#fix_status_badge').html('<span style="color:#64748b; font-weight:700;"><i class="fa fa-clock-o"></i> Month Ongoing (Processable after month ends)</span>');
            $('#btn_fix_attendance').html('<i class="fa fa-clock-o"></i> Month Ongoing').removeClass('btn-success btn-warning').addClass('btn-secondary').prop('disabled', true);
            return;
        }

        $.post('../api/monthly_attendance_report.php', { action: 'check_fixed_status', month: monthVal }, function(res) {
            try {
                let parsed = typeof res === 'string' ? JSON.parse(res) : res;
                if (parsed.is_fixed) {
                    isCurrentMonthFixed = true;
                    $('#fix_status_badge').html('<span style="color:#059669;"><i class="fa fa-lock"></i> Fixed & Locked</span>');
                    $('#btn_fix_attendance').html('<i class="fa fa-check-circle"></i> Attendance Fixed').removeClass('btn-warning btn-secondary').addClass('btn-success').prop('disabled', true);
                } else {
                    isCurrentMonthFixed = false;
                    $('#fix_status_badge').html('<span style="color:#10b981;"><i class="fa fa-calendar-check-o"></i> Ready to Process (Auto-locks previous month)</span>');
                    $('#btn_fix_attendance').html('<i class="fa fa-lock"></i> Lock Month Now').removeClass('btn-success btn-secondary').addClass('btn-warning').prop('disabled', false);
                }
            } catch(e) {}
        });
    }

    function goToFixAttendancePage() {
        let monthVal = $('#proc_month').val();
        let url = 'monthly_attendance.php';
        if (monthVal) {
            url += '?month=' + monthVal;
        }
        window.location.href = url;
    }

    function toggleFixAttendance() {
        goToFixAttendancePage();
    }

    function loadCompiledList() {
        checkFixedStatus();
        loadPendingMonths();
        let monthVal = $('#proc_month').val();
        let groupId = $('#proc_group').val() || 0;
        if (!monthVal) return;

        // Check explicit processed status
        $.post('../api/monthly_attendance.php', { action: 'check_processed_status', month: monthVal, group_id: groupId }, function(pRes) {
            try {
                let pData = typeof pRes === 'string' ? JSON.parse(pRes) : pRes;
                if (pData && pData.is_processed) {
                    isCurrentMonthProcessed = true;
                    $('#btn_process_monthly_att').removeClass('btn-action-primary').addClass('btn-secondary').prop('disabled', true).html('<i class="fa fa-check-circle"></i> Attendance & Receivables Processed');
                    $('#btn_undo_monthly_att').show();
                } else {
                    isCurrentMonthProcessed = false;
                    $('#btn_process_monthly_att').removeClass('btn-secondary').addClass('btn-action-primary').prop('disabled', false).html('<i class="fa fa-cogs"></i> Process Monthly Attendance & Receivables');
                    $('#btn_undo_monthly_att').hide();
                }
            } catch(e) {}
        });

        let year = monthVal.split('-')[0];
        let month = monthVal.split('-')[1];
        let daysInMonth = new Date(year, month, 0).getDate();
        let fromDate = monthVal + '-01';
        let toDate = monthVal + '-' + daysInMonth;

        $('#lbl_month_tag').text(new Date(fromDate).toLocaleDateString('en-US', { month: 'short', year: 'numeric' }));
        $('#compiled_roster_container').html('<div style="text-align:center; padding:30px; color:#94a3b8;"><i class="fa fa-spinner fa-spin" style="font-size:24px; margin-bottom:8px; display:block;"></i>Loading compiled attendance...</div>');

        $.post('../api/monthly_attendance.php', { action: 'load_data', page: 1, group_id: groupId, month: monthVal, no_limit: 1 }, function(res) {
            try {
                let parsed = typeof res === 'string' ? JSON.parse(res) : res;
                let items = parsed[1] || [];
                let htm = '';

                let totalAttended = 0;
                let doneCount = 0;

                if (!items || items.length === 0) {
                    $('#compiled_roster_container').html('<div style="text-align:center; padding:35px 20px; color:#94a3b8;"><i class="fa fa-info-circle" style="font-size:32px; margin-bottom:8px; display:block;"></i>No compiled monthly attendance records found. Click <strong>Process Monthly Attendance & Receivables</strong> above to compile logs.</div>');
                    $('#stat_members_count').text(0);
                    $('#stat_attended_count').text(0);
                    $('#stat_receivable_count').text('0 Done');
                    return;
                }

                isCurrentMonthProcessed = true;
                $('#btn_process_monthly_att').removeClass('btn-action-primary').addClass('btn-secondary').prop('disabled', true).html('<i class="fa fa-check-circle"></i> Attendance & Receivables Processed');

                items.forEach(function(r) {
                    let name = [r.first_name, r.middle_name, r.last_name].filter(function(p){ return p && p.trim(); }).join(' ');
                    let att = parseInt(r.attendance, 10) || 0;
                    totalAttended += att;
                    let isRec = parseInt(r.isreceiveble, 10) === 1;
                    if (isRec) doneCount++;

                    let statusClass = isRec ? 'pill-done' : (att > 0 ? 'pill-pending' : 'pill-done');
                    let statusText = isRec ? 'Receivable Generated' : (att > 0 ? 'Processing...' : 'No Fee (0 Days)');
                    let isGuest = parseInt(r.member_type, 10) === 1;
                    let guestBadge = isGuest ? '<span style="background:#fff7ed; color:#c2410c; border:1px solid #ffedd5; font-size:10px; font-weight:800; padding:2px 6px; border-radius:6px; margin-left:6px;"><i class="fa fa-user-circle"></i> GUEST</span>' : '';

                    let feeInputHtm = isGuest ? `
                        <div style="margin-top:6px; display:flex; align-items:center; gap:6px;">
                            <span style="font-size:11px; font-weight:700; color:#64748b;">Custom Fee: ₹</span>
                            <input type="number" class="form-control input-sm guest-fee-input" data-member-id="${r.member_id}" value="300" style="width:90px; height:28px; font-size:12px; font-weight:800; border-radius:6px; text-align:right;">
                        </div>
                    ` : '';

                    htm += `
                        <div class="att-row-card">
                            <div class="att-row-info">
                                <h4 class="att-row-name">${name} ${guestBadge}</h4>
                                <div class="att-row-sub">
                                    <span style="font-weight:800; color:#10b981;"><i class="fa fa-check-circle"></i> ${att} Days Attended</span>
                                    &bull; ${r.group_name || 'General'}
                                </div>
                                ${feeInputHtm}
                            </div>
                            <div>
                                <span class="pill-badge ${statusClass}">${statusText}</span>
                            </div>
                        </div>
                    `;
                });

                $('#stat_members_count').text(items.length);
                $('#stat_attended_count').text(totalAttended);
                $('#stat_receivable_count').text(doneCount + ' / ' + items.length + ' Done');

                $('#compiled_roster_container').html(htm);
            } catch(e) {
                $('#compiled_roster_container').html('<div style="text-align:center; padding:30px; color:#ef4444;">Error parsing attendance data.</div>');
            }
        });
    }

    function compileMonthlyAttendance() {
        let monthVal = $('#proc_month').val();
        let groupId = $('#proc_group').val() || 0;
        if (!monthVal) {
            alert('Please select a month.');
            return;
        }

        let parts = monthVal.split('-');
        let year = parseInt(parts[0], 10);
        let month = parseInt(parts[1], 10);
        let daysInMonth = new Date(year, month, 0).getDate();
        let fromDate = monthVal + '-01';
        let toDate = monthVal + '-' + (daysInMonth < 10 ? '0' + daysInMonth : daysInMonth);
        let monthLabel = new Date(fromDate).toLocaleDateString('en-US', { month: 'long', year: 'numeric' });

        // Rule 1: Check if month has ended
        if (!isCurrentMonthEnded) {
            if (typeof swal !== 'undefined') {
                swal("Month Not Ended Yet", "Attendance for " + monthLabel + " can only be processed after the month ends.", "warning");
            } else {
                alert("Attendance for " + monthLabel + " can only be processed after the month ends.");
            }
            return;
        }

        // Rule 2: Check if month was already processed
        if (isCurrentMonthProcessed) {
            if (typeof swal !== 'undefined') {
                swal("Already Processed", "Attendance for " + monthLabel + " has already been processed once and cannot be processed again.", "info");
            } else {
                alert("Attendance for " + monthLabel + " has already been processed once and cannot be processed again.");
            }
            return;
        }

        let executeCompile = function() {
            let guestFees = {};
            $('.guest-fee-input').each(function() {
                let mId = $(this).attr('data-member-id');
                let val = $(this).val();
                if (mId && val) {
                    guestFees[mId] = val;
                }
            });

            $.post('../api/monthly_attendance.php', {
                action: 'save_attendance',
                from_date: fromDate,
                to_date: toDate,
                group_id: groupId,
                custom_guest_fees: JSON.stringify(guestFees)
            }, function(res) {
                if (typeof swal !== 'undefined') {
                    swal({ title: "Processed!", text: "Monthly attendance processed and fee receivables generated successfully!", type: "success" }, function() {
                        loadCompiledList();
                    });
                } else {
                    alert('Monthly attendance processed and fee receivables generated successfully!');
                    loadCompiledList();
                }
            }).fail(function(xhr) {
                let errMsg = 'Error processing monthly attendance.';
                try {
                    let parsed = JSON.parse(xhr.responseText);
                    if (parsed.Message) errMsg = parsed.Message;
                } catch(e) {}

                if (typeof swal !== 'undefined') {
                    swal("Process Failed", errMsg, "error");
                } else {
                    alert(errMsg);
                }
            });
        };

        if (typeof swal !== 'undefined') {
            swal({
                title: "Process Attendance & Receivables?",
                text: "Compile attendance and automatically generate fee receivables for " + monthLabel + "?",
                type: "warning",
                showCancelButton: true,
                confirmButtonColor: "#10b981",
                confirmButtonText: "Yes, Process!",
                cancelButtonText: "Cancel",
                closeOnConfirm: false
            }, function(isConfirm) {
                if (isConfirm) executeCompile();
            });
        } else {
            if (confirm("Compile attendance and automatically generate fee receivables for " + monthLabel + "?")) {
                executeCompile();
            }
        }
    }

    function undoMonthlyAttendanceMobile() {
        let monthVal = $('#proc_month').val();
        let groupId = $('#proc_group').val() || 0;
        if (!monthVal) return;

        let parts = monthVal.split('-');
        let year = parseInt(parts[0], 10);
        let month = parseInt(parts[1], 10);
        let fromDateStr = year + "-" + ("0" + month).slice(-2) + "-01";
        let lastDayDate = new Date(year, month, 0);
        let toDateStr = year + "-" + ("0" + month).slice(-2) + "-" + ("0" + lastDayDate.getDate()).slice(-2);
        let monthNames = ["January", "February", "March", "April", "May", "June", "July", "August", "September", "October", "November", "December"];
        let monthLabel = monthNames[month - 1] + " " + year;

        let doUndo = function() {
            $.ajax({
                type: "POST",
                url: "../api/monthly_attendance.php",
                data: {
                    action: 'undo_monthly_attendance',
                    from_date: fromDateStr,
                    to_date: toDateStr,
                    group_id: groupId
                },
                success: function(response) {
                    if (typeof swal === 'function') {
                        swal("Success", "Attendance and receivables for " + monthLabel + " reset successfully", "success");
                    } else {
                        alert("Attendance and receivables for " + monthLabel + " reset successfully");
                    }
                    loadCompiledList();
                },
                error: function(xhr) {
                    try {
                        let msgObj = JSON.parse(xhr.responseText);
                        if (typeof swal === 'function') {
                            swal("Error", msgObj.Message || "Failed to reset", "error");
                        } else {
                            alert(msgObj.Message || "Failed to reset");
                        }
                    } catch(e) {
                        if (typeof swal === 'function') {
                            swal("Error", "Unexpected error occurred", "error");
                        } else {
                            alert("Unexpected error occurred");
                        }
                    }
                }
            });
        };

        if (typeof swal === 'function') {
            swal({
                title: "Undo Processed Month?",
                text: "Are you sure you want to reset attendance and uncollected fee receivables for " + monthLabel + "?",
                type: "warning",
                showCancelButton: true,
                confirmButtonColor: "#ef4444",
                confirmButtonText: "Yes, Reset & Undo",
                cancelButtonText: "Cancel",
                closeOnConfirm: false
            }, function(isConfirm) {
                if (isConfirm) doUndo();
            });
        } else {
            if (confirm("Are you sure you want to reset attendance and uncollected fee receivables for " + monthLabel + "?")) {
                doUndo();
            }
        }
    }

    function selectPendingMonth(mVal) {
        $('#proc_month').val(mVal);
        loadCompiledList();
    }

    function loadPendingMonths() {
        let groupId = $('#proc_group').val() || 0;
        $.post('../api/monthly_attendance.php', { action: 'load_pending_months', group_id: groupId }, function(res) {
            try {
                let parsed = typeof res === 'string' ? JSON.parse(res) : res;
                if (Array.isArray(parsed) && parsed.length > 0) {
                    let htm = '';
                    parsed.forEach(function(pm) {
                        htm += `<button type="button" class="btn btn-xs" onclick="selectPendingMonth('${pm.month_val}')" style="background:#f97316; color:#fff; font-weight:800; border-radius:8px; padding:5px 12px; border:none; font-size:11.5px; box-shadow:0 2px 6px rgba(249,115,22,0.2);">
                            <i class="fa fa-calendar"></i> ${pm.month_label} (${pm.group_name})
                        </button>`;
                    });
                    $('#pending_months_pills').html(htm);
                    $('#pending_months_wrapper').show();
                } else {
                    $('#pending_months_pills').html('');
                    $('#pending_months_wrapper').hide();
                }
            } catch(e) {
                $('#pending_months_wrapper').hide();
            }
        });
    }

    $(document).ready(function() {
        $.post('../api/attendance.php', { action: 'load_groups' }, function(data) {
            try {
                let parsed = typeof data === 'string' ? JSON.parse(data) : data;
                let groups = Array.isArray(parsed[0]) ? parsed[0] : (Array.isArray(parsed) ? parsed : []);
                if (groups && groups.length > 0) {
                    let htm = '';
                    groups.forEach(function(g) { htm += '<option value="' + g.id + '">' + g.name + '</option>'; });
                    $('#proc_group').html(htm);
                }
                loadCompiledList();
            } catch(e) {
                loadCompiledList();
            }
        });

        $.post('../api/payable.php', { action: 'load_closing_years' }, function(data) {
            try {
                let parsed = typeof data === 'string' ? JSON.parse(data) : data;
                let years = Array.isArray(parsed[0]) ? parsed[0] : (Array.isArray(parsed) ? parsed : []);
                if (years.length > 0) $('#hdn_flag_id').val(years[0].id);
            } catch(e) {}
        });

        $.post('../api/monthly_attendance.php', { action: 'load_heads' }, function(data) {
            try {
                let parsed = typeof data === 'string' ? JSON.parse(data) : data;
                let heads = Array.isArray(parsed[0]) ? parsed[0] : (Array.isArray(parsed) ? parsed : []);
                if (heads.length > 0) $('#hdn_head_id').val(heads[0].id);
            } catch(e) {}
        });
    });
    </script>
</body>
</html>
