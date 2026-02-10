-- Database schema for One Trillion Smiles
-- Mental model: Messages (Smiles) are the atomic unit, not pages

-- Senders table for users creating and sending smiles
CREATE TABLE IF NOT EXISTS senders (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    name TEXT NOT NULL,
    email TEXT UNIQUE NOT NULL,
    avatar TEXT NOT NULL,
    email_confirmed INTEGER DEFAULT 0,
    email_confirmation_token TEXT UNIQUE,
    dashboard_url TEXT UNIQUE NOT NULL,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    last_activity DATETIME DEFAULT CURRENT_TIMESTAMP
);

-- Messages table for individual smiles sent
CREATE TABLE IF NOT EXISTS messages (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    sender_id INTEGER NOT NULL,
    recipient_name TEXT NOT NULL,
    recipient_email TEXT NOT NULL,
    message TEXT NOT NULL,
    message_url TEXT UNIQUE NOT NULL,
    sent_at DATETIME,
    viewed_at DATETIME,
    smiled_at DATETIME,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (sender_id) REFERENCES senders (id) ON DELETE CASCADE
);

-- Email notifications tracking to prevent duplicate sends
CREATE TABLE IF NOT EXISTS email_notifications (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    sender_id INTEGER NOT NULL,
    recipient_email TEXT NOT NULL,
    notification_type TEXT NOT NULL,
    sent_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (sender_id) REFERENCES senders (id) ON DELETE CASCADE
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
CREATE INDEX IF NOT EXISTS idx_senders_email ON senders (email);
CREATE INDEX IF NOT EXISTS idx_senders_dashboard_url ON senders (dashboard_url);
CREATE INDEX IF NOT EXISTS idx_senders_confirmation_token ON senders (email_confirmation_token);
CREATE INDEX IF NOT EXISTS idx_senders_email_confirmed ON senders (email_confirmed);

CREATE INDEX IF NOT EXISTS idx_messages_sender_id ON messages (sender_id);
CREATE INDEX IF NOT EXISTS idx_messages_message_url ON messages (message_url);
CREATE INDEX IF NOT EXISTS idx_messages_recipient_email ON messages (recipient_email);
CREATE INDEX IF NOT EXISTS idx_messages_viewed_at ON messages (viewed_at);
CREATE INDEX IF NOT EXISTS idx_messages_smiled_at ON messages (smiled_at);
CREATE INDEX IF NOT EXISTS idx_messages_sent_at ON messages (sent_at);

CREATE INDEX IF NOT EXISTS idx_email_notifications_sender_id ON email_notifications (sender_id);
CREATE INDEX IF NOT EXISTS idx_email_notifications_recipient ON email_notifications (sender_id, recipient_email, notification_type);
