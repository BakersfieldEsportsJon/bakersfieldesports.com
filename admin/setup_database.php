<?php
require_once __DIR__ . '/includes/config.php';
require_once __DIR__ . '/includes/AdminLogger.php';
require_once __DIR__ . '/includes/db.php';

try {
    // Check if table exists
    $tableExists = $pdo->query("SHOW TABLES LIKE 'active_sessions'")->rowCount() > 0;
    
    if (!$tableExists) {
        $sql = "CREATE TABLE active_sessions (
        id INT AUTO_INCREMENT PRIMARY KEY,
        user_id BIGINT UNSIGNED NOT NULL,
        username VARCHAR(255) NOT NULL,
        ip_address VARCHAR(45) NOT NULL,
        user_agent TEXT NOT NULL,
        last_activity TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4";

        $pdo->exec($sql);
        echo "Table 'active_sessions' created successfully.\n";
        
        AdminLogger::logInfo('setup', 'Active sessions table created');
    } else {
        echo "Table 'active_sessions' already exists.\n";
        AdminLogger::logInfo('setup', 'Active sessions table exists, skipping creation');
    }
} catch (PDOException $e) {
    AdminLogger::logError('setup', 'Database setup failed', [
        'error' => $e->getMessage(),
        'code' => $e->getCode(),
        'php_version' => PHP_VERSION,
        'pdo_drivers' => implode(', ', PDO::getAvailableDrivers())
    ]);
    die("Setup failed: " . $e->getMessage() . "\nPHP Version: " . PHP_VERSION);
}
