<?php if (isset($_SESSION['user_id'])): ?>
<div class="admin-nav-bar">
    <form action="<?= $base_path ?? '' ?>logout.php" method="POST" style="display: inline;">
        <input type="hidden" name="csrf_token" value="<?= htmlspecialchars(get_csrf_token()) ?>">
        <button type="submit" class="logout-btn">Logout</button>
    </form>
</div>
<?php endif; ?>
