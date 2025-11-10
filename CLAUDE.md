# CLAUDE.md

This file provides guidance to Claude Code (claude.ai/code) when working with code in this repository.

## Project Overview

Bakersfield eSports Center website - a full-stack PHP/JavaScript application for an esports gaming venue featuring tournament management, event scheduling, party booking, and integration with Start.gg API.

**Tech Stack:** PHP 7.4+, MySQL/MariaDB, Vanilla JavaScript (ES6+), Node.js components, Discord.js, Express.js

---

## Build & Development Commands

### CSS Compilation
```bash
# CSS minification (using clean-css-cli)
npm run minify-css

# CSS linting and auto-fix
npm run lint:css
```

**Note:** The `npm run build:css` command is defined in package.json but the build-css.js script does not exist. CSS optimization currently uses clean-css-cli for minification.

### Image Optimization
```bash
# Convert images to WebP (PHP script)
php scripts/convert-images-to-webp.php

# Alternative Node.js script (if available)
node scripts/convert-images.js
```

**Note:** The `npm run optimize:images` command references a non-existent optimize-images.js file. Use the PHP script in scripts/ directory instead.

### Database Migrations
```bash
# Core admin tables
mysql -u root -p bakersfield_db < admin/includes/mysql_schema.sql

# Start.gg integration tables
mysql -u root -p bakersfield_db < admin/startgg/migrations/001_create_startgg_tables.sql

# Or use PHP setup wizard (recommended):
# Upload STARTGG_QUICK_INSTALL.php to web root, visit in browser
```

### PHP Dependencies
```bash
# Install Stripe SDK (for party booking payments)
cd rates-parties
composer install
```

---

## Architecture & Key Concepts

### PHP Templating System

**All pages use reusable components:**
```php
<?php require_once 'includes/head.php'; ?>      // <head> tag, meta, CSS, analytics
<?php require_once 'includes/nav.php'; ?>       // Navigation menu
<!-- Page content -->
<?php require_once 'includes/footer-content.php'; ?> // Footer
```

**CRITICAL:** When creating/editing pages:
- Never duplicate head/nav/footer code
- Use the includes system
- Pass variables to components via scope (they execute in-place)

### Database Connection Pattern

**Always use centralized connection:**
```php
require_once __DIR__ . '/admin/includes/db.php';

// $pdo is now available (PDO instance)
$stmt = $pdo->prepare("SELECT * FROM users WHERE id = ?");
$stmt->execute([$userId]);
```

**Never create new PDO instances** - use the global `$pdo` from `db.php`.

### Environment Configuration

**Two-tier environment system:**

1. **`.env` file** - Server-level configuration (database, API keys)
2. **`includes/config.php`** - Application constants (site name, URLs, analytics IDs)

**Load order:**
```php
require_once 'includes/secure-config.php';  // Loads .env variables
require_once 'includes/config.php';         // Defines constants
```

### Start.gg Integration Architecture

**Five-layer system:**
```
StartGGClient.php          → GraphQL API communication
StartGGConfig.php          → Credential management (encrypted storage)
TournamentSync.php         → Orchestrates sync operations
TournamentRepository.php   → Database CRUD operations
Display Components         → Frontend rendering
```

**Database tables:**
- `startgg_config` - API credentials (encrypted), sync settings
- `startgg_tournaments` - Tournament metadata (30+ fields)
- `startgg_events` - Events within tournaments
- `startgg_entrants` - Participant data
- `startgg_sync_log` - Sync history/errors

**Key files:**
- `admin/startgg/` - Admin dashboard for sync management
- `admin/startgg/cron/sync_tournaments.php` - Automated sync (cron job)
- `includes/startgg/` - Core integration classes
- `includes/startgg_tournaments_display.php` - Tournament card display

**API Authentication:**
- Token stored **encrypted** in database using AES-256-CBC
- Encryption key: `SESSION_ENCRYPTION_KEY` from `.env`
- Never store token in plain text

### Admin Authentication Flow

**Session security features:**
- IP address binding
- User-agent fingerprinting
- 30-minute timeout
- Session hijacking detection

**Authentication check pattern:**
```php
require_once __DIR__ . '/includes/auth.php';
requireAdmin(); // Dies if not authenticated

// Now safe to proceed with admin operations
```

**NEVER bypass authentication** - always use `requireAdmin()` on admin pages.

### Event Management System

**Dual-source events:**
1. **Local Events** - JSON-based (`events/data/events.json`)
2. **Start.gg Tournaments** - Database-driven (auto-synced)

**Display pages:**
- `events/index.php` - Current events page with Start.gg integration
- `events/index_with_startgg.php` - Alternative version
- Multiple backup files exist (`.backup-*` extensions)

**Admin panels:**
- `events/admin/` - Local events CRUD
- `admin/startgg/` - Tournament sync management

### API & Caching Architecture

**File-based caching pattern:**
All external API integrations use JSON file caching to reduce API calls and improve performance:

```php
// Standard caching pattern
$CACHE_FILE = __DIR__ . '/../cache/resource-name.json';
$CACHE_MAX_AGE = 86400; // 24 hours

// Check cache validity
if (file_exists($CACHE_FILE) && (time() - filemtime($CACHE_FILE)) < $CACHE_MAX_AGE) {
    // Return cached data
} else {
    // Fetch fresh data, update cache
}
```

**Cache locations:**
- `cache/ggleap-stats.json` - GGLeap gaming statistics
- Database caching for Start.gg tournaments (not file-based)

**Manual cache refresh endpoints:**
- `api/update-ggleap-stats.php` - Force GGLeap stats refresh
- `api/update-startgg-events.php` - Force Start.gg events refresh
- `api/update-top-games.php` - Update popular games list

### CSS Architecture

**No SASS/SCSS** - uses modular CSS with direct imports:
```css
/* CSS files in css/ directory */
core.css        /* Variables, base styles */
components.css  /* UI components */
layout.css      /* Layout utilities */
custom.css      /* Site-specific */
```

**Build process:**
```bash
# Minify CSS files
npm run minify-css

# Lint and auto-fix CSS
npm run lint:css
```

**When editing CSS:**
- Edit source files in `css/`
- Run `npm run minify-css` to generate minified output
- Use `npm run lint:css` to check formatting (Stylelint)
- Check `STYLEGUIDE.md` for brand guidelines

**Output:** Minified files in `css/min/`

**Note:** The build system does not use @import combining. Each CSS file is standalone.

---

## Critical Integration Details

### Start.gg GraphQL API

**Endpoint:** `https://api.start.gg/gql/alpha`

**Rate limiting:** 100ms delay between requests (enforced in `StartGGClient.php`)

**Common queries:**
- `getTournamentsByOwner($ownerId)` - Fetch user's tournaments
- `getTournamentDetails($slug)` - Get specific tournament
- `getUpcomingTournaments($ownerId, $days)` - Filter by date range

**Authentication:**
```php
$client = new StartGGClient($apiToken);
// Token retrieved from encrypted database storage
```

### GGLeap API Integration

**Purpose:** Display live gaming statistics from GGLeap management system

**API Endpoints:**
- `api/ggleap-stats.php` - Cached stats API (24-hour cache)
- `api/update-ggleap-stats.php` - Manual cache update endpoint
- `api/update-ggleap-stats-simple.php` - Simplified update script

**Configuration:**
```env
# Add to .env file (if using live GGLeap API)
GGLEAP_API_KEY=your_api_key_here
GGLEAP_CENTER_ID=your_center_id
GGLEAP_API_URL=https://api.ggleap.com/v1/
```

**Cache system:**
- Stats cached in `cache/ggleap-stats.json`
- Auto-refreshes daily (86400 seconds)
- Fallback values used if API unavailable
- Manual updates supported for non-API setups

**Setup guide:** See `GGLEAP_API_SETUP.md` for full integration instructions.

### Discord Integration

**Configuration:**
```env
DISCORD_TOKEN=your_discord_bot_token_here
DISCORD_WEBHOOK_URL=https://discord.com/api/webhooks/YOUR_ID/YOUR_TOKEN
```

**Used for:**
- Party booking notifications (via webhook in rates-parties/)
- Event announcements (manual or via future bot integration)

### Security Components

**Session Manager** (`includes/security/session_manager.php`):
- AES-256-CBC encryption
- Fingerprint validation
- Timeout enforcement

**Encryption Class** (`includes/security/Encryption.php`):
- Used for API tokens, OAuth secrets
- Key stored in `SESSION_ENCRYPTION_KEY` environment variable

**Activity Logger** (`admin/includes/AdminLogger.php`):
- Logs all admin actions
- Security event tracking
- Database + file logging

**Security Monitoring** (`admin/security_dashboard.php`):
- Failed login attempts
- Session hijacking detections
- IP-based access patterns

---

## Common Patterns & Gotchas

### Schema.org Structured Data

**Every page should include:**
```php
<?php
require_once 'includes/schemas.php';
echo generateOrganizationSchema();
echo generateWebPageSchema('Page Title', 'Page description');
?>
```

**Available schemas:**
- `generateOrganizationSchema()` - Organization details
- `generateLocalBusinessSchema()` - Business location
- `generateEventSchema($eventData)` - Individual events
- `generateWebPageSchema($title, $description)` - Page metadata

### Image Helpers

**Always use responsive images:**
```php
<?php require_once 'includes/image-helpers.php'; ?>

<?= generateResponsiveImage(
    'images/hero.jpg',
    'Alt text',
    ['large' => '1200w', 'medium' => '800w', 'small' => '400w']
); ?>
```

**Benefits:**
- Automatic srcset generation
- WebP/AVIF fallbacks
- Lazy loading
- SEO-friendly alt tags

### Modal System

**Tournament details modal:**
```javascript
// Frontend: events/js/tournament-modal.js
window.tournamentModal.open('tournament-slug');

// Fetches from: api/tournament-details.php
// Displays in: tournament-modal.css styled modal
```

**Party booking modal:**
```javascript
// Frontend: js/party-booking.js
// Backend: rates-parties/ (Stripe integration)
```

### AJAX Endpoints

**Pattern for admin AJAX:**
```php
// admin/startgg/ajax/endpoint.php
session_start();
require_once '../../includes/auth.php';
requireAdmin(); // CRITICAL - prevent unauthorized access

header('Content-Type: application/json');

try {
    // Handle request
    echo json_encode(['success' => true, 'data' => $result]);
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
}
```

### Cron Jobs

**Start.gg tournament sync (every 30 minutes):**
```bash
*/30 * * * * /usr/bin/php /path/to/admin/startgg/cron/sync_tournaments.php >> /path/to/logs/sync.log 2>&1
```

**GGLeap stats update (daily):**
```bash
0 2 * * * /usr/bin/php /path/to/api/update-ggleap-stats.php >> /path/to/logs/ggleap.log 2>&1
```

**Note:** GGLeap stats auto-update when cache expires (24 hours), so cron is optional.

---

## File Locations Quick Reference

### Critical Configuration Files
- `.env` - Database, API keys, secrets (NEVER commit)
- `includes/config.php` - Site constants, URLs, social links
- `admin/includes/db.php` - Database connection
- `includes/secure-config.php` - Environment loader

### Admin System
- `admin/login.php` - Admin login
- `admin/dashboard.php` - Main dashboard
- `admin/includes/auth.php` - Authentication logic
- `admin/includes/User.php` - User model

### Start.gg Integration
- `admin/startgg/index.php` - Sync dashboard
- `admin/startgg/cron/sync_tournaments.php` - Auto-sync script
- `includes/startgg/StartGGClient.php` - API client
- `includes/startgg/TournamentSync.php` - Sync orchestrator

### Events Management
- `events/index.php` - Events listing (current page)
- `events/admin/` - Event management panel
- `events/data/events.json` - Event data storage
- `events/js/tournament-modal.js` - Modal functionality

### Templates & Components
- `includes/head.php` - HTML head template
- `includes/nav.php` - Navigation menu
- `includes/footer-content.php` - Footer
- `includes/startgg_tournaments_display.php` - Tournament cards

### Frontend Assets
- `css/*.css` - Source CSS files (edit these)
- `css/min/styles.min.css` - Minified output (generated, don't edit)
- `js/main.js` - JavaScript entry point
- `js/modules/` - Modular JS components
- `events/js/tournament-modal.js` - Tournament modal functionality

### API Endpoints
- `api/ggleap-stats.php` - GGLeap statistics (cached)
- `api/top-games.php` - Popular games listing
- `api/tournament-details.php` - Start.gg tournament details
- `api/startgg-events.php` - Start.gg events feed
- `api/update-*.php` - Manual cache update scripts

### External Services
- `rates-parties/` - Party booking with Stripe integration
- Discord webhooks - Party booking notifications

---

## Archived OAuth System

**Location:** `archive/oauth-stripe-system-2025-10-28/`

A complete OAuth + Stripe payment system was built but archived due to Start.gg API limitations. The system includes:
- OAuth 2.0 authentication with Start.gg
- Stripe payment processing
- Database tracking of registrations
- Professional payment UI

**Why archived:** Start.gg's GraphQL API doesn't support tournament registration mutations via OAuth.

**Current approach:** Display tournaments, link directly to Start.gg for registration.

**Restoration:** See `archive/oauth-stripe-system-2025-10-28/README.md` if needed.

---

## Development Environment Notes

### XAMPP Configuration
- Web root: `C:\xampp\htdocs\bakersfield`
- Database: MySQL/MariaDB on localhost
- PHP version: 7.4+
- Node.js version: 20.x (specified in package.json)

### Local vs Production
- Local: `http://127.0.0.1/bakersfield/` or `http://localhost/bakersfield/`
- Production: `https://bakersfieldesports.com/`
- Environment auto-detected based on hostname
- Use `.env` to set explicit `ENVIRONMENT=local` or `ENVIRONMENT=production`

### Directory Structure
The repository is organized as follows:
- Root directory contains the active website files
- `admin/` - Admin panel and authentication
- `events/` - Event management system
- `includes/` - Shared PHP components and libraries
- `api/` - RESTful API endpoints
- `css/`, `js/` - Frontend assets
- `scripts/` - Build and utility scripts
- `archive/` - Archived code (OAuth system, etc.)

**Note:** References to `public_html/` or multiple directory structures in older documentation may not apply to current setup.

---

## Testing Considerations

**No automated test suite exists.** When making changes:

1. **Manual Testing Checklist:**
   - Test in both logged-in and logged-out states
   - Verify admin panel access controls
   - Check mobile responsiveness
   - Test Start.gg tournament display
   - Verify Discord bot sync (if events changed)

2. **Database Changes:**
   - Always backup before schema changes
   - Test migrations on local before production
   - Update both MySQL and SQLite schemas if maintained

3. **CSS Changes:**
   - Edit source files in `css/`
   - Run `npm run minify-css` to regenerate minified output
   - Run `npm run lint:css` to check formatting
   - Clear browser cache
   - Test mobile viewport

4. **JavaScript Changes:**
   - Check browser console for errors
   - Test in Chrome, Firefox, Safari, Edge
   - Verify analytics tracking still works

---

## Deployment Checklist

When deploying to production:

1. **Environment Configuration:**
   - Set `ENVIRONMENT=production` in `.env`
   - Update database credentials
   - Use production API tokens (Start.gg, Discord)
   - Enable HTTPS redirects in `.htaccess`

2. **Build Assets:**
   ```bash
   npm run minify-css
   npm run lint:css
   php scripts/convert-images-to-webp.php  # Image optimization
   ```

3. **Security:**
   - Rotate `ADMIN_INIT_PASSWORD`
   - Verify `.env` is not publicly accessible (`.htaccess` rules)
   - Check session security settings
   - Enable error logging to files (not display)

4. **Cron Jobs:**
   - Set up Start.gg sync: `*/30 * * * * php admin/startgg/cron/sync_tournaments.php`
   - Optional GGLeap stats update: `0 2 * * * php api/update-ggleap-stats.php`

5. **Testing:**
   - Verify Start.gg tournaments display
   - Test admin login
   - Check SSL certificate
   - Confirm analytics tracking
   - Test forms (contact, party booking)

6. **Monitoring:**
   - Check `admin/startgg/logs/` for sync errors
   - Review `admin/includes/logs/` for admin activity
   - Monitor `cache/ggleap-stats.json` for GGLeap data freshness
   - Watch database size (gallery images can grow large)

---

## Support Resources

### External APIs & Services
- **Start.gg API Docs:** https://developer.start.gg/docs/intro
- **Discord.js Guide:** https://discordjs.guide/
- **Stripe PHP SDK:** https://github.com/stripe/stripe-php

### Internal Documentation
- `GGLEAP_API_SETUP.md` - GGLeap integration setup guide
- `GGLEAP_INTEGRATION_GUIDE.md` - Detailed GGLeap implementation
- `STYLEGUIDE.md` - CSS and brand guidelines
- `SECURITY_SETUP.md` - Security features configuration
- `IMAGE_OPTIMIZATION_GUIDE.md` - Image processing instructions
- `REGISTRATION_IMPLEMENTATION_PLAN.md` - Tournament registration planning
- `SESSION_PROGRESS_2025-10-28.md` - Session work summary
- `admin/README.md` - Admin panel documentation
- `admin/startgg/README.md` - Start.gg integration details
