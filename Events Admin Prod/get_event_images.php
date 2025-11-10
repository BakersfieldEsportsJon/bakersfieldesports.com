<?php
header('Content-Type: application/json');

$imagesDir = '../../images/events/';
$images = [];

// Check if directory exists
if (is_dir($imagesDir)) {
    // Get all files from directory
    $files = scandir($imagesDir);
    
    // Filter for image files
    foreach ($files as $file) {
        $ext = strtolower(pathinfo($file, PATHINFO_EXTENSION));
        if (in_array($ext, ['jpg', 'jpeg', 'png', 'gif', 'webp']) && $file !== '.' && $file !== '..') {
            $images[] = $file;
        }
    }
    
    echo json_encode($images);
} else {
    http_response_code(500);
    echo json_encode(['error' => 'Images directory not found']);
}
?>
