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
    header("Location: ledger.php");
    exit();
}

$is_admin = isSuperAdmin($login_id) || isGroupAdmin($login_id);
$allowed_groups = getUserAllowedGroupIds($login_id);
$primary_role = getUserPrimaryRoleName($login_id);

// Determine target member ID
$target_member_id = isset($_GET['member_id']) ? (int)$_GET['member_id'] : (isset($_SESSION['member_id']) ? (int)$_SESSION['member_id'] : 0);

if ($target_member_id <= 0) {
    $res_first = app_exec_query("SELECT id FROM tbl_members WHERE inactive = 0 ORDER BY first_name, last_name LIMIT 1");
    if ($res_first && $r1 = $res_first->fetch_assoc()) {
        $target_member_id = (int)$r1['id'];
    }
}
$_SESSION['member_id'] = $target_member_id;

// Fetch member details
$member = null;
if ($target_member_id > 0) {
    $res_m = app_exec_getresult("
        SELECT m.id, m.first_name, m.middle_name, m.last_name, m.phone, m.img, m.member_type,
               GROUP_CONCAT(DISTINCT g.name SEPARATOR ', ') as group_names
        FROM tbl_members m
        LEFT JOIN tbl_group_member_map gmm ON m.id = gmm.member_id
        LEFT JOIN tbl_groups g ON gmm.group_id = g.id
        WHERE m.id = ?
        GROUP BY m.id
    ", [$target_member_id], "i");
    if ($res_m && $row_m = $res_m->fetch_assoc()) {
        $member = $row_m;
    }
}

$member_name = $member ? trim($member['first_name'] . ' ' . $member['middle_name'] . ' ' . $member['last_name']) : 'Select Member';
$member_img = $member['img'] ?? '';
$img_src = !empty($member_img) ? '../../image_upload/member/' . $member_img : '';
$initial = strtoupper(substr($member['first_name'] ?? 'M', 0, 1));
$group_name = $member['group_names'] ?? 'Member';
$is_guest_member = $member ? ((int)($member['member_type'] ?? 0) === 1) : false;

// Fetch Members list for search drawer
$sql_all_m = "SELECT id, first_name, middle_name, last_name, phone FROM tbl_members WHERE inactive = 0 ORDER BY first_name, middle_name, last_name";
$res_all_m = app_exec_query($sql_all_m);

$active_tab = 'accounts';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
    <title>Member Fees Ledger - YMCA Mobile</title>
    
    <link href="../../css/bootstrap.min.css" rel="stylesheet">
    <link href="../../font-awesome/css/font-awesome.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <link href="mobile_layout.css" rel="stylesheet">
    <link href="../../css/plugins/sweetalert/sweetalert.css" rel="stylesheet">

    <style>
        body { font-family: 'Plus Jakarta Sans', sans-serif !important; background: #f8fafc !important; }
        
        .mem-profile-card {
            background: #ffffff; border-radius: 20px; border: 1px solid #e2e8f0; padding: 16px;
            margin-bottom: 14px; box-shadow: 0 4px 12px rgba(0,0,0,0.03);
            display: flex; align-items: center; justify-content: space-between; gap: 12px;
        }
        .mem-avatar-lg {
            width: 52px; height: 52px; border-radius: 50%; object-fit: cover;
            border: 3px solid #10b981; background: #ecfdf5; display: flex;
            align-items: center; justify-content: center; font-weight: 800; color: #059669; font-size: 18px;
            flex-shrink: 0;
        }
        .mem-info-block { flex: 1; min-width: 0; }
        .mem-name-title { font-size: 16px; font-weight: 800; color: #0f172a; margin: 0 0 2px 0; white-space: nowrap; overflow: hidden; text-overflow: ellipsis; }
        .mem-sub-tag { font-size: 12px; color: #64748b; font-weight: 600; }

        .btn-switch-mem {
            background: #ecfdf5; color: #059669; border: 1px solid #10b981; border-radius: 12px;
            padding: 8px 12px; font-size: 12px; font-weight: 800; display: inline-flex; align-items: center; gap: 6px;
            cursor: pointer; flex-shrink: 0;
        }

        .bal-grid { display: grid; grid-template-columns: repeat(2, 1fr); gap: 10px; margin-bottom: 16px; }
        .bal-card {
            background: #ffffff; border-radius: 16px; border: 1px solid #e2e8f0; padding: 12px 14px;
            box-shadow: 0 2px 6px rgba(0,0,0,0.02);
        }
        .bal-lbl { font-size: 10px; font-weight: 800; color: #64748b; text-transform: uppercase; letter-spacing: 0.4px; }
        .bal-val { font-size: 16px; font-weight: 800; color: #0f172a; margin-top: 2px; }

        .action-card {
            background: #ffffff; border-radius: 20px; border: 1px solid #e2e8f0; padding: 16px;
            margin-bottom: 16px; box-shadow: 0 4px 12px rgba(0,0,0,0.03);
        }
        .action-card-title { font-size: 13px; font-weight: 800; color: #0f172a; text-transform: uppercase; margin-bottom: 12px; display: flex; align-items: center; gap: 6px; }

        .rec-item-card {
            background: #ffffff; border-radius: 16px; border: 1px solid #e2e8f0; padding: 14px;
            margin-bottom: 10px; box-shadow: 0 2px 6px rgba(0,0,0,0.02);
        }
        .rec-item-header { display: flex; align-items: center; justify-content: space-between; margin-bottom: 6px; }
        .rec-item-date { font-size: 11px; font-weight: 700; color: #64748b; text-transform: uppercase; }
        .status-pill { font-size: 10px; font-weight: 800; padding: 3px 8px; border-radius: 6px; text-transform: uppercase; }
        .status-pill-paid { background: #dcfce7; color: #166534; }
        .status-pill-pending { background: #fef3c7; color: #92400e; }

        .rec-item-title { font-size: 14px; font-weight: 800; color: #0f172a; margin: 0 0 6px 0; }
        .rec-item-grid { display: grid; grid-template-columns: repeat(4, 1fr); gap: 4px; font-size: 11px; background: #f8fafc; padding: 8px 6px; border-radius: 10px; margin-bottom: 10px; text-align: center; }
        .rec-item-lbl { font-size: 9.5px; font-weight: 700; color: #64748b; text-transform: uppercase; }
        .rec-item-val { font-weight: 800; color: #0f172a; margin-top: 1px; }

        .btn-setoff-sm {
            background: linear-gradient(135deg, #10b981 0%, #059669 100%); color: #ffffff; border: none;
            border-radius: 8px; padding: 6px 12px; font-size: 11.5px; font-weight: 800; display: inline-flex; align-items: center; gap: 4px;
            cursor: pointer; width: 100%; justify-content: center;
            background: #ffffff;
            border-radius: 18px;
            border: 1px solid #e2e8f0;
            padding: 16px;
            margin-bottom: 16px;
            box-shadow: 0 2px 8px rgba(0,0,0,0.02);
        }

        .action-title { font-size: 13.5px; font-weight: 800; color: #0f172a; margin-bottom: 12px; display: flex; align-items: center; justify-content: space-between; }
        .setoff-item {
            background: #f8fafc;
            border: 1px solid #e2e8f0;
            border-radius: 12px;
            padding: 10px 12px;
            margin-bottom: 8px;
            display: flex;
            align-items: center;
            justify-content: space-between;
        }

        .btn-pay-cash {
            width: 100%;
            background: linear-gradient(135deg, #10b981 0%, #059669 100%);
            color: #fff;
            font-weight: 800;
            padding: 14px;
            border-radius: 14px;
            border: none;
            box-shadow: 0 6px 18px rgba(16, 185, 129, 0.25);
            font-size: 14px;
            margin-top: 8px;
        }

        .btn-pay-wallet {
            width: 100%;
            background: linear-gradient(135deg, #6366f1 0%, #4f46e5 100%);
            color: #fff;
            font-weight: 800;
            padding: 14px;
            border-radius: 14px;
            border: none;
            box-shadow: 0 6px 18px rgba(99, 102, 241, 0.25);
            font-size: 14px;
            margin-top: 8px;
        }

        .rec-item {
            background: #ffffff;
            border-radius: 14px;
            border: 1px solid #e2e8f0;
            padding: 12px 14px;
            margin-bottom: 8px;
            display: flex;
            align-items: center;
            justify-content: space-between;
        }

        .rec-amt { font-weight: 800; font-size: 14px; color: #0f172a; }
        .rec-sub { font-size: 11px; color: #64748b; font-weight: 600; margin-top: 2px; }

        .mem-pick-row {
            padding: 12px 14px; border-bottom: 1px solid #f1f5f9; display: flex; align-items: center; gap: 12px;
            cursor: pointer; transition: background 0.15s ease;
        }
        .mem-pick-row:hover { background: #ecfdf5; }

        .rec-item-card {
            transition: all 0.25s ease;
        }
        .rec-item-card.selected-setoff-card {
            border: 2px solid #4f46e5 !important;
            background: #f8fafc !important;
            box-shadow: 0 4px 18px rgba(79, 70, 229, 0.18) !important;
        }

        /* Member Picker Modal */
        .picker-search {
            width: 100%; padding: 10px 14px; border-radius: 12px; border: 1px solid #e2e8f0; font-size: 13px;
            font-weight: 600; outline: none; margin-bottom: 12px;
        }
    </style>
</head>
<body class="mob-body">

    <!-- Header -->
    <header class="mob-header">
        <div class="mob-header-brand">
            <a href="fee_receipts.php" style="color:#ffffff; margin-right:8px; font-size:18px;"><i class="fa fa-arrow-left"></i></a>
            <div class="mob-header-title">
                Member <span>Receivables Ledger</span>
            </div>
        </div>
        <div class="mob-header-actions">
            <a href="../../app_login_manager/logout.php" class="mob-header-btn" title="Logout"><i class="fa fa-sign-out"></i></a>
        </div>
    </header>

    <div class="mob-page">

        <!-- Active Member Card -->
        <div class="mem-profile-card">
            <?php if (!empty($img_src) && file_exists('../../image_upload/member/' . $member['img'])): ?>
                <img src="<?php echo $img_src; ?>" class="mem-avatar-lg" alt="<?php echo htmlspecialchars($member_name); ?>">
            <?php else: ?>
                <div class="mem-avatar-lg"><?php echo $initial; ?></div>
            <?php endif; ?>
            <div class="mem-info-block">
                <h3 class="mem-name-title">
                    <?php echo htmlspecialchars($member_name); ?>
                    <?php if ($is_guest_member): ?>
                        <span style="background: linear-gradient(135deg, #fff7ed 0%, #ffedd5 100%); color: #c2410c; border: 1px solid rgba(249, 115, 22, 0.3); box-shadow: 0 2px 6px rgba(249, 115, 22, 0.12); font-size: 9.5px; font-weight: 800; padding: 3px 8px 3px 6px; border-radius: 16px; letter-spacing: 0.5px; text-transform: uppercase; display: inline-flex; align-items: center; gap: 3px;"><i class="fa fa-star" style="color:#f59e0b; font-size:9px;"></i> Guest</span>
                    <?php endif; ?>
                </h3>
                <div class="mem-sub-tag"><i class="fa fa-users" style="color:#10b981;"></i> <?php echo htmlspecialchars($group_name); ?></div>
            </div>
            <div style="display:flex; flex-direction:column; gap:6px; align-items:flex-end;">
                <button class="btn-switch-mem" data-toggle="modal" data-target="#switchMemberModal">
                    <i class="fa fa-exchange"></i> Change
                </button>
                <?php if ($is_guest_member): ?>
                <button class="btn btn-warning btn-xs" onclick="$('#addGuestFeeModal').modal('show');" type="button" style="border-radius:8px; font-weight:800; font-size:10.5px; padding:4px 8px;">
                    <i class="fa fa-plus-circle"></i> Add Custom Fee
                </button>
                <?php endif; ?>
            </div>
        </div>

        <!-- Balance KPI Strip -->
        <div class="bal-grid">
            <div class="bal-card" style="border-left: 3px solid #3b82f6;">
                <div class="bal-lbl">Receivable Fees</div>
                <div class="bal-val" id="lbl_tot_receivable" style="color:#2563eb;">₹0.00</div>
            </div>
            <div class="bal-card" style="border-left: 3px solid #10b981;">
                <div class="bal-lbl">Received Fees</div>
                <div class="bal-val" id="lbl_tot_received" style="color:#059669;">₹0.00</div>
            </div>
            <div class="bal-card" style="border-left: 3px solid #ef4444;">
                <div class="bal-lbl">Pending Dues</div>
                <div class="bal-val" id="lbl_pending_balance" style="color:#dc2626;">₹0.00</div>
            </div>
            <div class="bal-card" style="border-left: 3px solid #8b5cf6;">
                <div class="bal-lbl">Wallet Balance</div>
                <div class="bal-val" id="lbl_wallet_balance" style="color:#7c3aed;">₹0.00</div>
            </div>
        </div>

        <!-- Set-Off Action Card -->
        <div class="action-card">
            <div class="action-card-title">
                <i class="fa fa-calculator" style="color:#10b981;"></i> Set-Off Receivable Payment
            </div>
            <div style="display:flex; flex-direction:column; gap:10px;">
                <div>
                    <label style="font-size:11px; font-weight:700; color:#64748b; text-transform:uppercase;">Payment Date</label>
                    <input type="date" id="txt_pay_date" value="<?php echo date('Y-m-d'); ?>" class="form-control" style="border-radius:10px; font-weight:600;">
                </div>
                <div>
                    <label style="font-size:11px; font-weight:700; color:#64748b; text-transform:uppercase;">Payment Mode</label>
                    <select id="txt_trans_type" class="form-control" style="border-radius:10px; font-weight:600;">
                        <option value="1">Cash</option>
                        <option value="2">Bank / UPI</option>
                    </select>
                </div>
                <div>
                    <label style="font-size:11px; font-weight:700; color:#64748b; text-transform:uppercase;">Amount to Pay (₹)</label>
                    <div style="display:flex; gap:8px;">
                        <input type="number" step="0.01" id="txt_pay_amount" placeholder="0.00" class="form-control" style="border-radius:10px; font-weight:800; color:#059669;" required>
                        <button type="button" class="btn btn-light" onclick="setFullPendingFees()" style="border-radius:10px; font-weight:800; color:#059669; white-space:nowrap; border:1px solid #10b981;">
                            Set Fees
                        </button>
                    </div>
                </div>
                <div id="setoff_action_buttons" style="display:none; flex-direction:column; gap:8px; margin-top:6px;">
                    <button type="button" class="btn btn-success" onclick="submitCashPayment()" style="width:100%; border-radius:12px; font-weight:800; padding:12px; font-size:13.5px; box-shadow:0 4px 10px rgba(16,185,129,0.15);">
                        <i class="fa fa-check-circle"></i> SetOff Payment
                    </button>
                    <button type="button" class="btn btn-primary" onclick="submitWalletPayment()" style="width:100%; border-radius:12px; font-weight:800; padding:12px; font-size:13.5px; background:#6366f1; border:none; box-shadow:0 4px 10px rgba(99,102,241,0.15); display:flex; align-items:center; justify-content:space-between;">
                        <span><i class="fa fa-credit-card"></i> Pay via Wallet</span>
                        <span id="btn_wallet_bal_badge" style="background:rgba(255,255,255,0.25); padding:3px 10px; border-radius:8px; font-size:12px; font-weight:800;">₹0.00</span>
                    </button>
                </div>
                <div id="wallet_info_sub" style="font-size:11.5px; font-weight:700; color:#4f46e5; background:#e0e7ff; padding:8px 12px; border-radius:10px; margin-top:2px; display:flex; align-items:center; justify-content:space-between;">
                    <span><i class="fa fa-credit-card"></i> Available Wallet Balance:</span>
                    <strong id="txt_wallet_bal_val" style="font-size:13px; font-weight:800; color:#3730a3;">₹0.00</strong>
                </div>
            </div>
        </div>

        <!-- Receivables List -->
        <div style="margin-bottom:12px; display:flex; justify-content:space-between; align-items:center;">
            <h4 style="font-size:15px; font-weight:800; color:#0f172a; margin:0;">Receivable Dues Log</h4>
            <span style="font-size:11px; font-weight:700; color:#64748b;" id="lbl_dues_count">0 Records</span>
        </div>

        <div id="receivables_list_container">
            <div style="text-align:center; padding:30px; color:#94a3b8;">
                <i class="fa fa-spinner fa-spin" style="font-size:24px; margin-bottom:8px; display:block;"></i>
                Loading receivables...
            </div>
        </div>

    </div>

    <!-- Modal for Member Selector -->
    <div class="modal fade" id="switchMemberModal" tabindex="-1" role="dialog" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered" role="document">
            <div class="modal-content" style="border-radius:24px; border:none; box-shadow:0 25px 50px -12px rgba(0,0,0,0.25);">
                <div class="modal-header" style="border-bottom:1px solid #f1f5f9; padding:16px 20px;">
                    <h5 class="modal-title" style="font-weight:800; color:#0f172a;"><i class="fa fa-users" style="color:#10b981;"></i> Select Member</h5>
                    <button type="button" class="close" data-dismiss="modal">&times;</button>
                </div>
                <div class="modal-body" style="padding:16px;">
                    <input type="text" class="picker-search" id="search_member_input" placeholder="Search member by name or phone..." onkeyup="filterMemberPicker()">
                    <div style="max-height:320px; overflow-y:auto;" id="member_picker_list">
                        <?php if ($res_all_m && $res_all_m->num_rows > 0): ?>
                            <?php while ($am = $res_all_m->fetch_assoc()): ?>
                                <?php $amName = trim($am['first_name'] . ' ' . $am['middle_name'] . ' ' . $am['last_name']); ?>
                                <div class="mem-pick-row" onclick="switchActiveMember('<?php echo $am['id']; ?>')">
                                    <div>
                                        <div style="font-size:14px; font-weight:800; color:#0f172a;"><?php echo htmlspecialchars($amName); ?></div>
                                        <div style="font-size:11px; color:#64748b; font-weight:600;"><?php echo htmlspecialchars($am['phone'] ?: 'No Phone'); ?></div>
                                    </div>
                                    <i class="fa fa-chevron-right" style="color:#cbd5e1; font-size:12px;"></i>
                                </div>
                            <?php endwhile; ?>
                        <?php endif; ?>
                    </div>
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
    const ACTIVE_MEMBER_ID = <?php echo $target_member_id; ?>;
    let currentOutstandingDue = 0;
    let currentWalletBalance = 0;

    function filterMemberPicker() {
        let q = $('#search_member_input').val().toLowerCase();
        $('.mem-pick-row').each(function() {
            let t = $(this).text().toLowerCase();
            if (t.indexOf(q) !== -1) $(this).show(); else $(this).hide();
        });
    }

    function switchActiveMember(mId) {
        window.location.href = 'member_receivable.php?member_id=' + mId;
    }

    function loadMemberBalances() {
        // Load pending payment metrics
        $.post('../api/fees_receiveble.php', { action: 'load_pending_payment', id: ACTIVE_MEMBER_ID }, function(res) {
            try {
                let parsed = typeof res === 'string' ? JSON.parse(res) : res;
                let data = (Array.isArray(parsed) && parsed[0] && parsed[0][0]) ? parsed[0][0] : (parsed[0] || {});
                let recFees = parseFloat(data.receivable_fees || 0);
                let rcvFees = parseFloat(data.received_fees || 0);
                let due = recFees - rcvFees;
                if (due < 0) due = 0;
                currentOutstandingDue = due;

                $('#lbl_tot_receivable').text('₹' + recFees.toLocaleString('en-IN', {minimumFractionDigits:2}));
                $('#lbl_tot_received').text('₹' + rcvFees.toLocaleString('en-IN', {minimumFractionDigits:2}));
                $('#lbl_pending_balance').text('₹' + due.toLocaleString('en-IN', {minimumFractionDigits:2}));
            } catch(e) {}
        });

        // Load wallet balance
        $.post('../api/fees_receiveble.php', { action: 'load_wallet_balance', id: ACTIVE_MEMBER_ID }, function(res) {
            try {
                let parsed = typeof res === 'string' ? JSON.parse(res) : res;
                let data = (Array.isArray(parsed) && parsed[0] && parsed[0][0]) ? parsed[0][0] : (parsed[0] || {});
                let wal = parseFloat(data.wallet_balance || 0);
                currentWalletBalance = wal;
                let walStr = '₹' + wal.toLocaleString('en-IN', {minimumFractionDigits:2});
                $('#lbl_wallet_balance').text(walStr);
                $('#btn_wallet_bal_badge').text(walStr);
                $('#txt_wallet_bal_val').text(walStr);
            } catch(e) {}
        });
    }

    let raw_receivables_data = [];
    let selectedSpecificRecId = 0;
    let selectedAllPending = false;

    function loadReceivablesList() {
        $.post('../api/fees_receiveble.php', { action: 'load_data', page: 1, member_id: ACTIVE_MEMBER_ID }, function(res) {
            try {
                let parsed = typeof res === 'string' ? JSON.parse(res) : res;
                let items = parsed[1] || [];
                raw_receivables_data = items;
                let htm = '';

                $('#lbl_dues_count').text(items.length + ' Records');

                if (!items || items.length === 0) {
                    $('#receivables_list_container').html('<div style="text-align:center; padding:30px; color:#94a3b8;"><i class="fa fa-check-circle-o" style="font-size:32px; margin-bottom:8px; display:block; color:#10b981;"></i>No receivable dues recorded for this member.</div>');
                    return;
                }

                items.forEach(function(r) {
                    let totalRec = parseFloat(r.receiveble_fees || 0);
                    let paid = parseFloat(r.total_received_fees || 0);
                    let due = totalRec - paid;
                    if (due < 0) due = 0;
                    let isComplete = (parseInt(r.iscomplete, 10) === 1 || due === 0);
                    let statusClass = isComplete ? 'status-pill-paid' : 'status-pill-pending';
                    let statusText = isComplete ? 'PAID / COMPLETED' : 'PENDING DUE';
                    let dateStr = r.date ? new Date(r.date).toLocaleDateString('en-GB', { day:'numeric', month:'short', year:'numeric' }) : '--';

                    let isCardSelected = (selectedSpecificRecId > 0 && parseInt(r.recieveble_id, 10) === parseInt(selectedSpecificRecId, 10)) ||
                                         (selectedAllPending && !isComplete && due > 0);
                    let cardSelectClass = isCardSelected ? 'selected-setoff-card' : '';
                    let itemTypeClass = isComplete ? 'is-complete' : 'is-pending';

                    htm += `
                        <div class="rec-item-card ${itemTypeClass} ${cardSelectClass}" id="rec_card_${r.recieveble_id}">
                            <div class="rec-item-header">
                                <span class="rec-item-date"><i class="fa fa-calendar"></i> ${dateStr}</span>
                                <span class="status-pill ${statusClass}">${statusText}</span>
                            </div>
                            <h4 class="rec-item-title">${r.head_name || 'Fee Receivable'} - ${r.discription || ''}</h4>
                            <div class="rec-item-grid">
                                <div>
                                    <div class="rec-item-lbl">Total Fee</div>
                                    <div class="rec-item-val">₹${totalRec.toFixed(2)}</div>
                                </div>
                                <div>
                                    <div class="rec-item-lbl">Paid Fee</div>
                                    <div class="rec-item-val" style="color:#059669;">₹${paid.toFixed(2)}</div>
                                </div>
                                <div>
                                    <div class="rec-item-lbl">Set-Off</div>
                                    <div class="rec-item-val" id="card_setoff_${r.recieveble_id}" style="color:#64748b;">₹0.00</div>
                                </div>
                                <div>
                                    <div class="rec-item-lbl">Balance Dues</div>
                                    <div class="rec-item-val" id="card_bal_${r.recieveble_id}" style="color:${due > 0 ? '#dc2626' : '#059669'};">₹${due.toFixed(2)}</div>
                                </div>
                            </div>
                            ${!isComplete ? `
                                <button type="button" class="btn-setoff-sm" onclick="setOffSpecificItem(${r.recieveble_id}, ${due}, ${r.head}, ${r.flag}, '${(r.discription || '').replace(/'/g, "\\'")}')">
                                    <i class="fa fa-check"></i> SetOff ₹${due.toFixed(2)}
                                </button>
                            ` : ''}
                        </div>
                    `;
                });

                $('#receivables_list_container').html(htm);
                if (selectedSpecificRecId > 0 || selectedAllPending) {
                    updateDuesLogSetOffCalculations();
                }
            } catch(e) {
                $('#receivables_list_container').html('<div style="text-align:center; padding:30px; color:#ef4444;">Error loading receivables.</div>');
            }
        });
    }

    function updateDuesLogSetOffCalculations() {
        let totalPayAmt = parseFloat($('#txt_pay_amount').val() || 0);
        let remAmt = totalPayAmt;

        if (!raw_receivables_data || raw_receivables_data.length === 0) return;

        if (selectedSpecificRecId > 0) {
            raw_receivables_data.forEach(function(r) {
                let recId = r.recieveble_id;
                let totalRec = parseFloat(r.receiveble_fees || 0);
                let paid = parseFloat(r.total_received_fees || 0);
                let due = Math.max(0, totalRec - paid);
                let isComplete = (parseInt(r.iscomplete, 10) === 1 || due === 0);

                let alloc = 0;
                if (!isComplete && parseInt(recId, 10) === parseInt(selectedSpecificRecId, 10)) {
                    alloc = Math.min(remAmt, due);
                }
                let bal = Math.max(0, due - alloc);
                updateCardRowUI(recId, alloc, bal, isComplete);
            });
        } else {
            raw_receivables_data.forEach(function(r) {
                let recId = r.recieveble_id;
                let totalRec = parseFloat(r.receiveble_fees || 0);
                let paid = parseFloat(r.total_received_fees || 0);
                let due = Math.max(0, totalRec - paid);
                let isComplete = (parseInt(r.iscomplete, 10) === 1 || due === 0);

                let alloc = 0;
                if (!isComplete && due > 0 && remAmt > 0) {
                    alloc = Math.min(remAmt, due);
                    remAmt -= alloc;
                }
                let bal = Math.max(0, due - alloc);
                updateCardRowUI(recId, alloc, bal, isComplete);
            });
        }
    }

    function updateCardRowUI(recId, alloc, bal, isComplete) {
        let $setoffEl = $('#card_setoff_' + recId);
        let $balEl = $('#card_bal_' + recId);
        let $card = $('#rec_card_' + recId);

        if ($setoffEl.length) {
            $setoffEl.text('₹' + alloc.toFixed(2));
            if (alloc > 0) {
                $setoffEl.css({ 'color': '#059669', 'font-weight': '800' });
            } else {
                $setoffEl.css({ 'color': '#64748b', 'font-weight': '600' });
            }
        }

        if ($balEl.length) {
            $balEl.text('₹' + bal.toFixed(2));
            if (bal === 0 && !isComplete && alloc > 0) {
                $balEl.css({ 'color': '#059669', 'font-weight': '800' });
            } else if (bal > 0) {
                $balEl.css({ 'color': '#dc2626', 'font-weight': '800' });
            } else {
                $balEl.css({ 'color': '#059669', 'font-weight': '800' });
            }
        }

        if (alloc > 0 && !isComplete) {
            $card.addClass('selected-setoff-card');
        } else if (selectedSpecificRecId === 0 && !selectedAllPending) {
            $card.removeClass('selected-setoff-card');
        }
    }

    function setFullPendingFees() {
        let typedVal = parseFloat($('#txt_pay_amount').val() || 0);

        if (typedVal <= 0) {
            if (typeof swal !== 'undefined') {
                swal("Amount Required", "Please enter an amount before clicking Set Fees.", "warning");
            } else {
                alert('Please enter an amount before clicking Set Fees.');
            }
            return;
        }

        selectedSpecificRecId = 0;
        selectedAllPending = true;
        $('#selected_item_hint').remove();
        $('.rec-item-card').removeClass('selected-setoff-card');

        $('#txt_pay_amount').val(typedVal);
        $('.rec-item-card.is-pending').addClass('selected-setoff-card');

        let hintHtm = '<div id="selected_item_hint" style="background:#ecfdf5; color:#065f46; border:1px solid #a7f3d0; padding:10px 14px; border-radius:12px; font-size:12.5px; font-weight:700; margin-bottom:12px; display:flex; align-items:center; justify-content:space-between; font-family:\'Inter\',sans-serif;">' +
            '<span><i class="fa fa-check-circle"></i> Fees set for SetOff (<strong>₹' + typedVal.toFixed(2) + '</strong>)</span>' +
            '<a href="javascript:void(0)" onclick="clearSelectedItem()" style="color:#ef4444; font-weight:800; text-decoration:none; margin-left:8px;">✕ Clear</a>' +
          '</div>';
        if ($('.action-card > div:first').length) {
            $(hintHtm).insertBefore('.action-card > div:first');
        }
        updateDuesLogSetOffCalculations();
        $('#setoff_action_buttons').fadeIn(200);
    }

    function setOffSpecificItem(recId, dueAmt, headId, flagId, desc) {
        selectedSpecificRecId = recId;
        selectedAllPending = false;
        $('#txt_pay_amount').val(dueAmt);

        $('.rec-item-card').removeClass('selected-setoff-card');
        $('#rec_card_' + recId).addClass('selected-setoff-card');

        $('html, body').animate({ scrollTop: 0 }, 'fast');

        let cleanDesc = desc || 'Receivable Item';
        let hintHtm = '<div id="selected_item_hint" style="background:#eef2ff; color:#4f46e5; border:1px solid #c7d2fe; padding:10px 14px; border-radius:12px; font-size:12.5px; font-weight:700; margin-bottom:12px; display:flex; align-items:center; justify-content:space-between; font-family:\'Inter\',sans-serif;">' +
            '<span><i class="fa fa-info-circle"></i> Item selected for SetOff: <strong>' + cleanDesc + '</strong> (₹' + dueAmt.toFixed(2) + ')</span>' +
            '<a href="javascript:void(0)" onclick="clearSelectedItem()" style="color:#ef4444; font-weight:800; text-decoration:none; margin-left:8px;">✕ Clear</a>' +
          '</div>';

        $('#selected_item_hint').remove();
        if ($('.action-card > div:first').length) {
            $(hintHtm).insertBefore('.action-card > div:first');
        }
        updateDuesLogSetOffCalculations();
        $('#setoff_action_buttons').fadeIn(200);
    }

    function clearSelectedItem() {
        selectedSpecificRecId = 0;
        selectedAllPending = false;
        $('#selected_item_hint').remove();
        $('.rec-item-card').removeClass('selected-setoff-card');
        $('#txt_pay_amount').val('');
        updateDuesLogSetOffCalculations();
        $('#setoff_action_buttons').hide();
    }

    function buildReceivedArray(payAmount) {
        let receivedArray = [];
        let remAmt = payAmount;

        if (!raw_receivables_data || raw_receivables_data.length === 0) {
            return receivedArray;
        }

        if (selectedSpecificRecId > 0) {
            let item = raw_receivables_data.find(function(r) {
                return parseInt(r.recieveble_id, 10) === parseInt(selectedSpecificRecId, 10);
            });

            if (item) {
                let totalRec = parseFloat(item.receiveble_fees || 0);
                let paid = parseFloat(item.total_received_fees || 0);
                let due = totalRec - paid;
                if (due < 0) due = 0;

                let alloc = Math.min(remAmt, due);
                let bal = Math.max(0, due - alloc);
                receivedArray.push({
                    receiveble_id: item.recieveble_id,
                    flag: item.flag || 1,
                    received: alloc,
                    balance: bal
                });
                return receivedArray;
            }
        }

        for (let i = 0; i < raw_receivables_data.length; i++) {
            let r = raw_receivables_data[i];
            let totalRec = parseFloat(r.receiveble_fees || 0);
            let paid = parseFloat(r.total_received_fees || 0);
            let due = totalRec - paid;
            if (due < 0) due = 0;
            let isComplete = (parseInt(r.iscomplete, 10) === 1 || due === 0);

            if (!isComplete && due > 0) {
                let alloc = Math.min(remAmt, due);
                let bal = Math.max(0, due - alloc);
                receivedArray.push({
                    receiveble_id: r.recieveble_id,
                    flag: r.flag || 1,
                    received: alloc,
                    balance: bal
                });
                remAmt -= alloc;
                if (remAmt <= 0) break;
            }
        }

        return receivedArray;
    }

    function submitCashPayment() {
        let amt = parseFloat($('#txt_pay_amount').val() || 0);
        if (amt <= 0) {
            if (typeof swal !== 'undefined') {
                swal("Invalid Amount", "Please enter a valid payment amount.", "warning");
            } else {
                alert('Please enter a valid amount.');
            }
            return;
        }

        let receivedArray = buildReceivedArray(amt);
        if (receivedArray.length === 0) {
            if (typeof swal !== 'undefined') {
                swal("No Dues", "No pending receivable dues found for this member.", "info");
            } else {
                alert('No pending receivable dues found.');
            }
            return;
        }

        let performSubmit = function() {
            if (typeof load_overlay === 'function') load_overlay();
            let payload = {
                action: 'setoff_receiveble',
                member_id: ACTIVE_MEMBER_ID,
                date: $('#txt_pay_date').val(),
                received_array: JSON.stringify(receivedArray),
                transaction_type: $('#txt_trans_type').val()
            };

            $.post('../api/fees_receiveble.php', payload, function(res) {
                if (typeof close_overlay === 'function') close_overlay();
                clearSelectedItem();
                loadMemberProfile();
                loadReceivablesList();
                if (typeof swal !== 'undefined') {
                    swal({ title: "Saved!", text: "Payment setoff recorded successfully!", type: "success" }, function() {
                        if ($('#receivables_list_container').length) {
                            $('html, body').animate({ scrollTop: $('#receivables_list_container').offset().top - 80 }, 'smooth');
                        }
                    });
                } else {
                    alert('Payment setoff recorded successfully!');
                    if ($('#receivables_list_container').length) {
                        $('html, body').animate({ scrollTop: $('#receivables_list_container').offset().top - 80 }, 'smooth');
                    }
                }
            }).fail(function(xhr) {
                if (typeof close_overlay === 'function') close_overlay();
                let msg = 'Error recording payment setoff.';
                try {
                    let errObj = typeof xhr.responseText === 'string' ? JSON.parse(xhr.responseText) : xhr.responseText;
                    if (errObj && errObj.Message) msg = errObj.Message;
                } catch(e) {}

                if (typeof swal !== 'undefined') {
                    swal("Error", msg, "error");
                } else {
                    alert(msg);
                }
            });
        };

        if (typeof swal !== 'undefined') {
            swal({
                title: "Confirm SetOff",
                text: "Are you sure you want to set off payment of ₹" + amt.toFixed(2) + "?",
                type: "warning",
                showCancelButton: true,
                confirmButtonColor: "#10b981",
                confirmButtonText: "Yes, Set Off!",
                cancelButtonText: "Cancel",
                closeOnConfirm: false
            }, function(isConfirm) {
                if (isConfirm) {
                    performSubmit();
                }
            });
        } else {
            if (confirm("Are you sure you want to set off payment of ₹" + amt.toFixed(2) + "?")) {
                performSubmit();
            }
        }
    }

    function submitWalletPayment() {
        let amt = parseFloat($('#txt_pay_amount').val() || 0);
        if (amt <= 0) {
            if (typeof swal !== 'undefined') {
                swal("Invalid Amount", "Please enter a valid payment amount.", "warning");
            } else {
                alert('Please enter a valid amount.');
            }
            return;
        }

        if (currentWalletBalance <= 0) {
            if (typeof swal !== 'undefined') {
                swal("Insufficient Wallet Balance", "Member has 0 wallet balance. Cannot pay via wallet.", "warning");
            } else {
                alert("Insufficient Wallet Balance: Member has 0 wallet balance.");
            }
            return;
        }

        if (amt > currentWalletBalance) {
            if (typeof swal !== 'undefined') {
                swal("Insufficient Wallet Balance", "Amount (₹" + amt.toFixed(2) + ") exceeds available wallet balance (₹" + currentWalletBalance.toFixed(2) + ").", "warning");
            } else {
                alert("Amount exceeds available wallet balance (₹" + currentWalletBalance.toFixed(2) + ").");
            }
            return;
        }

        let receivedArray = buildReceivedArray(amt);
        if (receivedArray.length === 0) {
            if (typeof swal !== 'undefined') {
                swal("No Dues", "No pending receivable dues found for this member.", "info");
            } else {
                alert('No pending receivable dues found.');
            }
            return;
        }

        let performWalletSubmit = function() {
            if (typeof load_overlay === 'function') load_overlay();
            let payload = {
                action: 'setoff_receiveble_from_wallet',
                member_id: ACTIVE_MEMBER_ID,
                date: $('#txt_pay_date').val(),
                received_array: JSON.stringify(receivedArray),
                transaction_type: $('#txt_trans_type').val()
            };

            $.post('../api/fees_receiveble.php', payload, function(res) {
                if (typeof close_overlay === 'function') close_overlay();
                clearSelectedItem();
                loadMemberProfile();
                loadReceivablesList();
                if (typeof swal !== 'undefined') {
                    swal({ title: "Saved!", text: "Wallet setoff recorded successfully!", type: "success" }, function() {
                        if ($('#receivables_list_container').length) {
                            $('html, body').animate({ scrollTop: $('#receivables_list_container').offset().top - 80 }, 'smooth');
                        }
                    });
                } else {
                    alert('Wallet setoff recorded successfully!');
                    if ($('#receivables_list_container').length) {
                        $('html, body').animate({ scrollTop: $('#receivables_list_container').offset().top - 80 }, 'smooth');
                    }
                }
            }).fail(function(xhr) {
                if (typeof close_overlay === 'function') close_overlay();
                let errMsg = 'Error recording wallet setoff.';
                try {
                    let errObj = typeof xhr.responseText === 'string' ? JSON.parse(xhr.responseText) : xhr.responseText;
                    if (errObj && errObj.Message) errMsg = errObj.Message;
                } catch(e) {}

                if (typeof swal !== 'undefined') {
                    swal("Error", errMsg, "error");
                } else {
                    alert(errMsg);
                }
            });
        };

        if (typeof swal !== 'undefined') {
            swal({
                title: "Confirm Wallet SetOff",
                text: "Are you sure you want to set off ₹" + amt.toFixed(2) + " from wallet balance?",
                type: "warning",
                showCancelButton: true,
                confirmButtonColor: "#6366f1",
                confirmButtonText: "Yes, Pay via Wallet!",
                cancelButtonText: "Cancel",
                closeOnConfirm: false
            }, function(isConfirm) {
                if (isConfirm) {
                    performWalletSubmit();
                }
            });
        } else {
            if (confirm("Are you sure you want to set off ₹" + amt.toFixed(2) + " from wallet balance?")) {
                performWalletSubmit();
            }
        }
    }
    </script>

    <!-- Add Custom Guest Fee Modal -->
    <div class="modal fade" id="addGuestFeeModal" tabindex="-1" role="dialog">
        <div class="modal-dialog modal-dialog-centered" role="document">
            <div class="modal-content" style="border-radius:24px; border:none; box-shadow: 0 25px 50px -12px rgba(0, 0, 0, 0.25);">
                <div class="modal-header" style="background: linear-gradient(135deg, #f59e0b 0%, #d97706 100%); padding: 18px 24px; color: white;">
                    <button type="button" class="close" data-dismiss="modal" style="color:white; opacity:0.9; font-size:24px;">&times;</button>
                    <h4 class="modal-title" style="font-weight:800; font-size:16px; margin:0;"><i class="fa fa-plus-circle"></i> Add Custom Guest Fee</h4>
                </div>
                <div class="modal-body" style="padding: 24px;">
                    <div class="form-group" style="margin-bottom:16px;">
                        <label style="font-size:11px; font-weight:800; color:#64748b; text-transform:uppercase;">Fee Amount (₹)</label>
                        <input type="number" id="txt_guest_fee_amt" class="form-control" placeholder="e.g. 250" style="border-radius:12px; height:46px; font-weight:800; font-size:16px;">
                    </div>
                    <div class="form-group" style="margin-bottom:16px;">
                        <label style="font-size:11px; font-weight:800; color:#64748b; text-transform:uppercase;">Date</label>
                        <input type="date" id="txt_guest_fee_date" class="form-control" value="<?php echo date('Y-m-d'); ?>" style="border-radius:12px; height:46px; font-weight:700;">
                    </div>
                    <div class="form-group" style="margin-bottom:16px;">
                        <label style="font-size:11px; font-weight:800; color:#64748b; text-transform:uppercase;">Description / Reason</label>
                        <input type="text" id="txt_guest_fee_desc" class="form-control" placeholder="e.g. Guest Daily Play Fee" value="Guest Play Fee" style="border-radius:12px; height:46px; font-weight:600;">
                    </div>
                    <button type="button" onclick="submitCustomGuestFee()" class="btn btn-block" style="background: linear-gradient(135deg, #f59e0b 0%, #d97706 100%); color:white; font-weight:800; height:46px; border-radius:14px; border:none; margin-top:10px;">
                        <i class="fa fa-check"></i> Post Guest Fee Receivable
                    </button>
                </div>
            </div>
        </div>
    </div>

    <script>
    $(document).ready(function() {
        loadMemberBalances();
        loadReceivablesList();

        $('#txt_pay_amount').on('input keyup change', function() {
            let val = parseFloat($(this).val() || 0);
            if (val <= 0) {
                selectedSpecificRecId = 0;
                selectedAllPending = false;
                $('#selected_item_hint').remove();
                $('.rec-item-card').removeClass('selected-setoff-card');
                $('#setoff_action_buttons').hide();
                updateDuesLogSetOffCalculations();
            } else if (selectedSpecificRecId > 0 || selectedAllPending) {
                updateDuesLogSetOffCalculations();
            }
        });
    });

    function submitCustomGuestFee() {
        let amt = parseFloat($('#txt_guest_fee_amt').val() || 0);
        let desc = $('#txt_guest_fee_desc').val() || 'Guest Custom Fee';
        let dt = $('#txt_guest_fee_date').val() || '<?php echo date('Y-m-d'); ?>';

        if (amt <= 0) {
            alert('Please enter a valid fee amount.');
            return;
        }

        $.post('../api/fees_receiveble.php', {
            action: 'add_custom_guest_receivable',
            member_id: ACTIVE_MEMBER_ID,
            amount: amt,
            discription: desc,
            date: dt,
            head: 12
        }, function(res) {
            $('#addGuestFeeModal').modal('hide');
            if (typeof swal !== 'undefined') {
                swal({ title: "Posted!", text: "Guest fee receivable added successfully!", type: "success" }, function() {
                    location.reload();
                });
            } else {
                alert('Guest fee receivable added successfully!');
                location.reload();
            }
        }).fail(function() {
            alert('Error adding guest fee receivable.');
        });
    }
    </script>
</body>
</html>
