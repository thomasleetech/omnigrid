<?php
session_start();
require_once 'includes/db.php';

$filter = $_GET['filter'] ?? 'all';
$sort = $_GET['sort'] ?? 'popular';

// Build query with resilient column references
try {
    $where = "1=1";
    try {
        $pdo->query("SELECT is_active FROM streams LIMIT 1");
        $where = "s.is_active = 1";
    } catch (Exception $e) {}

    if ($filter === 'live') $where .= " AND s.is_live = 1";

    $orderBy = match($sort) {
        'newest' => 's.created_at DESC',
        default => 's.is_live DESC, s.created_at DESC'
    };

    $streams = $pdo->query("
        SELECT s.*, u.display_name, u.email
        FROM streams s
        JOIN users u ON s.user_id = u.id
        WHERE $where
        ORDER BY $orderBy
        LIMIT 50
    ")->fetchAll(PDO::FETCH_ASSOC);
} catch (Exception $e) {
    $streams = [];
}

$liveCount = 0;
$totalCount = count($streams);
foreach ($streams as $s) {
    if (!empty($s['is_live'])) $liveCount++;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width,initial-scale=1.0">
<title>Browse Streams — OmniGrid</title>
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
    --warning: #f59e0b;
    --danger: #ef4444;
    --gradient-primary: linear-gradient(135deg, #6366f1, #a855f7);
    --radius: 12px;
    --radius-sm: 8px;
    --radius-pill: 100px;
    --shadow: 0 4px 24px rgba(0, 0, 0, 0.3);
    --shadow-lg: 0 12px 48px rgba(0, 0, 0, 0.5);
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
a { text-decoration: none; color: inherit; }

/* Header */
.header {
    position: sticky; top: 0; z-index: 100;
    padding: 0 2rem; height: 64px;
    display: flex; align-items: center; justify-content: space-between;
    background: rgba(8, 8, 13, 0.85); backdrop-filter: blur(20px);
    border-bottom: 1px solid rgba(37, 37, 58, 0.5);
}
.logo {
    font-family: 'JetBrains Mono', monospace;
    font-size: 1.35rem; font-weight: 700;
    display: flex; align-items: center; gap: 0.6rem;
}
.logo-icon {
    width: 32px; height: 32px;
    background: var(--gradient-primary); border-radius: 8px;
    display: flex; align-items: center; justify-content: center;
    font-size: 0.85rem; color: #fff;
    box-shadow: 0 4px 16px rgba(99, 102, 241, 0.3);
}
.logo-text span { color: var(--primary-light); }

.nav { display: flex; align-items: center; gap: 0.25rem; }
.nav a {
    color: var(--text-secondary); padding: 0.5rem 1rem; border-radius: var(--radius-sm);
    font-size: 0.875rem; font-weight: 500; transition: var(--transition);
    display: flex; align-items: center; gap: 0.4rem;
}
.nav a:hover { color: var(--text); background: var(--primary-glow); }
.nav a i { font-size: 0.8rem; }
.btn-primary {
    background: var(--gradient-primary); color: #fff;
    padding: 0.55rem 1.35rem; border-radius: var(--radius-pill);
    font-size: 0.85rem; font-weight: 600;
    display: inline-flex; align-items: center; gap: 0.45rem;
    box-shadow: 0 4px 16px rgba(99, 102, 241, 0.25);
    transition: var(--transition);
}
.btn-primary:hover { transform: translateY(-1px); box-shadow: 0 6px 24px rgba(99, 102, 241, 0.35); }

.mobile-toggle {
    display: none; background: none; border: none; color: var(--text);
    font-size: 1.25rem; cursor: pointer; padding: 0.5rem;
}

/* Container */
.container { max-width: 1320px; margin: 0 auto; padding: 0 2rem; }

/* Page Header */
.page-top { padding: 2.5rem 0 0; }
.page-top h1 { font-size: 1.75rem; font-weight: 800; letter-spacing: -0.03em; margin-bottom: 0.35rem; }
.page-top p { color: var(--muted); font-size: 0.9rem; }

/* Filter Bar */
.filter-bar {
    display: flex; justify-content: space-between; align-items: center;
    padding: 1.5rem 0; gap: 1rem; flex-wrap: wrap;
}
.filters { display: flex; gap: 0.4rem; flex-wrap: wrap; }
.filter-pill {
    padding: 0.45rem 1rem; border-radius: var(--radius-pill);
    font-size: 0.82rem; font-weight: 500;
    background: var(--surface); border: 1px solid var(--border);
    color: var(--text-secondary); cursor: pointer;
    transition: var(--transition);
    display: inline-flex; align-items: center; gap: 0.35rem;
}
.filter-pill:hover { border-color: var(--border-light); color: var(--text); }
.filter-pill.active {
    background: var(--primary-glow); border-color: var(--primary);
    color: var(--primary-light);
}
.filter-pill .count {
    background: rgba(255,255,255,0.06); padding: 0.1rem 0.4rem; border-radius: 6px;
    font-size: 0.7rem; font-family: 'JetBrains Mono', monospace;
}
.filter-pill.active .count { background: rgba(99,102,241,0.2); }

.sort-select {
    background: var(--surface); border: 1px solid var(--border); color: var(--text);
    padding: 0.45rem 0.85rem; border-radius: var(--radius-sm);
    font-family: inherit; font-size: 0.82rem; cursor: pointer;
    transition: var(--transition);
}
.sort-select:focus { outline: none; border-color: var(--primary); }

/* Grid */
.stream-grid {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(300px, 1fr));
    gap: 1.25rem;
    padding-bottom: 3rem;
}

.card {
    background: var(--surface); border: 1px solid var(--border);
    border-radius: var(--radius); overflow: hidden;
    transition: var(--transition); position: relative;
}
.card:hover { border-color: var(--border-light); transform: translateY(-4px); box-shadow: var(--shadow-lg); }
.card.live { border-color: rgba(239, 68, 68, 0.3); }
.card.live:hover { border-color: rgba(239, 68, 68, 0.5); box-shadow: 0 12px 48px rgba(239, 68, 68, 0.15); }

.thumb {
    height: 180px; background: var(--surface-2); position: relative; overflow: hidden;
}
.thumb img { width: 100%; height: 100%; object-fit: cover; transition: transform 0.4s ease; }
.card:hover .thumb img { transform: scale(1.05); }
.thumb .no-img {
    display: flex; align-items: center; justify-content: center;
    height: 100%; color: var(--border-light); font-size: 2.5rem;
}
.thumb::after {
    content: ''; position: absolute; bottom: 0; left: 0; right: 0;
    height: 60px; background: linear-gradient(transparent, rgba(17, 17, 24, 0.8));
    pointer-events: none;
}

.badge {
    position: absolute; top: 0.75rem; left: 0.75rem;
    padding: 0.2rem 0.65rem; border-radius: var(--radius-pill);
    font-size: 0.65rem; font-weight: 700; text-transform: uppercase;
    letter-spacing: 0.05em; color: #fff; z-index: 2;
}
.badge-live {
    background: var(--danger);
    animation: pulse 1.5s ease-in-out infinite;
    box-shadow: 0 2px 12px rgba(239, 68, 68, 0.4);
    display: flex; align-items: center; gap: 0.3rem;
}
.badge-live::before { content: ''; width: 6px; height: 6px; background: #fff; border-radius: 50%; }
@keyframes pulse { 0%,100%{opacity:1}50%{opacity:0.6} }

.card-body { padding: 1.1rem 1.2rem; }
.card-title {
    font-weight: 600; font-size: 0.95rem; margin-bottom: 0.35rem;
    white-space: nowrap; overflow: hidden; text-overflow: ellipsis;
}
.card-creator { color: var(--muted); font-size: 0.8rem; display: flex; align-items: center; gap: 0.3rem; }

/* Empty */
.empty {
    text-align: center; padding: 5rem 2rem; color: var(--muted);
}
.empty-icon {
    width: 80px; height: 80px; background: var(--surface); border: 1px solid var(--border);
    border-radius: 50%; display: flex; align-items: center; justify-content: center;
    font-size: 2rem; margin: 0 auto 1.25rem;
}
.empty p { font-size: 1rem; margin-bottom: 0.5rem; }
.empty .sub { font-size: 0.85rem; }

/* Footer */
.footer { padding: 2rem; border-top: 1px solid var(--border); text-align: center; }
.footer-text { color: var(--muted); font-size: 0.85rem; }

@media (max-width: 768px) {
    .header { padding: 0 1.25rem; }
    .nav { display: none; }
    .mobile-toggle { display: block; }
    .container { padding: 0 1.25rem; }
    .page-top h1 { font-size: 1.4rem; }
    .stream-grid { grid-template-columns: 1fr; }
    .filter-bar { flex-direction: column; align-items: flex-start; }
}
</style>
</head>
<body>

<header class="header">
    <a href="./" class="logo">
        <div class="logo-icon"><i class="fa-solid fa-cube"></i></div>
        <div class="logo-text">Omni<span>Grid</span></div>
    </a>
    <button class="mobile-toggle" aria-label="Menu"><i class="fa-solid fa-bars"></i></button>
    <nav class="nav">
        <a href="globe.php"><i class="fa-solid fa-earth-americas"></i> Globe</a>
        <a href="watch.php" style="color: var(--primary-light)"><i class="fa-solid fa-play"></i> Browse</a>
        <a href="calculator.html"><i class="fa-solid fa-calculator"></i> Earnings</a>
        <?php if (!empty($_SESSION['user_id'])): ?>
            <a href="contribute/" class="btn-primary"><i class="fa-solid fa-broadcast-tower"></i> Studio</a>
        <?php else: ?>
            <a href="login.php"><i class="fa-solid fa-arrow-right-to-bracket"></i> Login</a>
            <a href="contribute/" class="btn-primary"><i class="fa-solid fa-broadcast-tower"></i> Go Live</a>
        <?php endif; ?>
    </nav>
</header>

<div class="container">
    <div class="page-top">
        <h1>Browse Streams</h1>
        <p>Discover live and on-demand streams from creators worldwide</p>
    </div>

    <div class="filter-bar">
        <div class="filters">
            <a href="?filter=all&sort=<?= htmlspecialchars($sort) ?>" class="filter-pill <?= $filter === 'all' ? 'active' : '' ?>">
                All <span class="count"><?= $totalCount ?></span>
            </a>
            <a href="?filter=live&sort=<?= htmlspecialchars($sort) ?>" class="filter-pill <?= $filter === 'live' ? 'active' : '' ?>">
                <i class="fa-solid fa-circle" style="font-size: 0.5rem; color: var(--danger)"></i> Live <span class="count"><?= $liveCount ?></span>
            </a>
        </div>
        <select class="sort-select" onchange="location.href='?filter=<?= htmlspecialchars($filter) ?>&sort='+this.value">
            <option value="popular" <?= $sort === 'popular' ? 'selected' : '' ?>>Most Popular</option>
            <option value="newest" <?= $sort === 'newest' ? 'selected' : '' ?>>Newest</option>
        </select>
    </div>

    <?php if (empty($streams)): ?>
    <div class="empty">
        <div class="empty-icon"><i class="fa-solid fa-satellite-dish"></i></div>
        <p>No streams found</p>
        <p class="sub">Try a different filter or check back later</p>
    </div>
    <?php else: ?>
    <div class="stream-grid">
        <?php foreach ($streams as $s): ?>
        <a href="live.php?id=<?= $s['id'] ?>" class="card <?= !empty($s['is_live']) ? 'live' : '' ?>">
            <div class="thumb">
                <?php if (!empty($s['thumb_url'])): ?>
                    <img src="<?= htmlspecialchars($s['thumb_url']) ?>" alt="<?= htmlspecialchars($s['title']) ?>" loading="lazy">
                <?php else: ?>
                    <div class="no-img"><i class="fa-solid fa-video"></i></div>
                <?php endif; ?>
                <?php if (!empty($s['is_live'])): ?>
                    <span class="badge badge-live">Live</span>
                <?php endif; ?>
            </div>
            <div class="card-body">
                <div class="card-title"><?= htmlspecialchars($s['title']) ?></div>
                <span class="card-creator"><i class="fa-solid fa-user"></i> <?= htmlspecialchars($s['display_name'] ?: explode('@', $s['email'])[0]) ?></span>
            </div>
        </a>
        <?php endforeach; ?>
    </div>
    <?php endif; ?>
</div>

<footer class="footer">
    <div class="footer-text">&copy; <?= date('Y') ?> OmniGrid. All rights reserved.</div>
</footer>

</body>
</html>
