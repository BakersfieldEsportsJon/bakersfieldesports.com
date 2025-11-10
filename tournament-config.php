<?php
/**
 * Tournament Configuration
 * Override default values for specific tournaments
 */

return [
    // Tournament slug => settings
    'fortnite-battle-royale-pc-tournament-1' => [
        'entry_fee' => 20.00,
        'bracket_type' => 'Double Elimination',
        'description' => 'Epic Fortnite Battle Royale tournament! Compete for prizes and glory.',
        'rules' => 'Standard tournament rules apply. Must be present 15 minutes before start time.',
        'max_teams' => 32,
        'team_size' => 1
    ],

    'fall-bracket-breaker' => [
        'entry_fee' => 20.00,
        'bracket_type' => 'Double Elimination',
        'description' => 'Super Smash Bros. Ultimate tournament for all skill levels!',
        'max_teams' => 64,
        'team_size' => 1
    ],

    'tekken-8-fall-showdown-bakersfield-esports-center' => [
        'entry_fee' => 20.00,
        'bracket_type' => 'Double Elimination',
        'description' => 'Tekken 8 showdown at Bakersfield eSports Center!',
        'max_teams' => 32,
        'team_size' => 1
    ],

    'ea-fc-25-fall-tournament' => [
        'entry_fee' => 20.00,
        'bracket_type' => 'Swiss + Top 8 Bracket',
        'description' => 'EA Sports FC 25 competitive tournament!',
        'max_teams' => 32,
        'team_size' => 1
    ],

    'madden-26-tournament' => [
        'entry_fee' => 20.00,
        'bracket_type' => 'Double Elimination',
        'description' => 'Madden 26 competitive tournament!',
        'max_teams' => 16,
        'team_size' => 1
    ]
];
