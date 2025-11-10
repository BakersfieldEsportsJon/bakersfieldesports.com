<?php
/**
 * Start.gg Integration Setup Script
 * Run this once to install database tables and initial configuration
 */

// Prevent direct access from web
if (php_sapi_name() !== 'cli' && !isset($_SESSION['user_id'])) {
    die('Access denied. Run from command line or as authenticated admin.');
}

require_once __DIR__ . '/../../../admin/includes/db.php';
require_once __DIR__ . '/../../../includes/startgg/StartGGConfig.php';

echo "========================================\n";
echo "Start.gg Integration Setup\n";
echo "========================================\n\n";

// Step 1: Create tables
echo "Step 1: Creating database tables...\n";

$sqlFile = __DIR__ . '/../migrations/001_create_startgg_tables.sql';

if (!file_exists($sqlFile)) {
    die("ERROR: Migration file not found: $sqlFile\n");
}

$sql = file_get_contents($sqlFile);

// Split by semicolons to execute each statement separately
$statements = array_filter(
    array_map('trim', explode(';', $sql)),
    function($stmt) {
        return !empty($stmt) && substr($stmt, 0, 2) !== '--';
    }
);

$successCount = 0;
$errorCount = 0;

foreach ($statements as $statement) {
    try {
        $pdo->exec($statement);
        $successCount++;
    } catch (PDOException $e) {
        // Ignore "table already exists" errors
        if (strpos($e->getMessage(), 'already exists') === false) {
            echo "  ERROR: " . $e->getMessage() . "\n";
            $errorCount++;
        } else {
            echo "  SKIP: Table already exists\n";
        }
    }
}

echo "  Executed $successCount SQL statements\n";
if ($errorCount > 0) {
    echo "  $errorCount errors encountered\n";
}
echo "  ✓ Database tables created\n\n";

// Step 2: Store API token (encrypted)
echo "Step 2: Storing API token...\n";

$apiToken = 'd82b02d30c0c7cca724e04c3d4b47250'; // From user
$config = new StartGGConfig($pdo);

if ($config->saveApiToken($apiToken)) {
    echo "  ✓ API token encrypted and stored\n\n";
} else {
    die("  ERROR: Failed to save API token\n");
}

// Step 3: Test connection
echo "Step 3: Testing API connection...\n";

try {
    require_once __DIR__ . '/../../../includes/startgg/StartGGClient.php';

    $token = $config->getApiToken();
    $client = new StartGGClient($token);

    // Test with a simple query
    $result = $client->getCurrentUser();

    if (isset($result['data']['currentUser'])) {
        $user = $result['data']['currentUser'];
        echo "  ✓ Connection successful!\n";
        echo "  Authenticated as: " . ($user['name'] ?? 'Unknown') . "\n";
        echo "  User ID: " . ($user['id'] ?? 'Unknown') . "\n\n";
    } else {
        echo "  WARNING: Unexpected response from API\n";
        echo "  Response: " . json_encode($result) . "\n\n";
    }

} catch (Exception $e) {
    echo "  ERROR: " . $e->getMessage() . "\n\n";
    die("Setup failed at API test step\n");
}

// Step 4: Verify owner ID
echo "Step 4: Verifying owner configuration...\n";

$ownerId = $config->getOwnerId();
if ($ownerId) {
    echo "  ✓ Owner ID configured: $ownerId\n\n";
} else {
    echo "  WARNING: Owner ID not found in database\n";
    echo "  Attempting to insert...\n";

    try {
        $stmt = $pdo->prepare("
            UPDATE startgg_config
            SET owner_id = '6e4bd725'
            WHERE id = 1
        ");
        $stmt->execute();
        echo "  ✓ Owner ID updated\n\n";
    } catch (PDOException $e) {
        echo "  ERROR: " . $e->getMessage() . "\n\n";
    }
}

// Step 5: Test fetching tournaments
echo "Step 5: Testing tournament fetch...\n";

try {
    $ownerId = '6e4bd725';
    echo "  Fetching tournaments for owner ID: $ownerId\n";

    $result = $client->getTournamentsByOwner($ownerId, 1, 5);

    if (isset($result['data']['user']['tournaments']['nodes'])) {
        $tournaments = $result['data']['user']['tournaments']['nodes'];
        $count = count($tournaments);
        echo "  ✓ Found $count tournaments\n";

        if ($count > 0) {
            echo "\n  Sample tournament:\n";
            $sample = $tournaments[0];
            echo "  - Name: " . ($sample['name'] ?? 'N/A') . "\n";
            echo "  - Slug: " . ($sample['slug'] ?? 'N/A') . "\n";
            echo "  - Start: " . ($sample['startAt'] ? date('Y-m-d H:i', $sample['startAt']) : 'N/A') . "\n";
            echo "  - Registration Open: " . ($sample['isRegistrationOpen'] ? 'Yes' : 'No') . "\n";
        }
        echo "\n";
    } else {
        echo "  WARNING: No tournaments found or unexpected response\n";
        echo "  This might be normal if you haven't created any tournaments yet\n\n";
    }

} catch (Exception $e) {
    echo "  ERROR: " . $e->getMessage() . "\n\n";
}

// Summary
echo "========================================\n";
echo "Setup Complete!\n";
echo "========================================\n\n";

echo "Next steps:\n";
echo "1. Visit /admin/startgg/ to access the dashboard\n";
echo "2. Run your first tournament sync\n";
echo "3. Configure OAuth settings for registration\n\n";

echo "Setup log saved to: " . __DIR__ . "/install.log\n";

// Save setup log
$logContent = ob_get_contents();
file_put_contents(__DIR__ . '/install.log', $logContent);
