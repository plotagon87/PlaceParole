<?php
/**
 * config/admin_helpers.php
 * 
 * Shared helper functions for admin module:
 * - Activity logging
 * - System alerts
 * - Metrics queries
 * - Dashboard defaults
 * 
 * Used by: modules/admin/*, modules/auth/login.php, modules/complaints/respond.php
 */

// ========== ALERT THRESHOLDS (Configurable) ==========
define('ALERT_SLA_BREACH_THRESHOLD', 1);           // Number of breached complaints to trigger alert
define('ALERT_SLA_EXPIRING_HOURS', 24);             // Hours until deadline triggers "expiring soon"
define('ALERT_HIGH_PENDING_THRESHOLD', 50);         // Pending complaint count for alert
define('ALERT_NEW_USER_DAILY_THRESHOLD', 10);       // New users per day for notification

// ========== LOG ADMIN ACTION ==========
/**
 * Log an administrative action to the audit trail
 * 
 * @param PDO $pdo Database connection
 * @param int $actorId users.id of who performed the action
 * @param string $actionType Action category (e.g., 'user_created', 'login', 'complaint_status_changed')
 * @param string|null $subjectType Entity type affected (e.g., 'user', 'complaint')
 * @param int|null $subjectId ID of affected entity
 * @param array $details Optional JSON metadata (old values, new values, reason, etc.)
 * @param int|null $marketId Market context (default: NULL for platform-level actions)
 * @return bool Success status
 */
function logAdminAction(PDO $pdo, int $actorId, string $actionType, ?string $subjectType = null, ?int $subjectId = null, array $details = [], ?int $marketId = null): bool {
    try {
        $ipAddress = $_SERVER['HTTP_X_FORWARDED_FOR'] ?? $_SERVER['REMOTE_ADDR'] ?? null;
        $userAgent = $_SERVER['HTTP_USER_AGENT'] ?? null;
        $detailsJson = !empty($details) ? json_encode($details) : null;
        
        $stmt = $pdo->prepare("
            INSERT INTO admin_activity_log 
            (market_id, actor_id, action_type, subject_type, subject_id, ip_address, user_agent, details, created_at)
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, NOW())
        ");
        
        return $stmt->execute([$marketId, $actorId, $actionType, $subjectType, $subjectId, $ipAddress, $userAgent, $detailsJson]);
    } catch (Exception $e) {
        error_log("logAdminAction error: " . $e->getMessage());
        return false;
    }
}

// ========== GET SYSTEM ALERT COUNT ==========
/**
 * Get count of active system alerts for dashboard notification badge
 * Combines: SLA breaches + system health errors
 * 
 * @param PDO $pdo Database connection
 * @return int Total alert count
 */
function getSystemAlertCount(PDO $pdo): int {
    try {
        $alertCount = 0;
        
        // SLA breaches (complaints overdue)
        $stmt = $pdo->prepare("
            SELECT COUNT(*) as breach_count FROM complaints
            WHERE sla_deadline < NOW() AND status != 'resolved'
        ");
        $stmt->execute();
        $breaches = $stmt->fetch(PDO::FETCH_ASSOC);
        $alertCount += $breaches['breach_count'] ?? 0;
        
        // System health errors
        $stmt = $pdo->prepare("
            SELECT COUNT(*) as error_count FROM system_health_checks
            WHERE status = 'error'
        ");
        $stmt->execute();
        $errors = $stmt->fetch(PDO::FETCH_ASSOC);
        $alertCount += $errors['error_count'] ?? 0;
        
        return $alertCount;
    } catch (Exception $e) {
        error_log("getSystemAlertCount error: " . $e->getMessage());
        return 0;
    }
}

// ========== GET ACTIVITY LOG ENTRIES ==========
/**
 * Retrieve paginated activity log with optional filtering
 * 
 * @param PDO $pdo Database connection
 * @param int $page Page number (1-indexed)
 * @param int $perPage Results per page (default 50)
 * @param string|null $fromDate Filter from date (YYYY-MM-DD format, optional)
 * @param string|null $toDate Filter to date (YYYY-MM-DD format, optional)
 * @param string|null $actionType Filter by action type (optional)
 * @param int|null $actorId Filter by actor user ID (optional)
 * @return array ['entries' => [...], 'total' => int, 'page' => int, 'pages' => int]
 */
function getActivityLogEntries(PDO $pdo, int $page = 1, int $perPage = 50, ?string $fromDate = null, ?string $toDate = null, ?string $actionType = null, ?int $actorId = null): array {
    try {
        $offset = ($page - 1) * $perPage;
        $params = [];
        $where = "WHERE 1=1";
        
        if ($fromDate) {
            $where .= " AND DATE(aal.created_at) >= ?";
            $params[] = $fromDate;
        }
        if ($toDate) {
            $where .= " AND DATE(aal.created_at) <= ?";
            $params[] = $toDate;
        }
        if ($actionType) {
            $where .= " AND aal.action_type = ?";
            $params[] = $actionType;
        }
        if ($actorId) {
            $where .= " AND aal.actor_id = ?";
            $params[] = $actorId;
        }
        
        // Get total count
        $countStmt = $pdo->prepare("SELECT COUNT(*) as total FROM admin_activity_log aal $where");
        $countStmt->execute($params);
        $totalResult = $countStmt->fetch(PDO::FETCH_ASSOC);
        $total = $totalResult['total'] ?? 0;
        
        // Get paginated entries with actor user details
        $listStmt = $pdo->prepare("
            SELECT 
                aal.id,
                aal.action_type,
                aal.subject_type,
                aal.subject_id,
                aal.ip_address,
                aal.details,
                aal.created_at,
                u.name as actor_name,
                u.role as actor_role
            FROM admin_activity_log aal
            LEFT JOIN users u ON u.id = aal.actor_id
            $where
            ORDER BY aal.created_at DESC
            LIMIT ? OFFSET ?
        ");
        
        $listStmt->bindParam(1, $perPage, PDO::PARAM_INT);
        $listStmt->bindParam(2, $offset, PDO::PARAM_INT);
        
        $params[] = $perPage;
        $params[] = $offset;
        $listStmt->execute($params);
        $entries = $listStmt->fetchAll(PDO::FETCH_ASSOC);
        
        return [
            'entries' => $entries,
            'total' => $total,
            'page' => $page,
            'pages' => max(1, ceil($total / $perPage))
        ];
    } catch (Exception $e) {
        error_log("getActivityLogEntries error: " . $e->getMessage());
        return ['entries' => [], 'total' => 0, 'page' => $page, 'pages' => 0];
    }
}

// ========== GET METRIC DATA ==========
/**
 * Fetch dashboard metric data by category
 * Called by dashboard_data.php endpoint
 * 
 * @param PDO $pdo Database connection
 * @param string $widget Widget identifier (metrics|complaints|growth|sla|activity|health)
 * @return array Metric data for requested widget
 */
function getMetricData(PDO $pdo, string $widget): array {
    try {
        switch ($widget) {
            case 'metrics':
                return getMetricsCard($pdo);
            case 'complaints':
                return getComplaintDonutData($pdo);
            case 'growth':
                return getGrowthChartData($pdo);
            case 'sla':
                return getSLAMetrics($pdo);
            case 'activity':
                return getRecentActivityFeed($pdo);
            case 'health':
                return getHealthPillData($pdo);
            case 'top_markets':
                return getTopMarketsData($pdo);
            default:
                return ['error' => 'Unknown widget: ' . $widget];
        }
    } catch (Exception $e) {
        error_log("getMetricData error: " . $e->getMessage());
        return ['error' => $e->getMessage()];
    }
}

// ========== METRICS CARD DATA ==========
function getMetricsCard(PDO $pdo): array {
    try {
        // Total active sellers
        $stmt = $pdo->prepare("SELECT COUNT(*) as count FROM users WHERE role='seller' AND is_active=1");
        $stmt->execute();
        $sellers = $stmt->fetch(PDO::FETCH_ASSOC)['count'] ?? 0;
        
        // Active managers
        $stmt = $pdo->prepare("SELECT COUNT(*) as count FROM users WHERE role='manager' AND is_active=1");
        $stmt->execute();
        $managers = $stmt->fetch(PDO::FETCH_ASSOC)['count'] ?? 0;
        
        // Open complaints (pending + in_review)
        $stmt = $pdo->prepare("SELECT COUNT(*) as count FROM complaints WHERE status IN ('pending', 'in_review')");
        $stmt->execute();
        $openComplaints = $stmt->fetch(PDO::FETCH_ASSOC)['count'] ?? 0;
        
        // Announcements sent in last 30 days
        $stmt = $pdo->prepare("SELECT COUNT(*) as count FROM announcements WHERE deleted_at IS NULL AND created_at >= DATE_SUB(NOW(), INTERVAL 30 DAY)");
        $stmt->execute();
        $announcements = $stmt->fetch(PDO::FETCH_ASSOC)['count'] ?? 0;
        
        return [
            'total_sellers' => (int)$sellers,
            'active_managers' => (int)$managers,
            'open_complaints' => (int)$openComplaints,
            'announcements_sent' => (int)$announcements
        ];
    } catch (Exception $e) {
        error_log("getMetricsCard error: " . $e->getMessage());
        return [];
    }
}

// ========== COMPLAINT DONUT DATA ==========
function getComplaintDonutData(PDO $pdo): array {
    try {
        $stmt = $pdo->prepare("
            SELECT 
                status,
                COUNT(*) as count
            FROM complaints
            GROUP BY status
        ");
        $stmt->execute();
        $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        $data = ['pending' => 0, 'in_review' => 0, 'resolved' => 0];
        $total = 0;
        foreach ($rows as $row) {
            if (isset($data[$row['status']])) {
                $data[$row['status']] = (int)$row['count'];
                $total += (int)$row['count'];
            }
        }
        
        $data['total'] = $total;
        
        // Calculate percentages
        $data['pending_pct'] = $total > 0 ? round(($data['pending'] / $total) * 100) : 0;
        $data['in_review_pct'] = $total > 0 ? round(($data['in_review'] / $total) * 100) : 0;
        $data['resolved_pct'] = $total > 0 ? round(($data['resolved'] / $total) * 100) : 0;
        
        return $data;
    } catch (Exception $e) {
        error_log("getComplaintDonutData error: " . $e->getMessage());
        return [];
    }
}

// ========== GROWTH CHART DATA ==========
function getGrowthChartData(PDO $pdo): array {
    try {
        $data = [];
        
        for ($i = 5; $i >= 0; $i--) {
            $date = date('Y-m-01', strtotime("-$i months"));
            $nextDate = date('Y-m-01', strtotime("-" . ($i - 1) . " months"));
            
            // New users this month
            $stmt = $pdo->prepare("SELECT COUNT(*) as count FROM users WHERE created_at >= ? AND created_at < ? AND role IN ('seller', 'manager')");
            $stmt->execute([$date, $nextDate]);
            $users = $stmt->fetch(PDO::FETCH_ASSOC)['count'] ?? 0;
            
            // New complaints this month
            $stmt = $pdo->prepare("SELECT COUNT(*) as count FROM complaints WHERE created_at >= ? AND created_at < ?");
            $stmt->execute([$date, $nextDate]);
            $complaints = $stmt->fetch(PDO::FETCH_ASSOC)['count'] ?? 0;
            
            $data[] = [
                'month' => date('M Y', strtotime($date)),
                'new_users' => (int)$users,
                'new_complaints' => (int)$complaints
            ];
        }
        
        return $data;
    } catch (Exception $e) {
        error_log("getGrowthChartData error: " . $e->getMessage());
        return [];
    }
}

// ========== SLA METRICS ==========
function getSLAMetrics(PDO $pdo): array {
    try {
        // Breached (deadline passed, not resolved)
        $stmt = $pdo->prepare("SELECT COUNT(*) as count FROM complaints WHERE sla_deadline < NOW() AND status != 'resolved'");
        $stmt->execute();
        $breached = $stmt->fetch(PDO::FETCH_ASSOC)['count'] ?? 0;
        
        // Expiring within 24h
        $stmt = $pdo->prepare("SELECT COUNT(*) as count FROM complaints WHERE sla_deadline BETWEEN NOW() AND DATE_ADD(NOW(), INTERVAL 24 HOUR) AND status != 'resolved'");
        $stmt->execute();
        $expiring = $stmt->fetch(PDO::FETCH_ASSOC)['count'] ?? 0;
        
        // Within SLA
        $stmt = $pdo->prepare("SELECT COUNT(*) as count FROM complaints WHERE sla_deadline > DATE_ADD(NOW(), INTERVAL 24 HOUR) AND status != 'resolved'");
        $stmt->execute();
        $withinSLA = $stmt->fetch(PDO::FETCH_ASSOC)['count'] ?? 0;
        
        return [
            'breached' => (int)$breached,
            'expiring_24h' => (int)$expiring,
            'within_sla' => (int)$withinSLA
        ];
    } catch (Exception $e) {
        error_log("getSLAMetrics error: " . $e->getMessage());
        return [];
    }
}

// ========== ACTIVITY FEED ==========
function getRecentActivityFeed(PDO $pdo, int $limit = 20): array {
    try {
        $stmt = $pdo->prepare("
            SELECT 
                aal.id,
                aal.action_type,
                aal.subject_type,
                aal.created_at,
                u.name as actor_name,
                u.role as actor_role
            FROM admin_activity_log aal
            LEFT JOIN users u ON u.id = aal.actor_id
            ORDER BY aal.created_at DESC
            LIMIT ?
        ");
        $stmt->bindParam(1, $limit, PDO::PARAM_INT);
        $stmt->execute();
        
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    } catch (Exception $e) {
        error_log("getRecentActivityFeed error: " . $e->getMessage());
        return [];
    }
}

// ========== HEALTH PILL DATA ==========
function getHealthPillData(PDO $pdo): array {
    try {
        $stmt = $pdo->prepare("
            SELECT 
                COUNT(*) as total,
                SUM(CASE WHEN status='ok' THEN 1 ELSE 0 END) as ok_count,
                SUM(CASE WHEN status='warning' THEN 1 ELSE 0 END) as warning_count,
                SUM(CASE WHEN status='error' THEN 1 ELSE 0 END) as error_count
            FROM system_health_checks
        ");
        $stmt->execute();
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        
        return [
            'total_checks' => (int)($result['total'] ?? 0),
            'ok' => (int)($result['ok_count'] ?? 0),
            'warning' => (int)($result['warning_count'] ?? 0),
            'error' => (int)($result['error_count'] ?? 0),
            'status' => ($result['error_count'] ?? 0) > 0 ? 'error' : (($result['warning_count'] ?? 0) > 0 ? 'warning' : 'ok')
        ];
    } catch (Exception $e) {
        error_log("getHealthPillData error: " . $e->getMessage());
        return [];
    }
}

// ========== TOP MARKETS DATA ==========
function getTopMarketsData(PDO $pdo, int $limit = 5): array {
    try {
        $stmt = $pdo->prepare("
            SELECT 
                m.id,
                m.name,
                m.location,
                COUNT(c.id) as total_complaints,
                SUM(CASE WHEN c.status IN ('pending', 'in_review') THEN 1 ELSE 0 END) as pending_count,
                SUM(CASE WHEN c.status = 'resolved' THEN 1 ELSE 0 END) as resolved_count
            FROM markets m
            LEFT JOIN complaints c ON c.market_id = m.id
            GROUP BY m.id, m.name, m.location
            ORDER BY total_complaints DESC
            LIMIT ?
        ");
        $stmt->bindParam(1, $limit, PDO::PARAM_INT);
        $stmt->execute();
        
        $markets = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        // Add resolution percentage and color
        foreach ($markets as &$market) {
            $total = (int)$market['total_complaints'];
            $resolved = (int)$market['resolved_count'];
            $market['resolution_rate'] = $total > 0 ? round(($resolved / $total) * 100) : 0;
            $market['rate_color'] = $market['resolution_rate'] < 50 ? 'red' : ($market['resolution_rate'] < 80 ? 'yellow' : 'green');
        }
        
        return $markets;
    } catch (Exception $e) {
        error_log("getTopMarketsData error: " . $e->getMessage());
        return [];
    }
}

// ========== ENROLL DASHBOARD DEFAULTS ==========
/**
 * Set up default dashboard widget configuration for a new admin
 * 
 * @param PDO $pdo Database connection
 * @param int $adminId users.id of the admin
 */
function enrollDashboardDefaults(PDO $pdo, int $adminId): void {
    $defaults = [
        ['widget_id' => 'metrics_row', 'is_visible' => 1, 'sort_order' => 0],
        ['widget_id' => 'complaint_donut', 'is_visible' => 1, 'sort_order' => 1],
        ['widget_id' => 'sla_alert', 'is_visible' => 1, 'sort_order' => 2],
        ['widget_id' => 'top_markets', 'is_visible' => 1, 'sort_order' => 3],
        ['widget_id' => 'growth_chart', 'is_visible' => 1, 'sort_order' => 4],
        ['widget_id' => 'activity_feed', 'is_visible' => 1, 'sort_order' => 5],
        ['widget_id' => 'health_pill', 'is_visible' => 1, 'sort_order' => 6]
    ];
    
    try {
        foreach ($defaults as $config) {
            $stmt = $pdo->prepare("
                INSERT IGNORE INTO dashboard_widget_config 
                (admin_id, widget_id, is_visible, sort_order)
                VALUES (?, ?, ?, ?)
            ");
            $stmt->execute([$adminId, $config['widget_id'], $config['is_visible'], $config['sort_order']]);
        }
    } catch (Exception $e) {
        error_log("enrollDashboardDefaults error: " . $e->getMessage());
    }
}

// ========== GET ACTION TYPE COLOR BADGE ==========
/**
 * Return CSS color class for activity log action type badge
 */
function getActionBadgeColor(string $actionType): string {
    if (strpos($actionType, 'user_') === 0 && strpos($actionType, '_deactivated') === false && strpos($actionType, '_reactivated') === false) {
        return 'badge-teal';
    }
    if (strpos($actionType, 'deactivated') !== false || strpos($actionType, 'reactivated') !== false) {
        return 'badge-orange';
    }
    if (in_array($actionType, ['login', 'logout'])) {
        return 'badge-gray';
    }
    if (strpos($actionType, 'complaint') !== false) {
        return 'badge-blue';
    }
    if ($actionType === 'system_error') {
        return 'badge-red';
    }
    return 'badge-gray';
}

// ========== ESCAPE CSV VALUE ==========
/**
 * Escape CSV values to prevent formula injection
 * Prefixes values starting with =, +, -, @ with apostrophe
 */
function escapeCSVValue($value): string {
    $value = (string)$value;
    if (in_array($value[0] ?? null, ['=', '+', '-', '@'])) {
        return "'" . $value;
    }
    return $value;
}
