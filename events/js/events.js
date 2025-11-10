// Helper functions
function formatDateTime(dateString) {
    const date = new Date(dateString);
    return date.toLocaleDateString('en-US', {
        weekday: 'long',
        year: 'numeric',
        month: 'long',
        day: 'numeric',
        hour: 'numeric',
        minute: 'numeric',
        hour12: true,
        timeZone: 'America/Los_Angeles'
    });
}

function isUpcoming(event) {
    // Ensure all date comparisons are in Pacific Time
    let now = new Date(new Date().toLocaleString("en-US", {timeZone: "America/Los_Angeles"}));
    
    // For recurring events, check next occurrence
    if (event.isRecurring) {
        const nextDate = getNextOccurrence(event);
        // Handle null return value for ended events
        if (nextDate === null) {
            console.log(`Event ${event.name} has ended`);
            return false;
        }
        const isUpcoming = nextDate >= now;
        console.log(`Event: ${event.name}`);
        console.log(`- Current time: ${now}`);
        console.log(`- Next occurrence: ${nextDate}`);
        console.log(`- Is upcoming: ${isUpcoming}`);
        console.log(`- Time until event: ${Math.round((nextDate - now) / (1000 * 60 * 60))} hours`);
        return isUpcoming;
    }
    
    // For non-recurring events, check the event date
    // Parse date in Pacific Time
    const eventDateTime = new Date(new Date(event.date).toLocaleString("en-US", {timeZone: "America/Los_Angeles"}));
    
    // If event has an endDate, check if we're within the range
    if (event.endDate) {
        const endDateTime = new Date(new Date(event.endDate).toLocaleString("en-US", {timeZone: "America/Los_Angeles"}));
        const isUpcoming = now <= endDateTime;
        console.log(`Event with endDate: ${event.name}`);
        console.log(`- Event start: ${eventDateTime}`);
        console.log(`- Event end: ${endDateTime}`);
        console.log(`- Is upcoming: ${isUpcoming}`);
        return isUpcoming;
    }
    
    // Compare local times directly
    const isUpcoming = eventDateTime >= now;
    console.log(`Standard event: ${event.name}`);
    console.log(`- Event time: ${eventDateTime}`);
    console.log(`- Is upcoming: ${isUpcoming}`);
    return isUpcoming;
}

// Helper function to parse dates
function parseDate(dateStr) {
    const date = new Date(dateStr);
    if (isNaN(date)) {
        console.error('Invalid date format:', dateStr);
        return new Date(0); // Return epoch for invalid dates
    }
    return date;
}

// Calculate next occurrence for recurring events
function getNextOccurrence(event) {
    // Ensure all date comparisons are in Pacific Time
    let now = new Date(new Date().toLocaleString("en-US", {timeZone: "America/Los_Angeles"}));
    
    // Parse date in Pacific Time
    const eventDate = typeof event.date === 'string' ? 
        new Date(new Date(event.date).toLocaleString("en-US", {timeZone: "America/Los_Angeles"})) : 
        new Date(event.toLocaleString("en-US", {timeZone: "America/Los_Angeles"}));
    
    if (!event.isRecurring) return eventDate;
    
    // Check if event has ended
    if (event.endDate) {
        const endDate = new Date(event.endDate);
        if (now > endDate) {
            return null;
        }
    }
    
    // Enhanced logging for debugging
    console.log(`[DEBUG] Calculating next occurrence for ${event.name}:`);
    console.log(`[DEBUG] - Original date: ${eventDate}`);
    console.log(`[DEBUG] - Current time: ${now}`);
    console.log(`[DEBUG] - Category: ${event.category}`);
    
    // For league events, show start date until it passes
    if (event.category === 'nor-leagues' || event.category === 'league-of-dreams-leagues') {
        console.log(`[DEBUG] League event processing for ${event.name}:`);
        console.log(`[DEBUG] - Start date: ${eventDate}`);
        console.log(`[DEBUG] - End date: ${event.endDate}`);
        console.log(`[DEBUG] - Current time: ${now}`);
        
        if (now < eventDate) {
            console.log(`[DEBUG] - Before start date, returning start date`);
            return eventDate;
        }
        
        console.log(`[DEBUG] - After start date, calculating next occurrence`);
    }
    
    // Handle weekly recurrence
    const frequency = event.recurrenceFrequency || 'weekly';
    if (frequency === 'weekly') {
        // Get the target day of week and time from the original event
        const targetDay = eventDate.getDay(); // 0-6 (Sunday-Saturday)
        const targetHours = eventDate.getHours();
        const targetMinutes = eventDate.getMinutes();
        
        // Create a new date starting from today
        let nextDate = new Date(now);
        // Reset time to midnight to ensure clean day calculations
        nextDate.setHours(0, 0, 0, 0);
        
        // Calculate days until next occurrence
        let daysUntil = targetDay - nextDate.getDay();
        if (daysUntil < 0) {
            daysUntil += 7; // Move to next week if target day already passed
        }
        
        // Add the calculated days
        nextDate.setDate(nextDate.getDate() + daysUntil);
        
        // Set the target time
        nextDate.setHours(targetHours, targetMinutes, 0, 0);
        
        // If the calculated time has already passed today, move to next week
        if (nextDate <= now) {
            nextDate.setDate(nextDate.getDate() + 7);
        }
        
        // For league events, ensure we don't show dates before start date
        if ((event.category === 'nor-leagues' || event.category === 'league-of-dreams-leagues') && nextDate < eventDate) {
            console.log(`[DEBUG] - Next occurrence ${nextDate} is before start date ${eventDate}, returning start date`);
            return eventDate;
        }
        
        // Check if next occurrence is after end date
        if (event.endDate && nextDate > new Date(event.endDate)) {
            console.log(`[DEBUG] - Next occurrence ${nextDate} is after end date ${event.endDate}, returning null`);
            return null;
        }
        
        console.log(`[DEBUG] Weekly event details for ${event.name}:`);
        console.log(`[DEBUG] - Target day: ${['Sunday', 'Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday'][targetDay]}`);
        console.log(`[DEBUG] - Target time: ${targetHours}:${targetMinutes}`);
        console.log(`[DEBUG] - Next occurrence: ${nextDate}`);
        console.log(`[DEBUG] - Days until next: ${daysUntil}`);
        console.log(`[DEBUG] - Is in future: ${nextDate > now}`);
        
        return nextDate;
    }
    
    return eventDate;
}

function sortEventsByDate(events) {
    return events.sort((a, b) => {
        // Get next occurrences
        const dateA = getNextOccurrence(a);
        const dateB = getNextOccurrence(b);

        // Handle null values (ended events)
        if (dateA === null) return 1;
        if (dateB === null) return -1;

        // Ensure dates are valid Date objects
        if (isNaN(dateA.getTime()) || isNaN(dateB.getTime())) {
            console.error('Invalid date found in events:', a, b);
            return 0;
        }

        // Compare timestamps for sorting
        return dateA.getTime() - dateB.getTime();
    });
}

function createEventHTML(event) {
    const nextDate = getNextOccurrence(event);
    if (nextDate === null) return ''; // Skip ended events
    
    const displayDate = event.isRecurring ? nextDate : 
        new Date(new Date(event.date).toLocaleString("en-US", {timeZone: "America/Los_Angeles"}));
    const isWeeklyEvent = event.category === 'weekly-events';
    
    return `
        <div class="event-item">
            <div class="event-image">
                <img src="${event.image}" alt="${event.name}" />
            </div>
            <div class="event-details">
                <h3>${event.name}</h3>
                <p><strong>Location:</strong> ${event.location}<br/>${event.address || ''}</p>
                <p><strong>${event.isRecurring ? 'Next Occurrence' : 'Date & Time'}:</strong></p>
                <p class="event-datetime">${formatDateTime(displayDate)}</p>
                ${event.description ? `<p class="event-description">${event.description.replace(/\n/g, '<br>')}</p>` : ''}
                ${!isWeeklyEvent && event.entryCost !== undefined && event.entryCost !== null && event.entryCost > 0 ? 
                    `<p><strong>Entry Cost:</strong> $${event.entryCost} per player</p>` : 
                    !isWeeklyEvent && event.entryCost === 0 ? `<p><strong>Entry Cost:</strong> Free</p>` : ''}
                ${!isWeeklyEvent && event.notes ? `<p><em>*${event.notes}</em></p>` : ''}
                ${!isWeeklyEvent && event.registrationLink ? `<a class="btn btn-primary" href="${event.registrationLink}" target="_blank">Register</a>` : ''}
            </div>
        </div>
    `;
}

function renderEvents(events, containerSelector) {
    const container = document.querySelector(containerSelector);
    if (!container) return;

    container.innerHTML = '';

    // Filter out ended events
    const activeEvents = events.filter(event => {
        const nextDate = getNextOccurrence(event);
        return nextDate !== null;
    });

    if (activeEvents.length === 0) {
        container.innerHTML = `
            <div class="no-events-message">
                <h3>Stay tuned for more events!</h3>
                <p>Check back soon for upcoming events.</p>
            </div>
        `;
        return;
    }

    // Show all events for weekly events section
    if (containerSelector === '.weekly-events .events-grid') {
        activeEvents.forEach(event => {
            container.innerHTML += createEventHTML(event);
        });
    } else {
        // Show all events for tournament sections
        activeEvents.forEach(event => {
            container.innerHTML += createEventHTML(event);
        });
    }
}

async function loadAllEvents() {
    try {
        const response = await fetch('data/get_events.php');
        const text = await response.text();
        
        // Parse the JSON directly - removed cleaning logic that was adding extra commas
        const data = JSON.parse(text);
        
        console.log('All events loaded:', data.events);
        
        // Filter upcoming events
        let allUpcoming = data.events.filter(event => isUpcoming(event));

        console.log('Upcoming events:', allUpcoming);
        
        // Split events by category and sort each category individually
        const weeklyEvents = sortEventsByDate(allUpcoming.filter(e => {
            // Normalize category by converting to lowercase and removing special chars
            const normalizedCategory = e.category?.toLowerCase();
            return normalizedCategory === 'weekly-events';
        }));

        console.log('Weekly events to render:', weeklyEvents);
        const tournamentEvents = sortEventsByDate(allUpcoming.filter(e => e.category === 'tournaments'));
        const norLeagues = sortEventsByDate(allUpcoming.filter(e => e.category === 'nor-leagues'));
        const lodLeagues = sortEventsByDate(allUpcoming.filter(e => e.category === 'league-of-dreams-leagues'));

        // Render each sorted category to its respective section
        renderEvents(weeklyEvents, '.weekly-events .events-grid');
        renderEvents(tournamentEvents, '.events .events-grid');
        renderEvents(norLeagues, '.nor-leagues .events-grid');
        renderEvents(lodLeagues, '.lod-leagues .events-grid');

    } catch (error) {
        console.error('Error loading events:', error);
        
        // Show error message in all sections
        const sections = [
            '.weekly-events .events-grid',
            '.events .events-grid', 
            '.nor-leagues .events-grid',
            '.lod-leagues .events-grid'
        ];
        
        sections.forEach(selector => {
            const container = document.querySelector(selector);
            if (container) {
                container.innerHTML = `
                    <div class="error-message">
                        <h3>Unable to load events</h3>
                        <p>Please try again later.</p>
                    </div>
                `;
            }
        });
    }
}

// Initialize when DOM is ready
document.addEventListener('DOMContentLoaded', loadAllEvents);

// Schedule hourly refresh
function getMillisecondsUntilNextHour() {
    const now = new Date();
    const nextHour = new Date(now);
    nextHour.setHours(nextHour.getHours() + 1, 0, 0, 0);
    return nextHour - now;
}

// Adjust font size to prevent wrapping
function adjustDateTimeFontSizes() {
    const dateElements = document.querySelectorAll('.event-datetime');
    
    dateElements.forEach(el => {
        // Reset to default size first
        el.style.fontSize = '';
        
        // Get computed style
        const style = window.getComputedStyle(el);
        const lineHeight = parseFloat(style.lineHeight);
        const padding = parseFloat(style.paddingTop) + parseFloat(style.paddingBottom);
        
        // Check if text is wrapping
        while (el.scrollHeight - padding > lineHeight) {
            const currentSize = parseFloat(style.fontSize);
            if (currentSize <= 12) break; // Minimum font size
            
            // Reduce font size by 1px
            el.style.fontSize = `${currentSize - 1}px`;
        }
    });
}

// Run after events load and on window resize
function setupDateTimeResizing() {
    // Initial adjustment
    adjustDateTimeFontSizes();
    
    // Adjust on window resize with debounce
    let resizeTimeout;
    window.addEventListener('resize', () => {
        clearTimeout(resizeTimeout);
        resizeTimeout = setTimeout(adjustDateTimeFontSizes, 100);
    });
}

setTimeout(() => {
    loadAllEvents();
    setInterval(loadAllEvents, 3600000);
    
    // Setup date/time font adjustment
    setupDateTimeResizing();
}, getMillisecondsUntilNextHour());

// Also adjust after each events load
const originalLoadAllEvents = loadAllEvents;
loadAllEvents = async function() {
    await originalLoadAllEvents.apply(this, arguments);
    adjustDateTimeFontSizes();
};
