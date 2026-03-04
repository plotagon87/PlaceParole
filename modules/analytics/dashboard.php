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
