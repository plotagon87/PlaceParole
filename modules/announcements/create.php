<?php
/**
 * modules/announcements/create.php
 * Managers broadcast announcements to all sellers in their market
 */
require_once '../../config/auth_guard.php';
manager_only();
require_once '../../templates/header.php';
require_once '../../config/db.php';

$success = false;
$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $title = htmlspecialchars($_POST['title'] ?? '');
    $body  = htmlspecialchars($_POST['body']  ?? '');
    $sent_via = implode(',', $_POST['sent_via'] ?? ['web']);

    if (!$title || !$body) {
        $error = $t['error_required'];
    } else {
        $stmt = $pdo->prepare("INSERT INTO announcements (market_id, manager_id, title, body, sent_via) VALUES (?, ?, ?, ?, ?)");
        $stmt->execute([$_SESSION['market_id'], $_SESSION['user_id'], $title, $body, $sent_via]);
        $success = true;
    }
}
?>

<div class="max-w-lg mx-auto bg-white rounded-2xl shadow-lg p-8">
    <h1 class="text-3xl font-bold text-primary mb-2"><?= $t['broadcast_announcement'] ?></h1>
    <p class="text-gray-600 mb-6">Send an official message to all registered sellers in your market</p>

    <?php if ($success): ?>
        <div class="bg-green-100 text-green-800 p-6 rounded-lg mb-6 text-center">
            <div class="text-5xl mb-3">📣</div>
            <h2 class="text-xl font-bold mb-2"><?= $t['success'] ?>!</h2>
            <p class="mb-4">Your announcement has been broadcast to all sellers.</p>
            <div class="flex gap-2">
                <a href="list.php" class="flex-1 btn-primary py-2">View Announcements</a>
                <a href="create.php" class="flex-1 btn-outlined py-2">Create Another</a>
            </div>
        </div>
    <?php endif; ?>

    <?php if ($error): ?>
        <div class="bg-red-100 text-red-700 px-4 py-3 rounded-lg mb-6 border border-red-300">
            <strong><?= $t['error'] ?>:</strong> <?= $error ?>
        </div>
    <?php endif; ?>

    <form method="POST" class="space-y-4">
        <div>
            <label for="title" class="block font-semibold text-gray-700 mb-2"><?= $t['announcement_title'] ?></label>
            <input type="text" id="title" name="title" class="input-field" placeholder="e.g. Market Closure Tomorrow" required>
        </div>

        <div>
            <label for="body" class="block font-semibold text-gray-700 mb-2"><?= $t['announcement_body'] ?></label>
            <textarea id="body" name="body" class="input-field resize-none" rows="6" placeholder="Write your announcement here..." required></textarea>
        </div>

        <button type="submit" class="w-full btn-primary py-3 text-lg font-bold">
            📢 <?= $t['broadcast_announcement'] ?>
        </button>
    </form>
</div>

<?php require_once '../../templates/footer.php'; ?>
