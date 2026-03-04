<?php
/**
 * modules/auth/logout.php
 * Logout the user and redirect to login
 */
session_start();

// Regenerate session ID before destroying to prevent session fixation attacks
session_regenerate_id(true);

// Now destroy all session data — effectively logs the user out
session_destroy();

// Redirect to login with a success message
header('Location: login.php');
exit;
?>
