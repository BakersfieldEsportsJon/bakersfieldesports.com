<?php
require_once __DIR__.'/../../bootstrap.php';

class MockGeoLocationService extends \Security\GeoLocationService
{
    private ?array $mockLocation = null;
    private bool $allowedCountry = true;
    protected array $config;
    private array $defaultLocation = [
        'country' => 'US',
        'city' => 'Los Angeles',
        'latitude' => 34.0522,
        'longitude' => -118.2437,
        'timestamp' => 0
    ];

    public function __construct()
    {
        // Mock constructor - no database initialization needed
        $this->config = [
            'allowed_countries' => ['US'],
            'strict_mode' => true,
            'cache_ttl' => 3600
        ];
    }

    public function __destruct()
    {
        // Override parent destructor to avoid database close
    }

    public function setMockLocation(array $location)
    {
        $this->mockLocation = $location;
    }

    public function setAllowedCountry(bool $allowed)
    {
        $this->allowedCountry = $allowed;
    }

    public function getLocation(string $ip): ?array
    {
        // Always return the mock location if set, otherwise default
        if ($this->mockLocation !== null) {
            return $this->mockLocation;
        }
        
        // Update timestamp for default location
        $this->defaultLocation['timestamp'] = time();
        return $this->defaultLocation;
    }

    public function isAllowedCountry(string $ip): bool
    {
        return $this->allowedCountry;
    }

    public function calculateDistance(array $location1, array $location2): float
    {
        // Original implementation kept for real distance calculation
        return parent::calculateDistance($location1, $location2);
    }
}

class TestSessionManagerWithLocation extends \Security\SessionManager
{
    protected MockGeoLocationService $mockGeoService;
    protected bool $isStarted = false;
    protected array $sessionData = [];

    public function __construct(\Security\Encryption $encryption, array $config)
    {
        $this->encryption = $encryption;
        $this->config = $config;
        $this->mockGeoService = new MockGeoLocationService();
    }

    public function getMockGeoService(): MockGeoLocationService
    {
        return $this->mockGeoService;
    }

    protected function getGeoLocationService(): \Security\GeoLocationService
    {
        return $this->mockGeoService;
    }

    public function validateSession(): void
    {
        parent::validateSession();
    }

    public function validateLocation(): void
    {
        parent::validateLocation();
    }
}

class LocationValidationTest
{
    private TestSessionManagerWithLocation $sessionManager;
    private array $config;
    
    public function __construct()
    {
        // Set up test environment
        $_SERVER['HTTP_USER_AGENT'] = 'PHPUnit Test';
        $_SERVER['REMOTE_ADDR'] = '127.0.0.1';
        $_SERVER['HTTP_HOST'] = 'localhost';

        // Clear any existing session
        if (session_status() === PHP_SESSION_ACTIVE) {
            session_destroy();
        }
        
        // Clear output buffer
        if (ob_get_level()) ob_end_clean();

        $this->config = [
            'timeout' => 3600,
            'cookie' => [
                'name' => 'test_session',
                'path' => '/',
                'domain' => 'localhost',
                'secure' => true,
                'httponly' => true,
                'samesite' => 'Strict'
            ],
            'encryption' => [
                'key' => 'test_key',
                'cipher' => 'aes-256-cbc'
            ],
            'security' => [
                'strict_mode' => true
            ]
        ];

        $this->sessionManager = new TestSessionManagerWithLocation(
            new \Security\Encryption('test_key'),
            $this->config
        );
    }

    public function __destruct()
    {
        // Clean up test session
        if (session_status() === PHP_SESSION_ACTIVE) {
            session_destroy();
        }
    }

    private function resetSession()
    {
        if (session_status() === PHP_SESSION_ACTIVE) {
            session_destroy();
        }
        if (ob_get_level()) ob_end_clean();
    }

    public function testAllowedLocation()
    {
        $this->resetSession();
        echo "Testing allowed location...\n";
        
        $geoService = $this->sessionManager->getMockGeoService();
        $geoService->setAllowedCountry(true);
        
        try {
            $this->sessionManager->start();
            echo "✓ Session started successfully with allowed location\n";
        } catch (\Exception $e) {
            echo "✗ Unexpected exception: " . $e->getMessage() . "\n";
        }
    }

    public function testBlockedLocation()
    {
        $this->resetSession();
        echo "\nTesting blocked location...\n";
        
        $geoService = $this->sessionManager->getMockGeoService();
        $geoService->setAllowedCountry(false);
        
        try {
            $this->sessionManager->start();
            throw new \Exception("Session should not start from blocked location");
        } catch (\Security\Exceptions\SessionLocationException $e) {
            echo "✓ Blocked location detected correctly\n";
        } catch (\Exception $e) {
            echo "✗ " . $e->getMessage() . "\n";
        }
    }

    public function testLocationChange()
    {
        $this->resetSession();
        echo "\nTesting location change detection...\n";
        
        $geoService = $this->sessionManager->getMockGeoService();
        
        // Start with LA location
        $laLocation = [
            'country' => 'US',
            'city' => 'Los Angeles',
            'latitude' => 34.0522,
            'longitude' => -118.2437,
            'timestamp' => time()
        ];
        $geoService->setMockLocation($laLocation);
        $geoService->setAllowedCountry(true);
        
        $this->sessionManager->start();
        echo "✓ Session started with initial location\n";
        
        // Change to NYC location (suspicious distance)
        $nycLocation = [
            'country' => 'US',
            'city' => 'New York',
            'latitude' => 40.7128,
            'longitude' => -74.0060,
            'timestamp' => time()
        ];
        
        // Verify distance calculation directly
        $distance = $geoService->calculateDistance($laLocation, $nycLocation);
        echo "Direct distance calculation: " . round($distance) . "km\n";
        
        // Set new location and validate
        $geoService->setMockLocation($nycLocation);
        
        try {
            $this->sessionManager->validateLocation();
            throw new \Exception("Should detect suspicious location change");
        } catch (\Security\Exceptions\SessionLocationException $e) {
            echo "✓ Suspicious location change detected\n";
            $context = $e->getLocationContext();
            $distance = $context['distance'] ?? 0;
            echo "✓ Distance calculated: " . round($distance) . "km\n";
        }
    }
}

// Run tests
echo "Starting Location Validation Tests\n";
echo "================================\n";
$test = new LocationValidationTest();
$test->testAllowedLocation();
$test->testBlockedLocation();
$test->testLocationChange();
