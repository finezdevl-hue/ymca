<?php
error_reporting(0);
ini_set('display_errors', 0);

ob_start();

include '../app_common/db_connect.php';
require_once('tcpdf/tcpdf.php');

// Fetch all bank transactions (custom + payment-sourced)
$sqldatarows = "SELECT * FROM (
                  SELECT 'custom' AS source, id, date, type, amount, reference_no, description FROM tbl_bank_transactions
                  UNION ALL
                  SELECT 'payment' AS source, id, date, CASE WHEN head = 8 THEN 'Deposit' ELSE 'Withdrawal' END AS type, amount, '' AS reference_no, particuler AS description FROM tbl_paid WHERE head IN (8, 11)
                ) AS combined 
                ORDER BY date DESC, id DESC";

$result = app_exec_query($sqldatarows);

$sqlbalance = "SELECT 
                  (
                    COALESCE((SELECT SUM(amount) FROM tbl_bank_transactions WHERE type = 'Deposit'), 0) +
                    COALESCE((SELECT SUM(amount) FROM tbl_paid WHERE head = 8), 0)
                  ) - (
                    COALESCE((SELECT SUM(amount) FROM tbl_bank_transactions WHERE type = 'Withdrawal'), 0) +
                    COALESCE((SELECT SUM(amount) FROM tbl_paid WHERE head = 11), 0)
                  ) AS bank_balance";
$balanceResult = app_exec_query($sqlbalance);
$bankBalance = $balanceResult ? $balanceResult->fetch_assoc()['bank_balance'] : 0;
if ($bankBalance === null) $bankBalance = 0;

$pdf = new TCPDF();
$pdf->setPrintHeader(false);
$pdf->setPrintFooter(false);
$pdf->AddPage();
$pdf->SetFont('helvetica', '', 10);

$html = '
<style>
    h2 { text-align: center; margin-bottom: 2px; }
    .balance-box { background-color: #3b82f6; color: white; padding: 12px; text-align: center; margin-bottom: 20px; font-weight: bold; border-radius: 8px; font-size: 14pt; }
    table.report { width: 100%; border-collapse: collapse; margin-top: 15px; }
    table.report th, table.report td {
        border: 1px solid #ddd; padding: 8px; text-align: left; font-size: 9pt;
    }
    table.report th { background-color: #f3f4f6; font-weight: bold; }
    .text-center { text-align: center; }
    .text-right { text-align: right; }
    .deposit { color: #10b981; font-weight: bold; }
    .withdrawal { color: #ef4444; font-weight: bold; }
</style>

<h2>YMCA BCP Poovathussery | Bank Transactions Report</h2>
<p style="text-align: center; font-size: 9pt; color: #666; margin-top: 0;">Generated on: ' . date('Y-m-d H:i') . '</p>

<div class="balance-box">
    Available Bank Balance: ₹' . number_format($bankBalance, 2) . '
</div>

<table class="report">
    <thead>
        <tr>
            <th width="8%" class="text-center">Sl No</th>
            <th width="15%">Date</th>
            <th width="15%">Type</th>
            <th width="18%" class="text-right">Amount</th>
            <th width="19%">Ref / Cheque No</th>
            <th width="25%">Description</th>
        </tr>
    </thead>
    <tbody>';

$slno = 1;
if ($result && $result->num_rows > 0) {
    while ($row = $result->fetch_assoc()) {
        $typeClass = strtolower($row['type']) == 'deposit' ? 'deposit' : 'withdrawal';
        $html .= '
        <tr>
            <td class="text-center">' . $slno++ . '</td>
            <td>' . htmlspecialchars($row['date']) . '</td>
            <td class="' . $typeClass . '">' . htmlspecialchars($row['type']) . '</td>
            <td class="text-right">₹' . number_format($row['amount'], 2) . '</td>
            <td>' . htmlspecialchars($row['reference_no']) . '</td>
            <td>' . htmlspecialchars($row['description']) . '</td>
        </tr>';
    }
} else {
    $html .= '<tr><td colspan="6" class="text-center">No transactions found</td></tr>';
}

$html .= '
    </tbody>
</table>';

$pdf->writeHTML($html, true, false, true, false, '');

ob_end_clean();

header("Content-Type: application/pdf");
header("Content-Disposition: attachment; filename=bank_transactions_report.pdf");

$pdf->Output("bank_transactions_report.pdf", "I");
exit;
?>
