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

// Fetch recent fee collection history
$sql = "
    SELECT mr.id, mr.date, mr.fees as amount, fhm.name as head_name,
           m.first_name, m.middle_name, m.last_name
    FROM tbl_member_recieved mr
    JOIN tbl_members m ON mr.member_id = m.id
    LEFT JOIN tbl_fees_head_master fhm ON mr.head = fhm.id
    LEFT JOIN tbl_group_member_map gmm ON m.id = gmm.member_id
    WHERE mr.cancel = 0 AND mr.fees > 0
";
if (!in_array('ALL', $allowed_groups, true) && !empty($allowed_groups)) {
    $in = implode(',', array_map('intval', $allowed_groups));
    $sql .= " AND gmm.group_id IN ($in)";
}
$sql .= " GROUP BY mr.id ORDER BY mr.date DESC LIMIT 50";
$res = app_exec_query($sql);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
    <title>Fee Collection History - YMCA Mobile</title>
    
    <link href="../../css/bootstrap.min.css" rel="stylesheet">
    <link href="../../font-awesome/css/font-awesome.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <link href="mobile_layout.css" rel="stylesheet">

    <style>
        body { font-family: 'Plus Jakarta Sans', sans-serif !important; background: #f8fafc !important; }
        .rep-hero {
            background: linear-gradient(135deg, #06b6d4 0%, #0891b2 100%);
            border-radius: 20px; padding: 18px 20px; color: #ffffff; margin-bottom: 16px;
            box-shadow: 0 8px 20px rgba(6, 182, 212, 0.2);
        }
        .rep-hero h2 { margin: 0 0 4px 0; font-weight: 800; font-size: 20px; }
        .rep-hero p { margin: 0; font-size: 12.5px; opacity: 0.9; }

        .hist-item-card {
            background: #ffffff; border-radius: 16px; border: 1px solid #e2e8f0; padding: 14px 16px;
            margin-bottom: 10px; display: flex; align-items: center; justify-content: space-between;
            box-shadow: 0 2px 6px rgba(0,0,0,0.02);
        }
        .hist-date { font-size: 11px; font-weight: 700; color: #64748b; text-transform: uppercase; }
        .hist-head { font-size: 14px; font-weight: 800; color: #0f172a; margin: 2px 0 0 0; }
        .hist-narr { font-size: 11.5px; color: #059669; font-weight: 600; margin-top: 2px; }
        .hist-amt { font-size: 15px; font-weight: 800; color: #059669; text-align: right; }
    </style>
</head>
<body class="mob-body">

    <!-- Header -->
    <header class="mob-header">
        <div class="mob-header-brand">
            <a href="reports.php" style="color:#ffffff; margin-right:8px; font-size:18px;"><i class="fa fa-arrow-left"></i></a>
            <div class="mob-header-title">
                Collection <span>History</span>
            </div>
        </div>
        <div class="mob-header-actions">
            <a href="../../app_login_manager/logout.php" class="mob-header-btn" title="Logout"><i class="fa fa-sign-out"></i></a>
        </div>
    </header>

    <div class="mob-page">

        <div class="rep-hero">
            <h2>Fees Collection History</h2>
            <p>Member fee receipts, payments, and collection log</p>
        </div>

        <div>
            <?php if ($res && $res->num_rows > 0): ?>
                <?php while ($r = $res->fetch_assoc()): ?>
                    <?php $name = trim($r['first_name'] . ' ' . $r['middle_name'] . ' ' . $r['last_name']); ?>
                    <div class="hist-item-card">
                        <div>
                            <div class="hist-date"><i class="fa fa-calendar"></i> <?php echo ($r['date'] && $r['date'] !== '0000-00-00' && strtotime($r['date']) !== false) ? date('d M Y', strtotime($r['date'])) : '--'; ?></div>
                            <h4 class="hist-head"><?php echo htmlspecialchars($name); ?></h4>
                            <div class="hist-narr"><?php echo htmlspecialchars($r['head_name'] ?: 'Fee Receipt'); ?></div>
                        </div>
                        <div class="hist-amt">
                            +₹<?php echo number_format((float)$r['amount'], 2); ?>
                        </div>
                    </div>
                <?php endwhile; ?>
            <?php else: ?>
                <div style="text-align:center; padding:40px 20px; color:#94a3b8;">
                    <i class="fa fa-list-alt" style="font-size:36px; margin-bottom:10px; display:block;"></i>
                    No fee collection records found.
                </div>
            <?php endif; ?>
        </div>

    </div>

    <!-- Mobile Bottom Navigation (5 Tabs) -->
    <?php include 'mobile_bottom_nav.php'; ?>

    <script src="../../js/jquery-3.1.1.min.js"></script>
    <script src="../../js/bootstrap.min.js"></script>
</body>
</html>
