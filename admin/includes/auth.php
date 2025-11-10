<?php
require_once __DIR__ . '/config.php';
require_once __DIR__ . '/User.php';
require_once __DIR__ . '/AdminLogger.php';

// Initialize admin logger
AdminLogger::init(defined('DEBUG_MODE') ? DEBUG_MODE : false);

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    AdminLogger::logError('security', 'Unauthorized access attempt', [
        'ip' => $_SERVER['REMOTE_ADDR'],
        'session_id' => session_id(),
        'path' => $_SERVER['REQUEST_URI']
    ]);
    header('Location: login.php');
    exit;
}

// Verify session integrity
if (!isset($_SESSION['ip']) || $_SESSION['ip'] !== $_SERVER['REMOTE_ADDR'] ||
    !isset($_SESSION['user_agent']) || $_SESSION['user_agent'] !== ($_SERVER['HTTP_USER_AGENT'] ?? 'Unknown')) {
    
    // Possible session hijacking attempt
    AdminLogger::logError('security', 'Session hijacking attempt detected', [
        'ip' => $_SERVER['REMOTE_ADDR'],
        'session_id' => session_id(),
        'user_id' => $_SESSION['user_id'],
        'stored_ip' => $_SESSION['ip'] ?? 'none',
        'stored_ua' => $_SESSION['user_agent'] ?? 'none',
        'current_ua' => $_SERVER['HTTP_USER_AGENT'] ?? 'Unknown'
    ]);
    
    session_unset();
    session_destroy();
    session_start();
    header('Location: login.php?error=security');
    exit;
}

// Check session timeout
if (!isset($_SESSION['last_activity']) || (time() - $_SESSION['last_activity'] > 1800)) {
    AdminLogger::logError('security', 'Session timeout', [
        'ip' => $_SERVER['REMOTE_ADDR'],
        'session_id' => session_id(),
        'user_id' => $_SESSION['user_id'],
        'last_activity' => $_SESSION['last_activity'] ?? 'none'
    ]);
    
    session_unset();
    session_destroy();
    session_start();
    header('Location: login.php?timeout=1');
    exit;
}

// Load user object
try {
    require_once __DIR__ . '/db.php';
    $stmt = $pdo->prepare("SELECT id, username, email FROM users WHERE id = ?");
    $stmt->execute([$_SESSION['user_id']]);
    $userData = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$userData) {
        // User no longer exists or was deleted
        AdminLogger::logError('security', 'Invalid user in session', [
            'user_id' => $_SESSION['user_id'],
            'session_id' => session_id()
        ]);
        
        session_unset();
        session_destroy();
        session_start();
        header('Location: login.php?error=invalid');
        exit;
    }
    
    // Create user object
    $_SESSION['user'] = new User(
        $userData['id'],
        $userData['username'],
        $userData['email']
    );
    
    // Update last activity
    $_SESSION['last_activity'] = time();
    
    // Ensure auth_check cookie exists
    if (!isset($_COOKIE['auth_check'])) {
        set_auth_check_cookie('1');
    }
    
    // Log successful validation
    AdminLogger::logLoginAttempt($userData['username'], true, 'Session validated');
    
} catch (Exception $e) {
    AdminLogger::logError('system', 'Auth system error', [
        'error' => $e->getMessage(),
        'user_id' => $_SESSION['user_id'] ?? 'none',
        'session_id' => session_id()
    ]);
    
    session_unset();
    session_destroy();
    session_start();
    header('Location: login.php?error=system');
    exit;
}

// Function to log activities
function log_activity($action) {
    if (isset($_SESSION['username'])) {
        AdminLogger::logLoginAttempt($_SESSION['username'], true, $action);
    }
}
