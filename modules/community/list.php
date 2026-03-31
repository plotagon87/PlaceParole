<?php
/**
 * modules/community/list.php
 * All users see approved community feedback (anonymous)  
 * Replaces the old community_reports listing
 */
require_once '../../config/auth_guard.php';
require_once '../../templates/header.php';
require_once '../../config/db.php';

$is_manager = in_array($_SESSION['role'], ['manager', 'admin']);
$filter_status = $_GET['status'] ?? 'approved';

// If non-managers, force to approved-only
if (!$is_manager) {
    $filter_status = 'approved';
}

// Fetch feedback based on user role
$sql = "
    SELECT 
        cf.id,
        cf.title,
        cf.description,
        cf.status,
        cf.created_at
    FROM community_feedback cf
    WHERE cf.market_id = ? 
    AND cf.deleted_at IS NULL
";
$params = [$_SESSION['market_id']];

// Non-managers only see approved feedback
if (!$is_manager) {
    $sql .= " AND cf.status = 'approved'";
} else {
    // Managers can filter by status
    if ($filter_status && in_array($filter_status, ['pending', 'approved', 'rejected'])) {
        $sql .= " AND cf.status = ?";
        $params[] = $filter_status;
    }
}

$sql .= " ORDER BY cf.created_at DESC LIMIT 100";

$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$feedback = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<div class="container mx-auto px-4 py-8 max-w-4xl">
    <div class="mb-6">
        <h1 class="text-3xl font-bold text-primary mb-2">💬 <?= $t['nav_community'] ?? 'Community Feedback' ?></h1>
        <p class="text-gray-600">Feedback and ideas from our community (anonymous)</p>
    </div>

    <!-- Manager-Only Filters -->
    <?php if ($is_manager): ?>
        <div class="bg-white rounded-lg shadow p-4 mb-6 border border-purple-200">
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
                <a href="../admin/pending_feedback.php" class="text-purple-600 hover:text-purple-800 font-semibold">
                    → Pending Feedback Moderation
                </a>
            </div>
        </div>
    <?php endif; ?>

    <!-- Feedback List -->
    <?php if (!empty($feedback)): ?>
        <div class="space-y-4">
            <?php foreach ($feedback as $item): ?>
                <div class="bg-white rounded-lg shadow p-6 hover:shadow-lg transition">
                    <div class="flex justify-between items-start gap-4 mb-3">
                        <div class="flex-1">
                            <h3 class="text-xl font-bold text-gray-900 mb-1"><?= htmlspecialchars($item['title']) ?></h3>
                            <p class="text-sm text-gray-600">
                                Anonymous feedback · <?= date('M d, Y', strtotime($item['created_at'])) ?>
                            </p>
                        </div>
                        <?php if ($is_manager): ?>
                            <span class="px-3 py-1 rounded-full text-sm font-semibold <?php
                                echo $item['status'] === 'approved' ? 'bg-green-100 text-green-800' : 
                                     ($item['status'] === 'pending' ? 'bg-yellow-100 text-yellow-800' : 'bg-red-100 text-red-800');
                            ?>">
                                <?= ucfirst($item['status']) ?>
                            </span>
                        <?php endif; ?>
                    </div>

                    <p class="text-gray-700 leading-relaxed">
                        <?= nl2br(htmlspecialchars($item['description'])) ?>
                    </p>
                </div>
            <?php endforeach; ?>
        </div>
    <?php else: ?>
        <div class="bg-gray-50 rounded-lg text-center py-16">
            <div class="text-6xl mb-4">💭</div>
            <p class="text-gray-600 text-lg mb-2"><?= $t['no_announcements'] ?? 'No feedback yet' ?></p>
            <p class="text-gray-500"><?php 
                if ($is_manager && $filter_status === 'pending') {
                    echo 'All feedback has been reviewed!';
                } else {
                    echo 'Be the first to share your feedback.';
                }
            ?></p>
        </div>
    <?php endif; ?>

    <div class="mt-8 flex gap-3">
        <a href="report.php" class="btn-primary">💬 <?= $t['submit_feedback'] ?? 'Share Feedback' ?></a>
        <a href="../../index.php" class="btn-outlined">← Back</a>
    </div>
</div>

<?php require_once '../../templates/footer.php'; ?>
