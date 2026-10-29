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
    <title>Shuttle Cock Stock - YMCA Mobile</title>
    
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

        .stk-card {
            background: #ffffff; border-radius: 16px; border: 1px solid #e2e8f0; padding: 14px 16px;
            margin-bottom: 10px; box-shadow: 0 2px 6px rgba(0,0,0,0.02);
            transition: transform 0.15s ease;
        }
        .stk-card:active { transform: scale(0.99); }
        .stk-header { display: flex; align-items: center; justify-content: space-between; margin-bottom: 8px; }
        .stk-date { font-size: 11px; font-weight: 700; color: #64748b; text-transform: uppercase; }
        .stk-badge { font-size: 10px; font-weight: 800; padding: 3px 8px; border-radius: 6px; text-transform: uppercase; background: #ecfdf5; color: #047857; }

        .stk-body { display: flex; align-items: center; gap: 12px; }
        .stk-icon-box {
            width: 42px; height: 42px; border-radius: 12px; background: #ecfdf5; color: #059669;
            display: flex; align-items: center; justify-content: center; font-size: 18px; font-weight: 800; flex-shrink: 0;
        }
        .stk-info { flex: 1; min-width: 0; }
        .stk-brand { font-size: 14px; font-weight: 800; color: #0f172a; margin: 0; white-space: nowrap; overflow: hidden; text-overflow: ellipsis; }
        .stk-meta { font-size: 11.5px; color: #64748b; font-weight: 600; margin-top: 2px; }
        .stk-amt { font-size: 16px; font-weight: 800; color: #059669; text-align: right; white-space: nowrap; }

        .stk-footer {
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
        #printable_stock_container {
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
            body > *:not(#printable_stock_container) {
                display: none !important;
                height: 0 !important;
                overflow: hidden !important;
            }
            #printable_stock_container {
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
                Shuttle Cock <span>Stock</span>
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
                    <h2>Shuttle Cock Stock</h2>
                    <p>Inward stock & inventory management</p>
                </div>
                <div style="display:flex; gap:6px; flex-shrink:0;">
                    <button type="button" class="btn btn-light btn-sm" style="border-radius:12px; font-weight:800; color:#1e293b; padding:8px 12px; box-shadow: 0 4px 10px rgba(0,0,0,0.1);" onclick="openPrintModal()">
                        <i class="fa fa-print"></i> Print
                    </button>
                    <button class="btn btn-light btn-sm" style="border-radius:12px; font-weight:800; color:#059669; padding:8px 12px; box-shadow: 0 4px 10px rgba(0,0,0,0.1);" data-toggle="modal" data-target="#addStockModal">
                        <i class="fa fa-plus-circle"></i> Add Stock
                    </button>
                </div>
            </div>
        </div>

        <!-- KPI Summary Strip -->
        <div class="kpi-grid">
            <div class="kpi-card-sm" style="border-left: 3px solid #10b981;">
                <div class="kpi-lbl">Stock In (Shuttles)</div>
                <div class="kpi-val" style="color:#059669;" id="kpi_tot_in">0</div>
            </div>
            <div class="kpi-card-sm" style="border-left: 3px solid #ef4444;">
                <div class="kpi-lbl">Shuttles Used</div>
                <div class="kpi-val" style="color:#dc2626;" id="kpi_tot_used">0</div>
            </div>
            <div class="kpi-card-sm" style="border-left: 3px solid #3b82f6;">
                <div class="kpi-lbl">Remaining Stock</div>
                <div class="kpi-val" style="color:#2563eb;" id="kpi_remaining">0</div>
            </div>
        </div>

        <!-- Group Stock Breakdown Card -->
        <div class="bd-card">
            <div class="bd-title">
                <span><i class="fa fa-pie-chart" style="color:#059669;"></i> Group Stock Breakdown</span>
                <span id="latest_price_lbl" style="font-size:11px; font-weight:700; color:#64748b;"></span>
            </div>
            <div style="overflow-x:auto;">
                <table class="bd-table">
                    <thead>
                        <tr>
                            <th>Group</th>
                            <th style="text-align:center;">In</th>
                            <th style="text-align:center;">Used</th>
                            <th style="text-align:center;">Balance</th>
                        </tr>
                    </thead>
                    <tbody id="group_breakdown_body">
                        <tr><td colspan="4" style="text-align:center; color:#94a3b8;">Loading breakdown...</td></tr>
                    </tbody>
                </table>
            </div>
        </div>

        <!-- Stock Records Feed -->
        <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:10px;">
            <div style="font-size:13px; font-weight:800; color:#475569; text-transform:uppercase; letter-spacing:0.5px;">
                <i class="fa fa-list"></i> Inward Stock Log
            </div>
            <div style="font-size:12px; font-weight:700; color:#64748b;" id="record_count_lbl"></div>
        </div>

        <div id="stock_list_container">
            <div style="text-align:center; padding:30px; color:#94a3b8;">Loading stock records...</div>
        </div>

        <div class="pagination-container" id="pagination_wrap"></div>

    </div>

    <!-- Modal: Add Stock Inward -->
    <div class="modal fade" id="addStockModal" tabindex="-1" role="dialog" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered" role="document">
            <div class="modal-content" style="border-radius:20px; border:none;">
                <div class="modal-header" style="background:linear-gradient(135deg, #059669, #10b981); color:#fff; border-top-left-radius:20px; border-top-right-radius:20px; padding:16px 20px;">
                    <h5 class="modal-title" style="font-weight:800; font-size:16px;" id="modalTitle">Record Inward Stock</h5>
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close" style="color:#fff; opacity:0.9;">
                        <span aria-hidden="true">&times;</span>
                    </button>
                </div>
                <form id="stockForm" enctype="multipart/form-data">
                    <input type="hidden" id="stock_id" name="id" value="0">
                    <input type="hidden" name="action" value="save_stock_in">
                    <div class="modal-body" style="padding:20px;">
                        
                        <div class="form-group" style="margin-bottom:12px;">
                            <label style="font-size:12px; font-weight:800; color:#475569;">Purchase Date</label>
                            <input type="date" class="form-control" name="purchase_date" id="purchase_date" value="<?php echo date('Y-m-d'); ?>" style="border-radius:10px; font-weight:700;" required>
                        </div>

                        <div class="form-group" style="margin-bottom:12px;">
                            <label style="font-size:12px; font-weight:800; color:#475569;">Assign to Group</label>
                            <select class="form-control" name="group_id" id="group_id" style="border-radius:10px; font-weight:700;">
                                <!-- Loaded dynamically -->
                            </select>
                        </div>

                        <div class="row" style="margin-left:-6px; margin-right:-6px;">
                            <div class="col-6" style="padding-left:6px; padding-right:6px;">
                                <div class="form-group" style="margin-bottom:12px;">
                                    <label style="font-size:12px; font-weight:800; color:#475569;">Brand Name</label>
                                    <input type="text" class="form-control" name="brand_name" id="brand_name" placeholder="Yonex / Li-Ning" value="Yonex" style="border-radius:10px; font-weight:700;" required>
                                </div>
                            </div>
                            <div class="col-6" style="padding-left:6px; padding-right:6px;">
                                <div class="form-group" style="margin-bottom:12px;">
                                    <label style="font-size:12px; font-weight:800; color:#475569;">No. of Boxes</label>
                                    <input type="number" class="form-control" name="no_of_boxes" id="no_of_boxes" min="1" placeholder="10" style="border-radius:10px; font-weight:700;" required>
                                </div>
                            </div>
                        </div>

                        <div class="row" style="margin-left:-6px; margin-right:-6px;">
                            <div class="col-6" style="padding-left:6px; padding-right:6px;">
                                <div class="form-group" style="margin-bottom:12px;">
                                    <label style="font-size:12px; font-weight:800; color:#475569;">Shuttles / Box</label>
                                    <input type="number" class="form-control" name="shuttles_per_box" id="shuttles_per_box" value="12" min="1" style="border-radius:10px; font-weight:700;" required>
                                </div>
                            </div>
                            <div class="col-6" style="padding-left:6px; padding-right:6px;">
                                <div class="form-group" style="margin-bottom:12px;">
                                    <label style="font-size:12px; font-weight:800; color:#475569;">Total Cost (₹)</label>
                                    <input type="number" step="0.01" class="form-control" name="total_cost" id="total_cost" placeholder="12000" style="border-radius:10px; font-weight:700;" required>
                                </div>
                            </div>
                        </div>

                        <div class="form-group" style="margin-bottom:12px;">
                            <label style="font-size:12px; font-weight:800; color:#475569;">Supplier Name (Optional)</label>
                            <input type="text" class="form-control" name="supplier_name" id="supplier_name" placeholder="E.g., Sports World" style="border-radius:10px; font-weight:600;">
                        </div>

                        <div class="form-group" style="margin-bottom:12px;">
                            <label style="font-size:12px; font-weight:800; color:#475569;">Bill / Invoice Photo</label>
                            <input type="file" class="form-control" name="bill_file" id="bill_file" accept="image/*,.pdf" style="border-radius:10px; font-size:12px;">
                        </div>

                    </div>
                    <div class="modal-footer" style="border-top:1px solid #f1f5f9; padding:12px 20px;">
                        <button type="button" class="btn btn-secondary btn-sm" data-dismiss="modal" style="border-radius:10px; font-weight:700;">Cancel</button>
                        <button type="submit" class="btn btn-emerald btn-sm" style="background:#059669; color:#fff; border-radius:10px; font-weight:800; padding:8px 18px;" id="btnSubmitStock">Save Stock Entry</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Modal: Print Stock Statement -->
    <div class="modal fade" id="printStockModal" tabindex="-1" role="dialog" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered modal-lg" role="document" style="max-width:720px;">
            <div class="modal-content" style="border-radius:20px; border:none; box-shadow:0 12px 36px rgba(0,0,0,0.18);">
                <div class="modal-header" style="background:linear-gradient(135deg, #0f172a, #1e293b); color:#fff; border-top-left-radius:20px; border-top-right-radius:20px; padding:16px 20px; display:flex; align-items:center; justify-content:space-between;">
                    <div style="display:flex; align-items:center; gap:10px;">
                        <div style="width:36px; height:36px; border-radius:10px; background:rgba(255,255,255,0.15); display:flex; align-items:center; justify-content:center; font-size:16px;">
                            <i class="fa fa-print text-white"></i>
                        </div>
                        <div>
                            <h5 class="modal-title" style="font-weight:800; font-size:16px; margin:0; color:#fff;">Print Inventory Statement</h5>
                            <small style="opacity:0.8; font-size:11px; color:#cbd5e1;">A4 Clean Statement / PDF Export</small>
                        </div>
                    </div>
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close" style="color:#fff; opacity:0.9; text-shadow:none;">
                        <span aria-hidden="true">&times;</span>
                    </button>
                </div>
                <div class="modal-body" style="padding:16px;">
                    <!-- Scrollable Preview Area -->
                    <div class="print-preview-box" id="modal_print_preview_box">
                        <div id="modal_print_preview_content">
                            <div style="text-align:center; padding:35px 20px; color:#64748b;">
                                <i class="fa fa-spinner fa-spin fa-2x" style="color:#059669;"></i>
                                <div style="margin-top:10px; font-weight:700; font-size:13px;">Preparing clean inventory statement...</div>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="modal-footer" style="border-top:1px solid #f1f5f9; padding:14px 20px; display:flex; justify-content:space-between; align-items:center;">
                    <button type="button" class="btn btn-secondary btn-sm" data-dismiss="modal" style="border-radius:10px; font-weight:700; padding:8px 16px;">
                        <i class="fa fa-times"></i> Close
                    </button>
                    <button type="button" class="btn btn-emerald btn-sm" onclick="triggerNativePrint()" style="background:#059669; color:#fff; border-radius:10px; font-weight:800; padding:9px 22px; box-shadow:0 4px 12px rgba(5,150,105,0.3); font-size:13px;">
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
            loadStockData(1);

            $('#stockForm').on('submit', function(e) {
                e.preventDefault();
                let formData = new FormData(this);

                $('#btnSubmitStock').prop('disabled', true).text('Saving...');

                $.ajax({
                    url: '../api/shuttle_cock_stock.php',
                    type: 'POST',
                    data: formData,
                    contentType: false,
                    processData: false,
                    dataType: 'json',
                    success: function(res) {
                        $('#btnSubmitStock').prop('disabled', false).text('Save Stock Entry');
                        if (res.status === 'success') {
                            $('#addStockModal').modal('hide');
                            $('#stockForm')[0].reset();
                            $('#stock_id').val('0');
                            swal('Saved!', res.message || 'Stock recorded successfully.', 'success');
                            loadStockData(currentPage);
                        } else {
                            swal('Error', res.message || 'Could not save stock entry.', 'error');
                        }
                    },
                    error: function(xhr) {
                        $('#btnSubmitStock').prop('disabled', false).text('Save Stock Entry');
                        swal('Error', 'Server error saving stock record.', 'error');
                    }
                });
            });
        });

        function loadStockData(page) {
            currentPage = page || 1;
            $.post('../api/shuttle_cock_stock.php', { action: 'load_data', page: currentPage }, function(response) {
                if (!response || response.length < 2) return;
                let summary = response[0];
                let records = response[1];

                // KPI Metrics
                $('#kpi_tot_in').text(summary.total_shuttles_in || 0);
                $('#kpi_tot_used').text(summary.total_shuttles_used || 0);
                $('#kpi_remaining').text(summary.stock_remaining || 0);

                if (summary.latest_unit_price > 0) {
                    $('#latest_price_lbl').text('Latest: ₹' + summary.latest_unit_price.toFixed(2) + '/shuttle');
                }

                // Populate Group Selector Modal Dropdown
                let grpHtml = '';
                if (summary.is_all_groups) {
                    grpHtml += '<option value="0">General / Shared Pool</option>';
                }
                if (summary.groups && summary.groups.length > 0) {
                    summary.groups.forEach(function(g) {
                        grpHtml += `<option value="${g.id}">${g.group_name}</option>`;
                    });
                }
                $('#group_id').html(grpHtml);

                // Group Breakdown Table
                let bHtml = '';
                if (summary.group_breakdown && summary.group_breakdown.length > 0) {
                    summary.group_breakdown.forEach(function(b) {
                        let remColor = b.shuttles_remaining > 5 ? '#059669' : '#dc2626';
                        bHtml += `
                            <tr>
                                <td><strong>${b.group_name}</strong></td>
                                <td style="text-align:center;">${b.shuttles_in}</td>
                                <td style="text-align:center; color:#dc2626;">${b.shuttles_used}</td>
                                <td style="text-align:center; font-weight:800; color:${remColor};">${b.shuttles_remaining}</td>
                            </tr>
                        `;
                    });
                } else {
                    bHtml = '<tr><td colspan="4" style="text-align:center; color:#94a3b8;">No breakdown available</td></tr>';
                }
                $('#group_breakdown_body').html(bHtml);

                // Render Inward Records List
                $('#record_count_lbl').text('Total: ' + (summary.total_rows || 0));

                let listHtml = '';
                if (records && records.length > 0) {
                    records.forEach(function(r) {
                        let billBtn = r.bill_file ? `<a href="../../image_upload/shuttle_cock_bills/${r.bill_file}" target="_blank" style="color:#059669; font-weight:700;"><i class="fa fa-paperclip"></i> View Bill</a>` : '<span style="color:#cbd5e1;">No Bill</span>';
                        let lockBadge = r.is_fee_locked ? `<span style="display:inline-block; margin-left:4px; font-size:10px; font-weight:800; padding:2px 6px; border-radius:6px; background:#fef3c7; color:#b45309;"><i class="fa fa-lock"></i> Fee Generated</span>` : '';
                        let safeReason = (r.fee_lock_reason || 'Monthly fee generated for this month. Shuttle purchase cannot be deleted.').replace(/'/g, "\\'");
                        let delAction = r.is_fee_locked 
                            ? `<button class="btn-del-sm" style="background:#f1f5f9; color:#94a3b8; cursor:not-allowed;" onclick="swal('Locked', '${safeReason}', 'warning')"><i class="fa fa-lock"></i> Locked</button>`
                            : `<button class="btn-del-sm" onclick="deleteStock(${r.id})"><i class="fa fa-trash"></i> Delete</button>`;

                        listHtml += `
                            <div class="stk-card">
                                <div class="stk-header">
                                    <div class="stk-date"><i class="fa fa-calendar"></i> ${r.purchase_date}</div>
                                    <div style="display:flex; align-items:center; gap:4px;">
                                        <span class="stk-badge">${r.group_name}</span>
                                        ${lockBadge}
                                    </div>
                                </div>
                                <div class="stk-body">
                                    <div class="stk-icon-box"><i class="fa fa-cubes"></i></div>
                                    <div class="stk-info">
                                        <div class="stk-brand">${r.brand_name} (${r.no_of_boxes} boxes)</div>
                                        <div class="stk-meta">${r.total_shuttles} shuttles @ ₹${parseFloat(r.per_shuttle_price).toFixed(2)}/each</div>
                                    </div>
                                    <div class="stk-amt">
                                        ₹${parseFloat(r.total_cost).toLocaleString()}
                                        ${r.is_fee_locked ? '<i class="fa fa-lock text-warning" style="font-size:12px; margin-left:3px;" title="Amount locked"></i>' : ''}
                                    </div>
                                </div>
                                <div class="stk-footer">
                                    <div>${billBtn}</div>
                                    ${delAction}
                                </div>
                            </div>
                        `;
                    });
                } else {
                    listHtml = '<div style="text-align:center; padding:30px; background:#fff; border-radius:16px; color:#94a3b8; font-weight:600;">No inward stock records found</div>';
                }
                $('#stock_list_container').html(listHtml);

                // Pagination
                let totalPages = Math.ceil((summary.total_rows || 0) / 8);
                let phtml = '';
                if (totalPages > 1) {
                    for (let p = 1; p <= totalPages; p++) {
                        let active = (p === currentPage) ? 'active' : '';
                        phtml += `<button class="page-btn ${active}" onclick="loadStockData(${p})">${p}</button>`;
                    }
                }
                $('#pagination_wrap').html(phtml);

            }, 'json');
        }

        function deleteStock(id) {
            swal({
                title: "Delete Stock Record?",
                text: "This will remove the inward stock entry and its accounting voucher.",
                type: "warning",
                showCancelButton: true,
                confirmButtonColor: "#dc2626",
                confirmButtonText: "Yes, Delete",
                closeOnConfirm: false
            }, function() {
                $.post('../api/shuttle_cock_stock.php', { action: 'delete_stock_in', id: id }, function(res) {
                    if (res.status === 'success') {
                        swal("Deleted!", "Stock record deleted.", "success");
                        loadStockData(currentPage);
                    } else {
                        swal("Error", res.message || "Could not delete stock record.", "error");
                    }
                }, 'json');
            });
        }

        // ================= PRINT STATEMENT FUNCTIONS =================
        function openPrintModal() {
            generateStockPrintStatement();
            $('#printStockModal').modal('show');
        }

        function generateStockPrintStatement() {
            $('#modal_print_preview_content').html(`
                <div style="text-align:center; padding:40px 20px; color:#64748b;">
                    <i class="fa fa-spinner fa-spin fa-2x" style="color:#059669;"></i>
                    <div style="margin-top:10px; font-weight:700; font-size:13px;">Preparing clean inventory statement...</div>
                </div>
            `);

            $.post('../api/shuttle_cock_stock.php', {
                action: 'load_data',
                is_print: 1
            }, function(res) {
                if (!res || res.length < 2) {
                    $('#modal_print_preview_content').html('<div style="text-align:center; padding:30px; color:#dc2626;">Failed to load inventory statement data.</div>');
                    return;
                }
                let summary = res[0];
                let allRecords = res[1] || [];

                let nowStr = new Date().toLocaleString('en-IN', {
                    day: '2-digit', month: 'short', year: 'numeric',
                    hour: '2-digit', minute: '2-digit', hour12: true
                });

                let totBoxes = 0;
                let totShuttles = 0;
                let totCost = 0;

                // Build Inward Table Rows
                let rowsHtml = '';
                if (allRecords.length > 0) {
                    allRecords.forEach(function(r, idx) {
                        let boxes = parseInt(r.no_of_boxes) || 0;
                        let shuttles = parseInt(r.total_shuttles) || 0;
                        let cost = parseFloat(r.total_cost) || 0;
                        totBoxes += boxes;
                        totShuttles += shuttles;
                        totCost += cost;

                        let suppInfo = r.supplier_name || '-';
                        if (r.invoice_no) suppInfo += ' <small style="color:#64748b;">(#' + r.invoice_no + ')</small>';

                        let statusText = 'Unpaid';
                        if (r.payment_status === 'paid') statusText = 'Paid';
                        else if (r.payment_status === 'partial') statusText = 'Partial (₹' + (r.paid_amount || 0).toFixed(0) + ')';

                        rowsHtml += `
                            <tr>
                                <td style="text-align:center; color:#64748b; font-weight:600;">${idx + 1}</td>
                                <td style="white-space:nowrap;"><strong>${r.purchase_date}</strong></td>
                                <td>${r.group_name}</td>
                                <td>${r.brand_name || 'Standard'}</td>
                                <td style="text-align:center;">${boxes}</td>
                                <td style="text-align:center; font-weight:800; color:#059669;">${shuttles}</td>
                                <td style="text-align:right;">₹${parseFloat(r.box_price || 0).toFixed(2)}</td>
                                <td style="text-align:right; font-weight:800; color:#0f172a;">₹${Math.round(cost).toLocaleString()}</td>
                                <td>${suppInfo}</td>
                                <td style="text-align:center; font-weight:700;">${statusText}</td>
                            </tr>
                        `;
                    });
                } else {
                    rowsHtml = `<tr><td colspan="10" style="text-align:center; padding:20px; color:#94a3b8;">No inward stock records found.</td></tr>`;
                }

                // Build Group Breakdown rows
                let grpTableHtml = '';
                if (summary.group_breakdown && summary.group_breakdown.length > 0) {
                    let grpRows = '';
                    summary.group_breakdown.forEach(function(b) {
                        let remColor = b.shuttles_remaining > 5 ? '#059669' : '#dc2626';
                        grpRows += `
                            <tr>
                                <td><strong>${b.group_name}</strong></td>
                                <td style="text-align:center;">${b.shuttles_in}</td>
                                <td style="text-align:center; color:#dc2626;">${b.shuttles_used}</td>
                                <td style="text-align:center; font-weight:800; color:${remColor};">${b.shuttles_remaining}</td>
                            </tr>
                        `;
                    });
                    grpTableHtml = `
                        <div class="prt-section-title"><i class="fa fa-users"></i> Group-Wise Shuttle Cock Stock Breakdown</div>
                        <table class="prt-table">
                            <thead>
                                <tr>
                                    <th>Group / Pool</th>
                                    <th style="text-align:center;">Stock In</th>
                                    <th style="text-align:center;">Shuttles Used</th>
                                    <th style="text-align:center;">Balance Remaining</th>
                                </tr>
                            </thead>
                            <tbody>${grpRows}</tbody>
                        </table>
                    `;
                }

                let statementHtml = `
                    <div class="prt-statement-wrapper">
                        <!-- Formal Letterhead -->
                        <div class="prt-header">
                            <h3 class="prt-title-main">YMCA Badminton Club Poovathussery</h3>
                            <div class="prt-subtitle">Poovathussery, Parakkadavu P.O., Ernakulam Dist., Kerala &bull; Phone: +91 94951 83688</div>
                            <div class="prt-badge-bar">Shuttle Cock Stock & Inventory Statement</div>
                            <div class="prt-period-bar">
                                <span><strong>Statement:</strong> Inward Stock & Balance Log</span>
                                <span><strong>Generated:</strong> ${nowStr}</span>
                            </div>
                        </div>

                        <!-- KPI Strip -->
                        <div class="prt-kpi-grid">
                            <div class="prt-kpi-card" style="border-left-color: #10b981;">
                                <div class="prt-kpi-lbl">Total Stock In</div>
                                <div class="prt-kpi-val" style="color:#059669;">${summary.total_shuttles_in || 0} <small style="font-size:8pt; font-weight:600;">Shuttles</small></div>
                            </div>
                            <div class="prt-kpi-card" style="border-left-color: #ef4444;">
                                <div class="prt-kpi-lbl">Total Shuttles Used</div>
                                <div class="prt-kpi-val" style="color:#dc2626;">${summary.total_shuttles_used || 0} <small style="font-size:8pt; font-weight:600;">Shuttles</small></div>
                            </div>
                            <div class="prt-kpi-card" style="border-left-color: #3b82f6;">
                                <div class="prt-kpi-lbl">Remaining Stock Balance</div>
                                <div class="prt-kpi-val" style="color:#2563eb;">${summary.stock_remaining || 0} <small style="font-size:8pt; font-weight:600;">Shuttles</small></div>
                            </div>
                            <div class="prt-kpi-card" style="border-left-color: #8b5cf6;">
                                <div class="prt-kpi-lbl">Total Inward Value</div>
                                <div class="prt-kpi-val" style="color:#7c3aed;">₹${Math.round(summary.total_cost_in || totCost).toLocaleString()}</div>
                            </div>
                        </div>

                        ${grpTableHtml}

                        <!-- Detailed Inward Purchases Table -->
                        <div class="prt-section-title"><i class="fa fa-list-alt"></i> Inward Shuttle Cock Purchases Log (${allRecords.length} records)</div>
                        <table class="prt-table">
                            <thead>
                                <tr>
                                    <th style="width:4%; text-align:center;">#</th>
                                    <th style="width:12%;">Date</th>
                                    <th style="width:16%;">Group</th>
                                    <th style="width:14%;">Brand</th>
                                    <th style="width:8%; text-align:center;">Boxes</th>
                                    <th style="width:9%; text-align:center;">Shuttles</th>
                                    <th style="width:10%; text-align:right;">Box Rate</th>
                                    <th style="width:11%; text-align:right;">Total (₹)</th>
                                    <th style="width:16%;">Supplier</th>
                                    <th style="width:10%; text-align:center;">Status</th>
                                </tr>
                            </thead>
                            <tbody>${rowsHtml}</tbody>
                            <tfoot>
                                <tr>
                                    <td colspan="4" style="text-align:right; font-weight:800;">Total Inward:</td>
                                    <td style="text-align:center; font-weight:800;">${totBoxes}</td>
                                    <td style="text-align:center; font-weight:800; color:#059669;">${totShuttles}</td>
                                    <td></td>
                                    <td style="text-align:right; font-weight:800; color:#059669;">₹${Math.round(totCost).toLocaleString()}</td>
                                    <td colspan="2"></td>
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
                $('#printable_stock_container').html(statementHtml);
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
    <div id="printable_stock_container"></div>
</body>
</html>
