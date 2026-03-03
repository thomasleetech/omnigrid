<?php
session_start();
if (empty($_SESSION['user_id'])) { header('Location: ../login.php'); exit; }
require_once '../includes/db.php';
$stmt = $pdo->prepare("SELECT * FROM users WHERE id = ?");
$stmt->execute([$_SESSION['user_id']]);
$user = $stmt->fetch();
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8"><meta name="viewport" content="width=device-width,initial-scale=1.0">
<title>Creator Studio | OmniGrid</title>
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
<style>
:root{--bg:#0a0a0f;--surface:#12121a;--border:#2a2a3e;--text:#e0e0e0;--muted:#888;--primary:#6366f1;--success:#10b981;--warning:#f59e0b;--danger:#ef4444}
*{box-sizing:border-box;margin:0;padding:0}body{font-family:-apple-system,sans-serif;background:var(--bg);color:var(--text);min-height:100vh}
.layout{display:grid;grid-template-columns:220px 1fr;min-height:100vh}
.sidebar{background:var(--surface);border-right:1px solid var(--border);padding:1.25rem;display:flex;flex-direction:column}
.logo{font-size:1.3rem;font-weight:700;margin-bottom:1.5rem}.logo span{color:var(--primary)}
.nav-item{display:flex;align-items:center;gap:0.6rem;padding:0.6rem 0.9rem;border-radius:6px;color:var(--muted);text-decoration:none;margin-bottom:0.2rem;font-size:0.85rem}.nav-item:hover,.nav-item.active{background:rgba(99,102,241,0.1);color:var(--text)}.nav-item.active{color:var(--primary)}.nav-item i{width:18px}
.nav-div{height:1px;background:var(--border);margin:1rem 0}
.user-card{margin-top:auto;display:flex;align-items:center;gap:0.6rem;padding:0.75rem;background:var(--bg);border-radius:8px}
.user-avatar{width:32px;height:32px;background:var(--primary);border-radius:50%;display:flex;align-items:center;justify-content:center}
.main{padding:1.5rem 2rem;overflow-y:auto}
.page-header{display:flex;justify-content:space-between;align-items:center;margin-bottom:1.5rem}.page-header h1{font-size:1.4rem}
.btn{background:var(--primary);color:#fff;border:none;padding:0.5rem 1rem;border-radius:8px;cursor:pointer;font-size:0.85rem;display:inline-flex;align-items:center;gap:0.4rem;text-decoration:none}.btn:hover{filter:brightness(1.1)}.btn-outline{background:transparent;border:1px solid var(--border);color:var(--text)}.btn-sm{padding:0.35rem 0.7rem;font-size:0.8rem}.btn-success{background:var(--success)}.btn-danger{background:var(--danger)}
.stats-grid{display:grid;grid-template-columns:repeat(4,1fr);gap:1rem;margin-bottom:1.5rem}
.stat-card{background:var(--surface);border:1px solid var(--border);border-radius:10px;padding:1rem}.stat-card .label{font-size:0.75rem;color:var(--muted)}.stat-card .value{font-size:1.4rem;font-weight:600;margin-top:0.2rem}
.section-header{display:flex;justify-content:space-between;align-items:center;margin-bottom:1rem}.section-header h2{font-size:1rem}
.stream-grid{display:grid;grid-template-columns:repeat(auto-fill,minmax(280px,1fr));gap:1rem}
.stream-card{background:var(--surface);border:1px solid var(--border);border-radius:10px;overflow:hidden}.stream-card.live{border-color:var(--danger)}
.stream-thumb{height:130px;background:#1a1a2e;position:relative}.stream-thumb img{width:100%;height:100%;object-fit:cover}.stream-thumb .no-thumb{display:flex;align-items:center;justify-content:center;height:100%;color:var(--border);font-size:2rem}
.stream-badge{position:absolute;top:0.5rem;left:0.5rem;padding:0.15rem 0.5rem;border-radius:4px;font-size:0.65rem;font-weight:600;text-transform:uppercase}.badge-live{background:var(--danger);animation:pulse 1.5s infinite}.badge-public{background:var(--success)}.badge-lifestyle{background:var(--warning);color:#000}.badge-nsfw{background:#9333ea}
@keyframes pulse{0%,100%{opacity:1}50%{opacity:0.6}}
.stream-viewers{position:absolute;top:0.5rem;right:0.5rem;background:rgba(0,0,0,0.7);padding:0.15rem 0.5rem;border-radius:4px;font-size:0.7rem}
.stream-body{padding:0.9rem}.stream-title{font-weight:600;font-size:0.9rem;margin-bottom:0.2rem}.stream-tag{color:var(--primary);font-size:0.8rem}
.stream-stats{display:flex;gap:1rem;margin-top:0.5rem;font-size:0.75rem;color:var(--muted)}
.stream-earnings{display:flex;justify-content:space-between;align-items:center;margin-top:0.5rem;padding-top:0.5rem;border-top:1px solid var(--border)}
.earnings-value{font-size:1rem;font-weight:600;color:var(--success)}.smartgrid-rate{font-size:0.7rem;color:var(--warning)}
.stream-actions{display:flex;gap:0.3rem;margin-top:0.6rem}.stream-actions .btn{flex:1;justify-content:center}
.modal{display:none;position:fixed;inset:0;background:rgba(0,0,0,0.9);z-index:1000;align-items:center;justify-content:center}.modal.open{display:flex}
.studio{width:100%;max-width:1000px;height:80vh;display:grid;grid-template-columns:1fr 300px;background:var(--surface);border-radius:12px;overflow:hidden}
.studio-preview{background:#000;position:relative}.studio-preview video{width:100%;height:100%;object-fit:contain}
.studio-overlay{position:absolute;top:1rem;left:1rem;right:1rem;display:flex;justify-content:space-between}
.live-ind{background:var(--danger);padding:0.3rem 0.8rem;border-radius:20px;font-size:0.8rem;font-weight:600;display:none;align-items:center;gap:0.3rem}.live-ind.active{display:flex}.live-ind::before{content:'';width:6px;height:6px;background:#fff;border-radius:50%;animation:pulse 1s infinite}
.viewer-ct{background:rgba(0,0,0,0.7);padding:0.3rem 0.8rem;border-radius:20px;font-size:0.8rem}
.studio-ctrl{position:absolute;bottom:1.5rem;left:50%;transform:translateX(-50%);display:flex;gap:0.6rem}
.ctrl-btn{width:44px;height:44px;border-radius:50%;border:none;cursor:pointer;font-size:1rem;background:#333;color:#fff}.ctrl-btn.live-btn{background:var(--danger);width:56px;height:56px;font-size:1.2rem}.ctrl-btn.live-btn.streaming{background:#666}
.studio-panel{background:var(--surface);border-left:1px solid var(--border);display:flex;flex-direction:column}
.panel-header{padding:0.9rem 1rem;border-bottom:1px solid var(--border);display:flex;justify-content:space-between;align-items:center}.panel-header h2{font-size:0.95rem}.panel-close{background:none;border:none;color:var(--muted);font-size:1.3rem;cursor:pointer}
.panel-body{flex:1;overflow-y:auto;padding:1rem}
.form-group{margin-bottom:0.9rem}.form-group label{display:block;font-size:0.75rem;color:var(--muted);margin-bottom:0.25rem}.form-group input,.form-group select{width:100%;background:var(--bg);border:1px solid var(--border);border-radius:6px;padding:0.5rem;color:var(--text);font-size:0.85rem}.form-group input:focus{border-color:var(--primary);outline:none}
.room-display{background:var(--bg);padding:0.9rem;border-radius:8px;text-align:center;margin-bottom:1rem}.room-display label{font-size:0.65rem;color:var(--muted)}.room-display .code{font-size:1.5rem;font-weight:700;color:var(--primary);letter-spacing:0.1em;font-family:monospace}.room-display .url{font-size:0.6rem;color:var(--muted);word-break:break-all;margin-top:0.3rem}
.panel-footer{padding:0.9rem 1rem;border-top:1px solid var(--border)}
.empty-state{text-align:center;padding:3rem 2rem;color:var(--muted)}.empty-state i{font-size:3rem;margin-bottom:0.75rem;opacity:0.3}
#toasts{position:fixed;bottom:1rem;right:1rem;z-index:2000}.toast{background:var(--surface);border:1px solid var(--border);padding:0.5rem 0.9rem;border-radius:6px;margin-top:0.4rem;font-size:0.8rem;animation:slideIn 0.2s}.toast-success{border-color:var(--success)}@keyframes slideIn{from{transform:translateX(100%)}}
@media(max-width:900px){.layout{grid-template-columns:1fr}.sidebar{display:none}.stats-grid{grid-template-columns:repeat(2,1fr)}}
</style>
</head>
<body>
<div class="layout">
<aside class="sidebar">
<div class="logo">Omni<span>Grid</span></div>
<a class="nav-item active"><i class="fa fa-th-large"></i> Dashboard</a>
<a class="nav-item" href="earnings.php"><i class="fa fa-dollar-sign"></i> Earnings</a>
<div class="nav-div"></div>
<a class="nav-item" href="../"><i class="fa fa-globe"></i> View Site</a>
<a class="nav-item" href="../logout.php"><i class="fa fa-sign-out-alt"></i> Logout</a>
<div class="user-card"><div class="user-avatar"><i class="fa fa-user"></i></div><div style="font-size:0.8rem"><div style="font-weight:500"><?= htmlspecialchars($user['display_name'] ?: explode('@', $user['email'])[0]) ?></div><div style="color:var(--muted);font-size:0.7rem">Creator</div></div></div>
</aside>
<main class="main">
<div class="page-header"><h1>Creator Studio</h1><button class="btn" onclick="openStudio()"><i class="fa fa-broadcast-tower"></i> Go Live</button></div>
<div class="stats-grid">
<div class="stat-card"><div class="label">Today's Earnings</div><div class="value" id="statE">$0.00</div></div>
<div class="stat-card"><div class="label">Total Views</div><div class="value" id="statV">0</div></div>
<div class="stat-card"><div class="label">Subscribers</div><div class="value" id="statS">0</div></div>
<div class="stat-card"><div class="label">smartGrid Rate</div><div class="value" id="statR">1.0x</div></div>
</div>
<div class="section-header"><h2>Your Streams</h2><button class="btn btn-outline btn-sm" onclick="openAdd()"><i class="fa fa-plus"></i> Add</button></div>
<div class="stream-grid" id="grid"><div class="empty-state"><i class="fa fa-video-slash"></i><p>Loading...</p></div></div>
</main>
</div>
<div class="modal" id="studioModal">
<div class="studio">
<div class="studio-preview"><video id="studioVideo" autoplay muted playsinline></video>
<div class="studio-overlay"><div class="live-ind" id="liveInd">LIVE</div><div class="viewer-ct"><i class="fa fa-eye"></i> <span id="vc">0</span></div></div>
<div class="studio-ctrl"><button class="ctrl-btn" onclick="flipCam()"><i class="fa fa-sync"></i></button><button class="ctrl-btn live-btn" id="liveBtn" onclick="toggleLive()"><i class="fa fa-circle"></i></button><button class="ctrl-btn" onclick="toggleMic()"><i class="fa fa-microphone" id="micIcon"></i></button></div>
</div>
<div class="studio-panel">
<div class="panel-header"><h2>Stream Settings</h2><button class="panel-close" onclick="closeStudio()">&times;</button></div>
<div class="panel-body">
<div class="room-display"><label>Room Code</label><div class="code" id="roomCode">------</div><div class="url" id="roomUrl"></div></div>
<div class="form-group"><label>Title</label><input type="text" id="liveTitle" placeholder="What are you streaming?"></div>
<div class="form-group"><label>Category</label><select id="liveType"><option value="public">Public</option><option value="lifestyle">Lifestyle</option><option value="nsfw">NSFW</option></select></div>
</div>
<div class="panel-footer"><button class="btn btn-outline" style="width:100%" onclick="copyLink()"><i class="fa fa-copy"></i> Copy Link</button></div>
</div>
</div>
</div>
<div class="modal" id="addModal">
<div style="background:var(--surface);width:380px;max-height:90vh;border-radius:12px;overflow:hidden">
<div class="panel-header"><h2 id="addTitle">Add Stream</h2><button class="panel-close" onclick="closeAdd()">&times;</button></div>
<div class="panel-body">
<input type="hidden" id="editId">
<div class="form-group"><label>Title *</label><input type="text" id="fTitle"></div>
<div class="form-group"><label>Type</label><select id="fType"><option value="public">Public</option><option value="lifestyle">Lifestyle</option><option value="nsfw">NSFW</option></select></div>
<div class="form-group"><label>Vibe Tag</label><input type="text" id="fVibe" placeholder="chill · lofi"></div>
<div class="form-group"><label>Revenue</label><select id="fRev"><option value="smartgrid">smartGrid</option><option value="override">Fixed</option></select></div>
<div class="form-group"><label>Multiplier</label><input type="number" id="fMult" value="1.0" min="0.5" max="5" step="0.1"></div>
</div>
<div class="panel-footer"><button class="btn" style="width:100%" onclick="saveStream()"><i class="fa fa-save"></i> Save</button></div>
</div>
</div>
<div id="toasts"></div>
<script>
let streams=[],mediaStream=null,peers={},isLive=false,room='',pollInt=null,facing='user',micOn=true;
document.addEventListener('DOMContentLoaded',loadStreams);
async function loadStreams(){const r=await fetch('get_streams.php'),d=await r.json();if(d.success){streams=d.streams;render();stats();}}
function render(){const g=document.getElementById('grid');if(!streams.length){g.innerHTML='<div class="empty-state"><i class="fa fa-video-slash"></i><p>No streams yet</p><button class="btn" onclick="openAdd()"><i class="fa fa-plus"></i> Create Stream</button></div>';return;}g.innerHTML=streams.map(s=>`<div class="stream-card ${s.is_live?'live':''}"><div class="stream-thumb">${s.thumb_url?`<img src="../${s.thumb_url}">`:'<div class="no-thumb"><i class="fa fa-video"></i></div>'}${s.is_live?'<span class="stream-badge badge-live">LIVE</span>':`<span class="stream-badge badge-${s.type}">${s.type}</span>`}<span class="stream-viewers"><i class="fa fa-eye"></i> ${fmtN(s.views)}</span></div><div class="stream-body"><div class="stream-title">${esc(s.title)}</div><div class="stream-tag">${esc(s.vibe_tag||'')}</div><div class="stream-stats"><span><i class="fa fa-users"></i> ${s.subs_count}</span><span><i class="fa fa-heart"></i> ${fmtM(s.tips_cents)}</span></div><div class="stream-earnings"><div><div class="earnings-value">${fmtM(s.total_earnings_cents||0)}</div><div class="smartgrid-rate">${s.revenue_mode==='smartgrid'?s.smartgrid_multiplier+'x':'$'+s.price_per_minute}</div></div></div><div class="stream-actions"><button class="btn btn-sm ${s.is_live?'btn-danger':'btn-success'}" onclick="goLive(${s.id})"><i class="fa fa-${s.is_live?'stop':'broadcast-tower'}"></i></button><button class="btn btn-outline btn-sm" onclick="editStream(${s.id})"><i class="fa fa-edit"></i></button><button class="btn btn-outline btn-sm" onclick="delStream(${s.id})"><i class="fa fa-trash"></i></button></div></div></div>`).join('');}
function stats(){let e=0,v=0,sub=0,m=0,mc=0;streams.forEach(s=>{e+=s.total_earnings_cents||0;v+=s.views||0;sub+=s.subs_count||0;if(s.revenue_mode==='smartgrid'){m+=parseFloat(s.smartgrid_multiplier);mc++;}});document.getElementById('statE').textContent=fmtM(e);document.getElementById('statV').textContent=fmtN(v);document.getElementById('statS').textContent=sub;document.getElementById('statR').textContent=mc?(m/mc).toFixed(1)+'x':'1.0x';}
async function openStudio(){document.getElementById('studioModal').classList.add('open');room=genRoom();document.getElementById('roomCode').textContent=room;document.getElementById('roomUrl').textContent=location.origin+'/omnigrid/live.php?room='+room;try{mediaStream=await navigator.mediaDevices.getUserMedia({video:{facingMode:facing,width:{ideal:1280},height:{ideal:720}},audio:true});document.getElementById('studioVideo').srcObject=mediaStream;}catch(e){toast('Camera denied','error');}}
function closeStudio(){if(isLive&&!confirm('End stream?'))return;stopLive();if(mediaStream){mediaStream.getTracks().forEach(t=>t.stop());mediaStream=null;}document.getElementById('studioModal').classList.remove('open');}
function goLive(id){const s=streams.find(x=>x.id===id);if(s){document.getElementById('liveTitle').value=s.title;document.getElementById('liveType').value=s.type;}openStudio();}
async function toggleLive(){isLive?stopLive():startLive();}
async function startLive(){if(!mediaStream)return;isLive=true;document.getElementById('liveInd').classList.add('active');document.getElementById('liveBtn').classList.add('streaming');document.getElementById('liveBtn').innerHTML='<i class="fa fa-stop"></i>';await fetch('../signal.php?room='+room+'&action=reset');startPoll();toast('LIVE!','success');}
function stopLive(){isLive=false;document.getElementById('liveInd').classList.remove('active');document.getElementById('liveBtn').classList.remove('streaming');document.getElementById('liveBtn').innerHTML='<i class="fa fa-circle"></i>';Object.values(peers).forEach(p=>p.close());peers={};if(pollInt){clearInterval(pollInt);pollInt=null;}}
function startPoll(){if(pollInt)clearInterval(pollInt);pollInt=setInterval(async()=>{if(!isLive)return;try{const r=await fetch('../signal.php?room='+room+'&action=poll&from=host'),d=await r.json();if(d.offer)await handleOffer(d.offer.viewerId,d.offer);if(d.candidate&&d.candidate.viewerId){const p=peers[d.candidate.viewerId];if(p)await p.addIceCandidate(new RTCIceCandidate(d.candidate.candidate));}}catch(e){}},1000);}
async function handleOffer(vid,offer){const p=new RTCPeerConnection({iceServers:[{urls:'stun:stun.l.google.com:19302'}]});peers[vid]=p;mediaStream.getTracks().forEach(t=>p.addTrack(t,mediaStream));p.onicecandidate=async e=>{if(e.candidate)await fetch('../signal.php?room='+room+'&action=candidate',{method:'POST',headers:{'Content-Type':'application/json'},body:JSON.stringify({from:'host',viewerId:vid,candidate:e.candidate})});};p.onconnectionstatechange=()=>{if(p.connectionState==='connected'||p.connectionState==='disconnected')updateVC();if(p.connectionState==='failed')delete peers[vid];};await p.setRemoteDescription(new RTCSessionDescription(offer));const ans=await p.createAnswer();await p.setLocalDescription(ans);await fetch('../signal.php?room='+room+'&action=answer',{method:'POST',headers:{'Content-Type':'application/json'},body:JSON.stringify({...ans,viewerId:vid})});}
function updateVC(){document.getElementById('vc').textContent=Object.values(peers).filter(p=>p.connectionState==='connected').length;}
async function flipCam(){facing=facing==='user'?'environment':'user';if(mediaStream)mediaStream.getTracks().forEach(t=>t.stop());mediaStream=await navigator.mediaDevices.getUserMedia({video:{facingMode:facing,width:{ideal:1280},height:{ideal:720}},audio:micOn});document.getElementById('studioVideo').srcObject=mediaStream;const vt=mediaStream.getVideoTracks()[0];Object.values(peers).forEach(p=>{const s=p.getSenders().find(x=>x.track?.kind==='video');if(s)s.replaceTrack(vt);});}
function toggleMic(){micOn=!micOn;if(mediaStream)mediaStream.getAudioTracks().forEach(t=>t.enabled=micOn);document.getElementById('micIcon').className='fa fa-microphone'+(micOn?'':'-slash');}
function copyLink(){navigator.clipboard.writeText(location.origin+'/omnigrid/live.php?room='+room);toast('Copied!','success');}
function openAdd(){document.getElementById('addModal').classList.add('open');document.getElementById('addTitle').textContent='Add Stream';document.getElementById('editId').value='';document.getElementById('fTitle').value='';document.getElementById('fType').value='public';document.getElementById('fVibe').value='';document.getElementById('fRev').value='smartgrid';document.getElementById('fMult').value='1.0';}
function closeAdd(){document.getElementById('addModal').classList.remove('open');}
function editStream(id){const s=streams.find(x=>x.id===id);if(!s)return;document.getElementById('addModal').classList.add('open');document.getElementById('addTitle').textContent='Edit';document.getElementById('editId').value=id;document.getElementById('fTitle').value=s.title;document.getElementById('fType').value=s.type;document.getElementById('fVibe').value=s.vibe_tag||'';document.getElementById('fRev').value=s.revenue_mode;document.getElementById('fMult').value=s.smartgrid_multiplier;}
async function saveStream(){const id=document.getElementById('editId').value;const data={title:document.getElementById('fTitle').value,type:document.getElementById('fType').value,vibe_tag:document.getElementById('fVibe').value,revenue_mode:document.getElementById('fRev').value,smartgrid_multiplier:parseFloat(document.getElementById('fMult').value)};if(!data.title){toast('Title required','error');return;}if(id)data.id=id;const r=await fetch(id?'update_stream.php':'save_stream.php',{method:'POST',headers:{'Content-Type':'application/json'},body:JSON.stringify(data)}),d=await r.json();if(d.success){toast(id?'Updated':'Created','success');closeAdd();loadStreams();}else toast(d.error||'Failed','error');}
async function delStream(id){if(!confirm('Delete?'))return;await fetch('delete_stream.php',{method:'POST',headers:{'Content-Type':'application/json'},body:JSON.stringify({id})});toast('Deleted','success');loadStreams();}
function genRoom(){return Math.random().toString(36).substring(2,8).toUpperCase();}
function esc(s){const d=document.createElement('div');d.textContent=s||'';return d.innerHTML;}
function fmtN(n){return n>=1e6?(n/1e6).toFixed(1)+'M':n>=1e3?(n/1e3).toFixed(1)+'K':n||0;}
function fmtM(c){return '$'+((c||0)/100).toFixed(2);}
function toast(m,t='info'){const e=document.createElement('div');e.className='toast'+(t==='success'?' toast-success':'');e.textContent=m;document.getElementById('toasts').appendChild(e);setTimeout(()=>e.remove(),3000);}
</script>
</body>
</html>
