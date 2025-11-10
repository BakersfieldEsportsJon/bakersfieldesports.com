<?php
/**
 * API Endpoint: Tournament Details
 * Returns tournament information for use in modals/AJAX requests
 */

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');

require_once __DIR__ . '/../admin/includes/db.php';
require_once __DIR__ . '/../includes/startgg/TournamentRepository.php';

$repository = new TournamentRepository($pdo);

// Get tournament slug from request
$slug = $_GET['slug'] ?? '';

if (empty($slug)) {
    http_response_code(400);
    echo json_encode(['error' => 'Tournament slug is required']);
    exit;
}

$tournament = $repository->getTournamentBySlug($slug);

if (!$tournament) {
    http_response_code(404);
    echo json_encode(['error' => 'Tournament not found']);
    exit;
}

// Get events for this tournament
$events = $repository->getEventsByTournament($tournament['id']);

// Format the response
$response = [
    'success' => true,
    'tournament' => [
        'id' => $tournament['id'],
        'name' => $tournament['name'],
        'slug' => $tournament['slug'],
        'url' => $tournament['url'],
        'description' => $tournament['description'],
        'rules' => $tournament['rules'],
        'start_at' => $tournament['start_at'],
        'end_at' => $tournament['end_at'],
        'timezone' => $tournament['timezone'],
        'is_online' => $tournament['is_online'],
        'is_registration_open' => $tournament['is_registration_open'],
        'registration_closes_at' => $tournament['registration_closes_at'],
        'num_attendees' => $tournament['num_attendees'],
        'venue_name' => $tournament['venue_name'],
        'venue_address' => $tournament['venue_address'],
        'venue_city' => $tournament['venue_city'],
        'venue_state' => $tournament['venue_state'],
        'image_url' => $tournament['image_url'],
        'banner_url' => $tournament['banner_url'],
        'tournament_type' => $tournament['tournament_type'],
    ],
    'events' => array_map(function($event) {
        return [
            'id' => $event['id'],
            'name' => $event['name'],
            'slug' => $event['slug'],
            'videogame_name' => $event['videogame_name'],
            'num_entrants' => $event['num_entrants'],
            'entry_fee' => $event['entry_fee'],
            'bracket_type' => $event['bracket_type'],
            'state' => $event['state'],
        ];
    }, $events)
];

echo json_encode($response);
