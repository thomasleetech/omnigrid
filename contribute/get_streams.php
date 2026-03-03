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

try {
    $stmt = $pdo->prepare("
        SELECT s.*, 
               m.tips_cents, m.subs_count, m.views, m.avg_watch_seconds
        FROM streams s
        LEFT JOIN stream_metrics m ON s.id = m.stream_id
        WHERE s.user_id = ?
        ORDER BY s.created_at DESC
    ");
    $stmt->execute([$user_id]);
    $streams = $stmt->fetchAll();
    
    // Calculate earnings for each stream
    foreach ($streams as &$stream) {
        $stream['total_earnings_cents'] = ($stream['tips_cents'] ?? 0) + 
            (($stream['subs_count'] ?? 0) * 499); // Assuming $4.99 sub
    }
    
    echo json_encode(['success' => true, 'streams' => $streams]);
    
} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'error' => 'Database error']);
}
