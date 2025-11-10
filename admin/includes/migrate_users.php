<?php
require_once 'config.php';

try {
    // First, check if we need to back up existing users
    $hasUsers = $pdo->query("SELECT COUNT(*) FROM users")->fetchColumn() > 0;
    
    if ($hasUsers) {
        error_log("Backing up existing users...");
        // Create backup of existing users
        $pdo->exec("CREATE TABLE IF NOT EXISTS users_backup LIKE users");
        $pdo->exec("INSERT INTO users_backup SELECT * FROM users");
        error_log("Users backed up successfully");
        
        // Drop and recreate users table with new schema
        $pdo->exec("DROP TABLE users");
        error_log("Old users table dropped");
        
        // Create new users table
        $pdo->exec("CREATE TABLE users (
            id INT AUTO_INCREMENT PRIMARY KEY,
            username VARCHAR(50) NOT NULL UNIQUE,
            password VARCHAR(255) NOT NULL,
            email VARCHAR(255) NOT NULL UNIQUE,
            status ENUM('pending', 'active', 'disabled') DEFAULT 'pending',
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
        )");
        error_log("New users table created");
        
        // Migrate existing users with placeholder email
        $pdo->exec("INSERT INTO users (username, password, email, status)
                   SELECT 
                       username,
                       password,
                       CONCAT(username, '@bakersfieldesports.com') as email,
                       'active' as status
                   FROM users_backup");
        error_log("Users migrated successfully");
    }
    
    echo "Migration completed successfully\n";
} catch (PDOException $e) {
    error_log("Migration error: " . $e->getMessage());
    echo "Migration failed: " . $e->getMessage() . "\n";
}
