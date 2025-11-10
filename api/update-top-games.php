<?php
/**
 * GGLeap Top Games Updater
 * Bakersfield eSports - 2025
 *
 * Fetches top games by launches with cover images from GGLeap API
 *
 * Usage: php update-top-games.php
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

    // Top games settings
    'timeframe' => 'ThisMonth', // ThisMonth, Last7Days, etc.
    'limit' => 10, // Top 10 games
    'image_preference' => ['Image600x900', 'ImageBackground', 'LogoIcon'],

    // Rate limiting
    'request_delay' => 500000 // 500ms delay
];

// Excluded apps (launchers, utilities, browsers, etc.)
$EXCLUDED_APPS = [
    'Steam',
    'Epic Games Launcher',
    'Battle net',
    'Battle.net',
    'Riot Client',
    'Riot Games',
    'EA App',
    'Origin',
    'Ubisoft Connect',
    'GOG Galaxy',
    'Discord',
    'YouTube',
    'Spotify',
    'Chrome',
    'Firefox',
    'Edge',
    'Opera',
    'Sound Devices',
    'OBS Studio',
    'Streamlabs',
    'Xbox App',
    'GeForce Experience',
    'Radeon Software',
    'MSI Afterburner',
    'Task Manager',
    'Settings',
    'File Explorer',
    'Notepad',
    'Calculator'
];

// Cache file path
$CACHE_FILE = __DIR__ . '/../cache/top-games.json';
$LOG_FILE = __DIR__ . '/../cache/top-games-update.log';

// ============================================
// LOGGING
// ============================================

function logMessage($message) {
    global $LOG_FILE;
    $timestamp = date('Y-m-d H:i:s');
    $logEntry = "[$timestamp] $message\n";

    $logDir = dirname($LOG_FILE);
    if (!is_dir($logDir)) {
        mkdir($logDir, 0755, true);
    }
    file_put_contents($LOG_FILE, $logEntry, FILE_APPEND);

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
        logMessage("API request failed: HTTP $httpCode for $url");
        return null;
    }

    return json_decode($response, true);
}

// ============================================
// FETCH ENABLED APPS (for images/names)
// ============================================

function fetchEnabledApps($config, $jwtToken) {
    logMessage("Fetching enabled apps for image lookup...");

    $url = $config['api_base_url'] . '/apps/get-enabled-apps-summary';
    $response = makeGGLeapRequest($url, $jwtToken);

    if (!$response || !isset($response['Apps'])) {
        logMessage("Failed to fetch enabled apps");
        return [];
    }

    $apps = [];
    foreach ($response['Apps'] as $app) {
        $uuid = $app['Uuid'] ?? null;
        $mainUuid = $app['MainApplicationUuid'] ?? null;
        $name = $app['Name'] ?? 'Unknown';

        // Build image preference
        $imageUrl = null;
        foreach ($config['image_preference'] as $field) {
            if (!empty($app[$field])) {
                $imageUrl = $app[$field];
                break;
            }
        }

        $appData = [
            'uuid' => $uuid,
            'main_uuid' => $mainUuid,
            'name' => $name,
            'image_url' => $imageUrl
        ];

        // Index by UUID and main UUID
        if ($uuid) {
            $apps[$uuid] = $appData;
        }
        if ($mainUuid) {
            $apps[$mainUuid] = $appData;
        }

        // Also index by lowercase name for fallback
        $nameLower = strtolower($name);
        if (!isset($apps[$nameLower])) {
            $apps[$nameLower] = $appData;
        }
    }

    logMessage("Indexed " . count($response['Apps']) . " apps");
    return $apps;
}

// ============================================
// FETCH LAUNCH ACTIVITY LOGS
// ============================================

function fetchLaunchLogs($config, $jwtToken) {
    logMessage("Fetching game launch activity logs...");

    $launches = [];
    $paginationToken = null;
    $page = 0;

    do {
        $page++;
        $url = $config['api_base_url'] . '/activity-logs/search';
        $params = [
            'Actions[]' => 'LaunchedApplication',
            'TimeFrameType' => $config['timeframe'],
            'Limit' => 500
        ];

        if ($paginationToken) {
            $params['PaginationToken'] = $paginationToken;
        }

        $response = makeGGLeapRequest($url, $jwtToken, $params);

        if (!$response || !isset($response['Entries'])) {
            break;
        }

        logMessage("Processing page $page with " . count($response['Entries']) . " entries");

        // Count launches per app
        foreach ($response['Entries'] as $entry) {
            $appUuid = $entry['Data']['ApplicationUuid'] ?? null;
            $mainUuid = $entry['Data']['MainApplicationUuid'] ?? null;
            $appName = $entry['Data']['ApplicationName'] ?? 'Unknown';

            // Prefer UUID, fallback to name
            $key = $appUuid ?: ($mainUuid ?: strtolower($appName));

            if (!isset($launches[$key])) {
                $launches[$key] = [
                    'key' => $key,
                    'uuid' => $appUuid ?: $mainUuid,
                    'name' => $appName,
                    'count' => 0
                ];
            }

            $launches[$key]['count']++;
        }

        $paginationToken = $response['PaginationToken'] ?? null;

        // Safety: max 20 pages
        if ($page >= 20) {
            logMessage("Reached max page limit");
            break;
        }

    } while ($paginationToken);

    logMessage("Counted launches for " . count($launches) . " unique games");
    return array_values($launches);
}

// ============================================
// ENRICH WITH APP DATA
// ============================================

function enrichGamesWithImages($launches, $appsIndex) {
    logMessage("Enriching games with images...");

    $enriched = [];

    foreach ($launches as $launch) {
        $key = $launch['key'];
        $uuid = $launch['uuid'];
        $name = $launch['name'];
        $count = $launch['count'];

        // Try to find app data
        $appData = null;
        if ($uuid && isset($appsIndex[$uuid])) {
            $appData = $appsIndex[$uuid];
        } elseif (isset($appsIndex[strtolower($name)])) {
            $appData = $appsIndex[strtolower($name)];
        }

        $enriched[] = [
            'name' => $appData['name'] ?? $name,
            'app_uuid' => $uuid,
            'launches' => $count,
            'image_url' => $appData['image_url'] ?? null
        ];
    }

    logMessage("Enriched " . count($enriched) . " games");
    return $enriched;
}

// ============================================
// FILTER OUT NON-GAMES
// ============================================

function filterGames($games, $excludedApps) {
    logMessage("Filtering out launchers and non-game apps...");

    $filtered = [];
    $excludedCount = 0;

    foreach ($games as $game) {
        $gameName = $game['name'];

        // Check if game name matches any excluded app (case-insensitive)
        $isExcluded = false;
        foreach ($excludedApps as $excludedApp) {
            if (stripos($gameName, $excludedApp) !== false || stripos($excludedApp, $gameName) !== false) {
                $isExcluded = true;
                $excludedCount++;
                logMessage("  Excluded: $gameName");
                break;
            }
        }

        if (!$isExcluded) {
            $filtered[] = $game;
        }
    }

    logMessage("Filtered out $excludedCount apps, kept " . count($filtered) . " actual games");
    return $filtered;
}

// ============================================
// MAIN EXECUTION
// ============================================

logMessage("=== Top Games Update Started ===");
$startTime = microtime(true);

try {
    // Step 1: Authenticate
    $jwtToken = authenticateGGLeap($GGLEAP_CONFIG);

    if (!$jwtToken) {
        throw new Exception("Authentication failed");
    }

    // Step 2: Fetch enabled apps (for image lookup)
    $appsIndex = fetchEnabledApps($GGLEAP_CONFIG, $jwtToken);

    // Step 3: Fetch launch activity logs
    $launches = fetchLaunchLogs($GGLEAP_CONFIG, $jwtToken);

    // Step 4: Enrich with app images
    $games = enrichGamesWithImages($launches, $appsIndex);

    // Step 5: Filter out launchers and non-game apps
    $games = filterGames($games, $EXCLUDED_APPS);

    // Step 6: Sort by launch count descending
    usort($games, function($a, $b) {
        return $b['launches'] - $a['launches'];
    });

    // Step 7: Take top N
    $topGames = array_slice($games, 0, $GGLEAP_CONFIG['limit']);

    // Step 8: Compile result
    $result = [
        'top_games' => $topGames,
        'timeframe' => $GGLEAP_CONFIG['timeframe'],
        'timestamp' => time(),
        'last_updated' => date('Y-m-d H:i:s'),
        'processing_time_seconds' => round(microtime(true) - $startTime, 2)
    ];

    // Step 9: Save to cache
    $cacheDir = dirname($CACHE_FILE);
    if (!is_dir($cacheDir)) {
        mkdir($cacheDir, 0755, true);
    }

    file_put_contents($CACHE_FILE, json_encode($result, JSON_PRETTY_PRINT));

    logMessage("Cached top " . count($topGames) . " games");
    logMessage("Top game: " . ($topGames[0]['name'] ?? 'N/A') . " with " . ($topGames[0]['launches'] ?? 0) . " launches");

    $duration = round(microtime(true) - $startTime, 2);
    logMessage("=== Update Complete in {$duration} seconds ===");

    // Output JSON if accessed via web
    if (php_sapi_name() !== 'cli') {
        header('Content-Type: application/json');
        echo json_encode([
            'success' => true,
            'data' => $result,
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
