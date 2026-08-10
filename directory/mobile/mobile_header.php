<?php
// Shared mobile top header bar
// Usage: include 'mobile_header.php'; (pass $page_title before including)
if (!isset($page_title)) $page_title = 'YMCA';
?>
<header class="mob-header">
    <div class="mob-header-brand">
        <div class="mob-header-logo" style="background: #ffffff; padding: 3px; display: flex; align-items: center; justify-content: center; border-radius: 10px; box-shadow: 0 2px 6px rgba(0,0,0,0.15);">
            <img src="../../favicon.png" alt="YMCA Logo" style="width: 100%; height: 100%; object-fit: contain; border-radius: 6px;" onerror="this.onerror=null; this.src='../../favicon.ico';">
        </div>
        <div class="mob-header-title">
            <?php echo htmlspecialchars($page_title); ?>
            <span>YMCA Member Portal</span>
        </div>
    </div>
    <div class="mob-header-actions">
        <a href="../../app_login_manager/logout.php" class="mob-header-btn" title="Logout">
            <i class="fa fa-sign-out"></i>
        </a>
    </div>
</header>
<script src="../../app_js/date_picker_auto_init.js"></script>
