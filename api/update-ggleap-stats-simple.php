<?php
/**
 * GGLeap Stats Updater - SIMPLIFIED VERSION (Users Only)
 * Bakersfield eSports - 2025
 *
 * This script fetches ONLY user count stats (fast, no rate limits)
 * Run this daily via cron or manually
 *
 * Usage: php update-ggleap-stats-simple.php
 * Or visit: http://localhost/bakersfield/api/update-ggleap-stats-simple.php
 */

set_time_limit(300); // 5 minutes max

// ============================================
// CONFIGURATION
// ============================================

$GGLEAP_CONFIG = [
    // API token
    'auth_token' => 'A5n07/WXFkneqxj6G9pwn5KRk8G7NKXlqvZs5Onm960wiqjm6FKn/5BdWLII/CcQsS8peOEtotJet9MQ0mpg1hj1BOGYzWuZt2VXKAie/sX+aszsjam9ZSh++J29hxnM',

    // API endpoints
    'api_base_url' => 'https://api.ggleap.com/production',
    'auth_endpoint' => '/authorization/public-api/auth',

    // Rate limiting
    'request_delay' => 500000 // 500ms delay between requests
];

// Cache file path
$CACHE_FILE = __DIR__ . '/../cache/ggleap-stats.json';
$LOG_FILE = __DIR__ . '/../cache/ggleap-update.log';

// ============================================
// LOGGING
// ============================================

function logMessage($message) {
    global $LOG_FILE;
    $timestamp = date('Y-m-d H:i:s');
    $logEntry = "[$timestamp] $message\n";

    // Log to file
    $logDir = dirname($LOG_FILE);
    if (!is_dir($logDir)) {
        mkdir($logDir, 0755, true);
    }
    file_put_contents($LOG_FILE, $logEntry, FILE_APPEND);

    // Also output to console if running from CLI
    if (php_sapi_name() === 'cli') {
        echo $logEntry;
    }
}

// ============================================
// AUTHENTICATION
// ============================================

function authenticateGGLeap($config) {
    logMessage("Authenticating with GGLeap API...");

    $authUrl = $config['api_base_url'] . $config['auth_endpoint'];

    // Prepare POST body with AuthToken
    $postData = json_encode([
        'AuthToken' => $config['auth_token']
    ]);

    $ch = curl_init($authUrl);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_HTTPHEADER, [
        'Content-Type: application/json',
        'Content-Length: ' . strlen($postData)
    ]);
    curl_setopt($ch, CURLOPT_POSTFIELDS, $postData);
    curl_setopt($ch, CURLOPT_TIMEOUT, 30);

    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);

    if ($httpCode !== 200) {
        logMessage("Authentication failed with HTTP $httpCode");
        return null;
    }

    $data = json_decode($response, true);

    if (!isset($data['Jwt'])) {
        logMessage("No JWT in authentication response");
        return null;
    }

    logMessage("Authentication successful");
    return $data['Jwt'];
}

// ============================================
// API REQUEST HELPER
// ============================================

function makeGGLeapRequest($url, $jwtToken, $queryParams = []) {
    global $GGLEAP_CONFIG;

    // Build URL with query parameters
    if (!empty($queryParams)) {
        $url .= '?' . http_build_query($queryParams);
    }

    $ch = curl_init($url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_HTTPHEADER, [
        'Content-Type: application/json',
        'Authorization: Bearer ' . $jwtToken,
        'Accept-Encoding: gzip'
    ]);
    curl_setopt($ch, CURLOPT_ENCODING, 'gzip');
    curl_setopt($ch, CURLOPT_TIMEOUT, 30);

    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);

    usleep($GGLEAP_CONFIG['request_delay']);

    if ($httpCode !== 200) {
        logMessage("API request failed: HTTP $httpCode");
        return null;
    }

    return json_decode($response, true);
}

// ============================================
// FETCH USER COUNT
// ============================================

function fetchTotalUsers($config, $jwtToken) {
    logMessage("Fetching total user count...");

    $url = $config['api_base_url'] . '/users/summaries';
    $response = makeGGLeapRequest($url, $jwtToken);

    if (!$response || !isset($response['Users'])) {
        logMessage("Failed to fetch users");
        return 0;
    }

    $count = count($response['Users']);
    logMessage("Total registered users: $count");

    return $count;
}

// ============================================
// FETCH NEW PLAYERS THIS WEEK
// ============================================

function fetchNewPlayersThisWeek($config, $jwtToken) {
    logMessage("Fetching new players in last 7 days...");

    $uniqueUsers = [];
    $paginationToken = null;
    $page = 0;

    do {
        $page++;
        $url = $config['api_base_url'] . '/activity-logs/search';
        $params = [
            'TimeFrameType' => 'Last7Days',
            'Limit' => 500,
            'Actions[]' => 'CreatedUser',
            'GuestOnly' => 'false'
        ];

        if ($paginationToken) {
            $params['PaginationToken'] = $paginationToken;
        }

        $response = makeGGLeapRequest($url, $jwtToken, $params);

        if (!$response || !isset($response['Entries'])) {
            break;
        }

        // Collect unique user UUIDs
        foreach ($response['Entries'] as $entry) {
            if (isset($entry['UserUuid'])) {
                $uniqueUsers[$entry['UserUuid']] = true;
            }
        }

        $paginationToken = $response['PaginationToken'] ?? null;

        // Safety: max 10 pages
        if ($page >= 10) break;

    } while ($paginationToken);

    $count = count($uniqueUsers);
    logMessage("New players in last 7 days: $count");

    return $count;
}

// ============================================
// MAIN EXECUTION
// ============================================

logMessage("=== GGLeap Stats Update (Simple) Started ===");
$startTime = microtime(true);

try {
    // Step 1: Authenticate
    $jwtToken = authenticateGGLeap($GGLEAP_CONFIG);

    if (!$jwtToken) {
        throw new Exception("Authentication failed");
    }

    // Step 2: Fetch total users
    $totalUsers = fetchTotalUsers($GGLEAP_CONFIG, $jwtToken);

    // Step 3: Fetch new players this week
    $newPlayersThisWeek = fetchNewPlayersThisWeek($GGLEAP_CONFIG, $jwtToken);

    // Step 4: Compile stats
    $stats = [
        'totalAccounts' => $totalUsers,
        'newAccountsToday' => $newPlayersThisWeek,
        'timestamp' => time(),
        'using_fallback' => false,
        'last_updated' => date('Y-m-d H:i:s'),
        'processing_time_seconds' => round(microtime(true) - $startTime, 2),
        'note' => 'User counts only (gaming hours not available due to API limits)'
    ];

    // Step 5: Save to cache
    $cacheDir = dirname($CACHE_FILE);
    if (!is_dir($cacheDir)) {
        mkdir($cacheDir, 0755, true);
    }

    file_put_contents($CACHE_FILE, json_encode($stats, JSON_PRETTY_PRINT));

    logMessage("Stats cached successfully");
    logMessage("Total accounts: {$stats['totalAccounts']}");
    logMessage("New players in last 7 days: {$stats['newAccountsToday']}");

    $duration = round(microtime(true) - $startTime, 2);
    logMessage("=== Update Complete in {$duration} seconds ===");

    // Output JSON if accessed via web
    if (php_sapi_name() !== 'cli') {
        header('Content-Type: application/json');
        echo json_encode([
            'success' => true,
            'stats' => $stats,
            'duration_seconds' => $duration
        ]);
    }

} catch (Exception $e) {
    logMessage("ERROR: " . $e->getMessage());
    logMessage("=== Update Failed ===");

    if (php_sapi_name() !== 'cli') {
        header('Content-Type: application/json');
        http_response_code(500);
        echo json_encode([
            'success' => false,
            'error' => $e->getMessage()
        ]);
    }

    exit(1);
}
?>
