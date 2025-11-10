<?php
header('Content-Type: application/json');

// Include logger for error tracking
require_once '../../admin/includes/AdminLogger.php';
AdminLogger::init(defined('DEBUG_MODE') ? DEBUG_MODE : false);

$imagesDir = '../../images/events/';

try {
    // Check if directory exists
    if (!is_dir($imagesDir)) {
        throw new Exception('Images directory not found');
    }
    
    // Get all files from directory
    $files = scandir($imagesDir);
    $images = [];
    $processedFiles = [];
    
    // First pass: collect all WebP images
    foreach ($files as $file) {
        if ($file === '.' || $file === '..') continue;
        
        $ext = strtolower(pathinfo($file, PATHINFO_EXTENSION));
        if ($ext === 'webp') {
            $baseName = pathinfo($file, PATHINFO_FILENAME);
            $images[] = [
                'path' => '../../images/events/' . $file
            ];
            $processedFiles[] = $baseName;
        }
    }
    
    // Second pass: add non-WebP images only if no WebP version exists
    foreach ($files as $file) {
        if ($file === '.' || $file === '..') continue;
        
        $ext = strtolower(pathinfo($file, PATHINFO_EXTENSION));
        $baseName = pathinfo($file, PATHINFO_FILENAME);
        
        // If this file hasn't been processed and it's an image
        if (!in_array($baseName, $processedFiles) && in_array($ext, ['jpg', 'jpeg', 'png', 'gif'])) {
            $images[] = [
                'path' => '../../images/events/' . $file
            ];
        }
    }
    
    echo json_encode([
        'success' => true,
        'images' => $images
    ]);
    
} catch (Exception $e) {
    AdminLogger::logError('events', 'Error in get_event_images: ' . $e->getMessage(), [
        'error' => $e->getMessage(),
        'file' => $e->getFile(),
        'line' => $e->getLine()
    ]);
    
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage()
    ]);
}
