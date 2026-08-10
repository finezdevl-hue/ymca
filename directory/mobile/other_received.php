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

// Fetch recent other received payments
$sql = "
    SELECT r.id, r.date, r.amount, r.particuler, fhm.name AS head_name
    FROM tbl_other_recieved r
    LEFT JOIN tbl_fees_head_master fhm ON r.head = fhm.id
    WHERE (r.cancel IS NULL OR r.cancel = 0) AND r.amount > 0
    ORDER BY r.date DESC LIMIT 40
";
$res = app_exec_query($sql);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
    <title>Other Received - YMCA Mobile</title>
    
    <link href="../../css/bootstrap.min.css" rel="stylesheet">
    <link href="../../font-awesome/css/font-awesome.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <link href="mobile_layout.css" rel="stylesheet">

    <style>
        body { font-family: 'Plus Jakarta Sans', sans-serif !important; background: #f8fafc !important; }
        .acc-hero {
            background: linear-gradient(135deg, #8b5cf6 0%, #6d28d9 100%);
            border-radius: 20px; padding: 18px 20px; color: #ffffff; margin-bottom: 16px;
            box-shadow: 0 8px 20px rgba(139, 92, 246, 0.2);
        }
        .acc-hero h2 { margin: 0 0 4px 0; font-weight: 800; font-size: 20px; }
        .acc-hero p { margin: 0; font-size: 12.5px; opacity: 0.9; }

        .rcp-card {
            background: #ffffff; border-radius: 16px; border: 1px solid #e2e8f0; padding: 14px 16px;
            margin-bottom: 10px; display: flex; align-items: center; justify-content: space-between;
            box-shadow: 0 2px 6px rgba(0,0,0,0.02);
        }
        .rcp-date { font-size: 11px; font-weight: 700; color: #64748b; text-transform: uppercase; }
        .rcp-name { font-size: 14px; font-weight: 800; color: #0f172a; margin: 2px 0 0 0; }
        .rcp-head { font-size: 11.5px; color: #8b5cf6; font-weight: 600; margin-top: 2px; }
        .rcp-amt { font-size: 16px; font-weight: 800; color: #059669; text-align: right; }
    </style>
</head>
<body class="mob-body">

    <!-- Header -->
    <header class="mob-header">
        <div class="mob-header-brand">
            <a href="accounts.php" style="color:#ffffff; margin-right:8px; font-size:18px;"><i class="fa fa-arrow-left"></i></a>
            <div class="mob-header-title">
                Other <span>Received</span>
            </div>
        </div>
        <div class="mob-header-actions">
            <a href="../../app_login_manager/logout.php" class="mob-header-btn" title="Logout"><i class="fa fa-sign-out"></i></a>
        </div>
    </header>

    <div class="mob-page">

        <div class="acc-hero" style="display:flex; justify-content:space-between; align-items:center;">
            <div>
                <h2>Other Income Received</h2>
                <p>Miscellaneous income received log</p>
            </div>
            <button class="btn btn-light btn-sm" style="border-radius:12px; font-weight:800; color:#6d28d9;" data-toggle="modal" data-target="#addOtherReceivedModal">
                <i class="fa fa-plus"></i> Add
            </button>
        </div>

        <div>
            <?php if ($res && $res->num_rows > 0): ?>
                <?php while ($r = $res->fetch_assoc()): ?>
                    <div class="rcp-card">
                        <div>
                            <div class="rcp-date"><i class="fa fa-calendar"></i> <?php echo (!empty($r['date']) && $r['date'] != '0000-00-00') ? date('d M Y', strtotime($r['date'])) : 'General Entry'; ?></div>
                            <h4 class="rcp-name"><?php echo htmlspecialchars($r['head_name'] ?: 'Other Received'); ?></h4>
                            <?php if (!empty($r['particuler'])): ?>
                                <div class="rcp-head"><?php echo htmlspecialchars($r['particuler']); ?></div>
                            <?php endif; ?>
                        </div>
                        <div class="rcp-amt">
                            +₹<?php echo number_format((float)$r['amount'], 2); ?>
                        </div>
                    </div>
                <?php endwhile; ?>
            <?php else: ?>
                <div style="text-align:center; padding:40px 20px; color:#94a3b8;">
                    <i class="fa fa-check-circle" style="font-size:36px; margin-bottom:10px; display:block;"></i>
                    No other received income recorded yet.
                </div>
            <?php endif; ?>
        </div>

    </div>

    <!-- Modal for Add Other Received -->
    <div class="modal fade" id="addOtherReceivedModal" tabindex="-1" role="dialog" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered" role="document">
            <div class="modal-content" style="border-radius:20px; border:none;">
                <div class="modal-header" style="border-bottom:1px solid #f1f5f9;">
                    <h5 class="modal-title" style="font-weight:800; color:#0f172a;"><i class="fa fa-plus-circle" style="color:#8b5cf6;"></i> Add Received Income</h5>
                    <button type="button" class="close" data-dismiss="modal">&times;</button>
                </div>
                <form id="mob_other_recieved_form">
                    <div class="modal-body" style="padding:20px; display:flex; flex-direction:column; gap:12px;">
                        <div>
                            <label style="font-size:11px; font-weight:700; color:#64748b; text-transform:uppercase;">Date</label>
                            <input type="date" id="rec_date" value="<?php echo date('Y-m-d'); ?>" class="form-control" style="border-radius:10px; font-weight:600;" required>
                        </div>
                        <div>
                            <label style="font-size:11px; font-weight:700; color:#64748b; text-transform:uppercase;">Select Group</label>
                            <select id="rec_group_id" class="form-control" style="border-radius:10px; font-weight:600;"></select>
                        </div>
                        <div>
                            <label style="font-size:11px; font-weight:700; color:#64748b; text-transform:uppercase;">Financial Year</label>
                            <select id="rec_flag" class="form-control" style="border-radius:10px; font-weight:600;"></select>
                        </div>
                        <div>
                            <label style="font-size:11px; font-weight:700; color:#64748b; text-transform:uppercase;">Income Head / Category</label>
                            <select id="rec_head" class="form-control" style="border-radius:10px; font-weight:600;"></select>
                        </div>
                        <div>
                            <label style="font-size:11px; font-weight:700; color:#64748b; text-transform:uppercase;">Transaction Type</label>
                            <select id="rec_transaction_type" class="form-control" style="border-radius:10px; font-weight:600;">
                                <option value="1">Cash</option>
                                <option value="2">Bank</option>
                            </select>
                        </div>
                        <div>
                            <label style="font-size:11px; font-weight:700; color:#64748b; text-transform:uppercase;">Particulars / Description</label>
                            <input type="text" id="rec_particuler" placeholder="E.g., Guest fee collection" class="form-control" style="border-radius:10px; font-weight:600;" required>
                        </div>
                        <div>
                            <label style="font-size:11px; font-weight:700; color:#64748b; text-transform:uppercase;">Amount Received (₹)</label>
                            <input type="number" step="0.01" id="rec_amount" placeholder="0.00" class="form-control" style="border-radius:10px; font-weight:700; color:#059669;" required>
                        </div>
                    </div>
                    <div class="modal-footer" style="border-top:1px solid #f1f5f9;">
                        <button type="button" class="btn btn-light" data-dismiss="modal" style="border-radius:10px; font-weight:700;">Cancel</button>
                        <button type="submit" class="btn btn-purple" style="border-radius:10px; font-weight:800; background:#8b5cf6; color:#fff;">Save Received</button>
                    </div>
                </form>
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
                let htm = '';
                groups.forEach(function(g) { htm += '<option value="' + g.id + '">' + g.name + '</option>'; });
                $('#rec_group_id').html(htm);
            } catch(e) {}
        });

        $.post('../api/other_recieveble.php', { action: 'load_closing_years' }, function(data) {
            try {
                let parsed = typeof data === 'string' ? JSON.parse(data) : data;
                let years = Array.isArray(parsed[0]) ? parsed[0] : (Array.isArray(parsed) ? parsed : []);
                let htm = '';
                years.forEach(function(y) { htm += '<option value="' + y.id + '">' + y.from_year + ' - ' + y.to_year + '</option>'; });
                $('#rec_flag').html(htm);
            } catch(e) {}
        });

        $.post('../api/other_recieveble.php', { action: 'load_heads' }, function(data) {
            try {
                let parsed = typeof data === 'string' ? JSON.parse(data) : data;
                let heads = Array.isArray(parsed[0]) ? parsed[0] : (Array.isArray(parsed) ? parsed : []);
                let htm = '';
                heads.forEach(function(h) { htm += '<option value="' + h.id + '">' + h.name + '</option>'; });
                $('#rec_head').html(htm);
            } catch(e) {}
        });

        $('#mob_other_recieved_form').on('submit', function(e) {
            e.preventDefault();
            let payload = {
                action: 'save_payment',
                date: $('#rec_date').val(),
                group_id: $('#rec_group_id').val(),
                flag: $('#rec_flag').val(),
                head: $('#rec_head').val(),
                transaction_type: $('#rec_transaction_type').val(),
                particuler: $('#rec_particuler').val(),
                amount: $('#rec_amount').val(),
                id: 0
            };

            $.post('../api/other_recieveble.php', payload, function(res) {
                alert('Received income saved successfully!');
                location.reload();
            }).fail(function() {
                alert('Error saving income.');
            });
        });
    });
    </script>
</body>
</html>
