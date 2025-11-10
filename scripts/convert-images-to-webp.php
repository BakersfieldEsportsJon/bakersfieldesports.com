#!/usr/bin/env php
<?php
/**
 * Image Optimization Script
 * Converts JPG/PNG images to WebP format for better performance
 *
 * Usage:
 *   php convert-images-to-webp.php [directory] [--quality=85] [--delete-originals]
 *
 * Examples:
 *   php convert-images-to-webp.php ../images
 *   php convert-images-to-webp.php ../images --quality=90
 *   php convert-images-to-webp.php ../images --quality=85 --delete-originals
 *
 * Requirements:
 *   - PHP GD extension with WebP support
 *   - Write permissions in target directory
 */

// Check if running from CLI
if (php_sapi_name() !== 'cli') {
    die("This script must be run from the command line.\n");
}

// Check GD extension
if (!extension_loaded('gd')) {
    die("Error: GD extension is not loaded. Install php-gd to use this script.\n");
}

// Check WebP support
if (!function_exists('imagewebp')) {
    die("Error: Your PHP GD installation does not support WebP. Please upgrade PHP or rebuild GD with WebP support.\n");
}

// Configuration
$config = [
    'quality' => 85,
    'delete_originals' => false,
    'target_dir' => null,
    'extensions' => ['jpg', 'jpeg', 'png'],
    'max_width' => 2560, // Maximum width before resizing
    'max_height' => 2560, // Maximum height before resizing
];

// Parse command line arguments
$args = array_slice($argv, 1);
foreach ($args as $arg) {
    if (strpos($arg, '--quality=') === 0) {
        $config['quality'] = (int) substr($arg, 10);
    } elseif ($arg === '--delete-originals') {
        $config['delete_originals'] = true;
    } elseif (!$config['target_dir'] && !str_starts_with($arg, '--')) {
        $config['target_dir'] = $arg;
    }
}

// Validate target directory
if (!$config['target_dir']) {
    echo "Usage: php convert-images-to-webp.php [directory] [--quality=85] [--delete-originals]\n";
    exit(1);
}

$target_dir = realpath($config['target_dir']);
if (!$target_dir || !is_dir($target_dir)) {
    die("Error: Directory '{$config['target_dir']}' does not exist.\n");
}

echo "WebP Image Conversion Tool\n";
echo "===========================\n";
echo "Target Directory: $target_dir\n";
echo "Quality: {$config['quality']}\n";
echo "Delete Originals: " . ($config['delete_originals'] ? 'Yes' : 'No') . "\n";
echo "Max Dimensions: {$config['max_width']}x{$config['max_height']}\n";
echo "\n";

// Statistics
$stats = [
    'total_files' => 0,
    'converted' => 0,
    'skipped' => 0,
    'errors' => 0,
    'bytes_saved' => 0,
];

/**
 * Convert image to WebP
 */
function convertToWebP($source_path, $quality = 85, $max_width = 2560, $max_height = 2560) {
    $path_info = pathinfo($source_path);
    $webp_path = $path_info['dirname'] . '/' . $path_info['filename'] . '.webp';

    // Skip if WebP already exists and is newer
    if (file_exists($webp_path) && filemtime($webp_path) >= filemtime($source_path)) {
        return ['status' => 'skipped', 'reason' => 'WebP exists and is up to date'];
    }

    // Get image info
    $image_info = @getimagesize($source_path);
    if ($image_info === false) {
        return ['status' => 'error', 'reason' => 'Cannot read image'];
    }

    // Create image resource based on type
    switch ($image_info[2]) {
        case IMAGETYPE_JPEG:
            $image = @imagecreatefromjpeg($source_path);
            break;
        case IMAGETYPE_PNG:
            $image = @imagecreatefrompng($source_path);
            break;
        default:
            return ['status' => 'skipped', 'reason' => 'Unsupported format'];
    }

    if ($image === false) {
        return ['status' => 'error', 'reason' => 'Failed to create image resource'];
    }

    // Preserve transparency for PNG
    if ($image_info[2] === IMAGETYPE_PNG) {
        imagealphablending($image, false);
        imagesavealpha($image, true);
    }

    // Resize if needed
    $orig_width = imagesx($image);
    $orig_height = imagesy($image);

    if ($orig_width > $max_width || $orig_height > $max_height) {
        // Calculate new dimensions maintaining aspect ratio
        $ratio = min($max_width / $orig_width, $max_height / $orig_height);
        $new_width = (int) ($orig_width * $ratio);
        $new_height = (int) ($orig_height * $ratio);

        // Create resized image
        $resized = imagecreatetruecolor($new_width, $new_height);

        // Preserve transparency for PNG
        if ($image_info[2] === IMAGETYPE_PNG) {
            imagealphablending($resized, false);
            imagesavealpha($resized, true);
            $transparent = imagecolorallocatealpha($resized, 0, 0, 0, 127);
            imagefill($resized, 0, 0, $transparent);
        }

        imagecopyresampled($resized, $image, 0, 0, 0, 0, $new_width, $new_height, $orig_width, $orig_height);
        imagedestroy($image);
        $image = $resized;
    }

    // Convert to WebP
    $result = imagewebp($image, $webp_path, $quality);
    imagedestroy($image);

    if (!$result) {
        return ['status' => 'error', 'reason' => 'Failed to save WebP'];
    }

    // Calculate size savings
    $original_size = filesize($source_path);
    $webp_size = filesize($webp_path);
    $bytes_saved = $original_size - $webp_size;
    $percent_saved = $original_size > 0 ? round(($bytes_saved / $original_size) * 100, 1) : 0;

    return [
        'status' => 'success',
        'webp_path' => $webp_path,
        'original_size' => $original_size,
        'webp_size' => $webp_size,
        'bytes_saved' => $bytes_saved,
        'percent_saved' => $percent_saved
    ];
}

/**
 * Format bytes to human readable
 */
function formatBytes($bytes) {
    if ($bytes >= 1073741824) {
        return number_format($bytes / 1073741824, 2) . ' GB';
    } elseif ($bytes >= 1048576) {
        return number_format($bytes / 1048576, 2) . ' MB';
    } elseif ($bytes >= 1024) {
        return number_format($bytes / 1024, 2) . ' KB';
    } else {
        return $bytes . ' bytes';
    }
}

/**
 * Recursively scan directory for images
 */
function scanDirectory($dir, &$files, $extensions) {
    $items = scandir($dir);
    foreach ($items as $item) {
        if ($item === '.' || $item === '..') {
            continue;
        }

        $path = $dir . DIRECTORY_SEPARATOR . $item;

        if (is_dir($path)) {
            scanDirectory($path, $files, $extensions);
        } elseif (is_file($path)) {
            $ext = strtolower(pathinfo($path, PATHINFO_EXTENSION));
            if (in_array($ext, $extensions, true)) {
                $files[] = $path;
            }
        }
    }
}

// Scan for images
echo "Scanning for images...\n";
$image_files = [];
scanDirectory($target_dir, $image_files, $config['extensions']);
$stats['total_files'] = count($image_files);

echo "Found {$stats['total_files']} images to process.\n\n";

if ($stats['total_files'] === 0) {
    echo "No images found. Exiting.\n";
    exit(0);
}

// Process each image
$start_time = microtime(true);

foreach ($image_files as $index => $file_path) {
    $file_num = $index + 1;
    $relative_path = str_replace($target_dir . DIRECTORY_SEPARATOR, '', $file_path);

    echo "[{$file_num}/{$stats['total_files']}] Processing: $relative_path ... ";

    $result = convertToWebP(
        $file_path,
        $config['quality'],
        $config['max_width'],
        $config['max_height']
    );

    if ($result['status'] === 'success') {
        $stats['converted']++;
        $stats['bytes_saved'] += $result['bytes_saved'];
        echo "✓ Saved {$result['percent_saved']}% (" . formatBytes($result['bytes_saved']) . ")\n";

        // Delete original if requested
        if ($config['delete_originals']) {
            if (@unlink($file_path)) {
                echo "  → Original deleted\n";
            } else {
                echo "  → Warning: Could not delete original\n";
            }
        }
    } elseif ($result['status'] === 'skipped') {
        $stats['skipped']++;
        echo "⊘ Skipped ({$result['reason']})\n";
    } else {
        $stats['errors']++;
        echo "✗ Error: {$result['reason']}\n";
    }
}

$end_time = microtime(true);
$duration = round($end_time - $start_time, 2);

// Print summary
echo "\n";
echo "Conversion Complete\n";
echo "===================\n";
echo "Total files: {$stats['total_files']}\n";
echo "Converted: {$stats['converted']}\n";
echo "Skipped: {$stats['skipped']}\n";
echo "Errors: {$stats['errors']}\n";
echo "Total space saved: " . formatBytes($stats['bytes_saved']) . "\n";
echo "Time elapsed: {$duration} seconds\n";

if ($stats['converted'] > 0) {
    $avg_savings = $stats['bytes_saved'] / $stats['converted'];
    echo "Average savings per image: " . formatBytes($avg_savings) . "\n";
}

echo "\nDone!\n";
exit(0);
