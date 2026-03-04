<?php
/**
 * modules/community/list.php
 * Display community events to all market members
 */
require_once '../../config/auth_guard.php';
require_once '../../templates/header.php';
require_once '../../config/db.php';

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
                    <?php if ($report['status'] === 'coordinated'): ?>
                        <div class="text-xs font-bold bg-green-200 text-green-800 px-3 py-1 rounded-full inline-block">
                            ✅ Action coordinated
                        </div>
                    <?php endif; ?>
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
