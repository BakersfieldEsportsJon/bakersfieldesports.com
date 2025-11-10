<?php
/**
 * Simple Tournament Details API
 * Reads from cache file instead of database
 */

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');

// Load tournament config
$tournamentConfig = require __DIR__ . '/../tournament-config.php';

// Get tournament slug from request
$slug = $_GET['slug'] ?? '';

if (empty($slug)) {
    http_response_code(400);
    echo json_encode(['error' => 'Tournament slug is required']);
    exit;
}

// Get config for this tournament
$config = $tournamentConfig[$slug] ?? [];

// Load tournaments from cache
$cacheFile = __DIR__ . '/../cache/startgg-events.json';

if (!file_exists($cacheFile)) {
    http_response_code(404);
    echo json_encode(['error' => 'Tournament data not available']);
    exit;
}

$cacheData = json_decode(file_get_contents($cacheFile), true);

if (!$cacheData || !isset($cacheData['events'])) {
    http_response_code(500);
    echo json_encode(['error' => 'Failed to load tournament data']);
    exit;
}

// Find the tournament by slug
// Slug format: "tournament/SLUG/event/EVENT"
$tournament = null;
$tournamentEvents = [];

foreach ($cacheData['events'] as $event) {
    // Extract tournament slug from event slug
    if (preg_match('/tournament\/([^\/]+)/', $event['slug'], $matches)) {
        $eventTournamentSlug = $matches[1];

        if ($eventTournamentSlug === $slug) {
            // Found a matching event - group by tournament
            if (!$tournament) {
                $tournament = [
                    'id' => crc32($slug), // Generate a unique ID
                    'name' => $event['tournament_name'] ?? 'Tournament',
                    'slug' => $slug,
                    'url' => $event['event_url'] ?? 'https://start.gg',
                    'description' => $config['description'] ?? 'Official tournament hosted at Bakersfield eSports Center. Check event details below.',
                    'rules' => $config['rules'] ?? null,
                    'start_at' => $event['start_date'] ?? date('Y-m-d'),
                    'end_at' => null,
                    'timezone' => $cacheData['tournament']['timezone'] ?? 'America/Los_Angeles',
                    'is_online' => $event['is_online'] ?? false,
                    'is_registration_open' => $event['registration_open'] ?? false,
                    'registration_closes_at' => $event['registration_closes_at'] ?? null,
                    'num_attendees' => $event['num_entrants'] ?? 0,
                    'venue_name' => $event['is_online'] ? null : 'Bakersfield eSports Center',
                    'venue_address' => '7104 Golden State Hwy',
                    'venue_city' => 'Bakersfield',
                    'venue_state' => 'CA',
                    'image_url' => $event['game_image'] ?? null,
                    'banner_url' => null,
                    'tournament_type' => 'ONLINE' // Placeholder
                ];
            }

            // Add event to list
            $tournamentEvents[] = [
                'id' => $event['id'],
                'name' => $event['name'],
                'slug' => $event['slug'],
                'videogame_name' => $event['game'] ?? 'Unknown',
                'num_entrants' => $event['num_entrants'] ?? 0,
                'entry_fee' => ($config['entry_fee'] ?? 0) * 100, // Convert dollars to cents
                'bracket_type' => $config['bracket_type'] ?? 'To Be Determined',
                'state' => $event['state'] ?? 'CREATED'
            ];
        }
    }
}

if (!$tournament) {
    http_response_code(404);
    echo json_encode(['error' => 'Tournament not found', 'slug_searched' => $slug]);
    exit;
}

// Return tournament details
$response = [
    'success' => true,
    'tournament' => $tournament,
    'events' => $tournamentEvents
];

echo json_encode($response);
