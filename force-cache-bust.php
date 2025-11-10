<?php
/**
 * Force complete cache bust
 */

$file = __DIR__ . '/events/index.php';
$content = file_get_contents($file);

$timestamp = time();

// Replace any existing timestamp with new one
$content = preg_replace(
    '/tournament-modal\.js\?v=\d+/',
    "tournament-modal.js?v=$timestamp",
    $content
);

$content = preg_replace(
    '/startgg-events\.js\?v=\d+/',
    "startgg-events.js?v=$timestamp",
    $content
);

// Backup and save
$backup = $file . '.backup.' . date('Y-m-d-His');
copy($file, $backup);
file_put_contents($file, $content);

echo "✓ Updated cache busters with timestamp: $timestamp\n";
echo "✓ Backup: $backup\n";
echo "\n";
echo "Now try this:\n";
echo "1. Close ALL browser windows\n";
echo "2. Reopen browser in Incognito mode\n";
echo "3. Visit: http://localhost/bakersfield/events/index.php\n";
echo "4. Click a tournament - modal should work!\n";
