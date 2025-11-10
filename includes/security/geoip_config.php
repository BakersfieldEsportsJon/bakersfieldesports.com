<?php
// GeoIP Configuration
return [
    'license_key' => env('MAXMIND_LICENSE_KEY'),
    'database_path' => storage_path('geoip/GeoLite2-City.mmdb'),
    'allowed_countries' => ['US'],
    'cache_ttl' => 3600, // 1 hour cache
    'strict_mode' => true
];
