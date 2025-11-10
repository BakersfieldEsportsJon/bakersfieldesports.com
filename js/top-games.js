/**
 * Top Games Dynamic Loader
 * Bakersfield eSports - 2025
 *
 * Fetches and displays top games from GGLeap API
 */

(function() {
    'use strict';

    // Configuration
    const API_URL = 'api/top-games.php';
    const RETRY_DELAY = 3000; // 3 seconds
    const MAX_RETRIES = 3;

    let retryCount = 0;

    // ============================================
    // FETCH TOP GAMES DATA
    // ============================================

    function fetchTopGames() {
        fetch(API_URL)
            .then(response => {
                if (!response.ok) {
                    throw new Error('Network response was not ok');
                }
                return response.json();
            })
            .then(data => {
                displayTopGames(data);
                retryCount = 0; // Reset retry count on success
            })
            .catch(error => {
                console.error('Error fetching top games:', error);
                handleError();
            });
    }

    // ============================================
    // DISPLAY TOP GAMES
    // ============================================

    function displayTopGames(data) {
        const grid = document.getElementById('top-games-grid');

        if (!grid) {
            console.error('Top games grid element not found');
            return;
        }

        // Clear loading state
        grid.innerHTML = '';
        grid.setAttribute('aria-busy', 'false');

        // Check if we have games
        if (!data.top_games || data.top_games.length === 0) {
            grid.innerHTML = '<div class="error-message" role="alert">No games data available</div>';
            grid.removeAttribute('role');
            return;
        }

        // Create game cards
        data.top_games.forEach((game, index) => {
            const rank = index + 1;
            const card = createGameCard(game, rank);
            grid.appendChild(card);
        });

        // Announce to screen readers
        announceToScreenReader(`${data.top_games.length} top games loaded`);
    }

    // ============================================
    // CREATE GAME CARD
    // ============================================

    function createGameCard(game, rank) {
        const card = document.createElement('article');
        card.className = 'game-card';
        card.setAttribute('data-rank', rank);
        card.setAttribute('role', 'listitem');
        card.setAttribute('tabindex', '0');
        card.setAttribute('aria-label', `${game.name}, ranked number ${rank}`);

        // Rank badge
        const rankBadge = document.createElement('div');
        rankBadge.className = 'game-rank';
        rankBadge.textContent = `#${rank}`;
        rankBadge.setAttribute('aria-label', `Rank ${rank}`);

        // Game cover image
        const cover = document.createElement('img');
        cover.className = 'game-cover';
        cover.src = game.image_url || 'https://via.placeholder.com/200x300/1a1a1a/ec194d?text=' + encodeURIComponent(game.name);
        cover.alt = `${game.name} game cover`;
        cover.loading = 'lazy';
        cover.setAttribute('decoding', 'async');

        // Handle image load errors
        cover.onerror = function() {
            this.src = 'https://via.placeholder.com/200x300/1a1a1a/ec194d?text=' + encodeURIComponent(game.name);
            this.alt = `${game.name} (cover unavailable)`;
        };

        // Game info overlay
        const info = document.createElement('div');
        info.className = 'game-info';
        info.setAttribute('aria-hidden', 'true'); // Hidden by default, visible on hover

        const name = document.createElement('div');
        name.className = 'game-name';
        name.textContent = game.name;

        info.appendChild(name);

        // Keyboard interaction
        card.addEventListener('keydown', function(e) {
            if (e.key === 'Enter' || e.key === ' ') {
                e.preventDefault();
                this.click();
            }
        });

        // Focus management for accessibility
        card.addEventListener('focus', function() {
            info.setAttribute('aria-hidden', 'false');
        });

        card.addEventListener('blur', function() {
            info.setAttribute('aria-hidden', 'true');
        });

        // Assemble card
        card.appendChild(rankBadge);
        card.appendChild(cover);
        card.appendChild(info);

        return card;
    }

    // ============================================
    // ERROR HANDLING
    // ============================================

    function handleError() {
        const grid = document.getElementById('top-games-grid');
        grid.setAttribute('aria-busy', 'false');

        if (retryCount < MAX_RETRIES) {
            retryCount++;
            console.log(`Retrying... (${retryCount}/${MAX_RETRIES})`);
            setTimeout(fetchTopGames, RETRY_DELAY);
        } else {
            grid.innerHTML = `
                <div class="error-message" role="alert">
                    <p>Unable to load top games at this time.</p>
                    <p style="font-size: 0.9em; margin-top: 0.5em;">Please try refreshing the page.</p>
                </div>
            `;
            grid.removeAttribute('role');
            announceToScreenReader('Error loading top games. Please refresh the page.');
        }
    }

    // ============================================
    // ACCESSIBILITY HELPERS
    // ============================================

    function announceToScreenReader(message) {
        // Create a visually hidden live region for screen reader announcements
        let liveRegion = document.getElementById('top-games-sr-live');

        if (!liveRegion) {
            liveRegion = document.createElement('div');
            liveRegion.id = 'top-games-sr-live';
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
    // INITIALIZE
    // ============================================

    function init() {
        // Wait for DOM to be ready
        if (document.readyState === 'loading') {
            document.addEventListener('DOMContentLoaded', fetchTopGames);
        } else {
            fetchTopGames();
        }
    }

    // Start the app
    init();

})();
