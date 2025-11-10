<?php
require_once dirname(__DIR__) . '/bootstrap.php';
require_once dirname(__DIR__) . '/admin/includes/db.php';
require_once dirname(__DIR__) . '/vendor/autoload.php';

use Twilio\Rest\Client as TwilioClient;

date_default_timezone_set('America/Los_Angeles');

$now = new DateTime('now', new DateTimeZone('America/Los_Angeles'));
$windowStart = (clone $now)->modify('+48 hours');
$windowEnd = (clone $windowStart)->modify('+1 hour');

// Find parties occurring in ~48 hours that have not been reminded
$sql = 'SELECT * FROM party_bookings WHERE reminder_sent_at IS NULL AND status IN (\'pending\', \'confirmed\') AND party_date = :d';
$stmt = $pdo->prepare($sql);
$stmt->execute(['d' => $windowStart->format('Y-m-d')]);
$bookings = $stmt->fetchAll(PDO::FETCH_ASSOC);

$sid = env('TWILIO_SID');
$token = env('TWILIO_AUTH_TOKEN');
$from = env('TWILIO_PHONE_NUMBER');
$twilio = ($sid && $token && $from) ? new TwilioClient($sid, $token) : null;

foreach ($bookings as $b) {
    $partyDateTime = new DateTime($b['party_date'] . ' ' . $b['party_time'], new DateTimeZone('America/Los_Angeles'));
    $diffHrs = ($partyDateTime->getTimestamp() - $now->getTimestamp()) / 3600;
    if ($diffHrs < 47 || $diffHrs > 49) continue; // approximate 48 hours window

    $to = $b['customer_email'];
    $subject = 'Reminder: Your Party in 48 Hours - ' . $b['booking_reference'];
    $message = 'Hi ' . $b['customer_name'] . ", this is a reminder of your party on " . $partyDateTime->format('l, F j, Y \a\t g:i A') . ". We look forward to seeing you!";
    $headers = "From: Bakersfield eSports Center <noreply@bakersfieldesports.com>\r\nContent-Type: text/plain; charset=UTF-8\r\n";
    @mail($to, $subject, $message, $headers);

    if ($twilio && !empty($b['customer_phone'])) {
        try {
            $twilio->messages->create(
                preg_match('/^\+?1?\d{10,11}$/', $b['customer_phone']) ? $b['customer_phone'] : '+1' . preg_replace('/\D/','',$b['customer_phone']),
                ['from' => $from, 'body' => 'Reminder: Your party is in 48 hours. Ref ' . $b['booking_reference']]
            );
        } catch (Throwable $e) {
            error_log('Twilio reminder error: ' . $e->getMessage());
        }
    }

    // Mark reminder sent
    $pdo->prepare('UPDATE party_bookings SET reminder_sent_at = ' . (DB_ENGINE==='mysql' ? 'NOW()' : "datetime('now')") . ' WHERE id = :id')
        ->execute(['id' => $b['id']]);
}

echo json_encode(['status' => 'ok', 'processed' => count($bookings)]);