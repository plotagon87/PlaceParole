<?php
/**
 * modules/admin/pending_suggestions.php
 * Manager/Admin moderation page for pending suggestions
 */
require_once '../../config/auth_guard.php';
manager_only();

require_once '../../templates/header.php';
require_once '../../config/db.php';
require_once '../../config/notification_handler.php';

$action_success = false;
$action_error = '';

// Handle approval/rejection
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    csrf_verify();
    $suggestion_id = (int) ($_POST['suggestion_id'] ?? 0);
    $action = $_POST['action'] ?? '';
    $reason = $_POST['reason'] ?? '';

    if (!$suggestion_id || !in_array($action, ['approve', 'reject'])) {
        $action_error = 'Invalid request';
    } else {
        // Verify the suggestion exists and is pending
        $stmt = $pdo->prepare("SELECT id, market_id, status FROM suggestions WHERE id = ? AND market_id = ?");
        $stmt->execute([$suggestion_id, $_SESSION['market_id']]);
        $suggestion = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$suggestion) {
            $action_error = 'Suggestion not found';
        } elseif ($suggestion['status'] !== 'pending') {
            $action_error = 'Suggestion is not pending';
        } else {
            $new_status = $action === 'approve' ? 'approved' : 'rejected';

            // Update suggestion status
            $stmt = $pdo->prepare("UPDATE suggestions SET status = ? WHERE id = ?");
            $stmt->execute([$new_status, $suggestion_id]);

            // Log moderation action
            $stmt = $pdo->prepare("
                INSERT INTO moderation_log (market_id, actor_id, action_type, subject_type, subject_id, reason)
                VALUES (?, ?, ?, 'suggestion', ?, ?)
            ");
            $stmt->execute([
                $_SESSION['market_id'],
                $_SESSION['user_id'],
                $action === 'approve' ? 'suggestion_approved' : 'suggestion_rejected',
                $suggestion_id,
                $reason ?: null
            ]);

            // If approved, notify all market users
            if ($action === 'approve') {
                notifyMarketUsersOfSubmission($_SESSION['market_id'], 'suggestion_approved', 'suggestion', $suggestion_id);
            }

            $action_success = true;
        }
    }
}

// Fetch pending suggestions
$stmt = $pdo->prepare("
    SELECT 
        s.id,
        s.title,
        s.description,
        s.created_at,
        u.name as submitter_name,
        u.email
    FROM suggestions s
    JOIN users u ON s.seller_id = u.id
    WHERE s.market_id = ? AND s.status = 'pending'
    ORDER BY s.created_at DESC
");
$stmt->execute([$_SESSION['market_id']]);
$pending_suggestions = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<div class="container mx-auto px-4 py-8 max-w-6xl">
    <div class="mb-6">
        <h1 class="text-3xl font-bold text-primary mb-2"><?= $t['pending_suggestions'] ?></h1>
        <p class="text-gray-600">Review and moderate pending suggestions from sellers</p>
    </div>

    <?php if ($action_success): ?>
        <div class="bg-green-100 text-green-800 px-4 py-3 rounded-lg mb-6 border border-green-300">
            ✓ <?= $t['approve_success'] ?? 'Action completed successfully' ?>
        </div>
    <?php endif; ?>

    <?php if ($action_error): ?>
        <div class="bg-red-100 text-red-700 px-4 py-3 rounded-lg mb-6 border border-red-300">
            <strong><?= $t['error'] ?>:</strong> <?= $action_error ?>
        </div>
    <?php endif; ?>

    <?php if (empty($pending_suggestions)): ?>
        <div class="text-center py-12 bg-gray-50 rounded-lg">
            <p class="text-gray-600 text-lg">No pending suggestions to review</p>
        </div>
    <?php else: ?>
        <div class="space-y-4">
            <?php foreach ($pending_suggestions as $suggestion): ?>
                <div class="bg-white rounded-lg shadow p-6 border-l-4 border-yellow-400">
                    <div class="grid grid-cols-1 md:grid-cols-3 gap-4 mb-4">
                        <div>
                            <p class="text-sm text-gray-600">Submitted by</p>
                            <p class="font-semibold"><?= htmlspecialchars($suggestion['submitter_name']) ?></p>
                            <p class="text-sm text-gray-600"><?= htmlspecialchars($suggestion['email']) ?></p>
                        </div>
                        <div>
                            <p class="text-sm text-gray-600">Date</p>
                            <p class="font-semibold"><?= date('M d, Y H:i', strtotime($suggestion['created_at'])) ?></p>
                        </div>
                        <div></div>
                    </div>

                    <div class="mb-4">
                        <h3 class="text-xl font-bold mb-2"><?= htmlspecialchars($suggestion['title']) ?></h3>
                        <p class="text-gray-700"><?= nl2br(htmlspecialchars($suggestion['description'])) ?></p>
                    </div>

                    <form method="POST" class="border-t pt-4">
                        <input type="hidden" name="csrf_token" value="<?= csrf_token() ?>">
                        <input type="hidden" name="suggestion_id" value="<?= $suggestion['id'] ?>">

                        <div class="mb-3">
                            <label for="reason_<?= $suggestion['id'] ?>" class="block text-sm text-gray-700 mb-1">
                                <?= $t['reason'] ?> (<?= $t['optional'] ?? 'optional' ?>)
                            </label>
                            <input type="text" 
                                   id="reason_<?= $suggestion['id'] ?>" 
                                   name="reason" 
                                   class="input-field text-sm"
                                   placeholder="Reason for rejection (if applicable)">
                        </div>

                        <div class="flex gap-2">
                            <button type="submit" name="action" value="approve" class="flex-1 bg-green-600 text-white px-4 py-2 rounded hover:bg-green-700 font-semibold">
                                ✓ <?= $t['approve'] ?>
                            </button>
                            <button type="submit" name="action" value="reject" class="flex-1 bg-red-600 text-white px-4 py-2 rounded hover:bg-red-700 font-semibold">
                                ✗ <?= $t['reject'] ?>
                            </button>
                        </div>
                    </form>
                </div>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>

    <div class="mt-8">
        <a href="../../index.php" class="btn-primary">← <?= $t['back'] ?></a>
    </div>
</div>

<?php require_once '../../templates/footer.php'; ?>
