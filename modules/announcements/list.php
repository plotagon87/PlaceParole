<?php
/**
 * modules/announcements/list.php
 * All users see latest announcements from their market
 */
require_once '../../config/auth_guard.php';
require_once '../../templates/header.php';
require_once '../../config/db.php';

// Fetch announcements for this market (exclude deleted)
$stmt = $pdo->prepare("
    SELECT 
        a.*,
        u.name AS manager_name
    FROM announcements a
    LEFT JOIN users u ON a.manager_id = u.id
    WHERE a.market_id = ? AND a.deleted_at IS NULL
    ORDER BY a.created_at DESC
    LIMIT 100
");
$stmt->execute([$_SESSION['market_id']]);
$announcements = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Function to get channel icons
function getChannelIcons($sent_via) {
    $channels = explode(',', $sent_via);
    $icons = [];
    $channel_map = [
        'web' => '🌐 ' . ($t['channel_web'] ?? 'Web'),
        'sms' => '📱 ' . ($t['channel_sms'] ?? 'SMS'),
        'email' => '📧 ' . ($t['channel_email'] ?? 'Email'),
        'gmail' => '📧 ' . ($t['channel_gmail'] ?? 'Gmail'),
        'whatsapp' => '💬 ' . ($t['channel_whatsapp'] ?? 'WhatsApp')
    ];
    foreach ($channels as $ch) {
        $ch = trim($ch);
        if (isset($channel_map[$ch])) {
            $icons[] = $channel_map[$ch];
        }
    }
    return $icons;
}
?>

<div class="container mx-auto px-4 py-8 max-w-4xl">
    <div class="flex justify-between items-start mb-6">
        <div>
            <h1 class="text-3xl font-bold text-primary mb-2">📣 <?= $t['announcements'] ?></h1>
            <p class="text-gray-600">Official announcements from market management</p>
        </div>
        <?php if (isset($_SESSION['user_id']) && in_array($_SESSION['role'], ['manager', 'admin'])): ?>
            <a href="create.php" class="btn-primary whitespace-nowrap">📝 <?= $t['new_announcement'] ?? 'New Announcement' ?></a>
        <?php endif; ?>
    </div>

    <?php if (!empty($announcements)): ?>
        <div class="space-y-4">
            <?php foreach ($announcements as $ann): ?>
                <div class="bg-white rounded-lg shadow p-6 hover:shadow-lg transition border-l-4 border-blue-500">
                    <div class="flex justify-between items-start gap-4 mb-3">
                        <div class="flex-1">
                            <h3 class="text-xl font-bold text-gray-900 mb-1"><?= htmlspecialchars($ann['title']) ?></h3>
                            <p class="text-sm text-gray-600">
                                By: <strong><?= htmlspecialchars($ann['manager_name'] ?? 'Management') ?></strong> 
                                · <?= date('M d, Y H:i', strtotime($ann['created_at'])) ?>
                            </p>
                        </div>
                    </div>

                    <?php if (!empty($ann['picture_path'])): ?>
                        <div class="mb-4">
                            <img src="<?= BASE_URL ?>/<?= htmlspecialchars($ann['picture_path']) ?>" 
                                 alt="Announcement Image" 
                                 class="w-full max-w-md h-auto rounded-lg shadow-sm">
                        </div>
                    <?php endif; ?>

                    <p class="text-gray-700 leading-relaxed mb-4">
                        <?= nl2br(htmlspecialchars($ann['body'])) ?>
                    </p>

                    <?php if (!empty($ann['sent_via'])): ?>
                        <div class="flex flex-wrap gap-2">
                            <?php foreach (getChannelIcons($ann['sent_via']) as $icon): ?>
                                <span class="text-xs bg-blue-100 text-blue-800 px-2 py-1 rounded"><?= $icon ?></span>
                            <?php endforeach; ?>
                        </div>
                    <?php endif; ?>
                </div>
            <?php endforeach; ?>
        </div>
    <?php else: ?>
        <div class="bg-gray-50 rounded-lg text-center py-16">
            <div class="text-6xl mb-4">📭</div>
            <p class="text-gray-600 text-lg"><?= $t['no_announcements'] ?></p>
        </div>
    <?php endif; ?>

    <div class="mt-8">
        <a href="../../index.php" class="btn-outlined">← <?= $t['back'] ?></a>
    </div>
</div>

<?php require_once '../../templates/footer.php'; ?>
