/**
 * Notification System - Location Opening Alerts
 * Bakersfield eSports Center
 */

(function() {
    'use strict';

    let selectedLocationName = '';

    // ============================================
    // OPEN NOTIFICATION MODAL
    // ============================================

    window.openNotificationModal = function(locationName) {
        selectedLocationName = locationName;
        const modal = document.getElementById('notificationModal');
        const locationNameSpan = document.getElementById('modalLocationName');

        if (locationNameSpan) {
            locationNameSpan.textContent = locationName;
        }

        // Pre-select the location in the form
        const locationRadios = document.querySelectorAll('input[name="location"]');
        locationRadios.forEach(radio => {
            if (radio.value === locationName) {
                radio.checked = true;
            }
        });

        // Show modal
        if (modal) {
            modal.classList.add('active');
            document.body.style.overflow = 'hidden';
        }

        // Reset form and messages
        resetForm();
    };

    // ============================================
    // CLOSE NOTIFICATION MODAL
    // ============================================

    window.closeNotificationModal = function() {
        const modal = document.getElementById('notificationModal');
        if (modal) {
            modal.classList.remove('active');
            document.body.style.overflow = '';
        }
        resetForm();
    };

    // Close modal when clicking outside
    document.addEventListener('click', function(e) {
        const modal = document.getElementById('notificationModal');
        if (e.target === modal) {
            closeNotificationModal();
        }
    });

    // Close modal on Escape key
    document.addEventListener('keydown', function(e) {
        if (e.key === 'Escape') {
            closeNotificationModal();
        }
    });

    // ============================================
    // FORM VALIDATION
    // ============================================

    function validateForm(formData) {
        let isValid = true;
        const errors = {};

        // Validate name
        if (!formData.name || formData.name.trim().length < 2) {
            errors.name = 'Please enter your full name';
            isValid = false;
        }

        // Validate email
        const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
        if (!formData.email || !emailRegex.test(formData.email)) {
            errors.email = 'Please enter a valid email address';
            isValid = false;
        }

        // Validate phone (optional, but if provided must be valid)
        if (formData.phone && formData.phone.trim().length > 0) {
            const phoneRegex = /^[\d\s\(\)\-\+]+$/;
            if (!phoneRegex.test(formData.phone) || formData.phone.replace(/\D/g, '').length < 10) {
                errors.phone = 'Please enter a valid phone number';
                isValid = false;
            }
        }

        // Validate location
        if (!formData.location) {
            errors.location = 'Please select a location';
            isValid = false;
        }

        return { isValid, errors };
    }

    function showFieldErrors(errors) {
        // Clear all previous errors
        document.querySelectorAll('.form-group').forEach(group => {
            group.classList.remove('error');
        });

        // Show new errors
        Object.keys(errors).forEach(field => {
            const formGroup = document.querySelector(`#notify-${field}`)?.closest('.form-group') ||
                             document.querySelector(`input[name="${field}"]`)?.closest('.form-group');

            if (formGroup) {
                formGroup.classList.add('error');
                const errorSpan = formGroup.querySelector('.form-error');
                if (errorSpan) {
                    errorSpan.textContent = errors[field];
                }
            }
        });
    }

    // ============================================
    // SUBMIT NOTIFICATION
    // ============================================

    window.submitNotification = async function(event) {
        event.preventDefault();

        const form = document.getElementById('notificationForm');
        const submitBtn = document.getElementById('submitBtn');
        const formData = {
            name: document.getElementById('notify-name').value.trim(),
            email: document.getElementById('notify-email').value.trim(),
            phone: document.getElementById('notify-phone').value.trim(),
            location: document.querySelector('input[name="location"]:checked')?.value,
            timestamp: new Date().toISOString(),
            source: 'Location Page - Coming Soon Notification'
        };

        // Validate form
        const validation = validateForm(formData);
        if (!validation.isValid) {
            showFieldErrors(validation.errors);
            return;
        }

        // Clear any field errors
        showFieldErrors({});

        // Show loading state
        submitBtn.disabled = true;
        submitBtn.classList.add('loading');

        try {
            // Get webhook URL from backend
            const webhookUrl = await getWebhookUrl();

            if (!webhookUrl) {
                throw new Error('Webhook URL not configured. Please contact the administrator.');
            }

            // Send data to webhook (Zapier/Make)
            const response = await fetch(webhookUrl, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                },
                body: JSON.stringify(formData)
            });

            if (!response.ok) {
                throw new Error('Failed to submit notification request');
            }

            // Show success message
            showMessage('success', 'Success!', `Thanks ${formData.name}! We'll notify you when ${formData.location} opens.`);

            // Reset form after short delay
            setTimeout(() => {
                closeNotificationModal();
            }, 3000);

        } catch (error) {
            console.error('Notification submission error:', error);
            showMessage('error', 'Oops!', error.message || 'Something went wrong. Please try again later.');
        } finally {
            submitBtn.disabled = false;
            submitBtn.classList.remove('loading');
        }
    };

    // ============================================
    // GET WEBHOOK URL FROM BACKEND
    // ============================================

    async function getWebhookUrl() {
        try {
            const response = await fetch('../api/notification-webhook.php');
            const data = await response.json();

            if (data.success && data.webhookUrl) {
                return data.webhookUrl;
            }

            return null;
        } catch (error) {
            console.error('Error fetching webhook URL:', error);
            return null;
        }
    }

    // ============================================
    // SHOW MESSAGES
    // ============================================

    function showMessage(type, title, text) {
        const messageDiv = document.getElementById('notificationMessage');
        const titleEl = document.getElementById('messageTitle');
        const textEl = document.getElementById('messageText');

        if (messageDiv && titleEl && textEl) {
            titleEl.textContent = title;
            textEl.textContent = text;

            messageDiv.className = 'notification-message ' + type + ' show';

            // Auto-hide after 5 seconds for errors
            if (type === 'error') {
                setTimeout(() => {
                    messageDiv.classList.remove('show');
                }, 5000);
            }
        }
    }

    // ============================================
    // RESET FORM
    // ============================================

    function resetForm() {
        const form = document.getElementById('notificationForm');
        if (form) {
            form.reset();
        }

        // Clear all errors
        document.querySelectorAll('.form-group').forEach(group => {
            group.classList.remove('error');
        });

        // Hide messages
        const messageDiv = document.getElementById('notificationMessage');
        if (messageDiv) {
            messageDiv.classList.remove('show');
        }
    }

    // ============================================
    // UPDATE notifyMe FUNCTION IN locations.js
    // ============================================

    // Override the global notifyMe function from locations.js
    window.notifyMe = function(locationName) {
        openNotificationModal(locationName);
    };

    console.log('✉️ Notification system initialized');

})();
