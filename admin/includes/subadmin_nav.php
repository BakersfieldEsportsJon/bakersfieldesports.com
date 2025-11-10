    <header>
        <nav class="navbar">
            <div class="container">
                <a class="logo" href="<?= $nav_base_path ?? '../../' ?>index.php">
                    <img alt="Bakersfield eSports Logo" src="<?= $nav_base_path ?? '../../' ?>images/Asset%205-ts1621173277.png" />
                </a>
                <button aria-label="Toggle navigation" class="nav-toggle" id="nav-toggle">☰</button>
                <ul class="nav-menu" id="nav-menu">
                    <li><a href="<?= $nav_base_path ?? '../../' ?>index.php">Home</a></li>
                    <li><a href="<?= $nav_base_path ?? '../../' ?>locations/index.php">Locations</a></li>
                    <li><a href="<?= $nav_base_path ?? '../../' ?>events/">Events</a></li>
                    <li><a href="<?= $nav_base_path ?? '../../' ?>rates-parties/index.php">Rates & Parties</a></li>
                    <li><a href="<?= $nav_base_path ?? '../../' ?>partnerships/index.php">Partnerships</a></li>
                    <li><a href="<?= $nav_base_path ?? '../../' ?>about-us/index.php">About Us</a></li>
                    <li><a href="<?= $nav_base_path ?? '../../' ?>contact-us/index.php">Contact Us</a></li>
                    <li><a href="https://discord.gg/jbzWH3ZvRp" target="_blank">Discord</a></li>
                    <li><a href="<?= $nav_base_path ?? '../../' ?>stem/index.php">STEM</a></li>
                    <li>
                        <form action="<?= $nav_base_path ?? '../../' ?>admin/logout.php" method="POST" style="display: inline;">
                            <input type="hidden" name="csrf_token" value="<?= htmlspecialchars(get_csrf_token()) ?>">
                            <button type="submit" class="logout-btn">Logout</button>
                        </form>
                    </li>
                </ul>
            </div>
        </nav>
    </header>
