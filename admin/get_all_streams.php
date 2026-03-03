<?php
session_start();
header('Content-Type: application/json');
require_once '../includes/db.php';
$stmt = $pdo->prepare("SELECT role FROM users WHERE id = ?");
$stmt->execute([$_SESSION['user_id'] ?? 0]);
$u = $stmt->fetch();
if (!$u || $u['role'] !== 'admin') { echo json_encode(['streams' => []]); exit; }

$stmt = $pdo->query("
    SELECT s.*, u.display_name as creator_name, u.email as creator_email,
           m.views, m.tips_cents, m.subs_count
    FROM streams s
    JOIN users u ON s.user_id = u.id
    LEFT JOIN stream_metrics m ON s.id = m.stream_id
    ORDER BY s.created_at DESC
");
$streams = $stmt->fetchAll();
foreach ($streams as &$s) {
    if (!$s['creator_name']) $s['creator_name'] = explode('@', $s['creator_email'])[0];
}
echo json_encode(['streams' => $streams]);
