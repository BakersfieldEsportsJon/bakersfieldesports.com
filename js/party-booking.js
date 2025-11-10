/**
 * Party Booking System
 * Handles modal, form validation, pricing calculations, and Stripe payment
 */

// -------- Modal Management --------
document.addEventListener('DOMContentLoaded', () => {
  const modal = document.getElementById('bookingModal');
  const bookBtn = document.getElementById('bookNowBtn');
  const closeBtn = document.getElementById('modalClose');

  if (!modal) return;

  function openModal(e) {
    if (e) e.preventDefault();
    modal.style.display = 'flex';
    modal.classList.add('active');
    modal.setAttribute('aria-hidden', 'false');
    (closeBtn || modal).focus();
  }

  function closeModal() {
    modal.style.display = 'none';
    modal.classList.remove('active');
    modal.setAttribute('aria-hidden', 'true');
    if (bookBtn) bookBtn.focus();
  }

  if (bookBtn) bookBtn.addEventListener('click', openModal);
  if (closeBtn) closeBtn.addEventListener('click', closeModal);

  // Close on outside click
  document.addEventListener('click', (e) => {
    if (e.target === modal) closeModal();
  });

  // Close on Escape key
  document.addEventListener('keydown', (e) => {
    if (e.key === 'Escape' && modal.classList.contains('active')) closeModal();
  });
});

// -------- Pizza Options & Pricing --------
document.addEventListener('DOMContentLoaded', () => {
  const addPizzasCheckbox = document.getElementById('additionalPizzas');
  const qtyWrapper = document.getElementById('pizzaQtyWrapper');
  const cheeseInput = document.getElementById('additionalCheeseQty');
  const pepperoniInput = document.getElementById('additionalPepperoniQty');
  const costElement = document.getElementById('additionalPizzasCost');

  function calculateProcessingFee(amount) {
    return amount * 0.035;
  }

  function computeSubtotal() {
    let subtotal = 100;
    const addOn = addPizzasCheckbox.checked;
    const cheese = +cheeseInput.value || 0;
    const pep = +pepperoniInput.value || 0;
    if (addOn) subtotal += (cheese + pep) * 20;
    return subtotal;
  }

  function updateTotal() {
    const subtotal = computeSubtotal();
    const fee = calculateProcessingFee(subtotal);
    const extras = (subtotal - 100);

    document.getElementById('pizzaCost').textContent = (extras > 0 ? extras : 0).toFixed(2);
    document.getElementById('subtotalAmount').textContent = subtotal.toFixed(2);
    document.getElementById('processingFee').textContent = fee.toFixed(2);
    document.getElementById('totalAmount').textContent = (subtotal + fee).toFixed(2);
  }

  function togglePizzaOptions(checked) {
    qtyWrapper.classList.toggle('hidden', !checked);
    cheeseInput.disabled = !checked;
    pepperoniInput.disabled = !checked;
    costElement.classList.toggle('hidden', !checked);

    if (!checked) {
      cheeseInput.value = 0;
      pepperoniInput.value = 0;
    }
    updateTotal();
  }

  if (addPizzasCheckbox) {
    addPizzasCheckbox.addEventListener('change', function () {
      togglePizzaOptions(this.checked);
    });
  }

  if (cheeseInput) cheeseInput.addEventListener('change', updateTotal);
  if (pepperoniInput) pepperoniInput.addEventListener('change', updateTotal);
});

// -------- Party Flow & Pizza Time Auto-Calculation --------
document.addEventListener('DOMContentLoaded', () => {
  const partyFlowSelect = document.getElementById('partyFlow');
  const partyTimeInput = document.getElementById('partyTime');
  const pizzaReadyTimeInput = document.getElementById('pizzaReadyTime');

  function calculatePizzaTime() {
    const partyFlow = partyFlowSelect.value;
    const partyTime = partyTimeInput.value;

    if (!partyFlow || !partyTime) {
      pizzaReadyTimeInput.value = '';
      return;
    }

    const [hours, minutes] = partyTime.split(':').map(Number);

    if (partyFlow === 'party_first') {
      // Pizza is served at the start
      pizzaReadyTimeInput.value = partyTime;
    } else if (partyFlow === 'games_first') {
      // Pizza is served 2 hours after start
      const pizzaHours = hours + 2;
      const pizzaTime = String(pizzaHours).padStart(2, '0') + ':' + String(minutes).padStart(2, '0');
      pizzaReadyTimeInput.value = pizzaTime;
    }
  }

  if (partyFlowSelect) {
    partyFlowSelect.addEventListener('change', calculatePizzaTime);
  }

  if (partyTimeInput) {
    partyTimeInput.addEventListener('change', calculatePizzaTime);
  }
});

// -------- Date/Time Validation --------
function validateDateTime() {
  const dateInput = document.getElementById('partyDate');
  const timeInput = document.getElementById('partyTime');

  const now = new Date();
  const minMs = now.getTime() + (48 * 60 * 60 * 1000); // 48 hours
  const maxMs = now.getTime() + (6 * 30 * 24 * 60 * 60 * 1000); // ~6 months

  const [y, m, d] = (dateInput.value || '').split('-').map(Number);
  if (!y || !m || !d) return false;

  let selected = new Date(y, m - 1, d, 12, 0, 0, 0);
  if (timeInput.value) {
    const [hh, mm] = timeInput.value.split(':').map(Number);
    selected.setHours(hh || 12, mm || 0, 0, 0);
  }

  // Restricted dates (holidays)
  const restrictedDates = ['2025-04-20', '2025-04-26', '2025-11-27', '2025-12-25'];
  if (restrictedDates.includes(dateInput.value)) {
    alert('We apologize, but this date is not available for booking. Please select another date.');
    dateInput.value = '';
    return false;
  }

  // Must be at least 48 hours in advance
  if (selected.getTime() < minMs) {
    alert('Parties must be booked at least 48 hours in advance. We will contact you within 24 hours to confirm your booking details.');
    return false;
  }

  // Cannot be more than 6 months in advance
  if (selected.getTime() > maxMs) {
    alert('Party date cannot be more than 6 months in advance');
    return false;
  }

  // Time must be between 12:00 PM and 8:00 PM
  if (timeInput.value) {
    const [h, mi] = timeInput.value.split(':').map(Number);
    const totalMinutes = h * 60 + (mi || 0);
    if (totalMinutes < 12 * 60 || totalMinutes > 20 * 60) {
      alert('Party time must be between 12:00 PM and 8:00 PM');
      return false;
    }
  }

  return true;
}

// -------- Form Submission & Stripe Payment --------
async function handleSubmit(event) {
  event.preventDefault();
  if (!validateDateTime()) return;

  const form = event.target;
  const submitButton = form.querySelector('button[type="submit"]');
  submitButton.disabled = true;

  try {
    const addPizzasCheckbox = document.getElementById('additionalPizzas');
    const cheeseInput = document.getElementById('additionalCheeseQty');
    const pepperoniInput = document.getElementById('additionalPepperoniQty');

    const subtotal = (() => {
      let s = 100;
      if (addPizzasCheckbox.checked) {
        s += ((+cheeseInput.value || 0) + (+pepperoniInput.value || 0)) * 20;
      }
      return s;
    })();

    const processingFee = subtotal * 0.035;
    const finalAmount = subtotal + processingFee;

    const cheeseQty = parseInt(form.additionalCheeseQty.value) || 0;
    const pepperoniQty = parseInt(form.additionalPepperoniQty.value) || 0;

    // Submit booking to backend
    const response = await fetch('process_booking.php', {
      method: 'POST',
      headers: { 'Content-Type': 'application/json' },
      body: JSON.stringify({
        name: form.name.value,
        phone: form.phone.value,
        email: form.email.value,
        partyFor: form.partyFor.value,
        age: form.age.value,
        partyDate: form.partyDate.value,
        partyTime: form.partyTime.value,
        partyFlow: form.partyFlow.value,
        pizzaReadyTime: form.pizzaReadyTime.value,
        pizzaChoice: form.pizzaChoice.value,
        additionalPizzas: form.additionalPizzas.checked,
        additionalCheeseQty: cheeseQty,
        additionalPepperoniQty: pepperoniQty,
        subtotal: subtotal,
        processingFee: processingFee,
        amount: finalAmount
      })
    });

    const raw = await response.text();
    let session;
    try {
      session = JSON.parse(raw);
    } catch (e) {
      console.error('Non-JSON response from process_booking.php:', raw);
      alert('There was a problem with the booking service. Please try again later.');
      submitButton.disabled = false;
      return;
    }

    if (session.error) {
      alert('Booking failed: ' + session.error);
      submitButton.disabled = false;
      return;
    }

    // Store booking reference for success page
    if (session.booking_reference) {
      sessionStorage.setItem('bookingReference', session.booking_reference);
      sessionStorage.setItem('bookingData', JSON.stringify({
        name: form.name.value,
        partyFor: form.partyFor.value,
        partyDate: form.partyDate.value,
        partyTime: form.partyTime.value
      }));
    }

    // Redirect to Stripe checkout
    if (!window.Stripe) {
      alert('Payment library failed to load. Please refresh and try again.');
      submitButton.disabled = false;
      return;
    }

    const stripe = Stripe('pk_live_51MTRSXLWWKtj9NWo8qGFretrkekt8FsjAtj5aArYGdeI4jwXGhjUnic5c0iD2KSOoVLF2KJ8RwIaMqtBXcsVmpQQ00JbMS3iZm');
    const result = await stripe.redirectToCheckout({ sessionId: session.id });

    if (result.error) {
      alert('Payment failed: ' + result.error.message);
      submitButton.disabled = false;
    }
  } catch (error) {
    console.error('Error:', error);
    alert('An error occurred. Please try again.');
    submitButton.disabled = false;
  }
}
