# Environment File Security Setup

## ⚠️ CRITICAL: Your .env file has been secured but credentials MUST be rotated!

## Security Measures Implemented

### 1. ✅ .gitignore Protection
The `.env` file has been added to `.gitignore` to prevent accidental commits to version control.

**Files protected:**
- `.env`
- `.env.local`
- `.env.*.local`
- `*.db` and `*.sqlite` files

### 2. ✅ .htaccess Web Access Protection
The `.htaccess` file now blocks web access to sensitive files:
- `.env` files
- `.git` directory
- Database files (`.db`, `.sqlite`, `.sqlite3`)
- Configuration files (`composer.json`, `package.json`)

### 3. ✅ .env.example Template Created
A template file with placeholder values has been created for reference.

---

## 🔴 IMMEDIATE ACTION REQUIRED: Rotate All Credentials

The following credentials were found in your `.env` file and **MUST be rotated immediately**:

### Database Credentials
- **Current:** `DB_PASSWORD="[REDACTED]"`
- **Action:** Change your database password through your hosting control panel
- **Update:** Edit `.env` file with new password

### Session Encryption Key
- **Current:** `SESSION_ENCRYPTION_KEY='[REDACTED]'`
- **Action:** Generate a new random key
- **Command:** You can generate one with: `openssl rand -base64 32`
- **Warning:** Changing this will invalidate all existing user sessions (users will need to log in again)

### Discord Bot Token
- **Current:** `DISCORD_TOKEN='MTMyODQ4Mzg2NDk5MzA3NTIyMA.GyMme2...'`
- **Action:** Regenerate token at https://discord.com/developers/applications
- **Update:** Replace in `.env` file

### Twilio Credentials
- **Current SID:** `[REDACTED]`
- **Current Token:** `[REDACTED]`
- **Action:** Rotate credentials at https://console.twilio.com
- **Update:** Replace both values in `.env` file

### reCAPTCHA Keys
- **Current Secret:** `[REDACTED]`
- **Action:** Generate new keys at https://www.google.com/recaptcha/admin
- **Update:** Update both RECAPTCHA_SECRET_KEY and RECAPTCHA_SITE_KEY

---

## How to Rotate Credentials

### Step 1: Backup Current .env
```bash
cp .env .env.backup
```

### Step 2: Generate New Keys

**Session Encryption Key (on Linux/Mac):**
```bash
openssl rand -base64 32
```

**Session Encryption Key (on Windows with Git Bash):**
```bash
openssl rand -base64 32
```

**Or use this PHP script:**
```php
<?php
echo bin2hex(random_bytes(32));
?>
```

### Step 3: Update .env File
Edit the `.env` file and replace all the exposed values with new ones.

### Step 4: Update External Services
- Discord: Regenerate bot token
- Twilio: Rotate API credentials
- reCAPTCHA: Generate new site and secret keys
- Database: Change password through cPanel/hosting panel

### Step 5: Update Frontend (if needed)
If you regenerate reCAPTCHA keys, update the site key in:
- `contact-us/index.php` (line 57)

---

## Verify .env Protection

### Test 1: Check .htaccess Protection
Try accessing: `https://yourdomain.com/.env`

**Expected Result:** 403 Forbidden error

**If you see file contents:** Your .htaccess isn't working. Contact your hosting provider.

### Test 2: Verify Git Ignore
```bash
git status
```

**Expected Result:** `.env` should NOT appear in untracked or modified files

**If .env appears:** Run `git rm --cached .env` to remove it from tracking

### Test 3: Check File Permissions
```bash
ls -la .env
```

**Recommended Permissions:** `600` or `640` (read/write for owner only)

```bash
chmod 600 .env
```

---

## Production Deployment Checklist

Before deploying to production:

- [ ] `.env` file is NOT in version control
- [ ] All credentials have been rotated
- [ ] `.htaccess` blocks access to `.env`
- [ ] File permissions set to 600 or 640
- [ ] `.env.example` is committed (but NOT .env)
- [ ] Verified .env cannot be accessed via web browser
- [ ] All external services updated with new credentials
- [ ] Tested application works with new credentials

---

## Environment Variable Best Practices

### DO:
- ✅ Use `.env` files for local development
- ✅ Use secure secret management in production (AWS Secrets Manager, Azure Key Vault, etc.)
- ✅ Rotate credentials regularly (every 90 days)
- ✅ Use strong, unique passwords for each service
- ✅ Keep `.env.example` updated when adding new variables
- ✅ Document what each environment variable does

### DON'T:
- ❌ Commit `.env` files to version control
- ❌ Share `.env` files via email or messaging
- ❌ Use the same credentials across environments (dev/staging/prod)
- ❌ Store credentials in code files
- ❌ Use weak or default passwords
- ❌ Leave test/debug values in production

---

## Additional Security Recommendations

### 1. Move .env Outside Web Root (Advanced)
The most secure approach is to move `.env` outside the publicly accessible directory:

```
/home/user/
  ├── bakersfieldesports.com/  (NOT web accessible)
  │   └── .env
  └── public_html/             (Web accessible)
      └── index.php
```

Then update your bootstrap/config file to load from the parent directory.

### 2. Use Server Environment Variables
Instead of `.env` files, set environment variables at the server level:
- cPanel: PHP INI Editor or Environment Variables
- Apache: SetEnv directives in VirtualHost
- Nginx: fastcgi_param directives

### 3. Regular Security Audits
- Review `.env` values quarterly
- Check web server access logs for attempts to access `.env`
- Monitor for credential leaks on GitHub, pastebin, etc.

---

## Troubleshooting

### "Application can't find environment variables"
- Verify `.env` file exists in the correct directory
- Check file permissions (must be readable by web server)
- Verify your bootstrap code is loading `.env` correctly

### "Still getting 200 OK when accessing .env"
- Check if `.htaccess` is being processed (verify mod_rewrite is enabled)
- Try adding `Require all denied` instead of `Order allow,deny`
- Contact hosting provider to verify .htaccess support

### "Sessions invalidated after changing SESSION_ENCRYPTION_KEY"
- This is expected behavior
- All users will need to log in again
- Plan credential rotation during low-traffic periods

---

## Support

If you need help with credential rotation or have questions:
1. Check your hosting provider's documentation
2. Contact your hosting provider's support team
3. Review service-specific documentation (Discord, Twilio, etc.)

**Last Updated:** 2025-10-25
