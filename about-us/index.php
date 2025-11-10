<?php
/**
 * About Us Page
 * Bakersfield eSports Center
 */

$base_path = '../';
$active_page = 'about';
$page_title = 'About Bakersfield eSports Center | Gaming in Kern County';
$page_description = "Discover Bakersfield's first locally owned and operated eSports center. We provide a unique entertainment venue for all types of gamers, from competitive eSports to casual board gaming.";
$canonical_url = 'https://bakersfieldesports.com/about-us/';

require_once '../includes/schemas.php';
$schema_markup = getOrganizationSchema();

require_once '../includes/head.php';
require_once '../includes/nav.php';
?>

<!-- Main Content -->
<main>
    <!-- Hero Section -->
    <section class="hero" style="background-image: url('../images/about-us-hero.jpg');">
        <div class="container">
            <h1>About Us</h1>
        </div>
    </section>

    <!-- Content Section -->
    <section class="content">
        <div class="container">
            <h2>Our Story</h2>
            <p>We are Bakersfield's first locally owned and operated eSports and event center, founded in 2021 by a group of passionate gamers who saw the need for a dedicated gaming space in our community. Our founders, having grown up in Kern County, recognized that our region lacked a modern facility where gamers of all backgrounds could come together to play, compete, and build lasting friendships.</p>

            <p>What started as a dream to create a small gaming cafe has evolved into a 5,000 square foot state-of-the-art facility that serves as the epicenter of gaming culture in Bakersfield. Our center was built on the principle that gaming is more than just entertainment—it's a platform for building community, developing skills, and fostering healthy competition.</p>

            <h2>Our Facility</h2>
            <p>Our state-of-the-art gaming center features:</p>
            <ul>
                <li>40 high-performance gaming PCs equipped with the latest hardware</li>
                <li>Multiple console gaming stations featuring PlayStation 5, Xbox Series X, and Nintendo Switch</li>
                <li>Virtual Reality stations with the latest VR technology</li>
                <li>Dedicated spaces for trading card games and tabletop gaming</li>
                <li>Professional streaming setup for content creators</li>
                <li>Party rooms for private events and celebrations</li>
                <li>High-speed fiber internet connection for lag-free gaming</li>
            </ul>

            <h2>Our Community Impact</h2>
            <p>Since opening our doors, we've become more than just a gaming center—we're a hub for the local gaming community. We've hosted numerous tournaments that have brought players from across California, organized STEM education programs for local schools, and provided a safe, inclusive space for gamers of all ages to pursue their passion.</p>

            <p>Our partnerships with local schools and organizations have allowed us to:</p>
            <ul>
                <li>Introduce hundreds of students to STEM through gaming</li>
                <li>Host charity tournaments benefiting local causes</li>
                <li>Create job opportunities in the growing eSports industry</li>
                <li>Provide structured after-school activities for youth</li>
            </ul>

            <h2>Our Team</h2>
            <p>Our staff consists of dedicated gaming enthusiasts who bring their expertise and passion to create an exceptional experience for our visitors. From our tournament organizers to our technical support team, every member of our staff is committed to maintaining a welcoming, competitive, and fun environment for all gamers.</p>

            <h2>What Our Community Says</h2>
            <div class="testimonials">
                <blockquote>
                    "Bakersfield eSports Center has become my second home. The staff is incredibly knowledgeable and friendly, and the facility is always well-maintained. It's great to finally have a place where gamers can come together and compete!"
                    <cite>- Michael R., Regular Member</cite>
                </blockquote>

                <blockquote>
                    "As a parent, I appreciate having a safe, supervised environment where my kids can enjoy gaming with their friends. The STEM programs have also helped spark their interest in technology and programming."
                    <cite>- Sarah T., Parent</cite>
                </blockquote>
            </div>

            <h2>Join Our Community</h2>
            <p>Whether you're a competitive eSports player, casual board gamer, tactical tabletop enthusiast, strategic TCG player, or anything in between—from the most experienced to those eager to learn—Bakersfield eSports Center is your destination for all things gaming. Our doors are open to everyone who shares our passion for gaming and community.</p>

            <p>Bring your passion, and we will supply the games!</p>
        </div>
    </section>
</main>

<?php require_once '../includes/footer-content.php'; ?>
