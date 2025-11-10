<?php
error_reporting(E_ALL & ~E_NOTICE & ~E_STRICT);
ini_set('display_errors', 1);

// Use our centralized session and auth handling
require_once '../../admin/includes/config.php';
require_once '../../admin/includes/AdminLogger.php';

// Initialize admin logger
AdminLogger::init(defined('DEBUG_MODE') ? DEBUG_MODE : false);

// Debug session state
debug_session_state();

// Check authentication
if (!isset($_SESSION['user_id'])) {
    AdminLogger::logError('security', 'Unauthorized access attempt to events admin', [
        'ip' => $_SERVER['REMOTE_ADDR'],
        'session_id' => session_id(),
        'path' => $_SERVER['REQUEST_URI']
    ]);
    header('Location: ../../admin/login.php');
    exit;
}

// Verify session integrity
if (!isset($_SESSION['ip']) || $_SESSION['ip'] !== $_SERVER['REMOTE_ADDR'] ||
    !isset($_SESSION['user_agent']) || $_SESSION['user_agent'] !== ($_SERVER['HTTP_USER_AGENT'] ?? 'Unknown')) {
    
    AdminLogger::logError('security', 'Session validation failed in events admin', [
        'ip' => $_SERVER['REMOTE_ADDR'],
        'session_id' => session_id(),
        'user_id' => $_SESSION['user_id']
    ]);
    
    session_unset();
    session_destroy();
    session_start();
    header('Location: ../../admin/login.php?error=security');
    exit;
}

// Update last activity
$_SESSION['last_activity'] = time();

// Ensure auth_check cookie exists
if (!isset($_COOKIE['auth_check'])) {
    set_auth_check_cookie('1');
}

// Log access
AdminLogger::logLoginAttempt($_SESSION['username'], true, 'Accessed events admin');

// Set security headers
header("Content-Security-Policy: default-src 'self'; img-src 'self' data:; style-src 'self' 'unsafe-inline'; script-src 'self' 'unsafe-inline';");

// Template configuration
$page_title = 'Event Manager';
$nav_base_path = '../../';
$extra_css = [
    '../../css/optimized.min.css',
    '../../css/custom.css',
    'styles.css'
];

require_once '../../admin/includes/subadmin_head.php';
require_once '../../admin/includes/subadmin_nav.php';
?>

    <main class="events-admin-page">
        <div class="events-admin-header">
            <h1>Event Manager</h1>
            <div class="events-admin-actions">
                <button id="addEventBtn" class="events-admin-btn">Add Event</button>
                <button id="manageEventsBtn" class="events-admin-btn">Manage Events</button>
            </div>
        </div>

        <!-- Add Event Modal -->
        <div id="addEventModal" class="events-admin-modal">
            <div class="events-admin-modal-content">
                <span class="close">&times;</span>
                <h2>Add New Event</h2>
                <form id="addEventForm" class="events-admin-form">
                    <div class="events-admin-form-layout">
                        <!-- Left Section: Image Selection -->
                        <div class="events-admin-image-upload">
                            <div class="events-admin-image-preview" id="imagePreview">
                                <img id="selectedPreview" src="" alt="Selected Image" style="display: none;">
                                <div class="upload-prompt">Click to select image</div>
                            </div>
                            <div class="events-admin-image-selector">
                                <h3>Select Existing Image</h3>
                                <div id="uploadError" class="events-admin-error-message" style="display: none;"></div>
                                <div id="uploadStatus" class="events-admin-upload-status" style="display: none;">
                                    <p class="status-message"></p>
                                </div>
                                <div class="events-admin-image-grid" id="imageGrid">
                                    <?php
                                    $eventImagesDir = '../../images/events/';
                                    $images = glob($eventImagesDir . '*.{jpg,jpeg,png,gif,webp}', GLOB_BRACE);
                                    foreach ($images as $image) {
                                        $filename = basename($image);
                                        $relativePath = '../../images/events/' . $filename;
                                        echo '<div class="event-image-item">';
                                        echo '<img src="' . htmlspecialchars($relativePath) . '" alt="' . htmlspecialchars($filename) . '">';
                                        echo '<input type="radio" name="selectedImage" value="' . htmlspecialchars($relativePath) . '">';
                                        echo '</div>';
                                    }
                                    ?>
                                </div>
                                <div class="events-admin-image-upload-btn">
                                    <button type="button" class="events-admin-btn" id="uploadNewBtn">Upload New Image</button>
                                </div>
                            </div>
                        </div>
                        
                        <!-- Right Section: Event Details -->
                        <div class="events-admin-form-fields">
                            <div class="events-admin-form-group">
                                <label for="name">Event Name:</label>
                                <input type="text" id="name" name="name" required>
                            </div>
                            <div class="events-admin-form-group">
                                <label for="category">Category:</label>
                                <select id="category" name="category" required>
                                    <option value="tournaments">Tournaments</option>
                                    <option value="weekly-events">Weekly Events</option>
                                    <option value="nor-leagues">NOR Leagues</option>
                                    <option value="league-of-dreams-leagues">League of Dreams Leagues</option>
                                </select>
                            </div>
                            <div class="events-admin-form-group">
                                <label for="description">Description:</label>
                                <textarea id="description" name="description" rows="3"></textarea>
                            </div>
                            <div class="events-admin-form-group">
                                <label for="location">Location:</label>
                                <input type="text" id="location" name="location" required>
                            </div>
                            <div class="events-admin-form-group">
                                <label>
                                    <input type="checkbox" id="isRecurring" name="isRecurring">
                                    Recurring Event
                                </label>
                            </div>
                            <div class="events-admin-form-group" id="recurrenceFrequencyGroup" style="display: none;">
                                <label for="recurrenceFrequency">Frequency:</label>
                                <select id="recurrenceFrequency" name="recurrenceFrequency">
                                    <option value="weekly">Weekly</option>
                                    <option value="biweekly">Bi-weekly</option>
                                    <option value="monthly">Monthly</option>
                                </select>
                            </div>
                            <div class="events-admin-form-group">
                                <label for="address">Address:</label>
                                <input type="text" id="address" name="address" required>
                            </div>
                            <div class="events-admin-form-group">
                                <label for="date">Start Date & Time:</label>
                                <input type="datetime-local" id="date" name="date" required>
                            </div>
                            <div class="events-admin-form-group">
                                <label for="entryCost">Entry Cost:</label>
                                <input type="text" id="entryCost" name="entryCost" required>
                            </div>
                            <div class="events-admin-form-group">
                                <label for="registrationLink">Registration Link:</label>
                                <input type="url" id="registrationLink" name="registrationLink" required>
                            </div>
                            <div class="events-admin-form-group">
                                <label for="gameTag">Game Tag:</label>
                                <input type="text" id="gameTag" name="gameTag" placeholder="e.g., valorant, league-of-legends">
                            </div>
                            <div class="events-admin-form-group">
                                <label for="notes">Notes:</label>
                                <textarea id="notes" name="notes" rows="3"></textarea>
                            </div>
                            <div class="events-admin-button-group">
                                <button type="submit" class="events-admin-btn">Save Event</button>
                                <button type="button" class="events-admin-btn cancel-btn">Cancel</button>
                            </div>
                        </div>
                    </div>
                </form>
            </div>
        </div>

        <!-- Manage Events Modal -->
        <div id="manageEventsModal" class="events-admin-modal">
            <div class="events-admin-modal-content">
                <span class="close">&times;</span>
                <h2>Manage Events</h2>
                <div id="eventsContainer"></div>
                
                <!-- Edit Form (Slides Out) -->
                <div class="events-admin-edit-form">
                    <h3>Edit Event</h3>
                    <form id="editEventForm" class="events-admin-form">
                        <div class="events-admin-form-layout">
                            <!-- Left Section: Image Selection -->
                            <div class="events-admin-image-upload">
                                <div class="events-admin-image-preview" id="editImagePreview">
                                    <img id="editSelectedPreview" src="" alt="Selected Image" style="display: none;">
                                    <div class="upload-prompt">Click to select image</div>
                                </div>
                                <div class="events-admin-image-selector">
                                    <h3>Select Existing Image</h3>
                                    <div id="editUploadError" class="events-admin-error-message" style="display: none;"></div>
                                    <div id="editUploadStatus" class="events-admin-upload-status" style="display: none;">
                                        <p class="status-message"></p>
                                    </div>
                                    <div class="events-admin-image-grid" id="editImageGrid"></div>
                                    <div class="events-admin-image-upload-btn">
                                        <button type="button" class="events-admin-btn" id="editUploadNewBtn">Upload New Image</button>
                                    </div>
                                </div>
                            </div>
                            
                            <!-- Right Section: Event Details -->
                            <div class="events-admin-form-fields">
                                <input type="hidden" name="image" id="editImageInput">
                                <div class="events-admin-form-group">
                                    <label for="editName">Event Name:</label>
                                    <input type="text" id="editName" name="name" required>
                                </div>
                                <div class="events-admin-form-group">
                                    <label for="editCategory">Category:</label>
                                    <select id="editCategory" name="category" required>
                                        <option value="tournaments">Tournaments</option>
                                        <option value="weekly-events">Weekly Events</option>
                                        <option value="nor-leagues">NOR Leagues</option>
                                        <option value="league-of-dreams-leagues">League of Dreams Leagues</option>
                                    </select>
                                </div>
                                <div class="events-admin-form-group">
                                    <label for="editDescription">Description:</label>
                                    <textarea id="editDescription" name="description" rows="3"></textarea>
                                </div>
                                <div class="events-admin-form-group">
                                    <label for="editLocation">Location:</label>
                                    <input type="text" id="editLocation" name="location" required>
                                </div>
                                <div class="events-admin-form-group">
                                    <label>
                                        <input type="checkbox" id="editIsRecurring" name="isRecurring">
                                        Recurring Event
                                    </label>
                                </div>
                                <div class="events-admin-form-group" id="editRecurrenceFrequencyGroup" style="display: none;">
                                    <label for="editRecurrenceFrequency">Frequency:</label>
                                    <select id="editRecurrenceFrequency" name="recurrenceFrequency">
                                        <option value="weekly">Weekly</option>
                                        <option value="biweekly">Bi-weekly</option>
                                        <option value="monthly">Monthly</option>
                                    </select>
                                </div>
                                <div class="events-admin-form-group">
                                    <label for="editAddress">Address:</label>
                                    <input type="text" id="editAddress" name="address" required>
                                </div>
                                <div class="events-admin-form-group">
                                    <label for="editDate">Start Date & Time:</label>
                                    <input type="datetime-local" id="editDate" name="date" required>
                                </div>
                                <div class="events-admin-form-group">
                                    <label for="editEndDate">End Date & Time:</label>
                                    <input type="datetime-local" id="editEndDate" name="endDate">
                                </div>
                                <div class="events-admin-form-group">
                                    <label for="editEntryCost">Entry Cost:</label>
                                    <input type="text" id="editEntryCost" name="entryCost" required>
                                </div>
                                <div class="events-admin-form-group">
                                    <label for="editRegistrationLink">Registration Link:</label>
                                    <input type="url" id="editRegistrationLink" name="registrationLink" required>
                                </div>
                                <div class="events-admin-form-group">
                                    <label for="editNotes">Notes:</label>
                                    <textarea id="editNotes" name="notes" rows="3"></textarea>
                                </div>
                                <div class="events-admin-form-group">
                                    <label for="editGameTag">Game Tag:</label>
                                    <input type="text" id="editGameTag" name="gameTag" placeholder="e.g., valorant, league-of-legends">
                                </div>
                                <div class="events-admin-button-group">
                                    <button type="submit" class="events-admin-btn">Save Changes</button>
                                    <button type="button" class="events-admin-btn cancel-edit-btn">Cancel</button>
                                </div>
                            </div>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </main>
<?php
$extra_scripts = ['script.js'];
require_once '../../admin/includes/subadmin_footer.php';
?>
