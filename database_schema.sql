-- ============================================================
-- PlaceParole — Full Database Schema
-- Run this entire block once in phpMyAdmin to create all tables
-- ============================================================

-- Table 1: markets
-- This is created FIRST because all other tables reference it
CREATE TABLE IF NOT EXISTS markets (
    id          INT AUTO_INCREMENT PRIMARY KEY,
    name        VARCHAR(150) NOT NULL,           -- e.g. "Marché Mokolo"
    location    VARCHAR(200),                    -- e.g. "Yaoundé, Centre Region"
    created_at  TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Table 2: users
-- Both sellers and managers are stored here, distinguished by the 'role' column
CREATE TABLE IF NOT EXISTS users (
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
CREATE TABLE IF NOT EXISTS complaints (
    id          INT AUTO_INCREMENT PRIMARY KEY,
    market_id   INT NOT NULL,
    seller_id   INT NOT NULL,
    ref_code    VARCHAR(20) UNIQUE NOT NULL,     -- e.g. MKT-2024-00123
    category    VARCHAR(100),
    description TEXT,
    channel     ENUM('web', 'sms', 'gmail') DEFAULT 'web',
    status      ENUM('pending', 'in_review', 'resolved') DEFAULT 'pending',
    response    TEXT,
    photo_path  VARCHAR(255) NULL,                -- Path to attached complaint photo
    created_at  TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at  TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (seller_id)  REFERENCES users(id),
    FOREIGN KEY (market_id)  REFERENCES markets(id)
);

-- Table 4: suggestions
CREATE TABLE IF NOT EXISTS suggestions (
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
CREATE TABLE IF NOT EXISTS community_reports (
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
CREATE TABLE IF NOT EXISTS announcements (
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
