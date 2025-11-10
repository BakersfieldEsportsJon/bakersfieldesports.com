<?php
// Prevent session from starting multiple times
if (session_status() === PHP_SESSION_ACTIVE) {
    error_log('Session already active, skipping initialization');
    return;
}

// Get domain from server name
$domain = $_SERVER['HTTP_HOST'];
$isTestDomain = strpos($domain, 'test.') === 0;

// Set session name with domain prefix to avoid conflicts
$sessionName = $isTestDomain ? 'TEST_BEC_ADMIN_SESSID' : 'BEC_ADMIN_SESSID';
session_name($sessionName);

// Configure session parameters before starting
$sessionParams = [
    'lifetime' => 86400,  // 24 hours
    'path' => '/',
    'domain' => $domain,
    'secure' => true,
    'httponly' => true,
    'samesite' => 'Lax'
];

// Set session cookie parameters
session_set_cookie_params($sessionParams);

// Configure PHP session settings
ini_set('session.use_strict_mode', '1');  // Prevent session fixation
ini_set('session.use_cookies', '1');  // Force cookie usage
ini_set('session.use_only_cookies', '1');  // Disable URL-based sessions
ini_set('session.cookie_httponly', '1');  // Prevent JS access
ini_set('session.cookie_secure', '1');  // Require HTTPS
ini_set('session.cookie_samesite', 'Lax');  // CSRF protection
ini_set('session.gc_maxlifetime', '86400');  // Match cookie lifetime
ini_set('session.gc_probability', '1');
ini_set('session.gc_divisor', '100');  // 1% chance of GC
ini_set('session.cache_limiter', 'nocache');  // Prevent caching
ini_set('session.hash_function', 'sha256');  // Secure hash

// Log session configuration
error_log(sprintf(
    'Session config - Name: %s, Domain: %s, Path: %s, Secure: %s, SameSite: %s',
    session_name(),
    $sessionParams['domain'],
    $sessionParams['path'],
    $sessionParams['secure'] ? 'true' : 'false',
    $sessionParams['samesite']
));

// Start session and log details
if (!session_start()) {
    error_log('Failed to start session');
    throw new RuntimeException('Failed to start session');
}

error_log(sprintf(
    'Session started - ID: %s, Name: %s',
    session_id(),
    session_name()
));

// Generate CSRF token only if it doesn't exist
if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    error_log(sprintf(
        'New CSRF token generated - Session: %s, Token: %s',
        session_id(),
        $_SESSION['csrf_token']
    ));
}

// Function to validate CSRF token
function validate_csrf_token($token) {
    if (!isset($_SESSION['csrf_token'])) {
        error_log(sprintf(
            'No CSRF token in session - Session: %s, Headers: %s',
            session_id(),
            json_encode(getallheaders())
        ));
        return false;
    }
    
    if (!isset($token)) {
        error_log('No CSRF token provided in request');
        return false;
    }
    
    $valid = hash_equals($_SESSION['csrf_token'], $token);
    error_log(sprintf(
        'CSRF validation - Session: %s, Expected: %s, Got: %s, Valid: %s',
        session_id(),
        $_SESSION['csrf_token'],
        $token,
        $valid ? 'true' : 'false'
    ));
    
    return $valid;
}

// Function to get current CSRF token
function get_csrf_token() {
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
        error_log('Generated new CSRF token on get_csrf_token() call');
    }
    return $_SESSION['csrf_token'];
}

// Function to regenerate session safely
function regenerate_session() {
    // Store old session data
    $old_session_data = $_SESSION;
    
    // Regenerate session ID
    if (session_regenerate_id(true)) {
        // Restore old session data
        $_SESSION = $old_session_data;
        error_log(sprintf(
            'Session regenerated - Old ID: %s, New ID: %s',
            session_id(),
            session_id()
        ));
        return true;
    }
    
    error_log('Failed to regenerate session');
    return false;
}

// Function to set auth check cookie
function set_auth_check_cookie($value = '1') {
    $domain = $_SERVER['HTTP_HOST'];
    error_log(sprintf(
        'Setting auth_check cookie - Domain: %s, Path: /, Value: %s',
        $domain,
        $value
    ));
    
    return setcookie('auth_check', $value, [
        'expires' => $value === '' ? time() - 3600 : time() + 86400,
        'path' => '/',
        'domain' => $domain,
        'secure' => true,
        'httponly' => true,
        'samesite' => 'Lax'
    ]);
}

// Debug function to dump session state
function debug_session_state() {
    error_log(sprintf(
        'Session Debug - ID: %s, Name: %s, Cookie: %s, Token: %s, Domain: %s',
        session_id(),
        session_name(),
        isset($_COOKIE[session_name()]) ? $_COOKIE[session_name()] : 'none',
        $_SESSION['csrf_token'] ?? 'none',
        $_SERVER['HTTP_HOST']
    ));
}

// Log initial session state
debug_session_state();
