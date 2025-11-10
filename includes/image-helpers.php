<?php
/**
 * Image Helper Functions
 * Generate responsive image markup with WebP/AVIF support
 */

/**
 * Generate responsive picture element
 *
 * @param string $src - Original image path (without extension)
 * @param string $alt - Alt text
 * @param array $options - Additional options (class, width, height, sizes, loading)
 * @return string HTML picture element
 */
function responsive_image($src, $alt, $options = []) {
    $base_path = $options['base_path'] ?? '';
    $full_src = $base_path . $src;

    // Default options
    $class = $options['class'] ?? '';
    $width = isset($options['width']) ? ' width="' . htmlspecialchars($options['width']) . '"' : '';
    $height = isset($options['height']) ? ' height="' . htmlspecialchars($options['height']) . '"' : '';
    $loading = $options['loading'] ?? 'lazy';
    $sizes = $options['sizes'] ?? '100vw';

    // Determine file extension
    $ext = $options['ext'] ?? 'jpg';

    // Build picture element
    $html = '<picture>';

    // AVIF source (best compression)
    if (file_exists($full_src . '.avif')) {
        $html .= sprintf(
            '<source srcset="%s.avif" type="image/avif">',
            htmlspecialchars($src)
        );
    }

    // WebP source (good compression, wide support)
    if (file_exists($full_src . '.webp')) {
        $html .= sprintf(
            '<source srcset="%s.webp" type="image/webp">',
            htmlspecialchars($src)
        );
    }

    // Fallback to original image
    $html .= sprintf(
        '<img src="%s.%s" alt="%s"%s%s%s loading="%s"%s>',
        htmlspecialchars($src),
        htmlspecialchars($ext),
        htmlspecialchars($alt),
        $class ? ' class="' . htmlspecialchars($class) . '"' : '',
        $width,
        $height,
        htmlspecialchars($loading),
        $sizes !== '100vw' ? ' sizes="' . htmlspecialchars($sizes) . '"' : ''
    );

    $html .= '</picture>';

    return $html;
}

/**
 * Generate background image CSS with WebP fallback
 *
 * @param string $src - Original image path (without extension)
 * @param string $ext - File extension (default: jpg)
 * @return string CSS background-image declaration
 */
function responsive_bg_image($src, $ext = 'jpg') {
    $webp_path = $src . '.webp';
    $original_path = $src . '.' . $ext;

    $css = '';

    // Fallback for browsers that don't support WebP
    $css .= "background-image: url('" . htmlspecialchars($original_path) . "');";

    // WebP for supported browsers (handled in CSS with @supports or via JS)
    if (file_exists($webp_path)) {
        $css .= "\n    background-image: -webkit-image-set(";
        $css .= "url('" . htmlspecialchars($webp_path) . "') 1x, ";
        $css .= "url('" . htmlspecialchars($original_path) . "') 1x);";
        $css .= "\n    background-image: image-set(";
        $css .= "url('" . htmlspecialchars($webp_path) . "') type('image/webp'), ";
        $css .= "url('" . htmlspecialchars($original_path) . "') type('image/" . $ext . "'));";
    }

    return $css;
}

/**
 * Get optimized image path (returns WebP if exists, otherwise original)
 *
 * @param string $src - Original image path
 * @return string Optimized image path
 */
function get_optimized_image_path($src) {
    $path_info = pathinfo($src);
    $dir = $path_info['dirname'];
    $filename = $path_info['filename'];

    // Check for AVIF
    $avif_path = $dir . '/' . $filename . '.avif';
    if (file_exists($avif_path)) {
        return $avif_path;
    }

    // Check for WebP
    $webp_path = $dir . '/' . $filename . '.webp';
    if (file_exists($webp_path)) {
        return $webp_path;
    }

    // Return original
    return $src;
}

/**
 * Check if browser supports WebP
 * (Simple server-side check based on Accept header)
 *
 * @return bool True if WebP is supported
 */
function supports_webp() {
    if (!isset($_SERVER['HTTP_ACCEPT'])) {
        return false;
    }

    return strpos($_SERVER['HTTP_ACCEPT'], 'image/webp') !== false;
}

/**
 * Generate srcset for responsive images
 *
 * @param string $src - Base image path (without size suffix and extension)
 * @param array $sizes - Array of sizes [width => descriptor] or ['small' => '640w', ...]
 * @param string $ext - File extension
 * @return string srcset attribute value
 */
function generate_srcset($src, $sizes, $ext = 'jpg') {
    $srcset_parts = [];

    foreach ($sizes as $size => $descriptor) {
        $path = is_numeric($size) ? $src . '-' . $size . '.' . $ext : $src . '-' . $size . '.' . $ext;

        if (file_exists($path)) {
            $srcset_parts[] = $path . ' ' . $descriptor;
        }
    }

    // Add original if no sizes matched
    if (empty($srcset_parts)) {
        $srcset_parts[] = $src . '.' . $ext . ' 1x';
    }

    return implode(', ', $srcset_parts);
}
