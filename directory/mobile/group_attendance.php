<?php
session_start();
include '../../app_common/db_connect.php';
include '../../app_common/auth_helper.php';

if (empty($_SESSION['login_id'])) {
    header("Location: ../../index.php");
    exit();
}

$login_id = (int)$_SESSION['login_id'];
$is_admin = isSuperAdmin($login_id) || isGroupAdmin($login_id) || isAttendanceMaster($login_id);
if (!$is_admin) {
    header("Location: home.php");
    exit();
}

$allowed_groups = getUserAllowedGroupIds($login_id);
$primary_role = getUserPrimaryRoleName($login_id);

$active_tab = 'attendance';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
    <title>Group Attendance Marking - YMCA Mobile</title>
    
    <link href="../../css/bootstrap.min.css" rel="stylesheet">
    <link href="../../font-awesome/css/font-awesome.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <link href="../../css/plugins/sweetalert/sweetalert.css" rel="stylesheet">
    <link href="mobile_layout.css" rel="stylesheet">

    <style>
        body { font-family: 'Plus Jakarta Sans', sans-serif !important; background: #f8fafc !important; }
        
        .grp-att-hero {
            background: linear-gradient(135deg, #059669 0%, #10b981 100%);
            border-radius: 20px;
            padding: 18px 20px;
            color: #ffffff;
            margin-bottom: 16px;
            box-shadow: 0 8px 20px rgba(16, 185, 129, 0.2);
        }
        .grp-att-hero h2 { margin: 0 0 4px 0; font-weight: 800; font-size: 20px; }
        .grp-att-hero p { margin: 0; font-size: 12.5px; opacity: 0.9; }

        .grp-controls {
            background: #ffffff;
            border-radius: 16px;
            border: 1px solid #e2e8f0;
            padding: 14px;
            margin-bottom: 14px;
            box-shadow: 0 2px 8px rgba(0,0,0,0.02);
        }

        .grp-control-field {
            display: flex;
            flex-direction: column;
            gap: 4px;
            margin-bottom: 10px;
        }

        .grp-control-field:last-child { margin-bottom: 0; }

        .grp-control-field label {
            font-size: 11px;
            font-weight: 700;
            color: #64748b;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            margin: 0;
        }

        .grp-control-field select, .grp-control-field input[type="date"] {
            padding: 10px 14px;
            border-radius: 12px;
            border: 1.5px solid #cbd5e1;
            background: #f8fafc;
            font-size: 13.5px;
            font-weight: 600;
            color: #0f172a;
            outline: none;
        }

        .quick-actions-bar {
            display: flex;
            gap: 8px;
            margin-bottom: 14px;
        }

        .btn-quick-toggle {
            flex: 1;
            padding: 10px 8px;
            border-radius: 12px;
            border: 1px solid #e2e8f0;
            background: #ffffff;
            font-size: 12px;
            font-weight: 700;
            color: #475569;
            cursor: pointer;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 5px;
            box-shadow: 0 2px 6px rgba(0,0,0,0.02);
        }

        .btn-quick-toggle.all-present { color: #059669; border-color: #a7f3d0; background: #ecfdf5; }
        .btn-quick-toggle.all-absent { color: #dc2626; border-color: #fecaca; background: #fef2f2; }

        .mob-member-att-card {
            background: #ffffff;
            border-radius: 16px;
            border: 1.5px solid #e2e8f0;
            padding: 14px;
            margin-bottom: 10px;
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 10px;
            transition: all 0.2s;
        }

        .mob-member-att-card.is-present {
            border-color: #10b981;
            background: #f0fdf4;
        }

        .mob-member-att-card.is-absent {
            border-color: #f43f5e;
            background: #fff1f2;
        }

        .mem-att-info {
            display: flex;
            align-items: center;
            gap: 12px;
            flex: 1;
        }

        .mem-att-avatar {
            width: 44px;
            height: 44px;
            border-radius: 12px;
            object-fit: cover;
            background: #e2e8f0;
            flex-shrink: 0;
        }

        .mem-att-name {
            font-size: 14px;
            font-weight: 800;
            color: #0f172a;
            margin: 0;
            line-height: 1.25;
        }

        .mem-att-status-lbl {
            font-size: 11px;
            font-weight: 700;
            margin-top: 2px;
        }

        .att-toggle-btns {
            display: flex;
            gap: 6px;
        }

        .btn-status-pill {
            padding: 8px 14px;
            border-radius: 10px;
            border: 1.5px solid #cbd5e1;
            background: #ffffff;
            color: #64748b;
            font-size: 12px;
            font-weight: 800;
            cursor: pointer;
            transition: all 0.18s;
        }

        .btn-status-pill.btn-p.active {
            background: #10b981;
            color: #ffffff;
            border-color: #10b981;
            box-shadow: 0 4px 10px rgba(16,185,129,0.3);
        }

        .btn-status-pill.btn-a.active {
            background: #f43f5e;
            color: #ffffff;
            border-color: #f43f5e;
            box-shadow: 0 4px 10px rgba(244,63,94,0.3);
        }

        .mob-save-bar {
            position: fixed;
            bottom: var(--mob-nav-height);
            left: 0; right: 0;
            background: #ffffff;
            border-top: 1px solid #e2e8f0;
            padding: 12px 18px;
            box-shadow: 0 -4px 20px rgba(0,0,0,0.08);
            z-index: 980;
            display: flex;
            align-items: center;
            justify-content: space-between;
        }

        .btn-mob-save {
            width: 100%;
            padding: 14px;
            border-radius: 14px;
            border: none;
            background: linear-gradient(135deg, #059669, #10b981);
            color: #ffffff;
            font-size: 15px;
            font-weight: 800;
            cursor: pointer;
            box-shadow: 0 6px 18px rgba(16,185,129,0.35);
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 8px;
        }
    </style>
</head>
<body class="mob-body" style="padding-bottom: calc(var(--mob-nav-height) + 75px);">

    <!-- Header -->
    <header class="mob-header">
        <div class="mob-header-brand">
            <a href="home.php" style="color:#ffffff; margin-right:8px; font-size:18px;"><i class="fa fa-arrow-left"></i></a>
            <div class="mob-header-title">
                Group Attendance <span>Marking</span>
            </div>
        </div>
        <div class="mob-header-actions">
            <a href="../../app_login_manager/logout.php" class="mob-header-btn" title="Logout">
                <i class="fa fa-sign-out"></i>
            </a>
        </div>
    </header>

    <div class="mob-page">

        <!-- Hero Card -->
        <div class="grp-att-hero">
            <h2>Mark Group Attendance</h2>
            <p>Put attendance for all active members in your group</p>
        </div>

        <!-- Controls Card -->
        <div class="grp-controls">
            <div class="grp-control-field">
                <label>Select Group</label>
                <select id="grp_att_group" onchange="loadGroupMembers()"></select>
            </div>
            <div class="grp-control-field">
                <label>Attendance Date</label>
                <input type="date" id="grp_att_date" value="<?php echo date('Y-m-d'); ?>" onchange="loadGroupMembers()">
            </div>
        </div>

        <!-- Holiday / Lock Alert Banner -->
        <div id="grp_att_holiday_alert" style="display:none;"></div>

        <!-- Quick Toggles -->
        <div class="quick-actions-bar">
            <button class="btn-quick-toggle all-present" onclick="setAllAttendance(true)">
                <i class="fa fa-check-circle"></i> All Present
            </button>
            <button class="btn-quick-toggle all-absent" onclick="setAllAttendance(false)">
                <i class="fa fa-times-circle"></i> All Absent
            </button>
        </div>

        <!-- Search Input -->
        <div style="margin-bottom:12px;">
            <input type="text" id="att_member_search" placeholder="Search member name..." onkeyup="filterMemberRows()" style="width:100%; padding:10px 14px; border-radius:12px; border:1px solid #cbd5e1; font-size:13.5px; font-weight:600; outline:none;">
        </div>

        <!-- Members Attendance Cards List -->
        <div id="grp_members_att_list">
            <div style="text-align:center; padding:40px 20px; color:#94a3b8;">
                <i class="fa fa-spinner fa-spin" style="font-size:24px; margin-bottom:8px;"></i><br>
                Loading group members...
            </div>
        </div>

    </div>

    <!-- Floating Save Bar -->
    <div class="mob-save-bar">
        <button class="btn-mob-save" onclick="saveGroupAttendance()">
            <i class="fa fa-save"></i> Save Group Attendance (<span id="present_count_lbl">0</span> Present)
        </button>
    </div>

    <!-- Mobile Bottom Navigation (5 Tabs) -->
    <?php include 'mobile_bottom_nav.php'; ?>

    <script src="../../js/jquery-3.1.1.min.js"></script>
    <script src="../../js/bootstrap.min.js"></script>
    <script src="../../js/plugins/sweetalert/sweetalert.min.js"></script>

    <script>
    let memberAttendanceState = {}; // member_id -> true (present) / false (absent)
    let isCurrentHoliday = false;
    let isCurrentFixed = false;

    $(document).ready(function() {
        loadGroups();
    });

    function loadGroups() {
        $.post('../api/attendance.php', { action: 'load_groups' }, function(data) {
            try {
                let parsed = typeof data === 'string' ? JSON.parse(data) : data;
                let groups = Array.isArray(parsed[0]) ? parsed[0] : (Array.isArray(parsed) ? parsed : []);
                let htm = '';
                groups.forEach(function(g) {
                    htm += '<option value="' + g.id + '">' + g.name + '</option>';
                });
                $('#grp_att_group').html(htm);
                loadGroupMembers();
            } catch(e) {
                console.error(e);
            }
        });
    }

    function loadGroupMembers() {
        let group_id = $('#grp_att_group').val();
        let date_search = $('#grp_att_date').val();
        if (!group_id) return;

        $('#grp_members_att_list').html('<div style="text-align:center; padding:40px 20px; color:#94a3b8;"><i class="fa fa-spinner fa-spin" style="font-size:24px; margin-bottom:8px;"></i><br>Loading members...</div>');

        // Check holiday & locked status first
        $.post('../api/attendance.php', { action: 'check_holiday', group: group_id, date: date_search }, function(resHoliday) {
            let rHol = typeof resHoliday === 'string' ? JSON.parse(resHoliday) : resHoliday;
            isCurrentHoliday = !!rHol.is_holiday;
            isCurrentFixed = !!rHol.is_fixed;

            if (isCurrentHoliday) {
                $('#grp_att_holiday_alert').html(`
                    <div style="background:#fff1f2; border:1.5px solid #f43f5e; border-radius:16px; padding:16px; margin-bottom:14px; display:flex; align-items:center; gap:12px; color:#be123c; box-shadow:0 4px 12px rgba(244,63,94,0.15);">
                        <span style="width:42px; height:42px; border-radius:12px; background:#fecdd3; display:flex; align-items:center; justify-content:center; font-size:20px; color:#e11d48; flex-shrink:0;"><i class="fa fa-calendar-times-o"></i></span>
                        <div>
                            <div style="font-weight:800; font-size:15px; color:#9f1239;">Holiday / Leave Declared</div>
                            <div style="font-size:12px; font-weight:600; color:#be123c; margin-top:2px;">Attendance cannot be marked for this session because it is marked as a Holiday.</div>
                        </div>
                    </div>
                `).show();

                $('.quick-actions-bar').hide();
                $('.mob-save-bar').hide();
            } else if (isCurrentFixed) {
                $('#grp_att_holiday_alert').html(`
                    <div style="background:#fefce8; border:1.5px solid #eab308; border-radius:16px; padding:14px; margin-bottom:14px; color:#854d0e; font-size:13px; font-weight:700;">
                        <i class="fa fa-lock" style="font-size:16px; margin-right:6px; color:#ca8a04;"></i> Attendance for this month is fixed and locked.
                    </div>
                `).show();

                $('.quick-actions-bar').show();
                $('.mob-save-bar').show();
            } else {
                $('#grp_att_holiday_alert').hide().empty();
                $('.quick-actions-bar').show();
                $('.mob-save-bar').show();
            }

            // Step 1: Fetch all members in the group
            $.post('../api/attendance.php', { action: 'load_member_data', val: group_id }, function(data1) {
                try {
                    let parsed1 = typeof data1 === 'string' ? JSON.parse(data1) : data1;
                    let members = Array.isArray(parsed1[0]) ? parsed1[0] : (Array.isArray(parsed1) ? parsed1 : []);

                    if (members.length === 0) {
                        $('#grp_members_att_list').html('<div style="text-align:center; padding:40px 20px; color:#94a3b8;">No active members in this group.</div>');
                        updateSummary();
                        return;
                    }

                    // Step 2: Fetch marked attendance details for the selected date
                    $.post('../api/attendance.php', { action: 'fetch_Attendance_details', group: group_id, date: date_search }, function(data2) {
                        let markedMembers = [];
                        try {
                            let parsed2 = typeof data2 === 'string' ? JSON.parse(data2) : data2;
                            let attRows = Array.isArray(parsed2) ? parsed2 : [];
                            attRows.forEach(function(r) { markedMembers.push(parseInt(r.member_id)); });
                        } catch(e) {}

                        // Sort members: Present members first, then higher attendance count, then alphabetical
                        members.sort(function(a, b) {
                            let aId = parseInt(a.id);
                            let bId = parseInt(b.id);
                            let aPres = markedMembers.includes(aId) ? 1 : 0;
                            let bPres = markedMembers.includes(bId) ? 1 : 0;

                            if (aPres !== bPres) {
                                return bPres - aPres; // Present (1) first
                            }
                            
                            let aAtt = parseInt(a.total_att || 0);
                            let bAtt = parseInt(b.total_att || 0);
                            if (aAtt !== bAtt) {
                                return bAtt - aAtt; // Higher overall attendance first
                            }

                            let aName = (a.first_name || '').toLowerCase();
                            let bName = (b.first_name || '').toLowerCase();
                            return aName.localeCompare(bName);
                        });

                        let htm = '';
                        memberAttendanceState = {};

                        members.forEach(function(m) {
                            let mId = parseInt(m.id);
                            let isPresent = markedMembers.includes(mId);
                            memberAttendanceState[mId] = isPresent;

                            let fullName = (m.first_name || '') + ' ' + (m.middle_name || '') + ' ' + (m.last_name || '');
                            fullName = fullName.replace(/\s+/g, ' ').trim();
                            let img_src = m.img && m.img !== '0' ? "../../image_upload/members/uploads/" + m.img : "../../img/customer.png";
                            let btnDisabledAttr = isCurrentHoliday ? 'disabled style="opacity:0.5; cursor:not-allowed;"' : '';

                            htm += `
                                <div class="mob-member-att-card member-att-row ${isPresent ? 'is-present' : 'is-absent'}" id="card_m_${mId}" data-name="${fullName.toLowerCase()}">
                                    <div class="mem-att-info">
                                        <img src="${img_src}" class="mem-att-avatar" onerror="this.onerror=null; this.src='../../img/customer.png';">
                                        <div>
                                            <h4 class="mem-att-name">${fullName}</h4>
                                            <div class="mem-att-status-lbl" id="status_lbl_${mId}" style="color:${isCurrentHoliday ? '#e11d48' : (isPresent ? '#059669' : '#e11d48')};">
                                                ${isCurrentHoliday ? '🌙 Holiday' : (isPresent ? '🟢 Present' : '🔴 Absent')}
                                            </div>
                                        </div>
                                    </div>
                                    <div class="att-toggle-btns">
                                        <button class="btn-status-pill btn-p ${isPresent && !isCurrentHoliday ? 'active' : ''}" id="btn_p_${mId}" ${btnDisabledAttr} onclick="toggleMemberAtt(${mId}, true)">P</button>
                                        <button class="btn-status-pill btn-a ${!isPresent || isCurrentHoliday ? 'active' : ''}" id="btn_a_${mId}" ${btnDisabledAttr} onclick="toggleMemberAtt(${mId}, false)">A</button>
                                    </div>
                                </div>
                            `;
                        });

                        $('#grp_members_att_list').html(htm);
                        updateSummary();
                    });
                } catch(e) {
                    console.error(e);
                    $('#grp_members_att_list').html('<div style="text-align:center; padding:40px 20px; color:#ef4444;">Error loading members.</div>');
                }
            });
        });
    }

    function toggleMemberAtt(memberId, isPresent) {
        if (isCurrentHoliday) {
            swal('Holiday Declared', 'Attendance cannot be marked or changed on a holiday date.', 'warning');
            return;
        }

        memberAttendanceState[memberId] = isPresent;
        let card = $('#card_m_' + memberId);
        let lbl = $('#status_lbl_' + memberId);
        let btnP = $('#btn_p_' + memberId);
        let btnA = $('#btn_a_' + memberId);

        if (isPresent) {
            card.removeClass('is-absent').addClass('is-present');
            lbl.css('color', '#059669').text('🟢 Present');
            btnP.addClass('active');
            btnA.removeClass('active');
        } else {
            card.removeClass('is-present').addClass('is-absent');
            lbl.css('color', '#e11d48').text('🔴 Absent');
            btnA.addClass('active');
            btnP.removeClass('active');
        }

        updateSummary();
    }

    function setAllAttendance(isPresent) {
        if (isCurrentHoliday) {
            swal('Holiday Declared', 'Attendance cannot be marked or changed on a holiday date.', 'warning');
            return;
        }
        Object.keys(memberAttendanceState).forEach(function(mId) {
            toggleMemberAtt(mId, isPresent);
        });
    }

    function updateSummary() {
        let presentCount = 0;
        Object.values(memberAttendanceState).forEach(function(st) {
            if (st === true) presentCount++;
        });
        $('#present_count_lbl').text(presentCount);
    }

    function filterMemberRows() {
        let q = $('#att_member_search').val().toLowerCase();
        $('.member-att-row').each(function() {
            let name = $(this).attr('data-name');
            if (name.indexOf(q) !== -1) {
                $(this).show();
            } else {
                $(this).hide();
            }
        });
    }

    function saveGroupAttendance() {
        if (isCurrentHoliday) {
            swal('Holiday Declared', 'Attendance cannot be marked or saved on a holiday date.', 'warning');
            return;
        }

        let group_id = $('#grp_att_group').val();
        let date_search = $('#grp_att_date').val();

        let presentMemberIds = [];
        Object.keys(memberAttendanceState).forEach(function(mId) {
            if (memberAttendanceState[mId] === true) {
                presentMemberIds.push(parseInt(mId));
            }
        });

        if (typeof load_overlay === 'function') load_overlay();

        $.post('../api/attendance.php', {
            action: 'add_attendance',
            val: group_id,
            date: date_search,
            member_ids: presentMemberIds
        }, function(response) {
            if (typeof close_overlay === 'function') close_overlay();
            swal('Saved!', 'Group attendance recorded successfully.', 'success');
        }).fail(function(xhr) {
            if (typeof close_overlay === 'function') close_overlay();
            let msg = xhr.responseText || 'Could not save attendance. Please try again.';
            swal('Error', msg, 'error');
        });
    }
    </script>
</body>
</html>
