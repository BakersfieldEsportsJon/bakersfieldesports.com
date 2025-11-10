<?php
/**
 * Secure Configuration Loader
 * Loads environment variables from .env file outside webroot
 *
 * IMPORTANT: Move .env file to parent directory (above public_html)
 * Current location: public_html/.env
 * New location: ../.env (one level up)
 */

/**
 * Load environment variables from .env file
 * @param string $path - Path to .env file
 */
function load_env($path) {
    if (!file_exists($path)) {
        error_log("Warning: .env file not found at: " . $path);
        return;
    }

    $lines = file($path, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);

    foreach ($lines as $line) {
        // Skip comments
        if (strpos(trim($line), '#') === 0) {
            continue;
        }

        // Parse KEY=VALUE
        if (strpos($line, '=') !== false) {
            list($key, $value) = explode('=', $line, 2);
            $key = trim($key);
            $value = trim($value);

            // Remove quotes from value
            $value = trim($value, '"\'');

            // Set environment variable (don't overwrite existing)
            if (!getenv($key)) {
                putenv("$key=$value");
                $_ENV[$key] = $value;
                $_SERVER[$key] = $value;
            }
        }
    }
}

/**
 * Get environment variable with optional default
 * @param string $key - Environment variable key
 * @param mixed $default - Default value if not found
 * @return mixed Environment variable value or default
 */
function env($key, $default = null) {
    $value = getenv($key);

    if ($value === false) {
        return $default;
    }

    // Handle boolean values
    $lower = strtolower($value);
    if ($lower === 'true' || $lower === '(true)') {
        return true;
    }
    if ($lower === 'false' || $lower === '(false)') {
        return false;
    }
    if ($lower === 'null' || $lower === '(null)') {
        return null;
    }

    return $value;
}

// Try to load .env from parent directory first (secure location)
// Then fall back to current directory (for backward compatibility)
$env_paths = [
    __DIR__ . '/../../.env',  // Parent of public_html (RECOMMENDED)
    __DIR__ . '/../.env',     // public_html directory (FALLBACK - less secure)
];

foreach ($env_paths as $env_path) {
    if (file_exists($env_path)) {
        load_env($env_path);
        break;
    }
}
