<?php
/**
 * integrations/sms_send.php
 * Send SMS notifications to sellers
 * Supports: Textbelt (free, no signup), Vonage (production)
 */

/**
 * sendSMS($phone, $message)
 * Send an SMS message to a phone number
 * 
 * @param string $phone - Phone number in international format (e.g. +237612345678)
 * @param string $message - SMS message text
 * @return bool - True if sent successfully, false otherwise
 */
function sendSMS($phone, $message) {
    // Configuration: Choose your SMS provider
    $provider = 'textbelt'; // Options: 'textbelt' (free, dev only) or 'vonage' (production)

    if ($provider === 'textbelt') {
        return sendSMS_Textbelt($phone, $message);
    } elseif ($provider === 'vonage') {
        return sendSMS_Vonage($phone, $message);
    }

    return false;
}

/**
 * sendSMS_Textbelt($phone, $message)
 * Send SMS via Textbelt API (free, no signup required)
 * Limit: 1 free SMS per day per IP address
 */
function sendSMS_Textbelt($phone, $message) {
    $data = [
        'phone'   => $phone,
        'message' => $message,
        'key'     => 'textbelt' // Special free key
    ];

    $ch = curl_init('https://textbelt.com/text');
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($data));
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_TIMEOUT, 10);

    $response = curl_exec($ch);
    $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);

    if ($http_code === 200) {
        $result = json_decode($response);
        return (bool) $result->success;
    }

    return false;
}

/**
 * sendSMS_Vonage($phone, $message)
 * Send SMS via Vonage API (production-ready)
 * Requires: composer require vonage/client
 * Setup: Get API key/secret from vonage.com dashboard
 */
function sendSMS_Vonage($phone, $message) {
    // This requires the Vonage library to be installed via Composer
    // For now, this is a placeholder

    // Uncomment when Vonage SDK is installed:
    /*
    require 'vendor/autoload.php';

    $client = new Vonage\Client(
        new Vonage\Client\Credentials\Basic('YOUR_API_KEY', 'YOUR_API_SECRET')
    );

    $response = $client->sms()->send(
        new Vonage\SMS\Message\SMS($phone, 'PlaceParole', $message)
    );

    $message = $response->current();
    return $message->getStatus() === 0;
    */

    return false; // Placeholder
}

?>
