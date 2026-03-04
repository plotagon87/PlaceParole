<?php
/**
 * templates/header.php
 * Include at the very top of every page with: require_once '../templates/header.php';
 * Adjust the path (../) depending on how deep the file is in the folder structure
 */

if (session_status() === PHP_SESSION_NONE) session_start();
require_once __DIR__ . '/../config/db.php'; // Load BASE_URL constant
require_once __DIR__ . '/../config/lang.php'; // Load language system — $t is now available
?><!DOCTYPE html>
<html lang="<?= $_SESSION['lang'] ?? 'en' ?>">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= $t['app_name'] ?> — <?= $t['app_tagline'] ?></title>
    
    <!-- Tailwind CSS via CDN -->
    <script src="https://cdn.tailwindcss.com"></script>
    
    <!-- Custom Tailwind Configuration -->
    <script>
        tailwind.config = {
            theme: {
                extend: {
                    colors: {
                        // PlaceParole Color Palette - Cameroon Colors (Green & Orange)
                        primary: '#22863a',    // Dark Green
                        secondary: '#ff8c00',  // Orange
                        accent: '#fbbf24',     // Amber
                    }
                }
            }
        }
    </script>
    
    <!-- Alpine.js for interactive components -->
    <script defer src="https://cdn.jsdelivr.net/npm/alpinejs@3.x.x/dist/cdn.min.js"></script>

    <!-- App.js utilities -->
    <script src="<?= BASE_URL ?>/assets/js/app.js"></script>
    
    <style>
        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, 'Helvetica Neue', Arial, sans-serif;
        }

        /* Button Styles */
        .btn-primary {
            background-color: #22863a;
            color: white;
            padding: 0.5rem 1rem;
            border-radius: 0.5rem;
            font-weight: 600;
            transition: opacity 0.2s;
            display: inline-block;
            text-decoration: none;
            border: none;
            cursor: pointer;
        }
        .btn-primary:hover { opacity: 0.9; }

        .btn-secondary {
            background-color: #ff8c00;
            color: white;
            padding: 0.5rem 1rem;
            border-radius: 0.5rem;
            font-weight: 600;
            transition: opacity 0.2s;
            display: inline-block;
            text-decoration: none;
            border: none;
            cursor: pointer;
        }
        .btn-secondary:hover { opacity: 0.9; }

        .btn-outlined {
            border: 2px solid #22863a;
            color: #22863a;
            padding: 0.5rem 1rem;
            border-radius: 0.5rem;
            font-weight: 600;
            transition: all 0.2s;
            display: inline-block;
            text-decoration: none;
            background: transparent;
            cursor: pointer;
        }
        .btn-outlined:hover {
            background-color: #22863a;
            color: white;
        }

        /* Input Fields */
        .input-field {
            width: 100%;
            border: 1px solid #d1d5db;
            border-radius: 0.5rem;
            padding: 0.5rem 0.75rem;
            font-size: 1rem;
            transition: all 0.2s;
            box-sizing: border-box;
        }
        .input-field:focus {
            outline: none;
            ring: 2px #22863a;
            border-color: #22863a;
            box-shadow: 0 0 0 2px rgba(34, 134, 58, 0.1);
        }

        /* Cards */
        .card {
            background-color: white;
            border-radius: 1rem;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
            padding: 1.5rem;
        }

        /* Status Badges */
        .status-badge {
            padding: 0.25rem 0.75rem;
            border-radius: 9999px;
            font-size: 0.75rem;
            font-weight: 700;
            display: inline-block;
        }

        .status-pending {
            background-color: #fee2e2;
            color: #991b1b;
        }

        .status-in-review {
            background-color: #fef3c7;
            color: #92400e;
        }

        .status-resolved {
            background-color: #dcfce7;
            color: #166534;
        }

        /* Dark Mode */
        @media (prefers-color-scheme: dark) {
            .card { background-color: #2a2a2a; color: #f5f5f5; }
        }
    </style>
</head>
<body class="bg-gray-50 text-gray-800 min-h-screen">

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
                    <a href="<?= BASE_URL ?>/modules/suggestions/submit.php" class="hover:text-gray-200 transition"><?= $t['nav_suggestions'] ?></a>
                    <a href="<?= BASE_URL ?>/modules/community/report.php" class="hover:text-gray-200 transition"><?= $t['nav_community'] ?></a>
                    <a href="<?= BASE_URL ?>/modules/announcements/list.php" class="hover:text-gray-200 transition"><?= $t['nav_announcements'] ?></a>
                <?php elseif ($_SESSION['role'] === 'manager'): ?>
                    <a href="<?= BASE_URL ?>/modules/analytics/dashboard.php" class="hover:text-gray-200 transition"><?= $t['nav_analytics'] ?? 'Analytics' ?></a>
                    <a href="<?= BASE_URL ?>/modules/complaints/list.php" class="hover:text-gray-200 transition"><?= $t['nav_complaints'] ?></a>
                    <a href="<?= BASE_URL ?>/modules/suggestions/list.php" class="hover:text-gray-200 transition"><?= $t['nav_suggestions'] ?></a>
                    <a href="<?= BASE_URL ?>/modules/announcements/create.php" class="hover:text-gray-200 transition"><?= $t['nav_announcements'] ?></a>
                    <a href="<?= BASE_URL ?>/modules/community/list.php" class="hover:text-gray-200 transition"><?= $t['nav_community'] ?></a>
                <?php endif; ?>
            <?php else: ?>
                <!-- Not Logged In Navigation -->
                <a href="<?= BASE_URL ?>/modules/complaints/track.php" class="hover:text-gray-200 transition"><?= $t['track_complaint'] ?></a>
            <?php endif; ?>
        </div>

        <!-- Right Side: Language & User Menu (Desktop only) -->
        <div class="hidden md:flex items-center gap-4">
            <!-- Language Toggle -->
            <div class="flex gap-2">
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
            </div>

            <!-- User Menu / Auth Links -->
            <?php if (isset($_SESSION['user_id'])): ?>
                <div class="relative" x-data="{ open: false }">
                    <button @click="open = !open" class="bg-secondary px-4 py-2 rounded-lg font-semibold hover:bg-opacity-90 transition">
                        <?= htmlspecialchars($_SESSION['name'] ?? 'User') ?>
                    </button>
                    <div x-show="open" @click.outside="open = false" class="absolute right-0 mt-2 bg-white text-gray-800 rounded-lg shadow-lg overflow-hidden z-40">
                        <a href="<?= BASE_URL ?>/modules/auth/logout.php" class="block px-4 py-2 hover:bg-gray-100">
                            <?= $t['nav_logout'] ?>
                        </a>
                    </div>
                </div>
            <?php else: ?>
                  <a href="<?= BASE_URL ?>/modules/auth/login.php" class="btn-primary">
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
                    <a href="<?= BASE_URL ?>/modules/complaints/submit.php" class="block py-2 hover:text-gray-200 transition" @click="mobileMenuOpen = false"><?= $t['nav_complaints'] ?></a>
                    <a href="<?= BASE_URL ?>/modules/suggestions/submit.php" class="block py-2 hover:text-gray-200 transition" @click="mobileMenuOpen = false"><?= $t['nav_suggestions'] ?></a>
                    <a href="<?= BASE_URL ?>/modules/community/report.php" class="block py-2 hover:text-gray-200 transition" @click="mobileMenuOpen = false"><?= $t['nav_community'] ?></a>
                    <a href="<?= BASE_URL ?>/modules/announcements/list.php" class="block py-2 hover:text-gray-200 transition" @click="mobileMenuOpen = false"><?= $t['nav_announcements'] ?></a>
                <?php elseif ($_SESSION['role'] === 'manager'): ?>
                    <a href="<?= BASE_URL ?>/modules/analytics/dashboard.php" class="block py-2 hover:text-gray-200 transition" @click="mobileMenuOpen = false"><?= $t['nav_analytics'] ?? 'Analytics' ?></a>
                    <a href="<?= BASE_URL ?>/modules/complaints/list.php" class="block py-2 hover:text-gray-200 transition" @click="mobileMenuOpen = false"><?= $t['nav_complaints'] ?></a>
                    <a href="<?= BASE_URL ?>/modules/suggestions/list.php" class="block py-2 hover:text-gray-200 transition" @click="mobileMenuOpen = false"><?= $t['nav_suggestions'] ?></a>
                    <a href="<?= BASE_URL ?>/modules/announcements/create.php" class="block py-2 hover:text-gray-200 transition" @click="mobileMenuOpen = false"><?= $t['nav_announcements'] ?></a>
                    <a href="<?= BASE_URL ?>/modules/community/list.php" class="block py-2 hover:text-gray-200 transition" @click="mobileMenuOpen = false"><?= $t['nav_community'] ?></a>
                <?php endif; ?>

                <hr class="my-2 opacity-30">
                
                <!-- Language Toggle Mobile -->
                <div class="flex gap-4 py-2">
                    <a href="<?= buildLangUrl('en') ?>" class="<?= ($_SESSION['lang'] ?? 'en') === 'en' ? 'font-bold' : 'opacity-70' ?>" @click="mobileMenuOpen = false">EN</a>
                    <span class="opacity-30">/</span>
                    <a href="<?= buildLangUrl('fr') ?>" class="<?= ($_SESSION['lang'] ?? 'en') === 'fr' ? 'font-bold' : 'opacity-70' ?>" @click="mobileMenuOpen = false">FR</a>
                </div>

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
