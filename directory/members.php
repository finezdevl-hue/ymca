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
session_write_close();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="description" content="YMCA Members - Manage all members">
    <title>Members | YMCA</title>

    <link href="../css/bootstrap.min.css" rel="stylesheet">
    <link href="../font-awesome/css/font-awesome.css" rel="stylesheet">
    <link href="../css/animate.css" rel="stylesheet">
    <link href="../css/style.css" rel="stylesheet">
    <link href="../css/plugins/sweetalert/sweetalert.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/cropperjs@1.5.13/dist/cropper.min.css">
    <link href="../image_upload/members/upload.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">

    <script src="https://cdnjs.cloudflare.com/ajax/libs/jquery/3.6.0/jquery.min.js"></script>
    <script src="../js/plugins/sweetalert/sweetalert.min.js"></script>
    <script src="../app_js/sweetalert-finez.js"></script>

    <style>
        /* ===== MEMBERS PAGE — MODERN REDESIGN ===== */
        *, *::before, *::after { box-sizing: border-box; }
        body, #wrapper { font-family: 'Inter','Segoe UI',sans-serif !important; background: #f0f4ff !important; }

        /* ---- Top Bar ---- */
        .mem-topbar {
            background: #fff;
            border-bottom: 1px solid #e8edf5;
            padding: 0 28px;
            height: 62px;
            display: flex; align-items: center; justify-content: space-between;
            box-shadow: 0 1px 6px rgba(59,130,246,0.06);
            position: sticky; top: 0; z-index: 100;
        }
        .mem-topbar-left { display: flex; align-items: center; gap: 14px; }
        .mem-hamburger {
            width: 38px; height: 38px;
            background: linear-gradient(135deg,#3b82f6,#6366f1);
            border: none; border-radius: 10px; color: #fff;
            font-size: 15px; cursor: pointer;
            display: flex; align-items: center; justify-content: center;
            transition: opacity 0.2s;
        }
        .mem-hamburger:hover { opacity: 0.85; }
        .mem-topbar-title { font-size: 17px; font-weight: 700; color: #1e293b; }
        .mem-topbar-title span { color: #3b82f6; }
        .mem-logout {
            display: inline-flex; align-items: center; gap: 7px;
            padding: 8px 18px; background: #fff;
            border: 1.5px solid #e8edf5; border-radius: 10px;
            color: #64748b; font-size: 13.5px; font-weight: 500;
            text-decoration: none; transition: all 0.18s;
        }
        .mem-logout:hover { border-color: #3b82f6; color: #3b82f6; text-decoration: none; }

        /* ---- Control Bar ---- */
        .mem-control-bar {
            background: #fff;
            border-bottom: 1px solid #f1f5f9;
            padding: 16px 28px;
            display: flex; align-items: center; gap: 14px; flex-wrap: wrap;
        }
        .mem-page-title {
            font-size: 20px; font-weight: 800; color: #1e293b;
            display: flex; align-items: center; gap: 10px; margin: 0; white-space: nowrap;
        }
        .mem-page-title i {
            width: 38px; height: 38px;
            background: linear-gradient(135deg,#3b82f6,#6366f1);
            border-radius: 10px; display: inline-flex;
            align-items: center; justify-content: center;
            color: #fff; font-size: 16px;
        }
        .mem-search-wrap {
            display: flex; align-items: center; gap: 0;
            background: #f8faff; border: 1.5px solid #c7d7f5;
            border-radius: 10px; overflow: hidden;
            flex: 1; max-width: 380px; min-width: 200px;
            transition: border-color 0.2s, box-shadow 0.2s;
        }
        .mem-search-wrap:focus-within {
            border-color: #3b82f6;
            box-shadow: 0 0 0 3px rgba(59,130,246,0.1);
        }
        .mem-search-icon { padding: 0 12px; color: #94a3b8; font-size: 14px; }
        .mem-search-wrap input {
            border: none; background: transparent;
            padding: 9px 12px 9px 0; font-size: 14px;
            color: #1e293b; outline: none; flex: 1;
            font-family: 'Inter', sans-serif;
        }
        .mem-search-btn {
            padding: 9px 16px; background: linear-gradient(135deg,#3b82f6,#6366f1);
            color: #fff; border: none; font-size: 13px; font-weight: 600;
            cursor: pointer; transition: opacity 0.18s;
            font-family: 'Inter', sans-serif;
        }
        .mem-search-btn:hover { opacity: 0.9; }

        .mem-count-badge {
            display: inline-flex; align-items: center; gap: 6px;
            background: #eff6ff; color: #3b82f6;
            border: 1px solid #bfdbfe; border-radius: 20px;
            padding: 6px 14px; font-size: 13px; font-weight: 600;
            white-space: nowrap;
        }
        .mem-add-btn {
            display: inline-flex; align-items: center; gap: 8px;
            padding: 10px 22px;
            background: linear-gradient(135deg,#3b82f6,#6366f1);
            color: #fff; border: none; border-radius: 10px;
            font-size: 14px; font-weight: 600; cursor: pointer;
            transition: all 0.18s; white-space: nowrap;
            box-shadow: 0 3px 12px rgba(59,130,246,0.3);
            margin-left: auto;
        }
        .mem-add-btn:hover { transform: translateY(-1px); box-shadow: 0 5px 18px rgba(59,130,246,0.4); }

        /* ---- Member Grid ---- */
        .mem-main { padding: 24px 28px; }
        .mem-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(230px, 1fr));
            gap: 18px;
        }

        /* ---- Member Card ---- */
        .mem-card {
            background: #fff;
            border-radius: 18px;
            border: 1px solid #e8edf5;
            overflow: hidden;
            box-shadow: 0 2px 10px rgba(0,0,0,0.05);
            transition: all 0.22s ease;
            display: flex; flex-direction: column;
        }
        .mem-card:hover {
            transform: translateY(-4px);
            box-shadow: 0 12px 32px rgba(59,130,246,0.14);
            border-color: #bfdbfe;
        }
        .mem-card-top {
            padding: 22px 18px 14px;
            display: flex; flex-direction: column; align-items: center;
            gap: 12px; cursor: pointer; flex: 1;
            text-decoration: none;
        }
        .mem-card-top:hover { text-decoration: none; }
        .mem-avatar-wrap { position: relative; }
        .mem-avatar {
            width: 76px; height: 76px; border-radius: 50%;
            object-fit: cover;
            border: 3px solid #e8edf5;
            transition: border-color 0.2s;
        }
        .mem-card:hover .mem-avatar { border-color: #3b82f6; }
        .mem-status-dot {
            position: absolute; bottom: 3px; right: 3px;
            width: 14px; height: 14px; border-radius: 50%;
            border: 2px solid #fff;
            background: #10b981;
        }
        .mem-status-dot.inactive { background: #f87171; }
        .mem-name {
            font-size: 14px; font-weight: 700;
            color: #1e293b; text-align: center;
            line-height: 1.35; margin: 0;
        }
        .mem-address {
            font-size: 12px; color: #94a3b8;
            text-align: center; line-height: 1.5;
            margin: 0;
        }

        /* Contact icon buttons */
        .mem-contact-row {
            display: flex; justify-content: center; gap: 8px;
            padding: 0 16px 14px;
        }
        .mem-icon-btn {
            width: 34px; height: 34px; border-radius: 50%;
            border: 1.5px solid #e8edf5;
            background: #f8faff; color: #64748b;
            display: flex; align-items: center; justify-content: center;
            font-size: 14px; cursor: pointer;
            transition: all 0.18s; text-decoration: none;
        }
        .mem-icon-btn:hover { text-decoration: none; }
        .mem-icon-btn.phone:hover  { background: #eff6ff; border-color: #3b82f6; color: #3b82f6; }
        .mem-icon-btn.email:hover  { background: #fef9c3; border-color: #ca8a04; color: #ca8a04; }
        .mem-icon-btn.whatsapp:hover { background: #dcfce7; border-color: #16a34a; color: #16a34a; }
        .mem-icon-btn.view:hover   { background: #f5f3ff; border-color: #8b5cf6; color: #8b5cf6; }

        .guest-tag-pill {
            background: linear-gradient(135deg, #fff7ed 0%, #ffedd5 100%);
            color: #c2410c;
            border: 1px solid rgba(249, 115, 22, 0.3);
            box-shadow: 0 2px 6px rgba(249, 115, 22, 0.12);
            font-size: 10px;
            font-weight: 800;
            padding: 3px 10px 3px 8px;
            border-radius: 20px;
            letter-spacing: 0.6px;
            display: inline-flex;
            align-items: center;
            gap: 4px;
            vertical-align: middle;
            margin-left: 6px;
            text-transform: uppercase;
        }

        /* Action footer */
        .mem-card-footer {
            border-top: 1px solid #f1f5f9;
            display: flex;
        }
        .mem-action-btn {
            flex: 1; padding: 10px 6px;
            background: transparent; border: none;
            color: #64748b; font-size: 12px; font-weight: 600;
            cursor: pointer; display: flex;
            align-items: center; justify-content: center;
            gap: 5px; transition: all 0.15s;
            font-family: 'Inter', sans-serif;
        }
        .mem-action-btn + .mem-action-btn { border-left: 1px solid #f1f5f9; }
        .mem-action-btn:hover { background: #f8faff; color: #3b82f6; }
        .mem-action-btn.edit:hover  { color: #3b82f6; background: #eff6ff; }
        .mem-action-btn.group:hover { color: #8b5cf6; background: #f5f3ff; }
        .mem-action-btn.login:hover { color: #10b981; background: #f0fdf4; }

        /* ---- Pagination ---- */
        #table_members .pagination { margin: 28px 0 8px; justify-content: center; }
        #table_members .pagination li a,
        #table_members .pagination li span {
            border-radius: 8px !important; margin: 0 2px;
            border: 1.5px solid #e8edf5; color: #475569;
            font-size: 13px; font-weight: 500; padding: 7px 13px;
            transition: all 0.15s;
        }
        #table_members .pagination li.active a,
        #table_members .pagination li.active span {
            background: linear-gradient(135deg,#3b82f6,#6366f1) !important;
            border-color: transparent !important; color: #fff !important;
        }
        #table_members .pagination li a:hover { background: #f0f4ff; color: #3b82f6; border-color: #bfdbfe; }

        /* ---- Empty State ---- */
        .mem-empty {
            text-align: center; padding: 70px 20px; color: #94a3b8; grid-column: 1/-1;
        }
        .mem-empty i { font-size: 52px; margin-bottom: 16px; display: block; color: #dbeafe; }
        .mem-empty p { font-size: 15px; }

        /* ---- Modal Redesign ---- */
        .modal-content {
            border-radius: 20px !important; border: none !important;
            box-shadow: 0 25px 60px rgba(15,23,42,0.18) !important;
            overflow: hidden;
        }
        .modal-header-custom {
            background: linear-gradient(135deg,#1e3a8a,#3b82f6);
            padding: 20px 24px;
            display: flex; align-items: center; justify-content: space-between;
        }
        .modal-header-custom h4 {
            color: #fff; font-size: 17px; font-weight: 700; margin: 0;
            display: flex; align-items: center; gap: 10px;
        }
        .modal-header-custom .modal-close-btn {
            width: 30px; height: 30px; background: rgba(255,255,255,0.15);
            border: none; border-radius: 8px; color: #fff; font-size: 16px;
            cursor: pointer; display: flex; align-items: center; justify-content: center;
            transition: background 0.15s;
        }
        .modal-header-custom .modal-close-btn:hover { background: rgba(255,255,255,0.25); }
        .modal-body { padding: 22px 24px !important; }
        .modal-footer {
            padding: 16px 24px !important; border-top: 1px solid #f1f5f9 !important;
            display: flex; gap: 10px; justify-content: flex-end;
        }

        /* Form fields in modal */
        .modal-body .form-section-title {
            font-size: 11px; font-weight: 700; color: #94a3b8;
            text-transform: uppercase; letter-spacing: 0.08em;
            margin: 16px 0 10px; padding-bottom: 6px;
            border-bottom: 1px solid #f1f5f9;
        }
        .modal-body .form-row-pair {
            display: grid; grid-template-columns: 1fr 1fr; gap: 12px;
            margin-bottom: 12px;
        }
        .modal-body .form-field { display: flex; flex-direction: column; gap: 4px; }
        .modal-body .form-label {
            font-size: 11.5px; font-weight: 600; color: #64748b; margin: 0;
        }
        .modal-body .form-control {
            border: 1.5px solid #e2e8f0 !important;
            border-radius: 10px !important;
            padding: 9px 13px !important; font-size: 13.5px !important;
            color: #1e293b !important; background: #fafbff !important;
            transition: border-color 0.2s, box-shadow 0.2s !important;
            height: auto !important;
        }
        .modal-body .form-control:focus {
            border-color: #3b82f6 !important;
            box-shadow: 0 0 0 3px rgba(59,130,246,0.1) !important;
            background: #fff !important; outline: none !important;
        }
        .modal-body select.form-control {
            appearance: none; -webkit-appearance: none;
            background-image: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='12' height='12' viewBox='0 0 12 12'%3E%3Cpath fill='%236b7280' d='M6 8L1 3h10z'/%3E%3C/svg%3E") !important;
            background-repeat: no-repeat !important;
            background-position: right 12px center !important;
            padding-right: 36px !important;
        }
        .inactive-row { display: flex; align-items: center; gap: 10px; padding: 8px 0; }
        .inactive-row label { font-size: 14px; font-weight: 500; color: #475569; margin: 0; cursor: pointer; }
        .inactive-row input[type="checkbox"] { width: 17px; height: 17px; cursor: pointer; accent-color: #3b82f6; }

        /* Modal buttons */
        .btn-modal-close {
            padding: 9px 20px; border-radius: 10px;
            border: 1.5px solid #e2e8f0; background: #fff;
            color: #64748b; font-size: 13.5px; font-weight: 600;
            cursor: pointer; transition: all 0.15s;
        }
        .btn-modal-close:hover { background: #f8faff; border-color: #94a3b8; }
        .btn-modal-save {
            padding: 9px 22px; border-radius: 10px;
            background: linear-gradient(135deg,#3b82f6,#6366f1);
            color: #fff; border: none; font-size: 13.5px; font-weight: 600;
            cursor: pointer; transition: all 0.18s;
            box-shadow: 0 3px 10px rgba(59,130,246,0.28);
        }
        .btn-modal-save:hover { opacity: 0.9; transform: translateY(-1px); }

        /* Groups modal */
        #groupsModal {
            display: none;
            position: fixed;
            inset: 0;
            background: rgba(15,23,42,0.5);
            backdrop-filter: blur(8px);
            z-index: 9999;
            align-items: center;
            justify-content: center;
            opacity: 0;
            transition: opacity 0.3s ease;
        }
        #groupsModal.modal-open {
            display: flex;
            opacity: 1;
        }
        #group_container {
            background: rgba(255,255,255,0.85);
            border-radius: 16px;
            padding: 24px;
            max-width: 420px;
            width: 90%;
            box-shadow: 0 12px 30px rgba(0,0,0,0.15);
            animation: modalEnter 0.4s ease-out;
            overflow: hidden;
        }
        @keyframes modalEnter {
            from { transform: translateY(-20px); opacity: 0; }
            to { transform: translateY(0); opacity: 1; }
        }

        /* Modal header gradient banner */
        #group_container .modal-header-custom {
            background: linear-gradient(135deg, #3b82f6 0%, #6366f1 100%);
            border-radius: 12px 12px 0 0;
            padding: 18px 20px;
            display: flex;
            align-items: center;
            justify-content: space-between;
            margin: -24px -24px 20px -24px;
        }
        #group_container .modal-title {
            font-size: 1.1rem;
            font-weight: 700;
            color: #fff !important;
            background: none !important;
            -webkit-background-clip: unset !important;
            -webkit-text-fill-color: unset !important;
            margin: 0;
        }
        #group_container .modal-close-btn {
            background: rgba(255,255,255,0.2);
            border: none;
            border-radius: 8px;
            color: #fff;
            font-size: 1.2rem;
            width: 32px; height: 32px;
            display: flex; align-items: center; justify-content: center;
            cursor: pointer;
            transition: background 0.2s;
        }
        #group_container .modal-close-btn:hover { background: rgba(255,255,255,0.35); }

        /* Custom checkboxes */
        .group-check-item {
            margin-bottom: 10px;
        }
        .group-check-label {
            display: flex;
            align-items: center;
            gap: 12px;
            cursor: pointer;
            padding: 10px 14px;
            border-radius: 10px;
            border: 1.5px solid #e2e8f0;
            background: #f8faff;
            transition: all 0.18s;
            font-size: 14px;
            font-weight: 500;
            color: #334155;
            user-select: none;
        }
        .group-check-label:hover {
            border-color: #3b82f6;
            background: #eff6ff;
        }
        .group-check-input {
            display: none;
        }
        .group-check-box {
            width: 20px; height: 20px;
            border: 2px solid #cbd5e1;
            border-radius: 6px;
            background: #fff;
            flex-shrink: 0;
            display: flex; align-items: center; justify-content: center;
            transition: all 0.18s;
            position: relative;
        }
        .group-check-input:checked + .group-check-box {
            background: linear-gradient(135deg, #3b82f6, #6366f1);
            border-color: #3b82f6;
        }
        .group-check-input:checked + .group-check-box::after {
            content: '';
            display: block;
            width: 5px; height: 9px;
            border: 2px solid #fff;
            border-top: none; border-left: none;
            transform: rotate(45deg) translate(-1px, -1px);
        }
        .group-check-input:checked ~ .group-check-name {
            color: #1e40af;
            font-weight: 600;
        }
        .group-check-name { flex: 1; }

        /* Save button footer */
        .modal-footer-custom {
            margin-top: 20px;
            padding-top: 16px;
            border-top: 1px solid #e2e8f0;
        }
        .save-button {
            padding: 12px 0;
            border-radius: 10px;
            background: linear-gradient(135deg, #3b82f6, #6366f1);
            color: #fff;
            border: none;
            font-size: 14.5px;
            font-weight: 600;
            cursor: pointer;
            width: 100%;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 8px;
            box-shadow: 0 4px 14px rgba(59,130,246,0.4);
            transition: transform 0.2s, box-shadow 0.2s;
            letter-spacing: 0.4px;
        }
        .save-button:hover {
            transform: translateY(-2px);
            box-shadow: 0 6px 20px rgba(59,130,246,0.5);
        }
        .save-button:active { transform: translateY(0); }

        /* Responsive */
        @media (max-width: 600px) {
            .mem-main { padding: 14px; }
            .mem-grid { grid-template-columns: repeat(auto-fill, minmax(160px,1fr)); gap: 12px; }
            .mem-control-bar { padding: 14px 16px; }
        }
    </style>

    <script>
        var globalMembers = [];
        
        function formatAddress(street, city, pincode, country) {
            let parts = [];
            if (street && street !== '0' && street.trim() !== '') parts.push(street.trim());
            if (city   && city   !== '0' && city.trim()   !== '') parts.push(city.trim());
            return parts.length ? parts.join(', ') : '';
        }

        $(document).ready(function() {
            loadMenu();
            loadData(1);
        });

        function searchMembers(){
            loadData(1);
        }

        // Search on Enter key
        $(document).on('keydown','#txt_search',function(e){
            if(e.key==='Enter'){ e.preventDefault(); searchMembers(); }
        });

        function loadData(page) {
            $('#hdn_current_page').val(page);
            $.ajax({
                type: "POST",
                url: "api/members.php",
                data: { action:'load_members_data', page:page, val:$('#txt_search').val() },
                success: function(data) {
                    var obj   = typeof data === 'string' ? jQuery.parseJSON(data) : data;
                    var total = obj[0].total_rows;
                    var members = obj[1];
                    globalMembers = members;

                    $('#mem-count-badge').html('<i class="fa fa-users"></i> ' + total + ' Members');

                    var htm = '<div class="mem-grid">';

                    if (members.length === 0) {
                          htm += '<div class="mem-empty"><i class="fa fa-user-times"></i><p>No members found.</p></div>';
                    }

                    for (var i = 0; i < members.length; i++) {
                        var m = members[i];
                        var img = m.img;
                        var imgSrc = (img && img != 0 && img != '0' && typeof img === 'string' && img.trim() !== '')
                            ? '../image_upload/members/thumbnails/' + img
                            : '../img/customer.png';

                        var fullName = [m.first_name, m.middle_name, m.last_name]
                            .filter(function(p){ return p && p.trim(); }).join(' ');
                        var addr = formatAddress(m.p_street, m.p_city, m.p_pincode, m.p_country);
                        var isInactive = (m.inactive == 1);

                        var isGuest = (parseInt(m.member_type, 10) === 1);
                        var guestBadge = isGuest ? '<span class="guest-tag-pill"><i class="fa fa-star" style="color:#f59e0b; font-size:9px;"></i> GUEST</span>' : '';
                        var memberCode = isGuest ? '' : 'YMCA-BCP-' + (1000 + parseInt(m.id, 10));
                        var codeBadge = isGuest ? '' : '<span class="badge" style="background:#f8fafc; color:#475569; border:1px solid #e2e8f0; font-size:10px; font-weight:800; padding:3px 8px; border-radius:12px; font-family:inherit; letter-spacing:0.3px; display:inline-flex; align-items:center; gap:4px;"><i class="fa fa-id-card-o" style="color:#6366f1;"></i> ' + memberCode + '</span>';

                        htm += '<div class="mem-card">';

                        // Card top — clickable to view details
                        htm += '<a class="mem-card-top" onclick="navigateSeeDetails(\''+m.id+'\')" href="javascript:void(0)">';
                        htm +=   '<div class="mem-avatar-wrap">';
                        htm +=     '<img src="'+imgSrc+'" class="mem-avatar" onerror="this.src=\'../img/customer.png\'">';
                        htm +=     '<span class="mem-status-dot'+(isInactive?' inactive':'')+'"></span>';
                        htm +=   '</div>';
                        htm +=   '<p class="mem-name">'+fullName+'</p>';
                        htm +=   '<div style="display:flex; align-items:center; justify-content:center; gap:6px; flex-wrap:wrap; margin-top:4px; margin-bottom:4px;">'+codeBadge + guestBadge+'</div>';
                        if (addr) htm += '<p class="mem-address">'+addr+'</p>';
                        htm += '</a>';

                        // Contact icon row
                        htm += '<div class="mem-contact-row">';
                        htm +=   '<a onclick="window.location.href=\'tel:'+m.phone+'\'" class="mem-icon-btn phone" title="Call"><i class="fa fa-phone"></i></a>';
                        htm +=   '<a href="mailto:'+m.email+'" class="mem-icon-btn email" title="Email"><i class="fa fa-envelope"></i></a>';
                        htm +=   '<a onclick="window.open(\'https://wa.me/'+m.whtsapp+'\',\'_blank\')" class="mem-icon-btn whatsapp" title="WhatsApp"><i class="fa fa-whatsapp"></i></a>';
                        htm +=   '<a onclick="navigateSeeDetails(\''+m.id+'\')" class="mem-icon-btn view" title="View Details"><i class="fa fa-eye"></i></a>';
                        htm += '</div>';

                        // Action footer
                        htm += '<div class="mem-card-footer" style="flex-wrap:wrap; gap:4px;">';
                        htm +=   '<button class="mem-action-btn edit" onclick="editMemberByIndex('+i+')"><i class="fa fa-pencil"></i> Edit</button>';
                        htm +=   '<button class="mem-action-btn group" onclick="showgroupsModal('+m.id+')"><i class="fa fa-users"></i> Group</button>';
                        htm +=   '<button class="mem-action-btn login" onclick="createLoginByIndex('+i+')"><i class="fa fa-key"></i> Login</button>';
                        htm +=   '<button class="mem-action-btn" style="background:'+(isGuest?'#fff7ed':'#f1f5f9')+'; color:'+(isGuest?'#c2410c':'#475569')+'; border:1px solid '+(isGuest?'#ffedd5':'#cbd5e1')+';" onclick="toggleGuestStatusDesktop('+m.id+','+(isGuest?1:0)+')"><i class="fa '+(isGuest?'fa-user-times':'fa-user-plus')+'"></i> '+(isGuest?'Guest':'Make Guest')+'</button>';
                        htm += '</div>';

                        htm += '</div>'; // .mem-card
                    }
                    htm += '</div>'; // .mem-grid

                    // Pagination
                    $('#table_members').html(htm);
                    var pgHtml = paginate(total, page);
                    $('#table_members').append(pgHtml);
                },
                error: function(xhr, status, error) { console.log('AJAX error:', status, error); }
            });
            load_blood_groups();
            load_groups();
        }

        function paginate(totalRows, currentPage) {
            var rowsPerPage = 8;
            var totalPages = Math.ceil(totalRows / rowsPerPage);
            if (totalPages <= 1) return '';

            var html = '<div class="pagination-wrapper" style="text-align:center; margin-top:24px; margin-bottom:20px; display:flex; justify-content:center; align-items:center; gap:6px;">';
            if (currentPage > 1) {
                html += '<button type="button" class="btn btn-sm btn-default" onclick="loadData(' + (currentPage - 1) + ')" style="border-radius:8px; font-weight:700; padding:6px 14px;"><i class="fa fa-chevron-left"></i> Previous</button>';
            }
            for (var p = 1; p <= totalPages; p++) {
                var activeStyle = (p === currentPage) ? 'background:#3b82f6; color:#fff; font-weight:800; border-color:#3b82f6;' : 'background:#fff; color:#475569; font-weight:700; border-color:#cbd5e1;';
                html += '<button type="button" class="btn btn-sm" onclick="loadData(' + p + ')" style="border-radius:8px; padding:6px 12px; ' + activeStyle + '">' + p + '</button>';
            }
            if (currentPage < totalPages) {
                html += '<button type="button" class="btn btn-sm btn-default" onclick="loadData(' + (currentPage + 1) + ')" style="border-radius:8px; font-weight:700; padding:6px 14px;">Next <i class="fa fa-chevron-right"></i></button>';
            }
            html += '</div>';
            return html;
        }
    </script>

    <script>
        function toggleGuestStatusDesktop(memberId, currentStatus) {
            let newStatus = currentStatus === 1 ? 0 : 1;
            let actionName = newStatus === 1 ? "mark as Guest Member" : "convert to Regular Member";
            
            if (confirm("Are you sure you want to " + actionName + "?")) {
                $.post('api/members.php', { action: 'toggle_guest_status', id: memberId, member_type: newStatus }, function(res) {
                    loadData($('#hdn_current_page').val());
                }).fail(function() {
                    alert('Failed to update member status.');
                });
            }
        }

        function load_groups(){
                $.ajax({
                type:"POST", url:"api/members.php",
                data:{action:'load_groups'},
                success:function(data){ $('#group_content').html(data); },
                error:function(xhr,status,error){ console.error('AJAX error:',status,error); }
            });
        }

        function popuCreateLogin(first_name,middle_name,last_name,email){
            var cleanEmail = (email && email !== 'null' && email !== 'undefined') ? $.trim(email) : '';
            if (!cleanEmail) {
                alertwarning("Please add an email address in the member's profile first before creating a login.");
                return;
            }
            var fullName = (first_name + " " + (middle_name ? middle_name + " " : "") + last_name).replace(/\s+/g, ' ').trim();
            $("#name").val(fullName);
            $("#email").val(cleanEmail);
            $('#loginModal').modal('show');
        }

        function saveLogin(){
            swal({title:"Are you sure?",text:"Do you want to save this data!",type:"warning",showCancelButton:true,confirmButtonColor:"#DD6B55",confirmButtonText:"Yes,Save!",cancelButtonText:"Cancel",closeOnConfirm:false,closeOnCancel:true},
            function(isConfirm){
                if(isConfirm){
                    if($.trim($('#email').val())==""){alertwarning("Please add an email address in the member's profile first before creating a login.");return;}
                    if($('#password').val()!==$('#confirmpassword').val()){alertwarning('Passwords do not match!');}
                    else{
                        var data={action:'save_login',name:$('#name').val(),email:$('#email').val(),password:$('#password').val(),confirmPassword:$('#confirmpassword').val()};
                        $.ajax({type:"POST",url:"api/members.php",data:data,
                            success:function(response){closePopupLogin();alertsuccess('Saved Successfully');},
                            error:function(xhr,status){let m={};try{m=JSON.parse(xhr.responseText);}catch(e){m.Message="Something went wrong!";}alerterror(m,xhr);}
                        });
                    }
                }
            });
        }

        function closePopupLogin(){$('#login_form')[0].reset();$('#loginModal').modal('toggle');}

        let member_id=null;
        function showgroupsModal(id){fetchGrouopDetails(id);member_id=id;document.getElementById('groupsModal').classList.add('modal-open');}

        function fetchGrouopDetails(id){
            $.ajax({type:"POST",url:"api/members.php",data:{action:'fetch_group_details',id:id},
                success:function(data){var obj=jQuery.parseJSON(data);for(var i=0;i<obj.length;i++){document.getElementById(obj[i].group_id).checked=true;}},
                error:function(xhr,status,error){console.log('AJAX error:',status,error);}
            });
        }

        function closegroupsModal(){document.getElementById('groupsModal').classList.remove('modal-open');$("input[name='group']:checkbox").prop('checked',false);}

        function addMemberToGroups(){
            var group_ids=[];$("input[type=checkbox]:checked").each(function(){group_ids.push(this.value);});
            load_overlay();
            $.ajax({type:"POST",url:"api/members.php",data:{action:'add_member_to_groups',id:member_id,group_ids:group_ids},
                success:function(data){close_overlay();closegroupsModal();loadData($('#hdn_current_page').val());},
                error:function(xhr,status,error){console.log('AJAX error:',status,error);}
            });
        }

        function navigateSeeDetails(id){
            $.post("member_details.php",{'id':id}).done(function(){window.location.href="member_details.php";});
        }

        function editMemberByIndex(index) {
            var m = globalMembers[index];
            if (!m) return;
            fetchmemberDetails(
                m.id, m.first_name, m.middle_name, m.last_name,
                m.father_name, m.mother_name, m.dob, m.blood_group,
                m.email, m.phone, m.whtsapp, m.p_street,
                m.p_city, m.p_pincode, m.p_country, m.img, m.inactive
            );
        }

        function createLoginByIndex(index) {
            var m = globalMembers[index];
            if (!m) return;
            popuCreateLogin(m.first_name, m.middle_name, m.last_name, m.email);
        }

        function fetchmemberDetails(id,first_name,middle_name,last_name,father_name,mother_name,dob,blood_group,email,phone,whtsapp,p_street,p_city,p_pincode,p_country,img,inactive){
            $("#txt_first_name").val(first_name);$("#txt_middle_name").val(middle_name);$("#txt_last_name").val(last_name);
            $("#txt_father_name").val(father_name);$("#txt_mother_name").val(mother_name);$("#dob").val(dob);
            $("#selected_blood_group").val(blood_group);$("#txt_phone").val(phone);$("#txt_email").val(email);
            $("#txt_whtsapp").val(whtsapp);$("#txt_p_street").val(p_street);$("#txt_p_city").val(p_city);
            $("#txt_p_pincode").val(p_pincode);$("#txt_p_country").val(p_country);
            $("#inactive").prop("checked",inactive==1);$("#hdn_file_upload").val(img);$("#hdn_id").val(id);
            $('#clientModal').modal('show');
        }

        function popupMemberDetails(id){$("#hdn_id").val(id);$('#clientModal').modal('show');}

        function saveMembers(){
            if($('#txt_first_name').val()==""){alertinfo("First Name cannot be empty.");return;}
            if($('#txt_last_name').val()==""){alertinfo("Last Name cannot be empty.");return;}
            if($('#txt_phone').val()==""){alertinfo("Phone cannot be empty.");return;}
            swal({title:"Are you sure?",text:"Do you want to save this data!",type:"warning",showCancelButton:true,confirmButtonColor:"#DD6B55",confirmButtonText:"Yes,Save!",cancelButtonText:"Cancel",closeOnConfirm:false,closeOnCancel:true},
            function(isConfirm){
                if(isConfirm){
                    var data={action:'save_members',first_name:$('#txt_first_name').val(),middle_name:$('#txt_middle_name').val(),last_name:$('#txt_last_name').val(),father_name:$('#txt_father_name').val(),mother_name:$('#txt_mother_name').val(),dob:$('#dob').val(),blood_group:$("#selected_blood_group").val(),phone:$('#txt_phone').val(),whtsapp:$('#txt_whtsapp').val(),email:$('#txt_email').val(),p_street:$('#txt_p_street').val(),p_city:$('#txt_p_city').val(),p_pincode:$('#txt_p_pincode').val(),p_country:$('#txt_p_country').val(),inactive:$('#inactive').is(':checked')?1:0,id:$("#hdn_id").val(),img:$("#hdn_file_upload").val()};
                    $.ajax({type:"POST",url:"api/members.php",data:data,
                        success:function(response){closepopupMemberDetails();alertsuccess('Saved Successfully');loadData($('#hdn_current_page').val());},
                        error:function(xhr,status){var m=JSON.parse(xhr.responseText);alerterror(m,xhr);$('#member_form')[0].reset();$("#photoInput").val('');}
                    });
                }
            });
        }

        function closepopupMemberDetails(){$('#member_form')[0].reset();$('#clientModal').modal('toggle');}

        function load_blood_groups(){
            $.ajax({type:"POST",url:"api/members.php",data:{action:'load_blood_groups'},
                success:function(data){
                    var obj=jQuery.parseJSON(data);
                    var htm="<select id='selected_blood_group' class='form-control'><option value='0'>Select Blood Group</option>";
                    for(var i=0;i<obj[0].length;i++){htm+="<option value='"+obj[0][i].id+"'>"+obj[0][i].name+"</option>";}
                    htm+="</select>";
                    $('#blood_groups').html(htm);
                },
                error:function(xhr,status,error){console.error('AJAX error:',status,error);}
            });
        }
    </script>

    <script src="../app_pagination/pagination.js"></script>
    <script src="../app_menu/menu.js"></script>
</head>

<body>
<input type="hidden" id="hdn_current_page" value="0">
<input type="hidden" id="hdn_id" value="0">
<input type="hidden" id="hdn_file_upload" name="image" value="0">

<div id="wrapper">

    <!-- Sidebar -->
    <nav class="navbar-default navbar-static-side" role="navigation">
        <div class="dropdown profile-element">
            <center>
                <span><img alt="image" class="img-circle" src="../img/customer.png" style="padding-top:20px;"/></span>
                <span class="clear"><span class="block m-t-xs"><strong class="font-bold"><?php echo $_SESSION['name']; ?></strong></span></span>
            </center>
        </div>
        <div class="sidebar-collapse" id="divMenuContainer"></div>
    </nav>

    <!-- Main -->
    <div id="page-wrapper" style="background:#f0f4ff; padding:0; min-height:100vh;">

        <!-- Top Navbar -->
        <div class="mem-topbar">
            <div class="mem-topbar-left">
                <a class="navbar-minimalize minimalize-styl-2 mem-hamburger" href="#"><i class="fa fa-bars"></i></a>
                <span class="mem-topbar-title">YMCA <span>Admin</span></span>
            </div>
            <a href="../app_login_manager/logout.php" class="mem-logout">
                <i class="fa fa-sign-out"></i> Log out
            </a>
        </div>

        <!-- Control Bar -->
        <div class="mem-control-bar">
            <h1 class="mem-page-title">
                <i class="fa fa-users"></i> Members
            </h1>
            <div class="mem-search-wrap">
                <span class="mem-search-icon"><i class="fa fa-search"></i></span>
                <input type="text" id="txt_search" placeholder="Search by name, phone…">
                <button class="mem-search-btn" onclick="searchMembers()">Search</button>
            </div>
            <span class="mem-count-badge" id="mem-count-badge">
                <i class="fa fa-users"></i> Loading…
            </span>
            <button class="mem-add-btn" onclick="popupMemberDetails('0')">
                <i class="fa fa-user-plus"></i> Add Member
            </button>
        </div>

        <!-- Members Grid -->
        <div class="mem-main">
            <div id="table_members">
                <!-- Cards injected via AJAX -->
                <div class="mem-grid">
                    <div class="mem-empty">
                        <i class="fa fa-spinner fa-spin" style="color:#3b82f6;"></i>
                        <p>Loading members…</p>
                    </div>
                </div>
            </div>
        </div>

        <!-- ===== ADD / EDIT MEMBER MODAL ===== -->
        <div class="modal inmodal" id="clientModal" tabindex="-1" role="dialog" aria-hidden="true">
            <div class="modal-dialog" style="max-width:560px;">
                <div class="modal-content">
                    <form method="POST" id="member_form" enctype="multipart/form-data">
                        <!-- Header -->
                        <div class="modal-header-custom">
                            <h4><i class="fa fa-user-circle"></i> Member Details</h4>
                            <button type="button" class="modal-close-btn" onclick="closepopupMemberDetails()">&times;</button>
                        </div>

                        <div class="modal-body">
                            <!-- Name -->
                            <div class="form-section-title">Personal Information</div>
                            <div class="form-row-pair">
                                <div class="form-field">
                                    <label class="form-label">First Name *</label>
                                    <input type="text" id="txt_first_name" name="first_name" placeholder="First Name" class="form-control" oninput="nameValidation()">
                                </div>
                                <div class="form-field">
                                    <label class="form-label">Middle Name</label>
                                    <input type="text" id="txt_middle_name" name="middle_name" placeholder="Middle Name" class="form-control" oninput="nameValidation()">
                                </div>
                            </div>
                            <div class="form-row-pair">
                                <div class="form-field">
                                    <label class="form-label">Last Name *</label>
                                    <input type="text" id="txt_last_name" name="last_name" placeholder="Last Name" class="form-control" oninput="nameValidation()">
                                </div>
                                <div class="form-field">
                                    <label class="form-label">Father's Name</label>
                                    <input type="text" id="txt_father_name" name="father_name" placeholder="Father's Name" class="form-control" oninput="nameValidation()">
                                </div>
                            </div>
                            <div class="form-row-pair">
                                <div class="form-field">
                                    <label class="form-label">Mother's Name</label>
                                    <input type="text" id="txt_mother_name" name="mother_name" placeholder="Mother's Name" class="form-control" oninput="nameValidation()">
                                </div>
                                <div class="form-field">
                                    <label class="form-label">Date of Birth</label>
                                    <input type="date" id="dob" name="dob" class="form-control">
                                </div>
                            </div>
                            <div class="form-row-pair">
                                <div class="form-field">
                                    <label class="form-label">Blood Group</label>
                                    <div id="blood_groups">
                                        <select id="selected_blood_group" class="form-control"><option value="0">Loading…</option></select>
                                    </div>
                                </div>
                                <div class="form-field">
                                    <label class="form-label">Phone *</label>
                                    <input type="number" id="txt_phone" name="phone" placeholder="Phone Number" class="form-control">
                                </div>
                            </div>

                            <!-- Contact -->
                            <div class="form-section-title">Contact</div>
                            <div class="form-row-pair">
                                <div class="form-field">
                                    <label class="form-label">WhatsApp</label>
                                    <input type="number" id="txt_whtsapp" name="whtsapp" placeholder="WhatsApp Number" class="form-control">
                                </div>
                                <div class="form-field">
                                    <label class="form-label">Email</label>
                                    <input type="text" id="txt_email" name="email" placeholder="Email Address" class="form-control">
                                </div>
                            </div>

                            <!-- Address -->
                            <div class="form-section-title">Permanent Address</div>
                            <div class="form-row-pair">
                                <div class="form-field">
                                    <label class="form-label">Street</label>
                                    <input type="text" id="txt_p_street" name="p_street" placeholder="Street" class="form-control">
                                </div>
                                <div class="form-field">
                                    <label class="form-label">City</label>
                                    <input type="text" id="txt_p_city" name="p_city" placeholder="City" class="form-control">
                                </div>
                            </div>
                            <div class="form-row-pair">
                                <div class="form-field">
                                    <label class="form-label">Pincode</label>
                                    <input type="number" id="txt_p_pincode" name="p_pincode" placeholder="Pincode" class="form-control">
                                </div>
                                <div class="form-field">
                                    <label class="form-label">Country</label>
                                    <input type="text" id="txt_p_country" name="p_country" placeholder="Country" class="form-control">
                                </div>
                            </div>

                            <!-- Status + Photo -->
                            <div class="form-section-title">Status & Photo</div>
                            <div class="inactive-row">
                                <input type="checkbox" id="inactive" name="inactive">
                                <label for="inactive">Mark as Inactive</label>
                            </div>
                        </div>

                        <div class="modal-footer">
                            <input type="file" id="photoInput" onchange="photoInputChange(event);" accept="image/*" style="font-size:13px; color:#64748b;">
                            <button type="button" class="btn-modal-close" onclick="closepopupMemberDetails()">Cancel</button>
                            <button type="button" class="btn-modal-save" onclick="saveMembers()"><i class="fa fa-save"></i> Save</button>
                        </div>

                        <!-- Crop modal (unchanged) -->
                        <div id="cropModal">
                            <div id="modalContent">
                                <input type="button" id="closeModal" onclick="closeModalClicked();" class="closeModal" value="&times;">
                                <img id="modalPreview" alt="Preview">
                                <input type="button" id="cropButton" onclick="cropButtonClicked();" value="Crop and Upload">
                            </div>
                        </div>
                    </form>
                </div>
            </div>
        </div>

        <!-- ===== LOGIN MODAL ===== -->
        <div class="modal inmodal" id="loginModal" tabindex="-1" role="dialog" aria-hidden="true">
            <div class="modal-dialog" style="max-width:420px;">
                <div class="modal-content">
                    <form method="POST" id="login_form">
                        <div class="modal-header-custom">
                            <h4><i class="fa fa-key"></i> Create Login</h4>
                            <button type="button" class="modal-close-btn" onclick="closePopupLogin()">&times;</button>
                        </div>
                        <div class="modal-body">
                            <div class="form-field" style="margin-bottom:12px;">
                                <label class="form-label">Name</label>
                                <input type="text" id="name" name="Name" placeholder="Name" class="form-control" readonly>
                            </div>
                            <div class="form-field" style="margin-bottom:12px;">
                                <label class="form-label">Email</label>
                                <input type="text" id="email" name="Email" placeholder="Email" class="form-control">
                            </div>
                            <div class="form-field" style="margin-bottom:12px;">
                                <label class="form-label">Password</label>
                                <input type="text" id="password" name="password" placeholder="Password" class="form-control">
                            </div>
                            <div class="form-field">
                                <label class="form-label">Confirm Password</label>
                                <input type="text" id="confirmpassword" name="confirmpassword" placeholder="Confirm Password" class="form-control">
                            </div>
                        </div>
                        <div class="modal-footer">
                            <button type="button" class="btn-modal-close" onclick="closePopupLogin()">Cancel</button>
                            <button type="button" class="btn-modal-save" onclick="saveLogin()"><i class="fa fa-save"></i> Save</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>

        <!-- ===== GROUPS MODAL ===== -->
        <div id='groupsModal' class='modal'>
            <div id='group_container'>
                <div class="modal-header-custom">
                    <h4 class="modal-title">Select Group</h4>
                    <button type="button" class="modal-close-btn" onclick="closegroupsModal();">&times;</button>
                </div>
                <div id="group_content"></div>
                <div class="modal-footer-custom">
                    <button type="button" class="save-button" onclick="addMemberToGroups();"><i class="fa fa-check"></i> Save Changes</button>
                </div>
            </div>
        </div>

    </div><!-- end page-wrapper -->
</div><!-- end wrapper -->

<!-- Scripts -->
<script src="../js/jquery-3.1.1.min.js"></script>
<script src="../js/bootstrap.min.js"></script>
<script src="../js/plugins/metisMenu/jquery.metisMenu.js"></script>
<script src="../js/plugins/slimscroll/jquery.slimscroll.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/cropperjs@1.5.13/dist/cropper.min.js"></script>
<script src="../js/inspinia.js"></script>
<script src="../js/plugins/pace/pace.min.js"></script>
<script src="../image_upload/members/image_upload.js"></script>
<script src="../app_js/validation.js"></script>
<script src="../js/loadingoverlay.min.js"></script>

<script>
// Show groupsModal as flex when display:block is set
var gObserver = new MutationObserver(function(muts){
    muts.forEach(function(m){
        var el = document.getElementById('groupsModal');
        if(el && el.style.display === 'block'){
            el.style.display = 'flex';
        }
    });
});
var gEl = document.getElementById('groupsModal');
if(gEl) gObserver.observe(gEl, {attributes:true, attributeFilter:['style']});
</script>

</body>
</html>
