<?php
/**
 * modules/admin/dashboard_widget_save.php
 * Save widget configuration
 */
if (session_status() === PHP_SESSION_NONE) session_start();

require_once '../../config/db.php';
require_once '../../config/auth_guard.php';
require_once '../../config/csrf.php';

csrf_verify();
admin_only();

header('Content-Type: application/json');

try {
    $body = json_decode(file_get_contents('php://input'), true);
    
    if (!isset($body['widgets']) || !is_array($body['widgets'])) {
        throw new Exception('Invalid request format');
    }
    
    $adminId = $_SESSION['user_id'];
    
    foreach ($body['widgets'] as $widget) {
        $widgetId = $widget['id'] ?? null;
        $isVisible = $widget['visible'] ? 1 : 0;
        $sortOrder = (int)($widget['order'] ?? 0);
        
        if (!$widgetId) continue;
        
        $stmt = $pdo->prepare("
            INSERT INTO dashboard_widget_config (admin_id, widget_id, is_visible, sort_order)
            VALUES (?, ?, ?, ?)
            ON DUPLICATE KEY UPDATE is_visible = ?, sort_order = ?
        ");
        $stmt->execute([$adminId, $widgetId, $isVisible, $sortOrder, $isVisible, $sortOrder]);
    }
    
    echo json_encode(['success' => true]);
    
} catch (Exception $e) {
    error_log("widget_save error: " . $e->getMessage());
    http_response_code(400);
    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
}
?>
