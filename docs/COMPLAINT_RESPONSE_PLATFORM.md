# Complaint Response Platform - Feature Design & Implementation

## 📋 Overview

This document outlines the design of a comprehensive complaint response platform that allows managers to respond directly to sellers through the same communication channels used for submission, with full message threading and conversation history.

---

## 🎯 Core Objectives

1. **Multi-Channel Response**: Send responses via the same channel complaint was submitted (web, SMS, email, Gmail)
2. **Message Threading**: Maintain full conversation history between manager and seller
3. **Centralized Interface**: Dedicated manager dashboard to view and manage all complaint threads
4. **Smart Notifications**: Notify sellers of responses through their preferred channel
5. **Compliance Tracking**: Monitor SLA deadlines and response times

---

## 🗄️ Database Schema Enhancements

### New Tables

#### Table 1: `complaint_messages` (Message Threading)
```sql
CREATE TABLE IF NOT EXISTS complaint_messages (
    id                INT AUTO_INCREMENT PRIMARY KEY,
    complaint_id      INT NOT NULL,
    sender_id         INT NOT NULL,              -- Manager or Seller ID
    sender_role       ENUM('seller', 'manager') NOT NULL,
    message_type      ENUM('submission', 'response', 'internal_note') NOT NULL,
    content           TEXT NOT NULL,
    channel_sent      ENUM('web', 'sms', 'email', 'gmail') NOT NULL,
    sent_via_id       VARCHAR(255) NULL,         -- External message ID (for SMS/email tracking)
    sent_at           TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    read_at           TIMESTAMP NULL,            -- When seller read manager's response
    FOREIGN KEY (complaint_id) REFERENCES complaints(id) ON DELETE CASCADE,
    FOREIGN KEY (sender_id) REFERENCES users(id),
    INDEX (complaint_id),
    INDEX (sender_id),
    INDEX (sent_at)
);
```

#### Table 2: `complaint_attachments` (Thread Attachments)
```sql
CREATE TABLE IF NOT EXISTS complaint_attachments (
    id                INT AUTO_INCREMENT PRIMARY KEY,
    message_id        INT NOT NULL,
    complaint_id      INT NOT NULL,
    file_path         VARCHAR(255) NOT NULL,
    file_name         VARCHAR(255) NOT NULL,
    file_mime         VARCHAR(100) NOT NULL,
    file_size         INT NOT NULL,
    uploaded_at       TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (message_id) REFERENCES complaint_messages(id) ON DELETE CASCADE,
    FOREIGN KEY (complaint_id) REFERENCES complaints(id) ON DELETE CASCADE,
    INDEX (complaint_id)
);
```

#### Table 3: `complaint_notifications` (Notification Log)
```sql
CREATE TABLE IF NOT EXISTS complaint_notifications (
    id                INT AUTO_INCREMENT PRIMARY KEY,
    complaint_id      INT NOT NULL,
    message_id        INT,                       -- NULL for system notifications
    recipient_id      INT NOT NULL,
    notification_type ENUM('new_complaint', 'response_received', 'status_change', 'sla_warning') NOT NULL,
    channel           ENUM('web', 'sms', 'email', 'in_app') NOT NULL,
    status            ENUM('pending', 'sent', 'failed', 'read') DEFAULT 'pending',
    external_id       VARCHAR(255) NULL,        -- For tracking actual delivery
    attempt_count     INT DEFAULT 0,
    last_attempt_at   TIMESTAMP NULL,
    created_at        TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (complaint_id) REFERENCES complaints(id) ON DELETE CASCADE,
    FOREIGN KEY (message_id) REFERENCES complaint_messages(id) ON DELETE SET NULL,
    FOREIGN KEY (recipient_id) REFERENCES users(id),
    INDEX (complaint_id),
    INDEX (status),
    INDEX (created_at)
);
```

### Modified Tables

#### Update `complaints` Table
```sql
ALTER TABLE complaints ADD COLUMN (
    thread_count      INT DEFAULT 0,             -- Number of messages in thread
    last_message_at   TIMESTAMP NULL,            -- When last message was added
    manager_id        INT NULL,                  -- Assigned manager
    assigned_at       TIMESTAMP NULL,
    resolved_at       TIMESTAMP NULL,
    response_time_secs INT NULL,                -- Seconds to first response
    FOREIGN KEY (manager_id) REFERENCES users(id)
);
```

---

## 🏗️ Architecture & Files to Create

### 1. **Database Migration File**
- **File**: `database_migrations/001_add_complaint_threading.sql`
- Creates all tables and alters existing ones

### 2. **Core Utilities**

#### `config/complaint_helpers.php`
- `getComplaintThread($complaint_id)` - Fetch full conversation thread
- `sendComplaintResponse($complaint_id, $manager_id, $message, $channel)` - Send response through channel
- `routeResponseByChannel($channel, $seller_phone, $seller_email, $message, $ref_code)` - Route to correct integrations
- `markMessageAsRead($message_id)`
- `checkSLADeadline($complaint_id)` - Check if overdue

#### `config/notification_handler.php`
- `createNotification($complaint_id, $type, $recipient_id, $channel)` - Create notification record
- `sendNotification($notification_id)` - Execute sending
- `logNotificationRetry($notification_id, $error)` - Track failed attempts
- `getUnreadNotifications($user_id)` - Get seller's unread notifications

### 3. **Manager Interface**

#### `modules/complaints/dashboard.php` (Enhanced)
- Kanban/list view with threading preview
- Quick stats: pending, in-review, resolved, overdue
- Filter by status, assignee, SLA
- One-click access to detail view

#### `modules/complaints/detail.php` (NEW - Replaces respond.php)
- Full conversation thread display
- Real-time message list with avatars
- Manager response form with channel selector
- Attachment upload capability
- Status change controls
- SLA tracking and warnings
- Seller read receipts

#### `modules/complaints/respond.php` (ENHANCED)
- Backward compatible but enhanced
- Auto-selects original channel
- Pre-fills with best practices
- Syntax highlighting for different statuses
- Draft saving capability

### 4. **Seller Interface**

#### `modules/complaints/track.php` (ENHANCED)
- View full conversation thread
- See timestamps and read receipts
- Quick-reply interface (optional)
- Notification history

#### `modules/complaints/my_complaints.php` (ENHANCED)
- Thread preview with last manager message
- Unread badges
- Threading visual indicator

### 5. **Communication Integration Updates**

#### `integrations/response_router.php` (NEW)
```
Routes manager responses to:
├── Email: sendResponseEmail()
├── SMS: sendResponseSMS()
├── Gmail: sendResponseGmail()
├── Web: createInAppNotification()
└── Fallback: logFailedResponse()
```

#### `integrations/email_notify.php` (ENHANCED)
- `sendComplaintResponseEmail($to, $ref_code, $message, $thread_preview)`
- Include thread context in email

#### `integrations/sms_send.php` (ENHANCED)
- `sendComplaintResponseSMS($phone, $ref_code, $preview_message)`
- Truncate for SMS constraints

#### `integrations/notification_service.php` (NEW)
- `sendWebNotification()` - Push notification to web
- `sendInAppNotification()` - Add to notification center
- `getNotificationBadge()` - Unread count

### 6. **Admin/Analytics**

#### `modules/admin/response_analytics.php` (NEW)
- Average response time
- Channel usage statistics  
- SLA compliance rate
- Manager performance metrics

---

## 🔄 User Flows

### Manager Responding to Complaint

```
1. Manager logs in → Dashboard
2. Filters/searches for complaint
3. Clicks on complaint to view thread
4. Sees full conversation history
5. Selects response channel (auto-filled = original channel)
6. Types response message
7. Optionally: Uploads attachment
8. Select status (pending → in_review → resolved)
9. Click "SEND RESPONSE"
10. System routes to appropriate channel:
    - If SMS: Send SMS via Textbelt/Vonage
    - If Email: Send via PHPMailer
    - If Gmail: Send via Gmail API
    - If Web: Create in-app notification
11. Log message in complaint_messages table
12. Create notification record
13. Update complaint: last_message_at, thread_count
14. Show success confirmation
15. Seller receives notification in same channel
```

### Seller Receiving & Viewing Response

```
1. Seller receives notification:
   - SMS: "Your complaint MKT-2024-ABC123 has an update. Reply: [link]"
   - Email: "Update on complaint MKT-2024-ABC123: [preview]"
   - Web: In-app notification badge
2. Seller clicks link/notification
3. Views full complaint thread with:
   - Original complaint (timestamp, attachments)
   - All manager responses (in chronological order)
   - Read receipts of manager messages
4. Optionally: Text message from seller UI (optional feature)
5. Mark as read → notification cleared
```

---

## 📊 Data Flow Diagram

```
SELLER SUBMITS COMPLAINT
        ↓
    [complaints table]
    [complaint_messages: type=submission]
        ↓
MANAGER VIEWS DASHBOARD
        ↓
[Read complaint_messages for thread]
        ↓
MANAGER RESPONDS
        ↓
[INSERT complaint_messages: type=response]
[INSERT complaint_notifications]
[Trigger channel router]
        ↓
    ┌───────────────────────────────────┐
    │   ROUTE BY ORIGINAL CHANNEL       │
    ├───────────────────────────────────┤
    │ SMS → Textbelt/Vonage API         │
    │ Email → PHPMailer SMTP            │
    │ Gmail → Gmail API                 │
    │ Web → In-app notification         │
    └───────────────────────────────────┘
        ↓
[UPDATE complaint_notifications: status=sent]
[UPDATE complaints: thread_count++, last_message_at]
        ↓
SELLER RECEIVES NOTIFICATION
        ↓
[Views complaint thread via track.php]
        ↓
[UPDATE complaint_messages: read_at]
[UPDATE complaint_notifications: status=read]
```

---

## 🔐 Security Considerations

1. **Authorization**: Verify manager owns market before viewing complaints
2. **Audit Trail**: Log all responses with sender, timestamp, channel
3. **Data Validation**: Sanitize all message content
4. **Rate Limiting**: Prevent abuse (max responses per hour)
5. **Encryption**: Store sensitive data encrypted in transit
6. **File Upload**: Validate attachment types/sizes before storing
7. **Access Control**: Sellers can only see their own complaints

---

## 🎨 UI/UX Components

### Manager: Complaint Detail Thread View
```
┌─────────────────────────────────────────────┐
│ Complaint: MKT-2024-ABC123                  │
│ Seller: John Doe (Stall #12)                │
│ Category: Sanitation | Status: In Review    │
├─────────────────────────────────────────────┤
│ CONVERSATION THREAD                          │
├─────────────────────────────────────────────┤
│ [Seller Avatar] Seller - Mar 28, 2:15 PM    │
│ "Stall is flooded with water..."            │
│ [Photo attachment]                          │
│                                             │
│ [Manager Avatar] You - Mar 28, 3:45 PM     │
│ "We're investigating this. Will update..."  │
│ ✓ Seller read on Mar 28, 4:00 PM           │
│                                             │
│ [Seller Avatar] Seller - Mar 29, 9:00 AM   │
│ "Any progress? Losing business..."          │
│                                             │
│ SEND RESPONSE FORM                          │
├─────────────────────────────────────────────┤
│ Status: ⬤ Pending ○ In Review ○ Resolved   │
│ Channel: [SMS ▼]  (Original: SMS)           │
│                                             │
│ Message: ┌──────────────────────┐           │
│          │ Drainage issue...    │           │
│          │                      │ [x] 245   │
│          └──────────────────────┘           │
│                                             │
│ Attachments: [+ Add Files]                  │
│                                             │
│ [Cancel]  [Save as Draft]  [Send Response] │
└─────────────────────────────────────────────┘
```

### Seller: Complaint Thread View
```
┌─────────────────────────────────────────────┐
│ My Complaint: MKT-2024-ABC123               │
│ Status: IN REVIEW (SLA: 2 days left)        │
├─────────────────────────────────────────────┤
│ [Seller Avatar] You - Mar 28, 2:15 PM      │
│ Stall flooded with water and sewage        │
│ [Photo attachment]                          │
│                                             │
│ [Manager Avatar] Market Manager - Mar 28   │
│ "We've logged your complaint. Investigation │
│  in progress. Will update within 72 hours." │
│                                             │
│ [Green checkmark] You read this             │
│                                             │
│ [Timeline continues...]                    │
│                                             │
│ STATUS UPDATES:                             │
│ ✓ Submitted (Mar 28, 2:15 PM)              │
│ ✓ Acknowledged (Mar 28, 3:45 PM)           │
│ ○ Resolved (Pending)                       │
└─────────────────────────────────────────────┘
```

---

## 📋 Implementation Checklist

### Phase 1: Database (Week 1)
- [ ] Create migration file for new tables
- [ ] Add new columns to complaints table
- [ ] Test migration on dev database
- [ ] Verify foreign key relationships

### Phase 2: Core Logic (Week 1-2)
- [ ] Create complaint_helpers.php with threading functions
- [ ] Create notification_handler.php
- [ ] Create response_router.php with channel logic
- [ ] Enhance email/SMS integrations
- [ ] Write unit tests for routing logic

### Phase 3: Manager Interface (Week 2-3)
- [ ] Build detail.php with thread display
- [ ] Implement response form with channel selector
- [ ] Add status transition controls
- [ ] Build dashboard enhancements
- [ ] Add filtering and search

### Phase 4: Seller Interface & Notifications (Week 3)
- [ ] Enhance track.php with thread view
- [ ] Enhance my_complaints.php with previews
- [ ] Implement notification service
- [ ] Add read receipts
- [ ] Test notifications in all channels

### Phase 5: Analytics & Polish (Week 3-4)
- [ ] Create response_analytics.php
- [ ] Add performance metrics
- [ ] SLA compliance dashboard
- [ ] Performance optimization
- [ ] Comprehensive testing

### Phase 6: Deployment & Documentation (Week 4)
- [ ] Load testing
- [ ] Security audit
- [ ] User documentation
- [ ] Admin training materials
- [ ] Deploy to production

---

## 🚀 Next Steps

1. **Review this design** with stakeholders
2. **Prioritize features** (MVP vs. phase 2)
3. **Begin Phase 1** database implementation
4. **Set up version control** for tracking changes
5. **Establish testing procedures** before deployment

---

## 📝 Notes

- **Multi-language**: All UI strings must support English/French translations
- **Mobile-first**: Responsive design for manager/seller on mobile devices
- **Accessibility**: WCAG 2.1 AA compliance for all interfaces
- **Performance**: Optimize queries for large complaint volumes (1000+ records)
- **Scalability**: Design for future multi-market management
