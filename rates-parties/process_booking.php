<?php
require_once __DIR__ . '/vendor/autoload.php';
require_once dirname(__DIR__) . '/bootstrap.php';

date_default_timezone_set('America/Los_Angeles');
header('Content-Type: application/json');

try {
    $stripeSecret = env('STRIPE_SECRET_KEY');
    if (!$stripeSecret) {
        http_response_code(500);
        echo json_encode(['error' => 'Stripe not configured']);
        exit;
    }
    \Stripe\Stripe::setApiKey($stripeSecret);

    $data = json_decode(file_get_contents('php://input'), true);
    if (!is_array($data)) {
        http_response_code(400);
        echo json_encode(['error' => 'Invalid JSON']);
        exit;
    }

    // Required fields
    $required = ['name','phone','email','partyFor','age','pizzaChoice','amount','partyDate','partyTime','partyFlow','pizzaReadyTime'];
    foreach ($required as $field) {
        if (!isset($data[$field]) || $data[$field] === '') {
            http_response_code(422);
            echo json_encode(['error' => "Missing required field: $field"]);
            exit;
        }
    }

    // 48 hour rule in PT; dates expected MM-DD-YYYY
    $tz = new DateTimeZone('America/Los_Angeles');
    $partyDate = DateTime::createFromFormat('m-d-Y', $data['partyDate'], $tz) ?: new DateTime($data['partyDate'], $tz);
    $partyTime = DateTime::createFromFormat('H:i', $data['partyTime'], $tz) ?: new DateTime($data['partyTime'], $tz);
    $partyDateTime = new DateTime($partyDate->format('Y-m-d') . ' ' . $partyTime->format('H:i:s'), $tz);
    $minDateTime = new DateTime('+48 hours', $tz);

    if ($partyDateTime < $minDateTime) {
        http_response_code(422);
        echo json_encode(['error' => 'Party must be at least 48 hours from now']);
        exit;
    }

    $cheeseQty = isset($data['additionalCheeseQty']) ? (int)$data['additionalCheeseQty'] : 0;
    $pepperoniQty = isset($data['additionalPepperoniQty']) ? (int)$data['additionalPepperoniQty'] : 0;

    // Booking reference BK-MMDDYYYY-XXXXXX
    $bookingReference = 'BK-' . $partyDate->format('mdY') . '-' . strtoupper(substr(bin2hex(random_bytes(4)), 0, 6));

    $description = "Party Booking Deposit - For: {$data['partyFor']} (Age: {$data['age']})\n" .
                   "Booking Reference: {$bookingReference}\n" .
                   "Pizza Types:\n- Base Package: {$data['pizzaChoice']}\n" .
                   ($cheeseQty > 0 ? "- Cheese Pizzas: {$cheeseQty}\n" : '') .
                   ($pepperoniQty > 0 ? "- Pepperoni Pizzas: {$pepperoniQty}\n" : '') .
                   "\nPizza Ready Time: {$data['pizzaReadyTime']}\n\nWe will contact you within 24 hours to confirm your booking details.";

    $session = \Stripe\Checkout\Session::create([
        'payment_method_types' => ['card'],
        'client_reference_id' => $bookingReference,
        'line_items' => [[
            'price_data' => [
                'currency' => 'usd',
                'unit_amount' => (int)round($data['amount'] * 100),
                'product_data' => [
                    'name' => 'Party Booking Deposit',
                    'description' => $description,
                ],
            ],
            'quantity' => 1,
        ]],
        'mode' => 'payment',
        'success_url' => 'https://bakersfieldesports.com/rates-parties/booking-success.html',
        'cancel_url' => 'https://bakersfieldesports.com/rates-parties/index.html',
        'customer_email' => $data['email'],
        'metadata' => [
            'booking_reference' => $bookingReference,
            'name' => $data['name'],
            'phone' => $data['phone'],
            'party_for' => $data['partyFor'],
            'age' => (int)$data['age'],
            'pizza_choice' => $data['pizzaChoice'],
            'additional_pizzas_cheese' => $cheeseQty,
            'additional_pizzas_pepperoni' => $pepperoniQty,
            'party_date' => $partyDate->format('m-d-Y'),
            'party_time' => $partyTime->format('H:i'),
            'party_flow' => $data['partyFlow'],
            'pizza_ready_time' => $data['pizzaReadyTime'],
            'deposit_amount' => '100.00',
            'total_amount' => number_format((float)$data['amount'], 2, '.', '')
        ]
    ]);

    echo json_encode(['id' => $session->id, 'booking_reference' => $bookingReference]);
} catch (Throwable $e) {
    // Sanitize log
    $logData = $data ?? [];
    if (isset($logData['email'])) $logData['email'] = '****' . substr($logData['email'], strrpos($logData['email'], '@'));
    if (isset($logData['phone'])) $logData['phone'] = '****' . substr($logData['phone'], -4);
    error_log('Party Booking Error: ' . json_encode(['error' => $e->getMessage(), 'data' => $logData]));
    http_response_code(500);
    echo json_encode(['error' => 'Unable to process booking']);
}