<?php
if (session_status() === PHP_SESSION_NONE) session_start();

require_once '../../config/db.php';
require_once '../../config/auth_guard.php';
require_once '../../config/lang.php';
require_once '../../config/csrf.php';
require_once '../../config/admin_helpers.php';

admin_only();

$userId = (int)($_GET['id'] ?? 0);
$errors = [];
$user = null;

try {
    $stmt = $pdo->prepare("SELECT * FROM users WHERE id = ?");
    $stmt->execute([$userId]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$user) {
        die('<div class="alert-error">User not found</div>');
    }
} catch (Exception $e) {
    die('<div class="alert-error">Error loading user</div>');
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_verify();
    
    $name = trim(htmlspecialchars($_POST['name'] ?? ''));
    $email = trim(strtolower($_POST['email'] ?? ''));
    $phone = trim($_POST['phone'] ?? '');
    $role = $_POST['role'] ?? $user['role'];
    $marketId = (int)($_POST['market_id'] ?? $user['market_id']);
    $stallNo = trim($_POST['stall_no'] ?? '');
    $resetPassword = isset($_POST['reset_password']);
    $newPassword = trim($_POST['new_password'] ?? '');
    
    if (strlen($name) < 3) $errors[] = 'Name must be at least 3 characters';
    
    if (filter_var($email, FILTER_VALIDATE_EMAIL) === false) {
        $errors[] = 'Invalid email address';
    } elseif ($email !== $user['email']) {
        $checkStmt = $pdo->prepare("SELECT id FROM users WHERE email = ? AND id != ?");
        if ($checkStmt->execute([$email, $userId]) && $checkStmt->fetch()) {
            $errors[] = 'Email already exists';
        }
    }
    
    $phone = preg_replace('/[\s\-()]/', '', $phone);
    if (!preg_match('/^\+?[0-9]{7,15}$/', $phone)) {
        $errors[] = 'Invalid phone number';
    } elseif ($phone !== $user['phone']) {
        $checkPhone = $pdo->prepare("SELECT id FROM users WHERE phone = ? AND id != ?");
        if ($checkPhone->execute([$phone, $userId]) && $checkPhone->fetch()) {
            $errors[] = 'Phone already registered';
        }
    }
    
    if ($resetPassword && $newPassword) {
        if (strlen($newPassword) < 8 || !preg_match('/[A-Z]/', $newPassword) || !preg_match('/[a-z]/', $newPassword) || !preg_match('/[0-9]/', $newPassword) || !preg_match('/[!@#$%^&*]/', $newPassword)) {
            $errors[] = 'Password must be 8+ characters with uppercase, lowercase, number, and special character';
        }
    }
    
    if (empty($errors)) {
        try {
            $oldUser = $user;
            $updateFields = [];
            $updateParams = [];
            
            if ($name !== $user['name']) {
                $updateFields[] = 'name = ?';
                $updateParams[] = $name;
            }
            if ($email !== $user['email']) {
                $updateFields[] = 'email = ?';
                $updateParams[] = $email;
            }
            if ($phone !== $user['phone']) {
                $updateFields[] = 'phone = ?';
                $updateParams[] = $phone;
            }
            if ($role !== $user['role']) {
                $updateFields[] = 'role = ?';
                $updateParams[] = $role;
            }
            if ($marketId !== (int)$user['market_id']) {
                $updateFields[] = 'market_id = ?';
                $updateParams[] = $marketId;
            }
            if ($resetPassword && $newPassword) {
                $updateFields[] = 'password = ?';
                $updateParams[] = password_hash($newPassword, PASSWORD_BCRYPT, ['cost' => 12]);
            }
            if ($stallNo !== $user['stall_no']) {
                $updateFields[] = 'stall_no = ?';
                $updateParams[] = $stallNo ?: NULL;
            }
            
            if (!empty($updateFields)) {
                $updateParams[] = $userId;
                $stmt = $pdo->prepare("UPDATE users SET " . implode(', ', $updateFields) . " WHERE id = ?");
                $stmt->execute($updateParams);
                
                $changes = [];
                if ($name !== $oldUser['name']) $changes['name'] = ['old' => $oldUser['name'], 'new' => $name];
                if ($email !== $oldUser['email']) $changes['email'] = ['old' => $oldUser['email'], 'new' => $email];
                if ($phone !== $oldUser['phone']) $changes['phone'] = ['old' => $oldUser['phone'], 'new' => $phone];
                if ($role !== $oldUser['role']) $changes['role'] = ['old' => $oldUser['role'], 'new' => $role];
                if ($marketId !== (int)$oldUser['market_id']) $changes['market_id'] = ['old' => (int)$oldUser['market_id'], 'new' => $marketId];
                if ($resetPassword && $newPassword) $changes['password_reset'] = 'true';
                
                logAdminAction($pdo, $_SESSION['user_id'], 'user_updated', 'user', $userId, $changes);
            }
            
            $_SESSION['flash'] = ['success' => 'User updated successfully!'];
            header('Location: ' . BASE_URL . '/modules/admin/users.php');
            exit;
        } catch (Exception $e) {
            error_log("User update error: " . $e->getMessage());
            $errors[] = 'Database error';
        }
    }
}

try {
    $stmt = $pdo->prepare("SELECT id, name FROM markets ORDER BY name");
    $stmt->execute();
    $markets = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (Exception $e) {
    $markets = [];
}

require_once '../../templates/header.php';
?>

<div class="min-h-screen bg-gray-50">
    <div class="max-w-4xl mx-auto px-6 py-6">
        <div class="bg-white rounded-lg shadow-sm border border-gray-200 p-6">
            <h2 class="text-3xl font-bold text-gray-900 mb-6">Edit User: <?= htmlspecialchars($user['name']) ?></h2>
            
            <?php if (!empty($errors)): ?>
            <div class="mb-6 bg-red-50 border border-red-200 rounded-lg p-4">
                <p class="font-semibold text-red-900 mb-2">⚠️ Errors:</p>
                <ul class="list-disc list-inside text-red-700 text-sm space-y-1">
                    <?php foreach ($errors as $error): ?>
                        <li><?= htmlspecialchars($error) ?></li>
                    <?php endforeach; ?>
                </ul>
            </div>
            <?php endif; ?>
            
            <form method="POST" class="space-y-6">
                <input type="hidden" name="csrf_token" value="<?= csrf_token() ?>">
                
                <div>
                    <label for="name" class="block text-sm font-semibold text-gray-700 mb-2">Full Name *</label>
                    <input type="text" id="name" name="name" required value="<?= htmlspecialchars($user['name']) ?>" class="input-field w-full">
                </div>
                
                <div>
                    <label for="email" class="block text-sm font-semibold text-gray-700 mb-2">Email *</label>
                    <input type="email" id="email" name="email" required value="<?= htmlspecialchars($user['email']) ?>" class="input-field w-full">
                </div>
                
                <div>
                    <label for="phone" class="block text-sm font-semibold text-gray-700 mb-2">Phone *</label>
                    <input type="tel" id="phone" name="phone" required value="<?= htmlspecialchars($user['phone']) ?>" class="input-field w-full">
                </div>
                
                <div>
                    <label for="role" class="block text-sm font-semibold text-gray-700 mb-2">Role</label>
                    <select id="role" name="role" class="input-field w-full" <?= $_SESSION['user_id'] === $userId ? 'disabled' : '' ?>>
                        <option value="seller" <?= $user['role'] === 'seller' ? 'selected' : '' ?>>Seller</option>
                        <option value="manager" <?= $user['role'] === 'manager' ? 'selected' : '' ?>>Manager</option>
                        <option value="admin" <?= $user['role'] === 'admin' ? 'selected' : '' ?>>Admin</option>
                    </select>
                    <?php if ($_SESSION['user_id'] === $userId): ?>
                        <p class="text-xs text-gray-600 mt-1">You cannot change your own role</p>
                    <?php endif; ?>
                </div>
                
                <div>
                    <label for="market_id" class="block text-sm font-semibold text-gray-700 mb-2">Market</label>
                    <select id="market_id" name="market_id" class="input-field w-full">
                        <?php foreach ($markets as $m): ?>
                            <option value="<?= $m['id'] ?>" <?= $user['market_id'] == $m['id'] ? 'selected' : '' ?>>
                                <?= htmlspecialchars($m['name']) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                    <?php if ($user['role'] === 'seller'): ?>
                        <p class="text-xs text-yellow-600 mt-1">⚠️ Changing market will not move their complaints</p>
                    <?php endif; ?>
                </div>
                
                <?php if ($user['role'] === 'seller'): ?>
                <div>
                    <label for="stall_no" class="block text-sm font-semibold text-gray-700 mb-2">Stall Number</label>
                    <input type="text" id="stall_no" name="stall_no" value="<?= htmlspecialchars($user['stall_no'] ?? '') ?>" class="input-field w-full">
                </div>
                <?php endif; ?>
                
                <div class="border-t pt-6">
                    <label class="flex items-center gap-3 cursor-pointer mb-4">
                        <input type="checkbox" id="reset_password" name="reset_password" class="w-4 h-4">
                        <span class="text-sm font-semibold text-gray-700">Reset password?</span>
                    </label>
                    
                    <div id="password-section" style="display: none;" class="space-y-4">
                        <div>
                            <label for="new_password" class="block text-sm font-semibold text-gray-700 mb-2">New Password</label>
                            <input type="password" id="new_password" name="new_password" class="input-field w-full">
                            <div class="text-xs text-gray-600 mt-2 space-y-1">
                                <p>Must contain: 8+ characters, uppercase, lowercase, number, special char</p>
                            </div>
                        </div>
                    </div>
                </div>
                
                <div class="flex gap-3">
                    <button type="submit" class="btn-primary flex-1">✅ Save Changes</button>
                    <a href="<?= BASE_URL ?>/modules/admin/users.php" class="btn-secondary flex-1 text-center">❌ Cancel</a>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
document.getElementById('reset_password').addEventListener('change', function() {
    document.getElementById('password-section').style.display = this.checked ? 'block' : 'none';
});
</script>

<?php require_once '../../templates/footer.php'; ?>
