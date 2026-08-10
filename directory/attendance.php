<?php
session_start();

if(isset($_POST['id'])){
    $_SESSION['id']=$_POST['id'];
}

// Determine if user has admin attendance permission
include_once '../app_common/auth_helper.php';

$is_admin = false;
if (!empty($_SESSION['login_id'])) {
    $login_id = (int)$_SESSION['login_id'];
    if (isSuperAdmin($login_id) || isGroupAdmin($login_id) || isAttendanceMaster($login_id)) {
        $is_admin = true;
    }
}

// Redirect members to dedicated Member Attendance Page
if (!$is_admin) {
    header("Location: user_attendance.php");
    exit();
}
// Release session lock so AJAX API calls can start their own sessions without blocking
session_write_close();
?>
<!DOCTYPE html>
<html>

<head>

    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="description" content="Mark Attendance - YMCA Management System">
    <link rel="shortcut icon" href="../favicon.ico" type="image/x-icon">
    <link rel="manifest" href="../manifest.json">

    <title>Mark Attendance</title>

    <link href="../css/bootstrap.min.css" rel="stylesheet">
    <link href="../font-awesome/css/font-awesome.css" rel="stylesheet">
    <link href="../css/animate.css" rel="stylesheet">
    <link href="../css/style.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">

    <script src="https://cdnjs.cloudflare.com/ajax/libs/jquery/3.6.0/jquery.min.js"></script>
    <link href="../css/plugins/sweetalert/sweetalert.css" rel="stylesheet">
    <script src="../js/plugins/sweetalert/sweetalert.min.js"></script>
    <script src="../app_js/sweetalert-finez.js"></script>

    <style>
        /* ===== ATTENDANCE PAGE - MODERN REDESIGN ===== */
        * { box-sizing: border-box; }

        body, #wrapper {
            font-family: 'Inter', 'Segoe UI', sans-serif;
            background: #f0f4ff !important;
        }

        /* ---- Top Control Bar ---- */
        .att-control-bar {
            background: #fff;
            border-bottom: 1px solid #e8edf5;
            padding: 16px 28px;
            display: flex;
            align-items: center;
            gap: 16px;
            flex-wrap: wrap;
            box-shadow: 0 2px 8px rgba(59,130,246,0.06);
        }

        .att-page-title {
            font-size: 20px;
            font-weight: 700;
            color: #1e293b;
            margin: 0;
            display: flex;
            align-items: center;
            gap: 10px;
            white-space: nowrap;
        }
        .att-page-title i {
            width: 38px; height: 38px;
            background: linear-gradient(135deg, #3b82f6, #6366f1);
            border-radius: 10px;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            color: #fff;
            font-size: 17px;
        }

        .att-controls {
            display: flex;
            align-items: center;
            gap: 12px;
            flex-wrap: wrap;
            flex: 1;
        }

        /* Group select */
        .att-group-wrap {
            position: relative;
            min-width: 190px;
        }
        .att-group-wrap select {
            width: 100%;
            appearance: none;
            -webkit-appearance: none;
            background: #f8faff;
            border: 1.5px solid #c7d7f5;
            border-radius: 10px;
            padding: 9px 36px 9px 14px;
            font-size: 14px;
            font-weight: 500;
            color: #1e293b;
            cursor: pointer;
            outline: none;
            transition: border-color 0.2s, box-shadow 0.2s;
        }
        .att-group-wrap select:focus {
            border-color: #3b82f6;
            box-shadow: 0 0 0 3px rgba(59,130,246,0.12);
        }
        .att-group-wrap::after {
            content: "\f107";
            font-family: FontAwesome;
            position: absolute;
            right: 12px;
            top: 50%;
            transform: translateY(-50%);
            color: #6b7280;
            pointer-events: none;
            font-size: 14px;
        }

        /* Date picker */
        .att-date-wrap {
            position: relative;
        }
        .att-date-wrap input[type="date"] {
            background: #f8faff;
            border: 1.5px solid #c7d7f5;
            border-radius: 10px;
            padding: 9px 14px;
            font-size: 14px;
            font-weight: 500;
            color: #1e293b;
            outline: none;
            transition: border-color 0.2s, box-shadow 0.2s;
            min-width: 155px;
        }
        .att-date-wrap input[type="date"]:focus {
            border-color: #3b82f6;
            box-shadow: 0 0 0 3px rgba(59,130,246,0.12);
        }

        /* Attendance badge */
        .att-count-badge {
            display: inline-flex;
            align-items: center;
            gap: 6px;
            background: linear-gradient(135deg, #3b82f6, #6366f1);
            color: #fff;
            border-radius: 20px;
            padding: 7px 16px;
            font-size: 13px;
            font-weight: 600;
            min-width: 110px;
            white-space: nowrap;
            box-shadow: 0 2px 8px rgba(59,130,246,0.25);
        }
        .att-count-badge i { font-size: 13px; }

        /* Action buttons */
        .att-btn {
            display: inline-flex;
            align-items: center;
            gap: 7px;
            padding: 9px 20px;
            border-radius: 10px;
            font-size: 13.5px;
            font-weight: 600;
            border: none;
            cursor: pointer;
            transition: all 0.18s ease;
            text-decoration: none;
        }
        .att-btn-primary {
            background: linear-gradient(135deg, #3b82f6, #6366f1);
            color: #fff;
            box-shadow: 0 3px 12px rgba(59,130,246,0.3);
        }
        .att-btn-primary:hover {
            transform: translateY(-1px);
            box-shadow: 0 5px 18px rgba(59,130,246,0.4);
        }
        .att-btn-outline {
            background: #fff;
            color: #3b82f6;
            border: 1.5px solid #c7d7f5;
        }
        .att-btn-outline:hover {
            background: #f0f4ff;
            border-color: #3b82f6;
        }
        .att-btn:disabled, .att-btn[disabled] {
            opacity: 0.5;
            cursor: not-allowed;
            transform: none !important;
        }

        /* ---- Main Content Area ---- */
        .att-main {
            padding: 24px 28px;
        }

        /* Stats strip */
        .att-stats-strip {
            display: flex;
            gap: 14px;
            margin-bottom: 20px;
            flex-wrap: wrap;
        }
        .att-stat-card {
            background: #fff;
            border-radius: 14px;
            padding: 14px 20px;
            display: flex;
            align-items: center;
            gap: 14px;
            flex: 1;
            min-width: 150px;
            box-shadow: 0 1px 6px rgba(0,0,0,0.06);
            border: 1px solid #e8edf5;
        }
        .att-stat-icon {
            width: 42px; height: 42px;
            border-radius: 12px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 18px;
            color: #fff;
            flex-shrink: 0;
        }
        .att-stat-icon.blue  { background: linear-gradient(135deg, #3b82f6, #60a5fa); }
        .att-stat-icon.green { background: linear-gradient(135deg, #10b981, #34d399); }
        .att-stat-icon.amber { background: linear-gradient(135deg, #f59e0b, #fbbf24); }
        .att-stat-label { font-size: 11.5px; color: #94a3b8; font-weight: 500; }
        .att-stat-value { font-size: 22px; font-weight: 700; color: #1e293b; line-height: 1; }

        /* ---- Member Grid ---- */
        .att-members-header {
            display: flex;
            align-items: center;
            justify-content: space-between;
            margin-bottom: 16px;
            flex-wrap: wrap;
            gap: 10px;
        }
        .att-members-title {
            font-size: 15px;
            font-weight: 700;
            color: #1e293b;
            margin: 0;
        }
        .att-members-title span {
            font-size: 12px;
            font-weight: 500;
            color: #64748b;
            margin-left: 6px;
        }

        /* Select all toggle */
        .att-select-all-btn {
            display: inline-flex;
            align-items: center;
            gap: 6px;
            padding: 6px 14px;
            border-radius: 8px;
            font-size: 12.5px;
            font-weight: 600;
            cursor: pointer;
            border: 1.5px solid #c7d7f5;
            background: #f8faff;
            color: #3b82f6;
            transition: all 0.15s;
        }
        .att-select-all-btn:hover { background: #eff6ff; border-color: #3b82f6; }

        /* Member grid */
        .att-member-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(200px, 1fr));
            gap: 14px;
        }

        /* Member card */
        .att-member-card {
            background: #fff;
            border-radius: 16px;
            border: 2px solid #e8edf5;
            padding: 18px 14px 14px;
            display: flex;
            flex-direction: column;
            align-items: center;
            text-align: center;
            gap: 10px;
            cursor: pointer;
            transition: all 0.2s ease;
            position: relative;
            overflow: hidden;
            box-shadow: 0 1px 4px rgba(0,0,0,0.04);
            user-select: none;
        }
        .att-member-card:hover {
            border-color: #93c5fd;
            transform: translateY(-2px);
            box-shadow: 0 6px 18px rgba(59,130,246,0.12);
        }
        .att-member-card.checked {
            border-color: #3b82f6;
            background: linear-gradient(160deg, #eff6ff 0%, #f5f3ff 100%);
            box-shadow: 0 4px 16px rgba(59,130,246,0.15);
        }
        .att-member-card.disabled-card {
            pointer-events: none;
            opacity: 0.9;
        }

        /* Checkmark tick overlay */
        .att-member-card .card-tick {
            position: absolute;
            top: 10px;
            right: 10px;
            width: 22px; height: 22px;
            border-radius: 50%;
            border: 2px solid #d1d5db;
            background: #fff;
            display: flex;
            align-items: center;
            justify-content: center;
            transition: all 0.2s;
            font-size: 11px;
            color: transparent;
        }
        .att-member-card.checked .card-tick {
            background: linear-gradient(135deg, #3b82f6, #6366f1);
            border-color: #3b82f6;
            color: #fff;
        }

        /* Hidden real checkbox */
        .att-member-card input[type="checkbox"] {
            display: none;
        }

        /* Avatar */
        .att-member-card .att-avatar {
            width: 64px; height: 64px;
            border-radius: 50%;
            object-fit: cover;
            border: 3px solid #e8edf5;
            transition: border-color 0.2s;
        }
        .att-member-card.checked .att-avatar {
            border-color: #3b82f6;
        }

        .att-member-name {
            font-size: 13px;
            font-weight: 600;
            color: #1e293b;
            line-height: 1.3;
        }

        /* Show all button inside grid */
        .att-showall-card {
            background: #f8faff;
            border: 2px dashed #c7d7f5;
            border-radius: 16px;
            padding: 18px 14px;
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            gap: 8px;
            cursor: pointer;
            transition: all 0.2s;
            min-height: 140px;
            color: #3b82f6;
            font-size: 13px;
            font-weight: 600;
        }
        .att-showall-card:hover {
            background: #eff6ff;
            border-color: #3b82f6;
        }
        .att-showall-card i { font-size: 24px; }

        /* Empty / loading state */
        .att-empty {
            text-align: center;
            padding: 60px 20px;
            color: #94a3b8;
        }
        .att-empty i { font-size: 42px; margin-bottom: 14px; display: block; }
        .att-empty p { font-size: 14px; }

        /* Status chip */
        .att-status-chip {
            display: inline-flex;
            align-items: center;
            gap: 5px;
            padding: 3px 10px;
            border-radius: 20px;
            font-size: 11px;
            font-weight: 600;
        }
        .att-status-chip.saved {
            background: #dcfce7;
            color: #16a34a;
        }
        .att-status-chip.unsaved {
            background: #fef9c3;
            color: #ca8a04;
        }

        /* Responsive tweaks */
        @media (max-width: 600px) {
            .att-control-bar { padding: 14px 16px; }
            .att-main { padding: 16px; }
            .att-member-grid { grid-template-columns: repeat(auto-fill, minmax(145px, 1fr)); }
        }

        /* Dynamic styling for normal member login mode */
        <?php if (!$is_admin) { ?>
        .att-member-grid {
            display: flex !important;
            justify-content: center;
            align-items: center;
            padding: 40px 0;
            perspective: 1000px;
        }
        .att-member-card {
            width: 100%;
            max-width: 420px;
            background: #ffffff;
            border-radius: 24px;
            border: 2px solid #e8edf5;
            box-shadow: 0 10px 30px rgba(59, 130, 246, 0.08) !important;
            padding: 40px 30px 30px !important;
            transition: all 0.4s cubic-bezier(0.16, 1, 0.3, 1) !important;
            gap: 20px !important;
        }
        .att-member-card:hover {
            transform: translateY(-5px) !important;
            box-shadow: 0 20px 40px rgba(59, 130, 246, 0.12) !important;
            border-color: #3b82f6 !important;
        }
        .att-member-card.checked {
            background: linear-gradient(135deg, #ffffff 0%, #f4f7ff 100%) !important;
            border-color: #3b82f6 !important;
        }
        .att-member-card .att-avatar {
            width: 140px !important;
            height: 140px !important;
            border-radius: 50% !important;
            border: 5px solid #f1f5f9 !important;
            box-shadow: 0 4px 12px rgba(0,0,0,0.06);
            transition: all 0.3s ease;
        }
        .att-member-card.checked .att-avatar {
            border-color: #3b82f6 !important;
            box-shadow: 0 4px 20px rgba(59, 130, 246, 0.2);
        }
        .att-member-name {
            font-size: 22px !important;
            font-weight: 700 !important;
            color: #0f172a !important;
        }
        .att-member-card .card-tick {
            top: 20px !important;
            right: 20px !important;
            width: 30px !important;
            height: 30px !important;
            border-width: 2.5px !important;
            font-size: 14px !important;
        }
        .att-member-card::after {
            content: "Tap to Toggle Daily Check-in";
            font-size: 13px;
            color: #64748b;
            font-weight: 600;
            margin-top: 10px;
            padding: 6px 16px;
            background: #f1f5f9;
            border-radius: 20px;
            display: inline-block;
            transition: all 0.3s;
        }
        .att-member-card.checked::after {
            content: "Checked In Today";
            color: #10b981;
            background: #ecfdf5;
        }
        .att-control-bar {
            flex-direction: column !important;
            gap: 20px !important;
            align-items: center !important;
            text-align: center !important;
            padding: 30px 20px !important;
        }
        .att-controls {
            justify-content: center !important;
            width: 100% !important;
        }
        .att-control-bar > div:last-child {
            margin-left: 0 !important;
            justify-content: center !important;
            width: 100% !important;
        }
        .att-members-header {
            display: none !important;
        }
        .att-stats-strip {
            display: none !important;
        }
        <?php } ?>

        /* ----- Member Check-in View Responsive Styles ----- */
        .member-container {
            max-width: 520px;
            margin: 30px auto;
            padding: 0 16px;
            display: flex;
            flex-direction: column;
            gap: 20px;
        }
        .member-card {
            background: var(--card-bg);
            border-radius: 20px;
            padding: 36px 24px;
            text-align: center;
            box-shadow: var(--shadow-md);
            border: 1px solid var(--border-color);
            transition: all 0.3s ease;
        }
        .member-card:hover {
            box-shadow: var(--shadow-lg);
            transform: translateY(-2px);
        }
        .member-icon-circle {
            width: 72px;
            height: 72px;
            background: rgba(59, 130, 246, 0.08);
            border-radius: 50%;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            color: #3b82f6;
            font-size: 28px;
            margin-bottom: 16px;
        }
        .member-card h2 {
            font-size: 20px;
            font-weight: 700;
            color: var(--text-primary);
            margin: 0 0 6px 0;
        }
        .member-card p {
            font-size: 13.5px;
            color: var(--text-muted);
            margin: 0 0 20px 0;
            line-height: 1.5;
        }
        .member-action-btn {
            background: linear-gradient(135deg, #3b82f6, #6366f1);
            color: white !important;
            border: none;
            padding: 12px 36px;
            font-size: 15px;
            font-weight: 600;
            border-radius: 12px;
            cursor: pointer;
            box-shadow: 0 8px 18px rgba(59, 130, 246, 0.22);
            display: inline-flex;
            align-items: center;
            gap: 8px;
            transition: all 0.25s ease;
            width: auto;
            max-width: 100%;
            font-family: 'Inter', sans-serif;
        }
        .member-action-btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 10px 22px rgba(59, 130, 246, 0.35);
        }
        .member-action-btn:active {
            transform: translateY(0);
        }
        .present-list-card {
            background: var(--card-bg);
            border-radius: 20px;
            padding: 24px;
            box-shadow: var(--shadow-md);
            border: 1px solid var(--border-color);
        }
        .present-list-card h3 {
            font-size: 15px;
            font-weight: 700;
            color: var(--text-primary);
            margin: 0 0 16px 0;
            display: flex;
            align-items: center;
            gap: 8px;
        }
        
        @media (max-width: 576px) {
            .member-container {
                margin: 16px auto;
                padding: 0 12px;
                gap: 16px;
            }
            .member-card {
                padding: 24px 16px;
                border-radius: 16px;
            }
            .member-card h2 {
                font-size: 18px;
            }
            .member-card p {
                font-size: 13px;
                margin-color: 0 0 16px 0;
            }
            .member-icon-circle {
                width: 60px;
                height: 60px;
                font-size: 24px;
                margin-bottom: 12px;
            }
            .member-action-btn {
                width: 100%;
                justify-content: center;
                padding: 12px 20px;
            }
            .present-list-card {
                padding: 16px;
                border-radius: 16px;
            }
        }
    </style>

    <script>
        $(document).ready(function() {          
            loadMenu();
            load_groups();
            $('#date_search').val(new Date().toISOString().split('T')[0]);
            <?php if (!$is_admin) { ?>
            var d = new Date();
            var days = ['Sunday', 'Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday'];
            var months = ['Jan','Feb','Mar','Apr','May','Jun','Jul','Aug','Sep','Oct','Nov','Dec'];
            var dayName = days[d.getDay()];
            var dateStr = d.getDate() + ' ' + months[d.getMonth()] + ' ' + d.getFullYear();
            $('#member-today-date').text(dayName + ', ' + dateStr);
            <?php } ?>
        });  

        // ---- Load group dropdown ----
        function load_groups(){
            $.ajax({
                type: "POST",
                url: "api/attendance.php",
                data: { action: 'load_groups' },
                success: function(data) {
                    var obj = jQuery.parseJSON(data);
                    var htm = '<select id="selected_group" class="att-group-select" onchange="loadData(0)">';
                    for (var i = 0; i < obj[0].length; i++) {
                        htm += '<option value="' + obj[0][i].id + '">' + obj[0][i].name + '</option>';
                    }
                    htm += '</select>';
                    $('#groups_container').html(htm);
                    loadData(0);
                },
                error: function(xhr, status, error) {
                    console.error('AJAX error: ', status, error);
                }
            });
        }

        // Global variable to keep track of user-selected checkboxes during pagination/expansion
        var currentlyCheckedIds = [];

        // ---- Load member cards ----
        function loadData(is_showall) {
            if (is_showall === 1) {
                // Save current selections before redrawing
                currentlyCheckedIds = $("input[name='attendance']:checked").map(function() {
                    return this.value;
                }).get();
            } else {
                currentlyCheckedIds = [];
            }

            $.ajax({               
                type: "POST",
                url: "api/attendance.php",
                data: {
                    action: 'load_member_data',
                    val: $('#selected_group').val(),
                    is_showall: is_showall
                },
                success: function(data) {
                    var obj = jQuery.parseJSON(data);
                    var members = obj[0];

                    // Stats
                    $('#stat-total').text(members.length);

                    // Build grid
                    var htm = '<div class="att-member-grid" id="att-grid">';
                    for (var i = 0; i < members.length; i++) {
                        var m = members[i];
                        var imgSrc = '../img/customer.png';
                        if (m.img && m.img != 0 && m.img != '0' && typeof m.img === 'string' && m.img.trim() !== '') {
                            imgSrc = '../image_upload/members/thumbnails/' + m.img;
                        }
                        var fullName = [m.first_name, m.middle_name, m.last_name].filter(function(p){ return p && p.trim(); }).join(' ');
                        htm += '<div class="att-member-card" id="card_' + m.id + '" onclick="toggleCard(this, \'' + m.id + '\')">';
                        htm +=   '<span class="card-tick"><i class="fa fa-check"></i></span>';
                        htm +=   '<img src="' + imgSrc + '" class="att-avatar" onerror="this.src=\'../img/customer.png\'">';
                        htm +=   '<div class="att-member-name">' + fullName + '</div>';
                        htm +=   '<input type="checkbox" name="attendance" value="' + m.id + '" id="chk_' + m.id + '">';
                        htm += '</div>';
                    }

                    if (!is_showall && <?php echo $is_admin ? 'true' : 'false'; ?>) {
                        htm += '<div class="att-showall-card" onclick="loadData(1)">';
                        htm +=   '<i class="fa fa-users"></i>';
                        htm +=   '<span>Show All</span>';
                        htm += '</div>';
                    }

                    htm += '</div>';
                    $('#members-data').html(htm);
                    checkHolidayStatus();
                },
                error: function(xhr, status, error) {
                    console.log('AJAX error: ', status, error);
                }
            });
        }

        // ---- Toggle single card ----
        function toggleCard(cardEl, memberId) {
            if ($(cardEl).hasClass('disabled-card')) return;
            var chk = document.getElementById('chk_' + memberId);
            chk.checked = !chk.checked;
            $(cardEl).toggleClass('checked', chk.checked);
            updateAttendanceCount();
        }

        // ---- Select All / Deselect All ----
        function toggleSelectAll() {
            var anyUnchecked = $('.att-member-card:not(.att-showall-card)').filter(function(){
                return !$(this).hasClass('checked');
            }).length > 0;

            $('.att-member-card:not(.att-showall-card)').each(function() {
                if ($(this).hasClass('disabled-card')) return;
                var chk = $(this).find('input[type="checkbox"]')[0];
                if (chk) {
                    chk.checked = anyUnchecked;
                    $(this).toggleClass('checked', anyUnchecked);
                }
            });
            updateAttendanceCount();
        }

        // ---- Add attendance ----
        function addAttendance() {
            var member_ids = [];
            $("input[name='attendance']:checked").each(function() {
                member_ids.push(this.value);
            });
            var session = $('#mem_session').val() || 'Morning';
            load_overlay();
            $.ajax({               
                type: "POST",
                url: "api/attendance.php",
                data: {
                    action: 'add_attendance',
                    val: $('#selected_group').val(),
                    date: $('#date_search').val(),
                    session: session,
                    member_ids: member_ids
                },
                success: function(data) {  
                    close_overlay();
                    alertsuccess(session + ' Attendance Saved Successfully');
                    loadData(0);
                },
                error: function(xhr, status, error) {
                    console.log('AJAX error: ', status, error);
                }
            });     
        }

        function checkHolidayStatus() {
            var selectedDate = $('#date_search').val();
            var selectedGroup = $('#selected_group').val() || 0;
            $.ajax({
                type: "POST",
                url: "api/attendance.php",
                data: {
                    action: 'check_holiday',
                    date: selectedDate,
                    group: selectedGroup
                },
                success: function(response) {
                    var res = JSON.parse(response);
                    if (res.is_fixed) {
                        // Month is fixed & locked - show alert banner but still fetch & render attendance
                        $('#fixed-overlay-message-wrapper').show();
                        $('#holiday-overlay-message-wrapper').hide();
                        fetchAttendanceDetails(true);
                    } else if (res.is_holiday) {
                        // Show Holiday Alert
                        $('#fixed-overlay-message-wrapper').hide();
                        $('#holiday-overlay-message-wrapper').show();
                        $('#btn_submit').hide();
                        $('#btn_edit').hide();
                        $('.att-member-card').addClass('disabled-card');
                    } else {
                        // Hide Alerts
                        $('#fixed-overlay-message-wrapper').hide();
                        $('#holiday-overlay-message-wrapper').hide();
                        $('#btn_submit').show();
                        
                        // Proceed to fetch attendance details as normal
                        fetchAttendanceDetails(false);
                    }
                },
                error: function(xhr, status, error) {
                    console.log('checkHolidayStatus error: ', error);
                    fetchAttendanceDetails(false);
                }
            });
        }

        // ---- Fetch existing attendance ----
        function fetchAttendanceDetails(isFixed){
            $('input[name="attendance"]').prop('checked', false);
            $('.att-member-card').removeClass('checked');

            var groupName = $('#selected_group option:selected').text() || 'Session';

            $.ajax({               
                type: "POST",
                url: "api/attendance.php",
                data: {
                    action: 'fetch_Attendance_details',
                    date: $('#date_search').val(),
                    group: $('#selected_group').val()
                },                
                success: function(data) {  
                    var obj = jQuery.parseJSON(data);
              
                    if (<?php echo (!$is_admin) ? 'true' : 'false'; ?>) {
                        var isPresent = false;
                        for (var i = 0; i < obj.length; i++) {
                            if (obj[i].member_id == <?php echo !empty($_SESSION['user_id']) ? $_SESSION['user_id'] : 0; ?>) {
                                isPresent = true;
                                break;
                            }
                        }
                        if (isFixed) {
                            if (isPresent) {
                                var checkInHtml = '<div style="color: #10b981; font-weight: 600; font-size: 15px; display: inline-flex; align-items: center; gap: 8px; background: rgba(16, 185, 129, 0.1); padding: 10px 24px; border-radius: 30px; border: 1px solid rgba(16, 185, 129, 0.25); font-family: \'Inter\', sans-serif;"><i class="fa fa-check-circle" style="font-size: 18px;"></i> Checked In (' + groupName + ')</div>';
                                $('#member-action-area').html(checkInHtml);
                            } else {
                                var checkInHtml = '<div style="color: #dc2626; font-weight: 600; font-size: 15px; display: inline-flex; align-items: center; gap: 8px; background: rgba(239, 68, 68, 0.1); padding: 10px 24px; border-radius: 30px; border: 1px solid rgba(239, 68, 68, 0.25); font-family: \'Inter\', sans-serif;"><i class="fa fa-lock" style="font-size: 18px;"></i> Attendance Fixed & Locked</div>';
                                $('#member-action-area').html(checkInHtml);
                            }
                        } else if (isPresent) {
                            var checkInHtml = '<div style="display: flex; flex-direction: column; align-items: center; gap: 12px;">';
                            checkInHtml += '  <div style="color: #10b981; font-weight: 600; font-size: 15px; display: inline-flex; align-items: center; gap: 8px; background: rgba(16, 185, 129, 0.1); padding: 10px 24px; border-radius: 30px; border: 1px solid rgba(16, 185, 129, 0.25); font-family: \'Inter\', sans-serif;"><i class="fa fa-check-circle" style="font-size: 18px;"></i> Checked In Successfully (' + groupName + ')</div>';
                            checkInHtml += '  <button onclick="unmarkMyAttendance()" style="background: none; border: none; color: #ef4444; font-size: 12px; font-weight: 600; cursor: pointer; text-decoration: underline; font-family: \'Inter\', sans-serif;"><i class="fa fa-times"></i> Unmark ' + groupName + ' Attendance</button>';
                            checkInHtml += '</div>';
                            $('#member-action-area').html(checkInHtml);
                        } else {
                            $('#member-action-area').html('<button onclick="markMyAttendance()" class="member-action-btn"><i class="fa fa-check-circle"></i> Mark Attendance</button>');
                        }
                        $('#present-members-card').show();
                        loadTodayPresentMembers();
                        return;
                    }

                    for (var i = 0; i < obj.length; i++) {
                        var mid = obj[i].member_id;
                        var chk = document.getElementById('chk_' + mid);
                        if (chk) chk.checked = true;
                        $('#card_' + mid).addClass('checked');
                    }

                    if (isFixed) {
                        // Month is fixed — show attendance, lock cards, hide edit & save buttons
                        $('.att-member-card').addClass('disabled-card');
                        $('#btn_submit').hide();
                        $('#btn_edit').hide();
                        $('#att-status-chip').html('<span class="att-status-chip saved" style="background:#fee2e2; color:#dc2626;"><i class="fa fa-lock"></i> Fixed / Locked</span>');
                    } else if (obj.length > 0) {
                        // Saved — lock form, show Edit
                        $('.att-member-card').addClass('disabled-card');
                        $('#btn_submit').hide();
                        $('#btn_edit').show();
                        $('#att-status-chip').html('<span class="att-status-chip saved"><i class="fa fa-check-circle"></i> Saved</span>');
                    } else {
                        // Not saved — allow marking
                        if (currentlyCheckedIds && currentlyCheckedIds.length > 0) {
                            for (var k = 0; k < currentlyCheckedIds.length; k++) {
                                var mid = currentlyCheckedIds[k];
                                var chk = document.getElementById('chk_' + mid);
                                if (chk) {
                                    chk.checked = true;
                                    $('#card_' + mid).addClass('checked');
                                }
                            }
                        }
                        $('.att-member-card').removeClass('disabled-card');
                        $('#btn_submit').show();
                        $('#btn_edit').hide();
                        $('#att-status-chip').html('<span class="att-status-chip unsaved"><i class="fa fa-clock-o"></i> Pending</span>');
                    }
                    updateAttendanceCount();
                },
                error: function(xhr, status, error) {
                    console.log('AJAX error: ', status, error);
                }
            });
        }

        function markMyAttendance() {
            load_overlay();
            var groupName = $('#selected_group option:selected').text() || 'Session';
            $.ajax({               
                type: "POST",
                url: "api/attendance.php",
                data: {
                    action: 'add_attendance',
                    val: $('#selected_group').val(),
                    date: $('#date_search').val(),
                    member_ids: [<?php echo !empty($_SESSION['user_id']) ? $_SESSION['user_id'] : 0; ?>]
                },
                success: function(data) {  
                    close_overlay();
                    alertsuccess(groupName + ' Attendance Marked Successfully');
                    checkHolidayStatus();
                },
                error: function(xhr, status, error) {
                    close_overlay();
                    console.log('AJAX error: ', status, error);
                }
            });     
        }

        function unmarkMyAttendance() {
            var groupName = $('#selected_group option:selected').text() || 'Session';
            swal({
                title: "Are you sure?",
                text: "Do you want to unmark your " + groupName + " attendance for today?",
                type: "warning",
                showCancelButton: true,
                confirmButtonColor: "#DD6B55",
                confirmButtonText: "Yes, Unmark!",
                cancelButtonText: "Cancel",
                closeOnConfirm: true
            }, function(isConfirm) {
                if (isConfirm) {
                    load_overlay();
                    $.ajax({               
                        type: "POST",
                        url: "api/attendance.php",
                        data: {
                            action: 'add_attendance',
                            val: $('#selected_group').val(),
                            date: $('#date_search').val(),
                            member_ids: [] // Empty list to unmark
                        },
                        success: function(data) {  
                            close_overlay();
                            alertsuccess(groupName + ' Attendance Unmarked Successfully');
                            checkHolidayStatus();
                        },
                        error: function(xhr, status, error) {
                            close_overlay();
                            console.log('AJAX error: ', status, error);
                        }
                    });
                }
            });
        }

        function loadTodayPresentMembers() {
            var groupName = $('#selected_group option:selected').text() || 'Session';
            $.ajax({
                type: "POST",
                url: "api/attendance.php",
                data: {
                    action: 'load_today_present_members',
                    date: $('#date_search').val(),
                    group: $('#selected_group').val()
                },
                success: function(data) {
                    var obj = jQuery.parseJSON(data);
                    var htm = "";
                    if (obj.length > 0) {
                        for (var i = 0; i < obj.length; i++) {
                            var m = obj[i];
                            var imgSrc = '../img/customer.png';
                            if (m.img && m.img != 0 && m.img != '0' && typeof m.img === 'string' && m.img.trim() !== '') {
                                imgSrc = '../image_upload/members/thumbnails/' + m.img;
                            }
                            var fullName = [m.first_name, m.middle_name, m.last_name].filter(function(p){ return p && p.trim(); }).join(' ');
                            
                            htm += '<div style="display: flex; align-items: center; justify-content: space-between; padding: 10px 14px; background: var(--bg-main); border-radius: 12px; border: 1px solid var(--border-color); font-family: \'Inter\', sans-serif;">';
                            htm += '  <div style="display: flex; align-items: center; gap: 12px;">';
                            htm += '    <img src="' + imgSrc + '" style="width: 38px; height: 38px; border-radius: 50%; border: 2px solid var(--border-color);" onerror="this.src=\'../img/customer.png\'">';
                            htm += '    <div style="font-size: 14px; font-weight: 600; color: var(--text-primary);">' + fullName + '</div>';
                            htm += '  </div>';
                            htm += '  <div style="font-size: 11px; font-weight: 500; background: rgba(16, 185, 129, 0.1); color: #10b981; padding: 4px 10px; border-radius: 20px; border: 1px solid rgba(16, 185, 129, 0.25); display: inline-flex; align-items: center; gap: 4px;"><i class="fa fa-check"></i> Present (' + groupName + ')</div>';
                            htm += '</div>';
                        }
                    } else {
                        htm = '<div style="text-align: center; padding: 20px 0; color: #94a3b8; font-size: 13px; font-family: \'Inter\', sans-serif;">No one present for ' + groupName + ' yet.</div>';
                    }
                    $('#present-members-list').html(htm);
                },
                error: function(xhr, status, error) {
                    console.log('AJAX error: ', status, error);
                }
            });
        }

        function enableAttendance(){
            $('.att-member-card').removeClass('disabled-card');
            $('#btn_submit').show();
            $('#btn_edit').hide();
            $('#att-status-chip').html('<span class="att-status-chip unsaved"><i class="fa fa-clock-o"></i> Editing</span>');
        }

        // ---- Live count update ----
        $(document).on("change", "input[name='attendance']", function() {
            updateAttendanceCount();
        });

        function updateAttendanceCount() {
            var checked = $("input[name='attendance']:checked").length;
            $('#att-present-count').text(checked);
            $('#att-count-badge').html('<i class="fa fa-user-check"></i> ' + checked + ' Present');
        }

        // Date change → check holiday first
        $(document).on("change", "#date_search", function(){
            checkHolidayStatus();
        });
    </script>

    <script src="../app_menu/menu.js"></script>
    
</head>

<body>
    <input type="hidden" id="mem_session" value="Morning">

    <div id="wrapper">

        <!-- Sidebar -->
        <nav class="navbar-default navbar-static-side" role="navigation">
            <div class="dropdown profile-element">
                <center>
                    <span><img alt="image" class="img-circle" src="../img/customer.png" style="padding-top: 20px;"/></span>
                    <span class="clear"><span class="block m-t-xs"><strong class="font-bold"><?php echo $_SESSION['name']; ?></strong>
                </center>
            </div>
            <div class="sidebar-collapse" id="divMenuContainer">
                <!-- menu injected via ajax -->
            </div>
        </nav>

        <!-- Main content -->
        <div id="page-wrapper" style="background:#f0f4ff; min-height:100vh;">

            <!-- Top nav bar -->
            <div class="row border-bottom" style="background:#fff; margin:0;">
                <nav class="navbar navbar-static-top" role="navigation" style="margin-bottom:0; background:#fff;">
                    <div class="navbar-header">
                        <a class="navbar-minimalize minimalize-styl-2 btn btn-primary" href="#"><i class="fa fa-bars"></i></a>
                    </div>
                    <ul class="nav navbar-top-links navbar-right">
                        <li>
                            <a href="../app_login_manager/logout.php" style="color:#147ad1;">
                                <i class="fa fa-sign-out"></i> Log out
                            </a>
                        </li>
                    </ul>
                </nav>
            </div>

            <!-- Fixed month overlay banner for admin/member -->
            <div style="padding: 20px 20px 0 20px; max-width: 600px; margin: 0 auto; display: none;" id="fixed-overlay-message-wrapper">
                <div style="background: rgba(239, 68, 68, 0.08); border: 1.5px solid rgba(239, 68, 68, 0.3); border-radius: 12px; padding: 16px 20px; display: flex; align-items: center; gap: 12px; color: #dc2626; font-family: 'Inter', sans-serif;">
                    <i class="fa fa-lock" style="font-size: 24px;"></i>
                    <div>
                        <strong style="font-weight: 800; display: block; font-size: 15px;">Attendance Month is Fixed & Locked</strong>
                        <span style="font-size: 13.5px; color: #475569;">Attendance for this month has been fixed. No attendance can be added, updated, or removed.</span>
                    </div>
                </div>
            </div>

            <!-- Holiday overlay banner for admin/member -->
            <div style="padding: 20px 20px 0 20px; max-width: 600px; margin: 0 auto; display: none;" id="holiday-overlay-message-wrapper">
                <div style="background: rgba(239, 68, 68, 0.08); border: 1px solid rgba(239, 68, 68, 0.25); border-radius: 12px; padding: 16px 20px; display: flex; align-items: center; gap: 12px; color: #ef4444; font-family: 'Inter', sans-serif;">
                    <i class="fa fa-exclamation-triangle" style="font-size: 20px;"></i>
                    <div>
                        <strong style="font-weight: 700; display: block; font-size: 15px;">Selected Date is a Holiday / Leave</strong>
                        <span style="font-size: 13.5px; color: var(--text-muted);">Attendance cannot be marked or modified for this date.</span>
                    </div>
                </div>
            </div>

            <!-- Member Pending Balance & Quick Pay Card -->
            <?php if (!$is_admin) { ?>
            <div style="padding: 20px 20px 0 20px; max-width: 600px; margin: 0 auto;" id="member-pending-balance-wrapper">
                <div style="padding:22px 24px; text-align:left; background:#ffffff; border-radius:20px; border:1px solid #e2e8f0; box-shadow:0 4px 20px rgba(0,0,0,0.035); font-family:'Inter',sans-serif;">
                    <div style="display:flex; align-items:center; justify-content:space-between; flex-wrap:wrap; gap:14px;">
                        <div style="display:flex; align-items:center; gap:14px;">
                            <div style="width:46px; height:46px; border-radius:14px; background:linear-gradient(135deg, #1e1b4b, #312e81); color:#ffffff; display:flex; align-items:center; justify-content:center; font-size:20px; box-shadow:0 4px 14px rgba(30,27,75,0.25); flex-shrink:0;">
                                <i class="fa fa-credit-card"></i>
                            </div>
                            <div>
                                <div style="font-size:11px; font-weight:800; color:#64748b; text-transform:uppercase; letter-spacing:0.5px;">Pending Balance</div>
                                <div style="font-size:20px; font-weight:900; color:#0f172a; margin-top:2px;" id="user-pending-balance-val">₹0.00</div>
                            </div>
                        </div>
                        <div style="display:flex; align-items:center; gap:10px;">
                            <button onclick="openUserPayModal()" class="btn" style="background:linear-gradient(135deg, #10b981, #059669); color:#ffffff; border:none; padding:10px 20px; border-radius:30px; font-size:13.5px; font-weight:800; display:inline-flex; align-items:center; gap:6px; box-shadow:0 4px 12px rgba(16,185,129,0.35); cursor:pointer;">
                                <i class="fa fa-mobile" style="font-size:17px;"></i> Pay
                            </button>
                            <a href="mobile/ledger.php" class="btn" style="background:#f1f5f9; color:#334155; border:1px solid #cbd5e1; padding:10px 18px; border-radius:30px; font-size:13.5px; font-weight:700; display:inline-flex; align-items:center; gap:6px; text-decoration:none;">
                                <i class="fa fa-file-text-o" style="color:#3b82f6;"></i> View
                            </a>
                        </div>
                    </div>
                </div>
            </div>
            <?php } ?>

            <!-- ===== Attendance Control Bar ===== -->
            <div class="att-control-bar">
                <h1 class="att-page-title">
                    <i class="fa fa-calendar-check-o"></i>
                    Mark Attendance
                </h1>

                <div class="att-controls">
                    <!-- Group Selector -->
                    <div class="att-group-wrap" id="groups_container">
                        <!-- dropdown injected via AJAX -->
                    </div>

                    <!-- Date Picker -->
                    <div class="att-date-wrap">
                        <input type="date" id="date_search" name="date_search">
                    </div>

                    <!-- Status chip -->
                    <div id="att-status-chip"></div>

                    <!-- Attendance count badge -->
                    <div class="att-count-badge" id="att-count-badge">
                        <i class="fa fa-users"></i> 0 Present
                    </div>
                </div>

                <!-- Action buttons (right side) -->
                <div style="display:flex; align-items:center; gap:10px; margin-left:auto;">
                    <a href="groups.php" class="att-btn att-btn-outline" style="text-decoration:none; display:inline-flex; align-items:center; gap:6px;" title="Add / Manage Groups">
                        <i class="fa fa-users"></i> Manage Groups
                    </a>
                    <button class="att-btn att-btn-outline" type="button" onclick="toggleSelectAll()" title="Toggle Select All">
                        <i class="fa fa-check-square-o"></i> Select All
                    </button>
                    <button class="att-btn att-btn-primary" type="button" id="btn_submit" onclick="addAttendance()">
                        <i class="fa fa-save"></i> Save Attendance
                    </button>
                    <button class="att-btn att-btn-outline" type="button" id="btn_edit" onclick="enableAttendance()" style="display:none;">
                        <i class="fa fa-pencil"></i> Edit
                    </button>
                </div>
            </div>

            <!-- ===== Main Content ===== -->
            <div class="att-main">

                <!-- Stats strip -->
                <div class="att-stats-strip">
                    <div class="att-stat-card">
                        <div class="att-stat-icon blue"><i class="fa fa-users"></i></div>
                        <div>
                            <div class="att-stat-label">Total Members</div>
                            <div class="att-stat-value" id="stat-total">—</div>
                        </div>
                    </div>
                    <div class="att-stat-card">
                        <div class="att-stat-icon green"><i class="fa fa-check-circle"></i></div>
                        <div>
                            <div class="att-stat-label">Present Today</div>
                            <div class="att-stat-value" id="att-present-count">0</div>
                        </div>
                    </div>
                    <div class="att-stat-card">
                        <div class="att-stat-icon amber"><i class="fa fa-calendar"></i></div>
                        <div>
                            <div class="att-stat-label">Date</div>
                            <div class="att-stat-value" id="stat-date" style="font-size:14px; font-weight:600;">—</div>
                        </div>
                    </div>
                </div>

                <!-- Member Grid -->
                <div style="background:#fff; border-radius:18px; padding:20px 22px; box-shadow:0 1px 8px rgba(0,0,0,0.06); border:1px solid #e8edf5;">
                    <div class="att-members-header">
                        <h2 class="att-members-title">
                            <i class="fa fa-id-card-o" style="color:#3b82f6;"></i>
                            Members
                        </h2>
                    </div>
                    <div id="members-data">
                        <div class="att-empty">
                            <i class="fa fa-spinner fa-spin" style="color:#3b82f6;"></i>
                            <p>Loading members…</p>
                        </div>
                    </div>
                </div>

            </div><!-- end att-main -->

        </div><!-- end page-wrapper -->
    </div><!-- end wrapper -->

    <!-- Scripts -->
    <script src="../js/jquery-3.1.1.min.js"></script>
    <script src="../js/bootstrap.min.js"></script>
    <script src="../js/plugins/metisMenu/jquery.metisMenu.js"></script>
    <script src="../js/plugins/slimscroll/jquery.slimscroll.min.js"></script>
    <script src="../js/inspinia.js"></script>
    <script src="../js/plugins/pace/pace.min.js"></script>
    <script src="../js/loadingoverlay.min.js"></script>

    <script>
        // Update date stat display
        $(document).ready(function() {
            function refreshDateLabel() {
                var d = $('#date_search').val();
                if (d) {
                    var parts = d.split('-');
                    var months = ['Jan','Feb','Mar','Apr','May','Jun','Jul','Aug','Sep','Oct','Nov','Dec'];
                    $('#stat-date').text(parseInt(parts[2]) + ' ' + months[parseInt(parts[1])-1] + ' ' + parts[0]);
                }
            }
            refreshDateLabel();
            $('#date_search').on('change', refreshDateLabel);

            // Register Service Worker for PWA
            if ('serviceWorker' in navigator) {
                window.addEventListener('load', () => {
                    navigator.serviceWorker.register('../sw.js')
                        .then(reg => console.log('Service Worker registered', reg))
                        .catch(err => console.log('Service Worker registration failed', err));
    <!-- UPI Payment Modal Container -->
    <div id="upi-modal-container" style="display:none; position:fixed; top:0; left:0; width:100%; height:100%; z-index:9999; overflow-y:auto;">
        <div style="position:fixed; top:0; left:0; width:100%; height:100%; background:rgba(15,23,42,0.65); backdrop-filter:blur(4px);" onclick="closeUserPayModal(event)"></div>
        <div style="position:relative; max-width:480px; margin:40px auto; background:#ffffff; border-radius:24px; box-shadow:0 20px 40px rgba(0,0,0,0.2); overflow:hidden; z-index:10000; font-family:'Inter', sans-serif;">
            
            <!-- Header -->
            <div style="background:linear-gradient(135deg, #1e1b4b, #312e81); padding:24px 20px; color:#ffffff; position:relative;">
                <button onclick="closeUserPayModal()" style="position:absolute; top:16px; right:16px; background:rgba(255,255,255,0.15); border:none; color:#fff; width:32px; height:32px; border-radius:50%; display:flex; align-items:center; justify-content:center; cursor:pointer;">
                    <i class="fa fa-times"></i>
                </button>
                <div style="display:flex; align-items:center; gap:12px;">
                    <div style="width:42px; height:42px; border-radius:12px; background:rgba(255,255,255,0.15); display:flex; align-items:center; justify-content:center; font-size:20px; color:#60a5fa;">
                        <i class="fa fa-mobile"></i>
                    </div>
                    <div>
                        <h3 style="font-size:17px; font-weight:800; margin:0; color:#ffffff;">Instant UPI Payment</h3>
                        <p style="font-size:12px; color:#93c5fd; margin:2px 0 0;" id="upi-modal-payee">YMCA BCP Poovathussery</p>
                    </div>
                </div>
            </div>

            <!-- Body -->
            <div style="padding:24px 20px;">
                
                <!-- Important Cashier Screenshot Notice -->
                <div style="background:#fffbebf5; border:1px solid #fef08a; border-radius:14px; padding:12px 14px; margin-bottom:18px; display:flex; align-items:center; gap:10px; box-shadow:0 2px 8px rgba(234,179,8,0.08);">
                    <div style="width:34px; height:34px; border-radius:10px; background:#fef08a; color:#a16207; display:flex; align-items:center; justify-content:center; font-size:16px; flex-shrink:0;">
                        <i class="fa fa-info-circle"></i>
                    </div>
                    <div style="font-size:12.5px; font-weight:700; color:#854d0e; line-height:1.4;">
                        Please share the payment screenshot with the cashier after completing payment.
                    </div>
                </div>

                <!-- Amount Mode Selection -->
                <label style="display:block; font-size:11px; font-weight:800; color:#64748b; text-transform:uppercase; letter-spacing:0.5px; margin-bottom:12px;">SELECT PAYMENT AMOUNT</label>
                
                <div style="display:grid; grid-template-columns:1fr 1fr; gap:12px; margin-bottom:16px;">
                    <div id="mode-card-full" onclick="selectUserUpiMode('full')" style="border:2px solid #3b82f6; background:#eff6ff; border-radius:14px; padding:14px 12px; cursor:pointer; text-align:center; transition:all 0.2s;">
                        <div style="font-size:11px; font-weight:700; color:#2563eb;">PAY FULL BALANCE</div>
                        <div style="font-size:16px; font-weight:900; color:#1e3a8a; margin-top:4px;" id="upi-mode-full-val">₹0.00</div>
                    </div>
                    <div id="mode-card-custom" onclick="selectUserUpiMode('custom')" style="border:2px solid #e2e8f0; background:#f8fafc; border-radius:14px; padding:14px 12px; cursor:pointer; text-align:center; transition:all 0.2s;">
                        <div style="font-size:11px; font-weight:700; color:#64748b;">PAY CUSTOM AMOUNT</div>
                        <div style="font-size:14px; font-weight:800; color:#0f172a; margin-top:4px;">Enter Amount</div>
                    </div>
                </div>

                <!-- Custom Amount Input Field -->
                <div id="custom-amount-wrap" style="display:none; margin-bottom:20px;">
                    <label style="display:block; font-size:11px; font-weight:800; color:#475569; margin-bottom:6px;">TYPE CUSTOM AMOUNT (₹)</label>
                    <div style="position:relative;">
                        <span style="position:absolute; left:14px; top:12px; font-size:16px; font-weight:800; color:#64748b;">₹</span>
                        <input type="number" id="upi-custom-input" placeholder="Enter amount (e.g. 500)" style="width:100%; height:46px; border-radius:12px; border:2px solid #3b82f6; padding:0 14px 0 32px; font-size:16px; font-weight:800; color:#0f172a; outline:none;" oninput="onUserCustomAmountChange()">
                    </div>
                </div>

                <!-- Mobile Launch Button -->
                <div id="upi-mobile-section" style="margin-bottom:20px;">
                    <a id="upi-deep-link-btn" href="#" target="_blank" style="display:flex; align-items:center; justify-content:center; gap:10px; width:100%; height:52px; background:linear-gradient(135deg, #10b981, #059669); color:#ffffff; border-radius:30px; font-size:15px; font-weight:800; text-decoration:none; box-shadow:0 4px 16px rgba(16,185,129,0.35);">
                        <i class="fa fa-mobile" style="font-size:22px;"></i> Launch GPay / PhonePe / Paytm
                    </a>
                </div>

                <!-- Desktop QR Code & Copy Options -->
                <div style="background:#f8fafc; border:1px solid #e2e8f0; border-radius:16px; padding:16px; text-align:center;">
                    <div style="font-size:11px; font-weight:800; color:#64748b; text-transform:uppercase; margin-bottom:10px;"><i class="fa fa-qrcode"></i> SCAN QR CODE TO PAY</div>
                    
                    <!-- Dynamic QR Code Image -->
                    <div style="display:inline-block; background:#ffffff; padding:10px; border-radius:14px; border:1px solid #cbd5e1; box-shadow:0 2px 8px rgba(0,0,0,0.05); margin-bottom:12px;">
                        <img id="upi-qr-image" src="" alt="UPI QR Code" style="width:180px; height:180px; display:block;">
                    </div>
                    
                    <div style="font-size:13px; font-weight:800; color:#0f172a; margin-bottom:12px;">
                        Amount to Pay: <span id="upi-qr-amount-text" style="color:#2563eb;">₹0.00</span>
                    </div>

                    <!-- Copy Action Buttons -->
                    <div style="display:flex; gap:8px; justify-content:center; flex-wrap:wrap;">
                        <button onclick="copyUserUpiId()" style="background:#ffffff; border:1px solid #cbd5e1; color:#1e293b; padding:8px 14px; border-radius:20px; font-size:12px; font-weight:700; cursor:pointer; display:inline-flex; align-items:center; gap:6px;">
                            <i class="fa fa-copy" style="color:#3b82f6;"></i> Copy UPI ID
                        </button>
                        <button onclick="copyUserUpiLink()" style="background:#ffffff; border:1px solid #cbd5e1; color:#1e293b; padding:8px 14px; border-radius:20px; font-size:12px; font-weight:700; cursor:pointer; display:inline-flex; align-items:center; gap:6px;">
                            <i class="fa fa-link" style="color:#10b981;"></i> Copy Link
                        </button>
                    </div>
                </div>

            </div>

        </div>
    </div>

    <script>
    var currentUpiSettings = null;
    var userPendingBalance = 0;
    var activeUserUpiAmount = 0;

    $(document).ready(function(){
        loadUserPendingBalance();
    });

    function loadUserPendingBalance() {
        var curYear = new Date().getFullYear();
        if (new Date().getMonth() < 3) curYear--;

        $.post('api/member_cashbook_report.php', {
            action: 'get_member_cashbook',
            member_id: <?php echo !empty($_SESSION['user_id']) ? (int)$_SESSION['user_id'] : 0; ?>,
            year: curYear
        }, function(res){
            try {
                var d = (typeof res === 'string') ? JSON.parse(res) : res;
                var summary = d.summary || {};
                userPendingBalance = parseFloat(summary.closing_balance || summary.closing || 0);
                if (userPendingBalance < 0) userPendingBalance = 0;

                currentUpiSettings = summary.payment_settings || {
                    upi_id: 'ymcabcp@okaxis',
                    payee_name: 'YMCA BCP Poovathussery',
                    payment_note: 'YMCA Member Fee Payment',
                    is_active: 1
                };

                if (userPendingBalance > 0) {
                    $('#user-pending-balance-val').text('₹' + userPendingBalance.toFixed(2)).css('color', '#ef4444');
                } else {
                    $('#user-pending-balance-val').text('₹0.00').css('color', '#10b981');
                }
            } catch(ex) {
                console.error(ex);
            }
        });
    }

    function openUserPayModal() {
        if (!currentUpiSettings) {
            currentUpiSettings = {
                upi_id: 'ymcabcp@okaxis',
                payee_name: 'YMCA BCP Poovathussery',
                payment_note: 'YMCA Member Fee Payment',
                is_active: 1
            };
        }
        $('#upi-modal-payee').text(currentUpiSettings.payee_name || 'YMCA BCP Poovathussery');
        $('#upi-mode-full-val').text('₹' + userPendingBalance.toFixed(2));
        
        if (userPendingBalance > 0) {
            selectUserUpiMode('full');
        } else {
            selectUserUpiMode('custom');
        }
        $('#upi-modal-container').fadeIn(200);
    }

    function closeUserPayModal(e) {
        if (e && e.target !== e.currentTarget) return;
        $('#upi-modal-container').fadeOut(200);
    }

    function selectUserUpiMode(mode) {
        if (mode === 'full') {
            $('#mode-card-full').css({'border-color': '#3b82f6', 'background': '#eff6ff'});
            $('#mode-card-custom').css({'border-color': '#e2e8f0', 'background': '#f8fafc'});
            $('#custom-amount-wrap').hide();
            activeUserUpiAmount = userPendingBalance;
        } else {
            $('#mode-card-custom').css({'border-color': '#3b82f6', 'background': '#eff6ff'});
            $('#mode-card-full').css({'border-color': '#e2e8f0', 'background': '#f8fafc'});
            $('#custom-amount-wrap').show();
            $('#upi-custom-input').focus();
            onUserCustomAmountChange();
            return;
        }
        updateUserUpiLinksAndQr();
    }

    function onUserCustomAmountChange() {
        var val = parseFloat($('#upi-custom-input').val() || 0);
        activeUserUpiAmount = val > 0 ? val : 0;
        updateUserUpiLinksAndQr();
    }

    function updateUserUpiLinksAndQr() {
        if (!currentUpiSettings) return;
        var upiId = currentUpiSettings.upi_id || 'ymcabcp@okaxis';
        var payee = encodeURIComponent(currentUpiSettings.payee_name || 'YMCA BCP');
        var note = encodeURIComponent(currentUpiSettings.payment_note || 'YMCA Fee Payment');
        var amtStr = activeUserUpiAmount > 0 ? activeUserUpiAmount.toFixed(2) : '0.00';

        var upiUrl = 'upi://pay?pa=' + upiId + '&pn=' + payee + '&am=' + amtStr + '&cu=INR&tn=' + note;

        $('#upi-deep-link-btn').attr('href', upiUrl);
        $('#upi-qr-amount-text').text('₹' + amtStr);

        var qrImgUrl = 'https://api.qrserver.com/v1/create-qr-code/?size=200x200&data=' + encodeURIComponent(upiUrl);
        $('#upi-qr-image').attr('src', qrImgUrl);
    }

    function copyUserUpiId() {
        if (!currentUpiSettings || !currentUpiSettings.upi_id) return;
        navigator.clipboard.writeText(currentUpiSettings.upi_id);
        alert("UPI ID (" + currentUpiSettings.upi_id + ") copied to clipboard.");
    }

    function copyUserUpiLink() {
        var upiUrl = $('#upi-deep-link-btn').attr('href');
        if (upiUrl) {
            navigator.clipboard.writeText(upiUrl);
            alert("UPI Payment link copied to clipboard.");
        }
    }
    </script>
</body>

</html>
