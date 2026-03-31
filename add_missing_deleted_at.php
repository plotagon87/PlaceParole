<?php
/**
 * Add deleted_at column to suggestions table for soft delete support
 */
require 'config/db.php';

try {
    // Check if column exists first
    $result = $pdo->query("SHOW COLUMNS FROM suggestions LIKE 'deleted_at'");
    if ($result->rowCount() > 0) {
        echo "⚠️ Column deleted_at already exists in suggestions table\n";
    } else {
        $pdo->exec("ALTER TABLE suggestions ADD COLUMN deleted_at TIMESTAMP NULL DEFAULT NULL");
        echo "✅ Successfully added deleted_at column to suggestions table\n";
    }
} catch (PDOException $e) {
    echo "❌ Error: " . $e->getMessage() . "\n";
}

// Also check community_reports in case it needs soft deletes
echo "\n=== Checking community_reports ===\n";
try {
    $result = $pdo->query("SHOW COLUMNS FROM community_reports LIKE 'deleted_at'");
    if ($result->rowCount() > 0) {
        echo "✅ community_reports HAS deleted_at column\n";
    } else {
        echo "❌ community_reports might need deleted_at column\n";
    }
} catch (PDOException $e) {
    echo "⚠️ community_reports table might not exist: " . $e->getMessage() . "\n";
}
