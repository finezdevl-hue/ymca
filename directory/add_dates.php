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
    <title>Manage Holidays</title>
    
    <link href="../css/bootstrap.min.css" rel="stylesheet">
    <link href="../font-awesome/css/font-awesome.css" rel="stylesheet">
    <link href="../css/animate.css" rel="stylesheet">
    <link href="../css/style.css" rel="stylesheet">
    <link href="../css/custom_modern.css" rel="stylesheet">
    <link href="../css/plugins/sweetalert/sweetalert.css" rel="stylesheet">
    <script src="https://cdnjs.cloudflare.com/ajax/libs/jquery/3.6.0/jquery.min.js"></script>
    <script src="../js/plugins/sweetalert/sweetalert.min.js"></script>
    <script src="../app_js/sweetalert-finez.js"></script>
    <script src="../app_pagination/pagination.js"></script>
    <script src="../app_menu/menu.js"></script>
    
    <style>
        :root {
            --primary: #4f46e5;
            --primary-hover: #4338ca;
            --danger: #ef4444;
            --danger-hover: #dc2626;
            --bg-gray: #f8fafc;
            --border-color: #e2e8f0;
        }

        body {
            font-family: 'Outfit', 'Inter', sans-serif !important;
            background-color: var(--bg-gray) !important;
        }

        #page-wrapper {
            background-color: var(--bg-gray) !important;
            min-height: 100vh;
        }

        /* Modern card container */
        .holiday-card {
            background: var(--card-bg, #ffffff);
            border: 1px solid var(--border-color, #e2e8f0);
            border-radius: 16px;
            padding: 24px;
            box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.05), 0 2px 4px -1px rgba(0, 0, 0, 0.03);
            margin-bottom: 24px;
        }

        /* Title and headers */
        .page-title {
            font-size: 24px;
            font-weight: 800;
            color: var(--text-primary, #1e293b);
            margin: 0;
            display: flex;
            align-items: center;
            gap: 10px;
        }

        /* Header bar styling */
        .header-bar {
            display: flex;
            justify-content: space-between;
            align-items: center;
            flex-wrap: wrap;
            gap: 16px;
            margin-bottom: 24px;
        }

        /* Form elements */
        .search-container {
            display: flex;
            align-items: center;
            gap: 10px;
            background: #ffffff;
            border: 1px solid var(--border-color);
            border-radius: 30px;
            padding: 4px 6px 4px 16px;
            box-shadow: 0 1px 2px 0 rgba(0, 0, 0, 0.05);
            max-width: 320px;
            width: 100%;
        }

        .search-container input[type="date"] {
            border: none;
            outline: none;
            font-size: 14px;
            font-weight: 500;
            color: #475569;
            width: 100%;
            background: transparent;
        }

        .search-btn {
            background: var(--primary);
            color: white;
            border: none;
            border-radius: 30px;
            padding: 8px 18px;
            font-weight: 600;
            font-size: 13.5px;
            cursor: pointer;
            transition: all 0.2s;
        }

        .search-btn:hover {
            background: var(--primary-hover);
        }

        /* Action buttons */
        .btn-action-primary {
            background: var(--primary) !important;
            color: #ffffff !important;
            font-weight: 600 !important;
            font-size: 14px !important;
            padding: 10px 20px !important;
            border-radius: 12px !important;
            border: none !important;
            display: inline-flex;
            align-items: center;
            gap: 8px;
            transition: all 0.2s !important;
            box-shadow: 0 2px 4px 0 rgba(79, 70, 229, 0.15) !important;
        }

        .btn-action-primary:hover {
            background: var(--primary-hover) !important;
            transform: translateY(-1px);
        }

        .btn-action-secondary {
            background: #ffffff !important;
            color: #4f46e5 !important;
            border: 1.5px solid #e2e8f0 !important;
            font-weight: 600 !important;
            font-size: 14px !important;
            padding: 10px 20px !important;
            border-radius: 12px !important;
            display: inline-flex;
            align-items: center;
            gap: 8px;
            transition: all 0.2s !important;
        }

        .btn-action-secondary:hover {
            background: #f8fafc !important;
            border-color: #cbd5e1 !important;
            transform: translateY(-1px);
        }

        /* Modern Table styling */
        .modern-table {
            width: 100%;
            margin-bottom: 0;
            border-collapse: separate;
            border-spacing: 0;
        }

        .modern-table th {
            background: #f8fafc !important;
            color: #475569 !important;
            font-weight: 700 !important;
            text-transform: uppercase;
            font-size: 12px !important;
            letter-spacing: 0.05em;
            padding: 14px 16px !important;
            border-bottom: 2px solid var(--border-color) !important;
        }

        .modern-table td {
            padding: 16px !important;
            vertical-align: middle !important;
            border-bottom: 1px solid var(--border-color) !important;
            color: var(--text-primary, #334155) !important;
            font-size: 14.5px !important;
            font-weight: 500;
        }

        .modern-table tr:hover td {
            background-color: #f8fafc !important;
        }

        /* Delete button style */
        .btn-delete {
            background: rgba(239, 68, 68, 0.08) !important;
            color: var(--danger) !important;
            border: 1px solid rgba(239, 68, 68, 0.15) !important;
            font-weight: 600 !important;
            padding: 6px 14px !important;
            border-radius: 8px !important;
            font-size: 13px !important;
            display: inline-flex;
            align-items: center;
            gap: 6px;
            transition: all 0.2s !important;
        }

        .btn-delete:hover {
            background: var(--danger) !important;
            color: #ffffff !important;
            border-color: var(--danger) !important;
        }

        /* Modals style */
        .modal-content {
            border-radius: 16px !important;
            border: none !important;
            box-shadow: 0 20px 25px -5px rgba(0, 0, 0, 0.1), 0 10px 10px -5px rgba(0, 0, 0, 0.04) !important;
        }

        .modal-header {
            border-bottom: 1px solid #f1f5f9 !important;
            padding: 20px 24px !important;
        }

        .modal-title {
            font-weight: 700 !important;
            color: #1e293b !important;
            font-size: 18px !important;
        }

        .modal-body {
            padding: 24px !important;
        }

        .modal-footer {
            border-top: 1px solid #f1f5f9 !important;
            padding: 16px 24px !important;
        }

        .form-label {
            font-weight: 600;
            color: #334155;
            margin-bottom: 8px;
            font-size: 14px;
        }

        .form-control-modern {
            height: 48px;
            border-radius: 8px;
            border: 1px solid #cbd5e1;
            padding: 10px 14px;
            font-size: 15px;
            box-shadow: none;
            transition: border-color 0.2s;
            width: 100%;
        }

        .form-control-modern:focus {
            border-color: var(--primary);
            outline: none;
        }
    </style>
    
    <script>
        $(document).ready(function() {
            loadGroupOptions();
            loadData(1);
        });

        function loadGroupOptions() {
            $.ajax({
                type: "POST",
                url: "api/attendance.php",
                data: { action: 'load_groups' },
                success: function(data) {
                    var obj = jQuery.parseJSON(data);
                    var htm = '<option value="0">All Groups (Global Holiday)</option>';
                    if (obj && obj[0]) {
                        for (var i = 0; i < obj[0].length; i++) {
                            htm += '<option value="' + obj[0][i].id + '">' + obj[0][i].name + '</option>';
                        }
                    }
                    $('#select_group_single').html(htm);
                    $('#select_group_range').html(htm);
                }
            });
        }
        
        function searchDate(){
            loadData(1);
        }
    
        function loadData(page) {
            $('#hdn_current_page').val(page);
            $.ajax({               
                type: "POST",
                url: "api/add_dates.php",
                data: {
                    action: 'load_data',
                    page: page, 
                    val: $('#date_search').val()
                },
                success: function(data) {  
                    var obj = jQuery.parseJSON(data);
                    var totalrows = obj[0].total_rows;
                    var htm = "";
                    
                    htm += "<div class='holiday-card'>";
                    htm += "<div class='table-responsive'>";
                    htm += "<table class='table modern-table'>";
                    htm += "<thead>";
                    htm += "<tr>";
                    htm += "<th style='width: 70px;'>No</th>";
                    htm += "<th>Date</th>";
                    htm += "<th>Group / Session</th>";
                    htm += "<th style='width: 120px; text-align: right;'>Action</th>";
                    htm += "</tr>";
                    htm += "</thead>";
                    htm += "<tbody>";
                    
                    if (obj[1].length === 0) {
                        htm += "<tr><td colspan='4' class='text-center' style='padding: 30px !important; color: #64748b;'>No holiday dates configured.</td></tr>";
                    } else {
                        for (var i = 0; i < obj[1].length; i++) {
                            var j = i + 1;
                            var slno = ((page - 1) * 8) + j;
                            
                            var parts = obj[1][i].date.split('-');
                            var rawDate = new Date(parseInt(parts[0]), parseInt(parts[1]) - 1, parseInt(parts[2]));
                            var formattedDate = rawDate.toLocaleDateString('en-US', { weekday: 'long', year: 'numeric', month: 'long', day: 'numeric' });
                            
                            var gName = obj[1][i].group_name || 'All Groups';
                            var isGlobal = (obj[1][i].group_id == 0);
                            var badgeStyle = isGlobal ? 'background: rgba(79, 70, 229, 0.1); color: #4f46e5; border: 1px solid rgba(79, 70, 229, 0.2);' : 'background: rgba(16, 185, 129, 0.1); color: #10b981; border: 1px solid rgba(16, 185, 129, 0.2);';
                            var icon = isGlobal ? '<i class="fa fa-globe"></i> ' : '<i class="fa fa-users"></i> ';

                            htm += "<tr>";
                            htm += "<td>" + slno + "</td>";
                            htm += "<td style='font-weight: 600;'>" + formattedDate + "</td>";
                            htm += "<td><span style='font-size: 12px; font-weight: 700; padding: 5px 14px; border-radius: 20px; display: inline-flex; align-items: center; gap: 6px; " + badgeStyle + "'>" + icon + gName + "</span></td>";
                            htm += "<td style='text-align: right;'><a onclick='deleteDate(" + obj[1][i].id + ");' class='btn-delete'><i class='fa fa-trash'></i> Delete</a></td>";
                            htm += "</tr>";
                        }
                    }
                    
                    htm += "</tbody>";
                    htm += "</table>";
                    htm += "</div>";
                    htm += "</div>";
                    
                    $('#table_dates').html(htm);
                    var htmpage = paginate(totalrows, page);
                    $('#table_dates').append(htmpage);
                },
                error: function(xhr, status, error) {
                    console.log('AJAX error: ', status, error);
                }
            });
            loadMenu();
        }

        function popupDates(id) {
            $("#hdn_group_id").val(id);
            $('#groupModel').modal('show');
        }

        function popupRangeDates(id) {
            $("#hdn_group_id").val(id);
            $('#rangeModel').modal('show');
        }

        function saveDate() {
            var selectedDate = $('#txt_date').val();
            if (!selectedDate) {
                alertwarning('Please select a date.');
                return;
            }
            
            swal({
                title: "Are you sure?",
                text: "Do you want to save this holiday date?",
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
                    load_overlay();
                    var data = {
                        action: 'save_date',
                        id: $("#hdn_group_id").val(),
                        date: selectedDate,
                        group_id: $('#select_group_single').val() || 0
                    };
                    $.ajax({
                        type: "POST",
                        url: "api/add_dates.php",
                        data: data,
                        success: function(response) {
                            close_overlay();
                            closepopupDates();
                            alertsuccess('Saved Successfully');
                            loadData($('#hdn_current_page').val());
                        },
                        error: function (xhr, status){
                            close_overlay();
                            var msg = xhr.responseText || 'Error saving holiday date';
                            try {
                                var msgObj = JSON.parse(xhr.responseText);
                                if (msgObj && typeof msgObj === 'object') {
                                    msg = msgObj.Message || msgObj.message || msg;
                                }
                            } catch (e) {}
                            alerterror(msg);
                        }
                    });
                }
            });   
        }

        function saveRangeDate() {
            var startDate = $('#txt_start_date').val();
            var endDate = $('#txt_end_date').val();
            
            if (!startDate || !endDate) {
                alertwarning('Please select both start and end dates.');
                return;
            }

            swal({
                title: "Are you sure?",
                text: "Do you want to save this range of holiday dates?",
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
                    load_overlay();
                    var data = {
                        action: 'save_range_dates',
                        start_date: startDate,
                        end_date: endDate,
                        group_id: $('#select_group_range').val() || 0
                    };
                    $.ajax({
                        type: "POST",
                        url: "api/add_dates.php",
                        data: data,
                        success: function(response) {
                            close_overlay();
                            closepopupRangeDates();
                            alertsuccess('Saved Successfully');
                            loadData($('#hdn_current_page').val());
                        },
                        error: function (xhr, status){
                            close_overlay();
                            var msg = xhr.responseText || 'Error saving range dates';
                            alerterror(msg);
                        }
                    });
                }
            });   
        }

        function closepopupDates(){
            $('#group_form')[0].reset();
            $("#hdn_group_id").val(0);
            $('#groupModel').modal('hide');
        }

        function closepopupRangeDates(){
            $('#range_form')[0].reset();
            $("#hdn_group_id").val(0);
            $('#rangeModel').modal('hide');
        }

        function deleteDate(id) {
            $("#hdn_group_id").val(id);
            deleteRow();
        }

        function deleteRow() {
            swal({
                title: "Are you sure?",
                text: "Do you want to delete this holiday date?",
                type: "warning",
                showCancelButton: true,
                confirmButtonColor: "#DD6B55",
                confirmButtonText: "Yes, Delete!",
                cancelButtonText: "Cancel",
                closeOnConfirm: false,
                closeOnCancel: true
            },
            function (isConfirm) {
                if (isConfirm){
                    load_overlay();
                    var data = {
                        action: 'delete_date',
                        id: $("#hdn_group_id").val(),               
                    };
                    $.ajax({
                        type: "POST",
                        url: "api/add_dates.php",
                        data: data,
                        success: function(response) {
                            close_overlay();
                            $("#hdn_group_id").val(0);
                            alertwarning('Deleted');
                            loadData($('#hdn_current_page').val());
                        },
                        error: function(xhr, status, error) {
                            close_overlay();
                            console.log('AJAX error:', status, error);
                        }
                    });
                }
            });   
        }
    </script>
    
</head>

<body>
    <input type="hidden" id="hdn_current_page" value="1">
    <input type="hidden" id="hdn_group_id" value="0">

    <div id="wrapper">
        <!-- navigation starts -->
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

        <div id="page-wrapper">
            <div class="row border-bottom" style="background:#fff; margin:0;">
                <nav class="navbar navbar-static-top" role="navigation" style="margin-bottom: 0; background:#fff;">
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
            
            <div style="padding: 24px;">
                <div class="holiday-card">
                    <div class="header-bar">
                        <h2 class="page-title">
                            <i class="fa fa-calendar-check-o" style="color: var(--primary);"></i>
                            Manage Holidays
                        </h2>
                        
                        <div style="display: flex; gap: 12px; align-items: center; flex-wrap: wrap;">
                            <div class="search-container">
                                <input type="date" id="date_search" onchange="searchDate()">
                                <button class="search-btn" onclick="searchDate()"><i class="fa fa-search"></i></button>
                            </div>
                            
                            <button type="button" class="btn-action-primary" onclick="popupDates('0')">
                                <i class="fa fa-plus"></i> Single Date
                            </button>
                            <button type="button" class="btn-action-secondary" onclick="popupRangeDates('0')">
                                <i class="fa fa-calendar-plus-o"></i> Range Dates
                            </button>
                        </div>
                    </div>
                </div>

                <div class="animated fadeInRight" id="table_dates">
                    <!-- Data will be injected dynamically via AJAX -->
                </div>
            </div>
            
            <!-- Modal for Add Single Date -->
            <div class="modal inmodal" id="groupModel" tabindex="-1" role="dialog" aria-hidden="true">
                <div class="modal-dialog modal-md">
                    <div class="modal-content" style="border-radius: 16px; background-color: #ffffff !important;">
                        <div class="modal-header">
                            <button type="button" class="close" onclick="closepopupDates()"><span aria-hidden="true">&times;</span></button>
                            <h4 class="modal-title">Configure Holiday Date</h4>
                        </div>
                        <form method="POST" id="group_form">
                            <div class="modal-body" style="background-color: #ffffff !important;">
                                <div class="form-group" style="margin-bottom: 20px;">
                                    <label class="form-label">Applicable Group / Session</label>
                                    <select id="select_group_single" class="form-control-modern"></select>
                                </div>
                                <div class="form-group">
                                    <label class="form-label">Select Date</label>
                                    <input type="date" id="txt_date" name="date" class="form-control-modern" required>
                                </div>
                            </div>
                            <div class="modal-footer" style="background-color: #f8fafc !important;">
                                <button type="button" class="btn btn-default" style="padding: 10px 20px; border-radius: 8px;" onclick="closepopupDates();">Close</button>
                                <button type="button" class="btn btn-primary" style="padding: 10px 20px; border-radius: 8px;" onclick="saveDate();">Save Date</button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
            
            <!-- Modal for Add Range of Dates -->
            <div class="modal inmodal" id="rangeModel" tabindex="-1" role="dialog" aria-hidden="true">
                <div class="modal-dialog modal-md">
                    <div class="modal-content" style="border-radius: 16px; background-color: #ffffff !important;">
                        <div class="modal-header">
                            <button type="button" class="close" onclick="closepopupRangeDates()"><span aria-hidden="true">&times;</span></button>
                            <h4 class="modal-title">Configure Holiday Date Range</h4>
                        </div>
                        <form method="POST" id="range_form">
                            <div class="modal-body" style="background-color: #ffffff !important;">
                                <div class="form-group" style="margin-bottom: 20px;">
                                    <label class="form-label">Applicable Group / Session</label>
                                    <select id="select_group_range" class="form-control-modern"></select>
                                </div>
                                <div class="form-group" style="margin-bottom: 20px;">
                                    <label class="form-label">Start Date</label>
                                    <input type="date" id="txt_start_date" name="start_date" class="form-control-modern" required>
                                </div>
                                <div class="form-group">
                                    <label class="form-label">End Date</label>
                                    <input type="date" id="txt_end_date" name="end_date" class="form-control-modern" required>
                                </div>
                            </div>
                            <div class="modal-footer" style="background-color: #f8fafc !important;">
                                <button type="button" class="btn btn-default" style="padding: 10px 20px; border-radius: 8px;" onclick="closepopupRangeDates();">Close</button>
                                <button type="button" class="btn btn-primary" style="padding: 10px 20px; border-radius: 8px;" onclick="saveRangeDate();">Save Range</button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>

        </div>
    </div>

    <!-- Scripts -->
    <script src="../js/bootstrap.min.js"></script>
    <script src="../js/plugins/metisMenu/jquery.metisMenu.js"></script>
    <script src="../js/plugins/slimscroll/jquery.slimscroll.min.js"></script>
    <script src="../js/inspinia.js"></script>
    <script src="../js/plugins/pace/pace.min.js"></script>
    <script src="../app_js/validation.js"></script>
    <script src="../js/loadingoverlay.min.js"></script>

</body>

</html>
