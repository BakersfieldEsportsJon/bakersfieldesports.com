<?php
/**
 * GGLeap Stats Updater - Background Script
 * Bakersfield eSports - 2025
 *
 * This script fetches all stats from GGLeap API and caches them.
 * Run this script once per day via cron or manually.
 *
 * Usage: php update-ggleap-stats.php
 * Or visit: http://localhost/bakersfield/api/update-ggleap-stats.php
 */

// Set execution time limit to 30 minutes (for 5000+ users)
set_time_limit(1800);
ini_set('memory_limit', '256M');

// ============================================
// CONFIGURATION
// ============================================

$GGLEAP_CONFIG = [
    // API token
    'auth_token' => 'A5n07/WXFkneqxj6G9pwn5KRk8G7NKXlqvZs5Onm960wiqjm6FKn/5BdWLII/CcQsS8peOEtotJet9MQ0mpg1hj1BOGYzWuZt2VXKAie/sX+aszsjam9ZSh++J29hxnM',

    // API endpoints
    'api_base_url' => 'https://api.ggleap.com/production',
    'auth_endpoint' => '/authorization/public-api/auth',

    // Your opening date
    'opening_date' => '2021-10-01T00:00:00Z',

    // Batch processing
    'batch_size' => 50, // Process users in smaller batches
    'max_users' => 10000, // Increased to handle all users
    'request_delay' => 1000000, // 1 second delay between requests
    'retry_on_429' => true, // Retry if rate limited
    'retry_delay' => 10000000, // 10 seconds wait on rate limit (microseconds)

    // Sampling for gaming hours (much faster than processing all users)
    'sample_size' => 500, // Reduced sample size to avoid rate limits
    'use_sampling' => true // Use sampling instead of processing all users
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
        logMessage("Response: $response");
        return null;
    }

    $data = json_decode($response, true);

    // API returns 'Jwt' not 'Token'
    if (!isset($data['Jwt'])) {
        logMessage("No JWT in authentication response");
        logMessage("Full response: " . print_r($data, true));
        return null;
    }

    logMessage("Authentication successful");
    logMessage("Center UUID: " . ($data['CenterUuid'] ?? 'unknown'));
    return $data['Jwt'];
}

// ============================================
// API REQUEST HELPER
// ============================================

function makeGGLeapRequest($url, $jwtToken, $queryParams = [], $retryCount = 0) {
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
    curl_setopt($ch, CURLOPT_ENCODING, 'gzip'); // Auto-decompress gzip
    curl_setopt($ch, CURLOPT_TIMEOUT, 30);

    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $curlError = curl_error($ch);
    curl_close($ch);

    // Rate limiting - wait after every request
    usleep($GGLEAP_CONFIG['request_delay']);

    // Handle rate limiting (HTTP 429)
    if ($httpCode === 429 && $GGLEAP_CONFIG['retry_on_429'] && $retryCount < 3) {
        logMessage("Rate limited (429), waiting 5 seconds before retry " . ($retryCount + 1) . "/3...");
        usleep($GGLEAP_CONFIG['retry_delay']);
        return makeGGLeapRequest($url, $jwtToken, [], $retryCount + 1);
    }

    // Handle server errors (HTTP 500) - skip and continue
    if ($httpCode === 500) {
        logMessage("Server error (500) for request, skipping: " . parse_url($url, PHP_URL_PATH));
        return null;
    }

    if ($httpCode !== 200) {
        logMessage("API request failed: $url (HTTP $httpCode)");
        if ($curlError) {
            logMessage("cURL error: $curlError");
        }
        logMessage("Response: " . substr($response, 0, 300));
        return null;
    }

    $data = json_decode($response, true);
    if ($data === null) {
        logMessage("Failed to parse JSON response from: $url");
        logMessage("Response: " . substr($response, 0, 300));
    }

    return $data;
}

// ============================================
// FETCH ALL USERS
// ============================================

function fetchAllUsers($config, $jwtToken) {
    logMessage("Fetching all users...");

    $users = [];
    $paginationToken = null;
    $page = 0;

    do {
        $page++;
        logMessage("Fetching users page $page...");

        $url = $config['api_base_url'] . '/users/summaries';
        $params = [];

        if ($paginationToken) {
            $params['PaginationToken'] = $paginationToken;
        }

        $response = makeGGLeapRequest($url, $jwtToken, $params);

        if (!$response || !isset($response['Users'])) {
            logMessage("Failed to fetch users page $page");
            break;
        }

        $pageUsers = $response['Users'];
        $users = array_merge($users, $pageUsers);

        logMessage("Fetched " . count($pageUsers) . " users (total: " . count($users) . ")");

        // Get next pagination token
        $paginationToken = $response['PaginationToken'] ?? null;

        // Safety check
        if (count($users) >= $config['max_users']) {
            logMessage("Reached max user limit: " . $config['max_users']);
            break;
        }

    } while ($paginationToken);

    logMessage("Total users fetched: " . count($users));
    return $users;
}

// ============================================
// FETCH NEW PLAYERS LAST 30 DAYS
// ============================================

function fetchNewPlayersLast30Days($config, $jwtToken) {
    logMessage("Fetching new players in last 30 days...");

    $uniqueUsers = [];
    $paginationToken = null;
    $page = 0;

    // Calculate date range (last 30 days)
    $endDate = gmdate('Y-m-d\TH:i:s\Z'); // Now in UTC
    $startDate = gmdate('Y-m-d\TH:i:s\Z', strtotime('-30 days')); // 30 days ago

    logMessage("Date range: $startDate to $endDate");

    do {
        $page++;
        $url = $config['api_base_url'] . '/activity-logs/search';
        $params = [
            'Start' => $startDate,
            'End' => $endDate,
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

        logMessage("Page $page: Found " . count($response['Entries']) . " entries (unique users so far: " . count($uniqueUsers) . ")");

        $paginationToken = $response['PaginationToken'] ?? null;

    } while ($paginationToken);

    $count = count($uniqueUsers);
    logMessage("New players in last 30 days: $count");

    return $count;
}

// ============================================
// FETCH GAMING HOURS (SAMPLE-BASED ESTIMATE)
// ============================================

function fetchGamingHours($config, $jwtToken, $users) {
    $totalUsers = count($users);

    if ($config['use_sampling']) {
        // Use sampling for faster results
        $sampleSize = min($config['sample_size'], $totalUsers);
        logMessage("Using sample-based estimation: $sampleSize users out of $totalUsers total");
        logMessage("Expected time: ~" . round(($sampleSize * 0.6) / 60, 1) . " minutes");

        // Randomly select users for sampling
        $sampledUsers = [];
        $userIndices = range(0, $totalUsers - 1);
        shuffle($userIndices);
        $selectedIndices = array_slice($userIndices, 0, $sampleSize);

        foreach ($selectedIndices as $index) {
            $sampledUsers[] = $users[$index];
        }

        $users = $sampledUsers;
    } else {
        logMessage("Processing ALL $totalUsers users...");
        logMessage("WARNING: This may take 60-120 minutes for 5000+ users");
    }

    $totalSeconds = 0;
    $monthSeconds = 0;
    $processedUsers = 0;
    $failedUsers = 0;

    // Current month start
    $monthStart = date('Y-m-01') . 'T00:00:00Z';
    $now = gmdate('Y-m-d\TH:i:s\Z');

    // Process each user
    foreach ($users as $user) {
        if (!isset($user['Uuid'])) continue;

        $userUuid = $user['Uuid'];
        $url = $config['api_base_url'] . '/sessions/get-user-sessions';

        // Fetch all-time sessions
        $response = makeGGLeapRequest($url, $jwtToken, [
            'UserUuid' => $userUuid,
            'Start' => $config['opening_date'],
            'End' => $now
        ]);

        if ($response && isset($response['Sessions'])) {
            foreach ($response['Sessions'] as $session) {
                $totalSeconds += (int)($session['Seconds'] ?? 0);
            }
        } else {
            $failedUsers++;
        }

        // Fetch this month's sessions
        $response = makeGGLeapRequest($url, $jwtToken, [
            'UserUuid' => $userUuid,
            'Start' => $monthStart,
            'End' => $now
        ]);

        if ($response && isset($response['Sessions'])) {
            foreach ($response['Sessions'] as $session) {
                $monthSeconds += (int)($session['Seconds'] ?? 0);
            }
        }

        $processedUsers++;

        // Progress update every 50 users
        if ($processedUsers % 50 === 0) {
            $percentComplete = round(($processedUsers / count($users)) * 100, 1);
            logMessage("Progress: $processedUsers/" . count($users) . " sampled ($percentComplete%)");
        }
    }

    // Calculate hours from sample
    $sampleTotalHours = round($totalSeconds / 3600, 2);
    $sampleMonthHours = round($monthSeconds / 3600, 2);

    $successfulUsers = $processedUsers - $failedUsers;
    logMessage("Successfully processed: $successfulUsers/$processedUsers users");

    if ($config['use_sampling'] && $successfulUsers > 0) {
        // Extrapolate to total population
        $scaleFactor = $totalUsers / $successfulUsers;
        $estimatedTotalHours = round($sampleTotalHours * $scaleFactor);
        $estimatedMonthHours = round($sampleMonthHours * $scaleFactor);

        logMessage("Sample results: $sampleTotalHours total hours, $sampleMonthHours month hours");
        logMessage("Estimated total: $estimatedTotalHours hours (all-time), $estimatedMonthHours hours (this month)");
        logMessage("Scale factor: " . round($scaleFactor, 2) . "x");

        return [
            'total_hours' => $estimatedTotalHours,
            'month_hours' => $estimatedMonthHours,
            'is_estimate' => true,
            'sample_size' => $successfulUsers,
            'total_users' => $totalUsers
        ];
    } else {
        // Full calculation (no sampling)
        logMessage("Total gaming hours: $sampleTotalHours");
        logMessage("This month's hours: $sampleMonthHours");

        return [
            'total_hours' => (int)$sampleTotalHours,
            'month_hours' => (int)$sampleMonthHours,
            'is_estimate' => false
        ];
    }
}

// ============================================
// MAIN EXECUTION
// ============================================

logMessage("=== GGLeap Stats Update Started ===");
$startTime = microtime(true);

try {
    // Step 1: Authenticate
    $jwtToken = authenticateGGLeap($GGLEAP_CONFIG);

    if (!$jwtToken) {
        throw new Exception("Authentication failed");
    }

    // Step 2: Fetch all users
    $users = fetchAllUsers($GGLEAP_CONFIG, $jwtToken);
    $totalUsers = count($users);

    // Step 3: Count new players in last 30 days
    $newPlayersLast30Days = fetchNewPlayersLast30Days($GGLEAP_CONFIG, $jwtToken);

    // Step 4: Calculate gaming hours
    $hours = fetchGamingHours($GGLEAP_CONFIG, $jwtToken, $users);

    // Step 5: Compile stats
    $stats = [
        'totalAccounts' => $totalUsers,
        'newAccountsLast30Days' => $newPlayersLast30Days,
        'totalHours' => (int)$hours['total_hours'],
        'hoursThisMonth' => (int)$hours['month_hours'],
        'timestamp' => time(),
        'using_fallback' => false,
        'last_updated' => date('Y-m-d H:i:s'),
        'processing_time_minutes' => round((microtime(true) - $startTime) / 60, 2)
    ];

    // Step 6: Save to cache
    $cacheDir = dirname($CACHE_FILE);
    if (!is_dir($cacheDir)) {
        mkdir($cacheDir, 0755, true);
    }

    file_put_contents($CACHE_FILE, json_encode($stats, JSON_PRETTY_PRINT));

    logMessage("Stats cached successfully");
    logMessage("Total accounts: {$stats['totalAccounts']}");
    logMessage("New players in last 30 days: {$stats['newAccountsLast30Days']}");
    logMessage("Total hours: {$stats['totalHours']}");
    logMessage("Hours this month: {$stats['hoursThisMonth']}");

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
