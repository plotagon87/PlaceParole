<?php
/**
 * modules/announcements/create.php
 * Managers broadcast announcements to all sellers in their market
 */
require_once '../../config/auth_guard.php';
manager_only();

$pageHasForm = true;
require_once '../../templates/header.php';
require_once '../../config/db.php';
require_once '../../config/notification_handler.php';

$success = false;
$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_verify();
    $title = $_POST['title'] ?? '';
    $body  = $_POST['body']  ?? '';
    
    // Handle file upload
    $picture_path = null;
    if (isset($_FILES['picture']) && $_FILES['picture']['error'] !== UPLOAD_ERR_NO_FILE) {
        $file = $_FILES['picture'];
        
        // Validate file type
        $allowed_types = ['image/jpeg', 'image/jpg', 'image/png', 'image/gif'];
        $allowed_extensions = ['jpg', 'jpeg', 'png', 'gif'];
        $file_extension = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
        
        if (!in_array($file['type'], $allowed_types) || !in_array($file_extension, $allowed_extensions)) {
            $error = 'Invalid file type. Only JPG, PNG, and GIF images are allowed.';
        } elseif ($file['size'] > 5 * 1024 * 1024) { // 5MB limit
            $error = 'File size too large. Maximum 5MB allowed.';
        } elseif ($file['error'] !== UPLOAD_ERR_OK) {
            $error = 'File upload failed. Please try again.';
        } else {
            // Generate unique filename
            $filename = uniqid('announcement_') . '.' . $file_extension;
            $upload_path = __DIR__ . '/../../uploads/announcements/' . $filename;
            
            if (move_uploaded_file($file['tmp_name'], $upload_path)) {
                $picture_path = 'uploads/announcements/' . $filename;
            } else {
                $error = 'Failed to save uploaded file.';
            }
        }
    }
    
    // Set channels to all available (web, sms, email, gmail, whatsapp)
    $sent_via = ['web', 'sms', 'email', 'gmail', 'whatsapp'];
    $sent_via_str = implode(',', $sent_via);

    if (!$title || !$body) {
        $error = $t['error_required'] ?? 'Title and message are required.';
    } elseif (empty($error)) {
        $stmt = $pdo->prepare("INSERT INTO announcements (market_id, manager_id, title, body, picture_path, sent_via) VALUES (?, ?, ?, ?, ?, ?)");
        $stmt->execute([$_SESSION['market_id'], $_SESSION['user_id'], $title, $body, $picture_path, $sent_via_str]);
        $announcement_id = (int) $pdo->lastInsertId();
        
        // Broadcast announcement to all channels
        if ($announcement_id > 0) {
            broadcastAnnouncement($announcement_id, $sent_via);
        }
        
        $success = true;
    }
}
?>

<div class="max-w-lg mx-auto bg-white rounded-2xl shadow-lg p-8">
    <h1 class="text-3xl font-bold text-primary mb-2"><?= $t['broadcast_announcement'] ?></h1>
    <p class="text-gray-600 mb-6">Send an official message to all registered sellers in your market</p>

    <?php if ($success): ?>
        <div class="bg-green-100 text-green-800 p-6 rounded-lg mb-6 text-center">
            <div class="text-5xl mb-3">📣</div>
            <h2 class="text-xl font-bold mb-2"><?= $t['success'] ?>!</h2>
            <p class="mb-4"><?= $t['announcement_sent'] ?? 'Your announcement has been broadcast to all sellers.' ?></p>
            <div class="flex gap-2">
                <a href="list.php" class="flex-1 btn-primary py-2">View Announcements</a>
                <a href="create.php" class="flex-1 btn-outlined py-2">Create Another</a>
            </div>
        </div>
    <?php endif; ?>

    <?php if ($error): ?>
        <div class="bg-red-100 text-red-700 px-4 py-3 rounded-lg mb-6 border border-red-300">
            <strong><?= $t['error'] ?>:</strong> <?= $error ?>
        </div>
    <?php endif; ?>

    <form method="POST" enctype="multipart/form-data" class="space-y-6" style="display: block; visibility: visible;">
        <input type="hidden" name="csrf_token" value="<?= csrf_token() ?>" style="display: none;">
        
        <div class="form-group" style="display: block; margin-bottom: 1.5rem;">
            <label for="title" class="block font-semibold text-gray-700 mb-2" style="display: block; margin-bottom: 0.5rem;">
                <?= $t['announcement_title'] ?? 'Title' ?>
            </label>
            <input type="text" id="title" name="title" class="input-field" placeholder="e.g. Market Closure Tomorrow" required style="display: block; width: 100%; padding: 0.625rem 0.875rem; border: 1.5px solid #d1d5db; border-radius: 0.5rem; font-size: 1rem;">
        </div>

        <div class="form-group" style="display: block; margin-bottom: 1.5rem;">
            <label for="picture" class="block font-semibold text-gray-700 mb-2" style="display: block; margin-bottom: 0.5rem;">
                📷 <?= $t['announcement_picture'] ?? 'Picture' ?> (Optional)
            </label>
            <input type="file" id="picture" name="picture" accept="image/*" class="input-field" style="display: block; width: 100%; padding: 0.625rem 0.875rem; border: 1.5px solid #d1d5db; border-radius: 0.5rem; font-size: 1rem;">
            <small class="text-gray-500 mt-1 block">Supported formats: JPG, PNG, GIF. Max size: 5MB</small>
        </div>

        <div class="form-group" style="display: block; margin-bottom: 1.5rem;">
            <label for="body" class="block font-semibold text-gray-700 mb-2" style="display: block; margin-bottom: 0.5rem;">
                <?= $t['announcement_body'] ?? 'Message' ?>
            </label>
            <textarea id="body" name="body" class="input-field" rows="6" placeholder="Write your announcement here..." required style="display: block; width: 100%; padding: 0.625rem 0.875rem; border: 1.5px solid #d1d5db; border-radius: 0.5rem; font-size: 1rem; font-family: inherit; resize: none;"></textarea>
        </div>

        <div class="bg-blue-50 border border-blue-200 rounded-lg p-4 mb-6">
            <div class="flex items-center gap-2 text-blue-800">
                <span class="text-lg">📡</span>
                <div>
                    <strong>Distribution:</strong> This announcement will be sent via Web, SMS, Gmail, and WhatsApp to all market sellers.
                </div>
            </div>
        </div>

        <button type="submit" class="btn-primary" style="display: block; width: 100%; padding: 0.75rem 1rem; font-size: 1.125rem; font-weight: bold; cursor: pointer;">
            📢 Send Announcement
        </button>
    </form>
</div>

<?php require_once '../../templates/footer.php'; ?>
