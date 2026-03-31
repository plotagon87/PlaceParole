<?php
/**
 * modules/announcements/delete.php
 * Delete an announcement (managers/admins only)
 */
require_once '../../config/auth_guard.php';
manager_only();

require_once '../../config/db.php';

$announcement_id = (int) ($_POST['announcement_id'] ?? $_GET['id'] ?? 0);

if (!$announcement_id) {
    http_response_code(400);
    die('Invalid request');
}

// Fetch the announcement
$stmt = $pdo->prepare("SELECT id, manager_id, market_id FROM announcements WHERE id = ?");
$stmt->execute([$announcement_id]);
$announcement = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$announcement) {
    http_response_code(404);
    die('Announcement not found');
}

// Check permissions
$is_owner = $announcement['manager_id'] == $_SESSION['user_id'];
$is_admin = $_SESSION['role'] === 'admin';
$same_market = $announcement['market_id'] == $_SESSION['market_id'];

if (!$same_market || (!$is_owner && !$is_admin)) {
    http_response_code(403);
    die('You do not have permission to delete this announcement');
}

// Soft delete
csrf_verify();
$stmt = $pdo->prepare("UPDATE announcements SET deleted_at = NOW() WHERE id = ?");
$stmt->execute([$announcement_id]);

// Redirect back to list
header('Location: list.php?deleted=1');
exit;

?>
