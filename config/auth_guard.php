<?php
/**
 * config/auth_guard.php
 * Auth guard = a short script included at the top of every protected page.
 * If a user is not logged in, it redirects them to the login page immediately.
 * 
 * Usage: require_once '../../config/auth_guard.php';
 */

if (session_status() === PHP_SESSION_NONE) session_start();
require_once __DIR__ . '/db.php'; // Load BASE_URL constant

if (!isset($_SESSION['user_id'])) {
    // User is not logged in — send them to the login page
    header('Location: ' . BASE_URL . '/modules/auth/login.php');
    exit;
}

/**
 * manager_only()
 * Optional: restrict a page to managers only
 * Usage at the top of a manager-only page: require_once 'auth_guard.php'; manager_only();
 */
function manager_only() {
    if ($_SESSION['role'] !== 'manager') {
        die("<div style='font-family: Arial; padding: 20px; background: #f8d7da; color: #721c24; border: 1px solid #f5c6cb; border-radius: 5px;'><h2>❌ Access Denied</h2><p>Only managers can access this page.</p></div>");
    }
}

/**
 * seller_only()
 * Optional: restrict a page to sellers only
 * Usage: seller_only();
 */
function seller_only() {
    if ($_SESSION['role'] !== 'seller') {
        die("<div style='font-family: Arial; padding: 20px; background: #f8d7da; color: #721c24; border: 1px solid #f5c6cb; border-radius: 5px;'><h2>❌ Access Denied</h2><p>Only sellers can access this page.</p></div>");
    }
}
?>
