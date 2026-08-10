<?php
session_start();
?>
<!DOCTYPE html>
<html>

<head>

    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>Financial Year Report</title>

    <link href="../css/bootstrap.min.css" rel="stylesheet">
    <link href="../font-awesome/css/font-awesome.css" rel="stylesheet">

    <link href="../css/animate.css" rel="stylesheet">
    <link href="../css/style.css" rel="stylesheet">
    <script src="https://cdnjs.cloudflare.com/ajax/libs/jquery/3.6.0/jquery.min.js"></script>
    <link href="../css/plugins/sweetalert/sweetalert.css" rel="stylesheet">
    <script src="../js/plugins/sweetalert/sweetalert.min.js"></script>
    <script src="../app_js/sweetalert-finez.js"></script>
    
    <script>
        $(document).ready(function() {
            loadMenu(); // function to load the menu 
            load_closing_years();
            loadGlobalTotals();
        });

        function loadGlobalTotals() {
            $.ajax({
                type: 'POST',
                url: 'api/financial_year_report.php',
                data: { action: 'load_global_totals' },
                success: function(response) {
                    var d = JSON.parse(response);
                    var fmt = function(v) {
                        return new Intl.NumberFormat('en-IN', { style: 'currency', currency: 'INR', minimumFractionDigits: 2 }).format(v);
                    };
                    $('#global_pending_payment').text(fmt(d.total_pending));
                    $('#global_wallet_balance').text(fmt(d.wallet_balance));
                },
                error: function() { console.error('Failed to load global totals'); }
            });
        }

        // Function to load the dynamic financial dashboard
        function loadDashboard(yearId) {
            if (!yearId || yearId == 0) return;
            
            load_overlay(); // Show loading overlay
            
            $.ajax({
                type: "POST",
                url: "api/financial_year_report.php",
                data: {
                    action: 'load_financial_dashboard',
                    year_id: yearId
                },
                success: function(response) {
                    close_overlay();
                    var data = JSON.parse(response);
                    console.log("Dashboard loaded: ", data);
                    
                    // Format numbers helper (en-IN format for Indian Rupees)
                    const fmt = (val) => {
                        return new Intl.NumberFormat('en-IN', {
                            style: 'currency',
                            currency: 'INR',
                            minimumFractionDigits: 2
                        }).format(val);
                    };

                    // Update KPI cards
                    $('#kpi_opening_balance').text(fmt(data.opening_balance));
                    $('#kpi_member_fees_received').text(fmt(data.total_member_fees_received));
                    $('#kpi_other_income_received').text(fmt(data.other_rec_amount));
                    $('#kpi_wallet_net_credits').text(fmt(data.total_wallet_balance));
                    $('#kpi_pending_payment').text(fmt(data.total_pending_receivables));
                    $('#kpi_total_expense').text(fmt(data.other_payments_paid));
                    $('#kpi_closing_balance').text(fmt(data.closing_balance));
                    $('#kpi_total_bank_deposit_year').text(fmt(data.total_bank_deposit_year));
                    $('#kpi_cash_in_hand').text(fmt(data.cash_in_hand));
                    $('#kpi_total_fd_prev').text(fmt(data.total_fd_prev));
                    $('#kpi_savings_interest_year').text(fmt(data.savings_interest_year));
                    $('#kpi_fd_interest_year').text(fmt(data.fd_interest_year));
                    $('#kpi_total_assets').text(fmt(data.total_assets));
                    
                    // Sync the selected year inside the PDF modal dropdown as well
                    $('#selected_year').val(yearId);

                    // Populate Breakdown Tables
                    
                    // 1. Fees Breakdown Table
                    var feesHtml = "";
                    if (data.fees_breakdown && data.fees_breakdown.length > 0) {
                        for (var i = 0; i < data.fees_breakdown.length; i++) {
                            var item = data.fees_breakdown[i];
                            var rec = parseFloat(item.receivable);
                            var rcv = parseFloat(item.received);
                            var pend = rec - rcv;
                            feesHtml += "<tr>";
                            feesHtml += "<td><strong>" + item.head_name + "</strong></td>";
                            feesHtml += "<td>" + fmt(rec) + "</td>";
                            feesHtml += "<td class='text-navy'>" + fmt(rcv) + "</td>";
                            feesHtml += "<td class='" + (pend > 0 ? "text-danger" : "text-muted") + "'>" + fmt(pend) + "</td>";
                            feesHtml += "</tr>";
                        }
                    } else {
                        feesHtml = "<tr><td colspan='4' class='text-center text-muted'>No member fees recorded.</td></tr>";
                    }
                    $('#fees_table_body').html(feesHtml);

                    // 2. Other Income Breakdown Table
                    var otherHtml = "";
                    var showOtherTable = false;
                    if (data.other_breakdown && data.other_breakdown.length > 0) {
                        for (var i = 0; i < data.other_breakdown.length; i++) {
                            var item = data.other_breakdown[i];
                            var rec = parseFloat(item.receivable);
                            var rcv = parseFloat(item.received);
                            var pend = rec - rcv;
                            
                            // Show only if there's any transaction for this head
                            if (rec > 0 || rcv > 0) {
                                showOtherTable = true;
                                otherHtml += "<tr>";
                                otherHtml += "<td><strong>" + item.head_name + "</strong></td>";
                                otherHtml += "<td>" + fmt(rec) + "</td>";
                                otherHtml += "<td class='text-navy'>" + fmt(rcv) + "</td>";
                                otherHtml += "<td class='" + (pend > 0 ? "text-danger" : "text-muted") + "'>" + fmt(pend) + "</td>";
                                otherHtml += "</tr>";
                            }
                        }
                    }
                    
                    // Append Savings Account Interest if present
                    if (parseFloat(data.savings_interest_year) > 0) {
                        showOtherTable = true;
                        otherHtml += "<tr>";
                        otherHtml += "<td><strong>Savings Account Interest</strong></td>";
                        otherHtml += "<td>" + fmt(data.savings_interest_year) + "</td>";
                        otherHtml += "<td class='text-navy'>" + fmt(data.savings_interest_year) + "</td>";
                        otherHtml += "<td class='text-muted'>" + fmt(0) + "</td>";
                        otherHtml += "</tr>";
                    }
                    
                    // Append FD Interest if present
                    if (parseFloat(data.fd_interest_year) > 0) {
                        showOtherTable = true;
                        otherHtml += "<tr>";
                        otherHtml += "<td><strong>FD Account Interest</strong></td>";
                        otherHtml += "<td>" + fmt(data.fd_interest_year) + "</td>";
                        otherHtml += "<td class='text-navy'>" + fmt(data.fd_interest_year) + "</td>";
                        otherHtml += "<td class='text-muted'>" + fmt(0) + "</td>";
                        otherHtml += "</tr>";
                    }
                    
                    if (!showOtherTable) {
                        otherHtml = "<tr><td colspan='4' class='text-center text-muted'>No other payments recorded.</td></tr>";
                    }
                    $('#other_table_body').html(otherHtml);

                    // 3. Payables/Paid Breakdown Table
                    var payableHtml = "";
                    if (data.payable_breakdown && data.payable_breakdown.length > 0) {
                        var showPayableTable = false;
                        for (var i = 0; i < data.payable_breakdown.length; i++) {
                            var item = data.payable_breakdown[i];
                            var pay = parseFloat(item.payable);
                            var pd = parseFloat(item.paid);
                            var pend = pay - pd;
                            
                            if (pay > 0 || pd > 0) {
                                showPayableTable = true;
                                payableHtml += "<tr>";
                                payableHtml += "<td><strong>" + item.head_name + "</strong></td>";
                                payableHtml += "<td>" + fmt(pay) + "</td>";
                                payableHtml += "<td class='text-danger'>" + fmt(pd) + "</td>";
                                payableHtml += "<td class='" + (pend > 0 ? "text-warning" : "text-muted") + "'>" + fmt(pend) + "</td>";
                                payableHtml += "</tr>";
                            }
                        }
                        if (!showPayableTable) {
                            payableHtml = "<tr><td colspan='4' class='text-center text-muted'>No expenses recorded.</td></tr>";
                        }
                    } else {
                        payableHtml = "<tr><td colspan='4' class='text-center text-muted'>No expenses recorded.</td></tr>";
                    }
                    $('#payable_table_body').html(payableHtml);

                    // 4. Interest Credits Breakdown Table (sorted by credit date DESC)
                    var interestList = [];
                    if (data.savings_interest_details && data.savings_interest_details.length > 0) {
                        for (var i = 0; i < data.savings_interest_details.length; i++) {
                            var item = data.savings_interest_details[i];
                            interestList.push({
                                date: item.date,
                                source: "Savings Account",
                                description: item.description ? item.description : "Savings Interest Credit",
                                amount: parseFloat(item.amount)
                            });
                        }
                    }
                    if (data.fd_interest_details && data.fd_interest_details.length > 0) {
                        for (var i = 0; i < data.fd_interest_details.length; i++) {
                            var item = data.fd_interest_details[i];
                            interestList.push({
                                date: item.date,
                                source: "FD (No: " + item.fd_no + ", " + item.bank_name + ")",
                                description: item.description ? item.description : "Fixed Deposit Interest Credit",
                                amount: parseFloat(item.amount)
                            });
                        }
                    }
                    
                    // Sort by date DESC
                    interestList.sort(function(a, b) {
                        return new Date(b.date) - new Date(a.date);
                    });

                    var interestHtml = "";
                    if (interestList.length > 0) {
                        for (var i = 0; i < interestList.length; i++) {
                            var item = interestList[i];
                            interestHtml += "<tr>";
                            interestHtml += "<td>" + item.date + "</td>";
                            interestHtml += "<td><strong>" + item.source + "</strong></td>";
                            interestHtml += "<td>" + item.description + "</td>";
                            interestHtml += "<td class='text-navy'>" + fmt(item.amount) + "</td>";
                            interestHtml += "</tr>";
                        }
                    } else {
                        interestHtml = "<tr><td colspan='4' class='text-center text-muted'>No interest transactions recorded for this period.</td></tr>";
                    }
                    $('#interest_table_body').html(interestHtml);
                },
                error: function(xhr, status, error) {
                    close_overlay();
                    console.error('AJAX error: ', status, error);
                    alert("Error loading dashboard data.");
                }
            });
        }

        function load_closing_years(){
            $.ajax({
                type: "POST",
                url: "api/financial_year_report.php",
                data: {
                    action: 'load_closing_years',
                },
                success: function(data) {
                    var obj = jQuery.parseJSON(data);
                    
                    // Populate main page year dropdown
                    var mainHtm = "";
                    for (var i = 0; i < obj[0].length; i++) {
                        mainHtm += "<option value='" + obj[0][i].id + "'>" + obj[0][i].from_year + " - " + obj[0][i].to_year + "</option>";
                    }
                    $('#main_selected_year').html(mainHtm);

                    // Load dashboard data for the first (latest) year
                    if (obj[0] && obj[0].length > 0) {
                        var latestYearId = obj[0][0].id;
                        $('#main_selected_year').val(latestYearId);
                        loadDashboard(latestYearId);
                    }
                },
                error: function(xhr, status, error) {
                    console.error('AJAX error: ', status, error);
                }
            });
        }

        // function to open the print dialog directly in the current window using an iframe
        function printReport() {
            const selected_year = $('#main_selected_year').val();
            if (!selected_year || selected_year == 0) {
                alert("Please select a financial year.");
                return;
            }
            
            load_overlay();
            
            const formData = new FormData();
            formData.append("selected_type", 5);
            formData.append("selected_year", selected_year);

            fetch('../invoice/financial_year_summary.php', {
                method: "POST",
                body: formData,
            })
            .then(async (response) => {
                close_overlay();
                const contentType = response.headers.get("Content-Type");

                if (!response.ok || !contentType.includes("application/pdf")) {
                    const text = await response.text();
                    alert("Server Error: " + text);
                    return;
                }

                const blob = await response.blob();
                const blobURL = URL.createObjectURL(blob);

                // Get or create hidden iframe
                let iframe = document.getElementById('print_iframe');
                if (!iframe) {
                    iframe = document.createElement('iframe');
                    iframe.id = 'print_iframe';
                    iframe.style.position = 'fixed';
                    iframe.style.right = '0';
                    iframe.style.bottom = '0';
                    iframe.style.width = '0';
                    iframe.style.height = '0';
                    iframe.style.border = '0';
                    document.body.appendChild(iframe);
                }

                iframe.src = blobURL;
                iframe.onload = function() {
                    setTimeout(function() {
                        iframe.contentWindow.focus();
                        iframe.contentWindow.print();
                    }, 200);
                };
            })
            .catch((error) => {
                close_overlay();
                alert("AJAX Error: " + error);
            });
        }
    </script>
    
    <style>
        @import url('https://fonts.googleapis.com/css2?family=Outfit:wght@300;400;500;600;700;800&display=swap');

        /* Custom Font and Global overrides */
        body, h1, h2, h3, h4, h5, h6, .form-control, button, td, th {
            font-family: 'Outfit', sans-serif !important;
        }
        
        body {
            background-color: #f1f5f9;
        }

        #page-wrapper {
            background: #f8fafc !important;
        }

        .dashboard-container {
            padding: 20px 0;
        }

        /* KPI Card Styling with modern premium aesthetics */
        .kpi-card {
            border-radius: 16px;
            color: #ffffff !important;
            padding: 24px;
            margin-bottom: 25px;
            box-shadow: 0 10px 25px -5px rgba(0, 0, 0, 0.1), 0 8px 10px -6px rgba(0, 0, 0, 0.05);
            transition: all 0.4s cubic-bezier(0.4, 0, 0.2, 1);
            position: relative;
            overflow: hidden;
            display: block;
            min-height: 125px;
            border: 1px solid rgba(255, 255, 255, 0.15);
        }

        .kpi-card::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: linear-gradient(180deg, rgba(255, 255, 255, 0.15) 0%, rgba(255, 255, 255, 0) 100%);
            opacity: 1;
            transition: opacity 0.3s ease;
        }

        .kpi-card * {
            color: #ffffff !important;
            position: relative;
            z-index: 2;
        }

        .kpi-card:hover {
            transform: translateY(-6px);
            box-shadow: 0 20px 25px -5px rgba(0, 0, 0, 0.15), 0 10px 10px -5px rgba(0, 0, 0, 0.04);
            border-color: rgba(255, 255, 255, 0.3);
        }

        .kpi-card .icon-wrapper {
            position: absolute;
            right: 15px;
            bottom: -5px;
            font-size: 65px;
            opacity: 0.18 !important;
            transition: all 0.4s ease;
            z-index: 1;
        }

        .kpi-card:hover .icon-wrapper {
            transform: scale(1.15) rotate(-10deg);
            opacity: 0.30 !important;
        }

        .kpi-card .card-title {
            font-size: 11.5px;
            text-transform: uppercase;
            letter-spacing: 1.2px;
            font-weight: 600;
            margin-bottom: 10px;
            opacity: 0.95;
        }

        .kpi-card .card-value {
            font-size: 26px;
            font-weight: 800;
            margin-bottom: 6px;
            white-space: nowrap;
            letter-spacing: -0.5px;
        }

        .kpi-card .card-subtitle {
            font-size: 11.5px;
            opacity: 0.85;
            font-weight: 400;
        }
        
        /* Premium Gradients */
        .bg-gradient-opening {
            background: linear-gradient(135deg, #0f172a 0%, #1e293b 100%) !important;
        }
        .bg-gradient-monthly {
            background: linear-gradient(135deg, #059669 0%, #10b981 100%) !important;
        }
        .bg-gradient-membership {
            background: linear-gradient(135deg, #0ea5e9 0%, #0284c7 100%) !important;
        }
        .bg-gradient-other-rec {
            background: linear-gradient(135deg, #6366f1 0%, #4f46e5 100%) !important;
        }
        .bg-gradient-wallet {
            background: linear-gradient(135deg, #d946ef 0%, #a21caf 100%) !important;
        }
        .bg-gradient-pending {
            background: linear-gradient(135deg, #f59e0b 0%, #d97706 100%) !important;
        }
        .bg-gradient-expense {
            background: linear-gradient(135deg, #ef4444 0%, #dc2626 100%) !important;
        }
        .bg-gradient-closing {
            background: linear-gradient(135deg, #06b6d4 0%, #0891b2 100%) !important;
        }
        .bg-gradient-fd {
            background: linear-gradient(135deg, #4f46e5 0%, #6366f1 100%) !important;
        }

        /* Titles and Section Headers */
        .section-title {
            font-weight: 800;
            color: #0f172a;
            margin: 35px 0 20px 0;
            padding-bottom: 12px;
            border-bottom: 2px solid #e2e8f0;
            font-size: 20px;
            display: flex;
            align-items: center;
            gap: 10px;
            letter-spacing: -0.3px;
        }
        
        .section-title i {
            color: #3b82f6;
        }

        /* Modern Table Card Design */
        .modern-card {
            background: #ffffff;
            border-radius: 16px;
            border: 1px solid #e2e8f0;
            box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.05), 0 2px 4px -1px rgba(0, 0, 0, 0.02);
            margin-bottom: 30px;
            overflow: hidden;
            transition: box-shadow 0.3s ease;
        }

        .modern-card:hover {
            box-shadow: 0 10px 15px -3px rgba(0, 0, 0, 0.08), 0 4px 6px -4px rgba(0, 0, 0, 0.03);
        }

        .modern-card-header {
            background: #f8fafc;
            padding: 18px 24px;
            font-weight: 700;
            font-size: 15px;
            border-bottom: 1px solid #e2e8f0;
            color: #1e293b;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .modern-card-body {
            padding: 24px;
        }

        /* Modern Table styling */
        .table-modern {
            width: 100%;
            margin-bottom: 0;
            border-collapse: collapse;
        }
        
        .table-modern th {
            background-color: #f8fafc !important;
            color: #475569 !important;
            font-weight: 600 !important;
            text-transform: uppercase;
            font-size: 11px;
            letter-spacing: 0.8px;
            border: none !important;
            border-bottom: 1.5px solid #cbd5e1 !important;
            padding: 14px 20px !important;
        }
        
        .table-modern td {
            padding: 14px 20px !important;
            vertical-align: middle !important;
            border-top: 1px solid #e2e8f0 !important;
            border-bottom: 1px solid #e2e8f0 !important;
            color: #334155;
            font-size: 13.5px;
            font-weight: 500;
        }
        
        .table-modern tbody tr {
            transition: background-color 0.2s ease;
        }

        .table-modern tbody tr:hover {
            background-color: #f8fafc !important;
        }
        
        /* Badges */
        .badge {
            font-size: 11px;
            padding: 5px 10px;
            border-radius: 6px;
            font-weight: 600;
            letter-spacing: 0.3px;
        }

        .badge-pending {
            background-color: #fee2e2 !important;
            color: #b91c1c !important;
        }
        
        .badge-received {
            background-color: #d1fae5 !important;
            color: #065f46 !important;
        }

        .text-navy {
            color: #047857 !important;
            font-weight: 700;
        }

        .text-danger {
            color: #b91c1c !important;
            font-weight: 700;
        }

        .text-warning {
            color: #d97706 !important;
            font-weight: 700;
        }
        
        .text-muted {
            color: #94a3b8 !important;
            font-weight: 400;
        }
        
        /* Dropdown selector styling */
        select.form-control {
            border: 1px solid #cbd5e1;
            box-shadow: 0 1px 2px 0 rgba(0, 0, 0, 0.05);
            transition: all 0.2s ease;
            height: auto !important;
            padding: 5px 10px;
        }
        
        select.form-control:focus {
            border-color: #3b82f6;
            box-shadow: 0 0 0 3px rgba(59, 130, 246, 0.15);
        }
    </style>
    
    <script src="../app_pagination/pagination.js"></script>
    <script src="../app_menu/menu.js"></script>
</head>

<body>
    <input type="hidden" id="hdn_current_page"  value="0">
    <input type="hidden" id="hdn_id"  value="0">
    <input type="hidden" id="hdn_payment_id"  value="0">
    <input type="hidden" id="hdn_recieveble_id"  value="0">
    <input type="hidden" id="hdn_amount"  value="0">

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
             
            <!-- search bar starts -->
            <div class="row wrapper border-bottom white-bg page-heading">
                <div class="col-sm-6">
                    <h2 style="font-weight: 700; color: #1a202c; margin-top: 15px;">Financial Year Report</h2>
                    <ol class="breadcrumb">
                        <li><a href="reports.php">Reports</a></li>
                        <li class="active"><strong>Financial Dashboard</strong></li>
                    </ol>
                </div>
                <div class="col-sm-6 text-right" style="padding-top: 20px; display: flex; justify-content: flex-end; align-items: center; gap: 12px; flex-wrap: wrap;">
                    <div style="display: flex; align-items: center; gap: 8px;">
                        <span style="font-weight: 600; color: #4a5568;">Select Year:</span>
                        <select id="main_selected_year" class="form-control input-sm" style="width: 160px; font-weight: bold; border-radius: 6px;" onchange="loadDashboard(this.value)">
                            <!-- loaded dynamically -->
                        </select>
                    </div>
                    <button type="button" class="btn btn-primary btn-sm" style="border-radius: 6px;" onclick="printReport()"><i class="fa fa-print"></i> Print PDF Report</button>
                </div>
            </div>
            <!-- search bar ends -->

            <div class="wrapper wrapper-content animated fadeInRight">

                <!-- KPI ROW 1 -->
                <div class="row">
                    <div class="col-lg-3 col-md-6">
                        <div class="kpi-card bg-gradient-opening">
                            <div class="icon-wrapper"><i class="fa fa-briefcase"></i></div>
                            <div class="card-title">Opening Balance</div>
                            <div class="card-value" id="kpi_opening_balance">₹0.00</div>
                            <div class="card-subtitle">Cash In Hand + Bank Balance</div>
                        </div>
                    </div>
                    
                    <div class="col-lg-3 col-md-6">
                        <div class="kpi-card bg-gradient-monthly">
                            <div class="icon-wrapper"><i class="fa fa-calendar-check-o"></i></div>
                            <div class="card-title">Member Fees Received</div>
                            <div class="card-value" id="kpi_member_fees_received">₹0.00</div>
                            <div class="card-subtitle">Current financial year</div>
                        </div>
                    </div>
                    
                    <div class="col-lg-3 col-md-6">
                        <div class="kpi-card bg-gradient-other-rec">
                            <div class="icon-wrapper"><i class="fa fa-money"></i></div>
                            <div class="card-title">Other Income Received</div>
                            <div class="card-value" id="kpi_other_income_received">₹0.00</div>
                            <div class="card-subtitle">Other income</div>
                        </div>
                    </div>

                    <div class="col-lg-3 col-md-6">
                        <div class="kpi-card bg-gradient-wallet">
                            <div class="icon-wrapper"><i class="fa fa-google-wallet"></i></div>
                            <div class="card-title">Total Wallet Balance</div>
                            <div class="card-value" id="kpi_wallet_net_credits">₹0.00</div>
                            <div class="card-subtitle">Total accumulated wallet balance</div>
                        </div>
                    </div>
                </div>

                <!-- KPI ROW 2 -->
                <div class="row">
                    <div class="col-lg-4 col-md-4">
                        <div class="kpi-card bg-gradient-pending">
                            <div class="icon-wrapper"><i class="fa fa-exclamation-triangle"></i></div>
                            <div class="card-title">All-time unpaid receivables</div>
                            <div class="card-value" id="kpi_pending_payment">₹0.00</div>
                            <div class="card-subtitle">All-time unpaid receivables</div>
                        </div>
                    </div>

                    <div class="col-lg-4 col-md-4">
                        <div class="kpi-card bg-gradient-expense">
                            <div class="icon-wrapper"><i class="fa fa-minus-circle"></i></div>
                            <div class="card-title">Total Expense</div>
                            <div class="card-value" id="kpi_total_expense">₹0.00</div>
                            <div class="card-subtitle">Paid expenses in current year</div>
                        </div>
                    </div>

                    <div class="col-lg-4 col-md-4">
                        <div class="kpi-card bg-gradient-closing">
                            <div class="icon-wrapper"><i class="fa fa-balance-scale"></i></div>
                            <div class="card-title">Closing Balance</div>
                            <div class="card-value" id="kpi_closing_balance">₹0.00</div>
                            <div class="card-subtitle">Bank Balance + Cash in Hand</div>
                        </div>
                    </div>
                </div>

                <!-- KPI ROW 3 -->
                <div class="row">
                    <div class="col-lg-3 col-md-6">
                        <div class="kpi-card bg-gradient-opening">
                            <div class="icon-wrapper"><i class="fa fa-university"></i></div>
                            <div class="card-title">Current Savings Bank Balance</div>
                            <div class="card-value" id="kpi_total_bank_deposit_year">₹0.00</div>
                            <div class="card-subtitle">Bank Balance</div>
                        </div>
                    </div>

                    <div class="col-lg-3 col-md-6">
                        <div class="kpi-card bg-gradient-monthly">
                            <div class="icon-wrapper"><i class="fa fa-money"></i></div>
                            <div class="card-title">Cash in Hand</div>
                            <div class="card-value" id="kpi_cash_in_hand">₹0.00</div>
                            <div class="card-subtitle">Closing Balance - Bank Deposits</div>
                        </div>
                    </div>

                    <div class="col-lg-3 col-md-6">
                        <div class="kpi-card bg-gradient-fd">
                            <div class="icon-wrapper"><i class="fa fa-folder-open"></i></div>
                            <div class="card-title">Total FD Amount</div>
                            <div class="card-value" id="kpi_total_fd_prev">₹0.00</div>
                            <div class="card-subtitle">Principal + Interest (up to Year End)</div>
                        </div>
                    </div>

                    <div class="col-lg-3 col-md-6">
                        <div class="kpi-card" style="background: linear-gradient(135deg, #f59e0b 0%, #b45309 100%) !important;">
                            <div class="icon-wrapper"><i class="fa fa-briefcase"></i></div>
                            <div class="card-title">Total Assets (Bank+Cash+FD)</div>
                            <div class="card-value" id="kpi_total_assets">₹0.00</div>
                            <div class="card-subtitle">Total Accumulated Funds</div>
                        </div>
                    </div>
                </div>

                <!-- KPI ROW 4 (Interest Received) -->
                <div class="row">
                    <div class="col-lg-6 col-md-6">
                        <div class="kpi-card" style="background: linear-gradient(135deg, #0284c7 0%, #0369a1 100%) !important;">
                            <div class="icon-wrapper"><i class="fa fa-line-chart"></i></div>
                            <div class="card-title">Savings Account Interest</div>
                            <div class="card-value" id="kpi_savings_interest_year">₹0.00</div>
                            <div class="card-subtitle">Interest earned in savings account this year</div>
                        </div>
                    </div>

                    <div class="col-lg-6 col-md-6">
                        <div class="kpi-card" style="background: linear-gradient(135deg, #7c3aed 0%, #6d28d9 100%) !important;">
                            <div class="icon-wrapper"><i class="fa fa-percent"></i></div>
                            <div class="card-title">FD Account Interest</div>
                            <div class="card-value" id="kpi_fd_interest_year">₹0.00</div>
                            <div class="card-subtitle">Interest earned from FDs this year</div>
                        </div>
                    </div>
                </div>

                <!-- Detailed Tables Row -->
                <div class="row">
                    
                    <!-- Member Fees Breakdown -->
                    <div class="col-lg-6">
                        <div class="modern-card">
                            <div class="modern-card-header">
                                <span><i class="fa fa-university text-navy"></i> Member Fees Breakdown</span>
                                <span class="badge badge-received">Credit</span>
                            </div>
                            <div class="modern-card-body table-responsive" style="padding:0;">
                                <table class="table table-modern table-striped">
                                    <thead>
                                        <tr>
                                            <th>Fee Category</th>
                                            <th>Receivable</th>
                                            <th>Received</th>
                                            <th>Pending</th>
                                        </tr>
                                    </thead>
                                    <tbody id="fees_table_body">
                                        <!-- Dynamic rows -->
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>

                    <!-- Other Income Breakdown -->
                    <div class="col-lg-6">
                        <div class="modern-card">
                            <div class="modern-card-header">
                                <span><i class="fa fa-plus-circle text-success"></i> Other Payments / Incomes</span>
                                <span class="badge badge-received">Credit</span>
                            </div>
                            <div class="modern-card-body table-responsive" style="padding:0;">
                                <table class="table table-modern table-striped">
                                    <thead>
                                        <tr>
                                            <th>Category</th>
                                            <th>Receivable</th>
                                            <th>Received</th>
                                            <th>Pending</th>
                                        </tr>
                                    </thead>
                                    <tbody id="other_table_body">
                                        <!-- Dynamic rows -->
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>

                </div>

                <div class="row">
                    
                    <!-- Payables / Paid Expenses Breakdown -->
                    <div class="col-lg-12">
                        <div class="modern-card">
                            <div class="modern-card-header">
                                <span><i class="fa fa-minus-circle text-danger"></i> Expenses / Payables Summary</span>
                                <span class="badge badge-pending">Debit</span>
                            </div>
                            <div class="modern-card-body table-responsive" style="padding:0;">
                                <table class="table table-modern table-striped">
                                    <thead>
                                        <tr>
                                            <th>Expense Category</th>
                                            <th>Total Payable</th>
                                            <th>Total Paid</th>
                                            <th>Pending / Balance Owed</th>
                                        </tr>
                                    </thead>
                                    <tbody id="payable_table_body">
                                        <!-- Dynamic rows -->
                                    </tbody>
                                </table>
                            </div>
                        </div>
                </div>

                <div class="row">
                    <!-- Interest Credits Breakdown -->
                    <div class="col-lg-12">
                        <div class="modern-card">
                            <div class="modern-card-header">
                                <span><i class="fa fa-percent text-success"></i> Interest Credits Breakdown (by Credited Date)</span>
                                <span class="badge badge-received">Credit</span>
                            </div>
                            <div class="modern-card-body table-responsive" style="padding:0;">
                                <table class="table table-modern table-striped">
                                    <thead>
                                        <tr>
                                            <th>Date</th>
                                            <th>Account / Source</th>
                                            <th>Description</th>
                                            <th>Amount Received</th>
                                        </tr>
                                    </thead>
                                    <tbody id="interest_table_body">
                                        <!-- Dynamic rows -->
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
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

</body>

</html>
