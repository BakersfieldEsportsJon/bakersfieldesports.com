<?php
// Basic header template for admin pages
ob_start();

// Set Content Security Policy
header("Content-Security-Policy: default-src 'self'; script-src 'self'; style-src 'self'; img-src 'self' data: blob:;");
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars(SITE_NAME); ?></title>
    <link rel="stylesheet" href="/css/admin.css">
</head>
<body>
    <header class="admin-header">
        <nav>
            <a href="/admin/dashboard.php">Dashboard</a>
            <a href="/admin/logout.php">Logout</a>
        </nav>
    </header>
    <main class="admin-container">
