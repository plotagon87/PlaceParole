# Environment Variables Setup Guide

## Overview
This project uses a `.env` file to store sensitive credentials and configuration settings. This prevents hardcoded secrets from being committed to version control.

## Files in This Setup

### `.env`
- **Your local configuration file** with actual credentials
- **NEVER commit to git** (will be ignored by .gitignore)
- Should only exist on your local machine
- Each developer has their own `.env` with their credentials

### `.env.example`
- **Template file** that shows what variables are needed
- **Safe to commit to git** (template only, no real values)
- Use this as a reference to set up your `.env` file
- Document required variables without exposing secrets

### `config/env_loader.php`
- Helper file that reads `.env` and loads variables
- Automatically called by `config/db.php`
- Provides `env()` helper function and uses `getenv()`

## Setup Instructions for Developers

### 1. First Time Setup
```bash
# Copy the template file
cp .env.example .env

# Edit .env and fill in your actual values
# Update these with your credentials:
#   - DB_HOST, DB_NAME, DB_USER, DB_PASS
#   - GMAIL_USERNAME, GMAIL_PASSWORD
#   - TWILIO_ACCOUNT_SID, TWILIO_AUTH_TOKEN, TWILIO_WHATSAPP_FROM
```

### 2. Environment Variables Reference

#### Database
```env
DB_HOST=localhost              # MySQL server host
DB_NAME=placeparole            # Database name
DB_USER=root                   # MySQL username
DB_PASS=                       # MySQL password
```

#### Gmail/Email
```env
GMAIL_USERNAME=your-email@gmail.com      # Your Gmail address
GMAIL_PASSWORD=your-app-password         # Gmail App Password (not your main password)
GMAIL_FROM_NAME=PlaceParole Market       # Sender name (optional)
```

To get Gmail App Password:
1. Go to https://myaccount.google.com/security
2. Enable 2-Step Verification
3. Go to "App passwords"
4. Generate a new password for "Mail" and "Mac"
5. Copy the 16-character password into `GMAIL_PASSWORD`

#### Twilio (WhatsApp)
```env
TWILIO_ACCOUNT_SID=your-account-sid      # From console.twilio.com → Account Info
TWILIO_AUTH_TOKEN=your-auth-token        # From console.twilio.com → Account Info
TWILIO_WHATSAPP_FROM=whatsapp:+1234567890  # Your Twilio WhatsApp sandbox number
```

#### Application
```env
APP_ENV=development            # development or production
APP_DEBUG=true                 # Enable debug mode
BASE_URL=/PlaceParole          # Application base URL
```

## How It Works

### Loading Environment Variables
The `config/db.php` file automatically loads variables from `.env`:

```php
require_once __DIR__ . '/env_loader.php';

// Now you can use getenv() to access variables
$db_host = getenv('DB_HOST');
$db_user = getenv('DB_USER');
```

### Using in Code
Three ways to access environment variables:

```php
// Method 1: Using getenv()
$value = getenv('VARIABLE_NAME');

// Method 2: Using env() helper function (like Laravel)
$value = env('VARIABLE_NAME', 'default_value');

// Method 3: Using $_ENV array
$value = $_ENV['VARIABLE_NAME'] ?? 'default_value';
```

## Security Best Practices

✅ **DO:**
- ✅ Keep .env file locally only (not in git)
- ✅ Use different passwords for development and production
- ✅ Use Gmail App Passwords instead of main password
- ✅ Rotate API keys/tokens periodically
- ✅ Use .env.example as documentation

❌ **DON'T:**
- ❌ Commit .env to git (it's in .gitignore)
- ❌ Share .env files via email or messaging
- ❌ Use the same credentials across environments
- ❌ Hardcode secrets in source code
- ❌ Log or print environment variables

## Files Using Environment Variables

1. **config/db.php**
   - Loads environment variables
   - Uses: DB_HOST, DB_NAME, DB_USER, DB_PASS, BASE_URL

2. **integrations/email_notify.php**
   - Loads environment variables
   - Uses: GMAIL_USERNAME, GMAIL_PASSWORD, GMAIL_FROM_NAME

3. **integrations/whatsapp_send.php**
   - Loads environment variables
   - Uses: TWILIO_ACCOUNT_SID, TWILIO_AUTH_TOKEN, TWILIO_WHATSAPP_FROM

## Troubleshooting

### ".env file not found" Error
→ Run: `cp .env.example .env` from project root

### "SMTP authentication failed"
→ Check your GMAIL_PASSWORD is the App Password, not your main Gmail password

### "Twilio credentials not found"
→ Verify TWILIO_ACCOUNT_SID and TWILIO_AUTH_TOKEN in .env are correct

### Database connection fails
→ Verify DB_HOST, DB_NAME, DB_USER, and DB_PASS match your MySQL setup

## Git Commands Reference

```bash
# Check if .env is being ignored
git status  # .env should NOT appear

# View what's in .gitignore for .env
cat .gitignore | grep env

# If you accidentally committed .env (emergency):
git rm --cached .env
git commit -m "Remove .env from tracking"
```

## For Production

Before deploying to production:

1. Create a `.env` file on your server with production credentials
2. Never commit production .env to version control
3. Set proper file permissions: `chmod 600 .env`
4. Consider using environment-specific variables: `APP_ENV=production`
5. Use a secrets management tool (e.g., HashiCorp Vault, AWS Secrets Manager)

## Questions?

If you need to add new environment variables:
1. Add them to `.env.example` (with placeholder values)
2. Add them to `.env` (with real values)
3. Document them in this guide
4. Update the code to use `getenv()` instead of hardcoded values
