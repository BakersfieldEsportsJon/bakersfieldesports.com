<?php
require_once __DIR__.'/../../bootstrap.php';

class SessionMonitoringTest
{
    private $sessionManager;
    private $monitor;
    private $config;
    
    public function __construct()
    {
        // Set up test environment
        $_SERVER['HTTP_USER_AGENT'] = 'PHPUnit Test';
        $_SERVER['REMOTE_ADDR'] = '127.0.0.1';
        $_SERVER['HTTP_HOST'] = 'localhost';
        $_SERVER['REQUEST_URI'] = '/test-page';

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

        $this->sessionManager = new \Security\SessionManager(
            new \Security\Encryption('test_key'),
            $this->config
        );

        $this->monitor = new \Security\SecurityMonitor();
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

    public function testSessionStartMonitoring()
    {
        $this->resetSession();
        echo "Testing session start monitoring...\n";
        
        try {
            $this->sessionManager->start();
            
            // Verify session was logged
            $stmt = $this->monitor->db->prepare("
                SELECT * FROM active_sessions 
                WHERE session_id = :session_id AND activity_type = 'page_access'
            ");
            $stmt->execute([':session_id' => session_id()]);
            $result = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if ($result) {
                echo "✓ Session start logged successfully\n";
                echo "✓ Initial page access tracked\n";
            } else {
                echo "✗ Failed to log session start\n";
            }
        } catch (\Exception $e) {
            echo "✗ Unexpected exception: " . $e->getMessage() . "\n";
        }
    }

    public function testSessionActivityTracking()
    {
        $this->resetSession();
        echo "\nTesting session activity tracking...\n";
        
        try {
            $this->sessionManager->start();
            
            // Simulate page navigation
            $_SERVER['REQUEST_URI'] = '/new-page';
            $this->sessionManager->validateSession();
            
            // Verify activity was logged
            $stmt = $this->monitor->db->prepare("
                SELECT COUNT(*) as count FROM active_sessions 
                WHERE session_id = :session_id AND page_url = '/new-page'
            ");
            $stmt->execute([':session_id' => session_id()]);
            $result = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if ($result['count'] > 0) {
                echo "✓ Page access tracked successfully\n";
            } else {
                echo "✗ Failed to track page access\n";
            }
        } catch (\Exception $e) {
            echo "✗ Unexpected exception: " . $e->getMessage() . "\n";
        }
    }

    public function testSessionEndMonitoring()
    {
        $this->resetSession();
        echo "\nTesting session end monitoring...\n";
        
        try {
            $this->sessionManager->start();
            $sessionId = session_id();
            
            // Wait briefly to test duration
            sleep(1);
            
            $this->sessionManager->destroy();
            
            // Verify session end was logged
            $logLines = file($this->monitor->logFile);
            $lastLine = json_decode(end($logLines), true);
            
            if ($lastLine['event_type'] === 'session_end' && 
                $lastLine['details']['session_id'] === $sessionId &&
                $lastLine['details']['duration'] > 0) {
                echo "✓ Session end logged successfully\n";
                echo "✓ Session duration tracked: {$lastLine['details']['duration']} seconds\n";
            } else {
                echo "✗ Failed to log session end\n";
            }
        } catch (\Exception $e) {
            echo "✗ Unexpected exception: " . $e->getMessage() . "\n";
        }
    }
}

// Run tests
echo "Starting Session Monitoring Tests\n";
echo "================================\n";
$test = new SessionMonitoringTest();
$test->testSessionStartMonitoring();
$test->testSessionActivityTracking();
$test->testSessionEndMonitoring();
