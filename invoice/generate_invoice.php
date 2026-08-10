<?php
include '../app_common/db_connect.php';
require_once('tcpdf/tcpdf.php');

// Clean output buffer to prevent broken PDF
ob_clean();

// Check required fields
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['id'])) {
    $invoice_id = intval($_POST['id']);

    // Query member details and calculate total pending balance
    $sql = "SELECT 
                m.first_name, m.middle_name, m.last_name, m.email,
                (
                    COALESCE((SELECT SUM(fees) FROM tbl_member_recievable WHERE member_id = m.id), 0)
                    -
                    COALESCE((SELECT SUM(fees) FROM tbl_member_recieved WHERE member_id = m.id), 0)
                ) AS pending_balance
            FROM tbl_members AS m 
            WHERE m.id = ?";
    $parameters = [
        $invoice_id,        
    ];
    $types = "i";
    $result = app_exec_getresult($sql, $parameters, $types);
    $invoice = $result ? $result->fetch_assoc() : null;

    if (!$invoice) {
        http_response_code(404);
        exit("Invoice not found.");
    }

    $full_name = trim($invoice['first_name'] . ' ' . $invoice['middle_name'] . ' ' . $invoice['last_name']);
    $pending_balance = floatval($invoice['pending_balance']);
    $fees = isset($_POST['fees']) ? floatval($_POST['fees']) : 0;
    $date = isset($_POST['date']) ? htmlspecialchars($_POST['date']) : date('Y-m-d');
    $description = isset($_POST['discription']) ? htmlspecialchars($_POST['discription']) : 'Fee Payment';

    // Generate PDF
    $pdf = new TCPDF('P', 'mm', 'A4', true, 'UTF-8', false);
    $pdf->SetCreator('YMCA BCP');
    $pdf->SetAuthor('YMCA BCP');
    $pdf->SetTitle('Fee Invoice');
    $pdf->setPrintHeader(false);
    $pdf->setPrintFooter(false);
    $pdf->AddPage();
    $pdf->SetFont('helvetica', '', 10);

    $html = '
    <style>
        .title { font-size: 18px; font-weight: bold; color: #1e3a8a; text-align: center; }
        .sub-title { font-size: 11px; color: #64748b; text-align: center; margin-bottom: 15px; }
        .doc-name { font-size: 14px; font-weight: bold; color: #2563eb; text-align: center; margin-bottom: 20px; }
        .info-table { width: 100%; border-collapse: collapse; margin-bottom: 20px; }
        .info-table td { padding: 6px; font-size: 11px; }
        .detail-table { width: 100%; border-collapse: collapse; margin-top: 10px; margin-bottom: 20px; }
        .detail-table th { background-color: #3b82f6; color: #ffffff; font-weight: bold; padding: 8px; font-size: 11px; border: 1px solid #2563eb; }
        .detail-table td { padding: 8px; font-size: 11px; border: 1px solid #cbd5e1; }
        .summary-table { width: 100%; border-collapse: collapse; margin-top: 10px; }
        .summary-table td { padding: 8px; font-size: 11px; border: 1px solid #cbd5e1; }
        .bg-light { background-color: #f8fafc; }
        .bg-alert { background-color: #fef2f2; color: #dc2626; font-weight: bold; }
    </style>

    <div class="title">YMCA BCP POOVATHUSSERY</div>
    <div class="sub-title">Official Fee Statement & Payment Receipt</div>
    <div class="doc-name">OFFICIAL INVOICE & RECEIPT</div>

    <table class="info-table" border="0">
        <tr>
            <td width="60%"><strong>Customer / Member:</strong> ' . htmlspecialchars($full_name) . '</td>
            <td width="40%" align="right"><strong>Invoice Date:</strong> ' . $date . '</td>
        </tr>
        <tr>
            <td width="60%"><strong>Email:</strong> ' . htmlspecialchars($invoice['email']) . '</td>
            <td width="40%" align="right"><strong>Member ID:</strong> #' . $invoice_id . '</td>
        </tr>
    </table>

    <table class="detail-table">
        <thead>
            <tr>
                <th width="70%">Description / Particulars</th>
                <th width="30%" align="right">Amount (Rs.)</th>
            </tr>
        </thead>
        <tbody>
            <tr>
                <td>' . $description . '</td>
                <td align="right">' . number_format($fees, 2) . '</td>
            </tr>
        </tbody>
    </table>

    <table class="summary-table">
        <tr>
            <td width="60%" class="bg-light"><strong>Current Amount Paid / Billed:</strong></td>
            <td width="40%" align="right" class="bg-light"><strong>Rs. ' . number_format($fees, 2) . '</strong></td>
        </tr>
        <tr>
            <td width="60%" class="bg-alert"><strong>Pending Balance (Outstanding):</strong></td>
            <td width="40%" align="right" class="bg-alert"><strong>Rs. ' . number_format($pending_balance, 2) . '</strong></td>
        </tr>
    </table>
    ';

    $pdf->writeHTML($html, true, false, true, false, '');

    // Send headers
    header('Content-Type: application/pdf');
    header('Content-Disposition: attachment; filename="invoice_' . $invoice_id . '.pdf"');

    // Output PDF
    $pdf->Output('invoice_' . $invoice_id . '.pdf', 'I');
    exit;
} else {
    http_response_code(400);
    exit("Missing invoice_id.");
}
?>
