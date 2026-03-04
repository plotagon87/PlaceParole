<?php
/**
 * modules/community/list.php
 * Display community events to all market members (sellers see all, managers can coordinate)
 */
require_once '../../config/auth_guard.php';
require_once '../../templates/header.php';
require_once '../../config/db.php';

$message = '';
$message_type = 'success';

// Handle coordination action (managers only)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && $_SESSION['role'] === 'manager') {
    csrf_verify();
    $report_id = (int) ($_POST['report_id'] ?? 0);

    if ($report_id) {
        $stmt = $pdo->prepare("UPDATE community_reports SET status = 'coordinated' WHERE id = ? AND market_id = ?");
        $result = $stmt->execute([$report_id, $_SESSION['market_id']]);
        
        if ($result) {
            $message = '✓ Event marked as coordinated!';
            $message_type = 'success';
        } else {
            $message = 'Error updating event status.';
            $message_type = 'error';
        }
    }
}

$stmt = $pdo->prepare("SELECT r.*, u.name AS reporter_name FROM community_reports r LEFT JOIN users u ON r.reported_by = u.id WHERE r.market_id = ? ORDER BY r.created_at DESC");
$stmt->execute([$_SESSION['market_id']]);
$reports = $stmt->fetchAll();

$eventIcons = [
    'death' => '⚫',
    'illness' => '🏥',
    'emergency' => '🚨',
    'other' => '📢',
];

$eventColors = [
    'death' => 'border-gray-400 bg-gray-50',
    'illness' => 'border-yellow-400 bg-yellow-50',
    'emergency' => 'border-red-400 bg-red-50',
    'other' => 'border-blue-400 bg-blue-50',
];
?>

<div>
    <div class="flex justify-between items-center mb-6">
        <h1 class="text-3xl font-bold text-primary">🤝 <?= $t['nav_community'] ?></h1>
        <?php if (isset($_SESSION['user_id']) && $_SESSION['role'] === 'seller'): ?>
            <a href="report.php" class="btn-primary">📝 <?= $t['report_event'] ?></a>
        <?php endif; ?>
    </div>

    <!-- Success/Error Message -->
    <?php if ($message): ?>
        <div class="<?= $message_type === 'success' ? 'bg-green-100 text-green-700 border-green-300' : 'bg-red-100 text-red-700 border-red-300' ?> px-4 py-3 rounded-lg mb-6 border">
            <?= $message ?>
        </div>
    <?php endif; ?>

    <?php if (!empty($reports)): ?>
        <div class="space-y-6">
            <?php foreach ($reports as $report): ?>
                <div class="card border-l-4 <?= $eventColors[$report['event_type']] ?? $eventColors['other'] ?>">
                    <div class="flex items-start gap-3 mb-3">
                        <div class="text-3xl"><?= $eventIcons[$report['event_type']] ?? '📢' ?></div>
                        <div class="flex-1">
                            <h2 class="text-xl font-bold text-primary">
                                <?= htmlspecialchars(ucfirst(str_replace('_', ' ', $report['event_type']))) ?>: <?= htmlspecialchars($report['person_name']) ?>
                            </h2>
                            <p class="text-sm text-gray-600">
                                Reported by <?= htmlspecialchars($report['reporter_name'] ?? 'Anonymous') ?> on <?= date('d/m/Y H:i', strtotime($report['created_at'])) ?>
                            </p>
                        </div>
                    </div>
                    <p class="text-gray-700 leading-relaxed mb-3">
                        <?= nl2br(htmlspecialchars($report['description'])) ?>
                    </p>
                    
                    <!-- Status and Action -->
                    <div class="flex gap-3 items-center border-t pt-4">
                        <?php if ($report['status'] === 'coordinated'): ?>
                            <div class="text-xs font-bold bg-green-200 text-green-800 px-3 py-1 rounded-full inline-block">
                                ✅ Action coordinated
                            </div>
                        <?php endif; ?>
                        
                        <?php if ($_SESSION['role'] === 'manager' && $report['status'] !== 'coordinated'): ?>
                            <form method="POST" class="flex-1">
                                <input type="hidden" name="csrf_token" value="<?= csrf_token() ?>">
                                <input type="hidden" name="report_id" value="<?= $report['id'] ?>">
                                <button type="submit" class="w-full bg-blue-500 text-white px-3 py-2 rounded-lg hover:bg-blue-600 transition font-semibold text-sm">
                                    ✓ Mark as Coordinated
                                </button>
                            </form>
                        <?php endif; ?>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    <?php else: ?>
        <div class="card text-center py-12">
            <div class="text-5xl mb-3">🤝</div>
            <p class="text-gray-600 text-lg">No community events reported yet.</p>
        </div>
    <?php endif; ?>
</div>

<?php require_once '../../templates/footer.php'; ?>
