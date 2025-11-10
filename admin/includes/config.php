<?php
// Load configurations
require_once __DIR__ . '/db_config.php';
require_once __DIR__ . '/debug_config.php';
define('SITE_NAME', 'Bakersfield Esports Center');

// Production error handling configuration
error_reporting(E_ALL);
ini_set('display_errors', 0); // Changed from 1 to 0 for production security
ini_set('log_errors', 1);
ini_set('error_log', dirname(dirname(__DIR__)) . '/error.log');

// Ensure error log is writable
$errorLogFile = dirname(dirname(__DIR__)) . '/error.log';
if (!is_writable($errorLogFile) && !is_writable(dirname($errorLogFile))) {
    error_log("Error log is not writable: " . $errorLogFile);
}

// Ensure proper timezone is set
date_default_timezone_set('America/Los_Angeles');

// Initialize session handling
require_once __DIR__ . '/../../bootstrap.php';
require_once __DIR__ . '/session.php';

// Session security checks
if (isset($_SESSION['last_activity']) && (time() - $_SESSION['last_activity'] > 1800)) {
    // If last activity was more than 30 minutes ago
    session_unset();
    session_destroy();
    header('Location: login.php?timeout=1');
    exit;
}
$_SESSION['last_activity'] = time();

// Prevent session fixation
if (!isset($_SESSION['created'])) {
    $_SESSION['created'] = time();
} else if (time() - $_SESSION['created'] > 1800) {
    // Session started more than 30 minutes ago
    session_regenerate_id(true);
    $_SESSION['created'] = time();
}

// Define common paths
define('ADMIN_ROOT', dirname(__DIR__));
define('SITE_ROOT', dirname(ADMIN_ROOT));
define('ADMIN_URL', '/admin');

// Security headers
header('X-Frame-Options: DENY');
header('X-XSS-Protection: 1; mode=block');
header('X-Content-Type-Options: nosniff');
header('Referrer-Policy: same-origin');
if (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on') {
    header('Strict-Transport-Security: max-age=31536000; includeSubDomains');
}

// Error handler for logging
set_error_handler(function($errno, $errstr, $errfile, $errline) {
    if (!(error_reporting() & $errno)) {
        return false;
    }
    
    $errorType = match($errno) {
        E_ERROR => 'Error',
        E_WARNING => 'Warning',
        E_PARSE => 'Parse Error',
        E_NOTICE => 'Notice',
        E_CORE_ERROR => 'Core Error',
        E_CORE_WARNING => 'Core Warning',
        E_COMPILE_ERROR => 'Compile Error',
        E_COMPILE_WARNING => 'Compile Warning',
        E_USER_ERROR => 'User Error',
        E_USER_WARNING => 'User Warning',
        E_USER_NOTICE => 'User Notice',
        E_STRICT => 'Strict',
        E_RECOVERABLE_ERROR => 'Recoverable Error',
        E_DEPRECATED => 'Deprecated',
        E_USER_DEPRECATED => 'User Deprecated',
        default => 'Unknown Error'
    };
    
    error_log("$errorType: $errstr in $errfile on line $errline");
    return true;
});

// Production exception handler
set_exception_handler(function($e) {
    error_log('Uncaught Exception: ' . $e->getMessage() . ' in ' . $e->getFile() . ' on line ' . $e->getLine());
    http_response_code(500);
    include SITE_ROOT . '/500.shtml';
    exit();
});
