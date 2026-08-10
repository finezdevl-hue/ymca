<?php
session_start();
include_once '../app_common/db_connect.php';
include_once '../app_common/auth_helper.php';

$login_id = (int)($_SESSION['login_id'] ?? 0);
if (empty($login_id)) {
    header("Location: ../app_login_manager/logout.php");
    exit();
}
if (isNormalMember($login_id)) {
    header("Location: member_dashboard.php");
    exit();
}
session_write_close();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Guest Members | YMCA Admin</title>

    <link href="../css/bootstrap.min.css" rel="stylesheet">
    <link href="../font-awesome/css/font-awesome.css" rel="stylesheet">
    <link href="../css/animate.css" rel="stylesheet">
    <link href="../css/style.css" rel="stylesheet">
    <link href="../css/plugins/sweetalert/sweetalert.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">

    <script src="../js/jquery-3.1.1.min.js"></script>
    <script src="../js/plugins/sweetalert/sweetalert.min.js"></script>
    <script src="../app_js/sweetalert-finez.js"></script>

    <style>
        *, *::before, *::after { box-sizing: border-box; }
        body, #wrapper { font-family: 'Inter','Segoe UI',sans-serif !important; background: #f8fafc !important; }

        .mem-topbar {
            background: #fff;
            border-bottom: 1px solid #e8edf5;
            padding: 0 28px;
            height: 62px;
            display: flex; align-items: center; justify-content: space-between;
            box-shadow: 0 1px 6px rgba(15,23,42,0.06);
            position: sticky; top: 0; z-index: 100;
        }
        .mem-topbar-left { display: flex; align-items: center; gap: 14px; }
        .mem-hamburger {
            width: 38px; height: 38px;
            background: linear-gradient(135deg, #f59e0b, #d97706);
            border-radius: 10px; color: #fff !important;
            display: flex; align-items: center; justify-content: center;
            font-size: 16px; text-decoration: none !important;
        }
        .mem-topbar-title { font-size: 18px; font-weight: 800; color: #0f172a; }
        .mem-topbar-title span { font-size: 12px; font-weight: 600; color: #d97706; margin-left: 6px; }

        .mem-control-bar {
            background: #fff;
            border-bottom: 1px solid #e8edf5;
            padding: 16px 28px;
            display: flex; align-items: center; gap: 16px; flex-wrap: wrap;
        }
        .mem-page-title { font-size: 20px; font-weight: 800; color: #0f172a; margin: 0; display: flex; align-items: center; gap: 10px; }
        .mem-page-title i { color: #f59e0b; }

        .mem-search-wrap {
            position: relative; flex: 1; min-width: 240px; max-width: 440px;
        }
        .mem-search-wrap input {
            width: 100%; height: 42px; padding: 0 14px 0 40px;
            border-radius: 12px; border: 1.5px solid #cbd5e1;
            font-size: 13.5px; font-weight: 600; color: #0f172a; outline: none;
            transition: all 0.2s;
        }
        .mem-search-wrap input:focus { border-color: #f59e0b; box-shadow: 0 0 0 4px rgba(245,158,11,0.12); }
        .mem-search-icon { position: absolute; left: 14px; top: 50%; transform: translateY(-50%); color: #94a3b8; }
        .mem-search-btn {
            position: absolute; right: 4px; top: 4px; bottom: 4px;
            padding: 0 16px; border-radius: 9px;
            background: linear-gradient(135deg, #f59e0b, #d97706);
            color: #fff; border: none; font-size: 12.5px; font-weight: 700; cursor: pointer;
        }

        .mem-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(320px, 1fr));
            gap: 18px;
            padding: 24px 28px;
        }

        .guest-card {
            background: #fff;
            border-radius: 18px;
            border: 1px solid #e2e8f0;
            padding: 18px;
            box-shadow: 0 4px 14px rgba(0,0,0,0.03);
            display: flex;
            flex-direction: column;
            justify-content: space-between;
            transition: transform 0.2s, box-shadow 0.2s;
        }

        .guest-card:hover {
            transform: translateY(-2px);
            box-shadow: 0 10px 25px rgba(245,158,11,0.1);
        }

        .guest-avatar {
            width: 52px; height: 52px; border-radius: 14px; object-fit: cover; background: #f1f5f9; flex-shrink: 0;
        }

        .pill-amber {
            background: linear-gradient(135deg, #fff7ed 0%, #ffedd5 100%);
            color: #c2410c;
            border: 1px solid rgba(249, 115, 22, 0.3);
            box-shadow: 0 2px 6px rgba(249, 115, 22, 0.12);
            font-size: 10px;
            font-weight: 800;
            padding: 3px 10px 3px 8px;
            border-radius: 20px;
            letter-spacing: 0.6px;
            text-transform: uppercase;
            display: inline-flex;
            align-items: center;
            gap: 4px;
        }

        .btn-act {
            padding: 8px 12px; border-radius: 10px; font-size: 12px; font-weight: 700; border: none; cursor: pointer;
            display: inline-flex; align-items: center; justify-content: center; gap: 6px; text-decoration: none !important;
            transition: all 0.18s;
        }

        .btn-act-primary { background: #f59e0b; color: #fff; }
        .btn-act-primary:hover { background: #d97706; color: #fff; }
        .btn-act-secondary { background: #eff6ff; color: #2563eb; }
        .btn-act-secondary:hover { background: #2563eb; color: #fff; }
        .btn-act-danger { background: #fef2f2; color: #dc2626; }
        .btn-act-danger:hover { background: #dc2626; color: #fff; }
    </style>
</head>

<body>
<div id="wrapper">

    <!-- Sidebar -->
    <nav class="navbar-default navbar-static-side" role="navigation">
        <div class="dropdown profile-element">
            <center>
                <span><img alt="image" class="img-circle" src="../img/customer.png" style="padding-top:20px;"/></span>
                <span class="clear"><span class="block m-t-xs"><strong class="font-bold"><?php echo $_SESSION['name']; ?></strong></span></span>
            </center>
        </div>
        <div class="sidebar-collapse" id="divMenuContainer"></div>
    </nav>

    <!-- Main -->
    <div id="page-wrapper" style="background:#f8fafc; padding:0; min-height:100vh;">

        <!-- Top Navbar -->
        <div class="mem-topbar">
            <div class="mem-topbar-left">
                <a class="navbar-minimalize minimalize-styl-2 mem-hamburger" href="#"><i class="fa fa-bars"></i></a>
                <span class="mem-topbar-title">YMCA <span>Guest Members Portal</span></span>
            </div>
            <a href="../app_login_manager/logout.php" style="color:#64748b; font-weight:700; text-decoration:none;">
                <i class="fa fa-sign-out"></i> Log out
            </a>
        </div>

        <!-- Control Bar -->
        <div class="mem-control-bar">
            <h1 class="mem-page-title">
                <i class="fa fa-user-circle"></i> Guest Members
            </h1>
            <div style="display:flex; align-items:center; gap:10px; flex-wrap:wrap;">
                <div class="mem-search-wrap">
                    <span class="mem-search-icon"><i class="fa fa-search"></i></span>
                    <input type="text" id="txt_guest_search" placeholder="Search guests by name or phone…">
                    <button class="mem-search-btn" onclick="loadGuests()">Search</button>
                </div>
                <button type="button" class="btn btn-warning" onclick="openAddGuestModal()" style="border-radius:12px; font-weight:800; padding:9px 16px; background: linear-gradient(135deg, #f59e0b, #d97706); border:none; color:#fff; display:flex; align-items:center; gap:6px;">
                    <i class="fa fa-user-plus"></i> Add Guest
                </button>
            </div>
            <span class="badge" id="guest-count-badge" style="background:#fff7ed; color:#c2410c; font-size:13px; padding:8px 14px; border-radius:10px; font-weight:800;">
                <i class="fa fa-user-circle"></i> Loading…
            </span>
        </div>

        <!-- Guests Grid -->
        <div id="guests_grid_container">
            <div style="text-align:center; padding:60px 20px; color:#94a3b8;">
                <i class="fa fa-spinner fa-spin" style="font-size:32px; margin-bottom:12px; display:block; color:#f59e0b;"></i>
                Loading guest members…
            </div>
        </div>

    </div>
</div>

<script src="../js/bootstrap.min.js"></script>
<script src="../js/plugins/metisMenu/jquery.metisMenu.js"></script>
<script src="../js/plugins/slimscroll/jquery.slimscroll.min.js"></script>
<script src="../js/inspinia.js"></script>
<script src="../app_menu/menu.js"></script>
<script>
$(document).ready(function() {
    loadMenu();
    loadGuests();
});

$(document).on('keydown','#txt_guest_search',function(e){
    if(e.key==='Enter'){ e.preventDefault(); loadGuests(); }
});

function loadGuests() {
    let searchVal = $('#txt_guest_search').val() || '';
    $('#guests_grid_container').html('<div style="text-align:center; padding:60px 20px; color:#94a3b8;"><i class="fa fa-spinner fa-spin" style="font-size:32px; margin-bottom:12px; display:block; color:#f59e0b;"></i>Loading guest members…</div>');

    $.post('api/members.php', { action: 'load_members_data', member_type: 1, page: 1, val: searchVal }, function(res) {
        try {
            let obj = typeof res === 'string' ? JSON.parse(res) : res;
            let guests = obj[1] || [];
            $('#guest-count-badge').html('<i class="fa fa-user-circle"></i> ' + guests.length + ' Guest Members');

            if (guests.length === 0) {
                $('#guests_grid_container').html(`
                    <div style="text-align:center; padding:70px 20px; color:#94a3b8;">
                        <i class="fa fa-user-times" style="font-size:48px; margin-bottom:14px; display:block; color:#cbd5e1;"></i>
                        <h4 style="font-weight:700; color:#64748b;">No Guest Members Found</h4>
                        <p style="font-size:13px; margin-top:4px;">Members flagged as guests using the "Make Guest" toggle will appear here.</p>
                    </div>
                `);
                return;
            }

            let htm = '<div class="mem-grid">';
            guests.forEach(function(g) {
                let fullName = [g.first_name, g.middle_name, g.last_name].filter(Boolean).join(' ');
                let imgSrc = (g.img && g.img != '0') ? '../image_upload/members/thumbnails/' + g.img : '../img/customer.png';
                let phone = (g.phone && g.phone != '0') ? g.phone : 'N/A';
                htm += `
                    <div class="guest-card">
                        <div>
                            <div style="display:flex; align-items:center; justify-content:space-between; margin-bottom:12px;">
                                <div style="display:flex; align-items:center; gap:12px;">
                                    <img src="${imgSrc}" class="guest-avatar" onerror="this.src='../img/customer.png';">
                                    <div>
                                        <h4 style="font-size:15px; font-weight:800; color:#0f172a; margin:0 0 2px;">${fullName}</h4>
                                        <div style="font-size:12px; color:#64748b; font-weight:600;"><i class="fa fa-phone"></i> ${phone}</div>
                                    </div>
                                </div>
                                <span class="pill-amber"><i class="fa fa-star" style="color:#f59e0b; font-size:9px;"></i> Guest</span>
                            </div>
                        </div>
                        <div style="display:flex; gap:8px; margin-top:14px; border-top:1px solid #f1f5f9; padding-top:12px;">
                            <button type="button" onclick="openMemberLedger(${g.id})" class="btn-act btn-act-secondary" style="flex:1;"><i class="fa fa-book"></i> Ledger</button>
                            <button type="button" onclick="convertToRegular(${g.id})" class="btn-act btn-act-primary" style="flex:1;"><i class="fa fa-user-check"></i> Make Regular</button>
                        </div>
                    </div>
                `;
            });
            htm += '</div>';

            $('#guests_grid_container').html(htm);
        } catch(e) {
            $('#guests_grid_container').html('<div style="text-align:center; padding:40px; color:#ef4444;">Error loading guest list.</div>');
        }
    });
}
</script>

<!-- Add Guest Modal -->
<div class="modal fade" id="addGuestModal" tabindex="-1" role="dialog">
    <div class="modal-dialog">
        <div class="modal-content" style="border-radius:20px; overflow:hidden; border:none; box-shadow: 0 25px 50px -12px rgba(0, 0, 0, 0.25);">
            <div class="modal-header" style="background: linear-gradient(135deg, #f59e0b 0%, #d97706 100%); padding: 20px 24px; color: white;">
                <button type="button" class="close" data-dismiss="modal" style="color:white; opacity:0.9; font-size:24px;">&times;</button>
                <h4 class="modal-title" style="font-weight:800; font-size:18px; margin:0;"><i class="fa fa-user-plus"></i> Add Guest Member</h4>
            </div>
            <div class="modal-body" style="padding: 24px;">
                <div class="form-group" style="margin-bottom:14px;">
                    <label style="font-size:12px; font-weight:800; color:#64748b; text-transform:uppercase;">First Name *</label>
                    <input type="text" id="txt_guest_fn" class="form-control" placeholder="e.g. Akash" style="border-radius:12px; height:44px; font-weight:700;">
                </div>
                <div class="form-group" style="margin-bottom:14px;">
                    <label style="font-size:12px; font-weight:800; color:#64748b; text-transform:uppercase;">Last Name</label>
                    <input type="text" id="txt_guest_ln" class="form-control" placeholder="e.g. Kumar" style="border-radius:12px; height:44px; font-weight:600;">
                </div>
                <div class="form-group" style="margin-bottom:14px;">
                    <label style="font-size:12px; font-weight:800; color:#64748b; text-transform:uppercase;">Phone Number</label>
                    <input type="text" id="txt_guest_phone" class="form-control" placeholder="e.g. 9876543210" style="border-radius:12px; height:44px; font-weight:600;">
                </div>
                <div class="form-group" style="margin-bottom:14px;">
                    <label style="font-size:12px; font-weight:800; color:#64748b; text-transform:uppercase;">Email Address</label>
                    <input type="email" id="txt_guest_email" class="form-control" placeholder="e.g. akash@gmail.com" style="border-radius:12px; height:44px; font-weight:600;">
                </div>
                <button type="button" onclick="submitAddGuest()" class="btn btn-block" style="background: linear-gradient(135deg, #f59e0b 0%, #d97706 100%); color:white; font-weight:800; height:46px; border-radius:14px; border:none; margin-top:16px;">
                    <i class="fa fa-check"></i> Save Guest Member
                </button>
            </div>
        </div>
    </div>
</div>

<script>
function openAddGuestModal() {
    $('#txt_guest_fn').val('');
    $('#txt_guest_ln').val('');
    $('#txt_guest_phone').val('');
    $('#txt_guest_email').val('');
    $('#addGuestModal').modal('show');
}

function submitAddGuest() {
    let fn = $('#txt_guest_fn').val() || '';
    if (!fn.trim()) {
        alert("Please enter guest first name.");
        return;
    }
    $.post('api/members.php', {
        action: 'save_members',
        id: 0,
        member_type: 1,
        first_name: fn.trim(),
        last_name: ($('#txt_guest_ln').val() || '').trim(),
        phone: ($('#txt_guest_phone').val() || '').trim(),
        email: ($('#txt_guest_email').val() || '').trim(),
        inactive: 0
    }, function(res) {
        $('#addGuestModal').modal('hide');
        alert('Guest member added successfully!');
        loadGuests();
    }).fail(function(xhr) {
        let msg = "Error adding guest member.";
        try {
            let res = JSON.parse(xhr.responseText);
            if (res.Message) msg = res.Message;
        } catch(e){}
        alert(msg);
    });
}

function openMemberLedger(memberId) {
    window.location.href = "fees_receiveble.php?member_id=" + memberId;
}

function convertToRegular(memberId) {
    if (confirm("Are you sure you want to convert this Guest Member to a Regular Member?")) {
        $.post('api/members.php', { action: 'toggle_guest_status', id: memberId, member_type: 0 }, function(res) {
            loadGuests();
        }).fail(function() {
            alert('Failed to convert member.');
        });
    }
}
</script>
</body>
</html>
