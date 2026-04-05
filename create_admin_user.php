<?php
require_once 'config/db.php';

echo "<h1>Create Initial Admin User</h1>\n\n";

// Generate a secure password
$plainPassword = "Admin123456!";
$hashedPassword = password_hash($plainPassword, PASSWORD_BCRYPT, ['cost' => 12]);

echo "Creating admin user...\n";
echo "Email: admin@placeparole.local\n";
echo "Password: $plainPassword (shown once - save this!)\n";
echo "Role: admin\n\n";

try {
    $stmt = $pdo->prepare("
        INSERT INTO users (name, email, phone, role, market_id, password, lang, is_active, created_at)
        VALUES (?, ?, ?, ?, ?, ?, ?, ?, NOW())
    ");
    
    $result = $stmt->execute([
        'Admin User',                        // name
        'admin@placeparole.local',          // email
        '+1234567890',                      // phone
        'admin',                            // role
        1,                                  // market_id
        $hashedPassword,                    // password (hashed)
        'en',                               // lang (English)
        1                                   // is_active
    ]);
    
    if ($result) {
        $adminId = $pdo->lastInsertId();
        echo "✅ Admin user created successfully!\n";
        echo "   User ID: $adminId\n\n";
        
        // Set up default widget configuration for this admin
        echo "Setting up default dashboard widgets...\n";
        
        $widgets = [
            ['id' => 'metrics_row', 'order' => 1, 'visible' => 1],
            ['id' => 'complaint_donut', 'order' => 2, 'visible' => 1],
            ['id' => 'sla_alert', 'order' => 3, 'visible' => 1],
            ['id' => 'top_markets', 'order' => 4, 'visible' => 1],
            ['id' => 'growth_chart', 'order' => 5, 'visible' => 1],
            ['id' => 'activity_feed', 'order' => 6, 'visible' => 1],
            ['id' => 'health_pill', 'order' => 7, 'visible' => 1],
        ];
        
        $widgetStmt = $pdo->prepare("
            INSERT INTO dashboard_widget_config (admin_id, widget_id, is_visible, sort_order, created_at, updated_at)
            VALUES (?, ?, ?, ?, NOW(), NOW())
            ON DUPLICATE KEY UPDATE is_visible = ?, sort_order = ?, updated_at = NOW()
        ");
        
        foreach ($widgets as $widget) {
            $widgetStmt->execute([
                $adminId,
                $widget['id'],
                $widget['visible'],
                $widget['order'],
                $widget['visible'],
                $widget['order']
            ]);
            echo "   ✅ Configured widget: {$widget['id']}\n";
        }
        
        echo "\n" . str_repeat("=", 60) . "\n";
        echo "✅ ADMIN SETUP COMPLETE!\n";
        echo str_repeat("=", 60) . "\n\n";
        
        echo "Next steps:\n";
        echo "1. Start XAMPP Apache & MySQL\n";
        echo "2. Login at http://localhost/PlaceParole/index.php\n";
        echo "   Email: admin@placeparole.local\n";
        echo "   Password: " . $plainPassword . "\n";
        echo "3. Access dashboard: http://localhost/PlaceParole/modules/admin/dashboard.php\n";
        echo "4. Check ADMIN_SETUP.md for testing checklist\n\n";
        
        echo "⚠️  SECURITY: Change this password after first login!\n";
        
    } else {
        echo "❌ Failed to insert admin user\n";
    }
} catch (Exception $e) {
    if (strpos($e->getMessage(), 'Duplicate entry') !== false) {
        echo "⚠️  Admin user already exists (admin@placeparole.local)\n";
        echo "   If you need to reset password, run this SQL:\n";
        echo "   UPDATE users SET password = '" . $hashedPassword . "' WHERE email = 'admin@placeparole.local';\n";
    } else {
        echo "❌ Error: " . $e->getMessage() . "\n";
    }
}
?>
