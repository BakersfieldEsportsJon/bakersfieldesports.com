<?php
/**
 * Add cache buster to JavaScript files
 */

$file = __DIR__ . '/events/index.php';
$content = file_get_contents($file);

// Add timestamp to script tags to bust cache
$content = str_replace(
    '<script src="../js/startgg-events.js"></script>',
    '<script src="../js/startgg-events.js?v=' . time() . '"></script>',
    $content
);

$content = str_replace(
    '<script src="js/tournament-modal.js"></script>',
    '<script src="js/tournament-modal.js?v=' . time() . '"></script>',
    $content
);

// Backup and save
$backup = $file . '.backup.' . date('Y-m-d-His');
copy($file, $backup);
file_put_contents($file, $content);

echo "✓ Added cache busters to script tags\n";
echo "✓ This forces browser to reload JavaScript files\n";
echo "✓ Backup: $backup\n";
echo "\nNow refresh the events page - tournaments should appear!\n";
