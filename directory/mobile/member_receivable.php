<?php
session_start();
include_once '../../app_common/enums.php';
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

        /* Tool Split Container matching directory/fees_receiveble */
        .tool-panel-container {
            display: flex;
            flex-direction: column;
            gap: 12px;
            margin-bottom: 16px;
        }

        .tool-card-box {
            background: #ffffff;
            border-radius: 18px;
            border: 1.5px solid #e2e8f0;
            box-shadow: 0 2px 8px rgba(0,0,0,0.03);
            padding: 16px;
            display: flex;
            flex-direction: column;
            gap: 10px;
        }

        .tool-card-box h4 {
            font-weight: 800;
            font-size: 13.5px;
            margin: 0 !important;
            color: #0f172a;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            display: flex;
            align-items: center;
            gap: 8px;
        }

        /* Action form layout */
        .action-input-group {
            display: flex;
            align-items: center;
            background: #f8fafc !important;
            border: 1.5px solid #e2e8f0 !important;
            border-radius: 14px !important;
            padding: 4px 6px !important;
            transition: all 0.25s ease;
        }

        .action-input-group:focus-within {
            border-color: #6366f1 !important;
            box-shadow: 0 0 0 4px rgba(99, 102, 241, 0.12) !important;
        }

        .action-input-group input.form-control {
            background: transparent !important;
            border: none !important;
            padding: 8px 10px !important;
            height: auto !important;
            color: #0f172a !important;
            font-size: 15px !important;
            font-weight: 800 !important;
            flex: 1 !important;
            min-width: 0 !important;
        }

        .action-btn-group {
            display: flex;
            align-items: center;
            flex-shrink: 0;
        }

        .btn-set-fees-custom {
            background: #ffffff !important;
            color: #0f172a !important;
            border: 1.5px solid #e2e8f0 !important;
            border-radius: 10px !important;
            padding: 8px 14px !important;
            font-weight: 800 !important;
            font-size: 12.5px !important;
            transition: all 0.2s ease;
            white-space: nowrap !important;
            flex-shrink: 0 !important;
            cursor: pointer;
        }

        .btn-set-fees-custom:hover, .btn-set-fees-custom:active {
            border-color: #6366f1 !important;
            color: #6366f1 !important;
            background: #f8faff !important;
        }

        .btn-setoff-custom {
            background: linear-gradient(135deg, #6366f1 0%, #4f46e5 100%) !important;
            color: #ffffff !important;
            border-radius: 10px !important;
            padding: 8px 16px !important;
            font-weight: 800 !important;
            font-size: 12.5px !important;
            border: none !important;
            box-shadow: 0 4px 12px rgba(99, 102, 241, 0.28) !important;
            transition: all 0.2s ease;
            margin-left: 6px;
            white-space: nowrap !important;
            flex-shrink: 0 !important;
            cursor: pointer;
        }

        .btn-setoff-custom:hover, .btn-setoff-custom:active {
            transform: translateY(-1px);
            opacity: 0.95;
            box-shadow: 0 6px 16px rgba(99, 102, 241, 0.35) !important;
        }

        .rec-item-card {
            background: #ffffff; border-radius: 16px; border: 1px solid #e2e8f0; padding: 14px;
            margin-bottom: 10px; box-shadow: 0 2px 6px rgba(0,0,0,0.02);
            transition: all 0.25s ease;
        }
        .rec-item-card.selected-setoff-card {
            border: 2px solid #6366f1 !important;
            background: #f8faff !important;
            box-shadow: 0 4px 18px rgba(99, 102, 241, 0.15) !important;
        }
        .rec-item-card.is-canceled {
            opacity: 0.78;
            background: #f8fafc !important;
            border: 1.5px dashed #cbd5e1 !important;
        }
        .rec-item-header { display: flex; align-items: center; justify-content: space-between; margin-bottom: 6px; }
        .rec-item-date { font-size: 11px; font-weight: 700; color: #64748b; text-transform: uppercase; }
        .status-pill { font-size: 10px; font-weight: 800; padding: 3px 8px; border-radius: 6px; text-transform: uppercase; }
        .status-pill-paid { background: #dcfce7; color: #166534; }
        .status-pill-pending { background: #fef3c7; color: #92400e; }
        .status-pill-canceled { background: #f1f5f9; color: #64748b; }

        .btn-table-action-edit {
            background-color: rgba(59, 130, 246, 0.1) !important;
            color: #2563eb !important;
            border-radius: 8px !important;
            font-size: 11.5px !important;
            font-weight: 700 !important;
            padding: 5px 12px !important;
            border: none !important;
            transition: all 0.2s ease;
            display: inline-flex;
            align-items: center;
            gap: 4px;
            cursor: pointer;
        }
        .btn-table-action-edit:hover, .btn-table-action-edit:active {
            background-color: #2563eb !important;
            color: #ffffff !important;
        }
        .btn-table-action-cancel {
            border-radius: 8px !important;
            font-size: 11.5px !important;
            font-weight: 700 !important;
            padding: 5px 12px !important;
            border: none !important;
            transition: all 0.2s ease;
            display: inline-flex;
            align-items: center;
            gap: 4px;
            cursor: pointer;
        }
        .btn-table-action-cancel.cancel {
            background-color: rgba(239, 68, 68, 0.1) !important;
            color: #ef4444 !important;
        }
        .btn-table-action-cancel.cancel:hover, .btn-table-action-cancel.cancel:active {
            background-color: #ef4444 !important;
            color: #ffffff !important;
        }
        .btn-table-action-cancel.proceed {
            background-color: rgba(16, 185, 129, 0.1) !important;
            color: #10b981 !important;
        }
        .btn-table-action-cancel.proceed:hover, .btn-table-action-cancel.proceed:active {
            background-color: #10b981 !important;
            color: #ffffff !important;
        }

        .rec-item-title { font-size: 14px; font-weight: 800; color: #0f172a; margin: 0 0 6px 0; }
        .rec-item-grid { display: grid; grid-template-columns: repeat(4, 1fr); gap: 4px; font-size: 11px; background: #f8fafc; padding: 8px 6px; border-radius: 10px; margin-bottom: 6px; text-align: center; }
        .rec-item-lbl { font-size: 9.5px; font-weight: 700; color: #64748b; text-transform: uppercase; }
        .rec-item-val { font-weight: 800; color: #0f172a; margin-top: 1px; }

        .mem-pick-row {
            padding: 12px 14px; border-bottom: 1px solid #f1f5f9; display: flex; align-items: center; gap: 12px;
            cursor: pointer; transition: background 0.15s ease;
        }
        .mem-pick-row:hover { background: #ecfdf5; }

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

        <!-- search bar starts (Tools Panel matching directory/fees_receiveble) -->
        <div class="tool-panel-container">
            <!-- Cash/Bank Setoff Panel -->
            <div class="tool-card-box">
                <h4><i class="fa fa-money" style="color:#10b981;"></i> Set-Off Receivable (Cash/Bank)</h4>
                <div class="action-input-group">
                    <input type="number" placeholder="Enter amount to pay" id="txt_AmountPaid" name="submit" class="form-control" step="0.01">
                    <div class="action-btn-group">
                        <button class="btn-set-fees-custom" onclick="setFees()" type="button">Set Fees</button>
                        <button type="button" id="setoff_buttuon" class="btn-setoff-custom" onclick="setOffform()" style="display:none;">SetOff</button>
                    </div>
                </div>
            </div>

            <!-- Wallet Setoff Panel -->
            <div class="tool-card-box">
                <h4><i class="fa fa-credit-card" style="color:#6366f1;"></i> Set-Off Receivable (Wallet)</h4>
                <div class="action-input-group">
                    <input type="number" placeholder="Wallet set-off amount" id="txt_wallet_amount_input" name="wallet" class="form-control" step="0.01">
                    <div class="action-btn-group">
                        <button class="btn-set-fees-custom" onclick="setFeesFromWallet()" id="setFeesFromWallet_button" type="button">Set Fees</button>
                        <button type="button" id="setoff_buttuon_wallet" class="btn-setoff-custom" onclick="setOffformforWallet()" style="display:none;">SetOff</button>
                    </div>
                </div>
                <div id="add_from_wallet" style="margin-top:-2px;">
                    <span style="font-size:11.5px; font-weight:700; color:#6366f1; background:rgba(99,102,241,0.08); padding:5px 12px; border-radius:10px; display:inline-flex; align-items:center; gap:6px;">
                        <i class="fa fa-credit-card"></i> Available Wallet Balance: <strong id="txt_wallet_avail_display">₹0.00</strong>
                    </span>
                </div>
            </div>
        </div>
        <!-- search bar ends -->

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

    <!-- modal for setoff date/transaction type starts (Cash/Bank) -->
    <div class="modal inmodal fade" id="setOffModel" tabindex="-1" role="dialog" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content" style="border-radius: 20px; overflow: hidden; border: none; box-shadow: 0 20px 40px rgba(0,0,0,0.25);">
                <form method="POST" id="setoff_form" onsubmit="event.preventDefault();">
                    <div class="modal-header" style="background: linear-gradient(135deg, #6366f1 0%, #4f46e5 100%); padding: 18px 22px; color: #ffffff; text-align: left;">
                        <button type="button" class="close" data-dismiss="modal" style="color: #ffffff; opacity: 0.9; font-size: 24px;" onclick="closesetOffform();">&times;</button>
                        <h4 style="margin: 0; font-weight: 800; font-size: 17px;"><i class="fa fa-exchange"></i> Set-Off Cash/Bank</h4>
                        <p style="margin: 3px 0 0 0; opacity: 0.9; font-size: 12px;">Finalize date and transaction parameters for chronological allocation</p>
                    </div>

                    <div class="modal-body" style="padding: 20px 22px; background: #ffffff;">
                        <div class="form-group" style="margin-bottom: 14px;">
                            <label style="font-size:11px; font-weight:800; color:#64748b; text-transform:uppercase;">Payment Date</label>
                            <input type="date" id="setoff_date" name="setoff_date" value="<?php echo date('Y-m-d'); ?>" class="form-control" style="border-radius:12px; font-weight:700;">
                        </div>
                        
                        <div class="form-group" style="margin-bottom: 14px;">
                            <label style="font-size:11px; font-weight:800; color:#64748b; text-transform:uppercase;">Transaction Type</label>
                            <select class="dropdown form-control" name="selected_transaction" id="selected_transaction_type" style="border-radius:12px; font-weight:700;">
                                <?php foreach (TransactionType::all() as $key => $label): ?>
                                    <option value="<?= $key ?>"><?= htmlspecialchars($label) ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>

                        <div class="form-group" style="margin-top: 14px; margin-bottom: 6px;">
                            <label style="font-size:11px; font-weight:800; color:#64748b; text-transform:uppercase;">Upload Bill Photo (Optional)</label>
                            <input type="file" id="bill_photo" name="bill_photo" class="form-control" accept="image/*,application/pdf" style="padding: 6px 10px; border-radius: 12px; height:auto;">
                        </div>
                    </div>
                    
                    <div class="modal-footer" style="background: #ffffff; border-top: 1px solid #f1f5f9; padding: 14px 22px; display: flex; justify-content: flex-end; gap: 10px;">
                        <button type="button" class="btn btn-default" style="border-radius: 10px; font-weight: 700; padding: 8px 16px;" onclick="closesetOffform();">Close</button>
                        <button type="button" class="btn btn-primary" style="background: linear-gradient(135deg, #6366f1 0%, #4f46e5 100%); border: none; border-radius: 10px; font-weight: 800; padding: 8px 20px;" onclick="setOffReceiveble();">Save & Settle</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
    <!-- modal for setoff date/transaction type ends -->

    <!-- setoff form for wallet start -->
    <div class="modal inmodal fade" id="setOffwalletModel" tabindex="-1" role="dialog" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content" style="border-radius: 20px; overflow: hidden; border: none; box-shadow: 0 20px 40px rgba(0,0,0,0.25);">
                <form method="POST" id="setoff_form_wallet" onsubmit="event.preventDefault();">
                    <div class="modal-header" style="background: linear-gradient(135deg, #6366f1 0%, #4f46e5 100%); padding: 18px 22px; color: #ffffff; text-align: left;">
                        <button type="button" class="close" data-dismiss="modal" style="color: #ffffff; opacity: 0.9; font-size: 24px;" onclick="closesetOffformforWallet();">&times;</button>
                        <h4 style="margin: 0; font-weight: 800; font-size: 17px;"><i class="fa fa-credit-card"></i> Set-Off From Wallet</h4>
                        <p style="margin: 3px 0 0 0; opacity: 0.9; font-size: 12px;">Confirm date and wallet deduction parameters</p>
                    </div>

                    <div class="modal-body" style="padding: 20px 22px; background: #ffffff;">
                        <div class="form-group" style="margin-bottom: 14px;">
                            <label style="font-size:11px; font-weight:800; color:#64748b; text-transform:uppercase;">Deduction Date</label>
                            <input type="date" id="setoff_date_wallet" name="setoff_date_wallet" value="<?php echo date('Y-m-d'); ?>" class="form-control" style="border-radius:12px; font-weight:700;">
                        </div>
                        
                        <div class="form-group" style="margin-bottom: 6px;">
                            <label style="font-size:11px; font-weight:800; color:#64748b; text-transform:uppercase;">Transaction Type</label>
                            <select class="dropdown form-control" name="selected_transaction" id="selected_transaction_type_wallet" style="border-radius:12px; font-weight:700;">
                                <?php foreach (TransactionType::all() as $key => $label): ?>
                                    <option value="<?= $key ?>"><?= htmlspecialchars($label) ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                    </div>
                    
                    <div class="modal-footer" style="background: #ffffff; border-top: 1px solid #f1f5f9; padding: 14px 22px; display: flex; justify-content: flex-end; gap: 10px;">
                        <button type="button" class="btn btn-default" style="border-radius: 10px; font-weight: 700; padding: 8px 16px;" onclick="closesetOffformforWallet();">Close</button>
                        <button type="button" class="btn btn-primary" style="background: linear-gradient(135deg, #6366f1 0%, #4f46e5 100%); border: none; border-radius: 10px; font-weight: 800; padding: 8px 20px;" onclick="setOffReceivebleFromWallet();">Save & Settle</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
    <!-- setoff form for wallet end -->

    <!-- Edit Receivable Modal -->
    <div class="modal inmodal fade" id="receivebleModel" tabindex="-1" role="dialog" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content" style="border-radius: 20px; overflow: hidden; border: none; box-shadow: 0 20px 40px rgba(0,0,0,0.25);">
                <form method="POST" id="receiveble_form" onsubmit="event.preventDefault();">
                    <input type="hidden" id="hdn_id" name="hdn_id">
                    <div class="modal-header" style="background: linear-gradient(135deg, #6366f1 0%, #4f46e5 100%); padding: 18px 22px; color: #ffffff; text-align: left;">
                        <button type="button" class="close" data-dismiss="modal" style="color: #ffffff; opacity: 0.9; font-size: 24px;" onclick="closeaddRecieveble();">&times;</button>
                        <h4 style="margin: 0; font-weight: 800; font-size: 17px;"><i class="fa fa-pencil"></i> Edit Receivable Amount</h4>
                        <p style="margin: 3px 0 0 0; opacity: 0.9; font-size: 12px;">Modify date, amount or description parameters</p>
                    </div>
                    <div class="modal-body" style="padding: 20px 22px; background: #ffffff;">
                        <div class="form-group" style="margin-bottom: 14px;">
                            <label style="font-size:11px; font-weight:800; color:#64748b; text-transform:uppercase;">Date</label>
                            <input type="date" id="rec_date" name="rec_date" class="form-control" style="border-radius:12px; font-weight:700;">
                        </div>
                        <div class="form-group" style="margin-bottom: 14px;">
                            <label style="font-size:11px; font-weight:800; color:#64748b; text-transform:uppercase;">Amount</label>
                            <input type="number" id="rec_amount" name="amount" placeholder="Receivable amount" class="form-control" step="0.01" style="border-radius:12px; font-weight:700;">
                        </div>
                        <div class="form-group" style="margin-bottom: 6px;">
                            <label style="font-size:11px; font-weight:800; color:#64748b; text-transform:uppercase;">Description</label>
                            <textarea id="rec_discription" name="discription" rows="3" placeholder="Maximum 150 characters" class="form-control" style="border-radius:12px; font-weight:600;"></textarea>
                        </div>
                    </div>
                    <div class="modal-footer" style="background: #ffffff; border-top: 1px solid #f1f5f9; padding: 14px 22px; display: flex; justify-content: flex-end; gap: 10px;">
                        <button type="button" class="btn btn-default" style="border-radius: 10px; font-weight: 700; padding: 8px 16px;" onclick="closeaddRecieveble();">Close</button>
                        <button type="button" class="btn btn-primary" style="background: linear-gradient(135deg, #6366f1 0%, #4f46e5 100%); border: none; border-radius: 10px; font-weight: 800; padding: 8px 20px;" onclick="saveReceiveble();">Save</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
    <!-- edit receivable modal end -->

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
                $('#txt_wallet_avail_display').text(walStr);
            } catch(e) {}
        });
    }

    let raw_receivables_data = [];

    function loadReceivablesList() {
        $.post('../api/fees_receiveble.php', { action: 'load_data', page: 1, member_id: ACTIVE_MEMBER_ID, no_limit: 1 }, function(res) {
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

                items.forEach(function(r, index) {
                    let totalRec = parseFloat(r.receiveble_fees || 0);
                    let paid = parseFloat(r.total_received_fees || 0);
                    let due = totalRec - paid;
                    if (due < 0) due = 0;
                    let isCanceled = (parseInt(r.cancel, 10) === 1);
                    let isComplete = (parseInt(r.iscomplete, 10) === 1 || due === 0);
                    let statusClass = isCanceled ? 'status-pill-canceled' : (isComplete ? 'status-pill-paid' : 'status-pill-pending');
                    let statusText = isCanceled ? 'CANCELED' : (isComplete ? 'SETTLED' : 'PENDING');
                    let dateStr = r.date ? new Date(r.date).toLocaleDateString('en-GB', { day:'numeric', month:'short', year:'numeric' }) : '--';

                    htm += `
                        <div class="rec-item-card ${isCanceled ? 'is-canceled' : (isComplete ? 'is-complete' : 'is-pending')}" id="rec_card_${r.recieveble_id}" data-receiveble-id="${r.recieveble_id}" data-flag="${r.flag || 1}" data-remaining-balance="${isCanceled ? 0 : due}">
                            <div class="rec-item-header">
                                <span class="rec-item-date"><i class="fa fa-calendar"></i> ${dateStr}</span>
                                <span class="status-pill ${statusClass}">${statusText}</span>
                            </div>
                            <h4 class="rec-item-title">${r.name || r.head_name || 'Fee Receivable'} ${r.discription ? '— ' + r.discription : ''}</h4>
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
                                    <div class="rec-item-val card-setoff-val" style="color:#64748b;">₹0.00</div>
                                </div>
                                <div>
                                    <div class="rec-item-lbl">Balance Dues</div>
                                    <div class="rec-item-val card-bal-val" style="color:${isCanceled ? '#94a3b8' : (due > 0 ? '#ef4444' : '#10b981')};">₹${due.toFixed(2)}</div>
                                </div>
                            </div>
                            <div class="rec-card-actions" style="display:flex; justify-content:flex-end; gap:8px; margin-top:8px; padding-top:8px; border-top:1px dashed #f1f5f9;">
                                <button type="button" class="btn btn-table-action-edit" onclick="editReceiveble(${index});"><i class="fa fa-pencil"></i> Edit</button>
                                ${isCanceled ? 
                                    `<button type="button" class="btn btn-table-action-cancel proceed" onclick="cancelFeeDetails(${r.recieveble_id}, 1);"><i class="fa fa-refresh"></i> Re-open</button>` :
                                    `<button type="button" class="btn btn-table-action-cancel cancel" onclick="cancelFeeDetails(${r.recieveble_id}, 0);"><i class="fa fa-ban"></i> Cancel</button>`
                                }
                            </div>
                        </div>
                    `;
                });

                $('#receivables_list_container').html(htm);
            } catch(e) {
                $('#receivables_list_container').html('<div style="text-align:center; padding:30px; color:#ef4444;">Error loading receivables.</div>');
            }
        });
        $('#setoff_buttuon').hide();
        $('#setoff_buttuon_wallet').hide();
    }

    function setFees() {
        var balAmt = 0;
        var TotalAmount = parseFloat($('#txt_AmountPaid').val()) || 0;
        if (TotalAmount <= 0) {
            if (typeof swal !== 'undefined') {
                swal("Amount Required", "Please enter an amount before clicking Set Fees.", "warning");
            } else {
                alert("Please enter an amount before clicking Set Fees.");
            }
            return;
        }

        // Reset all cards to their original remaining balance state
        $('.rec-item-card').each(function() {
            var remaining_balance = parseFloat($(this).data('remaining-balance')) || 0;
            $(this).find('.card-setoff-val').text('₹0.00').css({ 'color': '#64748b', 'font-weight': '600' });
            $(this).find('.card-bal-val').text('₹' + remaining_balance.toFixed(2)).css({ 'color': (remaining_balance > 0 ? '#ef4444' : '#10b981'), 'font-weight': '800' });
            $(this).removeClass('selected-setoff-card');
        });

        $('.rec-item-card').each(function() {
            var remaining_balance = parseFloat($(this).data('remaining-balance')) || 0;
            if (remaining_balance <= 0) return; // skip completed

            if (TotalAmount >= remaining_balance) {
                $(this).find('.card-bal-val').text('₹0.00').css({ 'color': '#10b981', 'font-weight': '800' });
                $(this).find('.card-setoff-val').text('₹' + remaining_balance.toFixed(2)).css({ 'color': '#10b981', 'font-weight': '800' });
                $(this).addClass('selected-setoff-card');
                TotalAmount = TotalAmount - remaining_balance;
            } else {
                balAmt = remaining_balance - TotalAmount;
                $(this).find('.card-bal-val').text('₹' + balAmt.toFixed(2)).css({ 'color': '#ef4444', 'font-weight': '800' });
                $(this).find('.card-setoff-val').text('₹' + TotalAmount.toFixed(2)).css({ 'color': '#10b981', 'font-weight': '800' });
                $(this).addClass('selected-setoff-card');
                return false;
            }
        });
        $('#setoff_buttuon').show();
        $('#setoff_buttuon_wallet').hide();
    }

    function setFeesFromWallet() {
        var balAmt = 0;
        var TotalAmount = parseFloat($('#txt_wallet_amount_input').val()) || 0;
        if (TotalAmount <= 0) {
            if (typeof swal !== 'undefined') {
                swal("Amount Required", "Please enter a wallet amount before clicking Set Fees.", "warning");
            } else {
                alert("Please enter a wallet amount before clicking Set Fees.");
            }
            return;
        }

        if (TotalAmount > currentWalletBalance) {
            if (typeof swal !== 'undefined') {
                swal("Insufficient Balance", "Entered amount (₹" + TotalAmount.toFixed(2) + ") exceeds available wallet balance (₹" + currentWalletBalance.toFixed(2) + ").", "warning");
            } else {
                alert("Entered amount exceeds available wallet balance.");
            }
            return;
        }

        // Reset all cards
        $('.rec-item-card').each(function() {
            var remaining_balance = parseFloat($(this).data('remaining-balance')) || 0;
            $(this).find('.card-setoff-val').text('₹0.00').css({ 'color': '#64748b', 'font-weight': '600' });
            $(this).find('.card-bal-val').text('₹' + remaining_balance.toFixed(2)).css({ 'color': (remaining_balance > 0 ? '#ef4444' : '#10b981'), 'font-weight': '800' });
            $(this).removeClass('selected-setoff-card');
        });

        $('.rec-item-card').each(function() {
            var remaining_balance = parseFloat($(this).data('remaining-balance')) || 0;
            if (remaining_balance <= 0) return;

            if (TotalAmount >= remaining_balance) {
                $(this).find('.card-bal-val').text('₹0.00').css({ 'color': '#10b981', 'font-weight': '800' });
                $(this).find('.card-setoff-val').text('₹' + remaining_balance.toFixed(2)).css({ 'color': '#10b981', 'font-weight': '800' });
                $(this).addClass('selected-setoff-card');
                TotalAmount = TotalAmount - remaining_balance;
            } else {
                balAmt = remaining_balance - TotalAmount;
                $(this).find('.card-bal-val').text('₹' + balAmt.toFixed(2)).css({ 'color': '#ef4444', 'font-weight': '800' });
                $(this).find('.card-setoff-val').text('₹' + TotalAmount.toFixed(2)).css({ 'color': '#10b981', 'font-weight': '800' });
                $(this).addClass('selected-setoff-card');
                return false;
            }
        });
        $('#setoff_buttuon_wallet').show();
        $('#setoff_buttuon').hide();
    }

    function setOffform() {
        $('#setOffModel').modal('show');
    }

    function closesetOffform() {
        $('#setoff_form')[0].reset();
        $('#setOffModel').modal('hide');
    }

    function setOffformforWallet() {
        $('#setOffwalletModel').modal('show');
    }

    function closesetOffformforWallet() {
        $('#setoff_form_wallet')[0].reset();
        $('#setOffwalletModel').modal('hide');
    }

    function setOffReceiveble() {
        var receivedArray = [];

        $('.rec-item-card').each(function() {
            var $card = $(this);
            var receiveble_id = $card.data('receiveble-id');
            var flag = $card.data('flag');

            var receivedText = $card.find('.card-setoff-val').text().replace(/[₹,\s]/g, '');
            var balanceText  = $card.find('.card-bal-val').text().replace(/[₹,\s]/g, '');

            var received = parseFloat(receivedText) || 0;
            var balance  = parseFloat(balanceText) || 0;

            if (received > 0) {
                receivedArray.push({
                    receiveble_id: receiveble_id,
                    flag: flag,
                    received: received,
                    balance: balance
                });
            }
        });

        if (receivedArray.length === 0) {
            if (typeof swal !== 'undefined') swal("Notice", "Please set fees before saving.", "info");
            else alert("Please set fees before saving.");
            return;
        }

        var date = $('#setoff_date').val();
        if (!date) {
            if (typeof swal !== 'undefined') swal("Date Required", "Please select a transaction date.", "warning");
            else alert("Please select a transaction date.");
            return;
        }

        swal({
            title: "Are you sure?",
            text: "Do you want to save this data!",
            type: "warning",
            showCancelButton: true,
            confirmButtonColor: "#DD6B55",
            confirmButtonText: "Yes, Save!",
            cancelButtonText: "Cancel",
            closeOnConfirm: false,
            closeOnCancel: true
        },
        function (isConfirm) {
            if (isConfirm) {
                var formData = new FormData();
                formData.append('action', 'setoff_receiveble');
                formData.append('member_id', ACTIVE_MEMBER_ID);
                formData.append('received_array', JSON.stringify(receivedArray));
                formData.append('date', $('#setoff_date').val());
                formData.append('transaction_type', $('#selected_transaction_type').val());

                var fileInput = document.getElementById('bill_photo');
                if (fileInput && fileInput.files.length > 0) {
                    formData.append('bill_photo', fileInput.files[0]);
                }

                $.ajax({
                    type: "POST",
                    url: "../api/fees_receiveble.php",
                    data: formData,
                    processData: false,
                    contentType: false,
                    success: function(response) {
                        closesetOffform();
                        $('#txt_AmountPaid').val('');
                        $('#setoff_buttuon').hide();
                        swal("Saved!", "Payment processed successfully", "success");
                        loadMemberBalances();
                        loadReceivablesList();
                    },
                    error: function (xhr, status) {
                        var msg = xhr.responseText || 'Error processing payment';
                        try {
                            var msgObj = JSON.parse(xhr.responseText);
                            if (msgObj && typeof msgObj === 'object') {
                                msg = msgObj.Message || msgObj.message || msg;
                            }
                        } catch(e) {}
                        swal("Error", msg, "error");
                        $('#setoff_form')[0].reset();
                    }
                });
            }
        });
    }

    function setOffReceivebleFromWallet() {
        var receivedArray = [];

        $('.rec-item-card').each(function() {
            var $card = $(this);
            var receiveble_id = $card.data('receiveble-id');
            var flag = $card.data('flag');

            var receivedText = $card.find('.card-setoff-val').text().replace(/[₹,\s]/g, '');
            var balanceText  = $card.find('.card-bal-val').text().replace(/[₹,\s]/g, '');

            var received = parseFloat(receivedText) || 0;
            var balance  = parseFloat(balanceText) || 0;

            if (received > 0) {
                receivedArray.push({
                    receiveble_id: receiveble_id,
                    flag: flag,
                    received: received,
                    balance: balance
                });
            }
        });

        if (receivedArray.length === 0) {
            if (typeof swal !== 'undefined') swal("Notice", "Please set fees before saving.", "info");
            else alert("Please set fees before saving.");
            return;
        }

        var date = $('#setoff_date_wallet').val();
        if (!date) {
            if (typeof swal !== 'undefined') swal("Date Required", "Please select a deduction date.", "warning");
            else alert("Please select a deduction date.");
            return;
        }

        swal({
            title: "Are you sure?",
            text: "Do you want to save this data!",
            type: "warning",
            showCancelButton: true,
            confirmButtonColor: "#DD6B55",
            confirmButtonText: "Yes, Save!",
            cancelButtonText: "Cancel",
            closeOnConfirm: false,
            closeOnCancel: true
        },
        function (isConfirm) {
            if (isConfirm) {
                var payload = {
                    action: 'setoff_receiveble_from_wallet',
                    member_id: ACTIVE_MEMBER_ID,
                    received_array: JSON.stringify(receivedArray),
                    date: $('#setoff_date_wallet').val(),
                    transaction_type: $('#selected_transaction_type_wallet').val()
                };

                $.ajax({
                    type: "POST",
                    url: "../api/fees_receiveble.php",
                    data: payload,
                    success: function(response) {
                        closesetOffformforWallet();
                        $('#txt_wallet_amount_input').val('');
                        $('#setoff_buttuon_wallet').hide();
                        swal("Saved!", "Wallet deduction processed successfully", "success");
                        loadMemberBalances();
                        loadReceivablesList();
                    },
                    error: function (xhr, status) {
                        var msg = xhr.responseText || 'Error processing wallet deduction';
                        try {
                            var msgObj = JSON.parse(xhr.responseText);
                            if (msgObj && typeof msgObj === 'object') {
                                msg = msgObj.Message || msgObj.message || msg;
                            }
                        } catch(e) {}
                        swal("Error", msg, "error");
                        $('#setoff_form_wallet')[0].reset();
                    }
                });
            }
        });
    }

    function editReceiveble(index) {
        if (!raw_receivables_data || !raw_receivables_data[index]) return;
        var item = raw_receivables_data[index];
        $("#hdn_id").val(item.recieveble_id);
        $("#rec_date").val(item.date);
        $("#rec_amount").val(item.receiveble_fees);
        $("#rec_discription").val(item.discription || '');
        $('#receivebleModel').modal('show');
    }

    function closeaddRecieveble() {
        $('#receiveble_form')[0].reset();
        $('#receivebleModel').modal('hide');
    }

    function saveReceiveble() {
        const amount = $('#rec_amount').val();
        const date = $('#rec_date').val();
        const discription = $("#rec_discription").val();
        let length = discription.length;

        if (amount === "" || parseFloat(amount) <= 0) {
            swal("Notice", "Please enter a valid receivable amount.", "info");
            return;
        }
        if (date === "") {
            swal("Notice", "Date cannot be empty.", "info");
            return;
        }
        if (length >= 150) {
            swal("Notice", "Description must be less than 150 characters.", "info");
            return;
        }

        swal({
            title: "Are you sure?",
            text: "Do you want to update this receivable record?",
            type: "warning",
            showCancelButton: true,
            confirmButtonColor: "#DD6B55",
            confirmButtonText: "Yes, Update!",
            cancelButtonText: "Cancel",
            closeOnConfirm: false,
            closeOnCancel: true
        },
        function(isConfirm) {
            if (isConfirm) {
                var data = {
                    action: 'save_receiveble_amount',
                    id: $("#hdn_id").val(),
                    amount: amount,
                    date: date,
                    discription: discription
                };
                $.ajax({
                    type: "POST",
                    url: "../api/fees_receiveble.php",
                    data: data,
                    success: function(response) {
                        closeaddRecieveble();
                        swal("Success", "Receivable record updated successfully!", "success");
                        loadMemberBalances();
                        loadReceivablesList();
                    },
                    error: function(xhr, status, error) {
                        closeaddRecieveble();
                        var msg = "Oops! Something went wrong.";
                        try {
                            var msgObj = JSON.parse(xhr.responseText);
                            if (msgObj && msgObj.Message) msg = msgObj.Message;
                        } catch(e) {}
                        swal("Error", msg, "error");
                    }
                });
            }
        });
    }

    function cancelFeeDetails(receiveble_id, cancel) {
        var status;
        let text = '';
        if (parseInt(cancel) === 1) {
            status = 0;
            text = 'Do you want to re-open the payment?';
        } else {
            status = 1;
            text = 'Do you want to cancel/complete the payment?';
        }
        swal({
            title: "Are you sure?",
            text: text,
            type: "warning",
            showCancelButton: true,
            confirmButtonColor: "#DD6B55",
            confirmButtonText: "Yes!",
            cancelButtonText: "Cancel",
            closeOnConfirm: false,
            closeOnCancel: true
        },
        function (isConfirm) {
            if (isConfirm) {
                var data = {
                    action: 'cancel_payment',
                    id: receiveble_id,
                    status: status
                };
                $.ajax({
                    type: "POST",
                    url: "../api/fees_receiveble.php",
                    data: data,
                    success: function(response) {
                        swal("Updated!", response || "Payment status updated.", "success");
                        loadMemberBalances();
                        loadReceivablesList();
                    },
                    error: function(xhr, status, error) {
                        var msg = "Error updating payment status";
                        try {
                            var msgObj = JSON.parse(xhr.responseText);
                            if (msgObj && msgObj.Message) msg = msgObj.Message;
                        } catch(e) {}
                        swal("Error", msg, "error");
                    }
                });
            }
        });
    }

    $(document).ready(function() {
        loadMemberBalances();
        loadReceivablesList();
    });
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
            let data = (typeof res === 'string') ? JSON.parse(res) : res;
            if (data && (data.success || data.status === 'success')) {
                $('#addGuestFeeModal').modal('hide');
                if (typeof swal !== 'undefined') {
                    swal({ title: "Posted!", text: "Guest fee receivable added successfully!", type: "success" }, function() {
                        location.reload();
                    });
                } else {
                    alert('Guest fee receivable added successfully!');
                    location.reload();
                }
            } else {
                alert((data && data.Message) ? data.Message : 'Failed to add guest fee receivable.');
            }
        }).fail(function(xhr) {
            let msg = 'Error adding guest fee receivable.';
            try {
                let errObj = JSON.parse(xhr.responseText);
                if (errObj && errObj.Message) msg = errObj.Message;
            } catch(e) {}
            alert(msg);
        });
    }
    </script>
</body>
</html>
