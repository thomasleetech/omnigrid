<?php
session_start();
header('Content-Type: application/json');

// Auth check
if (empty($_SESSION['user_id'])) {
    http_response_code(401);
    echo json_encode(['success' => false, 'error' => 'Not logged in']);
    exit;
}

require_once '../includes/db.php';

$user_id = (int)$_SESSION['user_id'];
$data = json_decode(file_get_contents('php://input'), true);

// Validate required fields
if (empty($data['title'])) {
    echo json_encode(['success' => false, 'error' => 'Title is required']);
    exit;
}

// Sanitize inputs
$title = trim($data['title']);
$type = in_array($data['type'] ?? '', ['public', 'lifestyle', 'nsfw']) ? $data['type'] : 'public';
$vibe_tag = trim($data['vibe_tag'] ?? '');
$price_per_minute = max(0, floatval($data['price_per_minute'] ?? 0.01));
$revenue_mode = ($data['revenue_mode'] ?? 'smartgrid') === 'override' ? 'override' : 'smartgrid';
$smartgrid_multiplier = max(0.5, min(5.0, floatval($data['smartgrid_multiplier'] ?? 1.0)));
$geo_lat = isset($data['geo_lat']) && $data['geo_lat'] !== '' ? floatval($data['geo_lat']) : null;
$geo_lng = isset($data['geo_lng']) && $data['geo_lng'] !== '' ? floatval($data['geo_lng']) : null;
$archive_enabled = !empty($data['archive_enabled']) ? 1 : 0;
$thumb_url = trim($data['thumb_url'] ?? '');

try {
    $pdo->beginTransaction();
    
    // Insert stream
    $stmt = $pdo->prepare("
        INSERT INTO streams (user_id, title, type, vibe_tag, price_per_minute, revenue_mode, 
                            smartgrid_multiplier, geo_lat, geo_lng, archive_enabled, is_active, thumb_url)
        VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 1, ?)
    ");
    $stmt->execute([
        $user_id, $title, $type, $vibe_tag, $price_per_minute, $revenue_mode,
        $smartgrid_multiplier, $geo_lat, $geo_lng, $archive_enabled, $thumb_url
    ]);
    
    $stream_id = $pdo->lastInsertId();
    
    // Create initial metrics record
    $stmt = $pdo->prepare("INSERT INTO stream_metrics (stream_id) VALUES (?)");
    $stmt->execute([$stream_id]);
    
    $pdo->commit();
    
    echo json_encode([
        'success' => true,
        'stream_id' => $stream_id,
        'message' => 'Stream created successfully'
    ]);
    
} catch (PDOException $e) {
    $pdo->rollBack();
    http_response_code(500);
    echo json_encode(['success' => false, 'error' => 'Database error']);
}
