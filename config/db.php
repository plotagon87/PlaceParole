<?php
/**
 * config/db.php
 * Database connection configuration
 * This file is included at the top of every PHP file that needs the database.
 * It creates ONE connection object ($pdo) that is reused everywhere.
 */

define('DB_HOST', 'localhost');      // The server MySQL is running on
define('DB_NAME', 'placeparole_db');   // The name of our database
define('DB_USER', 'root');           // MySQL username (default in XAMPP is 'root')
define('DB_PASS', '');               // MySQL password (default in XAMPP is empty '')

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
