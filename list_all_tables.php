<?php
/**
 * List all tables in the database
 */
require 'config/db.php';

$result = $pdo->query("SHOW TABLES");
$tables = $result->fetchAll(PDO::FETCH_COLUMN);

echo "=== All tables in database ===\n";
foreach ($tables as $table) {
    echo "- $table\n";
}
