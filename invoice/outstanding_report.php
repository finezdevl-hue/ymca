<?php
ob_start();
error_reporting(E_ERROR | E_PARSE);

include '../app_common/enums.php';
include '../app_common/db_connect.php';
require_once('tcpdf/tcpdf.php');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    // --- Main SQL Query (Summary per member) ---
    $sql = "
        SELECT 
            m.id AS member_id,
            CONCAT(m.first_name, ' ', m.middle_name, ' ', m.last_name) AS member_name,
            COALESCE(r.total_receivable, 0) AS total_receivable,
            COALESCE(d.total_received, 0) AS total_received,
            (COALESCE(r.total_receivable, 0) - COALESCE(d.total_received, 0)) AS balance
        FROM tbl_members AS m
        LEFT JOIN (
            SELECT member_id, SUM(fees) AS total_receivable
            FROM tbl_member_recievable
            GROUP BY member_id
        ) AS r ON m.id = r.member_id
        LEFT JOIN (
            SELECT p.member_id, SUM(pd.fees) AS total_received
            FROM tbl_member_recievable AS p
            JOIN tbl_member_recieved AS pd ON p.id = pd.receiveble_id
            GROUP BY p.member_id
        ) AS d ON m.id = d.member_id
        WHERE COALESCE(r.total_receivable, 0) > 0 OR COALESCE(d.total_received, 0) > 0
        HAVING balance <> 0
        ORDER BY m.first_name, m.middle_name, m.last_name;
    ";

    $result = app_exec_query($sql);

    if (!$result || $result->num_rows === 0) {
        ob_end_clean();
        http_response_code(500);
        exit("No data found.");
    }

    // Initialize totals
    $grandReceivable = 0;
    $grandReceived = 0;
    $serialNo = 1;

    // --- Create PDF ---
    $pdf = new TCPDF();
    $pdf->setPrintHeader(false);
    $pdf->AddPage();
    $pdf->SetFont('helvetica', '', 11);

    // --- HTML Header ---
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
            h2 {
                text-align: center;
                color: #007BFF;
            }
        </style>

        <h2>Member Receivable Summary Report</h2>

        <table class="report">
            <thead>
                <tr>
                    <th><b>No</b></th>
                    <th><b>Member Name</b></th>
                    <th><b>Total Receivable</b></th>
                    <th><b>Total Received</b></th>
                    <th><b>Balance</b></th>
                </tr>
            </thead>
            <tbody>
    ';

    // --- Loop through members ---
    while ($row = $result->fetch_assoc()) {
        $receivable = (float)$row['total_receivable'];
        $received = (float)$row['total_received'];
        $balance = $receivable - $received;

        $grandReceivable += $receivable;
        $grandReceived += $received;

        $html .= "
            <tr>
                <td>" . $serialNo++ . "</td>
                <td>" . htmlspecialchars($row['member_name']) . "</td>
                <td>" . number_format($receivable, 2) . "</td>
                <td>" . number_format($received, 2) . "</td>
                <td>" . number_format($balance, 2) . "</td>
            </tr>
        ";
    }

    // --- Grand Totals ---
    $grandBalance = $grandReceivable - $grandReceived;

    $html .= "
        <tr style='font-weight:bold; background:#f9f9f9;'>
            <td colspan='2'><b>Grand Total</b></td>
            <td></td>
            <td><b>" . number_format($grandReceivable, 2) . "</b></td>
            <td><b>" . number_format($grandReceived, 2) . "</b></td>
            <td><b>" . number_format($grandBalance, 2) . "</b></td>
        </tr>
        </tbody>
        </table>
    ";

    // --- Write to PDF ---
    $pdf->writeHTML($html, true, false, true, false, '');

    // --- Output PDF (IMPORTANT FIX) ---
    ob_end_clean(); // correct fix for live server

    header('Content-Type: application/pdf');
    header('Content-Disposition: attachment; filename="Outstanding_Payment.pdf"');

    $pdf->Output('Outstanding_Payment.pdf', 'I');
    exit;

} else {
    ob_end_clean();
    http_response_code(500);
    exit("Invalid request.");
}
?>
