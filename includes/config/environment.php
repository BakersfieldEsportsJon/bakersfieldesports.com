<?php
/**
 * Environment Configuration
 * Auto-detects environment and loads appropriate settings
 *
 * Usage:
 *   require_once __DIR__ . '/includes/config/environment.php';
 *   echo SITE_URL; // Automatically uses local or production URL
 */

// Load environment variables from .env file
function loadEnv($path) {
    if (!file_exists($path)) {
        throw new Exception(".env file not found at: $path");
    }

    $lines = file($path, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    foreach ($lines as $line) {
        // Skip comments
        if (strpos(trim($line), '#') === 0) {
            continue;
        }

        // Parse KEY=VALUE
        if (strpos($line, '=') !== false) {
            list($key, $value) = explode('=', $line, 2);
            $key = trim($key);
            $value = trim($value);

            // Remove quotes if present
            $value = trim($value, '"\'');

            // Set environment variable
            if (!array_key_exists($key, $_ENV)) {
                $_ENV[$key] = $value;
                putenv("$key=$value");
            }
        }
    }
}

// Load .env file
$envPath = __DIR__ . '/../../.env';
loadEnv($envPath);

// Auto-detect environment based on hostname
$isProduction = false;
if (isset($_SERVER['HTTP_HOST'])) {
    $host = $_SERVER['HTTP_HOST'];
    $isProduction = (
        strpos($host, 'bakersfieldesports.com') !== false ||
        strpos($host, 'www.bakersfieldesports.com') !== false
    );
}

// Or use explicit environment variable
if (isset($_ENV['ENVIRONMENT'])) {
    $isProduction = ($_ENV['ENVIRONMENT'] === 'production');
}

// Define environment constant
define('IS_PRODUCTION', $isProduction);
define('ENVIRONMENT', $isProduction ? 'production' : 'local');

// Load environment-specific URLs
if (IS_PRODUCTION) {
    define('SITE_URL', $_ENV['PRODUCTION_SITE_URL'] ?? 'https://bakersfieldesports.com');
    define('STARTGG_OAUTH_CLIENT_ID', $_ENV['PRODUCTION_STARTGG_CLIENT_ID'] ?? '');
    define('STARTGG_OAUTH_CLIENT_SECRET', $_ENV['PRODUCTION_STARTGG_CLIENT_SECRET'] ?? '');
    define('STARTGG_OAUTH_REDIRECT_URI', $_ENV['PRODUCTION_STARTGG_REDIRECT_URI'] ?? '');
    define('STRIPE_PUBLISHABLE_KEY', $_ENV['STRIPE_PUBLISHABLE_KEY_LIVE'] ?? '');
    define('STRIPE_SECRET_KEY', $_ENV['STRIPE_SECRET_KEY_LIVE'] ?? '');
    define('STRIPE_WEBHOOK_SECRET', $_ENV['STRIPE_WEBHOOK_SECRET_LIVE'] ?? '');
} else {
    define('SITE_URL', $_ENV['LOCAL_SITE_URL'] ?? 'http://localhost/bakersfield');
    define('STARTGG_OAUTH_CLIENT_ID', $_ENV['LOCAL_STARTGG_CLIENT_ID'] ?? '');
    define('STARTGG_OAUTH_CLIENT_SECRET', $_ENV['LOCAL_STARTGG_CLIENT_SECRET'] ?? '');
    define('STARTGG_OAUTH_REDIRECT_URI', $_ENV['LOCAL_STARTGG_REDIRECT_URI'] ?? '');
    define('STRIPE_PUBLISHABLE_KEY', $_ENV['STRIPE_PUBLISHABLE_KEY_TEST'] ?? '');
    define('STRIPE_SECRET_KEY', $_ENV['STRIPE_SECRET_KEY_TEST'] ?? '');
    define('STRIPE_WEBHOOK_SECRET', $_ENV['STRIPE_WEBHOOK_SECRET_TEST'] ?? '');
}

// Common settings (same for both environments)
define('STARTGG_API_ENDPOINT', $_ENV['STARTGG_API_ENDPOINT'] ?? 'https://api.start.gg/gql/alpha');
define('STARTGG_API_TOKEN', $_ENV['STARTGG_API_TOKEN'] ?? $_ENV['api_token'] ?? '');

// Database settings (environment-specific)
if (IS_PRODUCTION) {
    define('DB_HOST', $_ENV['PRODUCTION_DB_HOST'] ?? $_ENV['DB_HOST'] ?? 'localhost');
    define('DB_NAME', $_ENV['PRODUCTION_DB_NAME'] ?? $_ENV['DB_NAME'] ?? '');
    define('DB_USER', $_ENV['PRODUCTION_DB_USER'] ?? $_ENV['DB_USER'] ?? '');
    define('DB_PASSWORD', $_ENV['PRODUCTION_DB_PASSWORD'] ?? $_ENV['DB_PASSWORD'] ?? '');
} else {
    define('DB_HOST', $_ENV['LOCAL_DB_HOST'] ?? $_ENV['DB_HOST'] ?? 'localhost');
    define('DB_NAME', $_ENV['LOCAL_DB_NAME'] ?? $_ENV['DB_NAME'] ?? '');
    define('DB_USER', $_ENV['LOCAL_DB_USER'] ?? $_ENV['DB_USER'] ?? 'root');
    define('DB_PASSWORD', $_ENV['LOCAL_DB_PASSWORD'] ?? $_ENV['DB_PASSWORD'] ?? '');
}

// Email settings
define('MAIL_FROM_ADDRESS', $_ENV['MAIL_FROM_ADDRESS'] ?? 'noreply@bakersfieldesports.com');
define('MAIL_FROM_NAME', $_ENV['MAIL_FROM_NAME'] ?? 'Bakersfield eSports Center');

// Debug mode (only in local environment)
define('DEBUG_MODE', !IS_PRODUCTION);

// Error reporting
if (DEBUG_MODE) {
    error_reporting(E_ALL);
    ini_set('display_errors', '1');
} else {
    error_reporting(E_ALL & ~E_NOTICE & ~E_DEPRECATED);
    ini_set('display_errors', '0');
}

// Timezone
date_default_timezone_set('America/Los_Angeles');

// Helper function to get database connection
function getDatabaseConnection() {
    static $pdo = null;

    if ($pdo === null) {
        try {
            $pdo = new PDO(
                sprintf('mysql:host=%s;dbname=%s;charset=utf8mb4', DB_HOST, DB_NAME),
                DB_USER,
                DB_PASSWORD,
                [
                    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                    PDO::ATTR_EMULATE_PREPARES => false
                ]
            );
        } catch (PDOException $e) {
            if (DEBUG_MODE) {
                die("Database connection failed: " . $e->getMessage());
            } else {
                die("Database connection failed. Please contact support.");
            }
        }
    }

    return $pdo;
}

// Log function for debugging
function debugLog($message, $data = null) {
    if (!DEBUG_MODE) {
        return;
    }

    $logFile = __DIR__ . '/../../cache/debug.log';
    $logDir = dirname($logFile);

    if (!is_dir($logDir)) {
        mkdir($logDir, 0755, true);
    }

    $timestamp = date('Y-m-d H:i:s');
    $logEntry = "[$timestamp] $message";

    if ($data !== null) {
        $logEntry .= "\n" . print_r($data, true);
    }

    $logEntry .= "\n---\n";

    file_put_contents($logFile, $logEntry, FILE_APPEND);
}

// Environment info (for debugging)
if (DEBUG_MODE) {
    debugLog("Environment loaded", [
        'Environment' => ENVIRONMENT,
        'Site URL' => SITE_URL,
        'OAuth Client ID' => STARTGG_OAUTH_CLIENT_ID,
        'Redirect URI' => STARTGG_OAUTH_REDIRECT_URI,
        'Has Stripe Key' => !empty(STRIPE_SECRET_KEY) ? 'Yes' : 'No'
    ]);
}
