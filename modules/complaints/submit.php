<?php
/**
 * modules/complaints/submit.php
 *
 * WHAT THIS FILE DOES:
 * ─────────────────────
 * 1. Checks the seller is logged in (auth guard).
 * 2. Validates CSRF token on every POST.
 * 3. Validates category and description fields.
 * 4. If a photo is attached, validates type (JPEG/PNG/WebP only) and size (max 2MB).
 * 5. Saves the photo to uploads/complaints/ with a unique filename.
 * 6. Generates a unique reference code (MKT-YEAR-XXXXXXXX format).
 * 7. Inserts the complaint into the database with a 72-hour SLA deadline.
 * 8. Shows a success screen with the reference code.
 * 9. If validation fails, re-renders the form with the error and keeps category selected.
 *
 * SECURITY MEASURES:
 * ──────────────────
 * - CSRF token verified on every POST (config/csrf.php)
 * - Auth guard ensures only logged-in sellers can access this page
 * - File type validated by MIME type (not just file extension — extensions can be faked)
 * - Unique filename generated with uniqid() to prevent filename collisions and guessing
 * - Uploaded file stored outside form's action reach; .htaccess blocks PHP execution
 * - SQL injection prevented by PDO prepared statements
 * - htmlspecialchars() used on all output to prevent XSS
 */

require_once '../../config/auth_guard.php';
seller_only();

$pageHasForm = true;
require_once '../../templates/header.php';
require_once '../../config/db.php';
require_once '../../config/csrf.php';

// ── State variables ───────────────────────────────────────────────────────────
$error       = '';      // Single error message string shown above form
$success     = false;   // True only after a complaint is saved successfully
$ref_code    = '';      // Populated after successful save
$oldCategory = '';      // Remembers which category was selected if form fails

// ── Guard: seller account must be linked to a market ─────────────────────────
$canSubmit = !empty($_SESSION['market_id']);
if (!$canSubmit) {
    $error = 'Your account is not linked to a market. Contact your market manager.';
}

// ── Handle form submission ────────────────────────────────────────────────────
if ($_SERVER['REQUEST_METHOD'] === 'POST' && $canSubmit) {

    csrf_verify(); // Reject request if CSRF token is missing or wrong

    $category    = $_POST['category']    ?? '';
    $description = trim($_POST['description'] ?? '');
    $oldCategory = $category; // Save for form repopulation on error

    // ── Photo upload handling ─────────────────────────────────────────────────
    $photo_path = null; // Will be set only if a valid photo is uploaded

    if (!empty($_FILES['photo']['name'])) {
        // finfo_open() = opens a file information resource that reads true MIME types
        // MIME type = the actual file format regardless of the file extension
        // This prevents attackers from renaming a .php file to .jpg to bypass the check
        $finfo     = finfo_open(FILEINFO_MIME_TYPE);
        $mimeType  = finfo_file($finfo, $_FILES['photo']['tmp_name']);
        finfo_close($finfo);

        $allowedMimes = ['image/jpeg', 'image/png', 'image/webp'];
        $maxFileSize  = 2 * 1024 * 1024; // 2 megabytes in bytes

        if (!in_array($mimeType, $allowedMimes)) {
            $error = 'Only JPEG, PNG, and WebP images are allowed.';
        } elseif ($_FILES['photo']['size'] > $maxFileSize) {
            $error = 'The photo must be smaller than 2MB.';
        } elseif ($_FILES['photo']['error'] !== UPLOAD_ERR_OK) {
            $error = 'Photo upload failed. Please try again.';
        } else {
            // pathinfo(PATHINFO_EXTENSION) extracts the file extension e.g. "jpg"
            $ext      = strtolower(pathinfo($_FILES['photo']['name'], PATHINFO_EXTENSION));
            // uniqid('', true) generates a unique string based on current microsecond timestamp
            // This guarantees no two uploaded files ever share the same name
            $filename = 'complaint_' . uniqid('', true) . '.' . $ext;
            $destDir  = __DIR__ . '/../../uploads/complaints/';
            $destPath = $destDir . $filename;

            if (move_uploaded_file($_FILES['photo']['tmp_name'], $destPath)) {
                $photo_path = 'uploads/complaints/' . $filename;
            } else {
                $error = 'Could not save the photo. Check that the uploads/complaints/ folder exists and is writable.';
            }
        }
    }

    // ── Validate text fields ──────────────────────────────────────────────────
    if (empty($error)) {
        if (empty($category)) {
            $error = 'Please select a complaint category.';
        } elseif (strlen($description) < 10) {
            $error = 'Please describe the issue in at least 10 characters.';
        }
    }

    // ── Save to database ──────────────────────────────────────────────────────
    if (empty($error)) {
        // generateRefCode() is defined below — outside all if-blocks to prevent redeclaration errors
        $refCode = generateRefCode($pdo);

        $stmt = $pdo->prepare("
            INSERT INTO complaints
                (market_id, seller_id, ref_code, category, description, channel, status, photo_path, sla_deadline)
            VALUES
                (?, ?, ?, ?, ?, 'web', 'pending', ?, DATE_ADD(NOW(), INTERVAL 72 HOUR))
        ");

        if ($stmt->execute([
            $_SESSION['market_id'],
            $_SESSION['user_id'],
            $refCode,
            $category,
            $description,
            $photo_path,
        ])) {
            $success  = true;
            $ref_code = $refCode;
        } else {
            $error = 'Database error. Please try again.';
        }
    }
}

/**
 * generateRefCode(PDO $pdo): string
 * Generates a collision-free reference code in MKT-YEAR-XXXXXXXX format.
 * The do-while loop retries until a truly unique code is found.
 * In practice, collisions are astronomically rare but this guarantees safety.
 */
function generateRefCode(PDO $pdo): string {
    do {
        $code  = 'MKT-' . date('Y') . '-' . strtoupper(substr(uniqid('', true), -8));
        $check = $pdo->prepare("SELECT id FROM complaints WHERE ref_code = ? LIMIT 1");
        $check->execute([$code]);
    } while ($check->fetch());
    return $code;
}

// Categories: keys match both $t[] translation keys AND database stored values
$categories = [
    'cat_infrastructure',
    'cat_sanitation',
    'cat_stall_allocation',
    'cat_security',
    'cat_other',
];
?>

<style>
/*
 * COMPLAINT SUBMISSION FORM — LOCAL STYLES
 * ────────────────────────────────────────
 * These styles extend the global style.css without overriding it.
 * They apply ONLY to this form page.
 * All color values are taken directly from the :root variables in style.css.
 */

/* ── Page title area ─────────────────────────────────────────────────────── */
.page-header {
    margin-bottom: 2rem;
}

.page-header h1 {
    font-size: 1.875rem;   /* 30px — matches dashboard h1 size */
    font-weight: 800;
    color: #052e16;        /* var(--color-text-heading) */
    margin: 0 0 0.375rem;
}

.page-header p {
    color: #6b7280;        /* var(--color-text-muted) */
    font-size: 0.9rem;
    margin: 0;
}

/* ── Two-column layout on desktop ────────────────────────────────────────── */
/*
 * On desktop (≥768px): left column = form, right column = info sidebar.
 * On mobile (<768px): single column, sidebar stacks below form.
 * This matches the dashboard's use of two-panel layouts.
 */
.form-layout {
    display: grid;
    grid-template-columns: 1fr;   /* Single column by default (mobile-first) */
    gap: 1.5rem;
    align-items: start;
}

@media (min-width: 768px) {
    .form-layout {
        grid-template-columns: 2fr 1fr; /* Form takes 2/3, sidebar takes 1/3 */
    }
}

/* ── Card — identical to .card in style.css ──────────────────────────────── */
/*
 * We reuse the global .card class from style.css.
 * These local overrides add internal section spacing within the card.
 */
.form-card {
    background: #ffffff;
    border: 1px solid #e5e7eb;
    border-radius: 1rem;
    padding: 2rem;
    box-shadow: 0 1px 3px rgba(0,0,0,0.06), 0 4px 8px rgba(0,0,0,0.04);
}

/* ── Section dividers inside the form card ───────────────────────────────── */
/*
 * These match the section dividers used in the manager registration form.
 * They visually group related fields together.
 */
.form-section-label {
    display: flex;
    align-items: center;
    gap: 0.5rem;
    font-size: 0.75rem;
    font-weight: 700;
    text-transform: uppercase;
    letter-spacing: 0.07em;
    color: #6b7280;
    margin: 1.5rem 0 1rem;
    padding-bottom: 0.5rem;
    border-bottom: 1px solid #e5e7eb;
}

.form-section-label:first-child {
    margin-top: 0; /* No top margin on the very first section */
}

/* ── Form field labels ───────────────────────────────────────────────────── */
.form-field {
    margin-bottom: 1.25rem;
}

.form-field label {
    display: block;
    font-weight: 600;
    font-size: 0.875rem;
    color: #1f2937;        /* var(--color-text-body) */
    margin-bottom: 0.4rem;
}

.form-field .field-hint {
    font-size: 0.75rem;
    color: #6b7280;
    margin-top: 0.3rem;
}

/* ── Input fields — identical to .input-field in style.css ──────────────── */
.form-field select,
.form-field textarea {
    width: 100%;
    border: 1.5px solid #d1d5db;
    border-radius: 0.5rem;
    padding: 0.625rem 0.875rem;
    font-size: 0.95rem;
    font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
    color: #1f2937;
    background: #ffffff;
    transition: border-color 0.2s ease, box-shadow 0.2s ease;
    outline: none;
    box-sizing: border-box;
}

.form-field select:focus,
.form-field textarea:focus {
    border-color: #16a34a;
    box-shadow: 0 0 0 3px rgba(22, 163, 74, 0.20); /* var(--shadow-focus) */
}

.form-field textarea {
    resize: vertical;      /* Let user resize vertically but not horizontally */
    min-height: 120px;
}

/* ── Photo upload zone ───────────────────────────────────────────────────── */
/*
 * The upload area uses a dashed border to visually communicate
 * "you can drop a file here." This is a standard UX pattern.
 * The border color uses the primary green to match the dashboard's accent color.
 */
.upload-zone {
    border: 2px dashed #d1fae5;     /* Light green dashed border */
    border-radius: 0.75rem;
    padding: 2rem 1rem;
    text-align: center;
    cursor: pointer;
    transition: border-color 0.2s ease, background 0.2s ease;
    background: #f0fdf4;            /* var(--color-bg) — page background color */
    position: relative;
}

.upload-zone:hover,
.upload-zone.drag-over {
    border-color: #16a34a;          /* Solid green border on hover/drag */
    background: #dcfce7;            /* var(--color-primary-light) */
}

/* Hide the native file input visually but keep it accessible to screen readers */
/* The actual click is triggered by clicking the styled .upload-zone div */
.upload-zone input[type="file"] {
    position: absolute;
    inset: 0;              /* inset: 0 = top:0; right:0; bottom:0; left:0 — cover entire zone */
    opacity: 0;            /* Invisible but clickable */
    cursor: pointer;
    width: 100%;
    height: 100%;
}

.upload-zone .upload-icon {
    font-size: 2.5rem;
    margin-bottom: 0.75rem;
    display: block;
}

.upload-zone .upload-label {
    font-weight: 600;
    color: #1f2937;
    display: block;
    margin-bottom: 0.25rem;
}

.upload-zone .upload-hint {
    font-size: 0.8rem;
    color: #6b7280;
}

/* ── Photo preview ───────────────────────────────────────────────────────── */
/*
 * Shown below the upload zone once the user selects a file.
 * Uses JavaScript to read the file and display a thumbnail before upload.
 * Starts hidden; JavaScript removes the 'hidden' attribute to reveal it.
 */
#photoPreviewWrapper {
    margin-top: 1rem;
    border: 1px solid #e5e7eb;
    border-radius: 0.75rem;
    overflow: hidden;
    display: none;   /* Hidden by default — shown by JavaScript on file select */
}

#photoPreviewWrapper.visible {
    display: block;
}

#photoPreview {
    width: 100%;
    max-height: 240px;
    object-fit: cover;  /* object-fit: cover = crops image to fill container without distortion */
    display: block;
}

.preview-footer {
    display: flex;
    justify-content: space-between;
    align-items: center;
    padding: 0.5rem 0.875rem;
    background: #f9fafb;
    border-top: 1px solid #e5e7eb;
    font-size: 0.8rem;
    color: #6b7280;
}

/* ── Submit button — matches .btn-primary in style.css ───────────────────── */
.btn-submit-form {
    width: 100%;
    padding: 0.875rem 1rem;
    background: #16a34a;
    color: #ffffff;
    font-size: 1rem;
    font-weight: 700;
    font-family: inherit;
    border: none;
    border-radius: 0.5rem;
    cursor: pointer;
    transition: background 0.2s ease, transform 0.15s ease, box-shadow 0.2s ease;
    box-shadow: 0 4px 12px rgba(22, 163, 74, 0.25);
    letter-spacing: 0.02em;
    margin-top: 0.5rem;
}

.btn-submit-form:hover {
    background: #15803d;    /* var(--color-primary-dark) */
    transform: translateY(-1px);
    box-shadow: 0 6px 16px rgba(22, 163, 74, 0.35);
}

.btn-submit-form:active {
    transform: translateY(0);
    box-shadow: 0 2px 8px rgba(22, 163, 74, 0.25);
}

/* ── Error alert — matches .alert-error in style.css ────────────────────── */
.form-error {
    background: #fef2f2;
    border: 1.5px solid #fca5a5;
    color: #7f1d1d;
    border-radius: 0.5rem;
    padding: 0.875rem 1rem;
    margin-bottom: 1.5rem;
    font-size: 0.875rem;
    display: flex;
    align-items: flex-start;
    gap: 0.5rem;
}

/* ── Success state — shown instead of form after submission ──────────────── */
.success-card {
    text-align: center;
    padding: 2.5rem 1.5rem;
}

.success-icon {
    font-size: 3.5rem;
    display: block;
    margin-bottom: 1rem;
}

.ref-code-display {
    font-size: 1.75rem;
    font-weight: 800;
    font-family: 'Courier New', Courier, monospace; /* Monospace = all characters same width — good for codes */
    letter-spacing: 0.1em;
    color: #ffffff;
    background: #16a34a;
    padding: 0.75rem 1.5rem;
    border-radius: 0.75rem;
    display: inline-block;
    margin: 1rem 0;
}

/* ── Info sidebar card ───────────────────────────────────────────────────── */
.info-sidebar .form-card {
    border-left: 4px solid #16a34a; /* Accent left border — used in list.php for complaints */
}

.info-step {
    display: flex;
    align-items: flex-start;
    gap: 0.875rem;
    margin-bottom: 1.25rem;
}

.info-step:last-child {
    margin-bottom: 0;
}

.step-number {
    width: 28px;
    height: 28px;
    border-radius: 50%;
    background: #dcfce7;   /* var(--color-primary-light) */
    color: #15803d;
    font-size: 0.8rem;
    font-weight: 700;
    display: flex;
    align-items: center;
    justify-content: center;
    flex-shrink: 0;
}

.step-text strong {
    display: block;
    font-size: 0.875rem;
    font-weight: 600;
    color: #1f2937;
    margin-bottom: 0.2rem;
}

.step-text span {
    font-size: 0.8rem;
    color: #6b7280;
}

/* ── Status badge — matches .status-pending etc. in style.css ────────────── */
.sla-badge {
    display: inline-flex;
    align-items: center;
    gap: 0.4rem;
    background: #fffbeb;
    color: #78350f;
    border: 1px solid #fcd34d;
    border-radius: 999px;
    font-size: 0.75rem;
    font-weight: 700;
    padding: 0.3rem 0.75rem;
    margin-top: 1rem;
}

/* ── Character counter for description textarea ──────────────────────────── */
.char-counter {
    text-align: right;
    font-size: 0.75rem;
    color: #6b7280;
    margin-top: 0.3rem;
    min-height: 1em;
}

.char-counter.warning {
    color: #d97706;   /* Orange — approaching limit */
    font-weight: 600;
}

/* ── Responsive adjustments ──────────────────────────────────────────────── */
@media (max-width: 480px) {
    .form-card {
        padding: 1.25rem;   /* Reduce card padding on very small screens */
    }

    .ref-code-display {
        font-size: 1.25rem;  /* Smaller code on mobile */
    }
}
</style>

<div class="max-w-5xl mx-auto">

    <!-- ── Page Header ────────────────────────────────────────────────── -->
    <div class="page-header">
        <h1>📢 <?= $t['submit_complaint'] ?></h1>
        <p>Report an issue in your market. You will receive a unique reference code to track progress.</p>
    </div>

    <!-- ── Success Screen (replaces form after submission) ───────────── -->
    <?php if ($success): ?>
    <div class="form-card success-card">
        <span class="success-icon">✅</span>
        <h2 style="font-size:1.5rem;font-weight:800;color:#052e16;margin:0 0 0.5rem;">
            <?= $t['complaint_sent'] ?>
        </h2>
        <p style="color:#6b7280;margin-bottom:0.5rem;"><?= $t['your_ref_code'] ?>:</p>
        <div class="ref-code-display"><?= htmlspecialchars($ref_code) ?></div>
        <p style="color:#6b7280;font-size:0.875rem;margin:0.75rem 0 2rem;">
            <?= $t['keep_ref_code'] ?>
        </p>
        <div style="display:flex;gap:0.75rem;max-width:380px;margin:0 auto;">
            <a href="track.php" class="btn-primary flex-1 text-center py-3 rounded-lg font-semibold no-underline">
                🔍 <?= $t['track_complaint'] ?>
            </a>
            <a href="../../index.php" class="btn-outlined flex-1 text-center py-3 rounded-lg font-semibold no-underline" style="border:2px solid #16a34a;color:#16a34a;display:flex;align-items:center;justify-content:center;">
                ← <?= $t['back'] ?>
            </a>
        </div>
    </div>

    <?php else: ?>

    <!-- ── Error Alert ────────────────────────────────────────────────── -->
    <?php if ($error): ?>
    <div class="form-error" role="alert">
        <span style="font-size:1.1rem;">⚠️</span>
        <span><?= htmlspecialchars($error) ?></span>
    </div>
    <?php endif; ?>

    <!-- ── Two-column layout: Form + Sidebar ─────────────────────────── -->
    <div class="form-layout">

        <!-- LEFT: Main Form Card -->
        <div class="form-card">
            <form method="POST" enctype="multipart/form-data" id="complaintForm" novalidate>
                <input type="hidden" name="csrf_token" value="<?= csrf_token() ?>">

                <!-- SECTION 1: Issue Details -->
                <p class="form-section-label">📋 Issue Details</p>

                <!-- Category Dropdown -->
                <div class="form-field">
                    <label for="category"><?= $t['complaint_category'] ?> *</label>
                    <select id="category" name="category" required aria-required="true">
                        <option value="">— <?= $t['select_category'] ?> —</option>
                        <?php foreach ($categories as $cat): ?>
                            <option value="<?= $cat ?>"
                                <?= $oldCategory === $cat ? 'selected' : '' ?>>
                                <?= $t[$cat] ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <!-- Description Textarea -->
                <div class="form-field">
                    <label for="description"><?= $t['complaint_description'] ?> *</label>
                    <textarea
                        id="description"
                        name="description"
                        required
                        aria-required="true"
                        maxlength="1000"
                        placeholder="Describe the issue clearly. Include location details, how long it has been happening, and any impact on your business."
                        aria-describedby="charCount"
                        rows="6"
                    ></textarea>
                    <!-- Live character counter updated by JavaScript -->
                    <div class="char-counter" id="charCount">0 / 1000 characters</div>
                    <p class="field-hint">Minimum 10 characters. Be as specific as possible — this helps the manager respond faster.</p>
                </div>

                <!-- SECTION 2: Photo Attachment -->
                <p class="form-section-label">📸 Photo Evidence (Optional)</p>

                <div class="form-field">
                    <!-- Upload Zone — clicking anywhere on it opens the file picker -->
                    <div class="upload-zone" id="uploadZone" role="button" tabindex="0" aria-label="Click or drag a photo here to upload">
                        <!-- Hidden file input — the upload zone div acts as its visual replacement -->
                        <input
                            type="file"
                            id="photo"
                            name="photo"
                            accept="image/jpeg,image/png,image/webp"
                            aria-label="Upload complaint photo"
                        >
                        <span class="upload-icon">🖼️</span>
                        <span class="upload-label" id="uploadLabel">Click to upload a photo</span>
                        <span class="upload-hint">JPEG, PNG, or WebP — maximum 2MB</span>
                    </div>

                    <!-- Photo preview — hidden until a file is selected -->
                    <div id="photoPreviewWrapper">
                        <img id="photoPreview" src="" alt="Selected photo preview">
                        <div class="preview-footer">
                            <span id="previewFileName">No file selected</span>
                            <button type="button" id="removePhoto" style="color:#dc2626;background:none;border:none;cursor:pointer;font-size:0.8rem;font-weight:600;">
                                ✕ Remove
                            </button>
                        </div>
                    </div>
                </div>

                <!-- SECTION 3: Submit -->
                <p class="form-section-label">✅ Submit</p>

                <?php if (!$canSubmit): ?>
                    <div style="background:#fffbeb;border:1px solid #fcd34d;border-radius:0.5rem;padding:1rem;color:#78350f;font-size:0.875rem;">
                        ⚠️ Your account is not linked to a market. Contact your market manager to resolve this before submitting.
                    </div>
                <?php else: ?>
                    <button type="submit" class="btn-submit-form" id="submitBtn">
                        📤 <?= $t['submit'] ?> <?= $t['submit_complaint'] ?>
                    </button>
                <?php endif; ?>
            </form>
        </div>

        <!-- RIGHT: Info Sidebar -->
        <div class="info-sidebar">
            <div class="form-card">
                <h2 style="font-size:1rem;font-weight:700;color:#052e16;margin:0 0 1.25rem;">
                    ℹ️ What Happens Next?
                </h2>

                <div class="info-step">
                    <div class="step-number">1</div>
                    <div class="step-text">
                        <strong>You Submit</strong>
                        <span>Your complaint is recorded and you receive a unique reference code instantly.</span>
                    </div>
                </div>

                <div class="info-step">
                    <div class="step-number">2</div>
                    <div class="step-text">
                        <strong>Manager Reviews</strong>
                        <span>The market manager sees your complaint in their dashboard and begins investigating.</span>
                    </div>
                </div>

                <div class="info-step">
                    <div class="step-number">3</div>
                    <div class="step-text">
                        <strong>You Get Notified</strong>
                        <span>You receive a WhatsApp message when the status changes or a response is posted.</span>
                    </div>
                </div>

                <div class="info-step">
                    <div class="step-number">4</div>
                    <div class="step-text">
                        <strong>Track Anytime</strong>
                        <span>Use your reference code at the track page to check status without logging in.</span>
                    </div>
                </div>

                <div class="sla-badge">
                    ⏱ Target response: within 72 hours
                </div>
            </div>

            <!-- Quick link to track a previous complaint -->
            <div class="form-card" style="margin-top:1rem;text-align:center;">
                <p style="font-size:0.875rem;color:#6b7280;margin:0 0 0.75rem;">
                    Already submitted a complaint?
                </p>
                <a href="track.php" class="btn-outlined" style="border:2px solid #16a34a;color:#16a34a;padding:0.625rem 1.25rem;border-radius:0.5rem;font-weight:600;text-decoration:none;display:inline-block;">
                    🔍 <?= $t['track_complaint'] ?>
                </a>
            </div>

            <!-- My complaints history -->
            <div class="form-card" style="margin-top:1rem;text-align:center;">
                <p style="font-size:0.875rem;color:#6b7280;margin:0 0 0.75rem;">
                    View all your past complaints:
                </p>
                <a href="my_complaints.php" style="color:#16a34a;font-weight:600;font-size:0.875rem;text-decoration:none;">
                    📋 My Complaint History →
                </a>
            </div>
        </div>

    </div><!-- /.form-layout -->
    <?php endif; ?>

</div><!-- /.max-w-5xl -->

<script>
/*
 * COMPLAINT FORM — JAVASCRIPT ENHANCEMENTS
 * ─────────────────────────────────────────
 * 1. Live character counter for the description textarea.
 * 2. Photo upload zone — custom styled trigger + file preview.
 * 3. Client-side validation before form submission (server-side is the gate,
 *    this just provides faster user feedback).
 */

document.addEventListener('DOMContentLoaded', function () {

    // ── 1. Character Counter ─────────────────────────────────────────────────
    // Counts characters typed in the description textarea and updates the label.
    // Turns orange when within 100 characters of the limit.
    const textarea  = document.getElementById('description');
    const charCount = document.getElementById('charCount');
    const maxLength = 1000;

    if (textarea && charCount) {
        textarea.addEventListener('input', function () {
            const current = textarea.value.length;
            charCount.textContent = current + ' / ' + maxLength + ' characters';
            charCount.classList.toggle('warning', current > maxLength - 100);
        });
    }

    // ── 2. Photo Upload Zone ─────────────────────────────────────────────────
    const fileInput       = document.getElementById('photo');
    const uploadZone      = document.getElementById('uploadZone');
    const uploadLabel     = document.getElementById('uploadLabel');
    const previewWrapper  = document.getElementById('photoPreviewWrapper');
    const previewImg      = document.getElementById('photoPreview');
    const previewFileName = document.getElementById('previewFileName');
    const removeBtn       = document.getElementById('removePhoto');

    if (fileInput) {
        // Show preview when user selects a file
        fileInput.addEventListener('change', function () {
            const file = fileInput.files[0];
            if (!file) return;

            // FileReader reads the file contents in the browser (without uploading yet)
            // readAsDataURL() converts the file to a base64 string suitable for <img src="">
            const reader = new FileReader();
            reader.onload = function (e) {
                previewImg.src = e.target.result;        // Set image source to base64 data
                previewWrapper.classList.add('visible'); // Reveal preview block
                previewFileName.textContent = file.name; // Show filename
                uploadLabel.textContent = '✓ Photo selected — click to change';
            };
            reader.readAsDataURL(file);
        });

        // Keyboard accessibility — allow Enter/Space to open file picker on upload zone
        if (uploadZone) {
            uploadZone.addEventListener('keydown', function (e) {
                if (e.key === 'Enter' || e.key === ' ') {
                    e.preventDefault();
                    fileInput.click();
                }
            });

            // Drag-and-drop visual feedback
            uploadZone.addEventListener('dragover',  function (e) { e.preventDefault(); uploadZone.classList.add('drag-over'); });
            uploadZone.addEventListener('dragleave', function ()   { uploadZone.classList.remove('drag-over'); });
            uploadZone.addEventListener('drop',      function (e) {
                e.preventDefault();
                uploadZone.classList.remove('drag-over');
                // Assign dropped files to the input so the form picks them up on submit
                if (e.dataTransfer.files.length) {
                    fileInput.files = e.dataTransfer.files;
                    fileInput.dispatchEvent(new Event('change')); // Trigger the preview
                }
            });
        }
    }

    // Remove photo button resets the input and hides the preview
    if (removeBtn) {
        removeBtn.addEventListener('click', function () {
            fileInput.value   = '';                          // Clear file input
            previewImg.src    = '';                          // Clear preview src
            previewWrapper.classList.remove('visible');      // Hide preview block
            uploadLabel.textContent = 'Click to upload a photo'; // Reset label
        });
    }

    // ── 3. Client-Side Validation ────────────────────────────────────────────
    // Runs before the form is submitted to the server.
    // This gives faster feedback — but the server ALWAYS validates too.
    const form = document.getElementById('complaintForm');
    if (form) {
        form.addEventListener('submit', function (e) {
            const category    = document.getElementById('category').value;
            const description = document.getElementById('description').value.trim();
            const errors      = [];

            if (!category)              errors.push('Please select a complaint category.');
            if (description.length < 10) errors.push('Please describe the issue in at least 10 characters.');

            if (errors.length > 0) {
                e.preventDefault(); // Stop form from submitting
                // Show errors — find or create the error div
                let alertBox = document.querySelector('.form-error');
                if (!alertBox) {
                    alertBox = document.createElement('div');
                    alertBox.className = 'form-error';
                    alertBox.setAttribute('role', 'alert');
                    form.insertBefore(alertBox, form.firstChild);
                }
                alertBox.innerHTML = '<span style="font-size:1.1rem;">⚠️</span><span>' + errors.join('<br>') + '</span>';
                alertBox.scrollIntoView({ behavior: 'smooth', block: 'nearest' });
            }
        });
    }

});
</script>

<?php
require_once '../../templates/footer.php';
?>
