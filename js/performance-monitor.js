// Performance Monitoring
(() => {
    // Core Web Vitals
    const reportWebVitals = () => {
        try {
            const observer = new PerformanceObserver((list) => {
                list.getEntries().forEach((entry) => {
                    // Log Core Web Vitals
                    if (entry.name === 'LCP') {
                        console.log('Largest Contentful Paint:', entry.startTime);
                    }
                    if (entry.name === 'FID') {
                        console.log('First Input Delay:', entry.processingStart - entry.startTime);
                    }
                    if (entry.name === 'CLS') {
                        console.log('Cumulative Layout Shift:', entry.value);
                    }
                });
            });

            // Observe performance entries
            observer.observe({ entryTypes: ['largest-contentful-paint', 'first-input', 'layout-shift'] });
        } catch (e) {
            console.error('Web Vitals monitoring error:', e);
        }
    };

    // Resource Timing
    const reportResourceTiming = () => {
        try {
            const resources = performance.getEntriesByType('resource');
            const resourceStats = resources.map(resource => ({
                name: resource.name.split('/').pop(),
                type: resource.initiatorType,
                duration: Math.round(resource.duration),
                size: resource.transferSize
            }));

            console.log('Resource Timing:', resourceStats);

            // Report slow resources (>1s)
            const slowResources = resourceStats.filter(r => r.duration > 1000);
            if (slowResources.length > 0) {
                console.warn('Slow Resources:', slowResources);
            }
        } catch (e) {
            console.error('Resource timing error:', e);
        }
    };

    // Navigation Timing
    const reportNavigationTiming = () => {
        try {
            const navigation = performance.getEntriesByType('navigation')[0];
            console.log('Navigation Timing:', {
                'DNS lookup': Math.round(navigation.domainLookupEnd - navigation.domainLookupStart),
                'Connection time': Math.round(navigation.connectEnd - navigation.connectStart),
                'First byte': Math.round(navigation.responseStart - navigation.requestStart),
                'DOM Interactive': Math.round(navigation.domInteractive),
                'DOM Complete': Math.round(navigation.domComplete),
                'Load Event': Math.round(navigation.loadEventEnd - navigation.loadEventStart),
                'Total Page Load': Math.round(navigation.loadEventEnd)
            });
        } catch (e) {
            console.error('Navigation timing error:', e);
        }
    };

    // Initialize monitoring
    const initMonitoring = () => {
        // Report Web Vitals
        reportWebVitals();

        // Report Navigation Timing after load
        window.addEventListener('load', () => {
            // Wait for a moment to ensure all metrics are available
            setTimeout(() => {
                reportNavigationTiming();
                reportResourceTiming();
            }, 0);
        });
    };

    // Start monitoring
    initMonitoring();
})();
