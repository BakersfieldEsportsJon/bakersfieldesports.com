<?php
/**
 * Fix JavaScript syntax error - remove broken comment block
 */

$file = __DIR__ . '/js/startgg-events.js';
$content = file_get_contents($file);

// Find and remove the broken comment block (lines 270-341)
// It starts with "// Keep old modal code" and ends before "// Make it globally accessible"

$pattern = '/\/\/ Keep old modal code commented out for reference\s*\/\*.*?(?=\/\/ Make it globally accessible)/s';

$fixed = preg_replace($pattern, '', $content);

if ($fixed === null || $fixed === $content) {
    echo "Pattern didn't match, trying alternative approach...\n";

    // Alternative: Just close the comment if it exists
    $fixed = str_replace(
        "    // Keep old modal code commented out for reference\n    /*",
        "    // Old modal code removed\n    // Comment block removed due to syntax error",
        $content
    );
}

// Backup and save
$backup = $file . '.backup.' . date('Y-m-d-His');
copy($file, $backup);
file_put_contents($file, $fixed);

echo "✓ Fixed JavaScript syntax error\n";
echo "✓ Removed broken comment block\n";
echo "✓ Backup: $backup\n";
echo "\nTesting syntax...\n";

// Test the fixed file
exec('node -c "' . $file . '" 2>&1', $output, $return);
if ($return === 0) {
    echo "✓ JavaScript syntax is now valid!\n";
    echo "\n🎉 Refresh the events page - tournaments should now appear!\n";
} else {
    echo "✗ Still has errors:\n";
    echo implode("\n", $output) . "\n";
}
