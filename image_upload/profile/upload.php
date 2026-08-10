<?php
header('Content-Type: application/json');
error_reporting(0);
ini_set('display_errors', 0);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        if (isset($_FILES['croppedImage']) && $_FILES['croppedImage']['error'] === UPLOAD_ERR_OK) {
            $file = $_FILES['croppedImage'];
            $uploadDir = 'uploads/';
            $thumbnailDir = 'thumbnails/';

            // Set the correct time zone
            date_default_timezone_set('Asia/Kolkata');

            // Validate uploaded file type
            $fileInfo = getimagesize($file['tmp_name']);
            if ($fileInfo === false) {
                echo json_encode(['status' => 'error', 'message' => 'Invalid file type.']);
                exit;
            }

            $allowedMimeTypes = ['image/jpeg', 'image/jpg', 'image/pjpeg', 'image/png', 'image/gif', 'image/webp'];
            if (!in_array(strtolower($fileInfo['mime']), $allowedMimeTypes)) {
                echo json_encode(['status' => 'error', 'message' => 'Unsupported file format. Only JPEG, PNG, GIF, and WEBP are supported.']);
                exit;
            }

            // Create directories if they don't exist
            if (!is_dir($uploadDir) && !mkdir($uploadDir, 0777, true)) {
                echo json_encode(['status' => 'error', 'message' => 'Failed to create upload directory.']);
                exit;
            }
            if (!is_dir($thumbnailDir) && !mkdir($thumbnailDir, 0777, true)) {
                echo json_encode(['status' => 'error', 'message' => 'Failed to create thumbnail directory.']);
                exit;
            }

            // Generate unique file name
            $currentDateTime = date('Y-m-d_H-i-s');
            $extension = pathinfo($file['name'], PATHINFO_EXTENSION);
            if (empty($extension)) {
                $extension = 'jpg';
            }
            $fileName = $currentDateTime . '.' . $extension;
            $filePath = $uploadDir . $fileName;
            $thumbnailPath = $thumbnailDir . $fileName;

            // Move uploaded file and create thumbnail
            if (move_uploaded_file($file['tmp_name'], $filePath)) {
                if (createThumbnail($filePath, $thumbnailPath, $fileInfo['mime'], 128, 128)) {
                    echo json_encode(['status' => 'success','filename' => $fileName, 'message' => 'Image uploaded successfully!', 'path' => $filePath, 'thumbnail' => $thumbnailPath]);
                } else {
                    echo json_encode(['status' => 'error', 'message' => 'Failed to create thumbnail.']);
                }
            } else {
                echo json_encode(['status' => 'error', 'message' => 'Failed to upload the image.']);
            }
        } else {
            echo json_encode(['status' => 'error', 'message' => 'No valid image file found.']);
        }
    } catch (Throwable $e) {
        echo json_encode(['status' => 'error', 'message' => 'An unexpected error occurred: ' . $e->getMessage()]);
    }
} else {
    echo json_encode(['status' => 'error', 'message' => 'Invalid request method.']);
}

function createThumbnail($sourcePath, $destPath, $mimeType, $width, $height) {
    if (!extension_loaded('gd') || !function_exists('imagecreatetruecolor')) {
        return @copy($sourcePath, $destPath);
    }

    try {
        $mimeType = strtolower($mimeType);
        $image = false;
        switch ($mimeType) {
            case 'image/jpeg':
            case 'image/jpg':
            case 'image/pjpeg':
                if (function_exists('imagecreatefromjpeg')) $image = @imagecreatefromjpeg($sourcePath);
                break;
            case 'image/png':
                if (function_exists('imagecreatefrompng')) $image = @imagecreatefrompng($sourcePath);
                break;
            case 'image/gif':
                if (function_exists('imagecreatefromgif')) $image = @imagecreatefromgif($sourcePath);
                break;
            case 'image/webp':
                if (function_exists('imagecreatefromwebp')) $image = @imagecreatefromwebp($sourcePath);
                break;
        }

        if (!$image) {
            return @copy($sourcePath, $destPath);
        }

        $originalWidth = imagesx($image);
        $originalHeight = imagesy($image);

        $thumbnail = imagecreatetruecolor($width, $height);

        // Preserve transparency for PNG, GIF, and WEBP
        if ($mimeType === 'image/png' || $mimeType === 'image/gif' || $mimeType === 'image/webp') {
            imagecolortransparent($thumbnail, imagecolorallocatealpha($thumbnail, 0, 0, 0, 127));
            imagealphablending($thumbnail, false);
            imagesavealpha($thumbnail, true);
        }

        if (!imagecopyresampled($thumbnail, $image, 0, 0, 0, 0, $width, $height, $originalWidth, $originalHeight)) {
            imagedestroy($image);
            imagedestroy($thumbnail);
            return @copy($sourcePath, $destPath);
        }

        // Save the thumbnail based on MIME type
        $success = false;
        switch ($mimeType) {
            case 'image/jpeg':
            case 'image/jpg':
            case 'image/pjpeg':
                $success = imagejpeg($thumbnail, $destPath, 90);
                break;
            case 'image/png':
                $success = imagepng($thumbnail, $destPath);
                break;
            case 'image/gif':
                $success = imagegif($thumbnail, $destPath);
                break;
            case 'image/webp':
                if (function_exists('imagewebp')) $success = imagewebp($thumbnail, $destPath);
                break;
        }

        imagedestroy($image);
        imagedestroy($thumbnail);

        return $success ? true : @copy($sourcePath, $destPath);
    }
    catch (Throwable $e) {
        return @copy($sourcePath, $destPath);
    }
}

?>
