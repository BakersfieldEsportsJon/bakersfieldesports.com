<?php
require_once __DIR__ . '/includes/config.php';
require_once __DIR__ . '/includes/AdminLogger.php';

// Initialize admin logger
AdminLogger::init(defined('DEBUG_MODE') ? DEBUG_MODE : false);

try {
    // Get database connection
    require_once __DIR__ . '/includes/db.php';
    
    // Check if users table exists
    $tableExists = $pdo->query("SHOW TABLES LIKE 'users'")->rowCount() > 0;
    
    if (!$tableExists) {
        // Create users table
        $pdo->exec("
            CREATE TABLE users (
                id INT AUTO_INCREMENT PRIMARY KEY,
                email VARCHAR(255) NOT NULL UNIQUE,
                full_name VARCHAR(255) NOT NULL,
                username VARCHAR(50) NOT NULL UNIQUE,
                password_hash VARCHAR(255) NOT NULL,
                role ENUM('admin', 'staff') NOT NULL DEFAULT 'staff',
                last_login DATETIME,
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
        ");
        
        echo "Users table created successfully\n";
        
        // Create admin user
        $stmt = $pdo->prepare("
            INSERT INTO users (
                email,
                full_name,
                username,
                password_hash,
                role
            ) VALUES (
                'admin@bakersfieldesports.com',
                'System Administrator',
                'admin',
                ?,
                'admin'
            )
        ");

        $adminPassword = $_ENV['ADMIN_INIT_PASSWORD'] ?? 'TEMP_INIT_PASSWORD_#RotateThis123';
        $passwordHash = password_hash($adminPassword, PASSWORD_DEFAULT);
        $stmt->execute([$passwordHash]);

        echo "Admin user created successfully\n";
        echo "Username: admin\n";
        echo "Password: " . $adminPassword . "\n";
        
        AdminLogger::logLoginAttempt('admin', true, 'Initial admin user created');
    } else {
        echo "Users table already exists\n";
        
        // Check if admin user exists
        $stmt = $pdo->prepare("SELECT COUNT(*) FROM users WHERE username = 'admin'");
        $stmt->execute();
        $adminExists = $stmt->fetchColumn() > 0;
        
        if (!$adminExists) {
            // Create admin user
            $stmt = $pdo->prepare("
                INSERT INTO users (
                    email,
                    full_name,
                    username,
                    password_hash,
                    role
                ) VALUES (
                    'admin@bakersfieldesports.com',
                    'System Administrator',
                    'admin',
                    ?,
                    'admin'
                )
            ");

            $adminPassword = $_ENV['ADMIN_INIT_PASSWORD'] ?? 'TEMP_INIT_PASSWORD_#RotateThis123';
            $passwordHash = password_hash($adminPassword, PASSWORD_DEFAULT);
            $stmt->execute([$passwordHash]);

            echo "Admin user created successfully\n";
            echo "Username: admin\n";
            echo "Password: " . $adminPassword . "\n";
            
            AdminLogger::logLoginAttempt('admin', true, 'Initial admin user created');
        } else {
            echo "Admin user already exists\n";
        }
    }

} catch (PDOException $e) {
    AdminLogger::logError('setup', 'Database setup failed', [
        'error' => $e->getMessage(),
        'code' => $e->getCode()
    ]);
    die("Setup failed: " . $e->getMessage());
}
