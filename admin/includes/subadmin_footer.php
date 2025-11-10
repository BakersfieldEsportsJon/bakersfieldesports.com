
    <footer>
        <div class="container">
            <div class="social-media">
                <h3>Follow Us on Social Media</h3>
                <ul>
                    <li><a aria-label="Facebook" href="https://www.facebook.com/Bakersfield-ESports-104418741131608" target="_blank"><img alt="Facebook" src="<?= $nav_base_path ?? '../../' ?>images/social/facebook.png" /> </a></li>
                    <li><a aria-label="X" href="https://x.com/Bak_eSports" target="_blank"><img alt="X" src="<?= $nav_base_path ?? '../../' ?>images/social/x.png" /> </a></li>
                    <li><a aria-label="Instagram" href="https://www.instagram.com/bakersfieldesports" target="_blank"><img alt="Instagram" src="<?= $nav_base_path ?? '../../' ?>images/social/instagram.png" /> </a></li>
                    <li><a aria-label="Twitch" href="https://www.twitch.tv/bakersfieldesportscenter" target="_blank"><img alt="Twitch" src="<?= $nav_base_path ?? '../../' ?>images/social/twitch.png" /> </a></li>
                    <li><a aria-label="YouTube" href="https://www.youtube.com/channel/UCZvHOMf6jzLVp4Rf3A_fd1A" target="_blank"><img alt="YouTube" src="<?= $nav_base_path ?? '../../' ?>images/social/youtube.png" /> </a></li>
                    <li><a aria-label="TikTok" href="https://www.tiktok.com/@bakersfieldesportscenter" target="_blank"><img alt="TikTok" src="<?= $nav_base_path ?? '../../' ?>images/social/tiktok.png" /> </a></li>
                </ul>
            </div>
            <p>&copy; 2025 <a href="https://bakersfieldesports.com">bakersfieldesports.com</a></p>
        </div>
    </footer>

<?php if (isset($extra_scripts)): ?>
    <?php foreach ((array)$extra_scripts as $script): ?>
    <script src="<?= $script ?>"></script>
    <?php endforeach; ?>
<?php endif; ?>
<?php if (isset($extra_footer_content)) echo $extra_footer_content; ?>
</body>
</html>
