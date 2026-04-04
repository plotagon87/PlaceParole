# WhatsApp Bot Setup Guide

## Overview
The WhatsApp bot allows users to submit complaints directly via WhatsApp. Users receive an automatic confirmation message when their complaint is received.

**Ngrok URL:** `https://extremer-criticisingly-andree.ngrok-free.app`

---

## Step 1: Set Up Twilio Account

1. Go to [console.twilio.com](https://console.twilio.com)
2. Sign up or log in
3. Navigate to **Messaging → Try it Out → Send an SMS**
4. Go to **WhatsApp** instead
5. Click **Get Started with WhatsApp**
6. Follow the sandbox setup instructions

---

## Step 2: Get Your Credentials

In the Twilio Console:
- **Account SID:** Dashboard → Account Info
- **Auth Token:** Dashboard → Account Info  
- **WhatsApp Sandbox Number:** Messaging → WhatsApp → Sandbox Settings

Example:
```
Account SID: AC1234567890abcdefg
Auth Token: your_token_here
Sandbox Number: whatsapp:+1415xxxxxxx
```

---

## Step 3: Set Environment Variables

Create a `.env` file in the project root:

```bash
# Twilio Configuration (WhatsApp)
TWILIO_ACCOUNT_SID=AC1234567890abcdefg
TWILIO_AUTH_TOKEN=your_token_here
TWILIO_WHATSAPP_FROM=whatsapp:+1415xxxxxxx
```

Alternatively, set these in `config/env_loader.php` for testing.

---

## Step 4: Configure Twilio Webhook

In Twilio Console → Messaging → WhatsApp → Sandbox Settings:

Set the **When a message comes in** webhook URL to:
```
https://extremer-criticisingly-andree.ngrok-free.app/integrations/whatsapp_webhook.php
```

Make sure to select **HTTP POST** as the method.

---

## Step 5: Test the Bot

1. Send a WhatsApp message to your Twilio sandbox number
2. The bot will:
   - Save the complaint to the database
   - Send a confirmation message back
   - Display complaint ID, timestamp, and status

### Example Flow:
**User:** "I have a complaint about my internet service"
**Bot Response:**
```
✅ Complaint Received!

Hello WhatsApp User,

Your complaint has been successfully received and registered.

📋 Complaint ID: #345
🕐 Submitted: 2026-04-04 10:30:15
📞 Status: Under Review

You will receive updates about your complaint via WhatsApp.
Thank you for helping us improve!
```

---

## Step 6: Keep Ngrok Running

The bot requires ngrok to be running (it maps port 80 to a public URL):
```bash
ngrok http 80
```

Keep this terminal open while testing.

---

## Features Implemented

✅ **Receive WhatsApp Messages** - Users can send complaints directly  
✅ **Auto Confirmation** - Instant acknowledgment with complaint ID  
✅ **Database Integration** - Complaints saved with metadata  
✅ **User Matching** - Links complaints to registered users if phone number matches  
✅ **Error Handling** - Graceful error messages if something fails  

---

## Troubleshooting

### "Twilio credentials not found"
- Check your `.env` file exists
- Verify `env_loader.php` is loading the environment variables
- Test with: `php -r "require 'config/env_loader.php'; echo getenv('TWILIO_ACCOUNT_SID');"`

### Webhook not receiving messages
- Verify the ngrok URL is correct in Twilio settings
- Make sure ngrok is running (`ngrok http 80`)
- Check the URL doesn't have extra slashes: `/integrations/whatsapp_webhook.php` ✓

### Messages not being sent back
- Verify `TWILIO_WHATSAPP_FROM` is your actual sandbox number
- Check Twilio account has credits/is active
- Look at error logs: `tail -f logs/error.log`

---

## File Locations

- **Webhook Handler:** `integrations/whatsapp_webhook.php`
- **Database Config:** `config/db.php` (stores complaints)
- **Environment Config:** `.env` (credentials)

---

## Next Steps

1. Test complaint submission via WhatsApp
2. Manager can view received complaints in the dashboard
3. Responses sent via WhatsApp will be handled by future SMS sending integration
