<?php
require_once 'config/db.php';

echo "<h1>Executing Admin Dashboard Migration (004)</h1>\n\n";

$migrationFile = 'database_migrations/004_admin_dashboard.sql';

if (!file_exists($migrationFile)) {
    echo "❌ Migration file not found: $migrationFile\n";
    exit(1);
}

try {
    $sql = file_get_contents($migrationFile);
    
    // Split by semicolons
    $statements = array_filter(array_map('trim', explode(';', $sql)));
    
    $count = 0;
    foreach ($statements as $statement) {
        // Skip comments and empty lines
        if (empty($statement) || substr(trim($statement), 0, 2) === '--') {
            continue;
        }
        
        try {
            $pdo->exec($statement);
            $count++;
            echo "✅ Executed statement $count\n";
        } catch (PDOException $e) {
            echo "❌ Error executing statement: " . $e->getMessage() . "\n";
            echo "   SQL: " . substr($statement, 0, 100) . "...\n";
        }
    }
    
    echo "\n" . str_repeat("=", 50) . "\n\n";
    echo "✅ Migration completed! Executed $count statements\n\n";
    
    // Verify
    echo "Verifying tables...\n";
    $tables = ['admin_activity_log', 'system_health_checks', 'dashboard_widget_config'];
    
    foreach ($tables as $table) {
        try {
            $pdo->query("SELECT 1 FROM $table LIMIT 1");
            echo "✅ Table '$table' exists\n";
        } catch (Exception $e) {
            echo "❌ Table '$table' not found\n";
        }
    }
    
    echo "\nVerifying users columns...\n";
    $stmt = $pdo->query("DESCRIBE users");
    $columns = $stmt->fetchAll(PDO::FETCH_ASSOC);
    $newCols = ['is_active', 'last_login_at', 'deactivated_at'];
    
    foreach ($newCols as $col) {
        $exists = array_filter($columns, fn($c) => $c['Field'] === $col);
        if ($exists) {
            echo "✅ Column '$col' exists\n";
        } else {
            echo "❌ Column '$col' missing\n";
        }
    }
    
} catch (Exception $e) {
    echo "❌ Migration failed: " . $e->getMessage() . "\n";
    exit(1);
}
?>
