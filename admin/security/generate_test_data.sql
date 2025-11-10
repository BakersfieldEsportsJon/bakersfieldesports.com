-- Test Data Generation Script for Security Dashboard
-- This script generates sample data to verify dashboard functionality

-- Clear existing test data
DELETE FROM security_events WHERE event_type LIKE 'TEST_%';
DELETE FROM active_sessions WHERE username LIKE 'test_user%';

-- Insert test sessions with various locations
INSERT INTO active_sessions (
    session_id, 
    user_id, 
    username, 
    ip_address, 
    user_agent, 
    activity_type,
    page_url,
    is_admin_session,
    location_data,
    created_at,
    last_activity
) VALUES
-- Local user
('test_session_1', 1, 'test_user1', '192.168.1.1', 'Mozilla/5.0', 'login', '/admin/dashboard.php', true, 
 '{"country":"US","city":"Bakersfield","region":"CA","latitude":35.3733,"longitude":-119.0187}',
 NOW() - INTERVAL 2 HOUR, NOW()),

-- National user
('test_session_2', 2, 'test_user2', '10.0.0.1', 'Chrome/120.0', 'page_access', '/events/index.php', false,
 '{"country":"US","city":"Seattle","region":"WA","latitude":47.6062,"longitude":-122.3321}',
 NOW() - INTERVAL 1 HOUR, NOW() - INTERVAL 5 MINUTE),

-- International user
('test_session_3', 3, 'test_user3', '172.16.0.1', 'Safari/605.1.15', 'page_access', '/gallery/index.php', false,
 '{"country":"GB","city":"London","region":"ENG","latitude":51.5074,"longitude":-0.1278}',
 NOW() - INTERVAL 30 MINUTE, NOW() - INTERVAL 2 MINUTE);

-- Insert test security events
INSERT INTO security_events (
    event_type,
    details,
    created_at,
    severity,
    acknowledged
) VALUES
-- High severity event
('TEST_LOGIN_FAILED', 
 '{"user_id":1,"username":"test_user1","ip":"192.168.1.1","attempt_count":5}',
 NOW() - INTERVAL 5 MINUTE,
 'high',
 false),

-- Medium severity event
('TEST_SUSPICIOUS_LOCATION',
 '{"user_id":2,"username":"test_user2","old_location":"Seattle, WA","new_location":"Portland, OR"}',
 NOW() - INTERVAL 15 MINUTE,
 'medium',
 false),

-- Low severity event
('TEST_SESSION_CREATED',
 '{"user_id":3,"username":"test_user3","ip":"172.16.0.1"}',
 NOW() - INTERVAL 1 HOUR,
 'low',
 true);

-- Insert session history data
INSERT INTO active_sessions (
    session_id,
    user_id,
    username,
    ip_address,
    user_agent,
    activity_type,
    created_at,
    last_activity
)
SELECT 
    CONCAT('test_history_', n),
    FLOOR(RAND() * 100) + 1,
    CONCAT('test_user', FLOOR(RAND() * 10) + 1),
    CONCAT('192.168.', FLOOR(RAND() * 255), '.', FLOOR(RAND() * 255)),
    'Mozilla/5.0',
    'page_access',
    NOW() - INTERVAL n HOUR,
    NOW() - INTERVAL (n-1) HOUR
FROM (
    SELECT a.N + b.N * 10 + 1 n
    FROM (SELECT 0 N UNION SELECT 1 UNION SELECT 2 UNION SELECT 3 UNION SELECT 4 UNION SELECT 5 UNION SELECT 6 UNION SELECT 7 UNION SELECT 8 UNION SELECT 9) a,
         (SELECT 0 N UNION SELECT 1 UNION SELECT 2) b
    ORDER BY n
) numbers
WHERE n <= 24;

-- Verify data was inserted
SELECT 'Active Sessions Count:', COUNT(*) FROM active_sessions WHERE username LIKE 'test_user%';
SELECT 'Security Events Count:', COUNT(*) FROM security_events WHERE event_type LIKE 'TEST_%';
SELECT 'Session History Count:', COUNT(*) FROM active_sessions WHERE session_id LIKE 'test_history_%';
