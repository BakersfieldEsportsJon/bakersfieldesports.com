<?php
// Parse .env file manually
function parseEnvFile($path) {
    if (!file_exists($path)) {
        return [];
    }
    $lines = file($path, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    $env = [];
    foreach ($lines as $line) {
        if (strpos($line, '#') === 0) continue;
        list($name, $value) = explode('=', $line, 2);
        $env[trim($name)] = trim($value);
    }
    return $env;
}

// Load environment variables from .env
$env = parseEnvFile(__DIR__ . '/../.env');

// Database configuration
define('DB_HOST', $env['DB_HOST'] ?? 'localhost');
define('DB_USER', $env['DB_USER'] ?? '');
define('DB_PASS', $env['DB_PASS'] ?? '');
define('DB_NAME', $env['DB_NAME'] ?? '');

// Email configuration
define('ADMIN_EMAIL', $env['ADMIN_EMAIL'] ?? 'admin@example.com');
define('SITE_NAME', $env['SITE_NAME'] ?? 'Bakersfield Esports Center');

// Verify required credentials are set
if (empty(DB_USER) || empty(DB_PASS) || empty(DB_NAME)) {
    error_log('Database credentials not configured');
    error_log('Current DB_HOST: ' . DB_HOST);
    error_log('Current DB_USER: ' . DB_USER);
    error_log('Current DB_NAME: ' . DB_NAME);
    die('Database configuration error. Please check .env file.');
}

// Test database connection
try {
    $test_pdo = new PDO("mysql:host=" . DB_HOST . ";dbname=" . DB_NAME, DB_USER, DB_PASS);
    $test_pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    error_log('Database connection test successful');
} catch (PDOException $e) {
    error_log('Database connection test failed: ' . $e->getMessage());
    error_log('Connection details:');
    error_log('Host: ' . DB_HOST);
    error_log('User: ' . DB_USER);
    error_log('Database: ' . DB_NAME);
    die('Database connection failed. Please check the error log for details.');
}
