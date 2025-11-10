/**
 * Modern Interactions & Animations
 * Bakersfield eSports - 2025
 */

(function() {
    'use strict';

    // ============================================
    // SCROLL ANIMATIONS
    // ============================================

    /**
     * Intersection Observer for scroll-triggered animations
     */
    function initScrollAnimations() {
        const animatedElements = document.querySelectorAll(
            '.animate-on-scroll, .animate-slide-left, .animate-slide-right, .animate-scale, .stagger-animation'
        );

        if (!animatedElements.length) return;

        const observerOptions = {
            threshold: 0.15,
            rootMargin: '0px 0px -100px 0px'
        };

        const observer = new IntersectionObserver((entries) => {
            entries.forEach(entry => {
                if (entry.isIntersecting) {
                    entry.target.classList.add('animated');
                    // Optional: unobserve after animation
                    // observer.unobserve(entry.target);
                }
            });
        }, observerOptions);

        animatedElements.forEach(el => observer.observe(el));
    }

    // ============================================
    // BUTTON RIPPLE EFFECT
    // ============================================

    /**
     * Add ripple effect to buttons on click
     */
    function initRippleEffect() {
        const buttons = document.querySelectorAll('.btn, .btn-primary, button');

        buttons.forEach(button => {
            button.addEventListener('click', function(e) {
                // Create ripple element
                const ripple = document.createElement('span');
                ripple.classList.add('ripple');

                // Calculate position
                const rect = this.getBoundingClientRect();
                const size = Math.max(rect.width, rect.height);
                const x = e.clientX - rect.left - size / 2;
                const y = e.clientY - rect.top - size / 2;

                // Set size and position
                ripple.style.width = ripple.style.height = size + 'px';
                ripple.style.left = x + 'px';
                ripple.style.top = y + 'px';

                // Add to button
                this.appendChild(ripple);

                // Remove after animation
                setTimeout(() => ripple.remove(), 600);
            });
        });
    }

    // ============================================
    // NAVBAR SCROLL EFFECT
    // ============================================

    /**
     * Add class to navbar when scrolling
     */
    function initNavbarScroll() {
        const navbar = document.querySelector('.navbar');
        if (!navbar) return;

        let lastScroll = 0;
        const scrollThreshold = 100;

        window.addEventListener('scroll', () => {
            const currentScroll = window.pageYOffset;

            if (currentScroll > scrollThreshold) {
                navbar.classList.add('scrolled');
            } else {
                navbar.classList.remove('scrolled');
            }

            lastScroll = currentScroll;
        }, { passive: true });
    }

    // ============================================
    // SMOOTH SCROLL FOR ANCHOR LINKS
    // ============================================

    /**
     * Smooth scroll to anchor links
     */
    function initSmoothScroll() {
        document.querySelectorAll('a[href^="#"]').forEach(anchor => {
            anchor.addEventListener('click', function(e) {
                const href = this.getAttribute('href');

                // Skip if it's just "#" or empty
                if (!href || href === '#') return;

                const target = document.querySelector(href);
                if (!target) return;

                e.preventDefault();

                target.scrollIntoView({
                    behavior: 'smooth',
                    block: 'start'
                });

                // Update URL without jumping
                if (history.pushState) {
                    history.pushState(null, null, href);
                }
            });
        });
    }

    // ============================================
    // CARD 3D TILT EFFECT
    // ============================================

    /**
     * Add subtle 3D tilt effect to cards on mouse move
     */
    function init3DTiltEffect() {
        const cards = document.querySelectorAll('.event-item, .card, .location-item, .rate-item');

        cards.forEach(card => {
            card.addEventListener('mousemove', function(e) {
                if (window.innerWidth < 768) return; // Skip on mobile

                const rect = this.getBoundingClientRect();
                const x = e.clientX - rect.left;
                const y = e.clientY - rect.top;

                const centerX = rect.width / 2;
                const centerY = rect.height / 2;

                const rotateX = (y - centerY) / 10;
                const rotateY = (centerX - x) / 10;

                this.style.transform = `perspective(1000px) rotateX(${rotateX}deg) rotateY(${rotateY}deg) translateY(-15px) scale(1.02)`;
            });

            card.addEventListener('mouseleave', function() {
                this.style.transform = '';
            });
        });
    }

    // ============================================
    // PARALLAX SCROLL EFFECT
    // ============================================

    /**
     * Simple parallax effect for hero sections
     */
    function initParallaxEffect() {
        const parallaxElements = document.querySelectorAll('.hero, .hero-page');

        if (!parallaxElements.length) return;

        window.addEventListener('scroll', () => {
            const scrolled = window.pageYOffset;

            parallaxElements.forEach(element => {
                const speed = 0.5;
                element.style.backgroundPositionY = -(scrolled * speed) + 'px';
            });
        }, { passive: true });
    }

    // ============================================
    // LAZY LOADING IMAGES
    // ============================================

    /**
     * Lazy load images for better performance
     */
    function initLazyLoading() {
        const images = document.querySelectorAll('img[data-src]');

        if (!images.length) return;

        const imageObserver = new IntersectionObserver((entries) => {
            entries.forEach(entry => {
                if (entry.isIntersecting) {
                    const img = entry.target;
                    img.src = img.dataset.src;
                    img.removeAttribute('data-src');
                    imageObserver.unobserve(img);
                }
            });
        });

        images.forEach(img => imageObserver.observe(img));
    }

    // ============================================
    // ADD ANIMATION CLASSES TO EXISTING ELEMENTS
    // ============================================

    /**
     * Automatically add animation classes to common elements
     */
    function autoAddAnimationClasses() {
        // Add scroll animations to sections
        const sections = document.querySelectorAll('section, .events, .about, .partners, .top-games, .purchase');
        sections.forEach((section, index) => {
            if (!section.classList.contains('animate-on-scroll')) {
                section.classList.add('animate-on-scroll');
            }
        });

        // Add stagger animation to grids
        const grids = document.querySelectorAll('.events-grid, .locations-grid, .rates-grid, .photos-grid');
        grids.forEach(grid => {
            if (!grid.classList.contains('stagger-animation')) {
                grid.classList.add('stagger-animation');
            }
        });

        // Add pulse to primary CTA buttons
        const ctaButtons = document.querySelectorAll('.hero .btn-primary, .purchase .btn-primary');
        ctaButtons.forEach(btn => {
            if (!btn.classList.contains('btn-pulse')) {
                btn.classList.add('btn-pulse');
            }
        });

        // Add floating animation to hero content
        const heroContent = document.querySelector('.hero > div, .hero > .container');
        if (heroContent && !heroContent.classList.contains('hero-content')) {
            heroContent.classList.add('hero-content');
        }
    }

    // ============================================
    // PERFORMANCE MONITORING
    // ============================================

    /**
     * Log performance metrics (optional, for development)
     */
    function logPerformance() {
        if (window.performance && console.debug) {
            const perfData = window.performance.timing;
            const pageLoadTime = perfData.loadEventEnd - perfData.navigationStart;
            console.debug('🚀 Page Load Time:', pageLoadTime + 'ms');
        }
    }

    // ============================================
    // INITIALIZE ALL FEATURES
    // ============================================

    /**
     * Main initialization function
     */
    function init() {
        // Wait for DOM to be ready
        if (document.readyState === 'loading') {
            document.addEventListener('DOMContentLoaded', runInit);
        } else {
            runInit();
        }
    }

    function runInit() {
        console.log('🎮 Initializing modern interactions...');

        // Auto-add animation classes
        autoAddAnimationClasses();

        // Initialize features
        initScrollAnimations();
        initRippleEffect();
        initNavbarScroll();
        initSmoothScroll();
        init3DTiltEffect();
        initParallaxEffect();
        initLazyLoading();

        // Performance logging (development only)
        if (window.location.hostname === 'localhost') {
            logPerformance();
        }

        console.log('✅ Modern interactions initialized!');
    }

    // Start initialization
    init();

    // ============================================
    // UTILITY FUNCTIONS (exported to window)
    // ============================================

    window.BakersFieldModern = {
        /**
         * Trigger shake animation on element
         */
        shake: function(element) {
            if (typeof element === 'string') {
                element = document.querySelector(element);
            }
            if (element) {
                element.classList.add('shake');
                setTimeout(() => element.classList.remove('shake'), 500);
            }
        },

        /**
         * Show toast notification (basic)
         */
        toast: function(message, duration = 3000) {
            const toast = document.createElement('div');
            toast.textContent = message;
            toast.style.cssText = `
                position: fixed;
                bottom: 20px;
                right: 20px;
                background: rgba(236, 25, 77, 0.95);
                color: white;
                padding: 1em 1.5em;
                border-radius: 8px;
                box-shadow: 0 4px 20px rgba(0, 0, 0, 0.3);
                z-index: 10000;
                animation: slideInRight 0.3s ease-out;
                backdrop-filter: blur(10px);
            `;
            document.body.appendChild(toast);
            setTimeout(() => {
                toast.style.animation = 'slideOutRight 0.3s ease-out';
                setTimeout(() => toast.remove(), 300);
            }, duration);
        },

        /**
         * Manually trigger scroll animation
         */
        animateElement: function(element) {
            if (typeof element === 'string') {
                element = document.querySelector(element);
            }
            if (element) {
                element.classList.add('animated');
            }
        }
    };

})();
