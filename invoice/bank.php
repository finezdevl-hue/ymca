<?php
error_reporting(0);
ini_set('display_errors', 0);

ob_start();

include '../app_common/enums.php';
include '../app_common/db_connect.php';
require_once('tcpdf/tcpdf.php');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $selected_year = $_POST['selected_year'];

    // Get year
    $sqlYear = "SELECT from_year, to_year FROM tbl_closing WHERE id = ?";
    $yearRow = app_exec_getresult($sqlYear, [$selected_year], "i")->fetch_assoc();

    $yearLabel = $yearRow ? ($yearRow['from_year'] . " - " . $yearRow['to_year']) : "Unknown Year";

    // Get heads
    $result = app_exec_getresult("SELECT id, name FROM tbl_payment_head_master WHERE bank = ?", [1], "i");

    if (!$result || $result->num_rows === 0) {
        ob_end_clean();
        exit("No Bank heads found.");
    }

    $grandCredit = 0;
    $grandDebit  = 0;

    // PDF start
    $pdf = new TCPDF();
    $pdf->setPrintHeader(false);
    $pdf->AddPage();
    $pdf->SetFont('helvetica', '', 10);

    $html = '
    <style>
        table.report { width: 100%; border-collapse: collapse; }
        table.report th, table.report td {
            border: 1px solid #000; padding: 6px; text-align: center; font-size: 9pt;
        }
        table.report th { background-color: #f0f0f0; }
        h2, h4 { text-align: center; }
    </style>

    <h2>YMCA BCP Poovathussery</h2>
    <h4>Bank Transactions (Year: ' . $yearLabel . ')</h4>

    <table class="report">
        <tr>
            <th>Head</th>
            <th>Credit</th>
            <th>Debit</th>
        </tr>
    ';

    while ($head = $result->fetch_assoc()) {

        $headId = $head['id'];

        $sqlAmount = "
            SELECT 
            COALESCE((SELECT SUM(mrd.fees)
                      FROM tbl_member_recieved mrd 
                      JOIN tbl_member_recievable mrb ON mrd.receiveble_id=mrb.id
                      WHERE mrb.head=? AND mrd.flag=? AND mrd.transaction_type=?),0)
            +
            COALESCE((SELECT SUM(ord.amount)
                      FROM tbl_other_recieved ord
                      JOIN tbl_other_recieveble orb ON ord.recieveble_id=orb.id
                      WHERE orb.head=? AND ord.flag=? AND ord.transaction_type=?),0) 
            AS credit,

            COALESCE((SELECT SUM(amount)
                      FROM tbl_paid
                      WHERE head=? AND flag=? AND transaction_type=?),0) AS debit
        ";

        $row = app_exec_getresult($sqlAmount, [
            $headId, $selected_year, TransactionType::BANK,
            $headId, $selected_year, TransactionType::BANK,
            $headId, $selected_year, TransactionType::BANK
        ], "iiiiiiiii")->fetch_assoc();

        $credit = (float)$row['credit'];
        $debit  = (float)$row['debit'];

        $grandCredit += $credit;
        $grandDebit  += $debit;

        $html .= "
        <tr>
            <td>" . htmlspecialchars($head['name']) . "</td>
            <td>" . number_format($credit, 2) . "</td>
            <td>" . number_format($debit, 2) . "</td>
        </tr>";
    }

    $balance = $grandCredit - $grandDebit;

    $html .= "
        <tr style='font-weight:bold; background:#f9f9f9;'>
            <td>Total</td>
            <td>" . number_format($grandCredit, 2) . "</td>
            <td>" . number_format($grandDebit, 2) . "</td>
        </tr>

        <tr style='font-weight:bold; background:#e9ecef;'>
            <td colspan='2'>Cash In Hand</td>
            <td>" . number_format($balance, 2) . "</td>
        </tr>
    </table>
    ";

    $pdf->writeHTML($html, true, false, true, false, '');

    ob_end_clean();   // ✅ THE CORRECT FIX

    header("Content-Type: application/pdf");
    header("Content-Disposition: inline; filename=Bank_Transactions.pdf");

    $pdf->Output("Bank_Transactions.pdf", "I");
    exit;
}

ob_end_clean();
exit("Invalid request.");
