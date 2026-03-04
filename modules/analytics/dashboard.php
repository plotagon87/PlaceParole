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
    <h1 class="text-3xl font-bold text-primary mb-4">📊 <?= $t['analytics_dashboard'] ?></h1>

    <div class="grid grid-cols-1 md:grid-cols-3 gap-4 mb-6">
        <div class="card text-center">
            <div class="text-2xl font-bold"><?= array_sum($catData) ?></div>
            <p class="text-gray-600 text-sm mt-2"><?= $t['total_complaints'] ?></p>
        </div>
        <div class="card text-center">
            <div class="text-2xl font-bold"><?= $avgHours !== null ? $avgHours . ' ' . $t['hours'] : $t['no_resolved'] ?></div>
            <p class="text-gray-600 text-sm mt-2"><?= $t['avg_resolution_time'] ?></p>
        </div>
        <div class="card text-center">
            <div class="text-2xl font-bold"><?= $byMonthRaw ? array_sum($monthData) : 0 ?></div>
            <p class="text-gray-600 text-sm mt-2"><?= $t['complaints_last_12_months'] ?></p>
        </div>
    </div>

    <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mb-6">
        <div class="card">
            <h2 class="font-bold mb-4"><?= $t['by_category'] ?></h2>
            <canvas id="catChart" height="220"></canvas>
        </div>

        <div class="card">
            <h2 class="font-bold mb-4"><?= $t['by_month'] ?></h2>
            <canvas id="monthChart" height="220"></canvas>
        </div>
    </div>

    <div class="card">
        <h2 class="font-bold mb-4"><?= $t['recent_complaints'] ?></h2>
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
