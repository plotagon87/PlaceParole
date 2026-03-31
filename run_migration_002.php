<?php
/**
 * Migration Runner: Applies 002_add_suggestions_announcements_feedback.sql
 */

require_once 'config/db.php';

try {
    // Read the migration file
    $migration_file = __DIR__ . '/database_migrations/002_add_suggestions_announcements_feedback.sql';
    $sql = file_get_contents($migration_file);
    
    // Split by ; and execute each statement
    $statements = array_filter(
        array_map('trim', explode(';', $sql)),
        fn($s) => !empty($s) && !str_starts_with($s, '--')
    );
    
    $success_count = 0;
    $error_count = 0;
    $errors = [];
    
    foreach ($statements as $statement) {
        // Skip comment lines and empty statements
        if (empty($statement) || str_starts_with(trim($statement), '--')) {
            continue;
        }
        
        try {
            $pdo->exec($statement);
            $success_count++;
        } catch (Exception $e) {
            $error_count++;
            $errors[] = $e->getMessage();
        }
    }
    
    echo "<h1>Migration Results</h1>";
    echo "<p><strong>Statements executed successfully: " . $success_count . "</strong></p>";
    
    if ($error_count > 0) {
        echo "<p style='color: red;'><strong>Statements with errors: " . $error_count . "</strong></p>";
        echo "<ul>";
        foreach ($errors as $error) {
            echo "<li>" . htmlspecialchars($error) . "</li>";
        }
        echo "</ul>";
    } else {
        echo "<p style='color: green;'><strong>✓ Migration completed successfully!</strong></p>";
    }
    
} catch (Exception $e) {
    echo "Error: " . htmlspecialchars($e->getMessage());
}
?>
