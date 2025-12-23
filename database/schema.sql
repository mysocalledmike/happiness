-- Database schema for multi-user happiness site

-- Waitlist table for email signups
CREATE TABLE IF NOT EXISTS waitlist (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    email TEXT UNIQUE NOT NULL,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP
);

-- Senders table for people creating happiness pages
CREATE TABLE IF NOT EXISTS senders (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    email TEXT UNIQUE NOT NULL,
    status TEXT NOT NULL CHECK (status IN ('waitlist', 'inactive', 'active')) DEFAULT 'waitlist',
    slug TEXT UNIQUE,
    overall_message TEXT,
    theme TEXT,
    not_found_message TEXT,
    creation_url TEXT UNIQUE,
    smile_count INTEGER DEFAULT 0,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    activated_at DATETIME,
    last_activity DATETIME DEFAULT CURRENT_TIMESTAMP
);

-- Messages table for happiness messages
CREATE TABLE IF NOT EXISTS messages (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    sender_id INTEGER NOT NULL,
    recipient_email TEXT NOT NULL,
    recipient_name TEXT,
    message TEXT,
    emotion TEXT,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (sender_id) REFERENCES senders (id) ON DELETE CASCADE,
    UNIQUE(sender_id, recipient_email)
);

-- Email notifications tracking table
CREATE TABLE IF NOT EXISTS email_notifications (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    sender_id INTEGER NOT NULL,
    recipient_email TEXT NOT NULL,
    sent_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (sender_id) REFERENCES senders (id) ON DELETE CASCADE,
    UNIQUE(sender_id, recipient_email)
);

-- Global stats table
CREATE TABLE IF NOT EXISTS stats (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    smile_count INTEGER DEFAULT 0,
    last_updated DATETIME DEFAULT CURRENT_TIMESTAMP
);

-- Insert initial stats record
INSERT OR IGNORE INTO stats (id, smile_count) VALUES (1, 0);

-- Indexes for performance
CREATE INDEX IF NOT EXISTS idx_senders_status ON senders (status);
CREATE INDEX IF NOT EXISTS idx_senders_slug ON senders (slug);
CREATE INDEX IF NOT EXISTS idx_senders_creation_url ON senders (creation_url);
CREATE INDEX IF NOT EXISTS idx_messages_sender_email ON messages (sender_id, recipient_email);
CREATE INDEX IF NOT EXISTS idx_waitlist_email ON waitlist (email);
CREATE INDEX IF NOT EXISTS idx_email_notifications_sender_email ON email_notifications (sender_id, recipient_email);