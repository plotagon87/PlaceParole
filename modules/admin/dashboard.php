<?php
/**
 * modules/admin/dashboard.php
 * 
 * Super Admin Dashboard
 * Real-time metrics, complaint status, SLA monitoring, system health, activity feed
 */

if (session_status() === PHP_SESSION_NONE) session_start();

require_once '../../config/auth_guard.php';
admin_only();

require_once '../../config/db.php';
require_once '../../config/lang.php';
require_once '../../config/admin_helpers.php';
require_once '../../config/csrf.php';

$widgetConfig = [];
try {
    $stmt = $pdo->prepare("
        SELECT widget_id, is_visible, sort_order 
        FROM dashboard_widget_config 
        WHERE admin_id = ? 
        ORDER BY sort_order ASC
    ");
    $stmt->execute([$_SESSION['user_id']]);
    $widgetRows = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    foreach ($widgetRows as $row) {
        $widgetConfig[$row['widget_id']] = [
            'visible' => (bool)$row['is_visible'],
            'order' => (int)$row['sort_order']
        ];
    }
} catch (Exception $e) {
    error_log("Dashboard widget config error: " . $e->getMessage());
}

if (empty($widgetConfig)) {
    enrollDashboardDefaults($pdo, $_SESSION['user_id']);
}

try {
    $stmt = $pdo->prepare("SELECT COUNT(*) as count FROM users WHERE role='seller' AND is_active=1");
    $stmt->execute();
    $totalSellers = $stmt->fetch(PDO::FETCH_ASSOC)['count'] ?? 0;
    
    $stmt = $pdo->prepare("SELECT COUNT(*) as count FROM users WHERE role='manager' AND is_active=1");
    $stmt->execute();
    $activeManagers = $stmt->fetch(PDO::FETCH_ASSOC)['count'] ?? 0;
    
    $stmt = $pdo->prepare("SELECT COUNT(*) as count FROM complaints WHERE status IN ('pending', 'in_review')");
    $stmt->execute();
    $openComplaints = $stmt->fetch(PDO::FETCH_ASSOC)['count'] ?? 0;
    
    $stmt = $pdo->prepare("SELECT COUNT(*) as count FROM announcements WHERE deleted_at IS NULL AND created_at >= DATE_SUB(NOW(), INTERVAL 30 DAY)");
    $stmt->execute();
    $announcementsSent = $stmt->fetch(PDO::FETCH_ASSOC)['count'] ?? 0;
    
    $stmt = $pdo->prepare("SELECT COUNT(*) as count FROM complaints WHERE sla_deadline < NOW() AND status != 'resolved'");
    $stmt->execute();
    $slaBreached = $stmt->fetch(PDO::FETCH_ASSOC)['count'] ?? 0;
    
    $systemAlerts = getSystemAlertCount($pdo);
    
} catch (Exception $e) {
    error_log("Dashboard metrics error: " . $e->getMessage());
}

require_once '../../templates/header.php';
?>

<div class="flex h-screen bg-gray-50" x-data="dashboardApp()" x-init="initWidgets()">
    <!-- SIDEBAR -->
    <aside class="fixed left-0 top-0 w-60 h-screen bg-white border-r border-gray-200 flex flex-col shadow-sm z-40">
        <div class="px-6 py-4 border-b border-gray-100">
            <h1 class="text-xl font-bold text-green-700">📊 PlaceParole</h1>
            <p class="text-xs text-gray-500 mt-1">Admin Dashboard</p>
        </div>
        
        <nav class="flex-1 px-3 py-4 space-y-2 overflow-y-auto">
            <a href="<?= BASE_URL ?>/modules/admin/dashboard.php" class="flex items-center gap-3 px-3 py-2.5 rounded-lg bg-green-50 text-green-700 font-medium">
                <span>📊</span> Dashboard
            </a>
            <a href="<?= BASE_URL ?>/modules/admin/users.php" class="flex items-center gap-3 px-3 py-2.5 rounded-lg text-gray-700 hover:bg-gray-100 transition">
                <span>👥</span> Users
            </a>
            <a href="<?= BASE_URL ?>/modules/admin/activity_log.php" class="flex items-center gap-3 px-3 py-2.5 rounded-lg text-gray-700 hover:bg-gray-100 transition">
                <span>📋</span> Activity Log
            </a>
            <a href="<?= BASE_URL ?>/modules/admin/system_health.php" class="flex items-center gap-3 px-3 py-2.5 rounded-lg text-gray-700 hover:bg-gray-100 transition">
                <span>⚙️</span> System Health
            </a>
            <hr class="my-3">
            <a href="<?= BASE_URL ?>/index.php" class="flex items-center gap-3 px-3 py-2.5 rounded-lg text-gray-600 hover:bg-gray-100 transition text-sm">
                <span>🏠</span> Back to Site
            </a>
        </nav>
        
        <div class="px-3 py-4 border-t border-gray-100">
            <div class="flex items-center gap-3">
                <div class="w-10 h-10 rounded-full bg-green-100 flex items-center justify-center text-sm font-bold text-green-700">
                    <?= htmlspecialchars(substr($_SESSION['name'], 0, 2)) ?>
                </div>
                <div class="flex-1 min-w-0">
                    <p class="text-sm font-medium text-gray-900 truncate"><?= htmlspecialchars($_SESSION['name']) ?></p>
                    <p class="text-xs text-gray-500">Admin</p>
                </div>
            </div>
        </div>
    </aside>
    
    <!-- MAIN CONTENT -->
    <main class="flex-1 ml-60 flex flex-col overflow-hidden">
        <!-- TOP BAR -->
        <div class="bg-white border-b border-gray-200 px-6 py-4 flex items-center justify-between sticky top-0 z-30">
            <div>
                <h2 class="text-2xl font-bold text-gray-900">Dashboard</h2>
                <p class="text-sm text-gray-500 mt-0.5">Welcome back, <?= htmlspecialchars($_SESSION['name']) ?></p>
            </div>
            
            <div class="flex items-center gap-4">
                <!-- NOTIFICATION BELL -->
                <div class="relative" x-data="{ open: false }">
                    <button @click="open = !open" class="relative p-2 text-gray-600 hover:text-gray-900 focus:outline-none focus:ring-2 focus:ring-green-500 rounded-lg">
                        🔔
                        <?php if ($systemAlerts > 0): ?>
                            <span class="absolute top-0 right-0 w-5 h-5 bg-red-500 text-white text-xs rounded-full flex items-center justify-center font-bold">
                                <?= min($systemAlerts, 9) ?>
                            </span>
                        <?php endif; ?>
                    </button>
                    
                    <div x-show="open" @click.outside="open = false" class="absolute right-0 mt-2 w-80 bg-white rounded-lg shadow-xl border border-gray-200 z-50">
                        <div class="px-4 py-3 border-b border-gray-100">
                            <h3 class="font-semibold text-gray-900">System Alerts</h3>
                        </div>
                        <div class="max-h-96 overflow-y-auto divide-y divide-gray-100">
                            <?php if ($slaBreached > 0): ?>
                                <div class="px-4 py-3 hover:bg-gray-50">
                                    <p class="text-sm font-medium text-gray-900">⏰ SLA Breached</p>
                                    <p class="text-sm text-gray-600 mt-1"><?= $slaBreached ?> complaint(s) past deadline</p>
                                    <a href="<?= BASE_URL ?>/modules/complaints/list.php?status=sla_breach" class="text-xs text-green-600 hover:text-green-700 font-medium mt-2 inline-block">View details →</a>
                                </div>
                            <?php endif; ?>
                            
                            <?php
                            try {
                                $stmt = $pdo->prepare("SELECT COUNT(*) as count FROM system_health_checks WHERE status='error'");
                                $stmt->execute();
                                $healthErrors = $stmt->fetch(PDO::FETCH_ASSOC)['count'] ?? 0;
                                if ($healthErrors > 0):
                            ?>
                                <div class="px-4 py-3 hover:bg-gray-50">
                                    <p class="text-sm font-medium text-gray-900">⚠️ System Errors</p>
                                    <p class="text-sm text-gray-600 mt-1"><?= $healthErrors ?> system check(s) failing</p>
                                    <a href="<?= BASE_URL ?>/modules/admin/system_health.php" class="text-xs text-green-600 hover:text-green-700 font-medium mt-2 inline-block">View details →</a>
                                </div>
                            <?php 
                                endif;
                            } catch (Exception $e) {}
                            ?>
                        </div>
                    </div>
                </div>
                
                <!-- ADMIN MENU -->
                <div class="relative" x-data="{ open: false }">
                    <button @click="open = !open" class="px-3 py-2 text-gray-600 hover:text-gray-900 focus:outline-none focus:ring-2 focus:ring-green-500 rounded-lg">
                        ⋮
                    </button>
                    <div x-show="open" @click.outside="open = false" class="absolute right-0 mt-2 w-48 bg-white rounded-lg shadow-xl border border-gray-200 z-50">
                        <a href="<?= BASE_URL ?>/modules/auth/profile.php" class="block px-4 py-2 text-sm text-gray-700 hover:bg-gray-100">📝 Edit Profile</a>
                        <a href="<?= BASE_URL ?>/modules/auth/logout.php" class="block px-4 py-2 text-sm text-red-600 hover:bg-red-50">🔒 Logout</a>
                    </div>
                </div>
            </div>
        </div>
        
        <!-- CONTENT SCROLL -->
        <div class="flex-1 overflow-y-auto px-6 py-6 space-y-6">
            <!-- METRIC CARDS -->
            <?php if ($widgetConfig['metrics_row']['visible'] ?? true): ?>
            <section class="widget" data-widget-id="metrics_row" x-show="widgetVisible('metrics_row')">
                <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
                    <div class="card">
                        <div class="flex items-start justify-between">
                            <div>
                                <p class="text-sm font-medium text-gray-600">Total Sellers</p>
                                <p class="text-3xl font-bold text-gray-900 mt-2" id="metric-sellers"><?= $totalSellers ?></p>
                            </div>
                            <span class="text-3xl">👤</span>
                        </div>
                        <p class="text-xs text-gray-500 mt-3">Active accounts</p>
                    </div>
                    
                    <div class="card">
                        <div class="flex items-start justify-between">
                            <div>
                                <p class="text-sm font-medium text-gray-600">Active Managers</p>
                                <p class="text-3xl font-bold text-gray-900 mt-2" id="metric-managers"><?= $activeManagers ?></p>
                            </div>
                            <span class="text-3xl">👔</span>
                        </div>
                        <p class="text-xs text-gray-500 mt-3">Market coordinators</p>
                    </div>
                    
                    <div class="card">
                        <div class="flex items-start justify-between">
                            <div>
                                <p class="text-sm font-medium text-gray-600">Open Complaints</p>
                                <p class="text-3xl font-bold text-gray-900 mt-2" id="metric-complaints"><?= $openComplaints ?></p>
                            </div>
                            <span class="text-3xl">🔴</span>
                        </div>
                        <p class="text-xs text-gray-500 mt-3">Pending + In Review</p>
                    </div>
                    
                    <div class="card">
                        <div class="flex items-start justify-between">
                            <div>
                                <p class="text-sm font-medium text-gray-600">Announcements</p>
                                <p class="text-3xl font-bold text-gray-900 mt-2" id="metric-announcements"><?= $announcementsSent ?></p>
                            </div>
                            <span class="text-3xl">📢</span>
                        </div>
                        <p class="text-xs text-gray-500 mt-3">Last 30 days</p>
                    </div>
                </div>
            </section>
            <?php endif; ?>
            
            <!-- SLA ALERT -->
            <?php if (($widgetConfig['sla_alert']['visible'] ?? true) && $slaBreached > 0): ?>
            <section class="widget" data-widget-id="sla_alert" x-show="widgetVisible('sla_alert')">
                <div class="bg-red-50 border-l-4 border-red-500 p-4 rounded-lg flex items-start gap-4">
                    <span class="text-3xl">⏰</span>
                    <div class="flex-1">
                        <h3 class="font-bold text-red-900">SLA Breach Alert</h3>
                        <p class="text-sm text-red-700 mt-1"><?= $slaBreached ?> complaint(s) have exceeded their SLA deadline and require immediate attention.</p>
                        <a href="<?= BASE_URL ?>/modules/complaints/list.php?status=sla_breach" class="inline-block mt-2 px-3 py-1.5 bg-red-600 text-white text-xs font-medium rounded hover:bg-red-700">View Overdue →</a>
                    </div>
                </div>
            </section>
            <?php endif; ?>
            
            <!-- COMPLAINT DONUT -->
            <?php if ($widgetConfig['complaint_donut']['visible'] ?? true): ?>
            <section class="widget" data-widget-id="complaint_donut" x-show="widgetVisible('complaint_donut')">
                <div class="card">
                    <h3 class="text-lg font-semibold text-gray-900 mb-4">Complaint Status Distribution</h3>
                    <canvas id="complaint-donut-canvas" width="400" height="300" style="width: 100%; height: auto;"></canvas>
                </div>
            </section>
            <?php endif; ?>
            
            <!-- TOP MARKETS -->
            <?php if ($widgetConfig['top_markets']['visible'] ?? true): ?>
            <section class="widget" data-widget-id="top_markets" x-show="widgetVisible('top_markets')">
                <div class="card">
                    <h3 class="text-lg font-semibold text-gray-900 mb-4">Top 5 Markets by Complaints</h3>
                    <div class="overflow-x-auto">
                        <table class="w-full text-sm">
                            <thead>
                                <tr class="border-b border-gray-200">
                                    <th class="text-left py-3 px-4 font-semibold text-gray-700">Market</th>
                                    <th class="text-center py-3 px-4 font-semibold text-gray-700">Location</th>
                                    <th class="text-center py-3 px-4 font-semibold text-gray-700">Total</th>
                                    <th class="text-center py-3 px-4 font-semibold text-gray-700">Pending</th>
                                    <th class="text-center py-3 px-4 font-semibold text-gray-700">Resolution %</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php
                                try {
                                    $stmt = $pdo->prepare("
                                        SELECT 
                                            m.id, m.name, m.location,
                                            COUNT(c.id) as total,
                                            SUM(CASE WHEN c.status IN ('pending', 'in_review') THEN 1 ELSE 0 END) as pending,
                                            SUM(CASE WHEN c.status = 'resolved' THEN 1 ELSE 0 END) as resolved
                                        FROM markets m
                                        LEFT JOIN complaints c ON c.market_id = m.id
                                        GROUP BY m.id, m.name, m.location
                                        ORDER BY total DESC
                                        LIMIT 5
                                    ");
                                    $stmt->execute();
                                    $topMarkets = $stmt->fetchAll(PDO::FETCH_ASSOC);
                                    
                                    foreach ($topMarkets as $market):
                                        $total = (int)$market['total'];
                                        $resolved = (int)$market['resolved'];
                                        $resolutionRate = $total > 0 ? round(($resolved / $total) * 100) : 0;
                                        $rateColor = $resolutionRate < 50 ? 'red' : ($resolutionRate < 80 ? 'yellow' : 'green');
                                ?>
                                <tr class="border-b border-gray-100 hover:bg-gray-50">
                                    <td class="py-3 px-4 font-medium text-gray-900"><?= htmlspecialchars($market['name']) ?></td>
                                    <td class="py-3 px-4 text-gray-600 text-center"><?= htmlspecialchars($market['location']) ?></td>
                                    <td class="py-3 px-4 text-center font-semibold text-gray-900"><?= $total ?></td>
                                    <td class="py-3 px-4 text-center text-red-600 font-medium"><?= (int)$market['pending'] ?></td>
                                    <td class="py-3 px-4 text-center">
                                        <span class="px-2.5 py-1 rounded-full text-xs font-bold
                                            <?php
                                            if ($rateColor === 'red') echo 'bg-red-100 text-red-700';
                                            elseif ($rateColor === 'yellow') echo 'bg-yellow-100 text-yellow-700';
                                            else echo 'bg-green-100 text-green-700';
                                            ?>
                                        ">
                                            <?= $resolutionRate ?>%
                                        </span>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                                <?php
                                } catch (Exception $e) {
                                    error_log("Top markets query error: " . $e->getMessage());
                                    echo '<tr><td colspan="5" class="py-4 text-center text-gray-500">Error loading data</td></tr>';
                                }
                                ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </section>
            <?php endif; ?>
            
            <!-- GROWTH CHART -->
            <?php if ($widgetConfig['growth_chart']['visible'] ?? true): ?>
            <section class="widget" data-widget-id="growth_chart" x-show="widgetVisible('growth_chart')">
                <div class="card">
                    <h3 class="text-lg font-semibold text-gray-900 mb-4">Platform Growth (Last 6 Months)</h3>
                    <canvas id="growth-chart-canvas" width="800" height="320" style="width: 100%; height: auto;"></canvas>
                </div>
            </section>
            <?php endif; ?>
            
            <!-- ACTIVITY FEED -->
            <?php if ($widgetConfig['activity_feed']['visible'] ?? true): ?>
            <section class="widget" data-widget-id="activity_feed" x-show="widgetVisible('activity_feed')">
                <div class="card">
                    <h3 class="text-lg font-semibold text-gray-900 mb-4">Recent Admin Activity</h3>
                    <div id="activity-feed-list" class="space-y-0">
                        <?php
                        try {
                            $stmt = $pdo->prepare("
                                SELECT 
                                    aal.id, aal.action_type, aal.subject_type, aal.created_at,
                                    u.name as actor_name
                                FROM admin_activity_log aal
                                LEFT JOIN users u ON u.id = aal.actor_id
                                ORDER BY aal.created_at DESC
                                LIMIT 20
                            ");
                            $stmt->execute();
                            $activityEntries = $stmt->fetchAll(PDO::FETCH_ASSOC);
                            
                            foreach ($activityEntries as $entry):
                                $relativeTime = getRelativeTime($entry['created_at']);
                                $initials = substr($entry['actor_name'] ?? 'System', 0, 2);
                        ?>
                        <div class="flex gap-3 py-3 border-b border-gray-100 activity-entry" data-activity-id="<?= htmlspecialchars($entry['id']) ?>">
                            <div class="w-10 h-10 rounded-full bg-green-100 flex items-center justify-center text-sm font-bold text-green-700 flex-shrink-0">
                                <?= htmlspecialchars($initials) ?>
                            </div>
                            <div class="flex-1 min-w-0">
                                <p class="text-sm">
                                    <strong><?= htmlspecialchars($entry['actor_name'] ?? 'System') ?></strong>
                                    <span class="text-gray-600"><?= formatActionDescription($entry['action_type']) ?></span>
                                </p>
                                <p class="text-xs text-gray-500 mt-0.5"><?= $relativeTime ?></p>
                            </div>
                        </div>
                        <?php endforeach; ?>
                        <?php
                        } catch (Exception $e) {
                            error_log("Activity feed error: " . $e->getMessage());
                            echo '<p class="text-gray-500 text-center py-4">Error loading activity</p>';
                        }
                        ?>
                    </div>
                </div>
            </section>
            <?php endif; ?>
            
            <!-- HEALTH PILL -->
            <?php if ($widgetConfig['health_pill']['visible'] ?? true): ?>
            <section class="widget" data-widget-id="health_pill" x-show="widgetVisible('health_pill')">
                <?php
                try {
                    $stmt = $pdo->prepare("
                        SELECT 
                            COUNT(*) as total,
                            SUM(CASE WHEN status='ok' THEN 1 ELSE 0 END) as ok_count,
                            SUM(CASE WHEN status='error' THEN 1 ELSE 0 END) as error_count
                        FROM system_health_checks
                    ");
                    $stmt->execute();
                    $health = $stmt->fetch(PDO::FETCH_ASSOC);
                    $healthStatus = ($health['error_count'] ?? 0) > 0 ? 'error' : 'ok';
                    $healthColor = $healthStatus === 'error' ? 'bg-red-100 text-red-700' : 'bg-green-100 text-green-700';
                    $healthText = $healthStatus === 'error' ? '⚠️ System Errors' : '✅ All Systems Operational';
                ?>
                <div class="card">
                    <div class="flex items-center justify-between">
                        <div>
                            <h3 class="text-lg font-semibold text-gray-900">System Health</h3>
                            <p class="text-sm text-gray-600 mt-1"><?= ($health['ok_count'] ?? 0) ?>/<?= ($health['total'] ?? 7) ?> checks passing</p>
                        </div>
                        <div class="px-4 py-2 rounded-full <?= $healthColor ?> font-bold text-sm">
                            <?= $healthText ?>
                        </div>
                    </div>
                    <a href="<?= BASE_URL ?>/modules/admin/system_health.php" class="mt-3 inline-block text-sm text-green-600 hover:text-green-700 font-medium">View Details →</a>
                </div>
                <?php
                } catch (Exception $e) {
                    error_log("Health status error: " . $e->getMessage());
                }
                ?>
            </section>
            <?php endif; ?>
        </div>
    </main>
    
    <!-- WIDGET CONTROL PANEL -->
    <div class="fixed right-6 bottom-6 z-40" x-data="{ panelOpen: false }">
        <button @click="panelOpen = !panelOpen" class="w-14 h-14 rounded-full bg-green-600 text-white shadow-lg hover:bg-green-700 flex items-center justify-center font-bold text-xl focus:outline-none focus:ring-2 focus:ring-green-400">
            ⚙️
        </button>
        
        <div x-show="panelOpen" @click.outside="panelOpen = false" class="absolute bottom-16 right-0 w-80 bg-white rounded-lg shadow-2xl border border-gray-200 max-h-96 overflow-y-auto">
            <div class="px-4 py-3 border-b border-gray-100 sticky top-0 bg-white">
                <h3 class="font-semibold text-gray-900">Dashboard Widgets</h3>
                <p class="text-xs text-gray-600 mt-1">Show/hide and reorder your dashboard</p>
            </div>
            
            <div class="p-4 space-y-2">
                <label class="flex items-center gap-3 cursor-pointer py-2 hover:bg-gray-50 px-2 rounded">
                    <input type="checkbox" x-model="widgets.metrics_row" @change="toggleWidget('metrics_row')" class="w-4 h-4 accent-green-600">
                    <span class="text-sm font-medium text-gray-700">Metric Cards</span>
                </label>
                
                <label class="flex items-center gap-3 cursor-pointer py-2 hover:bg-gray-50 px-2 rounded">
                    <input type="checkbox" x-model="widgets.complaint_donut" @change="toggleWidget('complaint_donut')" class="w-4 h-4 accent-green-600">
                    <span class="text-sm font-medium text-gray-700">Complaint Chart</span>
                </label>
                
                <label class="flex items-center gap-3 cursor-pointer py-2 hover:bg-gray-50 px-2 rounded">
                    <input type="checkbox" x-model="widgets.sla_alert" @change="toggleWidget('sla_alert')" class="w-4 h-4 accent-green-600">
                    <span class="text-sm font-medium text-gray-700">SLA Alert</span>
                </label>
                
                <label class="flex items-center gap-3 cursor-pointer py-2 hover:bg-gray-50 px-2 rounded">
                    <input type="checkbox" x-model="widgets.top_markets" @change="toggleWidget('top_markets')" class="w-4 h-4 accent-green-600">
                    <span class="text-sm font-medium text-gray-700">Top Markets</span>
                </label>
                
                <label class="flex items-center gap-3 cursor-pointer py-2 hover:bg-gray-50 px-2 rounded">
                    <input type="checkbox" x-model="widgets.growth_chart" @change="toggleWidget('growth_chart')" class="w-4 h-4 accent-green-600">
                    <span class="text-sm font-medium text-gray-700">Growth Chart</span>
                </label>
                
                <label class="flex items-center gap-3 cursor-pointer py-2 hover:bg-gray-50 px-2 rounded">
                    <input type="checkbox" x-model="widgets.activity_feed" @change="toggleWidget('activity_feed')" class="w-4 h-4 accent-green-600">
                    <span class="text-sm font-medium text-gray-700">Activity Feed</span>
                </label>
                
                <label class="flex items-center gap-3 cursor-pointer py-2 hover:bg-gray-50 px-2 rounded">
                    <input type="checkbox" x-model="widgets.health_pill" @change="toggleWidget('health_pill')" class="w-4 h-4 accent-green-600">
                    <span class="text-sm font-medium text-gray-700">Health Status</span>
                </label>
            </div>
            
            <div class="px-4 py-3 border-t border-gray-100">
                <button @click="resetWidgets()" class="w-full px-3 py-2 bg-gray-200 text-gray-800 text-sm font-medium rounded hover:bg-gray-300 transition">
                    Reset to Defaults
                </button>
            </div>
        </div>
    </div>
</div>

<script defer src="<?= BASE_URL ?>/assets/js/admin_dashboard.js"></script>

<?php
function getRelativeTime($timestamp) {
    $now = new DateTime();
    $created = new DateTime($timestamp);
    $diff = $now->diff($created);
    
    if ($diff->d > 0) return $diff->d . 'd ago';
    if ($diff->h > 0) return $diff->h . 'h ago';
    if ($diff->i > 0) return $diff->i . 'm ago';
    return 'just now';
}

function formatActionDescription($actionType) {
    $descriptions = [
        'user_created' => 'created a user account',
        'user_updated' => 'updated a user account',
        'user_deactivated' => 'deactivated a user',
        'user_reactivated' => 'reactivated a user',
        'login' => 'logged in',
        'complaint_status_changed' => 'updated complaint status',
        'system_error' => 'triggered a system error'
    ];
    
    return $descriptions[$actionType] ?? 'performed an action';
}

require_once '../../templates/footer.php';
?>
