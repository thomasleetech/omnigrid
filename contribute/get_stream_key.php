<?php
session_start();
header('Content-Type: application/json');

if (empty($_SESSION['user_id'])) {
    http_response_code(401);
    echo json_encode(['success' => false, 'error' => 'Not logged in']);
    exit;
}

try {
    require_once '../includes/db.php';
} catch (Exception $e) {
    echo json_encode(['success' => false, 'error' => 'Database connection failed']);
    exit;
}

$user_id = (int)$_SESSION['user_id'];
$data = json_decode(file_get_contents('php://input'), true);
$stream_id = (int)($data['stream_id'] ?? 0);

if (!$stream_id) {
    echo json_encode(['success' => false, 'error' => 'Stream ID required']);
    exit;
}

try {
    // Check if streaming columns exist
    $stmt = $pdo->query("SHOW COLUMNS FROM streams LIKE 'stream_key'");
    if (!$stmt->fetch()) {
        echo json_encode(['success' => false, 'error' => 'Run SQL migration first', 'needs_migration' => true]);
        exit;
    }
    
    // Verify ownership
    $stmt = $pdo->prepare("SELECT * FROM streams WHERE id = ? AND user_id = ?");
    $stmt->execute([$stream_id, $user_id]);
    $stream = $stmt->fetch();
    
    if (!$stream) {
        echo json_encode(['success' => false, 'error' => 'Stream not found']);
        exit;
    }
    
    // If already has credentials, return them
    if (!empty($stream['stream_key'])) {
        echo json_encode([
            'success' => true,
            'stream_key' => $stream['stream_key'],
            'rtmp_url' => $stream['ingest_url'] ?: 'rtmp://your-server.com/live',
            'playback_url' => $stream['playback_url'] ?: ''
        ]);
        exit;
    }
    
    // Generate new stream key
    $stream_key = 'og_' . bin2hex(random_bytes(16));
    $rtmp_server = 'rtmp://your-server.com/live';
    
    $stmt = $pdo->prepare("UPDATE streams SET stream_key = ?, ingest_url = ? WHERE id = ?");
    $stmt->execute([$stream_key, $rtmp_server . '/' . $stream_key, $stream_id]);
    
    echo json_encode([
        'success' => true,
        'stream_key' => $stream_key,
        'rtmp_url' => $rtmp_server,
        'playback_url' => ''
    ]);
    
} catch (PDOException $e) {
    echo json_encode(['success' => false, 'error' => 'DB error: ' . $e->getMessage()]);
}
