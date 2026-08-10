<?php
session_start();
session_write_close();

if (empty($_SESSION['login_id'])) {
    header("Location: ../app_login_manager/logout.php");
    exit();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="description" content="Member Cash Book Report - YMCA Management System">
    <title>YMCA | Member Cash Book</title>
    <!-- Mobile redirect: send non-admin member logins to mobile portal on small screens -->
    <script>
        (function(){
            if(<?php echo ($_SESSION['login_id'] != 1) ? 'true' : 'false'; ?> && window.innerWidth < 768 && !window.location.href.includes('desktop=1')){
                window.location.replace('mobile/ledger.php');
            }
        })();
    </script>

    <link href="../css/bootstrap.min.css" rel="stylesheet">
    <link href="../font-awesome/css/font-awesome.css" rel="stylesheet">
    <link href="../css/animate.css" rel="stylesheet">
    <link href="../css/style.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <link href="../css/plugins/sweetalert/sweetalert.css" rel="stylesheet">
    
    <style>
        *, *::before, *::after { box-sizing: border-box; }
        body, #wrapper { font-family: 'Inter','Segoe UI',sans-serif !important; background: #f0f4ff !important; }

        /* ---- Top Bar ---- */
        .rep-topbar {
            background: #fff;
            border-bottom: 1px solid #e8edf5;
            padding: 0 28px; height: 62px;
            display: flex; align-items: center; justify-content: space-between;
            box-shadow: 0 1px 6px rgba(59,130,246,0.06);
            position: sticky; top: 0; z-index: 100;
        }
        .rep-topbar-left { display: flex; align-items: center; gap: 14px; }
        .rep-hamburger {
            width: 38px; height: 38px;
            background: linear-gradient(135deg,#3b82f6,#6366f1);
            border: none; border-radius: 10px; color: #fff;
            font-size: 15px; cursor: pointer;
            display: flex; align-items: center; justify-content: center;
        }
        .rep-topbar-title { font-size: 17px; font-weight: 700; color: #1e293b; }
        .rep-topbar-title span { color: #3b82f6; }
        .rep-logout {
            display: inline-flex; align-items: center; gap: 7px;
            padding: 8px 18px; background: #fff;
            border: 1.5px solid #e8edf5; border-radius: 10px;
            color: #64748b; font-size: 13.5px; font-weight: 500;
            text-decoration: none; transition: all 0.18s;
        }
        .rep-logout:hover { border-color: #3b82f6; color: #3b82f6; text-decoration: none; }

        /* ---- Content ---- */
        .rep-content { padding: 24px 28px; }

        /* ---- Filter Card ---- */
        .rep-filter-card {
            background: #fff; border-radius: 18px;
            border: 1px solid #e8edf5;
            box-shadow: 0 2px 12px rgba(0,0,0,0.05);
            padding: 22px 26px; margin-bottom: 22px;
        }
        .rep-filter-card h2 {
            font-size: 16px; font-weight: 700; color: #1e293b;
            display: flex; align-items: center; gap: 9px;
            margin: 0 0 18px;
        }
        .rep-filter-card h2 i {
            width: 32px; height: 32px; border-radius: 9px;
            background: linear-gradient(135deg,#3b82f6,#6366f1);
            display: flex; align-items: center; justify-content: center;
            color: #fff; font-size: 14px;
        }
        .rep-filter-row {
            display: flex; align-items: flex-end; gap: 16px; flex-wrap: wrap;
        }
        .rep-filter-field { display: flex; flex-direction: column; gap: 5px; }
        .rep-filter-field label {
            font-size: 12px; font-weight: 600;
            color: #64748b; text-transform: uppercase; letter-spacing: 0.05em;
        }
        .rep-filter-field select,
        .rep-filter-field input[type="text"] {
            padding: 10px 14px; border-radius: 10px;
            border: 1.5px solid #e2e8f0; background: #f8faff;
            font-size: 14px; font-weight: 500; color: #1e293b;
            font-family: 'Inter', sans-serif;
            outline: none; transition: border-color 0.2s, box-shadow 0.2s;
            min-width: 180px;
        }
        .rep-filter-field select:focus,
        .rep-filter-field input[type="text"]:focus {
            border-color: #3b82f6;
            box-shadow: 0 0 0 3px rgba(59,130,246,0.1);
        }
        .rep-btn {
            display: inline-flex; align-items: center; gap: 7px;
            padding: 10px 20px; border-radius: 10px;
            font-size: 13.5px; font-weight: 600; cursor: pointer;
            transition: all 0.18s; border: none;
            font-family: 'Inter', sans-serif;
        }
        .rep-btn-primary {
            background: linear-gradient(135deg,#3b82f6,#6366f1);
            color: #fff; box-shadow: 0 3px 12px rgba(59,130,246,0.3);
        }
        .rep-btn-primary:hover { opacity: 0.9; transform: translateY(-1px); }
        .rep-btn-outline {
            background: #fff; color: #475569;
            border: 1.5px solid #e2e8f0;
        }
        .rep-btn-outline:hover { border-color: #94a3b8; background: #f8faff; }

        /* ---- Members Grid Table ---- */
        .rep-table-card {
            background: #fff; border-radius: 18px;
            border: 1px solid #e8edf5;
            box-shadow: 0 2px 12px rgba(0,0,0,0.05);
            overflow: hidden;
            width: 100%;
        }
        .rep-table-header {
            padding: 18px 24px 14px;
            border-bottom: 1px solid #f1f5f9;
            display: flex; align-items: center; justify-content: space-between; flex-wrap: wrap; gap: 10px;
        }
        .rep-table-title {
            font-size: 15px; font-weight: 700; color: #1e293b;
            display: flex; align-items: center; gap: 9px; margin: 0;
        }
        .rep-table-title i {
            width: 30px; height: 30px; border-radius: 8px;
            background: linear-gradient(135deg,#3b82f6,#6366f1);
            display: flex; align-items: center; justify-content: center;
            color: #fff; font-size: 13px;
        }
        .rep-table-wrap {
            overflow-x: auto;
            width: 100%;
        }
        .table-custom {
            width: 100%;
            border-collapse: collapse;
            font-size: 14px;
        }
        .table-custom th {
            background: #f8faff; padding: 14px 20px;
            font-weight: 600; color: #475569; text-align: left;
            border-bottom: 1.5px solid #e2e8f0; font-size: 12.5px;
            text-transform: uppercase; letter-spacing: 0.05em;
        }
        .table-custom td {
            padding: 16px 20px; border-bottom: 1px solid #f1f5f9;
            color: #334155; font-size: 14px; vertical-align: middle;
        }
        .table-custom tbody tr:hover { background: #f8faff; }

        /* Member Row info */
        .member-info-cell { display: flex; align-items: center; gap: 12px; }
        .member-avatar {
            width: 44px; height: 44px; border-radius: 50%;
            object-fit: cover; border: 2px solid #fff;
            box-shadow: 0 2px 8px rgba(0,0,0,0.08);
        }
        .member-details-cell { display: flex; flex-direction: column; }
        .member-name { font-weight: 600; color: #1e293b; font-size: 14px; }
        .member-group { font-size: 11.5px; color: #3b82f6; font-weight: 500; margin-top: 2px; }

        /* Detail View Controls */
        .details-header-card {
            background: #fff; border-radius: 18px;
            border: 1px solid #e8edf5; padding: 18px 24px;
            box-shadow: 0 2px 12px rgba(0,0,0,0.04);
            margin-bottom: 22px; display: flex; align-items: center; justify-content: space-between;
            flex-wrap: wrap; gap: 12px;
        }
        .details-header-left { display: flex; align-items: center; gap: 14px; }
        .details-member-summary { display: flex; align-items: center; gap: 12px; }
        .details-member-summary img {
            width: 46px; height: 46px; border-radius: 50%; object-fit: cover;
            border: 2.5px solid #e2e8f0;
        }
        .details-member-name { font-weight: 700; color: #1e293b; font-size: 16px; margin: 0; }
        .details-member-group { font-size: 12px; color: #64748b; margin-top: 2px; }

        /* KPI Badge Panel */
        .kpi-row { display: grid; grid-template-columns: repeat(4, 1fr); gap: 16px; margin-bottom: 22px; }
        @media(max-width: 991px) { .kpi-row { grid-template-columns: repeat(2, 1fr); } }
        @media(max-width: 575px) { .kpi-row { grid-template-columns: 1fr; } }
        .kpi-card {
            background: #fff; border-radius: 18px; border: 1px solid #e8edf5;
            padding: 20px; display: flex; flex-direction: column;
            box-shadow: 0 2px 10px rgba(0,0,0,0.03); position: relative; overflow: hidden;
        }
        .kpi-card::before {
            content: ''; position: absolute; left: 0; top: 0; bottom: 0; width: 4.5px;
        }
        .kpi-card.kpi-op::before { background: #64748b; }
        .kpi-card.kpi-deb::before { background: #ef4444; }
        .kpi-card.kpi-cred::before { background: #10b981; }
        .kpi-card.kpi-bal::before { background: #3b82f6; }
        
        .kpi-title { font-size: 12px; font-weight: 600; color: #64748b; text-transform: uppercase; letter-spacing: 0.05em; }
        .kpi-val { font-size: 24px; font-weight: 800; color: #1e293b; margin-top: 6px; }
        .kpi-val.text-danger { color: #ef4444; }
        .kpi-val.text-success { color: #10b981; }
        .kpi-val.text-primary { color: #3b82f6; }

        /* Ledger Table styles */
        .ledger-type-badge {
            font-size: 11px; font-weight: 700; padding: 3px 8px; border-radius: 5px;
            text-transform: uppercase; display: inline-block; text-align: center;
        }
        .ledger-type-badge.type-debit { background: #fef2f2; color: #ef4444; }
        .ledger-type-badge.type-credit { background: #ecfdf5; color: #10b981; }

        .text-debit { color: #ef4444; font-weight: 600; }
        .text-credit { color: #10b981; font-weight: 600; }
        .text-bold { font-weight: 700; color: #1e293b; }

        /* Print styles */
        #print_layout { display: none; }
        @media print {
            body { background: #fff !important; color: #000 !important; font-size: 12px !important; }
            #wrapper { display: none !important; }
            #print_layout { display: block !important; padding: 20px; }
            .print-header { border-bottom: 2px solid #334155; padding-bottom: 12px; margin-bottom: 20px; display: flex; justify-content: space-between; align-items: flex-end; }
            .print-header h1 { font-size: 20px; font-weight: 800; color: #0f172a; margin: 0; }
            .print-header p { margin: 3px 0 0 0; color: #475569; font-size: 12px; }
            
            .print-profile-grid { display: grid; grid-template-columns: 1fr 1fr; gap: 20px; margin-bottom: 20px; }
            .print-profile-card { border: 1px solid #cbd5e1; border-radius: 8px; padding: 14px; background: #f8fafc; }
            .print-profile-card table { width: 100%; }
            .print-profile-card table td { padding: 4px 6px; font-size: 12px; }
            
            .print-summary-grid { display: grid; grid-template-columns: repeat(4, 1fr); gap: 10px; margin-bottom: 20px; }
            .print-summary-card { border: 1.5px solid #94a3b8; border-radius: 6px; padding: 10px; text-align: center; }
            .print-summary-card strong { display: block; font-size: 10px; color: #475569; text-transform: uppercase; margin-bottom: 3px; }
            .print-summary-card span { font-size: 16px; font-weight: 800; color: #0f172a; }

            .print-table { width: 100%; border-collapse: collapse; margin-top: 10px; }
            .print-table th { background: #f1f5f9 !important; border: 1px solid #cbd5e1; padding: 8px 10px; font-weight: 700; font-size: 10px; text-transform: uppercase; text-align: left; }
            .print-table td { border: 1px solid #e2e8f0; padding: 8px 10px; font-size: 11px; }
            .print-text-right { text-align: right; }
        }
    </style>
</head>
<body>
    <input type="hidden" id="hdn_current_page" value="1">
    <input type="hidden" id="hdn_selected_member_id" value="<?php echo ($_SESSION['login_id'] != 1) ? $_SESSION['user_id'] : '0'; ?>">

    <div id="wrapper">
        <!-- Sidebar Navigation -->
        <nav class="navbar-default navbar-static-side" role="navigation">
            <div class="dropdown profile-element">
                <center>
                    <span><img alt="image" class="img-circle" src="../img/customer.png" style="padding-top: 20px;"/></span>
                    <span class="clear"> 
                        <span class="block m-t-xs"> 
                            <strong class="font-bold"><?php echo htmlspecialchars($_SESSION['name']); ?></strong>
                        </span>
                    </span>
                </center>
            </div>
            <div class="sidebar-collapse" id="divMenuContainer">
                <!-- Loaded via AJAX -->
            </div>
        </nav>

        <div id="page-wrapper" class="gray-bg">
            <!-- Topbar header -->
            <div class="row border-bottom">
                <nav class="navbar navbar-static-top" role="navigation" style="margin-bottom: 0">
                    <div class="navbar-header">
                        <button class="rep-hamburger minimalize-styl-2 btn btn-primary navbar-minimalize" type="button"><i class="fa fa-bars"></i></button>
                    </div>
                    <ul class="nav navbar-top-links navbar-right">     
                        <li>
                            <a href="../app_login_manager/logout.php" class="rep-logout">
                                <i class="fa fa-sign-out"></i> Log out
                            </a>
                        </li>
                    </ul>
                </nav>
            </div>

            <div class="rep-content">
                <!-- ================= STATE 1: MEMBERS LIST VIEW ================= -->
                <div id="members_list_view" <?php if ($_SESSION['login_id'] != 1) echo 'style="display: none;"'; ?>>
                    <!-- Filter card -->
                    <div class="rep-filter-card">
                        <h2><i class="fa fa-book"></i> Member Cash Book Report</h2>
                        <div class="rep-filter-row">
                            <div class="rep-filter-field">
                                <label for="txt_search">Search Member</label>
                                <input type="text" id="txt_search" placeholder="Name or phone..." onkeyup="searchMembers()">
                            </div>
                            <div class="rep-filter-field">
                                <label for="sel_financial_year">Financial Year</label>
                                <select id="sel_financial_year">
                                    <!-- Populated via Javascript -->
                                </select>
                            </div>
                            <button type="button" class="rep-btn rep-btn-primary" onclick="searchMembers()">
                                <i class="fa fa-search"></i> Search
                            </button>
                            <button type="button" class="rep-btn rep-btn-outline" onclick="clearFilters()">
                                Clear
                            </button>
                        </div>
                    </div>

                    <!-- Members list table card -->
                    <div class="rep-table-card">
                        <div class="rep-table-header">
                            <h3 class="rep-table-title"><i class="fa fa-users"></i> YMCA Members</h3>
                        </div>
                        <div class="rep-table-wrap">
                            <table class="table-custom" id="tbl_members_list">
                                <thead>
                                    <tr>
                                        <th style="width: 80px;">No</th>
                                        <th>Member Details</th>
                                        <th>Phone Number</th>
                                        <th>Action</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <!-- Injected via AJAX -->
                                </tbody>
                            </table>
                        </div>
                        <div id="members_pagination" style="padding: 14px 20px;"></div>
                    </div>
                </div>

                <!-- ================= STATE 2: CASH BOOK DETAIL VIEW ================= -->
                <div id="cashbook_details_view" <?php if ($_SESSION['login_id'] != 1) echo 'style="display: block;"'; else echo 'style="display: none;"'; ?>>
                    <!-- Detail Header Control card -->
                    <div class="details-header-card">
                        <div class="details-header-left">
                            <button type="button" class="rep-btn rep-btn-outline" onclick="backToMembers()" <?php if ($_SESSION['login_id'] != 1) echo 'style="display: none;"'; ?>>
                                <i class="fa fa-arrow-left"></i> Back to Members
                            </button>
                            <div class="details-member-summary">
                                <img id="detail_member_img" src="../img/customer.png" onerror="this.src='../img/customer.png';">
                                <div>
                                    <h3 class="details-member-name" id="detail_member_name">—</h3>
                                    <span class="details-member-group" id="detail_member_group">—</span>
                                </div>
                            </div>
                        </div>
                        <div style="display: flex; gap: 8px;">
                            <button type="button" class="rep-btn" style="background: #3b82f6; color: #fff; border: none;" onclick="openAdminInvoiceModal()">
                                <i class="fa fa-file-text-o"></i> View Fee Invoice
                            </button>
                            <button type="button" class="rep-btn" style="background: #10b981; color: #fff; border: none;" onclick="openAdminReceiptModal()">
                                <i class="fa fa-check-square-o"></i> View Payment Receipt
                            </button>
                            <button type="button" class="rep-btn rep-btn-primary" onclick="printCashBook()">
                                <i class="fa fa-print"></i> Print Cash Book
                            </button>
                        </div>
                    </div>

                    <!-- KPI Badges Overview -->
                    <div class="kpi-row">
                        <div class="kpi-card kpi-op">
                            <span class="kpi-title">Opening Balance</span>
                            <span class="kpi-val" id="kpi_opening_balance" style="color: #0f172a;">₹0.00</span>
                        </div>
                        <div class="kpi-card kpi-deb">
                            <span class="kpi-title">Total Debits (Receivable)</span>
                            <span class="kpi-val" id="kpi_total_debits" style="color: #0f172a;">₹0.00</span>
                        </div>
                        <div class="kpi-card kpi-cred">
                            <span class="kpi-title">Total Credits (Paid)</span>
                            <span class="kpi-val" id="kpi_total_credits" style="color: #0f172a;">₹0.00</span>
                        </div>
                        <div class="kpi-card kpi-bal">
                            <span class="kpi-title">Net Closing Balance</span>
                            <span class="kpi-val" id="kpi_closing_balance" style="color: #10b981;">₹0.00</span>
                        </div>
                    </div>

                    <!-- Chronological Ledger Table -->
                    <div class="rep-table-card">
                        <div class="rep-table-header">
                            <h3 class="rep-table-title"><i class="fa fa-list-alt"></i> Financial Year Ledger Transactions</h3>
                        </div>
                        <div class="rep-table-wrap">
                            <table class="table-custom" id="tbl_ledger_list">
                                <thead>
                                    <tr>
                                        <th style="width: 120px;">Date</th>
                                        <th>Particulars / Description</th>
                                        <th style="text-align: right; width: 150px;">Receivable (Debit) (₹)</th>
                                        <th style="text-align: right; width: 150px;">Payment (Credit) (₹)</th>
                                        <th style="text-align: center; width: 95px;">Receipt</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <!-- Injected via AJAX -->
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- ================= PRINTER LAYOUT (ISOLATED) ================= -->
    <div id="print_layout">
        <div class="print-header">
            <div>
                <h1>YMCA Badminton Club Poovathussery</h1>
                <p>Member Cash Book Ledger Statement</p>
            </div>
            <div style="text-align: right;">
                <p style="font-weight: 700; font-size: 13px;" id="print_year_label">—</p>
                <p>Generated on: <?php echo date('d-M-Y H:i'); ?></p>
            </div>
        </div>

        <div class="print-profile-grid">
            <div class="print-profile-card">
                <h4 style="margin: 0 0 10px 0; font-weight: 700; font-size: 13px; color: #1e293b; border-bottom: 1px solid #cbd5e1; padding-bottom: 4px;">MEMBER DETAILS</h4>
                <table>
                    <tr>
                        <td style="width: 100px; font-weight: 600;">Name:</td>
                        <td id="print_member_name">—</td>
                    </tr>
                    <tr>
                        <td style="font-weight: 600;">Assigned Groups:</td>
                        <td id="print_member_group">—</td>
                    </tr>
                    <tr>
                        <td style="font-weight: 600;">Email:</td>
                        <td id="print_member_email">—</td>
                    </tr>
                    <tr>
                        <td style="font-weight: 600;">Phone:</td>
                        <td id="print_member_phone">—</td>
                    </tr>
                </table>
            </div>
            <div class="print-profile-card">
                <h4 style="margin: 0 0 10px 0; font-weight: 700; font-size: 13px; color: #1e293b; border-bottom: 1px solid #cbd5e1; padding-bottom: 4px;">LEDGER OVERVIEW</h4>
                <table>
                    <tr>
                        <td style="width: 150px; font-weight: 600;">Opening Balance:</td>
                        <td id="print_opening_balance" style="text-align: right; font-weight: 600;">₹0.00</td>
                    </tr>
                    <tr>
                        <td style="font-weight: 600;">Total Debits (Receivables):</td>
                        <td id="print_total_debits" style="text-align: right; color: #ef4444; font-weight: 600;">₹0.00</td>
                    </tr>
                    <tr>
                        <td style="font-weight: 600;">Total Credits (Received):</td>
                        <td id="print_total_credits" style="text-align: right; color: #10b981; font-weight: 600;">₹0.00</td>
                    </tr>
                    <tr>
                        <td style="font-weight: 600; border-top: 1px solid #cbd5e1; padding-top: 6px;">Closing Balance Due:</td>
                        <td id="print_closing_balance" style="text-align: right; font-weight: 800; border-top: 1px solid #cbd5e1; padding-top: 6px;">₹0.00</td>
                    </tr>
                </table>
            </div>
        </div>

        <h4 style="margin: 20px 0 10px 0; font-weight: 700; font-size: 12px; border-bottom: 1.5px solid #475569; padding-bottom: 4px;">CHRONOLOGICAL LEDGER ENTRIES</h4>
        <table class="print-table" id="tbl_print_ledger">
            <thead>
                <tr>
                    <th style="width: 100px;">Date</th>
                    <th>Particulars / Description</th>
                    <th class="print-text-right" style="width: 140px;">Receivable (Debit) (₹)</th>
                    <th class="print-text-right" style="width: 140px;">Payment (Credit) (₹)</th>
                </tr>
            </thead>
            <tbody>
                <!-- Injected via Javascript -->
            </tbody>
        </table>

        <div style="margin-top: 50px; display: flex; justify-content: space-between;">
            <div style="text-align: center; width: 180px;">
                <div style="border-top: 1px solid #000; margin-top: 30px; padding-top: 4px; font-size: 10px; font-weight: 600;">Prepared By</div>
            </div>
            <div style="text-align: center; width: 180px;">
                <div style="border-top: 1px solid #000; margin-top: 30px; padding-top: 4px; font-size: 10px; font-weight: 600;">Member Signature</div>
            </div>
            <div style="text-align: center; width: 180px;">
                <div style="border-top: 1px solid #000; margin-top: 30px; padding-top: 4px; font-size: 10px; font-weight: 600;">Authorized Signatory</div>
            </div>
        </div>
    </div>

    <!-- Script imports -->
    <script src="../js/jquery-3.1.1.min.js"></script>
    <script src="../js/bootstrap.min.js"></script>
    <script src="../js/plugins/metisMenu/jquery.metisMenu.js"></script>
    <script src="../js/plugins/slimscroll/jquery.slimscroll.min.js"></script>
    <script src="../js/inspinia.js"></script>
    <script src="../js/plugins/pace/pace.min.js"></script>
    <script src="../js/loadingoverlay.min.js"></script>
    <script src="../app_pagination/pagination.js"></script>
    <script src="../app_menu/menu.js"></script>

    <script>
        $(document).ready(function() {
            buildFinancialYearDropdown();
            loadMenu();
            <?php if ($_SESSION['login_id'] != 1) { ?>
                viewCashBook(<?php echo $_SESSION['user_id']; ?>);
            <?php } else { ?>
                loadMembersList(1);
            <?php } ?>
        });

        $(document).on('change', '#sel_financial_year', function() {
            const memberId = parseInt($('#hdn_selected_member_id').val());
            if (memberId > 0) {
                viewCashBook(memberId);
            }
        });

        // Generate Financial Year Selector options dynamically (e.g. from 2024-2025 up to current+1)
        function buildFinancialYearDropdown() {
            const today = new Date();
            const currentYear = today.getFullYear();
            const currentMonth = today.getMonth(); // 0-indexed (0=Jan, 11=Dec)
            
            // Indian FY starts in April (Month Index 3)
            let defaultFYStart = currentYear;
            if (currentMonth < 3) {
                defaultFYStart = currentYear - 1;
            }

            let htm = "";
            // Build range from 2024 up to current default year + 1
            for (let y = defaultFYStart + 1; y >= 2024; y--) {
                const nextY = y + 1;
                const selectedAttr = (y === defaultFYStart) ? "selected" : "";
                htm += `<option value="${y}" ${selectedAttr}>${y} - ${nextY}</option>`;
            }
            $('#sel_financial_year').html(htm);
        }

        // Search members
        function searchMembers() {
            loadMembersList(1);
        }

        // Wrapper for pagination (pagination.js calls loadData)
        function loadData(page) {
            loadMembersList(page);
        }

        // Clear filters
        function clearFilters() {
            $('#txt_search').val('');
            buildFinancialYearDropdown();
            loadMembersList(1);
        }

        // Load members list
        function loadMembersList(page) {
            $('#hdn_current_page').val(page);
            console.log("Loading members for page:", page);
            $.ajax({
                type: "POST",
                url: "api/member_cashbook_report.php",
                data: {
                    action: 'load_members',
                    page: page,
                    val: $('#txt_search').val()
                },
                success: function(response) {
                    console.log("API Response:", response);
                    try {
                        const res = JSON.parse(response);
                        console.log("Parsed JSON:", res);
                        const pagination = res[0];
                        const members = res[1];
                        console.log("Members count:", members.length);

                        let htm = "";
                        if (members.length === 0) {
                            htm += `<tr><td colspan="4" style="text-align: center; color: #94a3b8; padding: 30px;">No members found matching your search.</td></tr>`;
                        } else {
                            for (let i = 0; i < members.length; i++) {
                                const m = members[i];
                                const slno = ((page - 1) * 8) + (i + 1);
                                const avatar = m.img && m.img !== '0' ? `../image_upload/members/thumbnails/${m.img}` : '../img/customer.png';
                                const fullName = m.first_name + " " + (m.middle_name ? m.middle_name + " " : "") + m.last_name;
                                const groupNames = m.group_names ? m.group_names : 'No Assigned Group';

                                htm += `<tr>
                                    <td>${slno}</td>
                                    <td>
                                        <div class="member-info-cell">
                                            <img class="member-avatar" src="${avatar}" onerror="this.src='../img/customer.png';">
                                            <div class="member-details-cell">
                                                <span class="member-name">${fullName}</span>
                                                <span class="member-group"><i class="fa fa-tags"></i> ${groupNames}</span>
                                            </div>
                                        </div>
                                    </td>
                                    <td>${m.phone ? m.phone : '<span class="text-muted">N/A</span>'}</td>
                                    <td>
                                        <button type="button" class="rep-btn rep-btn-primary btn-xs" onclick="viewCashBook(${m.id})">
                                            <i class="fa fa-book"></i> View Cash Book
                                        </button>
                                    </td>
                                </tr>`;
                            }
                        }

                        $('#tbl_members_list tbody').html(htm);
                        
                        // Inject pagination
                        const htmpage = paginate(pagination.total_rows, page);
                        $('#members_pagination').html(htmpage);

                    } catch (e) {
                        console.error("Parse error loading members:", e);
                    }
                },
                error: function(xhr, status, error) {
                    console.error('AJAX error status:', status);
                    console.error('AJAX error text:', error);
                    console.error('AJAX response:', xhr.responseText);
                    console.error('AJAX status code:', xhr.status);
                }
            });
        }

        // View Member Cash Book
        function viewCashBook(memberId) {
            $('#hdn_selected_member_id').val(memberId);
            const selectedYear = parseInt($('#sel_financial_year').val());
            const nextYear = selectedYear + 1;

            $.ajax({
                type: "POST",
                url: "api/member_cashbook_report.php",
                data: {
                    action: 'get_member_cashbook',
                    member_id: memberId,
                    year: selectedYear
                },
                beforeSend: function() {
                    $.LoadingOverlay("show");
                },
                success: function(response) {
                    $.LoadingOverlay("hide");
                    try {
                        const res = JSON.parse(response);
                        const info = res.member_info;
                        const summary = res.summary;
                        const trans = res.transactions;

                        // 1. Populate Screen View details
                        const avatar = info.img && info.img !== '0' ? `../image_upload/members/thumbnails/${info.img}` : '../img/customer.png';
                        const fullName = info.first_name + " " + (info.middle_name ? info.middle_name + " " : "") + info.last_name;
                        const groupNames = info.group_names ? info.group_names : 'No Assigned Group';

                        $('#detail_member_img').attr('src', avatar);
                        $('#detail_member_name').text(fullName);
                        $('#detail_member_group').html(`<i class="fa fa-tags"></i> ${groupNames}`);

                        // KPI details
                        $('#kpi_opening_balance').text('₹' + summary.opening_balance.toLocaleString('en-IN', { minimumFractionDigits: 2, maximumFractionDigits: 2 }));
                        $('#kpi_total_debits').text('₹' + summary.total_debit.toLocaleString('en-IN', { minimumFractionDigits: 2, maximumFractionDigits: 2 }));
                        $('#kpi_total_credits').text('₹' + summary.total_credit.toLocaleString('en-IN', { minimumFractionDigits: 2, maximumFractionDigits: 2 }));
                        $('#kpi_closing_balance').text('₹' + summary.closing_balance.toLocaleString('en-IN', { minimumFractionDigits: 2, maximumFractionDigits: 2 }));

                        if (summary.closing_balance > 0) {
                            $('#kpi_closing_balance').css('color', '#ef4444');
                        } else {
                            $('#kpi_closing_balance').css('color', '#10b981');
                        }

                        window.currentCashbookData = res;

                        // Detail Table entries
                        let tableHtm = "";
                        if (trans.length === 0) {
                            tableHtm += `<tr><td colspan="5" style="text-align: center; color: #94a3b8; padding: 30px;">No transactions recorded during this financial year.</td></tr>`;
                        } else {
                            for (let i = 0; i < trans.length; i++) {
                                const t = trans[i];
                                const formattedDate = new Date(t.date).toLocaleDateString('en-GB', { day: '2-digit', month: 'short', year: 'numeric' });
                                const debitVal = t.debit > 0 ? '₹' + t.debit.toLocaleString('en-IN', { minimumFractionDigits: 2, maximumFractionDigits: 2 }) : '—';
                                const creditVal = t.credit > 0 ? '₹' + t.credit.toLocaleString('en-IN', { minimumFractionDigits: 2, maximumFractionDigits: 2 }) : '—';

                                let btnHtml = '';
                                if (t.debit > 0) {
                                    btnHtml = `<button type="button" class="btn btn-xs btn-default" onclick="openSingleRowInvoice(${i})" style="border-radius: 6px; font-weight: 700; font-size: 11px; color: #2563eb; border-color: rgba(37,99,235,0.3);">
                                        <i class="fa fa-file-text-o"></i> Invoice
                                    </button>`;
                                } else if (t.credit > 0) {
                                    btnHtml = `<button type="button" class="btn btn-xs btn-default" onclick="openSingleRowReceipt(${i})" style="border-radius: 6px; font-weight: 700; font-size: 11px; color: #059669; border-color: rgba(5,150,105,0.3);">
                                        <i class="fa fa-check-square-o"></i> Receipt
                                    </button>`;
                                } else {
                                    btnHtml = `—`;
                                }

                                tableHtm += `<tr>
                                    <td>${formattedDate}</td>
                                    <td>${t.particulars}</td>
                                    <td style="text-align: right;" class="${t.debit > 0 ? 'text-debit' : ''}">${debitVal}</td>
                                    <td style="text-align: right;" class="${t.credit > 0 ? 'text-credit' : ''}">${creditVal}</td>
                                    <td style="text-align: center;">${btnHtml}</td>
                                </tr>`;
                            }

                            // Show Opening Balance at the very bottom (last row) for descending order
                            tableHtm += `<tr style="background: #f8fafc; font-style: italic;">
                                <td>01-Apr-${selectedYear}</td>
                                <td>Opening Balance Brought Forward</td>
                                <td style="text-align: right;">${summary.opening_balance >= 0 ? '₹' + summary.opening_balance.toLocaleString('en-IN', { minimumFractionDigits: 2, maximumFractionDigits: 2 }) : '—'}</td>
                                <td style="text-align: right;">${summary.opening_balance < 0 ? '₹' + Math.abs(summary.opening_balance).toLocaleString('en-IN', { minimumFractionDigits: 2, maximumFractionDigits: 2 }) : '—'}</td>
                                <td></td>
                            </tr>`;
                        }
                        $('#tbl_ledger_list tbody').html(tableHtm);

                        // 2. Populate Print layout details
                        $('#print_year_label').text(`FINANCIAL YEAR: ${selectedYear} - ${nextYear}`);
                        $('#print_member_name').text(fullName);
                        $('#print_member_group').text(groupNames);
                        $('#print_member_email').text(info.email ? info.email : 'N/A');
                        $('#print_member_phone').text(info.phone ? info.phone : 'N/A');

                        $('#print_opening_balance').text('₹' + summary.opening_balance.toLocaleString('en-IN', { minimumFractionDigits: 2, maximumFractionDigits: 2 }));
                        $('#print_total_debits').text('₹' + summary.total_debit.toLocaleString('en-IN', { minimumFractionDigits: 2, maximumFractionDigits: 2 }));
                        $('#print_total_credits').text('₹' + summary.total_credit.toLocaleString('en-IN', { minimumFractionDigits: 2, maximumFractionDigits: 2 }));
                        $('#print_closing_balance').text('₹' + summary.closing_balance.toLocaleString('en-IN', { minimumFractionDigits: 2, maximumFractionDigits: 2 }));

                        let printHtm = "";
                        if (trans.length === 0) {
                            printHtm += `<tr><td colspan="4" style="text-align: center; padding: 20px;">No transactions recorded.</td></tr>`;
                        } else {
                            for (let i = 0; i < trans.length; i++) {
                                const t = trans[i];
                                const formattedDate = new Date(t.date).toLocaleDateString('en-GB', { day: '2-digit', month: 'short', year: 'numeric' });
                                const debitVal = t.debit > 0 ? '₹' + t.debit.toLocaleString('en-IN', { minimumFractionDigits: 2, maximumFractionDigits: 2 }) : '—';
                                const creditVal = t.credit > 0 ? '₹' + t.credit.toLocaleString('en-IN', { minimumFractionDigits: 2, maximumFractionDigits: 2 }) : '—';

                                printHtm += `<tr>
                                    <td>${formattedDate}</td>
                                    <td>${t.particulars}</td>
                                    <td class="print-text-right">${debitVal}</td>
                                    <td class="print-text-right">${creditVal}</td>
                                </tr>`;
                            }

                            // Show Opening Balance at the very bottom (last row) for descending order
                            printHtm += `<tr style="font-style: italic; background: #f8fafc;">
                                <td>01-Apr-${selectedYear}</td>
                                <td>Opening Balance Brought Forward</td>
                                <td class="print-text-right">${summary.opening_balance >= 0 ? '₹' + summary.opening_balance.toLocaleString('en-IN', { minimumFractionDigits: 2, maximumFractionDigits: 2 }) : '—'}</td>
                                <td class="print-text-right">${summary.opening_balance < 0 ? '₹' + Math.abs(summary.opening_balance).toLocaleString('en-IN', { minimumFractionDigits: 2, maximumFractionDigits: 2 }) : '—'}</td>
                            </tr>`;
                        }
                        $('#tbl_print_ledger tbody').html(printHtm);

                        // 3. Switch screens
                        $('#members_list_view').fadeOut(200, function() {
                            $('#cashbook_details_view').fadeIn(200);
                        });

                    } catch (e) {
                        console.error("Parse error loading cash book details:", e);
                    }
                },
                error: function(xhr, status, error) {
                    $.LoadingOverlay("hide");
                    console.error('AJAX error: ', status, error);
                }
            });
        }

        // Back to list view
        function backToMembers() {
            $('#cashbook_details_view').fadeOut(200, function() {
                $('#members_list_view').fadeIn(200);
                loadMembersList(parseInt($('#hdn_current_page').val()));
            });
        }

        // Print Cash Book Action
        function printCashBook() {
            window.print();
        }

        // Open SEPARATE Admin Invoice Modal
        function openAdminInvoiceModal() {
            if(!window.currentCashbookData) return;
            const res = window.currentCashbookData;
            const info = res.member_info;
            const summary = res.summary;
            const trans = res.transactions || [];
            const selectedYear = $('#sel_financial_year').val();
            const fullName = info.first_name + " " + (info.middle_name ? info.middle_name + " " : "") + info.last_name;

            $('#arcpt-modal-title').text('OFFICIAL FEE INVOICE');
            $('#arcpt-doc-title-text').text('FEE INVOICE');
            $('#arcpt-no').text('INV-' + selectedYear + '-' + info.id);
            $('#arcpt-member-name').text(fullName);
            $('#arcpt-member-groups').text('Groups: ' + (info.group_names || 'N/A'));
            $('#arcpt-period').text('FY ' + selectedYear + ' - ' + (parseInt(selectedYear)+1));

            $('#arcpt-total-debit').text('₹' + summary.total_debit.toLocaleString('en-IN', { minimumFractionDigits: 2 }));
            $('#arcpt-total-credit').text('₹' + summary.total_credit.toLocaleString('en-IN', { minimumFractionDigits: 2 }));
            $('#arcpt-closing-balance').text('₹' + summary.closing_balance.toLocaleString('en-IN', { minimumFractionDigits: 2 }));

            if(summary.closing_balance <= 0){
                $('#arcpt-stamp-area').html('<span style="display:inline-flex;align-items:center;gap:5px;padding:5px 14px;border-radius:20px;font-size:12px;font-weight:900;background:#d1fae5;color:#047857;border:1px solid #10b981;"><i class="fa fa-check-circle"></i> INVOICE PAID IN FULL</span>');
            } else {
                $('#arcpt-stamp-area').html('<span style="display:inline-flex;align-items:center;gap:5px;padding:5px 14px;border-radius:20px;font-size:12px;font-weight:900;background:#fee2e2;color:#dc2626;border:1px solid #ef4444;"><i class="fa fa-exclamation-circle"></i> INVOICE DUE (₹' + summary.closing_balance.toFixed(2) + ')</span>');
            }

            let tableHtm = '';
            trans.forEach(function(t){
                if(t.debit > 0){
                    const formattedDate = new Date(t.date).toLocaleDateString('en-GB', { day: '2-digit', month: 'short', year: 'numeric' });
                    tableHtm += `<tr>
                        <td style="padding:8px 10px;font-size:12px;border-bottom:1px solid #f1f5f9;">${formattedDate}</td>
                        <td style="padding:8px 10px;font-size:12px;border-bottom:1px solid #f1f5f9;">${t.particulars}</td>
                        <td style="padding:8px 10px;font-size:12px;text-align:right;border-bottom:1px solid #f1f5f9;color:#ef4444;font-weight:600;">₹${t.debit.toLocaleString('en-IN', { minimumFractionDigits: 2 })}</td>
                        <td style="padding:8px 10px;font-size:12px;text-align:right;border-bottom:1px solid #f1f5f9;">—</td>
                    </tr>`;
                }
            });
            if(!tableHtm) tableHtm = '<tr><td colspan="4" style="text-align:center;padding:16px;color:#94a3b8;">No fee debits for this period.</td></tr>';
            $('#arcpt-table-body').html(tableHtm);

            $('#admin-receipt-modal').modal('show');
        }

        // Open Single Row Invoice Modal (Admin)
        function openSingleRowInvoice(idx) {
            if(!window.currentCashbookData || !window.currentCashbookData.transactions[idx]) return;
            const res = window.currentCashbookData;
            const info = res.member_info;
            const t = res.transactions[idx];
            const selectedYear = $('#sel_financial_year').val();
            const fullName = info.first_name + " " + (info.middle_name ? info.middle_name + " " : "") + info.last_name;
            const formattedDate = new Date(t.date).toLocaleDateString('en-GB', { day: '2-digit', month: 'short', year: 'numeric' });

            $('#arcpt-modal-title').text('FEE INVOICE ITEM');
            $('#arcpt-doc-title-text').text('FEE INVOICE');
            $('#arcpt-no').text('INV-' + t.date.replace(/-/g,'') + '-' + (t.id || idx));
            $('#arcpt-member-name').text(fullName);
            $('#arcpt-member-groups').text('Groups: ' + (info.group_names || 'N/A'));
            $('#arcpt-period').text(formattedDate);

            const deb = parseFloat(t.debit || 0);
            const pendingBal = summary ? summary.closing_balance : deb;

            $('#arcpt-total-debit').text('₹' + deb.toFixed(2));
            $('#arcpt-total-credit').text('₹0.00');
            $('#arcpt-closing-balance').text('₹' + pendingBal.toLocaleString('en-IN', { minimumFractionDigits: 2 }));

            $('#arcpt-stamp-area').html('<span style="display:inline-flex;align-items:center;gap:5px;padding:5px 14px;border-radius:20px;font-size:12px;font-weight:900;background:#fee2e2;color:#dc2626;border:1px solid #ef4444;"><i class="fa fa-file-text-o"></i> FEE CHARGE BILLEDS</span>');

            let tableHtm = `<tr>
                <td style="padding:8px 10px;font-size:12px;border-bottom:1px solid #f1f5f9;">${formattedDate}</td>
                <td style="padding:8px 10px;font-size:12px;border-bottom:1px solid #f1f5f9;">${t.particulars}</td>
                <td style="padding:8px 10px;font-size:12px;text-align:right;border-bottom:1px solid #f1f5f9;color:#ef4444;font-weight:600;">₹${deb.toFixed(2)}</td>
                <td style="padding:8px 10px;font-size:12px;text-align:right;border-bottom:1px solid #f1f5f9;">—</td>
            </tr>`;
            $('#arcpt-table-body').html(tableHtm);

            $('#admin-receipt-modal').modal('show');
        }

        // Open SEPARATE Admin Payment Receipt Modal
        function openAdminReceiptModal() {
            if(!window.currentCashbookData) return;
            const res = window.currentCashbookData;
            const info = res.member_info;
            const summary = res.summary;
            const trans = res.transactions || [];
            const selectedYear = $('#sel_financial_year').val();
            const fullName = info.first_name + " " + (info.middle_name ? info.middle_name + " " : "") + info.last_name;

            $('#arcpt-modal-title').text('OFFICIAL PAYMENT RECEIPT');
            $('#arcpt-doc-title-text').text('PAYMENT RECEIPT');
            $('#arcpt-no').text('RCPT-' + selectedYear + '-' + info.id);
            $('#arcpt-member-name').text(fullName);
            $('#arcpt-member-groups').text('Groups: ' + (info.group_names || 'N/A'));
            $('#arcpt-period').text('FY ' + selectedYear + ' - ' + (parseInt(selectedYear)+1));

            $('#arcpt-total-debit').text('₹' + summary.total_debit.toLocaleString('en-IN', { minimumFractionDigits: 2 }));
            $('#arcpt-total-credit').text('₹' + summary.total_credit.toLocaleString('en-IN', { minimumFractionDigits: 2 }));
            $('#arcpt-closing-balance').text('₹' + summary.closing_balance.toLocaleString('en-IN', { minimumFractionDigits: 2 }));

            if(summary.total_credit > 0){
                $('#arcpt-stamp-area').html('<span style="display:inline-flex;align-items:center;gap:5px;padding:5px 14px;border-radius:20px;font-size:12px;font-weight:900;background:#d1fae5;color:#047857;border:1px solid #10b981;"><i class="fa fa-check-circle"></i> PAYMENT RECEIVED & VERIFIED</span>');
            } else {
                $('#arcpt-stamp-area').html('<span style="display:inline-flex;align-items:center;gap:5px;padding:5px 14px;border-radius:20px;font-size:12px;font-weight:900;background:#f1f5f9;color:#64748b;border:1px solid #cbd5e1;"><i class="fa fa-clock-o"></i> NO PAYMENTS RECORDED YET</span>');
            }

            let tableHtm = '';
            trans.forEach(function(t){
                if(t.credit > 0){
                    const formattedDate = new Date(t.date).toLocaleDateString('en-GB', { day: '2-digit', month: 'short', year: 'numeric' });
                    tableHtm += `<tr>
                        <td style="padding:8px 10px;font-size:12px;border-bottom:1px solid #f1f5f9;">${formattedDate}</td>
                        <td style="padding:8px 10px;font-size:12px;border-bottom:1px solid #f1f5f9;"><i class="fa fa-check-circle" style="color:#10b981;"></i> ${t.particulars}</td>
                        <td style="padding:8px 10px;font-size:12px;text-align:right;border-bottom:1px solid #f1f5f9;">—</td>
                        <td style="padding:8px 10px;font-size:12px;text-align:right;border-bottom:1px solid #f1f5f9;color:#10b981;font-weight:600;">₹${t.credit.toLocaleString('en-IN', { minimumFractionDigits: 2 })}</td>
                    </tr>`;
                }
            });
            if(!tableHtm) tableHtm = '<tr><td colspan="4" style="text-align:center;padding:16px;color:#94a3b8;">No payment credits recorded.</td></tr>';
            $('#arcpt-table-body').html(tableHtm);

            $('#admin-receipt-modal').modal('show');
        }

        // Open Single Row Receipt Modal (Admin)
        function openSingleRowReceipt(idx) {
            if(!window.currentCashbookData || !window.currentCashbookData.transactions[idx]) return;
            const res = window.currentCashbookData;
            const info = res.member_info;
            const t = res.transactions[idx];
            const selectedYear = $('#sel_financial_year').val();
            const fullName = info.first_name + " " + (info.middle_name ? info.middle_name + " " : "") + info.last_name;
            const formattedDate = new Date(t.date).toLocaleDateString('en-GB', { day: '2-digit', month: 'short', year: 'numeric' });

            $('#arcpt-no').text('RCPT-' + t.date.replace(/-/g,'') + '-' + (t.id || idx));
            $('#arcpt-member-name').text(fullName);
            $('#arcpt-member-groups').text('Groups: ' + (info.group_names || 'N/A'));
            $('#arcpt-period').text(formattedDate);

            const deb = parseFloat(t.debit || 0);
            const cred = parseFloat(t.credit || 0);
            const isCredit = cred > 0;

            $('#arcpt-total-debit').text('₹' + deb.toFixed(2));
            $('#arcpt-total-credit').text('₹' + cred.toFixed(2));
            $('#arcpt-closing-balance').text('₹' + (deb > 0 ? deb.toFixed(2) : '0.00'));

            if(isCredit){
                $('#arcpt-stamp-area').html('<span style="display:inline-flex;align-items:center;gap:5px;padding:5px 14px;border-radius:20px;font-size:12px;font-weight:900;background:#d1fae5;color:#047857;border:1px solid #10b981;"><i class="fa fa-check-circle"></i> PAYMENT RECEIVED</span>');
            } else {
                $('#arcpt-stamp-area').html('<span style="display:inline-flex;align-items:center;gap:5px;padding:5px 14px;border-radius:20px;font-size:12px;font-weight:900;background:#fee2e2;color:#dc2626;border:1px solid #ef4444;"><i class="fa fa-file-text-o"></i> FEE CHARGE / DEBIT</span>');
            }

            let tableHtm = `<tr>
                <td style="padding:8px 10px;font-size:12px;border-bottom:1px solid #f1f5f9;">${formattedDate}</td>
                <td style="padding:8px 10px;font-size:12px;border-bottom:1px solid #f1f5f9;">${t.particulars}</td>
                <td style="padding:8px 10px;font-size:12px;text-align:right;border-bottom:1px solid #f1f5f9;color:#ef4444;font-weight:600;">${deb > 0 ? '₹' + deb.toFixed(2) : '—'}</td>
                <td style="padding:8px 10px;font-size:12px;text-align:right;border-bottom:1px solid #f1f5f9;color:#10b981;font-weight:600;">${cred > 0 ? '₹' + cred.toFixed(2) : '—'}</td>
            </tr>`;
            $('#arcpt-table-body').html(tableHtm);

            $('#admin-receipt-modal').modal('show');
        }

        function printAdminReceipt() {
            const printContents = document.getElementById('admin-receipt-print-area').innerHTML;
            const printWindow = window.open('', '', 'height=700,width=850');
            printWindow.document.write('<html><head><title>YMCA Official Fee Receipt</title>');
            printWindow.document.write('<link href="../css/bootstrap.min.css" rel="stylesheet">');
            printWindow.document.write('<link href="../font-awesome/css/font-awesome.css" rel="stylesheet">');
            printWindow.document.write('<style>body{font-family:"Inter",sans-serif;padding:25px;}</style>');
            printWindow.document.write('</head><body>');
            printWindow.document.write(printContents);
            printWindow.document.write('</body></html>');
            printWindow.document.close();
            printWindow.focus();
            setTimeout(function(){ printWindow.print(); printWindow.close(); }, 350);
        }
    </script>

    <!-- Admin Official Invoice & Receipt Modal Container -->
    <div id="admin-receipt-modal" class="modal fade" tabindex="-1" role="dialog" style="z-index: 2050;">
        <div class="modal-dialog modal-lg" role="document" style="max-width: 650px;">
            <div class="modal-content" style="border-radius: 18px; overflow: hidden;">
                <div class="modal-header" style="background: #f8fafc; border-bottom: 1px solid #e2e8f0; padding: 14px 20px;">
                    <button type="button" class="close" data-dismiss="modal">&times;</button>
                    <h4 class="modal-title" style="font-weight: 800; color: #1e293b;"><i class="fa fa-file-text-o" style="color: #3b82f6;"></i> <span id="arcpt-modal-title">Official Fee Receipt / Invoice</span></h4>
                </div>
                <div class="modal-body" style="padding: 0;">
                    <div id="admin-receipt-print-area" style="padding: 24px 20px; font-family: 'Inter', sans-serif;">
                        
                        <!-- Header -->
                        <div style="display: flex; justify-content: space-between; align-items: center; border-bottom: 2px solid #e2e8f0; padding-bottom: 16px; margin-bottom: 16px;">
                            <div style="display: flex; align-items: center; gap: 12px;">
                                <div style="width: 44px; height: 44px; border-radius: 12px; background: linear-gradient(135deg, #3b82f6, #6366f1); color: #fff; display: flex; align-items: center; justify-content: center; font-size: 22px; font-weight: 900;">Y</div>
                                <div>
                                    <h2 style="font-size: 16px; font-weight: 900; color: #0f172a; margin: 0;">YMCA BCP</h2>
                                    <div style="font-size: 11.5px; font-weight: 700; color: #2563eb; margin: 1px 0 2px 0;">Poovathussery</div>
                                    <p style="font-size: 11px; color: #64748b; margin: 2px 0 0;">Official Member Fee Statement & Document</p>
                                </div>
                            </div>
                            <div style="text-align: right;">
                                <h3 style="font-size: 13px; font-weight: 900; color: #3b82f6; text-transform: uppercase; margin: 0;" id="arcpt-doc-title-text">FEE RECEIPT</h3>
                                <div style="font-size: 11px; color: #64748b; margin-top: 2px;" id="arcpt-no">INV-2026-0001</div>
                            </div>
                        </div>

                        <!-- Info Grid -->
                        <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 12px; background: #f8fafc; border: 1px solid #e2e8f0; border-radius: 12px; padding: 12px 14px; margin-bottom: 16px;">
                            <div>
                                <div style="font-size: 10px; font-weight: 700; color: #64748b; text-transform: uppercase;">Member Name</div>
                                <div style="font-size: 13px; font-weight: 700; color: #0f172a; margin-top: 2px;" id="arcpt-member-name">—</div>
                                <div style="font-size: 11px; color: #64748b; margin-top: 2px;" id="arcpt-member-groups">—</div>
                            </div>
                            <div>
                                <div style="font-size: 10px; font-weight: 700; color: #64748b; text-transform: uppercase;">Financial Period</div>
                                <div style="font-size: 13px; font-weight: 700; color: #0f172a; margin-top: 2px;" id="arcpt-period">—</div>
                                <div style="font-size: 11px; color: #64748b; margin-top: 2px;" id="arcpt-date">Generated: <?php echo date('d-M-Y'); ?></div>
                            </div>
                        </div>

                        <!-- Items Table -->
                        <table style="width: 100%; border-collapse: collapse; margin-bottom: 16px;">
                            <thead>
                                <tr style="background: #f1f5f9; border-bottom: 1.5px solid #cbd5e1;">
                                    <th style="padding: 8px 10px; font-size: 11px; font-weight: 800; color: #475569; text-align: left; text-transform: uppercase;">Date</th>
                                    <th style="padding: 8px 10px; font-size: 11px; font-weight: 800; color: #475569; text-align: left; text-transform: uppercase;">Particulars</th>
                                    <th style="padding: 8px 10px; font-size: 11px; font-weight: 800; color: #ef4444; text-align: right; text-transform: uppercase;">Receivable (₹)</th>
                                    <th style="padding: 8px 10px; font-size: 11px; font-weight: 800; color: #10b981; text-align: right; text-transform: uppercase;">Received (₹)</th>
                                </tr>
                            </thead>
                            <tbody id="arcpt-table-body">
                                <!-- Items injected via JS -->
                            </tbody>
                        </table>

                        <!-- Summary Box -->
                        <div style="background: #f8fafc; border-radius: 12px; padding: 12px 14px; margin-bottom: 16px; border: 1px solid #e2e8f0;">
                            <div style="display: flex; justify-content: space-between; font-size: 13px; font-weight: 600; color: #475569;">
                                <span>Total Billed (Debits):</span>
                                <span id="arcpt-total-debit">₹0.00</span>
                            </div>
                            <div style="display: flex; justify-content: space-between; font-size: 13px; font-weight: 600; color: #475569; margin-top: 4px;">
                                <span>Total Paid (Credits):</span>
                                <span id="arcpt-total-credit" style="color: #10b981;">₹0.00</span>
                            </div>
                            <div style="display: flex; justify-content: space-between; font-size: 15px; font-weight: 900; color: #0f172a; border-top: 1.5px solid #e2e8f0; padding-top: 6px; margin-top: 6px;">
                                <span>Net Closing Balance Due:</span>
                                <span id="arcpt-closing-balance">₹0.00</span>
                            </div>
                        </div>

                        <!-- Stamp & Signatures -->
                        <div style="display: flex; align-items: center; justify-content: space-between; border-top: 1px dashed #cbd5e1; padding-top: 14px;">
                            <div id="arcpt-stamp-area">
                                <span style="display: inline-flex; align-items: center; gap: 5px; padding: 5px 14px; border-radius: 20px; font-size: 12px; font-weight: 900; background: #d1fae5; color: #047857; border: 1px solid #10b981;"><i class="fa fa-check-circle"></i> VERIFIED RECEIPT</span>
                            </div>
                            <div style="text-align: right;">
                                <div style="font-size: 11px; font-weight: 700; color: #64748b;">YMCA BCP Accounts & Management</div>
                                <div style="font-size: 10px; color: #94a3b8; margin-top: 2px;">Authorized Digital Signature</div>
                            </div>
                        </div>

                    </div>
                </div>
                <div class="modal-footer" style="background: #f8fafc; border-top: 1px solid #e2e8f0; padding: 12px 20px;">
                    <button type="button" class="btn btn-default" data-dismiss="modal"><i class="fa fa-times"></i> Close</button>
                    <button type="button" class="btn btn-primary" onclick="printAdminReceipt()"><i class="fa fa-print"></i> Print / Save PDF Receipt</button>
                </div>
            </div>
        </div>
    </div>
</body>
</html>
