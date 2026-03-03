<?php
// save_config.php
session_start();
header('Content-Type: application/json');
require_once '../includes/db.php';
$stmt = $pdo->prepare("SELECT role FROM users WHERE id = ?");
$stmt->execute([$_SESSION['user_id'] ?? 0]);
$u = $stmt->fetch();
if (!$u || $u['role'] !== 'admin') { echo json_encode(['success' => false]); exit; }

$data = json_decode(file_get_contents('php://input'), true);
$allowed = ['rate_mode', 'base_ppm', 'smartgrid_aggressiveness', 'hero_mode', 'hero_loop_url'];
$sets = [];
$params = [];
foreach ($allowed as $k) {
    if (isset($data[$k])) {
        $sets[] = "$k = ?";
        $params[] = $data[$k];
    }
}
if ($sets) {
    $pdo->prepare("UPDATE site_config SET " . implode(', ', $sets) . " WHERE id = 1")->execute($params);
}
echo json_encode(['success' => true]);
