<?php
/**
 * Notification Webhook API Endpoint
 * Returns the configured webhook URL for location opening notifications
 * Bakersfield eSports Center
 */

header('Content-Type: application/json');

require_once __DIR__ . '/../admin/includes/db.php';

try {
    // Get webhook URL from settings table
    $stmt = $pdo->prepare("SELECT setting_value FROM notification_settings WHERE setting_key = 'webhook_url' AND is_active = 1 LIMIT 1");
    $stmt->execute();
    $result = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($result && !empty($result['setting_value'])) {
        echo json_encode([
            'success' => true,
            'webhookUrl' => $result['setting_value']
        ]);
    } else {
        echo json_encode([
            'success' => false,
            'error' => 'Webhook not configured'
        ]);
    }

} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'error' => 'Database error: ' . $e->getMessage()
    ]);
}
