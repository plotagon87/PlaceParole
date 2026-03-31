<?php
/**
 * modules/admin/response_analytics.php
 * 
 * Analytics dashboard for complaint response metrics
 * Track response times, SLA compliance, channel usage, manager performance
 * 
 * KEY METRICS:
 * - Average response time
 * - SLA compliance rate
 * - Channel distribution
 * - Manager workload/performance
 * - Complaint category distribution
 * - Trend data (daily/weekly/monthly)
 */

require_once '../../config/auth_guard.php';
// Allow both admin and manager roles to view
$_SESSION['role'] = $_SESSION['role'] ?? 'manager';
if (!in_array($_SESSION['role'], ['admin', 'manager'])) {
    die("Access denied");
}

$pageTitle = 'Complaint Response Analytics';
require_once '../../templates/header.php';
require_once '../../config/db.php';

// ─────────────────────────────────────────────────────────────────────
// Get Time Range
// ─────────────────────────────────────────────────────────────────────
$timerange = $_GET['range'] ?? '30days';
$date_from = '';
$range_label = '';

switch ($timerange) {
    case '7days':
        $date_from = date('Y-m-d H:i:s', strtotime('-7 days'));
        $range_label = 'Last 7 Days';
        break;
    case '30days':
        $date_from = date('Y-m-d H:i:s', strtotime('-30 days'));
        $range_label = 'Last 30 Days';
        break;
    case '90days':
        $date_from = date('Y-m-d H:i:s', strtotime('-90 days'));
        $range_label = 'Last 90 Days';
        break;
    case 'all':
        $date_from = '2000-01-01';
        $range_label = 'All Time';
        break;
    default:
        $timerange = '30days';
        $date_from = date('Y-m-d H:i:s', strtotime('-30 days'));
        $range_label = 'Last 30 Days';
}

// ─────────────────────────────────────────────────────────────────────
// Get Overall Statistics
// ─────────────────────────────────────────────────────────────────────
$stats_sql = "
SELECT 
    COUNT(*) as total_complaints,
    SUM(CASE WHEN status='resolved' THEN 1 ELSE 0 END) as resolved,
    SUM(CASE WHEN status='pending' THEN 1 ELSE 0 END) as pending,
    SUM(CASE WHEN status='in_review' THEN 1 ELSE 0 END) as in_review,
    AVG(response_time_secs) as avg_response_time,
    SUM(CASE WHEN response_time_secs IS NOT NULL THEN 1 ELSE 0 END) as responded_count,
    SUM(CASE WHEN sla_deadline < NOW() AND status != 'resolved' THEN 1 ELSE 0 END) as sla_breached
FROM complaints 
WHERE market_id = ? AND created_at >= ?
";

$stmt = $pdo->prepare($stats_sql);
$stmt->execute([$_SESSION['market_id'], $date_from]);
$stats = $stmt->fetch(PDO::FETCH_ASSOC);

// Calculate SLA compliance rate
$sla_compliant = $stats['resolved'] - ($stats['sla_breached'] ?? 0);
$sla_rate = $stats['total_complaints'] > 0 
    ? round(($stats['resolved'] - max(0, $stats['sla_breached'] ?? 0)) / $stats['total_complaints'] * 100) 
    : 0;

// ─────────────────────────────────────────────────────────────────────
// Channel Distribution
// ─────────────────────────────────────────────────────────────────────
$channel_sql = "
SELECT 
    c.channel,
    COUNT(*) as count,
    SUM(CASE WHEN c.status='resolved' THEN 1 ELSE 0 END) as resolved
FROM complaints c
WHERE c.market_id = ? AND c.created_at >= ?
GROUP BY c.channel
ORDER BY count DESC
";

$stmt = $pdo->prepare($channel_sql);
$stmt->execute([$_SESSION['market_id'], $date_from]);
$channels = $stmt->fetchAll(PDO::FETCH_ASSOC);

// ─────────────────────────────────────────────────────────────────────
// Category Distribution
// ─────────────────────────────────────────────────────────────────────
$category_sql = "
SELECT 
    c.category,
    COUNT(*) as count,
    AVG(c.response_time_secs) as avg_response_time,
    SUM(CASE WHEN c.status='resolved' THEN 1 ELSE 0 END) as resolved
FROM complaints c
WHERE c.market_id = ? AND c.created_at >= ?
GROUP BY c.category
ORDER BY count DESC
";

$stmt = $pdo->prepare($category_sql);
$stmt->execute([$_SESSION['market_id'], $date_from]);
$categories = $stmt->fetchAll(PDO::FETCH_ASSOC);

// ─────────────────────────────────────────────────────────────────────
// Manager Performance
// ─────────────────────────────────────────────────────────────────────
$manager_sql = "
SELECT 
    u.id,
    u.name,
    COUNT(c.id) as total,
    SUM(CASE WHEN c.status='resolved' THEN 1 ELSE 0 END) as resolved,
    AVG(c.response_time_secs) as avg_response_time,
    MIN(c.response_time_secs) as min_response_time,
    MAX(c.response_time_secs) as max_response_time
FROM users u
LEFT JOIN complaints c ON u.id = c.manager_id AND c.market_id = ? AND c.created_at >= ?
WHERE u.market_id = ? AND u.role = 'manager'
GROUP BY u.id
ORDER BY total DESC
";

$stmt = $pdo->prepare($manager_sql);
$stmt->execute([$_SESSION['market_id'], $date_from, $_SESSION['market_id']]);
$managers = $stmt->fetchAll(PDO::FETCH_ASSOC);

// ─────────────────────────────────────────────────────────────────────
// Daily Trend Data
// ─────────────────────────────────────────────────────────────────────
$trend_sql = "
SELECT 
    DATE(c.created_at) as date,
    COUNT(*) as submitted,
    SUM(CASE WHEN c.status='resolved' THEN 1 ELSE 0 END) as resolved,
    SUM(CASE WHEN c.status='pending' THEN 1 ELSE 0 END) as pending,
    AVG(c.response_time_secs) as avg_response
FROM complaints c
WHERE c.market_id = ? AND c.created_at >= ?
GROUP BY DATE(c.created_at)
ORDER BY date DESC
LIMIT 30
";

$stmt = $pdo->prepare($trend_sql);
$stmt->execute([$_SESSION['market_id'], $date_from]);
$trends = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Format response time display
function formatSeconds($seconds) {
    if (!$seconds) return 'N/A';
    $hours = floor($seconds / 3600);
    $mins = floor(($seconds % 3600) / 60);
    return $hours > 0 ? "{$hours}h {$mins}m" : "{$mins}m";
}

?>

<div class="container max-w-6xl mx-auto px-4 py-6">
    
    <div class="mb-8">
        <h1 class="text-3xl font-bold text-gray-800 mb-2">Complaint Response Analytics</h1>
        <p class="text-gray-600">Performance metrics and insights for <strong><?= $range_label ?></strong></p>
    </div>
    
    <!-- Time Range Selector -->
    <div class="mb-6 flex gap-2 flex-wrap">
        <a href="?range=7days" class="px-4 py-2 rounded-lg <?= $timerange === '7days' ? 'bg-primary text-white' : 'bg-gray-200 text-gray-800 hover:bg-gray-300' ?> transition">
            7 Days
        </a>
        <a href="?range=30days" class="px-4 py-2 rounded-lg <?= $timerange === '30days' ? 'bg-primary text-white' : 'bg-gray-200 text-gray-800 hover:bg-gray-300' ?> transition">
            30 Days
        </a>
        <a href="?range=90days" class="px-4 py-2 rounded-lg <?= $timerange === '90days' ? 'bg-primary text-white' : 'bg-gray-200 text-gray-800 hover:bg-gray-300' ?> transition">
            90 Days
        </a>
        <a href="?range=all" class="px-4 py-2 rounded-lg <?= $timerange === 'all' ? 'bg-primary text-white' : 'bg-gray-200 text-gray-800 hover:bg-gray-300' ?> transition">
            All Time
        </a>
    </div>
    
    <!-- Top Metrics Grid -->
    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4 mb-8">
        
        <!-- Total Complaints -->
        <div class="bg-white rounded-lg shadow-sm border border-gray-200 p-6">
            <div class="text-gray-600 text-sm font-semibold mb-2">Total Complaints</div>
            <div class="text-3xl font-bold text-gray-800"><?= $stats['total_complaints'] ?? 0 ?></div>
            <div class="text-xs text-gray-500 mt-2">
                ✓ <?= $stats['resolved'] ?? 0 ?> resolved
            </div>
        </div>
        
        <!-- Resolution Rate -->
        <div class="bg-white rounded-lg shadow-sm border border-gray-200 p-6">
            <div class="text-gray-600 text-sm font-semibold mb-2">Resolution Rate</div>
            <div class="text-3xl font-bold text-primary">
                <?= $stats['total_complaints'] > 0 
                    ? round(($stats['resolved'] ?? 0) / $stats['total_complaints'] * 100) 
                    : 0 ?>%
            </div>
            <div class="text-xs text-gray-500 mt-2">
                Resolved / Total
            </div>
        </div>
        
        <!-- SLA Compliance -->
        <div class="bg-white rounded-lg shadow-sm border border-gray-200 p-6">
            <div class="text-gray-600 text-sm font-semibold mb-2">SLA Compliance</div>
            <div class="text-3xl font-bold <?= $sla_rate >= 90 ? 'text-green-600' : ($sla_rate >= 70 ? 'text-yellow-600' : 'text-red-600') ?>">
                <?= $sla_rate ?>%
            </div>
            <div class="text-xs text-gray-500 mt-2">
                On-time resolution
            </div>
        </div>
        
        <!-- Avg Response Time -->
        <div class="bg-white rounded-lg shadow-sm border border-gray-200 p-6">
            <div class="text-gray-600 text-sm font-semibold mb-2">Avg Response Time</div>
            <div class="text-3xl font-bold text-gray-800">
                <?= formatSeconds($stats['avg_response_time'] ?? 0) ?>
            </div>
            <div class="text-xs text-gray-500 mt-2">
                For <?= $stats['responded_count'] ?? 0 ?> complaints
            </div>
        </div>
    </div>
    
    <!-- Channel & Category Analysis -->
    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6 mb-8">
        
        <!-- Channel Distribution -->
        <div class="bg-white rounded-lg shadow-sm border border-gray-200 p-6">
            <h3 class="text-lg font-bold text-gray-800 mb-4">Channel Distribution</h3>
            
            <div class="space-y-3">
                <?php foreach ($channels as $channel): ?>
                    <?php
                    $percentage = $stats['total_complaints'] > 0 
                        ? round($channel['count'] / $stats['total_complaints'] * 100) 
                        : 0;
                    $icons = ['web' => '🌐', 'sms' => '📱', 'email' => '📧', 'gmail' => '📬'];
                    $icon = $icons[$channel['channel']] ?? '📋';
                    ?>
                    
                    <div>
                        <div class="flex justify-between items-center mb-1">
                            <span class="font-semibold text-gray-700">
                                <?= $icon ?> <?= ucfirst($channel['channel']) ?>
                            </span>
                            <span class="text-sm text-gray-600">
                                <?= $channel['count'] ?> (<?= $percentage ?>%)
                            </span>
                        </div>
                        <div class="w-full bg-gray-200 rounded-full h-2">
                            <div class="bg-primary h-2 rounded-full" style="width: <?= $percentage ?>%"></div>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>
        
        <!-- Category Analysis -->
        <div class="bg-white rounded-lg shadow-sm border border-gray-200 p-6">
            <h3 class="text-lg font-bold text-gray-800 mb-4">By Category</h3>
            
            <div class="space-y-3">
                <?php foreach ($categories as $cat): ?>
                    <?php
                    $cat_percentage = $stats['total_complaints'] > 0 
                        ? round($cat['count'] / $stats['total_complaints'] * 100) 
                        : 0;
                    $resolution = $cat['count'] > 0 
                        ? round($cat['resolved'] / $cat['count'] * 100) 
                        : 0;
                    ?>
                    
                    <div>
                        <div class="flex justify-between items-center mb-1">
                            <span class="font-semibold text-gray-700">
                                <?= htmlspecialchars($cat['category'] ?? 'Other') ?>
                            </span>
                            <span class="text-sm text-gray-600">
                                <?= $cat['count'] ?> (<?= $cat_percentage ?>%)
                            </span>
                        </div>
                        <div class="flex gap-2 text-xs">
                            <div class="flex-1">
                                <div class="bg-gray-200 rounded-full h-2">
                                    <div class="bg-primary h-2 rounded-full" style="width: <?= $cat_percentage ?>%"></div>
                                </div>
                                <div class="text-gray-600 mt-1">Avg: <?= formatSeconds($cat['avg_response_time']) ?></div>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>
    </div>
    
    <!-- Manager Performance -->
    <div class="bg-white rounded-lg shadow-sm border border-gray-200 p-6 mb-8">
        <h3 class="text-lg font-bold text-gray-800 mb-4">Manager Performance</h3>
        
        <div class="overflow-x-auto">
            <table class="w-full text-sm">
                <thead>
                    <tr class="border-b border-gray-200">
                        <th class="text-left py-2 px-3 font-semibold text-gray-700">Manager</th>
                        <th class="text-center py-2 px-3 font-semibold text-gray-700">Total</th>
                        <th class="text-center py-2 px-3 font-semibold text-gray-700">Resolved</th>
                        <th class="text-center py-2 px-3 font-semibold text-gray-700">Rate</th>
                        <th class="text-right py-2 px-3 font-semibold text-gray-700">Avg Response</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($managers as $mgr): ?>
                        <?php
                        $mgr_rate = $mgr['total'] > 0 
                            ? round($mgr['resolved'] / $mgr['total'] * 100) 
                            : 0;
                        ?>
                        <tr class="border-b border-gray-100 hover:bg-gray-50">
                            <td class="py-3 px-3 font-semibold text-gray-800">
                                <?= htmlspecialchars($mgr['name']) ?>
                            </td>
                            <td class="py-3 px-3 text-center text-gray-600">
                                <?= $mgr['total'] ?? 0 ?>
                            </td>
                            <td class="py-3 px-3 text-center text-gray-600">
                                <?= $mgr['resolved'] ?? 0 ?>
                            </td>
                            <td class="py-3 px-3 text-center">
                                <span class="bg-primary text-white text-xs font-semibold px-2 py-1 rounded">
                                    <?= $mgr_rate ?>%
                                </span>
                            </td>
                            <td class="py-3 px-3 text-right text-gray-600">
                                <?= formatSeconds($mgr['avg_response_time']) ?>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
    
    <!-- Recent Trends (Last 30 Days) -->
    <div class="bg-white rounded-lg shadow-sm border border-gray-200 p-6">
        <h3 class="text-lg font-bold text-gray-800 mb-4">Recent Daily Trends</h3>
        
        <div class="overflow-x-auto">
            <table class="w-full text-sm">
                <thead>
                    <tr class="border-b border-gray-200">
                        <th class="text-left py-2 px-3 font-semibold text-gray-700">Date</th>
                        <th class="text-center py-2 px-3 font-semibold text-gray-700">Submitted</th>
                        <th class="text-center py-2 px-3 font-semibold text-gray-700">Resolved</th>
                        <th class="text-center py-2 px-3 font-semibold text-gray-700">Pending</th>
                        <th class="text-right py-2 px-3 font-semibold text-gray-700">Avg Response</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach (array_reverse($trends) as $trend): ?>
                        <tr class="border-b border-gray-100 hover:bg-gray-50">
                            <td class="py-3 px-3 font-semibold text-gray-800">
                                <?= date_format(date_create($trend['date']), 'd M Y') ?>
                            </td>
                            <td class="py-3 px-3 text-center text-gray-600">
                                <?= $trend['submitted'] ?? 0 ?>
                            </td>
                            <td class="py-3 px-3 text-center text-green-600 font-semibold">
                                ✓ <?= $trend['resolved'] ?? 0 ?>
                            </td>
                            <td class="py-3 px-3 text-center text-yellow-600">
                                ⏱ <?= $trend['pending'] ?? 0 ?>
                            </td>
                            <td class="py-3 px-3 text-right text-gray-600">
                                <?= formatSeconds($trend['avg_response']) ?>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
    
</div>

<style>
    .container { max-width: 1400px; margin: 0 auto; }
    .bg-primary { background-color: #16a34a; }
    .text-primary { color: #16a34a; }
    .grid { display: grid; }
</style>

<?php require_once '../../templates/footer.php'; ?>
