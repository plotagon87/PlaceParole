<?php
/**
 * modules/complaints/respond.php
 * Manager responds to a complaint and updates its status
 */
require_once '../../config/auth_guard.php';
manager_only(); // Only managers can access this page
require_once '../../templates/header.php';
require_once '../../config/db.php';

$id = (int) ($_GET['id'] ?? 0);

// Fetch the complaint — verify it belongs to this manager's market (security)
$stmt = $pdo->prepare("SELECT * FROM complaints WHERE id = ? AND market_id = ? LIMIT 1");
$stmt->execute([$id, $_SESSION['market_id']]);
$complaint = $stmt->fetch();

if (!$complaint) {
    die("<div style='padding: 20px; background: #f8d7da; color: #721c24;'><h2>❌ Complaint not found or access denied.</h2></div>");
}

$success = false;
$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $response = htmlspecialchars($_POST['response'] ?? '');
    $status   = htmlspecialchars($_POST['status']   ?? '');

    if (!$response || !$status) {
        $error = $t['error_required'];
    } elseif (!in_array($status, ['pending', 'in_review', 'resolved'])) {
        $error = "Invalid status selected.";
    } else {
        // Update the complaint
        $stmt = $pdo->prepare("UPDATE complaints SET response = ?, status = ?, updated_at = NOW() WHERE id = ?");
        $stmt->execute([$response, $status, $complaint['id']]);

        $success = true;
        // Refresh complaint data
        $stmt = $pdo->prepare("SELECT * FROM complaints WHERE id = ? LIMIT 1");
        $stmt->execute([$complaint['id']]);
        $complaint = $stmt->fetch();
    }
}

$statusColors = [
    'pending'   => 'status-pending',
    'in_review' => 'status-in-review',
    'resolved'  => 'status-resolved',
];
?>

<div class="max-w-2xl mx-auto">
    <!-- Back Link -->
    <a href="list.php" class="text-primary font-semibold hover:underline mb-6 inline-block">← <?= $t['back'] ?> to <?= $t['all_complaints'] ?></a>

    <!-- Success Message -->
    <?php if ($success): ?>
        <div class="bg-green-100 border-2 border-green-400 text-green-800 rounded-lg p-4 mb-6">
            ✅ <?= $t['success'] ?>! Complaint updated and saved.
        </div>
    <?php endif; ?>

    <!-- Error Message -->
    <?php if ($error): ?>
        <div class="bg-red-100 border-2 border-red-400 text-red-800 rounded-lg p-4 mb-6">
            ❌ <?= $error ?>
        </div>
    <?php endif; ?>

    <!-- Complaint Details Card -->
    <div class="card mb-6">
        <h1 class="text-2xl font-bold text-primary mb-4">
            📋 Respond to Complaint: <span class="text-secondary"><?= htmlspecialchars($complaint['ref_code']) ?></span>
        </h1>

        <div class="grid grid-cols-2 gap-4 mb-6 pb-6 border-b">
            <div>
                <span class="text-gray-600 text-sm">Status</span>
                <div class="<?= $statusColors[$complaint['status']] ?>">
                    <?= $t['status_' . str_replace('_', '_', $complaint['status'])] ?>
                </div>
            </div>
            <div>
                <span class="text-gray-600 text-sm">Category</span>
                <div class="font-semibold mt-1"><?= $t[$complaint['category']] ?? htmlspecialchars($complaint['category']) ?></div>
            </div>
            <div>
                <span class="text-gray-600 text-sm">Submitted</span>
                <div class="font-semibold mt-1"><?= date('d/m/Y H:i', strtotime($complaint['created_at'])) ?></div>
            </div>
            <div>
                <span class="text-gray-600 text-sm">Channel</span>
                <div class="font-semibold mt-1"><?= ucfirst($complaint['channel']) ?></div>
            </div>
        </div>

        <!-- Complaint Description -->
        <div class="bg-gray-50 p-4 rounded-lg border mb-6">
            <h2 class="font-bold text-gray-800 mb-2">📝 Complaint Description</h2>
            <p class="text-gray-700 leading-relaxed">
                <?= nl2br(htmlspecialchars($complaint['description'])) ?>
            </p>
        </div>
    </div>

    <!-- Response Form -->
    <div class="card">
        <h2 class="text-xl font-bold text-primary mb-6">✏️ Your Response</h2>

        <form method="POST" class="space-y-4">
            <!-- Status Selection -->
            <div>
                <label for="status" class="block font-semibold text-gray-700 mb-2">Update Status</label>
                <select id="status" name="status" class="input-field" required>
                    <option value="pending" <?= $complaint['status'] === 'pending' ? 'selected' : '' ?>>
                        🔴 <?= $t['status_pending'] ?> — Not yet reviewed
                    </option>
                    <option value="in_review" <?= $complaint['status'] === 'in_review' ? 'selected' : '' ?>>
                        🟡 <?= $t['status_in_review'] ?> — Currently working on this
                    </option>
                    <option value="resolved" <?= $complaint['status'] === 'resolved' ? 'selected' : '' ?>>
                        🟢 <?= $t['status_resolved'] ?> — Issue has been addressed
                    </option>
                </select>
            </div>

            <!-- Response Text -->
            <div>
                <label for="response" class="block font-semibold text-gray-700 mb-2">
                    <?= $t['response'] ?> to Seller
                </label>
                <textarea
                    id="response"
                    name="response"
                    class="input-field resize-none"
                    rows="6"
                    placeholder="Explain what action has been taken or what you plan to do..."
                    required
                ><?= htmlspecialchars($complaint['response'] ?? '') ?></textarea>
                <p class="text-xs text-gray-500 mt-1">This message will be visible to the seller when they track their complaint.</p>
            </div>

            <!-- Buttons -->
            <div class="flex gap-3 pt-4 border-t">
                <button type="submit" class="flex-1 btn-primary py-3 font-semibold">
                    💾 Save Response
                </button>
                <a href="list.php" class="flex-1 btn-outlined py-3 font-semibold text-center">
                    <?= $t['cancel'] ?>
                </a>
            </div>
        </form>
    </div>
</div>

<?php require_once '../../templates/footer.php'; ?>
