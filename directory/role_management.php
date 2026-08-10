<?php
session_start();
include '../app_common/db_connect.php';
include '../app_common/auth_helper.php';

if (empty($_SESSION['login_id'])) {
    header("Location: ../index.php");
    exit();
}

$login_id = (int)$_SESSION['login_id'];
if (!isSuperAdmin($login_id)) {
    header("Location: dashboard.php");
    exit();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Role & Permissions Manager - YMCA</title>

    <!-- FontAwesome & Google Fonts -->
    <link href="../font-awesome/css/font-awesome.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&display=swap" rel="stylesheet">

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
            --brand-primary: #3b82f6;
            --brand-gradient: linear-gradient(135deg, #2563eb 0%, #1d4ed8 50%, #1e40af 100%);
            --card-bg: #ffffff;
            --border-color: #e2e8f0;
            --text-main: #0f172a;
            --text-sub: #64748b;
        }

        body {
            font-family: 'Plus Jakarta Sans', sans-serif;
            background-color: #f1f5f9;
            color: var(--text-main);
            min-height: 100vh;
        }

        /* Dark mode overrides */
        body.dark-mode {
            background-color: #0b1329 !important;
            color: #f8fafc !important;
        }
        body.dark-mode #page-wrapper {
            background-color: #0b1329 !important;
        }
        body.dark-mode .hero-header {
            background: linear-gradient(135deg, #1e1b4b 0%, #311b92 50%, #1e293b 100%) !important;
            border-color: rgba(255, 255, 255, 0.1) !important;
        }
        body.dark-mode .metric-card,
        body.dark-mode .user-card-item {
            background: #111c3a !important;
            border-color: #1e293b !important;
            color: #f8fafc !important;
        }
        body.dark-mode .search-input {
            background: #1e293b !important;
            color: #ffffff !important;
            border-color: #334155 !important;
        }
        body.dark-mode .modal-content {
            background: #111c3a !important;
            color: #ffffff !important;
        }
        body.dark-mode .form-select-custom {
            background: #1e293b !important;
            color: #ffffff !important;
            border-color: #334155 !important;
        }

        .role-page-container {
            padding: 30px 40px;
            max-width: 1400px;
            margin: 0 auto;
        }

        /* Hero Header */
        .hero-header {
            background: linear-gradient(135deg, #0f172a 0%, #1e293b 50%, #1e1b4b 100%);
            border-radius: 24px;
            padding: 32px 40px;
            color: #ffffff;
            margin-bottom: 30px;
            box-shadow: 0 20px 40px rgba(15, 23, 42, 0.15);
            display: flex;
            justify-content: space-between;
            align-items: center;
            flex-wrap: wrap;
            gap: 24px;
            position: relative;
            overflow: hidden;
        }

        .hero-header::before {
            content: '';
            position: absolute;
            top: -50%;
            right: -10%;
            width: 350px;
            height: 350px;
            background: radial-gradient(circle, rgba(59, 130, 246, 0.3) 0%, rgba(255, 255, 255, 0) 70%);
            pointer-events: none;
        }

        .hero-title-group h2 {
            margin: 0;
            font-weight: 800;
            font-size: 28px;
            letter-spacing: -0.5px;
            display: flex;
            align-items: center;
            gap: 14px;
        }

        .hero-title-group h2 i {
            background: rgba(255, 255, 255, 0.15);
            width: 48px;
            height: 48px;
            border-radius: 14px;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            color: #60a5fa;
            font-size: 22px;
        }

        .hero-title-group p {
            margin: 8px 0 0 62px;
            color: #94a3b8;
            font-size: 14px;
            font-weight: 500;
        }

        /* Search Bar & Action Buttons */
        .hero-actions {
            display: flex;
            align-items: center;
            gap: 14px;
            flex-wrap: wrap;
        }

        .search-wrap {
            position: relative;
        }

        .search-wrap i {
            position: absolute;
            left: 16px;
            top: 50%;
            transform: translateY(-50%);
            color: #94a3b8;
            font-size: 16px;
        }

        .search-input {
            width: 320px;
            height: 48px;
            border-radius: 14px;
            border: 1.5px solid rgba(255, 255, 255, 0.2);
            background: rgba(255, 255, 255, 0.1);
            backdrop-filter: blur(10px);
            padding: 0 16px 0 46px;
            font-size: 14px;
            font-weight: 600;
            color: #ffffff;
            outline: none;
            transition: all 0.25s ease;
        }

        .search-input::placeholder {
            color: #94a3b8;
        }

        .search-input:focus {
            border-color: #60a5fa;
            background: rgba(255, 255, 255, 0.18);
            box-shadow: 0 0 0 4px rgba(96, 165, 250, 0.25);
        }

        .btn-dash-back {
            height: 48px;
            padding: 0 22px;
            border-radius: 14px;
            background: rgba(255, 255, 255, 0.12);
            color: #ffffff;
            border: 1px solid rgba(255, 255, 255, 0.2);
            font-weight: 700;
            font-size: 14px;
            display: inline-flex;
            align-items: center;
            gap: 8px;
            text-decoration: none;
            transition: all 0.25s ease;
        }

        .btn-dash-back:hover {
            background: rgba(255, 255, 255, 0.22);
            color: #ffffff;
            text-decoration: none;
            transform: translateY(-2px);
        }

        /* Metric Cards */
        .metrics-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(220px, 1fr));
            gap: 18px;
            margin-bottom: 30px;
        }

        .metric-card {
            background: #ffffff;
            border: 1px solid #e2e8f0;
            border-radius: 20px;
            padding: 22px 24px;
            display: flex;
            align-items: center;
            gap: 18px;
            transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.03);
        }

        .metric-card:hover {
            transform: translateY(-4px);
            box-shadow: 0 12px 28px rgba(0, 0, 0, 0.08);
        }

        .metric-icon {
            width: 56px;
            height: 56px;
            border-radius: 16px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 22px;
            flex-shrink: 0;
        }

        .icon-blue { background: #eff6ff; color: #2563eb; }
        .icon-purple { background: #f3e8ff; color: #7c3aed; }
        .icon-amber { background: #fffbeb; color: #d97706; }
        .icon-emerald { background: #ecfdf5; color: #059669; }

        .metric-label {
            font-size: 12px;
            font-weight: 700;
            color: var(--text-sub);
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }

        .metric-value {
            font-size: 26px;
            font-weight: 800;
            color: var(--text-main);
            margin-top: 2px;
            line-height: 1;
        }

        /* User Cards Grid */
        .users-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(360px, 1fr));
            gap: 20px;
        }

        .user-card-item {
            background: #ffffff;
            border: 1px solid #e2e8f0;
            border-radius: 20px;
            padding: 24px;
            display: flex;
            flex-direction: column;
            justify-content: space-between;
            transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
            position: relative;
            box-shadow: 0 4px 16px rgba(0, 0, 0, 0.02);
        }

        .user-card-item:hover {
            transform: translateY(-4px);
            border-color: #cbd5e1;
            box-shadow: 0 16px 32px rgba(15, 23, 42, 0.08);
        }

        .user-header-row {
            display: flex;
            align-items: flex-start;
            gap: 16px;
        }

        .avatar-circle {
            width: 52px;
            height: 52px;
            border-radius: 16px;
            background: linear-gradient(135deg, #3b82f6, #1d4ed8);
            color: #ffffff;
            font-weight: 800;
            font-size: 20px;
            display: flex;
            align-items: center;
            justify-content: center;
            flex-shrink: 0;
            box-shadow: 0 4px 12px rgba(37, 99, 235, 0.3);
        }

        .user-info-name {
            font-size: 17px;
            font-weight: 800;
            margin: 0;
            color: var(--text-main);
            display: flex;
            align-items: center;
            gap: 8px;
            flex-wrap: wrap;
        }

        .user-email-text {
            font-size: 13px;
            color: var(--text-sub);
            margin: 4px 0 0 0;
            font-weight: 500;
        }

        /* Role Badges */
        .role-pill {
            padding: 4px 12px;
            border-radius: 20px;
            font-size: 11px;
            font-weight: 800;
            letter-spacing: 0.3px;
            text-transform: uppercase;
            display: inline-flex;
            align-items: center;
            gap: 5px;
        }

        .pill-super-admin { background: #fee2e2; color: #991b1b; border: 1px solid #fca5a5; }
        .pill-group-admin { background: #dbeafe; color: #1e40af; border: 1px solid #93c5fd; }
        .pill-attendance-master { background: #fef3c7; color: #92400e; border: 1px solid #fde68a; }
        .pill-member { background: #f1f5f9; color: #475569; border: 1px solid #cbd5e1; }

        /* Assignment Chips */
        .assignments-section {
            margin-top: 18px;
            padding-top: 14px;
            border-top: 1px dashed #e2e8f0;
        }

        .assignment-title {
            font-size: 11px;
            font-weight: 800;
            color: var(--text-sub);
            text-transform: uppercase;
            letter-spacing: 0.5px;
            margin-bottom: 8px;
        }

        .chips-wrap {
            display: flex;
            gap: 8px;
            flex-wrap: wrap;
        }

        .role-chip {
            background: #f8fafc;
            border: 1px solid #cbd5e1;
            border-radius: 10px;
            padding: 5px 12px;
            font-size: 12px;
            font-weight: 700;
            color: #1e293b;
            display: inline-flex;
            align-items: center;
            gap: 8px;
            transition: all 0.2s ease;
        }

        .role-chip:hover {
            background: #ffffff;
            border-color: #94a3b8;
        }

        .chip-delete-btn {
            color: #ef4444;
            cursor: pointer;
            padding: 2px 4px;
            border-radius: 4px;
            transition: all 0.2s ease;
        }

        .chip-delete-btn:hover {
            background: #fee2e2;
        }

        .btn-assign-trigger {
            margin-top: 18px;
            width: 100%;
            height: 42px;
            border-radius: 12px;
            background: linear-gradient(135deg, #2563eb, #1d4ed8);
            color: #ffffff;
            border: none;
            font-weight: 700;
            font-size: 13.5px;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 8px;
            cursor: pointer;
            transition: all 0.25s ease;
            box-shadow: 0 4px 14px rgba(37, 99, 235, 0.25);
        }

        .btn-assign-trigger:hover {
            background: linear-gradient(135deg, #1d4ed8, #1e40af);
            transform: translateY(-1px);
            box-shadow: 0 6px 18px rgba(37, 99, 235, 0.35);
        }

        /* Modal Styling */
        .modal-content {
            border-radius: 24px;
            border: none;
            box-shadow: 0 25px 50px -12px rgba(0, 0, 0, 0.25);
            overflow: hidden;
        }

        .modal-header {
            background: linear-gradient(135deg, #0f172a, #1e293b);
            color: #ffffff;
            padding: 24px 30px;
            border: none;
        }

        .modal-title {
            font-weight: 800;
            font-size: 18px;
            letter-spacing: -0.3px;
        }

        .modal-body {
            padding: 30px;
        }

        .form-select-custom {
            width: 100%;
            height: 48px;
            border-radius: 12px;
            border: 1.5px solid #cbd5e1;
            padding: 0 16px;
            font-size: 14px;
            font-weight: 700;
            color: #0f172a;
            outline: none;
            transition: all 0.2s ease;
        }

        .form-select-custom:focus {
            border-color: #2563eb;
            box-shadow: 0 0 0 4px rgba(37, 99, 235, 0.15);
        }

        .btn-modal-save {
            height: 46px;
            padding: 0 24px;
            border-radius: 12px;
            background: linear-gradient(135deg, #2563eb, #1d4ed8);
            color: #ffffff;
            font-weight: 800;
            font-size: 14px;
            border: none;
            cursor: pointer;
            transition: all 0.2s ease;
        }

        .btn-modal-save:hover {
            transform: translateY(-1px);
            box-shadow: 0 4px 14px rgba(37, 99, 235, 0.35);
        }
    </style>
</head>
<body>

<div id="wrapper">

    <!-- Navigation Sidebar -->
    <nav class="navbar-default navbar-static-side" role="navigation">
        <div class="sidebar-collapse" id="divMenuContainer">
            <!-- menu injected dynamically via menu.js -->
        </div>
    </nav>

    <!-- Page Wrapper -->
    <div id="page-wrapper" class="gray-bg">
        
        <!-- Top Navbar -->
        <div class="row border-bottom">
            <nav class="navbar navbar-static-top" role="navigation" style="margin-bottom: 0">
                <div class="navbar-header">
                    <a class="navbar-minimalize minimalize-styl-2 btn btn-primary" href="#"><i class="fa fa-bars"></i></a>
                </div>
                <ul class="nav navbar-top-links navbar-right">     
                    <li>
                        <a href="../app_login_manager/logout.php" style="color: #147ad1;">
                            <i class="fa fa-sign-out"></i> Log out
                        </a>
                    </li>
                </ul>
            </nav>
        </div>

        <div class="role-page-container">
            
            <!-- Hero Header -->
            <div class="hero-header">
                <div class="hero-title-group">
                    <h2><i class="fa fa-shield"></i> Role & Permissions Manager</h2>
                    <p>Assign Group Admins and Attendance Masters for specific groups</p>
                </div>
                <div class="hero-actions">
                    <div class="search-wrap">
                        <i class="fa fa-search"></i>
                        <input type="text" id="user-search" class="search-input" placeholder="Search user by name or email..." onkeyup="loadUsers()">
                    </div>
                    <a href="dashboard.php" class="btn-dash-back">
                        <i class="fa fa-arrow-left"></i> Dashboard
                    </a>
                </div>
            </div>

            <!-- Summary Metrics Grid -->
            <div class="metrics-grid">
                <div class="metric-card">
                    <div class="metric-icon icon-blue"><i class="fa fa-users"></i></div>
                    <div>
                        <div class="metric-label">Total System Logins</div>
                        <div class="metric-value" id="metric-total-users">0</div>
                    </div>
                </div>
                <div class="metric-card">
                    <div class="metric-icon icon-purple"><i class="fa fa-user-circle-o"></i></div>
                    <div>
                        <div class="metric-label">Group Admins</div>
                        <div class="metric-value" id="metric-group-admins">0</div>
                    </div>
                </div>
                <div class="metric-card">
                    <div class="metric-icon icon-amber"><i class="fa fa-calendar-check-o"></i></div>
                    <div>
                        <div class="metric-label">Attendance Masters</div>
                        <div class="metric-value" id="metric-att-masters">0</div>
                    </div>
                </div>
                <div class="metric-card">
                    <div class="metric-icon icon-emerald"><i class="fa fa-star"></i></div>
                    <div>
                        <div class="metric-label">Super Admins</div>
                        <div class="metric-value" id="metric-super-admins">0</div>
                    </div>
                </div>
            </div>

            <!-- Users Grid Container -->
            <div id="users-list-container">
                <div style="text-align:center; padding:60px; color:#64748b;">
                    <i class="fa fa-spinner fa-spin fa-3x" style="color:#2563eb;"></i>
                    <h4 style="margin-top:16px; font-weight:700;">Loading permissions & users...</h4>
                </div>
            </div>

        </div>
    </div>
</div>

<!-- Assign Role Modal -->
<div class="modal fade" id="assignRoleModal" tabindex="-1" role="dialog" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="modal-title-name"><i class="fa fa-user-plus" style="margin-right:8px; color:#60a5fa;"></i> Assign User Role & Group</h5>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close" style="color:#ffffff; opacity:0.8;">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <div class="modal-body">
                <form id="assignRoleForm">
                    <input type="hidden" id="modal-target-login-id">
                    
                    <div class="form-group" style="margin-bottom:20px;">
                        <label style="font-size:12px; font-weight:800; color:#475569; text-transform:uppercase; letter-spacing:0.5px; margin-bottom:8px; display:block;">Select Role</label>
                        <select id="modal-role-select" class="form-control" style="border-radius:10px; height:44px; font-weight:600; font-size:13.5px; border:1.5px solid #cbd5e1;">
                            <option value="0">Member (Default Baseline Access)</option>
                            <!-- Roles populated via JS -->
                        </select>
                    </div>

                    <div class="form-group" id="group-select-wrap" style="margin-bottom:24px;">
                        <label style="font-size:12px; font-weight:800; color:#475569; text-transform:uppercase; letter-spacing:0.5px; margin-bottom:8px; display:block;">Select Assigned Group</label>
                        <select id="modal-group-select" class="form-control" style="border-radius:10px; height:44px; font-weight:600; font-size:13.5px; border:1.5px solid #cbd5e1;">
                            <option value="0">All Groups (Global Access)</option>
                            <!-- Groups populated via JS -->
                        </select>
                    </div>

                    <div style="display:flex; justify-content:space-between; align-items:center; flex-wrap:wrap; gap:12px;">
                        <button type="button" onclick="revokeAllPermissionsFromModal()" class="btn-dash-back" style="background:#fee2e2; color:#dc2626; border:1px solid #fca5a5; font-size:12.5px;" title="Reset user to normal member">
                            <i class="fa fa-user-times"></i> Reset to Member
                        </button>
                        <div style="display:flex; gap:8px;">
                            <button type="button" class="btn-dash-back" style="background:#e2e8f0; color:#475569; border:none;" data-dismiss="modal">Cancel</button>
                            <button type="button" onclick="submitRoleAssignment()" class="btn-modal-save">Save Role Assignment</button>
                        </div>
                    </div>
                </form>
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
<script src="../js/plugins/sweetalert/sweetalert.min.js"></script>
<script src="../app_menu/menu.js"></script>

<script>
var globalRoles = [];
var globalGroups = [];
var usersMap = {};

$(document).ready(function() {
    loadMenu();
    loadRolesAndGroups();
    loadUsers();
});

function loadRolesAndGroups() {
    $.post('api/role_management.php', { action: 'load_roles' }, function(res) {
        try {
            var d = (typeof res === 'string') ? JSON.parse(res) : res;
            if (d.status === 'success') {
                globalRoles = d.data;
                var html = '<option value="0">Member (Default Baseline Access)</option>';
                d.data.forEach(function(r) {
                    if (parseInt(r.role_id) === 4 || r.role_name === 'Member') return;
                    html += '<option value="' + r.role_id + '">' + escapeHtml(r.role_name) + ' — ' + escapeHtml(r.description) + '</option>';
                });
                $('#modal-role-select').html(html);
            }
        } catch(e) {}
    });

    $.post('api/role_management.php', { action: 'load_groups' }, function(res) {
        try {
            var d = (typeof res === 'string') ? JSON.parse(res) : res;
            if (d.status === 'success') {
                globalGroups = d.data;
                var html = '<option value="0">All Groups (Global Access)</option>';
                d.data.forEach(function(g) {
                    html += '<option value="' + g.group_id + '">' + escapeHtml(g.group_name) + '</option>';
                });
                $('#modal-group-select').html(html);
            }
        } catch(e) {}
    });
}

function loadUsers() {
    var searchVal = $('#user-search').val();
    $.post('api/role_management.php', { action: 'load_users', search: searchVal }, function(res) {
        try {
            var d = (typeof res === 'string') ? JSON.parse(res) : res;
            if (d.status === 'success') {
                renderUsersGrid(d.data);
            }
        } catch(e) {}
    });
}

function renderUsersGrid(users) {
    usersMap = {};
    if (!users || users.length === 0) {
        $('#users-list-container').html('<div style="text-align:center; padding:60px; background:#ffffff; border-radius:20px; border:1px solid #e2e8f0; color:#64748b;"><i class="fa fa-user-times fa-3x" style="color:#cbd5e1; margin-bottom:12px;"></i><h4 style="font-weight:700;">No users found</h4><p style="margin:0; font-size:13px;">Try adjusting your search term.</p></div>');
        return;
    }

    var superAdmins = 0, groupAdmins = 0, attMasters = 0;

    var html = '<div class="users-grid">';
    users.forEach(function(u) {
        usersMap[u.login_id] = u;

        var badgeClass = 'pill-member';
        if (u.primary_role === 'Super Admin') { badgeClass = 'pill-super-admin'; superAdmins++; }
        else if (u.primary_role === 'Group Admin') { badgeClass = 'pill-group-admin'; groupAdmins++; }
        else if (u.primary_role === 'Attendance Master') { badgeClass = 'pill-attendance-master'; attMasters++; }

        var initials = u.name ? u.name.substring(0, 2).toUpperCase() : 'US';

        html += '<div class="user-card-item">';
        html += '  <div>';
        html += '    <div class="user-header-row">';
        html += '      <div class="avatar-circle">' + initials + '</div>';
        html += '      <div style="flex:1; overflow:hidden;">';
        html += '        <h4 class="user-info-name">' + escapeHtml(u.name) + '</h4>';
        html += '        <p class="user-email-text"><i class="fa fa-envelope-o"></i> ' + escapeHtml(u.email) + '</p>';
        html += '        <div style="margin-top:6px;"><span class="role-pill ' + badgeClass + '">' + u.primary_role + '</span></div>';
        html += '      </div>';
        html += '    </div>';

        // Display current group-role assignments
        var hasSpecialRoles = false;
        if (u.assignments && u.assignments.length > 0) {
            var validAssignments = u.assignments.filter(function(a) { return parseInt(a.role_id) !== 4; });
            if (validAssignments.length > 0) {
                hasSpecialRoles = true;
                html += '    <div class="assignments-section">';
                html += '      <div class="assignment-title">Assigned Role & Scope:</div>';
                html += '      <div class="chips-wrap">';
                validAssignments.forEach(function(a) {
                    var groupName = "All Groups";
                    if (a.group_id > 0) {
                        var foundGrp = globalGroups.find(g => g.group_id == a.group_id);
                        if (foundGrp) groupName = foundGrp.group_name;
                    }
                    html += '        <span class="role-chip">';
                    html += '          <i class="fa fa-shield" style="color:#2563eb;"></i> ' + a.role_name + ' (' + groupName + ')';
                    if (a.id) {
                        html += '          <i class="fa fa-times chip-delete-btn" onclick="deleteAssignment(' + a.id + ')" title="Remove Permission"></i>';
                    }
                    html += '        </span>';
                });
                html += '      </div>';
                html += '    </div>';
            }
        }

        html += '  </div>';

        html += '  <button onclick="openAssignModal(' + u.login_id + ')" class="btn-assign-trigger"><i class="fa fa-pencil"></i> Change User Role</button>';
        if (hasSpecialRoles && u.primary_role !== 'Super Admin') {
            html += '  <button onclick="revokeAllPermissions(' + u.login_id + ')" class="btn-dash-back" style="background:#fee2e2; color:#dc2626; border:1px solid #fca5a5; width:100%; margin-top:8px; height:38px; justify-content:center; font-size:12.5px;"><i class="fa fa-user-times"></i> Revoke Role (Reset to Member)</button>';
        }
        html += '</div>';
    });
    html += '</div>';

    $('#users-list-container').html(html);

    // Update Summary Metric Counters
    $('#metric-total-users').text(users.length);
    $('#metric-group-admins').text(groupAdmins);
    $('#metric-att-masters').text(attMasters);
    $('#metric-super-admins').text(superAdmins);
}

function openAssignModal(loginId) {
    var user = usersMap[loginId];
    if (!user) return;

    $('#modal-target-login-id').val(user.login_id);
    $('#modal-title-name').html('<i class="fa fa-user-plus" style="margin-right:8px; color:#60a5fa;"></i> Assign Role to: ' + escapeHtml(user.name));
    
    // Default selections
    $('#modal-role-select').val("0");
    $('#modal-group-select').val("0");

    // Pre-select existing assignment if available
    if (user.assignments && user.assignments.length > 0) {
        var firstValid = user.assignments.find(a => parseInt(a.role_id) !== 4);
        if (firstValid) {
            $('#modal-role-select').val(firstValid.role_id);
            $('#modal-group-select').val(firstValid.group_id);
        }
    }

    $('#assignRoleModal').modal('show');
}

function submitRoleAssignment() {
    var targetId = $('#modal-target-login-id').val();
    var selectedRole = $('#modal-role-select').val();
    var selectedGroup = $('#modal-group-select').val();

    $.ajax({
        type: 'POST',
        url: 'api/role_management.php',
        data: {
            action: 'save_assignment',
            target_login_id: targetId,
            role_id: selectedRole,
            group_id: selectedGroup
        },
        success: function(res) {
            var d;
            if (typeof res === 'object') {
                d = res;
            } else {
                try {
                    var str = String(res);
                    var sIdx = str.indexOf('{');
                    var eIdx = str.lastIndexOf('}');
                    if (sIdx !== -1 && eIdx !== -1) {
                        d = JSON.parse(str.substring(sIdx, eIdx + 1));
                    } else {
                        d = JSON.parse(str);
                    }
                } catch(e) {
                    console.error("Server raw response error:", res);
                    swal("Error!", "Failed to save assignment.", "error");
                    return;
                }
            }

            if (d && d.status === 'success') {
                swal("Success!", d.message || "Role updated successfully.", "success");
                $('#assignRoleModal').modal('hide');
                loadUsers();
            } else {
                swal("Error!", (d ? (d.message || d.Message) : null) || "Failed to save assignment.", "error");
            }
        },
        error: function(xhr, status, error) {
            console.error("AJAX error:", xhr.responseText);
            var errMsg = "Failed to save assignment.";
            try {
                var str = xhr.responseText;
                var sIdx = str.indexOf('{');
                var eIdx = str.lastIndexOf('}');
                if (sIdx !== -1 && eIdx !== -1) {
                    var errObj = JSON.parse(str.substring(sIdx, eIdx + 1));
                    errMsg = errObj.message || errObj.Message || errMsg;
                }
            } catch(e) {}
            swal("Error!", errMsg, "error");
        }
    });
}

function revokeAllPermissions(loginId) {
    swal({
        title: "Reset to Normal Member?",
        text: "Are you sure you want to remove all special permissions for this user? They will revert to a normal Member.",
        type: "warning",
        showCancelButton: true,
        confirmButtonColor: "#dc2626",
        confirmButtonText: "Yes, reset to Member",
        closeOnConfirm: true
    }, function() {
        $.post('api/role_management.php', {
            action: 'save_assignment',
            target_login_id: loginId,
            role_ids: '',
            group_ids: ''
        }, function(res) {
            $('#assignRoleModal').modal('hide');
            loadUsers();
            swal("Reset!", "User has been converted to a normal Member.", "success");
        });
    });
}

function revokeAllPermissionsFromModal() {
    var loginId = $('#modal-target-login-id').val();
    if (loginId) {
        revokeAllPermissions(loginId);
    }
}

function deleteAssignment(id) {
    swal({
        title: "Remove Permission?",
        text: "Are you sure you want to revoke this role assignment?",
        type: "warning",
        showCancelButton: true,
        confirmButtonColor: "#ef4444",
        confirmButtonText: "Yes, remove it!",
        closeOnConfirm: true
    }, function() {
        $.post('api/role_management.php', { action: 'delete_assignment', id: id }, function(res) {
            loadUsers();
        });
    });
}

function escapeHtml(text) {
    if (!text) return '';
    return text.replace(/'/g, "\\'").replace(/"/g, "&quot;");
}
</script>

</body>
</html>
