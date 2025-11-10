<?php
/**
 * Tournament Sync - Synchronize tournaments from start.gg to local database
 * Handles the sync logic, error handling, and logging
 */

require_once __DIR__ . '/StartGGClient.php';
require_once __DIR__ . '/StartGGConfig.php';
require_once __DIR__ . '/TournamentRepository.php';

class TournamentSync {
    private $client;
    private $config;
    private $repository;
    private $pdo;
    private $ownerId;
    private $syncLog = [];

    public function __construct($pdo) {
        $this->pdo = $pdo;
        $this->config = new StartGGConfig($pdo);
        $this->repository = new TournamentRepository($pdo);

        $apiToken = $this->config->getApiToken();
        if (!$apiToken) {
            throw new Exception('API token not configured');
        }

        $this->client = new StartGGClient($apiToken);
        $this->ownerId = $this->config->getOwnerId();

        if (!$this->ownerId) {
            throw new Exception('Owner ID not configured');
        }
    }

    /**
     * Sync all upcoming public tournaments
     */
    public function syncUpcomingTournaments() {
        $this->log('Starting tournament sync...');

        try {
            $tournaments = $this->fetchUpcomingTournaments();
            $this->log('Fetched ' . count($tournaments) . ' tournaments from start.gg');

            $synced = 0;
            $errors = 0;

            foreach ($tournaments as $tournamentData) {
                try {
                    $this->syncTournament($tournamentData);
                    $synced++;
                    $this->log('Synced: ' . $tournamentData['name']);
                } catch (Exception $e) {
                    $errors++;
                    $this->log('Error syncing tournament: ' . $e->getMessage(), 'error');
                }

                // Rate limiting
                usleep(100000); // 100ms delay
            }

            // Update sync status
            $this->config->updateLastSync();

            // Cleanup old tournaments
            $deleted = $this->repository->deleteOldTournaments(90);
            if ($deleted > 0) {
                $this->log("Cleaned up $deleted old tournaments");
            }

            // Update past tournaments
            $this->repository->updatePastTournaments();

            $this->log("Sync complete: $synced synced, $errors errors");

            // Save sync log to database
            $this->saveSyncLog($synced, $errors);

            return [
                'success' => true,
                'synced' => $synced,
                'errors' => $errors,
                'log' => $this->syncLog
            ];

        } catch (Exception $e) {
            $this->log('Sync failed: ' . $e->getMessage(), 'error');
            $this->saveSyncLog(0, 1, $e->getMessage());

            return [
                'success' => false,
                'error' => $e->getMessage(),
                'log' => $this->syncLog
            ];
        }
    }

    /**
     * Sync a single tournament by slug
     */
    public function syncTournamentBySlug($slug) {
        $this->log("Syncing tournament: $slug");

        try {
            $result = $this->client->getTournamentDetails($slug);

            if (!isset($result['data']['tournament'])) {
                throw new Exception('Tournament not found');
            }

            $tournamentData = $result['data']['tournament'];
            $this->syncTournament($tournamentData);

            $this->log('Successfully synced tournament');

            return [
                'success' => true,
                'tournament' => $tournamentData['name'],
                'log' => $this->syncLog
            ];

        } catch (Exception $e) {
            $this->log('Error: ' . $e->getMessage(), 'error');

            return [
                'success' => false,
                'error' => $e->getMessage(),
                'log' => $this->syncLog
            ];
        }
    }

    /**
     * Fetch upcoming tournaments from start.gg
     */
    private function fetchUpcomingTournaments() {
        $result = $this->client->getUpcomingTournaments($this->ownerId);

        if (isset($result['errors'])) {
            throw new Exception('API Error: ' . json_encode($result['errors']));
        }

        if (!isset($result['data']['user']['tournaments']['nodes'])) {
            throw new Exception('Unexpected API response structure');
        }

        // Filter for public tournaments only
        $tournaments = $result['data']['user']['tournaments']['nodes'];
        return array_filter($tournaments, function($tournament) {
            return !($tournament['isPrivate'] ?? false);
        });
    }

    /**
     * Sync a single tournament with all its events
     */
    private function syncTournament($tournamentData) {
        // Save tournament
        $tournamentId = $this->repository->saveTournament($tournamentData);

        if (!$tournamentId) {
            throw new Exception('Failed to save tournament');
        }

        // Sync events if available
        if (isset($tournamentData['events']) && is_array($tournamentData['events'])) {
            foreach ($tournamentData['events'] as $eventData) {
                $this->syncEvent($tournamentId, $eventData);
                usleep(50000); // 50ms delay between events
            }
        } else {
            // Fetch full tournament details to get events
            $fullTournament = $this->client->getTournamentDetails($tournamentData['slug']);

            if (isset($fullTournament['data']['tournament']['events'])) {
                foreach ($fullTournament['data']['tournament']['events'] as $eventData) {
                    $this->syncEvent($tournamentId, $eventData);
                    usleep(50000);
                }
            }
        }
    }

    /**
     * Sync an event with its entrants
     */
    private function syncEvent($tournamentId, $eventData) {
        // Save event
        $saved = $this->repository->saveEvent($tournamentId, $eventData);

        if (!$saved) {
            $this->log('Failed to save event: ' . $eventData['name'], 'warning');
            return;
        }

        // Get database event ID
        $eventId = $this->repository->getEventIdByStartggId($eventData['id']);

        if (!$eventId) {
            $this->log('Could not find event ID for entrant sync', 'warning');
            return;
        }

        // Sync entrants if available
        if (isset($eventData['entrants']['nodes']) && is_array($eventData['entrants']['nodes'])) {
            foreach ($eventData['entrants']['nodes'] as $entrantData) {
                $this->repository->saveEntrant($eventId, $entrantData);
            }
        } else {
            // Fetch entrants separately if needed
            $entrantsResult = $this->client->getEventEntrants($eventData['id'], 1, 100);

            if (isset($entrantsResult['data']['event']['entrants']['nodes'])) {
                foreach ($entrantsResult['data']['event']['entrants']['nodes'] as $entrantData) {
                    $this->repository->saveEntrant($eventId, $entrantData);
                }
            }
        }
    }

    /**
     * Add entry to sync log
     */
    private function log($message, $level = 'info') {
        $this->syncLog[] = [
            'timestamp' => date('Y-m-d H:i:s'),
            'level' => $level,
            'message' => $message
        ];

        // Also log to error_log for server logs
        if ($level === 'error') {
            error_log("[StartGG Sync] $message");
        }
    }

    /**
     * Save sync log to database
     */
    private function saveSyncLog($syncedCount, $errorCount, $errorMessage = null) {
        try {
            $stmt = $this->pdo->prepare("
                INSERT INTO startgg_sync_log (
                    sync_type, status, tournaments_synced, errors_count,
                    error_message, log_data, created_at
                ) VALUES (
                    'scheduled', :status, :synced, :errors,
                    :error_message, :log_data, NOW()
                )
            ");

            $stmt->execute([
                ':status' => $errorCount === 0 ? 'success' : 'partial',
                ':synced' => $syncedCount,
                ':errors' => $errorCount,
                ':error_message' => $errorMessage,
                ':log_data' => json_encode($this->syncLog)
            ]);
        } catch (PDOException $e) {
            error_log("Failed to save sync log: " . $e->getMessage());
        }
    }

    /**
     * Get sync statistics
     */
    public function getSyncStats() {
        try {
            // Get last sync info
            $stmt = $this->pdo->query("
                SELECT id, tournaments_synced, events_synced,
                       entrants_synced, duration_seconds,
                       created_at, sync_status, error_message
                FROM startgg_sync_log
                ORDER BY created_at DESC
                LIMIT 1
            ");
            $lastSync = $stmt->fetch(PDO::FETCH_ASSOC);

            // Get tournament stats
            $tournamentStats = $this->repository->getTournamentStats();

            return [
                'last_sync' => $lastSync,
                'tournament_stats' => $tournamentStats,
                'sync_enabled' => $this->config->isSyncEnabled()
            ];
        } catch (Exception $e) {
            return [
                'error' => $e->getMessage()
            ];
        }
    }

    /**
     * Get sync log history
     */
    public function getSyncHistory($limit = 10) {
        $stmt = $this->pdo->prepare("
            SELECT id, tournaments_synced, events_synced,
                   entrants_synced, duration_seconds,
                   created_at, sync_status, error_message
            FROM startgg_sync_log
            ORDER BY created_at DESC
            LIMIT :limit
        ");
        $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Test sync without saving
     */
    public function testSync() {
        try {
            // Test API connection
            $userResult = $this->client->getCurrentUser();

            if (!isset($userResult['data']['currentUser'])) {
                throw new Exception('API authentication failed');
            }

            // Test fetching tournaments
            $tournaments = $this->fetchUpcomingTournaments();

            return [
                'success' => true,
                'user' => $userResult['data']['currentUser']['name'],
                'tournaments_found' => count($tournaments),
                'sample' => array_slice($tournaments, 0, 3)
            ];
        } catch (Exception $e) {
            return [
                'success' => false,
                'error' => $e->getMessage()
            ];
        }
    }
}
