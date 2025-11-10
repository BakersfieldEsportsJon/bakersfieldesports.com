<?php
require_once __DIR__.'/../../bootstrap.php';
require_once __DIR__.'/../../includes/security/notification_config.php';

class EmailNotificationTest {
    private $config;
    
    public function __construct() {
        $this->config = include __DIR__.'/../../includes/security/notification_config.php';
    }

    public function testFailedLogin() {
        $this->triggerEvent('failed_login', [
            'ip' => '192.168.1.1',
            'username' => 'testuser',
            'attempts' => 3
        ]);
    }

    public function testAccountLockout() {
        $this->triggerEvent('account_lockout', [
            'ip' => '192.168.1.1',
            'username' => 'testuser',
            'duration' => 900
        ]);
    }

    private function triggerEvent($type, $data) {
        // Simulate notification trigger
        $message = $this->formatMessage($type, $data);
        $this->sendEmail($message);
    }

    private function formatMessage($type, $data) {
        $templates = [
            'failed_login' => "Failed login attempt detected\nIP: {ip}\nUsername: {username}\nAttempts: {attempts}",
            'account_lockout' => "Account lockout triggered\nIP: {ip}\nUsername: {username}\nDuration: {duration} seconds"
        ];
        
        return str_replace(
            array_map(fn($k) => '{'.$k.'}', array_keys($data)),
            array_values($data),
            $templates[$type]
        );
    }

    private function sendEmail($message) {
        // Output instead of actually sending for testing
        echo "TEST EMAIL SENT:\n";
        echo "To: admin@bakersfieldesports.com\n";
        echo "Subject: Security Alert\n";
        echo "Message:\n$message\n\n";
    }
}

// Run tests
$test = new EmailNotificationTest();
$test->testFailedLogin();
$test->testAccountLockout();
