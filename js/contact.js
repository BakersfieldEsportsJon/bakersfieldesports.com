// Handle form submission and validation
document.addEventListener('DOMContentLoaded', function() {
    // Fetch CSRF token from server
    fetch('process_form.php?csrf=1')
        .then(response => response.json())
        .then(data => {
            document.getElementById('csrf_token').value = data.token;
        });

    // Handle form submission
    document.getElementById('contact-form').addEventListener('submit', function(e) {
        e.preventDefault();
        
        // Clear previous errors
        document.querySelectorAll('.error-message').forEach(el => el.remove());
        document.querySelectorAll('.form-group').forEach(el => el.classList.remove('error'));
        
        const form = e.target;
        const formData = new FormData(form);
        
        fetch(form.action, {
            method: 'POST',
            body: formData
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                // Show success message
                const successMsg = document.createElement('div');
                successMsg.className = 'success-message';
                successMsg.textContent = 'Message sent successfully!';
                form.parentNode.insertBefore(successMsg, form);
                form.reset();
                
                // Remove success message after 5 seconds
                setTimeout(() => successMsg.remove(), 5000);
            } else {
                // Handle validation errors
                if (data.errors) {
                    Object.entries(data.errors).forEach(([field, message]) => {
                        const input = form.querySelector(`[name="${field}"]`);
                        if (input) {
                            const errorMsg = document.createElement('div');
                            errorMsg.className = 'error-message';
                            errorMsg.textContent = message;
                            input.parentNode.classList.add('error');
                            input.parentNode.appendChild(errorMsg);
                        }
                    });
                } else {
                    // Show generic error
                    const errorMsg = document.createElement('div');
                    errorMsg.className = 'error-message';
                    errorMsg.textContent = data.message || 'An error occurred. Please try again.';
                    form.parentNode.insertBefore(errorMsg, form);
                }
            }
        })
        .catch(error => {
            console.error('Error:', error);
            const errorMsg = document.createElement('div');
            errorMsg.className = 'error-message';
            errorMsg.textContent = 'An error occurred. Please try again.';
            form.parentNode.insertBefore(errorMsg, form);
        });
    });
});
