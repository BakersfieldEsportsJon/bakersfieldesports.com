const form = document.querySelector('form');

form.addEventListener('submit', (e) => {
    e.preventDefault();
    
    const captchaResponse = grecaptcha.getResponse();
    
    if (!captchaResponse.length > 0) {
        alert('Please complete the CAPTCHA');
        return;
    }
    
    // Submit form data via fetch API
    const formData = new FormData(form);
    fetch(form.action, {
        method: 'POST',
        body: formData
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            alert('Message sent successfully!');
            form.reset();
            grecaptcha.reset();
        } else {
            alert('Error: ' + data.message);
        }
    })
    .catch(error => {
        console.error('Error:', error);
        alert('An error occurred while sending your message');
    });
});
