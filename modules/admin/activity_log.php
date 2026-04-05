<?php
if (session_status() === PHP_SESSION_NONE) session_start();

require_once '../../config/db.php';
require_once '../../config/auth_guard.php';
require_once '../../config/lang.php';
require_once '../../config/admin_helpers.php';

admin_only();

$fromDate = $_GET['from_date'] ?? date('Y-m-d', strtotime('-7 days'));
$toDate = $_GET['to_date'] ?? date('Y-m-d');
$actionType = $_GET['action_type'] ?? '';
$actorId = $_GET['actor_id'] ?? '';
$page = max(1, (int)($_GET['page'] ?? 1));

$activityData = getActivityLogEntries($pdo, $page, 50, $fromDate, $toDate, $actionType ?: null, $actorId ? (int)$actorId : null);

try {
    $stmt = $pdo->prepare("SELECT DISTINCT action_type FROM admin_activity_log ORDER BY action_type");
    $stmt->execute();
    $actionTypes = array_column($stmt->fetchAll(PDO::FETCH_ASSOC), 'action_type');
} catch (Exception $e) {
    $actionTypes = [];
}

try {
    $stmt = $pdo->prepare("SELECT DISTINCT u.id, u.name FROM admin_activity_log aal JOIN users u ON u.id = aal.actor_id ORDER BY u.name");
    $stmt->execute();
    $actors = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (Exception $e) {
    $actors = [];
}

if (isset($_GET['export']) && $_GET['export'] === '1') {
    header('Content-Type: text/csv; charset=utf-8-sig');
    header('Content-Disposition: attachment; filename="activity_log_' . date('Y-m-d-H-i-s') . '.csv"');
    
    $f = fopen('php://output', 'w');
    fputcsv($f, ['Timestamp', 'Actor', 'Action', 'Subject', 'IP Address']);
    
    foreach ($activityData['entries'] as $entry) {
        fputcsv($f, [
            $entry['created_at'],
            $entry['actor_name'] ?? 'System',
            $entry['action_type'],
            ($entry['subject_type'] ?? '') . ' #' . ($entry['subject_id'] ?? ''),
            $entry['ip_address'] ?? '-'
        ]);
    }
    fclose($f);
    exit;
}

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
            <a href="<?= BASE_URL ?>/modules/admin/activity_log.php" class="flex items-center gap-3 px-3 py-2.5 rounded-lg bg-green-50 text-green-700 font-medium">
                <span>📋</span> Activity Log
            </a>
            <a href="<?= BASE_URL ?>/modules/admin/system_health.php" class="flex items-center gap-3 px-3 py-2.5 rounded-lg text-gray-700 hover:bg-gray-100 transition">
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
    <div class="max-w-7xl mx-auto">
        <div class="flex items-center justify-between mb-6">
            <div>
                <h2 class="text-3xl font-bold text-gray-900">Activity Log</h2>
                <p class="text-gray-600 text-sm mt-1">Audit trail of all admin actions</p>
            </div>
            <a href="?export=1&from_date=<?= urlencode($fromDate) ?>&to_date=<?= urlencode($toDate) ?>" class="btn-secondary">📥 Export CSV</a>
        </div>
        
        <div class="bg-white rounded-lg shadow-sm border border-gray-200 p-4 mb-6">
            <form method="GET" class="space-y-4">
                <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
                    <div>
                        <label class="block text-sm font-semibold text-gray-700 mb-2">From Date</label>
                        <input type="date" name="from_date" value="<?= htmlspecialchars($fromDate) ?>" class="input-field w-full">
                    </div>
                    
                    <div>
                        <label class="block text-sm font-semibold text-gray-700 mb-2">To Date</label>
                        <input type="date" name="to_date" value="<?= htmlspecialchars($toDate) ?>" class="input-field w-full">
                    </div>
                    
                    <div>
                        <label class="block text-sm font-semibold text-gray-700 mb-2">Action Type</label>
                        <select name="action_type" class="input-field w-full">
                            <option value="">All Actions</option>
                            <?php foreach ($actionTypes as $type): ?>
                                <option value="<?= htmlspecialchars($type) ?>" <?= $actionType === $type ? 'selected' : '' ?>>
                                    <?= htmlspecialchars(ucwords(str_replace('_', ' ', $type))) ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    
                    <div>
                        <label class="block text-sm font-semibold text-gray-700 mb-2">Actor</label>
                        <select name="actor_id" class="input-field w-full">
                            <option value="">All Actors</option>
                            <?php foreach ($actors as $actor): ?>
                                <option value="<?= $actor['id'] ?>" <?= $actorId == $actor['id'] ? 'selected' : '' ?>>
                                    <?= htmlspecialchars($actor['name']) ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                </div>
                
                <button type="submit" class="btn-primary w-full md:w-auto">🔍 Filter</button>
            </form>
        </div>
        
        <div class="bg-white rounded-lg shadow-sm border border-gray-200 overflow-hidden">
            <div class="overflow-x-auto">
                <table class="w-full text-sm">
                    <thead>
                        <tr class="bg-gray-50 border-b border-gray-200">
                            <th class="text-left py-3 px-4 font-semibold text-gray-700">Timestamp</th>
                            <th class="text-left py-3 px-4 font-semibold text-gray-700">Actor</th>
                            <th class="text-left py-3 px-4 font-semibold text-gray-700">Action</th>
                            <th class="text-left py-3 px-4 font-semibold text-gray-700">Subject</th>
                            <th class="text-left py-3 px-4 font-semibold text-gray-700">IP Address</th>
                            <th class="text-center py-3 px-4 font-semibold text-gray-700">Details</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($activityData['entries'])): ?>
                            <tr>
                                <td colspan="6" class="py-6 text-center text-gray-500">No activity found</td>
                            </tr>
                        <?php else: ?>
                            <?php foreach ($activityData['entries'] as $entry): ?>
                            <tr class="border-b border-gray-100 hover:bg-gray-50">
                                <td class="py-3 px-4 text-gray-600 whitespace-nowrap">
                                    <div class="font-medium"><?= date('M d, Y', strtotime($entry['created_at'])) ?></div>
                                    <div class="text-xs text-gray-500"><?= date('H:i:s', strtotime($entry['created_at'])) ?></div>
                                </td>
                                <td class="py-3 px-4">
                                    <div class="font-medium text-gray-900"><?= htmlspecialchars($entry['actor_name'] ?? 'System') ?></div>
                                    <?php if ($entry['actor_role']): ?>
                                        <div class="text-xs text-gray-500"><?= htmlspecialchars(ucfirst($entry['actor_role'])) ?></div>
                                    <?php endif; ?>
                                </td>
                                <td class="py-3 px-4">
                                    <span class="px-2.5 py-1 rounded-full text-xs font-bold bg-teal-100 text-teal-700">
                                        <?= htmlspecialchars(ucwords(str_replace('_', ' ', $entry['action_type']))) ?>
                                    </span>
                                </td>
                                <td class="py-3 px-4 text-gray-600">
                                    <?php if ($entry['subject_type']): ?>
                                        <?= htmlspecialchars(ucfirst($entry['subject_type'])) ?> #<?= htmlspecialchars($entry['subject_id'] ?? '') ?>
                                    <?php else: ?>
                                        <span class="text-gray-400">-</span>
                                    <?php endif; ?>
                                </td>
                                <td class="py-3 px-4 font-mono text-xs text-gray-600">
                                    <?= htmlspecialchars($entry['ip_address'] ?? '-') ?>
                                </td>
                                <td class="py-3 px-4 text-center">
                                    <?php if ($entry['details']): ?>
                                        <button type="button" onclick="toggleDetails(this)" class="text-blue-600 hover:text-blue-700 font-medium">📋</button>
                                    <?php else: ?>
                                        <span class="text-gray-400">-</span>
                                    <?php endif; ?>
                                </td>
                            </tr>
                            
                            <?php if ($entry['details']): ?>
                            <tr class="bg-gray-50 hidden">
                                <td colspan="6" class="py-3 px-4">
                                    <p class="text-xs font-semibold text-gray-700 mb-2">Details:</p>
                                    <pre class="bg-gray-900 text-gray-100 p-3 rounded text-xs overflow-x-auto font-mono"><?= htmlspecialchars(json_encode(json_decode($entry['details'], true), JSON_PRETTY_PRINT)) ?></pre>
                                </td>
                            </tr>
                            <?php endif; ?>
                            
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
            
            <?php if ($activityData['pages'] > 1): ?>
            <div class="px-6 py-4 border-t border-gray-200 flex items-center justify-between">
                <p class="text-sm text-gray-600">
                    Page <?= $activityData['page'] ?> of <?= $activityData['pages'] ?> 
                    (<?= $activityData['total'] ?> total entries)
                </p>
                <div class="flex gap-2">
                    <?php if ($activityData['page'] > 1): ?>
                        <a href="?page=<?= $activityData['page'] - 1 ?>&from_date=<?= urlencode($fromDate) ?>&to_date=<?= urlencode($toDate) ?>" class="px-3 py-2 border border-gray-300 rounded text-sm font-medium text-gray-700 hover:bg-gray-50">← Previous</a>
                    <?php endif; ?>
                    
                    <?php if ($activityData['page'] < $activityData['pages']): ?>
                        <a href="?page=<?= $activityData['page'] + 1 ?>&from_date=<?= urlencode($fromDate) ?>&to_date=<?= urlencode($toDate) ?>" class="px-3 py-2 border border-gray-300 rounded text-sm font-medium text-gray-700 hover:bg-gray-50">Next →</a>
                    <?php endif; ?>
                </div>
            </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<script>
function toggleDetails(btn) {
    const row = btn.closest('tr');
    const nextRow = row.nextElementSibling;
    if (nextRow && nextRow.classList.contains('hidden')) {
        nextRow.classList.remove('hidden');
        btn.textContent = '📋 Hide';
    } else if (nextRow) {
        nextRow.classList.add('hidden');
        btn.textContent = '📋';
    }
}
</script>
    </div>
    </main>
</div>

<?php require_once '../../templates/footer.php'; ?>
