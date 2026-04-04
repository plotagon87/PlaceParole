<?php
/**
 * modules/auth/login.php
 * Login page for both sellers and managers
 */
if (session_status() === PHP_SESSION_NONE) session_start();
require_once '../../config/db.php';
require_once '../../config/lang.php';
require_once '../../config/csrf.php';

// preserve language query parameter for links on this page
$langParam = isset($_SESSION['lang']) ? '?lang=' . $_SESSION['lang'] : '';

$error = '';
$success = '';

// Check for success message from registration
if (isset($_SESSION['success'])) {
    $success = $_SESSION['success'];
    unset($_SESSION['success']); // Clear it after displaying
}

// Rate limiting: maximum 5 failed attempts before a 15-minute lockout
$max_attempts  = 5;
$lockout_time  = 15 * 60;
$attempts_key  = 'login_attempts_' . md5($_SERVER['REMOTE_ADDR']);
$lockout_key   = 'login_lockout_'  . md5($_SERVER['REMOTE_ADDR']);

// Check if the user is currently locked out
if (isset($_SESSION[$lockout_key]) && time() < $_SESSION[$lockout_key]) {
    $remaining = ceil(($_SESSION[$lockout_key] - time()) / 60);
    $error = "Too many failed login attempts. Please wait {$remaining} minute(s) before trying again.";
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_verify();
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

            // Regenerate session ID after login to prevent session fixation attacks
            // This replaces the current session ID with a new one, invalidating any previously known IDs
            session_regenerate_id(true);

            // Redirect based on role
            if ($user['role'] === 'admin') {
                header('Location: ../../modules/admin/overview.php');
            } elseif ($user['role'] === 'manager') {
                header('Location: ../../modules/complaints/list.php');
            } else {
                header('Location: ../../index.php');
            }
            exit;
        } else {
            // Failed — increment the counter
            $_SESSION[$attempts_key] = ($_SESSION[$attempts_key] ?? 0) + 1;
            
            if ($_SESSION[$attempts_key] >= $max_attempts) {
                $_SESSION[$lockout_key] = time() + $lockout_time;
                $error = "Too many failed attempts. Account locked for 15 minutes.";
            } else {
                $error = $t['error_invalid_login'];
            }
        }
    }
}

// login form on this page
$pageHasForm = true;
require_once '../../templates/header.php';
?>

<!-- ══════════════════════════════════════════════════════════════════════════
     CSS — Password Visibility Toggle
     ══════════════════════════════════════════════════════════════════════════ -->
<style>
    /* ── Password wrapper (holds input + eye icon) ───────────────────────────── */
    .password-wrapper {
        position: relative;
        display: flex;
        align-items: center;
    }

    .password-wrapper .input-field {
        padding-right: 2.75rem;   /* Make room for the eye icon button */
    }

    /* ── Eye icon toggle button ──────────────────────────────────────────────── */
    .pw-toggle {
        position: absolute;
        right: 0.75rem;
        background: none;
        border: none;
        cursor: pointer;
        padding: 0.25rem;
        display: flex;
        align-items: center;
        color: var(--muted);
        transition: color 0.2s;
        line-height: 1;
    }
    .pw-toggle:hover { color: var(--green-mid); }

    /* ── SVG eye icons ───────────────────────────────────────────────────────── */
    .pw-toggle svg { width: 20px; height: 20px; }
    .icon-eye-open  { display: block; }  /* shown when password is hidden */
    .icon-eye-shut  { display: none; }   /* shown when password is visible */

    /* When toggle is active (password revealed), swap icons */
    .pw-toggle.revealed .icon-eye-open { display: none; }
    .pw-toggle.revealed .icon-eye-shut { display: block; }
</style>

<div class="max-w-md mx-auto bg-white rounded-2xl shadow-lg p-8 mt-10">
    <h1 class="text-3xl font-bold text-primary mb-2"><?= $t['login'] ?></h1>
    <p class="text-gray-600 mb-6"><?= $t['app_tagline'] ?></p>

    <?php if ($success): ?>
        <div class="bg-green-100 text-green-700 px-4 py-3 rounded-lg mb-6 border border-green-300">
            <strong>✓ <?= $t['success'] ?>!</strong> <?= $success ?>
        </div>
    <?php endif; ?>

    <?php if ($error): ?>
        <div class="bg-red-100 text-red-700 px-4 py-3 rounded-lg mb-6 border border-red-300">
            <strong><?= $t['error'] ?>:</strong> <?= $error ?>
        </div>
    <?php endif; ?>

    <form method="POST" class="space-y-4">
        <input type="hidden" name="csrf_token" value="<?= csrf_token() ?>">
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
            <div class="password-wrapper">
                <input 
                    type="password" 
                    id="password" 
                    name="password" 
                    class="input-field" 
                    placeholder="••••••••"
                    required
                >
                <button
                    type="button"
                    class="pw-toggle"
                    id="togglePassword"
                    onclick="togglePasswordVisibility('password', this)"
                    aria-label="Toggle password visibility"
                    title="Show / Hide password"
                >
                    <!-- Eye open icon (shown when password is hidden) -->
                    <svg class="icon-eye-open" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                        <path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"/>
                        <circle cx="12" cy="12" r="3"/>
                    </svg>
                    <!-- Eye shut (crossed-out) icon (shown when password is visible) -->
                    <svg class="icon-eye-shut" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                        <path d="M17.94 17.94A10.07 10.07 0 0 1 12 20c-7 0-11-8-11-8a18.45 18.45 0 0 1 5.06-5.94"/>
                        <path d="M9.9 4.24A9.12 9.12 0 0 1 12 4c7 0 11 8 11 8a18.5 18.5 0 0 1-2.16 3.19"/>
                        <line x1="1" y1="1" x2="23" y2="23"/>
                    </svg>
                </button>
            </div>
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
            <a href="register_seller.php<?= $langParam ?>" class="text-center btn-outlined py-2">
                <?= $t['seller'] ?>
            </a>
            <a href="register_manager.php<?= $langParam ?>" class="text-center btn-secondary py-2">
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

<!-- ══════════════════════════════════════════════════════════════════════════
     JAVASCRIPT — Password Visibility Toggle
     ══════════════════════════════════════════════════════════════════════════ -->
<script>
/**
 * togglePasswordVisibility(inputId, buttonEl)
 * Switches an <input> between type="password" (hidden) and type="text" (visible).
 * Also updates button aria-label and adds/removes the 'revealed' class for styling.
 */
function togglePasswordVisibility(inputId, buttonEl) {
    const input = document.getElementById(inputId);
    if (!input) return;
    
    if (input.type === 'password') {
        // Show password
        input.type = 'text';
        buttonEl.classList.add('revealed');
        buttonEl.setAttribute('aria-label', 'Hide password');
    } else {
        // Hide password
        input.type = 'password';
        buttonEl.classList.remove('revealed');
        buttonEl.setAttribute('aria-label', 'Show password');
    }
}
</script>

<?php require_once '../../templates/footer.php'; ?>
