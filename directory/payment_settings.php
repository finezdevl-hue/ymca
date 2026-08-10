<?php
session_start();
session_write_close();

if (empty($_SESSION['login_id'])) {
    header("Location: ../app_login_manager/logout.php");
    exit();
}
$is_admin = ($_SESSION['login_id'] == 1);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="description" content="YMCA Payment & UPI Settings">
    <title>YMCA | Payment & UPI Settings</title>

    <link href="../css/bootstrap.min.css" rel="stylesheet">
    <link href="../font-awesome/css/font-awesome.css" rel="stylesheet">
    <link href="../css/animate.css" rel="stylesheet">
    <link href="../css/style.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <link href="../css/plugins/sweetalert/sweetalert.css" rel="stylesheet">
    <script src="https://cdnjs.cloudflare.com/ajax/libs/jquery/3.6.0/jquery.min.js"></script>
    <script src="../js/plugins/sweetalert/sweetalert.min.js"></script>
    <script src="../js/plugins/metisMenu/jquery.metisMenu.js"></script>
    <script src="../js/inspinia.js"></script>
    <script src="../app_js/sweetalert-finez.js"></script>
    <script src="../app_menu/menu.js"></script>

    <style>
        *, *::before, *::after { box-sizing: border-box; }
        body, #wrapper { font-family: 'Inter','Segoe UI',sans-serif !important; background: #f8fafc !important; }

        /* Top Bar */
        .settings-topbar {
            background: #fff;
            border-bottom: 1px solid #e8edf5;
            padding: 0 28px; height: 62px;
            display: flex; align-items: center; justify-content: space-between;
            box-shadow: 0 1px 6px rgba(59,130,246,0.06);
            position: sticky; top: 0; z-index: 100;
        }
        .settings-topbar-left { display: flex; align-items: center; gap: 14px; }
        .settings-hamburger {
            width: 38px; height: 38px; border-radius: 10px;
            background: #f0f4ff; border: 1px solid #dbe6fe; color: #2563eb;
            display: flex; align-items: center; justify-content: center;
            cursor: pointer; transition: all .15s; font-size: 15px; text-decoration: none !important;
        }
        .settings-hamburger:hover { background: #2563eb; color: #fff; border-color: #2563eb; }
        .settings-title-group h1 { font-size: 18px; font-weight: 800; color: #0f172a; margin: 0; }
        .settings-title-group p { font-size: 12px; color: #64748b; margin: 1px 0 0; }

        /* Container */
        .settings-container { max-width: 900px; margin: 28px auto; padding: 0 20px; }
        
        .settings-card {
            background: #ffffff; border-radius: 20px; border: 1px solid #e2e8f0;
            box-shadow: 0 4px 20px rgba(0,0,0,0.04); overflow: hidden; margin-bottom: 24px;
        }
        .settings-card-header {
            padding: 20px 28px; border-bottom: 1px solid #f1f5f9;
            display: flex; align-items: center; justify-content: space-between; background: #fafbfc;
        }
        .settings-card-header-left { display: flex; align-items: center; gap: 14px; }
        .settings-icon-badge {
            width: 44px; height: 44px; border-radius: 12px;
            background: linear-gradient(135deg, #4f46e5, #3b82f6); color: #fff;
            display: flex; align-items: center; justify-content: center; font-size: 18px; font-weight: 700;
            box-shadow: 0 4px 14px rgba(59,130,246,0.3);
        }
        .settings-card-title { font-size: 17px; font-weight: 800; color: #0f172a; margin: 0; }
        .settings-card-sub { font-size: 12px; color: #64748b; margin: 2px 0 0; }

        .settings-card-body { padding: 28px; }

        .form-group-custom { margin-bottom: 22px; }
        .form-label-custom {
            display: block; font-size: 12px; font-weight: 800; color: #475569;
            text-transform: uppercase; letter-spacing: 0.5px; margin-bottom: 8px;
        }
        .form-control-custom {
            width: 100%; height: 48px; border-radius: 12px; border: 1px solid #cbd5e1;
            padding: 0 16px; font-size: 14px; font-weight: 600; color: #0f172a;
            background: #ffffff; transition: all 0.2s; font-family: 'Inter', sans-serif;
        }
        .form-control-custom:focus {
            border-color: #3b82f6; box-shadow: 0 0 0 3px rgba(59,130,246,0.15); outline: none;
        }
        .form-help-text { font-size: 12px; color: #64748b; margin-top: 6px; }

        /* Toggle Switch */
        .toggle-row {
            display: flex; align-items: center; justify-content: space-between;
            background: #f8fafc; border: 1px solid #e2e8f0; border-radius: 14px;
            padding: 16px 20px; margin-bottom: 24px;
        }
        .toggle-info h4 { font-size: 14px; font-weight: 800; color: #0f172a; margin: 0; }
        .toggle-info p { font-size: 12px; color: #64748b; margin: 2px 0 0; }

        .switch-custom { position: relative; display: inline-block; width: 52px; height: 28px; }
        .switch-custom input { opacity: 0; width: 0; height: 0; }
        .slider-custom {
            position: absolute; cursor: pointer; top: 0; left: 0; right: 0; bottom: 0;
            background-color: #cbd5e1; transition: .3s; border-radius: 34px;
        }
        .slider-custom:before {
            position: absolute; content: ""; height: 20px; width: 20px; left: 4px; bottom: 4px;
            background-color: white; transition: .3s; border-radius: 50%;
            box-shadow: 0 2px 4px rgba(0,0,0,0.2);
        }
        input:checked + .slider-custom { background-color: #10b981; }
        input:checked + .slider-custom:before { transform: translateX(24px); }

        .btn-save-settings {
            height: 48px; padding: 0 32px; border-radius: 12px;
            background: linear-gradient(135deg, #4f46e5, #3b82f6); color: #fff;
            font-size: 14px; font-weight: 700; border: none; cursor: pointer;
            display: inline-flex; align-items: center; gap: 8px;
            box-shadow: 0 4px 16px rgba(59,130,246,0.35); transition: all 0.2s;
        }
        .btn-save-settings:hover { transform: translateY(-1px); box-shadow: 0 6px 20px rgba(59,130,246,0.45); }

        /* Preview Card */
        .preview-box {
            background: #f1f5f9; border-radius: 14px; padding: 20px; border: 1px dashed #cbd5e1; margin-top: 24px;
        }
        .preview-title { font-size: 11px; font-weight: 800; color: #64748b; text-transform: uppercase; margin-bottom: 12px; }
    </style>
</head>
<body>

<div id="wrapper">

    <!-- Sidebar Navigation -->
    <nav class="navbar-default navbar-static-side" role="navigation">
        <div class="dropdown profile-element">
            <center>
                <span><img alt="image" class="img-circle" src="../img/customer.png" style="padding-top:20px;"/></span>
                <span class="clear"><span class="block m-t-xs"><strong class="font-bold"><?php echo htmlspecialchars($_SESSION['name'] ?? 'Admin'); ?></strong></span></span>
            </center>
        </div>
        <div class="sidebar-collapse" id="divMenuContainer"></div>
    </nav>

    <!-- Main Content Wrapper -->
    <div id="page-wrapper" class="gray-bg" style="padding:0; min-height:100vh;">

        <!-- Top Navbar -->
        <div class="row border-bottom">
            <nav class="navbar navbar-static-top" role="navigation" style="margin-bottom: 0; background: #fff;">
                <div class="navbar-header">
                    <a class="navbar-minimalize minimalize-styl-2 btn btn-primary" href="#"><i class="fa fa-bars"></i></a>
                </div>
                <ul class="nav navbar-top-links navbar-right">
                    <li>
                        <a href="../app_login_manager/logout.php" style="color: #2563eb; font-weight: 700;">
                            <i class="fa fa-sign-out"></i> Log out
                        </a>
                    </li>
                </ul>
            </nav>
        </div>

        <div class="settings-container">
            <div class="settings-card">
                <div class="settings-card-header">
                    <div class="settings-card-header-left">
                        <div class="settings-icon-badge">
                            <i class="fa fa-credit-card"></i>
                        </div>
                        <div>
                            <h2 class="settings-card-title">UPI Merchant & Payment Gateway</h2>
                            <p class="settings-card-sub">Set up your UPI ID for direct member fee collection</p>
                        </div>
                    </div>
                </div>

                <div class="settings-card-body">
                    <form id="payment_settings_form" onsubmit="saveSettings(event)">
                        
                        <!-- Toggle Switch -->
                        <div class="toggle-row">
                            <div class="toggle-info">
                                <h4>Enable Member UPI Payment Button</h4>
                                <p>Allow members to click "Pay via UPI" and pay directly from their ledger</p>
                            </div>
                            <label class="switch-custom">
                                <input type="checkbox" id="is_active" checked onchange="updatePreview()">
                                <span class="slider-custom"></span>
                            </label>
                        </div>

                        <div class="row">
                            <div class="col-md-6">
                                <div class="form-group-custom">
                                    <label class="form-label-custom">Merchant UPI ID <span style="color:#ef4444;">*</span></label>
                                    <input type="text" class="form-control-custom" id="upi_id" placeholder="e.g. ymcabcp@okaxis" required oninput="updatePreview()">
                                    <div class="form-help-text">Your bank account UPI VPA ID (GPay/PhonePe/Axis/HDFC).</div>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="form-group-custom">
                                    <label class="form-label-custom">Payee / Account Name <span style="color:#ef4444;">*</span></label>
                                    <input type="text" class="form-control-custom" id="payee_name" placeholder="e.g. YMCA BCP Poovathussery" required oninput="updatePreview()">
                                    <div class="form-help-text">Name displayed on member's GPay/PhonePe payment screen.</div>
                                </div>
                            </div>
                        </div>

                        <div class="form-group-custom">
                            <label class="form-label-custom">Payment Description / Note</label>
                            <input type="text" class="form-control-custom" id="payment_note" placeholder="e.g. YMCA Member Fee Payment" oninput="updatePreview()">
                            <div class="form-help-text">Default transaction note sent to UPI apps.</div>
                        </div>

                        <!-- Live Preview -->
                        <div class="preview-box">
                            <div class="preview-title"><i class="fa fa-eye"></i> Live Member Ledger Preview</div>
                            <div style="background:#fff; border-radius:12px; padding:16px; border:1px solid #e2e8f0; display:flex; align-items:center; justify-content:space-between; flex-wrap:wrap; gap:12px;">
                                <div>
                                    <div style="font-size:13px; font-weight:800; color:#0f172a;" id="prev-payee">YMCA BCP Poovathussery</div>
                                    <div style="font-size:11.5px; color:#64748b; margin-top:2px;" id="prev-upi">UPI ID: ymcabcp@okaxis</div>
                                </div>
                                <div id="prev-badge-wrap">
                                    <span style="display:inline-flex; align-items:center; gap:6px; background:linear-gradient(135deg, #4f46e5, #3b82f6); color:#fff; padding:8px 16px; border-radius:20px; font-size:12.5px; font-weight:700; box-shadow:0 3px 10px rgba(59,130,246,0.3);">
                                        <i class="fa fa-mobile" style="font-size:16px;"></i> Pay via UPI
                                    </span>
                                </div>
                            </div>
                        </div>

                        <div style="margin-top: 28px; text-align: right;">
                            <button type="submit" class="btn-save-settings">
                                <i class="fa fa-check-circle"></i> Save Settings
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>

    </div>
</div>

<script>
$(document).ready(function(){
    if (typeof loadMenu === 'function') {
        loadMenu();
    }
    loadSettings();
});

function loadSettings(){
    $.post('api/payment_settings.php', { action: 'get_payment_settings' }, function(response){
        if(response.status === 'success' && response.data){
            const d = response.data;
            $('#upi_id').val(d.upi_id || '');
            $('#payee_name').val(d.payee_name || '');
            $('#payment_note').val(d.payment_note || 'YMCA Member Fee Payment');
            $('#is_active').prop('checked', d.is_active === 1);
            updatePreview();
        }
    });
}

function updatePreview(){
    const upi = $('#upi_id').val() || 'ymcabcp@okaxis';
    const payee = $('#payee_name').val() || 'YMCA BCP Poovathussery';
    const isActive = $('#is_active').is(':checked');

    $('#prev-upi').text('UPI ID: ' + upi);
    $('#prev-payee').text(payee);
    if(isActive){
        $('#prev-badge-wrap').html('<span style="display:inline-flex; align-items:center; gap:6px; background:linear-gradient(135deg, #4f46e5, #3b82f6); color:#fff; padding:8px 16px; border-radius:20px; font-size:12.5px; font-weight:700; box-shadow:0 3px 10px rgba(59,130,246,0.3);"><i class="fa fa-mobile" style="font-size:16px;"></i> Pay via UPI</span>');
    } else {
        $('#prev-badge-wrap').html('<span style="display:inline-flex; align-items:center; gap:6px; background:#f1f5f9; color:#94a3b8; padding:8px 16px; border-radius:20px; font-size:12.5px; font-weight:700; border:1px solid #e2e8f0;"><i class="fa fa-ban"></i> Payments Disabled</span>');
    }
}

function saveSettings(e){
    e.preventDefault();
    const upi_id = $('#upi_id').val().trim();
    const payee_name = $('#payee_name').val().trim();
    const payment_note = $('#payment_note').val().trim();
    const is_active = $('#is_active').is(':checked') ? 1 : 0;

    $.post('api/payment_settings.php', {
        action: 'save_payment_settings',
        upi_id: upi_id,
        payee_name: payee_name,
        payment_note: payment_note,
        is_active: is_active
    }, function(res){
        if(res.status === 'success'){
            swal("Settings Saved!", res.message, "success");
        } else {
            swal("Error", res.message || "Failed to save settings.", "error");
        }
    }).fail(function(xhr){
        var msg = "Server Error";
        try {
            var obj = JSON.parse(xhr.responseText);
            if(obj.message) msg = obj.message;
        } catch(err){}
        swal("Error", msg, "error");
    });
}
</script>

</body>
</html>
