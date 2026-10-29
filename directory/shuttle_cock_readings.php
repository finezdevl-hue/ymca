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

    <title>Shuttle Cock Readings</title>

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
            --sidebar-bg: #1e293b;
        }

        [data-theme="dark"] {
            --bg-neutral: #0f172a;
            --card-bg: #1e293b;
            --text-main: #f8fafc;
            --text-muted: #94a3b8;
            --border-color: #334155;
            --primary-accent: #6366f1;
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
        .metric-card-amber { border-left-color: #f59e0b; }
        .metric-card-rose { border-left-color: #ef4444; }
        .metric-card-indigo { border-left-color: #6366f1; }

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
        .metric-icon-amber { background: rgba(245, 158, 11, 0.12); color: #f59e0b; }
        .metric-icon-rose { background: rgba(239, 68, 68, 0.12); color: #ef4444; }
        .metric-icon-indigo { background: rgba(99, 102, 241, 0.12); color: #6366f1; }

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

        /* Modern Soft-Tinted Pill Badges */
        .badge-pill-soft {
            display: inline-block;
            padding: 4px 12px;
            border-radius: 20px;
            font-size: 12px;
            font-weight: 600;
        }

        .badge-pill-group { background: #eff6ff; color: #1d4ed8; }
        .badge-pill-shuttle { background: #fef2f2; color: #b91c1c; font-weight: 700; }
        .badge-pill-session { background: #f0fdf4; color: #15803d; }
        .badge-pill-filter { background: #ecfdf5; color: #047857; }

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

        .btn-action-icon:hover { transform: scale(1.1); }

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

        .btn-action-filter {
            background: rgba(6, 182, 212, 0.12);
            color: #06b6d4;
            border: 1px solid rgba(6, 182, 212, 0.3);
            border-radius: 8px;
            padding: 4px 10px;
            font-size: 12px;
            font-weight: 600;
        }
        .btn-action-filter:hover { background: #06b6d4; color: #fff; }

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
           PRINT STYLING (A4 Clean Consumption & Readings Statement for Print / PDF)
           ========================================================================== */
        @media print {
            @page {
                size: A4 portrait;
                margin: 8mm 8mm 10mm 8mm;
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
                min-width: 100% !important;
                font-family: 'Inter', -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, Arial, sans-serif !important;
                font-size: 8.5pt !important;
            }

            /* Hide screen controls, sidebar, navbar, action buttons, modals, and pagination */
            .navbar-static-side, .navbar-static-top, nav.navbar,
            .dash-page-heading, .page-heading, .row.page-heading, .row.dash-page-heading,
            .btn-dash-primary, .btn-dash-print, .theme-toggle-btn, #month_filter_select,
            .modal, .modal-backdrop, .btn-action-icon, .btn-action-edit, .btn-action-delete, .btn-action-filter,
            .no-print, .pagination-wrapper, .hr-line-dashed,
            .custom-dashboard-table th.col-action, .custom-dashboard-table td.col-action,
            .custom-dashboard-table th.no-print, .custom-dashboard-table td.no-print,
            .custom-dashboard-table th:last-child, .custom-dashboard-table td:last-child,
            ul.pagination, .app-pagination, #app_pagination {
                display: none !important;
                visibility: hidden !important;
                width: 0 !important;
                padding: 0 !important;
                margin: 0 !important;
                border: none !important;
            }

            .wrapper-content {
                padding: 0 !important;
                margin: 0 !important;
                width: 100% !important;
            }

            /* Show Print Letterhead */
            .print-only-block {
                display: block !important;
                margin-bottom: 12px !important;
            }

            /* Metric Cards Layout in Print (4 columns horizontal grid) */
            .row {
                display: flex !important;
                flex-wrap: wrap !important;
                margin-right: -4px !important;
                margin-left: -4px !important;
            }
            .col-lg-3, .col-md-6, .col-sm-6, .col-xs-12 {
                width: 25% !important;
                float: left !important;
                padding-right: 4px !important;
                padding-left: 4px !important;
                box-sizing: border-box !important;
            }
            .col-lg-12 {
                width: 100% !important;
                float: none !important;
                padding-right: 4px !important;
                padding-left: 4px !important;
                box-sizing: border-box !important;
            }

            .metric-card-clean {
                border: 1px solid #cbd5e1 !important;
                border-left-width: 4px !important;
                border-radius: 8px !important;
                box-shadow: none !important;
                padding: 6px 10px !important;
                margin-bottom: 10px !important;
                background: #f8fafc !important;
                -webkit-print-color-adjust: exact !important;
                print-color-adjust: exact !important;
            }
            .metric-label {
                font-size: 6.5pt !important;
                color: #475569 !important;
                font-weight: 700 !important;
                text-transform: uppercase !important;
            }
            .metric-value {
                font-size: 12pt !important;
                font-weight: 800 !important;
                color: #0f172a !important;
            }
            .metric-icon-box {
                display: none !important;
            }

            /* Card Boxes in Print */
            .dash-card-box {
                border: 1px solid #94a3b8 !important;
                border-radius: 8px !important;
                box-shadow: none !important;
                margin-bottom: 12px !important;
                overflow: hidden !important;
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
                font-size: 8.5pt !important;
                font-weight: 800 !important;
                color: #0f172a !important;
                text-transform: uppercase !important;
                letter-spacing: 0.3px !important;
                margin: 0 !important;
            }

            .table-responsive {
                overflow: visible !important;
                margin-bottom: 0 !important;
                border: none !important;
            }

            /* Tables in Print */
            .custom-dashboard-table {
                width: 100% !important;
                table-layout: fixed !important;
                border-collapse: collapse !important;
                font-size: 7.5pt !important;
                margin-bottom: 0 !important;
            }
            .custom-dashboard-table > thead > tr > th {
                background-color: #1e293b !important;
                color: #ffffff !important;
                -webkit-print-color-adjust: exact !important;
                print-color-adjust: exact !important;
                font-size: 7pt !important;
                font-weight: 800 !important;
                padding: 5px 6px !important;
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
            .badge-pill-soft {
                border: 1px solid #cbd5e1 !important;
                padding: 1px 4px !important;
                font-size: 6.5pt !important;
                font-weight: 700 !important;
                background: #f1f5f9 !important;
                color: #0f172a !important;
                -webkit-print-color-adjust: exact !important;
                print-color-adjust: exact !important;
            }

            /* Proportional column widths for Table 1 (Month-wise Breakdown - 5 columns sum to 100%) */
            #month_breakdown_container table th:nth-child(1),
            #month_breakdown_container table td:nth-child(1) { width: 26% !important; text-align: left !important; }
            #month_breakdown_container table th:nth-child(2),
            #month_breakdown_container table td:nth-child(2) { width: 18% !important; text-align: center !important; }
            #month_breakdown_container table th:nth-child(3),
            #month_breakdown_container table td:nth-child(3) { width: 18% !important; text-align: center !important; }
            #month_breakdown_container table th:nth-child(4),
            #month_breakdown_container table td:nth-child(4) { width: 18% !important; text-align: right !important; }
            #month_breakdown_container table th:nth-child(5),
            #month_breakdown_container table td:nth-child(5) { width: 20% !important; text-align: right !important; }

            /* Proportional column widths for Table 2 (Daily Session Log - 6 columns sum to 100%) */
            #table_container table th:nth-child(1),
            #table_container table td:nth-child(1) { width: 6% !important; text-align: center !important; }
            #table_container table th:nth-child(2),
            #table_container table td:nth-child(2) { width: 24% !important; text-align: left !important; }
            #table_container table th:nth-child(3),
            #table_container table td:nth-child(3) { width: 18% !important; text-align: left !important; }
            #table_container table th:nth-child(4),
            #table_container table td:nth-child(4) { width: 18% !important; text-align: center !important; }
            #table_container table th:nth-child(5),
            #table_container table td:nth-child(5) { width: 16% !important; text-align: right !important; }
            #table_container table th:nth-child(6),
            #table_container table td:nth-child(6) { width: 18% !important; text-align: right !important; }

            /* Strictly hide action column cells in print */
            .custom-dashboard-table th.col-action,
            .custom-dashboard-table td.col-action,
            .custom-dashboard-table th.no-print,
            .custom-dashboard-table td.no-print,
            .custom-dashboard-table th:last-child,
            .custom-dashboard-table td:last-child {
                display: none !important;
                visibility: hidden !important;
                width: 0 !important;
                max-width: 0 !important;
                padding: 0 !important;
                border: none !important;
                margin: 0 !important;
                font-size: 0 !important;
                line-height: 0 !important;
            }

            /* Signatures in Print */
            .print-sig-block {
                display: flex !important;
                justify-content: space-between !important;
                margin-top: 24px !important;
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

            // Calculation listeners in Modal
            $('#shuttles_used_input').on('input change', function() {
                let startVal = parseInt($('#start_shuttle').val()) || 0;
                let usedVal = parseInt($(this).val()) || 0;
                if ($(this).val() !== '') {
                    $('#end_shuttle').val(startVal + usedVal);
                }
                calculateReadingPreview();
            });

            $('#end_shuttle').on('input change', function() {
                let startVal = parseInt($('#start_shuttle').val()) || 0;
                let endVal = parseInt($(this).val()) || 0;
                if ($(this).val() !== '') {
                    $('#shuttles_used_input').val(Math.max(0, endVal - startVal));
                }
                calculateReadingPreview();
            });

            $('#start_shuttle').on('input change', function() {
                let startVal = parseInt($(this).val()) || 0;
                if ($('#shuttles_used_input').val() !== '') {
                    let usedVal = parseInt($('#shuttles_used_input').val()) || 0;
                    $('#end_shuttle').val(startVal + usedVal);
                } else if ($('#end_shuttle').val() !== '') {
                    let endVal = parseInt($('#end_shuttle').val()) || 0;
                    $('#shuttles_used_input').val(Math.max(0, endVal - startVal));
                }
                calculateReadingPreview();
            });

            $('#per_shuttle_price').on('input change', function() {
                calculateReadingPreview();
            });

            // Auto fetch last reading & creator notice when group or date changes
            $('#group_id, #reading_date').on('change', function() {
                fetchLastReading();
                checkExistingReadingNotice();
            });

            // Intercept Ctrl+P / Cmd+P to print all records instead of only current page
            $(document).keydown(function(e) {
                if ((e.ctrlKey || e.metaKey) && (e.key === 'p' || e.keyCode === 80)) {
                    if (typeof printAllReadings === 'function' && !isPrintingReadings) {
                        e.preventDefault();
                        printAllReadings();
                    }
                }
            });
        });  

        function checkExistingReadingNotice() {
            let groupId = $('#group_id').val() || 0;
            let rDate = $('#reading_date').val();
            if (!rDate || !groupId) return;
            $.post('api/attendance.php', { action: 'get_shuttle_reading', group_id: groupId, date: rDate }, function(res) {
                try {
                    var r = typeof res === 'string' ? JSON.parse(res) : res;
                    if (r.status === 'success' && r.has_record && (r.last_user_name || r.created_by_name)) {
                        var lastUser = r.last_user_name || r.created_by_name;
                        var creatorLabel = r.is_created_by_me ? 'You' : lastUser;
                        var actWord = r.action_word || (r.is_edited ? 'updated' : 'added');
                        var noticeHtml = '<i class="fa fa-info-circle"></i> <strong>' + creatorLabel + '</strong> ' + actWord + ' the count (' + r.shuttles_used + ' shuttles). Do you want to edit?';
                        $('#modal_creator_notice').html(noticeHtml).show();
                        if ($('#hdn_id').val() == 0 && r.shuttles_used > 0) {
                            $('#shuttles_used_input').val(r.shuttles_used);
                            calculateReadingPreview();
                        }
                    } else {
                        $('#modal_creator_notice').hide().html('');
                    }
                } catch(e){}
            });
        }

        function calculateReadingPreview() {
            let startVal = parseInt($('#start_shuttle').val()) || 0;
            let endVal = parseInt($('#end_shuttle').val()) || 0;
            let usedVal = parseInt($('#shuttles_used_input').val());
            
            let used = !isNaN(usedVal) ? usedVal : Math.max(0, endVal - startVal);
            let price = parseFloat($('#per_shuttle_price').val()) || 0.00;
            let totalAmount = used * price;

            $('#preview_shuttles_used').text(used);
            $('#preview_total_amount').text('₹' + totalAmount.toFixed(2));

            if (endVal < startVal && $('#end_shuttle').val() !== '') {
                $('#warning_reading_val').text('Warning: End reading is less than start reading!').show();
            } else {
                $('#warning_reading_val').hide();
            }
        }

        function fetchLastReading() {
            let groupId = $('#group_id').val() || 0;
            $.ajax({
                type: "POST",
                url: "api/shuttle_cock_readings.php",
                data: { action: 'get_last_reading', group_id: groupId },
                success: function(response) {
                    if (response.status === 'success') {
                        if ($('#hdn_id').val() == 0) {
                            $('#start_shuttle').val(response.last_end_shuttle);
                            $('#per_shuttle_price').val(response.latest_price);
                            if (response.brand_name) {
                                $('#brand_name').val(response.brand_name);
                            }
                            calculateReadingPreview();
                        }
                    }
                }
            });
        }

        function adjustShuttleCount(inputId, delta) {
            var $el = $('#' + inputId);
            var val = parseInt($el.val()) || 0;
            val = Math.min(6, Math.max(0, val + delta));
            $el.val(val).trigger('change').trigger('input');
        }

        function filterByMonth(mKey) {
            $('#month_filter_select').val(mKey);
            loadData(1);
        }

        function loadData(page) {
            $('#hdn_current_page').val(page);
            var selectedMonth = $('#month_filter_select').val() || 'all';
            $.ajax({               
               type: "POST",
               url: "api/shuttle_cock_readings.php",
               data: {
                   action: 'load_data',
                   page: page,
                   selected_month: selectedMonth
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
                        groupOptions += "<option value='0'>General / All Groups</option>";
                    }
                    for (var g = 0; g < window.groupsList.length; g++) {
                        groupOptions += "<option value='" + window.groupsList[g].id + "'>" + window.groupsList[g].group_name + "</option>";
                    }
                    $('#group_id').html(groupOptions);

                    // Populate Month Filter Dropdown if options changed
                    var availMonths = summary.available_months || [];
                    var curSelMonth = $('#month_filter_select').val() || 'all';
                    var mOptHtml = "<option value='all'" + (curSelMonth === 'all' ? " selected" : "") + ">📅 All Months</option>";
                    for (var m = 0; m < availMonths.length; m++) {
                        var mk = availMonths[m].month_key;
                        var ml = availMonths[m].month_label;
                        var isSel = (mk === curSelMonth) ? " selected" : "";
                        mOptHtml += "<option value='" + mk + "'" + isSel + ">" + ml + "</option>";
                    }
                    $('#month_filter_select').html(mOptHtml);

                    // Render Month-Wise Consumption Breakdown Table
                    var mBreakdown = summary.month_breakdown || [];
                    var mbHtm = "<div class='table-responsive'><table class='table custom-dashboard-table'>";
                    mbHtm += "<thead><tr>";
                    mbHtm += "<th>Month & Year</th>";
                    mbHtm += "<th class='text-center'>Playing Sessions</th>";
                    mbHtm += "<th class='text-center'>Total Shuttles Used</th>";
                    mbHtm += "<th class='text-right'>Avg Per Shuttle Price</th>";
                    mbHtm += "<th class='text-right'>Total Shuttle Cost (₹)</th>";
                    mbHtm += "<th class='text-center col-action no-print' style='width: 120px;'>Action</th>";
                    mbHtm += "</tr></thead><tbody>";

                    if (mBreakdown.length === 0) {
                        mbHtm += "<tr><td colspan='6' class='text-center text-muted' style='padding: 24px;'>No month-wise readings recorded yet</td></tr>";
                    } else {
                        var totMbShuttles = 0;
                        var totMbCost = 0.00;
                        for (var mb = 0; mb < mBreakdown.length; mb++) {
                            var mbRow = mBreakdown[mb];
                            totMbShuttles += parseInt(mbRow.shuttles_used) || 0;
                            totMbCost += parseFloat(mbRow.total_cost) || 0.00;

                            var activeBadge = (mbRow.month_key === curSelMonth) ? " <span class='badge-pill-soft badge-pill-filter'><i class='fa fa-check'></i> Active Filter</span>" : "";
                            mbHtm += "<tr>";
                            mbHtm += "<td><strong>" + mbRow.month_label + "</strong>" + activeBadge + "</td>";
                            mbHtm += "<td class='text-center'><span class='badge-pill-soft badge-pill-session'>" + mbRow.session_count + " Sessions</span></td>";
                            mbHtm += "<td class='text-center'><span class='badge-pill-soft badge-pill-shuttle'>" + mbRow.shuttles_used + " Shuttles</span></td>";
                            mbHtm += "<td class='text-right'>₹" + parseFloat(mbRow.avg_price).toFixed(2) + "</td>";
                            mbHtm += "<td class='text-right'><strong class='text-success' style='font-size: 14px;'>₹" + parseFloat(mbRow.total_cost).toFixed(2) + "</strong></td>";
                            mbHtm += "<td class='text-center col-action no-print'><button type='button' class='btn-action-filter' onclick='filterByMonth(\"" + mbRow.month_key + "\")'><i class='fa fa-filter'></i> Filter Readings</button></td>";
                            mbHtm += "</tr>";
                        }
                        mbHtm += "<tr style='background-color: rgba(0,0,0,0.03); font-weight: bold;'>";
                        mbHtm += "<td>Total Summary</td>";
                        mbHtm += "<td class='text-center'>-</td>";
                        mbHtm += "<td class='text-center'><span class='badge-pill-soft badge-pill-shuttle'>" + totMbShuttles + " Shuttles</span></td>";
                        mbHtm += "<td class='text-right'>-</td>";
                        mbHtm += "<td class='text-right text-success' style='font-size: 15px;'>₹" + totMbCost.toFixed(2) + "</td>";
                        mbHtm += "<td class='text-center col-action no-print'>" + (curSelMonth !== 'all' ? "<button type='button' class='btn btn-xs btn-default' style='border-radius: 6px;' onclick='filterByMonth(\"all\")'><i class='fa fa-times'></i> Clear Filter</button>" : "") + "</td>";
                        mbHtm += "</tr>";
                    }
                    mbHtm += "</tbody></table></div>";
                    $('#month_breakdown_container').html(mbHtm);

                    // Render Summary KPI Cards
                    $('#stat_stock_remaining').text(summary.stock_remaining + ' Shuttles');
                    $('#stat_total_used').text(summary.total_used + ' Shuttles');
                    $('#stat_total_used_cost').text('₹' + parseFloat(summary.total_used_cost || 0).toFixed(2));
                    $('#stat_active_groups').text((groupsList.length) + ' Groups');

                    var totalrows = summary.total_rows;
                    var htm = renderReadingsTableHtml(list, page, false);
                    $('#table_container').html(htm);
                    $('[data-toggle="tooltip"]').tooltip();
                    var htmpage = paginate(totalrows, page);
                    if (htmpage) {
                        $('#table_container').append("<div class='no-print pagination-wrapper' style='margin-bottom:15px;'>" + htmpage + "</div>");
                    }
                    } catch(err) {
                        console.error('Error rendering readings:', err);
                    }
                },
                error: function(xhr, status, error) {
                    console.log('AJAX Error:', error);
                }
            });
        }

        function renderReadingsTableHtml(list, page, isPrint) {
            var htm = "";
            htm += "<div class='table-responsive'>";
            htm += "<table class='table custom-dashboard-table'>";
            htm += "<thead>";
            htm += "<tr>";
            htm += "<th class='text-center' style='width: 6%;'>No</th>";
            htm += "<th style='width: 24%;'>Reading Date</th>";
            htm += "<th style='width: 18%;'>Group</th>";
            htm += "<th class='text-center' style='width: 18%;'>Shuttles Used Count</th>";
            htm += "<th class='text-right' style='width: 16%;'>Per Shuttle Price</th>";
            htm += "<th class='text-right' style='width: 18%;'>Total Cost</th>";
            htm += "<th class='text-center col-action no-print' style='width: 90px;'>Action</th>";
            htm += "</tr>";
            htm += "</thead>";
            htm += "<tbody>";

            if (!list || list.length === 0) {
                htm += "<tr><td colspan='7' class='text-center text-muted' style='padding: 24px;'>No shuttle cock counts recorded yet</td></tr>";
            } else {
                for (var i = 0; i < list.length; i++) {
                    var slno = isPrint ? (i + 1) : (((page - 1) * 8) + (i + 1));
                    var r = list[i];
                    var noteHtm = (r.notes && r.notes.trim()) ? "<div style='font-size:11px; color:#b91c1c; font-weight:600; margin-top:2px;'><i class='fa fa-pencil' style='color:#ef4444; font-size:10px;'></i> " + $('<div>').text(r.notes).html() + "</div>" : "";

                    htm += "<tr>";
                    htm += "<td class='text-center'><span style='color: var(--text-muted); font-weight: 600;'>" + slno + "</span></td>";
                    htm += "<td><strong>" + r.reading_date + "</strong>" + noteHtm + "</td>";
                    htm += "<td><span class='badge-pill-soft badge-pill-group'>" + r.group_name + "</span></td>";
                    htm += "<td class='text-center'><span class='badge-pill-soft badge-pill-shuttle'>" + r.shuttles_used + " Shuttles</span></td>";
                    htm += "<td class='text-right'>₹" + parseFloat(r.per_shuttle_price).toFixed(2) + "</td>";
                    htm += "<td class='text-right'><strong class='text-success' style='font-size: 14px;'>₹" + parseFloat(r.total_amount).toFixed(2) + "</strong></td>";
                    htm += "<td class='text-center col-action no-print'>";
                    htm += "<button type='button' class='btn-action-icon btn-action-edit' data-toggle='tooltip' title='Edit Reading' onclick='editReading(" + JSON.stringify(r) + ")'><i class='fa fa-edit'></i></button> ";
                    htm += "<button type='button' class='btn-action-icon btn-action-delete' data-toggle='tooltip' title='Delete Reading' onclick='deleteReading(" + r.id + ")'><i class='fa fa-trash'></i></button>";
                    htm += "</td>";
                    htm += "</tr>";
                }
            }

            htm += "</tbody>";
            htm += "</table>";
            htm += "</div>";
            return htm;
        }

        var isPrintingReadings = false;
        function printAllReadings() {
            if (isPrintingReadings) return;
            isPrintingReadings = true;

            var $btnPrint = $('#btn_print_readings');
            var originalBtnHtml = $btnPrint.html();
            $btnPrint.html('<i class="fa fa-spinner fa-spin"></i> Preparing...').prop('disabled', true);

            var currentPage = parseInt($('#hdn_current_page').val()) || 1;
            var selectedMonth = $('#month_filter_select').val() || 'all';

            $.ajax({
                type: "POST",
                url: "api/shuttle_cock_readings.php",
                data: {
                    action: 'load_data',
                    selected_month: selectedMonth,
                    is_print: 1
                },
                success: function(data) {
                    try {
                        var obj = typeof data === 'string' ? JSON.parse(data) : data;
                        if (!obj || !obj[0]) {
                            cleanupPrint();
                            return;
                        }
                        var allList = obj[1] || [];

                        // Render all rows into table container without pagination controls
                        var printHtm = renderReadingsTableHtml(allList, 1, true);
                        $('#table_container').html(printHtm);

                        setTimeout(function() {
                            var restored = false;
                            function restoreView() {
                                if (restored) return;
                                restored = true;
                                window.removeEventListener('afterprint', restoreView);
                                loadData(currentPage);
                                cleanupPrint();
                            }

                            window.addEventListener('afterprint', restoreView);

                            window.print();

                            // Fallback timeout in case afterprint does not fire in some browsers
                            setTimeout(function() {
                                restoreView();
                            }, 2000);
                        }, 250);

                    } catch (e) {
                        console.error('Error preparing print data:', e);
                        cleanupPrint();
                    }
                },
                error: function(err) {
                    console.error('Error fetching print data:', err);
                    alert('Unable to load all readings for printing. Please try again.');
                    cleanupPrint();
                }
            });

            function cleanupPrint() {
                isPrintingReadings = false;
                $btnPrint.html(originalBtnHtml).prop('disabled', false);
            }
        }

        function loadGroupsForModal() {
            if ($('#group_id option').length === 0 && window.groupsList && window.groupsList.length > 0) {
                var groupOptions = "";
                if (window.isAllGroupsGlobal) {
                    groupOptions += "<option value='0'>General / All Groups</option>";
                }
                for (var g = 0; g < window.groupsList.length; g++) {
                    groupOptions += "<option value='" + window.groupsList[g].id + "'>" + window.groupsList[g].group_name + "</option>";
                }
                $('#group_id').html(groupOptions);
            }
        }

        function openAddModal() {
            loadGroupsForModal();
            $('#hdn_id').val(0);
            $('#modal_title').text('Record Shuttle Cock Reading');
            $('#reading_date').val(new Date().toISOString().slice(0, 10));
            var defaultG = $('#group_id option:first').val() || 0;
            $('#group_id').val(defaultG);
            $('#brand_name').val('Standard');
            $('#start_shuttle').val(0);
            $('#shuttles_used_input').val('');
            $('#end_shuttle').val('');
            $('#per_shuttle_price').val(0.00);
            $('#notes').val('');
            $('#modal_creator_notice').hide().html('');
            fetchLastReading();
            checkExistingReadingNotice();
            $('#readingModal').modal('show');
        }

        function editReading(item) {
            $('#hdn_id').val(item.id);
            $('#modal_title').text('Edit Shuttle Cock Reading');
            $('#reading_date').val(item.reading_date);
            $('#group_id').val(item.group_id);
            $('#brand_name').val(item.brand_name || 'Standard');
            $('#start_shuttle').val(item.start_shuttle);
            $('#shuttles_used_input').val(item.shuttles_used);
            $('#end_shuttle').val(item.end_shuttle);
            $('#per_shuttle_price').val(item.per_shuttle_price);
            $('#notes').val(item.notes);
            calculateReadingPreview();
            checkExistingReadingNotice();
            $('#readingModal').modal('show');
        }

        function saveReading() {
            let startVal = parseInt($('#start_shuttle').val()) || 0;
            let endVal = parseInt($('#end_shuttle').val()) || 0;
            let usedVal = parseInt($('#shuttles_used_input').val());

            if (isNaN(usedVal) && $('#end_shuttle').val() === '') {
                alertwarning('Please enter either Shuttles Used After Play or End Reading');
                return;
            }

            if (!isNaN(usedVal) && usedVal < 0) {
                alertwarning('Shuttles used cannot be negative');
                return;
            }

            var currentStock = parseInt($('#stat_stock_remaining').text()) || 0;
            if (currentStock <= 0) {
                alertwarning('Out of Stock! Available shuttle cock inventory is 0. Please add inward stock first.');
                return;
            }
            if (usedVal > currentStock) {
                alertwarning('Insufficient Stock! Only ' + currentStock + ' shuttles remaining in inventory.');
                return;
            }

            var formData = {
                action: 'save_reading',
                id: $('#hdn_id').val(),
                reading_date: $('#reading_date').val(),
                group_id: $('#group_id').val(),
                brand_name: $('#brand_name').val(),
                start_shuttle: startVal,
                end_shuttle: endVal,
                shuttles_used: !isNaN(usedVal) ? usedVal : '',
                per_shuttle_price: $('#per_shuttle_price').val(),
                notes: $('#notes').val()
            };

            $.ajax({
                type: "POST",
                url: "api/shuttle_cock_readings.php",
                data: formData,
                success: function(response) {
                    try {
                        var res = typeof response === 'string' ? JSON.parse(response) : response;
                        if (res.status === 'success') {
                            $('#readingModal').modal('hide');
                            alertsuccess(res.message || 'Reading Saved Successfully');
                            loadData($('#hdn_current_page').val());
                        } else {
                            alertwarning(res.message || 'Error saving reading');
                        }
                    } catch(e) {
                        $('#readingModal').modal('hide');
                        alertsuccess('Reading Saved Successfully');
                        loadData($('#hdn_current_page').val());
                    }
                },
                error: function(xhr) {
                    var msg = 'Error saving reading. Please check stock balance.';
                    try {
                        var r = JSON.parse(xhr.responseText);
                        if (r.message) msg = r.message;
                    } catch(e) {
                        if (xhr.responseText) msg = xhr.responseText;
                    }
                    alertwarning(msg);
                }
            });
        }

        function deleteReading(id) {
            swal({
                title: "Are you sure?",
                text: "You want to delete this reading entry?",
                type: "warning",
                showCancelButton: true,
                confirmButtonColor: "#DD6B55",
                confirmButtonText: "Yes, delete it!",
                closeOnConfirm: true
            }, function() {
                $.ajax({
                    type: "POST",
                    url: "api/shuttle_cock_readings.php",
                    data: { action: 'delete_reading', id: id },
                    success: function(response) {
                        alertsuccess('Deleted Successfully');
                        loadData($('#hdn_current_page').val());
                    }
                });
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
            <div class="row border-bottom no-print">
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
            <div class="row wrapper border-bottom white-bg page-heading dash-page-heading no-print">
                <div>
                    <h2><i class="fa fa-list-alt" style="color: var(--primary-accent); margin-right: 8px;"></i> Shuttle Cock Readings</h2>
                    <ol class="breadcrumb">
                        <li><a href="dashboard.php">Home</a></li>
                        <li class="active"><strong>Shuttle Cock Readings</strong></li>
                    </ol>
                </div>
                <div style="display: flex; align-items: center; gap: 10px; flex-wrap: wrap;">
                    <select id="month_filter_select" class="form-control input-sm" style="width: auto; min-width: 160px; height: 38px; border-radius: 8px; font-weight: 600;" onchange="loadData(1)">
                        <option value="all">📅 All Months</option>
                    </select>
                    <button type="button" id="btn_print_readings" class="btn btn-dash-print" onclick="printAllReadings()"><i class="fa fa-print"></i> Print Readings</button>
                    <button type="button" class="btn btn-dash-primary" onclick="openAddModal()"><i class="fa fa-plus"></i> Take Shuttle Reading</button>
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
                            Shuttle Cock Consumption & Session Readings Statement
                        </div>
                    </div>
                </div>

                <!-- Key Metrics (Stat Cards Horizontal Grid) -->
                <div class="row">
                    <div class="col-lg-3 col-md-6 col-sm-6 col-xs-12">
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
                    <div class="col-lg-3 col-md-6 col-sm-6 col-xs-12">
                        <div class="metric-card-clean metric-card-amber">
                            <div style="display: flex; align-items: center; justify-content: space-between;">
                                <div>
                                    <div class="metric-label">Total Shuttles Used</div>
                                    <div class="metric-value" id="stat_total_used">0 Shuttles</div>
                                </div>
                                <div class="metric-icon-box metric-icon-amber">
                                    <i class="fa fa-history"></i>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="col-lg-3 col-md-6 col-sm-6 col-xs-12">
                        <div class="metric-card-clean metric-card-rose">
                            <div style="display: flex; align-items: center; justify-content: space-between;">
                                <div>
                                    <div class="metric-label">Total Used Shuttle Cost</div>
                                    <div class="metric-value" id="stat_total_used_cost">₹0.00</div>
                                </div>
                                <div class="metric-icon-box metric-icon-rose">
                                    <i class="fa fa-inr"></i>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="col-lg-3 col-md-6 col-sm-6 col-xs-12">
                        <div class="metric-card-clean metric-card-indigo">
                            <div style="display: flex; align-items: center; justify-content: space-between;">
                                <div>
                                    <div class="metric-label">Playing Groups</div>
                                    <div class="metric-value" id="stat_active_groups">0 Groups</div>
                                </div>
                                <div class="metric-icon-box metric-icon-indigo">
                                    <i class="fa fa-users"></i>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Month-Wise Summary Breakdown Box -->
                <div class="row">
                    <div class="col-lg-12">
                        <div class="dash-card-box">
                            <div class="dash-card-header">
                                <h5><i class="fa fa-calendar" style="color: var(--primary-accent); margin-right: 8px;"></i> Month-Wise Shuttle Cock Consumption Breakdown</h5>
                            </div>
                            <div id="month_breakdown_container" style="padding: 0;">
                                <!-- Month breakdown table injected dynamically -->
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Detailed Log Table Box -->
                <div class="row">
                    <div class="col-lg-12">
                        <div class="dash-card-box">
                            <div class="dash-card-header">
                                <h5><i class="fa fa-list-alt" style="color: var(--primary-accent); margin-right: 8px;"></i> Daily / Session Shuttle Cock Readings Log</h5>
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

        <!-- Take Reading Modal -->
        <div class="modal inmodal" id="readingModal" tabindex="-1" role="dialog" aria-hidden="true">
            <div class="modal-dialog modal-lg">
                <div class="modal-content animated fadeIn">
                    <div class="modal-header">
                        <button type="button" class="close" data-dismiss="modal"><span aria-hidden="true">&times;</span></button>
                        <h4 class="modal-title" id="modal_title">Take Shuttle Cock Reading</h4>
                    </div>
                    <form id="reading_form">
                        <div class="modal-body">
                            <div class="row">
                                <div class="col-md-6 form-group">
                                    <label>Reading Date *</label>
                                    <input type="date" id="reading_date" class="form-control" required>
                                </div>
                                <div class="col-md-6 form-group">
                                    <label>Select Group *</label>
                                    <select id="group_id" class="form-control" required>
                                        <!-- Populated via AJAX -->
                                    </select>
                                </div>
                            </div>

                            <input type="hidden" id="start_shuttle" value="0">
                            <input type="hidden" id="end_shuttle" value="0">
                            <input type="hidden" id="per_shuttle_price" value="0">

                            <div class="row">
                                <div class="col-md-12 form-group">
                                    <div style="display:flex; align-items:center; gap:8px;">
                                        <button type="button" onclick="adjustShuttleCount('shuttles_used_input', -1)" style="width:48px; height:50px; border-radius:10px; border:2px solid #ef4444; background:#fff; color:#991b1b; font-weight:900; font-size:24px; display:flex; align-items:center; justify-content:center; cursor:pointer; padding:0; user-select:none;">-</button>
                                        <input type="number" id="shuttles_used_input" class="form-control input-lg" min="1" max="6" placeholder="0" readonly style="background-color: #fef2f2; font-weight: bold; border: 2px solid #ef4444; color: #991b1b; font-size: 22px; height: 50px; text-align:center; cursor:default;" required>
                                        <button type="button" onclick="adjustShuttleCount('shuttles_used_input', 1)" style="width:48px; height:50px; border-radius:10px; border:2px solid #ef4444; background:#fff; color:#991b1b; font-weight:900; font-size:24px; display:flex; align-items:center; justify-content:center; cursor:pointer; padding:0; user-select:none;">+</button>
                                    </div>
                                </div>
                            </div>

                            <div id="modal_creator_notice" class="alert alert-info text-center" style="display: none; font-weight: 700; border-radius: 10px; margin-top: 10px; background: rgba(37,99,235,0.08); color: #1d4ed8; border: 1px solid #bfdbfe;"></div>
                            <div id="warning_reading_val" class="alert alert-danger text-center" style="display: none;"></div>

                            <input type="hidden" id="brand_name" value="Standard">
                            <div class="form-group" style="margin-top: 10px; margin-bottom: 5px;">
                                <label style="font-size: 12px; font-weight: 700; color: #475569;"><i class="fa fa-pencil text-danger"></i> Short Note (Optional)</label>
                                <input type="text" id="notes" class="form-control" maxlength="150" placeholder="Optional short note (e.g. brand, broken, court)..." style="border-radius: 8px;">
                            </div>

                            <!-- Live Calculation Preview Card -->
                            <div class="alert alert-info" style="margin-top: 15px;">
                                <div class="row text-center">
                                    <div class="col-md-12">
                                        <strong style="font-size: 15px;">Estimated Session Cost:</strong> <span id="preview_total_amount" class="badge badge-success" style="font-size: 16px; padding: 6px 14px;">₹0.00</span>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="modal-footer">
                            <button type="button" class="btn btn-white" data-dismiss="modal">Close</button>
                            <button type="button" class="btn btn-primary" onclick="saveReading()"><i class="fa fa-save"></i> Save Reading</button>
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
