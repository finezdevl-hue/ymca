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

// KPI Metrics
$sql_tot = "SELECT SUM(mr.fees) as total_amount, COUNT(DISTINCT mr.id) as total_count 
            FROM tbl_member_recieved mr 
            LEFT JOIN tbl_group_member_map gmm ON mr.member_id = gmm.member_id 
            WHERE mr.cancel = 0 AND mr.fees > 0";
if (!in_array('ALL', $allowed_groups, true) && !empty($allowed_groups)) {
    $in = implode(',', array_map('intval', $allowed_groups));
    $sql_tot .= " AND gmm.group_id IN ($in)";
}
$res_tot = app_exec_query($sql_tot);
$tot_row = $res_tot ? $res_tot->fetch_assoc() : [];
$total_amount = (float)($tot_row['total_amount'] ?? 0);
$total_count = (int)($tot_row['total_count'] ?? 0);

// Current Month Collection
$current_month = date('Y-m');
$sql_m_tot = "SELECT SUM(mr.fees) as month_amount 
              FROM tbl_member_recieved mr 
              LEFT JOIN tbl_group_member_map gmm ON mr.member_id = gmm.member_id 
              WHERE mr.cancel = 0 AND mr.fees > 0 AND DATE_FORMAT(mr.date, '%Y-%m') = '$current_month'";
if (!in_array('ALL', $allowed_groups, true) && !empty($allowed_groups)) {
    $in = implode(',', array_map('intval', $allowed_groups));
    $sql_m_tot .= " AND gmm.group_id IN ($in)";
}
$res_m_tot = app_exec_query($sql_m_tot);
$month_amount = $res_m_tot ? (float)($res_m_tot->fetch_assoc()['month_amount'] ?? 0) : 0;

// Fetch fee receipts list
$sql = "
    SELECT mr.id, mr.date, mr.fees as amount, mr.discription, mr.transaction_type,
           fhm.name as head_name,
           m.id as member_id, m.first_name, m.middle_name, m.last_name, m.img,
           GROUP_CONCAT(DISTINCT g.name SEPARATOR ', ') AS group_names
    FROM tbl_member_recieved mr
    JOIN tbl_members m ON mr.member_id = m.id
    LEFT JOIN tbl_fees_head_master fhm ON mr.head = fhm.id
    LEFT JOIN tbl_group_member_map gmm ON m.id = gmm.member_id
    LEFT JOIN tbl_groups g ON gmm.group_id = g.id
    WHERE mr.cancel = 0 AND mr.fees > 0
";
if (!in_array('ALL', $allowed_groups, true) && !empty($allowed_groups)) {
    $in = implode(',', array_map('intval', $allowed_groups));
    $sql .= " AND gmm.group_id IN ($in)";
}
$sql .= " GROUP BY mr.id ORDER BY mr.date DESC, mr.id DESC LIMIT 100";
$res = app_exec_query($sql);

// Members list for selector modal
$sql_m = "SELECT id, first_name, middle_name, last_name, phone FROM tbl_members WHERE inactive = 0 ORDER BY first_name, middle_name, last_name";
$res_m = app_exec_query($sql_m);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
    <title>Fee Receipts - YMCA Mobile</title>
    
    <link href="../../css/bootstrap.min.css" rel="stylesheet">
    <link href="../../font-awesome/css/font-awesome.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <link href="mobile_layout.css" rel="stylesheet">

    <style>
        body { font-family: 'Plus Jakarta Sans', sans-serif !important; background: #f8fafc !important; }
        .acc-hero {
            background: linear-gradient(135deg, #10b981 0%, #059669 100%);
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

        .search-box { position: relative; margin-bottom: 14px; }
        .search-box input {
            width: 100%; height: 42px; border-radius: 12px; border: 1px solid #e2e8f0;
            padding: 0 14px 0 38px; font-size: 13px; font-weight: 600; color: #1e293b;
            background: #ffffff; box-shadow: 0 2px 6px rgba(0,0,0,0.02); outline: none;
        }
        .search-box i { position: absolute; left: 14px; top: 13px; color: #94a3b8; font-size: 14px; }

        .rcp-card {
            background: #ffffff; border-radius: 16px; border: 1px solid #e2e8f0; padding: 14px 16px;
            margin-bottom: 10px; box-shadow: 0 2px 6px rgba(0,0,0,0.02);
            transition: transform 0.15s ease;
        }
        .rcp-card:active { transform: scale(0.99); }
        .rcp-header { display: flex; align-items: center; justify-content: space-between; margin-bottom: 8px; }
        .rcp-date { font-size: 11px; font-weight: 700; color: #64748b; text-transform: uppercase; }
        .rcp-badge { font-size: 10px; font-weight: 800; padding: 3px 8px; border-radius: 6px; text-transform: uppercase; }
        .rcp-badge-cash { background: #dcfce7; color: #166534; }
        .rcp-badge-bank { background: #e0e7ff; color: #3730a3; }

        .rcp-body { display: flex; align-items: center; gap: 12px; }
        .rcp-avatar {
            width: 42px; height: 42px; border-radius: 50%; object-fit: cover;
            border: 2px solid #e2e8f0; background: #f1f5f9; display: flex;
            align-items: center; justify-content: center; font-weight: 800; color: #059669; font-size: 14px;
        }
        .rcp-info { flex: 1; min-width: 0; }
        .rcp-name { font-size: 14px; font-weight: 800; color: #0f172a; margin: 0; white-space: nowrap; overflow: hidden; text-overflow: ellipsis; }
        .rcp-meta { font-size: 11.5px; color: #64748b; font-weight: 600; margin-top: 2px; }
        .rcp-amt { font-size: 16px; font-weight: 800; color: #059669; text-align: right; white-space: nowrap; }

        .rcp-footer {
            margin-top: 10px; padding-top: 10px; border-top: 1px dashed #f1f5f9;
            display: flex; align-items: center; justify-content: space-between;
        }
        .rcp-num { font-size: 11px; font-weight: 700; color: #94a3b8; }
        .rcp-btn-view {
            background: rgba(16, 185, 129, 0.1); color: #059669; border: none; border-radius: 8px;
            padding: 5px 12px; font-size: 12px; font-weight: 800; display: inline-flex; align-items: center; gap: 5px;
            cursor: pointer; transition: background 0.2s ease;
        }
        .rcp-btn-view:hover { background: rgba(16, 185, 129, 0.2); }

        /* Printable Receipt Modal Styling */
        .printable-receipt {
            background: #ffffff; border-radius: 16px; padding: 20px; font-family: 'Plus Jakarta Sans', sans-serif;
            color: #0f172a; border: 2px solid #059669; position: relative;
        }
        .receipt-watermark {
            position: absolute; right: 20px; bottom: 20px; opacity: 0.05; font-size: 120px; color: #059669;
            pointer-events: none;
        }

        .picker-search {
            width: 100%; padding: 10px 14px; border-radius: 12px; border: 1px solid #e2e8f0; font-size: 13px;
            font-weight: 600; outline: none; margin-bottom: 12px;
        }
        .mem-pick-row {
            padding: 12px 14px; border-bottom: 1px solid #f1f5f9; display: flex; align-items: center; justify-content: space-between;
            cursor: pointer; transition: background 0.15s ease;
        }
        .mem-pick-row:hover { background: #ecfdf5; }
    </style>
</head>
<body class="mob-body">

    <!-- Header -->
    <header class="mob-header">
        <div class="mob-header-brand">
            <a href="accounts.php" style="color:#ffffff; margin-right:8px; font-size:18px;"><i class="fa fa-arrow-left"></i></a>
            <div class="mob-header-title">
                Fee <span>Receipts</span>
            </div>
        </div>
        <div class="mob-header-actions">
            <a href="../../app_login_manager/logout.php" class="mob-header-btn" title="Logout"><i class="fa fa-sign-out"></i></a>
        </div>
    </header>

    <div class="mob-page">

        <div class="acc-hero" style="display:flex; justify-content:space-between; align-items:center;">
            <div>
                <h2>Member Fee Receipts</h2>
                <p>Fee collections & payment receipts log</p>
            </div>
            <div style="display:flex; gap:6px;">
                <button class="btn btn-light btn-sm" style="border-radius:12px; font-weight:800; color:#059669; padding:8px 14px; box-shadow: 0 4px 10px rgba(0,0,0,0.1);" data-toggle="modal" data-target="#recordPaymentModal">
                    <i class="fa fa-plus-circle"></i> Record Payment
                </button>
            </div>
        </div>

        <!-- KPI Summary Strip -->
        <div class="kpi-grid">
            <div class="kpi-card-sm" style="border-left: 3px solid #10b981;">
                <div class="kpi-lbl">Total Fees</div>
                <div class="kpi-val" style="color:#059669;">₹<?php echo number_format($total_amount, 0); ?></div>
            </div>
            <div class="kpi-card-sm" style="border-left: 3px solid #3b82f6;">
                <div class="kpi-lbl">This Month</div>
                <div class="kpi-val" style="color:#2563eb;">₹<?php echo number_format($month_amount, 0); ?></div>
            </div>
            <div class="kpi-card-sm" style="border-left: 3px solid #8b5cf6;">
                <div class="kpi-lbl">Receipts</div>
                <div class="kpi-val" style="color:#7c3aed;"><?php echo $total_count; ?></div>
            </div>
        </div>

        <!-- Search Bar -->
        <div class="search-box">
            <i class="fa fa-search"></i>
            <input type="text" id="search_receipts" placeholder="Search by member name, category, or date..." onkeyup="filterReceipts()">
        </div>

        <!-- Receipts List -->
        <div id="receipts_container">
            <?php if ($res && $res->num_rows > 0): ?>
                <?php while ($r = $res->fetch_assoc()): ?>
                    <?php 
                    $name = trim($r['first_name'] . ' ' . $r['middle_name'] . ' ' . $r['last_name']);
                    $head = htmlspecialchars($r['head_name'] ?: 'Fee Collection');
                    $date_formatted = ($r['date'] && $r['date'] !== '0000-00-00' && strtotime($r['date']) !== false) ? date('d M Y', strtotime($r['date'])) : '--';
                    $amount_formatted = number_format((float)$r['amount'], 2);
                    $group = htmlspecialchars($r['group_names'] ?: 'Member');
                    $mode = ((int)$r['transaction_type'] === 2) ? 'Bank' : 'Cash';
                    $badge_class = ((int)$r['transaction_type'] === 2) ? 'rcp-badge-bank' : 'rcp-badge-cash';
                    $img_src = !empty($r['img']) ? '../../image_upload/member/' . $r['img'] : '';
                    $initial = strtoupper(substr($r['first_name'], 0, 1));
                    $desc = htmlspecialchars($r['discription'] ?: 'Fee Received');
                    ?>
                    <div class="rcp-card rcp-item-row" data-search="<?php echo strtolower($name . ' ' . $head . ' ' . $date_formatted . ' ' . $group); ?>">
                        <div class="rcp-header">
                            <div class="rcp-date"><i class="fa fa-calendar"></i> <?php echo $date_formatted; ?></div>
                            <span class="rcp-badge <?php echo $badge_class; ?>"><?php echo $mode; ?></span>
                        </div>
                        <div class="rcp-body" onclick="location.href='member_receivable.php?member_id=<?php echo $r['member_id']; ?>'" style="cursor:pointer;">
                            <?php if (!empty($img_src) && file_exists('../../image_upload/member/' . $r['img'])): ?>
                                <img src="<?php echo $img_src; ?>" class="rcp-avatar" alt="<?php echo htmlspecialchars($name); ?>">
                            <?php else: ?>
                                <div class="rcp-avatar"><?php echo $initial; ?></div>
                            <?php endif; ?>
                            <div class="rcp-info">
                                <h4 class="rcp-name"><?php echo htmlspecialchars($name); ?></h4>
                                <div class="rcp-meta"><i class="fa fa-tag" style="color:#10b981;"></i> <?php echo $head; ?> &bull; <?php echo $group; ?></div>
                            </div>
                            <div class="rcp-amt">
                                +₹<?php echo $amount_formatted; ?>
                            </div>
                        </div>
                        <div class="rcp-footer">
                            <span class="rcp-num">Receipt #REC-<?php echo str_pad($r['id'], 4, '0', STR_PAD_LEFT); ?></span>
                            <div style="display:flex; gap:6px;">
                                <a href="member_receivable.php?member_id=<?php echo $r['member_id']; ?>" class="rcp-btn-view" style="background:rgba(59,130,246,0.1); color:#2563eb; text-decoration:none;">
                                    <i class="fa fa-user"></i> Dues
                                </a>
                                <button class="rcp-btn-view" onclick="openReceiptModal('<?php echo $r['id']; ?>', '<?php echo addslashes($name); ?>', '<?php echo addslashes($group); ?>', '<?php echo $date_formatted; ?>', '<?php echo addslashes($head); ?>', '<?php echo $amount_formatted; ?>', '<?php echo (float)$r['amount']; ?>', '<?php echo $mode; ?>', '<?php echo addslashes($desc); ?>')">
                                    <i class="fa fa-print"></i> View
                                </button>
                            </div>
                        </div>
                    </div>
                <?php endwhile; ?>
            <?php else: ?>
                <div style="text-align:center; padding:40px 20px; color:#94a3b8;">
                    <i class="fa fa-file-text-o" style="font-size:36px; margin-bottom:10px; display:block;"></i>
                    No fee receipts recorded yet.
                </div>
            <?php endif; ?>
        </div>

    </div>

    <!-- Printable Fee Receipt Modal -->
    <div class="modal fade" id="viewReceiptModal" tabindex="-1" role="dialog" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered" role="document">
            <div class="modal-content" style="border-radius:24px; border:none; background:#f8fafc;">
                <div class="modal-header" style="border-bottom:1px solid #e2e8f0; padding:16px 20px;">
                    <h5 class="modal-title" style="font-weight:800; color:#0f172a; font-size:16px;">
                        <i class="fa fa-file-text" style="color:#10b981;"></i> Fee Receipt Preview
                    </h5>
                    <button type="button" class="close" data-dismiss="modal">&times;</button>
                </div>
                <div class="modal-body" style="padding:20px;">
                    
                    <div class="printable-receipt" id="printable_receipt_area">
                        <i class="fa fa-certificate receipt-watermark"></i>
                        <div style="text-align:center; border-bottom:2px solid #10b981; padding-bottom:12px; margin-bottom:14px;">
                            <h3 style="margin:0; font-weight:800; font-size:17px; color:#059669;">YMCA BADMINTON CLUB</h3>
                            <p style="margin:2px 0 0 0; font-size:11px; color:#64748b; font-weight:600;">POOVATHUSSERY — OFFICIAL FEE RECEIPT</p>
                        </div>

                        <div style="display:flex; justify-content:space-between; margin-bottom:12px; font-size:12px;">
                            <div><strong>Receipt No:</strong> <span id="m_rec_no" style="color:#059669; font-weight:800;">#REC-0000</span></div>
                            <div><strong>Date:</strong> <span id="m_rec_date">--</span></div>
                        </div>

                        <div style="background:#f1f5f9; border-radius:12px; padding:12px; margin-bottom:12px; font-size:12.5px;">
                            <div style="margin-bottom:4px;"><strong>Received From:</strong> <span id="m_rec_name" style="font-weight:700; color:#0f172a;">--</span></div>
                            <div style="margin-bottom:4px;"><strong>Group:</strong> <span id="m_rec_group">--</span></div>
                            <div style="margin-bottom:4px;"><strong>Payment Category:</strong> <span id="m_rec_head" style="color:#059669; font-weight:700;">--</span></div>
                            <div><strong>Payment Mode:</strong> <span id="m_rec_mode" class="rcp-badge rcp-badge-cash">--</span></div>
                        </div>

                        <div style="font-size:12px; color:#475569; margin-bottom:14px;">
                            <strong>Particulars:</strong> <span id="m_rec_desc">--</span>
                        </div>

                        <div style="background:#ecfdf5; border:1.5px solid #10b981; border-radius:12px; padding:12px 16px; display:flex; justify-content:space-between; align-items:center;">
                            <div>
                                <div style="font-size:10px; font-weight:800; color:#047857; text-transform:uppercase;">Amount Received</div>
                                <div id="m_rec_words" style="font-size:10.5px; font-weight:700; color:#065f46; margin-top:2px;">--</div>
                            </div>
                            <div id="m_rec_amt" style="font-size:20px; font-weight:800; color:#047857;">₹0.00</div>
                        </div>

                        <div style="margin-top:20px; display:flex; justify-content:space-between; align-items:flex-end; font-size:11px; color:#64748b;">
                            <div>Status: <span style="color:#10b981; font-weight:800;"><i class="fa fa-check-circle"></i> VERIFIED PAID</span></div>
                            <div style="text-align:right;">
                                <div style="font-weight:800; color:#0f172a;">Authorized Signature</div>
                                YMCA Management
                            </div>
                        </div>
                    </div>

                </div>
                <div class="modal-footer" style="border-top:1px solid #e2e8f0; padding:12px 20px;">
                    <button type="button" class="btn btn-light" data-dismiss="modal" style="border-radius:10px; font-weight:700;">Close</button>
                    <button type="button" class="btn btn-success" onclick="printReceiptArea()" style="border-radius:10px; font-weight:800;">
                        <i class="fa fa-print"></i> Print Receipt
                    </button>
                </div>
            </div>
        </div>
    </div>

    <!-- Modal for Selecting Member to Record Payment -->
    <div class="modal fade" id="recordPaymentModal" tabindex="-1" role="dialog" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered" role="document">
            <div class="modal-content" style="border-radius:24px; border:none; box-shadow:0 25px 50px -12px rgba(0,0,0,0.25);">
                <div class="modal-header" style="border-bottom:1px solid #f1f5f9; padding:16px 20px;">
                    <h5 class="modal-title" style="font-weight:800; color:#0f172a;"><i class="fa fa-user-plus" style="color:#10b981;"></i> Select Member to Record Payment</h5>
                    <button type="button" class="close" data-dismiss="modal">&times;</button>
                </div>
                <div class="modal-body" style="padding:16px;">
                    <input type="text" class="picker-search" id="search_rec_member" placeholder="Search member by name or phone..." onkeyup="filterRecordMemberPicker()">
                    <div style="max-height:340px; overflow-y:auto;" id="record_member_picker_list">
                        <?php if ($res_m && $res_m->num_rows > 0): ?>
                            <?php while ($rm = $res_m->fetch_assoc()): ?>
                                <?php $rmName = trim($rm['first_name'] . ' ' . $rm['middle_name'] . ' ' . $rm['last_name']); ?>
                                <div class="mem-pick-row record-mem-row" onclick="location.href='member_receivable.php?member_id=<?php echo $rm['id']; ?>'">
                                    <div>
                                        <div style="font-size:14px; font-weight:800; color:#0f172a;"><?php echo htmlspecialchars($rmName); ?></div>
                                        <div style="font-size:11px; color:#64748b; font-weight:600;"><?php echo htmlspecialchars($rm['phone'] ?: 'No Phone'); ?></div>
                                    </div>
                                    <span style="font-size:12px; font-weight:800; color:#059669; background:#ecfdf5; padding:4px 10px; border-radius:8px;">
                                        Select <i class="fa fa-chevron-right"></i>
                                    </span>
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
    <script>
    function filterReceipts() {
        let q = $('#search_receipts').val().toLowerCase();
        $('.rcp-item-row').each(function() {
            let s = $(this).data('search') || '';
            if (s.indexOf(q) !== -1) {
                $(this).show();
            } else {
                $(this).hide();
            }
        });
    }

    function filterRecordMemberPicker() {
        let q = $('#search_rec_member').val().toLowerCase();
        $('.record-mem-row').each(function() {
            let t = $(this).text().toLowerCase();
            if (t.indexOf(q) !== -1) $(this).show(); else $(this).hide();
        });
    }

    function numberToWords(num) {
        var a = ['','One ','Two ','Three ','Four ', 'Five ','Six ','Seven ','Eight ','Nine ','Ten ','Eleven ','Twelve ','Thirteen ','Fourteen ','Fifteen ','Sixteen ','Seventeen ','Eighteen ','Nineteen '];
        var b = ['', '', 'Twenty','Thirty','Forty','Fifty', 'Sixty','Seventy','Eighty','Ninety'];

        num = Math.floor(num);
        if (num === 0) return 'ZERO RUPEES ONLY';
        if ((num = num.toString()).length > 9) return 'OVERFLOW';
        var n = ('000000000' + num).substr(-9).match(/^(\d{2})(\d{2})(\d{2})(\d{1})(\d{2})$/);
        if (!n) return ''; 
        var str = '';
        str += (n[1] != 0) ? (a[Number(n[1])] || b[n[1][0]] + ' ' + a[n[1][1]]) + 'Crore ' : '';
        str += (n[2] != 0) ? (a[Number(n[2])] || b[n[2][0]] + ' ' + a[n[2][1]]) + 'Lakh ' : '';
        str += (n[3] != 0) ? (a[Number(n[3])] || b[n[3][0]] + ' ' + a[n[3][1]]) + 'Thousand ' : '';
        str += (n[4] != 0) ? (a[Number(n[4])] || b[n[4][0]] + ' ' + a[n[4][1]]) + 'Hundred ' : '';
        str += (n[5] != 0) ? ((str != '') ? 'and ' : '') + (a[Number(n[5])] || b[n[5][0]] + ' ' + a[n[5][1]]) : '';
        return (str.trim() + ' Rupees Only').toUpperCase();
    }

    function openReceiptModal(id, name, group, date, head, amountStr, rawAmt, mode, desc) {
        $('#m_rec_no').text('#REC-' + String(id).padStart(4, '0'));
        $('#m_rec_date').text(date);
        $('#m_rec_name').text(name);
        $('#m_rec_group').text(group);
        $('#m_rec_head').text(head);
        $('#m_rec_mode').text(mode).attr('class', mode === 'Bank' ? 'rcp-badge rcp-badge-bank' : 'rcp-badge rcp-badge-cash');
        $('#m_rec_desc').text(desc || 'Fee Received');
        $('#m_rec_amt').text('₹' + amountStr);
        $('#m_rec_words').text(numberToWords(rawAmt));
        $('#viewReceiptModal').modal('show');
    }

    function printReceiptArea() {
        var printContents = document.getElementById('printable_receipt_area').innerHTML;
        var originalContents = document.body.innerHTML;
        document.body.innerHTML = '<div style="padding:30px; font-family:sans-serif;">' + printContents + '</div>';
        window.print();
        document.body.innerHTML = originalContents;
        location.reload();
    }
    </script>
</body>
</html>
