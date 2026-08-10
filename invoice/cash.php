<?php
ob_start();
error_reporting(E_ERROR | E_PARSE);

include '../app_common/enums.php';
include '../app_common/db_connect.php';
require_once('tcpdf/tcpdf.php');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $selected_year = $_POST['selected_year'];

    // Fetch year label
    $sqlYear = "SELECT from_year, to_year FROM tbl_closing WHERE id = ?";
    $yearRow = app_exec_getresult($sqlYear, [$selected_year], "i")->fetch_assoc();

    if ($yearRow) {
        $yearLabel = htmlspecialchars($yearRow['from_year'] . " - " . $yearRow['to_year']);
    } else {
        $yearLabel = "Unknown Year";
    }

    // Fetch all heads with cash=1
    $sql = "SELECT id, name FROM tbl_payment_head_master WHERE cash = ?";
    $result = app_exec_getresult($sql, [1], "i");

    if (!$result || $result->num_rows === 0) {
        ob_end_clean();
        exit("No cash heads found.");
    }

    $grandCredit = 0;
    $grandDebit  = 0;

    // Create PDF
    $pdf = new TCPDF();
    $pdf->setPrintHeader(false);
    $pdf->AddPage();
    $pdf->SetFont('helvetica', '', 11);

    // HTML header
    $html = '
        <style>
            table.report {
                width: 100%;
                border-collapse: collapse;
                margin-top: 20px;
            }
            table.report th, table.report td {
                border: 1px solid #999;
                padding: 6px;
                text-align: center;
                font-size: 9pt;
            }
            table.report th {
                background-color: #f2f2f2;
                font-weight: bold;
            }
            h2, h4 {
                text-align: center;
            }
        </style>

        <h2>Cash Transactions</h2>
        <h4>Year: ' . $yearLabel . '</h4>

        <table class="report">
            <thead>
                <tr>
                    <th>Head</th>
                    <th>Credit</th>
                    <th>Debit</th>
                </tr>
            </thead>
            <tbody>
    ';

    // Loop through heads
    while ($head = $result->fetch_assoc()) {

        $headId = $head["id"];
        $headName = $head["name"];

        $sqlAmount = "
            SELECT 
                COALESCE((
                    SELECT SUM(mrd.fees)
                    FROM tbl_member_recieved mrd
                    JOIN tbl_member_recievable mrb ON mrd.receiveble_id = mrb.id
                    WHERE mrb.head = ? AND mrd.flag = ? AND mrd.transaction_type = ?
                ), 0)
                +
                COALESCE((
                    SELECT SUM(ord.amount)
                    FROM tbl_other_recieved ord
                    JOIN tbl_other_recieveble orb ON ord.recieveble_id = orb.id
                    WHERE orb.head = ? AND ord.flag = ? AND ord.transaction_type = ?
                ), 0) AS credit,
                
                COALESCE((
                    SELECT SUM(amount)
                    FROM tbl_paid
                    WHERE head = ? AND flag = ? AND transaction_type = ?
                ), 0) AS debit
        ";

        $params = [
            $headId, $selected_year, TransactionType::CASH,
            $headId, $selected_year, TransactionType::CASH,
            $headId, $selected_year, TransactionType::CASH
        ];

        $row = app_exec_getresult($sqlAmount, $params, "iiiiiiiii")->fetch_assoc();

        $credit = (float)$row['credit'];
        $debit  = (float)$row['debit'];

        $grandCredit += $credit;
        $grandDebit  += $debit;

        $html .= "
            <tr>
                <td>" . htmlspecialchars($headName) . "</td>
                <td>" . number_format($credit, 2) . "</td>
                <td>" . number_format($debit, 2) . "</td>
            </tr>
        ";
    }

    // Total + Balance
    $balance = $grandCredit - $grandDebit;

    $html .= "
        <tr style='font-weight:bold; background:#f9f9f9;'>
            <td><b>Total</b></td>
            <td><b>" . number_format($grandCredit, 2) . "</b></td>
            <td><b>" . number_format($grandDebit, 2) . "</b></td>
        </tr>

        <tr style='font-weight:bold; background:#e9ecef;'>
            <td colspan='2'><b>Cash In Hand</b></td>
            <td><b>" . number_format($balance, 2) . "</b></td>
        </tr>

        </tbody>
        </table>
    ";

    // Write to PDF
    $pdf->writeHTML($html, true, false, true, false, '');

    ob_end_clean(); // MUST be just before Output()

    header("Content-Type: application/pdf");
    header("Content-Disposition: attachment; filename=Cash_Transactions.pdf");

    $pdf->Output("Cash_Transactions.pdf", "I");
    exit;

} else {
    ob_end_clean();
    exit("Invalid Request");
}
?>
