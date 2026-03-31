# Complaint Response Platform - Manager User Guide

## 🎯 Quick Start for Managers

### What's New?

You now have a **threaded conversation system** for managing complaints. Instead of just viewing and responding once, you can see the complete conversation history and track all communication in one place.

---

## 📖 How to Use the New System

### 1. Viewing Complaints

#### Old Way (Still Works)
- Click "Complaints" in menu
- See list of all complaints

#### New Way (Better!)
- Click on any complaint
- Opens full **Conversation Thread** view
- See entire history with timestamps

---

### 2. Responding to a Complaint

#### Step-by-Step

**1. Open the Complaint**
- From Complaints list, click the complaint
- Shows: "Conversation Thread" on left, "Send Response" on right

**2. View the Thread**
- See original complaint from seller
- See all previous responses from you and other managers
- Look for ✓ read receipts showing when seller read your messages

**3. Write Your Response**
```
Response Form (Right Side):
┌─────────────────────────┐
│ Seller Info (top)       │  ← Phone, email shown
├─────────────────────────┤
│ Send via: [SMS ▼]       │  ← Auto-selects original channel
├─────────────────────────┤
│ Status: ○ Pending       │
│         ○ In Review     │
│         ○ Resolved      │
├─────────────────────────┤
│ Message: [Big text box] │
│ (max 2000 chars)        │
├─────────────────────────┤
│ Internal Notes:         │  ← Hidden from seller
│ [Smaller text box]      │    (for team only)
├─────────────────────────┤
│ [Send Response] [Clear] │
└─────────────────────────┘
```

**4. Choose Output Channel**
- **Web** (Platform): Shows as in-app notification
- **SMS** (Original): Sends text message to seller's phone
- **Email** (Original): Sends email to seller
- **Gmail**: Alternative email option

> **💡 Pro Tip:** Select **Same Channel as Original** to keep consistency
> The form automatically suggests the channel seller used!

**5. Set Status**
```
Pending   → Initial state, issue not yet handled
In Review → Being actively investigated/handled  
Resolved → Issue fixed and closed
```

**6. Type Response**
- Be professional and clear
- Include actionable next steps
- Keep under 2000 characters (for SMS and readability)

**Example:**
```
"Thank you for reporting this issue. We've sent our sanitation team 
to investigate. We expect to complete cleaning by tomorrow morning 
at 8 AM. We'll send an update when complete."
```

**7. Optional: Add Internal Note**
- Only visible to your management team
- NOT sent to seller
- Use for: instructions, follow-ups, internal context

**8. Click "Send Response"**
- Message appears in thread immediately
- Seller notified via selected channel (SMS/Email/etc)
- Status updated
- Timestamp recorded

---

### 3. Understanding the Thread View

#### Conversation Layout

```
Thread View (Left Side)

┌─────────────────────────────────────┐
│ Conversation Thread            2 msgs│
│                                     │
│ [S] Seller - Mar 28, 2:15 PM   📱   │
│ "Stall is flooded with water"      │
│ [Photo attachment shown]           │
│                                     │
│ [M] You - Mar 28, 3:45 PM     🌐   │
│ "We're investigating this issue"   │
│ ✓ Read on Mar 28, 4:00 PM         │
│                                     │
└─────────────────────────────────────┘

[S] = Seller message
[M] = Manager message (you)
🌐 = Sent via Web platform
📱 = Sent via SMS
📧 = Sent via Email
📬 = Sent via Gmail

✓ Read = When seller viewed your message
```

#### Color Coding
```
Seller messages:   Light gray bubble
Your messages:     Green bubble (your color)

Timestamps:        Always shown (timezone: Server's timezone)
Read receipts:     Green checkmark + timestamp
Unread messages:   No checkmark (waiting for seller to open)
```

---

### 4. SLA Tracking

#### What's SLA?
**SLA = Service Level Agreement**
- 72-hour window to respond to complaints
- Starts when complaint is submitted
- Timer shown in red box at top of page

#### Status Colors
```
🔴 BREACHED    = Past SLA deadline (RED - URGENT!)
⏱ DUE SOON    = Less than 1 hour left (ORANGE)
⏱ X days left = Comfortable (BLUE)
✓ Resolved    = Issue closed, SLA met (GREEN)
```

#### What to Do If SLA is Breached
1. Respond immediately with detailed update
2. Explain causes of delay
3. Provide concrete timeline for resolution
4. Follow up more frequently

---

### 5. Channel Selection Guide

#### When to Use Each Channel

| Channel | Best For | Speed | When to Use |
|---------|----------|-------|------------|
| **Web** | General updates | Medium | Low urgency, preference unknown |
| **SMS** | Urgent updates | Fast | SLA warnings, urgent fixes |
| **Email** | Detailed info | Medium | Complex explanations, attachments |
| **Original** | Consistency | Auto | Default - keeps seller's preferences |

#### Pro Tips
- **Multi-channel is OK**: Send SMS for urgent, email for details
- **Match seller's style**: Use same channel they used
- **Emergency**: Use SMS (instant), then email (backup)
- **Complex issues**: Use Email (more space)

---

### 6. Status Transitions

#### Valid Status Changes
```
Pending → In Review → Resolved
  ↓         ↓
  └─────→ Resolved (skip In Review if simple fix)

Also possible:
In Review → In Review (No change, continue investigating)
Resolved → Pending (Reopen if needed - rare)
```

#### When to Use Each Status

**Pending** (Red)
- Issue just received
- Haven't started investigating yet
- Use for: First acknowledgment

**In Review** (Orange)
- Investigation in progress
- Being worked on
- Waiting for info/parts/team
- Use for: Most manager responses

**Resolved** (Green)
- Issue fixed
- Seller confirmed
- No further action needed
- Use for: Final response only

---

### 7. Real-World Examples

#### Example 1: Simple Issue
```
Seller: "Stall number is wrong on my contract"
You:    "We'll fix this - sending corrected contract today by email"
Status: Resolved (simple, done immediately)
```

#### Example 2: Complex Issue
```
Seller: "Drainage blocked, stall flooded"
You:    (First response)
        "We've logged this. Sending plumber tomorrow 8 AM"
Status: In Review
        
        (Follow-up response next day)
        "Plumbing fixed. We've also installed grates to prevent future issues."
Status: Resolved
```

#### Example 3: Need More Info
```
Seller: "Can't access portal"
You:    "We need your login email to help. Can you reply with the email address?"
Status: Pending (waiting for their info)
```

---

### 8. Performance Dashboard

#### Where to Find It
**Menu → Admin → Response Analytics**

#### What You'll See
```
📊 Dashboard Shows:
- Total complaints handled
- Average response time (how fast you respond)
- SLA compliance rate (% on-time)
- Channel breakdown (which channels most used)
- Manager rankings (team comparison - friendly!)
- Trends (improving or not?)
```

#### How to Use for Improvement
1. Check your **Average Response Time** - aim for under 2 hours
2. Monitor **SLA Compliance** - target 90%+
3. Compare with team - learn from top performers
4. Track trends - see if you're improving month-to-month

---

## 🚨 Common Issues & Solutions

### Issue: Seller Didn't Get My Response

**Check:**
1. Did you click "Send Response"? (must see green checkmark)
2. Is seller's phone/email correct? (shown in form)
3. Which channel did you select?

**Solution:**
- Try alternate channel (SMS if email failed)
- Resend manually
- Check error message in form

### Issue: Thread Won't Load

**Check:**
1. Valid complaint ID in URL?
2. You have access to that market?
3. No permission errors?

**Solution:**
- Go back to Complaints list
- Click complaint again
- Try in different browser
- Clear browser cache

### Issue: Can't Change Status

**Check:**
1. Is complaint already resolved? (can't change)
2. Selected a valid status?
3. Written a response message? (required)

**Solution:**
- Type response message first
- Ensure status is selected
- Click Send Response

### Issue: Wrong Channel Selected

**Issue:** Accidentally sent SMS instead of Email

**Fix (If Noticed Immediately):**
1. Send correct response via right channel immediately
2. Add internal note explaining mistake

**Prevent Next Time:**
- Always double-check channel dropdown
- Use **Suggested (Original)** option

---

## 📱 Mobile Tips

### Viewing Threats on Phone
- ✓ Thread view responsive - full functionality
- The form scrolls - can see all fields
- Touch-friendly buttons

### Responding on Phone
- Works great for short responses
- Harder for long messages (use desktop)
- SMS channel shows character count
- Always preview before sending

---

## ⌨️ Keyboard Shortcuts (Desktop)

| Shortcut | Action |
|----------|--------|
| `Shift+Enter` | Send Response (if form focused) |
| `Ctrl++` | Zoom in (thread too small?) |
| `Tab` | Move through form fields |

---

## 📞 Support

### Getting Help

**If complaint doesn't load:**
- Check browser console (F12)
- Try another browser
- Refresh page

**If response won't send:**
- Check all fields are filled
- Verify seller's contact info
- Try simpler message

**If unsure of status:**
- Default to "In Review"
- Better to over-communicate
- Can always update

**Questions about the system?**
- Ask your manager
- Check IMPLEMENTATION_GUIDE.md
- Review COMPLAINT_RESPONSE_PLATFORM.md design docs

---

## 💡 Best Practices

### Communication Tips
1. **Always be professional** - Sellers see all messages
2. **Respond within 2 hours** - Better SLA rate
3. **Give specific timelines** - "by 3 PM today" vs "soon"
4. **Acknowledge the issue** - Show you understand their concern
5. **Provide updates** - Even if issue not solved

### Follow-Up Tips
1. Set a reminder if issue needs checking
2. Follow up the next day for complex issues
3. Confirm seller satisfaction before closing
4. Learn from patterns - similar issues recurring?

### Workload Management
1. Start with oldest complaints (oldest first priority)
2. Batch similar issues together for efficiency
3. Use response templates for common issues
4. Check analytics dashboard weekly for trends

---

## 🎓 Training Checklist

Before going live, ensure all managers can:

- [ ] Access complaint list and open detail view
- [ ] Read and understand conversation thread
- [ ] Write and send response via each channel
- [ ] Change complaint status correctly
- [ ] Track read receipts
- [ ] Check SLA deadline
- [ ] Use internal notes
- [ ] View response analytics
- [ ] Handle email delivery failures
- [ ] Resend failed SMS messages

---

## 📝 Quick Reference Card

**Print this out and keep at your desk!**

```
COMPLAINT MANAGEMENT FLOW:

1. Open complaint from list
   ↓
2. Read thread (what happened so far?)
   ↓
3. Check SLA (time remaining?)
   ↓
4. Draft response message
   ↓
5. Select channel (web/sms/email/original?)
   ↓
6. Set new status (pending/in_review/resolved?)
   ↓
7. Add internal notes (private notes for team?)
   ↓
8. Send Response
   ↓
9. Verify seller received (check notifications)
   ↓
10. Monitor for follow-up

SLA Guidelines:
- Pending → In Review: within 24 hours
- In Review → Investigation: keep updating
- Final Response: within 72 hours
- Aim: All resolved within 72 hours ✓
```

---

**Version:** 1.0 | **Date:** March 31, 2026 | **Language:** English/Français

For French version, contact your administration team.
