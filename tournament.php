<?php
/**
 * Tournament Details Page
 * Display full tournament information from start.gg
 */

require_once __DIR__ . '/admin/includes/db.php';
require_once __DIR__ . '/includes/startgg/TournamentRepository.php';

$repository = new TournamentRepository($pdo);

// Get tournament by slug from URL parameter
$slug = $_GET['slug'] ?? '';

if (empty($slug)) {
    header('Location: /events/');
    exit;
}

$tournament = $repository->getTournamentBySlug($slug);

if (!$tournament) {
    header('Location: /events/');
    exit;
}

// Get events for this tournament
$events = $repository->getEventsByTournament($tournament['id']);

$page_title = htmlspecialchars($tournament['name']) . ' - Tournament';
$extra_css = '<link rel="stylesheet" href="/css/tournament.css">';
require_once __DIR__ . '/includes/header.php';
?>

<div class="tournament-hero" style="<?= !empty($tournament['banner_url']) ? 'background-image: url(' . htmlspecialchars($tournament['banner_url']) . ');' : '' ?>">
    <div class="tournament-hero-overlay">
        <div class="container">
            <h1 class="tournament-title"><?= htmlspecialchars($tournament['name']) ?></h1>

            <div class="tournament-meta">
                <div class="meta-item">
                    <i class="icon">📅</i>
                    <span><?= date('F j, Y', strtotime($tournament['start_at'])) ?></span>
                    <?php if ($tournament['end_at']): ?>
                        - <?= date('F j, Y', strtotime($tournament['end_at'])) ?>
                    <?php endif; ?>
                </div>

                <?php if ($tournament['is_online']): ?>
                    <div class="meta-item">
                        <i class="icon">🌐</i>
                        <span>Online Tournament</span>
                    </div>
                <?php elseif ($tournament['venue_name']): ?>
                    <div class="meta-item">
                        <i class="icon">📍</i>
                        <span>
                            <?= htmlspecialchars($tournament['venue_name']) ?>
                            <?php if ($tournament['venue_city']): ?>
                                - <?= htmlspecialchars($tournament['venue_city']) ?>, <?= htmlspecialchars($tournament['venue_state'] ?? '') ?>
                            <?php endif; ?>
                        </span>
                    </div>
                <?php endif; ?>

                <div class="meta-item">
                    <i class="icon">👥</i>
                    <span><?= $tournament['num_attendees'] ?? 0 ?> Registered</span>
                </div>
            </div>

            <?php if ($tournament['is_registration_open']): ?>
                <div class="registration-status open">
                    <span class="status-icon">✓</span>
                    Registration Open
                    <?php if ($tournament['registration_closes_at']): ?>
                        <span class="closes-text">
                            - Closes <?= date('M j, Y g:i A', strtotime($tournament['registration_closes_at'])) ?>
                        </span>
                    <?php endif; ?>
                </div>
            <?php else: ?>
                <div class="registration-status closed">
                    <span class="status-icon">✗</span>
                    Registration Closed
                </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<div class="container tournament-content">
    <div class="tournament-grid">
        <!-- Main Content Column -->
        <div class="tournament-main">

            <!-- Description -->
            <?php if ($tournament['description']): ?>
                <section class="tournament-section">
                    <h2>About This Tournament</h2>
                    <div class="tournament-description">
                        <?= nl2br(htmlspecialchars($tournament['description'])) ?>
                    </div>
                </section>
            <?php endif; ?>

            <!-- Rules -->
            <?php if ($tournament['rules']): ?>
                <section class="tournament-section">
                    <h2>Rules & Format</h2>
                    <div class="tournament-rules">
                        <?= nl2br(htmlspecialchars($tournament['rules'])) ?>
                    </div>
                </section>
            <?php endif; ?>

            <!-- Events -->
            <?php if (!empty($events)): ?>
                <section class="tournament-section">
                    <h2>Events (<?= count($events) ?>)</h2>

                    <div class="events-list">
                        <?php foreach ($events as $event): ?>
                            <div class="event-card">
                                <div class="event-header">
                                    <h3><?= htmlspecialchars($event['name']) ?></h3>
                                    <?php if ($event['videogame_name']): ?>
                                        <span class="event-game"><?= htmlspecialchars($event['videogame_name']) ?></span>
                                    <?php endif; ?>
                                </div>

                                <div class="event-details">
                                    <div class="event-detail-item">
                                        <strong>Entrants:</strong>
                                        <span><?= $event['num_entrants'] ?? 0 ?></span>
                                    </div>

                                    <?php if ($event['entry_fee'] > 0): ?>
                                        <div class="event-detail-item">
                                            <strong>Entry Fee:</strong>
                                            <span>$<?= number_format($event['entry_fee'] / 100, 2) ?></span>
                                        </div>
                                    <?php else: ?>
                                        <div class="event-detail-item">
                                            <strong>Entry:</strong>
                                            <span class="free-entry">FREE</span>
                                        </div>
                                    <?php endif; ?>

                                    <?php if ($event['bracket_type']): ?>
                                        <div class="event-detail-item">
                                            <strong>Format:</strong>
                                            <span><?= htmlspecialchars($event['bracket_type']) ?></span>
                                        </div>
                                    <?php endif; ?>

                                    <?php if ($event['state']): ?>
                                        <div class="event-detail-item">
                                            <strong>Status:</strong>
                                            <span class="event-status <?= strtolower($event['state']) ?>">
                                                <?= htmlspecialchars($event['state']) ?>
                                            </span>
                                        </div>
                                    <?php endif; ?>
                                </div>

                                <?php if ($event['num_entrants'] > 0): ?>
                                    <a href="/tournament.php?slug=<?= urlencode($tournament['slug']) ?>&event=<?= $event['id'] ?>#entrants" class="event-link">
                                        View Entrants →
                                    </a>
                                <?php endif; ?>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </section>
            <?php endif; ?>

            <!-- Entrants Section -->
            <?php
            $selectedEventId = $_GET['event'] ?? null;
            if ($selectedEventId && !empty($events)):
                $entrants = $repository->getEntrantsByEvent($selectedEventId, 100);
                if (!empty($entrants)):
            ?>
                <section class="tournament-section" id="entrants">
                    <h2>Registered Players (<?= count($entrants) ?>)</h2>

                    <div class="entrants-grid">
                        <?php foreach ($entrants as $entrant): ?>
                            <div class="entrant-card">
                                <?php if ($entrant['seed']): ?>
                                    <span class="entrant-seed">#<?= $entrant['seed'] ?></span>
                                <?php endif; ?>

                                <div class="entrant-name">
                                    <?php if ($entrant['prefix']): ?>
                                        <span class="entrant-prefix"><?= htmlspecialchars($entrant['prefix']) ?></span>
                                    <?php endif; ?>
                                    <span class="entrant-tag"><?= htmlspecialchars($entrant['gamer_tag']) ?></span>
                                </div>

                                <?php if ($entrant['final_placement']): ?>
                                    <span class="entrant-placement">
                                        <?= $entrant['final_placement'] ?><?= getOrdinalSuffix($entrant['final_placement']) ?> Place
                                    </span>
                                <?php endif; ?>

                                <?php if ($entrant['is_disqualified']): ?>
                                    <span class="entrant-dq">DQ</span>
                                <?php endif; ?>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </section>
            <?php
                endif;
            endif;
            ?>

        </div>

        <!-- Sidebar -->
        <div class="tournament-sidebar">

            <!-- Quick Info Card -->
            <div class="sidebar-card">
                <h3>Quick Info</h3>

                <div class="info-list">
                    <?php if ($tournament['tournament_type']): ?>
                        <div class="info-item">
                            <strong>Type:</strong>
                            <span><?= htmlspecialchars($tournament['tournament_type']) ?></span>
                        </div>
                    <?php endif; ?>

                    <div class="info-item">
                        <strong>Timezone:</strong>
                        <span><?= htmlspecialchars($tournament['timezone'] ?? 'UTC') ?></span>
                    </div>

                    <div class="info-item">
                        <strong>Events:</strong>
                        <span><?= count($events) ?></span>
                    </div>

                    <div class="info-item">
                        <strong>Total Entrants:</strong>
                        <span><?= $tournament['num_attendees'] ?? 0 ?></span>
                    </div>
                </div>
            </div>

            <!-- Registration CTA -->
            <?php if ($tournament['is_registration_open']): ?>
                <div class="sidebar-card cta-card">
                    <h3>Ready to Compete?</h3>
                    <p>Registration is currently open for this tournament.</p>

                    <?php if ($tournament['url']): ?>
                        <a href="<?= htmlspecialchars($tournament['url']) ?>"
                           class="btn btn-primary"
                           target="_blank"
                           rel="noopener noreferrer">
                            Register on start.gg →
                        </a>
                    <?php endif; ?>

                    <p class="cta-note">
                        <small>
                            <?php if ($tournament['registration_closes_at']): ?>
                                Registration closes <?= date('M j, Y', strtotime($tournament['registration_closes_at'])) ?>
                            <?php endif; ?>
                        </small>
                    </p>
                </div>
            <?php endif; ?>

            <!-- Social Share -->
            <div class="sidebar-card">
                <h3>Share Tournament</h3>
                <div class="share-buttons">
                    <a href="https://twitter.com/intent/tweet?text=<?= urlencode($tournament['name']) ?>&url=<?= urlencode('https://bakersfieldesports.com/tournament.php?slug=' . $tournament['slug']) ?>"
                       class="share-btn twitter"
                       target="_blank">
                        Twitter
                    </a>
                    <a href="https://www.facebook.com/sharer/sharer.php?u=<?= urlencode('https://bakersfieldesports.com/tournament.php?slug=' . $tournament['slug']) ?>"
                       class="share-btn facebook"
                       target="_blank">
                        Facebook
                    </a>
                    <button onclick="copyLink()" class="share-btn copy">
                        Copy Link
                    </button>
                </div>
            </div>

        </div>
    </div>
</div>

<script>
    function copyLink() {
        const url = window.location.href;
        navigator.clipboard.writeText(url).then(() => {
            alert('Link copied to clipboard!');
        }).catch(err => {
            console.error('Failed to copy:', err);
        });
    }
</script>

<?php
// Helper function for ordinal suffixes
function getOrdinalSuffix($number) {
    $ends = ['th', 'st', 'nd', 'rd', 'th', 'th', 'th', 'th', 'th', 'th'];
    if ((($number % 100) >= 11) && (($number % 100) <= 13)) {
        return 'th';
    }
    return $ends[$number % 10];
}

require_once __DIR__ . '/includes/footer.php';
?>
