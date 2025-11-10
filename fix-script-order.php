<?php
/**
 * Fix script order - move tournament-modal.js after modal HTML
 */

$file = __DIR__ . '/events/index.php';
$content = file_get_contents($file);

// Remove the script tag from its current location
$content = str_replace(
    '    <script src="js/tournament-modal.js"></script>',
    '',
    $content
);

// Add it right before </body>
$content = str_replace(
    '</body>',
    '    <script src="js/tournament-modal.js"></script>' . "\n" . '</body>',
    $content
);

// Backup and save
$backup = $file . '.backup.' . date('Y-m-d-His');
copy($file, $backup);
file_put_contents($file, $content);

echo "✓ Fixed script order\n";
echo "✓ tournament-modal.js now loads AFTER modal HTML\n";
echo "✓ Backup: $backup\n";
echo "\nRefresh your browser and try again!\n";
