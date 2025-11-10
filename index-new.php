<?php
/**
 * Homepage - Bakersfield eSports Center
 * Optimized version using PHP templates
 */

// Page configuration
$base_path = '';
$active_page = 'home';

$page_title = 'Bakersfield eSports Center | Gaming, Tournaments & Events in Kern County';
$page_description = "Bakersfield eSports is your place to play or compete in your favorite games with your friends and take part in exclusive tournaments and leagues.";
$canonical_url = 'https://bakersfieldesports.com/';

// Load schemas
require_once 'includes/schemas.php';

// Combine multiple schemas for homepage
$schema_markup = getLocalBusinessSchema();
// Note: Event schemas will be dynamically loaded from start.gg API in future update
$schema_markup .= "\n    </script>\n    <script type=\"application/ld+json\">\n    " . getFAQSchema();
$schema_markup .= "\n    </script>\n    <script type=\"application/ld+json\">\n    " . getProductsSchema();

// Preload hero image
$preload_images = [];

// Include head
require_once 'includes/head.php';

// Include navigation
require_once 'includes/nav.php';
?>

<!-- Main Content -->
<main>
    <!-- Hero Section -->
    <section class="hero hero-home">
        <div class="container">
            <h1>Welcome to Bakersfield eSports Center</h1>
            <p>Your place to play or compete in your favorite games with friends and take part in exclusive tournaments and leagues.</p>
            <a class="btn btn-primary" href="events/">View Upcoming Events</a><br>
            <a class="btn btn-primary" href="rates-parties/index.html#parties">Book a Party</a>
        </div>
    </section>

    <!-- About Section -->
    <section class="about">
        <div class="container">
            <h2>We're Open!</h2>
            <p>We have opened our 5,000 square foot eSports and event center. Play or compete in your favorite games online or with friends, and take part in exclusive tournaments and league nights.</p>
            <p>Our state-of-the-art center features high-performance gaming PCs equipped with the latest hardware, next-generation gaming consoles, immersive virtual reality stations, challenging VR escape rooms, spacious party rooms, and much more! Whether you're a casual gamer or a competitive esports athlete, we have the perfect setup for you.</p>

            <p>For tabletop gaming enthusiasts, we have dedicated spaces for trading card games like Magic: The Gathering, Pokémon TCG, Yu-Gi-Oh!, and casual card games like UNO. Our weekly tournaments and friendly matches bring together Bakersfield's vibrant gaming community in a welcoming, competitive environment.</p>

            <p>Looking for a unique team building experience? We've got you covered! Our corporate events combine the excitement of gaming with team-building exercises, perfect for businesses in Kern County looking to strengthen their team dynamics. From collaborative VR experiences to multiplayer strategy games, we create memorable events that foster teamwork and communication.</p>

            <p>Located in the heart of Bakersfield, our center is more than just a gaming space – it's a community hub where gamers of all skill levels come together to play, compete, and connect. Join our regular tournaments, participate in local leagues, or simply enjoy casual gaming with friends in a comfortable, professionally managed environment.</p>
            <h3>Hours of Operation</h3>
            <ul>
                <li><strong>Sunday - Thursday:</strong> 12 P.M. - 11 P.M.</li>
                <li><strong>Friday &amp; Saturday:</strong> 12 P.M. - 12 A.M.</li>
            </ul>
        </div>
    </section>

    <!-- Top Games Section -->
    <section class="top-games">
        <div class="container">
            <h2>Top Games at Bakersfield eSports Center</h2>
            <p>Experience the latest and most popular games in our premium gaming environment. From fast-paced esports titles to immersive single-player adventures, we offer a diverse selection of games across all platforms. Our game library is regularly updated to include new releases and community favorites.</p>
            <div class="games-grid">
                <div class="games-row">
                    <?php for ($i = 1; $i <= 5; $i++): ?>
                    <div class="game-item">
                        <img src="images/game<?php echo $i; ?>.png" alt="Game <?php echo $i; ?>" loading="lazy" width="150" height="150">
                    </div>
                    <?php endfor; ?>
                </div>
                <div class="games-row">
                    <?php for ($i = 6; $i <= 10; $i++): ?>
                    <div class="game-item">
                        <img src="images/game<?php echo $i; ?>.png" alt="Game <?php echo $i; ?>" loading="lazy" width="150" height="150">
                    </div>
                    <?php endfor; ?>
                </div>
            </div>
        </div>
    </section>

    <!-- Partners Section -->
    <section class="partners">
        <div class="container">
            <h2>Our Partners</h2>
            <p>We're proud to partner with leading organizations in Bakersfield and beyond. These partnerships enable us to provide exceptional gaming experiences, support local events, and contribute to our community's growth. Together with our partners, we're building a stronger, more connected gaming ecosystem in Kern County.</p>
            <div class="partner-logos">
                <?php
                $partners = [
                    ['url' => 'https://www.valleystrong.com', 'image' => 'ValleyStrong-ts1630115307.png', 'alt' => 'Valley Strong Credit Union'],
                    ['url' => 'https://www.visitbakersfield.com/', 'image' => 'VisitBakersfieldWhite-ts1701147614.png', 'alt' => 'Visit Bakersfield'],
                    ['url' => 'https://www.facebook.com/InnovativeBusinessDesigns/', 'image' => 'Innovative%20Advantage%20Final%20Logo-ts1701147761.png', 'alt' => 'Innovative Advantage'],
                    ['url' => 'http://thebakersfieldfox.com/', 'image' => 'Asset%202-ts1652421198.png', 'alt' => 'Bakersfield Fox Theater']
                ];

                foreach ($partners as $partner):
                ?>
                <a href="<?php echo htmlspecialchars($partner['url']); ?>" target="_blank" rel="noopener noreferrer">
                    <img alt="<?php echo htmlspecialchars($partner['alt']); ?>"
                         src="images/<?php echo $partner['image']; ?>"
                         loading="lazy"
                         width="200"
                         height="100">
                </a>
                <?php endforeach; ?>
            </div>
        </div>
    </section>

    <!-- Purchase Section -->
    <section class="purchase">
        <div class="container">
            <h2>Gift Card</h2>
            <!-- Stripe Buy Buttons -->
            <div class="buy-buttons">
                <script async src="https://js.stripe.com/v3/buy-button.js"></script>
                <stripe-buy-button
                    buy-button-id="buy_btn_1PqgrFLWWKtj9NWo0PjQJCFu"
                    publishable-key="pk_live_51MTRSXLWWKtj9NWo8qGFretrkekt8FsjAtj5aArYGdeI4jwXGhjUnic5c0iD2KSOoVLF2KJ8RwIaMqtBXcsVmpQQ00JbMS3iZm">
                </stripe-buy-button>
            </div>
        </div>
    </section>
</main>

<?php
// Include footer
require_once 'includes/footer-content.php';
?>
