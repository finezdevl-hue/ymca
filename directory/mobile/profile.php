<?php
session_start();
session_write_close();
include_once __DIR__ . '/../../app_common/auth_helper.php';

if (empty($_SESSION['login_id']) || $_SESSION['login_id'] == 1) {
    header("Location: ../../index.php");
    exit();
}

$page_title = 'My Profile';
$active_tab = 'profile';

$member_name = $_SESSION['name']   ?? 'Member';
$email       = $_SESSION['email']  ?? '';
$member_id   = $_SESSION['user_id'] ?? 0;
$initial     = strtoupper(substr($member_name, 0, 1));
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
    <meta name="description" content="My Profile - YMCA Member Portal">
    <title>YMCA | My Profile</title>
    <link rel="shortcut icon" href="../../favicon.ico" type="image/x-icon">
    <link href="../../font-awesome/css/font-awesome.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800;900&display=swap" rel="stylesheet">
    <link href="../../css/plugins/sweetalert/sweetalert.css" rel="stylesheet">
    <link href="mobile_layout.css" rel="stylesheet">

    <style>
        /* ---- Profile Hero Card ---- */
        .prof-hero-card {
            background: linear-gradient(135deg, #4f46e5 0%, #6366f1 50%, #3b82f6 100%);
            border-radius: 20px;
            padding: 24px 20px;
            color: #fff;
            position: relative;
            overflow: hidden;
            box-shadow: 0 10px 25px rgba(79,70,229,0.25);
            display: flex;
            flex-direction: column;
            align-items: center;
            text-align: center;
        }

        .prof-hero-card::before {
            content: '';
            position: absolute;
            width: 260px;
            height: 260px;
            background: radial-gradient(circle, rgba(255,255,255,0.15) 0%, transparent 70%);
            top: -90px;
            right: -70px;
            border-radius: 50%;
            pointer-events: none;
        }

        .prof-avatar-container {
            position: relative;
            margin-bottom: 12px;
            cursor: pointer;
        }

        .prof-avatar-frame {
            width: 88px;
            height: 88px;
            border-radius: 50%;
            border: 4px solid rgba(255, 255, 255, 0.4);
            box-shadow: 0 8px 20px rgba(0, 0, 0, 0.2);
            overflow: hidden;
            background: rgba(255, 255, 255, 0.2);
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 34px;
            font-weight: 900;
            color: #fff;
        }

        .prof-avatar-frame img {
            width: 100%;
            height: 100%;
            object-fit: cover;
        }

        .prof-cam-badge {
            position: absolute;
            bottom: 2px;
            right: 2px;
            width: 28px;
            height: 28px;
            border-radius: 50%;
            background: #ffffff;
            color: #4f46e5;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 13px;
            box-shadow: 0 2px 8px rgba(0,0,0,0.2);
            border: 2px solid #6366f1;
        }

        .prof-hero-name {
            font-size: 20px;
            font-weight: 800;
            margin: 0 0 4px;
            letter-spacing: -0.5px;
            color: #ffffff;
        }

        .prof-hero-email {
            font-size: 12.5px;
            opacity: 0.85;
            font-weight: 500;
            margin: 0 0 10px;
        }

        .prof-badges-row {
            display: flex;
            gap: 8px;
            justify-content: center;
            flex-wrap: wrap;
        }

        .prof-pill-badge {
            display: inline-flex;
            align-items: center;
            gap: 5px;
            background: rgba(255, 255, 255, 0.18);
            backdrop-filter: blur(10px);
            color: #ffffff;
            font-size: 11px;
            font-weight: 700;
            padding: 5px 12px;
            border-radius: 30px;
            border: 1px solid rgba(255, 255, 255, 0.25);
        }

        /* ---- Modern Form Inputs ---- */
        .input-group-modern {
            display: flex;
            flex-direction: column;
            gap: 5px;
        }

        .input-wrapper-icon {
            position: relative;
            display: flex;
            align-items: center;
        }

        .input-wrapper-icon i.field-icon {
            position: absolute;
            left: 14px;
            color: #94a3b8;
            font-size: 14px;
            pointer-events: none;
            transition: color 0.2s;
        }

        .mob-input-custom {
            width: 100%;
            padding: 12px 14px 12px 40px !important;
            border-radius: 12px !important;
            border: 1.5px solid #e2e8f0 !important;
            background: #f8faff !important;
            font-size: 14px !important;
            font-weight: 600 !important;
            color: #0f172a !important;
            font-family: 'Inter', sans-serif !important;
            outline: none !important;
            transition: all 0.2s ease !important;
        }

        .mob-input-custom:focus {
            border-color: #4f46e5 !important;
            box-shadow: 0 0 0 3px rgba(79, 70, 229, 0.12) !important;
            background: #ffffff !important;
        }

        .mob-input-custom:focus + i.field-icon,
        .input-wrapper-icon:focus-within i.field-icon {
            color: #4f46e5 !important;
        }

        /* photo upload preview box */
        #mob-photo-preview {
            background: #f8faff;
            border: 1.5px dashed #cbd5e1;
            border-radius: 14px;
            padding: 16px;
            text-align: center;
        }

        /* Save Button Float */
        #mob-save-floating-bar {
            position: fixed;
            bottom: calc(68px + 12px);
            left: 14px;
            right: 14px;
            z-index: 998;
            display: none;
            animation: slideUpFloat 0.3s ease both;
        }

        @keyframes slideUpFloat {
            from { transform: translateY(20px); opacity: 0; }
            to { transform: translateY(0); opacity: 1; }
        }
    </style>
</head>
<body class="mob-body">

<?php include 'mobile_header.php'; ?>

<div class="mob-page">

    <!-- Profile Hero Card -->
    <div class="prof-hero-card">
        <div class="prof-avatar-container" onclick="$('#mob-photo-input').click();">
            <div class="prof-avatar-frame" id="profile-avatar-wrap">
                <span id="profile-initial"><?php echo $initial; ?></span>
            </div>
            <div class="prof-cam-badge" title="Change Photo">
                <i class="fa fa-camera"></i>
            </div>
        </div>

        <div class="prof-hero-name" id="profile-display-name"><?php echo htmlspecialchars($member_name); ?></div>
        <div class="prof-hero-email"><?php echo htmlspecialchars($email); ?></div>

        <div class="prof-badges-row">
            <span class="prof-pill-badge" style="background:#ffffff; color:#3b82f6; font-weight:800;"><i class="fa fa-id-card-o"></i> Member No: <?php echo getMemberCode($member_id); ?></span>
            <span class="prof-pill-badge"><i class="fa fa-shield"></i> YMCA Active</span>
        </div>
    </div>

    <!-- Hidden Photo Input & Preview Container -->
    <input type="file" id="mob-photo-input" accept="image/*" style="display:none;" onchange="handlePhotoChange(event)">
    <div id="mob-photo-preview" style="display:none;">
        <div style="font-size:12.5px; font-weight:700; color:#475569; margin-bottom:10px;">New Profile Photo Preview</div>
        <img id="mob-photo-img" style="width:90px;height:90px;border-radius:50%;object-fit:cover;border:3px solid #4f46e5;box-shadow:0 4px 12px rgba(0,0,0,0.15);" alt="Preview">
        <div style="margin-top:12px; display:flex; gap:8px; justify-content:center;">
            <button class="mob-btn mob-btn-primary" style="width:auto; padding:8px 20px; font-size:12.5px;" onclick="uploadPhoto()">
                <i class="fa fa-check"></i> Upload Photo
            </button>
            <button class="mob-btn mob-btn-outline" style="width:auto; padding:8px 14px; font-size:12.5px;" onclick="cancelPhotoPreview()">
                <i class="fa fa-times"></i> Cancel
            </button>
        </div>
    </div>

    <!-- Personal Details Card -->
    <div class="mob-card">
        <div class="mob-card-header">
            <div class="mob-card-icon" style="background: linear-gradient(135deg,#4f46e5,#6366f1);"><i class="fa fa-user"></i></div>
            <div class="mob-card-title">Personal Details</div>
        </div>
        <div class="mob-card-body">
            <div class="field-group">
                <div class="input-group-modern">
                    <label class="mob-label">First Name</label>
                    <div class="input-wrapper-icon">
                        <i class="fa fa-user field-icon"></i>
                        <input type="text" class="mob-input-custom" id="p_first_name" placeholder="First Name" oninput="onFieldChange()">
                    </div>
                </div>

                <div class="input-group-modern">
                    <label class="mob-label">Middle Name</label>
                    <div class="input-wrapper-icon">
                        <i class="fa fa-user-o field-icon"></i>
                        <input type="text" class="mob-input-custom" id="p_middle_name" placeholder="Middle Name" oninput="onFieldChange()">
                    </div>
                </div>

                <div class="input-group-modern">
                    <label class="mob-label">Last Name</label>
                    <div class="input-wrapper-icon">
                        <i class="fa fa-user field-icon"></i>
                        <input type="text" class="mob-input-custom" id="p_last_name" placeholder="Last Name" oninput="onFieldChange()">
                    </div>
                </div>

                <div class="input-group-modern">
                    <label class="mob-label">Date of Birth</label>
                    <div class="input-wrapper-icon">
                        <i class="fa fa-calendar field-icon"></i>
                        <input type="date" class="mob-input-custom" id="p_dob" oninput="onFieldChange()">
                    </div>
                </div>

                <div class="input-group-modern">
                    <label class="mob-label">Gender</label>
                    <div class="input-wrapper-icon">
                        <i class="fa fa-venus-mars field-icon"></i>
                        <select class="mob-input-custom" id="p_gender" onchange="onFieldChange()">
                            <option value="">Select Gender</option>
                            <option value="Male">Male</option>
                            <option value="Female">Female</option>
                            <option value="Other">Other</option>
                        </select>
                    </div>
                </div>

                <div class="input-group-modern">
                    <label class="mob-label">Blood Group</label>
                    <div class="input-wrapper-icon">
                        <i class="fa fa-tint field-icon" style="color:#ef4444;"></i>
                        <select class="mob-input-custom" id="p_blood_group" onchange="onFieldChange()">
                            <option value="">Select Blood Group</option>
                            <option>A+</option><option>A-</option>
                            <option>B+</option><option>B-</option>
                            <option>AB+</option><option>AB-</option>
                            <option>O+</option><option>O-</option>
                        </select>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Family Details Card -->
    <div class="mob-card">
        <div class="mob-card-header">
            <div class="mob-card-icon" style="background:linear-gradient(135deg,#10b981,#059669);"><i class="fa fa-users"></i></div>
            <div class="mob-card-title">Family Details</div>
        </div>
        <div class="mob-card-body">
            <div class="field-group">
                <div class="input-group-modern">
                    <label class="mob-label">Father's Name</label>
                    <div class="input-wrapper-icon">
                        <i class="fa fa-male field-icon"></i>
                        <input type="text" class="mob-input-custom" id="p_father_name" placeholder="Father's Name" oninput="onFieldChange()">
                    </div>
                </div>
                <div class="input-group-modern">
                    <label class="mob-label">Mother's Name</label>
                    <div class="input-wrapper-icon">
                        <i class="fa fa-female field-icon"></i>
                        <input type="text" class="mob-input-custom" id="p_mother_name" placeholder="Mother's Name" oninput="onFieldChange()">
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Contact Details Card -->
    <div class="mob-card">
        <div class="mob-card-header">
            <div class="mob-card-icon" style="background:linear-gradient(135deg,#f59e0b,#d97706);"><i class="fa fa-phone"></i></div>
            <div class="mob-card-title">Contact Information</div>
        </div>
        <div class="mob-card-body">
            <div class="field-group">
                <div class="input-group-modern">
                    <label class="mob-label">Phone Number</label>
                    <div class="input-wrapper-icon">
                        <i class="fa fa-phone field-icon"></i>
                        <input type="tel" class="mob-input-custom" id="p_phone" placeholder="Phone Number" oninput="onFieldChange()">
                    </div>
                </div>

                <div class="input-group-modern">
                    <label class="mob-label">WhatsApp Number</label>
                    <div class="input-wrapper-icon">
                        <i class="fa fa-whatsapp field-icon" style="color:#25d366;"></i>
                        <input type="tel" class="mob-input-custom" id="p_whatsapp" placeholder="WhatsApp Number" oninput="onFieldChange()">
                    </div>
                </div>

                <div class="input-group-modern">
                    <label class="mob-label">Email Address</label>
                    <div class="input-wrapper-icon">
                        <i class="fa fa-envelope field-icon"></i>
                        <input type="email" class="mob-input-custom" id="p_email" placeholder="Email Address" oninput="onFieldChange()">
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Address Details Card -->
    <div class="mob-card">
        <div class="mob-card-header">
            <div class="mob-card-icon" style="background:linear-gradient(135deg,#8b5cf6,#7c3aed);"><i class="fa fa-map-marker"></i></div>
            <div class="mob-card-title">Permanent Address</div>
        </div>
        <div class="mob-card-body">
            <div class="field-group">
                <div class="input-group-modern">
                    <label class="mob-label">Street / Area</label>
                    <div class="input-wrapper-icon">
                        <i class="fa fa-home field-icon"></i>
                        <input type="text" class="mob-input-custom" id="p_street" placeholder="Street / Area Name" oninput="onFieldChange()">
                    </div>
                </div>

                <div class="input-group-modern">
                    <label class="mob-label">City</label>
                    <div class="input-wrapper-icon">
                        <i class="fa fa-building field-icon"></i>
                        <input type="text" class="mob-input-custom" id="p_city" placeholder="City" oninput="onFieldChange()">
                    </div>
                </div>

                <div class="input-group-modern">
                    <label class="mob-label">Pincode</label>
                    <div class="input-wrapper-icon">
                        <i class="fa fa-map-pin field-icon"></i>
                        <input type="text" class="mob-input-custom" id="p_pincode" placeholder="Pincode" oninput="onFieldChange()">
                    </div>
                </div>

                <div class="input-group-modern">
                    <label class="mob-label">Country</label>
                    <div class="input-wrapper-icon">
                        <i class="fa fa-globe field-icon"></i>
                        <input type="text" class="mob-input-custom" id="p_country" placeholder="Country Name" oninput="onFieldChange()">
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Logout Button -->
    <div style="margin-top:20px; margin-bottom: 20px; text-align:center;">
        <a href="../../app_login_manager/logout.php" class="mob-btn" style="background:#fee2e2; color:#dc2626; border:1.5px solid #fca5a5; font-weight:800; display:flex; align-items:center; justify-content:center; gap:8px; width:100%; border-radius:14px; padding:12px;">
            <i class="fa fa-sign-out" style="font-size:16px;"></i> Log Out of Account
        </a>
    </div>

    <!-- Spacer for floating save button -->
    <div style="height: 30px;"></div>

</div><!-- /.mob-page -->

<!-- Floating Save Changes Button Bar -->
<div id="mob-save-floating-bar">
    <button class="mob-btn mob-btn-primary" onclick="saveProfile()">
        <i class="fa fa-check"></i> Save Profile Changes
    </button>
</div>

<?php include 'mobile_bottom_nav.php'; ?>

<script src="https://cdnjs.cloudflare.com/ajax/libs/jquery/3.6.0/jquery.min.js"></script>
<script src="../../js/plugins/sweetalert/sweetalert.min.js"></script>
<script src="../../app_js/sweetalert-finez.js"></script>

<script>
var profileChanged = false;
var profileData    = {};
var photoFile      = null;

$(document).ready(function(){
    loadProfileData();
});

function loadProfileData(){
    $.post('../api/profile.php', { action: 'load_profile_data', page: 1 }, function(data){
        try {
            const obj = JSON.parse(data);
            const member = obj[1] && obj[1][0] ? obj[1][0] : null;
            if(!member) return;

            profileData = member;

            $('#p_first_name').val(member.first_name   || '');
            $('#p_middle_name').val(member.middle_name || '');
            $('#p_last_name').val(member.last_name     || '');
            $('#p_dob').val(member.dob                 || '');
            $('#p_gender').val(member.gender           || '');
            $('#p_blood_group').val(member.blood_group || '');
            $('#p_father_name').val(member.father_name || '');
            $('#p_mother_name').val(member.mother_name || '');
            $('#p_phone').val(member.phone             || '');
            $('#p_whatsapp').val(member.whtsapp        || '');
            $('#p_email').val(member.email             || '');
            $('#p_street').val(member.street           || '');
            $('#p_city').val(member.city               || '');
            $('#p_pincode').val(member.pincode         || '');
            $('#p_country').val(member.country         || '');

            // Avatar photo display
            const initialText = ((member.first_name || '<?php echo $initial; ?>').charAt(0)).toUpperCase();
            if(member.img && member.img != '0' && member.img !== 0 && member.img !== 'customer.png'){
                const imgSrc = '../../image_upload/members/thumbnails/' + member.img;
                const fallbackSrc = '../../image_upload/members/uploads/' + member.img;
                $('#profile-avatar-wrap').html('<img src="'+imgSrc+'" id="mob-photo-img-tag">');
                $('#mob-photo-img-tag').on('error', function(){
                    if($(this).attr('src') !== fallbackSrc){
                        $(this).attr('src', fallbackSrc);
                    } else {
                        $('#profile-avatar-wrap').html('<span id="profile-initial">'+initialText+'</span>');
                    }
                });
            } else {
                $('#profile-avatar-wrap').html('<span id="profile-initial">'+initialText+'</span>');
            }

            // Display name
            const fullName = [member.first_name, member.middle_name, member.last_name].filter(Boolean).join(' ');
            if(fullName) $('#profile-display-name').text(fullName);

        } catch(e){}
    });
}

function onFieldChange(){
    profileChanged = true;
    $('#mob-save-floating-bar').fadeIn(220);
}

function saveProfile(){
    const payload = {
        action:       'save_profile',
        id:           profileData.id || '<?php echo (int)$member_id; ?>',
        first_name:   $('#p_first_name').val(),
        middle_name:  $('#p_middle_name').val(),
        last_name:    $('#p_last_name').val(),
        dob:          $('#p_dob').val(),
        gender:       $('#p_gender').val(),
        blood_group:  $('#p_blood_group').val(),
        father_name:  $('#p_father_name').val(),
        mother_name:  $('#p_mother_name').val(),
        phone:        $('#p_phone').val(),
        whtsapp:      $('#p_whatsapp').val(),
        email:        $('#p_email').val(),
        street:       $('#p_street').val(),
        city:         $('#p_city').val(),
        pincode:      $('#p_pincode').val(),
        country:      $('#p_country').val(),
        p_street:     $('#p_street').val(),
        p_city:       $('#p_city').val(),
        p_pincode:    $('#p_pincode').val(),
        p_country:    $('#p_country').val(),
        img:          profileData.img || ''
    };

    $.post('../api/profile.php', payload, function(data){
        let isSuccess = false;
        let msg = 'Profile updated successfully.';

        if (typeof data === 'object' && data !== null) {
            if (data.status === 'success' || data[0] === 'success') {
                isSuccess = true;
            } else {
                msg = data.message || data[1] || 'Could not save profile.';
            }
        } else if (typeof data === 'string') {
            const trimmed = data.trim();
            if (trimmed === 'Saved Successfully' || trimmed.toLowerCase().includes('saved')) {
                isSuccess = true;
            } else {
                try {
                    const res = JSON.parse(trimmed);
                    if (res.status === 'success' || res[0] === 'success') {
                        isSuccess = true;
                    } else {
                        msg = res.message || res[1] || 'Could not save profile.';
                    }
                } catch(e) {
                    msg = trimmed || 'Could not save profile.';
                }
            }
        }

        if (isSuccess) {
            swal('Saved!', msg, 'success');
            $('#mob-save-floating-bar').fadeOut(200);
            profileChanged = false;
            const fullName = [payload.first_name, payload.middle_name, payload.last_name].filter(Boolean).join(' ');
            if (fullName) $('#profile-display-name').text(fullName);
        } else {
            swal('Error', msg, 'error');
        }
    }).fail(function(xhr){
        swal('Error', 'Server connection failed. ' + (xhr.responseText || ''), 'error');
    });
}

// Photo preview & upload handlers
function handlePhotoChange(event){
    const file = event.target.files[0];
    if(!file) return;
    photoFile = file;
    const reader = new FileReader();
    reader.onload = function(e){
        $('#mob-photo-img').attr('src', e.target.result);
        $('#mob-photo-preview').slideDown(200);
    };
    reader.readAsDataURL(file);
}

function cancelPhotoPreview(){
    $('#mob-photo-preview').slideUp(180);
    $('#mob-photo-input').val('');
    photoFile = null;
}

function uploadPhoto(){
    if(!photoFile) return;
    const formData = new FormData();
    formData.append('croppedImage', photoFile);

    $.ajax({
        url: '../../image_upload/members/upload.php',
        type: 'POST',
        data: formData,
        contentType: false,
        processData: false,
        success: function(data){
            try {
                let res = data;
                if(typeof data === 'string'){
                    res = JSON.parse(data);
                }
                if(res.status === 'success' || res.success){
                    swal('Done!', 'Photo updated successfully.', 'success');
                    if(res.filename){
                        profileData.img = res.filename;
                    }
                    const newImg = $('#mob-photo-img').attr('src');
                    $('#profile-avatar-wrap').html('<img src="'+newImg+'" style="width:100%;height:100%;object-fit:cover;">');
                    cancelPhotoPreview();
                    saveProfile();
                } else {
                    swal('Error', res.message || 'Photo upload failed.', 'error');
                }
            } catch(e){
                swal('Error', 'Server error during photo upload.', 'error');
            }
        },
        error: function(){
            swal('Error', 'Failed to upload photo.', 'error');
        }
    });
}
</script>

</body>
</html>
