/**
 * start.gg API Integration Module
 * Fetches tournament data from start.gg GraphQL API
 *
 * Documentation: https://developer.start.gg/docs/intro
 */

import { fetchWithErrorHandling } from './utils.js';

// API Configuration
const STARTGG_API_URL = 'https://api.start.gg/gql/alpha';
const STARTGG_API_KEY = 'YOUR_API_KEY_HERE'; // TODO: Move to environment variable

/**
 * Make GraphQL request to start.gg API
 * @param {string} query - GraphQL query
 * @param {Object} variables - Query variables
 * @returns {Promise<Object>} API response data
 */
async function startGGQuery(query, variables = {}) {
    const response = await fetch(STARTGG_API_URL, {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
            'Authorization': `Bearer ${STARTGG_API_KEY}`
        },
        body: JSON.stringify({
            query,
            variables
        })
    });

    if (!response.ok) {
        throw new Error(`start.gg API error: ${response.statusText}`);
    }

    const result = await response.json();

    if (result.errors) {
        console.error('GraphQL errors:', result.errors);
        throw new Error(result.errors[0].message);
    }

    return result.data;
}

/**
 * Fetch tournament details by slug
 * @param {string} slug - Tournament slug (e.g., "tournament/champions-gauntlet")
 * @returns {Promise<Object>} Tournament data
 */
export async function getTournament(slug) {
    const query = `
        query TournamentQuery($slug: String!) {
            tournament(slug: $slug) {
                id
                name
                slug
                startAt
                endAt
                timezone
                venueAddress
                venueName
                description
                images {
                    url
                    type
                }
                events {
                    id
                    name
                    slug
                    startAt
                    numEntrants
                    entrantSizeMax
                    state
                    videogame {
                        id
                        name
                        images {
                            url
                            type
                        }
                    }
                }
                links {
                    facebook
                    discord
                }
                rules
            }
        }
    `;

    const data = await startGGQuery(query, { slug });
    return data.tournament;
}

/**
 * Fetch event details (specific game within a tournament)
 * @param {string} slug - Event slug
 * @returns {Promise<Object>} Event data with phases and standings
 */
export async function getEvent(slug) {
    const query = `
        query EventQuery($slug: String!) {
            event(slug: $slug) {
                id
                name
                slug
                startAt
                numEntrants
                entrantSizeMax
                checkInBuffer
                checkInDuration
                checkInEnabled
                isOnline
                state
                videogame {
                    name
                    images {
                        url
                    }
                }
                phases {
                    id
                    name
                    bracketType
                    numSeeds
                }
                standings(query: {
                    perPage: 8
                    page: 1
                }) {
                    nodes {
                        placement
                        entrant {
                            id
                            name
                        }
                    }
                }
            }
        }
    `;

    const data = await startGGQuery(query, { slug });
    return data.event;
}

/**
 * Get user's tournaments
 * @param {number} userId - start.gg user ID
 * @returns {Promise<Array>} Array of tournaments
 */
export async function getUserTournaments(userId) {
    const query = `
        query UserTournamentsQuery($userId: ID!) {
            user(id: $userId) {
                tournaments(query: {
                    perPage: 10
                    page: 1
                    sortBy: "startAt desc"
                }) {
                    nodes {
                        id
                        name
                        slug
                        startAt
                        numAttendees
                    }
                }
            }
        }
    `;

    const data = await startGGQuery(query, { userId });
    return data.user.tournaments.nodes;
}

/**
 * Get tournament brackets/phases
 * @param {number} eventId - Event ID
 * @returns {Promise<Object>} Phase groups and matches
 */
export async function getTournamentBrackets(eventId) {
    const query = `
        query EventBracketsQuery($eventId: ID!) {
            event(id: $eventId) {
                phaseGroups {
                    nodes {
                        id
                        displayIdentifier
                        state
                        sets(
                            page: 1
                            perPage: 50
                        ) {
                            nodes {
                                id
                                fullRoundText
                                identifier
                                round
                                state
                                slots {
                                    entrant {
                                        name
                                    }
                                    standing {
                                        stats {
                                            score {
                                                value
                                            }
                                        }
                                    }
                                }
                            }
                        }
                    }
                }
            }
        }
    `;

    const data = await startGGQuery(query, { eventId });
    return data.event.phaseGroups.nodes;
}

/**
 * Format tournament data for display
 * @param {Object} tournament - Raw tournament data from API
 * @returns {Object} Formatted tournament object
 */
export function formatTournamentForDisplay(tournament) {
    return {
        id: tournament.id,
        title: tournament.name,
        slug: tournament.slug,
        description: tournament.description || '',
        startDate: new Date(tournament.startAt * 1000),
        endDate: tournament.endAt ? new Date(tournament.endAt * 1000) : null,
        venue: {
            name: tournament.venueName || 'Bakersfield eSports Center',
            address: tournament.venueAddress || '7104 Golden State Hwy, Bakersfield, CA 93308'
        },
        image: tournament.images?.find(img => img.type === 'banner')?.url || tournament.images?.[0]?.url,
        events: tournament.events?.map(event => ({
            id: event.id,
            name: event.name,
            slug: event.slug,
            game: event.videogame?.name,
            gameImage: event.videogame?.images?.[0]?.url,
            entrants: event.numEntrants || 0,
            maxEntrants: event.entrantSizeMax,
            status: event.state,
            startTime: new Date(event.startAt * 1000)
        })) || [],
        registrationUrl: `https://start.gg/${tournament.slug}/register`,
        detailsUrl: `https://start.gg/${tournament.slug}`,
        rules: tournament.rules,
        socialLinks: {
            facebook: tournament.links?.facebook,
            discord: tournament.links?.discord
        }
    };
}

/**
 * Create tournament registration button
 * @param {Object} tournament - Formatted tournament data
 * @returns {HTMLElement} Registration button element
 */
export function createRegistrationButton(tournament) {
    const button = document.createElement('a');
    button.href = tournament.registrationUrl;
    button.className = 'btn btn-primary';
    button.target = '_blank';
    button.rel = 'noopener noreferrer';
    button.textContent = 'Register on start.gg';

    // Add tracking
    button.addEventListener('click', () => {
        if (window.trackEvent) {
            window.trackEvent('register_tournament', {
                tournament_name: tournament.title,
                tournament_slug: tournament.slug
            });
        }
    });

    return button;
}

/**
 * Display tournament details in a modal or section
 * @param {Object} tournament - Formatted tournament data
 * @param {HTMLElement} container - Container element
 */
export function displayTournamentDetails(tournament, container) {
    const html = `
        <div class="tournament-details">
            ${tournament.image ? `<img src="${tournament.image}" alt="${tournament.title}" class="tournament-banner">` : ''}

            <h2>${tournament.title}</h2>

            <div class="tournament-info">
                <p><strong>Date:</strong> ${tournament.startDate.toLocaleDateString()}</p>
                <p><strong>Venue:</strong> ${tournament.venue.name}</p>
                ${tournament.description ? `<p class="tournament-description">${tournament.description}</p>` : ''}
            </div>

            ${tournament.events.length > 0 ? `
                <div class="tournament-events">
                    <h3>Events</h3>
                    ${tournament.events.map(event => `
                        <div class="event-card">
                            ${event.gameImage ? `<img src="${event.gameImage}" alt="${event.game}">` : ''}
                            <h4>${event.name}</h4>
                            <p>${event.game}</p>
                            <p>Entrants: ${event.entrants}${event.maxEntrants ? ` / ${event.maxEntrants}` : ''}</p>
                        </div>
                    `).join('')}
                </div>
            ` : ''}

            ${tournament.rules ? `
                <div class="tournament-rules">
                    <h3>Rules</h3>
                    <div>${tournament.rules}</div>
                </div>
            ` : ''}

            <div class="tournament-actions">
                <a href="${tournament.registrationUrl}" class="btn btn-primary" target="_blank" rel="noopener noreferrer">
                    Register Now
                </a>
                <a href="${tournament.detailsUrl}" class="btn btn-secondary" target="_blank" rel="noopener noreferrer">
                    View on start.gg
                </a>
            </div>
        </div>
    `;

    container.innerHTML = html;
}

// Export for use in other modules
export default {
    getTournament,
    getEvent,
    getUserTournaments,
    getTournamentBrackets,
    formatTournamentForDisplay,
    createRegistrationButton,
    displayTournamentDetails
};
