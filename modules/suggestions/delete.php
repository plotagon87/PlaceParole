<?php
/**
 * modules/suggestions/delete.php
 * Delete a suggestion (users can delete own, admins can delete any)
 */
require_once '../../config/auth_guard.php';
require_once '../../config/db.php';

$suggestion_id = (int) ($_POST['suggestion_id'] ?? $_GET['id'] ?? 0);

if (!$suggestion_id) {
    http_response_code(400);
    die('Invalid request');
}

// Fetch the suggestion
$stmt = $pdo->prepare("SELECT id, seller_id, market_id FROM suggestions WHERE id = ?");
$stmt->execute([$suggestion_id]);
$suggestion = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$suggestion) {
    http_response_code(404);
    die('Suggestion not found');
}

// Check permissions
$is_owner = $suggestion['seller_id'] == $_SESSION['user_id'];
$is_admin = $_SESSION['role'] === 'admin';
$same_market = $suggestion['market_id'] == $_SESSION['market_id'];

if (!$same_market || (!$is_owner && !$is_admin)) {
    http_response_code(403);
    die('You do not have permission to delete this suggestion');
}

// Soft delete
csrf_verify();
$stmt = $pdo->prepare("UPDATE suggestions SET deleted_at = NOW() WHERE id = ?");
$stmt->execute([$suggestion_id]);

// Redirect back to list
header('Location: list.php?deleted=1');
exit;

?>
