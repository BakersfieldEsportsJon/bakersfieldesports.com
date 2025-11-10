<?php
$eventsFile = __DIR__ . '/events.json';
$content = file_get_contents($eventsFile);

// Fix common JSON issues
$content = preg_replace('/}\s*{/', '},{', $content); // Add missing commas between objects
$content = preg_replace('/"([^"]+)"\s*:/', '"$1":', $content); // Fix spacing around colons
$content = preg_replace('/,\s*}/', '}', $content); // Remove trailing commas
$content = preg_replace('/,\s*]/', ']', $content); // Remove trailing commas in arrays

// Validate the fixed JSON
$data = json_decode($content);
if ($data === null) {
    die("Error: " . json_last_error_msg());
}

// Write back the fixed JSON
file_put_contents($eventsFile, json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE));
echo "JSON fixed successfully\n";
?>
