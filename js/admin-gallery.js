// File Upload Handling
const dropzone = document.getElementById('dropzone');
const fileInput = document.getElementById('fileInput');
const uploadForm = document.getElementById('uploadForm');
const progressBar = document.querySelector('.progress-bar');
const progressText = document.querySelector('.progress-text');
const uploadProgress = document.querySelector('.upload-progress');

// Initialize date picker if available
if (typeof flatpickr !== 'undefined') {
    flatpickr("#dateTaken", {
        enableTime: false,
        dateFormat: "Y-m-d"
    });
}

// Prevent defaults for drag and drop events
['dragenter', 'dragover', 'dragleave', 'drop'].forEach(eventName => {
    dropzone?.addEventListener(eventName, preventDefaults, false);
});

function preventDefaults(e) {
    e.preventDefault();
    e.stopPropagation();
}

// Add/remove dragover class
['dragenter', 'dragover'].forEach(eventName => {
    dropzone?.addEventListener(eventName, highlight, false);
});

['dragleave', 'drop'].forEach(eventName => {
    dropzone?.addEventListener(eventName, unhighlight, false);
});

function highlight(e) {
    dropzone?.classList.add('dragover');
}

function unhighlight(e) {
    dropzone?.classList.remove('dragover');
}

// Handle file selection
dropzone?.addEventListener('click', () => {
    fileInput?.click();
});

dropzone?.addEventListener('drop', handleDrop, false);
fileInput?.addEventListener('change', handleFiles, false);

function handleDrop(e) {
    const dt = e.dataTransfer;
    const files = dt.files;
    handleFiles({ target: { files } });
}

function handleFiles(e) {
    const files = e.target.files;
    if (files && files.length > 0) {
        uploadFiles(files);
    }
}

function uploadFiles(files) {
    const formData = new FormData();
    const maxFiles = 500;
    const maxSizePerFile = 128 * 1024 * 1024; // 128MB
    const maxTotalSize = 1024 * 1024 * 1024; // 1GB

    // Validate number of files
    if (files.length > maxFiles) {
        alert(`You can only upload up to ${maxFiles} files at once.`);
        if (fileInput) fileInput.value = '';
        return;
    }

    // Validate file sizes
    let totalSize = 0;
    const validFiles = Array.from(files).filter(file => {
        if (file.size > maxSizePerFile) {
            alert(`File "${file.name}" exceeds the maximum file size of 128MB.`);
            return false;
        }
        totalSize += file.size;
        return true;
    });

    if (totalSize > maxTotalSize) {
        alert('Total upload size exceeds 1GB limit.');
        if (fileInput) fileInput.value = '';
        return;
    }

    // Add valid files to form data
    validFiles.forEach(file => {
        formData.append('photos[]', file);
    });

    if (validFiles.length === 0) {
        if (fileInput) fileInput.value = '';
        return;
    }

    // Show and reset progress bar
    if (uploadProgress) uploadProgress.style.display = 'block';
    if (progressBar) progressBar.style.width = '0%';
    if (progressText) progressText.textContent = '0%';

    // Send upload request
    const xhr = new XMLHttpRequest();
    
    xhr.upload.addEventListener('progress', (e) => {
        if (e.lengthComputable && progressBar && progressText) {
            const percentComplete = Math.round((e.loaded / e.total) * 100);
            progressBar.style.width = percentComplete + '%';
            progressText.textContent = percentComplete + '%';
        }
    });

    xhr.onload = function() {
        if (xhr.status === 200) {
            try {
                const response = JSON.parse(xhr.responseText);
                
                if (response.success) {
                    location.reload();
                    return;
                }
                
                // Handle duplicate file case
                if (response.error) {
                    try {
                        const errorData = JSON.parse(response.error);
                        if (errorData.isDuplicate) {
                            const duplicate = errorData.existingFile;
                            const dateTaken = new Date(duplicate.date_taken).toLocaleDateString();
                            const message = `This file was already uploaded on ${dateTaken}.\n\n` +
                                          `Would you like to:\n` +
                                          `• Skip this file\n` +
                                          `• Upload as a new copy\n\n` +
                                          `Click OK to upload as a new copy, or Cancel to skip.`;
                            
                            if (confirm(message)) {
                                // User chose to upload anyway - add timestamp to make filename unique
                                const newFormData = new FormData();
                                Array.from(files).forEach(file => {
                                    const timestamp = Date.now();
                                    const nameParts = file.name.split('.');
                                    const ext = nameParts.pop();
                                    const newName = `${nameParts.join('.')}_${timestamp}.${ext}`;
                                    const newFile = new File([file], newName, {
                                        type: file.type,
                                        lastModified: file.lastModified
                                    });
                                    newFormData.append('photos[]', newFile);
                                });
                                
                                // Send new request with renamed file
                                const newXhr = new XMLHttpRequest();
                                newXhr.open('POST', '../upload.php', true);
                                newXhr.onload = function() {
                                    if (newXhr.status === 200) {
                                        const newResponse = JSON.parse(newXhr.responseText);
                                        if (newResponse.success) {
                                            location.reload();
                                        } else {
                                            alert('Error uploading files. Please try again.');
                                        }
                                    }
                                    if (uploadProgress) uploadProgress.style.display = 'none';
                                    if (fileInput) fileInput.value = '';
                                };
                                newXhr.send(newFormData);
                                return;
                            }
                        }
                    } catch (e) {
                        console.error('Error parsing duplicate file data:', e);
                    }
                }
                
                alert('Error uploading files: ' + (response.error || 'Unknown error'));
            } catch (e) {
                console.error('Error processing response:', e);
                alert('Error uploading files. Please try again.');
            }
        } else {
            alert('Error uploading files. Server returned status ' + xhr.status);
        }
        
        if (uploadProgress) uploadProgress.style.display = 'none';
        if (fileInput) fileInput.value = '';
    };

    xhr.onerror = function() {
        alert('Error uploading files. Please check your connection and try again.');
        if (uploadProgress) uploadProgress.style.display = 'none';
        if (fileInput) fileInput.value = '';
    };

    xhr.open('POST', '../upload.php', true);
    xhr.send(formData);
}

// Filter handling
document.addEventListener('DOMContentLoaded', function() {
    // Navigation toggle
    const navToggle = document.getElementById('nav-toggle');
    const navMenu = document.getElementById('nav-menu');
    
    navToggle?.addEventListener('click', function() {
        navMenu?.classList.toggle('active');
    });

    // Filter section toggle
    const filterToggle = document.createElement('div');
    filterToggle.className = 'filter-toggle';
    filterToggle.innerHTML = `
        <span>Filters</span>
        <svg viewBox="0 0 24 24" width="16" height="16">
            <path d="M7 10l5 5 5-5z" fill="currentColor"/>
        </svg>
    `;

    const filterSection = document.querySelector('.filter-section');
    if (filterSection) {
        filterSection.parentNode.insertBefore(filterToggle, filterSection);
        
        filterToggle.addEventListener('click', () => {
            filterSection.classList.toggle('collapsed');
            filterToggle.classList.toggle('collapsed');
        });
    }

    // Filter group toggle
    document.querySelectorAll('.filter-group h3').forEach(header => {
        header.addEventListener('click', () => {
            const group = header.parentElement;
            group.classList.toggle('collapsed');
        });
    });

    // Handle filter clicks with transition
    const filterButtons = document.querySelectorAll('.filter-btn');
    filterButtons.forEach(button => {
        button.addEventListener('click', function(e) {
            e.preventDefault();
            const target = this.href;
            
            // Add loading class to container
            const container = document.querySelector('.gallery-container');
            if (container) {
                container.classList.add('loading');
                
                // Small delay for transition
                setTimeout(() => {
                    window.location.href = target;
                }, 300);
            } else {
                // If no container found, just navigate
                window.location.href = target;
            }
        });
    });
});

// Edit photo functions
function openEditModal(photoId, dateTaken, eventName) {
    const modal = document.getElementById('editModal');
    if (!modal) return;

    const photoIdInput = document.getElementById('photoId');
    const dateTakenInput = document.getElementById('dateTaken');
    const eventNameInput = document.getElementById('eventName');

    if (photoIdInput) photoIdInput.value = photoId;
    if (dateTakenInput) dateTakenInput.value = dateTaken;
    if (eventNameInput) eventNameInput.value = eventName || '';

    modal.classList.add('active');
}

function closeEditModal() {
    const modal = document.getElementById('editModal');
    if (modal) {
        modal.classList.remove('active');
    }
}

// Handle edit form submission
const editForm = document.getElementById('editForm');
editForm?.addEventListener('submit', function(e) {
    e.preventDefault();
    
    const photoId = document.getElementById('photoId')?.value;
    const dateTaken = document.getElementById('dateTaken')?.value;
    const eventName = document.getElementById('eventName')?.value;
    
    fetch('/gallery/admin/update_photo.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json'
        },
        body: JSON.stringify({
            photoId,
            dateTaken,
            eventName
        })
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            location.reload();
        } else {
            alert('Error updating photo: ' + (data.error || 'Unknown error'));
        }
    })
    .catch(error => {
        console.error('Error:', error);
        alert('Error updating photo. Please try again.');
    });
});

function deletePhoto(photoId) {
    if (confirm('Are you absolutely sure you want to delete this photo? This cannot be undone.')) {
        fetch('/gallery/admin/delete_photo.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json'
            },
            body: JSON.stringify({ photoId })
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                location.reload();
            } else {
                alert('Error deleting photo: ' + (data.error || 'Unknown error'));
            }
        })
        .catch(error => {
            console.error('Error:', error);
            alert('Error deleting photo. Please try again.');
        });
    }
}
