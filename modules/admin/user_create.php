<?php
if (session_status() === PHP_SESSION_NONE) session_start();

require_once '../../config/db.php';
require_once '../../config/auth_guard.php';
require_once '../../config/lang.php';
require_once '../../config/csrf.php';
require_once '../../config/admin_helpers.php';

admin_only();

$errors = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_verify();
    
    $name = trim(htmlspecialchars($_POST['name'] ?? ''));
    $email = trim(strtolower($_POST['email'] ?? ''));
    $phone = trim($_POST['phone'] ?? '');
    $role = $_POST['role'] ?? 'seller';
    $marketId = (int)($_POST['market_id'] ?? 0);
    $stallNo = trim($_POST['stall_no'] ?? '');
    $language = $_POST['lang'] ?? 'en';
    $password = trim($_POST['password'] ?? '');
    
    if (strlen($name) < 3) $errors[] = 'Name must be at least 3 characters';
    
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $errors[] = 'Invalid email address';
    } else {
        $checkStmt = $pdo->prepare("SELECT id FROM users WHERE email = ?");
        if ($checkStmt->execute([$email]) && $checkStmt->fetch()) {
            $errors[] = 'Email already exists';
        }
    }
    
    $phone = preg_replace('/[\s\-()]/', '', $phone);
    if (!preg_match('/^\+?[0-9]{7,15}$/', $phone)) {
        $errors[] = 'Invalid phone number';
    } else {
        $checkPhone = $pdo->prepare("SELECT id FROM users WHERE phone = ?");
        if ($checkPhone->execute([$phone]) && $checkPhone->fetch()) {
            $errors[] = 'Phone already registered';
        }
    }
    
    if ($marketId <= 0) $errors[] = 'Please select a market';
    if ($role === 'seller' && strlen($stallNo) < 1) $errors[] = 'Stall number required for sellers';
    
    if (strlen($password) < 8 || !preg_match('/[A-Z]/', $password) || !preg_match('/[a-z]/', $password) || !preg_match('/[0-9]/', $password) || !preg_match('/[!@#$%^&*]/', $password)) {
        $errors[] = 'Password must be 8+ characters with uppercase, lowercase, number, and special character';
    }
    
    if (empty($errors)) {
        try {
            $hashedPassword = password_hash($password, PASSWORD_BCRYPT, ['cost' => 12]);
            $stmt = $pdo->prepare("
                INSERT INTO users (market_id, name, email, phone, role, stall_no, password, lang, is_active, created_at)
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, 1, NOW())
            ");
            
            $stmt->execute([$marketId, $name, $email, $phone, $role, $stallNo ?: NULL, $hashedPassword, $language]);
            $userId = $pdo->lastInsertId();
            
            logAdminAction($pdo, $_SESSION['user_id'], 'user_created', 'user', $userId, [
                'name' => $name, 'email' => $email, 'role' => $role, 'market_id' => $marketId
            ]);
            
            $_SESSION['flash'] = ['success' => 'User created successfully!'];
            header('Location: ' . BASE_URL . '/modules/admin/users.php');
            exit;
        } catch (Exception $e) {
            error_log("User create error: " . $e->getMessage());
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
            <h2 class="text-3xl font-bold text-gray-900 mb-6">Create New User</h2>
            
            <?php if (!empty($errors)): ?>
            <div class="mb-6 bg-red-50 border border-red-200 rounded-lg p-4">
                <p class="font-semibold text-red-900 mb-2">⚠️ Please fix these errors:</p>
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
                    <input type="text" id="name" name="name" required value="<?= htmlspecialchars($_POST['name'] ?? '') ?>" class="input-field w-full">
                </div>
                
                <div>
                    <label for="email" class="block text-sm font-semibold text-gray-700 mb-2">Email *</label>
                    <input type="email" id="email" name="email" required value="<?= htmlspecialchars($_POST['email'] ?? '') ?>" class="input-field w-full">
                </div>
                
                <div>
                    <label for="phone" class="block text-sm font-semibold text-gray-700 mb-2">Phone *</label>
                    <input type="tel" id="phone" name="phone" required value="<?= htmlspecialchars($_POST['phone'] ?? '') ?>" placeholder="+23712345678" class="input-field w-full">
                </div>
                
                <div>
                    <label for="role" class="block text-sm font-semibold text-gray-700 mb-2">Role *</label>
                    <select id="role" name="role" required class="input-field w-full" onchange="document.getElementById('stall-field').style.display = this.value === 'seller' ? 'block' : 'none'">
                        <option value="seller">Seller</option>
                        <option value="manager">Manager</option>
                    </select>
                </div>
                
                <div>
                    <label for="market_id" class="block text-sm font-semibold text-gray-700 mb-2">Market *</label>
                    <select id="market_id" name="market_id" required class="input-field w-full">
                        <option value="">-- Select Market --</option>
                        <?php foreach ($markets as $m): ?>
                            <option value="<?= $m['id'] ?>"><?= htmlspecialchars($m['name']) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                
                <div id="stall-field">
                    <label for="stall_no" class="block text-sm font-semibold text-gray-700 mb-2">Stall Number *</label>
                    <input type="text" id="stall_no" name="stall_no" placeholder="e.g., A-12" class="input-field w-full">
                    <p class="text-xs text-gray-500 mt-1">Only for sellers</p>
                </div>
                
                <div>
                    <label for="password" class="block text-sm font-semibold text-gray-700 mb-2">Password *</label>
                    <input type="password" id="password" name="password" required class="input-field w-full" placeholder="Min 8 chars, uppercase, lowercase, number, special">
                    <div class="text-xs text-gray-600 mt-2 space-y-1">
                        <p>Must contain:</p>
                        <ul class="list-disc list-inside ml-2">
                            <li>At least 8 characters</li>
                            <li>Uppercase letter (A-Z)</li>
                            <li>Lowercase letter (a-z)</li>
                            <li>Number (0-9)</li>
                            <li>Special character (!@#$%^&*)</li>
                        </ul>
                    </div>
                </div>
                
                <div>
                    <label for="lang" class="block text-sm font-semibold text-gray-700 mb-2">Language Preference</label>
                    <select id="lang" name="lang" class="input-field w-full">
                        <option value="en">English</option>
                        <option value="fr">Français</option>
                    </select>
                </div>
                
                <div class="flex gap-3">
                    <button type="submit" class="btn-primary flex-1">✅ Create User</button>
                    <a href="<?= BASE_URL ?>/modules/admin/users.php" class="btn-secondary flex-1 text-center">❌ Cancel</a>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
document.getElementById('role').dispatchEvent(new Event('change'));
</script>

<?php require_once '../../templates/footer.php'; ?>
