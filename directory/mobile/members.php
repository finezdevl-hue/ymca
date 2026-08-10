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
$allowed_groups = getUserAllowedGroupIds($login_id);
$primary_role = getUserPrimaryRoleName($login_id);

$active_tab = 'members';

// Fetch members for allowed groups
$sql = "
    SELECT DISTINCT m.id, m.first_name, m.middle_name, m.last_name, m.email, m.phone, m.img, m.member_type,
           GROUP_CONCAT(DISTINCT g.name SEPARATOR ', ') AS group_names
    FROM tbl_members m
    LEFT JOIN tbl_group_member_map gmm ON m.id = gmm.member_id
    LEFT JOIN tbl_groups g ON gmm.group_id = g.id AND g.status = 1
    WHERE m.inactive = 0
";
$params = [];
$types = "";

if (!in_array('ALL', $allowed_groups, true) && !empty($allowed_groups)) {
    $in = implode(',', array_map('intval', $allowed_groups));
    $sql .= " AND gmm.group_id IN ($in)";
}

$sql .= " GROUP BY m.id ORDER BY m.first_name, m.middle_name, m.last_name";
$res = app_exec_query($sql);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
    <title>Members Roster - YMCA Mobile</title>
    
    <link href="../../css/bootstrap.min.css" rel="stylesheet">
    <link href="../../font-awesome/css/font-awesome.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <link href="mobile_layout.css" rel="stylesheet">

    <style>
        body { font-family: 'Plus Jakarta Sans', sans-serif !important; background: #f8fafc !important; }
        
        .mem-hero {
            background: linear-gradient(135deg, #1e1b4b 0%, #312e81 50%, #4338ca 100%);
            border-radius: 20px;
            padding: 22px 20px;
            color: #ffffff;
            margin-bottom: 16px;
            box-shadow: 0 10px 25px rgba(30, 27, 75, 0.15);
        }

        .mem-hero h2 { font-size: 20px; font-weight: 800; margin: 0 0 4px 0; }
        .mem-hero p { font-size: 12.5px; opacity: 0.8; margin: 0; }

        .mem-search {
            position: relative;
            margin-bottom: 16px;
        }

        .mem-search input {
            width: 100%;
            padding: 13px 16px 13px 44px;
            border-radius: 14px;
            border: 1.5px solid #e2e8f0;
            background: #ffffff;
            font-size: 13.5px;
            font-weight: 600;
            color: #0f172a;
            outline: none;
            box-shadow: 0 2px 10px rgba(0,0,0,0.02);
            transition: all 0.2s;
        }

        .mem-search input:focus {
            border-color: #4f46e5;
            box-shadow: 0 0 0 4px rgba(79, 70, 229, 0.1);
        }

        .mem-search i {
            position: absolute;
            left: 16px;
            top: 50%;
            transform: translateY(-50%);
            color: #94a3b8;
            font-size: 15px;
        }

        .member-card {
            background: #ffffff;
            border-radius: 16px;
            border: 1px solid #e2e8f0;
            padding: 14px 16px;
            margin-bottom: 10px;
            display: flex;
            align-items: center;
            justify-content: space-between;
            box-shadow: 0 2px 8px rgba(0,0,0,0.02);
        }

        .mem-avatar {
            width: 46px;
            height: 46px;
            border-radius: 14px;
            object-fit: cover;
            background: #e2e8f0;
            flex-shrink: 0;
        }

        .mem-info { flex: 1; margin-left: 12px; }
        .mem-name { font-size: 14px; font-weight: 800; color: #0f172a; margin: 0 0 2px 0; display:flex; align-items:center; gap:6px; flex-wrap:wrap; }
        .mem-group { font-size: 11.5px; font-weight: 600; color: #6366f1; }
        
        .pill-guest {
            background: #fff7ed;
            color: #c2410c;
            border: 1px solid #ffedd5;
            font-size: 10px;
            font-weight: 800;
            padding: 2px 7px;
            border-radius: 6px;
            letter-spacing: 0.4px;
            text-transform: uppercase;
        }

        .mem-call-btn {
            width: 38px;
            height: 38px;
            border-radius: 12px;
            background: #eff6ff;
            color: #2563eb;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 16px;
            text-decoration: none;
            transition: all 0.2s;
        }

        .mem-call-btn:active { background: #2563eb; color: #ffffff; }
    </style>
</head>
<body class="mob-body">

    <!-- Header -->
    <header class="mob-header">
        <div class="mob-header-brand">
            <div class="mob-header-logo">Y</div>
            <div class="mob-header-title">
                YMCA <span>Members Directory</span>
            </div>
        </div>
        <div class="mob-header-actions">
            <a href="../../app_login_manager/logout.php" class="mob-header-btn" title="Logout">
                <i class="fa fa-sign-out"></i>
            </a>
        </div>
    </header>

    <div class="mob-page">

        <div class="mem-hero">
            <h2>Group Members</h2>
            <p>Directory roster of active members in your assigned group</p>
        </div>

        <div class="mem-search">
            <i class="fa fa-search"></i>
            <input type="text" id="member_search_input" placeholder="Search members by name..." onkeyup="filterMembers()">
        </div>

        <div id="members_list_container">
            <?php if ($res && $res->num_rows > 0): ?>
                <?php while ($m = $res->fetch_assoc()): ?>
                    <?php 
                        $full_name = trim($m['first_name'] . ' ' . $m['middle_name'] . ' ' . $m['last_name']);
                        $img_src = !empty($m['img']) && $m['img'] != '0' ? "../../image_upload/members/uploads/" . $m['img'] : "../../img/customer.png";
                        $is_guest = ((int)($m['member_type'] ?? 0)) === 1;
                    ?>
                    <div class="member-card member-item-row" data-name="<?php echo strtolower(htmlspecialchars($full_name)); ?>">
                        <div style="display:flex; align-items:center; flex:1;">
                            <img src="<?php echo htmlspecialchars($img_src); ?>" class="mem-avatar" onerror="this.onerror=null; this.src='../../img/customer.png';">
                            <div class="mem-info">
                                <h3 class="mem-name">
                                    <?php echo htmlspecialchars($full_name); ?>
                                    <?php if ($is_guest): ?>
                                        <span class="pill-guest"><i class="fa fa-user-circle"></i> Guest</span>
                                    <?php endif; ?>
                                </h3>
                                <div class="mem-group"><i class="fa fa-users"></i> <?php echo htmlspecialchars($m['group_names'] ?: 'Member'); ?></div>
                            </div>
                        </div>
                        <div style="display:flex; align-items:center; gap:8px;">
                            <button type="button" onclick="toggleGuestStatus(<?php echo $m['id']; ?>, <?php echo $is_guest ? 1 : 0; ?>)" class="btn btn-xs <?php echo $is_guest ? 'btn-warning' : 'btn-default'; ?>" style="border-radius:8px; font-weight:700; font-size:11px; padding:4px 8px;" title="Toggle Guest Status">
                                <i class="fa <?php echo $is_guest ? 'fa-user-times' : 'fa-user-plus'; ?>"></i> <?php echo $is_guest ? 'Guest' : 'Make Guest'; ?>
                            </button>
                            <?php if (!empty($m['phone']) && $m['phone'] != '0'): ?>
                                <a href="tel:<?php echo htmlspecialchars($m['phone']); ?>" class="mem-call-btn" title="Call Member">
                                    <i class="fa fa-phone"></i>
                                </a>
                            <?php endif; ?>
                        </div>
                    </div>
                <?php endwhile; ?>
            <?php else: ?>
                <div style="text-align:center; padding:40px 20px; color:#94a3b8;">
                    <i class="fa fa-user-times" style="font-size:36px; margin-bottom:10px; display:block;"></i>
                    No group members found.
                </div>
            <?php endif; ?>
        </div>

    </div>

    <!-- Mobile Bottom Navigation (5 Tabs) -->
    <?php include 'mobile_bottom_nav.php'; ?>

    <script src="../../js/jquery-3.1.1.min.js"></script>
    <script src="../../js/bootstrap.min.js"></script>
    <script>
        function filterMembers() {
            var q = $('#member_search_input').val().toLowerCase();
            $('.member-item-row').each(function() {
                var name = $(this).attr('data-name');
                if (name.indexOf(q) !== -1) {
                    $(this).show();
                } else {
                    $(this).hide();
                }
            });
        }

        function toggleGuestStatus(memberId, currentStatus) {
            let newStatus = currentStatus === 1 ? 0 : 1;
            let actionName = newStatus === 1 ? "mark as Guest Member" : "convert to Regular Member";
            
            if (confirm("Are you sure you want to " + actionName + "?")) {
                $.post('../api/members.php', { action: 'toggle_guest_status', id: memberId, member_type: newStatus }, function(res) {
                    location.reload();
                }).fail(function() {
                    alert('Failed to update member status.');
                });
            }
        }
    </script>
</body>
</html>
