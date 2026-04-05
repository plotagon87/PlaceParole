<?php
/**
 * modules/admin/dashboard_data.php
 * AJAX endpoint for real-time dashboard updates
 */
if (session_status() === PHP_SESSION_NONE) session_start();

require_once '../../config/db.php';
require_once '../../config/auth_guard.php';
require_once '../../config/admin_helpers.php';

admin_only();
header('Content-Type: application/json');

try {
    $widget = $_GET['widget'] ?? 'metrics';
    
    if ($widget === 'config') {
        $stmt = $pdo->prepare("
            SELECT widget_id, is_visible, sort_order 
            FROM dashboard_widget_config 
            WHERE admin_id = ?
            ORDER BY sort_order ASC
        ");
        $stmt->execute([$_SESSION['user_id']]);
        $data = $stmt->fetchAll(PDO::FETCH_ASSOC);
    } else {
        $data = getMetricData($pdo, $widget);
    }
    
    echo json_encode([
        'success' => true,
        'data' => $data,
        'generated_at' => date('c')
    ]);
    
} catch (Exception $e) {
    error_log("dashboard_data error: " . $e->getMessage());
    echo json_encode([
        'success' => false,
        'error' => 'Failed to fetch data'
    ]);
}
?>
