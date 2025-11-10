# Start.gg Integration

## Quick Start

### Option 1: Web-Based Setup (Recommended)
1. Visit: `https://your-domain.com/admin/startgg/setup/`
2. Follow the setup wizard
3. Done!

### Option 2: Command Line Setup
```bash
cd public_html/admin/startgg/setup
php install.php
```

---

## What's Included

This integration provides:
- ✅ Automatic tournament sync from start.gg
- ✅ Tournament details pages with rules, brackets, entrants
- ✅ Direct registration integration (OAuth)
- ✅ Admin dashboard for managing tournaments
- ✅ Secure encrypted API token storage
- ✅ Real-time entrant counts and registration status

---

## File Structure

```
admin/startgg/
├── setup/
│   ├── index.php          # Web setup wizard
│   └── install.php        # CLI setup script
├── migrations/
│   └── 001_create_startgg_tables.sql
├── index.php              # Dashboard (coming soon)
├── settings.php           # Settings page (coming soon)
└── README.md              # This file

includes/startgg/
├── StartGGClient.php      # GraphQL API client
├── StartGGConfig.php      # Configuration manager
├── TournamentSync.php     # Sync system (coming soon)
└── TournamentRepository.php  # Database operations (coming soon)
```

---

## Database Tables Created

1. **startgg_config** - API configuration and settings
2. **startgg_tournaments** - Tournament data
3. **startgg_events** - Events within tournaments
4. **startgg_entrants** - Participant data
5. **startgg_sync_log** - Sync operation logs

---

## Configuration

### API Token
- Stored encrypted in database
- Never exposed in frontend code
- Auto-expires after 1 year (start.gg policy)
- Get yours at: https://start.gg/admin/profile/developer

### Owner ID
- Your start.gg user/organization ID
- Used to fetch your tournaments
- Configured: `6e4bd725`

### Sync Settings
- Default interval: 30 minutes
- Can be changed in admin settings
- Manual sync available via dashboard

---

## Security Features

1. **Encryption**
   - API token encrypted with AES-256-CBC
   - Encryption key stored outside web root
   - Key auto-generated on first run

2. **Authentication**
   - All admin pages require login
   - CSRF protection on all forms
   - Session validation

3. **Rate Limiting**
   - 100ms delay between API calls
   - Prevents hitting start.gg rate limits
   - Configurable if needed

---

## Next Steps (In Development)

### Phase 2: Tournament Sync (Next)
- Automatic tournament fetching
- Scheduled sync via cron
- Manual sync button
- Sync status display

### Phase 3: Tournament Display
- Tournament details page
- Entrants list
- Brackets display
- Registration buttons

### Phase 4: OAuth Registration
- Direct registration from your site
- User authentication flow
- Registration confirmation

### Phase 5: Admin Interface
- Dashboard with statistics
- Tournament management
- Sync logs
- Settings page

---

## API Documentation

### StartGGClient Methods

```php
$client = new StartGGClient($apiToken);

// Get tournaments by owner
$tournaments = $client->getTournamentsByOwner($ownerId, $page, $perPage);

// Get tournament details
$tournament = $client->getTournamentDetails($slug);

// Get event entrants
$entrants = $client->getEventEntrants($eventId, $page, $perPage);

// Get upcoming tournaments only
$upcoming = $client->getUpcomingTournaments($ownerId);

// Get public tournaments only
$public = $client->getPublicTournaments($ownerId);

// Check registration status
$status = $client->getRegistrationStatus($slug);

// Test connection
$user = $client->getCurrentUser();
```

### StartGGConfig Methods

```php
$config = new StartGGConfig($pdo);

// Save/get API token
$config->saveApiToken($token);
$token = $config->getApiToken();

// Get configuration
$ownerId = $config->getOwnerId();
$settings = $config->getConfig();

// Update settings
$config->updateSettings(['sync_interval' => 60]);

// Test connection
$result = $config->testConnection();

// Sync tracking
$config->updateLastSync();
$isEnabled = $config->isSyncEnabled();
```

---

## Troubleshooting

### "API token not configured"
- Run setup: `/admin/startgg/setup/`
- Or manually save token via admin panel

### "Connection failed"
- Check API token is correct
- Verify internet connection
- Check start.gg API status

### "Tables not found"
- Run database migration: `/admin/startgg/setup/`
- Check database user has CREATE TABLE permissions

### "Rate limit exceeded"
- Reduce sync frequency
- Check for duplicate cron jobs
- Wait a few minutes and retry

---

## Support

- Documentation: `/admin/startgg/docs/`
- Implementation Plan: `/STARTGG_INTEGRATION_PLAN.md`
- Configuration: `/STARTGG_CONFIG.md`
- Logs: Check `startgg_sync_log` table

---

## Version

**Current:** Phase 1 Complete (Database + API Client)
**Next:** Phase 2 (Tournament Sync)
**Target:** Full integration (34 hours estimated)

Last Updated: 2025-10-08
