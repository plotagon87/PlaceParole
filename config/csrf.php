<?php
/**
 * config/csrf.php
 * CSRF (Cross-Site Request Forgery) protection functions
 * Safe to include on ANY page (public or protected)
 * 
 * Usage:
 *   require_once '../../config/csrf.php';
 *   <input type="hidden" name="csrf_token" value="<?= csrf_token() ?>">
 *   
 *   if ($_POST) csrf_verify();  // Verify on form submission
 */

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

/**
 * csrf_token()
 * Generates a CSRF token and stores it in the session.
 * A CSRF (Cross-Site Request Forgery) token is a random secret value
 * that proves a form was submitted by YOUR page and not by a malicious website.
 * Call this function to GET the token for embedding in forms.
 * 
 * Usage: <input type="hidden" name="csrf_token" value="<?= csrf_token() ?>">
 */
function csrf_token() {
    // If no token exists in the session yet, create one
    if (empty($_SESSION['csrf_token'])) {
        // bin2hex() converts random bytes to a readable hexadecimal string
        // random_bytes(32) generates 32 truly random bytes — very hard to guess
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

/**
 * csrf_verify()
 * Checks that the CSRF token submitted with a form matches the one in the session.
 * Call this at the top of every POST handler before processing any form data.
 * 
 * Usage: if ($_SERVER['REQUEST_METHOD'] === 'POST') { csrf_verify(); ... }
 */
function csrf_verify() {
    $submitted_token = $_POST['csrf_token'] ?? '';
    $session_token   = $_SESSION['csrf_token'] ?? '';

    // hash_equals() does a timing-safe comparison — prevents "timing attacks"
    // (a hacking technique that measures how long a comparison takes to guess the value)
    if (!hash_equals($session_token, $submitted_token)) {
        http_response_code(403); // 403 = HTTP status code meaning "Forbidden"
        die("<div style='font-family: Arial; padding: 20px; background: #f8d7da; color: #721c24; border: 1px solid #f5c6cb; border-radius: 5px;'><h2>❌ Security Error</h2><p>CSRF token mismatch. Please go back and try again.</p></div>");
    }
}
?>
