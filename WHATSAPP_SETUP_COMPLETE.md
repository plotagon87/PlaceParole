# WhatsApp Bot Setup - Complete Instructions

## Current Status
✅ **ngrok is running** at: https://extremer-criticisingly-andree.ngrok-free.dev
✅ **Webhooks are receiving messages** (confirmed from database)
✅ **Complaints are being saved** (IDs 28 & 29)
⚠️ **Confirmation messages not working** - due to old webhook URL

---

## Step 1: Update Twilio Webhook URL

The old ngrok URL (`.app`) is offline. You need to update to the new URL (`.dev`):

### In Twilio Console:
1. Go to https://console.twilio.com
2. Click **Messaging** in left sidebar
3. Click **WhatsApp** 
4. Click **Sandbox Settings**
5. Find the field: "When a message comes in"
6. **Replace the URL** with:
   ```
   https://extremer-criticisingly-andree.ngrok-free.dev/integrations/whatsapp_webhook.php
   ```
7. Make sure method is set to **HTTP POST**
8. Click **Save**

---

## Step 2: Test the WhatsApp Bot

1. **Send a test message** to your Twilio sandbox WhatsApp number
2. **Check the logs** to see what happened:
   ```bash
   type logs/whatsapp_webhook.log
   ```
3. **Expected log output:**
   ```
   2026-04-04 10:30:45 - Webhook called via POST
     From: +237123456789 | Message: Hello world...
     ✓ Complaint saved: ID=30, Ref=MKT-2026-98765432
     ✓ Confirmation sent to: +237123456789
   ```

---

## Step 3: Verify in Database

Check if the complaint was saved:
```sql
SELECT id, ref_code, description, channel, created_at 
FROM complaints 
WHERE channel='sms' 
ORDER BY created_at DESC 
LIMIT 1;
```

---

## Troubleshooting

### If you still don't get a confirmation:
- Check `logs/whatsapp_webhook.log` for error messages
- Verify your `.env` file has correct Twilio credentials
- Make sure **ngrok is still running** - check the terminal

### If confirmation says "Failed to send":
- Check your Twilio account has credits
- Verify the phone number in `.env` is correct format: `whatsapp:+14155238886`

---

## Keep Ngrok Running

**Important:** The ngrok terminal must stay open. If closed, you'll get the "offline" error again.

Current ngrok terminal ID: `7848a618-2072-4f57-9928-c07084aabca9`

To check if it's still running:
```bash
Get-Process ngrok
```

---

## Summary of What's Working

| Feature | Status |
|---------|--------|
| Twilio receives WhatsApp messages | ✅ YES |
| Database saves complaints | ✅ YES |
| Ngrok tunnel active | ✅ YES |
| Auto confirmations sent | ⚠️ After URL update |
| Manager can see complaints | ✅ YES |

---

## Next After Testing

Once WhatsApp confirmation messages work:
1. Test submitting a complaint via WhatsApp
2. Login as manager
3. View the complaint in the dashboard
4. Send a response

The manager response will trigger a notification back to the user via SMS integration (next feature to implement).
