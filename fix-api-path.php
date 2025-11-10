<?php
/**
 * Fix API path in startgg-events.js
 */

$file = __DIR__ . '/js/startgg-events.js';
$content = file_get_contents($file);

// Fix the API_URL to use absolute path
$content = str_replace(
    "const API_URL = '../api/startgg-events.php';",
    "const API_URL = '/bakersfield/api/startgg-events.php';",
    $content
);

// Backup and save
$backup = $file . '.backup.' . date('Y-m-d-His');
copy($file, $backup);
file_put_contents($file, $content);

echo "✓ Fixed API path to use absolute URL\n";
echo "✓ Changed from: ../api/startgg-events.php\n";
echo "✓ Changed to:   /bakersfield/api/startgg-events.php\n";
echo "✓ Backup: $backup\n";
echo "\n🎉 Now hard refresh the events page (Ctrl+Shift+R) and tournaments should appear!\n";
