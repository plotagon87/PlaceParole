<?php
/**
 * modules/admin/user_toggle.php
 * POST endpoint: activate/deactivate user
 */
if (session_status() === PHP_SESSION_NONE) session_start();

require_once '../../config/db.php';
require_once '../../config/auth_guard.php';
require_once '../../config/csrf.php';
require_once '../../config/admin_helpers.php';

csrf_verify();
admin_only();

header('Content-Type: application/json');

try {
    $userId = (int)($_POST['user_id'] ?? 0);
    $action = $_POST['action'] ?? '';
    
    if ($userId <= 0 || !in_array($action, ['activate', 'deactivate'])) {
        throw new Exception('Invalid request');
    }
    
    if ($action === 'deactivate' && $userId === $_SESSION['user_id']) {
        http_response_code(403);
        echo json_encode(['success' => false, 'error' => 'Cannot deactivate your own account']);
        exit;
    }
    
    $stmt = $pdo->prepare("SELECT id FROM users WHERE id = ?");
    $stmt->execute([$userId]);
    if (!$stmt->fetch()) {
        throw new Exception('User not found');
    }
    
    if ($action === 'deactivate') {
        $stmt = $pdo->prepare("UPDATE users SET is_active = 0, deactivated_at = NOW() WHERE id = ?");
        $stmt->execute([$userId]);
        $newStatus = 'inactive';
    } else {
        $stmt = $pdo->prepare("UPDATE users SET is_active = 1, deactivated_at = NULL WHERE id = ?");
        $stmt->execute([$userId]);
        $newStatus = 'active';
    }
    
    logAdminAction($pdo, $_SESSION['user_id'], 
        $action === 'deactivate' ? 'user_deactivated' : 'user_reactivated', 
        'user', $userId, ['action' => $action]
    );
    
    echo json_encode(['success' => true, 'new_status' => $newStatus]);
    
} catch (Exception $e) {
    error_log("user_toggle error: " . $e->getMessage());
    http_response_code(400);
    echo json_encode(['success' => false, 'error' => 'Failed to update user']);
}
?>
