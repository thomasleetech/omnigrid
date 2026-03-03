<?php
session_start();
header('Content-Type: application/json');
require_once '../includes/db.php';
$stmt = $pdo->prepare("SELECT role FROM users WHERE id = ?");
$stmt->execute([$_SESSION['user_id'] ?? 0]);
$u = $stmt->fetch();
if (!$u || $u['role'] !== 'admin') { echo json_encode(['users' => []]); exit; }

$stmt = $pdo->query("
    SELECT u.*, 
           (SELECT COUNT(*) FROM streams WHERE user_id = u.id) as stream_count,
           (SELECT COALESCE(SUM(m.tips_cents), 0) FROM streams s 
            LEFT JOIN stream_metrics m ON s.id = m.stream_id 
            WHERE s.user_id = u.id) as total_earnings
    FROM users u ORDER BY u.created_at DESC
");
echo json_encode(['users' => $stmt->fetchAll()]);
