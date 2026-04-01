# Complaint Response Platform - Implementation & Deployment Guide

## 📋 Table of Contents

1. [Quick Summary](#quick-summary)
2. [Installation Steps](#installation-steps)
3. [File Structure](#file-structure)
4. [Configuration](#configuration)
5. [Testing Guide](#testing-guide)
6. [Deployment Checklist](#deployment-checklist)
7. [Troubleshooting](#troubleshooting)

---

## Quick Summary

### What's New

This implementation adds a **complete message threading system** to PlaceParole's complaint management platform. Managers can now:

1. **View full conversation threads** - All messages between seller and manager in chronological order
2. **Respond through any channel** - SMS, Email, Gmail, or Web (auto-selects original channel)
3. **Track read receipts** - See when sellers read manager responses
4. **Access dedicated manager interface** - New `/detail.php` page for individual complaints
5. **Monitor SLA compliance** - Real-time tracking of response deadlines
6. **View analytics** - Performance metrics and response time analytics

### Key Components Created

| File | Purpose |
|------|---------|
| **config/complaint_helpers.php** | Core threading functions |
| **config/notification_handler.php** | Notification system with retry logic |
| **modules/complaints/detail.php** | **NEW** - Manager thread view + response form |
| **modules/admin/response_analytics.php** | **NEW** - Response metrics dashboard |
| **database_migrations/001_add_complaint_threading.sql** | Database schema migration |
| **COMPLAINT_RESPONSE_PLATFORM.md** | Design documentation |

---

## Installation Steps

### Step 1: Database Migration (5 minutes)

1. Open **PhpMyAdmin** in your browser:
   - Go to: `http://localhost/phpmyadmin`
   - Login with your credentials

2. Select the **PlaceParole database**

3. Click **SQL** tab at the top

4. Copy the entire contents of:
   ```
   database_migrations/001_add_complaint_threading.sql
   ```

5. Paste into the SQL editor

6. Click **Go** to execute

   **What gets created:**
   - `complaint_messages` table - stores all messages in threads
   - `complaint_attachments` table - supports file uploads in threads
   - `complaint_notifications` table - tracks notification delivery
   - `complaint_response_templates` table - optional pre-written responses
   - `complaint_drafts` table - saves draft responses
   - Updated `complaints` table with new columns
   - 2 useful views for queries

**Verify Migration Successful:**
```sql
-- Run these queries to confirm
SELECT COUNT(*) FROM complaint_messages;
SELECT COUNT(*) FROM complaint_notifications;
DESCRIBE complaints;  -- Should show new columns
```

### Step 2: Upload PHP Files (2 minutes)

The following files have been created in the workspace:

```
✓ config/complaint_helpers.php              (NEW)
✓ config/notification_handler.php           (NEW)
✓ modules/complaints/detail.php             (NEW)
✓ modules/admin/response_analytics.php      (NEW)
```

These are already in your workspace and ready to use. No manual file copying needed.

### Step 3: Update Existing Files (5 minutes)

We need to update existing complaint files to link to the new threading interface:

#### A) Update `modules/complaints/list.php`

Find the line that displays complaints in a table/list, and change the link from:
```php
// OLD:
<a href="respond.php?id=<?= $complaint['id'] ?>">
```

To:
```php
// NEW:
<a href="detail.php?id=<?= $complaint['id'] ?>">
```

This ensures managers are routed to the new thread view instead of the old form.

#### B) Update `modules/complaints/respond.php`

At the top of the file, add:
```php
// Redirect to new threaded interface
header('Location: detail.php?id=' . $id);
exit;
```

This ensures backward compatibility - old links still work but redirect to new interface.

### Step 4: Verify Communication Integrations (10 minutes)

The system relies on existing integrations. Verify they're set up:

**Email (Required):**
```php
// Check: config/email_notify.php is configured
// These should be set:
$mail->Host = 'smtp.gmail.com';
$mail->Username = 'your-email@gmail.com';
$mail->Password = 'your-app-password';  // Not regular password!
```

**SMS (Optional but recommended):**
```php
// Check: integrations/sms_send.php
// Default provider is 'textbelt' (free, 1 msg/day)
// For production: set to 'vonage' and add credentials
```

**Gmail Integration (Optional):**
- Currently a placeholder - only web/email/SMS are active

---

## File Structure

```
PlaceParole/
├── config/
│   ├── complaint_helpers.php         ← NEW: Threading core functions
│   ├── notification_handler.php      ← NEW: Notification system
│   ├── db.php                        (existing)
│   ├── auth_guard.php                (existing)
│   ├── csrf.php                      (existing)
│   └── ...
│
├── modules/
│   ├── complaints/
│   │   ├── detail.php                ← NEW: Thread view + response form
│   │   ├── list.php                  (existing - UPDATED)
│   │   ├── respond.php               (existing - UPDATED)
│   │   ├── submit.php                (existing)
│   │   ├── track.php                 (existing)
│   │   └── my_complaints.php         (existing)
│   │
│   └── admin/
│       ├── response_analytics.php    ← NEW: Analytics dashboard
│       └── overview.php              (existing)
│
├── database_migrations/
│   └── 001_add_complaint_threading.sql    ← NEW: Migration script
│
├── integrations/
│   ├── email_notify.php              (existing)
│   ├── sms_send.php                  (existing)
│   ├── gmail_fetch.php               (existing)
│   └── ...
│
└── COMPLAINT_RESPONSE_PLATFORM.md    ← NEW: Design docs
```

---

## Configuration

### Email Configuration

**File:** `config/complaint_helpers.php`

Update in `sendResponseViaEmail()` function to use your app credentials:

```php
require_once __DIR__ . '/../integrations/email_notify.php';
// Already configured in email_notify.php
```

### SMS Configuration

**File:** `integrations/sms_send.php`

For production, change:
```php
$provider = 'vonage';  // Instead of 'textbelt'
```

Then set Vonage credentials:
```php
$client = new Vonage\Client(
    new Vonage\Client\Credentials\Basic('YOUR_API_KEY', 'YOUR_API_SECRET')
);
```

### Environment Variables (Optional)

Create a `.env.php` file:
```php
<?php
// .env.php
define('APP_URL', 'http://localhost/PlaceParole');
define('SUPPORT_EMAIL', 'support@placeparole.cm');
define('SMS_PROVIDER', 'textbelt'); // or 'vonage'
?>
```

---

## Testing Guide

### Manual Testing Checklist

#### Test 1: Create a Test Complaint (5 min)

1. Log in as a **Seller**
2. Navigate to **Submit Complaint**
3. Fill form:
   - Category: "Sanitation"
   - Description: "Test complaint for threading"
   - Don't upload photo (optional)
4. Submit
5. Copy the **Reference Code** shown (e.g., MKT-2024-ABC123)

**Verify:**
- [x] Database entry created in `complaints` table
- [x] Seller gets success screen with reference code
- [x] Message created in `complaint_messages` table

#### Test 2: Manager Views Thread (3 min)

1. Log in as a **Manager**
2. Go to **Complaints > List**
3. Click on the test complaint
4. **Should see:**
   - [x] Full complaint details
   - [x] Thread with original complaint message
   - [x] Response form on right side
   - [x] SLA countdown timer
   - [x] Channel selector showing original channel

#### Test 3: Manager Sends Response (5 min)

1. In detail view, fill response form:
   - Channel: Keep as original
   - Status: "In Review"
   - Message: "Thank you for reporting. We're investigating."
2. Click "Send Response"

**Verify:**
- [x] Response appears in thread
- [x] Status updated to "In Review"
- [x] Message saved in `complaint_messages` table
- [x] Notification created in `complaint_notifications` table
- [x] Email sent to seller (check inbox)

#### Test 4: Read Receipt Tracking (3 min)

1. Stay as Manager
2. Look at your response message in thread
3. Wait 1-2 seconds
4. Refresh page
5. **Should see:** "✓ Read on [timestamp]" if seller viewed (or placeholder)

#### Test 5: Multi-Channel Response (5 min)

1. Create another test response but change channel to **SMS**
2. Click "Send Response"
3. Check SMS (if Textbelt configured):
   - Should receive SMS with truncated message and ref code

#### Test 6: Analytics Dashboard (3 min)

1. Log in as Manager
2. Navigate to **Admin > Response Analytics**
3. **Should see:**
   - [x] Total complaints count
   - [x] Resolution rate %
   - [x] SLA compliance %
   - [x] Average response time
   - [x] Channel distribution chart
   - [x] Manager performance table
   - [x] Daily trend data

---

## Deployment Checklist

### Pre-Deployment (Before Going Live)

```
□ Database migration successful
□ All new PHP files uploaded
□ Email integration tested and working
□ SMS integration tested (or disabled)
□ Complaint list.php links updated
□ respond.php redirects working
□ No PHP errors in error logs
□ All static files (CSS/JS) loading
□ Responsive design tested on mobile
□ Seller notifications working
□ Manager dashboard displays correctly
```

### Production Deployment Steps

1. **Backup Database:**
   ```bash
   mysqldump -u user -p database_name > backup_2026-03-31.sql
   ```

2. **Run Migration:**
   - In PhpMyAdmin, run `001_add_complaint_threading.sql`

3. **Deploy PHP Files:**
   - Upload all new/updated files to production server
   - Verify file permissions: 644 for PHP files

4. **Test on Production:**
   - Create test complaint
   - Submit response
   - Verify email delivery
   - Check analytics page

5. **Monitor for Issues:**
   - Watch error logs for first 24 hours
   - Verify notifications are sending
   - Check database performance

### Post-Deployment (After Going Live)

```
□ Monitor error logs for 24 hours
□ Verify managers can access new interfaces
□ Confirm sellers receive all notifications
□ Test SLA deadline calculations
□ Verify read receipts tracking
□ Check analytics data accuracy
□ Collect user feedback
```

---

## Troubleshooting

### Issue: Database Migration Fails

**Symptom:** Error when running SQL migration script

**Solutions:**
1. Check that you're in the correct database
2. Verify database user has CREATE TABLE permissions
3. Ensure no duplicate table names exist
4. Check MySQL error message for specific constraint issues

**Debug:**
```sql
-- Check if tables exist:
SHOW TABLES LIKE 'complaint%';

-- Check complaints table structure:
DESCRIBE complaints;
```

### Issue: Manager Can't See New Detail Page

**Symptom:** Get 404 error or blank page on detail.php

**Solutions:**
1. Verify file exists: `modules/complaints/detail.php`
2. Check file permissions: should be readable (644)
3. View page source - check for PHP errors
4. Check browser console for JS errors

**Debug:**
```php
// Add at top of detail.php temporarily:
error_reporting(E_ALL);
ini_set('display_errors', 1);
```

### Issue: Emails Not Sending

**Symptom:** Response created but seller doesn't get email

**Solutions:**
1. Verify email credentials in `config/complaint_helpers.php`
2. Check Gmail App Password (not regular password)
3. Enable "Less secure app access" if needed
4. Check SMTP connection

**Debug:**
```php
// In sendResponseViaEmail():
echo "Attempting to send to: " . $email;
// Watch for error messages
```

### Issue: SMS Not Sending

**Symptom:** SMS channel selected but SMS not received

**Solutions:**
1. Verify Textbelt is active: `$provider = 'textbelt'`
2. Check phone number format: +237XXXXXXXXX
3. Verify Textbelt API key is correct
4. Check internet connection

**Debug:**
```php
// In sendResponseViaSMS():
error_log("SMS to: $phone, Message: $sms_message");
```

### Issue: Slow Performance/Database Locks

**Symptom:** Pages load slowly, especially analytics

**Solutions:**
1. Check database indexes were created (part of migration)
2. Run optimization:
   ```sql
   ANALYZE TABLE complaints;
   ANALYZE TABLE complaint_messages;
   OPTIMIZE TABLE complaints;
   ```

3. Limit query results if DB is huge

### Issue: SLA Deadline Shows Wrong Time

**Symptom:** SLA warning appears incorrect

**Solutions:**
1. Verify server timezone:
   ```php
   date_default_timezone_set('UTC');  // or your timezone
   ```

2. Check MySQL timezone settings
3. Review calculation in `checkSLACompliance()` function

---

## Next Steps & Enhancements

### Available for Phase 2:

- [ ] Response templates library (quick responses)
- [ ] Bulk complaint operations
- [ ] Advanced filtering/search
- [ ] Complaint assignment workflows
- [ ] Escalation rules
- [ ] Customer satisfaction surveys
- [ ] Attachment downloads in thread
- [ ] Email reply forwarding (turn emails into responses)
- [ ] SMS reply capabilities
- [ ] Complaint merging/linking
- [ ] Canned responses with variables
- [ ] API endpoints for mobile apps

---

## Support & Documentation

- **Design Document:** [COMPLAINT_RESPONSE_PLATFORM.md](COMPLAINT_RESPONSE_PLATFORM.md)
- **Database Schema:** [database_migrations](database_migrations/)
- **Code Comments:** All functions documented inline
- **Function Reference:**
  - `complaint_helpers.php` - Threading core
  - `notification_handler.php` - Notifications
  - `detail.php` - Manager interface
  - `response_analytics.php` - Analytics

---

## FAQ

**Q: Can sellers respond in the thread?**
A: Not in this version - one-way for now. Phase 2 will add seller responses via SMS/email.

**Q: Are old respond.php responses preserved?**
A: Yes - existing responses are migrated to complaint_messages table during migration.

**Q: How long are notifications retried?**
A: Failed notifications retry up to 3 times over ~15 minutes.

**Q: Can multiple managers view the same complaint?**
A: Yes, but only one can respond at a time (design choice for clarity).

**Q: Is there an API?**
A: Not yet - Phase 2 will include REST API for mobile/integrations.

---

## Credits & Version Info

- **Version:** 1.0.0
- **Release Date:** 2026-03-31
- **Database Version:** MySQL 5.7+
- **PHP Version:** 7.4+
- **License:** [Your License]

---

**Questions or Issues?** Please refer to the detailed design document or contact the development team.
