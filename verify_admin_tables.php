<?php
require_once 'config/db.php';

echo "<h2>Admin Dashboard Table Verification</h2>\n\n";

$tables = ['admin_activity_log', 'system_health_checks', 'dashboard_widget_config'];
$allExist = true;

foreach ($tables as $table) {
    try {
        $stmt = $pdo->query("DESCRIBE $table");
        $columns = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        echo "✅ Table '$table' exists with columns:\n";
        foreach ($columns as $col) {
            echo "   - {$col['Field']} ({$col['Type']})\n";
        }
        echo "\n";
    } catch (Exception $e) {
        echo "❌ Table '$table' does not exist\n";
        $allExist = false;
    }
}

echo "---\n\n";

// Check users table has new columns
echo "Checking 'users' table for new columns:\n";
try {
    $stmt = $pdo->query("DESCRIBE users");
    $columns = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    $newCols = ['is_active', 'last_login_at', 'deactivated_at'];
    foreach ($newCols as $col) {
        $exists = array_filter($columns, fn($c) => $c['Field'] === $col);
        if ($exists) {
            echo "✅ Column '$col' exists\n";
        } else {
            echo "❌ Column '$col' NOT FOUND\n";
            $allExist = false;
        }
    }
} catch (Exception $e) {
    echo "❌ Error describing users table\n";
    $allExist = false;
}

echo "\n---\n";
if ($allExist) {
    echo "✅ ALL TABLES VERIFIED SUCCESSFULLY!\n";
    echo "\nNext step: Create the initial admin user.\n";
    echo "Run this SQL:\n";
    echo "INSERT INTO users (name, email, phone, role, market_id, password, lang, is_active, created_at)\n";
    echo "VALUES ('Admin User', 'admin@placeparole.local', '+1234567890', 'admin', 1,\n";
    echo "'".password_hash("Admin123456!", PASSWORD_BCRYPT, ['cost' => 12])."', 'en', 1, NOW());\n";
} else {
    echo "❌ SOME TABLES ARE MISSING - Migration may have failed\n";
}
?>
