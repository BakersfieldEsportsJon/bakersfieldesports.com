<?php
/**
 * Simple Database Migration Runner (No Composer Required)
 * Run this via web browser: http://localhost/bakersfield/database/migrations/run_migration_simple.php
 */

// Database connection - hardcoded for simplicity
$dbHost = 'localhost';
$dbName = 'bakerwgx_bec_gallery';
$dbUser = 'bakerwgx_bec';
$dbPassword = 'BEC012020!?!';

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
    $successCount = 0;
    $errorCount = 0;

    foreach ($statements as $statement) {
        try {
            $pdo->exec($statement);

            // Extract table name for display
            if (preg_match('/CREATE TABLE.*?`?(\w+)`?/i', $statement, $matches)) {
                echo "<li style='color: green;'>✓ Created table: <strong>{$matches[1]}</strong></li>";
                $successCount++;
            } elseif (preg_match('/ALTER TABLE.*?`?(\w+)`?/i', $statement, $matches)) {
                echo "<li style='color: green;'>✓ Altered table: <strong>{$matches[1]}</strong></li>";
                $successCount++;
            } else {
                echo "<li style='color: green;'>✓ Executed statement</li>";
                $successCount++;
            }
        } catch (PDOException $e) {
            if (strpos($e->getMessage(), 'already exists') !== false) {
                // Table already exists, that's ok
                if (preg_match('/CREATE TABLE.*?`?(\w+)`?/i', $statement, $matches)) {
                    echo "<li style='color: orange;'>⚠ Table already exists: <strong>{$matches[1]}</strong></li>";
                }
            } else {
                echo "<li style='color: red;'>✗ Error: " . htmlspecialchars($e->getMessage()) . "</li>";
                $errorCount++;
            }
        }
    }
    echo "</ul>";

    echo "<h2 style='color: green;'>Migration Complete!</h2>";
    echo "<p><strong>Success:</strong> $successCount statements executed</p>";
    if ($errorCount > 0) {
        echo "<p><strong>Errors:</strong> $errorCount</p>";
    }
    echo "<p><a href='/bakersfield/events/'>← Go to Events Page</a></p>";

} catch (PDOException $e) {
    echo "<h1 style='color: red;'>Database Connection Error</h1>";
    echo "<p>Could not connect to database: " . htmlspecialchars($e->getMessage()) . "</p>";
    echo "<p>Check your database credentials.</p>";
    exit(1);
}
?>
