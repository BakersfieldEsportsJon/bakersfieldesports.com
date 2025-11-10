<?php
require_once __DIR__ . '/config.php';
require_once __DIR__ . '/User.php';
require_once __DIR__ . '/AdminLogger.php';

/**
 * Check if user is authenticated
 */
function isAuthenticated(): bool {
    // Check if user is logged in
    if (!isset($_SESSION['user_id'])) {
        return false;
    }

    // Verify session integrity
    if (!isset($_SESSION['ip']) || $_SESSION['ip'] !== $_SERVER['REMOTE_ADDR']) {
        // IP address changed, potential session hijacking
        session_unset();
        session_destroy();
        return false;
    }

    // Verify session timeout
    if (!isset($_SESSION['last_activity']) || (time() - $_SESSION['last_activity'] > 1800)) {
        session_unset();
        session_destroy();
        return false;
    }

    // Update last activity
    $_SESSION['last_activity'] = time();

    return true;
}

/**
 * Get photos with filters
 */
function getPhotos($year = null, $month = null, $day = null, $event = null, $page = 1, $perPage = 10, $sort = 'date'): array {
    global $pdo;

    $where = [];
    $params = [];

    if ($year !== null) {
        $where[] = "YEAR(date_taken) = ?";
        $params[] = $year;
    }

    if ($month !== null) {
        $where[] = "MONTH(date_taken) = ?";
        $params[] = $month;
    }

    if ($day !== null) {
        $where[] = "DAY(date_taken) = ?";
        $params[] = $day;
    }

    if ($event !== null) {
        if ($event === 'uncategorized') {
            $where[] = "(event_name IS NULL OR event_name = '')";
        } else {
            $where[] = "event_name = ?";
            $params[] = $event;
        }
    }

    $whereClause = !empty($where) ? "WHERE " . implode(" AND ", $where) : "";

    // Determine sort order
    $orderBy = match($sort) {
        'date_asc' => 'date_taken ASC',
        'name' => 'original_filename ASC',
        'name_desc' => 'original_filename DESC',
        default => 'date_taken DESC'
    };

    // Get total count
    $countSql = "SELECT COUNT(*) FROM photos $whereClause";
    $stmt = $pdo->prepare($countSql);
    $stmt->execute($params);
    $total = $stmt->fetchColumn();

    // Calculate pagination
    $totalPages = ceil($total / $perPage);
    $offset = ($page - 1) * $perPage;

    // Get photos - select only needed columns
    $sql = "SELECT id, filename, original_filename, file_path, thumbnail_path,
                   date_taken, year, month, day, event, width, height, file_size,
                   uploaded_at
            FROM photos $whereClause ORDER BY $orderBy LIMIT ? OFFSET ?";
    $stmt = $pdo->prepare($sql);
    $params[] = $perPage;
    $params[] = $offset;
    $stmt->execute($params);
    $photos = $stmt->fetchAll(PDO::FETCH_ASSOC);

    return [
        'photos' => $photos,
        'total' => $total,
        'pages' => $totalPages
    ];
}

/**
 * Get date ranges for photos
 */
function getDateRanges(): array {
    global $pdo;
    
    $sql = "SELECT 
                YEAR(date_taken) as year,
                MONTH(date_taken) as month,
                COUNT(*) as count
            FROM photos
            GROUP BY YEAR(date_taken), MONTH(date_taken)
            ORDER BY year DESC, month DESC";
            
    $stmt = $pdo->query($sql);
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

/**
 * Get unique event names
 */
function getEvents(): array {
    global $pdo;
    
    $sql = "SELECT DISTINCT event_name 
            FROM photos 
            WHERE event_name IS NOT NULL 
            AND event_name != ''
            ORDER BY event_name";
            
    $stmt = $pdo->query($sql);
    return $stmt->fetchAll(PDO::FETCH_COLUMN);
}
