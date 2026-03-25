<?php
/**
 * index.php
 * Home page for PlaceParole
 */
require_once 'templates/header.php';
require_once 'config/db.php';

// Preserve the current language setting in URL queries so navigation keeps the locale
$langParam = isset($_SESSION['lang']) ? '?lang=' . $_SESSION['lang'] : '';
?>

<div class="bg-gradient-to-br from-primary to-green-800 text-white px-6 py-12 rounded-2xl mb-10">
    <h1 class="text-4xl font-bold mb-3"><?= $t['app_name'] ?></h1>
    <p class="text-xl text-gray-100 mb-6"><?= $t['app_tagline'] ?></p>
    
    <?php if (!isset($_SESSION['user_id'])): ?>
        <!-- Not logged in -->
        <div class="flex gap-4 flex-wrap">
            <a href="<?= BASE_URL ?>/modules/auth/register_seller.php<?= $langParam ?>" class="btn-secondary px-6 py-3 text-lg">
                📝 <?= $t['register'] ?> (<?= $t['seller'] ?>)
            </a>
            <a href="<?= BASE_URL ?>/modules/auth/register_manager.php<?= $langParam ?>" class="bg-white text-primary px-6 py-3 rounded-lg font-semibold hover:bg-gray-100 transition text-lg">
                🏪 <?= $t['register_market'] ?>
            </a>
            <a href="<?= BASE_URL ?>/modules/complaints/submit_public.php<?= $langParam ?>" class="border-2 border-white text-white px-6 py-3 rounded-lg font-semibold hover:bg-white hover:text-primary transition text-lg">
                📢 Submit Complaint
            </a>
            <a href="<?= BASE_URL ?>/modules/complaints/track.php<?= $langParam ?>" class="border-2 border-white text-white px-6 py-3 rounded-lg font-semibold hover:bg-white hover:text-primary transition text-lg">
                🔍 <?= $t['track_complaint'] ?>
            </a>
        </div>
    <?php else: ?>
        <!-- Logged in - Show role-specific welcome -->
        <?php if ($_SESSION['role'] === 'seller'): ?>
            <p class="text-lg mb-4">👋 Welcome, <?= htmlspecialchars($_SESSION['name'] ?? 'Seller') ?>!</p>
            <p class="text-gray-100 mb-4">Get started by submitting your first complaint or suggestion below.</p>
        <?php elseif ($_SESSION['role'] === 'manager'): ?>
            <p class="text-lg mb-4">👋 Welcome, <?= htmlspecialchars($_SESSION['name'] ?? 'Manager') ?>!</p>
            <p class="text-gray-100 mb-4">Access your analytics dashboard to view market insights and manage complaints.</p>
            <a href="modules/analytics/dashboard.php" class="inline-block bg-white text-primary px-6 py-3 rounded-lg font-semibold hover:bg-gray-100 transition">
                📊 Go to Analytics Dashboard
            </a>
        <?php else: ?>
            <p class="text-lg">👋 <?= $t['login_success'] ?></p>
        <?php endif; ?>
    <?php endif; ?>
</div>

<!-- Main Content -->
<div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6 mb-12">
    
    <!-- Feature 1: Submit Complaint -->
    <div class="card hover:shadow-lg transition">
        <div class="text-4xl mb-3">📢</div>
        <h2 class="text-xl font-bold mb-2 text-primary"><?= $t['submit_complaint'] ?></h2>
        <p class="text-gray-600 mb-4">
            Voice your concerns to market management. Get a reference code to track your complaint status in real-time.
        </p>
        <?php
        $submit_link = (isset($_SESSION['user_id']) && $_SESSION['role'] === 'seller') ? 'modules/complaints/submit.php' : 'modules/complaints/submit_public.php';
        ?>
        <a href="<?= $submit_link ?>" class="text-primary font-semibold hover:underline">
            → <?= $t['submit_complaint'] ?>
        </a>
    </div>

    <!-- Feature 2: Track Complaint -->
    <div class="card hover:shadow-lg transition">
        <div class="text-4xl mb-3">🔍</div>
        <h2 class="text-xl font-bold mb-2 text-primary"><?= $t['track_complaint'] ?></h2>
        <p class="text-gray-600 mb-4">
            Use your reference code to check the current status of your complaint — from submitted to resolved.
        </p>
        <a href="modules/complaints/track.php" class="text-primary font-semibold hover:underline">
            → <?= $t['track_complaint'] ?>
        </a>
    </div>

    <!-- Feature 3: Submit Suggestion -->
    <div class="card hover:shadow-lg transition">
        <div class="text-4xl mb-3">💡</div>
        <h2 class="text-xl font-bold mb-2 text-primary"><?= $t['submit_suggestion'] ?></h2>
        <p class="text-gray-600 mb-4">
            Have an idea to improve the market? Share your innovation suggestions with market management.
        </p>
        <?php if (isset($_SESSION['user_id']) && $_SESSION['role'] === 'seller'): ?>
            <a href="modules/suggestions/submit.php" class="text-primary font-semibold hover:underline">
                → <?= $t['submit_suggestion'] ?>
            </a>
        <?php endif; ?>
    </div>

    <!-- Feature 4: Community Support -->
    <div class="card hover:shadow-lg transition">
        <div class="text-4xl mb-3">🤝</div>
        <h2 class="text-xl font-bold mb-2 text-primary"><?= $t['report_event'] ?></h2>
        <p class="text-gray-600 mb-4">
            Report life events (death, illness, emergency). Bring the market community together for mutual support.
        </p>
        <?php if (isset($_SESSION['user_id']) && $_SESSION['role'] === 'seller'): ?>
            <a href="modules/community/report.php" class="text-primary font-semibold hover:underline">
                → <?= $t['report_event'] ?>
            </a>
        <?php endif; ?>
    </div>

    <!-- Feature 5: Announcements -->
    <div class="card hover:shadow-lg transition">
        <div class="text-4xl mb-3">📣</div>
        <h2 class="text-xl font-bold mb-2 text-primary"><?= $t['announcements'] ?></h2>
        <p class="text-gray-600 mb-4">
            Stay updated with official market announcements, rules, and important information.
        </p>
        <?php if (isset($_SESSION['user_id'])): ?>
            <a href="modules/announcements/list.php" class="text-primary font-semibold hover:underline">
                → <?= $t['announcements'] ?>
            </a>
        <?php endif; ?>
    </div>

    <!-- Feature 6: Manager Dashboard -->
    <?php if (isset($_SESSION['user_id']) && $_SESSION['role'] === 'manager'): ?>
    <div class="card hover:shadow-lg transition bg-secondary text-white">
        <div class="text-4xl mb-3">📊</div>
        <h2 class="text-xl font-bold mb-2">Manager Analytics</h2>
        <p class="mb-4">
            View detailed market analytics, complaint trends, and performance metrics. Manage all submissions in one place.
        </p>
        <a href="modules/analytics/dashboard.php" class="text-white font-semibold hover:underline">
            → Go to Analytics
        </a>
    </div>
    <?php endif; ?>
</div>

<!-- About Section -->
<div class="bg-white rounded-2xl shadow p-8 mb-10">
    <h2 class="text-2xl font-bold text-primary mb-4">About PlaceParole</h2>
    <p class="text-gray-700 leading-relaxed mb-4">
        <strong>PlaceParole</strong> is a Market Feedback & Communication Platform designed for the small market squares of Cameroon.
    </p>
    <p class="text-gray-700 leading-relaxed mb-4">
        Market sellers now have a <strong>structured system</strong> to:
    </p>
    <ul class="list-disc list-inside text-gray-700 space-y-2 mb-4">
        <li>Submit complaints about market conditions (infrastructure, sanitation, security)</li>
        <li>Propose innovations and improvements to their market</li>
        <li>Report and support community events (deaths, illnesses, emergencies)</li>
        <li>Receive official announcements via web, SMS, and email</li>
        <li>Track the status of their complaints in real-time</li>
    </ul>
    <p class="text-gray-700 leading-relaxed">
        <strong>Market managers</strong> use PlaceParole to listen to sellers, respond to complaints, broadcast announcements, 
        and build a more organized, responsive market community.
    </p>
</div>

<?php require_once 'templates/footer.php'; ?>
