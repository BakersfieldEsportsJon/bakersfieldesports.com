-- Create security events table for storing alerts and monitoring data
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
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Create view for active session statistics
CREATE OR REPLACE VIEW v_session_stats AS
SELECT 
    COUNT(*) as total_active,
    SUM(CASE WHEN is_admin_session = 1 THEN 1 ELSE 0 END) as admin_sessions,
    AVG(TIMESTAMPDIFF(MINUTE, created_at, 
        CASE 
            WHEN last_activity > NOW() - INTERVAL 15 MINUTE THEN NOW()
            ELSE last_activity 
        END
    )) as avg_duration_minutes
FROM active_sessions 
WHERE last_activity > NOW() - INTERVAL 15 MINUTE;

-- Create view for location statistics
CREATE OR REPLACE VIEW v_location_stats AS
SELECT 
    JSON_UNQUOTE(JSON_EXTRACT(location_data, '$.country')) as country,
    COUNT(*) as session_count
FROM active_sessions 
WHERE location_data IS NOT NULL
AND last_activity > NOW() - INTERVAL 15 MINUTE
GROUP BY country;

-- Create stored procedure for cleaning up old events
DELIMITER //
CREATE PROCEDURE sp_cleanup_old_events(IN days_to_keep INT)
BEGIN
    DELETE FROM security_events 
    WHERE created_at < DATE_SUB(NOW(), INTERVAL days_to_keep DAY)
    AND acknowledged = TRUE;
END //
DELIMITER ;

-- Create stored procedure for session analysis
DELIMITER //
CREATE PROCEDURE sp_analyze_sessions()
BEGIN
    -- Check for suspicious concurrent sessions
    INSERT INTO security_events (event_type, details, severity)
    SELECT 
        'concurrent_sessions',
        JSON_OBJECT(
            'user_id', user_id,
            'username', username,
            'session_count', COUNT(*)
        ),
        CASE 
            WHEN COUNT(*) > 5 THEN 'high'
            WHEN COUNT(*) > 3 THEN 'medium'
            ELSE 'low'
        END as severity
    FROM active_sessions
    WHERE last_activity > NOW() - INTERVAL 15 MINUTE
    GROUP BY user_id, username
    HAVING COUNT(*) > 2;

    -- Check for suspicious location changes
    INSERT INTO security_events (event_type, details, severity)
    SELECT 
        'location_change',
        JSON_OBJECT(
            'user_id', a1.user_id,
            'username', a1.username,
            'old_location', a1.location_data,
            'new_location', a2.location_data,
            'time_difference', TIMESTAMPDIFF(MINUTE, a1.last_activity, a2.last_activity)
        ),
        'high' as severity
    FROM active_sessions a1
    JOIN active_sessions a2 ON a1.user_id = a2.user_id
    WHERE a1.location_data != a2.location_data
    AND TIMESTAMPDIFF(MINUTE, a1.last_activity, a2.last_activity) < 60;
END //
DELIMITER ;

-- Create event to run session analysis periodically
CREATE EVENT IF NOT EXISTS e_session_analysis
ON SCHEDULE EVERY 5 MINUTE
DO CALL sp_analyze_sessions();

-- Create event to clean up old events
CREATE EVENT IF NOT EXISTS e_cleanup_events
ON SCHEDULE EVERY 1 DAY
DO CALL sp_cleanup_old_events(30);
