<?php
/**
 * Start.gg Tournaments Display Component
 * Include this file on the events page to show synced tournaments
 *
 * Usage:
 *   require_once __DIR__ . '/includes/startgg_tournaments_display.php';
 *   displayStartGGTournaments($pdo, $limit = 10);
 */

require_once __DIR__ . '/../admin/includes/db.php';
require_once __DIR__ . '/startgg/TournamentRepository.php';

function displayStartGGTournaments($pdo, $limit = 10, $showFilters = true) {
    $repository = new TournamentRepository($pdo);

    // Get filter parameters
    $filter = $_GET['filter'] ?? 'upcoming';
    $search = $_GET['search'] ?? '';

    // Fetch tournaments based on filter
    if ($filter === 'open') {
        $tournaments = $repository->getOpenRegistrationTournaments();
    } else {
        $tournaments = $repository->getUpcomingTournaments($limit);
    }

    // Apply search if provided
    if (!empty($search)) {
        $tournaments = array_filter($tournaments, function($tournament) use ($search) {
            return stripos($tournament['name'], $search) !== false ||
                   stripos($tournament['venue_city'], $search) !== false;
        });
    }

    if (empty($tournaments)) {
        displayNoTournaments($filter, $search);
        return;
    }
    ?>

    <div class="startgg-tournaments-section">
        <?php if ($showFilters): ?>
            <div class="tournaments-header">
                <h2 class="tournaments-title">Upcoming Tournaments</h2>

                <div class="tournaments-filters">
                    <form method="GET" class="filter-form" id="tournamentFilters">
                        <!-- Preserve other GET params -->
                        <?php foreach ($_GET as $key => $value): ?>
                            <?php if ($key !== 'filter' && $key !== 'search'): ?>
                                <input type="hidden" name="<?= htmlspecialchars($key) ?>" value="<?= htmlspecialchars($value) ?>">
                            <?php endif; ?>
                        <?php endforeach; ?>

                        <div class="filter-group">
                            <select name="filter" onchange="this.form.submit()" class="filter-select">
                                <option value="upcoming" <?= $filter === 'upcoming' ? 'selected' : '' ?>>All Upcoming</option>
                                <option value="open" <?= $filter === 'open' ? 'selected' : '' ?>>Open Registration</option>
                            </select>
                        </div>

                        <div class="filter-group">
                            <input type="text"
                                   name="search"
                                   placeholder="Search tournaments..."
                                   value="<?= htmlspecialchars($search) ?>"
                                   class="search-input">
                            <button type="submit" class="search-btn">Search</button>
                        </div>
                    </form>
                </div>
            </div>
        <?php endif; ?>

        <div class="tournaments-grid">
            <?php foreach ($tournaments as $tournament): ?>
                <?php displayTournamentCard($tournament, $repository); ?>
            <?php endforeach; ?>
        </div>

        <div class="tournaments-footer">
            <p class="powered-by">
                Powered by <a href="https://start.gg" target="_blank" rel="noopener">start.gg</a>
            </p>
        </div>
    </div>

    <?php
}

function displayTournamentCard($tournament, $repository) {
    $events = $repository->getEventsByTournament($tournament['id']);
    $eventCount = count($events);
    $isRegistrationOpen = $tournament['is_registration_open'];
    $daysUntil = ceil((strtotime($tournament['start_at']) - time()) / 86400);
    ?>

    <div class="tournament-card <?= $isRegistrationOpen ? 'registration-open' : '' ?>">
        <?php if (!empty($tournament['image_url'])): ?>
            <div class="tournament-card-image" style="background-image: url('<?= htmlspecialchars($tournament['image_url']) ?>');">
                <?php if ($isRegistrationOpen): ?>
                    <div class="registration-badge">Registration Open</div>
                <?php endif; ?>
            </div>
        <?php else: ?>
            <div class="tournament-card-image placeholder">
                <div class="placeholder-content">
                    <span class="placeholder-icon">🎮</span>
                </div>
                <?php if ($isRegistrationOpen): ?>
                    <div class="registration-badge">Registration Open</div>
                <?php endif; ?>
            </div>
        <?php endif; ?>

        <div class="tournament-card-content">
            <h3 class="tournament-card-title">
                <a href="/tournament.php?slug=<?= urlencode($tournament['slug']) ?>">
                    <?= htmlspecialchars($tournament['name']) ?>
                </a>
            </h3>

            <div class="tournament-card-meta">
                <div class="meta-row">
                    <span class="meta-icon">📅</span>
                    <span class="meta-text">
                        <?= date('M j, Y', strtotime($tournament['start_at'])) ?>
                        <?php if ($daysUntil >= 0): ?>
                            <span class="days-until">(<?= $daysUntil ?> day<?= $daysUntil !== 1 ? 's' : '' ?>)</span>
                        <?php endif; ?>
                    </span>
                </div>

                <?php if ($tournament['is_online']): ?>
                    <div class="meta-row">
                        <span class="meta-icon">🌐</span>
                        <span class="meta-text">Online</span>
                    </div>
                <?php elseif ($tournament['venue_city']): ?>
                    <div class="meta-row">
                        <span class="meta-icon">📍</span>
                        <span class="meta-text">
                            <?= htmlspecialchars($tournament['venue_city']) ?>, <?= htmlspecialchars($tournament['venue_state'] ?? '') ?>
                        </span>
                    </div>
                <?php endif; ?>

                <div class="meta-row">
                    <span class="meta-icon">🎯</span>
                    <span class="meta-text"><?= $eventCount ?> Event<?= $eventCount !== 1 ? 's' : '' ?></span>
                </div>

                <div class="meta-row">
                    <span class="meta-icon">👥</span>
                    <span class="meta-text"><?= $tournament['num_attendees'] ?? 0 ?> Registered</span>
                </div>
            </div>

            <?php if ($eventCount > 0): ?>
                <div class="tournament-card-events">
                    <strong>Games:</strong>
                    <div class="event-tags">
                        <?php
                        $displayedGames = [];
                        $maxGames = 3;
                        $count = 0;
                        foreach ($events as $event):
                            if ($count >= $maxGames) break;
                            $game = $event['videogame_name'] ?? 'TBD';
                            if (!in_array($game, $displayedGames)):
                                $displayedGames[] = $game;
                                $count++;
                        ?>
                                <span class="event-tag"><?= htmlspecialchars($game) ?></span>
                        <?php
                            endif;
                        endforeach;
                        if ($eventCount > $maxGames):
                        ?>
                            <span class="event-tag more">+<?= $eventCount - $maxGames ?> more</span>
                        <?php endif; ?>
                    </div>
                </div>
            <?php endif; ?>

            <div class="tournament-card-footer">
                <a href="/tournament.php?slug=<?= urlencode($tournament['slug']) ?>"
                   class="btn btn-primary tournament-btn">
                    View Details →
                </a>

                <?php if ($isRegistrationOpen && $tournament['url']): ?>
                    <a href="<?= htmlspecialchars($tournament['url']) ?>"
                       class="btn btn-secondary tournament-btn"
                       target="_blank"
                       rel="noopener noreferrer">
                        Register Now
                    </a>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <?php
}

function displayNoTournaments($filter, $search) {
    ?>
    <div class="no-tournaments">
        <div class="no-tournaments-icon">🎮</div>
        <h3>No Tournaments Found</h3>

        <?php if (!empty($search)): ?>
            <p>No tournaments match your search for "<?= htmlspecialchars($search) ?>"</p>
            <a href="?" class="btn btn-primary">Clear Search</a>
        <?php elseif ($filter === 'open'): ?>
            <p>There are currently no tournaments with open registration.</p>
            <a href="?filter=upcoming" class="btn btn-primary">View All Upcoming</a>
        <?php else: ?>
            <p>There are no upcoming tournaments scheduled at this time.</p>
            <p><small>Check back soon or contact us to schedule a tournament!</small></p>
        <?php endif; ?>
    </div>
    <?php
}
?>

<style>
/* Start.gg Tournaments Display Styles */

.startgg-tournaments-section {
    margin: 3em 0;
}

.tournaments-header {
    margin-bottom: 2em;
}

.tournaments-title {
    font-size: 2.5em;
    color: var(--primary-color);
    margin-bottom: 1em;
}

.tournaments-filters {
    display: flex;
    gap: 1em;
    flex-wrap: wrap;
    align-items: center;
}

.filter-form {
    display: flex;
    gap: 1em;
    flex-wrap: wrap;
    width: 100%;
}

.filter-group {
    display: flex;
    gap: 0.5em;
}

.filter-select,
.search-input {
    padding: 0.75em 1em;
    border: 2px solid var(--primary-color);
    border-radius: 5px;
    background: var(--darker-bg);
    color: var(--light-color);
    font-size: 1em;
}

.filter-select {
    cursor: pointer;
}

.search-input {
    min-width: 250px;
}

.search-btn {
    padding: 0.75em 1.5em;
    background: var(--primary-color);
    color: white;
    border: none;
    border-radius: 5px;
    cursor: pointer;
    font-weight: bold;
    transition: all 0.3s ease;
}

.search-btn:hover {
    background: var(--hover-color);
}

.tournaments-grid {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(350px, 1fr));
    gap: 2em;
    margin-bottom: 2em;
}

.tournament-card {
    background: var(--darker-bg);
    border-radius: 15px;
    overflow: hidden;
    transition: all 0.3s ease;
    border: 2px solid transparent;
}

.tournament-card:hover {
    transform: translateY(-5px);
    border-color: var(--primary-color);
    box-shadow: 0 15px 40px rgba(233, 69, 96, 0.3);
}

.tournament-card.registration-open {
    border-color: rgba(0, 200, 81, 0.3);
}

.tournament-card-image {
    height: 200px;
    background-size: cover;
    background-position: center;
    position: relative;
}

.tournament-card-image.placeholder {
    background: linear-gradient(135deg, var(--dark-bg) 0%, var(--darker-bg) 100%);
}

.placeholder-content {
    display: flex;
    align-items: center;
    justify-content: center;
    height: 100%;
}

.placeholder-icon {
    font-size: 4em;
    opacity: 0.3;
}

.registration-badge {
    position: absolute;
    top: 1em;
    right: 1em;
    background: #00C851;
    color: white;
    padding: 0.5em 1em;
    border-radius: 20px;
    font-weight: bold;
    font-size: 0.9em;
    animation: pulse 2s ease-in-out infinite;
}

.tournament-card-content {
    padding: 1.5em;
}

.tournament-card-title {
    margin: 0 0 1em 0;
    font-size: 1.5em;
}

.tournament-card-title a {
    color: var(--light-color);
    text-decoration: none;
    transition: color 0.3s ease;
}

.tournament-card-title a:hover {
    color: var(--primary-color);
}

.tournament-card-meta {
    display: flex;
    flex-direction: column;
    gap: 0.75em;
    margin-bottom: 1.5em;
}

.meta-row {
    display: flex;
    align-items: center;
    gap: 0.5em;
    color: var(--light-color);
}

.meta-icon {
    font-size: 1.1em;
}

.meta-text {
    font-size: 0.95em;
}

.days-until {
    color: var(--primary-color);
    font-weight: bold;
}

.tournament-card-events {
    margin-bottom: 1.5em;
    padding-top: 1em;
    border-top: 1px solid rgba(255, 255, 255, 0.1);
}

.tournament-card-events strong {
    color: var(--light-color);
    font-size: 0.9em;
    display: block;
    margin-bottom: 0.5em;
}

.event-tags {
    display: flex;
    flex-wrap: wrap;
    gap: 0.5em;
}

.event-tag {
    background: var(--dark-bg);
    color: var(--primary-color);
    padding: 0.4em 0.8em;
    border-radius: 15px;
    font-size: 0.85em;
    font-weight: bold;
    border: 1px solid var(--primary-color);
}

.event-tag.more {
    background: var(--primary-color);
    color: white;
}

.tournament-card-footer {
    display: flex;
    gap: 0.75em;
    flex-wrap: wrap;
}

.tournament-btn {
    flex: 1;
    text-align: center;
    padding: 0.75em 1em;
    border-radius: 5px;
    text-decoration: none;
    font-weight: bold;
    transition: all 0.3s ease;
    min-width: 120px;
}

.btn-primary {
    background: var(--primary-color);
    color: white;
    border: 2px solid var(--primary-color);
}

.btn-primary:hover {
    background: var(--hover-color);
    border-color: var(--hover-color);
}

.btn-secondary {
    background: transparent;
    color: var(--primary-color);
    border: 2px solid var(--primary-color);
}

.btn-secondary:hover {
    background: var(--primary-color);
    color: white;
}

.tournaments-footer {
    text-align: center;
    padding: 2em 0;
    color: var(--light-color);
    opacity: 0.7;
}

.powered-by a {
    color: var(--primary-color);
    text-decoration: none;
    font-weight: bold;
}

.powered-by a:hover {
    text-decoration: underline;
}

/* No Tournaments Message */
.no-tournaments {
    text-align: center;
    padding: 4em 2em;
    background: var(--darker-bg);
    border-radius: 15px;
    margin: 2em 0;
}

.no-tournaments-icon {
    font-size: 5em;
    margin-bottom: 0.5em;
    opacity: 0.5;
}

.no-tournaments h3 {
    color: var(--light-color);
    font-size: 2em;
    margin-bottom: 0.5em;
}

.no-tournaments p {
    color: var(--light-color);
    opacity: 0.8;
    margin-bottom: 1em;
}

/* Responsive */
@media (max-width: 768px) {
    .tournaments-grid {
        grid-template-columns: 1fr;
    }

    .filter-form {
        flex-direction: column;
    }

    .search-input {
        min-width: 100%;
    }

    .tournament-card-footer {
        flex-direction: column;
    }

    .tournament-btn {
        width: 100%;
    }
}
</style>
