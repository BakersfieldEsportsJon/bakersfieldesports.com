<?php
/**
 * Start.gg Events API - Auto-Refreshing Cache
 * Bakersfield eSports - 2025
 *
 * This file serves cached tournament events and auto-updates hourly
 */

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET');

// Cache file path
$CACHE_FILE = __DIR__ . '/../cache/startgg-events.json';
$CACHE_MAX_AGE = 3600; // 1 hour (tournaments don't change frequently)

// ============================================
// FALLBACK DATA
// ============================================

function getFallbackData() {
    return [
        'tournament' => [
            'name' => 'Bakersfield eSports Center Tournaments',
            'url' => 'https://start.gg/tournament/bakersfield-esports-center-tournaments',
            'registration_open' => true
        ],
        'events' => [],
        'total_events' => 0,
        'timestamp' => time(),
        'using_fallback' => true,
        'message' => 'No tournament data available - check Start.gg page for updates'
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
            // Windows
            $phpPath = 'C:\\xampp\\php\\php.exe';
            $scriptPath = __DIR__ . '\\update-startgg-events.php';
            pclose(popen('start /B "' . $phpPath . '" "' . $scriptPath . '"', 'r'));
        } else {
            // Linux/Mac
            exec('php ' . escapeshellarg(__DIR__ . '/update-startgg-events.php') . ' > /dev/null 2>&1 &');
        }
    }

    // Load and serve cached data
    if (!file_exists($CACHE_FILE)) {
        echo json_encode(getFallbackData());
        exit;
    }

    $cacheData = file_get_contents($CACHE_FILE);
    $data = json_decode($cacheData, true);

    if (!$data) {
        echo json_encode(getFallbackData());
        exit;
    }

    // Add cache age info
    $cacheAge = time() - filemtime($CACHE_FILE);
    $data['cache_age_minutes'] = round($cacheAge / 60, 1);
    $data['from_cache'] = true;

    // Return cached data
    echo json_encode($data);

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'error' => 'Server error',
        'message' => $e->getMessage(),
        'fallback' => getFallbackData()
    ]);
}
?>
