<?php
// Shared mobile bottom navigation bar
// Usage: include 'mobile_bottom_nav.php'; (pass $active_tab before including)
if (!isset($active_tab)) $active_tab = 'attendance';
include_once __DIR__ . '/../../app_common/auth_helper.php';

$mob_login_id = (int)($_SESSION['login_id'] ?? 0);
$is_admin_mob = isGroupAdmin($mob_login_id);
?>
<nav class="mob-bottom-nav" id="mob-bottom-nav">
    <?php if ($is_admin_mob): ?>
    <a href="members.php" class="mob-nav-tab <?php echo ($active_tab === 'members') ? 'active' : ''; ?>" id="nav-members">
        <span class="mob-nav-icon"><i class="fa fa-users"></i></span>
        Members
    </a>
    <a href="group_attendance.php" class="mob-nav-tab <?php echo ($active_tab === 'attendance' || $active_tab === 'home') ? 'active' : ''; ?>" id="nav-attendance">
        <span class="mob-nav-icon"><i class="fa fa-calendar-check-o"></i></span>
        Attendance
    </a>
    <a href="accounts.php" class="mob-nav-tab <?php echo ($active_tab === 'accounts') ? 'active' : ''; ?>" id="nav-accounts">
        <span class="mob-nav-icon"><i class="fa fa-calculator"></i></span>
        Accounts
    </a>
    <?php else: ?>
    <a href="home.php" class="mob-nav-tab <?php echo ($active_tab === 'attendance' || $active_tab === 'home') ? 'active' : ''; ?>" id="nav-attendance">
        <span class="mob-nav-icon"><i class="fa fa-calendar-check-o"></i></span>
        Attendance
    </a>
    <a href="ledger.php" class="mob-nav-tab <?php echo ($active_tab === 'ledger' || $active_tab === 'accounts') ? 'active' : ''; ?>" id="nav-ledger">
        <span class="mob-nav-icon"><i class="fa fa-book"></i></span>
        Ledger
    </a>
    <?php endif; ?>
    <a href="reports.php" class="mob-nav-tab <?php echo ($active_tab === 'reports') ? 'active' : ''; ?>" id="nav-reports">
        <span class="mob-nav-icon"><i class="fa fa-bar-chart"></i></span>
        Reports
    </a>
    <a href="profile.php" class="mob-nav-tab <?php echo ($active_tab === 'profile') ? 'active' : ''; ?>" id="nav-profile">
        <span class="mob-nav-icon"><i class="fa fa-user-circle"></i></span>
        Profile
    </a>
</nav>

<script>
function load_overlay() {
    if ($('#mob-global-loader').length === 0) {
        $('body').append(`
            <div id="mob-global-loader">
                <div class="mob-spinner-box">
                    <div class="mob-spinner-wrapper">
                        <div class="mob-spinner-ring"></div>
                        <img src="../../favicon.png" class="mob-spinner-logo" alt="YMCA Logo" onerror="this.onerror=null; this.src='../../favicon.ico';">
                    </div>
                    <div class="mob-spinner-text">Loading...</div>
                </div>
            </div>
        `);
    }
    $('#mob-global-loader').addClass('active');
}

function close_overlay() {
    $('#mob-global-loader').removeClass('active');
}

document.addEventListener('DOMContentLoaded', function() {
    const links = document.querySelectorAll('a[href]');
    links.forEach(function(link) {
        link.addEventListener('click', function(e) {
            const href = link.getAttribute('href');
            if (href && href !== '#' && !href.startsWith('javascript:') && !href.startsWith('mailto:') && 
                !href.startsWith('tel:') && !href.startsWith('upi:') && !href.startsWith('tez:') && 
                !href.startsWith('phonepe:') && !href.startsWith('paytmmp:')) {
                if (typeof load_overlay === 'function') {
                    load_overlay();
                }
            }
        });
    });
});

window.addEventListener('pageshow', function() {
    close_overlay();
});

document.addEventListener('visibilitychange', function() {
    if (document.visibilityState === 'visible') {
        close_overlay();
    }
});
</script>
