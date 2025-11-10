<?php
header('Content-Type: application/json');

// Include logger for error tracking
require_once '../../admin/includes/AdminLogger.php';
AdminLogger::init(defined('DEBUG_MODE') ? DEBUG_MODE : false);

try {
    if (!isset($_FILES['image'])) {
        throw new Exception('No image file uploaded');
    }

    $file = $_FILES['image'];
    
    // Validate file type
    $fileType = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
    $allowedTypes = ['jpg', 'jpeg', 'png', 'gif'];
    if (!in_array($fileType, $allowedTypes)) {
        throw new Exception('Invalid file type. Allowed types: ' . implode(', ', $allowedTypes));
    }
    
    // Verify it's actually an image
    if (!getimagesize($file['tmp_name'])) {
        throw new Exception('Invalid image file');
    }

    // Add timestamp to filename
    $fileName = time() . '-' . basename($file['name']);
    $targetDir = '../../images/events/';
    $targetPath = $targetDir . $fileName;

    // Move uploaded file
    if (move_uploaded_file($file['tmp_name'], $targetPath)) {
        echo json_encode([
            'success' => true,
            'data' => [
                'original' => [
                    'path' => '../../images/events/' . $fileName
                ]
            ]
        ]);
    } else {
        throw new Exception('Failed to upload image');
    }

} catch (Exception $e) {
    AdminLogger::logError('events', 'Error in upload_event_image: ' . $e->getMessage(), [
        'error' => $e->getMessage(),
        'file' => $e->getFile(),
        'line' => $e->getLine()
    ]);
    
    // Clean up any uploaded files if there was an error
    if (isset($targetPath) && file_exists($targetPath)) {
        unlink($targetPath);
    }
    
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage()
    ]);
}
