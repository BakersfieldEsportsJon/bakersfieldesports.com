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
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Event Manager - Bakersfield Esports Center</title>
    <link rel="stylesheet" href="../../css/styles.css">
    <link rel="stylesheet" href="styles.css">
</head>
<body>
    <header>
        <nav class="navbar">
            <div class="container">
                <a class="logo" href="../../index.html">
                    <img alt="Bakersfield eSports Logo" src="../../images/Asset%205-ts1621173277.png" />
                </a>
                <button aria-label="Toggle navigation" class="nav-toggle" id="nav-toggle">☰</button>
                <ul class="nav-menu" id="nav-menu">
                    <li><a href="../../index.html">Home</a></li>
                    <li><a href="../../locations/index.html">Locations</a></li>
                    <li><a href="../../events/">Events</a></li>
                    <li><a href="../../rates-parties/index.html">Rates & Parties</a></li>
                    <li><a href="../../partnerships/index.html">Partnerships</a></li>
                    <li><a href="../../about-us/index.html">About Us</a></li>
                    <li><a href="../../gallery/index.php">Gallery</a></li>
                    <li><a href="../../contact-us/index.html">Contact Us</a></li>
                    <li><a href="https://discord.gg/jbzWH3ZvRp" target="_blank">Discord</a></li>
                    <li><a href="../../stem/index.html">STEM</a></li>
                    <li>
                        <form action="../../admin/logout.php" method="POST" style="display: inline;">
                            <input type="hidden" name="csrf_token" value="<?= htmlspecialchars(get_csrf_token()) ?>">
                            <button type="submit" class="logout-btn">Logout</button>
                        </form>
                    </li>
                </ul>
            </div>
        </nav>
    </header>

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
                <form id="addEventForm" class="events-admin-form" action="save_event.php" method="POST">
                    <input type="hidden" name="csrf_token" value="<?= htmlspecialchars(get_csrf_token()) ?>">
                    <div class="form-group">
                        <label for="name">Event Name:</label>
                        <input type="text" id="name" name="name" required>
                    </div>
                    <div class="form-group">
                        <label for="category">Category:</label>
                        <select id="category" name="category" required>
                            <option value="tournaments">Tournaments</option>
                            <option value="weekly-events">Weekly Events</option>
                            <option value="nor-leagues">NOR Leagues</option>
                            <option value="league-of-dreams-leagues">League of Dreams Leagues</option>
                        </select>
                    </div>
                    <div class="form-group">
                        <label for="description">Description:</label>
                        <textarea id="description" name="description"></textarea>
                    </div>
                    <div class="form-group">
                        <label for="location">Location:</label>
                        <input type="text" id="location" name="location" required>
                    </div>
                    <div class="form-group">
                        <label for="address">Address:</label>
                        <input type="text" id="address" name="address">
                    </div>
                    <div class="form-group">
                        <label for="date">Start Date & Time:</label>
                        <input type="datetime-local" id="date" name="date" required>
                    </div>
                    <div class="form-group">
                        <label for="endDate">End Date & Time (Optional):</label>
                        <input type="datetime-local" id="endDate" name="endDate">
                    </div>
                    <div class="form-group">
                        <label>
                            <input type="checkbox" id="isRecurring" name="isRecurring">
                            Recurring Event
                        </label>
                    </div>
                    <div id="recurrenceFrequencyGroup" style="display: none;">
                        <label for="recurrenceFrequency">Frequency:</label>
                        <select id="recurrenceFrequency" name="recurrenceFrequency">
                            <option value="weekly">Weekly</option>
                            <option value="biweekly">Bi-weekly</option>
                            <option value="monthly">Monthly</option>
                        </select>
                    </div>
                    <div class="form-group">
                        <label for="entryCost">Entry Cost ($):</label>
                        <input type="number" id="entryCost" name="entryCost" min="0" step="0.01">
                    </div>
                    <div class="form-group">
                        <label for="registrationLink">Registration Link:</label>
                        <input type="url" id="registrationLink" name="registrationLink">
                    </div>
                    <div class="form-group">
                        <label for="notes">Notes:</label>
                        <textarea id="notes" name="notes"></textarea>
                    </div>
                    <div class="form-group">
                        <label for="gameTag">Game Tag:</label>
                        <input type="text" id="gameTag" name="gameTag">
                    </div>
                    <div class="form-actions">
                        <button type="submit" class="events-admin-btn">Save Event</button>
                        <button type="button" class="events-admin-btn cancel-btn">Cancel</button>
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
                
                <!-- Edit Form -->
                <div class="events-admin-edit-form">
                    <h3>Edit Event</h3>
                    <form id="editEventForm" class="events-admin-form" action="update_event.php" method="POST">
                        <input type="hidden" name="csrf_token" value="<?= htmlspecialchars(get_csrf_token()) ?>">
                        <div class="form-group">
                            <label for="editName">Event Name:</label>
                            <input type="text" id="editName" name="name" required>
                        </div>
                        <div class="form-group">
                            <label for="editCategory">Category:</label>
                            <select id="editCategory" name="category" required>
                                <option value="tournaments">Tournaments</option>
                                <option value="weekly-events">Weekly Events</option>
                                <option value="nor-leagues">NOR Leagues</option>
                                <option value="league-of-dreams-leagues">League of Dreams Leagues</option>
                            </select>
                        </div>
                        <div class="form-group">
                            <label for="editDescription">Description:</label>
                            <textarea id="editDescription" name="description"></textarea>
                        </div>
                        <div class="form-group">
                            <label for="editLocation">Location:</label>
                            <input type="text" id="editLocation" name="location" required>
                        </div>
                        <div class="form-group">
                            <label for="editAddress">Address:</label>
                            <input type="text" id="editAddress" name="address">
                        </div>
                        <div class="form-group">
                            <label for="editDate">Start Date & Time:</label>
                            <input type="datetime-local" id="editDate" name="date" required>
                        </div>
                        <div class="form-group">
                            <label for="editEndDate">End Date & Time (Optional):</label>
                            <input type="datetime-local" id="editEndDate" name="endDate">
                        </div>
                        <div class="form-group">
                            <label>
                                <input type="checkbox" id="editIsRecurring" name="isRecurring">
                                Recurring Event
                            </label>
                        </div>
                        <div id="editRecurrenceFrequencyGroup" style="display: none;">
                            <label for="editRecurrenceFrequency">Frequency:</label>
                            <select id="editRecurrenceFrequency" name="recurrenceFrequency">
                                <option value="weekly">Weekly</option>
                                <option value="biweekly">Bi-weekly</option>
                                <option value="monthly">Monthly</option>
                            </select>
                        </div>
                        <div class="form-group">
                            <label for="editEntryCost">Entry Cost ($):</label>
                            <input type="number" id="editEntryCost" name="entryCost" min="0" step="0.01">
                        </div>
                        <div class="form-group">
                            <label for="editRegistrationLink">Registration Link:</label>
                            <input type="url" id="editRegistrationLink" name="registrationLink">
                        </div>
                        <div class="form-group">
                            <label for="editNotes">Notes:</label>
                            <textarea id="editNotes" name="notes"></textarea>
                        </div>
                        <div class="form-group">
                            <label for="editGameTag">Game Tag:</label>
                            <input type="text" id="editGameTag" name="gameTag">
                        </div>
                        <div class="form-actions">
                            <button type="submit" class="events-admin-btn">Update Event</button>
                            <button type="button" class="events-admin-btn cancel-edit-btn">Cancel</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </main>

    <script src="script.js"></script>
</body>
</html>
