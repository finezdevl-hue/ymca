<?php
    include '../app_common/db_connect.php';
    require_once('tcpdf/tcpdf.php');

    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        ob_start();

        $selected_year = $_POST['selected_year'];

        $sqlYear = "SELECT from_year, to_year FROM tbl_closing WHERE id = ?";
        $types = "i";
        $parameters = [$selected_year];
        $yearRow = app_exec_getresult($sqlYear, $parameters, $types)->fetch_assoc();

        if ($yearRow) {
            $yearLabel = htmlspecialchars($yearRow['from_year'] . ' - ' . $yearRow['to_year']);
        } else {
            $yearLabel = "Unknown Year";
        }

        // 🔹 Fetch all heads where pnl = 1
        $sql = "SELECT id, name FROM tbl_payment_head_master WHERE pnl = ?";
        $types = "i";
        $parameters = [1];
        $result = app_exec_getresult($sql, $parameters, $types);

        if (!$result || $result->num_rows === 0) {
            http_response_code(500);
            exit("No P&L heads found.");
        }

        // Initialize totals
        $grandCredit = 0;
        $grandDebit = 0;

        // Create PDF
        $pdf = new TCPDF();
        $pdf->setPrintHeader(false);
        $pdf->AddPage();
        $pdf->SetFont('helvetica', '', 12);

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
                h4 {
                    text-align: center;
                }
            </style>

            <h2>Profit and Loss Report</h2>
            <h4>Year: ' . $yearLabel . '</h4>
            <table class="report">
                <thead>
                    <tr>
                        <th><b>Head</b></th>
                        <th><b>Income</b></th>
                        <th><b>Expense</b></th>
                    </tr>
                </thead>
                <tbody>
        ';

        // Loop through all heads
        while ($head = $result->fetch_assoc()) {
            $headId = $head['id'];
            $headName = $head['name'];

            // Calculate credit and debit
            $sqlAmount = "
                SELECT 
                COALESCE((SELECT SUM(fees) FROM tbl_member_recievable WHERE head=? AND flag=?), 0) +
                COALESCE((SELECT SUM(amount) FROM tbl_other_recieveble  WHERE head=? AND flag=?), 0) AS credit,
                COALESCE((SELECT SUM(amount) FROM tbl_payable WHERE head=? AND flag=?), 0) AS debit
            ";
            $parameters = [
                $headId,
                $selected_year,
                $headId,
                $selected_year,
                $headId,
                $selected_year,
            ];
            $types = "iiiiii";
            $row = app_exec_getresult($sqlAmount, $parameters, $types)->fetch_assoc();

            $credit = (float)$row['credit'];
            $debit = (float)$row['debit'];

            $grandCredit += $credit;
            $grandDebit  += $debit;

            // Add row to HTML
            $html .= "
                <tr>
                    <td>" . htmlspecialchars($headName) . "</td>
                    <td>" . number_format($credit, 2) . "</td>
                    <td>" . number_format($debit, 2) . "</td>
                </tr>
            ";
        }

        // Totals and Balance
        // Totals and Balance
        $balance = $grandCredit - $grandDebit;

        $html .= "
            <tr style='font-weight:bold; background:#f9f9f9;'>
                <td>Total</td>
                <td>" . number_format($grandCredit, 2) . "</td>
                <td>" . number_format($grandDebit, 2) . "</td>
            </tr>

            <tr style='font-weight:bold; background:#f9f9f9;'>
                <td></td>
                <td></td>
                <td></td>
            </tr>
        ";

        // Check if balance is negative or positive
        if ($balance < 0) {
            $html .= "
                <tr style='font-weight:bold; background:#e9ecef; color:red;'>
                    <td colspan='2'><b>Net Loss</b></td>
                    <td><b>" . number_format(abs($balance), 2) . "</b></td>
                </tr>
            ";
        } else {
            $html .= "
                <tr style='font-weight:bold; background:#e9ecef; color:green;'>
                    <td colspan='2'><b>Net Profit</b></td>
                    <td><b>" . number_format($balance, 2) . "</b></td>
                </tr>
            ";
        }

        $html .= "
                </tbody>
            </table>
        ";

        // Output PDF
        $pdf->writeHTML($html, true, false, true, false, '');
        ob_clean();
        header('Content-Type: application/pdf');
        header('Content-Disposition: attachment; filename=\"Profit_And_Loss.pdf\"');
        $pdf->Output('Profit_And_Loss.pdf', 'I');
        exit;

    } else {
        http_response_code(500);
        exit("Invalid request.");
    }
?>
