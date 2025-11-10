<?php
/**
 * Add Tournament Modal to Live Events Page
 */

$eventsFile = __DIR__ . '/events/index.php';
$content = file_get_contents($eventsFile);

// 1. Add CSS link if not present
if (strpos($content, 'tournament-modal.css') === false) {
    $content = str_replace(
        '<link href="../css/startgg-events.css" rel="stylesheet">',
        '<link href="../css/startgg-events.css" rel="stylesheet">' . "\n" .
        '    <link href="../css/tournament-modal.css" rel="stylesheet">',
        $content
    );
    echo "✓ Added CSS link\n";
} else {
    echo "- CSS already present\n";
}

// 2. Add modal JS if not present
if (strpos($content, 'tournament-modal.js') === false) {
    $content = str_replace(
        '<script src="../js/startgg-events.js"></script>',
        '<script src="../js/startgg-events.js"></script>' . "\n" .
        '    <script src="js/tournament-modal.js"></script>',
        $content
    );
    echo "✓ Added JS link\n";
} else {
    echo "- JS already present\n";
}

// 3. Add modal HTML before closing </body> if not present
if (strpos($content, 'tournamentModal') === false) {
    $modalHtml = file_get_contents(__DIR__ . '/tournament-modal-template.html');
    $content = str_replace('</body>', $modalHtml . "\n</body>", $content);
    echo "✓ Added modal HTML\n";
} else {
    echo "- Modal HTML already present\n";
}

// Backup and save
$backup = $eventsFile . '.backup.' . date('Y-m-d-His');
copy($eventsFile, $backup);
file_put_contents($eventsFile, $content);

echo "\nDone! Backup saved to: $backup\n";
echo "\nNow visit: http://localhost/bakersfield/events/index.php\n";
