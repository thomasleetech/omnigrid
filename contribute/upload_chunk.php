<?php
session_start();
header('Content-Type: application/json');

if (empty($_SESSION['user_id'])) {
    http_response_code(401);
    echo json_encode(['error' => 'Not logged in']);
    exit;
}

require_once '../includes/db.php';

$user_id = (int)$_SESSION['user_id'];
$stream_id = (int)($_POST['stream_id'] ?? 0);

if (!$stream_id || empty($_FILES['chunk'])) {
    echo json_encode(['error' => 'Missing data']);
    exit;
}

// Verify ownership
$stmt = $pdo->prepare("SELECT id FROM streams WHERE id = ? AND user_id = ?");
$stmt->execute([$stream_id, $user_id]);
if (!$stmt->fetch()) {
    echo json_encode(['error' => 'Not authorized']);
    exit;
}

// Create chunks directory
$chunk_dir = "../stream_data/{$stream_id}/";
if (!is_dir($chunk_dir)) {
    mkdir($chunk_dir, 0755, true);
}

// Save chunk
$timestamp = (int)($_POST['timestamp'] ?? time());
$chunk_file = $chunk_dir . "chunk_{$timestamp}.webm";
move_uploaded_file($_FILES['chunk']['tmp_name'], $chunk_file);

// Clean old chunks (keep last 30 seconds = ~15 chunks at 2s each)
$files = glob($chunk_dir . "chunk_*.webm");
if (count($files) > 15) {
    usort($files, fn($a, $b) => filemtime($a) - filemtime($b));
    for ($i = 0; $i < count($files) - 15; $i++) {
        unlink($files[$i]);
    }
}

// Get viewer count from metrics
$stmt = $pdo->prepare("SELECT views FROM stream_metrics WHERE stream_id = ?");
$stmt->execute([$stream_id]);
$metrics = $stmt->fetch();

echo json_encode([
    'success' => true,
    'viewers' => $metrics['views'] ?? 0
]);
