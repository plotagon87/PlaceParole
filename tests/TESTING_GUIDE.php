<?php
/**
 * TESTING_GUIDE.php
 * 
 * Quick checklist to verify the three-form implementation
 * Access at: http://localhost/PlaceParole/TESTING_GUIDE.php
 */

require_once 'config/db.php';

$checks = [];
$all_passed = true;

// ========================================
// 1. Database Schema Checks
// ========================================

// Check community_feedback table
try {
    $stmt = $pdo->query("SELECT 1 FROM community_feedback LIMIT 1");
    $checks['DB: community_feedback table'] = true;
} catch (Exception $e) {
    $checks['DB: community_feedback table'] = false;
    $all_passed = false;
}

// Check notifications table
try {
    $stmt = $pdo->query("SELECT 1 FROM notifications LIMIT 1");
    $checks['DB: notifications table'] = true;
} catch (Exception $e) {
    $checks['DB: notifications table'] = false;
    $all_passed = false;
}

// Check moderation_log table
try {
    $stmt = $pdo->query("SELECT 1 FROM moderation_log LIMIT 1");
    $checks['DB: moderation_log table'] = true;
} catch (Exception $e) {
    $checks['DB: moderation_log table'] = false;
    $all_passed = false;
}

// Check suggestions.deleted_at column
try {
    $stmt = $pdo->query("SHOW COLUMNS FROM suggestions LIKE 'deleted_at'");
    $checks['DB: suggestions.deleted_at column'] = ($stmt->rowCount() > 0);
    if (!$checks['DB: suggestions.deleted_at column']) $all_passed = false;
} catch (Exception $e) {
    $checks['DB: suggestions.deleted_at column'] = false;
    $all_passed = false;
}

// Check announcements.deleted_at column
try {
    $stmt = $pdo->query("SHOW COLUMNS FROM announcements LIKE 'deleted_at'");
    $checks['DB: announcements.deleted_at column'] = ($stmt->rowCount() > 0);
    if (!$checks['DB: announcements.deleted_at column']) $all_passed = false;
} catch (Exception $e) {
    $checks['DB: announcements.deleted_at column'] = false;
    $all_passed = false;
}

// ========================================
// 2. File Existence Checks
// ========================================

$files = [
    'Forms' => [
        'modules/suggestions/submit.php' => 'Suggestions Form',
        'modules/announcements/create.php' => 'Announcements Form',
        'modules/community/report.php' => 'Feedback Form',
    ],
    'List Pages' => [
        'modules/suggestions/list.php' => 'Suggestions List',
        'modules/announcements/list.php' => 'Announcements List',
        'modules/community/list.php' => 'Feedback List',
    ],
    'Moderation Pages' => [
        'modules/admin/pending_suggestions.php' => 'Pending Suggestions',
        'modules/admin/pending_feedback.php' => 'Pending Feedback',
    ],
    'Deletion Endpoints' => [
        'modules/suggestions/delete.php' => 'Delete Suggestion',
        'modules/announcements/delete.php' => 'Delete Announcement',
        'modules/community/delete.php' => 'Delete Feedback',
    ],
    'Database Migration' => [
        'database_migrations/002_add_suggestions_announcements_feedback.sql' => 'Migration SQL',
    ],
];

foreach ($files as $category => $file_list) {
    foreach ($file_list as $path => $label) {
        $exists = file_exists(__DIR__ . '/' . $path);
        $checks["Files ($category): $label"] = $exists;
        if (!$exists) $all_passed = false;
    }
}

// ========================================
// 3. Language String Checks
// ========================================

$lang_en = require 'lang/en.php';
$lang_fr = require 'lang/fr.php';

$required_strings = [
    'announcement_channels',
    'channel_web',
    'channel_sms',
    'channel_email',
    'submit_feedback',
    'feedback_description',
    'pending_suggestions',
    'pending_feedback',
    'approve',
    'reject',
];

foreach ($required_strings as $key) {
    $en_ok = isset($lang_en[$key]);
    $fr_ok = isset($lang_fr[$key]);
    $checks["Language: $key (EN)"] = $en_ok;
    $checks["Language: $key (FR)"] = $fr_ok;
    if (!$en_ok || !$fr_ok) $all_passed = false;
}

// ========================================
// 4. Function Checks
// ========================================

require_once 'config/notification_handler.php';

$functions = [
    'createGenericNotification',
    'notifyMarketUsersOfSubmission',
    'notifyManagersOfPendingSubmission',
    'getGenericNotifications',
    'markGenericNotificationAsRead',
];

foreach ($functions as $func) {
    $exists = function_exists($func);
    $checks["Functions: $func"] = $exists;
    if (!$exists) $all_passed = false;
}

// ========================================
// 5. Sample Data Checks (Optional)
// ========================================

$sample_checks = [];

try {
    $stmt = $pdo->query("SELECT COUNT(*) as count FROM suggestions WHERE status='pending'");
    $result = $stmt->fetch(PDO::FETCH_ASSOC);
    $sample_checks['Pending Suggestions Count'] = $result['count'] ?? 0;
} catch (Exception $e) {
    $sample_checks['Pending Suggestions Count'] = 'N/A';
}

try {
    $stmt = $pdo->query("SELECT COUNT(*) as count FROM community_feedback WHERE status='approved'");
    $result = $stmt->fetch(PDO::FETCH_ASSOC);
    $sample_checks['Approved Feedback Count'] = $result['count'] ?? 0;
} catch (Exception $e) {
    $sample_checks['Approved Feedback Count'] = 'N/A';
}

try {
    $stmt = $pdo->query("SELECT COUNT(*) as count FROM announcements WHERE deleted_at IS NULL");
    $result = $stmt->fetch(PDO::FETCH_ASSOC);
    $sample_checks['Active Announcements'] = $result['count'] ?? 0;
} catch (Exception $e) {
    $sample_checks['Active Announcements'] = 'N/A';
}

// ========================================
// Display Results
// ========================================
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>PlaceParole - Implementation Testing Guide</title>
    <style>
        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Oxygen, Ubuntu, sans-serif;
            max-width: 1200px;
            margin: 0 auto;
            padding: 20px;
            background: #f5f5f5;
        }
        .container {
            background: white;
            border-radius: 8px;
            padding: 30px;
            box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
        }
        h1 {
            color: #333;
            border-bottom: 3px solid #007bff;
            padding-bottom: 10px;
        }
        h2 {
            color: #555;
            margin-top: 30px;
        }
        .status {
            display: flex;
            align-items: center;
            padding: 12px;
            margin: 8px 0;
            border-radius: 4px;
            border-left: 4px solid #ddd;
        }
        .status.pass {
            background: #d4edda;
            border-left-color: #28a745;
            color: #155724;
        }
        .status.fail {
            background: #f8d7da;
            border-left-color: #dc3545;
            color: #721c24;
        }
        .status-icon {
            font-weight: bold;
            margin-right: 10px;
            font-size: 18px;
        }
        .status-label {
            flex: 1;
        }
        .status-value {
            font-weight: bold;
            font-family: monospace;
        }
        .summary {
            padding: 20px;
            border-radius: 4px;
            margin: 20px 0;
            font-weight: bold;
        }
        .summary.pass {
            background: #d4edda;
            color: #155724;
            border-left: 4px solid #28a745;
        }
        .summary.fail {
            background: #f8d7da;
            color: #721c24;
            border-left: 4px solid #dc3545;
        }
        .section {
            margin-bottom: 30px;
        }
        .quick-links {
            background: #e7f3ff;
            border-left: 4px solid #0066cc;
            padding: 20px;
            border-radius: 4px;
            margin: 20px 0;
        }
        .quick-links h3 {
            margin-top: 0;
        }
        .quick-links a {
            display: inline-block;
            margin: 5px 10px 5px 0;
            padding: 8px 12px;
            background: #0066cc;
            color: white;
            text-decoration: none;
            border-radius: 4px;
        }
        .quick-links a:hover {
            background: #0052a3;
        }
        .table-data {
            width: 100%;
            border-collapse: collapse;
            margin: 20px 0;
        }
        .table-data th, .table-data td {
            padding: 12px;
            text-align: left;
            border-bottom: 1px solid #ddd;
        }
        .table-data th {
            background: #f8f9fa;
            font-weight: bold;
        }
    </style>
</head>
<body>
    <div class="container">
        <h1>✅ PlaceParole - Three-Form Implementation Test Guide</h1>
        <p style="color: #666;">A comprehensive checklist to verify the suggestions, announcements, and feedback system is working correctly.</p>

        <?php if ($all_passed): ?>
            <div class="summary pass">
                ✓ ALL TESTS PASSED - Implementation is complete!
            </div>
        <?php else: ?>
            <div class="summary fail">
                ✗ SOME TESTS FAILED - Please review the failures below
            </div>
        <?php endif; ?>

        <div class="quick-links">
            <h3>Quick Links to Test</h3>
            <a href="modules/suggestions/submit.php">📝 Submit Suggestion</a>
            <a href="modules/announcements/create.php">📢 Create Announcement</a>
            <a href="modules/community/report.php">💬 Share Feedback</a>
            <a href="modules/suggestions/list.php">📋 View Suggestions</a>
            <a href="modules/announcements/list.php">📣 View Announcements</a>
            <a href="modules/community/list.php">💭 View Feedback</a>
            <a href="modules/admin/pending_suggestions.php">⚙️ Mod: Suggestions</a>
            <a href="modules/admin/pending_feedback.php">⚙️ Mod: Feedback</a>
        </div>

        <!-- Database Checks -->
        <div class="section">
            <h2>1. Database Schema Verification</h2>
            <?php
                $db_checks = array_filter($checks, function($k) { return strpos($k, 'DB:') === 0; }, ARRAY_FILTER_USE_KEY);
                foreach ($db_checks as $check => $passed):
            ?>
                <div class="status <?= $passed ? 'pass' : 'fail' ?>">
                    <span class="status-icon"><?= $passed ? '✓' : '✗' ?></span>
                    <span class="status-label"><?= str_replace('DB: ', '', $check) ?></span>
                </div>
            <?php endforeach; ?>
        </div>

        <!-- File Checks -->
        <div class="section">
            <h2>2. File System Verification</h2>
            <?php
                $file_checks = array_filter($checks, function($k) { return strpos($k, 'Files') === 0; }, ARRAY_FILTER_USE_KEY);
                foreach ($file_checks as $check => $passed):
            ?>
                <div class="status <?= $passed ? 'pass' : 'fail' ?>">
                    <span class="status-icon"><?= $passed ? '✓' : '✗' ?></span>
                    <span class="status-label"><?= str_replace('Files (Forms): ', '', str_replace('Files (List Pages): ', '', str_replace('Files (Moderation Pages): ', '', str_replace('Files (Deletion Endpoints): ', '', str_replace('Files (Database Migration): ', '', $check)))); ?></span>
                </div>
            <?php endforeach; ?>
        </div>

        <!-- Language Checks -->
        <div class="section">
            <h2>3. Language Support Verification</h2>
            <?php
                $lang_checks = array_filter($checks, function($k) { return strpos($k, 'Language:') === 0; }, ARRAY_FILTER_USE_KEY);
                $grouped = [];
                foreach ($lang_checks as $check => $passed) {
                    $key = preg_replace('/Language: (\w+) \(.*\)/', '$1', $check);
                    if (!isset($grouped[$key])) $grouped[$key] = ['en' => false, 'fr' => false];
                    if (preg_match('/\(EN\)/', $check)) $grouped[$key]['en'] = $passed;
                    else $grouped[$key]['fr'] = $passed;
                }
                foreach ($grouped as $key => $value):
            ?>
                <div class="status <?= ($value['en'] && $value['fr']) ? 'pass' : 'fail' ?>">
                    <span class="status-icon"><?= ($value['en'] && $value['fr']) ? '✓' : '✗' ?></span>
                    <span class="status-label"><?= $key ?></span>
                    <span class="status-value">EN: <?= $value['en'] ? '✓' : '✗' ?> | FR: <?= $value['fr'] ? '✓' : '✗' ?></span>
                </div>
            <?php endforeach; ?>
        </div>

        <!-- Function Checks -->
        <div class="section">
            <h2>4. Notification Functions Verification</h2>
            <?php
                $func_checks = array_filter($checks, function($k) { return strpos($k, 'Functions:') === 0; }, ARRAY_FILTER_USE_KEY);
                foreach ($func_checks as $check => $passed):
            ?>
                <div class="status <?= $passed ? 'pass' : 'fail' ?>">
                    <span class="status-icon"><?= $passed ? '✓' : '✗' ?></span>
                    <span class="status-label"><?= str_replace('Functions: ', '', $check) ?></span>
                </div>
            <?php endforeach; ?>
        </div>

        <!-- Sample Data -->
        <div class="section">
            <h2>5. Sample Data Statistics (Optional)</h2>
            <table class="table-data">
                <tr>
                    <th>Metric</th>
                    <th>Value</th>
                </tr>
                <?php foreach ($sample_checks as $label => $value): ?>
                    <tr>
                        <td><?= $label ?></td>
                        <td><strong><?= $value ?></strong></td>
                    </tr>
                <?php endforeach; ?>
            </table>
        </div>

        <!-- Manual Testing Checklist -->
        <div class="section">
            <h2>6. Manual Testing Checklist</h2>
            <p>After verifying all auto-checks above, run through these manual tests:</p>
            <table class="table-data">
                <tr>
                    <th>Test</th>
                    <th>Steps</th>
                    <th>Expected Result</th>
                    <th>Status</th>
                </tr>
                <tr>
                    <td>Submit Suggestion</td>
                    <td>Go to /modules/suggestions/submit.php, fill form, submit</td>
                    <td>Success screen, status='pending' in DB</td>
                    <td><input type="checkbox"> Pass</td>
                </tr>
                <tr>
                    <td>Create Announcement</td>
                    <td>Go to /modules/announcements/create.php (as manager), fill form with channels, submit</td>
                    <td>Success screen, appears immediately on list</td>
                    <td><input type="checkbox"> Pass</td>
                </tr>
                <tr>
                    <td>Submit Feedback</td>
                    <td>Go to /modules/community/report.php, fill title + description, submit</td>
                    <td>Success screen, status='pending' in DB, anonymous</td>
                    <td><input type="checkbox"> Pass</td>
                </tr>
                <tr>
                    <td>Moderate Suggestion</td>
                    <td>As manager, go to /modules/admin/pending_suggestions.php, approve a suggestion</td>
                    <td>Suggestion status='approved', notification sent, appears on list</td>
                    <td><input type="checkbox"> Pass</td>
                </tr>
                <tr>
                    <td>Moderate Feedback</td>
                    <td>As manager, go to /modules/admin/pending_feedback.php, approve feedback</td>
                    <td>Feedback status='approved', notification sent, appears on list</td>
                    <td><input type="checkbox"> Pass</td>
                </tr>
                <tr>
                    <td>View Lists (All Users)</td>
                    <td>Check /modules/suggestions/list.php, /modules/announcements/list.php, /modules/community/list.php</td>
                    <td>All approved items visible, feedback anonymous, announcements show channels</td>
                    <td><input type="checkbox"> Pass</td>
                </tr>
                <tr>
                    <td>Language Support</td>
                    <td>Switch to French, reload forms and lists</td>
                    <td>All labels and messages are in French</td>
                    <td><input type="checkbox"> Pass</td>
                </tr>
                <tr>
                    <td>CSRF Protection</td>
                    <td>Try to submit form without CSRF token (modify request)</td>
                    <td>Request rejected/fails</td>
                    <td><input type="checkbox"> Pass</td>
                </tr>
                <tr>
                    <td>Access Control</td>
                    <td>As seller, try to access /modules/announcements/create.php</td>
                    <td>Redirected/denied (manager_only guard)</td>
                    <td><input type="checkbox"> Pass</td>
                </tr>
                <tr>
                    <td>Soft Delete</td>
                    <td>Delete a suggestion/feedback, check list doesn't show it</td>
                    <td>Item removed from lists but exists in DB with deleted_at timestamp</td>
                    <td><input type="checkbox"> Pass</td>
                </tr>
            </table>
        </div>

        <!-- Summary -->
        <div class="section">
            <h2>Next Steps</h2>
            <ul>
                <li>✅ Run all manual tests from the checklist above</li>
                <li>✅ Verify database records are created with correct statuses</li>
                <li>✅ Test notifications are generated (check notifications table)</li>
                <li>✅ Confirm market scoping (sellers can't see other markets' items)</li>
                <li>✅ Check multilingual support (EN/FR)</li>
                <li>✅ Test deletion endpoints</li>
                <li>✅ Review moderation_log for audit trail</li>
            </ul>
        </div>

        <div style="text-align: center; color: #666; margin-top: 30px;">
            <p>Testing Guide Generated: <?= date('Y-m-d H:i:s') ?></p>
            <p><strong>Status:</strong> <?= $all_passed ? '✅ Implementation Complete' : '⚠️ Review Failures' ?></p>
        </div>
    </div>
</body>
</html>
