<?php
require_once __DIR__ . '/vendor/autoload.php';
require_once dirname(__DIR__) . '/bootstrap.php';
require_once dirname(__DIR__) . '/admin/includes/db.php';

use Twilio\Rest\Client as TwilioClient;

date_default_timezone_set('America/Los_Angeles');

header('Content-Type: application/json');

$stripeSecret = env('STRIPE_SECRET_KEY');
$webhookSecret = env('STRIPE_WEBHOOK_SECRET');
if (!$stripeSecret || !$webhookSecret) {
    http_response_code(500);
    echo json_encode(['error' => 'Stripe not configured']);
    exit;
}
\Stripe\Stripe::setApiKey($stripeSecret);

$payload = file_get_contents('php://input');
$sigHeader = $_SERVER['HTTP_STRIPE_SIGNATURE'] ?? '';

try {
    $event = \Stripe\Webhook::constructEvent($payload, $sigHeader, $webhookSecret);
} catch (\UnexpectedValueException $e) {
    http_response_code(400);
    echo json_encode(['error' => 'Invalid payload']);
    exit;
} catch (\Stripe\Exception\SignatureVerificationException $e) {
    http_response_code(400);
    echo json_encode(['error' => 'Invalid signature']);
    exit;
}

function normalizePhone($phone) {
    $num = preg_replace('/[^0-9+]/', '', $phone);
    if (strlen($num) === 10) return '+1' . $num;
    if (strlen($num) === 11 && $num[0] === '1') return '+' . $num;
    return $num;
}

function upsertBooking($pdo, $session) {
    $m = $session->metadata ?? (object)[];
    $bookingRef = $m->booking_reference ?? $session->client_reference_id;

    if (DB_ENGINE === 'mysql') {
        $sql = "INSERT INTO party_bookings (
            booking_reference, stripe_session_id, stripe_payment_intent_id,
            customer_name, customer_email, customer_phone,
            party_for, party_age, party_date, party_time, guest_count, party_flow,
            base_pizza_choice, cheese_pizzas, pepperoni_pizzas, pizza_ready_time,
            deposit_amount, processing_fee, total_amount,
            status, payment_status, confirmation_sent_at, created_at, updated_at
        ) VALUES (
            :booking_reference, :stripe_session_id, :stripe_payment_intent_id,
            :customer_name, :customer_email, :customer_phone,
            :party_for, :party_age, :party_date, :party_time, :guest_count, :party_flow,
            :base_pizza_choice, :cheese_pizzas, :pepperoni_pizzas, :pizza_ready_time,
            :deposit_amount, :processing_fee, :total_amount,
            'pending', 'paid', NOW(), NOW(), NOW()
        ) ON DUPLICATE KEY UPDATE
            customer_name = VALUES(customer_name),
            customer_email = VALUES(customer_email),
            customer_phone = VALUES(customer_phone),
            party_for = VALUES(party_for),
            party_age = VALUES(party_age),
            party_date = VALUES(party_date),
            party_time = VALUES(party_time),
            guest_count = VALUES(guest_count),
            party_flow = VALUES(party_flow),
            base_pizza_choice = VALUES(base_pizza_choice),
            cheese_pizzas = VALUES(cheese_pizzas),
            pepperoni_pizzas = VALUES(pepperoni_pizzas),
            pizza_ready_time = VALUES(pizza_ready_time),
            deposit_amount = VALUES(deposit_amount),
            processing_fee = VALUES(processing_fee),
            total_amount = VALUES(total_amount),
            updated_at = NOW();";
    } else {
        $sql = "INSERT INTO party_bookings (
            booking_reference, stripe_session_id, stripe_payment_intent_id,
            customer_name, customer_email, customer_phone,
            party_for, party_age, party_date, party_time, guest_count, party_flow,
            base_pizza_choice, cheese_pizzas, pepperoni_pizzas, pizza_ready_time,
            deposit_amount, processing_fee, total_amount,
            status, payment_status, confirmation_sent_at, created_at, updated_at
        ) VALUES (
            :booking_reference, :stripe_session_id, :stripe_payment_intent_id,
            :customer_name, :customer_email, :customer_phone,
            :party_for, :party_age, :party_date, :party_time, :guest_count, :party_flow,
            :base_pizza_choice, :cheese_pizzas, :pepperoni_pizzas, :pizza_ready_time,
            :deposit_amount, :processing_fee, :total_amount,
            'pending', 'paid', datetime('now'), datetime('now'), datetime('now')
        ) ON CONFLICT(stripe_session_id) DO UPDATE SET
            customer_name = excluded.customer_name,
            customer_email = excluded.customer_email,
            customer_phone = excluded.customer_phone,
            party_for = excluded.party_for,
            party_age = excluded.party_age,
            party_date = excluded.party_date,
            party_time = excluded.party_time,
            guest_count = excluded.guest_count,
            party_flow = excluded.party_flow,
            base_pizza_choice = excluded.base_pizza_choice,
            cheese_pizzas = excluded.cheese_pizzas,
            pepperoni_pizzas = excluded.pepperoni_pizzas,
            pizza_ready_time = excluded.pizza_ready_time,
            deposit_amount = excluded.deposit_amount,
            processing_fee = excluded.processing_fee,
            total_amount = excluded.total_amount,
            updated_at = datetime('now');";
    }

    $stmt = $pdo->prepare($sql);
    $params = [
        'booking_reference' => $bookingRef,
        'stripe_session_id' => $session->id,
        'stripe_payment_intent_id' => $session->payment_intent ?? null,
        'customer_name' => $m->name ?? '',
        'customer_email' => $session->customer_email ?? '',
        'customer_phone' => $m->phone ?? '',
        'party_for' => $m->party_for ?? null,
        'party_age' => isset($m->age) ? (int)$m->age : null,
        'party_date' => isset($m->party_date) ? DateTime::createFromFormat('m-d-Y', $m->party_date)->format('Y-m-d') : null,
        'party_time' => $m->party_time ?? null,
        'guest_count' => isset($m->guest_count) ? (int)$m->guest_count : 0,
        'party_flow' => $m->party_flow ?? 'party_first',
        'base_pizza_choice' => $m->pizza_choice ?? null,
        'cheese_pizzas' => isset($m->additional_pizzas_cheese) ? (int)$m->additional_pizzas_cheese : 0,
        'pepperoni_pizzas' => isset($m->additional_pizzas_pepperoni) ? (int)$m->additional_pizzas_pepperoni : 0,
        'pizza_ready_time' => $m->pizza_ready_time ?? null,
        'deposit_amount' => isset($m->deposit_amount) ? (float)$m->deposit_amount : 100.00,
        'processing_fee' => isset($session->amount_total) ? ($session->amount_total / 100) - (float)($m->deposit_amount ?? 100.00) : 0,
        'total_amount' => isset($session->amount_total) ? ($session->amount_total / 100) : 0,
    ];
    $stmt->execute($params);

    return $bookingRef;
}

function sendConfirmation($name, $email, $phone, $bookingRef, $partyDate, $partyTime, $pizzaChoice, $additionalCheese, $additionalPepperoni) {
    $partyTs = strtotime("$partyDate $partyTime");
    $partyDateTime = date('l, F j, Y \a\t g:i A', $partyTs);

    $pizzaDetails = "Pizzas:";
    if ($pizzaChoice === '2 Cheese') $pizzaDetails .= "\n- 2x Cheese";
    elseif ($pizzaChoice === '2 Pepperoni') $pizzaDetails .= "\n- 2x Pepperoni";
    else $pizzaDetails .= "\n- 1x Cheese\n- 1x Pepperoni";
    if ((int)$additionalCheese > 0) $pizzaDetails .= "\n- {$additionalCheese}x Additional Cheese";
    if ((int)$additionalPepperoni > 0) $pizzaDetails .= "\n- {$additionalPepperoni}x Additional Pepperoni";

    $to = $email;
    $subject = 'Party Booking Confirmation - Bakersfield eSports Center';
    $message = "Dear $name,\n\nThank you for booking your party with Bakersfield eSports Center!\n\nBooking Reference: $bookingRef\n\nParty For: $name\nDate & Time: $partyDateTime\n\n$pizzaDetails\n\nWe will contact you within 24 hours at $phone to confirm details.\n\nBest regards,\nBakersfield eSports Center Team";
    $headers = "From: Bakersfield eSports Center <noreply@bakersfieldesports.com>\r\n" .
               "Content-Type: text/plain; charset=UTF-8\r\n";

    @mail($to, $subject, $message, $headers);
}

function sendSmsIfConfigured($to, $text) {
    $sid = env('TWILIO_SID');
    $token = env('TWILIO_AUTH_TOKEN');
    $from = env('TWILIO_PHONE_NUMBER');
    if (!$sid || !$token || !$from || !$to) return;
    try {
        $client = new TwilioClient($sid, $token);
        $client->messages->create(normalizePhone($to), [
            'from' => $from,
            'body' => $text
        ]);
    } catch (Throwable $e) {
        error_log('Twilio SMS error: ' . $e->getMessage());
    }
}

function sendZapierWebhook($bookingData) {
    $zapierUrl = env('ZAPIER_WEBHOOK_URL');
    if (!$zapierUrl) {
        error_log('Zapier webhook URL not configured');
        return;
    }

    try {
        $ch = curl_init($zapierUrl);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($bookingData));
        curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/json']);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_TIMEOUT, 10);

        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        if ($httpCode < 200 || $httpCode >= 300) {
            error_log("Zapier webhook failed with HTTP $httpCode: $response");
        }
    } catch (Throwable $e) {
        error_log('Zapier webhook error: ' . $e->getMessage());
    }
}

function sendDiscordWebhook($bookingData) {
    $discordUrl = env('DISCORD_WEBHOOK_URL');
    if (!$discordUrl) {
        error_log('Discord webhook URL not configured');
        return;
    }

    // Format party flow for display
    $partyFlowText = $bookingData['party_flow'] === 'party_first'
        ? '🍕 Party Area First (Pizza → Games)'
        : '🎮 Game Time First (Games → Pizza)';

    // Build Discord embed
    $embed = [
        'embeds' => [[
            'title' => '🎉 New Party Booking!',
            'color' => 0x00ff00, // Green
            'fields' => [
                ['name' => '📋 Booking Reference', 'value' => $bookingData['booking_reference'], 'inline' => true],
                ['name' => '👤 Customer Name', 'value' => $bookingData['customer_name'], 'inline' => true],
                ['name' => '📧 Email', 'value' => $bookingData['customer_email'], 'inline' => false],
                ['name' => '📞 Phone', 'value' => $bookingData['customer_phone'], 'inline' => true],
                ['name' => '🎂 Party For', 'value' => "{$bookingData['party_for']} (Age: {$bookingData['party_age']})", 'inline' => true],
                ['name' => '📅 Date & Time', 'value' => $bookingData['party_datetime'], 'inline' => false],
                ['name' => '🎯 Party Flow', 'value' => $partyFlowText, 'inline' => false],
                ['name' => '🍕 Pizza Ready Time', 'value' => $bookingData['pizza_ready_time'], 'inline' => true],
                ['name' => '🍕 Pizza Order', 'value' => $bookingData['pizza_details'], 'inline' => false],
                ['name' => '💰 Total Amount', 'value' => '$' . number_format($bookingData['total_amount'], 2), 'inline' => true],
                ['name' => '💵 Deposit Paid', 'value' => '$' . number_format($bookingData['deposit_amount'], 2), 'inline' => true],
            ],
            'timestamp' => date('c'),
            'footer' => ['text' => 'Bakersfield eSports Center']
        ]]
    ];

    try {
        $ch = curl_init($discordUrl);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($embed));
        curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/json']);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_TIMEOUT, 10);

        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        if ($httpCode < 200 || $httpCode >= 300) {
            error_log("Discord webhook failed with HTTP $httpCode: $response");
        }
    } catch (Throwable $e) {
        error_log('Discord webhook error: ' . $e->getMessage());
    }
}

if ($event->type === 'checkout.session.completed') {
    $session = $event->data->object;
    $bookingRef = upsertBooking($pdo, $session);

    $m = $session->metadata ?? (object)[];

    // Send email confirmation
    sendConfirmation(
        $m->name ?? 'Customer',
        $session->customer_email ?? '',
        $m->phone ?? '',
        $bookingRef,
        isset($m->party_date) ? DateTime::createFromFormat('m-d-Y', $m->party_date)->format('Y-m-d') : '',
        $m->party_time ?? '',
        $m->pizza_choice ?? '',
        $m->additional_pizzas_cheese ?? 0,
        $m->additional_pizzas_pepperoni ?? 0
    );

    // Send SMS (optional - Twilio)
    $sms = 'Bakersfield eSports - Your party booking is confirmed. Ref ' . $bookingRef . '. See email for details.';
    sendSmsIfConfigured($m->phone ?? '', $sms);

    // Prepare booking data for webhooks
    $partyDate = isset($m->party_date) ? DateTime::createFromFormat('m-d-Y', $m->party_date) : null;
    $partyTime = $m->party_time ?? '';
    $partyDateTime = $partyDate ? $partyDate->format('l, F j, Y') . ' at ' . date('g:i A', strtotime($partyTime)) : '';

    // Build pizza details
    $pizzaDetails = '';
    if (isset($m->pizza_choice)) {
        if ($m->pizza_choice === 'cheese') $pizzaDetails = '2x Cheese';
        elseif ($m->pizza_choice === 'pepperoni') $pizzaDetails = '2x Pepperoni';
        else $pizzaDetails = '1x Cheese, 1x Pepperoni';
    }
    if (isset($m->additional_pizzas_cheese) && (int)$m->additional_pizzas_cheese > 0) {
        $pizzaDetails .= "\n+ {$m->additional_pizzas_cheese}x Cheese";
    }
    if (isset($m->additional_pizzas_pepperoni) && (int)$m->additional_pizzas_pepperoni > 0) {
        $pizzaDetails .= "\n+ {$m->additional_pizzas_pepperoni}x Pepperoni";
    }

    $webhookData = [
        'booking_reference' => $bookingRef,
        'customer_name' => $m->name ?? '',
        'customer_email' => $session->customer_email ?? '',
        'customer_phone' => $m->phone ?? '',
        'party_for' => $m->party_for ?? '',
        'party_age' => $m->age ?? '',
        'party_date' => $partyDate ? $partyDate->format('Y-m-d') : '',
        'party_time' => $partyTime,
        'party_datetime' => $partyDateTime,
        'party_flow' => $m->party_flow ?? 'party_first',
        'pizza_ready_time' => $m->pizza_ready_time ?? '',
        'pizza_details' => trim($pizzaDetails),
        'deposit_amount' => isset($m->deposit_amount) ? (float)$m->deposit_amount : 100.00,
        'total_amount' => isset($session->amount_total) ? ($session->amount_total / 100) : 0,
        'stripe_session_id' => $session->id,
        'created_at' => date('Y-m-d H:i:s')
    ];

    // Send to Zapier (for Textla SMS)
    sendZapierWebhook($webhookData);

    // Send to Discord
    sendDiscordWebhook($webhookData);
}

http_response_code(200);
echo json_encode(['status' => 'ok']);