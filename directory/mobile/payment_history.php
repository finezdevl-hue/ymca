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

$active_tab = 'reports';

// Fetch recent payment history
$sql = "
    SELECT p.id, p.date, p.amount, p.particuler, p.transaction_type, p.bill_photo,
           phm.name as head_name, COALESCE(g.name, 'General') as group_name
    FROM tbl_paid p
    LEFT JOIN tbl_payment_head_master phm ON p.head = phm.id
    LEFT JOIN tbl_groups g ON p.group_id = g.id
    WHERE (p.cancel IS NULL OR p.cancel = 0)
";
if (!in_array('ALL', $allowed_groups, true) && !empty($allowed_groups)) {
    $in = implode(',', array_map('intval', $allowed_groups));
    $sql .= " AND p.group_id IN ($in)";
}
$sql .= " ORDER BY p.date DESC LIMIT 50";
$res = app_exec_query($sql);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
    <title>Payment History - YMCA Mobile</title>
    
    <link href="../../css/bootstrap.min.css" rel="stylesheet">
    <link href="../../font-awesome/css/font-awesome.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <link href="mobile_layout.css" rel="stylesheet">

    <style>
        body { font-family: 'Plus Jakarta Sans', sans-serif !important; background: #f8fafc !important; }
        .rep-hero {
            background: linear-gradient(135deg, #0ea5e9 0%, #0284c7 100%);
            border-radius: 20px; padding: 18px 20px; color: #ffffff; margin-bottom: 16px;
            box-shadow: 0 8px 20px rgba(14, 165, 233, 0.2);
        }
        .rep-hero h2 { margin: 0 0 4px 0; font-weight: 800; font-size: 20px; }
        .rep-hero p { margin: 0; font-size: 12.5px; opacity: 0.9; }

        .hist-item-card {
            background: #ffffff; border-radius: 16px; border: 1px solid #e2e8f0; padding: 14px 16px;
            margin-bottom: 10px; display: flex; flex-direction: column; justify-content: space-between;
            box-shadow: 0 2px 6px rgba(0,0,0,0.02); gap: 8px;
        }
        .hist-date { font-size: 11px; font-weight: 700; color: #64748b; text-transform: uppercase; }
        .hist-head { font-size: 14px; font-weight: 800; color: #0f172a; margin: 2px 0 0 0; }
        .hist-narr { font-size: 11.5px; color: #64748b; margin-top: 2px; }
        .hist-amt { font-size: 15px; font-weight: 800; color: #dc2626; text-align: right; }
    </style>
</head>
<body class="mob-body">

    <!-- Header -->
    <header class="mob-header">
        <div class="mob-header-brand">
            <a href="reports.php" style="color:#ffffff; margin-right:8px; font-size:18px;"><i class="fa fa-arrow-left"></i></a>
            <div class="mob-header-title">
                Payment <span>History</span>
            </div>
        </div>
        <div class="mob-header-actions">
            <a href="../../app_login_manager/logout.php" class="mob-header-btn" title="Logout"><i class="fa fa-sign-out"></i></a>
        </div>
    </header>

    <div class="mob-page">

        <div class="rep-hero">
            <h2>Group Payment History</h2>
            <p>Expense vouchers, payments, and vendor payouts log</p>
        </div>

        <div>
            <?php if ($res && $res->num_rows > 0): ?>
                <?php while ($r = $res->fetch_assoc()): ?>
                    <div class="hist-item-card">
                        <div style="display:flex; justify-content:space-between; align-items:flex-start;">
                            <div>
                                <div class="hist-date"><i class="fa fa-calendar"></i> <?php echo date('d M Y', strtotime($r['date'])); ?> &bull; <span style="color:#0ea5e9; font-weight:800;">VCH-<?php echo str_pad($r['id'], 4, '0', STR_PAD_LEFT); ?></span></div>
                                <h4 class="hist-head"><?php echo htmlspecialchars($r['head_name'] ?: 'Expense'); ?></h4>
                                <?php if (!empty($r['particuler'])): ?>
                                    <div class="hist-narr"><?php echo htmlspecialchars($r['particuler']); ?></div>
                                <?php endif; ?>
                            </div>
                            <div class="hist-amt">
                                -₹<?php echo number_format((float)$r['amount'], 2); ?>
                            </div>
                        </div>
                        <div style="display:flex; justify-content:space-between; align-items:center; border-top:1px solid #f1f5f9; padding-top:8px; margin-top:2px;">
                            <span class="badge" style="background:#f1f5f9; color:#475569; font-size:10px; font-weight:700; border-radius:6px;"><i class="fa fa-users"></i> <?php echo htmlspecialchars($r['group_name']); ?></span>
                            <button type="button" class="btn btn-xs btn-primary" onclick="openBillModal('<?php echo $r['id']; ?>', '<?php echo date('d M Y', strtotime($r['date'])); ?>', '<?php echo addslashes(htmlspecialchars($r['head_name'] ?: 'Expense')); ?>', '<?php echo addslashes(htmlspecialchars($r['group_name'])); ?>', '<?php echo number_format((float)$r['amount'], 2); ?>', '<?php echo addslashes(htmlspecialchars($r['particuler'] ?: 'Payment Expense')); ?>', '<?php echo ((int)$r['transaction_type'] === 2) ? 'Bank' : 'Cash'; ?>', '<?php echo addslashes($r['bill_photo'] ?: ''); ?>')" style="border-radius:8px; font-weight:800; font-size:11px; padding:4px 12px; background: linear-gradient(135deg, #0ea5e9, #0284c7); border:none;">
                                <i class="fa fa-file-text-o"></i> View Bill
                            </button>
                        </div>
                    </div>
                <?php endwhile; ?>
            <?php else: ?>
                <div style="text-align:center; padding:40px 20px; color:#94a3b8;">
                    <i class="fa fa-history" style="font-size:36px; margin-bottom:10px; display:block;"></i>
                    No payment history records found.
                </div>
            <?php endif; ?>
        </div>

    </div>

    <!-- View Bill Modal -->
    <div class="modal fade" id="viewBillModal" tabindex="-1" role="dialog">
        <div class="modal-dialog modal-dialog-centered" role="document">
            <div class="modal-content" style="border-radius:24px; border:none; box-shadow: 0 25px 50px -12px rgba(0, 0, 0, 0.25); overflow:hidden;">
                <div class="modal-header" style="background: linear-gradient(135deg, #0ea5e9 0%, #0284c7 100%); padding: 18px 24px; color: white;">
                    <button type="button" class="close" data-dismiss="modal" style="color:white; opacity:0.9; font-size:24px;">&times;</button>
                    <h4 class="modal-title" style="font-weight:800; font-size:16px; margin:0; display:flex; align-items:center; gap:8px;">
                        <i class="fa fa-file-text-o"></i> Payment Voucher <span id="vch_num_badge" style="background:rgba(255,255,255,0.2); padding:2px 8px; border-radius:6px; font-size:12px;"></span>
                    </h4>
                </div>
                <div class="modal-body" style="padding: 20px;">
                    <div style="background:#f8fafc; border:1px solid #e2e8f0; border-radius:14px; padding:14px; margin-bottom:16px;">
                        <div style="display:flex; justify-content:space-between; margin-bottom:8px;">
                            <span style="font-size:11px; font-weight:700; color:#64748b; text-transform:uppercase;">Date</span>
                            <span id="vch_date" style="font-size:12px; font-weight:800; color:#0f172a;"></span>
                        </div>
                        <div style="display:flex; justify-content:space-between; margin-bottom:8px;">
                            <span style="font-size:11px; font-weight:700; color:#64748b; text-transform:uppercase;">Payment Head</span>
                            <span id="vch_head" style="font-size:12.5px; font-weight:800; color:#0ea5e9;"></span>
                        </div>
                        <div style="display:flex; justify-content:space-between; margin-bottom:8px;">
                            <span style="font-size:11px; font-weight:700; color:#64748b; text-transform:uppercase;">Group</span>
                            <span id="vch_group" style="font-size:12px; font-weight:700; color:#475569;"></span>
                        </div>
                        <div style="display:flex; justify-content:space-between; margin-bottom:8px;">
                            <span style="font-size:11px; font-weight:700; color:#64748b; text-transform:uppercase;">Payment Mode</span>
                            <span id="vch_mode" style="font-size:12px; font-weight:700; color:#10b981;"></span>
                        </div>
                        <div style="display:flex; justify-content:space-between; align-items:center; border-top:1px solid #e2e8f0; padding-top:8px; margin-top:8px;">
                            <span style="font-size:12px; font-weight:800; color:#0f172a; text-transform:uppercase;">Amount Paid</span>
                            <span id="vch_amount" style="font-size:18px; font-weight:800; color:#dc2626;"></span>
                        </div>
                    </div>

                    <div style="margin-bottom:14px;">
                        <label style="font-size:11px; font-weight:800; color:#64748b; text-transform:uppercase; margin-bottom:4px; display:block;">Particulars / Description</label>
                        <div id="vch_desc" style="font-size:13px; font-weight:600; color:#1e293b; background:#fff; border:1px solid #e2e8f0; border-radius:10px; padding:10px 12px;"></div>
                    </div>

                    <div>
                        <label style="font-size:11px; font-weight:800; color:#64748b; text-transform:uppercase; margin-bottom:6px; display:block;">Attached Bill Document</label>
                        <div id="vch_photo_container" style="text-align:center; background:#f8fafc; border:1.5px dashed #cbd5e1; border-radius:14px; padding:16px;"></div>
                    </div>
                </div>
                <div class="modal-footer" style="padding:12px 20px; background:#fafafa; border-top:1px solid #f1f5f9; display:flex; justify-content:flex-end;">
                    <button type="button" class="btn btn-default" data-dismiss="modal" style="border-radius:10px; font-weight:700; padding:6px 20px;">Close</button>
                </div>
            </div>
        </div>
    </div>

    <!-- Mobile Bottom Navigation (5 Tabs) -->
    <?php include 'mobile_bottom_nav.php'; ?>

    <script src="../../js/jquery-3.1.1.min.js"></script>
    <script src="../../js/bootstrap.min.js"></script>
    <script>
        function openBillModal(id, date, head, group, amount, desc, mode, photo) {
            $('#vch_num_badge').text('VCH-' + String(id).padStart(4, '0'));
            $('#vch_date').text(date);
            $('#vch_head').text(head);
            $('#vch_group').text(group);
            $('#vch_mode').text(mode);
            $('#vch_amount').text('₹ ' + amount);
            $('#vch_desc').text(desc || 'Payment Expense');

            let cleanPhoto = (photo && typeof photo === 'string') ? photo.trim() : '';
            if (cleanPhoto !== '' && cleanPhoto !== '0' && cleanPhoto !== 'null' && cleanPhoto !== 'undefined') {
                let photoPath = '../../image_upload/payments/' + cleanPhoto;
                let ext = cleanPhoto.split('.').pop().toLowerCase();
                if (['jpg', 'jpeg', 'png', 'gif', 'webp'].includes(ext)) {
                    $('#vch_photo_container').html(`
                        <a href="javascript:void(0)" onclick="viewBillFile('${photoPath}', '${cleanPhoto}')">
                            <img src="${photoPath}" style="max-width:100%; max-height:220px; border-radius:10px; box-shadow:0 4px 12px rgba(0,0,0,0.1);" onerror="this.onerror=null; this.parentNode.innerHTML='<div style=\\'color:#ef4444; font-size:12px; font-weight:700; padding:10px; background:#fef2f2; border-radius:10px; border:1px solid #fca5a5;\\'><i class=\\'fa fa-exclamation-triangle\\'></i> Bill image (${cleanPhoto}) missing on server</div>';">
                        </a>
                        <div style="font-size:11px; color:#64748b; margin-top:6px;"><i class="fa fa-search-plus"></i> Tap image to open full size</div>
                    `);
                } else {
                    $('#vch_photo_container').html(`
                        <button type="button" onclick="viewBillFile('${photoPath}', '${cleanPhoto}')" class="btn btn-xs btn-info" style="border-radius:8px; font-weight:700; padding:8px 16px; background:#0284c7; border:none; color:#fff;">
                            <i class="fa fa-file-pdf-o"></i> View Attached Bill Document (${ext.toUpperCase()})
                        </button>
                    `);
                }
            } else {
                $('#vch_photo_container').html(`
                    <div style="color:#94a3b8; font-size:12.5px; font-weight:600;">
                        <i class="fa fa-file-o" style="font-size:24px; margin-bottom:6px; display:block; color:#cbd5e1;"></i>
                        No digital bill photo attached to this voucher
                    </div>
                `);
            }

            $('#viewBillModal').modal('show');
        }

        function viewBillFile(photoPath, cleanPhoto) {
            let mainPath = '../../image_upload/payments/' + cleanPhoto;
            fetch(mainPath, { method: 'HEAD' })
                .then(function(response) {
                    if (response.ok) {
                        window.open(mainPath, '_blank');
                    } else {
                        let altPath1 = '../../image_upload/bills/' + cleanPhoto;
                        fetch(altPath1, { method: 'HEAD' })
                            .then(function(altRes1) {
                                if (altRes1.ok) {
                                    window.open(altPath1, '_blank');
                                } else {
                                    let altPath2 = '../../pdf_upload/' + cleanPhoto;
                                    window.open(altPath2, '_blank');
                                }
                            }).catch(function() {
                                window.open(mainPath, '_blank');
                            });
                    }
                })
                .catch(function() {
                    window.open(mainPath, '_blank');
                });
        }
    </script>
</body>
</html>
