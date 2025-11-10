# Migration Checklist - Bakersfield eSports Website

Use this checklist to migrate from the old structure to the new optimized system.

---

## ✅ Pre-Migration

- [ ] **Backup everything**
  ```bash
  # Create backup of current site
  tar -czf backup-$(date +%Y%m%d).tar.gz public_html/
  ```

- [ ] **Test in staging environment first** (if available)

- [ ] **Review all changes in this backup directory**

---

## 🔧 Phase 1: Setup & Configuration

### 1.1 Install Dependencies
- [ ] SSH into server
- [ ] Navigate to `public_html/`
- [ ] Run `npm install`
- [ ] Verify Node.js version: `node --version` (should be 20.x)

### 1.2 Move .env File (Security)
- [ ] Copy `.env` from `public_html/` to parent directory
  ```bash
  cp public_html/.env .env
  ```
- [ ] Update paths in any scripts that reference `.env`
- [ ] Delete `public_html/.env` after confirming everything works
- [ ] Add `.env` to `.gitignore` if using Git

### 1.3 Update .env File
- [ ] Add start.gg API key (get from https://developer.start.gg/)
- [ ] Verify Google Analytics ID
- [ ] Verify Facebook Pixel ID
- [ ] Add any other API keys needed

---

## 🎨 Phase 2: Build Assets

### 2.1 Build CSS
- [ ] Run `npm run build:css`
- [ ] Verify `css/optimized.min.css` was created
- [ ] Check file size (should be ~15 KB)

### 2.2 Optimize Images
- [ ] Run `npm run optimize:images`
- [ ] Verify `.webp` files were created alongside originals
- [ ] Verify `.avif` files were created (if Sharp supports it)
- [ ] Check a few images manually to ensure quality

---

## 📄 Phase 3: Convert Pages

### 3.1 Convert Main Pages

For each HTML page, convert to PHP using the template system:

- [ ] **index.html → index.php**
  - Copy content from `index-new.php` (already created)
  - Test page loads correctly
  - Verify navigation highlights correctly
  - Check schema markup

- [ ] **about-us/index.html → about-us/index.php**
  - Use template system
  - Update `$active_page = 'about'`
  - Add Organization schema
  - Test page

- [ ] **contact-us/index.html → contact-us/index.php**
  - Use template system
  - Update `$active_page = 'contact'`
  - Ensure form functionality works
  - Test reCAPTCHA

- [ ] **events/index.php**
  - Already exists, update to use new modules
  - Connect to start.gg API
  - Test event loading

- [ ] **gallery/index.php**
  - Update to use optimized images
  - Test image loading
  - Verify lazy loading works

- [ ] **locations/index.html → locations/index.php**
  - Convert to template system
  - Test map integration

- [ ] **partnerships/index.html → partnerships/index.php**
  - Convert to template system
  - Update partner logos

- [ ] **rates-parties/index.html → rates-parties/index.php**
  - Convert to template system
  - Test pricing display
  - Verify Stripe integration

- [ ] **stem/index.html → stem/index.php**
  - Convert to template system
  - Test all links and content

### 3.2 Verify Each Page
For each converted page, check:
- [ ] Page loads without errors
- [ ] Navigation highlights correct item
- [ ] Meta tags are present and correct
- [ ] Images load (responsive versions)
- [ ] Forms work (if applicable)
- [ ] Links work
- [ ] Mobile responsiveness

---

## 🔗 Phase 4: Update .htaccess

- [ ] Backup current `.htaccess`
- [ ] Update with new version (already done in backup)
- [ ] Test URL rewrites work
- [ ] Test .php extension removal
- [ ] Test WebP serving
- [ ] Verify HTTPS redirect
- [ ] Check caching headers

---

## 📱 Phase 5: JavaScript Integration

### 5.1 Update Script Loading
- [ ] Remove old inline analytics scripts from pages
- [ ] Add `<script type="module" src="/js/main.js"></script>` to pages
- [ ] Verify analytics tracking works
- [ ] Test navigation menu on mobile
- [ ] Check smooth scrolling
- [ ] Test fade-in animations

### 5.2 Test start.gg Integration
- [ ] Update API key in code or .env
- [ ] Test tournament fetching
- [ ] Verify registration buttons work
- [ ] Check event details display correctly

---

## 🧪 Phase 6: Testing

### 6.1 Functional Testing
- [ ] Test all forms
- [ ] Test navigation (desktop & mobile)
- [ ] Test search functionality (if applicable)
- [ ] Test external links open in new tabs
- [ ] Test back button functionality
- [ ] Test 404 error pages
- [ ] Test contact form sends emails

### 6.2 Visual Testing
- [ ] Check layout on desktop
- [ ] Check layout on tablet
- [ ] Check layout on mobile (various sizes)
- [ ] Verify images display correctly
- [ ] Check font rendering
- [ ] Verify colors match brand
- [ ] Check for layout breaks

### 6.3 Browser Testing
Test in:
- [ ] Chrome (latest)
- [ ] Firefox (latest)
- [ ] Safari (latest)
- [ ] Edge (latest)
- [ ] Mobile browsers (iOS Safari, Chrome Mobile)

### 6.4 Performance Testing
- [ ] Run Lighthouse audit
- [ ] Check Core Web Vitals
- [ ] Test page load speed
- [ ] Verify caching works
- [ ] Check bundle sizes
- [ ] Test on slow connection

### 6.5 SEO Testing
- [ ] Verify meta tags
- [ ] Check schema markup (https://validator.schema.org/)
- [ ] Test robots.txt
- [ ] Verify sitemap.xml
- [ ] Check canonical URLs
- [ ] Test Open Graph tags (Facebook debugger)
- [ ] Test Twitter Cards

### 6.6 Analytics Testing
- [ ] Verify Google Analytics tracking
- [ ] Test Facebook Pixel events
- [ ] Check event tracking (forms, clicks, etc.)
- [ ] Verify conversion tracking

---

## 🚀 Phase 7: Deployment

### 7.1 Pre-Deployment
- [ ] Final backup of live site
- [ ] Prepare rollback plan
- [ ] Schedule deployment during low-traffic time
- [ ] Notify team of deployment

### 7.2 Deploy Files
- [ ] Upload new/modified files
- [ ] Upload includes/ directory
- [ ] Upload js/ directory
- [ ] Upload css/ directory
- [ ] Upload optimized images
- [ ] Upload .htaccess (carefully!)

### 7.3 Post-Deployment Checks
- [ ] Test homepage loads
- [ ] Check all main pages
- [ ] Verify forms work
- [ ] Check analytics tracking
- [ ] Monitor error logs
- [ ] Test mobile site
- [ ] Check SSL certificate

---

## 📊 Phase 8: Monitoring

### Week 1
- [ ] Monitor Google Analytics for traffic drops
- [ ] Check Google Search Console for crawl errors
- [ ] Monitor server error logs
- [ ] Check page load times
- [ ] Monitor conversion rates

### Week 2-4
- [ ] Review performance metrics
- [ ] Check for 404 errors
- [ ] Review user feedback
- [ ] Monitor search rankings
- [ ] Check Core Web Vitals in Search Console

---

## 🔄 Phase 9: Cleanup

After confirming everything works (2-4 weeks):
- [ ] Delete old .html files
- [ ] Remove old/unused CSS files
- [ ] Remove old JavaScript files
- [ ] Clean up old images (after verifying WebP versions work)
- [ ] Archive old backups
- [ ] Update documentation

---

## 📋 Final Verification

- [ ] All pages converted and tested
- [ ] All images optimized
- [ ] Analytics working correctly
- [ ] Forms submitting properly
- [ ] No console errors
- [ ] Mobile experience excellent
- [ ] Page speed score 90+
- [ ] SEO score 100
- [ ] All team members trained on new system

---

## 🆘 Rollback Plan (If Needed)

If critical issues occur:

1. **Restore from backup:**
   ```bash
   tar -xzf backup-YYYYMMDD.tar.gz
   ```

2. **Restore .htaccess** (keep backup)

3. **Restore database** (if modified)

4. **Clear all caches** (browser, server, CDN)

5. **Investigate issues** before retry

---

## 📝 Notes

- Document any issues encountered
- Keep track of what was changed when
- Update team on progress
- Collect user feedback post-migration

**Migration Start Date:** ______________
**Migration Complete Date:** ______________
**Performed By:** ______________

---

## ✨ Success Criteria

Migration is successful when:
- [x] All pages load without errors
- [x] Performance score improved
- [x] No SEO ranking drops
- [x] User experience enhanced
- [x] Team comfortable with new system
- [x] Analytics tracking correctly
- [x] Mobile experience excellent

---

**Document Version:** 1.0
**Last Updated:** October 2025
