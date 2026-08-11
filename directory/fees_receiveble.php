<?php
session_start();
include '../app_common/enums.php';
include '../app_common/db_connect.php';
include_once '../app_common/auth_helper.php';

if (isset($_GET['member_id'])) {
    $_SESSION['member_id'] = (int)$_GET['member_id'];
} elseif (isset($_POST['member_id'])) {
    $_SESSION['member_id'] = (int)$_POST['member_id'];
}

if(isset($_POST['first_name'])){
    $_SESSION['first_name']=$_POST['first_name'];
}
if(isset($_POST['middle_name'])){
    $_SESSION['middle_name']=$_POST['middle_name'];
}
if(isset($_POST['last_name'])){
    $_SESSION['last_name']=$_POST['last_name'];
}

$member_id = 0;
if (!empty($_SESSION['member_id'])) {
    $member_id = (int)$_SESSION['member_id'];
} elseif (!empty($_SESSION['user_id'])) {
    $member_id = (int)$_SESSION['user_id'];
}
$member_img = '';
$first_name = '';
$middle_name = '';
$last_name = '';

$member_type = 0;
if ($member_id > 0) {
    $res = app_exec_getresult("SELECT first_name, middle_name, last_name, img, member_type FROM tbl_members WHERE id = ?", [$member_id], "i");
    if ($res && $row = $res->fetch_assoc()) {
        $member_img = $row['img'];
        $first_name = $row['first_name'];
        $middle_name = $row['middle_name'];
        $last_name = $row['last_name'];
        $member_type = (int)($row['member_type'] ?? 0);
    }
}
$is_guest_desktop = ($member_type === 1);
?>
<!DOCTYPE html>
<html>

<head>

    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>Fees Receivables</title>

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
        .profile-summary-header {
            background: var(--card-bg, #ffffff);
            border-radius: var(--border-radius-lg, 24px);
            border: 1px solid var(--border-color, #e2e8f0);
            box-shadow: var(--shadow-sm, 0 1px 3px rgba(0,0,0,0.05));
            padding: 24px 30px;
            margin-bottom: 24px;
            display: flex;
            align-items: center;
            justify-content: space-between;
            flex-wrap: wrap;
            gap: 20px;
        }

        .dark-theme .profile-summary-header {
            border-color: rgba(255, 255, 255, 0.06);
        }

        .profile-info-block {
            display: flex;
            align-items: center;
            gap: 20px;
        }

        .profile-info-block img {
            width: 64px;
            height: 64px;
            border-radius: 50%;
            object-fit: cover;
            border: 3px solid #ffffff;
            box-shadow: var(--shadow-md, 0 4px 10px rgba(0,0,0,0.1));
        }

        .profile-text-block h3 {
            font-weight: 800;
            font-size: 20px;
            letter-spacing: -0.5px;
            margin: 0 0 4px 0 !important;
            color: var(--text-primary, #0f172a);
        }

        .profile-text-block span {
            font-size: 13.5px;
            font-weight: 600;
            color: var(--text-muted, #64748b);
        }

        /* Tool Split Container */
        .tool-panel-container {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(320px, 1fr));
            gap: 24px;
            margin-bottom: 24px;
        }

        .tool-card-box {
            background: var(--card-bg, #ffffff);
            border-radius: var(--border-radius-lg, 24px);
            border: 1px solid var(--border-color, #e2e8f0);
            box-shadow: var(--shadow-sm, 0 1px 3px rgba(0,0,0,0.05));
            padding: 24px;
            display: flex;
            flex-direction: column;
            gap: 15px;
        }

        .dark-theme .tool-card-box {
            border-color: rgba(255, 255, 255, 0.06);
        }

        .tool-card-box h4 {
            font-weight: 800;
            font-size: 15px;
            margin: 0 !important;
            color: var(--text-primary, #0f172a);
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }

        /* Action form layout */
        .action-input-group {
            display: flex;
            align-items: center;
            background: var(--bg-main, #f8fafc) !important;
            border: 1.5px solid var(--border-color, #e2e8f0) !important;
            border-radius: 16px !important;
            padding: 4px 6px !important;
            transition: all 0.25s ease;
        }

        .dark-theme .action-input-group {
            background: rgba(255,255,255,0.02) !important;
            border-color: rgba(255,255,255,0.08) !important;
        }

        .action-input-group:focus-within {
            border-color: var(--primary-color, #6366f1) !important;
            box-shadow: 0 0 0 4px rgba(99, 102, 241, 0.12) !important;
        }

        .action-input-group input.form-control {
            background: transparent !important;
            border: none !important;
            padding: 8px 12px !important;
            height: auto !important;
            color: var(--text-primary, #0f172a) !important;
            font-size: 14.5px !important;
            font-weight: 700 !important;
            flex: 1 !important;
            min-width: 0 !important;
        }

        .btn-set-fees-custom {
            background: var(--card-bg, #ffffff) !important;
            color: var(--text-primary, #0f172a) !important;
            border: 1px solid var(--border-color, #e2e8f0) !important;
            border-radius: 10px !important;
            padding: 8px 14px !important;
            font-weight: 700 !important;
            transition: all 0.2s ease;
            white-space: nowrap !important;
            flex-shrink: 0 !important;
        }

        .btn-set-fees-custom:hover {
            border-color: var(--primary-color, #6366f1) !important;
            color: var(--primary-color, #6366f1) !important;
        }

        .btn-setoff-custom {
            background: var(--primary-gradient, linear-gradient(135deg, #6366f1 0%, #4f46e5 100%)) !important;
            color: #ffffff !important;
            border-radius: 10px !important;
            padding: 8px 16px !important;
            font-weight: 700 !important;
            border: none !important;
            box-shadow: 0 4px 10px rgba(99, 102, 241, 0.2) !important;
            transition: all 0.2s ease;
            margin-left: 6px;
            white-space: nowrap !important;
            flex-shrink: 0 !important;
        }

        .btn-setoff-custom:hover {
            transform: translateY(-1px);
            opacity: 0.95;
        }

        /* Status & Actions in Table */
        .status-badge-custom {
            border-radius: 8px !important;
            font-size: 12px !important;
            font-weight: 700 !important;
            padding: 4px 10px !important;
            display: inline-block;
            border: none !important;
        }

        .status-badge-custom.pending {
            background-color: rgba(239, 68, 68, 0.08) !important;
            color: #ef4444 !important;
        }

        .status-badge-custom.completed {
            background-color: rgba(16, 185, 129, 0.08) !important;
            color: #10b981 !important;
        }

        .btn-table-action-edit {
            background-color: rgba(59, 130, 246, 0.08) !important;
            color: #3b82f6 !important;
            border-radius: 8px !important;
            font-size: 12px !important;
            font-weight: 700 !important;
            padding: 4px 10px !important;
            margin-left: 6px;
            border: none !important;
            transition: all 0.2s ease;
        }

        .btn-table-action-edit:hover {
            background-color: #3b82f6 !important;
            color: #ffffff !important;
        }

        .btn-table-action-cancel {
            border-radius: 8px !important;
            font-size: 12px !important;
            font-weight: 700 !important;
            padding: 4px 12px !important;
            border: none !important;
            transition: all 0.2s ease;
        }

        .btn-table-action-cancel.cancel {
            background-color: rgba(239, 68, 68, 0.08) !important;
            color: #ef4444 !important;
        }

        .btn-table-action-cancel.cancel:hover {
            background-color: #ef4444 !important;
            color: #ffffff !important;
        }

        .btn-table-action-cancel.proceed {
            background-color: rgba(16, 185, 129, 0.08) !important;
            color: #10b981 !important;
        }

        .btn-table-action-cancel.proceed:hover {
            background-color: #10b981 !important;
            color: #ffffff !important;
        }

        /* ---- Admin Member Selector Drawer ---- */
        .mem-sel-btn {
            display: inline-flex; align-items: center; gap: 8px;
            padding: 9px 20px; border-radius: 12px;
            background: linear-gradient(135deg, #6366f1, #4f46e5);
            color: #fff; border: none; font-size: 13.5px; font-weight: 700;
            cursor: pointer; box-shadow: 0 4px 12px rgba(99,102,241,0.28);
            transition: all 0.2s; font-family: inherit;
        }
        .mem-sel-btn:hover { transform: translateY(-1px); opacity: 0.93; }

        /* Overlay */
        #memberSelectorOverlay {
            position: fixed; inset: 0;
            background: rgba(15,23,42,0.45);
            backdrop-filter: blur(6px);
            z-index: 8000;
            display: none; opacity: 0;
            transition: opacity 0.25s;
        }
        #memberSelectorOverlay.open { display: block; opacity: 1; }

        /* Drawer panel */
        #memberSelectorDrawer {
            position: fixed; top: 0; right: -480px; width: 460px;
            height: 100vh; background: #fff;
            box-shadow: -8px 0 40px rgba(0,0,0,0.14);
            z-index: 8001;
            display: flex; flex-direction: column;
            transition: right 0.3s cubic-bezier(0.4, 0, 0.2, 1);
            font-family: 'Inter', 'Segoe UI', sans-serif;
        }
        #memberSelectorDrawer.open { right: 0; }

        .msd-header {
            background: linear-gradient(135deg, #6366f1, #4f46e5);
            padding: 20px 24px;
            display: flex; align-items: center; justify-content: space-between;
            flex-shrink: 0;
        }
        .msd-header h3 {
            color: #fff; margin: 0; font-size: 17px; font-weight: 800;
            display: flex; align-items: center; gap: 9px;
        }
        .msd-close-btn {
            width: 32px; height: 32px; border-radius: 9px;
            background: rgba(255,255,255,0.18); border: none; color: #fff;
            font-size: 18px; cursor: pointer; display: flex;
            align-items: center; justify-content: center;
            transition: background 0.15s;
        }
        .msd-close-btn:hover { background: rgba(255,255,255,0.3); }

        .msd-search-wrap {
            padding: 14px 20px; border-bottom: 1px solid #f1f5f9; flex-shrink: 0;
        }
        .msd-search-inner {
            display: flex; align-items: center;
            background: #f8faff; border: 1.5px solid #e2e8f0;
            border-radius: 12px; overflow: hidden;
            transition: border-color 0.2s, box-shadow 0.2s;
        }
        .msd-search-inner:focus-within {
            border-color: #6366f1; box-shadow: 0 0 0 3px rgba(99,102,241,0.1);
        }
        .msd-search-icon { padding: 0 12px; color: #94a3b8; font-size: 14px; flex-shrink: 0; }
        .msd-search-inner input {
            flex: 1; border: none; background: transparent;
            padding: 10px 8px 10px 0; font-size: 14px; font-family: inherit;
            color: #1e293b; outline: none;
        }

        .msd-list { flex: 1; overflow-y: auto; padding: 10px 16px; }
        .msd-item {
            display: flex; align-items: center; gap: 14px;
            padding: 13px 14px; border-radius: 14px;
            cursor: pointer; border: 1.5px solid transparent;
            transition: all 0.18s; margin-bottom: 6px;
            background: #f8faff;
        }
        .msd-item:hover { background: #eff1fe; border-color: #c7d2fe; }
        .msd-item.active { background: #eef2ff; border-color: #6366f1; }
        .msd-avatar {
            width: 46px; height: 46px; border-radius: 50%;
            object-fit: cover; border: 2.5px solid #e8edf5; flex-shrink: 0;
        }
        .msd-item:hover .msd-avatar { border-color: #6366f1; }
        .msd-info { flex: 1; min-width: 0; }
        .msd-name {
            font-weight: 700; color: #1e293b; font-size: 14px;
            white-space: nowrap; overflow: hidden; text-overflow: ellipsis;
        }
        .msd-group { font-size: 11.5px; color: #6366f1; font-weight: 500; margin-top: 2px; }
        .msd-phone { font-size: 12px; color: #94a3b8; margin-top: 1px; }
        .msd-select-icon {
            width: 30px; height: 30px; border-radius: 8px;
            background: #6366f1; color: #fff; display: flex;
            align-items: center; justify-content: center;
            font-size: 13px; flex-shrink: 0;
            opacity: 0; transition: opacity 0.18s;
        }
        .msd-item:hover .msd-select-icon { opacity: 1; }
        .msd-item.active .msd-select-icon { opacity: 1; background: #4f46e5; }

        .msd-empty {
            text-align: center; padding: 40px 20px;
            color: #94a3b8; font-size: 14px;
        }
        .msd-empty i { font-size: 38px; display: block; margin-bottom: 10px; color: #dbeafe; }

        .msd-pagination { padding: 10px 16px 14px; border-top: 1px solid #f1f5f9; flex-shrink: 0; }
        .msd-pagination .pagination { margin: 0; justify-content: center; }
        .msd-pagination .pagination li a,
        .msd-pagination .pagination li span {
            border-radius: 8px !important; margin: 0 2px;
            border: 1.5px solid #e8edf5; color: #475569;
            font-size: 12px; font-weight: 500; padding: 5px 10px;
        }
        .msd-pagination .pagination li.active a {
            background: #6366f1 !important; border-color: transparent !important; color: #fff !important;
        }

        @media (max-width: 520px) {
            #memberSelectorDrawer { width: 100%; right: -100%; }
        }

        /* Layout wraps */
        .settings-card-wrapper {
            background-color: var(--card-bg, #ffffff);
            border-radius: var(--border-radius-lg, 24px);
            border: 1px solid var(--border-color, #e2e8f0);
            box-shadow: var(--shadow-md, 0 10px 30px -10px rgba(99, 102, 241, 0.08));
            padding: 30px;
            transition: all 0.3s ease;
        }

        .dark-theme .settings-card-wrapper {
            border-color: rgba(255, 255, 255, 0.06);
        }

        /* Modal styling */
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
        function getMonthNameFromRecord(dateStr, description) {
            const monthNames = [
                "", "January", "February", "March", "April", "May", "June",
                "July", "August", "September", "October", "November", "December"
            ];
            
            let foundMonth = null;
            let foundYear = null;

            if (description) {
                const descLower = description.toLowerCase();
                for (let i = 1; i < monthNames.length; i++) {
                    const mLower = monthNames[i].toLowerCase();
                    if (descLower.includes(mLower)) {
                        foundMonth = monthNames[i];
                        break;
                    }
                }
                
                // Try to find a 4-digit year (e.g. 2025 or 2026) in the description
                const yearMatch = description.match(/\b(20\d{2})\b/);
                if (yearMatch) {
                    foundYear = yearMatch[1];
                }
            }

            // If month is found in description, return it
            if (foundMonth) {
                if (!foundYear && dateStr) {
                    const parts = dateStr.split('-');
                    if (parts.length > 0) {
                        foundYear = parts[0];
                    }
                }
                return foundMonth + (foundYear ? " " + foundYear : "");
            }

            // Fallback: parse from dateStr
            if (!dateStr) return '';
            const parts = dateStr.split('-');
            if (parts.length < 2) return '';
            const monthIndex = parseInt(parts[1], 10);
            const year = parts[0];
            const monthName = monthNames[monthIndex];
            return monthName ? monthName + " " + year : '';
        }

        function msdPaginate(totalRows, current_page) {
            var rowsPerPage = 8;
            var pagesToShow = 5;
            var totalPages = Math.ceil(totalRows / rowsPerPage);   
            var currentGroup = Math.ceil(current_page / pagesToShow);
            var startPage = (currentGroup - 1) * pagesToShow + 1;
            var endPage = Math.min(startPage + pagesToShow - 1, totalPages);
            var val;

            var html = '';
            html = html + "<div class='text-center'>";
            if (current_page > 1) {
                val = current_page - 1;
                html = html + "<a onclick='msdLoadMembers(" + val + ")' class='btn btn-white'><</a>";
            }
            for (var i = startPage; i <= endPage; i++) {
                var activeClass = (i == current_page) ? "active" : "";
                html = html + "<a onclick='msdLoadMembers(" + i + ")' class='btn btn-white " + activeClass + "'>" + i + "</a>";
            }
            if (current_page < totalPages) {
                val = current_page + 1;
                html = html + "<a onclick='msdLoadMembers(" + val + ")' class='btn btn-white'>></a>";
            }
            html = html + "</div>";

            return html;
        }

        $(document).ready(function() {          
            loadData(1); // Function to load data for page 1     
        });  

        // function to load recieveble amount details start
        function loadData(page) {
            $('#hdn_current_page').val(page); //used for Status Update function
            console.log("Loading data for page:", page);
            $.ajax({               
               type: "POST",
               url: "api/fees_receiveble.php",
               data: {
                action: 'load_data',
                page: page, 
               },
                success: function(data) {

                    var obj = jQuery.parseJSON(data);
                    window.currentReceivablesList = obj[1];
                                       
                    var totalrows = obj[0].total_rows;
                    var htm="";
                    
                    htm=htm+ "<div class='table-responsive'>";
                    htm=htm+ "<table class='table table-striped table-hover' id='tbl_receiveble' style='margin-bottom: 0;'>";
                    htm=htm+ "<thead>";
                    htm=htm+ "<tr>";
                    htm=htm+ "<th>Date</th>";
                    htm=htm+ "<th>Head</th>";
                    htm=htm+ "<th>Particular</th>";
                    htm=htm+ "<th>Receivable</th>";
                    htm=htm+ "<th>Set-Off</th>";
                    htm=htm+ "<th>Balance Dues</th>";
                    htm=htm+ "<th>Status</th>";
                    htm=htm+ "<th style='text-align: right;'>Actions</th>";
                   
                    htm=htm+ "</tr>";
                    htm=htm+ "</thead>";
                    htm=htm+ "<tbody>";
                    for (var i = 0; i < obj[1].length; i++) {

                        var recFees = parseFloat(obj[1][i].receiveble_fees) || 0;
                        var totRcvd = parseFloat(obj[1][i].total_received_fees) || 0;
                        var balance = recFees - totRcvd;

                        htm += "<tr id='tr_" + i + "' data-receiveble-id='" + obj[1][i].recieveble_id + "' data-flag='" + obj[1][i].flag + "' data-remaining-balance='" + balance + "'>";

                        var monthYear = getMonthNameFromRecord(obj[1][i].date, obj[1][i].discription);
                        htm=htm+ "<td style='vertical-align:middle; font-weight:600; color:var(--text-muted);'>"+obj[1][i].date+"</td>";
                        htm=htm+ "<td style='vertical-align:middle; font-weight:700; color:var(--text-primary);'>"+obj[1][i].name+"</td>";
                        // Build Particular/Description cell
                        var headName = obj[1][i].name ? obj[1][i].name.trim() : '';
                        var rawDesc  = obj[1][i].discription ? obj[1][i].discription.trim() : '';
                        var particularHtml = '';

                        if (headName.toLowerCase() === 'monthly fee' || headName.toLowerCase() === 'monthly fees') {
                            // For Monthly Fee: show "Monthly Fee — Month Year" derived from the record date only
                            var monthYearFromDate = getMonthNameFromRecord(obj[1][i].date, null);
                            var monthLabel = monthYearFromDate ? headName + ' \u2014 ' + monthYearFromDate : headName;
                            particularHtml = "<strong style='color:var(--text-primary);'>" + monthLabel + "</strong>";
                            // Append extra description below if it exists and isn't just the head name
                            if (rawDesc && rawDesc.toLowerCase() !== headName.toLowerCase()) {
                                particularHtml += "<br><span style='font-size:11px; color:var(--text-muted);'>" + rawDesc + "</span>";
                            }
                        } else {
                            particularHtml = rawDesc || '\u2014';
                        }

                        var groupName = obj[1][i].group_name ? obj[1][i].group_name.trim() : '';
                        if (groupName) {
                            particularHtml += "<br><span class='badge' style='margin-top: 4px; font-size: 11px; padding: 3px 8px; border-radius: 6px; background-color: rgba(99, 102, 241, 0.1); color: #6366f1; font-weight: 700;'><i class='fa fa-users'></i> " + groupName + "</span>";
                        }

                        htm=htm+ "<td style='vertical-align:middle;'>" + particularHtml + "</td>";

                        htm = htm + "<td style='vertical-align:middle; font-weight:700;'>₹ " + recFees.toFixed(2) + "</td>";
                        htm = htm + "<td style='vertical-align:middle; font-weight:700; color:#10b981;'>₹ 0.00</td>";
                        htm=htm+ "<td style='vertical-align:middle; font-weight:700; color:#ef4444;' id='bal_"+i+"'>₹ " + balance.toFixed(2) + "</td>";
                        
                        if(obj[1][i].iscomplete == 0){
                            htm=htm+ "<td style='vertical-align:middle;'><button type='button' class='status-badge-custom pending' disabled><i class='fa fa-clock-o'></i> Pending</button></td>";
                        }
                        else{
                            htm=htm+ "<td style='vertical-align:middle;'><button type='button' class='status-badge-custom completed' disabled><i class='fa fa-check-circle'></i> Settled</button></td>";
                        }
                        
                        htm=htm+ "<td style='vertical-align:middle; text-align: right;'>";
                        htm=htm+ "<button type='button' class='btn btn-table-action-edit' onclick='editReceiveble("+i+");'><i class='fa fa-pencil'></i> Edit</button>";
                        
                        if(obj[1][i].cancel == 1){
                            htm=htm+ "<button type='button' class='btn btn-table-action-cancel proceed' style='margin-left:6px;' onclick='cancelFeeDetails("+obj[1][i].recieveble_id+", "+obj[1][i].cancel+");'><i class='fa fa-refresh'></i> Re-open</button>";
                        }
                        else{
                            htm=htm+ "<button type='button' class='btn btn-table-action-cancel cancel' style='margin-left:6px;' onclick='cancelFeeDetails("+obj[1][i].recieveble_id+", "+obj[1][i].cancel+");'><i class='fa fa-ban'></i> Cancel</button>";
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
            $('#setoff_buttuon').hide();
            $('#setoff_buttuon_wallet').hide();
            load_pending_payment();
            loadMenu();
            load_wallet_amount();
            load_heads();
            load_closing_years();
            
        }
    </script>

    <script>
        function setFees(){
            var balAmt=0;
            var TotalAmount=parseFloat($('#txt_AmountPaid').val()) || 0;
            if (TotalAmount <= 0) {
                if (typeof alertinfo === 'function') {
                    alertinfo("Please enter an amount before clicking Set Fees.");
                } else if (typeof swal !== 'undefined') {
                    swal("Amount Required", "Please enter an amount before clicking Set Fees.", "warning");
                } else {
                    alert("Please enter an amount before clicking Set Fees.");
                }
                return;
            }
            
            // First, reset all rows to their original remaining balance state
            $('#tbl_receiveble tbody tr').each(function() {
                var remaining_balance = parseFloat($(this).data('remaining-balance')) || 0;
                $(this).find('td:nth-child(5)').text('₹ 0.00');
                $(this).find('td:nth-child(6)').text('₹ ' + remaining_balance.toFixed(2));
            });

            $('#tbl_receiveble tbody tr').each(function() {
                var remaining_balance = parseFloat($(this).data('remaining-balance')) || 0;
                if(TotalAmount>=remaining_balance){
                    $(this).find('td:nth-child(6)').text('₹ 0.00');
                    $(this).find('td:nth-child(5)').text('₹ ' + remaining_balance.toFixed(2));
                    TotalAmount=TotalAmount-remaining_balance;
                }
                else{
                   balAmt= remaining_balance-TotalAmount;
                   $(this).find('td:nth-child(6)').text('₹ ' + balAmt.toFixed(2));
                   $(this).find('td:nth-child(5)').text('₹ ' + TotalAmount.toFixed(2));
                   return false;
                }
             });
            $('#setoff_buttuon').show();
        }

        function setFeesFromWallet(){
            var balAmt=0;
            var TotalAmount=parseFloat($('#txt_wallet_amount_input').val()) || 0;
            if (TotalAmount <= 0) {
                if (typeof alertinfo === 'function') {
                    alertinfo("Please enter a wallet amount before clicking Set Fees.");
                } else if (typeof swal !== 'undefined') {
                    swal("Amount Required", "Please enter a wallet amount before clicking Set Fees.", "warning");
                } else {
                    alert("Please enter a wallet amount before clicking Set Fees.");
                }
                return;
            }
            
            // First, reset all rows to their original remaining balance state
            $('#tbl_receiveble tbody tr').each(function() {
                var remaining_balance = parseFloat($(this).data('remaining-balance')) || 0;
                $(this).find('td:nth-child(5)').text('₹ 0.00');
                $(this).find('td:nth-child(6)').text('₹ ' + remaining_balance.toFixed(2));
            });

            $('#tbl_receiveble tbody tr').each(function() {
                var remaining_balance = parseFloat($(this).data('remaining-balance')) || 0;
                if(TotalAmount>=remaining_balance){
                    $(this).find('td:nth-child(6)').text('₹ 0.00');
                    $(this).find('td:nth-child(5)').text('₹ ' + remaining_balance.toFixed(2));
                    TotalAmount=TotalAmount-remaining_balance;
                }
                else{
                   balAmt= remaining_balance-TotalAmount;
                   $(this).find('td:nth-child(6)').text('₹ ' + balAmt.toFixed(2));
                   $(this).find('td:nth-child(5)').text('₹ ' + TotalAmount.toFixed(2));
                   return false;
                }
             });
            $('#setoff_buttuon_wallet').show();
        }
        
        function setOffform(){
            $('#setOffModel').modal('show');
        }
        
        function setOffformforWallet(){
            $('#setOffwalletModel').modal('show');
        }

        function closesetOffformforWallet(){
            $('#setoff_form_wallet')[0].reset();
            $('#setOffwalletModel').modal('hide');
        }
        
        function closesetOffform(){
            $('#setoff_form')[0].reset();
            $('#setOffModel').modal('hide');
        }

        function setOffReceiveble() {
            var receivedArray = [];

            $('#tbl_receiveble tbody tr').each(function() {
                var $tr = $(this);

                var receiveble_id = $tr.data('receiveble-id');
                var flag = $tr.data('flag');
                
                // Clean the currency symbols before parsing
                var receivedText = $tr.find('td:nth-child(5)').text().replace('₹ ', '');
                var balanceText  = $tr.find('td:nth-child(6)').text().replace('₹ ', '');
                
                var received = parseFloat(receivedText) || 0;
                var balance  = parseFloat(balanceText) || 0;

                if (received > 0) {
                    receivedArray.push({
                        receiveble_id: receiveble_id,
                        flag: flag,
                        received: received,
                        balance: balance
                    });
                }
            });

            if (receivedArray.length === 0) {
                alertinfo("Please set fees before saving.");
                return;
            }

            var date = $('#setoff_date').val();
            if (date == "") {
                alertinfo("Please select a transaction date.");
                return;
            }

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
                    var formData = new FormData();
                    formData.append('action', 'setoff_receiveble');
                    formData.append('received_array', JSON.stringify(receivedArray));
                    formData.append('date', $('#setoff_date').val());
                    formData.append('transaction_type', $('#selected_transaction_type').val());
                    
                    var fileInput = document.getElementById('bill_photo');
                    if (fileInput && fileInput.files.length > 0) {
                        formData.append('bill_photo', fileInput.files[0]);
                    }

                    $.ajax({
                        type: "POST",
                        url: "api/fees_receiveble.php",
                        data: formData,
                        processData: false,
                        contentType: false,
                        success: function(response) {
                            closesetOffform();
                            alertsuccess('Payment processed successfully');
                            loadData($('#hdn_current_page').val());
                        },
                        error: function (xhr, status){
                            var msg = xhr.responseText || 'Error processing payment';
                            try {
                                var msgObj = JSON.parse(xhr.responseText);
                                if (msgObj && typeof msgObj === 'object') {
                                    msg = msgObj.Message || msgObj.message || msg;
                                }
                            } catch(e) {}
                            alerterror(msg);
                            $('#setoff_form')[0].reset();
                        }
                    });
                }
            });   
        }

        function setOffReceivebleFromWallet() {
            var receivedArray = [];

            $('#tbl_receiveble tbody tr').each(function() {
                var $tr = $(this);

                var receiveble_id = $tr.data('receiveble-id');
                var flag = $tr.data('flag');
                
                // Clean the currency symbols before parsing
                var receivedText = $tr.find('td:nth-child(5)').text().replace('₹ ', '');
                var balanceText  = $tr.find('td:nth-child(6)').text().replace('₹ ', '');
                
                var received = parseFloat(receivedText) || 0;
                var balance  = parseFloat(balanceText) || 0;

                if (received > 0) {
                    receivedArray.push({
                        receiveble_id: receiveble_id,
                        flag: flag,
                        received: received,
                        balance: balance
                    });
                }
            });

            if (receivedArray.length === 0) {
                alertinfo("Please set fees before saving.");
                return;
            }

            var date = $('#setoff_date_wallet').val();
            if (date == "") {
                alertinfo("Please select a transaction date.");
                return;
            }

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
                        action: 'setoff_receiveble_from_wallet',
                        received_array: JSON.stringify(receivedArray),
                        date: $('#setoff_date_wallet').val(),
                        transaction_type: $('#selected_transaction_type_wallet').val()
                    };
                    $.ajax({
                        type: "POST",
                        url: "api/fees_receiveble.php",
                        data: data,
                        success: function(response) {
                            closesetOffformforWallet();
                            alertsuccess('Payment processed from wallet');
                            loadData($('#hdn_current_page').val());
                        },
                        error: function (xhr, status){
                            var msgObj = JSON.parse(xhr.responseText);
                            alerterror(msgObj, xhr);
                            $('#setoff_form_wallet')[0].reset();
                        }
                    });
                }
            });   
        }

        function editReceiveble(index) {
            var item = window.currentReceivablesList[index];
            $("#hdn_id").val(item.recieveble_id);
            $("#rec_date").val(item.date);
            $("#rec_amount").val(item.receiveble_fees);
            $("#rec_discription").val(item.discription || '');
            $('#receivebleModel').modal('show');
        }

        function closeaddRecieveble() {
            $('#receiveble_form')[0].reset();
            $('#receivebleModel').modal('hide');
        }

        function saveReceiveble() {
            const amount = $('#rec_amount').val();
            const date = $('#rec_date').val();
            const discription = $("#rec_discription").val();
            let length = discription.length;

            if (amount == "") {
                alertinfo("Amount cannot be empty.");
                return;
            }
            if (date == "") {
                alertinfo("Date cannot be empty.");
                return;
            }
            if (length >= 150) {
                alertinfo("Description must be less than 150 characters.");
                return;
            }

            swal({
                title: "Are you sure?",
                text: "Do you want to update this receivable record?",
                type: "warning",
                showCancelButton: true,
                confirmButtonColor: "#DD6B55",
                confirmButtonText: "Yes, Update!",
                cancelButtonText: "Cancel",
                closeOnConfirm: false,
                closeOnCancel: true
            },
            function(isConfirm) {
                if (isConfirm) {
                    var data = {
                        action: 'save_receiveble_amount',
                        id: $("#hdn_id").val(),
                        amount: amount,
                        date: date,
                        discription: discription
                    };
                    $.ajax({
                        type: "POST",
                        url: "api/fees_receiveble.php",
                        data: data,
                        success: function(response) {
                            closeaddRecieveble();
                            alertsuccess('Updated Successfully');
                            loadData($('#hdn_current_page').val());
                        },
                        error: function(xhr, status, error) {
                            closeaddRecieveble();
                            console.error('AJAX error: ', error);
                            var msgObj = { Message: "Oops! Something went wrong." };
                            try {
                                msgObj = JSON.parse(xhr.responseText);
                            } catch(e) {}
                            alerterror(msgObj, xhr);
                        }
                    });
                }
            });
        }

        function load_wallet_balance(member_id){
            $.ajax({
                type: "POST",
                url: "api/fees_receiveble.php",
                data: {
                    action: 'load_wallet_balance',
                    id:member_id,
                },
                success: function(data) {
                    var obj = jQuery.parseJSON(data);
                    var htm = "";

                    for (var i = 0; i < obj[0].length; i++) {
                        var wallet_balance = parseFloat(obj[0][i].wallet_balance);
                        htm += "<label>Wallet Balance</label><input type='number' id='wallet_amount' name='wallet_amount' value='" + wallet_balance + "' class='form-control' readonly style='margin-bottom:10px;'>";

                        if (wallet_balance === 0) {
                            htm += "<div style='color:#ef4444; font-weight:700; margin-bottom: 12px;'><i class='fa fa-exclamation-circle'></i> Wallet is empty</div>";
                        } else {
                            htm += "<label class='head-check-card' style='display:inline-flex; width:auto; border-color:var(--primary-color); padding: 8px 16px; margin-bottom:12px;'>";
                            htm += "<input type='checkbox' name='wallet' value='1' id='use_wallet' style='margin-right:8px;'> Add from Wallet";
                            htm += "</label>";
                        }
                    }
                    $('#wallet_balance').html(htm);
                },
                error: function(xhr, status, error) {
                    console.error('AJAX error: ', status, error);
                }
            });
        }

        function load_closing_years(){
            $.ajax({
                type: "POST",
                url: "api/fees_receiveble.php",
                data: {
                    action: 'load_closing_years',
                },
                success: function(data) {
                    var obj = jQuery.parseJSON(data);
                    var htm="";
                    htm=htm+ "<div class='dropdown form-group'><select id='selected_year' class='form-control' disabled>";
                    for (var i = 0; i < obj[0].length; i++) {
                        htm=htm+"<option value='"+obj[0][i].id+"'>"+obj[0][i].from_year+" - "+obj[0][i].to_year+"</option>";
                    }                
                    htm=htm+"</select></div>";
                    $('#select_year').html(htm);
                },
                error: function(xhr, status, error) {
                    console.error('AJAX error: ', status, error);
                }
            });
        }

        function cancelFeeDetails(receiveble_id, cancel) {
            var status;
            let text = '';
            if(cancel===1){
                status=0;
                text = 'Do you want to re-open the payment?';
            }
            else{
                status=1;
                text = 'Do you want to cancel/complete the payment?';
            }
            swal({
                title: "Are you sure?",
                text: text,
                type: "warning",
                showCancelButton: true,
                confirmButtonColor: "#DD6B55",
                confirmButtonText: "Yes!",
                cancelButtonText: "Cancel",
                closeOnConfirm: false,
                closeOnCancel: true
            },
            function (isConfirm) {
                if (isConfirm){
                    var data = {
                        action: 'cancel_payment',
                        id: receiveble_id,
                        status: status,
                    };
                    $.ajax({
                        type: "POST",
                        url: "api/fees_receiveble.php",
                        data: data,
                        success: function(response) {
                            alertwarning(response);
                            loadData($('#hdn_current_page').val());
                        },
                        error: function(xhr, status, error) {
                            console.log('AJAX error:', status, error);
                        }
                    });
                }
            });   
        }

        function load_wallet_amount(){
            $.ajax({
                type: "POST",
                url: "api/fees_receiveble.php",
                data: {
                    action: 'load_wallet_amount',
                },
                success: function(data) {
                    var obj = jQuery.parseJSON(data);
                    var htm="";                
                    for (var i = 0; i < obj[0].length; i++) {
                        var val = parseFloat(obj[0][i].wallet_balance);
                        $("#txt_wallet_amount_input").val(val);
                        if(val > 0){
                            htm=htm+ "<span style='color: #10b981; font-weight:700; font-size:13px;'><i class='fa fa-check-circle'></i> Available Wallet Balance: ₹ " + val.toFixed(2) + "</span>";
                        }
                        else{
                            $('#setFeesFromWallet_button').hide();
                            $('#txt_wallet_amount_input').hide();
                        }
                    }     
                    $('#add_from_wallet').html(htm);  
                },
                error: function(xhr, status, error) {
                    console.error('AJAX error: ', status, error);
                }
            });
        }

        function load_pending_payment(){
            $.ajax({
                type: "POST",
                url: "api/fees_receiveble.php",
                data: {
                    action: 'load_pending_payment',
                },
                success: function(data) {
                    var obj = jQuery.parseJSON(data);
                    var htm="";
                    for (var i = 0; i < obj[0].length; i++) {
                        var recVal = parseFloat(obj[0][i].receivable_fees) || 0;
                        var rcvVal = parseFloat(obj[0][i].received_fees) || 0;
                        var pending = recVal - rcvVal;
                        htm=htm+ "<div style='background:rgba(239, 68, 68, 0.08); border: 1.5px solid rgba(239, 68, 68, 0.15); border-radius:12px; padding: 10px 20px; font-weight:800; font-size:14px; color:#ef4444; display:inline-block;'><i class='fa fa-exclamation-triangle'></i> Outstanding Balance Dues: ₹ " + pending.toFixed(2) + "</div>";
                    }                
                    $('#pending_balace_table').html(htm);  
                },
                error: function(xhr, status, error) {
                    console.error('AJAX error: ', status, error);
                }
            });
        }

        function load_heads(){
            // Keep head dropdown loading if modal utilizes it.
        }

        // ===== Admin Member Selector Drawer =====
        var msdCurrentPage = 1;
        var msdSearchTimer = null;
        var msdActiveMemberId = <?php echo isset($_SESSION['member_id']) ? (int)$_SESSION['member_id'] : 0; ?>;

        function openMemberSelector() {
            document.getElementById('memberSelectorOverlay').classList.add('open');
            document.getElementById('memberSelectorDrawer').classList.add('open');
            document.getElementById('msd_search').value = '';
            msdCurrentPage = 1;
            msdLoadMembers(1);
        }

        function closeMemberSelector() {
            document.getElementById('memberSelectorOverlay').classList.remove('open');
            document.getElementById('memberSelectorDrawer').classList.remove('open');
        }

        function msdSearch() {
            clearTimeout(msdSearchTimer);
            msdSearchTimer = setTimeout(function() {
                msdCurrentPage = 1;
                msdLoadMembers(1);
            }, 300);
        }

        function msdLoadMembers(page) {
            msdCurrentPage = page;
            var searchVal = document.getElementById('msd_search').value;
            $('#msd_list').html('<div class="msd-empty"><i class="fa fa-spinner fa-spin"></i> Loading...</div>');

            $.ajax({
                type: "POST",
                url: "api/fees_receiveble.php",
                data: { action: 'load_members_for_select', page: page, val: searchVal },
                success: function(response) {
                    try {
                        var res = JSON.parse(response);
                        var pagination = res[0];
                        var members = res[1];
                        var htm = "";

                        if (members.length === 0) {
                            htm = '<div class="msd-empty"><i class="fa fa-user-times"></i> No members found.</div>';
                        } else {
                            for (var i = 0; i < members.length; i++) {
                                var m = members[i];
                                var fullName = m.first_name + " " + (m.middle_name ? m.middle_name + " " : "") + m.last_name;
                                var avatar = (m.img && m.img !== '0') ? '../image_upload/members/thumbnails/' + m.img : '../img/customer.png';
                                var groupNames = m.group_names ? m.group_names : 'No Group';
                                var activeClass = (parseInt(m.id) === msdActiveMemberId) ? ' active' : '';
                                var activeIcon = (parseInt(m.id) === msdActiveMemberId) ? 'fa-check' : 'fa-arrow-right';

                                htm += '<div class="msd-item' + activeClass + '" onclick="msdSelectMember(' + m.id + ', \'' + fullName.replace(/'/g, "\\'") + '\')">';
                                htm +=   '<img class="msd-avatar" src="' + avatar + '" onerror="this.src=\'../img/customer.png\'">';
                                htm +=   '<div class="msd-info">';
                                htm +=     '<div class="msd-name">' + fullName + '</div>';
                                htm +=     '<div class="msd-group"><i class="fa fa-tag"></i> ' + groupNames + '</div>';
                                htm +=     (m.phone ? '<div class="msd-phone"><i class="fa fa-phone"></i> ' + m.phone + '</div>' : '');
                                htm +=   '</div>';
                                htm +=   '<div class="msd-select-icon"><i class="fa ' + activeIcon + '"></i></div>';
                                htm += '</div>';
                            }
                        }

                        $('#msd_list').html(htm);

                        // Pagination
                        var pgHtm = msdPaginate(pagination.total_rows, page);
                        // Wrap pagination in msd-pagination compatible structure
                        $('#msd_pagination').html('<div class="msd-pagination-inner">' + pgHtm + '</div>');

                    } catch (e) {
                        console.error("Member selector parse error:", e);
                        $('#msd_list').html('<div class="msd-empty"><i class="fa fa-exclamation-circle"></i> Error loading members.</div>');
                    }
                },
                error: function() {
                    $('#msd_list').html('<div class="msd-empty"><i class="fa fa-exclamation-circle"></i> Error loading members.</div>');
                }
            });
        }

        function msdSelectMember(memberId, fullName) {
            if (memberId === msdActiveMemberId) {
                closeMemberSelector();
                return;
            }
            $.ajax({
                type: "POST",
                url: "api/fees_receiveble.php",
                data: { action: 'switch_member', member_id: memberId },
                success: function(response) {
                    try {
                        var res = JSON.parse(response);
                        if (res.success) {
                            msdActiveMemberId = memberId;
                            closeMemberSelector();
                            // Reload the page to update PHP session-dependent content (member name, image)
                            window.location.href = 'fees_receiveble.php';
                        } else {
                            alert('Could not switch member: ' + (res.Message || 'Unknown error'));
                        }
                    } catch (e) {
                        console.error("Switch member parse error:", e);
                    }
                },
                error: function() {
                    alert('Error switching member. Please try again.');
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

            <!-- ===== ADMIN MEMBER SELECTOR BUTTON (admin only) ===== -->
            <?php if (isset($_SESSION['login_id']) && (isSuperAdmin($_SESSION['login_id']) || isGroupAdmin($_SESSION['login_id']))): ?>
            <div style="display: flex; align-items: center; justify-content: space-between; padding: 14px 0; flex-wrap: wrap; gap: 10px;">
                <div style="font-size: 13px; color: #64748b; font-weight: 500;">
                    <i class="fa fa-info-circle" style="color:#6366f1;"></i> Admin View — Viewing ledger for selected member
                </div>
                <div style="display:flex; align-items:center; gap:8px;">
                    <button class="mem-sel-btn" onclick="openMemberSelector()" type="button" id="btn_change_member">
                        <i class="fa fa-users"></i> Change Member
                    </button>
                </div>
            </div>
            <?php endif; ?>

            <!-- Profile Summary top bar -->
            <div class="profile-summary-header" style="margin-top: 24px;">
                <div class="profile-info-block">
                    <?php
                    $img_src = '../img/customer.png';
                    if ($member_img && $member_img != '0' && $member_img != 0 && trim($member_img) !== '') {
                        $img_src = '../image_upload/members/uploads/' . $member_img;
                    }
                    ?>
                    <img src="<?php echo $img_src; ?>" onerror="this.src='../img/customer.png';">
                    <div class="profile-text-block">
                        <h3>
                            <?php echo $first_name . " " . ($middle_name ? $middle_name . " " : "") . $last_name; ?>
                            <?php if ($is_guest_desktop): ?>
                                <span class="badge" style="background: linear-gradient(135deg, #fff7ed 0%, #ffedd5 100%); color: #c2410c; border: 1px solid rgba(249, 115, 22, 0.3); box-shadow: 0 2px 6px rgba(249, 115, 22, 0.12); font-size: 11px; font-weight: 800; padding: 4px 10px 4px 8px; border-radius: 20px; letter-spacing: 0.6px; margin-left: 6px; display: inline-flex; align-items: center; gap: 4px; vertical-align: middle;"><i class="fa fa-star" style="color:#f59e0b; font-size:10px;"></i> GUEST</span>
                                <button class="btn btn-xs" onclick="openAddGuestFeeModalDesktop()" type="button" style="background: linear-gradient(135deg, #f59e0b, #d97706); color:#fff; font-weight:800; border-radius:8px; padding:4px 10px; margin-left:10px; border:none;">
                                    <i class="fa fa-plus-circle"></i> Add Custom Guest Fee
                                </button>
                            <?php endif; ?>
                        </h3>
                        <span>Member Profile Collection details</span>
                    </div>
                </div>
                
                <div id="pending_balace_table">
                    <!-- Outstanding dues injected here -->
                </div>
            </div>

            <!-- search bar starts (Tools Panel) -->
            <div class="tool-panel-container">
                <!-- Cash/Bank Setoff Panel -->
                <div class="tool-card-box">
                    <h4>Set-Off Receivable (Cash/Bank)</h4>
                    <div class="action-input-group">
                        <input type="number" placeholder="Enter amount to pay" id="txt_AmountPaid" name="submit" class="form-control">
                        <div class="action-btn-group" style="display: flex; flex-shrink: 0;">
                            <button class="btn-set-fees-custom" onclick="setFees()" type="button">Set Fees</button>
                            <button type="button" id="setoff_buttuon" class="btn-setoff-custom" onclick="setOffform()">SetOff</button>
                        </div>
                    </div>
                </div>

                <!-- Wallet Setoff Panel -->
                <div class="tool-card-box">
                    <h4>Set-Off Receivable (Wallet)</h4>
                    <div class="action-input-group">
                        <input type="number" placeholder="Wallet set-off amount" id="txt_wallet_amount_input" name="wallet" class="form-control">
                        <div class="action-btn-group" style="display: flex; flex-shrink: 0;">
                            <button class="btn-set-fees-custom" onclick="setFeesFromWallet()" id="setFeesFromWallet_button" type="button">Set Fees</button>
                            <button type="button" id="setoff_buttuon_wallet" class="btn-setoff-custom" onclick="setOffformforWallet()">SetOff</button>
                        </div>
                    </div>
                    <div id="add_from_wallet" style="margin-top:-5px;">
                       <!-- Wallet text info -->
                    </div>
                </div>
            </div>
            <!-- search bar ends -->
             
            <div class="settings-card-wrapper" style="margin-bottom: 30px;">
                <div class="wrapper wrapper-content animated fadeInRight" id="table_client" style="padding:0;">
                    <!-- table injected via ajax -->
                </div>
            </div>
        </div>
    </div>
       
    <!-- ===== ADMIN MEMBER SELECTOR DRAWER ===== -->
    <?php if (isset($_SESSION['login_id']) && $_SESSION['login_id'] == 1): ?>
    <div id="memberSelectorOverlay" onclick="closeMemberSelector()"></div>
    <div id="memberSelectorDrawer">
        <div class="msd-header">
            <h3><i class="fa fa-users"></i> Select Member</h3>
            <button class="msd-close-btn" onclick="closeMemberSelector()">&times;</button>
        </div>
        <div class="msd-search-wrap">
            <div class="msd-search-inner">
                <span class="msd-search-icon"><i class="fa fa-search"></i></span>
                <input type="text" id="msd_search" placeholder="Search by name or phone…" oninput="msdSearch()">
            </div>
        </div>
        <div class="msd-list" id="msd_list">
            <div class="msd-empty"><i class="fa fa-spinner fa-spin"></i> Loading members…</div>
        </div>
        <div class="msd-pagination" id="msd_pagination"></div>
    </div>
    <?php endif; ?>

    <!-- modal for setoff date/transaction type starts -->
    <div class="modal inmodal" id="setOffModel" tabindex="-1" role="dialog" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content animated bounceInRight" style="border-radius: 20px; overflow: hidden; border: none; box-shadow: var(--shadow-lg);">
                <form method="POST" id="setoff_form">
                    <div class="modal-header" style="background: var(--primary-gradient, linear-gradient(135deg, #6366f1 0%, #4f46e5 100%)); padding: 24px 30px; color: #ffffff; text-align: left;">
                        <button type="button" class="close" data-dismiss="modal" style="color: #ffffff; opacity: 0.8; font-size: 24px;" onclick="closesetOffform();">&times;</button>
                        <h3 style="margin: 0; font-weight: 800; font-size: 20px; letter-spacing: -0.5px;"><i class="fa fa-exchange"></i> Set-Off Cash/Bank</h3>
                        <p style="margin: 4px 0 0 0; opacity: 0.9; font-size: 13.5px;">Finalize date and transaction parameters for chronological allocation</p>
                    </div>

                    <div class="modal-body" style="padding: 30px; background: var(--card-bg, #ffffff);">
                        <div class="form-group">
                            <label>Payment Date</label>
                            <input type="date" id="setoff_date" name="setoff_date" class="form-control">
                        </div>
                        
                        <div class="form-group">
                            <label>Transaction Type</label>
                            <select class="dropdown form-control" name="selected_transaction" id="selected_transaction_type">
                                <?php foreach (TransactionType::all() as $key => $label): ?>
                                    <option value="<?= $key ?>"><?= htmlspecialchars($label) ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>

                        <div class="form-group" style="margin-top: 15px;">
                            <label style="display: block; font-weight: 600; margin-bottom: 6px;">Upload Bill Photo (Optional)</label>
                            <div class="custom-file" style="position: relative;">
                                <input type="file" id="bill_photo" name="bill_photo" class="form-control" accept="image/*,application/pdf" style="padding: 6px 12px; border-radius: 8px;">
                            </div>
                        </div>
                    </div>
                    
                    <div class="modal-footer" style="background: var(--card-bg, #ffffff); border-top: 1px solid var(--border-color, #e2e8f0); padding: 18px 30px; display: flex; justify-content: flex-end; gap: 12px;">
                        <button type="button" class="btn btn-white" style="border-radius: 10px; font-weight: 700; padding: 8px 16px;" onclick="closesetOffform();">Close</button>
                        <button type="button" class="btn btn-primary" style="border-radius: 10px; font-weight: 700; padding: 8px 20px;" onclick="setOffReceiveble();">Save & Settle</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
    <!-- modal for setoff date/transaction type ends -->

    <!-- setoff form for wallet start -->
    <div class="modal inmodal" id="setOffwalletModel" tabindex="-1" role="dialog" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content animated bounceInRight" style="border-radius: 20px; overflow: hidden; border: none; box-shadow: var(--shadow-lg);">
                <form method="POST" id="setoff_form_wallet">
                    <div class="modal-header" style="background: var(--primary-gradient, linear-gradient(135deg, #6366f1 0%, #4f46e5 100%)); padding: 24px 30px; color: #ffffff; text-align: left;">
                        <button type="button" class="close" data-dismiss="modal" style="color: #ffffff; opacity: 0.8; font-size: 24px;" onclick="closesetOffformforWallet();">&times;</button>
                        <h3 style="margin: 0; font-weight: 800; font-size: 20px; letter-spacing: -0.5px;"><i class="fa fa-wallet"></i> Set-Off From Wallet</h3>
                        <p style="margin: 4px 0 0 0; opacity: 0.9; font-size: 13.5px;">Confirm date and wallet deduction parameters</p>
                    </div>

                    <div class="modal-body" style="padding: 30px; background: var(--card-bg, #ffffff);">
                        <div class="form-group">
                            <label>Deduction Date</label>
                            <input type="date" id="setoff_date_wallet" name="setoff_date_wallet" class="form-control">
                        </div>
                        
                        <div class="form-group">
                            <label>Transaction Type</label>
                            <select class="dropdown form-control" name="selected_transaction" id="selected_transaction_type_wallet">
                                <?php foreach (TransactionType::all() as $key => $label): ?>
                                    <option value="<?= $key ?>"><?= htmlspecialchars($label) ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                    </div>
                    
                    <div class="modal-footer" style="background: var(--card-bg, #ffffff); border-top: 1px solid var(--border-color, #e2e8f0); padding: 18px 30px; display: flex; justify-content: flex-end; gap: 12px;">
                        <button type="button" class="btn btn-white" style="border-radius: 10px; font-weight: 700; padding: 8px 16px;" onclick="closesetOffformforWallet();">Close</button>
                        <button type="button" class="btn btn-primary" style="border-radius: 10px; font-weight: 700; padding: 8px 20px;" onclick="setOffReceivebleFromWallet();">Save & Settle</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
    <!-- setoff form for wallet end -->

    <!-- edit receivable modal start -->
    <div class="modal inmodal" id="receivebleModel" tabindex="-1" role="dialog" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content animated bounceInRight" style="border-radius: 20px; overflow: hidden; border: none; box-shadow: var(--shadow-lg);">
                <form method="POST" id="receiveble_form">
                    <div class="modal-header" style="background: var(--primary-gradient, linear-gradient(135deg, #6366f1 0%, #4f46e5 100%)); padding: 24px 30px; color: #ffffff; text-align: left;">
                        <button type="button" class="close" data-dismiss="modal" style="color: #ffffff; opacity: 0.8; font-size: 24px;" onclick="closeaddRecieveble();">&times;</button>
                        <h3 style="margin: 0; font-weight: 800; font-size: 20px; letter-spacing: -0.5px;"><i class="fa fa-pencil"></i> Edit Receivable Amount</h3>
                        <p style="margin: 4px 0 0 0; opacity: 0.9; font-size: 13.5px;">Modify date, amount or description parameters</p>
                    </div>

                    <div class="modal-body" style="padding: 30px; background: var(--card-bg, #ffffff);">
                        <div class="form-group">
                            <label>Date</label>
                            <input type="date" id="rec_date" name="rec_date" class="form-control">
                        </div>
                        <div class="form-group">
                            <label>Amount</label>
                            <input type="number" id="rec_amount" name="amount" placeholder="Receivable amount" class="form-control">
                        </div>
                        <div class="form-group">
                            <label>Description</label>
                            <textarea id="rec_discription" name="discription" rows="3" placeholder="Maximum 150 characters" class="form-control"></textarea>
                        </div>
                    </div>
                    
                    <div class="modal-footer" style="background: var(--card-bg, #ffffff); border-top: 1px solid var(--border-color, #e2e8f0); padding: 18px 30px; display: flex; justify-content: flex-end; gap: 12px;">
                        <button type="button" class="btn btn-white" style="border-radius: 10px; font-weight: 700; padding: 8px 16px;" onclick="closeaddRecieveble();">Close</button>
                        <button type="button" class="btn btn-primary" style="border-radius: 10px; font-weight: 700; padding: 8px 20px;" onclick="saveReceiveble();">Save</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
    <!-- edit receivable modal end -->

    <!-- Add Custom Guest Fee Modal Desktop -->
    <div class="modal inmodal" id="addGuestFeeModalDesktop" tabindex="-1" role="dialog" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content animated bounceInRight" style="border-radius:20px; overflow:hidden; border:none; box-shadow: 0 25px 50px -12px rgba(0, 0, 0, 0.25);">
                <div class="modal-header" style="background: linear-gradient(135deg, #f59e0b 0%, #d97706 100%); padding: 20px 24px; color: white;">
                    <button type="button" class="close" data-dismiss="modal" style="color:white; opacity:0.9; font-size:24px;">&times;</button>
                    <h4 class="modal-title" style="font-weight:800; font-size:18px; margin:0;"><i class="fa fa-plus-circle"></i> Add Custom Guest Fee</h4>
                </div>
                <div class="modal-body" style="padding: 24px;">
                    <div class="form-group" style="margin-bottom:16px;">
                        <label style="font-size:12px; font-weight:800; color:#64748b; text-transform:uppercase;">Fee Amount (₹)</label>
                        <input type="number" id="txt_desktop_guest_fee_amt" class="form-control" placeholder="e.g. 250" style="border-radius:12px; height:46px; font-weight:800; font-size:16px;">
                    </div>
                    <div class="form-group" style="margin-bottom:16px;">
                        <label style="font-size:12px; font-weight:800; color:#64748b; text-transform:uppercase;">Date</label>
                        <input type="date" id="txt_desktop_guest_fee_date" class="form-control" value="<?php echo date('Y-m-d'); ?>" style="border-radius:12px; height:46px; font-weight:700;">
                    </div>
                    <div class="form-group" style="margin-bottom:16px;">
                        <label style="font-size:12px; font-weight:800; color:#64748b; text-transform:uppercase;">Description / Reason</label>
                        <input type="text" id="txt_desktop_guest_fee_desc" class="form-control" placeholder="e.g. Guest Daily Play Fee" value="Guest Play Fee" style="border-radius:12px; height:46px; font-weight:600;">
                    </div>
                    <button type="button" onclick="submitCustomGuestFeeDesktop()" class="btn btn-block" style="background: linear-gradient(135deg, #f59e0b 0%, #d97706 100%); color:white; font-weight:800; height:46px; border-radius:14px; border:none; margin-top:10px;">
                        <i class="fa fa-check"></i> Post Guest Fee Receivable
                    </button>
                </div>
            </div>
        </div>
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

    <script>
        function openAddGuestFeeModalDesktop() {
            $('#addGuestFeeModalDesktop').modal('show');
        }
        function submitCustomGuestFeeDesktop() {
            let amt = parseFloat($('#txt_desktop_guest_fee_amt').val() || 0);
            let desc = $('#txt_desktop_guest_fee_desc').val() || 'Guest Custom Fee';
            let dt = $('#txt_desktop_guest_fee_date').val() || '<?php echo date('Y-m-d'); ?>';
            let mId = <?php echo (int)$member_id; ?>;

            if (amt <= 0) {
                if (typeof alertinfo === 'function') alertinfo("Please enter a valid fee amount.");
                else alert("Please enter a valid fee amount.");
                return;
            }
            if (mId <= 0) {
                if (typeof alertinfo === 'function') alertinfo("Please select a member first.");
                else alert("Please select a member first.");
                return;
            }

            $.post('api/fees_receiveble.php', {
                action: 'add_custom_guest_receivable',
                member_id: mId,
                amount: amt,
                discription: desc,
                date: dt,
                head: 12
            }, function(res) {
                $('#addGuestFeeModalDesktop').modal('hide');
                if (typeof alertsuccess === 'function') {
                    alertsuccess('Guest fee receivable added successfully!');
                } else {
                    alert('Guest fee receivable added successfully!');
                }
                loadData($('#hdn_current_page').val());
            }).fail(function() {
                if (typeof alerterror === 'function') {
                    alerterror('Error adding guest fee receivable.');
                } else {
                    alert('Error adding guest fee receivable.');
                }
            });
        }
    </script>
</body>

</html>
