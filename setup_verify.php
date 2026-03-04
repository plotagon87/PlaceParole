<?php
/**
 * setup_verify.php
 * Verify that PlaceParole is properly set up
 * Access: http://localhost/PlaceParole/setup_verify.php
 */

require_once 'config/db.php';
?>
<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <title>PlaceParole — Setup Verification</title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-gray-50 p-8">

<div class="max-w-2xl mx-auto">
    <h1 class="text-4xl font-bold text-green-700 mb-2">✅ PlaceParole Setup Verification</h1>
    <p class="text-gray-600 mb-8">Check the status of your installation</p>

    <?php
    // Test 1: Database Connection
    echo "<div class='bg-white rounded-lg shadow p-6 mb-4'>";
    try {
        $stmt = $pdo->query("SELECT COUNT(*) FROM markets");
        $count = $stmt->fetchColumn();
        echo "<div class='flex items-center gap-3'>";
        echo "<span class='text-2xl'>✅</span>";
        echo "<div>";
        echo "<h2 class='font-bold text-green-700'>Database Connection</h2>";
        echo "<p class='text-sm text-gray-600'>Connected to placeparole. Found " . $count . " market(s).</p>";
        echo "</div>";
        echo "</div>";
    } catch (Exception $e) {
        echo "<div class='flex items-center gap-3'>";
        echo "<span class='text-2xl'>❌</span>";
        echo "<div>";
        echo "<h2 class='font-bold text-red-700'>Database Connection</h2>";
        echo "<p class='text-sm text-red-600'>" . $e->getMessage() . "</p>";
        echo "</div>";
        echo "</div>";
    }
    echo "</div>";

    // Test 2: Database Tables
    echo "<div class='bg-white rounded-lg shadow p-6 mb-4'>";
    $tables = ['markets', 'users', 'complaints', 'suggestions', 'community_reports', 'announcements'];
    $allTablesExist = true;
    foreach ($tables as $table) {
        try {
            $pdo->query("SELECT 1 FROM $table LIMIT 1");
        } catch (Exception $e) {
            $allTablesExist = false;
            break;
        }
    }
    if ($allTablesExist) {
        echo "<div class='flex items-center gap-3'>";
        echo "<span class='text-2xl'>✅</span>";
        echo "<div>";
        echo "<h2 class='font-bold text-green-700'>Database Tables</h2>";
        echo "<p class='text-sm text-gray-600'>All 6 tables created successfully.</p>";
        echo "</div>";
        echo "</div>";
    } else {
        echo "<div class='flex items-center gap-3'>";
        echo "<span class='text-2xl'>❌</span>";
        echo "<div>";
        echo "<h2 class='font-bold text-red-700'>Database Tables</h2>";
        echo "<p class='text-sm text-red-600'>Some tables are missing. Run setup_database.php again.</p>";
        echo "</div>";
        echo "</div>";
    }
    echo "</div>";

    // Test 3: Config Files
    echo "<div class='bg-white rounded-lg shadow p-6 mb-4'>";
    $configFiles = ['config/db.php', 'config/lang.php', 'config/auth_guard.php', 'lang/en.php', 'lang/fr.php', 'templates/header.php', 'templates/footer.php'];
    $allConfigsExist = true;
    foreach ($configFiles as $file) {
        if (!file_exists($file)) {
            $allConfigsExist = false;
            break;
        }
    }
    if ($allConfigsExist) {
        echo "<div class='flex items-center gap-3'>";
        echo "<span class='text-2xl'>✅</span>";
        echo "<div>";
        echo "<h2 class='font-bold text-green-700'>Config & Template Files</h2>";
        echo "<p class='text-sm text-gray-600'>All essential configuration files present.</p>";
        echo "</div>";
        echo "</div>";
    } else {
        echo "<div class='flex items-center gap-3'>";
        echo "<span class='text-2xl'>❌</span>";
        echo "<div>";
        echo "<h2 class='font-bold text-red-700'>Config & Template Files</h2>";
        echo "<p class='text-sm text-red-600'>Some configuration files are missing.</p>";
        echo "</div>";
        echo "</div>";
    }
    echo "</div>";

    // Test 4: Modules
    echo "<div class='bg-white rounded-lg shadow p-6 mb-4'>";
    $modules = [
        'modules/auth/login.php',
        'modules/complaints/submit.php',
        'modules/complaints/list.php',
        'modules/announcements/list.php',
        'modules/suggestions/submit.php',
        'modules/community/report.php'
    ];
    $allModulesExist = true;
    foreach ($modules as $file) {
        if (!file_exists($file)) {
            $allModulesExist = false;
            break;
        }
    }
    if ($allModulesExist) {
        echo "<div class='flex items-center gap-3'>";
        echo "<span class='text-2xl'>✅</span>";
        echo "<div>";
        echo "<h2 class='font-bold text-green-700'>Core Modules</h2>";
        echo "<p class='text-sm text-gray-600'>All application modules built and ready.</p>";
        echo "</div>";
        echo "</div>";
    } else {
        echo "<div class='flex items-center gap-3'>";
        echo "<span class='text-2xl'>⚠️</span>";
        echo "<div>";
        echo "<h2 class='font-bold text-yellow-700'>Core Modules</h2>";
        echo "<p class='text-sm text-yellow-600'>Some modules may be incomplete.</p>";
        echo "</div>";
        echo "</div>";
    }
    echo "</div>";

    // Quick Start Guide
    echo "<div class='bg-blue-50 border-2 border-blue-300 rounded-lg p-6 mt-8'>";
    echo "<h2 class='font-bold text-blue-900 mb-4'>🚀 Quick Start Guide</h2>";
    echo "<ol class='text-sm text-blue-900 space-y-2 list-decimal list-inside'>";
    echo "<li><a href='modules/auth/register_manager.php' class='text-blue-600 hover:underline font-semibold'>Register a Market Manager</a> — Create a market and manager account</li>";
    echo "<li><a href='modules/auth/register_seller.php' class='text-blue-600 hover:underline font-semibold'>Register as a Seller</a> — Select the market and register</li>";
    echo "<li><a href='modules/auth/login.php' class='text-blue-600 hover:underline font-semibold'>Login as Manager</a> — View the manager dashboard</li>";
    echo "<li><a href='modules/complaints/submit.php' class='text-blue-600 hover:underline font-semibold'>Submit a Test Complaint</a> — As a seller, submit a complaint</li>";
    echo "<li><a href='modules/complaints/list.php' class='text-blue-600 hover:underline font-semibold'>Respond to Complaint</a> — As a manager, respond and change status</li>";
    echo "</ol>";
    echo "</div>";

    echo "<div class='bg-green-50 border-2 border-green-300 rounded-lg p-6 mt-4'>";
    echo "<p class='text-green-900'><strong>✅ PlaceParole is ready to use!</strong><br>Visit <a href='index.php' class='text-green-700 hover:underline font-semibold'>the home page →</a></p>";
    echo "</div>";
    ?>

</div>

</body>
</html>
