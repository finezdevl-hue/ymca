<?php
session_start();
if (empty($_SESSION['login_id']) || $_SESSION['login_id'] == 1) {
    header("Location: ../index.php");
    exit();
}
include '../app_common/db_connect.php';
include_once '../app_common/auth_helper.php';

$login_id = (int)($_SESSION['login_id'] ?? 0);
$can_mark_all_att_desktop = isAttendanceMaster($login_id);

$db = new Database();
$conn = $db->getConnection();

$member_id = $_SESSION['user_id'] ?? null;
$member = null;
$fullName = $_SESSION['name'];
$first_name = $_SESSION['name'];
$email = $_SESSION['email'] ?? 'No Email Added';
$avatar = '../img/customer.png';

$total_credit = 0;
$total_debit = 0;
$wallet_balance = 0;
$days_attended = 0;
$already_checked_in = false;
$transactions = [];

if (!empty($member_id)) {
    // 1. Fetch Member Info
    $member_stmt = $conn->prepare("SELECT * FROM tbl_members WHERE id = ?");
    $member_stmt->bind_param("i", $member_id);
    $member_stmt->execute();
    $member = $member_stmt->get_result()->fetch_assoc();
    $member_stmt->close();

    if ($member) {
        $fullName = $member['first_name'] . ' ' . ($member['middle_name'] ? $member['middle_name'] . ' ' : '') . $member['last_name'];
        $first_name = $member['first_name'];
        $email = $member['email'] ? $member['email'] : ($_SESSION['email'] ?? 'No Email Added');
        $img = $member['img'];
        $avatar = ($img && $img != 0 && $img != '0') ? '../image_upload/members/thumbnails/' . $img : '../img/customer.png';
    }

    // 2. Calculate Wallet Metrics
    $wallet_stmt = $conn->prepare("SELECT 
        SUM(CASE WHEN type = 'credit' THEN amount ELSE 0 END) as total_credit,
        SUM(CASE WHEN type = 'debit' THEN amount ELSE 0 END) as total_debit
        FROM tbl_wallet WHERE client_id = ?");
    $wallet_stmt->bind_param("i", $member_id);
    $wallet_stmt->execute();
    $wallet_res = $wallet_stmt->get_result()->fetch_assoc();
    $wallet_stmt->close();

    $total_credit = $wallet_res['total_credit'] ?? 0;
    $total_debit = $wallet_res['total_debit'] ?? 0;
    $wallet_balance = $total_credit - $total_debit;

    // 3. Fetch Monthly Attendance Count
    $month_start = date('Y-m-01');
    $month_end = date('Y-m-t');
    $att_stmt = $conn->prepare("SELECT COUNT(*) as days_attended FROM tbl_attendance WHERE member_id = ? AND date BETWEEN ? AND ?");
    $att_stmt->bind_param("iss", $member_id, $month_start, $month_end);
    $att_stmt->execute();
    $att_res = $att_stmt->get_result()->fetch_assoc();
    $att_stmt->close();
    $days_attended = $att_res['days_attended'] ?? 0;

    // 4. Check if today's attendance is already marked
    $today_date = date('Y-m-d');
    $today_stmt = $conn->prepare("SELECT id FROM tbl_attendance WHERE member_id = ? AND date = ?");
    $today_stmt->bind_param("is", $member_id, $today_date);
    $today_stmt->execute();
    $already_checked_in = ($today_stmt->get_result()->num_rows > 0);
    $today_stmt->close();

    // 5. Load last 5 Wallet Transactions for preview
    $tx_stmt = $conn->prepare("SELECT date, amount, type FROM tbl_wallet WHERE client_id = ? ORDER BY date DESC, id DESC LIMIT 5");
    $tx_stmt->bind_param("i", $member_id);
    $tx_stmt->execute();
    $tx_result = $tx_stmt->get_result();
    while ($row = $tx_result->fetch_assoc()) {
        $transactions[] = $row;
    }
    $tx_stmt->close();
}

$conn->close();
?>
<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>YMCA | Member Dashboard</title>
    <link rel="shortcut icon" href="../favicon.ico" type="image/x-icon">
    <!-- Mobile redirect: send non-admin member logins to mobile portal on small screens -->
    <script>
        (function(){
            if(window.innerWidth < 768 && !window.location.href.includes('desktop=1')){
                window.location.replace('mobile/home.php');
            }
        })();
    </script>

    <link href="../css/bootstrap.min.css" rel="stylesheet">
    <link href="../font-awesome/css/font-awesome.css" rel="stylesheet">
    <link href="../css/animate.css" rel="stylesheet">
    <link href="../css/style.css" rel="stylesheet">
    <link href="../css/custom_modern.css" rel="stylesheet">
    <link href="../css/plugins/sweetalert/sweetalert.css" rel="stylesheet">

    <style>
        .mem-dash-container {
            display: flex;
            flex-direction: column;
            gap: 24px;
            padding: 24px 0;
        }
        /* Greeting card styling */
        .dash-welcome-card {
            background: linear-gradient(135deg, #4f46e5 0%, #6366f1 50%, #3b82f6 100%);
            border-radius: var(--border-radius-lg);
            padding: 30px;
            color: var(--text-white);
            display: flex;
            align-items: center;
            justify-content: space-between;
            box-shadow: 0 10px 25px rgba(79, 70, 229, 0.25);
            border: 1px solid rgba(255, 255, 255, 0.15);
            position: relative;
            overflow: hidden;
        }
        .dash-welcome-card::before {
            content: '';
            position: absolute;
            width: 320px;
            height: 320px;
            background: radial-gradient(circle, rgba(255,255,255,0.12) 0%, rgba(255,255,255,0) 70%);
            border-radius: 50%;
            top: -80px;
            right: -80px;
            pointer-events: none;
        }
        .welcome-left {
            display: flex;
            align-items: center;
            gap: 24px;
        }
        .welcome-avatar-wrap {
            position: relative;
            width: 90px;
            height: 90px;
            border-radius: 50%;
            border: 4px solid rgba(255, 255, 255, 0.25);
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.15);
            overflow: hidden;
            background: #fff;
            flex-shrink: 0;
        }
        .welcome-avatar {
            width: 100%;
            height: 100%;
            object-fit: cover;
        }
        .welcome-text h2 {
            font-size: 2.1rem;
            font-weight: 800;
            letter-spacing: -0.5px;
            margin: 0 0 6px 0;
            color: var(--text-white);
        }
        .welcome-text p {
            margin: 0;
            opacity: 0.9;
            font-size: 0.95rem;
            font-weight: 500;
        }
        /* Interactive check-in widget */
        .checkin-widget-card {
            background: var(--card-bg);
            border-radius: var(--border-radius-md);
            padding: 24px;
            border: 1px solid var(--border-color);
            box-shadow: var(--shadow-md);
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 20px;
            transition: transform 0.3s ease, box-shadow 0.3s ease;
        }
        .checkin-widget-card:hover {
            transform: translateY(-2px);
            box-shadow: var(--shadow-hover);
        }
        .widget-time-block {
            display: flex;
            flex-direction: column;
            gap: 4px;
        }
        .widget-date {
            font-size: 0.95rem;
            color: var(--text-muted);
            font-weight: 500;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }
        .widget-time {
            font-size: 1.7rem;
            font-weight: 800;
            color: var(--text-primary);
        }
        .btn-checkin-action {
            background: var(--primary-gradient);
            color: var(--text-white) !important;
            border: none;
            padding: 14px 28px;
            font-size: 1rem;
            font-weight: 700;
            border-radius: var(--border-radius-sm);
            box-shadow: var(--shadow-md);
            cursor: pointer;
            display: inline-flex;
            align-items: center;
            gap: 8px;
            transition: all 0.3s ease;
            position: relative;
        }
        .btn-checkin-action:not(:disabled):hover {
            transform: translateY(-2px);
            box-shadow: 0 10px 20px rgba(99, 102, 241, 0.3);
            filter: brightness(1.05);
        }
        .btn-checkin-action:disabled {
            background: #e2e8f0;
            color: #94a3b8 !important;
            cursor: not-allowed;
            box-shadow: none;
            border: 1px solid #cbd5e1;
        }
        .dark-theme .btn-checkin-action:disabled {
            background: #1e293b;
            color: #475569 !important;
            border-color: #334155;
        }
        /* Pulse decoration for check-in */
        .pulse-dot {
            width: 8px;
            height: 8px;
            background: #10b981;
            border-radius: 50%;
            display: inline-block;
            box-shadow: 0 0 0 0 rgba(16, 185, 129, 0.7);
            animation: statusPulse 1.5s infinite;
        }
        @media (max-width: 768px) {
            .dash-welcome-card {
                flex-direction: column;
                align-items: flex-start;
                gap: 16px;
            }
            .checkin-widget-card {
                flex-direction: column;
                align-items: stretch;
                text-align: center;
            }
            .widget-time-block {
                align-items: center;
            }
            .btn-checkin-action {
                justify-content: center;
            }
        }

        /* Quick Actions styling */
        .dash-quick-actions {
            margin-top: 10px;
        }
        .quick-actions-title {
            font-size: 1.1rem;
            font-weight: 700;
            color: var(--text-primary);
            margin: 0 0 16px 0;
            display: flex;
            align-items: center;
            gap: 8px;
        }
        .quick-actions-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(220px, 1fr));
            gap: 16px;
        }
        .action-card {
            background: var(--card-bg);
            border-radius: var(--border-radius-md);
            padding: 18px;
            display: flex;
            align-items: center;
            gap: 14px;
            border: 1px solid var(--border-color);
            transition: all 0.3s cubic-bezier(0.16, 1, 0.3, 1);
            text-decoration: none !important;
        }
        .action-card:hover {
            transform: translateY(-3px);
            border-color: #3b82f6;
            box-shadow: var(--shadow-lg);
        }
        .action-icon {
            width: 44px;
            height: 44px;
            border-radius: 12px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.25rem;
            flex-shrink: 0;
        }
        .action-info h4 {
            font-size: 0.95rem;
            font-weight: 700;
            color: var(--text-primary);
            margin: 0 0 3px 0;
        }
        .action-info p {
            font-size: 0.8rem;
            color: var(--text-muted);
            margin: 0;
        }
    </style>
</head>
<body>
    <div id="wrapper">
        <!-- Sidebar Navigation -->
        <nav class="navbar-default navbar-static-side" role="navigation">
            <div class="dropdown profile-element">
                <center>
                    <span><img alt="image" class="img-circle" src="<?php echo $avatar; ?>" style="padding-top: 0 !important; width: 64px; height: 64px; object-fit: cover;" onerror="this.src='../img/customer.png'"/></span>
                    <span class="clear"> 
                        <span class="block m-t-xs"> 
                            <strong class="font-bold"><?php echo htmlspecialchars($_SESSION['name']); ?></strong>
                        </span>
                    </span>
                </center>
            </div>
            <div class="sidebar-collapse" id="divMenuContainer">
                <!-- Loaded via AJAX -->
            </div>
        </nav>

        <div id="page-wrapper" class="gray-bg">
            <!-- Top Bar Header -->
            <div class="row border-bottom">
                <nav class="navbar navbar-static-top" role="navigation" style="margin-bottom: 0">
                    <div class="navbar-header">
                        <button class="minimalize-styl-2 btn btn-primary navbar-minimalize" type="button"><i class="fa fa-bars"></i></button>
                    </div>
                    <ul class="nav navbar-top-links navbar-right">     
                        <li>
                            <a href="../app_login_manager/logout.php" style="color: #4f46e5; font-weight: 600;">
                                <i class="fa fa-sign-out"></i> Log out
                            </a>
                        </li>
                    </ul>
                </nav>
            </div>

            <!-- Dashboard Content wrapper -->
            <div class="wrapper wrapper-content">
                <div class="mem-dash-container">

                    <!-- Welcome greeting banner -->
                    <div class="dash-welcome-card animate-card">
                        <div class="welcome-left">
                            <div class="welcome-avatar-wrap">
                                <img src="<?php echo $avatar; ?>" class="welcome-avatar" onerror="this.src='../img/customer.png'">
                            </div>
                            <div class="welcome-text">
                                <h2>Hello, <?php echo htmlspecialchars($first_name); ?>!</h2>
                                <p>Member ID: #<?php echo $member_id ? $member_id : 'N/A'; ?> | Always stay active in the YMCA community.</p>
                            </div>
                        </div>
                        <div>
                            <span class="badge" style="background: rgba(255,255,255,0.18); border: 1.5px solid rgba(255,255,255,0.3); color: #ffffff; font-size: 0.9rem; padding: 8px 16px; border-radius: 30px; font-weight: 600; letter-spacing: 0.3px; backdrop-filter: blur(10px); display: inline-block;">
                                <?php echo htmlspecialchars($email); ?>
                            </span>
                        </div>
                    </div>

                    <!-- Interactive check-in widget -->
                    <div class="checkin-widget-card animate-card">
                        <div class="widget-time-block">
                            <span class="widget-date" id="live-date-str">Loading Date...</span>
                            <span class="widget-time" id="live-time-str">00:00:00 AM</span>
                        </div>
                        <div>
                            <?php if ($already_checked_in) { ?>
                                <button type="button" class="btn-checkin-action" disabled>
                                    <i class="fa fa-check-circle" style="color: #10b981;"></i> Checked In Today
                                </button>
                            <?php } else { ?>
                                <button type="button" class="btn-checkin-action" id="btn-checkin" onclick="markSelfAttendance()">
                                    <span class="pulse-dot"></span> Check In Attendance
                                </button>
                            <?php } ?>
                        </div>
                    </div>

                    <!-- Statistics grid -->
                    <div class="dash-kpi-grid">
                        <!-- Wallet balance card -->
                        <div class="dash-kpi-card info">
                            <div class="kpi-icon-wrap"><i class="fa fa-google-wallet"></i></div>
                            <span class="dash-kpi-title">Wallet Balance</span>
                            <span class="dash-kpi-val">₹<?php echo number_format($wallet_balance, 2); ?></span>
                            <div class="kpi-footer-breakdown" style="font-size: 0.8rem; color: var(--text-muted); margin-top: 8px;">
                                <span style="color: #10b981;"><i class="fa fa-arrow-up"></i> Total In: ₹<?php echo number_format($total_credit, 2); ?></span> &nbsp;|&nbsp;
                                <span style="color: #ef4444;"><i class="fa fa-arrow-down"></i> Out: ₹<?php echo number_format($total_debit, 2); ?></span>
                            </div>
                        </div>
                        
                        <!-- Attendance card -->
                        <div class="dash-kpi-card success">
                            <div class="kpi-icon-wrap"><i class="fa fa-calendar-check-o"></i></div>
                            <span class="dash-kpi-title">Monthly Attendance</span>
                            <span class="dash-kpi-val"><?php echo $days_attended; ?> Days</span>
                            <span class="text-muted" style="font-size: 0.8rem; display: block; margin-top: 8px;">Check-ins logged in <?php echo date('F Y'); ?></span>
                        </div>
                    </div>

                    <!-- Quick Actions Shortcuts -->
                    <div class="dash-quick-actions animate-card">
                        <h3 class="quick-actions-title"><i class="fa fa-rocket" style="color: #4f46e5;"></i> Quick Services</h3>
                        <div class="quick-actions-grid">
                            <a href="user_attendance.php" class="action-card">
                                <div class="action-icon" style="background: rgba(99, 102, 241, 0.1); color: #6366f1;">
                                    <i class="fa fa-calendar-check-o"></i>
                                </div>
                                <div class="action-info">
                                    <h4>Mark My Attendance</h4>
                                    <p>Check in your daily session</p>
                                </div>
                            </a>
                            <?php if ($can_mark_all_att_desktop): ?>
                            <a href="attendance.php" class="action-card">
                                <div class="action-icon" style="background: rgba(16, 185, 129, 0.1); color: #10b981;">
                                    <i class="fa fa-users"></i>
                                </div>
                                <div class="action-info">
                                    <h4>Mark All Attendance</h4>
                                    <p>Mark group members attendance</p>
                                </div>
                            </a>
                            <?php endif; ?>
                            <a href="member_cashbook_report.php" class="action-card">
                                <div class="action-icon" style="background: rgba(16, 185, 129, 0.1); color: #10b981;">
                                    <i class="fa fa-book"></i>
                                </div>
                                <div class="action-info">
                                    <h4>Your Cash Ledger</h4>
                                    <p>View your wallet statements</p>
                                </div>
                            </a>
                            <a href="monthly_attendance_report.php" class="action-card">
                                <div class="action-icon" style="background: rgba(245, 158, 11, 0.1); color: #f59e0b;">
                                    <i class="fa fa-file-pdf-o"></i>
                                </div>
                                <div class="action-info">
                                    <h4>Monthly Report</h4>
                                    <p>Check daily status grid</p>
                                </div>
                            </a>
                            <a href="yearly_attendance_report.php" class="action-card">
                                <div class="action-icon" style="background: rgba(139, 92, 246, 0.1); color: #8b5cf6;">
                                    <i class="fa fa-bar-chart"></i>
                                </div>
                                <div class="action-info">
                                    <h4>Yearly Summary</h4>
                                    <p>Overall yearly statistics</p>
                                </div>
                            </a>
                        </div>
                    </div>

                    <!-- Recent Ledger Preview Statement -->
                    <div class="ibox animate-card" style="margin-top: 10px;">
                        <div class="ibox-title" style="display: flex; align-items: center; justify-content: space-between;">
                            <h5><i class="fa fa-list-alt" style="color: #4f46e5;"></i> Recent Wallet Statement</h5>
                            <a href="member_cashbook_report.php" class="btn btn-xs btn-primary" style="border-radius: 8px;">View Full Ledger</a>
                        </div>
                        <div class="ibox-content" style="padding: 0;">
                            <div class="transaction-timeline">
                                <?php if (count($transactions) === 0) { ?>
                                    <div class="text-center text-muted" style="padding: 20px 0;">No transactions recorded.</div>
                                <?php } else { 
                                    foreach ($transactions as $tx) { 
                                        $isCredit = ($tx['type'] === 'credit');
                                        $icon = $isCredit ? 'fa-arrow-circle-down' : 'fa-arrow-circle-up';
                                        $iconColor = $isCredit ? '#10b981' : '#ef4444';
                                        $bg = $isCredit ? 'rgba(16, 185, 129, 0.08)' : 'rgba(239, 68, 68, 0.08)';
                                        $sign = $isCredit ? '+' : '-';
                                        $amountColor = $isCredit ? '#10b981' : '#f59e0b';
                                ?>
                                    <div class="timeline-item" style="display: flex; align-items: center; justify-content: space-between; padding: 14px 20px; border-bottom: 1px solid var(--border-color); gap: 14px;">
                                        <div style="display: flex; align-items: center; gap: 14px;">
                                            <div class="timeline-icon" style="width: 40px; height: 40px; border-radius: 10px; background: <?php echo $bg; ?>; color: <?php echo $iconColor; ?>; display: flex; align-items: center; justify-content: center; font-size: 1.15rem;">
                                                <i class="fa <?php echo $icon; ?>"></i>
                                            </div>
                                            <div>
                                                <div style="font-weight: 700; color: var(--text-primary); font-size: 0.95rem;">
                                                    <?php echo $isCredit ? 'Wallet Deposited' : 'Fees Payment'; ?>
                                                </div>
                                                <div style="font-size: 0.8rem; color: var(--text-muted); margin-top: 2px;">
                                                    <i class="fa fa-clock-o"></i> <?php echo date('d M Y', strtotime($tx['date'])); ?>
                                                </div>
                                            </div>
                                        </div>
                                        <div style="font-weight: 700; font-size: 1rem; color: <?php echo $amountColor; ?>;">
                                            <?php echo $sign; ?> ₹<?php echo number_format($tx['amount'], 2); ?>
                                        </div>
                                    </div>
                                <?php } 
                                } ?>
                            </div>
                        </div>
                    </div>

                </div>
            </div>
        </div>
    </div>

    <!-- Scripts -->
    <script src="../js/jquery-3.1.1.min.js"></script>
    <script src="../js/bootstrap.min.js"></script>
    <script src="../js/plugins/metisMenu/jquery.metisMenu.js"></script>
    <script src="../js/plugins/slimscroll/jquery.slimscroll.min.js"></script>
    <script src="../js/plugins/sweetalert/sweetalert.min.js"></script>
    <script src="../app_js/sweetalert-finez.js"></script>
    <script src="../js/inspinia.js"></script>
    <script src="../app_menu/menu.js"></script>

    <script>
        // Live Date & Time Clock
        function updateClock() {
            const now = new Date();
            const dateOptions = { weekday: 'long', year: 'numeric', month: 'long', day: 'numeric' };
            document.getElementById('live-date-str').textContent = now.toLocaleDateString('en-US', dateOptions);
            document.getElementById('live-time-str').textContent = now.toLocaleTimeString('en-US');
        }
        setInterval(updateClock, 1000);
        updateClock();

        // Check In Attendance Handler
        function markSelfAttendance() {
            const today = new Date().toISOString().split('T')[0];
            
            // First load the member's group mappings
            $.ajax({
                type: "POST",
                url: "api/user_attendance.php",
                data: { action: 'load_groups' },
                success: function(response) {
                    try {
                        const res = JSON.parse(response);
                        const groups = res[0];
                        if (!groups || groups.length === 0) {
                            alertwarning("You must be assigned to at least one group by an administrator to check in.");
                            return;
                        }
                        // Use their first group assignment to mark check-in
                        const groupId = groups[0].id;
                        
                        swal({
                            title: "Check In Attendance?",
                            text: "Mark your attendance check-in for today!",
                            type: "info",
                            showCancelButton: true,
                            confirmButtonColor: "#4f46e5",
                            confirmButtonText: "Yes, Check In!",
                            cancelButtonText: "Cancel",
                            closeOnConfirm: false
                        }, function(isConfirm) {
                            if (isConfirm) {
                                $.ajax({
                                    type: "POST",
                                    url: "api/user_attendance.php",
                                    data: {
                                        action: 'add_attendance',
                                        date: today,
                                        group: groupId
                                    },
                                    success: function(saveRes) {
                                        swal("Checked In!", "Your attendance check-in has been logged.", "success");
                                        setTimeout(function() {
                                            window.location.reload();
                                        }, 1200);
                                    },
                                    error: function(xhr) {
                                        try {
                                            const err = JSON.parse(xhr.responseText);
                                            alerterror(err, xhr);
                                        } catch (e) {
                                            alertwarning("Already Checked In or today is a Holiday!");
                                        }
                                    }
                                });
                            }
                        });

                    } catch(e) {
                        alertwarning("Could not retrieve group mapping: " + e.message);
                    }
                },
                error: function() {
                    alertwarning("Failed to fetch group details.");
                }
            });
        }
    </script>
</body>
</html>
