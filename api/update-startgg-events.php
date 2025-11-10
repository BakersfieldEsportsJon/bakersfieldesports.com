<?php
/**
 * Start.gg Events Updater
 * Bakersfield eSports - 2025
 *
 * Fetches upcoming tournament events from Start.gg API
 *
 * Usage: php update-startgg-events.php
 */

set_time_limit(60); // 1 minute max

// ============================================
// CONFIGURATION
// ============================================

$STARTGG_CONFIG = [
    // API credentials
    'api_token' => '42746cbd9160c46f1301391db68c45ac',
    'api_endpoint' => 'https://api.start.gg/gql/alpha',

    // User settings
    'user_slug' => 'user/6e4bd725', // BakersfieldeSportsCenter user ID

    // Display settings
    'max_events' => 10, // Maximum events to display
    'timezone' => 'America/Los_Angeles' // Your local timezone
];

// Cache file path
$CACHE_FILE = __DIR__ . '/../cache/startgg-events.json';
$LOG_FILE = __DIR__ . '/../cache/startgg-events-update.log';

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
// START.GG GRAPHQL REQUEST
// ============================================

function makeStartGGRequest($config, $query, $variables = []) {
    logMessage("Making Start.gg GraphQL request...");

    $postData = json_encode([
        'query' => $query,
        'variables' => $variables
    ]);

    $ch = curl_init($config['api_endpoint']);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_HTTPHEADER, [
        'Authorization: Bearer ' . $config['api_token'],
        'Content-Type: application/json',
        'Content-Length: ' . strlen($postData)
    ]);
    curl_setopt($ch, CURLOPT_POSTFIELDS, $postData);
    curl_setopt($ch, CURLOPT_TIMEOUT, 30);

    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $curlError = curl_error($ch);
    curl_close($ch);

    logMessage("HTTP Code: $httpCode");

    if ($curlError) {
        logMessage("cURL Error: " . $curlError);
        return null;
    }

    if ($httpCode !== 200) {
        logMessage("Start.gg API request failed with HTTP $httpCode");
        logMessage("Full Response: " . $response);
        return null;
    }

    $data = json_decode($response, true);

    // Log the full decoded response for debugging
    logMessage("Decoded response: " . json_encode($data));

    if (isset($data['errors'])) {
        logMessage("GraphQL errors: " . json_encode($data['errors']));
        return null;
    }

    return $data['data'] ?? null;
}

// ============================================
// FETCH TOURNAMENT AND EVENTS
// ============================================

function fetchTournamentEvents($config) {
    logMessage("Fetching upcoming tournaments from Start.gg user account...");

    // Query to get upcoming tournaments from specific user
    $currentTime = time();

    $query = <<<'GRAPHQL'
query UserTournaments($slug: String!) {
  user(slug: $slug) {
    id
    name
    tournaments(query: {
      perPage: 20
      filter: {
        upcoming: true
      }
    }) {
      nodes {
        id
        name
        slug
        timezone
        startAt
        endAt
        isRegistrationOpen
        registrationClosesAt
        images {
          url
          type
        }
        events {
          id
          name
          slug
          startAt
          numEntrants
          state
          isOnline
          videogame {
            id
            name
            images {
              url
              type
            }
          }
        }
      }
    }
  }
}
GRAPHQL;

    $variables = [
        'slug' => $config['user_slug']
    ];

    $response = makeStartGGRequest($config, $query, $variables);

    if (!$response || !isset($response['user']['tournaments']['nodes'])) {
        logMessage("Failed to fetch tournament data from user");
        logMessage("Response structure: " . json_encode($response));
        return null;
    }

    $tournaments = $response['user']['tournaments']['nodes'];
    $userName = $response['user']['name'] ?? 'Bakersfield eSports Center';
    logMessage("User: " . $userName);
    logMessage("Tournaments found: " . count($tournaments));

    // Collect all events from all tournaments
    $allEvents = [];
    foreach ($tournaments as $tournament) {
        if (isset($tournament['events']) && !empty($tournament['events'])) {
            foreach ($tournament['events'] as $event) {
                // Add tournament context to each event
                $event['tournament_name'] = $tournament['name'];
                $event['tournament_slug'] = $tournament['slug'];
                $event['tournament_registration_open'] = $tournament['isRegistrationOpen'] ?? false;
                $event['tournament_registration_closes_at'] = $tournament['registrationClosesAt'] ?? null;
                $allEvents[] = $event;
            }
        }
    }

    logMessage("Total events found across all tournaments: " . count($allEvents));

    // Return a combined structure
    return [
        'name' => $userName . ' Tournaments',
        'slug' => $config['user_slug'],
        'events' => $allEvents,
        'tournaments' => $tournaments
    ];
}

// ============================================
// PROCESS AND FILTER EVENTS
// ============================================

function processEvents($tournament, $config) {
    logMessage("Processing tournament events...");

    if (!isset($tournament['events']) || empty($tournament['events'])) {
        logMessage("No events found in tournament");
        return [];
    }

    $events = [];
    $now = time();

    foreach ($tournament['events'] as $event) {
        // Get event start time
        $startAt = $event['startAt'] ?? null;

        if (!$startAt) {
            logMessage("Skipping event without start time: " . $event['name']);
            continue;
        }

        // Only include upcoming events (not past)
        if ($startAt < $now) {
            logMessage("Skipping past event: " . $event['name']);
            continue;
        }

        // Format event data
        $processedEvent = [
            'id' => $event['id'],
            'name' => $event['name'],
            'slug' => $event['slug'],
            'game' => $event['videogame']['name'] ?? 'Unknown Game',
            'game_id' => $event['videogame']['id'] ?? null,
            'game_image' => getGameImage($event['videogame']),
            'start_timestamp' => $startAt,
            'start_date' => date('Y-m-d', $startAt),
            'start_time' => date('g:i A', $startAt),
            'start_datetime' => date('l, F j, Y \a\t g:i A', $startAt),
            'tournament_name' => $event['tournament_name'] ?? 'Unknown Tournament',
            'num_entrants' => $event['numEntrants'] ?? 0,
            'is_online' => $event['isOnline'] ?? false,
            'state' => $event['state'] ?? 'UNKNOWN',
            'registration_open' => $event['tournament_registration_open'] ?? false,
            'registration_closes_at' => $event['tournament_registration_closes_at'] ?? null,
            'registration_url' => "https://start.gg/" . $event['slug'] . "/register",
            'event_url' => "https://start.gg/" . $event['slug']
        ];

        $events[] = $processedEvent;
    }

    // Sort by start date (earliest first)
    usort($events, function($a, $b) {
        return $a['start_timestamp'] - $b['start_timestamp'];
    });

    // Limit to max events
    if (count($events) > $config['max_events']) {
        $events = array_slice($events, 0, $config['max_events']);
    }

    logMessage("Processed " . count($events) . " upcoming events");

    return $events;
}

// ============================================
// HELPER FUNCTIONS
// ============================================

function getGameImage($videogame) {
    if (!isset($videogame['images']) || empty($videogame['images'])) {
        return null;
    }

    // Try to get the best image
    foreach ($videogame['images'] as $image) {
        if (isset($image['url'])) {
            return $image['url'];
        }
    }

    return null;
}

function getTournamentImage($tournament) {
    if (!isset($tournament['images']) || empty($tournament['images'])) {
        return null;
    }

    // Prefer banner or profile type
    $preferredTypes = ['banner', 'profile'];

    foreach ($preferredTypes as $type) {
        foreach ($tournament['images'] as $image) {
            if (isset($image['type']) && strtolower($image['type']) === $type && isset($image['url'])) {
                return $image['url'];
            }
        }
    }

    // Return first available image
    foreach ($tournament['images'] as $image) {
        if (isset($image['url'])) {
            return $image['url'];
        }
    }

    return null;
}

// ============================================
// MAIN EXECUTION
// ============================================

logMessage("=== Start.gg Events Update Started ===");
$startTime = microtime(true);

try {
    // Step 1: Fetch tournament data
    $tournament = fetchTournamentEvents($STARTGG_CONFIG);

    if (!$tournament) {
        throw new Exception("Failed to fetch tournament data from Start.gg");
    }

    // Step 2: Process and filter events
    $events = processEvents($tournament, $STARTGG_CONFIG);

    // Step 3: Compile result
    $result = [
        'tournament' => [
            'name' => $tournament['name'],
            'slug' => $tournament['slug'],
            'url' => "https://start.gg/" . $STARTGG_CONFIG['user_slug'],
            'timezone' => $STARTGG_CONFIG['timezone']
        ],
        'events' => $events,
        'total_events' => count($events),
        'timestamp' => time(),
        'last_updated' => date('Y-m-d H:i:s'),
        'processing_time_seconds' => round(microtime(true) - $startTime, 2)
    ];

    // Step 4: Save to cache
    $cacheDir = dirname($CACHE_FILE);
    if (!is_dir($cacheDir)) {
        mkdir($cacheDir, 0755, true);
    }

    file_put_contents($CACHE_FILE, json_encode($result, JSON_PRETTY_PRINT));

    logMessage("Cached " . count($events) . " upcoming events");
    logMessage("User: " . $tournament['name']);
    logMessage("Found " . count($tournament['tournaments'] ?? []) . " tournaments");

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
