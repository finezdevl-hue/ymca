<?php
session_start();
if(empty($_SESSION['login_id'])){
    header("Location: ../index.php");
    exit();
}
if($_SESSION['login_id'] != 1){
    header("Location: user_attendance.php");
    exit();
}
if(isset($_POST['id'])){
    $_SESSION['id']=$_POST['id'];
}
?>
<!DOCTYPE html>
<html>

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Groups - YMCA</title>

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
        :root {
            --bg-main: #f8fafc;
            --bg-card: #ffffff;
            --border-color: #e2e8f0;
            --text-primary: #0f172a;
            --text-muted: #64748b;
        }

        .grp-container {
            padding: 24px 30px;
            font-family: 'Inter', sans-serif;
        }

        .grp-header-bar {
            display: flex;
            align-items: center;
            justify-content: space-between;
            flex-wrap: wrap;
            gap: 16px;
            background: #ffffff !important;
            border: 1px solid #e2e8f0 !important;
            border-radius: 16px;
            padding: 20px 24px;
            margin-bottom: 24px;
            box-shadow: 0 4px 15px rgba(0, 0, 0, 0.04);
        }

        .grp-title-area h1 {
            font-size: 22px;
            font-weight: 800;
            color: #0f172a;
            margin: 0 0 4px 0;
            display: flex;
            align-items: center;
            gap: 10px;
            font-family: 'Inter', sans-serif;
        }

        .grp-title-area p {
            font-size: 13px;
            color: #64748b;
            margin: 0;
        }

        .grp-controls {
            display: flex;
            align-items: center;
            gap: 12px;
            flex-wrap: wrap;
        }

        .grp-filter-select {
            background: #ffffff !important;
            border: 1px solid #cbd5e1 !important;
            border-radius: 10px;
            padding: 9px 16px;
            font-size: 13px;
            font-weight: 600;
            color: #0f172a !important;
            outline: none;
            cursor: pointer;
            font-family: 'Inter', sans-serif;
            transition: all 0.2s;
        }

        .grp-filter-select:focus {
            border-color: #3b82f6 !important;
            box-shadow: 0 0 0 3px rgba(59, 130, 246, 0.15);
        }

        .grp-add-btn {
            background: #3b82f6 !important;
            color: #ffffff !important;
            border: none;
            border-radius: 10px;
            padding: 10px 22px;
            font-size: 14px;
            font-weight: 700;
            font-family: 'Inter', sans-serif;
            cursor: pointer;
            display: inline-flex;
            align-items: center;
            gap: 8px;
            box-shadow: 0 4px 12px rgba(59, 130, 246, 0.25);
            transition: all 0.2s ease;
        }

        .grp-add-btn:hover {
            background: #2563eb !important;
            color: #ffffff !important;
        }

        /* Group Card Grid */
        .grp-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(280px, 1fr));
            gap: 20px;
        }

        .grp-card {
            background: #ffffff !important;
            border: 1px solid #e2e8f0 !important;
            border-radius: 16px;
            padding: 24px;
            box-shadow: 0 4px 15px rgba(0, 0, 0, 0.04);
            transition: all 0.2s ease;
            display: flex;
            flex-direction: column;
            justify-content: space-between;
            position: relative;
            overflow: hidden;
        }

        .grp-card:hover {
            border-color: #3b82f6 !important;
            box-shadow: 0 8px 25px rgba(0, 0, 0, 0.08);
        }

        .grp-card-top {
            display: flex;
            align-items: flex-start;
            justify-content: space-between;
            margin-bottom: 16px;
        }

        .grp-icon-avatar {
            width: 52px;
            height: 52px;
            border-radius: 14px;
            background: #eff6ff !important;
            color: #3b82f6;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 22px;
            border: 1px solid #dbeafe;
        }

        .grp-icon-avatar.evening {
            background: #faf5ff !important;
            color: #9333ea;
            border-color: #f3e8ff;
        }

        .grp-status-chip {
            font-size: 12px;
            font-weight: 700;
            padding: 5px 14px;
            border-radius: 20px;
            cursor: pointer;
            transition: all 0.2s;
            display: inline-flex;
            align-items: center;
            gap: 6px;
            border: 1px solid transparent;
            font-family: 'Inter', sans-serif;
        }

        .grp-status-chip.active {
            background: #ecfdf5 !important;
            color: #059669 !important;
            border-color: #a7f3d0 !important;
        }

        .grp-status-chip.active:hover {
            background: #d1fae5 !important;
        }

        .grp-status-chip.inactive {
            background: #fffbeb !important;
            color: #d97706 !important;
            border-color: #fde68a !important;
        }

        .grp-status-chip.inactive:hover {
            background: #fef3c7 !important;
        }

        .grp-name {
            font-size: 18px;
            font-weight: 800;
            color: #0f172a;
            margin: 0 0 8px 0;
            font-family: 'Inter', sans-serif;
        }

        .grp-members-badge {
            font-size: 12px;
            font-weight: 600;
            color: #475569;
            background: #f1f5f9 !important;
            padding: 6px 14px;
            border-radius: 10px;
            display: inline-flex;
            align-items: center;
            gap: 6px;
            margin-bottom: 20px;
            width: fit-content;
            border: 1px solid #e2e8f0;
        }

        .grp-actions {
            display: flex;
            align-items: center;
            gap: 8px;
            border-top: 1px solid #f1f5f9;
            padding-top: 16px;
        }

        .grp-act-btn {
            flex: 1;
            background: #ffffff !important;
            border: 1px solid #cbd5e1 !important;
            border-radius: 8px;
            padding: 8px 10px;
            font-size: 12px;
            font-weight: 600;
            color: #334155 !important;
            cursor: pointer;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 6px;
            transition: all 0.2s;
            text-decoration: none !important;
            font-family: 'Inter', sans-serif;
        }

        .grp-act-btn:hover {
            background: #3b82f6 !important;
            color: #ffffff !important;
            border-color: #3b82f6 !important;
        }

        .grp-act-btn.delete-btn:hover {
            background: #ef4444 !important;
            color: #ffffff !important;
            border-color: #ef4444 !important;
        }

        /* Modal Solid Overrides - NO Glass, NO Transparency */
        .modal-content {
            border-radius: 16px !important;
            border: 1px solid #cbd5e1 !important;
            background-color: #ffffff !important;
            box-shadow: 0 25px 50px -12px rgba(0, 0, 0, 0.25) !important;
            opacity: 1 !important;
        }

        .modal-backdrop {
            background-color: #0f172a !important;
            opacity: 0.6 !important;
        }

        .modal-header-custom {
            padding: 20px 24px;
            border-bottom: 1px solid #e2e8f0;
            display: flex;
            align-items: center;
            justify-content: space-between;
            background-color: #ffffff !important;
            border-top-left-radius: 16px;
            border-top-right-radius: 16px;
        }

        .modal-header-custom h3 {
            margin: 0;
            font-size: 18px;
            font-weight: 800;
            color: #0f172a;
            font-family: 'Inter', sans-serif;
        }

        .modal-body-custom {
            padding: 24px;
            background-color: #ffffff !important;
        }

        .modal-footer-custom {
            padding: 16px 24px;
            border-top: 1px solid #e2e8f0;
            display: flex;
            align-items: center;
            justify-content: flex-end;
            gap: 10px;
            background-color: #f8fafc !important;
            border-bottom-left-radius: 16px;
            border-bottom-right-radius: 16px;
        }

        .status-option-card {
            display: flex;
            align-items: center;
            gap: 12px;
            padding: 14px 18px;
            border-radius: 12px;
            border: 1.5px solid #cbd5e1;
            background-color: #ffffff !important;
            margin-bottom: 12px;
            cursor: pointer;
            transition: all 0.2s;
        }

        .status-option-card:hover {
            border-color: #3b82f6 !important;
            background-color: #eff6ff !important;
        }

        /* Toggle switch */
        .switch-toggle input:checked + .slider-round {
            background-color: #3b82f6 !important;
        }
        .switch-toggle input:checked + .slider-round:before {
            transform: translateX(16px);
        }
        .slider-round:before {
            position: absolute;
            content: "";
            height: 16px;
            width: 16px;
            left: 3px;
            bottom: 3px;
            background-color: white;
            transition: .3s;
            border-radius: 50%;
        }
    </style>

    <script src="https://cdnjs.cloudflare.com/ajax/libs/jquery/3.6.0/jquery.min.js"></script>
    <script src="../js/plugins/sweetalert/sweetalert.min.js"></script>
    <script src="../app_js/sweetalert-finez.js"></script>

    <script>
        $(document).ready(function() {
            load_group_status_for_select();
        });

        function toggleTomorrowAtt(id, isChecked) {
            var val = isChecked ? 1 : 0;
            $.ajax({
                type: "POST",
                url: "api/groups.php",
                data: {
                    action: 'update_group_tomorrow_attendance',
                    id: id,
                    allow_tomorrow_attendance: val
                },
                success: function(response) {
                    var res = (typeof response === 'string') ? JSON.parse(response) : response;
                    if (res.status === 'success') {
                        alertsuccess(res.message);
                    } else {
                        alerterror(res.message || 'Failed to update setting.');
                    }
                },
                error: function() {
                    alerterror('Failed to update setting.');
                }
            });
        }

        function load_group_status_for_select(){
            $.ajax({
                type: "POST",
                url: "api/groups.php",
                data: { action: 'load_group_status' },
                success: function(data) {
                    $('#status-container').html(data);
                    // Style select if inserted as raw html
                    $('#select_status').addClass('grp-filter-select');
                    loadData(1);
                },
                error: function(xhr, status, error) {
                    console.error('AJAX error: ', status, error);
                }
            });
        }

        function loadData(page) {
            $('#hdn_current_page').val(page);
            $.ajax({               
                type: "POST",
                url: "api/groups.php",
                data: {
                    action: 'load_group_data',
                    page: page, 
                    group_status: $("#select_status").val() || 0
                },
                success: function(data) {                    
                    var obj = jQuery.parseJSON(data);
                    var totalrows = obj[0].total_rows;
                    var groups = obj[1];
                    var htm = "";

                    if (groups.length > 0) {
                        htm += "<div class='grp-grid'>";
                        for (var i = 0; i < groups.length; i++) {
                            var g = groups[i];
                            var statusClass = (g.status == '1') ? 'active' : 'inactive';
                            var statusText = (g.status == '1') ? 'Active' : 'Inactive';
                            var statusDot = (g.status == '1') ? '●' : '○';
                            
                            var isEvening = g.name.toLowerCase().indexOf('evening') !== -1;
                            var isMorning = g.name.toLowerCase().indexOf('morning') !== -1;
                            var avatarIcon = isEvening ? 'fa-moon-o' : (isMorning ? 'fa-sun-o' : 'fa-users');
                            var avatarClass = isEvening ? 'evening' : '';

                            var memberCountText = (g.member_count !== undefined) ? g.member_count : 0;
                            var allowTomorrowChecked = (g.allow_tomorrow_attendance == 1) ? 'checked' : '';

                            htm += "<div class='grp-card'>";
                            htm += "  <div>";
                            htm += "    <div class='grp-card-top'>";
                            htm += "      <div class='grp-icon-avatar " + avatarClass + "'><i class='fa " + avatarIcon + "'></i></div>";
                            htm += "      <span class='grp-status-chip " + statusClass + "' onclick='showStatusModal(" + g.id + ")' title='Click to change status'>" + statusDot + " " + statusText + "</span>";
                            htm += "    </div>";
                            htm += "    <h3 class='grp-name'>" + g.name + "</h3>";
                            htm += "    <div class='grp-members-badge'><i class='fa fa-user-circle' style='color:#3b82f6;'></i> " + memberCountText + " Members</div>";
                            htm += "    <div style='margin: 10px 0 14px 0; padding: 10px 14px; background: #f8fafc; border: 1px solid #e2e8f0; border-radius: 12px; display: flex; align-items: center; justify-content: space-between;'>";
                            htm += "      <div>";
                            htm += "        <div style='font-size: 12px; font-weight: 700; color: #1e293b;'><i class='fa fa-calendar-plus-o' style='color: #3b82f6;'></i> Tomorrow's Attendance</div>";
                            htm += "        <div style='font-size: 10.5px; color: #64748b;'>Allow members to mark tomorrow</div>";
                            htm += "      </div>";
                            htm += "      <label class='switch-toggle' style='position: relative; display: inline-block; width: 38px; height: 22px; margin: 0;'>";
                            htm += "        <input type='checkbox' onchange='toggleTomorrowAtt(" + g.id + ", this.checked)' " + allowTomorrowChecked + " style='opacity: 0; width: 0; height: 0;'>";
                            htm += "        <span class='slider-round' style='position: absolute; cursor: pointer; top: 0; left: 0; right: 0; bottom: 0; background-color: #cbd5e1; transition: .3s; border-radius: 22px;'></span>";
                            htm += "      </label>";
                            htm += "    </div>";
                            htm += "  </div>";
                            htm += "  <div class='grp-actions'>";
                            htm += "    <button type='button' onclick='fetchGroupDetails(" + g.id + ",\"" + g.name + "\")' class='grp-act-btn' title='Edit Group Name'><i class='fa fa-pencil'></i> Edit</button>";
                            htm += "    <button type='button' onclick='groupMembers(" + g.id + ")' class='grp-act-btn' title='View Members'><i class='fa fa-users'></i> Members</button>";
                            htm += "    <button type='button' onclick='deleteGroup(" + g.id + ")' class='grp-act-btn delete-btn' title='Delete Group'><i class='fa fa-trash'></i> Delete</button>";
                            htm += "  </div>";
                            htm += "</div>";
                        }
                        htm += "</div>";
                    } else {
                        htm += "<div style='background: var(--bg-card); border: 1px dashed var(--border-color); border-radius: 20px; padding: 48px 20px; text-align: center; color: var(--text-muted); font-family: \"Inter\", sans-serif;'>";
                        htm += "  <i class='fa fa-folder-open-o' style='font-size: 40px; color: #94a3b8; margin-bottom: 12px; display: block;'></i>";
                        htm += "  <h3 style='font-size: 16px; font-weight: 700; color: var(--text-primary); margin: 0 0 6px 0;'>No Groups Found</h3>";
                        htm += "  <p style='font-size: 13px; margin: 0;'>Click '+ Add Group' to create a new group or session.</p>";
                        htm += "</div>";
                    }
                   
                    $('#table-container').html(htm);
                    var htmpage = paginate(totalrows, page);
                    $('#table-container').append('<div style="margin-top: 24px;">' + htmpage + '</div>');
                },
                error: function(xhr, status, error) {
                    console.log('AJAX error: ', status, error);
                }
            });
            loadMenu();
        }

        let mail_data_id = null;

        function showStatusModal(id) {
            mail_data_id = id;
            $('#statusModal').modal('show');
        }

        function saveStatus() {
            const selectedStatus = document.querySelector('input[name="status"]:checked');
            if (!selectedStatus) {
                alertwarning('Please select a status first.');
                return;
            }       
            $.ajax({               
                type: "POST",
                url: "api/groups.php",
                data: {
                    action: 'update_group_status',
                    id: mail_data_id, 
                    status: selectedStatus.value
                },
                success: function(data) {                    
                    $('#statusModal').modal('hide');
                    alertsuccess('Group status updated successfully');
                    loadData($('#hdn_current_page').val());                
                },
                error: function(xhr, status, error) {
                    console.log('AJAX error: ', status, error);
                }
            });   
        }

        function groupMembers(id){ 
            $.post("group_members.php", { 'id': id })
            .done(function(response) {
                window.location.href = "group_members.php";
            });
        }

        function fetchGroupDetails(id, group_name){
            $("#txt_group_name").val(group_name);
            $("#hdn_group_id").val(id);
            $('#modal-title-text').text(id > 0 ? 'Edit Group' : 'Add New Group');
            $('#groupModel').modal('show');
        }

        function popupGroupDetails(id) {
            $("#txt_group_name").val('');
            $("#hdn_group_id").val(id);
            $('#modal-title-text').text('Add New Group');
            $('#groupModel').modal('show');
        }

        function saveGroup() {
            var gName = $('#txt_group_name').val().trim();
            if (!gName) {
                alertwarning('Please enter a group name.');
                return;
            }
            swal({
                title: "Confirm Save",
                text: "Do you want to save this group?",
                type: "warning",
                showCancelButton: true,
                confirmButtonColor: "#3b82f6",
                confirmButtonText: "Yes, Save!",
                cancelButtonText: "Cancel",
                closeOnConfirm: true
            }, function (isConfirm) {
                if (isConfirm){
                    load_overlay();
                    $.ajax({
                        type: "POST",
                        url: "api/groups.php",
                        data: {
                            action: 'save_group',
                            id: $("#hdn_group_id").val(),
                            group_name: gName
                        },
                        success: function(response) {
                            close_overlay();
                            closePopupGroupDetails();
                            alertsuccess('Saved Successfully');
                            loadData($('#hdn_current_page').val());
                        },
                        error: function (xhr, status){
                            close_overlay();
                            var msgObj = JSON.parse(xhr.responseText);
                            alerterror(msgObj, xhr);
                        }
                    });
                }
            });   
        }

        function closePopupGroupDetails(){
            $('#group_form')[0].reset();
            $("#hdn_group_id").val(0);
            $('#groupModel').modal('hide');
        }

        function deleteGroup(id) {
            $("#hdn_group_id").val(id);
            swal({
                title: "Are you sure?",
                text: "Do you really want to delete this group?",
                type: "warning",
                showCancelButton: true,
                confirmButtonColor: "#ef4444",
                confirmButtonText: "Yes, Delete!",
                cancelButtonText: "Cancel",
                closeOnConfirm: true
            }, function (isConfirm) {
                if (isConfirm){
                    load_overlay();
                    $.ajax({
                        type: "POST",
                        url: "api/groups.php",
                        data: {
                            action: 'delete_group',
                            id: $("#hdn_group_id").val()
                        },
                        success: function(response) {
                            close_overlay();
                            $("#hdn_group_id").val(0);
                            alertsuccess('Group deleted successfully');
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
    <script src="../app_pagination/pagination.js"></script>
    <script src="../app_menu/menu.js"></script>
</head>

<body>
    <input type="hidden" id="hdn_current_page" value="1">
    <input type="hidden" id="hdn_group_id" value="0">

    <div id="wrapper">
        <!-- Sidebar Navigation -->
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

        <div id="page-wrapper" style="background: var(--bg-main); min-height: 100vh;">
            <!-- Top navbar -->
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
            
            <div class="grp-container">
                <!-- Header Control Bar -->
                <div class="grp-header-bar">
                    <div class="grp-title-area">
                        <h1>
                            <i class="fa fa-users" style="color: #3b82f6;"></i>
                            Manage Groups & Sessions
                        </h1>
                        <p>Create and organize sessions (Morning, Evening, etc.) and view enrolled members.</p>
                    </div>
                    <div class="grp-controls">
                        <div id="status-container">
                            <!-- Filter dropdown injected via AJAX -->
                        </div>
                        <button type="button" class="grp-add-btn" onclick="popupGroupDetails('0')">
                            <i class="fa fa-plus-circle"></i> Add Group
                        </button>
                    </div>
                </div>

                <!-- Main Content Cards Container -->
                <div id="table-container">
                    <!-- Cards injected dynamically via AJAX -->
                </div>
            </div>

            <!-- Modal for Add/Edit Group -->
            <div class="modal fade" id="groupModel" tabindex="-1" role="dialog" aria-hidden="true">
                <div class="modal-dialog modal-dialog-centered" style="max-width: 440px;">
                    <div class="modal-content">
                        <div class="modal-header-custom">
                            <h3 id="modal-title-text">Add New Group</h3>
                            <button type="button" class="close" data-dismiss="modal" aria-label="Close" style="font-size: 24px; color: var(--text-muted); opacity: 0.8;">
                                <span aria-hidden="true">&times;</span>
                            </button>
                        </div>
                        <form method="POST" id="group_form" onsubmit="event.preventDefault(); saveGroup();">
                            <div class="modal-body-custom">
                                <div class="form-group" style="margin: 0;">
                                    <label style="font-size: 13px; font-weight: 700; color: var(--text-primary); margin-bottom: 8px; display: block; font-family: 'Inter', sans-serif;">Group Name</label>
                                    <input type="text" id="txt_group_name" name="group_name" placeholder="e.g. Morning Session, Evening Session..." class="form-control" style="border-radius: 12px; padding: 12px 16px; border: 1.5px solid var(--border-color); font-size: 14px; font-family: 'Inter', sans-serif;" required>
                                </div>
                            </div>
                            <div class="modal-footer-custom">
                                <button type="button" class="btn btn-default" data-dismiss="modal" style="border-radius: 10px; font-weight: 600; font-family: 'Inter', sans-serif;">Cancel</button>
                                <button type="button" class="grp-add-btn" onclick="saveGroup()" style="border-radius: 10px;">Save Group</button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>

            <!-- Modal for Change Status -->
            <div class="modal fade" id="statusModal" tabindex="-1" role="dialog" aria-hidden="true">
                <div class="modal-dialog modal-dialog-centered" style="max-width: 380px;">
                    <div class="modal-content">
                        <div class="modal-header-custom">
                            <h3>Update Status</h3>
                            <button type="button" class="close" data-dismiss="modal" aria-label="Close" style="font-size: 24px; color: var(--text-muted); opacity: 0.8;">
                                <span aria-hidden="true">&times;</span>
                            </button>
                        </div>
                        <div class="modal-body-custom">
                            <p style="font-size: 13px; color: var(--text-muted); margin-bottom: 16px;">Select the current operational status for this group:</p>
                            <label class="status-option-card">
                                <input type="radio" name="status" value="1" style="width: 18px; height: 18px; accent-color: #10b981;">
                                <div>
                                    <strong style="display: block; font-size: 14px; color: var(--text-primary);">Active</strong>
                                    <span style="font-size: 12px; color: var(--text-muted);">Group is active and available for attendance</span>
                                </div>
                            </label>
                            <label class="status-option-card">
                                <input type="radio" name="status" value="2" style="width: 18px; height: 18px; accent-color: #f59e0b;">
                                <div>
                                    <strong style="display: block; font-size: 14px; color: var(--text-primary);">Inactive</strong>
                                    <span style="font-size: 12px; color: var(--text-muted);">Group is paused / temporarily hidden</span>
                                </div>
                            </label>
                        </div>
                        <div class="modal-footer-custom">
                            <button type="button" class="btn btn-default" data-dismiss="modal" style="border-radius: 10px; font-weight: 600;">Cancel</button>
                            <button type="button" class="grp-add-btn" onclick="saveStatus()" style="border-radius: 10px;">Save Status</button>
                        </div>
                    </div>
                </div>
            </div>

        </div><!-- end page-wrapper -->
    </div><!-- end wrapper -->

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
