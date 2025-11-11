# GGLeap Stats - Weekly Automatic Update Setup

This guide explains how to set up automatic weekly updates for GGLeap statistics on your web server.

---

## Option 1: Server Cron Job (Recommended - Linux/Unix Servers)

If you have SSH access to your web server, you can set up a cron job:

### Step 1: Access Your Server's Crontab

```bash
crontab -e
```

### Step 2: Add Weekly Cron Job

Add one of these lines to run the update script weekly:

```bash
# Run every Sunday at 2:00 AM
0 2 * * 0 /usr/bin/php /path/to/bakersfield/api/update-ggleap-stats-simple.php >> /path/to/bakersfield/cache/ggleap-cron.log 2>&1

# OR run every Monday at 3:00 AM
0 3 * * 1 /usr/bin/php /path/to/bakersfield/api/update-ggleap-stats-simple.php >> /path/to/bakersfield/cache/ggleap-cron.log 2>&1
```

**Important:** Replace `/path/to/bakersfield/` with your actual server path (e.g., `/home/username/public_html/`)

### Cron Schedule Format

```
* * * * *
│ │ │ │ │
│ │ │ │ └─── Day of week (0-7, Sunday = 0 or 7)
│ │ │ └───── Month (1-12)
│ │ └─────── Day of month (1-31)
│ └───────── Hour (0-23)
└─────────── Minute (0-59)
```

### Common Schedules

```bash
# Every Sunday at 2 AM
0 2 * * 0

# Every Monday at 3 AM
0 3 * * 1

# Every day at midnight (daily instead of weekly)
0 0 * * *

# Every 3 days at 2 AM
0 2 */3 * *
```

### Step 3: Verify Cron Job

List your cron jobs to verify:
```bash
crontab -l
```

### Step 4: Check Logs

Monitor the cron log file to ensure it's working:
```bash
tail -f /path/to/bakersfield/cache/ggleap-cron.log
```

---

## Option 2: cPanel Cron Jobs

If your hosting uses cPanel:

1. **Log into cPanel**
2. **Find "Cron Jobs"** under "Advanced" section
3. **Add New Cron Job:**
   - **Common Settings:** Select "Once Per Week" or customize
   - **Command:**
     ```bash
     /usr/bin/php /home/username/public_html/bakersfield/api/update-ggleap-stats-simple.php
     ```
   - **Email:** Enter your email to receive execution reports

---

## Option 3: Web-Based Cron Service (No Server Access Needed)

If you don't have SSH or cPanel access, use a free web cron service:

### Recommended Services:
- **EasyCron** (https://www.easycron.com) - Free tier: 1 cron job
- **cron-job.org** (https://cron-job.org) - Free, unlimited jobs
- **UptimeRobot** (https://uptimerobot.com) - Monitors + cron functionality

### Setup Steps:

1. **Create Account** on chosen service
2. **Add New Cron Job:**
   - **URL:** `https://bakersfieldesports.com/api/update-ggleap-stats-simple.php`
   - **Schedule:** Weekly (every Sunday at 2 AM, for example)
   - **Method:** GET request
3. **Set Notifications:** Email alerts if the update fails

### Security Note:
The update script is currently publicly accessible. If you want to restrict access, see the security section below.

---

## Option 4: WordPress Cron (If Using WordPress)

If your site uses WordPress, you can use WP-Cron:

1. Install **WP Crontrol** plugin
2. Add new cron event:
   - **Hook name:** `ggleap_weekly_update`
   - **Schedule:** Weekly
3. Add this to your theme's `functions.php`:

```php
add_action('ggleap_weekly_update', function() {
    $url = get_site_url() . '/api/update-ggleap-stats-simple.php';
    wp_remote_get($url, ['timeout' => 120]);
});
```

---

## Security: Protecting the Update Script

To prevent unauthorized access to your update endpoint:

### Method 1: Add Secret Token

1. **Add to `.env` file:**
   ```env
   GGLEAP_UPDATE_SECRET=your_random_secret_key_here_12345
   ```

2. **Modify update script** (at the top, after `<?php`):
   ```php
   // Check for secret token
   $expectedSecret = getenv('GGLEAP_UPDATE_SECRET');
   $providedSecret = $_GET['secret'] ?? '';

   if ($expectedSecret && $providedSecret !== $expectedSecret) {
       http_response_code(403);
       die('Unauthorized');
   }
   ```

3. **Update cron command/URL:**
   ```bash
   # Server cron:
   /usr/bin/php /path/to/api/update-ggleap-stats-simple.php

   # Web cron URL:
   https://bakersfieldesports.com/api/update-ggleap-stats-simple.php?secret=your_random_secret_key_here_12345
   ```

### Method 2: IP Whitelist

Add this to the top of `update-ggleap-stats-simple.php`:

```php
// Allow only specific IPs
$allowedIPs = ['127.0.0.1', 'your.server.ip.address'];
$clientIP = $_SERVER['REMOTE_ADDR'] ?? '';

if (!in_array($clientIP, $allowedIPs)) {
    http_response_code(403);
    die('Access denied');
}
```

---

## Monitoring & Troubleshooting

### Check if Updates are Running

1. **View cache file modification time:**
   - Check: `/cache/ggleap-stats.json`
   - Should update weekly

2. **Check log files:**
   - `/cache/ggleap-update.log` - PHP script logs
   - `/cache/ggleap-cron.log` - Cron execution logs

3. **View last update timestamp:**
   - Open `cache/ggleap-stats.json`
   - Check `last_updated` field

### Common Issues

**Issue:** Script times out
- **Solution:** Increase PHP timeout in cron command:
  ```bash
  php -d max_execution_time=300 /path/to/update-ggleap-stats-simple.php
  ```

**Issue:** Permission denied
- **Solution:** Make script executable:
  ```bash
  chmod +x /path/to/api/update-ggleap-stats-simple.php
  ```

**Issue:** Cron not running
- **Solution:** Check cron service is running:
  ```bash
  sudo service cron status
  ```

---

## Current Cache Settings

The current cache settings in `api/ggleap-stats.php`:

```php
$CACHE_MAX_AGE = 86400; // 24 hours (daily refresh)
```

**For weekly updates**, you may want to increase this:

```php
$CACHE_MAX_AGE = 604800; // 7 days (weekly refresh)
```

This prevents the auto-refresh system from triggering daily updates when you only want weekly updates.

---

## Recommended Setup

**Best practice for production:**

1. ✅ Use **server cron job** (Option 1) if you have SSH access
2. ✅ Run **weekly on Sunday at 2 AM** (low traffic time)
3. ✅ Add **secret token** for security
4. ✅ Set up **email notifications** for failures
5. ✅ Monitor logs weekly to ensure success

---

## Quick Start (Most Common Setup)

For most hosting providers with cPanel:

1. Log into **cPanel**
2. Go to **Cron Jobs**
3. Add this command:
   ```
   /usr/bin/php /home/yourusername/public_html/api/update-ggleap-stats-simple.php
   ```
4. Set schedule: **Weekly** or **Custom: `0 2 * * 0`** (Sunday 2 AM)
5. Save

Done! Your stats will update automatically every week.

---

## Questions?

- **Where is my server path?** Contact your hosting provider or check cPanel File Manager
- **Which PHP path?** Try `/usr/bin/php` or `/usr/local/bin/php` (common locations)
- **Can't access cron?** Use Option 3 (Web-Based Cron Service)

For more help, consult your hosting provider's documentation on setting up cron jobs.
