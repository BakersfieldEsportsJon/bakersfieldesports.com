<?php
/**
 * Footer Component
 *
 * Optional variables:
 * - $base_path: Path to root (default: '../' for subdirectories, '' for root)
 */

$base_path = $base_path ?? '../';

// Social media links with icons
$social_media = [
    'facebook' => ['url' => FACEBOOK_URL, 'icon' => 'facebook.png', 'label' => 'Facebook'],
    'x' => ['url' => TWITTER_URL, 'icon' => 'x.png', 'label' => 'X'],
    'instagram' => ['url' => INSTAGRAM_URL, 'icon' => 'instagram.png', 'label' => 'Instagram'],
    'twitch' => ['url' => TWITCH_URL, 'icon' => 'twitch.png', 'label' => 'Twitch'],
    'youtube' => ['url' => YOUTUBE_URL, 'icon' => 'youtube.png', 'label' => 'YouTube'],
    'tiktok' => ['url' => TIKTOK_URL, 'icon' => 'tiktok.png', 'label' => 'TikTok']
];
?>
<!-- Footer -->
<footer>
    <div class="footer-content">
        <div class="social-media">
            <h3>Follow Us on Social Media</h3>
            <ul>
                <?php foreach ($social_media as $platform => $data): ?>
                <li>
                    <a aria-label="<?php echo htmlspecialchars($data['label']); ?>"
                       href="<?php echo htmlspecialchars($data['url']); ?>"
                       target="_blank"
                       rel="noopener noreferrer">
                        <img alt="<?php echo htmlspecialchars($data['label']); ?>"
                             src="<?php echo $base_path; ?>images/social/<?php echo htmlspecialchars($data['icon']); ?>"
                             loading="lazy"
                             width="32"
                             height="32">
                    </a>
                </li>
                <?php endforeach; ?>
            </ul>
        </div>
        <div class="contact-info">
            <p><strong>Address:</strong> <?php echo SITE_ADDRESS; ?></p>
            <p><strong>Phone:</strong> <a href="tel:<?php echo SITE_PHONE_LINK; ?>"><?php echo SITE_PHONE; ?></a></p>
        </div>
        <p>&copy; <?php echo date('Y'); ?> <a href="<?php echo SITE_URL; ?>">bakersfieldesports.com</a></p>
    </div>
</footer>

<!-- Mobile Navigation Toggle Script -->
<script>
    document.getElementById('nav-toggle').onclick = function() {
        var menu = document.getElementById('nav-menu');
        menu.classList.toggle('active');
    };
</script>

<!-- Service Worker Registration -->
<script>
    if ('serviceWorker' in navigator) {
        window.addEventListener('load', () => {
            navigator.serviceWorker.register('<?php echo $base_path; ?>js/service-worker.js')
                .then(registration => {
                    console.log('ServiceWorker registration successful');
                })
                .catch(error => {
                    console.log('ServiceWorker registration failed:', error);
                });
        });
    }
</script>
</body>
</html>
