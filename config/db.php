<?php
/**
 * config/db.php
 * Database connection configuration
 * This file is included at the top of every PHP file that needs the database.
 * It creates ONE connection object ($pdo) that is reused everywhere.
 */

// Load environment variables from .env file
require_once __DIR__ . '/env_loader.php';

// Application base URL — change this if you rename the /PlaceParole folder
define('BASE_URL', '');                              // Empty string — site is now at root

// Database configuration from environment variables
define('DB_HOST', 'sql212.infinityfree.com');        // Your actual MySQL host from Step 4
define('DB_NAME', 'if0_41577102_placeparole');      // Your actual DB name from Step 4
define('DB_USER', 'if0_41577102');                  // Your actual MySQL username from Step 4
define('DB_PASS', 'y8mZ8C10m9XEQt');             // Your actual password from Step 4

try {
    // PDO (PHP Data Objects) = a safe, modern interface for connecting PHP to MySQL
    // DSN (Data Source Name) = a string that tells PDO where and how to connect
    $dsn = "mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=utf8mb4";
    // charset=utf8mb4 supports all characters including French accented letters (é, è, ê, etc.)

    $pdo = new PDO($dsn, DB_USER, DB_PASS, [
        PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION, // Throw exceptions on errors
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,       // Return results as associative arrays
        PDO::ATTR_EMULATE_PREPARES   => false,                   // Use real prepared statements
    ]);
    // Prepared statements prevent SQL Injection = a hacking technique where malicious SQL code is inserted into input fields

} catch (PDOException $e) {
    // PDOException = an error thrown by PDO when something goes wrong
    // die() stops all execution and shows an error message
    die("Database connection failed: " . $e->getMessage());
}
?>
