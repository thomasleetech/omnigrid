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
$data = json_decode(file_get_contents('php://input'), true);
$stream_id = (int)($data['stream_id'] ?? 0);
$status = $data['status'] ?? '';

if (!$stream_id) {
    echo json_encode(['error' => 'Missing stream_id']);
    exit;
}

// Verify ownership
$stmt = $pdo->prepare("SELECT id FROM streams WHERE id = ? AND user_id = ?");
$stmt->execute([$stream_id, $user_id]);
if (!$stmt->fetch()) {
    echo json_encode(['error' => 'Not authorized']);
    exit;
}

// Check if is_live column exists
$stmt = $pdo->query("SHOW COLUMNS FROM streams LIKE 'is_live'");
if ($stmt->fetch()) {
    $is_live = ($status === 'live') ? 1 : 0;
    $stmt = $pdo->prepare("UPDATE streams SET is_live = ? WHERE id = ?");
    $stmt->execute([$is_live, $stream_id]);
}

echo json_encode(['success' => true, 'status' => $status]);
