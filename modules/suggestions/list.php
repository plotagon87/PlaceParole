<?php
/**
 * modules/suggestions/list.php
 * Managers view and manage seller suggestions
 */
require_once '../../config/auth_guard.php';
manager_only();
require_once '../../templates/header.php';
require_once '../../config/db.php';

$filterStatus = $_GET['status'] ?? '';

$sql = "SELECT s.*, u.name AS seller_name FROM suggestions s LEFT JOIN users u ON s.seller_id = u.id WHERE s.market_id = ?";
$params = [$_SESSION['market_id']];

if ($filterStatus) {
    $sql .= " AND s.status = ?";
    $params[] = $filterStatus;
}

$sql .= " ORDER BY s.created_at DESC";

$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$suggestions = $stmt->fetchAll();

$statusColors = [
    'pending'  => 'bg-yellow-100 text-yellow-700',
    'approved' => 'bg-green-100 text-green-700',
    'rejected' => 'bg-red-100 text-red-700',
];
?>

<div>
    <h1 class="text-3xl font-bold text-primary mb-6">💡 <?= $t['nav_suggestions'] ?></h1>

    <!-- Filters -->
    <div class="card mb-6">
        <form method="GET" class="flex gap-3">
            <select name="status" class="input-field flex-1">
                <option value="">All Statuses</option>
                <option value="pending" <?= $filterStatus === 'pending' ? 'selected' : '' ?>>Pending</option>
                <option value="approved" <?= $filterStatus === 'approved' ? 'selected' : '' ?>>Approved</option>
                <option value="rejected" <?= $filterStatus === 'rejected' ? 'selected' : '' ?>>Rejected</option>
            </select>
            <button type="submit" class="btn-primary">Filter</button>
            <a href="list.php" class="btn-outlined px-4">↺</a>
        </form>
    </div>

    <!-- Suggestions List -->
    <?php if (!empty($suggestions)): ?>
        <div class="space-y-4">
            <?php foreach ($suggestions as $sug): ?>
                <div class="card">
                    <div class="flex justify-between items-start mb-2">
                        <h2 class="text-xl font-bold text-primary flex-1"><?= htmlspecialchars($sug['title']) ?></h2>
                        <span class="<?= $statusColors[$sug['status']] ?? 'bg-gray-100 text-gray-700' ?> px-3 py-1 rounded-full text-xs font-bold">
                            <?= ucfirst($sug['status']) ?>
                        </span>
                    </div>
                    <p class="text-gray-700 mb-3"><?= nl2br(htmlspecialchars($sug['description'])) ?></p>
                    <div class="text-sm text-gray-600">
                        <span>From: <strong><?= htmlspecialchars($sug['seller_name'] ?? 'Unknown') ?></strong> — <?= date('d/m/Y', strtotime($sug['created_at'])) ?></span>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    <?php else: ?>
        <div class="card text-center py-12">
            <div class="text-5xl mb-3">📭</div>
            <p class="text-gray-600">No suggestions found.</p>
        </div>
    <?php endif; ?>
</div>

<?php require_once '../../templates/footer.php'; ?>
