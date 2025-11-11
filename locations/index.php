<?php
/**
 * Locations Page - Enhanced Interactive Version
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

// Location data
$current_location = [
    'name' => 'Bakersfield eSports Event Center',
    'address' => SITE_ADDRESS,
    'phone' => SITE_PHONE,
    'phone_link' => SITE_PHONE_LINK,
    'lat' => 35.3917,
    'lng' => -119.0134,
    'hours' => [
        'Sun-Thu' => '12:00 PM - 11:00 PM',
        'Fri-Sat' => '12:00 PM - 12:00 AM'
    ],
    'amenities' => ['WiFi', 'Parking', 'Food & Drinks', 'Wheelchair Access', 'Party Rooms', 'VR Stations'],
    'image' => '../images/locations/location1.png',
    'status' => 'open'
];

$coming_soon = [
    ['name' => 'South Bakersfield', 'area' => 'South Bakersfield', 'eta' => 'TBA'],
    ['name' => 'East Bakersfield', 'area' => 'East Bakersfield', 'eta' => 'TBA']
];
?>

<!-- Custom Location Page Styles -->
<link rel="stylesheet" href="../css/locations.css">

<!-- Main Content -->
<main class="locations-page">
    <!-- Hero Section -->
    <section class="hero-locations">
        <div class="hero-overlay"></div>
        <div class="container">
            <h1>Our Locations</h1>
            <p>Find your nearest gaming destination in Kern County</p>
            <div class="hero-actions">
                <a href="#map-section" class="btn btn-primary">📍 View on Map</a>
                <a href="#contact-section" class="btn btn-secondary">📞 Get in Touch</a>
            </div>
        </div>
    </section>

    <!-- Interactive Map Section -->
    <section id="map-section" class="map-section">
        <div class="container">
            <h2>Find Us on the Map</h2>
            <div class="map-container">
                <div id="google-map" class="interactive-map"></div>
                <div class="map-controls">
                    <button class="btn-map-control" onclick="getDirections()">
                        🧭 Get Directions
                    </button>
                    <button class="btn-map-control" onclick="shareLocation()">
                        🔗 Share Location
                    </button>
                </div>
            </div>
        </div>
    </section>

    <!-- Main Location Section -->
    <section class="location-details-section">
        <div class="container">
            <div class="location-layout">
                <!-- Main Location Card -->
                <div class="main-location-card">
                    <div class="location-card-header">
                        <div class="status-badge status-open">
                            <span class="status-dot"></span> Open Now
                        </div>
                        <button class="btn-share" onclick="shareLocation()">
                            <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                <circle cx="18" cy="5" r="3"></circle>
                                <circle cx="6" cy="12" r="3"></circle>
                                <circle cx="18" cy="19" r="3"></circle>
                                <line x1="8.59" y1="13.51" x2="15.42" y2="17.49"></line>
                                <line x1="15.41" y1="6.51" x2="8.59" y2="10.49"></line>
                            </svg>
                            Share
                        </button>
                    </div>

                    <div class="location-card-image">
                        <img src="<?php echo $current_location['image']; ?>" alt="<?php echo $current_location['name']; ?>">
                        <div class="image-overlay">
                            <button class="btn-virtual-tour" onclick="openVirtualTour()">
                                🎥 Take Virtual Tour
                            </button>
                        </div>
                    </div>

                    <div class="location-card-body">
                        <h2><?php echo $current_location['name']; ?></h2>

                        <!-- Address -->
                        <div class="info-block">
                            <div class="info-icon">📍</div>
                            <div class="info-content">
                                <strong>Address</strong>
                                <p><?php echo $current_location['address']; ?></p>
                                <a href="https://www.google.com/maps/dir/?api=1&destination=<?php echo urlencode($current_location['address']); ?>"
                                   target="_blank" class="link-directions">Get Directions →</a>
                            </div>
                        </div>

                        <!-- Hours -->
                        <div class="info-block">
                            <div class="info-icon">🕒</div>
                            <div class="info-content">
                                <strong>Hours of Operation</strong>
                                <?php foreach ($current_location['hours'] as $days => $hours): ?>
                                <p><span class="days"><?php echo $days; ?>:</span> <?php echo $hours; ?></p>
                                <?php endforeach; ?>
                            </div>
                        </div>

                        <!-- Contact Methods -->
                        <div id="contact-section" class="info-block">
                            <div class="info-icon">📞</div>
                            <div class="info-content">
                                <strong>Contact Us</strong>
                                <div class="contact-methods">
                                    <a href="tel:<?php echo $current_location['phone_link']; ?>" class="contact-btn">
                                        📱 Call Now
                                    </a>
                                    <a href="sms:<?php echo $current_location['phone_link']; ?>" class="contact-btn">
                                        💬 Text Us
                                    </a>
                                    <a href="<?php echo DISCORD_URL; ?>" target="_blank" class="contact-btn">
                                        💭 Discord
                                    </a>
                                    <a href="<?php echo FACEBOOK_URL; ?>" target="_blank" class="contact-btn">
                                        👥 Facebook
                                    </a>
                                </div>
                            </div>
                        </div>

                        <!-- Amenities -->
                        <div class="info-block">
                            <div class="info-icon">✨</div>
                            <div class="info-content">
                                <strong>Amenities</strong>
                                <div class="amenities-grid">
                                    <?php foreach ($current_location['amenities'] as $amenity): ?>
                                    <span class="amenity-tag"><?php echo $amenity; ?></span>
                                    <?php endforeach; ?>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Visit Planner Sidebar -->
                <aside class="visit-planner">
                    <div class="planner-card">
                        <h3>Plan Your Visit</h3>

                        <div class="planner-section">
                            <div class="planner-label">Current Status</div>
                            <div class="status-indicator status-open">
                                <span class="status-dot"></span>
                                <span id="location-status">Open Now</span>
                            </div>
                            <div class="status-detail" id="status-detail">Closes at 11:00 PM</div>
                        </div>

                        <div class="planner-section">
                            <div class="planner-label">Expected Wait Time</div>
                            <div class="wait-time">
                                <span class="wait-time-value" id="wait-time">5-10 min</span>
                                <span class="traffic-level traffic-low">Low Traffic</span>
                            </div>
                        </div>

                        <div class="planner-section">
                            <div class="planner-label">Quick Actions</div>
                            <div class="quick-actions">
                                <a href="../rates-parties/index.html#parties" class="action-btn">
                                    🎉 Book a Party
                                </a>
                                <a href="../events/" class="action-btn">
                                    🎮 View Events
                                </a>
                                <a href="../rates-parties/index.html" class="action-btn">
                                    💵 See Rates
                                </a>
                            </div>
                        </div>

                        <div class="planner-section">
                            <div class="planner-label">Best Time to Visit</div>
                            <div class="best-times">
                                <p>🌅 <strong>Weekday Afternoons:</strong> Least busy</p>
                                <p>🌆 <strong>Friday Evenings:</strong> Most popular</p>
                            </div>
                        </div>
                    </div>
                </aside>
            </div>
        </div>
    </section>

    <!-- 360 Virtual Tour Section -->
    <section class="virtual-tour-section">
        <div class="container">
            <div class="section-header">
                <h2>Explore Before You Visit</h2>
                <p>Take a virtual tour of our gaming center</p>
            </div>

            <div class="virtual-tour-container">
                <div class="tour-placeholder">
                    <div class="tour-icon">🎥</div>
                    <h3>360° Virtual Tour</h3>
                    <p>Explore our state-of-the-art gaming facility from the comfort of your home</p>
                    <button class="btn btn-primary" onclick="openVirtualTour()">
                        Start Virtual Tour
                    </button>
                    <p class="tour-note">Virtual tour coming soon! Check back later.</p>
                </div>
            </div>
        </div>
    </section>

    <!-- Events at This Location -->
    <section class="location-events-section">
        <div class="container">
            <div class="section-header">
                <h2>Upcoming Events</h2>
                <p>What's happening at this location</p>
            </div>

            <div class="events-feed" id="location-events">
                <!-- Events will be loaded via JavaScript -->
                <div class="events-loading">
                    <div class="loading-spinner"></div>
                    <p>Loading upcoming events...</p>
                </div>
            </div>

            <div class="events-cta">
                <a href="../events/" class="btn btn-primary">View All Events</a>
            </div>
        </div>
    </section>

    <!-- Transit & Parking -->
    <section class="transit-parking-section">
        <div class="container">
            <h2>Getting Here</h2>

            <div class="transit-grid">
                <!-- Parking -->
                <div class="transit-card">
                    <div class="transit-icon">🅿️</div>
                    <h3>Parking</h3>
                    <p><strong>Free parking available</strong></p>
                    <ul>
                        <li>Large parking lot on-site</li>
                        <li>Accessible parking spaces available</li>
                        <li>Well-lit for evening events</li>
                    </ul>
                </div>

                <!-- Public Transit -->
                <div class="transit-card">
                    <div class="transit-icon">🚌</div>
                    <h3>Public Transit</h3>
                    <p><strong>GET Bus Routes Nearby</strong></p>
                    <ul>
                        <li>Route 22 - Golden State Highway</li>
                        <li>Stop: 0.2 miles away</li>
                        <li><a href="https://getbus.org/" target="_blank">View GET Bus Schedule →</a></li>
                    </ul>
                </div>

                <!-- Bike & Walk -->
                <div class="transit-card">
                    <div class="transit-icon">🚲</div>
                    <h3>Bike & Walk</h3>
                    <p><strong>Bike-friendly location</strong></p>
                    <ul>
                        <li>Bike racks available</li>
                        <li>Sidewalks from nearby areas</li>
                        <li>Safe pedestrian access</li>
                    </ul>
                </div>

                <!-- Rideshare -->
                <div class="transit-card">
                    <div class="transit-icon">🚗</div>
                    <h3>Rideshare</h3>
                    <p><strong>Easy pickup/dropoff</strong></p>
                    <ul>
                        <li>Uber & Lyft available</li>
                        <li>Designated pickup zone</li>
                        <li>Average wait: 5-10 minutes</li>
                    </ul>
                </div>
            </div>
        </div>
    </section>

    <!-- Coming Soon Locations -->
    <section class="coming-soon-section">
        <div class="container">
            <div class="section-header">
                <h2>Expanding Across Kern County</h2>
                <p>More locations coming soon to serve you better</p>
            </div>

            <div class="coming-soon-grid">
                <?php foreach ($coming_soon as $location): ?>
                <div class="coming-soon-card">
                    <div class="card-overlay"></div>
                    <img src="../images/locations/comingsoon.png" alt="<?php echo $location['name']; ?>">
                    <div class="card-content">
                        <span class="badge-coming-soon">Coming Soon</span>
                        <h3><?php echo $location['name']; ?></h3>
                        <p class="location-area"><?php echo $location['area']; ?></p>
                        <p class="location-eta">Expected: <?php echo $location['eta']; ?></p>
                        <button class="btn btn-outline" onclick="notifyMe('<?php echo $location['name']; ?>')">
                            🔔 Notify Me When Open
                        </button>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
        </div>
    </section>
</main>

<!-- Mobile Quick Actions Bar -->
<div class="mobile-actions-bar">
    <a href="tel:<?php echo $current_location['phone_link']; ?>" class="mobile-action">
        <div class="action-icon">📱</div>
        <div class="action-label">Call</div>
    </a>
    <a href="https://www.google.com/maps/dir/?api=1&destination=<?php echo urlencode($current_location['address']); ?>"
       target="_blank" class="mobile-action">
        <div class="action-icon">🧭</div>
        <div class="action-label">Directions</div>
    </a>
    <a href="#" onclick="shareLocation(); return false;" class="mobile-action">
        <div class="action-icon">🔗</div>
        <div class="action-label">Share</div>
    </a>
    <a href="../rates-parties/index.html#parties" class="mobile-action">
        <div class="action-icon">🎉</div>
        <div class="action-label">Book</div>
    </a>
</div>

<!-- Location Page JavaScript -->
<script>
// Location data for JavaScript
const locationData = {
    name: '<?php echo $current_location['name']; ?>',
    address: '<?php echo $current_location['address']; ?>',
    lat: <?php echo $current_location['lat']; ?>,
    lng: <?php echo $current_location['lng']; ?>,
    phone: '<?php echo $current_location['phone']; ?>',
    phoneLink: '<?php echo $current_location['phone_link']; ?>'
};
</script>
<script src="https://maps.googleapis.com/maps/api/js?key=YOUR_GOOGLE_MAPS_API_KEY&callback=initMap" async defer></script>
<script src="../js/locations.js"></script>

<?php require_once '../includes/footer-content.php'; ?>
