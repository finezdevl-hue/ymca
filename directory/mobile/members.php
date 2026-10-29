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

// Fetch allowed groups details for modal selector
$user_groups = [];
if (in_array('ALL', $allowed_groups, true)) {
    $g_res = app_exec_query("SELECT id, name FROM tbl_groups WHERE status = 1 ORDER BY name");
    if ($g_res) {
        while ($r = $g_res->fetch_assoc()) {
            $user_groups[] = $r;
        }
    }
} else if (!empty($allowed_groups)) {
    $in_g = implode(',', array_map('intval', $allowed_groups));
    $g_res = app_exec_query("SELECT id, name FROM tbl_groups WHERE status = 1 AND id IN ($in_g) ORDER BY name");
    if ($g_res) {
        while ($r = $g_res->fetch_assoc()) {
            $user_groups[] = $r;
        }
    }
}

// Fetch blood groups
$blood_groups = [];
$bg_res = app_exec_query("SELECT id, name FROM tbl_bloodgroup_master ORDER BY id");
if ($bg_res && $bg_res->num_rows > 0) {
    while ($bg = $bg_res->fetch_assoc()) {
        $blood_groups[] = $bg;
    }
}

// Fetch members for allowed groups with full profile details
$sql = "
    SELECT DISTINCT m.id, m.first_name, m.middle_name, m.last_name, m.gender, m.father_name, m.mother_name,
           m.dob, m.phone, m.whtsapp, m.email, m.p_street, m.p_city, m.p_pincode, m.p_country, m.img, m.member_type,
           bg.name AS blood_group_name,
           GROUP_CONCAT(DISTINCT g.name SEPARATOR ', ') AS group_names
    FROM tbl_members m
    LEFT JOIN tbl_bloodgroup_master bg ON m.blood_group = bg.id
    LEFT JOIN tbl_group_member_map gmm ON m.id = gmm.member_id
    LEFT JOIN tbl_groups g ON gmm.group_id = g.id AND g.status = 1
    WHERE m.inactive = 0
";

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
    <link href="../../css/plugins/sweetalert/sweetalert.css" rel="stylesheet">
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
            transition: all 0.2s;
        }

        .mem-call-btn:active { background: #2563eb; color: #ffffff; }

        .mem-wa-btn {
            width: 34px;
            height: 34px;
            border-radius: 10px;
            background: #dcfce7;
            color: #16a34a;
            border: 1px solid #86efac;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 14px;
            text-decoration: none;
            transition: all 0.2s;
        }

        .mem-wa-btn:hover, .mem-wa-btn:focus {
            background: #bbf7d0;
            color: #15803d;
            text-decoration: none;
        }
        .mem-wa-btn:active { background: #16a34a; color: #ffffff; }

        .search-item-row {
            display: flex;
            align-items: center;
            justify-content: space-between;
            padding: 10px 12px;
            border-bottom: 1px solid #f1f5f9;
            background: #ffffff;
            border-radius: 10px;
            margin-bottom: 6px;
        }
        .search-item-row:last-child { border-bottom: none; }

        .form-section-header {
            font-size: 11.5px;
            font-weight: 800;
            color: #4338ca;
            text-transform: uppercase;
            letter-spacing: 0.05em;
            margin: 16px 0 10px;
            padding-bottom: 6px;
            border-bottom: 1.5px solid #e2e8f0;
            display: flex;
            align-items: center;
            gap: 6px;
        }

        .form-field-group {
            margin-bottom: 12px;
        }

        .form-field-group label {
            font-size: 11px;
            font-weight: 800;
            color: #64748b;
            text-transform: uppercase;
            margin-bottom: 4px;
            display: block;
        }

        .form-field-group .form-control {
            border-radius: 12px;
            height: 42px;
            font-weight: 600;
            font-size: 13.5px;
            border: 1.5px solid #e2e8f0;
            background: #fafbff;
        }

        .form-field-group .form-control:focus {
            border-color: #4f46e5;
            box-shadow: 0 0 0 3px rgba(79, 70, 229, 0.1);
            background: #ffffff;
        }

        /* Modern SweetAlert Mobile Styling */
        .sweet-overlay {
            z-index: 20000000 !important;
            position: fixed !important;
        }
        .sweet-alert {
            z-index: 20000050 !important;
            position: fixed !important;
            border-radius: 20px !important;
            padding: 24px 20px !important;
            box-shadow: 0 20px 50px rgba(0,0,0,0.35) !important;
            font-family: 'Plus Jakarta Sans', sans-serif !important;
        }
        @media all and (max-width: 540px) {
            .sweet-alert {
                width: calc(100% - 32px) !important;
                left: 16px !important;
                right: 16px !important;
                margin-left: 0 !important;
                margin-right: 0 !important;
                top: 50% !important;
                transform: translateY(-50%) !important;
            }
        }
        .sweet-alert h2 {
            font-size: 19px !important;
            font-weight: 800 !important;
            color: #0f172a !important;
            margin: 14px 0 8px !important;
        }
        .sweet-alert p {
            font-size: 13.5px !important;
            color: #64748b !important;
            font-weight: 500 !important;
            line-height: 1.5 !important;
        }
        .sweet-alert button {
            border-radius: 12px !important;
            padding: 10px 22px !important;
            font-size: 13px !important;
            font-weight: 700 !important;
            box-shadow: none !important;
        }
        .sweet-alert button.confirm {
            background: linear-gradient(135deg, #1e1b4b 0%, #4338ca 100%) !important;
        }
        .sweet-alert button.cancel {
            background: #f1f5f9 !important;
            color: #64748b !important;
        }
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
            <div style="display:flex; justify-content:space-between; align-items:center; flex-wrap:wrap; gap:12px;">
                <div>
                    <h2>Group Members</h2>
                    <p>Directory roster of active members in your assigned group</p>
                </div>
                <div>
                    <button type="button" onclick="openAddMemberModalMobile()" class="btn btn-light" style="border-radius:12px; font-weight:800; color:#4338ca; padding:8px 14px; font-size:13px; border:none; box-shadow:0 4px 12px rgba(0,0,0,0.15); display:inline-flex; align-items:center; gap:6px;">
                        <i class="fa fa-plus-circle" style="font-size:15px;"></i> Add Member
                    </button>
                </div>
            </div>
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
                        $safe_name = addslashes(htmlspecialchars($full_name, ENT_QUOTES));
                        $img_file = !empty($m['img']) && $m['img'] != '0' ? $m['img'] : '';
                        $img_version = '';
                        if (!empty($img_file)) {
                            $real_p = __DIR__ . "/../../image_upload/members/uploads/" . $img_file;
                            if (file_exists($real_p)) {
                                $img_version = '?v=' . filemtime($real_p);
                            }
                        }
                        $img_src = !empty($img_file) ? "../../image_upload/members/uploads/" . $img_file . $img_version : "../../img/customer.png";
                        $is_guest = ((int)($m['member_type'] ?? 0)) === 1;

                        $address_parts = [];
                        if (!empty($m['p_street']) && $m['p_street'] !== '0') $address_parts[] = trim($m['p_street']);
                        if (!empty($m['p_city']) && $m['p_city'] !== '0') $address_parts[] = trim($m['p_city']);
                        if (!empty($m['p_pincode']) && $m['p_pincode'] !== '0') $address_parts[] = trim($m['p_pincode']);
                        if (!empty($m['p_country']) && $m['p_country'] !== '0') $address_parts[] = trim($m['p_country']);
                        $formatted_address = !empty($address_parts) ? implode(', ', $address_parts) : 'Not Specified';

                        $dob_formatted = (!empty($m['dob']) && $m['dob'] !== '0000-00-00') ? date('d M Y', strtotime($m['dob'])) : 'Not Specified';
                        $phone_clean = (!empty($m['phone']) && $m['phone'] !== '0') ? trim($m['phone']) : '';
                        $whtsapp_clean = (!empty($m['whtsapp']) && $m['whtsapp'] !== '0') ? trim($m['whtsapp']) : $phone_clean;
                        $wa_digits = preg_replace('/[^0-9]/', '', $whtsapp_clean);
                        if (strlen($wa_digits) === 10) {
                            $wa_digits = '91' . $wa_digits;
                        }
                        $email_clean = (!empty($m['email']) && $m['email'] !== '0') ? trim($m['email']) : '';
                        $blood_clean = (!empty($m['blood_group_name']) && $m['blood_group_name'] !== 'Not Specified') ? trim($m['blood_group_name']) : 'Not Specified';
                        $gender_clean = (!empty($m['gender']) && $m['gender'] !== '0') ? trim($m['gender']) : 'Not Specified';
                        $father_clean = (!empty($m['father_name']) && $m['father_name'] !== '0') ? trim($m['father_name']) : 'Not Specified';

                        $profile_data = [
                            'id' => (int)$m['id'],
                            'name' => $full_name,
                            'img' => $img_src,
                            'group_names' => $m['group_names'] ?: 'General Member',
                            'is_guest' => $is_guest,
                            'phone' => $phone_clean,
                            'whtsapp' => $whtsapp_clean,
                            'email' => $email_clean,
                            'blood_group' => $blood_clean,
                            'gender' => $gender_clean,
                            'dob' => $dob_formatted,
                            'father_name' => $father_clean,
                            'address' => $formatted_address
                        ];
                        $profile_json = htmlspecialchars(json_encode($profile_data, JSON_HEX_APOS | JSON_HEX_QUOT), ENT_QUOTES, 'UTF-8');
                    ?>
                    <div class="member-card member-item-row" data-name="<?php echo strtolower(htmlspecialchars($full_name)); ?>">
                        <div style="display:flex; align-items:center; flex:1; overflow:hidden; cursor:pointer;" onclick="openMemberProfile(this)" data-profile='<?php echo $profile_json; ?>' title="Tap to view profile">
                            <img src="<?php echo htmlspecialchars($img_src); ?>" class="mem-avatar" onerror="this.onerror=null; this.src='../../img/customer.png';">
                            <div class="mem-info" style="overflow:hidden; text-overflow:ellipsis;">
                                <h3 class="mem-name">
                                    <?php echo htmlspecialchars($full_name); ?>
                                    <?php if ($is_guest): ?>
                                        <span class="pill-guest"><i class="fa fa-user-circle"></i> Guest</span>
                                    <?php endif; ?>
                                </h3>
                                <div class="mem-group"><i class="fa fa-users"></i> <?php echo htmlspecialchars($m['group_names'] ?: 'Member'); ?></div>
                            </div>
                        </div>
                        <div style="display:flex; align-items:center; gap:4px; flex-shrink:0;">
                            <button type="button" onclick="openMemberProfile(this)" data-profile='<?php echo $profile_json; ?>' class="btn btn-xs" style="border-radius:8px; font-weight:700; font-size:11px; padding:4px 7px; border:1px solid #c7d2fe; background:#eef2ff; color:#4338ca; display:inline-flex; align-items:center; gap:3px;" title="View Member Profile">
                                <i class="fa fa-user-circle"></i> Profile
                            </button>
                            <button type="button" onclick="toggleGuestStatus(<?php echo $m['id']; ?>, <?php echo $is_guest ? 1 : 0; ?>, '<?php echo $safe_name; ?>')" class="btn btn-xs <?php echo $is_guest ? 'btn-warning' : 'btn-default'; ?>" style="width:26px; height:26px; border-radius:8px; font-weight:700; font-size:11px; padding:0; display:inline-flex; align-items:center; justify-content:center;" title="Toggle Guest Status">
                                <i class="fa <?php echo $is_guest ? 'fa-user-times' : 'fa-user-plus'; ?>"></i>
                            </button>
                            <?php if (!empty($phone_clean)): ?>
                                <a href="tel:<?php echo htmlspecialchars($phone_clean); ?>" class="mem-call-btn" title="Call Member" style="width:26px; height:26px; border-radius:8px; font-size:11px;">
                                    <i class="fa fa-phone"></i>
                                </a>
                            <?php endif; ?>
                            <?php if (!empty($wa_digits)): ?>
                                <a href="https://wa.me/<?php echo $wa_digits; ?>" target="_blank" class="mem-wa-btn" title="Chat on WhatsApp" style="width:26px; height:26px; border-radius:8px; font-size:12px;">
                                    <i class="fa fa-whatsapp"></i>
                                </a>
                            <?php endif; ?>
                            <button type="button" onclick="removeMemberFromGroup(<?php echo $m['id']; ?>, '<?php echo $safe_name; ?>')" class="btn btn-xs btn-outline-danger" style="width:26px; height:26px; border-radius:8px; font-weight:700; font-size:11px; padding:0; border:1px solid #fecdd3; background:#fff1f2; color:#e11d48; display:inline-flex; align-items:center; justify-content:center;" title="Remove from Group">
                                <i class="fa fa-trash"></i>
                            </button>
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

    <!-- Member Profile Details Modal (Mobile) -->
    <div class="modal fade" id="memberProfileModal" tabindex="-1" role="dialog" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered" role="document" style="max-width:500px; margin:16px auto;">
            <div class="modal-content" style="border-radius:24px; border:none; box-shadow: 0 25px 50px -12px rgba(0, 0, 0, 0.25); overflow:hidden;">
                <!-- Gradient Header Banner -->
                <div style="background: linear-gradient(135deg, #1e1b4b 0%, #312e81 50%, #4338ca 100%); padding: 22px 20px 72px 20px; color: white; position: relative; z-index: 1; text-align: center;">
                    <button type="button" class="close" data-dismiss="modal" style="position:absolute; top:16px; right:18px; color:white; opacity:0.85; font-size:24px; z-index: 2;">&times;</button>
                    <div style="font-size:13px; font-weight:700; opacity:0.85; text-transform:uppercase; letter-spacing:0.8px;">
                        <i class="fa fa-id-card-o"></i> Member Profile
                    </div>
                </div>

                <div class="modal-body" style="padding: 0 20px 24px 20px; margin-top: -36px; position: relative; z-index: 5;">
                    <!-- Profile Avatar & Title Card -->
                    <div style="background:#ffffff; border-radius:20px; box-shadow:0 8px 25px rgba(0,0,0,0.06); border:1px solid #e2e8f0; padding:18px; text-align:center; margin-bottom:16px; position: relative; z-index: 5;">
                        <div style="position:relative; z-index: 10; width:92px; height:92px; margin:-46px auto 10px auto; cursor:pointer;" onclick="viewFullProfilePhoto()" title="Tap to view full photo">
                            <img id="prof_modal_img" src="../../img/customer.png" style="width:92px; height:92px; border-radius:50%; object-fit:cover; object-position:center top; border:4px solid #ffffff; box-shadow:0 8px 24px rgba(0,0,0,0.18), 0 0 0 1px rgba(0,0,0,0.08); background:#fff;" onerror="this.onerror=null; this.src='../../img/customer.png';">
                            <span id="prof_modal_badge_dot" style="position:absolute; bottom:2px; right:4px; width:16px; height:16px; border-radius:50%; background:#10b981; border:2px solid #ffffff;"></span>
                            <span id="prof_modal_zoom_hint" style="position:absolute; bottom:2px; left:4px; width:22px; height:22px; border-radius:50%; background:rgba(15,23,42,0.8); color:#ffffff; display:inline-flex; align-items:center; justify-content:center; font-size:10px; border:2px solid #ffffff; box-shadow:0 2px 6px rgba(0,0,0,0.25);" title="Tap to view full photo"><i class="fa fa-search-plus"></i></span>
                        </div>
                        <h3 id="prof_modal_name" style="font-size:18px; font-weight:800; color:#0f172a; margin:0 0 4px 0;"></h3>
                        <div style="display:flex; justify-content:center; align-items:center; gap:6px; flex-wrap:wrap; margin-bottom:12px;">
                            <span id="prof_modal_group" style="background:#eef2ff; color:#4338ca; border:1px solid #c7d2fe; font-size:11px; font-weight:700; padding:3px 10px; border-radius:12px;"><i class="fa fa-users"></i> Morning</span>
                            <span id="prof_modal_type" style="background:#ecfdf5; color:#059669; border:1px solid #a7f3d0; font-size:11px; font-weight:700; padding:3px 10px; border-radius:12px;">Regular Member</span>
                        </div>

                        <!-- Quick Action Chips (Call, WhatsApp, Email, Dues) -->
                        <div style="display:flex; justify-content:center; align-items:center; gap:8px; padding-top:10px; border-top:1px solid #f1f5f9;">
                            <a id="prof_modal_btn_call" href="#" class="btn btn-default" style="flex:1; max-width:85px; min-width:0; border-radius:12px; font-size:11px; font-weight:700; padding:8px 4px; display:flex; flex-direction:column; align-items:center; gap:4px; border:1px solid #e2e8f0; color:#059669; background:#ecfdf5; text-decoration:none;">
                                <i class="fa fa-phone" style="font-size:16px;"></i> Call
                            </a>
                            <a id="prof_modal_btn_wa" href="#" target="_blank" class="btn btn-default" style="flex:1; max-width:85px; min-width:0; border-radius:12px; font-size:11px; font-weight:700; padding:8px 4px; display:flex; flex-direction:column; align-items:center; gap:4px; border:1px solid #bbf7d0; color:#16a34a; background:#f0fdf4; text-decoration:none;">
                                <i class="fa fa-whatsapp" style="font-size:16px;"></i> WhatsApp
                            </a>
                            <a id="prof_modal_btn_email" href="#" class="btn btn-default" style="flex:1; max-width:85px; min-width:0; border-radius:12px; font-size:11px; font-weight:700; padding:8px 4px; display:flex; flex-direction:column; align-items:center; gap:4px; border:1px solid #e2e8f0; color:#2563eb; background:#eff6ff; text-decoration:none;">
                                <i class="fa fa-envelope" style="font-size:16px;"></i> Email
                            </a>
                            <a id="prof_modal_btn_fees" href="#" class="btn btn-default" style="flex:1; max-width:85px; min-width:0; border-radius:12px; font-size:11px; font-weight:700; padding:8px 4px; display:flex; flex-direction:column; align-items:center; gap:4px; border:1px solid #fed7aa; color:#ea580c; background:#fff7ed; text-decoration:none;">
                                <i class="fa fa-calculator" style="font-size:16px;"></i> Dues
                            </a>
                        </div>
                    </div>

                    <!-- Personal Information Card -->
                    <div style="background:#ffffff; border-radius:16px; border:1px solid #e2e8f0; padding:14px 16px; margin-bottom:12px;">
                        <div style="font-size:11.5px; font-weight:800; color:#4338ca; text-transform:uppercase; letter-spacing:0.5px; margin-bottom:10px; display:flex; align-items:center; gap:6px;">
                            <i class="fa fa-user-circle"></i> Personal Details
                        </div>
                        <div style="display:grid; grid-template-columns: 1fr 1fr; gap:10px;">
                            <div style="background:#f8fafc; padding:8px 10px; border-radius:10px;">
                                <span style="font-size:10px; color:#64748b; font-weight:700; display:block;">BLOOD GROUP</span>
                                <span id="prof_modal_blood" style="font-size:12.5px; font-weight:800; color:#dc2626;"><i class="fa fa-tint"></i> O+</span>
                            </div>
                            <div style="background:#f8fafc; padding:8px 10px; border-radius:10px;">
                                <span style="font-size:10px; color:#64748b; font-weight:700; display:block;">GENDER</span>
                                <span id="prof_modal_gender" style="font-size:12.5px; font-weight:700; color:#0f172a;">Male</span>
                            </div>
                            <div style="background:#f8fafc; padding:8px 10px; border-radius:10px;">
                                <span style="font-size:10px; color:#64748b; font-weight:700; display:block;">DATE OF BIRTH</span>
                                <span id="prof_modal_dob" style="font-size:12.5px; font-weight:700; color:#0f172a;">15 Aug 1995</span>
                            </div>
                            <div style="background:#f8fafc; padding:8px 10px; border-radius:10px;">
                                <span style="font-size:10px; color:#64748b; font-weight:700; display:block;">FATHER'S NAME</span>
                                <span id="prof_modal_father" style="font-size:12.5px; font-weight:700; color:#0f172a;">--</span>
                            </div>
                        </div>
                    </div>

                    <!-- Contact & Address Card -->
                    <div style="background:#ffffff; border-radius:16px; border:1px solid #e2e8f0; padding:14px 16px; margin-bottom:16px;">
                        <div style="font-size:11.5px; font-weight:800; color:#4338ca; text-transform:uppercase; letter-spacing:0.5px; margin-bottom:10px; display:flex; align-items:center; gap:6px;">
                            <i class="fa fa-map-marker"></i> Contact & Address
                        </div>
                        <div style="display:flex; flex-direction:column; gap:8px;">
                            <div style="display:flex; justify-content:space-between; font-size:12.5px; border-bottom:1px solid #f1f5f9; padding-bottom:6px;">
                                <span style="color:#64748b; font-weight:600;"><i class="fa fa-phone"></i> Phone:</span>
                                <span id="prof_modal_phone" style="font-weight:700; color:#0f172a;">--</span>
                            </div>
                            <div style="display:flex; justify-content:space-between; font-size:12.5px; border-bottom:1px solid #f1f5f9; padding-bottom:6px;">
                                <span style="color:#64748b; font-weight:600;"><i class="fa fa-whatsapp" style="color:#16a34a;"></i> WhatsApp:</span>
                                <span id="prof_modal_whatsapp" style="font-weight:700; color:#0f172a;">--</span>
                            </div>
                            <div style="display:flex; justify-content:space-between; font-size:12.5px; border-bottom:1px solid #f1f5f9; padding-bottom:6px;">
                                <span style="color:#64748b; font-weight:600;"><i class="fa fa-envelope"></i> Email:</span>
                                <span id="prof_modal_email" style="font-weight:700; color:#0f172a; max-width:200px; overflow:hidden; text-overflow:ellipsis;">--</span>
                            </div>
                            <div style="font-size:12.5px; padding-top:2px;">
                                <span style="color:#64748b; font-weight:600; display:block; margin-bottom:2px;"><i class="fa fa-home"></i> Address:</span>
                                <span id="prof_modal_address" style="font-weight:600; color:#334155; line-height:1.4;">--</span>
                            </div>
                        </div>
                    </div>

                    <!-- Action Footer Buttons -->
                    <div style="display:flex; gap:10px;">
                        <a id="prof_modal_btn_ledger_full" href="#" class="btn btn-block" style="background:linear-gradient(135deg, #1e1b4b 0%, #4338ca 100%); color:#fff; font-weight:800; border-radius:14px; padding:12px; border:none; margin:0; font-size:13px; display:flex; align-items:center; justify-content:center; gap:6px; text-decoration:none;">
                            <i class="fa fa-calculator"></i> View Dues & Set-Off
                        </a>
                        <button type="button" class="btn btn-default" data-dismiss="modal" style="border-radius:14px; font-weight:700; padding:12px 18px; border:1px solid #e2e8f0; color:#64748b;">
                            Close
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Full Photo Lightbox Modal (Mobile) -->
    <div class="modal fade" id="profilePhotoLightboxModal" tabindex="-1" role="dialog" aria-hidden="true" style="z-index: 10050;">
        <div class="modal-dialog modal-dialog-centered" role="document" style="max-width:420px; margin:20px auto; padding:0 14px;">
            <div class="modal-content" style="background:rgba(15, 23, 42, 0.96); backdrop-filter:blur(16px); -webkit-backdrop-filter:blur(16px); border-radius:24px; border:1px solid rgba(255,255,255,0.15); box-shadow: 0 25px 60px rgba(0,0,0,0.6); overflow:hidden; text-align:center; padding:18px 14px;">
                <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:12px; padding:0 6px;">
                    <div style="display:flex; align-items:center; gap:8px; overflow:hidden;">
                        <span style="width:28px; height:28px; border-radius:8px; background:rgba(255,255,255,0.1); color:#fff; display:inline-flex; align-items:center; justify-content:center; font-size:12px;"><i class="fa fa-user"></i></span>
                        <h4 id="lightbox_member_name" style="color:#ffffff; font-weight:800; font-size:15px; margin:0; overflow:hidden; text-overflow:ellipsis; white-space:nowrap; text-align:left;">Member Photo</h4>
                    </div>
                    <button type="button" class="close" data-dismiss="modal" style="color:#ffffff; opacity:0.85; font-size:26px; text-shadow:none; line-height:1; padding:4px 8px;">&times;</button>
                </div>
                <div style="display:flex; align-items:center; justify-content:center; min-height:260px; max-height:68vh; overflow:hidden; border-radius:18px; background:#020617; border:1px solid rgba(255,255,255,0.08);">
                    <img id="lightbox_img" src="" style="max-width:100%; max-height:68vh; object-fit:contain; border-radius:16px;" onerror="this.onerror=null; this.src='../../img/customer.png';">
                </div>
                <div style="margin-top:14px; display:flex; justify-content:center; gap:10px;">
                    <button type="button" class="btn btn-default" data-dismiss="modal" style="border-radius:12px; font-weight:700; font-size:12.5px; padding:8px 24px; background:rgba(255,255,255,0.12); color:#ffffff; border:1px solid rgba(255,255,255,0.25);">
                        <i class="fa fa-times"></i> Close
                    </button>
                </div>
            </div>
        </div>
    </div>

    <!-- Mobile Add Member Modal -->
    <div class="modal fade" id="addMemberModalMobile" tabindex="-1" role="dialog" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered" role="document" style="max-width:540px;">
            <div class="modal-content" style="border-radius:24px; border:none; box-shadow: 0 25px 50px -12px rgba(0, 0, 0, 0.25);">
                <div class="modal-header" style="background: linear-gradient(135deg, #1e1b4b 0%, #312e81 50%, #4338ca 100%); padding: 18px 24px; color: white; border-top-left-radius:24px; border-top-right-radius:24px;">
                    <button type="button" class="close" data-dismiss="modal" style="color:white; opacity:0.9; font-size:24px;">&times;</button>
                    <h4 class="modal-title" style="font-weight:800; font-size:16px; margin:0;"><i class="fa fa-user-plus"></i> Add Member to Group</h4>
                </div>
                <div class="modal-body" style="padding: 20px; max-height:75vh; overflow-y:auto;">
                    
                    <!-- Group Selection -->
                    <div class="form-field-group">
                        <label>Target Group *</label>
                        <select id="sel_mob_target_group" class="form-control" style="font-weight:700;" onchange="onTargetGroupChanged()">
                            <?php if (!empty($user_groups)): ?>
                                <?php foreach ($user_groups as $g): ?>
                                    <option value="<?php echo $g['id']; ?>"><?php echo htmlspecialchars($g['name']); ?></option>
                                <?php endforeach; ?>
                            <?php else: ?>
                                <option value="0">No assigned groups available</option>
                            <?php endif; ?>
                        </select>
                    </div>

                    <!-- Tab Switcher -->
                    <div style="display:flex; background:#f1f5f9; border-radius:12px; padding:4px; margin-bottom:18px;">
                        <button type="button" id="tab_btn_create" onclick="switchAddMemberTab('create')" style="flex:1; border:none; padding:8px; border-radius:10px; font-size:12.5px; font-weight:800; background:#ffffff; color:#4338ca; box-shadow:0 2px 6px rgba(0,0,0,0.06); transition:all 0.2s;">
                            <i class="fa fa-user-plus"></i> Create New
                        </button>
                        <button type="button" id="tab_btn_existing" onclick="switchAddMemberTab('existing')" style="flex:1; border:none; padding:8px; border-radius:10px; font-size:12.5px; font-weight:700; background:transparent; color:#64748b; transition:all 0.2s;">
                            <i class="fa fa-users"></i> Add Existing
                        </button>
                    </div>

                    <!-- Tab 1: Create New Member (All Details) -->
                    <div id="tab_content_create">
                        
                        <!-- 1. Personal Information -->
                        <div class="form-section-header">
                            <i class="fa fa-user-circle"></i> Personal Information
                        </div>

                        <div class="row" style="margin:0 -5px;">
                            <div class="col-xs-6" style="padding:0 5px;">
                                <div class="form-field-group">
                                    <label>First Name *</label>
                                    <input type="text" id="txt_mob_mem_fn" class="form-control" placeholder="First Name">
                                </div>
                            </div>
                            <div class="col-xs-6" style="padding:0 5px;">
                                <div class="form-field-group">
                                    <label>Middle Name</label>
                                    <input type="text" id="txt_mob_mem_mn" class="form-control" placeholder="Middle Name">
                                </div>
                            </div>
                        </div>

                        <div class="row" style="margin:0 -5px;">
                            <div class="col-xs-6" style="padding:0 5px;">
                                <div class="form-field-group">
                                    <label>Last Name *</label>
                                    <input type="text" id="txt_mob_mem_ln" class="form-control" placeholder="Last Name">
                                </div>
                            </div>
                            <div class="col-xs-6" style="padding:0 5px;">
                                <div class="form-field-group">
                                    <label>Gender</label>
                                    <select id="sel_mob_mem_gender" class="form-control">
                                        <option value="Male">Male</option>
                                        <option value="Female">Female</option>
                                        <option value="Other">Other</option>
                                    </select>
                                </div>
                            </div>
                        </div>

                        <div class="row" style="margin:0 -5px;">
                            <div class="col-xs-6" style="padding:0 5px;">
                                <div class="form-field-group">
                                    <label>Father's Name</label>
                                    <input type="text" id="txt_mob_mem_father" class="form-control" placeholder="Father's Name">
                                </div>
                            </div>
                            <div class="col-xs-6" style="padding:0 5px;">
                                <div class="form-field-group">
                                    <label>Mother's Name</label>
                                    <input type="text" id="txt_mob_mem_mother" class="form-control" placeholder="Mother's Name">
                                </div>
                            </div>
                        </div>

                        <div class="row" style="margin:0 -5px;">
                            <div class="col-xs-6" style="padding:0 5px;">
                                <div class="form-field-group">
                                    <label>Date of Birth</label>
                                    <input type="date" id="txt_mob_mem_dob" class="form-control">
                                </div>
                            </div>
                            <div class="col-xs-6" style="padding:0 5px;">
                                <div class="form-field-group">
                                    <label>Blood Group</label>
                                    <select id="sel_mob_mem_bg" class="form-control">
                                        <option value="0">Select Blood Group</option>
                                        <?php foreach ($blood_groups as $bg): ?>
                                            <option value="<?php echo $bg['id']; ?>"><?php echo htmlspecialchars($bg['name']); ?></option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                            </div>
                        </div>

                        <!-- 2. Contact Details -->
                        <div class="form-section-header">
                            <i class="fa fa-phone"></i> Contact Details
                        </div>

                        <div class="row" style="margin:0 -5px;">
                            <div class="col-xs-6" style="padding:0 5px;">
                                <div class="form-field-group">
                                    <label>Phone *</label>
                                    <input type="tel" id="txt_mob_mem_phone" class="form-control" placeholder="Phone Number">
                                </div>
                            </div>
                            <div class="col-xs-6" style="padding:0 5px;">
                                <div class="form-field-group">
                                    <label>WhatsApp</label>
                                    <input type="tel" id="txt_mob_mem_whtsapp" class="form-control" placeholder="WhatsApp">
                                </div>
                            </div>
                        </div>

                        <div class="form-field-group">
                            <label>Email Address</label>
                            <input type="email" id="txt_mob_mem_email" class="form-control" placeholder="Email Address">
                        </div>

                        <!-- 3. Permanent Address -->
                        <div class="form-section-header">
                            <i class="fa fa-map-marker"></i> Permanent Address
                        </div>

                        <div class="row" style="margin:0 -5px;">
                            <div class="col-xs-6" style="padding:0 5px;">
                                <div class="form-field-group">
                                    <label>Street</label>
                                    <input type="text" id="txt_mob_mem_street" class="form-control" placeholder="Street Address">
                                </div>
                            </div>
                            <div class="col-xs-6" style="padding:0 5px;">
                                <div class="form-field-group">
                                    <label>City</label>
                                    <input type="text" id="txt_mob_mem_city" class="form-control" placeholder="City">
                                </div>
                            </div>
                        </div>

                        <div class="row" style="margin:0 -5px;">
                            <div class="col-xs-6" style="padding:0 5px;">
                                <div class="form-field-group">
                                    <label>Pincode</label>
                                    <input type="number" id="txt_mob_mem_pincode" class="form-control" placeholder="Pincode">
                                </div>
                            </div>
                            <div class="col-xs-6" style="padding:0 5px;">
                                <div class="form-field-group">
                                    <label>Country</label>
                                    <input type="text" id="txt_mob_mem_country" class="form-control" placeholder="Country" value="India">
                                </div>
                            </div>
                        </div>

                        <!-- 4. Status & Photo -->
                        <div class="form-section-header">
                            <i class="fa fa-sliders"></i> Status & Photo
                        </div>

                        <div class="row" style="margin:0 -5px;">
                            <div class="col-xs-6" style="padding:0 5px;">
                                <div class="form-field-group">
                                    <label>Member Type</label>
                                    <select id="sel_mob_mem_type" class="form-control">
                                        <option value="0">Regular Member</option>
                                        <option value="1">Guest Member</option>
                                    </select>
                                </div>
                            </div>
                            <div class="col-xs-6" style="padding:0 5px;">
                                <div class="form-field-group">
                                    <label>Status</label>
                                    <div style="padding-top:10px;">
                                        <label style="font-weight:600; font-size:13px; color:#475569; cursor:pointer; text-transform:none;">
                                            <input type="checkbox" id="chk_mob_mem_inactive" style="margin-right:6px; accent-color:#4338ca;"> Mark as Inactive
                                        </label>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Photo Upload & Preview -->
                        <div style="display:flex; align-items:center; gap:14px; margin-top:8px; margin-bottom:14px; background:#f8fafc; padding:12px; border-radius:14px; border:1px solid #e2e8f0;">
                            <img id="mob_photo_preview" src="../../img/customer.png" style="width:58px; height:58px; border-radius:50%; object-fit:cover; border:2px solid #cbd5e1; flex-shrink:0;">
                            <div style="flex:1;">
                                <label style="font-size:11px; font-weight:800; color:#64748b; text-transform:uppercase; margin-bottom:4px; display:block;">Member Photo</label>
                                <input type="file" id="mobPhotoInput" accept="image/*" onchange="uploadMobileMemberPhoto(event)" style="font-size:12px; color:#475569; width:100%;">
                                <input type="hidden" id="hdn_file_upload" value="0">
                                <small id="mob_photo_status" style="font-size:11px; color:#10b981; font-weight:600; display:none; margin-top:4px;"><i class="fa fa-check"></i> Photo uploaded</small>
                            </div>
                        </div>

                        <button type="button" onclick="submitCreateNewMemberMobile()" class="btn btn-block" style="background: linear-gradient(135deg, #1e1b4b 0%, #4338ca 100%); color:white; font-weight:800; height:46px; border-radius:14px; border:none; margin-top:18px; box-shadow:0 4px 12px rgba(67,56,202,0.3);">
                            <i class="fa fa-check-circle"></i> Save & Add to Group
                        </button>
                    </div>

                    <!-- Tab 2: Add Existing Member -->
                    <div id="tab_content_existing" style="display:none;">
                        <div style="position:relative; margin-bottom:14px;">
                            <i class="fa fa-search" style="position:absolute; left:14px; top:50%; transform:translateY(-50%); color:#94a3b8;"></i>
                            <input type="text" id="txt_existing_search" class="form-control" placeholder="Search members by name or phone..." onkeyup="searchExistingMembers()" style="border-radius:12px; height:42px; padding-left:38px; font-weight:600;">
                        </div>
                        <div id="existing_members_results" style="max-height:260px; overflow-y:auto; padding-right:2px;">
                            <div style="text-align:center; padding:30px 10px; color:#94a3b8; font-size:13px;">
                                <i class="fa fa-search" style="font-size:24px; margin-bottom:8px; display:block;"></i>
                                Type a name or phone to search existing members
                            </div>
                        </div>
                    </div>

                </div>
            </div>
        </div>
    </div>

    <!-- Mobile Bottom Navigation (5 Tabs) -->
    <?php include 'mobile_bottom_nav.php'; ?>

    <script src="../../js/jquery-3.1.1.min.js"></script>
    <script src="../../js/bootstrap.min.js"></script>
    <script src="../../js/plugins/sweetalert/sweetalert.min.js"></script>
    <script src="../../app_js/sweetalert-finez.js"></script>
    <script>
        // Disable Bootstrap modal enforceFocus to allow SweetAlert on top
        if ($.fn.modal && $.fn.modal.Constructor) {
            $.fn.modal.Constructor.prototype.enforceFocus = function () {};
        }

        let searchTimer = null;

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

        function openMemberProfile(triggerEl) {
            let raw = $(triggerEl).attr('data-profile');
            let p = {};
            try {
                p = typeof raw === 'string' ? JSON.parse(raw) : (raw || {});
            } catch(e) {
                console.error("Profile parse error", e);
                return;
            }

            $('#prof_modal_img').attr('src', p.img || '../../img/customer.png');
            $('#prof_modal_name').text(p.name || 'Member Profile');
            $('#prof_modal_group').html('<i class="fa fa-users"></i> ' + (p.group_names || 'General Member'));

            if (p.img && p.img.indexOf('customer.png') === -1) {
                $('#prof_modal_zoom_hint').show();
            } else {
                $('#prof_modal_zoom_hint').hide();
            }

            if (p.is_guest) {
                $('#prof_modal_type').text('Guest Member').css({ background:'#fff7ed', color:'#c2410c', borderColor:'#fed7aa' });
                $('#prof_modal_badge_dot').css('background', '#f59e0b');
            } else {
                $('#prof_modal_type').text('Regular Member').css({ background:'#ecfdf5', color:'#059669', borderColor:'#a7f3d0' });
                $('#prof_modal_badge_dot').css('background', '#10b981');
            }

            // Quick communication links
            if (p.phone && p.phone !== '0') {
                $('#prof_modal_btn_call').attr('href', 'tel:' + p.phone).show();
                $('#prof_modal_phone').html('<a href="tel:' + p.phone + '" style="color:#0f172a; text-decoration:none;">' + p.phone + '</a>');
            } else {
                $('#prof_modal_btn_call').hide();
                $('#prof_modal_phone').html('<span style="color:#94a3b8;">Not Provided</span>');
            }

            if (p.whtsapp && p.whtsapp !== '0') {
                let waDigits = p.whtsapp.replace(/[^0-9]/g, '');
                if (waDigits.length === 10) waDigits = '91' + waDigits;
                $('#prof_modal_btn_wa').attr('href', 'https://wa.me/' + waDigits).show();
                $('#prof_modal_whatsapp').html('<a href="https://wa.me/' + waDigits + '" target="_blank" style="color:#16a34a; text-decoration:none; font-weight:700;">' + p.whtsapp + '</a>');
            } else if (p.phone && p.phone !== '0') {
                let waDigits = p.phone.replace(/[^0-9]/g, '');
                if (waDigits.length === 10) waDigits = '91' + waDigits;
                $('#prof_modal_btn_wa').attr('href', 'https://wa.me/' + waDigits).show();
                $('#prof_modal_whatsapp').html('<a href="https://wa.me/' + waDigits + '" target="_blank" style="color:#16a34a; text-decoration:none; font-weight:700;">' + p.phone + '</a>');
            } else {
                $('#prof_modal_btn_wa').hide();
                $('#prof_modal_whatsapp').html('<span style="color:#94a3b8;">Not Provided</span>');
            }

            if (p.email && p.email.trim() !== '' && p.email !== '0') {
                $('#prof_modal_btn_email').attr('href', 'mailto:' + p.email).show();
                $('#prof_modal_email').html('<a href="mailto:' + p.email + '" style="color:#2563eb; text-decoration:none;">' + p.email + '</a>');
            } else {
                $('#prof_modal_btn_email').hide();
                $('#prof_modal_email').html('<span style="color:#94a3b8;">Not Provided</span>');
            }

            // Ledger links
            let duesUrl = 'member_receivable.php?member_id=' + p.id;
            $('#prof_modal_btn_fees').attr('href', duesUrl);
            $('#prof_modal_btn_ledger_full').attr('href', duesUrl);

            // Personal details
            if (p.blood_group && p.blood_group !== 'Not Specified' && p.blood_group !== '0') {
                $('#prof_modal_blood').html('<i class="fa fa-tint" style="color:#dc2626;"></i> ' + p.blood_group).css('color', '#dc2626');
            } else {
                $('#prof_modal_blood').text('--').css('color', '#94a3b8');
            }
            $('#prof_modal_gender').text((p.gender && p.gender !== 'Not Specified' && p.gender !== '0') ? p.gender : '--');
            $('#prof_modal_dob').text((p.dob && p.dob !== 'Not Specified') ? p.dob : '--');
            $('#prof_modal_father').text((p.father_name && p.father_name !== 'Not Specified' && p.father_name !== '0') ? p.father_name : '--');
            $('#prof_modal_address').html((p.address && p.address !== 'Not Specified' && p.address !== 'Not Provided') ? p.address : '<span style="color:#94a3b8;">Not Provided</span>');

            $('#memberProfileModal').modal('show');
        }

        function viewFullProfilePhoto() {
            let src = $('#prof_modal_img').attr('src');
            let name = $('#prof_modal_name').text();
            if (!src || src.indexOf('customer.png') !== -1) return;
            $('#lightbox_img').attr('src', src);
            $('#lightbox_member_name').text(name || 'Member Photo');
            $('#profilePhotoLightboxModal').modal('show');
        }

        function toggleGuestStatus(memberId, currentStatus, memberName) {
            let newStatus = currentStatus === 1 ? 0 : 1;
            let actionName = newStatus === 1 ? "mark as Guest Member" : "convert to Regular Member";
            let titleName = newStatus === 1 ? "Make Guest Member?" : "Make Regular Member?";

            swal({
                title: titleName,
                text: "Are you sure you want to " + actionName + (memberName ? " for " + memberName : "") + "?",
                type: "warning",
                showCancelButton: true,
                confirmButtonColor: newStatus === 1 ? "#f59e0b" : "#4338ca",
                confirmButtonText: "Yes, Convert",
                cancelButtonText: "Cancel",
                closeOnConfirm: false,
                showLoaderOnConfirm: true
            }, function(isConfirm) {
                if (isConfirm) {
                    $.post('../api/members.php', { action: 'toggle_guest_status', id: memberId, member_type: newStatus }, function(res) {
                        swal({
                            title: "Updated!",
                            text: "Member status updated successfully.",
                            type: "success",
                            confirmButtonColor: "#4338ca"
                        }, function() {
                            location.reload();
                        });
                    }).fail(function() {
                        swal({
                            title: "Error!",
                            text: "Failed to update member status.",
                            type: "error",
                            confirmButtonColor: "#4338ca"
                        });
                    });
                }
            });
        }

        function removeMemberFromGroup(memberId, memberName) {
            let groupId = $('#sel_mob_target_group').val() || 0;
            swal({
                title: "Remove from Group?",
                text: "Are you sure you want to remove " + (memberName ? memberName : "this member") + " from the group?",
                type: "warning",
                showCancelButton: true,
                confirmButtonColor: "#ef4444",
                confirmButtonText: "Yes, Remove",
                cancelButtonText: "Cancel",
                closeOnConfirm: false,
                showLoaderOnConfirm: true
            }, function(isConfirm) {
                if (isConfirm) {
                    $.post('../api/group_members.php', { action: 'remove_member', id: memberId, group_id: groupId }, function(res) {
                        swal({
                            title: "Removed!",
                            text: "Member has been removed from the group.",
                            type: "success",
                            confirmButtonColor: "#4338ca"
                        }, function() {
                            location.reload();
                        });
                    }, 'json').fail(function(xhr) {
                        let msg = "Failed to remove member.";
                        try {
                            let res = JSON.parse(xhr.responseText);
                            if (res.Message) msg = res.Message;
                        } catch(e){}
                        swal({
                            title: "Error!",
                            text: msg,
                            type: "error",
                            confirmButtonColor: "#4338ca"
                        });
                    });
                }
            });
        }

        function openAddMemberModalMobile() {
            // Reset all input fields
            $('#txt_mob_mem_fn').val('');
            $('#txt_mob_mem_mn').val('');
            $('#txt_mob_mem_ln').val('');
            $('#sel_mob_mem_gender').val('Male');
            $('#txt_mob_mem_father').val('');
            $('#txt_mob_mem_mother').val('');
            $('#txt_mob_mem_dob').val('');
            $('#sel_mob_mem_bg').val('0');
            $('#txt_mob_mem_phone').val('');
            $('#txt_mob_mem_whtsapp').val('');
            $('#txt_mob_mem_email').val('');
            $('#txt_mob_mem_street').val('');
            $('#txt_mob_mem_city').val('');
            $('#txt_mob_mem_pincode').val('');
            $('#txt_mob_mem_country').val('India');
            $('#sel_mob_mem_type').val('0');
            $('#chk_mob_mem_inactive').prop('checked', false);
            $('#hdn_file_upload').val('0');
            $('#mobPhotoInput').val('');
            $('#mob_photo_preview').attr('src', '../../img/customer.png');
            $('#mob_photo_status').hide();
            $('#txt_existing_search').val('');
            
            switchAddMemberTab('create');
            $('#addMemberModalMobile').modal('show');
        }

        function switchAddMemberTab(tabName) {
            if (tabName === 'create') {
                $('#tab_content_create').show();
                $('#tab_content_existing').hide();
                $('#tab_btn_create').css({ background: '#ffffff', color: '#4338ca', fontWeight: '800', boxShadow: '0 2px 6px rgba(0,0,0,0.06)' });
                $('#tab_btn_existing').css({ background: 'transparent', color: '#64748b', fontWeight: '700', boxShadow: 'none' });
            } else {
                $('#tab_content_create').hide();
                $('#tab_content_existing').show();
                $('#tab_btn_existing').css({ background: '#ffffff', color: '#4338ca', fontWeight: '800', boxShadow: '0 2px 6px rgba(0,0,0,0.06)' });
                $('#tab_btn_create').css({ background: 'transparent', color: '#64748b', fontWeight: '700', boxShadow: 'none' });
                searchExistingMembers();
            }
        }

        function onTargetGroupChanged() {
            if ($('#tab_content_existing').is(':visible')) {
                searchExistingMembers();
            }
        }

        function uploadMobileMemberPhoto(event) {
            const file = event.target.files[0];
            if (!file) return;

            // Instant preview
            const reader = new FileReader();
            reader.onload = (e) => {
                $('#mob_photo_preview').attr('src', e.target.result);
            };
            reader.readAsDataURL(file);

            // Upload
            const formData = new FormData();
            formData.append('croppedImage', file, 'member_photo.jpg');

            $('#mob_photo_status').show().html('<i class="fa fa-spinner fa-spin"></i> Uploading photo...').css('color', '#4338ca');

            fetch('../../image_upload/members/upload.php', {
                method: 'POST',
                body: formData
            })
            .then(r => r.json())
            .then(data => {
                if (data.status === 'success' || data.filename) {
                    $('#hdn_file_upload').val(data.filename);
                    $('#mob_photo_status').html('<i class="fa fa-check"></i> Photo uploaded successfully').css('color', '#10b981');
                } else {
                    $('#mob_photo_status').html('<i class="fa fa-exclamation-circle"></i> Upload failed: ' + (data.message || 'Error')).css('color', '#ef4444');
                }
            })
            .catch(err => {
                $('#mob_photo_status').html('<i class="fa fa-exclamation-circle"></i> Upload error').css('color', '#ef4444');
            });
        }

        function submitCreateNewMemberMobile() {
            let groupId = $('#sel_mob_target_group').val();
            let groupName = $('#sel_mob_target_group option:selected').text();
            let fn = $('#txt_mob_mem_fn').val() || '';
            let ln = $('#txt_mob_mem_ln').val() || '';
            let phone = $('#txt_mob_mem_phone').val() || '';

            if (!groupId || groupId === '0') {
                swal({
                    title: "Missing Group",
                    text: "Please select a target group.",
                    type: "warning",
                    confirmButtonColor: "#4338ca"
                });
                return;
            }
            if (!fn.trim()) {
                swal({
                    title: "Missing First Name",
                    text: "Please enter member's first name.",
                    type: "warning",
                    confirmButtonColor: "#4338ca"
                });
                return;
            }
            if (!ln.trim()) {
                swal({
                    title: "Missing Last Name",
                    text: "Please enter member's last name.",
                    type: "warning",
                    confirmButtonColor: "#4338ca"
                });
                return;
            }
            if (!phone.trim()) {
                swal({
                    title: "Missing Phone Number",
                    text: "Please enter member's phone number.",
                    type: "warning",
                    confirmButtonColor: "#4338ca"
                });
                return;
            }

            let memberName = (fn + ' ' + ln).trim();

            let postData = {
                action: 'create_and_add_member',
                group_id: groupId,
                first_name: fn.trim(),
                middle_name: ($('#txt_mob_mem_mn').val() || '').trim(),
                last_name: ln.trim(),
                gender: $('#sel_mob_mem_gender').val(),
                father_name: ($('#txt_mob_mem_father').val() || '').trim(),
                mother_name: ($('#txt_mob_mem_mother').val() || '').trim(),
                dob: $('#txt_mob_mem_dob').val(),
                blood_group: $('#sel_mob_mem_bg').val() || 0,
                phone: phone.trim(),
                whtsapp: ($('#txt_mob_mem_whtsapp').val() || phone).trim(),
                email: ($('#txt_mob_mem_email').val() || '').trim(),
                p_street: ($('#txt_mob_mem_street').val() || '').trim(),
                p_city: ($('#txt_mob_mem_city').val() || '').trim(),
                p_pincode: ($('#txt_mob_mem_pincode').val() || '').trim(),
                p_country: ($('#txt_mob_mem_country').val() || '').trim(),
                img: $('#hdn_file_upload').val() || '0',
                inactive: $('#chk_mob_mem_inactive').is(':checked') ? 1 : 0,
                member_type: $('#sel_mob_mem_type').val()
            };

            swal({
                title: "Save New Member?",
                text: "Add " + memberName + " to " + groupName + "?",
                type: "info",
                showCancelButton: true,
                confirmButtonColor: "#4338ca",
                confirmButtonText: "Yes, Save",
                cancelButtonText: "Cancel",
                closeOnConfirm: false,
                showLoaderOnConfirm: true
            }, function(isConfirm) {
                if (isConfirm) {
                    $.post('../api/group_members.php', postData, function(res) {
                        if (res.success) {
                            $('#addMemberModalMobile').modal('hide');
                            swal({
                                title: "Member Added!",
                                text: memberName + " has been successfully created and added to " + groupName + ".",
                                type: "success",
                                confirmButtonColor: "#4338ca",
                                confirmButtonText: "OK"
                            }, function() {
                                location.reload();
                            });
                        } else {
                            swal({
                                title: "Failed",
                                text: res.Message || "Error creating member.",
                                type: "error",
                                confirmButtonColor: "#4338ca"
                            });
                        }
                    }, 'json').fail(function(xhr) {
                        let msg = "Error creating member.";
                        try {
                            let res = JSON.parse(xhr.responseText);
                            if (res.Message) msg = res.Message;
                        } catch(e){}
                        swal({
                            title: "Error!",
                            text: msg,
                            type: "error",
                            confirmButtonColor: "#4338ca"
                        });
                    });
                }
            });
        }

        function searchExistingMembers() {
            clearTimeout(searchTimer);
            searchTimer = setTimeout(function() {
                let groupId = $('#sel_mob_target_group').val();
                let q = ($('#txt_existing_search').val() || '').trim();

                if (!groupId || groupId === '0') {
                    $('#existing_members_results').html('<div style="text-align:center; padding:20px; color:#94a3b8;">No group selected.</div>');
                    return;
                }

                $('#existing_members_results').html('<div style="text-align:center; padding:20px; color:#94a3b8;"><i class="fa fa-spinner fa-spin"></i> Searching members...</div>');

                $.post('../api/group_members.php', {
                    action: 'search_existing_members',
                    group_id: groupId,
                    query: q
                }, function(res) {
                    if (res.success && res.data && res.data.length > 0) {
                        let html = '';
                        res.data.forEach(function(m) {
                            let fullName = (m.first_name + ' ' + (m.middle_name || '') + ' ' + m.last_name).replace(/\s+/g, ' ').trim();
                            let safeName = fullName.replace(/'/g, "\\'");
                            let avatar = (m.img && m.img !== '0') ? '../../image_upload/members/uploads/' + m.img : '../../img/customer.png';
                            let subInfo = m.phone ? m.phone : (m.email ? m.email : 'No contact details');

                            html += `
                                <div class="search-item-row">
                                    <div style="display:flex; align-items:center; gap:10px; overflow:hidden;">
                                        <img src="${avatar}" style="width:36px; height:36px; border-radius:10px; object-fit:cover;" onerror="this.src='../../img/customer.png';">
                                        <div style="overflow:hidden;">
                                            <div style="font-size:13px; font-weight:800; color:#0f172a; white-space:nowrap; overflow:hidden; text-overflow:ellipsis;">${fullName}</div>
                                            <div style="font-size:11px; font-weight:600; color:#64748b;">${subInfo}</div>
                                        </div>
                                    </div>
                                    <button type="button" onclick="addExistingMemberToGroup(${m.id}, '${safeName}')" class="btn btn-xs" style="background:#eff6ff; color:#2563eb; font-weight:800; border-radius:8px; padding:6px 12px; border:none; flex-shrink:0;">
                                        <i class="fa fa-plus"></i> Add
                                    </button>
                                </div>
                            `;
                        });
                        $('#existing_members_results').html(html);
                    } else {
                        $('#existing_members_results').html('<div style="text-align:center; padding:30px 10px; color:#94a3b8; font-size:13px;">No unassigned members found.</div>');
                    }
                }, 'json').fail(function() {
                    $('#existing_members_results').html('<div style="text-align:center; padding:20px; color:#ef4444;">Error fetching members.</div>');
                });
            }, 250);
        }

        function addExistingMemberToGroup(memberId, memberName) {
            let groupId = $('#sel_mob_target_group').val();
            let groupName = $('#sel_mob_target_group option:selected').text();

            if (!groupId || groupId === '0') {
                swal({
                    title: "Missing Group",
                    text: "Please select a target group.",
                    type: "warning",
                    confirmButtonColor: "#4338ca"
                });
                return;
            }

            swal({
                title: "Add to Group?",
                text: "Add " + (memberName || "this member") + " to " + groupName + "?",
                type: "info",
                showCancelButton: true,
                confirmButtonColor: "#4338ca",
                confirmButtonText: "Yes, Add",
                cancelButtonText: "Cancel",
                closeOnConfirm: false,
                showLoaderOnConfirm: true
            }, function(isConfirm) {
                if (isConfirm) {
                    $.post('../api/group_members.php', {
                        action: 'add_existing_member_to_group',
                        group_id: groupId,
                        member_id: memberId
                    }, function(res) {
                        if (res.success) {
                            swal({
                                title: "Member Added!",
                                text: (memberName || "Member") + " has been added to " + groupName + " successfully.",
                                type: "success",
                                confirmButtonColor: "#4338ca",
                                confirmButtonText: "Done"
                            }, function() {
                                $('#addMemberModalMobile').modal('hide');
                                location.reload();
                            });
                        } else {
                            swal({
                                title: "Notice",
                                text: res.Message || "Error adding member.",
                                type: "warning",
                                confirmButtonColor: "#4338ca"
                            });
                        }
                    }, 'json').fail(function(xhr) {
                        let msg = "Error adding member.";
                        try {
                            let res = JSON.parse(xhr.responseText);
                            if (res.Message) msg = res.Message;
                        } catch(e){}
                        swal({
                            title: "Error!",
                            text: msg,
                            type: "error",
                            confirmButtonColor: "#4338ca"
                        });
                    });
                }
            });
        }
    </script>
</body>
</html>
