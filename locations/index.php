<?php
/**
 * Locations Page
 * Bakersfield eSports Center
 */

$base_path = '../';
$active_page = 'locations';
$page_title = 'Locations | Bakersfield eSports Center';
$page_description = 'Find the nearest Bakersfield eSports Center location. Currently serving Bakersfield with plans to expand across Kern County.';
$canonical_url = 'https://bakersfieldesports.com/locations/';

require_once '../includes/schemas.php';
$schema_markup = getLocalBusinessSchema();

require_once '../includes/head.php';
require_once '../includes/nav.php';
?>

<!-- Main Content -->
<main>
    <!-- Hero Section -->
    <section class="hero" style="background-image: url('../images/locations-hero.jpg');">
        <div class="container">
            <h1>Our Locations</h1>
            <p>Find the nearest Bakersfield eSports Center location.</p>
        </div>
    </section>

    <!-- Locations Section -->
    <section class="locations">
        <div class="container">
            <h2>Locations</h2>
            <div class="locations-grid">
                <!-- Location Item 1 -->
                <div class="location-item">
                    <div class="location-image">
                        <img alt="Bakersfield eSports Event Center" src="../images/locations/location1.png" loading="lazy" width="300" height="200">
                    </div>
                    <div class="location-details">
                        <h3>Bakersfield eSports<br>Event Center</h3>
                        <p><strong>Address:</strong> <?php echo SITE_ADDRESS; ?></p>
                        <p><strong>Phone:</strong> <a href="tel:<?php echo SITE_PHONE_LINK; ?>"><?php echo SITE_PHONE; ?></a></p>
                    </div>
                </div>
                <!-- Location Item 2 -->
                <div class="location-item">
                    <div class="location-image">
                        <img alt="South Bakersfield" src="../images/locations/comingsoon.png" loading="lazy" width="300" height="200">
                    </div>
                    <div class="location-details">
                        <h3>South Bakersfield</h3>
                        <p><strong>Status:</strong> Coming Soon</p>
                    </div>
                </div>
                <!-- Location Item 3 -->
                <div class="location-item">
                    <div class="location-image">
                        <img alt="East Bakersfield" src="../images/locations/comingsoon.png" loading="lazy" width="300" height="200">
                    </div>
                    <div class="location-details">
                        <h3>East Bakersfield</h3>
                        <p><strong>Status:</strong> Coming Soon</p>
                    </div>
                </div>
            </div>
        </div>
    </section>
</main>

<?php require_once '../includes/footer-content.php'; ?>
