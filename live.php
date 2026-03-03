<?php
session_start();
require_once 'includes/db.php';
$stream_id = (int)($_GET['id'] ?? 0);
$room = preg_replace('/[^A-Za-z0-9]/', '', $_GET['room'] ?? '');
$stream = null;
if ($stream_id) {
    $stmt = $pdo->prepare("SELECT s.*, u.display_name, m.views, m.subs_count FROM streams s JOIN users u ON s.user_id = u.id LEFT JOIN stream_metrics m ON s.id = m.stream_id WHERE s.id = ? AND s.is_active = 1");
    $stmt->execute([$stream_id]);
    $stream = $stmt->fetch();
    if ($stream) $pdo->prepare("UPDATE stream_metrics SET views = views + 1 WHERE stream_id = ?")->execute([$stream_id]);
}
$title = $stream['title'] ?? 'Live Stream';
$creator = $stream['display_name'] ?? 'Anonymous';
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8"><meta name="viewport" content="width=device-width,initial-scale=1.0">
<title><?= htmlspecialchars($title) ?> | OmniGrid</title>
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
<style>
:root{--bg:#0a0a0f;--surface:#12121a;--border:#2a2a3e;--text:#e0e0e0;--muted:#888;--primary:#6366f1;--danger:#ef4444}
*{box-sizing:border-box;margin:0;padding:0}body{font-family:-apple-system,sans-serif;background:var(--bg);color:var(--text);min-height:100vh}
.header{background:var(--surface);padding:0.6rem 1.5rem;display:flex;justify-content:space-between;align-items:center;border-bottom:1px solid var(--border)}
.logo{font-size:1.2rem;font-weight:700;color:#fff;text-decoration:none}.logo span{color:var(--primary)}
.btn{background:var(--primary);color:#fff;border:none;padding:0.45rem 0.9rem;border-radius:6px;font-size:0.8rem;cursor:pointer;text-decoration:none;display:inline-flex;align-items:center;gap:0.3rem}
.btn-outline{background:transparent;border:1px solid var(--border);color:var(--text)}
.main{display:grid;grid-template-columns:1fr 320px;height:calc(100vh - 49px)}
.video-wrap{background:#000;position:relative;display:flex;align-items:center;justify-content:center}
#video{width:100%;height:100%;object-fit:contain;background:#000}
.placeholder{position:absolute;text-align:center;color:#666}.placeholder i{font-size:3rem;margin-bottom:0.5rem;display:block}
.live-badge{position:absolute;top:1rem;left:1rem;background:#666;padding:0.25rem 0.6rem;border-radius:4px;font-size:0.7rem;font-weight:600;display:flex;align-items:center;gap:0.3rem}
.live-badge.live{background:var(--danger)}.live-badge::before{content:'';width:6px;height:6px;background:#fff;border-radius:50%}.live-badge.live::before{animation:p 1s infinite}
@keyframes p{0%,100%{opacity:1}50%{opacity:0.4}}
.viewers{position:absolute;top:1rem;right:1rem;background:rgba(0,0,0,0.7);padding:0.25rem 0.5rem;border-radius:4px;font-size:0.75rem}
.sidebar{background:var(--surface);border-left:1px solid var(--border);display:flex;flex-direction:column}
.info{padding:1rem;border-bottom:1px solid var(--border)}
.info h1{font-size:1rem;margin-bottom:0.25rem}
.room-box{background:var(--bg);padding:0.75rem;border-radius:8px;text-align:center;margin-top:0.75rem}
.room-box label{font-size:0.7rem;color:var(--muted)}
.room-box input{background:transparent;border:none;color:var(--primary);font-size:1.4rem;font-weight:700;letter-spacing:0.1em;text-align:center;width:100%;text-transform:uppercase}
.connect-btn{width:100%;margin-top:0.75rem;justify-content:center;padding:0.6rem}
.chat{flex:1;display:flex;flex-direction:column;min-height:0}
.chat-head{padding:0.6rem 1rem;border-bottom:1px solid var(--border);font-size:0.85rem}
.chat-msgs{flex:1;overflow-y:auto;padding:0.75rem 1rem;font-size:0.85rem}
.chat-msgs .msg{margin-bottom:0.5rem}.chat-msgs .name{color:var(--primary);font-weight:500}
.chat-input{padding:0.75rem 1rem;border-top:1px solid var(--border);display:flex;gap:0.4rem}
.chat-input input{flex:1;background:var(--bg);border:1px solid var(--border);border-radius:6px;padding:0.5rem 0.75rem;color:var(--text);font-size:0.85rem}
.chat-input button{background:var(--primary);border:none;color:#fff;padding:0 0.75rem;border-radius:6px;cursor:pointer}
@media(max-width:900px){.main{grid-template-columns:1fr;grid-template-rows:50vh 1fr}.sidebar{border-left:none}}
</style>
</head>
<body>
<header class="header"><a href="./" class="logo">Omni<span>Grid</span></a><a href="./" class="btn btn-outline"><i class="fa fa-th"></i> Grid</a></header>
<main class="main">
<div class="video-wrap">
<div class="live-badge" id="badge">OFFLINE</div>
<div class="viewers"><i class="fa fa-eye"></i> <span id="vc"><?= number_format($stream['views'] ?? 0) ?></span></div>
<video id="video" autoplay playsinline></video>
<div class="placeholder" id="ph"><i class="fa fa-satellite-dish"></i><p id="status">Enter room code to connect</p></div>
</div>
<aside class="sidebar">
<div class="info">
<h1><?= htmlspecialchars($title) ?></h1>
<div class="room-box"><label>Room Code</label><input type="text" id="roomIn" value="<?= htmlspecialchars($room) ?>" placeholder="XXXXXX" maxlength="10"></div>
<button class="btn connect-btn" id="connBtn" onclick="connect()"><i class="fa fa-plug"></i> Connect</button>
</div>
<div class="chat">
<div class="chat-head"><i class="fa fa-comments"></i> Chat</div>
<div class="chat-msgs" id="msgs"><div class="msg"><span class="name">System:</span> Enter room code to watch</div></div>
<div class="chat-input"><input type="text" id="chatIn" placeholder="Say something..."><button onclick="sendChat()"><i class="fa fa-paper-plane"></i></button></div>
</div>
</aside>
</main>
<script>
const video=document.getElementById('video'),ph=document.getElementById('ph'),badge=document.getElementById('badge');
let pc=null,poll=null,room='',viewerId=null;

async function connect(){
    room=document.getElementById('roomIn').value.trim().toUpperCase();
    if(!room){document.getElementById('status').textContent='Enter a room code';return;}
    document.getElementById('connBtn').disabled=true;
    document.getElementById('connBtn').innerHTML='<i class="fa fa-spinner fa-spin"></i> Connecting...';
    document.getElementById('status').textContent='Connecting to '+room+'...';
    addChat('System','Connecting...');
    viewerId='v'+Date.now();
    startPoll();
}

function startPoll(){
    if(poll)clearInterval(poll);
    poll=setInterval(async()=>{
        try{
            const r=await fetch('signal.php?room='+room+'&action=poll&from=viewer&viewerId='+viewerId);
            const d=await r.json();
            if(d.answer&&pc&&pc.signalingState!=='stable'){await pc.setRemoteDescription(new RTCSessionDescription(d.answer));}
            if(d.candidate&&pc){await pc.addIceCandidate(new RTCIceCandidate(d.candidate));}
        }catch(e){}
    },1000);
    createOffer();
}

async function createOffer(){
    pc=new RTCPeerConnection({iceServers:[{urls:'stun:stun.l.google.com:19302'}]});
    pc.addTransceiver('video',{direction:'recvonly'});
    pc.addTransceiver('audio',{direction:'recvonly'});
    pc.ontrack=e=>{video.srcObject=e.streams[0];ph.style.display='none';setLive(true);addChat('System','Connected!');};
    pc.onicecandidate=async e=>{if(e.candidate)await fetch('signal.php?room='+room+'&action=candidate',{method:'POST',headers:{'Content-Type':'application/json'},body:JSON.stringify({from:'viewer',viewerId,candidate:e.candidate})});};
    pc.onconnectionstatechange=()=>{if(pc.connectionState==='disconnected'||pc.connectionState==='failed'){setLive(false);document.getElementById('status').textContent='Disconnected';ph.style.display='block';addChat('System','Disconnected');pc=null;setTimeout(createOffer,3000);}};
    const offer=await pc.createOffer();
    await pc.setLocalDescription(offer);
    await fetch('signal.php?room='+room+'&action=offer',{method:'POST',headers:{'Content-Type':'application/json'},body:JSON.stringify({...offer,viewerId})});
}

function setLive(live){badge.classList.toggle('live',live);badge.textContent=live?'LIVE':'OFFLINE';document.getElementById('connBtn').disabled=false;document.getElementById('connBtn').innerHTML='<i class="fa fa-plug"></i> '+(live?'Connected':'Connect');}
function addChat(n,t){const m=document.getElementById('msgs');m.innerHTML+='<div class="msg"><span class="name">'+n+':</span> '+t+'</div>';m.scrollTop=m.scrollHeight;}
function sendChat(){const i=document.getElementById('chatIn'),t=i.value.trim();if(t){addChat('You',t.replace(/</g,'&lt;'));i.value='';}}
document.getElementById('chatIn').onkeypress=e=>{if(e.key==='Enter')sendChat();};
<?php if($room):?>setTimeout(connect,500);<?php endif;?>
</script>
</body>
</html>
