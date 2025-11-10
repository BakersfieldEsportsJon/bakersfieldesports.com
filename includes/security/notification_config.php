<?php
// Notification Configuration
return [
    'twilio' => [
        'sid' => env('TWILIO_SID'),
        'auth_token' => env('TWILIO_AUTH_TOKEN'),
        'phone_number' => env('TWILIO_PHONE_NUMBER')
    ],
    'thresholds' => [
        'failed_logins' => (int) env('SECURITY_NOTIFICATION_THRESHOLD', 3),
        'lockout_duration' => (int) env('SECURITY_LOCKOUT_DURATION', 900),
        'max_attempts' => (int) env('SECURITY_MAX_ATTEMPTS', 5)
    ],
    'notification_channels' => [
        'sms' => true,
        'email' => true,
        'discord' => false
    ]
];
