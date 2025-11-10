<?php
require_once __DIR__ . '/includes/init_db.php';
require_once __DIR__ . '/includes/auth.php';

// Only allow access during initial setup
$existingTables = $pdo->query("SELECT name FROM sqlite_master WHERE type='table'")->fetchAll(PDO::FETCH_COLUMN);
if (!empty($existingTables)) {
    header('Location: /admin/login.php');
    exit();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $password = $_POST['password'] ?? '';
    
    if (password_verify($password, $_ENV['ADMIN_INIT_HASH'])) {
        $_SESSION['setup_verified'] = true;
        header('Location: /admin/init_db.php');
        exit();
    } else {
        $error = "Invalid setup key";
    }
}

?><!DOCTYPE html>
<html>
<head>
    <title>Initial Setup</title>
    <link rel="stylesheet" href="/css/admin.css">
</head>
<body>
    <div class="setup-container">
        <h1>Initial System Setup</h1>
        <?php if (isset($error)): ?>
            <div class="alert error"><?= htmlspecialchars($error) ?></div>
        <?php endif; ?>
        <form method="POST">
            <div class="form-group">
                <label>Setup Key (from .env):
                    <input type="password" name="password" required>
                </label>
            </div>
            <button type="submit">Initialize System</button>
        </form>
    </div>
</body>
</html>
