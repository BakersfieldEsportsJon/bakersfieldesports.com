<?php
/**
 * Tournament Modal Installation Script
 * Run this script to automatically integrate the tournament modal into your events page
 *
 * Usage: php install-tournament-modal.php
 */

echo "=== Tournament Modal Installation ===\n\n";

$baseDir = __DIR__;
$publicHtml = $baseDir . '/public_html';

// Check if required files exist
$requiredFiles = [
    'api' => $publicHtml . '/api/tournament-details.php',
    'css' => $publicHtml . '/css/tournament-modal.css',
    'js' => $publicHtml . '/events/js/tournament-modal.js',
];

echo "Checking required files...\n";
foreach ($requiredFiles as $type => $file) {
    if (file_exists($file)) {
        echo "✓ {$type}: {$file}\n";
    } else {
        echo "✗ {$type}: {$file} NOT FOUND\n";
        exit(1);
    }
}

echo "\n";

// Step 1: Update events/index.php
echo "Step 1: Updating events/index.php...\n";
$eventsIndexPath = $publicHtml . '/events/index.php';

if (!file_exists($eventsIndexPath)) {
    echo "Error: {$eventsIndexPath} not found\n";
    exit(1);
}

$eventsContent = file_get_contents($eventsIndexPath);

// Add CSS link if not present
if (strpos($eventsContent, 'tournament-modal.css') === false) {
    $eventsContent = str_replace(
        '<link href="../css/custom.css" rel="stylesheet">',
        '<link href="../css/custom.css" rel="stylesheet">' . "\n" .
        '    <link href="../css/tournament-modal.css" rel="stylesheet">',
        $eventsContent
    );
    echo "  ✓ Added CSS link\n";
} else {
    echo "  - CSS link already present\n";
}

// Add modal HTML if not present
if (strpos($eventsContent, 'tournamentModal') === false) {
    $modalHtml = file_get_contents(__DIR__ . '/tournament-modal-template.html');
    $eventsContent = str_replace(
        '<script src="js/events.js"></script>',
        $modalHtml . "\n\n" . '    <script src="js/events.js"></script>' . "\n" .
        '    <script src="js/tournament-modal.js"></script>',
        $eventsContent
    );
    echo "  ✓ Added modal HTML and JS\n";
} else {
    echo "  - Modal HTML already present\n";
    // Check for JS
    if (strpos($eventsContent, 'tournament-modal.js') === false) {
        $eventsContent = str_replace(
            '<script src="js/events.js"></script>',
            '<script src="js/events.js"></script>' . "\n" .
            '    <script src="js/tournament-modal.js"></script>',
            $eventsContent
        );
        echo "  ✓ Added modal JS\n";
    } else {
        echo "  - Modal JS already present\n";
    }
}

// Backup and write
$backupPath = $eventsIndexPath . '.backup.' . date('Y-m-d-His');
copy($eventsIndexPath, $backupPath);
file_put_contents($eventsIndexPath, $eventsContent);
echo "  ✓ Saved (backup: {$backupPath})\n\n";

// Step 2: Update startgg_tournaments_display.php
echo "Step 2: Updating startgg_tournaments_display.php...\n";
$tournamentDisplayPath = $publicHtml . '/includes/startgg_tournaments_display.php';

if (!file_exists($tournamentDisplayPath)) {
    echo "Warning: {$tournamentDisplayPath} not found - skipping\n\n";
} else {
    $displayContent = file_get_contents($tournamentDisplayPath);

    // Check if already updated
    if (strpos($displayContent, 'window.tournamentModal') !== false) {
        echo "  - Already updated\n\n";
    } else {
        // Replace the View Details link with button
        $oldCode = '<a href="/tournament.php?slug=<?= urlencode($tournament[\'slug\']) ?>"
                   class="btn btn-primary tournament-btn">
                    View Details →
                </a>';

        $newCode = '<button onclick="window.tournamentModal && window.tournamentModal.open(\'<?= addslashes($tournament[\'slug\']) ?>\')"
                        class="btn btn-primary tournament-btn"
                        type="button">
                    View Details →
                </button>';

        $displayContent = str_replace($oldCode, $newCode, $displayContent);

        // Also add button styling if not present
        if (strpos($displayContent, '.tournament-btn {') === false) {
            $buttonStyles = "\n" . '.tournament-btn {
    border: none;
    cursor: pointer;
    font-family: inherit;
}

.tournament-btn.btn-primary {
    background: var(--primary-color);
    color: white;
}

';
            // Insert before closing </style>
            $displayContent = str_replace('</style>', $buttonStyles . '</style>', $displayContent);
        }

        // Backup and write
        $backupPath = $tournamentDisplayPath . '.backup.' . date('Y-m-d-His');
        copy($tournamentDisplayPath, $backupPath);
        file_put_contents($tournamentDisplayPath, $displayContent);
        echo "  ✓ Updated (backup: {$backupPath})\n\n";
    }
}

echo "=== Installation Complete! ===\n\n";
echo "Next Steps:\n";
echo "1. Test the modal by visiting your events page\n";
echo "2. Click 'View Details' on any tournament card\n";
echo "3. If using index_with_startgg.php, rename it to index.php (after backing up current index.php)\n";
echo "\nFor more information, see TOURNAMENT_MODAL_INTEGRATION.md\n";
