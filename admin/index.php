<?php
session_start();
require_once '../includes/db.php';
if (empty($_SESSION['user_id'])) { header('Location: ../login.php'); exit; }
$stmt = $pdo->prepare("SELECT * FROM users WHERE id = ? AND role = 'admin'");
$stmt->execute([$_SESSION['user_id']]);
if (!$stmt->fetch()) { header('Location: ../'); exit; }
$stats = ['users' => $pdo->query("SELECT COUNT(*) FROM users")->fetchColumn(), 'streams' => $pdo->query("SELECT COUNT(*) FROM streams")->fetchColumn(), 'live' => $pdo->query("SELECT COUNT(*) FROM streams WHERE is_live = 1")->fetchColumn() ?: 0, 'views' => $pdo->query("SELECT COALESCE(SUM(views),0) FROM stream_metrics")->fetchColumn(), 'earnings' => $pdo->query("SELECT COALESCE(SUM(tips_cents),0) FROM stream_metrics")->fetchColumn()];
$config = $pdo->query("SELECT * FROM site_config WHERE id = 1")->fetch() ?: ['rate_mode'=>'smartgrid','base_ppm'=>0.0005,'smartgrid_aggressiveness'=>1.5,'hero_mode'=>'auto','hero_loop_url'=>''];
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8"><meta name="viewport" content="width=device-width,initial-scale=1.0">
<title>Admin | OmniGrid</title>
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<style>
:root{--bg:#0d0d12;--surface:#15151e;--border:#252536;--text:#e8e8ed;--muted:#6b6b80;--primary:#7c3aed;--success:#22c55e;--warning:#eab308;--danger:#ef4444}
*{box-sizing:border-box;margin:0;padding:0}body{font-family:'Inter',-apple-system,sans-serif;background:var(--bg);color:var(--text);min-height:100vh}
.layout{display:grid;grid-template-columns:220px 1fr;min-height:100vh}.sidebar{background:var(--surface);border-right:1px solid var(--border);padding:1.25rem}
.logo{font-size:1.3rem;font-weight:700;margin-bottom:2rem;display:flex;align-items:center;gap:0.5rem}.logo i{color:var(--primary)}
.nav-item{display:flex;align-items:center;gap:0.6rem;padding:0.6rem 0.9rem;border-radius:6px;color:var(--muted);text-decoration:none;margin-bottom:0.2rem;font-size:0.85rem;cursor:pointer;transition:0.15s}
.nav-item:hover,.nav-item.active{background:rgba(124,58,237,0.1);color:var(--text)}.nav-item.active{color:var(--primary)}.nav-item i{width:18px;font-size:0.9rem}
.nav-section{font-size:0.65rem;text-transform:uppercase;letter-spacing:0.1em;color:var(--muted);margin:1.5rem 0 0.5rem 0.5rem}
.main{padding:1.5rem 2rem;overflow-y:auto}.page-header{margin-bottom:1.5rem}.page-header h1{font-size:1.4rem;font-weight:600}.page-header p{color:var(--muted);font-size:0.85rem;margin-top:0.25rem}
.btn{background:var(--primary);color:#fff;border:none;padding:0.5rem 1rem;border-radius:6px;cursor:pointer;font-size:0.8rem;display:inline-flex;align-items:center;gap:0.4rem}.btn:hover{filter:brightness(1.1)}.btn-outline{background:transparent;border:1px solid var(--border);color:var(--text)}.btn-sm{padding:0.35rem 0.7rem;font-size:0.75rem}
.stats-row{display:grid;grid-template-columns:repeat(5,1fr);gap:1rem;margin-bottom:1.5rem}.stat-card{background:var(--surface);border:1px solid var(--border);border-radius:10px;padding:1rem 1.25rem}
.stat-card .icon{width:36px;height:36px;border-radius:8px;display:flex;align-items:center;justify-content:center;margin-bottom:0.75rem;font-size:1rem}
.stat-card .icon.purple{background:rgba(124,58,237,0.15);color:var(--primary)}.stat-card .icon.green{background:rgba(34,197,94,0.15);color:var(--success)}.stat-card .icon.yellow{background:rgba(234,179,8,0.15);color:var(--warning)}.stat-card .icon.red{background:rgba(239,68,68,0.15);color:var(--danger)}
.stat-card .label{font-size:0.75rem;color:var(--muted)}.stat-card .value{font-size:1.5rem;font-weight:600;margin-top:0.2rem}
.grid-2{display:grid;grid-template-columns:1fr 1fr;gap:1.5rem}.card{background:var(--surface);border:1px solid var(--border);border-radius:10px}
.card-header{padding:1rem 1.25rem;border-bottom:1px solid var(--border);display:flex;justify-content:space-between;align-items:center}.card-header h2{font-size:0.95rem;font-weight:600}.card-body{padding:1.25rem}
.form-group{margin-bottom:1rem}.form-group label{display:block;font-size:0.8rem;color:var(--muted);margin-bottom:0.3rem}
.form-group input,.form-group select{width:100%;background:var(--bg);border:1px solid var(--border);border-radius:6px;padding:0.55rem 0.75rem;color:var(--text);font-size:0.85rem}.form-group input:focus{border-color:var(--primary);outline:none}
.form-hint{font-size:0.7rem;color:var(--muted);margin-top:0.25rem}
.table{width:100%;border-collapse:collapse;font-size:0.85rem}.table th{text-align:left;padding:0.6rem 0.75rem;color:var(--muted);font-weight:500;font-size:0.75rem;text-transform:uppercase;letter-spacing:0.05em;border-bottom:1px solid var(--border)}.table td{padding:0.75rem;border-bottom:1px solid var(--border)}.table tr:hover{background:rgba(255,255,255,0.02)}
.badge{padding:0.2rem 0.5rem;border-radius:4px;font-size:0.7rem;font-weight:500}.badge-live{background:var(--danger)}.badge-public{background:var(--success)}.badge-lifestyle{background:var(--warning);color:#000}.badge-nsfw{background:#9333ea}.badge-admin{background:var(--primary)}.badge-creator{background:var(--border);color:var(--text)}
.avatar{width:32px;height:32px;background:var(--border);border-radius:50%;display:flex;align-items:center;justify-content:center;font-size:0.8rem}.user-cell{display:flex;align-items:center;gap:0.6rem}
.smartgrid-visual{background:var(--bg);border-radius:8px;padding:1.25rem;margin-top:1rem}.rate-bar{height:8px;background:var(--border);border-radius:4px;margin:0.75rem 0;overflow:hidden}.rate-fill{height:100%;background:linear-gradient(90deg,var(--success),var(--warning),var(--danger));border-radius:4px;transition:0.3s}.rate-labels{display:flex;justify-content:space-between;font-size:0.7rem;color:var(--muted)}.current-rate{text-align:center;margin-top:1rem}.current-rate .big{font-size:2rem;font-weight:700;color:var(--primary)}.current-rate .label{font-size:0.75rem;color:var(--muted)}
.view{display:none}.view.active{display:block}
#toasts{position:fixed;bottom:1.5rem;right:1.5rem;z-index:2000}.toast{background:var(--surface);border:1px solid var(--border);padding:0.6rem 1rem;border-radius:6px;margin-top:0.5rem;font-size:0.8rem;animation:slideIn 0.2s}@keyframes slideIn{from{transform:translateX(100%)}}
@media(max-width:1024px){.layout{grid-template-columns:1fr}.sidebar{display:none}.stats-row{grid-template-columns:repeat(2,1fr)}}
</style>
</head>
<body>
<div class="layout">
<aside class="sidebar">
<div class="logo"><i class="fa fa-cube"></i> OmniGrid</div>
<nav>
<a class="nav-item active" data-view="dashboard"><i class="fa fa-chart-pie"></i> Dashboard</a>
<a class="nav-item" data-view="smartgrid"><i class="fa fa-bolt"></i> smartGrid</a>
<a class="nav-item" data-view="streams"><i class="fa fa-video"></i> Streams</a>
<a class="nav-item" data-view="users"><i class="fa fa-users"></i> Users</a>
<div class="nav-section">System</div>
<a class="nav-item" data-view="settings"><i class="fa fa-cog"></i> Settings</a>
<a class="nav-item" href="../"><i class="fa fa-globe"></i> View Site</a>
<a class="nav-item" href="../logout.php"><i class="fa fa-sign-out-alt"></i> Logout</a>
</nav>
</aside>
<main class="main">
<div class="view active" id="dashboardView">
<div class="page-header"><h1>Dashboard</h1><p>Platform overview</p></div>
<div class="stats-row">
<div class="stat-card"><div class="icon purple"><i class="fa fa-users"></i></div><div class="label">Users</div><div class="value"><?= number_format($stats['users']) ?></div></div>
<div class="stat-card"><div class="icon green"><i class="fa fa-video"></i></div><div class="label">Streams</div><div class="value"><?= $stats['streams'] ?></div></div>
<div class="stat-card"><div class="icon red"><i class="fa fa-broadcast-tower"></i></div><div class="label">Live</div><div class="value"><?= $stats['live'] ?></div></div>
<div class="stat-card"><div class="icon yellow"><i class="fa fa-eye"></i></div><div class="label">Views</div><div class="value"><?= number_format($stats['views']) ?></div></div>
<div class="stat-card"><div class="icon green"><i class="fa fa-dollar-sign"></i></div><div class="label">Earnings</div><div class="value">$<?= number_format($stats['earnings']/100,2) ?></div></div>
</div>
<div class="grid-2">
<div class="card"><div class="card-header"><h2>Views (7d)</h2></div><div class="card-body"><canvas id="viewsChart" height="180"></canvas></div></div>
<div class="card"><div class="card-header"><h2>Earnings (7d)</h2></div><div class="card-body"><canvas id="earningsChart" height="180"></canvas></div></div>
</div>
</div>
<div class="view" id="smartgridView">
<div class="page-header"><h1>smartGrid Engine</h1><p>Adaptive earnings configuration</p></div>
<div class="grid-2">
<div class="card"><div class="card-header"><h2>Settings</h2></div><div class="card-body">
<div class="form-group"><label>Rate Mode</label><select id="sgMode"><option value="smartgrid" <?= ($config['rate_mode']??'')==='smartgrid'?'selected':'' ?>>smartGrid</option><option value="override" <?= ($config['rate_mode']??'')==='override'?'selected':'' ?>>Override</option></select></div>
<div class="form-group"><label>Base PPM ($)</label><input type="number" id="sgPPM" value="<?= $config['base_ppm']??0.0005 ?>" step="0.000001"><div class="form-hint">Base $/minute before multipliers</div></div>
<div class="form-group"><label>Aggressiveness</label><input type="range" id="sgAgg" min="0.5" max="3" step="0.1" value="<?= $config['smartgrid_aggressiveness']??1.5 ?>" oninput="document.getElementById('aggVal').textContent=this.value"><div class="form-hint">Current: <span id="aggVal"><?= $config['smartgrid_aggressiveness']??1.5 ?></span>x</div></div>
<div class="form-group"><label>Admin Adjustment (%)</label><input type="number" id="sgAdj" value="0" min="-50" max="100"><div class="form-hint">Manually adjust all payouts</div></div>
<button class="btn" onclick="saveSG()"><i class="fa fa-save"></i> Save</button>
</div></div>
<div class="card"><div class="card-header"><h2>How it Works</h2></div><div class="card-body">
<p style="font-size:0.85rem;color:var(--muted);line-height:1.6">smartGrid adjusts earnings based on:</p>
<ul style="font-size:0.8rem;color:var(--muted);margin:1rem 0 1rem 1.5rem;line-height:1.8"><li><b>Watch Time</b> - Longer sessions = higher rate</li><li><b>Revisits</b> - Return viewers boost rate</li><li><b>Tips</b> - Tip velocity affects payout</li><li><b>Subs</b> - Growth increases multiplier</li></ul>
<div class="smartgrid-visual"><div style="font-size:0.75rem;color:var(--muted)">Rate Range</div><div class="rate-bar"><div class="rate-fill" style="width:60%"></div></div><div class="rate-labels"><span>0.5x</span><span>1.0x</span><span>2.0x</span><span>3.0x</span><span>5.0x</span></div><div class="current-rate"><div class="big">1.5x</div><div class="label">Platform Avg</div></div></div>
</div></div>
</div>
</div>
<div class="view" id="streamsView"><div class="page-header"><h1>Streams</h1></div><div class="card"><div class="card-body" style="padding:0"><table class="table" id="streamsTable"><thead><tr><th>Stream</th><th>Creator</th><th>Type</th><th>Rate</th><th>Views</th><th>Status</th><th></th></tr></thead><tbody></tbody></table></div></div></div>
<div class="view" id="usersView"><div class="page-header"><h1>Users</h1></div><div class="card"><div class="card-body" style="padding:0"><table class="table" id="usersTable"><thead><tr><th>User</th><th>Role</th><th>Streams</th><th>Joined</th><th></th></tr></thead><tbody></tbody></table></div></div></div>
<div class="view" id="settingsView"><div class="page-header"><h1>Settings</h1></div><div class="card" style="max-width:500px"><div class="card-header"><h2>Hero Section</h2></div><div class="card-body">
<div class="form-group"><label>Hero Mode</label><select id="heroMode"><option value="auto" <?= ($config['hero_mode']??'')==='auto'?'selected':'' ?>>Auto</option><option value="live" <?= ($config['hero_mode']??'')==='live'?'selected':'' ?>>Live</option><option value="loop" <?= ($config['hero_mode']??'')==='loop'?'selected':'' ?>>Loop</option></select></div>
<div class="form-group"><label>Loop URL</label><input type="text" id="heroLoop" value="<?= htmlspecialchars($config['hero_loop_url']??'') ?>"></div>
<button class="btn" onclick="saveSet()"><i class="fa fa-save"></i> Save</button>
</div></div></div>
</main>
</div>
<div id="toasts"></div>
<script>
document.querySelectorAll('.nav-item[data-view]').forEach(n=>n.onclick=()=>{document.querySelectorAll('.nav-item').forEach(x=>x.classList.remove('active'));document.querySelectorAll('.view').forEach(x=>x.classList.remove('active'));n.classList.add('active');document.getElementById(n.dataset.view+'View').classList.add('active');if(n.dataset.view==='streams')loadStreams();if(n.dataset.view==='users')loadUsers();});
const cOpts={responsive:true,plugins:{legend:{display:false}},scales:{y:{beginAtZero:true,grid:{color:'#252536'}},x:{grid:{display:false}}}};
new Chart(document.getElementById('viewsChart'),{type:'line',data:{labels:['Mon','Tue','Wed','Thu','Fri','Sat','Sun'],datasets:[{data:[1200,1800,1500,2200,2800,3500,2900],borderColor:'#7c3aed',tension:0.3,fill:true,backgroundColor:'rgba(124,58,237,0.1)'}]},options:cOpts});
new Chart(document.getElementById('earningsChart'),{type:'bar',data:{labels:['Mon','Tue','Wed','Thu','Fri','Sat','Sun'],datasets:[{data:[45,62,58,89,120,145,98],backgroundColor:'#22c55e'}]},options:cOpts});
async function saveSG(){const d={rate_mode:document.getElementById('sgMode').value,base_ppm:parseFloat(document.getElementById('sgPPM').value),smartgrid_aggressiveness:parseFloat(document.getElementById('sgAgg').value),admin_adjustment:parseFloat(document.getElementById('sgAdj').value)};const r=await fetch('save_config.php',{method:'POST',headers:{'Content-Type':'application/json'},body:JSON.stringify(d)});toast((await r.json()).success?'Saved!':'Error');}
async function saveSet(){const d={hero_mode:document.getElementById('heroMode').value,hero_loop_url:document.getElementById('heroLoop').value};const r=await fetch('save_config.php',{method:'POST',headers:{'Content-Type':'application/json'},body:JSON.stringify(d)});toast((await r.json()).success?'Saved!':'Error');}
async function loadStreams(){const r=await fetch('get_all_streams.php'),d=await r.json();document.querySelector('#streamsTable tbody').innerHTML=d.streams.map(s=>`<tr><td><b>${esc(s.title)}</b></td><td>${esc(s.creator_name||'')}</td><td><span class="badge badge-${s.type}">${s.type}</span></td><td>${s.revenue_mode==='smartgrid'?s.smartgrid_multiplier+'x':'$'+s.price_per_minute}</td><td>${fmtN(s.views)}</td><td>${s.is_live?'<span class="badge badge-live">LIVE</span>':s.is_active?'Active':'Off'}</td><td><button class="btn btn-outline btn-sm" onclick="togStream(${s.id},${s.is_active?0:1})">${s.is_active?'Pause':'Enable'}</button></td></tr>`).join('');}
async function loadUsers(){const r=await fetch('get_all_users.php'),d=await r.json();document.querySelector('#usersTable tbody').innerHTML=d.users.map(u=>`<tr><td><div class="user-cell"><div class="avatar"><i class="fa fa-user"></i></div><div><b>${esc(u.display_name||u.email)}</b></div></div></td><td><span class="badge badge-${u.role}">${u.role}</span></td><td>${u.stream_count||0}</td><td style="font-size:0.8rem;color:var(--muted)">${(u.created_at||'').slice(0,10)}</td><td><button class="btn btn-outline btn-sm" onclick="togBan(${u.id},${u.is_banned?0:1})">${u.is_banned?'Unban':'Ban'}</button></td></tr>`).join('');}
async function togStream(id,a){await fetch('toggle_stream.php',{method:'POST',headers:{'Content-Type':'application/json'},body:JSON.stringify({id,is_active:a})});loadStreams();}
async function togBan(id,b){await fetch('toggle_ban.php',{method:'POST',headers:{'Content-Type':'application/json'},body:JSON.stringify({id,is_banned:b})});loadUsers();}
function esc(s){const d=document.createElement('div');d.textContent=s||'';return d.innerHTML;}
function fmtN(n){return n>=1e6?(n/1e6).toFixed(1)+'M':n>=1e3?(n/1e3).toFixed(1)+'K':n||0;}
function toast(m){const e=document.createElement('div');e.className='toast';e.textContent=m;document.getElementById('toasts').appendChild(e);setTimeout(()=>e.remove(),3000);}
</script>
</body>
</html>
