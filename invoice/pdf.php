<?php
// ob_clean();

// require_once('tcpdf/tcpdf.php'); // path to TCPDF

// Fetch data
function downloadInvoice($html){
    // $sql = "SELECT first_name, middle_name, last_name FROM tbl_members Where id =" . $id;
    // $result = app_exec_query($sql);

    // Start HTML content
   

    // Create new PDF document
    $pdf = new TCPDF();
    $pdf->AddPage();

    // Set font
    $pdf->SetFont('helvetica', '', 12);

    // Output HTML content
    $pdf->writeHTML($html, true, false, true, false, '');

    ob_end_clean(); 
    // Output PDF
    $pdf->Output('user_report.pdf', 'D'); // D = Download


    }

?>
