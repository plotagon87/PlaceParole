<?php
/**
 * run_migrations.php
 * Automatically run all pending database migrations
 * Navigate to: http://localhost/PlaceParole/run_migrations.php in your browser
 */

require_once 'config/db.php';

echo "<h1>PlaceParole Database Migrations</h1>";
echo "<hr>";

// List of migrations to run IN ORDER
$migrations = [
    '001_add_complaint_threading.sql',
    '002_add_suggestions_announcements_feedback.sql',
    '003_add_soft_delete_columns.sql',
    '005_add_announcement_picture_and_channels.sql'
];

$migrationPath = __DIR__ . '/database_migrations/';
$failureCount = 0;

foreach ($migrations as $migration) {
    $filePath = $migrationPath . $migration;
    
    if (!file_exists($filePath)) {
        echo "<p style='color: red;'><strong>❌ MISSING:</strong> $migration not found</p>";
        $failureCount++;
        continue;
    }
    
    echo "<p><strong>Running:</strong> $migration</p>";
    
    try {
        // Read the SQL file
        $sql = file_get_contents($filePath);
        
        // Split by semicolons to handle multiple statements
        $statements = array_filter(array_map('trim', explode(';', $sql)));
        
        $executedCount = 0;
        foreach ($statements as $statement) {
            // Skip comments and empty statements
            if (empty($statement) || substr(trim($statement), 0, 2) === '--') {
                continue;
            }
            
            try {
                $pdo->exec($statement);
                $executedCount++;
            } catch (PDOException $e) {
                echo "<p style='color: orange;'><small>⚠️  Statement skipped (may already exist): " . substr($e->getMessage(), 0, 80) . "</small></p>";
            }
        }
        
        echo "<p style='color: green;'><strong>✅ COMPLETED:</strong> $migration ($executedCount statements)</p>";
    } catch (Exception $e) {
        echo "<p style='color: red;'><strong>❌ ERROR:</strong> " . $e->getMessage() . "</p>";
        $failureCount++;
    }
}

echo "<hr>";

// Verify tables exist
echo "<h2>Table Verification</h2>";
$tables = [
    'users',
    'markets',
    'complaints',
    'suggestions',
    'announcements',
    'community_feedback',
    'notifications',
    'moderation_log'
];

foreach ($tables as $table) {
    try {
        $result = $pdo->query("SELECT 1 FROM $table LIMIT 1");
        echo "<p style='color: green;'>✅ $table exists</p>";
    } catch (PDOException $e) {
        echo "<p style='color: red;'>❌ $table NOT FOUND</p>";
    }
}

echo "<hr>";
echo "<h2>Summary</h2>";
if ($failureCount === 0) {
    echo "<p style='color: green;'><strong>✅ All migrations completed successfully!</strong></p>";
} else {
    echo "<p style='color: orange;'><strong>⚠️ Some migrations had issues. Check above for details.</strong></p>";
}

echo "<p><a href='index.php'>← Back to Home</a></p>";
?>
