-- Migration: Add viewed_at column and rename read_at to smiled_at
-- Run this on existing databases to update the schema

-- Step 1: Add viewed_at column (if it doesn't exist)
ALTER TABLE messages ADD COLUMN viewed_at DATETIME;

-- Step 2: Add smiled_at column (if it doesn't exist)
ALTER TABLE messages ADD COLUMN smiled_at DATETIME;

-- Step 3: Copy read_at values to smiled_at
UPDATE messages SET smiled_at = read_at WHERE read_at IS NOT NULL;

-- Step 4: Create new indexes
CREATE INDEX IF NOT EXISTS idx_messages_viewed_at ON messages (viewed_at);
CREATE INDEX IF NOT EXISTS idx_messages_smiled_at ON messages (smiled_at);

-- Note: The old read_at column will remain but won't be used
-- SQLite doesn't support DROP COLUMN in older versions, so we leave it
