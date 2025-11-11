<?php
header('Content-Type: text/html; charset=utf-8');
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
    <link href="../css/startgg-events.css" rel="stylesheet">
    <link href="../css/tournament-modal.css" rel="stylesheet">
    <link href="../css/tournament-modal-custom.css" rel="stylesheet">
    
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
                    <li><a href="../locations/">Locations</a></li>
                    <li><a class="active" href="index.php">Events</a></li>
                    <li><a href="../rates-parties/index.html">Rates &amp; Parties</a></li>
                    <li><a href="../partnerships/index.html">Partnerships</a></li>
                    <li><a href="../about-us/index.html">About Us</a></li>
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
                </div>
            </div>
        </section>

        <!-- Start.gg Tournaments Section -->
        <section class="startgg-tournaments-section">
            <div class="container">
                <h2>Tournaments &amp; Special Events</h2>
                <p class="section-description">Compete in our official tournaments hosted on Start.gg. Register now for upcoming events!</p>
                <div id="startgg-events-container" role="list" aria-live="polite" aria-busy="true">
                    <!-- Loading state -->
                    <div class="loading-tournaments" role="status" aria-label="Loading tournament events">
                        <div class="spinner" aria-hidden="true"></div>
                        <p>Loading upcoming tournaments...</p>
                    </div>
                </div>
            </div>
        </section>

        <!-- NOR Leagues Section -->
        <section class="nor-leagues">
            <div class="container">
                <h2>NOR Leagues</h2>
                <div class="events-grid">
                </div>
            </div>
        </section>

        <!-- League of Dreams Leagues Section -->
        <section class="lod-leagues">
            <div class="container">
                <h2>League of Dreams Leagues</h2>
                <div class="events-grid">
                </div>
            </div>
        </section>
    </main>

    <!-- Tournament Modal -->
    <div id="tournament-modal" class="tournament-modal" role="dialog" aria-modal="true" aria-labelledby="modal-title" style="display: none;">
        <div class="modal-overlay" id="modal-overlay"></div>
        <div class="modal-content">
            <button class="modal-close" id="modal-close" aria-label="Close modal">&times;</button>
            <div class="modal-header">
                <img id="modal-game-image" class="modal-game-image" alt="">
                <div class="modal-title-section">
                    <h2 id="modal-title"></h2>
                    <p id="modal-tournament-name" class="modal-tournament-name"></p>
                </div>
            </div>
            <div class="modal-body">
                <div class="modal-info">
                    <div class="info-item">
                        <span class="info-label">Game:</span>
                        <span id="modal-game" class="info-value"></span>
                    </div>
                    <div class="info-item">
                        <span class="info-label">Date & Time:</span>
                        <span id="modal-datetime" class="info-value"></span>
                    </div>
                    <div class="info-item">
                        <span class="info-label">Location:</span>
                        <span id="modal-location" class="info-value">Bakersfield eSports Center</span>
                    </div>
                    <div id="modal-online-badge" class="info-badge" style="display: none;">
                        <span class="badge online">Online Event</span>
                    </div>
                </div>
                <div class="modal-registration">
                    <h3>Tournament Registration</h3>
                    <div id="modal-registration-status"></div>
                    <div id="modal-registration-frame" class="registration-frame-container">
                        <iframe id="registration-iframe" class="registration-iframe" frameborder="0" allowfullscreen></iframe>
                    </div>
                </div>
            </div>
        </div>
    </div>

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
    <script src="../js/startgg-events.js?v=1761620434"></script>

    <!-- Tournament Details Modal -->
    <div id="tournamentModal" class="tournament-modal">
        <div class="tournament-modal-overlay"></div>
        <div class="tournament-modal-content">
            <button class="tournament-modal-close" aria-label="Close modal">&times;</button>

            <div class="tournament-modal-body">
                <div class="tournament-modal-loading">
                    <div class="spinner"></div>
                    <p>Loading tournament details...</p>
                </div>

                <div class="tournament-modal-data" style="display: none;">
                    <!-- Tournament Header -->
                    <div class="tournament-modal-header">
                        <h2 id="tournamentName"></h2>
                        <div class="tournament-modal-meta">
                            <div class="meta-item">
                                <span class="meta-icon">📅</span>
                                <span id="tournamentDate"></span>
                            </div>
                            <div class="meta-item">
                                <span class="meta-icon">📍</span>
                                <span id="tournamentLocation"></span>
                            </div>
                            <div class="meta-item">
                                <span class="meta-icon">👥</span>
                                <span id="tournamentAttendees"></span>
                            </div>
                        </div>
                        <div id="registrationStatus" class="registration-status-modal"></div>
                    </div>

                    <!-- Tournament Description -->
                    <div class="tournament-modal-section">
                        <h3>About This Tournament</h3>
                        <div id="tournamentDescription" class="tournament-description"></div>
                    </div>

                    <!-- Tournament Rules -->
                    <div id="tournamentRulesSection" class="tournament-modal-section" style="display: none;">
                        <h3>Rules & Format</h3>
                        <div id="tournamentRules" class="tournament-rules"></div>
                    </div>

                    <!-- Events List -->
                    <div class="tournament-modal-section">
                        <h3>Events (<span id="eventCount">0</span>)</h3>
                        <div id="tournamentEvents" class="tournament-events-list"></div>
                    </div>

                    <!-- Registration Section -->
                    <div id="registrationSection" class="tournament-modal-section">
                        <h3>Register for Tournament</h3>
                        <div class="registration-info">
                            <p>Click below to register on Start.gg. Registration will open in a new tab.</p>
                            <a id="registerButton" href="#" class="btn btn-primary btn-large" target="_blank" rel="noopener noreferrer">
                                Register on Start.gg →
                            </a>
                        </div>
                    </div>

                    <!-- Registration Iframe (Alternative) -->
                    <div id="registrationIframe" class="tournament-modal-section" style="display: none;">
                        <h3>Register for Tournament</h3>
                        <div class="iframe-container">
                            <iframe id="startggIframe" frameborder="0" allowfullscreen></iframe>
                        </div>
                    </div>
                </div>

                <div class="tournament-modal-error" style="display: none;">
                    <p>Sorry, we couldn't load the tournament details. Please try again later.</p>
                </div>
            </div>
        </div>
    </div>

    <script src="js/tournament-modal.js?v=1761620434"></script>
</body>
</html>
