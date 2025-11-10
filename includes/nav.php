<?php
/**
 * Navigation Component
 *
 * Optional variables:
 * - $active_page: The current active page (e.g., 'home', 'events', 'about')
 * - $base_path: Path to root (default: '../' for subdirectories, '' for root)
 */

$base_path = $base_path ?? '../';
$active_page = $active_page ?? '';

// Navigation items
$nav_items = [
    'home' => ['url' => $base_path . 'index.html', 'label' => 'Home'],
    'locations' => ['url' => $base_path . 'locations/index.html', 'label' => 'Locations'],
    'events' => ['url' => $base_path . 'events/', 'label' => 'Events'],
    'rates' => ['url' => $base_path . 'rates-parties/index.html', 'label' => 'Rates &amp; Parties'],
    'partnerships' => ['url' => $base_path . 'partnerships/index.html', 'label' => 'Partnerships'],
    'about' => ['url' => $base_path . 'about-us/index.html', 'label' => 'About Us'],
    'contact' => ['url' => $base_path . 'contact-us/index.html', 'label' => 'Contact Us'],
    'discord' => ['url' => DISCORD_URL, 'label' => 'Discord', 'external' => true],
    'stem' => ['url' => $base_path . 'stem/index.html', 'label' => 'STEM']
];
?>
<!-- Header -->
<header>
    <nav class="navbar">
        <div class="container">
            <a class="logo" href="<?php echo $base_path; ?>index.html">
                <img alt="Bakersfield eSports Logo" src="<?php echo $base_path; ?>images/Asset%205-ts1621173277.png" width="200" height="80">
            </a>
            <!-- Navigation Toggle Button -->
            <button aria-label="Toggle navigation" class="nav-toggle" id="nav-toggle">☰</button>
            <!-- Navigation Menu -->
            <ul class="nav-menu" id="nav-menu">
                <?php foreach ($nav_items as $key => $item): ?>
                <li>
                    <a <?php echo ($active_page === $key) ? 'class="active"' : ''; ?>
                       href="<?php echo htmlspecialchars($item['url']); ?>"
                       <?php echo isset($item['external']) && $item['external'] ? 'target="_blank" rel="noopener noreferrer"' : ''; ?>>
                        <?php echo $item['label']; ?>
                    </a>
                </li>
                <?php endforeach; ?>
            </ul>
        </div>
    </nav>
</header>
