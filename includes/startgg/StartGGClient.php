<?php
/**
 * Start.gg GraphQL API Client
 * Handles all communication with start.gg API
 */

class StartGGClient {
    private $apiToken;
    private $endpoint = 'https://api.start.gg/gql/alpha';
    private $rateLimitDelay = 100000; // 100ms between requests (microseconds)
    private $lastRequestTime = 0;

    /**
     * Constructor
     * @param string $apiToken Start.gg API token
     */
    public function __construct($apiToken) {
        $this->apiToken = $apiToken;
    }

    /**
     * Execute a GraphQL query
     * @param string $query GraphQL query
     * @param array $variables Query variables
     * @return array API response
     * @throws Exception on API errors
     */
    public function query($query, $variables = []) {
        // Rate limiting
        $this->enforceRateLimit();

        $data = [
            'query' => $query,
            'variables' => $variables
        ];

        $ch = curl_init($this->endpoint);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            'Content-Type: application/json',
            'Authorization: Bearer ' . $this->apiToken
        ]);

        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $error = curl_error($ch);
        curl_close($ch);

        if ($error) {
            throw new Exception("cURL error: $error");
        }

        if ($httpCode !== 200) {
            throw new Exception("HTTP error $httpCode: $response");
        }

        $result = json_decode($response, true);

        if (json_last_error() !== JSON_ERROR_NONE) {
            throw new Exception("JSON decode error: " . json_last_error_msg());
        }

        if (isset($result['errors'])) {
            $errorMsg = $result['errors'][0]['message'] ?? 'Unknown GraphQL error';
            throw new Exception("GraphQL error: $errorMsg");
        }

        return $result;
    }

    /**
     * Enforce rate limiting between API calls
     */
    private function enforceRateLimit() {
        $timeSinceLastRequest = microtime(true) - $this->lastRequestTime;
        $timeToWait = $this->rateLimitDelay - ($timeSinceLastRequest * 1000000);

        if ($timeToWait > 0) {
            usleep($timeToWait);
        }

        $this->lastRequestTime = microtime(true);
    }

    /**
     * Get tournaments by owner ID
     * @param string $ownerId Owner/user ID
     * @param int $page Page number
     * @param int $perPage Results per page
     * @return array Tournament data
     */
    public function getTournamentsByOwner($ownerId, $page = 1, $perPage = 25) {
        $query = <<<'GQL'
        query TournamentsByOwner($ownerId: ID!, $page: Int!, $perPage: Int!) {
          user(id: $ownerId) {
            tournaments(query: {
              page: $page
              perPage: $perPage
              sortBy: "startAt asc"
            }) {
              pageInfo {
                total
                totalPages
              }
              nodes {
                id
                name
                slug
                startAt
                endAt
                timezone
                registrationClosesAt
                isRegistrationOpen
                isOnline
                numAttendees
                venueAddress
                venueName
                city
                addrState
                countryCode
                lat
                lng
                primaryContact
                primaryContactType
                images {
                  url
                  type
                }
                events {
                  id
                  name
                  slug
                  videogame {
                    id
                    name
                    displayName
                  }
                  numEntrants
                  type
                  startAt
                  checkInEnabled
                  checkInBuffer
                  state
                  isOnline
                }
                rules
                links {
                  facebook
                  discord
                }
              }
            }
          }
        }
        GQL;

        $variables = [
            'ownerId' => $ownerId,
            'page' => $page,
            'perPage' => $perPage
        ];

        return $this->query($query, $variables);
    }

    /**
     * Get detailed tournament information by slug
     * @param string $slug Tournament slug
     * @return array Tournament details
     */
    public function getTournamentDetails($slug) {
        $query = <<<'GQL'
        query TournamentDetails($slug: String!) {
          tournament(slug: $slug) {
            id
            name
            slug
            startAt
            endAt
            timezone
            registrationClosesAt
            isRegistrationOpen
            isOnline
            numAttendees
            venueAddress
            venueName
            city
            addrState
            countryCode
            lat
            lng
            rules
            primaryContact
            primaryContactType
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
              checkInEnabled
              checkInBuffer
              state
              isOnline
              type
              videogame {
                id
                name
                displayName
                images {
                  url
                  type
                }
              }
              phases {
                id
                name
                numSeeds
                bracketType
              }
            }
            links {
              facebook
              discord
            }
          }
        }
        GQL;

        $variables = ['slug' => $slug];
        return $this->query($query, $variables);
    }

    /**
     * Get entrants for an event
     * @param int $eventId Event ID
     * @param int $page Page number
     * @param int $perPage Results per page
     * @return array Entrant data
     */
    public function getEventEntrants($eventId, $page = 1, $perPage = 50) {
        $query = <<<'GQL'
        query EventEntrants($eventId: ID!, $page: Int!, $perPage: Int!) {
          event(id: $eventId) {
            id
            name
            entrants(query: {
              page: $page
              perPage: $perPage
            }) {
              pageInfo {
                total
                totalPages
              }
              nodes {
                id
                name
                isDisqualified
                standing {
                  placement
                }
                participants {
                  id
                  gamerTag
                  prefix
                  connectedAccounts
                  user {
                    id
                    name
                    location {
                      city
                      state
                      country
                    }
                  }
                }
              }
            }
          }
        }
        GQL;

        $variables = [
            'eventId' => $eventId,
            'page' => $page,
            'perPage' => $perPage
        ];

        return $this->query($query, $variables);
    }

    /**
     * Get upcoming tournaments (filter by date)
     * @param string $ownerId Owner ID
     * @param int $afterTimestamp Unix timestamp (get tournaments after this date)
     * @return array Tournament data
     */
    public function getUpcomingTournaments($ownerId, $afterTimestamp = null) {
        if ($afterTimestamp === null) {
            $afterTimestamp = time();
        }

        $tournaments = [];
        $page = 1;
        $hasMore = true;

        while ($hasMore) {
            $result = $this->getTournamentsByOwner($ownerId, $page, 25);

            if (!isset($result['data']['user']['tournaments']['nodes'])) {
                break;
            }

            $nodes = $result['data']['user']['tournaments']['nodes'];

            foreach ($nodes as $tournament) {
                $startAt = isset($tournament['startAt']) ? $tournament['startAt'] : null;

                // Only include upcoming/ongoing tournaments
                if ($startAt && $startAt >= $afterTimestamp) {
                    $tournaments[] = $tournament;
                }
            }

            // Check if there are more pages
            $pageInfo = $result['data']['user']['tournaments']['pageInfo'] ?? [];
            $totalPages = $pageInfo['totalPages'] ?? 1;
            $hasMore = $page < $totalPages;
            $page++;
        }

        return $tournaments;
    }

    /**
     * Get public tournaments only (filter out private/draft)
     * @param string $ownerId Owner ID
     * @return array Public tournament data
     */
    public function getPublicTournaments($ownerId) {
        $allTournaments = $this->getUpcomingTournaments($ownerId);

        // Filter to only registration open or upcoming tournaments
        return array_filter($allTournaments, function($tournament) {
            // Include if registration is open or tournament is in the future
            $isOpen = $tournament['isRegistrationOpen'] ?? false;
            $startAt = $tournament['startAt'] ?? 0;

            return $isOpen || $startAt > time();
        });
    }

    /**
     * Check tournament registration status
     * @param string $slug Tournament slug
     * @return array ['isOpen' => bool, 'closesAt' => timestamp, 'attendees' => int]
     */
    public function getRegistrationStatus($slug) {
        $result = $this->getTournamentDetails($slug);
        $tournament = $result['data']['tournament'] ?? null;

        if (!$tournament) {
            return [
                'isOpen' => false,
                'closesAt' => null,
                'attendees' => 0,
                'error' => 'Tournament not found'
            ];
        }

        return [
            'isOpen' => $tournament['isRegistrationOpen'] ?? false,
            'closesAt' => $tournament['registrationClosesAt'] ?? null,
            'attendees' => $tournament['numAttendees'] ?? 0,
            'events' => count($tournament['events'] ?? [])
        ];
    }

    /**
     * Get current user info (test authentication)
     * @return array User data
     */
    public function getCurrentUser() {
        $query = <<<'GQL'
        query CurrentUser {
          currentUser {
            id
            name
            slug
            bio
            location {
              city
              state
              country
            }
            images {
              url
              type
            }
          }
        }
        GQL;

        return $this->query($query);
    }

    /**
     * Get tournament by videogame
     * @param int $videogameId Videogame ID
     * @param int $page Page number
     * @return array Tournament data
     */
    public function getTournamentsByVideogame($videogameId, $page = 1) {
        $query = <<<'GQL'
        query TournamentsByVideogame($videogameId: ID!, $page: Int!) {
          tournaments(query: {
            page: $page
            perPage: 25
            sortBy: "startAt asc"
            filter: {
              videogameIds: [$videogameId]
              upcoming: true
            }
          }) {
            nodes {
              id
              name
              slug
              startAt
              city
              addrState
              numAttendees
              isRegistrationOpen
            }
          }
        }
        GQL;

        $variables = [
            'videogameId' => $videogameId,
            'page' => $page
        ];

        return $this->query($query, $variables);
    }
}
