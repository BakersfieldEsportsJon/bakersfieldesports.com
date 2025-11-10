<?php
/**
 * Marketing Opt-In Page
 * Bakersfield eSports Center
 */

// Template configuration
$base_path = '';
$active_page = 'opt-in';
$page_title = 'Stay Connected | Bakersfield eSports Center';
$page_description = 'Sign up to receive exclusive SMS and email updates on tournaments, events, and special offers from Bakersfield eSports Center.';
$canonical_url = 'https://bakersfieldesports.com/opt-in.php';

require_once 'includes/head.php';
require_once 'includes/nav.php';
?>

<style>
/* Opt-in specific styles */
.opt-in-container {
  max-width: 800px;
  width: 92%;
  margin: 2rem auto;
  padding: 2rem;
  background-color: rgba(255,255,255,.05);
  border-radius: 8px;
  border: 1px solid rgba(255,255,255,.1);
}

.opt-in-container h1 {
  font-size: 2rem;
  margin-bottom: 1rem;
  color: var(--primary-color);
  text-align: center;
}

.opt-in-container .description {
  text-align: center;
  margin-bottom: 1.5rem;
  font-size: 1.05rem;
  color: #ccc;
}

.opt-in-form {
  display: flex;
  flex-direction: column;
}

.opt-in-form label {
  margin-bottom: .5rem;
  font-weight: 700;
}

.opt-in-form input[type="text"],
.opt-in-form input[type="email"],
.opt-in-form input[type="tel"] {
  padding: .65rem .75rem;
  margin-bottom: 1rem;
  border: none;
  border-radius: 4px;
  background-color: var(--darker-bg);
  color: var(--light-color);
}

.opt-in-form input[type="submit"] {
  background-color: var(--primary-color);
  color: #fff;
  border: none;
  padding: .85rem;
  font-size: 1.05rem;
  border-radius: 4px;
  cursor: pointer;
  transition: background-color .2s ease;
  margin-top: .25rem;
}

.opt-in-form input[type="submit"]:hover {
  background-color: #c00048;
}

.consent {
  margin-top: .85rem;
  font-size: .875rem;
  line-height: 1.5;
  color: #cfcfcf;
}

.consent a {
  color: var(--light-color);
  text-decoration: underline;
  text-underline-offset: 2px;
}

.consent a:hover {
  color: var(--primary-color);
}

.consent .em {
  font-weight: 700;
}

.nowrap {
  white-space: nowrap;
}

#statusMessage {
  margin-top: 1rem;
  min-height: 1.2em;
}

@media (max-width: 480px) {
  .opt-in-container {
    padding: 1.25rem;
  }
  .opt-in-container h1 {
    font-size: 1.75rem;
  }
}
</style>

<div class="opt-in-container">
  <h1>Stay Connected</h1>
  <p class="description">
    Sign up to receive exclusive SMS and email updates on tournaments, events, and special offers.
  </p>

  <form id="optInForm" class="opt-in-form" novalidate>
    <label for="name">Name</label>
    <input type="text" id="name" name="name" placeholder="Your Name" required autocomplete="name" />

    <label for="email">Email</label>
    <input type="email" id="email" name="email" placeholder="you@example.com" required autocomplete="email" />

    <label for="phone">Phone Number (SMS)</label>
    <input type="tel" id="phone" name="phone" required
           pattern="\d{3}-?\d{3}-?\d{4}"
           inputmode="tel"
           title="Enter 10 digits; dashes optional (e.g., 5555555555 or 555-555-5555)" />

    <input type="submit" id="submitBtn" value="Subscribe" />

    <!-- SMS disclosure -->
    <p class="consent" role="note" aria-label="SMS consent disclosure">
      By submitting this form, you agree to receive marketing text messages from Bakersfield eSports Center at the number provided.
      Up to <span class="nowrap">4 msgs/week</span>. <span class="nowrap">Msg &amp; data rates may apply.</span>
      Reply <span class="em">STOP</span> to cancel, <span class="em">HELP</span> for help.
      <span class="em">Consent is not a condition of purchase.</span>
      See our <a href="<?php echo $base_path; ?>privacy-policy.html">Privacy Policy</a>.
    </p>

    <p id="statusMessage" style="display:none;" aria-live="polite"></p>
  </form>
</div>

<script>
document.addEventListener('DOMContentLoaded', () => {
  const form   = document.getElementById('optInForm');
  const btn    = document.getElementById('submitBtn');
  const status = document.getElementById('statusMessage');

  const phoneRegex = /^\d{3}-?\d{3}-?\d{4}$/;

  form.addEventListener('submit', async (e) => {
    e.preventDefault();
    status.style.display = 'block';
    status.style.color = '#cccccc';
    status.textContent = 'Submitting…';

    const fd    = new FormData(form);
    const name  = (fd.get('name')  || '').toString().trim();
    const email = (fd.get('email') || '').toString().trim();
    const phone = (fd.get('phone') || '').toString().trim();

    if (!name)  { status.style.color = '#f07373'; status.textContent = 'Please enter your name.';  return; }
    if (!email) { status.style.color = '#f07373'; status.textContent = 'Please enter your email.'; return; }
    if (!phoneRegex.test(phone)) {
      status.style.color = '#f07373';
      status.textContent = 'Phone must be 10 digits; dashes optional (e.g., 6618587259 or 661-858-7259).';
      return;
    }

    btn.disabled = true; btn.value = 'Sending…';

    try {
      const res = await fetch('process_marketing_signup.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({ name, email, phone })
      });

      let json = null;
      try { json = await res.json(); } catch {}

      if (res.ok && json && json.success === true) {
        status.style.color = '#a3ffa3';
        status.textContent = 'Thank you for subscribing! We will keep you informed.';
        form.reset();
        btn.value = 'Sent!';
        setTimeout(() => { btn.value = 'Subscribe'; btn.disabled = false; }, 1200);
      } else {
        status.style.color = '#f07373';
        status.textContent = (json && json.error) ? json.error : 'Submission failed. Please try again.';
        btn.disabled = false; btn.value = 'Subscribe';
      }
    } catch {
      status.style.color = '#f07373';
      status.textContent = 'Network error. Please try again later.';
      btn.disabled = false; btn.value = 'Subscribe';
    }
  });
});
</script>

<?php require_once 'includes/footer-content.php'; ?>

</body>
</html>
