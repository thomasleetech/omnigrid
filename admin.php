<?php
session_start();
require_once 'includes/db.php';
require_once 'includes/SmartGrid.php';

// Simple admin check (in production, use proper admin role)
$isAdmin = !empty($_SESSION['user_id']);

$smartGrid = new SmartGrid($pdo);

// Handle form submissions
if ($_SERVER['REQUEST_METHOD'] === 'POST' && $isAdmin) {
    if (isset($_POST['payout_multiplier'])) {
        $mult = max(0.1, min(5.0, floatval($_POST['payout_multiplier'])));
        $smartGrid->setPayoutMultiplier($mult);
        $msg = "Payout multiplier updated to {$mult}x";
    }
}

$platformStats = $smartGrid->getPlatformStats();
$allStreams = $smartGrid->getAllStreamsWithEarnings();

// Get current config
$config = $pdo->query("SELECT config_key, config_value FROM site_config")->fetchAll(PDO::FETCH_KEY_PAIR);
$payoutMultiplier = floatval($config['payout_multiplier'] ?? 1.0);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin | OmniGrid</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=DM+Sans:wght@400;500;600;700&family=Space+Mono:wght@400;700&display=swap" rel="stylesheet">
    <style>
        :root {
            --bg: #0f0f1a;
            --surface: #1a1a2e;
            --surface-2: #252540;
            --text: #e0e0e0;
            --text-muted: #8888aa;
            --primary: #6366f1;
            --accent: #f43f5e;
            --success: #10b981;
            --warning: #f59e0b;
            --border: #2a2a45;
        }
        * { box-sizing: border-box; margin: 0; padding: 0; }
        body { font-family: 'DM Sans', sans-serif; background: var(--bg); color: var(--text); min-height: 100vh; }
        
        .layout { display: grid; grid-template-columns: 260px 1fr; min-height: 100vh; }
        
        /* Sidebar */
        .sidebar {
            background: var(--surface);
            border-right: 1px solid var(--border);
            padding: 1.5rem;
            position: sticky;
            top: 0;
            height: 100vh;
            overflow-y: auto;
        }
        .logo {
            font-family: 'Space Mono', monospace;
            font-size: 1.3rem;
            font-weight: 700;
            color: #fff;
            margin-bottom: 2rem;
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }
        .logo i { color: var(--primary); }
        .nav-section { margin-bottom: 2rem; }
        .nav-label { font-size: 0.7rem; text-transform: uppercase; letter-spacing: 0.1em; color: var(--text-muted); margin-bottom: 0.75rem; }
        .nav-item {
            display: flex;
            align-items: center;
            gap: 0.75rem;
            padding: 0.75rem 1rem;
            color: var(--text-muted);
            text-decoration: none;
            border-radius: 8px;
            margin-bottom: 0.25rem;
            transition: all 0.2s;
        }
        .nav-item:hover, .nav-item.active { background: var(--surface-2); color: #fff; }
        .nav-item.active { border-left: 3px solid var(--primary); }
        .nav-item i { width: 20px; text-align: center; }
        
        /* Main Content */
        main { padding: 2rem; overflow-y: auto; }
        .page-header { margin-bottom: 2rem; }
        .page-header h1 { font-size: 1.75rem; font-weight: 700; margin-bottom: 0.25rem; }
        .page-header p { color: var(--text-muted); }
        
        /* Stats Grid */
        .stats-grid {
            display: grid;
            grid-template-columns: repeat(4, 1fr);
            gap: 1.5rem;
            margin-bottom: 2rem;
        }
        .stat-card {
            background: var(--surface);
            border-radius: 12px;
            padding: 1.5rem;
            border: 1px solid var(--border);
        }
        .stat-card.highlight { border-color: var(--primary); background: linear-gradient(135deg, var(--surface) 0%, rgba(99,102,241,0.1) 100%); }
        .stat-icon {
            width: 40px;
            height: 40px;
            border-radius: 10px;
            display: flex;
            align-items: center;
            justify-content: center;
            margin-bottom: 1rem;
            font-size: 1.1rem;
        }
        .stat-icon.blue { background: rgba(99,102,241,0.2); color: var(--primary); }
        .stat-icon.green { background: rgba(16,185,129,0.2); color: var(--success); }
        .stat-icon.red { background: rgba(244,63,94,0.2); color: var(--accent); }
        .stat-icon.yellow { background: rgba(245,158,11,0.2); color: var(--warning); }
        .stat-value { font-family: 'Space Mono', monospace; font-size: 2rem; font-weight: 700; margin-bottom: 0.25rem; }
        .stat-label { font-size: 0.85rem; color: var(--text-muted); }
        
        /* Cards */
        .card {
            background: var(--surface);
            border-radius: 12px;
            border: 1px solid var(--border);
            margin-bottom: 1.5rem;
        }
        .card-header {
            padding: 1.25rem 1.5rem;
            border-bottom: 1px solid var(--border);
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        .card-header h2 { font-size: 1.1rem; font-weight: 600; display: flex; align-items: center; gap: 0.5rem; }
        .card-body { padding: 1.5rem; }
        
        /* SmartGrid Panel */
        .smartgrid-control {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 2rem;
        }
        .multiplier-display {
            background: var(--surface-2);
            border-radius: 12px;
            padding: 2rem;
            text-align: center;
        }
        .multiplier-value {
            font-family: 'Space Mono', monospace;
            font-size: 4rem;
            font-weight: 700;
            color: var(--primary);
            line-height: 1;
        }
        .multiplier-label { color: var(--text-muted); margin-top: 0.5rem; }
        .multiplier-form { display: flex; flex-direction: column; justify-content: center; gap: 1rem; }
        .form-group label { display: block; font-size: 0.85rem; color: var(--text-muted); margin-bottom: 0.5rem; }
        .form-group input, .form-group select {
            width: 100%;
            background: var(--surface-2);
            border: 1px solid var(--border);
            border-radius: 8px;
            padding: 0.75rem 1rem;
            color: var(--text);
            font-size: 1rem;
        }
        .form-group input:focus { outline: none; border-color: var(--primary); }
        .range-row { display: flex; align-items: center; gap: 1rem; }
        .range-row input[type="range"] { flex: 1; }
        .range-row span { font-family: 'Space Mono', monospace; min-width: 50px; }
        .btn {
            background: var(--primary);
            color: #fff;
            border: none;
            padding: 0.75rem 1.5rem;
            border-radius: 8px;
            font-size: 0.9rem;
            font-weight: 600;
            cursor: pointer;
            display: inline-flex;
            align-items: center;
            gap: 0.4rem;
        }
        .btn:hover { filter: brightness(1.1); }
        .btn-sm { padding: 0.4rem 0.8rem; font-size: 0.8rem; }
        
        /* Table */
        table { width: 100%; border-collapse: collapse; }
        th, td { padding: 1rem 1.25rem; text-align: left; border-bottom: 1px solid var(--border); }
        th { font-size: 0.75rem; text-transform: uppercase; letter-spacing: 0.05em; color: var(--text-muted); font-weight: 600; }
        td { font-size: 0.9rem; }
        tr:hover { background: var(--surface-2); }
        .status-live { color: var(--accent); }
        .status-offline { color: var(--text-muted); }
        .rate-display { font-family: 'Space Mono', monospace; color: var(--success); }
        .earnings-display { font-family: 'Space Mono', monospace; }
        
        /* Alert */
        .alert {
            padding: 1rem 1.25rem;
            border-radius: 8px;
            margin-bottom: 1.5rem;
            display: flex;
            align-items: center;
            gap: 0.75rem;
        }
        .alert-success { background: rgba(16,185,129,0.15); border: 1px solid var(--success); color: var(--success); }
        
        @media (max-width: 1200px) { .stats-grid { grid-template-columns: repeat(2, 1fr); } }
        @media (max-width: 900px) {
            .layout { grid-template-columns: 1fr; }
            .sidebar { display: none; }
            .smartgrid-control { grid-template-columns: 1fr; }
        }
    </style>
</head>
<body>
    <div class="layout">
        <aside class="sidebar">
            <div class="logo"><i class="fa fa-globe"></i> OmniGrid</div>
            <div class="nav-section">
                <div class="nav-label">Dashboard</div>
                <a href="admin.php" class="nav-item active"><i class="fa fa-chart-pie"></i> Overview</a>
                <a href="admin.php?tab=streams" class="nav-item"><i class="fa fa-video"></i> Streams</a>
                <a href="admin.php?tab=users" class="nav-item"><i class="fa fa-users"></i> Users</a>
            </div>
            <div class="nav-section">
                <div class="nav-label">SmartGrid</div>
                <a href="admin.php?tab=smartgrid" class="nav-item"><i class="fa fa-sliders-h"></i> Rate Control</a>
                <a href="admin.php?tab=payouts" class="nav-item"><i class="fa fa-dollar-sign"></i> Payouts</a>
            </div>
            <div class="nav-section">
                <div class="nav-label">Platform</div>
                <a href="./" class="nav-item"><i class="fa fa-external-link-alt"></i> View Site</a>
            </div>
        </aside>
        
        <main>
            <div class="page-header">
                <h1>Admin Dashboard</h1>
                <p>Platform overview and SmartGrid controls</p>
            </div>
            
            <?php if (!empty($msg)): ?>
            <div class="alert alert-success"><i class="fa fa-check-circle"></i> <?= htmlspecialchars($msg) ?></div>
            <?php endif; ?>
            
            <div class="stats-grid">
                <div class="stat-card">
                    <div class="stat-icon blue"><i class="fa fa-broadcast-tower"></i></div>
                    <div class="stat-value"><?= $platformStats['streams']['live'] ?></div>
                    <div class="stat-label">Live Streams</div>
                </div>
                <div class="stat-card">
                    <div class="stat-icon green"><i class="fa fa-users"></i></div>
                    <div class="stat-value"><?= $platformStats['creators'] ?></div>
                    <div class="stat-label">Creators</div>
                </div>
                <div class="stat-card">
                    <div class="stat-icon red"><i class="fa fa-eye"></i></div>
                    <div class="stat-value"><?= number_format($platformStats['engagement']['total_views']) ?></div>
                    <div class="stat-label">Total Views</div>
                </div>
                <div class="stat-card highlight">
                    <div class="stat-icon yellow"><i class="fa fa-dollar-sign"></i></div>
                    <div class="stat-value">$<?= number_format($platformStats['financials']['total_payouts'], 2) ?></div>
                    <div class="stat-label">Total Payouts</div>
                </div>
            </div>
            
            <div class="card">
                <div class="card-header">
                    <h2><i class="fa fa-sliders-h"></i> SmartGrid Rate Control</h2>
                </div>
                <div class="card-body">
                    <div class="smartgrid-control">
                        <div class="multiplier-display">
                            <div class="multiplier-value" id="multDisplay"><?= number_format($payoutMultiplier, 2) ?>x</div>
                            <div class="multiplier-label">Global Payout Multiplier</div>
                        </div>
                        <form method="POST" class="multiplier-form">
                            <div class="form-group">
                                <label>Adjust Payout Rate</label>
                                <div class="range-row">
                                    <input type="range" name="payout_multiplier" id="multSlider" min="0.1" max="3" step="0.05" value="<?= $payoutMultiplier ?>">
                                    <span id="multValue"><?= number_format($payoutMultiplier, 2) ?>x</span>
                                </div>
                            </div>
                            <p style="color:var(--text-muted);font-size:0.85rem;margin-bottom:1rem;">
                                Affects all SmartGrid calculations. 1.0x = normal rates. Lower to reduce payouts, higher to boost.
                            </p>
                            <button type="submit" class="btn"><i class="fa fa-save"></i> Update Rate</button>
                        </form>
                    </div>
                </div>
            </div>
            
            <div class="card">
                <div class="card-header">
                    <h2><i class="fa fa-video"></i> All Streams</h2>
                </div>
                <div class="card-body" style="padding:0;">
                    <table>
                        <thead>
                            <tr>
                                <th>Stream</th>
                                <th>Creator</th>
                                <th>Status</th>
                                <th>Views</th>
                                <th>Rate/min</th>
                                <th>Total Earned</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($allStreams as $s): 
                                $rate = $smartGrid->calculateRate($s['id']);
                            ?>
                            <tr>
                                <td><strong><?= htmlspecialchars($s['title']) ?></strong></td>
                                <td><?= htmlspecialchars($s['display_name'] ?: explode('@', $s['email'])[0]) ?></td>
                                <td>
                                    <?php if ($s['is_live']): ?>
                                        <span class="status-live"><i class="fa fa-circle"></i> Live</span>
                                    <?php else: ?>
                                        <span class="status-offline"><i class="fa fa-circle"></i> Offline</span>
                                    <?php endif; ?>
                                </td>
                                <td><?= number_format($s['views'] ?? 0) ?></td>
                                <td class="rate-display">$<?= number_format($rate['rate_cents_per_min'] / 100, 4) ?></td>
                                <td class="earnings-display">$<?= number_format(($s['total_earnings_cents'] ?? 0) / 100, 2) ?></td>
                                <td>
                                    <a href="live.php?id=<?= $s['id'] ?>" class="btn btn-sm"><i class="fa fa-eye"></i></a>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                            <?php if (empty($allStreams)): ?>
                            <tr><td colspan="7" style="text-align:center;color:var(--text-muted);">No streams yet</td></tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </main>
    </div>
    
    <script>
        const slider = document.getElementById('multSlider');
        const display = document.getElementById('multDisplay');
        const value = document.getElementById('multValue');
        slider.oninput = () => {
            const v = parseFloat(slider.value).toFixed(2);
            display.textContent = v + 'x';
            value.textContent = v + 'x';
        };
    </script>
</body>
</html>
