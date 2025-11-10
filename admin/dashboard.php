<?php
require_once __DIR__ . '/includes/config.php';
require_once __DIR__ . '/includes/AdminLogger.php';

// Initialize admin logger
AdminLogger::init(defined('DEBUG_MODE') ? DEBUG_MODE : false);

// Verify user is logged in and session is valid
require_once __DIR__ . '/includes/auth.php';

// Log dashboard access
AdminLogger::logLoginAttempt($_SESSION['user']->getUsername(), true, 'Accessed admin dashboard');

// Initialize database connection
require_once __DIR__ . '/includes/db.php';

// Handle user approval if admin
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    if ($_POST['action'] === 'approve' && isset($_POST['user_id'])) {
        try {
            $stmt = $pdo->prepare("UPDATE users SET status = 'active' WHERE id = ?");
            $stmt->execute([$_POST['user_id']]);
            $success = "User approved successfully";
        } catch (PDOException $e) {
            $error = "Failed to approve user";
            AdminLogger::logError('database', 'User approval error', [
                'error' => $e->getMessage(),
                'user_id' => $_POST['user_id']
            ]);
        }
    }
}

// Get pending users if admin
$pendingUsers = [];
try {
    $stmt = $pdo->prepare("SELECT id, username, email, created_at FROM users WHERE status = 'pending'");
    $stmt->execute();
    $pendingUsers = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    AdminLogger::logError('database', 'Error fetching pending users', [
        'error' => $e->getMessage()
    ]);
}

// Build base URL
$protocol = isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? 'https://' : 'http://';
$host = $_SERVER['HTTP_HOST'];
$baseUrl = $protocol . $host;

// Template configuration
$page_title = 'Admin Dashboard';
$base_path = '../';
$extra_css = ['../css/styles.css'];
$extra_head_content = '<style>
    body {
        background-color: var(--darker-bg);
        margin: 0;
        min-height: 100vh;
        padding: 2em;
    }
    .dashboard-container {
        margin-top: 3em;
    }
</style>';

require_once __DIR__ . '/includes/admin_head.php';
require_once __DIR__ . '/includes/admin_nav.php';
?>
    <div class="dashboard-container">
        <h1>Welcome, <?php echo htmlspecialchars($_SESSION['user']->getUsername()); ?></h1>
        
        <div class="admin-sections">
            <div class="admin-section">
                <h2>Events Administration</h2>
                <p>Manage upcoming events, registrations, and event details</p>
                <a href="../events/admin/index.php" class="admin-btn">Go to Events Admin</a>
            </div>

            <div class="admin-section">
                <h2>Security Monitoring</h2>
                <p>Monitor sessions, track activity, and view security alerts</p>
                <a href="security_dashboard.php" class="admin-btn">Security Dashboard</a>
            </div>
        </div>

        <div class="pending-users">
            <h2>Pending User Approvals <?php if (!empty($pendingUsers)): ?><span class="badge"><?php echo count($pendingUsers); ?></span><?php endif; ?></h2>
            <?php if (!empty($pendingUsers)): ?>
            <table class="user-table">
                <thead>
                    <tr>
                        <th>Username</th>
                        <th>Email</th>
                        <th>Requested</th>
                        <th>Action</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($pendingUsers as $user): ?>
                    <tr>
                        <td><?php echo htmlspecialchars($user['username']); ?></td>
                        <td><?php echo htmlspecialchars($user['email']); ?></td>
                        <td><?php echo date('M j, Y', strtotime($user['created_at'])); ?></td>
                        <td>
                            <form method="POST" style="display: inline;">
                                <input type="hidden" name="action" value="approve">
                                <input type="hidden" name="user_id" value="<?php echo $user['id']; ?>">
                                <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars(get_csrf_token()); ?>">
                                <button type="submit" class="approve-btn">Approve</button>
                            </form>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
            <?php else: ?>
                <p class="no-pending">No pending user approvals at this time.</p>
            <?php endif; ?>
            <?php if (isset($success)): ?>
                <div class="success-message"><?php echo htmlspecialchars($success); ?></div>
            <?php endif; ?>
        </div>
    </div>
<?php require_once __DIR__ . '/includes/admin_footer.php'; ?>
