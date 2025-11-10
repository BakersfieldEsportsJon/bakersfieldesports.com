<?php
echo "Current working directory: " . getcwd() . "\n";
echo ".env file exists: " . (file_exists(__DIR__ . '/../.env') ? 'Yes' : 'No') . "\n\n";

echo "Raw .env contents:\n";
echo file_get_contents(__DIR__ . '/../.env') . "\n\n";

// Now load bootstrap to get env variables
require_once __DIR__ . '/../bootstrap.php';

echo "Parsed environment variables:\n";
$env = parse_ini_file(__DIR__ . '/../.env');
var_dump($env);
echo "\n";

// Print all environment variables (excluding passwords)
echo "All Environment Variables:\n";
foreach ($_ENV as $key => $value) {
    if (stripos($key, 'pass') === false && stripos($key, 'secret') === false) {
        echo "$key = $value\n";
    }
}

echo "\nTrying database connection...\n";
try {
    require_once __DIR__ . '/includes/db.php';
} catch (Exception $e) {
    echo "Error loading db.php: " . $e->getMessage() . "\n";
    echo "Stack trace:\n" . $e->getTraceAsString() . "\n";
}

try {
    // Print loaded environment variables (excluding password)
    echo "Environment Variables:\n";
    foreach ($_ENV as $key => $value) {
        if (stripos($key, 'password') === false) {
            echo "$key: $value\n";
        }
    }
    
    echo "\nDatabase Configuration:\n";
    echo "Host: " . env('DB_HOST') . "\n";
    echo "Database: " . env('DB_NAME') . "\n";
    echo "User: " . env('DB_USER') . "\n";
    echo "Engine: " . env('DB_ENGINE') . "\n";
    
    echo "\nTesting Database Connection:\n";
    // $pdo is already created by db.php
    echo "Connection successful!\n";
    
    // Test query
    $stmt = $pdo->query("SELECT COUNT(*) FROM users");
    $count = $stmt->fetchColumn();
    echo "Found $count users in database\n";
    
} catch (Exception $e) {
    echo "\nERROR DETAILS:\n";
    echo "Error Message: " . $e->getMessage() . "\n";
    echo "Error Code: " . $e->getCode() . "\n";
    echo "File: " . $e->getFile() . "\n";
    echo "Line: " . $e->getLine() . "\n";
    if ($e instanceof PDOException) {
        echo "PDO Error Info: " . print_r($e->errorInfo, true) . "\n";
    }
    echo "\nStack Trace:\n" . $e->getTraceAsString() . "\n";
}
