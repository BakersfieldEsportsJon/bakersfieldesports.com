<?php
return [
    'encryption' => [
        'key' => $_ENV['SESSION_ENCRYPTION_KEY'] ?? '7X9w!z%C*F-JaNdRgUkXp2s5v8y/B?E(',
        'cipher' => 'aes-256-cbc' // Updated to match our Encryption class
    ],
    'timeout' => (int) ($_ENV['SESSION_TIMEOUT'] ?? 3600),
    'security' => [
        'max_attempts' => (int) ($_ENV['SECURITY_MAX_ATTEMPTS'] ?? 5),
        'lockout_duration' => (int) ($_ENV['SECURITY_LOCKOUT_DURATION'] ?? 900),
        'notify_on_lockout' => true
    ],
    'cookie' => [
        'name' => 'secure_session',
        'path' => '/',
        'domain' => $_SERVER['HTTP_HOST'] ?? 'localhost',
        'secure' => true,
        'httponly' => true,
        'samesite' => 'Strict'
    ]
];
