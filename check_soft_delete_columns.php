<?php
/**
 * Check which tables are missing the deleted_at column
 */
require 'config/db.php';

$tables_to_check = ['community_feedback', 'suggestions', 'feedback'];

echo "=== Checking for deleted_at column ===\n\n";

foreach ($tables_to_check as $table) {
    // Check if table exists
    $result = $pdo->query("SHOW TABLES LIKE '$table'");
    $table_exists = $result->rowCount() > 0;
    
    if (!$table_exists) {
        echo "⚠️  Table '$table' does not exist\n";
        continue;
    }
    
    // Check if deleted_at column exists
    $result = $pdo->query("SHOW COLUMNS FROM $table LIKE 'deleted_at'");
    $has_deleted_at = $result->rowCount() > 0;
    
    if ($has_deleted_at) {
        echo "✅ Table '$table' HAS deleted_at column\n";
    } else {
        echo "❌ Table '$table' MISSING deleted_at column - NEEDS FIX\n";
    }
}
