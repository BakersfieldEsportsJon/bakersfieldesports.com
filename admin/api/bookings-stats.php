<?php
require_once dirname(__DIR__) . '/includes/config.php';
require_once dirname(__DIR__) . '/includes/db.php';

date_default_timezone_set('America/Los_Angeles');
header('Content-Type: application/json');

$today = new DateTime('today', new DateTimeZone('America/Los_Angeles'));
$monthStart = (clone $today)->modify('first day of this month');

$total = executeQuery('SELECT COUNT(*) c FROM party_bookings')->fetch()['c'] ?? 0;
$upcoming = executeQuery("SELECT COUNT(*) c FROM party_bookings WHERE party_date >= :d", ['d' => $today->format('Y-m-d')])->fetch()['c'] ?? 0;
$mtd = executeQuery("SELECT COUNT(*) c FROM party_bookings WHERE party_date >= :d", ['d' => $monthStart->format('Y-m-d')])->fetch()['c'] ?? 0;

echo json_encode([
    'total' => (int)$total,
    'upcoming' => (int)$upcoming,
    'month_to_date' => (int)$mtd
]);