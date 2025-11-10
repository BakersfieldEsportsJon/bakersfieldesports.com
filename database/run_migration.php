<?php
// Simple migration runner wrapper for party_bookings table
require_once __DIR__ . '/../admin/includes/db.php';

date_default_timezone_set('America/Los_Angeles');

try {
    // If table already exists, exit
    if (DB_ENGINE === 'mysql') {
        $stmt = $pdo->query("SHOW TABLES LIKE 'party_bookings'");
        if ($stmt && $stmt->rowCount() > 0) {
            echo "party_bookings already exists.\n";
            return;
        }
    } else {
        $stmt = $pdo->query("SELECT name FROM sqlite_master WHERE type='table' AND name='party_bookings'");
        if ($stmt && $stmt->fetch()) {
            echo "party_bookings already exists.\n";
            return;
        }
    }

    // Delegate to the migration file
    require_once __DIR__ . '/migrations/20251028_create_party_bookings_table.php';
} catch (Throwable $e) {
    error_log('Migration error: ' . $e->getMessage());
    http_response_code(500);
    echo "Migration failed\n";
}