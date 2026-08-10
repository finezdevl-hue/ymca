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

// Fetch pending receivables member list
$sql = "
    SELECT m.id, m.first_name, m.middle_name, m.last_name, m.phone, m.img,
           GROUP_CONCAT(DISTINCT g.name SEPARATOR ', ') as group_names,
           (
               (SELECT IFNULL(SUM(fees),0) FROM tbl_member_recievable WHERE member_id = m.id AND cancel=0) -
               (SELECT IFNULL(SUM(fees),0) FROM tbl_member_recieved   WHERE member_id = m.id AND cancel=0)
           ) AS pending_due
    FROM tbl_members m
    LEFT JOIN tbl_group_member_map gmm ON m.id = gmm.member_id
    LEFT JOIN tbl_groups g ON gmm.group_id = g.id AND g.status = 1
    WHERE m.inactive = 0
";
if (!in_array('ALL', $allowed_groups, true) && !empty($allowed_groups)) {
    $in = implode(',', array_map('intval', $allowed_groups));
    $sql .= " AND gmm.group_id IN ($in)";
}
$sql .= " GROUP BY m.id HAVING pending_due > 0 ORDER BY pending_due DESC";
$res = app_exec_query($sql);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
    <title>Pending Receivables - YMCA Mobile</title>
    
    <link href="../../css/bootstrap.min.css" rel="stylesheet">
    <link href="../../font-awesome/css/font-awesome.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <link href="mobile_layout.css" rel="stylesheet">

    <style>
        body { font-family: 'Plus Jakarta Sans', sans-serif !important; background: #f8fafc !important; }
        .rep-hero {
            background: linear-gradient(135deg, #ef4444 0%, #dc2626 100%);
            border-radius: 20px; padding: 18px 20px; color: #ffffff; margin-bottom: 16px;
            box-shadow: 0 8px 20px rgba(239, 68, 68, 0.2);
        }
        .rep-hero h2 { margin: 0 0 4px 0; font-weight: 800; font-size: 20px; }
        .rep-hero p { margin: 0; font-size: 12.5px; opacity: 0.9; }

        .due-card {
            background: #ffffff; border-radius: 16px; border: 1px solid #e2e8f0; padding: 14px 16px;
            margin-bottom: 10px; display: flex; align-items: center; justify-content: space-between;
            box-shadow: 0 2px 6px rgba(0,0,0,0.02);
        }
        .mem-info { display: flex; align-items: center; gap: 12px; }
        .mem-avatar { width: 44px; height: 44px; border-radius: 12px; object-fit: cover; background: #e2e8f0; flex-shrink: 0; }
        .mem-name { font-size: 14px; font-weight: 800; color: #0f172a; margin: 0; }
        .mem-group { font-size: 11.5px; color: #64748b; font-weight: 600; }
        .due-amt { font-size: 16px; font-weight: 800; color: #dc2626; text-align: right; }
    </style>
</head>
<body class="mob-body">

    <!-- Header -->
    <header class="mob-header">
        <div class="mob-header-brand">
            <a href="reports.php" style="color:#ffffff; margin-right:8px; font-size:18px;"><i class="fa fa-arrow-left"></i></a>
            <div class="mob-header-title">
                Pending <span>Receivables</span>
            </div>
        </div>
        <div class="mob-header-actions">
            <a href="../../app_login_manager/logout.php" class="mob-header-btn" title="Logout"><i class="fa fa-sign-out"></i></a>
        </div>
    </header>

    <div class="mob-page">

        <div class="rep-hero">
            <h2>Pending Dues & Receivables</h2>
            <p>Outstanding member balances and fee receivables list</p>
        </div>

        <div>
            <?php if ($res && $res->num_rows > 0): ?>
                <?php while ($r = $res->fetch_assoc()): ?>
                    <?php 
                        $name = trim($r['first_name'] . ' ' . $r['middle_name'] . ' ' . $r['last_name']);
                        $img_src = !empty($r['img']) && $r['img'] != '0' ? "../../image_upload/members/uploads/" . $r['img'] : "../../img/customer.png";
                    ?>
                    <div class="due-card">
                        <div class="mem-info">
                            <img src="<?php echo htmlspecialchars($img_src); ?>" class="mem-avatar" onerror="this.onerror=null; this.src='../../img/customer.png';">
                            <div>
                                <h4 class="mem-name"><?php echo htmlspecialchars($name); ?></h4>
                                <div class="mem-group"><i class="fa fa-users"></i> <?php echo htmlspecialchars($r['group_names'] ?: 'Member'); ?></div>
                            </div>
                        </div>
                        <div class="due-amt">
                            ₹<?php echo number_format((float)$r['pending_due'], 2); ?>
                        </div>
                    </div>
                <?php endwhile; ?>
            <?php else: ?>
                <div style="text-align:center; padding:40px 20px; color:#94a3b8;">
                    <i class="fa fa-check-circle-o" style="font-size:36px; margin-bottom:10px; display:block; color:#10b981;"></i>
                    Great! No pending receivables found.
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
