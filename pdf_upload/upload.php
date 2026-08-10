<?php
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    if (isset($_FILES['pdfFile']) && $_FILES['pdfFile']['error'] == 0) {
        $uploadDir = 'uploads/';
        if (!is_dir($uploadDir)) {
            mkdir($uploadDir, 0777, true);
        }

        $fileName = basename($_FILES['pdfFile']['name']);
        $targetPath = $uploadDir . $fileName;

        // Move uploaded file to the target directory
        if (move_uploaded_file($_FILES['pdfFile']['tmp_name'], $targetPath)) {
            echo $fileName; // Return only the filename
        } else {
            echo "Error moving the uploaded file.";
        }
    } else {
        echo "No file uploaded or file error.";
    }
}
?>

