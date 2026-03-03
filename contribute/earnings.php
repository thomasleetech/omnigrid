<?php
session_start();
if (empty($_SESSION['user_id'])) { header('Location: ../login.php'); exit; }
require_once '../includes/db.php';
require_once '../includes/SmartGrid.php';

$uid = $_SESSION['user_id'];
$stmt = $pdo->prepare("SELECT * FROM users WHERE id = ?");
$stmt->execute([$uid]);
$user = $stmt->fetch();

$smartGrid = new SmartGrid($pdo);

// Get all user streams with metrics
$stmt = $pdo->prepare("
    SELECT s.*,
           COALESCE(m.views, 0) as views,
           COALESCE(m.peak_viewers, 0) as peak_viewers,
           COALESCE(m.subs_count, 0) as subs_count,
           COALESCE(m.tips_cents, 0) as tips_cents,
           COALESCE(m.chats_count, 0) as chats_count,
           COALESCE(m.avg_watch_time, 0) as avg_watch_time,
           COALESCE(m.total_watch_time, 0) as total_watch_time,
           COALESCE(m.uptime_percent, 99) as uptime_percent,
           COALESCE(m.bitrate_avg, 2500) as bitrate_avg,
           COALESCE(m.new_subs_today, 0) as new_subs_today,
           COALESCE(m.total_earnings_cents, 0) as total_earnings_cents,
           COALESCE(m.last_rate_cents, 0) as last_rate_cents
    FROM streams s
    LEFT JOIN stream_metrics m ON s.id = m.stream_id
    WHERE s.user_id = ? AND s.is_active = 1
    ORDER BY m.total_earnings_cents DESC
");
$stmt->execute([$uid]);
$streams = $stmt->fetchAll();

// Calculate rates for each stream
$streamData = [];
$totalEarnings = 0;
$totalViews = 0;
$avgRate = 0;
$rateCount = 0;

foreach ($streams as $s) {
    $rate = $smartGrid->calculateRate($s['id']);
    $proj = $smartGrid->getProjections($s['id']);
    $streamData[] = [
        'stream' => $s,
        'rate' => $rate,
        'projections' => $proj
    ];
    $totalEarnings += $s['total_earnings_cents'];
    $totalViews += $s['views'];
    $avgRate += $rate['rate_cents_per_min'];
    $rateCount++;
}
$avgRate = $rateCount > 0 ? $avgRate / $rateCount : 0;
$avgHourly = $avgRate * 60 / 100;

// Aggregate projections
$totalDaily = 0;
$totalMonthly = 0;
foreach ($streamData as $sd) {
    $totalDaily += $sd['projections']['daily'];
    $totalMonthly += $sd['projections']['monthly'];
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width,initial-scale=1.0">
<title>Earnings — OmniGrid</title>
<link rel="preconnect" href="https://fonts.googleapis.com">
<link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&family=JetBrains+Mono:wght@500;700&display=swap" rel="stylesheet">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
<style>
:root {
    --bg: #08080d;
    --surface: #111118;
    --surface-2: #1a1a24;
    --border: #25253a;
    --border-light: #2f2f4a;
    --text: #f0f0f5;
    --text-secondary: #b0b0c8;
    --muted: #6b6b85;
    --primary: #6366f1;
    --primary-light: #818cf8;
    --primary-glow: rgba(99, 102, 241, 0.15);
    --success: #10b981;
    --success-glow: rgba(16, 185, 129, 0.12);
    --warning: #f59e0b;
    --warning-glow: rgba(245, 158, 11, 0.12);
    --danger: #ef4444;
    --danger-glow: rgba(239, 68, 68, 0.12);
    --gradient-primary: linear-gradient(135deg, #6366f1, #a855f7);
    --radius: 12px;
    --radius-sm: 8px;
    --radius-pill: 100px;
    --shadow: 0 4px 24px rgba(0, 0, 0, 0.3);
    --transition: 0.25s cubic-bezier(0.4, 0, 0.2, 1);
}
* { box-sizing: border-box; margin: 0; padding: 0; }
body {
    font-family: 'Inter', -apple-system, sans-serif;
    background: var(--bg);
    color: var(--text);
    min-height: 100vh;
    -webkit-font-smoothing: antialiased;
}

.layout { display: grid; grid-template-columns: 240px 1fr; min-height: 100vh; }

/* Sidebar */
.sidebar {
    background: var(--surface);
    border-right: 1px solid var(--border);
    padding: 1.5rem;
    display: flex;
    flex-direction: column;
    position: sticky;
    top: 0;
    height: 100vh;
}
.logo {
    font-family: 'JetBrains Mono', monospace;
    font-size: 1.25rem;
    font-weight: 700;
    margin-bottom: 2rem;
    display: flex;
    align-items: center;
    gap: 0.6rem;
    text-decoration: none;
    color: var(--text);
}
.logo .icon {
    width: 30px; height: 30px;
    background: var(--gradient-primary);
    border-radius: 7px;
    display: flex; align-items: center; justify-content: center;
    font-size: 0.75rem; color: #fff;
    box-shadow: 0 4px 12px rgba(99, 102, 241, 0.3);
}
.logo span { color: var(--primary-light); }
.nav-section {
    font-size: 0.65rem; text-transform: uppercase; letter-spacing: 0.1em;
    color: var(--muted); margin: 1.25rem 0 0.5rem 0.5rem; font-weight: 600;
}
.nav-item {
    display: flex; align-items: center; gap: 0.65rem;
    padding: 0.6rem 0.85rem; border-radius: var(--radius-sm);
    color: var(--text-secondary); text-decoration: none; margin-bottom: 0.15rem;
    font-size: 0.85rem; font-weight: 500; transition: var(--transition);
}
.nav-item i { width: 18px; font-size: 0.85rem; text-align: center; }
.nav-item:hover { background: var(--primary-glow); color: var(--text); }
.nav-item.active { background: var(--primary-glow); color: var(--primary-light); }
.nav-divider { height: 1px; background: var(--border); margin: 1rem 0; }
.user-card {
    margin-top: auto; display: flex; align-items: center; gap: 0.7rem;
    padding: 0.85rem; background: var(--bg); border-radius: var(--radius-sm); border: 1px solid var(--border);
}
.user-avatar {
    width: 34px; height: 34px; background: var(--gradient-primary); border-radius: 50%;
    display: flex; align-items: center; justify-content: center; font-size: 0.8rem; color: #fff; flex-shrink: 0;
}
.user-info { min-width: 0; }
.user-name { font-size: 0.85rem; font-weight: 600; white-space: nowrap; overflow: hidden; text-overflow: ellipsis; }
.user-role { font-size: 0.7rem; color: var(--muted); }

/* Main */
.main { padding: 2rem 2.5rem; overflow-y: auto; }
.page-header { margin-bottom: 2rem; }
.page-header h1 { font-size: 1.5rem; font-weight: 700; letter-spacing: -0.02em; }
.page-header .subtitle { color: var(--muted); font-size: 0.85rem; margin-top: 0.2rem; }

/* Stats */
.stats-grid { display: grid; grid-template-columns: repeat(4, 1fr); gap: 1rem; margin-bottom: 2rem; }
.stat-card {
    background: var(--surface); border: 1px solid var(--border); border-radius: var(--radius);
    padding: 1.25rem; transition: var(--transition);
}
.stat-card:hover { border-color: var(--border-light); }
.stat-header { display: flex; justify-content: space-between; align-items: center; margin-bottom: 0.75rem; }
.stat-header .label { font-size: 0.78rem; color: var(--muted); font-weight: 500; }
.stat-header .icon {
    width: 32px; height: 32px; border-radius: var(--radius-sm);
    display: flex; align-items: center; justify-content: center; font-size: 0.8rem;
}
.stat-header .icon.green { background: var(--success-glow); color: var(--success); }
.stat-header .icon.blue { background: var(--primary-glow); color: var(--primary-light); }
.stat-header .icon.yellow { background: var(--warning-glow); color: var(--warning); }
.stat-header .icon.red { background: var(--danger-glow); color: var(--danger); }
.stat-value {
    font-family: 'JetBrains Mono', monospace;
    font-size: 1.6rem; font-weight: 700; letter-spacing: -0.02em;
}
.stat-sub { font-size: 0.75rem; color: var(--muted); margin-top: 0.15rem; }

/* Section */
.section { margin-bottom: 2rem; }
.section-header { display: flex; justify-content: space-between; align-items: center; margin-bottom: 1rem; }
.section-header h2 { font-size: 1.1rem; font-weight: 600; letter-spacing: -0.01em; }

/* Card */
.card { background: var(--surface); border: 1px solid var(--border); border-radius: var(--radius); }
.card-header { padding: 1rem 1.25rem; border-bottom: 1px solid var(--border); display: flex; justify-content: space-between; align-items: center; }
.card-header h3 { font-size: 0.95rem; font-weight: 600; }
.card-body { padding: 1.25rem; }

/* Stream Earnings Row */
.stream-earnings-list { display: flex; flex-direction: column; gap: 1rem; }
.stream-row {
    background: var(--surface); border: 1px solid var(--border); border-radius: var(--radius);
    overflow: hidden; transition: var(--transition);
}
.stream-row:hover { border-color: var(--border-light); }
.stream-row-main {
    display: grid; grid-template-columns: 1fr auto auto auto auto;
    align-items: center; gap: 1.5rem; padding: 1rem 1.25rem; cursor: pointer;
}
.stream-row-main:hover { background: rgba(255,255,255,0.01); }
.stream-name { font-weight: 600; font-size: 0.95rem; }
.stream-name .tag { color: var(--primary-light); font-size: 0.8rem; font-weight: 500; display: block; margin-top: 0.1rem; }
.stream-metric { text-align: right; }
.stream-metric .val {
    font-family: 'JetBrains Mono', monospace; font-size: 1rem; font-weight: 700;
}
.stream-metric .lbl { font-size: 0.7rem; color: var(--muted); }
.stream-metric .val.green { color: var(--success); }
.stream-metric .val.blue { color: var(--primary-light); }
.stream-metric .val.yellow { color: var(--warning); }

.expand-btn {
    background: none; border: none; color: var(--muted); cursor: pointer;
    font-size: 0.85rem; padding: 0.25rem; transition: var(--transition);
}
.expand-btn:hover { color: var(--text); }
.expand-btn.open { transform: rotate(180deg); }

/* Breakdown Panel */
.stream-breakdown {
    display: none; padding: 1.25rem; border-top: 1px solid var(--border);
    background: var(--bg);
}
.stream-breakdown.open { display: block; }

.factors-grid { display: grid; grid-template-columns: repeat(5, 1fr); gap: 1rem; margin-bottom: 1.25rem; }
.factor {
    text-align: center; padding: 0.75rem; background: var(--surface);
    border: 1px solid var(--border); border-radius: var(--radius-sm);
}
.factor-name { font-size: 0.7rem; color: var(--muted); text-transform: uppercase; letter-spacing: 0.05em; font-weight: 600; margin-bottom: 0.35rem; }
.factor-score {
    font-family: 'JetBrains Mono', monospace; font-size: 1.25rem; font-weight: 700; margin-bottom: 0.35rem;
}
.factor-bar { height: 4px; background: var(--border); border-radius: 2px; overflow: hidden; }
.factor-bar .fill { height: 100%; border-radius: 2px; transition: width 0.5s ease; }
.factor-weight { font-size: 0.65rem; color: var(--muted); margin-top: 0.35rem; }

.proj-grid { display: grid; grid-template-columns: repeat(4, 1fr); gap: 1rem; }
.proj-card {
    padding: 0.75rem; background: var(--surface); border: 1px solid var(--border);
    border-radius: var(--radius-sm); text-align: center;
}
.proj-card .period { font-size: 0.7rem; color: var(--muted); text-transform: uppercase; letter-spacing: 0.05em; font-weight: 600; margin-bottom: 0.25rem; }
.proj-card .amount { font-family: 'JetBrains Mono', monospace; font-size: 1.1rem; font-weight: 700; color: var(--success); }

/* How It Works */
.how-grid { display: grid; grid-template-columns: repeat(5, 1fr); gap: 0.75rem; }
.how-item {
    text-align: center; padding: 1rem 0.5rem; background: var(--surface);
    border: 1px solid var(--border); border-radius: var(--radius-sm);
}
.how-icon {
    width: 36px; height: 36px; border-radius: 50%;
    display: flex; align-items: center; justify-content: center;
    font-size: 0.85rem; margin: 0 auto 0.5rem;
}
.how-icon.v { background: var(--primary-glow); color: var(--primary-light); }
.how-icon.r { background: var(--success-glow); color: var(--success); }
.how-icon.e { background: var(--warning-glow); color: var(--warning); }
.how-icon.g { background: var(--danger-glow); color: var(--danger); }
.how-icon.q { background: rgba(147,51,234,0.12); color: #a855f7; }
.how-label { font-size: 0.78rem; font-weight: 600; margin-bottom: 0.15rem; }
.how-weight { font-size: 0.65rem; color: var(--muted); }
.how-desc { font-size: 0.7rem; color: var(--text-secondary); margin-top: 0.25rem; line-height: 1.4; }

/* Empty */
.empty-state { text-align: center; padding: 4rem 2rem; color: var(--muted); }
.empty-icon {
    width: 72px; height: 72px; background: var(--surface); border: 1px solid var(--border);
    border-radius: 50%; display: flex; align-items: center; justify-content: center;
    font-size: 1.75rem; margin: 0 auto 1rem;
}

.btn {
    background: var(--gradient-primary); color: #fff; border: none; padding: 0.55rem 1.25rem;
    border-radius: var(--radius-sm); cursor: pointer; font-family: inherit; font-size: 0.85rem;
    font-weight: 600; display: inline-flex; align-items: center; gap: 0.45rem; text-decoration: none;
    transition: var(--transition); box-shadow: 0 4px 16px rgba(99, 102, 241, 0.2);
}
.btn:hover { transform: translateY(-1px); box-shadow: 0 6px 24px rgba(99, 102, 241, 0.3); }

@media (max-width: 1100px) { .factors-grid { grid-template-columns: repeat(3, 1fr); } .how-grid { grid-template-columns: repeat(3, 1fr); } }
@media (max-width: 900px) {
    .layout { grid-template-columns: 1fr; }
    .sidebar { display: none; }
    .main { padding: 1.5rem; }
    .stats-grid { grid-template-columns: repeat(2, 1fr); }
    .stream-row-main { grid-template-columns: 1fr; gap: 0.5rem; }
    .stream-metric { text-align: left; }
    .factors-grid, .proj-grid { grid-template-columns: repeat(2, 1fr); }
    .how-grid { grid-template-columns: 1fr 1fr; }
}
</style>
</head>
<body>

<div class="layout">
<aside class="sidebar">
    <a class="logo" href="../"><div class="icon"><i class="fa-solid fa-cube"></i></div> Omni<span>Grid</span></a>
    <div class="nav-section">Studio</div>
    <a class="nav-item" href="./"><i class="fa-solid fa-th-large"></i> Dashboard</a>
    <a class="nav-item active" href="earnings.php"><i class="fa-solid fa-coins"></i> Earnings</a>
    <div class="nav-divider"></div>
    <div class="nav-section">Navigation</div>
    <a class="nav-item" href="../"><i class="fa-solid fa-earth-americas"></i> View Site</a>
    <a class="nav-item" href="../globe.php"><i class="fa-solid fa-globe"></i> Globe</a>
    <a class="nav-item" href="../logout.php"><i class="fa-solid fa-arrow-right-from-bracket"></i> Logout</a>
    <div class="user-card">
        <div class="user-avatar"><i class="fa-solid fa-user"></i></div>
        <div class="user-info">
            <div class="user-name"><?= htmlspecialchars($user['display_name'] ?: explode('@', $user['email'])[0]) ?></div>
            <div class="user-role">Creator</div>
        </div>
    </div>
</aside>

<main class="main">
    <div class="page-header">
        <h1>Earnings</h1>
        <div class="subtitle">smartGrid performance across all your streams</div>
    </div>

    <!-- Summary Stats -->
    <div class="stats-grid">
        <div class="stat-card">
            <div class="stat-header">
                <span class="label">Total Earned</span>
                <div class="icon green"><i class="fa-solid fa-coins"></i></div>
            </div>
            <div class="stat-value">$<?= number_format($totalEarnings / 100, 2) ?></div>
            <div class="stat-sub">Lifetime earnings</div>
        </div>
        <div class="stat-card">
            <div class="stat-header">
                <span class="label">Avg Rate</span>
                <div class="icon blue"><i class="fa-solid fa-gauge-high"></i></div>
            </div>
            <div class="stat-value"><?= number_format($avgRate, 2) ?>&cent;</div>
            <div class="stat-sub">Per minute average</div>
        </div>
        <div class="stat-card">
            <div class="stat-header">
                <span class="label">Projected Monthly</span>
                <div class="icon yellow"><i class="fa-solid fa-chart-line"></i></div>
            </div>
            <div class="stat-value">$<?= number_format($totalMonthly, 2) ?></div>
            <div class="stat-sub">Based on 2hr/day</div>
        </div>
        <div class="stat-card">
            <div class="stat-header">
                <span class="label">Active Streams</span>
                <div class="icon red"><i class="fa-solid fa-video"></i></div>
            </div>
            <div class="stat-value"><?= count($streams) ?></div>
            <div class="stat-sub"><?= count(array_filter($streams, fn($s) => $s['is_live'])) ?> live now</div>
        </div>
    </div>

    <?php if (empty($streams)): ?>
    <div class="empty-state">
        <div class="empty-icon"><i class="fa-solid fa-chart-bar"></i></div>
        <p>No streams yet. Create a stream to start tracking earnings.</p>
        <br>
        <a href="./" class="btn"><i class="fa-solid fa-plus"></i> Go to Dashboard</a>
    </div>
    <?php else: ?>

    <!-- Per-Stream Earnings -->
    <div class="section">
        <div class="section-header"><h2>Stream Breakdown</h2></div>
        <div class="stream-earnings-list">
            <?php foreach ($streamData as $i => $sd):
                $s = $sd['stream'];
                $r = $sd['rate'];
                $p = $sd['projections'];
                $b = $r['breakdown'];
            ?>
            <div class="stream-row">
                <div class="stream-row-main" onclick="toggle(<?= $i ?>)">
                    <div class="stream-name">
                        <?= htmlspecialchars($s['title']) ?>
                        <span class="tag"><?= htmlspecialchars($s['vibe_tag'] ?: $s['type']) ?></span>
                    </div>
                    <div class="stream-metric">
                        <div class="val green">$<?= number_format($s['total_earnings_cents'] / 100, 2) ?></div>
                        <div class="lbl">Earned</div>
                    </div>
                    <div class="stream-metric">
                        <div class="val blue"><?= number_format($r['rate_cents_per_min'], 2) ?>&cent;</div>
                        <div class="lbl">Rate/min</div>
                    </div>
                    <div class="stream-metric">
                        <div class="val yellow"><?= number_format($r['composite_score'], 1) ?></div>
                        <div class="lbl">Score</div>
                    </div>
                    <button class="expand-btn" id="btn<?= $i ?>"><i class="fa-solid fa-chevron-down"></i></button>
                </div>
                <div class="stream-breakdown" id="bd<?= $i ?>">
                    <!-- Factor Scores -->
                    <div class="factors-grid">
                        <?php
                        $colors = ['viewers' => '#6366f1', 'retention' => '#10b981', 'engagement' => '#f59e0b', 'growth' => '#ef4444', 'quality' => '#a855f7'];
                        $icons = ['viewers' => 'eye', 'retention' => 'clock', 'engagement' => 'comments', 'growth' => 'arrow-trend-up', 'quality' => 'signal'];
                        foreach ($b as $key => $factor): ?>
                        <div class="factor">
                            <div class="factor-name"><i class="fa-solid fa-<?= $icons[$key] ?>"></i> <?= ucfirst($key) ?></div>
                            <div class="factor-score" style="color: <?= $colors[$key] ?>"><?= $factor['score'] ?></div>
                            <div class="factor-bar"><div class="fill" style="width: <?= $factor['score'] ?>%; background: <?= $colors[$key] ?>"></div></div>
                            <div class="factor-weight"><?= $factor['weight'] ?> weight</div>
                        </div>
                        <?php endforeach; ?>
                    </div>

                    <!-- Projections -->
                    <div style="margin-top: 0.75rem">
                        <div style="font-size: 0.75rem; color: var(--muted); font-weight: 600; text-transform: uppercase; letter-spacing: 0.05em; margin-bottom: 0.65rem">
                            Projected Earnings (2hr/day)
                        </div>
                        <div class="proj-grid">
                            <div class="proj-card">
                                <div class="period">Hourly</div>
                                <div class="amount">$<?= number_format($r['rate_dollars_per_hour'], 2) ?></div>
                            </div>
                            <div class="proj-card">
                                <div class="period">Daily</div>
                                <div class="amount">$<?= number_format($p['daily'], 2) ?></div>
                            </div>
                            <div class="proj-card">
                                <div class="period">Monthly</div>
                                <div class="amount">$<?= number_format($p['monthly'], 2) ?></div>
                            </div>
                            <div class="proj-card">
                                <div class="period">Yearly</div>
                                <div class="amount">$<?= number_format($p['yearly'], 2) ?></div>
                            </div>
                        </div>
                    </div>

                    <!-- Multipliers -->
                    <div style="margin-top: 1rem; display: flex; gap: 1rem; font-size: 0.8rem; color: var(--text-secondary)">
                        <span>Admin Multiplier: <strong><?= $r['multipliers']['admin'] ?>x</strong></span>
                        <span>Stream Multiplier: <strong><?= $r['multipliers']['stream'] ?>x</strong></span>
                        <span>Effective: <strong style="color: var(--primary-light)"><?= $r['multipliers']['effective'] ?>x</strong></span>
                    </div>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
    </div>

    <?php endif; ?>

    <!-- How smartGrid Works -->
    <div class="section">
        <div class="card">
            <div class="card-header"><h3><i class="fa-solid fa-bolt" style="color: var(--warning)"></i> How smartGrid Works</h3></div>
            <div class="card-body">
                <p style="font-size: 0.85rem; color: var(--text-secondary); margin-bottom: 1.25rem; line-height: 1.7">
                    smartGrid dynamically adjusts your payout rate based on five engagement factors. Higher engagement across these metrics means a higher per-minute rate, automatically.
                </p>
                <div class="how-grid">
                    <div class="how-item">
                        <div class="how-icon v"><i class="fa-solid fa-eye"></i></div>
                        <div class="how-label">Viewers</div>
                        <div class="how-weight">35% weight</div>
                        <div class="how-desc">Current viewership vs peak</div>
                    </div>
                    <div class="how-item">
                        <div class="how-icon r"><i class="fa-solid fa-clock"></i></div>
                        <div class="how-label">Retention</div>
                        <div class="how-weight">25% weight</div>
                        <div class="how-desc">Average watch time</div>
                    </div>
                    <div class="how-item">
                        <div class="how-icon e"><i class="fa-solid fa-comments"></i></div>
                        <div class="how-label">Engagement</div>
                        <div class="how-weight">20% weight</div>
                        <div class="how-desc">Tips and chat activity</div>
                    </div>
                    <div class="how-item">
                        <div class="how-icon g"><i class="fa-solid fa-arrow-trend-up"></i></div>
                        <div class="how-label">Growth</div>
                        <div class="how-weight">15% weight</div>
                        <div class="how-desc">Subscriber growth rate</div>
                    </div>
                    <div class="how-item">
                        <div class="how-icon q"><i class="fa-solid fa-signal"></i></div>
                        <div class="how-label">Quality</div>
                        <div class="how-weight">5% weight</div>
                        <div class="how-desc">Uptime and bitrate</div>
                    </div>
                </div>
                <div style="margin-top: 1.25rem; padding: 1rem; background: var(--surface-2); border-radius: var(--radius-sm); font-size: 0.8rem; color: var(--text-secondary)">
                    <strong>Rate Range:</strong> $0.001 &mdash; $0.25 per minute &bull;
                    <strong>Base Rate:</strong> $0.005/min &bull;
                    Your composite score determines where you fall in this range.
                </div>
            </div>
        </div>
    </div>
</main>
</div>

<script>
function toggle(i) {
    const bd = document.getElementById('bd' + i);
    const btn = document.getElementById('btn' + i);
    bd.classList.toggle('open');
    btn.classList.toggle('open');
}
</script>
</body>
</html>
