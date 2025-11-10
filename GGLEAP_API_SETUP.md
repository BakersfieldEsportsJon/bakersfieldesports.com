# GGLeap API Integration Setup Guide
**Bakersfield eSports - Simple Setup**

---

## 🎯 What I Need From You

To connect your live GGLeap stats, I need the following information:

### **Required Information:**

1. **GGLeap API Key** (or API Token)
   - Where to find: GGLeap Dashboard → Settings → API or Integrations
   - Looks like: `sk_live_xxxxxxxxxxxxx` or similar long string

2. **Center ID** (or Location ID)
   - Where to find: GGLeap Dashboard → Your center/location settings
   - Looks like: A number or code identifying your specific location

3. **API Documentation Access**
   - Do you have access to GGLeap's API documentation?
   - URL would be something like: `docs.ggleap.com/api` or similar

---

## 📋 Information Checklist

Please provide:

- [ ] **API Key:** `___________________________________`
- [ ] **Center ID:** `___________________________________`
- [ ] **API Base URL:** (usually `https://api.ggleap.com/v1/` or similar)
- [ ] **API Documentation Link:** `___________________________________`

---

## 🔍 Where to Find This Information

### **Method 1: GGLeap Dashboard**

1. Log into your GGLeap dashboard at: `https://ggleap.com/login` (or your specific URL)
2. Go to **Settings** or **Configuration**
3. Look for:
   - **API Settings**
   - **Integrations**
   - **Developer Tools**
   - **API Keys**
4. Copy the API Key shown there

### **Method 2: Contact GGLeap Support**

If you can't find the API settings:

**Email:** support@ggleap.com (or check their website)
**Request:** "API access for custom website integration"

Tell them you need:
- API key/token
- API documentation
- Endpoint URLs for:
  - Total user accounts
  - New accounts today
  - Gaming session data (total hours, monthly hours)

---

## 📊 Stats We Want to Pull

Once connected, the site will automatically display:

### **From GGLeap API:**
1. **Total Registered Accounts**
   - All-time user count

2. **New Accounts Today**
   - Accounts created in the last 24 hours

3. **Total Gaming Hours**
   - Sum of all gameplay time ever

4. **Hours Played This Month**
   - Current month's total gameplay

### **Update Frequency:**
- Auto-refreshes every 5 minutes
- Users can manually refresh with button
- Cached to reduce API calls

---

## 🛠️ What Happens After You Provide This

Once you give me the information:

**I will:**
1. Update `api/ggleap-stats.php` with your credentials ✅
2. Configure the API endpoints ✅
3. Test the connection ✅
4. Adjust data mapping if needed ✅
5. Verify stats are pulling correctly ✅

**Time needed:** ~15-30 minutes

**You'll see:**
- Real numbers instead of fallback values
- Green "LIVE" indicators
- Stats updating automatically
- Fresh data every 5 minutes

---

## 🔒 Security Notes

**Your API key will be:**
- ✅ Stored server-side only (in PHP file)
- ✅ Never exposed to browser/frontend
- ✅ Protected by file permissions
- ✅ Used only for read-only stats queries

**I will NOT:**
- ❌ Store credentials in JavaScript (visible to users)
- ❌ Request write/modify permissions
- ❌ Access any sensitive customer data
- ❌ Make changes to your GGLeap account

---

## 📱 Alternative: Manual Stats Update

**Don't have API access?** No problem!

You can manually update the stats by editing the fallback values in:

**File:** `api/ggleap-stats.php` (lines 165-172)

```php
function getFallbackStats() {
    return [
        'totalAccounts' => 2500,        // ← Update this
        'newAccountsToday' => 12,       // ← Update this
        'totalHours' => 50000,          // ← Update this
        'hoursThisMonth' => 3200,       // ← Update this
        'timestamp' => time(),
        'using_fallback' => true
    ];
}
```

**How often to update:** Weekly or monthly, manually

---

## 🎯 Quick Start Options

### **Option 1: Full API Integration** (Recommended)
- ✅ Real-time data
- ✅ Auto-updates
- ✅ Most accurate
- ⏱️ Needs: API credentials

### **Option 2: Manual Updates**
- ✅ No API needed
- ✅ You control the numbers
- ⚠️ Requires manual editing
- ⏱️ Needs: 5 minutes of your time weekly

### **Option 3: Keep Fallback Values**
- ✅ Works right now
- ⚠️ Numbers are estimates
- ⚠️ Never updates
- ⏱️ Needs: Nothing

---

## 💡 Example GGLeap API Credentials

**What they typically look like:**

```
API Key: [REDACTED - Use .env file]
Center ID: 12345
API URL: https://api.ggleap.com/v1/
```

Or:

```
API Token: Bearer eyJhbGciOiJIUzI1NiIsInR5cCI6IkpXVCJ9...
Location ID: bak-esports-001
API URL: https://ggleap.com/api/v2/
```

*(Exact format depends on GGLeap's system)*

---

## 📞 Need Help?

### **Contact GGLeap:**
- **Website:** https://ggleap.com
- **Support:** Check their website for support contact
- **Documentation:** Ask for API documentation

### **What to Tell Them:**
> "Hi, I'm setting up a custom stats display on our website and need API access to pull:
> - Total user accounts
> - New accounts today
> - Total gaming hours
> - Monthly gaming hours
>
> Can you provide an API key and documentation?"

---

## ✅ Current Status

**Right Now:**
- ✅ Stats section is live and working
- ✅ Using placeholder/fallback values
- ✅ Structure is ready for API integration
- ⚠️ Waiting for GGLeap credentials

**After Setup:**
- ✅ Real-time data from GGLeap
- ✅ Auto-updating every 5 minutes
- ✅ Accurate player statistics

---

## 🚀 Next Steps

**Choose Your Path:**

1. **Get API Access** → Provide credentials → I'll integrate it
2. **Manual Updates** → Tell me what numbers to use → I'll update fallback values
3. **Use Current Values** → Do nothing, stats work as-is with estimates

**Which option works best for you?**

---

## 📝 Quick Info Form

Copy and fill this out, send it back:

```
=== GGLEAP API INFO ===

API Key/Token:

Center/Location ID:

API Base URL:

Any special notes:


=== OR MANUAL STATS ===

Total Accounts:
New Today:
Total Hours:
Hours This Month:

Update frequency I want: [Daily/Weekly/Monthly]

========================
```

---

**Once I have this info, your stats will be showing live data! 🎮**

