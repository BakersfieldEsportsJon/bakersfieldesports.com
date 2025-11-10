<?php
require_once dirname(__DIR__) . '/includes/config.php';
require_once dirname(__DIR__) . '/includes/db.php';

date_default_timezone_set('America/Los_Angeles');
header('Content-Type: application/json');

$method = $_SERVER['REQUEST_METHOD'];

if ($method === 'GET') {
    $page = max(1, (int)($_GET['page'] ?? 1));
    $perPage = min(100, max(1, (int)($_GET['per_page'] ?? 20)));
    $offset = ($page - 1) * $perPage;

    $where = [];
    $params = [];

    if (!empty($_GET['status'])) {
        $where[] = 'status = :status';
        $params['status'] = $_GET['status'];
    }

    if (!empty($_GET['date_from'])) {
        $where[] = 'party_date >= :date_from';
        $params['date_from'] = $_GET['date_from'];
    }
    if (!empty($_GET['date_to'])) {
        $where[] = 'party_date <= :date_to';
        $params['date_to'] = $_GET['date_to'];
    }

    if (!empty($_GET['q'])) {
        $where[] = '(booking_reference LIKE :q OR customer_name LIKE :q OR customer_email LIKE :q)';
        $params['q'] = '%' . $_GET['q'] . '%';
    }

    $whereSql = $where ? ('WHERE ' . implode(' AND ', $where)) : '';

    $countStmt = executeQuery("SELECT COUNT(*) as c FROM party_bookings $whereSql", $params);
    $total = (int)($countStmt ? $countStmt->fetch()['c'] : 0);

    $sql = "SELECT id, booking_reference, customer_name, customer_email, customer_phone,
                   party_date, party_time, guest_count, status, created_at
            FROM party_bookings $whereSql
            ORDER BY party_date DESC, party_time DESC
            LIMIT :limit OFFSET :offset";

    $stmt = $pdo->prepare($sql);
    foreach ($params as $k => $v) {
        $stmt->bindValue(':' . $k, $v);
    }
    $stmt->bindValue(':limit', $perPage, PDO::PARAM_INT);
    $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
    $stmt->execute();

    echo json_encode([
        'data' => $stmt->fetchAll(),
        'pagination' => [
            'page' => $page,
            'per_page' => $perPage,
            'total' => $total
        ]
    ]);
    exit;
}

if ($method === 'PATCH') {
    $input = json_decode(file_get_contents('php://input'), true);
    if (!isset($input['id'])) {
        http_response_code(400);
        echo json_encode(['error' => 'Missing id']);
        exit;
    }

    $fields = [];
    $params = ['id' => (int)$input['id']];

    if (isset($input['status'])) {
        $fields[] = 'status = :status';
        $params['status'] = $input['status'];
    }
    if (isset($input['admin_notes'])) {
        $fields[] = 'admin_notes = :admin_notes';
        $params['admin_notes'] = $input['admin_notes'];
    }

    if (!$fields) {
        http_response_code(400);
        echo json_encode(['error' => 'No updatable fields provided']);
        exit;
    }

    $sql = 'UPDATE party_bookings SET ' . implode(', ', $fields) . ', updated_at = ' . (DB_ENGINE === 'mysql' ? 'NOW()' : "datetime('now')") . ' WHERE id = :id';
    $ok = executeQuery($sql, $params) !== false;

    echo json_encode(['updated' => $ok]);
    exit;
}

http_response_code(405);
header('Allow: GET, PATCH');
echo json_encode(['error' => 'Method not allowed']);