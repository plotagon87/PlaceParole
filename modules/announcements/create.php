<?php
/**
 * modules/announcements/create.php
 * Managers broadcast announcements to all sellers in their market
 */
require_once '../../config/auth_guard.php';
manager_only();

$pageHasForm = true;
require_once '../../templates/header.php';
require_once '../../config/db.php';
require_once '../../config/notification_handler.php';

$success = false;
$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_verify();
    $title = $_POST['title'] ?? '';
    $body  = $_POST['body']  ?? '';
    $sent_via = $_POST['sent_via'] ?? ['web'];
    
    // Ensure sent_via is an array
    if (is_string($sent_via)) {
        $sent_via = [$sent_via];
    }
    
    // Convert to comma-separated string for storage
    $sent_via_str = implode(',', array_filter($sent_via));

    if (!$title || !$body) {
        $error = $t['error_required'];
    } elseif (empty($sent_via)) {
        $error = $t['error_select_channel'];
    } else {
        $stmt = $pdo->prepare("INSERT INTO announcements (market_id, manager_id, title, body, sent_via) VALUES (?, ?, ?, ?, ?)");
        $stmt->execute([$_SESSION['market_id'], $_SESSION['user_id'], $title, $body, $sent_via_str]);
        $announcement_id = (int) $pdo->lastInsertId();
        
        // Notify all market users of new announcement via web channel (users can subscribe to SMS/email separately)
        if ($announcement_id > 0) {
            notifyMarketUsersOfSubmission($_SESSION['market_id'], 'new_announcement', 'announcement', $announcement_id, ['web']);
        }
        
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

    <form method="POST" class="space-y-6" style="display: block; visibility: visible;">
        <input type="hidden" name="csrf_token" value="<?= csrf_token() ?>" style="display: none;">
        
        <div class="form-group" style="display: block; margin-bottom: 1.5rem;">
            <label for="title" class="block font-semibold text-gray-700 mb-2" style="display: block; margin-bottom: 0.5rem;">
                <?= $t['announcement_title'] ?? 'Title' ?>
            </label>
            <input type="text" id="title" name="title" class="input-field" placeholder="e.g. Market Closure Tomorrow" required style="display: block; width: 100%; padding: 0.625rem 0.875rem; border: 1.5px solid #d1d5db; border-radius: 0.5rem; font-size: 1rem;">
        </div>

        <div class="form-group" style="display: block; margin-bottom: 1.5rem;">
            <label for="body" class="block font-semibold text-gray-700 mb-2" style="display: block; margin-bottom: 0.5rem;">
                <?= $t['announcement_body'] ?? 'Message' ?>
            </label>
            <textarea id="body" name="body" class="input-field" rows="6" placeholder="Write your announcement here..." required style="display: block; width: 100%; padding: 0.625rem 0.875rem; border: 1.5px solid #d1d5db; border-radius: 0.5rem; font-size: 1rem; font-family: inherit; resize: none;"></textarea>
        </div>

        <div class="form-group" style="display: block; margin-bottom: 1.5rem;">
            <label style="display: block; font-weight: 600; color: rgb(55, 65, 81); margin-bottom: 0.75rem;">
                📱 <?= $t['announcement_channels'] ?? 'Send via:' ?>
            </label>
            <div class="channels-group" style="display: block;">
                <label style="display: flex; align-items: center; gap: 0.75rem; cursor: pointer; margin-bottom: 0.5rem;">
                    <input type="checkbox" name="sent_via[]" value="web" checked style="display: inline-block; width: 1.25rem; height: 1.25rem; cursor: pointer;">
                    <span><?= $t['channel_web'] ?? 'Web/In-App' ?></span>
                </label>
                <label style="display: flex; align-items: center; gap: 0.75rem; cursor: pointer; margin-bottom: 0.5rem;">
                    <input type="checkbox" name="sent_via[]" value="sms" style="display: inline-block; width: 1.25rem; height: 1.25rem; cursor: pointer;">
                    <span><?= $t['channel_sms'] ?? 'SMS' ?></span>
                </label>
                <label style="display: flex; align-items: center; gap: 0.75rem; cursor: pointer;">
                    <input type="checkbox" name="sent_via[]" value="email" style="display: inline-block; width: 1.25rem; height: 1.25rem; cursor: pointer;">
                    <span><?= $t['channel_email'] ?? 'Email' ?></span>
                </label>
            </div>
        </div>

        <button type="submit" class="btn-primary" style="display: block; width: 100%; padding: 0.75rem 1rem; font-size: 1.125rem; font-weight: bold; cursor: pointer;">
            📢 <?= $t['broadcast_announcement'] ?? 'Broadcast Announcement' ?>
        </button>
    </form>
</div>

<?php require_once '../../templates/footer.php'; ?>
