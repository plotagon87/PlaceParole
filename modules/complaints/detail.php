<?php
/**
 * modules/complaints/detail.php
 * 
 * NEW: Enhanced complaint detail view with full message threading
 * Manager can view complete conversation history and respond through any channel
 * 
 * FEATURES:
 * - Full message thread with timestamps
 * - Read receipts (shows when seller read manager messages)
 * - Response form with channel selector
 * - Status transition controls
 * - SLA tracking and warnings
 * - Attachment support
 * - Internal notes (optional)
 */

require_once '../../config/auth_guard.php';
manager_only(); // Only managers can access

$pageHasForm = true;
require_once '../../templates/header.php';
require_once '../../config/db.php';
require_once '../../config/csrf.php';
require_once '../../config/complaint_helpers.php';
require_once '../../config/notification_handler.php';

// ─────────────────────────────────────────────────────────────────────
// Get complaint ID from URL
// ─────────────────────────────────────────────────────────────────────
$complaint_id = (int) ($_GET['id'] ?? 0);

if (!$complaint_id) {
    die("<div class='alert alert-error p-4 m-4'>Invalid complaint ID</div>");
}

// ─────────────────────────────────────────────────────────────────────
// Fetch complaint and verify manager has access
// ─────────────────────────────────────────────────────────────────────
$complaint = getComplaintDetails($complaint_id);

if (!$complaint) {
    die("<div class='alert alert-error p-4 m-4'>Complaint not found</div>");
}

if ($complaint['market_id'] != $_SESSION['market_id']) {
    die("<div class='alert alert-error p-4 m-4'>Access denied</div>");
}

// ─────────────────────────────────────────────────────────────────────
// Get thread and statistics
// ─────────────────────────────────────────────────────────────────────
$thread = getComplaintThread($complaint_id);
$thread_stats = getThreadStats($complaint_id);
$sla_status = checkSLACompliance($complaint_id);

// ─────────────────────────────────────────────────────────────────────
// Handle response submission
// ─────────────────────────────────────────────────────────────────────
$error = '';
$success = false;
$response_message = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_verify();
    
    $message = $_POST['response_message'] ?? '';
    $status = $_POST['status'] ?? '';
    $channel = $_POST['channel'] ?? $complaint['channel'];
    $internal_note = $_POST['internal_note'] ?? '';
    
    // Validation
    if (empty($message) || strlen($message) < 5) {
        $error = $t['error_required'] ?? 'Response message must be at least 5 characters';
    } elseif (empty($status)) {
        $error = $t['error_required'] ?? 'Please select a status';
    } else {
        // Send response
        $result = sendComplaintResponse(
            $complaint_id,
            $_SESSION['user_id'],
            $message,
            $status,
            $channel
        );
        
        if ($result['success']) {
            $success = true;
            $response_message = $result['message'];
            
            // Refresh complaint data
            $complaint = getComplaintDetails($complaint_id);
            $thread = getComplaintThread($complaint_id);
        } else {
            $error = $result['message'];
        }
    }
}

// ─────────────────────────────────────────────────────────────────────
// Prepare data for display
// ─────────────────────────────────────────────────────────────────────
$statusColors = [
    'pending'   => '#fbbf24',    // Amber
    'in_review' => '#f59e0b',    // Orange
    'resolved'  => '#10b981',    // Green
];

$channelIcons = [
    'web'  => '🌐',
    'sms'  => '📱',
    'email' => '📧',
    'gmail' => '📬'
];

// Format SLA display
$sla_text = '';
$sla_color = '';
if ($sla_status['status'] === 'resolved') {
    $sla_text = '✓ Resolved';
    $sla_color = '#10b981';
} elseif ($sla_status['status'] === 'breached') {
    $sla_text = '⚠ BREACHED';
    $sla_color = '#ef4444';
} elseif ($sla_status['status'] === 'warning') {
    $sla_text = '⏱ Due Soon (' . floor($sla_status['time_remaining']/60) . ' min)';
    $sla_color = '#f59e0b';
} else {
    $days = floor($sla_status['time_remaining'] / 86400);
    $hours = floor(($sla_status['time_remaining'] % 86400) / 3600);
    $sla_text = "⏱ $days days, $hours hours remaining";
    $sla_color = '#3b82f6';
}

?>

<div class="container max-w-4xl mx-auto px-4 py-6">
    
    <!-- Header with Back Link -->
    <div class="mb-6">
        <a href="list.php" class="text-primary hover:underline font-semibold">← Back to Complaints</a>
    </div>
    
    <!-- Top Bar: Reference & Status -->
    <div class="bg-white rounded-lg shadow-sm border border-gray-200 p-6 mb-6">
        <div class="flex flex-col md:flex-row md:items-center md:justify-between gap-4">
            <div>
                <h1 class="text-2xl font-bold text-gray-800">
                    <?= htmlspecialchars($complaint['ref_code']) ?>
                </h1>
                <p class="text-sm text-gray-600 mt-1">
                    Submitted: <?= date_format(date_create($complaint['created_at']), 'd M Y, H:i') ?>
                </p>
            </div>
            
            <div class="flex gap-4 items-center">
                <!-- Status Badge -->
                <div class="px-4 py-2 rounded-lg text-white font-semibold" 
                     style="background-color: <?= $statusColors[$complaint['status']] ?>">
                    <?= ucfirst($complaint['status']) ?>
                </div>
                
                <!-- SLA Status -->
                <div class="px-4 py-2 rounded-lg text-white font-semibold" 
                     style="background-color: <?= $sla_color ?>">
                    <?= $sla_text ?>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Success/Error Messages -->
    <?php if ($error): ?>
        <div class="bg-red-50 border border-red-300 rounded-lg p-4 mb-6 text-red-700">
            <strong>❌ Error:</strong> <?= htmlspecialchars($error) ?>
        </div>
    <?php endif; ?>
    
    <?php if ($success): ?>
        <div class="bg-green-50 border border-green-300 rounded-lg p-4 mb-6 text-green-700">
            <strong>✓ Success:</strong> <?= htmlspecialchars($response_message) ?>
        </div>
    <?php endif; ?>
    
    <!-- Two-Column Layout: Thread | Response Form -->
    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
        
        <!-- LEFT COLUMN: Message Thread (2/3) -->
        <div class="lg:col-span-2">
            <div class="bg-white rounded-lg shadow-sm border border-gray-200 overflow-hidden">
                
                <!-- Thread Header -->
                <div class="bg-gradient-to-r from-primary to-primary-dark p-4 text-white">
                    <h2 class="text-lg font-bold">Conversation Thread</h2>
                    <p class="text-sm text-white/80 mt-1">
                        <?= $thread_stats['total_messages'] ?? 0 ?> messages
                        • <?= $thread_stats['manager_messages'] ?? 0 ?> from manager
                        • <?= $thread_stats['seller_messages'] ?? 0 ?> from seller
                    </p>
                </div>
                
                <!-- Messages -->
                <div class="p-6 space-y-6 max-h-96 overflow-y-auto bg-gray-50">
                    
                    <?php foreach ($thread as $idx => $msg): ?>
                        <?php
                        $is_manager = $msg['sender_role'] === 'manager';
                        $is_read = !is_null($msg['read_at']);
                        $msg_class = $is_manager ? 'flex-row-reverse' : 'flex-row';
                        $bubble_class = $is_manager 
                            ? 'bg-primary text-white ml-auto' 
                            : 'bg-gray-200 text-gray-800 mr-auto';
                        ?>
                        
                        <div class="flex gap-3 <?= $msg_class ?>">
                            <!-- Avatar -->
                            <div class="flex-shrink-0 w-10 h-10 rounded-full bg-gray-300 flex items-center justify-center font-bold text-sm">
                                <?= substr($msg['sender_name'], 0, 1) ?>
                            </div>
                            
                            <!-- Message Bubble -->
                            <div class="flex-1 max-w-xs">
                                <!-- Header: Name + Time -->
                                <div class="text-xs text-gray-600 mb-1 px-3">
                                    <strong><?= htmlspecialchars($msg['sender_name']) ?></strong>
                                    <span class="text-gray-500">
                                        • <?= date_format(date_create($msg['sent_at']), 'd M, H:i') ?>
                                    </span>
                                    <span class="ml-2 text-gray-500">
                                        <?= $channelIcons[$msg['channel_sent']] ?? '' ?>
                                    </span>
                                </div>
                                
                                <!-- Message Content -->
                                <div class="px-4 py-3 rounded-lg <?= $bubble_class ?> break-words">
                                    <?= nl2br(htmlspecialchars($msg['content'])) ?>
                                </div>
                                
                                <!-- Read Receipt (for manager messages) -->
                                <?php if ($is_manager && $is_read): ?>
                                    <div class="text-xs text-green-600 mt-1 px-3">
                                        ✓ Read on <?= date_format(date_create($msg['read_at']), 'd M, H:i') ?>
                                    </div>
                                <?php endif; ?>
                                
                                <!-- Attachments Indicator -->
                                <?php if ($msg['attachment_count'] > 0): ?>
                                    <div class="text-xs text-gray-500 mt-2 px-3">
                                        📎 <?= $msg['attachment_count'] ?> attachment(s)
                                    </div>
                                <?php endif; ?>
                            </div>
                        </div>
                    <?php endforeach; ?>
                    
                </div>
            </div>
        </div>
        
        <!-- RIGHT COLUMN: Response Form (1/3) -->
        <div class="lg:col-span-1">
            <div class="bg-white rounded-lg shadow-sm border border-gray-200 p-6 sticky top-4">
                
                <h3 class="text-lg font-bold mb-4 text-gray-800">Send Response</h3>
                
                <form method="POST" action="">
                    <!-- CSRF Token -->
                    <?php csrf_token(); ?>
                    
                    <!-- Seller Info -->
                    <div class="bg-gray-50 rounded p-3 mb-4 text-sm">
                        <div class="font-semibold text-gray-700">
                            <?= htmlspecialchars($complaint['seller_name']) ?>
                        </div>
                        <div class="text-gray-600">Stall <?= htmlspecialchars($complaint['stall_no'] ?? 'N/A') ?></div>
                        <?php if ($complaint['seller_phone']): ?>
                            <div class="text-gray-600">📱 <?= htmlspecialchars($complaint['seller_phone']) ?></div>
                        <?php endif; ?>
                        <?php if ($complaint['seller_email']): ?>
                            <div class="text-gray-600 break-all">📧 <?= htmlspecialchars($complaint['seller_email']) ?></div>
                        <?php endif; ?>
                    </div>
                    
                    <!-- Channel Selector -->
                    <div class="mb-4">
                        <label class="block text-sm font-semibold text-gray-700 mb-2">
                            Send via:
                        </label>
                        <select name="channel" class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-primary">
                            <option value="web" <?= $complaint['channel'] === 'web' ? 'selected' : '' ?>>🌐 Web Platform</option>
                            <option value="sms" <?= $complaint['channel'] === 'sms' ? 'selected' : '' ?>>📱 SMS (Original)</option>
                            <option value="email" <?= $complaint['channel'] === 'email' ? 'selected' : '' ?>>📧 Email (Original)</option>
                            <option value="gmail" <?= $complaint['channel'] === 'gmail' ? 'selected' : '' ?>>📬 Gmail</option>
                        </select>
                        <p class="text-xs text-gray-500 mt-1">
                            ✓ Responds via same channel as submission (<?= $channelIcons[$complaint['channel']] ?> <?= ucfirst($complaint['channel']) ?>)
                        </p>
                    </div>
                    
                    <!-- Status Selector -->
                    <div class="mb-4">
                        <label class="block text-sm font-semibold text-gray-700 mb-2">
                            Status:
                        </label>
                        <select name="status" class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-primary">
                            <option value="">-- Select Status --</option>
                            <option value="pending">🔴 Pending</option>
                            <option value="in_review" selected>🟡 In Review</option>
                            <option value="resolved">🟢 Resolved</option>
                        </select>
                    </div>
                    
                    <!-- Message Textarea -->
                    <div class="mb-4">
                        <label class="block text-sm font-semibold text-gray-700 mb-2">
                            Response Message:
                        </label>
                        <textarea 
                            name="response_message" 
                            rows="6"
                            placeholder="Type your response here..."
                            class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-primary resize-none"
                            maxlength="2000"
                        ></textarea>
                        <p class="text-xs text-gray-500 mt-1">Max 2000 characters</p>
                    </div>
                    
                    <!-- Internal Notes (Optional) -->
                    <div class="mb-4">
                        <label class="block text-sm font-semibold text-gray-700 mb-2">
                            Internal Notes (not sent):
                        </label>
                        <textarea 
                            name="internal_note" 
                            rows="3"
                            placeholder="For team reference only..."
                            class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-primary resize-none text-xs"
                            maxlength="1000"
                        ></textarea>
                    </div>
                    
                    <!-- Buttons -->
                    <div class="flex gap-2">
                        <button type="submit" class="flex-1 bg-primary hover:bg-primary-dark text-white font-semibold py-2 rounded-lg transition">
                            ✓ Send Response
                        </button>
                        <button type="reset" class="flex-1 bg-gray-200 hover:bg-gray-300 text-gray-800 font-semibold py-2 rounded-lg transition">
                            Clear
                        </button>
                    </div>
                </form>
                
            </div>
        </div>
    </div>
    
    <!-- Complaint Details Section -->
    <div class="grid grid-cols-1 md:grid-cols-2 gap-6 mt-6">
        
        <!-- Original Complaint -->
        <div class="bg-white rounded-lg shadow-sm border border-gray-200 p-6">
            <h3 class="text-lg font-bold mb-4 text-gray-800">Original Complaint</h3>
            
            <div class="space-y-3 text-sm">
                <div>
                    <span class="font-semibold text-gray-700">Category:</span>
                    <span class="text-gray-600">
                        <?= htmlspecialchars($complaint['category'] ?? 'N/A') ?>
                    </span>
                </div>
                
                <div>
                    <span class="font-semibold text-gray-700">Description:</span>
                    <p class="text-gray-600 mt-1 bg-gray-50 p-3 rounded">
                        <?= nl2br(htmlspecialchars($complaint['description'])) ?>
                    </p>
                </div>
                
                <?php if ($complaint['photo_path']): ?>
                    <div>
                        <span class="font-semibold text-gray-700">Attachment:</span>
                        <div class="mt-2">
                            <a href="<?= htmlspecialchars($complaint['photo_path']) ?>" target="_blank" class="text-primary hover:underline">
                                📎 View attached photo
                            </a>
                        </div>
                    </div>
                <?php endif; ?>
            </div>
        </div>
        
        <!-- Metadata -->
        <div class="bg-white rounded-lg shadow-sm border border-gray-200 p-6">
            <h3 class="text-lg font-bold mb-4 text-gray-800">Details</h3>
            
            <div class="space-y-3 text-sm">
                <div>
                    <span class="font-semibold text-gray-700">Market:</span>
                    <span class="text-gray-600"><?= htmlspecialchars($complaint['market_name']) ?></span>
                </div>
                
                <div>
                    <span class="font-semibold text-gray-700">Submitted:</span>
                    <span class="text-gray-600">
                        <?= date_format(date_create($complaint['created_at']), 'd M Y, H:i') ?>
                    </span>
                </div>
                
                <div>
                    <span class="font-semibold text-gray-700">SLA Deadline:</span>
                    <span class="text-gray-600">
                        <?= date_format(date_create($complaint['sla_deadline']), 'd M Y, H:i') ?>
                    </span>
                </div>
                
                <div>
                    <span class="font-semibold text-gray-700">Channel:</span>
                    <span class="text-gray-600">
                        <?= $channelIcons[$complaint['channel']] ??'' ?> 
                        <?= ucfirst($complaint['channel']) ?>
                    </span>
                </div>
                
                <div>
                    <span class="font-semibold text-gray-700">Response Time:</span>
                    <span class="text-gray-600">
                        <?php 
                        if ($complaint['response_time_secs']) {
                            $hours = floor($complaint['response_time_secs'] / 3600);
                            $mins = floor(($complaint['response_time_secs'] % 3600) / 60);
                            echo "$hours h $mins m";
                        } else {
                            echo "No response yet";
                        }
                        ?>
                    </span>
                </div>
            </div>
        </div>
    </div>
    
</div>

<style>
    .container { max-width: 1200px; margin: 0 auto; }
    .grid { display: grid; }
    .bg-primary { background-color: #16a34a; }
    .bg-primary-dark { background-color: #15803d; }
    .text-primary { color: #16a34a; }
    .focus\:ring-2:focus { outline: 2px solid #16a34a; outline-offset: 2px; }
    
    /* Scrollbar styling for thread */
    .max-h-96::-webkit-scrollbar {
        width: 6px;
    }
    .max-h-96::-webkit-scrollbar-track {
        background: #f1f5f9;
    }
    .max-h-96::-webkit-scrollbar-thumb {
        background: #cbd5e1;
        border-radius: 3px;
    }
</style>

<?php require_once '../../templates/footer.php'; ?>
