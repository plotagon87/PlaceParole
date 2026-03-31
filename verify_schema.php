<?php
require_once 'config/db.php';

echo "<h2>Database Schema Verification</h2>";

// Check for community_feedback table
$stmt = $pdo->query("SHOW TABLES LIKE 'community_feedback'");
$table_exists = $stmt->rowCount() > 0;
echo "<p><strong>community_feedback table:</strong> " . ($table_exists ? "✓ Created" : "✗ Missing") . "</p>";

if ($table_exists) {
    $stmt = $pdo->query("DESCRIBE community_feedback");
    $columns = $stmt->fetchAll(PDO::FETCH_ASSOC);
    echo "<ul>";
    foreach ($columns as $col) {
        echo "<li>" . htmlspecialchars($col['Field']) . " - " . htmlspecialchars($col['Type']) . "</li>";
    }
    echo "</ul>";
}

// Check for notifications table
$stmt = $pdo->query("SHOW TABLES LIKE 'notifications'");
$table_exists = $stmt->rowCount() > 0;
echo "<p><strong>notifications table:</strong> " . ($table_exists ? "✓ Created" : "✗ Missing") . "</p>";

// Check suggestions table for new columns
$stmt = $pdo->query("DESCRIBE suggestions");
$columns = $stmt->fetchAll(PDO::FETCH_ASSOC);
echo "<p><strong>suggestions table columns:</strong></p>";
echo "<ul>";
foreach ($columns as $col) {
    echo "<li>" . htmlspecialchars($col['Field']) . " - " . htmlspecialchars($col['Type']) . "</li>";
}
echo "</ul>";

// Check announcements table
$stmt = $pdo->query("DESCRIBE announcements");
$columns = $stmt->fetchAll(PDO::FETCH_ASSOC);
echo "<p><strong>announcements table columns:</strong></p>";
echo "<ul>";
foreach ($columns as $col) {
    echo "<li>" . htmlspecialchars($col['Field']) . " - " . htmlspecialchars($col['Type']) . "</li>";
}
echo "</ul>";

echo "<p><strong>✓ Schema verification complete!</strong></p>";
?>
