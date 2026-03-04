<?php
/**
 * modules/complaints/list.php
 * Manager views all complaints for their market with filtering and search
 */
require_once '../../config/auth_guard.php';
manager_only(); // Only managers can access this page
require_once '../../templates/header.php';
require_once '../../config/db.php';

// Get filter parameters
$filterStatus   = $_GET['status']   ?? '';
$filterCategory = $_GET['category'] ?? '';

// Build the SQL query dynamically based on active filters
$sql    = "SELECT c.*, u.name AS seller_name, u.stall_no
           FROM complaints c
           LEFT JOIN users u ON c.seller_id = u.id
           WHERE c.market_id = ?";
$params = [$_SESSION['market_id']]; // Always filter by the manager's market

// Append optional filters
if ($filterStatus)   { $sql .= " AND c.status = ?";   $params[] = $filterStatus; }
if ($filterCategory) { $sql .= " AND c.category = ?"; $params[] = $filterCategory; }

$sql .= " ORDER BY c.created_at DESC LIMIT 100";

$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$complaints = $stmt->fetchAll();

// Get summary statistics
$statsStmt = $pdo->prepare("SELECT 
    COUNT(*) as total,
    SUM(CASE WHEN status='pending' THEN 1 ELSE 0 END) as pending,
    SUM(CASE WHEN status='in_review' THEN 1 ELSE 0 END) as in_review,
    SUM(CASE WHEN status='resolved' THEN 1 ELSE 0 END) as resolved
    FROM complaints WHERE market_id = ?");
$statsStmt->execute([$_SESSION['market_id']]);
$stats = $statsStmt->fetch();

// Categories and statuses for filters
$categories = [
    'cat_infrastructure' => 'cat_infrastructure',
    'cat_sanitation' => 'cat_sanitation',
    'cat_stall_allocation' => 'cat_stall_allocation',
    'cat_security' => 'cat_security',
    'cat_other' => 'cat_other',
];

$statuses = [
    'pending' => 'status_pending',
    'in_review' => 'status_in_review',
    'resolved' => 'status_resolved',
];

// Status badge colors
$statusColors = [
    'pending'   => 'status-pending',
    'in_review' => 'status-in-review',
    'resolved'  => 'status-resolved',
];
?>

<div class="mb-10">
    <h1 class="text-3xl font-bold text-primary mb-6"><?= $t['manager_dashboard'] ?></h1>

    <!-- Summary Statistics -->
    <div class="grid grid-cols-2 md:grid-cols-4 gap-4 mb-8">
        <div class="card text-center">
            <div class="text-3xl font-bold text-primary"><?= $stats['total'] ?? 0 ?></div>
            <p class="text-gray-600 text-sm mt-2"><?= $t['total_complaints'] ?></p>
        </div>
        <div class="card text-center border-2 border-red-200">
            <div class="text-3xl font-bold text-red-600"><?= $stats['pending'] ?? 0 ?></div>
            <p class="text-gray-600 text-sm mt-2"><?= $t['status_pending'] ?></p>
        </div>
        <div class="card text-center border-2 border-yellow-200">
            <div class="text-3xl font-bold text-yellow-600"><?= $stats['in_review'] ?? 0 ?></div>
            <p class="text-gray-600 text-sm mt-2">In Review</p>
        </div>
        <div class="card text-center border-2 border-green-200">
            <div class="text-3xl font-bold text-green-600"><?= $stats['resolved'] ?? 0 ?></div>
            <p class="text-gray-600 text-sm mt-2"><?= $t['status_resolved'] ?></p>
        </div>
    </div>

    <!-- Filters -->
    <div class="card mb-6">
        <h2 class="font-bold text-gray-800 mb-4">🔍 <?= $t['filter'] ?></h2>
        <form method="GET" class="grid grid-cols-1 md:grid-cols-3 gap-4">
            <!-- Status Filter -->
            <div>
                <label for="status" class="block font-semibold text-gray-700 mb-2"><?= $t['filter_by_status'] ?></label>
                <select id="status" name="status" class="input-field">
                    <option value="">- <?= $t['all'] ?> -</option>
                    <?php foreach ($statuses as $key => $label): ?>
                        <option value="<?= $key ?>" <?= $filterStatus === $key ? 'selected' : '' ?>>
                            <?= $t[$label] ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>

            <!-- Category Filter -->
            <div>
                <label for="category" class="block font-semibold text-gray-700 mb-2"><?= $t['filter_by_category'] ?></label>
                <select id="category" name="category" class="input-field">
                    <option value="">- <?= $t['all_categories'] ?> -</option>
                    <?php foreach ($categories as $key => $label): ?>
                        <option value="<?= $key ?>" <?= $filterCategory === $key ? 'selected' : '' ?>>
                            <?= $t[$label] ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>

            <!-- Apply Button -->
            <div class="flex items-end">
                <button type="submit" class="w-full btn-primary">
                    <?= $t['filter'] ?>
                </button>
                <a href="list.php" class="ml-2 btn-outlined px-4 py-2">↺</a>
            </div>
        </form>
    </div>

    <!-- Complaints Table -->
    <div class="card overflow-hidden">
        <?php if (!empty($complaints)): ?>
            <div class="overflow-x-auto">
                <table class="w-full text-sm">
                    <thead class="bg-gray-100 border-b">
                        <tr>
                            <th class="px-4 py-3 text-left font-semibold">Ref Code</th>
                            <th class="px-4 py-3 text-left font-semibold"><?= $t['seller_name'] ?></th>
                            <th class="px-4 py-3 text-left font-semibold"><?= $t['complaint_category'] ?></th>
                            <th class="px-4 py-3 text-left font-semibold">Status</th>
                            <th class="px-4 py-3 text-left font-semibold"><?= $t['date'] ?></th>
                            <th class="px-4 py-3 text-left font-semibold"><?= $t['actions'] ?></th>
                        </tr>
                    </thead>
                    <tbody class="divide-y">
                        <?php foreach ($complaints as $complaint): ?>
                            <tr class="hover:bg-gray-50 transition <?= $complaint['status'] === 'pending' ? 'bg-red-50' : '' ?>">
                                <td class="px-4 py-3"><strong><?= htmlspecialchars($complaint['ref_code']) ?></strong></td>
                                <td class="px-4 py-3">
                                    <div><?= htmlspecialchars($complaint['seller_name'] ?? 'Unknown') ?></div>
                                    <div class="text-xs text-gray-500">Stall <?= htmlspecialchars($complaint['stall_no'] ?? 'N/A') ?></div>
                                </td>
                                <td class="px-4 py-3"><?= $t[$complaint['category']] ?? htmlspecialchars($complaint['category']) ?></td>
                                <td class="px-4 py-3">
                                    <span class="<?= $statusColors[$complaint['status']] ?? 'status-pending' ?>">
                                        <?= $t['status_' . $complaint['status']] ?>
                                    </span>
                                </td>
                                <td class="px-4 py-3 text-xs text-gray-600">
                                    <?= date('d/m/Y H:i', strtotime($complaint['created_at'])) ?>
                                </td>
                                <td class="px-4 py-3 space-x-2">
                                    <a href="respond.php?id=<?= $complaint['id'] ?>" class="text-primary font-semibold hover:underline inline-block">
                                        <?= $t['respond'] ?>
                                    </a>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php else: ?>
            <div class="text-center py-12">
                <div class="text-5xl mb-3">📭</div>
                <p class="text-gray-600 text-lg">No complaints found matching your filters.</p>
                <a href="list.php" class="text-primary font-semibold hover:underline mt-3 inline-block">Clear filters →</a>
            </div>
        <?php endif; ?>
    </div>
</div>

<?php require_once '../../templates/footer.php'; ?>
