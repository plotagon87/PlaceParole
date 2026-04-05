<?php
if (session_status() === PHP_SESSION_NONE) session_start();

require_once '../../config/db.php';
require_once '../../config/auth_guard.php';
require_once '../../config/lang.php';

admin_only();

function performHealthCheck($pdo, $checkName, $checkFunc) {
    try {
        $startTime = microtime(true);
        $result = $checkFunc();
        $responseMs = round((microtime(true) - $startTime) * 1000);
        
        $status = $result['status'] ?? 'ok';
        $details = json_encode($result['details'] ?? []);
        
        $stmt = $pdo->prepare("
            INSERT INTO system_health_checks (check_name, status, response_ms, details, checked_at)
            VALUES (?, ?, ?, ?, NOW())
            ON DUPLICATE KEY UPDATE status = ?, response_ms = ?, details = ?, checked_at = NOW()
        ");
        $stmt->execute([$checkName, $status, $responseMs, $details, $status, $responseMs, $details]);
        
        return ['name' => $checkName, 'status' => $status, 'response_ms' => $responseMs, 'details' => $result['details'] ?? []];
    } catch (Exception $e) {
        error_log("Health check $checkName failed: " . $e->getMessage());
        $stmt = $pdo->prepare("
            INSERT INTO system_health_checks (check_name, status, details, checked_at)
            VALUES (?, 'error', ?, NOW())
            ON DUPLICATE KEY UPDATE status = 'error', details = ?, checked_at = NOW()
        ");
        $stmt->execute([$checkName, json_encode(['error' => $e->getMessage()]), json_encode(['error' => $e->getMessage()])]);
        
        return ['name' => $checkName, 'status' => 'error', 'response_ms' => 0, 'details' => ['error' => $e->getMessage()]];
    }
}

$checks = [];

$checks[] = performHealthCheck($pdo, 'Database', function() use ($pdo) {
    $start = microtime(true);
    $pdo->query('SELECT 1');
    $responseTime = round((microtime(true) - $start) * 1000);
    
    $tables = ['markets', 'users', 'complaints', 'suggestions', 'announcements', 'community_feedback', 'notifications', 'admin_activity_log', 'system_health_checks', 'dashboard_widget_config'];
    
    $counts = [];
    foreach ($tables as $table) {
        $result = $pdo->query("SELECT COUNT(*) FROM $table")->fetch(PDO::FETCH_NUM);
        $counts[$table] = $result[0];
    }
    
    return [
        'status' => 'ok',
        'details' => ['response_ms' => $responseTime, 'table_counts' => $counts]
    ];
});

$checks[] = performHealthCheck($pdo, 'Email', function() {
    $exists = file_exists(__DIR__ . '/../../vendor/autoload.php');
    $configured = false;
    
    if (file_exists(__DIR__ . '/../../.env')) {
        $envContent = file_get_contents(__DIR__ . '/../../.env');
        $configured = strpos($envContent, 'GMAIL_USERNAME') !== false && strpos($envContent, 'GMAIL_PASSWORD') !== false;
    }
    
    global $pdo;
    try {
        $stmt = $pdo->prepare("SELECT MAX(created_at) as last_sent FROM notifications WHERE channel = 'email'");
        $stmt->execute();
        $lastSent = $stmt->fetch(PDO::FETCH_ASSOC)['last_sent'];
    } catch (Exception $e) {
        $lastSent = null;
    }
    
    return [
        'status' => $exists && $configured ? 'ok' : 'warning',
        'details' => [
            'phpmailer_installed' => $exists,
            'credentials_configured' => $configured,
            'last_sent' => $lastSent ?? 'Never'
        ]
    ];
});

$checks[] = performHealthCheck($pdo, 'SMS', function() {
    $exists = file_exists(__DIR__ . '/../../integrations/sms_send.php');
    $provider = 'Unknown';
    
    if ($exists) {
        $content = file_get_contents(__DIR__ . '/../../integrations/sms_send.php');
        if (strpos($content, 'textbelt') !== false || strpos($content, 'Textbelt') !== false) {
            $provider = 'Textbelt';
        } elseif (strpos($content, 'vonage') !== false || strpos($content, 'Vonage') !== false) {
            $provider = 'Vonage';
        }
    }
    
    global $pdo;
    try {
        $stmt = $pdo->prepare("SELECT MAX(created_at) as last_sent FROM notifications WHERE channel = 'sms'");
        $stmt->execute();
        $lastSent = $stmt->fetch(PDO::FETCH_ASSOC)['last_sent'];
    } catch (Exception $e) {
        $lastSent = null;
    }
    
    return [
        'status' => $exists ? 'ok' : 'error',
        'details' => [
            'sms_module_exists' => $exists,
            'provider' => $provider,
            'last_sent' => $lastSent ?? 'Never'
        ]
    ];
});

$checks[] = performHealthCheck($pdo, 'WhatsApp', function() {
    $twillioExists = file_exists(__DIR__ . '/../../vendor/twilio');
    $configured = false;
    
    if (file_exists(__DIR__ . '/../../.env')) {
        $envContent = file_get_contents(__DIR__ . '/../../.env');
        $configured = strpos($envContent, 'TWILIO_ACCOUNT_SID') !== false 
            && strpos($envContent, 'TWILIO_AUTH_TOKEN') !== false
            && strpos($envContent, 'TWILIO_WHATSAPP_FROM') !== false;
    }
    
    return [
        'status' => $twillioExists && $configured ? 'ok' : 'warning',
        'details' => [
            'twilio_installed' => $twillioExists,
            'credentials_configured' => $configured
        ]
    ];
});

$checks[] = performHealthCheck($pdo, 'Filesystem', function() {
    $uploadsExists = is_dir(__DIR__ . '/../../uploads/complaints');
    $uploadsWritable = $uploadsExists && is_writable(__DIR__ . '/../../uploads/complaints');
    $logsExists = is_dir(__DIR__ . '/../../logs');
    $logsWritable = $logsExists && is_writable(__DIR__ . '/../../logs');
    $envExists = file_exists(__DIR__ . '/../../.env');
    
    $fileCount = 0;
    if ($uploadsExists) {
        $fileCount = count(glob(__DIR__ . '/../../uploads/complaints/*'));
    }
    
    $logsSize = 0;
    if ($logsExists) {
        foreach (glob(__DIR__ . '/../../logs/*') as $file) {
            if (is_file($file)) $logsSize += filesize($file);
        }
    }
    
    return [
        'status' => $uploadsWritable && $logsWritable && $envExists ? 'ok' : 'warning',
        'details' => [
            'uploads_exists' => $uploadsExists,
            'uploads_writable' => $uploadsWritable,
            'logs_exists' => $logsExists,
            'logs_writable' => $logsWritable,
            'logs_size_mb' => round($logsSize / 1024 / 1024, 2),
            'upload_files_count' => $fileCount,
            'env_exists' => $envExists
        ]
    ];
});

$checks[] = performHealthCheck($pdo, 'Error Rate', function() {
    $errorCount = 0;
    $logFile = __DIR__ . '/../../logs/whatsapp_webhook.log';
    
    if (file_exists($logFile)) {
        $lines = file($logFile);
        $oneDay = time() - (24 * 3600);
        
        foreach ($lines as $line) {
            if (strpos($line, '[' . date('Y-m-d')) !== false && strpos($line, 'ERROR') !== false) {
                $errorCount++;
            }
        }
    }
    
    return [
        'status' => $errorCount > 10 ? 'warning' : 'ok',
        'details' => ['errors_24h' => $errorCount, 'threshold' => 10]
    ];
});

require_once '../../templates/header.php';
?>

<div class="flex min-h-screen bg-gray-50">
    <!-- SIDEBAR -->
    <aside class="fixed left-0 top-0 w-60 h-screen bg-white border-r border-gray-200 flex flex-col shadow-sm z-40">
        <div class="px-6 py-4 border-b border-gray-100">
            <h1 class="text-xl font-bold text-green-700">📊 PlaceParole</h1>
            <p class="text-xs text-gray-500 mt-1">Admin Dashboard</p>
        </div>
        
        <nav class="flex-1 px-3 py-4 space-y-2 overflow-y-auto">
            <a href="<?= BASE_URL ?>/modules/admin/dashboard.php" class="flex items-center gap-3 px-3 py-2.5 rounded-lg text-gray-700 hover:bg-gray-100 transition">
                <span>📊</span> Dashboard
            </a>
            <a href="<?= BASE_URL ?>/modules/admin/overview.php" class="flex items-center gap-3 px-3 py-2.5 rounded-lg text-gray-700 hover:bg-gray-100 transition">
                <span>🌍</span> Overview
            </a>
            <a href="<?= BASE_URL ?>/modules/admin/users.php" class="flex items-center gap-3 px-3 py-2.5 rounded-lg text-gray-700 hover:bg-gray-100 transition">
                <span>👥</span> Users
            </a>
            <a href="<?= BASE_URL ?>/modules/admin/activity_log.php" class="flex items-center gap-3 px-3 py-2.5 rounded-lg text-gray-700 hover:bg-gray-100 transition">
                <span>📋</span> Activity Log
            </a>
            <a href="<?= BASE_URL ?>/modules/admin/system_health.php" class="flex items-center gap-3 px-3 py-2.5 rounded-lg bg-green-50 text-green-700 font-medium">
                <span>⚙️</span> System Health
            </a>
            <hr class="my-3">
            <a href="<?= BASE_URL ?>/index.php" class="flex items-center gap-3 px-3 py-2.5 rounded-lg text-gray-600 hover:bg-gray-100 transition text-sm">
                <span>🏠</span> Back to Site
            </a>
        </nav>
    </aside>

    <!-- MAIN CONTENT -->
    <main class="flex-1 ml-60 p-8">
    <div class="max-w-6xl mx-auto">
        <div class="mb-6">
            <h2 class="text-3xl font-bold text-gray-900">System Health</h2>
            <p class="text-gray-600 text-sm mt-1">Real-time health status of all platform systems</p>
        </div>
        
        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
            <?php foreach ($checks as $check): ?>
            <div class="bg-white rounded-lg shadow-sm border border-gray-200 p-6">
                <div class="flex items-start justify-between mb-4">
                    <h3 class="text-lg font-semibold text-gray-900"><?= htmlspecialchars($check['name']) ?></h3>
                    <span class="px-3 py-1 rounded-full text-sm font-bold
                        <?php
                        switch ($check['status']) {
                            case 'ok': echo 'bg-green-100 text-green-700'; break;
                            case 'warning': echo 'bg-yellow-100 text-yellow-700'; break;
                            case 'error': echo 'bg-red-100 text-red-700'; break;
                        }
                        ?>
                    ">
                        <?php
                        switch ($check['status']) {
                            case 'ok': echo '✅ OK'; break;
                            case 'warning': echo '⚠️ Warning'; break;
                            case 'error': echo '❌ Error'; break;
                        }
                        ?>
                    </span>
                </div>
                
                <?php if ($check['response_ms']): ?>
                <p class="text-sm text-gray-600 mb-4">Response: <strong><?= $check['response_ms'] ?>ms</strong></p>
                <?php endif; ?>
                
                <details class="text-sm">
                    <summary class="cursor-pointer text-blue-600 hover:text-blue-700 font-medium">Show details</summary>
                    <pre class="mt-3 bg-gray-50 p-3 rounded text-xs overflow-x-auto text-gray-700"><?= htmlspecialchars(json_encode($check['details'], JSON_PRETTY_PRINT)) ?></pre>
                </details>
            </div>
            <?php endforeach; ?>
        </div>
    </div>
    </main>
</div>

<?php require_once '../../templates/footer.php'; ?>
