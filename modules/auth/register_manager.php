<?php
/**
 * modules/auth/register_manager.php
 * A manager registers a new market + their account simultaneously
 */
require_once '../../templates/header.php';
require_once '../../config/db.php';

$errors = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_verify();
    $market_name     = trim($_POST['market_name']     ?? '');
    $market_location = trim($_POST['market_location'] ?? '');
    $name            = trim($_POST['name']            ?? '');
    $email           = trim($_POST['email']           ?? '');
    $phone           = trim($_POST['phone']           ?? '');
    $password        = trim($_POST['password']        ?? '');
    $password_confirm= trim($_POST['password_confirm']?? '');

    // Validation
    if (!$market_name)      $errors[] = "Market name is required.";
    if (!$market_location)  $errors[] = "Market location is required.";
    if (!$name)             $errors[] = $t['error_required'];
    if (!$email)            $errors[] = $t['error_required'];
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) $errors[] = $t['error_invalid_email'];
    if (!$phone)            $errors[] = $t['error_required'];
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

    // If no errors, create the market and user
    if (!$errors) {
        try {
            $pdo->beginTransaction(); // Start a transaction — if anything fails, both inserts are rolled back

            // Step 1: Insert the market
            $stmt = $pdo->prepare("INSERT INTO markets (name, location) VALUES (?, ?)");
            $stmt->execute([$market_name, $market_location]);
            $market_id = $pdo->lastInsertId(); // Get the ID of the newly inserted market

            // Step 2: Insert the manager user, linked to the market
            $password_hash = password_hash($password, PASSWORD_DEFAULT); // Hash the password
            // password_hash() creates a one-way encrypted hash of the password — impossible to decrypt back to original
            $stmt = $pdo->prepare("
                INSERT INTO users (market_id, name, email, phone, role, password, lang)
                VALUES (?, ?, ?, ?, 'manager', ?, ?)
            ");
            $stmt->execute([$market_id, $name, $email, $phone, $password_hash, $_SESSION['lang']]);

            $pdo->commit(); // Commit the transaction — both inserts are now permanent

            // Redirect to login
            $_SESSION['success'] = $t['register_success'];
            header('Location: login.php');
            exit;

        } catch (Exception $e) {
            $pdo->rollBack(); // Rollback if anything fails
            $errors[] = "Registration failed: " . $e->getMessage();
        }
    }
}
?>

<div class="max-w-lg mx-auto bg-white rounded-2xl shadow-lg p-8 mt-10">
    <h1 class="text-3xl font-bold text-primary mb-2"><?= $t['register_market'] ?></h1>
    <p class="text-gray-600 mb-6"><?= $t['manager'] ?> <?= $t['register'] ?></p>

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
        <!-- Market Section -->
        <div class="bg-gray-50 p-4 rounded-lg border border-gray-200">
            <h2 class="font-bold text-gray-700 mb-3">📍 <?= $t['register_market'] ?></h2>
            
            <div>
                <label for="market_name" class="block font-semibold text-gray-700 mb-2"><?= $t['market_name'] ?></label>
                <input type="text" id="market_name" name="market_name" class="input-field" placeholder="e.g. Marché Mokolo" required>
            </div>

            <div class="mt-3">
                <label for="market_location" class="block font-semibold text-gray-700 mb-2"><?= $t['market_location'] ?></label>
                <input type="text" id="market_location" name="market_location" class="input-field" placeholder="e.g. Yaoundé, Centre Region" required>
            </div>
        </div>

        <!-- Manager Section -->
        <div class="bg-gray-50 p-4 rounded-lg border border-gray-200">
            <h2 class="font-bold text-gray-700 mb-3">👤 <?= $t['manager'] ?> <?= $t['register'] ?></h2>

            <div>
                <label for="name" class="block font-semibold text-gray-700 mb-2"><?= $t['name'] ?></label>
                <input type="text" id="name" name="name" class="input-field" placeholder="Your full name" required>
            </div>

            <div class="mt-3">
                <label for="email" class="block font-semibold text-gray-700 mb-2"><?= $t['email'] ?></label>
                <input type="email" id="email" name="email" class="input-field" placeholder="your@email.com" required>
            </div>

            <div class="mt-3">
                <label for="phone" class="block font-semibold text-gray-700 mb-2"><?= $t['phone'] ?></label>
                <input type="tel" id="phone" name="phone" class="input-field" placeholder="+237612345678" required>
            </div>

            <div class="mt-3">
                <label for="password" class="block font-semibold text-gray-700 mb-2"><?= $t['password'] ?></label>
                <input type="password" id="password" name="password" class="input-field" placeholder="••••••••" required>
            </div>

            <div class="mt-3">
                <label for="password_confirm" class="block font-semibold text-gray-700 mb-2"><?= $t['password_confirm'] ?></label>
                <input type="password" id="password_confirm" name="password_confirm" class="input-field" placeholder="••••••••" required>
            </div>
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
</div>

<?php require_once '../../templates/footer.php'; ?>
