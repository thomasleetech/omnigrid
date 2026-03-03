<?php
require_once 'includes/db.php';

// Get live/active streams
$stmt = $pdo->query("
    SELECT s.*, u.display_name, u.email,
           m.views, m.tips_cents, m.subs_count
    FROM streams s
    JOIN users u ON s.user_id = u.id
    LEFT JOIN stream_metrics m ON s.id = m.stream_id
    WHERE s.is_active = 1
    ORDER BY s.is_live DESC, m.views DESC
    LIMIT 20
");
$streams = $stmt->fetchAll();
$liveCount = $pdo->query("SELECT COUNT(*) FROM streams WHERE is_live = 1")->fetchColumn();
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>OmniGrid | Live Streams From Everywhere</title>
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
<style>
:root {
    --bg: #f8f9fc;
    --surface: #ffffff;
    --border: #e5e7eb;
    --text: #1f2937;
    --muted: #6b7280;
    --primary: #6366f1;
    --primary-light: #e0e7ff;
    --success: #10b981;
    --danger: #ef4444;
}
* { box-sizing: border-box; margin: 0; padding: 0; }
body { font-family: 'Inter', -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif; background: var(--bg); color: var(--text); line-height: 1.5; }

/* Header */
.header {
    background: var(--surface);
    border-bottom: 1px solid var(--border);
    position: sticky;
    top: 0;
    z-index: 100;
}
.header-inner {
    max-width: 1400px;
    margin: 0 auto;
    padding: 0.75rem 2rem;
    display: flex;
    justify-content: space-between;
    align-items: center;
}
.logo {
    font-size: 1.4rem;
    font-weight: 700;
    color: var(--text);
    text-decoration: none;
    display: flex;
    align-items: center;
    gap: 0.5rem;
}
.logo i { color: var(--primary); }
.logo span { color: var(--primary); }
.nav { display: flex; align-items: center; gap: 0.5rem; }
.nav-link {
    color: var(--muted);
    text-decoration: none;
    padding: 0.5rem 1rem;
    border-radius: 8px;
    font-size: 0.9rem;
    transition: all 0.2s;
}
.nav-link:hover { color: var(--text); background: var(--bg); }
.nav-link.active { color: var(--primary); background: var(--primary-light); }
.btn {
    background: var(--primary);
    color: #fff;
    border: none;
    padding: 0.55rem 1.2rem;
    border-radius: 8px;
    font-size: 0.85rem;
    cursor: pointer;
    text-decoration: none;
    display: inline-flex;
    align-items: center;
    gap: 0.4rem;
    transition: all 0.2s;
}
.btn:hover { filter: brightness(1.1); transform: translateY(-1px); }
.btn-outline { background: transparent; border: 1px solid var(--border); color: var(--text); }
.btn-outline:hover { background: var(--bg); border-color: var(--primary); }

/* Hero */
.hero {
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    color: #fff;
    padding: 4rem 2rem;
    text-align: center;
}
.hero h1 {
    font-size: 2.75rem;
    font-weight: 700;
    margin-bottom: 1rem;
    letter-spacing: -0.02em;
}
.hero p {
    font-size: 1.15rem;
    opacity: 0.9;
    max-width: 600px;
    margin: 0 auto 2rem;
}
.hero-stats {
    display: flex;
    justify-content: center;
    gap: 3rem;
    margin-top: 2rem;
}
.hero-stat {
    text-align: center;
}
.hero-stat .num {
    font-size: 2rem;
    font-weight: 700;
}
.hero-stat .label {
    font-size: 0.85rem;
    opacity: 0.8;
}
.hero-actions {
    display: flex;
    justify-content: center;
    gap: 1rem;
    margin-top: 2rem;
}
.hero .btn {
    padding: 0.75rem 2rem;
    font-size: 1rem;
}
.hero .btn-outline {
    border-color: rgba(255,255,255,0.4);
    color: #fff;
}
.hero .btn-outline:hover {
    background: rgba(255,255,255,0.1);
    border-color: #fff;
}

/* Filters */
.filters {
    max-width: 1400px;
    margin: 0 auto;
    padding: 1.5rem 2rem;
    display: flex;
    justify-content: space-between;
    align-items: center;
    flex-wrap: wrap;
    gap: 1rem;
}
.filter-tabs {
    display: flex;
    gap: 0.5rem;
}
.filter-tab {
    background: var(--surface);
    border: 1px solid var(--border);
    color: var(--muted);
    padding: 0.5rem 1rem;
    border-radius: 20px;
    font-size: 0.85rem;
    cursor: pointer;
    transition: all 0.2s;
}
.filter-tab:hover { border-color: var(--primary); color: var(--text); }
.filter-tab.active { background: var(--primary); border-color: var(--primary); color: #fff; }
.view-toggle {
    display: flex;
    background: var(--surface);
    border: 1px solid var(--border);
    border-radius: 8px;
    overflow: hidden;
}
.view-btn {
    background: none;
    border: none;
    padding: 0.5rem 1rem;
    color: var(--muted);
    cursor: pointer;
}
.view-btn.active { background: var(--primary-light); color: var(--primary); }

/* Grid */
.stream-grid {
    max-width: 1400px;
    margin: 0 auto;
    padding: 0 2rem 3rem;
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(300px, 1fr));
    gap: 1.25rem;
}
.stream-card {
    background: var(--surface);
    border: 1px solid var(--border);
    border-radius: 12px;
    overflow: hidden;
    transition: all 0.2s;
    text-decoration: none;
    color: inherit;
}
.stream-card:hover {
    transform: translateY(-4px);
    box-shadow: 0 12px 40px rgba(0,0,0,0.1);
    border-color: var(--primary);
}
.stream-thumb {
    position: relative;
    height: 170px;
    background: var(--bg);
    overflow: hidden;
}
.stream-thumb img {
    width: 100%;
    height: 100%;
    object-fit: cover;
}
.stream-thumb .no-thumb {
    display: flex;
    align-items: center;
    justify-content: center;
    height: 100%;
    color: var(--border);
    font-size: 2.5rem;
}
.stream-badge {
    position: absolute;
    top: 0.6rem;
    left: 0.6rem;
    padding: 0.2rem 0.6rem;
    border-radius: 4px;
    font-size: 0.7rem;
    font-weight: 600;
    text-transform: uppercase;
}
.badge-live { background: var(--danger); color: #fff; animation: pulse 2s infinite; }
.badge-public { background: var(--success); color: #fff; }
.badge-lifestyle { background: #f59e0b; color: #fff; }
.badge-nsfw { background: #9333ea; color: #fff; }
@keyframes pulse { 0%,100% { opacity: 1; } 50% { opacity: 0.7; } }
.stream-viewers {
    position: absolute;
    bottom: 0.6rem;
    right: 0.6rem;
    background: rgba(0,0,0,0.7);
    color: #fff;
    padding: 0.2rem 0.5rem;
    border-radius: 4px;
    font-size: 0.75rem;
    display: flex;
    align-items: center;
    gap: 0.3rem;
}
.stream-body { padding: 1rem; }
.stream-title {
    font-weight: 600;
    font-size: 0.95rem;
    margin-bottom: 0.3rem;
    white-space: nowrap;
    overflow: hidden;
    text-overflow: ellipsis;
}
.stream-creator {
    color: var(--muted);
    font-size: 0.85rem;
    display: flex;
    align-items: center;
    gap: 0.4rem;
}
.stream-tag {
    display: inline-block;
    background: var(--primary-light);
    color: var(--primary);
    padding: 0.15rem 0.5rem;
    border-radius: 4px;
    font-size: 0.75rem;
    margin-top: 0.6rem;
}
.stream-meta {
    display: flex;
    justify-content: space-between;
    margin-top: 0.75rem;
    padding-top: 0.75rem;
    border-top: 1px solid var(--border);
    font-size: 0.8rem;
    color: var(--muted);
}

/* Globe CTA */
.globe-cta {
    max-width: 1400px;
    margin: 0 auto 3rem;
    padding: 0 2rem;
}
.globe-cta-inner {
    background: linear-gradient(135deg, #1e1b4b 0%, #312e81 100%);
    border-radius: 16px;
    padding: 3rem;
    display: flex;
    align-items: center;
    justify-content: space-between;
    color: #fff;
}
.globe-cta h2 {
    font-size: 1.75rem;
    margin-bottom: 0.5rem;
}
.globe-cta p {
    opacity: 0.8;
    margin-bottom: 1.5rem;
}
.globe-cta .btn {
    background: #fff;
    color: var(--primary);
}
.globe-cta .btn:hover { background: #f0f0ff; }
.globe-icon {
    font-size: 5rem;
    opacity: 0.3;
}

/* Footer */
.footer {
    background: var(--surface);
    border-top: 1px solid var(--border);
    padding: 2rem;
    text-align: center;
    color: var(--muted);
    font-size: 0.85rem;
}
.footer a { color: var(--primary); text-decoration: none; }

/* Empty State */
.empty-state {
    text-align: center;
    padding: 4rem 2rem;
    color: var(--muted);
}
.empty-state i { font-size: 4rem; opacity: 0.2; margin-bottom: 1rem; }

@media (max-width: 768px) {
    .header-inner { padding: 0.75rem 1rem; }
    .nav { display: none; }
    .hero { padding: 2.5rem 1rem; }
    .hero h1 { font-size: 1.75rem; }
    .hero-stats { gap: 1.5rem; }
    .hero-stat .num { font-size: 1.5rem; }
    .filters { padding: 1rem; }
    .stream-grid { padding: 0 1rem 2rem; }
    .globe-cta { padding: 0 1rem; }
    .globe-cta-inner { flex-direction: column; text-align: center; padding: 2rem 1.5rem; }
    .globe-icon { display: none; }
}
</style>
</head>
<body>

<header class="header">
    <div class="header-inner">
        <a href="./" class="logo"><i class="fa fa-circle-nodes"></i> Omni<span>Grid</span></a>
        <nav class="nav">
            <a href="./" class="nav-link active">Discover</a>
            <a href="globe.php" class="nav-link"><i class="fa fa-globe"></i> Globe</a>
            <a href="contribute/" class="nav-link">Create</a>
        </nav>
        <div class="nav">
            <a href="login.php" class="btn btn-outline">Log In</a>
            <a href="register.php" class="btn">Sign Up</a>
        </div>
    </div>
</header>

<section class="hero">
    <h1>Live Streams From Everywhere</h1>
    <p>Discover authentic moments from creators around the world. Watch, connect, and earn with our smartGrid adaptive platform.</p>
    <div class="hero-actions">
        <a href="globe.php" class="btn"><i class="fa fa-globe"></i> Explore the Globe</a>
        <a href="contribute/" class="btn btn-outline"><i class="fa fa-broadcast-tower"></i> Start Streaming</a>
    </div>
    <div class="hero-stats">
        <div class="hero-stat">
            <div class="num"><?= $liveCount ?></div>
            <div class="label">Live Now</div>
        </div>
        <div class="hero-stat">
            <div class="num"><?= count($streams) ?></div>
            <div class="label">Streams</div>
        </div>
        <div class="hero-stat">
            <div class="num"><?= number_format(array_sum(array_column($streams, 'views'))) ?></div>
            <div class="label">Total Views</div>
        </div>
    </div>
</section>

<div class="filters">
    <div class="filter-tabs">
        <button class="filter-tab active">All</button>
        <button class="filter-tab">Public</button>
        <button class="filter-tab">Lifestyle</button>
        <button class="filter-tab">Live Now</button>
    </div>
    <div class="view-toggle">
        <button class="view-btn active"><i class="fa fa-th"></i></button>
        <button class="view-btn"><i class="fa fa-list"></i></button>
    </div>
</div>

<div class="stream-grid">
    <?php if (empty($streams)): ?>
        <div class="empty-state" style="grid-column: 1/-1">
            <i class="fa fa-video-slash"></i>
            <p>No streams available yet. Be the first to go live!</p>
            <a href="contribute/" class="btn"><i class="fa fa-plus"></i> Create Stream</a>
        </div>
    <?php else: ?>
        <?php foreach ($streams as $s): 
            $creator = $s['display_name'] ?: explode('@', $s['email'])[0];
        ?>
        <a href="live.php?id=<?= $s['id'] ?>" class="stream-card">
            <div class="stream-thumb">
                <?php if ($s['thumb_url']): ?>
                    <img src="<?= htmlspecialchars($s['thumb_url']) ?>" alt="">
                <?php else: ?>
                    <div class="no-thumb"><i class="fa fa-video"></i></div>
                <?php endif; ?>
                <?php if ($s['is_live']): ?>
                    <span class="stream-badge badge-live">LIVE</span>
                <?php else: ?>
                    <span class="stream-badge badge-<?= $s['type'] ?>"><?= $s['type'] ?></span>
                <?php endif; ?>
                <span class="stream-viewers"><i class="fa fa-eye"></i> <?= number_format($s['views'] ?? 0) ?></span>
            </div>
            <div class="stream-body">
                <div class="stream-title"><?= htmlspecialchars($s['title']) ?></div>
                <div class="stream-creator"><i class="fa fa-user-circle"></i> <?= htmlspecialchars($creator) ?></div>
                <?php if ($s['vibe_tag']): ?>
                    <span class="stream-tag"><?= htmlspecialchars($s['vibe_tag']) ?></span>
                <?php endif; ?>
                <div class="stream-meta">
                    <span><i class="fa fa-users"></i> <?= $s['subs_count'] ?? 0 ?> subs</span>
                    <span><i class="fa fa-bolt"></i> <?= $s['smartgrid_multiplier'] ?>x</span>
                </div>
            </div>
        </a>
        <?php endforeach; ?>
    <?php endif; ?>
</div>

<div class="globe-cta">
    <div class="globe-cta-inner">
        <div>
            <h2>Discover Random Streams</h2>
            <p>Spin the globe and drop into a live feed from anywhere on Earth</p>
            <a href="globe.php" class="btn"><i class="fa fa-globe"></i> Launch Globe</a>
        </div>
        <div class="globe-icon"><i class="fa fa-earth-americas"></i></div>
    </div>
</div>

<footer class="footer">
    <p>&copy; <?= date('Y') ?> OmniGrid. Powered by <a href="#">smartGrid</a> adaptive earnings.</p>
</footer>

</body>
</html>
