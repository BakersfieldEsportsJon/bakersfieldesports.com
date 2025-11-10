<?php

require_once __DIR__ . '/bootstrap.php';

try {
    $host = env('DB_HOST');
    $dbname = env('DB_NAME');
    $user = env('DB_USER');
    $password = trim(env('DB_PASSWORD'), "'");

    echo "Connecting to database:\n";
    echo "Host: $host\n";
    echo "Database: $dbname\n";
    echo "User: $user\n";
    echo "Password: " . str_repeat('*', strlen($password)) . "\n";

    // Try TCP connection with explicit settings
    echo "Trying TCP connection...\n";
    // Use IP address instead of hostname
    $dsn = "mysql:host=127.0.0.1;port=3306;dbname=$dbname";
    echo "Using DSN: $dsn\n";
    
    $db = new PDO(
        $dsn,
        $user,
        $password,
        array(
            PDO::ATTR_TIMEOUT => 3,
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION
        )
    );
    echo "Successfully connected using TCP\n";
    $db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    // Add new columns
    $alterTableSQL = "
        ALTER TABLE active_sessions
        ADD COLUMN session_id VARCHAR(255) AFTER id,
        ADD COLUMN activity_type ENUM('login', 'logout', 'page_access', 'admin_action') AFTER user_agent,
        ADD COLUMN page_url VARCHAR(255) AFTER activity_type,
        ADD COLUMN is_admin_session BOOLEAN DEFAULT FALSE AFTER page_url,
        ADD COLUMN location_data JSON AFTER is_admin_session,
        ADD INDEX idx_session_id (session_id),
        ADD INDEX idx_session_activity (session_id, activity_type),
        ADD INDEX idx_admin_session (is_admin_session)
    ";

    $db->exec($alterTableSQL);
    echo "Successfully added session activity columns\n";

} catch (PDOException $e) {
    die("Database Error: " . $e->getMessage() . "\n");
}
