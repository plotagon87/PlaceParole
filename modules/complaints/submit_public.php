<?php
/**
 * modules/complaints/submit_public.php
 * Public form for users to submit complaints
 * Users can select market and seller
 */

// This page contains a form, so enable global stylesheet
$pageHasForm = true;
require_once '../../templates/header.php';
require_once '../../config/db.php';
require_once '../../config/market_validator.php'; // Market data validation

// Initialize variables used in page rendering
$error    = '';     // Holds error message if something goes wrong
$success  = false;  // Set to true only after successful complaint submission
$ref_code = '';     // Holds the generated reference code

// Get all markets for dropdown - GUARANTEED from database
// Uses MarketValidator to ensure data originates from database
$markets = array_map(function($m) {
    MarketValidator::verifyDatabaseSource($m);
    return $m;
}, MarketValidator::getAllMarkets($pdo));

// Get sellers if market is selected
$sellers = [];
$selected_market = $_POST['market_id'] ?? '';
if ($selected_market) {
    // Validate market exists in database before querying sellers
    if (!MarketValidator::validateMarketExists($pdo, $selected_market)) {
        $error = 'Invalid market selected.';
    } else {
        $stmt = $pdo->prepare("SELECT id, name, stall_no FROM users WHERE role = 'seller' AND market_id = ? ORDER BY name");
        $stmt->execute([$selected_market]);
        $sellers = $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
}

if (isset($_POST['submit_complaint'])) {
    $market_id   = $_POST['market_id']   ?? '';
    $seller_id   = $_POST['seller_id']   ?? '';
    $category    = $_POST['category']    ?? '';
    $description = $_POST['description'] ?? '';

    // Handle photo upload (optional)
    $photo_path = null;
    if (!empty($_FILES['photo']['name'])) {
        $allowed_types = ['image/jpeg', 'image/png', 'image/webp'];
        $max_size      = 2 * 1024 * 1024; // 2 megabytes maximum

        if (!in_array($_FILES['photo']['type'], $allowed_types)) {
            $error = 'Only JPEG, PNG, and WebP images are allowed.';
        } elseif ($_FILES['photo']['size'] > $max_size) {
            $error = 'Photo must be smaller than 2MB.';
        } else {
            // Generate a unique filename to prevent overwriting existing files
            $ext       = pathinfo($_FILES['photo']['name'], PATHINFO_EXTENSION);
            $filename  = uniqid('complaint_', true) . '.' . $ext;
            $dest      = __DIR__ . '/../../uploads/complaints/' . $filename;

            if (move_uploaded_file($_FILES['photo']['tmp_name'], $dest)) {
                $photo_path = 'uploads/complaints/' . $filename;
            } else {
                $error = 'Photo upload failed. Please try again.';
            }
        }
    }

    if (!$market_id || !$seller_id || !$category || !$description) {
        $error = 'All fields are required.';
    } elseif (empty($error)) {
        // Generate a unique reference code
        $refCode = generateRefCode($pdo);
        $stmt = $pdo->prepare("
            INSERT INTO complaints (market_id, seller_id, ref_code, category, description, channel, status, photo_path, sla_deadline)
            VALUES (?, ?, ?, ?, ?, 'web', 'pending', ?, DATE_ADD(NOW(), INTERVAL 72 HOUR))
        ");
        if ($stmt->execute([
            $market_id,
            $seller_id,
            $refCode,
            $category,
            $description,
            $photo_path
        ])) {
            $success = true;
            $ref_code = $refCode;
        } else {
            $error = 'Failed to submit complaint. Please try again.';
        }
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
    <h1 class="text-3xl font-bold text-primary mb-2"><?= $t['submit_complaint_title'] ?></h1>
    <p class="text-gray-600 mb-6"><?= $t['help_improve_market'] ?></p>

    <?php if ($success): ?>
        <!-- Success Message -->
        <div class="bg-green-100 border-2 border-green-400 text-green-800 rounded-lg p-6 text-center mb-6">
            <div class="text-5xl mb-3">✅</div>
            <h2 class="text-2xl font-bold mb-2"><?= $t['complaint_sent_title'] ?></h2>
            <p class="text-lg mb-4"><?= $t['your_ref_code_is'] ?></p>
            <div class="bg-primary text-white text-2xl font-bold px-4 py-3 rounded-lg mb-4 tracking-wider">
                <?= $ref_code ?>
            </div>
            <p class="text-sm text-gray-700 mb-4">Keep this code to track your complaint status.</p>
            <div class="flex gap-3">
                <a href="track.php" class="flex-1 btn-primary py-2">🔍 Track Complaint</a>
                <a href="../../index.php" class="flex-1 btn-outlined py-2">← Back</a>
            </div>
        </div>
    <?php else: ?>
        <!-- Complaint Form -->
        <?php if (!empty($error)): ?>
            <div class="bg-red-100 text-red-700 px-4 py-3 rounded-lg mb-6 border border-red-300">
                <strong>Error:</strong> <?= $error ?>
            </div>
        <?php endif; ?>

        <form method="POST" enctype="multipart/form-data" class="space-y-4">
            <!-- Market -->
            <div>
                <label for="market_id" class="block font-semibold text-gray-700 mb-2">Market</label>
                <select id="market_id" name="market_id" class="input-field" required>
                    <option value="">— Select Market —</option>
                    <?php foreach ($markets as $market): ?>
                        <option value="<?= $market['id'] ?>" <?= $selected_market == $market['id'] ? 'selected' : '' ?>>
                            <?= htmlspecialchars($market['name']) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>

            <button type="submit" name="load_sellers" class="btn-primary py-2 px-4">Load Sellers</button>

            <?php if ($selected_market && $sellers): ?>
            <!-- Seller -->
            <div>
                <label for="seller_id" class="block font-semibold text-gray-700 mb-2">Seller</label>
                <select id="seller_id" name="seller_id" class="input-field" required>
                    <option value="">— Select Seller —</option>
                    <?php foreach ($sellers as $seller): ?>
                        <option value="<?= $seller['id'] ?>">
                            <?= htmlspecialchars($seller['name']) ?> (Stall: <?= htmlspecialchars($seller['stall_no']) ?>)
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>

            <!-- Category -->
            <div>
                <label for="category" class="block font-semibold text-gray-700 mb-2">Complaint Category</label>
                <select id="category" name="category" class="input-field" required>
                    <option value="">— Select Category —</option>
                    <?php foreach ($categories as $cat): ?>
                        <option value="<?= $cat ?>"><?= $t[$cat] ?? $cat ?></option>
                    <?php endforeach; ?>
                </select>
            </div>

            <!-- Description -->
            <div>
                <label for="description" class="block font-semibold text-gray-700 mb-2">Description</label>
                <textarea 
                    id="description" 
                    name="description" 
                    class="input-field resize-none" 
                    rows="5"
                    placeholder="Describe the problem clearly and provide details..."
                    required
                ></textarea>
            </div>

            <!-- Photo Attachment (optional) -->
            <div>
                <label for="photo" class="block font-semibold text-gray-700 mb-2">📸 Attach a Photo (optional)</label>
                <input type="file" id="photo" name="photo" accept="image/jpeg,image/png,image/webp" class="input-field">
                <p class="text-xs text-gray-500 mt-2">Supported: JPEG, PNG, WebP. Maximum 2MB.</p>
            </div>

            <!-- Submit Button -->
            <button type="submit" name="submit_complaint" class="w-full btn-primary py-3 text-lg font-bold">
                📤 Submit Complaint
            </button>
            <?php elseif ($selected_market && empty($sellers)): ?>
                <p class="text-red-500">No sellers available for the selected market.</p>
            <?php endif; ?>
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

<?php require_once '../../templates/footer.php'; ?></content>
<parameter name="filePath">c:\xampp\htdocs\PlaceParole\modules\complaints\submit_public.php