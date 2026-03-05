<?php
/**
 * integrations/email_notify.php
 * Send email notifications to sellers when their complaint status is updated.
 * Requires PHPMailer: composer require phpmailer/phpmailer
 *
 * PHPMailer = a popular PHP library (reusable code package) for sending emails
 *             reliably through SMTP (Simple Mail Transfer Protocol — the standard
 *             protocol used to send emails across the internet).
 *
 * SETUP INSTRUCTIONS:
 * 1. Install PHPMailer via Composer:
 *    cd C:\xampp\htdocs\PlaceParole
 *    composer require phpmailer/phpmailer
 *
 * 2. Update SMTP credentials at the bottom of this file (lines 48-51)
 *    - Get your Gmail App Password from: https://myaccount.google.com/security
 *    - Use 2-Step Verification to generate one if you don't have it
 */

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

// Check if PHPMailer is available
if (!file_exists(__DIR__ . '/../vendor/autoload.php')) {
    // PHPMailer not installed — silently fail during development
    // Production: you should log this error or send notifications via alternative means
    error_log("PHPMailer not installed. Run: composer require phpmailer/phpmailer");
    return;
}

require_once __DIR__ . '/../vendor/autoload.php'; // Load all Composer packages

/**
 * sendComplaintUpdateEmail($toEmail, $toName, $refCode, $status, $response)
 *
 * Sends an email to the seller informing them that their complaint has been updated.
 *
 * @param string $toEmail   — Seller's email address
 * @param string $toName    — Seller's full name
 * @param string $refCode   — The complaint reference code e.g. MKT-2024-ABC123
 * @param string $status    — The new complaint status: pending | in_review | resolved
 * @param string $response  — The manager's response text
 * @return bool             — Returns true if sent successfully, false otherwise
 */
function sendComplaintUpdateEmail(
    string $toEmail,
    string $toName,
    string $refCode,
    string $status,
    string $response
): bool {

    $mail = new PHPMailer(true); // true = enable exceptions for error handling

    try {
        // ── SMTP Configuration ─────────────────────────────────────────────
        // SMTP (Simple Mail Transfer Protocol) = the protocol used to send emails
        // You can use Gmail's SMTP server for free during development
        $mail->isSMTP();                                      // Use SMTP instead of PHP's mail()
        $mail->Host       = 'smtp.gmail.com';                 // Gmail's outgoing mail server
        $mail->SMTPAuth   = true;                             // Require SMTP authentication
        $mail->Username   = 'fonutchi87@gmail.com';           // Your Gmail address — CHANGE THIS
        $mail->Password   = 'Confinnement';              // Gmail App Password — CHANGE THIS
        // NOTE: Do NOT use your regular Gmail password.
        // Generate an App Password at: myaccount.google.com > Security > 2-Step Verification > App passwords
        $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;   // TLS encryption for security
        $mail->Port       = 587;                              // Gmail's SMTP port for TLS

        // ── Email Content ──────────────────────────────────────────────────
        $mail->setFrom('fonutchi87@gmail.com', 'PlaceParole Market Platform');
        $mail->addAddress($toEmail, $toName);                 // Recipient

        $mail->isHTML(true);                                  // Send HTML email
        $mail->Subject = "Update on your complaint {$refCode} — PlaceParole";

        // Build the HTML email body
        $statusEmoji = ['pending' => '🔴', 'in_review' => '🟡', 'resolved' => '🟢'];
        $emoji = $statusEmoji[$status] ?? '📋';

        $mail->Body = "
            <div style='font-family: Arial, sans-serif; max-width: 600px; margin: 0 auto;'>
                <div style='background: #16a34a; padding: 20px; border-radius: 8px 8px 0 0;'>
                    <h1 style='color: white; margin: 0;'>🗣 PlaceParole</h1>
                </div>
                <div style='padding: 24px; background: #f9fafb; border: 1px solid #e5e7eb;'>
                    <p>Dear <strong>{$toName}</strong>,</p>
                    <p>Your complaint <strong>{$refCode}</strong> has been updated.</p>
                    <p><strong>New Status:</strong> {$emoji} " . ucfirst(str_replace('_', ' ', $status)) . "</p>
                    <div style='background: white; border-left: 4px solid #16a34a; padding: 12px; margin: 16px 0; border-radius: 4px;'>
                        <strong>Manager's Response:</strong><br>
                        " . nl2br(htmlspecialchars($response)) . "
                    </div>
                    <p style='color: #6b7280; font-size: 14px;'>
                        You can track your complaint anytime at your market's PlaceParole portal.
                    </p>
                </div>
            </div>
        ";

        $mail->send();
        return true;

    } catch (Exception $e) {
        // Log the error to PHP's error log without exposing it to the user
        error_log("Email notification failed for {$refCode}: " . $mail->ErrorInfo);
        return false;
    }
}
?>
