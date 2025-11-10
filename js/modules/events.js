/**
 * Events Module
 * Handles fetching and displaying events from JSON and start.gg API
 */

import { fetchWithErrorHandling, showLoading, showError, formatDate } from './utils.js';

/**
 * Fetch events from local JSON file
 * @param {string} category - Event category (weekly, tournaments, nor, lod)
 * @returns {Promise<Array>} Array of events
 */
export async function fetchLocalEvents(category = 'all') {
    try {
        const data = await fetchWithErrorHandling('/events/data/events.json');

        if (category === 'all') {
            return data.events || [];
        }

        return data.events?.filter(event => event.category === category) || [];
    } catch (error) {
        console.error('Error fetching local events:', error);
        return [];
    }
}

/**
 * Fetch events from start.gg API
 * @param {string} tournamentSlug - Tournament slug from start.gg
 * @returns {Promise<Object>} Tournament data
 */
export async function fetchStartGGTournament(tournamentSlug) {
    // This will be implemented with start.gg API integration
    // For now, return empty data
    console.log('start.gg integration coming soon for:', tournamentSlug);
    return null;
}

/**
 * Create event card HTML
 * @param {Object} event - Event data
 * @returns {string} HTML string
 */
export function createEventCard(event) {
    const {
        title,
        description,
        date,
        time,
        price,
        image,
        registrationUrl,
        category
    } = event;

    const formattedDate = date ? formatDate(new Date(date + 'T' + (time || '00:00'))) : 'TBA';
    const priceText = price === '0' || price === 0 ? 'Free' : `$${price}`;

    return `
        <div class="event-item" data-category="${category}">
            ${image ? `<img src="${image}" alt="${title}" loading="lazy">` : ''}
            <h3>${title}</h3>
            ${description ? `<p>${description}</p>` : ''}
            <p class="event-datetime">${formattedDate}</p>
            <p class="event-price"><strong>${priceText}</strong></p>
            ${registrationUrl ?
                `<a href="${registrationUrl}" class="btn btn-primary" target="_blank" rel="noopener noreferrer">
                    Register Now
                </a>` :
                `<button class="btn btn-primary" onclick="alert('Registration coming soon!')">
                    Learn More
                </button>`
            }
        </div>
    `;
}

/**
 * Display events in a container
 * @param {HTMLElement} container - Container element
 * @param {Array} events - Array of events
 */
export function displayEvents(container, events) {
    if (!container) {
        console.error('Container element not found');
        return;
    }

    if (!events || events.length === 0) {
        container.innerHTML = '<div class="no-events-message"><p>No events scheduled at this time. Check back soon!</p></div>';
        return;
    }

    container.innerHTML = events.map(event => createEventCard(event)).join('');
}

/**
 * Load and display events by category
 * @param {string} containerSelector - CSS selector for container
 * @param {string} category - Event category
 */
export async function loadEvents(containerSelector, category = 'all') {
    const container = document.querySelector(containerSelector);

    if (!container) {
        console.error(`Container not found: ${containerSelector}`);
        return;
    }

    showLoading(container);

    try {
        const events = await fetchLocalEvents(category);
        displayEvents(container, events);
    } catch (error) {
        showError(container, 'Unable to load events. Please try again later.');
        console.error('Error loading events:', error);
    }
}

/**
 * Initialize events page
 */
export function initEventsPage() {
    // Load different event categories
    loadEvents('.weekly-events .events-grid', 'weekly');
    loadEvents('.events .events-grid', 'tournament');
    loadEvents('.nor-leagues .events-grid', 'nor');
    loadEvents('.lod-leagues .events-grid', 'lod');
}
