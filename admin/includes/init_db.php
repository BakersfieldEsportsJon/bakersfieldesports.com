<?php
require_once __DIR__ . '/db.php';

try {
    // Check existing tables
    // Database-agnostic table existence check
    $existingTables = $_ENV['DB_ENGINE'] === 'mysql' 
        ? $pdo->query("SHOW TABLES")->fetchAll(PDO::FETCH_COLUMN)
        : $pdo->query("SELECT name FROM sqlite_master WHERE type='table'")->fetchAll(PDO::FETCH_COLUMN);
    
    if (!empty($existingTables)) {
        die("Database already initialized - aborting");
    }

    // Get appropriate schema based on DB engine
    $schemaFile = ($_ENV['DB_ENGINE'] === 'mysql') 
        ? __DIR__ . '/mysql_schema.sql'
        : __DIR__ . '/sqlite_schema.sql';

    $schema = file_get_contents($schemaFile);
    
    // Execute schema in transactions
    $pdo->beginTransaction();
    $pdo->exec($schema);
    $pdo->commit();
    
    echo "Database initialized successfully\n";
    
    // Create initial admin user
    require_once __DIR__ . '/create_admin.php';
    
} catch (PDOException $e) {
        try {
            if ($pdo->inTransaction()) {
                $pdo->rollBack();
            }
        } catch (PDOException $e) {
            error_log("Rollback error: " . $e->getMessage());
        }
    error_log("Init error: " . $e->getMessage());
    die("Database initialization failed");
}
