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

            $allowedMimeTypes = ['image/jpeg', 'image/png', 'image/gif'];
            if (!in_array($fileInfo['mime'], $allowedMimeTypes)) {
                echo json_encode(['status' => 'error', 'message' => 'Unsupported file format. Only JPEG, PNG, and GIF are supported.']);
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
            $fileName = $currentDateTime . '.' . $extension;
            $filePath = $uploadDir . $fileName;
            $thumbnailPath = $thumbnailDir . $fileName;

            // Move uploaded file and create thumbnail
            if (move_uploaded_file($file['tmp_name'], $filePath)) {
                if (createThumbnail($filePath, $thumbnailPath, $fileInfo['mime'], 200, 200)) {
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
    try {
        // Load image based on MIME type
        switch ($mimeType) {
            case 'image/jpeg':
                $image = @imagecreatefromjpeg($sourcePath);
                break;
            case 'image/png':
                $image = @imagecreatefrompng($sourcePath);
                break;
            case 'image/gif':
                $image = @imagecreatefromgif($sourcePath);
                break;
            default:
                return false;
        }

        if (!$image) {
            return false;
        }

        $originalWidth = imagesx($image);
        $originalHeight = imagesy($image);

        $thumbnail = imagecreatetruecolor($width, $height);

        // Preserve transparency for PNG and GIF
        if ($mimeType === 'image/png' || $mimeType === 'image/gif') {
            imagecolortransparent($thumbnail, imagecolorallocatealpha($thumbnail, 0, 0, 0, 127));
            imagealphablending($thumbnail, false);
            imagesavealpha($thumbnail, true);
        }

        if (!imagecopyresampled($thumbnail, $image, 0, 0, 0, 0, $width, $height, $originalWidth, $originalHeight)) {
            imagedestroy($image);
            imagedestroy($thumbnail);
            return false;
        }

        // Save the thumbnail based on MIME type
        switch ($mimeType) {
            case 'image/jpeg':
                $success = imagejpeg($thumbnail, $destPath, 90);
                break;
            case 'image/png':
                $success = imagepng($thumbnail, $destPath);
                break;
            case 'image/gif':
                $success = imagegif($thumbnail, $destPath);
                break;
            default:
                $success = false;
        }

        imagedestroy($image);
        imagedestroy($thumbnail);

        return $success;
    }
     catch (Throwable $e) {
        return false;
    }
}

?>
