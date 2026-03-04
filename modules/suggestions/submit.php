<?php
/**
 * modules/suggestions/submit.php
 * Sellers propose market improvements
 */
require_once '../../config/auth_guard.php';
seller_only();
require_once '../../templates/header.php';
require_once '../../config/db.php';

$success = false;
$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $title = htmlspecialchars($_POST['title'] ?? '');
    $desc  = htmlspecialchars($_POST['description'] ?? '');

    if (!$title || !$desc) {
        $error = $t['error_required'];
    } else {
        $stmt = $pdo->prepare("INSERT INTO suggestions (market_id, seller_id, title, description) VALUES (?, ?, ?, ?)");
        $stmt->execute([$_SESSION['market_id'], $_SESSION['user_id'], $title, $desc]);
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

        <form method="POST" class="space-y-4">
            <div>
                <label for="title" class="block font-semibold text-gray-700 mb-2"><?= $t['suggestion_title'] ?></label>
                <input type="text" id="title" name="title" class="input-field" placeholder="Brief title of your idea" required>
            </div>

            <div>
                <label for="description" class="block font-semibold text-gray-700 mb-2"><?= $t['suggestion_description'] ?></label>
                <textarea id="description" name="description" class="input-field resize-none" rows="6" placeholder="Explain your idea in detail..." required></textarea>
            </div>

            <button type="submit" class="w-full btn-primary py-3 text-lg font-bold">
                ✈️ <?= $t['submit'] ?>
            </button>
        </form>
    <?php endif; ?>
</div>

<?php require_once '../../templates/footer.php'; ?>
