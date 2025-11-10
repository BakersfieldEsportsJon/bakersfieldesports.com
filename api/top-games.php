<?php
/**
 * Top Games API - Auto-Refreshing Cache
 * Bakersfield eSports - 2025
 *
 * This file serves cached top games and auto-updates daily when cache expires
 */

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET');

// Cache file path
$CACHE_FILE = __DIR__ . '/../cache/top-games.json';
$CACHE_MAX_AGE = 86400; // 24 hours (daily refresh)

// ============================================
// FALLBACK DATA
// ============================================

function getFallbackTopGames() {
    return [
        'top_games' => [
            [
                'name' => 'Roblox',
                'app_uuid' => null,
                'launches' => 0,
                'image_url' => null
            ],
            [
                'name' => 'Fortnite',
                'app_uuid' => null,
                'launches' => 0,
                'image_url' => null
            ],
            [
                'name' => 'Minecraft',
                'app_uuid' => null,
                'launches' => 0,
                'image_url' => null
            ],
            [
                'name' => 'Valorant',
                'app_uuid' => null,
                'launches' => 0,
                'image_url' => null
            ],
            [
                'name' => 'League of Legends',
                'app_uuid' => null,
                'launches' => 0,
                'image_url' => null
            ]
        ],
        'timeframe' => 'ThisMonth',
        'timestamp' => time(),
        'using_fallback' => true,
        'message' => 'Using fallback data - update in progress'
    ];
}

// ============================================
// CHECK IF UPDATE NEEDED
// ============================================

function needsUpdate($cacheFile, $maxAge) {
    if (!file_exists($cacheFile)) {
        return true;
    }

    $cacheAge = time() - filemtime($cacheFile);
    return $cacheAge > $maxAge;
}

// ============================================
// MAIN EXECUTION
// ============================================

try {
    // Check if cache needs updating
    if (needsUpdate($CACHE_FILE, $CACHE_MAX_AGE)) {
        // Trigger background update (non-blocking)
        if (strtoupper(substr(PHP_OS, 0, 3)) === 'WIN') {
            // Windows - use full PHP path
            $phpPath = 'C:\\xampp\\php\\php.exe';
            $scriptPath = __DIR__ . '\\update-top-games.php';
            pclose(popen('start /B "' . $phpPath . '" "' . $scriptPath . '"', 'r'));
        } else {
            // Linux/Mac
            exec('php ' . escapeshellarg(__DIR__ . '/update-top-games.php') . ' > /dev/null 2>&1 &');
        }
    }

    // Load and serve cached data (or fallback if cache doesn't exist yet)
    if (!file_exists($CACHE_FILE)) {
        echo json_encode(getFallbackTopGames());
        exit;
    }

    $cacheData = file_get_contents($CACHE_FILE);
    $topGamesData = json_decode($cacheData, true);

    if (!$topGamesData) {
        echo json_encode(getFallbackTopGames());
        exit;
    }

    // Add cache age info
    $cacheAge = time() - filemtime($CACHE_FILE);
    $topGamesData['cache_age_hours'] = round($cacheAge / 3600, 1);
    $topGamesData['from_cache'] = true;

    // Return cached top games
    echo json_encode($topGamesData);

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'error' => 'Server error',
        'message' => $e->getMessage(),
        'fallback' => getFallbackTopGames()
    ]);
}
?>
