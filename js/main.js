/**
 * Main Application Entry Point
 * Initializes all modules and handles page-specific logic
 */

import { initNavigation } from './modules/navigation.js';
import { initEventsPage } from './modules/events.js';
import { initAnalytics } from './modules/analytics.js';

/**
 * Initialize common functionality for all pages
 */
function initCommon() {
    // Initialize navigation
    initNavigation();

    // Initialize analytics (optimized, non-blocking)
    initAnalytics();

    // Initialize service worker for PWA support
    if ('serviceWorker' in navigator) {
        window.addEventListener('load', () => {
            navigator.serviceWorker.register('/js/service-worker.js')
                .then(registration => {
                    console.log('ServiceWorker registration successful');
                })
                .catch(error => {
                    console.log('ServiceWorker registration failed:', error);
                });
        });
    }

    // Add smooth scrolling to anchor links
    document.querySelectorAll('a[href^="#"]').forEach(anchor => {
        anchor.addEventListener('click', function (e) {
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

    // Add fade-in animation to sections as they come into view
    const observerOptions = {
        threshold: 0.1,
        rootMargin: '0px 0px -50px 0px'
    };

    const observer = new IntersectionObserver((entries) => {
        entries.forEach(entry => {
            if (entry.isIntersecting) {
                entry.target.classList.add('fade-in');
                observer.unobserve(entry.target);
            }
        });
    }, observerOptions);

    document.querySelectorAll('section').forEach(section => {
        observer.observe(section);
    });
}

/**
 * Initialize page-specific functionality
 */
function initPageSpecific() {
    const path = window.location.pathname;

    // Events page
    if (path.includes('/events/')) {
        initEventsPage();
    }

    // Contact page - initialize form validation
    if (path.includes('/contact')) {
        // Form validation will be added here
        console.log('Contact page initialized');
    }

    // Gallery page
    if (path.includes('/gallery')) {
        // Gallery functionality will be added here
        console.log('Gallery page initialized');
    }
}

/**
 * Main initialization
 */
document.addEventListener('DOMContentLoaded', () => {
    initCommon();
    initPageSpecific();
});

// Export for potential external use
export { initCommon, initPageSpecific };
