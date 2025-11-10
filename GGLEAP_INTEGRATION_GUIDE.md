# GGLeap Live Stats Integration - Complete Setup Guide
**Bakersfield eSports - October 2025**

---

## 🎯 Overview

Your GGLeap integration is now set up for **5,000+ users** with an optimized daily update system.

### What's Included:
- ✅ Fast stats endpoint (instant response)
- ✅ Background updater script (handles all API calls)
- ✅ Detailed logging
- ✅ Error handling and fallback data
- ✅ Progress tracking for long-running updates

---

## 📋 Setup Steps

### Step 1: Add Your API Token

Edit `/api/update-ggleap-stats.php` line 28:

```php
'auth_token' => 'YOUR_TOKEN_KEY_HERE',  // ← Replace with your actual token
```

**Where to find your token:**
- Log into your GGLeap/ggCircuit dashboard
- Go to Settings → API or Developer Tools
- Copy your AuthToken/API Token Key
- Paste it into the config

---

### Step 2: Test the Connection

Run the updater script manually to test:

**Option A: Via Browser**
```
http://localhost/bakersfield/api/update-ggleap-stats.php
```

**Option B: Via Command Line**
```bash
cd C:\xampp\htdocs\bakersfield\api
php update-ggleap-stats.php
```

**What to expect:**
- ⏱️ **Duration:** 20-60 minutes for 5,000+ users
- 📊 **Progress:** Updates every 50 users in the log
- ✅ **Success:** Creates `/cache/ggleap-stats.json`

---

### Step 3: Check the Logs

View the update log:
```
C:\xampp\htdocs\bakersfield\cache\ggleap-update.log
```

Log shows:
- Authentication status
- User count per page
- Progress percentage
- Final statistics
- Processing time

---

### Step 4: Verify Stats Display

Visit your site:
```
http://localhost/bakersfield/
```

Scroll to the stats section and verify:
- Numbers updated with real data
- Green "LIVE" indicators showing
- No fallback message

---

## 🤖 Automated Daily Updates

### Option 1: Windows Task Scheduler

1. Open **Task Scheduler** (Windows key + search "Task Scheduler")
2. Click **Create Basic Task**
3. Name: "GGLeap Stats Update"
4. Trigger: **Daily** at 3:00 AM
5. Action: **Start a program**
   - Program: `C:\xampp\php\php.exe`
   - Arguments: `C:\xampp\htdocs\bakersfield\api\update-ggleap-stats.php`
6. Finish and test

### Option 2: Manual Cron (if using Linux)

Add to crontab:
```bash
0 3 * * * php /path/to/bakersfield/api/update-ggleap-stats.php
```

---

## 🔄 Manual Updates

### Update Stats Anytime:

**Method 1: Web Button**

I can add an admin button to your site that triggers updates.

**Method 2: Direct URL**

Visit:
```
http://localhost/bakersfield/api/update-ggleap-stats.php
```

**Method 3: Command Line**

```bash
php C:\xampp\htdocs\bakersfield\api\update-ggleap-stats.php
```

---

## 📊 Stats Collected

### 1. Total Registered Accounts
- Source: `/users/summaries` (all pages)
- Updates: Daily
- Display: Exact count

### 2. New Players This Week
- Source: `/activity-logs/search` with `TimeFrameType=ThisWeek`
- Updates: Daily
- Display: Count of unique users created this week
- **Note:** Currently showing weekly (not daily) - label on frontend will be updated

### 3. Total Gaming Hours (Since October 1, 2021)
- Source: `/sessions/get-user-sessions` for each user
- Calculation: Sum all session seconds ÷ 3600
- Updates: Daily
- Display: Integer hours with "+" suffix

### 4. Gaming Hours This Month
- Source: `/sessions/get-user-sessions` for current month
- Calculation: Sum current month seconds ÷ 3600
- Updates: Daily
- Display: Integer hours

---

## ⚙️ Configuration Options

All settings in `/api/update-ggleap-stats.php`:

### API Settings (Lines 19-32)

```php
$GGLEAP_CONFIG = [
    'auth_token' => 'YOUR_TOKEN_HERE',
    'api_base_url' => 'https://api.ggleap.com/production',
    'opening_date' => '2021-10-01T00:00:00Z',  // Your opening date
    'batch_size' => 100,      // Users processed per batch
    'max_users' => 5000,      // Safety limit
    'request_delay' => 100000 // 100ms delay between API calls
];
```

### Tuning for Performance:

**If updates are too slow:**
- Decrease `request_delay` to `50000` (50ms)
- Increase `batch_size` to `200`
- ⚠️ Watch for API rate limits

**If hitting rate limits:**
- Increase `request_delay` to `200000` (200ms)
- Decrease `batch_size` to `50`

---

## 🐛 Troubleshooting

### Issue: Authentication Failed

**Check:**
1. Token is correct (no extra spaces)
2. Token is active in GGLeap dashboard
3. API base URL is correct (`/production` vs `/beta`)

**View logs:**
```
cat C:\xampp\htdocs\bakersfield\cache\ggleap-update.log
```

---

### Issue: Script Times Out

**Solution 1:** Increase PHP timeout

Edit `update-ggleap-stats.php` line 16:
```php
set_time_limit(3600); // 1 hour
```

**Solution 2:** Process fewer users per run

Edit line 31:
```php
'max_users' => 1000, // Process only 1000 users
```

---

### Issue: Stats Not Showing on Site

**Check:**
1. Cache file exists:
   ```
   C:\xampp\htdocs\bakersfield\cache\ggleap-stats.json
   ```

2. Cache file is readable:
   ```php
   php -r "var_dump(json_decode(file_get_contents('cache/ggleap-stats.json')));"
   ```

3. Frontend JS is loading:
   ```
   F12 → Console → Look for "BakersFieldStats"
   ```

---

### Issue: "Using Fallback Data" Message

**Causes:**
- Update script hasn't run yet
- Authentication failed
- Cache file missing or corrupt

**Solution:**
Run the update script manually and check logs.

---

## 📁 File Structure

```
bakersfield/
├── api/
│   ├── ggleap-stats.php           ← Fast endpoint (serves cache)
│   └── update-ggleap-stats.php    ← Background updater (runs daily)
├── cache/
│   ├── ggleap-stats.json          ← Stats cache (updated daily)
│   └── ggleap-update.log          ← Update logs
├── js/
│   └── stats-counter.js           ← Frontend animations
└── css/
    └── stats-section.css          ← Stats styling
```

---

## 🔒 Security Notes

### Protect Your Token:
- ✅ Token stored server-side only
- ✅ Never exposed in HTML/JavaScript
- ✅ File permissions: 644 or 640

### Restrict Update Script:

Add to top of `update-ggleap-stats.php`:

```php
// Only allow localhost or specific IPs
$allowed_ips = ['127.0.0.1', '::1'];
if (!in_array($_SERVER['REMOTE_ADDR'], $allowed_ips)) {
    die('Forbidden');
}
```

---

## 📈 Performance Stats

**For 5,000 users:**
- API requests: ~10,050 (users + sessions × 2)
- Estimated time: 20-40 minutes
- Memory usage: ~50-100 MB
- Cache file size: ~1 KB

**For 10,000 users:**
- Estimated time: 40-80 minutes
- Consider breaking into multiple runs

---

## 🎯 Frontend Updates Needed

The stats section currently shows "New Players Today" but the API returns weekly data.

### Update Label:

Edit `index.html` around line 540:

**Change:**
```html
<div class="stat-label">New Players Today</div>
```

**To:**
```html
<div class="stat-label">New Players This Week</div>
```

---

## 🔄 Cache Management

### View Current Cache:

```bash
cat C:\xampp\htdocs\bakersfield\cache\ggleap-stats.json
```

### Clear Cache:

```bash
del C:\xampp\htdocs\bakersfield\cache\ggleap-stats.json
```

### Cache Format:

```json
{
  "totalAccounts": 5234,
  "newAccountsToday": 18,
  "totalHours": 125000,
  "hoursThisMonth": 4500,
  "timestamp": 1730000000,
  "using_fallback": false,
  "last_updated": "2025-10-26 03:00:00",
  "processing_time_minutes": 35.5
}
```

---

## ✅ Post-Setup Checklist

- [ ] API token added to config
- [ ] Test run completed successfully
- [ ] Cache file created
- [ ] Stats displaying on site
- [ ] Daily update scheduled
- [ ] Logs are being written
- [ ] Frontend label updated to "This Week"
- [ ] Tested manual refresh button

---

## 🆘 Need Help?

### Check These Files:
1. Update log: `/cache/ggleap-update.log`
2. PHP error log: `C:\xampp\apache\logs\error.log`
3. Browser console: F12 → Console

### Common Issues:
- **"Authentication failed"** → Check token
- **"Timeout"** → Increase `set_time_limit()`
- **"No users fetched"** → Check API base URL
- **"Cache expired"** → Run update script

---

## 📞 GGLeap API Info

**Base URL:** `https://api.ggleap.com/production`

**Key Endpoints Used:**
- `POST /authorization/public-api/auth` - Get JWT token
- `GET /users/summaries` - List all users
- `GET /activity-logs/search` - Find new accounts
- `GET /sessions/get-user-sessions` - Get user sessions

**Rate Limits:**
- Unknown - configured with 100ms delays to be safe
- Adjust `request_delay` if you know your limits

---

## 🚀 Next Steps

1. **Add your token** to the config
2. **Run first update** manually
3. **Set up daily schedule** (Task Scheduler)
4. **Update frontend label** to "This Week"
5. **Monitor logs** for the first few days

---

## 📝 Quick Command Reference

```bash
# Run update script
php C:\xampp\htdocs\bakersfield\api\update-ggleap-stats.php

# View stats cache
type C:\xampp\htdocs\bakersfield\cache\ggleap-stats.json

# View update logs
type C:\xampp\htdocs\bakersfield\cache\ggleap-update.log

# Clear cache
del C:\xampp\htdocs\bakersfield\cache\ggleap-stats.json

# Test stats endpoint
curl http://localhost/bakersfield/api/ggleap-stats.php
```

---

**Your stats will update automatically every day! 🎮**

*Last updated: October 26, 2025*
