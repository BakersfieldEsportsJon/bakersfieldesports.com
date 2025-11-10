<?php
/**
 * Contact Us Page
 * Bakersfield eSports Center
 */

// Start session for CSRF token
session_start();

// Generate CSRF token if not exists
if (!isset($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

$base_path = '../';
$active_page = 'contact';
$page_title = 'Contact Bakersfield eSports Center | Get in Touch';
$page_description = 'Contact Bakersfield eSports Center for inquiries about events, parties, or general questions. We are here to help!';
$canonical_url = 'https://bakersfieldesports.com/contact-us/';

require_once '../includes/schemas.php';
$schema_markup = getLocalBusinessSchema();

require_once '../includes/head.php';
require_once '../includes/nav.php';
?>

<!-- Main Content -->
<main>
    <!-- Hero Section -->
    <section class="hero hero-contact">
        <div class="container">
            <h1>Contact Us</h1>
        </div>
    </section>

    <section class="contact-info">
        <div class="container">
            <!-- Contact Form -->
            <div class="contact-form">
                <h2>Send Us a Message</h2>
                <form action="process_form.php" id="contact-form" method="POST">
                    <input type="hidden" name="csrf_token" id="csrf_token" value="<?php echo htmlspecialchars($_SESSION['csrf_token'], ENT_QUOTES, 'UTF-8'); ?>">
                    <div class="form-group">
                        <label for="name">Name<span class="required">*</span></label>
                        <input id="name" name="name" required type="text" maxlength="100" pattern="[a-zA-Z\s\-\.']+" class="form-input" />
                    </div>

                    <div class="form-group">
                        <label for="email">Email<span class="required">*</span></label>
                        <input id="email" name="email" required type="email" maxlength="254" pattern="[a-zA-Z0-9._%+-]+@[a-zA-Z0-9.-]+\.[a-zA-Z]{2,}" class="form-input" />
                    </div>

                    <div class="form-group">
                        <label for="subject">Subject<span class="required">*</span></label>
                        <input id="subject" name="subject" required type="text" maxlength="200" pattern="[a-zA-Z0-9\s\-\.',!?]+" class="form-input" />
                    </div>

                    <div class="form-group">
                        <label for="message">Message<span class="required">*</span></label>
                        <textarea id="message" name="message" required rows="5" maxlength="2000" class="form-input"></textarea>
                    </div>

                    <!-- Google reCAPTCHA Widget -->
                    <div class="g-recaptcha" data-sitekey="6Lf8MlQqAAAAAAVK-Uy9ecJJtS0P0FNYaA_bin-6"></div>
                    <button type="submit" class="btn btn-primary">Submit</button>
                </form>
            </div>
        </div>
    </section>
</main>

<?php require_once '../includes/footer-content.php'; ?>
