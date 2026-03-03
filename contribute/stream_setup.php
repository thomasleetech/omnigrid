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

// Verify ownership
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
    <title>Stream Setup | OmniGrid</title>
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
            background: #6366f1; color: #fff; border: none; padding: 0.5rem 1rem;
            border-radius: 6px; cursor: pointer; font-size: 0.85rem; text-decoration: none;
            display: inline-flex; align-items: center; gap: 0.4rem;
        }
        .btn:hover { background: #4f46e5; }
        .btn-outline { background: transparent; border: 1px solid #3a3a4e; }
        
        .main { max-width: 800px; margin: 0 auto; padding: 2rem; }
        h1 { margin-bottom: 0.5rem; }
        .subtitle { color: #888; margin-bottom: 2rem; }
        
        .card {
            background: #12121a;
            border: 1px solid #2a2a3e;
            border-radius: 12px;
            padding: 1.5rem;
            margin-bottom: 1.5rem;
        }
        .card h2 { font-size: 1.1rem; margin-bottom: 1rem; display: flex; align-items: center; gap: 0.5rem; }
        .card h2 i { color: #6366f1; }
        
        .cred-row {
            display: flex;
            align-items: center;
            gap: 0.75rem;
            margin-bottom: 1rem;
        }
        .cred-row label { width: 120px; color: #888; font-size: 0.9rem; }
        .cred-row input {
            flex: 1;
            background: #0a0a0f;
            border: 1px solid #2a2a3e;
            border-radius: 6px;
            padding: 0.7rem 1rem;
            color: #e0e0e0;
            font-family: monospace;
        }
        .cred-row button {
            background: #1a1a2e;
            border: 1px solid #2a2a3e;
            color: #e0e0e0;
            padding: 0.7rem 1rem;
            border-radius: 6px;
            cursor: pointer;
        }
        .cred-row button:hover { background: #2a2a3e; }
        
        .warning {
            background: rgba(239, 68, 68, 0.1);
            border: 1px solid #ef4444;
            border-radius: 8px;
            padding: 1rem;
            margin-bottom: 1rem;
            font-size: 0.9rem;
        }
        .warning i { color: #ef4444; margin-right: 0.5rem; }
        
        .steps { counter-reset: step; }
        .step {
            position: relative;
            padding-left: 2.5rem;
            margin-bottom: 1.5rem;
        }
        .step::before {
            counter-increment: step;
            content: counter(step);
            position: absolute;
            left: 0;
            width: 1.75rem;
            height: 1.75rem;
            background: #6366f1;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 0.85rem;
            font-weight: 600;
        }
        .step h3 { font-size: 1rem; margin-bottom: 0.5rem; }
        .step p { color: #aaa; font-size: 0.9rem; }
        .step code {
            display: block;
            background: #0a0a0f;
            border: 1px solid #2a2a3e;
            border-radius: 6px;
            padding: 0.75rem 1rem;
            margin-top: 0.5rem;
            font-family: monospace;
            font-size: 0.85rem;
            word-break: break-all;
        }
        
        .status-badge {
            display: inline-flex;
            align-items: center;
            gap: 0.4rem;
            padding: 0.4rem 0.8rem;
            border-radius: 20px;
            font-size: 0.85rem;
            font-weight: 500;
        }
        .status-badge.offline { background: #333; color: #888; }
        .status-badge.live { background: #10b981; color: #fff; }
        .status-badge::before {
            content: '';
            width: 8px;
            height: 8px;
            border-radius: 50%;
            background: currentColor;
        }
        
        .loading { text-align: center; padding: 3rem; color: #666; }
        .loading i { font-size: 2rem; margin-bottom: 1rem; }
        
        #credentials { display: none; }
    </style>
</head>
<body>
    <header class="header">
        <a href="../" class="logo">Omni<span>Grid</span></a>
        <a href="./" class="btn btn-outline"><i class="fa fa-arrow-left"></i> Dashboard</a>
    </header>
    
    <main class="main">
        <h1>Stream Setup: <?= htmlspecialchars($stream['title']) ?></h1>
        <p class="subtitle">Configure your streaming software to broadcast to OmniGrid</p>
        
        <div class="loading" id="loading">
            <i class="fa fa-spinner fa-spin"></i>
            <p>Generating stream credentials...</p>
        </div>
        
        <div id="credentials">
            <div class="card">
                <h2><i class="fa fa-key"></i> Your Stream Credentials</h2>
                
                <div class="warning">
                    <i class="fa fa-exclamation-triangle"></i>
                    <strong>Keep these secret!</strong> Anyone with your stream key can broadcast to your channel.
                </div>
                
                <div class="cred-row">
                    <label>Server URL</label>
                    <input type="text" id="rtmpUrl" readonly>
                    <button onclick="copyField('rtmpUrl')"><i class="fa fa-copy"></i></button>
                </div>
                
                <div class="cred-row">
                    <label>Stream Key</label>
                    <input type="password" id="streamKey" readonly>
                    <button onclick="toggleKey()"><i class="fa fa-eye" id="eyeIcon"></i></button>
                    <button onclick="copyField('streamKey')"><i class="fa fa-copy"></i></button>
                </div>
                
                <div class="cred-row">
                    <label>Status</label>
                    <span class="status-badge offline" id="statusBadge">Offline</span>
                </div>
            </div>
            
            <div class="card">
                <h2><i class="fa fa-desktop"></i> OBS Studio Setup</h2>
                
                <div class="steps">
                    <div class="step">
                        <h3>Open OBS Settings</h3>
                        <p>Go to <strong>Settings → Stream</strong></p>
                    </div>
                    
                    <div class="step">
                        <h3>Select Custom Server</h3>
                        <p>Set <strong>Service</strong> to "Custom..." </p>
                    </div>
                    
                    <div class="step">
                        <h3>Enter Server URL</h3>
                        <p>Paste your Server URL:</p>
                        <code id="displayRtmp"></code>
                    </div>
                    
                    <div class="step">
                        <h3>Enter Stream Key</h3>
                        <p>Paste your Stream Key (click eye icon above to reveal)</p>
                    </div>
                    
                    <div class="step">
                        <h3>Recommended Settings</h3>
                        <p>Go to <strong>Settings → Output</strong> and set:</p>
                        <code>
Output Mode: Advanced<br>
Encoder: x264 (or NVENC if available)<br>
Rate Control: CBR<br>
Bitrate: 2500-6000 Kbps<br>
Keyframe Interval: 2 seconds
                        </code>
                    </div>
                    
                    <div class="step">
                        <h3>Start Streaming</h3>
                        <p>Click "Start Streaming" in OBS. Your stream will appear on OmniGrid within seconds.</p>
                    </div>
                </div>
            </div>
            
            <div class="card">
                <h2><i class="fa fa-mobile-alt"></i> Mobile Streaming</h2>
                <p style="color:#aaa;margin-bottom:1rem;">Use apps like <strong>Larix Broadcaster</strong> (iOS/Android) or <strong>Streamlabs Mobile</strong></p>
                <p style="color:#888;font-size:0.9rem;">Enter the same Server URL and Stream Key in the app's RTMP settings.</p>
            </div>
            
            <a href="../live.php?id=<?= $stream_id ?>" class="btn" style="width:100%;justify-content:center;padding:1rem;">
                <i class="fa fa-play"></i> View Your Stream
            </a>
        </div>
    </main>
    
    <script>
        const streamId = <?= $stream_id ?>;
        
        async function loadCredentials() {
            try {
                const res = await fetch('get_stream_key.php', {
                    method: 'POST',
                    headers: {'Content-Type': 'application/json'},
                    body: JSON.stringify({stream_id: streamId})
                });
                const data = await res.json();
                
                if (data.success) {
                    document.getElementById('rtmpUrl').value = data.rtmp_url;
                    document.getElementById('streamKey').value = data.stream_key;
                    document.getElementById('displayRtmp').textContent = data.rtmp_url;
                    
                    document.getElementById('loading').style.display = 'none';
                    document.getElementById('credentials').style.display = 'block';
                } else {
                    document.getElementById('loading').innerHTML = `<i class="fa fa-exclamation-circle" style="color:#ef4444"></i><p>${data.error || 'Failed to load credentials'}</p>`;
                }
            } catch (err) {
                document.getElementById('loading').innerHTML = '<i class="fa fa-exclamation-circle" style="color:#ef4444"></i><p>Network error</p>';
            }
        }
        
        function toggleKey() {
            const input = document.getElementById('streamKey');
            const icon = document.getElementById('eyeIcon');
            if (input.type === 'password') {
                input.type = 'text';
                icon.classList.replace('fa-eye', 'fa-eye-slash');
            } else {
                input.type = 'password';
                icon.classList.replace('fa-eye-slash', 'fa-eye');
            }
        }
        
        function copyField(id) {
            const input = document.getElementById(id);
            const originalType = input.type;
            input.type = 'text';
            input.select();
            document.execCommand('copy');
            input.type = originalType;
            
            // Flash feedback
            input.style.borderColor = '#10b981';
            setTimeout(() => input.style.borderColor = '', 500);
        }
        
        loadCredentials();
    </script>
</body>
</html>
