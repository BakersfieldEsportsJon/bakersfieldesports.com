<!DOCTYPE html>
<html>
<head>
    <title>Modal Setup Verification</title>
    <style>
        body { font-family: Arial, sans-serif; padding: 20px; background: #f0f0f0; }
        .check { background: white; padding: 15px; border-radius: 5px; margin: 10px 0; }
        .success { color: green; }
        .error { color: red; }
        .warning { color: orange; }
        h2 { color: #333; }
        code { background: #e0e0e0; padding: 2px 5px; border-radius: 3px; }
    </style>
</head>
<body>
    <h1>Tournament Modal Setup Verification</h1>

    <?php
    $checks = [
        'Modal CSS' => 'css/tournament-modal.css',
        'Modal JS' => 'events/js/tournament-modal.js',
        'API Endpoint' => 'api/tournament-details.php',
        'Events Page' => 'events/index.php',
        'StartGG Events JS' => 'js/startgg-events.js'
    ];

    foreach ($checks as $name => $path) {
        $fullPath = __DIR__ . '/' . $path;
        echo '<div class="check">';
        if (file_exists($fullPath)) {
            echo "<p class='success'>✓ <strong>$name</strong>: Found</p>";
            echo "<p><code>$path</code></p>";
        } else {
            echo "<p class='error'>✗ <strong>$name</strong>: NOT FOUND</p>";
            echo "<p><code>$path</code></p>";
        }
        echo '</div>';
    }
    ?>

    <div class="check">
        <h2>Check Events Page Integration</h2>
        <?php
        $eventsPage = file_get_contents(__DIR__ . '/events/index.php');

        $checks2 = [
            'Modal CSS Link' => 'tournament-modal.css',
            'Modal JS Script' => 'tournament-modal.js',
            'Modal HTML' => 'tournamentModal'
        ];

        foreach ($checks2 as $name => $needle) {
            if (strpos($eventsPage, $needle) !== false) {
                echo "<p class='success'>✓ $name is present</p>";
            } else {
                echo "<p class='error'>✗ $name is MISSING</p>";
            }
        }
        ?>
    </div>

    <div class="check">
        <h2>Check StartGG Events JS</h2>
        <?php
        $startggJS = file_get_contents(__DIR__ . '/js/startgg-events.js');

        if (strpos($startggJS, 'window.tournamentModal') !== false) {
            echo "<p class='success'>✓ Updated to use new modal system</p>";
        } else {
            echo "<p class='warning'>⚠ May still be using old modal code</p>";
        }
        ?>
    </div>

    <div class="check">
        <h2>Next Steps</h2>
        <ol>
            <li>Make sure XAMPP Apache is running</li>
            <li>Visit: <a href="/bakersfield/events/index.php" target="_blank">http://localhost/bakersfield/events/index.php</a></li>
            <li>Open browser console (F12)</li>
            <li>Type: <code>window.tournamentModal</code> and press Enter - should show an object</li>
            <li>Click "View Details" on any tournament card</li>
            <li>Modal should open with tournament details</li>
        </ol>
    </div>

    <div class="check">
        <h2>Troubleshooting</h2>
        <ul>
            <li>If no tournaments show: Run <code>php api/update-startgg-events.php</code> to sync from Start.gg</li>
            <li>If modal doesn't open: Check browser console for errors (F12)</li>
            <li>If console shows "window.tournamentModal is undefined": Clear browser cache and refresh</li>
            <li>If API gives 404: Check that <code>api/tournament-details.php</code> exists</li>
        </ul>
    </div>

    <div class="check">
        <h2>Test Links</h2>
        <ul>
            <li><a href="/bakersfield/events/index.php" target="_blank">Events Page</a></li>
            <li><a href="/bakersfield/test-modal.html" target="_blank">Modal Test Page</a></li>
            <li><a href="/bakersfield/check-tournaments.php" target="_blank">Check Tournaments in Database</a></li>
        </ul>
    </div>
</body>
</html>
