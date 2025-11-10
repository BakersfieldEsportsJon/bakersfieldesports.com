<?php
/**
 * Get Events API - Optimized with caching and ETag support
 * Performance improvements:
 * - File caching with APCu
 * - ETag support for HTTP 304 responses
 * - Cache-Control headers for browser caching
 */

$eventsFile = __DIR__ . '/events.json';
$cacheKey = 'events_json_data';
$cacheTTL = 300; // 5 minutes

// Enable APCu caching if available
$cacheEnabled = function_exists('apcu_enabled') && apcu_enabled();

error_log('get_events.php called');

if (!file_exists($eventsFile)) {
    error_log('Events file not found at: ' . $eventsFile);
    http_response_code(404);
    header('Content-Type: application/json');
    echo json_encode(['error' => 'Events data not found']);
    exit;
}

// Get file modification time for ETag and caching
$fileModTime = filemtime($eventsFile);
$etag = md5($fileModTime . filesize($eventsFile));

// Check if client has a cached version (ETag)
$clientEtag = isset($_SERVER['HTTP_IF_NONE_MATCH']) ? trim($_SERVER['HTTP_IF_NONE_MATCH'], '"') : null;

if ($clientEtag === $etag) {
    // Client has the latest version, send 304 Not Modified
    http_response_code(304);
    header('ETag: "' . $etag . '"');
    exit;
}

// Try to get from APCu cache
$jsonOutput = false;
if ($cacheEnabled) {
    $cached = apcu_fetch($cacheKey);
    if ($cached !== false && $cached['mtime'] === $fileModTime) {
        $jsonOutput = $cached['content'];
        error_log('Events loaded from APCu cache');
    }
}

// If not in cache or cache is stale, read from file
if ($jsonOutput === false) {
    error_log('Loading events from file');

    // Read file contents
    $content = file_get_contents($eventsFile);

    // Remove BOM if present
    $content = preg_replace('/^\xEF\xBB\xBF/', '', $content);

    // Decode and re-encode to ensure valid JSON
    $data = json_decode($content, true);
    if ($data === null) {
        error_log('Invalid JSON: ' . json_last_error_msg());
        http_response_code(500);
        header('Content-Type: application/json');
        echo json_encode(['error' => 'Invalid JSON data']);
        exit;
    }

    // Re-encode with proper options
    $jsonOutput = json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);

    // Store in cache
    if ($cacheEnabled) {
        apcu_store($cacheKey, [
            'content' => $jsonOutput,
            'mtime' => $fileModTime
        ], $cacheTTL);
        error_log('Events cached in APCu');
    }
}

// Set caching headers
header('Content-Type: application/json');
header('ETag: "' . $etag . '"');
header('Cache-Control: public, max-age=300'); // 5 minutes browser cache
header('Last-Modified: ' . gmdate('D, d M Y H:i:s', $fileModTime) . ' GMT');

// Output the JSON
error_log('Events loaded successfully');
echo $jsonOutput;
?>
