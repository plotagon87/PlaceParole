<?php
/**
 * test_form_render.php
 * Direct test of form rendering without any headers/footers
 * Navigate to: http://localhost/PlaceParole/test_form_render.php
 */
session_start();

// Simulate a logged-in manager session
$_SESSION['user_id'] = 1;
$_SESSION['market_id'] = 1;
$_SESSION['role'] = 'manager';
$_SESSION['lang'] = 'fr';

// Load language 
$t = require 'lang/fr.php';

?><!DOCTYPE html>
<html>
<head>
    <title>Form Render Test</title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body style="padding: 20px;">

<h1>Form Render Test</h1>
<p>Check if form elements render below:</p>

<div style="border: 2px solid red; padding: 20px; margin: 20px 0;">
    <h2>FORM STRUCTURE TEST:</h2>
    <form method="POST">
        <div>
            <label for="title">Title:</label>
            <input type="text" id="title" name="title" placeholder="Enter title" style="width: 100%; padding: 8px; border: 1px solid gray;">
        </div>

        <div style="margin-top: 15px;">
            <label for="body">Body:</label>
            <textarea id="body" name="body" style="width: 100%; padding: 8px; border: 1px solid gray; height: 100px;"></textarea>
        </div>

        <div style="margin-top: 15px;">
            <label>Channels:</label>
            <label style="display: block; margin: 5px 0;">
                <input type="checkbox" name="channels" value="web"> Web
            </label>
            <label style="display: block; margin: 5px 0;">
                <input type="checkbox" name="channels" value="sms"> SMS
            </label>
        </div>

        <button type="submit" style="margin-top: 15px; padding: 10px 20px; background: green; color: white; border: none; cursor: pointer;">
            Submit
        </button>
    </form>
</div>

<h2>LANGUAGE VARIABLES TEST:</h2>
<pre><?php var_dump($t); ?></pre>

<h2>SESSION TEST:</h2>
<pre><?php var_dump($_SESSION); ?></pre>

</body>
</html>
