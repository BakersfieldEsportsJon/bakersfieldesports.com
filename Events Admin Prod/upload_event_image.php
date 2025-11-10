<?php
// CRITICAL: Authentication required
require_once '../admin/includes/config.php';
require_once '../admin/includes/auth.php';

header('Content-Type: application/json');

// Verify user is authenticated
if (!isset($_SESSION['user_id'])) {
    http_response_code(401);
    echo json_encode(['error' => 'Unauthorized - Please log in']);
    exit;
}

if (!isset($_FILES['image'])) {
    http_response_code(400);
    echo json_encode(['error' => 'No image file uploaded']);
    exit;
}

$file = $_FILES['image'];

// File size validation (5MB max)
$maxSize = 5 * 1024 * 1024; // 5MB
if ($file['size'] > $maxSize) {
    http_response_code(400);
    echo json_encode(['error' => 'File size exceeds 5MB limit']);
    exit;
}

// Validate file type using MIME type
$allowedTypes = ['image/jpeg', 'image/jpg', 'image/png', 'image/gif', 'image/webp'];
$finfo = finfo_open(FILEINFO_MIME_TYPE);
$mimeType = finfo_file($finfo, $file['tmp_name']);
finfo_close($finfo);

if (!in_array($mimeType, $allowedTypes)) {
    http_response_code(400);
    echo json_encode(['error' => 'Invalid file type. Only JPG, PNG, GIF, and WebP images are allowed']);
    exit;
}

// Validate file extension
$allowedExtensions = ['jpg', 'jpeg', 'png', 'gif', 'webp'];
$fileExtension = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));

if (!in_array($fileExtension, $allowedExtensions)) {
    http_response_code(400);
    echo json_encode(['error' => 'Invalid file extension']);
    exit;
}

// Additional validation with getimagesize for images
$imageInfo = @getimagesize($file['tmp_name']);
if ($imageInfo === false) {
    http_response_code(400);
    echo json_encode(['error' => 'File is not a valid image']);
    exit;
}

// Generate secure unique filename
$uniqueId = bin2hex(random_bytes(8));
$fileName = time() . '-' . $uniqueId . '.' . $fileExtension;
$targetDir = '../../images/events/';
$targetPath = $targetDir . $fileName;

// Create directory if it doesn't exist with secure permissions
if (!file_exists($targetDir)) {
    mkdir($targetDir, 0755, true);
}

// Move uploaded file
if (move_uploaded_file($file['tmp_name'], $targetPath)) {
    // Set secure file permissions
    chmod($targetPath, 0644);

    echo json_encode([
        'message' => 'Image uploaded successfully',
        'imagePath' => '../../images/events/' . $fileName
    ]);
} else {
    http_response_code(500);
    echo json_encode(['error' => 'Failed to upload image']);
}
