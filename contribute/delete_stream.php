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

try {
    // Delete only if owned by user (CASCADE will handle metrics/archives)
    $stmt = $pdo->prepare("DELETE FROM streams WHERE id = ? AND user_id = ?");
    $stmt->execute([$stream_id, $user_id]);
    
    if ($stmt->rowCount() > 0) {
        echo json_encode(['success' => true, 'message' => 'Stream deleted']);
    } else {
        http_response_code(404);
        echo json_encode(['success' => false, 'error' => 'Stream not found or not owned']);
    }
    
} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'error' => 'Database error']);
}
