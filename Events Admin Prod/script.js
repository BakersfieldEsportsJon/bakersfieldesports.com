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
    addEventBtn.addEventListener('click', () => {
        addEventModal.classList.add('show');
        resetAddForm();
        loadEventImages();
    });

    manageEventsBtn.addEventListener('click', () => {
        manageEventsModal.classList.add('show');
        loadEvents();
    });

    // Close modals
    document.querySelectorAll('.close').forEach(closeBtn => {
        closeBtn.addEventListener('click', () => {
            addEventModal.classList.remove('show');
            manageEventsModal.classList.remove('show');
            editFormSection.classList.remove('show');
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
    document.querySelector('.cancel-btn').addEventListener('click', () => {
        addEventModal.classList.remove('show');
        resetAddForm();
    });

    document.querySelector('.cancel-edit-btn').addEventListener('click', () => {
        editFormSection.classList.remove('show');
        resetEditForm();
    });

    // Load existing event images
    async function loadEventImages() {
        try {
            const response = await fetch('../admin/get_event_images.php');
            if (!response.ok) throw new Error('Failed to load images');
            const images = await response.json();
            
            const imageGrid = document.getElementById('imageGrid');
            imageGrid.innerHTML = '';
            
            images.forEach(imageFile => {
                const imagePath = `../../images/events/${imageFile}`;
                const imageDiv = document.createElement('div');
                imageDiv.className = 'events-admin-image-item';
                imageDiv.innerHTML = `
                    <img src="${imagePath}" alt="Event Image" style="max-width: 150px; height: 150px; object-fit: cover;">
                `;
                
                imageDiv.addEventListener('click', () => {
                    // Remove selected class from all images
                    document.querySelectorAll('.events-admin-image-item').forEach(item => {
                        item.classList.remove('selected');
                    });
                    
                    // Add selected class to clicked image
                    imageDiv.classList.add('selected');
                    
                    // Update preview
                    selectedImage = imagePath;
                    const previewImg = document.getElementById('selectedPreview');
                    if (previewImg) {
                        previewImg.src = imagePath;
                        previewImg.style.display = 'block';
                    }
                });
                
                imageGrid.appendChild(imageDiv);
            });
        } catch (error) {
            console.error('Error loading images:', error);
        }
    }

    // Load and display events
    async function loadEvents() {
        try {
            const response = await fetch('../data/events.json');
            if (!response.ok) {
                throw new Error('Failed to load events');
            }
            const data = await response.json();
            const events = data.events;
            
            eventsContainer.innerHTML = '';
            events.forEach(event => {
                const eventDiv = document.createElement('div');
                eventDiv.className = 'events-admin-item';
                eventDiv.dataset.eventId = event.id;
                eventDiv.innerHTML = `
                    <div class="events-admin-content">
                        <div class="events-admin-image-container">
                            ${event.image ? `<img src="${event.image.replace('../images/events/', '../../images/events/')}" alt="${event.name}" class="events-admin-image">` : ''}
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

                // Add event listeners for edit and delete buttons
                const editBtn = eventDiv.querySelector('.edit-btn');
                const deleteBtn = eventDiv.querySelector('.delete-btn');

                editBtn.addEventListener('click', () => editEvent(event));
                deleteBtn.addEventListener('click', () => deleteEvent(event.id));
            });
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
            image: editingImage,
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
        editEventForm.dataset.eventId = event.id;
        
        // Fill form with event data
        const form = editEventForm;
        form.querySelector('#editName').value = event.name;
        form.querySelector('#editCategory').value = event.category || 'Weekly Events';
        form.querySelector('#editLocation').value = event.location;
        form.querySelector('#editAddress').value = event.address || '';
        form.querySelector('#editDate').value = event.date.slice(0, 16); // Remove seconds
        form.querySelector('#editEndDate').value = event.endDate ? event.endDate.slice(0, 16) : '';
        form.querySelector('#editDescription').value = event.description || '';
        form.querySelector('#editEntryCost').value = event.entryCost;
        form.querySelector('#editRegistrationLink').value = event.registrationLink;
        form.querySelector('#editNotes').value = event.notes || '';
        form.querySelector('#editGameTag').value = event.gameTag || '';
        
        // Handle recurring event fields
        const isRecurring = event.isRecurring || false;
        form.querySelector('#editIsRecurring').checked = isRecurring;
        form.querySelector('#editRecurrenceFrequencyGroup').style.display = isRecurring ? 'block' : 'none';
        if (isRecurring) {
            form.querySelector('#editRecurrenceFrequency').value = event.recurrenceFrequency || 'weekly';
        }
        
        editingImage = event.image;

        // Show edit form
        editFormSection.classList.add('show');
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
    }

    // Handle new image upload
    const uploadNewBtn = document.getElementById('uploadNewBtn');
    if (uploadNewBtn) {
        uploadNewBtn.addEventListener('click', handleImageUpload);
    }

    // Function to handle image upload
    async function handleImageUpload() {
        const input = document.createElement('input');
        input.type = 'file';
        input.accept = 'image/*';
        input.onchange = async (e) => {
            const file = e.target.files[0];
            if (file) {
                try {
                    const formData = new FormData();
                    formData.append('image', file);

                    const response = await fetch('../admin/upload_event_image.php', {
                        method: 'POST',
                        body: formData
                    });

                    if (response.ok) {
                        const data = await response.json();
                        selectedImage = data.imagePath;
                        
                        // Update preview
                        const previewImg = document.getElementById('selectedPreview');
                        if (previewImg) {
                            previewImg.src = selectedImage;
                            previewImg.style.display = 'block';
                        }
                        
                        // Add new image to grid
                        const imageGrid = document.getElementById('imageGrid');
                        const imageDiv = document.createElement('div');
                        imageDiv.className = 'events-admin-image-item selected';
                        imageDiv.innerHTML = `<img src="${selectedImage}" alt="Event Image">`;
                        
                        // Remove selected class from other images
                        document.querySelectorAll('.events-admin-image-item').forEach(item => {
                            item.classList.remove('selected');
                        });
                        
                        imageGrid.appendChild(imageDiv);
                    } else {
                        throw new Error('Failed to upload image');
                    }
                } catch (error) {
                    console.error('Error:', error);
                    alert('An error occurred while uploading the image');
                }
            }
        };
        input.click();
    }
});
