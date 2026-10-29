<?php
session_start();
session_write_close();

if (empty($_SESSION['login_id'])) {
    header("Location: ../app_login_manager/logout.php");
    exit();
}

include_once '../app_common/auth_helper.php';
$login_id = (int)$_SESSION['login_id'];
$is_admin = isSuperAdmin($login_id) || isGroupAdmin($login_id);

if (!$is_admin) {
    header("Location: member_dashboard.php");
    exit();
}

if (isMobileClient()) {
    header("Location: mobile/set_fees.php");
    exit();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="description" content="YMCA Monthly Fee Settings">
    <title>YMCA | Monthly Fee Settings</title>

    <link href="../css/bootstrap.min.css" rel="stylesheet">
    <link href="../font-awesome/css/font-awesome.css" rel="stylesheet">
    <link href="../css/animate.css" rel="stylesheet">
    <link href="../css/style.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <link href="../css/plugins/sweetalert/sweetalert.css" rel="stylesheet">
    
    <script src="../js/jquery-3.1.1.min.js"></script>
    <script src="../js/bootstrap.min.js"></script>
    <script src="../js/plugins/sweetalert/sweetalert.min.js"></script>
    <script src="../js/plugins/metisMenu/jquery.metisMenu.js"></script>
    <script src="../js/plugins/slimscroll/jquery.slimscroll.min.js"></script>
    <script src="../js/inspinia.js"></script>
    <script src="../app_js/sweetalert-finez.js"></script>
    <script src="../app_pagination/pagination.js"></script>
    <script src="../app_menu/menu.js"></script>

    <style>
        *, *::before, *::after { box-sizing: border-box; }
        body, #wrapper { font-family: 'Inter','Segoe UI',sans-serif !important; background: #f8fafc !important; }

        /* Top Bar */
        .fee-topbar {
            background: #fff;
            border-bottom: 1px solid #e8edf5;
            padding: 12px 24px; min-height: 64px; height: auto;
            display: flex; align-items: center; justify-content: space-between; flex-wrap: wrap; gap: 10px;
            box-shadow: 0 1px 6px rgba(59,130,246,0.06);
            position: sticky; top: 0; z-index: 100;
        }
        .fee-topbar-left { display: flex; align-items: center; gap: 14px; flex-wrap: wrap; }
        .fee-hamburger {
            width: 38px; height: 38px; border-radius: 10px;
            background: #f0f4ff; border: 1px solid #dbe6fe; color: #2563eb;
            display: flex; align-items: center; justify-content: center;
            cursor: pointer; transition: all .15s; font-size: 15px; text-decoration: none !important;
        }
        .fee-hamburger:hover { background: #2563eb; color: #fff; border-color: #2563eb; }
        .fee-title-group h1 { font-size: 18px; font-weight: 800; color: #0f172a; margin: 0; }
        .fee-title-group p { font-size: 12px; color: #64748b; margin: 1px 0 0; }

        /* Container */
        .fee-container { max-width: 1100px; margin: 24px auto; padding: 0 20px; }

        /* Stat Cards */
        .stat-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(220px, 1fr));
            gap: 16px;
            margin-bottom: 24px;
        }
        .stat-card {
            background: #fff;
            border-radius: 16px;
            border: 1px solid #e2e8f0;
            padding: 18px 22px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.03);
            display: flex;
            align-items: center;
            gap: 16px;
        }
        .stat-icon-wrap {
            width: 48px; height: 48px; border-radius: 14px;
            display: flex; align-items: center; justify-content: center;
            font-size: 20px; color: #fff;
            flex-shrink: 0;
        }
        .stat-icon-blue { background: linear-gradient(135deg, #3b82f6, #1d4ed8); }
        .stat-icon-green { background: linear-gradient(135deg, #10b981, #059669); }
        .stat-icon-purple { background: linear-gradient(135deg, #8b5cf6, #6d28d9); }
        .stat-meta h3 { font-size: 22px; font-weight: 800; color: #0f172a; margin: 0; }
        .stat-meta p { font-size: 12px; font-weight: 600; color: #64748b; margin: 2px 0 0; text-transform: uppercase; letter-spacing: 0.5px; }

        /* Main Table Card */
        .fee-card {
            background: #ffffff; border-radius: 20px; border: 1px solid #e2e8f0;
            box-shadow: 0 4px 20px rgba(0,0,0,0.04); overflow: hidden; margin-bottom: 24px;
        }
        .fee-card-header {
            padding: 20px 28px; border-bottom: 1px solid #f1f5f9;
            display: flex; align-items: center; justify-content: space-between; flex-wrap: wrap; gap: 14px;
            background: #fafbfc;
        }
        .fee-card-header-left { display: flex; align-items: center; gap: 14px; }
        .fee-icon-badge {
            width: 44px; height: 44px; border-radius: 12px;
            background: linear-gradient(135deg, #4f46e5, #3b82f6); color: #fff;
            display: flex; align-items: center; justify-content: center; font-size: 18px; font-weight: 700;
            box-shadow: 0 4px 14px rgba(59,130,246,0.3);
        }
        .fee-card-title { font-size: 17px; font-weight: 800; color: #0f172a; margin: 0; }
        .fee-card-sub { font-size: 12px; color: #64748b; margin: 2px 0 0; }

        .btn-add-fee {
            background: linear-gradient(135deg, #2563eb, #1d4ed8);
            color: #fff !important; font-weight: 700; font-size: 13.5px;
            padding: 10px 20px; border-radius: 12px; border: none;
            display: inline-flex; align-items: center; gap: 8px;
            box-shadow: 0 4px 14px rgba(37,99,235,0.25);
            transition: all .15s ease-in-out;
            cursor: pointer;
        }
        .btn-add-fee:hover {
            transform: translateY(-1px);
            box-shadow: 0 6px 18px rgba(37,99,235,0.35);
        }

        /* Table */
        .fee-table {
            width: 100%; border-collapse: separate; border-spacing: 0;
        }
        .fee-table thead th {
            background: #f8fafc;
            padding: 14px 20px;
            font-size: 11.5px; font-weight: 800;
            text-transform: uppercase; letter-spacing: 0.05em;
            color: #475569;
            border-bottom: 1.5px solid #e8edf5;
        }
        .fee-table tbody td {
            padding: 16px 20px;
            vertical-align: middle;
            border-bottom: 1px solid #f1f5f9;
            color: #334155; font-size: 14px;
        }
        .fee-table tbody tr:hover {
            background: #f8faff;
        }
        .fee-table tbody tr:last-child td {
            border-bottom: none;
        }

        /* Badges */
        .badge-group {
            display: inline-flex; align-items: center; gap: 6px;
            background: #eff6ff; color: #2563eb;
            padding: 6px 12px; border-radius: 8px;
            font-size: 13px; font-weight: 700;
            border: 1px solid #dbeafe;
        }
        .badge-method {
            display: inline-flex; align-items: center; gap: 6px;
            padding: 5px 12px; border-radius: 8px;
            font-size: 12.5px; font-weight: 700;
        }
        .badge-method.fixed { background: #f1f5f9; color: #334155; border: 1px solid #cbd5e1; }
        .badge-method.shuttle { background: #ede9fe; color: #6d28d9; border: 1px solid #ddd6fe; }
        .badge-method.shuttle-equal { background: #fef3c7; color: #b45309; border: 1px solid #fde68a; }
        .badge-amount {
            display: inline-flex; align-items: center; gap: 4px;
            background: #ecfdf5; color: #059669;
            padding: 6px 14px; border-radius: 10px;
            font-size: 15px; font-weight: 800;
            border: 1px solid #a7f3d0;
        }
        .badge-status {
            display: inline-block; padding: 4px 10px; border-radius: 20px;
            font-size: 11.5px; font-weight: 800; text-transform: uppercase; letter-spacing: 0.04em;
        }
        .badge-status.active { background: #dcfce7; color: #166534; }
        .badge-status.upcoming { background: #fef3c7; color: #92400e; }
        .badge-status.expired { background: #f1f5f9; color: #64748b; }

        /* Action Buttons */
        .btn-act {
            width: 32px; height: 32px; border-radius: 8px;
            border: 1px solid transparent; display: inline-flex;
            align-items: center; justify-content: center;
            font-size: 13px; cursor: pointer; transition: all .15s;
            margin-right: 6px;
        }
        .btn-act-edit { background: #eff6ff; color: #2563eb; border-color: #dbeafe; }
        .btn-act-edit:hover { background: #2563eb; color: #fff; }
        .btn-act-del { background: #fef2f2; color: #dc2626; border-color: #fee2e2; }
        .btn-act-del:hover { background: #dc2626; color: #fff; }

        /* Empty State */
        .fee-empty {
            text-align: center; padding: 50px 20px; color: #94a3b8;
        }
        .fee-empty i { font-size: 40px; margin-bottom: 12px; color: #cbd5e1; }
        .fee-empty p { font-size: 14px; font-weight: 600; margin: 0; }

        /* Modal custom styles */
        .modal-content {
            border-radius: 20px; border: none; overflow: hidden;
            box-shadow: 0 10px 30px rgba(0,0,0,0.15);
        }
        .modal-header-custom {
            background: linear-gradient(135deg, #1e293b, #0f172a);
            color: #fff; padding: 20px 24px;
        }
        .modal-header-custom h4 {
            margin: 0; font-weight: 800; font-size: 17px;
            display: flex; align-items: center; gap: 10px;
        }
        .modal-body-custom { padding: 24px; }
        .form-group-custom { margin-bottom: 18px; }
        .form-label-custom {
            display: block; font-size: 12px; font-weight: 800; color: #475569;
            text-transform: uppercase; letter-spacing: 0.5px; margin-bottom: 6px;
        }
        .form-control-custom {
            width: 100%; height: 46px; border-radius: 12px; border: 1px solid #cbd5e1;
            padding: 0 14px; font-size: 14px; font-weight: 600; color: #0f172a;
            background: #ffffff; transition: all 0.2s; outline: none;
        }
        .form-control-custom:focus {
            border-color: #3b82f6; box-shadow: 0 0 0 3px rgba(59,130,246,0.15);
        }
        .modal-footer-custom {
            background: #f8fafc; padding: 16px 24px; border-top: 1px solid #e8edf5;
            display: flex; justify-content: flex-end; gap: 10px;
        }
        .btn-modal-cancel {
            padding: 10px 18px; border-radius: 10px; border: 1px solid #cbd5e1;
            background: #fff; color: #475569; font-weight: 700; font-size: 13px;
        }
        .btn-modal-save {
            padding: 10px 22px; border-radius: 10px; border: none;
            background: linear-gradient(135deg, #2563eb, #1d4ed8);
            color: #fff; font-weight: 700; font-size: 13px;
            box-shadow: 0 4px 12px rgba(37,99,235,0.25);
        }
    </style>
</head>

<body>
    <input type="hidden" id="hdn_current_page" value="1">
    <input type="hidden" id="hdn_id" value="0">

    <div id="wrapper">
        <!-- Sidebar Navigation -->
        <nav class="navbar-default navbar-static-side" role="navigation">
            <div class="dropdown profile-element">
                <center>
                    <span><img alt="image" class="img-circle" src="../img/customer.png" style="padding-top: 20px;"/></span>
                    <span class="clear"> <span class="block m-t-xs"> <strong class="font-bold"><?php echo htmlspecialchars($_SESSION['name'] ?? 'Admin'); ?></strong></span></span>
                </center>
            </div>
            <div class="sidebar-collapse" id="divMenuContainer">
                <!-- Injected via app_menu/menu.js -->
            </div>
        </nav>

        <!-- Main Wrapper -->
        <div id="page-wrapper" class="gray-bg" style="min-height: 100vh; padding: 0;">
            <!-- Top Bar -->
            <div class="fee-topbar">
                <div class="fee-topbar-left">
                    <a class="fee-hamburger navbar-minimalize" href="#"><i class="fa fa-bars"></i></a>
                    <div class="fee-title-group">
                        <h1>Monthly Fee Settings</h1>
                        <p>Configure and manage monthly membership fees for club groups</p>
                    </div>
                </div>
                <div class="fee-topbar-right">
                    <a href="../app_login_manager/logout.php" class="btn btn-sm btn-outline btn-default" style="border-radius:8px; font-weight:700;">
                        <i class="fa fa-sign-out"></i> Log out
                    </a>
                </div>
            </div>

            <!-- Page Container -->
            <div class="fee-container">
                <!-- Summary Stats -->
                <div class="stat-grid">
                    <div class="stat-card">
                        <div class="stat-icon-wrap stat-icon-green">
                            <i class="fa fa-inr"></i>
                        </div>
                        <div class="stat-meta">
                            <h3 id="stat_active_fee">₹300</h3>
                            <p>Active Default Fee</p>
                        </div>
                    </div>
                    <div class="stat-card">
                        <div class="stat-icon-wrap stat-icon-blue">
                            <i class="fa fa-calendar-check-o"></i>
                        </div>
                        <div class="stat-meta">
                            <h3 id="stat_total_rules">0</h3>
                            <p>Configured Rules</p>
                        </div>
                    </div>
                    <div class="stat-card">
                        <div class="stat-icon-wrap stat-icon-purple">
                            <i class="fa fa-users"></i>
                        </div>
                        <div class="stat-meta">
                            <h3 id="stat_covered_groups">All</h3>
                            <p>Coverage Scope</p>
                        </div>
                    </div>
                </div>

                <!-- Direct Fee Setting Form Card -->
                <div class="fee-card" style="margin-bottom: 24px;">
                    <div class="fee-card-header" style="background: linear-gradient(135deg, #f8fafc 0%, #f1f5f9 100%);">
                        <div class="fee-card-header-left">
                            <div class="fee-icon-badge" style="background: linear-gradient(135deg, #2563eb, #1d4ed8);">
                                <i class="fa fa-sliders"></i>
                            </div>
                            <div>
                                <h2 class="fee-card-title">Set Monthly Fee & Calculation Type</h2>
                                <p class="fee-card-sub">Configure how monthly dues are calculated for groups (Fixed Amount vs Actual Shuttlecock Usage)</p>
                            </div>
                        </div>
                    </div>

                    <div style="padding: 24px 28px;">
                        <form id="quick_fee_form" onsubmit="submitQuickFeeForm(event)">
                            <div class="row">
                                <div class="col-md-3 col-sm-6 form-group-custom">
                                    <label class="form-label-custom"><i class="fa fa-users text-primary"></i> Group / Scope</label>
                                    <select id="quick_group_id" class="form-control-custom" required>
                                        <option value="0">All Groups (General Default)</option>
                                    </select>
                                </div>

                                <div class="col-md-4 col-sm-6 form-group-custom">
                                    <label class="form-label-custom"><i class="fa fa-cogs text-primary"></i> Fee Calculation Type</label>
                                    <select id="quick_fee_method" class="form-control-custom" onchange="onQuickFeeMethodChange()" required>
                                        <option value="fixed">Fixed Monthly Fee (Flat Amount)</option>
                                        <option value="shuttle_cost">By Shuttle Cost Pro-rata (Based on Sessions)</option>
                                        <option value="shuttle_equal">By Shuttle Cost Equal Split (Divided among Members)</option>
                                    </select>
                                </div>

                                <div class="col-md-2 col-sm-6 form-group-custom">
                                    <label class="form-label-custom" id="quick_fee_label">Monthly Fee (₹)</label>
                                    <div style="position:relative;">
                                        <span style="position:absolute; left:12px; top:12px; font-weight:800; color:#64748b; font-size:15px;">₹</span>
                                        <input type="number" id="quick_fee_amount" class="form-control-custom" style="padding-left:28px; font-weight:800;" placeholder="300" min="0" step="1" required>
                                    </div>
                                </div>

                                <div class="col-md-3 col-sm-6 form-group-custom">
                                    <label class="form-label-custom"><i class="fa fa-calendar text-primary"></i> Effective From</label>
                                    <input type="date" id="quick_from_date" class="form-control-custom" required>
                                </div>
                            </div>

                            <!-- Live Explanation Box -->
                            <div id="quick_method_info" style="background:#f8fafc; border:1px solid #e2e8f0; border-left:4px solid #2563eb; padding:12px 16px; border-radius:10px; margin-top:6px; margin-bottom:18px; display:flex; align-items:center; justify-content:space-between; flex-wrap:wrap; gap:10px;">
                                <div style="display:flex; align-items:center; gap:10px;">
                                    <i class="fa fa-info-circle" id="quick_info_icon" style="color:#2563eb; font-size:16px;"></i>
                                    <span id="quick_info_text" style="font-size:13px; font-weight:600; color:#334155;">
                                        <strong>Fixed Monthly Fee:</strong> Attending members are billed a flat rate of ₹300 per month.
                                    </span>
                                </div>
                                <button type="submit" class="btn-add-fee" id="btn_quick_save" style="padding:9px 24px; font-size:13.5px;">
                                    <i class="fa fa-save"></i> Save Fee Setting
                                </button>
                            </div>
                        </form>
                    </div>
                </div>

                <!-- Fee Rules History Table Card -->
                <div class="fee-card">
                    <div class="fee-card-header">
                        <div class="fee-card-header-left">
                            <div class="fee-icon-badge">
                                <i class="fa fa-history"></i>
                            </div>
                            <div>
                                <h2 class="fee-card-title">Configured Fee Rules History</h2>
                                <p class="fee-card-sub">Active and past fee calculation rules applied during monthly attendance processing</p>
                            </div>
                        </div>
                    </div>

                    <div class="table-responsive">
                        <table class="fee-table">
                            <thead>
                                <tr>
                                    <th style="width: 60px;">#</th>
                                    <th>Group / Batch</th>
                                    <th>Calculation Method</th>
                                    <th>Fee / Base Fee (₹)</th>
                                    <th>Effective From</th>
                                    <th>Status</th>
                                    <th style="text-align: right; width: 120px;">Actions</th>
                                </tr>
                            </thead>
                            <tbody id="tbl_fee_body">
                                <tr>
                                    <td colspan="7" class="fee-empty">
                                        <i class="fa fa-spinner fa-spin"></i>
                                        <p>Loading monthly fee rules...</p>
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>

                    <div id="pagination_container" style="padding: 16px 24px; border-top: 1px solid #f1f5f9;"></div>
                </div>
            </div>
        </div>
    </div>

    <!-- Modal: Add / Edit Fee Rule -->
    <div class="modal fade" id="feeModal" tabindex="-1" role="dialog" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content animated fadeInDown">
                <div class="modal-header-custom">
                    <button type="button" class="close" data-dismiss="modal" onclick="closeFeeModal()" style="color:#fff; opacity:0.8;">&times;</button>
                    <h4 id="modal_title"><i class="fa fa-pencil-square-o"></i> Set Monthly Fee Amount</h4>
                </div>
                <form id="fee_form" onsubmit="submitFeeForm(event)">
                    <div class="modal-body-custom">
                        <div class="form-group-custom">
                            <label class="form-label-custom">Select Group / Scope</label>
                            <select id="modal_group_id" class="form-control-custom" required>
                                <option value="0">All Groups (General Default)</option>
                            </select>
                            <small class="text-muted" style="font-size:11.5px; display:block; margin-top:4px;">
                                Set for all groups or override for a specific batch.
                            </small>
                        </div>

                        <div class="form-group-custom">
                            <label class="form-label-custom">Fee Calculation Method</label>
                            <select id="modal_fee_method" class="form-control-custom" onchange="onFeeMethodChange()" required>
                                <option value="fixed">Fixed Monthly Fee (Flat Amount)</option>
                                <option value="shuttle_cost">By Shuttle Cost Pro-rata (Based on Member Sessions)</option>
                                <option value="shuttle_equal">By Shuttle Cost Equal Split (Divided among Attending Members)</option>
                            </select>
                            <small class="text-muted" id="method_help_text" style="font-size:11.5px; display:block; margin-top:4px;">
                                Fixed flat amount billed to attending members each month.
                            </small>
                        </div>

                        <div class="form-group-custom">
                            <label class="form-label-custom" id="modal_fee_label">Monthly Fee Amount (₹)</label>
                            <div style="position:relative;">
                                <span style="position:absolute; left:14px; top:12px; font-weight:800; color:#64748b; font-size:16px;">₹</span>
                                <input type="number" id="modal_fee_amount" class="form-control-custom" style="padding-left:32px; font-size:16px; font-weight:800;" placeholder="300" min="0" step="1" required>
                            </div>
                            <small class="text-muted" id="modal_fee_subtext" style="font-size:11.5px; display:block; margin-top:4px;">
                                Enter the fixed amount charged per member per month.
                            </small>
                        </div>

                        <div class="form-group-custom">
                            <label class="form-label-custom">Effective From Date</label>
                            <input type="date" id="modal_from_date" class="form-control-custom" required>
                            <small class="text-muted" style="font-size:11.5px; display:block; margin-top:4px;">
                                <i class="fa fa-info-circle"></i> This fee rule will be active from this date onwards until updated.
                            </small>
                        </div>
                    </div>
                    <div class="modal-footer-custom">
                        <button type="button" class="btn-modal-cancel" data-dismiss="modal" onclick="closeFeeModal()">Cancel</button>
                        <button type="submit" class="btn-modal-save" id="btn_modal_submit"><i class="fa fa-save"></i> Save Fee Rule</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <script>
        var cachedGroups = [];

        $(document).ready(function() {
            loadMenu();
            loadGroupsDropdown();
            initQuickFeeForm();
            loadFeeData(1);
        });

        function initQuickFeeForm() {
            var today = new Date();
            var yr = today.getFullYear();
            var mo = String(today.getMonth() + 1).padStart(2, '0');
            $('#quick_from_date').val(yr + '-' + mo + '-01');
            $('#quick_fee_amount').val('300');
            $('#quick_fee_method').val('fixed');
            onQuickFeeMethodChange();
        }

        function onQuickFeeMethodChange() {
            var method = $('#quick_fee_method').val();
            if (method === 'shuttle_cost') {
                $('#quick_fee_label').text('Base Fee (₹) [Optional]');
                $('#quick_fee_amount').attr('placeholder', '0').attr('min', '0');
                if ($('#quick_fee_amount').val() === '300') $('#quick_fee_amount').val('0');
                $('#quick_info_icon').attr('class', 'fa fa-bolt').css('color', '#6d28d9');
                $('#quick_method_info').css('border-left-color', '#6d28d9').css('background', '#f5f3ff');
                $('#quick_info_text').html('<strong>Shuttle Cost (Pro-rata):</strong> Monthly dues = <em>(Total Monthly Shuttle Expense ÷ Total Attended Sessions) × Member Attended Days</em> + Base Fee.');
            } else if (method === 'shuttle_equal') {
                $('#quick_fee_label').text('Base Fee (₹) [Optional]');
                $('#quick_fee_amount').attr('placeholder', '0').attr('min', '0');
                if ($('#quick_fee_amount').val() === '300') $('#quick_fee_amount').val('0');
                $('#quick_info_icon').attr('class', 'fa fa-pie-chart').css('color', '#b45309');
                $('#quick_method_info').css('border-left-color', '#f59e0b').css('background', '#fffbeb');
                $('#quick_info_text').html('<strong>Shuttle Cost (Equal Split):</strong> Monthly dues = <em>(Total Monthly Shuttle Expense ÷ Attending Members Count)</em> + Base Fee.');
            } else {
                $('#quick_fee_label').text('Monthly Fee (₹)');
                $('#quick_fee_amount').attr('placeholder', '300').attr('min', '1');
                if ($('#quick_fee_amount').val() === '0') $('#quick_fee_amount').val('300');
                $('#quick_info_icon').attr('class', 'fa fa-info-circle').css('color', '#2563eb');
                $('#quick_method_info').css('border-left-color', '#2563eb').css('background', '#f8fafc');
                var curFee = $('#quick_fee_amount').val() || '300';
                $('#quick_info_text').html('<strong>Fixed Monthly Fee:</strong> Attending members are billed a flat rate of ₹' + curFee + ' per month.');
            }
        }

        function submitQuickFeeForm(e) {
            e.preventDefault();
            var groupId = $('#quick_group_id').val();
            var feeMethod = $('#quick_fee_method').val();
            var fee = $('#quick_fee_amount').val();
            var fromDate = $('#quick_from_date').val();

            if (feeMethod === 'fixed' && (!fee || parseFloat(fee) <= 0)) {
                swal("Invalid Amount", "Please enter a valid monthly fee amount greater than 0.", "warning");
                return;
            }

            if (feeMethod !== 'fixed' && (fee === '' || parseFloat(fee) < 0)) {
                swal("Invalid Amount", "Base fee cannot be negative. Enter 0 if no extra base fee.", "warning");
                return;
            }

            if (!fromDate) {
                swal("Invalid Date", "Please select an Effective From date.", "warning");
                return;
            }

            var postData = {
                action: 'save_new_fees',
                group_id: groupId,
                fee_method: feeMethod,
                fees: fee || 0,
                from_date: fromDate
            };

            $('#btn_quick_save').prop('disabled', true).html('<i class="fa fa-spinner fa-spin"></i> Saving...');

            $.ajax({
                type: "POST",
                url: "api/fees.php",
                data: postData,
                success: function(res) {
                    $('#btn_quick_save').prop('disabled', false).html('<i class="fa fa-save"></i> Save Fee Setting');
                    try {
                        var resp = JSON.parse(res);
                        if (resp.status === 'success') {
                            alertsuccess(resp.message || 'Saved successfully');
                            loadFeeData(1);
                        } else {
                            alerterror(resp.message || 'Failed to save.');
                        }
                    } catch(ex) {
                        alertsuccess('Fee rule saved successfully.');
                        loadFeeData(1);
                    }
                },
                error: function(xhr) {
                    $('#btn_quick_save').prop('disabled', false).html('<i class="fa fa-save"></i> Save Fee Setting');
                    try {
                        var err = JSON.parse(xhr.responseText);
                        alerterror(err.message || 'Failed to save fee rule.');
                    } catch(e) {
                        alerterror('Failed to save fee rule.');
                    }
                }
            });
        }

        function onFeeMethodChange() {
            var method = $('#modal_fee_method').val();
            if (method === 'shuttle_cost') {
                $('#modal_fee_label').text('Base / Maintenance Fee (₹) [Optional]');
                $('#modal_fee_amount').attr('placeholder', '0').attr('min', '0');
                $('#modal_fee_subtext').text('Group monthly shuttle cock cost will be divided by total sessions and charged pro-rata per member. Base fee is added on top.');
                $('#method_help_text').text('Calculates fees based on actual shuttlecock usage divided proportionally by attendance sessions.');
            } else if (method === 'shuttle_equal') {
                $('#modal_fee_label').text('Base / Maintenance Fee (₹) [Optional]');
                $('#modal_fee_amount').attr('placeholder', '0').attr('min', '0');
                $('#modal_fee_subtext').text('Group monthly shuttle cock cost will be divided equally among all attending members. Base fee is added on top.');
                $('#method_help_text').text('Total monthly shuttlecock expense is split equally among all members who attended that month.');
            } else {
                $('#modal_fee_label').text('Monthly Fee Amount (₹)');
                $('#modal_fee_amount').attr('placeholder', '300').attr('min', '1');
                $('#modal_fee_subtext').text('Enter the fixed amount charged per member per month.');
                $('#method_help_text').text('Fixed flat amount billed to attending members each month.');
            }
        }

        function loadGroupsDropdown() {
            $.ajax({
                type: "POST",
                url: "api/fees.php",
                data: { action: 'load_groups' },
                success: function(data) {
                    try {
                        var obj = JSON.parse(data);
                        var groups = obj[0] || [];
                        cachedGroups = groups;
                        var isSuperAdmin = <?php echo isSuperAdmin($login_id) ? 'true' : 'false'; ?>;
                        var htm = '';
                        if (isSuperAdmin) {
                            htm += '<option value="0">All Groups (General Default)</option>';
                        }
                        for (var i = 0; i < groups.length; i++) {
                            htm += '<option value="' + groups[i].id + '">' + groups[i].name + '</option>';
                        }
                        if (!isSuperAdmin && groups.length === 0) {
                            htm = '<option value="0">Default Group</option>';
                        }
                        $('#quick_group_id').html(htm);
                        $('#modal_group_id').html(htm);
                    } catch(e) {
                        console.error('Error parsing groups:', e);
                    }
                }
            });
        }

        function loadFeeData(page) {
            $('#hdn_current_page').val(page);
            $.ajax({
                type: "POST",
                url: "api/fees.php",
                data: { action: 'load_data', page: page },
                success: function(data) {
                    try {
                        var obj = JSON.parse(data);
                        var meta = obj[0] || {};
                        var rows = obj[1] || [];
                        var totalRows = meta.total_rows || 0;
                        var activeFee = meta.active_fee || 300;

                        $('#stat_active_fee').text('₹' + activeFee);
                        $('#stat_total_rules').text(totalRows);

                        if (rows.length === 0) {
                            $('#tbl_fee_body').html('<tr><td colspan="7" class="fee-empty"><i class="fa fa-inr"></i><p>No fee configuration rules found. Click "+ Set Fee Amount" to add one.</p></td></tr>');
                            $('#pagination_container').empty();
                            return;
                        }

                        var tbodyHtml = '';
                        for (var i = 0; i < rows.length; i++) {
                            var r = rows[i];
                            var idx = ((page - 1) * 10) + (i + 1);
                            var isGeneral = (parseInt(r.group_id, 10) === 0);
                            var grpBadge = isGeneral 
                                ? '<span class="badge-group" style="background:#f1f5f9; color:#475569; border-color:#e2e8f0;"><i class="fa fa-globe"></i> All Groups</span>'
                                : '<span class="badge-group"><i class="fa fa-users"></i> ' + r.group_name + '</span>';

                            var methodCls = (r.fee_method === 'shuttle_cost') ? 'shuttle' : ((r.fee_method === 'shuttle_equal') ? 'shuttle-equal' : 'fixed');
                            var methodIcon = (r.fee_method === 'shuttle_cost') ? 'fa fa-bolt' : ((r.fee_method === 'shuttle_equal') ? 'fa fa-pie-chart' : 'fa fa-tag');
                            var methodBadge = '<span class="badge-method ' + methodCls + '"><i class="' + methodIcon + '"></i> ' + (r.method_label || 'Fixed Fee') + '</span>';

                            var amountHtml = '';
                            if (r.fee_method === 'shuttle_cost') {
                                amountHtml = '<span class="badge-amount" style="background:#f5f3ff; color:#6d28d9; border-color:#ddd6fe;"><i class="fa fa-bolt"></i> Shuttle Pro-rata' + (parseFloat(r.fee) > 0 ? ' + ₹' + r.fee : '') + '</span>';
                            } else if (r.fee_method === 'shuttle_equal') {
                                amountHtml = '<span class="badge-amount" style="background:#fffbeb; color:#b45309; border-color:#fde68a;"><i class="fa fa-pie-chart"></i> Shuttle Equal' + (parseFloat(r.fee) > 0 ? ' + ₹' + r.fee : '') + '</span>';
                            } else {
                                amountHtml = '<span class="badge-amount"><i class="fa fa-inr"></i> ' + r.fee + '</span>';
                            }

                            var statusCls = r.status || 'active';
                            var statusLabel = 'Active';
                            var statusIcon = 'fa fa-check-circle';
                            if (statusCls === 'active') {
                                statusLabel = 'Current Active';
                                statusIcon = 'fa fa-check-circle';
                            } else if (statusCls === 'upcoming') {
                                statusLabel = 'Upcoming';
                                statusIcon = 'fa fa-clock-o';
                            } else {
                                statusLabel = 'Replaced';
                                statusIcon = 'fa fa-history';
                            }

                            var statusBadge = '<span class="badge-status ' + statusCls + '"><i class="' + statusIcon + '"></i> ' + statusLabel + '</span>';

                            var escapedMethod = (r.fee_method || 'fixed').replace(/'/g, "\\'");

                            tbodyHtml += '<tr>';
                            tbodyHtml += '<td><strong>' + idx + '</strong></td>';
                            tbodyHtml += '<td>' + grpBadge + '</td>';
                            tbodyHtml += '<td>' + methodBadge + '</td>';
                            tbodyHtml += '<td>' + amountHtml + '</td>';
                            tbodyHtml += '<td><strong style="color:#0f172a;">' + formatDate(r.from_date) + '</strong></td>';
                            tbodyHtml += '<td>' + statusBadge + '</td>';
                            tbodyHtml += '<td style="text-align: right;">';
                            tbodyHtml += '<button type="button" class="btn-act btn-act-edit" title="Edit Rule" onclick="openEditFeeModal(' + r.id + ', ' + r.group_id + ', ' + r.fee + ', \'' + r.from_date + '\', \'' + escapedMethod + '\')"><i class="fa fa-pencil"></i></button>';
                            tbodyHtml += '<button type="button" class="btn-act btn-act-del" title="Delete Rule" onclick="confirmDeleteFee(' + r.id + ')"><i class="fa fa-trash"></i></button>';
                            tbodyHtml += '</td>';
                            tbodyHtml += '</tr>';
                        }

                        $('#tbl_fee_body').html(tbodyHtml);

                        var pageHtml = paginate(totalRows, page);
                        $('#pagination_container').html(pageHtml);
                    } catch(e) {
                        console.error('Error parsing load_data:', e);
                        $('#tbl_fee_body').html('<tr><td colspan="7" class="fee-empty"><i class="fa fa-exclamation-triangle" style="color:#ef4444;"></i><p>Failed to load fee configuration data.</p></td></tr>');
                    }
                },
                error: function(xhr, status, error) {
                    console.error('AJAX error:', error);
                    $('#tbl_fee_body').html('<tr><td colspan="7" class="fee-empty"><i class="fa fa-exclamation-triangle" style="color:#ef4444;"></i><p>Network error loading fee rules.</p></td></tr>');
                }
            });
        }

        function formatDate(dateStr) {
            if (!dateStr) return '—';
            var d = new Date(dateStr);
            if (isNaN(d.getTime())) return dateStr;
            var months = ['Jan','Feb','Mar','Apr','May','Jun','Jul','Aug','Sep','Oct','Nov','Dec'];
            var day = d.getDate() < 10 ? '0' + d.getDate() : d.getDate();
            return day + ' ' + months[d.getMonth()] + ' ' + d.getFullYear();
        }

        function openAddFeeModal() {
            $('#hdn_id').val('0');
            $('#modal_title').html('<i class="fa fa-plus-circle"></i> Set Monthly Fee Amount');
            $('#modal_group_id').val('0');
            $('#modal_fee_method').val('fixed');
            onFeeMethodChange();
            $('#modal_fee_amount').val('300');
            
            var today = new Date();
            var yr = today.getFullYear();
            var mo = String(today.getMonth() + 1).padStart(2, '0');
            $('#modal_from_date').val(yr + '-' + mo + '-01');
            
            if (typeof $.fn.modal === 'function') {
                $('#feeModal').modal('show');
            } else {
                $('#feeModal').addClass('in').css('display', 'block');
                $('body').addClass('modal-open');
                if ($('#fee_backdrop').length === 0) {
                    $('body').append('<div class="modal-backdrop fade in" id="fee_backdrop" onclick="closeFeeModal()"></div>');
                }
            }
        }

        function openEditFeeModal(id, groupId, fee, fromDate, method) {
            $('#hdn_id').val(id);
            $('#modal_title').html('<i class="fa fa-pencil-square-o"></i> Edit Monthly Fee Rule');
            $('#modal_group_id').val(groupId);
            $('#modal_fee_method').val(method || 'fixed');
            onFeeMethodChange();
            $('#modal_fee_amount').val(fee);
            $('#modal_from_date').val(fromDate);
            
            if (typeof $.fn.modal === 'function') {
                $('#feeModal').modal('show');
            } else {
                $('#feeModal').addClass('in').css('display', 'block');
                $('body').addClass('modal-open');
                if ($('#fee_backdrop').length === 0) {
                    $('body').append('<div class="modal-backdrop fade in" id="fee_backdrop" onclick="closeFeeModal()"></div>');
                }
            }
        }

        function closeFeeModal() {
            if (typeof $.fn.modal === 'function') {
                $('#feeModal').modal('hide');
            }
            $('#feeModal').removeClass('in').css('display', 'none');
            $('#fee_backdrop, .modal-backdrop').remove();
            $('body').removeClass('modal-open');
        }

        function submitFeeForm(e) {
            e.preventDefault();
            var id = parseInt($('#hdn_id').val(), 10) || 0;
            var action = (id > 0) ? 'save_fees' : 'save_new_fees';
            var groupId = $('#modal_group_id').val();
            var feeMethod = $('#modal_fee_method').val();
            var fee = $('#modal_fee_amount').val();
            var fromDate = $('#modal_from_date').val();

            if (feeMethod === 'fixed' && (!fee || parseFloat(fee) <= 0)) {
                swal("Invalid Amount", "Please enter a valid monthly fee amount greater than 0.", "warning");
                return;
            }

            if (feeMethod !== 'fixed' && (fee === '' || parseFloat(fee) < 0)) {
                swal("Invalid Amount", "Base fee cannot be negative. Enter 0 if no extra base fee.", "warning");
                return;
            }

            if (!fromDate) {
                swal("Invalid Date", "Please select an Effective From date.", "warning");
                return;
            }

            var postData = {
                action: action,
                id: id,
                group_id: groupId,
                fee_method: feeMethod,
                fees: fee || 0,
                from_date: fromDate
            };

            $.ajax({
                type: "POST",
                url: "api/fees.php",
                data: postData,
                success: function(res) {
                    try {
                        var resp = JSON.parse(res);
                        if (resp.status === 'success') {
                            closeFeeModal();
                            alertsuccess(resp.message || 'Saved successfully');
                            loadFeeData($('#hdn_current_page').val());
                        } else {
                            alerterror(resp.message || 'Failed to save.');
                        }
                    } catch(ex) {
                        closeFeeModal();
                        alertsuccess('Fee rule saved successfully.');
                        loadFeeData($('#hdn_current_page').val());
                    }
                },
                error: function(xhr) {
                    try {
                        var err = JSON.parse(xhr.responseText);
                        alerterror(err.message || 'Failed to save fee rule.');
                    } catch(e) {
                        alerterror('Failed to save fee rule.');
                    }
                }
            });
        }

        function confirmDeleteFee(id) {
            swal({
                title: "Delete Fee Rule?",
                text: "Are you sure you want to remove this monthly fee rule?",
                type: "warning",
                showCancelButton: true,
                confirmButtonColor: "#ef4444",
                confirmButtonText: "Yes, Delete",
                cancelButtonText: "Cancel",
                closeOnConfirm: true
            }, function(isConfirm) {
                if (isConfirm) {
                    $.ajax({
                        type: "POST",
                        url: "api/fees.php",
                        data: { action: 'delete_fee_details', id: id },
                        success: function(res) {
                            try {
                                var resp = JSON.parse(res);
                                if (resp.status === 'success') {
                                    alertwarning(resp.message || 'Fee rule deleted.');
                                    loadFeeData($('#hdn_current_page').val());
                                } else {
                                    alerterror(resp.message || 'Failed to delete.');
                                }
                            } catch(e) {
                                alertwarning('Fee rule deleted.');
                                loadFeeData($('#hdn_current_page').val());
                            }
                        },
                        error: function(xhr) {
                            try {
                                var err = JSON.parse(xhr.responseText);
                                alerterror(err.message || 'Failed to delete.');
                            } catch(e) {
                                alerterror('Failed to delete fee rule.');
                            }
                        }
                    });
                }
            });
        }
    </script>
</body>
</html>
