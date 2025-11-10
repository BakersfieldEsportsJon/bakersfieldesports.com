<?php
class AdminLogger {
    private static $logFile = __DIR__ . '/../logs/admin_login.log';
    private static $debugMode = false;

    public static function init(bool $debugMode = false): void {
        self::$debugMode = $debugMode;
        
        // Ensure logs directory exists
        $logDir = dirname(self::$logFile);
        if (!file_exists($logDir)) {
            mkdir($logDir, 0755, true);
        }
    }

    public static function logLoginAttempt(string $username, bool $success, ?string $reason = null): void {
        $logEntry = [
            'timestamp' => date('Y-m-d H:i:s'),
            'event' => 'login_attempt',
            'username' => $username,
            'ip' => $_SERVER['REMOTE_ADDR'],
            'user_agent' => $_SERVER['HTTP_USER_AGENT'] ?? 'Unknown',
            'success' => $success,
            'trace_id' => uniqid('login_', true)
        ];

        if (!$success && $reason) {
            $logEntry['failure_reason'] = $reason;
        }

        if (self::$debugMode) {
            $logEntry['session_id'] = session_id();
            $logEntry['request_method'] = $_SERVER['REQUEST_METHOD'];
            $logEntry['referer'] = $_SERVER['HTTP_REFERER'] ?? 'Direct';
        }

        self::writeLog($logEntry);
    }

    public static function logError(string $type, string $message, array $context = []): void {
        $logEntry = [
            'timestamp' => date('Y-m-d H:i:s'),
            'event' => 'error',
            'type' => $type,
            'message' => $message,
            'ip' => $_SERVER['REMOTE_ADDR'],
            'trace_id' => uniqid('error_', true)
        ];

        if (self::$debugMode) {
            $logEntry['context'] = $context;
            $logEntry['session_id'] = session_id();
            $logEntry['debug_backtrace'] = debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS);
        }

        self::writeLog($logEntry);
    }

    private static function writeLog(array $data): void {
        $logLine = json_encode($data) . PHP_EOL;
        
        if (file_put_contents(self::$logFile, $logLine, FILE_APPEND | LOCK_EX) === false) {
            error_log('Failed to write to admin login log: ' . self::$logFile);
        }
    }

    public static function getLogPath(): string {
        return self::$logFile;
    }
}
