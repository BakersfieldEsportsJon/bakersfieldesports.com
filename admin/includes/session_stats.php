<?php
require_once __DIR__ . '/../../includes/security/session_manager.php';
require_once __DIR__ . '/../../includes/security/monitoring.php';
require_once __DIR__ . '/auth.php';

// Verify user is logged in and has admin access
if (!isset($_SESSION['user_id']) || !$_SESSION['is_admin']) {
    http_response_code(403);
    die(json_encode(['error' => 'Unauthorized access']));
}

// Initialize SecurityMonitor
$monitor = new \Security\SecurityMonitor();

try {
    // Get current time window
    $timeWindow = isset($_GET['window']) ? intval($_GET['window']) : 24; // Default 24 hours
    $endTime = time();
    $startTime = $endTime - ($timeWindow * 3600);

    // Prepare SQL for session statistics
    $stats = [
        'active_sessions' => 0,
        'admin_sessions' => 0,
        'avg_duration' => 0,
        'session_history' => [],
        'location_stats' => [
            'local' => 0,
            'national' => 0,
            'international' => 0
        ],
        'recent_alerts' => []
    ];

    // Get active and admin session counts
    $stmt = $monitor->db->prepare("
        SELECT 
            COUNT(*) as total_active,
            SUM(CASE WHEN is_admin_session = 1 THEN 1 ELSE 0 END) as admin_count,
            AVG(TIMESTAMPDIFF(MINUTE, created_at, CASE 
                WHEN last_activity > NOW() - INTERVAL 15 MINUTE THEN NOW()
                ELSE last_activity 
            END)) as avg_duration
        FROM active_sessions 
        WHERE last_activity > NOW() - INTERVAL 15 MINUTE
    ");
    $stmt->execute();
    $result = $stmt->fetch(PDO::FETCH_ASSOC);
    
    $stats['active_sessions'] = intval($result['total_active']);
    $stats['admin_sessions'] = intval($result['admin_count']);
    $stats['avg_duration'] = round($result['avg_duration']);

    // Get session history for chart
    $stmt = $monitor->db->prepare("
        SELECT 
            DATE_FORMAT(created_at, '%H:00') as hour,
            COUNT(*) as count
        FROM active_sessions 
        WHERE created_at > DATE_SUB(NOW(), INTERVAL ? HOUR)
        GROUP BY hour
        ORDER BY hour
    ");
    $stmt->execute([$timeWindow]);
    $stats['session_history'] = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Get location statistics
    $stmt = $monitor->db->prepare("
        SELECT 
            JSON_EXTRACT(location_data, '$.country') as country,
            COUNT(*) as count
        FROM active_sessions 
        WHERE location_data IS NOT NULL
        AND last_activity > NOW() - INTERVAL 15 MINUTE
        GROUP BY country
    ");
    $stmt->execute();
    $locations = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Process location stats
    $userCountry = 'US'; // Replace with your actual country
    foreach ($locations as $loc) {
        $country = json_decode($loc['country'], true);
        if ($country === $userCountry) {
            $stats['location_stats']['local'] += $loc['count'];
        } elseif (in_array($country, ['CA', 'MX'])) { // Neighboring countries
            $stats['location_stats']['national'] += $loc['count'];
        } else {
            $stats['location_stats']['international'] += $loc['count'];
        }
    }

    // Get recent security alerts
    $stmt = $monitor->db->prepare("
        SELECT 
            event_type,
            details,
            created_at,
            CASE 
                WHEN event_type IN ('login_failed', 'session_security_violation') THEN 'high'
                WHEN event_type IN ('suspicious_location', 'session_expired') THEN 'medium'
                ELSE 'low'
            END as severity
        FROM security_events
        WHERE created_at > DATE_SUB(NOW(), INTERVAL 1 HOUR)
        ORDER BY created_at DESC
        LIMIT 5
    ");
    $stmt->execute();
    $stats['recent_alerts'] = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Return JSON response
    header('Content-Type: application/json');
    echo json_encode($stats);

} catch (PDOException $e) {
    http_response_code(500);
    die(json_encode(['error' => 'Database error occurred']));
} catch (Exception $e) {
    http_response_code(500);
    die(json_encode(['error' => 'An error occurred']));
}
