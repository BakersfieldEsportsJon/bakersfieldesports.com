<?php
/**
 * Rates & Parties Page
 * Bakersfield eSports Center
 */

$base_path = '../';
$active_page = 'rates';
$page_title = 'Rates & Parties | Bakersfield eSports Center';
$page_description = 'Explore our rates and party packages at Bakersfield eSports Center. Affordable gaming, unlimited memberships, and exciting party packages for all ages.';
$canonical_url = 'https://bakersfieldesports.com/rates-parties/';

require_once '../includes/schemas.php';
$schema_markup = getLocalBusinessSchema();

require_once '../includes/head.php';
require_once '../includes/nav.php';
?>

<!-- Main Content -->
<main>
    <!-- Hero Section -->
    <section class="hero" style="background-image: url('../images/rates-hero.jpg');">
        <div class="container">
            <h1>Rates &amp; Parties</h1>
            <p>Explore our rates and party packages at Bakersfield eSports Center.</p>
        </div>
    </section>

    <!-- Rates Section -->
    <section class="rates">
        <div class="container">
            <h2>Rates</h2>
            <div class="rates-grid">
                <!-- Unlimited Membership -->
                <div class="rate-item">
                    <h3>Unlimited Membership</h3>
                    <p>Unlimited access during operating hours!</p>
                    <h4>$250/month</h4>
                </div>

                <!-- Hourly Rates -->
                <div class="rate-item">
                    <h3>Hourly Rate</h3>
                    <p>Enjoy gaming at an hourly rate.</p>
                    <h4>$7/hour</h4>
                    <h4>$24 for 4 hours</h4>
                </div>

                <!-- Day Passes -->
                <div class="rate-item">
                    <h3>Day Passes</h3>
                    <p>Enjoy gaming for up to 12 hours!</p>
                    <h4>Monday-Friday | $35</h4>
                    <h4>Saturday-Sunday | $40</h4>
                </div>

                <!-- Night Passes -->
                <div class="rate-item">
                    <h3>Night Passes</h3>
                    <p>Enjoy gaming for the last 3 hours of the night!</p>
                    <h4>Sunday-Thursday (8pm-11pm) | $14</h4>
                    <h4>Friday-Saturday (9pm-Midnight) | $14</h4>
                </div>
            </div>
        </div>
    </section>

    <!-- Parties Section -->
    <section class="parties about" id="parties">
        <div class="container">
            <div class="party-promo">
                <img src="../images/Party.png" class="responsive-img" alt="Party Package" width="1200" height="675" loading="lazy">
                <button type="button" class="btn btn-primary" id="bookNowBtn">Book Now</button>
            </div>
        </div>
    </section>
</main>

<!-- Booking Modal -->
<div class="modal" id="bookingModal" role="dialog" aria-modal="true" aria-labelledby="bookingTitle" aria-hidden="true">
    <div class="modal-content">
        <button type="button" class="close-button" id="modalClose" aria-label="Close dialog">&times;</button>
        <h2 id="bookingTitle">Book Your Party</h2>
        <form id="bookingForm" onsubmit="handleSubmit(event)">
            <div class="form-group">
                <label for="name">Your Name:</label>
                <input type="text" id="name" name="name" required autocomplete="name" class="form-input">
            </div>

            <div class="form-group">
                <label for="phone">Phone Number:</label>
                <input type="tel" id="phone" name="phone" required autocomplete="tel" pattern="^[0-9()+\s-]{7,}$" class="form-input">
            </div>

            <div class="form-group">
                <label for="email">Email:</label>
                <input type="email" id="email" name="email" required autocomplete="email" class="form-input">
            </div>

            <div class="form-group">
                <label for="partyFor">Who is the party for?</label>
                <input type="text" id="partyFor" name="partyFor" required autocomplete="nickname" class="form-input">
            </div>

            <div class="form-group">
                <label for="age">Age:</label>
                <input type="number" id="age" name="age" required min="1" max="99" autocomplete="off" class="form-input">
            </div>

            <div class="form-group">
                <label for="partyDate">Party Date:</label>
                <input type="date" id="partyDate" name="partyDate" required class="form-input">
            </div>

            <div class="form-group">
                <label for="partyTime">Party Start Time:</label>
                <input type="time" id="partyTime" name="partyTime" required min="12:00" max="20:00" step="1800" class="form-input">
            </div>

            <div class="important-notice">
                <h3>⚠️ Important: Game Time Cannot Be Paused</h3>
                <p>Once your 2-hour game session begins, it cannot be paused or stopped. Please plan accordingly!</p>
            </div>

            <div class="form-group">
                <label for="partyFlow">Choose Your Party Flow:</label>
                <select id="partyFlow" name="partyFlow" required class="form-input">
                    <option value="">-- Select Party Flow --</option>
                    <option value="party_first">Party Area First → Pizza served immediately, then 2 hours of gaming</option>
                    <option value="games_first">Game Time First → 2 hours of gaming first, then pizza in party area</option>
                </select>
                <small class="form-help">This determines when your pizza will be served during your party.</small>
            </div>

            <div class="form-group">
                <label for="pizzaReadyTime">Pizza Ready Time:</label>
                <input type="time" id="pizzaReadyTime" name="pizzaReadyTime" required readonly class="form-input" disabled>
                <small class="form-help">Automatically calculated based on your party flow choice.</small>
            </div>

            <div class="disclaimer">
                <p>Parties are booked on a first-come, first-served basis, and we may need to adjust the timing of your party if necessary.</p>
            </div>

            <div class="form-group">
                <label for="pizzaChoice">Pizza Choice (2 included):</label>
                <select id="pizzaChoice" name="pizzaChoice" required class="form-input">
                    <option value="cheese">2 Cheese Pizzas</option>
                    <option value="pepperoni">2 Pepperoni Pizzas</option>
                    <option value="both">1 Cheese + 1 Pepperoni</option>
                </select>
            </div>

            <div class="additional-pizzas">
                <label class="checkbox-label">
                    <input type="checkbox" id="additionalPizzas" name="additionalPizzas">
                    <span>Add More Pizzas ($20 each)</span>
                </label>

                <div id="pizzaQtyWrapper" class="hidden">
                    <div class="pizza-type form-group">
                        <label for="additionalCheeseQty">Additional Cheese Pizzas:</label>
                        <input type="number" id="additionalCheeseQty" name="additionalCheeseQty"
                               min="0" max="5" value="0" disabled class="form-input">
                    </div>
                    <div class="pizza-type form-group">
                        <label for="additionalPepperoniQty">Additional Pepperoni Pizzas:</label>
                        <input type="number" id="additionalPepperoniQty" name="additionalPepperoniQty"
                               min="0" max="5" value="0" disabled class="form-input">
                    </div>
                </div>
            </div>

            <div class="payment-summary">
                <h3>Payment Summary</h3>
                <p class="contact-timeframe">We will contact you within 24 hours to confirm your booking details.</p>
                <p>Deposit: $100</p>
                <p id="additionalPizzasCost" class="hidden">Additional Pizzas: $<span id="pizzaCost">0</span></p>
                <p>Subtotal: $<span id="subtotalAmount">100.00</span></p>
                <p>Processing Fee (3.5%): $<span id="processingFee">3.50</span></p>
                <p><strong>Total: $<span id="totalAmount">103.50</span></strong></p>
            </div>

            <button type="submit" class="btn btn-primary">Proceed to Payment</button>
        </form>
    </div>
</div>

<?php require_once '../includes/footer-content.php'; ?>

<!-- Party Booking JavaScript -->
<script src="../js/party-booking.js"></script>
