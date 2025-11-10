<?php
/**
 * Update modal to use simple API endpoint
 */

$file = __DIR__ . '/events/js/tournament-modal.js';
$content = file_get_contents($file);

// Update API endpoint
$content = str_replace(
    "const response = await fetch(`/api/tournament-details.php?slug=",
    "const response = await fetch(`/bakersfield/api/tournament-details-simple.php?slug=",
    $content
);

// Backup and save
$backup = $file . '.backup.' . date('Y-m-d-His');
copy($file, $backup);
file_put_contents($file, $content);

echo "✓ Updated modal to use tournament-details-simple.php\n";
echo "✓ Backup: $backup\n";
echo "\n🎉 Refresh the page and try clicking a tournament!\n";
