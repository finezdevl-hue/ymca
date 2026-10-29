<?php
session_start();
include '../../app_common/db_connect.php';
include '../../app_common/auth_helper.php';

if (empty($_SESSION['login_id'])) {
    header("Location: ../../index.php");
    exit();
}

$login_id = (int)$_SESSION['login_id'];
$is_group_admin = isSuperAdmin($login_id) || isGroupAdmin($login_id);
$is_only_attendance_master = !$is_group_admin && (isAttendanceMaster($login_id) || isExecutiveMember($login_id));
$is_admin = $is_group_admin || isAttendanceMaster($login_id) || isExecutiveMember($login_id);
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

        @media (max-width: 360px) {
            .grp-controls {
                padding: 10px !important;
            }
            .grp-control-field {
                padding: 8px 10px !important;
            }
            #grp_shuttle_prev_note_section {
                padding: 8px 10px !important;
            }
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
                <input type="date" id="grp_att_date" value="<?php echo date('Y-m-d'); ?>" <?php if ($is_only_attendance_master) { echo 'min="' . date('Y-m-d') . '"'; } ?> onchange="loadGroupMembers()">
            </div>
            <?php if ($is_group_admin) { ?>
            <div class="grp-control-field" style="margin-top: 8px; background: #fef2f2; border: 1.5px solid #ef4444; padding: 10px 12px; border-radius: 14px; box-shadow: 0 2px 8px rgba(239, 68, 68, 0.12); text-align: left;">
                <label style="color: #b91c1c; font-weight: 800; text-align: left;">🔴 Shuttles Used After Play <span id="grp_stock_status_lbl" style="display:none;"></span></label>
                <div id="grp_shuttle_creator_notice" style="display:none; font-size:11.5px; font-weight:700; color:#1d4ed8; margin-bottom:6px; background:rgba(37,99,235,0.08); padding:4px 8px; border-radius:6px; border:1px solid #bfdbfe; text-align: left;"></div>
                <!-- Optional Short Note Field (Above the save & count row) -->
                <div style="position:relative; width:100%; margin-bottom:8px;">
                    <i class="fa fa-pencil" style="position:absolute; left:11px; top:50%; transform:translateY(-50%); font-size:12px; color:#ef4444; pointer-events:none;"></i>
                    <input type="text" id="grp_shuttle_notes" maxlength="150" placeholder="Short note (optional, e.g. brand, broken, court)..." style="width:100%; height:34px; padding:6px 12px 6px 32px; border-radius:10px; border:1.5px solid #fca5a5; background:#ffffff; font-size:12px; font-weight:600; color:#7f1d1d; outline:none; box-sizing:border-box; box-shadow:inset 0 1px 2px rgba(0,0,0,0.03); font-family:'Inter',sans-serif;">
                </div>
                <div style="display:flex; align-items:center; justify-content:space-between; gap:6px; flex-wrap:wrap;">
                    <div style="display:flex; align-items:center; gap:5px; flex-shrink:0;">
                        <!-- Vertical Stock Box on the left side of [-] [ 0 ] [+] -->
                        <div id="grp_stock_box" style="height:36px; min-width:38px; padding:2px 6px; border-radius:9px; border:1.5px solid #059669; background:#fff; display:flex; flex-direction:column; align-items:center; justify-content:center; user-select:none; box-shadow:0 1px 3px rgba(0,0,0,0.06); cursor:default; flex-shrink:0; margin-right:4px;" title="Remaining stock of shuttles">
                            <div id="grp_stock_box_lbl" style="font-size:8px; font-weight:800; letter-spacing:0.5px; text-transform:uppercase; line-height:1; color:#059669;">STOCK</div>
                            <div id="grp_stock_box_val" style="font-size:14px; font-weight:900; line-height:1.1; color:#059669; margin-top:1px;">0</div>
                        </div>
                        <button type="button" onclick="adjustShuttleCount('grp_shuttles_used', -1)" style="width:34px; height:36px; border-radius:9px; border:1.5px solid #fca5a5; background:#fff; color:#991b1b; font-weight:900; font-size:19px; display:flex; align-items:center; justify-content:center; cursor:pointer; padding:0; user-select:none; flex-shrink:0;">-</button>
                        <input type="number" id="grp_shuttles_used" min="0" max="6" placeholder="0" readonly style="width:42px; height:36px; padding:2px; border-radius:9px; border:1.5px solid #fca5a5; background:#fff; font-weight:900; font-size:16px; color:#991b1b; text-align:center; outline:none; cursor:default; flex-shrink:0;">
                        <button type="button" onclick="adjustShuttleCount('grp_shuttles_used', 1)" style="width:34px; height:36px; border-radius:9px; border:1.5px solid #fca5a5; background:#fff; color:#991b1b; font-weight:900; font-size:19px; display:flex; align-items:center; justify-content:center; cursor:pointer; padding:0; user-select:none; flex-shrink:0;">+</button>
                    </div>
                    <button type="button" onclick="saveGrpShuttleOnly()" style="height:36px; background:linear-gradient(135deg,#dc2626,#b91c1c); color:#fff; border:none; border-radius:9px; padding:0 14px; font-size:13px; font-weight:800; cursor:pointer; display:inline-flex; align-items:center; justify-content:center; gap:4px; box-shadow:0 3px 8px rgba(220,38,38,0.25); flex-shrink:0;" title="Save only Shuttle Count">
                        <i class="fa fa-save"></i> Save
                    </button>
                </div>
            </div>

            <!-- Previous Added Note Section -->
            <div id="grp_shuttle_prev_note_section" style="display:none; margin-top:8px; background:#fffdf7; border:1.5px solid #fed7aa; border-left:4px solid #f97316; border-radius:14px; padding:10px 12px; box-shadow:0 2px 8px rgba(249,115,22,0.08); font-family:'Inter',sans-serif; text-align:left;">
                <div style="display:flex; align-items:center; justify-content:space-between; gap:6px; margin-bottom:4px;">
                    <div style="font-size:11px; font-weight:800; color:#c2410c; text-transform:uppercase; letter-spacing:0.5px; display:inline-flex; align-items:center; gap:5px; white-space:nowrap; flex-shrink:0;">
                        <i class="fa fa-sticky-note" style="font-size:12px; color:#ea580c;"></i> <span id="grp_shuttle_prev_note_label" style="white-space:nowrap;">Previous Added Note</span>
                    </div>
                    <button type="button" id="grp_btn_use_note" onclick="copyGrpShuttleNoteToInput()" style="display:none; background:#ffedd5; border:1px solid #fed7aa; color:#9a3412; border-radius:6px; padding:2px 8px; font-size:10.5px; font-weight:700; cursor:pointer; align-items:center; gap:3px; white-space:nowrap; flex-shrink:0;" title="Copy note into input field">
                        <i class="fa fa-copy"></i> Use
                    </button>
                </div>
                <div style="margin-bottom:6px; text-align:left;">
                    <span id="grp_shuttle_prev_note_badge" style="font-size:10px; font-weight:700; color:#9a3412; background:#ffedd5; padding:2px 8px; border-radius:8px; display:inline-block; max-width:100%; word-break:break-word; text-align:left;"></span>
                </div>
                <div id="grp_shuttle_prev_note_text" style="font-size:12.5px; font-weight:600; color:#431407; line-height:1.45; word-break:break-word; text-align:left; background:rgba(254,215,170,0.18); padding:7px 10px; border-radius:8px; border:1px solid rgba(254,215,170,0.45);"></div>
                <div id="grp_shuttle_older_note_wrap" style="display:none; margin-top:8px; padding-top:7px; border-top:1px dashed #fed7aa; font-size:11px; color:#7c2d12; text-align:left;">
                    <div style="display:flex; align-items:center; justify-content:space-between; gap:6px; flex-wrap:wrap; margin-bottom:3px;">
                        <div style="font-weight:700; font-size:10.5px; color:#9a3412; white-space:nowrap;">
                            <i class="fa fa-history" style="color:#ea580c; margin-right:3px;"></i> Prior Session Note
                        </div>
                        <span id="grp_shuttle_older_note_badge" style="font-size:9.5px; font-weight:700; color:#9a3412; opacity:0.85; white-space:nowrap;"></span>
                    </div>
                    <div id="grp_shuttle_older_note_text" style="font-weight:600; font-size:12px; color:#431407; word-break:break-word; text-align:left; background:rgba(254,215,170,0.12); padding:5px 8px; border-radius:6px;"></div>
                </div>
            </div>
            <?php } ?>
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
    var mob_grp_stock_base = 0;
    var mob_grp_stock_remaining = 0;

    function updateGrpStockLabel(currentVal) {
        var val = parseInt(currentVal) || 0;
        var netRemaining = Math.max(0, mob_grp_stock_base - val);
        var isLow = (mob_grp_stock_base <= 0 || netRemaining < 3);
        var color = isLow ? '#dc2626' : '#059669';
        var bg = (isLow && netRemaining <= 0) ? '#fef2f2' : '#ffffff';

        $('#grp_stock_box').css({
            'border-color': color,
            'background-color': bg,
            'box-shadow': isLow ? '0 1px 3px rgba(220,38,38,0.2)' : '0 1px 3px rgba(5,150,105,0.2)'
        });
        $('#grp_stock_box_lbl').css('color', color);
        $('#grp_stock_box_val').css('color', color).text(netRemaining);

        if (mob_grp_stock_base <= 0 || netRemaining <= 0) {
            $('#grp_stock_box').attr('title', 'OUT OF STOCK (0 shuttles remaining)');
        } else {
            $('#grp_stock_box').attr('title', 'Remaining Stock: ' + netRemaining + ' shuttles');
        }
    }

    function adjustShuttleCount(inputId, delta) {
        var $el = $('#' + inputId);
        var val = parseInt($el.val()) || 0;
        val = Math.min(6, Math.max(0, val + delta));
        $el.val(val).trigger('change').trigger('input');
        if (inputId === 'grp_shuttles_used') {
            updateGrpStockLabel(val);
        }
    }

    function saveGrpShuttleOnly() {
        let group_id = $('#grp_att_group').val();
        let date_search = $('#grp_att_date').val();
        let shuttles_used = $('#grp_shuttles_used').val();
        let shuttle_notes = $('#grp_shuttle_notes').val() ? $('#grp_shuttle_notes').val().trim() : '';
        if (!group_id || !date_search) return;

        if (IS_ONLY_ATTENDANCE_MASTER && date_search < TODAY_DATE_STR) {
            swal('Restricted', 'Attendance Masters can only record for today.', 'warning');
            return;
        }

        let countVal = parseInt(shuttles_used) || 0;
        if (countVal > 0 && mob_grp_stock_remaining <= 0) {
            swal('Out of Stock!', 'Available shuttle cock inventory is 0. Please add inward stock first before recording shuttle count.', 'error');
            return;
        }
        if (countVal > mob_grp_stock_remaining && mob_grp_stock_remaining > 0) {
            swal('Insufficient Stock!', 'Only ' + mob_grp_stock_remaining + ' shuttles remaining in inventory.', 'error');
            return;
        }

        if (typeof load_overlay === 'function') load_overlay();
        $.post('../api/attendance.php', {
            action: 'save_shuttle_reading',
            group_id: group_id,
            date: date_search,
            shuttles_used: shuttles_used,
            notes: shuttle_notes
        }, function(res) {
            if (typeof close_overlay === 'function') close_overlay();
            swal('Saved!', 'Session shuttle count saved successfully.', 'success');
            loadGroupMembers();
        }).fail(function(xhr) {
            if (typeof close_overlay === 'function') close_overlay();
            let msg = xhr.responseText || 'Could not save shuttle count.';
            swal('Error', msg, 'error');
        });
    }

    const IS_ONLY_ATTENDANCE_MASTER = <?php echo $is_only_attendance_master ? 'true' : 'false'; ?>;
    const TODAY_DATE_STR = '<?php echo date('Y-m-d'); ?>';

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

        if (IS_ONLY_ATTENDANCE_MASTER && date_search < TODAY_DATE_STR) {
            swal('Restricted', 'Attendance Masters can only mark attendance for today.', 'warning');
            $('#grp_att_date').val(TODAY_DATE_STR);
            date_search = TODAY_DATE_STR;
        }

        $('#grp_members_att_list').html('<div style="text-align:center; padding:40px 20px; color:#94a3b8;"><i class="fa fa-spinner fa-spin" style="font-size:24px; margin-bottom:8px;"></i><br>Loading members...</div>');

        // Fetch shuttle reading & stock remaining for date & group
        $.post('../api/attendance.php', { action: 'get_shuttle_reading', group_id: group_id, date: date_search }, function(rSh) {
            try {
                let parsedSh = typeof rSh === 'string' ? JSON.parse(rSh) : rSh;
                if (parsedSh.status === 'success') {
                    mob_grp_stock_base = parseInt(parsedSh.stock_remaining || 0);
                    mob_grp_stock_remaining = mob_grp_stock_base;
                    var currentGrpUsed = parsedSh.shuttles_used > 0 ? parsedSh.shuttles_used : '';
                    $('#grp_shuttles_used').val(currentGrpUsed);
                    updateGrpStockLabel(currentGrpUsed);
                    $('#grp_shuttle_notes').val(parsedSh.notes || '');

                    if (parsedSh.has_record && (parsedSh.last_user_name || parsedSh.created_by_name)) {
                        var lastUser = parsedSh.last_user_name || parsedSh.created_by_name;
                        var creatorLabel = parsedSh.is_created_by_me ? 'You' : lastUser;
                        var actWord = parsedSh.action_word || (parsedSh.is_edited ? 'updated' : 'added');
                        var noticeHtml = '<i class="fa fa-info-circle" style="color:#2563eb; margin-right:4px;"></i> <strong>' + creatorLabel + '</strong> ' + actWord + ' count (' + parsedSh.shuttles_used + ' shuttles). Do you want to edit?';
                        $('#grp_shuttle_creator_notice').html(noticeHtml).show();
                    } else {
                        $('#grp_shuttle_creator_notice').hide().html('');
                    }

                    // Render Previous Added Note Section
                    var currentNote = (parsedSh.notes || '').trim();
                    var prevNote    = (parsedSh.prev_notes || '').trim();
                    var $grpNoteCard = $('#grp_shuttle_prev_note_section');
                    window._lastAvailableGrpShuttleNote = '';

                    if (currentNote !== '') {
                        window._lastAvailableGrpShuttleNote = currentNote;
                        var noteAuthor = parsedSh.is_created_by_me ? 'You' : (parsedSh.last_user_name || parsedSh.created_by_name || 'Admin');
                        var noteStatus = parsedSh.is_edited ? 'Updated' : 'Added';

                        $('#grp_shuttle_prev_note_label').text('Previously Added Note');
                        $('#grp_shuttle_prev_note_badge').text(noteAuthor + ' • ' + noteStatus).show();
                        $('#grp_shuttle_prev_note_text').text(currentNote);

                        if (prevNote !== '') {
                            var olderMeta = (parsedSh.prev_formatted_date || parsedSh.prev_reading_date || '');
                            if (parsedSh.prev_user_name) olderMeta += ' • ' + parsedSh.prev_user_name;
                            $('#grp_shuttle_older_note_text').text(prevNote);
                            $('#grp_shuttle_older_note_badge').text(olderMeta);
                            $('#grp_shuttle_older_note_wrap').show();
                        } else {
                            $('#grp_shuttle_older_note_wrap').hide();
                        }

                        if (!$('#grp_shuttle_notes').val()) {
                            $('#grp_btn_use_note').css('display', 'inline-flex');
                        } else {
                            $('#grp_btn_use_note').hide();
                        }

                        $grpNoteCard.fadeIn(150);
                    } else if (prevNote !== '') {
                        window._lastAvailableGrpShuttleNote = prevNote;
                        var prevMeta = (parsedSh.prev_formatted_date || parsedSh.prev_reading_date || '');
                        if (parsedSh.prev_user_name) prevMeta += ' • ' + parsedSh.prev_user_name;

                        $('#grp_shuttle_prev_note_label').text('Previous Added Note');
                        $('#grp_shuttle_prev_note_badge').text(prevMeta).show();
                        $('#grp_shuttle_prev_note_text').text(prevNote);
                        $('#grp_shuttle_older_note_wrap').hide();

                        if (!$('#grp_shuttle_notes').val()) {
                            $('#grp_btn_use_note').css('display', 'inline-flex');
                        } else {
                            $('#grp_btn_use_note').hide();
                        }

                        $grpNoteCard.fadeIn(150);
                    } else {
                        $grpNoteCard.hide();
                        $('#grp_btn_use_note').hide();
                    }
                }
            } catch(e){}
        });

    function copyGrpShuttleNoteToInput() {
        if (window._lastAvailableGrpShuttleNote) {
            $('#grp_shuttle_notes').val(window._lastAvailableGrpShuttleNote).focus();
            $('#grp_btn_use_note').hide();
        }
    }

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

                        // Sort members: Present members first, then previously present members, then higher attendance count, then alphabetical
                        members.sort(function(a, b) {
                            let aId = parseInt(a.id);
                            let bId = parseInt(b.id);
                            let aIsMarked = markedMembers.indexOf(aId) !== -1 ? 1 : 0;
                            let bIsMarked = markedMembers.indexOf(bId) !== -1 ? 1 : 0;

                            if (aIsMarked !== bIsMarked) {
                                return bIsMarked - aIsMarked;
                            }
                            let aPrev = parseInt(a.was_prev_present || 0);
                            let bPrev = parseInt(b.was_prev_present || 0);
                            if (aPrev !== bPrev) {
                                return bPrev - aPrev;
                            }
                            let aCnt = parseInt(a.total_att || a.attendance_count || 0);
                            let bCnt = parseInt(b.total_att || b.attendance_count || 0);
                            if (aCnt !== bCnt) {
                                return bCnt - aCnt;
                            }
                            let aName = (a.first_name || '') + ' ' + (a.last_name || '');
                            let bName = (b.first_name || '') + ' ' + (b.last_name || '');
                            return aName.localeCompare(bName);
                        });

                        memberAttendanceState = {};
                        let htm = '';

                        members.forEach(function(m) {
                            let mId = parseInt(m.id);
                            let isMarked = markedMembers.indexOf(mId) !== -1;
                            memberAttendanceState[mId] = isMarked;

                            let fullName = [m.first_name, m.middle_name, m.last_name].filter(function(p){ return p && p.trim(); }).join(' ');
                            let imgSrc = '../../img/customer.png';
                            if (m.img && m.img != '0' && typeof m.img === 'string' && m.img.trim() !== '') {
                                imgSrc = '../../image_upload/members/thumbnails/' + m.img;
                            }

                            let cardClass = isMarked ? 'is-present' : 'is-absent';
                            let statusText = isMarked ? '🟢 Present' : '🔴 Absent';
                            let statusColor = isMarked ? '#059669' : '#e11d48';

                            htm += `
                                <div class="mob-member-att-card ${cardClass} member-att-row" id="card_m_${mId}" data-name="${fullName.toLowerCase()}">
                                    <div class="mem-att-info">
                                        <img src="${imgSrc}" class="mem-att-avatar" onerror="this.src='../../img/customer.png'">
                                        <div>
                                            <div class="mem-att-name">${fullName}</div>
                                            <div class="mem-att-status-lbl" id="status_lbl_${mId}" style="color:${statusColor};">${statusText}</div>
                                        </div>
                                    </div>
                                    <div class="att-toggle-btns">
                                        <button class="btn-status-pill btn-p ${isMarked ? 'active' : ''}" id="btn_p_${mId}" onclick="toggleMemberAtt(${mId}, true)">P</button>
                                        <button class="btn-status-pill btn-a ${!isMarked ? 'active' : ''}" id="btn_a_${mId}" onclick="toggleMemberAtt(${mId}, false)">A</button>
                                    </div>
                                </div>
                            `;
                        });

                        $('#grp_members_att_list').html(htm);
                        updateSummary();
                    });
                } catch(e) {
                    console.error(e);
                    $('#grp_members_att_list').html('<div style="text-align:center; padding:40px 20px; color:#ef4444;">Error rendering members.</div>');
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
        if (IS_ONLY_ATTENDANCE_MASTER && date_search < TODAY_DATE_STR) {
            swal('Restricted', 'Attendance Masters can only mark attendance for today.', 'warning');
            return;
        }

        let hasShuttleField = $('#grp_shuttles_used').length > 0;
        let shuttles_used = hasShuttleField ? $('#grp_shuttles_used').val() : '';
        let shuttle_notes = $('#grp_shuttle_notes').length > 0 ? $('#grp_shuttle_notes').val().trim() : '';

        let presentMemberIds = [];
        Object.keys(memberAttendanceState).forEach(function(mId) {
            if (memberAttendanceState[mId] === true) {
                presentMemberIds.push(parseInt(mId));
            }
        });

        let countVal = parseInt(shuttles_used) || 0;
        if (hasShuttleField && countVal > 0 && mob_grp_stock_remaining <= 0) {
            swal('Out of Stock!', 'Available shuttle cock inventory is 0. Please add inward stock first before recording shuttle count.', 'error');
            return;
        }
        if (hasShuttleField && countVal > mob_grp_stock_remaining && mob_grp_stock_remaining > 0) {
            swal('Insufficient Stock!', 'Only ' + mob_grp_stock_remaining + ' shuttles remaining in inventory.', 'error');
            return;
        }

        if (typeof load_overlay === 'function') load_overlay();

        var postData = {
            action: 'add_attendance',
            val: group_id,
            date: date_search,
            member_ids: presentMemberIds
        };
        if (hasShuttleField && shuttles_used !== '') {
            postData.shuttles_used = shuttles_used;
            postData.notes = shuttle_notes;
        }

        $.post('../api/attendance.php', postData, function(response) {
            if (typeof close_overlay === 'function') close_overlay();
            if (!hasShuttleField) {
                swal('Saved!', 'Group attendance recorded successfully.', 'success');
            } else if (!shuttles_used || parseInt(shuttles_used) <= 0) {
                swal({
                    title: "Saved!",
                    text: "Group attendance recorded successfully.\n\n🔴 REMINDER: Don't forget to enter the count of shuttles used after today's game!",
                    type: "warning",
                    confirmButtonColor: "#dc2626",
                    confirmButtonText: "OK, I will add Shuttle Count!"
                });
            } else {
                swal('Saved!', 'Group attendance & Shuttle Count (' + shuttles_used + ') recorded successfully.', 'success');
            }
        }).fail(function(xhr) {
            if (typeof close_overlay === 'function') close_overlay();
            let msg = xhr.responseText || 'Could not save attendance. Please try again.';
            swal('Error', msg, 'error');
        });
    }
    </script>
</body>
</html>
