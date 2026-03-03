<?php
session_start();
if (empty($_SESSION['user_id'])) {
    header('Location: ../login.php');
    exit;
}

require_once '../includes/db.php';

$stream_id = (int)($_GET['id'] ?? 0);
if (!$stream_id) {
    header('Location: ./');
    exit;
}

$stmt = $pdo->prepare("SELECT * FROM streams WHERE id = ? AND user_id = ?");
$stmt->execute([$stream_id, $_SESSION['user_id']]);
$stream = $stmt->fetch();

if (!$stream) {
    header('Location: ./');
    exit;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Go Live | OmniGrid</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    <style>
        * { box-sizing: border-box; margin: 0; padding: 0; }
        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
            background: #0a0a0f;
            color: #e0e0e0;
            min-height: 100vh;
        }
        .header {
            background: #12121a;
            padding: 0.75rem 1.5rem;
            display: flex;
            justify-content: space-between;
            align-items: center;
            border-bottom: 1px solid #2a2a3e;
        }
        .logo { font-size: 1.25rem; font-weight: 700; color: #fff; text-decoration: none; }
        .logo span { color: #6366f1; }
        .btn {
            background: #6366f1; color: #fff; border: none; padding: 0.6rem 1.2rem;
            border-radius: 6px; cursor: pointer; font-size: 0.9rem; text-decoration: none;
            display: inline-flex; align-items: center; gap: 0.5rem;
        }
        .btn:hover { background: #4f46e5; }
        .btn-outline { background: transparent; border: 1px solid #3a3a4e; }
        .btn-live { background: #ef4444; }
        .btn-live:hover { background: #dc2626; }
        .btn-stop { background: #666; }
        
        .main {
            max-width: 1000px;
            margin: 0 auto;
            padding: 2rem;
        }
        h1 { margin-bottom: 0.5rem; }
        .subtitle { color: #888; margin-bottom: 2rem; }
        
        .studio {
            display: grid;
            grid-template-columns: 1fr 300px;
            gap: 1.5rem;
        }
        
        .preview-container {
            background: #000;
            border-radius: 12px;
            overflow: hidden;
            position: relative;
            aspect-ratio: 16/9;
        }
        #preview {
            width: 100%;
            height: 100%;
            object-fit: cover;
            transform: scaleX(-1);
        }
        .preview-overlay {
            position: absolute;
            top: 1rem;
            left: 1rem;
            display: flex;
            gap: 0.5rem;
        }
        .live-badge {
            background: #ef4444;
            color: #fff;
            padding: 0.3rem 0.75rem;
            border-radius: 4px;
            font-size: 0.8rem;
            font-weight: 600;
            display: none;
            align-items: center;
            gap: 0.3rem;
        }
        .live-badge.active { display: flex; }
        .live-badge::before {
            content: '';
            width: 8px;
            height: 8px;
            background: #fff;
            border-radius: 50%;
            animation: pulse 1s infinite;
        }
        @keyframes pulse { 0%,100% { opacity: 1; } 50% { opacity: 0.4; } }
        
        .timer {
            background: rgba(0,0,0,0.7);
            padding: 0.3rem 0.75rem;
            border-radius: 4px;
            font-size: 0.85rem;
            font-family: monospace;
        }
        
        .controls {
            background: #12121a;
            border: 1px solid #2a2a3e;
            border-radius: 12px;
            padding: 1.5rem;
        }
        .controls h2 {
            font-size: 1rem;
            margin-bottom: 1rem;
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }
        .controls h2 i { color: #6366f1; }
        
        .control-group {
            margin-bottom: 1.25rem;
        }
        .control-group label {
            display: block;
            font-size: 0.85rem;
            color: #888;
            margin-bottom: 0.4rem;
        }
        .control-group select {
            width: 100%;
            background: #0a0a0f;
            border: 1px solid #2a2a3e;
            border-radius: 6px;
            padding: 0.6rem;
            color: #e0e0e0;
            font-size: 0.9rem;
        }
        
        .meter {
            height: 8px;
            background: #1a1a2e;
            border-radius: 4px;
            overflow: hidden;
        }
        .meter-fill {
            height: 100%;
            background: #10b981;
            width: 0%;
            transition: width 0.1s;
        }
        
        .go-live-btn {
            width: 100%;
            padding: 1rem;
            font-size: 1rem;
            margin-top: 1rem;
        }
        
        .status-card {
            background: #1a1a2e;
            border-radius: 8px;
            padding: 1rem;
            margin-top: 1rem;
        }
        .status-card h3 {
            font-size: 0.85rem;
            color: #888;
            margin-bottom: 0.5rem;
        }
        .status-card .value {
            font-size: 1.5rem;
            font-weight: 600;
        }
        .status-card .value.live { color: #ef4444; }
        
        .error-msg {
            background: rgba(239,68,68,0.1);
            border: 1px solid #ef4444;
            color: #ef4444;
            padding: 1rem;
            border-radius: 8px;
            margin-bottom: 1rem;
            display: none;
        }
        
        .permission-prompt {
            position: absolute;
            inset: 0;
            background: rgba(0,0,0,0.9);
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            text-align: center;
            padding: 2rem;
        }
        .permission-prompt i { font-size: 3rem; color: #6366f1; margin-bottom: 1rem; }
        .permission-prompt h2 { margin-bottom: 0.5rem; }
        .permission-prompt p { color: #888; margin-bottom: 1.5rem; }
        .permission-prompt.hidden { display: none; }
        
        @media (max-width: 800px) {
            .studio { grid-template-columns: 1fr; }
        }
    </style>
</head>
<body>
    <header class="header">
        <a href="../" class="logo">Omni<span>Grid</span></a>
        <a href="./" class="btn btn-outline"><i class="fa fa-arrow-left"></i> Dashboard</a>
    </header>
    
    <main class="main">
        <h1><?= htmlspecialchars($stream['title']) ?></h1>
        <p class="subtitle">Browser Streaming Studio</p>
        
        <div class="error-msg" id="error"></div>
        
        <div class="studio">
            <div class="preview-container">
                <video id="preview" autoplay muted playsinline></video>
                <div class="preview-overlay">
                    <div class="live-badge" id="liveBadge">LIVE</div>
                    <div class="timer" id="timer">00:00:00</div>
                </div>
                <div class="permission-prompt" id="permissionPrompt">
                    <i class="fa fa-video"></i>
                    <h2>Camera Access Required</h2>
                    <p>Click below to allow camera and microphone access</p>
                    <button class="btn" onclick="requestCamera()">
                        <i class="fa fa-camera"></i> Enable Camera
                    </button>
                </div>
            </div>
            
            <div class="controls">
                <h2><i class="fa fa-sliders-h"></i> Stream Settings</h2>
                
                <div class="control-group">
                    <label>Camera</label>
                    <select id="videoSource"></select>
                </div>
                
                <div class="control-group">
                    <label>Microphone</label>
                    <select id="audioSource"></select>
                </div>
                
                <div class="control-group">
                    <label>Audio Level</label>
                    <div class="meter">
                        <div class="meter-fill" id="audioMeter"></div>
                    </div>
                </div>
                
                <div class="control-group">
                    <label>Quality</label>
                    <select id="quality">
                        <option value="720">720p (Recommended)</option>
                        <option value="1080">1080p</option>
                        <option value="480">480p (Low bandwidth)</option>
                    </select>
                </div>
                
                <button class="btn btn-live go-live-btn" id="goLiveBtn" onclick="toggleStream()">
                    <i class="fa fa-broadcast-tower"></i> Go Live
                </button>
                
                <div class="status-card">
                    <h3>Status</h3>
                    <div class="value" id="statusText">Ready</div>
                </div>
                
                <div class="status-card">
                    <h3>Viewers</h3>
                    <div class="value" id="viewerCount">0</div>
                </div>
            </div>
        </div>
    </main>
    
    <script>
        const streamId = <?= $stream_id ?>;
        let mediaStream = null;
        let mediaRecorder = null;
        let isLive = false;
        let startTime = null;
        let timerInterval = null;
        let audioContext = null;
        let analyser = null;
        
        // Request camera on load
        window.onload = () => requestCamera();
        
        async function requestCamera() {
            try {
                const quality = document.getElementById('quality').value;
                const constraints = {
                    video: {
                        width: { ideal: quality === '1080' ? 1920 : quality === '720' ? 1280 : 854 },
                        height: { ideal: quality === '1080' ? 1080 : quality === '720' ? 720 : 480 },
                        facingMode: 'user'
                    },
                    audio: {
                        echoCancellation: true,
                        noiseSuppression: true
                    }
                };
                
                mediaStream = await navigator.mediaDevices.getUserMedia(constraints);
                document.getElementById('preview').srcObject = mediaStream;
                document.getElementById('permissionPrompt').classList.add('hidden');
                
                // Populate device lists
                await populateDevices();
                
                // Setup audio meter
                setupAudioMeter();
                
            } catch (err) {
                showError('Camera access denied. Please allow camera permissions and reload.');
                console.error(err);
            }
        }
        
        async function populateDevices() {
            const devices = await navigator.mediaDevices.enumerateDevices();
            const videoSelect = document.getElementById('videoSource');
            const audioSelect = document.getElementById('audioSource');
            
            videoSelect.innerHTML = '';
            audioSelect.innerHTML = '';
            
            devices.forEach(device => {
                const option = document.createElement('option');
                option.value = device.deviceId;
                option.text = device.label || `${device.kind} ${device.deviceId.slice(0,5)}`;
                
                if (device.kind === 'videoinput') videoSelect.appendChild(option);
                if (device.kind === 'audioinput') audioSelect.appendChild(option);
            });
            
            // Switch device when changed
            videoSelect.onchange = audioSelect.onchange = switchDevice;
        }
        
        async function switchDevice() {
            if (mediaStream) {
                mediaStream.getTracks().forEach(t => t.stop());
            }
            
            const videoSource = document.getElementById('videoSource').value;
            const audioSource = document.getElementById('audioSource').value;
            const quality = document.getElementById('quality').value;
            
            const constraints = {
                video: {
                    deviceId: videoSource ? { exact: videoSource } : undefined,
                    width: { ideal: quality === '1080' ? 1920 : quality === '720' ? 1280 : 854 },
                    height: { ideal: quality === '1080' ? 1080 : quality === '720' ? 720 : 480 }
                },
                audio: {
                    deviceId: audioSource ? { exact: audioSource } : undefined
                }
            };
            
            try {
                mediaStream = await navigator.mediaDevices.getUserMedia(constraints);
                document.getElementById('preview').srcObject = mediaStream;
                setupAudioMeter();
            } catch (err) {
                showError('Could not switch device');
            }
        }
        
        function setupAudioMeter() {
            if (!mediaStream) return;
            
            audioContext = new AudioContext();
            analyser = audioContext.createAnalyser();
            const source = audioContext.createMediaStreamSource(mediaStream);
            source.connect(analyser);
            analyser.fftSize = 256;
            
            const dataArray = new Uint8Array(analyser.frequencyBinCount);
            const meter = document.getElementById('audioMeter');
            
            function updateMeter() {
                analyser.getByteFrequencyData(dataArray);
                const avg = dataArray.reduce((a, b) => a + b) / dataArray.length;
                meter.style.width = Math.min(100, avg * 1.5) + '%';
                requestAnimationFrame(updateMeter);
            }
            updateMeter();
        }
        
        async function toggleStream() {
            if (isLive) {
                stopStream();
            } else {
                startStream();
            }
        }
        
        async function startStream() {
            if (!mediaStream) {
                showError('Camera not ready');
                return;
            }
            
            isLive = true;
            startTime = Date.now();
            
            // Update UI
            document.getElementById('liveBadge').classList.add('active');
            document.getElementById('goLiveBtn').innerHTML = '<i class="fa fa-stop"></i> End Stream';
            document.getElementById('goLiveBtn').classList.remove('btn-live');
            document.getElementById('goLiveBtn').classList.add('btn-stop');
            document.getElementById('statusText').textContent = 'LIVE';
            document.getElementById('statusText').classList.add('live');
            
            // Start timer
            timerInterval = setInterval(updateTimer, 1000);
            
            // Start recording and uploading chunks
            const mimeType = MediaRecorder.isTypeSupported('video/webm;codecs=vp9') 
                ? 'video/webm;codecs=vp9' 
                : 'video/webm';
            
            mediaRecorder = new MediaRecorder(mediaStream, {
                mimeType: mimeType,
                videoBitsPerSecond: 2500000
            });
            
            mediaRecorder.ondataavailable = async (e) => {
                if (e.data.size > 0 && isLive) {
                    await uploadChunk(e.data);
                }
            };
            
            // Record in 2-second chunks
            mediaRecorder.start(2000);
            
            // Notify server stream started
            await fetch('stream_status.php', {
                method: 'POST',
                headers: {'Content-Type': 'application/json'},
                body: JSON.stringify({stream_id: streamId, status: 'live'})
            });
        }
        
        function stopStream() {
            isLive = false;
            
            if (mediaRecorder && mediaRecorder.state !== 'inactive') {
                mediaRecorder.stop();
            }
            
            clearInterval(timerInterval);
            
            // Update UI
            document.getElementById('liveBadge').classList.remove('active');
            document.getElementById('goLiveBtn').innerHTML = '<i class="fa fa-broadcast-tower"></i> Go Live';
            document.getElementById('goLiveBtn').classList.add('btn-live');
            document.getElementById('goLiveBtn').classList.remove('btn-stop');
            document.getElementById('statusText').textContent = 'Offline';
            document.getElementById('statusText').classList.remove('live');
            
            // Notify server
            fetch('stream_status.php', {
                method: 'POST',
                headers: {'Content-Type': 'application/json'},
                body: JSON.stringify({stream_id: streamId, status: 'offline'})
            });
        }
        
        async function uploadChunk(blob) {
            const formData = new FormData();
            formData.append('chunk', blob);
            formData.append('stream_id', streamId);
            formData.append('timestamp', Date.now());
            
            try {
                const res = await fetch('upload_chunk.php', {
                    method: 'POST',
                    body: formData
                });
                const data = await res.json();
                if (data.viewers !== undefined) {
                    document.getElementById('viewerCount').textContent = data.viewers;
                }
            } catch (err) {
                console.error('Upload failed:', err);
            }
        }
        
        function updateTimer() {
            const elapsed = Math.floor((Date.now() - startTime) / 1000);
            const hrs = Math.floor(elapsed / 3600).toString().padStart(2, '0');
            const mins = Math.floor((elapsed % 3600) / 60).toString().padStart(2, '0');
            const secs = (elapsed % 60).toString().padStart(2, '0');
            document.getElementById('timer').textContent = `${hrs}:${mins}:${secs}`;
        }
        
        function showError(msg) {
            const el = document.getElementById('error');
            el.textContent = msg;
            el.style.display = 'block';
        }
        
        // Quality change
        document.getElementById('quality').onchange = switchDevice;
        
        // Warn before leaving while live
        window.onbeforeunload = (e) => {
            if (isLive) {
                e.preventDefault();
                return 'You are still live. Are you sure you want to leave?';
            }
        };
    </script>
</body>
</html>
