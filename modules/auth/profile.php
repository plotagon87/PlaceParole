<?php
/**
 * modules/auth/profile.php
 * Manager and Seller profile & settings page.
 * Allows users to update their personal details and managers to update market info.
 */
require_once '../../config/auth_guard.php';

$pageHasForm = true;
require_once '../../templates/header.php';
require_once '../../config/db.php';

$success = '';
$errors  = [];

// Fetch the current logged-in user's data from the database
$userStmt = $pdo->prepare("SELECT * FROM users WHERE id = ? LIMIT 1");
$userStmt->execute([$_SESSION['user_id']]);
$user = $userStmt->fetch();

// If manager, also fetch their market data
$market = null;
if (isset($_SESSION['role']) && $_SESSION['role'] === 'manager') {
    $marketStmt = $pdo->prepare("SELECT * FROM markets WHERE id = ? LIMIT 1");
    $marketStmt->execute([$_SESSION['market_id']]);
    $market = $marketStmt->fetch();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_verify();

    $name  = trim(htmlspecialchars($_POST['name']  ?? ''));
    $phone = trim(htmlspecialchars($_POST['phone'] ?? ''));
    $email = trim(strtolower($_POST['email']       ?? ''));

    // Validate inputs
    if (strlen($name) < 3)                            $errors[] = 'Name must be at least 3 characters.';
    if (!filter_var($email, FILTER_VALIDATE_EMAIL))   $errors[] = 'Please enter a valid email address.';

    // Check if email is taken by a DIFFERENT user (not the current user)
    $emailCheck = $pdo->prepare("SELECT id FROM users WHERE email = ? AND id != ? LIMIT 1");
    $emailCheck->execute([$email, $_SESSION['user_id']]);
    if ($emailCheck->fetch())                         $errors[] = 'That email is already used by another account.';

    if (empty($errors)) {
        // Update the user's personal details
        $pdo->prepare("UPDATE users SET name = ?, phone = ?, email = ? WHERE id = ?")
            ->execute([$name, $phone, $email, $_SESSION['user_id']]);

        // Update session name so navigation bar reflects change immediately
        $_SESSION['name'] = $name;

        // If manager, also update market details
        if (isset($_SESSION['role']) && $_SESSION['role'] === 'manager' && isset($_POST['market_name'])) {
            $market_name     = trim(htmlspecialchars($_POST['market_name']     ?? ''));
            $market_location = trim(htmlspecialchars($_POST['market_location'] ?? ''));

            if (strlen($market_name) >= 3) {
                $pdo->prepare("UPDATE markets SET name = ?, location = ? WHERE id = ?")
                    ->execute([$market_name, $market_location, $_SESSION['market_id']]);
            }
        }

        $success = 'Your profile has been updated successfully.';

        // Refresh data from database after update
        $userStmt->execute([$_SESSION['user_id']]);
        $user = $userStmt->fetch();
        if (isset($_SESSION['role']) && $_SESSION['role'] === 'manager') {
            $marketStmt->execute([$_SESSION['market_id']]);
            $market = $marketStmt->fetch();
        }
    }
}
?>

<div class="max-w-lg mx-auto">
    <h1 class="text-3xl font-bold text-primary mb-6">👤 My Profile</h1>

    <?php if ($success): ?>
        <div class="alert-success mb-6">✅ <?= $success ?></div>
    <?php endif; ?>

    <?php if (!empty($errors)): ?>
        <div class="alert-error mb-6">
            <?php foreach ($errors as $e): ?><p>• <?= $e ?></p><?php endforeach; ?>
        </div>
    <?php endif; ?>

    <form method="POST" class="space-y-4">
        <input type="hidden" name="csrf_token" value="<?= csrf_token() ?>">

        <?php if ($_SESSION['role'] === 'manager' && $market): ?>
            <div class="card mb-2">
                <h2 class="font-bold text-gray-700 mb-4">🏪 Market Details</h2>
                <label class="block font-semibold text-gray-700 mb-2">Market Name</label>
                <input type="text" name="market_name" class="input-field"
                       value="<?= htmlspecialchars($market['name']) ?>" required>
                <label class="block font-semibold text-gray-700 mb-2 mt-4">Market Location</label>
                <input type="text" name="market_location" class="input-field"
                       value="<?= htmlspecialchars($market['location']) ?>">
            </div>
        <?php endif; ?>

        <div class="card">
            <h2 class="font-bold text-gray-700 mb-4">👤 Personal Details</h2>
            <label class="block font-semibold text-gray-700 mb-2">Full Name</label>
            <input type="text" name="name" class="input-field"
                   value="<?= htmlspecialchars($user['name']) ?>" required>

            <label class="block font-semibold text-gray-700 mb-2 mt-4">Email Address</label>
            <input type="email" name="email" class="input-field"
                   value="<?= htmlspecialchars($user['email']) ?>" required>

            <label class="block font-semibold text-gray-700 mb-2 mt-4">Phone Number</label>
            <input type="tel" name="phone" class="input-field"
                   value="<?= htmlspecialchars($user['phone'] ?? '') ?>">
        </div>

        <button type="submit" class="w-full btn-primary py-3 font-bold">
            💾 Save Changes
        </button>
    </form>
</div>

<?php require_once '../../templates/footer.php'; ?>
