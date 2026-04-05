# Super Admin Dashboard Setup Guide

**Last Updated**: December 2024  
**Target Environment**: XAMPP on Windows  
**PHP Version**: 8.0+  
**MySQL Version**: 8.0+  

---

## Table of Contents

1. [Prerequisites](#prerequisites)
2. [Local Deployment](#local-deployment)
3. [Database Migration](#database-migration)
4. [Initial Admin User](#initial-admin-user)
5. [Login & Verification](#login--verification)
6. [Testing Checklist](#testing-checklist)
7. [Troubleshooting](#troubleshooting)
8. [Production Hardening](#production-hardening)
9. [Performance Optimization](#performance-optimization)

---

## Prerequisites

### System Requirements
- ✅ XAMPP 7.4+ with PHP 8.0+ CLI enabled
- ✅ MySQL 8.0+ Command-Line Client
- ✅ 50MB free disk space (for uploads and logs)
- ✅ SMTP/Gmail credentials for email notifications (optional)
- ✅ Twilio account for WhatsApp integration (optional)

### Verify Installation
```bash
php -v
mysql -V
```

Both should return version 8.0+. If not, update your PHP CLI via XAMPP Control Panel.

---

## Local Deployment

### Step 1: Extract Files to XAMPP

All 13 files are included in this package:

```
PlaceParole/
├── database_migrations/
│   └── 004_admin_dashboard.sql         (migration script)
├── config/
│   └── admin_helpers.php               (helper functions)
├── assets/js/
│   └── admin_dashboard.js              (frontend interactivity)
├── modules/admin/
│   ├── dashboard.php                   (main dashboard page)
│   ├── dashboard_data.php              (AJAX metrics endpoint)
│   ├── dashboard_widget_save.php       (widget config endpoint)
│   ├── users.php                       (user management list)
│   ├── user_create.php                 (user creation form)
│   ├── user_edit.php                   (user editing form)
│   ├── user_toggle.php                 (deactivate/activate endpoint)
│   ├── activity_log.php                (audit log viewer)
│   └── system_health.php               (integration health checks)
└── ADMIN_SETUP.md                      (this file)
```

**Deployment via XAMPP:**

1. Copy all files to `C:\xampp\htdocs\PlaceParole\` (preserving folder structure)
2. Verify `C:\xampp\htdocs\PlaceParole\modules\admin\dashboard.php` exists
3. Verify `C:\xampp\htdocs\PlaceParole\database_migrations\004_admin_dashboard.sql` exists

### Step 2: Verify Composer Dependencies

The admin dashboard requires PHPMailer (already in your vendor/ folder):

```bash
cd C:\xampp\htdocs\PlaceParole
composer install  # If not already done
```

Confirm `vendor/phpmailer/phpmailer` exists.

---

## Database Migration

### Option A: CLI Method (Recommended)

```bash
cd C:\xampp\htdocs\PlaceParole
php run_migrations.php
```

Expected output:
```
Running migration: 001_add_complaint_threading.sql
Running migration: 002_add_suggestions_announcements_feedback.sql
Running migration: 003_add_soft_delete_columns.sql
Running migration: 004_admin_dashboard.sql
✅ All migrations completed successfully!
```

### Option B: phpMyAdmin Method

1. Open **http://localhost/phpmyadmin/**
2. Login with root / (empty password)
3. Select **PlaceParole** database in left sidebar
4. Click **Import** tab
5. Click **Choose File** and select `database_migrations/004_admin_dashboard.sql`
6. Click **Go**

Expected tables to appear:
- `admin_activity_log`
- `system_health_checks`
- `dashboard_widget_config`

### Option C: MySQL CLI Method

```bash
mysql -u root -p PlaceParole < database_migrations/004_admin_dashboard.sql
```

(Press Enter when prompted for password; leave it blank by default)

### Verify Migration Success

```bash
mysql -u root -p PlaceParole
```

Then paste:
```sql
DESCRIBE users;
DESCRIBE admin_activity_log;
DESCRIBE system_health_checks;
DESCRIBE dashboard_widget_config;
SHOW INDEXES FROM admin_activity_log;
```

Expected output for `users`:
```
| is_active       | tinyint(1)       | YES  |     | NULL    |                |
| last_login_at   | timestamp        | YES  |     | NULL    |                |
| deactivated_at  | timestamp        | YES  |     | NULL    |                |
```

If columns are missing, migration failed. See **Troubleshooting > Migration Failed**.

---

## Initial Admin User

### Create First Admin Account

After migration completes, create the initial admin user via CLI:

#### Option A: CLI Command

```bash
mysql -u root -p PlaceParole
```

Then paste this SQL (modify credentials as needed):

```sql
INSERT INTO users 
(name, email, phone, role, market_id, password, lang, is_active, created_at)
VALUES (
    'Admin User',
    'admin@placeparole.local',
    '+1234567890',
    'admin',
    1,
    '$2y$12$abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ1234',
    'en',
    1,
    NOW()
);
```

**Password is hashed with bcrypt. To generate a hash:**

```php
<?php
$password = 'SecurePassword123!';
$hash = password_hash($password, PASSWORD_BCRYPT, ['cost' => 12]);
echo $hash; // Use this in the SQL above
?>
```

**Important**: Save the password somewhere secure before inserting!

#### Option B: Direct Hash Generation

Use this PowerShell command:

```powershell
php -r "echo password_hash('YourPassword123!', PASSWORD_BCRYPT, ['cost' => 12]);"
```

Copy the output and paste into the SQL INSERT above.

#### Option C: Web-based Setup (Alternative)

Create a temporary file `admin_setup.php` in PlaceParole root:

```php
<?php
require_once 'config/db.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $password = password_hash($_POST['password'], PASSWORD_BCRYPT, ['cost' => 12]);
    $stmt = $pdo->prepare("
        INSERT INTO users (name, email, phone, role, market_id, password, lang, is_active, created_at)
        VALUES (?, ?, ?, 'admin', 1, ?, 'en', 1, NOW())
    ");
    $stmt->execute([$_POST['name'], $_POST['email'], $_POST['phone'], $password]);
    echo "✅ Admin user created!";
    exit;
}
?>
<form method="POST">
    Name: <input name="name" required>
    Email: <input name="email" type="email" required>
    Phone: <input name="phone" required>
    Password: <input name="password" type="password" required>
    <button type="submit">Create Admin</button>
</form>
```

Access: **http://localhost/PlaceParole/admin_setup.php**

Then delete `admin_setup.php` after creation!

### Default Credentials

```
Email: admin@placeparole.local
Password: (whatever you set above)
Role: admin
```

---

## Login & Verification

### Step 1: Start XAMPP

1. Open **XAMPP Control Panel**
2. Click **Start** for Apache
3. Click **Start** for MySQL

### Step 2: Navigate to Dashboard

```
http://localhost/PlaceParole/index.php
```

### Step 3: Login

- Email: `admin@placeparole.local`
- Password: (whatever password you created)

### Step 4: Access Admin Dashboard

After login, you should see the admin dashboard with 7 widgets:

1. **Metrics Row** — 4 cards showing totals
2. **Complaint Status Donut** — Ring chart with pending/in_review/resolved
3. **SLA Alert Banner** — Red alert if breaches exist
4. **Top Markets** — Table with resolution % per market
5. **Growth Chart** — 6-month trend line + bar chart
6. **Activity Feed** — Real-time log entries (updates every 30s)
7. **Health Status Pill** — System integration health summary

If you see all 7 widgets, dashboard is working! ✅

---

## Testing Checklist

### Dashboard Functionality (Expected: All Pass)

- [ ] Dashboard loads without JavaScript errors (F12 → Console)
- [ ] All 7 widgets visible and populated with data
- [ ] Metric cards show correct totals (match database counts)
- [ ] Donut chart renders with 3 colors (pending/in_review/resolved)
- [ ] Growth chart shows 6 months of trend data
- [ ] Activity feed shows recent admin actions
- [ ] Health pill shows "X/7 Checks OK"
- [ ] Notification bell badge shows count
- [ ] Widget visibility toggles work (gear icon → uncheck/recheck widget)
- [ ] Drag-to-reorder widgets works (grab widget header, drag), persists after page reload

### User Management (Expected: All Pass)

- [ ] Users page loads with pagination (25 per page)
- [ ] Search by name/email/phone filters correctly
- [ ] Filter by role shows only selected role
- [ ] Filter by market shows only selected market
- [ ] Filter by status (active/inactive) works
- [ ] CSV export downloads file with all filtered users
- [ ] Create User form validates:
  - [ ] Name required (3+ chars)
  - [ ] Email unique (reject duplicates)
  - [ ] Email valid format
  - [ ] Phone format (7-15 digits, +optional)
  - [ ] Password requires UC/LC/digit/special (8+ chars)
  - [ ] Stall number required if role=seller (always shows)
  - [ ] CSRF token validated
- [ ] Edit User form:
  - [ ] Pre-fills all current values
  - [ ] Cannot change own role (select disabled)
  - [ ] Password reset checkbox shows/hides password field
  - [ ] Logs change to activity_log
  - [ ] Shows JSON diff of changes
- [ ] Deactivate user:
  - [ ] Button appears on each user row
  - [ ] Prevents self-deactivation (logs error)
  - [ ] Sets deactivated_at timestamp
  - [ ] User appears in "inactive" filter

### Activity Logging (Expected: All Pass)

- [ ] Activity Log page loads
- [ ] Filter by date range works (default: last 7 days)
- [ ] Filter by action type (dropdown) filters correctly
- [ ] Filter by actor (autocomplete) works
- [ ] Pagination shows 50 entries per page
- [ ] Expandable detail rows show full JSON details
- [ ] CSV export includes all filters applied
- [ ] Create user action logged with email/phone in details
- [ ] Edit user action logged with {field: {old, new}} diff
- [ ] Deactivate user action logged with before/after status

### System Health (Expected: All Pass)

- [ ] System Health page loads
- [ ] All 6 checks complete within 5 seconds
- [ ] Database check shows ✅ and table row counts
- [ ] Email check shows ✅ if GMAIL_USERNAME in .env, ⚠️ if not
- [ ] SMS check shows ✅ if sms_send.php exists
- [ ] WhatsApp check shows ✅ if Twilio vendor + TWILIO_* env vars set
- [ ] Filesystem check shows ✅ if uploads/ and logs/ writable
- [ ] Error Rate check shows ✅ if <10 ERROR lines in logs/ (24h)
- [ ] Details expand to show raw data
- [ ] Health summary in dashboard updates based on these checks

### Real-Time Updates (Expected: All Pass)

- [ ] Dashboard tiles refresh every ~30 seconds without page reload
- [ ] New activity feed entries appear automatically (watch for 60s)
- [ ] Charts update when new complaints/users added
- [ ] No JavaScript errors in console during polling
- [ ] Polling stops gracefully if you navigate away

### Security (Expected: All Pass)

- [ ] CSRF token required on all POST requests
  - [ ] Create user requires token
  - [ ] Edit user requires token
  - [ ] Widget config save requires token
- [ ] Non-admins cannot access dashboard (test with seller/manager login)
- [ ] Passwords hashed with bcrypt (verify in database):
  ```sql
  SELECT password FROM users WHERE role='admin' LIMIT 1;
  -- Should show: $2y$12$... (60 chars)
  ```
- [ ] Prepared statements used (check source code, no string concatenation in SQL)
- [ ] HTML escaped on display (no `<script>` tags execute in activity details)

---

## Troubleshooting

### Issue: White Screen / 500 Error

**Symptoms**: Blank page or "500 Internal Server Error" when accessing dashboard

**Solution**:
1. Check PHP error log: `C:\xampp\php\logs\php_error.log`
2. Look for recent errors
3. Common causes:
   - Missing `config/admin_helpers.php` (check file exists)
   - Database not migrated (run migration again)
   - Missing session start (should be in index.php)

**Test**:
```bash
php -l modules/admin/dashboard.php
```

Should return: `No syntax errors`

---

### Issue: "Table admin_activity_log Doesn't Exist"

**Symptoms**: Error mentioning missing table when loading dashboard

**Solution**:
1. Verify migration ran:
```sql
SHOW TABLES LIKE 'admin_%';
```

Should list: `admin_activity_log`, `dashboard_widget_config`

2. If missing, run migration again:
```bash
php run_migrations.php
```

3. Check migration file permissions:
```bash
dir database_migrations/004_admin_dashboard.sql
```

---

### Issue: Session Lost / Login Keeps Redirecting

**Symptoms**: Login page reloads repeatedly instead of entering dashboard

**Solution**:
1. Check session directory is writable:
```bash
mkdir C:\xampp\tmp
```

2. Verify session cookie settings in `php.ini`:
```
session.save_path = "C:\xampp\tmp"
```

3. Clear browser cookies (Ctrl+Shift+Delete)
4. Try incognito window
5. Check database `users` table has admin user:
```sql
SELECT COUNT(*) FROM users WHERE role='admin' AND is_active=1;
```

Should return: `1`

---

### Issue: Charts Not Rendering / Canvas Elements Empty

**Symptoms**: Widget area shows gray box but no chart visualization

**Solution**:
1. Check browser console (F12 → Console) for JavaScript errors
2. Verify `assets/js/admin_dashboard.js` exists
3. Verify `assets/js/alpine.min.js` exists
4. Check network tab (F12 → Network) for failed requests to these files
5. Verify database has complaint data:
```sql
SELECT COUNT(*) FROM complaints;
```

If 0, charts will be empty (add test data):
```sql
INSERT INTO complaints (market_id, user_id, title, details, status, created_at)
VALUES (1, 2, 'Test Complaint', 'Test details', 'pending', NOW());
```

---

### Issue: Users Page Slow / Freezes on Load

**Symptoms**: Users page takes >5 seconds to load or hangs

**Solution**:
1. Check users table row count:
```sql
SELECT COUNT(*) FROM users;
```

If >100K, pagination is working (25/page).

2. Verify indexes exist:
```sql
SHOW INDEXES FROM users;
```

Should include indexes on: `email`, `role`, `market_id`, `is_active`

3. If missing, add them:
```sql
ALTER TABLE users ADD INDEX idx_email (email);
ALTER TABLE users ADD INDEX idx_role (role);
ALTER TABLE users ADD INDEX idx_market (market_id);
ALTER TABLE users ADD INDEX idx_active (is_active);
```

4. Check for slow queries in MySQL logs:
```bash
dir C:\xampp\mysql\data\PlaceParole\
```

If `mysql-slow.log` exists, review recent queries.

---

### Issue: Email/SMS/WhatsApp Checks Show Warning

**Symptoms**: Health page shows ⚠️ for Email or integrations

**Solution for Email**:
1. Check .env has GMAIL credentials:
```bash
cat .env | find "GMAIL_"
```

2. If missing, add to `.env`:
```
GMAIL_USERNAME=your-email@gmail.com
GMAIL_PASSWORD=your-16-char-app-password
```

3. Get app password from: https://myaccount.google.com/apppasswords

**Solution for WhatsApp**:
1. Check Twilio vendor exists:
```bash
dir vendor/twilio
```

2. If missing, run:
```bash
composer require twilio/sdk
```

3. Check .env has Twilio credentials:
```bash
cat .env | find "TWILIO_"
```

---

### Issue: Activity Log Not Recording Actions

**Symptoms**: Activity Log page empty or doesn't update after user creation

**Solution**:
1. Verify table is populated:
```sql
SELECT COUNT(*) FROM admin_activity_log;
```

2. Check logAdminAction() is called in user_create.php:
   - Line should contain: `logAdminAction($pdo, $_SESSION['user_id'], 'user_created', 'user', $userId, ...)`

3. Verify config/admin_helpers.php has logAdminAction() function:
```bash
grep -n "function logAdminAction" config/admin_helpers.php
```

4. If empty, function might not exist. Verify file content:
```bash
wc -l config/admin_helpers.php
```

Should be 800+ lines.

---

### Issue: CSRF Token Validation Failing

**Symptoms**: Form submission shows "CSRF token mismatch" error

**Solution**:
1. Verify csrf.php exists and is included:
```bash
grep -n "csrf_verify()" modules/admin/user_create.php
```

2. Check token is in form:
```bash
grep -n "csrf_token" modules/admin/user_create.php
```

3. Clear cookies (browser Ctrl+Shift+Delete)
4. Try in incognito window
5. Verify session is starting in index.php:
```bash
grep -n "session_start()" index.php
```

---

## Production Hardening

### Before Going Live

1. **Change Default Admin Password**
   ```sql
   UPDATE users SET password = ? 
   WHERE email = 'admin@placeparole.local';
   -- Use password_hash() to generate new hash
   ```

2. **Enable HTTPS**
   - Obtain SSL certificate (Let's Encrypt for free)
   - Configure Apache:
   ```apache
   <VirtualHost *:443>
       ServerName your-domain.com
       SSLEngine on
       SSLCertificateFile /etc/ssl/certs/your.crt
       SSLCertificateKeyFile /etc/ssl/private/your.key
       DocumentRoot /var/www/html/PlaceParole
   </VirtualHost>
   ```

3. **Secure Session Cookies** (in `php.ini` or `.htaccess`):
   ```php
   session.cookie_secure = 1        // HTTPS only
   session.cookie_httponly = 1      // No JavaScript access
   session.cookie_samesite = 'Lax'  // CSRF protection
   session.gc_maxlifetime = 3600    // 1 hour expiry
   ```

4. **Increase Bcrypt Cost** (in config/admin_helpers.php):
   ```php
   // Change from cost 12 → 13 (slower, more secure)
   // Affects only NEW passwords created after change
   password_hash($password, PASSWORD_BCRYPT, ['cost' => 13])
   ```

5. **Set File Permissions**
   ```bash
   chmod 750 modules/admin/
   chmod 640 database_migrations/
   chmod 640 config/db.php
   chmod 640 .env
   ```

6. **Hide Errors in Production** (in `php.ini`):
   ```
   display_errors = Off
   log_errors = On
   error_log = /var/log/php/error.log
   ```

7. **Regular Backups**
   ```bash
   # Daily backup script
   mysqldump -u root -p PlaceParole > backup_$(date +%Y%m%d).sql
   ```

---

## Performance Optimization

### Index Optimization

Verify all indexes exist (already added by migration):

```sql
-- admin_activity_log indexes
ALTER TABLE admin_activity_log 
ADD INDEX idx_actor (actor_id),
ADD INDEX idx_action (action_type),
ADD INDEX idx_created (created_at);

-- system_health_checks index
ALTER TABLE system_health_checks 
ADD UNIQUE INDEX uk_check_name (check_name);

-- dashboard_widget_config index
ALTER TABLE dashboard_widget_config 
ADD UNIQUE INDEX uk_admin_widget (admin_id, widget_id);
```

### Query Caching

If using MySQL 5.7 (not 8.0+, which removed query cache):

```sql
SET GLOBAL query_cache_size = 268435456; # 256MB
SET GLOBAL query_cache_type = 1;          # ON
```

### Slow Query Log

Monitor slow queries:

```sql
SET GLOBAL slow_query_log = 'ON';
SET GLOBAL long_query_time = 2; # Log queries >2s
```

Then analyze:
```bash
mysqldumpslow /var/log/mysql/slow.log | head -20
```

### Polling Optimization

Dashboard polls every 30 seconds. To reduce load:

1. Increase polling interval in `assets/js/admin_dashboard.js`:
   ```javascript
   // Change from 30000ms → 60000ms (1 minute)
   setInterval(() => pollMetrics(), 60000);
   ```

2. Add Redis caching (optional, advanced):
   ```php
   $redis = new Redis();
   $redis->connect('127.0.0.1', 6379);
   
   $cacheKey = 'dashboard_metrics_' . date('YmdHi');
   if ($cached = $redis->get($cacheKey)) {
       return json_decode($cached);
   }
   ```

### Log Archiving

Prevent logs from growing too large:

```bash
# Daily cron job
find /var/www/html/PlaceParole/logs -name "*.log" -mtime +30 -delete
```

Or use `logrotate`:
```
/var/www/html/PlaceParole/logs/*.log {
    daily
    rotate 30
    compress
    delaycompress
    notifempty
}
```

---

## Support & Diagnostics

### Key SQL Queries for Troubleshooting

**Check admin user exists:**
```sql
SELECT id, name, email, role, is_active FROM users WHERE role='admin';
```

**Check recent activity:**
```sql
SELECT 
    DATE_FORMAT(created_at, '%Y-%m-%d %H:%i:%s') as time,
    u.name,
    action_type,
    subject_type,
    COUNT(*) as count
FROM admin_activity_log al
LEFT JOIN users u ON al.actor_id = u.id
WHERE al.created_at > DATE_SUB(NOW(), INTERVAL 24 HOUR)
GROUP BY DATE_FORMAT(created_at, '%Y-%m-%d %H:%i'), u.name, action_type
ORDER BY al.created_at DESC;
```

**Check widget configuration:**
```sql
SELECT 
    u.email as admin_email,
    dwc.widget_id,
    dwc.is_visible,
    dwc.sort_order
FROM dashboard_widget_config dwc
LEFT JOIN users u ON dwc.admin_id = u.id
ORDER BY u.email, dwc.sort_order;
```

**Check system health status:**
```sql
SELECT 
    check_name,
    status,
    response_ms,
    DATE_FORMAT(checked_at, '%Y-%m-%d %H:%i:%s') as last_checked,
    details
FROM system_health_checks
ORDER BY checked_at DESC;
```

### Enable Debug Logging

Add to top of `modules/admin/dashboard.php`:

```php
error_reporting(E_ALL);
ini_set('display_errors', 1);
ini_set('log_errors', 1);
ini_set('error_log', __DIR__ . '/../../logs/admin_debug.log');
```

Then view log:
```bash
tail -f logs/admin_debug.log
```

### Contact Support

If issues persist:
1. Collect `php_error.log` from XAMPP/logs/
2. Run diagnostics:
   ```bash
   php verify_schema.php
   php setup_verify.php
   ```
3. Export activity log (CSV from admin panel)
4. Email logs + describe steps to reproduce

---

**Setup Complete!** 🎉

Your Super Admin Dashboard is now live at:  
**http://localhost/PlaceParole/modules/admin/dashboard.php**

Next steps:
- [ ] Complete Testing Checklist above
- [ ] Add test data to verify widgets populate
- [ ] Invite other admins via Users page
- [ ] Configure integrations (email/SMS/WhatsApp) in .env
- [ ] Schedule backups
- [ ] Plan production deployment (see Production Hardening)

Questions? Check the logs or troubleshooting section above.
