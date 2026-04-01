# 🎯 COMPLAINT RESPONSE PLATFORM - ONE-PAGE REFERENCE

## IMPLEMENTATION CHECKLIST

### Phase 1: Database (Do First)
```
□ Open PhpMyAdmin → PlaceParole DB
□ Click SQL tab
□ Copy entire: database_migrations/001_add_complaint_threading.sql  
□ Click Go → Wait for success message
□ VERIFY: Run test query below
```

**Verify Query:**
```sql
SELECT COUNT(*) FROM complaint_messages;
SELECT COUNT(*) FROM complaint_notifications;
```

### Phase 2: PHP Files (Already Done)
```
✓ config/complaint_helpers.php (READY)
✓ config/notification_handler.php (READY)  
✓ modules/complaints/detail.php (READY)
✓ modules/admin/response_analytics.php (READY)

Files already in workspace - NO ACTION NEEDED
```

### Phase 3: Update Links (Do Now)

**File: modules/complaints/list.php** 

Find (around line 80-90):
```php
<a href="respond.php?id=<?= $complaint['id'] ?>">
```

Change to:
```php
<a href="detail.php?id=<?= $complaint['id'] ?>">
```

**File: modules/complaints/respond.php**

Add at top (after line 6 - after manager_only()):
```php
header('Location: detail.php?id=' . $id);
exit;
```

### Phase 4: Verify Configuration
```
□ Email: Check config/complaint_helpers.php imports email_notify.php
□ SMS: Check integrations/sms_send.php set to 'textbelt' or 'vonage'  
□ No credentials needed for dev (uses "textbelt" free SMS)
```

### Phase 5: Test (Do This!)

**Test 1: Submit Complaint**
1. Log in as SELLER
2. Click "Submit Complaint"
3. Fill: Category + Description + pick photo (optional)
4. Submit → Copy Reference Code
5. ✓ Check: Complaint appears in database

**Test 2: Manager Views**
1. Log in as MANAGER
2. Go Complaints → List
3. Click on your test complaint
4. ✓ Should see: Full thread + SLA timer + response form on right

**Test 3: Send Response**
1. Type in response message
2. Select Channel: SMS (or keep as original)
3. Select Status: In Review
4. Click "Send Response"
5. ✓ Check: Message appears in thread immediately

**Test 4: Check Analytics**
1. Go Admin → Response Analytics
2. ✓ Should see: Complaint counted, time tracked, etc

### Phase 6: Go Live!

Monitor for 24 hours:
```
□ Check error logs: no PHP errors
□ Verify SMS/Email working (send yourself one)
□ Check analytics has data
□ Get feedback from 2-3 managers
```

---

## QUICK REFERENCE

### Manager Workflow (in 3 steps)
```
1. Complaints → Click complaint
2. Type response in form on right side
3. Click "Send Response" → Done! Seller notified
```

### URL Reference
```
List Complaints:      /modules/complaints/list.php
Thread View:          /modules/complaints/detail.php?id=42
Track Complaint:      /modules/complaints/track.php?ref=MKT-2024-ABC
Analytics:            /modules/admin/response_analytics.php
```

### SMS Character Limits
```
Keep response under 160 characters for single SMS
Or let system split into multiple messages
Include reference code at start
```

### SLA Deadlines
```
Submitted: Now
First response needed by: Now + 24 hours (soft)
Resolution needed by: Now + 72 hours (hard)
Status Red = Past deadline (emergency!)
```

### Response Status Guide
```
Pending   = Initial alert sent, not started
In Review = Being investigated, actively worked
Resolved  = Fixed, closed, no follow-up needed
```

### Channel Selection
```
Web    = In-app notification (good for general)
SMS    = Text message (fast, urgent)
Email  = Detailed info (complex issues)
Original = Keeps seller's preference (RECOMMENDED)
```

---

## FILE MAP (Quick Reference)

### Core Functions
```
complaint_helpers.php
  └─ sendComplaintResponse()        ← Main function
  └─ getComplaintThread()           ← Load conversation
  └─ checkSLACompliance()           ← Check deadline
  └─ routeResponseByChannel()       ← Send SMS/Email/Web

notification_handler.php
  └─ createNotification()           ← Queue notification
  └─ sendNotification()             ← Deliver notification
  └─ retryFailedNotifications()     ← Retry failed (cron)
```

### Manager Pages
```
detail.php                          ← NEW! Main page
  └─ Shows thread (left)
  └─ Response form (right)
  └─ SLA timer (top)
  └─ Manager info display

response_analytics.php              ← NEW! Metrics
  └─ Overall stats
  └─ By channel
  └─ By category
  └─ By manager
  └─ Daily trends
```

### Database
```
complaint_messages                  ← Each message in conversation
complaint_attachments               ← Files in messages
complaint_notifications             ← What was sent to whom
complaint_response_templates        ← Optional pre-written responses
complaint_drafts                    ← In-progress responses

Existing: complaints table
  └─ Added: thread_count, manager_id, response_time_secs, etc.
```

---

## COMMON PROBLEMS & FIXES

| Problem | Quick Fix |
|---------|-----------|
| "Complaint not found" | Check ID in URL, try different complaint |
| SMS not sending | Check phone format: +237XXXXXXXXX, enable API key |
| Email not sending | Check Gmail app password set, enable less-secure access |
| Thread won't load | Check browser console (F12), clear cache |
| Slow performance | Check database indexes exist |
| Can't change status | Fill response message first (required) |
| Wrong channel sent | Send another response via correct channel |

---

## TESTING DATA SETUP (SQL)

Copy-paste into PhpMyAdmin SQL tab:

```sql
-- Create test seller
INSERT INTO users (market_id, name, phone, email, role, stall_no, password, lang) 
VALUES (1, 'Test Seller', '+237612345678', 'test@seller.com', 'seller', '99', SHA2('test', 256), 'en')
ON DUPLICATE KEY UPDATE id = id;

-- Get IDs
SET @seller_id = (SELECT id FROM users WHERE email = 'test@seller.com');
SET @manager_id = (SELECT id FROM users WHERE role = 'manager' LIMIT 1);

-- Create test complaint
INSERT INTO complaints (market_id, seller_id, ref_code, category, description, channel, status, sla_deadline)
VALUES (1, @seller_id, 'TST-2026-00001', 'Sanitation', 'Test complaint', 'web', 'pending', DATE_ADD(NOW(), INTERVAL 3 DAY));

-- Verify
SELECT * FROM complaints WHERE ref_code = 'TST-2026-00001';
```

---

## PERFORMANCE NOTES

| Action | Expected Time |
|--------|----------------|
| Load thread | < 100ms |
| Send response | 200-500ms |
| Retry notifications | 500ms/50 items |
| Load analytics | 1-2 seconds |

**If slower:** Check database indexing (SHOW INDEXES)

---

## SECURITY CHECKLIST

```
✓ CSRF tokens on all forms
✓ Authorization check: manager can only see their market
✓ SQL injection: all queries prepared statements
✓ XSS: all output htmlspecialchars()
✓ Files: stored outside web root
✓ Emails: validated before sending
```

---

## SUPPORT & DOCS

| Question | Read This |
|----------|-----------|
| How do I use the system? | MANAGER_USER_GUIDE.md |
| How do I deploy it? | IMPLEMENTATION_GUIDE.md |
| What's the architecture? | COMPLAINT_RESPONSE_PLATFORM.md |
| API reference? | TECHNICAL_REFERENCE.md |
| Testing? | database_migrations/testing_and_queries.sql |
| Quick ref? | DEPLOYMENT_PACKAGE_INDEX.md |

---

## DEPLOYMENT SIGN-OFF

Manager/Team Lead: _________________  Date: _______

QA Lead: _________________________  Date: _______

IT/DevOps: ________________________  Date: _______

---

## GO-LIVE CHECKLIST (Before Pushing Live)

```
DATABASE
☐ Migration successful 
☐ All new tables present
☐ Indexes optimized
☐ No constraint errors

APPLICATION  
☐ All PHP files uploaded
☐ Links updated (list.php, respond.php)
☐ Error logs clean
☐ No permission issues

COMMUNICATION
☐ Email working
☐ SMS working (or disabled gracefully)
☐ Gmail configured (or defer to Phase 2)

TESTING
☐ Create test complaint
☐ Manager sends response
☐ Notification received
☐ Analytics shows data
☐ Performance acceptable

MONITORING
☐ Error logs set to monitor
☐ Database space OK
☐ Notification queue monitored
☐ User feedback channel open
```

---

## 24-HOUR POST-LAUNCH CHECKLIST

```
HOUR 1: Have someone send test update
HOUR 2: Check notifications arrived
HOUR 4: Review error logs (should be empty)
HOUR 8: Check with 2-3 managers (feedback?)
HOUR 12: Verify analytics data accurate
HOUR 24: Check database space growth (normal?)
```

---

**🎉 Ready to Deploy!** 

Questions? Check DEPLOYMENT_PACKAGE_INDEX.md for complete documentation links.
