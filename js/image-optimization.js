// Image optimization and responsive loading
const createResponsiveImage = (src, alt, className = '') => {
    // Extract file extension and name
    const ext = src.split('.').pop();
    const baseName = src.replace(`.${ext}`, '');
    
    // Create picture element
    const picture = document.createElement('picture');
    
    // Add WebP source
    const webpSource = document.createElement('source');
    webpSource.srcset = `${baseName}.webp`;
    webpSource.type = 'image/webp';
    picture.appendChild(webpSource);
    
    // Add original format source
    const originalSource = document.createElement('source');
    originalSource.srcset = src;
    originalSource.type = `image/${ext}`;
    picture.appendChild(originalSource);
    
    // Add img element as fallback
    const img = document.createElement('img');
    img.src = src;
    img.alt = alt;
    if (className) {
        img.className = className;
    }
    img.loading = 'lazy'; // Enable lazy loading
    picture.appendChild(img);
    
    return picture;
};

// Replace all images with responsive picture elements
document.addEventListener('DOMContentLoaded', () => {
    const images = document.querySelectorAll('img:not([data-no-optimize])');
    
    images.forEach(img => {
        if (img.parentNode.tagName !== 'PICTURE') {
            const picture = createResponsiveImage(img.src, img.alt, img.className);
            img.parentNode.replaceChild(picture, img);
        }
    });
});

// Lazy loading for background images
const lazyLoadBackgroundImages = () => {
    const elements = document.querySelectorAll('[data-bg]');
    
    const loadBackgroundImage = (element) => {
        const src = element.getAttribute('data-bg');
        if (src) {
            element.style.backgroundImage = `url(${src})`;
            element.removeAttribute('data-bg');
        }
    };
    
    const observer = new IntersectionObserver((entries) => {
        entries.forEach(entry => {
            if (entry.isIntersecting) {
                loadBackgroundImage(entry.target);
                observer.unobserve(entry.target);
            }
        });
    });
    
    elements.forEach(element => observer.observe(element));
};

// Initialize lazy loading for background images
document.addEventListener('DOMContentLoaded', lazyLoadBackgroundImages);
