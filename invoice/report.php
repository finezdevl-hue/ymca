<?php
session_start();
ini_set('display_errors', 0);
error_reporting(0);

include '../app_common/db_connect.php';
require_once('tcpdf/tcpdf.php');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    ob_start();

    $headInput = isset($_POST['head']) ? $_POST['head'] : '[]';
    $heads = json_decode($headInput, true);
    if (!is_array($heads)) {
        $heads = $headInput ? [$headInput] : [];
    }

    $only_credit = isset($_POST['only_credit']) && $_POST['only_credit'] == 1;

    // Prepare totals
    $grandCredit = 0;
    $grandDebit = 0;

    // Generate PDF
    $pdf = new TCPDF();
    $pdf->setPrintHeader(false);
    $pdf->AddPage();
    $pdf->SetFont('helvetica', '', 12);

    $html = '
        <style>
            table.report {
                width: 100%;
                border-collapse: collapse;
                margin-top: 20px;
            }
            table.report th, table.report td {
                border: 1px solid #999;
                padding: 8px;
                text-align: center;
                font-size: 10pt;
            }
            table.report th {
                background-color: #f2f2f2;
                font-weight: bold;
            }
            h2 {
                text-align: center;
                color: #007BFF;
            }
        </style>
    ';

    if ($only_credit) {
        $html .= '
            <h2>YMCA Credits Report</h2>
            <table class="report">
                <thead>
                    <tr>
                        <th><b>Head</b></th>
                        <th><b>Credit</b></th>
                    </tr>
                </thead>
                <tbody>
        ';
    } else {
        $html .= '
            <h2>YMCA Report</h2>
            <table class="report">
                <thead>
                    <tr>
                        <th><b>Head</b></th>
                        <th><b>Credit</b></th>
                        <th><b>Debit</b></th>
                    </tr>
                </thead>
                <tbody>
        ';
    }

    foreach ($heads as $headId) {
        // Fetch head name
        $sql = "SELECT name FROM tbl_payment_head_master WHERE id = ?";
        $parameters = [$headId];
        $types = "i";
        $headRes = app_exec_getresult($sql, $parameters, $types);
        $head = $headRes ? $headRes->fetch_assoc() : null;

        $headName = $head ? $head['name'] : "Unknown";

        // Get sums (ignoring cancelled transactions)
        $sql = "
            SELECT 
                COALESCE((SELECT SUM(fees) FROM tbl_member_recieved WHERE head=? AND (cancel=0 OR cancel IS NULL)), 0) +
                COALESCE((SELECT SUM(amount) FROM tbl_other_recieved WHERE head=? AND (cancel=0 OR cancel IS NULL)), 0) AS credit,
                COALESCE((SELECT SUM(amount) FROM tbl_paid WHERE head=? AND (cancel=0 OR cancel IS NULL)), 0) AS debit
        ";
        $parameters = [$headId, $headId, $headId];
        $types = "iii";
        $rowRes = app_exec_getresult($sql, $parameters, $types);
        $row = $rowRes ? $rowRes->fetch_assoc() : null;

        $credit = $row ? (int)$row['credit'] : 0;
        $debit = $row ? (int)$row['debit'] : 0;

        // Totals
        $grandCredit += $credit;
        $grandDebit += $debit;

        // Add row
        if ($only_credit) {
            $html .= "
                <tr>
                    <td>" . htmlspecialchars($headName) . "</td>
                    <td>" . htmlspecialchars($credit) . "</td>
                </tr>
            ";
        } else {
            $html .= "
                <tr>
                    <td>" . htmlspecialchars($headName) . "</td>
                    <td>" . htmlspecialchars($credit) . "</td>
                    <td>" . htmlspecialchars($debit) . "</td>
                </tr>
            ";
        }
    }

    // Final total row
    if ($only_credit) {
        $html .= "
            <tr style='font-weight:bold; background:#f9f9f9;'>
                <td>Total Credits</td>
                <td>" . htmlspecialchars($grandCredit) . "</td>
            </tr>
            </tbody>
            </table>
        ";
    } else {
        $html .= "
            <tr style='font-weight:bold; background:#f9f9f9;'>
                <td>Total</td>
                <td>" . htmlspecialchars($grandCredit) . "</td>
                <td>" . htmlspecialchars($grandDebit) . "</td>
            </tr>
        ";

        $balance = $grandCredit - $grandDebit;

        $html .= "
            <tr style='font-weight:bold; background:#f9f9f9;'>
                <td colspan='2'><b>Balance</b></td>
                <td>" . htmlspecialchars($balance) . "</td>
            </tr>
            </tbody>
            </table>
        ";
    }

    $pdf->writeHTML($html, true, false, true, false, '');
    ob_clean();
    header('Content-Type: application/pdf');
    header('Content-Disposition: attachment; filename="report.pdf"');
    $pdf->Output('Report.pdf', 'I');
    exit;
} else {
    http_response_code(400);
    exit("Invalid request.");
}
?>
