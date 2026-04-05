<?php
require_once 'config/db.php';

echo "<h1>Creating Admin Dashboard Tables (Direct Method)</h1>\n\n";

// These statements work better when executed individually
$statements = [
    // ALTER statements
    "ALTER TABLE users ADD COLUMN IF NOT EXISTS is_active TINYINT(1) DEFAULT 1 COMMENT 'Soft deactivation flag (0=inactive, 1=active)'",
    "ALTER TABLE users ADD COLUMN IF NOT EXISTS last_login_at TIMESTAMP NULL DEFAULT NULL COMMENT 'Timestamp of last successful login'",
    "ALTER TABLE users ADD COLUMN IF NOT EXISTS deactivated_at TIMESTAMP NULL DEFAULT NULL COMMENT 'Timestamp when user was deactivated (NULL if active)'",
    "ALTER TABLE users MODIFY COLUMN role ENUM('seller','manager','admin') NOT NULL COMMENT 'User role: seller, manager, or admin'",
    
    // Indexes on users
    "CREATE INDEX IF NOT EXISTS idx_users_is_active ON users(is_active)",
    "CREATE INDEX IF NOT EXISTS idx_users_deactivated_at ON users(deactivated_at)",
    "CREATE INDEX IF NOT EXISTS idx_users_last_login_at ON users(last_login_at)",
];

$createTables = [
    "CREATE TABLE IF NOT EXISTS admin_activity_log (
      id INT AUTO_INCREMENT PRIMARY KEY COMMENT 'Unique log entry ID',
      market_id INT NULL COMMENT 'Market context (NULL for platform-level actions)',
      actor_id INT NOT NULL COMMENT 'users.id of who performed the action',
      action_type VARCHAR(60) NOT NULL COMMENT 'Action category: user_created, user_updated, user_deactivated, user_reactivated, login, logout, complaint_status_changed, etc.',
      subject_type VARCHAR(40) NULL COMMENT 'Entity type: user, complaint, announcement, suggestion, system, etc.',
      subject_id INT NULL COMMENT 'ID of the affected entity',
      ip_address VARCHAR(45) NULL COMMENT 'IPv4 or IPv6 address of requester',
      user_agent VARCHAR(255) NULL COMMENT 'Browser user agent string',
      details JSON NULL COMMENT 'Extra context: {old_value, new_value, reason, status_before, status_after, etc.}',
      created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP COMMENT 'When the action occurred',
      FOREIGN KEY (actor_id) REFERENCES users(id) ON DELETE CASCADE,
      FOREIGN KEY (market_id) REFERENCES markets(id) ON DELETE CASCADE,
      INDEX idx_actor_id (actor_id),
      INDEX idx_action_type (action_type),
      INDEX idx_created_at (created_at),
      INDEX idx_subject (subject_type, subject_id),
      INDEX idx_market_id (market_id)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='Audit log of all admin actions for accountability and compliance'",
    
    "CREATE TABLE IF NOT EXISTS system_health_checks (
      id INT AUTO_INCREMENT PRIMARY KEY COMMENT 'Unique health check ID',
      check_name VARCHAR(60) NOT NULL UNIQUE COMMENT 'System identifier: database, email, sms, whatsapp, filesystem, error_rate, etc.',
      status ENUM('ok', 'warning', 'error') DEFAULT 'ok' COMMENT 'Current health status',
      response_ms INT NULL COMMENT 'Response time in milliseconds (for connectivity checks)',
      details JSON NULL COMMENT 'Detailed check result: {message, table_counts, file_count, log_size, last_event, etc.}',
      checked_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP COMMENT 'When this check was last performed',
      INDEX idx_check_name (check_name),
      INDEX idx_status (status),
      INDEX idx_checked_at (checked_at)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='System health status snapshots (auto-updated on health page load)'",
    
    "CREATE TABLE IF NOT EXISTS dashboard_widget_config (
      id INT AUTO_INCREMENT PRIMARY KEY COMMENT 'Unique widget config ID',
      admin_id INT NOT NULL COMMENT 'users.id of the admin who configured this',
      widget_id VARCHAR(40) NOT NULL COMMENT 'Widget identifier: metrics_row, complaint_donut, sla_alert, top_markets, growth_chart, activity_feed, health_pill',
      is_visible TINYINT(1) DEFAULT 1 COMMENT 'Widget visibility toggle (0=hidden, 1=visible)',
      sort_order INT DEFAULT 0 COMMENT 'Display order priority (0=first, higher numbers later)',
      created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
      updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
      FOREIGN KEY (admin_id) REFERENCES users(id) ON DELETE CASCADE,
      UNIQUE KEY uk_admin_widget (admin_id, widget_id),
      INDEX idx_admin_id (admin_id),
      INDEX idx_sort_order (sort_order)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='Per-admin dashboard widget visibility and display preferences'"
];

echo "Executing ALTER and CREATE INDEX statements...\n";
foreach ($statements as $i => $stmt) {
    try {
        $pdo->exec($stmt);
        echo "✅ Statement " . ($i+1) . " executed\n";
    } catch (Exception $e) {
        echo "⚠️  Statement " . ($i+1) . " error: " . $e->getMessage() . "\n";
    }
}

echo "\nCreating tables...\n";
foreach ($createTables as $i => $stmt) {
    try {
        $pdo->exec($stmt);
        echo "✅ Table created\n";
    } catch (Exception $e) {
        echo "❌ Table creation error: " . $e->getMessage() . "\n";
    }
}

echo "\n" . str_repeat("=", 60) . "\n";
echo "Final Verification\n";
echo str_repeat("=", 60) . "\n\n";

// Verify
$tables = ['admin_activity_log', 'system_health_checks', 'dashboard_widget_config'];

$allOk = true;
foreach ($tables as $table) {
    try {
        $result = $pdo->query("SELECT COUNT(*) FROM $table");
        echo "✅ Table '$table' exists and is accessible\n";
    } catch (Exception $e) {
        echo "❌ Table '$table' error: " . $e->getMessage() . "\n";
        $allOk = false;
    }
}

echo "\nVerifying users table columns...\n";
$stmt = $pdo->query("DESCRIBE users");
$columns = $stmt->fetchAll(PDO::FETCH_ASSOC);
$newCols = ['is_active', 'last_login_at', 'deactivated_at'];

foreach ($newCols as $col) {
    $exists = array_filter($columns, fn($c) => $c['Field'] === $col);
    if ($exists) {
        echo "✅ Column '$col' exists\n";
    } else {
        echo "❌ Column '$col' missing\n";
        $allOk = false;
    }
}

echo "\n" . str_repeat("=", 60) . "\n";
if ($allOk) {
    echo "✅ SUCCESS: All admin dashboard tables created!\n";
    echo "\nNext step: Create the initial admin user\n";
} else {
    echo "⚠️  Some tables may have issues. Check errors above.\n";
}
?>
