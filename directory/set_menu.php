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
    header("Location: member_dashboard.php");
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

    <title>Set Menu</title>
    <link rel="shortcut icon" href="../favicon.ico" type="image/x-icon">

    <link href="../css/bootstrap.min.css" rel="stylesheet">
    <link href="../font-awesome/css/font-awesome.css" rel="stylesheet">

    <link href="../css/animate.css" rel="stylesheet">
    <link href="../css/style.css" rel="stylesheet">
    <link href="../css/custom_modern.css" rel="stylesheet">
    
    <script src="https://cdnjs.cloudflare.com/ajax/libs/jquery/3.6.0/jquery.min.js"></script>
    <script src="https://code.jquery.com/jquery-3.6.4.min.js"></script>
    <link href="../css/plugins/sweetalert/sweetalert.css" rel="stylesheet">
    <script src="../js/plugins/sweetalert/sweetalert.min.js"></script>
    <script src="../app_js/sweetalert-finez.js"></script>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/cropperjs@1.5.13/dist/cropper.min.css">
    <link href="../image_upload/members/upload.css" rel="stylesheet">
    
    <style>
        /* Custom modal override for Set Menu page */
        #membersModal {
            display: none; /* Hidden by default */
            position: fixed;
            z-index: 2050; /* Above bootstrap modal backdrop if any */
            left: 0;
            top: 0;
            width: 100%;
            height: 100%;
            overflow-x: hidden;
            overflow-y: auto;
            background-color: rgba(15, 23, 42, 0.6); /* Glassmorphic dark overlay */
            backdrop-filter: blur(8px);
            -webkit-backdrop-filter: blur(8px);
            transition: opacity 0.3s ease;
        }

        #membersModal .modal-content {
            background-color: var(--card-bg, #ffffff) !important;
            color: var(--text-primary, #0f172a) !important;
            margin: 60px auto;
            width: 90%;
            max-width: 650px; /* Wider and spacious modal */
            border-radius: var(--border-radius-lg, 24px) !important;
            border: 1px solid var(--border-color, #e2e8f0) !important;
            box-shadow: 0 25px 50px -12px rgba(0, 0, 0, 0.25) !important;
            overflow: hidden;
            max-height: calc(100vh - 120px);
            display: flex;
            flex-direction: column;
            animation: modalFadeScaleIn 0.35s cubic-bezier(0.34, 1.56, 0.64, 1);
            padding: 0 !important;
        }

        /* Modal entry animation */
        @keyframes modalFadeScaleIn {
            from {
                opacity: 0;
                transform: scale(0.92) translateY(20px);
            }
            to {
                opacity: 1;
                transform: scale(1) translateY(0);
            }
        }

        /* Modern styled headers and footers for modal */
        .modal-header-custom {
            background: var(--primary-gradient, linear-gradient(135deg, #6366f1 0%, #4f46e5 100%));
            color: #ffffff;
            padding: 20px 28px;
            display: flex;
            justify-content: space-between;
            align-items: center;
            border-bottom: 1px solid rgba(255, 255, 255, 0.1);
        }

        .modal-title-custom {
            margin: 0;
            font-size: 18px;
            font-weight: 700;
            color: #ffffff !important;
            letter-spacing: -0.3px;
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .close-custom {
            color: rgba(255, 255, 255, 0.8);
            font-size: 28px;
            font-weight: 300;
            cursor: pointer;
            transition: all 0.2s ease;
            line-height: 1;
        }

        .close-custom:hover {
            color: #ffffff;
            transform: scale(1.1);
        }

        .modal-body-custom {
            padding: 24px 28px;
            overflow-y: auto;
            flex: 1;
        }

        .modal-footer-custom {
            padding: 16px 28px;
            background-color: var(--bg-main, #f8fafc);
            border-top: 1px solid var(--border-color, #e2e8f0);
            display: flex;
            justify-content: flex-end;
            gap: 12px;
        }

        .modal-actions-bar {
            margin-bottom: 18px;
            display: flex;
            gap: 10px;
        }

        /* Hierarchical Tree Styles */
        .menu-hierarchy-container {
            display: flex;
            flex-direction: column;
            gap: 16px;
        }

        .parent-menu-group {
            background-color: var(--bg-main, #f8fafc);
            border: 1px solid var(--border-color, #e2e8f0);
            border-radius: var(--border-radius-md, 16px);
            overflow: hidden;
            transition: all 0.2s ease;
        }

        .parent-menu-group:hover {
            box-shadow: 0 4px 12px rgba(99, 102, 241, 0.05);
            border-color: rgba(99, 102, 241, 0.2);
        }

        .parent-menu-header {
            padding: 14px 18px;
            background-color: rgba(99, 102, 241, 0.04);
            border-bottom: 1px solid var(--border-color, #e2e8f0);
            font-weight: 600;
        }

        .child-menus-container {
            padding: 14px 18px 18px 36px;
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(180px, 1fr));
            gap: 12px 24px;
        }

        .child-menu-item {
            display: flex;
            align-items: center;
        }

        /* Custom Checkbox Design */
        .custom-checkbox-label {
            display: flex;
            align-items: center;
            position: relative;
            cursor: pointer;
            user-select: none;
            margin: 0;
            font-weight: 500;
            width: 100%;
        }

        .custom-checkbox-label input {
            position: absolute;
            opacity: 0;
            cursor: pointer;
            height: 0;
            width: 0;
        }

        .custom-checkbox-box {
            position: relative;
            height: 20px;
            width: 20px;
            background-color: var(--card-bg, #ffffff);
            border: 2px solid #cbd5e1;
            border-radius: 6px;
            margin-right: 12px;
            flex-shrink: 0;
            transition: all 0.2s cubic-bezier(0.4, 0, 0.2, 1);
        }

        /* On hover, darken border */
        .custom-checkbox-label:hover input ~ .custom-checkbox-box {
            border-color: var(--primary-color, #4f46e5);
        }

        /* When checked, add gradient background and change border */
        .custom-checkbox-label input:checked ~ .custom-checkbox-box {
            background: var(--primary-gradient, linear-gradient(135deg, #6366f1 0%, #4f46e5 100%));
            border-color: transparent;
            box-shadow: 0 2px 8px rgba(99, 102, 241, 0.3);
        }

        /* Checkmark icon (hidden when not checked) */
        .custom-checkbox-box:after {
            content: "";
            position: absolute;
            display: none;
            left: 6px;
            top: 2px;
            width: 5px;
            height: 10px;
            border: solid white;
            border-width: 0 2px 2px 0;
            transform: rotate(45deg);
        }

        /* Show checkmark when checked */
        .custom-checkbox-label input:checked ~ .custom-checkbox-box:after {
            display: block;
        }

        .menu-name-wrapper {
            display: flex;
            align-items: center;
            gap: 8px;
            font-size: 14.5px !important;
            color: var(--text-primary, #0f172a);
        }

        .menu-icon-prefix {
            font-size: 15px;
            color: #6366f1;
            width: 18px;
            text-align: center;
        }

        .child-icon-prefix {
            color: #94a3b8;
            font-size: 13px;
        }

        .parent-label .menu-text-name {
            font-weight: 600;
            font-size: 15px !important;
        }

        .child-label .menu-text-name {
            font-weight: 400;
            font-size: 14px !important;
            color: var(--text-muted, #475569);
        }

        /* Dark mode overrides specifically for our Set Menu page components */
        .dark-theme #membersModal {
            background-color: rgba(9, 13, 22, 0.7);
        }

        .dark-theme .parent-menu-group {
            background-color: rgba(255, 255, 255, 0.02);
            border-color: rgba(255, 255, 255, 0.06);
        }

        .dark-theme .parent-menu-header {
            background-color: rgba(99, 102, 241, 0.08);
            border-color: rgba(255, 255, 255, 0.06);
        }

        .dark-theme .custom-checkbox-box {
            background-color: rgba(15, 23, 42, 0.3);
            border-color: rgba(255, 255, 255, 0.2);
        }

        .dark-theme .custom-checkbox-label:hover input ~ .custom-checkbox-box {
            border-color: #818cf8;
        }

        .dark-theme .child-label .menu-text-name {
            color: #94a3b8;
        }

        /* Table Card and Search Wrapper */
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

        .header-section {
            background: linear-gradient(135deg, rgba(99, 102, 241, 0.03) 0%, rgba(79, 70, 229, 0.03) 100%);
            border-radius: var(--border-radius-lg, 24px);
            padding: 30px;
            margin-bottom: 24px;
            border: 1px solid var(--border-color, #e2e8f0);
        }

        .dark-theme .header-section {
            background: linear-gradient(135deg, rgba(99, 102, 241, 0.08) 0%, rgba(79, 70, 229, 0.08) 100%);
            border-color: rgba(255, 255, 255, 0.06);
        }

        .header-title-area {
            display: flex;
            align-items: center;
            justify-content: space-between;
            margin-bottom: 0px;
            flex-wrap: wrap;
            gap: 15px;
        }

        .header-title-area h2 {
            margin: 0 !important;
            font-weight: 800;
            letter-spacing: -0.5px;
            background: var(--primary-gradient, linear-gradient(135deg, #6366f1 0%, #4f46e5 100%));
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            display: flex;
            align-items: center;
            gap: 12px;
        }

        /* Modernized Search Box */
        .search-container-modern {
            position: relative;
            max-width: 480px;
            width: 100%;
        }

        .search-input-modern {
            width: 100%;
            padding: 14px 20px 14px 48px !important;
            border-radius: 16px !important;
            border: 1px solid var(--border-color, #e2e8f0) !important;
            background-color: var(--card-bg, #ffffff) !important;
            color: var(--text-primary, #0f172a) !important;
            font-size: 15px !important;
            box-shadow: var(--shadow-sm, 0 1px 2px 0 rgba(0, 0, 0, 0.05)) !important;
            transition: all 0.25s ease !important;
        }

        .search-input-modern:focus {
            border-color: var(--primary-color, #4f46e5) !important;
            box-shadow: 0 0 0 4px rgba(99, 102, 241, 0.15) !important;
        }

        .search-icon-inside {
            position: absolute;
            left: 18px;
            top: 50%;
            transform: translateY(-50%);
            color: #94a3b8;
            font-size: 16px;
            pointer-events: none;
            transition: color 0.2s ease;
        }

        .search-input-modern:focus + .search-icon-inside {
            color: var(--primary-color, #4f46e5);
        }

        /* Modern pill/tag for buttons */
        .btn-edit-menu-custom {
            display: inline-flex;
            align-items: center;
            gap: 6px;
            background-color: rgba(99, 102, 241, 0.08) !important;
            color: var(--primary-color, #4f46e5) !important;
            border: 1px solid rgba(99, 102, 241, 0.15) !important;
            border-radius: 12px !important;
            padding: 8px 14px !important;
            font-size: 13.5px !important;
            font-weight: 600 !important;
            cursor: pointer;
            transition: all 0.2s ease;
            box-shadow: none !important;
            text-decoration: none !important;
        }

        .btn-edit-menu-custom:hover {
            background-color: var(--primary-color, #4f46e5) !important;
            color: #ffffff !important;
            border-color: transparent !important;
            transform: translateY(-1px);
            box-shadow: 0 4px 12px rgba(99, 102, 241, 0.2) !important;
        }

        .dark-theme .btn-edit-menu-custom {
            background-color: rgba(129, 140, 248, 0.12) !important;
            color: #818cf8 !important;
            border-color: rgba(129, 140, 248, 0.2) !important;
        }

        .dark-theme .btn-edit-menu-custom:hover {
            background-color: #818cf8 !important;
            color: #0f172a !important;
        }

        /* Custom pagination overrides */
        .text-center > .btn-white {
            border-radius: 10px !important;
            padding: 8px 14px !important;
            margin: 0 3px;
            font-weight: 600 !important;
            font-size: 14px !important;
            border: 1px solid var(--border-color, #e2e8f0) !important;
            background: var(--card-bg, #ffffff) !important;
            color: var(--text-muted, #475569) !important;
            box-shadow: none !important;
            transition: all 0.2s ease;
        }

        .text-center > .btn-white:hover {
            border-color: var(--primary-color, #4f46e5) !important;
            color: var(--primary-color, #4f46e5) !important;
            background-color: rgba(99, 102, 241, 0.04) !important;
            transform: translateY(-1px);
        }

        .text-center > .btn-white.active {
            background: var(--primary-gradient, linear-gradient(135deg, #6366f1 0%, #4f46e5 100%)) !important;
            color: #ffffff !important;
            border-color: transparent !important;
            box-shadow: 0 4px 10px rgba(99, 102, 241, 0.25) !important;
        }
        
        .dark-theme .text-center > .btn-white {
            border-color: rgba(255, 255, 255, 0.08) !important;
            background: rgba(255, 255, 255, 0.02) !important;
            color: #94a3b8 !important;
        }
        
        .dark-theme .text-center > .btn-white.active {
            color: #ffffff !important;
        }

        .email-link-custom {
            color: var(--primary-color, #4f46e5);
            text-decoration: none;
            font-weight: 500;
            display: inline-flex;
            align-items: center;
            gap: 6px;
            transition: color 0.2s;
        }
        .email-link-custom:hover {
            color: var(--primary-hover, #4338ca);
            text-decoration: none;
        }
        .dark-theme .email-link-custom {
            color: #818cf8;
        }
        .dark-theme .email-link-custom:hover {
            color: #a5b4fc;
        }
    </style>
    
    <script>
        $(document).ready(function() {          
            loadData(1); // Function to load data for a specific page       
            
            // Close modal when clicking outside modal-content
            window.addEventListener('click', function(event) {
                var modal = document.getElementById('membersModal');
                if (event.target == modal) {
                    closeMenuModal();
                }
            });
        });  
        
        function searchMembers(){
            loadData(1);
        }

        // function to load the details of login created users start
        function loadData(page) {
            $('#hdn_current_page').val(page); //used for Status Update function
            console.log("Loading data for page:", page);
            $.ajax({               
               type: "POST",
               url: "api/set_menu.php",
               data: {
               action: 'load_login_data',
               page: page, 
               val:$('#txt_search').val()
               },
                success: function(data) {  
                     
                    var obj = jQuery.parseJSON(data);
                    var totalrows = obj[0].total_rows;
                    var htm="";
                    
                    htm=htm+ "<div class='col-lg-12'>";
                    htm=htm+ "<div class='ibox float-e-margins'>";
                    htm=htm+ "<div class='ibox-content'>";
                    htm=htm+ "<div class='table-responsive'>";
                    htm=htm+ "<table class='table table-striped'>";
                    htm=htm+ "<thead>";
                    htm=htm+ "<tr>";
                    htm=htm+ "<th>Name</th>";
                    htm=htm+ "<th>Email</th>";
                    htm=htm+ "<th style='text-align: right;'>Action</th>";
                    htm=htm+ "</tr>";
                    htm=htm+ "</thead>";
                    htm=htm+ "<tbody>";
                    for (var i = 0; i < obj[1].length; i++) {
                        htm=htm+ "<tr>";
                        htm=htm+ "<td style='font-weight: 600;'>"+obj[1][i].name+"</td>";
                        htm=htm+ "<td><a href='mailto:"+obj[1][i].email+ "' class='email-link-custom'><i class='fa fa-envelope-o' style='font-family: FontAwesome !important;'></i> "+obj[1][i].email+ "</a></td>";
                        htm=htm+ "<td style='text-align: right;'><a onclick='showMenuModal("+obj[1][i].login_id+");' class='btn-edit-menu-custom'><i class='fa fa-sliders' style='font-family: FontAwesome !important;'></i> Edit Menu</a></td>";
                        htm=htm+ "</tr>";
                    }                

                    htm=htm+ "</tbody>";
                    htm=htm+ "</table>";
                    htm=htm+ "</div>";
                    htm=htm+ "</div>";
                    htm=htm+ "</div>";
                    htm=htm+ "</div>";
                    $('#table_members').html(htm);
                    var htmpage= paginate(totalrows,page);
                    $('#table_members').append(htmpage);
                },

                error: function(xhr, status, error) {
                    console.log('AJAX error: ', status, error);
                }
            });
            loadMenu();
            load_set_menu();
        }

        // function to show the popup for set menu start
        function showMenuModal(login_id) {
            id = login_id;
            load_set_menu();
            fetchMenuDetails(login_id);
            document.getElementById('membersModal').style.display = 'block';
        }

        // function to close the popup for set menu start
        function closeMenuModal() {
            document.getElementById('membersModal').style.display = 'none';
            $("input[name='group']:checkbox").prop('checked',false);
        }

        var menuDataLoaded = false;
        var rawMenus = [];

        // function to load all menus via ajax start
        function load_set_menu(){
            if (menuDataLoaded) return;
            $.ajax({
                type: "POST",
                url: "api/set_menu.php",
                data: {
                    action: 'load_menu',
                },
                success: function(data) { 
                    var obj = jQuery.parseJSON(data);
                    rawMenus = obj[0];
                    renderMenuHierarchy();
                    menuDataLoaded = true;
                },
                error: function(xhr, status, error) {
                        console.log('AJAX error: ', status, error);
                }
            });
        }

        function renderMenuHierarchy() {
            var parents = {};
            var children = {};
            
            // First group by parent
            for (var i = 0; i < rawMenus.length; i++) {
                var m = rawMenus[i];
                if (m.menu_level == 1) {
                    parents[m.menu_id] = m;
                } else if (m.menu_level == 2) {
                    if (!children[m.parent_id]) {
                        children[m.parent_id] = [];
                    }
                    children[m.parent_id].push(m);
                }
            }

            var htm = "";
            htm += "<div class='modal-header-custom'>";
            htm += "  <h3 class='modal-title-custom'><i class='fa fa-sliders' style='font-family: FontAwesome !important;'></i> Set Menu Permissions</h3>";
            htm += "  <span class='close-custom' onclick='closeMenuModal()'>&times;</span>";
            htm += "</div>";
            
            htm += "<div class='modal-body-custom'>";
            htm += "  <div class='modal-actions-bar'>";
            htm += "    <button type='button' class='btn btn-xs btn-white' onclick='toggleAllMenus(true)'><i class='fa fa-check-square-o' style='font-family: FontAwesome !important;'></i> Select All</button>";
            htm += "    <button type='button' class='btn btn-xs btn-white' onclick='toggleAllMenus(false)'><i class='fa fa-square-o' style='font-family: FontAwesome !important;'></i> Clear All</button>";
            htm += "  </div>";
            htm += "  <div class='menu-hierarchy-container'>";

            // Render parents and their nested children
            for (var parentId in parents) {
                var p = parents[parentId];
                var pIcon = p.icon || 'fa fa-folder-o';
                var pChildren = children[parentId] || [];

                // Hide menu_id 25 (Staff/Member Attendance - redundant)
                // Hide menu_id 45, 46 (standalone report items - auto-bundled)
                if (p.menu_id == 25 || p.menu_id == 45 || p.menu_id == 46) {
                    continue;
                }
                
                htm += "<div class='parent-menu-group' data-parent-id='" + p.menu_id + "'>";
                
                // Parent block
                var parentDisplayName = p.name;
                var parentBadge = '';
                if (p.menu_id == 11) {
                    parentDisplayName = "Admin Mark Attendance";
                    parentBadge = "<span style='font-size:10px;font-weight:600;background:#fff3e0;color:#e65100;border:1px solid #ffcc02;border-radius:20px;padding:2px 9px;margin-left:8px;font-family:Inter,sans-serif;vertical-align:middle;'>All Members</span>";
                } else if (p.menu_id == 25) {
                    parentDisplayName = "Staff/Member Mark Attendance";
                    parentBadge = "<span style='font-size:10px;font-weight:600;background:#e8f5e9;color:#2e7d32;border:1px solid #a5d6a7;border-radius:20px;padding:2px 9px;margin-left:8px;font-family:Inter,sans-serif;vertical-align:middle;'>Own Only</span>";
                } else if (p.menu_id == 44) {
                    parentDisplayName = "Member Mark Attendance";
                    parentBadge = "<span style='font-size:10px;font-weight:600;background:#e8f5e9;color:#2e7d32;border:1px solid #a5d6a7;border-radius:20px;padding:2px 9px;margin-left:8px;font-family:Inter,sans-serif;vertical-align:middle;'>Own Only</span>";
                }
                
                htm += "  <div class='parent-menu-header'>";
                htm += "    <label class='custom-checkbox-label parent-label'>";
                htm += "      <input type='checkbox' name='group' id='" + p.menu_id + "' value='" + p.menu_id + "' class='parent-checkbox' data-id='" + p.menu_id + "' onchange='onParentToggle(this)'>";
                htm += "      <span class='custom-checkbox-box'></span>";
                htm += "      <span class='menu-name-wrapper'><i class='" + pIcon + " menu-icon-prefix' style='font-family: FontAwesome !important;'></i> <span class='menu-text-name'>" + parentDisplayName + "</span>" + parentBadge + "</span>";
                htm += "    </label>";
                htm += "  </div>";
                
                // Children block
                // Filter children based on parent:
                // Admin Mark Attendance (11): only show child 12
                // Staff/Member Mark Attendance (25): only show child 26
                // hide admin-only items (13=Manage Holidays, 14=Process Monthly, 27=Manage Holidays, 28=Attendance Details, 37=Monthly Report)
                var filteredChildren = pChildren;
                if (p.menu_id == 11) {
                    filteredChildren = pChildren.filter(function(ch) { return ch.menu_id == 12; });
                } else if (p.menu_id == 25) {
                    filteredChildren = pChildren.filter(function(ch) { return ch.menu_id == 26; });
                }

                if (filteredChildren.length > 0) {
                    htm += "  <div class='child-menus-container'>";
                    for (var j = 0; j < filteredChildren.length; j++) {
                        var c = filteredChildren[j];
                        var cIcon = c.icon || 'fa fa-file-o';
                        
                        var childDisplayName = c.name;
                        if (p.menu_id == 11 && c.menu_id == 12) {
                            childDisplayName = "Mark Attendance (All Members)";
                        }
                        
                        htm += "    <div class='child-menu-item'>";
                        htm += "      <label class='custom-checkbox-label child-label'>";
                        htm += "        <input type='checkbox' name='group' id='" + c.menu_id + "' value='" + c.menu_id + "' class='child-checkbox' data-parent='" + p.menu_id + "' onchange='onChildToggle(this)'>";
                        htm += "        <span class='custom-checkbox-box'></span>";
                        htm += "        <span class='menu-name-wrapper'><i class='" + cIcon + " menu-icon-prefix child-icon-prefix' style='font-family: FontAwesome !important;'></i> <span class='menu-text-name'>" + childDisplayName + "</span></span>";
                        htm += "      </label>";
                        htm += "    </div>";
                    }
                    htm += "  </div>";
                }
                
                htm += "</div>"; // End parent-menu-group
            }

            htm += "  </div>"; // End menu-hierarchy-container
            htm += "</div>"; // End modal-body-custom
            
            htm += "<div class='modal-footer-custom'>";
            htm += "  <button type='button' onclick='closeMenuModal()' class='btn btn-white btn-sm'>Cancel</button>";
            htm += "  <button type='button' onclick='addMenuToUser();' class='btn btn-primary btn-sm'><i class='fa fa-save' style='font-family: FontAwesome !important;'></i> Save Changes</button>";
            htm += "</div>";

            $('#group_container').html(htm);
        }

        function onParentToggle(checkbox) {
            var parentId = checkbox.value;
            var isChecked = checkbox.checked;
            $("input[type=checkbox][data-parent='" + parentId + "']").each(function() {
                this.checked = isChecked;
            });
        }
        
        function onChildToggle(checkbox) {
            var parentId = checkbox.getAttribute('data-parent');
            var isChecked = checkbox.checked;
            if (isChecked) {
                var parentCheckbox = document.getElementById(parentId);
                if (parentCheckbox) {
                    parentCheckbox.checked = true;
                }
            }
        }
        
        function toggleAllMenus(checked) {
            $("input[name='group']:checkbox").prop('checked', checked);
        }

        // function to add menu to the users start
        function addMenuToUser() {
            var menu_ids = [];
            $("#group_container input[type=checkbox]:checked").each(function () {
                menu_ids.push(this.value);
            });
            
            $.ajax({               
                type: "POST",
                url: "api/set_menu.php",
                data: {
                    action: 'save_menu',
                    id: id, 
                    menu_ids: menu_ids
                },
                success: function(data) {  
                    alertsuccess('Saved Successfully');                
                    closeMenuModal();
                    loadData($('#hdn_current_page').val());                
                },
                error: function(xhr, status, error) {
                    console.log('AJAX error: ', status, error);
                }
            });     
        }

        // function to fetch the current menu details start
        function fetchMenuDetails(login_id){
            $.ajax({               
                type: "POST",
                url: "api/set_menu.php",
                data: {
                action: 'fetch_menu_details',
                id:login_id,
                },
                success: function(data) {
                    var obj = jQuery.parseJSON(data);
                    $("input[name='group']:checkbox").prop('checked', false);
                    for (var i = 0; i < obj.length; i++) {
                        var checkbox = document.getElementById(obj[i].menu_id);
                        if (checkbox) {
                            checkbox.checked = true;
                        }
                    }
                },
                error: function(xhr, status, error) {
                    console.log('AJAX error: ', status, error);
                }
            });
        }
    </script>
    <script src="../app_pagination/pagination.js"></script>
    <script src="../app_menu/menu.js"></script>

</head>

<body>
<!-- hidden values -->
<input type="hidden" id="hdn_current_page"  value="0">
<input type="hidden" id="hdn_id"  value="0">
<input type="hidden" id="hdn_file_upload" name="image" value="0">

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
        <!-- navigation starts -->
        <div id="page-wrapper" class="gray-bg">
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
            
            <div class="settings-card-wrapper">
                <!-- header & search bar started -->
                <div class="header-section">
                    <div class="header-title-area">
                        <h2><i class="fa fa-cogs" style="font-family: FontAwesome !important;"></i> Menu Settings</h2>
                        <div class="search-container-modern">
                            <form onsubmit="event.preventDefault(); searchMembers();">
                                <input type="text" placeholder="Search user by name..." id="txt_search" name="search" class="search-input-modern" onkeyup="if(event.key === 'Enter') searchMembers();">
                                <i class="fa fa-search search-icon-inside"></i>
                            </form>
                        </div>
                    </div>
                </div>
                <!-- header & search bar end -->
                
                <!-- popup for add members into family starts -->
                <div id='membersModal' class='modal'>
                    <div class='modal-content' id="group_container">
                        <!-- popup data injected by ajax -->
                    </div>
                </div>
                <!-- popup for add members into family ends -->
                
                <div class="wrapper wrapper-content animated fadeInRight" id="table_members" style="padding: 0;">
                    <!-- Data injected by ajax -->
                </div>
            </div>
               
        </div>
    </div>
    
    <!-- Mainly scripts -->
    <script src="../js/jquery-3.1.1.min.js"></script>
    <script src="../js/bootstrap.min.js"></script>
    <script src="../js/plugins/metisMenu/jquery.metisMenu.js"></script>
    <script src="../js/plugins/slimscroll/jquery.slimscroll.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/cropperjs@1.5.13/dist/cropper.min.js"></script>
    <!-- Custom and plugin javascript -->
    <script src="../js/inspinia.js"></script>
    <script src="../js/plugins/pace/pace.min.js"></script>
    <script src="../image_upload/members/image_upload.js"></script>
    <script src="../app_js/validation.js"></script>

</body>

</html>
