<?php
session_start();
?>
<!DOCTYPE html>
<html>

<head>

    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>My Profile</title>
    <link rel="shortcut icon" href="../favicon.ico" type="image/x-icon">
    <!-- Mobile redirect: send non-admin member logins to mobile portal on small screens -->
    <script>
        (function(){
            if(<?php echo (isset($_SESSION['login_id']) && $_SESSION['login_id'] != 1) ? 'true' : 'false'; ?> && window.innerWidth < 768 && !window.location.href.includes('desktop=1')){
                window.location.replace('mobile/profile.php');
            }
        })();
    </script>

    <link href="../css/bootstrap.min.css" rel="stylesheet">
    <link href="../font-awesome/css/font-awesome.css" rel="stylesheet">

    <link href="../css/animate.css" rel="stylesheet">
    <link href="../css/style.css" rel="stylesheet">
    <link href="../css/custom_modern.css" rel="stylesheet">
    
    <script src="https://cdnjs.cloudflare.com/ajax/libs/jquery/3.6.0/jquery.min.js"></script>
    <link href="../css/plugins/sweetalert/sweetalert.css" rel="stylesheet">
    <script src="../js/plugins/sweetalert/sweetalert.min.js"></script>
    <script src="../app_js/sweetalert-finez.js"></script>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/cropperjs@1.5.13/dist/cropper.min.css">
    <link href="../image_upload/members/upload.css" rel="stylesheet">
    
    <style>
        /* Modern Profile Styles */
        .settings-card-wrapper {
            background-color: var(--card-bg, #ffffff);
            border-radius: var(--border-radius-lg, 24px);
            border: 1px solid var(--border-color, #e2e8f0);
            box-shadow: var(--shadow-md, 0 10px 30px -10px rgba(99, 102, 241, 0.08));
            padding: 35px;
            margin-top: 24px;
            transition: all 0.3s ease;
        }

        .dark-theme .settings-card-wrapper {
            border-color: rgba(255, 255, 255, 0.06);
            box-shadow: 0 10px 30px -10px rgba(0, 0, 0, 0.3);
        }

        /* Profile Layout Split View */
        .profile-container-grid {
            display: grid;
            grid-template-columns: 340px 1fr;
            gap: 35px;
            align-items: start;
        }

        @media (max-width: 992px) {
            .profile-container-grid {
                grid-template-columns: 1fr;
            }
        }

        /* Left profile card with premium gradient backdrop */
        .profile-avatar-card {
            background: linear-gradient(135deg, rgba(99, 102, 241, 0.03) 0%, rgba(168, 85, 247, 0.03) 100%);
            border: 1px solid var(--border-color, #e2e8f0);
            border-radius: var(--border-radius-lg, 24px);
            padding: 40px 24px;
            text-align: center;
            box-shadow: var(--shadow-sm, 0 1px 2px 0 rgba(0, 0, 0, 0.05));
            display: flex;
            flex-direction: column;
            align-items: center;
            gap: 20px;
            transition: all 0.3s ease;
        }

        .dark-theme .profile-avatar-card {
            background: linear-gradient(135deg, rgba(99, 102, 241, 0.06) 0%, rgba(168, 85, 247, 0.06) 100%);
            border-color: rgba(255, 255, 255, 0.06);
        }

        .profile-avatar-card:hover {
            transform: translateY(-2px);
            box-shadow: var(--shadow-md, 0 10px 30px -10px rgba(99, 102, 241, 0.12));
        }

        .profile-avatar-wrapper {
            width: 150px;
            height: 150px;
            border-radius: 50%;
            overflow: hidden;
            border: 4px solid var(--card-bg, #ffffff);
            box-shadow: 0 10px 25px rgba(99, 102, 241, 0.2);
            position: relative;
            transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
        }

        .dark-theme .profile-avatar-wrapper {
            border-color: #1e293b;
        }

        .profile-avatar-wrapper img {
            width: 100%;
            height: 100%;
            object-fit: cover;
            transition: transform 0.5s ease;
        }

        .profile-avatar-card:hover .profile-avatar-wrapper img {
            transform: scale(1.08);
        }

        .profile-name-title {
            font-size: 22px;
            font-weight: 800;
            letter-spacing: -0.5px;
            color: var(--text-primary, #0f172a);
            margin: 6px 0 0 0 !important;
        }

        .profile-sub-role {
            font-size: 12.5px;
            font-weight: 700;
            color: var(--text-muted, #475569);
            margin: 0 !important;
            text-transform: uppercase;
            letter-spacing: 1px;
            display: inline-flex;
            align-items: center;
            gap: 6px;
        }

        .profile-sub-role i {
            color: var(--primary-color, #6366f1);
        }

        .active-member-badge {
            background: linear-gradient(135deg, rgba(16, 185, 129, 0.1) 0%, rgba(5, 150, 105, 0.1) 100%);
            color: #10b981;
            padding: 6px 16px;
            border-radius: 9999px;
            font-size: 11.5px;
            font-weight: 800;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            display: inline-flex;
            align-items: center;
            gap: 6px;
            border: 1px solid rgba(16, 185, 129, 0.15);
        }

        /* Right side details card */
        .profile-details-card {
            background: var(--card-bg, #ffffff);
            border: 1px solid var(--border-color, #e2e8f0);
            border-radius: var(--border-radius-lg, 24px);
            padding: 35px;
            box-shadow: var(--shadow-sm, 0 1px 2px 0 rgba(0, 0, 0, 0.05));
        }

        .dark-theme .profile-details-card {
            border-color: rgba(255, 255, 255, 0.06);
        }

        .details-section-box {
            margin-bottom: 32px;
            position: relative;
        }

        .details-section-box:last-child {
            margin-bottom: 0;
        }

        .details-section-title {
            font-size: 13.5px;
            font-weight: 800;
            text-transform: uppercase;
            letter-spacing: 0.8px;
            color: var(--text-muted, #475569);
            border-bottom: 1px solid var(--border-color, #e2e8f0);
            padding-bottom: 10px;
            margin-bottom: 20px;
            display: flex;
            align-items: center;
            gap: 8px;
        }

        .details-section-title i {
            color: var(--primary-color, #6366f1);
            font-size: 15px;
        }

        .dark-theme .details-section-title {
            border-color: rgba(255, 255, 255, 0.06);
        }

        .details-fields-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(220px, 1fr));
            gap: 20px;
        }

        .detail-item {
            display: flex;
            flex-direction: column;
            gap: 5px;
            padding: 10px 14px;
            border-radius: 12px;
            background: rgba(99, 102, 241, 0.01);
            border: 1px solid transparent;
            transition: all 0.2s ease;
        }

        .detail-item:hover {
            background: rgba(99, 102, 241, 0.03);
            border-color: rgba(99, 102, 241, 0.08);
        }

        .dark-theme .detail-item:hover {
            background: rgba(255, 255, 255, 0.02);
            border-color: rgba(255, 255, 255, 0.04);
        }

        .detail-item .lbl {
            font-size: 12px;
            color: var(--text-muted, #475569);
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }

        .detail-item .val {
            font-size: 15px;
            color: var(--text-primary, #0f172a);
            font-weight: 700;
        }

        .detail-item .val a {
            color: var(--primary-color, #6366f1);
            transition: opacity 0.2s ease;
        }

        .detail-item .val a:hover {
            opacity: 0.8;
        }

        /* Form styling inside modal */
        .modal-body label {
            font-weight: 700 !important;
            font-size: 13px;
            color: var(--text-muted, #475569);
            margin-bottom: 8px;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }

        .form-control {
            border-radius: 12px !important;
            border: 1px solid var(--border-color, #e2e8f0) !important;
            padding: 10px 14px !important;
            background-color: var(--card-bg, #ffffff) !important;
            color: var(--text-primary, #0f172a) !important;
            transition: all 0.2s ease;
        }

        .form-control:focus {
            border-color: var(--primary-color, #6366f1) !important;
            box-shadow: 0 0 0 4px rgba(99, 102, 241, 0.12) !important;
        }

        .modal-body h4 {
            font-size: 14px;
            font-weight: 800;
            text-transform: uppercase;
            letter-spacing: 0.8px;
            margin-top: 25px;
            margin-bottom: 15px;
            border-bottom: 1px solid var(--border-color);
            padding-bottom: 8px;
        }
    </style>
    
    <script>
        var profileMember = null;
        var bloodGroupsMap = {};

        $(document).ready(function() {          
            loadBloodGroupsSelect();
        });  

        function loadBloodGroupsSelect() {
            $.ajax({
                type: "POST",
                url: "api/profile.php",
                data: { action: 'load_blood_groups' },
                success: function(data) {
                    var obj = jQuery.parseJSON(data);
                    var htm = "<option value='0'>Select Blood Group</option>";
                    for (var i = 0; i < obj[0].length; i++) {
                        htm += "<option value='" + obj[0][i].id + "'>" + obj[0][i].name + "</option>";
                        bloodGroupsMap[obj[0][i].id] = obj[0][i].name;
                    }
                    $("#txt_blood_group").html(htm);
                    loadData(1);
                }
            });
        }
               
        function loadData(page) {
            $('#hdn_current_page').val(page); 
            $.ajax({               
               type: "POST",
               url: "api/profile.php",
               data: {
               action: 'load_profile_data',
               page: page, 
               },
                success: function(data) {  
                    var obj = jQuery.parseJSON(data);
                    if (obj[1] && obj[1].length > 0) {
                        profileMember = obj[1][0];
                    }
                    
                    var htm="";
                    if (obj[1].length === 0) {
                        htm = "<div class='text-center text-muted' style='padding: 40px;'>No profile information available.</div>";
                    } else {
                        var member = obj[1][0];
                        var imgPath = (member.img && member.img !== '0' && member.img !== 'customer.png') ? '../image_upload/members/uploads/' + member.img : '../img/customer.png';
                        
                        var genderText = member.gender ? member.gender : "Not Specified";
                        var dobText = (member.dob && member.dob !== '0000-00-00') ? member.dob : "Not Specified";
                        var bloodGroupName = bloodGroupsMap[member.blood_group] ? bloodGroupsMap[member.blood_group] : "Not Specified";
                        
                        htm += "<div class='profile-container-grid'>";
                        
                        // Left card
                        htm += "  <div class='profile-avatar-card'>";
                        htm += "    <div class='profile-avatar-wrapper'>";
                        htm += "      <img src='" + imgPath + "' alt='Avatar' onerror=\"this.src='../img/customer.png'\">";
                        htm += "    </div>";
                        var memberCode = 'YMCA-BCA-' + (1000 + parseInt(member.id, 10));
                        htm += "    <div>";
                        htm += "      <h3 class='profile-name-title'>" + member.first_name + " " + (member.middle_name ? member.middle_name + " " : "") + member.last_name + "</h3>";
                        htm += "      <p class='profile-sub-role'><i class='fa fa-id-card-o'></i> Member No: " + memberCode + "</p>";
                        htm += "    </div>";
                        htm += "    <div class='active-member-badge'><i class='fa fa-check-circle'></i> Active</div>";
                        htm += "    <button type='button' class='btn btn-primary btn-block' style='margin-top: 10px; border-radius: 12px; font-weight:700; padding:10px;' onclick='editMyProfile()'><i class='fa fa-edit'></i> Edit Profile</button>";
                        htm += "    <a href='../app_login_manager/forgot_password.php' class='btn btn-default btn-block' style='margin-top: 10px; border-radius: 12px; font-weight:700; padding:10px; border: 1.5px solid var(--border-color); color: var(--text-primary);'><i class='fa fa-key'></i> Change Password</a>";
                        htm += "  </div>";
                        
                        // Right card
                        htm += "  <div class='profile-details-card'>";
                        
                        // Personal Information
                        htm += "    <div class='details-section-box'>";
                        htm += "      <h4 class='details-section-title'><i class='fa fa-user'></i> Personal Details</h4>";
                        htm += "      <div class='details-fields-grid'>";
                        htm += "        <div class='detail-item'><span class='lbl'>Member No</span><span class='val'><span class='badge' style='background:#f1f5f9; color:#475569; border:1px solid #cbd5e1; font-size:12px; font-weight:800; padding:4px 10px; border-radius:8px;'><i class='fa fa-id-card-o'></i> " + memberCode + "</span></span></div>";
                        htm += "        <div class='detail-item'><span class='lbl'>Gender</span><span class='val'>" + genderText + "</span></div>";
                        htm += "        <div class='detail-item'><span class='lbl'>Date of Birth</span><span class='val'>" + dobText + "</span></div>";
                        htm += "        <div class='detail-item'><span class='lbl'>Blood Group</span><span class='val'>" + bloodGroupName + "</span></div>";
                        htm += "      </div>";
                        htm += "    </div>";

                        // Family Information
                        htm += "    <div class='details-section-box'>";
                        htm += "      <h4 class='details-section-title'><i class='fa fa-users'></i> Family Information</h4>";
                        htm += "      <div class='details-fields-grid'>";
                        htm += "        <div class='detail-item'><span class='lbl'>Father's Name</span><span class='val'>" + (member.father_name ? member.father_name : "Not Specified") + "</span></div>";
                        htm += "        <div class='detail-item'><span class='lbl'>Mother's Name</span><span class='val'>" + (member.mother_name ? member.mother_name : "Not Specified") + "</span></div>";
                        htm += "      </div>";
                        htm += "    </div>";

                        // Contact Details
                        htm += "    <div class='details-section-box'>";
                        htm += "      <h4 class='details-section-title'><i class='fa fa-envelope-o'></i> Contact Details</h4>";
                        htm += "      <div class='details-fields-grid'>";
                        htm += "        <div class='detail-item'><span class='lbl'>Phone Number</span><span class='val'><a href='tel:" + member.phone + "'><i class='fa fa-phone'></i> " + member.phone + "</a></span></div>";
                        htm += "        <div class='detail-item'><span class='lbl'>WhatsApp Number</span><span class='val'><a href='https://wa.me/" + member.whtsapp + "' target='_blank'><i class='fa fa-whatsapp'></i> " + (member.whtsapp ? member.whtsapp : "Not Specified") + "</a></span></div>";
                        htm += "        <div class='detail-item'><span class='lbl'>Email Address</span><span class='val'><a href='mailto:" + member.email + "'><i class='fa fa-envelope'></i> " + member.email + "</a></span></div>";
                        htm += "      </div>";
                        htm += "    </div>";

                        // Address Details
                        htm += "    <div class='details-section-box'>";
                        htm += "      <h4 class='details-section-title'><i class='fa fa-map-marker'></i> Permanent Address</h4>";
                        htm += "      <div class='details-fields-grid'>";
                        htm += "        <div class='detail-item'><span class='lbl'>Street</span><span class='val'>" + (member.street ? member.street : "Not Specified") + "</span></div>";
                        htm += "        <div class='detail-item'><span class='lbl'>City</span><span class='val'>" + (member.city ? member.city : "Not Specified") + "</span></div>";
                        htm += "        <div class='detail-item'><span class='lbl'>Pincode</span><span class='val'>" + (member.pincode ? member.pincode : "Not Specified") + "</span></div>";
                        htm += "        <div class='detail-item'><span class='lbl'>Country</span><span class='val'>" + (member.country ? member.country : "Not Specified") + "</span></div>";
                        htm += "      </div>";
                        htm += "    </div>";
                        
                        htm += "  </div>";
                        htm += "</div>";
                    }

                    $('#table_profiles').html(htm);
                },

                error: function(xhr, status, error) {
                    console.log('AJAX error: ', status, error);
                }
            });
            loadMenu();
        }
    </script>
 
    <script>
        function editMyProfile() {
            if (!profileMember) return;
            $("#txt_first_name").val(profileMember.first_name);
            $("#txt_middle_name").val(profileMember.middle_name);
            $("#txt_last_name").val(profileMember.last_name);
            $("#txt_gender").val(profileMember.gender ? profileMember.gender : "0");
            $("#txt_father_name").val(profileMember.father_name);
            $("#txt_mother_name").val(profileMember.mother_name);
            $("#txt_dob").val(profileMember.dob && profileMember.dob !== '0000-00-00' ? profileMember.dob : '');
            $("#txt_blood_group").val(profileMember.blood_group || "0");
            $("#txt_phone").val(profileMember.phone);
            $("#txt_whtsapp").val(profileMember.whtsapp);
            $("#txt_email").val(profileMember.email);
            $("#txt_street").val(profileMember.street);
            $("#txt_city").val(profileMember.city);
            $("#txt_pincode").val(profileMember.pincode);
            $("#txt_country").val(profileMember.country);

            $("#hdn_file_upload").val(profileMember.img);
            $("#hdn_id").val(profileMember.id);
            $('#clientModal').modal('show');
        }

        function saveProfile() { 
            const firstName = $('#txt_first_name').val();
            const phone = $('#txt_phone').val();
            const email = $('#txt_email').val();

            if (firstName == "") {
                alertinfo("First Name cannot be empty");
                return;
            }

            if (phone == "") {
                alertinfo("Phone cannot be empty");
                return;
            }

            if (email == "") {
                alertinfo("Email cannot be empty");
                return;
            }

            swal({
                title: "Are you sure?",
                text: "Do you want to save this data!",
                type: "warning",
                showCancelButton: true,
                confirmButtonColor: "#DD6B55",
                confirmButtonText: "Yes, Save!",
                cancelButtonText: "Cancel",
                closeOnConfirm: false,
                closeOnCancel: true
            },
            function (isConfirm) {
                if (isConfirm){
                    var data = {
                        action: 'save_profile',
                        first_name: $('#txt_first_name').val(),
                        middle_name: $('#txt_middle_name').val(),
                        last_name: $('#txt_last_name').val(),
                        gender: $('#txt_gender').val(),
                        father_name: $('#txt_father_name').val(),
                        mother_name: $('#txt_mother_name').val(),
                        dob: $('#txt_dob').val(),
                        blood_group: $('#txt_blood_group').val(),
                        phone: $('#txt_phone').val(),
                        whtsapp: $('#txt_whtsapp').val(),
                        email: $('#txt_email').val(),
                        street: $('#txt_street').val(),
                        city: $('#txt_city').val(),
                        pincode: $('#txt_pincode').val(),
                        country: $('#txt_country').val(),
                        id: $("#hdn_id").val(),
                        img: $("#hdn_file_upload").val(),
                    };
                    $.ajax({
                        type: "POST",
                        url: "api/profile.php",
                        data: data,
                        success: function(response) {
                            console.log('saved:', response);
                            closeProfileDetails();
                            alertsuccess('Saved Successfully');
                            loadData($('#hdn_current_page').val());
                        },
                        error: function (xhr, status){
                            var msgObj = JSON.parse(xhr.responseText);
                            alerterror(msgObj, xhr);
                            $('#member_form')[0].reset();
                            $("#photoInput").val('');
                        }
                    });
                }
            }); 
        }

        function closeProfileDetails(){
            $("#hdn_id").val(0);
            $('#member_form')[0].reset();
            $('#clientModal').modal('toggle');
        }
    
    </script>
    <script src="../app_menu/menu.js"></script>

</head>

<body>
<input type="hidden" id="hdn_current_page"  value="0">
<input type="hidden" id="hdn_id"  value="0">
<input type="hidden" id="hdn_file_upload" name="image" value="0">

    <div id="wrapper">
        
        <!-- navigation starts -->
        <nav class="navbar-default navbar-static-side" role="navigation">
            <div class="dropdown profile-element">
                <center>
                    <span><img alt="image" class="img-circle" src="../img/customer.png" style="padding-top: 20px;"/></span>
                    <span class="clear"> <span class="block m-t-xs"> <strong class="font-bold"><?php echo $_SESSION['name']; ?></strong>
                </center>
            </div>
            <div class="sidebar-collapse" id="divMenuContainer">
                <!-- menu injected via ajax -->
            </div>
        </nav>
        <!-- navigation ends -->

        <div id="page-wrapper" class="gray-bg">
            <div class="row border-bottom">
                <nav class="navbar navbar-static-top" role="navigation" style="margin-bottom: 0">
                    <div class="navbar-header">
                        <a class="navbar-minimalize minimalize-styl-2 btn btn-primary " href="#"><i class="fa fa-bars"></i> </a>
                    </div>
                    <ul class="nav navbar-top-links navbar-right">     
                        <form action="../app_login_manager/logout.php" method="post"></form>        
                            <li>
                                <a href="../app_login_manager/logout.php" style="color: #147ad1";>
                                    <i class="fa fa-sign-out"></i> Log out
                                </a>
                            </li>
                         </form>
                    </ul>
                </nav>
            </div>
            
            <!-- search bar started -->
            <div class="row wrapper border-bottom white-bg page-heading" style="padding: 20px 30px; border-bottom: 1px solid var(--border-color, #e2e8f0) !important;">
                <div class="col-sm-8">
                    <h2 style="font-weight: 800; font-size: 24px; letter-spacing: -0.5px; margin: 0 !important; color: var(--text-primary, #0f172a);">My Profile</h2>
                </div>
            </div>
            <!-- search bar end -->

            <div class="settings-card-wrapper">
                <div class="wrapper wrapper-content animated fadeInRight" id="table_profiles" style="padding: 0;">
                    <!-- Profile Datas injected by ajax -->
                </div>
            </div>
        </div>
    </div>

    <!-- popup for add new profile or edit profile starts -->
    <div class="modal inmodal" id="clientModal" tabindex="-1" role="dialog" aria-hidden="true">
        <div class="modal-dialog modal-lg">
            <div class="modal-content animated bounceInRight" style="border-radius: 20px; overflow: hidden; border: none; box-shadow: var(--shadow-lg);">
                <form method="POST" id="member_form" enctype="multipart/form-data">
                    <div class="modal-header" style="background: var(--primary-gradient, linear-gradient(135deg, #6366f1 0%, #4f46e5 100%)); padding: 24px 30px; color: #ffffff; text-align: left;">
                        <button type="button" class="close" data-dismiss="modal" style="color: #ffffff; opacity: 0.8; font-size: 24px;" onclick="closeProfileDetails();">&times;</button>
                        <h3 style="margin: 0; font-weight: 800; font-size: 20px; letter-spacing: -0.5px;"><i class="fa fa-edit"></i> Edit Profile Details</h3>
                        <p style="margin: 4px 0 0 0; opacity: 0.9; font-size: 13.5px;">Update your personal, contact, and address information</p>
                    </div>

                    <div class="modal-body" style="padding: 30px; max-height: 480px; overflow-y: auto; background: var(--card-bg, #ffffff);">
                        
                        <!-- Section: Names & Personal -->
                        <h4 style="font-weight:700; color:var(--primary-color); margin-bottom: 15px; border-bottom:1px solid var(--border-color); padding-bottom:6px;"><i class="fa fa-user"></i> Personal Details</h4>
                        <div class="row">
                            <div class="col-md-4 form-group">
                                <label>First Name</label>
                                <input type="text" id="txt_first_name" placeholder="First Name" class="form-control">
                            </div>
                            <div class="col-md-4 form-group">
                                <label>Middle Name</label>
                                <input type="text" id="txt_middle_name" placeholder="Middle Name" class="form-control">
                            </div>
                            <div class="col-md-4 form-group">
                                <label>Last Name</label>
                                <input type="text" id="txt_last_name" placeholder="Last Name" class="form-control">
                            </div>
                        </div>

                        <div class="row">
                            <div class="col-md-4 form-group">
                                <label>Gender</label>
                                <select id="txt_gender" class="form-control">
                                    <option value="0">Select Gender</option>
                                    <option value="Male">Male</option>
                                    <option value="Female">Female</option>
                                    <option value="Other">Other</option>
                                </select>
                            </div>
                            <div class="col-md-4 form-group">
                                <label>Date of Birth</label>
                                <input type="date" id="txt_dob" class="form-control">
                            </div>
                            <div class="col-md-4 form-group">
                                <label>Blood Group</label>
                                <select id="txt_blood_group" class="form-control">
                                    <!-- Loaded via AJAX -->
                                </select>
                            </div>
                        </div>

                        <!-- Section: Family Details -->
                        <h4 style="font-weight:700; color:var(--primary-color); margin-top:20px; margin-bottom: 15px; border-bottom:1px solid var(--border-color); padding-bottom:6px;"><i class="fa fa-users"></i> Family Details</h4>
                        <div class="row">
                            <div class="col-md-6 form-group">
                                <label>Father's Name</label>
                                <input type="text" id="txt_father_name" placeholder="Father's Name" class="form-control">
                            </div>
                            <div class="col-md-6 form-group">
                                <label>Mother's Name</label>
                                <input type="text" id="txt_mother_name" placeholder="Mother's Name" class="form-control">
                            </div>
                        </div>

                        <!-- Section: Contact Details -->
                        <h4 style="font-weight:700; color:var(--primary-color); margin-top:20px; margin-bottom: 15px; border-bottom:1px solid var(--border-color); padding-bottom:6px;"><i class="fa fa-phone"></i> Contact Details</h4>
                        <div class="row">
                            <div class="col-md-4 form-group">
                                <label>Phone Number</label>
                                <input type="text" id="txt_phone" placeholder="Phone" class="form-control">
                            </div>
                            <div class="col-md-4 form-group">
                                <label>WhatsApp Number</label>
                                <input type="text" id="txt_whtsapp" placeholder="WhatsApp" class="form-control">
                            </div>
                            <div class="col-md-4 form-group">
                                <label>Email Address</label>
                                <input type="email" id="txt_email" placeholder="Email" class="form-control">
                            </div>
                        </div>

                        <!-- Section: Permanent Address -->
                        <h4 style="font-weight:700; color:var(--primary-color); margin-top:20px; margin-bottom: 15px; border-bottom:1px solid var(--border-color); padding-bottom:6px;"><i class="fa fa-map-marker"></i> Permanent Address</h4>
                        <div class="row">
                            <div class="col-md-6 form-group">
                                <label>Street</label>
                                <input type="text" id="txt_street" placeholder="Street" class="form-control">
                            </div>
                            <div class="col-md-6 form-group">
                                <label>City</label>
                                <input type="text" id="txt_city" placeholder="City" class="form-control">
                            </div>
                        </div>
                        <div class="row">
                            <div class="col-md-6 form-group">
                                <label>Pincode</label>
                                <input type="text" id="txt_pincode" placeholder="Pincode" class="form-control">
                            </div>
                            <div class="col-md-6 form-group">
                                <label>Country</label>
                                <input type="text" id="txt_country" placeholder="Country Name" class="form-control">
                            </div>
                        </div>

                        <!-- Image Section -->
                        <h4 style="font-weight:700; color:var(--primary-color); margin-top:20px; margin-bottom: 15px; border-bottom:1px solid var(--border-color); padding-bottom:6px;"><i class="fa fa-image"></i> Profile Photo</h4>
                        <div class="row">
                            <div class="col-md-12">
                                <input type="file" id="photoInput" onchange="photoInputChange(event);" accept="image/*" class="form-control" style="border:none; padding:4px 0; background:transparent !important;">
                            </div>
                        </div>

                        <!-- Popup for crop the image start -->
                        <div id="cropModal">
                            <div id="modalContent">
                                <input type="button" id="closeModal" onclick="closeModalClicked();" class="closeModal" value="&times;">
                                <img id="modalPreview" alt="Preview">
                                <input type="button" id="cropButton" onclick="cropButtonClicked();" value="Crop and Upload">
                            </div>
                        </div>
                        <!-- Popup for crop the image ends -->

                    </div>
                 
                    <div class="modal-footer" style="background: var(--card-bg, #ffffff); border-top: 1px solid var(--border-color, #e2e8f0); padding: 18px 30px; display: flex; justify-content: flex-end; gap: 12px;">
                        <button type="button" class="btn btn-white" style="border-radius: 10px; font-weight: 700; padding: 8px 16px;" onclick="closeProfileDetails();">Close</button>
                        <button type="button" class="btn btn-primary" style="border-radius: 10px; font-weight: 700; padding: 8px 20px;" onclick="saveProfile();">Save</button>
                    </div>
                </form>
            </div>
        </div>
    </div> 
    <!-- popup for add new profile or update profile end-->
    
    <!-- Mainly scripts -->
    <script src="../js/jquery-3.1.1.min.js"></script>
    <script src="../js/bootstrap.min.js"></script>
    <script src="../js/plugins/metisMenu/jquery.metisMenu.js"></script>
    <script src="../js/plugins/slimscroll/jquery.slimscroll.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/cropperjs@1.5.13/dist/cropper.min.js"></script>
    <!-- Custom and plugin javascript -->
    <script src="../js/inspinia.js"></script>
    <script src="../js/plugins/pace/pace.min.js"></script>
    <script src="../image_upload/members/image_upload.js"></script>
    <script src="../app_js/validation.js"></script>

</body>

</html>
