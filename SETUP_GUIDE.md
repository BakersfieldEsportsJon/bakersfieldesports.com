# Setup Guide - Bakersfield eSports Website

## 🚀 Quick Start

### Prerequisites
- Node.js 20.x or higher
- PHP 7.4 or higher
- Access to cPanel or server configuration

---

## 📦 Installation

### 1. Install Dependencies
```bash
cd public_html
npm install
```

### 2. Build Assets
```bash
# Build CSS and optimize images
npm run build

# Or run separately:
npm run build:css        # Build and minify CSS
npm run optimize:images  # Convert images to WebP/AVIF
```

---

## 🔒 Security Setup

### Move .env File (IMPORTANT!)

**Current location:** `public_html/.env`
**New location:** `../.env` (parent directory, outside webroot)

```bash
# From public_html directory:
mv .env ../.env
```

**Why?** Keeping `.env` outside the webroot prevents it from being accessed via HTTP requests.

### Update .env File

Edit `../.env` and add your API keys:

```env
# Google Analytics
GA_ID=G-09FBJQFRQE

# Facebook Pixel
FB_PIXEL_ID=1055372993198040

# start.gg API (get from: https://developer.start.gg/)
STARTGG_API_KEY=your_api_key_here

# Stripe (if using payment features)
STRIPE_PUBLIC_KEY=pk_live_...
STRIPE_SECRET_KEY=sk_live_...

# Database (if applicable)
DB_HOST=localhost
DB_NAME=database_name
DB_USER=database_user
DB_PASS=database_password
```

---

## 🎨 Using the New Template System

### Converting a Page

**Old way (index.html):**
```html
<!DOCTYPE html>
<html>
<head>
    <!-- 100+ lines of duplicate head content -->
</head>
<body>
    <!-- 50+ lines of duplicate navigation -->
    <main>Your content</main>
    <!-- 80+ lines of duplicate footer -->
</body>
</html>
```

**New way (index.php):**
```php
<?php
// Page configuration
$page_title = 'Page Title | Bakersfield eSports';
$page_description = 'Your meta description';
$canonical_url = 'https://bakersfieldesports.com/page/';
$active_page = 'home'; // Highlights nav item
$base_path = '../'; // or '' for root pages

// Optional: Add schemas
require_once 'includes/schemas.php';
$schema_markup = getLocalBusinessSchema();

// Include templates
require_once 'includes/head.php';
require_once 'includes/nav.php';
?>

<main>
    <!-- Your page content here -->
</main>

<?php require_once 'includes/footer-content.php'; ?>
```

### Using Responsive Images

Include the image helpers:
```php
<?php require_once 'includes/image-helpers.php'; ?>
```

Generate responsive image:
```php
<?php echo responsive_image('images/hero', 'Hero Image', [
    'class' => 'hero-img',
    'width' => 1920,
    'height' => 1080,
    'loading' => 'eager' // or 'lazy'
]); ?>
```

Outputs:
```html
<picture>
    <source srcset="images/hero.avif" type="image/avif">
    <source srcset="images/hero.webp" type="image/webp">
    <img src="images/hero.jpg" alt="Hero Image" class="hero-img" width="1920" height="1080" loading="eager">
</picture>
```

---

## 🎮 start.gg Integration

### 1. Get API Key

1. Go to https://developer.start.gg/
2. Create an account / log in
3. Generate an API key
4. Add to `.env` file

### 2. Using the API Module

```javascript
import startgg from './modules/startgg-api.js';

// Fetch tournament
const tournament = await startgg.getTournament('tournament/champions-gauntlet');
const formatted = startgg.formatTournamentForDisplay(tournament);

// Display in your page
const container = document.getElementById('tournament-details');
startgg.displayTournamentDetails(formatted, container);
```

### 3. Event Page Integration

The events module is already set up to integrate with start.gg. Update your tournament slugs in the events JSON or directly fetch from start.gg.

---

## 🏗️ File Structure

```
public_html/
├── includes/
│   ├── config.php              # Site configuration
│   ├── head.php                # HTML head template
│   ├── nav.php                 # Navigation component
│   ├── footer-content.php      # Footer component
│   ├── schemas.php             # Schema.org generators
│   ├── image-helpers.php       # Responsive image helpers
│   └── secure-config.php       # Environment loader
│
├── css/
│   ├── variables.css           # CSS custom properties
│   ├── core.css                # Base styles
│   ├── components.css          # Components
│   ├── layout.css              # Layouts
│   ├── utilities.css           # Utility classes
│   ├── main.css                # Imports all above
│   └── optimized.min.css       # Production build
│
├── js/
│   ├── modules/
│   │   ├── utils.js            # Utilities
│   │   ├── events.js           # Events management
│   │   ├── navigation.js       # Nav functionality
│   │   ├── analytics.js        # Analytics tracking
│   │   └── startgg-api.js      # start.gg integration
│   └── main.js                 # App entry point
│
├── build-css.js                # CSS build script
├── optimize-images.js          # Image optimization
├── package.json                # NPM configuration
└── .htaccess                   # Server configuration
```

---

## 📝 Development Workflow

### 1. Making CSS Changes

Edit source files in `css/` directory:
```bash
# Edit css/components.css, css/layout.css, etc.

# Rebuild
npm run build:css
```

### 2. Adding New JavaScript Features

Create a module in `js/modules/`:
```javascript
// js/modules/my-feature.js
export function myFunction() {
    // Your code
}
```

Import in `main.js`:
```javascript
import { myFunction } from './modules/my-feature.js';
```

### 3. Optimizing New Images

```bash
# Optimize all images in images/ directory
npm run optimize:images

# Or specific directory
node optimize-images.js images/events
```

---

## 🎯 Performance Checklist

- [x] CSS minified and optimized
- [x] Images converted to WebP/AVIF
- [x] Analytics loaded asynchronously
- [x] Service Worker for caching
- [x] Browser caching configured (.htaccess)
- [x] GZIP compression enabled
- [x] Responsive images with lazy loading
- [ ] Run Lighthouse audit
- [ ] Test on mobile devices
- [ ] Check Core Web Vitals

---

## 🔍 Testing

### Lighthouse Audit
```bash
# Install Lighthouse CLI
npm install -g lighthouse

# Run audit
lighthouse https://bakersfieldesports.com --view
```

### Expected Scores
- **Performance:** 90+
- **Accessibility:** 95+
- **Best Practices:** 95+
- **SEO:** 100

### Manual Testing Checklist

- [ ] All pages load without errors
- [ ] Navigation works on mobile
- [ ] Forms submit correctly
- [ ] Images display properly
- [ ] Analytics tracking works
- [ ] start.gg integration functions
- [ ] Service worker registers
- [ ] Meta tags are correct
- [ ] Schema markup validates

---

## 🐛 Troubleshooting

### CSS not updating?
```bash
# Clear browser cache, then rebuild
npm run build:css
```

### Images not optimizing?
Check that Sharp is installed:
```bash
npm install sharp --save
```

### Analytics not tracking?
1. Check browser console for errors
2. Verify API keys in `.env`
3. Check that `js/main.js` is loading

### start.gg API errors?
1. Verify API key is correct
2. Check API rate limits
3. Ensure tournament slug is valid

---

## 📚 Resources

- [start.gg API Docs](https://developer.start.gg/docs/intro)
- [Schema.org Validator](https://validator.schema.org/)
- [Google PageSpeed Insights](https://pagespeed.web.dev/)
- [WebP Conversion Info](https://developers.google.com/speed/webp)

---

## 🆘 Support

For issues or questions:
1. Check this guide first
2. Review `OPTIMIZATION_SUMMARY.md`
3. Check browser console for errors
4. Review server error logs

---

**Last Updated:** October 2025
**Version:** 2.0.0
