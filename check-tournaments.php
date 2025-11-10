<!DOCTYPE html>
<html>
<head>
    <title>Check Tournaments</title>
    <style>
        body { font-family: Arial, sans-serif; padding: 20px; background: #f0f0f0; }
        .info { background: white; padding: 20px; border-radius: 5px; margin: 10px 0; }
        .success { color: green; }
        .error { color: red; }
        table { border-collapse: collapse; width: 100%; margin: 10px 0; }
        th, td { border: 1px solid #ddd; padding: 8px; text-align: left; }
        th { background: #4CAF50; color: white; }
    </style>
</head>
<body>
    <h1>Tournament Database Check</h1>

    <?php
    require_once __DIR__ . '/admin/includes/db.php';
    require_once __DIR__ . '/includes/startgg/TournamentRepository.php';

    echo '<div class="info">';
    echo '<h2>Database Connection</h2>';
    if ($pdo) {
        echo '<p class="success">✓ Database connected successfully</p>';
    } else {
        echo '<p class="error">✗ Database connection failed</p>';
        exit;
    }
    echo '</div>';

    echo '<div class="info">';
    echo '<h2>Tournament Repository</h2>';
    try {
        $repository = new TournamentRepository($pdo);
        echo '<p class="success">✓ TournamentRepository initialized</p>';
    } catch (Exception $e) {
        echo '<p class="error">✗ Error: ' . htmlspecialchars($e->getMessage()) . '</p>';
        exit;
    }
    echo '</div>';

    echo '<div class="info">';
    echo '<h2>Upcoming Tournaments</h2>';
    try {
        $tournaments = $repository->getUpcomingTournaments(20);
        echo '<p>Found <strong>' . count($tournaments) . '</strong> tournaments</p>';

        if (count($tournaments) > 0) {
            echo '<table>';
            echo '<tr><th>Name</th><th>Slug</th><th>Date</th><th>Registration</th><th>Attendees</th></tr>';
            foreach ($tournaments as $t) {
                $regStatus = $t['is_registration_open'] ? '✓ Open' : '✗ Closed';
                echo '<tr>';
                echo '<td>' . htmlspecialchars($t['name']) . '</td>';
                echo '<td>' . htmlspecialchars($t['slug']) . '</td>';
                echo '<td>' . date('M j, Y', strtotime($t['start_at'])) . '</td>';
                echo '<td>' . $regStatus . '</td>';
                echo '<td>' . ($t['num_attendees'] ?? 0) . '</td>';
                echo '</tr>';
            }
            echo '</table>';
        } else {
            echo '<p class="error">No tournaments found in database.</p>';
            echo '<p>To add tournaments, run the Start.gg sync script or check your STARTGG_INTEGRATION_PLAN.md</p>';
        }
    } catch (Exception $e) {
        echo '<p class="error">✗ Error fetching tournaments: ' . htmlspecialchars($e->getMessage()) . '</p>';
    }
    echo '</div>';

    echo '<div class="info">';
    echo '<h2>Test Modal Link</h2>';
    if (count($tournaments) > 0) {
        $testSlug = $tournaments[0]['slug'];
        echo '<p>Click to test modal with first tournament:</p>';
        echo '<button onclick="window.location.href=\'/test-modal.html?slug=' . urlencode($testSlug) . '\'" style="padding:10px 20px; cursor:pointer;">Test Modal</button>';
        echo '<p>Or visit: <a href="/events/index.php">/events/index.php</a></p>';
    } else {
        echo '<p>No tournaments available to test with.</p>';
    }
    echo '</div>';
    ?>

    <div class="info">
        <h2>Quick Links</h2>
        <ul>
            <li><a href="/events/index.php">Events Page</a></li>
            <li><a href="/test-modal.html">Modal Test Page</a></li>
            <li><a href="/api/tournament-details.php?slug=test">Test API (will show error if no tournaments)</a></li>
        </ul>
    </div>
</body>
</html>
