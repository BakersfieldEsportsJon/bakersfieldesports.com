<?php
header('Content-Type: application/json');

// Get JSON data
$jsonData = file_get_contents('php://input');
$deleteData = json_decode($jsonData, true);

if (!$deleteData || !isset($deleteData['id'])) {
    http_response_code(400);
    echo json_encode(['error' => 'Invalid JSON data']);
    exit;
}

// Read existing events
$eventsFile = '../data/events.json';
$currentEvents = json_decode(file_get_contents($eventsFile), true);

if (!$currentEvents) {
    http_response_code(404);
    echo json_encode(['error' => 'Events file not found']);
    exit;
}

// Find and remove the event
$eventFound = false;
foreach ($currentEvents['events'] as $key => $event) {
    if ($event['id'] === $deleteData['id']) {
        array_splice($currentEvents['events'], $key, 1);
        $eventFound = true;
        break;
    }
}

if (!$eventFound) {
    http_response_code(404);
    echo json_encode(['error' => 'Event not found']);
    exit;
}

// Save back to file
if (file_put_contents($eventsFile, json_encode($currentEvents, JSON_PRETTY_PRINT))) {
    http_response_code(200);
    echo json_encode(['message' => 'Event deleted successfully']);
} else {
    http_response_code(500);
    echo json_encode(['error' => 'Failed to delete event']);
}
