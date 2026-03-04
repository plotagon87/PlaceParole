<?php
/**
 * modules/auth/login.php
 * Login page for both sellers and managers
 */
require_once '../../templates/header.php';
require_once '../../config/db.php';

$error = '';
$success = false;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email    = trim($_POST['email']    ?? '');
    $password = trim($_POST['password'] ?? '');

    if (!$email || !$password) {
        $error = $t['error_required'];
    } else {
        // Look up the user by email
        $stmt = $pdo->prepare("SELECT * FROM users WHERE email = ? LIMIT 1");
        $stmt->execute([$email]);
        $user = $stmt->fetch();

        // Verify password using password_verify()
        // password_verify() safely compares the plain text password with the hashed password stored in the database
        if ($user && password_verify($password, $user['password'])) {
            // Login successful — store user data in session
            $_SESSION['user_id']   = $user['id'];
            $_SESSION['role']      = $user['role'];
            $_SESSION['market_id'] = $user['market_id'];
            $_SESSION['name']      = $user['name'];
            $_SESSION['lang']      = $user['lang'] ?? 'en';

            // Redirect based on role
            if ($user['role'] === 'manager') {
                header('Location: ../../modules/complaints/list.php');
            } else {
                header('Location: ../../index.php');
            }
            exit;
        } else {
            $error = $t['error_invalid_login'];
        }
    }
}
?>

<div class="max-w-md mx-auto bg-white rounded-2xl shadow-lg p-8 mt-10">
    <h1 class="text-3xl font-bold text-primary mb-2"><?= $t['login'] ?></h1>
    <p class="text-gray-600 mb-6"><?= $t['app_tagline'] ?></p>

    <?php if ($error): ?>
        <div class="bg-red-100 text-red-700 px-4 py-3 rounded-lg mb-6 border border-red-300">
            <strong><?= $t['error'] ?>:</strong> <?= $error ?>
        </div>
    <?php endif; ?>

    <form method="POST" class="space-y-4">
        <!-- Email Input -->
        <div>
            <label for="email" class="block font-semibold text-gray-700 mb-2"><?= $t['email'] ?></label>
            <input 
                type="email" 
                id="email" 
                name="email" 
                class="input-field" 
                placeholder="user@example.com"
                required 
                autofocus
            >
        </div>

        <!-- Password Input -->
        <div>
            <label for="password" class="block font-semibold text-gray-700 mb-2"><?= $t['password'] ?></label>
            <input 
                type="password" 
                id="password" 
                name="password" 
                class="input-field" 
                placeholder="••••••••"
                required
            >
        </div>

        <!-- Submit Button -->
        <button type="submit" class="w-full btn-primary py-3 text-lg font-bold">
            <?= $t['login'] ?>
        </button>
    </form>

    <!-- Registration Links -->
    <div class="mt-8 space-y-3 border-t pt-6">
        <p class="text-center text-gray-600"><?= $t['no_account'] ?></p>
        <div class="grid grid-cols-2 gap-3">
            <a href="register_seller.php" class="text-center btn-outlined py-2">
                <?= $t['seller'] ?>
            </a>
            <a href="register_manager.php" class="text-center btn-secondary py-2">
                <?= $t['manager'] ?>
            </a>
        </div>
    </div>

    <!-- Track Complaint Link (for non-registered users) -->
    <div class="mt-6 pt-6 border-t text-center">
        <p class="text-sm text-gray-600 mb-3"><?= $t['keep_ref_code'] ?></p>
        <a href="../complaints/track.php" class="text-primary font-semibold hover:underline">
            → <?= $t['track_complaint'] ?>
        </a>
    </div>
</div>

<?php require_once '../../templates/footer.php'; ?>
