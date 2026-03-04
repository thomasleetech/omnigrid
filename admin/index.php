<?php
session_start();
require_once '../includes/db.php';
if (empty($_SESSION['user_id'])) { header('Location: ../login.php'); exit; }
$stmt = $pdo->prepare("SELECT * FROM users WHERE id = ? AND role = 'admin'");
$stmt->execute([$_SESSION['user_id']]);
if (!$stmt->fetch()) { header('Location: ../'); exit; }
$stats = [
    'users' => $pdo->query("SELECT COUNT(*) FROM users")->fetchColumn(),
    'streams' => $pdo->query("SELECT COUNT(*) FROM streams")->fetchColumn(),
    'live' => 0,
    'views' => 0,
    'earnings' => 0
];
try { $stats['live'] = $pdo->query("SELECT COUNT(*) FROM streams WHERE is_live = 1")->fetchColumn() ?: 0; } catch (Exception $e) {}
try { $stats['views'] = $pdo->query("SELECT COALESCE(SUM(views),0) FROM stream_metrics")->fetchColumn(); } catch (Exception $e) {}
try { $stats['earnings'] = $pdo->query("SELECT COALESCE(SUM(total_earnings_cents),0) FROM stream_metrics")->fetchColumn(); } catch (Exception $e) {}
$config = $pdo->query("SELECT * FROM site_config WHERE id = 1")->fetch() ?: ['rate_mode'=>'smartgrid','base_ppm'=>0.0005,'smartgrid_aggressiveness'=>1.5,'hero_mode'=>'auto','hero_loop_url'=>''];
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width,initial-scale=1.0">
<title>Admin — OmniGrid</title>
<link rel="preconnect" href="https://fonts.googleapis.com">
<link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&family=JetBrains+Mono:wght@500;700&display=swap" rel="stylesheet">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
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
    margin-bottom: 0.35rem;
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
.logo-sub {
    font-size: 0.65rem;
    color: var(--muted);
    text-transform: uppercase;
    letter-spacing: 0.1em;
    font-weight: 600;
    margin-bottom: 2rem;
    padding-left: 2.85rem;
}

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

/* Main */
.main { padding: 2rem 2.5rem; overflow-y: auto; }
.page-header { margin-bottom: 2rem; }
.page-header h1 { font-size: 1.5rem; font-weight: 700; letter-spacing: -0.02em; }
.page-header .subtitle { color: var(--muted); font-size: 0.85rem; margin-top: 0.2rem; }

/* Buttons */
.btn {
    background: var(--gradient-primary);
    color: #fff;
    border: none;
    padding: 0.5rem 1rem;
    border-radius: var(--radius-sm);
    cursor: pointer;
    font-family: inherit;
    font-size: 0.8rem;
    font-weight: 600;
    display: inline-flex;
    align-items: center;
    gap: 0.4rem;
    transition: var(--transition);
}
.btn:hover { filter: brightness(1.1); }
.btn-outline {
    background: transparent;
    border: 1px solid var(--border);
    color: var(--text-secondary);
}
.btn-outline:hover { border-color: var(--border-light); color: var(--text); }
.btn-sm { padding: 0.35rem 0.7rem; font-size: 0.75rem; }
.btn-danger { background: var(--danger); }
.btn-success { background: var(--success); }

/* Stats */
.stats-row {
    display: grid;
    grid-template-columns: repeat(5, 1fr);
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
.stat-icon {
    width: 38px;
    height: 38px;
    border-radius: 10px;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 0.95rem;
    margin-bottom: 0.85rem;
}
.stat-icon.purple { background: var(--primary-glow); color: var(--primary-light); }
.stat-icon.green { background: var(--success-glow); color: var(--success); }
.stat-icon.yellow { background: var(--warning-glow); color: var(--warning); }
.stat-icon.red { background: var(--danger-glow); color: var(--danger); }
.stat-label { font-size: 0.78rem; color: var(--muted); font-weight: 500; }
.stat-value {
    font-family: 'JetBrains Mono', monospace;
    font-size: 1.6rem;
    font-weight: 700;
    margin-top: 0.2rem;
    letter-spacing: -0.02em;
}

/* Cards */
.grid-2 { display: grid; grid-template-columns: 1fr 1fr; gap: 1.5rem; }
.card {
    background: var(--surface);
    border: 1px solid var(--border);
    border-radius: var(--radius);
}
.card-header {
    padding: 1rem 1.25rem;
    border-bottom: 1px solid var(--border);
    display: flex;
    justify-content: space-between;
    align-items: center;
}
.card-header h2 { font-size: 0.95rem; font-weight: 600; }
.card-body { padding: 1.25rem; }

/* Form */
.form-group { margin-bottom: 1rem; }
.form-group label {
    display: block;
    font-size: 0.8rem;
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
.form-group input[type="range"] {
    -webkit-appearance: none;
    height: 6px;
    background: var(--border);
    border-radius: 3px;
    padding: 0;
    border: none;
}
.form-group input[type="range"]::-webkit-slider-thumb {
    -webkit-appearance: none;
    width: 18px;
    height: 18px;
    background: var(--primary);
    border-radius: 50%;
    cursor: pointer;
    box-shadow: 0 2px 8px rgba(99, 102, 241, 0.3);
}
.form-hint { font-size: 0.72rem; color: var(--muted); margin-top: 0.3rem; }

/* Table */
.table { width: 100%; border-collapse: collapse; font-size: 0.85rem; }
.table th {
    text-align: left;
    padding: 0.7rem 1rem;
    color: var(--muted);
    font-weight: 600;
    font-size: 0.72rem;
    text-transform: uppercase;
    letter-spacing: 0.05em;
    border-bottom: 1px solid var(--border);
}
.table td {
    padding: 0.85rem 1rem;
    border-bottom: 1px solid var(--border);
}
.table tr:hover { background: rgba(255, 255, 255, 0.015); }
.table tr:last-child td { border-bottom: none; }

/* Badges */
.badge {
    padding: 0.2rem 0.55rem;
    border-radius: var(--radius-pill);
    font-size: 0.7rem;
    font-weight: 600;
    letter-spacing: 0.02em;
    display: inline-block;
}
.badge-live { background: var(--danger-glow); color: var(--danger); }
.badge-public { background: var(--success-glow); color: var(--success); }
.badge-lifestyle { background: var(--warning-glow); color: var(--warning); }
.badge-nsfw { background: rgba(147, 51, 234, 0.12); color: #a855f7; }
.badge-admin { background: var(--primary-glow); color: var(--primary-light); }
.badge-creator { background: rgba(255,255,255,0.06); color: var(--text-secondary); }

.user-cell { display: flex; align-items: center; gap: 0.7rem; }
.avatar {
    width: 34px;
    height: 34px;
    background: var(--primary-glow);
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 0.8rem;
    color: var(--primary-light);
    flex-shrink: 0;
}

/* SmartGrid Visual */
.smartgrid-visual {
    background: var(--bg);
    border: 1px solid var(--border);
    border-radius: var(--radius-sm);
    padding: 1.25rem;
    margin-top: 1rem;
}
.rate-bar {
    height: 8px;
    background: var(--border);
    border-radius: 4px;
    margin: 0.75rem 0;
    overflow: hidden;
}
.rate-fill {
    height: 100%;
    background: linear-gradient(90deg, var(--success), var(--warning), var(--danger));
    border-radius: 4px;
    transition: width 0.5s;
}
.rate-labels {
    display: flex;
    justify-content: space-between;
    font-size: 0.7rem;
    color: var(--muted);
    font-family: 'JetBrains Mono', monospace;
}
.current-rate { text-align: center; margin-top: 1.25rem; }
.current-rate .big {
    font-family: 'JetBrains Mono', monospace;
    font-size: 2.25rem;
    font-weight: 700;
    background: var(--gradient-primary);
    -webkit-background-clip: text;
    -webkit-text-fill-color: transparent;
}
.current-rate .label { font-size: 0.78rem; color: var(--muted); margin-top: 0.15rem; }

/* Views */
.view { display: none; }
.view.active { display: block; }

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
    box-shadow: var(--shadow);
}
@keyframes slideIn { from { transform: translateX(100%); opacity: 0; } }

@media (max-width: 1024px) {
    .layout { grid-template-columns: 1fr; }
    .sidebar { display: none; }
    .main { padding: 1.5rem; }
    .stats-row { grid-template-columns: repeat(2, 1fr); }
    .grid-2 { grid-template-columns: 1fr; }
}
</style>
</head>
<body>

<div class="layout">
<aside class="sidebar">
    <a class="logo" href="../"><div class="icon"><i class="fa-solid fa-cube"></i></div> Omni<span>Grid</span></a>
    <div class="logo-sub">Admin Panel</div>

    <nav>
        <a class="nav-item active" data-view="dashboard"><i class="fa-solid fa-chart-pie"></i> Dashboard</a>
        <a class="nav-item" data-view="smartgrid"><i class="fa-solid fa-bolt"></i> smartGrid</a>
        <a class="nav-item" data-view="streams"><i class="fa-solid fa-video"></i> Streams</a>
        <a class="nav-item" data-view="users"><i class="fa-solid fa-users"></i> Users</a>
        <div class="nav-section">System</div>
        <a class="nav-item" data-view="settings"><i class="fa-solid fa-gear"></i> Settings</a>
        <a class="nav-item" href="../"><i class="fa-solid fa-earth-americas"></i> View Site</a>
        <a class="nav-item" href="../logout.php"><i class="fa-solid fa-arrow-right-from-bracket"></i> Logout</a>
    </nav>
</aside>

<main class="main">
<!-- Dashboard -->
<div class="view active" id="dashboardView">
    <div class="page-header"><h1>Dashboard</h1><p class="subtitle">Platform overview and analytics</p></div>
    <div class="stats-row">
        <div class="stat-card">
            <div class="stat-icon purple"><i class="fa-solid fa-users"></i></div>
            <div class="stat-label">Total Users</div>
            <div class="stat-value"><?= number_format($stats['users']) ?></div>
        </div>
        <div class="stat-card">
            <div class="stat-icon green"><i class="fa-solid fa-video"></i></div>
            <div class="stat-label">Streams</div>
            <div class="stat-value"><?= $stats['streams'] ?></div>
        </div>
        <div class="stat-card">
            <div class="stat-icon red"><i class="fa-solid fa-broadcast-tower"></i></div>
            <div class="stat-label">Live Now</div>
            <div class="stat-value"><?= $stats['live'] ?></div>
        </div>
        <div class="stat-card">
            <div class="stat-icon yellow"><i class="fa-solid fa-eye"></i></div>
            <div class="stat-label">Total Views</div>
            <div class="stat-value"><?= number_format($stats['views']) ?></div>
        </div>
        <div class="stat-card">
            <div class="stat-icon green"><i class="fa-solid fa-coins"></i></div>
            <div class="stat-label">Earnings</div>
            <div class="stat-value">$<?= number_format($stats['earnings']/100, 2) ?></div>
        </div>
    </div>
    <div class="grid-2">
        <div class="card">
            <div class="card-header"><h2>Views (7 days)</h2></div>
            <div class="card-body"><canvas id="viewsChart" height="200"></canvas></div>
        </div>
        <div class="card">
            <div class="card-header"><h2>Earnings (7 days)</h2></div>
            <div class="card-body"><canvas id="earningsChart" height="200"></canvas></div>
        </div>
    </div>
</div>

<!-- smartGrid -->
<div class="view" id="smartgridView">
    <div class="page-header"><h1>smartGrid Engine</h1><p class="subtitle">Configure adaptive earnings for creators</p></div>
    <div class="grid-2">
        <div class="card">
            <div class="card-header"><h2>Configuration</h2></div>
            <div class="card-body">
                <div class="form-group">
                    <label>Rate Mode</label>
                    <select id="sgMode">
                        <option value="smartgrid" <?= ($config['rate_mode']??'')==='smartgrid'?'selected':'' ?>>smartGrid (Adaptive)</option>
                        <option value="override" <?= ($config['rate_mode']??'')==='override'?'selected':'' ?>>Override (Fixed)</option>
                    </select>
                </div>
                <div class="form-group">
                    <label>Base Rate per Minute ($)</label>
                    <input type="number" id="sgPPM" value="<?= $config['base_ppm']??0.0005 ?>" step="0.000001">
                    <div class="form-hint">Base $/minute before multipliers are applied</div>
                </div>
                <div class="form-group">
                    <label>Aggressiveness</label>
                    <input type="range" id="sgAgg" min="0.5" max="3" step="0.1" value="<?= $config['smartgrid_aggressiveness']??1.5 ?>" oninput="document.getElementById('aggVal').textContent=this.value+'x'">
                    <div class="form-hint">Engagement bonus multiplier: <strong id="aggVal"><?= $config['smartgrid_aggressiveness']??1.5 ?>x</strong></div>
                </div>
                <div class="form-group">
                    <label>Admin Adjustment (%)</label>
                    <input type="number" id="sgAdj" value="0" min="-50" max="100">
                    <div class="form-hint">Manual adjustment applied to all payouts</div>
                </div>
                <button class="btn" onclick="saveSG()"><i class="fa-solid fa-check"></i> Save Configuration</button>
            </div>
        </div>
        <div class="card">
            <div class="card-header"><h2>How It Works</h2></div>
            <div class="card-body">
                <p style="font-size:0.85rem;color:var(--text-secondary);line-height:1.7;margin-bottom:1rem">smartGrid dynamically adjusts creator earnings based on real-time engagement metrics:</p>
                <ul style="font-size:0.82rem;color:var(--text-secondary);margin:0 0 0 1.25rem;line-height:2">
                    <li><strong>Watch Time</strong> &mdash; Longer sessions increase rate</li>
                    <li><strong>Return Viewers</strong> &mdash; Revisits boost multiplier</li>
                    <li><strong>Tips</strong> &mdash; Tip velocity improves payout</li>
                    <li><strong>Subscriber Growth</strong> &mdash; New subs increase rate</li>
                </ul>
                <div class="smartgrid-visual">
                    <div style="font-size:0.75rem;color:var(--muted);font-weight:500">Rate Range</div>
                    <div class="rate-bar"><div class="rate-fill" style="width:60%"></div></div>
                    <div class="rate-labels"><span>0.5x</span><span>1.0x</span><span>2.0x</span><span>3.0x</span><span>5.0x</span></div>
                    <div class="current-rate">
                        <div class="big">1.5x</div>
                        <div class="label">Platform Average</div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Streams -->
<div class="view" id="streamsView">
    <div class="page-header"><h1>Streams</h1><p class="subtitle">Manage all platform streams</p></div>
    <div class="card">
        <div class="card-body" style="padding:0">
            <table class="table" id="streamsTable">
                <thead><tr><th>Stream</th><th>Creator</th><th>Type</th><th>Rate</th><th>Views</th><th>Status</th><th></th></tr></thead>
                <tbody></tbody>
            </table>
        </div>
    </div>
</div>

<!-- Users -->
<div class="view" id="usersView">
    <div class="page-header"><h1>Users</h1><p class="subtitle">Manage platform users</p></div>
    <div class="card">
        <div class="card-body" style="padding:0">
            <table class="table" id="usersTable">
                <thead><tr><th>User</th><th>Role</th><th>Streams</th><th>Joined</th><th></th></tr></thead>
                <tbody></tbody>
            </table>
        </div>
    </div>
</div>

<!-- Settings -->
<div class="view" id="settingsView">
    <div class="page-header"><h1>Settings</h1><p class="subtitle">Platform configuration</p></div>
    <div class="card" style="max-width:500px">
        <div class="card-header"><h2>Hero Section</h2></div>
        <div class="card-body">
            <div class="form-group">
                <label>Hero Mode</label>
                <select id="heroMode">
                    <option value="auto" <?= ($config['hero_mode']??'')==='auto'?'selected':'' ?>>Auto (Show live streams)</option>
                    <option value="live" <?= ($config['hero_mode']??'')==='live'?'selected':'' ?>>Live Only</option>
                    <option value="loop" <?= ($config['hero_mode']??'')==='loop'?'selected':'' ?>>Video Loop</option>
                </select>
            </div>
            <div class="form-group">
                <label>Loop Video URL</label>
                <input type="text" id="heroLoop" value="<?= htmlspecialchars($config['hero_loop_url']??'') ?>" placeholder="https://...">
            </div>
            <button class="btn" onclick="saveSet()"><i class="fa-solid fa-check"></i> Save Settings</button>
        </div>
    </div>
</div>
</main>
</div>

<div id="toasts"></div>

<script>
// Navigation
document.querySelectorAll('.nav-item[data-view]').forEach(n => {
    n.onclick = () => {
        document.querySelectorAll('.nav-item').forEach(x => x.classList.remove('active'));
        document.querySelectorAll('.view').forEach(x => x.classList.remove('active'));
        n.classList.add('active');
        document.getElementById(n.dataset.view + 'View').classList.add('active');
        if (n.dataset.view === 'streams') loadStreams();
        if (n.dataset.view === 'users') loadUsers();
    };
});

// Charts
const chartDefaults = {
    responsive: true,
    plugins: { legend: { display: false } },
    scales: {
        y: {
            beginAtZero: true,
            grid: { color: 'rgba(37,37,58,0.5)' },
            ticks: { color: '#6b6b85', font: { family: 'JetBrains Mono', size: 11 } }
        },
        x: {
            grid: { display: false },
            ticks: { color: '#6b6b85', font: { family: 'Inter', size: 11 } }
        }
    }
};

new Chart(document.getElementById('viewsChart'), {
    type: 'line',
    data: {
        labels: ['Mon','Tue','Wed','Thu','Fri','Sat','Sun'],
        datasets: [{
            data: [1200, 1800, 1500, 2200, 2800, 3500, 2900],
            borderColor: '#6366f1',
            backgroundColor: 'rgba(99, 102, 241, 0.08)',
            tension: 0.4,
            fill: true,
            borderWidth: 2,
            pointRadius: 3,
            pointBackgroundColor: '#6366f1'
        }]
    },
    options: chartDefaults
});

new Chart(document.getElementById('earningsChart'), {
    type: 'bar',
    data: {
        labels: ['Mon','Tue','Wed','Thu','Fri','Sat','Sun'],
        datasets: [{
            data: [45, 62, 58, 89, 120, 145, 98],
            backgroundColor: 'rgba(16, 185, 129, 0.7)',
            borderRadius: 6,
            borderSkipped: false
        }]
    },
    options: chartDefaults
});

// API functions
async function saveSG() {
    const d = {
        rate_mode: document.getElementById('sgMode').value,
        base_ppm: parseFloat(document.getElementById('sgPPM').value),
        smartgrid_aggressiveness: parseFloat(document.getElementById('sgAgg').value),
        admin_adjustment: parseFloat(document.getElementById('sgAdj').value)
    };
    const r = await fetch('save_config.php', { method: 'POST', headers: { 'Content-Type': 'application/json' }, body: JSON.stringify(d) });
    toast((await r.json()).success ? 'Configuration saved' : 'Error saving');
}

async function saveSet() {
    const d = {
        hero_mode: document.getElementById('heroMode').value,
        hero_loop_url: document.getElementById('heroLoop').value
    };
    const r = await fetch('save_config.php', { method: 'POST', headers: { 'Content-Type': 'application/json' }, body: JSON.stringify(d) });
    toast((await r.json()).success ? 'Settings saved' : 'Error saving');
}

async function loadStreams() {
    const r = await fetch('get_all_streams.php'), d = await r.json();
    document.querySelector('#streamsTable tbody').innerHTML = d.streams.map(s => `
        <tr>
            <td><strong>${esc(s.title)}</strong></td>
            <td>${esc(s.creator_name || '')}</td>
            <td><span class="badge badge-${s.type}">${s.type}</span></td>
            <td style="font-family:'JetBrains Mono',monospace;font-size:0.8rem">${s.revenue_mode === 'smartgrid' ? s.smartgrid_multiplier + 'x' : '$' + s.price_per_minute}</td>
            <td style="font-family:'JetBrains Mono',monospace;font-size:0.8rem">${fmtN(s.views)}</td>
            <td>${s.is_live ? '<span class="badge badge-live">Live</span>' : s.is_active ? '<span style="color:var(--success)">Active</span>' : '<span style="color:var(--muted)">Paused</span>'}</td>
            <td><button class="btn btn-outline btn-sm" onclick="togStream(${s.id},${s.is_active ? 0 : 1})">${s.is_active ? 'Pause' : 'Enable'}</button></td>
        </tr>
    `).join('');
}

async function loadUsers() {
    const r = await fetch('get_all_users.php'), d = await r.json();
    document.querySelector('#usersTable tbody').innerHTML = d.users.map(u => `
        <tr>
            <td>
                <div class="user-cell">
                    <div class="avatar"><i class="fa-solid fa-user"></i></div>
                    <div>
                        <strong>${esc(u.display_name || u.email)}</strong>
                        ${u.display_name ? `<div style="font-size:0.75rem;color:var(--muted)">${esc(u.email)}</div>` : ''}
                    </div>
                </div>
            </td>
            <td><span class="badge badge-${u.role}">${u.role}</span></td>
            <td style="font-family:'JetBrains Mono',monospace;font-size:0.85rem">${u.stream_count || 0}</td>
            <td style="font-size:0.8rem;color:var(--muted)">${(u.created_at || '').slice(0, 10)}</td>
            <td><button class="btn ${u.is_banned ? 'btn-success' : 'btn-danger'} btn-sm" onclick="togBan(${u.id},${u.is_banned ? 0 : 1})">${u.is_banned ? 'Unban' : 'Ban'}</button></td>
        </tr>
    `).join('');
}

async function togStream(id, a) {
    await fetch('toggle_stream.php', { method: 'POST', headers: { 'Content-Type': 'application/json' }, body: JSON.stringify({ id, is_active: a }) });
    loadStreams();
}

async function togBan(id, b) {
    await fetch('toggle_ban.php', { method: 'POST', headers: { 'Content-Type': 'application/json' }, body: JSON.stringify({ id, is_banned: b }) });
    loadUsers();
}

function esc(s) { const d = document.createElement('div'); d.textContent = s || ''; return d.innerHTML; }
function fmtN(n) { return n >= 1e6 ? (n/1e6).toFixed(1) + 'M' : n >= 1e3 ? (n/1e3).toFixed(1) + 'K' : n || 0; }
function toast(m) {
    const e = document.createElement('div');
    e.className = 'toast';
    e.textContent = m;
    document.getElementById('toasts').appendChild(e);
    setTimeout(() => e.remove(), 3000);
}
</script>
</body>
</html>
