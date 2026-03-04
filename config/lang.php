<?php
/**
 * config/lang.php
 * Language detection and loading
 * Include this file at the top of EVERY page (after session_start)
 * Makes the $t variable available with all translated strings
 */

if (session_status() === PHP_SESSION_NONE) session_start();

// Step 1: If the user manually clicked a language toggle button (?lang=fr or ?lang=en)
if (isset($_GET['lang']) && in_array($_GET['lang'], ['en', 'fr'])) {
    $_SESSION['lang'] = $_GET['lang'];
}

// Step 2: If no language is in the session yet, detect it from the browser
if (!isset($_SESSION['lang'])) {
    // HTTP_ACCEPT_LANGUAGE = a string sent by the browser e.g. "fr-FR,fr;q=0.9,en-US;q=0.8"
    $accept_language = $_SERVER['HTTP_ACCEPT_LANGUAGE'] ?? 'en';
    $languages = explode(',', $accept_language);
    $browserLang = substr($languages[0], 0, 2); // Extract just the language code (e.g. "fr" from "fr-FR")
    
    $_SESSION['lang'] = in_array($browserLang, ['en', 'fr']) ? $browserLang : 'en';
}

// Step 3: Load the correct language file into the $t (translations) variable
// require returns the array from the language file and assigns it to $t
$t = require __DIR__ . "/../lang/{$_SESSION['lang']}.php";
// Usage on any page: echo $t['submit_complaint'];
?>
