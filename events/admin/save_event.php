<?php
header('Content-Type: application/json');

function normalizeCategory($category) {
    if (empty($category)) {
        return '';
    }
    // Convert to lowercase and replace spaces with hyphens
    return str_replace(' ', '-', strtolower(trim($category)));
}

function sanitizeGameTag($gameTag) {
    if (empty($gameTag)) {
        return '';
    }
    // Remove special characters except hyphens and underscores
    return preg_replace('/[^a-zA-Z0-9-_]/', '', trim($gameTag));
}

// Get JSON data
$jsonData = file_get_contents('php://input');
$eventData = json_decode($jsonData, true);

if (!$eventData) {
    http_response_code(400);
    echo json_encode(['error' => 'Invalid JSON data']);
    exit;
}

// Debugging: Log received data
error_log('Received event data: ' . print_r($eventData, true));

// Read existing events
$eventsFile = '../data/events.json';
$currentEvents = json_decode(file_get_contents($eventsFile), true);

if (!$currentEvents) {
    $currentEvents = ['events' => []];
}

// Normalize category name and sanitize game tag
if (isset($eventData['category'])) {
    $eventData['category'] = normalizeCategory($eventData['category']);
}

if (isset($eventData['gameTag'])) {
    $eventData['gameTag'] = sanitizeGameTag($eventData['gameTag']);
} else {
    $eventData['gameTag'] = '';
}

// Prepare event data with all required fields
$newEvent = [
    'id' => $eventData['id'] ?? uniqid('event-'),
    'name' => $eventData['name'] ?? '',
    'category' => $eventData['category'] ?? '',
    'gameTag' => $eventData['gameTag'] ?? '',
    'description' => $eventData['description'] ?? '',
    'location' => $eventData['location'] ?? '',
    'address' => $eventData['address'] ?? '',
    'date' => $eventData['date'] ?? '',
    'endDate' => $eventData['endDate'] ?? null,
    'isRecurring' => $eventData['isRecurring'] ?? false,
    'image' => $eventData['image'] ?? '',
    'entryCost' => $eventData['entryCost'] ?? '',
    'registrationLink' => $eventData['registrationLink'] ?? '',
    'notes' => $eventData['notes'] ?? ''
];

// Add new event
$currentEvents['events'][] = $newEvent;

// Save back to file
if (file_put_contents($eventsFile, json_encode($currentEvents, JSON_PRETTY_PRINT))) {
    http_response_code(200);
    echo json_encode(['message' => 'Event saved successfully']);
} else {
    http_response_code(500);
    echo json_encode(['error' => 'Failed to save event']);
}
