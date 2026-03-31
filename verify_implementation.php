<?php
/**
 * Quick verification script to test all implementation pieces
 */
require_once 'config/db.php';

$results = [];

// 1. Check community_feedback table
try {
    $stmt = $pdo->query("SHOW TABLES LIKE 'community_feedback'");
    $results['community_feedback_table'] = $stmt->rowCount() > 0 ? '✅ EXISTS' : '❌ MISSING';
} catch (Exception $e) {
    $results['community_feedback_table'] = '❌ ERROR: ' . $e->getMessage();
}

// 2. Check notifications table
try {
    $stmt = $pdo->query("SHOW TABLES LIKE 'notifications'");
    $results['notifications_table'] = $stmt->rowCount() > 0 ? '✅ EXISTS' : '❌ MISSING';
} catch (Exception $e) {
    $results['notifications_table'] = '❌ ERROR: ' . $e->getMessage();
}

// 3. Check suggestions.deleted_at column
try {
    $stmt = $pdo->query("SHOW COLUMNS FROM suggestions LIKE 'deleted_at'");
    $results['suggestions_deleted_at'] = $stmt->rowCount() > 0 ? '✅ EXISTS' : '❌ MISSING';
} catch (Exception $e) {
    $results['suggestions_deleted_at'] = '❌ ERROR: ' . $e->getMessage();
}

// 4. Check announcements.deleted_at column
try {
    $stmt = $pdo->query("SHOW COLUMNS FROM announcements LIKE 'deleted_at'");
    $results['announcements_deleted_at'] = $stmt->rowCount() > 0 ? '✅ EXISTS' : '❌ MISSING';
} catch (Exception $e) {
    $results['announcements_deleted_at'] = '❌ ERROR: ' . $e->getMessage();
}

// 5. Check notification functions exist
require_once 'config/notification_handler.php';
$functions = [
    'createGenericNotification',
    'notifyMarketUsersOfSubmission',
    'notifyManagersOfPendingSubmission',
    'getGenericNotifications',
    'markGenericNotificationAsRead'
];

foreach ($functions as $func) {
    $results["function_$func"] = function_exists($func) ? '✅ EXISTS' : '❌ MISSING';
}

// 6. Check language strings
$lang_en = require 'lang/en.php';
$lang_fr = require 'lang/fr.php';

$critical_strings = [
    'submit_feedback',
    'announcement_channels',
    'pending_suggestions',
    'pending_feedback',
    'approve',
    'reject'
];

foreach ($critical_strings as $key) {
    $en_ok = isset($lang_en[$key]) ? '✅' : '❌';
    $fr_ok = isset($lang_fr[$key]) ? '✅' : '❌';
    $results["lang_$key"] = "EN: $en_ok | FR: $fr_ok";
}

// 7. Check file existence
$critical_files = [
    'modules/suggestions/submit.php',
    'modules/announcements/create.php',
    'modules/community/report.php',
    'modules/suggestions/list.php',
    'modules/announcements/list.php',
    'modules/community/list.php',
    'modules/admin/pending_suggestions.php',
    'modules/admin/pending_feedback.php',
    'modules/suggestions/delete.php',
    'modules/announcements/delete.php',
    'modules/community/delete.php',
];

foreach ($critical_files as $file) {
    $exists = file_exists(__DIR__ . '/' . $file) ? '✅' : '❌';
    $results["file_" . basename($file)] = $exists;
}

// Output results
echo "═══════════════════════════════════════════════════════════\n";
echo "IMPLEMENTATION VERIFICATION REPORT\n";
echo "═══════════════════════════════════════════════════════════\n\n";

$passed = 0;
$failed = 0;

foreach ($results as $check => $status) {
    echo "$check: $status\n";
    if (strpos($status, '✅') === 0 || strpos($status, 'EN: ✅') === 0) {
        $passed++;
    } else {
        $failed++;
    }
}

echo "\n═══════════════════════════════════════════════════════════\n";
echo "SUMMARY: $passed passed, $failed failed\n";
echo "═══════════════════════════════════════════════════════════\n";

if ($failed > 0) {
    echo "\n⚠️  ISSUES DETECTED - Please review failures above\n";
    exit(1);
} else {
    echo "\n✅ ALL CHECKS PASSED - Implementation is complete!\n";
    exit(0);
}
?>
