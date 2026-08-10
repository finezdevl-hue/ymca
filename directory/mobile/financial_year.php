<?php
session_start();
include '../../app_common/db_connect.php';
include '../../app_common/auth_helper.php';

if (empty($_SESSION['login_id'])) {
    header("Location: ../../index.php");
    exit();
}

$login_id = (int)$_SESSION['login_id'];
$is_admin = isSuperAdmin($login_id) || isGroupAdmin($login_id) || isExecutiveMember($login_id);
if (!$is_admin) {
    header("Location: home.php");
    exit();
}

$allowed_groups = getUserAllowedGroupIds($login_id);
$primary_role = getUserPrimaryRoleName($login_id);

$active_tab = 'reports';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
    <title>Financial Year Report - YMCA Mobile</title>
    
    <link href="../../css/bootstrap.min.css" rel="stylesheet">
    <link href="../../font-awesome/css/font-awesome.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800;900&display=swap" rel="stylesheet">
    <link href="mobile_layout.css" rel="stylesheet">

    <style>
        :root {
            --fy-amber: #d97706;
            --fy-grad: linear-gradient(135deg, #b45309 0%, #f59e0b 50%, #d97706 100%);
        }

        body.mob-body {
            font-family: 'Plus Jakarta Sans', -apple-system, sans-serif !important;
            background: #f8fafc !important;
        }

        .fy-hero {
            background: var(--fy-grad);
            border-radius: 22px;
            padding: 22px 20px;
            color: #ffffff;
            margin-bottom: 16px;
            box-shadow: 0 12px 28px rgba(217, 119, 6, 0.22);
            position: relative;
            overflow: hidden;
        }
        .fy-hero::after {
            content: '';
            position: absolute;
            top: -40%; right: -15%;
            width: 180px; height: 180px;
            background: radial-gradient(circle, rgba(255,255,255,0.2) 0%, rgba(255,255,255,0) 70%);
            pointer-events: none;
        }
        .fy-hero h2 { margin: 0 0 4px 0; font-weight: 900; font-size: 21px; letter-spacing: -0.5px; }
        .fy-hero p { margin: 0; font-size: 12.5px; opacity: 0.92; font-weight: 500; }

        .fy-filter-box {
            background: #ffffff;
            border-radius: 20px;
            border: 1px solid #e2e8f0;
            padding: 16px;
            margin-bottom: 14px;
            box-shadow: 0 4px 14px rgba(0,0,0,0.03);
        }
        .fy-field label {
            font-size: 11px; font-weight: 800; color: #64748b;
            text-transform: uppercase; letter-spacing: 0.5px; margin-bottom: 6px; display: block;
        }
        .fy-field select {
            width: 100%; padding: 12px 14px; border-radius: 14px;
            border: 1.5px solid #cbd5e1; background: #f8fafc; font-size: 13.5px;
            font-weight: 700; color: #0f172a; outline: none; transition: all 0.2s;
        }
        .fy-field select:focus { border-color: #f59e0b; background: #ffffff; box-shadow: 0 0 0 3px rgba(245,158,11,0.15); }

        /* KPI Cards Grid */
        .kpi-grid-mobile {
            display: grid;
            grid-template-columns: repeat(2, 1fr);
            gap: 12px;
            margin-bottom: 16px;
        }
        .kpi-box-mob {
            background: #ffffff;
            border-radius: 18px;
            border: 1px solid #e2e8f0;
            padding: 14px 14px;
            box-shadow: 0 4px 12px rgba(0,0,0,0.025);
            position: relative;
            overflow: hidden;
            display: flex;
            flex-direction: column;
            justify-content: space-between;
        }
        .kpi-box-mob::before {
            content: '';
            position: absolute; left: 0; top: 0; bottom: 0; width: 4.5px;
        }
        .kpi-op::before { background: #f59e0b; }
        .kpi-inc::before { background: #10b981; }
        .kpi-oth::before { background: #3b82f6; }
        .kpi-wal::before { background: #8b5cf6; }
        .kpi-pend::before { background: #f43f5e; }
        .kpi-exp::before { background: #ef4444; }
        .kpi-cl::before { background: #0f172a; }
        .kpi-ast::before { background: #6366f1; }

        .kpi-lbl-mob {
            font-size: 10.5px;
            font-weight: 800;
            color: #64748b;
            text-transform: uppercase;
            letter-spacing: 0.4px;
            display: flex;
            align-items: center;
            gap: 5px;
        }
        .kpi-val-mob {
            font-size: 17px;
            font-weight: 900;
            color: #0f172a;
            margin-top: 6px;
            line-height: 1.15;
        }

        .mob-section-card {
            background: #ffffff;
            border-radius: 20px;
            border: 1px solid #e2e8f0;
            padding: 18px;
            margin-bottom: 16px;
            box-shadow: 0 4px 14px rgba(0,0,0,0.03);
        }
        .mob-card-title {
            font-size: 14.5px;
            font-weight: 900;
            color: #0f172a;
            margin: 0 0 14px 0;
            display: flex;
            align-items: center;
            gap: 8px;
        }

        .summary-row {
            display: flex;
            align-items: center;
            justify-content: space-between;
            padding: 11px 0;
            border-bottom: 1px solid #f1f5f9;
            font-size: 13.5px;
        }
        .summary-row:last-child { border-bottom: none; }
        .summary-row .lbl { color: #64748b; font-weight: 600; }
        .summary-row .val { font-weight: 800; color: #0f172a; }

        /* Global Totals Strip */
        .global-totals-strip {
            background: linear-gradient(135deg, #0f172a 0%, #1e293b 100%);
            border-radius: 18px;
            padding: 14px 16px;
            color: #ffffff;
            margin-bottom: 16px;
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 12px;
            box-shadow: 0 6px 18px rgba(15,23,42,0.12);
        }
        .global-item { text-align: center; flex: 1; }
        .global-val { font-size: 16px; font-weight: 900; }
        .global-lbl { font-size: 10px; font-weight: 700; opacity: 0.8; text-transform: uppercase; letter-spacing: 0.5px; margin-top: 2px; }
    </style>
</head>
<body class="mob-body">

    <!-- Header -->
    <header class="mob-header">
        <div class="mob-header-brand">
            <a href="reports.php" style="color:#ffffff; margin-right:10px; font-size:18px;"><i class="fa fa-arrow-left"></i></a>
            <div class="mob-header-title">
                Financial Year <span>Report</span>
            </div>
        </div>
        <div class="mob-header-actions">
            <a href="../../app_login_manager/logout.php" class="mob-header-btn" title="Logout"><i class="fa fa-sign-out"></i></a>
        </div>
    </header>

    <div class="mob-page">

        <!-- Hero Card -->
        <div class="fy-hero">
            <h2>Financial Year Summary</h2>
            <p id="fy_label_p">Annual financial statement (<span id="fy_yr_txt">FY Loading...</span>)</p>
        </div>

        <!-- Controls / FY Selector -->
        <div class="fy-filter-box">
            <div class="fy-field">
                <label><i class="fa fa-calendar" style="color:#f59e0b;"></i> Select Financial Year</label>
                <select id="fy_year_select" onchange="onYearChange()"></select>
            </div>
        </div>

        <!-- Global All-Time Totals -->
        <div class="global-totals-strip">
            <div class="global-item">
                <div class="global-val" id="glob_pending_val" style="color:#fecaca;">₹0.00</div>
                <div class="global-lbl">All-Time Pending Dues</div>
            </div>
            <div style="width:1px; height:28px; background:rgba(255,255,255,0.15);"></div>
            <div class="global-item">
                <div class="global-val" id="glob_wallet_val" style="color:#a7f3d0;">₹0.00</div>
                <div class="global-lbl">All-Time Wallet Balance</div>
            </div>
        </div>

        <!-- 8 KPI Summary Cards -->
        <div class="kpi-grid-mobile">
            <div class="kpi-box-mob kpi-op">
                <div class="kpi-lbl-mob"><i class="fa fa-hourglass-start" style="color:#f59e0b;"></i> OPENING BAL</div>
                <div class="kpi-val-mob" id="kpi_opening_balance">₹0.00</div>
            </div>
            <div class="kpi-box-mob kpi-inc">
                <div class="kpi-lbl-mob"><i class="fa fa-arrow-circle-down" style="color:#10b981;"></i> MEMBER FEES</div>
                <div class="kpi-val-mob" id="kpi_member_fees_received" style="color:#059669;">₹0.00</div>
            </div>
            <div class="kpi-box-mob kpi-oth">
                <div class="kpi-lbl-mob"><i class="fa fa-download" style="color:#3b82f6;"></i> OTHER INCOME</div>
                <div class="kpi-val-mob" id="kpi_other_income_received" style="color:#2563eb;">₹0.00</div>
            </div>
            <div class="kpi-box-mob kpi-wal">
                <div class="kpi-lbl-mob"><i class="fa fa-google-wallet" style="color:#8b5cf6;"></i> WALLET NET</div>
                <div class="kpi-val-mob" id="kpi_wallet_net_credits" style="color:#7c3aed;">₹0.00</div>
            </div>
            <div class="kpi-box-mob kpi-pend">
                <div class="kpi-lbl-mob"><i class="fa fa-exclamation-circle" style="color:#f43f5e;"></i> PENDING DUES</div>
                <div class="kpi-val-mob" id="kpi_pending_payment" style="color:#dc2626;">₹0.00</div>
            </div>
            <div class="kpi-box-mob kpi-exp">
                <div class="kpi-lbl-mob"><i class="fa fa-arrow-circle-up" style="color:#ef4444;"></i> TOTAL EXPENSES</div>
                <div class="kpi-val-mob" id="kpi_total_expenses" style="color:#b91c1c;">₹0.00</div>
            </div>
            <div class="kpi-box-mob kpi-cl">
                <div class="kpi-lbl-mob"><i class="fa fa-calculator" style="color:#0f172a;"></i> CLOSING BAL</div>
                <div class="kpi-val-mob" id="kpi_closing_balance">₹0.00</div>
            </div>
            <div class="kpi-box-mob kpi-ast">
                <div class="kpi-lbl-mob"><i class="fa fa-line-chart" style="color:#6366f1;"></i> TOTAL ASSETS</div>
                <div class="kpi-val-mob" id="kpi_total_assets" style="color:#4f46e5;">₹0.00</div>
            </div>
        </div>

        <!-- Cash & Bank Position Card -->
        <div class="mob-section-card">
            <h3 class="mob-card-title"><i class="fa fa-university" style="color:#3b82f6;"></i> Cash & Bank Position</h3>
            <div class="summary-row">
                <span class="lbl">Cash in Hand</span>
                <span class="val" id="val_cash_in_hand" style="color:#059669;">₹0.00</span>
            </div>
            <div class="summary-row">
                <span class="lbl">Bank Deposit / Savings</span>
                <span class="val" id="val_bank_deposit">₹0.00</span>
            </div>
            <div class="summary-row">
                <span class="lbl">Fixed Deposits & Interest</span>
                <span class="val" id="val_fd_prev">₹0.00</span>
            </div>
        </div>

        <!-- Fees Category Breakdown Card -->
        <div class="mob-section-card">
            <h3 class="mob-card-title"><i class="fa fa-list-alt" style="color:#10b981;"></i> Fee Category Collection</h3>
            <div class="summary-row">
                <span class="lbl">Monthly Membership Fees</span>
                <span class="val" id="val_monthly_fees" style="color:#059669;">₹0.00</span>
            </div>
            <div class="summary-row">
                <span class="lbl">Admission / New Member Fees</span>
                <span class="val" id="val_membership_fees">₹0.00</span>
            </div>
            <div class="summary-row">
                <span class="lbl">Other Member Fees</span>
                <span class="val" id="val_other_member_fees">₹0.00</span>
            </div>
        </div>

    </div>

    <!-- Mobile Bottom Navigation (4/5 Tabs) -->
    <?php include 'mobile_bottom_nav.php'; ?>

    <script src="../../js/jquery-3.1.1.min.js"></script>
    <script src="../../js/bootstrap.min.js"></script>

    <script>
    let closingYears = [];

    $(document).ready(function() {
        loadGlobalTotals();
        loadClosingYears();
    });

    const fmtINR = (val) => {
        let v = parseFloat(val || 0);
        return new Intl.NumberFormat('en-IN', {
            style: 'currency',
            currency: 'INR',
            minimumFractionDigits: 2
        }).format(v);
    };

    function loadGlobalTotals() {
        $.post('../api/financial_year_report.php', { action: 'load_global_totals' }, function(res) {
            try {
                let d = typeof res === 'string' ? JSON.parse(res) : res;
                $('#glob_pending_val').text(fmtINR(d.total_pending));
                $('#glob_wallet_val').text(fmtINR(d.wallet_balance));
            } catch(e) {
                console.error(e);
            }
        });
    }

    function loadClosingYears() {
        $.post('../api/financial_year_report.php', { action: 'load_closing_years' }, function(res) {
            try {
                let parsed = typeof res === 'string' ? JSON.parse(res) : res;
                closingYears = Array.isArray(parsed[0]) ? parsed[0] : (Array.isArray(parsed) ? parsed : []);
                
                let htm = '';
                closingYears.forEach(function(y) {
                    htm += `<option value="${y.id}">FY ${y.from_year} - ${y.to_year}</option>`;
                });
                
                if (closingYears.length === 0) {
                    let curYear = new Date().getFullYear();
                    htm = `<option value="1">FY ${curYear} - ${curYear + 1}</option>`;
                }
                
                $('#fy_year_select').html(htm);
                let firstYearId = closingYears.length > 0 ? closingYears[0].id : 1;
                $('#fy_year_select').val(firstYearId);
                loadFinancialDashboard(firstYearId);
            } catch(e) {
                console.error(e);
            }
        });
    }

    function onYearChange() {
        let selectedId = $('#fy_year_select').val();
        if (selectedId) {
            loadFinancialDashboard(selectedId);
        }
    }

    function loadFinancialDashboard(yearId) {
        if (typeof load_overlay === 'function') load_overlay();

        $.post('../api/financial_year_report.php', { action: 'load_financial_dashboard', year_id: yearId }, function(res) {
            if (typeof close_overlay === 'function') close_overlay();
            try {
                let data = typeof res === 'string' ? JSON.parse(res) : res;
                
                $('#fy_yr_txt').text('FY ' + (data.year_label || 'Summary'));
                $('#kpi_opening_balance').text(fmtINR(data.opening_balance));
                $('#kpi_member_fees_received').text(fmtINR(data.total_member_fees_received));
                $('#kpi_other_income_received').text(fmtINR(data.other_rec_amount));
                $('#kpi_wallet_net_credits').text(fmtINR(data.total_wallet_balance));
                $('#kpi_pending_payment').text(fmtINR(data.total_pending_receivables));
                $('#kpi_total_expenses').text(fmtINR(data.other_payments_paid));
                $('#kpi_closing_balance').text(fmtINR(data.closing_balance));
                $('#kpi_total_assets').text(fmtINR(data.total_assets));

                $('#val_cash_in_hand').text(fmtINR(data.cash_in_hand));
                $('#val_bank_deposit').text(fmtINR(data.total_bank_deposit_year));
                $('#val_fd_prev').text(fmtINR(data.total_fd_prev));

                $('#val_monthly_fees').text(fmtINR(data.monthly_fees));
                $('#val_membership_fees').text(fmtINR(data.membership_fees));
                $('#val_other_member_fees').text(fmtINR(data.other_member_fees));
            } catch(e) {
                console.error(e);
            }
        }).fail(function() {
            if (typeof close_overlay === 'function') close_overlay();
        });
    }
    </script>
</body>
</html>
