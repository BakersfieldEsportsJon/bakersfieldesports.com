<?php
/**
 * Database Migration Runner
 * Run this file to create OAuth and registration tables
 *
 * Usage: http://localhost/bakersfield/database/migrations/run_migration.php
 */

// Load environment variables
require_once __DIR__ . '/../../vendor/autoload.php';

use Dotenv\Dotenv;

$dotenv = Dotenv::createImmutable(__DIR__ . '/../..');
$dotenv->load();

// Database connection
$dbHost = $_ENV['DB_HOST'];
$dbName = $_ENV['DB_NAME'];
$dbUser = $_ENV['DB_USER'];
$dbPassword = $_ENV['DB_PASSWORD'];

try {
    $pdo = new PDO(
        "mysql:host=$dbHost;dbname=$dbName;charset=utf8mb4",
        $dbUser,
        $dbPassword,
        [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC
        ]
    );

    echo "<h1>Database Migration</h1>";
    echo "<p>Running migration: create_oauth_tables.sql</p>";

    // Read SQL file
    $sql = file_get_contents(__DIR__ . '/create_oauth_tables.sql');

    // Split into individual statements
    $statements = array_filter(
        array_map('trim', explode(';', $sql)),
        function($stmt) {
            return !empty($stmt) &&
                   !preg_match('/^--/', $stmt) &&
                   strlen(trim($stmt)) > 0;
        }
    );

    echo "<ul>";
    foreach ($statements as $statement) {
        try {
            $pdo->exec($statement);

            // Extract table name for display
            if (preg_match('/CREATE TABLE.*?`?(\w+)`?/i', $statement, $matches)) {
                echo "<li>✓ Created table: <strong>{$matches[1]}</strong></li>";
            } elseif (preg_match('/ALTER TABLE.*?`?(\w+)`?/i', $statement, $matches)) {
                echo "<li>✓ Altered table: <strong>{$matches[1]}</strong></li>";
            } else {
                echo "<li>✓ Executed statement</li>";
            }
        } catch (PDOException $e) {
            if (strpos($e->getMessage(), 'already exists') !== false) {
                // Table already exists, that's ok
                if (preg_match('/CREATE TABLE.*?`?(\w+)`?/i', $statement, $matches)) {
                    echo "<li>⚠ Table already exists: <strong>{$matches[1]}</strong></li>";
                }
            } else {
                echo "<li>✗ Error: " . htmlspecialchars($e->getMessage()) . "</li>";
            }
        }
    }
    echo "</ul>";

    echo "<h2>Migration Complete!</h2>";
    echo "<p><a href='/bakersfield'>← Back to Home</a></p>";

} catch (PDOException $e) {
    echo "<h1>Database Connection Error</h1>";
    echo "<p>Could not connect to database: " . htmlspecialchars($e->getMessage()) . "</p>";
    echo "<p>Check your .env database credentials.</p>";
    exit(1);
}
?>
