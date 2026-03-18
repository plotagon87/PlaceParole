<?php
/**
 * modules/auth/register_seller.php
 *
 * SECURITY IMPROVEMENTS IN THIS VERSION:
 * ─────────────────────────────────────
 * 1. CSRF (Cross-Site Request Forgery) token verified on every POST.
 * 2. All inputs sanitised with htmlspecialchars() + trim() before use.
 * 3. Passwords hashed with password_hash(PASSWORD_BCRYPT) — industry-standard bcrypt algorithm.
 *    bcrypt = an adaptive, salted hashing algorithm designed specifically for passwords.
 *    It is intentionally slow, making brute-force attacks extremely expensive.
 * 4. Duplicate email / phone checked before inserting.
 * 5. Password strength enforced SERVER-SIDE as well as client-side.
 *    Client-side validation is cosmetic only; SERVER-SIDE is the real gate.
 * 6. Exception handling with PDO transaction rollback prevents partial inserts.
 */

// Enable error reporting during development to catch issues on these pages
ini_set('display_errors', 1);
error_reporting(E_ALL);

// csrf functions (token generation/verification) — safe for public pages
require_once '../../config/csrf.php';
require_once '../../config/lang.php';
require_once '../../config/db.php';           // Provides $pdo — the database connection object

// registration form page
$pageHasForm = true;

// ── Load all available markets for the dropdown ──────────────────────────────
// fetchAll() retrieves every row from the query result as an associative array
$markets = $pdo->query("SELECT id, name, location FROM markets ORDER BY name ASC")->fetchAll();

// ── Handle form submission
// ... (processing logic continues)


// ── Initialise error and form-value holders ───────────────────────────────────
$errors     = [];   // Array that accumulates validation error messages
$oldValues  = [];   // Array that stores re-populated form values after a failed submission
                    // so the user does not have to retype everything

// ── Rate limiting: prevent registration spam ──────────────────────────────────
// Limit: 5 registration attempts per 15 minutes per IP address
if (session_status() === PHP_SESSION_NONE) session_start();
$max_reg_attempts = 5;
$reg_lockout_time = 15 * 60;  // 15 minutes in seconds
$reg_attempts_key = 'reg_attempts_' . md5($_SERVER['REMOTE_ADDR']);
$reg_lockout_key  = 'reg_lockout_'  . md5($_SERVER['REMOTE_ADDR']);

// Check if this IP is currently locked out
if (isset($_SESSION[$reg_lockout_key]) && time() < $_SESSION[$reg_lockout_key]) {
    $remaining = ceil(($_SESSION[$reg_lockout_key] - time()) / 60);
    $errors[] = "Too many registration attempts. Please wait {$remaining} minute(s) before trying again.";
}

// ── Handle form submission ────────────────────────────────────────────────────
if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    // ── CSRF check ───────────────────────────────────────────────────────────
    // csrf_verify() is defined in config/auth_guard.php
    // It compares the hidden token in the form with the one stored in $_SESSION
    // If they do not match, execution stops immediately — the request is rejected
    csrf_verify();

    // ── Collect and sanitise inputs ──────────────────────────────────────────
    // trim()             : removes leading and trailing whitespace
    // htmlspecialchars() : converts < > & " ' into safe HTML entities, preventing XSS
    // XSS (Cross-Site Scripting) = injecting malicious HTML/JS through input fields
    $market_id        = (int)   ($_POST['market_id']       ?? 0);   // (int) cast prevents non-numeric injection
    $name             = trim(htmlspecialchars($_POST['name']            ?? ''));
    $email            = trim(strtolower($_POST['email']     ?? ''));  // Normalise email to lowercase
    $phone            = preg_replace('/[\s\-()]/', '', trim(htmlspecialchars($_POST['phone'] ?? '')));
    // ^-- Normalize phone: strip spaces, dashes, parentheses before storage
    $stall_no         = trim(htmlspecialchars($_POST['stall_no']        ?? ''));
    $password         =       $_POST['password']         ?? '';      // NOT htmlspecialchars — bcrypt handles raw bytes
    $password_confirm =       $_POST['password_confirm'] ?? '';

    // Preserve values to re-populate form fields on error
    $oldValues = compact('market_id', 'name', 'email', 'phone', 'stall_no');

    // ── Validate: Market selection ───────────────────────────────────────────
    if ($market_id === 0) {
        $errors[] = 'Please select a market from the list.';
    }

    // ── Validate: Full name ──────────────────────────────────────────────────
    if (empty($name)) {
        $errors[] = $t['error_required'] . ' (' . $t['name'] . ')';
    } elseif (strlen($name) < 3) {
        $errors[] = 'Full name must be at least 3 characters long.';
    }

    // ── Validate: Email address ───────────────────────────────────────────────
    // FILTER_VALIDATE_EMAIL is a built-in PHP filter that checks RFC-compliant email format
    if (empty($email) || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $errors[] = $t['error_invalid_email'];
    }

    // ── Validate: Phone number ────────────────────────────────────────────────
    // preg_match() = checks a string against a regular expression (regex) pattern
    // The pattern /^\+?[0-9]{7,15}$/ means:
    //   ^       : start of string
    //   \+?     : optional leading + sign
    //   [0-9]   : only digits
    //   {7,15}  : between 7 and 15 digits
    //   $       : end of string
    if (empty($phone)) {
        $errors[] = $t['error_required'] . ' (' . $t['phone'] . ')';
    } elseif (!preg_match('/^\+?[0-9]{7,15}$/', preg_replace('/[\s\-()]/', '', $phone))) {
        $errors[] = 'Please enter a valid phone number (7–15 digits, international format accepted).';
    }

    // ── Validate: Stall number ────────────────────────────────────────────────
    if (empty($stall_no)) {
        $errors[] = $t['error_required'] . ' (' . $t['stall_number'] . ')';
    }

    // ── Validate: Password strength (SERVER-SIDE) ─────────────────────────────
    // This mirrors the client-side checks but is the AUTHORITATIVE validation.
    // Never rely solely on JavaScript — it can be bypassed by any user.
    $passwordErrors = validatePasswordStrength($password);
    if (!empty($passwordErrors)) {
        $errors = array_merge($errors, $passwordErrors);
    }

    // ── Validate: Password confirmation ──────────────────────────────────────
    if ($password !== $password_confirm) {
        $errors[] = $t['error_password_match'];
    }

    // ── Check uniqueness (only if basic validation passed) ────────────────────
    if (empty($errors)) {

        // Check if email already exists in the database
        $stmt = $pdo->prepare("SELECT id FROM users WHERE email = ? LIMIT 1");
        $stmt->execute([$email]);
        if ($stmt->fetch()) {
            $errors[] = $t['error_email_exists'];
        }

        // Check if phone already exists
        $stmt = $pdo->prepare("SELECT id FROM users WHERE phone = ? LIMIT 1");
        $stmt->execute([$phone]);
        if ($stmt->fetch()) {
            $errors[] = $t['error_phone_exists'];
        }
    }

    // ── If all checks pass — insert the new seller ────────────────────────────
    if (empty($errors)) {
        try {

            // password_hash() with PASSWORD_BCRYPT:
            //   - Generates a random salt automatically
            //   - Runs bcrypt with cost factor 12 (each increment doubles the computation time)
            //   - Returns a 60-character hash that includes the algorithm, cost, salt, and digest
            //   - NEVER store plain-text passwords — bcrypt is irreversible by design
            $passwordHash = password_hash($password, PASSWORD_BCRYPT, ['cost' => 12]);

            $stmt = $pdo->prepare("
                INSERT INTO users
                    (market_id, name, email, phone, stall_no, role, password, lang)
                VALUES
                    (?, ?, ?, ?, ?, 'seller', ?, ?)
            ");
            // Values are passed as a separate array — PDO prepared statements
            // automatically escape them, preventing SQL Injection attacks
            $stmt->execute([
                $market_id,
                $name,
                $email,
                $phone,
                $stall_no,
                $passwordHash,
                $_SESSION['lang'],   // Save the user's chosen language preference
            ]);

            // Store a one-time success flash message in the session
            // It will be displayed on the login page and then immediately cleared
            $_SESSION['success'] = $t['register_success'];
            header('Location: login.php');
            exit; // Always call exit after header() to stop further code execution

        } catch (PDOException $e) {
            // PDOException = an error thrown by the PDO database driver
            // We log the full error internally but only show a safe message to the user
            error_log('Registration PDO error: ' . $e->getMessage());
            $errors[] = 'Registration failed due to a server error. Please try again.';
        }
    }

    // ── Rate limiting: increment attempt counter on registration failure ────
    // This runs only if there are validation or database errors (successful registrations exit above)
    if (!empty($errors) && $_SERVER['REQUEST_METHOD'] === 'POST') {
        $_SESSION[$reg_attempts_key] = ($_SESSION[$reg_attempts_key] ?? 0) + 1;
        
        if ($_SESSION[$reg_attempts_key] >= $max_reg_attempts) {
            $_SESSION[$reg_lockout_key] = time() + $reg_lockout_time;
            $errors[] = "Too many registration attempts. Please wait 15 minutes before trying again.";
        }
    }
}

/**
 * validatePasswordStrength(string $password): array
 *
 * Evaluates the password against five security criteria.
 * Returns an array of error messages for each criterion that fails.
 * An empty array means the password is strong enough.
 *
 * @param  string $password  The raw plain-text password to evaluate
 * @return string[]          Array of human-readable error messages (empty = valid)
 */
function validatePasswordStrength(string $password): array
{
    $errors = [];

    // Criterion 1 — Minimum length
    // strlen() returns the number of bytes (characters) in the string
    if (strlen($password) < 8) {
        $errors[] = 'Password must be at least 8 characters long.';
    }

    // Criterion 2 — At least one uppercase letter
    // preg_match() tests a string against a regex pattern
    // [A-Z] matches any single uppercase ASCII letter
    if (!preg_match('/[A-Z]/', $password)) {
        $errors[] = 'Password must contain at least one uppercase letter (A–Z).';
    }

    // Criterion 3 — At least one lowercase letter
    if (!preg_match('/[a-z]/', $password)) {
        $errors[] = 'Password must contain at least one lowercase letter (a–z).';
    }

    // Criterion 4 — At least one digit
    // \d matches any digit 0–9
    if (!preg_match('/\d/', $password)) {
        $errors[] = 'Password must contain at least one number (0–9).';
    }

    // Criterion 5 — At least one special character
    // The character class [@$!%*?&...] lists accepted special characters
    if (!preg_match('/[@$!%*?&#^()_\-+=\[\]{};\':",.<>\/\\\\|`~]/', $password)) {
        $errors[] = 'Password must contain at least one special character (e.g. @, #, $, !).';
    }

    return $errors;
}

// Now include the site header (prints HTML) only after all redirect logic is done.
require_once '../../templates/header.php';
?>

<!-- ══════════════════════════════════════════════════════════════════════════
     PAGE HTML — Registration Form for Sellers
     ══════════════════════════════════════════════════════════════════════════ -->

<style>
    /* ── Google Font: Outfit (modern, friendly, highly legible) ─────────────── */
    @import url('https://fonts.googleapis.com/css2?family=Outfit:wght@300;400;500;600;700;800&display=swap');

    /* ── CSS Custom Properties (variables) ──────────────────────────────────── */
    :root {
        --green-dark:   #14532d;
        --green-mid:    #16a34a;
        --green-light:  #4ade80;
        --orange:       #ea580c;
        --orange-light: #fed7aa;
        --bg:           #f0fdf4;
        --card-bg:      #ffffff;
        --text:         #111827;
        --muted:        #6b7280;
        --border:       #d1fae5;
        --error-bg:     #fff1f2;
        --error-border: #fda4af;
        --error-text:   #9f1239;

        /* Password strength colours */
        --strength-empty:  #e5e7eb;
        --strength-weak:   #ef4444;   /* Red  */
        --strength-fair:   #f97316;   /* Orange */
        --strength-good:   #eab308;   /* Yellow */
        --strength-strong: #22c55e;   /* Green */
    }

    * { font-family: 'Outfit', sans-serif; box-sizing: border-box; }

    body { background: var(--bg); }

    /* ── Page wrapper ────────────────────────────────────────────────────────── */
    .auth-wrapper {
        min-height: 100vh;
        display: flex;
        align-items: flex-start;
        justify-content: center;
        padding: 2rem 1rem 4rem;
    }

    /* ── Card ────────────────────────────────────────────────────────────────── */
    .auth-card {
        background: var(--card-bg);
        border: 1px solid var(--border);
        border-radius: 1.5rem;
        padding: 2.5rem 2rem;
        width: 100%;
        max-width: 520px;
        box-shadow: 0 20px 60px rgba(20, 83, 45, 0.08),
                    0 4px 16px rgba(0,0,0,0.04);
        animation: cardIn 0.4s cubic-bezier(0.34, 1.56, 0.64, 1) both;
    }

    @keyframes cardIn {
        from { opacity: 0; transform: translateY(24px) scale(0.98); }
        to   { opacity: 1; transform: translateY(0)   scale(1); }
    }

    /* ── Badge ───────────────────────────────────────────────────────────────── */
    .role-badge {
        display: inline-flex;
        align-items: center;
        gap: 0.4rem;
        background: #dcfce7;
        color: var(--green-dark);
        font-size: 0.7rem;
        font-weight: 700;
        letter-spacing: 0.08em;
        text-transform: uppercase;
        padding: 0.3rem 0.75rem;
        border-radius: 999px;
        margin-bottom: 0.75rem;
    }

    /* ── Form labels ─────────────────────────────────────────────────────────── */
    .form-label {
        display: block;
        font-weight: 600;
        font-size: 0.875rem;
        color: var(--text);
        margin-bottom: 0.4rem;
    }

    /* ── Input fields ────────────────────────────────────────────────────────── */
    .form-input {
        width: 100%;
        border: 1.5px solid #d1fae5;
        border-radius: 0.6rem;
        padding: 0.65rem 0.875rem;
        font-size: 0.95rem;
        font-family: 'Outfit', sans-serif;
        color: var(--text);
        background: #f0fdf4;
        transition: border-color 0.2s, box-shadow 0.2s, background 0.2s;
        outline: none;
    }

    .form-input:focus {
        border-color: var(--green-mid);
        background: #ffffff;
        box-shadow: 0 0 0 3px rgba(22, 163, 74, 0.15);
    }

    /* ── Password wrapper (holds input + eye icon) ───────────────────────────── */
    .password-wrapper {
        position: relative;
        display: flex;
        align-items: center;
    }

    .password-wrapper .form-input {
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

    /* ── Password strength meter ─────────────────────────────────────────────── */
    .strength-bars {
        display: flex;
        gap: 4px;
        margin-top: 0.5rem;
    }

    /* Each individual strength bar */
    .strength-bar {
        flex: 1;
        height: 5px;
        border-radius: 999px;
        background: var(--strength-empty);
        transition: background 0.3s ease;
    }

    /* ── Strength criteria checklist ─────────────────────────────────────────── */
    .strength-criteria {
        margin-top: 0.75rem;
        display: grid;
        grid-template-columns: 1fr 1fr;  /* Two columns for compactness */
        gap: 0.3rem 0.75rem;
    }

    /* Each individual criterion row */
    .criterion {
        display: flex;
        align-items: center;
        gap: 0.375rem;
        font-size: 0.78rem;
        color: var(--muted);
        transition: color 0.25s;
    }

    /* The circle icon before each criterion */
    .criterion-dot {
        width: 14px;
        height: 14px;
        border-radius: 50%;
        border: 2px solid var(--muted);
        flex-shrink: 0;
        transition: background 0.25s, border-color 0.25s;
        display: flex;
        align-items: center;
        justify-content: center;
    }

    /* ── When a criterion is met, apply .met class via JavaScript ────────────── */
    .criterion.met {
        color: var(--green-dark);
        font-weight: 600;
    }

    .criterion.met .criterion-dot {
        background: var(--green-mid);
        border-color: var(--green-mid);
    }

    /* Checkmark inside met dot — drawn with CSS border trick */
    .criterion.met .criterion-dot::after {
        content: '';
        display: block;
        width: 4px;
        height: 7px;
        border-right: 2px solid white;
        border-bottom: 2px solid white;
        transform: rotate(45deg) translate(-1px, -1px);
    }

    /* ── Strength label text ─────────────────────────────────────────────────── */
    .strength-label {
        font-size: 0.78rem;
        font-weight: 700;
        margin-top: 0.4rem;
        min-height: 1.1em;
        transition: color 0.3s;
    }

    /* ── Submit button ───────────────────────────────────────────────────────── */
    .btn-submit {
        width: 100%;
        padding: 0.875rem 1rem;
        background: var(--green-mid);
        color: #fff;
        font-size: 1rem;
        font-weight: 700;
        font-family: 'Outfit', sans-serif;
        border: none;
        border-radius: 0.75rem;
        cursor: pointer;
        transition: background 0.2s, transform 0.15s, box-shadow 0.2s;
        box-shadow: 0 4px 16px rgba(22, 163, 74, 0.25);
        letter-spacing: 0.02em;
    }

    .btn-submit:hover {
        background: #15803d;
        transform: translateY(-1px);
        box-shadow: 0 6px 20px rgba(22, 163, 74, 0.35);
    }

    .btn-submit:active { transform: translateY(0); }

    /* ── Error / Alert block ─────────────────────────────────────────────────── */
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

    @keyframes alertShake {
        0%, 100% { transform: translateX(0); }
        20%       { transform: translateX(-6px); }
        60%       { transform: translateX(4px); }
    }

    .alert-error li {
        display: flex;
        align-items: flex-start;
        gap: 0.4rem;
        margin-top: 0.3rem;
    }

    /* ── Form section divider ────────────────────────────────────────────────── */
    .section-divider {
        display: flex;
        align-items: center;
        gap: 0.75rem;
        color: var(--muted);
        font-size: 0.8rem;
        font-weight: 600;
        text-transform: uppercase;
        letter-spacing: 0.06em;
        margin: 1.5rem 0 1rem;
    }

    .section-divider::before,
    .section-divider::after {
        content: '';
        flex: 1;
        height: 1px;
        background: #e5e7eb;
    }

    /* ── Footer link ─────────────────────────────────────────────────────────── */
    .auth-footer-link {
        text-align: center;
        font-size: 0.875rem;
        color: var(--muted);
        margin-top: 1.5rem;
        padding-top: 1.25rem;
        border-top: 1px solid #e5e7eb;
    }

    .auth-footer-link a {
        color: var(--green-mid);
        font-weight: 600;
        text-decoration: none;
    }

    .auth-footer-link a:hover { text-decoration: underline; }

    /* ── Security badge ──────────────────────────────────────────────────────── */
    .security-note {
        display: flex;
        align-items: center;
        gap: 0.5rem;
        font-size: 0.72rem;
        color: var(--muted);
        background: #f9fafb;
        border: 1px solid #e5e7eb;
        border-radius: 0.5rem;
        padding: 0.5rem 0.75rem;
        margin-top: 1rem;
    }
</style>

<div class="auth-wrapper">
<div class="auth-card">

    <!-- ── Header ───────────────────────────────────────────────────────────── -->
    <div class="role-badge">
        <!-- Green dot indicator -->
        <span style="width:7px;height:7px;background:var(--green-mid);border-radius:50%;display:inline-block;"></span>
        <?= htmlspecialchars($t['seller_registration']) ?>
    </div>

    <h1 style="font-size:1.875rem;font-weight:800;color:var(--text);margin:0 0 0.25rem;">
        <?= htmlspecialchars($t['register_seller']) ?>
    </h1>
    <p style="color:var(--muted);font-size:0.9rem;margin:0 0 1.5rem;">
        <?= htmlspecialchars($t['seller_registration_intro']) ?>
    </p>

    <!-- ── Server-side error messages ──────────────────────────────────────── -->
    <?php if (!empty($errors)): ?>
        <div class="alert-error" role="alert" aria-live="polite">
            <strong>⚠ Please fix the following:</strong>
            <ul style="margin:0;padding:0;list-style:none;">
                <?php foreach ($errors as $err): ?>
                    <li>
                        <span style="font-size:1rem;">•</span>
                        <?= htmlspecialchars($err) ?>
                    </li>
                <?php endforeach; ?>
            </ul>
        </div>
    <?php endif; ?>

    <!-- ══════════════════════════════════════════════════════════════════════
         FORM
         method="POST"  : data is sent in the HTTP request body, not the URL
         novalidate     : disables browser's native validation UI (we have our own)
         ══════════════════════════════════════════════════════════════════════ -->
    <form method="POST" id="registerSellerForm" novalidate>

        <!-- CSRF hidden token — protects against Cross-Site Request Forgery attacks -->
        <input type="hidden" name="csrf_token" value="<?= csrf_token() ?>">

        <!-- ── Market Selection ──────────────────────────────────────────── -->
        <div style="margin-bottom:1rem;">
            <label for="market_id" class="form-label"><?= $t['select_market'] ?> *</label>
            <select
                id="market_id"
                name="market_id"
                class="form-input"
                required
                aria-required="true"
                autofocus
            >
                <option value="">— Choose your market —</option>
                <?php foreach ($markets as $market): ?>
                    <!-- htmlspecialchars() prevents XSS in market names -->
                    <option
                        value="<?= (int)$market['id'] ?>"
                        <?= ((int)($oldValues['market_id'] ?? 0) === (int)$market['id']) ? 'selected' : '' ?>
                    >
                        <?= htmlspecialchars($market['name']) ?>
                        <?php if ($market['location']): ?>
                            — <?= htmlspecialchars($market['location']) ?>
                        <?php endif; ?>
                    </option>
                <?php endforeach; ?>
            </select>
            <?php if (empty($markets)): ?>
                <p style="color:var(--error-text);font-size:0.8rem;margin-top:0.3rem;">
                    ⚠ No markets registered yet. Ask an administrator to create one first.
                </p>
            <?php endif; ?>
        </div>

        <!-- ── Personal Information Section ────────────────────────────── -->
        <div class="section-divider">Personal Information</div>

        <!-- Full Name -->
        <div style="margin-bottom:1rem;">
            <label for="name" class="form-label"><?= $t['name'] ?> *</label>
            <input
                type="text"
                id="name"
                name="name"
                class="form-input"
                placeholder="e.g. Mbarga Jean-Pierre"
                value="<?= htmlspecialchars($oldValues['name'] ?? '') ?>"
                required
                autocomplete="name"
                aria-required="true"
            >
        </div>

        <!-- Email Address -->
        <div style="margin-bottom:1rem;">
            <label for="email" class="form-label"><?= $t['email'] ?> *</label>
            <input
                type="email"
                id="email"
                name="email"
                class="form-input"
                placeholder="you@example.com"
                value="<?= htmlspecialchars($oldValues['email'] ?? '') ?>"
                required
                autocomplete="email"
                inputmode="email"
                aria-required="true"
            >
        </div>

        <!-- Two-column row: Phone + Stall Number -->
        <div style="display:grid;grid-template-columns:1fr 1fr;gap:0.75rem;margin-bottom:1rem;">
            <div>
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
                    aria-required="true"
                >
            </div>
            <div>
                <label for="stall_no" class="form-label"><?= $t['stall_number'] ?> *</label>
                <input
                    type="text"
                    id="stall_no"
                    name="stall_no"
                    class="form-input"
                    placeholder="e.g. B-12"
                    value="<?= htmlspecialchars($oldValues['stall_no'] ?? '') ?>"
                    required
                    aria-required="true"
                >
            </div>
        </div>

        <!-- ── Password Section ─────────────────────────────────────────── -->
        <div class="section-divider">Security</div>

        <!-- Password Input + Eye Toggle + Strength Meter -->
        <div style="margin-bottom:1rem;">
            <label for="password" class="form-label"><?= $t['password'] ?> *</label>

            <!-- password-wrapper positions the eye icon absolutely inside the input -->
            <div class="password-wrapper">
                <input
                    type="password"
                    id="password"
                    name="password"
                    class="form-input"
                    placeholder="Create a strong password"
                    required
                    autocomplete="new-password"
                    aria-required="true"
                    aria-describedby="strengthLabel strengthCriteria"
                >
                <!--
                    Eye Toggle Button
                    ─────────────────
                    Clicking this runs togglePasswordVisibility('password', this)
                    type="button" prevents accidental form submission on click
                    aria-label provides accessible description for screen readers
                -->
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

            <!-- ── Strength Progress Bars ───────────────────────────────── -->
            <!--
                Five bars, one per criterion.
                JavaScript will add a colour class to each bar as criteria are met.
                The bars start as var(--strength-empty) = light grey.
            -->
            <div class="strength-bars" id="strengthBars" aria-hidden="true">
                <div class="strength-bar" id="bar1"></div>
                <div class="strength-bar" id="bar2"></div>
                <div class="strength-bar" id="bar3"></div>
                <div class="strength-bar" id="bar4"></div>
                <div class="strength-bar" id="bar5"></div>
            </div>

            <!-- Strength label text (e.g. "Weak", "Strong") -->
            <p class="strength-label" id="strengthLabel" aria-live="polite"></p>

            <!-- ── Criteria Checklist ───────────────────────────────────── -->
            <!--
                Each .criterion row contains a visual dot and a text description.
                JavaScript adds the .met class when the criterion is satisfied,
                which turns the dot green and bolds the text.
                id="strengthCriteria" links this section to the password input via aria-describedby
            -->
            <div class="strength-criteria" id="strengthCriteria">
                <span class="criterion" id="crit-length">
                    <span class="criterion-dot"></span>
                    At least 8 characters
                </span>
                <span class="criterion" id="crit-upper">
                    <span class="criterion-dot"></span>
                    One uppercase letter
                </span>
                <span class="criterion" id="crit-lower">
                    <span class="criterion-dot"></span>
                    One lowercase letter
                </span>
                <span class="criterion" id="crit-number">
                    <span class="criterion-dot"></span>
                    One number
                </span>
                <span class="criterion" id="crit-special">
                    <span class="criterion-dot"></span>
                    One special character
                </span>
            </div>
        </div>

        <!-- Confirm Password Input + Eye Toggle -->
        <div style="margin-bottom:1.5rem;">
            <label for="password_confirm" class="form-label"><?= $t['password_confirm'] ?> *</label>
            <div class="password-wrapper">
                <input
                    type="password"
                    id="password_confirm"
                    name="password_confirm"
                    class="form-input"
                    placeholder="Repeat your password"
                    required
                    autocomplete="new-password"
                    aria-required="true"
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
            <!-- Live match feedback -->
            <p id="matchHint" style="font-size:0.78rem;margin-top:0.3rem;min-height:1.1em;" aria-live="polite"></p>
        </div>

        <!-- ── Submit Button ─────────────────────────────────────────────── -->
        <button type="submit" class="btn-submit">
            Create Seller Account →
        </button>

        <!-- ── Security Note ─────────────────────────────────────────────── -->
        <div class="security-note">
            <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                <path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z"/>
            </svg>
            Your password is encrypted with bcrypt before storage — it is never saved as plain text.
            All data is transmitted over HTTPS.
        </div>
    </form>

    <!-- ── Footer Links ──────────────────────────────────────────────────── -->
    <div class="auth-footer-link">
        <?= htmlspecialchars($t['already_have_account']) ?>
        <a href="login.php"><?= htmlspecialchars($t['login_here']) ?></a>
    </div>
    <div class="auth-footer-link" style="margin-top:0.5rem;border:none;padding:0;">
        Are you a market manager?
        <a href="register_manager.php">Register your market →</a>
    </div>

</div><!-- /.auth-card -->
</div><!-- /.auth-wrapper -->


<!-- ══════════════════════════════════════════════════════════════════════════
     JAVASCRIPT — Password Strength Meter & Visibility Toggle
     ══════════════════════════════════════════════════════════════════════════ -->
<script>
/* ────────────────────────────────────────────────────────────────────────────
   togglePasswordVisibility(inputId, buttonEl)
   ────────────────────────────────────────────────────────────────────────────
   Switches the <input> between type="password" (hidden) and type="text" (visible).
   Also swaps the eye icon appearance via the .revealed CSS class.

   Parameters:
     inputId  : string — the id attribute of the <input> element to toggle
     buttonEl : HTMLElement — the button element that was clicked (for icon swap)
   ────────────────────────────────────────────────────────────────────────────*/
function togglePasswordVisibility(inputId, buttonEl) {
    const input = document.getElementById(inputId);
    if (!input) return;

    if (input.type === 'password') {
        // Reveal the password
        input.type = 'text';
        buttonEl.classList.add('revealed');
        buttonEl.setAttribute('aria-label', 'Hide password');
    } else {
        // Hide the password again
        input.type = 'password';
        buttonEl.classList.remove('revealed');
        buttonEl.setAttribute('aria-label', 'Show password');
    }
}

/* ────────────────────────────────────────────────────────────────────────────
   Password Strength Evaluator
   ────────────────────────────────────────────────────────────────────────────
   Listens to keystrokes in the #password field.
   For every keystroke it:
     1. Tests the current value against each of 5 criteria.
     2. Applies .met class to each criterion row that passes.
     3. Colours the matching number of strength bars.
     4. Updates the strength label text.
   ────────────────────────────────────────────────────────────────────────────*/
(function () {

    // ── Grab references to DOM elements ─────────────────────────────────────
    const passwordInput  = document.getElementById('password');
    const confirmInput   = document.getElementById('password_confirm');
    const bars           = [
        document.getElementById('bar1'),
        document.getElementById('bar2'),
        document.getElementById('bar3'),
        document.getElementById('bar4'),
        document.getElementById('bar5'),
    ];
    const strengthLabel  = document.getElementById('strengthLabel');
    const matchHint      = document.getElementById('matchHint');

    // Criterion element references
    // Each entry: { el: DOM element, test: RegExp or function }
    const criteria = [
        { el: document.getElementById('crit-length'),  test: (v) => v.length >= 8 },
        { el: document.getElementById('crit-upper'),   test: (v) => /[A-Z]/.test(v) },
        { el: document.getElementById('crit-lower'),   test: (v) => /[a-z]/.test(v) },
        { el: document.getElementById('crit-number'),  test: (v) => /\d/.test(v) },
        { el: document.getElementById('crit-special'), test: (v) => /[@$!%*?&#^()_\-+=\[\]{};':",.<>\/\\|`~]/.test(v) },
    ];

    /*
        Strength level configuration.
        Each level maps:
          bars   : how many bars to colour
          colour : the CSS colour string applied to each filled bar
          label  : text shown below the bars
          style  : inline CSS for the label colour
    */
    const levels = [
        { bars: 0, colour: '',                         label: '',           style: '' },                    // 0 criteria met
        { bars: 1, colour: 'var(--strength-weak)',     label: 'Very Weak',  style: 'color:#ef4444' },       // 1
        { bars: 2, colour: 'var(--strength-weak)',     label: 'Weak',       style: 'color:#ef4444' },       // 2
        { bars: 3, colour: 'var(--strength-fair)',     label: 'Fair',       style: 'color:#f97316' },       // 3
        { bars: 4, colour: 'var(--strength-good)',     label: 'Good',       style: 'color:#eab308' },       // 4
        { bars: 5, colour: 'var(--strength-strong)',   label: '✓ Strong',   style: 'color:#16a34a;font-size:0.85rem' }, // 5
    ];

    // ── Main evaluation function ─────────────────────────────────────────────
    function evaluateStrength() {
        const value = passwordInput.value;

        // Count how many criteria are currently met
        let metCount = 0;
        criteria.forEach(function(c) {
            const passed = c.test(value);
            // .met class triggers the green dot and bold text via CSS
            c.el.classList.toggle('met', passed);
            if (passed) metCount++;
        });

        // Update strength bars:
        // Fill bars 0…(metCount-1) with the level colour; reset the rest to empty
        const level = levels[metCount];
        bars.forEach(function(bar, i) {
            bar.style.background = (i < level.bars)
                ? level.colour
                : 'var(--strength-empty)';
        });

        // Update strength label
        strengthLabel.textContent   = level.label;
        strengthLabel.style.cssText = level.style;

        // Re-run the match check whenever password changes
        checkMatch();
    }

    // ── Password match checker ───────────────────────────────────────────────
    function checkMatch() {
        if (!confirmInput.value) {
            matchHint.textContent   = '';
            matchHint.style.cssText = '';
            return;
        }

        if (passwordInput.value === confirmInput.value) {
            matchHint.textContent   = '✓ Passwords match';
            matchHint.style.cssText = 'color:var(--green-mid);font-weight:600';
        } else {
            matchHint.textContent   = '✗ Passwords do not match';
            matchHint.style.cssText = 'color:var(--error-text);font-weight:600';
        }
    }

    // ── Attach event listeners ───────────────────────────────────────────────
    // 'input' fires on every character change (including paste, cut, autocomplete)
    if (passwordInput)  passwordInput.addEventListener('input', evaluateStrength);
    if (confirmInput)   confirmInput.addEventListener('input',  checkMatch);

    // ── Client-side form validation before submission ────────────────────────
    const form = document.getElementById('registerSellerForm');
    if (form) {
        form.addEventListener('submit', function (e) {

            let clientErrors = [];

            // Check market is selected
            const market = document.getElementById('market_id');
            if (market && !market.value) {
                clientErrors.push('Please select a market.');
            }

            // Check name length
            const name = document.getElementById('name');
            if (name && name.value.trim().length < 3) {
                clientErrors.push('Full name must be at least 3 characters.');
            }

            // Check email format using a basic regex
            const email = document.getElementById('email');
            if (email && !/^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(email.value.trim())) {
                clientErrors.push('Please enter a valid email address.');
            }

            // Count met criteria for password
            const criteriaMet = criteria.filter(c => c.test(passwordInput.value)).length;
            if (criteriaMet < 5) {
                clientErrors.push('Password does not meet all strength requirements.');
            }

            // Check passwords match
            if (passwordInput.value !== confirmInput.value) {
                clientErrors.push('Passwords do not match.');
            }

            if (clientErrors.length > 0) {
                // Prevent the form from submitting to the server
                e.preventDefault();

                // Display errors in the alert box — or create one if it doesn't exist
                let alertBox = document.querySelector('.alert-error');
                if (!alertBox) {
                    alertBox = document.createElement('div');
                    alertBox.className = 'alert-error';
                    form.insertBefore(alertBox, form.firstChild);
                }

                alertBox.innerHTML = '<strong>⚠ Please fix the following:</strong><ul style="margin:0;padding:0;list-style:none;">'
                    + clientErrors.map(e => `<li><span>•</span> ${e}</li>`).join('')
                    + '</ul>';

                // Scroll the alert into view
                alertBox.scrollIntoView({ behavior: 'smooth', block: 'nearest' });
            }
        });
    }

})(); // Immediately-Invoked Function Expression (IIFE) — runs once on page load
</script>

<?php require_once '../../templates/footer.php'; ?>
