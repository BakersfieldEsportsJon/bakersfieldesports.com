/**
 * Start.gg Tournament Events Loader
 * Bakersfield eSports - 2025
 *
 * Fetches and displays upcoming tournament events from Start.gg
 */

(function() {
    'use strict';

    // Configuration
    const API_URL = '/bakersfield/api/startgg-events.php';
    const RETRY_DELAY = 3000; // 3 seconds
    const MAX_RETRIES = 3;

    let retryCount = 0;

    // ============================================
    // FETCH TOURNAMENT EVENTS
    // ============================================

    function fetchTournamentEvents() {
        fetch(API_URL)
            .then(response => {
                if (!response.ok) {
                    throw new Error('Network response was not ok');
                }
                return response.json();
            })
            .then(data => {
                displayTournamentEvents(data);
                retryCount = 0; // Reset retry count on success
            })
            .catch(error => {
                console.error('Error fetching tournament events:', error);
                handleError();
            });
    }

    // ============================================
    // DISPLAY TOURNAMENT EVENTS
    // ============================================

    function displayTournamentEvents(data) {
        const container = document.getElementById('startgg-events-container');

        if (!container) {
            console.error('Start.gg events container not found');
            return;
        }

        // Clear loading state
        container.innerHTML = '';
        container.setAttribute('aria-busy', 'false');

        // Check if we have events
        if (!data.events || data.events.length === 0) {
            container.innerHTML = `
                <div class="no-events-message">
                    <p>No upcoming tournaments scheduled at this time.</p>
                    <p>Check back soon or visit our <a href="${data.tournament?.url || 'https://start.gg'}" target="_blank" rel="noopener">Start.gg page</a> for updates!</p>
                </div>
            `;
            return;
        }

        // Create event cards
        data.events.forEach((event) => {
            const card = createEventCard(event, data.tournament);
            container.appendChild(card);
        });

        // Announce to screen readers
        announceToScreenReader(`${data.events.length} tournament events loaded`);
    }

    // ============================================
    // CREATE EVENT CARD
    // ============================================

    function createEventCard(event, tournament) {
        const card = document.createElement('article');
        card.className = 'event-item startgg-event';
        card.setAttribute('role', 'article');
        card.setAttribute('tabindex', '0');

        // Event image (game image or placeholder)
        const imageUrl = event.game_image || '../images/default-tournament.jpg';

        const image = document.createElement('img');
        image.src = imageUrl;
        image.alt = `${event.game} tournament`;
        image.loading = 'lazy';
        image.crossOrigin = 'anonymous';
        image.onerror = function() {
            // Fallback to a gradient background if image fails
            this.style.display = 'none';
            this.parentElement.style.background = 'linear-gradient(135deg, #ec194d, #1a1a1a)';
            this.parentElement.style.display = 'flex';
            this.parentElement.style.alignItems = 'center';
            this.parentElement.style.justifyContent = 'center';

            const fallbackText = document.createElement('div');
            fallbackText.textContent = event.game;
            fallbackText.style.color = 'white';
            fallbackText.style.fontSize = '1.5rem';
            fallbackText.style.fontWeight = 'bold';
            fallbackText.style.textAlign = 'center';
            fallbackText.style.padding = '20px';
            this.parentElement.appendChild(fallbackText);
        };

        // Event info
        const info = document.createElement('div');
        info.className = 'event-info';

        // Event name
        const name = document.createElement('h3');
        name.textContent = event.name;

        // Game name
        const game = document.createElement('p');
        game.className = 'event-game';
        game.innerHTML = `<strong>Game:</strong> ${event.game}`;

        // Date and time
        const datetime = document.createElement('p');
        datetime.className = 'event-datetime';
        datetime.innerHTML = `<strong>When:</strong> ${event.start_datetime}`;

        // Online/Offline indicator
        if (event.is_online) {
            const onlineBadge = document.createElement('span');
            onlineBadge.className = 'event-badge online';
            onlineBadge.textContent = 'Online';
            info.appendChild(onlineBadge);
        }

        // Registration status and button
        const regSection = document.createElement('div');
        regSection.className = 'event-registration';

        if (event.registration_open) {
            const regStatus = document.createElement('p');
            regStatus.className = 'registration-status open';
            regStatus.innerHTML = '✅ <strong>Registration Open</strong>';
            regSection.appendChild(regStatus);

            const regButton = document.createElement('button');
            regButton.className = 'btn btn-primary';
            regButton.textContent = 'Register Now';
            regButton.onclick = () => openTournamentModal(event);
            regSection.appendChild(regButton);
        } else {
            const regStatus = document.createElement('p');
            regStatus.className = 'registration-status closed';
            regStatus.innerHTML = '🔒 Registration Closed';
            regSection.appendChild(regStatus);
        }

        // View event details link
        const detailsLink = document.createElement('button');
        detailsLink.className = 'event-details-link';
        detailsLink.textContent = 'View Details & Register →';
        detailsLink.style.background = 'none';
        detailsLink.style.border = 'none';
        detailsLink.style.cursor = 'pointer';
        detailsLink.onclick = () => openTournamentModal(event);

        // Assemble card
        info.appendChild(name);
        info.appendChild(game);
        info.appendChild(datetime);
        info.appendChild(regSection);
        info.appendChild(detailsLink);

        card.appendChild(image);
        card.appendChild(info);

        return card;
    }

    // ============================================
    // ERROR HANDLING
    // ============================================

    function handleError() {
        const container = document.getElementById('startgg-events-container');

        if (!container) return;

        container.setAttribute('aria-busy', 'false');

        if (retryCount < MAX_RETRIES) {
            retryCount++;
            console.log(`Retrying... (${retryCount}/${MAX_RETRIES})`);
            setTimeout(fetchTournamentEvents, RETRY_DELAY);
        } else {
            container.innerHTML = `
                <div class="error-message" role="alert">
                    <p>Unable to load tournament events at this time.</p>
                    <p style="font-size: 0.9em; margin-top: 0.5em;">Please visit our <a href="https://start.gg/tournament/bakersfield-esports-center-tournaments" target="_blank" rel="noopener">Start.gg page</a> for tournament information.</p>
                </div>
            `;
            announceToScreenReader('Error loading tournament events.');
        }
    }

    // ============================================
    // ACCESSIBILITY HELPERS
    // ============================================

    function announceToScreenReader(message) {
        let liveRegion = document.getElementById('startgg-sr-live');

        if (!liveRegion) {
            liveRegion = document.createElement('div');
            liveRegion.id = 'startgg-sr-live';
            liveRegion.setAttribute('aria-live', 'polite');
            liveRegion.setAttribute('aria-atomic', 'true');
            liveRegion.className = 'sr-only';
            document.body.appendChild(liveRegion);
        }

        liveRegion.textContent = message;

        // Clear after announcement
        setTimeout(() => {
            liveRegion.textContent = '';
        }, 1000);
    }

    // ============================================
    // MODAL FUNCTIONALITY
    // ============================================

    function openTournamentModal(event) {
        // Use the new tournament modal system if available
        if (typeof window.tournamentModal !== 'undefined') {
            // Extract tournament slug from event slug
            // Format: "tournament/TOURNAMENT-SLUG/event/EVENT-SLUG"
            let tournamentSlug = event.tournament_slug;

            if (!tournamentSlug && event.slug) {
                const matches = event.slug.match(/tournament\/([^\/]+)/);
                if (matches && matches[1]) {
                    tournamentSlug = matches[1];
                    console.log('Extracted tournament slug:', tournamentSlug);
                }
            }

            if (tournamentSlug) {
                console.log('Opening modal for tournament:', tournamentSlug);
                window.tournamentModal.open(tournamentSlug);
                return;
            } else {
                console.error('Could not extract tournament slug from:', event);
            }
        }

        // Fallback: open registration URL in new tab if modal not available
        console.log('Modal not available, opening registration URL');
        if (event.registration_url) {
            window.open(event.registration_url, '_blank');
        } else {
            alert('Tournament details not available at this time.');
        }
    }

    // Make it globally accessible
    window.openTournamentModal = openTournamentModal;

    // ============================================
    // INITIALIZE
    // ============================================

    function init() {
        // Wait for DOM to be ready
        if (document.readyState === 'loading') {
            document.addEventListener('DOMContentLoaded', fetchTournamentEvents);
        } else {
            fetchTournamentEvents();
        }
    }

    // Start the app
    init();

})();
