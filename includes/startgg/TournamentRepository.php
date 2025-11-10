<?php
declare(strict_types=1);

/**
 * Tournament Repository - Database Operations for Start.gg Integration
 * Handles all database CRUD operations for tournaments, events, and entrants
 */

class TournamentRepository {
    private \PDO $pdo;
    private bool $cacheEnabled;
    private int $defaultCacheTTL = 300; // 5 minutes

    public function __construct(\PDO $pdo) {
        $this->pdo = $pdo;
        // Check if APCu is available
        $this->cacheEnabled = function_exists('apcu_enabled') && apcu_enabled();
    }

    /**
     * Get cached data or execute callback
     */
    private function getCached(string $key, callable $callback, ?int $ttl = null): mixed {
        $ttl = $ttl ?? $this->defaultCacheTTL;

        if ($this->cacheEnabled && apcu_exists($key)) {
            return apcu_fetch($key);
        }

        $result = $callback();

        if ($this->cacheEnabled && $result !== false) {
            apcu_store($key, $result, $ttl);
        }

        return $result;
    }

    /**
     * Clear cache for specific key or pattern
     */
    public function clearCache(?string $key = null): bool {
        if (!$this->cacheEnabled) {
            return false;
        }

        if ($key === null) {
            // Clear all tournament-related cache
            $iterator = new \APCUIterator('/^tournament_/');
            return apcu_delete($iterator);
        }

        return apcu_delete($key);
    }

    /**
     * Save or update a tournament from start.gg API data
     */
    public function saveTournament(array $tournamentData): int|false {
        try {
            $stmt = $this->pdo->prepare("
                INSERT INTO startgg_tournaments (
                    startgg_id, name, slug, start_at, end_at, timezone,
                    venue_name, venue_address, venue_city, venue_state,
                    num_attendees, registration_closes_at, is_registration_open,
                    is_online, tournament_type, rules, description,
                    image_url, banner_url, url, currency, created_at, updated_at
                ) VALUES (
                    :startgg_id, :name, :slug, :start_at, :end_at, :timezone,
                    :venue_name, :venue_address, :venue_city, :venue_state,
                    :num_attendees, :registration_closes_at, :is_registration_open,
                    :is_online, :tournament_type, :rules, :description,
                    :image_url, :banner_url, :url, :currency, NOW(), NOW()
                )
                ON DUPLICATE KEY UPDATE
                    name = VALUES(name),
                    start_at = VALUES(start_at),
                    end_at = VALUES(end_at),
                    timezone = VALUES(timezone),
                    venue_name = VALUES(venue_name),
                    venue_address = VALUES(venue_address),
                    venue_city = VALUES(venue_city),
                    venue_state = VALUES(venue_state),
                    num_attendees = VALUES(num_attendees),
                    registration_closes_at = VALUES(registration_closes_at),
                    is_registration_open = VALUES(is_registration_open),
                    is_online = VALUES(is_online),
                    tournament_type = VALUES(tournament_type),
                    rules = VALUES(rules),
                    description = VALUES(description),
                    image_url = VALUES(image_url),
                    banner_url = VALUES(banner_url),
                    url = VALUES(url),
                    currency = VALUES(currency),
                    updated_at = NOW()
            ");

            $stmt->execute([
                ':startgg_id' => $tournamentData['id'],
                ':name' => $tournamentData['name'],
                ':slug' => $tournamentData['slug'],
                ':start_at' => $this->formatTimestamp($tournamentData['startAt'] ?? null),
                ':end_at' => $this->formatTimestamp($tournamentData['endAt'] ?? null),
                ':timezone' => $tournamentData['timezone'] ?? null,
                ':venue_name' => $tournamentData['venueAddress']['venueName'] ?? null,
                ':venue_address' => $tournamentData['venueAddress']['address'] ?? null,
                ':venue_city' => $tournamentData['venueAddress']['city'] ?? null,
                ':venue_state' => $tournamentData['venueAddress']['state'] ?? null,
                ':num_attendees' => $tournamentData['numAttendees'] ?? 0,
                ':registration_closes_at' => $this->formatTimestamp($tournamentData['registrationClosesAt'] ?? null),
                ':is_registration_open' => $tournamentData['isRegistrationOpen'] ?? false,
                ':is_online' => $tournamentData['isOnline'] ?? false,
                ':tournament_type' => $tournamentData['tournamentType'] ?? null,
                ':rules' => $tournamentData['rules'] ?? null,
                ':description' => $tournamentData['description'] ?? null,
                ':image_url' => $tournamentData['images'][0]['url'] ?? null,
                ':banner_url' => $tournamentData['bannerImages'][0]['url'] ?? null,
                ':url' => $tournamentData['url'] ?? null,
                ':currency' => $tournamentData['currency'] ?? 'usd'
            ]);

            // Clear relevant caches when tournament is saved/updated
            $this->clearCache();

            return $this->pdo->lastInsertId() ?: $this->getTournamentIdBySlug($tournamentData['slug']);
        } catch (PDOException $e) {
            error_log("Error saving tournament: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Save or update an event within a tournament
     */
    public function saveEvent(int $tournamentId, array $eventData): bool {
        try {
            $stmt = $this->pdo->prepare("
                INSERT INTO startgg_events (
                    tournament_id, startgg_event_id, name, slug, start_at,
                    num_entrants, entry_fee, state, bracket_type,
                    is_online, videogame_id, videogame_name, created_at, updated_at
                ) VALUES (
                    :tournament_id, :startgg_event_id, :name, :slug, :start_at,
                    :num_entrants, :entry_fee, :state, :bracket_type,
                    :is_online, :videogame_id, :videogame_name, NOW(), NOW()
                )
                ON DUPLICATE KEY UPDATE
                    name = VALUES(name),
                    start_at = VALUES(start_at),
                    num_entrants = VALUES(num_entrants),
                    entry_fee = VALUES(entry_fee),
                    state = VALUES(state),
                    bracket_type = VALUES(bracket_type),
                    is_online = VALUES(is_online),
                    videogame_name = VALUES(videogame_name),
                    updated_at = NOW()
            ");

            $stmt->execute([
                ':tournament_id' => $tournamentId,
                ':startgg_event_id' => $eventData['id'],
                ':name' => $eventData['name'],
                ':slug' => $eventData['slug'],
                ':start_at' => $this->formatTimestamp($eventData['startAt'] ?? null),
                ':num_entrants' => $eventData['numEntrants'] ?? 0,
                ':entry_fee' => $eventData['entryFee'] ?? 0,
                ':state' => $eventData['state'] ?? 'CREATED',
                ':bracket_type' => $eventData['type'] ?? null,
                ':is_online' => $eventData['isOnline'] ?? false,
                ':videogame_id' => $eventData['videogame']['id'] ?? null,
                ':videogame_name' => $eventData['videogame']['name'] ?? null
            ]);

            // Clear relevant caches when event is saved/updated
            $this->clearCache();

            return true;
        } catch (PDOException $e) {
            error_log("Error saving event: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Save or update an entrant
     */
    public function saveEntrant(int $eventId, array $entrantData): bool {
        try {
            $stmt = $this->pdo->prepare("
                INSERT INTO startgg_entrants (
                    event_id, startgg_entrant_id, participant_id, gamer_tag,
                    prefix, final_placement, seed, is_disqualified, created_at, updated_at
                ) VALUES (
                    :event_id, :startgg_entrant_id, :participant_id, :gamer_tag,
                    :prefix, :final_placement, :seed, :is_disqualified, NOW(), NOW()
                )
                ON DUPLICATE KEY UPDATE
                    gamer_tag = VALUES(gamer_tag),
                    prefix = VALUES(prefix),
                    final_placement = VALUES(final_placement),
                    seed = VALUES(seed),
                    is_disqualified = VALUES(is_disqualified),
                    updated_at = NOW()
            ");

            $participant = $entrantData['participants'][0] ?? [];

            $stmt->execute([
                ':event_id' => $eventId,
                ':startgg_entrant_id' => $entrantData['id'],
                ':participant_id' => $participant['id'] ?? null,
                ':gamer_tag' => $participant['gamerTag'] ?? 'Unknown',
                ':prefix' => $participant['prefix'] ?? null,
                ':final_placement' => $entrantData['standing']['placement'] ?? null,
                ':seed' => $entrantData['seed'] ?? null,
                ':is_disqualified' => $entrantData['isDisqualified'] ?? false
            ]);

            // Clear relevant caches when entrant is saved/updated
            $this->clearCache();

            return true;
        } catch (PDOException $e) {
            error_log("Error saving entrant: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Get all upcoming tournaments
     */
    public function getUpcomingTournaments(int $limit = 10): array {
        $cacheKey = "tournament_upcoming_{$limit}";

        return $this->getCached($cacheKey, function() use ($limit) {
            $stmt = $this->pdo->prepare("
                SELECT id, startgg_id, name, slug, start_at, end_at,
                       timezone, is_online, is_registration_open,
                       registration_closes_at, num_attendees,
                       venue_name, venue_address, venue_city, venue_state,
                       created_at, updated_at
                FROM startgg_tournaments
                WHERE start_at >= NOW()
                ORDER BY start_at ASC
                LIMIT :limit
            ");
            $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
            $stmt->execute();
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        });
    }

    /**
     * Get tournament by slug
     */
    public function getTournamentBySlug(string $slug): array|false {
        $cacheKey = "tournament_slug_{$slug}";

        return $this->getCached($cacheKey, function() use ($slug) {
            $stmt = $this->pdo->prepare("
                SELECT id, startgg_id, name, slug, start_at, end_at,
                       timezone, is_online, is_registration_open,
                       registration_closes_at, num_attendees,
                       venue_name, venue_address, venue_city, venue_state,
                       created_at, updated_at
                FROM startgg_tournaments
                WHERE slug = :slug
                LIMIT 1
            ");
            $stmt->execute([':slug' => $slug]);
            return $stmt->fetch(PDO::FETCH_ASSOC);
        });
    }

    /**
     * Get tournament by start.gg ID
     */
    public function getTournamentById(int $startggId): array|false {
        $cacheKey = "tournament_id_{$startggId}";

        return $this->getCached($cacheKey, function() use ($startggId) {
            $stmt = $this->pdo->prepare("
                SELECT id, startgg_id, name, slug, start_at, end_at,
                       timezone, is_online, is_registration_open,
                       registration_closes_at, num_attendees,
                       venue_name, venue_address, venue_city, venue_state,
                       created_at, updated_at
                FROM startgg_tournaments
                WHERE startgg_id = :startgg_id
                LIMIT 1
            ");
            $stmt->execute([':startgg_id' => $startggId]);
            return $stmt->fetch(PDO::FETCH_ASSOC);
        });
    }

    /**
     * Get tournament ID by slug
     */
    private function getTournamentIdBySlug(string $slug): ?int {
        $stmt = $this->pdo->prepare("
            SELECT id FROM startgg_tournaments
            WHERE slug = :slug
            LIMIT 1
        ");
        $stmt->execute([':slug' => $slug]);
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        return $result ? $result['id'] : null;
    }

    /**
     * Get events for a tournament
     */
    public function getEventsByTournament(int $tournamentId): array {
        $cacheKey = "tournament_events_{$tournamentId}";

        return $this->getCached($cacheKey, function() use ($tournamentId) {
            $stmt = $this->pdo->prepare("
                SELECT id, tournament_id, startgg_id, name, slug,
                       start_at, num_entrants, state,
                       created_at, updated_at
                FROM startgg_events
                WHERE tournament_id = :tournament_id
                ORDER BY start_at ASC
            ");
            $stmt->execute([':tournament_id' => $tournamentId]);
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        });
    }

    /**
     * Get entrants for an event
     */
    public function getEntrantsByEvent(int $eventId, int $limit = 100): array {
        $cacheKey = "event_entrants_{$eventId}_{$limit}";

        return $this->getCached($cacheKey, function() use ($eventId, $limit) {
            $stmt = $this->pdo->prepare("
                SELECT id, event_id, startgg_id, gamer_tag,
                       prefix, seed, placement,
                       created_at, updated_at
                FROM startgg_entrants
                WHERE event_id = :event_id
                ORDER BY seed ASC, gamer_tag ASC
                LIMIT :limit
            ");
            $stmt->bindValue(':event_id', $eventId, PDO::PARAM_INT);
            $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
            $stmt->execute();
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        });
    }

    /**
     * Get tournaments with open registration
     */
    public function getOpenRegistrationTournaments(): array {
        $cacheKey = "tournament_open_registration";

        return $this->getCached($cacheKey, function() {
            $stmt = $this->pdo->query("
                SELECT id, startgg_id, name, slug, start_at, end_at,
                       timezone, is_online, is_registration_open,
                       registration_closes_at, num_attendees,
                       venue_name, venue_address, venue_city, venue_state,
                       created_at, updated_at
                FROM startgg_tournaments
                WHERE is_registration_open = 1
                AND start_at >= NOW()
                ORDER BY start_at ASC
            ");
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        });
    }

    /**
     * Delete old tournaments (cleanup)
     */
    public function deleteOldTournaments(int $daysOld = 90): int {
        $stmt = $this->pdo->prepare("
            DELETE FROM startgg_tournaments
            WHERE end_at < DATE_SUB(NOW(), INTERVAL :days DAY)
        ");
        $stmt->execute([':days' => $daysOld]);

        // Clear caches after deletion
        $this->clearCache();

        return $stmt->rowCount();
    }

    /**
     * Get tournament statistics
     */
    public function getTournamentStats(): array|false {
        $stmt = $this->pdo->query("
            SELECT
                COUNT(*) as total_tournaments,
                SUM(CASE WHEN is_registration_open = 1 THEN 1 ELSE 0 END) as open_registration,
                SUM(CASE WHEN start_at >= NOW() THEN 1 ELSE 0 END) as upcoming,
                SUM(num_attendees) as total_attendees
            FROM startgg_tournaments
        ");
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    /**
     * Format timestamp for database
     */
    private function formatTimestamp(?int $timestamp): ?string {
        if (!$timestamp) {
            return null;
        }
        return date('Y-m-d H:i:s', $timestamp);
    }

    /**
     * Mark tournaments as past if their end date has passed
     */
    public function updatePastTournaments(): int|false {
        $stmt = $this->pdo->exec("
            UPDATE startgg_tournaments
            SET is_registration_open = 0
            WHERE end_at < NOW()
            AND is_registration_open = 1
        ");

        // Clear caches after update
        $this->clearCache();

        return $stmt;
    }

    /**
     * Get event ID by start.gg event ID
     */
    public function getEventIdByStartggId(int $startggEventId): ?int {
        $stmt = $this->pdo->prepare("
            SELECT id FROM startgg_events
            WHERE startgg_event_id = :startgg_event_id
            LIMIT 1
        ");
        $stmt->execute([':startgg_event_id' => $startggEventId]);
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        return $result ? $result['id'] : null;
    }
}
