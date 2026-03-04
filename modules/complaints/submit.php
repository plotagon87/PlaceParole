<?php
/**
 * modules/complaints/submit.php
 * Sellers submit a complaint via web form
 * A unique reference code is generated and shown
 */
require_once '../../config/auth_guard.php';
seller_only(); // Only sellers can access this page
require_once '../../templates/header.php';
require_once '../../config/db.php';

// Initialize variables used in page rendering
$error    = '';     // Holds error message if something goes wrong
$success  = false;  // Set to true only after successful complaint submission
$ref_code = '';     // Holds the generated reference code

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_verify();
    $category    = $_POST['category']    ?? '';
    $description = $_POST['description'] ?? '';

    if (!$category || !$description) {
        $error = $t['error_required'];
    } else {
        // Generate a unique reference code using the function defined above (outside if-blocks)
        $refCode = generateRefCode($pdo);
        $stmt = $pdo->prepare("
            INSERT INTO complaints (market_id, seller_id, ref_code, category, description, channel, status)
            VALUES (?, ?, ?, ?, ?, 'web', 'pending')
        ");
        $stmt->execute([
            $_SESSION['market_id'],
            $_SESSION['user_id'],
            $refCode,
            $category,
            $description
        ]);

        $success = true;
        $ref_code = $refCode;
    }
}

/**
 * generateRefCode($pdo): string
 * Generates a unique reference code for a complaint
 * Format: MKT-YEAR-RANDOMCODE
 * Example: MKT-2024-a1b2c3d4
 */
function generateRefCode($pdo) {
    do {
        $code = 'MKT-' . date('Y') . '-' . strtoupper(substr(uniqid('', true), -8));
        $stmt = $pdo->prepare("SELECT id FROM complaints WHERE ref_code = ? LIMIT 1");
        $stmt->execute([$code]);
    } while ($stmt->fetch());
    return $code;
}

// Categories for the dropdown
$categories = [
    'cat_infrastructure',
    'cat_sanitation',
    'cat_stall_allocation',
    'cat_security',
    'cat_other'
];
?>

<div class="max-w-lg mx-auto bg-white rounded-2xl shadow-lg p-8">
    <h1 class="text-3xl font-bold text-primary mb-2"><?= $t['submit_complaint'] ?></h1>
    <p class="text-gray-600 mb-6">Help us improve your market by reporting issues</p>

    <?php if ($success): ?>
        <!-- Success Message -->
        <div class="bg-green-100 border-2 border-green-400 text-green-800 rounded-lg p-6 text-center mb-6">
            <div class="text-5xl mb-3">✅</div>
            <h2 class="text-2xl font-bold mb-2"><?= $t['complaint_sent'] ?></h2>
            <p class="text-lg mb-4"><?= $t['your_ref_code'] ?>:</p>
            <div class="bg-primary text-white text-2xl font-bold px-4 py-3 rounded-lg mb-4 tracking-wider">
                <?= $ref_code ?>
            </div>
            <p class="text-sm text-gray-700 mb-4"><?= $t['keep_ref_code'] ?></p>
            <div class="flex gap-3">
                <a href="track.php" class="flex-1 btn-primary py-2">🔍 <?= $t['track_complaint'] ?></a>
                <a href="../../index.php" class="flex-1 btn-outlined py-2">← <?= $t['back'] ?></a>
            </div>
        </div>
    <?php else: ?>
        <!-- Complaint Form -->
        <?php if (isset($error)): ?>
            <div class="bg-red-100 text-red-700 px-4 py-3 rounded-lg mb-6 border border-red-300">
                <strong><?= $t['error'] ?>:</strong> <?= $error ?>
            </div>
        <?php endif; ?>

        <form method="POST" class="space-y-4">
            <input type="hidden" name="csrf_token" value="<?= csrf_token() ?>">
            <!-- Category -->
            <div>
                <label for="category" class="block font-semibold text-gray-700 mb-2"><?= $t['complaint_category'] ?></label>
                <select id="category" name="category" class="input-field" required>
                    <option value="">— <?= $t['select_category'] ?> —</option>
                    <?php foreach ($categories as $cat): ?>
                        <option value="<?= $cat ?>"><?= $t[$cat] ?></option>
                    <?php endforeach; ?>
                </select>
            </div>

            <!-- Description -->
            <div>
                <label for="description" class="block font-semibold text-gray-700 mb-2"><?= $t['complaint_description'] ?></label>
                <textarea 
                    id="description" 
                    name="description" 
                    class="input-field resize-none" 
                    rows="5"
                    placeholder="Describe the problem clearly and provide details..."
                    required
                ></textarea>
            </div>

            <!-- Submit Button -->
            <button type="submit" class="w-full btn-primary py-3 text-lg font-bold">
                📤 <?= $t['submit'] ?>
            </button>
        </form>

        <!-- Info Box -->
        <div class="bg-blue-50 border-l-4 border-primary p-4 rounded mt-6">
            <h3 class="font-bold text-primary mb-2">ℹ️ What happens next?</h3>
            <ul class="text-sm text-gray-700 space-y-1">
                <li>✓ Your complaint will be reviewed by market management</li>
                <li>✓ You will receive updates via SMS/email about status changes</li>
                <li>✓ Use your reference code to track progress anytime</li>
            </ul>
        </div>
    <?php endif; ?>
</div>

<?php require_once '../../templates/footer.php'; ?>
