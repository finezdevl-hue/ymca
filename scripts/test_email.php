<?php
/**
 * YMCA BCP SMTP Diagnostic & Test Sender
 * Can be run via CLI: php scripts/test_email.php [recipient@example.com]
 * Or included in test.php via run_smtp_diagnostics()
 */

require_once __DIR__ . '/../config/app.php';
require_once __DIR__ . '/../includes/mailer.php';

function run_smtp_diagnostics(string $recipient = 'harikrishnamanoj79@gmail.com', bool $echoOutput = false): array {
    $logs = [];
    $log = function(string $msg) use (&$logs, $echoOutput) {
        $logs[] = $msg;
        if ($echoOutput) {
            echo $msg . "\n";
        }
    };

    $log("=======================================================");
    $log("   YMCA BCP SMTP Email Diagnostic Tool");
    $log("=======================================================");

    $host     = defined('SMTP_HOST') ? SMTP_HOST : 'smtp.gmail.com';
    $port     = defined('SMTP_PORT') ? (int)SMTP_PORT : 465;
    $secure   = defined('SMTP_SECURE') ? SMTP_SECURE : 'ssl';
    $username = defined('SMTP_USERNAME') ? SMTP_USERNAME : '';
    $password = defined('SMTP_PASSWORD') ? SMTP_PASSWORD : '';
    $from     = defined('SMTP_FROM_EMAIL') ? SMTP_FROM_EMAIL : '';
    $fromName = defined('SMTP_FROM_NAME') ? SMTP_FROM_NAME : 'YMCA BCP';
    $enabled  = defined('MAIL_ENABLED') ? (MAIL_ENABLED ? 'true' : 'false') : 'true';

    $log("Configuration:");
    $log("  • Host:         {$host}");
    $log("  • Port:         {$port}");
    $log("  • Secure:       {$secure}");
    $log("  • Username:     " . (!empty($username) ? $username : '<EMPTY (Required for Gmail)>'));
    $log("  • Password:     " . (!empty($password) ? str_repeat('*', min(strlen($password), 16)) . ' (' . strlen($password) . ' chars)' : '<EMPTY (Required for Gmail)>'));
    $log("  • From Email:   " . (!empty($from) ? $from : '<EMPTY>'));
    $log("  • From Name:    {$fromName}");
    $log("  • Mail Enabled: {$enabled}");
    $log("  • Target To:    {$recipient}\n");

    if (empty($username) || empty($password)) {
        $log("⚠️  WARNING: SMTP_USERNAME or SMTP_PASSWORD is not configured in .env!");
        $log("   Gmail SMTP (smtp.gmail.com) rejects unauthenticated emails.");
        $log("   To fix this:");
        $log("   1. Enable 2-Step Verification on your Google Account.");
        $log("   2. Generate a 16-character App Password at: https://myaccount.google.com/apppasswords");
        $log("   3. Add SMTP_USERNAME, SMTP_PASSWORD, and SMTP_FROM_EMAIL to .env\n");
    }

    $log("Starting socket test to {$host}:{$port}...");

    $timeout = 15;
    $remote = ($secure === 'ssl' ? 'ssl://' : '') . $host;
    $context = stream_context_create([
        'ssl' => [
            'verify_peer'       => false,
            'verify_peer_name'  => false,
            'allow_self_signed' => true
        ]
    ]);

    $socket = @stream_socket_client($remote . ':' . $port, $errno, $errstr, $timeout, STREAM_CLIENT_CONNECT, $context);
    if (!$socket) {
        $log("❌ Connection FAILED: {$errstr} (code {$errno})");
        return ['success' => false, 'error' => "Connection failed: $errstr ($errno)", 'log' => implode("\n", $logs)];
    }

    $log("✅ Connected to {$remote}:{$port}");

    $read = function() use ($socket) {
        $out = '';
        while (!feof($socket)) {
            $line = fgets($socket, 515);
            if ($line === false) break;
            $out .= $line;
            if (preg_match('/^\d{3} /', $line)) break;
        }
        return $out;
    };

    $send = function($cmd, $mask = false) use ($socket, $read, $log) {
        $log(">>> " . ($mask ? '******' : $cmd));
        fwrite($socket, $cmd . "\r\n");
        $resp = $read();
        $log("<<< " . trim($resp));
        return $resp;
    };

    $greet = $read();
    $log("<<< " . trim($greet));
    if (!str_starts_with($greet, '220')) {
        $log("❌ Unexpected server greeting!");
        fclose($socket);
        return ['success' => false, 'error' => "Invalid greeting: " . trim($greet), 'log' => implode("\n", $logs)];
    }

    $ehlo = $send("EHLO " . (gethostname() ?: 'localhost'));

    if ($secure === 'tls' && strpos($ehlo, 'STARTTLS') !== false) {
        $tlsResp = $send("STARTTLS");
        if (!str_starts_with($tlsResp, '220')) {
            $log("❌ STARTTLS negotiation failed.");
            fclose($socket);
            return ['success' => false, 'error' => "STARTTLS failed: " . trim($tlsResp), 'log' => implode("\n", $logs)];
        }
        if (!stream_socket_enable_crypto($socket, true, STREAM_CRYPTO_METHOD_TLS_CLIENT)) {
            $log("❌ TLS crypto enablement failed.");
            fclose($socket);
            return ['success' => false, 'error' => "TLS crypto handshake failed", 'log' => implode("\n", $logs)];
        }
        $log("✅ TLS encryption negotiated.");
        $send("EHLO " . (gethostname() ?: 'localhost'));
    }

    if (!empty($username) && !empty($password)) {
        $log("\nAttempting SMTP Authentication (AUTH LOGIN)...");
        $authResp = $send("AUTH LOGIN");
        if (!str_starts_with($authResp, '334')) {
            $log("❌ AUTH LOGIN rejected.");
            fclose($socket);
            return ['success' => false, 'error' => "AUTH LOGIN rejected: " . trim($authResp), 'log' => implode("\n", $logs)];
        }

        $userResp = $send(base64_encode($username));
        if (!str_starts_with($userResp, '334')) {
            $log("❌ Username rejected.");
            fclose($socket);
            return ['success' => false, 'error' => "Username rejected: " . trim($userResp), 'log' => implode("\n", $logs)];
        }

        $passResp = $send(base64_encode($password), true);
        if (!str_starts_with($passResp, '235')) {
            $log("❌ Authentication failed! Check your email or App Password.");
            fclose($socket);
            return ['success' => false, 'error' => "Authentication failed: " . trim($passResp), 'log' => implode("\n", $logs)];
        }
        $log("✅ Authenticated successfully!\n");
    } else {
        $log("\n⚠️  Skipping AUTH (No credentials configured). Authenticated relays will reject.\n");
    }

    $fromAddr = !empty($from) ? $from : (!empty($username) ? $username : 'noreply@ymcabcp.com');
    $mailFromResp = $send("MAIL FROM:<{$fromAddr}>");
    if (!str_starts_with($mailFromResp, '250')) {
        $log("❌ MAIL FROM failed: " . trim($mailFromResp));
        fclose($socket);
        return ['success' => false, 'error' => "MAIL FROM failed: " . trim($mailFromResp), 'log' => implode("\n", $logs)];
    }

    $rcptToResp = $send("RCPT TO:<{$recipient}>");
    if (!str_starts_with($rcptToResp, '250') && !str_starts_with($rcptToResp, '251')) {
        $log("❌ RCPT TO failed: " . trim($rcptToResp));
        fclose($socket);
        return ['success' => false, 'error' => "RCPT TO failed: " . trim($rcptToResp), 'log' => implode("\n", $logs)];
    }

    $dataResp = $send("DATA");
    if (!str_starts_with($dataResp, '354')) {
        $log("❌ DATA command failed.");
        fclose($socket);
        return ['success' => false, 'error' => "DATA failed: " . trim($dataResp), 'log' => implode("\n", $logs)];
    }

    $subject = "YMCA BCP - Test Email Delivery";
    $encodedSubject = "=?UTF-8?B?" . base64_encode($subject) . "?=";
    $encodedFrom = "=?UTF-8?B?" . base64_encode($fromName) . "?= <{$fromAddr}>";
    $boundary = "bnd_" . md5((string)microtime(true));

    $msg  = "From: {$encodedFrom}\r\n";
    $msg .= "To: <{$recipient}>\r\n";
    $msg .= "Subject: {$encodedSubject}\r\n";
    $msg .= "Date: " . date('r') . "\r\n";
    $msg .= "MIME-Version: 1.0\r\n";
    $msg .= "Content-Type: multipart/alternative; boundary=\"{$boundary}\"\r\n\r\n";
    $msg .= "--{$boundary}\r\n";
    $msg .= "Content-Type: text/plain; charset=UTF-8\r\n\r\n";
    $msg .= "This is a test email sent from YMCA BCP Member Portal.\n\nTime sent: " . date('Y-m-d H:i:s') . "\n";
    $msg .= "--{$boundary}\r\n";
    $msg .= "Content-Type: text/html; charset=UTF-8\r\n\r\n";
    $msg .= "<div style='font-family:-apple-system, BlinkMacSystemFont, Segoe UI, Roboto, sans-serif; padding:24px; background:#0b1120; color:#e2e8f0; border-radius:12px; border:1px solid #1e293b;'>";
    $msg .= "<h2 style='color:#38bdf8; margin-top:0;'>YMCA BCP Email Service Operational</h2>";
    $msg .= "<p style='color:#cbd5e1; font-size:14px; line-height:1.6;'>This test email confirms that the YMCA BCP SMTP mail service is configured correctly and actively dispatching emails!</p>";
    $msg .= "<p style='color:#64748b; font-size:12px; margin-top:20px;'>Sent on: " . date('Y-m-d H:i:s') . "</p>";
    $msg .= "</div>\r\n";
    $msg .= "--{$boundary}--\r\n";

    fwrite($socket, $msg . "\r\n.\r\n");
    $log(">>> [Message Content Sent (" . strlen($msg) . " bytes)]");
    $finalResp = $read();
    $log("<<< " . trim($finalResp));

    $send("QUIT");
    fclose($socket);

    if (str_starts_with($finalResp, '250')) {
        $log("\n🎉 SUCCESS! Test email has been delivered/queued by the SMTP server to {$recipient}!\n");
        return ['success' => true, 'error' => null, 'log' => implode("\n", $logs)];
    }

    $log("\n❌ Delivery FAILED: " . trim($finalResp) . "\n");
    return ['success' => false, 'error' => "Delivery rejected: " . trim($finalResp), 'log' => implode("\n", $logs)];
}

// If invoked from CLI directly
if (php_sapi_name() === 'cli' && isset($argv) && basename($argv[0] ?? '') === 'test_email.php') {
    $target = $argv[1] ?? 'harikrishnamanoj79@gmail.com';
    $res = run_smtp_diagnostics($target, true);
    exit($res['success'] ? 0 : 1);
}
