<?php
namespace Security;

use GeoIp2\Database\Reader;
use GeoIp2\Exception\AddressNotFoundException;
use MaxMind\Db\Reader\InvalidDatabaseException;

require_once __DIR__ . '/../../vendor/autoload.php';

class GeoLocationService
{
    private $reader;
    private $config;
    private $cache = [];

    public function __construct(array $config)
    {
        $this->config = $config;
        try {
            if (file_exists($config['database_path'])) {
                $this->reader = new Reader($config['database_path']);
            }
        } catch (InvalidDatabaseException $e) {
            // Log error but continue - service will work in degraded mode
            error_log("GeoIP database error: " . $e->getMessage());
        }
    }

    private function hasReader(): bool
    {
        return isset($this->reader) && $this->reader instanceof Reader;
    }

    public function getLocation(string $ip): ?array
    {
        if (isset($this->cache[$ip]) && time() - $this->cache[$ip]['timestamp'] < $this->config['cache_ttl']) {
            return $this->cache[$ip]['data'];
        }

        if (!$this->hasReader()) {
            // Return null if no database is available
            return null;
        }

        try {
            $record = $this->reader->city($ip);
            $location = [
                'country' => $record->country->isoCode,
                'city' => $record->city->name,
                'latitude' => $record->location->latitude,
                'longitude' => $record->location->longitude,
                'timestamp' => time()
            ];

            // Cache the result
            $this->cache[$ip] = [
                'data' => $location,
                'timestamp' => time()
            ];

            return $location;
        } catch (AddressNotFoundException $e) {
            return null;
        }
    }

    public function isAllowedCountry(string $ip): bool
    {
        $location = $this->getLocation($ip);
        if (!$location) {
            return !$this->config['strict_mode'];
        }

        return in_array($location['country'], $this->config['allowed_countries']);
    }

    public function calculateDistance(array $location1, array $location2): float
    {
        // Haversine formula to calculate distance between two points
        $earthRadius = 6371; // km

        $lat1 = deg2rad($location1['latitude']);
        $lon1 = deg2rad($location1['longitude']);
        $lat2 = deg2rad($location2['latitude']);
        $lon2 = deg2rad($location2['longitude']);

        $latDelta = $lat2 - $lat1;
        $lonDelta = $lon2 - $lon1;

        $a = sin($latDelta/2) * sin($latDelta/2) +
            cos($lat1) * cos($lat2) *
            sin($lonDelta/2) * sin($lonDelta/2);

        $c = 2 * atan2(sqrt($a), sqrt(1-$a));

        return $earthRadius * $c;
    }

    public function isSuspiciousLocationChange(array $oldLocation, array $newLocation): bool
    {
        // If locations are in different countries, consider it suspicious
        if ($oldLocation['country'] !== $newLocation['country']) {
            return true;
        }

        // Calculate distance between locations
        $distance = $this->calculateDistance($oldLocation, $newLocation);

        // Log distance for debugging
        error_log("Distance between locations: " . $distance . "km");

        // If distance is more than 100km in less than cache_ttl, consider it suspicious
        // Lowered threshold for testing
        return $distance > 100;
    }

    public function __destruct()
    {
        if ($this->hasReader()) {
            $this->reader->close();
        }
    }
}
