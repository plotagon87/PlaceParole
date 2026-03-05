<?php
/**
 * modules/complaints/my_complaints.php
 * Sellers view a full history of all complaints they have ever submitted.
 * This page is protected — sellers only, no managers.
 */
require_once '../../config/auth_guard.php';
seller_only(); // Defined in config/auth_guard.php — stops non-sellers from accessing

require_once '../../templates/header.php';
require_once '../../config/db.php';

// Fetch ALL complaints submitted by the currently logged-in seller
// ORDER BY created_at DESC = most recent complaints appear first
$stmt = $pdo->prepare("
    SELECT * FROM complaints
    WHERE seller_id = ?
    ORDER BY created_at DESC
");
$stmt->execute([$_SESSION['user_id']]);
$complaints = $stmt->fetchAll();

// Map status values to CSS class names for colour-coded badges
$statusColors = [
    'pending'   => 'status-pending',
    'in_review' => 'status-in-review',
    'resolved'  => 'status-resolved',
];
?>

<div>
    <div class="flex justify-between items-center mb-6">
        <h1 class="text-3xl font-bold text-primary">📋 My Complaints</h1>
        <a href="submit.php" class="btn-primary">+ New Complaint</a>
    </div>

    <?php if (!empty($complaints)): ?>
        <div class="space-y-4">
            <?php foreach ($complaints as $c): ?>
                <div class="card">
                    <!-- Header row: reference code + status badge + date -->
                    <div class="flex justify-between items-center mb-3">
                        <span class="font-bold text-primary text-lg"><?= htmlspecialchars($c['ref_code']) ?></span>
                        <div class="flex items-center gap-3">
                            <span class="text-xs text-gray-500">
                                <?= date('d/m/Y', strtotime($c['created_at'])) ?>
                            </span>
                            <span class="<?= $statusColors[$c['status']] ?? 'status-pending' ?>">
                                <?= $t['status_' . $c['status']] ?>
                            </span>
                        </div>
                    </div>

                    <!-- Category and description preview -->
                    <p class="text-sm text-gray-500 mb-1">
                        <strong>Category:</strong> <?= $t[$c['category']] ?? htmlspecialchars($c['category']) ?>
                    </p>
                    <p class="text-gray-700 mb-3">
                        <?= htmlspecialchars(substr($c['description'], 0, 120)) ?>
                        <?= strlen($c['description']) > 120 ? '…' : '' ?>
                    </p>

                    <!-- Manager response, if one exists -->
                    <?php if ($c['response']): ?>
                        <div class="bg-green-50 border-l-4 border-green-400 p-3 rounded text-sm text-gray-700">
                            <strong>✅ Manager Response:</strong><br>
                            <?= nl2br(htmlspecialchars($c['response'])) ?>
                        </div>
                    <?php else: ?>
                        <p class="text-xs text-yellow-600">⏳ Awaiting manager response...</p>
                    <?php endif; ?>
                </div>
            <?php endforeach; ?>
        </div>

    <?php else: ?>
        <div class="card text-center py-12">
            <div class="text-5xl mb-3">📭</div>
            <p class="text-gray-600 text-lg">You have not submitted any complaints yet.</p>
            <a href="submit.php" class="btn-primary mt-4 inline-block">Submit Your First Complaint</a>
        </div>
    <?php endif; ?>
</div>

<?php require_once '../../templates/footer.php'; ?>
