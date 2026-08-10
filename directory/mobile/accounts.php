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
    header("Location: ledger.php");
    exit();
}
$is_admin = isSuperAdmin($login_id) || isGroupAdmin($login_id);
$allowed_groups = getUserAllowedGroupIds($login_id);
$primary_role = getUserPrimaryRoleName($login_id);

$active_tab = 'accounts';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
    <title>Accounts Hub - YMCA Mobile</title>
    
    <link href="../../css/bootstrap.min.css" rel="stylesheet">
    <link href="../../font-awesome/css/font-awesome.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <link href="mobile_layout.css" rel="stylesheet">

    <style>
        body { font-family: 'Plus Jakarta Sans', sans-serif !important; background: #f1f5f9 !important; }
        .acc-hero {
            background: linear-gradient(135deg, #059669 0%, #10b981 100%);
            border-radius: 20px;
            padding: 22px 20px;
            color: #ffffff;
            margin-bottom: 20px;
            box-shadow: 0 10px 25px rgba(16, 185, 129, 0.2);
        }
        .acc-hero h2 { margin: 0 0 4px 0; font-weight: 800; font-size: 22px; }
        .acc-hero p { margin: 0; font-size: 13px; opacity: 0.9; }

        .acc-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(150px, 1fr));
            gap: 14px;
            margin-bottom: 24px;
        }

        .acc-box-card {
            background: #ffffff;
            border: 1px solid #e2e8f0;
            border-radius: 18px;
            padding: 18px 16px;
            text-decoration: none;
            color: inherit;
            display: flex;
            flex-direction: column;
            justify-content: space-between;
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.03);
            transition: all 0.25s;
        }

        .acc-box-card:active, .acc-box-card:hover {
            transform: translateY(-3px);
            border-color: #10b981;
            text-decoration: none;
            color: inherit;
        }

        .acc-icon {
            width: 44px;
            height: 44px;
            border-radius: 14px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 20px;
            color: #ffffff;
            margin-bottom: 14px;
        }

        .acc-title { font-size: 14px; font-weight: 800; color: #0f172a; margin: 0 0 4px 0; }
        .acc-desc { font-size: 11.5px; color: #64748b; margin: 0; }
        .acc-action { font-size: 11px; font-weight: 700; color: #059669; margin-top: 12px; }
    </style>
</head>
<body class="mob-body">

    <!-- Header -->
    <header class="mob-header">
        <div class="mob-header-brand">
            <div class="mob-header-logo">Y</div>
            <div class="mob-header-title">
                YMCA <span>Accounts Hub</span>
            </div>
        </div>
        <div class="mob-header-actions">
            <a href="../../app_login_manager/logout.php" class="mob-header-btn" title="Logout">
                <i class="fa fa-sign-out"></i>
            </a>
        </div>
    </header>

    <div class="mob-page">

        <div class="acc-hero">
            <h2>Accounts & Collections</h2>
            <p>Manage income, record fee receipts, and track group expenses</p>
        </div>

        <div class="acc-grid">
            <a href="fee_receipts.php" class="acc-box-card">
                <div class="acc-icon" style="background: linear-gradient(135deg, #10b981, #059669);"><i class="fa fa-download"></i></div>
                <div>
                    <h3 class="acc-title">Fee Receipts</h3>
                    <p class="acc-desc">Collect member payments & fee receipts</p>
                </div>
                <div class="acc-action">Open Module <i class="fa fa-arrow-right"></i></div>
            </a>

            <a href="process_monthly_attendance.php" class="acc-box-card">
                <div class="acc-icon" style="background: linear-gradient(135deg, #059669, #047857);"><i class="fa fa-calendar-check-o"></i></div>
                <div>
                    <h3 class="acc-title">Process Attendance</h3>
                    <p class="acc-desc">Compile attendance & generate fee receivables</p>
                </div>
                <div class="acc-action">Open Module <i class="fa fa-arrow-right"></i></div>
            </a>

            <a href="other_receivable.php" class="acc-box-card">
                <div class="acc-icon" style="background: linear-gradient(135deg, #3b82f6, #1d4ed8);"><i class="fa fa-plus-circle"></i></div>
                <div>
                    <h3 class="acc-title">Other Receivable</h3>
                    <p class="acc-desc">Record non-fee income receivables</p>
                </div>
                <div class="acc-action">Open Module <i class="fa fa-arrow-right"></i></div>
            </a>

            <a href="other_received.php" class="acc-box-card">
                <div class="acc-icon" style="background: linear-gradient(135deg, #8b5cf6, #6d28d9);"><i class="fa fa-check-circle"></i></div>
                <div>
                    <h3 class="acc-title">Other Received</h3>
                    <p class="acc-desc">View miscellaneous income received</p>
                </div>
                <div class="acc-action">Open Module <i class="fa fa-arrow-right"></i></div>
            </a>

            <a href="group_expenses.php" class="acc-box-card">
                <div class="acc-icon" style="background: linear-gradient(135deg, #ef4444, #dc2626);"><i class="fa fa-upload"></i></div>
                <div>
                    <h3 class="acc-title">Group Expenses</h3>
                    <p class="acc-desc">Record payables & group expenses</p>
                </div>
                <div class="acc-action">Open Module <i class="fa fa-arrow-right"></i></div>
            </a>

            <a href="guests.php" class="acc-box-card">
                <div class="acc-icon" style="background: linear-gradient(135deg, #f59e0b, #d97706);"><i class="fa fa-user-circle"></i></div>
                <div>
                    <h3 class="acc-title">Guests Roster</h3>
                    <p class="acc-desc">View guest members list & fee ledgers</p>
                </div>
                <div class="acc-action">Open Module <i class="fa fa-arrow-right"></i></div>
            </a>

            <a href="ledger.php" class="acc-box-card">
                <div class="acc-icon" style="background: linear-gradient(135deg, #6366f1, #4f46e5);"><i class="fa fa-book"></i></div>
                <div>
                    <h3 class="acc-title">My Ledger</h3>
                    <p class="acc-desc">View personal cashbook ledger</p>
                </div>
                <div class="acc-action">Open Module <i class="fa fa-arrow-right"></i></div>
            </a>

            <a href="holidays.php" class="acc-box-card">
                <div class="acc-icon" style="background: linear-gradient(135deg, #f43f5e, #e11d48);"><i class="fa fa-calendar-times-o"></i></div>
                <div>
                    <h3 class="acc-title">Manage Holidays</h3>
                    <p class="acc-desc">Add club holidays & date exemptions</p>
                </div>
                <div class="acc-action">Open Module <i class="fa fa-arrow-right"></i></div>
            </a>
        </div>

    </div>

    <!-- Mobile Bottom Navigation (5 Tabs) -->
    <?php include 'mobile_bottom_nav.php'; ?>

    <script src="../../js/jquery-3.1.1.min.js"></script>
    <script src="../../js/bootstrap.min.js"></script>
</body>
</html>
