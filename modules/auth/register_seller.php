<?php
/**
 * modules/auth/register_seller.php
 * Seller selects an existing market, then registers their account
 */
require_once '../../templates/header.php';
require_once '../../config/db.php';

// Load all markets for the dropdown
$markets = $pdo->query("SELECT id, name, location FROM markets ORDER BY name ASC")->fetchAll();

$errors = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_verify();
    $market_id  = (int) ($_POST['market_id']  ?? 0);
    $name       = trim($_POST['name']         ?? '');
    $email      = trim($_POST['email']        ?? '');
    $phone      = trim($_POST['phone']        ?? '');
    $stall_no   = trim($_POST['stall_no']     ?? '');
    $password   = trim($_POST['password']     ?? '');
    $password_confirm = trim($_POST['password_confirm'] ?? '');

    // Validation
    if (!$market_id)        $errors[] = "Please select a market.";
    if (!$name)             $errors[] = $t['error_required'];
    if (!$email)            $errors[] = $t['error_required'];
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) $errors[] = $t['error_invalid_email'];
    if (!$phone)            $errors[] = $t['error_required'];
    if (!$stall_no)         $errors[] = $t['error_required'];
    if (!$password || strlen($password) < 6) $errors[] = "Password must be at least 6 characters.";
    if ($password !== $password_confirm) $errors[] = $t['error_password_match'];

    // Check if email already exists
    if (!$errors) {
        $stmt = $pdo->prepare("SELECT id FROM users WHERE email = ? LIMIT 1");
        $stmt->execute([$email]);
        if ($stmt->fetch()) $errors[] = $t['error_email_exists'];
    }

    // Check if phone already exists
    if (!$errors) {
        $stmt = $pdo->prepare("SELECT id FROM users WHERE phone = ? LIMIT 1");
        $stmt->execute([$phone]);
        if ($stmt->fetch()) $errors[] = $t['error_phone_exists'];
    }

    // If no errors, create the seller user
    if (!$errors) {
        try {
            $password_hash = password_hash($password, PASSWORD_DEFAULT);
            $stmt = $pdo->prepare("
                INSERT INTO users (market_id, name, email, phone, stall_no, role, password, lang)
                VALUES (?, ?, ?, ?, ?, 'seller', ?, ?)
            ");
            $stmt->execute([$market_id, $name, $email, $phone, $stall_no, $password_hash, $_SESSION['lang']]);

            $_SESSION['success'] = $t['register_success'];
            header('Location: login.php');
            exit;

        } catch (Exception $e) {
            $errors[] = "Registration failed: " . $e->getMessage();
        }
    }
}
?>

<div class="max-w-lg mx-auto bg-white rounded-2xl shadow-lg p-8 mt-10">
    <h1 class="text-3xl font-bold text-primary mb-2"><?= $t['register'] ?></h1>
    <p class="text-gray-600 mb-6"><?= $t['seller'] ?> <?= $t['register'] ?></p>

    <?php if (!empty($errors)): ?>
        <div class="bg-red-100 text-red-700 px-4 py-3 rounded-lg mb-6 border border-red-300">
            <strong><?= $t['error'] ?>:</strong>
            <ul class="mt-2">
                <?php foreach ($errors as $err): ?>
                    <li>• <?= $err ?></li>
                <?php endforeach; ?>
            </ul>
        </div>
    <?php endif; ?>

    <form method="POST" class="space-y-4">
        <input type="hidden" name="csrf_token" value="<?= csrf_token() ?>">
        <!-- Market Selection -->
        <div>
            <label for="market_id" class="block font-semibold text-gray-700 mb-2"><?= $t['select_market'] ?></label>
            <select id="market_id" name="market_id" class="input-field" required>
                <option value="">Choose your market...</option>
                <?php foreach ($markets as $market): ?>
                    <option value="<?= $market['id'] ?>">
                        <?= htmlspecialchars($market['name']) ?> — <?= htmlspecialchars($market['location']) ?>
                    </option>
                <?php endforeach; ?>
            </select>
            <?php if (empty($markets)): ?>
                <p class="text-red-600 text-sm mt-2">No markets registered yet. Please contact an administrator.</p>
            <?php endif; ?>
        </div>

        <!-- Name -->
        <div>
            <label for="name" class="block font-semibold text-gray-700 mb-2"><?= $t['name'] ?></label>
            <input type="text" id="name" name="name" class="input-field" placeholder="Your full name" required>
        </div>

        <!-- Email -->
        <div>
            <label for="email" class="block font-semibold text-gray-700 mb-2"><?= $t['email'] ?></label>
            <input type="email" id="email" name="email" class="input-field" placeholder="your@email.com" required>
        </div>

        <!-- Phone -->
        <div>
            <label for="phone" class="block font-semibold text-gray-700 mb-2"><?= $t['phone'] ?></label>
            <input type="tel" id="phone" name="phone" class="input-field" placeholder="+237612345678" required>
        </div>

        <!-- Stall Number -->
        <div>
            <label for="stall_no" class="block font-semibold text-gray-700 mb-2"><?= $t['stall_number'] ?></label>
            <input type="text" id="stall_no" name="stall_no" class="input-field" placeholder="e.g. A12, B5" required>
        </div>

        <!-- Password -->
        <div>
            <label for="password" class="block font-semibold text-gray-700 mb-2"><?= $t['password'] ?></label>
            <input type="password" id="password" name="password" class="input-field" placeholder="••••••••" required>
        </div>

        <!-- Password Confirm -->
        <div>
            <label for="password_confirm" class="block font-semibold text-gray-700 mb-2"><?= $t['password_confirm'] ?></label>
            <input type="password" id="password_confirm" name="password_confirm" class="input-field" placeholder="••••••••" required>
        </div>

        <!-- Submit Button -->
        <button type="submit" class="w-full btn-primary py-3 text-lg font-bold mt-6">
            <?= $t['register'] ?>
        </button>
    </form>

    <!-- Login Link -->
    <div class="mt-6 text-center border-t pt-6">
        <p class="text-gray-600"><?= $t['already_have_account'] ?> <a href="login.php" class="text-primary font-semibold hover:underline"><?= $t['login_here'] ?></a></p>
    </div>

    <!-- Manager Register Link -->
    <div class="mt-4 text-center text-sm">
        <p class="text-gray-600">Are you a market manager? <a href="register_manager.php" class="text-secondary font-semibold hover:underline">Register your market</a></p>
    </div>
</div>

<?php require_once '../../templates/footer.php'; ?>
