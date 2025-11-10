/**
 * Analytics Module
 * Optimized Google Analytics and Facebook Pixel tracking
 */

import { throttle, debounce } from './utils.js';

// Analytics configuration
const GA_ID = 'G-09FBJQFRQE';
const FB_PIXEL_ID = '1055372993198040';

/**
 * Initialize Google Analytics
 */
export function initGoogleAnalytics() {
    // Initialize dataLayer
    window.dataLayer = window.dataLayer || [];
    window.gtag = function() { dataLayer.push(arguments); };

    gtag('js', new Date());
    gtag('config', GA_ID, {
        'send_page_view': true,
        'cookie_flags': 'SameSite=None;Secure'
    });
}

/**
 * Initialize Facebook Pixel
 */
export function initFacebookPixel() {
    !function(f,b,e,v,n,t,s) {
        if(f.fbq) return;
        n=f.fbq=function() {
            n.callMethod ? n.callMethod.apply(n,arguments) : n.queue.push(arguments)
        };
        if(!f._fbq) f._fbq=n;
        n.push=n;
        n.loaded=!0;
        n.version='2.0';
        n.queue=[];
        t=b.createElement(e);
        t.async=!0;
        t.src=v;
        s=b.getElementsByTagName(e)[0];
        s.parentNode.insertBefore(t,s)
    }(window, document, 'script', 'https://connect.facebook.net/en_US/fbevents.js');

    fbq('init', FB_PIXEL_ID);
    fbq('track', 'PageView');
}

/**
 * Track custom event to both GA and FB Pixel
 * @param {string} eventName - Event name
 * @param {Object} params - Event parameters
 */
export function trackEvent(eventName, params = {}) {
    // Google Analytics
    if (window.gtag) {
        gtag('event', eventName, params);
    }

    // Facebook Pixel (map GA events to FB events)
    if (window.fbq) {
        const fbEventMap = {
            'form_submit': 'Lead',
            'cta_click': 'Lead',
            'register_tournament': 'CompleteRegistration',
            'view_event': 'ViewContent',
            'purchase_gift_card': 'Purchase'
        };

        const fbEvent = fbEventMap[eventName] || 'CustomEvent';
        fbq('track', fbEvent, params);
    }
}

/**
 * Track outbound links
 */
function trackOutboundLinks() {
    document.querySelectorAll('a[href^="http"]').forEach(link => {
        // Skip links to own domain
        if (link.hostname === window.location.hostname) return;

        link.addEventListener('click', function(e) {
            trackEvent('click', {
                'event_category': 'Outbound Link',
                'event_label': this.href,
                'transport_type': 'beacon'
            });
        });
    });
}

/**
 * Track form submissions
 */
function trackFormSubmissions() {
    document.querySelectorAll('form').forEach(form => {
        form.addEventListener('submit', function(e) {
            const formName = form.getAttribute('name') ||
                            form.getAttribute('id') ||
                            'Unknown Form';

            trackEvent('form_submit', {
                'event_category': 'Form',
                'event_label': formName
            });
        });
    });
}

/**
 * Track CTA button clicks
 */
function trackCTAClicks() {
    document.querySelectorAll('.btn-primary, .btn-cta, [data-track="cta"]').forEach(button => {
        button.addEventListener('click', function() {
            trackEvent('cta_click', {
                'event_category': 'CTA',
                'event_label': this.textContent.trim(),
                'value': this.getAttribute('data-value') || undefined
            });
        });
    });
}

/**
 * Track scroll depth (throttled for performance)
 */
function trackScrollDepth() {
    const scrollDepths = [25, 50, 75, 90];
    const scrollDepthTriggered = new Set();

    const handleScroll = throttle(() => {
        const winHeight = window.innerHeight;
        const docHeight = document.documentElement.scrollHeight;
        const scrollTop = window.scrollY;
        const scrollPercent = (scrollTop / (docHeight - winHeight)) * 100;

        scrollDepths.forEach(depth => {
            if (scrollPercent >= depth && !scrollDepthTriggered.has(depth)) {
                scrollDepthTriggered.add(depth);
                trackEvent('scroll_depth', {
                    'event_category': 'Scroll',
                    'event_label': depth + '%',
                    'value': depth
                });
            }
        });
    }, 500); // Throttle to once per 500ms

    window.addEventListener('scroll', handleScroll, { passive: true });
}

/**
 * Track time on page
 */
function trackTimeOnPage() {
    const timeIntervals = [30, 60, 120, 300]; // seconds

    timeIntervals.forEach(time => {
        setTimeout(() => {
            trackEvent('time_on_page', {
                'event_category': 'Engagement',
                'event_label': time + ' seconds',
                'value': time
            });
        }, time * 1000);
    });
}

/**
 * Track video plays (if videos exist)
 */
function trackVideoEngagement() {
    document.querySelectorAll('video, iframe[src*="youtube"], iframe[src*="vimeo"]').forEach(video => {
        if (video.tagName === 'VIDEO') {
            video.addEventListener('play', () => {
                trackEvent('video_play', {
                    'event_category': 'Video',
                    'event_label': video.getAttribute('title') || video.src
                });
            });
        } else {
            // For YouTube/Vimeo iframes
            video.addEventListener('load', () => {
                trackEvent('video_load', {
                    'event_category': 'Video',
                    'event_label': video.src
                });
            });
        }
    });
}

/**
 * Initialize all analytics tracking
 */
export function initAnalytics() {
    // Load analytics scripts asynchronously
    const loadAnalyticsScripts = () => {
        // Google Analytics
        const gaScript = document.createElement('script');
        gaScript.async = true;
        gaScript.src = `https://www.googletagmanager.com/gtag/js?id=${GA_ID}`;
        document.head.appendChild(gaScript);

        gaScript.onload = () => {
            initGoogleAnalytics();
        };

        // Facebook Pixel
        initFacebookPixel();
    };

    // Use requestIdleCallback for better performance
    if ('requestIdleCallback' in window) {
        requestIdleCallback(loadAnalyticsScripts);
    } else {
        setTimeout(loadAnalyticsScripts, 1);
    }

    // Set up event tracking after DOM is ready
    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', setupEventTracking);
    } else {
        setupEventTracking();
    }
}

/**
 * Set up all event tracking
 */
function setupEventTracking() {
    trackOutboundLinks();
    trackFormSubmissions();
    trackCTAClicks();
    trackScrollDepth();
    trackTimeOnPage();
    trackVideoEngagement();
}

// Export individual tracking functions for custom use
export {
    trackOutboundLinks,
    trackFormSubmissions,
    trackCTAClicks,
    trackScrollDepth,
    trackTimeOnPage,
    trackVideoEngagement
};
