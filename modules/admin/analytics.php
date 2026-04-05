<?php
/**
 * modules/admin/analytics.php
 * 
 * Platform-wide Admin Analytics Dashboard
 * Shows platform-wide metrics across all markets, distinct from manager dashboards
 * 
 * FEATURES:
 * - Platform KPIs (total markets, users, complaints, SLA compliance)
 * - Market performance ranking table with health badges
 * - Cross-market complaint distributions (status, channels, categories)
 * - Cross-market manager performance leaderboard
 * - 6-month growth trends (submitted vs resolved)
 * - Community engagement metrics (suggestions, announcements, reports)
 */

require_once '../../config/auth_guard.php';
admin_only();

$pageTitle = 'Platform Analytics';
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
// HELPER FUNCTION: Format Seconds to Hours/Minutes
// ─────────────────────────────────────────────────────────────────────
function formatSeconds($seconds) {
    if (!$seconds) return 'N/A';
    $hours = floor($seconds / 3600);
    $mins = floor(($seconds % 3600) / 60);
    return $hours > 0 ? "{$hours}h {$mins}m" : "{$mins}m";
}

// ─────────────────────────────────────────────────────────────────────
// 1. PLATFORM-WIDE KPIs (Total Markets, Users, Complaints, SLA)
// ─────────────────────────────────────────────────────────────────────
$kpi_sql = "
SELECT 
    COUNT(DISTINCT m.id) AS total_markets,
    (SELECT COUNT(*) FROM users WHERE role='seller' AND is_active=1) AS active_sellers,
    (SELECT COUNT(*) FROM users WHERE role='manager' AND is_active=1) AS active_managers,
    COUNT(DISTINCT c.id) AS total_complaints,
    SUM(CASE WHEN c.status='resolved' THEN 1 ELSE 0 END) AS resolved_count,
    SUM(CASE WHEN c.status IN ('pending', 'in_review') THEN 1 ELSE 0 END) AS open_count,
    ROUND(SUM(CASE WHEN c.status='resolved' THEN 1 ELSE 0 END) * 100.0 / NULLIF(COUNT(c.id), 0), 2) AS resolution_rate,
    SUM(CASE WHEN c.sla_deadline < NOW() AND c.status != 'resolved' THEN 1 ELSE 0 END) AS sla_breached,
    ROUND(SUM(CASE WHEN c.sla_deadline >= NOW() OR c.status='resolved' THEN 1 ELSE 0 END) * 100.0 / NULLIF(COUNT(c.id), 0), 2) AS sla_compliance_rate,
    ROUND(AVG(CASE WHEN c.response_time_secs IS NOT NULL THEN c.response_time_secs ELSE NULL END), 0) AS avg_response_secs
FROM markets m
LEFT JOIN complaints c ON m.id = c.market_id AND c.created_at >= ?
";

$stmt = $pdo->prepare($kpi_sql);
$stmt->execute([$date_from]);
$kpis = $stmt->fetch(PDO::FETCH_ASSOC);

// ─────────────────────────────────────────────────────────────────────
// 2. MARKET PERFORMANCE RANKING (All Markets with Metrics)
// ─────────────────────────────────────────────────────────────────────
$market_ranking_sql = "
SELECT 
    m.id,
    m.name,
    m.location,
    COUNT(DISTINCT c.id) AS total_complaints,
    SUM(CASE WHEN c.status='resolved' THEN 1 ELSE 0 END) AS resolved_complaints,
    SUM(CASE WHEN c.status IN ('pending', 'in_review') THEN 1 ELSE 0 END) AS open_complaints,
    ROUND(SUM(CASE WHEN c.status='resolved' THEN 1 ELSE 0 END) * 100.0 / NULLIF(COUNT(c.id), 0), 1) AS resolution_rate,
    ROUND(AVG(CASE WHEN c.response_time_secs IS NOT NULL THEN c.response_time_secs ELSE 0 END), 0) AS avg_response_secs,
    SUM(CASE WHEN c.sla_deadline < NOW() AND c.status != 'resolved' THEN 1 ELSE 0 END) AS sla_breached,
    ROUND(SUM(CASE WHEN c.sla_deadline >= NOW() OR c.status='resolved' THEN 1 ELSE 0 END) * 100.0 / NULLIF(COUNT(c.id), 0), 1) AS sla_compliance_rate
FROM markets m
LEFT JOIN complaints c ON m.id = c.market_id AND c.created_at >= ?
GROUP BY m.id, m.name, m.location
ORDER BY total_complaints DESC
";

$stmt = $pdo->prepare($market_ranking_sql);
$stmt->execute([$date_from]);
$markets = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Add health status badge logic
foreach ($markets as &$market) {
    $market['health_status'] = 'Healthy';
    $market['health_color'] = 'green';
    
    if ($market['total_complaints'] > 0) {
        if ($market['sla_compliance_rate'] < 70) {
            $market['health_status'] = 'Critical';
            $market['health_color'] = 'red';
        } elseif ($market['sla_compliance_rate'] < 85) {
            $market['health_status'] = 'At Risk';
            $market['health_color'] = 'yellow';
        }
    }
}

// ─────────────────────────────────────────────────────────────────────
// 3. CROSS-MARKET COMPLAINT DISTRIBUTIONS (Status, Channel, Category)
// ─────────────────────────────────────────────────────────────────────

// By Status
$status_dist_sql = "
SELECT 
    c.status,
    COUNT(*) AS count,
    ROUND(COUNT(*) * 100.0 / (SELECT COUNT(*) FROM complaints WHERE created_at >= ?), 2) AS percentage
FROM complaints c
WHERE c.created_at >= ?
GROUP BY c.status
ORDER BY count DESC
";

$stmt = $pdo->prepare($status_dist_sql);
$stmt->execute([$date_from, $date_from]);
$status_dist = $stmt->fetchAll(PDO::FETCH_ASSOC);

// By Channel
$channel_dist_sql = "
SELECT 
    c.channel,
    COUNT(*) AS count,
    ROUND(COUNT(*) * 100.0 / (SELECT COUNT(*) FROM complaints WHERE created_at >= ?), 2) AS percentage,
    SUM(CASE WHEN c.status='resolved' THEN 1 ELSE 0 END) AS resolved
FROM complaints c
WHERE c.created_at >= ?
GROUP BY c.channel
ORDER BY count DESC
";

$stmt = $pdo->prepare($channel_dist_sql);
$stmt->execute([$date_from, $date_from]);
$channel_dist = $stmt->fetchAll(PDO::FETCH_ASSOC);

// By Category (Top 10)
$category_dist_sql = "
SELECT 
    c.category,
    COUNT(*) AS count,
    COUNT(DISTINCT c.market_id) AS markets_affected,
    ROUND(COUNT(*) * 100.0 / (SELECT COUNT(*) FROM complaints WHERE created_at >= ?), 2) AS percentage,
    SUM(CASE WHEN c.status='resolved' THEN 1 ELSE 0 END) AS resolved,
    ROUND(AVG(CASE WHEN c.response_time_secs IS NOT NULL THEN c.response_time_secs ELSE 0 END), 0) AS avg_response_secs
FROM complaints c
WHERE c.created_at >= ?
GROUP BY c.category
ORDER BY count DESC
LIMIT 10
";

$stmt = $pdo->prepare($category_dist_sql);
$stmt->execute([$date_from, $date_from]);
$category_dist = $stmt->fetchAll(PDO::FETCH_ASSOC);

// ─────────────────────────────────────────────────────────────────────
// 4. CROSS-MARKET MANAGER PERFORMANCE (Leaderboard)
// ─────────────────────────────────────────────────────────────────────
$manager_perf_sql = "
SELECT 
    u.id,
    u.name,
    m.name AS market_name,
    COUNT(c.id) AS assigned_complaints,
    SUM(CASE WHEN c.status='resolved' THEN 1 ELSE 0 END) AS resolved_by_manager,
    ROUND(SUM(CASE WHEN c.status='resolved' THEN 1 ELSE 0 END) * 100.0 / NULLIF(COUNT(c.id), 0), 1) AS resolution_rate,
    ROUND(AVG(CASE WHEN c.response_time_secs IS NOT NULL THEN c.response_time_secs ELSE 0 END), 0) AS avg_response_secs,
    SUM(CASE WHEN c.sla_deadline < NOW() AND c.status != 'resolved' THEN 1 ELSE 0 END) AS sla_breaches
FROM users u
LEFT JOIN markets m ON u.market_id = m.id
LEFT JOIN complaints c ON u.id = c.manager_id AND c.created_at >= ?
WHERE u.role = 'manager' AND u.is_active = 1
GROUP BY u.id, u.name, m.id, m.name
HAVING assigned_complaints > 0
ORDER BY assigned_complaints DESC
LIMIT 15
";

$stmt = $pdo->prepare($manager_perf_sql);
$stmt->execute([$date_from]);
$managers = $stmt->fetchAll(PDO::FETCH_ASSOC);

// ─────────────────────────────────────────────────────────────────────
// 5. 6-MONTH GROWTH TRENDS (Submitted vs Resolved Platform-Wide)
// ─────────────────────────────────────────────────────────────────────
$growth_data = [];
for ($i = 5; $i >= 0; $i--) {
    $month_start = date('Y-m-01', strtotime("-$i months"));
    $month_end = date('Y-m-01', strtotime("-" . ($i - 1) . " months"));
    
    // Complaints submitted this month
    $stmt = $pdo->prepare("SELECT COUNT(*) as count FROM complaints WHERE created_at >= ? AND created_at < ?");
    $stmt->execute([$month_start, $month_end]);
    $submitted = $stmt->fetch(PDO::FETCH_ASSOC)['count'] ?? 0;
    
    // Complaints resolved this month
    $stmt = $pdo->prepare("SELECT COUNT(*) as count FROM complaints WHERE status='resolved' AND updated_at >= ? AND updated_at < ?");
    $stmt->execute([$month_start, $month_end]);
    $resolved = $stmt->fetch(PDO::FETCH_ASSOC)['count'] ?? 0;
    
    $growth_data[] = [
        'month' => date('M Y', strtotime($month_start)),
        'submitted' => (int)$submitted,
        'resolved' => (int)$resolved
    ];
}

// ─────────────────────────────────────────────────────────────────────
// 6. COMMUNITY ENGAGEMENT METRICS
// ─────────────────────────────────────────────────────────────────────
$community_sql = "
SELECT 
    (SELECT COUNT(*) FROM suggestions WHERE created_at >= ?) AS total_suggestions,
    (SELECT COUNT(*) FROM suggestions WHERE status='approved' AND created_at >= ?) AS approved_suggestions,
    (SELECT COUNT(*) FROM announcements WHERE deleted_at IS NULL AND created_at >= ?) AS active_announcements,
    (SELECT COUNT(*) FROM community_reports WHERE created_at >= ?) AS total_reports,
    (SELECT COUNT(*) FROM community_reports WHERE status='open' AND created_at >= ?) AS open_reports
";

$stmt = $pdo->prepare($community_sql);
$stmt->execute([$date_from, $date_from, $date_from, $date_from, $date_from]);
$community = $stmt->fetch(PDO::FETCH_ASSOC);

?>

<!-- SIDEBAR -->
<aside class="fixed left-0 top-0 w-60 h-screen bg-white border-r border-gray-200 flex flex-col shadow-sm z-40">
    <div class="px-6 py-4 border-b border-gray-100">
        <h1 class="text-xl font-bold text-green-700">📊 PlaceParole</h1>
        <p class="text-xs text-gray-500 mt-1">Platform Analytics</p>
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
        <a href="<?= BASE_URL ?>/modules/admin/system_health.php" class="flex items-center gap-3 px-3 py-2.5 rounded-lg text-gray-700 hover:bg-gray-100 transition">
            <span>⚙️</span> System Health
        </a>
        <a href="<?= BASE_URL ?>/modules/admin/analytics.php" class="flex items-center gap-3 px-3 py-2.5 rounded-lg bg-green-50 text-green-700 font-medium">
            <span>📈</span> Analytics
        </a>
        <hr class="my-3">
        <a href="<?= BASE_URL ?>/index.php" class="flex items-center gap-3 px-3 py-2.5 rounded-lg text-gray-600 hover:bg-gray-100 transition text-sm">
            <span>🏠</span> Back to Site
        </a>
    </nav>
    
    <div class="px-3 py-4 border-t border-gray-100">
        <div class="flex items-center gap-3">
            <div class="w-10 h-10 rounded-full bg-green-100 flex items-center justify-center text-sm font-bold text-green-700">
                <?= htmlspecialchars(substr($_SESSION['name'], 0, 2)) ?>
            </div>
            <div class="flex-1 min-w-0">
                <p class="text-sm font-medium text-gray-900 truncate"><?= htmlspecialchars($_SESSION['name']) ?></p>
                <p class="text-xs text-gray-500">Admin</p>
            </div>
        </div>
    </div>
</aside>

<!-- MAIN CONTENT -->
<main class="flex-1 ml-60 flex flex-col overflow-hidden">
    <div class="bg-white border-b border-gray-200 px-6 py-4 flex items-center justify-between sticky top-0 z-30">
        <div>
            <h1 class="text-2xl font-bold text-gray-900">Platform Analytics</h1>
            <p class="text-sm text-gray-600 mt-1">Cross-market insights for <?= $range_label ?></p>
        </div>
        <div class="text-right">
            <p class="text-xs text-gray-500">Platform-wide view</p>
        </div>
    </div>
    
    <div class="flex-1 overflow-y-auto px-6 py-6 space-y-6">
        
        <!-- Time Range Selector -->
        <div class="flex gap-2 flex-wrap">
            <a href="?range=7days" class="px-4 py-2 rounded-lg <?= $timerange === '7days' ? 'bg-primary text-white' : 'bg-gray-200 text-gray-800 hover:bg-gray-300' ?> transition text-sm font-medium">
                7 Days
            </a>
            <a href="?range=30days" class="px-4 py-2 rounded-lg <?= $timerange === '30days' ? 'bg-primary text-white' : 'bg-gray-200 text-gray-800 hover:bg-gray-300' ?> transition text-sm font-medium">
                30 Days
            </a>
            <a href="?range=90days" class="px-4 py-2 rounded-lg <?= $timerange === '90days' ? 'bg-primary text-white' : 'bg-gray-200 text-gray-800 hover:bg-gray-300' ?> transition text-sm font-medium">
                90 Days
            </a>
            <a href="?range=all" class="px-4 py-2 rounded-lg <?= $timerange === 'all' ? 'bg-primary text-white' : 'bg-gray-200 text-gray-800 hover:bg-gray-300' ?> transition text-sm font-medium">
                All Time
            </a>
        </div>
        
        <!-- ===== SECTION 1: PLATFORM KPIs (Top 4 Cards) ===== -->
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4">
            <!-- Total Markets -->
            <div class="bg-white rounded-lg shadow-sm border border-gray-200 p-6">
                <div class="text-gray-600 text-sm font-semibold mb-2">Total Markets</div>
                <div class="text-3xl font-bold text-gray-800"><?= $kpis['total_markets'] ?? 0 ?></div>
                <div class="text-xs text-gray-500 mt-2">Active across platform</div>
            </div>
            
            <!-- Total Users (Sellers + Managers) -->
            <div class="bg-white rounded-lg shadow-sm border border-gray-200 p-6">
                <div class="text-gray-600 text-sm font-semibold mb-2">Active Users</div>
                <div class="text-3xl font-bold text-gray-800">
                    <?= ($kpis['active_sellers'] ?? 0) + ($kpis['active_managers'] ?? 0) ?>
                </div>
                <div class="text-xs text-gray-500 mt-2">
                    <?= $kpis['active_sellers'] ?? 0 ?> sellers, <?= $kpis['active_managers'] ?? 0 ?> managers
                </div>
            </div>
            
            <!-- Platform Resolution Rate -->
            <div class="bg-white rounded-lg shadow-sm border border-gray-200 p-6">
                <div class="text-gray-600 text-sm font-semibold mb-2">Resolution Rate</div>
                <div class="text-3xl font-bold text-primary"><?= $kpis['resolution_rate'] ?? 0 ?>%</div>
                <div class="text-xs text-gray-500 mt-2">
                    <?= $kpis['resolved_count'] ?? 0 ?> / <?= $kpis['total_complaints'] ?? 0 ?> resolved
                </div>
            </div>
            
            <!-- SLA Compliance -->
            <div class="bg-white rounded-lg shadow-sm border border-gray-200 p-6">
                <div class="text-gray-600 text-sm font-semibold mb-2">SLA Compliance</div>
                <div class="text-3xl font-bold <?= ($kpis['sla_compliance_rate'] ?? 0) >= 90 ? 'text-green-600' : (($kpis['sla_compliance_rate'] ?? 0) >= 70 ? 'text-yellow-600' : 'text-red-600') ?>">
                    <?= $kpis['sla_compliance_rate'] ?? 0 ?>%
                </div>
                <div class="text-xs text-gray-500 mt-2">
                    <?= $kpis['sla_breached'] ?? 0 ?> breached
                </div>
            </div>
        </div>
        
        <!-- ===== SECTION 2: MARKET PERFORMANCE RANKING ===== -->
        <div class="bg-white rounded-lg shadow-sm border border-gray-200 p-6">
            <h2 class="text-lg font-bold text-gray-800 mb-4">Market Performance Ranking</h2>
            
            <div class="overflow-x-auto">
                <table class="w-full text-sm">
                    <thead>
                        <tr class="border-b border-gray-200">
                            <th class="text-left py-2 px-3 font-semibold text-gray-700">Market</th>
                            <th class="text-center py-2 px-3 font-semibold text-gray-700">Total</th>
                            <th class="text-center py-2 px-3 font-semibold text-gray-700">Resolved</th>
                            <th class="text-center py-2 px-3 font-semibold text-gray-700">Resolution %</th>
                            <th class="text-center py-2 px-3 font-semibold text-gray-700">SLA Compliance</th>
                            <th class="text-right py-2 px-3 font-semibold text-gray-700">Avg Response</th>
                            <th class="text-center py-2 px-3 font-semibold text-gray-700">Status</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($markets as $market): ?>
                            <tr class="border-b border-gray-100 hover:bg-gray-50">
                                <td class="py-3 px-3 font-semibold text-gray-800">
                                    <?= htmlspecialchars($market['name']) ?>
                                </td>
                                <td class="py-3 px-3 text-center text-gray-600">
                                    <?= $market['total_complaints'] ?? 0 ?>
                                </td>
                                <td class="py-3 px-3 text-center text-gray-600">
                                    <?= $market['resolved_complaints'] ?? 0 ?>
                                </td>
                                <td class="py-3 px-3 text-center">
                                    <span class="bg-primary text-white text-xs font-semibold px-2 py-1 rounded">
                                        <?= $market['resolution_rate'] ?? 0 ?>%
                                    </span>
                                </td>
                                <td class="py-3 px-3 text-center">
                                    <span class="text-xs font-semibold px-2 py-1 rounded <?= ($market['sla_compliance_rate'] ?? 0) >= 90 ? 'bg-green-100 text-green-700' : (($market['sla_compliance_rate'] ?? 0) >= 70 ? 'bg-yellow-100 text-yellow-700' : 'bg-red-100 text-red-700') ?>">
                                        <?= $market['sla_compliance_rate'] ?? 0 ?>%
                                    </span>
                                </td>
                                <td class="py-3 px-3 text-right text-gray-600">
                                    <?= formatSeconds($market['avg_response_secs']) ?>
                                </td>
                                <td class="py-3 px-3 text-center">
                                    <span class="text-xs font-bold px-2 py-1 rounded <?= $market['health_color'] === 'green' ? 'bg-green-100 text-green-700' : ($market['health_color'] === 'yellow' ? 'bg-yellow-100 text-yellow-700' : 'bg-red-100 text-red-700') ?>">
                                        <?= $market['health_status'] ?>
                                    </span>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
        
        <!-- ===== SECTION 3: CROSS-MARKET COMPLAINT DISTRIBUTIONS (3 Columns) ===== -->
        <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
            
            <!-- Status Distribution -->
            <div class="bg-white rounded-lg shadow-sm border border-gray-200 p-6">
                <h3 class="text-lg font-bold text-gray-800 mb-4">Complaints by Status</h3>
                
                <div class="space-y-3">
                    <?php foreach ($status_dist as $status): ?>
                        <?php
                        $status_colors = [
                            'pending' => ['bg' => 'bg-red-100', 'text' => 'text-red-700', 'label' => '⏱ Pending'],
                            'in_review' => ['bg' => 'bg-yellow-100', 'text' => 'text-yellow-700', 'label' => '🔄 In Review'],
                            'resolved' => ['bg' => 'bg-green-100', 'text' => 'text-green-700', 'label' => '✓ Resolved']
                        ];
                        $color = $status_colors[$status['status']] ?? ['bg' => 'bg-gray-100', 'text' => 'text-gray-700', 'label' => ucfirst($status['status'])];
                        ?>
                        
                        <div>
                            <div class="flex justify-between items-center mb-1">
                                <span class="font-semibold text-gray-700">
                                    <?= $color['label'] ?>
                                </span>
                                <span class="text-sm text-gray-600">
                                    <?= $status['count'] ?> (<?= $status['percentage'] ?>%)
                                </span>
                            </div>
                            <div class="w-full bg-gray-200 rounded-full h-2">
                                <div class="bg-primary h-2 rounded-full" style="width: <?= $status['percentage'] ?>%"></div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>
            
            <!-- Channel Distribution -->
            <div class="bg-white rounded-lg shadow-sm border border-gray-200 p-6">
                <h3 class="text-lg font-bold text-gray-800 mb-4">Complaints by Channel</h3>
                
                <div class="space-y-3">
                    <?php foreach ($channel_dist as $channel): ?>
                        <?php
                        $channel_icons = ['web' => '🌐', 'sms' => '📱', 'email' => '📧', 'gmail' => '📬'];
                        $icon = $channel_icons[$channel['channel']] ?? '📋';
                        $resolved_pct = $channel['count'] > 0 ? round(($channel['resolved'] / $channel['count']) * 100) : 0;
                        ?>
                        
                        <div>
                            <div class="flex justify-between items-center mb-1">
                                <span class="font-semibold text-gray-700">
                                    <?= $icon ?> <?= ucfirst($channel['channel']) ?>
                                </span>
                                <span class="text-sm text-gray-600">
                                    <?= $channel['count'] ?> (<?= $channel['percentage'] ?>%)
                                </span>
                            </div>
                            <div class="w-full bg-gray-200 rounded-full h-2">
                                <div class="bg-primary h-2 rounded-full" style="width: <?= $channel['percentage'] ?>%"></div>
                            </div>
                            <div class="text-xs text-gray-500 mt-1">
                                <?= $resolved_pct ?>% resolved
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>
            
            <!-- Community Metrics -->
            <div class="bg-white rounded-lg shadow-sm border border-gray-200 p-6">
                <h3 class="text-lg font-bold text-gray-800 mb-4">Community Engagement</h3>
                
                <div class="space-y-3">
                    <div>
                        <div class="flex justify-between items-center mb-1">
                            <span class="font-semibold text-gray-700">💡 Suggestions</span>
                            <span class="text-sm font-bold text-gray-800"><?= $community['total_suggestions'] ?? 0 ?></span>
                        </div>
                        <div class="text-xs text-gray-600">
                            <?= $community['approved_suggestions'] ?? 0 ?> approved
                        </div>
                    </div>
                    
                    <div class="border-t border-gray-200 pt-3">
                        <div class="flex justify-between items-center mb-1">
                            <span class="font-semibold text-gray-700">📢 Announcements</span>
                            <span class="text-sm font-bold text-gray-800"><?= $community['active_announcements'] ?? 0 ?></span>
                        </div>
                        <div class="text-xs text-gray-600">
                            Active platform-wide
                        </div>
                    </div>
                    
                    <div class="border-t border-gray-200 pt-3">
                        <div class="flex justify-between items-center mb-1">
                            <span class="font-semibold text-gray-700">🚨 Community Reports</span>
                            <span class="text-sm font-bold text-gray-800"><?= $community['total_reports'] ?? 0 ?></span>
                        </div>
                        <div class="text-xs text-gray-600">
                            <?= $community['open_reports'] ?? 0 ?> open cases
                        </div>
                    </div>
                </div>
            </div>
        </div>
        
        <!-- ===== SECTION 4: TOP COMPLAINT CATEGORIES ===== -->
        <div class="bg-white rounded-lg shadow-sm border border-gray-200 p-6">
            <h3 class="text-lg font-bold text-gray-800 mb-4">Top Complaint Categories (Platform-Wide)</h3>
            
            <div class="overflow-x-auto">
                <table class="w-full text-sm">
                    <thead>
                        <tr class="border-b border-gray-200">
                            <th class="text-left py-2 px-3 font-semibold text-gray-700">Category</th>
                            <th class="text-center py-2 px-3 font-semibold text-gray-700">Count</th>
                            <th class="text-center py-2 px-3 font-semibold text-gray-700">% of Total</th>
                            <th class="text-center py-2 px-3 font-semibold text-gray-700">Markets</th>
                            <th class="text-center py-2 px-3 font-semibold text-gray-700">Resolved</th>
                            <th class="text-right py-2 px-3 font-semibold text-gray-700">Avg Response</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($category_dist as $cat): ?>
                            <tr class="border-b border-gray-100 hover:bg-gray-50">
                                <td class="py-3 px-3 font-semibold text-gray-800">
                                    <?= htmlspecialchars($cat['category'] ?? 'Other') ?>
                                </td>
                                <td class="py-3 px-3 text-center text-gray-600">
                                    <?= $cat['count'] ?>
                                </td>
                                <td class="py-3 px-3 text-center text-gray-600">
                                    <?= $cat['percentage'] ?>%
                                </td>
                                <td class="py-3 px-3 text-center text-gray-600">
                                    <?= $cat['markets_affected'] ?>
                                </td>
                                <td class="py-3 px-3 text-center">
                                    <span class="text-green-600 font-semibold">
                                        ✓ <?= $cat['resolved'] ?>
                                    </span>
                                </td>
                                <td class="py-3 px-3 text-right text-gray-600">
                                    <?= formatSeconds($cat['avg_response_secs']) ?>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
        
        <!-- ===== SECTION 5: CROSS-MARKET MANAGER PERFORMANCE ===== -->
        <div class="bg-white rounded-lg shadow-sm border border-gray-200 p-6">
            <h3 class="text-lg font-bold text-gray-800 mb-4">Manager Performance Leaderboard (Cross-Market)</h3>
            
            <div class="overflow-x-auto">
                <table class="w-full text-sm">
                    <thead>
                        <tr class="border-b border-gray-200">
                            <th class="text-left py-2 px-3 font-semibold text-gray-700">Manager</th>
                            <th class="text-left py-2 px-3 font-semibold text-gray-700">Market</th>
                            <th class="text-center py-2 px-3 font-semibold text-gray-700">Assigned</th>
                            <th class="text-center py-2 px-3 font-semibold text-gray-700">Resolved</th>
                            <th class="text-center py-2 px-3 font-semibold text-gray-700">Rate</th>
                            <th class="text-right py-2 px-3 font-semibold text-gray-700">Avg Response</th>
                            <th class="text-center py-2 px-3 font-semibold text-gray-700">SLA Breaches</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($managers as $mgr): ?>
                            <tr class="border-b border-gray-100 hover:bg-gray-50">
                                <td class="py-3 px-3 font-semibold text-gray-800">
                                    <?= htmlspecialchars($mgr['name']) ?>
                                </td>
                                <td class="py-3 px-3 text-gray-600 text-sm">
                                    <?= htmlspecialchars($mgr['market_name']) ?>
                                </td>
                                <td class="py-3 px-3 text-center text-gray-600">
                                    <?= $mgr['assigned_complaints'] ?>
                                </td>
                                <td class="py-3 px-3 text-center text-green-600 font-semibold">
                                    <?= $mgr['resolved_by_manager'] ?>
                                </td>
                                <td class="py-3 px-3 text-center">
                                    <span class="bg-primary text-white text-xs font-semibold px-2 py-1 rounded">
                                        <?= $mgr['resolution_rate'] ?? 0 ?>%
                                    </span>
                                </td>
                                <td class="py-3 px-3 text-right text-gray-600">
                                    <?= formatSeconds($mgr['avg_response_secs']) ?>
                                </td>
                                <td class="py-3 px-3 text-center">
                                    <span class="<?= ($mgr['sla_breaches'] ?? 0) > 0 ? 'bg-red-100 text-red-700' : 'bg-green-100 text-green-700' ?> text-xs font-semibold px-2 py-1 rounded">
                                        <?= $mgr['sla_breaches'] ?? 0 ?>
                                    </span>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
        
        <!-- ===== SECTION 6: 6-MONTH GROWTH TRENDS ===== -->
        <div class="bg-white rounded-lg shadow-sm border border-gray-200 p-6">
            <h3 class="text-lg font-bold text-gray-800 mb-4">6-Month Growth Trends (Submitted vs Resolved)</h3>
            
            <div class="overflow-x-auto">
                <table class="w-full text-sm">
                    <thead>
                        <tr class="border-b border-gray-200">
                            <th class="text-left py-2 px-3 font-semibold text-gray-700">Month</th>
                            <th class="text-center py-2 px-3 font-semibold text-gray-700">Submitted</th>
                            <th class="text-center py-2 px-3 font-semibold text-gray-700">Resolved</th>
                            <th class="text-center py-2 px-3 font-semibold text-gray-700">Gap</th>
                            <th class="text-center py-2 px-3 font-semibold text-gray-700">Trend</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($growth_data as $growth): ?>
                            <?php $gap = $growth['submitted'] - $growth['resolved']; ?>
                            <tr class="border-b border-gray-100 hover:bg-gray-50">
                                <td class="py-3 px-3 font-semibold text-gray-800">
                                    <?= $growth['month'] ?>
                                </td>
                                <td class="py-3 px-3 text-center text-gray-600">
                                    <?= $growth['submitted'] ?>
                                </td>
                                <td class="py-3 px-3 text-center text-green-600 font-semibold">
                                    ✓ <?= $growth['resolved'] ?>
                                </td>
                                <td class="py-3 px-3 text-center <?= $gap > 0 ? 'text-orange-600' : 'text-gray-600' ?>">
                                    <?= $gap > 0 ? '+' . $gap : $gap ?>
                                </td>
                                <td class="py-3 px-3 text-center">
                                    <?php
                                    $resolution_rate = $growth['submitted'] > 0 ? round(($growth['resolved'] / $growth['submitted']) * 100) : 0;
                                    $badge_color = $resolution_rate >= 75 ? 'bg-green-100 text-green-700' : ($resolution_rate >= 50 ? 'bg-yellow-100 text-yellow-700' : 'bg-red-100 text-red-700');
                                    ?>
                                    <span class="<?= $badge_color ?> text-xs font-semibold px-2 py-1 rounded">
                                        <?= $resolution_rate ?>%
                                    </span>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
        
    </div>
</main>

<?php require_once '../../templates/footer.php'; ?>
