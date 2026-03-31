<?php
/**
 * modules/suggestions/submit.php
 * Sellers propose market improvements
 */
require_once '../../config/auth_guard.php';
seller_only();

// form on this page
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
        $stmt = $pdo->prepare("INSERT INTO suggestions (market_id, seller_id, title, description, status) VALUES (?, ?, ?, ?, 'pending')");
        $stmt->execute([$_SESSION['market_id'], $_SESSION['user_id'], $title, $desc]);
        $suggestion_id = (int) $pdo->lastInsertId();
        
        // Notify managers/admins of pending suggestion
        if ($suggestion_id > 0) {
            notifyManagersOfPendingSubmission($_SESSION['market_id'], 'new_suggestion', 'suggestion', $suggestion_id);
        }
        
        $success = true;
    }
}
?>

<div class="max-w-lg mx-auto bg-white rounded-2xl shadow-lg p-8">
    <h1 class="text-3xl font-bold text-primary mb-2"><?= $t['submit_suggestion'] ?></h1>
    <p class="text-gray-600 mb-6">Share your ideas to improve the market</p>

    <?php if ($success): ?>
        <div class="bg-green-100 text-green-800 p-6 rounded-lg mb-6 text-center">
            <div class="text-5xl mb-3">💡</div>
            <h2 class="text-xl font-bold mb-2"><?= $t['success'] ?>!</h2>
            <p><?= $t['suggestion_sent'] ?></p>
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
                <label for="title" class="block font-semibold text-gray-700 mb-2" style="display: block; margin-bottom: 0.5rem;"><?= $t['suggestion_title'] ?? 'Title' ?></label>
                <input type="text" id="title" name="title" class="input-field" placeholder="Brief title of your idea" required style="display: block; width: 100%; padding: 0.625rem 0.875rem; border: 1.5px solid #d1d5db; border-radius: 0.5rem; font-size: 1rem;">
            </div>

            <div class="form-group" style="display: block; margin-bottom: 1.5rem;">
                <label for="description" class="block font-semibold text-gray-700 mb-2" style="display: block; margin-bottom: 0.5rem;"><?= $t['suggestion_description'] ?? 'Description' ?></label>
                <textarea id="description" name="description" class="input-field" rows="6" placeholder="Explain your idea in detail..." required style="display: block; width: 100%; padding: 0.625rem 0.875rem; border: 1.5px solid #d1d5db; border-radius: 0.5rem; font-size: 1rem; font-family: inherit; resize: none;"></textarea>
            </div>

            <button type="submit" class="btn-primary" style="display: block; width: 100%; padding: 0.75rem 1rem; font-size: 1.125rem; font-weight: bold; cursor: pointer;">
                ✈️ <?= $t['submit'] ?? 'Submit' ?>
            </button>
        </form>
    <?php endif; ?>
</div>

<?php require_once '../../templates/footer.php'; ?>
