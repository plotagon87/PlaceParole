# 🛒 Market Feedback & Communication Platform
### *A Structured, Multi-Channel Feedback & Announcement System for Small Market Squares in Cameroon*

---

## 📌 Project Overview

Small market squares in Cameroon are the backbone of local trade and community livelihoods. Yet, sellers currently have **no structured system** to voice complaints, propose improvements, or receive official announcements — all communication happens informally, mouth-to-mouth.

This platform solves that problem by providing:
- A **web-based portal** where sellers submit complaints, suggestions, and community reports
- **SMS and Gmail (Google Mail) integration** so sellers without smartphones can still participate
- A **manager dashboard** to filter, track, respond to issues, and broadcast announcements to all sellers

> **Tech Stack (Technology Stack — the set of programming tools and languages used to build the system):**
> PHP · MySQL · Tailwind CSS · Free SMS API (e.g., Textbelt / Vonage Free Tier) · Gmail API (Google Mail Application Programming Interface)

---

## 🗂️ Table of Contents

1. [Features & Functionalities](#features--functionalities)
2. [Tech Stack](#tech-stack)
3. [Project Structure](#project-structure)
4. [Database Schema](#database-schema)
5. [Modules Breakdown](#modules-breakdown)
6. [API Integrations](#api-integrations)
7. [User Roles](#user-roles)
8. [UI/UX Guidelines](#uiux-guidelines)
9. [Installation & Setup](#installation--setup)
10. [Testing Plan](#testing-plan)
11. [Roadmap & Future Scope](#roadmap--future-scope)

---

## ✅ Features & Functionalities

### 🔴 Core Features (Must Have — these are mandatory for the system to work)

| Feature | Description |
|---|---|
| Complaint Submission | Sellers submit complaints via web form, SMS, or Gmail |
| Complaint Tracking | Each complaint gets a unique ID (Identifier) and a status: Pending → In Review → Resolved |
| Manager Dashboard | Managers view, filter, prioritize, and respond to all submitted complaints |
| Announcement Broadcast | Managers send official announcements to all registered sellers simultaneously |
| SMS Notifications | Sellers receive automated SMS alerts when their complaint status changes |
| Gmail Submission | Sellers email a dedicated inbox; the system auto-parses (reads and processes) the email into a complaint |
| Innovation Suggestions | Sellers submit ideas for market improvements through a dedicated form |
| Community Support Module | Sellers report life events (death, illness, emergency); the system notifies the market community |

### 🟡 Secondary Features (Nice to Have — these improve the experience but are not blocking)

| Feature | Description |
|---|---|
| Complaint Analytics Dashboard | Visual charts showing complaint categories, resolution rates, peak periods |
| Multi-Market Support | Each manager registers and manages their own independent market; sellers choose their market on signup — **this is a core architectural feature, not optional** |
| Seller Profile Management | Sellers register, log in, and track their own complaint history |
| Notification Preferences | Sellers choose to receive updates via SMS, email, or both |
| Search & Filter Complaints | Managers search by date, category, seller name, or status |

---

## 🛠️ Tech Stack

> **Tech Stack** means the collection of technologies (programming languages, frameworks, databases, and tools) used to build the application from front to back.

### Backend (Server Side — the part of the application the user does not see, running on the server)
- **PHP** — Primary programming language for server-side logic
  - Option A: **Plain PHP** (vanilla PHP, no framework) — simpler, recommended for beginners
  - Option B: **Laravel** — a PHP framework (a pre-built structure that speeds up development)
- **MySQL** — Relational database (a database that stores data in structured tables linked together)

### Frontend (Client Side — the part the user sees and interacts with in the browser)
- **HTML5 / CSS3** — Structure and styling of web pages
- **Tailwind CSS** — A utility-first CSS (Cascading Style Sheets) framework where you style elements by combining small, single-purpose classes directly in your HTML (HyperText Markup Language). For example: `class="bg-green-600 text-white px-4 py-2 rounded-lg"` instead of writing a separate CSS file
  - Load via CDN (Content Delivery Network — a server that hosts the file so you don't have to): `<script src="https://cdn.tailwindcss.com"></script>`
  - **Color palette configured for this project:** Green (primary), Orange (accent), White (background) — matching Cameroon's national colors
- **JavaScript (JS)** — For interactive UI elements (e.g., live form validation, dynamic complaint status updates)
- **AJAX (Asynchronous JavaScript And XML)** — Allows parts of a page to update without a full page reload
- **Alpine.js** *(optional but recommended)* — A tiny JavaScript framework (3KB — kilobytes) that pairs perfectly with Tailwind for dropdowns, modals (pop-up dialogs), and toggles without writing complex JS

### Integrations (External Services Connected to the Platform)
- **Free SMS API** — See the [API Integrations](#api-integrations) section for the full list of free options and code examples
- **Gmail API (Google Mail Application Programming Interface)** — Allows the system to read emails sent to a dedicated inbox and convert them into complaints automatically
- **PHPMailer** — A PHP library (a reusable code package) for sending emails from the system

### Hosting & Deployment (Deployment — the process of making the application available to users)
- **XAMPP or LAMP Stack** — Local server environment for development and testing
  - **XAMPP**: Cross-platform (Windows/Linux/Mac) Apache, MySQL, PHP, Perl bundle
  - **LAMP**: Linux, Apache, MySQL, PHP — the standard production server stack

---

## 📁 Project Structure

```
market-feedback-platform/
│
├── index.php                  # Main entry point of the application
├── lang/
│   ├── en.php                 # English translations for all interface text
│   └── fr.php                 # French translations for all interface text
│
├── config/
│   ├── db.php                 # Database connection configuration
│   ├── lang.php               # Language loader — detects browser language or reads session
│   ├── sms.php                # SMS gateway configuration (API keys, phone numbers)
│   └── gmail.php              # Gmail API credentials and settings
│
├── modules/
│   ├── auth/                  # Authentication (Login, Registration, Session Management)
│   │   ├── login.php
│   │   ├── register_manager.php  # Manager registers a NEW market + their account
│   │   ├── register_seller.php   # Seller selects an existing market, then registers
│   │   └── logout.php
│   │
│   ├── complaints/            # Complaint Submission and Tracking Module
│   │   ├── submit.php         # Form for sellers to submit new complaints
│   │   ├── track.php          # Page showing complaint status by ID
│   │   ├── list.php           # Manager view: list of all complaints
│   │   └── respond.php        # Manager responds to / resolves a complaint
│   │
│   ├── suggestions/           # Innovation Suggestion Module
│   │   ├── submit.php         # Sellers propose market improvements
│   │   └── list.php           # Manager reviews suggestions
│   │
│   ├── community/             # Community Support Module (deaths, illness, emergencies)
│   │   ├── report.php         # Sellers report a community event
│   │   └── list.php           # Manager and community members view reports
│   │
│   ├── announcements/         # Manager Broadcast Announcement Module
│   │   ├── create.php         # Manager writes and publishes a new announcement
│   │   └── list.php           # All sellers see latest announcements
│   │
│   └── analytics/             # (Optional) Charts and statistics for managers
│       └── dashboard.php
│
├── integrations/
│   ├── sms_receive.php        # Parses (reads and processes) incoming SMS complaints
│   ├── sms_send.php           # Sends SMS notifications to sellers
│   ├── gmail_fetch.php        # Fetches and parses incoming Gmail complaints
│   └── gmail_notify.php       # Sends email notifications to sellers
│
├── assets/
│   ├── css/
│   │   └── style.css          # Custom stylesheet
│   ├── js/
│   │   └── app.js             # Custom JavaScript logic
│   └── img/                   # Image files (logos, icons)
│
├── templates/
│   ├── header.php             # Shared page header (navigation bar, logo)
│   └── footer.php             # Shared page footer
│
└── README.md                  # This file — project documentation
```

---

## 🗄️ Database Schema

> **Schema** means the blueprint or plan of how the database tables are structured and related to each other.

### Table: `markets`
Stores each registered market square. This is the **top-level entity** — everything else (managers, sellers, complaints, announcements) belongs to a specific market.

```sql
CREATE TABLE markets (
    id          INT AUTO_INCREMENT PRIMARY KEY,
    name        VARCHAR(150) NOT NULL,           -- Official name of the market e.g. "Marché Mokolo"
    location    VARCHAR(200),                    -- City or neighbourhood e.g. "Yaoundé, Centre Region"
    created_at  TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);
```

---

### Table: `users`
Stores all registered users (both sellers and managers). Every user belongs to exactly one market.

```sql
-- SQL (Structured Query Language) is used to create and manage database tables
CREATE TABLE users (
    id          INT AUTO_INCREMENT PRIMARY KEY, -- Unique identifier for each user
    market_id   INT NOT NULL,                   -- Foreign Key: which market this user belongs to
    name        VARCHAR(100) NOT NULL,           -- Full name of the user
    phone       VARCHAR(20) UNIQUE,              -- Phone number (used for SMS)
    email       VARCHAR(150) UNIQUE,             -- Email address
    role        ENUM('seller', 'manager'),       -- ENUM = a fixed list of allowed values
    stall_no    VARCHAR(20),                     -- Market stall number (for sellers only)
    password    VARCHAR(255),                    -- Hashed (encrypted) password
    created_at  TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (market_id) REFERENCES markets(id) -- Links user to their specific market
);
```

### Table: `complaints`
Stores all complaints submitted through any channel (web, SMS, Gmail). Each complaint is scoped to a specific market.

```sql
CREATE TABLE complaints (
    id          INT AUTO_INCREMENT PRIMARY KEY,
    market_id   INT NOT NULL,                            -- Which market this complaint belongs to
    ref_code    VARCHAR(20) UNIQUE,                      -- Reference code given to seller to track their complaint
    seller_id   INT,                                     -- Foreign Key linking to users table
    category    VARCHAR(100),                            -- e.g., 'Infrastructure', 'Sanitation', 'Stall Allocation'
    description TEXT,                                    -- Full text of the complaint
    channel     ENUM('web', 'sms', 'gmail'),             -- How the complaint was submitted
    status      ENUM('pending', 'in_review', 'resolved') DEFAULT 'pending',
    response    TEXT,                                    -- Manager's official response
    created_at  TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at  TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (seller_id)  REFERENCES users(id),
    FOREIGN KEY (market_id)  REFERENCES markets(id)     -- Ensures manager only sees complaints for their market
);
```

### Table: `suggestions`
Stores innovation/improvement ideas submitted by sellers.

```sql
CREATE TABLE suggestions (
    id          INT AUTO_INCREMENT PRIMARY KEY,
    seller_id   INT,
    title       VARCHAR(200),
    description TEXT,
    status      ENUM('pending', 'approved', 'rejected') DEFAULT 'pending',
    created_at  TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (seller_id) REFERENCES users(id)
);
```

### Table: `community_reports`
Stores community events like deaths, illnesses, and emergencies.

```sql
CREATE TABLE community_reports (
    id           INT AUTO_INCREMENT PRIMARY KEY,
    reported_by  INT,                                        -- Seller who reported the event
    event_type   ENUM('death', 'illness', 'emergency', 'other'),
    person_name  VARCHAR(100),                               -- Name of the affected person
    description  TEXT,
    status       ENUM('open', 'coordinated') DEFAULT 'open', -- 'coordinated' = manager has taken action
    created_at   TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (reported_by) REFERENCES users(id)
);
```

### Table: `announcements`
Stores manager-created announcements broadcast to all sellers.

```sql
CREATE TABLE announcements (
    id          INT AUTO_INCREMENT PRIMARY KEY,
    manager_id  INT,
    title       VARCHAR(200),
    body        TEXT,                           -- Full text of the announcement
    sent_via    SET('web', 'sms', 'email'),     -- Which channels the announcement was sent through
    created_at  TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (manager_id) REFERENCES users(id)
);
```

---

## 🧩 Modules Breakdown

### 1. 🔐 Authentication Module
> **Authentication** = the process of verifying that a user is who they claim to be (login/logout system)

This module handles two distinct registration and login flows — one for **managers** and one for **sellers** — both of which are tied to a specific market.

---

**Manager Registration & Login:**
- A manager visits the platform and registers a **new market** by providing:
  - Market name (e.g., "Marché Mokolo")
  - Market location (e.g., "Yaoundé, Centre Region")
  - Their own personal details: name, phone, email, password
- The system creates a record in the `markets` table first, then creates the manager's account in `users` linked to that market via `market_id`
- On login, the manager enters their email and password — the system recognises their role and loads their market-specific dashboard
- A manager **can only see data belonging to their own market** — they cannot see complaints or sellers from other markets

---

**Seller Registration:**
- A seller visits the registration page and is presented with a **dropdown list of all registered markets** (populated from the `markets` table)
- The seller selects their market (e.g., "Marché Central — Bafoussam") before filling in their personal details
- Registration fields: name, phone, email, stall number, password
- The seller's account is saved to `users` with the `market_id` of the chosen market

---

**Seller Login & Complaint Submission:**
- On login, the seller selects their market from the dropdown (or it is remembered from registration)
- All complaints, announcements, and community reports the seller sees after login are **filtered to show only their market's data**
- When a seller submits a complaint, the `market_id` is automatically attached from their session — they do not need to select a market again

---

**Technical implementation notes:**
- Passwords must be **hashed** using `password_hash()` in PHP — never store plain text passwords
- Session management using PHP `$_SESSION`:
  ```php
  // After successful login, store the user's details in the session
  $_SESSION['user_id']   = $user['id'];
  $_SESSION['role']      = $user['role'];      // 'seller' or 'manager'
  $_SESSION['market_id'] = $user['market_id']; // Used to scope ALL database queries
  $_SESSION['name']      = $user['name'];
  ```
- Every database query that fetches complaints, announcements, or community reports **must include** `WHERE market_id = $_SESSION['market_id']` to prevent data leaking between markets

---

### 2. 📝 Complaint Submission Module
> This is the heart of the platform — the main way sellers communicate problems to management

**Web Submission:**
- Seller fills a form: category (dropdown), description (text area), optional photo upload
- On submission, a unique `ref_code` (Reference Code — e.g., `MKT-2024-00123`) is generated and shown to the seller
- An SMS and/or email confirmation is sent to the seller automatically

**SMS Submission:**
- Seller sends an SMS to a dedicated market number in the format:
  ```
  COMPLAINT [CATEGORY] [MESSAGE]
  Example: COMPLAINT SANITATION There is a blocked drainage near stall B12
  ```
- The SMS gateway (Kannel/Gammu) forwards the message to `sms_receive.php`
- The script parses the message, looks up the sender's phone number in the `users` table, and creates a complaint record
- A confirmation SMS is sent back with the `ref_code`

**Gmail Submission:**
- Seller emails `marketcomplaints@gmail.com` with the subject: `COMPLAINT: [Category]` and the body containing the description
- A scheduled PHP script (`cron job` — a task that runs automatically at set time intervals) uses the **Gmail API** to fetch unread emails from this inbox
- Each email is parsed and converted into a complaint record
- A reply email is sent to the seller with their `ref_code`

---

### 3. 📊 Manager Dashboard & Filtering Module
> **Dashboard** = a central screen showing all key information at a glance

- Manager sees a table of all complaints with columns: ID, Seller Name, Category, Status, Date, Channel
- **Filters available:**
  - By status: Pending / In Review / Resolved
  - By category: Infrastructure / Sanitation / Stall Allocation / Other
  - By date range
  - By submission channel: Web / SMS / Gmail
- Manager can click any complaint to view full details, write a response, and update the status
- Status change triggers an automatic SMS and/or email notification to the seller

---

### 4. 📣 Announcement Broadcast Module
> Replaces word-of-mouth communication with official, documented broadcasts

- Manager writes an announcement with a title and body text
- Selects delivery channels: Web only / SMS / Email / All
- On submission:
  - Announcement is saved to the `announcements` table and shown on the seller dashboard
  - If SMS is selected: a loop sends an SMS to every registered seller's phone number
  - If email is selected: a loop sends an email to every registered seller's email
- Sellers see a "Latest Announcements" panel on their home page after login

---

### 5. 💡 Innovation Suggestion Module
- Sellers submit improvement ideas: title + description
- Manager views and marks each suggestion as: Pending / Approved / Rejected
- Approved suggestions can be pinned as visible "Community Wins" on the seller dashboard

---

### 6. 🤝 Community Support Module
> This module reflects the real social solidarity of Cameroonian market culture

- Sellers report a community event: death / illness / emergency / other
- The report includes: affected person's name, event type, and a description
- The manager receives an alert and can coordinate a community response (e.g., collecting contributions)
- All market members can see open community reports on their dashboard to act accordingly
- Manager marks report as "Coordinated" once action has been taken

---

## 🔌 API Integrations

> **API (Application Programming Interface)** = a way for two different software systems to communicate with each other

### SMS API — Free Options

> **API (Application Programming Interface)** = a set of rules that allows your PHP code to talk to an external SMS sending service over the internet

Since you are looking for a free SMS option, here are the best candidates. Note that truly free SMS (with no cost at all, ever) is rare because mobile networks charge for every message. However, these services offer **free tiers** (a limited number of free messages per day/month) that are sufficient for development and small-scale pilots:

| Service | Free Tier | Works in Cameroon | Notes |
|---|---|---|---|
| **Textbelt** | 1 free SMS/day per IP address | ✅ Yes | Simplest to use — one HTTP request, no signup required |
| **Vonage (formerly Nexmo)** | Free trial credit (~€2) on signup | ✅ Yes | Professional, reliable, excellent PHP SDK |
| **Twilio** | Free trial credit ($15) on signup | ✅ Yes | Industry standard, great documentation |
| **Fast2SMS** | Free tier available | ⚠️ India-focused | Not ideal for Cameroon |

**Recommended approach:** Use **Textbelt** during development (no signup needed), then switch to **Vonage or Twilio** free trial credit for your pilot deployment.

---

**Sending an SMS using Textbelt (the simplest free option — no signup required):**
```php
<?php
// This function sends a single SMS using the Textbelt free API
// Textbelt allows 1 free SMS per day per IP address (Internet Protocol address — your server's unique network identifier)
function sendSMS($phone, $message) {

    // The data we are sending to Textbelt's server
    $data = [
        'phone'   => $phone,    // Recipient phone number in international format e.g. +237XXXXXXXXX
        'message' => $message,  // The text content of the SMS
        'key'     => 'textbelt' // 'textbelt' is the special free key — replace with a paid key later
    ];

    // curl = Client URL — a tool for making HTTP requests from PHP to external servers
    $ch = curl_init('https://textbelt.com/text'); // Initialize a connection to Textbelt's API endpoint
    curl_setopt($ch, CURLOPT_POST, true);                          // Tell curl we are sending a POST request
    curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($data)); // Attach our data to the request
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);                // Return the response as a string

    $response = curl_exec($ch); // Execute (run) the request and get the response
    curl_close($ch);            // Close the connection to free up resources

    // json_decode() converts the JSON response text into a PHP object we can read
    $result = json_decode($response);

    // Check if the SMS was sent successfully
    if ($result->success) {
        return true; // SMS sent
    } else {
        error_log("SMS failed: " . $result->error); // Log the error for debugging
        return false;
    }
}

// --- EXAMPLE USAGE ---
sendSMS('+237612345678', 'Your complaint MKT-2024-00123 has been received.');
?>
```

---

**Sending an SMS using Vonage PHP SDK (recommended for production/pilot):**
```php
<?php
// First install the Vonage SDK via Composer: composer require vonage/client
require 'vendor/autoload.php'; // Load all installed libraries

// Initialize (set up) the Vonage client with your API credentials from vonage.com dashboard
$client = new Vonage\Client(
    new Vonage\Client\Credentials\Basic('YOUR_API_KEY', 'YOUR_API_SECRET')
    // YOUR_API_KEY and YOUR_API_SECRET are found in your Vonage account dashboard after signup
);

function sendSMSVonage($client, $toPhone, $message) {
    $response = $client->sms()->send(
        new Vonage\SMS\Message\SMS(
            $toPhone,           // Recipient phone number e.g. '+237612345678'
            'MarketSquare',     // Sender name shown on recipient's phone (max 11 characters)
            $message            // SMS text content
        )
    );

    // Check the first message in the response to confirm delivery
    $msg = $response->current();
    if ($msg->getStatus() == 0) { // Status 0 = success in Vonage's system
        return true;
    } else {
        error_log("Vonage SMS error: " . $msg->getStatus());
        return false;
    }
}
?>
```

---

**Receiving SMS (how sellers send complaints via SMS):**

Since free SMS APIs generally only support **sending** (outbound SMS), not receiving (inbound SMS), the recommended approach for receiving complaints via SMS on a free tier is to use **Vonage's inbound webhook** (a URL your server exposes that Vonage calls whenever someone sends an SMS to your virtual number):

```php
<?php
// Vonage calls this URL (sms_receive.php) automatically when an SMS is sent to your Vonage number
// The data arrives as a JSON (JavaScript Object Notation — a lightweight data format) body

$data   = json_decode(file_get_contents('php://input'), true); // Read the incoming JSON data
$sender = $data['msisdn']; // msisdn = Mobile Station International Subscriber Directory Number — i.e. the sender's phone number
$text   = $data['text'];   // The SMS message content

// Check if the message follows the complaint format
if (stripos($text, 'COMPLAINT') === 0) {
    // Format: COMPLAINT SANITATION Blocked drain near stall B12
    $parts    = explode(' ', $text, 3); // Split into maximum 3 parts: ['COMPLAINT', 'SANITATION', 'Blocked drain...']
    $category = $parts[1] ?? 'General'; // The second word is the category
    $message  = $parts[2] ?? $text;     // Everything after is the complaint description

    // Look up the sender's phone number in the users table
    // Save the complaint to the database
    // Send a confirmation SMS back with the ref_code
}
?>
```

---

### Gmail API (Google Mail Application Programming Interface)

**Setup Steps:**
1. Go to [Google Cloud Console](https://console.cloud.google.com) and create a new project
2. Enable the **Gmail API** for that project
3. Create **OAuth 2.0 credentials** (a secure login token system) and download the `credentials.json` file
4. Install the Google PHP client library: `composer require google/apiclient`
   - **Composer** = PHP's package manager (a tool that automatically downloads and installs code libraries)

**Fetching and Parsing Complaint Emails:**
```php
<?php
require 'vendor/autoload.php'; // Load all installed libraries via Composer

// Initialize (set up) the Google API client
$client = new Google_Client();
$client->setAuthConfig('config/credentials.json'); // Your downloaded credentials file
$client->addScope(Google_Service_Gmail::GMAIL_READONLY); // Only need READ access

$service = new Google_Service_Gmail($client);

// Fetch unread emails in the inbox
$messages = $service->users_messages->listUsersMessages('me', ['q' => 'is:unread subject:COMPLAINT']);

foreach ($messages->getMessages() as $message) {
    $msg     = $service->users_messages->get('me', $message->getId());
    $subject = /* extract subject */;
    $body    = /* extract body */;
    $from    = /* extract sender email */;

    // Parse the complaint and save it to the database
    // Then send a reply with the reference code
}
?>
```

---

## 👤 User Roles

| Role | How They Register | Permissions |
|---|---|---|
| **Manager** | Registers a new market (name + location), then creates their account linked to that market | Login, view **only their market's** complaints, filter/search, respond to complaints, change complaint status, create and broadcast announcements to their market's sellers only, view suggestions, coordinate community reports, view analytics |
| **Seller** | Selects their market from a dropdown during registration | Login, submit complaints (web/SMS/Gmail) to their chosen market, submit suggestions, report community events, view announcements from their market only, track own complaint status |

> ⚠️ **Critical scoping rule:** Every query in the system must be filtered by `market_id`. A manager from "Marché Mokolo" must **never** see complaints from "Marché Central." This is enforced by always including `WHERE market_id = $_SESSION['market_id']` in all SQL queries after login.

---

## 🎨 UI/UX Guidelines

> **UI (User Interface)** = what the user sees and interacts with on screen
> **UX (User Experience)** = how easy and pleasant the overall experience is for the user

- Use **Tailwind CSS** loaded via CDN for all layouts — add `<script src="https://cdn.tailwindcss.com"></script>` to every page's `<head>` tag
- **Configure Tailwind's custom color palette** in a `<script>` block to match the project's theme:
  ```html
  <script>
    // This block tells Tailwind to add our custom colors to its utility classes
    tailwind.config = {
      theme: {
        extend: {
          colors: {
            primary:  '#16a34a', // Green  — Cameroon national color (used for buttons, headers)
            accent:   '#ea580c', // Orange — Cameroon national color (used for alerts, badges)
            neutral:  '#f9fafb', // Off-white background
          }
        }
      }
    }
  </script>
  ```
- **Seller Dashboard:** Simple, large text, generous padding — sellers may have limited digital literacy (familiarity with technology). Example button: `class="bg-primary text-white w-full py-3 rounded-xl text-lg font-semibold"`
- **Manager Dashboard:** Dense data tables with alternating row colors: `class="even:bg-gray-50"`
- **Forms:** Use `class="w-full border border-gray-300 rounded-lg px-3 py-2 focus:outline-none focus:ring-2 focus:ring-primary"` on all inputs for consistent styling
- **Status Badges:** `class="px-2 py-1 rounded-full text-xs font-bold"` with color variants — 🔴 `bg-red-100 text-red-700` Pending | 🟡 `bg-yellow-100 text-yellow-700` In Review | 🟢 `bg-green-100 text-green-700` Resolved
- **Language Selection:** The platform must support **English and French** (Cameroon's two official languages). The language system works as follows:
  - On first visit, the platform **automatically detects the user's browser language** using JavaScript's built-in `navigator.language` property (a browser property that returns the user's preferred language, e.g., `"fr"` for French or `"en"` for English) and loads the matching language automatically
  - A **visible language toggle** (a button or dropdown showing 🇬🇧 EN / 🇫🇷 FR) is permanently displayed in the navigation bar so the user can manually override the detected language at any time
  - The chosen language is saved in `$_SESSION['lang']` (PHP session) so it persists as the user moves between pages
  - All static text (button labels, form placeholders, error messages, page titles) must be stored in **language files** — one per language — rather than hardcoded directly in the HTML:
    ```
    lang/
    ├── en.php   # English translations
    └── fr.php   # French translations
    ```
    ```php
    <?php
    // lang/en.php — English language file
    // Each key is a code name; each value is the text shown to the user
    return [
        'submit_complaint'  => 'Submit a Complaint',
        'your_market'       => 'Your Market',
        'complaint_sent'    => 'Your complaint has been received.',
        'status_pending'    => 'Pending',
        'status_in_review'  => 'In Review',
        'status_resolved'   => 'Resolved',
        // ... add all interface text here
    ];
    ?>
    ```
    ```php
    <?php
    // lang/fr.php — French language file
    return [
        'submit_complaint'  => 'Soumettre une Plainte',
        'your_market'       => 'Votre Marché',
        'complaint_sent'    => 'Votre plainte a bien été reçue.',
        'status_pending'    => 'En attente',
        'status_in_review'  => 'En cours',
        'status_resolved'   => 'Résolu',
        // ... add all interface text here
    ];
    ?>
    ```
    ```php
    <?php
    // config/lang.php — Language loader (include this at the top of every page)
    // Start the session if it has not already been started
    if (session_status() === PHP_SESSION_NONE) session_start();

    // Step 1: Check if the user already has a language saved in their session
    if (!isset($_SESSION['lang'])) {

        // Step 2: If not, read the browser's preferred language from the HTTP header
        // HTTP_ACCEPT_LANGUAGE is sent by the browser e.g. "fr-FR,fr;q=0.9,en;q=0.8"
        $browserLang = substr($_SERVER['HTTP_ACCEPT_LANGUAGE'] ?? 'en', 0, 2);

        // Step 3: Only accept 'fr' or 'en' — default to 'en' for anything else
        $_SESSION['lang'] = in_array($browserLang, ['fr', 'en']) ? $browserLang : 'en';
    }

    // Step 4: If the user clicked the language toggle button, update the session
    if (isset($_GET['lang']) && in_array($_GET['lang'], ['fr', 'en'])) {
        $_SESSION['lang'] = $_GET['lang'];
    }

    // Step 5: Load the correct language file into the $t variable
    // $t stands for "translations" — use it on every page like: echo $t['submit_complaint']
    $t = require "lang/{$_SESSION['lang']}.php";
    ?>
    ```
    ```html
    <!-- Language toggle buttons in the navigation bar (header.php) -->
    <!-- Clicking a button adds ?lang=en or ?lang=fr to the current URL -->
    <a href="?lang=en" class="px-2 py-1 rounded <?= $_SESSION['lang'] === 'en' ? 'bg-primary text-white' : 'text-gray-600' ?>">
        🇬🇧 EN
    </a>
    <a href="?lang=fr" class="px-2 py-1 rounded <?= $_SESSION['lang'] === 'fr' ? 'bg-primary text-white' : 'text-gray-600' ?>">
        🇫🇷 FR
    </a>
    ```
- **Mobile-First Design:** Tailwind is mobile-first by default — unprefixed classes apply to small screens, `md:` prefix applies to medium screens and above (e.g., `class="flex-col md:flex-row"`)

---

## ⚙️ Installation & Setup

> Follow these steps in order to install and run the project on your local computer

### Prerequisites (things you must have installed before starting)
- **PHP 8.0+** — Download from [php.net](https://php.net)
- **MySQL 8.0+** — Database server
- **XAMPP** (recommended for beginners) — Bundles PHP, MySQL, and Apache in one installer: [apachefriends.org](https://apachefriends.org)
- **Composer** — PHP package manager: [getcomposer.org](https://getcomposer.org)
- **Tailwind CSS** — No installation needed during development; loaded via CDN (one `<script>` tag in HTML)
- **Vonage Account** *(optional, for SMS)* — Free signup at [vonage.com](https://vonage.com) — free trial credit included

### Step-by-Step Setup

```bash
# Step 1: Clone (download a copy of) the repository to your computer
git clone https://github.com/YOUR_USERNAME/market-feedback-platform.git

# Step 2: Navigate (move) into the project folder
cd market-feedback-platform

# Step 3: Install PHP dependencies (additional code packages the project needs)
composer install
```

```sql
-- Step 4: Open phpMyAdmin (MySQL's web interface) and create the database
CREATE DATABASE market_feedback_db;

-- Step 5: Import the schema (run all the CREATE TABLE commands)
-- Either paste the SQL from the Database Schema section above,
-- or run: mysql -u root -p market_feedback_db < database/schema.sql
```

```php
// Step 6: Edit config/db.php with your database credentials (login details)
<?php
define('DB_HOST', 'localhost');   // The server where MySQL is running
define('DB_NAME', 'market_feedback_db');
define('DB_USER', 'root');        // Your MySQL username
define('DB_PASS', '');            // Your MySQL password (empty by default in XAMPP)
?>
```

```bash
# Step 7: Place the project folder inside XAMPP's web root folder
# On Windows: C:/xampp/htdocs/market-feedback-platform
# On Linux:   /opt/lampp/htdocs/market-feedback-platform

# Step 8: Start XAMPP (Apache + MySQL), then visit in your browser:
# http://localhost/market-feedback-platform/
```

---

## 🧪 Testing Plan

> **Testing** = the process of checking that each part of the system works correctly before releasing it to real users

| Test Type | Description | Tool |
|---|---|---|
| **Unit Testing** | Test individual functions in isolation (e.g., "does `sendSMS()` work correctly?") | PHPUnit |
| **Integration Testing** | Test that modules work together (e.g., submitting a complaint via web and checking it appears in the manager dashboard) | Manual / PHPUnit |
| **UAT (User Acceptance Testing)** | Real sellers and managers from a pilot market use the system and give feedback | Pilot deployment |
| **SMS Testing** | Send real SMS messages via Textbelt free key and verify they appear as complaints in the database | Textbelt / Vonage sandbox |
| **Gmail Testing** | Send emails to the complaint inbox and verify they are parsed correctly | Gmail API sandbox |

---

## 🗺️ Roadmap & Future Scope

> **Roadmap** = a plan showing what features will be built and in what order
> **Scope** = the boundary of what the project covers

### Phase 1 — MVP (Minimum Viable Product — the simplest working version)
- [x] Web complaint submission and tracking
- [x] Manager dashboard with filters
- [x] Announcement broadcast (web only)
- [x] Community support module

### Phase 2 — Multi-Channel
- [ ] SMS complaint submission via Vonage free tier (or Textbelt for early testing)
- [ ] Gmail complaint submission via Gmail API
- [ ] SMS and email notifications to sellers on status changes

### Phase 3 — Analytics & Scale
- [ ] Analytics dashboard with complaint trend charts per market
- [ ] Super Admin role — a platform administrator who can see all markets and their statistics
- [ ] French language support (bilingual interface — English and French)
- [ ] Android mobile app wrapper (a thin app that displays the website in a mobile app)

---

## 🤝 Contributing

Pull requests are welcome. For major changes, please open an issue first to discuss what you would like to change.

---

## 📄 License

This project is developed as an academic proposal for improving digital governance in Cameroonian local markets. All rights reserved to the original author.

---

*Built with ❤️ for the market communities of Cameroon.*
