<?php
/**
 * Official YMCA Payment Receipt & PDF Generator
 * Enterprise-grade Responsive Web View & TCPDF Engine
 * YMCA Badminton Club Management ERP
 */

require_once __DIR__ . '/../includes/api_auth.php';
start_secure_session();
require_login();

require_once __DIR__ . '/../config/app.php';
require_once __DIR__ . '/../app_common/db_connect.php';
require_once __DIR__ . '/tcpdf/tcpdf.php';
require_once __DIR__ . '/../includes/whatsapp.php';

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

$member_id   = isset($_GET['member_id']) ? (int)$_GET['member_id'] : (isset($_POST['member_id']) ? (int)$_POST['member_id'] : 0);
$rec_id      = isset($_GET['id']) ? (int)$_GET['id'] : (isset($_POST['id']) ? (int)$_POST['id'] : 0);
$receipt_no  = isset($_GET['receipt_no']) ? trim($_GET['receipt_no']) : (isset($_POST['receipt_no']) ? trim($_POST['receipt_no']) : '');
$amount      = isset($_GET['amount']) ? (float)$_GET['amount'] : (isset($_POST['amount']) ? (float)$_POST['amount'] : 0.0);
$date        = isset($_GET['date']) ? trim($_GET['date']) : (isset($_POST['date']) ? trim($_POST['date']) : date('d-m-Y'));
$mode        = isset($_GET['mode']) ? trim($_GET['mode']) : (isset($_POST['mode']) ? trim($_POST['mode']) : 'Cash');
$balance     = isset($_GET['balance']) ? (float)$_GET['balance'] : (isset($_POST['balance']) ? (float)$_POST['balance'] : null);
$format      = isset($_GET['format']) ? strtolower(trim($_GET['format'])) : 'view'; // 'view' or 'pdf'
$particulars = 'Monthly Membership Subscription Contribution';

// Fetch details from database if a specific receipt ID is provided
if ($rec_id > 0) {
    try {
        $db = new Database();
        $conn = $db->getConnection();
        $stmt = $conn->prepare("
            SELECT r.id, r.member_id, r.fees, r.date, r.discription, r.transaction_type, r.iswallet, r.receiveble_id,
                   rb.discription AS rec_discription
            FROM tbl_member_recieved r
            LEFT JOIN tbl_member_recievable rb ON r.receiveble_id = rb.id
            WHERE r.id = ? LIMIT 1
        ");
        if ($stmt) {
            $stmt->bind_param("i", $rec_id);
            $stmt->execute();
            $res = $stmt->get_result();
            if ($res && $row = $res->fetch_assoc()) {
                $member_id = (int)$row['member_id'];
                $amount = (float)$row['fees'];
                $date = date('d-m-Y', strtotime($row['date']));
                $mode = ($row['iswallet'] == 1) ? 'Member Prepaid Wallet' : (($row['transaction_type'] == 2) ? 'Bank / Online / UPI' : 'Cash');
                if (empty($receipt_no)) {
                    $receipt_no = 'REC-' . $row['id'];
                }
                if (!empty($row['discription'])) {
                    $particulars = $row['discription'];
                } elseif (!empty($row['rec_discription'])) {
                    $particulars = $row['rec_discription'];
                }
            }
        }
    } catch (Throwable $e) {}
}

if ($member_id <= 0) {
    http_response_code(400);
    exit("<div style='font-family:sans-serif; text-align:center; padding:50px;'><h2>Invalid Receipt Request</h2><p>Member ID or Receipt parameters missing.</p></div>");
}

// Fetch member details
$member = getMemberWhatsAppInfo($member_id);
if (!$member) {
    http_response_code(404);
    exit("<div style='font-family:sans-serif; text-align:center; padding:50px;'><h2>Member Not Found</h2><p>The specified member could not be located in the database.</p></div>");
}

$settings = getWhatsAppSettings();
$club_name = defined('CLUB_NAME') ? CLUB_NAME : ($settings['club_name'] ?? 'YMCA BCP Poovathussery');
$club_short = defined('CLUB_SHORT_NAME') ? CLUB_SHORT_NAME : 'YMCA BCP';
$member_prefix = defined('CLUB_MEMBER_PREFIX') ? CLUB_MEMBER_PREFIX : 'YMCA-';
$member_code = $member_prefix . str_pad($member_id, 3, '0', STR_PAD_LEFT);

// Fetch member group name
$group_name = 'General Member';
try {
    $db = new Database();
    $conn = $db->getConnection();
    $stmtG = $conn->prepare("SELECT g.name FROM tbl_group_member_map gm JOIN tbl_groups g ON gm.group_id = g.id WHERE gm.member_id = ? LIMIT 1");
    if ($stmtG) {
        $stmtG->bind_param("i", $member_id);
        $stmtG->execute();
        $resG = $stmtG->get_result();
        if ($resG && $rowG = $resG->fetch_assoc()) {
            $group_name = $rowG['name'] . ' Group';
        }
    }
} catch (Throwable $e) {}

if ($balance === null) {
    $balance = getMemberOutstandingBalance($member_id);
}

if (empty($receipt_no)) {
    $receipt_no = 'REC-' . $member_id . '-' . date('Ymd');
}

$full_name = $member['full_name'];
$phone = !empty($member['whtsapp']) ? $member['whtsapp'] : ($member['phone'] ?? '');
$amount_words = numberToIndianWords($amount);

// --------------------------------------------------------------------------
// 1. PDF Output via TCPDF (format=pdf)
// --------------------------------------------------------------------------
if ($format === 'pdf') {
    while (ob_get_level()) { ob_end_clean(); }

    $pdf = new TCPDF('P', 'mm', 'A4', true, 'UTF-8', false);
    $pdf->SetCreator('YMCA ClubPulse ERP');
    $pdf->SetAuthor($club_name);
    $pdf->SetTitle('Official Payment Receipt - ' . $receipt_no);
    $pdf->setPrintHeader(false);
    $pdf->setPrintFooter(false);
    $pdf->SetMargins(14, 14, 14);
    $pdf->SetAutoPageBreak(true, 14);
    $pdf->AddPage();
    $pdf->SetFont('helvetica', '', 9);

    $balanceColor = ($balance <= 0) ? '#059669' : '#dc2626';
    $balanceBg = ($balance <= 0) ? '#ecfdf5' : '#fef2f2';
    $balanceText = ($balance <= 0) ? '₹ 0.00 (All Dues Cleared)' : ('₹ ' . number_format($balance, 2) . ' Pending');

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
        .summary-td { padding: 6px 8px; }
        .foot-tbl { width: 100%; margin-top: 25px; border-top: 1px dashed #cbd5e1; padding-top: 10px; font-size: 8px; color: #64748b; }
    </style>

    <div class="rec-container">
        <table class="header-tbl" cellpadding="0" cellspacing="0">
            <tr>
                <td width="70%">
                    <div class="club-name">' . htmlspecialchars(strtoupper($club_name)) . '</div>
                    <div class="club-subtitle">YMCA Badminton Club &bull; Poovathussery</div>
                    <div style="font-size: 8px; color: #64748b; margin-top: 2px;">Official Fee Payment Acknowledgment &amp; Member Receipt</div>
                </td>
                <td width="30%" align="right">
                    <div class="badge-receipt">[ PAID RECEIPT ]</div>
                    <div style="font-size: 11px; font-weight: bold; color: #1e3a8a;">#' . htmlspecialchars($receipt_no) . '</div>
                    <div style="font-size: 8.5px; color: #475569;">Date: ' . htmlspecialchars($date) . '</div>
                </td>
            </tr>
        </table>

        <table class="meta-tbl" cellpadding="3" cellspacing="0">
            <tr>
                <td class="meta-lbl">Member Name:</td>
                <td class="meta-val">' . htmlspecialchars($full_name) . '</td>
                <td class="meta-lbl">Receipt Number:</td>
                <td class="meta-val">#' . htmlspecialchars($receipt_no) . '</td>
            </tr>
            <tr>
                <td class="meta-lbl">Member Code:</td>
                <td class="meta-val">' . htmlspecialchars($member_code) . '</td>
                <td class="meta-lbl">Payment Date:</td>
                <td class="meta-val">' . htmlspecialchars($date) . '</td>
            </tr>
            <tr>
                <td class="meta-lbl">Club Group:</td>
                <td class="meta-val">' . htmlspecialchars($group_name) . '</td>
                <td class="meta-lbl">Payment Mode:</td>
                <td class="meta-val">' . htmlspecialchars($mode) . '</td>
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
                    <div class="hero-words">In Words: ' . htmlspecialchars($amount_words) . '</div>
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
                    <td class="items-td" width="65%"><strong>' . htmlspecialchars($particulars) . '</strong><br><span style="font-size:8px; color:#64748b;">Member Contribution &bull; ' . htmlspecialchars($group_name) . '</span></td>
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
                    ' . htmlspecialchars($club_name) . '
                </td>
            </tr>
        </table>
    </div>
    ';

    $pdf->writeHTML($html, true, false, true, false, '');
    $filename = 'Receipt_' . preg_replace('/[^A-Za-z0-9_-]/', '', $receipt_no) . '.pdf';
    header('Content-Type: application/pdf');
    header('Content-Disposition: inline; filename="' . $filename . '"');
    $pdf->Output($filename, 'I');
    exit();
}

// --------------------------------------------------------------------------
// 2. Responsive Web / Mobile View with Print & PDF Export
// --------------------------------------------------------------------------
$baseUrl = function_exists('getAppBaseUrl') ? getAppBaseUrl() : 'http://localhost/ymca_test';
$pdfDownloadUrl = "receipt.php?member_id={$member_id}&amount=" . urlencode($amount) . "&receipt_no=" . urlencode($receipt_no) . "&date=" . urlencode($date) . "&mode=" . urlencode($mode) . "&balance=" . urlencode($balance) . "&format=pdf";
$cleanPhoneForWa = !empty($member['clean_phone']) ? $member['clean_phone'] : preg_replace('/[^0-9]/', '', $phone);
$waShareUrl = "https://wa.me/{$cleanPhoneForWa}?text=" . rawurlencode("🏸 *{$club_name}*\nOfficial Fee Payment Receipt\nReceipt No: #{$receipt_no}\nAmount: ₹" . number_format($amount, 2) . "\nView full receipt: " . $baseUrl . "/invoice/" . $pdfDownloadUrl);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Fee Payment Receipt #<?php echo htmlspecialchars($receipt_no); ?> &bull; <?php echo htmlspecialchars($club_name); ?></title>
    
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800;900&display=swap" rel="stylesheet">
    <link href="../font-awesome/css/font-awesome.css" rel="stylesheet">
    
    <style>
        :root {
            --primary: #1e3a8a;
            --primary-dark: #0f172a;
            --accent: #2563eb;
            --success: #059669;
            --success-light: #ecfdf5;
            --warning: #d97706;
            --danger: #dc2626;
            --danger-light: #fef2f2;
            --card-bg: #ffffff;
            --body-bg: #f8fafc;
            --text-main: #0f172a;
            --text-muted: #64748b;
            --border-color: #e2e8f0;
            --shadow: 0 10px 40px -10px rgba(15, 23, 42, 0.08), 0 0 1px 1px rgba(15, 23, 42, 0.05);
        }

        *, *::before, *::after { box-sizing: border-box; }

        body {
            font-family: 'Plus Jakarta Sans', -apple-system, BlinkMacSystemFont, sans-serif;
            background: linear-gradient(180deg, #f1f5f9 0%, #e2e8f0 100%);
            margin: 0;
            padding: 32px 16px;
            color: var(--text-main);
            min-height: 100vh;
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            -webkit-font-smoothing: antialiased;
        }

        .receipt-wrapper {
            width: 100%;
            max-width: 620px;
            margin: 0 auto;
        }

        .receipt-card {
            background: var(--card-bg);
            border-radius: 24px;
            box-shadow: var(--shadow);
            border: 1px solid rgba(226, 232, 240, 0.8);
            overflow: hidden;
            position: relative;
        }

        /* Top decorative gradient bar */
        .receipt-card::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            height: 6px;
            background: linear-gradient(90deg, #2563eb, #10b981, #f59e0b);
            z-index: 10;
        }

        /* Header block */
        .receipt-header {
            background: linear-gradient(135deg, #0f172a 0%, #1e3a8a 100%);
            color: #ffffff;
            padding: 32px 28px 24px;
            text-align: center;
            position: relative;
        }

        .club-emblem {
            width: 52px;
            height: 52px;
            background: rgba(255, 255, 255, 0.12);
            backdrop-filter: blur(8px);
            border: 1px solid rgba(255, 255, 255, 0.25);
            border-radius: 16px;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            font-size: 26px;
            margin-bottom: 12px;
            box-shadow: 0 4px 12px rgba(0,0,0,0.15);
        }

        .club-name {
            font-size: 20px;
            font-weight: 900;
            letter-spacing: -0.5px;
            margin: 0;
            text-transform: uppercase;
        }

        .club-sub {
            font-size: 12px;
            color: #94a3b8;
            margin: 4px 0 0;
            font-weight: 500;
        }

        .paid-badge {
            display: inline-flex;
            align-items: center;
            gap: 6px;
            background: rgba(16, 185, 129, 0.18);
            border: 1px solid rgba(16, 185, 129, 0.4);
            color: #6ee7b7;
            padding: 5px 14px;
            border-radius: 100px;
            font-size: 11px;
            font-weight: 800;
            letter-spacing: 0.5px;
            margin-top: 14px;
            text-transform: uppercase;
        }

        /* Body container */
        .receipt-body {
            padding: 28px;
        }

        /* Hero Amount block */
        .hero-amount-box {
            background: linear-gradient(135deg, #ecfdf5 0%, #f0fdf4 100%);
            border: 1.5px solid #a7f3d0;
            border-radius: 18px;
            padding: 20px;
            text-align: center;
            margin-bottom: 24px;
            position: relative;
        }

        .hero-amount-label {
            font-size: 11px;
            font-weight: 800;
            color: #065f46;
            text-transform: uppercase;
            letter-spacing: 0.8px;
        }

        .hero-amount-value {
            font-size: 36px;
            font-weight: 900;
            color: #047857;
            letter-spacing: -1px;
            margin: 4px 0 2px;
            line-height: 1.1;
        }

        .hero-amount-words {
            font-size: 12px;
            font-weight: 600;
            color: #0f766e;
            font-style: italic;
        }

        /* Two Column Meta Information */
        .meta-grid {
            display: grid;
            grid-template-columns: repeat(2, 1fr);
            gap: 14px 18px;
            background: #f8fafc;
            border: 1px solid var(--border-color);
            border-radius: 16px;
            padding: 18px 20px;
            margin-bottom: 24px;
        }

        .meta-item {
            display: flex;
            flex-direction: column;
        }

        .meta-label {
            font-size: 11px;
            font-weight: 700;
            color: var(--text-muted);
            text-transform: uppercase;
            letter-spacing: 0.5px;
            margin-bottom: 3px;
        }

        .meta-value {
            font-size: 14px;
            font-weight: 700;
            color: var(--text-main);
            word-break: break-word;
        }

        .meta-value.highlight {
            color: var(--primary);
        }

        /* Particulars Table */
        .table-responsive {
            margin-bottom: 24px;
            border: 1px solid var(--border-color);
            border-radius: 14px;
            overflow: hidden;
        }

        .receipt-table {
            width: 100%;
            border-collapse: collapse;
            font-size: 13px;
        }

        .receipt-table th {
            background: #0f172a;
            color: #ffffff;
            font-weight: 700;
            padding: 10px 14px;
            text-align: left;
            font-size: 11.5px;
            letter-spacing: 0.4px;
            text-transform: uppercase;
        }

        .receipt-table th:last-child {
            text-align: right;
        }

        .receipt-table td {
            padding: 12px 14px;
            border-top: 1px solid var(--border-color);
            color: var(--text-main);
        }

        .receipt-table td:last-child {
            text-align: right;
            font-weight: 700;
        }

        .receipt-table .subtext {
            font-size: 11px;
            color: var(--text-muted);
            margin-top: 2px;
        }

        /* Summary balance callout */
        .balance-callout {
            border-radius: 14px;
            padding: 14px 18px;
            display: flex;
            align-items: center;
            justify-content: space-between;
            margin-bottom: 24px;
            border: 1px solid;
        }

        .balance-callout.cleared {
            background: #ecfdf5;
            border-color: #a7f3d0;
            color: #065f46;
        }

        .balance-callout.pending {
            background: #fef2f2;
            border-color: #fecaca;
            color: #991b1b;
        }

        .balance-callout-label {
            font-size: 12.5px;
            font-weight: 700;
            display: flex;
            align-items: center;
            gap: 8px;
        }

        .balance-callout-val {
            font-size: 16px;
            font-weight: 900;
        }

        /* Signatory & Verification Block */
        .verification-row {
            display: flex;
            justify-content: space-between;
            align-items: flex-end;
            border-top: 1px dashed var(--border-color);
            padding-top: 18px;
            margin-bottom: 24px;
        }

        .verification-stamp {
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .stamp-badge {
            width: 44px;
            height: 44px;
            border-radius: 50%;
            border: 2px dashed #059669;
            color: #059669;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 18px;
            background: #ecfdf5;
        }

        .stamp-text {
            font-size: 10.5px;
            color: var(--text-muted);
            line-height: 1.4;
        }

        .signature-box {
            text-align: right;
        }

        .signature-line {
            width: 140px;
            height: 1px;
            background: #cbd5e1;
            margin-bottom: 6px;
            margin-left: auto;
        }

        .signature-title {
            font-size: 11px;
            font-weight: 800;
            color: var(--text-main);
            text-transform: uppercase;
        }

        .signature-club {
            font-size: 10px;
            color: var(--text-muted);
        }

        /* Action Buttons */
        .actions-bar {
            display: flex;
            gap: 10px;
            margin-top: 6px;
        }

        .btn-action {
            flex: 1;
            height: 44px;
            border-radius: 12px;
            font-size: 13.5px;
            font-weight: 700;
            border: none;
            cursor: pointer;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: 8px;
            text-decoration: none;
            transition: all 0.2s ease;
        }

        .btn-pdf {
            background: var(--primary);
            color: #ffffff;
            box-shadow: 0 4px 12px rgba(30, 58, 138, 0.25);
        }

        .btn-pdf:hover {
            background: #1e40af;
            color: #ffffff;
            transform: translateY(-1px);
        }

        .btn-print {
            background: #ffffff;
            color: #334155;
            border: 1px solid var(--border-color);
            box-shadow: 0 2px 6px rgba(0,0,0,0.04);
        }

        .btn-print:hover {
            background: #f8fafc;
            border-color: #cbd5e1;
        }

        .btn-wa {
            background: #10b981;
            color: #ffffff;
            box-shadow: 0 4px 12px rgba(16, 185, 129, 0.25);
        }

        .btn-wa:hover {
            background: #059669;
            color: #ffffff;
            transform: translateY(-1px);
        }

        .receipt-footer-note {
            text-align: center;
            font-size: 10.5px;
            color: var(--text-muted);
            margin-top: 20px;
        }

        /* Print optimizations */
        @media print {
            body {
                background: #ffffff;
                padding: 0;
            }
            .receipt-wrapper {
                max-width: 100%;
            }
            .receipt-card {
                box-shadow: none;
                border: 1px solid #94a3b8;
                border-radius: 0;
            }
            .receipt-card::before {
                display: none;
            }
            .receipt-header {
                background: #0f172a !important;
                -webkit-print-color-adjust: exact;
                print-color-adjust: exact;
            }
            .hero-amount-box {
                background: #ecfdf5 !important;
                -webkit-print-color-adjust: exact;
                print-color-adjust: exact;
            }
            .actions-bar {
                display: none !important;
            }
            .receipt-footer-note {
                margin-top: 10px;
            }
        }

        @media (max-width: 500px) {
            body { padding: 12px 8px; }
            .receipt-header { padding: 24px 18px 20px; }
            .receipt-body { padding: 18px; }
            .meta-grid { grid-template-columns: 1fr; gap: 10px; }
            .hero-amount-value { font-size: 30px; }
            .actions-bar { flex-direction: column; }
        }
    </style>
</head>
<body>

<div class="receipt-wrapper">
    <div class="receipt-card">
        
        <!-- Header -->
        <div class="receipt-header">
            <div class="club-emblem">🏸</div>
            <h1 class="club-name"><?php echo htmlspecialchars($club_name); ?></h1>
            <p class="club-sub">YMCA Badminton Club &bull; Poovathussery &bull; Official Fee Receipt</p>
            <div class="paid-badge">
                <i class="fa fa-check-circle"></i> Payment Received &amp; Verified
            </div>
        </div>

        <div class="receipt-body">
            
            <!-- Hero Amount Block -->
            <div class="hero-amount-box">
                <div class="hero-amount-label">Amount Received</div>
                <div class="hero-amount-value">₹<?php echo number_format($amount, 2); ?></div>
                <div class="hero-amount-words">In Words: <?php echo htmlspecialchars($amount_words); ?></div>
            </div>

            <!-- Meta Information Grid -->
            <div class="meta-grid">
                <div class="meta-item">
                    <span class="meta-label">Member Name</span>
                    <span class="meta-value highlight"><?php echo htmlspecialchars($full_name); ?></span>
                </div>
                <div class="meta-item">
                    <span class="meta-label">Receipt Number</span>
                    <span class="meta-value">#<?php echo htmlspecialchars($receipt_no); ?></span>
                </div>
                <div class="meta-item">
                    <span class="meta-label">Member Code</span>
                    <span class="meta-value"><?php echo htmlspecialchars($member_code); ?></span>
                </div>
                <div class="meta-item">
                    <span class="meta-label">Payment Date</span>
                    <span class="meta-value"><?php echo htmlspecialchars($date); ?></span>
                </div>
                <div class="meta-item">
                    <span class="meta-label">Club Group</span>
                    <span class="meta-value"><?php echo htmlspecialchars($group_name); ?></span>
                </div>
                <div class="meta-item">
                    <span class="meta-label">Payment Mode</span>
                    <span class="meta-value">
                        <?php if (stripos($mode, 'Cash') !== false): ?>
                            <i class="fa fa-money" style="color:#059669;"></i>
                        <?php elseif (stripos($mode, 'Wallet') !== false): ?>
                            <i class="fa fa-credit-card" style="color:#2563eb;"></i>
                        <?php else: ?>
                            <i class="fa fa-bank" style="color:#7c3aed;"></i>
                        <?php endif; ?>
                        <?php echo htmlspecialchars($mode); ?>
                    </span>
                </div>
                <div class="meta-item">
                    <span class="meta-label">Registered Phone</span>
                    <span class="meta-value"><?php echo htmlspecialchars($phone ?: 'N/A'); ?></span>
                </div>
                <div class="meta-item">
                    <span class="meta-label">Transaction Status</span>
                    <span class="meta-value" style="color:#059669;">
                        <i class="fa fa-check"></i> Settled / Complete
                    </span>
                </div>
            </div>

            <!-- Particulars Table -->
            <div class="table-responsive">
                <table class="receipt-table">
                    <thead>
                        <tr>
                            <th style="width: 10%;">Sl.</th>
                            <th style="width: 65%;">Particulars / Description</th>
                            <th style="width: 25%;">Amount</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr>
                            <td>01</td>
                            <td>
                                <strong><?php echo htmlspecialchars($particulars); ?></strong>
                                <div class="subtext">Contribution &bull; <?php echo htmlspecialchars($group_name); ?></div>
                            </td>
                            <td>₹<?php echo number_format($amount, 2); ?></td>
                        </tr>
                    </tbody>
                </table>
            </div>

            <!-- Balance Callout -->
            <div class="balance-callout <?php echo ($balance <= 0) ? 'cleared' : 'pending'; ?>">
                <div class="balance-callout-label">
                    <?php if ($balance <= 0): ?>
                        <i class="fa fa-check-circle" style="font-size:16px;"></i> All Monthly Dues Cleared:
                    <?php else: ?>
                        <i class="fa fa-exclamation-circle" style="font-size:16px;"></i> Remaining Outstanding Balance:
                    <?php endif; ?>
                </div>
                <div class="balance-callout-val">
                    ₹<?php echo number_format($balance, 2); ?>
                </div>
            </div>

            <!-- Signatory & Verification Block -->
            <div class="verification-row">
                <div class="verification-stamp">
                    <div class="stamp-badge">
                        <i class="fa fa-shield"></i>
                    </div>
                    <div class="stamp-text">
                        <strong style="color:var(--text-main);">Verified Official Receipt</strong><br>
                        Computer Generated &bull; Authenticated<br>
                        YMCA BCP Poovathussery
                    </div>
                </div>
                <div class="signature-box">
                    <div class="signature-line"></div>
                    <div class="signature-title">Authorized Signatory</div>
                    <div class="signature-club"><?php echo htmlspecialchars($club_name); ?></div>
                </div>
            </div>

            <!-- Actions Bar -->
            <div class="actions-bar">
                <button type="button" class="btn-action btn-print" onclick="window.print()">
                    <i class="fa fa-print"></i> Print Receipt
                </button>
                <a href="<?php echo htmlspecialchars($pdfDownloadUrl); ?>" class="btn-action btn-pdf" target="_blank">
                    <i class="fa fa-download"></i> Download PDF
                </a>
                <a href="<?php echo htmlspecialchars($waShareUrl); ?>" class="btn-action btn-wa" target="_blank">
                    <i class="fa fa-whatsapp"></i> Share on WhatsApp
                </a>
            </div>

            <!-- Footer note -->
            <div class="receipt-footer-note">
                Generated via YMCA Management System on <?php echo date('d-m-Y H:i:s'); ?> &bull; Ref #<?php echo htmlspecialchars($receipt_no); ?>
            </div>

        </div>
    </div>
</div>

</body>
</html>
