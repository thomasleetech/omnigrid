<?php
/**
 * SmartGrid Adaptive Earnings Engine
 * Calculates dynamic payout rates based on engagement metrics
 */

class SmartGrid {
    private $pdo;
    
    // Base rates (cents per minute)
    const BASE_RATE = 0.50;           // $0.005 per minute base
    const MAX_RATE = 25.0;            // $0.25 per minute cap
    const MIN_RATE = 0.10;            // $0.001 per minute floor
    
    // Weight factors
    const WEIGHT_VIEWERS = 0.35;      // Current viewer count
    const WEIGHT_RETENTION = 0.25;    // How long viewers stay
    const WEIGHT_ENGAGEMENT = 0.20;   // Tips, chats, reactions
    const WEIGHT_GROWTH = 0.15;       // Subscriber growth rate
    const WEIGHT_QUALITY = 0.05;      // Stream quality/uptime
    
    public function __construct($pdo) {
        $this->pdo = $pdo;
    }
    
    /**
     * Calculate current payout rate for a stream
     */
    public function calculateRate(int $streamId): array {
        $metrics = $this->getStreamMetrics($streamId);
        $config = $this->getSiteConfig();
        
        // Calculate component scores (0-100)
        $viewerScore = $this->scoreViewers($metrics['current_viewers'], $metrics['peak_viewers']);
        $retentionScore = $this->scoreRetention($metrics['avg_watch_time'], $metrics['total_watch_time']);
        $engagementScore = $this->scoreEngagement($metrics['tips_today'], $metrics['chats_today'], $metrics['current_viewers']);
        $growthScore = $this->scoreGrowth($metrics['subs_today'], $metrics['subs_count']);
        $qualityScore = $this->scoreQuality($metrics['uptime_percent'], $metrics['bitrate_avg']);
        
        // Weighted composite score
        $composite = 
            ($viewerScore * self::WEIGHT_VIEWERS) +
            ($retentionScore * self::WEIGHT_RETENTION) +
            ($engagementScore * self::WEIGHT_ENGAGEMENT) +
            ($growthScore * self::WEIGHT_GROWTH) +
            ($qualityScore * self::WEIGHT_QUALITY);
        
        // Convert score to rate using exponential curve
        $rawRate = self::BASE_RATE * pow(2, $composite / 25);
        
        // Apply site-wide multiplier from admin
        $adminMultiplier = floatval($config['payout_multiplier'] ?? 1.0);
        $adjustedRate = $rawRate * $adminMultiplier;
        
        // Apply stream-specific multiplier if set
        $streamMultiplier = floatval($metrics['smartgrid_multiplier'] ?? 1.0);
        $finalRate = $adjustedRate * $streamMultiplier;
        
        // Clamp to bounds
        $finalRate = max(self::MIN_RATE, min(self::MAX_RATE, $finalRate));
        
        return [
            'rate_cents_per_min' => round($finalRate, 3),
            'rate_dollars_per_hour' => round($finalRate * 60 / 100, 2),
            'composite_score' => round($composite, 1),
            'breakdown' => [
                'viewers' => ['score' => round($viewerScore, 1), 'weight' => self::WEIGHT_VIEWERS * 100 . '%'],
                'retention' => ['score' => round($retentionScore, 1), 'weight' => self::WEIGHT_RETENTION * 100 . '%'],
                'engagement' => ['score' => round($engagementScore, 1), 'weight' => self::WEIGHT_ENGAGEMENT * 100 . '%'],
                'growth' => ['score' => round($growthScore, 1), 'weight' => self::WEIGHT_GROWTH * 100 . '%'],
                'quality' => ['score' => round($qualityScore, 1), 'weight' => self::WEIGHT_QUALITY * 100 . '%']
            ],
            'multipliers' => [
                'admin' => $adminMultiplier,
                'stream' => $streamMultiplier,
                'effective' => round($adminMultiplier * $streamMultiplier, 2)
            ],
            'raw_rate' => round($rawRate, 3),
            'timestamp' => date('c')
        ];
    }
    
    /**
     * Calculate earnings for a time period
     */
    public function calculateEarnings(int $streamId, int $minutes): array {
        $rate = $this->calculateRate($streamId);
        $earnings = $rate['rate_cents_per_min'] * $minutes;
        
        return [
            'minutes' => $minutes,
            'rate' => $rate,
            'earnings_cents' => round($earnings, 2),
            'earnings_dollars' => round($earnings / 100, 2)
        ];
    }
    
    /**
     * Get projected daily/monthly earnings
     */
    public function getProjections(int $streamId, int $avgDailyMinutes = 120): array {
        $rate = $this->calculateRate($streamId);
        
        return [
            'current_rate' => $rate,
            'daily' => round($rate['rate_cents_per_min'] * $avgDailyMinutes / 100, 2),
            'weekly' => round($rate['rate_cents_per_min'] * $avgDailyMinutes * 7 / 100, 2),
            'monthly' => round($rate['rate_cents_per_min'] * $avgDailyMinutes * 30 / 100, 2),
            'yearly' => round($rate['rate_cents_per_min'] * $avgDailyMinutes * 365 / 100, 2),
            'assumptions' => [
                'avg_daily_stream_minutes' => $avgDailyMinutes
            ]
        ];
    }
    
    /**
     * Record streaming time and earnings
     */
    public function recordStreamTime(int $streamId, int $minutes): void {
        $earnings = $this->calculateEarnings($streamId, $minutes);
        
        $this->pdo->prepare("
            UPDATE stream_metrics 
            SET total_stream_minutes = total_stream_minutes + ?,
                total_earnings_cents = total_earnings_cents + ?,
                last_rate_cents = ?
            WHERE stream_id = ?
        ")->execute([
            $minutes,
            $earnings['earnings_cents'],
            $earnings['rate']['rate_cents_per_min'],
            $streamId
        ]);
    }
    
    // --- Scoring Functions ---
    
    private function scoreViewers(int $current, int $peak): float {
        if ($current == 0) return 0;
        $base = min(100, log($current + 1, 2) * 15);
        $retention = $peak > 0 ? ($current / $peak) : 0;
        return $base * (0.7 + 0.3 * $retention);
    }
    
    private function scoreRetention(float $avgWatchTime, int $totalWatchTime): float {
        $avgScore = min(50, $avgWatchTime * 5);
        $totalScore = min(50, log($totalWatchTime + 1, 10) * 15);
        return $avgScore + $totalScore;
    }
    
    private function scoreEngagement(int $tips, int $chats, int $viewers): float {
        if ($viewers == 0) return 0;
        $tipScore = min(50, $tips * 2);
        $chatRatio = $chats / max(1, $viewers);
        $chatScore = min(50, $chatRatio * 25);
        return $tipScore + $chatScore;
    }
    
    private function scoreGrowth(int $newSubs, int $totalSubs): float {
        if ($totalSubs == 0) return $newSubs > 0 ? 50 : 0;
        $growthRate = $newSubs / $totalSubs;
        return min(100, $growthRate * 500 + ($newSubs * 5));
    }
    
    private function scoreQuality(float $uptime, int $bitrate): float {
        $uptimeScore = $uptime >= 99 ? 50 : $uptime / 2;
        $bitrateScore = min(50, $bitrate / 60);
        return $uptimeScore + $bitrateScore;
    }
    
    // --- Data Access ---
    
    private function getStreamMetrics(int $streamId): array {
        $stmt = $this->pdo->prepare("
            SELECT s.smartgrid_multiplier,
                   COALESCE(m.views, 0) as current_viewers,
                   COALESCE(m.peak_viewers, 0) as peak_viewers,
                   COALESCE(m.avg_watch_time, 0) as avg_watch_time,
                   COALESCE(m.total_watch_time, 0) as total_watch_time,
                   COALESCE(m.tips_cents, 0) as tips_today,
                   COALESCE(m.chats_count, 0) as chats_today,
                   COALESCE(m.subs_count, 0) as subs_count,
                   COALESCE(m.new_subs_today, 0) as subs_today,
                   COALESCE(m.uptime_percent, 99) as uptime_percent,
                   COALESCE(m.bitrate_avg, 2500) as bitrate_avg
            FROM streams s
            LEFT JOIN stream_metrics m ON s.id = m.stream_id
            WHERE s.id = ?
        ");
        $stmt->execute([$streamId]);
        return $stmt->fetch(PDO::FETCH_ASSOC) ?: [
            'smartgrid_multiplier' => 1.0,
            'current_viewers' => 0,
            'peak_viewers' => 0,
            'avg_watch_time' => 0,
            'total_watch_time' => 0,
            'tips_today' => 0,
            'chats_today' => 0,
            'subs_count' => 0,
            'subs_today' => 0,
            'uptime_percent' => 99,
            'bitrate_avg' => 2500
        ];
    }
    
    private function getSiteConfig(): array {
        try {
            $stmt = $this->pdo->query("SELECT config_key, config_value FROM site_config");
            $config = [];
            while ($row = $stmt->fetch()) {
                $config[$row['config_key']] = $row['config_value'];
            }
            return $config;
        } catch (Exception $e) {
            return ['payout_multiplier' => 1.0];
        }
    }
    
    /**
     * Admin: Set global payout multiplier
     */
    public function setPayoutMultiplier(float $multiplier): void {
        $this->pdo->prepare("
            INSERT INTO site_config (config_key, config_value) 
            VALUES ('payout_multiplier', ?)
            ON DUPLICATE KEY UPDATE config_value = ?
        ")->execute([$multiplier, $multiplier]);
    }
    
    /**
     * Get platform-wide stats
     */
    public function getPlatformStats(): array {
        $stats = $this->pdo->query("
            SELECT 
                COUNT(DISTINCT s.id) as total_streams,
                COUNT(DISTINCT CASE WHEN s.is_live = 1 THEN s.id END) as live_streams,
                COUNT(DISTINCT s.user_id) as total_creators,
                SUM(m.views) as total_views,
                SUM(m.tips_cents) as total_tips_cents,
                SUM(m.subs_count) as total_subs,
                SUM(m.total_earnings_cents) as total_payouts_cents,
                AVG(m.last_rate_cents) as avg_rate
            FROM streams s
            LEFT JOIN stream_metrics m ON s.id = m.stream_id
            WHERE s.is_active = 1
        ")->fetch(PDO::FETCH_ASSOC);
        
        return [
            'streams' => [
                'total' => (int)($stats['total_streams'] ?? 0),
                'live' => (int)($stats['live_streams'] ?? 0)
            ],
            'creators' => (int)($stats['total_creators'] ?? 0),
            'engagement' => [
                'total_views' => (int)($stats['total_views'] ?? 0),
                'total_tips' => round(($stats['total_tips_cents'] ?? 0) / 100, 2),
                'total_subs' => (int)($stats['total_subs'] ?? 0)
            ],
            'financials' => [
                'total_payouts' => round(($stats['total_payouts_cents'] ?? 0) / 100, 2),
                'avg_rate_per_min' => round(($stats['avg_rate'] ?? 0) / 100, 4)
            ]
        ];
    }
    
    /**
     * Get all streams with earnings data for admin
     */
    public function getAllStreamsWithEarnings(): array {
        $stmt = $this->pdo->query("
            SELECT s.*, u.email, u.display_name,
                   m.views, m.tips_cents, m.subs_count, m.total_earnings_cents, m.last_rate_cents
            FROM streams s
            JOIN users u ON s.user_id = u.id
            LEFT JOIN stream_metrics m ON s.id = m.stream_id
            WHERE s.is_active = 1
            ORDER BY m.views DESC
        ");
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
}
