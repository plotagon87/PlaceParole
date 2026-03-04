<?php
/**
 * modules/announcements/list.php
 * All users see latest announcements from their market
 */
require_once '../../config/auth_guard.php';
require_once '../../templates/header.php';
require_once '../../config/db.php';

// Fetch announcements for this market
$stmt = $pdo->prepare("SELECT a.*, u.name AS manager_name FROM announcements a LEFT JOIN users u ON a.manager_id = u.id WHERE a.market_id = ? ORDER BY a.created_at DESC LIMIT 50");
$stmt->execute([$_SESSION['market_id']]);
$announcements = $stmt->fetchAll();
?>

<div>
    <div class="flex justify-between items-center mb-6">
        <h1 class="text-3xl font-bold text-primary"><?= $t['announcements'] ?></h1>
        <?php if (isset($_SESSION['user_id']) && $_SESSION['role'] === 'manager'): ?>
            <a href="create.php" class="btn-primary">📝 <?= $t['new_announcement'] ?></a>
        <?php endif; ?>
    </div>

    <?php if (!empty($announcements)): ?>
        <div class="space-y-6">
            <?php foreach ($announcements as $ann): ?>
                <div class="card border-l-4 border-secondary hover:shadow-lg transition">
                    <div class="flex justify-between items-start mb-3">
                        <h2 class="text-xl font-bold text-primary flex-1"><?= htmlspecialchars($ann['title']) ?></h2>
                        <span class="text-xs text-gray-500"><?= date('d/m/Y', strtotime($ann['created_at'])) ?></span>
                    </div>
                    <p class="text-gray-700 mb-3 leading-relaxed"><?= nl2br(htmlspecialchars($ann['body'])) ?></p>
                    <div class="flex gap-2 items-center text-sm text-gray-600">
                        <span>📢 From: <strong><?= htmlspecialchars($ann['manager_name'] ?? 'Market Management') ?></strong></span>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    <?php else: ?>
        <div class="card text-center py-12">
            <div class="text-5xl mb-3">📭</div>
            <p class="text-gray-600 text-lg"><?= $t['no_announcements'] ?></p>
        </div>
    <?php endif; ?>
</div>

<?php require_once '../../templates/footer.php'; ?>
