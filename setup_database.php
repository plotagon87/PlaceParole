<?php
/**
 * setup_database.php
 * Run this file once to create the PlaceParole database and tables
 * Access it via: http://localhost/PlaceParole/setup_database.php
 */

// MySQL connection details
$host = 'localhost';
$user = 'root';
$pass = '';
$dbname = 'placeparole_db';

// Create connection to MySQL (without selecting a database first)
try {
    $pdo = new PDO("mysql:host=$host", $user, $pass);
    
    // Create the database
    $pdo->exec("CREATE DATABASE IF NOT EXISTS $dbname CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci");
    
    // Select the database
    $pdo->exec("USE $dbname");
    
    // Read and execute the schema file
    $schema = file_get_contents(__DIR__ . '/database_schema.sql');
    
    // Split by semicolons and execute each statement
    $statements = array_filter(array_map('trim', explode(';', $schema)));
    foreach ($statements as $statement) {
        if (!empty($statement)) {
            $pdo->exec($statement);
        }
    }
    
    echo "<div style='font-family: Arial; padding: 20px; background: #d4edda; color: #155724; border: 1px solid #c3e6cb; border-radius: 5px;'>";
    echo "<h2>✅ Database Setup Complete!</h2>";
    echo "<p><strong>Database created:</strong> $dbname</p>";
    echo "<p><strong>Tables created:</strong> markets, users, complaints, suggestions, community_reports, announcements</p>";
    echo "<p><a href='http://localhost/phpmyadmin' target='_blank'>View in phpMyAdmin</a></p>";
    echo "<p style='margin-top: 20px; font-size: 12px;'>";
    echo "Next step: Run Phase 2 setup to create project files.";
    echo "</p>";
    echo "</div>";
    
} catch (PDOException $e) {
    echo "<div style='font-family: Arial; padding: 20px; background: #f8d7da; color: #721c24; border: 1px solid #f5c6cb; border-radius: 5px;'>";
    echo "<h2>❌ Database Error</h2>";
    echo "<p>" . $e->getMessage() . "</p>";
    echo "</div>";
}
?>
