<?php
/**
 * Update the openTournamentModal function in startgg-events.js
 */

$jsFile = __DIR__ . '/js/startgg-events.js';
$content = file_get_contents($jsFile);

// Find and replace the openTournamentModal function
$oldFunction = <<<'EOD'
    function openTournamentModal(event) {
        const modal = document.getElementById('tournament-modal');
        const overlay = document.getElementById('modal-overlay');
        const closeBtn = document.getElementById('modal-close');

        // Populate modal with event data
        document.getElementById('modal-title').textContent = event.name;
        document.getElementById('modal-tournament-name').textContent = event.tournament_name;
        document.getElementById('modal-game').textContent = event.game;
        document.getElementById('modal-datetime').textContent = event.start_datetime;

        // Set game image
        const modalImage = document.getElementById('modal-game-image');
        modalImage.src = event.game_image || '';
        modalImage.alt = `${event.game} cover art`;

        // Show online badge if applicable
        const onlineBadge = document.getElementById('modal-online-badge');
        if (event.is_online) {
            onlineBadge.style.display = 'block';
        } else {
            onlineBadge.style.display = 'none';
        }

        // Set registration status
        const regStatus = document.getElementById('modal-registration-status');
        if (event.registration_open) {
            regStatus.className = 'open';
            regStatus.textContent = '✅ Registration is Open - Complete the form below to register!';
        } else {
            regStatus.className = 'closed';
            regStatus.textContent = '🔒 Registration is currently closed for this event.';
        }

        // Load registration iframe
        const iframe = document.getElementById('registration-iframe');
        if (event.registration_open) {
            // Use the event's registration URL
            iframe.src = event.registration_url;
            iframe.style.display = 'block';
        } else {
            iframe.style.display = 'none';
        }

        // Show modal
        modal.classList.add('active');
        document.body.style.overflow = 'hidden'; // Prevent scrolling

        // Close handlers
        const closeModal = () => {
            modal.classList.remove('active');
            document.body.style.overflow = '';
            // Clear iframe to stop any loading
            setTimeout(() => {
                iframe.src = '';
            }, 300);
        };

        closeBtn.onclick = closeModal;
        overlay.onclick = closeModal;
EOD;

$newFunction = <<<'EOD'
    function openTournamentModal(event) {
        // Use the new tournament modal system if available
        if (typeof window.tournamentModal !== 'undefined' && event.tournament_slug) {
            window.tournamentModal.open(event.tournament_slug);
            return;
        }

        // Fallback: open registration URL in new tab if modal not available
        if (event.registration_url) {
            window.open(event.registration_url, '_blank');
        } else {
            alert('Tournament details not available at this time.');
        }
    }

    // Keep old modal code commented out for reference
    /*
    function openTournamentModal_OLD(event) {
        const modal = document.getElementById('tournament-modal');
        const overlay = document.getElementById('modal-overlay');
        const closeBtn = document.getElementById('modal-close');

        // Populate modal with event data
        document.getElementById('modal-title').textContent = event.name;
        document.getElementById('modal-tournament-name').textContent = event.tournament_name;
        document.getElementById('modal-game').textContent = event.game;
        document.getElementById('modal-datetime').textContent = event.start_datetime;

        // Set game image
        const modalImage = document.getElementById('modal-game-image');
        modalImage.src = event.game_image || '';
        modalImage.alt = `${event.game} cover art`;

        // Show online badge if applicable
        const onlineBadge = document.getElementById('modal-online-badge');
        if (event.is_online) {
            onlineBadge.style.display = 'block';
        } else {
            onlineBadge.style.display = 'none';
        }

        // Set registration status
        const regStatus = document.getElementById('modal-registration-status');
        if (event.registration_open) {
            regStatus.className = 'open';
            regStatus.textContent = '✅ Registration is Open - Complete the form below to register!';
        } else {
            regStatus.className = 'closed';
            regStatus.textContent = '🔒 Registration is currently closed for this event.';
        }

        // Load registration iframe
        const iframe = document.getElementById('registration-iframe');
        if (event.registration_open) {
            // Use the event's registration URL
            iframe.src = event.registration_url;
            iframe.style.display = 'block';
        } else {
            iframe.style.display = 'none';
        }

        // Show modal
        modal.classList.add('active');
        document.body.style.overflow = 'hidden'; // Prevent scrolling

        // Close handlers
        const closeModal = () => {
            modal.classList.remove('active');
            document.body.style.overflow = '';
            // Clear iframe to stop any loading
            setTimeout(() => {
                iframe.src = '';
            }, 300);
        };

        closeBtn.onclick = closeModal;
        overlay.onclick = closeModal;
EOD;

$content = str_replace($oldFunction, $newFunction, $content);

// Backup and save
$backup = $jsFile . '.backup.' . date('Y-m-d-His');
copy($jsFile, $backup);
file_put_contents($jsFile, $content);

echo "✓ Updated openTournamentModal function\n";
echo "✓ Backup saved to: $backup\n";
echo "\nThe modal should now work!\n";
echo "Visit: http://localhost/bakersfield/events/index.php\n";
