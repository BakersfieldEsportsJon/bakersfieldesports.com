<?php
/**
 * Start.gg Integration - Web Setup Interface
 * Access: /admin/startgg/setup/
 */

// Require admin authentication
require_once __DIR__ . '/../../../admin/includes/config.php';
require_once __DIR__ . '/../../../admin/includes/auth.php';

if (!isset($_SESSION['user_id'])) {
    header('Location: /admin/login.php');
    exit;
}

require_once __DIR__ . '/../../../admin/includes/db.php';
require_once __DIR__ . '/../../../includes/startgg/StartGGConfig.php';

$step = isset($_GET['step']) ? $_GET['step'] : 'welcome';
$errors = [];
$success = [];

// Handle form submissions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!validate_csrf_token($_POST['csrf_token'] ?? '')) {
        $errors[] = 'Invalid CSRF token';
    } else {
        $action = $_POST['action'] ?? '';

        switch ($action) {
            case 'create_tables':
                try {
                    $sqlFile = __DIR__ . '/../migrations/001_create_startgg_tables.sql';
                    $sql = file_get_contents($sqlFile);
                    $statements = array_filter(
                        array_map('trim', explode(';', $sql)),
                        function($stmt) { return !empty($stmt) && substr($stmt, 0, 2) !== '--'; }
                    );

                    foreach ($statements as $statement) {
                        try {
                            $pdo->exec($statement);
                        } catch (PDOException $e) {
                            if (strpos($e->getMessage(), 'already exists') === false) {
                                throw $e;
                            }
                        }
                    }

                    $success[] = 'Database tables created successfully!';
                    $step = 'configure';
                } catch (Exception $e) {
                    $errors[] = 'Database error: ' . $e->getMessage();
                }
                break;

            case 'save_config':
                try {
                    $config = new StartGGConfig($pdo);

                    // Save API token if provided
                    $apiToken = $_POST['api_token'] ?? '';
                    if (!empty($apiToken)) {
                        if ($config->saveApiToken($apiToken)) {
                            $success[] = 'API token saved successfully!';
                        } else {
                            $errors[] = 'Failed to save API token';
                        }
                    }

                    $step = 'test';
                } catch (Exception $e) {
                    $errors[] = 'Configuration error: ' . $e->getMessage();
                }
                break;

            case 'test_connection':
                try {
                    $config = new StartGGConfig($pdo);
                    $testResult = $config->testConnection();

                    if ($testResult['success']) {
                        $success[] = 'API connection successful!';
                        $success[] = 'Authenticated as: ' . ($testResult['data']['name'] ?? 'Unknown');
                        $step = 'complete';
                    } else {
                        $errors[] = 'Connection failed: ' . $testResult['message'];
                    }
                } catch (Exception $e) {
                    $errors[] = 'Test error: ' . $e->getMessage();
                }
                break;
        }
    }
}

// Check current setup status
$setupStatus = [
    'tables_exist' => false,
    'token_configured' => false,
    'connection_ok' => false
];

try {
    $stmt = $pdo->query("SHOW TABLES LIKE 'startgg_config'");
    $setupStatus['tables_exist'] = $stmt->rowCount() > 0;

    if ($setupStatus['tables_exist']) {
        $config = new StartGGConfig($pdo);
        $setupStatus['token_configured'] = !empty($config->getApiToken());

        if ($setupStatus['token_configured']) {
            $testResult = $config->testConnection();
            $setupStatus['connection_ok'] = $testResult['success'];
        }
    }
} catch (Exception $e) {
    // Ignore errors during status check
}

// Template configuration
$page_title = 'Start.gg Setup';
$base_path = '../../../';
require_once __DIR__ . '/../../../admin/includes/admin_head.php';
?>

<style>
    .setup-container {
        max-width: 800px;
        margin: 2em auto;
        padding: 2em;
        background: var(--dark-bg);
        border-radius: 10px;
    }
    .setup-step {
        margin: 2em 0;
    }
    .status-grid {
        display: grid;
        grid-template-columns: 1fr auto;
        gap: 1em;
        margin: 1em 0;
    }
    .status-item {
        display: flex;
        align-items: center;
        padding: 1em;
        background: var(--darker-bg);
        border-radius: 5px;
    }
    .status-ok { color: #00C851; }
    .status-error { color: #ff4444; }
    .btn-setup {
        background: var(--primary-color);
        color: white;
        padding: 0.75em 2em;
        border: none;
        border-radius: 5px;
        cursor: pointer;
        font-size: 1em;
        margin-top: 1em;
    }
    .btn-setup:hover {
        background: var(--hover-color);
    }
    .error-message {
        background: #ff444420;
        color: #ff4444;
        padding: 1em;
        border-radius: 5px;
        margin: 1em 0;
    }
    .success-message {
        background: #00C85120;
        color: #00C851;
        padding: 1em;
        border-radius: 5px;
        margin: 1em 0;
    }
    .form-group {
        margin: 1.5em 0;
    }
    .form-group label {
        display: block;
        margin-bottom: 0.5em;
        color: var(--light-color);
    }
    .form-group input[type="text"],
    .form-group input[type="password"] {
        width: 100%;
        padding: 0.75em;
        border: 1px solid #333;
        border-radius: 5px;
        background: var(--darker-bg);
        color: var(--light-color);
    }
    .step-indicator {
        display: flex;
        justify-content: space-between;
        margin: 2em 0;
    }
    .step-indicator-item {
        flex: 1;
        text-align: center;
        padding: 1em;
        background: var(--darker-bg);
        border-radius: 5px;
        margin: 0 0.5em;
    }
    .step-indicator-item.active {
        background: var(--primary-color);
    }
</style>

<div class="setup-container">
    <h1>🚀 Start.gg Integration Setup</h1>

    <?php if (!empty($errors)): ?>
        <?php foreach ($errors as $error): ?>
            <div class="error-message"><?= htmlspecialchars($error) ?></div>
        <?php endforeach; ?>
    <?php endif; ?>

    <?php if (!empty($success)): ?>
        <?php foreach ($success as $msg): ?>
            <div class="success-message"><?= htmlspecialchars($msg) ?></div>
        <?php endforeach; ?>
    <?php endif; ?>

    <div class="step-indicator">
        <div class="step-indicator-item <?= $step === 'welcome' ? 'active' : '' ?>">1. Welcome</div>
        <div class="step-indicator-item <?= $step === 'database' ? 'active' : '' ?>">2. Database</div>
        <div class="step-indicator-item <?= $step === 'configure' ? 'active' : '' ?>">3. Configure</div>
        <div class="step-indicator-item <?= $step === 'test' ? 'active' : '' ?>">4. Test</div>
        <div class="step-indicator-item <?= $step === 'complete' ? 'active' : '' ?>">5. Complete</div>
    </div>

    <?php if ($step === 'welcome'): ?>
        <div class="setup-step">
            <h2>Welcome to Start.gg Integration!</h2>
            <p>This setup wizard will help you integrate your tournaments from start.gg into your website.</p>

            <h3>Current Status:</h3>
            <div class="status-grid">
                <div class="status-item">
                    Database Tables:
                    <strong class="<?= $setupStatus['tables_exist'] ? 'status-ok' : 'status-error' ?>">
                        <?= $setupStatus['tables_exist'] ? '✓ Created' : '✗ Not Created' ?>
                    </strong>
                </div>
                <div class="status-item">
                    API Token:
                    <strong class="<?= $setupStatus['token_configured'] ? 'status-ok' : 'status-error' ?>">
                        <?= $setupStatus['token_configured'] ? '✓ Configured' : '✗ Not Configured' ?>
                    </strong>
                </div>
                <div class="status-item">
                    API Connection:
                    <strong class="<?= $setupStatus['connection_ok'] ? 'status-ok' : 'status-error' ?>">
                        <?= $setupStatus['connection_ok'] ? '✓ Working' : '✗ Not Tested' ?>
                    </strong>
                </div>
            </div>

            <h3>What will be configured:</h3>
            <ul>
                <li>5 database tables for storing tournament data</li>
                <li>Encrypted API token storage</li>
                <li>Automatic tournament synchronization</li>
                <li>Tournament display on your events page</li>
                <li>Direct registration integration</li>
            </ul>

            <?php if (!$setupStatus['tables_exist']): ?>
                <a href="?step=database" class="btn-setup">Begin Setup →</a>
            <?php elseif (!$setupStatus['token_configured']): ?>
                <a href="?step=configure" class="btn-setup">Configure API Token →</a>
            <?php elseif (!$setupStatus['connection_ok']): ?>
                <a href="?step=test" class="btn-setup">Test Connection →</a>
            <?php else: ?>
                <div class="success-message">
                    ✓ Setup is complete! You can now use the Start.gg integration.
                </div>
                <a href="../" class="btn-setup">Go to Dashboard →</a>
            <?php endif; ?>
        </div>

    <?php elseif ($step === 'database'): ?>
        <div class="setup-step">
            <h2>Step 2: Create Database Tables</h2>
            <p>Click the button below to create the required database tables.</p>

            <form method="POST">
                <input type="hidden" name="csrf_token" value="<?= htmlspecialchars(get_csrf_token()) ?>">
                <input type="hidden" name="action" value="create_tables">
                <button type="submit" class="btn-setup">Create Database Tables</button>
            </form>
        </div>

    <?php elseif ($step === 'configure'): ?>
        <div class="setup-step">
            <h2>Step 3: Configure API Token</h2>
            <p>Enter your Start.gg API token below. The token will be encrypted before storage.</p>

            <form method="POST">
                <input type="hidden" name="csrf_token" value="<?= htmlspecialchars(get_csrf_token()) ?>">
                <input type="hidden" name="action" value="save_config">

                <div class="form-group">
                    <label for="api_token">Start.gg API Token:</label>
                    <input type="password" id="api_token" name="api_token"
                           placeholder="Paste your API token here" required>
                    <small>Get your token from: <a href="https://start.gg/admin/profile/developer" target="_blank">start.gg/admin/profile/developer</a></small>
                </div>

                <button type="submit" class="btn-setup">Save Configuration</button>
            </form>
        </div>

    <?php elseif ($step === 'test'): ?>
        <div class="setup-step">
            <h2>Step 4: Test API Connection</h2>
            <p>Test the connection to start.gg API to ensure everything is configured correctly.</p>

            <form method="POST">
                <input type="hidden" name="csrf_token" value="<?= htmlspecialchars(get_csrf_token()) ?>">
                <input type="hidden" name="action" value="test_connection">
                <button type="submit" class="btn-setup">Test Connection</button>
            </form>
        </div>

    <?php elseif ($step === 'complete'): ?>
        <div class="setup-step">
            <h2>✓ Setup Complete!</h2>
            <div class="success-message">
                Your Start.gg integration is now configured and ready to use!
            </div>

            <h3>Next Steps:</h3>
            <ol>
                <li>Visit the <a href="../">Start.gg Dashboard</a> to sync tournaments</li>
                <li>Configure automatic sync schedule</li>
                <li>Set up OAuth for direct registration (optional)</li>
                <li>Customize tournament display settings</li>
            </ol>

            <a href="../" class="btn-setup">Go to Dashboard →</a>
        </div>
    <?php endif; ?>

    <div style="margin-top: 3em; padding-top: 2em; border-top: 1px solid #333;">
        <p><small>Need help? Check the documentation in <code>/admin/startgg/docs/</code></small></p>
    </div>
</div>

<?php require_once __DIR__ . '/../../../admin/includes/admin_footer.php'; ?>
