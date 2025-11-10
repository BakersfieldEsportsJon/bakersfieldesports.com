<?php
require_once __DIR__.'/../../bootstrap.php';
require_once __DIR__.'/../../includes/security/middleware/SessionValidationMiddleware.php';

class TestSessionManager extends \Security\SessionManager {
    private $isValid = true;
    private $isExpired = false;
    private $lastActivity = null;
    private $securityEvents = [];

    public function __construct() {
        // Mock config for testing
        $config = [
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
            ]
        ];
        
        parent::__construct(new \Security\Encryption('test_key'), $config);
    }

    // Override methods for testing
    public function isValid() {
        return $this->isValid;
    }

    public function isExpired() {
        return $this->isExpired;
    }

    public function setValid($valid) {
        $this->isValid = $valid;
    }

    public function setExpired($expired) {
        $this->isExpired = $expired;
    }

    public function updateLastActivity() {
        $this->lastActivity = time();
    }

    public function getLastActivity() {
        return $this->lastActivity;
    }

    public function logSecurityEvent($exception) {
        $this->securityEvents[] = [
            'type' => get_class($exception),
            'message' => $exception->getMessage(),
            'time' => time()
        ];
    }

    public function getSecurityEvents() {
        return $this->securityEvents;
    }
}

class MockResponse {
    private $redirectUrl = null;

    public function withRedirect($url) {
        $this->redirectUrl = $url;
        return $this;
    }

    public function getRedirectUrl() {
        return $this->redirectUrl;
    }
}

class SessionValidationTest {
    private $sessionManager;
    private $middleware;
    private $request;
    private $response;
    
    public function __construct() {
        $this->sessionManager = new TestSessionManager();
        $this->middleware = new \Security\Middleware\SessionValidationMiddleware($this->sessionManager);
        $this->request = new stdClass(); // Mock request
        $this->response = new MockResponse();
    }

    public function testValidSession() {
        echo "Testing valid session flow...\n";
        
        $next = function($request, $response) {
            echo "Next middleware called successfully\n";
            return $response;
        };

        $result = ($this->middleware)($this->request, $this->response, $next);
        
        if ($this->sessionManager->getLastActivity() !== null) {
            echo "✓ Last activity timestamp updated\n";
        } else {
            echo "✗ Failed to update last activity timestamp\n";
        }

        if ($result === $this->response) {
            echo "✓ Valid session passed through middleware\n";
        } else {
            echo "✗ Unexpected response for valid session\n";
        }
    }

    public function testInvalidSession() {
        echo "\nTesting invalid session handling...\n";
        
        $this->sessionManager->setValid(false);
        
        $next = function($request, $response) {
            echo "✗ Next middleware should not be called for invalid session\n";
            return $response;
        };

        $result = ($this->middleware)($this->request, $this->response, $next);
        
        if ($result->getRedirectUrl() === '/admin/login') {
            echo "✓ Invalid session redirected to login\n";
        } else {
            echo "✗ Failed to redirect invalid session\n";
        }

        $events = $this->sessionManager->getSecurityEvents();
        if (count($events) > 0 && $events[0]['type'] === 'Security\Exceptions\SessionSecurityException') {
            echo "✓ Security event logged for invalid session\n";
        } else {
            echo "✗ Failed to log security event\n";
        }
    }

    public function testSessionTimeout() {
        echo "\nTesting session timeout handling...\n";
        
        $this->sessionManager->setValid(true);
        $this->sessionManager->setExpired(true);
        
        $next = function($request, $response) {
            echo "✗ Next middleware should not be called for expired session\n";
            return $response;
        };

        $result = ($this->middleware)($this->request, $this->response, $next);
        
        if ($result->getRedirectUrl() === '/admin/login') {
            echo "✓ Expired session redirected to login\n";
        } else {
            echo "✗ Failed to redirect expired session\n";
        }

        $events = $this->sessionManager->getSecurityEvents();
        if (count($events) > 0 && $events[count($events)-1]['type'] === 'Security\Exceptions\SessionTimeoutException') {
            echo "✓ Security event logged for session timeout\n";
        } else {
            echo "✗ Failed to log timeout event\n";
        }
    }
}

// Run tests
echo "Starting Session Validation Middleware Tests\n";
echo "==========================================\n";
$test = new SessionValidationTest();
$test->testValidSession();
$test->testInvalidSession();
$test->testSessionTimeout();
