# CLAUDE.md

This file provides guidance to Claude Code (claude.ai/code) when working with code in this repository.

## Project Overview

Bakersfield eSports Center website - a full-stack PHP/JavaScript application for an esports gaming venue featuring tournament management, event scheduling, party booking, and integration with Start.gg API.

**Tech Stack:** PHP 7.4+, MySQL/MariaDB, Vanilla JavaScript (ES6+), Node.js components, Discord.js, Express.js

---

## Build & Development Commands

### CSS Compilation
```bash
# Build optimized CSS from main.css (combines @imports, minifies)
npm run build:css

# Output: css/optimized.min.css (14.92 KB from ~22.8 KB source)
```

### Image Optimization
```bash
# Convert images to WebP/AVIF, generate multiple sizes
npm run optimize:images

# Or specify directory:
node optimize-images.js images/gallery/
```

### Discord Bot (Event Synchronization)
```bash
# Run the Discord bot (syncs events to Discord server)
cd DiscordEventBot
node bot.js

# Bot runs continuously, syncs every 30 minutes
```

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
- `events/index.php` - Local events only (original)
- `events/index_with_startgg.php` - Both sources combined (current)

**Admin panels:**
- `events/admin/` - Local events CRUD
- `admin/startgg/` - Tournament sync management

### CSS Architecture

**No SASS/SCSS** - uses CSS @import system:
```css
/* main.css - master file */
@import 'core.css';       /* Variables, base styles */
@import 'components.css'; /* UI components */
@import 'layout.css';     /* Layout utilities */
@import 'custom.css';     /* Site-specific */
```

**Build process:**
1. Reads `main.css`
2. Recursively resolves all `@import` statements
3. Combines into single file
4. Minifies (removes comments/whitespace)
5. Outputs `css/optimized.min.css`

**When editing CSS:**
- Edit source files in `css/`
- Run `npm run build:css`
- **Never edit `optimized.min.css` directly**
- HTML pages load the optimized version

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

### Discord Bot Integration

**Purpose:** Auto-sync website events to Discord scheduled events

**Configuration:**
```env
DISCORD_TOKEN=your_token_here
GUILD_ID=your_server_id
EVENTS_JSON_URL=https://bakersfieldesports.com/events/data/events.json
```

**Sync process:**
1. Fetches `events.json` every 30 minutes
2. Compares with existing Discord events
3. Creates/updates/deletes as needed
4. Logs all operations to `logs/bot.log`

**Running:**
```bash
cd DiscordEventBot
node bot.js  # Runs indefinitely
```

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

**Discord bot sync:**
- Handled internally by `node-cron` in bot.js
- No system cron needed if bot runs continuously

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
- `css/optimized.min.css` - Compiled CSS (generated, don't edit)
- `css/main.css` - CSS entry point (edit this)
- `js/main.js` - JavaScript entry point
- `js/modules/` - Modular JS components

### External Services
- `DiscordEventBot/` - Discord bot (standalone Node.js app)
- `rates-parties/` - Party booking with Stripe

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
- Document root should point to `public_html/` for production

### Local vs Production
- Local: `http://127.0.0.1/bakersfield/` or `http://localhost/bakersfield/`
- Production: `https://bakersfieldesports.com/`
- Environment auto-detected based on hostname
- Use `.env` to set explicit `ENVIRONMENT=local` or `ENVIRONMENT=production`

### Multiple Directory Structures
This repository contains multiple snapshots/versions:
- `public_html/` - Main website (current version)
- `bakersfieldesports.com/` - Alternative/older version
- `test.bakersfieldesports.com/` - Test environment
- Various backup directories with dates

**Work in `public_html/` for active development.**

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
   - Run `npm run build:css`
   - Check `optimized.min.css` was regenerated
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
   npm run build:css
   npm run optimize:images
   ```

3. **Security:**
   - Rotate `ADMIN_INIT_PASSWORD`
   - Verify `.env` is not publicly accessible (`.htaccess` rules)
   - Check session security settings
   - Enable error logging to files (not display)

4. **Cron Jobs:**
   - Set up Start.gg sync: `*/30 * * * * php admin/startgg/cron/sync_tournaments.php`
   - Configure Discord bot to run as service (systemd, PM2, or screen)

5. **Testing:**
   - Verify Start.gg tournaments display
   - Test admin login
   - Check SSL certificate
   - Confirm analytics tracking
   - Test forms (contact, party booking)

6. **Monitoring:**
   - Check `admin/startgg/logs/` for sync errors
   - Monitor `DiscordEventBot/logs/bot.log`
   - Review `admin/includes/logs/` for admin activity
   - Watch database size (gallery images can grow large)

---

## Support Resources

- **Start.gg API Docs:** https://developer.start.gg/docs/intro
- **Discord.js Guide:** https://discordjs.guide/
- **Stripe PHP SDK:** https://github.com/stripe/stripe-php
- **Internal Documentation:** `START_HERE.md`, `QUICK_REFERENCE.md`
- **Session Progress:** `SESSION_PROGRESS_2025-10-28.md`
