# Image Optimization Guide

**Project:** Bakersfield eSports Center Website
**Date:** October 25, 2025

---

## Overview

This guide explains the image optimization system implemented on the website, including WebP conversion, responsive images, and performance best practices.

---

## 🎯 Current Status

### ✅ What's Already Implemented

1. **Lazy Loading** - All images in `index.html` use `loading="lazy"` attribute
2. **Image Helper Functions** - `includes/image-helpers.php` provides:
   - Responsive `<picture>` element generation
   - WebP/AVIF support with fallbacks
   - Srcset generation for responsive images
   - Background image optimization

3. **WebP Conversion Tool** - `scripts/convert-images-to-webp.php` for batch conversion

### 📊 Image Inventory

- **Total images:** 654 files (JPG, PNG, GIF)
- **Location:** `public_html/images/`
- **Formats:** Mix of JPG and PNG
- **Usage:** Event images, game thumbnails, promotional graphics

---

## 🚀 Using the WebP Conversion Tool

### Installation Check

Verify your PHP installation has WebP support:

```bash
php -r "echo extension_loaded('gd') ? 'GD: Yes\n' : 'GD: No\n';"
php -r "echo function_exists('imagewebp') ? 'WebP: Yes\n' : 'WebP: No\n';"
```

If either shows "No", install/update PHP GD extension:

**Ubuntu/Debian:**
```bash
sudo apt-get install php-gd
sudo systemctl restart apache2
```

**Windows (XAMPP/WAMP):**
- Edit `php.ini`
- Uncomment: `;extension=gd`
- Restart Apache

### Basic Usage

**Convert all images in a directory:**
```bash
cd public_html/scripts
php convert-images-to-webp.php ../images
```

**Custom quality (default is 85):**
```bash
php convert-images-to-webp.php ../images --quality=90
```

**Delete originals after conversion (use with caution):**
```bash
php convert-images-to-webp.php ../images --quality=85 --delete-originals
```

### What the Script Does

1. **Recursively scans** the target directory for JPG/PNG images
2. **Creates WebP versions** with specified quality (default 85%)
3. **Resizes oversized images** (max 2560x2560) while maintaining aspect ratio
4. **Preserves transparency** for PNG images
5. **Skips existing WebP** files that are up-to-date
6. **Reports savings** - shows file size reduction and percentage saved

### Example Output

```
WebP Image Conversion Tool
===========================
Target Directory: /var/www/html/images
Quality: 85
Delete Originals: No
Max Dimensions: 2560x2560

Found 654 images to process.

[1/654] Processing: game1.png ... ✓ Saved 42.3% (124.5 KB)
[2/654] Processing: event-photo.jpg ... ✓ Saved 38.1% (256.2 KB)
[3/654] Processing: logo.png ... ⊘ Skipped (WebP exists and is up to date)
...

Conversion Complete
===================
Total files: 654
Converted: 620
Skipped: 32
Errors: 2
Total space saved: 45.2 MB
Time elapsed: 124.8 seconds
Average savings per image: 74.6 KB

Done!
```

---

## 📝 Using Image Helper Functions

### Include the Helper Library

```php
<?php
require_once 'includes/image-helpers.php';
?>
```

### Method 1: Responsive Picture Element

**Best for:** Content images that need format fallbacks

```php
<?php
// Basic usage
echo responsive_image('images/game1', 'Game Title Screenshot', [
    'ext' => 'png',
    'width' => 800,
    'height' => 600,
    'loading' => 'lazy'
]);
?>
```

**Outputs:**
```html
<picture>
    <source srcset="images/game1.webp" type="image/webp">
    <img src="images/game1.png" alt="Game Title Screenshot"
         width="800" height="600" loading="lazy">
</picture>
```

### Method 2: Simple Optimized Path

**Best for:** When you just need the best available format

```php
<?php
$optimized_path = get_optimized_image_path('images/game1.png');
// Returns: images/game1.webp (if exists), otherwise images/game1.png
?>

<img src="<?php echo htmlspecialchars($optimized_path); ?>"
     alt="Game Title" loading="lazy">
```

### Method 3: Responsive Srcset

**Best for:** Multiple image sizes for different screen resolutions

```php
<?php
// Assuming you have: game1-640.jpg, game1-1024.jpg, game1-1920.jpg
$srcset = generate_srcset('images/game1', [
    640 => '640w',
    1024 => '1024w',
    1920 => '1920w'
], 'jpg');
?>

<img srcset="<?php echo $srcset; ?>"
     sizes="(max-width: 640px) 100vw, (max-width: 1024px) 50vw, 800px"
     src="images/game1-1024.jpg"
     alt="Game Screenshot"
     loading="lazy">
```

### Method 4: Background Images

**Best for:** CSS background images

```php
<div style="<?php echo responsive_bg_image('images/banner', 'jpg'); ?>">
    Content here
</div>
```

---

## 🎨 HTML Best Practices

### Always Include These Attributes

```html
<img src="image.jpg"
     alt="Descriptive text"          <!-- Required for accessibility -->
     width="800"                      <!-- Prevents layout shift -->
     height="600"                     <!-- Prevents layout shift -->
     loading="lazy">                  <!-- Lazy load below fold images -->
```

### Above-the-Fold Images

For images visible on page load, use `loading="eager"` or omit the attribute:

```html
<!-- Hero image - load immediately -->
<img src="images/hero.jpg" alt="Hero" width="1920" height="1080">
```

### Below-the-Fold Images

For images not immediately visible, use `loading="lazy"`:

```html
<!-- Game grid - lazy load -->
<img src="images/game1.png" alt="Game 1" loading="lazy" width="150" height="150">
```

---

## 📱 Responsive Image Sizes

### Recommended Breakpoints

Create multiple versions of large images:

- **640px** - Mobile portrait
- **1024px** - Tablet/small desktop
- **1920px** - Desktop/HD

### Example Implementation

```html
<picture>
    <!-- WebP versions -->
    <source srcset="images/hero-640.webp 640w,
                    images/hero-1024.webp 1024w,
                    images/hero-1920.webp 1920w"
            type="image/webp">

    <!-- JPG fallback -->
    <source srcset="images/hero-640.jpg 640w,
                    images/hero-1024.jpg 1024w,
                    images/hero-1920.jpg 1920w"
            type="image/jpeg">

    <!-- Default -->
    <img src="images/hero-1024.jpg"
         alt="Hero Image"
         sizes="100vw"
         loading="lazy">
</picture>
```

---

## ⚡ Performance Impact

### Expected Improvements

| Metric | Before | After | Improvement |
|--------|--------|-------|-------------|
| Image File Size | ~200 KB avg | ~80 KB avg | 60% reduction |
| Page Load Time | 4.5s | 2.2s | 51% faster |
| Bandwidth Usage | 12 MB | 4.8 MB | 60% reduction |
| Lighthouse Score | 72 | 92 | +20 points |

### WebP Compression Stats

Based on typical conversion with 85% quality:

- **PNG photos:** 40-50% smaller
- **JPG photos:** 30-40% smaller
- **PNG graphics:** 50-70% smaller
- **Quality:** Nearly identical to original

---

## 🔧 Integration Checklist

### For New Images

- [ ] Upload original (JPG/PNG) to `images/` folder
- [ ] Run WebP conversion script
- [ ] Use `responsive_image()` helper in PHP files
- [ ] Add `loading="lazy"` for below-fold images
- [ ] Include `width` and `height` attributes

### For Existing Pages

- [ ] Convert existing images to WebP using the script
- [ ] Update `<img>` tags to use helper functions OR
- [ ] Add `<picture>` elements with WebP sources OR
- [ ] Keep existing `<img>` tags and serve WebP via `.htaccess` rewrite

---

## 🌐 Browser Support

### WebP Support

- ✅ Chrome 32+ (2014)
- ✅ Firefox 65+ (2019)
- ✅ Edge 18+ (2018)
- ✅ Safari 14+ (2020)
- ✅ Opera 19+ (2014)

**Coverage:** ~97% of global browsers

### Fallback Strategy

The `<picture>` element automatically falls back to JPG/PNG for older browsers. No JavaScript required!

```html
<picture>
    <source srcset="image.webp" type="image/webp">
    <img src="image.jpg" alt="Description">  <!-- Fallback -->
</picture>
```

---

## 🚦 `.htaccess` Auto-Serve (Optional)

To automatically serve WebP images when available without changing HTML:

```apache
# Add to .htaccess
<IfModule mod_rewrite.c>
    RewriteEngine On

    # Check if browser supports WebP
    RewriteCond %{HTTP_ACCEPT} image/webp

    # Check if WebP version exists
    RewriteCond %{REQUEST_FILENAME} \.(jpe?g|png)$
    RewriteCond %{REQUEST_FILENAME}.webp -f

    # Serve WebP version
    RewriteRule ^(.+)\.(jpe?g|png)$ $1.$2.webp [T=image/webp,E=REQUEST_image]
</IfModule>

<IfModule mod_headers.c>
    # Vary header for caching
    Header append Vary Accept env=REQUEST_image
</IfModule>

<IfModule mod_mime.c>
    # Set WebP MIME type
    AddType image/webp .webp
</IfModule>
```

**Benefits:**
- No code changes needed
- Automatic format selection
- Works with existing `<img>` tags

**Drawbacks:**
- Requires mod_rewrite
- Slightly more complex caching

---

## 📚 Additional Resources

### Tools

- **ImageOptim** - GUI tool for lossless image compression (Mac)
- **Squoosh** - https://squoosh.app - Online image optimizer
- **TinyPNG** - https://tinypng.com - PNG/JPG compression service

### Testing

**Check WebP browser support:**
```javascript
// In browser console
document.createElement('canvas').toDataURL('image/webp').indexOf('data:image/webp') === 0
// Returns: true if supported
```

**Test image loading:**
- Chrome DevTools > Network tab > Filter: Img
- Check "Type" column for `webp`

**Measure performance:**
- Google PageSpeed Insights: https://pagespeed.web.dev
- WebPageTest: https://www.webpagetest.org

---

## 🔒 Security Considerations

### File Upload Validation

When accepting user-uploaded images:

1. **Validate file type** - Check MIME type AND extension
2. **Use `getimagesize()`** - Verify it's actually an image
3. **Limit file size** - Max 5-10 MB per image
4. **Rename files** - Use random/hashed filenames
5. **Store outside web root** - Or use .htaccess protection

**Example:**
```php
// From Events Admin Prod/upload_event_image.php
$allowed_types = ['image/jpeg', 'image/png', 'image/gif'];
$max_size = 5 * 1024 * 1024; // 5MB

if (!in_array($file['type'], $allowed_types)) {
    die('Invalid file type');
}

if ($file['size'] > $max_size) {
    die('File too large');
}

$image_info = getimagesize($file['tmp_name']);
if ($image_info === false) {
    die('Not a valid image');
}
```

---

## 📞 Support

For issues or questions about image optimization:

1. Check the script output for error messages
2. Verify PHP GD extension is installed
3. Ensure write permissions in target directories
4. Test with a single image first before batch conversion

---

**Last Updated:** October 25, 2025
**Status:** ✅ Image optimization system ready for production use
