<?php
session_start();
include '../app_common/db_connect.php';

// Check if user is logged in
if (empty($_SESSION['login_id'])) {
    header("Location: ../index.php");
    exit();
}

$is_admin = ($_SESSION['login_id'] == 1);
// Release session lock so AJAX API calls can start their own sessions without blocking
session_write_close();
?>

<!DOCTYPE html>
<html>

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="description" content="Member Mark Attendance - YMCA Management System">
    <title>Member Attendance - YMCA</title>

    <!-- Mobile redirect: send non-admin member logins to mobile portal on small screens -->
    <script>
        (function(){
            if(window.innerWidth < 768 && !window.location.href.includes('desktop=1')){
                window.location.replace('mobile/home.php');
            }
        })();
    </script>

    <!-- FontAwesome & Fonts -->
    <link href="../font-awesome/css/font-awesome.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">

    <!-- Framework Styles -->
    <link href="../css/bootstrap.min.css" rel="stylesheet">
    <link href="../css/animate.css" rel="stylesheet">
    <link href="../css/style.css" rel="stylesheet">
    <link href="../css/plugins/sweetalert/sweetalert.css" rel="stylesheet">

    <!-- Modern Dark Mode Core -->
    <link href="../css/modern_dark_mode.css" rel="stylesheet">
    <script src="../js/modern_dark_mode.js"></script>

    <style>
        .member-container {
            max-width: 500px;
            margin: 30px auto;
            padding: 0 15px;
            display: flex;
            flex-direction: column;
            gap: 20px;
        }

        .member-card {
            background: var(--bg-card);
            border: 1px solid var(--border-color);
            border-radius: 24px;
            padding: 36px 24px;
            text-align: center;
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.04);
            transition: all 0.3s ease;
        }

        .member-icon-circle {
            width: 70px;
            height: 70px;
            border-radius: 50%;
            background: rgba(59, 130, 246, 0.1);
            color: #3b82f6;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 28px;
            margin: 0 auto 16px auto;
            border: 1px solid rgba(59, 130, 246, 0.2);
        }

        .member-card h2 {
            font-size: 22px;
            font-weight: 800;
            color: var(--text-primary);
            margin: 0 0 6px 0;
            font-family: 'Inter', sans-serif;
        }

        .member-action-btn {
            background: linear-gradient(135deg, #4f46e5 0%, #3b82f6 100%);
            color: white;
            border: none;
            border-radius: 30px;
            padding: 14px 36px;
            font-size: 16px;
            font-weight: 700;
            font-family: 'Inter', sans-serif;
            cursor: pointer;
            box-shadow: 0 8px 20px rgba(59, 130, 246, 0.3);
            transition: all 0.2s ease;
            display: inline-flex;
            align-items: center;
            gap: 10px;
            width: 100%;
            justify-content: center;
        }

        .member-action-btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 12px 25px rgba(59, 130, 246, 0.4);
            color: white;
        }

        .present-list-card {
            background: var(--bg-card);
            border: 1px solid var(--border-color);
            border-radius: 20px;
            padding: 20px 24px;
            box-shadow: 0 4px 15px rgba(0, 0, 0, 0.03);
        }

        .present-list-card h3 {
            font-size: 15px;
            font-weight: 700;
            color: var(--text-primary);
            margin: 0 0 16px 0;
            display: flex;
            align-items: center;
            gap: 8px;
            font-family: 'Inter', sans-serif;
        }

        .att-group-select {
            background: var(--bg-card) !important;
            border: 1px solid var(--border-color) !important;
            border-radius: 20px !important;
            padding: 8px 20px !important;
            font-size: 14px !important;
            font-weight: 600 !important;
            color: var(--text-primary) !important;
            outline: none !important;
            cursor: pointer !important;
            font-family: 'Inter', sans-serif !important;
            box-shadow: 0 2px 6px rgba(0, 0, 0, 0.03) !important;
            width: 100% !important;
            text-align: center !important;
            text-align-last: center !important;
        }

        /* Hide unstyled raw LoadingOverlay text */
        .loadingoverlay_text {
            display: none !important;
        }

        @keyframes mob-spin {
            to { transform: rotate(360deg); }
        }
    </style>

    <script src="https://cdnjs.cloudflare.com/ajax/libs/jquery/3.6.0/jquery.min.js"></script>
    <script src="../js/plugins/sweetalert/sweetalert.min.js"></script>
    <script src="../app_js/sweetalert-finez.js"></script>

    <script>
        function load_overlay() {
            if ($('#mob-global-loader').length === 0) {
                $('body').append(`
                    <div id="mob-global-loader" style="position:fixed;top:0;left:0;width:100vw;height:100vh;background:rgba(15,23,42,0.45);backdrop-filter:blur(8px);-webkit-backdrop-filter:blur(8px);z-index:99999;display:flex;flex-direction:column;align-items:center;justify-content:center;opacity:0;pointer-events:none;transition:opacity 0.25s ease;">
                        <div style="background:#ffffff;padding:26px 36px;border-radius:24px;box-shadow:0 20px 40px rgba(0,0,0,0.2);display:flex;flex-direction:column;align-items:center;gap:16px;">
                            <div style="position:relative;width:56px;height:56px;display:flex;align-items:center;justify-content:center;">
                                <div style="position:absolute;inset:0;border:3.5px solid #e0e7ff;border-top-color:#4f46e5;border-radius:50%;animation:mob-spin 0.8s linear infinite;box-sizing:border-box;"></div>
                                <img src="../favicon.png" style="width:28px;height:28px;object-fit:contain;animation:mob-logo-pulse 1.4s ease-in-out infinite;" alt="YMCA Logo" onerror="this.onerror=null;this.src='../favicon.ico';">
                            </div>
                            <div style="font-family:'Inter',sans-serif;font-size:13.5px;font-weight:700;color:#1e293b;letter-spacing:0.3px;">Please wait...</div>
                        </div>
                    </div>
                `);
            }
            $('#mob-global-loader').css({'opacity':'1','pointer-events':'auto'});
        }

        function close_overlay() {
            $('#mob-global-loader').css({'opacity':'0','pointer-events':'none'});
            if (typeof $.LoadingOverlay === 'function') {
                try { $.LoadingOverlay('hide'); } catch(e){}
            }
        }
        $(document).ready(function() {
            // Set today's date formatted nicely
            const today = new Date();
            const options = { weekday: 'long', year: 'numeric', month: 'short', day: 'numeric' };
            $('#member-today-date').text(today.toLocaleDateString('en-US', options));
            
            // Set default date input value to YYYY-MM-DD
            const yyyy = today.getFullYear();
            let mm = today.getMonth() + 1;
            let dd = today.getDate();
            if (dd < 10) dd = '0' + dd;
            if (mm < 10) mm = '0' + mm;
            const formattedToday = yyyy + '-' + mm + '-' + dd;
            $('#date_search').val(formattedToday);

            load_groups();
        });

        var user_groups_map = {};

        function load_groups(){
            $.ajax({
                type: "POST",
                url: "api/attendance.php",
                data: { action: 'load_groups' },
                success: function(data) {
                    var obj = jQuery.parseJSON(data);
                    var htm = '<select id="selected_group" class="att-group-select" onchange="selectSessionGroup(this.value)">';
                    var pillHtm = '';
                    user_groups_map = {};
                    var firstGroupId = (obj[0] && obj[0].length > 0) ? obj[0][0].id : 0;
                    for (var i = 0; i < obj[0].length; i++) {
                        var grp = obj[0][i];
                        user_groups_map[grp.id] = grp;
                        htm += '<option value="' + grp.id + '">' + grp.name + '</option>';
                        var isFirst = (i === 0);
                        var bgStyle = isFirst ? 'background: #3b82f6; color: #ffffff; border-color: #3b82f6;' : 'background: var(--bg-card); color: #3b82f6; border-color: rgba(59, 130, 246, 0.3);';
                        var icon = grp.name.toLowerCase().includes('evening') ? '&#9790;' : '&#9728;';
                        pillHtm += '<button id="session_pill_' + grp.id + '" type="button" class="session-pill-btn" onclick="selectSessionGroup(' + grp.id + ')" style="padding: 10px 24px; border-radius: 30px; border: 2px solid; font-weight: 700; font-size: 13px; font-family: \'Inter\', sans-serif; cursor: pointer; transition: all 0.2s; ' + bgStyle + '">' + icon + ' ' + grp.name + '</button>';
                    }
                    htm += '</select>';
                    $('#groups_container').html(htm);
                    $('#session-btn-container').html(pillHtm);

                    if (obj[0].length <= 1) {
                        $('#member-session-selector').hide();
                    } else {
                        $('#member-session-selector').show();
                    }

                    if (firstGroupId > 0) {
                        $('#selected_group').val(firstGroupId);
                    }
                    updateTomorrowDateToggle();
                },
                error: function(xhr, status, error) {
                    console.error('AJAX error: ', status, error);
                    fetchAttendanceDetails();
                }
            });
        }

        function selectSessionGroup(groupId) {
            $('#selected_group').val(groupId);
            $('.session-pill-btn').css({ 'background': 'var(--bg-card)', 'color': '#3b82f6', 'border-color': 'rgba(59, 130, 246, 0.3)' });
            $('#session_pill_' + groupId).css({ 'background': '#3b82f6', 'color': '#ffffff', 'border-color': '#3b82f6' });
            updateTomorrowDateToggle();
        }

        function updateTomorrowDateToggle() {
            var groupId = $('#selected_group').val();
            var grp = user_groups_map[groupId];
            if (grp && parseInt(grp.allow_tomorrow_attendance) === 1) {
                $('#date-toggle-container').css('display', 'flex');
            } else {
                $('#date-toggle-container').hide();
                selectAttDate('today');
            }
        }

        function selectAttDate(type) {
            var targetDate = new Date();
            if (type === 'tomorrow') {
                targetDate.setDate(targetDate.getDate() + 1);
                $('#btn_date_today').css({ 'background': 'var(--bg-card)', 'color': '#64748b', 'border-color': '#cbd5e1' });
                $('#btn_date_tomorrow').css({ 'background': '#3b82f6', 'color': '#ffffff', 'border-color': '#3b82f6' });
            } else {
                $('#btn_date_tomorrow').css({ 'background': 'var(--bg-card)', 'color': '#64748b', 'border-color': '#cbd5e1' });
                $('#btn_date_today').css({ 'background': '#3b82f6', 'color': '#ffffff', 'border-color': '#3b82f6' });
            }

            var yyyy = targetDate.getFullYear();
            var mm = targetDate.getMonth() + 1;
            var dd = targetDate.getDate();
            if (dd < 10) dd = '0' + dd;
            if (mm < 10) mm = '0' + mm;
            var formatted = yyyy + '-' + mm + '-' + dd;
            $('#date_search').val(formatted);

            const opts = { weekday: 'short', year: 'numeric', month: 'short', day: 'numeric' };
            $('#member-today-date').text(targetDate.toLocaleDateString('en-US', opts) + (type === 'tomorrow' ? ' (Tomorrow)' : ''));

            fetchAttendanceDetails();
        }

        function fetchAttendanceDetails(){
            var groupName = $('#selected_group option:selected').text() || 'Session';

            // First check if today is a holiday for this session or all groups
            $.ajax({
                type: "POST",
                url: "api/attendance.php",
                data: {
                    action: 'check_holiday',
                    date: $('#date_search').val(),
                    group: $('#selected_group').val()
                },
                success: function(response) {
                    var res = JSON.parse(response);
                    if (res.is_fixed) {
                        $('#member-action-area').html('<div style="color: #dc2626; font-weight: 600; font-size: 15px; display: inline-flex; align-items: center; gap: 8px; background: rgba(239, 68, 68, 0.1); padding: 10px 24px; border-radius: 30px; border: 1px solid rgba(239, 68, 68, 0.25); font-family: \'Inter\', sans-serif;"><i class="fa fa-lock" style="font-size: 18px;"></i> Attendance Month is Fixed & Locked</div>');
                        $('#present-members-card').show();
                        loadTodayPresentMembers();
                    } else if (res.is_holiday) {
                        $('#member-action-area').html('<div style="color: #ef4444; font-weight: 600; font-size: 15px; display: inline-flex; align-items: center; gap: 8px; background: rgba(239, 68, 68, 0.1); padding: 10px 24px; border-radius: 30px; border: 1px solid rgba(239, 68, 68, 0.25); font-family: \'Inter\', sans-serif;"><i class="fa fa-info-circle" style="font-size: 18px;"></i> Holiday / Leave for ' + groupName + '</div>');
                        $('#present-members-card').hide();
                    } else {
                        // Proceed to fetch attendance
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
                                var isPresent = false;
                                var memId = <?php echo !empty($_SESSION['user_id']) ? $_SESSION['user_id'] : (!empty($_SESSION['login_id']) ? $_SESSION['login_id'] : 0); ?>;
                                for (var i = 0; i < obj.length; i++) {
                                    if (obj[i].member_id == memId || obj[i].member_id == <?php echo !empty($_SESSION['login_id']) ? $_SESSION['login_id'] : 0; ?>) {
                                        isPresent = true;
                                        break;
                                    }
                                }
                                // Always fetch temp status for expected time or absent/half_chance status
                                $.ajax({
                                    type: "POST",
                                    url: "api/attendance.php",
                                    data: {
                                        action: 'fetch_temp_status',
                                        date: $('#date_search').val(),
                                        group: $('#selected_group').val()
                                    },
                                    success: function(resData) {
                                        var tempRes = (typeof resData === 'string') ? JSON.parse(resData) : resData;
                                        var tempStatus = tempRes ? tempRes.temp_status : null;
                                        var expTime = tempRes ? tempRes.expected_time : null;
                                        var timeLabel = expTime ? (' (ETA: ' + formatTime12h(expTime) + ')') : '';

                                        if (isPresent) {
                                            var checkInHtml = '<div style="display: flex; flex-direction: column; align-items: center; gap: 12px;">';
                                            checkInHtml += '  <div style="color: #10b981; font-weight: 600; font-size: 15px; display: inline-flex; align-items: center; gap: 8px; background: rgba(16, 185, 129, 0.1); padding: 10px 24px; border-radius: 30px; border: 1px solid rgba(16, 185, 129, 0.25); font-family: \'Inter\', sans-serif;"><i class="fa fa-check-circle" style="font-size: 18px;"></i> Checked In Successfully' + timeLabel + '</div>';
                                            checkInHtml += '  <button onclick="unmarkMyAttendance()" style="background: none; border: none; color: #ef4444; font-size: 12px; font-weight: 600; cursor: pointer; text-decoration: underline; font-family: \'Inter\', sans-serif;"><i class="fa fa-times"></i> Unmark Attendance</button>';
                                            checkInHtml += '</div>';
                                            $('#member-action-area').html(checkInHtml);
                                        } else if (tempStatus === 'absent') {
                                            var htm = '<div style="display: flex; flex-direction: column; align-items: center; gap: 12px;">';
                                            htm += '  <div style="color: #ef4444; font-weight: 600; font-size: 15px; display: inline-flex; align-items: center; gap: 8px; background: rgba(239, 68, 68, 0.1); padding: 10px 24px; border-radius: 30px; border: 1px solid rgba(239, 68, 68, 0.25); font-family: \'Inter\', sans-serif;"><i class="fa fa-times-circle" style="font-size: 18px;"></i> Marked Absent (Temporary - 2 Days)</div>';
                                            htm += '  <div style="display: flex; gap: 10px;">';
                                            htm += '    <button onclick="markMyAttendance()" style="background:#10b981; color:#fff; border:none; padding:8px 18px; border-radius:20px; font-weight:700; font-size:13px; cursor:pointer;"><i class="fa fa-check-circle"></i> Mark Present</button>';
                                            htm += '    <button onclick="clearMyTempStatus()" style="background:none; border:none; color:#ef4444; font-size:12px; font-weight:600; cursor:pointer; text-decoration:underline;"><i class="fa fa-refresh"></i> Clear Status</button>';
                                            htm += '  </div>';
                                            htm += '</div>';
                                            $('#member-action-area').html(htm);
                                        } else if (tempStatus === 'half_chance') {
                                            var htm = '<div style="display: flex; flex-direction: column; align-items: center; gap: 12px;">';
                                            htm += '  <div style="color: #f59e0b; font-weight: 600; font-size: 15px; display: inline-flex; align-items: center; gap: 8px; background: rgba(245, 158, 11, 0.1); padding: 10px 24px; border-radius: 30px; border: 1px solid rgba(245, 158, 11, 0.25); font-family: \'Inter\', sans-serif;"><i class="fa fa-adjust" style="font-size: 18px;"></i> Marked Half Chance' + timeLabel + ' (Temporary - 2 Days)</div>';
                                            htm += '  <div style="display: flex; gap: 10px;">';
                                            htm += '    <button onclick="markMyAttendance()" style="background:#10b981; color:#fff; border:none; padding:8px 18px; border-radius:20px; font-weight:700; font-size:13px; cursor:pointer;"><i class="fa fa-check-circle"></i> Mark Present</button>';
                                            htm += '    <button onclick="clearMyTempStatus()" style="background:none; border:none; color:#f59e0b; font-size:12px; font-weight:600; cursor:pointer; text-decoration:underline;"><i class="fa fa-refresh"></i> Clear Status</button>';
                                            htm += '  </div>';
                                            htm += '</div>';
                                            $('#member-action-area').html(htm);
                                        } else {
                                             var actionHtm = '<div style="display: flex; flex-direction: column; gap: 12px; width: 100%; text-align: center;">';
                                             actionHtm += '  <div style="font-size: 13px; font-weight: 800; color: #475569; text-transform: uppercase; letter-spacing: 0.5px; margin-bottom: 2px; font-family: \'Inter\', sans-serif;"><i class="fa fa-calendar-check-o" style="color: #3b82f6;"></i> Mark Attendance</div>';
                                             actionHtm += '  <div style="display: grid; grid-template-columns: repeat(3, 1fr); gap: 10px; width: 100%;">';
                                             actionHtm += '    <button onclick="markMyAttendance()" style="background: linear-gradient(135deg, #10b981 0%, #059669 100%); color: white; border: none; border-radius: 16px; padding: 14px 6px; font-size: 13px; font-weight: 800; cursor: pointer; display: flex; flex-direction: column; align-items: center; justify-content: center; gap: 6px; box-shadow: 0 4px 14px rgba(16,185,129,0.3); font-family: \'Inter\', sans-serif; transition: all 0.2s;"><i class="fa fa-check-circle" style="font-size: 20px;"></i> Present</button>';
                                             actionHtm += '    <button onclick="markMyTempStatus(\'absent\')" style="background: linear-gradient(135deg, #ef4444 0%, #dc2626 100%); color: white; border: none; border-radius: 16px; padding: 14px 6px; font-size: 13px; font-weight: 800; cursor: pointer; display: flex; flex-direction: column; align-items: center; justify-content: center; gap: 6px; box-shadow: 0 4px 14px rgba(239,68,68,0.3); font-family: \'Inter\', sans-serif; transition: all 0.2s;"><i class="fa fa-times-circle" style="font-size: 20px;"></i> Absent</button>';
                                             actionHtm += '    <button onclick="markMyTempStatus(\'half_chance\')" style="background: linear-gradient(135deg, #f59e0b 0%, #d97706 100%); color: white; border: none; border-radius: 16px; padding: 14px 6px; font-size: 13px; font-weight: 800; cursor: pointer; display: flex; flex-direction: column; align-items: center; justify-content: center; gap: 6px; box-shadow: 0 4px 14px rgba(245,158,11,0.3); font-family: \'Inter\', sans-serif; transition: all 0.2s;"><i class="fa fa-pie-chart" style="font-size: 20px;"></i> Half Chance</button>';
                                             actionHtm += '  </div>';
                                             actionHtm += '</div>';
                                             $('#member-action-area').html(actionHtm);
                                        }
                                    },
                                    error: function() {
                                        if (isPresent) {
                                            $('#member-action-area').html('<div style="color: #10b981; font-weight: 600; font-size: 15px; display: inline-flex; align-items: center; gap: 8px; background: rgba(16, 185, 129, 0.1); padding: 10px 24px; border-radius: 30px; border: 1px solid rgba(16, 185, 129, 0.25); font-family: \'Inter\', sans-serif;"><i class="fa fa-check-circle" style="font-size: 18px;"></i> Checked In Successfully</div>');
                                        } else {
                                            $('#member-action-area').html('<button onclick="markMyAttendance()" class="member-action-btn"><i class="fa fa-check-circle"></i> Mark Present</button>');
                                        }
                                    }
                                });
                                $('#present-members-card').show();
                                loadTodayPresentMembers();
                            },
                            error: function(xhr, status, error) {
                                console.log('AJAX error: ', status, error);
                            }
                        });
                    }
                },
                error: function() {
                    $('#member-action-area').html('<button onclick="markMyAttendance()" class="member-action-btn"><i class="fa fa-check-circle"></i> Mark Present</button>');
                }
            });
        }

        function formatTime12h(time24) {
            if (!time24) return '';
            var parts = time24.split(':');
            if (parts.length < 2) return time24;
            var h = parseInt(parts[0], 10);
            var m = parts[1];
            var ampm = h >= 12 ? 'PM' : 'AM';
            h = h % 12;
            h = h ? h : 12;
            return h + ':' + m + ' ' + ampm;
        }

        function markMyTempStatus(status) {
            load_overlay();
            var timeVal = $('#expected_arrival_time').val();
            $.ajax({
                type: "POST",
                url: "api/attendance.php",
                data: {
                    action: 'save_temp_status',
                    group: $('#selected_group').val(),
                    date: $('#date_search').val(),
                    status: status,
                    expected_time: timeVal
                },
                success: function(data) {
                    close_overlay();
                    var res = (typeof data === 'string') ? JSON.parse(data) : data;
                    if (res.status === 'success') {
                        alertsuccess(res.message);
                        fetchAttendanceDetails();
                    } else {
                        alerterror(res.message || 'Failed to save status.');
                    }
                },
                error: function() {
                    close_overlay();
                    alerterror('Failed to update status.');
                }
            });
        }

        function clearMyTempStatus() {
            markMyTempStatus('clear');
        }

        function markMyAttendance() {
            load_overlay();
            var timeVal = $('#expected_arrival_time').val();
            $.ajax({               
                type: "POST",
                url: "api/attendance.php",
                data: {
                    action: 'add_attendance',
                    val: $('#selected_group').val(),
                    date: $('#date_search').val(),
                    expected_time: timeVal,
                    member_ids: [<?php echo !empty($_SESSION['user_id']) ? (int)$_SESSION['user_id'] : 0; ?>]
                },
                success: function(data) {  
                    close_overlay();
                    alertsuccess('Attendance Marked Successfully');
                    fetchAttendanceDetails();
                },
                error: function(xhr, status, error) {
                    close_overlay();
                    console.log('AJAX error: ', status, error);
                }
            });     
        }

        function unmarkMyAttendance() {
            swal({
                title: "Are you sure?",
                text: "Do you want to unmark your attendance for today?",
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
                            alertsuccess('Attendance Unmarked Successfully');
                            fetchAttendanceDetails();
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
            $.ajax({
                type: "POST",
                url: "api/attendance.php",
                data: {
                    action: 'load_today_attendance_summary',
                    date: $('#date_search').val(),
                    group: $('#selected_group').val()
                },
                success: function(data) {
                    var obj = (typeof data === 'string') ? JSON.parse(data) : data;
                    var htm = "";
                    if (obj && obj.length > 0) {
                        for (var i = 0; i < obj.length; i++) {
                            var m = obj[i];
                            var imgSrc = '../img/customer.png';
                            if (m.img && m.img != 0 && m.img != '0' && typeof m.img === 'string' && m.img.trim() !== '') {
                                imgSrc = '../image_upload/members/thumbnails/' + m.img;
                            }
                            var fullName = [m.first_name, m.middle_name, m.last_name].filter(function(p){ return p && p.trim(); }).join(' ');
                            
                            var statusBadge = '';
                            var timeStr = m.expected_time ? (' (ETA: ' + formatTime12h(m.expected_time) + ')') : '';

                            if (m.status === 'present') {
                                statusBadge = '<div style="font-size: 11.5px; font-weight: 700; background: rgba(16, 185, 129, 0.1); color: #10b981; padding: 4px 12px; border-radius: 20px; border: 1px solid rgba(16, 185, 129, 0.25); display: inline-flex; align-items: center; gap: 4px;"><i class="fa fa-check"></i> Present' + timeStr + '</div>';
                            } else if (m.status === 'half_chance') {
                                statusBadge = '<div style="font-size: 11.5px; font-weight: 700; background: rgba(245, 158, 11, 0.1); color: #d97706; padding: 4px 12px; border-radius: 20px; border: 1px solid rgba(245, 158, 11, 0.25); display: inline-flex; align-items: center; gap: 4px;"><i class="fa fa-adjust"></i> Half Chance' + timeStr + '</div>';
                            } else if (m.status === 'absent') {
                                statusBadge = '<div style="font-size: 11.5px; font-weight: 700; background: rgba(239, 68, 68, 0.1); color: #dc2626; padding: 4px 12px; border-radius: 20px; border: 1px solid rgba(239, 68, 68, 0.25); display: inline-flex; align-items: center; gap: 4px;"><i class="fa fa-times"></i> Absent</div>';
                            }

                            htm += '<div style="display: flex; align-items: center; justify-content: space-between; padding: 10px 14px; background: var(--bg-main); border-radius: 12px; border: 1px solid var(--border-color); font-family: \'Inter\', sans-serif;">';
                            htm += '  <div style="display: flex; align-items: center; gap: 12px;">';
                            htm += '    <img src="' + imgSrc + '" style="width: 38px; height: 38px; border-radius: 50%; border: 2px solid var(--border-color);" onerror="this.src=\'../img/customer.png\'">';
                            htm += '    <div style="font-size: 14px; font-weight: 600; color: var(--text-primary);">' + fullName + '</div>';
                            htm += '  </div>';
                            htm += '  ' + statusBadge;
                            htm += '</div>';
                        }
                    } else {
                        htm = '<div style="text-align: center; padding: 20px 0; color: #94a3b8; font-size: 13px; font-family: \'Inter\', sans-serif;">No member status recorded yet.</div>';
                    }
                    $('#present-members-list').html(htm);
                },
                error: function(xhr, status, error) {
                    console.log('AJAX error: ', status, error);
                }
            });
        }
    </script>
    <script src="../app_menu/menu.js"></script>
</head>

<body>
    <div id="wrapper">
        <!-- Sidebar -->
        <nav class="navbar-default navbar-static-side" role="navigation">
            <div class="dropdown profile-element">
                <center>
                    <span><img alt="image" class="img-circle" src="../img/customer.png" style="padding-top: 20px;"/></span>
                    <span class="clear"><span class="block m-t-xs"><strong class="font-bold"><?php echo $_SESSION['name']; ?></strong></span></span>
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
                    <div class="navbar-header" style="display: flex; align-items: center; gap: 12px; padding-left: 15px;">
                        <a class="navbar-minimalize minimalize-styl-2 btn btn-primary" href="#"><i class="fa fa-bars"></i></a>
                        <div style="display: flex; align-items: center; gap: 10px; padding: 6px 0;">
                            <img src="../favicon.png" alt="YMCA Logo" style="width: 30px; height: 30px; object-fit: contain; background: #ffffff; padding: 2px; border-radius: 6px; box-shadow: 0 2px 5px rgba(0,0,0,0.1);" onerror="this.onerror=null; this.src='../favicon.ico';">
                            <span style="font-weight: 800; font-size: 16px; color: #1e293b; font-family: 'Inter', sans-serif;">YMCA Member Portal</span>
                        </div>
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

            <!-- Member attendance UI -->
            <div id="member-attendance-container" class="member-container">
                
                <!-- Pending Balance & Quick Pay Card -->
                <div class="member-card" id="pending-balance-card" style="padding:22px 24px; text-align:left; background:#ffffff; border-radius:20px; border:1px solid #e2e8f0; box-shadow:0 4px 20px rgba(0,0,0,0.035); margin-bottom:0;">
                    <div style="display:flex; align-items:center; justify-content:space-between; flex-wrap:wrap; gap:14px;">
                        <div style="display:flex; align-items:center; gap:14px;">
                            <div style="width:46px; height:46px; border-radius:14px; background:linear-gradient(135deg, #1e1b4b, #312e81); color:#ffffff; display:flex; align-items:center; justify-content:center; font-size:20px; box-shadow:0 4px 14px rgba(30,27,75,0.25); flex-shrink:0;">
                                <i class="fa fa-credit-card"></i>
                            </div>
                            <div>
                                <div style="font-size:11px; font-weight:800; color:#64748b; text-transform:uppercase; letter-spacing:0.5px; font-family:'Inter',sans-serif;">Pending Balance</div>
                                <div style="font-size:20px; font-weight:900; color:#0f172a; margin-top:2px; font-family:'Inter',sans-serif;" id="user-pending-balance-val">₹0.00</div>
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

                <!-- Mark Attendance Button Card -->
                <div class="member-card">
                    <div class="member-icon-circle">
                        <i class="fa fa-calendar-check-o"></i>
                    </div>
                    <h2>Today's Attendance</h2>
                    
                    <div style="font-size: 15px; font-weight: 700; color: #3b82f6; background: rgba(59, 130, 246, 0.08); padding: 8px 18px; border-radius: 30px; display: inline-flex; align-items: center; gap: 8px; margin: 12px 0 6px 0; border: 1.5px solid rgba(59, 130, 246, 0.15); font-family: 'Inter', sans-serif;">
                        <i class="fa fa-calendar"></i> 
                        <span id="member-today-date"></span>
                    </div>

                    <!-- Date Toggle Pills (Today / Tomorrow) for groups with tomorrow's attendance enabled -->
                    <div id="date-toggle-container" style="display:none; margin: 6px 0 16px 0; justify-content: center; gap: 8px;">
                        <button id="btn_date_today" type="button" onclick="selectAttDate('today')" style="padding: 6px 18px; border-radius: 20px; border: 1.5px solid #3b82f6; background: #3b82f6; color: #ffffff; font-weight: 700; font-size: 12px; cursor: pointer;"><i class="fa fa-calendar"></i> Today</button>
                        <button id="btn_date_tomorrow" type="button" onclick="selectAttDate('tomorrow')" style="padding: 6px 18px; border-radius: 20px; border: 1.5px solid #cbd5e1; background: var(--bg-card); color: #64748b; font-weight: 700; font-size: 12px; cursor: pointer;"><i class="fa fa-calendar-plus-o"></i> Tomorrow</button>
                    </div>
                    
                    <!-- Session Selector -->
                    <div id="member-session-selector" style="margin: 16px 0 24px 0;">
                        <p style="font-size: 12px; color: var(--text-muted); margin-bottom: 10px; font-family: 'Inter', sans-serif; font-weight: 600; text-transform: uppercase; letter-spacing: 0.5px;">Select Session</p>
                        <div style="display: flex; gap: 10px; justify-content: center; flex-wrap: wrap;" id="session-btn-container">
                            <!-- Populated dynamically via AJAX -->
                        </div>
                    </div>

                    <!-- Expected Arrival Time Input -->
                    <div style="margin: 16px 0 20px 0; text-align: center;">
                        <label style="font-size: 11px; font-weight: 700; color: #64748b; text-transform: uppercase; letter-spacing: 0.5px; margin-bottom: 6px; display: block; font-family: 'Inter', sans-serif;">
                            <i class="fa fa-clock-o" style="color:#3b82f6;"></i> Expected Arrival Time (Optional)
                        </label>
                        <input type="time" id="expected_arrival_time" class="att-group-select" style="max-width: 220px; margin: 0 auto; display: inline-block; text-align: center; height: 42px; font-size: 14px;">
                    </div>

                    <!-- Hidden elements required for background script -->
                    <div style="display:none;">
                        <div id="groups_container"></div>
                        <input type="date" id="date_search">
                    </div>

                    <div id="member-action-area">
                        <button onclick="markMyAttendance()" class="member-action-btn">
                            <i class="fa fa-check-circle"></i> Mark Attendance
                        </button>
                    </div>
                </div>
                
                <!-- Member Status Today List Card -->
                <div id="present-members-card" class="present-list-card" style="display: none;">
                    <h3>
                        <i class="fa fa-users" style="color: #3b82f6;"></i>
                        Today's Member Status & Attendance
                    </h3>
                    
                    <div id="present-members-list" style="display: flex; flex-direction: column; gap: 12px;">
                        <!-- List populated dynamically -->
                        <div style="text-align: center; padding: 20px 0; color: #94a3b8; font-size: 13px; font-family: 'Inter', sans-serif;">
                            No member status recorded yet.
                        </div>
                    </div>
                </div>
            </div>

        </div><!-- end page-wrapper -->
    </div><!-- end wrapper -->

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

    <!-- Scripts -->
    <script src="../js/jquery-3.1.1.min.js"></script>
    <script src="../js/bootstrap.min.js"></script>
    <script src="../js/plugins/metisMenu/jquery.metisMenu.js"></script>
    <script src="../js/plugins/slimscroll/jquery.slimscroll.min.js"></script>
    <script src="../js/inspinia.js"></script>
    <script src="../js/plugins/pace/pace.min.js"></script>

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
