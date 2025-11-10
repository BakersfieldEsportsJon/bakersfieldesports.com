<?php
// Load environment variables
if (file_exists(__DIR__.'/.env')) {
    $env = parse_ini_file(__DIR__.'/.env');
    if ($env === false) {
        throw new RuntimeException('Failed to parse .env file');
    }
    foreach ($env as $key => $value) {
        // Strip quotes from the value if present
        $value = trim($value, "'\"");
        putenv("$key=$value");
        $_ENV[$key] = $value;
        $_SERVER[$key] = $value;
    }
}

// Define helper functions
if (!function_exists('env')) {
    function env($key, $default = null) {
        return $_ENV[$key] ?? $default;
    }
}

if (!function_exists('storage_path')) {
    function storage_path($path = '') {
        return __DIR__ . '/storage/' . ltrim($path, '/');
    }
}

// Set default timezone
date_default_timezone_set('UTC');

// Initialize session security
require_once __DIR__.'/includes/security/Encryption.php';
require_once __DIR__.'/includes/security/GeoLocationService.php';
require_once __DIR__.'/includes/security/session_manager.php';
require_once __DIR__.'/includes/security/middleware/SessionValidationMiddleware.php';
require_once __DIR__.'/includes/security/exceptions/SessionException.php';
require_once __DIR__.'/includes/security/exceptions/SessionTimeoutException.php';
require_once __DIR__.'/includes/security/exceptions/SessionSecurityException.php';
require_once __DIR__.'/includes/security/exceptions/SessionNotStartedException.php';
require_once __DIR__.'/includes/security/exceptions/SessionLocationException.php';

use Security\Encryption;
use Security\SessionManager;
use Security\Middleware\SessionValidationMiddleware;

// Load session configuration
$sessionConfig = require __DIR__.'/includes/security/session_config.php';

// Initialize components
$encryption = new Encryption(env('SESSION_ENCRYPTION_KEY'));
$sessionManager = new SessionManager($encryption, $sessionConfig);

// Register session manager as a global service
$GLOBALS['sessionManager'] = $sessionManager;

// Register session validation middleware if $app exists
if (isset($app)) {
    $app->add(new SessionValidationMiddleware($sessionManager));
}
