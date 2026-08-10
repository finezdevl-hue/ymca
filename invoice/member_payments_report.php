<?php
    ob_start();
    error_reporting(E_ERROR | E_PARSE);
    ini_set('display_errors', 0);

    include '../app_common/db_connect.php';
    require_once('tcpdf/tcpdf.php');

    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        $selected_year = $_POST['selected_year'];

        // Get year details
        $sqlYear = "SELECT from_year, to_year FROM tbl_closing WHERE id = ?";
        $yearRow = app_exec_getresult($sqlYear, [$selected_year], "i")->fetch_assoc();

        if ($yearRow) {
            $yearLabel = htmlspecialchars($yearRow['from_year'] . ' - ' . $yearRow['to_year']);
        } else {
            ob_end_clean();
            exit("Invalid financial year selected.");
        }

        // Query member payments breakdown
        $sql = "
            SELECT 
                m.id,
                CONCAT(m.first_name, ' ', CASE WHEN m.middle_name IS NULL OR m.middle_name = '' THEN '' ELSE CONCAT(m.middle_name, ' ') END, m.last_name) as name,
                COALESCE((
                    SELECT SUM(fees) 
                    FROM tbl_member_recieved 
                    WHERE member_id = m.id AND flag = ? AND (head != 12 AND head != 6 OR head IS NULL) AND (cancel = 0 OR cancel IS NULL)
                ), 0) as regular_paid,
                COALESCE((
                    SELECT SUM(fees) 
                    FROM tbl_member_recieved 
                    WHERE member_id = m.id AND flag = ? AND head = 6 AND (cancel = 0 OR cancel IS NULL)
                ), 0) as opening_paid,
                COALESCE((
                    SELECT SUM(fees) 
                    FROM tbl_member_recieved 
                    WHERE member_id = m.id AND flag = ? AND head = 12 AND (cancel = 0 OR cancel IS NULL)
                ), 0) as guest_paid,
                COALESCE((
                    SELECT SUM(fees) 
                    FROM tbl_member_recieved 
                    WHERE member_id = m.id AND flag = ? AND (cancel = 0 OR cancel IS NULL)
                ), 0) as total_paid
            FROM tbl_members m
            WHERE m.inactive = 0
            HAVING total_paid > 0
            ORDER BY name ASC
        ";

        $res = app_exec_getresult($sql, [$selected_year, $selected_year, $selected_year, $selected_year], "iiii");

        // --- Generate PDF ---
        $pdf = new TCPDF();
        $pdf->setPrintHeader(false);
        $pdf->setPrintFooter(false);
        $pdf->AddPage();
        $pdf->SetFont('helvetica', '', 11);

        $html = '
            <style>
                table.report {
                    width: 100%;
                    border-collapse: collapse;
                    margin-top: 20px;
                }
                table.report th, table.report td {
                    border: 1px solid #aaa;
                    padding: 6px;
                    font-size: 9pt;
                }
                table.report th {
                    background-color: #f2f2f2;
                    font-weight: bold;
                    text-align: center;
                }
                .text-left {
                    text-align: left;
                }
                .text-right {
                    text-align: right;
                }
                .text-center {
                    text-align: center;
                }
                h2 {
                    text-align: center;
                    color: #007BFF;
                    margin-bottom: 5px;
                }
                h4 {
                    text-align: center;
                    margin-top: 0px;
                    color: #555;
                }
            </style>

            <h2>YMCA Badminton Club Poovathussery</h2>
            <h4>Member Payments Report (' . $yearLabel . ')</h4>
            
            <table class="report">
                <thead>
                    <tr>
                        <th width="8%"><b>No</b></th>
                        <th width="32%"><b>Member Name</b></th>
                        <th width="15%"><b>Regular Paid</b></th>
                        <th width="15%"><b>Opening Paid</b></th>
                        <th width="15%"><b>Guest Paid</b></th>
                        <th width="15%"><b>Total Paid</b></th>
                    </tr>
                </thead>
                <tbody>
        ';

        $slno = 1;
        $totalRegular = 0;
        $totalOpening = 0;
        $totalGuest = 0;
        $totalPayments = 0;

        while ($row = $res->fetch_assoc()) {
            $reg = (double)$row['regular_paid'];
            $open = (double)$row['opening_paid'];
            $guest = (double)$row['guest_paid'];
            $tot = (double)$row['total_paid'];

            $totalRegular += $reg;
            $totalOpening += $open;
            $totalGuest += $guest;
            $totalPayments += $tot;

            $html .= '
                <tr>
                    <td class="text-center">' . $slno++ . '</td>
                    <td class="text-left">' . htmlspecialchars($row['name']) . '</td>
                    <td class="text-right">' . number_format($reg, 2) . '</td>
                    <td class="text-right">' . number_format($open, 2) . '</td>
                    <td class="text-right">' . number_format($guest, 2) . '</td>
                    <td class="text-right"><b>' . number_format($tot, 2) . '</b></td>
                </tr>
            ';
        }

        // Add Grand Totals row
        $html .= '
                <tr style="font-weight: bold; background-color: #f8f9fa;">
                    <td colspan="2" class="text-center"><b>Grand Total</b></td>
                    <td class="text-right">' . number_format($totalRegular, 2) . '</td>
                    <td class="text-right">' . number_format($totalOpening, 2) . '</td>
                    <td class="text-right">' . number_format($totalGuest, 2) . '</td>
                    <td class="text-right"><b>' . number_format($totalPayments, 2) . '</b></td>
                </tr>
                </tbody>
            </table>
        ';

        $pdf->writeHTML($html, true, false, true, false, '');

        ob_end_clean();

        header('Content-Type: application/pdf');
        header('Content-Disposition: attachment; filename="Member_Payments_Report.pdf"');

        $pdf->Output('Member_Payments_Report.pdf', 'I');
        exit;
    } else {
        ob_end_clean();
        http_response_code(500);
        exit("Invalid request method.");
    }
?>
