<?php
session_start();
if (empty($_SESSION['user_id'])) { header('Location: ../login.php'); exit; }
require_once '../includes/db.php';
$uid = $_SESSION['user_id'];
$stmt = $pdo->prepare("SELECT * FROM users WHERE id = ?");
$stmt->execute([$uid]);
$user = $stmt->fetch();

// Get user's streams for the stream selector
$stmt = $pdo->prepare("SELECT id, title, type, vibe_tag FROM streams WHERE user_id = ? AND is_active = 1 ORDER BY title");
$stmt->execute([$uid]);
$streams = $stmt->fetchAll();

// If a specific stream ID was passed, pre-select it
$preselect = (int)($_GET['id'] ?? 0);
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width,initial-scale=1.0">
<title>Go Live — OmniGrid</title>
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
    --danger: #ef4444;
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
    background: #000;
    color: var(--text);
    height: 100vh;
    overflow: hidden;
    -webkit-font-smoothing: antialiased;
}

.studio { display: grid; grid-template-columns: 1fr 340px; height: 100vh; }

/* Preview */
.preview { position: relative; background: #000; display: flex; align-items: center; justify-content: center; }
#video { width: 100%; height: 100%; object-fit: contain; }

.preview-overlay {
    position: absolute; top: 0; left: 0; right: 0;
    padding: 1rem 1.25rem; display: flex; justify-content: space-between; align-items: flex-start;
    background: linear-gradient(rgba(0,0,0,0.6), transparent); pointer-events: none;
}
.preview-overlay > * { pointer-events: auto; }

.top-left { display: flex; align-items: center; gap: 0.65rem; }
.back-btn {
    background: rgba(255,255,255,0.08); backdrop-filter: blur(8px);
    border: 1px solid rgba(255,255,255,0.1); color: #fff;
    width: 36px; height: 36px; border-radius: 50%;
    display: flex; align-items: center; justify-content: center;
    cursor: pointer; font-size: 0.85rem; transition: var(--transition); text-decoration: none;
}
.back-btn:hover { background: rgba(255,255,255,0.15); }

.live-ind {
    background: rgba(0,0,0,0.5); backdrop-filter: blur(8px);
    padding: 0.3rem 0.85rem; border-radius: var(--radius-pill);
    font-size: 0.8rem; font-weight: 700; color: var(--muted);
    display: flex; align-items: center; gap: 0.4rem;
}
.live-ind.active { background: var(--danger); color: #fff; }
.live-ind::before {
    content: ''; width: 7px; height: 7px; background: currentColor; border-radius: 50%;
}
.live-ind.active::before { background: #fff; animation: pulse 1s ease-in-out infinite; }
@keyframes pulse { 0%,100%{opacity:1}50%{opacity:0.4} }

.top-right { display: flex; gap: 0.5rem; align-items: center; }
.pill {
    background: rgba(0,0,0,0.5); backdrop-filter: blur(8px);
    padding: 0.3rem 0.75rem; border-radius: var(--radius-pill);
    font-size: 0.8rem; display: flex; align-items: center; gap: 0.3rem;
}
.pill.viewers i { color: var(--primary-light); }
.pill.duration { font-family: 'JetBrains Mono', monospace; font-weight: 600; }
.pill.earnings { color: var(--success); font-family: 'JetBrains Mono', monospace; font-weight: 600; }

/* Controls Bar */
.controls {
    position: absolute; bottom: 2rem; left: 50%; transform: translateX(-50%);
    display: flex; gap: 0.75rem; align-items: center;
}
.ctrl-btn {
    width: 50px; height: 50px; border-radius: 50%;
    border: 1px solid rgba(255,255,255,0.15);
    cursor: pointer; font-size: 1.05rem;
    background: rgba(255,255,255,0.08); backdrop-filter: blur(8px);
    color: #fff; transition: var(--transition);
    display: flex; align-items: center; justify-content: center;
}
.ctrl-btn:hover { background: rgba(255,255,255,0.15); }
.ctrl-btn.muted { color: var(--danger); border-color: rgba(239,68,68,0.3); }

.live-toggle {
    width: 64px; height: 64px; border-radius: 50%;
    background: var(--danger); border: 3px solid rgba(255,255,255,0.2);
    cursor: pointer; font-size: 1.4rem; color: #fff;
    box-shadow: 0 4px 24px rgba(239, 68, 68, 0.35);
    transition: var(--transition);
    display: flex; align-items: center; justify-content: center;
}
.live-toggle:hover { box-shadow: 0 6px 32px rgba(239, 68, 68, 0.5); transform: scale(1.05); }
.live-toggle.streaming {
    background: rgba(255,255,255,0.1); border-color: rgba(255,255,255,0.2);
    box-shadow: none;
}
.live-toggle.streaming:hover { background: rgba(255,255,255,0.15); }

/* Placeholder */
.placeholder { position: absolute; text-align: center; color: var(--muted); }
.ph-icon {
    width: 80px; height: 80px; background: var(--surface); border-radius: 50%;
    display: flex; align-items: center; justify-content: center;
    font-size: 2rem; margin: 0 auto 1rem; border: 1px solid var(--border);
}

/* Sidebar */
.sidebar {
    background: var(--surface); border-left: 1px solid var(--border);
    display: flex; flex-direction: column; height: 100vh;
}
.sidebar-header {
    padding: 1rem 1.25rem; border-bottom: 1px solid var(--border);
    display: flex; justify-content: space-between; align-items: center;
}
.sidebar-header h2 { font-size: 0.95rem; font-weight: 600; }

/* Room Code */
.room-section { padding: 1.25rem; border-bottom: 1px solid var(--border); }
.room-display {
    background: var(--bg); border: 1px solid var(--border);
    padding: 1rem; border-radius: var(--radius-sm); text-align: center;
}
.room-label {
    font-size: 0.6rem; color: var(--muted); text-transform: uppercase;
    letter-spacing: 0.12em; font-weight: 600;
}
.room-code {
    font-family: 'JetBrains Mono', monospace; font-size: 1.8rem; font-weight: 700;
    color: var(--primary-light); letter-spacing: 0.15em; margin: 0.15rem 0;
}
.room-url { font-size: 0.6rem; color: var(--muted); word-break: break-all; margin-top: 0.25rem; }
.copy-btn {
    width: 100%; background: transparent; border: 1px solid var(--border);
    color: var(--text-secondary); padding: 0.55rem; border-radius: var(--radius-sm);
    cursor: pointer; font-family: inherit; font-size: 0.82rem; font-weight: 500;
    margin-top: 0.75rem; transition: var(--transition);
    display: flex; align-items: center; justify-content: center; gap: 0.4rem;
}
.copy-btn:hover { border-color: var(--border-light); color: var(--text); }

/* Settings */
.settings { padding: 1.25rem; border-bottom: 1px solid var(--border); }
.form-group { margin-bottom: 0.85rem; }
.form-group:last-child { margin-bottom: 0; }
.form-group label {
    display: block; font-size: 0.78rem; color: var(--text-secondary);
    margin-bottom: 0.3rem; font-weight: 500;
}
.form-group input, .form-group select {
    width: 100%; background: var(--bg); border: 1px solid var(--border);
    border-radius: var(--radius-sm); padding: 0.55rem 0.75rem;
    color: var(--text); font-family: inherit; font-size: 0.85rem;
    transition: var(--transition);
}
.form-group input:focus, .form-group select:focus {
    border-color: var(--primary); outline: none;
    box-shadow: 0 0 0 3px var(--primary-glow);
}

/* Rate Display */
.rate-section { padding: 1.25rem; border-bottom: 1px solid var(--border); }
.rate-card {
    background: var(--bg); border: 1px solid var(--border);
    border-radius: var(--radius-sm); padding: 1rem; text-align: center;
}
.rate-label { font-size: 0.65rem; color: var(--muted); text-transform: uppercase; letter-spacing: 0.08em; font-weight: 600; }
.rate-value {
    font-family: 'JetBrains Mono', monospace; font-size: 1.4rem; font-weight: 700;
    background: var(--gradient-primary); -webkit-background-clip: text;
    -webkit-text-fill-color: transparent; margin: 0.15rem 0;
}
.rate-sub { font-size: 0.72rem; color: var(--text-secondary); }

/* Chat */
.chat { flex: 1; display: flex; flex-direction: column; min-height: 0; }
.chat-head {
    padding: 0.7rem 1.25rem; border-bottom: 1px solid var(--border);
    font-size: 0.82rem; font-weight: 600; color: var(--text-secondary);
    display: flex; align-items: center; gap: 0.4rem;
}
.chat-msgs {
    flex: 1; overflow-y: auto; padding: 0.85rem 1.25rem;
    font-size: 0.82rem; display: flex; flex-direction: column; gap: 0.4rem;
}
.chat-msgs::-webkit-scrollbar { width: 3px; }
.chat-msgs::-webkit-scrollbar-thumb { background: var(--border); border-radius: 2px; }
.msg { line-height: 1.5; }
.msg .name { color: var(--primary-light); font-weight: 600; font-size: 0.78rem; }
.msg.system { color: var(--muted); font-size: 0.78rem; font-style: italic; }
.chat-input {
    padding: 0.65rem 0.85rem; border-top: 1px solid var(--border);
    display: flex; gap: 0.35rem;
}
.chat-input input {
    flex: 1; background: var(--bg); border: 1px solid var(--border);
    border-radius: var(--radius-sm); padding: 0.5rem 0.75rem;
    color: var(--text); font-family: inherit; font-size: 0.82rem;
}
.chat-input input:focus { outline: none; border-color: var(--primary); }
.chat-input input::placeholder { color: var(--muted); }
.chat-input button {
    background: var(--primary); border: none; color: #fff;
    width: 34px; border-radius: var(--radius-sm); cursor: pointer;
    font-size: 0.75rem; transition: var(--transition);
    display: flex; align-items: center; justify-content: center;
}
.chat-input button:hover { background: var(--primary-light); }

/* Toasts */
#toasts { position: fixed; bottom: 1rem; left: 50%; transform: translateX(-50%); z-index: 2000; }
.toast {
    background: var(--surface); border: 1px solid var(--border);
    padding: 0.6rem 1.25rem; border-radius: var(--radius-sm);
    font-size: 0.85rem; box-shadow: var(--shadow);
    animation: fadeUp 0.3s ease-out;
    display: flex; align-items: center; gap: 0.4rem;
}
@keyframes fadeUp { from { opacity: 0; transform: translateY(10px); } }

@media (max-width: 900px) {
    .studio { grid-template-columns: 1fr; grid-template-rows: 45vh 1fr; }
    .sidebar { border-left: none; border-top: 1px solid var(--border); height: auto; overflow-y: auto; }
    .controls { bottom: 1rem; }
}
</style>
</head>
<body>

<div class="studio">
    <div class="preview">
        <video id="video" autoplay muted playsinline></video>
        <div class="placeholder" id="ph">
            <div class="ph-icon"><i class="fa-solid fa-video"></i></div>
            <p>Initializing camera...</p>
        </div>

        <div class="preview-overlay">
            <div class="top-left">
                <a href="./" class="back-btn"><i class="fa-solid fa-arrow-left"></i></a>
                <div class="live-ind" id="liveInd">Offline</div>
            </div>
            <div class="top-right">
                <div class="pill viewers"><i class="fa-solid fa-eye"></i> <span id="vc">0</span></div>
                <div class="pill duration" id="duration">00:00:00</div>
                <div class="pill earnings" id="earningsPill">$0.00</div>
            </div>
        </div>

        <div class="controls">
            <button class="ctrl-btn" onclick="flipCam()" title="Flip Camera"><i class="fa-solid fa-arrows-rotate"></i></button>
            <button class="ctrl-btn" onclick="toggleScreen()" title="Share Screen"><i class="fa-solid fa-display"></i></button>
            <button class="live-toggle" id="liveBtn" onclick="toggleLive()" title="Go Live"><i class="fa-solid fa-circle"></i></button>
            <button class="ctrl-btn" id="micBtn" onclick="toggleMic()" title="Toggle Mic"><i class="fa-solid fa-microphone" id="micIcon"></i></button>
            <button class="ctrl-btn" id="camBtn" onclick="toggleCam()" title="Toggle Camera"><i class="fa-solid fa-video" id="camIcon"></i></button>
        </div>
    </div>

    <aside class="sidebar">
        <div class="sidebar-header">
            <h2>Stream Studio</h2>
        </div>

        <div class="room-section">
            <div class="room-display">
                <div class="room-label">Share Room Code</div>
                <div class="room-code" id="roomCode">------</div>
                <div class="room-url" id="roomUrl"></div>
            </div>
            <button class="copy-btn" onclick="copyLink()"><i class="fa-solid fa-copy"></i> Copy Share Link</button>
        </div>

        <div class="settings">
            <div class="form-group">
                <label>Stream</label>
                <select id="streamSelect">
                    <option value="">Quick stream (no saved stream)</option>
                    <?php foreach ($streams as $s): ?>
                    <option value="<?= $s['id'] ?>" <?= $s['id'] === $preselect ? 'selected' : '' ?>><?= htmlspecialchars($s['title']) ?> (<?= $s['type'] ?>)</option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="form-group">
                <label>Title</label>
                <input type="text" id="liveTitle" placeholder="What are you streaming?">
            </div>
            <div class="form-group">
                <label>Category</label>
                <select id="liveType">
                    <option value="public">Public</option>
                    <option value="lifestyle">Lifestyle</option>
                    <option value="nsfw">NSFW</option>
                </select>
            </div>
        </div>

        <div class="rate-section">
            <div class="rate-card">
                <div class="rate-label">smartGrid Rate</div>
                <div class="rate-value" id="rateDisplay">0.50&cent;/min</div>
                <div class="rate-sub" id="rateHourly">$0.30/hr estimated</div>
            </div>
        </div>

        <div class="chat">
            <div class="chat-head"><i class="fa-solid fa-comments"></i> Chat</div>
            <div class="chat-msgs" id="msgs">
                <div class="msg system">Waiting for viewers to connect...</div>
            </div>
            <div class="chat-input">
                <input type="text" id="chatIn" placeholder="Say something...">
                <button onclick="sendChat()"><i class="fa-solid fa-paper-plane"></i></button>
            </div>
        </div>
    </aside>
</div>

<div id="toasts"></div>

<script>
let mediaStream = null, peers = {}, isLive = false, room = '', pollInt = null;
let facing = 'user', micOn = true, camOn = true, screenStream = null;
let startTime = null, durationInt = null, earnings = 0;

// Init camera immediately
(async function init() {
    room = genRoom();
    document.getElementById('roomCode').textContent = room;
    document.getElementById('roomUrl').textContent = location.origin + '/omnigrid/live.php?room=' + room;

    // Pre-fill title from selected stream
    const sel = document.getElementById('streamSelect');
    if (sel.value) {
        const opt = sel.options[sel.selectedIndex];
        document.getElementById('liveTitle').value = opt.textContent.split(' (')[0];
    }

    try {
        mediaStream = await navigator.mediaDevices.getUserMedia({
            video: { facingMode: facing, width: { ideal: 1280 }, height: { ideal: 720 } },
            audio: true
        });
        document.getElementById('video').srcObject = mediaStream;
        document.getElementById('ph').style.display = 'none';
    } catch (e) {
        document.querySelector('#ph p').textContent = 'Camera access denied. Check permissions.';
    }
})();

function toggleLive() { isLive ? stopLive() : startLive(); }

async function startLive() {
    if (!mediaStream) return;
    isLive = true;
    const ind = document.getElementById('liveInd');
    ind.classList.add('active'); ind.textContent = 'LIVE';
    document.getElementById('liveBtn').classList.add('streaming');
    document.getElementById('liveBtn').innerHTML = '<i class="fa-solid fa-stop"></i>';

    await fetch('../signal.php?room=' + room + '&action=reset');
    startPoll();

    const sid = document.getElementById('streamSelect').value;
    if (sid) {
        fetch('stream_status.php', {
            method: 'POST', headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ id: parseInt(sid), is_live: 1 })
        }).catch(() => {});
    }

    startTime = Date.now();
    durationInt = setInterval(updateDuration, 1000);
    addChat('System', 'You are LIVE! Share your room code.', true);
    toast('You are LIVE!');
}

function stopLive() {
    isLive = false;
    const ind = document.getElementById('liveInd');
    ind.classList.remove('active'); ind.textContent = 'Offline';
    document.getElementById('liveBtn').classList.remove('streaming');
    document.getElementById('liveBtn').innerHTML = '<i class="fa-solid fa-circle"></i>';

    Object.values(peers).forEach(p => p.close());
    peers = {};
    if (pollInt) { clearInterval(pollInt); pollInt = null; }
    if (durationInt) { clearInterval(durationInt); durationInt = null; }

    const sid = document.getElementById('streamSelect').value;
    if (sid) {
        fetch('stream_status.php', {
            method: 'POST', headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ id: parseInt(sid), is_live: 0 })
        }).catch(() => {});
    }
    addChat('System', 'Stream ended.', true);
}

function startPoll() {
    if (pollInt) clearInterval(pollInt);
    pollInt = setInterval(async () => {
        if (!isLive) return;
        try {
            const r = await fetch('../signal.php?room=' + room + '&action=poll&from=host');
            const d = await r.json();
            if (d.offer) await handleOffer(d.offer.viewerId, d.offer);
            if (d.candidate && d.candidate.viewerId) {
                const p = peers[d.candidate.viewerId];
                if (p) await p.addIceCandidate(new RTCIceCandidate(d.candidate.candidate));
            }
        } catch (e) {}
    }, 1000);
}

async function handleOffer(vid, offer) {
    const p = new RTCPeerConnection({ iceServers: [{ urls: 'stun:stun.l.google.com:19302' }] });
    peers[vid] = p;
    const stream = screenStream || mediaStream;
    stream.getTracks().forEach(t => p.addTrack(t, stream));
    p.onicecandidate = async e => {
        if (e.candidate) await fetch('../signal.php?room=' + room + '&action=candidate', {
            method: 'POST', headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ from: 'host', viewerId: vid, candidate: e.candidate })
        });
    };
    p.onconnectionstatechange = () => {
        if (p.connectionState === 'connected') { updateVC(); addChat('System', 'A viewer connected', true); }
        if (p.connectionState === 'disconnected' || p.connectionState === 'failed') { delete peers[vid]; updateVC(); }
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

function updateDuration() {
    if (!startTime) return;
    const elapsed = Math.floor((Date.now() - startTime) / 1000);
    const h = String(Math.floor(elapsed / 3600)).padStart(2, '0');
    const m = String(Math.floor((elapsed % 3600) / 60)).padStart(2, '0');
    const s = String(elapsed % 60).padStart(2, '0');
    document.getElementById('duration').textContent = h + ':' + m + ':' + s;
    earnings = 0.5 * elapsed / 60;
    document.getElementById('earningsPill').textContent = '$' + (earnings / 100).toFixed(2);
}

async function flipCam() {
    facing = facing === 'user' ? 'environment' : 'user';
    if (mediaStream) mediaStream.getTracks().forEach(t => t.stop());
    mediaStream = await navigator.mediaDevices.getUserMedia({
        video: { facingMode: facing, width: { ideal: 1280 }, height: { ideal: 720 } }, audio: micOn
    });
    document.getElementById('video').srcObject = mediaStream;
    replaceTrackInPeers('video', mediaStream.getVideoTracks()[0]);
}

function toggleMic() {
    micOn = !micOn;
    if (mediaStream) mediaStream.getAudioTracks().forEach(t => t.enabled = micOn);
    document.getElementById('micIcon').className = 'fa-solid fa-microphone' + (micOn ? '' : '-slash');
    document.getElementById('micBtn').classList.toggle('muted', !micOn);
}

function toggleCam() {
    camOn = !camOn;
    if (mediaStream) mediaStream.getVideoTracks().forEach(t => t.enabled = camOn);
    document.getElementById('camIcon').className = 'fa-solid fa-video' + (camOn ? '' : '-slash');
    document.getElementById('camBtn').classList.toggle('muted', !camOn);
}

async function toggleScreen() {
    if (screenStream) {
        screenStream.getTracks().forEach(t => t.stop()); screenStream = null;
        document.getElementById('video').srcObject = mediaStream;
        replaceTrackInPeers('video', mediaStream.getVideoTracks()[0]);
        toast('Switched to camera');
        return;
    }
    try {
        screenStream = await navigator.mediaDevices.getDisplayMedia({ video: true });
        document.getElementById('video').srcObject = screenStream;
        replaceTrackInPeers('video', screenStream.getVideoTracks()[0]);
        screenStream.getVideoTracks()[0].onended = () => {
            screenStream = null;
            document.getElementById('video').srcObject = mediaStream;
            replaceTrackInPeers('video', mediaStream.getVideoTracks()[0]);
        };
        toast('Screen sharing active');
    } catch (e) { toast('Screen share cancelled'); }
}

function replaceTrackInPeers(kind, newTrack) {
    Object.values(peers).forEach(p => {
        const sender = p.getSenders().find(s => s.track?.kind === kind);
        if (sender) sender.replaceTrack(newTrack);
    });
}

function copyLink() {
    navigator.clipboard.writeText(location.origin + '/omnigrid/live.php?room=' + room);
    toast('Share link copied!');
}

function addChat(name, text, isSystem) {
    const m = document.getElementById('msgs');
    const el = document.createElement('div');
    el.className = 'msg' + (isSystem ? ' system' : '');
    if (isSystem) { el.textContent = text; }
    else { el.innerHTML = '<span class="name">' + esc(name) + '</span> ' + esc(text); }
    m.appendChild(el);
    m.scrollTop = m.scrollHeight;
}

function sendChat() {
    const i = document.getElementById('chatIn'), t = i.value.trim();
    if (t) { addChat('You', t, false); i.value = ''; }
}

document.getElementById('chatIn').onkeypress = e => { if (e.key === 'Enter') sendChat(); };

document.getElementById('streamSelect').onchange = function() {
    if (this.value) document.getElementById('liveTitle').value = this.options[this.selectedIndex].textContent.split(' (')[0];
};

// Warn before leaving while live
window.onbeforeunload = e => { if (isLive) { e.preventDefault(); return 'You are still live!'; } };

function genRoom() { return Math.random().toString(36).substring(2, 8).toUpperCase(); }
function esc(s) { const d = document.createElement('div'); d.textContent = s || ''; return d.innerHTML; }
function toast(m) {
    const e = document.createElement('div'); e.className = 'toast'; e.textContent = m;
    document.getElementById('toasts').appendChild(e);
    setTimeout(() => e.remove(), 3000);
}
</script>
</body>
</html>
