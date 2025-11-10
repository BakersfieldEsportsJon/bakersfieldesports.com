// Google Analytics 4 Configuration
window.dataLayer = window.dataLayer || [];
function gtag(){dataLayer.push(arguments);}
gtag('js', new Date());
gtag('config', 'G-09FBJQFRQE');

// Custom event tracking
document.addEventListener('DOMContentLoaded', function() {
    // Track outbound links
    document.querySelectorAll('a[href^="http"]').forEach(link => {
        link.addEventListener('click', function() {
            gtag('event', 'click', {
                'event_category': 'Outbound Link',
                'event_label': this.href
            });
        });
    });

    // Track form submissions
    document.querySelectorAll('form').forEach(form => {
        form.addEventListener('submit', function() {
            gtag('event', 'form_submit', {
                'event_category': 'Form',
                'event_label': form.getAttribute('name') || 'Unknown Form'
            });
        });
    });

    // Track CTA button clicks
    document.querySelectorAll('.btn-primary').forEach(button => {
        button.addEventListener('click', function() {
            gtag('event', 'click', {
                'event_category': 'CTA',
                'event_label': this.textContent.trim()
            });
        });
    });

    // Track scroll depth
    let scrollDepths = [25, 50, 75, 90];
    let scrollDepthTriggered = new Set();
    
    window.addEventListener('scroll', function() {
        const winHeight = window.innerHeight;
        const docHeight = document.documentElement.scrollHeight;
        const scrollTop = window.scrollY;
        const scrollPercent = (scrollTop / (docHeight - winHeight)) * 100;
        
        scrollDepths.forEach(depth => {
            if (scrollPercent >= depth && !scrollDepthTriggered.has(depth)) {
                scrollDepthTriggered.add(depth);
                gtag('event', 'scroll_depth', {
                    'event_category': 'Scroll',
                    'event_label': depth + '%'
                });
            }
        });
    });

    // Track time on page
    let timeIntervals = [30, 60, 120, 300]; // seconds
    timeIntervals.forEach(time => {
        setTimeout(() => {
            gtag('event', 'time_on_page', {
                'event_category': 'Engagement',
                'event_label': time + ' seconds'
            });
        }, time * 1000);
    });
});
