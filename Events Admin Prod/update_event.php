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
$updatedEvent = json_decode($jsonData, true);

if (!$updatedEvent || !isset($updatedEvent['id'])) {
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

// Normalize category name and sanitize game tag
if (isset($updatedEvent['category'])) {
    $updatedEvent['category'] = normalizeCategory($updatedEvent['category']);
}

if (isset($updatedEvent['gameTag'])) {
    $updatedEvent['gameTag'] = sanitizeGameTag($updatedEvent['gameTag']);
} else {
    $updatedEvent['gameTag'] = '';
}

// Find and update the event
$eventFound = false;
foreach ($currentEvents['events'] as $key => $event) {
    if ($event['id'] === $updatedEvent['id']) {
        $currentEvents['events'][$key] = $updatedEvent;
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
    echo json_encode(['message' => 'Event updated successfully']);
} else {
    http_response_code(500);
    echo json_encode(['error' => 'Failed to update event']);
}
