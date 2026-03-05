<?php
/**
 * modules/complaints/track.php
 * Anyone can track a complaint using its reference code
 * No login required
 */
// allows applying stylesheet only when needed
$pageHasForm = true;
require_once '../../templates/header.php';
require_once '../../config/db.php';

$complaint = null;
$notFound  = false;
$refCode   = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $refCode = strtoupper(trim($_POST['ref_code'] ?? ''));

    if ($refCode) {
        // Look up the complaint by reference code
        $stmt = $pdo->prepare("SELECT c.*, u.name AS seller_name FROM complaints c LEFT JOIN users u ON c.seller_id = u.id WHERE c.ref_code = ? LIMIT 1");
        $stmt->execute([$refCode]);
        $complaint = $stmt->fetch();

        if (!$complaint) $notFound = true;
    }
}

// Status colors map
$statusColors = [
    'pending'   => 'status-pending',
    'in_review' => 'status-in-review',
    'resolved'  => 'status-resolved',
];
?>

<div class="max-w-lg mx-auto bg-white rounded-2xl shadow-lg p-8">
    <h1 class="text-3xl font-bold text-primary mb-2"><?= $t['track_complaint'] ?></h1>
    <p class="text-gray-600 mb-6">Enter your reference code to check the status of your complaint</p>

    <!-- Search Form -->
    <form method="POST" class="mb-8">
        <div class="flex gap-2">
            <input 
                type="text" 
                name="ref_code" 
                class="input-field flex-1" 
                placeholder="e.g. MKT-2024-00123"
                maxlength="20"
                required
                autofocus
            >
            <button type="submit" class="btn-primary px-6">
                🔍 <?= $t['view_status'] ?>
            </button>
        </div>
    </form>

    <?php if ($notFound): ?>
        <!-- Not Found -->
        <div class="bg-red-100 border-2 border-red-400 text-red-800 rounded-lg p-6 text-center">
            <div class="text-5xl mb-3">❌</div>
            <h2 class="text-xl font-bold mb-2">Complaint Not Found</h2>
            <p class="text-sm mb-4">The reference code <strong><?= htmlspecialchars($refCode) ?></strong> was not found in our system.</p>
            <p class="text-sm text-gray-700">Please double-check the code and try again, or contact market management if you believe this is an error.</p>
        </div>

    <?php elseif ($complaint): ?>
        <!-- Complaint Found - Show Details -->
        <div class="space-y-4">
            <!-- Reference Code & Status -->
            <div class="bg-gray-50 p-4 rounded-lg border border-gray-200">
                <div class="flex justify-between items-center mb-2">
                    <span class="font-semibold text-gray-700">Reference Code:</span>
                    <span class="text-xl font-bold text-primary"><?= htmlspecialchars($complaint['ref_code']) ?></span>
                </div>
                <div class="flex justify-between items-center">
                    <span class="font-semibold text-gray-700">Current Status:</span>
                    <span class="<?= $statusColors[$complaint['status']] ?? 'status-pending' ?>">
                        <?= $t['status_' . $complaint['status']] ?>
                    </span>
                </div>
            </div>

            <!-- Complaint Details -->
            <div class="bg-blue-50 p-4 rounded-lg border border-blue-200">
                <h3 class="font-bold text-gray-800 mb-2">📝 Complaint Details</h3>
                <div class="space-y-2 text-sm">
                    <div>
                        <span class="font-semibold text-gray-700">Category:</span><br>
                        <?= $t[$complaint['category']] ?? htmlspecialchars($complaint['category']) ?>
                    </div>
                    <div>
                        <span class="font-semibold text-gray-700">Submitted:</span><br>
                        <?= date('d/m/Y H:i', strtotime($complaint['created_at'])) ?>
                    </div>
                    <div>
                        <span class="font-semibold text-gray-700">Channel:</span><br>
                        <?= ucfirst($complaint['channel']) ?>
                    </div>
                    <div>
                        <span class="font-semibold text-gray-700">Description:</span><br>
                        <p class="mt-1 p-2 bg-white rounded border text-gray-700">
                            <?= nl2br(htmlspecialchars($complaint['description'])) ?>
                        </p>
                    </div>
                </div>
            </div>

            <!-- Manager Response (if available) -->
            <?php if ($complaint['response']): ?>
                <div class="bg-green-50 p-4 rounded-lg border border-green-200">
                    <h3 class="font-bold text-gray-800 mb-2">✅ Response from Market Management</h3>
                    <p class="text-sm p-2 bg-white rounded border text-gray-700">
                        <?= nl2br(htmlspecialchars($complaint['response'])) ?>
                    </p>
                    <p class="text-xs text-gray-500 mt-2">Last Updated: <?= date('d/m/Y H:i', strtotime($complaint['updated_at'])) ?></p>
                </div>
            <?php else: ?>
                <div class="bg-yellow-50 p-4 rounded-lg border border-yellow-200">
                    <p class="text-sm text-yellow-800">
                        ⏳ <strong>Your complaint is being reviewed.</strong> Market management will respond shortly.
                    </p>
                </div>
            <?php endif; ?>

            <!-- Status Timeline -->
            <div class="bg-white p-4 rounded-lg border border-gray-200">
                <h3 class="font-bold text-gray-800 mb-3">📊 Status Timeline</h3>
                <div class="space-y-2 text-sm">
                    <div class="flex items-center gap-3">
                        <div class="w-4 h-4 rounded-full <?= $complaint['status'] !== 'pending' ? 'bg-green-500' : 'bg-primary' ?>"></div>
                        <span class="text-gray-700"><strong>Submitted</strong> - <?= date('d/m/Y', strtotime($complaint['created_at'])) ?></span>
                    </div>
                    <div class="flex items-center gap-3">
                        <div class="w-4 h-4 rounded-full <?= in_array($complaint['status'], ['in_review', 'resolved']) ? 'bg-green-500' : 'bg-gray-300' ?>"></div>
                        <span class="text-gray-700"><strong>Under Review</strong></span>
                    </div>
                    <div class="flex items-center gap-3">
                        <div class="w-4 h-4 rounded-full <?= $complaint['status'] === 'resolved' ? 'bg-green-500' : 'bg-gray-300' ?>"></div>
                        <span class="text-gray-700"><strong>Resolved</strong></span>
                    </div>
                </div>
            </div>

            <!-- Track Again Button -->
            <a href="track.php" class="w-full btn-outlined py-2 text-center block">🔄 Track Another Complaint</a>
        </div>

    <?php else: ?>
        <!-- Initial State - Waiting for input -->
        <div class="bg-gray-50 p-6 rounded-lg text-center border border-gray-200">
            <div class="text-5xl mb-3">🔍</div>
            <p class="text-gray-600">Enter your reference code above to check your complaint status</p>
        </div>
    <?php endif; ?>
</div>

<?php require_once '../../templates/footer.php'; ?>
