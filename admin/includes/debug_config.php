<?php
// Debug configuration settings
define('DEBUG_MODE', true); // Temporary debug mode

// Maximum log file size in bytes (50MB)
define('MAX_LOG_SIZE', 52428800);

// Log rotation settings
define('MAX_LOG_FILES', 5);

// Check and rotate logs if needed
function rotateLogFile(string $logFile): void {
    if (!file_exists($logFile) || filesize($logFile) < MAX_LOG_SIZE) {
        return;
    }

    for ($i = MAX_LOG_FILES - 1; $i > 0; $i--) {
        $oldFile = $logFile . '.' . $i;
        $newFile = $logFile . '.' . ($i + 1);
        
        if (file_exists($oldFile)) {
            rename($oldFile, $newFile);
        }
    }

    rename($logFile, $logFile . '.1');
}

// Register shutdown function to rotate logs
register_shutdown_function(function() {
    $logFile = dirname(__DIR__) . '/logs/admin_login.log';
    rotateLogFile($logFile);
});
