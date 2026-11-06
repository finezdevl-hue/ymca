<?php
/**
 * WhatsApp Notification Service & API Integration
 * YMCA Badminton Club ERP
 */

if (defined('APP_WHATSAPP_LOADED')) {
    return;
}
define('APP_WHATSAPP_LOADED', true);

require_once __DIR__ . '/../database/Connection.php';
require_once __DIR__ . '/../config/app.php';
require_once __DIR__ . '/logger.php';

/**
 * Get base URL of the application
 */
function getAppBaseUrl(): string {
    // Prefer the configured APP_URL to avoid trusting user-controlled HTTP_HOST header
    if (defined('APP_URL') && !empty(APP_URL)) {
        return rtrim(APP_URL, '/');
    }
    if (function_exists('app_env')) {
        $envUrl = app_env('APP_URL', '');
        if (!empty($envUrl)) {
            return rtrim($envUrl, '/');
        }
    }
    // Fallback: derive from server variables (HTTP_HOST is user-controlled — only used as last resort)
    if (!empty($_SERVER['HTTP_HOST'])) {
        $protocol = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? "https://" : "http://";
        $host = $_SERVER['HTTP_HOST'];
        $script = $_SERVER['SCRIPT_NAME'] ?? '';
        $parts = explode('/', trim($script, '/'));
        $baseFolder = (!empty($parts[0]) && $parts[0] !== 'index.php' && $parts[0] !== 'invoice' && $parts[0] !== 'directory') ? '/' . $parts[0] : '';
        return rtrim($protocol . $host . $baseFolder, '/');
    }
    return 'http://localhost/ymca_test';
}

/**
 * Fetch WhatsApp API and template configurations
 * Reads directly from config/whatsapp.php and environment
 */
function getWhatsAppSettings(): array {
    $defaults = [
        'id'                  => 1,
        'provider'            => 'meta', // 'meta', 'gateway', 'wame'
        'is_active'           => 1,
        'auto_send_payment'   => 1,
        'send_pdf_receipt'    => 1,
        'api_url'             => '',
        'api_token'           => '',
        'phone_number_id'     => '',
        'business_account_id' => '',
        'sender_phone'        => '',
        'template_payment'    => "Dear *{member_name}*, your fee payment for *{club_name}* is confirmed!\n\nReceipt Number: *#{receipt_no}*\nAmount Received: *₹{amount}*\nPayment Date: *{date}*\nPayment Mode: *{payment_mode}*\nCurrent Balance Due: *₹{balance}*\n\nYour official Payment Receipt PDF has been attached to this message.\n\nThank you for your timely payment and support!",
        'template_balance'    => "🏸 *{club_name}*\n━━━━━━━━━━━━━━━━━━━━\n*MEMBERSHIP DUES REMINDER*\n\nDear *{member_name}*,\n\nWe would like to remind you that your monthly membership dues have a pending balance:\n\n💰 *Amount Due:* *₹{balance}*\n📲 *UPI ID:* {upi_id} ({payee_name})\n\nPlease clear the dues to ensure uninterrupted access to club facilities.\n\nThank you for your prompt payment!\n━━━━━━━━━━━━━━━━━━━━",
        'upi_id'              => 'ymcabcp@okaxis',
        'payee_name'          => 'YMCA BCP Poovathussery',
        'club_name'           => defined('CLUB_NAME') ? CLUB_NAME : 'YMCA BCP Poovathussery'
    ];

    // 1. Load directly from config/whatsapp.php file
    $configFile = __DIR__ . '/../config/whatsapp.php';
    if (file_exists($configFile)) {
        try {
            $fileConfig = require $configFile;
            if (is_array($fileConfig)) {
                foreach ($fileConfig as $k => $v) {
                    if ($v !== null && $v !== '') {
                        $defaults[$k] = $v;
                    }
                }
            }
        } catch (Throwable $fe) {}
    }

    // 2. Optional Environment variables (.env) support
    if (function_exists('app_env')) {
        $envProvider = app_env('WHATSAPP_PROVIDER');
        if (!empty($envProvider)) $defaults['provider'] = $envProvider;
        $envPhoneId = app_env('WHATSAPP_PHONE_NUMBER_ID');
        if (!empty($envPhoneId)) $defaults['phone_number_id'] = $envPhoneId;
        $envToken = app_env('WHATSAPP_API_TOKEN');
        if (!empty($envToken)) $defaults['api_token'] = $envToken;
        $envWaba = app_env('WHATSAPP_BUSINESS_ACCOUNT_ID');
        if (!empty($envWaba)) $defaults['business_account_id'] = $envWaba;
        $envApiUrl = app_env('WHATSAPP_API_URL');
        if (!empty($envApiUrl)) $defaults['api_url'] = $envApiUrl;
    }

    // 3. Optional fallback to tbl_payment_settings for UPI details
    try {
        $db = new Database();
        $conn = $db->getConnection();
        $resPay = $conn->query("SELECT upi_id, payee_name FROM tbl_payment_settings WHERE id = 1 LIMIT 1");
        if ($resPay && $rowPay = $resPay->fetch_assoc()) {
            if (!empty($rowPay['upi_id']) && empty($defaults['upi_id'])) {
                $defaults['upi_id'] = $rowPay['upi_id'];
            }
            if (!empty($rowPay['payee_name']) && empty($defaults['payee_name'])) {
                $defaults['payee_name'] = $rowPay['payee_name'];
            }
        }
    } catch (Throwable $e) {
        // Silently continue with defaults
    }

    return $defaults;
}

/**
 * Format phone number to international format (default 91 for 10-digit Indian numbers)
 */
function formatWhatsAppPhone(?string $phone): string {
    if (empty($phone)) {
        return '';
    }
    // Remove all non-digit characters
    $clean = preg_replace('/[^0-9]/', '', $phone);
    if (empty($clean)) {
        return '';
    }

    // Strip leading zeroes
    $clean = ltrim($clean, '0');

    // If 10 digits, assume standard Indian mobile and prefix with 91
    if (strlen($clean) === 10) {
        $clean = '91' . $clean;
    }

    return $clean;
}

/**
 * Replace placeholders in template text
 */
function replaceWhatsAppPlaceholders(string $template, array $data): string {
    $search = [];
    $replace = [];

    foreach ($data as $key => $val) {
        $search[] = '{' . $key . '}';
        $replace[] = (string)$val;
    }

    return str_replace($search, $replace, $template);
}

/**
 * Calculate outstanding balance for a given member
 */
function getMemberOutstandingBalance(int $member_id): float {
    try {
        $db = new Database();
        $conn = $db->getConnection();
        $stmt = $conn->prepare("
            SELECT 
                (SELECT COALESCE(SUM(fees), 0) FROM tbl_member_recievable WHERE member_id = ? AND (cancel = 0 OR cancel IS NULL)) AS expected,
                (SELECT COALESCE(SUM(fees), 0) FROM tbl_member_recieved WHERE member_id = ? AND (cancel = 0 OR cancel IS NULL)) AS received
        ");
        if ($stmt) {
            $stmt->bind_param("ii", $member_id, $member_id);
            $stmt->execute();
            $res = $stmt->get_result();
            if ($res && $row = $res->fetch_assoc()) {
                $exp = (float)$row['expected'];
                $rec = (float)$row['received'];
                return max(0.0, round($exp - $rec, 2));
            }
        }
    } catch (Throwable $e) {
        app_log_error("Failed to calculate member balance: " . $e->getMessage(), 'whatsapp');
    }
    return 0.0;
}

/**
 * Fetch member details including phone and full name
 */
function getMemberWhatsAppInfo(int $member_id): ?array {
    try {
        $db = new Database();
        $conn = $db->getConnection();
        $stmt = $conn->prepare("SELECT id, first_name, middle_name, last_name, phone, whtsapp FROM tbl_members WHERE id = ? LIMIT 1");
        if ($stmt) {
            $stmt->bind_param("i", $member_id);
            $stmt->execute();
            $res = $stmt->get_result();
            if ($res && $row = $res->fetch_assoc()) {
                $parts = array_filter([$row['first_name'], $row['middle_name'], $row['last_name']]);
                $row['full_name'] = implode(' ', $parts);
                $preferredPhone = !empty($row['whtsapp']) ? $row['whtsapp'] : $row['phone'];
                $row['clean_phone'] = formatWhatsAppPhone($preferredPhone);
                return $row;
            }
        }
    } catch (Throwable $e) {
        app_log_error("Failed to fetch member for WhatsApp: " . $e->getMessage(), 'whatsapp');
    }
    return null;
}

/**
 * Upload Media to Meta Cloud API to obtain a media ID
 */
function uploadWhatsAppMediaMeta(string $phoneNumberId, string $apiToken, string $filePath, string $filename): ?string {
    if (!file_exists($filePath) || empty($phoneNumberId) || empty($apiToken)) {
        return null;
    }
    $url = "https://graph.facebook.com/v19.0/{$phoneNumberId}/media";
    $cfile = new CURLFile($filePath, 'application/pdf', $filename);
    $postFields = [
        'file'              => $cfile,
        'type'              => 'application/pdf',
        'messaging_product' => 'whatsapp'
    ];

    $ch = curl_init();
    curl_setopt_array($ch, [
        CURLOPT_URL            => $url,
        CURLOPT_POST           => true,
        CURLOPT_POSTFIELDS     => $postFields,
        CURLOPT_HTTPHEADER     => [
            'Authorization: Bearer ' . $apiToken
        ],
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_SSL_VERIFYPEER => false,
        CURLOPT_SSL_VERIFYHOST => false,
        CURLOPT_TIMEOUT        => 12
    ]);

    $resp = curl_exec($ch);
    $code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);

    if ($code >= 200 && $code < 300 && $resp) {
        $json = json_decode($resp, true);
        if (!empty($json['id'])) {
            return (string)$json['id'];
        }
    }
    return null;
}

if (!function_exists('numberToIndianWords')) {
    function numberToIndianWords(float $number): string {
        $no = floor($number);
        $decimal = (int)round(($number - $no) * 100);
        
        $words = [
            0 => '', 1 => 'One', 2 => 'Two', 3 => 'Three', 4 => 'Four', 5 => 'Five',
            6 => 'Six', 7 => 'Seven', 8 => 'Eight', 9 => 'Nine', 10 => 'Ten',
            11 => 'Eleven', 12 => 'Twelve', 13 => 'Thirteen', 14 => 'Fourteen', 15 => 'Fifteen',
            16 => 'Sixteen', 17 => 'Seventeen', 18 => 'Eighteen', 19 => 'Nineteen', 20 => 'Twenty',
            30 => 'Thirty', 40 => 'Forty', 50 => 'Fifty', 60 => 'Sixty', 70 => 'Seventy',
            80 => 'Eighty', 90 => 'Ninety'
        ];
        
        if ($no == 0) {
            $rupeesStr = "Zero";
        } else {
            $digits = ['', 'Hundred', 'Thousand', 'Lakh', 'Crore'];
            $i = 0;
            $str = [];
            $digits_length = strlen($no);
            while ($i < $digits_length) {
                $divider = ($i == 2) ? 10 : 100;
                $number_part = floor($no % $divider);
                $no = floor($no / $divider);
                $i += ($divider == 10) ? 1 : 2;
                if ($number_part) {
                    $counter = count($str);
                    $unit = isset($digits[$counter]) ? ' ' . $digits[$counter] : '';
                    $partWords = ($number_part < 21)
                        ? $words[$number_part] . $unit
                        : $words[floor($number_part / 10) * 10] . ' ' . $words[$number_part % 10] . $unit;
                    $str[] = $partWords;
                } else {
                    $str[] = '';
                }
            }
            $rupeesStr = trim(implode(' ', array_filter(array_reverse($str))));
        }
        
        $paiseStr = '';
        if ($decimal > 0) {
            $pWords = ($decimal < 21) 
                ? $words[$decimal] 
                : $words[floor($decimal / 10) * 10] . ' ' . $words[$decimal % 10];
            $paiseStr = " and " . trim($pWords) . " Paise";
        }
        
        return "Rupees " . preg_replace('/\s+/', ' ', $rupeesStr . $paiseStr) . " Only";
    }
}

/**
 * Generate official PDF receipt on disk and return file info
 */
function generateAndSaveReceiptPdf(int $member_id, float $amount, string $receipt_no, string $date, string $payment_mode, float $balance): array {
    require_once __DIR__ . '/../invoice/tcpdf/tcpdf.php';
    require_once __DIR__ . '/../config/app.php';

    $member = getMemberWhatsAppInfo($member_id);
    $fullName = $member['full_name'] ?? ('Member #' . $member_id);
    $phone = $member['phone'] ?? '';
    $settings = getWhatsAppSettings();
    $clubName = defined('CLUB_NAME') ? CLUB_NAME : ($settings['club_name'] ?? 'YMCA BCP Poovathussery');
    $member_prefix = defined('CLUB_MEMBER_PREFIX') ? CLUB_MEMBER_PREFIX : 'YMCA-';
    $memberCode = $member_prefix . str_pad($member_id, 3, '0', STR_PAD_LEFT);

    // Group name
    $groupName = 'General Member';
    try {
        $db = new Database();
        $conn = $db->getConnection();
        $stmtG = $conn->prepare("SELECT g.name FROM tbl_group_member_map gm JOIN tbl_groups g ON gm.group_id = g.id WHERE gm.member_id = ? LIMIT 1");
        if ($stmtG) {
            $stmtG->bind_param("i", $member_id);
            $stmtG->execute();
            $resG = $stmtG->get_result();
            if ($resG && $rowG = $resG->fetch_assoc()) {
                $groupName = $rowG['name'] . ' Group';
            }
        }
    } catch (Throwable $e) {}

    $uploadDir = __DIR__ . '/../uploads/receipts/';
    if (!is_dir($uploadDir)) {
        @mkdir($uploadDir, 0755, true);
    }

    $cleanRecNo = preg_replace('/[^A-Za-z0-9_-]/', '', (string)$receipt_no);
    if (empty($cleanRecNo)) {
        $cleanRecNo = 'REC-' . $member_id . '-' . date('YmdHis');
    }
    $filename = 'Receipt_' . $cleanRecNo . '.pdf';
    $filePath = $uploadDir . $filename;

    try {
        $pdf = new TCPDF('P', 'mm', 'A4', true, 'UTF-8', false);
        $pdf->SetCreator('YMCA ClubPulse ERP');
        $pdf->SetAuthor($clubName);
        $pdf->SetTitle('Fee Receipt - ' . $cleanRecNo);
        $pdf->setPrintHeader(false);
        $pdf->setPrintFooter(false);
        $pdf->SetMargins(14, 14, 14);
        $pdf->SetAutoPageBreak(true, 14);
        $pdf->AddPage();
        $pdf->SetFont('helvetica', '', 9);

        $amountWords = numberToIndianWords($amount);
        $balanceColor = ($balance <= 0) ? '#059669' : '#dc2626';
        $balanceText = ($balance <= 0) ? 'Rs. 0.00 (All Dues Cleared)' : ('Rs. ' . number_format($balance, 2) . ' Pending');

        $html = '
        <style>
            .rec-container { border: 1.5px solid #0f172a; padding: 12px; }
            .header-tbl { width: 100%; border-bottom: 2px solid #1e3a8a; padding-bottom: 8px; margin-bottom: 12px; }
            .club-name { font-size: 18px; font-weight: bold; color: #1e3a8a; text-transform: uppercase; }
            .club-subtitle { font-size: 9px; color: #475569; }
            .badge-receipt { font-size: 10px; font-weight: bold; color: #047857; text-align: right; }
            .meta-tbl { width: 100%; margin-top: 10px; margin-bottom: 12px; font-size: 9.5px; }
            .meta-lbl { color: #64748b; font-weight: bold; width: 22%; }
            .meta-val { color: #0f172a; font-weight: bold; width: 28%; }
            .hero-tbl { width: 100%; background-color: #ecfdf5; border: 1px solid #10b981; margin-top: 8px; margin-bottom: 12px; }
            .hero-lbl { font-size: 8.5px; color: #065f46; font-weight: bold; text-transform: uppercase; padding: 6px 10px 0 10px; }
            .hero-amt { font-size: 20px; color: #047857; font-weight: bold; padding: 2px 10px 4px 10px; }
            .hero-words { font-size: 8.5px; color: #0f766e; font-style: italic; padding: 0 10px 6px 10px; }
            .items-tbl { width: 100%; border-collapse: collapse; margin-top: 6px; margin-bottom: 12px; font-size: 9.5px; }
            .items-th { background-color: #1e3a8a; color: #ffffff; font-weight: bold; padding: 6px 8px; }
            .items-td { padding: 8px; border-bottom: 1px solid #e2e8f0; }
            .summary-tbl { width: 100%; border-collapse: collapse; margin-top: 4px; font-size: 9.5px; }
            .foot-tbl { width: 100%; margin-top: 25px; border-top: 1px dashed #cbd5e1; padding-top: 10px; font-size: 8px; color: #64748b; }
        </style>

        <div class="rec-container">
            <table class="header-tbl" cellpadding="0" cellspacing="0">
                <tr>
                    <td width="70%">
                        <div class="club-name">' . htmlspecialchars(strtoupper($clubName)) . '</div>
                        <div class="club-subtitle">YMCA Badminton Club &bull; Poovathussery</div>
                        <div style="font-size: 8px; color: #64748b; margin-top: 2px;">Official Fee Payment Acknowledgment &amp; Member Receipt</div>
                    </td>
                    <td width="30%" align="right">
                        <div class="badge-receipt">[ PAID RECEIPT ]</div>
                        <div style="font-size: 11px; font-weight: bold; color: #1e3a8a;">#' . htmlspecialchars($cleanRecNo) . '</div>
                        <div style="font-size: 8.5px; color: #475569;">Date: ' . htmlspecialchars($date) . '</div>
                    </td>
                </tr>
            </table>

            <table class="meta-tbl" cellpadding="3" cellspacing="0">
                <tr>
                    <td class="meta-lbl">Member Name:</td>
                    <td class="meta-val">' . htmlspecialchars($fullName) . '</td>
                    <td class="meta-lbl">Receipt Number:</td>
                    <td class="meta-val">#' . htmlspecialchars($cleanRecNo) . '</td>
                </tr>
                <tr>
                    <td class="meta-lbl">Member Code:</td>
                    <td class="meta-val">' . htmlspecialchars($memberCode) . '</td>
                    <td class="meta-lbl">Payment Date:</td>
                    <td class="meta-val">' . htmlspecialchars($date) . '</td>
                </tr>
                <tr>
                    <td class="meta-lbl">Club Group:</td>
                    <td class="meta-val">' . htmlspecialchars($groupName) . '</td>
                    <td class="meta-lbl">Payment Mode:</td>
                    <td class="meta-val">' . htmlspecialchars($payment_mode) . '</td>
                </tr>
                <tr>
                    <td class="meta-lbl">Phone / WhatsApp:</td>
                    <td class="meta-val">' . htmlspecialchars($phone ?: 'N/A') . '</td>
                    <td class="meta-lbl">Payment Status:</td>
                    <td class="meta-val" style="color:#047857;">VERIFIED &amp; SETTLED</td>
                </tr>
            </table>

            <table class="hero-tbl" cellpadding="0" cellspacing="0">
                <tr>
                    <td>
                        <div class="hero-lbl">Total Amount Received</div>
                        <div class="hero-amt">Rs. ' . number_format($amount, 2) . '</div>
                        <div class="hero-words">In Words: ' . htmlspecialchars($amountWords) . '</div>
                    </td>
                </tr>
            </table>

            <table class="items-tbl" cellpadding="4" cellspacing="0">
                <thead>
                    <tr>
                        <th class="items-th" width="10%">Sl.</th>
                        <th class="items-th" width="65%">Particulars / Description</th>
                        <th class="items-th" width="25%" align="right">Amount (Rs.)</th>
                    </tr>
                </thead>
                <tbody>
                    <tr>
                        <td class="items-td" width="10%">01</td>
                        <td class="items-td" width="65%"><strong>Monthly Membership Subscription Contribution</strong><br><span style="font-size:8px; color:#64748b;">Member Contribution &bull; ' . htmlspecialchars($groupName) . '</span></td>
                        <td class="items-td" width="25%" align="right"><strong>' . number_format($amount, 2) . '</strong></td>
                    </tr>
                </tbody>
            </table>

            <table class="summary-tbl" cellpadding="3" cellspacing="0">
                <tr>
                    <td width="65%" align="right" style="font-weight:bold; color:#1e293b;">NET AMOUNT SETTLED:</td>
                    <td width="35%" align="right" style="font-weight:bold; color:#047857; font-size:11px;">Rs. ' . number_format($amount, 2) . '</td>
                </tr>
                <tr>
                    <td width="65%" align="right" style="font-weight:bold; color:#475569;">CURRENT BALANCE OUTSTANDING:</td>
                    <td width="35%" align="right" style="font-weight:bold; color:' . $balanceColor . '; font-size:10px;">' . $balanceText . '</td>
                </tr>
            </table>

            <table class="foot-tbl" cellpadding="0" cellspacing="0">
                <tr>
                    <td width="60%">
                        <div style="font-weight:bold; color:#047857; font-size:9px;">[ OFFICIAL VERIFIED RECEIPT ]</div>
                        This is a computer-generated official payment receipt issued via YMCA Management System.<br>
                        Generated on ' . date('d-m-Y H:i:s') . ' &bull; Valid for all club and financial records.
                    </td>
                    <td width="40%" align="right">
                        <br><br>
                        ___________________________<br>
                        <strong>Authorized Signatory</strong><br>
                        ' . htmlspecialchars($clubName) . '
                    </td>
                </tr>
            </table>
        </div>
        ';

        $pdf->writeHTML($html, true, false, true, false, '');
        $pdf->Output($filePath, 'F');
    } catch (Throwable $e) {
        app_log_error("Failed to generate PDF on disk: " . $e->getMessage(), 'whatsapp');
    }

    $baseUrl = getAppBaseUrl();
    $fileUrl = $baseUrl . '/uploads/receipts/' . $filename;

    return [
        'file_path' => $filePath,
        'filename'  => $filename,
        'file_url'  => $fileUrl,
        'file_size' => file_exists($filePath) ? filesize($filePath) : 0
    ];
}

/**
 * Dispatch WhatsApp message through the configured API Gateway
 */
function dispatchWhatsAppMessage(string $rawPhone, string $message, string $messageType = 'general', int $memberId = 0, ?array $document = null, ?array $templateData = null): array {
    $settings = getWhatsAppSettings();
    $cleanPhone = formatWhatsAppPhone($rawPhone);

    if (empty($cleanPhone)) {
        return [
            'success' => false,
            'status'  => 'invalid_phone',
            'message' => 'Invalid or missing phone number.',
            'wa_url'  => ''
        ];
    }

    $waUrl = 'https://wa.me/' . $cleanPhone . '?text=' . rawurlencode($message);

    // If disabled or direct WhatsApp Web provider selected
    if (empty($settings['is_active']) || $settings['provider'] === 'wame') {
        logWhatsAppRecord($memberId, $cleanPhone, $messageType, $message, 'direct', 'Direct WhatsApp Web / App mode');
        return [
            'success' => true,
            'status'  => 'direct',
            'message' => 'Direct WhatsApp link generated.',
            'wa_url'  => $waUrl
        ];
    }

    // Determine API Endpoint and Dispatch
    $provider = $settings['provider'] ?? 'meta';
    $apiToken = trim($settings['api_token'] ?? '');
    $apiUrl   = trim($settings['api_url'] ?? '');

    // If no API token configured, fallback to wa.me gracefully
    if (empty($apiToken) && empty($apiUrl)) {
        logWhatsAppRecord($memberId, $cleanPhone, $messageType, $message, 'pending_config', 'No API credentials configured. Direct WhatsApp link provided.');
        return [
            'success' => true,
            'status'  => 'direct',
            'message' => 'WhatsApp API credentials not configured yet. WhatsApp Web / App link ready.',
            'wa_url'  => $waUrl
        ];
    }

    $sendAsDocument = !empty($document) && !empty($document['file_path']) && !empty($settings['send_pdf_receipt']);

    $ch = curl_init();
    $headers = [];
    $postPayload = null;

    if ($provider === 'meta') {
        $phoneId = trim($settings['phone_number_id'] ?? '');
        $endpoint = !empty($apiUrl) ? $apiUrl : "https://graph.facebook.com/v19.0/{$phoneId}/messages";
        
        $headers = [
            'Authorization: Bearer ' . $apiToken,
            'Content-Type: application/json'
        ];

        $metaTplName = trim($settings['meta_template_payment'] ?? '');
        $metaTplLang = trim($settings['meta_template_lang'] ?? 'en');

        // Check if official approved Meta Template should be dispatched
        if (!empty($metaTplName) && $messageType === 'payment_receipt' && !empty($templateData)) {
            $mediaId = $sendAsDocument ? uploadWhatsAppMediaMeta($phoneId, $apiToken, $document['file_path'], $document['filename']) : null;
            $components = [];

            if ($sendAsDocument) {
                $docParam = null;
                if ($mediaId) {
                    $docParam = ['type' => 'document', 'document' => ['id' => $mediaId, 'filename' => $document['filename']]];
                } else if (!empty($document['file_url'])) {
                    $docParam = ['type' => 'document', 'document' => ['link' => $document['file_url'], 'filename' => $document['filename']]];
                }
                if ($docParam) {
                    $components[] = [
                        'type' => 'header',
                        'parameters' => [$docParam]
                    ];
                }
            }

            $components[] = [
                'type' => 'body',
                'parameters' => [
                    ['type' => 'text', 'text' => (string)($templateData['member_name'] ?? 'Member')],
                    ['type' => 'text', 'text' => (string)($templateData['club_name'] ?? 'YMCA Badminton Club')],
                    ['type' => 'text', 'text' => (string)($templateData['receipt_no'] ?? 'REC-1')],
                    ['type' => 'text', 'text' => (string)($templateData['amount'] ?? '0.00')],
                    ['type' => 'text', 'text' => (string)($templateData['date'] ?? date('d-m-Y'))],
                    ['type' => 'text', 'text' => (string)($templateData['payment_mode'] ?? 'Cash')],
                    ['type' => 'text', 'text' => (string)($templateData['balance'] ?? '0.00')]
                ]
            ];

            $postPayload = json_encode([
                'messaging_product' => 'whatsapp',
                'recipient_type'    => 'individual',
                'to'                => $cleanPhone,
                'type'              => 'template',
                'template'          => [
                    'name'       => $metaTplName,
                    'language'   => ['code' => $metaTplLang],
                    'components' => $components
                ]
            ]);
        } else if (!empty($settings['meta_template_balance']) && $messageType === 'balance_alert' && !empty($templateData)) {
            $metaBalTpl = trim($settings['meta_template_balance']);
            $postPayload = json_encode([
                'messaging_product' => 'whatsapp',
                'recipient_type'    => 'individual',
                'to'                => $cleanPhone,
                'type'              => 'template',
                'template'          => [
                    'name'       => $metaBalTpl,
                    'language'   => ['code' => $metaTplLang],
                    'components' => [
                        [
                            'type' => 'body',
                            'parameters' => [
                                ['type' => 'text', 'text' => (string)($templateData['club_name'] ?? 'YMCA BCP Poovathussery')],
                                ['type' => 'text', 'text' => (string)($templateData['member_name'] ?? 'Member')],
                                ['type' => 'text', 'text' => (string)($templateData['balance'] ?? '0.00')],
                                ['type' => 'text', 'text' => (string)($templateData['upi_id'] ?? '')],
                                ['type' => 'text', 'text' => (string)($templateData['payee_name'] ?? '')]
                            ]
                        ]
                    ]
                ]
            ]);
        } else if ($sendAsDocument) {
            $caption = (mb_strlen($message) > 1024) ? (mb_substr($message, 0, 1020) . '...') : $message;
            // 1. Try uploading to Meta media endpoint for reliable sending even from localhost
            $mediaId = uploadWhatsAppMediaMeta($phoneId, $apiToken, $document['file_path'], $document['filename']);
            if ($mediaId) {
                $postPayload = json_encode([
                    'messaging_product' => 'whatsapp',
                    'recipient_type'    => 'individual',
                    'to'                => $cleanPhone,
                    'type'              => 'document',
                    'document'          => [
                        'id'       => $mediaId,
                        'caption'  => $caption,
                        'filename' => $document['filename']
                    ]
                ]);
            } else if (!empty($document['file_url'])) {
                // 2. Direct public link
                $postPayload = json_encode([
                    'messaging_product' => 'whatsapp',
                    'recipient_type'    => 'individual',
                    'to'                => $cleanPhone,
                    'type'              => 'document',
                    'document'          => [
                        'link'     => $document['file_url'],
                        'caption'  => $caption,
                        'filename' => $document['filename']
                    ]
                ]);
            }
        }

        if (!$postPayload) {
            $postPayload = json_encode([
                'messaging_product' => 'whatsapp',
                'recipient_type'    => 'individual',
                'to'                => $cleanPhone,
                'type'              => 'text',
                'text'              => [
                    'preview_url' => false,
                    'body'        => $message
                ]
            ]);
        }
    } else {
        // Generic REST Gateway / UltraMsg
        $endpoint = $apiUrl;
        if (strpos($endpoint, 'ultramsg.com') !== false) {
            $headers = ['Content-Type: application/x-www-form-urlencoded'];
            if ($sendAsDocument && (!empty($document['file_url']) || !empty($document['file_path']))) {
                $docEndpoint = str_replace('/messages/chat', '/messages/document', $endpoint);
                if (strpos($docEndpoint, '/messages/document') === false) {
                    $docEndpoint = rtrim($docEndpoint, '/') . '/messages/document';
                }
                $endpoint = $docEndpoint;

                $docPayload = $document['file_url'] ?? '';
                if (!empty($document['file_path']) && file_exists($document['file_path'])) {
                    $isLocal = empty($docPayload) || (strpos($docPayload, 'localhost') !== false) || (strpos($docPayload, '127.0.0.1') !== false);
                    if ($isLocal) {
                        $docPayload = 'data:application/pdf;base64,' . base64_encode(file_get_contents($document['file_path']));
                    }
                }

                $postPayload = http_build_query([
                    'token'    => $apiToken,
                    'to'       => $cleanPhone,
                    'filename' => $document['filename'],
                    'document' => $docPayload,
                    'caption'  => $message
                ]);
            } else {
                $postPayload = http_build_query([
                    'token' => $apiToken,
                    'to'    => $cleanPhone,
                    'body'  => $message
                ]);
            }
        } else {
            $headers = ['Content-Type: application/json'];
            if (!empty($apiToken)) {
                $headers[] = 'Authorization: Bearer ' . $apiToken;
            }
            if ($sendAsDocument && !empty($document['file_url'])) {
                $postPayload = json_encode([
                    'token'    => $apiToken,
                    'to'       => $cleanPhone,
                    'type'     => 'document',
                    'document' => $document['file_url'],
                    'filename' => $document['filename'],
                    'caption'  => $message,
                    'message'  => $message
                ]);
            } else {
                $postPayload = json_encode([
                    'token'   => $apiToken,
                    'to'      => $cleanPhone,
                    'phone'   => $cleanPhone,
                    'message' => $message,
                    'body'    => $message
                ]);
            }
        }
    }

    curl_setopt_array($ch, [
        CURLOPT_URL            => $endpoint,
        CURLOPT_POST           => true,
        CURLOPT_POSTFIELDS     => $postPayload,
        CURLOPT_HTTPHEADER     => $headers,
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_SSL_VERIFYPEER => false,
        CURLOPT_SSL_VERIFYHOST => false,
        CURLOPT_TIMEOUT        => 10
    ]);

    $rawResponse = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $curlErr = curl_error($ch);
    curl_close($ch);

    $isSuccess = ($httpCode >= 200 && $httpCode < 300);
    $status = $isSuccess ? 'sent' : 'failed';
    $logResponse = $rawResponse ? $rawResponse : ("CURL Error: " . $curlErr . " (HTTP " . $httpCode . ")");

    logWhatsAppRecord($memberId, $cleanPhone, $messageType, $message, $status, $logResponse);

    if ($isSuccess) {
        return [
            'success' => true,
            'status'  => 'sent',
            'message' => 'WhatsApp message delivered successfully via API.',
            'wa_url'  => $waUrl,
            'api_response' => $rawResponse
        ];
    } else {
        return [
            'success' => false,
            'status'  => 'failed',
            'message' => 'Failed to send via WhatsApp API: ' . ($curlErr ?: ('HTTP ' . $httpCode . ' - ' . substr($rawResponse, 0, 120))),
            'wa_url'  => $waUrl,
            'api_response' => $rawResponse
        ];
    }
}

/**
 * Log message transaction into tbl_whatsapp_logs
 */
function logWhatsAppRecord(int $memberId, string $phone, string $type, string $body, string $status, ?string $apiResp = null): void {
    try {
        $db = new Database();
        $conn = $db->getConnection();
        $stmt = $conn->prepare("INSERT INTO tbl_whatsapp_logs (member_id, phone, message_type, message_body, status, api_response) VALUES (?, ?, ?, ?, ?, ?)");
        if ($stmt) {
            $stmt->bind_param("isssss", $memberId, $phone, $type, $body, $status, $apiResp);
            $stmt->execute();
        }
    } catch (Throwable $e) {
        // Silently capture logging issues
    }
}

/**
 * Send Payment Confirmation Receipt via WhatsApp
 */
function sendPaymentReceiptWhatsApp(int $member_id, float $amount, $receipt_no, ?string $date = null, string $payment_mode = 'Cash', ?float $balance = null): array {
    $settings = getWhatsAppSettings();
    $member = getMemberWhatsAppInfo($member_id);

    if (!$member || empty($member['clean_phone'])) {
        return [
            'success' => false,
            'status'  => 'no_phone',
            'message' => 'Member has no phone or WhatsApp number registered.',
            'wa_url'  => ''
        ];
    }

    if ($date === null) {
        $date = date('d-m-Y');
    } else {
        $time = strtotime($date);
        $date = $time ? date('d-m-Y', $time) : $date;
    }

    if ($balance === null) {
        $balance = getMemberOutstandingBalance($member_id);
    }

    $baseUrl = getAppBaseUrl();
    $cleanRecNo = (string)$receipt_no;
    $receiptUrl = $baseUrl . '/invoice/receipt.php?member_id=' . $member_id . '&amount=' . urlencode(number_format($amount, 2, '.', '')) . '&receipt_no=' . urlencode($cleanRecNo) . '&date=' . urlencode($date) . '&mode=' . urlencode($payment_mode) . '&balance=' . urlencode(number_format($balance, 2, '.', ''));
    $receiptPdfUrl = $receiptUrl . '&format=pdf';

    $data = [
        'club_name'       => $settings['club_name'] ?? 'YMCA Badminton Club',
        'member_name'     => $member['full_name'],
        'phone'           => $member['phone'],
        'amount'          => number_format($amount, 2),
        'receipt_no'      => $cleanRecNo,
        'date'            => $date,
        'payment_mode'    => $payment_mode,
        'balance'         => number_format($balance, 2),
        'upi_id'          => $settings['upi_id'] ?? '',
        'payee_name'      => $settings['payee_name'] ?? '',
        'receipt_url'     => $receiptUrl,
        'receipt_pdf_url' => $receiptPdfUrl
    ];

    $template = $settings['template_payment'];
    $message = replaceWhatsAppPlaceholders($template, $data);
    $waUrl = 'https://wa.me/' . $member['clean_phone'] . '?text=' . rawurlencode($message);

    // Generate physical PDF receipt for WhatsApp document attachment and archiving
    $pdfDoc = generateAndSaveReceiptPdf($member_id, $amount, $cleanRecNo, $date, $payment_mode, $balance);

    // If auto send is disabled, provide direct link only
    if (empty($settings['auto_send_payment'])) {
        return [
            'success'      => true,
            'status'       => 'manual_mode',
            'message'      => 'Payment receipt message formatted. Ready for WhatsApp.',
            'wa_url'       => $waUrl,
            'message_text' => $message,
            'phone'        => $member['clean_phone'],
            'pdf_url'      => $pdfDoc ? $pdfDoc['file_url'] : $receiptPdfUrl,
            'pdf_path'     => $pdfDoc ? $pdfDoc['file_path'] : null
        ];
    }

    $result = dispatchWhatsAppMessage($member['clean_phone'], $message, 'payment_receipt', $member_id, $pdfDoc, $data);
    $result['message_text'] = $message;
    $result['phone']        = $member['clean_phone'];
    if ($pdfDoc) {
        $result['pdf_url']  = $pdfDoc['file_url'];
        $result['pdf_path'] = $pdfDoc['file_path'];
    }
    return $result;
}

/**
 * Send Pending Balance Reminder via WhatsApp
 */
function sendPendingBalanceAlertWhatsApp(int $member_id, ?float $balance = null): array {
    $settings = getWhatsAppSettings();
    $member = getMemberWhatsAppInfo($member_id);

    if (!$member || empty($member['clean_phone'])) {
        return [
            'success' => false,
            'status'  => 'no_phone',
            'message' => 'Member has no phone or WhatsApp number registered.',
            'wa_url'  => ''
        ];
    }

    if ($balance === null) {
        $balance = getMemberOutstandingBalance($member_id);
    }

    $data = [
        'club_name'    => $settings['club_name'] ?? 'YMCA Badminton Club',
        'member_name'  => $member['full_name'],
        'phone'        => $member['phone'],
        'balance'      => number_format($balance, 2),
        'date'         => date('d-m-Y'),
        'due_date'     => date('t-m-Y'), // End of month
        'upi_id'       => $settings['upi_id'] ?? '',
        'payee_name'   => $settings['payee_name'] ?? ''
    ];

    $message = replaceWhatsAppPlaceholders($settings['template_balance'], $data);
    $result = dispatchWhatsAppMessage($member['clean_phone'], $message, 'balance_alert', $member_id, null, $data);
    $result['message_text'] = $message;
    $result['phone'] = $member['clean_phone'];
    $result['member_name'] = $member['full_name'];
    $result['balance'] = $balance;

    return $result;
}
