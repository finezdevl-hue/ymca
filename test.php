<?php
error_reporting(E_ALL);
ini_set('display_errors', '1');

$action_message = '';
$action_status = '';

// Handle direct password reset action
if (($_SERVER['REQUEST_METHOD'] ?? '') === 'POST' && isset($_POST['action']) && $_POST['action'] === 'reset_admin') {
    try {
        require_once __DIR__ . '/config/database.php';
        $config = require __DIR__ . '/config/database.php';
        $conn = new mysqli($config['host'], $config['username'], $config['password'], $config['database'], $config['port']);
        if (!$conn->connect_error) {
            $new_hash = password_hash('Admin@123', PASSWORD_DEFAULT);
            $stmt = $conn->prepare("UPDATE tbl_login SET password = ? WHERE login_id = 1 OR email = 'finezseo@gmail.com'");
            if ($stmt) {
                $stmt->bind_param("s", $new_hash);
                $stmt->execute();
                if ($stmt->affected_rows > 0 || $stmt->errno === 0) {
                    $action_status = 'success';
                    $action_message = 'Admin password has been reset to: <strong>Admin@123</strong>';
                } else {
                    $action_status = 'warning';
                    $action_message = 'No rows were updated. Check if tbl_login has login_id=1 or finezseo@gmail.com.';
                }
                $stmt->close();
            }
            $conn->close();
        }
    } catch (Throwable $e) {
        $action_status = 'danger';
        $action_message = 'Reset failed: ' . htmlspecialchars($e->getMessage());
    }
}

// Handle test email action
$smtp_diag_result = null;
if (($_SERVER['REQUEST_METHOD'] ?? '') === 'POST' && isset($_POST['action']) && $_POST['action'] === 'test_email') {
    $test_target = trim($_POST['test_recipient'] ?? 'harikrishnamanoj79@gmail.com');
    if (empty($test_target) || !filter_var($test_target, FILTER_VALIDATE_EMAIL)) {
        $action_status = 'danger';
        $action_message = 'Please provide a valid recipient email address for testing.';
    } else {
        require_once __DIR__ . '/scripts/test_email.php';
        $smtp_diag_result = run_smtp_diagnostics($test_target, false);
        if ($smtp_diag_result['success']) {
            $action_status = 'success';
            $action_message = 'Test email successfully sent to: <strong>' . htmlspecialchars($test_target) . '</strong>! Check inbox / spam.';
        } else {
            $action_status = 'danger';
            $action_message = 'Failed to send test email: ' . htmlspecialchars($smtp_diag_result['error'] ?? 'Unknown error');
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>YMCA ERP Server Diagnostics</title>
    <style>
        body {
            font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, sans-serif;
            padding: 30px;
            line-height: 1.6;
            background: #0b1120;
            color: #f1f5f9;
            max-width: 860px;
            margin: 0 auto;
        }
        .card {
            background: #1e293b;
            border: 1px solid #334155;
            border-radius: 12px;
            padding: 20px 24px;
            margin-bottom: 20px;
        }
        h1 { color: #38bdf8; margin-top: 0; font-size: 24px; }
        h2 { color: #94a3b8; font-size: 17px; margin-top: 0; margin-bottom: 12px; border-bottom: 1px solid #334155; padding-bottom: 8px; }
        .badge {
            display: inline-block;
            padding: 3px 10px;
            border-radius: 999px;
            font-size: 12px;
            font-weight: 600;
        }
        .badge-success { background: rgba(52, 211, 153, 0.2); color: #34d399; border: 1px solid #059669; }
        .badge-danger { background: rgba(248, 113, 113, 0.2); color: #f87171; border: 1px solid #dc2626; }
        .badge-warning { background: rgba(251, 191, 36, 0.2); color: #fbbf24; border: 1px solid #d97706; }
        .btn {
            background: #2563eb;
            color: white;
            border: none;
            padding: 10px 18px;
            border-radius: 8px;
            font-weight: 600;
            cursor: pointer;
            font-size: 14px;
        }
        .btn:hover { background: #1d4ed8; }
        code { background: #0f172a; padding: 2px 6px; border-radius: 4px; color: #38bdf8; font-size: 13px; }
        .alert {
            padding: 12px 16px;
            border-radius: 8px;
            margin-bottom: 16px;
            font-size: 14px;
        }
        .alert-success { background: rgba(52, 211, 153, 0.15); border: 1px solid #34d399; color: #a7f3d0; }
        .alert-danger { background: rgba(248, 113, 113, 0.15); border: 1px solid #f87171; color: #fecaca; }
        .alert-warning { background: rgba(251, 191, 36, 0.15); border: 1px solid #fbbf24; color: #fde68a; }
        ul { margin: 8px 0; padding-left: 20px; }
        li { margin-bottom: 4px; }
    </style>
</head>
<body>

    <h1>YMCA ERP Server &amp; Database Diagnostics</h1>
    <p style="color:#94a3b8; font-size:14px;">This diagnostic script checks PHP, <code>.env</code> settings, MySQL connectivity, and database tables.</p>

    <?php if (!empty($action_message)): ?>
    <div class="alert alert-<?php echo $action_status; ?>">
        <?php echo $action_message; ?>
    </div>
    <?php endif; ?>

    <!-- 1. PHP Environment -->
    <div class="card">
        <h2>1. PHP Environment</h2>
        <p><strong>PHP Version:</strong> <?php echo PHP_VERSION; ?> 
            <?php if (PHP_VERSION_ID >= 80000): ?>
                <span class="badge badge-success">PHP 8+ Compatible</span>
            <?php else: ?>
                <span class="badge badge-warning">PHP 7.4 (Upgrade recommended)</span>
            <?php endif; ?>
        </p>

        <p><strong>Required Extensions:</strong></p>
        <ul>
            <?php
            $extensions = ['mysqli', 'mbstring', 'curl', 'json', 'openssl', 'gd'];
            foreach ($extensions as $ext) {
                $ok = extension_loaded($ext);
                echo "<li><code>$ext</code>: " . ($ok ? "<span class='badge badge-success'>Installed</span>" : "<span class='badge badge-danger'>Missing</span>") . "</li>";
            }
            ?>
        </ul>
    </div>

    <!-- 2. Configuration & .env -->
    <div class="card">
        <h2>2. Configuration &amp; .env File</h2>
        <?php
        $env_file = __DIR__ . '/.env';
        $has_env = file_exists($env_file);
        ?>
        <p><strong>.env File:</strong> 
            <?php if ($has_env): ?>
                <span class="badge badge-success">Found at root</span>
            <?php else: ?>
                <span class="badge badge-warning">Not found (Using config/database.php fallbacks)</span>
            <?php endif; ?>
        </p>

        <?php
        require_once __DIR__ . '/config/env.php';
        require_once __DIR__ . '/config/database.php';
        $config = require __DIR__ . '/config/database.php';
        ?>

        <ul>
            <li><strong>DB_HOST:</strong> <code><?php echo htmlspecialchars($config['host']); ?></code></li>
            <li><strong>DB_PORT:</strong> <code><?php echo htmlspecialchars($config['port']); ?></code></li>
            <li><strong>DB_USER:</strong> <code><?php echo htmlspecialchars($config['username']); ?></code></li>
            <li><strong>DB_NAME:</strong> <code><?php echo htmlspecialchars($config['database']); ?></code></li>
            <li><strong>Password Set:</strong> <?php echo !empty($config['password']) ? '<span class="badge badge-success">Yes (' . strlen($config['password']) . ' chars)</span>' : '<span class="badge badge-danger">Empty</span>'; ?></li>
        </ul>
    </div>

    <!-- 3. MySQL Database Test -->
    <div class="card">
        <h2>3. MySQL Database Connection</h2>
        <?php
        $db_connected = false;
        $conn_error = '';
        $conn = null;

        try {
            $conn = @new mysqli($config['host'], $config['username'], $config['password'], $config['database'], $config['port']);
            if ($conn->connect_error) {
                $conn_error = $conn->connect_error;
            } else {
                $db_connected = true;
            }
        } catch (Throwable $e) {
            $conn_error = $e->getMessage();
        }

        if (!$db_connected):
        ?>
            <div class="alert alert-danger">
                <strong>Connection Failed:</strong> <?php echo htmlspecialchars($conn_error); ?>
            </div>
            <p><strong>How to fix this in cPanel:</strong></p>
            <ol style="color:#cbd5e1; font-size:14px;">
                <li>Open <strong>cPanel &rarr; MySQL Databases</strong>.</li>
                <li>Verify database <code><?php echo htmlspecialchars($config['database']); ?></code> exists.</li>
                <li>Verify user <code><?php echo htmlspecialchars($config['username']); ?></code> exists.</li>
                <li>Under <strong>Add User To Database</strong>: select user <code><?php echo htmlspecialchars($config['username']); ?></code> and database <code><?php echo htmlspecialchars($config['database']); ?></code>, click <strong>Add</strong>, and check <strong>ALL PRIVILEGES</strong>.</li>
                <li>If credentials differ, edit or create a <code>.env</code> file in your server root with the exact cPanel database credentials.</li>
            </ol>
        <?php else: ?>
            <div class="alert alert-success">
                <strong>MySQL Connected Successfully!</strong>
            </div>

            <?php
            // Check tables
            $tables_res = $conn->query("SHOW TABLES");
            $table_count = $tables_res ? $tables_res->num_rows : 0;
            ?>
            <p><strong>Total Tables in Database:</strong> <?php echo $table_count; ?>
                <?php if ($table_count === 0): ?>
                    <span class="badge badge-danger">Empty Database! Import backup_full_data.sql</span>
                <?php elseif ($table_count < 20): ?>
                    <span class="badge badge-warning">Partial Database (<?php echo $table_count; ?> tables)</span>
                <?php else: ?>
                    <span class="badge badge-success">Complete Database (<?php echo $table_count; ?> tables)</span>
                <?php endif; ?>
            </p>

            <!-- 4. Login Table Check -->
            <h3 style="color:#94a3b8; font-size:15px; margin-top:20px;">4. Admin Login Accounts</h3>
            <?php
            $tbl_check = $conn->query("SHOW TABLES LIKE 'tbl_login'");
            if (!$tbl_check || $tbl_check->num_rows === 0):
            ?>
                <div class="alert alert-danger">
                    Table <code>tbl_login</code> does NOT exist! Please import <code>database/backup_full_data.sql</code> into this database via cPanel phpMyAdmin.
                </div>
            <?php
            else:
                $users_res = $conn->query("SELECT login_id, name, email, password FROM tbl_login");
                $user_count = $users_res ? $users_res->num_rows : 0;
            ?>
                <p>Found <strong><?php echo $user_count; ?></strong> user account(s) in <code>tbl_login</code>.</p>
                <?php if ($user_count === 0): ?>
                    <div class="alert alert-danger">
                        <code>tbl_login</code> is empty! Please import <code>database/backup_full_data.sql</code> via phpMyAdmin.
                    </div>
                <?php else: ?>
                    <table style="width:100%; border-collapse:collapse; margin-top:10px; font-size:13px;">
                        <thead>
                            <tr style="text-align:left; background:#0f172a; border-bottom:1px solid #334155;">
                                <th style="padding:8px;">ID</th>
                                <th style="padding:8px;">Name</th>
                                <th style="padding:8px;">Email</th>
                                <th style="padding:8px;">Password Verify (Admin@123)</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php
                            $admin_exists = false;
                            while ($u = $users_res->fetch_assoc()):
                                $is_admin_user = ($u['login_id'] == 1 || $u['email'] === 'finezseo@gmail.com');
                                if ($is_admin_user) $admin_exists = true;
                                $pw_ok = password_verify('Admin@123', $u['password']) || $u['password'] === 'Admin@123';
                            ?>
                            <tr style="border-bottom:1px solid #334155;">
                                <td style="padding:8px;"><?php echo $u['login_id']; ?></td>
                                <td style="padding:8px;"><?php echo htmlspecialchars($u['name']); ?></td>
                                <td style="padding:8px;"><code><?php echo htmlspecialchars($u['email']); ?></code></td>
                                <td style="padding:8px;">
                                    <?php if ($pw_ok): ?>
                                        <span class="badge badge-success">Matches Admin@123</span>
                                    <?php else: ?>
                                        <span class="badge badge-warning">Different Password</span>
                                    <?php endif; ?>
                                </td>
                            </tr>
                            <?php endwhile; ?>
                        </tbody>
                    </table>

                    <div style="margin-top:20px;">
                        <form method="POST" style="display:inline;">
                            <input type="hidden" name="action" value="reset_admin">
                            <button type="submit" class="btn" onclick="return confirm('Reset admin password to Admin@123?');">
                                Reset Admin Password to Admin@123
                            </button>
                        </form>
                    </div>
                <?php endif; ?>
            <?php endif; ?>

            <?php if ($conn) $conn->close(); ?>
        <?php endif; ?>
    </div>

    <!-- 5. SMTP Mail Dispatcher & Live Test -->
    <div class="card">
        <h2>5. SMTP Email Configuration &amp; Live Test</h2>
        <?php
        require_once __DIR__ . '/config/app.php';
        $smtp_h = defined('SMTP_HOST') ? SMTP_HOST : '';
        $smtp_p = defined('SMTP_PORT') ? SMTP_PORT : '';
        $smtp_s = defined('SMTP_SECURE') ? SMTP_SECURE : '';
        $smtp_u = defined('SMTP_USERNAME') ? SMTP_USERNAME : '';
        $smtp_pw = defined('SMTP_PASSWORD') ? SMTP_PASSWORD : '';
        $smtp_f = defined('SMTP_FROM_EMAIL') ? SMTP_FROM_EMAIL : '';
        $smtp_has_cred = !empty($smtp_u) && !empty($smtp_pw);
        ?>
        <ul>
            <li><strong>SMTP Host:</strong> <code><?php echo htmlspecialchars($smtp_h ?: 'Not set'); ?>:<?php echo htmlspecialchars((string)$smtp_p); ?></code> (<?php echo htmlspecialchars($smtp_s); ?>)</li>
            <li><strong>SMTP User:</strong> <?php echo !empty($smtp_u) ? '<code>' . htmlspecialchars($smtp_u) . '</code>' : '<span class="badge badge-danger">Empty</span>'; ?></li>
            <li><strong>SMTP Password:</strong> <?php echo !empty($smtp_pw) ? '<span class="badge badge-success">Configured (' . strlen($smtp_pw) . ' chars)</span>' : '<span class="badge badge-danger">Empty</span>'; ?></li>
            <li><strong>From Email:</strong> <code><?php echo htmlspecialchars($smtp_f ?: 'Not set'); ?></code></li>
            <li><strong>Credentials Status:</strong> 
                <?php if ($smtp_has_cred): ?>
                    <span class="badge badge-success">Credentials Present</span>
                <?php else: ?>
                    <span class="badge badge-danger">Missing Credentials in .env</span>
                <?php endif; ?>
            </li>
        </ul>

        <?php if (!$smtp_has_cred): ?>
            <div class="alert alert-warning" style="margin-top:14px;">
                <strong>⚠️ Why Password Reset Mail Fails:</strong><br>
                <code>smtp.gmail.com</code> requires an authenticated Gmail address and a 16-character <strong>Google App Password</strong>.<br>
                Because <code>SMTP_USERNAME</code> and <code>SMTP_PASSWORD</code> are currently empty in your <code>.env</code> file, Google immediately rejects all outgoing emails with <code>530 Authentication Required</code>.<br><br>
                <strong>How to enable sending:</strong>
                <ol style="margin: 6px 0 0 18px; padding: 0;">
                    <li>Open your Google Account &rarr; Security &rarr; Enable 2-Step Verification.</li>
                    <li>Generate a 16-character App Password at <a href="https://myaccount.google.com/apppasswords" target="_blank" style="color:#38bdf8;">myaccount.google.com/apppasswords</a>.</li>
                    <li>Open <code>.env</code> and add:
                        <pre style="background:#0f172a; padding:8px 12px; border-radius:6px; margin-top:6px; color:#a7f3d0; font-size:12px;">SMTP_USERNAME=your_email@gmail.com
SMTP_PASSWORD=your_16_char_app_password
SMTP_FROM_EMAIL=your_email@gmail.com</pre>
                    </li>
                </ol>
            </div>
        <?php endif; ?>

        <h3 style="color:#94a3b8; font-size:15px; margin-top:20px;">Send Live Test Email</h3>
        <form method="POST" style="margin-top:10px;">
            <input type="hidden" name="action" value="test_email">
            <div style="display:flex; gap:10px; flex-wrap:wrap; align-items:center;">
                <input type="email" name="test_recipient" value="harikrishnamanoj79@gmail.com" required style="background:#0f172a; border:1px solid #334155; color:#f1f5f9; padding:9px 14px; border-radius:8px; font-size:14px; min-width:280px;">
                <button type="submit" class="btn">
                    <i class="fa fa-paper-plane"></i> Send Test Email
                </button>
            </div>
        </form>

        <?php if (!empty($smtp_diag_result['log'])): ?>
            <h4 style="color:#94a3b8; font-size:13px; margin-top:20px; margin-bottom:8px;">Live SMTP Socket Diagnostic Log:</h4>
            <pre style="background:#0f172a; border:1px solid #334155; border-radius:8px; padding:12px 14px; font-size:12px; line-height:1.5; color:#cbd5e1; max-height:280px; overflow-y:auto; white-space:pre-wrap;"><?php echo htmlspecialchars($smtp_diag_result['log']); ?></pre>
        <?php endif; ?>
    </div>

    <div style="color:#64748b; font-size:12px; margin-top:30px; text-align:center;">
        <p><a href="index.php" style="color:#38bdf8; text-decoration:none;">&larr; Return to YMCA Portal Login</a></p>
        <p>Security Warning: Please delete this file (<code>test.php</code>) from your server once login is verified.</p>
    </div>

</body>
</html>
