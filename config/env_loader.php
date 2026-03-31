<?php
/**
 * config/env_loader.php
 * 
 * Load environment variables from .env file
 * This file should be included at the very beginning of your application
 * 
 * USAGE:
 *   require_once __DIR__ . '/../config/env_loader.php';
 *   $db_host = getenv('DB_HOST');
 *   $db_pass = getenv('DB_PASS') ?: '';
 */

// Define the path to the .env file
$env_file = __DIR__ . '/../.env';

// Check if .env file exists
if (!file_exists($env_file)) {
    // Try to guide the user to create it
    die("Error: .env file not found. Please copy .env.example to .env and update it with your actual values: {$env_file}");
}

// Read and parse the .env file
function loadEnvFile($file) {
    $lines = file($file, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    
    foreach ($lines as $line) {
        // Skip comments
        if (strpos(trim($line), '#') === 0) {
            continue;
        }
        
        // Parse KEY=VALUE format
        if (strpos($line, '=') !== false) {
            [$key, $value] = explode('=', $line, 2);
            $key = trim($key);
            $value = trim($value);
            
            // Remove quotes if present
            $value = trim($value, '\'"');
            
            // Set as environment variable
            putenv("{$key}={$value}");
            $_ENV[$key] = $value;
            $_SERVER[$key] = $value;
        }
    }
}

// Load the environment file
loadEnvFile($env_file);

// Optional: Helper function to get env variables with default values
if (!function_exists('env')) {
    function env($key, $default = null) {
        $value = getenv($key);
        return $value !== false ? $value : $default;
    }
}
?>
