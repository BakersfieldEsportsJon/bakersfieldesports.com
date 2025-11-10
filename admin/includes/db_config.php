<?php
// Load environment configuration
require_once dirname(dirname(__DIR__)) . '/bootstrap.php';

// Get database engine from environment
define('DB_ENGINE', env('DB_ENGINE', 'mysql'));

if (DB_ENGINE === 'sqlite') {
    // SQLite configuration
    define('DB_PATH', env('DB_PATH', dirname(__DIR__) . '/includes/bakersfield_esports.db'));
    
    // Validate SQLite configuration
    if (empty(DB_PATH)) {
        if (class_exists('AdminLogger')) {
            AdminLogger::logError('config', 'Missing required SQLite configuration', [
                'missing_fields' => ['DB_PATH']
            ]);
        }
        throw new RuntimeException('SQLite database path is not configured. Check your .env file.');
    }
    
    // Ensure SQLite directory exists and is writable
    $dbDir = dirname(DB_PATH);
    if (!file_exists($dbDir)) {
        mkdir($dbDir, 0755, true);
    }
    
    // Define connection string for SQLite
    define('DB_DSN', 'sqlite:' . DB_PATH);
    define('DB_OPTIONS', [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_EMULATE_PREPARES => false
    ]);
} else {
    // MySQL configuration
    define('DB_HOST', env('DB_HOST', 'localhost'));
    define('DB_NAME', env('DB_NAME'));
    define('DB_USER', env('DB_USER'));
    define('DB_PASS', env('DB_PASSWORD')); // Note: using DB_PASSWORD from .env
    
    // Validate MySQL configuration
    if (empty(DB_NAME) || empty(DB_USER) || empty(DB_PASS)) {
        if (class_exists('AdminLogger')) {
            AdminLogger::logError('config', 'Missing required MySQL configuration', [
                'missing_fields' => array_filter([
                    empty(DB_NAME) ? 'DB_NAME' : null,
                    empty(DB_USER) ? 'DB_USER' : null,
                    empty(DB_PASS) ? 'DB_PASSWORD' : null
                ])
            ]);
        }
        throw new RuntimeException('MySQL configuration is incomplete. Check your .env file.');
    }
    
    // Define connection string for MySQL
    define('DB_DSN', "mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=utf8mb4");
    define('DB_OPTIONS', [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_EMULATE_PREPARES => false
    ]);
}
