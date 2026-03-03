<?php
session_start();
header('Content-Type: application/json');

if (empty($_SESSION['user_id'])) {
    http_response_code(401);
    echo json_encode(['success' => false, 'error' => 'Not logged in']);
    exit;
}

require_once '../includes/db.php';

$user_id = (int)$_SESSION['user_id'];
$data = json_decode(file_get_contents('php://input'), true);

if (empty($data['id'])) {
    echo json_encode(['success' => false, 'error' => 'Stream ID required']);
    exit;
}

$stream_id = (int)$data['id'];

// Verify ownership
$stmt = $pdo->prepare("SELECT id FROM streams WHERE id = ? AND user_id = ?");
$stmt->execute([$stream_id, $user_id]);
if (!$stmt->fetch()) {
    http_response_code(403);
    echo json_encode(['success' => false, 'error' => 'Stream not found or not owned']);
    exit;
}

// Build update query dynamically
$allowed = ['title', 'type', 'vibe_tag', 'price_per_minute', 'revenue_mode', 
            'smartgrid_multiplier', 'geo_lat', 'geo_lng', 'archive_enabled', 'is_active', 'thumb_url'];
$updates = [];
$params = [];

foreach ($allowed as $field) {
    if (isset($data[$field])) {
        $updates[] = "$field = ?";
        $value = $data[$field];
        
        // Type validation
        if ($field === 'type' && !in_array($value, ['public', 'lifestyle', 'nsfw'])) continue;
        if ($field === 'revenue_mode' && !in_array($value, ['smartgrid', 'override'])) continue;
        if (in_array($field, ['archive_enabled', 'is_active'])) $value = $value ? 1 : 0;
        
        $params[] = $value;
    }
}

if (empty($updates)) {
    echo json_encode(['success' => false, 'error' => 'No fields to update']);
    exit;
}

$params[] = $stream_id;

try {
    $sql = "UPDATE streams SET " . implode(', ', $updates) . " WHERE id = ?";
    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    
    echo json_encode(['success' => true, 'message' => 'Stream updated']);
    
} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'error' => 'Database error']);
}
