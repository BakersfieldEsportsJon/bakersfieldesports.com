<?php
/**
 * GGLeap Stats API - Auto-Refreshing Cache
 * Bakersfield eSports - 2025
 *
 * This file serves cached stats and auto-updates daily when cache expires
 */

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET');

// Cache file path
$CACHE_FILE = __DIR__ . '/../cache/ggleap-stats.json';
$CACHE_MAX_AGE = 86400; // 24 hours (daily refresh)

// ============================================
// FALLBACK DATA
// ============================================

function getFallbackStats() {
    return [
        'totalAccounts' => 5804,
        'newAccountsToday' => 8,
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
        // This runs the update script in the background
        if (strtoupper(substr(PHP_OS, 0, 3)) === 'WIN') {
            // Windows - use full PHP path
            $phpPath = 'C:\\xampp\\php\\php.exe';
            $scriptPath = __DIR__ . '\\update-ggleap-stats-simple.php';
            pclose(popen('start /B "' . $phpPath . '" "' . $scriptPath . '"', 'r'));
        } else {
            // Linux/Mac
            exec('php ' . escapeshellarg(__DIR__ . '/update-ggleap-stats-simple.php') . ' > /dev/null 2>&1 &');
        }
    }

    // Load and serve cached data (or fallback if cache doesn't exist yet)
    if (!file_exists($CACHE_FILE)) {
        echo json_encode(getFallbackStats());
        exit;
    }

    $cacheData = file_get_contents($CACHE_FILE);
    $stats = json_decode($cacheData, true);

    if (!$stats) {
        echo json_encode(getFallbackStats());
        exit;
    }

    // Add cache age info
    $cacheAge = time() - filemtime($CACHE_FILE);
    $stats['cache_age_hours'] = round($cacheAge / 3600, 1);
    $stats['from_cache'] = true;

    // Return cached stats
    echo json_encode($stats);

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'error' => 'Server error',
        'message' => $e->getMessage(),
        'fallback' => getFallbackStats()
    ]);
}
?>
