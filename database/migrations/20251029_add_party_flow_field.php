<?php
/**
 * Migration: Add party_flow field to party_bookings table
 * Date: 2025-10-29
 *
 * Adds party_flow field to track whether customer wants:
 * - 'party_first': Party area first, then 2 hours game time (pizza at start)
 * - 'games_first': 2 hours game time first, then party area (pizza at 2hr mark)
 */

require_once dirname(dirname(__DIR__)) . '/admin/includes/db.php';

try {
    $pdo->beginTransaction();

    if (DB_ENGINE === 'sqlite') {
        // SQLite doesn't support ALTER TABLE ADD COLUMN with DEFAULT for existing rows easily
        // We'll add the column without default first, then update existing rows
        $pdo->exec("ALTER TABLE party_bookings ADD COLUMN party_flow VARCHAR(20)");
        $pdo->exec("UPDATE party_bookings SET party_flow = 'party_first' WHERE party_flow IS NULL");
    } else {
        // MySQL
        $pdo->exec("ALTER TABLE party_bookings ADD COLUMN party_flow VARCHAR(20) DEFAULT 'party_first' AFTER guest_count");
    }

    $pdo->commit();

    echo "✓ Migration successful: party_flow field added to party_bookings table\n";

} catch (PDOException $e) {
    $pdo->rollBack();
    echo "✗ Migration failed: " . $e->getMessage() . "\n";
    exit(1);
}
