<?php
/**
 * Start.gg Manual Sync - AJAX Endpoint
 * Allows admins to manually trigger tournament sync
 */

session_start();

// Require admin authentication
require_once __DIR__ . '/../../includes/config.php';
require_once __DIR__ . '/../../includes/auth.php';
require_once __DIR__ . '/../../includes/db.php';

header('Content-Type: application/json');

// Check authentication
if (!isset($_SESSION['user_id'])) {
    http_response_code(401);
    echo json_encode(['success' => false, 'error' => 'Not authenticated']);
    exit;
}

// Check CSRF token for POST requests
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!validate_csrf_token($_POST['csrf_token'] ?? '')) {
        http_response_code(403);
        echo json_encode(['success' => false, 'error' => 'Invalid CSRF token']);
        exit;
    }
}

require_once __DIR__ . '/../../../includes/startgg/TournamentSync.php';

$action = $_GET['action'] ?? $_POST['action'] ?? 'sync';

try {
    $sync = new TournamentSync($pdo);

    switch ($action) {
        case 'sync':
            // Manual sync of all tournaments
            $result = $sync->syncUpcomingTournaments();
            echo json_encode($result);
            break;

        case 'sync_single':
            // Sync a specific tournament
            $slug = $_POST['slug'] ?? '';
            if (empty($slug)) {
                throw new Exception('Tournament slug required');
            }
            $result = $sync->syncTournamentBySlug($slug);
            echo json_encode($result);
            break;

        case 'test':
            // Test sync without saving
            $result = $sync->testSync();
            echo json_encode($result);
            break;

        case 'stats':
            // Get sync statistics
            $result = $sync->getSyncStats();
            echo json_encode(['success' => true, 'data' => $result]);
            break;

        case 'history':
            // Get sync history
            $limit = intval($_GET['limit'] ?? 10);
            $result = $sync->getSyncHistory($limit);
            echo json_encode(['success' => true, 'data' => $result]);
            break;

        default:
            throw new Exception('Invalid action');
    }

} catch (Exception $e) {
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage()
    ]);
}
