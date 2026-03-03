<?php
session_start();
if (empty($_SESSION['user_id'])) {
    header('Location: ../login.php');
    exit;
}

require_once '../includes/db.php';

// Get user info
$stmt = $pdo->prepare("SELECT display_name, email FROM users WHERE id = ?");
$stmt->execute([$_SESSION['user_id']]);
$user = $stmt->fetch();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Creator Dashboard | OmniGrid</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    <style>
        * { box-sizing: border-box; margin: 0; padding: 0; }
        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
            background: #0a0a0f;
            color: #e0e0e0;
            min-height: 100vh;
        }
        
        /* Header */
        .header {
            background: linear-gradient(135deg, #12121a 0%, #1a1a2e 100%);
            padding: 1rem 2rem;
            display: flex;
            justify-content: space-between;
            align-items: center;
            border-bottom: 1px solid #2a2a3e;
        }
        .logo {
            font-size: 1.5rem;
            font-weight: 700;
            color: #fff;
        }
        .logo span { color: #6366f1; }
        .user-info {
            display: flex;
            align-items: center;
            gap: 1rem;
        }
        .user-info span { opacity: 0.7; }
        .btn {
            background: #6366f1;
            color: #fff;
            border: none;
            padding: 0.6rem 1.2rem;
            border-radius: 6px;
            cursor: pointer;
            font-size: 0.9rem;
            transition: all 0.2s;
        }
        .btn:hover { background: #4f46e5; }
        .btn-outline {
            background: transparent;
            border: 1px solid #3a3a4e;
        }
        .btn-outline:hover { background: #1a1a2e; }
        
        /* Main */
        .main {
            max-width: 1200px;
            margin: 0 auto;
            padding: 2rem;
        }
        .page-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 2rem;
        }
        .page-header h1 { font-size: 1.75rem; }
        
        /* Stream Cards */
        .streams-grid {
            display: grid;
            gap: 1.5rem;
        }
        .stream-card {
            background: #12121a;
            border: 1px solid #2a2a3e;
            border-radius: 12px;
            display: grid;
            grid-template-columns: 160px 1fr auto;
            overflow: hidden;
            transition: all 0.2s;
        }
        .stream-card:hover { border-color: #4a4a5e; }
        .stream-card.inactive { opacity: 0.6; }
        .stream-thumb {
            position: relative;
            height: 120px;
            background: #1a1a2e;
        }
        .stream-thumb img {
            width: 100%;
            height: 100%;
            object-fit: cover;
        }
        .no-thumb {
            display: flex;
            align-items: center;
            justify-content: center;
            height: 100%;
            color: #4a4a5e;
            font-size: 2rem;
        }
        .stream-type {
            position: absolute;
            top: 8px;
            left: 8px;
            padding: 2px 8px;
            border-radius: 4px;
            font-size: 0.7rem;
            text-transform: uppercase;
            font-weight: 600;
        }
        .type-public { background: #10b981; color: #fff; }
        .type-lifestyle { background: #f59e0b; color: #000; }
        .type-nsfw { background: #ef4444; color: #fff; }
        
        .stream-info {
            padding: 1rem 1.5rem;
        }
        .stream-info h3 {
            font-size: 1.1rem;
            margin-bottom: 0.25rem;
        }
        .vibe-tag {
            color: #6366f1;
            font-size: 0.85rem;
        }
        .stream-stats {
            display: flex;
            gap: 1rem;
            margin-top: 0.75rem;
            font-size: 0.85rem;
            color: #888;
        }
        .stream-stats i { margin-right: 4px; }
        .stream-meta {
            margin-top: 0.5rem;
            display: flex;
            gap: 0.5rem;
        }
        .mode-badge {
            font-size: 0.75rem;
            padding: 2px 8px;
            border-radius: 4px;
            background: #1a1a2e;
        }
        .mode-badge.smartgrid { color: #10b981; }
        .mode-badge.override { color: #f59e0b; }
        .archive-on {
            font-size: 0.75rem;
            color: #888;
        }
        
        .stream-actions {
            display: flex;
            flex-direction: column;
            justify-content: center;
            gap: 0.5rem;
            padding: 1rem;
        }
        .stream-actions button,
        .stream-actions .action-btn {
            background: #1a1a2e;
            border: 1px solid #2a2a3e;
            color: #e0e0e0;
            width: 36px;
            height: 36px;
            border-radius: 6px;
            cursor: pointer;
            transition: all 0.2s;
            display: flex;
            align-items: center;
            justify-content: center;
            text-decoration: none;
        }
        .stream-actions button:hover,
        .stream-actions .action-btn:hover { background: #2a2a3e; }
        .stream-actions .action-btn:hover { background: #6366f1; border-color: #6366f1; }
        .stream-actions button.danger:hover { background: #7f1d1d; border-color: #ef4444; }
        
        .empty-state {
            text-align: center;
            padding: 4rem 2rem;
            color: #666;
        }
        .empty-state i { font-size: 3rem; margin-bottom: 1rem; }
        
        /* Modal */
        .modal {
            display: none;
            position: fixed;
            inset: 0;
            background: rgba(0,0,0,0.8);
            align-items: center;
            justify-content: center;
            z-index: 1000;
        }
        .modal.open { display: flex; }
        .modal-content {
            background: #12121a;
            border: 1px solid #2a2a3e;
            border-radius: 12px;
            width: 100%;
            max-width: 500px;
            max-height: 90vh;
            overflow-y: auto;
        }
        .modal-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 1.25rem 1.5rem;
            border-bottom: 1px solid #2a2a3e;
        }
        .modal-header h2 { font-size: 1.25rem; }
        .modal-close {
            background: none;
            border: none;
            color: #888;
            font-size: 1.5rem;
            cursor: pointer;
        }
        .modal-body { padding: 1.5rem; }
        
        /* Form */
        .form-group {
            margin-bottom: 1.25rem;
        }
        .form-group label {
            display: block;
            margin-bottom: 0.4rem;
            font-size: 0.9rem;
            color: #aaa;
        }
        .form-group input,
        .form-group select {
            width: 100%;
            padding: 0.7rem 1rem;
            background: #0a0a0f;
            border: 1px solid #2a2a3e;
            border-radius: 6px;
            color: #e0e0e0;
            font-size: 0.95rem;
        }
        .form-group input:focus,
        .form-group select:focus {
            outline: none;
            border-color: #6366f1;
        }
        .form-row {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 1rem;
        }
        .checkbox-group {
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }
        .checkbox-group input { width: auto; }
        
        .thumb-upload {
            display: flex;
            gap: 1rem;
            align-items: center;
        }
        .thumb-upload input[type="file"] { display: none; }
        .thumb-btn {
            background: #1a1a2e;
            border: 1px dashed #3a3a4e;
            padding: 0.7rem 1rem;
            border-radius: 6px;
            cursor: pointer;
            color: #888;
        }
        .thumb-btn:hover { border-color: #6366f1; color: #6366f1; }
        #thumbPreview {
            width: 60px;
            height: 60px;
            border-radius: 6px;
            overflow: hidden;
            background: #1a1a2e;
            display: flex;
            align-items: center;
            justify-content: center;
            color: #4a4a5e;
        }
        #thumbPreview img {
            width: 100%;
            height: 100%;
            object-fit: cover;
        }
        
        .form-actions {
            display: flex;
            gap: 1rem;
            margin-top: 1.5rem;
        }
        .form-actions .btn { flex: 1; }
        
        /* Toasts */
        #toasts {
            position: fixed;
            bottom: 2rem;
            right: 2rem;
            z-index: 2000;
        }
        .toast {
            background: #1a1a2e;
            border: 1px solid #2a2a3e;
            padding: 0.75rem 1.25rem;
            border-radius: 8px;
            margin-top: 0.5rem;
            display: flex;
            align-items: center;
            gap: 0.5rem;
            animation: slideIn 0.3s ease;
        }
        .toast-success { border-color: #10b981; }
        .toast-success i { color: #10b981; }
        .toast-error { border-color: #ef4444; }
        .toast-error i { color: #ef4444; }
        @keyframes slideIn {
            from { transform: translateX(100%); opacity: 0; }
            to { transform: translateX(0); opacity: 1; }
        }
        
        @media (max-width: 768px) {
            .stream-card { grid-template-columns: 1fr; }
            .stream-thumb { height: 160px; }
            .stream-actions { flex-direction: row; justify-content: flex-end; }
            .form-row { grid-template-columns: 1fr; }
        }
    </style>
</head>
<body>
    <header class="header">
        <div class="logo">Omni<span>Grid</span></div>
        <div class="user-info">
            <span><?= htmlspecialchars($user['display_name'] ?? $user['email']) ?></span>
            <a href="../" class="btn btn-outline"><i class="fa fa-globe"></i> View Site</a>
            <a href="../logout.php" class="btn btn-outline"><i class="fa fa-sign-out-alt"></i></a>
        </div>
    </header>
    
    <main class="main">
        <div class="page-header">
            <h1>Your Streams</h1>
            <button class="btn" onclick="Dashboard.openModal()">
                <i class="fa fa-plus"></i> Add Stream
            </button>
        </div>
        
        <div class="streams-grid" id="streamsList">
            <div class="empty-state">
                <i class="fa fa-spinner fa-spin"></i>
                <p>Loading streams...</p>
            </div>
        </div>
    </main>
    
    <!-- Add/Edit Modal -->
    <div class="modal" id="streamModal">
        <div class="modal-content">
            <div class="modal-header">
                <h2 id="formTitle">Add New Stream</h2>
                <button class="modal-close" onclick="Dashboard.closeModal()">&times;</button>
            </div>
            <div class="modal-body">
                <form id="addStreamForm">
                    <div class="form-group">
                        <label>Stream Title</label>
                        <input type="text" name="title" placeholder="My Awesome Stream" required>
                    </div>
                    
                    <div class="form-row">
                        <div class="form-group">
                            <label>Type</label>
                            <select name="type">
                                <option value="public">Public</option>
                                <option value="lifestyle">Home & Lifestyle</option>
                                <option value="nsfw">NSFW (18+)</option>
                            </select>
                        </div>
                        <div class="form-group">
                            <label>Vibe Tag</label>
                            <input type="text" name="vibe_tag" placeholder="chill · lofi">
                        </div>
                    </div>
                    
                    <div class="form-group">
                        <label>Revenue Mode</label>
                        <select name="revenue_mode" id="revenueMode">
                            <option value="smartgrid">smartGrid (adaptive)</option>
                            <option value="override">Fixed Rate</option>
                        </select>
                    </div>
                    
                    <div class="form-group" id="multiplierGroup">
                        <label>smartGrid Multiplier</label>
                        <input type="number" name="smartgrid_multiplier" value="1.0" min="0.5" max="5" step="0.1">
                    </div>
                    
                    <div class="form-group" id="priceGroup" style="display:none">
                        <label>Price per Minute ($)</label>
                        <input type="number" name="price_per_minute" value="0.01" min="0" step="0.001">
                    </div>
                    
                    <div class="form-row">
                        <div class="form-group">
                            <label>Latitude</label>
                            <input type="text" name="geo_lat" id="geo_lat" placeholder="34.0522">
                        </div>
                        <div class="form-group">
                            <label>Longitude</label>
                            <input type="text" name="geo_lng" id="geo_lng" placeholder="-118.2437">
                        </div>
                    </div>
                    <button type="button" class="btn btn-outline" id="getLocation" style="margin-bottom:1rem">
                        <i class="fa fa-location-crosshairs"></i> Use My Location
                    </button>
                    
                    <div class="form-group">
                        <label>Thumbnail</label>
                        <div class="thumb-upload">
                            <label class="thumb-btn">
                                <i class="fa fa-upload"></i> Upload Image
                                <input type="file" id="thumbFile" accept="image/*">
                            </label>
                            <div id="thumbPreview"></div>
                        </div>
                        <input type="hidden" name="thumb_url" id="thumb_url">
                    </div>
                    
                    <div class="form-group">
                        <label class="checkbox-group">
                            <input type="checkbox" name="archive_enabled" checked>
                            Enable archive (save VODs)
                        </label>
                    </div>
                    
                    <div class="form-actions">
                        <button type="button" class="btn btn-outline" onclick="Dashboard.closeModal()">Cancel</button>
                        <button type="submit" class="btn" id="submitBtn">Create Stream</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
    
    <div id="toasts"></div>
    
    <script src="dashboard.js"></script>
</body>
</html>
