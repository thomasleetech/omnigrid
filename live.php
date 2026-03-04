<?php
session_start();
require_once 'includes/db.php';
$stream_id = (int)($_GET['id'] ?? 0);
$room = preg_replace('/[^A-Za-z0-9]/', '', $_GET['room'] ?? '');
$stream = null;
if ($stream_id) {
    try {
        $stmt = $pdo->prepare("SELECT s.*, u.display_name FROM streams s JOIN users u ON s.user_id = u.id WHERE s.id = ?");
        $stmt->execute([$stream_id]);
        $stream = $stmt->fetch();
        if ($stream) {
            try { $pdo->prepare("UPDATE stream_metrics SET views = views + 1 WHERE stream_id = ?")->execute([$stream_id]); } catch (Exception $e) {}
        }
    } catch (Exception $e) {}
}
$title = $stream['title'] ?? 'Live Stream';
$creator = $stream['display_name'] ?? 'Anonymous';
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width,initial-scale=1.0">
<title><?= htmlspecialchars($title) ?> — OmniGrid</title>
<link rel="preconnect" href="https://fonts.googleapis.com">
<link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&family=JetBrains+Mono:wght@500;700&display=swap" rel="stylesheet">
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
    --danger: #ef4444;
    --gradient-primary: linear-gradient(135deg, #6366f1, #a855f7);
    --radius: 12px;
    --radius-sm: 8px;
    --radius-pill: 100px;
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

/* Header */
.header {
    background: var(--surface);
    padding: 0 1.5rem;
    height: 52px;
    display: flex;
    justify-content: space-between;
    align-items: center;
    border-bottom: 1px solid var(--border);
}
.logo {
    font-family: 'JetBrains Mono', monospace;
    font-size: 1.15rem;
    font-weight: 700;
    color: #fff;
    text-decoration: none;
    display: flex;
    align-items: center;
    gap: 0.5rem;
}
.logo .icon {
    width: 26px;
    height: 26px;
    background: var(--gradient-primary);
    border-radius: 6px;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 0.7rem;
}
.logo span { color: var(--primary-light); }

.header-actions { display: flex; gap: 0.5rem; }
.btn-sm {
    background: transparent;
    border: 1px solid var(--border);
    color: var(--text-secondary);
    padding: 0.4rem 0.85rem;
    border-radius: var(--radius-sm);
    font-family: inherit;
    font-size: 0.8rem;
    font-weight: 500;
    cursor: pointer;
    text-decoration: none;
    display: inline-flex;
    align-items: center;
    gap: 0.35rem;
    transition: var(--transition);
}
.btn-sm:hover { border-color: var(--border-light); color: var(--text); }

/* Main layout */
.main {
    display: grid;
    grid-template-columns: 1fr 360px;
    height: calc(100vh - 52px);
}

/* Video Section */
.video-section {
    background: #000;
    position: relative;
    display: flex;
    align-items: center;
    justify-content: center;
}
#video {
    width: 100%;
    height: 100%;
    object-fit: contain;
    background: #000;
}
.placeholder {
    position: absolute;
    text-align: center;
    color: var(--muted);
}
.placeholder .ph-icon {
    width: 72px;
    height: 72px;
    background: var(--surface);
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 1.75rem;
    margin: 0 auto 1rem;
    border: 1px solid var(--border);
}
.placeholder p {
    font-size: 0.95rem;
}
.placeholder .sub {
    font-size: 0.8rem;
    color: var(--muted);
    margin-top: 0.35rem;
}

/* Video overlays */
.video-overlay {
    position: absolute;
    top: 1rem;
    left: 1rem;
    right: 1rem;
    display: flex;
    justify-content: space-between;
    align-items: flex-start;
    pointer-events: none;
}
.live-badge {
    background: var(--surface);
    border: 1px solid var(--border);
    padding: 0.3rem 0.75rem;
    border-radius: var(--radius-pill);
    font-size: 0.75rem;
    font-weight: 600;
    display: flex;
    align-items: center;
    gap: 0.35rem;
    color: var(--muted);
}
.live-badge::before {
    content: '';
    width: 7px;
    height: 7px;
    background: var(--muted);
    border-radius: 50%;
}
.live-badge.live {
    background: var(--danger);
    border-color: var(--danger);
    color: #fff;
}
.live-badge.live::before {
    background: #fff;
    animation: pulse 1s ease-in-out infinite;
}
@keyframes pulse { 0%, 100% { opacity: 1; } 50% { opacity: 0.4; } }

.viewers-pill {
    background: rgba(0, 0, 0, 0.7);
    backdrop-filter: blur(8px);
    padding: 0.3rem 0.65rem;
    border-radius: var(--radius-pill);
    font-size: 0.8rem;
    display: flex;
    align-items: center;
    gap: 0.3rem;
}

/* Sidebar */
.sidebar {
    background: var(--surface);
    border-left: 1px solid var(--border);
    display: flex;
    flex-direction: column;
}

/* Stream Info */
.stream-info {
    padding: 1.25rem;
    border-bottom: 1px solid var(--border);
}
.stream-info h1 {
    font-size: 1.05rem;
    font-weight: 600;
    margin-bottom: 0.35rem;
    letter-spacing: -0.01em;
}
.stream-creator {
    color: var(--muted);
    font-size: 0.85rem;
    display: flex;
    align-items: center;
    gap: 0.35rem;
}
.stream-creator .avatar {
    width: 20px;
    height: 20px;
    background: var(--primary-glow);
    color: var(--primary-light);
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 0.55rem;
}

/* Room Connection */
.room-box {
    padding: 1.25rem;
    border-bottom: 1px solid var(--border);
}
.room-input-group {
    background: var(--bg);
    border: 1px solid var(--border);
    border-radius: var(--radius-sm);
    display: flex;
    align-items: center;
    padding: 0.15rem;
    transition: var(--transition);
    margin-bottom: 0.75rem;
}
.room-input-group:focus-within {
    border-color: var(--primary);
    box-shadow: 0 0 0 3px var(--primary-glow);
}
.room-input-group label {
    font-size: 0.7rem;
    color: var(--muted);
    padding: 0 0.5rem 0 0.75rem;
    white-space: nowrap;
    text-transform: uppercase;
    font-weight: 600;
    letter-spacing: 0.05em;
}
.room-input-group input {
    flex: 1;
    background: transparent;
    border: none;
    color: var(--primary-light);
    font-family: 'JetBrains Mono', monospace;
    font-size: 1.1rem;
    font-weight: 700;
    letter-spacing: 0.12em;
    text-transform: uppercase;
    padding: 0.55rem 0.5rem;
    outline: none;
}
.room-input-group input::placeholder {
    color: var(--muted);
    font-weight: 500;
}

.connect-btn {
    width: 100%;
    background: var(--gradient-primary);
    color: #fff;
    border: none;
    padding: 0.7rem;
    border-radius: var(--radius-sm);
    font-family: inherit;
    font-size: 0.9rem;
    font-weight: 600;
    cursor: pointer;
    display: flex;
    align-items: center;
    justify-content: center;
    gap: 0.5rem;
    transition: var(--transition);
    box-shadow: 0 4px 16px rgba(99, 102, 241, 0.2);
}
.connect-btn:hover {
    transform: translateY(-1px);
    box-shadow: 0 6px 24px rgba(99, 102, 241, 0.3);
}
.connect-btn:disabled {
    opacity: 0.6;
    cursor: not-allowed;
    transform: none;
}

/* Chat */
.chat {
    flex: 1;
    display: flex;
    flex-direction: column;
    min-height: 0;
}
.chat-head {
    padding: 0.75rem 1.25rem;
    border-bottom: 1px solid var(--border);
    font-size: 0.85rem;
    font-weight: 600;
    display: flex;
    align-items: center;
    gap: 0.4rem;
    color: var(--text-secondary);
}
.chat-msgs {
    flex: 1;
    overflow-y: auto;
    padding: 1rem 1.25rem;
    font-size: 0.85rem;
    display: flex;
    flex-direction: column;
    gap: 0.5rem;
}
.chat-msgs::-webkit-scrollbar { width: 4px; }
.chat-msgs::-webkit-scrollbar-track { background: transparent; }
.chat-msgs::-webkit-scrollbar-thumb { background: var(--border); border-radius: 2px; }

.msg {
    line-height: 1.5;
}
.msg .name {
    color: var(--primary-light);
    font-weight: 600;
    font-size: 0.8rem;
}
.msg.system {
    color: var(--muted);
    font-size: 0.8rem;
    font-style: italic;
}

.chat-input {
    padding: 0.75rem 1rem;
    border-top: 1px solid var(--border);
    display: flex;
    gap: 0.4rem;
}
.chat-input input {
    flex: 1;
    background: var(--bg);
    border: 1px solid var(--border);
    border-radius: var(--radius-sm);
    padding: 0.55rem 0.85rem;
    color: var(--text);
    font-family: inherit;
    font-size: 0.85rem;
    transition: var(--transition);
}
.chat-input input:focus {
    outline: none;
    border-color: var(--primary);
}
.chat-input input::placeholder { color: var(--muted); }
.chat-input button {
    background: var(--primary);
    border: none;
    color: #fff;
    width: 36px;
    border-radius: var(--radius-sm);
    cursor: pointer;
    font-size: 0.8rem;
    transition: var(--transition);
    display: flex;
    align-items: center;
    justify-content: center;
}
.chat-input button:hover { background: var(--primary-light); }

@media (max-width: 900px) {
    .main {
        grid-template-columns: 1fr;
        grid-template-rows: 45vh 1fr;
    }
    .sidebar { border-left: none; border-top: 1px solid var(--border); }
}
</style>
</head>
<body>

<header class="header">
    <a href="./" class="logo"><div class="icon"><i class="fa-solid fa-cube"></i></div> Omni<span>Grid</span></a>
    <div class="header-actions">
        <a href="globe.php" class="btn-sm"><i class="fa-solid fa-earth-americas"></i> Globe</a>
        <a href="./" class="btn-sm"><i class="fa-solid fa-th-large"></i> Grid</a>
    </div>
</header>

<main class="main">
    <div class="video-section">
        <div class="video-overlay">
            <div class="live-badge" id="badge">Offline</div>
            <div class="viewers-pill"><i class="fa-solid fa-eye"></i> <span id="vc"><?= number_format($stream['views'] ?? 0) ?></span></div>
        </div>
        <video id="video" autoplay playsinline></video>
        <div class="placeholder" id="ph">
            <div class="ph-icon"><i class="fa-solid fa-satellite-dish"></i></div>
            <p id="status">Enter room code to connect</p>
            <p class="sub">Share a room code with the broadcaster</p>
        </div>
    </div>

    <aside class="sidebar">
        <div class="stream-info">
            <h1><?= htmlspecialchars($title) ?></h1>
            <div class="stream-creator">
                <div class="avatar"><i class="fa-solid fa-user"></i></div>
                <?= htmlspecialchars($creator) ?>
            </div>
        </div>

        <div class="room-box">
            <div class="room-input-group">
                <label>Room</label>
                <input type="text" id="roomIn" value="<?= htmlspecialchars($room) ?>" placeholder="XXXXXX" maxlength="10">
            </div>
            <button class="connect-btn" id="connBtn" onclick="connect()">
                <i class="fa-solid fa-plug"></i> Connect
            </button>
        </div>

        <div class="chat">
            <div class="chat-head"><i class="fa-solid fa-comments"></i> Chat</div>
            <div class="chat-msgs" id="msgs">
                <div class="msg system">Enter a room code to start watching</div>
            </div>
            <div class="chat-input">
                <input type="text" id="chatIn" placeholder="Say something...">
                <button onclick="sendChat()"><i class="fa-solid fa-paper-plane"></i></button>
            </div>
        </div>
    </aside>
</main>

<script>
const video = document.getElementById('video'), ph = document.getElementById('ph'), badge = document.getElementById('badge');
let pc = null, poll = null, room = '', viewerId = null;

async function connect() {
    room = document.getElementById('roomIn').value.trim().toUpperCase();
    if (!room) { document.getElementById('status').textContent = 'Enter a room code'; return; }
    const btn = document.getElementById('connBtn');
    btn.disabled = true;
    btn.innerHTML = '<i class="fa-solid fa-spinner fa-spin"></i> Connecting...';
    document.getElementById('status').textContent = 'Connecting to ' + room + '...';
    document.querySelector('.placeholder .sub').textContent = 'Establishing peer connection...';
    addChat('System', 'Connecting...', true);
    viewerId = 'v' + Date.now();
    startPoll();
}

function startPoll() {
    if (poll) clearInterval(poll);
    poll = setInterval(async () => {
        try {
            const r = await fetch('signal.php?room=' + room + '&action=poll&from=viewer&viewerId=' + viewerId);
            const d = await r.json();
            if (d.answer && pc && pc.signalingState !== 'stable') await pc.setRemoteDescription(new RTCSessionDescription(d.answer));
            if (d.candidate && pc) await pc.addIceCandidate(new RTCIceCandidate(d.candidate));
        } catch (e) {}
    }, 1000);
    createOffer();
}

async function createOffer() {
    pc = new RTCPeerConnection({ iceServers: [{ urls: 'stun:stun.l.google.com:19302' }] });
    pc.addTransceiver('video', { direction: 'recvonly' });
    pc.addTransceiver('audio', { direction: 'recvonly' });
    pc.ontrack = e => {
        video.srcObject = e.streams[0]; ph.style.display = 'none';
        setLive(true); addChat('System', 'Connected!', true);
    };
    pc.onicecandidate = async e => {
        if (e.candidate) await fetch('signal.php?room=' + room + '&action=candidate', {
            method: 'POST', headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ from: 'viewer', viewerId, candidate: e.candidate })
        });
    };
    pc.onconnectionstatechange = () => {
        if (pc.connectionState === 'disconnected' || pc.connectionState === 'failed') {
            setLive(false); document.getElementById('status').textContent = 'Disconnected';
            document.querySelector('.placeholder .sub').textContent = 'Attempting to reconnect...';
            ph.style.display = 'block'; addChat('System', 'Disconnected', true);
            pc = null; setTimeout(createOffer, 3000);
        }
    };
    const offer = await pc.createOffer();
    await pc.setLocalDescription(offer);
    await fetch('signal.php?room=' + room + '&action=offer', {
        method: 'POST', headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({ ...offer, viewerId })
    });
}

function setLive(live) {
    badge.classList.toggle('live', live);
    badge.textContent = live ? 'LIVE' : 'Offline';
    const btn = document.getElementById('connBtn');
    btn.disabled = false;
    btn.innerHTML = '<i class="fa-solid fa-plug"></i> ' + (live ? 'Connected' : 'Connect');
}

function addChat(name, text, isSystem) {
    const m = document.getElementById('msgs');
    const el = document.createElement('div');
    el.className = 'msg' + (isSystem ? ' system' : '');
    if (isSystem) {
        el.textContent = text;
    } else {
        el.innerHTML = '<span class="name">' + escHtml(name) + '</span> ' + escHtml(text);
    }
    m.appendChild(el);
    m.scrollTop = m.scrollHeight;
}

function escHtml(s) {
    const d = document.createElement('div');
    d.textContent = s;
    return d.innerHTML;
}

function sendChat() {
    const i = document.getElementById('chatIn'), t = i.value.trim();
    if (t) { addChat('You', t, false); i.value = ''; }
}

document.getElementById('chatIn').onkeypress = e => { if (e.key === 'Enter') sendChat(); };
<?php if ($room): ?>setTimeout(connect, 500);<?php endif; ?>
</script>
</body>
</html>
