<?php
require_once __DIR__ . '/includes/config.php';
require_once __DIR__ . '/includes/User.php';
require_once __DIR__ . '/includes/AdminLogger.php';
require_once __DIR__ . '/includes/session.php';

// Initialize admin logger with debug mode
AdminLogger::init(defined('DEBUG_MODE') ? DEBUG_MODE : false);


// Initialize error message
$error = '';

// Check if this is a timeout redirect
if (isset($_GET['timeout']) && $_GET['timeout'] == 1) {
    $error = "Your session has expired - please log in again";
    AdminLogger::logError('security', 'Session timeout', [
        'session_id' => session_id(),
        'ip' => $_SERVER['REMOTE_ADDR']
    ]);
}

// Check if user is already logged in
if (isset($_SESSION['user_id'])) {
    // Verify session integrity
    if (isset($_SESSION['ip']) && $_SESSION['ip'] === $_SERVER['REMOTE_ADDR'] &&
        isset($_SESSION['last_activity']) && (time() - $_SESSION['last_activity'] < 1800)) {
        
        // Valid session, redirect to dashboard
        if (!isset($_COOKIE['auth_check'])) {
            header('Location: dashboard.php');
            exit;
        }
    }
    
    // Invalid session, clear it
    error_log('Clearing invalid session');
    session_unset();
    session_destroy();
    session_start();
    $error = "Session expired - please log in again";
}

// Clear any existing auth check cookie
if (isset($_COOKIE['auth_check'])) {
    set_auth_check_cookie('');  // Clear the cookie
}

// Handle login attempt
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    error_log("Login attempt received");
    error_log("POST data: " . print_r($_POST, true));
    error_log("Session data: " . print_r($_SESSION, true));
    error_log(sprintf(
        'Login attempt - POST Token: %s, Session Token: %s, Session ID: %s',
        $_POST['csrf_token'] ?? 'none',
        $_SESSION['csrf_token'] ?? 'none',
        session_id()
    ));
    
    // Validate CSRF token
    if (!validate_csrf_token($_POST['csrf_token'] ?? '')) {
        AdminLogger::logError('security', 'Invalid CSRF token on login', [
            'ip' => $_SERVER['REMOTE_ADDR'],
            'user_agent' => $_SERVER['HTTP_USER_AGENT'] ?? 'Unknown',
            'session_id' => session_id(),
            'session_name' => session_name(),
            'domain' => $_SERVER['HTTP_HOST']
        ]);
        $error = "Security validation failed - please try again";
    } else {
        try {
            $username = trim($_POST['username']);
            $password = $_POST['password'];
            
            error_log("Attempting authentication for username: $username");
            try {
                // Test database connection before authentication
                $testPdo = new PDO(
                    DB_DSN,
                    DB_USER,
                    DB_PASS,
                    [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]
                );
                error_log("Database connection test successful");
                
                // Authenticate user
                $user = User::authenticate($username, $password);
                error_log("Authentication result: " . ($user ? "success" : "failed"));
            } catch (PDOException $e) {
                error_log("Database connection error: " . $e->getMessage());
                error_log("DSN: " . DB_DSN);
                error_log("User: " . DB_USER);
                throw $e;
            }
            
            if ($user) {
                // Log successful login
                AdminLogger::logLoginAttempt($username, true);
                
                // Regenerate session safely
                if (!regenerate_session()) {
                    throw new RuntimeException('Failed to regenerate session');
                }
                
                // Store user info in session
                $_SESSION['user_id'] = $user->getId();
                $_SESSION['username'] = $user->getUsername();
                $_SESSION['last_activity'] = time();
                $_SESSION['created'] = time();
                $_SESSION['ip'] = $_SERVER['REMOTE_ADDR'];
                $_SESSION['user_agent'] = $_SERVER['HTTP_USER_AGENT'] ?? 'Unknown';
                $_SESSION['is_admin'] = true;

                // Set auth check cookie
                if (!set_auth_check_cookie('1')) {
                    error_log('Warning: Failed to set auth_check cookie');
                }
                
                // Log session state before redirect
                debug_session_state();
                
                // Redirect to dashboard
                header('Location: dashboard.php');
                exit;
            } else {
                AdminLogger::logLoginAttempt($username, false, "Invalid credentials");
                $error = "Invalid username or password";
            }
        } catch (PDOException $e) {
            AdminLogger::logError('database', $e->getMessage(), ['exception' => get_class($e)]);
            $error = "Database error - please try again later";
        } catch (Exception $e) {
            AdminLogger::logError('system', $e->getMessage(), ['exception' => get_class($e)]);
            $error = "Login failed - please try again";
        }
    }
}


// Template configuration
$page_title = 'Admin Login';
$body_class = 'admin-page';
$base_path = '../';

require_once __DIR__ . '/includes/admin_head.php';
?>
    <main class="admin-container">
        <div class="auth-container">
            <?php if ($error): ?>
            <div class="error-message"><?= htmlspecialchars($error) ?></div>
            <?php endif; ?>

            <form method="POST">
                <input type="hidden" name="csrf_token" value="<?= htmlspecialchars(get_csrf_token()) ?>">

                <div class="form-group">
                    <label for="username">Username:</label>
                    <input type="text" id="username" name="username" required>
                </div>

                <div class="form-group">
                    <label for="password">Password:</label>
                    <input type="password" id="password" name="password" required>
                </div>

                <button type="submit" class="btn-submit">Login</button>
            </form>
        </div>
    </main>
<?php require_once __DIR__ . '/includes/admin_footer.php'; ?>
