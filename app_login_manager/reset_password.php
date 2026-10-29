<?php
require_once __DIR__ . '/../includes/api_auth.php';
require_once __DIR__ . '/../includes/auth_security.php';
require_once __DIR__ . '/../includes/db.php';

start_secure_session();

$alertmessage = "";
$alert_type = "danger";
$token = isset($_GET['token']) && is_string($_GET['token']) ? trim($_GET['token']) : '';
$token_valid = false;
$user_id = null;

if (!empty($token)) {
    // Validate token existence and expiration
    $sql = "SELECT login_id, email FROM tbl_login WHERE reset_token = ? AND reset_token_expires > NOW() LIMIT 1";
    $result = app_exec_getresult($sql, [$token], "s");

    if ($result && $row = $result->fetch_assoc()) {
        $token_valid = true;
        $user_id = (int)$row['login_id'];

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $new_password = $_POST['password'] ?? '';
            $con_password = $_POST['con_password'] ?? '';

            if (empty($new_password)) {
                $alertmessage = "Password cannot be empty.";
                $alert_type = "danger";
            } elseif ($new_password !== $con_password) {
                $alertmessage = "Passwords do not match.";
                $alert_type = "danger";
            } elseif (strlen($new_password) < 6) {
                $alertmessage = "Password must be at least 6 characters long.";
                $alert_type = "danger";
            } else {
                try {
                    $hashed_password = password_hash($new_password, PASSWORD_DEFAULT);
                    $sql_up = "UPDATE tbl_login SET password = ?, reset_token = NULL, reset_token_expires = NULL WHERE reset_token = ?";
                    app_exec_nonquery($sql_up, [$hashed_password, $token], "ss");
                    
                    // Revoke any existing remember-me sessions for this user
                    app_exec_nonquery("DELETE FROM tbl_auth_tokens WHERE login_id = ?", [$user_id], "i");

                    $alertmessage = "Password has been reset successfully! You can now sign in with your new password.";
                    $alert_type = "success";
                    $token_valid = false; // Prevent further submission with this token
                } catch (Throwable $e) {
                    $alertmessage = "Failed to reset password. Please try again.";
                    $alert_type = "danger";
                }
            }
        }
    } else {
        $alertmessage = "This password reset link is invalid or has expired.";
        $alert_type = "danger";
    }
} else {
    $alertmessage = "No reset token was provided.";
    $alert_type = "danger";
}
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>YMCA BCP | Reset Password</title>
    <link rel="shortcut icon" href="../favicon.ico" type="image/x-icon">

    <link href="../css/bootstrap.min.css" rel="stylesheet">
    <link href="../font-awesome/css/font-awesome.css" rel="stylesheet">
    <link href="../css/animate.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&family=Plus+Jakarta+Sans:wght@400;500;600;700;800&display=swap" rel="stylesheet">

    <style>
        *, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }

        body {
            font-family: 'Inter', sans-serif;
            min-height: 100vh;
            background: #0a0f1e;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 24px 16px;
            overflow-x: hidden;
        }

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
            width: 450px; height: 450px;
            background: radial-gradient(circle, #3b82f6, #1d4ed8);
            top: -120px; left: -80px;
            animation-delay: 0s;
        }

        .orb-2 {
            width: 380px; height: 380px;
            background: radial-gradient(circle, #8b5cf6, #6d28d9);
            bottom: -80px; right: -60px;
            animation-delay: -3s;
        }

        @keyframes float {
            0%, 100% { transform: translate(0, 0) scale(1); }
            33% { transform: translate(20px, -30px) scale(1.05); }
            66% { transform: translate(-15px, 20px) scale(0.95); }
        }

        .card-container {
            position: relative;
            z-index: 1;
            width: 100%;
            max-width: 460px;
        }

        .reset-card {
            background: rgba(15, 23, 42, 0.75);
            backdrop-filter: blur(24px);
            -webkit-backdrop-filter: blur(24px);
            border: 1px solid rgba(255, 255, 255, 0.08);
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
            color: #64748b;
            margin-bottom: 30px;
            line-height: 1.5;
        }

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
            padding: 13px 46px 13px 46px !important;
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

        .btn-submit {
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
            margin-top: 8px;
        }

        .btn-submit:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 24px rgba(79, 70, 229, 0.5);
        }

        .btn-submit:active { transform: translateY(0); }

        .back-link {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            font-size: 0.88rem;
            color: #3b82f6;
            text-decoration: none;
            font-weight: 500;
            margin-top: 24px;
            transition: color 0.2s;
        }

        .back-link:hover {
            color: #60a5fa;
            text-decoration: none;
        }

        .alert-box {
            display: flex;
            align-items: flex-start;
            gap: 12px;
            border-radius: 12px;
            padding: 14px 16px;
            margin-bottom: 24px;
            font-size: 0.86rem;
            line-height: 1.5;
            animation: slideUp 0.3s ease both;
        }

        .alert-box.success {
            background: rgba(16, 185, 129, 0.12);
            border: 1px solid rgba(16, 185, 129, 0.28);
            color: #a7f3d0;
        }

        .alert-box.danger {
            background: rgba(239, 68, 68, 0.12);
            border: 1px solid rgba(239, 68, 68, 0.28);
            color: #fca5a5;
        }
    </style>
</head>

<body>

    <div class="bg-canvas">
        <div class="orb orb-1"></div>
        <div class="orb orb-2"></div>
    </div>

    <div class="card-container">
        <div class="reset-card">

            <div class="card-eyebrow">Security Credentials</div>
            <h1 class="card-title">Reset Password</h1>
            <p class="card-subtitle">Create a new secure password for your YMCA BCP account.</p>

            <?php if (!empty($alertmessage)): ?>
                <div class="alert-box <?= $alert_type === 'success' ? 'success' : 'danger' ?>">
                    <i class="fa <?= $alert_type === 'success' ? 'fa-check-circle' : 'fa-exclamation-circle' ?>" style="font-size: 1.15rem; margin-top: 2px; flex-shrink: 0; color: <?= $alert_type === 'success' ? '#34d399' : '#f87171' ?>;"></i>
                    <div style="flex: 1;">
                        <div style="font-weight: 600; color: <?= $alert_type === 'success' ? '#ecfdf5' : '#fee2e2' ?>; margin-bottom: 2px;">
                            <?= $alert_type === 'success' ? 'Success' : 'Notice' ?>
                        </div>
                        <div><?= $alertmessage ?></div>
                    </div>
                </div>
            <?php endif; ?>

            <?php if ($token_valid): ?>
            <form method="POST" action="" autocomplete="off" id="resetPwForm">
                <div class="field-wrap">
                    <label class="field-label" for="password">New Password</label>
                    <input type="password" id="password" name="password" placeholder="••••••••" required minlength="6" autocomplete="new-password">
                    <i class="fa fa-lock field-icon"></i>
                    <i class="fa fa-eye-slash toggle-pw" id="togglePw1" title="Show/Hide password"></i>
                </div>

                <div class="field-wrap">
                    <label class="field-label" for="con_password">Confirm New Password</label>
                    <input type="password" id="con_password" name="con_password" placeholder="••••••••" required minlength="6" autocomplete="new-password">
                    <i class="fa fa-lock field-icon"></i>
                    <i class="fa fa-eye-slash toggle-pw" id="togglePw2" title="Show/Hide password"></i>
                </div>

                <button type="submit" class="btn-submit">
                    <i class="fa fa-check" style="margin-right: 8px;"></i> Update Password
                </button>
            </form>
            <?php endif; ?>

            <div style="text-align: center;">
                <a class="back-link" href="../index.php">
                    <i class="fa fa-arrow-left"></i> Back to Login
                </a>
            </div>

        </div>
    </div>

    <script>
        function setupToggle(toggleId, inputId) {
            const toggle = document.getElementById(toggleId);
            const input = document.getElementById(inputId);
            if (!toggle || !input) return;
            toggle.addEventListener('click', function () {
                const isHidden = input.type === 'password';
                input.type = isHidden ? 'text' : 'password';
                this.className = isHidden ? 'fa fa-eye toggle-pw' : 'fa fa-eye-slash toggle-pw';
            });
        }
        setupToggle('togglePw1', 'password');
        setupToggle('togglePw2', 'con_password');

        const form = document.getElementById('resetPwForm');
        if (form) {
            form.addEventListener('submit', function (e) {
                const p1 = document.getElementById('password').value;
                const p2 = document.getElementById('con_password').value;
                if (p1 !== p2) {
                    e.preventDefault();
                    alert('Passwords do not match. Please verify and try again.');
                }
            });
        }
    </script>

</body>
</html>
