<?php
require_once __DIR__ . '/includes/api_auth.php';
require_once __DIR__ . '/includes/auth_security.php';
require_once __DIR__ . '/includes/auth.php';

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

try {
    start_secure_session();

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
            if ($stmt) {
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
    }
} catch (Throwable $e) {
    // If DB is temporarily unavailable or autologin fails, log and continue to login page
    error_log("Login page autologin check notice: " . $e->getMessage());
}

$login_error = '';
if (isset($_GET['error'])) {
    if ($_GET['error'] === 'invalid') {
        $login_error = 'Invalid email or password. Please try again.';
    } elseif ($_GET['error'] === 'locked') {
        $login_error = 'Too many failed login attempts. Please wait 15 minutes before trying again.';
    } elseif ($_GET['error'] === 'db_connect') {
        $login_error = 'Database connection failed. Please check MySQL settings or .env configuration.';
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
    <title>YMCA BCP | Member &amp; Admin Login</title>
    <meta name="description" content="Sign in to YMCA BCP Badminton Club Management ERP">
    <link rel="canonical" href="https://ymca.finez.in/">
    <?php if (!empty($_GET['error']) || !empty($_GET['reset'])): ?>
    <meta name="robots" content="noindex, nofollow">
    <?php else: ?>
    <meta name="robots" content="index, follow">
    <?php endif; ?>
    <link rel="shortcut icon" href="favicon.ico" type="image/x-icon">
    <link rel="icon" type="image/png" sizes="192x192" href="favicon.png">
    <link rel="apple-touch-icon" href="favicon.png">
    <link rel="manifest" href="manifest.json">
    <meta name="theme-color" content="#0a0f1e">

    <link href="./css/bootstrap.min.css" rel="stylesheet">
    <link href="./font-awesome/css/font-awesome.css" rel="stylesheet">
    <link href="./css/animate.css" rel="stylesheet">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">

    <style>
        *, *::before, *::after {
            box-sizing: border-box;
            margin: 0;
            padding: 0;
        }

        :root {
            --primary: #2563eb;
            --primary-glow: rgba(37, 99, 235, 0.45);
            --accent: #06b6d4;
            --accent-purple: #8b5cf6;
            --emerald: #10b981;
            --dark-bg: #070b14;
            --card-bg: rgba(13, 20, 36, 0.82);
            --card-border: rgba(255, 255, 255, 0.08);
            --input-bg: rgba(9, 14, 26, 0.88);
            --text-main: #f8fafc;
            --text-muted: #94a3b8;
            --text-dim: #64748b;
        }

        html, body {
            height: 100%;
            overflow-x: hidden;
            font-family: 'Inter', sans-serif;
            background-color: var(--dark-bg);
            color: var(--text-main);
            -webkit-font-smoothing: antialiased;
        }

        /* ── Dynamic Mesh & Ambient Background ── */
        .bg-canvas {
            position: fixed;
            inset: 0;
            z-index: 0;
            background: 
                radial-gradient(circle at 15% 20%, rgba(37, 99, 235, 0.18) 0%, transparent 40%),
                radial-gradient(circle at 85% 80%, rgba(139, 92, 246, 0.15) 0%, transparent 45%),
                radial-gradient(circle at 50% 50%, rgba(6, 182, 212, 0.1) 0%, transparent 50%),
                linear-gradient(145deg, #060911 0%, #0a0f1d 50%, #0d1629 100%);
            overflow: hidden;
        }

        /* Subtle grid overlay */
        .bg-grid {
            position: absolute;
            inset: 0;
            background-image: 
                radial-gradient(rgba(255, 255, 255, 0.06) 1px, transparent 1px);
            background-size: 32px 32px;
            opacity: 0.6;
            mask-image: radial-gradient(ellipse at center, rgba(0,0,0,1) 30%, rgba(0,0,0,0) 80%);
            -webkit-mask-image: radial-gradient(ellipse at center, rgba(0,0,0,1) 30%, rgba(0,0,0,0) 80%);
        }

        .orb {
            position: absolute;
            border-radius: 50%;
            filter: blur(90px);
            opacity: 0.35;
            animation: orbFloat 14s ease-in-out infinite alternate;
        }

        .orb-1 {
            width: 520px; height: 520px;
            background: radial-gradient(circle, #2563eb, #1e40af);
            top: -120px; left: -100px;
            animation-duration: 16s;
        }

        .orb-2 {
            width: 440px; height: 440px;
            background: radial-gradient(circle, #7c3aed, #4c1d95);
            bottom: -120px; right: -80px;
            animation-duration: 18s;
            animation-delay: -4s;
        }

        .orb-3 {
            width: 320px; height: 320px;
            background: radial-gradient(circle, #0284c7, #0d9488);
            top: 45%; left: 45%;
            animation-duration: 20s;
            animation-delay: -8s;
        }

        @keyframes orbFloat {
            0%   { transform: translate(0, 0) scale(1); }
            50%  { transform: translate(30px, -40px) scale(1.08); }
            100% { transform: translate(-25px, 30px) scale(0.96); }
        }

        /* ── Main Layout ── */
        .login-wrapper {
            position: relative;
            z-index: 1;
            display: flex;
            min-height: 100vh;
            width: 100%;
        }

        /* ── Left Branding Hero Panel ── */
        .left-panel {
            flex: 1.15;
            display: flex;
            flex-direction: column;
            justify-content: space-between;
            padding: 56px 64px;
            position: relative;
        }

        .left-panel-content {
            margin-top: auto;
            margin-bottom: auto;
            max-width: 580px;
        }

        /* Header Brand with Logo */
        .brand-header {
            display: inline-flex;
            align-items: center;
            gap: 16px;
            margin-bottom: 28px;
        }

        .hero-logo-container {
            width: 72px;
            height: 72px;
            background: radial-gradient(circle, rgba(37, 99, 235, 0.28) 0%, rgba(13, 20, 36, 0.85) 100%);
            border: 1.5px solid rgba(59, 130, 246, 0.45);
            border-radius: 20px;
            padding: 8px;
            display: flex;
            align-items: center;
            justify-content: center;
            box-shadow: 
                0 16px 36px -10px rgba(37, 99, 235, 0.5),
                0 0 20px rgba(59, 130, 246, 0.2) inset;
            transition: transform 0.3s ease, box-shadow 0.3s ease;
        }

        .hero-logo-container:hover {
            transform: translateY(-2px) scale(1.03);
            box-shadow: 
                0 20px 40px -8px rgba(37, 99, 235, 0.65),
                0 0 25px rgba(59, 130, 246, 0.3) inset;
        }

        .hero-logo-img {
            width: 100%;
            height: 100%;
            object-fit: contain;
            filter: drop-shadow(0 4px 8px rgba(0, 0, 0, 0.35));
        }

        .brand-title-wrap {
            display: flex;
            flex-direction: column;
        }

        .club-title-text {
            font-family: 'Plus Jakarta Sans', sans-serif;
            font-size: 1.45rem;
            font-weight: 800;
            color: #ffffff;
            letter-spacing: -0.01em;
            line-height: 1.2;
        }

        .club-subtitle-tag {
            font-size: 0.8rem;
            color: #94a3b8;
            font-weight: 500;
            letter-spacing: 0.04em;
            text-transform: uppercase;
            margin-top: 2px;
        }

        .brand-badge-pill {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            background: rgba(37, 99, 235, 0.12);
            border: 1px solid rgba(59, 130, 246, 0.28);
            border-radius: 50px;
            padding: 6px 14px;
            margin-bottom: 24px;
        }

        .live-dot {
            width: 7px;
            height: 7px;
            border-radius: 50%;
            background: #10b981;
            box-shadow: 0 0 10px #10b981;
            animation: pulse-dot 2s ease-in-out infinite;
        }

        @keyframes pulse-dot {
            0%, 100% { opacity: 1; transform: scale(1); }
            50% { opacity: 0.45; transform: scale(0.8); }
        }

        .brand-badge-pill span {
            color: #93c5fd;
            font-size: 12px;
            font-weight: 600;
            letter-spacing: 0.06em;
            text-transform: uppercase;
        }

        .hero-title {
            font-family: 'Plus Jakarta Sans', sans-serif;
            font-size: clamp(2.4rem, 3.8vw, 3.6rem);
            font-weight: 800;
            color: #ffffff;
            line-height: 1.15;
            margin-bottom: 18px;
            letter-spacing: -0.025em;
        }

        .hero-title .gradient-text {
            background: linear-gradient(135deg, #60a5fa 0%, #a78bfa 50%, #34d399 100%);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
        }

        .hero-subtitle {
            font-size: 1.05rem;
            color: #94a3b8;
            line-height: 1.65;
            max-width: 480px;
            margin-bottom: 38px;
        }

        /* Feature Pills */
        .feature-grid {
            display: grid;
            grid-template-columns: repeat(2, 1fr);
            gap: 14px;
            max-width: 520px;
            margin-bottom: 36px;
        }

        .feature-card {
            background: rgba(15, 23, 42, 0.45);
            border: 1px solid rgba(255, 255, 255, 0.05);
            border-radius: 14px;
            padding: 12px 14px;
            display: flex;
            align-items: center;
            gap: 12px;
            transition: all 0.25s ease;
        }

        .feature-card:hover {
            background: rgba(37, 99, 235, 0.12);
            border-color: rgba(59, 130, 246, 0.25);
            transform: translateY(-2px);
        }

        .feature-card-icon {
            width: 36px;
            height: 36px;
            border-radius: 10px;
            background: linear-gradient(135deg, rgba(37, 99, 235, 0.2), rgba(139, 92, 246, 0.15));
            border: 1px solid rgba(59, 130, 246, 0.3);
            display: flex;
            align-items: center;
            justify-content: center;
            color: #60a5fa;
            font-size: 15px;
            flex-shrink: 0;
        }

        .feature-card-content strong {
            display: block;
            font-size: 0.88rem;
            color: #f1f5f9;
            font-weight: 600;
        }

        .feature-card-content span {
            font-size: 0.76rem;
            color: #64748b;
        }

        .hero-footer-status {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            font-size: 0.82rem;
            color: #64748b;
        }

        .hero-footer-status i {
            color: #3b82f6;
        }

        /* ── Right Login Panel ── */
        .right-panel {
            flex: 0.85;
            min-width: 0;
            width: 100%;
            max-width: 540px;
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            padding: 40px 48px;
            position: relative;
        }

        .login-card {
            width: 100%;
            background: var(--card-bg);
            backdrop-filter: blur(28px);
            -webkit-backdrop-filter: blur(28px);
            border: 1px solid var(--card-border);
            border-radius: 26px;
            padding: 42px 38px;
            box-shadow:
                0 0 0 1px rgba(255, 255, 255, 0.04) inset,
                0 30px 70px -15px rgba(0, 0, 0, 0.75),
                0 0 40px -10px rgba(37, 99, 235, 0.15);
            animation: cardSlideUp 0.65s cubic-bezier(0.16, 1, 0.3, 1) both;
            position: relative;
            overflow: hidden;
        }

        /* Subtle glowing top line */
        .login-card::before {
            content: '';
            position: absolute;
            top: 0;
            left: 20%;
            right: 20%;
            height: 1.5px;
            background: linear-gradient(90deg, transparent, #3b82f6, #8b5cf6, transparent);
            border-radius: 2px;
        }

        @keyframes cardSlideUp {
            from {
                opacity: 0;
                transform: translateY(28px) scale(0.98);
            }
            to {
                opacity: 1;
                transform: translateY(0) scale(1);
            }
        }

        /* Card Top Brand Header */
        .card-brand-top {
            display: flex;
            align-items: center;
            gap: 16px;
            margin-bottom: 24px;
        }

        .card-logo-container {
            width: 54px;
            height: 54px;
            background: radial-gradient(circle, rgba(255, 255, 255, 0.09) 0%, rgba(15, 23, 42, 0.7) 100%);
            border: 1px solid rgba(255, 255, 255, 0.12);
            border-radius: 16px;
            padding: 6px;
            display: flex;
            align-items: center;
            justify-content: center;
            box-shadow: 
                0 8px 20px -4px rgba(37, 99, 235, 0.35),
                0 0 12px rgba(59, 130, 246, 0.15) inset;
            flex-shrink: 0;
        }

        .card-logo-img {
            width: 100%;
            height: 100%;
            object-fit: contain;
            filter: drop-shadow(0 2px 5px rgba(0,0,0,0.4));
        }

        .card-brand-text {
            display: flex;
            flex-direction: column;
        }

        .card-eyebrow {
            font-size: 11px;
            font-weight: 700;
            color: #3b82f6;
            letter-spacing: 0.12em;
            text-transform: uppercase;
        }

        .card-title {
            font-family: 'Plus Jakarta Sans', sans-serif;
            font-size: 1.65rem;
            font-weight: 800;
            color: #f8fafc;
            letter-spacing: -0.02em;
            line-height: 1.2;
            margin-top: 2px;
        }

        .card-subtitle {
            font-size: 0.88rem;
            color: #64748b;
            margin-bottom: 28px;
            line-height: 1.5;
        }

        /* ── Form Inputs ── */
        .field-group {
            margin-bottom: 20px;
        }

        .field-label {
            display: block;
            font-size: 12px;
            font-weight: 600;
            color: #94a3b8;
            letter-spacing: 0.05em;
            text-transform: uppercase;
            margin-bottom: 8px;
        }

        .field-wrap {
            position: relative;
        }

        .field-wrap input {
            width: 100%;
            background: var(--input-bg) !important;
            border: 1.5px solid rgba(255, 255, 255, 0.08) !important;
            border-radius: 14px !important;
            padding: 13px 16px 13px 46px !important;
            color: #f8fafc !important;
            font-size: 0.94rem !important;
            font-family: 'Inter', sans-serif !important;
            outline: none !important;
            transition: all 0.25s ease !important;
            height: auto !important;
            box-shadow: 0 2px 6px rgba(0,0,0,0.25) inset !important;
        }

        .field-wrap input::placeholder {
            color: #475569 !important;
        }

        .field-wrap input:focus {
            border-color: #3b82f6 !important;
            box-shadow: 
                0 0 0 4px rgba(59, 130, 246, 0.16),
                0 2px 6px rgba(0,0,0,0.25) inset !important;
            background: rgba(13, 20, 38, 0.96) !important;
        }

        .field-icon {
            position: absolute;
            left: 16px;
            top: 50%;
            transform: translateY(-50%);
            color: #475569;
            font-size: 0.95rem;
            pointer-events: none;
            transition: color 0.25s ease;
        }

        .field-wrap input:focus ~ .field-icon {
            color: #3b82f6;
        }

        .toggle-pw {
            position: absolute;
            right: 4px;
            top: 50%;
            transform: translateY(-50%);
            width: 42px;
            height: 42px;
            display: flex;
            align-items: center;
            justify-content: center;
            color: #64748b;
            cursor: pointer;
            font-size: 1rem;
            border-radius: 10px;
            transition: color 0.2s ease, background 0.2s ease;
            user-select: none;
            -webkit-tap-highlight-color: transparent;
        }

        .toggle-pw:hover, .toggle-pw:active {
            color: #60a5fa;
            background: rgba(255, 255, 255, 0.06);
        }

        /* ── Remember & Forgot Row ── */
        .remember-row {
            display: flex;
            align-items: center;
            justify-content: space-between;
            margin-bottom: 26px;
            margin-top: 4px;
            gap: 10px;
        }

        .remember-check {
            display: flex;
            align-items: center;
            gap: 9px;
            cursor: pointer;
            -webkit-tap-highlight-color: transparent;
        }

        .remember-check input[type="checkbox"] {
            width: 18px !important;
            height: 18px !important;
            border-radius: 5px;
            accent-color: #2563eb;
            cursor: pointer;
            margin: 0;
            flex-shrink: 0;
        }

        .remember-check label {
            font-size: 0.86rem;
            color: #94a3b8;
            cursor: pointer;
            margin: 0;
            user-select: none;
            padding: 4px 0;
        }

        .forgot-link {
            font-size: 0.86rem;
            color: #60a5fa;
            text-decoration: none;
            font-weight: 500;
            transition: all 0.2s ease;
            padding: 4px 0;
            -webkit-tap-highlight-color: transparent;
        }

        .forgot-link:hover {
            color: #93c5fd;
            text-decoration: underline;
        }

        /* ── Sign In Button ── */
        .btn-signin {
            width: 100%;
            background: linear-gradient(135deg, #2563eb 0%, #4f46e5 50%, #7c3aed 100%);
            background-size: 200% 200%;
            border: none;
            border-radius: 14px;
            padding: 14px 20px;
            color: #ffffff;
            font-size: 0.98rem;
            font-weight: 700;
            font-family: 'Plus Jakarta Sans', sans-serif;
            cursor: pointer;
            letter-spacing: 0.02em;
            box-shadow: 0 8px 24px -4px rgba(79, 70, 229, 0.45);
            transition: all 0.3s cubic-bezier(0.16, 1, 0.3, 1);
            position: relative;
            overflow: hidden;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 10px;
        }

        .btn-signin::before {
            content: '';
            position: absolute;
            inset: 0;
            background: linear-gradient(135deg, rgba(255,255,255,0.18), transparent 70%);
            opacity: 0;
            transition: opacity 0.3s ease;
        }

        .btn-signin:hover {
            transform: translateY(-2px);
            background-position: 100% 100%;
            box-shadow: 0 12px 32px -4px rgba(79, 70, 229, 0.6);
            color: #ffffff;
        }

        .btn-signin:hover::before {
            opacity: 1;
        }

        .btn-signin:active {
            transform: translateY(0);
        }

        /* Card Footer */
        .card-footer-text {
            text-align: center;
            margin-top: 26px;
            padding-top: 20px;
            border-top: 1px solid rgba(255, 255, 255, 0.05);
            font-size: 0.78rem;
            color: #64748b;
        }

        .card-footer-text span {
            display: inline-flex;
            align-items: center;
            gap: 6px;
            color: #64748b;
        }

        .card-footer-text i {
            color: #10b981;
        }

        /* View transitions */
        .view-pane {
            animation: fadeInView 0.3s cubic-bezier(0.16, 1, 0.3, 1) both;
        }

        @keyframes fadeInView {
            from { opacity: 0; transform: translateY(10px); }
            to { opacity: 1; transform: translateY(0); }
        }

        /* ── Responsive Styling ── */
        @media (max-width: 991px) {
            html, body {
                overflow-x: hidden;
                width: 100%;
            }

            .login-wrapper {
                min-height: 100vh;
                min-height: 100dvh;
                display: flex;
                flex-direction: column;
                justify-content: center;
                align-items: center;
                padding: 20px 14px;
                width: 100%;
                box-sizing: border-box;
            }

            .left-panel {
                display: none;
            }

            .right-panel {
                flex: none;
                display: flex !important;
                flex-direction: column !important;
                align-items: center !important;
                justify-content: center !important;
                min-width: 0 !important;
                width: 100% !important;
                max-width: 420px;
                margin: 0 auto;
                padding: 0 !important;
                box-sizing: border-box;
            }

            .login-card {
                width: 100% !important;
                max-width: 420px;
                padding: 32px 24px;
                border-radius: 24px;
                box-sizing: border-box;
                box-shadow:
                    0 0 0 1px rgba(255, 255, 255, 0.06) inset,
                    0 24px 60px -12px rgba(0, 0, 0, 0.75),
                    0 0 35px -10px rgba(37, 99, 235, 0.2);
            }

            .card-brand-top {
                display: flex;
                flex-direction: column;
                align-items: center;
                text-align: center;
                gap: 12px;
                margin-bottom: 18px;
            }

            .card-logo-container {
                width: 64px;
                height: 64px;
                border-radius: 18px;
                padding: 7px;
                display: flex;
                align-items: center;
                justify-content: center;
                background: radial-gradient(circle, rgba(37, 99, 235, 0.35) 0%, rgba(13, 20, 36, 0.95) 100%);
                border: 1.5px solid rgba(59, 130, 246, 0.5);
                box-shadow: 
                    0 10px 24px -6px rgba(37, 99, 235, 0.55),
                    0 0 14px rgba(59, 130, 246, 0.2) inset;
            }

            .card-brand-text {
                display: flex;
                flex-direction: column;
                align-items: center;
                text-align: center;
            }

            .card-eyebrow {
                font-size: 11px;
                font-weight: 700;
                color: #60a5fa;
                letter-spacing: 0.1em;
                text-transform: uppercase;
            }

            .card-title {
                font-size: 1.55rem;
                font-weight: 800;
                color: #ffffff;
                margin-top: 2px;
            }

            .card-subtitle {
                text-align: center;
                font-size: 0.85rem;
                color: #94a3b8;
                margin-bottom: 22px;
                line-height: 1.45;
            }

            .field-group {
                margin-bottom: 18px;
            }

            .field-wrap input {
                font-size: 16px !important; /* Critical: Prevents iOS Safari auto-zoom on focus */
                padding: 13px 44px 13px 44px !important;
                border-radius: 13px !important;
                min-height: 48px !important;
            }

            .field-icon {
                left: 15px;
                font-size: 0.95rem;
            }

            .btn-signin {
                min-height: 48px;
                font-size: 0.98rem;
                border-radius: 13px;
                padding: 14px 18px;
            }

            .remember-row {
                margin-bottom: 22px;
            }
        }

        @media (max-width: 480px) {
            .login-wrapper {
                padding: 16px 10px;
            }

            .login-card {
                padding: 26px 18px;
                border-radius: 20px;
            }

            .card-logo-container {
                width: 56px;
                height: 56px;
                border-radius: 16px;
                padding: 6px;
            }

            .card-title {
                font-size: 1.4rem;
            }

            .card-subtitle {
                font-size: 0.82rem;
                margin-bottom: 18px;
            }

            .field-label {
                font-size: 11px;
                margin-bottom: 6px;
            }

            .field-wrap input {
                padding: 12px 42px 12px 42px !important;
                min-height: 46px !important;
            }

            .field-icon {
                left: 14px;
            }

            .btn-signin {
                min-height: 46px;
                font-size: 0.95rem;
                padding: 12px 16px;
            }

            .card-footer-text {
                margin-top: 20px;
                padding-top: 16px;
                font-size: 0.74rem;
            }
        }

        @media (max-width: 360px) {
            .login-card {
                padding: 22px 14px;
                border-radius: 18px;
            }

            .remember-row {
                flex-direction: column;
                align-items: flex-start;
                gap: 8px;
                margin-bottom: 18px;
            }
        }
    </style>
</head>

<body>

    <!-- Animated background canvas with ambient orbs & dot grid -->
    <div class="bg-canvas">
        <div class="bg-grid"></div>
        <div class="orb orb-1"></div>
        <div class="orb orb-2"></div>
        <div class="orb orb-3"></div>
    </div>

    <div class="login-wrapper">

        <!-- ── Left Branding Hero Panel ── -->
        <div class="left-panel animated fadeInLeft">

            <div class="brand-header">
                <div class="hero-logo-container">
                    <img src="favicon.png" alt="YMCA Logo" class="hero-logo-img" onerror="this.onerror=null; this.src='favicon.ico';">
                </div>
                <div class="brand-title-wrap">
                    <div class="club-title-text">YMCA BCP</div>
                    <div class="club-subtitle-tag">Poovathussery Badminton Club</div>
                </div>
            </div>

            <div class="left-panel-content">
                <div class="brand-badge-pill">
                    <div class="live-dot"></div>
                    <span>Official Member Portal</span>
                </div>

                <h1 class="hero-title">
                    Excellence on Court,<br>
                    <span class="gradient-text">Precision in Management.</span>
                </h1>

                <p class="hero-subtitle">
                    Streamlined club administration for active members. Track daily court attendance, record subscriptions, and access official verified receipts instantly.
                </p>

                <div class="feature-grid">
                    <div class="feature-card">
                        <div class="feature-card-icon">
                            <i class="fa fa-calendar-check-o"></i>
                        </div>
                        <div class="feature-card-content">
                            <strong>Live Attendance</strong>
                            <span>Morning &amp; Evening sessions</span>
                        </div>
                    </div>

                    <div class="feature-card">
                        <div class="feature-card-icon">
                            <i class="fa fa-file-text-o"></i>
                        </div>
                        <div class="feature-card-content">
                            <strong>Official Receipts</strong>
                            <span>Instant PDF receipts &amp; alerts</span>
                        </div>
                    </div>

                    <div class="feature-card">
                        <div class="feature-card-icon">
                            <i class="fa fa-google-wallet"></i>
                        </div>
                        <div class="feature-card-content">
                            <strong>Prepaid Wallet</strong>
                            <span>Automatic dues settlement</span>
                        </div>
                    </div>

                    <div class="feature-card">
                        <div class="feature-card-icon">
                            <i class="fa fa-users"></i>
                        </div>
                        <div class="feature-card-content">
                            <strong>Family Directory</strong>
                            <span>Linked accounts &amp; groupings</span>
                        </div>
                    </div>
                </div>
            </div>

            <div class="hero-footer-status">
                <i class="fa fa-shield"></i>
                <span>Enterprise Cloud Architecture &bull; <strong>YMCA BCP &bull; 2026</strong></span>
            </div>

        </div>

        <!-- ── Right Login Panel ── -->
        <div class="right-panel">
            <div class="login-card">

                <!-- ── Sign In View ── -->
                <div id="signin-view" class="view-pane">

                    <div class="card-brand-top">
                        <div class="card-logo-container">
                            <img src="favicon.png" alt="YMCA BCP Logo" class="card-logo-img" onerror="this.onerror=null; this.src='favicon.ico';">
                        </div>
                        <div class="card-brand-text">
                            <div class="card-eyebrow">YMCA Poovathussery</div>
                            <div class="card-title">Sign In</div>
                        </div>
                    </div>

                    <div class="card-subtitle">Enter your registered email and password to access your account</div>

                    <?php if (!empty($login_success)) { ?>
                    <div id="login-success-alert" style="
                        display: flex;
                        align-items: center;
                        gap: 12px;
                        background: rgba(16, 185, 129, 0.12);
                        border: 1px solid rgba(16, 185, 129, 0.3);
                        border-radius: 12px;
                        padding: 12px 16px;
                        margin-bottom: 22px;
                        animation: fadeInView 0.3s ease both;
                    ">
                        <i class="fa fa-check-circle" style="color: #34d399; font-size: 1.1rem; flex-shrink: 0;"></i>
                        <span style="color: #a7f3d0; font-size: 0.86rem; font-weight: 500; line-height: 1.4;"><?php echo htmlspecialchars($login_success); ?></span>
                    </div>
                    <?php } ?>

                    <?php if (!empty($login_error)) { ?>
                    <div id="login-error-alert" style="
                        display: flex;
                        align-items: center;
                        gap: 12px;
                        background: rgba(239, 68, 68, 0.12);
                        border: 1px solid rgba(239, 68, 68, 0.28);
                        border-radius: 12px;
                        padding: 12px 16px;
                        margin-bottom: 22px;
                        animation: fadeInView 0.3s ease both;
                    ">
                        <i class="fa fa-exclamation-circle" style="color: #f87171; font-size: 1.1rem; flex-shrink: 0;"></i>
                        <span style="color: #fca5a5; font-size: 0.86rem; font-weight: 500; line-height: 1.4;"><?php echo htmlspecialchars($login_error); ?></span>
                    </div>
                    <?php } ?>

                    <form role="form" action="./app_login_manager/login_validation.php" method="POST" autocomplete="off">

                        <div class="field-group">
                            <label class="field-label" for="email">Email Address</label>
                            <div class="field-wrap">
                                <input type="email" id="email" name="email" placeholder="member@example.com" required autocomplete="username">
                                <i class="fa fa-envelope field-icon"></i>
                            </div>
                        </div>

                        <div class="field-group">
                            <label class="field-label" for="password">Password</label>
                            <div class="field-wrap">
                                <input type="password" id="password" name="password" placeholder="••••••••" required autocomplete="current-password">
                                <i class="fa fa-lock field-icon"></i>
                                <i class="fa fa-eye-slash toggle-pw" id="togglePw" title="Show or hide password"></i>
                            </div>
                        </div>

                        <div class="remember-row">
                            <div class="remember-check">
                                <input type="checkbox" id="remember_me" name="remember_me">
                                <label for="remember_me">Remember me</label>
                            </div>
                            <a class="forgot-link" href="./app_login_manager/forgot_password.php" id="btnForgotView">Forgot password?</a>
                        </div>

                        <button type="submit" class="btn-signin">
                            <i class="fa fa-sign-in"></i> Sign In to Account
                        </button>

                    </form>
                </div>

                <!-- ── In-Page Forgot Password View ── -->
                <div id="forgot-view" class="view-pane" style="display: none;">

                    <div class="card-brand-top">
                        <div class="card-logo-container">
                            <img src="favicon.png" alt="YMCA BCP Logo" class="card-logo-img" onerror="this.onerror=null; this.src='favicon.ico';">
                        </div>
                        <div class="card-brand-text">
                            <div class="card-eyebrow">Account Recovery</div>
                            <div class="card-title">Reset Password</div>
                        </div>
                    </div>

                    <div class="card-subtitle">Enter your registered email and we'll send a secure password reset link</div>

                    <div id="forgot-alert" style="display: none; margin-bottom: 20px;"></div>

                    <form id="forgotForm" autocomplete="off">
                        <div class="field-group">
                            <label class="field-label" for="forgot_email">Registered Email Address</label>
                            <div class="field-wrap">
                                <input type="email" id="forgot_email" name="email" placeholder="member@example.com" required autocomplete="email">
                                <i class="fa fa-envelope field-icon"></i>
                            </div>
                        </div>

                        <button type="submit" class="btn-signin" id="btnSubmitForgot">
                            <span id="btnForgotText">
                                <i class="fa fa-paper-plane" style="margin-right: 8px;"></i> Send Reset Link
                            </span>
                        </button>

                        <div style="text-align: center; margin-top: 24px;">
                            <a href="#" id="btnBackToLogin" class="forgot-link" style="display: inline-flex; align-items: center; gap: 8px;">
                                <i class="fa fa-arrow-left"></i> Return to Sign In
                            </a>
                        </div>
                    </form>
                </div>

                <div class="card-footer-text">
                    <span><i class="fa fa-lock"></i> 256-Bit SSL Encrypted &bull; YMCA BCP ERP</span>
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
                                    <a href="${res.dev_reset_link}" style="color: #93c5fa; text-decoration: underline; word-break: break-all;">Click here to reset password directly</a>
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
