<?php
/**
 * modules/auth/logout.php
 * Logout the user and redirect to login
 */
session_start();
session_destroy(); // Destroys all session data — effectively logs the user out

// Redirect to login with a success message
header('Location: login.php');
exit;
?>
