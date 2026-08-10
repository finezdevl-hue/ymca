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
    header("Location: reports.php");
    exit();
}
$is_admin = isSuperAdmin($login_id) || isGroupAdmin($login_id);
$allowed_groups = getUserAllowedGroupIds($login_id);
$primary_role = getUserPrimaryRoleName($login_id);

$active_tab = 'reports';

// Fetch revenue by head summary
$sql = "
    SELECT fhm.name AS head_name, SUM(mr.fees) AS total_amount
    FROM tbl_member_recieved mr
    JOIN tbl_fees_head_master fhm ON mr.head = fhm.id
    LEFT JOIN tbl_group_member_map gmm ON mr.member_id = gmm.member_id
    WHERE mr.cancel = 0
";
if (!in_array('ALL', $allowed_groups, true) && !empty($allowed_groups)) {
    $in = implode(',', array_map('intval', $allowed_groups));
    $sql .= " AND gmm.group_id IN ($in)";
}
$sql .= " GROUP BY fhm.id, fhm.name ORDER BY total_amount DESC";
$res = app_exec_query($sql);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
    <title>Revenue by Head - YMCA Mobile</title>
    
    <link href="../../css/bootstrap.min.css" rel="stylesheet">
    <link href="../../font-awesome/css/font-awesome.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <link href="mobile_layout.css" rel="stylesheet">

    <style>
        body { font-family: 'Plus Jakarta Sans', sans-serif !important; background: #f8fafc !important; }
        .rep-hero {
            background: linear-gradient(135deg, #1d4ed8 0%, #3b82f6 100%);
            border-radius: 20px; padding: 18px 20px; color: #ffffff; margin-bottom: 16px;
            box-shadow: 0 8px 20px rgba(29, 78, 216, 0.2);
        }
        .rep-hero h2 { margin: 0 0 4px 0; font-weight: 800; font-size: 20px; }
        .rep-hero p { margin: 0; font-size: 12.5px; opacity: 0.9; }

        .mob-tbl-card {
            background: #ffffff; border-radius: 16px; border: 1px solid #e2e8f0; overflow: hidden;
            box-shadow: 0 2px 8px rgba(0,0,0,0.02); margin-bottom: 20px;
        }
        .table-mob { width: 100%; border-collapse: collapse; font-size: 13.5px; }
        .table-mob th { background: #f8fafc; padding: 12px 14px; font-size: 11px; font-weight: 700; color: #475569; text-transform: uppercase; border-bottom: 1px solid #e2e8f0; }
        .table-mob td { padding: 14px; border-bottom: 1px solid #f1f5f9; color: #1e293b; font-weight: 600; }
    </style>
</head>
<body class="mob-body">

    <!-- Header -->
    <header class="mob-header">
        <div class="mob-header-brand">
            <a href="reports.php" style="color:#ffffff; margin-right:8px; font-size:18px;"><i class="fa fa-arrow-left"></i></a>
            <div class="mob-header-title">
                Revenue by Head <span>Report</span>
            </div>
        </div>
        <div class="mob-header-actions">
            <a href="../../app_login_manager/logout.php" class="mob-header-btn" title="Logout"><i class="fa fa-sign-out"></i></a>
        </div>
    </header>

    <div class="mob-page">

        <div class="rep-hero">
            <h2>Revenue by Head Category</h2>
            <p>Category-wise breakdown of fee collections and revenue</p>
        </div>

        <div class="mob-tbl-card">
            <table class="table-mob">
                <thead>
                    <tr>
                        <th>Income Category</th>
                        <th style="text-align:right;">Total Collected</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if ($res && $res->num_rows > 0): ?>
                        <?php while ($r = $res->fetch_assoc()): ?>
                            <tr>
                                <td><?php echo htmlspecialchars($r['head_name']); ?></td>
                                <td style="text-align:right; color:#059669;">₹<?php echo number_format((float)$r['total_amount'], 2); ?></td>
                            </tr>
                        <?php endwhile; ?>
                    <?php else: ?>
                        <tr><td colspan="2" style="text-align:center; color:#94a3b8; padding:30px;">No revenue records found.</td></tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>

    </div>

    <!-- Mobile Bottom Navigation (5 Tabs) -->
    <?php include 'mobile_bottom_nav.php'; ?>

    <script src="../../js/jquery-3.1.1.min.js"></script>
    <script src="../../js/bootstrap.min.js"></script>
</body>
</html>
