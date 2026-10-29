<?php
include_once __DIR__ . '/../app_common/enums.php';
include_once __DIR__ . '/../app_common/db_connect.php';
require_once __DIR__ . '/tcpdf/tcpdf.php';

function formatReportDescription($description, $headName, $dateStr) {
    $desc = trim($description ?? '');
    $head = trim($headName ?? '');
    $isMonthlyFee = (stripos($head, 'monthly') !== false);

    if ($isMonthlyFee) {
        // If it already matches "Monthly Fee - Month Year", return as-is
        if (preg_match('/^Monthly Fee\s*-\s*[A-Za-z]+\s+\d{4}/i', $desc)) {
            return $desc;
        }

        $monthNames = [
            1 => "January", 2 => "February", 3 => "March", 4 => "April",
            5 => "May", 6 => "June", 7 => "July", 8 => "August",
            9 => "September", 10 => "October", 11 => "November", 12 => "December"
        ];

        $foundMonth = null;
        $foundYear = null;

        if (!empty($desc)) {
            foreach ($monthNames as $mNum => $mName) {
                if (stripos($desc, $mName) !== false) {
                    $foundMonth = $mName;
                    break;
                }
            }
            if (!$foundMonth) {
                $shortMonths = ["Jan", "Feb", "Mar", "Apr", "May", "Jun", "Jul", "Aug", "Sep", "Oct", "Nov", "Dec"];
                foreach ($shortMonths as $idx => $sName) {
                    if (preg_match('/\b' . $sName . '\b/i', $desc)) {
                        $foundMonth = $monthNames[$idx + 1];
                        break;
                    }
                }
            }
            if (preg_match('/\b(20\d{2})\b/', $desc, $yMatch)) {
                $foundYear = $yMatch[1];
            }
        }

        if ($foundMonth) {
            if (!$foundYear && !empty($dateStr) && $dateStr !== '0000-00-00') {
                $foundYear = date('Y', strtotime($dateStr));
            }
            return "Monthly Fee - " . $foundMonth . ($foundYear ? " " . $foundYear : "");
        }

        if (!empty($dateStr) && $dateStr !== '0000-00-00' && strtotime($dateStr) > 0) {
            $monthYear = date('F Y', strtotime($dateStr));
            return "Monthly Fee - " . $monthYear;
        }

        return !empty($desc) ? $desc : 'Monthly Fee';
    }

    if (!empty($desc)) {
        return $desc;
    }
    return !empty($head) ? $head : '-';
}

if (($_SERVER['REQUEST_METHOD'] ?? '') === 'POST') {
    ob_start();

    $member_id = intval($_POST['member_id'] ?? 0);

    // --- Fetch member details & group mapping ---
    $sqlMember = "SELECT m.id, m.first_name, m.middle_name, m.last_name, m.phone, m.email,
                         COALESCE(g.name, 'General') as group_name
                  FROM tbl_members m
                  LEFT JOIN tbl_group_member_map gmm ON m.id = gmm.member_id
                  LEFT JOIN tbl_groups g ON gmm.group_id = g.id
                  WHERE m.id = ? LIMIT 1";
    $types = "i";
    $parameters = [$member_id];
    $memberRow = app_exec_getresult($sqlMember, $parameters, $types)->fetch_assoc();

    $memberName = $memberRow 
        ? trim($memberRow['first_name'] . ' ' . ($memberRow['middle_name'] ? $memberRow['middle_name'] . ' ' : '') . $memberRow['last_name'])
        : 'Member #' . $member_id;
    $memberPhone = !empty($memberRow['phone']) ? htmlspecialchars($memberRow['phone']) : 'N/A';
    $memberEmail = !empty($memberRow['email']) ? htmlspecialchars($memberRow['email']) : 'N/A';
    $memberGroup = !empty($memberRow['group_name']) ? htmlspecialchars($memberRow['group_name']) : 'General';

    // --- Main SQL Query ---
    $sql = "SELECT 
                p.id AS recieveble_id,
                p.date,
                p.fees AS receiveble_fees,
                p.head,
                p.discription,
                g.name,
                COALESCE(SUM(pd.fees), 0) AS total_received_fees
            FROM tbl_member_recievable AS p 
            LEFT JOIN tbl_payment_head_master AS g ON p.head=g.id 
            LEFT JOIN tbl_member_recieved AS pd ON p.id=pd.receiveble_id AND (pd.cancel = 0 OR pd.cancel IS NULL) 
            WHERE p.member_id = ? AND (p.cancel = 0 OR p.cancel IS NULL)
            GROUP BY p.id, p.date, p.discription, p.fees, p.head, g.name 
            ORDER BY p.date, p.id, p.iscomplete";

    $result = app_exec_getresult($sql, [$member_id], "i");

    // Initialize totals & process rows
    $totalReceivable = 0;
    $totalReceived = 0;
    $rows_data = [];

    if ($result && $result->num_rows > 0) {
        while ($row = $result->fetch_assoc()) {
            $rec = (float)$row['receiveble_fees'];
            $rcv = (float)$row['total_received_fees'];
            $bal = $rec - $rcv;
            $totalReceivable += $rec;
            $totalReceived += $rcv;

            $formattedDate = ($row['date'] && $row['date'] !== '0000-00-00') 
                ? date('d-m-Y', strtotime($row['date'])) 
                : '-';

            $rows_data[] = [
                'date' => $formattedDate,
                'desc' => formatReportDescription($row['discription'], $row['name'], $row['date']),
                'head' => $row['name'] ? ucwords(strtolower($row['name'])) : 'Fee',
                'rec'  => $rec,
                'rcv'  => $rcv,
                'bal'  => $bal
            ];
        }
    }
    $balanceTotal = $totalReceivable - $totalReceived;

    // --- Create PDF Document ---
    $pdf = new TCPDF('P', 'mm', 'A4', true, 'UTF-8', false);
    $pdf->SetCreator('YMCA BCP');
    $pdf->SetAuthor('YMCA Badminton Club');
    $pdf->SetTitle('Member Receivable Report - ' . $memberName);
    $pdf->setPrintHeader(false);
    $pdf->setPrintFooter(false);
    $pdf->SetMargins(12, 10, 12);
    $pdf->SetAutoPageBreak(TRUE, 15);
    $pdf->AddPage();
    $pdf->SetFont('helvetica', '', 9);

    $genDate = date('d M Y, h:i A');

    // --- Modern PDF Layout ---
    $html = '
    <style>
        .club-title { font-size: 16pt; font-weight: bold; color: #1e3a8a; letter-spacing: 0.5px; }
        .club-subtitle { font-size: 8.5pt; color: #64748b; line-height: 1.4; }
        .report-banner { background-color: #1e3a8a; color: #ffffff; text-align: center; font-size: 11pt; font-weight: bold; padding: 7px; letter-spacing: 1px; }
        
        .meta-table { width: 100%; border: 1px solid #cbd5e1; background-color: #f8fafc; margin-top: 10px; margin-bottom: 10px; }
        .meta-table td { padding: 5px 8px; font-size: 8.5pt; color: #1e293b; }
        .meta-label { color: #64748b; font-weight: bold; width: 15%; }
        .meta-val { color: #0f172a; width: 35%; }

        .kpi-table { width: 100%; margin-top: 5px; margin-bottom: 12px; }
        .kpi-table td { text-align: center; padding: 8px 6px; border: 1px solid #cbd5e1; }
        .kpi-card-blue { background-color: #eff6ff; }
        .kpi-card-green { background-color: #ecfdf5; }
        .kpi-card-red { background-color: #fef2f2; }
        .kpi-lbl { font-size: 7.5pt; font-weight: bold; color: #64748b; text-transform: uppercase; letter-spacing: 0.5px; }
        .kpi-val { font-size: 12pt; font-weight: bold; }
        .val-blue { color: #1e40af; }
        .val-green { color: #065f46; }
        .val-red { color: #991b1b; }

        table.data-table { width: 100%; border-collapse: collapse; margin-top: 6px; }
        table.data-table th { background-color: #1e293b; color: #ffffff; font-weight: bold; font-size: 8.5pt; padding: 7px 4px; text-align: center; border: 1px solid #0f172a; }
        table.data-table td { font-size: 8pt; padding: 5px 4px; border: 1px solid #e2e8f0; color: #334155; }
        .row-even { background-color: #f8fafc; }
        .row-odd { background-color: #ffffff; }

        .text-center { text-align: center; }
        .text-left { text-align: left; }
        .text-right { text-align: right; }
        .font-bold { font-weight: bold; }
        
        .status-paid { color: #059669; font-weight: bold; }
        .status-due { color: #dc2626; font-weight: bold; }

        .totals-row td { background-color: #f1f5f9; font-weight: bold; font-size: 8.5pt; padding: 7px 4px; border: 1px solid #cbd5e1; color: #0f172a; }
        
        .footer-note { font-size: 7.5pt; color: #64748b; line-height: 1.4; }
        .sign-table { width: 100%; margin-top: 25px; }
        .sign-table td { font-size: 8pt; color: #475569; }
    </style>

    <table width="100%" cellpadding="0" cellspacing="0" border="0">
        <tr>
            <td align="center">
                <span class="club-title">YMCA BADMINTON CLUB POOVATHUSSERY</span><br>
                <span class="club-subtitle">Poovathussery, Parakkadavu P.O., Ernakulam Dist., Kerala &bull; Phone: +91 94951 83688</span>
            </td>
        </tr>
    </table>
    <div style="height: 6px;"></div>

    <table width="100%" cellpadding="0" cellspacing="0" border="0">
        <tr>
            <td class="report-banner">MEMBER RECEIVABLE STATEMENT</td>
        </tr>
    </table>

    <table class="meta-table" cellpadding="3" cellspacing="0">
        <tr>
            <td class="meta-label">Member Name:</td>
            <td class="meta-val"><b>' . htmlspecialchars($memberName) . '</b></td>
            <td class="meta-label">Member ID:</td>
            <td class="meta-val"><b>#' . $member_id . '</b></td>
        </tr>
        <tr>
            <td class="meta-label">Phone:</td>
            <td class="meta-val">' . $memberPhone . '</td>
            <td class="meta-label">Batch / Group:</td>
            <td class="meta-val">' . $memberGroup . '</td>
        </tr>
        <tr>
            <td class="meta-label">Email:</td>
            <td class="meta-val">' . $memberEmail . '</td>
            <td class="meta-label">Statement Date:</td>
            <td class="meta-val">' . $genDate . '</td>
        </tr>
    </table>

    <table class="kpi-table" cellpadding="4" cellspacing="0">
        <tr>
            <td width="33.33%" class="kpi-card-blue">
                <div class="kpi-lbl">TOTAL BILLED / RECEIVABLE</div>
                <div class="kpi-val val-blue">Rs. ' . number_format($totalReceivable, 2) . '</div>
            </td>
            <td width="33.33%" class="kpi-card-green">
                <div class="kpi-lbl">TOTAL PAID / SETTLED</div>
                <div class="kpi-val val-green">Rs. ' . number_format($totalReceived, 2) . '</div>
            </td>
            <td width="33.34%" class="kpi-card-red">
                <div class="kpi-lbl">NET OUTSTANDING BALANCE</div>
                <div class="kpi-val val-red">Rs. ' . number_format($balanceTotal, 2) . '</div>
            </td>
        </tr>
    </table>

    <table class="data-table" cellpadding="4" cellspacing="0">
        <thead>
            <tr>
                <th width="5%">#</th>
                <th width="13%">Date</th>
                <th width="38%" style="text-align: left;">Description</th>
                <th width="14%">Head</th>
                <th width="10%" style="text-align: right;">Billed (Rs.)</th>
                <th width="10%" style="text-align: right;">Paid (Rs.)</th>
                <th width="10%" style="text-align: right;">Balance (Rs.)</th>
            </tr>
        </thead>
        <tbody>';

    if (empty($rows_data)) {
        $html .= '<tr><td colspan="7" class="text-center" style="padding: 18px; color: #64748b;">No receivable or payment records found for this member.</td></tr>';
    } else {
        $slno = 1;
        foreach ($rows_data as $idx => $r) {
            $rowClass = ($idx % 2 === 0) ? 'row-odd' : 'row-even';
            $balClass = ($r['bal'] > 0) ? 'status-due' : 'status-paid';
            $balText = ($r['bal'] > 0) ? number_format($r['bal'], 2) : '0.00';

            $html .= '
                <tr class="' . $rowClass . '">
                    <td width="5%" class="text-center">' . $slno++ . '</td>
                    <td width="13%" class="text-center">' . htmlspecialchars($r['date']) . '</td>
                    <td width="38%" class="text-left font-bold">' . htmlspecialchars($r['desc']) . '</td>
                    <td width="14%" class="text-center">' . htmlspecialchars($r['head']) . '</td>
                    <td width="10%" class="text-right">' . number_format($r['rec'], 2) . '</td>
                    <td width="10%" class="text-right">' . number_format($r['rcv'], 2) . '</td>
                    <td width="10%" class="text-right ' . $balClass . '">' . $balText . '</td>
                </tr>';
        }
    }

    $html .= '
            <tr class="totals-row">
                <td colspan="4" class="text-right font-bold">TOTAL (Rs.):</td>
                <td class="text-right font-bold">' . number_format($totalReceivable, 2) . '</td>
                <td class="text-right font-bold">' . number_format($totalReceived, 2) . '</td>
                <td class="text-right font-bold status-due">' . number_format($balanceTotal, 2) . '</td>
            </tr>
        </tbody>
    </table>

    <table class="sign-table" width="100%">
        <tr>
            <td width="60%" class="footer-note">
                &bull; This is an official computer-generated statement from YMCA Badminton Club.<br>
                &bull; For questions regarding payments or balance, please contact the treasurer.
            </td>
            <td width="40%" align="right">
                <br><br>
                <b>_________________________________</b><br>
                <b>Authorized Signatory / Treasurer</b><br>
                <span style="font-size:7.5pt; color:#64748b;">YMCA Badminton Club Poovathussery</span>
            </td>
        </tr>
    </table>
    ';

    // --- Write to PDF ---
    $pdf->writeHTML($html, true, false, true, false, '');

    // --- Output PDF ---
    ob_clean();
    header('Content-Type: application/pdf');
    header('Content-Disposition: attachment; filename="payment_report.pdf"');
    $pdf->Output('payment_report.pdf', 'I');
    exit;

} elseif (php_sapi_name() !== 'cli') {
    http_response_code(500);
    exit("Invalid request.");
}
?>
