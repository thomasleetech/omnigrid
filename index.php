<?php
require_once 'includes/db.php';
$stmt = $pdo->query("SELECT s.*, m.views, m.subs_count, m.tips_cents, u.display_name FROM streams s LEFT JOIN stream_metrics m ON s.id = m.stream_id LEFT JOIN users u ON s.user_id = u.id WHERE s.is_active = 1 ORDER BY s.is_live DESC, m.views DESC LIMIT 12");
$streams = $stmt->fetchAll();
$live = array_filter($streams, fn($s) => $s['is_live']);
?>
<!DOCTYPE html>
<html lang="en">
<head>
<script src="https://checkoutmyvibes.com/invitegate/gate4.js?v=4.2" data-app="omnigrid"></script>
 
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width,initial-scale=1.0">
<title>OmniGrid | Live Streams from Everywhere</title>
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
<style>
:root{--bg:#f8f9fc;--surface:#fff;--border:#e5e7eb;--text:#1f2937;--muted:#6b7280;--primary:#6366f1;--success:#10b981;--danger:#ef4444}
*{box-sizing:border-box;margin:0;padding:0}
body{font-family:-apple-system,BlinkMacSystemFont,'Segoe UI',Roboto,sans-serif;background:var(--bg);color:var(--text);line-height:1.5}
a{text-decoration:none;color:inherit}
.header{background:var(--surface);border-bottom:1px solid var(--border);padding:0.75rem 2rem;display:flex;justify-content:space-between;align-items:center;position:sticky;top:0;z-index:100}
.logo{font-size:1.4rem;font-weight:700;display:flex;align-items:center;gap:0.5rem}.logo span{color:var(--primary)}
.nav{display:flex;gap:1.5rem;align-items:center}
.nav a{color:var(--muted);font-size:0.9rem;transition:0.2s}.nav a:hover{color:var(--text)}
.btn{background:var(--primary);color:#fff;padding:0.5rem 1.25rem;border-radius:8px;font-size:0.85rem;display:inline-flex;align-items:center;gap:0.4rem;border:none;cursor:pointer;transition:0.2s}
.btn:hover{filter:brightness(1.1)}
.btn-outline{background:transparent;border:1px solid var(--border);color:var(--text)}
.hero{padding:4rem 2rem;text-align:center;background:linear-gradient(135deg,#eef2ff 0%,#faf5ff 100%)}
.hero h1{font-size:2.75rem;font-weight:700;margin-bottom:0.75rem;background:linear-gradient(135deg,var(--primary),#8b5cf6);-webkit-background-clip:text;-webkit-text-fill-color:transparent}
.hero p{color:var(--muted);font-size:1.1rem;max-width:500px;margin:0 auto 2rem}
.hero-btns{display:flex;gap:1rem;justify-content:center;flex-wrap:wrap}
.hero-btns .btn{padding:0.75rem 2rem;font-size:1rem}
.container{max-width:1400px;margin:0 auto;padding:2rem}
.section-header{display:flex;justify-content:space-between;align-items:center;margin-bottom:1.25rem}
.section-header h2{font-size:1.25rem;font-weight:600;display:flex;align-items:center;gap:0.5rem}
.section-header h2 .live-dot{width:10px;height:10px;background:var(--danger);border-radius:50%;animation:pulse 1.5s infinite}
@keyframes pulse{0%,100%{opacity:1}50%{opacity:0.5}}
.grid{display:grid;grid-template-columns:repeat(auto-fill,minmax(300px,1fr));gap:1.25rem}
.card{background:var(--surface);border:1px solid var(--border);border-radius:12px;overflow:hidden;transition:0.2s}
.card:hover{box-shadow:0 8px 30px rgba(0,0,0,0.08);transform:translateY(-2px)}
.card.live{border-color:var(--danger)}
.thumb{height:170px;background:#f1f5f9;position:relative}
.thumb img{width:100%;height:100%;object-fit:cover}
.thumb .no-img{display:flex;align-items:center;justify-content:center;height:100%;color:var(--border);font-size:2.5rem}
.badge{position:absolute;top:0.6rem;left:0.6rem;padding:0.2rem 0.6rem;border-radius:6px;font-size:0.7rem;font-weight:600;text-transform:uppercase;color:#fff}
.badge-live{background:var(--danger);animation:pulse 1.5s infinite}
.badge-public{background:var(--success)}
.badge-lifestyle{background:#f59e0b}
.badge-nsfw{background:#9333ea}
.viewers{position:absolute;top:0.6rem;right:0.6rem;background:rgba(0,0,0,0.6);color:#fff;padding:0.2rem 0.5rem;border-radius:6px;font-size:0.75rem}
.card-body{padding:1rem}
.card-title{font-weight:600;margin-bottom:0.2rem;white-space:nowrap;overflow:hidden;text-overflow:ellipsis}
.card-meta{display:flex;justify-content:space-between;align-items:center}
.card-tag{color:var(--primary);font-size:0.85rem}
.card-creator{color:var(--muted);font-size:0.8rem}
.card-stats{display:flex;gap:1rem;margin-top:0.6rem;font-size:0.8rem;color:var(--muted)}
.features{padding:4rem 2rem;background:var(--surface);border-top:1px solid var(--border)}
.features-grid{display:grid;grid-template-columns:repeat(3,1fr);gap:2rem;max-width:1000px;margin:0 auto}
.feature{text-align:center;padding:1.5rem}
.feature-icon{width:60px;height:60px;background:linear-gradient(135deg,#eef2ff,#faf5ff);border-radius:16px;display:flex;align-items:center;justify-content:center;margin:0 auto 1rem;font-size:1.5rem;color:var(--primary)}
.feature h3{font-size:1.1rem;margin-bottom:0.5rem}
.feature p{color:var(--muted);font-size:0.9rem}
.footer{padding:2rem;text-align:center;color:var(--muted);font-size:0.85rem;border-top:1px solid var(--border)}
@media(max-width:768px){.hero h1{font-size:2rem}.features-grid{grid-template-columns:1fr}.nav{display:none}}
</style>
</head>
<body>
<header class="header">
    <a href="./" class="logo"><i class="fa fa-cube" style="color:var(--primary)"></i> Omni<span>Grid</span></a>
    <nav class="nav">
        <a href="globe.php"><i class="fa fa-globe"></i> Globe</a>
        <a href="#streams">Browse</a>
        <a href="login.php">Login</a>
        <a href="contribute/" class="btn"><i class="fa fa-broadcast-tower"></i> Go Live</a>
    </nav>
</header>

<section class="hero">
    <h1>Live Streams from Everywhere</h1>
    <p>Discover authentic moments from creators around the world. Watch, connect, and earn with smartGrid.</p>
    <div class="hero-btns">
        <a href="globe.php" class="btn"><i class="fa fa-globe"></i> Spin the Globe</a>
        <a href="#streams" class="btn btn-outline"><i class="fa fa-play"></i> Browse Streams</a>
    </div>
</section>

<main class="container" id="streams">
    <?php if (count($live) > 0): ?>
    <section style="margin-bottom:3rem">
        <div class="section-header">
            <h2><span class="live-dot"></span> Live Now</h2>
            <a href="#" style="color:var(--primary);font-size:0.9rem">View all</a>
        </div>
        <div class="grid">
            <?php foreach ($live as $s): ?>
            <a href="live.php?id=<?= $s['id'] ?>" class="card live">
                <div class="thumb">
                    <?php if ($s['thumb_url']): ?><img src="<?= htmlspecialchars($s['thumb_url']) ?>" alt=""><?php else: ?><div class="no-img"><i class="fa fa-video"></i></div><?php endif; ?>
                    <span class="badge badge-live">Live</span>
                    <span class="viewers"><i class="fa fa-eye"></i> <?= number_format($s['views'] ?? 0) ?></span>
                </div>
                <div class="card-body">
                    <div class="card-title"><?= htmlspecialchars($s['title']) ?></div>
                    <div class="card-meta">
                        <span class="card-tag"><?= htmlspecialchars($s['vibe_tag'] ?: $s['type']) ?></span>
                        <span class="card-creator"><?= htmlspecialchars($s['display_name'] ?: 'Anonymous') ?></span>
                    </div>
                </div>
            </a>
            <?php endforeach; ?>
        </div>
    </section>
    <?php endif; ?>

    <section>
        <div class="section-header">
            <h2><i class="fa fa-fire" style="color:#f59e0b"></i> Popular Streams</h2>
        </div>
        <div class="grid">
            <?php foreach ($streams as $s): if ($s['is_live']) continue; ?>
            <a href="live.php?id=<?= $s['id'] ?>" class="card">
                <div class="thumb">
                    <?php if ($s['thumb_url']): ?><img src="<?= htmlspecialchars($s['thumb_url']) ?>" alt=""><?php else: ?><div class="no-img"><i class="fa fa-video"></i></div><?php endif; ?>
                    <span class="badge badge-<?= $s['type'] ?>"><?= $s['type'] ?></span>
                    <span class="viewers"><i class="fa fa-eye"></i> <?= number_format($s['views'] ?? 0) ?></span>
                </div>
                <div class="card-body">
                    <div class="card-title"><?= htmlspecialchars($s['title']) ?></div>
                    <div class="card-meta">
                        <span class="card-tag"><?= htmlspecialchars($s['vibe_tag'] ?: $s['type']) ?></span>
                        <span class="card-creator"><?= htmlspecialchars($s['display_name'] ?: 'Anonymous') ?></span>
                    </div>
                    <div class="card-stats">
                        <span><i class="fa fa-users"></i> <?= $s['subs_count'] ?? 0 ?> subs</span>
                        <span><i class="fa fa-dollar-sign"></i> <?= number_format(($s['tips_cents'] ?? 0) / 100, 2) ?> tips</span>
                    </div>
                </div>
            </a>
            <?php endforeach; ?>
        </div>
    </section>
</main>

<section class="features">
    <div class="features-grid">
        <div class="feature">
            <div class="feature-icon"><i class="fa fa-bolt"></i></div>
            <h3>smartGrid Earnings</h3>
            <p>Dynamic payouts that adapt to your engagement. More viewers = higher rates.</p>
        </div>
        <div class="feature">
            <div class="feature-icon"><i class="fa fa-globe"></i></div>
            <h3>Global Discovery</h3>
            <p>Spin the globe and discover authentic streams from creators worldwide.</p>
        </div>
        <div class="feature">
            <div class="feature-icon"><i class="fa fa-video"></i></div>
            <h3>Instant Streaming</h3>
            <p>Go live directly from your browser. No apps, no setup, just stream.</p>
        </div>
    </div>
</section>

<footer class="footer">
    <p>&copy; <?= date('Y') ?> OmniGrid. All rights reserved.</p>
</footer>
</body>
</html>