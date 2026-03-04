<?php
/**
 * modules/community/report.php
 * Sellers report community events (death, illness, emergency)
 */
require_once '../../config/auth_guard.php';
seller_only();
require_once '../../templates/header.php';
require_once '../../config/db.php';

$success = false;
$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $event_type = htmlspecialchars($_POST['event_type'] ?? '');
    $person_name = htmlspecialchars($_POST['person_name'] ?? '');
    $description = htmlspecialchars($_POST['description'] ?? '');

    if (!$event_type || !$person_name || !$description) {
        $error = $t['error_required'];
    } else {
        $stmt = $pdo->prepare("INSERT INTO community_reports (market_id, reported_by, event_type, person_name, description) VALUES (?, ?, ?, ?, ?)");
        $stmt->execute([$_SESSION['market_id'], $_SESSION['user_id'], $event_type, $person_name, $description]);
        $success = true;
    }
}

$event_types = ['event_death', 'event_illness', 'event_emergency', 'event_other'];
?>

<div class="max-w-lg mx-auto bg-white rounded-2xl shadow-lg p-8">
    <h1 class="text-3xl font-bold text-primary mb-2"><?= $t['report_event'] ?></h1>
    <p class="text-gray-600 mb-6">Share community events so market members can offer support</p>

    <?php if ($success): ?>
        <div class="bg-green-100 text-green-800 p-6 rounded-lg mb-6 text-center">
            <div class="text-5xl mb-3">🤝</div>
            <h2 class="text-xl font-bold mb-2"><?= $t['success'] ?>!</h2>
            <p><?= $t['report_sent'] ?></p>
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
                <label for="event_type" class="block font-semibold text-gray-700 mb-2"><?= $t['event_type'] ?></label>
                <select id="event_type" name="event_type" class="input-field" required>
                    <option value="">Choose event type...</option>
                    <?php foreach ($event_types as $type): ?>
                        <option value="<?= str_replace('event_', '', $type) ?>"><?= $t[$type] ?></option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div>
                <label for="person_name" class="block font-semibold text-gray-700 mb-2"><?= $t['person_name'] ?></label>
                <input type="text" id="person_name" name="person_name" class="input-field" placeholder="Full name" required>
            </div>

            <div>
                <label for="description" class="block font-semibold text-gray-700 mb-2"><?= $t['event_description'] ?></label>
                <textarea id="description" name="description" class="input-field resize-none" rows="5" placeholder="Provide details..." required></textarea>
            </div>

            <button type="submit" class="w-full btn-primary py-3 text-lg font-bold">
                📢 <?= $t['submit'] ?>
            </button>
        </form>
    <?php endif; ?>
</div>

<?php require_once '../../templates/footer.php'; ?>
