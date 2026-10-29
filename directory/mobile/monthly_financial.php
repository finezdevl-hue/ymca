<?php
session_start();
include '../../app_common/db_connect.php';
include '../../app_common/auth_helper.php';

if (empty($_SESSION['login_id'])) {
    header("Location: ../../index.php");
    exit();
}

$login_id = (int)$_SESSION['login_id'];
if (isNormalMember($login_id)) {
    header("Location: home.php");
    exit();
}
$is_admin = isSuperAdmin($login_id) || isGroupAdmin($login_id) || isAttendanceMaster($login_id) || isExecutiveMember($login_id);
$allowed_groups = getUserAllowedGroupIds($login_id);
$primary_role = getUserPrimaryRoleName($login_id);

$active_tab = 'reports';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
    <title>Monthly Financial Statement - YMCA Mobile</title>
    
    <link href="../../css/bootstrap.min.css" rel="stylesheet">
    <link href="../../font-awesome/css/font-awesome.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <link href="mobile_layout.css" rel="stylesheet">

    <style>
        body { font-family: 'Plus Jakarta Sans', sans-serif !important; background: #f8fafc !important; }
        .rep-hero {
            background: linear-gradient(135deg, #1e3a8a 0%, #3b82f6 100%);
            border-radius: 20px; padding: 18px 20px; color: #ffffff; margin-bottom: 16px;
            box-shadow: 0 8px 20px rgba(30, 58, 138, 0.25);
            position: relative;
        }
        .rep-hero h2 { margin: 0 0 4px 0; font-weight: 800; font-size: 19px; }
        .rep-hero p { margin: 0; font-size: 12.5px; opacity: 0.9; }

        .rep-filter-box {
            background: #ffffff; border-radius: 16px; border: 1px solid #e2e8f0;
            padding: 14px; margin-bottom: 14px; box-shadow: 0 2px 8px rgba(0,0,0,0.02);
            display: flex; flex-direction: column; gap: 10px;
        }
        .rep-field { display: flex; flex-direction: column; gap: 4px; }
        .rep-field label { font-size: 11px; font-weight: 700; color: #64748b; text-transform: uppercase; margin: 0; }
        .rep-field input, .rep-field select {
            padding: 10px 14px; border-radius: 12px; border: 1.5px solid #cbd5e1;
            background: #f8fafc; font-size: 13.5px; font-weight: 600; color: #0f172a; outline: none;
        }

        .btn-mob-print {
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 8px;
            width: 100%;
            padding: 11px 16px;
            background: linear-gradient(135deg, #10b981 0%, #059669 100%);
            color: #ffffff;
            border: none;
            border-radius: 12px;
            font-size: 13.5px;
            font-weight: 700;
            box-shadow: 0 3px 10px rgba(16,185,129,0.25);
            cursor: pointer;
            transition: transform 0.15s, opacity 0.15s;
        }
        .btn-mob-print:active {
            transform: scale(0.98);
            opacity: 0.9;
        }

        /* Stat Grid */
        .stat-card-grid {
            display: grid; grid-template-columns: repeat(2, 1fr); gap: 10px; margin-bottom: 16px;
        }
        .stat-box {
            background: #fff; border-radius: 14px; border: 1px solid #e2e8f0;
            padding: 12px 14px; box-shadow: 0 2px 6px rgba(0,0,0,0.02);
            position: relative; display: flex; flex-direction: column; gap: 3px;
        }
        .stat-lbl { font-size: 10px; font-weight: 800; color: #64748b; text-transform: uppercase; letter-spacing: 0.04em; }
        .stat-val { font-size: 17px; font-weight: 800; color: #0f172a; }
        .stat-sub { font-size: 10.5px; font-weight: 600; color: #94a3b8; }
        .stat-icon { position: absolute; right: 12px; bottom: 12px; opacity: 0.15; font-size: 22px; }

        /* Segmented Tabs */
        .seg-nav {
            display: flex; background: #e2e8f0; padding: 4px; border-radius: 14px;
            gap: 4px; margin-bottom: 16px;
        }
        .seg-btn {
            flex: 1; padding: 9px 8px; border-radius: 10px; border: none;
            background: transparent; font-size: 12px; font-weight: 700; color: #64748b;
            cursor: pointer; transition: all 0.18s; display: flex; align-items: center; justify-content: center; gap: 5px;
        }
        .seg-btn.active {
            background: #ffffff; color: #0f172a; box-shadow: 0 2px 6px rgba(0,0,0,0.06);
        }
        .seg-badge {
            background: #f1f5f9; padding: 2px 6px; border-radius: 10px; font-size: 10px;
        }
        .seg-btn.active .seg-badge {
            background: #e0e7ff; color: #4338ca;
        }

        /* Table Card */
        .mob-tbl-card {
            background: #ffffff; border-radius: 16px; border: 1px solid #e2e8f0; overflow: hidden;
            box-shadow: 0 2px 8px rgba(0,0,0,0.02); margin-bottom: 20px;
        }
        .mob-tbl-header {
            padding: 12px 16px; border-bottom: 1px solid #e2e8f0; font-size: 13.5px;
            font-weight: 800; color: #0f172a; display: flex; align-items: center; justify-content: space-between;
        }
        .table-mob { width: 100%; border-collapse: collapse; font-size: 12.5px; }
        .table-mob th {
            background: #f8fafc; padding: 10px 14px; font-size: 10.5px; font-weight: 700;
            color: #475569; text-transform: uppercase; border-bottom: 1px solid #e2e8f0;
        }
        .table-mob td { padding: 11px 14px; border-bottom: 1px solid #f1f5f9; color: #1e293b; font-weight: 500; }
        .table-mob tfoot td { background: #f8fafc; font-weight: 800; border-top: 1.5px solid #cbd5e1; padding: 12px 14px; }

        .mob-badge {
            display: inline-block; padding: 3px 8px; border-radius: 6px; font-size: 10.5px; font-weight: 700;
        }
        .mob-badge-inc { background: #dcfce7; color: #15803d; }
        .mob-badge-exp { background: #fee2e2; color: #b91c1c; }
        .mob-badge-guest { background: #fef3c7; color: #b45309; }
        .mob-badge-surplus { background: #dcfce7; color: #15803d; }
        .mob-badge-deficit { background: #fee2e2; color: #b91c1c; }

        .btn-mob-bill {
            display: inline-flex; align-items: center; gap: 4px; padding: 3px 8px;
            background: #e0e7ff; color: #4338ca; border-radius: 6px; font-size: 11px;
            font-weight: 600; text-decoration: none; border: none;
        }

        .rep-empty-mob {
            text-align: center; padding: 35px 16px; color: #94a3b8;
        }
        .rep-empty-mob i { font-size: 30px; margin-bottom: 8px; display: block; color: #cbd5e1; }

        /* Print only elements hidden by default on screen */
        .print-only-block { display: none; }
        .print-sig-block { display: none; }

        /* ==========================================================================
           PRINT STYLING (A4 Clean Executive Statement for Mobile Print/PDF)
           ========================================================================== */
        @media print {
            @page {
                size: A4 portrait;
                margin: 10mm 10mm 12mm 10mm;
            }
            *, *::before, *::after {
                box-shadow: none !important;
                text-shadow: none !important;
            }
            html, body {
                background: #ffffff !important;
                color: #0f172a !important;
                margin: 0 !important;
                padding: 0 !important;
                width: 100% !important;
                font-family: 'Inter', -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, Arial, sans-serif !important;
                font-size: 8.5pt !important;
            }

            /* Hide mobile screen UI controls */
            .bottom-nav, .rep-hero, .rep-filter-box, .stat-card-grid, .seg-nav,
            .mob-tbl-card, .btn-mob-bill, .btn-mob-print {
                display: none !important;
            }

            .main-content {
                padding: 0 !important;
                margin: 0 !important;
                width: 100% !important;
            }

            /* Display Print Only Container */
            .print-only-block {
                display: block !important;
                width: 100% !important;
                margin: 0 0 12px 0 !important;
            }

            .table-print {
                width: 100% !important;
                max-width: 100% !important;
                table-layout: fixed !important;
                border-collapse: collapse !important;
                border: 1px solid #94a3b8 !important;
                font-size: 8pt !important;
                margin-bottom: 14px !important;
                page-break-inside: auto;
            }
            .table-print th {
                background-color: #1e293b !important;
                color: #ffffff !important;
                -webkit-print-color-adjust: exact !important;
                print-color-adjust: exact !important;
                font-size: 7.5pt !important;
                font-weight: 800 !important;
                padding: 5px 6px !important;
                border: 1px solid #0f172a !important;
                text-transform: uppercase !important;
                letter-spacing: 0.3px !important;
            }
            .table-print td {
                padding: 4px 6px !important;
                font-size: 8pt !important;
                color: #0f172a !important;
                border: 1px solid #cbd5e1 !important;
                vertical-align: middle !important;
                word-wrap: break-word !important;
                overflow-wrap: break-word !important;
                line-height: 1.3 !important;
            }
            .table-print tfoot td {
                background-color: #f1f5f9 !important;
                -webkit-print-color-adjust: exact !important;
                print-color-adjust: exact !important;
                font-weight: 800 !important;
                font-size: 8pt !important;
                border-top: 1.5px solid #0f172a !important;
                border-bottom: 1px solid #94a3b8 !important;
                padding: 5px 6px !important;
            }
            .table-print tr {
                page-break-inside: avoid !important;
                break-inside: avoid !important;
            }
            thead { display: table-header-group !important; }
            tfoot { display: table-footer-group !important; }

            .head-badge {
                border: 1px solid #cbd5e1 !important;
                padding: 1px 4px !important;
                font-size: 7pt !important;
                font-weight: 700 !important;
                background: #f1f5f9 !important;
                color: #0f172a !important;
                -webkit-print-color-adjust: exact !important;
                print-color-adjust: exact !important;
            }

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
</head>
<body class="mobile-app">

    <div class="main-content" style="padding: 16px 16px 85px 16px;">

        <!-- Dedicated Executive Print Container (Hidden on Screen, Shown on Print/PDF) -->
        <div class="print-only-block">
            <!-- 1. Club Header Banner -->
            <div style="text-align:center; padding-bottom:8px; border-bottom:2px solid #1e3a8a; margin-bottom:8px;">
                <div style="font-size:16pt; font-weight:800; color:#1e3a8a; letter-spacing:0.5px; text-transform:uppercase;">
                    YMCA Badminton Club Poovathussery
                </div>
                <div style="font-size:8.5pt; color:#64748b; margin-top:2px;">
                    Poovathussery, Parakkadavu P.O., Ernakulam Dist., Kerala &bull; Phone: +91 94951 83688
                </div>
                <div style="background-color:#1e3a8a; color:#ffffff; font-weight:700; font-size:10pt; padding:4px 8px; margin-top:6px; letter-spacing:0.5px; text-transform:uppercase; -webkit-print-color-adjust:exact; print-color-adjust:exact;">
                    Monthly Financial & Operations Statement
                </div>
            </div>

            <!-- 2. Statement Metadata Table -->
            <table style="width:100%; border:1px solid #cbd5e1; background-color:#f8fafc; margin-bottom:10px; font-size:8pt; border-collapse:collapse; -webkit-print-color-adjust:exact; print-color-adjust:exact;">
                <tr>
                    <td style="padding:4px 8px; color:#64748b; font-weight:700; width:15%; border:1px solid #e2e8f0;">STATEMENT MONTH:</td>
                    <td style="padding:4px 8px; color:#0f172a; font-weight:800; width:35%; border:1px solid #e2e8f0;" id="print_mob_month">—</td>
                    <td style="padding:4px 8px; color:#64748b; font-weight:700; width:15%; border:1px solid #e2e8f0;">BATCH / GROUP:</td>
                    <td style="padding:4px 8px; color:#0f172a; font-weight:800; width:35%; border:1px solid #e2e8f0;" id="print_mob_group">—</td>
                </tr>
                <tr>
                    <td style="padding:4px 8px; color:#64748b; font-weight:700; border:1px solid #e2e8f0;">DATE GENERATED:</td>
                    <td style="padding:4px 8px; color:#0f172a; border:1px solid #e2e8f0;" id="print_mob_date">—</td>
                    <td style="padding:4px 8px; color:#64748b; font-weight:700; border:1px solid #e2e8f0;">SESSIONS HELD:</td>
                    <td style="padding:4px 8px; color:#0f172a; border:1px solid #e2e8f0;" id="print_mob_sessions">—</td>
                </tr>
            </table>

            <!-- 3. Financial KPI Summary Cards (Compact 5-cell table) -->
            <table style="width:100%; border-collapse:collapse; margin-bottom:12px; font-size:8pt; table-layout:fixed; -webkit-print-color-adjust:exact; print-color-adjust:exact;">
                <tr>
                    <td style="width:20%; border:1px solid #cbd5e1; background-color:#eef2ff; padding:6px 8px; text-align:center;">
                        <div style="font-size:7pt; font-weight:800; color:#4338ca; text-transform:uppercase;">TOTAL FEE RECEIVABLE</div>
                        <div style="font-size:11.5pt; font-weight:800; color:#3730a3; margin:2px 0;" id="print_mob_kpi_receivable">₹ 0.00</div>
                        <div style="font-size:7pt; color:#4f46e5;" id="print_mob_kpi_rec_sub">0 Members Billed</div>
                    </td>
                    <td style="width:20%; border:1px solid #cbd5e1; background-color:#ecfdf5; padding:6px 8px; text-align:center;">
                        <div style="font-size:7pt; font-weight:800; color:#047857; text-transform:uppercase;">TOTAL AMOUNT COLLECTED</div>
                        <div style="font-size:11.5pt; font-weight:800; color:#065f46; margin:2px 0;" id="print_mob_kpi_income">₹ 0.00</div>
                        <div style="font-size:7pt; color:#059669;" id="print_mob_kpi_inc_sub">0 Collections</div>
                    </td>
                    <td style="width:20%; border:1px solid #cbd5e1; background-color:#fef2f2; padding:6px 8px; text-align:center;">
                        <div style="font-size:7pt; font-weight:800; color:#b91c1c; text-transform:uppercase;">TOTAL EXPENSES</div>
                        <div style="font-size:11.5pt; font-weight:800; color:#991b1b; margin:2px 0;" id="print_mob_kpi_expense">₹ 0.00</div>
                        <div style="font-size:7pt; color:#dc2626;" id="print_mob_kpi_exp_sub">0 Vouchers Paid</div>
                    </td>
                    <td style="width:20%; border:1px solid #cbd5e1; background-color:#ecfeff; padding:6px 8px; text-align:center;">
                        <div style="font-size:7pt; font-weight:800; color:#0e7490; text-transform:uppercase;">SHUTTLE COST</div>
                        <div style="font-size:11.5pt; font-weight:800; color:#0891b2; margin:2px 0;" id="print_mob_kpi_shuttle">₹ 0.00</div>
                        <div style="font-size:7pt; color:#06b6d4;" id="print_mob_kpi_shuttle_sub">0 Shuttles Used</div>
                    </td>
                    <td style="width:20%; border:1px solid #cbd5e1; background-color:#f0fdf4; padding:6px 8px; text-align:center;">
                        <div style="font-size:7pt; font-weight:800; color:#15803d; text-transform:uppercase;">PRESENT MEMBERS</div>
                        <div style="font-size:11.5pt; font-weight:800; color:#166534; margin:2px 0;" id="print_mob_kpi_members">0 Members</div>
                        <div style="font-size:7pt; color:#15803d;" id="print_mob_kpi_mem_sub">0 Check-ins</div>
                    </td>
                </tr>
            </table>

            <!-- 4. SECTION 1: Monthly Income Received & Collections Print Table -->
            <div style="margin-bottom:16px;">
                <div style="font-size:9.5pt; font-weight:800; color:#0f172a; text-transform:uppercase; border-bottom:1.5px solid #0f172a; padding-bottom:4px; margin-bottom:6px; display:flex; justify-content:space-between;">
                    <span>Monthly Income Received & Collections</span>
                    <span id="print_mob_badge_inc_total" style="font-size:8pt; border:1px solid #cbd5e1; padding:2px 6px; background:#f8fafc;">Total: ₹ 0.00</span>
                </div>
                <table class="table-print">
                    <thead>
                        <tr>
                            <th style="width:5%; text-align:center;">#</th>
                            <th style="width:13%;">Date</th>
                            <th style="width:24%;">Who Paid (Member / Payer)</th>
                            <th style="width:14%;">Category</th>
                            <th style="width:24%;">Description / Particulars</th>
                            <th style="width:10%;">Group</th>
                            <th style="width:14%; text-align:right;">Amount (₹)</th>
                        </tr>
                    </thead>
                    <tbody id="print_mob_inc_tbody">
                        <!-- Dynamic Print Rows -->
                    </tbody>
                    <tfoot id="print_mob_inc_tfoot" style="display:none;">
                        <tr>
                            <td colspan="6" style="text-align:right; font-weight:800;">TOTAL AMOUNT COLLECTED:</td>
                            <td style="text-align:right; color:#059669; font-weight:800;" id="print_mob_foot_inc_total">₹ 0.00</td>
                        </tr>
                    </tfoot>
                </table>
            </div>

            <!-- 5. SECTION 2: Monthly Expenses Paid Print Table -->
            <div style="margin-bottom:16px;">
                <div style="font-size:9.5pt; font-weight:800; color:#0f172a; text-transform:uppercase; border-bottom:1.5px solid #0f172a; padding-bottom:4px; margin-bottom:6px; display:flex; justify-content:space-between;">
                    <span>Monthly Expenses Paid</span>
                    <span id="print_mob_badge_exp_total" style="font-size:8pt; border:1px solid #cbd5e1; padding:2px 6px; background:#f8fafc;">Total: ₹ 0.00</span>
                </div>
                <table class="table-print">
                    <thead>
                        <tr>
                            <th style="width:5%; text-align:center;">#</th>
                            <th style="width:13%;">Date</th>
                            <th style="width:18%;">Expense Head</th>
                            <th style="width:40%;">Particulars</th>
                            <th style="width:11%;">Group</th>
                            <th style="width:13%; text-align:right;">Amount (₹)</th>
                        </tr>
                    </thead>
                    <tbody id="print_mob_exp_tbody">
                        <!-- Dynamic Print Rows -->
                    </tbody>
                    <tfoot id="print_mob_exp_tfoot" style="display:none;">
                        <tr>
                            <td colspan="5" style="text-align:right; font-weight:800;">TOTAL EXPENSES:</td>
                            <td style="text-align:right; color:#dc2626; font-weight:800;" id="print_mob_foot_exp_total">₹ 0.00</td>
                        </tr>
                    </tfoot>
                </table>
            </div>

            <!-- 6. SECTION 3: Monthly Present Members Print Table -->
            <div style="margin-bottom:16px;">
                <div style="font-size:9.5pt; font-weight:800; color:#0f172a; text-transform:uppercase; border-bottom:1.5px solid #0f172a; padding-bottom:4px; margin-bottom:6px; display:flex; justify-content:space-between;">
                    <span>Monthly Present Members & Fee Status</span>
                    <span id="print_mob_badge_mem_count" style="font-size:8pt; border:1px solid #cbd5e1; padding:2px 6px; background:#f8fafc;">0 Members</span>
                </div>
                <table class="table-print">
                    <thead>
                        <tr>
                            <th style="width:5%; text-align:center;">#</th>
                            <th style="width:33%;">Member Name</th>
                            <th style="width:20%;">Group(s)</th>
                            <th style="width:14%; text-align:center;">Sessions</th>
                            <th style="width:16%; text-align:right;">Fee Receivable (₹)</th>
                            <th style="width:12%; text-align:center;">Status</th>
                        </tr>
                    </thead>
                    <tbody id="print_mob_mem_tbody">
                        <!-- Dynamic Print Rows -->
                    </tbody>
                    <tfoot id="print_mob_mem_tfoot" style="display:none;">
                        <tr>
                            <td colspan="3" style="text-align:right; font-weight:800;">TOTALS:</td>
                            <td style="text-align:center; font-weight:800;" id="print_mob_foot_mem_att">0</td>
                            <td style="text-align:right; color:#4f46e5; font-weight:800;" id="print_mob_foot_mem_rec">₹ 0.00</td>
                            <td style="text-align:center; font-size:7pt; font-weight:700;" id="print_mob_foot_mem_summary">—</td>
                        </tr>
                    </tfoot>
                </table>
            </div>

            <!-- 7. Print Signatures Block -->
            <div class="print-sig-block">
                <div class="print-sig-col">
                    <div class="print-sig-line"></div>
                    <div class="print-sig-title">Prepared By (Treasurer)</div>
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

        <!-- Screen Hero Banner -->
        <div class="rep-hero">
            <h2>Financial & Operations Report</h2>
            <p id="hero_subtitle">Monthly Income, Expenses & Operations</p>
        </div>

        <!-- Filter Box -->
        <div class="rep-filter-box">
            <div class="rep-field">
                <label>Select Month</label>
                <input type="month" id="rep_month" value="<?php echo date('Y-m'); ?>" onchange="loadReport()">
            </div>
            <div class="rep-field" id="rep_group_container">
                <label>Select Group</label>
                <select id="rep_group" onchange="loadReport()"></select>
            </div>
            <button type="button" class="btn-mob-print" onclick="window.print()">
                <i class="fa fa-print"></i> Print Report / PDF
            </button>
        </div>

        <!-- Screen Stat Grid -->
        <div class="stat-card-grid">
            <!-- 1. Total Fee Receivable -->
            <div class="stat-box" style="border-left: 4px solid #4f46e5;">
                <div class="stat-lbl">Fee Receivable</div>
                <div class="stat-val" id="lbl_fee_receivable" style="color:#4338ca;">₹0.00</div>
                <div class="stat-sub" id="lbl_sub_fee_rec">0 Members Billed</div>
                <i class="fa fa-calculator stat-icon" style="color:#4f46e5;"></i>
            </div>

            <!-- 2. Total Income -->
            <div class="stat-box" style="border-left: 4px solid #10b981;">
                <div class="stat-lbl">Total Collected</div>
                <div class="stat-val" id="lbl_total_income" style="color:#059669;">₹0.00</div>
                <div class="stat-sub" id="lbl_sub_income">0 Collections</div>
                <i class="fa fa-money stat-icon" style="color:#10b981;"></i>
            </div>

            <!-- 3. Total Expenses -->
            <div class="stat-box" style="border-left: 4px solid #ef4444;">
                <div class="stat-lbl">Total Expenses</div>
                <div class="stat-val" id="lbl_total_expense" style="color:#dc2626;">₹0.00</div>
                <div class="stat-sub" id="lbl_sub_expense">0 Vouchers</div>
                <i class="fa fa-credit-card stat-icon" style="color:#ef4444;"></i>
            </div>

            <!-- 4. Member Monthly Fees -->
            <div class="stat-box" style="border-left: 4px solid #3b82f6;">
                <div class="stat-lbl">Member Fees</div>
                <div class="stat-val" id="lbl_monthly_received" style="color:#2563eb;">₹0.00</div>
                <div class="stat-sub" id="lbl_sub_member_fee">0 Receipts</div>
                <i class="fa fa-users stat-icon" style="color:#3b82f6;"></i>
            </div>

            <!-- 5. Guest Fees -->
            <div class="stat-box" style="border-left: 4px solid #f59e0b;">
                <div class="stat-lbl">Guest Fees</div>
                <div class="stat-val" id="lbl_guest_fees" style="color:#d97706;">₹0.00</div>
                <div class="stat-sub" id="lbl_sub_guest">0 Receipts</div>
                <i class="fa fa-user-plus stat-icon" style="color:#f59e0b;"></i>
            </div>

            <!-- 6. Present Members -->
            <div class="stat-box" style="border-left: 4px solid #ec4899;">
                <div class="stat-lbl">Present Members</div>
                <div class="stat-val" id="lbl_member_count">0 Members</div>
                <div class="stat-sub" id="lbl_sub_mem_att">Active This Month</div>
                <i class="fa fa-check-square-o stat-icon" style="color:#ec4899;"></i>
            </div>

            <!-- 7. Sessions Held -->
            <div class="stat-box" style="border-left: 4px solid #6366f1;">
                <div class="stat-lbl">Sessions Held</div>
                <div class="stat-val" id="lbl_total_sessions">0 Sessions</div>
                <div class="stat-sub" id="lbl_sub_sessions">0 Check-in Marks</div>
                <i class="fa fa-clock-o stat-icon" style="color:#6366f1;"></i>
            </div>

            <!-- 8. Shuttles Used & Cost -->
            <div class="stat-box" style="border-left: 4px solid #06b6d4;">
                <div class="stat-lbl">Shuttles Used & Cost</div>
                <div class="stat-val" id="lbl_coke_cost" style="color:#0891b2; font-size:16px;">₹0.00</div>
                <div class="stat-sub" id="lbl_cokes_used">0 Shuttles Used</div>
                <i class="fa fa-shopping-cart stat-icon" style="color:#06b6d4;"></i>
            </div>
        </div>

        <!-- Segmented Tab Navigation -->
        <div class="seg-nav">
            <button type="button" class="seg-btn active" id="btn_tab_income" onclick="switchTab('income')">
                <i class="fa fa-money"></i> Income <span class="seg-badge" id="badge_inc_count">0</span>
            </button>
            <button type="button" class="seg-btn" id="btn_tab_expenses" onclick="switchTab('expenses')">
                <i class="fa fa-credit-card"></i> Expenses <span class="seg-badge" id="badge_exp_count">0</span>
            </button>
            <button type="button" class="seg-btn" id="btn_tab_members" onclick="switchTab('members')">
                <i class="fa fa-users"></i> Members <span class="seg-badge" id="badge_mem_count">0</span>
            </button>
        </div>

        <!-- Screen Section 1: Income Received & Collections -->
        <div id="section_income" class="mob-tbl-card">
            <div class="mob-tbl-header">
                <span><i class="fa fa-money" style="color:#10b981;"></i> Income & Collections</span>
                <span class="mob-badge mob-badge-inc" id="mob_badge_income_total">₹0.00</span>
            </div>
            <div style="overflow-x:auto;">
                <table class="table-mob">
                    <thead>
                        <tr>
                            <th>Who Paid & Description</th>
                            <th style="text-align:right;">Amount (₹)</th>
                        </tr>
                    </thead>
                    <tbody id="tbl_income_body">
                        <tr><td colspan="2" class="rep-empty-mob">Loading...</td></tr>
                    </tbody>
                    <tfoot id="tbl_income_foot" style="display:none;">
                        <tr>
                            <td>Total Collected:</td>
                            <td style="text-align:right; color:#059669;" id="mob_foot_inc_total">₹0.00</td>
                        </tr>
                    </tfoot>
                </table>
            </div>
        </div>

        <!-- Screen Section 2: Expenses -->
        <div id="section_expenses" class="mob-tbl-card" style="display:none;">
            <div class="mob-tbl-header">
                <span><i class="fa fa-credit-card" style="color:#ef4444;"></i> Monthly Expenses</span>
                <span class="mob-badge mob-badge-exp" id="mob_badge_expense_total">₹0.00</span>
            </div>
            <div style="overflow-x:auto;">
                <table class="table-mob">
                    <thead>
                        <tr>
                            <th>Particulars / Head</th>
                            <th style="text-align:right;">Amount (₹)</th>
                        </tr>
                    </thead>
                    <tbody id="tbl_expenses_body">
                        <tr><td colspan="2" class="rep-empty-mob">Loading...</td></tr>
                    </tbody>
                    <tfoot id="tbl_expenses_foot" style="display:none;">
                        <tr>
                            <td>Total Expenses:</td>
                            <td style="text-align:right; color:#dc2626;" id="mob_foot_exp_total">₹0.00</td>
                        </tr>
                    </tfoot>
                </table>
            </div>
        </div>

        <!-- Screen Section 3: Members Attendance & Fee Status -->
        <div id="section_members" class="mob-tbl-card" style="display:none;">
            <div class="mob-tbl-header">
                <span><i class="fa fa-users" style="color:#3b82f6;"></i> Monthly Present Members</span>
                <span class="mob-badge" style="background:#e0e7ff; color:#4338ca;" id="mob_badge_mem_count">0 Members</span>
            </div>
            <div style="overflow-x:auto;">
                <table class="table-mob">
                    <thead>
                        <tr>
                            <th>Member & Group</th>
                            <th style="text-align:center;">Sessions</th>
                            <th style="text-align:right;">Fee Receivable</th>
                        </tr>
                    </thead>
                    <tbody id="tbl_members_body">
                        <tr><td colspan="3" class="rep-empty-mob">Loading...</td></tr>
                    </tbody>
                    <tfoot id="tbl_members_foot" style="display:none;">
                        <tr>
                            <td>Totals:</td>
                            <td style="text-align:center; font-weight:800;" id="mob_foot_mem_att">0</td>
                            <td style="text-align:right; color:#4f46e5; font-weight:800;" id="mob_foot_mem_rec">₹0.00</td>
                        </tr>
                    </tfoot>
                </table>
            </div>
        </div>

    </div>

    <!-- Mobile Bottom Navigation (5 Tabs) -->
    <?php include 'mobile_bottom_nav.php'; ?>

    <script src="../../js/jquery-3.1.1.min.js"></script>
    <script src="../../js/bootstrap.min.js"></script>
    <script>
    $(document).ready(function() {
        $.post('../api/attendance.php', { action: 'load_groups' }, function(data) {
            try {
                let parsed = typeof data === 'string' ? JSON.parse(data) : data;
                let groups = Array.isArray(parsed[0]) ? parsed[0] : (Array.isArray(parsed) ? parsed : []);
                let htm = groups.length > 1 ? '<option value="0">All Groups</option>' : '';
                groups.forEach(function(g) { htm += '<option value="' + g.id + '">' + g.name + '</option>'; });
                $('#rep_group').html(htm);
                if (groups.length <= 1) {
                    $('#rep_group_container').hide();
                    if (groups.length === 1) {
                        $('#rep_group').val(groups[0].id);
                    }
                } else {
                    $('#rep_group_container').show();
                }
                loadReport();
            } catch(e) {}
        });
    });

    function formatCurr(num) {
        return '₹' + parseFloat(num || 0).toLocaleString('en-IN', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
    }

    function formatDateMob(dateStr) {
        if (!dateStr || dateStr === '0000-00-00') return '—';
        var parts = dateStr.split('-');
        if (parts.length === 3) {
            var d = new Date(parts[0], parts[1] - 1, parts[2]);
            if (!isNaN(d.getTime())) {
                return d.toLocaleDateString('en-IN', { day: '2-digit', month: 'short' });
            }
        }
        return dateStr;
    }

    function formatDateFull(dateStr) {
        if (!dateStr || dateStr === '0000-00-00') return '—';
        var parts = dateStr.split('-');
        if (parts.length === 3) {
            var d = new Date(parts[0], parts[1] - 1, parts[2]);
            if (!isNaN(d.getTime())) {
                return d.toLocaleDateString('en-IN', { day: '2-digit', month: 'short', year: 'numeric' });
            }
        }
        return dateStr;
    }

    function openBillInvoiceMob(fileName) {
        if (!fileName) return;
        var path = "../../image_upload/payments/" + encodeURIComponent(fileName);
        if (fileName.indexOf('shuttle_bill_') === 0) {
            path = "../../image_upload/shuttle_cock_bills/" + encodeURIComponent(fileName);
        }
        window.open(path, "_blank");
    }

    function switchTab(tabKey) {
        $('.seg-btn').removeClass('active');
        $('#btn_tab_' + tabKey).addClass('active');

        $('#section_income').hide();
        $('#section_expenses').hide();
        $('#section_members').hide();

        $('#section_' + tabKey).show();
    }

    function loadReport() {
        let month = $('#rep_month').val();
        let group_id = $('#rep_group').val();
        if (!month) return;

        $('#tbl_income_body').html('<tr><td colspan="2" class="rep-empty-mob"><i class="fa fa-spinner fa-spin"></i> Loading...</td></tr>');
        $('#tbl_expenses_body').html('<tr><td colspan="2" class="rep-empty-mob"><i class="fa fa-spinner fa-spin"></i> Loading...</td></tr>');
        $('#tbl_members_body').html('<tr><td colspan="2" class="rep-empty-mob"><i class="fa fa-spinner fa-spin"></i> Loading...</td></tr>');

        $.post('../api/monthly_financial_report.php', { action: 'load_report', month: month, group_id: group_id }, function(res) {
            try {
                let data = typeof res === 'string' ? JSON.parse(res) : res;
                let summary = data.summary || {};
                let incomeItems = data.income_items || [];
                let expenses = data.expense_items || [];
                let members = data.members || [];

                // Subtitle & Metadata
                let d = new Date(month + '-01');
                let monthName = d.toLocaleString('default', { month: 'long', year: 'numeric' });
                let groupText = $('#rep_group option:selected').text() || 'All Groups';
                let genTimeStr = new Date().toLocaleDateString('en-IN', { day: '2-digit', month: 'short', year: 'numeric', hour: '2-digit', minute: '2-digit', hour12: true });

                $('#hero_subtitle').text(monthName + ' • ' + groupText);

                // Populate Print Metadata
                $('#print_mob_month').text(monthName);
                $('#print_mob_group').text(groupText);
                $('#print_mob_date').text(genTimeStr);
                $('#print_mob_sessions').text((summary.total_sessions || 0) + ' Sessions (' + (summary.total_attendance_marks || 0) + ' Marks)');

                // Screen Stat Cards
                $('#lbl_fee_receivable').text(formatCurr(summary.total_fee_receivable));
                var feeMobSub = (summary.fee_receivable_count || 0) + ' Members Billed';
                if (summary.fee_pending_for_month > 0) {
                    feeMobSub += ' • Pend: ' + formatCurr(summary.fee_pending_for_month);
                } else if (summary.total_fee_receivable > 0) {
                    feeMobSub += ' • Settled';
                }
                if (summary.is_fee_estimated) {
                    feeMobSub += ' (Est)';
                }
                $('#lbl_sub_fee_rec').text(feeMobSub);

                $('#lbl_total_income').text(formatCurr(summary.total_income));
                $('#lbl_sub_income').text((summary.income_count || 0) + ' Collections');

                $('#lbl_total_expense').text(formatCurr(summary.total_expense));
                $('#lbl_sub_expense').text((summary.expense_count || 0) + ' Vouchers');

                $('#lbl_monthly_received').text(formatCurr(summary.member_fees_total));
                $('#lbl_sub_member_fee').text((summary.member_fees_count || 0) + ' Receipts');

                $('#lbl_guest_fees').text(formatCurr(summary.guest_fee_total));
                $('#lbl_sub_guest').text((summary.guest_fee_count || 0) + ' Receipts');

                $('#lbl_member_count').text((summary.member_count || 0) + ' Members');
                $('#lbl_sub_mem_att').text('Active This Month');

                $('#lbl_total_sessions').text((summary.total_sessions || 0) + ' Sessions');
                $('#lbl_sub_sessions').text((summary.total_attendance_marks || 0) + ' Check-in Marks');

                // Shuttles Used & Cost
                $('#lbl_coke_cost').text(formatCurr(summary.shuttle_total_cost));
                $('#lbl_cokes_used').text((summary.shuttle_total_used || 0) + ' Shuttles Used');

                // Populate Print KPI Summary (5 cells)
                $('#print_mob_kpi_receivable').text(formatCurr(summary.total_fee_receivable));
                $('#print_mob_kpi_rec_sub').text((summary.fee_receivable_count || 0) + ' Members Billed');
                $('#print_mob_kpi_income').text(formatCurr(summary.total_income));
                $('#print_mob_kpi_inc_sub').text((summary.income_count || 0) + ' Collections');
                $('#print_mob_kpi_expense').text(formatCurr(summary.total_expense));
                $('#print_mob_kpi_exp_sub').text((summary.expense_count || 0) + ' Vouchers Paid');
                $('#print_mob_kpi_shuttle').text(formatCurr(summary.shuttle_total_cost));
                $('#print_mob_kpi_shuttle_sub').text((summary.shuttle_total_used || 0) + ' Shuttles Used');
                $('#print_mob_kpi_members').text((summary.member_count || 0) + ' Active');
                $('#print_mob_kpi_mem_sub').text((summary.total_attendance_marks || 0) + ' Check-ins');

                // Badge Counts on Screen Tabs
                $('#badge_inc_count').text(incomeItems.length);
                $('#badge_exp_count').text(expenses.length);
                $('#badge_mem_count').text(members.length);

                // Section 1: Income Collections Table (Screen & Print)
                let incHtm = '';
                let printIncHtm = '';
                let incTotal = 0;
                if (incomeItems.length > 0) {
                    incomeItems.forEach(function(item, idx) {
                        incTotal += parseFloat(item.amount || 0);
                        let isGuest = (item.category || '').toLowerCase().indexOf('guest') !== -1;
                        let badgeClass = isGuest ? 'mob-badge-guest' : 'mob-badge-inc';

                        // Screen row
                        incHtm += `<tr>
                            <td>
                                <div style="display:flex; align-items:center; gap:6px; margin-bottom:2px;">
                                    <span style="font-size:10px; color:#64748b; font-weight:700;">${formatDateMob(item.date)}</span>
                                    <span class="mob-badge ${badgeClass}" style="font-size:10px;">${item.category}</span>
                                </div>
                                <div style="font-weight:700; color:#0f172a; font-size:12.5px;">${item.payer_name}</div>
                                <div style="font-size:11px; color:#475569; margin-top:2px;">
                                    ${item.description || '—'} &bull; <span style="color:#64748b;">${item.group_name}</span>
                                </div>
                            </td>
                            <td style="text-align:right; font-weight:800; color:#059669; vertical-align:top; padding-top:12px;">
                                ${formatCurr(item.amount)}
                            </td>
                        </tr>`;

                        // Print row
                        printIncHtm += `<tr>
                            <td style="text-align:center; color:#64748b; font-weight:600;">${idx + 1}</td>
                            <td style="font-weight:600; white-space:nowrap;">${formatDateFull(item.date)}</td>
                            <td style="font-weight:700;">${item.payer_name}</td>
                            <td><span class="head-badge">${item.category}</span></td>
                            <td>${item.description || '—'}</td>
                            <td>${item.group_name || 'General'}</td>
                            <td style="text-align:right; font-weight:700; color:#059669;">${formatCurr(item.amount)}</td>
                        </tr>`;
                    });

                    $('#tbl_income_body').html(incHtm);
                    $('#mob_foot_inc_total').text(formatCurr(incTotal));
                    $('#tbl_income_foot').show();

                    $('#print_mob_inc_tbody').html(printIncHtm);
                    $('#print_mob_foot_inc_total').text(formatCurr(incTotal));
                    $('#print_mob_inc_tfoot').show();
                } else {
                    $('#tbl_income_body').html('<tr><td colspan="2" class="rep-empty-mob"><i class="fa fa-money"></i>No income recorded.</td></tr>');
                    $('#tbl_income_foot').hide();

                    $('#print_mob_inc_tbody').html('<tr><td colspan="7" style="text-align:center; padding:15px; color:#94a3b8;">No income recorded for this month.</td></tr>');
                    $('#print_mob_inc_tfoot').hide();
                }
                $('#mob_badge_income_total').text(formatCurr(incTotal));
                $('#print_mob_badge_inc_total').text('Total: ' + formatCurr(incTotal));

                // Section 2: Expenses Table (Screen & Print)
                let expHtm = '';
                let printExpHtm = '';
                let expTotal = 0;
                if (expenses.length > 0) {
                    expenses.forEach(function(e, i) {
                        expTotal += parseFloat(e.amount || 0);
                        let billLink = e.bill_photo 
                            ? `<button type="button" class="btn-mob-bill" onclick="openBillInvoiceMob('${e.bill_photo}')"><i class="fa fa-file-text-o"></i> Bill</button>`
                            : '';
                        
                        // Screen row
                        expHtm += `<tr>
                            <td>
                                <div style="display:flex; align-items:center; gap:6px; margin-bottom:2px;">
                                    <span style="font-size:10px; color:#64748b; font-weight:700;">${formatDateMob(e.date)}</span>
                                    <span class="mob-badge mob-badge-exp" style="font-size:10px;">${e.head_name}</span>
                                </div>
                                <div style="font-weight:700; color:#0f172a; font-size:12px;">${e.particulars || 'Expense'}</div>
                                <div style="display:flex; align-items:center; gap:8px; margin-top:4px;">
                                    <span style="font-size:10.5px; color:#64748b;">${e.group_name || 'General'}</span>
                                    ${billLink}
                                </div>
                            </td>
                            <td style="text-align:right; font-weight:800; color:#dc2626; vertical-align:top; padding-top:12px;">
                                ${formatCurr(e.amount)}
                            </td>
                        </tr>`;

                        // Print row
                        printExpHtm += `<tr>
                            <td style="text-align:center; color:#64748b; font-weight:600;">${i + 1}</td>
                            <td style="font-weight:600; white-space:nowrap;">${formatDateFull(e.date)}</td>
                            <td><span class="head-badge">${e.head_name}</span></td>
                            <td>${e.particulars || '—'}</td>
                            <td>${e.group_name || 'General'}</td>
                            <td style="text-align:right; font-weight:700; color:#dc2626;">${formatCurr(e.amount)}</td>
                        </tr>`;
                    });

                    $('#tbl_expenses_body').html(expHtm);
                    $('#mob_foot_exp_total').text(formatCurr(expTotal));
                    $('#tbl_expenses_foot').show();

                    $('#print_mob_exp_tbody').html(printExpHtm);
                    $('#print_mob_foot_exp_total').text(formatCurr(expTotal));
                    $('#print_mob_exp_tfoot').show();
                } else {
                    $('#tbl_expenses_body').html('<tr><td colspan="2" class="rep-empty-mob"><i class="fa fa-folder-open-o"></i>No expenses recorded.</td></tr>');
                    $('#tbl_expenses_foot').hide();

                    $('#print_mob_exp_tbody').html('<tr><td colspan="6" style="text-align:center; padding:15px; color:#94a3b8;">No expenses recorded for this month.</td></tr>');
                    $('#print_mob_exp_tfoot').hide();
                }
                $('#mob_badge_expense_total').text(formatCurr(expTotal));
                $('#print_mob_badge_exp_total').text('Total: ' + formatCurr(expTotal));

                // Section 3: Members Attendance & Fee Status Table (Screen & Print)
                let memHtm = '';
                let printMemHtm = '';
                let totalAtt = 0;
                let totalRec = 0;
                let totalPaid = 0;
                if (members.length > 0) {
                    members.forEach(function(m, k) {
                        let attVal = parseInt(m.attendance || 0);
                        let recVal = parseFloat(m.fee_receivable || 0);
                        let paidVal = parseFloat(m.fee_paid || 0);
                        totalAtt += attVal;
                        totalRec += recVal;
                        totalPaid += paidVal;

                        let statusBadge = '';
                        let printStatusBadge = '';
                        if (m.fee_status === 'Paid') {
                            statusBadge = '<span class="mob-badge mob-badge-inc" style="font-size:10px;">Paid</span>';
                            printStatusBadge = '<span class="head-badge" style="background:#dcfce7; color:#15803d;">Paid</span>';
                        } else if (m.fee_status === 'Partial') {
                            statusBadge = '<span class="mob-badge mob-badge-guest" style="font-size:10px;">Paid ' + formatCurr(paidVal) + '</span>';
                            printStatusBadge = '<span class="head-badge" style="background:#fef3c7; color:#b45309;">Paid ' + formatCurr(paidVal) + '</span>';
                        } else if (m.fee_status === 'Exempt') {
                            statusBadge = '<span class="mob-badge" style="background:#f1f5f9; color:#64748b; font-size:10px;">Exempt</span>';
                            printStatusBadge = '<span class="head-badge">Exempt</span>';
                        } else {
                            statusBadge = '<span class="mob-badge mob-badge-exp" style="font-size:10px;">Pending</span>';
                            printStatusBadge = '<span class="head-badge" style="background:#fee2e2; color:#b91c1c;">Pending</span>';
                        }

                        // Screen row
                        memHtm += `<tr>
                            <td>
                                <strong style="color:#0f172a; font-size:12.5px;">${m.name}</strong>
                                <div style="font-size:10.5px; color:#64748b; margin-top:1px;">${m.group_names || 'General'}</div>
                            </td>
                            <td style="text-align:center; vertical-align:middle;">
                                <span class="mob-badge" style="background:#e0e7ff; color:#4338ca; font-size:11px; font-weight:700;">
                                    ${attVal}
                                </span>
                            </td>
                            <td style="text-align:right; vertical-align:middle;">
                                <div style="font-weight:800; color:#4338ca; font-size:12.5px;">${formatCurr(recVal)}</div>
                                <div style="margin-top:2px;">${statusBadge}</div>
                            </td>
                        </tr>`;

                        // Print row
                        printMemHtm += `<tr>
                            <td style="text-align:center; color:#64748b; font-weight:600;">${k + 1}</td>
                            <td style="font-weight:700;">${m.name}</td>
                            <td>${m.group_names || 'General'}</td>
                            <td style="text-align:center; font-weight:800;">${attVal}</td>
                            <td style="text-align:right; font-weight:700; color:#4338ca;">${formatCurr(recVal)}</td>
                            <td style="text-align:center;">${printStatusBadge}</td>
                        </tr>`;
                    });

                    $('#tbl_members_body').html(memHtm);
                    $('#mob_foot_mem_att').text(totalAtt);
                    $('#mob_foot_mem_rec').text(formatCurr(totalRec));
                    $('#tbl_members_foot').show();

                    $('#print_mob_mem_tbody').html(printMemHtm);
                    $('#print_mob_foot_mem_att').text(totalAtt);
                    $('#print_mob_foot_mem_rec').text(formatCurr(totalRec));
                    $('#print_mob_foot_mem_summary').text(formatCurr(totalPaid) + ' Paid');
                    $('#print_mob_mem_tfoot').show();
                } else {
                    $('#tbl_members_body').html('<tr><td colspan="3" class="rep-empty-mob"><i class="fa fa-users"></i>No members present.</td></tr>');
                    $('#tbl_members_foot').hide();

                    $('#print_mob_mem_tbody').html('<tr><td colspan="6" style="text-align:center; padding:15px; color:#94a3b8;">No members were present for this month.</td></tr>');
                    $('#print_mob_mem_tfoot').hide();
                }
                $('#mob_badge_mem_count').text(members.length + ' Members (' + formatCurr(totalRec) + ')');
                $('#print_mob_badge_mem_count').text(members.length + ' Members • Receivable: ' + formatCurr(totalRec));

            } catch(e) {
                $('#tbl_income_body').html('<tr><td colspan="2" class="rep-empty-mob" style="color:#ef4444;">Error loading report data.</td></tr>');
            }
        });
    }
    </script>
</body>
</html>
