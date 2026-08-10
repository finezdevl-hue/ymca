<?php
session_start();
if(empty($_SESSION['login_id'])){
    header("Location: ../index.php");
    exit();
}

if(isset($_POST['id'])){
    $_SESSION['id']=$_POST['id'];
}

include_once '../app_common/auth_helper.php';
$login_id = (int)$_SESSION['login_id'];
if (isNormalMember($login_id)) {
    header("Location: member_dashboard.php");
    exit();
}
$allowed_groups = getUserAllowedGroupIds($login_id);

$group_id = !empty($_SESSION['id']) ? (int)$_SESSION['id'] : 0;
if ($group_id == 0 && !in_array('ALL', $allowed_groups, true) && !empty($allowed_groups)) {
    $group_id = (int)$allowed_groups[0];
    $_SESSION['id'] = $group_id;
}

$group_name = "Group Members";

if ($group_id > 0) {
    include_once '../app_common/database_class.php';
    $db = new Database();
    $conn = $db->getConnection();
    $stmt_g = $conn->prepare("SELECT name FROM tbl_groups WHERE id = ?");
    if ($stmt_g) {
        $stmt_g->bind_param("i", $group_id);
        $stmt_g->execute();
        $res_g = $stmt_g->get_result();
        if ($res_g && $row_g = $res_g->fetch_assoc()) {
            $group_name = $row_g['name'];
        }
        $stmt_g->close();
    }
    $db->closeConnection();
}
?>
<!DOCTYPE html>
<html>

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Members in <?php echo htmlspecialchars($group_name); ?> - YMCA</title>

    <!-- FontAwesome & Fonts -->
    <link href="../font-awesome/css/font-awesome.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">

    <!-- Framework Styles -->
    <link href="../css/bootstrap.min.css" rel="stylesheet">
    <link href="../css/animate.css" rel="stylesheet">
    <link href="../css/style.css" rel="stylesheet">
    <link href="../css/plugins/sweetalert/sweetalert.css" rel="stylesheet">

    <!-- Modern Dark Mode Core -->
    <link href="../css/modern_dark_mode.css" rel="stylesheet">
    <script src="../js/modern_dark_mode.js"></script>

    <style>
        :root {
            --bg-main: #f8fafc;
            --bg-card: #ffffff;
            --border-color: #e2e8f0;
            --text-primary: #0f172a;
            --text-muted: #64748b;
        }

        .gmem-container {
            padding: 24px 30px;
            font-family: 'Inter', sans-serif;
        }

        .gmem-header-bar {
            display: flex;
            align-items: center;
            justify-content: space-between;
            flex-wrap: wrap;
            gap: 16px;
            background: #ffffff !important;
            border: 1px solid #e2e8f0 !important;
            border-radius: 16px;
            padding: 20px 24px;
            margin-bottom: 24px;
            box-shadow: 0 4px 15px rgba(0, 0, 0, 0.04);
        }

        .gmem-title-area {
            display: flex;
            align-items: center;
            gap: 14px;
        }

        .gmem-back-btn {
            background: #ffffff !important;
            border: 1px solid #cbd5e1 !important;
            color: #0f172a !important;
            width: 42px;
            height: 42px;
            border-radius: 10px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 16px;
            text-decoration: none !important;
            transition: all 0.2s;
        }

        .gmem-back-btn:hover {
            background: #3b82f6 !important;
            color: #ffffff !important;
            border-color: #3b82f6 !important;
        }

        .gmem-title-area h1 {
            font-size: 20px;
            font-weight: 800;
            color: #0f172a;
            margin: 0 0 4px 0;
            font-family: 'Inter', sans-serif;
        }

        .gmem-group-badge {
            font-size: 12px;
            font-weight: 700;
            background: #eff6ff !important;
            color: #3b82f6 !important;
            padding: 5px 14px;
            border-radius: 20px;
            display: inline-flex;
            align-items: center;
            gap: 6px;
            border: 1px solid #dbeafe !important;
        }

        .gmem-search-wrap {
            position: relative;
            min-width: 260px;
        }

        .gmem-search-input {
            width: 100%;
            background: #ffffff !important;
            border: 1px solid #cbd5e1 !important;
            border-radius: 10px;
            padding: 10px 16px 10px 38px;
            font-size: 13.5px;
            font-weight: 500;
            color: #0f172a !important;
            outline: none;
            font-family: 'Inter', sans-serif;
            transition: all 0.2s;
        }

        .gmem-search-input:focus {
            border-color: #3b82f6 !important;
            box-shadow: 0 0 0 3px rgba(59, 130, 246, 0.15);
        }

        .gmem-search-icon {
            position: absolute;
            left: 14px;
            top: 50%;
            transform: translateY(-50%);
            color: #64748b;
            font-size: 14px;
        }

        /* Member Grid */
        .gmem-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(280px, 1fr));
            gap: 20px;
        }

        .gmem-card {
            background: #ffffff !important;
            border: 1px solid #e2e8f0 !important;
            border-radius: 16px;
            padding: 24px;
            box-shadow: 0 4px 15px rgba(0, 0, 0, 0.04);
            transition: all 0.2s ease;
            display: flex;
            flex-direction: column;
            align-items: center;
            text-align: center;
            position: relative;
        }

        .gmem-card:hover {
            border-color: #3b82f6 !important;
            box-shadow: 0 8px 25px rgba(0, 0, 0, 0.08);
        }

        .gmem-avatar {
            width: 76px;
            height: 76px;
            border-radius: 50%;
            object-fit: cover;
            border: 3px solid #e2e8f0;
            margin-bottom: 14px;
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.06);
        }

        .gmem-name {
            font-size: 16px;
            font-weight: 800;
            color: #0f172a;
            margin: 0 0 4px 0;
            font-family: 'Inter', sans-serif;
            line-height: 1.3;
        }

        .gmem-contact-info {
            font-size: 12px;
            color: #64748b;
            margin-bottom: 16px;
            word-break: break-all;
        }

        /* Quick Contact Bar */
        .gmem-contact-bar {
            display: flex;
            align-items: center;
            gap: 8px;
            margin-bottom: 18px;
        }

        .gmem-contact-btn {
            width: 36px;
            height: 36px;
            border-radius: 50%;
            background: #f8fafc !important;
            border: 1px solid #cbd5e1 !important;
            color: #334155 !important;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 14px;
            text-decoration: none !important;
            transition: all 0.2s;
            cursor: pointer;
        }

        .gmem-contact-btn.call:hover {
            background: #10b981 !important;
            color: #ffffff !important;
            border-color: #10b981 !important;
        }

        .gmem-contact-btn.email:hover {
            background: #3b82f6 !important;
            color: #ffffff !important;
            border-color: #3b82f6 !important;
        }

        .gmem-contact-btn.wa:hover {
            background: #25d366 !important;
            color: #ffffff !important;
            border-color: #25d366 !important;
        }

        /* Bottom Footer Actions */
        .gmem-card-footer {
            width: 100%;
            border-top: 1px solid #f1f5f9;
            padding-top: 14px;
            display: flex;
            align-items: center;
            gap: 8px;
        }

        .gmem-footer-btn {
            flex: 1;
            background: #ffffff !important;
            border: 1px solid #cbd5e1 !important;
            border-radius: 8px;
            padding: 8px 10px;
            font-size: 12px;
            font-weight: 600;
            color: #334155 !important;
            cursor: pointer;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 6px;
            transition: all 0.2s;
            text-decoration: none !important;
            font-family: 'Inter', sans-serif;
        }

        .gmem-footer-btn:hover {
            background: #3b82f6 !important;
            color: #ffffff !important;
            border-color: #3b82f6 !important;
        }

        .gmem-footer-btn.remove-btn:hover {
            background: #ef4444 !important;
            color: #ffffff !important;
            border-color: #ef4444 !important;
        }

        /* Modal Solid Overrides */
        .modal-content {
            border-radius: 16px !important;
            border: 1px solid #cbd5e1 !important;
            background-color: #ffffff !important;
            box-shadow: 0 25px 50px -12px rgba(0, 0, 0, 0.25) !important;
            opacity: 1 !important;
        }

        .modal-backdrop {
            background-color: #0f172a !important;
            opacity: 0.6 !important;
        }
    </style>

    <script src="https://cdnjs.cloudflare.com/ajax/libs/jquery/3.6.0/jquery.min.js"></script>
    <script src="../js/plugins/sweetalert/sweetalert.min.js"></script>
    <script src="../app_js/sweetalert-finez.js"></script>

    <script>
        $(document).ready(function() {
            loadData(1);
        });

        function loadData(page) {
            $('#hdn_current_page').val(page);
            var searchVal = $('#txt_search').val().trim();

            $.ajax({               
                type: "POST",
                url: "api/group_members.php",
                data: {
                    action: 'load_data',
                    page: page, 
                    val: searchVal
                },
                success: function(data) {
                    var obj = jQuery.parseJSON(data);
                    var totalrows = obj[0].total_rows;
                    var members = obj[1];
                    var htm = "";

                    if (members.length > 0) {
                        htm += "<div class='gmem-grid'>";
                        for (var i = 0; i < members.length; i++) {
                            var m = members[i];
                            var fullName = [m.first_name, m.middle_name, m.last_name].filter(function(p){ return p && p.trim(); }).join(' ');
                            
                            var imgSrc = '../img/customer.png';
                            if (m.img && m.img != 0 && m.img != '0' && typeof m.img === 'string' && m.img.trim() !== '') {
                                imgSrc = '../image_upload/members/thumbnails/' + m.img;
                            }

                            var phoneVal = m.phone ? m.phone.trim() : '';
                            var emailVal = m.email ? m.email.trim() : '';
                            var waVal = m.whtsapp ? m.whtsapp.trim() : phoneVal;

                            htm += "<div class='gmem-card'>";
                            htm += "  <img src='" + imgSrc + "' class='gmem-avatar' onerror=\"this.src='../img/customer.png'\">";
                            htm += "  <h3 class='gmem-name'>" + fullName + "</h3>";
                            htm += "  <div class='gmem-contact-info'>" + (phoneVal ? phoneVal : 'No Phone') + "</div>";
                            
                            htm += "  <div class='gmem-contact-bar'>";
                            if (phoneVal) {
                                htm += "    <a href='tel:" + phoneVal + "' class='gmem-contact-btn call' title='Call Member'><i class='fa fa-phone'></i></a>";
                            }
                            if (emailVal) {
                                htm += "    <a href='mailto:" + emailVal + "' class='gmem-contact-btn email' title='Email Member'><i class='fa fa-envelope'></i></a>";
                            }
                            if (waVal) {
                                htm += "    <a onclick=\"window.open('https://wa.me/" + waVal + "', '_blank')\" class='gmem-contact-btn wa' title='WhatsApp'><i class='fa fa-whatsapp'></i></a>";
                            }
                            htm += "  </div>";

                            htm += "  <div class='gmem-card-footer'>";
                            htm += "    <button type='button' onclick='navigateSeeFeeHostory(" + m.id + ",\"" + (m.first_name||'') + "\",\"" + (m.middle_name||'') + "\",\"" + (m.last_name||'') + "\")' class='gmem-footer-btn' title='View Fee Details'><i class='fa fa-history'></i> Fees</button>";
                            htm += "    <button type='button' onclick='removeMember(" + m.id + ")' class='gmem-footer-btn remove-btn' title='Remove from Group'><i class='fa fa-trash'></i> Remove</button>";
                            htm += "  </div>";
                            htm += "</div>";
                        }
                        htm += "</div>";
                    } else {
                        htm += "<div style='background: var(--bg-card); border: 1px dashed var(--border-color); border-radius: 20px; padding: 48px 20px; text-align: center; color: var(--text-muted); font-family: \"Inter\", sans-serif;'>";
                        htm += "  <i class='fa fa-users' style='font-size: 40px; color: #94a3b8; margin-bottom: 12px; display: block;'></i>";
                        htm += "  <h3 style='font-size: 16px; font-weight: 700; color: var(--text-primary); margin: 0 0 6px 0;'>No Members Found</h3>";
                        htm += "  <p style='font-size: 13px; margin: 0;'>No members match your search criteria for this group.</p>";
                        htm += "</div>";
                    }

                    $('#table_client').html(htm);
                    var htmpage = paginate(totalrows, page);
                    $('#table_client').append('<div style="margin-top: 24px;">' + htmpage + '</div>');
                },
                error: function(xhr, status, error) {
                    console.log('AJAX error: ', status, error);
                }
            });
            loadMenu();
        }

        function searchMember(){
            loadData(1);
        }

        function navigateSeeFeeHostory(id, first_name, middle_name, last_name){ 
            $.post("fees_details.php", { 
                'member_id': id,
                'first_name': first_name, 
                'middle_name': middle_name, 
                'last_name': last_name 
            }).done(function(response) {
                window.location.href = "fees_details.php";
            });
        }

        function removeMember(id) {
            $("#hdn_id").val(id);
            swal({
                title: "Are you sure?",
                text: "Do you want to remove this member from the group?",
                type: "warning",
                showCancelButton: true,
                confirmButtonColor: "#ef4444",
                confirmButtonText: "Yes, Remove!",
                cancelButtonText: "Cancel",
                closeOnConfirm: true
            }, function (isConfirm) {
                if (isConfirm){
                    load_overlay();
                    $.ajax({
                        type: "POST",
                        url: "api/group_members.php",
                        data: {
                            action: 'remove_member',
                            id: $("#hdn_id").val()
                        },
                        success: function(response) {
                            close_overlay();
                            $("#hdn_id").val(0);
                            alertwarning('Member removed from group');
                            loadData($('#hdn_current_page').val());
                        },
                        error: function(xhr, status, error) {
                            close_overlay();
                            console.log('AJAX error:', status, error);
                        }
                    });
                }
            });   
        }
    </script>
    <script src="../app_pagination/pagination.js"></script>
    <script src="../app_menu/menu.js"></script>
</head>

<body>
    <input type="hidden" id="hdn_current_page" value="1">
    <input type="hidden" id="hdn_id" value="0">
    <input type="hidden" id="hdn_member_id" value="0">

    <div id="wrapper">
        <!-- Sidebar Navigation -->
        <nav class="navbar-default navbar-static-side" role="navigation">
            <div class="dropdown profile-element">
                <center>
                    <span><img alt="image" class="img-circle" src="../img/customer.png" style="padding-top: 20px;"/></span>
                    <span class="clear"><span class="block m-t-xs"><strong class="font-bold"><?php echo $_SESSION['name']; ?></strong></span></span>
                </center>
            </div>
            <div class="sidebar-collapse" id="divMenuContainer">
                <!-- menu injected via ajax -->
            </div>
        </nav>

        <div id="page-wrapper" style="background: var(--bg-main); min-height: 100vh;">
            <!-- Top navbar -->
            <div class="row border-bottom" style="background:#fff; margin:0;">
                <nav class="navbar navbar-static-top" role="navigation" style="margin-bottom:0; background:#fff;">
                    <div class="navbar-header">
                        <a class="navbar-minimalize minimalize-styl-2 btn btn-primary" href="#"><i class="fa fa-bars"></i></a>
                    </div>
                    <ul class="nav navbar-top-links navbar-right">
                        <li>
                            <a href="../app_login_manager/logout.php" style="color:#147ad1;">
                                <i class="fa fa-sign-out"></i> Log out
                            </a>
                        </li>
                    </ul>
                </nav>
            </div>

            <div class="gmem-container">
                <!-- Header Control Bar -->
                <div class="gmem-header-bar">
                    <div class="gmem-title-area">
                        <a href="groups.php" class="gmem-back-btn" title="Back to Groups">
                            <i class="fa fa-arrow-left"></i>
                        </a>
                        <div>
                            <h1>Enrolled Members</h1>
                            <span class="gmem-group-badge">
                                <i class="fa fa-users"></i> <?php echo htmlspecialchars($group_name); ?>
                            </span>
                        </div>
                    </div>
                    <div class="gmem-search-wrap">
                        <i class="fa fa-search gmem-search-icon"></i>
                        <input type="text" id="txt_search" placeholder="Search members by name..." class="gmem-search-input" onkeyup="if(event.key==='Enter') searchMember();">
                    </div>
                </div>

                <!-- Main Content Cards Container -->
                <div id="table_client">
                    <!-- Cards injected dynamically via AJAX -->
                </div>
            </div>

        </div><!-- end page-wrapper -->
    </div><!-- end wrapper -->

    <!-- Scripts -->
    <script src="../js/bootstrap.min.js"></script>
    <script src="../js/plugins/metisMenu/jquery.metisMenu.js"></script>
    <script src="../js/plugins/slimscroll/jquery.slimscroll.min.js"></script>
    <script src="../js/inspinia.js"></script>
    <script src="../js/plugins/pace/pace.min.js"></script>
    <script src="../js/loadingoverlay.min.js"></script>
</body>

</html>
