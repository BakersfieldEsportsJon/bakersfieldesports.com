/**
 * Stats Counter Animation with GGLeap Integration
 * Bakersfield eSports - 2025
 */

(function() {
    'use strict';

    // ============================================
    // CONFIGURATION
    // ============================================

    const STATS_CONFIG = {
        // Static stats (always shown)
        static: {
            gamingStations: 40,
            tournaments: 150,
            yearsOpen: 4,
            communitySize: 8500
        },

        // GGLeap API configuration
        ggLeap: {
            enabled: true, // Set to false to disable GGLeap integration
            apiEndpoint: 'api/ggleap-stats.php', // Your API proxy endpoint
            refreshInterval: 300000, // Refresh every 5 minutes (300000ms)
            fallbackValues: {
                totalAccounts: 5801,
                newAccountsLast30Days: 45
            }
        },

        // Animation settings
        animation: {
            duration: 2000, // 2 seconds
            easing: 'easeOutExpo'
        }
    };

    // ============================================
    // EASING FUNCTIONS
    // ============================================

    const easingFunctions = {
        linear: t => t,
        easeOutExpo: t => t === 1 ? 1 : 1 - Math.pow(2, -10 * t),
        easeOutCubic: t => 1 - Math.pow(1 - t, 3)
    };

    // ============================================
    // NUMBER FORMATTER
    // ============================================

    function formatNumber(num) {
        if (num >= 1000000) {
            return (num / 1000000).toFixed(1) + 'M';
        } else if (num >= 1000) {
            return (num / 1000).toFixed(1) + 'K';
        }
        return num.toLocaleString();
    }

    // ============================================
    // COUNTER ANIMATION
    // ============================================

    function animateCounter(element, start, end, duration, easingName = 'easeOutExpo') {
        const startTime = performance.now();
        const easing = easingFunctions[easingName] || easingFunctions.linear;
        const suffix = element.dataset.suffix || '';
        const prefix = element.dataset.prefix || '';

        function update(currentTime) {
            const elapsed = currentTime - startTime;
            const progress = Math.min(elapsed / duration, 1);
            const easedProgress = easing(progress);
            const current = Math.floor(start + (end - start) * easedProgress);

            element.textContent = prefix + formatNumber(current) + suffix;

            if (progress < 1) {
                requestAnimationFrame(update);
            } else {
                // Ensure we end on exact number
                element.textContent = prefix + formatNumber(end) + suffix;
            }
        }

        requestAnimationFrame(update);
    }

    // ============================================
    // GGLEAP API INTEGRATION
    // ============================================

    class GGLeapStats {
        constructor(config) {
            this.config = config;
            this.data = null;
            this.lastFetch = null;
        }

        async fetchStats() {
            if (!this.config.enabled) {
                return this.config.fallbackValues;
            }

            try {
                const response = await fetch(this.config.apiEndpoint, {
                    method: 'GET',
                    headers: {
                        'Content-Type': 'application/json'
                    },
                    cache: 'no-cache'
                });

                if (!response.ok) {
                    throw new Error('API request failed');
                }

                const data = await response.json();
                this.data = data;
                this.lastFetch = Date.now();
                return data;

            } catch (error) {
                console.warn('GGLeap API error, using fallback values:', error);
                return this.config.fallbackValues;
            }
        }

        shouldRefresh() {
            if (!this.lastFetch) return true;
            const timeSinceLastFetch = Date.now() - this.lastFetch;
            return timeSinceLastFetch >= this.config.refreshInterval;
        }

        async getStats() {
            if (this.shouldRefresh()) {
                return await this.fetchStats();
            }
            return this.data || this.config.fallbackValues;
        }
    }

    // ============================================
    // STATS SECTION INITIALIZER
    // ============================================

    class StatsCounter {
        constructor() {
            this.ggLeap = new GGLeapStats(STATS_CONFIG.ggLeap);
            this.hasAnimated = false;
            this.observer = null;
        }

        async init() {
            const statsSection = document.querySelector('.stats-section');
            if (!statsSection) {
                console.log('Stats section not found, skipping initialization');
                return;
            }

            // Set up intersection observer
            this.setupObserver(statsSection);

            // Fetch GGLeap stats
            if (STATS_CONFIG.ggLeap.enabled) {
                await this.loadGGLeapStats();
            }
        }

        setupObserver(element) {
            const options = {
                threshold: 0.3,
                rootMargin: '0px'
            };

            this.observer = new IntersectionObserver((entries) => {
                entries.forEach(entry => {
                    if (entry.isIntersecting && !this.hasAnimated) {
                        this.animateAllCounters();
                        this.hasAnimated = true;
                    }
                });
            }, options);

            this.observer.observe(element);
        }

        animateAllCounters() {
            const counters = document.querySelectorAll('[data-count]');

            counters.forEach((counter, index) => {
                const targetValue = parseInt(counter.dataset.count, 10);
                const delay = index * 100; // Stagger animation

                setTimeout(() => {
                    animateCounter(
                        counter,
                        0,
                        targetValue,
                        STATS_CONFIG.animation.duration,
                        STATS_CONFIG.animation.easing
                    );
                }, delay);
            });
        }

        async loadGGLeapStats() {
            try {
                const stats = await this.ggLeap.getStats();

                // Update stat elements with GGLeap data
                this.updateStatElement('ggleap-accounts', stats.totalAccounts);
                this.updateStatElement('ggleap-new-accounts', stats.newAccountsLast30Days);

                console.log('✅ GGLeap stats loaded:', stats);

                // Set up periodic refresh
                this.scheduleRefresh();

            } catch (error) {
                console.error('Error loading GGLeap stats:', error);
            }
        }

        updateStatElement(id, value) {
            const element = document.querySelector(`[data-stat-id="${id}"]`);
            if (element) {
                element.dataset.count = value;
            }
        }

        scheduleRefresh() {
            if (!STATS_CONFIG.ggLeap.enabled) return;

            setInterval(async () => {
                console.log('Refreshing GGLeap stats...');
                await this.loadGGLeapStats();

                // Re-animate if section is visible
                if (this.hasAnimated) {
                    this.animateAllCounters();
                }
            }, STATS_CONFIG.ggLeap.refreshInterval);
        }

        // Public method to manually refresh
        async refresh() {
            await this.loadGGLeapStats();
            this.animateAllCounters();
        }
    }

    // ============================================
    // INITIALIZE ON PAGE LOAD
    // ============================================

    let statsCounter = null;

    function initStatsCounter() {
        if (document.readyState === 'loading') {
            document.addEventListener('DOMContentLoaded', runInit);
        } else {
            runInit();
        }
    }

    async function runInit() {
        console.log('🔢 Initializing stats counter...');
        statsCounter = new StatsCounter();
        await statsCounter.init();
        console.log('✅ Stats counter initialized!');
    }

    // Start initialization
    initStatsCounter();

    // ============================================
    // EXPORT TO WINDOW
    // ============================================

    window.BakersFieldStats = {
        refresh: () => {
            if (statsCounter) {
                return statsCounter.refresh();
            }
        },
        getConfig: () => STATS_CONFIG,
        updateConfig: (newConfig) => {
            Object.assign(STATS_CONFIG, newConfig);
        }
    };

})();
