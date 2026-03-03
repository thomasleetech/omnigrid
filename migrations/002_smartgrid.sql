-- OmniGrid Database Schema
-- Run this migration to set up or update the database

-- Site configuration
CREATE TABLE IF NOT EXISTS site_config (
    id INT PRIMARY KEY DEFAULT 1,
    rate_mode ENUM('smartgrid', 'override') DEFAULT 'smartgrid',
    base_ppm DECIMAL(10,6) DEFAULT 0.000500,
    smartgrid_aggressiveness DECIMAL(3,1) DEFAULT 1.5,
    admin_adjustment INT DEFAULT 0,
    hero_mode ENUM('auto', 'live', 'loop') DEFAULT 'auto',
    hero_loop_url VARCHAR(512) DEFAULT '',
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

-- Insert default config if not exists
INSERT IGNORE INTO site_config (id) VALUES (1);

-- Users table updates
ALTER TABLE users 
    ADD COLUMN IF NOT EXISTS role ENUM('user', 'creator', 'admin') DEFAULT 'creator',
    ADD COLUMN IF NOT EXISTS is_banned TINYINT(1) DEFAULT 0,
    ADD COLUMN IF NOT EXISTS display_name VARCHAR(100) NULL;

-- Streams table updates  
ALTER TABLE streams
    ADD COLUMN IF NOT EXISTS stream_key VARCHAR(64) NULL,
    ADD COLUMN IF NOT EXISTS is_live TINYINT(1) DEFAULT 0,
    ADD COLUMN IF NOT EXISTS provider ENUM('webrtc', 'rtmp', 'cloudflare') DEFAULT 'webrtc';

-- Stream metrics updates
ALTER TABLE stream_metrics
    ADD COLUMN IF NOT EXISTS avg_watch_seconds INT DEFAULT 0,
    ADD COLUMN IF NOT EXISTS total_earnings_cents INT DEFAULT 0,
    ADD COLUMN IF NOT EXISTS last_calculated_at TIMESTAMP NULL;

-- Create indexes
CREATE INDEX IF NOT EXISTS idx_streams_live ON streams(is_live);
CREATE INDEX IF NOT EXISTS idx_streams_user ON streams(user_id);
CREATE INDEX IF NOT EXISTS idx_users_role ON users(role);

-- Sample data (optional, for testing)
-- INSERT INTO users (email, password_hash, display_name, role) VALUES 
--     ('admin@omnigrid.com', '$2y$10$...', 'Admin', 'admin'),
--     ('demo@omnigrid.com', '$2y$10$...', 'Demo Creator', 'creator');
