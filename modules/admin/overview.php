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

// Fetch all markets with their complaint counts, joined using SQL aggregate functions
// COUNT(*) = counts all rows in a group
// LEFT JOIN = include markets even if they have zero complaints
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

$totalMarkets    = count($markets);
$totalComplaints = array_sum(array_column($markets, 'total_complaints'));
$totalPending    = array_sum(array_column($markets, 'pending'));
?>

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
                    <th class="px-4 py-3 text-left">Market Name</th>
                    <th class="px-4 py-3 text-left">Location</th>
                    <th class="px-4 py-3 text-center">Sellers</th>
                    <th class="px-4 py-3 text-center">Total Complaints</th>
                    <th class="px-4 py-3 text-center">Pending</th>
                    <th class="px-4 py-3 text-center">Resolved</th>
                    <th class="px-4 py-3 text-left">Created</th>
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

<?php require_once '../../templates/footer.php'; ?>
