<?php
/**
 * HTML Head Component
 * Usage: include this file after setting page-specific variables
 *
 * Required variables:
 * - $page_title: Page title
 * - $page_description: Meta description
 * - $canonical_url: Canonical URL
 *
 * Optional variables:
 * - $page_keywords: Meta keywords (defaults to site keywords)
 * - $og_image: Open Graph image (defaults to site default)
 * - $og_type: Open Graph type (defaults to 'website')
 * - $preload_images: Array of images to preload
 * - $schema_markup: Additional schema.org JSON-LD markup
 * - $additional_css: Array of additional CSS files to load
 * - $additional_js: Array of additional JS files to load in head
 * - $base_path: Path to root (default: '../' for subdirectories, '' for root)
 */

if (!defined('SITE_NAME')) {
    require_once __DIR__ . '/config.php';
}

// Set defaults
$base_path = $base_path ?? '../';
$page_keywords = $page_keywords ?? $default_meta['keywords'];
$og_image = $og_image ?? $default_meta['og_image'];
$og_type = $og_type ?? 'website';
$preload_images = $preload_images ?? [];
$additional_css = $additional_css ?? [];
$additional_js = $additional_js ?? [];
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <title><?php echo htmlspecialchars($page_title); ?></title>
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <link rel="canonical" href="<?php echo htmlspecialchars($canonical_url); ?>" />

    <!-- Meta Tags for SEO -->
    <meta name="description" content="<?php echo htmlspecialchars($page_description); ?>">
    <meta name="keywords" content="<?php echo htmlspecialchars($page_keywords); ?>">
    <meta name="robots" content="index, follow">
    <meta name="author" content="<?php echo SITE_NAME; ?>">

    <!-- Open Graph Meta Tags -->
    <meta property="og:title" content="<?php echo htmlspecialchars($page_title); ?>">
    <meta property="og:description" content="<?php echo htmlspecialchars($page_description); ?>">
    <meta property="og:image" content="<?php echo htmlspecialchars($og_image); ?>">
    <meta property="og:type" content="<?php echo htmlspecialchars($og_type); ?>">
    <meta property="og:url" content="<?php echo htmlspecialchars($canonical_url); ?>">

    <!-- Twitter Cards -->
    <meta name="twitter:card" content="summary_large_image">
    <meta name="twitter:site" content="<?php echo $default_meta['twitter_handle']; ?>">
    <meta name="twitter:title" content="<?php echo htmlspecialchars($page_title); ?>">
    <meta name="twitter:description" content="<?php echo htmlspecialchars($page_description); ?>">
    <meta name="twitter:image" content="<?php echo htmlspecialchars($og_image); ?>">

    <?php if (isset($schema_markup)): ?>
    <!-- Schema.org Markup -->
    <script type="application/ld+json">
    <?php echo $schema_markup; ?>
    </script>
    <?php endif; ?>

    <!-- Preload Critical Resources -->
    <link rel="preload" href="<?php echo $base_path; ?>css/optimized.min.css" as="style">
    <link rel="preload" href="<?php echo $base_path; ?>images/Asset%205-ts1621173277.png" as="image" type="image/png">
    <?php foreach ($preload_images as $image): ?>
    <link rel="preload" href="<?php echo htmlspecialchars($image['url']); ?>" as="image" type="<?php echo htmlspecialchars($image['type']); ?>">
    <?php endforeach; ?>

    <!-- Preconnect to external domains -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>

    <!-- External CSS -->
    <link href="<?php echo $base_path; ?>css/optimized.min.css" rel="stylesheet">
    <link href="<?php echo $base_path; ?>css/custom.css" rel="stylesheet">
    <?php foreach ($additional_css as $css_file): ?>
    <link href="<?php echo htmlspecialchars($css_file); ?>" rel="stylesheet">
    <?php endforeach; ?>

    <!-- Google Fonts - Using font-display: swap for performance -->
    <link href="https://fonts.googleapis.com/css?family=Lato:400,900|Fugaz+One|Open+Sans:300,400,600,700,800&display=swap" rel="stylesheet">

    <!-- Favicon -->
    <link href="<?php echo $base_path; ?>images/favicons/favicon.png" rel="icon" type="image/png">

    <!-- Main Application (includes optimized analytics) -->
    <script type="module" src="<?php echo $base_path; ?>js/main.js"></script>

    <!-- Facebook Pixel Noscript -->
    <noscript>
        <img height="1" width="1" class="hidden"
            src="https://www.facebook.com/tr?id=<?php echo FB_PIXEL_ID; ?>&ev=PageView&noscript=1"/>
    </noscript>

    <?php foreach ($additional_js as $js_file): ?>
    <script src="<?php echo htmlspecialchars($js_file); ?>" defer></script>
    <?php endforeach; ?>
</head>
<body>
