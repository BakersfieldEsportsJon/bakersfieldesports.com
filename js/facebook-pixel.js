!function(f,b,e,v,n,t,s)
{if(f.fbq)return;n=f.fbq=function(){n.callMethod?
n.callMethod.apply(n,arguments):n.queue.push(arguments)};
if(!f._fbq)f._fbq=n;n.push=n;n.loaded=!0;n.version='2.0';
n.queue=[];t=b.createElement(e);t.async=!0;
t.src=v;s=b.getElementsByTagName(e)[0];
s.parentNode.insertBefore(t,s)}(window, document,'script',
'https://connect.facebook.net/en_US/fbevents.js');

// Initialize Facebook Pixel
fbq('init', 'YOUR-PIXEL-ID'); // Replace with your actual Pixel ID
fbq('track', 'PageView');

// Track specific events
document.addEventListener('DOMContentLoaded', function() {
    // Track CTA clicks
    document.querySelectorAll('.btn-primary').forEach(button => {
        button.addEventListener('click', function() {
            fbq('track', 'Lead', {
                content_name: this.textContent.trim(),
                content_category: 'CTA'
            });
        });
    });

    // Track form submissions
    const forms = document.querySelectorAll('form');
    forms.forEach(form => {
        form.addEventListener('submit', function() {
            fbq('track', 'Lead', {
                content_category: 'Form',
                content_name: form.getAttribute('name') || 'Unknown Form'
            });
        });
    });
});
