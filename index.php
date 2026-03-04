<?php
require_once 'includes/db.php';
$stmt = $pdo->query("SELECT s.*, m.views, m.subs_count, m.tips_cents, u.display_name FROM streams s LEFT JOIN stream_metrics m ON s.id = m.stream_id LEFT JOIN users u ON s.user_id = u.id WHERE s.is_active = 1 ORDER BY s.is_live DESC, m.views DESC LIMIT 12");
$streams = $stmt->fetchAll();
$live = array_filter($streams, fn($s) => $s['is_live']);
$offline = array_filter($streams, fn($s) => !$s['is_live']);
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width,initial-scale=1.0">
<title>OmniGrid — Live Streams from Everywhere</title>
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
    --accent: #f43f5e;
    --accent-glow: rgba(244, 63, 94, 0.15);
    --success: #10b981;
    --warning: #f59e0b;
    --danger: #ef4444;
    --gradient-primary: linear-gradient(135deg, #6366f1, #a855f7);
    --gradient-accent: linear-gradient(135deg, #f43f5e, #ec4899);
    --gradient-warm: linear-gradient(135deg, #f59e0b, #f43f5e);
    --radius: 12px;
    --radius-sm: 8px;
    --radius-pill: 100px;
    --shadow: 0 4px 24px rgba(0, 0, 0, 0.3);
    --shadow-lg: 0 12px 48px rgba(0, 0, 0, 0.5);
    --transition: 0.25s cubic-bezier(0.4, 0, 0.2, 1);
}

* { box-sizing: border-box; margin: 0; padding: 0; }

body {
    font-family: 'Inter', -apple-system, BlinkMacSystemFont, 'Segoe UI', sans-serif;
    background: var(--bg);
    color: var(--text);
    line-height: 1.6;
    overflow-x: hidden;
    -webkit-font-smoothing: antialiased;
}

a { text-decoration: none; color: inherit; }

/* ── Header ── */
.header {
    position: fixed;
    top: 0;
    left: 0;
    right: 0;
    z-index: 100;
    padding: 0 2rem;
    height: 64px;
    display: flex;
    align-items: center;
    justify-content: space-between;
    background: rgba(8, 8, 13, 0.8);
    backdrop-filter: blur(20px);
    border-bottom: 1px solid rgba(37, 37, 58, 0.5);
    transition: var(--transition);
}
.header.scrolled {
    background: rgba(8, 8, 13, 0.95);
    border-bottom-color: var(--border);
}

.logo {
    font-family: 'JetBrains Mono', monospace;
    font-size: 1.35rem;
    font-weight: 700;
    display: flex;
    align-items: center;
    gap: 0.6rem;
    letter-spacing: -0.02em;
}
.logo-icon {
    width: 32px;
    height: 32px;
    background: var(--gradient-primary);
    border-radius: 8px;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 0.85rem;
    color: #fff;
    box-shadow: 0 4px 16px rgba(99, 102, 241, 0.3);
}
.logo-text span { color: var(--primary-light); }

.nav {
    display: flex;
    align-items: center;
    gap: 0.25rem;
}
.nav a {
    color: var(--text-secondary);
    padding: 0.5rem 1rem;
    border-radius: var(--radius-sm);
    font-size: 0.875rem;
    font-weight: 500;
    transition: var(--transition);
    display: flex;
    align-items: center;
    gap: 0.4rem;
}
.nav a:hover {
    color: var(--text);
    background: var(--primary-glow);
}
.nav a i { font-size: 0.8rem; }

.btn {
    background: var(--gradient-primary);
    color: #fff;
    padding: 0.55rem 1.35rem;
    border-radius: var(--radius-pill);
    font-size: 0.85rem;
    font-weight: 600;
    display: inline-flex;
    align-items: center;
    gap: 0.45rem;
    border: none;
    cursor: pointer;
    transition: var(--transition);
    box-shadow: 0 4px 16px rgba(99, 102, 241, 0.25);
    white-space: nowrap;
}
.btn:hover {
    transform: translateY(-1px);
    box-shadow: 0 6px 24px rgba(99, 102, 241, 0.35);
}
.btn-outline {
    background: transparent;
    border: 1px solid var(--border-light);
    color: var(--text);
    box-shadow: none;
}
.btn-outline:hover {
    border-color: var(--primary);
    background: var(--primary-glow);
    box-shadow: none;
}
.btn-lg {
    padding: 0.85rem 2.25rem;
    font-size: 1rem;
    border-radius: var(--radius-pill);
}

.mobile-toggle {
    display: none;
    background: none;
    border: none;
    color: var(--text);
    font-size: 1.25rem;
    cursor: pointer;
    padding: 0.5rem;
}

/* ── Hero Section ── */
.hero {
    position: relative;
    padding: 10rem 2rem 6rem;
    text-align: center;
    overflow: hidden;
    min-height: 85vh;
    display: flex;
    align-items: center;
    justify-content: center;
}
.hero-bg {
    position: absolute;
    inset: 0;
    overflow: hidden;
}
.hero-bg::before {
    content: '';
    position: absolute;
    top: -50%;
    left: -30%;
    width: 80%;
    height: 80%;
    background: radial-gradient(ellipse, rgba(99, 102, 241, 0.12) 0%, transparent 70%);
    animation: float1 20s ease-in-out infinite;
}
.hero-bg::after {
    content: '';
    position: absolute;
    bottom: -40%;
    right: -20%;
    width: 70%;
    height: 70%;
    background: radial-gradient(ellipse, rgba(168, 85, 247, 0.08) 0%, transparent 70%);
    animation: float2 25s ease-in-out infinite;
}
@keyframes float1 {
    0%, 100% { transform: translate(0, 0) rotate(0deg); }
    33% { transform: translate(5%, 8%) rotate(2deg); }
    66% { transform: translate(-3%, -5%) rotate(-1deg); }
}
@keyframes float2 {
    0%, 100% { transform: translate(0, 0) rotate(0deg); }
    50% { transform: translate(-6%, 4%) rotate(-2deg); }
}

.hero-grid-lines {
    position: absolute;
    inset: 0;
    background-image:
        linear-gradient(rgba(99, 102, 241, 0.03) 1px, transparent 1px),
        linear-gradient(90deg, rgba(99, 102, 241, 0.03) 1px, transparent 1px);
    background-size: 60px 60px;
    mask-image: radial-gradient(ellipse 60% 50% at 50% 40%, black 20%, transparent 80%);
}

.hero-content {
    position: relative;
    z-index: 2;
    max-width: 760px;
    margin: 0 auto;
}

.hero-badge {
    display: inline-flex;
    align-items: center;
    gap: 0.5rem;
    background: var(--surface);
    border: 1px solid var(--border);
    padding: 0.4rem 1rem 0.4rem 0.5rem;
    border-radius: var(--radius-pill);
    font-size: 0.8rem;
    color: var(--text-secondary);
    margin-bottom: 2rem;
    animation: fadeUp 0.8s ease-out;
}
.hero-badge .dot {
    width: 8px;
    height: 8px;
    background: var(--success);
    border-radius: 50%;
    animation: pulse 2s ease-in-out infinite;
    box-shadow: 0 0 8px rgba(16, 185, 129, 0.5);
}
.hero-badge strong { color: var(--text); font-weight: 600; }

.hero h1 {
    font-size: clamp(2.5rem, 5.5vw, 4rem);
    font-weight: 800;
    line-height: 1.1;
    letter-spacing: -0.03em;
    margin-bottom: 1.25rem;
    animation: fadeUp 0.8s ease-out 0.1s both;
}
.hero h1 .gradient {
    background: var(--gradient-primary);
    -webkit-background-clip: text;
    -webkit-text-fill-color: transparent;
    background-clip: text;
}

.hero p {
    color: var(--text-secondary);
    font-size: 1.15rem;
    max-width: 520px;
    margin: 0 auto 2.5rem;
    line-height: 1.7;
    animation: fadeUp 0.8s ease-out 0.2s both;
}

.hero-btns {
    display: flex;
    gap: 1rem;
    justify-content: center;
    flex-wrap: wrap;
    animation: fadeUp 0.8s ease-out 0.3s both;
}

.hero-stats {
    display: flex;
    justify-content: center;
    gap: 3rem;
    margin-top: 4rem;
    animation: fadeUp 0.8s ease-out 0.4s both;
}
.hero-stat {
    text-align: center;
}
.hero-stat .num {
    font-family: 'JetBrains Mono', monospace;
    font-size: 1.5rem;
    font-weight: 700;
    color: var(--text);
}
.hero-stat .label {
    font-size: 0.8rem;
    color: var(--muted);
    margin-top: 0.2rem;
}

@keyframes fadeUp {
    from { opacity: 0; transform: translateY(20px); }
    to { opacity: 1; transform: translateY(0); }
}
@keyframes pulse {
    0%, 100% { opacity: 1; transform: scale(1); }
    50% { opacity: 0.6; transform: scale(0.9); }
}

/* ── Sections ── */
.container {
    max-width: 1320px;
    margin: 0 auto;
    padding: 0 2rem;
}

.section {
    padding: 5rem 0;
}

.section-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 2rem;
}
.section-header h2 {
    font-size: 1.5rem;
    font-weight: 700;
    display: flex;
    align-items: center;
    gap: 0.65rem;
    letter-spacing: -0.02em;
}
.section-header .view-all {
    color: var(--primary-light);
    font-size: 0.875rem;
    font-weight: 500;
    display: flex;
    align-items: center;
    gap: 0.3rem;
    transition: var(--transition);
}
.section-header .view-all:hover { gap: 0.6rem; }

.live-dot {
    width: 10px;
    height: 10px;
    background: var(--danger);
    border-radius: 50%;
    animation: pulse 1.5s ease-in-out infinite;
    box-shadow: 0 0 12px rgba(239, 68, 68, 0.5);
}

/* ── Stream Cards ── */
.grid {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(300px, 1fr));
    gap: 1.25rem;
}

.card {
    background: var(--surface);
    border: 1px solid var(--border);
    border-radius: var(--radius);
    overflow: hidden;
    transition: var(--transition);
    position: relative;
}
.card:hover {
    border-color: var(--border-light);
    transform: translateY(-4px);
    box-shadow: var(--shadow-lg);
}
.card.live {
    border-color: rgba(239, 68, 68, 0.3);
}
.card.live:hover {
    border-color: rgba(239, 68, 68, 0.5);
    box-shadow: 0 12px 48px rgba(239, 68, 68, 0.15);
}

.thumb {
    height: 180px;
    background: var(--surface-2);
    position: relative;
    overflow: hidden;
}
.thumb img {
    width: 100%;
    height: 100%;
    object-fit: cover;
    transition: transform 0.4s ease;
}
.card:hover .thumb img {
    transform: scale(1.05);
}
.thumb .no-img {
    display: flex;
    align-items: center;
    justify-content: center;
    height: 100%;
    color: var(--border-light);
    font-size: 2.5rem;
}
.thumb::after {
    content: '';
    position: absolute;
    bottom: 0;
    left: 0;
    right: 0;
    height: 60px;
    background: linear-gradient(transparent, rgba(17, 17, 24, 0.8));
    pointer-events: none;
}

.badge {
    position: absolute;
    top: 0.75rem;
    left: 0.75rem;
    padding: 0.2rem 0.65rem;
    border-radius: var(--radius-pill);
    font-size: 0.65rem;
    font-weight: 700;
    text-transform: uppercase;
    letter-spacing: 0.05em;
    color: #fff;
    z-index: 2;
}
.badge-live {
    background: var(--danger);
    animation: pulse 1.5s ease-in-out infinite;
    box-shadow: 0 2px 12px rgba(239, 68, 68, 0.4);
    display: flex;
    align-items: center;
    gap: 0.3rem;
}
.badge-live::before {
    content: '';
    width: 6px;
    height: 6px;
    background: #fff;
    border-radius: 50%;
}
.badge-public { background: var(--success); }
.badge-lifestyle { background: var(--warning); }
.badge-nsfw { background: #9333ea; }

.viewers-badge {
    position: absolute;
    top: 0.75rem;
    right: 0.75rem;
    background: rgba(0, 0, 0, 0.65);
    backdrop-filter: blur(8px);
    color: #fff;
    padding: 0.2rem 0.6rem;
    border-radius: var(--radius-pill);
    font-size: 0.75rem;
    font-weight: 500;
    display: flex;
    align-items: center;
    gap: 0.3rem;
    z-index: 2;
}

.card-body { padding: 1.1rem 1.2rem; }

.card-title {
    font-weight: 600;
    font-size: 0.95rem;
    margin-bottom: 0.35rem;
    white-space: nowrap;
    overflow: hidden;
    text-overflow: ellipsis;
}
.card-meta {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 0.5rem;
}
.card-tag {
    color: var(--primary-light);
    font-size: 0.8rem;
    font-weight: 500;
}
.card-creator {
    color: var(--muted);
    font-size: 0.8rem;
    display: flex;
    align-items: center;
    gap: 0.3rem;
}
.card-stats {
    display: flex;
    gap: 1.25rem;
    font-size: 0.8rem;
    color: var(--muted);
    padding-top: 0.6rem;
    border-top: 1px solid var(--border);
}
.card-stats span {
    display: flex;
    align-items: center;
    gap: 0.3rem;
}

/* ── Features Section ── */
.features {
    padding: 6rem 0;
    position: relative;
}
.features::before {
    content: '';
    position: absolute;
    inset: 0;
    background: linear-gradient(180deg, var(--bg), var(--surface), var(--bg));
    z-index: -1;
}

.features-header {
    text-align: center;
    margin-bottom: 4rem;
}
.features-header h2 {
    font-size: 2rem;
    font-weight: 700;
    margin-bottom: 0.75rem;
    letter-spacing: -0.02em;
}
.features-header p {
    color: var(--text-secondary);
    font-size: 1.05rem;
    max-width: 500px;
    margin: 0 auto;
}

.features-grid {
    display: grid;
    grid-template-columns: repeat(3, 1fr);
    gap: 1.5rem;
    max-width: 1100px;
    margin: 0 auto;
}

.feature-card {
    background: var(--surface);
    border: 1px solid var(--border);
    border-radius: var(--radius);
    padding: 2rem;
    transition: var(--transition);
    position: relative;
    overflow: hidden;
}
.feature-card:hover {
    border-color: var(--border-light);
    transform: translateY(-4px);
    box-shadow: var(--shadow);
}
.feature-card::before {
    content: '';
    position: absolute;
    top: 0;
    left: 0;
    right: 0;
    height: 2px;
    background: var(--gradient-primary);
    opacity: 0;
    transition: var(--transition);
}
.feature-card:hover::before { opacity: 1; }

.feature-icon {
    width: 52px;
    height: 52px;
    border-radius: 12px;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 1.25rem;
    margin-bottom: 1.25rem;
}
.feature-icon.indigo {
    background: var(--primary-glow);
    color: var(--primary-light);
}
.feature-icon.emerald {
    background: rgba(16, 185, 129, 0.12);
    color: var(--success);
}
.feature-icon.rose {
    background: var(--accent-glow);
    color: var(--accent);
}

.feature-card h3 {
    font-size: 1.1rem;
    font-weight: 600;
    margin-bottom: 0.5rem;
    letter-spacing: -0.01em;
}
.feature-card p {
    color: var(--text-secondary);
    font-size: 0.9rem;
    line-height: 1.6;
}

/* ── CTA Banner ── */
.cta {
    padding: 5rem 0;
}
.cta-inner {
    background: var(--surface);
    border: 1px solid var(--border);
    border-radius: 20px;
    padding: 4rem;
    text-align: center;
    position: relative;
    overflow: hidden;
}
.cta-inner::before {
    content: '';
    position: absolute;
    top: -100px;
    left: 50%;
    transform: translateX(-50%);
    width: 400px;
    height: 400px;
    background: radial-gradient(circle, rgba(99, 102, 241, 0.1) 0%, transparent 70%);
}
.cta h2 {
    font-size: 2rem;
    font-weight: 700;
    margin-bottom: 0.75rem;
    position: relative;
    letter-spacing: -0.02em;
}
.cta p {
    color: var(--text-secondary);
    font-size: 1.05rem;
    margin-bottom: 2rem;
    position: relative;
}
.cta .btn { position: relative; }

/* ── Footer ── */
.footer {
    padding: 3rem 2rem;
    border-top: 1px solid var(--border);
}
.footer-inner {
    max-width: 1320px;
    margin: 0 auto;
    display: flex;
    justify-content: space-between;
    align-items: center;
}
.footer-copy {
    color: var(--muted);
    font-size: 0.85rem;
}
.footer-links {
    display: flex;
    gap: 1.5rem;
}
.footer-links a {
    color: var(--muted);
    font-size: 0.85rem;
    transition: var(--transition);
}
.footer-links a:hover { color: var(--text); }

/* ── Mobile ── */
@media (max-width: 768px) {
    .header { padding: 0 1.25rem; }
    .nav {
        display: none;
        position: fixed;
        top: 64px;
        left: 0;
        right: 0;
        background: var(--surface);
        border-bottom: 1px solid var(--border);
        flex-direction: column;
        padding: 1rem;
        gap: 0.25rem;
    }
    .nav.open { display: flex; }
    .nav a {
        padding: 0.75rem 1rem;
        border-radius: var(--radius-sm);
    }
    .mobile-toggle { display: block; }
    .hero { padding: 8rem 1.5rem 4rem; min-height: auto; }
    .hero h1 { font-size: 2.2rem; }
    .hero p { font-size: 1rem; }
    .hero-stats { gap: 1.5rem; }
    .hero-stat .num { font-size: 1.2rem; }
    .features-grid { grid-template-columns: 1fr; }
    .grid { grid-template-columns: 1fr; }
    .section { padding: 3rem 0; }
    .cta-inner { padding: 2.5rem 1.5rem; }
    .footer-inner { flex-direction: column; gap: 1rem; text-align: center; }
}
</style>
</head>
<body>

<!-- Header -->
<header class="header" id="header">
    <a href="./" class="logo">
        <div class="logo-icon"><i class="fa-solid fa-cube"></i></div>
        <div class="logo-text">Omni<span>Grid</span></div>
    </a>
    <button class="mobile-toggle" onclick="document.querySelector('.nav').classList.toggle('open')" aria-label="Menu">
        <i class="fa-solid fa-bars"></i>
    </button>
    <nav class="nav" id="nav">
        <a href="globe.php"><i class="fa-solid fa-earth-americas"></i> Globe</a>
        <a href="watch.php"><i class="fa-solid fa-play"></i> Browse</a>
        <a href="calculator.html"><i class="fa-solid fa-calculator"></i> Earnings</a>
        <a href="login.php"><i class="fa-solid fa-arrow-right-to-bracket"></i> Login</a>
        <a href="contribute/" class="btn"><i class="fa-solid fa-broadcast-tower"></i> Go Live</a>
    </nav>
</header>

<!-- Hero -->
<section class="hero">
    <div class="hero-bg">
        <div class="hero-grid-lines"></div>
    </div>
    <div class="hero-content">
        <div class="hero-badge">
            <span class="dot"></span>
            <span><strong><?= count($live) ?></strong> streams live now</span>
        </div>
        <h1>Live Streams from <span class="gradient">Everywhere</span></h1>
        <p>Discover authentic moments from creators around the world. Watch, connect, and earn with the smartGrid engine.</p>
        <div class="hero-btns">
            <a href="globe.php" class="btn btn-lg"><i class="fa-solid fa-earth-americas"></i> Spin the Globe</a>
            <a href="watch.php" class="btn btn-outline btn-lg"><i class="fa-solid fa-play"></i> Browse Streams</a>
        </div>
        <div class="hero-stats">
            <div class="hero-stat">
                <div class="num"><?= number_format(count($streams)) ?></div>
                <div class="label">Active Streams</div>
            </div>
            <div class="hero-stat">
                <div class="num"><?= count($live) ?></div>
                <div class="label">Live Now</div>
            </div>
            <div class="hero-stat">
                <div class="num"><?php
                    $totalViews = array_sum(array_column($streams, 'views'));
                    echo $totalViews >= 1000000 ? number_format($totalViews / 1000000, 1) . 'M' : ($totalViews >= 1000 ? number_format($totalViews / 1000, 1) . 'K' : $totalViews);
                ?></div>
                <div class="label">Total Views</div>
            </div>
        </div>
    </div>
</section>

<!-- Streams -->
<main class="container" id="streams">
    <?php if (count($live) > 0): ?>
    <section class="section">
        <div class="section-header">
            <h2><span class="live-dot"></span> Live Now</h2>
            <a href="globe.php" class="view-all">View all <i class="fa-solid fa-arrow-right"></i></a>
        </div>
        <div class="grid">
            <?php foreach ($live as $s): ?>
            <a href="live.php?id=<?= $s['id'] ?>" class="card live">
                <div class="thumb">
                    <?php if ($s['thumb_url']): ?>
                        <img src="<?= htmlspecialchars($s['thumb_url']) ?>" alt="<?= htmlspecialchars($s['title']) ?>">
                    <?php else: ?>
                        <div class="no-img"><i class="fa-solid fa-video"></i></div>
                    <?php endif; ?>
                    <span class="badge badge-live">Live</span>
                    <span class="viewers-badge"><i class="fa-solid fa-eye"></i> <?= number_format($s['views'] ?? 0) ?></span>
                </div>
                <div class="card-body">
                    <div class="card-title"><?= htmlspecialchars($s['title']) ?></div>
                    <div class="card-meta">
                        <span class="card-tag"><?= htmlspecialchars($s['vibe_tag'] ?: $s['type']) ?></span>
                        <span class="card-creator"><i class="fa-solid fa-user"></i> <?= htmlspecialchars($s['display_name'] ?: 'Anonymous') ?></span>
                    </div>
                </div>
            </a>
            <?php endforeach; ?>
        </div>
    </section>
    <?php endif; ?>

    <section class="section">
        <div class="section-header">
            <h2><i class="fa-solid fa-fire" style="color: var(--warning)"></i> Popular Streams</h2>
        </div>
        <div class="grid">
            <?php foreach ($offline as $s): ?>
            <a href="live.php?id=<?= $s['id'] ?>" class="card">
                <div class="thumb">
                    <?php if ($s['thumb_url']): ?>
                        <img src="<?= htmlspecialchars($s['thumb_url']) ?>" alt="<?= htmlspecialchars($s['title']) ?>">
                    <?php else: ?>
                        <div class="no-img"><i class="fa-solid fa-video"></i></div>
                    <?php endif; ?>
                    <span class="badge badge-<?= htmlspecialchars($s['type']) ?>"><?= htmlspecialchars($s['type']) ?></span>
                    <span class="viewers-badge"><i class="fa-solid fa-eye"></i> <?= number_format($s['views'] ?? 0) ?></span>
                </div>
                <div class="card-body">
                    <div class="card-title"><?= htmlspecialchars($s['title']) ?></div>
                    <div class="card-meta">
                        <span class="card-tag"><?= htmlspecialchars($s['vibe_tag'] ?: $s['type']) ?></span>
                        <span class="card-creator"><i class="fa-solid fa-user"></i> <?= htmlspecialchars($s['display_name'] ?: 'Anonymous') ?></span>
                    </div>
                    <div class="card-stats">
                        <span><i class="fa-solid fa-users"></i> <?= $s['subs_count'] ?? 0 ?> subs</span>
                        <span><i class="fa-solid fa-coins"></i> $<?= number_format(($s['tips_cents'] ?? 0) / 100, 2) ?> tips</span>
                    </div>
                </div>
            </a>
            <?php endforeach; ?>
        </div>
    </section>
</main>

<!-- Features -->
<section class="features">
    <div class="container">
        <div class="features-header">
            <h2>Why OmniGrid?</h2>
            <p>Built for creators who want to earn, connect, and grow their audience globally.</p>
        </div>
        <div class="features-grid">
            <div class="feature-card">
                <div class="feature-icon indigo"><i class="fa-solid fa-bolt"></i></div>
                <h3>smartGrid Earnings</h3>
                <p>Dynamic payouts that adapt to your engagement in real time. More viewers, better retention &mdash; higher rates automatically.</p>
            </div>
            <div class="feature-card">
                <div class="feature-icon emerald"><i class="fa-solid fa-earth-americas"></i></div>
                <h3>Global Discovery</h3>
                <p>An interactive 3D globe lets viewers discover streams from creators worldwide. Spin and drop into a random live feed.</p>
            </div>
            <div class="feature-card">
                <div class="feature-icon rose"><i class="fa-solid fa-video"></i></div>
                <h3>Instant Streaming</h3>
                <p>Go live directly from your browser with WebRTC. No software to install, no setup required &mdash; just press Go Live.</p>
            </div>
        </div>
    </div>
</section>

<!-- CTA -->
<section class="cta">
    <div class="container">
        <div class="cta-inner">
            <h2>Ready to go live?</h2>
            <p>Join thousands of creators earning with smartGrid. Start streaming in seconds.</p>
            <a href="register.php" class="btn btn-lg"><i class="fa-solid fa-rocket"></i> Create Free Account</a>
        </div>
    </div>
</section>

<!-- Footer -->
<footer class="footer">
    <div class="footer-inner">
        <div class="footer-copy">&copy; <?= date('Y') ?> OmniGrid. All rights reserved.</div>
        <div class="footer-links">
            <a href="specs.html">API</a>
            <a href="globe.php">Globe</a>
            <a href="contribute/">Creators</a>
        </div>
    </div>
</footer>

<script>
// Scroll header effect
const header = document.getElementById('header');
window.addEventListener('scroll', () => {
    header.classList.toggle('scrolled', window.scrollY > 20);
});

// Close mobile nav on link click
document.querySelectorAll('.nav a').forEach(a => {
    a.addEventListener('click', () => {
        document.getElementById('nav').classList.remove('open');
    });
});
</script>
</body>
</html>
