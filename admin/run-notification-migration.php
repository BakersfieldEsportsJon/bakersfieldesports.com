<?php
/**
 * Notification System Database Migration
 * Run this file once to create the necessary tables
 */

session_start();
require_once __DIR__ . '/includes/auth.php';
requireAdmin();

require_once __DIR__ . '/includes/db.php';

$sql = file_get_contents(__DIR__ . '/migrations/002_create_notification_settings.sql');

// Remove comments and split into individual statements
$sql = preg_replace('/--.*$/m', '', $sql);
$statements = array_filter(array_map('trim', explode(';', $sql)));

$results = [];
$errors = [];

foreach ($statements as $statement) {
    if (empty($statement)) continue;

    try {
        $pdo->exec($statement);
        $results[] = "✓ Executed successfully";
    } catch (PDOException $e) {
        $errors[] = "✗ Error: " . $e->getMessage();
    }
}

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Notification System Migration</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            max-width: 800px;
            margin: 50px auto;
            padding: 20px;
            background: #f5f5f5;
        }
        .container {
            background: white;
            padding: 30px;
            border-radius: 8px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        }
        h1 {
            color: #EC194D;
        }
        .success {
            color: #10b981;
            margin: 10px 0;
        }
        .error {
            color: #ff6b6b;
            margin: 10px 0;
        }
        .back-link {
            display: inline-block;
            margin-top: 20px;
            padding: 10px 20px;
            background: #EC194D;
            color: white;
            text-decoration: none;
            border-radius: 5px;
        }
        .back-link:hover {
            background: #b3163e;
        }
    </style>
</head>
<body>
    <div class="container">
        <h1>Notification System Migration</h1>

        <?php if (empty($errors)): ?>
            <h2 class="success">✓ Migration Successful!</h2>
            <p>The notification settings tables have been created successfully.</p>
            <ul>
                <?php foreach ($results as $result): ?>
                    <li class="success"><?php echo htmlspecialchars($result); ?></li>
                <?php endforeach; ?>
            </ul>
        <?php else: ?>
            <h2 class="error">Migration Completed with Errors</h2>
            <p>Some statements executed successfully:</p>
            <ul>
                <?php foreach ($results as $result): ?>
                    <li class="success"><?php echo htmlspecialchars($result); ?></li>
                <?php endforeach; ?>
            </ul>
            <p>Errors encountered:</p>
            <ul>
                <?php foreach ($errors as $error): ?>
                    <li class="error"><?php echo htmlspecialchars($error); ?></li>
                <?php endforeach; ?>
            </ul>
        <?php endif; ?>

        <a href="notification-settings.php" class="back-link">Go to Notification Settings</a>
        <a href="dashboard.php" class="back-link">Back to Dashboard</a>
    </div>
</body>
</html>
