<?php
/**
 * Site-wide configuration
 * Defines constants and settings used across the site
 */

// Site information
define('SITE_NAME', 'Bakersfield eSports Center');
define('SITE_URL', 'https://bakersfieldesports.com');
define('SITE_PHONE', '(661) 529-7447');
define('SITE_PHONE_LINK', '+16615297447');
define('SITE_ADDRESS', '7104 Golden State Hwy, Bakersfield, CA 93308');

// Social media links
define('FACEBOOK_URL', 'https://www.facebook.com/Bakersfield-ESports-104418741131608');
define('TWITTER_URL', 'https://x.com/Bak_eSports');
define('INSTAGRAM_URL', 'https://www.instagram.com/bakersfieldesports');
define('TWITCH_URL', 'https://www.twitch.tv/bakersfieldesportscenter');
define('YOUTUBE_URL', 'https://www.youtube.com/channel/UCZvHOMf6jzLVp4Rf3A_fd1A');
define('TIKTOK_URL', 'https://www.tiktok.com/@bakersfieldesportscenter');
define('DISCORD_URL', 'https://discord.gg/jbzWH3ZvRp');

// Analytics
define('GA_ID', 'G-09FBJQFRQE');
define('FB_PIXEL_ID', '1055372993198040');

// Default meta information
$default_meta = [
    'description' => "Bakersfield eSports is your place to play or compete in your favorite games with your friends and take part in exclusive tournaments and leagues.",
    'keywords' => "eSports, Bakersfield, gaming, tournaments, leagues, virtual reality, board games, card games",
    'og_image' => SITE_URL . '/images/4f89135c19afc30d06cbfb9a8d6fdc11_fit.png',
    'twitter_handle' => '@Bak_eSports'
];
