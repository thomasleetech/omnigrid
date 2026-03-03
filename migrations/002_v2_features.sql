-- OmniGrid v2 Migration
-- Run this on your database

-- Add streaming columns to streams table
ALTER TABLE streams 
    ADD COLUMN IF NOT EXISTS stream_key VARCHAR(64) NULL,
    ADD COLUMN IF NOT EXISTS is_live TINYINT(1) DEFAULT 0;

-- Add admin adjustment to site_config
ALTER TABLE site_config 
    ADD COLUMN IF NOT EXISTS admin_adjustment DECIMAL(5,2) DEFAULT 0;

-- Add is_banned to users
ALTER TABLE users 
    ADD COLUMN IF NOT EXISTS is_banned TINYINT(1) DEFAULT 0;

-- Add total_earnings_cents to stream_metrics
ALTER TABLE stream_metrics 
    ADD COLUMN IF NOT EXISTS total_earnings_cents INT DEFAULT 0,
    ADD COLUMN IF NOT EXISTS avg_watch_seconds INT DEFAULT 0;

-- Ensure site_config has required columns
ALTER TABLE site_config
    ADD COLUMN IF NOT EXISTS rate_mode ENUM('smartgrid','override') DEFAULT 'smartgrid',
    ADD COLUMN IF NOT EXISTS base_ppm DECIMAL(10,6) DEFAULT 0.000500,
    ADD COLUMN IF NOT EXISTS smartgrid_aggressiveness DECIMAL(3,1) DEFAULT 1.5,
    ADD COLUMN IF NOT EXISTS hero_mode ENUM('auto','live','loop') DEFAULT 'auto',
    ADD COLUMN IF NOT EXISTS hero_loop_url VARCHAR(512) DEFAULT '';

-- Insert default config if not exists
INSERT IGNORE INTO site_config (id, rate_mode, base_ppm, smartgrid_aggressiveness, hero_mode) 
VALUES (1, 'smartgrid', 0.0005, 1.5, 'auto');

-- Create user_events table for analytics
CREATE TABLE IF NOT EXISTS user_events (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT,
    ip VARCHAR(45),
    event_type VARCHAR(50),
    payload JSON,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_user (user_id),
    INDEX idx_created (created_at)
);
