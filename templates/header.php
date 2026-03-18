<?php
/**
 * templates/header.php
 * Include at the very top of every page with: require_once '../templates/header.php';
 * Adjust the path (../) depending on how deep the file is in the folder structure
 */

if (session_status() === PHP_SESSION_NONE) session_start();
require_once __DIR__ . '/../config/db.php'; // Load BASE_URL constant
require_once __DIR__ . '/../config/lang.php'; // Load language system — $t is now available

// Append the current language query parameter to internal links when needed.
// This keeps users on the selected language after navigating away from the home page.
$langParam = isset($_SESSION['lang']) ? '?lang=' . $_SESSION['lang'] : '';
?><!DOCTYPE html>
<html lang="<?= $_SESSION['lang'] ?? 'en' ?>">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="description" content="PlaceParole — Market Feedback & Communication Platform for Cameroon's market communities. Report complaints, share suggestions, and build community support systems.">
    <meta name="theme-color" content="#22863a">
    <title><?= $t['app_name'] ?> — <?= $t['app_tagline'] ?></title>
    
    <!-- Favicon -->
    <link rel="icon" type="image/svg+xml" href="data:image/svg+xml,<svg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 100 100'><text y='.9em' font-size='90' fill='%2322863a'>🗣</text></svg>">
    
    <!-- Tailwind CSS (built locally for production) -->
    <!--
        The warning in the console indicates the CDN version is not intended for
        production. To generate a compiled stylesheet run the Tailwind CLI or
        PostCSS plugin during your build step, e.g.:

            npm install -D tailwindcss postcss autoprefixer
            npx tailwindcss init
            npx tailwindcss -i ./assets/css/src/input.css -o ./assets/css/tailwind.css --minify

        Put your `@tailwind base; @tailwind components; @tailwind utilities;`
        directives in `assets/css/src/input.css` and include the resulting
        `tailwind.css` file below.
    -->
    <!-- <link rel="stylesheet" href="<?= BASE_URL ?>/assets/css/tailwind.css"> -->
    <script src="https://cdn.tailwindcss.com"></script>
    <!-- Custom overrides & utilities -->
    <link rel="stylesheet" href="<?= BASE_URL ?>/assets/css/style.css">

    <!-- Alpine.js ships with a CSP‑safe build when installed via npm/bundler -->
    <!-- In the meantime you can download a copy and serve it from assets/js/ -->
    <script defer src="<?= BASE_URL ?>/assets/js/alpine.min.js"></script>

    <!-- App.js utilities -->
    <script src="<?= BASE_URL ?>/assets/js/app.js"></script>
    
</head>
<body class="min-h-screen">

<!-- Navigation Bar -->
<div x-data="{ mobileMenuOpen: false }">
    <nav class="bg-primary text-white px-6 py-4 flex items-center justify-between shadow-md sticky top-0 z-50">
        <!-- Logo / App Name -->
        <div class="flex items-center gap-3">
            <a href="/" class="text-2xl font-bold text-white hover:text-gray-200 transition">
                <?= $t['app_name'] ?>
            </a>
        </div>

        <!-- Mobile Menu Button (Hamburger) -->
        <button 
            class="md:hidden text-white text-2xl hover:text-gray-200 transition" 
            @click="mobileMenuOpen = !mobileMenuOpen"
        >
            ☰
        </button>

        <!-- Desktop Navigation Menu -->
        <div class="hidden md:flex items-center gap-6">
            <?php if (isset($_SESSION['user_id'])): ?>
                <!-- Logged In Navigation -->
                <a href="/" class="hover:text-gray-200 transition"><?= $t['nav_home'] ?></a>
                
                <?php if ($_SESSION['role'] === 'seller'): ?>
                    <a href="<?= BASE_URL ?>/modules/complaints/submit.php" class="hover:text-gray-200 transition"><?= $t['nav_complaints'] ?></a>
                    <a href="<?= BASE_URL ?>/modules/complaints/my_complaints.php" class="hover:text-gray-200 transition">📋 My History</a>
                    <a href="<?= BASE_URL ?>/modules/suggestions/submit.php" class="hover:text-gray-200 transition"><?= $t['nav_suggestions'] ?></a>
                    <a href="<?= BASE_URL ?>/modules/community/report.php" class="hover:text-gray-200 transition"><?= $t['nav_community'] ?></a>
                    <a href="<?= BASE_URL ?>/modules/announcements/list.php<?= $langParam ?>" class="hover:text-gray-200 transition"><?= $t['nav_announcements'] ?></a>
                <?php elseif ($_SESSION['role'] === 'manager'): ?>
                    <a href="<?= BASE_URL ?>/modules/analytics/dashboard.php<?= $langParam ?>" class="hover:text-gray-200 transition"><?= $t['nav_analytics'] ?? 'Analytics' ?></a>
                    <a href="<?= BASE_URL ?>/modules/complaints/list.php<?= $langParam ?>" class="hover:text-gray-200 transition"><?= $t['nav_complaints'] ?></a>
                    <a href="<?= BASE_URL ?>/modules/suggestions/list.php<?= $langParam ?>" class="hover:text-gray-200 transition"><?= $t['nav_suggestions'] ?></a>
                    <a href="<?= BASE_URL ?>/modules/announcements/create.php<?= $langParam ?>" class="hover:text-gray-200 transition"><?= $t['nav_announcements'] ?></a>
                    <a href="<?= BASE_URL ?>/modules/community/list.php<?= $langParam ?>" class="hover:text-gray-200 transition"><?= $t['nav_community'] ?></a>
                <?php elseif ($_SESSION['role'] === 'admin'): ?>
                    <a href="<?= BASE_URL ?>/modules/admin/overview.php<?= $langParam ?>" class="hover:text-gray-200 transition">Admin Overview</a>
                    <a href="<?= BASE_URL ?>/modules/analytics/dashboard.php<?= $langParam ?>" class="hover:text-gray-200 transition"><?= $t['nav_analytics'] ?? 'Analytics' ?></a>
                    <a href="<?= BASE_URL ?>/modules/announcements/list.php<?= $langParam ?>" class="hover:text-gray-200 transition"><?= $t['nav_announcements'] ?></a>
                <?php endif; ?>
            <?php else: ?>
                <!-- Not Logged In Navigation -->
                <a href="<?= BASE_URL ?>/modules/complaints/track.php<?= $langParam ?>" class="hover:text-gray-200 transition"><?= $t['track_complaint'] ?></a>
            <?php endif; ?>
        </div>

        <!-- Right Side: Language & User Menu (Desktop only) -->
        <div class="hidden md:flex items-center gap-4">
            <!-- Language Toggle -->
            <div class="flex gap-2 items-center">
                <?php
                    // Helper function to build language toggle URL while preserving other query parameters
                    function buildLangUrl($lang) {
                        $params = $_GET;
                        $params['lang'] = $lang;
                        $query = http_build_query($params);
                        return '?' . $query;
                    }
                ?>
                <a href="<?= buildLangUrl('en') ?>" class="<?= ($_SESSION['lang'] ?? 'en') === 'en' ? 'font-bold' : 'opacity-70 hover:opacity-100' ?> transition">EN</a>
                <span class="opacity-70">/</span>
                <a href="<?= buildLangUrl('fr') ?>" class="<?= ($_SESSION['lang'] ?? 'en') === 'fr' ? 'font-bold' : 'opacity-70 hover:opacity-100' ?> transition">FR</a>

                <!-- Theme selector dropdown (light/dark/system) -->
                <div x-data="{ open: false }" class="relative ml-4">
                    <button
                        id="theme-toggle"
                        class="text-white transition opacity-70 hover:opacity-100"
                        aria-label="Change colour theme"
                        @click="open = !open"
                    >🌓</button>
                    <div
                        x-show="open"
                        @click.outside="open = false"
                        x-transition
                        class="absolute right-0 mt-2 w-36 bg-white text-gray-800 rounded-lg shadow-lg z-50"
                    >
                        <button class="w-full text-left px-4 py-2 hover:bg-gray-100" @click="setTheme('light'); open = false">
                            ☀️ Light
                        </button>
                        <button class="w-full text-left px-4 py-2 hover:bg-gray-100" @click="setTheme('dark'); open = false">
                            🌙 Dark
                        </button>
                        <button class="w-full text-left px-4 py-2 hover:bg-gray-100" @click="setTheme('system'); open = false">
                            🖥️ System
                        </button>
                    </div>
                </div>
            </div>

            <!-- User Menu / Auth Links -->
            <?php if (isset($_SESSION['user_id'])): ?>
                <div class="relative" x-data="{ open: false }">
                    <button @click="open = !open" class="bg-secondary px-4 py-2 rounded-lg font-semibold hover:bg-opacity-90 transition">
                        <?= htmlspecialchars($_SESSION['name'] ?? 'User') ?>
                    </button>
                    <div x-show="open" @click.outside="open = false" class="absolute right-0 mt-2 bg-white text-gray-800 rounded-lg shadow-lg overflow-hidden z-40">
                        <a href="<?= BASE_URL ?>/modules/auth/profile.php" class="block px-4 py-2 hover:bg-gray-100">
                            👤 <?= $t['nav_profile'] ?? 'My Profile' ?>
                        </a>
                        <a href="<?= BASE_URL ?>/modules/auth/logout.php" class="block px-4 py-2 hover:bg-gray-100">
                            <?= $t['nav_logout'] ?>
                        </a>
                    </div>
                </div>
            <?php else: ?>
                  <a href="<?= BASE_URL ?>/modules/auth/login.php<?= $langParam ?>" class="btn-primary">
                    <?= $t['login'] ?>
                </a>
            <?php endif; ?>
        </div>
    </nav>

    <!-- Mobile Navigation Menu (Drawer) -->
    <div 
        class="md:hidden bg-primary text-white shadow-lg" 
        x-show="mobileMenuOpen"
        x-transition
        @click.outside="mobileMenuOpen = false"
    >
        <div class="px-6 py-4 space-y-3">
            <?php if (isset($_SESSION['user_id'])): ?>
                <!-- Logged In Mobile Navigation -->
                <a href="/" class="block py-2 hover:text-gray-200 transition" @click="mobileMenuOpen = false"><?= $t['nav_home'] ?></a>
                
                <?php if ($_SESSION['role'] === 'seller'): ?>
                    <a href="<?= BASE_URL ?>/modules/complaints/submit.php<?= $langParam ?>" class="block py-2 hover:text-gray-200 transition" @click="mobileMenuOpen = false"><?= $t['nav_complaints'] ?></a>
                    <a href="<?= BASE_URL ?>/modules/complaints/my_complaints.php<?= $langParam ?>" class="block py-2 hover:text-gray-200 transition" @click="mobileMenuOpen = false">📋 My History</a>
                    <a href="<?= BASE_URL ?>/modules/suggestions/submit.php<?= $langParam ?>" class="block py-2 hover:text-gray-200 transition" @click="mobileMenuOpen = false"><?= $t['nav_suggestions'] ?></a>
                    <a href="<?= BASE_URL ?>/modules/community/report.php<?= $langParam ?>" class="block py-2 hover:text-gray-200 transition" @click="mobileMenuOpen = false"><?= $t['nav_community'] ?></a>
                    <a href="<?= BASE_URL ?>/modules/announcements/list.php<?= $langParam ?>" class="block py-2 hover:text-gray-200 transition" @click="mobileMenuOpen = false"><?= $t['nav_announcements'] ?></a>
                <?php elseif ($_SESSION['role'] === 'manager'): ?>
                    <a href="<?= BASE_URL ?>/modules/analytics/dashboard.php<?= $langParam ?>" class="block py-2 hover:text-gray-200 transition" @click="mobileMenuOpen = false"><?= $t['nav_analytics'] ?? 'Analytics' ?></a>
                    <a href="<?= BASE_URL ?>/modules/complaints/list.php<?= $langParam ?>" class="block py-2 hover:text-gray-200 transition" @click="mobileMenuOpen = false"><?= $t['nav_complaints'] ?></a>
                    <a href="<?= BASE_URL ?>/modules/suggestions/list.php<?= $langParam ?>" class="block py-2 hover:text-gray-200 transition" @click="mobileMenuOpen = false"><?= $t['nav_suggestions'] ?></a>
                    <a href="<?= BASE_URL ?>/modules/announcements/create.php" class="block py-2 hover:text-gray-200 transition" @click="mobileMenuOpen = false"><?= $t['nav_announcements'] ?></a>
                    <a href="<?= BASE_URL ?>/modules/community/list.php" class="block py-2 hover:text-gray-200 transition" @click="mobileMenuOpen = false"><?= $t['nav_community'] ?></a>
                <?php elseif ($_SESSION['role'] === 'admin'): ?>
                    <a href="<?= BASE_URL ?>/modules/admin/overview.php<?= $langParam ?>" class="block py-2 hover:text-gray-200 transition" @click="mobileMenuOpen = false">Admin Overview</a>
                    <a href="<?= BASE_URL ?>/modules/analytics/dashboard.php<?= $langParam ?>" class="block py-2 hover:text-gray-200 transition" @click="mobileMenuOpen = false"><?= $t['nav_analytics'] ?? 'Analytics' ?></a>
                    <a href="<?= BASE_URL ?>/modules/announcements/list.php<?= $langParam ?>" class="block py-2 hover:text-gray-200 transition" @click="mobileMenuOpen = false"><?= $t['nav_announcements'] ?></a>
                <?php endif; ?>

                <hr class="my-2 opacity-30">
                
                <!-- Language Toggle Mobile -->
                <div class="flex gap-4 py-2 items-center">
                    <a href="<?= buildLangUrl('en') ?>" class="<?= ($_SESSION['lang'] ?? 'en') === 'en' ? 'font-bold' : 'opacity-70' ?>" @click="mobileMenuOpen = false">EN</a>
                    <span class="opacity-30">/</span>
                    <a href="<?= buildLangUrl('fr') ?>" class="<?= ($_SESSION['lang'] ?? 'en') === 'fr' ? 'font-bold' : 'opacity-70' ?>" @click="mobileMenuOpen = false">FR</a>

                    <!-- Mobile theme selector dropdown -->
                    <div x-data="{ open: false }" class="relative ml-4">
                        <button
                            id="theme-toggle-mobile"
                            class="text-white opacity-70 hover:opacity-100"
                            aria-label="Change colour theme"
                            @click="open = !open"
                        >🌓</button>
                        <div
                            x-show="open"
                            @click.outside="open = false"
                            x-transition
                            class="absolute right-0 mt-2 w-36 bg-white text-gray-800 rounded-lg shadow-lg z-50"
                        >
                            <button class="w-full text-left px-4 py-2 hover:bg-gray-100" @click="setTheme('light'); open = false">
                                ☀️ Light
                            </button>
                            <button class="w-full text-left px-4 py-2 hover:bg-gray-100" @click="setTheme('dark'); open = false">
                                🌙 Dark
                            </button>
                            <button class="w-full text-left px-4 py-2 hover:bg-gray-100" @click="setTheme('system'); open = false">
                                🖥️ System
                            </button>
                        </div>
                    </div>
                </div>

                <a href="<?= BASE_URL ?>/modules/auth/profile.php" class="block py-2 hover:text-gray-200 transition" @click="mobileMenuOpen = false">
                    👤 <?= $t['nav_profile'] ?? 'My Profile' ?>
                </a>
                <a href="<?= BASE_URL ?>/modules/auth/logout.php" class="block py-2 hover:text-gray-200 transition text-red-200" @click="mobileMenuOpen = false">
                    🚪 <?= $t['nav_logout'] ?>
                </a>
            <?php else: ?>
                <!-- Not Logged In Mobile Navigation -->
                <a href="<?= BASE_URL ?>/modules/complaints/track.php" class="block py-2 hover:text-gray-200 transition" @click="mobileMenuOpen = false"><?= $t['track_complaint'] ?></a>
                <a href="<?= BASE_URL ?>/modules/auth/login.php" class="block py-2 hover:text-gray-200 transition" @click="mobileMenuOpen = false"><?= $t['login'] ?></a>
            <?php endif; ?>
        </div>
    </div>
</div>

<!-- Main Content Wrapper -->
<main class="max-w-6xl mx-auto px-4 py-8">
