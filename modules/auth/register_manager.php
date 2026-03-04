<?php
/**
 * modules/auth/register_manager.php
 *
 * SECURITY FEATURES IN THIS VERSION:
 * ───────────────────────────────────
 * 1. CSRF token enforced on every POST submission.
 * 2. All user inputs sanitised (trim + htmlspecialchars) before processing.
 * 3. Passwords hashed with bcrypt via password_hash(PASSWORD_BCRYPT, cost=12).
 *    bcrypt = an industry-standard, adaptive, salted hashing algorithm designed
 *    for storing passwords. It is intentionally slow, deterring brute-force attacks.
 * 4. Duplicate email / phone detection with clear user feedback.
 * 5. PDO transaction: both the market INSERT and user INSERT succeed or both fail.
 *    This prevents a "ghost market" with no manager from being created.
 * 6. Password strength enforced SERVER-SIDE (client-side JS is cosmetic only).
 */

// Enable error reporting for troubleshooting registration rendering issues
ini_set('display_errors', 1);
error_reporting(E_ALL);

require_once '../../templates/header.php';   // Starts session, loads $t (translations), renders nav
require_once '../../config/db.php';           // Provides $pdo — the PDO database connection

// ── Initialise state variables ────────────────────────────────────────────────
$errors    = [];   // Collects validation errors to display above the form
$oldValues = [];   // Caches submitted values so the form repopulates after an error

// ── Handle form submission ────────────────────────────────────────────────────
if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    // ── CSRF protection ───────────────────────────────────────────────────────
    // csrf_verify() is defined in config/auth_guard.php
    // It compares the hidden form token with $_SESSION['csrf_token']
    csrf_verify();

    // ── Collect and sanitise inputs ───────────────────────────────────────────
    // trim()             — strips surrounding whitespace
    // htmlspecialchars() — encodes < > & " ' to prevent XSS injection
    // strtolower()       — normalises email addresses to avoid case-sensitive duplicates
    $market_name      = trim(htmlspecialchars($_POST['market_name']     ?? ''));
    $market_location  = trim(htmlspecialchars($_POST['market_location'] ?? ''));
    $name             = trim(htmlspecialchars($_POST['name']            ?? ''));
    $email            = trim(strtolower(      $_POST['email']           ?? ''));
    $phone            = trim(htmlspecialchars($_POST['phone']           ?? ''));
    $password         =                       $_POST['password']        ?? '';  // Raw — bcrypt handles special chars
    $password_confirm =                       $_POST['password_confirm']?? '';

    $oldValues = compact('market_name', 'market_location', 'name', 'email', 'phone');

    // ── Validate: Market name ─────────────────────────────────────────────────
    if (empty($market_name)) {
        $errors[] = 'Market name is required.';
    } elseif (strlen($market_name) < 3) {
        $errors[] = 'Market name must be at least 3 characters long.';
    }

    // ── Validate: Market location ─────────────────────────────────────────────
    if (empty($market_location)) {
        $errors[] = 'Market location / city is required.';
    }

    // ── Validate: Manager full name ───────────────────────────────────────────
    if (empty($name)) {
        $errors[] = $t['error_required'] . ' (' . $t['name'] . ')';
    } elseif (strlen($name) < 3) {
        $errors[] = 'Full name must be at least 3 characters long.';
    }

    // ── Validate: Email address ───────────────────────────────────────────────
    // FILTER_VALIDATE_EMAIL uses PHP's built-in RFC 5321-compliant email validator
    if (empty($email) || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $errors[] = $t['error_invalid_email'];
    }

    // ── Validate: Phone number ────────────────────────────────────────────────
    // Allows optional + prefix, digits only, 7-15 characters
    // preg_replace removes spaces, dashes, parentheses before the pattern check
    if (empty($phone)) {
        $errors[] = $t['error_required'] . ' (' . $t['phone'] . ')';
    } elseif (!preg_match('/^\+?[0-9]{7,15}$/', preg_replace('/[\s\-()]/', '', $phone))) {
        $errors[] = 'Please enter a valid phone number (7–15 digits, international format accepted).';
    }

    // ── Validate: Password strength (SERVER-SIDE authoritative check) ─────────
    $passwordErrors = validateManagerPasswordStrength($password);
    if (!empty($passwordErrors)) {
        $errors = array_merge($errors, $passwordErrors);
    }

    // ── Validate: Password confirmation ──────────────────────────────────────
    if ($password !== $password_confirm) {
        $errors[] = $t['error_password_match'];
    }

    // ── Check for duplicate email / phone ────────────────────────────────────
    if (empty($errors)) {
        $stmt = $pdo->prepare("SELECT id FROM users WHERE email = ? LIMIT 1");
        $stmt->execute([$email]);
        if ($stmt->fetch()) { $errors[] = $t['error_email_exists']; }

        $stmt = $pdo->prepare("SELECT id FROM users WHERE phone = ? LIMIT 1");
        $stmt->execute([$phone]);
        if ($stmt->fetch()) { $errors[] = $t['error_phone_exists']; }
    }

    // ── All validation passed — insert market + manager ───────────────────────
    if (empty($errors)) {
        try {
            // beginTransaction() — starts an atomic block
            // Both INSERTs will only be saved permanently when commit() is called
            // If anything goes wrong, rollBack() undoes both to prevent data inconsistency
            $pdo->beginTransaction();

            // Step 1: Create the market record
            $stmt = $pdo->prepare("INSERT INTO markets (name, location) VALUES (?, ?)");
            $stmt->execute([$market_name, $market_location]);
            // lastInsertId() returns the auto-generated primary key of the row just inserted
            $marketId = (int) $pdo->lastInsertId();

            // Step 2: Hash the password and create the manager user
            // PASSWORD_BCRYPT — uses the bcrypt hashing algorithm
            // cost 12 — each unit doubles computation time (12 is the recommended minimum for 2025)
            $passwordHash = password_hash($password, PASSWORD_BCRYPT, ['cost' => 12]);

            $stmt = $pdo->prepare("
                INSERT INTO users
                    (market_id, name, email, phone, role, password, lang)
                VALUES
                    (?, ?, ?, ?, 'manager', ?, ?)
            ");
            $stmt->execute([$marketId, $name, $email, $phone, $passwordHash, $_SESSION['lang']]);

            // Commit both inserts permanently
            $pdo->commit();

            // Flash success message to display on login page
            $_SESSION['success'] = $t['register_success'];
            header('Location: login.php');
            exit;

        } catch (PDOException $e) {
            // Something failed — undo both inserts
            $pdo->rollBack();
            error_log('Manager registration error: ' . $e->getMessage());
            $errors[] = 'Registration failed due to a server error. Please try again.';
        }
    }
}

/**
 * validateManagerPasswordStrength(string $password): array
 * Server-side authority check — mirrors the JavaScript criteria exactly.
 */
function validateManagerPasswordStrength(string $password): array
{
    $e = [];
    if (strlen($password) < 8)                                                                      $e[] = 'Password must be at least 8 characters long.';
    if (!preg_match('/[A-Z]/', $password))                                                          $e[] = 'Password must contain at least one uppercase letter (A–Z).';
    if (!preg_match('/[a-z]/', $password))                                                          $e[] = 'Password must contain at least one lowercase letter (a–z).';
    if (!preg_match('/\d/', $password))                                                             $e[] = 'Password must contain at least one number (0–9).';
    if (!preg_match('/[@$!%*?&#^()_\-+=\[\]{};\':",.<>\/\\\\|`~]/', $password))                    $e[] = 'Password must contain at least one special character (e.g. @, #, $, !).';
    return $e;
}
?>

<!-- ══════════════════════════════════════════════════════════════════════════
     STYLES
     ══════════════════════════════════════════════════════════════════════════ -->
<style>
    @import url('https://fonts.googleapis.com/css2?family=Outfit:wght@300;400;500;600;700;800&display=swap');

    :root {
        --green-dark:   #14532d;
        --green-mid:    #16a34a;
        --green-light:  #4ade80;
        --orange:       #ea580c;
        --bg:           #fff7ed;     /* Warm off-white — slightly different from seller page */
        --card-bg:      #ffffff;
        --text:         #111827;
        --muted:        #6b7280;
        --border:       #fed7aa;
        --error-bg:     #fff1f2;
        --error-border: #fda4af;
        --error-text:   #9f1239;

        --strength-empty:  #e5e7eb;
        --strength-weak:   #ef4444;
        --strength-fair:   #f97316;
        --strength-good:   #eab308;
        --strength-strong: #22c55e;
    }

    * { font-family: 'Outfit', sans-serif; box-sizing: border-box; }
    body { background: var(--bg); }

    /* ── Card ── */
    .auth-wrapper {
        min-height: 100vh;
        display: flex;
        align-items: flex-start;
        justify-content: center;
        padding: 2rem 1rem 4rem;
    }

    .auth-card {
        background: var(--card-bg);
        border: 1px solid var(--border);
        border-radius: 1.5rem;
        padding: 2.5rem 2rem;
        width: 100%;
        max-width: 560px;
        box-shadow: 0 20px 60px rgba(234, 88, 12, 0.07),
                    0 4px 16px rgba(0,0,0,0.04);
        animation: cardIn 0.4s cubic-bezier(0.34, 1.56, 0.64, 1) both;
    }

    @keyframes cardIn {
        from { opacity: 0; transform: translateY(24px) scale(0.98); }
        to   { opacity: 1; transform: translateY(0)   scale(1); }
    }

    .role-badge {
        display: inline-flex;
        align-items: center;
        gap: 0.4rem;
        background: #fff7ed;
        border: 1px solid var(--border);
        color: var(--orange);
        font-size: 0.7rem;
        font-weight: 700;
        letter-spacing: 0.08em;
        text-transform: uppercase;
        padding: 0.3rem 0.75rem;
        border-radius: 999px;
        margin-bottom: 0.75rem;
    }

    /* ── Section box: Market / Manager ── */
    .section-box {
        background: #fafafa;
        border: 1px solid #f0f0f0;
        border-radius: 0.875rem;
        padding: 1.25rem 1rem;
        margin-bottom: 1.25rem;
    }

    .section-box-title {
        font-size: 0.8rem;
        font-weight: 700;
        text-transform: uppercase;
        letter-spacing: 0.07em;
        color: var(--muted);
        margin-bottom: 0.875rem;
        display: flex;
        align-items: center;
        gap: 0.4rem;
    }

    /* ── Input fields ── */
    .form-label {
        display: block;
        font-weight: 600;
        font-size: 0.875rem;
        color: var(--text);
        margin-bottom: 0.4rem;
    }

    .form-input {
        width: 100%;
        border: 1.5px solid #e5e7eb;
        border-radius: 0.6rem;
        padding: 0.65rem 0.875rem;
        font-size: 0.95rem;
        font-family: 'Outfit', sans-serif;
        color: var(--text);
        background: #ffffff;
        transition: border-color 0.2s, box-shadow 0.2s;
        outline: none;
        margin-bottom: 0.875rem;
    }

    .form-input:focus {
        border-color: var(--orange);
        box-shadow: 0 0 0 3px rgba(234, 88, 12, 0.12);
    }

    .form-row { display: grid; grid-template-columns: 1fr 1fr; gap: 0.75rem; }

    /* ── Password wrapper + Eye toggle ── */
    .password-wrapper { position: relative; display: flex; align-items: center; }
    .password-wrapper .form-input { padding-right: 2.75rem; margin-bottom: 0; }

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
    }
    .pw-toggle:hover       { color: var(--orange); }
    .pw-toggle svg         { width: 20px; height: 20px; }
    .icon-eye-open         { display: block; }
    .icon-eye-shut         { display: none; }
    .pw-toggle.revealed .icon-eye-open { display: none; }
    .pw-toggle.revealed .icon-eye-shut { display: block; }

    /* ── Strength bars ── */
    .strength-bars { display: flex; gap: 4px; margin-top: 0.5rem; }
    .strength-bar  { flex: 1; height: 5px; border-radius: 999px; background: var(--strength-empty); transition: background 0.3s ease; }

    /* ── Criteria checklist ── */
    .strength-criteria { margin-top: 0.75rem; display: grid; grid-template-columns: 1fr 1fr; gap: 0.3rem 0.75rem; }
    .criterion { display: flex; align-items: center; gap: 0.375rem; font-size: 0.78rem; color: var(--muted); transition: color 0.25s; }
    .criterion-dot { width: 14px; height: 14px; border-radius: 50%; border: 2px solid var(--muted); flex-shrink: 0; transition: background 0.25s, border-color 0.25s; display: flex; align-items: center; justify-content: center; }
    .criterion.met { color: var(--green-dark); font-weight: 600; }
    .criterion.met .criterion-dot { background: var(--green-mid); border-color: var(--green-mid); }
    .criterion.met .criterion-dot::after { content: ''; display: block; width: 4px; height: 7px; border-right: 2px solid white; border-bottom: 2px solid white; transform: rotate(45deg) translate(-1px, -1px); }

    .strength-label { font-size: 0.78rem; font-weight: 700; margin-top: 0.4rem; min-height: 1.1em; transition: color 0.3s; }

    /* ── Submit button ── */
    .btn-submit {
        width: 100%;
        padding: 0.875rem 1rem;
        background: var(--orange);
        color: #fff;
        font-size: 1rem;
        font-weight: 700;
        font-family: 'Outfit', sans-serif;
        border: none;
        border-radius: 0.75rem;
        cursor: pointer;
        transition: background 0.2s, transform 0.15s, box-shadow 0.2s;
        box-shadow: 0 4px 16px rgba(234, 88, 12, 0.25);
        letter-spacing: 0.02em;
        margin-top: 0.5rem;
    }
    .btn-submit:hover  { background: #c2410c; transform: translateY(-1px); box-shadow: 0 6px 20px rgba(234, 88, 12, 0.35); }
    .btn-submit:active { transform: translateY(0); }

    /* ── Error block ── */
    .alert-error {
        background: var(--error-bg);
        border: 1.5px solid var(--error-border);
        color: var(--error-text);
        padding: 0.875rem 1rem;
        border-radius: 0.75rem;
        font-size: 0.875rem;
        margin-bottom: 1.25rem;
        animation: alertShake 0.4s ease;
    }
    @keyframes alertShake { 0%,100%{transform:translateX(0)} 20%{transform:translateX(-6px)} 60%{transform:translateX(4px)} }
    .alert-error li { display:flex; align-items:flex-start; gap:0.4rem; margin-top:0.3rem; }

    /* ── Footer link ── */
    .auth-footer-link { text-align:center; font-size:0.875rem; color:var(--muted); margin-top:1.5rem; padding-top:1.25rem; border-top:1px solid #e5e7eb; }
    .auth-footer-link a { color:var(--orange); font-weight:600; text-decoration:none; }
    .auth-footer-link a:hover { text-decoration:underline; }

    .security-note { display:flex; align-items:center; gap:0.5rem; font-size:0.72rem; color:var(--muted); background:#f9fafb; border:1px solid #e5e7eb; border-radius:0.5rem; padding:0.5rem 0.75rem; margin-top:1rem; }

    /* ── Responsive ── */
    @media (max-width: 480px) { .form-row { grid-template-columns: 1fr; } }
</style>

<div class="auth-wrapper">
<div class="auth-card">

    <div class="role-badge">
        <span style="width:7px;height:7px;background:var(--orange);border-radius:50%;display:inline-block;"></span>
        <?= htmlspecialchars($t['manager_registration']) ?>
    </div>

    <h1 style="font-size:1.875rem;font-weight:800;color:var(--text);margin:0 0 0.25rem;">
        <?= htmlspecialchars($t['register_market']) ?>
    </h1>
    <p style="color:var(--muted);font-size:0.9rem;margin:0 0 1.5rem;">
        <?= htmlspecialchars($t['register_manager_intro']) ?>
    </p>

    <!-- Server-side error messages -->
    <?php if (!empty($errors)): ?>
        <div class="alert-error" role="alert" aria-live="polite">
            <strong>⚠ Please fix the following:</strong>
            <ul style="margin:0;padding:0;list-style:none;">
                <?php foreach ($errors as $err): ?>
                    <li><span>•</span> <?= htmlspecialchars($err) ?></li>
                <?php endforeach; ?>
            </ul>
        </div>
    <?php endif; ?>

    <form method="POST" id="registerManagerForm" novalidate>
        <input type="hidden" name="csrf_token" value="<?= csrf_token() ?>">

        <!-- ── Section 1: Market Details ──────────────────────────────── -->
        <div class="section-box">
            <p class="section-box-title">📍 Market Details</p>

            <label for="market_name" class="form-label"><?= $t['market_name'] ?> *</label>
            <input
                type="text"
                id="market_name"
                name="market_name"
                class="form-input"
                placeholder="e.g. Marché Mokolo"
                value="<?= htmlspecialchars($oldValues['market_name'] ?? '') ?>"
                required
                autocomplete="organization"
                autofocus
            >

            <label for="market_location" class="form-label"><?= $t['market_location'] ?> *</label>
            <input
                type="text"
                id="market_location"
                name="market_location"
                class="form-input"
                placeholder="e.g. Yaoundé, Centre Region"
                value="<?= htmlspecialchars($oldValues['market_location'] ?? '') ?>"
                required
                style="margin-bottom:0;"
            >
        </div>

        <!-- ── Section 2: Manager Personal Details ────────────────────── -->
        <div class="section-box">
            <p class="section-box-title">👤 Manager Details</p>

            <label for="name" class="form-label"><?= $t['name'] ?> *</label>
            <input
                type="text"
                id="name"
                name="name"
                class="form-input"
                placeholder="Your full name"
                value="<?= htmlspecialchars($oldValues['name'] ?? '') ?>"
                required
                autocomplete="name"
            >

            <label for="email" class="form-label"><?= $t['email'] ?> *</label>
            <input
                type="email"
                id="email"
                name="email"
                class="form-input"
                placeholder="manager@example.com"
                value="<?= htmlspecialchars($oldValues['email'] ?? '') ?>"
                required
                autocomplete="email"
                inputmode="email"
            >

            <label for="phone" class="form-label"><?= $t['phone'] ?> *</label>
            <input
                type="tel"
                id="phone"
                name="phone"
                class="form-input"
                placeholder="+237 6XX XXX XXX"
                value="<?= htmlspecialchars($oldValues['phone'] ?? '') ?>"
                required
                autocomplete="tel"
                inputmode="tel"
                style="margin-bottom:0;"
            >
        </div>

        <!-- ── Section 3: Password ─────────────────────────────────────── -->
        <div class="section-box">
            <p class="section-box-title">🔑 Security</p>

            <!-- Password + Eye Toggle -->
            <label for="password" class="form-label" style="margin-bottom:0.4rem;"><?= $t['password'] ?> *</label>
            <div class="password-wrapper" style="margin-bottom:0.5rem;">
                <input
                    type="password"
                    id="password"
                    name="password"
                    class="form-input"
                    placeholder="Create a strong password"
                    required
                    autocomplete="new-password"
                    aria-describedby="strengthLabel strengthCriteria"
                >
                <button
                    type="button"
                    class="pw-toggle"
                    onclick="togglePasswordVisibility('password', this)"
                    aria-label="Toggle password visibility"
                    title="Show / Hide password"
                >
                    <svg class="icon-eye-open" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                        <path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"/>
                        <circle cx="12" cy="12" r="3"/>
                    </svg>
                    <svg class="icon-eye-shut" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                        <path d="M17.94 17.94A10.07 10.07 0 0 1 12 20c-7 0-11-8-11-8a18.45 18.45 0 0 1 5.06-5.94"/>
                        <path d="M9.9 4.24A9.12 9.12 0 0 1 12 4c7 0 11 8 11 8a18.5 18.5 0 0 1-2.16 3.19"/>
                        <line x1="1" y1="1" x2="23" y2="23"/>
                    </svg>
                </button>
            </div>

            <!-- Strength Bars -->
            <div class="strength-bars" id="strengthBars" aria-hidden="true">
                <div class="strength-bar" id="bar1"></div>
                <div class="strength-bar" id="bar2"></div>
                <div class="strength-bar" id="bar3"></div>
                <div class="strength-bar" id="bar4"></div>
                <div class="strength-bar" id="bar5"></div>
            </div>
            <p class="strength-label" id="strengthLabel" aria-live="polite"></p>

            <!-- Criteria checklist -->
            <div class="strength-criteria" id="strengthCriteria">
                <span class="criterion" id="crit-length">
                    <span class="criterion-dot"></span>At least 8 characters
                </span>
                <span class="criterion" id="crit-upper">
                    <span class="criterion-dot"></span>One uppercase letter
                </span>
                <span class="criterion" id="crit-lower">
                    <span class="criterion-dot"></span>One lowercase letter
                </span>
                <span class="criterion" id="crit-number">
                    <span class="criterion-dot"></span>One number
                </span>
                <span class="criterion" id="crit-special">
                    <span class="criterion-dot"></span>One special character
                </span>
            </div>

            <!-- Confirm Password -->
            <label for="password_confirm" class="form-label" style="margin-top:1rem;display:block;">
                <?= $t['password_confirm'] ?> *
            </label>
            <div class="password-wrapper" style="margin-bottom:0.5rem;">
                <input
                    type="password"
                    id="password_confirm"
                    name="password_confirm"
                    class="form-input"
                    placeholder="Repeat your password"
                    required
                    autocomplete="new-password"
                    aria-describedby="matchHint"
                >
                <button
                    type="button"
                    class="pw-toggle"
                    onclick="togglePasswordVisibility('password_confirm', this)"
                    aria-label="Toggle confirm password visibility"
                    title="Show / Hide confirm password"
                >
                    <svg class="icon-eye-open" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                        <path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"/>
                        <circle cx="12" cy="12" r="3"/>
                    </svg>
                    <svg class="icon-eye-shut" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                        <path d="M17.94 17.94A10.07 10.07 0 0 1 12 20c-7 0-11-8-11-8a18.45 18.45 0 0 1 5.06-5.94"/>
                        <path d="M9.9 4.24A9.12 9.12 0 0 1 12 4c7 0 11 8 11 8a18.5 18.5 0 0 1-2.16 3.19"/>
                        <line x1="1" y1="1" x2="23" y2="23"/>
                    </svg>
                </button>
            </div>
            <p id="matchHint" style="font-size:0.78rem;margin-top:0.3rem;min-height:1.1em;" aria-live="polite"></p>
        </div>

        <button type="submit" class="btn-submit">
            Register Market &amp; Create Account →
        </button>

        <div class="security-note">
            <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                <path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z"/>
            </svg>
            Your password is encrypted with bcrypt (cost 12) before storage.
            Market and user records are inserted atomically — both succeed or neither does.
        </div>
    </form>

    <div class="auth-footer-link">
        <?= htmlspecialchars($t['already_have_account']) ?>
        <a href="login.php"><?= htmlspecialchars($t['login_here']) ?></a>
    </div>
    <div class="auth-footer-link" style="margin-top:0.5rem;border:none;padding:0;">
        Are you a seller?
        <a href="register_seller.php">Register as seller →</a>
    </div>

</div>
</div>


<!-- ══════════════════════════════════════════════════════════════════════════
     JAVASCRIPT — Identical logic to register_seller.php
     ══════════════════════════════════════════════════════════════════════════ -->
<script>
/* ─────────────────────────────────────────────────────────────────────────────
   togglePasswordVisibility(inputId, buttonEl)
   Switches an <input> between type="password" and type="text".
   ─────────────────────────────────────────────────────────────────────────────*/
function togglePasswordVisibility(inputId, buttonEl) {
    const input = document.getElementById(inputId);
    if (!input) return;
    if (input.type === 'password') {
        input.type = 'text';
        buttonEl.classList.add('revealed');
        buttonEl.setAttribute('aria-label', 'Hide password');
    } else {
        input.type = 'password';
        buttonEl.classList.remove('revealed');
        buttonEl.setAttribute('aria-label', 'Show password');
    }
}

/* ─────────────────────────────────────────────────────────────────────────────
   Password strength meter — evaluates 5 criteria in real-time
   ─────────────────────────────────────────────────────────────────────────────*/
(function () {
    const passwordInput = document.getElementById('password');
    const confirmInput  = document.getElementById('password_confirm');
    const bars = ['bar1','bar2','bar3','bar4','bar5'].map(id => document.getElementById(id));
    const strengthLabel = document.getElementById('strengthLabel');
    const matchHint     = document.getElementById('matchHint');

    // Five criteria objects — each has a test function and a DOM element
    const criteria = [
        { el: document.getElementById('crit-length'),  test: v => v.length >= 8 },
        { el: document.getElementById('crit-upper'),   test: v => /[A-Z]/.test(v) },
        { el: document.getElementById('crit-lower'),   test: v => /[a-z]/.test(v) },
        { el: document.getElementById('crit-number'),  test: v => /\d/.test(v) },
        { el: document.getElementById('crit-special'), test: v => /[@$!%*?&#^()_\-+=\[\]{};':",.<>\/\\|`~]/.test(v) },
    ];

    const levels = [
        { bars: 0, colour: '',                       label: '',           style: '' },
        { bars: 1, colour: 'var(--strength-weak)',   label: 'Very Weak',  style: 'color:#ef4444' },
        { bars: 2, colour: 'var(--strength-weak)',   label: 'Weak',       style: 'color:#ef4444' },
        { bars: 3, colour: 'var(--strength-fair)',   label: 'Fair',       style: 'color:#f97316' },
        { bars: 4, colour: 'var(--strength-good)',   label: 'Good',       style: 'color:#eab308' },
        { bars: 5, colour: 'var(--strength-strong)', label: '✓ Strong',   style: 'color:#16a34a;font-size:0.85rem' },
    ];

    function evaluateStrength() {
        const value = passwordInput.value;
        let met = 0;
        criteria.forEach(c => {
            const pass = c.test(value);
            c.el.classList.toggle('met', pass);
            if (pass) met++;
        });
        const level = levels[met];
        bars.forEach((b, i) => { b.style.background = i < level.bars ? level.colour : 'var(--strength-empty)'; });
        strengthLabel.textContent   = level.label;
        strengthLabel.style.cssText = level.style;
        checkMatch();
    }

    function checkMatch() {
        if (!confirmInput.value) { matchHint.textContent = ''; matchHint.style.cssText = ''; return; }
        if (passwordInput.value === confirmInput.value) {
            matchHint.textContent   = '✓ Passwords match';
            matchHint.style.cssText = 'color:#16a34a;font-weight:600';
        } else {
            matchHint.textContent   = '✗ Passwords do not match';
            matchHint.style.cssText = 'color:#9f1239;font-weight:600';
        }
    }

    if (passwordInput) passwordInput.addEventListener('input', evaluateStrength);
    if (confirmInput)  confirmInput.addEventListener('input',  checkMatch);

    // Client-side gate before form submits to server
    const form = document.getElementById('registerManagerForm');
    if (form) {
        form.addEventListener('submit', function(e) {
            const errs = [];
            const marketName = document.getElementById('market_name');
            if (marketName && marketName.value.trim().length < 3) errs.push('Market name must be at least 3 characters.');
            const name = document.getElementById('name');
            if (name && name.value.trim().length < 3) errs.push('Full name must be at least 3 characters.');
            const email = document.getElementById('email');
            if (email && !/^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(email.value.trim())) errs.push('Please enter a valid email address.');
            if (criteria.filter(c => c.test(passwordInput.value)).length < 5) errs.push('Password does not meet all strength requirements.');
            if (passwordInput.value !== confirmInput.value) errs.push('Passwords do not match.');

            if (errs.length > 0) {
                e.preventDefault();
                let box = document.querySelector('.alert-error');
                if (!box) { box = document.createElement('div'); box.className = 'alert-error'; form.insertBefore(box, form.firstChild); }
                box.innerHTML = '<strong>⚠ Please fix the following:</strong><ul style="margin:0;padding:0;list-style:none;">' + errs.map(err => `<li><span>•</span> ${err}</li>`).join('') + '</ul>';
                box.scrollIntoView({ behavior: 'smooth', block: 'nearest' });
            }
        });
    }
})();
</script>

<?php require_once '../../templates/footer.php'; ?>
