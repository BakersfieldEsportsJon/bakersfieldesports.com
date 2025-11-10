<?php
require_once __DIR__ . '/includes/config.php';
require_once __DIR__ . '/includes/AdminLogger.php';

// Initialize admin logger
AdminLogger::init(defined('DEBUG_MODE') ? DEBUG_MODE : false);

// Verify CSRF token if POST request
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!validate_csrf_token($_POST['csrf_token'] ?? '')) {
        AdminLogger::logError('security', 'Invalid CSRF token on logout', [
            'user_id' => $_SESSION['user_id'] ?? null,
            'username' => $_SESSION['username'] ?? null,
            'session_id' => session_id()
        ]);
        die('Invalid CSRF token');
    }
}

// Initialize monitoring
require_once __DIR__ . '/../includes/security/monitoring.php';
$monitor = new \Security\SecurityMonitor();

// Log the logout and track session end
if (isset($_SESSION['username'])) {
    AdminLogger::logLoginAttempt($_SESSION['username'], true, 'User logged out');
    
    // Track session end in monitoring
    $monitor->trackSessionActivity(
        session_id(),
        'logout',
        'admin/logout.php',
        true
    );
}

// Use session manager for secure session termination
$sessionManager = $GLOBALS['sessionManager'];
if ($sessionManager) {
    $sessionManager->destroy();
} else {
    // Fallback if session manager is not available
    session_unset();
    session_destroy();
}

// Clean up any inactive sessions
$monitor->cleanupInactiveSessions();

// Clear auth check cookie
setcookie('auth_check', '', [
    'expires' => time() - 3600,
    'path' => '/',
    'domain' => '',
    'secure' => true,
    'httponly' => true,
    'samesite' => 'Lax'
]);

// Redirect to login page
header('Location: login.php');
exit;
