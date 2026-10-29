<?php
session_start();
include_once '../app_common/db_connect.php';
include_once '../app_common/auth_helper.php';

$login_id = (int)($_SESSION['login_id'] ?? 0);
if (empty($login_id)) {
    header("Location: ../app_login_manager/logout.php");
    exit();
}
if (isNormalMember($login_id)) {
    header("Location: user_attendance.php");
    exit();
}
?>
<!DOCTYPE html>
<html>

<head>

    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>Monthly Attendance</title>

    <link href="../css/bootstrap.min.css" rel="stylesheet">
    <link href="../font-awesome/css/font-awesome.css" rel="stylesheet">

    <link href="../css/animate.css" rel="stylesheet">
    <link href="../css/style.css" rel="stylesheet">
    <link href="../css/custom_modern.css" rel="stylesheet">
    
    <script src="https://cdnjs.cloudflare.com/ajax/libs/jquery/3.6.0/jquery.min.js"></script>
    <link href="../css/plugins/sweetalert/sweetalert.css" rel="stylesheet">
    <script src="../js/plugins/sweetalert/sweetalert.min.js"></script>
    <script src="../app_js/sweetalert-finez.js"></script>
    
    <style>
        .settings-card-wrapper {
            background-color: var(--card-bg, #ffffff);
            border-radius: var(--border-radius-lg, 24px);
            border: 1px solid var(--border-color, #e2e8f0);
            box-shadow: var(--shadow-md, 0 10px 30px -10px rgba(99, 102, 241, 0.08));
            padding: 30px;
            margin-top: 24px;
            transition: all 0.3s ease;
        }

        .dark-theme .settings-card-wrapper {
            border-color: rgba(255, 255, 255, 0.06);
        }

        .btn-set-attendance {
            background: var(--primary-gradient, linear-gradient(135deg, #6366f1 0%, #4f46e5 100%)) !important;
            color: #ffffff !important;
            border-radius: 12px !important;
            padding: 10px 20px !important;
            font-weight: 700 !important;
            border: none !important;
            box-shadow: 0 4px 10px rgba(99, 102, 241, 0.2) !important;
            transition: all 0.2s ease;
        }

        .btn-set-attendance:hover {
            transform: translateY(-1px);
            opacity: 0.95;
        }

        .status-badge-custom {
            border-radius: 8px !important;
            font-size: 12px !important;
            font-weight: 700 !important;
            padding: 4px 10px !important;
            display: inline-block;
            border: none !important;
        }

        .status-badge-custom.completed {
            background-color: rgba(16, 185, 129, 0.08) !important;
            color: #10b981 !important;
        }

        .btn-table-action {
            border-radius: 10px !important;
            font-size: 12.5px !important;
            font-weight: 700 !important;
            padding: 6px 12px !important;
            display: inline-flex;
            align-items: center;
            gap: 5px;
            transition: all 0.2s ease;
            border: 1px solid transparent;
        }

        .btn-table-action.receivable {
            background-color: rgba(59, 130, 246, 0.08) !important;
            color: #3b82f6 !important;
        }

        .btn-table-action.receivable:hover {
            background-color: #3b82f6 !important;
            color: #ffffff !important;
            transform: translateY(-1px);
        }

        .modal-body label {
            font-weight: 700 !important;
            font-size: 13px;
            color: var(--text-muted, #475569);
            margin-bottom: 6px;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }

        .form-control {
            border-radius: 12px !important;
            border: 1px solid var(--border-color, #e2e8f0) !important;
            padding: 10px 14px !important;
            background-color: var(--card-bg, #ffffff) !important;
            color: var(--text-primary, #0f172a) !important;
            transition: all 0.2s ease;
        }

        .form-control:focus {
            border-color: var(--primary-color, #6366f1) !important;
            box-shadow: 0 0 0 4px rgba(99, 102, 241, 0.12) !important;
        }
    </style>
    
    <script>
        $(document).ready(function() {          
            loadData(1); // Function to load data for a specific page       
        });  

        function getMonthLabel(fromDateStr) {
            if (!fromDateStr) return '';
            var parts = fromDateStr.split('-');
            if (parts.length >= 2) {
                var year = parts[0];
                var monthIdx = parseInt(parts[1], 10) - 1;
                var monthNames = ["January", "February", "March", "April", "May", "June", "July", "August", "September", "October", "November", "December"];
                if (monthIdx >= 0 && monthIdx < 12) {
                    return monthNames[monthIdx] + " " + year;
                }
            }
            return fromDateStr;
        }

        // function to load attendance details start
        function loadData(page) {
            $('#hdn_current_page').val(page); //used for Status Update function
            var filterGroup = $('#filter_group_id').val() || 0;
            console.log("Loading data for page:", page);
            $.ajax({               
               type: "POST",
               url: "api/monthly_attendance.php",
               data: {
                action: 'load_data',
                page: page, 
                group_id: filterGroup
               },
                success: function(data) {

                    var obj = jQuery.parseJSON(data);
                                       
                    var totalrows = obj[0].total_rows;
                    var htm="";
                    
                    htm=htm+ "<div class='table-responsive'>";
                    htm=htm+ "<table class='table table-striped table-hover' style='margin-bottom:0;'>";
                    htm=htm+ "<thead>";
                    htm=htm+ "<tr>";
                    htm=htm+ "<th>Month</th>";
                    htm=htm+ "<th>Member Name</th>";
                    htm=htm+ "<th>Batch / Group</th>";
                    htm=htm+ "<th>Days Attended</th>";
                    htm=htm+ "<th style='text-align: right;'>Receivable Status</th>";
                    htm=htm+ "</tr>";
                    htm=htm+ "</thead>";
                    htm=htm+ "<tbody>";
                    for (var i = 0; i < obj[1].length; i++) {
                        var item = obj[1][i];
                        var groupName = item.group_name ? item.group_name : 'General';
                        var isGuest = (parseInt(item.member_type, 10) === 1);
                        var guestBadge = isGuest ? " <span class='badge' style='background: linear-gradient(135deg, #fff7ed 0%, #ffedd5 100%); color: #c2410c; border: 1px solid rgba(249, 115, 22, 0.3); box-shadow: 0 2px 6px rgba(249, 115, 22, 0.12); font-size: 10px; font-weight: 800; padding: 3px 8px 3px 6px; border-radius: 16px; letter-spacing: 0.5px; margin-left: 6px; display: inline-flex; align-items: center; gap: 3px; vertical-align: middle;'><i class='fa fa-star' style='color:#f59e0b; font-size:9px;'></i> GUEST</span>" : "";

                        htm=htm+ "<tr>";
                        htm=htm+ "<td style='vertical-align:middle; font-weight:700; color:var(--text-primary);'>"+getMonthLabel(item.from_date)+"</td>";
                        htm=htm+ "<td style='vertical-align:middle; font-weight:700; color:var(--text-primary);'>"+item.first_name+" "+(item.middle_name ? item.middle_name + " " : "")+item.last_name+guestBadge+"</td>";
                        htm=htm+ "<td style='vertical-align:middle;'><span class='badge' style='padding: 6px 12px; border-radius:8px; background-color: rgba(99, 102, 241, 0.1); color: #6366f1; font-weight:700;'>"+groupName+"</span></td>";
                        htm=htm+ "<td style='vertical-align:middle; font-weight:700;'><span class='badge badge-primary' style='padding: 6px 12px; border-radius:8px;'>"+item.attendance+" Days</span></td>";
                        
                        htm=htm+ "<td style='vertical-align:middle; text-align: right;'>";
                        if(parseInt(item.isreceiveble, 10) === 1){
                            htm=htm+ "<span class='status-badge-custom completed'><i class='fa fa-check-circle'></i> Generated</span>";
                        }
                        else if(parseInt(item.attendance, 10) > 0){
                            htm=htm+ "<span class='badge' style='background:rgba(245,158,11,0.1); color:#d97706; font-weight:700; padding:6px 12px; border-radius:8px;'><i class='fa fa-clock-o'></i> Processing</span>";
                        }
                        else{
                            htm=htm+ "<span class='badge' style='background:rgba(100,116,139,0.1); color:#64748b; font-weight:700; padding:6px 12px; border-radius:8px;'><i class='fa fa-minus-circle'></i> No Fee (0 Days)</span>";
                        }
                        htm=htm+ "</td>";
                        htm=htm+ "</tr>";
                            
                    }                
                    htm=htm+ "</tbody>";
                    htm=htm+ "</table>";
                    htm=htm+ "</div>";

                    $('#table_client').html(htm);
                    var htmpage= paginate(totalrows,page);
                    $('#table_client').append(htmpage);
                },
                error: function(xhr, status, error) {
                   console.log('AJAX error: ', status, error);
                }
            });
            loadMenu();
            load_groups();
            loadPendingMonths();
        }

        function showFixAttendanceRequiredAlert(monthVal, monthLabel) {
            if (!monthLabel) {
                monthLabel = getMonthLabel(monthVal + "-01");
            }
            swal({
                title: "Fix Attendance Required",
                text: "Attendance for " + monthLabel + " is not fixed. Please fix the attendance for this month before processing.",
                type: "warning",
                showCancelButton: true,
                confirmButtonColor: "#3b82f6",
                confirmButtonText: "Fix Attendance Now",
                cancelButtonText: "Cancel",
                closeOnConfirm: true
            }, function (isConfirm) {
                if (isConfirm) {
                    window.location.href = "monthly_attendance_report.php?month=" + monthVal;
                }
            });
        }

        function loadPendingMonths() {
            $.ajax({
                type: "POST",
                url: "api/monthly_attendance.php",
                data: { action: 'load_pending_months' },
                success: function(data) {
                    var list = jQuery.parseJSON(data);
                    var htm = "";
                    if (list && list.length > 0) {
                        htm += "<div style='background: var(--card-bg, #ffffff); border-radius: 20px; border: 1.5px solid #fef3c7; box-shadow: 0 4px 20px rgba(245,158,11,0.08); padding: 24px; margin-top: 24px;'>";
                        htm += "<div style='display:flex; align-items:center; justify-content:space-between; margin-bottom: 16px; flex-wrap:wrap; gap:10px;'>";
                        htm += "<h4 style='font-weight:800; font-size:16px; margin:0; color:#d97706; display:flex; align-items:center; gap:8px;'><i class='fa fa-clock-o'></i> Pending Months to Process Attendance</h4>";
                        htm += "<span class='badge' style='background:#fef3c7; color:#d97706; font-weight:700; padding:6px 12px; border-radius:10px; font-size:12px;'>" + list.length + " Pending Batch/Month(s)</span>";
                        htm += "</div>";
                        htm += "<div class='row' style='row-gap: 15px;'>";
                        for (var i = 0; i < list.length; i++) {
                            var item = list[i];
                            var lockBadge = "";
                            if (item.is_fixed) {
                                lockBadge = "<span class='badge' style='background:rgba(16,185,129,0.1); color:#10b981; font-weight:700; padding:4px 8px; border-radius:6px; font-size:11px;'><i class='fa fa-check-circle'></i> Fixed</span>";
                            } else if (!item.is_month_ended) {
                                lockBadge = "<span class='badge' style='background:rgba(100,116,139,0.1); color:#64748b; font-weight:700; padding:4px 8px; border-radius:6px; font-size:11px;'><i class='fa fa-clock-o'></i> Month Ongoing</span>";
                            } else {
                                lockBadge = "<span class='badge' style='background:rgba(16,185,129,0.1); color:#10b981; font-weight:700; padding:4px 8px; border-radius:6px; font-size:11px;'><i class='fa fa-calendar-check-o'></i> Ready to Process</span>";
                            }

                            htm += "<div class='col-md-6 col-lg-4'>";
                            htm += "<div style='border: 1px solid #fde68a; background: #fffdf5; border-radius: 14px; padding: 16px; transition: all 0.2s;'>";
                            htm += "<div style='display:flex; justify-content:space-between; align-items:flex-start; margin-bottom:8px; gap:8px;'>";
                            htm += "<strong style='font-size:15px; color:#0f172a;'>" + item.month_label + "</strong>";
                            htm += "<div><span class='badge' style='background:rgba(99, 102, 241, 0.1); color:#6366f1; font-weight:700; padding:4px 8px; border-radius:6px; font-size:11px; margin-right:4px;'>" + item.group_name + "</span>" + lockBadge + "</div>";
                            htm += "</div>";
                            htm += "<div style='font-size:12.5px; color:#64748b; margin-bottom:12px;'>";
                            htm += "<i class='fa fa-users'></i> " + item.total_members + " Members &bull; <i class='fa fa-calendar'></i> " + item.attendance_days + " Days Recorded";
                            htm += "</div>";
                            htm += "<button type='button' class='btn btn-xs btn-primary' style='border-radius:8px; font-weight:700; padding:7px 14px; width:100%; font-size:12px;' onclick='quickProcessAttendance(\"" + item.month_val + "\", " + item.group_id + ")'><i class='fa fa-flash'></i> Process " + item.month_label + "</button>";
                            htm += "</div>";
                            htm += "</div>";
                        }
                        htm += "</div>";
                        htm += "</div>";
                    }
                    $('#pending_months_container').html(htm);
                }
            });
        }

        function quickProcessAttendance(monthVal, groupId) {
            var parts = monthVal.split('-');
            var year = parseInt(parts[0], 10);
            var month = parseInt(parts[1], 10);
            var fromDateStr = year + "-" + ("0" + month).slice(-2) + "-01";
            var lastDayDate = new Date(year, month, 0);
            var toDateStr = year + "-" + ("0" + month).slice(-2) + "-" + ("0" + lastDayDate.getDate()).slice(-2);
            var monthLabel = getMonthLabel(fromDateStr);

            var today = new Date();
            var currentYr = today.getFullYear();
            var currentMo = today.getMonth() + 1;

            if (year > currentYr || (year === currentYr && month >= currentMo)) {
                var monthNames = ["January", "February", "March", "April", "May", "June", "July", "August", "September", "October", "November", "December"];
                var selMonthName = monthNames[month - 1];
                alertinfo("Monthly attendance for " + selMonthName + " " + year + " can only be processed after the month ends.");
                return;
            }

            swal({
                title: "Process Monthly Attendance?",
                text: "Do you want to compile attendance and generate fee receivables for " + monthLabel + "? (Previous month will be locked automatically).",
                type: "warning",
                showCancelButton: true,
                confirmButtonColor: "#6366f1",
                confirmButtonText: "Yes, Process!",
                cancelButtonText: "Cancel",
                closeOnConfirm: false,
                closeOnCancel: true
            }, function (isConfirm) {
                if (isConfirm) {
                    $.ajax({
                        type: "POST",
                        url: "api/monthly_attendance.php",
                        data: {
                            action: 'save_attendance',
                            from_date: fromDateStr,
                            to_date: toDateStr,
                            group_id: groupId
                        },
                        success: function(response) {
                            alertsuccess('Monthly attendance processed and fee receivables generated successfully');
                            loadPendingMonths();
                            loadData($('#hdn_current_page').val());
                        },
                        error: function(xhr) {
                            try {
                                var msgObj = JSON.parse(xhr.responseText);
                                alerterror(msgObj, xhr);
                            } catch(e) {
                                alerterror({ Message: "Unexpected error occurred." }, xhr);
                            }
                        }
                    });
                }
            });
        }

        function load_groups(){
            $.ajax({
                type: "POST",
                url: "api/monthly_attendance.php",
                data: {
                    action: 'load_groups',
                },
                success: function(data) {
                    var obj = jQuery.parseJSON(data);
                    var opts = "<option value='0'>All Batches / Groups</option>";
                    if (obj[0]) {
                        for (var i = 0; i < obj[0].length; i++) {
                            opts += "<option value='"+obj[0][i].id+"'>"+obj[0][i].name+"</option>";
                        }
                    }

                    var curFilter = $('#filter_group_id').val() || 0;
                    $('#filter_group_id').html(opts).val(curFilter);

                    var curModalGrp = $('#selected_group').val() || 0;
                    $('#select_groups').html("<div class='dropdown form-group'><select id='selected_group' class='form-control'>" + opts + "</select></div>").find('#selected_group').val(curModalGrp);

                    var curUndoGrp = $('#undo_selected_group').val() || 0;
                    $('#undo_select_groups').html("<div class='dropdown form-group'><select id='undo_selected_group' class='form-control'>" + opts + "</select></div>").find('#undo_selected_group').val(curUndoGrp);
                },
                error: function(xhr, status, error) {
                    console.error('AJAX error: ', status, error);
                }
            });
        }

        function addMonthlyAttendance(){
            load_groups();
            $('#attendanceModel').modal('show');
        }

        function closeaddMonthlyAttendance(){
            $('#attendance_form')[0].reset();
            $('#attendanceModel').modal('hide');
        }

        function openUndoModal() {
            load_groups();
            $('#undoAttendanceModal').modal('show');
        }

        function closeUndoModal() {
            $('#undo_attendance_form')[0].reset();
            $('#undoAttendanceModal').modal('hide');
        }

        function submitUndoMonthlyAttendance() {
            var selectedMonthVal = $("#txt_undo_month").val();

            if (!selectedMonthVal) {
                alertinfo("Please select a month to undo.");
                return;
            }

            var parts = selectedMonthVal.split('-');
            var year = parseInt(parts[0], 10);
            var month = parseInt(parts[1], 10);
            var fromDateStr = year + "-" + ("0" + month).slice(-2) + "-01";
            var lastDayDate = new Date(year, month, 0);
            var toDateStr = year + "-" + ("0" + month).slice(-2) + "-" + ("0" + lastDayDate.getDate()).slice(-2);
            var monthLabel = getMonthLabel(fromDateStr);
            var groupId = $('#undo_selected_group').val() || 0;

            swal({
                title: "Undo Processed Month?",
                text: "Are you sure you want to reset and undo attendance & fee receivables for " + monthLabel + "? This will remove generated receivables so the month can be recalculated and reprocessed.",
                type: "warning",
                showCancelButton: true,
                confirmButtonColor: "#ef4444",
                confirmButtonText: "Yes, Reset & Undo!",
                cancelButtonText: "Cancel",
                closeOnConfirm: false,
                closeOnCancel: true
            },
            function (isConfirm) {
                if (isConfirm){
                    $.ajax({
                        type: "POST",
                        url: "api/monthly_attendance.php",
                        data: {
                            action: 'undo_monthly_attendance',
                            from_date: fromDateStr,
                            to_date: toDateStr,
                            group_id: groupId
                        },
                        success: function(response) {
                            closeUndoModal();
                            alertsuccess('Monthly attendance and receivables reset successfully');
                            loadPendingMonths();
                            loadData($('#hdn_current_page').val());
                        },
                        error: function (xhr, status) {
                            try {
                                var msgObj = JSON.parse(xhr.responseText);
                                alerterror(msgObj, xhr); 
                            } catch (e) {
                                alerterror({ Message: "Unexpected error occurred." }, xhr);
                            }
                        }
                    });
                }
            });
        }

        function saveMonthlyAttendance() {
            var selectedMonthVal = $("#txt_attendance_month").val();

            if (!selectedMonthVal) {
                alertinfo("Please select a month.");
                return;
            }

            var parts = selectedMonthVal.split('-');
            var year = parseInt(parts[0], 10);
            var month = parseInt(parts[1], 10);

            var today = new Date();
            var currentYr = today.getFullYear();
            var currentMo = today.getMonth() + 1;

            if (year > currentYr || (year === currentYr && month >= currentMo)) {
                var monthNames = ["January", "February", "March", "April", "May", "June", "July", "August", "September", "October", "November", "December"];
                var selMonthName = monthNames[month - 1];
                alertinfo("Monthly attendance for " + selMonthName + " " + year + " can only be processed after the month ends.");
                return;
            }

            // Calculate first day of the month
            var fromDateStr = year + "-" + ("0" + month).slice(-2) + "-01";

            // Calculate last day of the month
            var lastDayDate = new Date(year, month, 0);
            var toDateStr = year + "-" + ("0" + month).slice(-2) + "-" + ("0" + lastDayDate.getDate()).slice(-2);

            swal({
                title: "Are you sure?",
                text: "Do you want to save this data!",
                type: "warning",
                showCancelButton: true,
                confirmButtonColor: "#DD6B55",
                confirmButtonText: "Yes, Save!",
                cancelButtonText: "Cancel",
                closeOnConfirm: false,
                closeOnCancel: true
            },
            function (isConfirm) {
                if (isConfirm){
                    var data = {
                        action: 'save_attendance',
                        from_date: fromDateStr,
                        to_date: toDateStr,
                        group_id: $('#selected_group').val() || 0,
                    };
                    $.ajax({
                        type: "POST",
                        url: "api/monthly_attendance.php",
                        data: data,
                        success: function(response) {
                            closeaddMonthlyAttendance();
                            alertsuccess('Saved Successfully');
                            loadData($('#hdn_current_page').val());
                        },
                        error: function (xhr, status) {
                            closeaddMonthlyAttendance();
                            try {
                                var msgObj = JSON.parse(xhr.responseText);
                                if (msgObj.errmsg) {
                                    msgObj.Message = msgObj.errmsg;
                                }
                                alerterror(msgObj, xhr); 
                            } catch (e) {
                                alerterror({ Message: "Unexpected error occurred." }, xhr);
                            }
                            $('#attendance_form')[0].reset();
                        }
                    });
                }
            });
        }
    </script>
    <script src="../app_pagination/pagination.js"></script>
    <script src="../app_menu/menu.js"></script>
</head>

<body>
    <input type="hidden" id="hdn_current_page"  value="0">
    <input type="hidden" id="hdn_id"  value="0">
    <input type="hidden" id="hdn_member_id"  value="0">
    <input type="hidden" id="hdn_from_date"  value="0">
    <input type="hidden" id="hdn_to_date"  value="0">
    <input type="hidden" id="hdn_attendance"  value="0">
    
    <div id="wrapper">
        <!-- navigation start -->
        <nav class="navbar-default navbar-static-side" role="navigation">
            <div class="dropdown profile-element">
                <center>
                    <span><img alt="image" class="img-circle" src="../img/customer.png" style="padding-top: 20px;"/></span>
                    <span class="clear"> <span class="block m-t-xs"> <strong class="font-bold"><?php echo $_SESSION['name']; ?></strong>
                </center>
            </div>
            <div class="sidebar-collapse" id="divMenuContainer">
                <!-- menu injected via ajax -->
            </div>
        </nav>
        <!-- navigation end -->
        
        <div id="page-wrapper" class="gray-bg">
            <!-- header start -->
            <div class="row border-bottom">
                <nav class="navbar navbar-static-top" role="navigation" style="margin-bottom: 0">
                    <div class="navbar-header">
                        <a class="navbar-minimalize minimalize-styl-2 btn btn-primary " href="#"><i class="fa fa-bars"></i> </a>
                    </div>
                    <ul class="nav navbar-top-links navbar-right">     
                        <form action="../app_login_manager/logout.php" method="post"></form>        
                            <li>
                                <a href="../app_login_manager/logout.php" style="color: #147ad1";>
                                    <i class="fa fa-sign-out"></i> Log out
                                </a>
                            </li>
                        </form>
                    </ul>
                </nav>
            </div>
            <!-- header end -->
            
            <!-- page heading starts -->
            <div class="row wrapper border-bottom white-bg page-heading" style="padding: 20px 30px; display:flex; align-items:center; justify-content:space-between; flex-wrap:wrap; gap:15px; border-bottom: 1px solid var(--border-color, #e2e8f0) !important;">
                <div>
                    <h2 style="font-weight: 800; font-size: 24px; letter-spacing: -0.5px; margin: 0 !important; color: var(--text-primary, #0f172a);">Monthly Attendance Process</h2>
                </div>
                <div style="display: flex; gap: 10px; flex-wrap: wrap;">
                    <button type="button" class="btn btn-outline btn-danger" style="border-radius:12px; font-weight:700; padding:10px 18px; border: 1.5px solid #ef4444; color:#ef4444; background: #fff5f5;" onclick="openUndoModal()"><i class="fa fa-undo"></i> Undo Processed Month</button>
                    <button type="button" class="btn-set-attendance" onclick="addMonthlyAttendance(0)"><i class="fa fa-cogs"></i> Process Monthly Attendance</button>
                </div>
            </div>
            <!-- page heading ends -->
            
            <div id="pending_months_container"></div>

            <div class="settings-card-wrapper" style="margin-bottom: 30px;">
                <div class="wrapper wrapper-content animated fadeInRight" id="table_client" style="padding: 0;">
                    <!-- data injected Dynamically via ajax -->
                </div>
            </div>
        </div>
    
        <!-- popup modal for add attendace of a month starts -->
        <div class="modal inmodal" id="attendanceModel" tabindex="-1" role="dialog" aria-hidden="true">
            <div class="modal-dialog">
                <div class="modal-content animated bounceInRight" style="border-radius: 20px; overflow: hidden; border: none; box-shadow: var(--shadow-lg);">
                    <form method="POST" id="attendance_form">
                        <div class="modal-header" style="background: var(--primary-gradient, linear-gradient(135deg, #6366f1 0%, #4f46e5 100%)); padding: 24px 30px; color: #ffffff; text-align: left;">
                            <button type="button" class="close" data-dismiss="modal" style="color: #ffffff; opacity: 0.8; font-size: 24px;" onclick="closeaddMonthlyAttendance();">&times;</button>
                            <h3 style="margin: 0; font-weight: 800; font-size: 20px; letter-spacing: -0.5px;"><i class="fa fa-calendar-check-o"></i> Process Monthly Attendance</h3>
                            <p style="margin: 4px 0 0 0; opacity: 0.9; font-size: 13.5px;">Compile monthly attendance and automatically generate member fee receivables</p>
                        </div>
                        <div class="modal-body" style="padding: 30px; background: var(--card-bg, #ffffff);">
                            <div class="form-group">
                                <label>Select Month</label>
                                <input type="month" id="txt_attendance_month" name="attendance_month" class="form-control">
                            </div>
                            <div class="form-group">
                                <label>Select Batch / Group</label>
                                <div id="select_groups">
                                    <!-- dropdown injected via ajax -->
                                </div>
                            </div>
                        </div>
                        <div class="modal-footer" style="background: var(--card-bg, #ffffff); border-top: 1px solid var(--border-color, #e2e8f0); padding: 18px 30px; display: flex; justify-content: flex-end; gap: 12px;">
                            <button type="button" class="btn btn-white" style="border-radius: 10px; font-weight: 700; padding: 8px 16px;" onclick="closeaddMonthlyAttendance();">Close</button>
                            <button type="button" class="btn btn-primary" style="border-radius: 10px; font-weight: 700; padding: 8px 20px;" onclick="saveMonthlyAttendance();">Process</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
        <!-- popup modal for add attendance of a month ends -->

        <!-- popup modal for undo monthly attendance starts -->
        <div class="modal inmodal" id="undoAttendanceModal" tabindex="-1" role="dialog" aria-hidden="true">
            <div class="modal-dialog">
                <div class="modal-content animated bounceInRight" style="border-radius: 20px; overflow: hidden; border: none; box-shadow: var(--shadow-lg);">
                    <form method="POST" id="undo_attendance_form">
                        <div class="modal-header" style="background: linear-gradient(135deg, #ef4444 0%, #dc2626 100%); padding: 24px 30px; color: #ffffff; text-align: left;">
                            <button type="button" class="close" data-dismiss="modal" style="color: #ffffff; opacity: 0.8; font-size: 24px;" onclick="closeUndoModal();">&times;</button>
                            <h3 style="margin: 0; font-weight: 800; font-size: 20px; letter-spacing: -0.5px;"><i class="fa fa-undo"></i> Undo Processed Month</h3>
                            <p style="margin: 4px 0 0 0; opacity: 0.9; font-size: 13.5px;">Reset processed attendance & uncollected fee receivables to re-compile</p>
                        </div>
                        <div class="modal-body" style="padding: 30px; background: var(--card-bg, #ffffff);">
                            <div class="alert alert-warning" style="border-radius:12px; font-size:13px; font-weight:600;">
                                <i class="fa fa-exclamation-triangle"></i> Undoing a month will remove generated monthly fee receivables and restore the month to pending status so it can be reprocessed under new fee settings.
                            </div>
                            <div class="form-group">
                                <label>Select Month to Reset</label>
                                <input type="month" id="txt_undo_month" name="undo_month" class="form-control">
                            </div>
                            <div class="form-group">
                                <label>Select Batch / Group</label>
                                <div id="undo_select_groups">
                                    <!-- dropdown injected via ajax -->
                                </div>
                            </div>
                        </div>
                        <div class="modal-footer" style="background: var(--card-bg, #ffffff); border-top: 1px solid var(--border-color, #e2e8f0); padding: 18px 30px; display: flex; justify-content: flex-end; gap: 12px;">
                            <button type="button" class="btn btn-white" style="border-radius: 10px; font-weight: 700; padding: 8px 16px;" onclick="closeUndoModal();">Close</button>
                            <button type="button" class="btn btn-danger" style="border-radius: 10px; font-weight: 700; padding: 8px 20px; background:#ef4444; border:none;" onclick="submitUndoMonthlyAttendance();">Reset & Undo</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
        <!-- popup modal for undo monthly attendance ends -->
    </div>
       
    <!-- Mainly scripts -->
    <script src="../js/jquery-3.1.1.min.js"></script>
    <script src="../js/bootstrap.min.js"></script>
    <script src="../js/plugins/metisMenu/jquery.metisMenu.js"></script>
    <script src="../js/plugins/slimscroll/jquery.slimscroll.min.js"></script>

    <!-- Custom and plugin javascript -->
    <script src="../js/inspinia.js"></script>
    <script src="../js/plugins/pace/pace.min.js"></script>
    <script src="../js/loadingoverlay.min.js"></script>

</body>

</html>
