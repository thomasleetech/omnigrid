-- OmniGrid Migration 003: Add all SmartGrid metric columns
-- Run this to add columns needed by the SmartGrid engine

-- Add missing columns to stream_metrics
ALTER TABLE stream_metrics
    ADD COLUMN IF NOT EXISTS views INT DEFAULT 0,
    ADD COLUMN IF NOT EXISTS peak_viewers INT DEFAULT 0,
    ADD COLUMN IF NOT EXISTS tips_cents INT DEFAULT 0,
    ADD COLUMN IF NOT EXISTS subs_count INT DEFAULT 0,
    ADD COLUMN IF NOT EXISTS new_subs_today INT DEFAULT 0,
    ADD COLUMN IF NOT EXISTS chats_count INT DEFAULT 0,
    ADD COLUMN IF NOT EXISTS avg_watch_time DECIMAL(10,2) DEFAULT 0,
    ADD COLUMN IF NOT EXISTS total_watch_time INT DEFAULT 0,
    ADD COLUMN IF NOT EXISTS uptime_percent DECIMAL(5,2) DEFAULT 99.00,
    ADD COLUMN IF NOT EXISTS bitrate_avg INT DEFAULT 2500,
    ADD COLUMN IF NOT EXISTS last_rate_cents DECIMAL(10,3) DEFAULT 0,
    ADD COLUMN IF NOT EXISTS total_stream_minutes INT DEFAULT 0;

-- Add smartgrid_multiplier to streams if missing
ALTER TABLE streams
    ADD COLUMN IF NOT EXISTS smartgrid_multiplier DECIMAL(5,2) DEFAULT 1.00;

-- Add display_name to users if missing
ALTER TABLE users
    ADD COLUMN IF NOT EXISTS display_name VARCHAR(100) NULL;

-- Create indexes for SmartGrid queries
CREATE INDEX IF NOT EXISTS idx_metrics_views ON stream_metrics(views);
CREATE INDEX IF NOT EXISTS idx_metrics_stream ON stream_metrics(stream_id);
