<?php
/**
 * Environment Test Script
 * Upload to: test.bakersfieldesports.com/test-env.php
 */

echo "<h1>Environment Test</h1>";

// Check PHP version
echo "<p><strong>PHP Version:</strong> " . phpversion() . "</p>";

// Check if .env loader works
require_once 'includes/secure-config.php';

// Check if .env file was found
$env_found = false;
$env_paths = [
    __DIR__ . '/../.env',
    __DIR__ . '/.env',
];

echo "<h2>.env File Check:</h2>";
foreach ($env_paths as $path) {
    $exists = file_exists($path);
    echo "<p>" . htmlspecialchars($path) . ": " . ($exists ? "✅ EXISTS" : "❌ NOT FOUND") . "</p>";
    if ($exists) {
        $env_found = true;
    }
}

// Check key environment variables (without exposing values)
echo "<h2>Environment Variables:</h2>";
$keys = ['DB_HOST', 'DB_NAME', 'DB_USER', 'SESSION_ENCRYPTION_KEY'];
foreach ($keys as $key) {
    $value = env($key);
    $status = $value ? "✅ SET" : "❌ NOT SET";
    echo "<p><strong>$key:</strong> $status</p>";
}

// Test database connection
echo "<h2>Database Connection:</h2>";
try {
    require_once 'admin/includes/db.php';
    echo "<p>✅ Database connected successfully!</p>";
} catch (Exception $e) {
    echo "<p>❌ Database error: " . htmlspecialchars($e->getMessage()) . "</p>";
}

// Test cache directory
echo "<h2>Cache Directory:</h2>";
$cache_dir = __DIR__ . '/cache';
echo "<p>Cache directory: " . ($cache_dir) . "</p>";
echo "<p>Exists: " . (is_dir($cache_dir) ? "✅ YES" : "❌ NO") . "</p>";
echo "<p>Writable: " . (is_writable($cache_dir) ? "✅ YES" : "❌ NO") . "</p>";

if (file_exists($cache_dir . '/ggleap-stats.json')) {
    $age = time() - filemtime($cache_dir . '/ggleap-stats.json');
    echo "<p>GGLeap cache age: " . round($age / 3600, 1) . " hours</p>";
}

echo "<h2>GGLeap API Test:</h2>";
echo "<p><a href='api/ggleap-stats.php' target='_blank'>Test GGLeap Stats API</a></p>";

phpinfo();
?>
