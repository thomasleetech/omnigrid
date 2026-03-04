<?php
session_start();
if (empty($_SESSION['user_id'])) { header('Location: ../login.php'); exit; }
require_once '../includes/db.php';
$stmt = $pdo->prepare("SELECT * FROM users WHERE id = ?");
$stmt->execute([$_SESSION['user_id']]);
$user = $stmt->fetch();
if (!$user) { header('Location: ../login.php'); exit; }
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width,initial-scale=1.0">
<title>Creator Studio — OmniGrid</title>
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

/* Layout */
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
    width: 30px;
    height: 30px;
    background: var(--gradient-primary);
    border-radius: 7px;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 0.75rem;
    color: #fff;
    box-shadow: 0 4px 12px rgba(99, 102, 241, 0.3);
}
.logo span { color: var(--primary-light); }

.nav-section {
    font-size: 0.65rem;
    text-transform: uppercase;
    letter-spacing: 0.1em;
    color: var(--muted);
    margin: 1.25rem 0 0.5rem 0.5rem;
    font-weight: 600;
}
.nav-item {
    display: flex;
    align-items: center;
    gap: 0.65rem;
    padding: 0.6rem 0.85rem;
    border-radius: var(--radius-sm);
    color: var(--text-secondary);
    text-decoration: none;
    margin-bottom: 0.15rem;
    font-size: 0.85rem;
    font-weight: 500;
    transition: var(--transition);
    cursor: pointer;
}
.nav-item i { width: 18px; font-size: 0.85rem; text-align: center; }
.nav-item:hover { background: var(--primary-glow); color: var(--text); }
.nav-item.active { background: var(--primary-glow); color: var(--primary-light); }

.nav-divider {
    height: 1px;
    background: var(--border);
    margin: 1rem 0;
}

.user-card {
    margin-top: auto;
    display: flex;
    align-items: center;
    gap: 0.7rem;
    padding: 0.85rem;
    background: var(--bg);
    border-radius: var(--radius-sm);
    border: 1px solid var(--border);
}
.user-avatar {
    width: 34px;
    height: 34px;
    background: var(--gradient-primary);
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 0.8rem;
    color: #fff;
    flex-shrink: 0;
}
.user-info { min-width: 0; }
.user-name {
    font-size: 0.85rem;
    font-weight: 600;
    white-space: nowrap;
    overflow: hidden;
    text-overflow: ellipsis;
}
.user-role {
    font-size: 0.7rem;
    color: var(--muted);
}

/* Main Content */
.main {
    padding: 2rem 2.5rem;
    overflow-y: auto;
}

.page-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 2rem;
}
.page-header h1 {
    font-size: 1.5rem;
    font-weight: 700;
    letter-spacing: -0.02em;
}
.page-header .subtitle {
    color: var(--muted);
    font-size: 0.85rem;
    margin-top: 0.2rem;
}

.btn {
    background: var(--gradient-primary);
    color: #fff;
    border: none;
    padding: 0.55rem 1.25rem;
    border-radius: var(--radius-sm);
    cursor: pointer;
    font-family: inherit;
    font-size: 0.85rem;
    font-weight: 600;
    display: inline-flex;
    align-items: center;
    gap: 0.45rem;
    text-decoration: none;
    transition: var(--transition);
    box-shadow: 0 4px 16px rgba(99, 102, 241, 0.2);
    white-space: nowrap;
}
.btn:hover { transform: translateY(-1px); box-shadow: 0 6px 24px rgba(99, 102, 241, 0.3); }
.btn-outline {
    background: transparent;
    border: 1px solid var(--border);
    color: var(--text-secondary);
    box-shadow: none;
}
.btn-outline:hover { border-color: var(--border-light); color: var(--text); box-shadow: none; transform: none; }
.btn-sm { padding: 0.4rem 0.8rem; font-size: 0.8rem; }
.btn-success { background: var(--success); box-shadow: 0 4px 16px rgba(16,185,129,0.2); }
.btn-danger { background: var(--danger); box-shadow: 0 4px 16px rgba(239,68,68,0.2); }

/* Stats Grid */
.stats-grid {
    display: grid;
    grid-template-columns: repeat(4, 1fr);
    gap: 1rem;
    margin-bottom: 2rem;
}
.stat-card {
    background: var(--surface);
    border: 1px solid var(--border);
    border-radius: var(--radius);
    padding: 1.25rem;
    transition: var(--transition);
}
.stat-card:hover { border-color: var(--border-light); }
.stat-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 0.75rem;
}
.stat-header .label {
    font-size: 0.78rem;
    color: var(--muted);
    font-weight: 500;
}
.stat-header .icon {
    width: 32px;
    height: 32px;
    border-radius: var(--radius-sm);
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 0.8rem;
}
.stat-header .icon.green { background: var(--success-glow); color: var(--success); }
.stat-header .icon.blue { background: var(--primary-glow); color: var(--primary-light); }
.stat-header .icon.yellow { background: var(--warning-glow); color: var(--warning); }
.stat-header .icon.red { background: var(--danger-glow); color: var(--danger); }

.stat-value {
    font-family: 'JetBrains Mono', monospace;
    font-size: 1.6rem;
    font-weight: 700;
    letter-spacing: -0.02em;
}

/* Section Header */
.section-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 1.25rem;
}
.section-header h2 {
    font-size: 1.1rem;
    font-weight: 600;
    letter-spacing: -0.01em;
}

/* Stream Grid */
.stream-grid {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(300px, 1fr));
    gap: 1.25rem;
}

.stream-card {
    background: var(--surface);
    border: 1px solid var(--border);
    border-radius: var(--radius);
    overflow: hidden;
    transition: var(--transition);
}
.stream-card:hover { border-color: var(--border-light); transform: translateY(-2px); box-shadow: var(--shadow); }
.stream-card.live { border-color: rgba(239, 68, 68, 0.3); }
.stream-card.live:hover { border-color: rgba(239, 68, 68, 0.5); }

.stream-thumb {
    height: 140px;
    background: var(--surface-2);
    position: relative;
    overflow: hidden;
}
.stream-thumb img { width: 100%; height: 100%; object-fit: cover; transition: transform 0.4s; }
.stream-card:hover .stream-thumb img { transform: scale(1.05); }
.stream-thumb .no-thumb {
    display: flex;
    align-items: center;
    justify-content: center;
    height: 100%;
    color: var(--border-light);
    font-size: 2rem;
}

.stream-badge {
    position: absolute;
    top: 0.6rem;
    left: 0.6rem;
    padding: 0.15rem 0.55rem;
    border-radius: var(--radius-pill);
    font-size: 0.65rem;
    font-weight: 700;
    text-transform: uppercase;
    letter-spacing: 0.05em;
    color: #fff;
}
.badge-live {
    background: var(--danger);
    animation: pulse 1.5s ease-in-out infinite;
    display: flex; align-items: center; gap: 0.25rem;
}
.badge-live::before { content: ''; width: 5px; height: 5px; background: #fff; border-radius: 50%; }
.badge-public { background: var(--success); }
.badge-lifestyle { background: var(--warning); }
.badge-nsfw { background: #9333ea; }
@keyframes pulse { 0%,100%{opacity:1}50%{opacity:0.6} }

.stream-viewers {
    position: absolute;
    top: 0.6rem;
    right: 0.6rem;
    background: rgba(0,0,0,0.65);
    backdrop-filter: blur(8px);
    padding: 0.15rem 0.5rem;
    border-radius: var(--radius-pill);
    font-size: 0.7rem;
    display: flex;
    align-items: center;
    gap: 0.25rem;
}

.stream-body { padding: 1rem 1.15rem; }
.stream-title { font-weight: 600; font-size: 0.95rem; margin-bottom: 0.2rem; }
.stream-tag { color: var(--primary-light); font-size: 0.8rem; font-weight: 500; }
.stream-stats {
    display: flex;
    gap: 1rem;
    margin-top: 0.6rem;
    font-size: 0.78rem;
    color: var(--muted);
}
.stream-stats span { display: flex; align-items: center; gap: 0.25rem; }

.stream-earnings {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-top: 0.75rem;
    padding-top: 0.75rem;
    border-top: 1px solid var(--border);
}
.earnings-value {
    font-family: 'JetBrains Mono', monospace;
    font-size: 1.1rem;
    font-weight: 700;
    color: var(--success);
}
.smartgrid-rate {
    font-size: 0.72rem;
    color: var(--warning);
    font-weight: 600;
    background: var(--warning-glow);
    padding: 0.15rem 0.5rem;
    border-radius: var(--radius-pill);
}

.stream-actions {
    display: flex;
    gap: 0.4rem;
    margin-top: 0.85rem;
}
.stream-actions .btn { flex: 1; justify-content: center; }

/* Empty State */
.empty-state {
    text-align: center;
    padding: 4rem 2rem;
    color: var(--muted);
}
.empty-state .empty-icon {
    width: 72px;
    height: 72px;
    background: var(--surface);
    border: 1px solid var(--border);
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 1.75rem;
    margin: 0 auto 1rem;
}
.empty-state p { margin-bottom: 1.25rem; }

/* Modal */
.modal {
    display: none;
    position: fixed;
    inset: 0;
    background: rgba(0, 0, 0, 0.85);
    backdrop-filter: blur(4px);
    z-index: 1000;
    align-items: center;
    justify-content: center;
}
.modal.open { display: flex; }

/* Studio Modal */
.studio {
    width: 100%;
    max-width: 1100px;
    height: 85vh;
    display: grid;
    grid-template-columns: 1fr 320px;
    background: var(--surface);
    border: 1px solid var(--border);
    border-radius: 16px;
    overflow: hidden;
    box-shadow: var(--shadow-lg);
}

.studio-preview { background: #000; position: relative; }
.studio-preview video { width: 100%; height: 100%; object-fit: contain; }

.studio-overlay {
    position: absolute;
    top: 1rem;
    left: 1rem;
    right: 1rem;
    display: flex;
    justify-content: space-between;
    pointer-events: none;
}
.live-ind {
    background: rgba(0,0,0,0.6);
    backdrop-filter: blur(8px);
    padding: 0.35rem 0.9rem;
    border-radius: var(--radius-pill);
    font-size: 0.8rem;
    font-weight: 700;
    display: none;
    align-items: center;
    gap: 0.4rem;
    color: #fff;
}
.live-ind.active {
    display: flex;
    background: var(--danger);
}
.live-ind::before {
    content: '';
    width: 7px;
    height: 7px;
    background: #fff;
    border-radius: 50%;
    animation: pulse 1s ease-in-out infinite;
}
.viewer-ct {
    background: rgba(0,0,0,0.6);
    backdrop-filter: blur(8px);
    padding: 0.35rem 0.75rem;
    border-radius: var(--radius-pill);
    font-size: 0.8rem;
    display: flex;
    align-items: center;
    gap: 0.3rem;
}

.studio-ctrl {
    position: absolute;
    bottom: 1.75rem;
    left: 50%;
    transform: translateX(-50%);
    display: flex;
    gap: 0.7rem;
    pointer-events: auto;
}
.ctrl-btn {
    width: 48px;
    height: 48px;
    border-radius: 50%;
    border: 1px solid rgba(255,255,255,0.15);
    cursor: pointer;
    font-size: 1rem;
    background: rgba(255,255,255,0.08);
    backdrop-filter: blur(8px);
    color: #fff;
    transition: var(--transition);
    display: flex;
    align-items: center;
    justify-content: center;
}
.ctrl-btn:hover { background: rgba(255,255,255,0.15); }
.ctrl-btn.live-btn {
    background: var(--danger);
    border-color: var(--danger);
    width: 60px;
    height: 60px;
    font-size: 1.3rem;
    box-shadow: 0 4px 20px rgba(239, 68, 68, 0.3);
}
.ctrl-btn.live-btn:hover { box-shadow: 0 6px 28px rgba(239, 68, 68, 0.4); }
.ctrl-btn.live-btn.streaming {
    background: rgba(255,255,255,0.1);
    border-color: rgba(255,255,255,0.2);
    box-shadow: none;
}

/* Studio Panel */
.studio-panel {
    background: var(--surface);
    border-left: 1px solid var(--border);
    display: flex;
    flex-direction: column;
}
.panel-header {
    padding: 1rem 1.25rem;
    border-bottom: 1px solid var(--border);
    display: flex;
    justify-content: space-between;
    align-items: center;
}
.panel-header h2 { font-size: 0.95rem; font-weight: 600; }
.panel-close {
    background: none;
    border: none;
    color: var(--muted);
    font-size: 1.3rem;
    cursor: pointer;
    padding: 0.25rem;
    transition: var(--transition);
}
.panel-close:hover { color: var(--text); }

.panel-body { flex: 1; overflow-y: auto; padding: 1.25rem; }

.room-display {
    background: var(--bg);
    border: 1px solid var(--border);
    padding: 1rem;
    border-radius: var(--radius-sm);
    text-align: center;
    margin-bottom: 1.25rem;
}
.room-display label {
    font-size: 0.65rem;
    color: var(--muted);
    text-transform: uppercase;
    letter-spacing: 0.1em;
    font-weight: 600;
}
.room-display .code {
    font-family: 'JetBrains Mono', monospace;
    font-size: 1.6rem;
    font-weight: 700;
    color: var(--primary-light);
    letter-spacing: 0.15em;
    margin: 0.25rem 0;
}
.room-display .url {
    font-size: 0.6rem;
    color: var(--muted);
    word-break: break-all;
}

.form-group { margin-bottom: 1rem; }
.form-group label {
    display: block;
    font-size: 0.78rem;
    color: var(--text-secondary);
    margin-bottom: 0.3rem;
    font-weight: 500;
}
.form-group input, .form-group select {
    width: 100%;
    background: var(--bg);
    border: 1px solid var(--border);
    border-radius: var(--radius-sm);
    padding: 0.55rem 0.75rem;
    color: var(--text);
    font-family: inherit;
    font-size: 0.85rem;
    transition: var(--transition);
}
.form-group input:focus, .form-group select:focus {
    border-color: var(--primary);
    outline: none;
    box-shadow: 0 0 0 3px var(--primary-glow);
}

.panel-footer {
    padding: 1rem 1.25rem;
    border-top: 1px solid var(--border);
}

/* Add/Edit Modal */
.add-modal-content {
    background: var(--surface);
    border: 1px solid var(--border);
    width: 420px;
    max-height: 90vh;
    border-radius: 16px;
    overflow: hidden;
    box-shadow: var(--shadow-lg);
}

/* Toasts */
#toasts { position: fixed; bottom: 1.25rem; right: 1.25rem; z-index: 2000; }
.toast {
    background: var(--surface);
    border: 1px solid var(--border);
    padding: 0.65rem 1.1rem;
    border-radius: var(--radius-sm);
    margin-top: 0.5rem;
    font-size: 0.85rem;
    animation: slideIn 0.25s ease-out;
    display: flex;
    align-items: center;
    gap: 0.4rem;
    box-shadow: var(--shadow);
}
.toast-success { border-color: rgba(16, 185, 129, 0.3); }
.toast-success::before { content: '\f058'; font-family: 'Font Awesome 6 Free'; font-weight: 900; color: var(--success); font-size: 0.9rem; }
.toast-error { border-color: rgba(239, 68, 68, 0.3); }
.toast-error::before { content: '\f06a'; font-family: 'Font Awesome 6 Free'; font-weight: 900; color: var(--danger); font-size: 0.9rem; }
@keyframes slideIn { from { transform: translateX(100%); opacity: 0; } to { transform: translateX(0); opacity: 1; } }

@media (max-width: 900px) {
    .layout { grid-template-columns: 1fr; }
    .sidebar { display: none; }
    .main { padding: 1.5rem; }
    .stats-grid { grid-template-columns: repeat(2, 1fr); }
    .studio { grid-template-columns: 1fr; height: 95vh; }
}
</style>
</head>
<body>

<div class="layout">
<aside class="sidebar">
    <a class="logo" href="../"><div class="icon"><i class="fa-solid fa-cube"></i></div> Omni<span>Grid</span></a>
    <div class="nav-section">Studio</div>
    <a class="nav-item active"><i class="fa-solid fa-th-large"></i> Dashboard</a>
    <a class="nav-item" href="earnings.php"><i class="fa-solid fa-coins"></i> Earnings</a>
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
        <div>
            <h1>Creator Studio</h1>
            <div class="subtitle">Manage your streams and earnings</div>
        </div>
        <a href="go_live.php" class="btn"><i class="fa-solid fa-broadcast-tower"></i> Go Live</a>
    </div>

    <div class="stats-grid">
        <div class="stat-card">
            <div class="stat-header">
                <span class="label">Today's Earnings</span>
                <div class="icon green"><i class="fa-solid fa-coins"></i></div>
            </div>
            <div class="stat-value" id="statE">$0.00</div>
        </div>
        <div class="stat-card">
            <div class="stat-header">
                <span class="label">Total Views</span>
                <div class="icon blue"><i class="fa-solid fa-eye"></i></div>
            </div>
            <div class="stat-value" id="statV">0</div>
        </div>
        <div class="stat-card">
            <div class="stat-header">
                <span class="label">Subscribers</span>
                <div class="icon yellow"><i class="fa-solid fa-users"></i></div>
            </div>
            <div class="stat-value" id="statS">0</div>
        </div>
        <div class="stat-card">
            <div class="stat-header">
                <span class="label">smartGrid Rate</span>
                <div class="icon red"><i class="fa-solid fa-bolt"></i></div>
            </div>
            <div class="stat-value" id="statR">1.0x</div>
        </div>
    </div>

    <div class="section-header">
        <h2>Your Streams</h2>
        <button class="btn btn-outline btn-sm" onclick="openAdd()"><i class="fa-solid fa-plus"></i> Add Stream</button>
    </div>
    <div class="stream-grid" id="grid">
        <div class="empty-state">
            <div class="empty-icon"><i class="fa-solid fa-video-slash"></i></div>
            <p>Loading streams...</p>
        </div>
    </div>
</main>
</div>

<!-- Studio Modal -->
<div class="modal" id="studioModal">
<div class="studio">
    <div class="studio-preview">
        <video id="studioVideo" autoplay muted playsinline></video>
        <div class="studio-overlay">
            <div class="live-ind" id="liveInd">LIVE</div>
            <div class="viewer-ct"><i class="fa-solid fa-eye"></i> <span id="vc">0</span></div>
        </div>
        <div class="studio-ctrl">
            <button class="ctrl-btn" onclick="flipCam()" title="Flip Camera"><i class="fa-solid fa-arrows-rotate"></i></button>
            <button class="ctrl-btn live-btn" id="liveBtn" onclick="toggleLive()" title="Go Live"><i class="fa-solid fa-circle"></i></button>
            <button class="ctrl-btn" onclick="toggleMic()" title="Toggle Mic"><i class="fa-solid fa-microphone" id="micIcon"></i></button>
        </div>
    </div>
    <div class="studio-panel">
        <div class="panel-header"><h2>Stream Settings</h2><button class="panel-close" onclick="closeStudio()">&times;</button></div>
        <div class="panel-body">
            <div class="room-display">
                <label>Room Code</label>
                <div class="code" id="roomCode">------</div>
                <div class="url" id="roomUrl"></div>
            </div>
            <div class="form-group"><label>Title</label><input type="text" id="liveTitle" placeholder="What are you streaming?"></div>
            <div class="form-group"><label>Category</label><select id="liveType"><option value="public">Public</option><option value="lifestyle">Lifestyle</option><option value="nsfw">NSFW</option></select></div>
        </div>
        <div class="panel-footer">
            <button class="btn btn-outline" style="width:100%" onclick="copyLink()"><i class="fa-solid fa-copy"></i> Copy Share Link</button>
        </div>
    </div>
</div>
</div>

<!-- Add/Edit Modal -->
<div class="modal" id="addModal">
<div class="add-modal-content">
    <div class="panel-header"><h2 id="addTitle">Add Stream</h2><button class="panel-close" onclick="closeAdd()">&times;</button></div>
    <div class="panel-body">
        <input type="hidden" id="editId">
        <div class="form-group"><label>Title *</label><input type="text" id="fTitle" placeholder="My awesome stream"></div>
        <div class="form-group"><label>Type</label><select id="fType"><option value="public">Public</option><option value="lifestyle">Lifestyle</option><option value="nsfw">NSFW</option></select></div>
        <div class="form-group"><label>Vibe Tag</label><input type="text" id="fVibe" placeholder="chill &middot; lofi &middot; music"></div>
        <div class="form-group"><label>Revenue Mode</label><select id="fRev"><option value="smartgrid">smartGrid</option><option value="override">Fixed Rate</option></select></div>
        <div class="form-group"><label>Multiplier</label><input type="number" id="fMult" value="1.0" min="0.5" max="5" step="0.1"></div>
    </div>
    <div class="panel-footer"><button class="btn" style="width:100%" onclick="saveStream()"><i class="fa-solid fa-check"></i> Save Stream</button></div>
</div>
</div>

<div id="toasts"></div>

<script>
let streams=[], mediaStream=null, peers={}, isLive=false, room='', pollInt=null, facing='user', micOn=true;

document.addEventListener('DOMContentLoaded', loadStreams);

async function loadStreams() {
    const r = await fetch('get_streams.php'), d = await r.json();
    if (d.success) { streams = d.streams; render(); stats(); }
}

function render() {
    const g = document.getElementById('grid');
    if (!streams.length) {
        g.innerHTML = '<div class="empty-state"><div class="empty-icon"><i class="fa-solid fa-video-slash"></i></div><p>No streams yet. Create your first stream!</p><button class="btn" onclick="openAdd()"><i class="fa-solid fa-plus"></i> Create Stream</button></div>';
        return;
    }
    g.innerHTML = streams.map(s => `
        <div class="stream-card ${s.is_live ? 'live' : ''}">
            <div class="stream-thumb">
                ${s.thumb_url ? `<img src="../${s.thumb_url}" alt="">` : '<div class="no-thumb"><i class="fa-solid fa-video"></i></div>'}
                ${s.is_live ? '<span class="stream-badge badge-live">Live</span>' : `<span class="stream-badge badge-${s.type}">${s.type}</span>`}
                <span class="stream-viewers"><i class="fa-solid fa-eye"></i> ${fmtN(s.views)}</span>
            </div>
            <div class="stream-body">
                <div class="stream-title">${esc(s.title)}</div>
                <div class="stream-tag">${esc(s.vibe_tag || '')}</div>
                <div class="stream-stats">
                    <span><i class="fa-solid fa-users"></i> ${s.subs_count || 0}</span>
                    <span><i class="fa-solid fa-heart"></i> ${fmtM(s.tips_cents)}</span>
                </div>
                <div class="stream-earnings">
                    <div>
                        <div class="earnings-value">${fmtM(s.total_earnings_cents || 0)}</div>
                    </div>
                    <span class="smartgrid-rate">${s.revenue_mode === 'smartgrid' ? s.smartgrid_multiplier + 'x' : '$' + s.price_per_minute}</span>
                </div>
                <div class="stream-actions">
                    <a href="go_live.php?id=${s.id}" class="btn btn-sm ${s.is_live ? 'btn-danger' : 'btn-success'}"><i class="fa-solid fa-${s.is_live ? 'stop' : 'broadcast-tower'}"></i></a>
                    <button class="btn btn-outline btn-sm" onclick="editStream(${s.id})"><i class="fa-solid fa-pen"></i></button>
                    <button class="btn btn-outline btn-sm" onclick="delStream(${s.id})"><i class="fa-solid fa-trash"></i></button>
                </div>
            </div>
        </div>
    `).join('');
}

function stats() {
    let e=0, v=0, sub=0, m=0, mc=0;
    streams.forEach(s => {
        e += s.total_earnings_cents || 0;
        v += s.views || 0;
        sub += s.subs_count || 0;
        if (s.revenue_mode === 'smartgrid') { m += parseFloat(s.smartgrid_multiplier); mc++; }
    });
    document.getElementById('statE').textContent = fmtM(e);
    document.getElementById('statV').textContent = fmtN(v);
    document.getElementById('statS').textContent = sub;
    document.getElementById('statR').textContent = mc ? (m/mc).toFixed(1) + 'x' : '1.0x';
}

async function openStudio() {
    document.getElementById('studioModal').classList.add('open');
    room = genRoom();
    document.getElementById('roomCode').textContent = room;
    document.getElementById('roomUrl').textContent = location.origin + '/omnigrid/live.php?room=' + room;
    try {
        mediaStream = await navigator.mediaDevices.getUserMedia({
            video: { facingMode: facing, width: { ideal: 1280 }, height: { ideal: 720 } }, audio: true
        });
        document.getElementById('studioVideo').srcObject = mediaStream;
    } catch(e) { toast('Camera access denied', 'error'); }
}

function closeStudio() {
    if (isLive && !confirm('End stream?')) return;
    stopLive();
    if (mediaStream) { mediaStream.getTracks().forEach(t => t.stop()); mediaStream = null; }
    document.getElementById('studioModal').classList.remove('open');
}

function goLive(id) {
    const s = streams.find(x => x.id === id);
    if (s) {
        document.getElementById('liveTitle').value = s.title;
        document.getElementById('liveType').value = s.type;
    }
    openStudio();
}

async function toggleLive() { isLive ? stopLive() : startLive(); }

async function startLive() {
    if (!mediaStream) return;
    isLive = true;
    document.getElementById('liveInd').classList.add('active');
    document.getElementById('liveBtn').classList.add('streaming');
    document.getElementById('liveBtn').innerHTML = '<i class="fa-solid fa-stop"></i>';
    await fetch('../signal.php?room=' + room + '&action=reset');
    startPoll();
    toast('You are LIVE!', 'success');
}

function stopLive() {
    isLive = false;
    document.getElementById('liveInd').classList.remove('active');
    document.getElementById('liveBtn').classList.remove('streaming');
    document.getElementById('liveBtn').innerHTML = '<i class="fa-solid fa-circle"></i>';
    Object.values(peers).forEach(p => p.close());
    peers = {};
    if (pollInt) { clearInterval(pollInt); pollInt = null; }
}

function startPoll() {
    if (pollInt) clearInterval(pollInt);
    pollInt = setInterval(async () => {
        if (!isLive) return;
        try {
            const r = await fetch('../signal.php?room=' + room + '&action=poll&from=host'), d = await r.json();
            if (d.offer) await handleOffer(d.offer.viewerId, d.offer);
            if (d.candidate && d.candidate.viewerId) {
                const p = peers[d.candidate.viewerId];
                if (p) await p.addIceCandidate(new RTCIceCandidate(d.candidate.candidate));
            }
        } catch(e) {}
    }, 1000);
}

async function handleOffer(vid, offer) {
    const p = new RTCPeerConnection({ iceServers: [{ urls: 'stun:stun.l.google.com:19302' }] });
    peers[vid] = p;
    mediaStream.getTracks().forEach(t => p.addTrack(t, mediaStream));
    p.onicecandidate = async e => {
        if (e.candidate) await fetch('../signal.php?room=' + room + '&action=candidate', {
            method: 'POST', headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ from: 'host', viewerId: vid, candidate: e.candidate })
        });
    };
    p.onconnectionstatechange = () => {
        if (p.connectionState === 'connected' || p.connectionState === 'disconnected') updateVC();
        if (p.connectionState === 'failed') delete peers[vid];
    };
    await p.setRemoteDescription(new RTCSessionDescription(offer));
    const ans = await p.createAnswer();
    await p.setLocalDescription(ans);
    await fetch('../signal.php?room=' + room + '&action=answer', {
        method: 'POST', headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({ ...ans, viewerId: vid })
    });
}

function updateVC() {
    document.getElementById('vc').textContent = Object.values(peers).filter(p => p.connectionState === 'connected').length;
}

async function flipCam() {
    facing = facing === 'user' ? 'environment' : 'user';
    if (mediaStream) mediaStream.getTracks().forEach(t => t.stop());
    mediaStream = await navigator.mediaDevices.getUserMedia({
        video: { facingMode: facing, width: { ideal: 1280 }, height: { ideal: 720 } }, audio: micOn
    });
    document.getElementById('studioVideo').srcObject = mediaStream;
    const vt = mediaStream.getVideoTracks()[0];
    Object.values(peers).forEach(p => {
        const s = p.getSenders().find(x => x.track?.kind === 'video');
        if (s) s.replaceTrack(vt);
    });
}

function toggleMic() {
    micOn = !micOn;
    if (mediaStream) mediaStream.getAudioTracks().forEach(t => t.enabled = micOn);
    document.getElementById('micIcon').className = 'fa-solid fa-microphone' + (micOn ? '' : '-slash');
}

function copyLink() {
    navigator.clipboard.writeText(location.origin + '/omnigrid/live.php?room=' + room);
    toast('Link copied!', 'success');
}

function openAdd() {
    document.getElementById('addModal').classList.add('open');
    document.getElementById('addTitle').textContent = 'Add Stream';
    document.getElementById('editId').value = '';
    document.getElementById('fTitle').value = '';
    document.getElementById('fType').value = 'public';
    document.getElementById('fVibe').value = '';
    document.getElementById('fRev').value = 'smartgrid';
    document.getElementById('fMult').value = '1.0';
}

function closeAdd() { document.getElementById('addModal').classList.remove('open'); }

function editStream(id) {
    const s = streams.find(x => x.id === id);
    if (!s) return;
    document.getElementById('addModal').classList.add('open');
    document.getElementById('addTitle').textContent = 'Edit Stream';
    document.getElementById('editId').value = id;
    document.getElementById('fTitle').value = s.title;
    document.getElementById('fType').value = s.type;
    document.getElementById('fVibe').value = s.vibe_tag || '';
    document.getElementById('fRev').value = s.revenue_mode;
    document.getElementById('fMult').value = s.smartgrid_multiplier;
}

async function saveStream() {
    const id = document.getElementById('editId').value;
    const data = {
        title: document.getElementById('fTitle').value,
        type: document.getElementById('fType').value,
        vibe_tag: document.getElementById('fVibe').value,
        revenue_mode: document.getElementById('fRev').value,
        smartgrid_multiplier: parseFloat(document.getElementById('fMult').value)
    };
    if (!data.title) { toast('Title is required', 'error'); return; }
    if (id) data.id = id;
    const r = await fetch(id ? 'update_stream.php' : 'save_stream.php', {
        method: 'POST', headers: { 'Content-Type': 'application/json' }, body: JSON.stringify(data)
    });
    const d = await r.json();
    if (d.success) { toast(id ? 'Stream updated' : 'Stream created', 'success'); closeAdd(); loadStreams(); }
    else toast(d.error || 'Failed', 'error');
}

async function delStream(id) {
    if (!confirm('Delete this stream?')) return;
    await fetch('delete_stream.php', { method: 'POST', headers: { 'Content-Type': 'application/json' }, body: JSON.stringify({ id }) });
    toast('Stream deleted', 'success');
    loadStreams();
}

function genRoom() { return Math.random().toString(36).substring(2, 8).toUpperCase(); }
function esc(s) { const d = document.createElement('div'); d.textContent = s || ''; return d.innerHTML; }
function fmtN(n) { return n >= 1e6 ? (n/1e6).toFixed(1) + 'M' : n >= 1e3 ? (n/1e3).toFixed(1) + 'K' : n || 0; }
function fmtM(c) { return '$' + ((c || 0) / 100).toFixed(2); }

function toast(m, t = 'info') {
    const e = document.createElement('div');
    e.className = 'toast' + (t === 'success' ? ' toast-success' : t === 'error' ? ' toast-error' : '');
    e.textContent = m;
    document.getElementById('toasts').appendChild(e);
    setTimeout(() => e.remove(), 3000);
}
</script>
</body>
</html>
