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

// Fetch ONLY guest members (member_type = 1)
$sql = "
    SELECT DISTINCT m.id, m.first_name, m.middle_name, m.last_name, m.email, m.phone, m.img, m.member_type,
           GROUP_CONCAT(DISTINCT g.name SEPARATOR ', ') AS group_names
    FROM tbl_members m
    LEFT JOIN tbl_group_member_map gmm ON m.id = gmm.member_id
    LEFT JOIN tbl_groups g ON gmm.group_id = g.id AND g.status = 1
    WHERE m.inactive = 0 AND m.member_type = 1
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
    <title>Guests List - YMCA Mobile</title>
    
    <link href="../../css/bootstrap.min.css" rel="stylesheet">
    <link href="../../font-awesome/css/font-awesome.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <link href="mobile_layout.css" rel="stylesheet">

    <style>
        body { font-family: 'Plus Jakarta Sans', sans-serif !important; background: #f8fafc !important; }
        
        .guest-hero {
            background: linear-gradient(135deg, #d97706 0%, #b45309 50%, #78350f 100%);
            border-radius: 20px;
            padding: 22px 20px;
            color: #ffffff;
            margin-bottom: 16px;
            box-shadow: 0 10px 25px rgba(217, 119, 6, 0.2);
        }

        .guest-hero h2 { font-size: 20px; font-weight: 800; margin: 0 0 4px 0; }
        .guest-hero p { font-size: 12.5px; opacity: 0.9; margin: 0; }

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
            border-color: #f59e0b;
            box-shadow: 0 0 0 4px rgba(245, 158, 11, 0.12);
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
        .mem-group { font-size: 11.5px; font-weight: 600; color: #d97706; }
        
        .pill-guest {
            background: linear-gradient(135deg, #fff7ed 0%, #ffedd5 100%);
            color: #c2410c;
            border: 1px solid rgba(249, 115, 22, 0.3);
            box-shadow: 0 2px 6px rgba(249, 115, 22, 0.12);
            font-size: 9.5px;
            font-weight: 800;
            padding: 3px 9px 3px 7px;
            border-radius: 16px;
            letter-spacing: 0.5px;
            text-transform: uppercase;
            display: inline-flex;
            align-items: center;
            gap: 3px;
        }

        .mem-call-btn {
            width: 34px;
            height: 34px;
            border-radius: 10px;
            background: #eff6ff;
            color: #2563eb;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 14px;
            text-decoration: none;
        }
    </style>
</head>
<body class="mob-body">

    <!-- Header -->
    <header class="mob-header">
        <div class="mob-header-brand">
            <a href="accounts.php" style="color:#ffffff; margin-right:8px; font-size:18px;"><i class="fa fa-arrow-left"></i></a>
            <div class="mob-header-title">
                YMCA <span>Guest Members</span>
            </div>
        </div>
        <div class="mob-header-actions">
            <a href="../../app_login_manager/logout.php" class="mob-header-btn" title="Logout">
                <i class="fa fa-sign-out"></i>
            </a>
        </div>
    </header>

    <div class="mob-page">

        <div class="guest-hero" style="display:flex; align-items:center; justify-content:space-between; flex-wrap:wrap; gap:10px;">
            <div>
                <h2><i class="fa fa-user-circle"></i> Guests List</h2>
                <p>Dedicated roster of guest and temporary members</p>
            </div>
            <button type="button" class="btn btn-warning btn-xs" onclick="openAddGuestModalMobile()" style="border-radius:10px; font-weight:800; font-size:12px; padding:7px 14px; background:#ffffff; color:#d97706; border:none; box-shadow:0 4px 12px rgba(0,0,0,0.15);">
                <i class="fa fa-user-plus"></i> Add Guest
            </button>
        </div>

        <div class="mem-search">
            <i class="fa fa-search"></i>
            <input type="text" id="member_search_input" placeholder="Search guests by name..." onkeyup="filterMembers()">
        </div>

        <div id="members_list_container">
            <?php if ($res && $res->num_rows > 0): ?>
                <?php while ($m = $res->fetch_assoc()): ?>
                    <?php 
                        $full_name = trim($m['first_name'] . ' ' . $m['middle_name'] . ' ' . $m['last_name']);
                        $img_src = !empty($m['img']) && $m['img'] != '0' ? "../../image_upload/members/uploads/" . $m['img'] : "../../img/customer.png";
                    ?>
                    <div class="member-card member-item-row" data-name="<?php echo strtolower(htmlspecialchars($full_name)); ?>">
                        <div style="display:flex; align-items:center; flex:1;">
                            <img src="<?php echo htmlspecialchars($img_src); ?>" class="mem-avatar" onerror="this.onerror=null; this.src='../../img/customer.png';">
                            <div class="mem-info">
                                <h3 class="mem-name">
                                    <?php echo htmlspecialchars($full_name); ?>
                                    <span class="pill-guest"><i class="fa fa-star" style="color:#f59e0b; font-size:9px;"></i> Guest</span>
                                </h3>
                                <div class="mem-group"><i class="fa fa-users"></i> <?php echo htmlspecialchars($m['group_names'] ?: 'Guest'); ?></div>
                            </div>
                        </div>
                        <div style="display:flex; align-items:center; gap:6px;">
                            <a href="member_receivable.php?member_id=<?php echo $m['id']; ?>" class="btn btn-xs btn-primary" style="border-radius:8px; font-weight:800; font-size:11px; padding:4px 8px;">
                                <i class="fa fa-book"></i> Ledger
                            </a>
                            <button type="button" onclick="convertToRegular(<?php echo $m['id']; ?>)" class="btn btn-xs btn-warning" style="border-radius:8px; font-weight:800; font-size:11px; padding:4px 8px;" title="Make Regular Member">
                                <i class="fa fa-user-check"></i> Make Regular
                            </button>
                        </div>
                    </div>
                <?php endwhile; ?>
            <?php else: ?>
                <div style="text-align:center; padding:50px 20px; color:#94a3b8;">
                    <i class="fa fa-user-times" style="font-size:42px; margin-bottom:12px; display:block; color:#cbd5e1;"></i>
                    <h4 style="font-weight:700; color:#64748b;">No Guest Members Found</h4>
                    <p style="font-size:12.5px;">Members flagged as guests using the "Make Guest" toggle will appear here.</p>
                </div>
            <?php endif; ?>
        </div>

    </div>

    <!-- Mobile Add Guest Modal -->
    <div class="modal fade" id="addGuestModalMobile" tabindex="-1" role="dialog">
        <div class="modal-dialog modal-dialog-centered" role="document">
            <div class="modal-content" style="border-radius:24px; border:none; box-shadow: 0 25px 50px -12px rgba(0, 0, 0, 0.25);">
                <div class="modal-header" style="background: linear-gradient(135deg, #f59e0b 0%, #d97706 100%); padding: 18px 24px; color: white;">
                    <button type="button" class="close" data-dismiss="modal" style="color:white; opacity:0.9; font-size:24px;">&times;</button>
                    <h4 class="modal-title" style="font-weight:800; font-size:16px; margin:0;"><i class="fa fa-user-plus"></i> Add Guest Member</h4>
                </div>
                <div class="modal-body" style="padding: 24px;">
                    <div class="form-group" style="margin-bottom:14px;">
                        <label style="font-size:11px; font-weight:800; color:#64748b; text-transform:uppercase;">First Name *</label>
                        <input type="text" id="txt_mob_guest_fn" class="form-control" placeholder="e.g. Akash" style="border-radius:12px; height:44px; font-weight:700;">
                    </div>
                    <div class="form-group" style="margin-bottom:14px;">
                        <label style="font-size:11px; font-weight:800; color:#64748b; text-transform:uppercase;">Last Name</label>
                        <input type="text" id="txt_mob_guest_ln" class="form-control" placeholder="e.g. Kumar" style="border-radius:12px; height:44px; font-weight:600;">
                    </div>
                    <div class="form-group" style="margin-bottom:14px;">
                        <label style="font-size:11px; font-weight:800; color:#64748b; text-transform:uppercase;">Phone Number</label>
                        <input type="text" id="txt_mob_guest_phone" class="form-control" placeholder="e.g. 9876543210" style="border-radius:12px; height:44px; font-weight:600;">
                    </div>
                    <div class="form-group" style="margin-bottom:14px;">
                        <label style="font-size:11px; font-weight:800; color:#64748b; text-transform:uppercase;">Email Address</label>
                        <input type="email" id="txt_mob_guest_email" class="form-control" placeholder="e.g. akash@gmail.com" style="border-radius:12px; height:44px; font-weight:600;">
                    </div>
                    <button type="button" onclick="submitAddGuestMobile()" class="btn btn-block" style="background: linear-gradient(135deg, #f59e0b 0%, #d97706 100%); color:white; font-weight:800; height:46px; border-radius:14px; border:none; margin-top:16px;">
                        <i class="fa fa-check"></i> Save Guest Member
                    </button>
                </div>
            </div>
        </div>
    </div>

    <!-- Mobile Bottom Navigation (5 Tabs) -->
    <?php include 'mobile_bottom_nav.php'; ?>

    <script src="../../js/jquery-3.1.1.min.js"></script>
    <script src="../../js/bootstrap.min.js"></script>
    <script>
        function openAddGuestModalMobile() {
            $('#txt_mob_guest_fn').val('');
            $('#txt_mob_guest_ln').val('');
            $('#txt_mob_guest_phone').val('');
            $('#txt_mob_guest_email').val('');
            $('#addGuestModalMobile').modal('show');
        }

        function submitAddGuestMobile() {
            let fn = $('#txt_mob_guest_fn').val() || '';
            if (!fn.trim()) {
                alert("Please enter guest first name.");
                return;
            }
            $.post('../api/members.php', {
                action: 'save_members',
                id: 0,
                member_type: 1,
                first_name: fn.trim(),
                last_name: ($('#txt_mob_guest_ln').val() || '').trim(),
                phone: ($('#txt_mob_guest_phone').val() || '').trim(),
                email: ($('#txt_mob_guest_email').val() || '').trim(),
                inactive: 0
            }, function(res) {
                $('#addGuestModalMobile').modal('hide');
                alert('Guest member added successfully!');
                location.reload();
            }).fail(function(xhr) {
                let msg = "Error adding guest member.";
                try {
                    let res = JSON.parse(xhr.responseText);
                    if (res.Message) msg = res.Message;
                } catch(e){}
                alert(msg);
            });
        }

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

        function convertToRegular(memberId) {
            if (confirm("Are you sure you want to convert this Guest Member to a Regular Member?")) {
                $.post('../api/members.php', { action: 'toggle_guest_status', id: memberId, member_type: 0 }, function(res) {
                    location.reload();
                }).fail(function() {
                    alert('Failed to convert member.');
                });
            }
        }
    </script>
</body>
</html>
