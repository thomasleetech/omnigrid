-- OmniGrid Streaming Migration
-- Run this to add live streaming support

ALTER TABLE streams 
    ADD COLUMN stream_key VARCHAR(64) NULL AFTER thumb_url,
    ADD COLUMN playback_url VARCHAR(512) NULL AFTER stream_key,
    ADD COLUMN ingest_url VARCHAR(512) NULL AFTER playback_url,
    ADD COLUMN provider ENUM('cloudflare','mux','rtmp') DEFAULT 'rtmp' AFTER ingest_url,
    ADD COLUMN is_live TINYINT(1) DEFAULT 0 AFTER provider;

-- Index for webhook lookups
CREATE INDEX idx_stream_key ON streams(stream_key);
