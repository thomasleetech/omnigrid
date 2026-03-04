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

        try {
            $this->pdo->prepare("
                UPDATE stream_metrics
                SET total_stream_minutes = total_stream_minutes + ?,
                    total_earnings_cents = total_earnings_cents + ?,
                    last_rate_cents = ?,
                    last_calculated_at = NOW()
                WHERE stream_id = ?
            ")->execute([
                $minutes,
                $earnings['earnings_cents'],
                $earnings['rate']['rate_cents_per_min'],
                $streamId
            ]);
        } catch (Exception $e) {
            // Columns may not exist yet - silently skip
        }
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
        $defaults = [
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

        try {
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
            return $stmt->fetch(PDO::FETCH_ASSOC) ?: $defaults;
        } catch (Exception $e) {
            // Columns may not exist yet - try minimal query
            try {
                $stmt = $this->pdo->prepare("SELECT smartgrid_multiplier FROM streams WHERE id = ?");
                $stmt->execute([$streamId]);
                $row = $stmt->fetch(PDO::FETCH_ASSOC);
                if ($row) {
                    $defaults['smartgrid_multiplier'] = $row['smartgrid_multiplier'] ?? 1.0;
                }
            } catch (Exception $e2) {
                // smartgrid_multiplier column may not exist either
            }
            return $defaults;
        }
    }

    private function getSiteConfig(): array {
        try {
            // Try single-row config format first (actual schema)
            $row = $this->pdo->query("SELECT * FROM site_config WHERE id = 1")->fetch(PDO::FETCH_ASSOC);
            if ($row) {
                return [
                    'payout_multiplier' => floatval($row['smartgrid_aggressiveness'] ?? 1.0),
                    'rate_mode' => $row['rate_mode'] ?? 'smartgrid',
                    'base_ppm' => floatval($row['base_ppm'] ?? 0.0005),
                    'hero_mode' => $row['hero_mode'] ?? 'auto',
                    'hero_loop_url' => $row['hero_loop_url'] ?? ''
                ];
            }
        } catch (Exception $e) {
            // Table may not exist
        }
        return ['payout_multiplier' => 1.0, 'rate_mode' => 'smartgrid', 'base_ppm' => 0.0005];
    }

    /**
     * Admin: Set global payout multiplier
     */
    public function setPayoutMultiplier(float $multiplier): void {
        try {
            $this->pdo->prepare("
                UPDATE site_config SET smartgrid_aggressiveness = ? WHERE id = 1
            ")->execute([$multiplier]);
        } catch (Exception $e) {
            // Table/column may not exist
        }
    }

    /**
     * Get platform-wide stats
     */
    public function getPlatformStats(): array {
        $default = [
            'streams' => ['total' => 0, 'live' => 0],
            'creators' => 0,
            'engagement' => ['total_views' => 0, 'total_tips' => 0, 'total_subs' => 0],
            'financials' => ['total_payouts' => 0, 'avg_rate_per_min' => 0]
        ];

        try {
            // Get stream counts from streams table only (always safe)
            $counts = $this->pdo->query("
                SELECT
                    COUNT(DISTINCT id) as total_streams,
                    COUNT(DISTINCT CASE WHEN is_live = 1 THEN id END) as live_streams,
                    COUNT(DISTINCT user_id) as total_creators
                FROM streams
                WHERE is_active = 1
            ")->fetch(PDO::FETCH_ASSOC);

            $default['streams']['total'] = (int)($counts['total_streams'] ?? 0);
            $default['streams']['live'] = (int)($counts['live_streams'] ?? 0);
            $default['creators'] = (int)($counts['total_creators'] ?? 0);
        } catch (Exception $e) {
            // is_active column may not exist
            try {
                $counts = $this->pdo->query("
                    SELECT COUNT(*) as total_streams, COUNT(DISTINCT user_id) as total_creators
                    FROM streams
                ")->fetch(PDO::FETCH_ASSOC);
                $default['streams']['total'] = (int)($counts['total_streams'] ?? 0);
                $default['creators'] = (int)($counts['total_creators'] ?? 0);
            } catch (Exception $e2) {}
        }

        // Try to get metrics from stream_metrics (may fail if columns missing)
        try {
            $metrics = $this->pdo->query("
                SELECT
                    COALESCE(SUM(views), 0) as total_views,
                    COALESCE(SUM(tips_cents), 0) as total_tips_cents,
                    COALESCE(SUM(subs_count), 0) as total_subs,
                    COALESCE(SUM(total_earnings_cents), 0) as total_payouts_cents,
                    COALESCE(AVG(last_rate_cents), 0) as avg_rate
                FROM stream_metrics
            ")->fetch(PDO::FETCH_ASSOC);

            $default['engagement']['total_views'] = (int)($metrics['total_views'] ?? 0);
            $default['engagement']['total_tips'] = round(($metrics['total_tips_cents'] ?? 0) / 100, 2);
            $default['engagement']['total_subs'] = (int)($metrics['total_subs'] ?? 0);
            $default['financials']['total_payouts'] = round(($metrics['total_payouts_cents'] ?? 0) / 100, 2);
            $default['financials']['avg_rate_per_min'] = round(($metrics['avg_rate'] ?? 0) / 100, 4);
        } catch (Exception $e) {
            // Columns don't exist yet - try just total_earnings_cents (from migration 002)
            try {
                $earnings = $this->pdo->query("
                    SELECT COALESCE(SUM(total_earnings_cents), 0) as total
                    FROM stream_metrics
                ")->fetchColumn();
                $default['financials']['total_payouts'] = round($earnings / 100, 2);
            } catch (Exception $e2) {}
        }

        return $default;
    }

    /**
     * Get all streams with earnings data for admin
     */
    public function getAllStreamsWithEarnings(): array {
        try {
            $stmt = $this->pdo->query("
                SELECT s.*, u.email, u.display_name,
                       COALESCE(m.views, 0) as views,
                       COALESCE(m.tips_cents, 0) as tips_cents,
                       COALESCE(m.subs_count, 0) as subs_count,
                       COALESCE(m.total_earnings_cents, 0) as total_earnings_cents,
                       COALESCE(m.last_rate_cents, 0) as last_rate_cents
                FROM streams s
                JOIN users u ON s.user_id = u.id
                LEFT JOIN stream_metrics m ON s.id = m.stream_id
                WHERE s.is_active = 1
                ORDER BY m.views DESC
            ");
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (Exception $e) {
            // Fallback: query without metrics columns
            try {
                $stmt = $this->pdo->query("
                    SELECT s.*, u.email, u.display_name,
                           0 as views, 0 as tips_cents, 0 as subs_count,
                           COALESCE(m.total_earnings_cents, 0) as total_earnings_cents,
                           0 as last_rate_cents
                    FROM streams s
                    JOIN users u ON s.user_id = u.id
                    LEFT JOIN stream_metrics m ON s.id = m.stream_id
                    WHERE s.is_active = 1
                    ORDER BY s.id DESC
                ");
                return $stmt->fetchAll(PDO::FETCH_ASSOC);
            } catch (Exception $e2) {
                // Even more minimal fallback
                try {
                    $stmt = $this->pdo->query("
                        SELECT s.*, u.email, u.display_name,
                               0 as views, 0 as tips_cents, 0 as subs_count,
                               0 as total_earnings_cents, 0 as last_rate_cents
                        FROM streams s
                        JOIN users u ON s.user_id = u.id
                        ORDER BY s.id DESC
                    ");
                    return $stmt->fetchAll(PDO::FETCH_ASSOC);
                } catch (Exception $e3) {
                    return [];
                }
            }
        }
    }
}
