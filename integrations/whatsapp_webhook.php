<?php
/**
 * integrations/whatsapp_webhook.php
 * Twilio WhatsApp webhook for receiving and responding to complaints
 * 
 * This handles incoming WhatsApp messages and:
 * 1. Saves the complaint to database
 * 2. Sends an automatic confirmation back to the user
 */

require_once __DIR__ . '/../config/db.php';

// Get Twilio credentials
$twilio_account = getenv('TWILIO_ACCOUNT_SID');
$twilio_token = getenv('TWILIO_AUTH_TOKEN');
$twilio_from = getenv('TWILIO_WHATSAPP_FROM');

// Log all webhook calls
file_put_contents(
    __DIR__ . '/../logs/whatsapp_webhook.log',
    date('Y-m-d H:i:s') . " - Webhook called via " . $_SERVER['REQUEST_METHOD'] . "\n",
    FILE_APPEND
);

// Only process POST requests
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Content-Type: text/xml');
    echo '<?xml version="1.0" encoding="UTF-8"?><Response></Response>';
    exit;
}

// Get message data from Twilio
$from_number = $_POST['From'] ?? '';  // whatsapp:+237123456789
$message_body = $_POST['Body'] ?? ''; // The complaint text
$message_id = $_POST['MessageSid'] ?? '';

// Remove the 'whatsapp:' prefix from the phone number
$phone = str_replace('whatsapp:', '', $from_number);

// Log the incoming message
file_put_contents(
    __DIR__ . '/../logs/whatsapp_webhook.log',
    "  From: $phone | Message: " . substr($message_body, 0, 50) . "...\n",
    FILE_APPEND
);

// Check if we have a valid message
if (empty($message_body)) {
    header('Content-Type: text/xml');
    echo '<?xml version="1.0" encoding="UTF-8"?><Response></Response>';
    exit;
}

try {
    // Check if this phone number belongs to a registered user
    $stmt = $pdo->prepare("SELECT id, name, market_id FROM users WHERE phone = ?");
    $stmt->execute([$phone]);
    $user = $stmt->fetch();
    
    if ($user) {
        $market_id = $user['market_id'];
        $seller_id = $user['id'];
        $user_name = $user['name'];
    } else {
        // Use default market and find/create placeholder seller
        $market_id = 1;
        
        // Look for placeholder seller
        $placeholder_stmt = $pdo->prepare("SELECT id FROM users WHERE email = 'whatsapp-anonymous@placeholder.local'");
        $placeholder_stmt->execute();
        $placeholder = $placeholder_stmt->fetch();
        
        if ($placeholder) {
            $seller_id = $placeholder['id'];
        } else {
            // Create placeholder seller
            $hash = password_hash('placeholder_' . time(), PASSWORD_BCRYPT);
            $insert_stmt = $pdo->prepare(
                "INSERT INTO users (market_id, name, email, phone, role, password, lang, created_at) 
                 VALUES (?, ?, ?, ?, ?, ?, ?, NOW())"
            );
            $insert_stmt->execute([
                $market_id,
                'WhatsApp Complaints',
                'whatsapp-anonymous@placeholder.local',
                'whatsapp-' . time(),
                'seller',
                $hash,
                'en'
            ]);
            $seller_id = $pdo->lastInsertId();
        }
        
        $user_name = 'WhatsApp User';
    }
    
    // Generate unique reference code
    $ref_code = 'MKT-' . date('Y') . '-' . str_pad(mt_rand(10000000, 99999999), 8, '0', STR_PAD_LEFT);
    
    // Insert complaint into database
    $insert_stmt = $pdo->prepare(
        "INSERT INTO complaints (market_id, seller_id, ref_code, description, channel, status, created_at, updated_at)
         VALUES (?, ?, ?, ?, ?, ?, NOW(), NOW())"
    );
    $insert_stmt->execute([
        $market_id,
        $seller_id,
        $ref_code,
        $message_body,
        'sms',  // Using 'sms' since 'whatsapp' isn't in the enum
        'pending'
    ]);
    
    $complaint_id = $pdo->lastInsertId();
    
    // Log success
    file_put_contents(
        __DIR__ . '/../logs/whatsapp_webhook.log',
        "  ✓ Complaint saved: ID=$complaint_id, Ref=$ref_code\n",
        FILE_APPEND
    );
    
    // Send confirmation message back to user
    if (!empty($twilio_account) && !empty($twilio_token) && !empty($twilio_from)) {
        try {
            require_once __DIR__ . '/../vendor/autoload.php';
            
            $client = new \Twilio\Rest\Client($twilio_account, $twilio_token);
            
            $confirmation_text = "✅ Complaint Received!\n\n";
            $confirmation_text .= "Your complaint has been registered.\n";
            $confirmation_text .= "Reference ID: #$complaint_id\n";
            $confirmation_text .= "Status: Under Review\n\n";
            $confirmation_text .= "You will receive updates via WhatsApp.";
            
            $client->messages->create(
                'whatsapp:' . $phone,
                [
                    'from' => $twilio_from,
                    'body' => $confirmation_text
                ]
            );
            
            file_put_contents(
                __DIR__ . '/../logs/whatsapp_webhook.log',
                "  ✓ Confirmation sent to: $phone\n",
                FILE_APPEND
            );
            
        } catch (Exception $e) {
            file_put_contents(
                __DIR__ . '/../logs/whatsapp_webhook.log',
                "  ✗ Failed to send confirmation: " . $e->getMessage() . "\n",
                FILE_APPEND
            );
        }
    }
    
} catch (Exception $e) {
    file_put_contents(
        __DIR__ . '/../logs/whatsapp_webhook.log',
        "  ✗ ERROR: " . $e->getMessage() . "\n",
        FILE_APPEND
    );
}

// Always respond to Twilio with success
header('Content-Type: text/xml');
echo '<?xml version="1.0" encoding="UTF-8"?>';
echo '<Response></Response>';
?>
