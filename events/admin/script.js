document.addEventListener('DOMContentLoaded', () => {
    // DOM Elements
    const addEventForm = document.getElementById('addEventForm');
    const editEventForm = document.getElementById('editEventForm');
    
    // Recurrence toggle handlers
    const isRecurringCheckbox = document.getElementById('isRecurring');
    const recurrenceFrequencyGroup = document.getElementById('recurrenceFrequencyGroup');
    const editIsRecurringCheckbox = document.getElementById('editIsRecurring');
    const editRecurrenceFrequencyGroup = document.getElementById('editRecurrenceFrequencyGroup');
    
    if (isRecurringCheckbox && recurrenceFrequencyGroup) {
        isRecurringCheckbox.addEventListener('change', (e) => {
            recurrenceFrequencyGroup.style.display = e.target.checked ? 'block' : 'none';
        });
    }
    
    if (editIsRecurringCheckbox && editRecurrenceFrequencyGroup) {
        editIsRecurringCheckbox.addEventListener('change', (e) => {
            editRecurrenceFrequencyGroup.style.display = e.target.checked ? 'block' : 'none';
        });
    }

    const eventsContainer = document.getElementById('eventsContainer');
    const addEventModal = document.getElementById('addEventModal');
    const manageEventsModal = document.getElementById('manageEventsModal');
    const editFormSection = document.querySelector('.events-admin-edit-form');
    let selectedImage = '';
    let editingImage = '';

    // Modal handling
    const addEventBtn = document.getElementById('addEventBtn');
    const manageEventsBtn = document.getElementById('manageEventsBtn');

    // Open modals
    if (!addEventBtn) {
        console.error('Add Event button not found');
    } else {
        addEventBtn.addEventListener('click', async () => {
            console.log('Add Event button clicked');
            if (!addEventModal) {
                console.error('Add Event modal not found');
                return;
            }
            
            addEventModal.classList.add('show');
            resetAddForm();
            
            try {
                await loadEventImages();
                console.log('Images loaded successfully');
            } catch (error) {
                console.error('Failed to load images:', error);
            }
        });
    }

    if (!manageEventsBtn) {
        console.error('Manage Events button not found');
    } else {
        manageEventsBtn.addEventListener('click', async () => {
            console.log('Manage Events button clicked');
            if (!manageEventsModal) {
                console.error('Manage Events modal not found');
                return;
            }
            
            manageEventsModal.classList.add('show');
            
            try {
                await loadEvents();
                console.log('Events loaded successfully');
            } catch (error) {
                console.error('Failed to load events:', error);
            }
        });
    }

    // Close modals
    document.querySelectorAll('.close').forEach(closeBtn => {
        closeBtn.addEventListener('click', () => {
            if (addEventModal) {
                addEventModal.classList.remove('show');
            }
            if (manageEventsModal) {
                manageEventsModal.classList.remove('show');
            }
            if (editFormSection) {
                editFormSection.classList.remove('show');
            }
            resetAddForm();
            resetEditForm();
        });
    });

    // Close modal when clicking outside
    window.addEventListener('click', (event) => {
        if (event.target === addEventModal) {
            addEventModal.classList.remove('show');
            resetAddForm();
        }
        if (event.target === manageEventsModal) {
            manageEventsModal.classList.remove('show');
            editFormSection.classList.remove('show');
            resetEditForm();
        }
    });

    // Cancel buttons
    const cancelBtn = document.querySelector('.cancel-btn');
    if (cancelBtn) {
        cancelBtn.addEventListener('click', () => {
            addEventModal.classList.remove('show');
            resetAddForm();
        });
    }

    const cancelEditBtn = document.querySelector('.cancel-edit-btn');
    if (cancelEditBtn) {
        cancelEditBtn.addEventListener('click', () => {
            editFormSection.classList.remove('show');
            resetEditForm();
        });
    }

    // Load existing event images
    async function loadEventImages(isEdit = false) {
        const gridId = isEdit ? 'editImageGrid' : 'imageGrid';
        const previewId = isEdit ? 'editSelectedPreview' : 'selectedPreview';
        const errorId = isEdit ? 'editUploadError' : 'uploadError';
        
        try {
            const grid = document.getElementById(gridId);
            if (!grid) {
                console.error(`Grid element not found: ${gridId}`);
                return;
            }
            
            const errorDiv = document.getElementById(errorId);
            if (errorDiv) {
                errorDiv.style.display = 'none';
            }
            
            grid.innerHTML = '';
            
            const response = await fetch('get_event_images.php');
            if (!response.ok) throw new Error('Failed to load images');
            
            const data = await response.json();
            if (!data.success) throw new Error(data.error || 'Failed to load images');
            
            if (!Array.isArray(data.images)) {
                throw new Error('Invalid image data received');
            }
            
            data.images.forEach(image => {
                const imageDiv = document.createElement('div');
                imageDiv.className = 'events-admin-image-item';
                imageDiv.innerHTML = `
                    <img src="${image.path}" alt="Event Image" style="max-width: 150px; height: 150px; object-fit: cover;">
                `;
                
                imageDiv.addEventListener('click', () => {
                    document.querySelectorAll(`#${gridId} .events-admin-image-item`).forEach(item => {
                        item.classList.remove('selected');
                    });
                    
                    imageDiv.classList.add('selected');
                    
                    if (isEdit) {
                        editingImage = image.path;
                        // Update hidden input
                        const imageInput = document.getElementById('editImageInput');
                        if (imageInput) {
                            imageInput.value = image.path;
                        }
                        console.log('Selected image for edit:', editingImage);
                    } else {
                        selectedImage = image.path;
                    }
                    
                    const previewImg = document.getElementById(previewId);
                    if (previewImg) {
                        previewImg.src = image.path;
                        previewImg.style.display = 'block';
                        previewImg.parentElement.querySelector('.upload-prompt').style.display = 'none';
                    }
                });
                
                grid.appendChild(imageDiv);
            });
        } catch (error) {
            console.error('Error loading images:', error);
            const errorDiv = document.getElementById(errorId);
            if (errorDiv) {
                errorDiv.textContent = error.message;
                errorDiv.style.display = 'block';
            }
        }
    }

    // Load and display events
    async function loadEvents() {
        try {
            console.log('Loading events...');
            const response = await fetch('../data/events.json');
            if (!response.ok) {
                throw new Error(`Failed to load events: ${response.status} ${response.statusText}`);
            }
            const data = await response.json();
            console.log('Events loaded:', data);
            
            if (!data.events || !Array.isArray(data.events)) {
                throw new Error('Invalid events data format');
            }
            
            const events = data.events;
            
            console.log('Events container:', eventsContainer);
            if (!eventsContainer) {
                throw new Error('Events container not found');
            }

            eventsContainer.innerHTML = '';
            console.log('Rendering events:', events);
            events.forEach((event, index) => {
                console.log(`Rendering event ${index}:`, event);
                const eventDiv = document.createElement('div');
                eventDiv.className = 'events-admin-item';
                eventDiv.dataset.eventId = event.id;
                eventDiv.innerHTML = `
                    <div class="events-admin-content">
                        <div class="events-admin-image-container">
                            ${event.image ? `<img src="${event.image}" alt="${event.name}" class="events-admin-image">` : ''}
                        </div>
                        <div class="events-admin-details">
                            <h3>${event.name}</h3>
                            <p>Location: ${event.location}</p>
                            <p>Date: ${new Date(event.date).toLocaleString()}</p>
                            <p>Entry Cost: $${event.entryCost}</p>
                            ${event.notes ? `<p>Notes: ${event.notes}</p>` : ''}
                        </div>
                        <div class="events-admin-actions">
                            <button class="events-admin-btn edit-btn" data-event-id="${event.id}">Edit</button>
                            <button class="events-admin-btn events-admin-btn-danger delete-btn" data-event-id="${event.id}">Delete</button>
                        </div>
                    </div>
                `;
                eventsContainer.appendChild(eventDiv);
                console.log(`Event ${index} rendered:`, eventDiv);

                // Add event listeners for edit and delete buttons
                const editBtn = eventDiv.querySelector('.edit-btn');
                const deleteBtn = eventDiv.querySelector('.delete-btn');

                editBtn.addEventListener('click', () => editEvent(event));
                deleteBtn.addEventListener('click', () => deleteEvent(event.id));
            });
            console.log('All events rendered. Container contents:', eventsContainer.innerHTML);
        } catch (error) {
            console.error('Error loading events:', error);
            eventsContainer.innerHTML = '<p>Error loading events. Please try again later.</p>';
        }
    }

    // Handle add event form submission
    addEventForm.addEventListener('submit', async (e) => {
        e.preventDefault();
        const formData = new FormData(addEventForm);
            const eventData = {
                id: `event-${Date.now()}`,
                name: formData.get('name'),
                location: formData.get('location'),
                address: formData.get('address'),
                date: formData.get('date'),
                endDate: formData.get('endDate') || null,
                isRecurring: formData.get('isRecurring') === 'on',
                recurrenceFrequency: formData.get('isRecurring') === 'on' ? formData.get('recurrenceFrequency') : null,
                description: formData.get('description'),
                image: selectedImage,
                entryCost: formData.get('entryCost'),
                registrationLink: formData.get('registrationLink'),
                notes: formData.get('notes'),
                category: formData.get('category'),
                gameTag: formData.get('gameTag')
            };

        try {
            const response = await fetch('../admin/save_event.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                },
                body: JSON.stringify(eventData)
            });
            
            if (response.ok) {
                alert('Event saved successfully!');
                addEventModal.classList.remove('show');
                resetAddForm();
                loadEvents();
            } else {
                throw new Error('Failed to save event');
            }
        } catch (error) {
            console.error('Error:', error);
            alert('An error occurred while saving the event');
        }
    });

    // Handle edit event form submission
    editEventForm.addEventListener('submit', async (e) => {
        e.preventDefault();
        const formData = new FormData(editEventForm);
        const eventData = {
            id: editEventForm.dataset.eventId,
            name: formData.get('name'),
            category: formData.get('category'),
            description: formData.get('description'),
            location: formData.get('location'),
            address: formData.get('address'),
            date: formData.get('date'),
            endDate: formData.get('endDate') || null,
            isRecurring: formData.get('isRecurring') === 'on',
            image: document.getElementById('editImageInput').value || editingImage,
            entryCost: formData.get('entryCost'),
            registrationLink: formData.get('registrationLink'),
            notes: formData.get('notes'),
            gameTag: formData.get('gameTag')
        };

        try {
            const response = await fetch('../admin/update_event.php', {
                method: 'PUT',
                headers: {
                    'Content-Type': 'application/json',
                },
                body: JSON.stringify(eventData)
            });
            
            if (response.ok) {
                alert('Event updated successfully!');
                editFormSection.classList.remove('show');
                resetEditForm();
                loadEvents();
            } else {
                throw new Error('Failed to update event');
            }
        } catch (error) {
            console.error('Error:', error);
            alert('An error occurred while updating the event');
        }
    });

    // Function to edit event
    async function editEvent(event) {
        console.log('Editing event:', event);
        editEventForm.dataset.eventId = event.id;
        
        // Fill form with event data
        const form = editEventForm;
        // Show edit form first to ensure elements are in the DOM
        editFormSection.classList.add('show');
        
        // Add a small delay to ensure the form is visible before populating
        setTimeout(() => {
            form.querySelector('[name="name"]').value = event.name;
            form.querySelector('[name="category"]').value = event.category || 'Weekly Events';
            form.querySelector('[name="location"]').value = event.location;
            form.querySelector('[name="address"]').value = event.address || '';
            form.querySelector('[name="date"]').value = event.date.slice(0, 16); // Remove seconds
            form.querySelector('[name="endDate"]').value = event.endDate ? event.endDate.slice(0, 16) : '';
            form.querySelector('[name="description"]').value = event.description || '';
            form.querySelector('[name="entryCost"]').value = event.entryCost;
            form.querySelector('[name="registrationLink"]').value = event.registrationLink;
            form.querySelector('[name="notes"]').value = event.notes || '';
            form.querySelector('[name="gameTag"]').value = event.gameTag || '';
            
            // Set the image input value
            const imageInput = document.getElementById('editImageInput');
            if (imageInput) {
                imageInput.value = event.image;
            }
        }, 100);
        
        // Handle recurring event fields
        const isRecurring = event.isRecurring || false;
        form.querySelector('#editIsRecurring').checked = isRecurring;
        form.querySelector('#editRecurrenceFrequencyGroup').style.display = isRecurring ? 'block' : 'none';
        if (isRecurring) {
            form.querySelector('#editRecurrenceFrequency').value = event.recurrenceFrequency || 'weekly';
        }
        
        // Handle image
        editingImage = event.image;
        console.log('Initial edit image:', editingImage);
        const previewImg = document.getElementById('editSelectedPreview');
        if (previewImg && event.image) {
            previewImg.src = event.image;
            previewImg.style.display = 'block';
            previewImg.parentElement.querySelector('.upload-prompt').style.display = 'none';
        } else if (previewImg) {
            previewImg.style.display = 'none';
            previewImg.src = '';
            previewImg.parentElement.querySelector('.upload-prompt').style.display = 'block';
        }
        
        // Load images for selection
        await loadEventImages(true);
        
        // Select current image if it exists
        if (event.image) {
            const imageItems = document.querySelectorAll('#editImageGrid .events-admin-image-item img');
            imageItems.forEach(img => {
                if (img.src.endsWith(event.image.split('/').pop())) {
                    img.parentElement.classList.add('selected');
                }
            });
        }
    }

    // Function to delete event
    async function deleteEvent(eventId) {
        if (confirm('Are you sure you want to delete this event?')) {
            try {
                const response = await fetch('../admin/delete_event.php', {
                    method: 'DELETE',
                    headers: {
                        'Content-Type': 'application/json',
                    },
                    body: JSON.stringify({ id: eventId })
                });

                if (response.ok) {
                    alert('Event deleted successfully!');
                    loadEvents();
                } else {
                    throw new Error('Failed to delete event');
                }
            } catch (error) {
                console.error('Error:', error);
                alert('An error occurred while deleting the event');
            }
        }
    }

    // Function to reset add form
    function resetAddForm() {
        addEventForm.reset();
        selectedImage = '';
        const previewImg = document.getElementById('selectedPreview');
        if (previewImg) {
            previewImg.style.display = 'none';
            previewImg.src = '';
        }
        // Reset image selection
        document.querySelectorAll('.events-admin-image-item').forEach(item => {
            item.classList.remove('selected');
        });
    }

    // Function to reset edit form
    function resetEditForm() {
        editEventForm.reset();
        editEventForm.dataset.eventId = '';
        editingImage = '';
        
        // Reset image preview
        const previewImg = document.getElementById('editSelectedPreview');
        if (previewImg) {
            previewImg.style.display = 'none';
            previewImg.src = '';
            previewImg.parentElement.querySelector('.upload-prompt').style.display = 'block';
        }
        
        // Reset image selection
        document.querySelectorAll('#editImageGrid .events-admin-image-item').forEach(item => {
            item.classList.remove('selected');
        });
        
        // Reset hidden image input
        const imageInput = document.getElementById('editImageInput');
        if (imageInput) {
            imageInput.value = '';
        }
    }

    // Set up image upload handlers
    const uploadNewBtn = document.getElementById('uploadNewBtn');
    const editUploadNewBtn = document.getElementById('editUploadNewBtn');
    
    if (uploadNewBtn) {
        uploadNewBtn.addEventListener('click', () => handleImageUpload(false));
    }
    if (editUploadNewBtn) {
        editUploadNewBtn.addEventListener('click', () => handleImageUpload(true));
    }

    // Function to handle image upload
    async function handleImageUpload(isEdit = false) {
        const statusId = isEdit ? 'editUploadStatus' : 'uploadStatus';
        const errorId = isEdit ? 'editUploadError' : 'uploadError';
        const previewId = isEdit ? 'editSelectedPreview' : 'selectedPreview';
        
        const input = document.createElement('input');
        input.type = 'file';
        input.accept = 'image/*';
        
        input.onchange = async (e) => {
            const file = e.target.files[0];
            if (!file) return;
            
            const statusDiv = document.getElementById(statusId);
            const errorDiv = document.getElementById(errorId);
            const statusMessage = statusDiv.querySelector('.status-message');
            
            try {
                // Reset status
                errorDiv.style.display = 'none';
                statusDiv.style.display = 'block';
                statusMessage.textContent = 'Uploading image...';
                
                const formData = new FormData();
                formData.append('image', file);

                const response = await fetch('upload_event_image.php', {
                    method: 'POST',
                    body: formData
                });

                const data = await response.json();
                
                if (!response.ok || !data.success) {
                    throw new Error(data.error || 'Failed to upload image');
                }
                
                statusMessage.textContent = 'Processing image...';
                
                // Use the original image path
                const imagePath = data.data.original.path;
                if (isEdit) {
                    editingImage = imagePath;
                    // Update hidden input
                    const imageInput = document.getElementById('editImageInput');
                    if (imageInput) {
                        imageInput.value = imagePath;
                    }
                } else {
                    selectedImage = imagePath;
                }
                
                // Update preview
                const previewImg = document.getElementById(previewId);
                if (previewImg) {
                    previewImg.src = imagePath;
                    previewImg.style.display = 'block';
                    previewImg.parentElement.querySelector('.upload-prompt').style.display = 'none';
                }
                
                // Reload image grid to show new image
                await loadEventImages(isEdit);
                
                statusMessage.textContent = 'Image uploaded successfully!';
                setTimeout(() => {
                    statusDiv.style.display = 'none';
                }, 3000);
                
            } catch (error) {
                console.error('Error:', error);
                statusDiv.style.display = 'none';
                errorDiv.textContent = error.message;
                errorDiv.style.display = 'block';
            }
        };
        
        input.click();
    }
});
