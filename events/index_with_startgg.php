<?php
/**
 * Events Page - Updated with Start.gg Integration
 * This version includes both local events and synced start.gg tournaments
 *
 * To activate: Rename this file to index.php (backup the old one first)
 */

header('Content-Type: text/html; charset=utf-8');

// Include database connection and start.gg display
require_once __DIR__ . '/../admin/includes/db.php';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <title>Events - Bakersfield eSports Center</title>
    <meta name="viewport" content="width=device-width, initial-scale=1">

    <!-- Canonical Tags -->
    <link rel="canonical" href="https://bakersfieldesports.com/events/" />

    <!-- Meta Tags for SEO -->
    <meta name="description" content="Join us at Bakersfield eSports Center for exciting gaming events, tournaments, and leagues.">
    <meta name="keywords" content="eSports, Bakersfield, events, tournaments, gaming events, Fortnite League, Valorant, Rocket League, Call of Duty, Overwatch">
    <meta name="robots" content="index, follow">
    <meta name="author" content="Bakersfield eSports Center">

    <!-- Open Graph Meta Tags -->
    <meta property="og:title" content="Events - Bakersfield eSports Center">
    <meta property="og:description" content="Check out upcoming events at Bakersfield eSports Center.">
    <meta property="og:image" content="https://bakersfieldesports.com/images/events-og-image.png">
    <meta property="og:type" content="website">
    <meta property="og:url" content="https://bakersfieldesports.com/events/">

    <!-- External CSS -->
    <link href="../css/optimized.min.css" rel="stylesheet">
    <link href="../css/custom.css" rel="stylesheet">

    <!-- Google Fonts -->
    <link href="https://fonts.googleapis.com/css?family=Lato:400,900|Fugaz+One|Open+Sans:300,400,600,700,800&amp;display=swap" rel="stylesheet" />

    <!-- Favicon -->
    <link href="../images/favicons/favicon.png" rel="icon" type="image/png" />

    <style>
        .event-item.placeholder {
            background: rgba(0,0,0,0.05);
            border-radius: 8px;
            padding: 20px;
            text-align: center;
        }
        .no-events-message {
            text-align: center;
            padding: 40px;
            background: rgba(0,0,0,0.05);
            border-radius: 8px;
            grid-column: 1 / -1;
        }
        .error-message {
            text-align: center;
            padding: 40px;
            background: rgba(255,0,0,0.1);
            border-radius: 8px;
            grid-column: 1 / -1;
        }

        /* Start.gg Tournaments Section */
        .startgg-section {
            background: linear-gradient(135deg, rgba(233, 69, 96, 0.05) 0%, rgba(0, 0, 0, 0.02) 100%);
            padding: 3em 0;
            margin: 2em 0;
        }

        .startgg-section .container {
            max-width: 1200px;
        }

        .section-divider {
            display: flex;
            align-items: center;
            text-align: center;
            margin: 3em 0;
        }

        .section-divider::before,
        .section-divider::after {
            content: '';
            flex: 1;
            border-bottom: 2px solid rgba(233, 69, 96, 0.3);
        }

        .section-divider span {
            padding: 0 1em;
            color: var(--primary-color);
            font-weight: bold;
            font-size: 1.2em;
        }
    </style>

    <!-- Meta Pixel Code -->
    <script>
    !function(f,b,e,v,n,t,s)
    {if(f.fbq)return;n=f.fbq=function(){n.callMethod?
    n.callMethod.apply(n,arguments):n.queue.push(arguments)};
    if(!f._fbq)f._fbq=n;n.push=n;n.loaded=!0;n.version='2.0';
    n.queue=[];t=b.createElement(e);t.async=!0;
    t.src=v;s=b.getElementsByTagName(e)[0];
    s.parentNode.insertBefore(t,s)}(window, document,'script',
    'https://connect.facebook.net/en_US/fbevents.js');
    fbq('init', '1055372993198040');
    fbq('track', 'PageView');
    </script>
    <noscript><img height="1" width="1" style="display:none"
    src="https://www.facebook.com/tr?id=1055372993198040&ev=PageView&noscript=1"
    /></noscript>
    <!-- End Meta Pixel Code -->
</head>
<body>
    <!-- Header -->
    <header>
        <nav class="navbar">
            <div class="container">
                <a class="logo" href="../index.html">
                    <img alt="Bakersfield eSports Logo" src="../images/Asset%205-ts1621173277.png" />
                </a>
                <!-- Navigation Toggle Button -->
                <button aria-label="Toggle navigation" class="nav-toggle" id="nav-toggle">☰</button>
                <!-- Navigation Menu -->
                <ul class="nav-menu" id="nav-menu">
                    <li><a href="../index.html">Home</a></li>
                    <li><a href="../locations/index.html">Locations</a></li>
                    <li><a class="active" href="index.php">Events</a></li>
                    <li><a href="../rates-parties/index.html">Rates &amp; Parties</a></li>
                    <li><a href="../partnerships/index.html">Partnerships</a></li>
                    <li><a href="../about-us/index.html">About Us</a></li>
                    <li><a href="../gallery/index.php">Gallery</a></li>
                    <li><a href="../contact-us/index.html">Contact Us</a></li>
                    <li><a href="https://discord.gg/jbzWH3ZvRp" target="_blank">Discord</a></li>
                    <li><a href="../stem/index.html">STEM</a></li>
                </ul>
            </div>
        </nav>
    </header>

    <!-- Main Content -->
    <main>
        <!-- Hero Section -->
        <section class="hero" style="background-image: url('../images/events-hero.jpg');">
            <div class="container">
                <h1>Upcoming Events</h1>
                <p>Join us for exciting tournaments and special events at Bakersfield eSports Center.</p>
            </div>
        </section>

        <!-- Weekly Events Section -->
        <section class="weekly-events">
            <div class="container">
                <h2>Weekly Events</h2>
                <div class="events-grid">
                    <!-- Populated by events.js -->
                </div>
            </div>
        </section>

        <!-- Start.gg Tournaments Section -->
        <section class="startgg-section">
            <div class="container">
                <?php
                try {
                    require_once __DIR__ . '/../includes/startgg_tournaments_display.php';
                    displayStartGGTournaments($pdo, 12, true);
                } catch (Exception $e) {
                    // Silently fail if start.gg integration not set up yet
                    // Or display nothing
                }
                ?>
            </div>
        </section>

        <div class="section-divider">
            <span>Local Events & Leagues</span>
        </div>

        <!-- Events Section -->
        <section class="events">
            <div class="container">
                <h2>Tournaments &amp; Special Events</h2>
                <div class="events-grid">
                    <!-- Events will be dynamically populated here by events.js -->
                </div>
            </div>
        </section>

        <!-- NOR Leagues Section -->
        <section class="nor-leagues">
            <div class="container">
                <h2>NOR Leagues</h2>
                <div class="events-grid">
                    <!-- Populated by events.js -->
                </div>
            </div>
        </section>

        <!-- League of Dreams Leagues Section -->
        <section class="lod-leagues">
            <div class="container">
                <h2>League of Dreams Leagues</h2>
                <div class="events-grid">
                    <!-- Populated by events.js -->
                </div>
            </div>
        </section>
    </main>

    <!-- Footer -->
    <footer>
        <div class="footer-content">
            <div class="social-media">
                <h3>Follow Us on Social Media</h3>
                <ul>
                    <li>
                        <a aria-label="Facebook" href="https://www.facebook.com/Bakersfield-ESports-104418741131608" target="_blank">
                            <img alt="Facebook" src="../images/social/facebook.png" loading="lazy" width="32" height="32">
                        </a>
                    </li>
                    <li>
                        <a aria-label="X" href="https://x.com/Bak_eSports" target="_blank">
                            <img alt="X" src="../images/social/x.png" loading="lazy" width="32" height="32">
                        </a>
                    </li>
                    <li>
                        <a aria-label="Instagram" href="https://www.instagram.com/bakersfieldesports" target="_blank">
                            <img alt="Instagram" src="../images/social/instagram.png" loading="lazy" width="32" height="32">
                        </a>
                    </li>
                    <li>
                        <a aria-label="Twitch" href="https://www.twitch.tv/bakersfieldesportscenter" target="_blank">
                            <img alt="Twitch" src="../images/social/twitch.png" loading="lazy" width="32" height="32">
                        </a>
                    </li>
                    <li>
                        <a aria-label="YouTube" href="https://www.youtube.com/channel/UCZvHOMf6jzLVp4Rf3A_fd1A" target="_blank">
                            <img alt="YouTube" src="../images/social/youtube.png" loading="lazy" width="32" height="32">
                        </a>
                    </li>
                    <li>
                        <a aria-label="TikTok" href="https://www.tiktok.com/@bakersfieldesportscenter" target="_blank">
                            <img alt="TikTok" src="../images/social/tiktok.png" loading="lazy" width="32" height="32">
                        </a>
                    </li>
                </ul>
            </div>
            <div class="contact-info">
                <p><strong>Address:</strong> 7104 Golden State Hwy, Bakersfield, CA 93308</p>
                <p><strong>Phone:</strong> <a href="tel:+16615297447">(661) 529-7447</a></p>
            </div>
            <p>&copy; 2025 <a href="https://bakersfieldesports.com">bakersfieldesports.com</a></p>
        </div>
    </footer>

    <script>
        document.getElementById('nav-toggle').onclick = function() {
            var menu = document.getElementById('nav-menu');
            menu.classList.toggle('active');
        };
    </script>
    <script src="js/events.js"></script>
</body>
</html>
