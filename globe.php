<?php
require_once 'includes/db.php';
$stmt = $pdo->query("SELECT s.id, s.title, s.type, s.vibe_tag, s.thumb_url, s.is_live, m.views, m.subs_count, u.display_name FROM streams s LEFT JOIN stream_metrics m ON s.id = m.stream_id LEFT JOIN users u ON s.user_id = u.id WHERE s.is_active = 1 ORDER BY s.is_live DESC LIMIT 20");
$dbStreams = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width,initial-scale=1.0">
<title>Globe — OmniGrid</title>
<link rel="preconnect" href="https://fonts.googleapis.com">
<link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&family=JetBrains+Mono:wght@500;700&display=swap" rel="stylesheet">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
<style>
:root {
    --bg: #08080d;
    --surface: #111118;
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
    background: #000;
    color: var(--text);
    overflow: hidden;
    -webkit-font-smoothing: antialiased;
}
#globe { position: fixed; inset: 0; cursor: grab; }
#globe:active { cursor: grabbing; }

.ui { position: fixed; z-index: 100; pointer-events: none; }
.ui > * { pointer-events: auto; }

/* Header */
.top-bar {
    position: fixed;
    top: 0;
    left: 0;
    right: 0;
    z-index: 100;
    padding: 1rem 1.5rem;
    display: flex;
    justify-content: space-between;
    align-items: center;
    background: linear-gradient(rgba(0, 0, 0, 0.7), transparent);
    pointer-events: auto;
    transition: opacity 0.4s;
}
.logo {
    font-family: 'JetBrains Mono', monospace;
    font-size: 1.3rem;
    font-weight: 700;
    text-decoration: none;
    color: #fff;
    display: flex;
    align-items: center;
    gap: 0.5rem;
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
    box-shadow: 0 4px 12px rgba(99, 102, 241, 0.3);
}
.logo span { color: var(--primary-light); }

.top-actions { display: flex; gap: 0.5rem; }
.pill-btn {
    background: rgba(255, 255, 255, 0.08);
    backdrop-filter: blur(12px);
    color: #fff;
    border: 1px solid rgba(255, 255, 255, 0.12);
    padding: 0.5rem 1.15rem;
    border-radius: var(--radius-pill);
    cursor: pointer;
    font-family: inherit;
    font-size: 0.85rem;
    font-weight: 500;
    display: inline-flex;
    align-items: center;
    gap: 0.4rem;
    text-decoration: none;
    transition: var(--transition);
}
.pill-btn:hover { background: rgba(255, 255, 255, 0.15); }

/* Center CTA */
.center-ui {
    position: fixed;
    top: 50%;
    left: 50%;
    transform: translate(-50%, -50%);
    text-align: center;
    z-index: 100;
    pointer-events: auto;
    transition: opacity 0.5s, transform 0.5s;
}
.center-ui.hidden {
    opacity: 0;
    pointer-events: none;
    transform: translate(-50%, -50%) scale(0.95);
}
.center-ui h1 {
    font-size: 2.5rem;
    font-weight: 800;
    margin-bottom: 0.5rem;
    text-shadow: 0 0 60px rgba(99, 102, 241, 0.4);
    letter-spacing: -0.03em;
}
.center-ui p {
    color: rgba(255, 255, 255, 0.5);
    font-size: 1.05rem;
    margin-bottom: 2rem;
}
.spin-btn {
    background: var(--gradient-primary);
    border: none;
    color: #fff;
    padding: 1rem 3rem;
    border-radius: var(--radius-pill);
    font-family: inherit;
    font-size: 1.1rem;
    font-weight: 600;
    cursor: pointer;
    box-shadow: 0 12px 48px rgba(99, 102, 241, 0.4);
    transition: var(--transition);
    display: inline-flex;
    align-items: center;
    gap: 0.6rem;
}
.spin-btn:hover {
    transform: scale(1.05);
    box-shadow: 0 16px 56px rgba(99, 102, 241, 0.5);
}

/* Side controls */
.controls {
    position: fixed;
    right: 1.25rem;
    top: 50%;
    transform: translateY(-50%);
    display: flex;
    flex-direction: column;
    gap: 0.35rem;
    z-index: 100;
    pointer-events: auto;
    transition: opacity 0.4s;
}
.ctrl {
    width: 40px;
    height: 40px;
    background: rgba(255, 255, 255, 0.06);
    backdrop-filter: blur(10px);
    border: 1px solid rgba(255, 255, 255, 0.1);
    border-radius: 10px;
    display: flex;
    align-items: center;
    justify-content: center;
    cursor: pointer;
    color: rgba(255, 255, 255, 0.7);
    font-size: 0.85rem;
    transition: var(--transition);
}
.ctrl:hover {
    background: rgba(255, 255, 255, 0.12);
    color: #fff;
}

.hint {
    position: fixed;
    bottom: 1.25rem;
    left: 50%;
    transform: translateX(-50%);
    color: rgba(255, 255, 255, 0.2);
    font-size: 0.75rem;
    z-index: 100;
    pointer-events: none;
    transition: opacity 0.4s;
}

/* My location indicator */
.my-loc {
    position: fixed;
    width: 12px;
    height: 12px;
    background: var(--success);
    border-radius: 50%;
    box-shadow: 0 0 16px var(--success);
    pointer-events: none;
    z-index: 50;
    display: none;
}
.my-loc::after {
    content: '';
    position: absolute;
    inset: -6px;
    border: 2px solid var(--success);
    border-radius: 50%;
    animation: ping 1.5s ease-out infinite;
}
@keyframes ping {
    0% { transform: scale(1); opacity: 1; }
    100% { transform: scale(2.5); opacity: 0; }
}

/* Stream popup */
.popup {
    position: fixed;
    background: rgba(17, 17, 24, 0.95);
    backdrop-filter: blur(24px);
    border: 1px solid var(--border-light);
    border-radius: var(--radius);
    width: 280px;
    display: none;
    z-index: 200;
    overflow: hidden;
    box-shadow: 0 16px 48px rgba(0, 0, 0, 0.5);
    animation: popIn 0.2s ease-out;
}
.popup.show { display: block; }
@keyframes popIn {
    from { opacity: 0; transform: scale(0.95); }
    to { opacity: 1; transform: scale(1); }
}

.popup-img {
    width: 100%;
    height: 120px;
    object-fit: cover;
    background: var(--surface);
    display: block;
}
.popup-body { padding: 1rem 1.1rem; }
.popup-title {
    font-weight: 600;
    font-size: 0.95rem;
    margin-bottom: 0.2rem;
}
.popup-tag {
    color: var(--primary-light);
    font-size: 0.8rem;
    font-weight: 500;
}
.popup-stats {
    display: flex;
    gap: 1rem;
    margin: 0.75rem 0;
    font-size: 0.8rem;
    color: var(--muted);
}
.popup-stats span {
    display: flex;
    align-items: center;
    gap: 0.3rem;
}
.popup-btn {
    display: flex;
    width: 100%;
    background: var(--gradient-primary);
    color: #fff;
    border: none;
    padding: 0.6rem;
    border-radius: var(--radius-sm);
    font-family: inherit;
    font-size: 0.85rem;
    font-weight: 600;
    cursor: pointer;
    text-decoration: none;
    align-items: center;
    justify-content: center;
    gap: 0.4rem;
    transition: var(--transition);
}
.popup-btn:hover { filter: brightness(1.1); }

.popup-live-badge {
    position: absolute;
    top: 0.5rem;
    left: 0.5rem;
    background: var(--danger);
    color: #fff;
    font-size: 0.65rem;
    font-weight: 700;
    text-transform: uppercase;
    padding: 0.15rem 0.5rem;
    border-radius: var(--radius-pill);
    letter-spacing: 0.05em;
}

/* Stream count indicator */
.stream-count {
    position: fixed;
    bottom: 1.25rem;
    right: 1.25rem;
    background: rgba(17, 17, 24, 0.9);
    backdrop-filter: blur(12px);
    border: 1px solid var(--border);
    border-radius: var(--radius-sm);
    padding: 0.5rem 0.85rem;
    font-size: 0.8rem;
    color: var(--text-secondary);
    z-index: 100;
    display: flex;
    align-items: center;
    gap: 0.4rem;
}
.stream-count .dot {
    width: 6px;
    height: 6px;
    background: var(--success);
    border-radius: 50%;
}

/* Fullscreen mode */
body.fs .top-bar,
body.fs .center-ui,
body.fs .hint { opacity: 0; pointer-events: none; }
</style>
</head>
<body>
<div id="globe"></div>

<div class="top-bar">
    <a href="./" class="logo"><div class="icon"><i class="fa-solid fa-cube"></i></div> Omni<span>Grid</span></a>
    <div class="top-actions">
        <a href="./" class="pill-btn"><i class="fa-solid fa-th-large"></i> Grid</a>
        <a href="contribute/" class="pill-btn"><i class="fa-solid fa-broadcast-tower"></i> Go Live</a>
    </div>
</div>

<div class="center-ui" id="centerUI">
    <h1>Spin the Globe</h1>
    <p>Drop into a random live feed anywhere on Earth</p>
    <button class="spin-btn" onclick="spin()"><i class="fa-solid fa-earth-americas"></i> Spin</button>
</div>

<div class="controls">
    <div class="ctrl" onclick="zoomIn()" title="Zoom In"><i class="fa-solid fa-plus"></i></div>
    <div class="ctrl" onclick="zoomOut()" title="Zoom Out"><i class="fa-solid fa-minus"></i></div>
    <div class="ctrl" onclick="reset()" title="Reset"><i class="fa-solid fa-house"></i></div>
    <div class="ctrl" onclick="toggleFS()" title="Fullscreen"><i class="fa-solid fa-expand"></i></div>
</div>

<div class="hint">Drag to rotate &middot; Scroll to zoom &middot; Click markers to explore</div>
<div class="my-loc" id="myLoc"></div>

<div class="popup" id="popup">
    <div style="position:relative">
        <img class="popup-img" id="pImg" alt="">
        <span class="popup-live-badge" id="pLiveBadge" style="display:none">Live</span>
    </div>
    <div class="popup-body">
        <div class="popup-title" id="pTitle"></div>
        <div class="popup-tag" id="pTag"></div>
        <div class="popup-stats">
            <span><i class="fa-solid fa-eye"></i> <span id="pViews"></span></span>
            <span><i class="fa-solid fa-users"></i> <span id="pSubs"></span></span>
        </div>
        <a class="popup-btn" id="pLink"><i class="fa-solid fa-play"></i> Watch</a>
    </div>
</div>

<div class="stream-count" id="streamCount">
    <span class="dot"></span>
    <span id="liveCount">0</span> streams on the grid
</div>

<script src="https://cdnjs.cloudflare.com/ajax/libs/three.js/r128/three.min.js"></script>
<script>
// Demo stream data with randomized locations
const defaultStreams = [
    {id:1,title:'Shibuya Crossing',tag:'city \u00b7 ambient',lat:35.66,lng:139.70,views:5150,subs:38,live:true},
    {id:2,title:'Studio Chill',tag:'lofi \u00b7 cozy',lat:34.05,lng:-118.24,views:3120,subs:21,live:false},
    {id:3,title:'NYC Live',tag:'urban',lat:40.71,lng:-74.00,views:8900,subs:104,live:true},
    {id:4,title:'Berlin Beats',tag:'music \u00b7 techno',lat:52.52,lng:13.40,views:2300,subs:57,live:false},
    {id:5,title:'S\u00e3o Paulo Streets',tag:'city \u00b7 culture',lat:-23.55,lng:-46.63,views:1800,subs:33,live:true},
    {id:6,title:'Sydney Harbour',tag:'travel',lat:-33.86,lng:151.21,views:4200,subs:82,live:false}
];

<?php if (!empty($dbStreams)): ?>
const dbStreams = <?= json_encode($dbStreams) ?>;
const streams = dbStreams.map((s, i) => ({
    id: s.id,
    title: s.title,
    tag: s.vibe_tag || s.type,
    lat: (Math.random() - 0.5) * 120,
    lng: (Math.random() - 0.5) * 300,
    views: parseInt(s.views) || 0,
    subs: parseInt(s.subs_count) || 0,
    live: !!parseInt(s.is_live),
    thumb: s.thumb_url || '',
    creator: s.display_name || 'Anonymous'
}));
<?php else: ?>
const streams = defaultStreams;
<?php endif; ?>

document.getElementById('liveCount').textContent = streams.length;

let scene, camera, renderer, globe, markers = [], isDrag = false, prev = {x:0,y:0}, vel = {x:0,y:0},
    spinEnd = 0, tgtZoom = 2.5, zoom = 2.5, myLat = null, myLng = null;

function init() {
    scene = new THREE.Scene();
    camera = new THREE.PerspectiveCamera(45, innerWidth / innerHeight, 0.1, 1000);
    camera.position.z = zoom;
    renderer = new THREE.WebGLRenderer({ antialias: true, alpha: true });
    renderer.setSize(innerWidth, innerHeight);
    renderer.setPixelRatio(Math.min(devicePixelRatio, 2));
    document.getElementById('globe').appendChild(renderer.domElement);

    // Earth
    const geo = new THREE.SphereGeometry(1, 64, 64);
    const tex = new THREE.CanvasTexture(createTexture());
    globe = new THREE.Mesh(geo, new THREE.MeshPhongMaterial({
        map: tex, bumpScale: 0.02, specular: 0x222233, shininess: 8
    }));
    scene.add(globe);

    // Atmosphere
    const atmosMat = new THREE.ShaderMaterial({
        vertexShader: `varying vec3 vN; void main(){ vN = normalize(normalMatrix * normal); gl_Position = projectionMatrix * modelViewMatrix * vec4(position, 1.); }`,
        fragmentShader: `varying vec3 vN; void main(){ float i = pow(0.6 - dot(vN, vec3(0,0,1)), 2.5); gl_FragColor = vec4(0.35, 0.45, 1.0, 1.0) * i; }`,
        blending: THREE.AdditiveBlending, side: THREE.BackSide, transparent: true
    });
    scene.add(new THREE.Mesh(new THREE.SphereGeometry(1.02, 64, 64), atmosMat));

    // Lights
    const sun = new THREE.DirectionalLight(0xffffff, 1.1);
    sun.position.set(5, 3, 5);
    scene.add(sun);
    scene.add(new THREE.AmbientLight(0x334466, 0.5));

    // Stars
    const starGeo = new THREE.BufferGeometry(), sv = [];
    for (let i = 0; i < 10000; i++) {
        sv.push((Math.random() - 0.5) * 200, (Math.random() - 0.5) * 200, (Math.random() - 0.5) * 200);
    }
    starGeo.setAttribute('position', new THREE.Float32BufferAttribute(sv, 3));
    scene.add(new THREE.Points(starGeo, new THREE.PointsMaterial({ color: 0xffffff, size: 0.04 })));

    // Markers
    streams.forEach(s => {
        const p = ll2v(s.lat, s.lng, 1.012);
        const color = s.live ? 0xef4444 : 0x6366f1;
        const m = new THREE.Mesh(
            new THREE.SphereGeometry(0.015, 16, 16),
            new THREE.MeshBasicMaterial({ color })
        );
        m.position.copy(p);
        m.userData = s;
        globe.add(m);
        markers.push(m);

        // Glow ring
        const g = new THREE.Mesh(
            new THREE.SphereGeometry(s.live ? 0.035 : 0.025, 16, 16),
            new THREE.MeshBasicMaterial({ color, transparent: true, opacity: s.live ? 0.35 : 0.2 })
        );
        g.position.copy(p);
        globe.add(g);
    });

    getLocation();
    bindEvents();
    animate();
}

function createTexture() {
    const c = document.createElement('canvas');
    c.width = 1024; c.height = 512;
    const x = c.getContext('2d');
    // Ocean
    const grad = x.createLinearGradient(0, 0, 0, 512);
    grad.addColorStop(0, '#0c1e3a');
    grad.addColorStop(0.5, '#0f2942');
    grad.addColorStop(1, '#0c1e3a');
    x.fillStyle = grad;
    x.fillRect(0, 0, 1024, 512);
    // Continents
    x.fillStyle = '#15543a';
    [[180,140,110,90],[260,320,55,110],[520,220,70,160],[720,140,160,110],[830,340,55,45],[400,260,35,55]].forEach(([cx,cy,rx,ry]) => {
        x.beginPath();
        x.ellipse(cx, cy, rx, ry, 0, 0, Math.PI * 2);
        x.fill();
    });
    // Subtle grid
    x.strokeStyle = 'rgba(99, 102, 241, 0.03)';
    x.lineWidth = 0.5;
    for (let i = 0; i <= 512; i += 32) {
        x.beginPath(); x.moveTo(0, i); x.lineTo(1024, i); x.stroke();
    }
    for (let i = 0; i <= 1024; i += 32) {
        x.beginPath(); x.moveTo(i, 0); x.lineTo(i, 512); x.stroke();
    }
    return c;
}

function ll2v(lat, lng, r) {
    const phi = (90 - lat) * Math.PI / 180, theta = (lng + 180) * Math.PI / 180;
    return new THREE.Vector3(-r * Math.sin(phi) * Math.cos(theta), r * Math.cos(phi), r * Math.sin(phi) * Math.sin(theta));
}

function getLocation() {
    if (navigator.geolocation) navigator.geolocation.getCurrentPosition(p => {
        myLat = p.coords.latitude; myLng = p.coords.longitude;
    });
}

function updateMyLoc() {
    if (myLat === null) return;
    const p = ll2v(myLat, myLng, 1.02).applyMatrix4(globe.matrixWorld).project(camera);
    const el = document.getElementById('myLoc');
    if (p.z < 1) {
        el.style.display = 'block';
        el.style.left = (p.x + 1) / 2 * innerWidth + 'px';
        el.style.top = (-p.y + 1) / 2 * innerHeight + 'px';
    } else {
        el.style.display = 'none';
    }
}

function bindEvents() {
    const c = renderer.domElement;
    c.addEventListener('mousedown', e => { isDrag = true; prev = {x: e.clientX, y: e.clientY}; vel = {x:0,y:0}; spinEnd = 0; });
    c.addEventListener('mousemove', e => {
        if (!isDrag) return;
        const dx = e.clientX - prev.x, dy = e.clientY - prev.y;
        vel = {x: dx * 0.002, y: dy * 0.002};
        globe.rotation.y += dx * 0.005;
        globe.rotation.x = Math.max(-1.5, Math.min(1.5, globe.rotation.x + dy * 0.005));
        prev = {x: e.clientX, y: e.clientY};
    });
    c.addEventListener('mouseup', () => {
        if (!isDrag) return;
        isDrag = false;
        const spd = Math.sqrt(vel.x * vel.x + vel.y * vel.y);
        if (spd > 0.003) { spinEnd = Date.now() + Math.min(17000, spd * 50000); vel.x *= 5; }
    });
    c.addEventListener('wheel', e => {
        e.preventDefault();
        tgtZoom = Math.max(1.3, Math.min(5, tgtZoom + e.deltaY * 0.001));
    }, { passive: false });
    c.addEventListener('click', e => {
        const ray = new THREE.Raycaster();
        const mouse = new THREE.Vector2((e.clientX / innerWidth) * 2 - 1, -(e.clientY / innerHeight) * 2 + 1);
        ray.setFromCamera(mouse, camera);
        const hits = ray.intersectObjects(markers);
        if (hits.length) { showPopup(hits[0].object.userData, e.clientX, e.clientY); }
        else { hidePopup(); document.body.classList.toggle('fs'); }
    });
    c.addEventListener('touchstart', e => { e.preventDefault(); isDrag = true; prev = {x: e.touches[0].clientX, y: e.touches[0].clientY}; spinEnd = 0; }, { passive: false });
    c.addEventListener('touchmove', e => {
        e.preventDefault(); if (!isDrag) return;
        const dx = e.touches[0].clientX - prev.x, dy = e.touches[0].clientY - prev.y;
        vel = {x: dx * 0.002, y: dy * 0.002};
        globe.rotation.y += dx * 0.005;
        globe.rotation.x = Math.max(-1.5, Math.min(1.5, globe.rotation.x + dy * 0.005));
        prev = {x: e.touches[0].clientX, y: e.touches[0].clientY};
    }, { passive: false });
    c.addEventListener('touchend', () => {
        isDrag = false;
        const spd = Math.sqrt(vel.x * vel.x + vel.y * vel.y);
        if (spd > 0.002) { spinEnd = Date.now() + Math.min(17000, spd * 80000); vel.x *= 8; }
    });
    addEventListener('resize', () => {
        camera.aspect = innerWidth / innerHeight;
        camera.updateProjectionMatrix();
        renderer.setSize(innerWidth, innerHeight);
    });
}

function showPopup(s, x, y) {
    const p = document.getElementById('popup');
    p.style.left = Math.min(x + 10, innerWidth - 300) + 'px';
    p.style.top = Math.min(y + 10, innerHeight - 280) + 'px';
    document.getElementById('pImg').src = s.thumb || 'assets/img/thumbs/' + s.id + '.jpg';
    document.getElementById('pTitle').textContent = s.title;
    document.getElementById('pTag').textContent = s.tag;
    document.getElementById('pViews').textContent = (s.views || 0).toLocaleString();
    document.getElementById('pSubs').textContent = s.subs || 0;
    document.getElementById('pLink').href = 'live.php?id=' + s.id;
    const badge = document.getElementById('pLiveBadge');
    badge.style.display = s.live ? 'block' : 'none';
    p.classList.add('show');
}

function hidePopup() { document.getElementById('popup').classList.remove('show'); }

function spin() {
    document.getElementById('centerUI').classList.add('hidden');
    const dur = 3 + Math.random() * 10, dir = Math.random() > 0.5 ? 1 : -1;
    vel.x = dir * (0.04 + Math.random() * 0.06);
    spinEnd = Date.now() + dur * 1000;
    setTimeout(() => {
        const s = streams[Math.floor(Math.random() * streams.length)];
        focusOn(s.lat, s.lng);
        setTimeout(() => showPopup(s, innerWidth / 2 - 140, innerHeight / 2 - 100), 800);
    }, dur * 1000);
}

function focusOn(lat, lng) {
    const phi = (90 - lat) * Math.PI / 180, theta = (lng + 180) * Math.PI / 180;
    globe.rotation.x = Math.PI / 2 - phi;
    globe.rotation.y = -theta + Math.PI;
}

function zoomIn() { tgtZoom = Math.max(1.3, tgtZoom - 0.4); }
function zoomOut() { tgtZoom = Math.min(5, tgtZoom + 0.4); }
function reset() {
    tgtZoom = 2.5; globe.rotation.x = 0; globe.rotation.y = 0;
    vel = {x:0,y:0}; spinEnd = 0;
    document.getElementById('centerUI').classList.remove('hidden');
    hidePopup(); document.body.classList.remove('fs');
}
function toggleFS() {
    document.body.classList.toggle('fs');
    if (document.body.classList.contains('fs')) document.documentElement.requestFullscreen?.();
    else document.exitFullscreen?.();
}

// Pulsing marker animation
let pulseTime = 0;

function animate() {
    requestAnimationFrame(animate);
    pulseTime += 0.03;

    if (Date.now() < spinEnd) {
        const t = 1 - (spinEnd - Date.now()) / (spinEnd - Date.now() + 1000);
        globe.rotation.y += vel.x * (1 - t * t);
    } else if (!isDrag && Math.abs(vel.x) > 0.0001) {
        globe.rotation.y += vel.x;
        vel.x *= 0.96;
    }
    zoom += (tgtZoom - zoom) * 0.1;
    camera.position.z = zoom;

    // Pulse live markers
    markers.forEach(m => {
        if (m.userData.live) {
            const s = 1 + Math.sin(pulseTime * 2) * 0.3;
            m.scale.set(s, s, s);
        }
    });

    updateMyLoc();
    renderer.render(scene, camera);
}
init();
</script>
</body>
</html>
