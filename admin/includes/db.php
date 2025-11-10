<?php
require_once __DIR__ . '/config.php';

try {
    if (DB_ENGINE === 'mysql') {
        $pdo = new PDO(
            DB_DSN,
            DB_USER,
            DB_PASS,
            DB_OPTIONS
        );
    } else {
        // Ensure SQLite directory exists
        $db_dir = dirname(DB_PATH);
        if (!file_exists($db_dir)) {
            mkdir($db_dir, 0755, true);
        }
        
        $pdo = new PDO(DB_DSN, null, null, DB_OPTIONS);
        
        // Enable foreign keys for SQLite
        $pdo->exec('PRAGMA foreign_keys = ON;');
    }

    // Make PDO object available globally
    global $pdo;
    
} catch (PDOException $e) {
    if (class_exists('AdminLogger')) {
        AdminLogger::logError('database', 'Database connection failed', [
            'error' => $e->getMessage(),
            'engine' => DB_ENGINE
        ]);
    }
    error_log("Database connection failed: " . $e->getMessage());
    die('Database connection error');
}

// Secure query helper function
function executeQuery($sql, $params = []) {
    global $pdo;
    try {
        $stmt = $pdo->prepare($sql);
        $stmt->execute($params);
        return $stmt;
    } catch (PDOException $e) {
        if (class_exists('AdminLogger')) {
            AdminLogger::logError('database', 'Query execution failed', [
                'error' => $e->getMessage(),
                'query' => $sql,
                'params' => $params
            ]);
        }
        error_log("SQL Error: " . $e->getMessage() . " Query: " . $sql);
        return false;
    }
}
