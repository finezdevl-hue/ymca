<?php
include '../app_common/enums.php';
include '../app_common/db_connect.php';
require_once('tcpdf/tcpdf.php');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    ob_start();

    $member_id = $_POST['member_id'];

    // --- Fetch member info (for title) ---
    $sqlMember = "SELECT CONCAT(first_name, ' ', middle_name, ' ', last_name) AS member_name 
                  FROM tbl_members WHERE id = ?";
    $types = "i";
    $parameters = [$member_id];
    $memberRow = app_exec_getresult($sqlMember, $parameters, $types)->fetch_assoc();
    $memberName = $memberRow ? htmlspecialchars($memberRow['member_name']) : 'Unknown Member';

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
            LEFT JOIN tbl_member_recieved AS pd ON p.id=pd.receiveble_id 
            WHERE p.member_id = ?
            GROUP BY p.id, p.date, p.discription, p.fees, p.head, g.name 
            ORDER BY  p.date, p.iscomplete";

    $types = "i";
    $parameters = [$member_id];
    $result = app_exec_getresult($sql, $parameters, $types);

    if (!$result || $result->num_rows === 0) {
        http_response_code(500);
        exit("No data found for this member.");
    }

    // Initialize totals
    $totalReceivable = 0;
    $totalReceived = 0;
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
            h4 {
                text-align: center;
            }
        </style>

        <h2>Member Receivable Report</h2>
        <h4>Member: ' . $memberName . '</h4>

        <table class="report">
            <thead>
                <tr>
                    <th><b>No</b></th>
                    <th><b>Date</b></th>
                    <th><b>Description</b></th>
                    <th><b>Head</b></th>
                    <th><b>Receivable</b></th>
                    <th><b>Received</b></th>
                    <th><b>Balance</b></th>
                </tr>
            </thead>
            <tbody>
    ';

    // --- Loop through records ---
    while ($row = $result->fetch_assoc()) {
        $receivable = (float)$row['receiveble_fees'];
        $received = (float)$row['total_received_fees'];
        $balance = $receivable - $received;

        $totalReceivable += $receivable;
        $totalReceived += $received;

        $html .= "
            <tr>
                <td>" . $serialNo++ . "</td>
                <td>" . htmlspecialchars($row['date']) . "</td>
                <td>" . htmlspecialchars($row['discription']) . "</td>
                <td>" . htmlspecialchars($row['name']) . "</td>
                <td>" . number_format($receivable, 2) . "</td>
                <td>" . number_format($received, 2) . "</td>
                <td>" . number_format($balance, 2) . "</td>
            </tr>
        ";
    }

    // --- Totals ---
    $balanceTotal = $totalReceivable - $totalReceived;

    $html .= "
        <tr style='font-weight:bold; background:#f9f9f9;'>
            <td colspan='4'><b>Total</b></td>
            <td></td>
            <td></td>
            <td></td>
            <td><b>" . number_format($totalReceivable, 2) . "</b></td>
            <td><b>" . number_format($totalReceived, 2) . "</b></td>
            <td><b>" . number_format($balanceTotal, 2) . "</b></td>
        </tr>
    ";

    $html .= "
            </tbody>
        </table>
    ";

    // --- Write to PDF ---
    $pdf->writeHTML($html, true, false, true, false, '');

    // --- Output PDF ---
    ob_clean();
    header('Content-Type: application/pdf');
    header('Content-Disposition: attachment; filename=\"payment_report.pdf\"');
    $pdf->Output('payment_report.pdf', 'I');
    exit;

} else {
    http_response_code(500);
    exit("Invalid request.");
}
?>
