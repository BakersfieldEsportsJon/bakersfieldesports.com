<?php
/**
 * Start.gg Integration - Admin Dashboard
 * Manage tournament sync, view statistics, and configure settings
 */

session_start();

require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/auth.php';

if (!isset($_SESSION['user_id'])) {
    header('Location: /admin/login.php');
    exit;
}

require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../../includes/startgg/TournamentSync.php';
require_once __DIR__ . '/../../includes/startgg/TournamentRepository.php';

$sync = new TournamentSync($pdo);
$repository = new TournamentRepository($pdo);

// Get statistics
$stats = $sync->getSyncStats();
$tournamentStats = $repository->getTournamentStats();
$upcomingTournaments = $repository->getUpcomingTournaments(5);
$syncHistory = $sync->getSyncHistory(5);

$page_title = 'Start.gg Dashboard';
$base_path = '../../';
require_once __DIR__ . '/../includes/admin_head.php';
?>

<style>
    .startgg-dashboard {
        padding: 2em;
        max-width: 1400px;
        margin: 0 auto;
    }

    .stats-grid {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
        gap: 1.5em;
        margin: 2em 0;
    }

    .stat-card {
        background: var(--darker-bg);
        padding: 1.5em;
        border-radius: 10px;
        border-left: 4px solid var(--primary-color);
    }

    .stat-card h3 {
        margin: 0 0 0.5em 0;
        color: var(--light-color);
        font-size: 0.9em;
        text-transform: uppercase;
        opacity: 0.8;
    }

    .stat-card .stat-value {
        font-size: 2.5em;
        font-weight: bold;
        color: var(--primary-color);
        margin: 0;
    }

    .sync-section {
        background: var(--darker-bg);
        padding: 2em;
        border-radius: 10px;
        margin: 2em 0;
    }

    .sync-button {
        background: var(--primary-color);
        color: white;
        padding: 1em 2em;
        border: none;
        border-radius: 5px;
        cursor: pointer;
        font-size: 1em;
        transition: all 0.3s;
    }

    .sync-button:hover {
        background: var(--hover-color);
        transform: translateY(-2px);
    }

    .sync-button:disabled {
        background: #555;
        cursor: not-allowed;
        transform: none;
    }

    .tournament-list {
        margin: 2em 0;
    }

    .tournament-item {
        background: var(--darker-bg);
        padding: 1.5em;
        margin: 1em 0;
        border-radius: 8px;
        border-left: 4px solid var(--primary-color);
    }

    .tournament-item h4 {
        margin: 0 0 0.5em 0;
        color: var(--primary-color);
    }

    .tournament-meta {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(150px, 1fr));
        gap: 1em;
        margin-top: 1em;
        font-size: 0.9em;
        color: var(--light-color);
        opacity: 0.8;
    }

    .sync-log {
        background: #0a0a0a;
        padding: 1em;
        border-radius: 5px;
        max-height: 400px;
        overflow-y: auto;
        font-family: monospace;
        font-size: 0.9em;
        display: none;
    }

    .sync-log.active {
        display: block;
        margin-top: 1em;
    }

    .sync-log-entry {
        padding: 0.5em;
        margin: 0.25em 0;
        border-left: 3px solid #333;
    }

    .sync-log-entry.info {
        border-left-color: #00d4ff;
    }

    .sync-log-entry.error {
        border-left-color: #ff4444;
        background: #ff444410;
    }

    .sync-log-entry.warning {
        border-left-color: #ffbb33;
    }

    .badge {
        display: inline-block;
        padding: 0.25em 0.75em;
        border-radius: 12px;
        font-size: 0.85em;
        font-weight: bold;
    }

    .badge-success {
        background: #00C85120;
        color: #00C851;
    }

    .badge-error {
        background: #ff444420;
        color: #ff4444;
    }

    .badge-info {
        background: #00d4ff20;
        color: #00d4ff;
    }

    .sync-history {
        margin-top: 2em;
    }

    .history-item {
        background: var(--darker-bg);
        padding: 1em;
        margin: 0.5em 0;
        border-radius: 5px;
        display: grid;
        grid-template-columns: auto 1fr auto auto;
        gap: 1em;
        align-items: center;
    }

    .spinner {
        border: 3px solid #333;
        border-top: 3px solid var(--primary-color);
        border-radius: 50%;
        width: 20px;
        height: 20px;
        animation: spin 1s linear infinite;
        display: inline-block;
        margin-right: 0.5em;
    }

    @keyframes spin {
        0% { transform: rotate(0deg); }
        100% { transform: rotate(360deg); }
    }
</style>

<div class="startgg-dashboard">
    <div style="display: flex; justify-content: space-between; align-items: center;">
        <h1>🎮 Start.gg Integration</h1>
        <a href="setup/" class="sync-button" style="display: inline-block; text-decoration: none; padding: 0.5em 1.5em;">⚙️ Setup</a>
    </div>

    <!-- Statistics Grid -->
    <div class="stats-grid">
        <div class="stat-card">
            <h3>Total Tournaments</h3>
            <div class="stat-value"><?= $tournamentStats['total_tournaments'] ?? 0 ?></div>
        </div>

        <div class="stat-card">
            <h3>Upcoming</h3>
            <div class="stat-value"><?= $tournamentStats['upcoming'] ?? 0 ?></div>
        </div>

        <div class="stat-card">
            <h3>Open Registration</h3>
            <div class="stat-value"><?= $tournamentStats['open_registration'] ?? 0 ?></div>
        </div>

        <div class="stat-card">
            <h3>Total Attendees</h3>
            <div class="stat-value"><?= $tournamentStats['total_attendees'] ?? 0 ?></div>
        </div>
    </div>

    <!-- Manual Sync Section -->
    <div class="sync-section">
        <h2>Sync Control</h2>
        <p>Manually sync tournaments from start.gg or test the connection.</p>

        <?php if (!empty($stats['last_sync'])): ?>
            <p>
                <strong>Last Sync:</strong>
                <?= date('M j, Y g:i A', strtotime($stats['last_sync']['created_at'])) ?>
                -
                <span class="badge <?= $stats['last_sync']['status'] === 'success' ? 'badge-success' : 'badge-error' ?>">
                    <?= strtoupper($stats['last_sync']['status']) ?>
                </span>
                (<?= $stats['last_sync']['tournaments_synced'] ?> tournaments, <?= $stats['last_sync']['errors_count'] ?> errors)
            </p>
        <?php else: ?>
            <p><em>No sync performed yet</em></p>
        <?php endif; ?>

        <div style="margin-top: 1em;">
            <button class="sync-button" id="syncButton" onclick="runSync()">
                🔄 Sync Now
            </button>
            <button class="sync-button" id="testButton" onclick="testSync()" style="background: #00d4ff;">
                🧪 Test Connection
            </button>
        </div>

        <div id="syncLog" class="sync-log"></div>
    </div>

    <!-- Upcoming Tournaments -->
    <?php if (!empty($upcomingTournaments)): ?>
        <div class="tournament-list">
            <h2>Upcoming Tournaments</h2>

            <?php foreach ($upcomingTournaments as $tournament): ?>
                <div class="tournament-item">
                    <h4><?= htmlspecialchars($tournament['name']) ?></h4>
                    <div class="tournament-meta">
                        <div>
                            📅 <?= date('M j, Y', strtotime($tournament['start_at'])) ?>
                        </div>
                        <div>
                            👥 <?= $tournament['num_attendees'] ?? 0 ?> attendees
                        </div>
                        <div>
                            <?php if ($tournament['is_registration_open']): ?>
                                <span class="badge badge-success">✓ Registration Open</span>
                            <?php else: ?>
                                <span class="badge badge-error">✗ Registration Closed</span>
                            <?php endif; ?>
                        </div>
                        <div>
                            <?php if ($tournament['is_online']): ?>
                                <span class="badge badge-info">🌐 Online</span>
                            <?php else: ?>
                                <span class="badge badge-info">📍 <?= htmlspecialchars($tournament['venue_city'] ?? 'In Person') ?></span>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>

            <a href="/events/" style="color: var(--primary-color);">View all tournaments →</a>
        </div>
    <?php else: ?>
        <div class="sync-section">
            <h2>No Tournaments Found</h2>
            <p>No upcoming tournaments in the database. Run a sync to fetch tournaments from start.gg.</p>
        </div>
    <?php endif; ?>

    <!-- Sync History -->
    <?php if (!empty($syncHistory)): ?>
        <div class="sync-history">
            <h2>Recent Sync History</h2>

            <?php foreach ($syncHistory as $log): ?>
                <div class="history-item">
                    <div>
                        <span class="badge <?= $log['status'] === 'success' ? 'badge-success' : 'badge-error' ?>">
                            <?= strtoupper($log['status']) ?>
                        </span>
                    </div>
                    <div>
                        <?= date('M j, Y g:i A', strtotime($log['created_at'])) ?>
                    </div>
                    <div>
                        <?= $log['tournaments_synced'] ?> synced
                    </div>
                    <div>
                        <?= $log['errors_count'] ?> errors
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>
</div>

<script>
    function runSync() {
        const button = document.getElementById('syncButton');
        const log = document.getElementById('syncLog');

        button.disabled = true;
        button.innerHTML = '<span class="spinner"></span> Syncing...';
        log.classList.add('active');
        log.innerHTML = '<div class="sync-log-entry info">Starting sync...</div>';

        fetch('ajax/sync.php?action=sync', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/x-www-form-urlencoded'
            },
            body: 'csrf_token=<?= htmlspecialchars(get_csrf_token()) ?>'
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                log.innerHTML = '<div class="sync-log-entry info">✓ Sync completed successfully!</div>';
                log.innerHTML += `<div class="sync-log-entry info">Synced: ${data.synced} tournaments</div>`;
                log.innerHTML += `<div class="sync-log-entry info">Errors: ${data.errors}</div>`;

                if (data.log && data.log.length > 0) {
                    log.innerHTML += '<br><strong>Detailed Log:</strong>';
                    data.log.forEach(entry => {
                        log.innerHTML += `<div class="sync-log-entry ${entry.level}">[${entry.timestamp}] ${entry.message}</div>`;
                    });
                }

                // Reload page after 2 seconds to show updated stats
                setTimeout(() => {
                    location.reload();
                }, 2000);
            } else {
                log.innerHTML = `<div class="sync-log-entry error">✗ Sync failed: ${data.error}</div>`;

                if (data.log && data.log.length > 0) {
                    log.innerHTML += '<br><strong>Error Log:</strong>';
                    data.log.forEach(entry => {
                        log.innerHTML += `<div class="sync-log-entry ${entry.level}">[${entry.timestamp}] ${entry.message}</div>`;
                    });
                }
            }
        })
        .catch(error => {
            log.innerHTML = `<div class="sync-log-entry error">✗ Request failed: ${error.message}</div>`;
        })
        .finally(() => {
            button.disabled = false;
            button.innerHTML = '🔄 Sync Now';
        });
    }

    function testSync() {
        const button = document.getElementById('testButton');
        const log = document.getElementById('syncLog');

        button.disabled = true;
        button.innerHTML = '<span class="spinner"></span> Testing...';
        log.classList.add('active');
        log.innerHTML = '<div class="sync-log-entry info">Testing connection...</div>';

        fetch('ajax/sync.php?action=test')
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                log.innerHTML = '<div class="sync-log-entry info">✓ Connection test successful!</div>';
                log.innerHTML += `<div class="sync-log-entry info">Authenticated as: ${data.user}</div>`;
                log.innerHTML += `<div class="sync-log-entry info">Tournaments found: ${data.tournaments_found}</div>`;

                if (data.sample && data.sample.length > 0) {
                    log.innerHTML += '<br><strong>Sample Tournaments:</strong>';
                    data.sample.forEach(tournament => {
                        log.innerHTML += `<div class="sync-log-entry info">• ${tournament.name}</div>`;
                    });
                }
            } else {
                log.innerHTML = `<div class="sync-log-entry error">✗ Test failed: ${data.error}</div>`;
            }
        })
        .catch(error => {
            log.innerHTML = `<div class="sync-log-entry error">✗ Request failed: ${error.message}</div>`;
        })
        .finally(() => {
            button.disabled = false;
            button.innerHTML = '🧪 Test Connection';
        });
    }
</script>

<?php require_once __DIR__ . '/../includes/admin_footer.php'; ?>
