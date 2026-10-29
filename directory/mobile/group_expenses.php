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
$allowed_groups = getUserAllowedGroupIds($login_id);
$primary_role = getUserPrimaryRoleName($login_id);

$active_tab = 'accounts';

// Fetch recent group expenses / payables
$sql = "
    SELECT p.id, p.date, p.amount, p.particuler, p.bill_photo, p.head, p.group_id, p.transaction_type, phm.name as head_name, g.name as group_name
    FROM tbl_paid p
    LEFT JOIN tbl_payment_head_master phm ON p.head = phm.id
    LEFT JOIN tbl_groups g ON p.group_id = g.id
    WHERE (p.cancel IS NULL OR p.cancel = 0)
";
if (!in_array('ALL', $allowed_groups, true) && !empty($allowed_groups)) {
    $in = implode(',', array_map('intval', $allowed_groups));
    $sql .= " AND p.group_id IN ($in)";
}
$sql .= " ORDER BY p.date DESC, p.id DESC LIMIT 40";
$res = app_exec_query($sql);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
    <title>Group Expenses - YMCA Mobile</title>
    
    <link href="../../css/bootstrap.min.css" rel="stylesheet">
    <link href="../../font-awesome/css/font-awesome.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <link href="../../css/plugins/sweetalert/sweetalert.css" rel="stylesheet">
    <link href="mobile_layout.css" rel="stylesheet">

    <style>
        body { font-family: 'Plus Jakarta Sans', sans-serif !important; background: #f8fafc !important; }
        .acc-hero {
            background: linear-gradient(135deg, #ef4444 0%, #dc2626 100%);
            border-radius: 20px; padding: 18px 20px; color: #ffffff; margin-bottom: 16px;
            box-shadow: 0 8px 20px rgba(239, 68, 68, 0.2);
        }
        .acc-hero h2 { margin: 0 0 4px 0; font-weight: 800; font-size: 20px; }
        .acc-hero p { margin: 0; font-size: 12.5px; opacity: 0.9; }

        .rcp-card {
            background: #ffffff; border-radius: 16px; border: 1px solid #e2e8f0; padding: 14px 16px;
            margin-bottom: 12px; box-shadow: 0 2px 6px rgba(0,0,0,0.02);
            display: flex; flex-direction: column; gap: 10px;
        }
        .rcp-header { display: flex; align-items: flex-start; justify-content: space-between; gap: 10px; }
        .rcp-date { font-size: 11px; font-weight: 700; color: #64748b; text-transform: uppercase; }
        .rcp-name { font-size: 14.5px; font-weight: 800; color: #0f172a; margin: 2px 0 0 0; }
        .rcp-head { font-size: 12px; color: #64748b; font-weight: 600; margin-top: 2px; }
        .rcp-amt { font-size: 16px; font-weight: 800; color: #ef4444; text-align: right; white-space: nowrap; }

        .rcp-actions {
            display: flex; align-items: center; justify-content: space-between;
            padding-top: 10px; border-top: 1px dashed #f1f5f9; margin-top: 2px;
        }
        .btn-act-sm {
            border-radius: 8px; font-weight: 800; font-size: 11px; padding: 4px 10px; cursor: pointer; border: none;
        }
        .btn-act-bill { background: #fff1f2; color: #e11d48; border: 1px solid #fecdd3; }
        .btn-act-edit { background: #eff6ff; color: #2563eb; border: 1px solid #bfdbfe; }
        .btn-act-del { background: #fef2f2; color: #dc2626; border: 1px solid #fca5a5; }
    </style>
</head>
<body class="mob-body">

    <!-- Header -->
    <header class="mob-header">
        <div class="mob-header-brand">
            <a href="accounts.php" style="color:#ffffff; margin-right:8px; font-size:18px;"><i class="fa fa-arrow-left"></i></a>
            <div class="mob-header-title">
                Group <span>Expenses</span>
            </div>
        </div>
        <div class="mob-header-actions">
            <a href="../../app_login_manager/logout.php" class="mob-header-btn" title="Logout"><i class="fa fa-sign-out"></i></a>
        </div>
    </header>

    <div class="mob-page">

        <div class="acc-hero" style="display:flex; justify-content:space-between; align-items:center;">
            <div>
                <h2>Group Expenses</h2>
                <p>Expense vouchers & vendor payouts log</p>
            </div>
            <button class="btn btn-light btn-sm" style="border-radius:12px; font-weight:800; color:#ef4444;" onclick="openAddModal()">
                <i class="fa fa-plus"></i> Add
            </button>
        </div>

        <div>
            <?php if ($res && $res->num_rows > 0): ?>
                <?php while ($r = $res->fetch_assoc()): ?>
                    <div class="rcp-card">
                        <div class="rcp-header">
                            <div>
                                <div class="rcp-date">
                                    <i class="fa fa-calendar"></i> <?php echo date('d M Y', strtotime($r['date'])); ?>
                                    <?php if (!empty($r['group_name'])): ?>
                                        &bull; <span style="color:#059669; font-weight:800;"><?php echo htmlspecialchars($r['group_name']); ?></span>
                                    <?php endif; ?>
                                </div>
                                <h4 class="rcp-name"><?php echo htmlspecialchars($r['head_name'] ?: 'Expense'); ?></h4>
                                <?php if (!empty($r['particuler'])): ?>
                                    <div class="rcp-head"><?php echo htmlspecialchars($r['particuler']); ?></div>
                                <?php endif; ?>
                            </div>
                            <div class="rcp-amt">
                                -₹<?php echo number_format((float)$r['amount'], 2); ?>
                            </div>
                        </div>

                        <div class="rcp-actions">
                            <div>
                                <?php if (!empty($r['bill_photo'])): ?>
                                    <button type="button" onclick="openBillModal('<?php echo $r['id']; ?>', '<?php echo date('d M Y', strtotime($r['date'])); ?>', '<?php echo addslashes(htmlspecialchars($r['head_name'] ?: 'Expense')); ?>', '<?php echo number_format((float)$r['amount'], 2); ?>', '<?php echo addslashes(htmlspecialchars($r['particuler'] ?: 'Expense')); ?>', '<?php echo addslashes($r['bill_photo']); ?>')" class="btn-act-sm btn-act-bill">
                                        <i class="fa fa-file-text-o"></i> View Bill
                                    </button>
                                <?php else: ?>
                                    <span style="font-size:11px; color:#cbd5e1; font-weight:600;"><i class="fa fa-file-o"></i> No Bill</span>
                                <?php endif; ?>
                            </div>

                            <div style="display:flex; gap:6px;">
                                <button type="button" onclick='openEditModal(<?php echo htmlspecialchars(json_encode($r), ENT_QUOTES, "UTF-8"); ?>)' class="btn-act-sm btn-act-edit">
                                    <i class="fa fa-pencil"></i> Edit
                                </button>
                                <button type="button" onclick="deleteExpense(<?php echo $r['id']; ?>)" class="btn-act-sm btn-act-del">
                                    <i class="fa fa-trash"></i> Delete
                                </button>
                            </div>
                        </div>
                    </div>
                <?php endwhile; ?>
            <?php else: ?>
                <div style="text-align:center; padding:40px 20px; color:#94a3b8;">
                    <i class="fa fa-upload" style="font-size:36px; margin-bottom:10px; display:block;"></i>
                    No group expenses recorded yet.
                </div>
            <?php endif; ?>
        </div>

    </div>

    <!-- Modal for Add / Edit Expense -->
    <div class="modal fade" id="addExpenseModal" tabindex="-1" role="dialog" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered" role="document">
            <div class="modal-content" style="border-radius:20px; border:none;">
                <div class="modal-header" style="border-bottom:1px solid #f1f5f9;">
                    <h5 class="modal-title" style="font-weight:800; color:#0f172a;" id="modalExpenseTitle">
                        <i class="fa fa-plus-circle" style="color:#ef4444;"></i> Add New Expense
                    </h5>
                    <button type="button" class="close" data-dismiss="modal">&times;</button>
                </div>
                <form id="mob_expense_form" enctype="multipart/form-data">
                    <input type="hidden" id="exp_id" name="id" value="0">
                    <div class="modal-body" style="padding:20px; display:flex; flex-direction:column; gap:12px;">
                        <div>
                            <label style="font-size:11px; font-weight:700; color:#64748b; text-transform:uppercase;">Date</label>
                            <input type="date" id="exp_date" value="<?php echo date('Y-m-d'); ?>" class="form-control" style="border-radius:10px; font-weight:600;" required>
                        </div>
                        <div>
                            <label style="font-size:11px; font-weight:700; color:#64748b; text-transform:uppercase;">Select Group</label>
                            <select id="exp_group_id" class="form-control" style="border-radius:10px; font-weight:600;"></select>
                        </div>
                        <div>
                            <label style="font-size:11px; font-weight:700; color:#64748b; text-transform:uppercase;">Head / Category</label>
                            <select id="exp_head" class="form-control" style="border-radius:10px; font-weight:600;"></select>
                        </div>
                        <div>
                            <label style="font-size:11px; font-weight:700; color:#64748b; text-transform:uppercase;">Transaction Type</label>
                            <select id="exp_transaction_type" class="form-control" style="border-radius:10px; font-weight:600;">
                                <option value="1">Cash</option>
                                <option value="2">Bank</option>
                            </select>
                        </div>
                        <div>
                            <label style="font-size:11px; font-weight:700; color:#64748b; text-transform:uppercase;">Particulars / Description</label>
                            <input type="text" id="exp_particuler" placeholder="E.g., Shuttlecock boxes purchase" class="form-control" style="border-radius:10px; font-weight:600;" required>
                        </div>
                        <div>
                            <label style="font-size:11px; font-weight:700; color:#64748b; text-transform:uppercase;">Amount (₹)</label>
                            <input type="number" step="0.01" id="exp_amount" placeholder="0.00" class="form-control" style="border-radius:10px; font-weight:700; color:#dc2626;" required>
                        </div>
                        <div>
                            <label style="font-size:11px; font-weight:700; color:#64748b; text-transform:uppercase;">Attach Bill / Receipt Photo (Optional)</label>
                            <input type="file" id="exp_bill" accept="image/*,.pdf" class="form-control" style="border-radius:10px; font-size:12px;">
                            <div id="exp_current_bill_lbl" style="font-size:11px; font-weight:700; color:#059669; margin-top:4px;"></div>
                        </div>
                    </div>
                    <div class="modal-footer" style="border-top:1px solid #f1f5f9;">
                        <button type="button" class="btn btn-light" data-dismiss="modal" style="border-radius:10px; font-weight:700;">Cancel</button>
                        <button type="submit" class="btn btn-danger" style="border-radius:10px; font-weight:800;" id="btnSubmitExpense">Save Expense</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- View Bill Modal -->
    <div class="modal fade" id="viewBillModal" tabindex="-1" role="dialog" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered" role="document">
            <div class="modal-content" style="border-radius:20px; border:none; box-shadow:0 15px 35px rgba(0,0,0,0.2);">
                <div class="modal-header" style="border-bottom:1px solid #f1f5f9; background:#fff1f2; border-top-left-radius:20px; border-top-right-radius:20px; padding:16px 20px;">
                    <h5 class="modal-title" style="font-weight:800; color:#9f1239; font-size:15px;">
                        <i class="fa fa-file-text-o" style="color:#e11d48; margin-right:6px;"></i> Expense Bill Voucher
                    </h5>
                    <button type="button" class="close" data-dismiss="modal" style="opacity:0.8;">&times;</button>
                </div>
                <div class="modal-body" style="padding:20px;">
                    <div style="background:#f8fafc; border:1px solid #e2e8f0; border-radius:14px; padding:14px; margin-bottom:16px;">
                        <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:8px;">
                            <span id="vch_date" style="font-size:11px; font-weight:700; color:#64748b; text-transform:uppercase;"></span>
                            <span id="vch_amount" style="font-size:16px; font-weight:900; color:#e11d48;"></span>
                        </div>
                        <div id="vch_head" style="font-size:14px; font-weight:800; color:#0f172a;"></div>
                        <div id="vch_desc" style="font-size:12px; color:#475569; margin-top:2px; font-weight:600;"></div>
                    </div>

                    <div style="font-size:11px; font-weight:800; color:#64748b; text-transform:uppercase; margin-bottom:8px;">Attached Bill Attachment</div>
                    <div id="vch_photo_container" style="text-align:center; padding:12px; background:#f8fafc; border:1px dashed #cbd5e1; border-radius:14px;">
                        <!-- Injected dynamically -->
                    </div>
                </div>
                <div class="modal-footer" style="border-top:1px solid #f1f5f9; padding:12px 20px;">
                    <button type="button" class="btn btn-secondary btn-block" data-dismiss="modal" style="border-radius:12px; font-weight:700;">Close</button>
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
    function updateCustomSelectLabel(selectSelector) {
        let $select = $(selectSelector);
        if (!$select.length) return;
        let labelId = $select.attr('id') + '_custom_label';
        let selText = $select.find('option:selected').text() || $select.find('option').first().text() || 'Select...';
        $('#' + labelId).text(selText);
    }

    function makeCustomSelect(selectSelector, title) {
        let $select = $(selectSelector);
        if (!$select.length) return;

        $select.hide();
        let triggerId = $select.attr('id') + '_custom_trigger';
        let labelId = $select.attr('id') + '_custom_label';

        let initialText = $select.find('option:selected').text() || $select.find('option').first().text() || 'Select...';

        if (!$('#' + triggerId).length) {
            $select.after('<div class="custom-mob-select" id="' + triggerId + '"><span id="' + labelId + '">' + initialText + '</span><i class="fa fa-chevron-down"></i></div>');
        } else {
            $('#' + labelId).text(initialText);
        }

        $('#' + triggerId).off('click').on('click', function() {
            let options = [];
            $select.find('option').each(function() {
                options.push({ value: $(this).val(), label: $(this).text() });
            });

            let currentVal = $select.val();
            let htm = '<div id="customPickerModal" style="position:fixed; top:0; left:0; right:0; bottom:0; background:rgba(15,23,42,0.6); z-index:2000000; display:flex; align-items:center; justify-content:center; padding:20px; backdrop-filter:blur(4px);">';
            htm += '<div style="background:#ffffff; border-radius:24px; width:100%; max-width:340px; overflow:hidden; box-shadow:0 25px 50px -12px rgba(0,0,0,0.3); max-height:80vh; display:flex; flex-direction:column;">';
            htm += '<div style="padding:16px 20px; border-bottom:1px solid #f1f5f9; display:flex; justify-content:space-between; align-items:center;">';
            htm += '<h4 style="margin:0; font-size:15px; font-weight:800; color:#0f172a;">' + title + '</h4>';
            htm += '<span id="closePickerBtn" style="font-size:22px; cursor:pointer; color:#64748b; line-height:1;">&times;</span>';
            htm += '</div>';
            htm += '<div style="overflow-y:auto; padding:8px 0; flex:1;">';
            options.forEach(function(opt) {
                let isSel = (opt.value == currentVal);
                htm += '<div class="picker-opt-row" data-val="' + opt.value + '" style="padding:14px 20px; font-size:14px; font-weight:700; color:' + (isSel ? '#ef4444' : '#1e293b') + '; background:' + (isSel ? '#fef2f2' : 'transparent') + '; cursor:pointer; display:flex; justify-content:space-between; align-items:center; border-bottom:1px solid #f8fafc;">';
                htm += '<span>' + opt.label + '</span>';
                if (isSel) htm += '<i class="fa fa-check" style="color:#ef4444;"></i>';
                htm += '</div>';
            });
            htm += '</div>';
            htm += '</div></div>';

            $('body').append(htm);

            $('#closePickerBtn, #customPickerModal').on('click', function(e) {
                if (e.target === this) $('#customPickerModal').remove();
            });

            $('.picker-opt-row').on('click', function() {
                let val = $(this).data('val');
                let label = $(this).find('span').text();
                $select.val(val).trigger('change');
                $('#' + labelId).text(label);
                $('#customPickerModal').remove();
            });
        });
    }

    $(document).ready(function() {
        makeCustomSelect('#exp_transaction_type', 'Transaction Type');

        $.post('../api/attendance.php', { action: 'load_groups' }, function(data) {
            try {
                let parsed = typeof data === 'string' ? JSON.parse(data) : data;
                let groups = Array.isArray(parsed[0]) ? parsed[0] : (Array.isArray(parsed) ? parsed : []);
                let htm = '';
                groups.forEach(function(g) { htm += '<option value="' + g.id + '">' + g.name + '</option>'; });
                $('#exp_group_id').html(htm);
                makeCustomSelect('#exp_group_id', 'Select Group');
            } catch(e) {}
        });

        $.post('../api/payable.php', { action: 'load_heads' }, function(data) {
            try {
                let parsed = typeof data === 'string' ? JSON.parse(data) : data;
                let heads = Array.isArray(parsed[0]) ? parsed[0] : (Array.isArray(parsed) ? parsed : []);
                let htm = '';
                heads.forEach(function(h) { htm += '<option value="' + h.id + '">' + h.name + '</option>'; });
                $('#exp_head').html(htm);
                makeCustomSelect('#exp_head', 'Head / Category');
            } catch(e) {}
        });

        $('#mob_expense_form').on('submit', function(e) {
            e.preventDefault();
            let formData = new FormData();
            formData.append('action', 'save_payment');
            formData.append('id', $('#exp_id').val());
            formData.append('date', $('#exp_date').val());
            formData.append('group_id', $('#exp_group_id').val());
            formData.append('head', $('#exp_head').val());
            formData.append('transaction_type', $('#exp_transaction_type').val());
            formData.append('particuler', $('#exp_particuler').val());
            formData.append('amount', $('#exp_amount').val());
            
            if ($('#exp_bill')[0].files[0]) {
                formData.append('bill', $('#exp_bill')[0].files[0]);
            }

            $('#btnSubmitExpense').prop('disabled', true).text('Saving...');

            $.ajax({
                url: '../api/payable.php',
                type: 'POST',
                data: formData,
                processData: false,
                contentType: false,
                success: function(res) {
                    swal("Success!", "Expense saved successfully!", "success");
                    setTimeout(function() { location.reload(); }, 1200);
                },
                error: function() {
                    $('#btnSubmitExpense').prop('disabled', false).text('Save Expense');
                    swal("Error", "Error saving expense.", "error");
                }
            });
        });
    });

    function openAddModal() {
        $('#exp_id').val(0);
        $('#modalExpenseTitle').html('<i class="fa fa-plus-circle" style="color:#ef4444;"></i> Add New Expense');
        $('#btnSubmitExpense').text('Save Expense').prop('disabled', false);
        $('#exp_date').val(new Date().toISOString().slice(0, 10));
        $('#exp_particuler').val('');
        $('#exp_amount').val('');
        $('#exp_bill').val('');
        $('#exp_current_bill_lbl').html('');
        
        let firstGroup = $('#exp_group_id option:first').val();
        if (firstGroup) $('#exp_group_id').val(firstGroup);
        updateCustomSelectLabel('#exp_group_id');

        let firstHead = $('#exp_head option:first').val();
        if (firstHead) $('#exp_head').val(firstHead);
        updateCustomSelectLabel('#exp_head');

        $('#exp_transaction_type').val('1');
        updateCustomSelectLabel('#exp_transaction_type');

        $('#addExpenseModal').modal('show');
    }

    function openEditModal(item) {
        $('#exp_id').val(item.id);
        $('#modalExpenseTitle').html('<i class="fa fa-pencil" style="color:#ef4444;"></i> Edit Expense');
        $('#btnSubmitExpense').text('Update Expense').prop('disabled', false);
        $('#exp_date').val(item.date);
        $('#exp_particuler').val(item.particuler || '');
        $('#exp_amount').val(item.amount);
        $('#exp_bill').val('');

        if (item.bill_photo) {
            $('#exp_current_bill_lbl').html('<i class="fa fa-paperclip"></i> Current file: ' + item.bill_photo);
        } else {
            $('#exp_current_bill_lbl').html('');
        }

        if (item.group_id) $('#exp_group_id').val(item.group_id);
        updateCustomSelectLabel('#exp_group_id');

        if (item.head) $('#exp_head').val(item.head);
        updateCustomSelectLabel('#exp_head');

        if (item.transaction_type) $('#exp_transaction_type').val(item.transaction_type);
        updateCustomSelectLabel('#exp_transaction_type');

        $('#addExpenseModal').modal('show');
    }

    function deleteExpense(id) {
        swal({
            title: "Delete Expense?",
            text: "Are you sure you want to delete this expense record?",
            type: "warning",
            showCancelButton: true,
            confirmButtonColor: "#dc2626",
            confirmButtonText: "Yes, Delete",
            cancelButtonText: "Cancel",
            closeOnConfirm: false
        }, function() {
            $.post('../api/payable.php', { action: 'delete_payment', id: id }, function(res) {
                swal("Deleted!", "Expense record has been deleted.", "success");
                setTimeout(function() { location.reload(); }, 1200);
            }).fail(function() {
                swal("Error", "Could not delete expense record.", "error");
            });
        });
    }

    function openBillModal(id, date, head, amount, desc, photo) {
        $('#vch_date').text(date);
        $('#vch_head').text(head);
        $('#vch_amount').text('₹ ' + amount);
        $('#vch_desc').text(desc || 'Expense');

        let cleanPhoto = (photo && typeof photo === 'string') ? photo.trim() : '';
        if (cleanPhoto !== '' && cleanPhoto !== '0' && cleanPhoto !== 'null' && cleanPhoto !== 'undefined') {
            resolveBillPath(cleanPhoto, function(foundPath) {
                let ext = cleanPhoto.split('.').pop().toLowerCase();
                if (['jpg', 'jpeg', 'png', 'gif', 'webp'].includes(ext)) {
                    $('#vch_photo_container').html(`
                        <a href="${foundPath}" target="_blank">
                            <img src="${foundPath}" style="max-width:100%; max-height:220px; border-radius:10px; box-shadow:0 4px 12px rgba(0,0,0,0.1);" onerror="this.onerror=null; this.parentNode.innerHTML='<div style=\\'color:#ef4444; font-size:12px; font-weight:700; padding:10px; background:#fef2f2; border-radius:10px; border:1px solid #fca5a5;\\'><i class=\\'fa fa-exclamation-triangle\\'></i> Bill file (${cleanPhoto}) missing on server</div>';">
                        </a>
                        <div style="font-size:11px; color:#64748b; margin-top:8px;"><a href="${foundPath}" target="_blank" style="color:#e11d48; font-weight:800;"><i class="fa fa-external-link"></i> Open Full Image</a></div>
                    `);
                } else {
                    $('#vch_photo_container').html(`
                        <a href="${foundPath}" target="_blank" class="btn btn-sm btn-danger" style="border-radius:10px; font-weight:800; padding:10px 18px; color:#fff;">
                            <i class="fa fa-file-pdf-o"></i> View Document (${ext.toUpperCase()})
                        </a>
                    `);
                }
            });
        } else {
            $('#vch_photo_container').html(`
                <div style="color:#94a3b8; font-size:12.5px; font-weight:600;">
                    <i class="fa fa-file-o" style="font-size:24px; margin-bottom:6px; display:block; color:#cbd5e1;"></i>
                    No digital bill photo attached
                </div>
            `);
        }

        $('#viewBillModal').modal('show');
    }

    function resolveBillPath(cleanPhoto, callback) {
        let path1 = '../../image_upload/bills/' + cleanPhoto;
        fetch(path1, { method: 'HEAD' })
            .then(function(res1) {
                if (res1.ok) {
                    callback(path1);
                } else {
                    let path2 = '../../image_upload/payments/' + cleanPhoto;
                    fetch(path2, { method: 'HEAD' })
                        .then(function(res2) {
                            if (res2.ok) {
                                callback(path2);
                            } else {
                                callback('../../image_upload/bills/' + cleanPhoto);
                            }
                        }).catch(function() {
                            callback(path1);
                        });
                }
            }).catch(function() {
                callback(path1);
            });
    }
    </script>
</body>
</html>
