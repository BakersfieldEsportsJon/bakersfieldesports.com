<?php
/**
 * Notification Settings Admin Panel
 * Manage webhook URL for location opening notifications
 */

session_start();
require_once __DIR__ . '/includes/auth.php';
requireAdmin();

require_once __DIR__ . '/includes/db.php';

$success_message = '';
$error_message = '';

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    if ($_POST['action'] === 'update_webhook') {
        $webhookUrl = trim($_POST['webhook_url']);
        $isActive = isset($_POST['is_active']) ? 1 : 0;

        try {
            $stmt = $pdo->prepare("
                UPDATE notification_settings
                SET setting_value = ?, is_active = ?, updated_at = NOW()
                WHERE setting_key = 'webhook_url'
            ");
            $stmt->execute([$webhookUrl, $isActive]);

            $success_message = 'Webhook URL updated successfully!';
        } catch (PDOException $e) {
            $error_message = 'Error updating webhook URL: ' . $e->getMessage();
        }
    }
}

// Get current webhook settings
$webhookUrl = '';
$isActive = 1;

try {
    $stmt = $pdo->prepare("SELECT setting_value, is_active FROM notification_settings WHERE setting_key = 'webhook_url'");
    $stmt->execute();
    $result = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($result) {
        $webhookUrl = $result['setting_value'];
        $isActive = $result['is_active'];
    }
} catch (PDOException $e) {
    $error_message = 'Error loading settings: ' . $e->getMessage();
}

// Get recent notification submissions
$recentSubmissions = [];
try {
    $stmt = $pdo->prepare("
        SELECT * FROM notification_submissions
        ORDER BY submitted_at DESC
        LIMIT 50
    ");
    $stmt->execute();
    $recentSubmissions = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    // Table might not exist yet - that's okay
}

$page_title = 'Notification Settings';
require_once __DIR__ . '/includes/header.php';
?>

<style>
    .settings-container {
        max-width: 1200px;
        margin: 0 auto;
    }
    .settings-card {
        background: white;
        border-radius: 8px;
        padding: 30px;
        margin-bottom: 30px;
        box-shadow: 0 2px 10px rgba(0,0,0,0.1);
    }
    .settings-card h2 {
        color: #EC194D;
        margin-bottom: 20px;
        border-bottom: 2px solid #EC194D;
        padding-bottom: 10px;
    }
    .form-group {
        margin-bottom: 20px;
    }
    .form-group label {
        display: block;
        font-weight: bold;
        margin-bottom: 8px;
        color: #333;
    }
    .form-group input[type="text"],
    .form-group input[type="url"] {
        width: 100%;
        padding: 12px;
        border: 2px solid #ddd;
        border-radius: 5px;
        font-size: 14px;
        box-sizing: border-box;
    }
    .form-group input[type="text"]:focus,
    .form-group input[type="url"]:focus {
        outline: none;
        border-color: #EC194D;
    }
    .form-group .help-text {
        font-size: 13px;
        color: #666;
        margin-top: 5px;
    }
    .checkbox-group {
        display: flex;
        align-items: center;
        gap: 10px;
    }
    .checkbox-group input[type="checkbox"] {
        width: 20px;
        height: 20px;
        cursor: pointer;
    }
    .btn-primary {
        background: #EC194D;
        color: white;
        padding: 12px 30px;
        border: none;
        border-radius: 5px;
        font-size: 16px;
        font-weight: bold;
        cursor: pointer;
        transition: all 0.3s ease;
    }
    .btn-primary:hover {
        background: #b3163e;
        transform: translateY(-2px);
    }
    .alert {
        padding: 15px;
        border-radius: 5px;
        margin-bottom: 20px;
    }
    .alert-success {
        background: #d4edda;
        color: #155724;
        border: 1px solid #c3e6cb;
    }
    .alert-error {
        background: #f8d7da;
        color: #721c24;
        border: 1px solid #f5c6cb;
    }
    .submissions-table {
        width: 100%;
        border-collapse: collapse;
        margin-top: 20px;
    }
    .submissions-table th,
    .submissions-table td {
        padding: 12px;
        text-align: left;
        border-bottom: 1px solid #ddd;
    }
    .submissions-table th {
        background: #f5f5f5;
        font-weight: bold;
        color: #333;
    }
    .submissions-table tr:hover {
        background: #f9f9f9;
    }
    .webhook-instructions {
        background: #f0f7ff;
        border: 1px solid #b3d9ff;
        border-radius: 5px;
        padding: 20px;
        margin-bottom: 20px;
    }
    .webhook-instructions h3 {
        color: #0066cc;
        margin-top: 0;
    }
    .webhook-instructions ol {
        margin: 10px 0;
        padding-left: 20px;
    }
    .webhook-instructions li {
        margin: 10px 0;
    }
    .webhook-instructions code {
        background: #e0e0e0;
        padding: 2px 6px;
        border-radius: 3px;
        font-family: monospace;
    }
</style>

<div class="settings-container">
    <h1>Location Opening Notification Settings</h1>

    <?php if ($success_message): ?>
        <div class="alert alert-success"><?php echo htmlspecialchars($success_message); ?></div>
    <?php endif; ?>

    <?php if ($error_message): ?>
        <div class="alert alert-error"><?php echo htmlspecialchars($error_message); ?></div>
    <?php endif; ?>

    <!-- Webhook Configuration -->
    <div class="settings-card">
        <h2>Webhook Configuration</h2>

        <div class="webhook-instructions">
            <h3>📝 Setup Instructions</h3>
            <ol>
                <li><strong>Create a Zapier Zap or Make Scenario:</strong>
                    <ul>
                        <li><strong>Zapier:</strong> Create a new Zap with "Webhooks by Zapier" trigger → Choose "Catch Hook"</li>
                        <li><strong>Make.com:</strong> Create new scenario with "Webhooks" module → Choose "Custom webhook"</li>
                    </ul>
                </li>
                <li>Copy the webhook URL provided by Zapier/Make</li>
                <li>Paste it in the field below and click "Save Settings"</li>
                <li>Set up your workflow to add the data to Google Sheets:
                    <ul>
                        <li>Fields sent: <code>name</code>, <code>email</code>, <code>phone</code>, <code>location</code>, <code>timestamp</code></li>
                    </ul>
                </li>
                <li>Test the integration using the button on the locations page</li>
            </ol>
        </div>

        <form method="POST" action="">
            <input type="hidden" name="action" value="update_webhook">

            <div class="form-group">
                <label for="webhook_url">Webhook URL</label>
                <input
                    type="url"
                    id="webhook_url"
                    name="webhook_url"
                    value="<?php echo htmlspecialchars($webhookUrl); ?>"
                    placeholder="https://hooks.zapier.com/hooks/catch/12345/abcdef/"
                    required
                >
                <p class="help-text">Enter your Zapier or Make.com webhook URL</p>
            </div>

            <div class="form-group">
                <div class="checkbox-group">
                    <input
                        type="checkbox"
                        id="is_active"
                        name="is_active"
                        <?php echo $isActive ? 'checked' : ''; ?>
                    >
                    <label for="is_active" style="margin:0;">Enable Notifications</label>
                </div>
                <p class="help-text">When unchecked, the notification form will not be available to users</p>
            </div>

            <button type="submit" class="btn-primary">Save Settings</button>
        </form>
    </div>

    <!-- Recent Submissions -->
    <?php if (!empty($recentSubmissions)): ?>
    <div class="settings-card">
        <h2>Recent Notification Requests (Last 50)</h2>
        <table class="submissions-table">
            <thead>
                <tr>
                    <th>Name</th>
                    <th>Email</th>
                    <th>Phone</th>
                    <th>Location</th>
                    <th>Submitted</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($recentSubmissions as $submission): ?>
                <tr>
                    <td><?php echo htmlspecialchars($submission['name']); ?></td>
                    <td><?php echo htmlspecialchars($submission['email']); ?></td>
                    <td><?php echo htmlspecialchars($submission['phone'] ?: 'N/A'); ?></td>
                    <td><?php echo htmlspecialchars($submission['location']); ?></td>
                    <td><?php echo date('M j, Y g:i A', strtotime($submission['submitted_at'])); ?></td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
    <?php endif; ?>
</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
