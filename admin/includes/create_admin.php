<?php
require_once __DIR__ . '/config.php';
require_once dirname(dirname(dirname(__DIR__))) . '/secure_storage/database/db_config.php';
require_once __DIR__ . '/AdminLogger.php';

// Initialize admin logger
AdminLogger::init(defined('DEBUG_MODE') ? DEBUG_MODE : false);

try {
    // Check if admin user exists
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM users WHERE role = 'admin'");
    $stmt->execute();
    $adminCount = $stmt->fetchColumn();

    if ($adminCount > 0) {
        AdminLogger::logError('setup', 'Admin user already exists');
        die("Admin user already exists");
    }

    // Get admin password from environment
    $adminPassword = $_ENV['ADMIN_INIT_PASSWORD'] ?? null;
    if (!$adminPassword) {
        AdminLogger::logError('setup', 'Missing ADMIN_INIT_PASSWORD in environment');
        die("Missing admin password configuration");
    }

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

    $passwordHash = password_hash($adminPassword, PASSWORD_DEFAULT);
    $stmt->execute([$passwordHash]);

    AdminLogger::logLoginAttempt('admin', true, 'Initial admin user created');
    echo "Admin user created successfully\n";
    echo "Username: admin\n";
    echo "Use the password from ADMIN_INIT_PASSWORD in your .env file to login\n";
    echo "Please change this password after first login\n";

} catch (PDOException $e) {
    AdminLogger::logError('setup', 'Failed to create admin user', [
        'error' => $e->getMessage(),
        'code' => $e->getCode()
    ]);
    die("Failed to create admin user: " . $e->getMessage());
}
