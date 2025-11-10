<?php
// Security Monitoring and Logging System
namespace Security;

use PDO;
use PDOException;
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;
use RuntimeException;

class SecurityMonitor {
    private $logFile;
    private $notificationConfig;
    public $db; // Public for testing purposes

    public function __construct() {
        $this->logFile = __DIR__.'/../logs/security.log';
        $this->notificationConfig = include __DIR__.'/notification_config.php';
        
        // Use the global PDO connection
        global $pdo;
        if (!$pdo) {
            throw new RuntimeException('Database connection not available');
        }
        $this->db = $pdo;
    }

    public function logEvent($eventType, $details) {
        $logEntry = [
            'timestamp' => date('Y-m-d H:i:s'),
            'event_type' => $eventType,
            'details' => $details,
            'ip_address' => $_SERVER['REMOTE_ADDR'] ?? 'unknown'
        ];

        file_put_contents(
            $this->logFile,
            json_encode($logEntry).PHP_EOL,
            FILE_APPEND
        );

        $this->checkThresholds($eventType);
        
        // If this is a session-related event, track it
        if (isset($details['session_id'])) {
            $this->trackSessionActivity(
                $details['session_id'],
                $eventType,
                $details['page_url'] ?? null,
                $details['is_admin'] ?? false
            );
        }
    }

    public function trackSessionActivity($sessionId, $activityType, $pageUrl = null, $isAdmin = false) {
        try {
            // Create tables if they don't exist
            $this->ensureTablesExist();

            // First try to find existing session
            $stmt = $this->db->prepare("
                SELECT id FROM active_sessions WHERE session_id = :session_id
            ");
            $stmt->execute([':session_id' => $sessionId]);
            $exists = $stmt->fetch(PDO::FETCH_ASSOC);

            if ($exists) {
                // Update existing session
                $stmt = $this->db->prepare("
                    UPDATE active_sessions 
                    SET activity_type = :activity_type,
                        page_url = :page_url,
                        is_admin_session = :is_admin,
                        last_activity = CURRENT_TIMESTAMP
                    WHERE session_id = :session_id
                ");

                $stmt->execute([
                    ':session_id' => $sessionId,
                    ':activity_type' => $activityType,
                    ':page_url' => $pageUrl,
                    ':is_admin' => $isAdmin
                ]);
            } else {
                // Insert new session
                $stmt = $this->db->prepare("
                    INSERT INTO active_sessions (
                        session_id, user_id, username, ip_address, 
                        user_agent, activity_type, page_url, is_admin_session
                    ) VALUES (
                        :session_id, :user_id, :username, :ip_address,
                        :user_agent, :activity_type, :page_url, :is_admin
                    )
                ");

                $stmt->execute([
                    ':session_id' => $sessionId,
                    ':user_id' => $_SESSION['user_id'] ?? 0,
                    ':username' => $_SESSION['username'] ?? 'anonymous',
                    ':ip_address' => $_SERVER['REMOTE_ADDR'] ?? 'unknown',
                    ':user_agent' => $_SERVER['HTTP_USER_AGENT'] ?? 'unknown',
                    ':activity_type' => $activityType,
                    ':page_url' => $pageUrl,
                    ':is_admin' => $isAdmin
                ]);
            }
        } catch (PDOException $e) {
            error_log("Failed to track session activity: " . $e->getMessage());
            throw new RuntimeException("Session tracking failed");
        }
    }

    public function updateSessionLocation($sessionId, $locationData) {
        try {
            $stmt = $this->db->prepare("
                UPDATE active_sessions 
                SET location_data = :location_data
                WHERE session_id = :session_id
            ");

            $stmt->execute([
                ':session_id' => $sessionId,
                ':location_data' => json_encode($locationData)
            ]);
        } catch (PDOException $e) {
            error_log("Failed to update session location: " . $e->getMessage());
            throw new RuntimeException("Location update failed");
        }
    }

    public function getSessionDuration($sessionId) {
        try {
            $stmt = $this->db->prepare("
                SELECT 
                    TIMESTAMPDIFF(
                        SECOND, 
                        created_at,
                        CASE 
                            WHEN last_activity > NOW() - INTERVAL 15 MINUTE 
                            THEN NOW() 
                            ELSE last_activity 
                        END
                    ) as duration
                FROM active_sessions 
                WHERE session_id = :session_id
            ");

            $stmt->execute([':session_id' => $sessionId]);
            $result = $stmt->fetch(PDO::FETCH_ASSOC);
            
            return $result ? $result['duration'] : 0;
        } catch (PDOException $e) {
            error_log("Failed to get session duration: " . $e->getMessage());
            return 0;
        }
    }

    public function cleanupInactiveSessions($timeout = 3600) {
        try {
            $stmt = $this->db->prepare("
                DELETE FROM active_sessions 
                WHERE last_activity < NOW() - INTERVAL :timeout SECOND
            ");

            $stmt->execute([':timeout' => $timeout]);
            
            return $stmt->rowCount();
        } catch (PDOException $e) {
            error_log("Failed to cleanup sessions: " . $e->getMessage());
            return 0;
        }
    }

    private function checkThresholds($eventType) {
        $thresholds = $this->notificationConfig['thresholds'];
        
        // Count recent events of this type
        $logLines = file($this->logFile);
        $recentEvents = array_filter($logLines, function($line) use ($eventType) {
            $entry = json_decode($line, true);
            return $entry['event_type'] === $eventType && 
                   time() - strtotime($entry['timestamp']) < 3600; // Last hour
        });

        if (count($recentEvents) >= $thresholds['failed_logins']) {
            $this->triggerNotification($eventType, count($recentEvents));
        }
    }

    private function triggerNotification($eventType, $count) {
        $config = $this->notificationConfig;
        
        // SMS Notification
        if ($config['notification_channels']['sms']) {
            $this->sendSmsNotification($eventType, $count);
        }

        // Email Notification
        if ($config['notification_channels']['email']) {
            $this->sendEmailNotification($eventType, $count);
        }
    }

    private function sendSmsNotification($eventType, $count) {
        // Placeholder for SMS notification logic
        // Will require valid Twilio credentials
    }

    private function sendEmailNotification($eventType, $count) {
        $mail = new PHPMailer(true);
        
        try {
            // Server settings
            $mail->isSMTP();
            $mail->Host = $_ENV['MAIL_HOST'] ?? 'smtp.example.com';
            $mail->SMTPAuth = true;
            $mail->Username = $_ENV['MAIL_USERNAME'] ?? 'your_email@example.com';
            $mail->Password = $_ENV['MAIL_PASSWORD'] ?? 'your_email_password';
            $mail->SMTPSecure = $_ENV['MAIL_ENCRYPTION'] ?? 'tls';
            $mail->Port = (int)($_ENV['MAIL_PORT'] ?? 587);

            // Recipients
            $mail->setFrom(
                $_ENV['MAIL_FROM_ADDRESS'] ?? 'security@bakersfieldesports.com',
                $_ENV['MAIL_FROM_NAME'] ?? 'Bakersfield Esports Security'
            );
            $mail->addAddress($_ENV['SECURITY_NOTIFICATION_EMAIL'] ?? 'admin@bakersfieldesports.com');

            // Content
            $mail->isHTML(true);
            $mail->Subject = 'Security Alert: ' . ucfirst($eventType);
            $mail->Body = $this->getEmailTemplate($eventType, $count);
            $mail->AltBody = strip_tags($this->getEmailTemplate($eventType, $count));

            $mail->send();
        } catch (Exception $e) {
            error_log("Email notification failed: " . $e->getMessage());
        }
    }

    private function ensureTablesExist() {
        try {
            // Check if active_sessions table exists
            $stmt = $this->db->query("SHOW TABLES LIKE 'active_sessions'");
            if ($stmt->rowCount() === 0) {
                // Create active_sessions table without foreign key
                $this->db->exec("
                    CREATE TABLE active_sessions (
                        id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
                        session_id VARCHAR(255) UNIQUE,
                        user_id BIGINT UNSIGNED NOT NULL,
                        username VARCHAR(255) NOT NULL,
                        ip_address VARCHAR(45) NOT NULL,
                        user_agent TEXT,
                        activity_type ENUM('login', 'logout', 'page_access', 'admin_action'),
                        page_url VARCHAR(255),
                        is_admin_session BOOLEAN DEFAULT FALSE,
                        location_data JSON,
                        last_activity TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                        INDEX idx_session_activity (session_id, activity_type),
                        INDEX idx_admin_session (is_admin_session),
                        INDEX idx_user_id (user_id)
                    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
                ");
            }

            // Check if security_events table exists
            $stmt = $this->db->query("SHOW TABLES LIKE 'security_events'");
            if ($stmt->rowCount() === 0) {
                // Create security_events table
                $this->db->exec("
                    CREATE TABLE security_events (
                        id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
                        event_type VARCHAR(50) NOT NULL,
                        details JSON,
                        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                        severity ENUM('low', 'medium', 'high') NOT NULL DEFAULT 'low',
                        acknowledged BOOLEAN DEFAULT FALSE,
                        acknowledged_by BIGINT UNSIGNED,
                        acknowledged_at TIMESTAMP NULL,
                        INDEX idx_event_type (event_type),
                        INDEX idx_created_at (created_at),
                        INDEX idx_severity (severity),
                        INDEX idx_acknowledged (acknowledged)
                    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
                ");
            }
        } catch (PDOException $e) {
            error_log("Failed to create tables: " . $e->getMessage());
            throw new RuntimeException("Failed to create required tables");
        }
    }

    private function getEmailTemplate($eventType, $count) {
        $template = file_get_contents(__DIR__.'/templates/email_alert.html');
        return str_replace(
            ['{event_type}', '{count}', '{timestamp}'],
            [ucfirst($eventType), $count, date('Y-m-d H:i:s')],
            $template
        );
    }

    public function generateReport($period = 'daily') {
        // Generate security report based on log data
    }
}
