<?php
/**
 * modules/admin/overview.php
 * Super Admin overview — see all markets and their complaint statistics.
 * Only accessible to users with role = 'admin'.
 */
require_once '../../config/auth_guard.php';
admin_only(); // Restrict to admin users only

require_once '../../templates/header.php';
require_once '../../config/db.php';
require_once '../../config/market_validator.php'; // Market data validation

// Fetch all markets with their complaint counts, joined using SQL aggregate functions
// COUNT(*) = counts all rows in a group
// LEFT JOIN = include markets even if they have zero complaints
// GUARANTEED: All market data originates from database
$stmt = $pdo->query("
    SELECT
        m.id,
        m.name,
        m.location,
        m.created_at,
        COUNT(c.id)                                              AS total_complaints,
        SUM(CASE WHEN c.status = 'pending'   THEN 1 ELSE 0 END) AS pending,
        SUM(CASE WHEN c.status = 'resolved'  THEN 1 ELSE 0 END) AS resolved,
        COUNT(DISTINCT u.id)                                     AS total_users
    FROM markets m
    LEFT JOIN complaints c ON c.market_id = m.id
    LEFT JOIN users u      ON u.market_id = m.id AND u.role = 'seller'
    GROUP BY m.id
    ORDER BY total_complaints DESC
");
$markets = $stmt->fetchAll();

// Verify all markets originate from database (security verification)
foreach ($markets as $market) {
    MarketValidator::verifyDatabaseSource($market);
}

$totalMarkets    = count($markets);
$totalComplaints = array_sum(array_column($markets, 'total_complaints'));
$totalPending    = array_sum(array_column($markets, 'pending'));
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
                <span>📊</span> <?= $t['nav_dashboard'] ?>
            </a>
            <a href="<?= BASE_URL ?>/modules/admin/overview.php" class="flex items-center gap-3 px-3 py-2.5 rounded-lg bg-green-50 text-green-700 font-medium">
                <span>🌍</span> <?= $t['nav_overview'] ?>
            </a>
            <a href="<?= BASE_URL ?>/modules/admin/users.php" class="flex items-center gap-3 px-3 py-2.5 rounded-lg text-gray-700 hover:bg-gray-100 transition">
                <span>👥</span> <?= $t['nav_users'] ?>
            </a>
            <a href="<?= BASE_URL ?>/modules/admin/activity_log.php" class="flex items-center gap-3 px-3 py-2.5 rounded-lg text-gray-700 hover:bg-gray-100 transition">
                <span>📋</span> <?= $t['nav_activity_log'] ?>
            </a>
            <a href="<?= BASE_URL ?>/modules/admin/system_health.php" class="flex items-center gap-3 px-3 py-2.5 rounded-lg text-gray-700 hover:bg-gray-100 transition">
                <span>⚙️</span> <?= $t['nav_system_health'] ?>
            </a>
            <hr class="my-3">
            <a href="<?= BASE_URL ?>/index.php" class="flex items-center gap-3 px-3 py-2.5 rounded-lg text-gray-600 hover:bg-gray-100 transition text-sm">
                <span>🏠</span> <?= $t['nav_back_to_site'] ?>
            </a>
        </nav>
    </aside>

    <!-- MAIN CONTENT -->
    <main class="flex-1 ml-60 p-8">
        <div>
            <h1 class="text-3xl font-bold text-primary mb-6">🌍 Super Admin Overview</h1>

    <!-- Platform-wide summary statistics -->
    <div class="grid grid-cols-1 md:grid-cols-3 gap-4 mb-8">
        <div class="card text-center">
            <div class="text-3xl font-bold text-primary"><?= $totalMarkets ?></div>
            <p class="text-gray-600 text-sm mt-2">Total Markets</p>
        </div>
        <div class="card text-center">
            <div class="text-3xl font-bold text-primary"><?= $totalComplaints ?></div>
            <p class="text-gray-600 text-sm mt-2">Total Complaints (All Markets)</p>
        </div>
        <div class="card text-center border-2 border-red-200">
            <div class="text-3xl font-bold text-red-600"><?= $totalPending ?></div>
            <p class="text-gray-600 text-sm mt-2">Pending Complaints (All Markets)</p>
        </div>
    </div>

    <!-- Per-market table -->
    <div class="card overflow-x-auto">
        <h2 class="font-bold text-gray-800 mb-4">All Registered Markets</h2>
        <table class="w-full text-sm">
            <thead>
                <tr class="bg-gray-100">
                    <th class="px-4 py-3 text-left"><?= $t['market_name'] ?></th>
                    <th class="px-4 py-3 text-left"><?= $t['location'] ?></th>
                    <th class="px-4 py-3 text-center"><?= $t['sellers'] ?></th>
                    <th class="px-4 py-3 text-center"><?= $t['total_complaints'] ?></th>
                    <th class="px-4 py-3 text-center"><?= $t['pending'] ?></th>
                    <th class="px-4 py-3 text-center"><?= $t['resolved'] ?></th>
                    <th class="px-4 py-3 text-left"><?= $t['created'] ?></th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($markets as $m): ?>
                    <tr class="border-t hover:bg-gray-50">
                        <td class="px-4 py-3 font-semibold"><?= htmlspecialchars($m['name']) ?></td>
                        <td class="px-4 py-3 text-gray-600"><?= htmlspecialchars($m['location']) ?></td>
                        <td class="px-4 py-3 text-center"><?= $m['total_users'] ?? 0 ?></td>
                        <td class="px-4 py-3 text-center font-bold"><?= $m['total_complaints'] ?? 0 ?></td>
                        <td class="px-4 py-3 text-center">
                            <span class="status-pending"><?= $m['pending'] ?? 0 ?></span>
                        </td>
                        <td class="px-4 py-3 text-center">
                            <span class="status-resolved"><?= $m['resolved'] ?? 0 ?></span>
                        </td>
                        <td class="px-4 py-3 text-gray-500 text-xs">
                            <?= date('d/m/Y', strtotime($m['created_at'])) ?>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
        </div>
    </main>
</div>

<?php require_once '../../templates/footer.php'; ?>
