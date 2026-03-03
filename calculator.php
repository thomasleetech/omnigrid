<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width,initial-scale=1.0">
<title>Earnings Calculator — OmniGrid</title>
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
.btn-primary {
    background: var(--gradient-primary); color: #fff;
    padding: 0.55rem 1.35rem; border-radius: var(--radius-pill);
    font-size: 0.85rem; font-weight: 600;
    display: inline-flex; align-items: center; gap: 0.45rem;
    box-shadow: 0 4px 16px rgba(99, 102, 241, 0.25); transition: var(--transition);
}
.btn-primary:hover { transform: translateY(-1px); }

/* Container */
.container { max-width: 1100px; margin: 0 auto; padding: 0 2rem; }

/* Page Header */
.page-top { padding: 3rem 0 2rem; text-align: center; }
.page-top h1 { font-size: 2rem; font-weight: 800; letter-spacing: -0.03em; margin-bottom: 0.5rem; }
.page-top h1 .gradient {
    background: var(--gradient-primary); -webkit-background-clip: text;
    -webkit-text-fill-color: transparent; background-clip: text;
}
.page-top p { color: var(--text-secondary); font-size: 1rem; max-width: 500px; margin: 0 auto; }

/* Layout */
.calc-layout { display: grid; grid-template-columns: 1fr 1fr; gap: 2rem; padding-bottom: 4rem; }

/* Card */
.card { background: var(--surface); border: 1px solid var(--border); border-radius: var(--radius); }
.card-header { padding: 1rem 1.5rem; border-bottom: 1px solid var(--border); }
.card-header h2 { font-size: 1rem; font-weight: 600; display: flex; align-items: center; gap: 0.5rem; }
.card-body { padding: 1.5rem; }

/* Sliders */
.slider-group { margin-bottom: 1.75rem; }
.slider-group:last-child { margin-bottom: 0; }
.slider-label {
    display: flex; justify-content: space-between; align-items: center;
    margin-bottom: 0.5rem;
}
.slider-label .name { font-size: 0.85rem; font-weight: 500; color: var(--text-secondary); display: flex; align-items: center; gap: 0.4rem; }
.slider-label .name i { font-size: 0.75rem; color: var(--muted); }
.slider-label .value {
    font-family: 'JetBrains Mono', monospace; font-size: 0.9rem; font-weight: 700;
    color: var(--primary-light); min-width: 60px; text-align: right;
}

input[type="range"] {
    -webkit-appearance: none; width: 100%; height: 6px;
    background: var(--border); border-radius: 3px; outline: none;
    cursor: pointer;
}
input[type="range"]::-webkit-slider-thumb {
    -webkit-appearance: none; width: 20px; height: 20px;
    background: var(--primary); border-radius: 50%; cursor: pointer;
    box-shadow: 0 2px 8px rgba(99, 102, 241, 0.4);
    transition: box-shadow 0.2s;
}
input[type="range"]::-webkit-slider-thumb:hover {
    box-shadow: 0 3px 14px rgba(99, 102, 241, 0.6);
}
input[type="range"]::-moz-range-thumb {
    width: 20px; height: 20px; background: var(--primary);
    border-radius: 50%; border: none; cursor: pointer;
}

.slider-hint { font-size: 0.72rem; color: var(--muted); margin-top: 0.35rem; display: flex; justify-content: space-between; }

/* Results */
.result-hero {
    text-align: center; padding: 2rem 1rem; margin-bottom: 1.5rem;
    background: var(--bg); border: 1px solid var(--border); border-radius: var(--radius);
    position: relative; overflow: hidden;
}
.result-hero::before {
    content: ''; position: absolute; inset: 0;
    background: radial-gradient(circle at 50% 50%, rgba(99, 102, 241, 0.06) 0%, transparent 70%);
}
.result-hero .label { font-size: 0.78rem; color: var(--muted); text-transform: uppercase; letter-spacing: 0.08em; font-weight: 600; position: relative; }
.result-hero .big {
    font-family: 'JetBrains Mono', monospace; font-size: 3rem; font-weight: 700;
    background: var(--gradient-primary); -webkit-background-clip: text;
    -webkit-text-fill-color: transparent; position: relative; line-height: 1.2;
}
.result-hero .sub { font-size: 0.82rem; color: var(--text-secondary); position: relative; margin-top: 0.2rem; }

.results-grid { display: grid; grid-template-columns: repeat(2, 1fr); gap: 0.75rem; margin-bottom: 1.5rem; }
.result-card {
    padding: 1rem; background: var(--bg); border: 1px solid var(--border);
    border-radius: var(--radius-sm); text-align: center;
}
.result-card .period { font-size: 0.7rem; color: var(--muted); text-transform: uppercase; letter-spacing: 0.05em; font-weight: 600; margin-bottom: 0.2rem; }
.result-card .amount { font-family: 'JetBrains Mono', monospace; font-size: 1.2rem; font-weight: 700; color: var(--success); }

/* Factor Bars */
.factor-bars { display: flex; flex-direction: column; gap: 0.85rem; }
.factor-row { display: flex; align-items: center; gap: 0.75rem; }
.factor-label { width: 80px; font-size: 0.78rem; color: var(--text-secondary); font-weight: 500; flex-shrink: 0; }
.factor-track { flex: 1; height: 8px; background: var(--border); border-radius: 4px; overflow: hidden; }
.factor-fill { height: 100%; border-radius: 4px; transition: width 0.4s ease; }
.factor-val { width: 45px; font-family: 'JetBrains Mono', monospace; font-size: 0.8rem; font-weight: 600; text-align: right; }

/* Tier Badge */
.tier-badge {
    display: flex; align-items: center; justify-content: center; gap: 0.6rem;
    padding: 0.85rem 1.25rem; border-radius: var(--radius-sm);
    font-size: 0.9rem; font-weight: 600;
    border: 1px solid var(--border); margin-top: 1rem;
}
.tier-badge .tier-icon { font-size: 1.25rem; }

@media (max-width: 900px) {
    .calc-layout { grid-template-columns: 1fr; }
    .nav { display: none; }
    .results-grid { grid-template-columns: 1fr 1fr; }
}
</style>
</head>
<body>

<header class="header">
    <a href="./" class="logo">
        <div class="logo-icon"><i class="fa-solid fa-cube"></i></div>
        <div class="logo-text">Omni<span>Grid</span></div>
    </a>
    <nav class="nav">
        <a href="globe.php"><i class="fa-solid fa-earth-americas"></i> Globe</a>
        <a href="watch.php"><i class="fa-solid fa-play"></i> Browse</a>
        <a href="calculator.php" style="color: var(--primary-light)"><i class="fa-solid fa-calculator"></i> Earnings</a>
        <a href="login.php"><i class="fa-solid fa-arrow-right-to-bracket"></i> Login</a>
        <a href="contribute/" class="btn-primary"><i class="fa-solid fa-broadcast-tower"></i> Go Live</a>
    </nav>
</header>

<div class="container">
    <div class="page-top">
        <h1>Earnings <span class="gradient">Calculator</span></h1>
        <p>See how much you could earn with smartGrid based on your audience metrics</p>
    </div>

    <div class="calc-layout">
        <!-- Inputs -->
        <div class="card">
            <div class="card-header"><h2><i class="fa-solid fa-sliders" style="color: var(--primary-light)"></i> Your Metrics</h2></div>
            <div class="card-body">
                <div class="slider-group">
                    <div class="slider-label">
                        <span class="name"><i class="fa-solid fa-eye"></i> Concurrent Viewers</span>
                        <span class="value" id="viewersVal">50</span>
                    </div>
                    <input type="range" id="viewers" min="0" max="10000" value="50" oninput="calc()">
                    <div class="slider-hint"><span>0</span><span>10,000</span></div>
                </div>

                <div class="slider-group">
                    <div class="slider-label">
                        <span class="name"><i class="fa-solid fa-clock"></i> Avg Watch Time (min)</span>
                        <span class="value" id="watchVal">5</span>
                    </div>
                    <input type="range" id="watchTime" min="0" max="60" value="5" oninput="calc()">
                    <div class="slider-hint"><span>0</span><span>60 min</span></div>
                </div>

                <div class="slider-group">
                    <div class="slider-label">
                        <span class="name"><i class="fa-solid fa-coins"></i> Tips per Hour ($)</span>
                        <span class="value" id="tipsVal">$2</span>
                    </div>
                    <input type="range" id="tips" min="0" max="100" value="2" oninput="calc()">
                    <div class="slider-hint"><span>$0</span><span>$100</span></div>
                </div>

                <div class="slider-group">
                    <div class="slider-label">
                        <span class="name"><i class="fa-solid fa-arrow-trend-up"></i> New Subs per Day</span>
                        <span class="value" id="subsVal">3</span>
                    </div>
                    <input type="range" id="newSubs" min="0" max="100" value="3" oninput="calc()">
                    <div class="slider-hint"><span>0</span><span>100</span></div>
                </div>

                <div class="slider-group">
                    <div class="slider-label">
                        <span class="name"><i class="fa-solid fa-broadcast-tower"></i> Hours Streaming / Day</span>
                        <span class="value" id="hoursVal">2</span>
                    </div>
                    <input type="range" id="streamHours" min="0" max="12" value="2" step="0.5" oninput="calc()">
                    <div class="slider-hint"><span>0</span><span>12 hrs</span></div>
                </div>
            </div>
        </div>

        <!-- Results -->
        <div>
            <div class="card" style="margin-bottom: 1.5rem">
                <div class="card-header"><h2><i class="fa-solid fa-chart-bar" style="color: var(--success)"></i> Projected Earnings</h2></div>
                <div class="card-body">
                    <div class="result-hero">
                        <div class="label">Estimated Monthly</div>
                        <div class="big" id="monthly">$0.00</div>
                        <div class="sub" id="rateDisplay">Rate: 0.00&cent;/min</div>
                    </div>

                    <div class="results-grid">
                        <div class="result-card">
                            <div class="period">Hourly</div>
                            <div class="amount" id="hourly">$0.00</div>
                        </div>
                        <div class="result-card">
                            <div class="period">Daily</div>
                            <div class="amount" id="daily">$0.00</div>
                        </div>
                        <div class="result-card">
                            <div class="period">Weekly</div>
                            <div class="amount" id="weekly">$0.00</div>
                        </div>
                        <div class="result-card">
                            <div class="period">Yearly</div>
                            <div class="amount" id="yearly">$0.00</div>
                        </div>
                    </div>

                    <div id="tierBadge" class="tier-badge"></div>
                </div>
            </div>

            <div class="card">
                <div class="card-header"><h2><i class="fa-solid fa-bolt" style="color: var(--warning)"></i> Score Breakdown</h2></div>
                <div class="card-body">
                    <div class="factor-bars">
                        <div class="factor-row">
                            <span class="factor-label">Viewers</span>
                            <div class="factor-track"><div class="factor-fill" id="barV" style="width:0;background:#6366f1"></div></div>
                            <span class="factor-val" id="scoreV" style="color:#6366f1">0</span>
                        </div>
                        <div class="factor-row">
                            <span class="factor-label">Retention</span>
                            <div class="factor-track"><div class="factor-fill" id="barR" style="width:0;background:#10b981"></div></div>
                            <span class="factor-val" id="scoreR" style="color:#10b981">0</span>
                        </div>
                        <div class="factor-row">
                            <span class="factor-label">Engagement</span>
                            <div class="factor-track"><div class="factor-fill" id="barE" style="width:0;background:#f59e0b"></div></div>
                            <span class="factor-val" id="scoreE" style="color:#f59e0b">0</span>
                        </div>
                        <div class="factor-row">
                            <span class="factor-label">Growth</span>
                            <div class="factor-track"><div class="factor-fill" id="barG" style="width:0;background:#ef4444"></div></div>
                            <span class="factor-val" id="scoreG" style="color:#ef4444">0</span>
                        </div>
                        <div class="factor-row">
                            <span class="factor-label">Quality</span>
                            <div class="factor-track"><div class="factor-fill" id="barQ" style="width:0;background:#a855f7"></div></div>
                            <span class="factor-val" id="scoreQ" style="color:#a855f7">0</span>
                        </div>
                    </div>
                    <div style="margin-top: 1rem; text-align: center; font-size: 0.8rem; color: var(--muted)">
                        Composite Score: <strong id="compositeScore" style="color: var(--primary-light)">0.0</strong> / 100
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
// Port of SmartGrid scoring (mirrors PHP SmartGrid.php)
const BASE_RATE = 0.50, MAX_RATE = 25.0, MIN_RATE = 0.10;
const W = { v: 0.35, r: 0.25, e: 0.20, g: 0.15, q: 0.05 };

function scoreViewers(current, peak) {
    if (current === 0) return 0;
    peak = Math.max(peak, current);
    const base = Math.min(100, Math.log2(current + 1) * 15);
    const retention = peak > 0 ? current / peak : 0;
    return base * (0.7 + 0.3 * retention);
}

function scoreRetention(avgWatchTime) {
    const avgScore = Math.min(50, avgWatchTime * 5);
    const totalScore = Math.min(50, Math.log10(avgWatchTime * 60 + 1) * 15);
    return avgScore + totalScore;
}

function scoreEngagement(tipsCents, viewers) {
    if (viewers === 0) return 0;
    const tipScore = Math.min(50, tipsCents * 2);
    const chatRatio = Math.min(2, viewers * 0.3);
    const chatScore = Math.min(50, chatRatio * 25);
    return tipScore + chatScore;
}

function scoreGrowth(newSubs, totalSubs) {
    totalSubs = Math.max(totalSubs, newSubs * 5);
    if (totalSubs === 0) return newSubs > 0 ? 50 : 0;
    const growthRate = newSubs / totalSubs;
    return Math.min(100, growthRate * 500 + newSubs * 5);
}

function scoreQuality() {
    // Assume good quality for calculator
    return 74.5; // 99% uptime score (49.5) + 2500 bitrate score (25)
}

function getTier(monthly) {
    if (monthly >= 2000) return { name: 'Top Creator', icon: '&#x1F451;', color: '#f59e0b', bg: 'var(--warning-glow)' };
    if (monthly >= 500) return { name: 'Established', icon: '&#x2B50;', color: '#a855f7', bg: 'rgba(168,85,247,0.12)' };
    if (monthly >= 100) return { name: 'Rising', icon: '&#x1F680;', color: '#6366f1', bg: 'var(--primary-glow)' };
    if (monthly >= 10) return { name: 'Beginner', icon: '&#x1F331;', color: '#10b981', bg: 'var(--success-glow)' };
    return { name: 'Getting Started', icon: '&#x1F44B;', color: 'var(--muted)', bg: 'rgba(255,255,255,0.03)' };
}

function calc() {
    const viewers = +document.getElementById('viewers').value;
    const watchTime = +document.getElementById('watchTime').value;
    const tips = +document.getElementById('tips').value;
    const newSubs = +document.getElementById('newSubs').value;
    const hours = +document.getElementById('streamHours').value;

    // Update display values
    document.getElementById('viewersVal').textContent = viewers.toLocaleString();
    document.getElementById('watchVal').textContent = watchTime;
    document.getElementById('tipsVal').textContent = '$' + tips;
    document.getElementById('subsVal').textContent = newSubs;
    document.getElementById('hoursVal').textContent = hours;

    // Calculate scores
    const vScore = scoreViewers(viewers, viewers);
    const rScore = scoreRetention(watchTime);
    const eScore = scoreEngagement(tips, viewers);
    const gScore = scoreGrowth(newSubs, newSubs * 10);
    const qScore = scoreQuality();

    const composite = vScore * W.v + rScore * W.r + eScore * W.e + gScore * W.g + qScore * W.q;

    // Calculate rate
    let rate = BASE_RATE * Math.pow(2, composite / 25);
    rate = Math.max(MIN_RATE, Math.min(MAX_RATE, rate));

    // Earnings
    const minutesPerDay = hours * 60;
    const hourlyEarnings = rate * 60 / 100;
    const dailyEarnings = rate * minutesPerDay / 100;
    const weeklyEarnings = dailyEarnings * 7;
    const monthlyEarnings = dailyEarnings * 30;
    const yearlyEarnings = dailyEarnings * 365;

    // Update results
    document.getElementById('monthly').textContent = '$' + monthlyEarnings.toFixed(2);
    document.getElementById('rateDisplay').innerHTML = 'Rate: ' + rate.toFixed(2) + '&cent;/min &bull; Score: ' + composite.toFixed(1);
    document.getElementById('hourly').textContent = '$' + hourlyEarnings.toFixed(2);
    document.getElementById('daily').textContent = '$' + dailyEarnings.toFixed(2);
    document.getElementById('weekly').textContent = '$' + weeklyEarnings.toFixed(2);
    document.getElementById('yearly').textContent = '$' + yearlyEarnings.toFixed(2);

    // Update factor bars
    const factors = [
        ['V', vScore], ['R', rScore], ['E', eScore], ['G', gScore], ['Q', qScore]
    ];
    factors.forEach(([k, s]) => {
        document.getElementById('bar' + k).style.width = Math.min(s, 100) + '%';
        document.getElementById('score' + k).textContent = s.toFixed(1);
    });
    document.getElementById('compositeScore').textContent = composite.toFixed(1);

    // Tier
    const tier = getTier(monthlyEarnings);
    document.getElementById('tierBadge').innerHTML =
        '<span class="tier-icon">' + tier.icon + '</span> ' +
        '<span>Creator Tier: <strong style="color:' + tier.color + '">' + tier.name + '</strong></span>';
    document.getElementById('tierBadge').style.background = tier.bg;
    document.getElementById('tierBadge').style.borderColor = tier.color + '33';
}

calc();
</script>
</body>
</html>
