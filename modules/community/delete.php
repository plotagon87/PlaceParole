<?php
/**
 * modules/community/delete.php
 * Delete feedback (users can delete own, admins can delete any)
 */
require_once '../../config/auth_guard.php';
require_once '../../config/db.php';

$feedback_id = (int) ($_POST['feedback_id'] ?? $_GET['id'] ?? 0);

if (!$feedback_id) {
    http_response_code(400);
    die('Invalid request');
}

// Fetch the feedback
$stmt = $pdo->prepare("SELECT id, user_id, market_id FROM community_feedback WHERE id = ?");
$stmt->execute([$feedback_id]);
$feedback = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$feedback) {
    http_response_code(404);
    die('Feedback not found');
}

// Check permissions
$is_owner = $feedback['user_id'] == $_SESSION['user_id'];
$is_admin = $_SESSION['role'] === 'admin';
$same_market = $feedback['market_id'] == $_SESSION['market_id'];

if (!$same_market || (!$is_owner && !$is_admin)) {
    http_response_code(403);
    die('You do not have permission to delete this feedback');
}

// Soft delete
csrf_verify();
$stmt = $pdo->prepare("UPDATE community_feedback SET deleted_at = NOW() WHERE id = ?");
$stmt->execute([$feedback_id]);

// Redirect back to list
header('Location: list.php?deleted=1');
exit;

?>
