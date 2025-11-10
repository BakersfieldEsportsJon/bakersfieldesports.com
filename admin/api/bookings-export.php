<?php
require_once dirname(__DIR__) . '/includes/config.php';
require_once dirname(__DIR__) . '/includes/db.php';

date_default_timezone_set('America/Los_Angeles');

$filename = 'party_bookings_' . date('Ymd_His') . '.csv';
header('Content-Type: text/csv');
header('Content-Disposition: attachment; filename=' . $filename);

$where = [];
$params = [];
if (!empty($_GET['status'])) { $where[] = 'status = :status'; $params['status'] = $_GET['status']; }
if (!empty($_GET['date_from'])) { $where[] = 'party_date >= :date_from'; $params['date_from'] = $_GET['date_from']; }
if (!empty($_GET['date_to'])) { $where[] = 'party_date <= :date_to'; $params['date_to'] = $_GET['date_to']; }
if (!empty($_GET['q'])) { $where[] = '(booking_reference LIKE :q OR customer_name LIKE :q OR customer_email LIKE :q)'; $params['q'] = '%' . $_GET['q'] . '%'; }
$whereSql = $where ? ('WHERE ' . implode(' AND ', $where)) : '';

$sql = "SELECT booking_reference, customer_name, customer_email, customer_phone, party_date, party_time, guest_count, status, created_at FROM party_bookings $whereSql ORDER BY party_date, party_time";
$stmt = $pdo->prepare($sql);
foreach ($params as $k => $v) { $stmt->bindValue(':' . $k, $v); }
$stmt->execute();

$out = fopen('php://output', 'w');
fputcsv($out, ['Booking Reference','Name','Email','Phone','Party Date','Party Time','Guests','Status','Created At']);
while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
    fputcsv($out, $row);
}
fclose($out);