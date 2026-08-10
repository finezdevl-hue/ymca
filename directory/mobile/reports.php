<?php
session_start();
include '../../app_common/db_connect.php';
include '../../app_common/auth_helper.php';

if (empty($_SESSION['login_id'])) {
    header("Location: ../../index.php");
    exit();
}

$login_id = (int)$_SESSION['login_id'];
$is_admin_reports = isGroupAdmin($login_id) || isExecutiveMember($login_id);
$is_executive = isExecutiveMember($login_id) && !isSuperAdmin($login_id) && !isGroupAdmin($login_id);
$allowed_groups = getUserAllowedGroupIds($login_id);
$primary_role = getUserPrimaryRoleName($login_id);

$active_tab = 'reports';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
    <title>Reports Dashboard - YMCA Mobile</title>
    
    <link href="../../css/bootstrap.min.css" rel="stylesheet">
    <link href="../../font-awesome/css/font-awesome.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <link href="mobile_layout.css" rel="stylesheet">

    <style>
        :root {
            --mob-font: 'Plus Jakarta Sans', sans-serif;
        }

        body {
            font-family: var(--mob-font) !important;
            background: #f1f5f9 !important;
        }

        .mob-reports-hero {
            background: linear-gradient(135deg, #0f172a 0%, #1e293b 50%, #1d4ed8 100%);
            border-radius: 20px;
            padding: 22px 20px;
            color: #ffffff;
            margin-bottom: 20px;
            box-shadow: 0 10px 25px rgba(15, 23, 42, 0.15);
            position: relative;
            overflow: hidden;
        }

        .mob-reports-hero::after {
            content: '';
            position: absolute;
            top: -50%;
            right: -20%;
            width: 220px;
            height: 220px;
            background: radial-gradient(circle, rgba(59, 130, 246, 0.3) 0%, rgba(255, 255, 255, 0) 70%);
            pointer-events: none;
        }

        .mob-role-pill {
            display: inline-flex;
            align-items: center;
            gap: 6px;
            background: rgba(255, 255, 255, 0.15);
            backdrop-filter: blur(10px);
            padding: 4px 10px;
            border-radius: 12px;
            font-size: 11px;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            margin-bottom: 8px;
            border: 1px solid rgba(255, 255, 255, 0.2);
        }

        .mob-reports-hero h2 {
            margin: 0 0 4px 0;
            font-weight: 800;
            font-size: 22px;
            letter-spacing: -0.5px;
        }

        .mob-reports-hero p {
            margin: 0;
            font-size: 13px;
            opacity: 0.85;
            font-weight: 500;
        }

        .reports-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(150px, 1fr));
            gap: 14px;
            margin-bottom: 24px;
        }

        .report-box-card {
            background: #ffffff;
            border: 1px solid #e2e8f0;
            border-radius: 18px;
            padding: 18px 16px;
            text-decoration: none;
            color: inherit;
            display: flex;
            flex-direction: column;
            justify-content: space-between;
            transition: all 0.25s cubic-bezier(0.4, 0, 0.2, 1);
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.03);
            position: relative;
            overflow: hidden;
            -webkit-tap-highlight-color: transparent;
        }

        .report-box-card:active,
        .report-box-card:hover {
            transform: translateY(-3px);
            border-color: #2563eb;
            box-shadow: 0 10px 24px rgba(37, 99, 235, 0.12);
            text-decoration: none;
            color: inherit;
        }

        .box-icon-wrap {
            width: 44px;
            height: 44px;
            border-radius: 14px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 20px;
            color: #ffffff;
            margin-bottom: 14px;
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.1);
        }

        .grad-indigo { background: linear-gradient(135deg, #6366f1, #4f46e5); }
        .grad-emerald { background: linear-gradient(135deg, #10b981, #059669); }
        .grad-purple { background: linear-gradient(135deg, #a855f7, #7c3aed); }
        .grad-blue { background: linear-gradient(135deg, #3b82f6, #1d4ed8); }
        .grad-amber { background: linear-gradient(135deg, #f59e0b, #d97706); }
        .grad-rose { background: linear-gradient(135deg, #f43f5e, #e11d48); }
        .grad-sky { background: linear-gradient(135deg, #0ea5e9, #0284c7); }
        .grad-cyan { background: linear-gradient(135deg, #06b6d4, #0891b2); }
        .grad-violet { background: linear-gradient(135deg, #8b5cf6, #6d28d9); }
        .grad-red { background: linear-gradient(135deg, #ef4444, #dc2626); }

        .report-box-title {
            font-size: 14px;
            font-weight: 800;
            color: #0f172a;
            margin: 0 0 4px 0;
            line-height: 1.3;
        }

        .report-box-desc {
            font-size: 11.5px;
            color: #64748b;
            margin: 0;
            line-height: 1.35;
            font-weight: 500;
        }

        .report-box-arrow {
            display: flex;
            align-items: center;
            gap: 4px;
            font-size: 11px;
            font-weight: 700;
            color: #2563eb;
            margin-top: 12px;
        }

        .section-label {
            font-size: 13px;
            font-weight: 800;
            color: #475569;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            margin-bottom: 12px;
            display: flex;
            align-items: center;
            gap: 8px;
        }
    </style>
</head>
<body class="mob-body">

    <!-- Header -->
    <header class="mob-header">
        <div class="mob-header-brand">
            <div class="mob-header-logo">Y</div>
            <div class="mob-header-title">
                YMCA <span>Reports Portal</span>
            </div>
        </div>
        <div class="mob-header-actions">
            <a href="../../app_login_manager/logout.php" class="mob-header-btn" title="Logout">
                <i class="fa fa-sign-out"></i>
            </a>
        </div>
    </header>

    <div class="mob-page">
        
        <!-- Hero Card -->
        <div class="mob-reports-hero">
            <div class="mob-role-pill">
                <i class="fa fa-shield"></i> <?php echo htmlspecialchars($primary_role); ?>
            </div>
            <h2>Reports & Analytics</h2>
            <p><?php echo !$is_admin_reports ? 'Access your monthly attendance, yearly summary, and wallet' : 'Access group attendance, ledger statements, and financial reports'; ?></p>
        </div>

        <?php if (!$is_admin_reports): ?>
        <!-- Member Reports Section -->
        <div class="section-label">
            <i class="fa fa-bar-chart" style="color:#6366f1;"></i> Your Reports
        </div>
        <div class="reports-grid">
            <a href="monthly_attendance.php" class="report-box-card">
                <div class="box-icon-wrap grad-indigo"><i class="fa fa-calendar"></i></div>
                <div>
                    <h3 class="report-box-title">Monthly Attendance</h3>
                    <p class="report-box-desc">Day-by-day attendance record</p>
                </div>
                <div class="report-box-arrow">View Report <i class="fa fa-arrow-right"></i></div>
            </a>

            <a href="yearly_attendance.php" class="report-box-card">
                <div class="box-icon-wrap grad-emerald"><i class="fa fa-bar-chart"></i></div>
                <div>
                    <h3 class="report-box-title">Yearly Summary</h3>
                    <p class="report-box-desc">Financial year attendance totals</p>
                </div>
                <div class="report-box-arrow">View Report <i class="fa fa-arrow-right"></i></div>
            </a>

            <a href="wallet.php" class="report-box-card">
                <div class="box-icon-wrap grad-rose"><i class="fa fa-google-wallet"></i></div>
                <div>
                    <h3 class="report-box-title">Wallet</h3>
                    <p class="report-box-desc">Digital wallet balance & transactions</p>
                </div>
                <div class="report-box-arrow">View Report <i class="fa fa-arrow-right"></i></div>
            </a>
        </div>
        <?php else: ?>
        <!-- Attendance Reports Section -->
        <div class="section-label">
            <i class="fa fa-calendar-check-o" style="color:#6366f1;"></i> Attendance Reports
        </div>
        <div class="reports-grid">
            <a href="monthly_attendance.php" class="report-box-card">
                <div class="box-icon-wrap grad-indigo"><i class="fa fa-calendar"></i></div>
                <div>
                    <h3 class="report-box-title">Monthly Attendance</h3>
                    <p class="report-box-desc">Day-by-day group attendance matrix</p>
                </div>
                <div class="report-box-arrow">View Report <i class="fa fa-arrow-right"></i></div>
            </a>

            <a href="yearly_attendance.php" class="report-box-card">
                <div class="box-icon-wrap grad-emerald"><i class="fa fa-bar-chart"></i></div>
                <div>
                    <h3 class="report-box-title">Yearly Summary</h3>
                    <p class="report-box-desc">Financial year attendance totals</p>
                </div>
                <div class="report-box-arrow">View Report <i class="fa fa-arrow-right"></i></div>
            </a>
        </div>

        <!-- Financial & Fee Reports Section -->
        <div class="section-label">
            <i class="fa fa-line-chart" style="color:#10b981;"></i> Financial Reports
        </div>
        <div class="reports-grid">
            <a href="monthly_financial.php" class="report-box-card">
                <div class="box-icon-wrap grad-purple"><i class="fa fa-line-chart"></i></div>
                <div>
                    <h3 class="report-box-title">Monthly Financial</h3>
                    <p class="report-box-desc">Monthly group income & expenses</p>
                </div>
                <div class="report-box-arrow">View Report <i class="fa fa-arrow-right"></i></div>
            </a>

            <?php if (!$is_executive): ?>
            <a href="revenue_by_head.php" class="report-box-card">
                <div class="box-icon-wrap grad-blue"><i class="fa fa-pie-chart"></i></div>
                <div>
                    <h3 class="report-box-title">Revenue by Head</h3>
                    <p class="report-box-desc">Category revenue analysis</p>
                </div>
                <div class="report-box-arrow">View Report <i class="fa fa-arrow-right"></i></div>
            </a>
            <?php endif; ?>

            <a href="financial_year.php" class="report-box-card">
                <div class="box-icon-wrap grad-amber"><i class="fa fa-file-text-o"></i></div>
                <div>
                    <h3 class="report-box-title">Financial Year</h3>
                    <p class="report-box-desc">Annual group financial report</p>
                </div>
                <div class="report-box-arrow">View Report <i class="fa fa-arrow-right"></i></div>
            </a>

            <a href="wallet_ledger.php" class="report-box-card">
                <div class="box-icon-wrap grad-rose"><i class="fa fa-credit-card"></i></div>
                <div>
                    <h3 class="report-box-title">Wallet Ledger</h3>
                    <p class="report-box-desc">Member digital wallet balances</p>
                </div>
                <div class="report-box-arrow">View Report <i class="fa fa-arrow-right"></i></div>
            </a>
        </div>

        <!-- Transaction & History Section -->
        <div class="section-label">
            <i class="fa fa-history" style="color:#0ea5e9;"></i> History & Receivables
        </div>
        <div class="reports-grid">
            <a href="payment_history.php" class="report-box-card">
                <div class="box-icon-wrap grad-sky"><i class="fa fa-history"></i></div>
                <div>
                    <h3 class="report-box-title">Payment History</h3>
                    <p class="report-box-desc">Expense & payable vouchers</p>
                </div>
                <div class="report-box-arrow">View Report <i class="fa fa-arrow-right"></i></div>
            </a>

            <a href="fee_collection_history.php" class="report-box-card">
                <div class="box-icon-wrap grad-cyan"><i class="fa fa-list-alt"></i></div>
                <div>
                    <h3 class="report-box-title">Collection History</h3>
                    <p class="report-box-desc">Fee receipts & collections</p>
                </div>
                <div class="report-box-arrow">View Report <i class="fa fa-arrow-right"></i></div>
            </a>

            <?php if (!$is_executive): ?>
            <a href="member_ledger.php" class="report-box-card">
                <div class="box-icon-wrap grad-violet"><i class="fa fa-book"></i></div>
                <div>
                    <h3 class="report-box-title">Member Ledger</h3>
                    <p class="report-box-desc">Individual member cashbook</p>
                </div>
                <div class="report-box-arrow">View Report <i class="fa fa-arrow-right"></i></div>
            </a>
            <?php endif; ?>

            <a href="pending_receivables.php" class="report-box-card">
                <div class="box-icon-wrap grad-red"><i class="fa fa-exclamation-circle"></i></div>
                <div>
                    <h3 class="report-box-title">Pending Dues</h3>
                    <p class="report-box-desc">Outstanding fee receivables</p>
                </div>
                <div class="report-box-arrow">View Report <i class="fa fa-arrow-right"></i></div>
            </a>
        <?php endif; ?>

    </div>

    <!-- Mobile Bottom Navigation (5 Tabs) -->
    <?php include 'mobile_bottom_nav.php'; ?>

    <script src="../../js/jquery-3.1.1.min.js"></script>
    <script src="../../js/bootstrap.min.js"></script>
</body>
</html>
