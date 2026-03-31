# Complaint Response Platform - Technical Architecture & API Reference

## 📐 System Architecture

### High-Level Flow Diagram

```
┌─────────────────────────────────────────────────────────────────────┐
│                         SELLERS                                     │
│  Submit Complaint via Web/SMS/Email/Gmail                           │
└────────────┬────────────────────────────────────────────────────────┘
             │
             ▼ (Channel selected during submission)
┌─────────────────────────────────────────────────────────────────────┐
│                    DATABASE: complaints table                        │
│  Stores: id, ref_code, category, description, channel, status,     │
│          sla_deadline, response_time_secs, manager_id, etc.        │
└────────────┬────────────────────────────────────────────────────────┘
             │
             ├─────────────────────────────────────────────────────┐
             │                                                     │
             ▼                                                     ▼
┌──────────────────────────────┐            ┌──────────────────────────┐
│ complaint_messages table     │            │ complaint_notifications  │
│ (Threading/Conversation)     │            │ (Delivery Log)          │
├──────────────────────────────┤            ├──────────────────────────┤
│ • Original submission        │            │ • Email sent/failed     │
│ • Manager responses          │            │ • SMS sent/failed       │
│ • Read receipts              │            │ • Retry logic           │
│ • Timestamps                 │            │ • Delivery tracking     │
└──────────────────────────────┘            └──────────────────────────┘
             │                                      │
             │                                      ▼
             │                         ┌─────────────────────────┐
             │                         │ ROUTING ENGINE          │
             │                         │ (response_router.php)   │
             │                         │ Selects: SMS/Email/Web  │
             │                         └────┬────────────────────┘
             │                              │
             └──────────────────────┬───────┘
                                    │
                    ┌───────────────┼───────────────┐
                    │               │               │
                    ▼               ▼               ▼
            ┌───────────────┐ ┌──────────┐ ┌──────────────┐
            │ Email via     │ │ SMS via  │ │ Web Push &   │
            │ PHPMailer     │ │ Textbelt │ │ In-App       │
            │ (Gmail SMTP)  │ │ /Vonage  │ │ Notification │
            └────────┬──────┘ └────┬─────┘ └──────┬───────┘
                     │             │              │
                     │             │              │
                     └─────────────┼──────────────┘
                                   │
                                   ▼
                    ┌─────────────────────────────┐
                    │      SELLER RECEIVES        │
                    │  Notification in Original   │
                    │  Channel (SMS/Email/Web)    │
                    └─────────────────────────────┘
                                   │
                                   ▼
                    ┌─────────────────────────────┐
                    │   SELLER CLICKS LINK        │
                    │  Opens track.php to view    │
                    │  full conversation thread   │
                    └─────────────────────────────┘
```

---

## 💾 Database Schema Map

### Table Relationships

```
markets (1)
  ├─ complaints (N)
  │   ├─ complaint_messages (N)
  │   │   └─ complaint_attachments (N)
  │   ├─ complaint_notifications (N)
  │   └─ complaint_drafts (N)
  │
  └─ users
      ├─ As seller → complaints (N)
      ├─ As manager → complaints assigned (N)
      └─ user_id in notifications (N)

Key Foreign Keys:
- complaints.seller_id → users.id
- complaints.manager_id → users.id
- complaint_messages.sender_id → users.id
- complaint_messages.complaint_id → complaints.id
- complaint_notifications.recipient_id → users.id
```

### Column Purpose Reference

#### complaints Table (Existing + New)
```
EXISTING:
  id              PK
  market_id       FK → markets
  seller_id       FK → users (seller)
  ref_code        Unique reference (MKT-YYYY-XXXXX)
  category        Complaint category
  description     Original complaint text
  channel         'web'|'sms'|'email'|'gmail'
  status          'pending'|'in_review'|'resolved'
  response        OLD field - still here for compatibility
  photo_path      Photo attachment if any
  sla_deadline    72-hour deadline from creation
  created_at      Submission timestamp
  updated_at      Last update timestamp

NEW (for threading):
  thread_count         Number of messages in conversation
  last_message_at      Timestamp of latest message
  last_message_from    'seller' or 'manager'
  manager_id           FK → users (assigned manager)
  assigned_at          When assigned to manager
  resolved_at          When actually resolved
  response_time_secs   Seconds to first manager response
```

#### complaint_messages Table (NEW)
```
id                INT PK
complaint_id      FK → complaints
sender_id         FK → users
sender_role       'seller' or 'manager'
message_type      'submission'|'response'|'internal_note'
content           Message text (TEXT)
channel_sent      How delivered: 'web'|'sms'|'email'|'gmail'
sent_via_id       External ID for SMS/email tracking
sent_at           When message created/sent
read_at           When recipient read it (NULL = unread)

Indexes:
- complaint_id (queries by complaint thread)
- sender_id (queries by person)
- sent_at (ordering)
- (complaint_id, read_at) - finding unread
```

#### complaint_notifications Table (NEW)
```
id                INT PK
complaint_id      FK → complaints
message_id        FK → complaint_messages (nullable)
recipient_id      FK → users (who receives notification)
notification_type 'new_complaint'|'response_received'|'status_change'
                 |'sla_warning'|'sla_breached'
channel           'web'|'sms'|'email'|'gmail'|'in_app'
status            'pending'|'sent'|'failed'|'read'
external_id       Reference from SMS/email provider
attempt_count     Number of send attempts
last_attempt_at   Timestamp of last attempt
error_message     Error if failed
created_at        When notification created

State Transitions:
pending → sent → read
   ↓
  failed → (retry) → sent → read
              ↓ (max retries exceeded)
            failed (permanent)
```

---

## 🔌 API Functions Reference

### config/complaint_helpers.php

#### Core Functions

```php
getComplaintThread($complaint_id, $limit = 100): array
├─ Purpose: Fetch complete conversation thread
├─ Returns: Array of messages with sender details
├─ Usage: Initialize thread display
└─ Example:
   $thread = getComplaintThread(42);
   foreach ($thread as $msg) {
       echo $msg['sender_name'] . ': ' . $msg['content'];
   }
```

```php
getComplaintDetails($complaint_id): array
├─ Purpose: Get full complaint + metadata + seller info
├─ Returns: Associative array with all details
├─ Usage: Load complaint for display
└─ Example:
   $complaint = getComplaintDetails(42);
   echo $complaint['seller_name'];
```

```php
sendComplaintResponse($complaint_id, $manager_id, $message, $status, $channel = null): array
├─ Purpose: Main function - sends response through channel
├─ Params:
│  • $complaint_id: Which complaint
│  • $manager_id: Who is responding
│  • $message: Response text
│  • $status: New status (pending|in_review|resolved)
│  • $channel: Override original (optional)
├─ Returns: ['success' => bool, 'message' => string, 'message_id' => int]
├─ Side Effects:
│  • Creates complaint_messages record
│  • Updates complaint status + metadata
│  • Routes to appropriate channel
│  • Creates notification
│  • Logs response_time_secs
└─ Example:
   $result = sendComplaintResponse(42, 1, "We're investigating", "in_review");
   if ($result['success']) echo "Message ID: " . $result['message_id'];
```

```php
routeResponseByChannel($channel, $seller_phone, $seller_email, $message, $ref_code, $seller_name): array
├─ Purpose: Route message to SMS/Email/Web/Gmail
├─ Params: All required to cover all channels
├─ Returns: ['success' => bool, 'message' => string, 'external_id' => string|null]
├─ Channels:
│  • 'sms' → sendResponseViaSMS()
│  • 'email'/'gmail' → sendResponseViaEmail()
│  • 'web' → createInAppNotification()
└─ Example: Used internally by sendComplaintResponse()
```

```php
markMessageAsRead($message_id, $user_id): bool
├─ Purpose: Record when message was read
├─ Side Effects:
│  • Updates complaint_messages.read_at
│  • Updates notifications.status = 'read'
└─ Example:
   markMessageAsRead(123, $_SESSION['user_id']);
```

```php
checkSLACompliance($complaint_id): array
├─ Purpose: Check SLA status
├─ Returns: [
│  'compliant' => bool (within deadline?),
│  'time_remaining' => int (seconds),
│  'status' => 'ok'|'warning'|'breached'|'resolved'
│ ]
└─ Example:
   $sla = checkSLACompliance(42);
   if ($sla['status'] === 'breached') {
       sendUrgentNotification();
   }
```

```php
getThreadStats($complaint_id): array
├─ Purpose: Get conversation statistics
├─ Returns: [
│  'total_messages' => int,
│  'manager_messages' => int,
│  'seller_messages' => int,
│  'unread_by_seller' => int,
│  'first_message' => datetime,
│  'last_message' => datetime
│ ]
└─ Use for: Display "2 msgs" badge, thread summary
```

```php
getUnreadCount($user_id, $market_id = null): int
├─ Purpose: Get count of unread notifications
├─ Returns: Integer count
└─ Use for: Display notification badges
```

---

### config/notification_handler.php

```php
createNotification($complaint_id, $type, $recipient_id, $channel, $message_id = null): int
├─ Purpose: Create notification record
├─ Types: 'new_complaint', 'response_received', 'status_change', 'sla_warning', 'sla_breached'
├─ Returns: notification ID (0 on failure)
└─ Example:
   $notif_id = createNotification(42, 'response_received', $seller_id, 'sms');
```

```php
sendNotification($notification_id): bool
├─ Purpose: Actually deliver the notification
├─ Logic:
│  • Fetches notification details
│  • Routes to appropriate channel
│  • Updates delivery status
│  • Handles failures with retry flag
├─ Returns: Success boolean
└─ Note: Called by sendComplaintResponse(), also can be called by queues
```

```php
getUnreadNotifications($user_id, $limit = 50): array
├─ Purpose: Get seller's unread notifications
├─ Usage: Display notification center
└─ Returns: Array of notifications with complaint details
```

```php
retryFailedNotifications($max_attempts = 3): array
├─ Purpose: Retry sending failed notifications
├─ Usage: Call from cron/scheduled task
├─ Returns: ['retried' => int, 'successful' => int, 'failed' => int]
└─ Note: Retries only if attempt_count < max_attempts AND 5+ minutes elapsed
```

---

## 🔄 Common Code Patterns

### Pattern 1: Display a Complaint Thread

```php
require_once 'config/complaint_helpers.php';

$complaint_id = $_GET['id'];
$complaint = getComplaintDetails($complaint_id);
$thread = getComplaintThread($complaint_id);

// Now display complaint and thread
echo "Complaint: " . $complaint['ref_code'];
echo "Thread has " . count($thread) . " messages";

foreach ($thread as $msg) {
    echo $msg['sender_name'] . " (" . $msg['sent_at'] . "): ";
    echo $msg['content'];
    if ($msg['read_at']) {
        echo " [Read at " . $msg['read_at'] . "]";
    }
}
```

### Pattern 2: Send Response

```php
require_once 'config/complaint_helpers.php';
require_once 'config/csrf.php';

if ($_POST) {
    csrf_verify(); // Always verify CSRF
    
    $result = sendComplaintResponse(
        $_POST['complaint_id'],
        $_SESSION['user_id'],
        $_POST['message'],
        $_POST['status'],
        $_POST['channel'] ?? null  // null = use original
    );
    
    if ($result['success']) {
        echo "Response sent! Message ID: " . $result['message_id'];
    } else {
        echo "Error: " . $result['message'];
    }
}
```

### Pattern 3: Check SLA and Send Warning

```php
require_once 'config/complaint_helpers.php';
require_once 'config/notification_handler.php';

$sla = checkSLACompliance($complaint_id);

if ($sla['status'] === 'warning') {
    createNotification(
        $complaint_id,
        'sla_warning',
        $complaint['seller_id'],
        'sms'  // Alert via SMS
    );
    echo "SLA warning sent to seller";
} else if ($sla['status'] === 'breached') {
    // Send to manager too
    createNotification(
        $complaint_id,
        'sla_breached',
        $manager_id,
        'email'
    );
}
```

### Pattern 4: Retry Failed Notifications (Cron Job)

```php
// tasks/retry_notifications.php
// Run hourly via cron: 0 * * * * php tasks/retry_notifications.php

require_once 'config/notification_handler.php';

$results = retryFailedNotifications(3); // max 3 attempts

file_put_contents('logs/notification_retry.log', 
    date('Y-m-d H:i:s') . " - Retried: " . json_encode($results) . "\n",
    FILE_APPEND
);

echo "Retried " . $results['retried'] . " - Success: " . $results['successful'];
```

---

## 🧪 Testing Functions

### Unit Test Template

```php
<?php
// tests/test_complaint_helpers.php

require_once '../config/db.php';
require_once '../config/complaint_helpers.php';

function test_getComplaintThread() {
    $thread = getComplaintThread(1);
    
    assert(is_array($thread), "Should return array");
    assert(count($thread) > 0, "Thread should have messages");
    assert(isset($thread[0]['sender_name']), "Should have sender_name");
    
    echo "✓ getComplaintThread PASSED";
}

function test_sendComplaintResponse() {
    $result = sendComplaintResponse(
        1,
        1,
        "Test response",
        "in_review",
        "web"
    );
    
    assert($result['success'] === true, "Should succeed");
    assert($result['message_id'] > 0, "Should return message ID");
    
    echo "✓ sendComplaintResponse PASSED";
}

function test_checkSLACompliance() {
    $sla = checkSLACompliance(1);
    
    assert(isset($sla['compliant']), "Should have compliant key");
    assert(isset($sla['status']), "Should have status");
    
    echo "✓ checkSLACompliance PASSED";
}

// Run all tests
test_getComplaintThread();
test_sendComplaintResponse();
test_checkSLACompliance();

echo "\n✓ All tests passed!";
?>
```

---

## 📊 Performance Considerations

### Query Optimization

```php
// GOOD: Use indexes
$stmt = $pdo->prepare("
    SELECT * FROM complaint_messages 
    WHERE complaint_id = ? 
    ORDER BY sent_at DESC
    LIMIT 100
"); // Uses: complaint_id index, fast

// BAD: No index
$stmt = $pdo->prepare("
    SELECT * FROM complaint_messages 
    WHERE content LIKE ? 
    LIMIT 100
"); // Full table scan, slow
```

### Indexes Created

```sql
-- complaint_messages
INDEX idx_complaint (complaint_id)      ← Load thread
INDEX idx_sender (sender_id)             ← Find by person
INDEX idx_sent_at (sent_at)              ← Order messages
INDEX idx_unread (complaint_id, read_at) ← Find unread

-- complaint_notifications
INDEX idx_complaint (complaint_id)       ← Load notifications
INDEX idx_recipient (recipient_id)       ← User's notifications
INDEX idx_status (status)                ← Find pending
INDEX idx_created (created_at)           ← Order by date
INDEX idx_pending (status, created_at)   ← Find pending to send
```

### Scaling Tips

1. **Archive old threads**: Move messages > 2 years old to `complaint_messages_archive`
2. **Pagination**: Use LIMIT/OFFSET for large threads
3. **Batch notifications**: Re-try 50 at a time, not all
4. **Connection pool**: Use persistent MySQL connections

---

## 🔐 Security Checklist

```
✓ CSRF tokens on all forms
✓ Manager access control: verify market_id before getComplaintDetails()
✓ SQL injection prevention: All queries use prepared statements
✓ XSS prevention: htmlspecialchars() on all output
✓ Email validation: Verify phone/email before sending
✓ Rate limiting: (TODO - add if complaints spike)
✓ Audit logging: All responses logged with timestamp + manager ID
```

---

## 📝 Deployment Notes

### Pre-Production Checklist

- [ ] Database migration successful
- [ ] Indexes created (check with SHOW INDEXES)
- [ ] Email SMTP credentials configured
- [ ] SMS API key valid (if using Vonage)
- [ ] Complaint list links redirect to detail.php
- [ ] Error logs monitored
- [ ] Timestamp correctly set (UTC or local?)

### Monitoring Commands

```bash
# Check for slow queries
mysql> SET GLOBAL slow_query_log = 'ON';
mysql> SET GLOBAL long_query_time = 2;

# Monitor active queries
mysql> SHOW PROCESSLIST;

# Check index usage
mysql> SELECT * FROM INFORMATION_SCHEMA.STATISTICS 
       WHERE TABLE_NAME = 'complaint_messages';

# Monitor notifications queue
SELECT COUNT(*) FROM complaint_notifications WHERE status = 'pending';
```

---

## 🆘 Troubleshooting Reference

| Issue | Query | Solution |
|-------|-------|----------|
| No response visible | `SELECT * FROM complaint_messages WHERE complaint_id = ?` | Check message was inserted |
| SMS not sent | `SELECT * FROM complaint_notifications WHERE id = ? \G` | Check status, error_message |
| Slow thread load | `EXPLAIN SELECT...` | Add indexes |
| Duplicate messages | `SELECT COUNT(*) GROUP BY message_id...` | Transaction issue? |
| SLA calc wrong | `SELECT sla_deadline, NOW() - sla_deadline...` | Timezone issue? |

---

**Version:** 1.0 | **Last Updated:** 2026-03-31 | **For Developers**
