<?php
/**
 * Partnerships Page
 * Bakersfield eSports Center
 */

$base_path = '../';
$active_page = 'partnerships';
$page_title = 'Partner With Us | Bakersfield eSports Center';
$page_description = 'Join us in shaping the future of gaming and entertainment in Kern County. Explore partnership opportunities with Bakersfield eSports Center.';
$canonical_url = 'https://bakersfieldesports.com/partnerships/';

require_once '../includes/schemas.php';
$schema_markup = getOrganizationSchema();

require_once '../includes/head.php';
require_once '../includes/nav.php';
?>

<!-- Main Content -->
<main>
    <!-- Hero Section -->
    <section class="hero" style="background-image: url('../images/partnerships-hero.jpg');">
        <div class="container">
            <h1>Partner With Us</h1>
            <p>Join us in shaping the future of gaming and entertainment in Kern County.</p>
        </div>
    </section>

    <!-- Industry Overview Section -->
    <section class="industry-overview">
        <div class="container">
            <h2>The Growing Gaming Industry</h2>
            <p>The gaming and eSports industry continues to experience unprecedented growth, creating unique opportunities for businesses to connect with engaged audiences:</p>

            <div class="stats-grid">
                <div class="stat-item">
                    <h3>$200B+</h3>
                    <p>Global gaming market value in 2024</p>
                </div>
                <div class="stat-item">
                    <h3>3.2B</h3>
                    <p>Active gamers worldwide</p>
                </div>
                <div class="stat-item">
                    <h3>25%</h3>
                    <p>Annual growth in eSports viewership</p>
                </div>
                <div class="stat-item">
                    <h3>18-34</h3>
                    <p>Core demographic age range</p>
                </div>
            </div>
        </div>
    </section>

    <!-- Partnership Tiers Section -->
    <section class="partnership-tiers">
        <div class="container">
            <h2>Partnership Opportunities</h2>
            <p class="section-intro">We offer flexible partnership tiers designed to meet your business objectives while supporting the growing gaming community in Bakersfield.</p>

            <div class="tiers-grid">
                <!-- Platinum Tier -->
                <div class="tier-item">
                    <h3>Platinum Partner</h3>
                    <p class="tier-description">Premium visibility and maximum engagement opportunities.</p>
                    <ul class="benefits">
                        <li>Naming rights for major tournaments</li>
                        <li>Prominent logo placement in facility</li>
                        <li>Featured in all marketing materials</li>
                        <li>VIP access to all events</li>
                        <li>Custom branded gaming station</li>
                        <li>Social media promotion package</li>
                        <li>Monthly private event allocation</li>
                        <li>First right of refusal on new opportunities</li>
                    </ul>
                </div>

                <!-- Gold Tier -->
                <div class="tier-item">
                    <h3>Gold Partner</h3>
                    <p class="tier-description">Significant brand presence and regular engagement opportunities.</p>
                    <ul class="benefits">
                        <li>Tournament sponsorship opportunities</li>
                        <li>Logo placement in key areas</li>
                        <li>Regular marketing inclusion</li>
                        <li>Quarterly private event allocation</li>
                        <li>Social media promotion</li>
                        <li>Event booth opportunities</li>
                        <li>Partnership announcement campaign</li>
                    </ul>
                </div>

                <!-- Silver Tier -->
                <div class="tier-item">
                    <h3>Silver Partner</h3>
                    <p class="tier-description">Essential visibility and community engagement package.</p>
                    <ul class="benefits">
                        <li>Logo placement in facility</li>
                        <li>Event sponsorship opportunities</li>
                        <li>Bi-annual private event allocation</li>
                        <li>Social media mentions</li>
                        <li>Website presence</li>
                        <li>Community event participation</li>
                    </ul>
                </div>
            </div>
        </div>
    </section>

    <!-- Success Stories Section -->
    <section class="success-stories">
        <div class="container">
            <h2>Partnership Success Stories</h2>

            <!-- Valley Strong Credit Union Case Study -->
            <div class="case-study">
                <h3>Valley Strong Credit Union</h3>
                <div class="case-content">
                    <div class="case-image">
                        <img src="../images/ValleyStrong-ts1630115307.png" alt="Valley Strong Credit Union Partnership" loading="lazy" width="300" height="200">
                    </div>
                    <div class="case-details">
                        <p>Valley Strong Credit Union partnered with us to create the "Future of Finance" gaming tournament series, combining financial literacy with competitive gaming:</p>
                        <ul>
                            <li>Reached 500+ young adults in Kern County</li>
                            <li>20% increase in youth account openings</li>
                            <li>Featured in local media coverage</li>
                            <li>Created lasting community impact</li>
                        </ul>
                    </div>
                </div>
            </div>

            <!-- Visit Bakersfield Case Study -->
            <div class="case-study">
                <h3>Visit Bakersfield</h3>
                <div class="case-content">
                    <div class="case-image">
                        <img src="../images/VisitBakersfieldWhite-ts1701147614.png" alt="Visit Bakersfield Partnership" loading="lazy" width="300" height="200">
                    </div>
                    <div class="case-details">
                        <p>Collaboration with Visit Bakersfield brought regional gaming tournaments to our city:</p>
                        <ul>
                            <li>Attracted 1000+ visitors to Bakersfield</li>
                            <li>Generated significant local economic impact</li>
                            <li>Established Bakersfield as a gaming destination</li>
                            <li>Created ongoing tourism opportunities</li>
                        </ul>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- Community Impact Section -->
    <section class="community-impact">
        <div class="container">
            <h2>Community Impact</h2>
            <p>Our partnerships extend beyond business objectives to create lasting positive impact in Kern County:</p>

            <div class="impact-grid">
                <div class="impact-item">
                    <h3>Education</h3>
                    <ul>
                        <li>STEM education programs reaching 1000+ students</li>
                        <li>Coding workshops and game development classes</li>
                        <li>Scholarship opportunities for local students</li>
                    </ul>
                </div>

                <div class="impact-item">
                    <h3>Economic Growth</h3>
                    <ul>
                        <li>Created 20+ local jobs</li>
                        <li>Attracted regional tournaments and visitors</li>
                        <li>Supported local business ecosystem</li>
                    </ul>
                </div>

                <div class="impact-item">
                    <h3>Youth Development</h3>
                    <ul>
                        <li>Safe, supervised gaming environment</li>
                        <li>Leadership development through eSports</li>
                        <li>College scholarship opportunities</li>
                    </ul>
                </div>
            </div>
        </div>
    </section>

    <!-- Contact Section -->
    <section class="partnership-contact">
        <div class="container">
            <h2>Start Your Partnership Journey</h2>
            <p>Ready to explore partnership opportunities with Bakersfield eSports Center? Contact our partnerships team to discuss how we can create value together.</p>

            <div class="contact-options">
                <div class="contact-item">
                    <h3>Email Us</h3>
                    <p><a href="mailto:partnerships@bakersfieldesports.com">partnerships@bakersfieldesports.com</a></p>
                </div>

                <div class="contact-item">
                    <h3>Call Us</h3>
                    <p><a href="tel:<?php echo SITE_PHONE_LINK; ?>"><?php echo SITE_PHONE; ?></a></p>
                </div>

                <div class="contact-item">
                    <h3>Visit Us</h3>
                    <p><?php echo SITE_ADDRESS; ?><br>Bakersfield, CA 93308</p>
                </div>
            </div>
        </div>
    </section>
</main>

<?php require_once '../includes/footer-content.php'; ?>
