<?php
session_start();
include '../app_common/db_connect.php';
include '../app_common/auth_helper.php';

if (empty($_SESSION['login_id'])) {
    header("Location: ../index.php");
    exit();
}
?>
<!DOCTYPE html>
<html>

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>Shuttle Cock Stock Management</title>

    <link href="../css/bootstrap.min.css" rel="stylesheet">
    <link href="../font-awesome/css/font-awesome.css" rel="stylesheet">
    <link href="../css/animate.css" rel="stylesheet">
    <link href="../css/style.css" rel="stylesheet">
    <script src="https://cdnjs.cloudflare.com/ajax/libs/jquery/3.6.0/jquery.min.js"></script>
    <script src="../js/bootstrap.min.js"></script>
    <link href="../css/plugins/sweetalert/sweetalert.css" rel="stylesheet">
    <script src="../js/plugins/sweetalert/sweetalert.min.js"></script>
    <script src="../app_js/sweetalert-finez.js"></script>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <style>
        :root {
            --bg-neutral: #f8fafc;
            --card-bg: #ffffff;
            --text-main: #0f172a;
            --text-muted: #64748b;
            --border-color: #e2e8f0;
            --primary-accent: #4f46e5;
            --primary-hover: #4338ca;
            --sidebar-bg: #1e293b;
        }

        [data-theme="dark"] {
            --bg-neutral: #0f172a;
            --card-bg: #1e293b;
            --text-main: #f8fafc;
            --text-muted: #94a3b8;
            --border-color: #334155;
            --primary-accent: #6366f1;
            --primary-hover: #4f46e5;
            --sidebar-bg: #0f172a;
        }

        body {
            font-family: 'Inter', -apple-system, BlinkMacSystemFont, sans-serif !important;
            background-color: var(--bg-neutral) !important;
            color: var(--text-main);
            transition: background-color 0.3s ease, color 0.3s ease;
        }

        #page-wrapper {
            background-color: var(--bg-neutral) !important;
            min-height: 100vh;
        }

        /* Sidebar Styling */
        .navbar-static-side {
            background: var(--sidebar-bg) !important;
        }

        .profile-element {
            padding: 20px 15px;
            background: rgba(0, 0, 0, 0.15);
            margin-bottom: 10px;
        }

        .profile-element img {
            border: 2px solid var(--primary-accent);
        }

        .nav > li.active > a {
            background: linear-gradient(135deg, #4f46e5, #6366f1) !important;
            color: #ffffff !important;
            font-weight: 700;
            border-radius: 8px;
            margin: 4px 10px;
        }

        /* Page Heading Banner */
        .dash-page-heading {
            background: var(--card-bg);
            border-bottom: 1px solid var(--border-color);
            padding: 20px 30px;
            margin-bottom: 25px;
            display: flex;
            align-items: center;
            justify-content: space-between;
            flex-wrap: wrap;
            gap: 15px;
        }

        .dash-page-heading h2 {
            font-weight: 800;
            font-size: 24px;
            margin: 0 0 4px 0;
            color: var(--text-main);
            letter-spacing: -0.5px;
        }

        .dash-page-heading .breadcrumb {
            background: transparent;
            padding: 0;
            margin: 0;
            font-size: 13px;
        }

        /* Primary Call To Action Button */
        .btn-dash-primary {
            background: linear-gradient(135deg, #4f46e5 0%, #6366f1 100%);
            color: #ffffff !important;
            border: none;
            border-radius: 10px;
            padding: 10px 20px;
            font-weight: 700;
            font-size: 14px;
            box-shadow: 0 4px 14px rgba(79, 70, 229, 0.3);
            transition: all 0.2s ease;
        }

        .btn-dash-primary:hover {
            transform: translateY(-2px);
            box-shadow: 0 6px 20px rgba(79, 70, 229, 0.4);
        }

        /* Theme Toggle & Logout */
        .theme-toggle-btn {
            background: var(--card-bg);
            border: 1px solid var(--border-color);
            color: var(--text-main);
            border-radius: 8px;
            padding: 6px 14px;
            font-weight: 600;
            transition: all 0.2s ease;
        }

        .theme-toggle-btn:hover {
            background: var(--border-color);
        }

        /* Clean White Summary Metric Cards */
        .metric-card-clean {
            background: var(--card-bg);
            border-radius: 14px;
            padding: 20px 22px;
            border: 1px solid var(--border-color);
            border-left-width: 5px;
            box-shadow: 0 4px 20px -2px rgba(0, 0, 0, 0.05);
            transition: transform 0.25s ease, box-shadow 0.25s ease;
            margin-bottom: 20px;
        }

        .metric-card-clean:hover {
            transform: translateY(-3px);
            box-shadow: 0 10px 25px -4px rgba(0, 0, 0, 0.08);
        }

        .metric-card-emerald { border-left-color: #10b981; }
        .metric-card-cyan { border-left-color: #06b6d4; }
        .metric-card-amber { border-left-color: #f59e0b; }
        .metric-card-rose { border-left-color: #ec4899; }

        .metric-icon-box {
            width: 46px;
            height: 46px;
            border-radius: 12px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 20px;
        }

        .metric-icon-emerald { background: rgba(16, 185, 129, 0.12); color: #10b981; }
        .metric-icon-cyan { background: rgba(6, 182, 212, 0.12); color: #06b6d4; }
        .metric-icon-amber { background: rgba(245, 158, 11, 0.12); color: #f59e0b; }
        .metric-icon-rose { background: rgba(236, 72, 153, 0.12); color: #ec4899; }

        .metric-label {
            font-size: 12px;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 0.6px;
            color: var(--text-muted);
            margin-bottom: 2px;
        }

        .metric-value {
            font-size: 26px;
            font-weight: 800;
            color: var(--text-main);
            letter-spacing: -0.5px;
            line-height: 1.2;
        }

        /* Glass / Card Dashboard Container */
        .dash-card-box {
            background: var(--card-bg);
            border-radius: 14px;
            border: 1px solid var(--border-color);
            box-shadow: 0 4px 20px -2px rgba(0, 0, 0, 0.05);
            margin-bottom: 25px;
            overflow: hidden;
        }

        .dash-card-header {
            padding: 16px 24px;
            background: rgba(0, 0, 0, 0.02);
            border-bottom: 1px solid var(--border-color);
            display: flex;
            align-items: center;
            justify-content: space-between;
        }

        .dash-card-header h5 {
            margin: 0;
            font-size: 16px;
            font-weight: 700;
            color: var(--text-main);
        }

        /* Structured Data Table Styling */
        .custom-dashboard-table {
            margin-bottom: 0;
        }

        .custom-dashboard-table > thead > tr > th {
            background: var(--sidebar-bg);
            color: #ffffff;
            font-weight: 700;
            font-size: 12px;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            padding: 14px 16px;
            border: none;
        }

        .custom-dashboard-table > tbody > tr > td {
            padding: 14px 16px;
            vertical-align: middle;
            font-size: 13px;
            border-color: var(--border-color);
            color: var(--text-main);
        }

        .custom-dashboard-table > tbody > tr:nth-of-type(odd) {
            background-color: rgba(0, 0, 0, 0.015);
        }

        .custom-dashboard-table > tbody > tr:hover {
            background-color: rgba(79, 70, 229, 0.04) !important;
        }

        /* Icon-Only Action Buttons */
        .btn-action-icon {
            width: 32px;
            height: 32px;
            padding: 0;
            border-radius: 8px;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            font-size: 14px;
            transition: all 0.2s ease;
            margin: 0 2px;
        }

        .btn-action-icon:hover {
            transform: scale(1.1);
        }

        .btn-action-pay {
            background: rgba(16, 185, 129, 0.12);
            color: #10b981;
            border: 1px solid rgba(16, 185, 129, 0.3);
        }
        .btn-action-pay:hover { background: #10b981; color: #fff; }

        .btn-action-edit {
            background: rgba(79, 70, 229, 0.12);
            color: #4f46e5;
            border: 1px solid rgba(79, 70, 229, 0.3);
        }
        .btn-action-edit:hover { background: #4f46e5; color: #fff; }

        .btn-action-delete {
            background: rgba(220, 38, 38, 0.12);
            color: #dc2626;
            border: 1px solid rgba(220, 38, 38, 0.3);
        }
        .btn-action-delete:hover { background: #dc2626; color: #fff; }

        .btn-action-bill {
            background: rgba(6, 182, 212, 0.12);
            color: #06b6d4;
            border: 1px solid rgba(6, 182, 212, 0.3);
        }
        .btn-action-bill:hover { background: #06b6d4; color: #fff; }

        /* Print Button Styling */
        .btn-dash-print {
            background: #ffffff;
            color: #1e293b !important;
            border: 1.5px solid #cbd5e1;
            border-radius: 10px;
            padding: 9px 18px;
            font-weight: 700;
            font-size: 14px;
            box-shadow: 0 2px 6px rgba(0, 0, 0, 0.04);
            transition: all 0.2s ease;
            display: inline-flex;
            align-items: center;
            gap: 7px;
        }

        .btn-dash-print:hover {
            background: #f1f5f9;
            border-color: #94a3b8;
            color: #0f172a !important;
            transform: translateY(-1px);
        }

        /* Print only elements hidden by default on screen */
        .print-only-block { display: none; }
        .print-sig-block { display: none; }

        /* ==========================================================================
           PRINT STYLING (A4 Clean Inventory Statement for Print / PDF)
           ========================================================================== */
        @media print {
            @page {
                size: A4 landscape;
                margin: 10mm 10mm 12mm 10mm;
            }
            *, *::before, *::after {
                box-shadow: none !important;
                text-shadow: none !important;
            }
            html, body, #wrapper, #page-wrapper {
                background: #ffffff !important;
                color: #0f172a !important;
                margin: 0 !important;
                padding: 0 !important;
                width: 100% !important;
                font-family: 'Inter', -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, Arial, sans-serif !important;
                font-size: 8.5pt !important;
            }

            /* Hide screen controls, sidebar, navbar, action buttons, modals, and pagination */
            .navbar-static-side, .navbar-static-top, .dash-page-heading,
            .btn-dash-primary, .btn-dash-print, .theme-toggle-btn,
            .modal, .btn-action-icon, .btn-action-pay, .btn-action-edit, .btn-action-delete, .btn-action-bill,
            .custom-dashboard-table th:last-child, .custom-dashboard-table td:last-child,
            ul.pagination, .app-pagination, #app_pagination {
                display: none !important;
            }

            .wrapper-content {
                padding: 0 !important;
                margin: 0 !important;
                width: 100% !important;
            }

            /* Show Print Letterhead */
            .print-only-block {
                display: block !important;
                margin-bottom: 14px !important;
            }

            /* Metric Cards Layout in Print (4 columns horizontal grid) */
            .row {
                display: flex !important;
                flex-wrap: wrap !important;
                margin-right: -8px !important;
                margin-left: -8px !important;
            }
            .col-lg-3, .col-md-6 {
                width: 25% !important;
                float: left !important;
                padding-right: 8px !important;
                padding-left: 8px !important;
            }
            .col-lg-12 {
                width: 100% !important;
                float: none !important;
                padding-right: 8px !important;
                padding-left: 8px !important;
            }

            .metric-card-clean {
                border: 1px solid #cbd5e1 !important;
                border-left-width: 4px !important;
                box-shadow: none !important;
                padding: 8px 12px !important;
                margin-bottom: 12px !important;
                background: #f8fafc !important;
                -webkit-print-color-adjust: exact !important;
                print-color-adjust: exact !important;
            }
            .metric-label {
                font-size: 7pt !important;
                color: #475569 !important;
                font-weight: 700 !important;
            }
            .metric-value {
                font-size: 13pt !important;
                font-weight: 800 !important;
                color: #0f172a !important;
            }
            .metric-icon-box {
                display: none !important;
            }

            /* Card Boxes in Print */
            .dash-card-box {
                border: 1px solid #94a3b8 !important;
                box-shadow: none !important;
                margin-bottom: 14px !important;
                page-break-inside: auto;
                break-inside: auto;
            }
            .dash-card-header {
                background: #f1f5f9 !important;
                border-bottom: 1.5px solid #0f172a !important;
                padding: 6px 10px !important;
                -webkit-print-color-adjust: exact !important;
                print-color-adjust: exact !important;
            }
            .dash-card-header h5 {
                font-size: 9pt !important;
                font-weight: 800 !important;
                color: #0f172a !important;
                text-transform: uppercase !important;
                letter-spacing: 0.3px !important;
            }

            /* Tables in Print */
            .custom-dashboard-table {
                width: 100% !important;
                border-collapse: collapse !important;
                font-size: 7.5pt !important;
            }
            .custom-dashboard-table > thead > tr > th {
                background-color: #1e293b !important;
                color: #ffffff !important;
                -webkit-print-color-adjust: exact !important;
                print-color-adjust: exact !important;
                font-size: 7pt !important;
                font-weight: 800 !important;
                padding: 4px 6px !important;
                border: 1px solid #0f172a !important;
                text-transform: uppercase !important;
            }
            .custom-dashboard-table > tbody > tr > td {
                padding: 4px 6px !important;
                font-size: 7.5pt !important;
                color: #0f172a !important;
                border: 1px solid #cbd5e1 !important;
                vertical-align: middle !important;
            }
            .custom-dashboard-table tr {
                page-break-inside: avoid !important;
                break-inside: avoid !important;
            }
            .label {
                border: 1px solid #cbd5e1 !important;
                padding: 1px 4px !important;
                font-size: 6.5pt !important;
                font-weight: 700 !important;
                background: #f1f5f9 !important;
                color: #0f172a !important;
                -webkit-print-color-adjust: exact !important;
                print-color-adjust: exact !important;
            }

            /* Signatures in Print */
            .print-sig-block {
                display: flex !important;
                justify-content: space-between !important;
                margin-top: 30px !important;
                padding-top: 10px !important;
                page-break-inside: avoid !important;
                break-inside: avoid !important;
            }
            .print-sig-col {
                width: 28% !important;
                text-align: center !important;
            }
            .print-sig-line {
                border-top: 1.2px solid #475569 !important;
                margin-bottom: 5px !important;
            }
            .print-sig-title {
                font-size: 8pt !important;
                font-weight: 700 !important;
                color: #334155 !important;
                text-transform: uppercase !important;
            }
        }
    </style>
    <script>
        function toggleDashboardTheme() {
            var currentTheme = document.documentElement.getAttribute('data-theme');
            var newTheme = currentTheme === 'dark' ? 'light' : 'dark';
            document.documentElement.setAttribute('data-theme', newTheme);
            localStorage.setItem('dashboard_theme', newTheme);
            $('#theme_toggle_icon').attr('class', newTheme === 'dark' ? 'fa fa-sun-o text-warning' : 'fa fa-moon-o');
        }

        $(document).ready(function() {
            var savedTheme = localStorage.getItem('dashboard_theme') || 'light';
            document.documentElement.setAttribute('data-theme', savedTheme);
            $('#theme_toggle_icon').attr('class', savedTheme === 'dark' ? 'fa fa-sun-o text-warning' : 'fa fa-moon-o');
            $('[data-toggle="tooltip"]').tooltip();
        });
    </script>

    <script>
        var groupsList = [];

        $(document).ready(function() {          
            loadGroupsForModal();
            loadData(1);

            // Real-time calculation listeners in Modal
            $('#no_of_boxes, #shuttles_per_box, #total_cost_input').on('input change', function() {
                calculatePreview();
            });

            $('#stock_form').on('submit', function(e) {
                e.preventDefault();
                saveStock();
            });
        });  

        function loadGroupsForModal() {
            if ($('#group_id option').length === 0 && window.groupsList && window.groupsList.length > 0) {
                var groupOptions = "";
                if (window.isAllGroupsGlobal) {
                    groupOptions += "<option value='0'>General / All Groups (Shared Pool)</option>";
                }
                for (var g = 0; g < window.groupsList.length; g++) {
                    groupOptions += "<option value='" + window.groupsList[g].id + "'>" + window.groupsList[g].group_name + "</option>";
                }
                $('#group_id').html(groupOptions);
            }
        }

        function calculatePreview() {
            let boxes = parseInt($('#no_of_boxes').val()) || 0;
            let shuttlesPerBox = parseInt($('#shuttles_per_box').val()) || 0;
            let totalCost = parseFloat($('#total_cost_input').val()) || 0.00;

            let totalShuttles = boxes * shuttlesPerBox;
            let boxPrice = boxes > 0 ? (totalCost / boxes) : 0.00;
            let perShuttlePrice = totalShuttles > 0 ? (totalCost / totalShuttles) : 0.00;

            $('#preview_total_shuttles').text(totalShuttles);
            $('#preview_box_price').text('₹' + boxPrice.toFixed(2));
            $('#preview_per_shuttle_price').text('₹' + perShuttlePrice.toFixed(2));
        }

        function loadData(page) {
            $('#hdn_current_page').val(page);
            $.ajax({               
               type: "POST",
               url: "api/shuttle_cock_stock.php",
               data: {
                   action: 'load_data',
                   page: page
               },
               success: function(data) {
                    try {
                        var obj = typeof data === 'string' ? JSON.parse(data) : data;
                        if (!obj || !obj[0]) return;
                        var summary = obj[0];
                        var list = obj[1] || [];

                    window.groupsList = summary.groups || [];
                    window.isAllGroupsGlobal = summary.is_all_groups;

                    // Populate Group Select in Modal
                    var groupOptions = "";
                    if (summary.is_all_groups) {
                        groupOptions += "<option value='0'>General / All Groups (Shared Pool)</option>";
                    }
                    for (var g = 0; g < window.groupsList.length; g++) {
                        groupOptions += "<option value='" + window.groupsList[g].id + "'>" + window.groupsList[g].group_name + "</option>";
                    }
                    $('#group_id').html(groupOptions);

                    // Render Overall Summary KPI Cards
                    $('#stat_stock_remaining').text(summary.stock_remaining + ' Shuttles');
                    $('#stat_boxes_in').text(summary.total_boxes_in + ' Boxes (' + summary.total_shuttles_in + ' Shuttles)');
                    $('#stat_total_cost').text('₹' + parseFloat(summary.total_cost_in).toFixed(2));
                    $('#stat_unit_price').text('₹' + parseFloat(summary.latest_unit_price).toFixed(2));

                    // Render Group-Wise Stock Breakdown Table
                    var gBreakdown = summary.group_breakdown || [];
                    var gHtm = "<div class='table-responsive'><table class='table custom-dashboard-table'>";
                    gHtm += "<thead><tr>";
                    gHtm += "<th>Group Name</th>";
                    gHtm += "<th>Allocated Inward</th>";
                    gHtm += "<th>Shuttles Used</th>";
                    gHtm += "<th>Remaining Stock</th>";
                    gHtm += "<th>Usage Cost (₹)</th>";
                    gHtm += "</tr></thead><tbody>";

                    for (var gb = 0; gb < gBreakdown.length; gb++) {
                        var b = gBreakdown[gb];
                        gHtm += "<tr>";
                        gHtm += "<td><strong><i class='fa fa-users text-primary' style='margin-right: 6px;'></i> " + b.group_name + "</strong></td>";
                        gHtm += "<td><span class='label label-info' style='border-radius: 6px; padding: 4px 8px;'>" + b.shuttles_in + " Shuttles</span></td>";
                        gHtm += "<td><span class='label label-warning' style='border-radius: 6px; padding: 4px 8px;'>" + b.shuttles_used + " Used</span></td>";
                        gHtm += "<td><span class='label label-primary' style='border-radius: 6px; padding: 4px 8px; font-weight: bold;'>" + b.shuttles_remaining + " Remaining</span></td>";
                        gHtm += "<td><strong class='text-navy' style='font-size: 14px;'>₹" + parseFloat(b.total_used_cost).toFixed(2) + "</strong></td>";
                        gHtm += "</tr>";
                    }
                    gHtm += "</tbody></table></div>";
                    $('#group_breakdown_container').html(gHtm);

                    var totalrows = summary.total_rows;
                    var htm = "";

                    htm += "<div class='table-responsive'>";
                    htm += "<table class='table custom-dashboard-table'>";
                    htm += "<thead>";
                    htm += "<tr>";
                    htm += "<th>No</th>";
                    htm += "<th>Purchase Date</th>";
                    htm += "<th>Group</th>";
                    htm += "<th>Brand</th>";
                    htm += "<th>Boxes</th>";
                    htm += "<th>Shuttles / Box</th>";
                    htm += "<th>Total Shuttles</th>";
                    htm += "<th>Box Price</th>";
                    htm += "<th>Per Shuttle</th>";
                    htm += "<th>Total Cost</th>";
                    htm += "<th>Supplier / Invoice</th>";
                    htm += "<th>Payment Status</th>";
                    htm += "<th class='text-center'>Action</th>";
                    htm += "</tr>";
                    htm += "</thead>";
                    htm += "<tbody>";

                    if (list.length === 0) {
                        htm += "<tr><td colspan='13' class='text-center text-muted' style='padding: 24px;'>No stock inward records found</td></tr>";
                    } else {
                        for (var i = 0; i < list.length; i++) {
                            var slno = ((page - 1) * 8) + (i + 1);
                            var r = list[i];

                            var supp = r.supplier_name ? r.supplier_name : '-';
                            if (r.invoice_no) {
                                supp += ' <small class="text-muted">(Inv: ' + r.invoice_no + ')</small>';
                            }
                            var billBtn = "";
                            if (r.bill_file) {
                                billBtn = '<a href="../image_upload/shuttle_cock_bills/' + r.bill_file + '" target="_blank" class="btn-action-icon btn-action-bill" data-toggle="tooltip" title="View Bill Invoice"><i class="fa fa-file-text-o"></i></a>';
                            }

                            var statusBadge = "";
                            if (r.payment_status === 'paid') {
                                statusBadge = "<span class='label label-primary' style='background:#059669; font-size:11px; border-radius: 6px; padding: 4px 8px;'><i class='fa fa-check-circle'></i> Paid</span>";
                            } else if (r.payment_status === 'partial') {
                                statusBadge = "<span class='label label-warning' style='background:#d97706; font-size:11px; border-radius: 6px; padding: 4px 8px;'><i class='fa fa-clock-o'></i> Partial<br>(Paid: ₹" + r.paid_amount.toFixed(2) + ")</span>";
                            } else {
                                statusBadge = "<span class='label label-danger' style='background:#dc2626; font-size:11px; border-radius: 6px; padding: 4px 8px;'><i class='fa fa-exclamation-circle'></i> Unpaid<br>(Due: ₹" + r.due_amount.toFixed(2) + ")</span>";
                            }

                            htm += "<tr>";
                            htm += "<td><span style='color: var(--text-muted); font-weight: 600;'>" + slno + "</span></td>";
                            htm += "<td><strong>" + r.purchase_date + "</strong>";
                            if (r.is_fee_locked) {
                                htm += " <span class='label label-warning' style='background:#f59e0b; color:#fff; border-radius:4px; padding:2px 5px; font-size:10px; font-weight:700; white-space:nowrap;' data-toggle='tooltip' title='" + (r.fee_lock_reason || 'Monthly fee generated') + "'><i class='fa fa-lock'></i> Fee Generated</span>";
                            }
                            htm += "</td>";
                            htm += "<td><span class='label label-success' style='border-radius: 6px; padding: 4px 8px;'>" + r.group_name + "</span></td>";
                            htm += "<td><strong>" + r.brand_name + "</strong></td>";
                            htm += "<td><span class='label label-info' style='border-radius: 6px; padding: 4px 8px;'>" + r.no_of_boxes + " Boxes</span></td>";
                            htm += "<td>" + r.shuttles_per_box + "</td>";
                            htm += "<td><span class='label label-primary' style='border-radius: 6px; padding: 4px 8px; font-weight: bold;'>" + r.total_shuttles + "</span></td>";
                            htm += "<td>₹" + parseFloat(r.box_price).toFixed(2) + "</td>";
                            htm += "<td><strong class='text-success'>₹" + parseFloat(r.per_shuttle_price).toFixed(2) + "</strong></td>";
                            htm += "<td><strong style='font-size: 14px;'>₹" + parseFloat(r.total_cost).toFixed(2) + "</strong>";
                            if (r.is_fee_locked) {
                                htm += " <i class='fa fa-lock text-warning' data-toggle='tooltip' title='Purchase amount locked - monthly fee generated' style='margin-left: 4px;'></i>";
                            }
                            htm += "</td>";
                            htm += "<td>" + supp + "</td>";
                            htm += "<td>" + statusBadge + "</td>";
                            htm += "<td class='text-center' style='white-space: nowrap;'>";
                            if (billBtn) {
                                htm += billBtn + " ";
                            }
                            if (r.payment_status !== 'paid') {
                                htm += "<button type='button' class='btn-action-icon btn-action-pay' data-toggle='tooltip' title='Record Voucher Payment' onclick='openPaymentModal(" + JSON.stringify(r) + ")'><i class='fa fa-money'></i></button> ";
                            }
                            var editTitle = r.is_fee_locked ? "Edit Details (Amount Locked - Fee Generated)" : "Edit Stock Purchase";
                            htm += "<button type='button' class='btn-action-icon btn-action-edit' data-toggle='tooltip' title='" + editTitle + "' onclick='editStock(" + JSON.stringify(r) + ")'><i class='fa fa-edit'></i></button> ";

                            if (r.is_fee_locked) {
                                var safeReason = (r.fee_lock_reason || "Monthly fee generated. Shuttle purchase cannot be deleted.").replace(/'/g, "\\'");
                                htm += "<button type='button' class='btn-action-icon btn-action-delete' style='opacity:0.4; cursor:not-allowed;' data-toggle='tooltip' title='Cannot Delete: " + safeReason + "' onclick='alertwarning(\"" + safeReason + "\")'><i class='fa fa-lock'></i></button>";
                            } else {
                                htm += "<button type='button' class='btn-action-icon btn-action-delete' data-toggle='tooltip' title='Delete Stock Purchase' onclick='deleteStock(" + r.id + ")'><i class='fa fa-trash'></i></button>";
                            }
                            htm += "</td>";
                            htm += "</tr>";
                        }
                    }

                    htm += "</tbody>";
                    htm += "</table>";
                    htm += "</div>";

                    $('#table_container').html(htm);
                    $('[data-toggle="tooltip"]').tooltip();
                    var htmpage = paginate(totalrows, page);
                    $('#table_container').append(htmpage);
                    } catch(err) {
                        console.error('Error rendering data:', err);
                    }
                },
                error: function(xhr, status, error) {
                    console.log('AJAX Error:', error);
                }
            });
        }

        function openAddModal() {
            loadGroupsForModal();
            $('#hdn_id').val(0);
            $('#modal_title').text('Add Shuttle Cock Stock Inward');
            $('#modal_fee_locked_alert').hide();
            $('#cost_locked_hint').hide();

            // Re-enable inputs
            $('#purchase_date').prop('readonly', false).css({'background-color': '', 'cursor': ''});
            $('#group_id').prop('disabled', false).css({'background-color': '', 'cursor': ''});
            $('#brand_name').prop('readonly', false).css({'background-color': '', 'cursor': ''});
            $('#no_of_boxes').prop('readonly', false).css({'background-color': '', 'cursor': ''});
            $('#shuttles_per_box').prop('readonly', false).css({'background-color': '', 'cursor': ''});
            $('#total_cost_input').prop('readonly', false).css({'background-color': '#ecfdf5', 'border-color': '#10b981', 'cursor': ''});

            $('#purchase_date').val(new Date().toISOString().slice(0, 10));
            var defaultG = $('#group_id option:first').val() || 0;
            $('#group_id').val(defaultG);
            $('#brand_name').val('Yonex AS-20');
            $('#no_of_boxes').val(1);
            $('#shuttles_per_box').val(12);
            $('#total_cost_input').val('');
            $('#supplier_name').val('');
            $('#invoice_no').val('');
            $('#bill_file').val('');
            $('#current_bill_lbl').html('');
            $('#notes').val('');
            calculatePreview();
            $('#stockModal').modal('show');
        }

        function editStock(item) {
            $('#hdn_id').val(item.id);
            $('#modal_title').text('Edit Shuttle Cock Stock Inward');
            $('#purchase_date').val(item.purchase_date);
            $('#group_id').val(item.group_id || 0);
            $('#brand_name').val(item.brand_name);
            $('#no_of_boxes').val(item.no_of_boxes);
            $('#shuttles_per_box').val(item.shuttles_per_box);
            $('#total_cost_input').val(item.total_cost);
            $('#supplier_name').val(item.supplier_name);
            $('#invoice_no').val(item.invoice_no);
            $('#bill_file').val('');
            if (item.bill_file) {
                $('#current_bill_lbl').html('<a href="../image_upload/shuttle_cock_bills/' + item.bill_file + '" target="_blank" class="text-info"><i class="fa fa-paperclip"></i> Existing Bill File</a>');
            } else {
                $('#current_bill_lbl').html('');
            }
            $('#notes').val(item.notes);

            if (item.is_fee_locked) {
                $('#modal_fee_locked_alert').show();
                $('#modal_fee_locked_reason').text(item.fee_lock_reason || 'Monthly fee for this month has been generated.');
                $('#cost_locked_hint').show();

                $('#purchase_date').prop('readonly', true).css({'background-color': '#f1f5f9', 'cursor': 'not-allowed'});
                $('#group_id').prop('disabled', true).css({'background-color': '#f1f5f9', 'cursor': 'not-allowed'});
                $('#no_of_boxes').prop('readonly', true).css({'background-color': '#f1f5f9', 'cursor': 'not-allowed'});
                $('#shuttles_per_box').prop('readonly', true).css({'background-color': '#f1f5f9', 'cursor': 'not-allowed'});
                $('#total_cost_input').prop('readonly', true).css({'background-color': '#fef2f2', 'border-color': '#f87171', 'cursor': 'not-allowed'});
            } else {
                $('#modal_fee_locked_alert').hide();
                $('#cost_locked_hint').hide();

                $('#purchase_date').prop('readonly', false).css({'background-color': '', 'cursor': ''});
                $('#group_id').prop('disabled', false).css({'background-color': '', 'cursor': ''});
                $('#no_of_boxes').prop('readonly', false).css({'background-color': '', 'cursor': ''});
                $('#shuttles_per_box').prop('readonly', false).css({'background-color': '', 'cursor': ''});
                $('#total_cost_input').prop('readonly', false).css({'background-color': '#ecfdf5', 'border-color': '#10b981', 'cursor': ''});
            }

            calculatePreview();
            $('#stockModal').modal('show');
        }

        function saveStock() {
            let boxes = parseInt($('#no_of_boxes').val()) || 0;
            let shuttlesPerBox = parseInt($('#shuttles_per_box').val()) || 0;
            let totalCost = parseFloat($('#total_cost_input').val()) || 0;

            if (boxes <= 0) {
                alertwarning('Please enter valid number of boxes');
                return;
            }
            if (shuttlesPerBox <= 0) {
                alertwarning('Please enter valid shuttles per box');
                return;
            }
            if (totalCost <= 0) {
                alertwarning('Please enter valid Total Purchase Amount');
                return;
            }

            let pDate = $('#purchase_date').val() || new Date().toISOString().slice(0, 10);

            var formData = new FormData();
            formData.append('action', 'save_stock_in');
            formData.append('id', $('#hdn_id').val());
            formData.append('purchase_date', pDate);
            formData.append('group_id', $('#group_id').val());
            formData.append('brand_name', $('#brand_name').val());
            formData.append('no_of_boxes', boxes);
            formData.append('shuttles_per_box', shuttlesPerBox);
            formData.append('total_cost', totalCost);
            formData.append('supplier_name', $('#supplier_name').val());
            formData.append('invoice_no', $('#invoice_no').val());
            formData.append('notes', $('#notes').val());

            var fileInput = $('#bill_file')[0];
            if (fileInput && fileInput.files.length > 0) {
                formData.append('bill_file', fileInput.files[0]);
            }

            $.ajax({
                type: "POST",
                url: "api/shuttle_cock_stock.php",
                data: formData,
                processData: false,
                contentType: false,
                success: function(response) {
                    try {
                        var res = typeof response === 'string' ? JSON.parse(response) : response;
                        if (res.status === 'success') {
                            $('#stockModal').modal('hide');
                            alertsuccess(res.message || 'Stock Inward Saved Successfully');
                            loadData($('#hdn_current_page').val());
                        } else {
                            alertwarning(res.message || 'Error saving record');
                        }
                    } catch(e) {
                        $('#stockModal').modal('hide');
                        alertsuccess('Stock Inward Saved Successfully');
                        loadData($('#hdn_current_page').val());
                    }
                },
                error: function(xhr) {
                    var msg = 'Error saving stock inward record';
                    try {
                        var errObj = JSON.parse(xhr.responseText);
                        if (errObj && errObj.message) msg = errObj.message;
                    } catch(e){}
                    alertwarning(msg);
                }
            });
        }

        function deleteStock(id) {
            swal({
                title: "Are you sure?",
                text: "You want to delete this stock entry?",
                type: "warning",
                showCancelButton: true,
                confirmButtonColor: "#DD6B55",
                confirmButtonText: "Yes, delete it!",
                closeOnConfirm: true
            }, function() {
                $.ajax({
                    type: "POST",
                    url: "api/shuttle_cock_stock.php",
                    data: { action: 'delete_stock_in', id: id },
                    success: function(response) {
                        try {
                            var res = typeof response === 'string' ? JSON.parse(response) : response;
                            if (res.status === 'success') {
                                alertsuccess(res.message || 'Deleted Successfully');
                                loadData($('#hdn_current_page').val());
                            } else {
                                alertwarning(res.message || 'Error deleting record');
                            }
                        } catch(e) {
                            alertsuccess('Deleted Successfully');
                            loadData($('#hdn_current_page').val());
                        }
                    },
                    error: function(xhr) {
                        var msg = 'Error deleting record';
                        try {
                            var errObj = JSON.parse(xhr.responseText);
                            if (errObj && errObj.message) msg = errObj.message;
                        } catch(e){}
                        alertwarning(msg);
                    }
                });
            });
        }

        function openPaymentModal(item) {
            $('#pay_stock_id').val(item.id);
            $('#pay_payable_id').val(item.payable_id || 0);
            $('#pay_supplier_info').text((item.supplier_name || 'Stock Purchase') + (item.invoice_no ? ' (Inv: ' + item.invoice_no + ')' : ''));
            $('#pay_total_cost').text('₹' + parseFloat(item.total_cost).toFixed(2));
            $('#pay_already_paid').text('₹' + parseFloat(item.paid_amount).toFixed(2));
            
            let due = parseFloat(item.due_amount) || 0;
            $('#pay_due_amount').text('₹' + due.toFixed(2));
            
            $('#pay_date').val(new Date().toISOString().slice(0, 10));
            $('#pay_amount').val(due > 0 ? due.toFixed(2) : '');
            $('#pay_mode').val(1);
            $('#pay_notes').val('');
            
            $('#paymentRecordModal').modal('show');
        }

        function saveStockPayment() {
            let amount = parseFloat($('#pay_amount').val()) || 0;
            if (amount <= 0) {
                alertwarning('Please enter a valid payment amount');
                return;
            }

            $.ajax({
                type: "POST",
                url: "api/shuttle_cock_stock.php",
                data: {
                    action: 'record_stock_payment',
                    stock_id: $('#pay_stock_id').val(),
                    payable_id: $('#pay_payable_id').val(),
                    payment_date: $('#pay_date').val(),
                    amount_paid: amount,
                    transaction_type: $('#pay_mode').val(),
                    notes: $('#pay_notes').val()
                },
                success: function(response) {
                    try {
                        var res = typeof response === 'string' ? JSON.parse(response) : response;
                        if (res.status === 'success') {
                            $('#paymentRecordModal').modal('hide');
                            alertsuccess(res.message || 'Payment Recorded Successfully');
                            loadData($('#hdn_current_page').val());
                        } else {
                            alertwarning(res.message || 'Error recording payment');
                        }
                    } catch(e) {
                        $('#paymentRecordModal').modal('hide');
                        alertsuccess('Payment Recorded Successfully');
                        loadData($('#hdn_current_page').val());
                    }
                },
                error: function(xhr) {
                    alertwarning('Error recording payment');
                }
            });
        }
    </script>
    <script src="../app_pagination/pagination.js"></script>
    <script src="../app_menu/menu.js"></script>
</head>

<body>
    <input type="hidden" id="hdn_current_page" value="1">
    <input type="hidden" id="hdn_id" value="0">
    
    <div id="wrapper">
        <!-- Sidebar Navigation -->
        <nav class="navbar-default navbar-static-side" role="navigation">
            <div class="dropdown profile-element text-center">
                <span><img alt="image" class="img-circle" src="../img/customer.png" style="padding-top: 20px;"/></span>
                <span class="clear"><span class="block m-t-xs"><strong class="font-bold"><?php echo $_SESSION['name'] ?? 'User'; ?></strong></span></span>
            </div>
            <div class="sidebar-collapse" id="divMenuContainer">
                <!-- Navigation menu injected via menu.js -->
            </div>
        </nav>

        <div id="page-wrapper" class="gray-bg">
            <!-- Header Bar -->
            <div class="row border-bottom">
                <nav class="navbar navbar-static-top" role="navigation" style="margin-bottom: 0; padding: 0 15px;">
                    <div class="navbar-header">
                        <a class="navbar-minimalize minimalize-styl-2 btn btn-primary" href="#" style="border-radius: 8px;"><i class="fa fa-bars"></i></a>
                    </div>
                    <ul class="nav navbar-top-links navbar-right" style="display: flex; align-items: center; gap: 10px; margin-right: 0;">
                        <li>
                            <button type="button" class="theme-toggle-btn" onclick="toggleDashboardTheme()" data-toggle="tooltip" title="Toggle Light/Dark Theme">
                                <i id="theme_toggle_icon" class="fa fa-moon-o"></i>
                            </button>
                        </li>
                        <li>
                            <a href="../app_login_manager/logout.php" style="color: #ef4444; font-weight: 600;">
                                <i class="fa fa-sign-out"></i> Log out
                            </a>
                        </li>
                    </ul>
                </nav>
            </div>

            <!-- Page Title Bar -->
            <div class="row wrapper border-bottom white-bg page-heading dash-page-heading">
                <div>
                    <h2><i class="fa fa-cubes" style="color: var(--primary-accent); margin-right: 8px;"></i> Shuttle Cock Stock Management</h2>
                    <ol class="breadcrumb">
                        <li><a href="dashboard.php">Home</a></li>
                        <li class="active"><strong>Shuttle Cock Stock</strong></li>
                    </ol>
                </div>
                <div style="display: flex; align-items: center; gap: 10px; flex-wrap: wrap;">
                    <button type="button" class="btn btn-dash-print" onclick="window.print()"><i class="fa fa-print"></i> Print Statement</button>
                    <button type="button" class="btn btn-dash-primary" onclick="openAddModal()"><i class="fa fa-plus"></i> Add Stock Inward</button>
                </div>
            </div>

            <!-- Content Area -->
            <div class="wrapper wrapper-content animated fadeInRight" style="padding-top: 10px;">

                <!-- Dedicated Print Header (Hidden on Screen, Shown on Print/PDF) -->
                <div class="print-only-block">
                    <div style="text-align:center; padding-bottom:8px; border-bottom:2px solid #1e3a8a; margin-bottom:12px;">
                        <div style="font-size:16pt; font-weight:800; color:#1e3a8a; letter-spacing:0.5px; text-transform:uppercase;">
                            YMCA Badminton Club Poovathussery
                        </div>
                        <div style="font-size:8.5pt; color:#64748b; margin-top:2px;">
                            Poovathussery, Parakkadavu P.O., Ernakulam Dist., Kerala &bull; Phone: +91 94951 83688
                        </div>
                        <div style="background-color:#1e3a8a; color:#ffffff; font-weight:700; font-size:10pt; padding:4px 8px; margin-top:6px; letter-spacing:0.5px; text-transform:uppercase; -webkit-print-color-adjust:exact; print-color-adjust:exact;">
                            Shuttle Cock Stock & Inventory Statement
                        </div>
                    </div>
                </div>

                <!-- Summary Metric Cards (Top of Main Area) -->
                <div class="row">
                    <div class="col-lg-3 col-md-6">
                        <div class="metric-card-clean metric-card-emerald">
                            <div style="display: flex; align-items: center; justify-content: space-between;">
                                <div>
                                    <div class="metric-label">Available Stock Balance</div>
                                    <div class="metric-value" id="stat_stock_remaining">0 Shuttles</div>
                                </div>
                                <div class="metric-icon-box metric-icon-emerald">
                                    <i class="fa fa-cubes"></i>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="col-lg-3 col-md-6">
                        <div class="metric-card-clean metric-card-cyan">
                            <div style="display: flex; align-items: center; justify-content: space-between;">
                                <div>
                                    <div class="metric-label">Total Inward Stock</div>
                                    <div class="metric-value" id="stat_boxes_in">0 Boxes</div>
                                </div>
                                <div class="metric-icon-box metric-icon-cyan">
                                    <i class="fa fa-shopping-cart"></i>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="col-lg-3 col-md-6">
                        <div class="metric-card-clean metric-card-amber">
                            <div style="display: flex; align-items: center; justify-content: space-between;">
                                <div>
                                    <div class="metric-label">Total Stock Value</div>
                                    <div class="metric-value" id="stat_total_cost">₹0.00</div>
                                </div>
                                <div class="metric-icon-box metric-icon-amber">
                                    <i class="fa fa-inr"></i>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="col-lg-3 col-md-6">
                        <div class="metric-card-clean metric-card-rose">
                            <div style="display: flex; align-items: center; justify-content: space-between;">
                                <div>
                                    <div class="metric-label">Latest Unit Box Price</div>
                                    <div class="metric-value" id="stat_unit_price">₹0.00</div>
                                </div>
                                <div class="metric-icon-box metric-icon-rose">
                                    <i class="fa fa-tag"></i>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Group-Wise Stock Balance Breakdown Box -->
                <div class="row">
                    <div class="col-lg-12">
                        <div class="dash-card-box">
                            <div class="dash-card-header">
                                <h5><i class="fa fa-users" style="color: var(--primary-accent); margin-right: 8px;"></i> Group-Wise Shuttle Cock Stock Breakdown</h5>
                            </div>
                            <div id="group_breakdown_container" style="padding: 0;">
                                <!-- Group Breakdown table injected dynamically -->
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Main Data Table Box -->
                <div class="row">
                    <div class="col-lg-12">
                        <div class="dash-card-box">
                            <div class="dash-card-header">
                                <h5><i class="fa fa-list-alt" style="color: var(--primary-accent); margin-right: 8px;"></i> Shuttle Cock Inward Purchases Log</h5>
                            </div>
                            <div id="table_container" style="padding: 0;">
                                <!-- Table injected dynamically -->
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Print Signatures Block -->
                <div class="print-sig-block">
                    <div class="print-sig-col">
                        <div class="print-sig-line"></div>
                        <div class="print-sig-title">Prepared By (In-Charge)</div>
                    </div>
                    <div class="print-sig-col">
                        <div class="print-sig-line"></div>
                        <div class="print-sig-title">Audited / Verified By</div>
                    </div>
                    <div class="print-sig-col">
                        <div class="print-sig-line"></div>
                        <div class="print-sig-title">President / Secretary</div>
                    </div>
                </div>

            </div>
        </div>

        <!-- Add/Edit Stock Modal -->
        <div class="modal inmodal" id="stockModal" tabindex="-1" role="dialog" aria-hidden="true">
            <div class="modal-dialog modal-lg">
                <div class="modal-content animated fadeIn">
                    <div class="modal-header">
                        <button type="button" class="close" data-dismiss="modal"><span aria-hidden="true">&times;</span></button>
                        <h4 class="modal-title" id="modal_title">Add Shuttle Cock Stock Inward</h4>
                    </div>
                    <form id="stock_form">
                        <div class="modal-body">
                            <!-- Fee Locked Warning Alert Banner -->
                            <div id="modal_fee_locked_alert" class="alert alert-warning" style="display: none; border-left: 4px solid #f59e0b; background-color: #fffbeb; color: #92400e; margin-bottom: 18px;">
                                <h5 style="margin: 0 0 5px 0; font-weight: 700;"><i class="fa fa-lock"></i> Shuttle Purchase Amount & Quantity Locked</h5>
                                <p style="margin: 0; font-size: 12.5px;" id="modal_fee_locked_reason"></p>
                                <div style="font-size: 11.5px; margin-top: 6px; opacity: 0.9;">
                                    Because monthly membership fees for this month have already been generated, the purchase date, group, box count, and total amount cannot be changed. You can still update supplier name, invoice number, bill upload, or notes.
                                </div>
                            </div>

                            <div class="row">
                                <div class="col-md-4 form-group">
                                    <label>Purchase Date *</label>
                                    <input type="date" id="purchase_date" class="form-control" required>
                                </div>
                                <div class="col-md-4 form-group">
                                    <label>Assign to Group *</label>
                                    <select id="group_id" class="form-control" required>
                                        <!-- Populated via AJAX -->
                                    </select>
                                </div>
                                <div class="col-md-4 form-group">
                                    <label>Brand / Shuttlecock Name *</label>
                                    <input type="text" id="brand_name" class="form-control" placeholder="e.g. Yonex AS-20" required>
                                </div>
                            </div>

                            <div class="row">
                                <div class="col-md-4 form-group">
                                    <label>Number of Boxes Purchased *</label>
                                    <input type="number" id="no_of_boxes" class="form-control" min="1" placeholder="e.g. 5" required>
                                </div>
                                <div class="col-md-4 form-group">
                                    <label>Shuttles per Box *</label>
                                    <input type="number" id="shuttles_per_box" class="form-control" min="1" placeholder="e.g. 12" required>
                                </div>
                                <div class="col-md-4 form-group">
                                    <label style="color: #059669; font-weight: bold;">Total Purchase Amount (₹) *</label>
                                    <input type="number" step="0.01" id="total_cost_input" class="form-control" min="0" placeholder="e.g. 6000.00" style="background-color: #ecfdf5; font-weight: bold; border: 2px solid #10b981;" required>
                                    <small id="cost_locked_hint" class="text-danger" style="display:none; font-weight: 600; margin-top: 4px;"><i class="fa fa-lock"></i> Amount locked: monthly fee generated</small>
                                </div>
                            </div>

                            <!-- Live Calculation Preview Card -->
                            <div class="alert alert-info" style="margin-top: 10px;">
                                <div class="row text-center">
                                    <div class="col-md-4">
                                        <strong>Total Shuttles Added:</strong> <span id="preview_total_shuttles" class="badge badge-primary" style="font-size: 13px;">0</span>
                                    </div>
                                    <div class="col-md-4">
                                        <strong>Calculated Box Price:</strong> <span id="preview_box_price" class="badge badge-info" style="font-size: 13px;">₹0.00</span>
                                    </div>
                                    <div class="col-md-4">
                                        <strong>Per Shuttle Unit Cost:</strong> <span id="preview_per_shuttle_price" class="badge badge-warning" style="font-size: 13px;">₹0.00</span>
                                    </div>
                                </div>
                            </div>

                            <div class="row">
                                <div class="col-md-4 form-group">
                                    <label>Supplier Name</label>
                                    <input type="text" id="supplier_name" class="form-control" placeholder="Supplier / Vendor Name">
                                </div>
                                <div class="col-md-4 form-group">
                                    <label>Invoice / Bill Number</label>
                                    <input type="text" id="invoice_no" class="form-control" placeholder="Invoice Number">
                                </div>
                                <div class="col-md-4 form-group">
                                    <label><i class="fa fa-upload"></i> Upload Bill / Receipt</label>
                                    <input type="file" id="bill_file" accept="image/*,application/pdf" class="form-control">
                                    <small id="current_bill_lbl" class="help-block text-muted" style="margin-top: 4px;"></small>
                                </div>
                            </div>

                            <div class="form-group">
                                <label>Notes / Comments</label>
                                <textarea id="notes" class="form-control" rows="2" placeholder="Additional details or remarks..."></textarea>
                            </div>
                        </div>
                        <div class="modal-footer">
                            <button type="button" class="btn btn-white" data-dismiss="modal">Close</button>
                            <button type="button" class="btn btn-primary" onclick="saveStock()"><i class="fa fa-save"></i> Save Stock</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>

        <!-- Record Payment Modal -->
        <div class="modal fade" id="paymentRecordModal" tabindex="-1" role="dialog" aria-hidden="true">
            <div class="modal-dialog">
                <div class="modal-content">
                    <div class="modal-header" style="background-color: #10b981; color: white;">
                        <button type="button" class="close" data-dismiss="modal" style="color: white;"><span aria-hidden="true">&times;</span></button>
                        <h4 class="modal-title"><i class="fa fa-money"></i> Record Voucher Payment</h4>
                    </div>
                    <form id="payment_form">
                        <input type="hidden" id="pay_stock_id" value="0">
                        <input type="hidden" id="pay_payable_id" value="0">
                        
                        <div class="modal-body">
                            <!-- Summary Card -->
                            <div class="alert alert-warning" style="margin-bottom: 15px;">
                                <p style="margin-bottom: 5px;"><strong>Supplier / Purchase:</strong> <span id="pay_supplier_info">-</span></p>
                                <div class="row text-center" style="margin-top: 10px;">
                                    <div class="col-xs-4">
                                        <small class="text-muted block">Total Cost</small>
                                        <strong id="pay_total_cost" class="text-navy" style="font-size: 14px;">₹0.00</strong>
                                    </div>
                                    <div class="col-xs-4">
                                        <small class="text-muted block">Paid Amount</small>
                                        <strong id="pay_already_paid" class="text-success" style="font-size: 14px;">₹0.00</strong>
                                    </div>
                                    <div class="col-xs-4">
                                        <small class="text-muted block">Balance Due</small>
                                        <strong id="pay_due_amount" class="text-danger" style="font-size: 14px;">₹0.00</strong>
                                    </div>
                                </div>
                            </div>

                            <div class="row">
                                <div class="col-md-6 form-group">
                                    <label>Payment Date *</label>
                                    <input type="date" id="pay_date" class="form-control" required>
                                </div>
                                <div class="col-md-6 form-group">
                                    <label>Payment Mode *</label>
                                    <select id="pay_mode" class="form-control" required>
                                        <option value="1">Cash Payment</option>
                                        <option value="2">UPI / GPay / Online / Bank Transfer</option>
                                    </select>
                                </div>
                            </div>

                            <div class="row">
                                <div class="col-md-12 form-group">
                                    <label style="color: #059669; font-weight: bold;">Amount Paid (₹) *</label>
                                    <input type="number" step="0.01" id="pay_amount" class="form-control" placeholder="e.g. 3000.00" style="background-color: #ecfdf5; font-weight: bold; font-size: 16px; border: 2px solid #10b981;" required>
                                </div>
                            </div>

                            <div class="form-group">
                                <label>Transaction Reference / Cheque No / Notes</label>
                                <input type="text" id="pay_notes" class="form-control" placeholder="e.g. UPI Ref # 12345678 or Cash payment by Cashier">
                            </div>
                        </div>
                        <div class="modal-footer">
                            <button type="button" class="btn btn-white" data-dismiss="modal">Close</button>
                            <button type="button" class="btn btn-primary" onclick="saveStockPayment()" style="background-color: #10b981; border-color: #10b981;"><i class="fa fa-check"></i> Save Payment</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <!-- Core Scripts -->
    <script src="../js/plugins/metisMenu/jquery.metisMenu.js"></script>
    <script src="../js/plugins/slimscroll/jquery.slimscroll.min.js"></script>
    <script src="../js/inspinia.js"></script>
    <script src="../js/plugins/pace/pace.min.js"></script>
</body>
</html>
