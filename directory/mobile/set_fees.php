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

$is_admin = isSuperAdmin($login_id) || isGroupAdmin($login_id);
if (!$is_admin) {
    header("Location: home.php");
    exit();
}

$allowed_groups = getUserAllowedGroupIds($login_id);
$primary_role = getUserPrimaryRoleName($login_id);
$active_tab = 'accounts';

// Fetch groups for dropdown
if (in_array('ALL', $allowed_groups, true)) {
    $res_grp = app_exec_query("SELECT id, name FROM tbl_groups WHERE status = 1 ORDER BY name ASC");
} else {
    if (!empty($allowed_groups)) {
        $placeholders = implode(',', array_fill(0, count($allowed_groups), '?'));
        $sql_grp = "SELECT id, name FROM tbl_groups WHERE status = 1 AND id IN ($placeholders) ORDER BY name ASC";
        $res_grp = app_exec_getresult($sql_grp, $allowed_groups, str_repeat('i', count($allowed_groups)));
    } else {
        $res_grp = false;
    }
}

$groups = [];
if ($res_grp) {
    while ($g = $res_grp->fetch_assoc()) {
        $groups[] = $g;
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
    <title>Set Monthly Fees - YMCA Mobile</title>
    
    <link href="../../css/bootstrap.min.css" rel="stylesheet">
    <link href="../../font-awesome/css/font-awesome.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <link href="../../css/plugins/sweetalert/sweetalert.css" rel="stylesheet">
    <link href="mobile_layout.css" rel="stylesheet">

    <style>
        body { font-family: 'Plus Jakarta Sans', sans-serif !important; background: #f8fafc !important; }
        
        .acc-hero {
            background: linear-gradient(135deg, #059669 0%, #10b981 100%);
            border-radius: 20px;
            padding: 20px;
            color: #ffffff;
            margin-bottom: 16px;
            box-shadow: 0 10px 25px rgba(16, 185, 129, 0.22);
        }
        .acc-hero h2 { margin: 0 0 4px 0; font-weight: 800; font-size: 20px; }
        .acc-hero p { margin: 0; font-size: 12.5px; opacity: 0.92; }

        .stat-grid-sm {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 12px;
            margin-bottom: 16px;
        }
        .stat-card-sm {
            background: #ffffff;
            border: 1px solid #e2e8f0;
            border-radius: 16px;
            padding: 14px 16px;
            box-shadow: 0 2px 6px rgba(0,0,0,0.02);
            display: flex;
            align-items: center;
            gap: 12px;
        }
        .stat-icon-sm {
            width: 38px;
            height: 38px;
            border-radius: 12px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 16px;
            color: #ffffff;
            flex-shrink: 0;
        }
        .stat-info-sm h4 { margin: 0 0 2px 0; font-size: 17px; font-weight: 800; color: #0f172a; }
        .stat-info-sm p { margin: 0; font-size: 11px; font-weight: 700; color: #64748b; text-transform: uppercase; }

        .search-box { position: relative; margin-bottom: 14px; }
        .search-box input {
            width: 100%; height: 42px; border-radius: 12px; border: 1px solid #e2e8f0;
            padding: 0 14px 0 38px; font-size: 13px; font-weight: 600; color: #1e293b;
            background: #ffffff; box-shadow: 0 2px 6px rgba(0,0,0,0.02); outline: none;
        }
        .search-box i { position: absolute; left: 14px; top: 13px; color: #94a3b8; font-size: 14px; }

        .fee-card {
            background: #ffffff;
            border-radius: 16px;
            border: 1px solid #e2e8f0;
            padding: 16px;
            margin-bottom: 12px;
            box-shadow: 0 2px 8px rgba(0,0,0,0.03);
            transition: all 0.2s ease;
        }
        .fee-card-head {
            display: flex;
            justify-content: space-between;
            align-items: flex-start;
            margin-bottom: 10px;
        }
        .fee-grp-title {
            font-size: 14px;
            font-weight: 800;
            color: #0f172a;
            display: flex;
            align-items: center;
            gap: 6px;
        }
        .fee-amount-badge {
            font-size: 16px;
            font-weight: 800;
            color: #059669;
            background: #ecfdf5;
            border: 1px solid #a7f3d0;
            padding: 4px 10px;
            border-radius: 10px;
        }
        .fee-meta-row {
            display: flex;
            justify-content: space-between;
            align-items: center;
            font-size: 12px;
            color: #64748b;
            margin-bottom: 12px;
        }
        .fee-actions {
            display: flex;
            gap: 8px;
            border-top: 1px dashed #e2e8f0;
            padding-top: 10px;
            justify-content: flex-end;
        }
        .btn-act-mob {
            border: none;
            border-radius: 8px;
            padding: 6px 12px;
            font-size: 11.5px;
            font-weight: 700;
            display: inline-flex;
            align-items: center;
            gap: 5px;
            cursor: pointer;
            transition: all 0.15s;
        }
        .btn-act-mob-edit { background: #eff6ff; color: #2563eb; }
        .btn-act-mob-del { background: #fef2f2; color: #dc2626; }

        .badge-status-mob {
            font-size: 10.5px;
            font-weight: 800;
            padding: 3px 8px;
            border-radius: 6px;
            text-transform: uppercase;
        }
        .badge-active { background: #ecfdf5; color: #047857; }
        .badge-upcoming { background: #eff6ff; color: #1d4ed8; }
        .badge-replaced { background: #f1f5f9; color: #64748b; }

        .modal-content-custom {
            border-radius: 20px;
            border: none;
            box-shadow: 0 20px 40px rgba(0,0,0,0.15);
        }
        .form-group-custom { margin-bottom: 14px; }
        .form-group-custom label {
            font-size: 11.5px; font-weight: 800; color: #475569; text-transform: uppercase; margin-bottom: 6px; display: block;
        }
        .form-group-custom input, .form-group-custom select {
            width: 100%; height: 44px; border-radius: 12px; border: 1px solid #cbd5e1;
            padding: 0 14px; font-size: 13.5px; font-weight: 600; color: #0f172a; outline: none; background: #ffffff;
        }
    </style>
</head>
<body class="mob-body">

    <!-- Header -->
    <header class="mob-header">
        <div class="mob-header-brand">
            <a href="accounts.php" style="color:#ffffff; margin-right:10px; font-size:18px;"><i class="fa fa-arrow-left"></i></a>
            <div class="mob-header-title">
                Set Monthly <span>Fees</span>
            </div>
        </div>
        <div class="mob-header-actions">
            <a href="../../app_login_manager/logout.php" class="mob-header-btn" title="Logout"><i class="fa fa-sign-out"></i></a>
        </div>
    </header>

    <div class="mob-page">

        <!-- Hero Card -->
        <div class="acc-hero" style="display:flex; justify-content:space-between; align-items:center;">
            <div>
                <h2>Monthly Fee Master</h2>
                <p>Configure fee rates applied during attendance dues processing</p>
            </div>
            <button class="btn btn-light btn-sm" style="border-radius:12px; font-weight:800; color:#059669; padding:8px 14px; box-shadow: 0 4px 10px rgba(0,0,0,0.1); flex-shrink:0; margin-left:10px;" onclick="openAddFeeModal()">
                <i class="fa fa-plus-circle"></i> Set Fee
            </button>
        </div>

        <!-- Summary Stats -->
        <div class="stat-grid-sm">
            <div class="stat-card-sm">
                <div class="stat-icon-sm" style="background:linear-gradient(135deg, #10b981, #059669);"><i class="fa fa-inr"></i></div>
                <div class="stat-info-sm">
                    <h4 id="stat_active_fee">₹300</h4>
                    <p>Active Default</p>
                </div>
            </div>
            <div class="stat-card-sm">
                <div class="stat-icon-sm" style="background:linear-gradient(135deg, #6366f1, #4f46e5);"><i class="fa fa-calendar"></i></div>
                <div class="stat-info-sm">
                    <h4 id="stat_total_rules">0</h4>
                    <p>Configured Rules</p>
                </div>
            </div>
        </div>

        <!-- Search Bar -->
        <div class="search-box">
            <i class="fa fa-search"></i>
            <input type="text" id="search_fees" placeholder="Search by group or date..." onkeyup="filterFeeCards()">
        </div>

        <!-- Fees Cards Container -->
        <div id="fees_container">
            <div style="text-align:center; padding:40px 20px; color:#94a3b8;">
                <i class="fa fa-spinner fa-spin fa-2x" style="margin-bottom:10px;"></i>
                <p>Loading monthly fee configuration...</p>
            </div>
        </div>

    </div>

    <!-- Modal: Add New Fee Rule -->
    <div class="modal fade" id="addFeeModal" tabindex="-1" role="dialog" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered" role="document">
            <div class="modal-content modal-content-custom">
                <div class="modal-header" style="border-bottom:1px solid #f1f5f9; padding:16px 20px;">
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close" style="font-size:24px;">
                        <span aria-hidden="true">&times;</span>
                    </button>
                    <h4 class="modal-title" style="font-weight:800; color:#0f172a; font-size:16px;">
                        <i class="fa fa-plus-circle" style="color:#10b981; margin-right:6px;"></i> Set Monthly Fee Rule
                    </h4>
                </div>
                <form id="form_add_fee" onsubmit="submitNewFee(event)">
                    <div class="modal-body" style="padding:20px;">
                        <div class="form-group-custom">
                            <label>Group / Batch</label>
                            <select id="add_group_id" name="group_id" required>
                                <?php if (isSuperAdmin($login_id)): ?>
                                    <option value="0">All Groups (General Default)</option>
                                <?php endif; ?>
                                <?php foreach ($groups as $g): ?>
                                    <option value="<?php echo $g['id']; ?>"><?php echo htmlspecialchars($g['name']); ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>

                        <div class="form-group-custom">
                            <label>Calculation Method</label>
                            <select id="add_fee_method" name="fee_method" onchange="onMobileAddMethodChange()" required>
                                <option value="fixed">Fixed Monthly Fee (Flat Amount)</option>
                                <option value="shuttle_cost">By Shuttle Cost Pro-rata (Based on Sessions)</option>
                                <option value="shuttle_equal">By Shuttle Cost Equal Split (Divided among Members)</option>
                            </select>
                        </div>

                        <div class="form-group-custom">
                            <label id="add_fee_label">Monthly Fee Amount (₹)</label>
                            <input type="number" id="add_fee_amount" name="fees" placeholder="e.g. 300" min="0" required>
                            <small class="text-muted" id="add_fee_subtext" style="font-size:11px; display:block; margin-top:3px;">
                                Fixed amount charged per member per month.
                            </small>
                        </div>

                        <div class="form-group-custom">
                            <label>Effective From Date</label>
                            <input type="date" id="add_from_date" name="from_date" value="<?php echo date('Y-m-01'); ?>" required>
                        </div>
                    </div>
                    <div class="modal-footer" style="border-top:1px solid #f1f5f9; padding:14px 20px; display:flex; justify-content:flex-end; gap:8px;">
                        <button type="button" class="btn btn-default" data-dismiss="modal" style="border-radius:10px; font-weight:700;">Cancel</button>
                        <button type="submit" class="btn btn-primary" style="background:#059669; border:none; border-radius:10px; font-weight:800; padding:8px 18px;">
                            <i class="fa fa-save"></i> Save Fee Rule
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Modal: Edit Fee Rule -->
    <div class="modal fade" id="editFeeModal" tabindex="-1" role="dialog" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered" role="document">
            <div class="modal-content modal-content-custom">
                <div class="modal-header" style="border-bottom:1px solid #f1f5f9; padding:16px 20px;">
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close" style="font-size:24px;">
                        <span aria-hidden="true">&times;</span>
                    </button>
                    <h4 class="modal-title" style="font-weight:800; color:#0f172a; font-size:16px;">
                        <i class="fa fa-pencil" style="color:#2563eb; margin-right:6px;"></i> Edit Fee Rule
                    </h4>
                </div>
                <form id="form_edit_fee" onsubmit="submitEditFee(event)">
                    <input type="hidden" id="edit_fee_id" name="id">
                    <div class="modal-body" style="padding:20px;">
                        <div class="form-group-custom">
                            <label>Group / Batch</label>
                            <select id="edit_group_id" name="group_id" required>
                                <?php if (isSuperAdmin($login_id)): ?>
                                    <option value="0">All Groups (General Default)</option>
                                <?php endif; ?>
                                <?php foreach ($groups as $g): ?>
                                    <option value="<?php echo $g['id']; ?>"><?php echo htmlspecialchars($g['name']); ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>

                        <div class="form-group-custom">
                            <label>Calculation Method</label>
                            <select id="edit_fee_method" name="fee_method" onchange="onMobileEditMethodChange()" required>
                                <option value="fixed">Fixed Monthly Fee (Flat Amount)</option>
                                <option value="shuttle_cost">By Shuttle Cost Pro-rata (Based on Sessions)</option>
                                <option value="shuttle_equal">By Shuttle Cost Equal Split (Divided among Members)</option>
                            </select>
                        </div>

                        <div class="form-group-custom">
                            <label id="edit_fee_label">Monthly Fee Amount (₹)</label>
                            <input type="number" id="edit_fee_amount" name="fees" min="0" required>
                            <small class="text-muted" id="edit_fee_subtext" style="font-size:11px; display:block; margin-top:3px;">
                                Fixed amount charged per member per month.
                            </small>
                        </div>

                        <div class="form-group-custom">
                            <label>Effective From Date</label>
                            <input type="date" id="edit_from_date" name="from_date" required>
                        </div>
                    </div>
                    <div class="modal-footer" style="border-top:1px solid #f1f5f9; padding:14px 20px; display:flex; justify-content:flex-end; gap:8px;">
                        <button type="button" class="btn btn-default" data-dismiss="modal" style="border-radius:10px; font-weight:700;">Cancel</button>
                        <button type="submit" class="btn btn-primary" style="background:#2563eb; border:none; border-radius:10px; font-weight:800; padding:8px 18px;">
                            <i class="fa fa-check"></i> Update Rule
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Mobile Bottom Navigation (5 Tabs) -->
    <?php include 'mobile_bottom_nav.php'; ?>

    <script src="../../js/jquery-3.1.1.min.js"></script>
    <script src="../../js/bootstrap.min.js"></script>
    <script src="../../js/plugins/sweetalert/sweetalert.min.js"></script>

    <script>
    var cachedFeeRows = [];

    $(document).ready(function() {
        loadFeeData();
    });

    function onMobileAddMethodChange() {
        var m = $('#add_fee_method').val();
        if (m === 'shuttle_cost' || m === 'shuttle_equal') {
            $('#add_fee_label').text('Base / Maintenance Fee (₹) [Optional]');
            $('#add_fee_amount').attr('placeholder', '0').val('0');
            $('#add_fee_subtext').text('Shuttlecock usage for the month will be dynamically computed. Enter base fee if applicable.');
        } else {
            $('#add_fee_label').text('Monthly Fee Amount (₹)');
            $('#add_fee_amount').attr('placeholder', '300').val('300');
            $('#add_fee_subtext').text('Fixed amount charged per member per month.');
        }
    }

    function onMobileEditMethodChange() {
        var m = $('#edit_fee_method').val();
        if (m === 'shuttle_cost' || m === 'shuttle_equal') {
            $('#edit_fee_label').text('Base / Maintenance Fee (₹) [Optional]');
            $('#edit_fee_subtext').text('Shuttlecock usage for the month will be dynamically computed. Enter base fee if applicable.');
        } else {
            $('#edit_fee_label').text('Monthly Fee Amount (₹)');
            $('#edit_fee_subtext').text('Fixed amount charged per member per month.');
        }
    }

    function loadFeeData() {
        $.post('../api/fees.php', { action: 'load_data', page: 1 }, function(res) {
            try {
                var obj = typeof res === 'string' ? JSON.parse(res) : res;
                var meta = obj[0] || {};
                var rows = obj[1] || [];
                cachedFeeRows = rows;

                $('#stat_active_fee').text('₹' + (meta.active_fee || 300));
                $('#stat_total_rules').text(meta.total_rows || rows.length);

                renderFeeCards(rows);
            } catch(e) {
                console.error(e);
                $('#fees_container').html('<div style="text-align:center; padding:30px; color:#ef4444;">Failed to load fee configurations.</div>');
            }
        }).fail(function() {
            $('#fees_container').html('<div style="text-align:center; padding:30px; color:#ef4444;">Server connection failed.</div>');
        });
    }

    function formatDate(dateStr) {
        if (!dateStr) return '';
        var d = new Date(dateStr + 'T00:00:00');
        if (isNaN(d.getTime())) return dateStr;
        return d.toLocaleDateString('en-GB', { day: '2-digit', month: 'short', year: 'numeric' });
    }

    function renderFeeCards(rows) {
        if (!rows || rows.length === 0) {
            $('#fees_container').html(`
                <div style="text-align:center; padding:40px 20px; background:#fff; border-radius:16px; border:1px dashed #cbd5e1;">
                    <i class="fa fa-inr" style="font-size:32px; color:#cbd5e1; margin-bottom:10px; display:block;"></i>
                    <p style="font-size:13.5px; font-weight:700; color:#64748b; margin:0;">No fee rules configured.</p>
                    <p style="font-size:12px; color:#94a3b8; margin-top:4px;">Click "+ Set Fee" above to add a monthly fee amount.</p>
                </div>
            `);
            return;
        }

        var html = '';
        rows.forEach(function(r) {
            var isAll = (parseInt(r.group_id, 10) === 0);
            var grpName = isAll ? 'All Groups (General)' : (r.group_name || 'Group #' + r.group_id);
            var grpIcon = isAll ? 'fa-globe' : 'fa-users';

            var status = r.status || 'active';
            var statusLabel = 'Active';
            var statusClass = 'badge-active';
            if (status === 'upcoming') {
                statusLabel = 'Upcoming';
                statusClass = 'badge-upcoming';
            } else if (status === 'replaced') {
                statusLabel = 'Replaced';
                statusClass = 'badge-replaced';
            }

            var methodBadgeText = (r.fee_method === 'shuttle_cost') ? 'Shuttle Pro-rata' : ((r.fee_method === 'shuttle_equal') ? 'Shuttle Equal' : 'Fixed Fee');
            var methodBg = (r.fee_method === 'shuttle_cost') ? '#ede9fe; color:#6d28d9;' : ((r.fee_method === 'shuttle_equal') ? '#fef3c7; color:#b45309;' : '#f1f5f9; color:#475569;');

            var amountBadgeText = '₹' + r.fee + ' <span style="font-size:11px; font-weight:600; color:#64748b;">/ mo</span>';
            if (r.fee_method === 'shuttle_cost') {
                amountBadgeText = '<span style="font-size:12px; color:#6d28d9;"><i class="fa fa-bolt"></i> Shuttle Cost' + (parseFloat(r.fee) > 0 ? ' + ₹' + r.fee : '') + '</span>';
            } else if (r.fee_method === 'shuttle_equal') {
                amountBadgeText = '<span style="font-size:12px; color:#b45309;"><i class="fa fa-pie-chart"></i> Shuttle Equal' + (parseFloat(r.fee) > 0 ? ' + ₹' + r.fee : '') + '</span>';
            }

            var escapedMethod = (r.fee_method || 'fixed').replace(/'/g, "\\'");
            var searchString = (grpName + ' ' + r.fee + ' ' + methodBadgeText + ' ' + r.from_date + ' ' + formatDate(r.from_date)).toLowerCase();

            html += `
                <div class="fee-card fee-item-card" data-search="${searchString}">
                    <div class="fee-card-head">
                        <div>
                            <div class="fee-grp-title">
                                <i class="fa ${grpIcon}" style="color:#6366f1;"></i>
                                <span>${grpName}</span>
                            </div>
                            <span style="display:inline-block; font-size:11px; font-weight:700; background:${methodBg}; padding:2px 8px; border-radius:6px; margin-top:4px;">
                                ${methodBadgeText}
                            </span>
                        </div>
                        <div class="fee-amount-badge">
                            ${amountBadgeText}
                        </div>
                    </div>
                    <div class="fee-meta-row">
                        <div>
                            <span style="color:#94a3b8; font-weight:600;">Effective From:</span>
                            <strong style="color:#0f172a; margin-left:4px;">${formatDate(r.from_date)}</strong>
                        </div>
                        <span class="badge-status-mob ${statusClass}">${statusLabel}</span>
                    </div>
                    <div class="fee-actions">
                        <button type="button" class="btn-act-mob btn-act-mob-edit" onclick="openEditFeeModal(${r.id}, ${r.group_id}, ${r.fee}, '${r.from_date}', '${escapedMethod}')">
                            <i class="fa fa-pencil"></i> Edit
                        </button>
                        <button type="button" class="btn-act-mob btn-act-mob-del" onclick="confirmDeleteFee(${r.id})">
                            <i class="fa fa-trash"></i> Delete
                        </button>
                    </div>
                </div>
            `;
        });

        $('#fees_container').html(html);
    }

    function filterFeeCards() {
        var query = $('#search_fees').val().toLowerCase().trim();
        $('.fee-item-card').each(function() {
            var search = $(this).attr('data-search') || '';
            if (search.indexOf(query) !== -1) {
                $(this).show();
            } else {
                $(this).hide();
            }
        });
    }

    function openAddFeeModal() {
        $('#form_add_fee')[0].reset();
        $('#add_fee_method').val('fixed');
        onMobileAddMethodChange();
        $('#addFeeModal').modal('show');
    }

    function submitNewFee(e) {
        e.preventDefault();
        var gid = $('#add_group_id').val();
        var method = $('#add_fee_method').val();
        var fee = $('#add_fee_amount').val();
        var fdate = $('#add_from_date').val();

        if (method === 'fixed' && (!fee || parseFloat(fee) <= 0)) {
            swal("Invalid Amount", "Please enter a valid monthly fee amount greater than 0.", "warning");
            return;
        }

        $.post('../api/fees.php', {
            action: 'save_new_fees',
            group_id: gid,
            fee_method: method,
            fees: fee || 0,
            from_date: fdate
        }, function(res) {
            try {
                var d = typeof res === 'string' ? JSON.parse(res) : res;
                if (d.status === 'success') {
                    $('#addFeeModal').modal('hide');
                    swal("Success", d.message, "success");
                    loadFeeData();
                } else {
                    swal("Error", d.message || "Failed to save fee", "error");
                }
            } catch(e) {
                swal("Error", "Server error while saving fee rule", "error");
            }
        }).fail(function(xhr) {
            var msg = "Failed to save fee.";
            try { msg = JSON.parse(xhr.responseText).message || msg; } catch(e) {}
            swal("Error", msg, "error");
        });
    }

    function openEditFeeModal(id, groupId, fee, fromDate, method) {
        $('#edit_fee_id').val(id);
        $('#edit_group_id').val(groupId);
        $('#edit_fee_method').val(method || 'fixed');
        onMobileEditMethodChange();
        $('#edit_fee_amount').val(fee);
        $('#edit_from_date').val(fromDate);
        $('#editFeeModal').modal('show');
    }

    function submitEditFee(e) {
        e.preventDefault();
        var id = $('#edit_fee_id').val();
        var gid = $('#edit_group_id').val();
        var method = $('#edit_fee_method').val();
        var fee = $('#edit_fee_amount').val();
        var fdate = $('#edit_from_date').val();

        if (method === 'fixed' && (!fee || parseFloat(fee) <= 0)) {
            swal("Invalid Amount", "Please enter a valid monthly fee amount greater than 0.", "warning");
            return;
        }

        $.post('../api/fees.php', {
            action: 'save_fees',
            id: id,
            group_id: gid,
            fee_method: method,
            fees: fee || 0,
            from_date: fdate
        }, function(res) {
            try {
                var d = typeof res === 'string' ? JSON.parse(res) : res;
                if (d.status === 'success') {
                    $('#editFeeModal').modal('hide');
                    swal("Updated!", d.message, "success");
                    loadFeeData();
                } else {
                    swal("Error", d.message || "Failed to update fee", "error");
                }
            } catch(e) {
                swal("Error", "Server error while updating fee rule", "error");
            }
        }).fail(function(xhr) {
            var msg = "Failed to update fee.";
            try { msg = JSON.parse(xhr.responseText).message || msg; } catch(e) {}
            swal("Error", msg, "error");
        });
    }

    function confirmDeleteFee(id) {
        swal({
            title: "Delete Fee Rule?",
            text: "Are you sure you want to remove this monthly fee configuration rule?",
            type: "warning",
            showCancelButton: true,
            confirmButtonColor: "#ef4444",
            confirmButtonText: "Yes, Delete",
            closeOnConfirm: false
        }, function() {
            $.post('../api/fees.php', { action: 'delete_fee_details', id: id }, function(res) {
                try {
                    var d = typeof res === 'string' ? JSON.parse(res) : res;
                    if (d.status === 'success') {
                        swal("Deleted!", d.message, "success");
                        loadFeeData();
                    } else {
                        swal("Cannot Delete", d.message || "Could not delete rule", "warning");
                    }
                } catch(e) {
                    swal("Error", "Server error while deleting fee rule", "error");
                }
            }).fail(function(xhr) {
                var msg = "Failed to delete.";
                try { msg = JSON.parse(xhr.responseText).message || msg; } catch(e) {}
                swal("Error", msg, "error");
            });
        });
    }
    </script>
</body>
</html>
