<?php
/**
 * Update openTournamentModal to extract tournament slug from event slug
 */

$file = __DIR__ . '/js/startgg-events.js';
$content = file_get_contents($file);

$oldCode = <<<'EOD'
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
EOD;

$newCode = <<<'EOD'
    function openTournamentModal(event) {
        // Use the new tournament modal system if available
        if (typeof window.tournamentModal !== 'undefined') {
            // Extract tournament slug from event slug
            // Format: "tournament/TOURNAMENT-SLUG/event/EVENT-SLUG"
            let tournamentSlug = event.tournament_slug;

            if (!tournamentSlug && event.slug) {
                const matches = event.slug.match(/tournament\/([^\/]+)/);
                if (matches && matches[1]) {
                    tournamentSlug = matches[1];
                    console.log('Extracted tournament slug:', tournamentSlug);
                }
            }

            if (tournamentSlug) {
                console.log('Opening modal for tournament:', tournamentSlug);
                window.tournamentModal.open(tournamentSlug);
                return;
            } else {
                console.error('Could not extract tournament slug from:', event);
            }
        }

        // Fallback: open registration URL in new tab if modal not available
        console.log('Modal not available, opening registration URL');
        if (event.registration_url) {
            window.open(event.registration_url, '_blank');
        } else {
            alert('Tournament details not available at this time.');
        }
    }
EOD;

$content = str_replace($oldCode, $newCode, $content);

// Backup and save
$backup = $file . '.backup.' . date('Y-m-d-His');
copy($file, $backup);
file_put_contents($file, $content);

echo "✓ Updated openTournamentModal to extract tournament slug\n";
echo "✓ Added console logging for debugging\n";
echo "✓ Backup: $backup\n";
echo "\nHard refresh your browser (Ctrl+Shift+R) and try clicking a tournament!\n";
