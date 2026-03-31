<?php
/**
 * modules/community/report.php
 * Sellers submit community feedback and ideas (anonymous)
 * Replaces the old event-based community_reports system
 */
require_once '../../config/auth_guard.php';
seller_only();

$pageHasForm = true;
require_once '../../templates/header.php';
require_once '../../config/db.php';
require_once '../../config/notification_handler.php';

$success = false;
$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_verify();
    $title = $_POST['title'] ?? '';
    $desc  = $_POST['description'] ?? '';

    if (!$title || !$desc) {
        $error = $t['error_required'];
    } else {
        // Insert into community_feedback table with anonymous submission
        $stmt = $pdo->prepare("INSERT INTO community_feedback (market_id, user_id, title, description, status) VALUES (?, ?, ?, ?, 'pending')");
        $stmt->execute([$_SESSION['market_id'], $_SESSION['user_id'], $title, $desc]);
        $feedback_id = (int) $pdo->lastInsertId();
        
        // Notify managers/admins of pending feedback
        if ($feedback_id > 0) {
            notifyManagersOfPendingSubmission($_SESSION['market_id'], 'new_community_feedback', 'feedback', $feedback_id);
        }
        
        $success = true;
    }
}
?>

<div class="max-w-lg mx-auto bg-white rounded-2xl shadow-lg p-8">
    <h1 class="text-3xl font-bold text-primary mb-2"><?= $t['submit_feedback'] ?? 'Share Your Feedback' ?></h1>
    <p class="text-gray-600 mb-6"><?= $t['feedback_description'] ?? 'Share ideas, suggestions, or feedback about the market community' ?></p>

    <?php if ($success): ?>
        <div class="bg-green-100 text-green-800 p-6 rounded-lg mb-6 text-center">
            <div class="text-5xl mb-3">💬</div>
            <h2 class="text-xl font-bold mb-2"><?= $t['success'] ?>!</h2>
            <p><?= $t['feedback_sent'] ?? 'Thank you! Your feedback has been received and will be reviewed by our team.' ?></p>
        </div>
        <a href="../../index.php" class="btn-primary w-full py-3 text-center block">← <?= $t['back'] ?></a>
    <?php else: ?>
        <?php if ($error): ?>
            <div class="bg-red-100 text-red-700 px-4 py-3 rounded-lg mb-6 border border-red-300">
                <strong><?= $t['error'] ?>:</strong> <?= $error ?>
            </div>
        <?php endif; ?>

        <form method="POST" class="space-y-6" style="display: block; visibility: visible;">
            <input type="hidden" name="csrf_token" value="<?= csrf_token() ?>" style="display: none;">
            <div class="form-group" style="display: block; margin-bottom: 1.5rem;">
                <label for="title" class="block font-semibold text-gray-700 mb-2" style="display: block; margin-bottom: 0.5rem;"><?= $t['feedback_title'] ?? 'Feedback Title' ?></label>
                <input type="text" id="title" name="title" class="input-field" placeholder="<?= $t['feedback_title_placeholder'] ?? 'Brief title of your feedback' ?>" required style="display: block; width: 100%; padding: 0.625rem 0.875rem; border: 1.5px solid #d1d5db; border-radius: 0.5rem; font-size: 1rem;">
            </div>

            <div class="form-group" style="display: block; margin-bottom: 1.5rem;">
                <label for="description" class="block font-semibold text-gray-700 mb-2" style="display: block; margin-bottom: 0.5rem;"><?= $t['feedback_message'] ?? 'Your Feedback' ?></label>
                <textarea id="description" name="description" class="input-field" rows="6" placeholder="<?= $t['feedback_placeholder'] ?? 'Share your ideas or concerns in detail...' ?>" required style="display: block; width: 100%; padding: 0.625rem 0.875rem; border: 1.5px solid #d1d5db; border-radius: 0.5rem; font-size: 1rem; font-family: inherit; resize: none;"></textarea>
            </div>

            <p class="text-sm text-gray-500" style="display: block;">💡 <?= $t['feedback_anonymous'] ?? 'Your feedback will remain anonymous to other market members.' ?></p>

            <button type="submit" class="btn-primary" style="display: block; width: 100%; padding: 0.75rem 1rem; font-size: 1.125rem; font-weight: bold; cursor: pointer;">
                ✉️ <?= $t['submit'] ?? 'Submit' ?>
            </button>
        </form>
    <?php endif; ?>
</div>

<?php require_once '../../templates/footer.php'; ?>
