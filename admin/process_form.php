<?php
require_once __DIR__ . '/includes/config.php';
require_once __DIR__ . '/includes/session.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    validate_csrf_token($_POST['csrf_token'] ?? '');
    
    // Sanitize and validate input
    $username = htmlspecialchars(strip_tags($_POST['username'] ?? ''), ENT_QUOTES, 'UTF-8');
    $password = htmlspecialchars(strip_tags($_POST['password'] ?? ''), ENT_QUOTES, 'UTF-8');
    
    if (empty($username) || empty($password)) {
        http_response_code(400);
        exit('Missing required fields');
    }

    // Authentication logic here
    
    // Regenerate session ID after login
    session_regenerate_id(true);
}
