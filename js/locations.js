/**
 * Locations Page JavaScript
 * Interactive Features for Location Page
 * Bakersfield eSports Center
 */

(function() {
    'use strict';

    let map;
    let marker;

    // ============================================
    // GOOGLE MAPS INITIALIZATION
    // ============================================

    window.initMap = function() {
        if (typeof locationData === 'undefined') {
            console.error('Location data not found');
            return;
        }

        const location = { lat: locationData.lat, lng: locationData.lng };

        // Create map
        map = new google.maps.Map(document.getElementById('google-map'), {
            zoom: 15,
            center: location,
            styles: [
                {
                    "featureType": "all",
                    "elementType": "geometry",
                    "stylers": [{"color": "#1a1a1a"}]
                },
                {
                    "featureType": "all",
                    "elementType": "labels.text.fill",
                    "stylers": [{"color": "#888888"}]
                },
                {
                    "featureType": "all",
                    "elementType": "labels.text.stroke",
                    "stylers": [{"color": "#000000"}]
                },
                {
                    "featureType": "water",
                    "elementType": "geometry",
                    "stylers": [{"color": "#2c3e50"}]
                },
                {
                    "featureType": "road",
                    "elementType": "geometry",
                    "stylers": [{"color": "#2c2c2c"}]
                },
                {
                    "featureType": "poi",
                    "elementType": "labels.icon",
                    "stylers": [{"visibility": "off"}]
                },
                {
                    "featureType": "poi",
                    "elementType": "geometry",
                    "stylers": [{"color": "#222222"}]
                }
            ],
            mapTypeControl: false,
            streetViewControl: false,
            fullscreenControl: false,
            zoomControl: true,
            disableDefaultUI: false
        });

        // Custom marker icon
        const icon = {
            url: '../images/map-marker.png', // Create a custom marker icon
            scaledSize: new google.maps.Size(50, 50),
            origin: new google.maps.Point(0, 0),
            anchor: new google.maps.Point(25, 50)
        };

        // Add marker
        marker = new google.maps.Marker({
            position: location,
            map: map,
            title: locationData.name,
            animation: google.maps.Animation.DROP,
            // icon: icon // Uncomment if you have a custom icon
        });

        // Info window
        const infoWindow = new google.maps.InfoWindow({
            content: `
                <div style="padding: 10px;">
                    <h3 style="margin: 0 0 10px 0; color: #2d3748;">${locationData.name}</h3>
                    <p style="margin: 0 0 8px 0; color: #4a5568;">${locationData.address}</p>
                    <p style="margin: 0; color: #4a5568;">📱 ${locationData.phone}</p>
                    <a href="https://www.google.com/maps/dir/?api=1&destination=${encodeURIComponent(locationData.address)}"
                       target="_blank"
                       style="display: inline-block; margin-top: 10px; color: #667eea; font-weight: 600; text-decoration: none;">
                        Get Directions →
                    </a>
                </div>
            `
        });

        // Show info window on marker click
        marker.addListener('click', function() {
            infoWindow.open(map, marker);
        });

        // Open info window by default
        infoWindow.open(map, marker);
    };

    // ============================================
    // GET DIRECTIONS
    // ============================================

    window.getDirections = function() {
        if (typeof locationData === 'undefined') return;

        const destination = encodeURIComponent(locationData.address);
        const url = `https://www.google.com/maps/dir/?api=1&destination=${destination}`;

        // Open in new tab
        window.open(url, '_blank');
    };

    // ============================================
    // SHARE LOCATION
    // ============================================

    window.shareLocation = async function() {
        const shareData = {
            title: locationData.name,
            text: `Check out ${locationData.name} - ${locationData.address}`,
            url: window.location.href
        };

        // Try Web Share API first (mobile)
        if (navigator.share) {
            try {
                await navigator.share(shareData);
                console.log('Location shared successfully');
            } catch (err) {
                if (err.name !== 'AbortError') {
                    console.log('Share failed:', err);
                    fallbackShare();
                }
            }
        } else {
            fallbackShare();
        }
    };

    function fallbackShare() {
        // Fallback: Copy to clipboard
        const url = window.location.href;

        if (navigator.clipboard && navigator.clipboard.writeText) {
            navigator.clipboard.writeText(url).then(function() {
                showNotification('✅ Link copied to clipboard!', 'success');
            }).catch(function(err) {
                console.error('Could not copy text: ', err);
                showNotification('❌ Could not copy link', 'error');
            });
        } else {
            // Old method fallback
            const textarea = document.createElement('textarea');
            textarea.value = url;
            textarea.style.position = 'fixed';
            textarea.style.opacity = '0';
            document.body.appendChild(textarea);
            textarea.select();

            try {
                document.execCommand('copy');
                showNotification('✅ Link copied to clipboard!', 'success');
            } catch (err) {
                showNotification('❌ Could not copy link', 'error');
            }

            document.body.removeChild(textarea);
        }
    }

    // ============================================
    // VIRTUAL TOUR
    // ============================================

    window.openVirtualTour = function() {
        showNotification('🎥 Virtual tour coming soon! Check back later.', 'info');
        // TODO: Integrate with 360° tour solution (e.g., Matterport, Kuula)
    };

    // ============================================
    // NOTIFY ME
    // ============================================

    window.notifyMe = function(locationName) {
        // Simple implementation - could integrate with email service
        const email = prompt(`Enter your email to be notified when ${locationName} opens:`);

        if (email && validateEmail(email)) {
            // TODO: Send to backend/email service
            console.log('Notification signup:', email, locationName);
            showNotification(`✅ Thanks! We'll notify you when ${locationName} opens.`, 'success');
        } else if (email) {
            showNotification('❌ Please enter a valid email address', 'error');
        }
    };

    function validateEmail(email) {
        return /^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(email);
    }

    // ============================================
    // LOCATION STATUS (OPEN/CLOSED)
    // ============================================

    function updateLocationStatus() {
        const now = new Date();
        const day = now.getDay();
        const hour = now.getHours();
        const minute = now.getMinutes();
        const currentTime = hour * 60 + minute;

        const statusElement = document.getElementById('location-status');
        const headerStatusElement = document.getElementById('header-status');
        const statusDetail = document.getElementById('status-detail');
        const statusBadges = document.querySelectorAll('.status-badge, .status-indicator');

        let isOpen = false;
        let closeTime = '';

        // Sunday-Thursday: 12 PM - 11 PM (720 - 1380 minutes)
        // Friday-Saturday: 12 PM - 12 AM (720 - 1440 minutes)

        if (day >= 0 && day <= 4) { // Sunday-Thursday
            isOpen = currentTime >= 720 && currentTime < 1380;
            closeTime = '11:00 PM';
        } else { // Friday-Saturday
            isOpen = currentTime >= 720 && currentTime < 1440;
            closeTime = '12:00 AM';
        }

        // Update status display
        if (isOpen) {
            if (statusElement) statusElement.textContent = 'Open Now';
            if (headerStatusElement) headerStatusElement.textContent = 'Open Now';
            if (statusDetail) statusDetail.textContent = `Closes at ${closeTime}`;

            statusBadges.forEach(badge => {
                badge.classList.remove('status-closed');
                badge.classList.add('status-open');
            });
        } else {
            if (statusElement) statusElement.textContent = 'Closed';
            if (headerStatusElement) headerStatusElement.textContent = 'Closed';

            // Calculate next opening time
            const openTime = new Date(now);
            openTime.setHours(12, 0, 0, 0);

            if (currentTime >= 1380 || currentTime < 720) {
                // If after closing or before opening, next opening is tomorrow or today at 12 PM
                if (currentTime >= 1380) {
                    openTime.setDate(openTime.getDate() + 1);
                }
            }

            const tomorrow = now.getDate() !== openTime.getDate();
            if (statusDetail) statusDetail.textContent = tomorrow ? 'Opens tomorrow at 12:00 PM' : 'Opens today at 12:00 PM';

            statusBadges.forEach(badge => {
                badge.classList.remove('status-open');
                badge.classList.add('status-closed');
            });
        }
    }

    // ============================================
    // TRAFFIC/WAIT TIME ESTIMATE
    // ============================================

    function updateWaitTime() {
        const now = new Date();
        const day = now.getDay();
        const hour = now.getHours();

        const waitTimeElement = document.getElementById('wait-time');
        const trafficLevels = document.querySelectorAll('.traffic-level');

        let waitTime = '5-10 min';
        let trafficClass = 'traffic-low';

        // Estimate based on day and time
        // Weekend evenings are busiest
        if ((day === 5 || day === 6) && hour >= 18 && hour <= 22) {
            waitTime = '20-30 min';
            trafficClass = 'traffic-high';
        } else if ((day === 5 || day === 6) && hour >= 14 && hour <= 17) {
            waitTime = '10-15 min';
            trafficClass = 'traffic-medium';
        } else if (hour >= 18 && hour <= 21) {
            waitTime = '10-15 min';
            trafficClass = 'traffic-medium';
        }

        if (waitTimeElement) {
            waitTimeElement.textContent = waitTime;
        }

        trafficLevels.forEach(level => {
            level.className = 'traffic-level ' + trafficClass;
            level.textContent = trafficClass.replace('traffic-', '').replace(/^\w/, c => c.toUpperCase()) + ' Traffic';
        });
    }

    // ============================================
    // LOAD EVENTS FOR THIS LOCATION
    // ============================================

    async function loadLocationEvents() {
        const eventsContainer = document.getElementById('location-events');

        if (!eventsContainer) return;

        try {
            // Fetch events from your API
            const response = await fetch('../api/startgg-events.php');
            const data = await response.json();

            if (data && data.tournaments && data.tournaments.length > 0) {
                // Show first 3 upcoming events
                const upcomingEvents = data.tournaments.slice(0, 3);

                let eventsHTML = '<div class="events-grid">';

                upcomingEvents.forEach(event => {
                    const eventDate = new Date(event.startAt * 1000);
                    const formattedDate = eventDate.toLocaleDateString('en-US', {
                        month: 'short',
                        day: 'numeric',
                        year: 'numeric'
                    });

                    eventsHTML += `
                        <div class="event-card">
                            <div class="event-date">${formattedDate}</div>
                            <h4>${event.name}</h4>
                            <p>${event.numAttendees || 0} attendees</p>
                            <a href="${event.url}" target="_blank" class="btn btn-secondary">View Event</a>
                        </div>
                    `;
                });

                eventsHTML += '</div>';

                eventsContainer.innerHTML = eventsHTML;
            } else {
                eventsContainer.innerHTML = `
                    <div class="no-events">
                        <p>No upcoming events scheduled at this location.</p>
                        <a href="../events/" class="btn btn-primary">Browse All Events</a>
                    </div>
                `;
            }
        } catch (error) {
            console.error('Error loading events:', error);
            eventsContainer.innerHTML = `
                <div class="events-error">
                    <p>Unable to load events. Please try again later.</p>
                    <a href="../events/" class="btn btn-primary">View Events Page</a>
                </div>
            `;
        }
    }

    // ============================================
    // NOTIFICATION SYSTEM
    // ============================================

    function showNotification(message, type = 'info') {
        const notification = document.createElement('div');
        notification.className = `notification notification-${type}`;
        notification.textContent = message;

        // Inline styles
        Object.assign(notification.style, {
            position: 'fixed',
            top: '20px',
            right: '20px',
            padding: '16px 24px',
            background: type === 'success' ? '#d4edda' : type === 'error' ? '#f8d7da' : '#cce5ff',
            color: type === 'success' ? '#155724' : type === 'error' ? '#721c24' : '#004085',
            borderRadius: '8px',
            boxShadow: '0 4px 12px rgba(0,0,0,0.15)',
            zIndex: '10000',
            fontWeight: '600',
            animation: 'slideIn 0.3s ease',
            maxWidth: '400px'
        });

        document.body.appendChild(notification);

        // Remove after 4 seconds
        setTimeout(() => {
            notification.style.animation = 'slideOut 0.3s ease';
            setTimeout(() => {
                document.body.removeChild(notification);
            }, 300);
        }, 4000);
    }

    // Add animation keyframes
    const style = document.createElement('style');
    style.textContent = `
        @keyframes slideIn {
            from {
                transform: translateX(400px);
                opacity: 0;
            }
            to {
                transform: translateX(0);
                opacity: 1;
            }
        }
        @keyframes slideOut {
            from {
                transform: translateX(0);
                opacity: 1;
            }
            to {
                transform: translateX(400px);
                opacity: 0;
            }
        }
    `;
    document.head.appendChild(style);

    // ============================================
    // SMOOTH SCROLLING FOR ANCHOR LINKS
    // ============================================

    document.querySelectorAll('a[href^="#"]').forEach(anchor => {
        anchor.addEventListener('click', function(e) {
            const href = this.getAttribute('href');
            if (href === '#') return;

            e.preventDefault();
            const target = document.querySelector(href);

            if (target) {
                target.scrollIntoView({
                    behavior: 'smooth',
                    block: 'start'
                });
            }
        });
    });

    // ============================================
    // INITIALIZATION
    // ============================================

    function init() {
        console.log('🗺️ Locations page initialized');

        // Update status and wait times
        updateLocationStatus();
        updateWaitTime();

        // Update every minute
        setInterval(updateLocationStatus, 60000);
        setInterval(updateWaitTime, 60000);

        // Load events
        loadLocationEvents();

        // Add animation on scroll for section visibility
        observeSections();
    }

    // ============================================
    // INTERSECTION OBSERVER FOR ANIMATIONS
    // ============================================

    function observeSections() {
        const sections = document.querySelectorAll('section');

        const observer = new IntersectionObserver((entries) => {
            entries.forEach(entry => {
                if (entry.isIntersecting) {
                    entry.target.style.opacity = '1';
                    entry.target.style.transform = 'translateY(0)';
                }
            });
        }, {
            threshold: 0.1,
            rootMargin: '0px 0px -100px 0px'
        });

        sections.forEach(section => {
            section.style.opacity = '0';
            section.style.transform = 'translateY(30px)';
            section.style.transition = 'all 0.6s ease';
            observer.observe(section);
        });
    }

    // ============================================
    // RUN ON PAGE LOAD
    // ============================================

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', init);
    } else {
        init();
    }

})();
