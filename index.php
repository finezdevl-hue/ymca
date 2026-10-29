<?php
require_once __DIR__ . '/includes/api_auth.php';
require_once __DIR__ . '/includes/auth_security.php';
require_once __DIR__ . '/includes/auth.php';

start_secure_session();

function getRedirectUrlForLogin($login_id, $base_path = './directory/') {
    if (isMobileClient()) {
        return $base_path . 'mobile/home.php';
    }
    if (isSuperAdmin($login_id)) {
        return $base_path . 'dashboard.php';
    }
    if (isGroupAdmin($login_id)) {
        return $base_path . 'group_dashboard.php';
    }
    if (isAttendanceMaster($login_id)) {
        return $base_path . 'attendance.php';
    }
    return $base_path . 'user_attendance.php';
}

if (!empty($_SESSION['login_id'])) {
    header("Location: " . getRedirectUrlForLogin((int)$_SESSION['login_id']));
    exit();
}

// Try secure autologin via cookie (selector/validator)
if (!empty($_COOKIE['remember_user'])) {
    $login_id = verify_and_rotate_remember_cookie();
    if ($login_id) {
        $db = new Database();
        $conn = $db->getConnection();
        $stmt = $conn->prepare("SELECT l.login_id, l.name, m.id AS user_id, l.email FROM tbl_login as l LEFT JOIN tbl_members AS m ON l.email=m.email WHERE l.login_id = ?");
        $stmt->bind_param("i", $login_id);
        $stmt->execute();
        $result = $stmt->get_result();
        if ($result && $row = $result->fetch_assoc()) {
            session_regenerate_id(true);
            $_SESSION['name']          = $row['name'];
            $_SESSION['email']         = $row['email'];
            $_SESSION['id']            = (int)$row["login_id"];
            $_SESSION['login_id']      = (int)$row["login_id"];
            $_SESSION['user_id']       = !empty($row["user_id"]) ? (int)$row["user_id"] : 0;
            $_SESSION['primary_role']  = getUserPrimaryRoleName($login_id);
            $_SESSION['allowed_groups'] = getUserAllowedGroupIds($login_id);
            
            header("Location: " . getRedirectUrlForLogin((int)$_SESSION['login_id']));
            exit();
        }
    }
}

$login_error = '';
if (isset($_GET['error'])) {
    if ($_GET['error'] === 'invalid') {
        $login_error = 'Invalid email or password. Please try again.';
    } elseif ($_GET['error'] === 'locked') {
        $login_error = 'Too many failed login attempts. Please wait 15 minutes before trying again.';
    } elseif ($_GET['error'] === 'server') {
        $login_error = 'Something went wrong on our end. Please try again.';
    }
}

$login_success = '';
if (isset($_GET['reset']) && $_GET['reset'] === 'success') {
    $login_success = 'Your password has been reset successfully. Please sign in with your new credentials.';
}
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>YMCA BCP | Login</title>
    <meta name="description" content="Sign in to YMCA BCP Member Management System">
    <link rel="shortcut icon" href="favicon.ico" type="image/x-icon">
    <link rel="manifest" href="manifest.json">

    <link href="./css/bootstrap.min.css" rel="stylesheet">
    <link href="./font-awesome/css/font-awesome.css" rel="stylesheet">
    <link href="./css/animate.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&family=Plus+Jakarta+Sans:wght@400;500;600;700;800&display=swap" rel="stylesheet">

    <style>
        *, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }

        body {
            font-family: 'Inter', sans-serif;
            height: 100vh;
            overflow: hidden;
            background: #0a0f1e;
        }

        /* ── Animated background ── */
        .bg-canvas {
            position: fixed;
            inset: 0;
            background: linear-gradient(135deg, #0a0f1e 0%, #0f172a 50%, #0d1b3e 100%);
            z-index: 0;
        }

        .orb {
            position: absolute;
            border-radius: 50%;
            filter: blur(80px);
            opacity: 0.25;
            animation: float 8s ease-in-out infinite;
        }

        .orb-1 {
            width: 500px; height: 500px;
            background: radial-gradient(circle, #3b82f6, #1d4ed8);
            top: -150px; left: -100px;
            animation-delay: 0s;
        }

        .orb-2 {
            width: 400px; height: 400px;
            background: radial-gradient(circle, #8b5cf6, #6d28d9);
            bottom: -100px; right: -80px;
            animation-delay: -3s;
        }

        .orb-3 {
            width: 300px; height: 300px;
            background: radial-gradient(circle, #06b6d4, #0284c7);
            top: 50%; left: 55%;
            animation-delay: -5s;
        }

        @keyframes float {
            0%, 100% { transform: translate(0, 0) scale(1); }
            33% { transform: translate(20px, -30px) scale(1.05); }
            66% { transform: translate(-15px, 20px) scale(0.95); }
        }

        /* ── Layout ── */
        .login-wrapper {
            position: relative;
            z-index: 1;
            display: flex;
            height: 100vh;
        }

        /* ── Left Panel ── */
        .left-panel {
            flex: 1;
            display: flex;
            flex-direction: column;
            justify-content: center;
            align-items: flex-start;
            padding: 60px 70px;
            position: relative;
        }

        .brand-badge {
            display: inline-flex;
            align-items: center;
            gap: 10px;
            background: rgba(59, 130, 246, 0.1);
            border: 1px solid rgba(59, 130, 246, 0.25);
            border-radius: 50px;
            padding: 8px 18px;
            margin-bottom: 36px;
        }

        .brand-badge-dot {
            width: 8px; height: 8px;
            border-radius: 50%;
            background: #3b82f6;
            animation: pulse-dot 2s ease-in-out infinite;
        }

        @keyframes pulse-dot {
            0%, 100% { opacity: 1; transform: scale(1); }
            50% { opacity: 0.5; transform: scale(0.8); }
        }

        .brand-badge span {
            color: #93c5fd;
            font-size: 13px;
            font-weight: 600;
            letter-spacing: 0.05em;
            text-transform: uppercase;
        }

        .hero-title {
            font-family: 'Plus Jakarta Sans', sans-serif;
            font-size: clamp(2.5rem, 4vw, 3.8rem);
            font-weight: 800;
            color: #ffffff;
            line-height: 1.15;
            margin-bottom: 20px;
            letter-spacing: -0.02em;
        }

        .hero-title .gradient-text {
            background: linear-gradient(135deg, #60a5fa 0%, #a78bfa 50%, #34d399 100%);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
        }

        .hero-subtitle {
            font-size: 1.05rem;
            color: #64748b;
            line-height: 1.7;
            max-width: 440px;
            margin-bottom: 48px;
        }

        /* Stats row */
        .stats-row {
            display: flex;
            gap: 32px;
        }

        .stat-item {
            text-align: left;
        }

        .stat-value {
            font-size: 1.8rem;
            font-weight: 800;
            color: #ffffff;
            font-family: 'Plus Jakarta Sans', sans-serif;
            line-height: 1;
        }

        .stat-label {
            font-size: 0.78rem;
            color: #475569;
            font-weight: 500;
            text-transform: uppercase;
            letter-spacing: 0.08em;
            margin-top: 4px;
        }

        .stat-divider {
            width: 1px;
            background: rgba(255,255,255,0.06);
        }

        /* ── Right Panel ── */
        .right-panel {
            width: 480px;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 40px 48px;
        }

        .login-card {
            width: 100%;
            background: rgba(15, 23, 42, 0.7);
            backdrop-filter: blur(24px);
            -webkit-backdrop-filter: blur(24px);
            border: 1px solid rgba(255, 255, 255, 0.07);
            border-radius: 24px;
            padding: 44px 40px;
            box-shadow:
                0 0 0 1px rgba(255,255,255,0.03) inset,
                0 32px 64px -12px rgba(0, 0, 0, 0.6);
            animation: slideUp 0.6s ease both;
        }

        @keyframes slideUp {
            from { opacity: 0; transform: translateY(30px); }
            to { opacity: 1; transform: translateY(0); }
        }

        .card-eyebrow {
            font-size: 11px;
            font-weight: 700;
            color: #3b82f6;
            letter-spacing: 0.12em;
            text-transform: uppercase;
            margin-bottom: 10px;
        }

        .card-title {
            font-family: 'Plus Jakarta Sans', sans-serif;
            font-size: 1.75rem;
            font-weight: 800;
            color: #f1f5f9;
            margin-bottom: 6px;
            letter-spacing: -0.02em;
        }

        .card-subtitle {
            font-size: 0.875rem;
            color: #475569;
            margin-bottom: 36px;
        }

        /* ── Form ── */
        .field-label {
            display: block;
            font-size: 12px;
            font-weight: 600;
            color: #94a3b8;
            letter-spacing: 0.06em;
            text-transform: uppercase;
            margin-bottom: 8px;
        }

        .field-wrap {
            position: relative;
            margin-bottom: 20px;
        }

        .field-wrap input {
            width: 100%;
            background: rgba(15, 23, 42, 0.8) !important;
            border: 1.5px solid rgba(255, 255, 255, 0.07) !important;
            border-radius: 12px !important;
            padding: 13px 16px 13px 46px !important;
            color: #f1f5f9 !important;
            font-size: 0.93rem !important;
            font-family: 'Inter', sans-serif !important;
            outline: none !important;
            transition: all 0.25s ease !important;
            height: auto !important;
            box-shadow: 0 2px 4px rgba(0,0,0,0.2) inset !important;
        }

        .field-wrap input::placeholder {
            color: #334155 !important;
        }

        .field-wrap input:focus {
            border-color: #3b82f6 !important;
            box-shadow: 0 0 0 4px rgba(59, 130, 246, 0.12), 0 2px 4px rgba(0,0,0,0.2) inset !important;
            background: rgba(15, 23, 42, 0.95) !important;
        }

        .field-icon {
            position: absolute;
            left: 15px;
            top: 50%;
            transform: translateY(-50%);
            color: #334155;
            font-size: 1rem;
            pointer-events: none;
            transition: color 0.25s;
        }

        .field-wrap input:focus ~ .field-icon {
            color: #3b82f6;
        }

        /* Toggle password visibility */
        .toggle-pw {
            position: absolute;
            right: 14px;
            top: 50%;
            transform: translateY(-50%);
            color: #334155;
            cursor: pointer;
            font-size: 0.95rem;
            transition: color 0.2s;
        }

        .toggle-pw:hover { color: #60a5fa; }

        /* Remember me */
        .remember-row {
            display: flex;
            align-items: center;
            justify-content: space-between;
            margin-bottom: 28px;
        }

        .remember-check {
            display: flex;
            align-items: center;
            gap: 8px;
            cursor: pointer;
        }

        .remember-check input[type="checkbox"] {
            width: 16px !important;
            height: 16px !important;
            accent-color: #3b82f6;
            cursor: pointer;
        }

        .remember-check label {
            font-size: 0.83rem;
            color: #64748b;
            cursor: pointer;
            margin: 0;
        }

        .forgot-link {
            font-size: 0.83rem;
            color: #3b82f6;
            text-decoration: none;
            font-weight: 500;
            transition: color 0.2s;
        }

        .forgot-link:hover { color: #60a5fa; text-decoration: none; }

        /* Submit button */
        .btn-signin {
            width: 100%;
            background: linear-gradient(135deg, #2563eb 0%, #4f46e5 100%);
            border: none;
            border-radius: 12px;
            padding: 14px;
            color: #ffffff;
            font-size: 0.95rem;
            font-weight: 700;
            font-family: 'Inter', sans-serif;
            cursor: pointer;
            letter-spacing: 0.02em;
            box-shadow: 0 4px 16px rgba(79, 70, 229, 0.35);
            transition: all 0.25s ease;
            position: relative;
            overflow: hidden;
        }

        .btn-signin::before {
            content: '';
            position: absolute;
            inset: 0;
            background: linear-gradient(135deg, rgba(255,255,255,0.1), transparent);
            opacity: 0;
            transition: opacity 0.25s;
        }

        .btn-signin:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 24px rgba(79, 70, 229, 0.5);
        }

        .btn-signin:hover::before { opacity: 1; }

        .btn-signin:active { transform: translateY(0); }

        /* Footer text */
        .card-footer-text {
            text-align: center;
            margin-top: 28px;
            font-size: 0.78rem;
            color: #1e293b;
        }

        .card-footer-text span {
            display: inline-flex;
            align-items: center;
            gap: 5px;
            color: #334155;
        }

        .card-footer-text i {
            color: #1d4ed8;
        }

        /* ── Responsive ── */
        @media (max-width: 900px) {
            .left-panel { display: none; }
            .right-panel {
                width: 100%;
                padding: 24px 20px;
            }
            .login-card { padding: 36px 28px; }
        }

        .view-pane {
            animation: fadeInView 0.3s cubic-bezier(0.16, 1, 0.3, 1) both;
        }

        @keyframes fadeInView {
            from { opacity: 0; transform: translateY(12px); }
            to { opacity: 1; transform: translateY(0); }
        }
    </style>
</head>

<body>

    <!-- Animated background -->
    <div class="bg-canvas">
        <div class="orb orb-1"></div>
        <div class="orb orb-2"></div>
        <div class="orb orb-3"></div>
    </div>

    <div class="login-wrapper">

        <!-- ── Left Branding Panel ── -->
        <div class="left-panel animated fadeInLeft">
            <div class="brand-badge">
                <div class="brand-badge-dot"></div>
                <span>Member Management</span>
            </div>

            <h1 class="hero-title">
                Welcome to<br>
                <span class="gradient-text">YMCA BCP</span>
            </h1>

            <p class="hero-subtitle">
                Your all-in-one platform to manage members, track attendance, process payments, and generate insightful reports — all from one place.
            </p>

            <div class="stats-row">
                <div class="stat-item">
                    <div class="stat-value">100%</div>
                    <div class="stat-label">Secure</div>
                </div>
                <div class="stat-divider"></div>
                <div class="stat-item">
                    <div class="stat-value">Live</div>
                    <div class="stat-label">Attendance</div>
                </div>
                <div class="stat-divider"></div>
                <div class="stat-item">
                    <div class="stat-value">24/7</div>
                    <div class="stat-label">Access</div>
                </div>
            </div>
        </div>

        <!-- ── Right Login Panel ── -->
        <div class="right-panel">
            <div class="login-card">

                <!-- ── Sign In View ── -->
                <div id="signin-view" class="view-pane">
                    <div class="card-eyebrow">YMCA BCP Portal</div>
                    <div class="card-title">Sign In</div>
                    <div class="card-subtitle">Enter your credentials to continue</div>

                    <?php if (!empty($login_success)) { ?>
                    <div id="login-success-alert" style="
                        display: flex;
                        align-items: center;
                        gap: 10px;
                        background: rgba(16, 185, 129, 0.12);
                        border: 1px solid rgba(16, 185, 129, 0.3);
                        border-radius: 10px;
                        padding: 12px 14px;
                        margin-bottom: 20px;
                        animation: slideUp 0.4s ease both;
                    ">
                        <i class="fa fa-check-circle" style="color: #34d399; font-size: 1rem; flex-shrink: 0;"></i>
                        <span style="color: #a7f3d0; font-size: 0.85rem; font-weight: 500;"><?php echo htmlspecialchars($login_success); ?></span>
                    </div>
                    <?php } ?>

                    <?php if (!empty($login_error)) { ?>
                    <div id="login-error-alert" style="
                        display: flex;
                        align-items: center;
                        gap: 10px;
                        background: rgba(239, 68, 68, 0.1);
                        border: 1px solid rgba(239, 68, 68, 0.25);
                        border-radius: 10px;
                        padding: 12px 14px;
                        margin-bottom: 20px;
                        animation: slideUp 0.4s ease both;
                    ">
                        <i class="fa fa-exclamation-circle" style="color: #f87171; font-size: 1rem; flex-shrink: 0;"></i>
                        <span style="color: #fca5a5; font-size: 0.85rem; font-weight: 500;"><?php echo htmlspecialchars($login_error); ?></span>
                    </div>
                    <?php } ?>

                    <form role="form" action="./app_login_manager/login_validation.php" method="POST" autocomplete="off">

                        <div class="field-wrap">
                            <label class="field-label" for="email">Email Address</label>
                            <input type="email" id="email" name="email" placeholder="you@example.com" required autocomplete="username">
                            <i class="fa fa-envelope field-icon"></i>
                        </div>

                        <div class="field-wrap">
                            <label class="field-label" for="password">Password</label>
                            <input type="password" id="password" name="password" placeholder="••••••••" required autocomplete="current-password">
                            <i class="fa fa-lock field-icon"></i>
                            <i class="fa fa-eye-slash toggle-pw" id="togglePw" title="Show/Hide password"></i>
                        </div>

                        <div class="remember-row">
                            <div class="remember-check">
                                <input type="checkbox" id="remember_me" name="remember_me">
                                <label for="remember_me">Remember me</label>
                            </div>
                            <a class="forgot-link" href="./app_login_manager/forgot_password.php" id="btnForgotView">Forgot password?</a>
                        </div>

                        <button type="submit" class="btn-signin">
                            <i class="fa fa-sign-in" style="margin-right: 8px;"></i> Sign In
                        </button>

                    </form>
                </div>

                <!-- ── In-Page Forgot Password View ── -->
                <div id="forgot-view" class="view-pane" style="display: none;">
                    <div class="card-eyebrow">Account Recovery</div>
                    <div class="card-title">Forgot Password</div>
                    <div class="card-subtitle">Enter your registered email and we'll send a reset link</div>

                    <div id="forgot-alert" style="display: none; margin-bottom: 20px;"></div>

                    <form id="forgotForm" autocomplete="off">
                        <div class="field-wrap">
                            <label class="field-label" for="forgot_email">Email Address</label>
                            <input type="email" id="forgot_email" name="email" placeholder="you@example.com" required autocomplete="email">
                            <i class="fa fa-envelope field-icon"></i>
                        </div>

                        <button type="submit" class="btn-signin" id="btnSubmitForgot">
                            <span id="btnForgotText">
                                <i class="fa fa-paper-plane" style="margin-right: 8px;"></i> Send Reset Link
                            </span>
                        </button>

                        <div style="text-align: center; margin-top: 24px;">
                            <a href="#" id="btnBackToLogin" class="forgot-link" style="display: inline-flex; align-items: center; gap: 8px;">
                                <i class="fa fa-arrow-left"></i> Back to Sign In
                            </a>
                        </div>
                    </form>
                </div>

                <div class="card-footer-text">
                    <span><i class="fa fa-shield"></i> Secured with encrypted authentication</span>
                </div>

            </div>
        </div>

    </div>

    <script src="./js/jquery-3.1.1.min.js"></script>
    <script src="./js/bootstrap.min.js"></script>

    <script>
        // Toggle password visibility
        const togglePw = document.getElementById('togglePw');
        if (togglePw) {
            togglePw.addEventListener('click', function () {
                const pw = document.getElementById('password');
                const isHidden = pw.type === 'password';
                pw.type = isHidden ? 'text' : 'password';
                this.className = isHidden ? 'fa fa-eye toggle-pw' : 'fa fa-eye-slash toggle-pw';
            });
        }

        // Validate password characters
        const pwInput = document.getElementById('password');
        if (pwInput) {
            pwInput.addEventListener('keyup', function () {
                const pattern = /^[a-zA-Z0-9#@\$]*$/;
                if (!pattern.test(this.value)) {
                    this.value = this.value.replace(/[^a-zA-Z0-9#@\$]/g, '');
                }
            });
        }

        // In-page Forgot Password view toggle
        const signinView = document.getElementById('signin-view');
        const forgotView = document.getElementById('forgot-view');
        const emailInput = document.getElementById('email');
        const forgotEmailInput = document.getElementById('forgot_email');
        const forgotAlert = document.getElementById('forgot-alert');
        const btnForgotView = document.getElementById('btnForgotView');
        const btnBackToLogin = document.getElementById('btnBackToLogin');
        const forgotForm = document.getElementById('forgotForm');

        function showForgotView() {
            if (signinView && forgotView) {
                if (emailInput && emailInput.value.trim() && forgotEmailInput && !forgotEmailInput.value.trim()) {
                    forgotEmailInput.value = emailInput.value.trim();
                }
                signinView.style.display = 'none';
                forgotView.style.display = 'block';
                if (forgotAlert) forgotAlert.style.display = 'none';
                if (forgotEmailInput) forgotEmailInput.focus();
                if (window.location.hash !== '#forgot') {
                    history.pushState(null, null, '#forgot');
                }
            }
        }

        function showSigninView() {
            if (signinView && forgotView) {
                forgotView.style.display = 'none';
                signinView.style.display = 'block';
                if (window.location.hash === '#forgot') {
                    history.pushState(null, null, window.location.pathname + window.location.search);
                }
            }
        }

        if (btnForgotView) {
            btnForgotView.addEventListener('click', function (e) {
                e.preventDefault();
                showForgotView();
            });
        }

        if (btnBackToLogin) {
            btnBackToLogin.addEventListener('click', function (e) {
                e.preventDefault();
                showSigninView();
            });
        }

        window.addEventListener('popstate', function () {
            if (window.location.hash === '#forgot') {
                showForgotView();
            } else {
                showSigninView();
            }
        });

        // Open forgot view directly if requested via URL hash or param
        if (window.location.hash === '#forgot' || new URLSearchParams(window.location.search).get('view') === 'forgot') {
            showForgotView();
        }

        // AJAX submit for Forgot Password
        if (forgotForm) {
            forgotForm.addEventListener('submit', function (e) {
                e.preventDefault();
                const emailVal = forgotEmailInput ? forgotEmailInput.value.trim() : '';
                if (!emailVal) return;

                const btnSubmit = document.getElementById('btnSubmitForgot');
                const btnText = document.getElementById('btnForgotText');
                const originalHtml = btnText ? btnText.innerHTML : 'Send Reset Link';

                if (btnSubmit) btnSubmit.disabled = true;
                if (btnText) btnText.innerHTML = '<i class="fa fa-spinner fa-spin" style="margin-right: 8px;"></i> Sending...';
                if (forgotAlert) forgotAlert.style.display = 'none';

                $.ajax({
                    url: './app_login_manager/forgot_password.php',
                    type: 'POST',
                    data: { email: emailVal, ajax: 1 },
                    dataType: 'json'
                }).done(function (res) {
                    if (btnSubmit) btnSubmit.disabled = false;
                    if (btnText) btnText.innerHTML = originalHtml;

                    if (res && res.status === 'success') {
                        let devHelper = '';
                        if (res.dev_reset_link) {
                            devHelper = `
                                <div style="margin-top: 10px; padding-top: 10px; border-top: 1px solid rgba(16, 185, 129, 0.25); font-size: 0.8rem;">
                                    <span style="opacity: 0.85; color: #6ee7b7;">Development Helper:</span><br>
                                    <a href="${res.dev_reset_link}" style="color: #93c5fd; text-decoration: underline; word-break: break-all;">Click here to reset password directly</a>
                                </div>
                            `;
                        }
                        forgotAlert.innerHTML = `
                            <div style="
                                display: flex;
                                align-items: flex-start;
                                gap: 12px;
                                background: rgba(16, 185, 129, 0.12);
                                border: 1px solid rgba(16, 185, 129, 0.28);
                                border-radius: 12px;
                                padding: 14px 16px;
                                color: #a7f3d0;
                                font-size: 0.86rem;
                                line-height: 1.5;
                                animation: fadeInView 0.3s ease both;
                            ">
                                <i class="fa fa-check-circle" style="color: #34d399; font-size: 1.15rem; margin-top: 2px; flex-shrink: 0;"></i>
                                <div style="flex: 1;">
                                    <div style="font-weight: 600; color: #ecfdf5; margin-bottom: 2px;">Instructions Sent</div>
                                    <div>${res.message || 'Password reset instructions have been sent to your email address.'}</div>
                                    ${devHelper}
                                </div>
                            </div>
                        `;
                        forgotAlert.style.display = 'block';
                    } else {
                        forgotAlert.innerHTML = `
                            <div style="
                                display: flex;
                                align-items: flex-start;
                                gap: 12px;
                                background: rgba(239, 68, 68, 0.12);
                                border: 1px solid rgba(239, 68, 68, 0.28);
                                border-radius: 12px;
                                padding: 14px 16px;
                                color: #fca5a5;
                                font-size: 0.86rem;
                                line-height: 1.5;
                                animation: fadeInView 0.3s ease both;
                            ">
                                <i class="fa fa-exclamation-circle" style="color: #f87171; font-size: 1.15rem; margin-top: 2px; flex-shrink: 0;"></i>
                                <div style="flex: 1;">
                                    <div style="font-weight: 600; color: #fee2e2; margin-bottom: 2px;">Notice</div>
                                    <div>${(res && res.message) ? res.message : 'An error occurred. Please try again.'}</div>
                                </div>
                            </div>
                        `;
                        forgotAlert.style.display = 'block';
                    }
                }).fail(function () {
                    if (btnSubmit) btnSubmit.disabled = false;
                    if (btnText) btnText.innerHTML = originalHtml;
                    forgotAlert.innerHTML = `
                        <div style="
                            display: flex;
                            align-items: flex-start;
                            gap: 12px;
                            background: rgba(239, 68, 68, 0.12);
                            border: 1px solid rgba(239, 68, 68, 0.28);
                            border-radius: 12px;
                            padding: 14px 16px;
                            color: #fca5a5;
                            font-size: 0.86rem;
                            line-height: 1.5;
                            animation: fadeInView 0.3s ease both;
                        ">
                            <i class="fa fa-exclamation-circle" style="color: #f87171; font-size: 1.15rem; margin-top: 2px; flex-shrink: 0;"></i>
                            <div style="flex: 1;">
                                <div style="font-weight: 600; color: #fee2e2; margin-bottom: 2px;">Error</div>
                                <div>Unable to process request. Please check your network and try again.</div>
                            </div>
                        </div>
                    `;
                    forgotAlert.style.display = 'block';
                });
            });
        }

        // Register Service Worker for PWA
        if ('serviceWorker' in navigator) {
            window.addEventListener('load', () => {
                navigator.serviceWorker.register('sw.js')
                    .then(reg => console.log('Service Worker registered', reg))
                    .catch(err => console.log('Service Worker registration failed', err));
            });
        }
    </script>

</body>

</html>
