<?php
/**
 * modules/analytics/dashboard.php
 * Manager analytics dashboard: complaints by category, by month, avg resolution time
 */
require_once '../../config/auth_guard.php';
manager_only();
require_once '../../templates/header.php';
require_once '../../config/db.php';

$market_id = $_SESSION['market_id'];

// 1) Complaints by category
$catStmt = $pdo->prepare("SELECT category, COUNT(*) AS total FROM complaints WHERE market_id = ? GROUP BY category");
$catStmt->execute([$market_id]);
$byCategory = $catStmt->fetchAll();

// 2) Complaints by month (last 12 months)
$monthStmt = $pdo->prepare("SELECT DATE_FORMAT(created_at, '%Y-%m') AS month, COUNT(*) AS total
    FROM complaints
    WHERE market_id = ? AND created_at >= DATE_SUB(CURDATE(), INTERVAL 12 MONTH)
    GROUP BY month
    ORDER BY month ASC");
$monthStmt->execute([$market_id]);
$byMonthRaw = $monthStmt->fetchAll();

// Build a months array for the last 12 months and merge counts
$months = [];
for ($i = 11; $i >= 0; $i--) {
    $m = date('Y-m', strtotime("-{$i} months"));
    $months[$m] = 0;
}
foreach ($byMonthRaw as $r) {
    if (isset($months[$r['month']])) $months[$r['month']] = (int) $r['total'];
}

// 3) Average resolution time in hours for resolved complaints
$avgStmt = $pdo->prepare("SELECT AVG(TIMESTAMPDIFF(HOUR, created_at, updated_at)) AS avg_hours FROM complaints WHERE market_id = ? AND status = 'resolved'");
$avgStmt->execute([$market_id]);
$avg = $avgStmt->fetch();
$avgHours = $avg['avg_hours'] ? round($avg['avg_hours'], 1) : null;

// 4) Recent complaints (latest 10)
$recentStmt = $pdo->prepare("SELECT c.*, u.name AS seller_name FROM complaints c LEFT JOIN users u ON c.seller_id = u.id WHERE c.market_id = ? ORDER BY c.created_at DESC LIMIT 10");
$recentStmt->execute([$market_id]);
$recent = $recentStmt->fetchAll();

// Prepare JSON for charts
$catLabels = array_map(function($r){ return htmlspecialchars($r['category']); }, $byCategory);
$catData = array_map(function($r){ return (int)$r['total']; }, $byCategory);
$monthLabels = array_keys($months);
$monthData = array_values($months);

?>
<div class="mb-8">
    <h1 class="text-3xl font-bold text-primary mb-4">📊 <?= $t['analytics_dashboard'] ?? 'Analytics Dashboard' ?></h1>

    <div class="grid grid-cols-1 md:grid-cols-3 gap-4 mb-6">
        <div class="card text-center">
            <div class="text-2xl font-bold"><?= array_sum($catData) ?></div>
            <p class="text-gray-600 text-sm mt-2"><?= $t['total_complaints'] ?? 'Total complaints' ?></p>
        </div>
        <div class="card text-center">
            <div class="text-2xl font-bold"><?= $avgHours !== null ? $avgHours . ' ' . ($t['hours'] ?? 'hrs') : ($t['no_resolved'] ?? 'No resolved yet') ?></div>
            <p class="text-gray-600 text-sm mt-2"><?= $t['avg_resolution_time'] ?? 'Avg resolution time' ?></p>
        </div>
        <div class="card text-center">
            <div class="text-2xl font-bold"><?= $byMonthRaw ? array_sum($monthData) : 0 ?></div>
            <p class="text-gray-600 text-sm mt-2"><?= $t['complaints_last_12_months'] ?? 'Last 12 months' ?></p>
        </div>
    </div>

    <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mb-6">
        <div class="card">
            <h2 class="font-bold mb-4"><?= $t['by_category'] ?? 'Complaints by Category' ?></h2>
            <canvas id="catChart" height="220"></canvas>
        </div>

        <div class="card">
            <h2 class="font-bold mb-4"><?= $t['by_month'] ?? 'Complaints by Month' ?></h2>
            <canvas id="monthChart" height="220"></canvas>
        </div>
    </div>

    <div class="card">
        <h2 class="font-bold mb-4"><?= $t['recent_complaints'] ?? 'Recent Complaints' ?></h2>
        <?php if (!empty($recent)): ?>
            <div class="overflow-x-auto">
                <table class="w-full text-left text-sm">
                    <thead>
                        <tr class="text-gray-600">
                            <th class="px-3 py-2">Ref</th>
                            <th class="px-3 py-2">Category</th>
                            <th class="px-3 py-2">Seller</th>
                            <th class="px-3 py-2">Status</th>
                            <th class="px-3 py-2">Submitted</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($recent as $r): ?>
                            <tr class="border-t">
                                <td class="px-3 py-2"><?= htmlspecialchars($r['ref_code']) ?></td>
                                <td class="px-3 py-2"><?= htmlspecialchars($r['category']) ?></td>
                                <td class="px-3 py-2"><?= htmlspecialchars($r['seller_name'] ?? '') ?></td>
                                <td class="px-3 py-2"><?= htmlspecialchars($r['status']) ?></td>
                                <td class="px-3 py-2"><?= date('d/m/Y', strtotime($r['created_at'])) ?></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php else: ?>
            <p class="text-gray-600">No recent complaints.</p>
        <?php endif; ?>
    </div>

</div>

<!-- Chart.js CDN -->
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
    const catLabels = <?= json_encode($catLabels) ?>;
    const catData = <?= json_encode($catData) ?>;
    const monthLabels = <?= json_encode($monthLabels) ?>;
    const monthData = <?= json_encode(array_values($monthData)) ?>;

    // Category pie chart
    new Chart(document.getElementById('catChart'), {
        type: 'pie',
        data: {
            labels: catLabels,
            datasets: [{ data: catData, backgroundColor: ['#60A5FA','#FBBF24','#34D399','#F87171','#C084FC'] }]
        },
        options: { plugins: { legend: { position: 'bottom' } } }
    });

    // Month bar chart
    new Chart(document.getElementById('monthChart'), {
        type: 'bar',
        data: {
            labels: monthLabels,
            datasets: [{ label: 'Complaints', data: monthData, backgroundColor: '#60A5FA' }]
        },
        options: { scales: { y: { beginAtZero: true } }, plugins: { legend: { display: false } } }
    });
</script>

<?php require_once '../../templates/footer.php'; ?>
<?php
/**
 * modules/analytics/dashboard.php
 * Manager views analytics for their market
 * Shows charts and statistics about complaints
 */
require_once '../../config/auth_guard.php';
manager_only();
require_once '../../templates/header.php';
require_once '../../config/db.php';

// Fetch complaint statistics
$statsStmt = $pdo->prepare("SELECT 
    COUNT(*) as total,
    SUM(CASE WHEN status='pending' THEN 1 ELSE 0 END) as pending,
    SUM(CASE WHEN status='in_review' THEN 1 ELSE 0 END) as in_review,
    SUM(CASE WHEN status='resolved' THEN 1 ELSE 0 END) as resolved,
    SUM(CASE WHEN status='resolved' THEN 1 ELSE 0 END) * 100 / COUNT(*) as resolution_rate
    FROM complaints WHERE market_id = ?");
$statsStmt->execute([$_SESSION['market_id']]);
$stats = $statsStmt->fetch();

// Complaints by category
$categoryStmt = $pdo->prepare("SELECT category, COUNT(*) as count FROM complaints WHERE market_id = ? GROUP BY category ORDER BY count DESC");
$categoryStmt->execute([$_SESSION['market_id']]);
$byCategory = $categoryStmt->fetchAll();

// Complaints by month (last 6 months)
$monthlyStmt = $pdo->prepare("SELECT DATE_TRUNC('month', created_at) as month, COUNT(*) as count FROM complaints WHERE market_id = ? AND created_at > DATE_SUB(NOW(), INTERVAL 6 MONTH) GROUP BY DATE_TRUNC('month', created_at) ORDER BY month");
try {
    $monthlyStmt->execute([$_SESSION['market_id']]);
    $byMonth = $monthlyStmt->fetchAll();
} catch (Exception $e) {
    // SQLite/MySQL compatibility - use DATE_FORMAT instead
    $monthlyStmt = $pdo->prepare("SELECT DATE_FORMAT(created_at, '%Y-%m-01') as month, COUNT(*) as count FROM complaints WHERE market_id = ? AND created_at > DATE_SUB(NOW(), INTERVAL 6 MONTH) GROUP BY DATE_FORMAT(created_at, '%Y-%m-01') ORDER BY month");
    $monthlyStmt->execute([$_SESSION['market_id']]);
    $byMonth = $monthlyStmt->fetchAll();
}

// Average resolution time
$resolutionStmt = $pdo->prepare("SELECT AVG(TIMESTAMPDIFF(DAY, created_at, updated_at)) as avg_days FROM complaints WHERE market_id = ? AND status = 'resolved'");
$resolutionStmt->execute([$_SESSION['market_id']]);
$avgResolution = $resolutionStmt->fetch();
?>

<div>
    <h1 class="text-3xl font-bold text-primary mb-6">📊 Analytics Dashboard</h1>

    <!-- Statistics Cards -->
    <div class="grid grid-cols-2 md:grid-cols-4 gap-4 mb-8">
        <div class="card text-center">
            <div class="text-3xl font-bold text-primary"><?= $stats['total'] ?? 0 ?></div>
            <p class="text-gray-600 text-sm mt-2">Total Complaints</p>
        </div>
        <div class="card text-center border-2 border-red-200">
            <div class="text-3xl font-bold text-red-600"><?= $stats['pending'] ?? 0 ?></div>
            <p class="text-gray-600 text-sm mt-2">Pending</p>
        </div>
        <div class="card text-center border-2 border-green-200">
            <div class="text-3xl font-bold text-green-600"><?= round($stats['resolution_rate'] ?? 0) ?>%</div>
            <p class="text-gray-600 text-sm mt-2">Resolved</p>
        </div>
        <div class="card text-center border-2 border-blue-200">
            <div class="text-3xl font-bold text-blue-600"><?= round($avgResolution['avg_days'] ?? 0) ?> days</div>
            <p class="text-gray-600 text-sm mt-2">Avg Resolution</p>
        </div>
    </div>

    <!-- Charts Section -->
    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6 mb-8">
        <!-- Complaints by Category -->
        <div class="card">
            <h2 class="font-bold text-lg text-primary mb-4">📋 By Category</h2>
            <div class="space-y-2">
                <?php foreach ($byCategory as $cat): ?>
                    <div>
                        <div class="flex justify-between text-sm mb-1">
                            <span><?= $cat['category'] ?></span>
                            <span class="font-bold"><?= $cat['count'] ?></span>
                        </div>
                        <div class="w-full bg-gray-200 rounded-full h-2">
                            <div class="bg-primary h-2 rounded-full" style="width: <?= ($cat['count'] / max(1, $stats['total'])) * 100 ?>%"></div>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>

        <!-- Status Breakdown -->
        <div class="card">
            <h2 class="font-bold text-lg text-primary mb-4">🎯 Status Breakdown</h2>
            <div class="space-y-3">
                <div>
                    <div class="flex justify-between text-sm mb-1">
                        <span>🔴 Pending</span>
                        <span class="font-bold"><?= $stats['pending'] ?? 0 ?></span>
                    </div>
                    <div class="w-full bg-red-100 rounded-full h-3" style="background: linear-gradient(90deg, #dc2626 0%, #fee2e2 100%); width: 100%;"></div>
                </div>
                <div>
                    <div class="flex justify-between text-sm mb-1">
                        <span>🟡 In Review</span>
                        <span class="font-bold"><?= $stats['in_review'] ?? 0 ?></span>
                    </div>
                    <div class="w-full bg-yellow-100 rounded-full h-3" style="background: linear-gradient(90deg, #eab308 0%, #fef08a 100%);"></div>
                </div>
                <div>
                    <div class="flex justify-between text-sm mb-1">
                        <span>🟢 Resolved</span>
                        <span class="font-bold"><?= $stats['resolved'] ?? 0 ?></span>
                    </div>
                    <div class="w-full bg-green-100 rounded-full h-3" style="background: linear-gradient(90deg, #22863a 0%, #dcfce7 100%);"></div>
                </div>
            </div>
        </div>
    </div>

    <!-- Action Cards -->
    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
        <a href="../complaints/list.php" class="card hover:shadow-lg transition cursor-pointer bg-gradient-to-br from-blue-50 to-blue-100 border-2 border-blue-300">
            <div class="text-3xl mb-2">📝</div>
            <h3 class="font-bold text-primary">View All Complaints</h3>
            <p class="text-sm text-gray-600">Manage and respond to complaints</p>
        </a>

        <a href="../announcements/create.php" class="card hover:shadow-lg transition cursor-pointer bg-gradient-to-br from-green-50 to-green-100 border-2 border-green-300">
            <div class="text-3xl mb-2">📢</div>
            <h3 class="font-bold text-primary">Broadcast Announcement</h3>
            <p class="text-sm text-gray-600">Send updates to all sellers</p>
        </a>
    </div>
</div>

<?php require_once '../../templates/footer.php'; ?>
