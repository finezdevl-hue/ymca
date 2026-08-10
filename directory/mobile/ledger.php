<?php
session_start();
session_write_close();

if (empty($_SESSION['login_id']) || $_SESSION['login_id'] == 1) {
    header("Location: ../../index.php");
    exit();
}

$page_title = 'Ledger';
$active_tab = 'ledger';
$member_id  = (int)($_SESSION['user_id'] ?? 0);
$member_name = $_SESSION['name'] ?? 'Member';
$email       = $_SESSION['email'] ?? '';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
    <meta name="description" content="Member Ledger & Invoice Receipts - YMCA Member Portal">
    <title>YMCA | Ledger & Receipts</title>
    <link rel="shortcut icon" href="../../favicon.ico" type="image/x-icon">
    <link href="../../font-awesome/css/font-awesome.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800;900&display=swap" rel="stylesheet">
    <link href="../../css/plugins/sweetalert/sweetalert.css" rel="stylesheet">
    <link href="mobile_layout.css" rel="stylesheet">

    <style>
        /* KPI row */
        .mob-kpi-row { display: grid; grid-template-columns: 1fr 1fr; gap: 10px; }

        .mob-kpi-card {
            background: #fff;
            border: 1px solid #e2e8f0;
            border-radius: 14px;
            padding: 14px 12px;
            position: relative;
            overflow: hidden;
            box-shadow: 0 2px 8px rgba(0,0,0,0.03);
        }
        .mob-kpi-card::before {
            content:'';
            position:absolute; left:0; top:0; bottom:0; width:4px;
        }
        .mob-kpi-card.kpi-deb::before { background:#0f172a; }
        .mob-kpi-card.kpi-cred::before { background:#0f172a; }
        .mob-kpi-card.kpi-op::before { background:#0f172a; }
        .mob-kpi-card.kpi-bal-zero::before { background:#10b981; }
        .mob-kpi-card.kpi-bal-due::before { background:#ef4444; }

        .mob-kpi-title { font-size:11px; font-weight:700; color:#64748b; text-transform:uppercase; letter-spacing:.5px; }
        .mob-kpi-val   { font-size:20px; font-weight:900; color:#0f172a; margin-top:4px; letter-spacing:-0.5px; }
        .mob-kpi-val.cred { color:#0f172a; }
        .mob-kpi-val.deb  { color:#0f172a; }
        .mob-kpi-val.op   { color:#0f172a; }
        .mob-kpi-val.bal  { color:#10b981; }

        /* Filter row */
        .mob-filter-row { display:flex; gap:8px; align-items:flex-end; flex-wrap:wrap; }
        .mob-filter-row .mob-input { min-width:0; }

        /* ---- Month Box Cards ---- */
        .month-boxes-container {
            display: flex;
            flex-direction: column;
            gap: 14px;
        }

        .month-box {
            background: #ffffff;
            border-radius: 16px;
            border: 1.5px solid #e2e8f0;
            box-shadow: 0 4px 14px rgba(0, 0, 0, 0.04);
            overflow: hidden;
            transition: all 0.25s ease;
        }

        .month-box.state-paid {
            border-color: rgba(16, 185, 129, 0.35);
        }

        .month-box.state-pending {
            border-color: rgba(239, 68, 68, 0.35);
            background: linear-gradient(180deg, #ffffff 0%, #fffdfd 100%);
        }

        .month-box.state-neutral {
            border-color: #e2e8f0;
        }

        .month-box-header {
            padding: 14px 16px;
            background: #f8faff;
            border-bottom: 1px solid #e2e8f0;
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 10px;
        }

        .state-paid .month-box-header {
            background: rgba(16, 185, 129, 0.06);
            border-bottom-color: rgba(16, 185, 129, 0.18);
        }

        .state-pending .month-box-header {
            background: rgba(239, 68, 68, 0.06);
            border-bottom-color: rgba(239, 68, 68, 0.18);
        }

        .month-title-wrap {
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .month-icon-circle {
            width: 38px;
            height: 38px;
            border-radius: 10px;
            background: linear-gradient(135deg, #4f46e5, #3b82f6);
            color: #ffffff;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 15px;
            flex-shrink: 0;
        }

        .state-paid .month-icon-circle {
            background: linear-gradient(135deg, #10b981, #059669);
        }

        .state-pending .month-icon-circle {
            background: linear-gradient(135deg, #ef4444, #dc2626);
        }

        .month-name {
            font-size: 15px;
            font-weight: 800;
            color: #0f172a;
            margin: 0;
            line-height: 1.2;
            letter-spacing: -0.3px;
        }

        .month-sub {
            font-size: 11px;
            color: #64748b;
            font-weight: 500;
            margin-top: 2px;
        }

        /* Status Badges */
        .month-badge {
            display: inline-flex;
            align-items: center;
            gap: 5px;
            padding: 5px 12px;
            border-radius: 30px;
            font-size: 12px;
            font-weight: 800;
            letter-spacing: -0.2px;
            flex-shrink: 0;
        }

        .month-badge.badge-paid {
            background: #d1fae5;
            color: #047857;
            border: 1px solid rgba(16, 185, 129, 0.3);
        }

        .month-badge.badge-pending {
            background: #fee2e2;
            color: #dc2626;
            border: 1px solid rgba(239, 68, 68, 0.3);
        }

        .month-badge.badge-cleared {
            background: #f1f5f9;
            color: #475569;
            border: 1px solid #cbd5e1;
        }

        /* Box Body & Items */
        .month-box-body {
            padding: 12px 16px;
            display: flex;
            flex-direction: column;
            gap: 10px;
        }

        .month-item-row {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 10px;
            font-size: 13px;
            padding: 4px 0;
        }

        .month-item-row + .month-item-row {
            border-top: 1px dashed #f1f5f9;
            padding-top: 8px;
        }

        .item-particulars {
            font-weight: 600;
            color: #334155;
            display: flex;
            align-items: center;
            gap: 7px;
            word-break: break-word;
        }

        .item-particulars i {
            font-size: 12px;
            flex-shrink: 0;
        }

        .item-amount {
            font-weight: 800;
            font-size: 13.5px;
            flex-shrink: 0;
            letter-spacing: -0.3px;
        }

        .item-amount.text-debit { color: #ef4444; }
        .item-amount.text-credit { color: #10b981; }

        /* Card Footer & Receipt Action Button */
        .month-box-footer {
            padding: 10px 16px;
            background: #f8faff;
            border-top: 1px solid #f1f5f9;
            display: flex;
            justify-content: flex-end;
            align-items: center;
        }

        .btn-receipt-action {
            background: rgba(79, 70, 229, 0.08);
            color: #4f46e5;
            border: 1px solid rgba(79, 70, 229, 0.2);
            border-radius: 20px;
            padding: 6px 16px;
            font-size: 12px;
            font-weight: 700;
            font-family: 'Inter', sans-serif;
            cursor: pointer;
            display: inline-flex;
            align-items: center;
            gap: 6px;
            transition: all 0.2s;
        }

        .btn-receipt-action:hover, .btn-receipt-action:active {
            background: #4f46e5;
            color: #ffffff;
            border-color: #4f46e5;
        }

        /* ---- Invoice & Receipt Modal ---- */
        .receipt-modal-backdrop {
            position: fixed;
            top: 0; left: 0; right: 0; bottom: 0;
            background: rgba(15, 23, 42, 0.7);
            backdrop-filter: blur(5px);
            z-index: 2000;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 16px;
        }

        .receipt-modal-content {
            background: #ffffff;
            border-radius: 22px;
            width: 100%;
            max-width: 540px;
            max-height: 90vh;
            overflow-y: auto;
            box-shadow: 0 25px 50px rgba(0,0,0,0.3);
            animation: modalPop 0.25s ease both;
        }

        @keyframes modalPop {
            from { transform: scale(0.92); opacity: 0; }
            to { transform: scale(1); opacity: 1; }
        }

        /* Printable Receipt Card */
        .receipt-card {
            padding: 24px 20px;
            color: #0f172a;
            font-family: 'Inter', sans-serif;
        }

        .receipt-brand-header {
            display: flex;
            align-items: center;
            justify-content: space-between;
            border-bottom: 2px solid #e2e8f0;
            padding-bottom: 16px;
            margin-bottom: 16px;
        }

        .receipt-logo-wrap {
            display: flex;
            align-items: center;
            gap: 12px;
        }

        .receipt-logo-box {
            width: 44px; height: 44px;
            border-radius: 12px;
            background: linear-gradient(135deg, #4f46e5, #3b82f6);
            color: #fff;
            display: flex; align-items: center; justify-content: center;
            font-size: 22px; font-weight: 900;
        }

        .receipt-org-title {
            font-size: 16px; font-weight: 900; color: #0f172a; margin: 0; line-height: 1.2;
        }

        .receipt-org-sub {
            font-size: 11px; color: #64748b; margin: 2px 0 0; font-weight: 500;
        }

        .receipt-doc-title {
            text-align: right;
        }

        .receipt-doc-title h3 {
            font-size: 13px; font-weight: 900; color: #4f46e5; text-transform: uppercase; letter-spacing: 0.5px; margin: 0;
        }

        .receipt-doc-title div {
            font-size: 11px; color: #64748b; margin-top: 2px; font-weight: 600;
        }

        .receipt-info-grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 12px;
            background: #f8faff;
            border: 1px solid #e2e8f0;
            border-radius: 14px;
            padding: 12px 14px;
            margin-bottom: 16px;
        }

        .receipt-info-lbl {
            font-size: 10px; font-weight: 700; color: #64748b; text-transform: uppercase; letter-spacing: 0.5px;
        }

        .receipt-info-val {
            font-size: 13px; font-weight: 700; color: #0f172a; margin-top: 2px;
        }

        /* Receipt Table */
        .receipt-table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 16px;
        }

        .receipt-table th {
            background: #f1f5f9;
            padding: 10px;
            font-size: 11px;
            font-weight: 800;
            color: #475569;
            text-align: left;
            text-transform: uppercase;
            border-bottom: 1.5px solid #cbd5e1;
        }

        .receipt-table td {
            padding: 12px 10px;
            font-size: 13px;
            color: #334155;
            border-bottom: 1px solid #f1f5f9;
            font-weight: 600;
        }

        .receipt-totals-box {
            background: #f8faff;
            border-radius: 14px;
            padding: 14px;
            display: flex;
            flex-direction: column;
            gap: 6px;
            margin-bottom: 16px;
            border: 1px solid #e2e8f0;
        }

        .receipt-total-row {
            display: flex;
            justify-content: space-between;
            font-size: 13px;
            font-weight: 600;
            color: #475569;
        }

        .receipt-total-row.grand-total {
            font-size: 16px;
            font-weight: 900;
            color: #0f172a;
            border-top: 1.5px solid #e2e8f0;
            padding-top: 8px;
            margin-top: 4px;
        }

        /* Stamp */
        .receipt-stamp-row {
            display: flex;
            align-items: center;
            justify-content: space-between;
            margin-top: 14px;
            padding-top: 12px;
            border-top: 1px dashed #e2e8f0;
        }

        .receipt-stamp {
            display: inline-flex;
            align-items: center;
            gap: 6px;
            padding: 6px 16px;
            border-radius: 30px;
            font-size: 12px;
            font-weight: 900;
            letter-spacing: 0.5px;
            text-transform: uppercase;
        }

        .receipt-stamp.stamp-paid {
            background: #d1fae5; color: #047857; border: 1.5px solid #10b981;
        }

        .receipt-stamp.stamp-pending {
            background: #fee2e2; color: #dc2626; border: 1.5px solid #ef4444;
        }

        .receipt-modal-actions {
            padding: 14px 20px;
            background: #f8faff;
            border-top: 1px solid #e2e8f0;
            display: flex;
            justify-content: flex-end;
            gap: 10px;
            border-bottom-left-radius: 22px;
            border-bottom-right-radius: 22px;
        }

        /* Print styling */
        @media print {
            body * {
                visibility: hidden !important;
            }
            #invoice-receipt-print-area, #invoice-receipt-print-area * {
                visibility: visible !important;
            }
            #invoice-receipt-print-area {
                position: absolute !important;
                left: 0 !important; top: 0 !important; width: 100% !important;
                padding: 0 !important; margin: 0 !important;
            }
            .receipt-modal-backdrop {
                position: static !important;
                background: none !important;
                padding: 0 !important;
            }
            .receipt-modal-content {
                box-shadow: none !important;
                max-width: 100% !important;
            }
            .receipt-modal-actions {
                display: none !important;
            }
        }
    </style>
</head>
<body class="mob-body">

<?php include 'mobile_header.php'; ?>

<div class="mob-page">

    <!-- Financial Year Filter Card (AT TOP) -->
    <div class="mob-card" style="background:#ffffff; border-radius:20px; border:1px solid #edf2f7; box-shadow: 0 4px 20px rgba(0,0,0,0.035); margin-bottom:16px; overflow:hidden;">
        <div style="display:flex; align-items:center; gap:12px; padding:16px 20px; border-bottom:1px solid #f1f5f9;">
            <div style="width:38px; height:38px; border-radius:12px; background:linear-gradient(135deg, #4f46e5, #3b82f6); color:#ffffff; display:flex; align-items:center; justify-content:center; font-size:16px; font-weight:700; box-shadow: 0 4px 12px rgba(59,130,246,0.3);">
                <i class="fa fa-filter"></i>
            </div>
            <h3 style="font-size:16px; font-weight:800; color:#0f172a; margin:0; font-family:'Inter', sans-serif;">Financial Year</h3>
        </div>
        <div style="padding:16px 20px;">
            <label style="display:block; font-size:11px; font-weight:800; color:#64748b; text-transform:uppercase; letter-spacing:0.5px; margin-bottom:8px; font-family:'Inter', sans-serif;">SELECT YEAR</label>
            <div style="display:flex; align-items:center; gap:12px;">
                <select id="ledger-year" onchange="loadLedgerEntries()" class="mob-input" style="flex:1; height:46px; border-radius:12px; border:1.5px solid #e2e8f0; padding:0 14px; font-size:14px; font-weight:600; font-family:'Inter',sans-serif; background:#f8fafc; color:#0f172a; outline:none;"></select>
                <button onclick="loadLedgerEntries()" class="mob-btn" style="width:46px; height:46px; border-radius:12px; background:linear-gradient(135deg,#3b82f6,#6366f1); color:#fff; border:none; display:flex; align-items:center; justify-content:center; font-size:18px; box-shadow: 0 4px 14px rgba(59,130,246,0.4); cursor:pointer; flex-shrink:0;">
                    <i class="fa fa-refresh"></i>
                </button>
            </div>
        </div>
    </div>

    <!-- Summary KPIs — Always shows Current Financial Year -->
    <div style="font-size:11px; font-weight:800; color:#64748b; text-transform:uppercase; letter-spacing:0.6px; font-family:'Inter',sans-serif; margin-bottom:10px; display:flex; align-items:center; gap:6px;">
        <i class="fa fa-lock" style="color:#3b82f6;"></i>
        Current FY Summary &nbsp;<span style="font-weight:600; color:#94a3b8;" id="kpi-fy-label"></span>
    </div>
    <div class="mob-kpi-row">
        <div class="mob-kpi-card kpi-cred">
            <div class="mob-kpi-title">Total Credit</div>
            <div class="mob-kpi-val cred" id="kpi-credit">₹—</div>
        </div>
        <div class="mob-kpi-card kpi-deb">
            <div class="mob-kpi-title">Total Debit</div>
            <div class="mob-kpi-val deb" id="kpi-debit">₹—</div>
        </div>
        <div class="mob-kpi-card kpi-op">
            <div class="mob-kpi-title">Opening Balance</div>
            <div class="mob-kpi-val op" id="kpi-opening">₹—</div>
        </div>
        <div class="mob-kpi-card kpi-bal-zero" id="kpi-card-closing">
            <div class="mob-kpi-title">Closing Balance</div>
            <div class="mob-kpi-val bal" id="kpi-closing">₹—</div>
        </div>
    </div>

    <!-- UPI Pay Action Banner (Shown when Closing Balance > 0 and UPI is Active) -->
    <div id="upi-pay-banner" style="display:none; background: linear-gradient(135deg, #1e1b4b, #312e81); border-radius: 20px; padding: 20px; color: #fff; margin-bottom: 20px; box-shadow: 0 8px 24px rgba(30,27,75,0.25);">
        <div style="display:flex; align-items:center; justify-content:space-between; flex-wrap:wrap; gap:16px;">
            <div style="display:flex; align-items:center; gap:14px;">
                <div style="width:48px; height:48px; border-radius:14px; background:rgba(255,255,255,0.12); border:1px solid rgba(255,255,255,0.2); display:flex; align-items:center; justify-content:center; font-size:22px; color:#60a5fa;">
                    <i class="fa fa-credit-card"></i>
                </div>
                <div>
                    <div style="font-size:12px; font-weight:700; color:#93c5fd; text-transform:uppercase; letter-spacing:0.5px;">Direct UPI Payment</div>
                    <div style="font-size:16px; font-weight:800; color:#ffffff; margin-top:2px;">Outstanding Balance: <span id="upi-banner-balance" style="color:#fca5a5;">₹0.00</span></div>
                </div>
            </div>
            <button onclick="openUpiPayModal()" style="background: linear-gradient(135deg, #10b981, #059669); color: #ffffff; border: none; padding: 12px 24px; border-radius: 30px; font-size: 14px; font-weight: 800; display: inline-flex; align-items: center; gap: 8px; box-shadow: 0 4px 16px rgba(16,185,129,0.4); cursor: pointer; transition: all 0.2s;">
                <i class="fa fa-mobile" style="font-size:18px;"></i> Pay via UPI Now
            </button>
        </div>
    </div>

    <!-- Monthly Ledger Boxes -->
    <div id="ledger-entries">
        <div style="text-align:center;color:#94a3b8;padding:24px 0;font-size:13px;">
            <i class="fa fa-spinner fa-spin"></i> Loading Monthly Statements…
        </div>
    </div>

</div>

<!-- UPI Payment Modal Container -->
<div id="upi-modal-container" style="display:none; position:fixed; top:0; left:0; width:100%; height:100%; z-index:9999; overflow-y:auto;">
    <div style="position:fixed; top:0; left:0; width:100%; height:100%; background:rgba(15,23,42,0.65); backdrop-filter:blur(4px);" onclick="closeUpiPayModal(event)"></div>
    <div style="position:relative; max-width:480px; margin:40px auto; background:#ffffff; border-radius:24px; box-shadow:0 20px 40px rgba(0,0,0,0.2); overflow:hidden; z-index:10000; font-family:'Inter', sans-serif;">
        
        <!-- Header -->
        <div style="background:linear-gradient(135deg, #1e1b4b, #312e81); padding:24px 20px; color:#ffffff; position:relative;">
            <button onclick="closeUpiPayModal()" style="position:absolute; top:16px; right:16px; background:rgba(255,255,255,0.15); border:none; color:#fff; width:32px; height:32px; border-radius:50%; display:flex; align-items:center; justify-content:center; cursor:pointer;">
                <i class="fa fa-times"></i>
            </button>
            <div style="display:flex; align-items:center; gap:12px;">
                <div style="width:42px; height:42px; border-radius:12px; background:rgba(255,255,255,0.15); display:flex; align-items:center; justify-content:center; font-size:20px; color:#60a5fa;">
                    <i class="fa fa-mobile"></i>
                </div>
                <div>
                    <h3 style="font-size:17px; font-weight:800; margin:0; color:#ffffff;">Instant UPI Payment</h3>
                    <p style="font-size:12px; color:#93c5fd; margin:2px 0 0;" id="upi-modal-payee">YMCA BCP Poovathussery</p>
                </div>
            </div>
        </div>

        <!-- Body -->
        <div style="padding:24px 20px;">

            <!-- Important Cashier Screenshot Notice -->
            <div style="background:#fffbebf5; border:1px solid #fef08a; border-radius:14px; padding:12px 14px; margin-bottom:18px; display:flex; align-items:center; gap:10px; box-shadow:0 2px 8px rgba(234,179,8,0.08);">
                <div style="width:34px; height:34px; border-radius:10px; background:#fef08a; color:#a16207; display:flex; align-items:center; justify-content:center; font-size:16px; flex-shrink:0;">
                    <i class="fa fa-info-circle"></i>
                </div>
                <div style="font-size:12.5px; font-weight:700; color:#854d0e; line-height:1.4;">
                    Please share the payment screenshot with the cashier after completing payment.
                </div>
            </div>
            
            <!-- Amount Mode Selection -->
            <label style="display:block; font-size:11px; font-weight:800; color:#64748b; text-transform:uppercase; letter-spacing:0.5px; margin-bottom:12px;">SELECT PAYMENT AMOUNT</label>
            
            <div style="display:grid; grid-template-columns:1fr 1fr; gap:12px; margin-bottom:16px;">
                <div id="mode-card-full" onclick="selectUpiMode('full')" style="border:2px solid #3b82f6; background:#eff6ff; border-radius:14px; padding:14px 12px; cursor:pointer; text-align:center; transition:all 0.2s;">
                    <div style="font-size:11px; font-weight:700; color:#2563eb;">PAY FULL BALANCE</div>
                    <div style="font-size:16px; font-weight:900; color:#1e3a8a; margin-top:4px;" id="upi-mode-full-val">₹0.00</div>
                </div>
                <div id="mode-card-custom" onclick="selectUpiMode('custom')" style="border:2px solid #e2e8f0; background:#f8fafc; border-radius:14px; padding:14px 12px; cursor:pointer; text-align:center; transition:all 0.2s;">
                    <div style="font-size:11px; font-weight:700; color:#64748b;">PAY CUSTOM AMOUNT</div>
                    <div style="font-size:14px; font-weight:800; color:#0f172a; margin-top:4px;">Enter Amount</div>
                </div>
            </div>

            <!-- Custom Amount Input Field -->
            <div id="custom-amount-wrap" style="display:none; margin-bottom:20px;">
                <label style="display:block; font-size:11px; font-weight:800; color:#475569; margin-bottom:6px;">TYPE CUSTOM AMOUNT (₹)</label>
                <div style="position:relative;">
                    <span style="position:absolute; left:14px; top:12px; font-size:16px; font-weight:800; color:#64748b;">₹</span>
                    <input type="number" id="upi-custom-input" placeholder="Enter amount (e.g. 500)" style="width:100%; height:46px; border-radius:12px; border:2px solid #3b82f6; padding:0 14px 0 32px; font-size:16px; font-weight:800; color:#0f172a; outline:none;" oninput="onCustomAmountChange()">
                </div>
            </div>

            <!-- Mobile App Selector Buttons -->
            <div id="upi-mobile-section" style="margin-bottom:20px;">
                <label style="display:block; font-size:11px; font-weight:800; color:#64748b; text-transform:uppercase; letter-spacing:0.5px; margin-bottom:10px;">CHOOSE YOUR UPI PAY APP</label>
                <div style="display:grid; grid-template-columns:1fr 1fr; gap:10px; margin-bottom:10px;">
                    <button type="button" onclick="launchUpiApp('tez')" style="display:flex; align-items:center; justify-content:center; gap:8px; height:46px; background:#ffffff; border:1.5px solid #4285f4; color:#1a73e8; border-radius:14px; font-size:13.5px; font-weight:800; font-family:'Inter',sans-serif; cursor:pointer;">
                        <i class="fa fa-google" style="font-size:16px; color:#4285f4;"></i> Google Pay
                    </button>
                    <button type="button" onclick="launchUpiApp('phonepe')" style="display:flex; align-items:center; justify-content:center; gap:8px; height:46px; background:#ffffff; border:1.5px solid #5f259f; color:#5f259f; border-radius:14px; font-size:13.5px; font-weight:800; font-family:'Inter',sans-serif; cursor:pointer;">
                        <i class="fa fa-mobile" style="font-size:18px; color:#5f259f;"></i> PhonePe
                    </button>
                    <button type="button" onclick="launchUpiApp('paytmmp')" style="display:flex; align-items:center; justify-content:center; gap:8px; height:46px; background:#ffffff; border:1.5px solid #00baf2; color:#002e6e; border-radius:14px; font-size:13.5px; font-weight:800; font-family:'Inter',sans-serif; cursor:pointer;">
                        <i class="fa fa-credit-card" style="font-size:15px; color:#00baf2;"></i> Paytm
                    </button>
                    <button type="button" onclick="launchUpiApp('')" style="display:flex; align-items:center; justify-content:center; gap:8px; height:46px; background:#f1f5f9; border:1.5px solid #cbd5e1; color:#334155; border-radius:14px; font-size:13.5px; font-weight:800; font-family:'Inter',sans-serif; cursor:pointer;">
                        <i class="fa fa-shield" style="font-size:16px; color:#3b82f6;"></i> Any UPI App
                    </button>
                </div>
                <a id="upi-deep-link-btn" href="#" style="display:none;"></a>
            </div>

            <!-- Desktop QR Code & Copy Options -->
            <div style="background:#f8fafc; border:1px solid #e2e8f0; border-radius:16px; padding:16px; text-align:center;">
                <div style="font-size:11px; font-weight:800; color:#64748b; text-transform:uppercase; margin-bottom:10px;"><i class="fa fa-qrcode"></i> SCAN QR CODE TO PAY</div>
                
                <!-- Dynamic QR Code Image -->
                <div style="display:inline-block; background:#ffffff; padding:10px; border-radius:14px; border:1px solid #cbd5e1; box-shadow:0 2px 8px rgba(0,0,0,0.05); margin-bottom:12px;">
                    <img id="upi-qr-image" src="" alt="UPI QR Code" style="width:180px; height:180px; display:block;">
                </div>
                
                <div style="font-size:13px; font-weight:800; color:#0f172a; margin-bottom:12px;">
                    Amount to Pay: <span id="upi-qr-amount-text" style="color:#2563eb;">₹0.00</span>
                </div>

                <!-- Copy Action Buttons -->
                <div style="display:flex; gap:8px; justify-content:center; flex-wrap:wrap;">
                    <button onclick="copyUpiId()" style="background:#ffffff; border:1px solid #cbd5e1; color:#1e293b; padding:8px 14px; border-radius:20px; font-size:12px; font-weight:700; cursor:pointer; display:inline-flex; align-items:center; gap:6px;">
                        <i class="fa fa-copy" style="color:#3b82f6;"></i> Copy UPI ID
                    </button>
                    <button onclick="copyUpiLink()" style="background:#ffffff; border:1px solid #cbd5e1; color:#1e293b; padding:8px 14px; border-radius:20px; font-size:12px; font-weight:700; cursor:pointer; display:inline-flex; align-items:center; gap:6px;">
                        <i class="fa fa-link" style="color:#10b981;"></i> Copy Link
                    </button>
                </div>
            </div>

        </div>

    </div>
</div>

<!-- Printable Invoice & Receipt Modal Container -->
<div id="receipt-modal-container" style="display:none;">
    <div class="receipt-modal-backdrop" onclick="closeReceiptModal(event)">
        <div class="receipt-modal-content" onclick="event.stopPropagation()">
            
            <div id="invoice-receipt-print-area">
                <div class="receipt-card">
                    <!-- Brand Header -->
                    <div class="receipt-brand-header">
                        <div class="receipt-logo-wrap">
                            <div class="receipt-logo-box">Y</div>
                            <div>
                                <h2 class="receipt-org-title">YMCA BCP</h2>
                                <div style="font-size: 11.5px; font-weight: 700; color: #2563eb; margin: 1px 0 2px 0; font-family: 'Inter', sans-serif;">Poovathussery</div>
                                <p class="receipt-org-sub" id="rcpt-header-sub">Member Portal Fee Receipt & Invoice</p>
                            </div>
                        </div>
                        <div class="receipt-doc-title">
                            <h3 id="rcpt-header-title">OFFICIAL RECEIPT</h3>
                            <div id="rcpt-no">INV-2026-0001</div>
                        </div>
                    </div>

                    <!-- Info Grid -->
                    <div class="receipt-info-grid">
                        <div>
                            <div class="receipt-info-lbl">Billed To</div>
                            <div class="receipt-info-val" id="rcpt-member-name"><?php echo htmlspecialchars($member_name); ?></div>
                            <div style="font-size:11px;color:#64748b;margin-top:2px;">ID: #<?php echo $member_id; ?> | <?php echo htmlspecialchars($email); ?></div>
                        </div>
                        <div>
                            <div class="receipt-info-lbl">Date & Period</div>
                            <div class="receipt-info-val" id="rcpt-period">June 2026</div>
                            <div style="font-size:11px;color:#64748b;margin-top:2px;" id="rcpt-fy">FY 2026-2027</div>
                        </div>
                    </div>

                    <!-- Items Table -->
                    <table class="receipt-table">
                        <thead>
                            <tr>
                                <th>Particulars / Description</th>
                                <th style="text-align:right;">Amount (₹)</th>
                            </tr>
                        </thead>
                        <tbody id="rcpt-table-body">
                            <!-- Items injected dynamically -->
                        </tbody>
                    </table>

                    <!-- Totals Box -->
                    <div class="receipt-totals-box">
                        <div class="receipt-total-row">
                            <span>Total Fee Billed</span>
                            <span id="rcpt-total-billed">₹0.00</span>
                        </div>
                        <div class="receipt-total-row">
                            <span>Total Payments Received</span>
                            <span id="rcpt-total-paid" style="color:#10b981;">₹0.00</span>
                        </div>
                        <div class="receipt-total-row grand-total">
                            <span>Outstanding Balance</span>
                            <span id="rcpt-balance-due">₹0.00</span>
                        </div>
                    </div>

                    <!-- Stamp & Sign Footer -->
                    <div class="receipt-stamp-row">
                        <div id="rcpt-stamp-area">
                            <span class="receipt-stamp stamp-paid"><i class="fa fa-check-circle"></i> PAID</span>
                        </div>
                        <div style="text-align:right;">
                            <div style="font-size:11px;font-weight:700;color:#64748b;">YMCA BCP Accounts Verification</div>
                            <div style="font-size:10px;color:#94a3b8;margin-top:2px;">Digitally Generated & Verified</div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Modal Action Buttons -->
            <div class="receipt-modal-actions">
                <button class="mob-btn mob-btn-outline" style="width:auto;padding:8px 16px;font-size:12.5px;" onclick="$('#receipt-modal-container').fadeOut(200)">
                    <i class="fa fa-times"></i> Close
                </button>
                <button class="mob-btn mob-btn-primary" style="width:auto;padding:8px 20px;font-size:12.5px;" onclick="window.print()">
                    <i class="fa fa-print"></i> <span id="rcpt-btn-print-text">Print / Save PDF</span>
                </button>
            </div>

        </div>
    </div>
</div>

<?php include 'mobile_bottom_nav.php'; ?>

<script src="https://cdnjs.cloudflare.com/ajax/libs/jquery/3.6.0/jquery.min.js"></script>
<script>
var globalMonthGroups = {};

$(document).ready(function(){
    // Determine current financial year (April start)
    const now = new Date();
    const currentFyYear = (now.getMonth() >= 3) ? now.getFullYear() : now.getFullYear() - 1;

    // Populate year selector
    let opts = '';
    for(let y = currentFyYear + 1; y >= currentFyYear - 3; y--){
        const selected = (y === currentFyYear) ? ' selected' : '';
        opts += '<option value="'+y+'"'+selected+'>FY '+y+'-'+(y+1)+'</option>';
    }
    $('#ledger-year').html(opts);

    // Load current FY summary (locked) + entries for selected year
    loadCurrentFySummary(currentFyYear);
    loadLedgerEntries();
});

// ---- Always loads KPI summary for CURRENT financial year only ----
function loadCurrentFySummary(currentFyYear){
    const fyLabel = 'FY ' + currentFyYear + '-' + (currentFyYear + 1);
    $('#kpi-fy-label').text('(' + fyLabel + ')');

    $.post('../api/member_cashbook_report.php', {
        action: 'get_member_cashbook',
        member_id: <?php echo $member_id; ?>,
        year: currentFyYear
    }, function(data){
        try {
            const obj = JSON.parse(data);
            const summary = obj.summary || {};
            const closingVal = parseFloat(summary.closing_balance || summary.closing || 0);

            $('#kpi-credit').text('₹' + parseFloat(summary.total_credit  || 0).toFixed(2));
            $('#kpi-debit').text('₹'  + parseFloat(summary.total_debit   || 0).toFixed(2));
            $('#kpi-opening').text('₹'+ parseFloat(summary.opening_balance || summary.opening || 0).toFixed(2));
            $('#kpi-closing').text('₹'+ closingVal.toFixed(2));

            if (closingVal > 0) {
                $('#kpi-closing').css('color', '#ef4444');
                $('#kpi-card-closing').removeClass('kpi-bal-zero').addClass('kpi-bal-due');
            } else {
                $('#kpi-closing').css('color', '#10b981');
                $('#kpi-card-closing').removeClass('kpi-bal-due').addClass('kpi-bal-zero');
            }

            // UPI pay banner uses current FY closing balance
            checkAndShowUpiBanner(closingVal, summary.payment_settings);
        } catch(ex){ console.error(ex); }
    });
}

// ---- Loads only the transaction entries for the SELECTED year ----
function loadLedgerEntries(){
    if (typeof load_overlay === 'function') load_overlay();
    const year = $('#ledger-year').val();
    $('#ledger-entries').html('<div style="text-align:center;color:#94a3b8;padding:24px 0;font-size:13px;"><i class="fa fa-spinner fa-spin"></i> Loading Monthly Statements…</div>');

    $.post('../api/member_cashbook_report.php', {
        action: 'get_member_cashbook',
        member_id: <?php echo $member_id; ?>,
        year: year
    }, function(data){
        if (typeof close_overlay === 'function') close_overlay();
        try {
            const obj = JSON.parse(data);
            const entries = obj.transactions || obj.rows || [];
            if(entries.length === 0){
                $('#ledger-entries').html('<div style="text-align:center;color:#94a3b8;padding:24px 0;font-size:13px;">No ledger entries for this period.</div>');
                return;
            }
            $('#ledger-entries').html(renderMonthBoxes(entries, obj.summary));
        } catch(ex){
            console.error(ex);
            $('#ledger-entries').html('<div style="text-align:center;color:#ef4444;padding:24px 0;font-size:13px;"><i class="fa fa-exclamation-circle"></i> Failed to load ledger details.</div>');
        }
    }).fail(function(){
        if (typeof close_overlay === 'function') close_overlay();
        $('#ledger-entries').html('<div style="text-align:center;color:#ef4444;padding:24px 0;font-size:13px;"><i class="fa fa-exclamation-circle"></i> Server error. Please try again.</div>');
    });
}

// Keep old loadLedger name as alias (used nowhere now but safe to keep)
function loadLedger(){ loadLedgerEntries(); }


// Render Month Box Groupings with Attendance
function renderMonthBoxes(transactions, summary){
    const monthNamesMap = {
        '01':'January', '02':'February', '03':'March', '04':'April',
        '05':'May', '06':'June', '07':'July', '08':'August',
        '09':'September', '10':'October', '11':'November', '12':'December'
    };

    globalMonthGroups = {};

    transactions.forEach(function(t){
        if(!t.date) return;
        const ym = t.date.substring(0, 7); // "YYYY-MM"
        const parts = ym.split('-');
        const y = parts[0];
        const m = parts[1];
        const mName = monthNamesMap[m] || ('Month ' + m);
        const monthTitle = mName + ' ' + y;

        if(!globalMonthGroups[ym]){
            globalMonthGroups[ym] = {
                ym: ym,
                title: monthTitle,
                items: [],
                totalDebit: 0,
                totalCredit: 0,
                hasPending: false,
                pendingAmount: 0,
                attendance: 0
            };
        }

        globalMonthGroups[ym].items.push(t);
        const deb = parseFloat(t.debit || 0);
        const cred = parseFloat(t.credit || 0);
        globalMonthGroups[ym].totalDebit += deb;
        globalMonthGroups[ym].totalCredit += cred;
        
        const part = (t.particulars || '').toString();
        if(part.toLowerCase().includes('pending')){
            globalMonthGroups[ym].hasPending = true;
            if(deb > 0) globalMonthGroups[ym].pendingAmount += deb;
        }
    });

    if(summary && summary.monthly_attendance){
        for(let ym in summary.monthly_attendance){
            const attVal = parseInt(summary.monthly_attendance[ym] || 0);
            if(globalMonthGroups[ym]){
                globalMonthGroups[ym].attendance = Math.max(globalMonthGroups[ym].attendance, attVal);
            } else if(attVal > 0){
                const parts = ym.split('-');
                const y = parts[0];
                const m = parts[1];
                const mName = monthNamesMap[m] || ('Month ' + m);
                globalMonthGroups[ym] = {
                    ym: ym,
                    title: mName + ' ' + y,
                    items: [],
                    totalDebit: 0,
                    totalCredit: 0,
                    hasPending: false,
                    pendingAmount: 0,
                    attendance: attVal
                };
            }
        }
    }

    let html = '<div class="month-boxes-container">';

    const now = new Date();
    const currentYM = now.getFullYear() + '-' + String(now.getMonth() + 1).padStart(2, '0');

    const sortedMonthKeys = Object.keys(globalMonthGroups).sort().reverse();
    let renderedCount = 0;

    sortedMonthKeys.forEach(function(ym){
        const g = globalMonthGroups[ym];

        // Hide upcoming/future months unless explicit fee/payment transactions exist
        const hasActiveTransactions = g.totalDebit > 0 || g.totalCredit > 0;
        if(ym > currentYM && !hasActiveTransactions) {
            return;
        }

        renderedCount++;
        const netUnpaid = g.totalDebit - g.totalCredit;
        const isPaid = (g.totalCredit > 0 && netUnpaid <= 0) || (g.totalDebit > 0 && g.totalCredit >= g.totalDebit);
        
        let lastPaidDate = '';
        g.items.forEach(function(item){
            if(parseFloat(item.credit || 0) > 0 && item.date){
                lastPaidDate = item.date;
            }
        });

        let paidDateStr = '';
        if(lastPaidDate){
            const dt = new Date(lastPaidDate);
            if(!isNaN(dt.getTime())){
                paidDateStr = dt.toLocaleDateString('en-GB', { day: '2-digit', month: 'short', year: 'numeric' });
            }
        }

        const stateClass = isPaid ? 'state-paid' : 'state-neutral';

        html += '<div class="month-box ' + stateClass + '">';
        
        // Header
        html += '<div class="month-box-header">';
        html += '  <div class="month-title-wrap">';
        html += '    <div class="month-icon-circle">';
        if(isPaid){
            html += '      <i class="fa fa-check"></i>';
        } else {
            html += '      <i class="fa fa-calendar"></i>';
        }
        html += '    </div>';
        const attDays = g.attendance || 0;
        const isProcessed = (summary && summary.processed_months && summary.processed_months[ym] === true);

        html += '      <h4 class="month-name">' + g.title + '</h4>';
        if(!isProcessed){
            html += '      <div class="month-sub" style="display:flex;align-items:center;gap:5px;font-size:11.5px;color:#d97706;font-weight:700;margin-top:2px;">';
            html += '        <i class="fa fa-clock-o"></i> Attendance Not Processed';
            html += '      </div>';
        } else if(attDays > 0) {
            html += '      <div class="month-sub" style="display:flex;align-items:center;gap:5px;font-size:11.5px;color:#059669;font-weight:700;margin-top:2px;">';
            html += '        <i class="fa fa-calendar-check-o"></i> ' + attDays + ' Days Present';
            html += '      </div>';
        } else {
            html += '      <div class="month-sub" style="display:flex;align-items:center;gap:5px;font-size:11.5px;color:#64748b;font-weight:700;margin-top:2px;">';
            html += '        <i class="fa fa-calendar-o"></i> 0 Days Present';
            html += '      </div>';
        }
        html += '  </div>';

        // Status Badge with Date under tick box
        if(isPaid || g.totalCredit > 0){
            html += '  <div style="text-align:right;">';
            html += '    <div class="month-badge badge-paid"><i class="fa fa-check-circle"></i> Paid</div>';
            if(paidDateStr){
                html += '    <div style="font-size:11px;color:#059669;font-weight:700;margin-top:4px;">' + paidDateStr + '</div>';
            }
            html += '  </div>';
        }

        html += '</div>'; // end header

        // Body
        html += '<div class="month-box-body">';
        if(g.items.length === 0){
            html += '<div style="font-size:12.5px;color:#94a3b8;font-style:italic;padding:8px 0;text-align:center;">Attendance not processed for this month yet.</div>';
        } else {
            g.items.forEach(function(item){
                const deb = parseFloat(item.debit || 0);
                const cred = parseFloat(item.credit || 0);
                const part = item.particulars || 'Fee Record';

                html += '<div class="month-item-row">';
                html += '  <div class="item-particulars">';
                if(cred > 0){
                    html += '    <i class="fa fa-check-circle" style="color:#10b981;"></i> ' + part;
                } else {
                    html += '    <i class="fa fa-circle-o" style="color:#3b82f6;"></i> ' + part;
                }
                html += '  </div>';

                if(deb > 0){
                    html += '  <div class="item-amount text-debit">₹' + deb.toFixed(2) + '</div>';
                } else if(cred > 0){
                    html += '  <div class="item-amount text-credit">+₹' + cred.toFixed(2) + '</div>';
                } else {
                    html += '  <div class="item-amount" style="color:#94a3b8;">₹0.00</div>';
                }

                html += '</div>';
            });
        }
        html += '</div>'; // end body

        // Footer with separate Invoice & Receipt Action Buttons
        const hasInvoice = (g.totalDebit > 0);
        const hasReceipt = (g.totalCredit > 0);

        if(hasInvoice || hasReceipt){
            html += '<div class="month-box-footer" style="display:flex; gap:8px; justify-content:flex-end;">';
            if (hasInvoice) {
                html += '  <button class="btn-receipt-action" style="background:rgba(59,130,246,0.08); color:#2563eb; border-color:rgba(59,130,246,0.2);" onclick="openInvoiceModal(\'' + ym + '\')">';
                html += '    <i class="fa fa-file-text-o"></i> View Invoice';
                html += '  </button>';
            }
            if (hasReceipt) {
                html += '  <button class="btn-receipt-action" style="background:rgba(16,185,129,0.08); color:#059669; border-color:rgba(16,185,129,0.2);" onclick="openReceiptModal(\'' + ym + '\')">';
                html += '    <i class="fa fa-check-square-o"></i> View Receipt';
                html += '  </button>';
            }
            html += '</div>';
        }

        html += '</div>'; // end month-box
    });

    if(renderedCount === 0){
        return '<div style="text-align:center;color:#94a3b8;padding:24px 0;font-size:13px;">No attendance processed or fee records for this period.</div>';
    }

    html += '</div>';
    return html;
}

// 1. Open SEPARATE Invoice Modal
function openInvoiceModal(ym){
    const g = globalMonthGroups[ym];
    if(!g) return;

    const fyYear = $('#ledger-year').val();

    $('#rcpt-header-title').text('OFFICIAL FEE INVOICE');
    $('#rcpt-header-sub').text('Monthly Fee Bill & Payment Demand');
    $('#rcpt-no').text('INV-' + ym.replace('-','') + '-<?php echo $member_id; ?>');
    $('#rcpt-period').text(g.title);
    $('#rcpt-fy').text('FY ' + fyYear + '-' + (parseInt(fyYear)+1));
    $('#rcpt-total-billed').text('₹' + g.totalDebit.toFixed(2));
    $('#rcpt-total-paid').text('₹' + g.totalCredit.toFixed(2));

    const netUnpaid = g.totalDebit - g.totalCredit;
    const isPending = g.hasPending || netUnpaid > 0;
    const balanceDue = netUnpaid > 0 ? netUnpaid : 0;
    $('#rcpt-balance-due').text('₹' + balanceDue.toFixed(2));

    if(!isPending && (g.totalCredit > 0 || (g.totalDebit > 0 && g.totalCredit >= g.totalDebit))){
        $('#rcpt-stamp-area').html('<span class="receipt-stamp stamp-paid"><i class="fa fa-check-circle"></i> INVOICE PAID IN FULL</span>');
    } else {
        $('#rcpt-stamp-area').html('<span class="receipt-stamp stamp-pending"><i class="fa fa-exclamation-circle"></i> INVOICE DUE (₹' + balanceDue.toFixed(2) + ')</span>');
    }

    // Populate Billed Fee / Debit Items
    let tableHtml = '';
    g.items.forEach(function(item){
        const deb = parseFloat(item.debit || 0);
        if(deb > 0 || g.items.length === 1){
            const amt = deb > 0 ? deb : parseFloat(item.credit || 0);
            const part = item.particulars || 'Fee Particulars';
            tableHtml += '<tr>';
            tableHtml += '  <td>' + part + '</td>';
            tableHtml += '  <td style="text-align:right;font-weight:700;color:#0f172a;">₹' + amt.toFixed(2) + '</td>';
            tableHtml += '</tr>';
        }
    });
    if(!tableHtml) {
        tableHtml = '<tr><td colspan="2" style="text-align:center;color:#94a3b8;">No fee charges for this month.</td></tr>';
    }
    $('#rcpt-table-body').html(tableHtml);
    $('#rcpt-btn-print-text').text('Print / Save Invoice PDF');
    $('#receipt-modal-container').fadeIn(200);
}

// 2. Open SEPARATE Receipt Modal
function openReceiptModal(ym){
    const g = globalMonthGroups[ym];
    if(!g) return;

    const fyYear = $('#ledger-year').val();

    $('#rcpt-header-title').text('PAYMENT RECEIPT');
    $('#rcpt-header-sub').text('Official Fee Payment Acknowledgment');
    $('#rcpt-no').text('RCPT-' + ym.replace('-','') + '-<?php echo $member_id; ?>');
    $('#rcpt-period').text(g.title);
    $('#rcpt-fy').text('FY ' + fyYear + '-' + (parseInt(fyYear)+1));
    $('#rcpt-total-billed').text('₹' + g.totalDebit.toFixed(2));
    $('#rcpt-total-paid').text('₹' + g.totalCredit.toFixed(2));

    const netUnpaid = g.totalDebit - g.totalCredit;
    const balanceDue = netUnpaid > 0 ? netUnpaid : 0;
    $('#rcpt-balance-due').text('₹' + balanceDue.toFixed(2));

    if(g.totalCredit > 0){
        $('#rcpt-stamp-area').html('<span class="receipt-stamp stamp-paid"><i class="fa fa-check-circle"></i> PAYMENT RECEIVED & VERIFIED</span>');
    } else {
        $('#rcpt-stamp-area').html('<span class="receipt-stamp stamp-pending"><i class="fa fa-clock-o"></i> NO PAYMENT RECORDED YET</span>');
    }

    // Populate Paid / Credit Items
    let tableHtml = '';
    g.items.forEach(function(item){
        const cred = parseFloat(item.credit || 0);
        if(cred > 0){
            const part = item.particulars || 'Payment Received';
            tableHtml += '<tr>';
            tableHtml += '  <td><i class="fa fa-check-circle" style="color:#10b981;"></i> ' + part + '</td>';
            tableHtml += '  <td style="text-align:right;font-weight:700;color:#10b981;">₹' + cred.toFixed(2) + '</td>';
            tableHtml += '</tr>';
        }
    });
    if(!tableHtml) {
        tableHtml = '<tr><td colspan="2" style="text-align:center;color:#94a3b8;">No payment credits recorded for this month.</td></tr>';
    }
    $('#rcpt-table-body').html(tableHtml);
    $('#rcpt-btn-print-text').text('Print / Save Receipt PDF');
    $('#receipt-modal-container').fadeIn(200);
}

function closeReceiptModal(event){
    if(event.target.classList.contains('receipt-modal-backdrop')){
        $('#receipt-modal-container').fadeOut(200);
    }
}

/* ---- UPI Payment Functions ---- */
var currentUpiSettings = null;
var currentClosingBalance = 0;
var selectedPayMode = 'full';
var activeUpiAmount = 0;

function checkAndShowUpiBanner(closingVal, settings) {
    currentClosingBalance = closingVal > 0 ? closingVal : 0;
    currentUpiSettings = settings || {
        upi_id: 'ymcabcp@okaxis',
        payee_name: 'YMCA BCP Poovathussery',
        payment_note: 'YMCA Member Fee Payment',
        is_active: 1
    };

    if (currentUpiSettings && currentUpiSettings.is_active !== 0) {
        if (currentClosingBalance > 0) {
            $('#upi-banner-balance').html('₹' + currentClosingBalance.toFixed(2));
        } else {
            $('#upi-banner-balance').html('₹0.00');
        }
        $('#upi-pay-banner').fadeIn(200);
    } else {
        $('#upi-pay-banner').hide();
    }
}

function openUpiPayModal() {
    if (!currentUpiSettings) {
        currentUpiSettings = {
            upi_id: 'ymcabcp@okaxis',
            payee_name: 'YMCA BCP Poovathussery',
            payment_note: 'YMCA Member Fee Payment',
            is_active: 1
        };
    }
    
    $('#upi-modal-payee').text(currentUpiSettings.payee_name || 'YMCA BCP Poovathussery');
    $('#upi-mode-full-val').text('₹' + currentClosingBalance.toFixed(2));
    
    if (currentClosingBalance > 0) {
        selectUpiMode('full');
    } else {
        selectUpiMode('custom');
    }
    $('#upi-modal-container').fadeIn(200);
}

function closeUpiPayModal(e) {
    if (e && e.target !== e.currentTarget) return;
    $('#upi-modal-container').fadeOut(200);
}

function selectUpiMode(mode) {
    selectedPayMode = mode;
    if (mode === 'full') {
        $('#mode-card-full').css({'border-color': '#3b82f6', 'background': '#eff6ff'});
        $('#mode-card-custom').css({'border-color': '#e2e8f0', 'background': '#f8fafc'});
        $('#custom-amount-wrap').hide();
        activeUpiAmount = currentClosingBalance;
    } else {
        $('#mode-card-custom').css({'border-color': '#3b82f6', 'background': '#eff6ff'});
        $('#mode-card-full').css({'border-color': '#e2e8f0', 'background': '#f8fafc'});
        $('#custom-amount-wrap').show();
        $('#upi-custom-input').focus();
        onCustomAmountChange();
        return;
    }
    updateUpiLinksAndQr();
}

function onCustomAmountChange() {
    const val = parseFloat($('#upi-custom-input').val() || 0);
    activeUpiAmount = val > 0 ? val : 0;
    updateUpiLinksAndQr();
}

function updateUpiLinksAndQr() {
    if (!currentUpiSettings) return;
    const upiId = currentUpiSettings.upi_id || 'ymcabcp@okaxis';
    const payee = encodeURIComponent(currentUpiSettings.payee_name || 'YMCA BCP');
    const note = encodeURIComponent(currentUpiSettings.payment_note || 'YMCA Fee Payment');
    const amtStr = activeUpiAmount > 0 ? activeUpiAmount.toFixed(2) : '0.00';

    // Standard UPI Deep Link URL
    const upiUrl = `upi://pay?pa=${upiId}&pn=${payee}&am=${amtStr}&cu=INR&tn=${note}`;

    $('#upi-deep-link-btn').attr('href', upiUrl);
    $('#upi-qr-amount-text').text('₹' + amtStr);

    // Dynamic Live QR Code API
    const qrImgUrl = `https://api.qrserver.com/v1/create-qr-code/?size=200x200&data=${encodeURIComponent(upiUrl)}`;
    $('#upi-qr-image').attr('src', qrImgUrl);
}

function launchUpiApp(scheme) {
    if (!currentUpiSettings) return;
    const upiId = currentUpiSettings.upi_id || 'ymcabcp@okaxis';
    const payee = encodeURIComponent(currentUpiSettings.payee_name || 'YMCA BCP');
    const note = encodeURIComponent(currentUpiSettings.payment_note || 'YMCA Fee Payment');
    const amtStr = activeUpiAmount > 0 ? activeUpiAmount.toFixed(2) : '0.00';
    const params = `pa=${upiId}&pn=${payee}&am=${amtStr}&cu=INR&tn=${note}`;

    let url = `upi://pay?${params}`;
    if (scheme === 'tez') {
        url = `tez://upi/pay?${params}`;
    } else if (scheme === 'phonepe') {
        url = `phonepe://pay?${params}`;
    } else if (scheme === 'paytmmp') {
        url = `paytmmp://pay?${params}`;
    }

    window.location.href = url;
}


function copyUpiId() {
    if (!currentUpiSettings || !currentUpiSettings.upi_id) return;
    navigator.clipboard.writeText(currentUpiSettings.upi_id);
    if(typeof swal === 'function') {
        swal("Copied!", "UPI ID (" + currentUpiSettings.upi_id + ") copied to clipboard.", "success");
    } else {
        alert("UPI ID (" + currentUpiSettings.upi_id + ") copied to clipboard.");
    }
}

function copyUpiLink() {
    const upiUrl = $('#upi-deep-link-btn').attr('href');
    if (upiUrl) {
        navigator.clipboard.writeText(upiUrl);
        if(typeof swal === 'function') {
            swal("Copied!", "UPI Payment link copied to clipboard.", "success");
        } else {
            alert("UPI Payment link copied to clipboard.");
        }
    }
}
</script>

</body>
</html>
