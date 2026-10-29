<?php
/**
 * YMCA BCP SMTP Mailer Service
 * Provides secure SSL/TLS SMTP email dispatch and responsive email templates.
 */

require_once __DIR__ . '/../config/app.php';

if (!function_exists('send_smtp_mail')) {
    /**
     * Send an email via direct SMTP socket.
     *
     * @param string $to Recipient email address
     * @param string $subject Email subject line
     * @param string $htmlBody HTML content
     * @param string $textBody Plain text fallback
     * @param string|null $fromEmail Sender address
     * @param string|null $fromName Sender display name
     * @return bool True if accepted by the SMTP server
     */
    function send_smtp_mail(string $to, string $subject, string $htmlBody, string $textBody = '', ?string $fromEmail = null, ?string $fromName = null): bool {
        if (!defined('MAIL_ENABLED') || !MAIL_ENABLED) {
            return false;
        }

        $host     = defined('SMTP_HOST') ? SMTP_HOST : (getenv('SMTP_HOST') ?: 'smtp.gmail.com');
        $port     = defined('SMTP_PORT') ? (int)SMTP_PORT : (int)(getenv('SMTP_PORT') ?: 465);
        $secure   = defined('SMTP_SECURE') ? strtolower(SMTP_SECURE) : strtolower(getenv('SMTP_SECURE') ?: 'ssl');
        $username = defined('SMTP_USERNAME') ? SMTP_USERNAME : (getenv('SMTP_USERNAME') ?: '');
        $password = defined('SMTP_PASSWORD') ? SMTP_PASSWORD : (getenv('SMTP_PASSWORD') ?: '');
        $from     = $fromEmail ?: (defined('SMTP_FROM_EMAIL') ? SMTP_FROM_EMAIL : (getenv('SMTP_FROM_EMAIL') ?: $username));
        $name     = $fromName ?: (defined('SMTP_FROM_NAME') ? SMTP_FROM_NAME : (getenv('SMTP_FROM_NAME') ?: 'YMCA BCP'));

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
            if (function_exists('app_log_error')) {
                app_log_error("SMTP Connection failed to $remote:$port - $errstr ($errno)");
            }
            return false;
        }

        stream_set_timeout($socket, $timeout);

        $readResponse = function() use ($socket): string {
            $data = '';
            while (!feof($socket)) {
                $line = fgets($socket, 515);
                if ($line === false) break;
                $data .= $line;
                if (preg_match('/^\d{3} /', $line)) break;
            }
            return $data;
        };

        $sendCommand = function(string $cmd) use ($socket, $readResponse): string {
            fwrite($socket, $cmd . "\r\n");
            return $readResponse();
        };

        $greet = $readResponse();
        if (!str_starts_with($greet, '220')) {
            fclose($socket);
            return false;
        }

        $ehlo = $sendCommand("EHLO " . (gethostname() ?: 'localhost'));

        // Handle STARTTLS negotiation if required
        if ($secure === 'tls' && strpos($ehlo, 'STARTTLS') !== false) {
            $tlsResp = $sendCommand("STARTTLS");
            if (!str_starts_with($tlsResp, '220')) {
                fclose($socket);
                return false;
            }
            if (!stream_socket_enable_crypto($socket, true, STREAM_CRYPTO_METHOD_TLS_CLIENT)) {
                fclose($socket);
                return false;
            }
            $sendCommand("EHLO " . (gethostname() ?: 'localhost'));
        }

        // Authenticate with server
        if (!empty($username) && !empty($password)) {
            $authResp = $sendCommand("AUTH LOGIN");
            if (!str_starts_with($authResp, '334')) {
                fclose($socket);
                return false;
            }

            $userResp = $sendCommand(base64_encode($username));
            if (!str_starts_with($userResp, '334')) {
                fclose($socket);
                return false;
            }

            $passResp = $sendCommand(base64_encode($password));
            if (!str_starts_with($passResp, '235')) {
                fclose($socket);
                return false;
            }
        }

        // SMTP Envelope
        $mailFromResp = $sendCommand("MAIL FROM:<$from>");
        if (!str_starts_with($mailFromResp, '250')) {
            fclose($socket);
            return false;
        }

        $rcptToResp = $sendCommand("RCPT TO:<$to>");
        if (!str_starts_with($rcptToResp, '250') && !str_starts_with($rcptToResp, '251')) {
            fclose($socket);
            return false;
        }

        $dataResp = $sendCommand("DATA");
        if (!str_starts_with($dataResp, '354')) {
            fclose($socket);
            return false;
        }

        // Build Multipart/Alternative Email
        $boundary = "bnd_" . md5(uniqid((string)microtime(true), true));
        $encodedSubject = "=?UTF-8?B?" . base64_encode($subject) . "?=";
        $encodedFrom = "=?UTF-8?B?" . base64_encode($name) . "?= <$from>";
        $date = date('r');
        $msgId = "<" . md5(uniqid((string)microtime(true), true)) . "@" . (parse_url(app_env('APP_URL', 'http://localhost'), PHP_URL_HOST) ?: 'localhost') . ">";

        if (empty($textBody)) {
            $textBody = strip_tags(str_replace(['<br>', '<br/>', '<br />', '</p>'], "\n", $htmlBody));
        }

        $headers = [
            "Date: $date",
            "From: $encodedFrom",
            "To: <$to>",
            "Subject: $encodedSubject",
            "Message-ID: $msgId",
            "MIME-Version: 1.0",
            "Content-Type: multipart/alternative; boundary=\"$boundary\"",
            "X-Mailer: YMCA-BCP Mailer"
        ];

        $content  = implode("\r\n", $headers) . "\r\n\r\n";
        $content .= "--$boundary\r\n";
        $content .= "Content-Type: text/plain; charset=UTF-8\r\n";
        $content .= "Content-Transfer-Encoding: base64\r\n\r\n";
        $content .= chunk_split(base64_encode($textBody)) . "\r\n";
        $content .= "--$boundary\r\n";
        $content .= "Content-Type: text/html; charset=UTF-8\r\n";
        $content .= "Content-Transfer-Encoding: base64\r\n\r\n";
        $content .= chunk_split(base64_encode($htmlBody)) . "\r\n";
        $content .= "--$boundary--\r\n";

        // Dot-stuffing for RFC compliance
        $content = preg_replace('/^\./m', '..', $content);

        fwrite($socket, $content . "\r\n.\r\n");
        $sendResp = $readResponse();

        $sendCommand("QUIT");
        fclose($socket);

        return str_starts_with($sendResp, '250');
    }
}

if (!function_exists('send_password_reset_email')) {
    /**
     * Send password reset email with responsive HTML and plain text template.
     *
     * @param string $recipientEmail
     * @param string $recipientName
     * @param string $resetLink
     * @return bool
     */
    function send_password_reset_email(string $recipientEmail, string $recipientName, string $resetLink): bool {
        $name = !empty(trim($recipientName)) ? htmlspecialchars(trim($recipientName)) : 'Member';
        $subject = "YMCA BCP - Password Reset Request";

        $html = <<<HTML
<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Password Reset Request</title>
</head>
<body style="margin: 0; padding: 0; background-color: #0b1120; font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Helvetica, Arial, sans-serif; color: #e2e8f0;">
    <table role="presentation" width="100%" cellspacing="0" cellpadding="0" border="0" style="background-color: #0b1120; padding: 40px 15px;">
        <tr>
            <td align="center">
                <table role="presentation" width="100%" style="max-width: 540px; background-color: #1e293b; border-radius: 16px; border: 1px solid rgba(255, 255, 255, 0.08); overflow: hidden; box-shadow: 0 20px 25px -5px rgba(0, 0, 0, 0.5);" cellspacing="0" cellpadding="0" border="0">
                    <!-- Header -->
                    <tr>
                        <td style="padding: 32px 36px; background: linear-gradient(135deg, #1e3a8a 0%, #312e81 100%); border-bottom: 1px solid rgba(255, 255, 255, 0.1); text-align: left;">
                            <div style="font-size: 11px; font-weight: 700; letter-spacing: 0.12em; text-transform: uppercase; color: #93c5fd; margin-bottom: 4px;">Security & Account Access</div>
                            <h1 style="margin: 0; font-size: 22px; font-weight: 800; color: #ffffff; letter-spacing: -0.01em;">YMCA BCP</h1>
                        </td>
                    </tr>

                    <!-- Body -->
                    <tr>
                        <td style="padding: 36px 36px 28px 36px;">
                            <h2 style="margin: 0 0 16px 0; font-size: 18px; font-weight: 700; color: #f8fafc;">Password Reset Request</h2>
                            <p style="margin: 0 0 20px 0; font-size: 14px; line-height: 1.6; color: #94a3b8;">
                                Hello <strong style="color: #f1f5f9;">{$name}</strong>,<br><br>
                                We received a request to reset the password for your YMCA BCP account. Click the button below to set a new password:
                            </p>

                            <!-- CTA Button -->
                            <div style="text-align: center; margin: 30px 0;">
                                <a href="{$resetLink}" style="display: inline-block; background: linear-gradient(135deg, #2563eb 0%, #4f46e5 100%); color: #ffffff; text-decoration: none; font-size: 15px; font-weight: 700; padding: 14px 32px; border-radius: 10px; box-shadow: 0 4px 14px rgba(79, 70, 229, 0.4);">Reset Password</a>
                            </div>

                            <p style="margin: 20px 0 0 0; font-size: 13px; line-height: 1.6; color: #64748b;">
                                This link will expire in <strong style="color: #cbd5e1;">1 hour</strong>. If you did not request this password reset, please ignore this email and your account password will remain safe and unchanged.
                            </p>

                            <hr style="border: none; border-top: 1px solid rgba(255, 255, 255, 0.08); margin: 28px 0 20px 0;">

                            <p style="margin: 0; font-size: 12px; line-height: 1.5; color: #64748b; word-break: break-all;">
                                If the button above does not work, copy and paste this URL into your browser:<br>
                                <a href="{$resetLink}" style="color: #60a5fa; text-decoration: underline;">{$resetLink}</a>
                            </p>
                        </td>
                    </tr>

                    <!-- Footer -->
                    <tr>
                        <td style="padding: 20px 36px 28px 36px; background-color: #0f172a; text-align: center; border-top: 1px solid rgba(255, 255, 255, 0.05);">
                            <p style="margin: 0; font-size: 12px; color: #475569;">
                                © 2026 YMCA BCP Member Management System. All rights reserved.
                            </p>
                        </td>
                    </tr>
                </table>
            </td>
        </tr>
    </table>
</body>
</html>
HTML;

        $text = "Hello {$name},\n\n"
              . "We received a request to reset the password for your YMCA BCP account.\n\n"
              . "Click the link below or paste it into your browser to reset your password (valid for 1 hour):\n"
              . "{$resetLink}\n\n"
              . "If you did not request a password reset, you can safely ignore this email.\n\n"
              . "— YMCA BCP Member Management System";

        // Dispatch via SMTP
        $sent = send_smtp_mail($recipientEmail, $subject, $html, $text);

        // Fallback to mail() if SMTP dispatch fails
        if (!$sent) {
            $headers  = "MIME-Version: 1.0\r\n";
            $headers .= "Content-Type: text/html; charset=UTF-8\r\n";
            $headers .= "From: YMCA BCP <" . (defined('SMTP_FROM_EMAIL') ? SMTP_FROM_EMAIL : 'no-reply@ymcabcp.com') . ">\r\n";
            $headers .= "X-Mailer: PHP/" . phpversion();
            $sent = @mail($recipientEmail, $subject, $html, $headers);
        }

        return $sent;
    }
}
