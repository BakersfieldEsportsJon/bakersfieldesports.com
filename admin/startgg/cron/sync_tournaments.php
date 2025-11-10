<?php
/**
 * Start.gg Tournament Sync - Cron Job
 *
 * Run this script every 30 minutes via cron:
 * */30 * * * * /usr/bin/php /path/to/public_html/admin/startgg/cron/sync_tournaments.php >> /var/log/startgg_sync.log 2>&1
 *
 * Or via wget/curl:
 * */30 * * * * wget -q -O - https://bakersfieldesports.com/admin/startgg/cron/sync_tournaments.php?key=YOUR_SECRET_KEY
 */

// Prevent direct web access without secret key
if (php_sapi_name() !== 'cli') {
    $secretKey = 'startgg_cron_' . md5('bakesports'); // Change this!

    if (!isset($_GET['key']) || $_GET['key'] !== $secretKey) {
        http_response_code(403);
        die('Access denied');
    }
}

// Set execution time limit
set_time_limit(300); // 5 minutes max

// Output start time
echo "[" . date('Y-m-d H:i:s') . "] Starting tournament sync...\n";

// Load dependencies
require_once __DIR__ . '/../../../admin/includes/db.php';
require_once __DIR__ . '/../../../includes/startgg/TournamentSync.php';

try {
    // Create sync instance
    $sync = new TournamentSync($pdo);

    // Run sync
    $result = $sync->syncUpcomingTournaments();

    if ($result['success']) {
        echo "[" . date('Y-m-d H:i:s') . "] Sync completed successfully\n";
        echo "  - Tournaments synced: " . $result['synced'] . "\n";
        echo "  - Errors: " . $result['errors'] . "\n";

        // Output log details
        if (!empty($result['log'])) {
            echo "\nSync Log:\n";
            foreach ($result['log'] as $entry) {
                echo "  [{$entry['timestamp']}] [{$entry['level']}] {$entry['message']}\n";
            }
        }

        exit(0); // Success
    } else {
        echo "[" . date('Y-m-d H:i:s') . "] Sync failed: " . $result['error'] . "\n";

        // Output error log
        if (!empty($result['log'])) {
            echo "\nError Log:\n";
            foreach ($result['log'] as $entry) {
                echo "  [{$entry['timestamp']}] [{$entry['level']}] {$entry['message']}\n";
            }
        }

        exit(1); // Error
    }

} catch (Exception $e) {
    echo "[" . date('Y-m-d H:i:s') . "] Fatal error: " . $e->getMessage() . "\n";
    error_log("StartGG Sync Error: " . $e->getMessage());
    exit(1);
}
