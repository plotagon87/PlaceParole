# 🏗️ VoxMarché — Implementation Guide
### *Step-by-step build order for the Market Feedback & Communication Platform*

> **How to use this document:**
> Hand this file to an AI coding assistant (e.g., Cursor, GitHub Copilot, Claude) alongside the main `README.md`.
> Follow the phases **in strict order** — each phase depends on the one before it.
> Do **not** skip ahead. A seller cannot submit a complaint if the database does not exist yet.
> Each step includes: what to build, what file to create, and how to verify it works before moving on.

---

## 📐 Implementation Philosophy

Build in this order: **Foundation → Structure → Logic → Interface → Integration**

```
Phase 1 — Environment & Database Setup     (no code yet — just tools and tables)
Phase 2 — Project Skeleton & Config        (folder structure, shared files)
Phase 3 — Language System                  (before any UI — every page needs this)
Phase 4 — Authentication                   (login/register — everything needs a logged-in user)
Phase 5 — Seller Dashboard & Complaints    (the core feature)
Phase 6 — Manager Dashboard                (responds to what sellers submit)
Phase 7 — Announcements & Community        (broadcast and solidarity modules)
Phase 8 — Suggestions Module               (innovation ideas)
Phase 9 — SMS Integration                  (extend complaint submission to SMS)
Phase 10 — Gmail Integration               (extend complaint submission to Gmail)
Phase 11 — Analytics Dashboard             (visualise the data already collected)
Phase 12 — Final Polish & Testing          (language, responsiveness, UAT)
```

> **Key:**
> - 🟢 Required — must be done before moving to the next phase
> - 🟡 Verify — test this works before continuing
> - 📁 File — create this file

---

## ✅ Phase 1 — Environment & Database Setup

> **Goal:** Have a running local server with the database fully created.
> **Time estimate:** 30–60 minutes (one-time setup)

### Step 1.1 — Install XAMPP
- Download and install XAMPP from [apachefriends.org](https://apachefriends.org)
- **XAMPP** = Cross-platform Apache MySQL PHP Perl — a bundle that gives you a local web server on your computer
- Start **Apache** (the web server) and **MySQL** (the database server) from the XAMPP Control Panel
- 🟡 Verify: Open your browser and visit `http://localhost` — you should see the XAMPP welcome page

### Step 1.2 — Create the project folder
```bash
# Navigate to XAMPP's web root — this is the folder Apache serves files from
# Windows: C:\xampp\htdocs\
# Linux/Mac: /opt/lampp/htdocs/

# Create the project folder
mkdir voxmarche
```
- 🟡 Verify: Visit `http://localhost/voxmarche/` in your browser — you should see a blank or directory listing page

### Step 1.3 — Create the database and all tables
- Open `http://localhost/phpmyadmin` in your browser
- **phpMyAdmin** = a web-based graphical interface (GUI — Graphical User Interface) for managing MySQL databases
- Click **"New"** in the left sidebar to create a new database
- Name it `voxmarche_db` and click **Create**
- Click the **SQL** tab and paste the following script in full, then click **Go**:

```sql
-- ============================================================
-- VoxMarché — Full Database Schema
-- Run this entire block once in phpMyAdmin to create all tables
-- ============================================================

-- Table 1: markets
-- This is created FIRST because all other tables reference it
CREATE TABLE markets (
    id          INT AUTO_INCREMENT PRIMARY KEY,
    name        VARCHAR(150) NOT NULL,           -- e.g. "Marché Mokolo"
    location    VARCHAR(200),                    -- e.g. "Yaoundé, Centre Region"
    created_at  TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Table 2: users
-- Both sellers and managers are stored here, distinguished by the 'role' column
CREATE TABLE users (
    id          INT AUTO_INCREMENT PRIMARY KEY,
    market_id   INT NOT NULL,
    name        VARCHAR(100) NOT NULL,
    phone       VARCHAR(20) UNIQUE,
    email       VARCHAR(150) UNIQUE,
    role        ENUM('seller', 'manager') NOT NULL,
    stall_no    VARCHAR(20),                     -- Stall number, for sellers only
    password    VARCHAR(255) NOT NULL,           -- Always stored hashed, never plain text
    lang        ENUM('en', 'fr') DEFAULT 'en',  -- User's preferred language, saved on registration
    created_at  TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (market_id) REFERENCES markets(id)
);

-- Table 3: complaints
CREATE TABLE complaints (
    id          INT AUTO_INCREMENT PRIMARY KEY,
    market_id   INT NOT NULL,
    seller_id   INT NOT NULL,
    ref_code    VARCHAR(20) UNIQUE NOT NULL,     -- e.g. MKT-2024-00123
    category    VARCHAR(100),
    description TEXT,
    channel     ENUM('web', 'sms', 'gmail') DEFAULT 'web',
    status      ENUM('pending', 'in_review', 'resolved') DEFAULT 'pending',
    response    TEXT,
    created_at  TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at  TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (seller_id)  REFERENCES users(id),
    FOREIGN KEY (market_id)  REFERENCES markets(id)
);

-- Table 4: suggestions
CREATE TABLE suggestions (
    id          INT AUTO_INCREMENT PRIMARY KEY,
    market_id   INT NOT NULL,
    seller_id   INT NOT NULL,
    title       VARCHAR(200),
    description TEXT,
    status      ENUM('pending', 'approved', 'rejected') DEFAULT 'pending',
    created_at  TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (seller_id)  REFERENCES users(id),
    FOREIGN KEY (market_id)  REFERENCES markets(id)
);

-- Table 5: community_reports
CREATE TABLE community_reports (
    id           INT AUTO_INCREMENT PRIMARY KEY,
    market_id    INT NOT NULL,
    reported_by  INT NOT NULL,
    event_type   ENUM('death', 'illness', 'emergency', 'other'),
    person_name  VARCHAR(100),
    description  TEXT,
    status       ENUM('open', 'coordinated') DEFAULT 'open',
    created_at   TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (reported_by) REFERENCES users(id),
    FOREIGN KEY (market_id)   REFERENCES markets(id)
);

-- Table 6: announcements
CREATE TABLE announcements (
    id          INT AUTO_INCREMENT PRIMARY KEY,
    market_id   INT NOT NULL,
    manager_id  INT NOT NULL,
    title       VARCHAR(200),
    body        TEXT,
    sent_via    SET('web', 'sms', 'email'),
    created_at  TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (manager_id) REFERENCES users(id),
    FOREIGN KEY (market_id)  REFERENCES markets(id)
);
```

- 🟡 Verify: In phpMyAdmin, click on `voxmarche_db` in the left sidebar — you should see all 6 tables listed

---

## ✅ Phase 2 — Project Skeleton & Shared Config

> **Goal:** Create the full folder structure and the files that every other file depends on.
> Build these files first — they are the backbone of the entire application.

### Step 2.1 — Create the full folder structure

```
voxmarche/
│
├── config/
│   ├── db.php           ← 📁 CREATE THIS FIRST
│   └── lang.php         ← 📁 CREATE SECOND
│
├── lang/
│   ├── en.php           ← 📁 English translations
│   └── fr.php           ← 📁 French translations
│
├── templates/
│   ├── header.php       ← 📁 Shared navigation bar (included on every page)
│   └── footer.php       ← 📁 Shared footer
│
├── modules/
│   ├── auth/
│   ├── complaints/
│   ├── suggestions/
│   ├── community/
│   ├── announcements/
│   └── analytics/
│
├── integrations/
│   ├── sms_send.php
│   ├── sms_receive.php
│   ├── gmail_fetch.php
│   └── gmail_notify.php
│
├── assets/
│   ├── css/style.css
│   ├── js/app.js
│   └── img/
│
└── index.php            ← 📁 Home/landing page
```

### Step 2.2 — 📁 `config/db.php`

> This file creates one database connection that every other PHP file reuses.
> **PDO** = PHP Data Objects — a safe, modern way to talk to a MySQL database from PHP.

```php
<?php
// config/db.php
// This file is included at the top of every PHP file that needs the database.
// It creates ONE connection object ($pdo) that is reused everywhere.

define('DB_HOST', 'localhost');      // The server MySQL is running on
define('DB_NAME', 'voxmarche_db');   // The name of our database
define('DB_USER', 'root');           // MySQL username (default in XAMPP is 'root')
define('DB_PASS', '');               // MySQL password (default in XAMPP is empty '')

try {
    // PDO (PHP Data Objects) = a safe, modern interface for connecting PHP to MySQL
    // DSN (Data Source Name) = a string that tells PDO where and how to connect
    $dsn = "mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=utf8mb4";
    // charset=utf8mb4 supports all characters including French accented letters (é, è, ê, etc.)

    $pdo = new PDO($dsn, DB_USER, DB_PASS, [
        PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION, // Throw exceptions on errors
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,       // Return rows as associative arrays
        PDO::ATTR_EMULATE_PREPARES   => false,                  // Use real prepared statements
        // Prepared statements = a security technique that prevents SQL Injection attacks
        // SQL Injection = a hacking technique where malicious SQL code is inserted into input fields
    ]);

} catch (PDOException $e) {
    // PDOException = an error thrown by PDO when something goes wrong
    // die() stops all execution and shows an error message
    die("Database connection failed: " . $e->getMessage());
}
?>
```

- 🟡 Verify: Create a temporary `test.php` file in the root with `<?php require 'config/db.php'; echo "Connected!"; ?>` and visit `http://localhost/voxmarche/test.php` — you should see "Connected!" — then delete `test.php`

---

## ✅ Phase 3 — Language System

> **Goal:** Every page must be able to display text in English or French before any UI is built.
> Build this now — retrofitting it later is extremely painful.

### Step 3.1 — 📁 `lang/en.php`

```php
<?php
// lang/en.php — All English interface text in one place
// To add a new text string: add a new key => value pair here AND in fr.php
return [
    // --- Navigation ---
    'app_name'              => 'VoxMarché',
    'nav_home'              => 'Home',
    'nav_complaints'        => 'Complaints',
    'nav_suggestions'       => 'Suggestions',
    'nav_community'         => 'Community',
    'nav_announcements'     => 'Announcements',
    'nav_logout'            => 'Logout',

    // --- Authentication ---
    'login'                 => 'Login',
    'register'              => 'Register',
    'email'                 => 'Email Address',
    'password'              => 'Password',
    'name'                  => 'Full Name',
    'phone'                 => 'Phone Number',
    'stall_number'          => 'Stall Number',
    'select_market'         => 'Select your Market',
    'register_market'       => 'Register a New Market',
    'market_name'           => 'Market Name',
    'market_location'       => 'Market Location',
    'i_am_a'                => 'I am a',
    'seller'                => 'Seller',
    'manager'               => 'Manager',
    'already_have_account'  => 'Already have an account? Login',
    'no_account'            => 'No account yet? Register',

    // --- Complaints ---
    'submit_complaint'      => 'Submit a Complaint',
    'complaint_category'    => 'Category',
    'complaint_description' => 'Describe your complaint',
    'complaint_sent'        => 'Your complaint has been received.',
    'your_ref_code'         => 'Your reference code is',
    'track_complaint'       => 'Track a Complaint',
    'enter_ref_code'        => 'Enter your reference code',
    'cat_infrastructure'    => 'Infrastructure',
    'cat_sanitation'        => 'Sanitation',
    'cat_stall_allocation'  => 'Stall Allocation',
    'cat_security'          => 'Security',
    'cat_other'             => 'Other',

    // --- Status Labels ---
    'status_pending'        => 'Pending',
    'status_in_review'      => 'In Review',
    'status_resolved'       => 'Resolved',

    // --- Suggestions ---
    'submit_suggestion'     => 'Submit a Suggestion',
    'suggestion_title'      => 'Title',
    'suggestion_description'=> 'Describe your idea',
    'suggestion_sent'       => 'Your suggestion has been submitted.',

    // --- Community ---
    'report_event'          => 'Report a Community Event',
    'event_type'            => 'Event Type',
    'event_death'           => 'Death',
    'event_illness'         => 'Illness',
    'event_emergency'       => 'Emergency',
    'event_other'           => 'Other',
    'person_name'           => 'Name of person affected',
    'event_description'     => 'Details',
    'report_sent'           => 'Your report has been shared with the community.',

    // --- Announcements ---
    'announcements'         => 'Announcements',
    'no_announcements'      => 'No announcements yet.',
    'new_announcement'      => 'New Announcement',
    'announcement_title'    => 'Title',
    'announcement_body'     => 'Message',
    'send_via'              => 'Send via',

    // --- General ---
    'submit'                => 'Submit',
    'save'                  => 'Save',
    'cancel'                => 'Cancel',
    'back'                  => 'Back',
    'search'                => 'Search',
    'filter'                => 'Filter',
    'all'                   => 'All',
    'date'                  => 'Date',
    'actions'               => 'Actions',
    'respond'               => 'Respond',
    'mark_resolved'         => 'Mark as Resolved',
    'error_required'        => 'This field is required.',
    'error_invalid_email'   => 'Please enter a valid email address.',
    'success'               => 'Success',
    'error'                 => 'Error',
];
?>
```

### Step 3.2 — 📁 `lang/fr.php`

```php
<?php
// lang/fr.php — All French interface text
// Every key here MUST match exactly the keys in en.php
return [
    // --- Navigation ---
    'app_name'              => 'VoxMarché',
    'nav_home'              => 'Accueil',
    'nav_complaints'        => 'Plaintes',
    'nav_suggestions'       => 'Suggestions',
    'nav_community'         => 'Communauté',
    'nav_announcements'     => 'Annonces',
    'nav_logout'            => 'Déconnexion',

    // --- Authentication ---
    'login'                 => 'Connexion',
    'register'              => "S'inscrire",
    'email'                 => 'Adresse Email',
    'password'              => 'Mot de passe',
    'name'                  => 'Nom complet',
    'phone'                 => 'Numéro de téléphone',
    'stall_number'          => 'Numéro de stand',
    'select_market'         => 'Choisissez votre marché',
    'register_market'       => 'Enregistrer un nouveau marché',
    'market_name'           => 'Nom du marché',
    'market_location'       => 'Localisation du marché',
    'i_am_a'                => 'Je suis',
    'seller'                => 'Vendeur',
    'manager'               => 'Gestionnaire',
    'already_have_account'  => 'Déjà un compte ? Se connecter',
    'no_account'            => 'Pas encore de compte ? S\'inscrire',

    // --- Complaints ---
    'submit_complaint'      => 'Soumettre une Plainte',
    'complaint_category'    => 'Catégorie',
    'complaint_description' => 'Décrivez votre plainte',
    'complaint_sent'        => 'Votre plainte a bien été reçue.',
    'your_ref_code'         => 'Votre code de référence est',
    'track_complaint'       => 'Suivre une Plainte',
    'enter_ref_code'        => 'Entrez votre code de référence',
    'cat_infrastructure'    => 'Infrastructure',
    'cat_sanitation'        => 'Assainissement',
    'cat_stall_allocation'  => 'Attribution des stands',
    'cat_security'          => 'Sécurité',
    'cat_other'             => 'Autre',

    // --- Status Labels ---
    'status_pending'        => 'En attente',
    'status_in_review'      => 'En cours',
    'status_resolved'       => 'Résolu',

    // --- Suggestions ---
    'submit_suggestion'     => 'Soumettre une Suggestion',
    'suggestion_title'      => 'Titre',
    'suggestion_description'=> 'Décrivez votre idée',
    'suggestion_sent'       => 'Votre suggestion a été soumise.',

    // --- Community ---
    'report_event'          => 'Signaler un Événement Communautaire',
    'event_type'            => "Type d'événement",
    'event_death'           => 'Décès',
    'event_illness'         => 'Maladie',
    'event_emergency'       => 'Urgence',
    'event_other'           => 'Autre',
    'person_name'           => 'Nom de la personne concernée',
    'event_description'     => 'Détails',
    'report_sent'           => 'Votre signalement a été partagé avec la communauté.',

    // --- Announcements ---
    'announcements'         => 'Annonces',
    'no_announcements'      => 'Aucune annonce pour le moment.',
    'new_announcement'      => 'Nouvelle Annonce',
    'announcement_title'    => 'Titre',
    'announcement_body'     => 'Message',
    'send_via'              => 'Envoyer via',

    // --- General ---
    'submit'                => 'Soumettre',
    'save'                  => 'Enregistrer',
    'cancel'                => 'Annuler',
    'back'                  => 'Retour',
    'search'                => 'Rechercher',
    'filter'                => 'Filtrer',
    'all'                   => 'Tout',
    'date'                  => 'Date',
    'actions'               => 'Actions',
    'respond'               => 'Répondre',
    'mark_resolved'         => 'Marquer comme résolu',
    'error_required'        => 'Ce champ est obligatoire.',
    'error_invalid_email'   => 'Veuillez entrer une adresse email valide.',
    'success'               => 'Succès',
    'error'                 => 'Erreur',
];
?>
```

### Step 3.3 — 📁 `config/lang.php`

```php
<?php
// config/lang.php — Language detection and loading
// Include this file at the top of EVERY page (after session_start)

if (session_status() === PHP_SESSION_NONE) session_start();

// Step 1: If the user manually clicked a language toggle button (?lang=fr or ?lang=en)
if (isset($_GET['lang']) && in_array($_GET['lang'], ['en', 'fr'])) {
    $_SESSION['lang'] = $_GET['lang'];
}

// Step 2: If no language is in the session yet, detect it from the browser
if (!isset($_SESSION['lang'])) {
    // HTTP_ACCEPT_LANGUAGE = a string sent by the browser e.g. "fr-FR,fr;q=0.9,en-US;q=0.8"
    // substr(..., 0, 2) extracts just the first 2 characters e.g. "fr"
    $browserLang = substr($_SERVER['HTTP_ACCEPT_LANGUAGE'] ?? 'en', 0, 2);
    $_SESSION['lang'] = in_array($browserLang, ['en', 'fr']) ? $browserLang : 'en';
}

// Step 3: Load the correct language file into the $t (translations) variable
// require returns the array from the language file and assigns it to $t
$t = require __DIR__ . "/../lang/{$_SESSION['lang']}.php";
// Usage on any page: echo $t['submit_complaint'];
?>
```

---

## ✅ Phase 4 — Authentication Module

> **Goal:** Users can register (as manager or seller) and log in. Sessions are started correctly.
> This is the gateway to every other feature — nothing works without it.

### Step 4.1 — 📁 `templates/header.php`

> This file is included at the top of every page. It loads Tailwind CSS, starts the session,
> loads the language system, and shows the navigation bar.

```php
<?php
// templates/header.php
// Include at the very top of every page with: require_once '../templates/header.php';
// Adjust the path (../) depending on how deep the file is in the folder structure

if (session_status() === PHP_SESSION_NONE) session_start();
require_once __DIR__ . '/../config/lang.php'; // Load language system — $t is now available
?>
<!DOCTYPE html>
<!-- lang attribute tells the browser which language the page is in -->
<html lang="<?= $_SESSION['lang'] ?? 'en' ?>">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <!-- viewport meta tag makes the page scale correctly on mobile screens -->
    <title><?= $t['app_name'] ?></title>

    <!-- Tailwind CSS loaded from CDN (Content Delivery Network — no installation needed) -->
    <script src="https://cdn.tailwindcss.com"></script>
    <script>
        // Configure Tailwind with VoxMarché's custom color palette
        tailwind.config = {
            theme: {
                extend: {
                    colors: {
                        primary: '#16a34a', // Green — Cameroon national color
                        accent:  '#ea580c', // Orange — Cameroon national color
                    }
                }
            }
        }
    </script>
</head>
<body class="bg-gray-50 text-gray-800 min-h-screen">

<!-- Navigation Bar -->
<nav class="bg-primary text-white px-6 py-3 flex items-center justify-between shadow-md">

    <!-- Logo / App Name -->
    <a href="/voxmarche/index.php" class="text-xl font-bold tracking-wide">
        🛒 <?= $t['app_name'] ?>
    </a>

    <!-- Navigation links — only shown when user is logged in -->
    <?php if (isset($_SESSION['user_id'])): ?>
    <div class="flex items-center gap-4 text-sm">
        <a href="/voxmarche/modules/complaints/submit.php"    class="hover:underline"><?= $t['nav_complaints'] ?></a>
        <a href="/voxmarche/modules/suggestions/submit.php"   class="hover:underline"><?= $t['nav_suggestions'] ?></a>
        <a href="/voxmarche/modules/community/report.php"     class="hover:underline"><?= $t['nav_community'] ?></a>
        <a href="/voxmarche/modules/announcements/list.php"   class="hover:underline"><?= $t['nav_announcements'] ?></a>
        <a href="/voxmarche/modules/auth/logout.php"          class="hover:underline text-red-200"><?= $t['nav_logout'] ?></a>
    </div>
    <?php endif; ?>

    <!-- Language Toggle — always visible -->
    <div class="flex items-center gap-1 text-sm">
        <a href="?lang=en"
           class="px-2 py-1 rounded <?= ($_SESSION['lang'] ?? 'en') === 'en' ? 'bg-white text-primary font-bold' : 'text-white hover:bg-green-700' ?>">
            🇬🇧 EN
        </a>
        <a href="?lang=fr"
           class="px-2 py-1 rounded <?= ($_SESSION['lang'] ?? 'en') === 'fr' ? 'bg-white text-primary font-bold' : 'text-white hover:bg-green-700' ?>">
            🇫🇷 FR
        </a>
    </div>
</nav>

<!-- Main content wrapper -->
<main class="max-w-4xl mx-auto px-4 py-8">
<!-- Note: max-w-4xl centers content and limits line width for readability -->
<!-- mx-auto = margin auto left and right = horizontally centered -->
```

### Step 4.2 — 📁 `templates/footer.php`

```php
<?php // templates/footer.php — Close the main content wrapper and body ?>
</main>
<footer class="text-center text-gray-400 text-sm py-6 mt-10 border-t border-gray-200">
    &copy; <?= date('Y') ?> VoxMarché — Built for Cameroon's Market Communities
</footer>
</body>
</html>
```

### Step 4.3 — 📁 `modules/auth/register_manager.php`

> A manager fills this form to register their market AND their account simultaneously.
> Two database inserts happen: first into `markets`, then into `users`.

```php
<?php
require_once '../../templates/header.php';
require_once '../../config/db.php';

$errors = []; // Array to collect validation errors before showing them to the user

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // $_SERVER['REQUEST_METHOD'] tells us how the page was accessed
    // 'POST' means the form was submitted; 'GET' means the page was just loaded

    // --- Step 1: Collect and sanitise (clean) form input ---
    // htmlspecialchars() converts dangerous characters like < > & into safe HTML entities
    // trim() removes leading and trailing whitespace from the value
    $marketName     = trim(htmlspecialchars($_POST['market_name']     ?? ''));
    $marketLocation = trim(htmlspecialchars($_POST['market_location'] ?? ''));
    $name           = trim(htmlspecialchars($_POST['name']            ?? ''));
    $phone          = trim(htmlspecialchars($_POST['phone']           ?? ''));
    $email          = trim($_POST['email']    ?? '');
    $password       = trim($_POST['password'] ?? '');

    // --- Step 2: Validate (check) the input ---
    if (empty($marketName))     $errors[] = $t['error_required'] . ' (' . $t['market_name'] . ')';
    if (empty($name))           $errors[] = $t['error_required'] . ' (' . $t['name'] . ')';
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) $errors[] = $t['error_invalid_email'];
    // filter_var() with FILTER_VALIDATE_EMAIL checks if the email is in a valid format

    if (empty($errors)) {
        // --- Step 3: Insert market first ---
        $stmt = $pdo->prepare("INSERT INTO markets (name, location) VALUES (?, ?)");
        // prepare() creates a prepared statement — ? are placeholders, filled safely by execute()
        $stmt->execute([$marketName, $marketLocation]);
        $marketId = $pdo->lastInsertId(); // Get the auto-generated ID of the market just created

        // --- Step 4: Hash the password and insert the manager user ---
        $hashedPassword = password_hash($password, PASSWORD_DEFAULT);
        // password_hash() converts plain text password to a secure one-way hash
        // PASSWORD_DEFAULT uses bcrypt algorithm — the industry standard for password storage

        $stmt = $pdo->prepare("
            INSERT INTO users (market_id, name, phone, email, role, password, lang)
            VALUES (?, ?, ?, ?, 'manager', ?, ?)
        ");
        $stmt->execute([$marketId, $name, $phone, $email, $hashedPassword, $_SESSION['lang']]);

        // --- Step 5: Redirect to login ---
        header('Location: login.php?registered=1');
        // header('Location: ...') sends the browser to a different page
        exit; // Always call exit after header redirect to stop further code execution
    }
}
?>

<div class="max-w-lg mx-auto bg-white rounded-2xl shadow p-8">
    <h1 class="text-2xl font-bold text-primary mb-6"><?= $t['register_market'] ?></h1>

    <!-- Show validation errors if any exist -->
    <?php if (!empty($errors)): ?>
        <div class="bg-red-50 border border-red-300 text-red-700 rounded-lg p-4 mb-4">
            <?php foreach ($errors as $error): ?>
                <p>⚠️ <?= $error ?></p>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>

    <form method="POST">
        <!-- Market Details Section -->
        <p class="font-semibold text-gray-600 mb-2 mt-4"><?= $t['market_name'] ?></p>
        <input type="text" name="market_name" required
               placeholder="e.g. Marché Mokolo"
               class="w-full border border-gray-300 rounded-lg px-3 py-2 mb-4 focus:outline-none focus:ring-2 focus:ring-primary">

        <p class="font-semibold text-gray-600 mb-2"><?= $t['market_location'] ?></p>
        <input type="text" name="market_location"
               placeholder="e.g. Yaoundé, Centre Region"
               class="w-full border border-gray-300 rounded-lg px-3 py-2 mb-4 focus:outline-none focus:ring-2 focus:ring-primary">

        <!-- Manager Personal Details Section -->
        <p class="font-semibold text-gray-600 mb-2 mt-6"><?= $t['name'] ?></p>
        <input type="text" name="name" required
               class="w-full border border-gray-300 rounded-lg px-3 py-2 mb-4 focus:outline-none focus:ring-2 focus:ring-primary">

        <p class="font-semibold text-gray-600 mb-2"><?= $t['phone'] ?></p>
        <input type="tel" name="phone" placeholder="+237612345678"
               class="w-full border border-gray-300 rounded-lg px-3 py-2 mb-4 focus:outline-none focus:ring-2 focus:ring-primary">

        <p class="font-semibold text-gray-600 mb-2"><?= $t['email'] ?></p>
        <input type="email" name="email" required
               class="w-full border border-gray-300 rounded-lg px-3 py-2 mb-4 focus:outline-none focus:ring-2 focus:ring-primary">

        <p class="font-semibold text-gray-600 mb-2"><?= $t['password'] ?></p>
        <input type="password" name="password" required
               class="w-full border border-gray-300 rounded-lg px-3 py-2 mb-6 focus:outline-none focus:ring-2 focus:ring-primary">

        <button type="submit"
                class="w-full bg-primary text-white py-3 rounded-xl font-semibold hover:bg-green-700 transition">
            <?= $t['register'] ?>
        </button>
    </form>

    <p class="text-center text-sm text-gray-500 mt-4">
        <a href="login.php" class="text-primary hover:underline"><?= $t['already_have_account'] ?></a>
    </p>
</div>

<?php require_once '../../templates/footer.php'; ?>
```

### Step 4.4 — 📁 `modules/auth/register_seller.php`

> Identical structure to manager registration, but the seller selects an existing market
> from a dropdown populated from the `markets` table.

```php
<?php
require_once '../../templates/header.php';
require_once '../../config/db.php';

// Load all markets for the dropdown list
$markets = $pdo->query("SELECT id, name, location FROM markets ORDER BY name ASC")->fetchAll();
// fetchAll() retrieves ALL rows from the query result as an array of associative arrays

$errors = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $marketId = (int) ($_POST['market_id'] ?? 0);
    // (int) casts (converts) the value to an integer, preventing non-numeric values
    $name     = trim(htmlspecialchars($_POST['name']     ?? ''));
    $phone    = trim(htmlspecialchars($_POST['phone']    ?? ''));
    $stallNo  = trim(htmlspecialchars($_POST['stall_no'] ?? ''));
    $email    = trim($_POST['email']    ?? '');
    $password = trim($_POST['password'] ?? '');

    if ($marketId === 0)  $errors[] = $t['error_required'] . ' (' . $t['select_market'] . ')';
    if (empty($name))     $errors[] = $t['error_required'] . ' (' . $t['name'] . ')';
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) $errors[] = $t['error_invalid_email'];

    if (empty($errors)) {
        $hashedPassword = password_hash($password, PASSWORD_DEFAULT);
        $stmt = $pdo->prepare("
            INSERT INTO users (market_id, name, phone, email, stall_no, role, password, lang)
            VALUES (?, ?, ?, ?, ?, 'seller', ?, ?)
        ");
        $stmt->execute([$marketId, $name, $phone, $email, $stallNo, $hashedPassword, $_SESSION['lang']]);
        header('Location: login.php?registered=1');
        exit;
    }
}
?>

<div class="max-w-lg mx-auto bg-white rounded-2xl shadow p-8">
    <h1 class="text-2xl font-bold text-primary mb-6"><?= $t['register'] ?> — <?= $t['seller'] ?></h1>

    <?php if (!empty($errors)): ?>
        <div class="bg-red-50 border border-red-300 text-red-700 rounded-lg p-4 mb-4">
            <?php foreach ($errors as $error): ?><p>⚠️ <?= $error ?></p><?php endforeach; ?>
        </div>
    <?php endif; ?>

    <form method="POST">
        <!-- Market Selection Dropdown -->
        <p class="font-semibold text-gray-600 mb-2"><?= $t['select_market'] ?></p>
        <select name="market_id" required
                class="w-full border border-gray-300 rounded-lg px-3 py-2 mb-4 focus:outline-none focus:ring-2 focus:ring-primary">
            <option value="">— <?= $t['select_market'] ?> —</option>
            <?php foreach ($markets as $market): ?>
                <!-- Loop through every market and create one <option> per market -->
                <option value="<?= $market['id'] ?>">
                    <?= htmlspecialchars($market['name']) ?> (<?= htmlspecialchars($market['location']) ?>)
                </option>
            <?php endforeach; ?>
        </select>

        <p class="font-semibold text-gray-600 mb-2"><?= $t['name'] ?></p>
        <input type="text" name="name" required
               class="w-full border border-gray-300 rounded-lg px-3 py-2 mb-4 focus:outline-none focus:ring-2 focus:ring-primary">

        <p class="font-semibold text-gray-600 mb-2"><?= $t['stall_number'] ?></p>
        <input type="text" name="stall_no" placeholder="e.g. B-12"
               class="w-full border border-gray-300 rounded-lg px-3 py-2 mb-4 focus:outline-none focus:ring-2 focus:ring-primary">

        <p class="font-semibold text-gray-600 mb-2"><?= $t['phone'] ?></p>
        <input type="tel" name="phone" placeholder="+237612345678"
               class="w-full border border-gray-300 rounded-lg px-3 py-2 mb-4 focus:outline-none focus:ring-2 focus:ring-primary">

        <p class="font-semibold text-gray-600 mb-2"><?= $t['email'] ?></p>
        <input type="email" name="email" required
               class="w-full border border-gray-300 rounded-lg px-3 py-2 mb-4 focus:outline-none focus:ring-2 focus:ring-primary">

        <p class="font-semibold text-gray-600 mb-2"><?= $t['password'] ?></p>
        <input type="password" name="password" required
               class="w-full border border-gray-300 rounded-lg px-3 py-2 mb-6 focus:outline-none focus:ring-2 focus:ring-primary">

        <button type="submit"
                class="w-full bg-primary text-white py-3 rounded-xl font-semibold hover:bg-green-700 transition">
            <?= $t['register'] ?>
        </button>
    </form>
</div>

<?php require_once '../../templates/footer.php'; ?>
```

### Step 4.5 — 📁 `modules/auth/login.php`

```php
<?php
require_once '../../templates/header.php';
require_once '../../config/db.php';

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email    = trim($_POST['email']    ?? '');
    $password = trim($_POST['password'] ?? '');

    // Fetch the user record from the database by email
    $stmt = $pdo->prepare("SELECT * FROM users WHERE email = ? LIMIT 1");
    $stmt->execute([$email]);
    $user = $stmt->fetch(); // fetch() returns ONE row, or false if no match found

    if ($user && password_verify($password, $user['password'])) {
        // password_verify() checks if the plain text password matches the stored hash
        // If it matches, store the user's key details in the session

        $_SESSION['user_id']   = $user['id'];
        $_SESSION['role']      = $user['role'];
        $_SESSION['market_id'] = $user['market_id'];
        $_SESSION['name']      = $user['name'];
        $_SESSION['lang']      = $user['lang']; // Restore user's preferred language

        // Redirect to the correct dashboard based on role
        if ($user['role'] === 'manager') {
            header('Location: ../../modules/complaints/list.php');
        } else {
            header('Location: ../../modules/complaints/submit.php');
        }
        exit;
    } else {
        $error = 'Invalid email or password.';
    }
}
?>

<div class="max-w-md mx-auto bg-white rounded-2xl shadow p-8 mt-10">
    <h1 class="text-2xl font-bold text-primary mb-6"><?= $t['login'] ?></h1>

    <?php if ($error): ?>
        <div class="bg-red-50 border border-red-300 text-red-700 rounded-lg p-3 mb-4">⚠️ <?= $error ?></div>
    <?php endif; ?>

    <?php if (isset($_GET['registered'])): ?>
        <div class="bg-green-50 border border-green-300 text-green-700 rounded-lg p-3 mb-4">
            ✅ Registration successful. Please log in.
        </div>
    <?php endif; ?>

    <form method="POST">
        <p class="font-semibold text-gray-600 mb-2"><?= $t['email'] ?></p>
        <input type="email" name="email" required
               class="w-full border border-gray-300 rounded-lg px-3 py-2 mb-4 focus:outline-none focus:ring-2 focus:ring-primary">

        <p class="font-semibold text-gray-600 mb-2"><?= $t['password'] ?></p>
        <input type="password" name="password" required
               class="w-full border border-gray-300 rounded-lg px-3 py-2 mb-6 focus:outline-none focus:ring-2 focus:ring-primary">

        <button type="submit"
                class="w-full bg-primary text-white py-3 rounded-xl font-semibold hover:bg-green-700 transition">
            <?= $t['login'] ?>
        </button>
    </form>
</div>

<?php require_once '../../templates/footer.php'; ?>
```

### Step 4.6 — 📁 `modules/auth/logout.php`

```php
<?php
session_start();
session_destroy(); // Destroys all session data — effectively logs the user out
header('Location: ../../modules/auth/login.php');
exit;
?>
```

### Step 4.7 — 📁 `config/auth_guard.php`

> **Auth guard** = a short script included at the top of every protected page.
> If a user is not logged in, it redirects them to the login page immediately.

```php
<?php
// config/auth_guard.php
// Include at the top of every page that requires the user to be logged in:
// require_once '../../config/auth_guard.php';

if (session_status() === PHP_SESSION_NONE) session_start();

if (!isset($_SESSION['user_id'])) {
    // User is not logged in — send them to the login page
    header('Location: /voxmarche/modules/auth/login.php');
    exit;
}

// Optional: restrict a page to managers only
// Usage: require_once 'auth_guard.php'; manager_only();
function manager_only() {
    if ($_SESSION['role'] !== 'manager') {
        header('Location: /voxmarche/modules/complaints/submit.php');
        exit;
    }
}
?>
```

- 🟡 Verify Phase 4: Register a manager (creates a market), register a seller (selects that market), log in as both — each should land on their respective page. Logout should return to login.

---

## ✅ Phase 5 — Complaint Submission & Tracking (Seller Side)

> **Goal:** A logged-in seller can submit a complaint via the web form and receive a reference code.
> This is the platform's most important feature — build and test it thoroughly before moving on.

### Step 5.1 — 📁 `modules/complaints/submit.php`

```php
<?php
require_once '../../config/auth_guard.php';   // Redirect to login if not logged in
require_once '../../templates/header.php';
require_once '../../config/db.php';

$success  = false;
$ref_code = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $category    = htmlspecialchars($_POST['category']    ?? '');
    $description = htmlspecialchars($_POST['description'] ?? '');

    // Generate a unique reference code
    // uniqid() generates a unique string based on current time
    // strtoupper() converts letters to uppercase
    // The format will look like: MKT-6A4F2B1D
    $ref_code = 'MKT-' . strtoupper(substr(uniqid(), -8));

    $stmt = $pdo->prepare("
        INSERT INTO complaints (market_id, seller_id, ref_code, category, description, channel)
        VALUES (?, ?, ?, ?, ?, 'web')
    ");
    $stmt->execute([
        $_SESSION['market_id'],  // From session — automatically scoped to the correct market
        $_SESSION['user_id'],
        $ref_code,
        $category,
        $description
    ]);

    $success = true;
}

// Categories array — keys match translation keys in lang files
$categories = ['cat_infrastructure', 'cat_sanitation', 'cat_stall_allocation', 'cat_security', 'cat_other'];
?>

<div class="max-w-lg mx-auto bg-white rounded-2xl shadow p-8">
    <h1 class="text-2xl font-bold text-primary mb-6"><?= $t['submit_complaint'] ?></h1>

    <!-- Success message shown after form submission -->
    <?php if ($success): ?>
        <div class="bg-green-50 border border-green-300 text-green-800 rounded-xl p-5 text-center">
            <p class="text-lg font-semibold">✅ <?= $t['complaint_sent'] ?></p>
            <p class="mt-2"><?= $t['your_ref_code'] ?>:</p>
            <!-- The reference code is shown large and bold for easy note-taking -->
            <p class="text-3xl font-bold text-primary mt-1 tracking-widest"><?= $ref_code ?></p>
            <p class="text-sm text-gray-500 mt-2">
                <?= $t['track_complaint'] ?>: <a href="track.php" class="underline text-primary">track.php</a>
            </p>
        </div>
    <?php else: ?>
        <form method="POST">
            <p class="font-semibold text-gray-600 mb-2"><?= $t['complaint_category'] ?></p>
            <select name="category" required
                    class="w-full border border-gray-300 rounded-lg px-3 py-2 mb-4 focus:outline-none focus:ring-2 focus:ring-primary">
                <option value="">— <?= $t['complaint_category'] ?> —</option>
                <?php foreach ($categories as $key): ?>
                    <option value="<?= $key ?>"><?= $t[$key] ?></option>
                <?php endforeach; ?>
            </select>

            <p class="font-semibold text-gray-600 mb-2"><?= $t['complaint_description'] ?></p>
            <textarea name="description" rows="5" required
                      class="w-full border border-gray-300 rounded-lg px-3 py-2 mb-6 focus:outline-none focus:ring-2 focus:ring-primary"
                      placeholder="<?= $t['complaint_description'] ?>..."></textarea>

            <button type="submit"
                    class="w-full bg-primary text-white py-3 rounded-xl font-semibold hover:bg-green-700 transition">
                <?= $t['submit'] ?>
            </button>
        </form>
    <?php endif; ?>
</div>

<?php require_once '../../templates/footer.php'; ?>
```

### Step 5.2 — 📁 `modules/complaints/track.php`

> Allows a seller (or anyone) to check the status of a complaint using its reference code.

```php
<?php
require_once '../../templates/header.php';
require_once '../../config/db.php';

$complaint = null;
$notFound  = false;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $refCode = strtoupper(trim($_POST['ref_code'] ?? ''));

    $stmt = $pdo->prepare("SELECT * FROM complaints WHERE ref_code = ? LIMIT 1");
    $stmt->execute([$refCode]);
    $complaint = $stmt->fetch();

    if (!$complaint) $notFound = true;
}

// Status badge colour map — maps each status value to a Tailwind CSS class
$statusColors = [
    'pending'   => 'bg-red-100 text-red-700',
    'in_review' => 'bg-yellow-100 text-yellow-700',
    'resolved'  => 'bg-green-100 text-green-700',
];
?>

<div class="max-w-lg mx-auto bg-white rounded-2xl shadow p-8">
    <h1 class="text-2xl font-bold text-primary mb-6"><?= $t['track_complaint'] ?></h1>

    <form method="POST" class="flex gap-2 mb-6">
        <input type="text" name="ref_code" required
               placeholder="<?= $t['enter_ref_code'] ?> e.g. MKT-6A4F2B1D"
               class="flex-1 border border-gray-300 rounded-lg px-3 py-2 focus:outline-none focus:ring-2 focus:ring-primary">
        <button type="submit"
                class="bg-primary text-white px-5 py-2 rounded-lg font-semibold hover:bg-green-700 transition">
            <?= $t['search'] ?>
        </button>
    </form>

    <?php if ($notFound): ?>
        <p class="text-red-600">⚠️ No complaint found with that reference code.</p>
    <?php endif; ?>

    <?php if ($complaint): ?>
        <div class="border border-gray-200 rounded-xl p-5 space-y-3">
            <div class="flex justify-between items-center">
                <span class="font-mono font-bold text-primary"><?= $complaint['ref_code'] ?></span>
                <!-- Status badge using dynamic colour from $statusColors map -->
                <span class="px-3 py-1 rounded-full text-xs font-bold <?= $statusColors[$complaint['status']] ?>">
                    <?= $t['status_' . $complaint['status']] ?>
                </span>
            </div>
            <p><span class="font-semibold"><?= $t['complaint_category'] ?>:</span> <?= $t[$complaint['category']] ?></p>
            <p><span class="font-semibold"><?= $t['complaint_description'] ?>:</span> <?= nl2br($complaint['description']) ?></p>
            <!-- nl2br() converts newline characters into HTML <br> tags for proper display -->
            <?php if ($complaint['response']): ?>
                <div class="bg-green-50 border border-green-200 rounded-lg p-3 mt-2">
                    <p class="font-semibold text-green-800">Manager Response:</p>
                    <p><?= nl2br($complaint['response']) ?></p>
                </div>
            <?php endif; ?>
            <p class="text-sm text-gray-400"><?= $t['date'] ?>: <?= $complaint['created_at'] ?></p>
        </div>
    <?php endif; ?>
</div>

<?php require_once '../../templates/footer.php'; ?>
```

- 🟡 Verify Phase 5: Submit a complaint as a seller → note the reference code → use track.php to find it → confirm it shows status "Pending"

---

## ✅ Phase 6 — Manager Dashboard & Complaint Response

> **Goal:** The manager can see all complaints for their market, filter them, respond, and update status.

### Step 6.1 — 📁 `modules/complaints/list.php`

```php
<?php
require_once '../../config/auth_guard.php';
manager_only(); // This function from auth_guard.php blocks sellers from accessing this page
require_once '../../templates/header.php';
require_once '../../config/db.php';

// --- Build the SQL query dynamically based on active filters ---
// Base query — always scoped to the manager's market via market_id
$sql    = "SELECT c.*, u.name AS seller_name, u.stall_no
           FROM complaints c
           JOIN users u ON c.seller_id = u.id
           WHERE c.market_id = ?";
$params = [$_SESSION['market_id']]; // Always filter by market — never show other markets' data

// Append optional filters if the manager selected them
$filterStatus   = $_GET['status']   ?? '';
$filterCategory = $_GET['category'] ?? '';

if ($filterStatus)   { $sql .= " AND c.status = ?";   $params[] = $filterStatus; }
if ($filterCategory) { $sql .= " AND c.category = ?"; $params[] = $filterCategory; }

$sql .= " ORDER BY c.created_at DESC"; // Show newest complaints first

$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$complaints = $stmt->fetchAll();

$statusColors = [
    'pending'   => 'bg-red-100 text-red-700',
    'in_review' => 'bg-yellow-100 text-yellow-700',
    'resolved'  => 'bg-green-100 text-green-700',
];
?>

<div class="bg-white rounded-2xl shadow p-6">
    <div class="flex justify-between items-center mb-6">
        <h1 class="text-2xl font-bold text-primary"><?= $t['nav_complaints'] ?></h1>
        <span class="text-sm text-gray-500"><?= count($complaints) ?> results</span>
    </div>

    <!-- Filter bar -->
    <form method="GET" class="flex gap-3 mb-6 flex-wrap">
        <select name="status"
                class="border border-gray-300 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-primary">
            <option value=""><?= $t['all'] ?> Status</option>
            <option value="pending"   <?= $filterStatus === 'pending'   ? 'selected' : '' ?>><?= $t['status_pending'] ?></option>
            <option value="in_review" <?= $filterStatus === 'in_review' ? 'selected' : '' ?>><?= $t['status_in_review'] ?></option>
            <option value="resolved"  <?= $filterStatus === 'resolved'  ? 'selected' : '' ?>><?= $t['status_resolved'] ?></option>
        </select>
        <select name="category"
                class="border border-gray-300 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-primary">
            <option value=""><?= $t['all'] ?> <?= $t['complaint_category'] ?></option>
            <?php foreach (['cat_infrastructure','cat_sanitation','cat_stall_allocation','cat_security','cat_other'] as $k): ?>
                <option value="<?= $k ?>" <?= $filterCategory === $k ? 'selected' : '' ?>><?= $t[$k] ?></option>
            <?php endforeach; ?>
        </select>
        <button type="submit"
                class="bg-primary text-white px-4 py-2 rounded-lg text-sm hover:bg-green-700 transition">
            <?= $t['filter'] ?>
        </button>
    </form>

    <!-- Complaints table -->
    <div class="overflow-x-auto">
        <!-- overflow-x-auto adds a horizontal scrollbar on small screens -->
        <table class="w-full text-sm">
            <thead class="bg-gray-50 text-gray-600 uppercase text-xs">
                <tr>
                    <th class="px-4 py-3 text-left">Ref</th>
                    <th class="px-4 py-3 text-left">Seller</th>
                    <th class="px-4 py-3 text-left"><?= $t['complaint_category'] ?></th>
                    <th class="px-4 py-3 text-left">Status</th>
                    <th class="px-4 py-3 text-left"><?= $t['date'] ?></th>
                    <th class="px-4 py-3 text-left"><?= $t['actions'] ?></th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-100">
                <!-- divide-y = adds a thin horizontal border between table rows -->
                <?php foreach ($complaints as $c): ?>
                <tr class="hover:bg-gray-50">
                    <td class="px-4 py-3 font-mono text-xs"><?= $c['ref_code'] ?></td>
                    <td class="px-4 py-3"><?= htmlspecialchars($c['seller_name']) ?> <span class="text-gray-400">(<?= $c['stall_no'] ?>)</span></td>
                    <td class="px-4 py-3"><?= $t[$c['category']] ?></td>
                    <td class="px-4 py-3">
                        <span class="px-2 py-1 rounded-full text-xs font-bold <?= $statusColors[$c['status']] ?>">
                            <?= $t['status_' . $c['status']] ?>
                        </span>
                    </td>
                    <td class="px-4 py-3 text-gray-400"><?= date('d M Y', strtotime($c['created_at'])) ?></td>
                    <td class="px-4 py-3">
                        <a href="respond.php?id=<?= $c['id'] ?>"
                           class="text-primary hover:underline font-semibold"><?= $t['respond'] ?></a>
                    </td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>

<?php require_once '../../templates/footer.php'; ?>
```

### Step 6.2 — 📁 `modules/complaints/respond.php`

```php
<?php
require_once '../../config/auth_guard.php';
manager_only();
require_once '../../templates/header.php';
require_once '../../config/db.php';

$id = (int) ($_GET['id'] ?? 0);

// Fetch the complaint — also verify it belongs to this manager's market (security check)
$stmt = $pdo->prepare("SELECT * FROM complaints WHERE id = ? AND market_id = ? LIMIT 1");
$stmt->execute([$id, $_SESSION['market_id']]);
$complaint = $stmt->fetch();

if (!$complaint) {
    die("Complaint not found or access denied.");
    // die() stops execution — in production, redirect to an error page instead
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $response = htmlspecialchars($_POST['response'] ?? '');
    $status   = $_POST['status'] ?? $complaint['status'];

    $stmt = $pdo->prepare("UPDATE complaints SET response = ?, status = ?, updated_at = NOW() WHERE id = ?");
    // UPDATE modifies an existing row; SET specifies which columns to change
    // NOW() is a MySQL function that returns the current date and time
    $stmt->execute([$response, $status, $id]);

    header('Location: list.php');
    exit;
}
?>

<div class="max-w-lg mx-auto bg-white rounded-2xl shadow p-8">
    <h1 class="text-2xl font-bold text-primary mb-2"><?= $t['respond'] ?></h1>
    <p class="text-gray-500 font-mono text-sm mb-6"><?= $complaint['ref_code'] ?></p>

    <!-- Show original complaint -->
    <div class="bg-gray-50 rounded-xl p-4 mb-6">
        <p class="font-semibold mb-1"><?= $t[$complaint['category']] ?></p>
        <p class="text-gray-700"><?= nl2br(htmlspecialchars($complaint['description'])) ?></p>
    </div>

    <form method="POST">
        <p class="font-semibold text-gray-600 mb-2">Status</p>
        <select name="status"
                class="w-full border border-gray-300 rounded-lg px-3 py-2 mb-4 focus:outline-none focus:ring-2 focus:ring-primary">
            <option value="pending"   <?= $complaint['status'] === 'pending'   ? 'selected' : '' ?>><?= $t['status_pending'] ?></option>
            <option value="in_review" <?= $complaint['status'] === 'in_review' ? 'selected' : '' ?>><?= $t['status_in_review'] ?></option>
            <option value="resolved"  <?= $complaint['status'] === 'resolved'  ? 'selected' : '' ?>><?= $t['status_resolved'] ?></option>
        </select>

        <p class="font-semibold text-gray-600 mb-2"><?= $t['respond'] ?></p>
        <textarea name="response" rows="5"
                  class="w-full border border-gray-300 rounded-lg px-3 py-2 mb-6 focus:outline-none focus:ring-2 focus:ring-primary"
                  ><?= htmlspecialchars($complaint['response'] ?? '') ?></textarea>

        <div class="flex gap-3">
            <button type="submit"
                    class="flex-1 bg-primary text-white py-3 rounded-xl font-semibold hover:bg-green-700 transition">
                <?= $t['save'] ?>
            </button>
            <a href="list.php"
               class="flex-1 text-center border border-gray-300 py-3 rounded-xl text-gray-600 hover:bg-gray-50 transition">
                <?= $t['cancel'] ?>
            </a>
        </div>
    </form>
</div>

<?php require_once '../../templates/footer.php'; ?>
```

- 🟡 Verify Phase 6: Log in as manager → see the seller's complaint in the list → respond to it → verify status changes → log back in as seller and track the complaint to confirm the response appears

---

## ✅ Phase 7 — Announcements & Community Support

> Build the announcement broadcast module and the community event reporting module.
> Both follow the exact same pattern as complaints — one form to submit, one list to view.

### Step 7.1 — 📁 `modules/announcements/create.php` *(manager only)*
### Step 7.2 — 📁 `modules/announcements/list.php` *(all users)*
### Step 7.3 — 📁 `modules/community/report.php` *(sellers)*
### Step 7.4 — 📁 `modules/community/list.php` *(all users in the market)*

> These four files follow the **identical structure** to the complaint files above:
> - `create.php` / `report.php` = POST form → INSERT into database → success message
> - `list.php` = SELECT from database WHERE market_id = session → display in table or cards
>
> The AI should build them by copying the complaint pattern and adjusting:
> - Table name (`announcements` or `community_reports`)
> - Form fields (title + body for announcements; event_type + person_name + description for community)
> - Access control (`manager_only()` for announcement creation; open to all sellers for community reports)

---

## ✅ Phase 8 — Suggestions Module

> Same pattern as Phase 7. Two files:
> - `modules/suggestions/submit.php` — seller submits title + description → INSERT
> - `modules/suggestions/list.php` — manager sees all suggestions, can approve/reject via a status UPDATE

---

## ✅ Phase 9 — SMS Integration (Textbelt → Vonage)

> **Goal:** Extend the complaint system to accept complaints sent via SMS.
> Build this only after Phases 1–8 are fully working — SMS is an extension, not the foundation.

### Step 9.1 — 📁 `integrations/sms_send.php`
Implement the `sendSMS()` function using Textbelt (for development) as documented in `README.md` — API Integrations section.

### Step 9.2 — 📁 `integrations/sms_receive.php`
Implement the inbound webhook (a URL that the SMS provider calls when a message is received) as documented in `README.md`.

### Step 9.3 — Connect status changes to SMS notifications
In `modules/complaints/respond.php`, after the UPDATE query, call `sendSMS($sellerPhone, $message)` to notify the seller when their complaint status changes.

---

## ✅ Phase 10 — Gmail Integration

> **Goal:** The system reads a dedicated Gmail inbox and converts matching emails into complaints.
> Implement as a cron job (an automated task that runs on a schedule — e.g., every 5 minutes).

### Step 10.1 — Install the Google API PHP client
```bash
composer require google/apiclient
```

### Step 10.2 — 📁 `integrations/gmail_fetch.php`
Implement the Gmail reading and parsing logic as documented in `README.md` — API Integrations section.

### Step 10.3 — Schedule the cron job
```bash
# On Linux, add this line to the crontab (cron table — the scheduler config file)
# crontab -e opens the editor; add the line below:
*/5 * * * * php /opt/lampp/htdocs/voxmarche/integrations/gmail_fetch.php
# This runs gmail_fetch.php every 5 minutes automatically
```

---

## ✅ Phase 11 — Analytics Dashboard

> **Goal:** Manager sees visual charts summarising complaint data for their market.
> Use Chart.js (a free JavaScript charting library) loaded via CDN.

### Suggested charts:
- **Pie chart:** Complaints by category
- **Bar chart:** Complaints by month
- **Summary cards:** Total complaints / Pending count / Resolved count / Average resolution time

### Implementation hint:
```php
// Fetch aggregated data using SQL GROUP BY
$stmt = $pdo->prepare("
    SELECT category, COUNT(*) as total
    FROM complaints
    WHERE market_id = ?
    GROUP BY category
");
$stmt->execute([$_SESSION['market_id']]);
$chartData = $stmt->fetchAll();

// Convert PHP array to JSON (JavaScript Object Notation) for Chart.js to consume
$chartJson = json_encode($chartData);
// json_encode() converts a PHP array into a JSON string that JavaScript can read
```

---

## ✅ Phase 12 — Final Polish & Testing

### Checklist before deployment:

- [ ] All pages tested in both English and French — no hardcoded text remaining
- [ ] All forms validated on both client side (JavaScript) and server side (PHP)
- [ ] All database queries use `WHERE market_id = $_SESSION['market_id']` — no data leaks between markets
- [ ] Passwords are hashed — verify in phpMyAdmin that no plain text passwords exist
- [ ] Error pages for 404 (page not found) and 403 (access forbidden) are created
- [ ] All pages are tested on mobile screen sizes using browser DevTools (F12 → Toggle device toolbar)
- [ ] UAT (User Acceptance Testing) conducted with at least 2 real sellers and 1 manager from a pilot market
- [ ] `test.php` and any other temporary debug files are deleted before going live

---

## 📋 Quick Reference — Build Order Summary

| Phase | What to build | Depends on |
|---|---|---|
| 1 | XAMPP + Database tables | Nothing |
| 2 | Folder structure + `db.php` | Phase 1 |
| 3 | Language files + `lang.php` | Phase 2 |
| 4 | Header, footer, auth (register/login/logout/guard) | Phase 3 |
| 5 | Complaint submit + track (seller) | Phase 4 |
| 6 | Complaint list + respond (manager) | Phase 5 |
| 7 | Announcements + community modules | Phase 6 |
| 8 | Suggestions module | Phase 6 |
| 9 | SMS integration | Phase 5, 6 |
| 10 | Gmail integration | Phase 5 |
| 11 | Analytics dashboard | Phase 6, 7, 8 |
| 12 | Testing + polish | All phases |

---

*This implementation guide is a companion document to `README.md`. Always read both together.*
