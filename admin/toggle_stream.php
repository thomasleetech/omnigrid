<?php
// toggle_stream.php
session_start();
header('Content-Type: application/json');
require_once '../includes/db.php';
$stmt = $pdo->prepare("SELECT role FROM users WHERE id = ?");
$stmt->execute([$_SESSION['user_id'] ?? 0]);
$u = $stmt->fetch();
if (!$u || $u['role'] !== 'admin') { echo json_encode(['success' => false]); exit; }

$data = json_decode(file_get_contents('php://input'), true);
$id = (int)($data['id'] ?? 0);
$active = (int)($data['is_active'] ?? 0);
if ($id) {
    $pdo->prepare("UPDATE streams SET is_active = ? WHERE id = ?")->execute([$active, $id]);
}
echo json_encode(['success' => true]);
