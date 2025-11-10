<?php
/**
 * Marketing Opt-In handler (AJAX) with DEBUG logging + admin-only email.
 * Expects JSON: { "name": "...", "phone": "...", "email": "..." }
 * Responds JSON: { success: true } OR { error: "...", debug: {...} }
 */

declare(strict_types=1);

// ===================== CONFIG =====================
const LOCAL_TZ            = 'America/Los_Angeles';
const DEBUG_ENABLED       = true;   // flip to false in production
const DEBUG_EXPOSE_PII    = false;  // only enable temporarily during troubleshooting

const ADMIN_TO            = 'jon@bakersfieldesports.com';
const FROM_ADDR           = 'noreply@bakersfieldesports.com'; // should exist on your domain
const FROM_NAME           = 'Bakersfield eSports Center';
const USE_ENVELOPE_SENDER = true;   // sets -f FROM_ADDR for SPF/DMARC alignment
const BCC_ADDR            = '';     // optional: e.g., 'yourtest@gmail.com' during testing

// ===================== HEADERS =====================
header('Content-Type: application/json; charset=utf-8');

// ===================== DEBUG SETUP =================
$debugLog = [
  'ts_utc'      => gmdate('Y-m-d H:i:s'),
  'ts_local'    => (new DateTime('now', new DateTimeZone(LOCAL_TZ)))->format('Y-m-d H:i:s T'),
  'php_version' => PHP_VERSION,
  'sapi'        => php_sapi_name(),
  'server_name' => $_SERVER['SERVER_NAME'] ?? null,
  'remote_addr' => $_SERVER['REMOTE_ADDR'] ?? null,
  'request_uri' => $_SERVER['REQUEST_URI'] ?? null,
  'mail_env'    => [
    'sendmail_path' => ini_get('sendmail_path'),
    'SMTP'          => ini_get('SMTP'),
    'smtp_port'     => ini_get('smtp_port'),
  ],
  'steps'       => []
];
$log = function (string $label, $data = null) use (&$debugLog) {
  $debugLog['steps'][] = ['t' => microtime(true), 'step' => $label, 'data' => $data];
};
set_error_handler(function ($severity, $message, $file, $line) use ($log) {
  $log('php_error', compact('severity','message','file','line'));
  return false;
});

// ===================== HELPERS =====================
$sanitize_plain = function (string $s, int $maxLen = 200): string {
  $s = preg_replace('/[^\P{C}\t\r\n]/u', '', $s) ?? '';
  $s = preg_replace('/\s{2,}/u', ' ', $s) ?? '';
  $s = trim($s);
  if (mb_strlen($s, 'UTF-8') > $maxLen) $s = mb_substr($s, 0, $maxLen, 'UTF-8');
  return $s;
};
$sanitize_header = function (string $s): string {
  return str_replace(["\r","\n",":"], '', trim($s));
};
$mask_email = function (?string $e): ?string {
  if (!$e) return null;
  return preg_replace('/^(.).+(@.*)$/', '$1***$2', $e);
};
$mask_last4 = function (string $p): string {
  return (strlen($p) > 4) ? str_repeat('*', max(0, strlen($p)-4)).substr($p, -4) : $p;
};

// Minimal mail sender (plain text; From only; no Reply-To)
$send_admin_mail = function (string $to, string $subject, string $body) use ($sanitize_header) {
  $from  = $sanitize_header(FROM_ADDR);
  $name  = $sanitize_header(FROM_NAME);

  $headers  = "From: {$name} <{$from}>\r\n";
  if (strlen(BCC_ADDR)) {
    $headers .= "Bcc: " . $sanitize_header(BCC_ADDR) . "\r\n";
  }
  $headers .= "Content-Type: text/plain; charset=UTF-8\r\n";
  $headers .= "MIME-Version: 1.0\r\n";
  $headers .= "X-Mailer: PHP/" . phpversion();

  $params = USE_ENVELOPE_SENDER ? ('-f ' . $from) : '';

  $ok = @mail($to, $subject, $body, $headers, $params);
  $err = null;
  if (!$ok) {
    $last = error_get_last();
    $err = $last['message'] ?? 'mail() failed';
  }
  return ['ok' => $ok, 'err' => $err];
};

// ===================== READ BODY =====================
$raw = file_get_contents('php://input');
$log('raw_body', ['len' => strlen((string)$raw), 'preview' => substr((string)$raw, 0, 200)]);
$data = json_decode($raw, true);
if (!is_array($data)) {
  http_response_code(400);
  $resp = ['error' => 'Invalid request body (expected JSON).'];
  if (DEBUG_ENABLED) $resp['debug'] = $debugLog;
  echo json_encode($resp);
  exit;
}

// ===================== REQUIRED FIELDS =====================
foreach (['name','phone','email'] as $f) {
  if (!isset($data[$f]) || $data[$f] === '' || $data[$f] === null) {
    http_response_code(400);
    $log('missing_field', ['field'=>$f]);
    $resp = ['error' => "Missing required field: {$f}"];
    if (DEBUG_ENABLED) $resp['debug'] = $debugLog;
    echo json_encode($resp);
    exit;
  }
}

// ===================== SANITIZE & VALIDATE =====================
$name       = $sanitize_plain((string)$data['name'], 120);
$phone_raw  = $sanitize_plain((string)$data['phone'], 40);
$email_val  = filter_var((string)$data['email'], FILTER_VALIDATE_EMAIL);

// phone: normalize to digits and require exactly 10 digits
$phone_digits = preg_replace('/\D/', '', $phone_raw) ?? '';
if (strlen($phone_digits) !== 10) {
  http_response_code(422);
  $log('phone_validation_failed', [
    'phone_raw'    => DEBUG_EXPOSE_PII ? $phone_raw    : $mask_last4($phone_raw),
    'phone_digits' => DEBUG_EXPOSE_PII ? $phone_digits : $mask_last4($phone_digits),
    'digit_count'  => strlen($phone_digits),
  ]);
  $resp = ['error' => 'Phone must have exactly 10 digits'];
  if (DEBUG_ENABLED) $resp['debug'] = $debugLog;
  echo json_encode($resp);
  exit;
}
if ($email_val === false) {
  http_response_code(422);
  $resp = ['error' => 'Invalid email address'];
  if (DEBUG_ENABLED) $resp['debug'] = $debugLog;
  echo json_encode($resp);
  exit;
}

$phone_display = substr($phone_digits,0,3) . '-' . substr($phone_digits,3,3) . '-' . substr($phone_digits,6);

$log('sanitized', [
  'name'         => DEBUG_EXPOSE_PII ? $name : (strlen($name)? substr($name,0,1).'***' : ''),
  'phone_raw'    => DEBUG_EXPOSE_PII ? $phone_raw : $mask_last4($phone_raw),
  'phone_digits' => DEBUG_EXPOSE_PII ? $phone_digits : $mask_last4($phone_digits),
  'email'        => DEBUG_EXPOSE_PII ? $email_val : $mask_email($email_val),
]);

// ===================== EMAIL CONTENT (ADMIN ONLY) =====================
$tz        = new DateTimeZone(LOCAL_TZ);
$now       = new DateTime('now', $tz);
$timeLocal = $now->format('Y-m-d H:i:s T'); // e.g., 2025-09-18 12:11:42 PDT

$admin_to   = ADMIN_TO;
$admin_subj = 'New Marketing Opt-In';
$admin_body = "New Marketing Opt-In\n"
            . "--------------------\n"
            . "Name:  {$name}\n"
            . "Phone: {$phone_display}\n"
            . "Email: {$email_val}\n"
            . "Time:  {$timeLocal}\n";

// ===================== SEND EMAIL =====================
$r = $send_admin_mail($admin_to, $admin_subj, $admin_body);
$send_results = [
  'admin' => [
    'to'  => DEBUG_EXPOSE_PII ? $admin_to : $mask_email($admin_to),
    'ok'  => $r['ok'],
    'err' => $r['err'],
  ]
];

if (!$r['ok']) {
  error_log('Marketing Opt-In: failed to send admin notification email to ' . $mask_email($admin_to));
}

$log('mail_results', $send_results);

// If admin email failed, treat as 500
if (!$send_results['admin']['ok']) {
  http_response_code(500);
  $resp = ['error' => 'Failed to send notification email'];
  if (DEBUG_ENABLED) $resp['debug'] = $debugLog;
  echo json_encode($resp);
  exit;
}

// ===================== SUCCESS =====================
$log('done', ['success' => true]);
echo json_encode([
  'success' => true,
  'debug'   => DEBUG_ENABLED ? $debugLog : null
]);
exit;
