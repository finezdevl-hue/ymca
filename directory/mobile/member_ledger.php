<?php
session_start();
include '../../app_common/db_connect.php';
include '../../app_common/auth_helper.php';

if (empty($_SESSION['login_id'])) {
    header("Location: ../../index.php");
    exit();
}

$login_id = (int)$_SESSION['login_id'];
if (isNormalMember($login_id) || isExecutiveMember($login_id)) {
    header("Location: ledger.php");
    exit();
}
$user_member_id = (int)($_SESSION['user_id'] ?? 0);
$is_admin = isSuperAdmin($login_id) || isGroupAdmin($login_id) || isExecutiveMember($login_id);
$allowed_groups = getUserAllowedGroupIds($login_id);
$primary_role = getUserPrimaryRoleName($login_id);

$active_tab = 'reports';

// Fetch members list for dropdown
$sql_m = "
    SELECT DISTINCT m.id, m.first_name, m.middle_name, m.last_name
    FROM tbl_members m
    LEFT JOIN tbl_group_member_map gmm ON m.id = gmm.member_id
    WHERE m.inactive = 0
";
if (!in_array('ALL', $allowed_groups, true) && !empty($allowed_groups)) {
    $in = implode(',', array_map('intval', $allowed_groups));
    $sql_m .= " AND gmm.group_id IN ($in)";
}
$sql_m .= " ORDER BY m.first_name, m.middle_name, m.last_name";
$res_m = app_exec_query($sql_m);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
    <title>Member Ledger - YMCA Mobile</title>
    
    <link href="../../css/bootstrap.min.css" rel="stylesheet">
    <link href="../../font-awesome/css/font-awesome.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <link href="mobile_layout.css" rel="stylesheet">

    <style>
        body { font-family: 'Plus Jakarta Sans', sans-serif !important; background: #f8fafc !important; }
        .rep-hero {
            background: linear-gradient(135deg, #8b5cf6 0%, #6d28d9 100%);
            border-radius: 20px; padding: 18px 20px; color: #ffffff; margin-bottom: 16px;
            box-shadow: 0 8px 20px rgba(139, 92, 246, 0.2);
        }
        .rep-hero h2 { margin: 0 0 4px 0; font-weight: 800; font-size: 20px; }
        .rep-hero p { margin: 0; font-size: 12.5px; opacity: 0.9; }

        .rep-filter-box {
            background: #ffffff; border-radius: 16px; border: 1px solid #e2e8f0;
            padding: 14px; margin-bottom: 14px; box-shadow: 0 2px 8px rgba(0,0,0,0.02);
        }
        .rep-field { display: flex; flex-direction: column; gap: 4px; }
        .rep-field label { font-size: 11px; font-weight: 700; color: #64748b; text-transform: uppercase; margin: 0; }
        .rep-field select {
            padding: 10px 14px; border-radius: 12px; border: 1.5px solid #cbd5e1;
            background: #f8fafc; font-size: 13.5px; font-weight: 600; color: #0f172a; outline: none;
        }

        .kpi-grid-mobile {
            display: grid; grid-template-columns: repeat(2, 1fr); gap: 10px; margin-bottom: 14px;
        }
        .kpi-box-mob {
            background: #ffffff; border-radius: 16px; border: 1px solid #e2e8f0; padding: 12px 14px;
            box-shadow: 0 2px 8px rgba(0,0,0,0.02); position: relative; overflow: hidden;
        }
        .kpi-dark-border { border-left: 4.5px solid #0f172a !important; }
        .kpi-green-border { border-left: 4.5px solid #10b981 !important; }
        .kpi-lbl-mob { font-size: 10.5px; font-weight: 800; color: #64748b; text-transform: uppercase; letter-spacing: 0.3px; }
        .kpi-val-mob { font-size: 18px; font-weight: 800; color: #0f172a; margin-top: 3px; }
        .kpi-green-text { color: #10b981 !important; }

        .mob-tbl-card {
            background: #ffffff; border-radius: 16px; border: 1px solid #e2e8f0; overflow: hidden;
            box-shadow: 0 2px 8px rgba(0,0,0,0.02); margin-bottom: 20px;
        }
        .table-mob { width: 100%; border-collapse: collapse; font-size: 12.5px; }
        .table-mob th { background: #f8fafc; padding: 10px 12px; font-size: 10.5px; font-weight: 700; color: #475569; text-transform: uppercase; border-bottom: 1px solid #e2e8f0; }
        .table-mob td { padding: 12px; border-bottom: 1px solid #f1f5f9; color: #1e293b; font-weight: 500; }
    </style>
</head>
<body class="mob-body">

    <!-- Header -->
    <header class="mob-header">
        <div class="mob-header-brand">
            <a href="reports.php" style="color:#ffffff; margin-right:8px; font-size:18px;"><i class="fa fa-arrow-left"></i></a>
            <div class="mob-header-title">
                Member Cashbook <span>Ledger</span>
            </div>
        </div>
        <div class="mob-header-actions">
            <a href="../../app_login_manager/logout.php" class="mob-header-btn" title="Logout"><i class="fa fa-sign-out"></i></a>
        </div>
    </header>

    <div class="mob-page">

        <div class="rep-hero">
            <h2>Member Cashbook Ledger</h2>
            <p>Statement of receivables, payments, and balances</p>
        </div>

        <div class="rep-filter-box">
            <div class="rep-field" style="margin-bottom: 10px;">
                <label>Select Financial Year</label>
                <select id="rep_year" onchange="loadLedger()"></select>
            </div>
            <div class="rep-field">
                <label>Select Member</label>
                <select id="rep_member_id" onchange="loadLedger()">
                    <?php if ($res_m && $res_m->num_rows > 0): ?>
                        <?php while ($m = $res_m->fetch_assoc()): ?>
                            <?php 
                                $mName = trim($m['first_name'] . ' ' . $m['middle_name'] . ' ' . $m['last_name']);
                                $isSelected = ($user_member_id > 0 && (int)$m['id'] === $user_member_id) ? 'selected' : '';
                            ?>
                            <option value="<?php echo $m['id']; ?>" <?php echo $isSelected; ?>><?php echo htmlspecialchars($mName); ?></option>
                        <?php endwhile; ?>
                    <?php endif; ?>
                </select>
            </div>
        </div>

        <!-- 4 KPI Summary Cards (Fixed to Current Financial Year) -->
        <div class="kpi-grid-mobile">
            <div class="kpi-box-mob kpi-dark-border">
                <div class="kpi-lbl-mob">TOTAL CREDIT</div>
                <div class="kpi-val-mob" id="kpi_total_credit">₹0.00</div>
            </div>
            <div class="kpi-box-mob kpi-dark-border">
                <div class="kpi-lbl-mob">TOTAL DEBIT</div>
                <div class="kpi-val-mob" id="kpi_total_debit">₹0.00</div>
            </div>
            <div class="kpi-box-mob kpi-dark-border">
                <div class="kpi-lbl-mob">OPENING BALANCE</div>
                <div class="kpi-val-mob" id="kpi_op_balance">₹0.00</div>
            </div>
            <div class="kpi-box-mob kpi-green-border">
                <div class="kpi-lbl-mob">CLOSING BALANCE</div>
                <div class="kpi-val-mob kpi-green-text" id="kpi_cl_balance">₹0.00</div>
            </div>
        </div>

        <div class="mob-tbl-card">
            <div style="overflow-x:auto;">
                <table class="table-mob">
                    <thead>
                        <tr>
                            <th>Date</th>
                            <th>Particulars</th>
                            <th style="text-align:right;">Debit</th>
                            <th style="text-align:right;">Credit</th>
                        </tr>
                    </thead>
                    <tbody id="tbl_ledger_body">
                        <tr><td colspan="4" style="text-align:center; color:#94a3b8; padding:30px;">Loading ledger...</td></tr>
                    </tbody>
                </table>
            </div>
        </div>

    </div>

    <!-- Mobile Bottom Navigation (5 Tabs) -->
    <?php include 'mobile_bottom_nav.php'; ?>

    <script src="../../js/jquery-3.1.1.min.js"></script>
    <script src="../../js/bootstrap.min.js"></script>
    <script>
    function getCurrentFY() {
        let now = new Date();
        let year = now.getFullYear();
        let month = now.getMonth();
        return (month < 3) ? (year - 1) : year;
    }

    $(document).ready(function() {
        let cur_fy = getCurrentFY();
        let htmYear = "";
        for (let y = cur_fy + 1; y >= 2020; y--) {
            htmYear += '<option value="' + y + '">FY ' + y + ' - ' + (y + 1) + '</option>';
        }
        $('#rep_year').html(htmYear).val(cur_fy);

        loadLedger();
    });

    function updateKPICards(summary) {
        summary = summary || {};
        let op = parseFloat(summary.opening_balance || 0);
        let deb = parseFloat(summary.total_debit || 0);
        let cred = parseFloat(summary.total_credit || 0);
        let cl = parseFloat(summary.closing_balance || 0);

        $('#kpi_total_credit').text('₹' + cred.toFixed(2));
        $('#kpi_total_debit').text('₹' + deb.toFixed(2));
        $('#kpi_op_balance').text('₹' + op.toFixed(2));
        $('#kpi_cl_balance').text('₹' + cl.toFixed(2));
    }

    function loadLedger() {
        let member_id = $('#rep_member_id').val();
        let selected_year = $('#rep_year').val();
        let cur_fy = getCurrentFY();
        if (!member_id) return;

        $('#tbl_ledger_body').html('<tr><td colspan="4" style="text-align:center; color:#94a3b8; padding:30px;"><i class="fa fa-spinner fa-spin"></i> Loading ledger...</td></tr>');

        // 1. Fetch ledger table data for selected_year
        $.post('../api/member_cashbook_report.php', { action: 'get_member_cashbook', member_id: member_id, year: selected_year }, function(res) {
            try {
                let data = typeof res === 'string' ? JSON.parse(res) : res;

                if (parseInt(selected_year) === parseInt(cur_fy)) {
                    updateKPICards(data.summary);
                }

                let rows = data.transactions || [];
                let htm = '';

                if (rows.length === 0) {
                    $('#tbl_ledger_body').html('<tr><td colspan="4" style="text-align:center; color:#94a3b8; padding:30px;">No ledger entries found for this year.</td></tr>');
                } else {
                    rows.forEach(function(r) {
                        let d = r.date || '';
                        let dr = parseFloat(r.debit || 0);
                        let cr = parseFloat(r.credit || 0);

                        htm += `<tr>
                            <td>${d}</td>
                            <td>${r.particulars || 'Transaction'}</td>
                            <td style="text-align:right; color:${dr > 0 ? '#ef4444' : '#64748b'};">${dr > 0 ? '₹' + dr.toFixed(2) : '-'}</td>
                            <td style="text-align:right; color:${cr > 0 ? '#10b981' : '#64748b'};">${cr > 0 ? '₹' + cr.toFixed(2) : '-'}</td>
                        </tr>`;
                    });
                    $('#tbl_ledger_body').html(htm);
                }
            } catch(e) {
                $('#tbl_ledger_body').html('<tr><td colspan="4" style="text-align:center; color:#ef4444; padding:30px;">Error loading member ledger.</td></tr>');
            }
        });

        // 2. Fetch current FY summary specifically for KPI cards if selected_year is NOT current FY
        if (parseInt(selected_year) !== parseInt(cur_fy)) {
            $.post('../api/member_cashbook_report.php', { action: 'get_member_cashbook', member_id: member_id, year: cur_fy }, function(res) {
                try {
                    let data = typeof res === 'string' ? JSON.parse(res) : res;
                    updateKPICards(data.summary);
                } catch(e) {}
            });
        }
    }
    </script>
</body>
</html>
