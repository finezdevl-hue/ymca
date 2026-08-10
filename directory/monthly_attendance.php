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
                    htm=htm+ "<th style='text-align: right;'>Action</th>";
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
                        if(item.isreceiveble==0){
                            htm=htm+ "<button type='button' class='btn btn-table-action receivable' onclick='setReceiveble(" + item.id + "," + item.member_id + "," + item.attendance + ",\"" + item.from_date + "\",\"" + item.to_date + "\");'><i class='fa fa-plus-circle'></i> Set Receivable</button>";
                        }
                        else{
                            htm=htm+ "<button type='button' class='status-badge-custom completed' disabled><i class='fa fa-check-circle'></i> Created</button>";
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
            load_heads();
            load_closing_years();
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
                            var lockBadge = item.is_fixed
                                ? "<span class='badge' style='background:rgba(16,185,129,0.1); color:#10b981; font-weight:700; padding:4px 8px; border-radius:6px; font-size:11px;'><i class='fa fa-check-circle'></i> Fixed</span>"
                                : "<span class='badge' style='background:rgba(239,68,68,0.1); color:#ef4444; font-weight:700; padding:4px 8px; border-radius:6px; font-size:11px;'><i class='fa fa-lock'></i> Not Fixed</span>";

                            htm += "<div class='col-md-6 col-lg-4'>";
                            htm += "<div style='border: 1px solid #fde68a; background: #fffdf5; border-radius: 14px; padding: 16px; transition: all 0.2s;'>";
                            htm += "<div style='display:flex; justify-content:space-between; align-items:flex-start; margin-bottom:8px; gap:8px;'>";
                            htm += "<strong style='font-size:15px; color:#0f172a;'>" + item.month_label + "</strong>";
                            htm += "<div><span class='badge' style='background:rgba(99, 102, 241, 0.1); color:#6366f1; font-weight:700; padding:4px 8px; border-radius:6px; font-size:11px; margin-right:4px;'>" + item.group_name + "</span>" + lockBadge + "</div>";
                            htm += "</div>";
                            htm += "<div style='font-size:12.5px; color:#64748b; margin-bottom:12px;'>";
                            htm += "<i class='fa fa-users'></i> " + item.total_members + " Members &bull; <i class='fa fa-calendar'></i> " + item.attendance_days + " Days Recorded";
                            htm += "</div>";
                            htm += "<button type='button' class='btn btn-xs btn-primary' style='border-radius:8px; font-weight:700; padding:7px 14px; width:100%; font-size:12px;' onclick='quickProcessAttendance(\"" + item.month_val + "\", " + item.group_id + ", " + (item.is_fixed ? "true" : "false") + ")'><i class='fa fa-flash'></i> Process " + item.month_label + "</button>";
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

        function quickProcessAttendance(monthVal, groupId, isFixed) {
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

            if (isFixed === false) {
                showFixAttendanceRequiredAlert(monthVal, monthLabel);
                return;
            }

            swal({
                title: "Process Monthly Attendance?",
                text: "Do you want to compile attendance for " + monthLabel + "?",
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
                            alertsuccess('Monthly attendance processed successfully');
                            loadPendingMonths();
                            loadData($('#hdn_current_page').val());
                        },
                        error: function(xhr) {
                            try {
                                var msgObj = JSON.parse(xhr.responseText);
                                if (msgObj && msgObj.fixed_required) {
                                    showFixAttendanceRequiredAlert(monthVal, monthLabel);
                                    return;
                                }
                                alerterror(msgObj, xhr);
                            } catch(e) {
                                alerterror({ Message: "Unexpected error occurred." }, xhr);
                            }
                        }
                    });
                }
            });
        }
    </script>

    <script>
        function load_heads(){
            $.ajax({
                type: "POST",
                url: "api/monthly_attendance.php",
                data: {
                action: 'load_heads',
                },
                success: function(data) {
                    var obj = jQuery.parseJSON(data);
                    var htm="";
                    htm=htm+ "<div class='dropdown form-group'><select id='selected_head' class='form-control'>";
                    for (var i = 0; i < obj[0].length; i++) {
                        htm=htm+"<option value='"+obj[0][i].id+"'>"+obj[0][i].name+"</option>";
                    }                
                    htm=htm+"</select></div>";

                    $('#select_heads').html(htm);
                },
                error: function(xhr, status, error) {
                    console.error('AJAX error: ', status, error);
                }
            });
        }

        var isBulkMode = false;

        function setAllReceivebles() {
            isBulkMode = true;
            $('#payment_modal_title').text("Allocate Bulk Fee Receivable");
            $('#payment_modal_subtitle').text("Assign calculated dues for pending member attendance records in one click");
            $('#payment_btn_text').text("Create Receivables");
            
            $('#group_payable_date').hide();
            $('#group_txt_attendance').hide();
            $('#group_txt_fees').hide();
            $('#group_bulk_group').show();

            $('#paymentModel').modal('show');
        }

        function setReceiveble(id,member_id,attendance,from_date,to_date){
            isBulkMode = false;
            $('#payment_modal_title').text("Allocate Fee Receivable");
            $('#payment_modal_subtitle').text("Assign calculated dues based on member monthly attendance");
            $('#payment_btn_text').text("Create Receivable");

            $('#group_payable_date').show();
            $('#group_txt_attendance').show();
            $('#group_txt_fees').show();
            $('#group_bulk_group').hide();

            var fees = 300;
            if (from_date < '2026-04-01') {
                if (attendance <= 5) {
                    fees = 150;
                } else {
                    fees = 300;
                }
            }

            $('#payable_date').val(to_date);
            $("#hdn_id").val(id);

            $('#rec_from_date').val(from_date);
            $('#rec_to_date').val(to_date);
            $("#hdn_member_id").val(member_id);
            $("#txt_attendance").val(attendance);
            $('#txt_fees').val(fees);
            $('#paymentModel').modal('show');
        }

        function closeaddRecieveble(){
            $('#payment_form')[0].reset();
            $('#hdn_member_id').val(0);
            $('#hdn_id').val(0);
            $('#paymentModel').modal('hide');
        }

        function saveReceiveble() {
            var discription = $("#txt_discription").val();
            let length = discription.length; 
            var head = $('#selected_head').val();

            if (head === "0" || head === 0 || head === null) {
                alertinfo("Select a head");
                return;
            }

            if (length >= 150) {
                alertinfo("Description should be less than 150 characters.");
                return;
            }
            
            swal({
                title: "Are you sure?",
                text: isBulkMode ? "Do you want to set monthly attendance receivables for the selected batch?" : "Do you want to save this data!",
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
                    var data = {};
                    if (isBulkMode) {
                        data = {
                            action: 'save_all_receivables',
                            discription: $('#txt_discription').val(),
                            head: $('#selected_head').val(),
                            selected_year: $('#selected_year').val(),
                            group_id: $('#selected_bulk_group').val() || 0,
                        };
                    } else {
                        data = {
                            action: 'save_recieveble',
                            id: $('#hdn_id').val(),
                            date: $('#payable_date').val(),
                            attendance: $('#txt_attendance').val(),
                            fees: $('#txt_fees').val(),
                            discription: $('#txt_discription').val(),
                            head: $('#selected_head').val(),
                            member_id: $('#hdn_member_id').val(),
                            selected_year: $('#selected_year').val(),
                        };
                    }
                    $.ajax({
                        type: "POST",
                        url: "api/monthly_attendance.php",
                        data: data,
                        success: function(response) {
                            closeaddRecieveble();
                            alertsuccess('Saved Successfully');
                            loadData($('#hdn_current_page').val());
                        },
                        error: function (xhr, status){
                            closeaddRecieveble();
                            var msgObj = { Message: "Oops! Something went wrong." };
                            try {
                                msgObj = JSON.parse(xhr.responseText);
                            } catch(e) {}
                            alerterror(msgObj, xhr);
                            $('#payment_form')[0].reset();
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

                    var curBulkGrp = $('#selected_bulk_group').val() || 0;
                    $('#select_bulk_groups').html("<div class='dropdown form-group'><select id='selected_bulk_group' class='form-control'>" + opts + "</select></div>").find('#selected_bulk_group').val(curBulkGrp);
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

        function load_closing_years(){
            $.ajax({
                type: "POST",
                url: "api/monthly_attendance.php",
                data: {
                action: 'load_closing_years',
                },
                success: function(data) {
                    var obj = jQuery.parseJSON(data);
                    var htm="";
                    htm=htm+ "<div class='dropdown form-group'><select id='selected_year' class='form-control'>";
                    for (var i = 0; i < obj[0].length; i++) {
                        htm=htm+"<option value='"+obj[0][i].id+"'>"+obj[0][i].from_year+" - "+obj[0][i].to_year+"</option>";
                    }                
                    htm=htm+"</select></div>";

                    $('#select_from_year').html(htm);
                },
                error: function(xhr, status, error) {
                    console.error('AJAX error: ', status, error);
                }
            });
        }

        function closeaddMonthlyAttendance(){
            $('#attendance_form')[0].reset();
            $('#attendanceModel').modal('hide');
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
                                if (msgObj && msgObj.fixed_required) {
                                    showFixAttendanceRequiredAlert(selectedMonthVal, getMonthLabel(fromDateStr));
                                    return;
                                }
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
                <div style="display: flex; gap: 10px;">
                    <button type="button" class="btn" style="background: linear-gradient(135deg, #f59e0b 0%, #d97706 100%) !important; color: #ffffff !important; border-radius: 12px !important; padding: 10px 20px !important; font-weight: 700 !important; border: none !important; box-shadow: 0 4px 10px rgba(245, 158, 11, 0.2) !important; transition: all 0.2s ease;" onclick="setAllReceivebles()"><i class="fa fa-money"></i> Set All as Receivable</button>
                    <button type="button" class="btn-set-attendance" onclick="addMonthlyAttendance(0)"><i class="fa fa-cogs"></i> Set Monthly Attendance</button>
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
                            <h3 style="margin: 0; font-weight: 800; font-size: 20px; letter-spacing: -0.5px;"><i class="fa fa-calendar-check-o"></i> Set Monthly Attendance</h3>
                            <p style="margin: 4px 0 0 0; opacity: 0.9; font-size: 13.5px;">Mark start and end dates to compile monthly attendance figures</p>
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

        <!-- popup modal for add new recieveble fees into master starts -->
        <div class="modal inmodal" id="paymentModel" tabindex="-1" role="dialog" aria-hidden="true">
            <div class="modal-dialog">
                <div class="modal-content animated bounceInRight" style="border-radius: 20px; overflow: hidden; border: none; box-shadow: var(--shadow-lg);">
                    <form method="POST" id="payment_form">
                        <div class="modal-header" style="background: var(--primary-gradient, linear-gradient(135deg, #6366f1 0%, #4f46e5 100%)); padding: 24px 30px; color: #ffffff; text-align: left;">
                            <button type="button" class="close" data-dismiss="modal" style="color: #ffffff; opacity: 0.8; font-size: 24px;" onclick="closeaddRecieveble();">&times;</button>
                            <h3 style="margin: 0; font-weight: 800; font-size: 20px; letter-spacing: -0.5px;"><i class="fa fa-money"></i> <span id="payment_modal_title">Allocate Fee Receivable</span></h3>
                            <p style="margin: 4px 0 0 0; opacity: 0.9; font-size: 13.5px;" id="payment_modal_subtitle">Assign calculated dues based on member monthly attendance</p>
                        </div>
                        <div class="modal-body" style="padding: 30px; background: var(--card-bg, #ffffff);">
                            <div class="form-group" id="group_bulk_group" style="display: none;">
                                <label>Target Batch / Group</label>
                                <div id="select_bulk_groups">
                                    <!-- dropdown injected via ajax -->
                                </div>
                            </div>
                            <div class="form-group" id="group_payable_date">
                                <label>Receivable Date</label>
                                <input type="date" id="payable_date" name="payable_date" class="form-control">
                            </div>
                            <div class="form-group" id="group_txt_attendance">
                                <label>Days Attended</label>
                                <input type="number" id="txt_attendance" name="attendance" class="form-control" readonly>
                            </div>
                            <div class="form-group" id="group_txt_fees">
                                <label>Calculated Fees</label>
                                <input type="number" id="txt_fees" name="fees" placeholder="Fees amount" class="form-control">
                            </div>
                            <div class="form-group">
                                <label>Payment Head</label>
                                <div id="select_heads">
                                    <!-- dropdown injected via ajax -->
                                </div>
                            </div>
                            <div class="form-group">
                                <label>Financial Year</label>
                                <div id="select_from_year">
                                    <!-- dropdown injected via ajax -->
                                </div>
                            </div>
                            <div class="form-group">
                                <label>Description</label>
                                <textarea id="txt_discription" name="discription" rows="3" placeholder="Additional details (max 150 chars)" class="form-control"></textarea>
                            </div>
                        </div>
                        
                        <div class="modal-footer" style="background: var(--card-bg, #ffffff); border-top: 1px solid var(--border-color, #e2e8f0); padding: 18px 30px; display: flex; justify-content: flex-end; gap: 12px;">
                            <button type="button" class="btn btn-white" style="border-radius: 10px; font-weight: 700; padding: 8px 16px;" onclick="closeaddRecieveble();">Close</button>
                            <button type="button" class="btn btn-primary" style="border-radius: 10px; font-weight: 700; padding: 8px 20px;" onclick="saveReceiveble();"><span id="payment_btn_text">Create Receivable</span></button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
        <!-- popup modal for add new recieveble fees into master ends -->
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
