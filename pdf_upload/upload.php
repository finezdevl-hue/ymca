<?php
require_once __DIR__ . '/../includes/api_auth.php';

// Ensure user is authenticated before allowing upload
start_secure_session();
require_login();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_FILES['pdfFile']) && $_FILES['pdfFile']['error'] === UPLOAD_ERR_OK) {
        $file = $_FILES['pdfFile'];
        $tmpName = $file['tmp_name'];

        // Verify MIME type
        $finfo = finfo_open(FILEINFO_MIME_TYPE);
        $mime = finfo_file($finfo, $tmpName);
        finfo_close($finfo);

        if ($mime !== 'application/pdf') {
            http_response_code(400);
            echo "Error: Only valid PDF files are allowed.";
            exit;
        }

        // Validate extension
        $origExt = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
        if ($origExt !== 'pdf') {
            http_response_code(400);
            echo "Error: Invalid file extension.";
            exit;
        }

        $uploadDir = __DIR__ . '/uploads/';
        if (!is_dir($uploadDir)) {
            mkdir($uploadDir, 0755, true);
        }

        // Generate sanitized unique filename
        $safeName = 'bulletin_' . time() . '_' . bin2hex(random_bytes(6)) . '.pdf';
        $targetPath = $uploadDir . $safeName;

        if (move_uploaded_file($tmpName, $targetPath)) {
            echo $safeName;
        } else {
            http_response_code(500);
            echo "Error moving the uploaded file.";
        }
    } else {
        http_response_code(400);
        echo "No file uploaded or file upload error.";
    }
}
