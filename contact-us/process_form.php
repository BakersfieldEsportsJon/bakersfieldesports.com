<?php
header('Content-Type: application/json');
session_start();

// Rate limiting
$rateLimitKey = 'contact_form_' . md5($_SERVER['REMOTE_ADDR']);
$rateLimit = 3; // Max submissions per hour
$rateLimitPeriod = 3600; // 1 hour

if (!isset($_SESSION[$rateLimitKey])) {
    $_SESSION[$rateLimitKey] = ['count' => 0, 'timestamp' => time()];
}

if ($_SESSION[$rateLimitKey]['count'] >= $rateLimit && 
    time() - $_SESSION[$rateLimitKey]['timestamp'] < $rateLimitPeriod) {
    http_response_code(429);
    echo json_encode(['success' => false, 'message' => 'Too many requests. Please try again later.']);
    exit;
}

// CSRF protection
if (!isset($_POST['csrf_token']) || $_POST['csrf_token'] !== ($_SESSION['csrf_token'] ?? '')) {
    http_response_code(403);
    echo json_encode(['success' => false, 'message' => 'Invalid CSRF token']);
    exit;
}

// Field validation rules
$validationRules = [
    'name' => [
        'required' => true,
        'max_length' => 100,
        'pattern' => '/^[a-zA-Z\s\-\.\']+$/'
    ],
    'email' => [
        'required' => true,
        'max_length' => 254,
        'filter' => FILTER_VALIDATE_EMAIL
    ],
    'subject' => [
        'required' => true,
        'max_length' => 200,
        'pattern' => '/^[a-zA-Z0-9\s\-\.\',!?]+$/'
    ],
    'message' => [
        'required' => true,
        'max_length' => 2000,
        'pattern' => '/^[\s\S]*$/'
    ]
];

// Validate fields
foreach ($validationRules as $field => $rules) {
    $value = $_POST[$field] ?? '';
    
    if ($rules['required'] && empty($value)) {
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => "Missing required field: $field"]);
        exit;
    }
    
    if (isset($rules['max_length']) && strlen($value) > $rules['max_length']) {
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => "$field exceeds maximum length"]);
        exit;
    }
    
    if (isset($rules['pattern']) && !preg_match($rules['pattern'], $value)) {
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => "Invalid characters in $field"]);
        exit;
    }
    
    if (isset($rules['filter']) && !filter_var($value, $rules['filter'])) {
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => "Invalid format for $field"]);
        exit;
    }
}

// Verify CAPTCHA
$captcha = $_POST['g-recaptcha-response'] ?? '';
if (empty($captcha)) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Please complete the CAPTCHA']);
    exit;
}

// Verify CAPTCHA with Google
$secret = getenv('RECAPTCHA_SECRET_KEY') ?: '6Lf8MlQqAAAAAAVK-Uy9ecJJtS0P0FNYaA_bin-6';
$response = file_get_contents("https://www.google.com/recaptcha/api/siteverify?secret=$secret&response=$captcha");
$responseKeys = json_decode($response, true);

if (!$responseKeys['success']) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'CAPTCHA verification failed']);
    exit;
}

// Sanitize input
$name = htmlspecialchars($_POST['name'], ENT_QUOTES, 'UTF-8');
$email = filter_var($_POST['email'], FILTER_SANITIZE_EMAIL);
$subject = htmlspecialchars($_POST['subject'], ENT_QUOTES, 'UTF-8');
$message = htmlspecialchars($_POST['message'], ENT_QUOTES, 'UTF-8');

// Validate email format
if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Invalid email format']);
    exit;
}

// Prepare email
$to = 'info@bakersfieldesports.com';
$headers = [
    'From' => $email,
    'Reply-To' => $email,
    'X-Mailer' => 'PHP/' . phpversion(),
    'Content-Type' => 'text/plain; charset=UTF-8',
    'X-Priority' => '1'
];

$emailBody = "Name: $name\n";
$emailBody .= "Email: $email\n\n";
$emailBody .= "Subject: $subject\n\n";
$emailBody .= "Message:\n$message";

// Send email
try {
    if (mail($to, $subject, $emailBody, $headers)) {
        // Update rate limit
        $_SESSION[$rateLimitKey]['count']++;
        $_SESSION[$rateLimitKey]['timestamp'] = time();
        
        echo json_encode(['success' => true]);
    } else {
        throw new Exception('Failed to send email');
    }
} catch (Exception $e) {
    error_log('Contact form error: ' . $e->getMessage());
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Failed to send message. Please try again later.']);
}
