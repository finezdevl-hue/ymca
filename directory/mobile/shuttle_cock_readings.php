<?php
session_start();
include '../../app_common/db_connect.php';
include '../../app_common/auth_helper.php';

if (empty($_SESSION['login_id'])) {
    header("Location: ../../index.php");
    exit();
}

$login_id = (int)$_SESSION['login_id'];
$is_admin = isSuperAdmin($login_id) || isGroupAdmin($login_id);
$allowed_groups = getUserAllowedGroupIds($login_id);
$primary_role = getUserPrimaryRoleName($login_id);

$active_tab = 'accounts';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
    <title>Shuttle Cock Readings - YMCA Mobile</title>
    
    <link href="../../css/bootstrap.min.css" rel="stylesheet">
    <link href="../../font-awesome/css/font-awesome.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <link href="../../css/plugins/sweetalert/sweetalert.css" rel="stylesheet">
    <link href="mobile_layout.css" rel="stylesheet">

    <style>
        body { font-family: 'Plus Jakarta Sans', sans-serif !important; background: #f8fafc !important; }
        .acc-hero {
            background: linear-gradient(135deg, #059669 0%, #10b981 100%);
            border-radius: 20px; padding: 18px 20px; color: #ffffff; margin-bottom: 16px;
            box-shadow: 0 8px 20px rgba(16, 185, 129, 0.2);
        }
        .acc-hero h2 { margin: 0 0 4px 0; font-weight: 800; font-size: 20px; }
        .acc-hero p { margin: 0; font-size: 12.5px; opacity: 0.9; }

        .kpi-grid { display: grid; grid-template-columns: repeat(3, 1fr); gap: 10px; margin-bottom: 16px; }
        .kpi-card-sm {
            background: #ffffff; border-radius: 14px; border: 1px solid #e2e8f0; padding: 10px 12px;
            box-shadow: 0 2px 6px rgba(0,0,0,0.02); text-align: left;
        }
        .kpi-lbl { font-size: 9.5px; font-weight: 800; color: #64748b; text-transform: uppercase; letter-spacing: 0.3px; }
        .kpi-val { font-size: 14px; font-weight: 800; color: #0f172a; margin-top: 2px; }

        .filter-select {
            width: 100%; height: 42px; border-radius: 12px; border: 1px solid #e2e8f0;
            padding: 0 14px; font-size: 13px; font-weight: 700; color: #1e293b;
            background: #ffffff; margin-bottom: 14px; outline: none; box-shadow: 0 2px 6px rgba(0,0,0,0.02);
        }

        .rd-card {
            background: #ffffff; border-radius: 16px; border: 1px solid #e2e8f0; padding: 14px 16px;
            margin-bottom: 10px; box-shadow: 0 2px 6px rgba(0,0,0,0.02);
            transition: transform 0.15s ease;
        }
        .rd-card:active { transform: scale(0.99); }
        .rd-header { display: flex; align-items: center; justify-content: space-between; margin-bottom: 8px; }
        .rd-date { font-size: 11px; font-weight: 700; color: #64748b; text-transform: uppercase; }
        .rd-badge { font-size: 10px; font-weight: 800; padding: 3px 8px; border-radius: 6px; text-transform: uppercase; background: #e0e7ff; color: #3730a3; }

        .rd-body { display: flex; align-items: center; gap: 12px; }
        .rd-icon-box {
            width: 42px; height: 42px; border-radius: 12px; background: #fef2f2; color: #dc2626;
            display: flex; align-items: center; justify-content: center; font-size: 18px; font-weight: 800; flex-shrink: 0;
        }
        .rd-info { flex: 1; min-width: 0; }
        .rd-title { font-size: 14px; font-weight: 800; color: #0f172a; margin: 0; white-space: nowrap; overflow: hidden; text-overflow: ellipsis; }
        .rd-meta { font-size: 11.5px; color: #64748b; font-weight: 600; margin-top: 2px; }
        .rd-amt { font-size: 16px; font-weight: 800; color: #dc2626; text-align: right; white-space: nowrap; }

        .rd-footer {
            margin-top: 10px; padding-top: 10px; border-top: 1px dashed #f1f5f9;
            display: flex; align-items: center; justify-content: space-between; font-size: 11.5px; color: #64748b; font-weight: 600;
        }
        .btn-del-sm {
            background: #fef2f2; color: #dc2626; border: none; border-radius: 6px; padding: 4px 10px; font-size: 11px; font-weight: 800; cursor: pointer;
        }

        .bd-card {
            background: #ffffff; border-radius: 16px; border: 1px solid #e2e8f0; padding: 14px 16px; margin-bottom: 16px;
        }
        .bd-title { font-size: 13px; font-weight: 800; color: #0f172a; margin-bottom: 10px; display: flex; align-items: center; justify-content: space-between; }
        .bd-table { width: 100%; font-size: 12px; border-collapse: collapse; }
        .bd-table th { font-weight: 800; color: #64748b; padding: 6px 4px; border-bottom: 1px solid #e2e8f0; text-align: left; }
        .bd-table td { padding: 8px 4px; border-bottom: 1px dashed #f1f5f9; font-weight: 600; color: #1e293b; }

        .pagination-container { display: flex; justify-content: center; gap: 6px; margin-top: 14px; margin-bottom: 20px; }
        .page-btn { padding: 6px 12px; border-radius: 8px; border: 1px solid #cbd5e1; background: #fff; color: #334155; font-weight: 700; font-size: 12px; cursor: pointer; }
        .page-btn.active { background: #059669; color: #fff; border-color: #059669; }

        /* Print Modal & Preview Box */
        .print-preview-box {
            background: #ffffff;
            border: 1px solid #e2e8f0;
            border-radius: 14px;
            padding: 16px;
            max-height: 62vh;
            overflow-y: auto;
            overflow-x: auto;
            -webkit-overflow-scrolling: touch;
        }

        /* Container hidden on screen, exclusively active when printing */
        #printable_readings_container {
            display: none;
        }

        /* ================= PRINT MEDIA STYLES ================= */
        @media print {
            @page {
                size: A4 portrait;
                margin: 8mm 8mm 10mm 8mm;
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
                font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, Arial, sans-serif !important;
                font-size: 8.5pt !important;
                width: 100% !important;
            }
            /* Strictly hide all mobile interface elements */
            body > *:not(#printable_readings_container) {
                display: none !important;
                height: 0 !important;
                overflow: hidden !important;
            }
            #printable_readings_container {
                display: block !important;
                position: static !important;
                width: 100% !important;
                margin: 0 !important;
                padding: 0 !important;
                background: #ffffff !important;
            }
            .prt-header {
                text-align: center;
                padding-bottom: 8px;
                border-bottom: 2px solid #059669;
                margin-bottom: 12px;
            }
            .prt-title-main {
                font-size: 15pt;
                font-weight: 800;
                color: #065f46;
                letter-spacing: 0.5px;
                text-transform: uppercase;
                margin: 0 0 2px 0;
            }
            .prt-subtitle {
                font-size: 8pt;
                color: #64748b;
                margin-top: 1px;
            }
            .prt-badge-bar {
                background-color: #059669 !important;
                color: #ffffff !important;
                font-weight: 700;
                font-size: 9.5pt;
                padding: 4px 8px;
                margin-top: 6px;
                letter-spacing: 0.5px;
                text-transform: uppercase;
                -webkit-print-color-adjust: exact !important;
                print-color-adjust: exact !important;
            }
            .prt-period-bar {
                font-size: 8pt;
                color: #475569;
                margin-top: 5px;
                display: flex;
                justify-content: space-between;
                font-weight: 600;
            }
            .prt-kpi-grid {
                display: flex;
                gap: 8px;
                margin-bottom: 12px;
            }
            .prt-kpi-card {
                flex: 1;
                border: 1px solid #cbd5e1;
                border-left: 3.5px solid #059669;
                border-radius: 6px;
                padding: 6px 10px;
                background: #f8fafc !important;
                -webkit-print-color-adjust: exact !important;
                print-color-adjust: exact !important;
            }
            .prt-kpi-lbl {
                font-size: 6.5pt;
                font-weight: 800;
                color: #475569;
                text-transform: uppercase;
            }
            .prt-kpi-val {
                font-size: 11pt;
                font-weight: 800;
                color: #0f172a;
            }
            .prt-section-title {
                font-size: 8pt;
                font-weight: 800;
                color: #0f172a;
                text-transform: uppercase;
                letter-spacing: 0.3px;
                margin: 10px 0 4px 0;
                padding-bottom: 2px;
                border-bottom: 1px solid #cbd5e1;
            }
            .prt-table {
                width: 100% !important;
                border-collapse: collapse !important;
                font-size: 7.5pt !important;
                margin-bottom: 10px !important;
            }
            .prt-table th {
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
            .prt-table td {
                padding: 4px 6px !important;
                font-size: 7.5pt !important;
                color: #0f172a !important;
                border: 1px solid #cbd5e1 !important;
            }
            .prt-table tr {
                page-break-inside: avoid !important;
                break-inside: avoid !important;
            }
            .prt-table tfoot td {
                font-weight: 800 !important;
                background: #f1f5f9 !important;
                -webkit-print-color-adjust: exact !important;
                print-color-adjust: exact !important;
            }
            .prt-sig-block {
                display: flex !important;
                justify-content: space-between !important;
                margin-top: 25px !important;
                padding-top: 8px !important;
                page-break-inside: avoid !important;
                break-inside: avoid !important;
            }
            .prt-sig-col {
                width: 28% !important;
                text-align: center !important;
            }
            .prt-sig-line {
                border-top: 1.2px solid #475569 !important;
                margin-bottom: 4px !important;
            }
            .prt-sig-title {
                font-size: 7.5pt !important;
                font-weight: 700 !important;
                color: #334155 !important;
            }
        }
    </style>
</head>
<body class="mob-body">

    <!-- Header -->
    <header class="mob-header">
        <div class="mob-header-brand">
            <a href="accounts.php" style="color:#ffffff; margin-right:8px; font-size:18px;"><i class="fa fa-arrow-left"></i></a>
            <div class="mob-header-title">
                Shuttle Cock <span>Readings</span>
            </div>
        </div>
        <div class="mob-header-actions">
            <button type="button" class="mob-header-btn" onclick="openPrintModal()" title="Print Statement"><i class="fa fa-print"></i></button>
            <a href="../../app_login_manager/logout.php" class="mob-header-btn" title="Logout"><i class="fa fa-sign-out"></i></a>
        </div>
    </header>

    <div class="mob-page">

        <div class="acc-hero">
            <div style="display:flex; justify-content:space-between; align-items:center; gap:8px;">
                <div>
                    <h2>Shuttle Cock Readings</h2>
                    <p>Session shuttle count & consumption log</p>
                </div>
                <div style="display:flex; gap:6px; flex-shrink:0;">
                    <button type="button" class="btn btn-light btn-sm" style="border-radius:12px; font-weight:800; color:#1e293b; padding:8px 12px; box-shadow: 0 4px 10px rgba(0,0,0,0.1);" onclick="openPrintModal()">
                        <i class="fa fa-print"></i> Print
                    </button>
                    <button type="button" class="btn btn-light btn-sm" style="border-radius:12px; font-weight:800; color:#059669; padding:8px 12px; box-shadow: 0 4px 10px rgba(0,0,0,0.1);" data-toggle="modal" data-target="#addReadingModal">
                        <i class="fa fa-plus-circle"></i> Record
                    </button>
                </div>
            </div>
        </div>

        <!-- KPI Summary Strip -->
        <div class="kpi-grid">
            <div class="kpi-card-sm" style="border-left: 3px solid #3b82f6;">
                <div class="kpi-lbl">Stock Balance</div>
                <div class="kpi-val" style="color:#2563eb;" id="kpi_balance">0</div>
            </div>
            <div class="kpi-card-sm" style="border-left: 3px solid #ef4444;">
                <div class="kpi-lbl">Shuttles Used</div>
                <div class="kpi-val" style="color:#dc2626;" id="kpi_used">0</div>
            </div>
            <div class="kpi-card-sm" style="border-left: 3px solid #8b5cf6;">
                <div class="kpi-lbl">Total Cost (₹)</div>
                <div class="kpi-val" style="color:#7c3aed;" id="kpi_cost">₹0</div>
            </div>
        </div>

        <!-- Month Filter Selector -->
        <select class="filter-select" id="month_filter" onchange="loadReadingData(1)">
            <option value="all">All Available Months</option>
        </select>

        <!-- Month-Wise Consumption Breakdown Card -->
        <div class="bd-card">
            <div class="bd-title">
                <span><i class="fa fa-bar-chart" style="color:#059669;"></i> Monthly Consumption Breakdown</span>
            </div>
            <div style="overflow-x:auto;">
                <table class="bd-table">
                    <thead>
                        <tr>
                            <th>Month</th>
                            <th style="text-align:center;">Sessions</th>
                            <th style="text-align:center;">Shuttles</th>
                            <th style="text-align:right;">Cost (₹)</th>
                        </tr>
                    </thead>
                    <tbody id="month_breakdown_body">
                        <tr><td colspan="4" style="text-align:center; color:#94a3b8;">Loading breakdown...</td></tr>
                    </tbody>
                </table>
            </div>
        </div>

        <!-- Shuttle Readings Log Feed -->
        <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:10px;">
            <div style="font-size:13px; font-weight:800; color:#475569; text-transform:uppercase; letter-spacing:0.5px;">
                <i class="fa fa-history"></i> Session Readings Log
            </div>
            <div style="font-size:12px; font-weight:700; color:#64748b;" id="record_count_lbl"></div>
        </div>

        <div id="readings_list_container">
            <div style="text-align:center; padding:30px; color:#94a3b8;">Loading reading records...</div>
        </div>

        <div class="pagination-container" id="pagination_wrap"></div>

    </div>

    <!-- Modal: Record Shuttle Reading -->
    <div class="modal fade" id="addReadingModal" tabindex="-1" role="dialog" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered" role="document">
            <div class="modal-content" style="border-radius:20px; border:none;">
                <div class="modal-header" style="background:linear-gradient(135deg, #059669, #10b981); color:#fff; border-top-left-radius:20px; border-top-right-radius:20px; padding:16px 20px;">
                    <h5 class="modal-title" style="font-weight:800; font-size:16px;">Record Session Shuttle Count</h5>
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close" style="color:#fff; opacity:0.9;">
                        <span aria-hidden="true">&times;</span>
                    </button>
                </div>
                <form id="readingForm">
                    <input type="hidden" id="reading_id" name="id" value="0">
                    <input type="hidden" name="action" value="save_reading">
                    <div class="modal-body" style="padding:20px;">
                        
                        <div class="form-group" style="margin-bottom:12px;">
                            <label style="font-size:12px; font-weight:800; color:#475569;">Reading Date</label>
                            <input type="date" class="form-control" name="reading_date" id="reading_date" value="<?php echo date('Y-m-d'); ?>" style="border-radius:10px; font-weight:700;" required>
                        </div>

                        <div class="form-group" style="margin-bottom:12px;">
                            <label style="font-size:12px; font-weight:800; color:#475569;">Select Group</label>
                            <select class="form-control" name="group_id" id="group_id" onchange="fetchLastReading()" style="border-radius:10px; font-weight:700;">
                                <!-- Loaded dynamically -->
                            </select>
                        </div>

                        <div class="row" style="margin-left:-6px; margin-right:-6px;">
                            <div class="col-6" style="padding-left:6px; padding-right:6px;">
                                <div class="form-group" style="margin-bottom:12px;">
                                    <label style="font-size:12px; font-weight:800; color:#475569;">Start Reading</label>
                                    <input type="number" class="form-control" name="start_shuttle" id="start_shuttle" min="0" placeholder="0" style="border-radius:10px; font-weight:700;" required>
                                </div>
                            </div>
                            <div class="col-6" style="padding-left:6px; padding-right:6px;">
                                <div class="form-group" style="margin-bottom:12px;">
                                    <label style="font-size:12px; font-weight:800; color:#475569;">End Reading</label>
                                    <input type="number" class="form-control" name="end_shuttle" id="end_shuttle" min="0" placeholder="0" onkeyup="calcShuttlesUsed()" style="border-radius:10px; font-weight:700;" required>
                                </div>
                            </div>
                        </div>

                        <div class="row" style="margin-left:-6px; margin-right:-6px;">
                            <div class="col-6" style="padding-left:6px; padding-right:6px;">
                                <div class="form-group" style="margin-bottom:12px;">
                                    <label style="font-size:12px; font-weight:800; color:#475569;">Shuttles Used</label>
                                    <input type="number" class="form-control" name="shuttles_used" id="shuttles_used" min="0" placeholder="0" style="border-radius:10px; font-weight:800; color:#dc2626; background:#fff1f2;" required>
                                </div>
                            </div>
                            <div class="col-6" style="padding-left:6px; padding-right:6px;">
                                <div class="form-group" style="margin-bottom:12px;">
                                    <label style="font-size:12px; font-weight:800; color:#475569;">Price / Shuttle (₹)</label>
                                    <input type="number" step="0.01" class="form-control" name="per_shuttle_price" id="per_shuttle_price" placeholder="0.00" style="border-radius:10px; font-weight:700;" required>
                                </div>
                            </div>
                        </div>

                        <div class="form-group" style="margin-bottom:12px;">
                            <label style="font-size:12px; font-weight:800; color:#475569;">Brand Name</label>
                            <input type="text" class="form-control" name="brand_name" id="brand_name" value="Standard" style="border-radius:10px; font-weight:600;">
                        </div>

                    </div>
                    <div class="modal-footer" style="border-top:1px solid #f1f5f9; padding:12px 20px;">
                        <button type="button" class="btn btn-secondary btn-sm" data-dismiss="modal" style="border-radius:10px; font-weight:700;">Cancel</button>
                        <button type="submit" class="btn btn-emerald btn-sm" style="background:#059669; color:#fff; border-radius:10px; font-weight:800; padding:8px 18px;" id="btnSubmitReading">Save Reading Log</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Modal: Print Consumption Statement -->
    <div class="modal fade" id="printModal" tabindex="-1" role="dialog" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered modal-lg" role="document" style="max-width:720px;">
            <div class="modal-content" style="border-radius:20px; border:none; box-shadow:0 12px 36px rgba(0,0,0,0.18);">
                <div class="modal-header" style="background:linear-gradient(135deg, #0f172a, #1e293b); color:#fff; border-top-left-radius:20px; border-top-right-radius:20px; padding:16px 20px; display:flex; align-items:center; justify-content:space-between;">
                    <div style="display:flex; align-items:center; gap:10px;">
                        <div style="width:36px; height:36px; border-radius:10px; background:rgba(255,255,255,0.15); display:flex; align-items:center; justify-content:center; font-size:16px;">
                            <i class="fa fa-print text-white"></i>
                        </div>
                        <div>
                            <h5 class="modal-title" style="font-weight:800; font-size:16px; margin:0; color:#fff;">Print Readings Statement</h5>
                            <small style="opacity:0.8; font-size:11px; color:#cbd5e1;">A4 Clean Statement / PDF Export</small>
                        </div>
                    </div>
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close" style="color:#fff; opacity:0.9; text-shadow:none;">
                        <span aria-hidden="true">&times;</span>
                    </button>
                </div>
                <div class="modal-body" style="padding:16px;">
                    <!-- Filter Scope in Modal -->
                    <div style="display:flex; align-items:center; justify-content:space-between; gap:10px; margin-bottom:12px; flex-wrap:wrap; background:#f8fafc; padding:10px 14px; border-radius:12px; border:1px solid #e2e8f0;">
                        <div style="font-size:12.5px; font-weight:800; color:#334155;">
                            <i class="fa fa-filter" style="color:#059669;"></i> Statement Period:
                        </div>
                        <select class="form-control" id="modal_print_month_select" onchange="generatePrintStatement(this.value)" style="border-radius:10px; font-size:12.5px; font-weight:700; height:38px; width:auto; min-width:210px; border:1.5px solid #cbd5e1;">
                            <option value="all">All Available Months</option>
                        </select>
                    </div>

                    <!-- Scrollable Preview Area -->
                    <div class="print-preview-box" id="modal_print_preview_box">
                        <div id="modal_print_preview_content">
                            <div style="text-align:center; padding:35px 20px; color:#64748b;">
                                <i class="fa fa-spinner fa-spin fa-2x" style="color:#059669;"></i>
                                <div style="margin-top:10px; font-weight:700; font-size:13px;">Preparing clean printable statement...</div>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="modal-footer" style="border-top:1px solid #f1f5f9; padding:14px 20px; display:flex; justify-content:space-between; align-items:center;">
                    <button type="button" class="btn btn-secondary btn-sm" data-dismiss="modal" style="border-radius:10px; font-weight:700; padding:8px 16px;">
                        <i class="fa fa-times"></i> Close
                    </button>
                    <button type="button" class="btn btn-emerald btn-sm" onclick="triggerNativePrint()" id="btn_modal_print_action" style="background:#059669; color:#fff; border-radius:10px; font-weight:800; padding:9px 22px; box-shadow:0 4px 12px rgba(5,150,105,0.3); font-size:13px;">
                        <i class="fa fa-print"></i> Print / Save PDF
                    </button>
                </div>
            </div>
        </div>
    </div>

    <!-- Mobile Bottom Navigation (5 Tabs) -->
    <?php include 'mobile_bottom_nav.php'; ?>

    <script src="../../js/jquery-3.1.1.min.js"></script>
    <script src="../../js/bootstrap.min.js"></script>
    <script src="../../js/plugins/sweetalert/sweetalert.min.js"></script>

    <script>
        let currentPage = 1;

        $(document).ready(function() {
            loadReadingData(1);

            $('#readingForm').on('submit', function(e) {
                e.preventDefault();
                let formData = $(this).serialize();

                $('#btnSubmitReading').prop('disabled', true).text('Saving...');

                $.post('../api/shuttle_cock_readings.php', formData, function(res) {
                    $('#btnSubmitReading').prop('disabled', false).text('Save Reading Log');
                    if (res.status === 'success') {
                        $('#addReadingModal').modal('hide');
                        $('#readingForm')[0].reset();
                        $('#reading_id').val('0');
                        swal('Saved!', res.message || 'Shuttle count recorded successfully.', 'success');
                        loadReadingData(currentPage);
                    } else {
                        swal('Error', res.message || 'Could not save shuttle reading.', 'error');
                    }
                }, 'json').fail(function() {
                    $('#btnSubmitReading').prop('disabled', false).text('Save Reading Log');
                    swal('Error', 'Server error saving reading log.', 'error');
                });
            });
        });

        function loadReadingData(page) {
            currentPage = page || 1;
            let selMonth = $('#month_filter').val() || 'all';

            $.post('../api/shuttle_cock_readings.php', { action: 'load_data', page: currentPage, selected_month: selMonth }, function(response) {
                if (!response || response.length < 2) return;
                let summary = response[0];
                let records = response[1];

                // KPI Metrics
                $('#kpi_balance').text(summary.stock_remaining || 0);
                $('#kpi_used').text(summary.total_used || 0);
                $('#kpi_cost').text('₹' + Math.round(summary.total_used_cost || 0).toLocaleString());

                // Available Months Filter Dropdown
                let mHtml = '<option value="all">All Available Months</option>';
                if (summary.available_months && summary.available_months.length > 0) {
                    summary.available_months.forEach(function(m) {
                        let sel = (m.month_key === summary.selected_month) ? 'selected' : '';
                        mHtml += `<option value="${m.month_key}" ${sel}>${m.month_label}</option>`;
                    });
                }
                $('#month_filter').html(mHtml);
                $('#modal_print_month_select').html(mHtml);

                // Group Dropdown for modal
                if (summary.groups && summary.groups.length > 0) {
                    let grpHtml = '';
                    if (summary.is_all_groups) {
                        grpHtml += '<option value="0">General / All Groups</option>';
                    }
                    summary.groups.forEach(function(g) {
                        grpHtml += `<option value="${g.id}">${g.group_name}</option>`;
                    });
                    $('#group_id').html(grpHtml);
                    fetchLastReading();
                }

                // Month Breakdown Table
                let mbHtml = '';
                if (summary.month_breakdown && summary.month_breakdown.length > 0) {
                    summary.month_breakdown.forEach(function(mb) {
                        mbHtml += `
                            <tr>
                                <td><strong>${mb.month_label}</strong></td>
                                <td style="text-align:center;">${mb.session_count}</td>
                                <td style="text-align:center; font-weight:800; color:#dc2626;">${mb.shuttles_used}</td>
                                <td style="text-align:right; font-weight:800; color:#059669;">₹${Math.round(mb.total_cost || 0).toLocaleString()}</td>
                            </tr>
                        `;
                    });
                } else {
                    mbHtml = '<tr><td colspan="4" style="text-align:center; color:#94a3b8;">No breakdown available</td></tr>';
                }
                $('#month_breakdown_body').html(mbHtml);

                // Render Readings List
                $('#record_count_lbl').text('Total: ' + (summary.total_rows || 0));

                let listHtml = '';
                if (records && records.length > 0) {
                    records.forEach(function(r) {
                        listHtml += `
                            <div class="rd-card">
                                <div class="rd-header">
                                    <div class="rd-date"><i class="fa fa-calendar"></i> ${r.reading_date}</div>
                                    <div class="rd-badge">${r.group_name}</div>
                                </div>
                                <div class="rd-body">
                                    <div class="rd-icon-box"><i class="fa fa-circle-o-notch"></i></div>
                                    <div class="rd-info">
                                        <div class="rd-title">${r.shuttles_used} shuttles used (${r.brand_name})</div>
                                        <div class="rd-meta">Readings: ${r.start_shuttle} &rarr; ${r.end_shuttle} @ ₹${parseFloat(r.per_shuttle_price).toFixed(2)}</div>
                                    </div>
                                    <div class="rd-amt">₹${Math.round(r.total_amount || 0).toLocaleString()}</div>
                                </div>
                                <div class="rd-footer">
                                    <div><i class="fa fa-user-circle"></i> ${r.brand_name}</div>
                                    <button class="btn-del-sm" onclick="deleteReading(${r.id})"><i class="fa fa-trash"></i> Delete</button>
                                </div>
                            </div>
                        `;
                    });
                } else {
                    listHtml = '<div style="text-align:center; padding:30px; background:#fff; border-radius:16px; color:#94a3b8; font-weight:600;">No shuttle reading records found</div>';
                }
                $('#readings_list_container').html(listHtml);

                // Pagination
                let totalPages = Math.ceil((summary.total_rows || 0) / 8);
                let phtml = '';
                if (totalPages > 1) {
                    for (let p = 1; p <= totalPages; p++) {
                        let active = (p === currentPage) ? 'active' : '';
                        phtml += `<button class="page-btn ${active}" onclick="loadReadingData(${p})">${p}</button>`;
                    }
                }
                $('#pagination_wrap').html(phtml);

            }, 'json');
        }

        function fetchLastReading() {
            let gid = $('#group_id').val();
            if (!gid) return;
            $.post('../api/shuttle_cock_readings.php', { action: 'get_last_reading', group_id: gid }, function(res) {
                if (res.status === 'success') {
                    $('#start_shuttle').val(res.last_end_shuttle || 0);
                    if (res.latest_price > 0) {
                        $('#per_shuttle_price').val(res.latest_price);
                    }
                    if (res.brand_name) {
                        $('#brand_name').val(res.brand_name);
                    }
                }
            }, 'json');
        }

        function calcShuttlesUsed() {
            let start = parseInt($('#start_shuttle').val()) || 0;
            let end = parseInt($('#end_shuttle').val()) || 0;
            if (end >= start) {
                $('#shuttles_used').val(end - start);
            }
        }

        function deleteReading(id) {
            swal({
                title: "Delete Shuttle Reading?",
                text: "This will remove the session shuttle count entry.",
                type: "warning",
                showCancelButton: true,
                confirmButtonColor: "#dc2626",
                confirmButtonText: "Yes, Delete",
                closeOnConfirm: false
            }, function() {
                $.post('../api/shuttle_cock_readings.php', { action: 'delete_reading', id: id }, function(res) {
                    if (res.status === 'success') {
                        swal("Deleted!", "Shuttle reading deleted.", "success");
                        loadReadingData(currentPage);
                    } else {
                        swal("Error", res.message || "Could not delete reading.", "error");
                    }
                }, 'json');
            });
        }

        // ================= PRINT STATEMENT FUNCTIONS =================
        function openPrintModal() {
            let selMonth = $('#month_filter').val() || 'all';
            $('#modal_print_month_select').val(selMonth);
            generatePrintStatement(selMonth);
            $('#printModal').modal('show');
        }

        function generatePrintStatement(monthKey) {
            monthKey = monthKey || $('#modal_print_month_select').val() || 'all';
            $('#modal_print_preview_content').html(`
                <div style="text-align:center; padding:40px 20px; color:#64748b;">
                    <i class="fa fa-spinner fa-spin fa-2x" style="color:#059669;"></i>
                    <div style="margin-top:10px; font-weight:700; font-size:13px;">Preparing clean printable statement...</div>
                </div>
            `);

            $.post('../api/shuttle_cock_readings.php', {
                action: 'load_data',
                selected_month: monthKey,
                is_print: 1
            }, function(res) {
                if (!res || res.length < 2) {
                    $('#modal_print_preview_content').html('<div style="text-align:center; padding:30px; color:#dc2626;">Failed to load print statement data.</div>');
                    return;
                }
                let summary = res[0];
                let allRecords = res[1] || [];

                let monthLabel = "All Available Months";
                if (monthKey !== 'all' && summary.available_months) {
                    let found = summary.available_months.find(function(m) { return m.month_key === monthKey; });
                    if (found) monthLabel = found.month_label;
                }

                let nowStr = new Date().toLocaleString('en-IN', {
                    day: '2-digit', month: 'short', year: 'numeric',
                    hour: '2-digit', minute: '2-digit', hour12: true
                });

                let totShuttles = 0;
                let totCost = 0;

                // Build Table Rows
                let rowsHtml = '';
                if (allRecords.length > 0) {
                    allRecords.forEach(function(r, idx) {
                        let used = parseInt(r.shuttles_used) || 0;
                        let cost = parseFloat(r.total_amount) || 0;
                        totShuttles += used;
                        totCost += cost;

                        rowsHtml += `
                            <tr>
                                <td style="text-align:center; color:#64748b; font-weight:600;">${idx + 1}</td>
                                <td style="white-space:nowrap;"><strong>${r.reading_date}</strong></td>
                                <td>${r.group_name}</td>
                                <td>${r.brand_name || 'Standard'}</td>
                                <td style="text-align:center;">${r.start_shuttle} &rarr; ${r.end_shuttle}</td>
                                <td style="text-align:center; font-weight:800; color:#dc2626;">${used}</td>
                                <td style="text-align:right;">₹${parseFloat(r.per_shuttle_price).toFixed(2)}</td>
                                <td style="text-align:right; font-weight:800; color:#0f172a;">₹${Math.round(cost).toLocaleString()}</td>
                            </tr>
                        `;
                    });
                } else {
                    rowsHtml = `<tr><td colspan="8" style="text-align:center; padding:20px; color:#94a3b8;">No session readings found for this statement period.</td></tr>`;
                }

                // Build Month Breakdown rows (if multiple months exist or all months selected)
                let mbTableHtml = '';
                if (summary.month_breakdown && summary.month_breakdown.length > 0) {
                    let mbRows = '';
                    summary.month_breakdown.forEach(function(mb) {
                        mbRows += `
                            <tr>
                                <td><strong>${mb.month_label}</strong></td>
                                <td style="text-align:center;">${mb.session_count}</td>
                                <td style="text-align:center; font-weight:800; color:#dc2626;">${mb.shuttles_used}</td>
                                <td style="text-align:right; font-weight:800; color:#059669;">₹${Math.round(mb.total_cost || 0).toLocaleString()}</td>
                            </tr>
                        `;
                    });
                    mbTableHtml = `
                        <div class="prt-section-title"><i class="fa fa-calendar"></i> Month-Wise Shuttle Cock Consumption Breakdown</div>
                        <table class="prt-table">
                            <thead>
                                <tr>
                                    <th>Month</th>
                                    <th style="text-align:center;">Sessions</th>
                                    <th style="text-align:center;">Shuttles Used</th>
                                    <th style="text-align:right;">Total Cost (₹)</th>
                                </tr>
                            </thead>
                            <tbody>${mbRows}</tbody>
                        </table>
                    `;
                }

                let statementHtml = `
                    <div class="prt-statement-wrapper">
                        <!-- Formal Letterhead -->
                        <div class="prt-header">
                            <h3 class="prt-title-main">YMCA Badminton Club Poovathussery</h3>
                            <div class="prt-subtitle">Poovathussery, Parakkadavu P.O., Ernakulam Dist., Kerala &bull; Phone: +91 94951 83688</div>
                            <div class="prt-badge-bar">Shuttle Cock Consumption & Session Readings Statement</div>
                            <div class="prt-period-bar">
                                <span><strong>Period:</strong> ${monthLabel}</span>
                                <span><strong>Generated:</strong> ${nowStr}</span>
                            </div>
                        </div>

                        <!-- KPI Strip -->
                        <div class="prt-kpi-grid">
                            <div class="prt-kpi-card" style="border-left-color: #3b82f6;">
                                <div class="prt-kpi-lbl">Current Stock Balance</div>
                                <div class="prt-kpi-val" style="color:#2563eb;">${summary.stock_remaining || 0} <small style="font-size:8pt; font-weight:600;">Shuttles</small></div>
                            </div>
                            <div class="prt-kpi-card" style="border-left-color: #ef4444;">
                                <div class="prt-kpi-lbl">Shuttles Consumed (${monthKey === 'all' ? 'All' : 'Period'})</div>
                                <div class="prt-kpi-val" style="color:#dc2626;">${totShuttles} <small style="font-size:8pt; font-weight:600;">Shuttles</small></div>
                            </div>
                            <div class="prt-kpi-card" style="border-left-color: #059669;">
                                <div class="prt-kpi-lbl">Total Cost (${monthKey === 'all' ? 'All' : 'Period'})</div>
                                <div class="prt-kpi-val" style="color:#059669;">₹${Math.round(totCost).toLocaleString()}</div>
                            </div>
                        </div>

                        ${mbTableHtml}

                        <!-- Detailed Daily Log Table -->
                        <div class="prt-section-title"><i class="fa fa-list-alt"></i> Daily Session Shuttle Cock Readings Log (${allRecords.length} records)</div>
                        <table class="prt-table">
                            <thead>
                                <tr>
                                    <th style="width:5%; text-align:center;">#</th>
                                    <th style="width:14%;">Date</th>
                                    <th style="width:20%;">Group / Session</th>
                                    <th style="width:16%;">Brand</th>
                                    <th style="width:15%; text-align:center;">Readings</th>
                                    <th style="width:10%; text-align:center;">Used</th>
                                    <th style="width:10%; text-align:right;">Rate</th>
                                    <th style="width:10%; text-align:right;">Total (₹)</th>
                                </tr>
                            </thead>
                            <tbody>${rowsHtml}</tbody>
                            <tfoot>
                                <tr>
                                    <td colspan="5" style="text-align:right; font-weight:800;">Period Total:</td>
                                    <td style="text-align:center; font-weight:800; color:#dc2626;">${totShuttles}</td>
                                    <td></td>
                                    <td style="text-align:right; font-weight:800; color:#059669;">₹${Math.round(totCost).toLocaleString()}</td>
                                </tr>
                            </tfoot>
                        </table>

                        <!-- Formal Signatures Block -->
                        <div class="prt-sig-block">
                            <div class="prt-sig-col">
                                <div class="prt-sig-line"></div>
                                <div class="prt-sig-title">Prepared By (In-Charge)</div>
                            </div>
                            <div class="prt-sig-col">
                                <div class="prt-sig-line"></div>
                                <div class="prt-sig-title">Audited / Verified By</div>
                            </div>
                            <div class="prt-sig-col">
                                <div class="prt-sig-line"></div>
                                <div class="prt-sig-title">President / Secretary</div>
                            </div>
                        </div>
                    </div>
                `;

                $('#modal_print_preview_content').html(statementHtml);
                $('#printable_readings_container').html(statementHtml);
            }, 'json');
        }

        function triggerNativePrint() {
            window.print();
        }

        // Intercept Ctrl+P or Cmd+P to open print modal cleanly
        $(document).keydown(function(e) {
            if ((e.ctrlKey || e.metaKey) && (e.key === 'p' || e.keyCode === 80)) {
                e.preventDefault();
                openPrintModal();
            }
        });
    </script>

    <!-- Dedicated Printable Container (Clean A4 Paper Output) -->
    <div id="printable_readings_container"></div>
</body>
</html>
