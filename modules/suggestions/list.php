<?php
/**
 * modules/suggestions/list.php
 * All users view approved suggestions from their market
 * Managers/Admins can see status filters and moderation options
 */
require_once '../../config/auth_guard.php';

require_once '../../templates/header.php';
require_once '../../config/db.php';

$pageHasForm = true;
$is_manager = in_array($_SESSION['role'], ['manager', 'admin']);
$filter_status = $_GET['status'] ?? 'approved';

// If non-managers filter, force to approved-only
if (!$is_manager) {
    $filter_status = 'approved';
}

// Fetch suggestions based on user role
$sql = "
    SELECT 
        s.*,
        u.name AS seller_name
    FROM suggestions s
    JOIN users u ON s.seller_id = u.id
    WHERE s.market_id = ? 
    AND s.deleted_at IS NULL
";
$params = [$_SESSION['market_id']];

// Non-managers only see approved suggestions
if (!$is_manager) {
    $sql .= " AND s.status = 'approved'";
} else {
    // Managers can filter by status
    if ($filter_status && in_array($filter_status, ['pending', 'approved', 'rejected'])) {
        $sql .= " AND s.status = ?";
        $params[] = $filter_status;
    }
}

$sql .= " ORDER BY s.created_at DESC LIMIT 100";

$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$suggestions = $stmt->fetchAll(PDO::FETCH_ASSOC);

$statusColors = [
    'pending'  => 'bg-yellow-100 text-yellow-700',
    'approved' => 'bg-green-100 text-green-700',
    'rejected' => 'bg-red-100 text-red-700',
];
?>

<div class="container mx-auto px-4 py-8 max-w-4xl">
    <div class="mb-6">
        <h1 class="text-3xl font-bold text-primary mb-2">💡 <?= $t['nav_suggestions'] ?></h1>
        <p class="text-gray-600">Market improvement ideas and suggestions</p>
    </div>

    <!-- Manager-Only Filters -->
    <?php if ($is_manager): ?>
        <div class="bg-white rounded-lg shadow p-4 mb-6 border border-blue-200">
            <form method="GET" class="flex gap-3 flex-wrap">
                <div class="flex-1 min-w-[200px]">
                    <select name="status" class="input-field w-full">
                        <option value="approved" <?= $filter_status === 'approved' ? 'selected' : '' ?>>Approved Only</option>
                        <option value="pending" <?= $filter_status === 'pending' ? 'selected' : '' ?>>Pending (Need Review)</option>
                        <option value="rejected" <?= $filter_status === 'rejected' ? 'selected' : '' ?>>Rejected</option>
                    </select>
                </div>
                <button type="submit" class="btn-primary px-6">Filter</button>
                <a href="list.php" class="btn-outlined px-6">Reset</a>
            </form>
            <div class="mt-3 flex gap-3">
                <a href="../admin/pending_suggestions.php" class="text-blue-600 hover:text-blue-800 font-semibold">
                    → Pending Suggestions Moderation
                </a>
            </div>
        </div>
    <?php endif; ?>

    <!-- Suggestions List -->
    <?php if (!empty($suggestions)): ?>
        <div class="space-y-4">
            <?php foreach ($suggestions as $sug): ?>
                <div class="bg-white rounded-lg shadow p-6 hover:shadow-lg transition">
                    <div class="flex justify-between items-start gap-4 mb-3">
                        <div class="flex-1">
                            <h3 class="text-xl font-bold text-gray-900 mb-1"><?= htmlspecialchars($sug['title']) ?></h3>
                            <p class="text-sm text-gray-600">
                                By: <strong><?= htmlspecialchars($sug['seller_name'] ?? 'Unknown') ?></strong> 
                                · <?= date('M d, Y', strtotime($sug['created_at'])) ?>
                            </p>
                        </div>
                        <?php if ($is_manager): ?>
                            <span class="px-3 py-1 rounded-full text-sm font-semibold <?php
                                echo $sug['status'] === 'approved' ? 'bg-green-100 text-green-800' : 
                                     ($sug['status'] === 'pending' ? 'bg-yellow-100 text-yellow-800' : 'bg-red-100 text-red-800');
                            ?>">
                                <?= ucfirst($sug['status']) ?>
                            </span>
                        <?php endif; ?>
                    </div>

                    <p class="text-gray-700 leading-relaxed mb-4">
                        <?= nl2br(htmlspecialchars($sug['description'])) ?>
                    </p>

                    <?php if ($sug['seller_id'] == $_SESSION['user_id']): ?>
                        <p class="text-xs text-blue-600 bg-blue-50 px-3 py-2 rounded inline-block">
                            ✏️ Your suggestion
                        </p>
                    <?php endif; ?>
                </div>
            <?php endforeach; ?>
        </div>
    <?php else: ?>
        <div class="bg-gray-50 rounded-lg text-center py-16">
            <div class="text-6xl mb-4">💭</div>
            <p class="text-gray-600 text-lg mb-2"><?= $t['no_announcements'] ?></p>
            <p class="text-gray-500"><?php 
                if ($is_manager && $filter_status === 'pending') {
                    echo 'All suggestions have been reviewed!';
                } else {
                    echo 'No suggestions available yet.';
                }
            ?></p>
        </div>
    <?php endif; ?>

    <div class="mt-8 flex gap-3">
        <a href="submit.php" class="btn-primary">💡 Submit a Suggestion</a>
        <a href="../../index.php" class="btn-outlined">← Back</a>
    </div>
</div>

<?php require_once '../../templates/footer.php'; ?>
