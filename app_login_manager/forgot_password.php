<?php
require_once __DIR__ . '/../includes/api_auth.php';
require_once __DIR__ . '/../includes/auth_security.php';
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/mailer.php';
require_once __DIR__ . '/../config/env.php';

start_secure_session();

$alertmessage = ""; 
$alert_type = "info";
$reset_link = "";
$is_ajax = (!empty($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest') 
            || !empty($_POST['ajax']) 
            || (isset($_SERVER['HTTP_ACCEPT']) && strpos($_SERVER['HTTP_ACCEPT'], 'application/json') !== false);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = trim($_POST['email'] ?? '');

    if (empty($email) || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $alertmessage = "Please enter a valid email address.";
        $alert_type = "danger";
    } elseif (!check_rate_limit($email, 'forgot_password', 5, 15)) {
        $alertmessage = "Too many password reset requests. Please wait a few minutes before trying again.";
        $alert_type = "danger";
    } else {
        record_failed_attempt($email, 'forgot_password');

        $sql = "SELECT login_id, name, email FROM tbl_login WHERE LTRIM(RTRIM(LOWER(email))) = LTRIM(RTRIM(LOWER(?))) LIMIT 1";
        $result = app_exec_getresult($sql, [$email], "s");

        if ($result && $row = $result->fetch_assoc()) {
            $token = bin2hex(random_bytes(32));
            $expires_at = date("Y-m-d H:i:s", strtotime('+1 hour'));

            $sql_up = "UPDATE tbl_login SET reset_token = ?, reset_token_expires = ? WHERE LTRIM(RTRIM(LOWER(email))) = LTRIM(RTRIM(LOWER(?)))";
            app_exec_nonquery($sql_up, [$token, $expires_at, $email], "sss");

            // Generate reset link using canonical base URL or sanitized host
            $configured_app_url = rtrim(app_env('APP_URL', ''), '/');
            if (!empty($configured_app_url)) {
                $reset_link = $configured_app_url . "/app_login_manager/reset_password.php?token=" . $token;
            } else {
                $protocol = is_request_https() ? "https://" : "http://";
                $host = preg_replace('/[^a-zA-Z0-9.:-]/', '', $_SERVER['HTTP_HOST'] ?? 'localhost');
                $uri = str_replace("forgot_password.php", "reset_password.php", $_SERVER['SCRIPT_NAME'] ?? '');
                $reset_link = $protocol . $host . $uri . "?token=" . $token;
            }

            // Dispatch branded password reset email via SMTP (with fallback)
            send_password_reset_email($email, $row['name'] ?? '', $reset_link);

            $alertmessage = "Password reset instructions have been sent to your email address.";
            $alert_type = "success";
        } else {
            $alertmessage = "No account exists with this email address.";
            $alert_type = "danger";
        }
    }

    if ($is_ajax) {
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode([
            'status' => $alert_type === 'success' ? 'success' : 'error',
            'message' => $alertmessage,
            'dev_reset_link' => (app_env('APP_ENV') === 'development' && !empty($reset_link)) ? $reset_link : null
        ]);
        exit();
    }
}
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>YMCA BCP | Forgot Password</title>
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

        .recovery-card {
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
            margin-bottom: 24px;
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
        <div class="recovery-card">

            <div class="card-eyebrow">Account Recovery</div>
            <h1 class="card-title">Forgot Password</h1>
            <p class="card-subtitle">Enter your registered email address to receive a secure password reset link.</p>

            <?php if (!empty($alertmessage)): ?>
                <div class="alert-box <?= $alert_type === 'success' ? 'success' : 'danger' ?>">
                    <i class="fa <?= $alert_type === 'success' ? 'fa-check-circle' : 'fa-exclamation-circle' ?>" style="font-size: 1.15rem; margin-top: 2px; flex-shrink: 0; color: <?= $alert_type === 'success' ? '#34d399' : '#f87171' ?>;"></i>
                    <div style="flex: 1;">
                        <div style="font-weight: 600; color: <?= $alert_type === 'success' ? '#ecfdf5' : '#fee2e2' ?>; margin-bottom: 2px;">
                            <?= $alert_type === 'success' ? 'Instructions Sent' : 'Notice' ?>
                        </div>
                        <div><?= htmlspecialchars($alertmessage) ?></div>

                        <?php if (app_env('APP_ENV') === 'development' && !empty($reset_link)): ?>
                            <div style="margin-top: 10px; padding-top: 10px; border-top: 1px solid rgba(16, 185, 129, 0.2); font-size: 0.8rem;">
                                <span style="opacity: 0.85;">Development Helper:</span><br>
                                <a href="<?= htmlspecialchars($reset_link) ?>" style="color: #93c5fd; text-decoration: underline; word-break: break-all;">Click here to reset password directly</a>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            <?php endif; ?>

            <form method="POST" action="" autocomplete="off">
                <div class="field-wrap">
                    <label class="field-label" for="email">Email Address</label>
                    <input type="email" id="email" name="email" placeholder="you@example.com" required autocomplete="email">
                    <i class="fa fa-envelope field-icon"></i>
                </div>

                <button type="submit" class="btn-submit">
                    <i class="fa fa-paper-plane" style="margin-right: 8px;"></i> Send Reset Link
                </button>
            </form>

            <div style="text-align: center;">
                <a class="back-link" href="../index.php">
                    <i class="fa fa-arrow-left"></i> Back to Login
                </a>
            </div>

        </div>
    </div>

</body>
</html>
