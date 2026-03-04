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
    
    <style>
        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, 'Helvetica Neue', Arial, sans-serif;
        }
        .btn-primary {
            @apply bg-primary text-white px-4 py-2 rounded-lg font-semibold hover:bg-opacity-90 transition;
        }
        .btn-secondary {
            @apply bg-secondary text-white px-4 py-2 rounded-lg font-semibold hover:bg-opacity-90 transition;
        }
        .btn-outlined {
            @apply border-2 border-primary text-primary px-4 py-2 rounded-lg font-semibold hover:bg-primary hover:text-white transition;
        }
        .input-field {
            @apply w-full border border-gray-300 rounded-lg px-3 py-2 focus:outline-none focus:ring-2 focus:ring-primary;
        }
        .card {
            @apply bg-white rounded-2xl shadow-md p-6;
        }
        .status-badge {
            @apply px-3 py-1 rounded-full text-xs font-bold inline-block;
        }
        .status-pending {
            @apply status-badge bg-red-100 text-red-700;
        }
        .status-in-review {
            @apply status-badge bg-yellow-100 text-yellow-700;
        }
        .status-resolved {
            @apply status-badge bg-green-100 text-green-700;
        }
    </style>
</head>
<body class="bg-gray-50 text-gray-800 min-h-screen">

<!-- Navigation Bar -->
<nav class="bg-primary text-white px-6 py-4 flex items-center justify-between shadow-md sticky top-0 z-50">
    <!-- Logo / App Name -->
    <div class="flex items-center gap-3">
        <a href="/" class="text-2xl font-bold text-white hover:text-gray-200 transition">
            <?= $t['app_name'] ?>
        </a>
    </div>

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

    <!-- Right Side: Language & User Menu -->
    <div class="flex items-center gap-4">
        <!-- Language Toggle -->
        <div class="flex gap-2">
            <a href="?lang=en" class="<?= ($_SESSION['lang'] ?? 'en') === 'en' ? 'font-bold' : 'opacity-70 hover:opacity-100' ?> transition">EN</a>
            <span class="opacity-70">/</span>
            <a href="?lang=fr" class="<?= ($_SESSION['lang'] ?? 'en') === 'fr' ? 'font-bold' : 'opacity-70 hover:opacity-100' ?> transition">FR</a>
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

<!-- Main Content Wrapper -->
<main class="max-w-6xl mx-auto px-4 py-8">
