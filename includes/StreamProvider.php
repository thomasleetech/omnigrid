<?php
/**
 * OmniGrid Streaming Integration
 * 
 * Supports: Cloudflare Stream, Mux, or self-hosted RTMP
 * 
 * Add these columns to your streams table:
 * 
 * ALTER TABLE streams ADD COLUMN stream_key VARCHAR(64) NULL;
 * ALTER TABLE streams ADD COLUMN playback_url VARCHAR(512) NULL;
 * ALTER TABLE streams ADD COLUMN ingest_url VARCHAR(512) NULL;
 * ALTER TABLE streams ADD COLUMN provider ENUM('cloudflare','mux','rtmp') DEFAULT 'rtmp';
 * ALTER TABLE streams ADD COLUMN is_live TINYINT(1) DEFAULT 0;
 */

class StreamProvider {
    private $pdo;
    private $config;
    
    public function __construct($pdo, $config) {
        $this->pdo = $pdo;
        $this->config = $config;
    }
    
    /**
     * Generate a unique stream key for a user's stream
     */
    public function generateStreamKey($stream_id) {
        $key = 'og_' . bin2hex(random_bytes(16));
        
        $stmt = $this->pdo->prepare("UPDATE streams SET stream_key = ? WHERE id = ?");
        $stmt->execute([$key, $stream_id]);
        
        return $key;
    }
    
    /**
     * Cloudflare Stream - Create live input
     */
    public function createCloudflareStream($stream_id, $title) {
        $account_id = $this->config['cloudflare_account_id'];
        $api_token = $this->config['cloudflare_api_token'];
        
        $ch = curl_init("https://api.cloudflare.com/client/v4/accounts/{$account_id}/stream/live_inputs");
        curl_setopt_array($ch, [
            CURLOPT_POST => true,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_HTTPHEADER => [
                "Authorization: Bearer {$api_token}",
                "Content-Type: application/json"
            ],
            CURLOPT_POSTFIELDS => json_encode([
                'meta' => ['name' => $title],
                'recording' => ['mode' => 'automatic']
            ])
        ]);
        
        $response = json_decode(curl_exec($ch), true);
        curl_close($ch);
        
        if ($response['success']) {
            $result = $response['result'];
            
            $stmt = $this->pdo->prepare("
                UPDATE streams SET 
                    stream_key = ?,
                    ingest_url = ?,
                    playback_url = ?,
                    provider = 'cloudflare'
                WHERE id = ?
            ");
            $stmt->execute([
                $result['uid'],
                $result['rtmps']['url'] . $result['rtmps']['streamKey'],
                "https://customer-{$account_id}.cloudflarestream.com/{$result['uid']}/manifest/video.m3u8",
                $stream_id
            ]);
            
            return [
                'stream_key' => $result['rtmps']['streamKey'],
                'rtmp_url' => $result['rtmps']['url'],
                'rtmps_url' => $result['rtmps']['url'],
                'playback_url' => "https://customer-{$account_id}.cloudflarestream.com/{$result['uid']}/manifest/video.m3u8"
            ];
        }
        
        return false;
    }
    
    /**
     * Self-hosted RTMP - Just generate credentials
     */
    public function createRTMPStream($stream_id) {
        $stream_key = $this->generateStreamKey($stream_id);
        $rtmp_server = $this->config['rtmp_server'] ?? 'rtmp://your-server.com/live';
        
        $stmt = $this->pdo->prepare("
            UPDATE streams SET 
                ingest_url = ?,
                playback_url = ?,
                provider = 'rtmp'
            WHERE id = ?
        ");
        $stmt->execute([
            "{$rtmp_server}/{$stream_key}",
            str_replace('rtmp://', 'https://', $rtmp_server) . "/hls/{$stream_key}.m3u8",
            $stream_id
        ]);
        
        return [
            'stream_key' => $stream_key,
            'rtmp_url' => $rtmp_server,
            'playback_url' => str_replace('rtmp://', 'https://', $rtmp_server) . "/hls/{$stream_key}.m3u8"
        ];
    }
    
    /**
     * Webhook handler for stream status updates
     */
    public function handleWebhook($payload) {
        $stream_key = $payload['stream_key'] ?? $payload['uid'] ?? null;
        $event = $payload['event'] ?? $payload['type'] ?? '';
        
        if (!$stream_key) return false;
        
        $is_live = in_array($event, ['stream.connected', 'live_input.connected', 'stream_start']) ? 1 : 0;
        
        $stmt = $this->pdo->prepare("UPDATE streams SET is_live = ? WHERE stream_key = ?");
        $stmt->execute([$is_live, $stream_key]);
        
        return true;
    }
}
