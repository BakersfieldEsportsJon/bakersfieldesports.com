<?php
namespace Security;

class SessionManager
{
    protected array $config;
    protected Encryption $encryption;
    protected GeoLocationService $geoService;
    protected ?SecurityMonitor $monitor;
    protected array $sessionData = [];
    protected bool $isStarted = false;

    public function __construct(Encryption $encryption, array $config, ?GeoLocationService $geoService = null)
    {
        $this->encryption = $encryption;
        $this->config = $config;
        $this->geoService = $geoService ?? new GeoLocationService(require __DIR__ . '/geoip_config.php');
        $this->monitor = null;
    }

    protected function getGeoLocationService(): GeoLocationService
    {
        return $this->geoService;
    }

    public function validateLocation(): void
    {
        $ip = $_SERVER['REMOTE_ADDR'];
        $currentLocation = $this->getGeoLocationService()->getLocation($ip);
        
        // Check if country is allowed
        if (!$this->getGeoLocationService()->isAllowedCountry($ip)) {
            throw new Exceptions\SessionLocationException(
                'Access denied from your location'
            );
        }

        if (!$currentLocation) {
            if ($this->config['security']['strict_mode'] ?? false) {
                throw new Exceptions\SessionLocationException(
                    'Could not determine location'
                );
            }
            return;
        }

        // Check for location change if we have a previous location
        if (isset($_SESSION['_location'])) {
            $previousLocation = $_SESSION['_location'];
            if ($this->getGeoLocationService()->isSuspiciousLocationChange($previousLocation, $currentLocation)) {
                throw new Exceptions\SessionLocationException(
                    'Suspicious location change detected',
                    $previousLocation,
                    $currentLocation
                );
            }
        }

        // Update session location and monitoring
        $_SESSION['_location'] = $currentLocation;
        if ($this->isStarted && $this->monitor) {
            $this->monitor->updateSessionLocation(session_id(), $currentLocation);
        }
    }

    public function start(): void
    {
        if ($this->isStarted) {
            return;
        }

        // Validate location before starting session
        $this->validateLocation();

        if (!headers_sent()) {
            session_set_cookie_params([
                'lifetime' => $this->config['timeout'] ?? 3600,
                'path' => $this->config['cookie']['path'] ?? '/',
                'domain' => $this->config['cookie']['domain'] ?? 'localhost',
                'secure' => $this->config['cookie']['secure'] ?? true,
                'httponly' => $this->config['cookie']['httponly'] ?? true,
                'samesite' => $this->config['cookie']['samesite'] ?? 'Strict'
            ]);

            session_name($this->config['cookie']['name'] ?? 'secure_session');
            session_start();
            $this->isStarted = true;

            $this->initializeSession();
            $this->checkTimeout();
            $this->checkSecurity();
            $this->regenerateId();

            if ($this->monitor) {
                // Log session start
                $this->monitor->logEvent('session_start', [
                    'session_id' => session_id(),
                    'is_admin' => isset($_SESSION['is_admin']) && $_SESSION['is_admin'],
                    'page_url' => $_SERVER['REQUEST_URI'] ?? null
                ]);

                // Track initial page access
                $this->trackPageAccess();
            }
        }
    }

    public function get(string $key)
    {
        $this->ensureStarted();
        return $this->sessionData[$key] ?? null;
    }

    public function set(string $key, $value): void
    {
        $this->ensureStarted();
        $this->sessionData[$key] = $value;
    }

    public function destroy(): void
    {
        if ($this->isStarted) {
            if ($this->monitor) {
                // Log session end
                $this->monitor->logEvent('session_end', [
                    'session_id' => session_id(),
                    'is_admin' => isset($_SESSION['is_admin']) && $_SESSION['is_admin'],
                    'duration' => $this->monitor->getSessionDuration(session_id())
                ]);
            }

            session_unset();
            session_destroy();
            $this->isStarted = false;
        }
    }

    public function regenerateId(): void
    {
        if ($this->isStarted) {
            $oldId = session_id();
            session_regenerate_id(true);
            $newId = session_id();

            // Update session ID in monitoring
            if ($oldId !== $newId && $this->monitor) {
                $this->monitor->logEvent('session_regenerate', [
                    'old_session_id' => $oldId,
                    'new_session_id' => $newId
                ]);
            }
        }
    }

    protected function validateSession(): void
    {
        // Validate session integrity and security
        if (!isset($_SESSION['_created'])) {
            $this->initializeSession();
        }

        $this->checkTimeout();
        $this->checkSecurity();
        $this->validateLocation();
        $this->trackPageAccess();
    }

    protected function initializeSession(): void
    {
        $_SESSION['_created'] = time();
        $_SESSION['_fingerprint'] = $this->generateFingerprint();
        $_SESSION['_location'] = $this->getGeoLocationService()->getLocation($_SERVER['REMOTE_ADDR'] ?? '127.0.0.1');
    }

    protected function checkTimeout(): void
    {
        if (isset($_SESSION['_created']) && time() - $_SESSION['_created'] > ($this->config['timeout'] ?? 3600)) {
            if ($this->monitor) {
                $this->monitor->logEvent('session_timeout', [
                    'session_id' => session_id(),
                    'duration' => time() - $_SESSION['_created']
                ]);
            }
            $this->destroy();
            throw new SessionTimeoutException('Session expired');
        }
    }

    protected function checkSecurity(): void
    {
        if (!isset($_SESSION['_fingerprint']) || $_SESSION['_fingerprint'] !== $this->generateFingerprint()) {
            if ($this->monitor) {
                $this->monitor->logEvent('session_security_violation', [
                    'session_id' => session_id(),
                    'reason' => 'Invalid fingerprint'
                ]);
            }
            $this->destroy();
            throw new SessionSecurityException('Invalid session fingerprint');
        }
    }

    protected function generateFingerprint(): string
    {
        return hash('sha256', 
            ($_SERVER['HTTP_USER_AGENT'] ?? 'CLI') . 
            ($_SERVER['REMOTE_ADDR'] ?? '127.0.0.1') . 
            ($this->config['encryption']['key'] ?? 'default_key')
        );
    }

    protected function trackPageAccess(): void
    {
        if ($this->monitor && $this->isStarted && isset($_SERVER['REQUEST_URI'])) {
            $this->monitor->trackSessionActivity(
                session_id(),
                'page_access',
                $_SERVER['REQUEST_URI'],
                isset($_SESSION['is_admin']) && $_SESSION['is_admin']
            );
        }
    }

    protected function ensureStarted(): void
    {
        if (!$this->isStarted) {
            throw new SessionNotStartedException('Session not started');
        }
    }
}
