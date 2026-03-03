<?php
header('Access-Control-Allow-Origin: *');

$stream_id = (int)($_GET['id'] ?? 0);
if (!$stream_id) {
    http_response_code(400);
    exit;
}

$chunk_dir = "stream_data/{$stream_id}/";

// Get latest chunk
$files = glob($chunk_dir . "chunk_*.webm");
if (empty($files)) {
    http_response_code(404);
    header('Content-Type: application/json');
    echo json_encode(['error' => 'No stream data', 'offline' => true]);
    exit;
}

// Sort by timestamp (newest first)
usort($files, fn($a, $b) => filemtime($b) - filemtime($a));

// Check if stream is stale (no new chunks in 10 seconds)
if (time() - filemtime($files[0]) > 10) {
    http_response_code(404);
    header('Content-Type: application/json');
    echo json_encode(['error' => 'Stream offline', 'offline' => true]);
    exit;
}

// Return the latest chunk
$latest = $files[0];
header('Content-Type: video/webm');
header('Content-Length: ' . filesize($latest));
header('Cache-Control: no-cache');
readfile($latest);
