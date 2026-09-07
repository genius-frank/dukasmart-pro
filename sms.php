<?php
// ================================================================
// DukaSmart Pro — SMS Service via Africa's Talking
// Sign up free at: https://africastalking.com
// Use sandbox for testing, live for production
// ================================================================

define('AT_USERNAME', 'sandbox');          // Change to your AT username in production
define('AT_API_KEY',  'atsk_xxxxxx');      // Paste your Africa's Talking API key here
define('AT_ENV',      'sandbox');          // Change to 'production' when going live

function send_sms($phone, $message) {
    // Normalise Kenyan number to international format
    $phone = preg_replace('/\s+/', '', $phone);
    if (substr($phone, 0, 1) === '0') {
        $phone = '+254' . substr($phone, 1);
    }
    if (substr($phone, 0, 3) === '254') {
        $phone = '+' . $phone;
    }

    $url = AT_ENV === 'sandbox'
        ? 'https://api.sandbox.africastalking.com/version1/messaging'
        : 'https://api.africastalking.com/version1/messaging';

    $data = http_build_query([
        'username' => AT_USERNAME,
        'to'       => $phone,
        'message'  => $message,
        'from'     => 'DukaSmart',
    ]);

    $context = stream_context_create([
        'http' => [
            'method'  => 'POST',
            'header'  => "apikey: " . AT_API_KEY . "\r\n" .
                         "Content-Type: application/x-www-form-urlencoded\r\n" .
                         "Accept: application/json\r\n",
            'content' => $data,
            'timeout' => 10,
        ],
    ]);

    $response = @file_get_contents($url, false, $context);
    return $response !== false;
}

// Send low stock alert to shop owner
function send_low_stock_alert($product_name, $quantity, $unit_type) {
    global $conn;
    $s = get_settings();

    // Only send if shop phone is set
    if (empty($s['shop_phone'])) return;

    $message = "⚠️ DukaSmart Alert\n"
             . "Low stock: {$product_name}\n"
             . "Remaining: {$quantity} {$unit_type}\n"
             . "Please restock soon.\n"
             . "— {$s['shop_name']}";

    send_sms($s['shop_phone'], $message);
}
