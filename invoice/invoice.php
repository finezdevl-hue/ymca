<?php
include '../app_common/db_connect.php';
require_once('tcpdf/tcpdf.php'); // path to TCPDF

// Database connection
$id =1;
$date='2025-05-26';
$fees=300;

$sql = "SELECT first_name, middle_name, last_name, email FROM tbl_members WHERE id= ?";
$parameters = [
    $id,
];
    
$types="i";
$result = app_exec_getresult($sql, $parameters, $types);

// Start HTML content
$html = '<h1 style="text-align:center;">User Report</h1>';
$html .= '
<table border="1" cellpadding="4">
    <thead>
        <tr>
            <th>date</th>
            <th>fees</th>
            <th>Name</th>
            <th>Email</th>
        </tr>
    </thead>
    <tbody>';

    if ($result->num_rows > 0) {
        while($row = $result->fetch_assoc()) {
            $html .= '<tr>
            <td>' . htmlspecialchars($date) . '</td>
            <td>' . htmlspecialchars($fees) . '</td>
            <td>' . htmlspecialchars($row['first_name']) . '</td>
            <td>' . htmlspecialchars($row['email']) . '</td>
            </tr>';
        }
    }
    else {
        $html .= '<tr><td colspan="3">No records found.</td></tr>';
    }

$html .= '</tbody></table>';

// Create new PDF document
$pdf = new TCPDF();
$pdf->AddPage();

// Set font
$pdf->SetFont('helvetica', '', 12);

// Output HTML content
$pdf->writeHTML($html, true, false, true, false, '');

// Output PDF
$pdf->Output('user_report.pdf', 'D'); // D = Download

$conn->close();
?>
