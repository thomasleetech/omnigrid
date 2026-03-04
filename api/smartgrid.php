<?php
session_start();
header('Content-Type: application/json');

require_once '../includes/db.php';
require_once '../includes/SmartGrid.php';

$smartGrid = new SmartGrid($pdo);
$action = $_GET['action'] ?? $_POST['action'] ?? 'rate';

switch ($action) {
    case 'rate':
        $stream_id = (int)($_GET['stream_id'] ?? 0);
        if (!$stream_id) {
            echo json_encode(['error' => 'Stream ID required']);
            exit;
        }
        echo json_encode($smartGrid->calculateRate($stream_id));
        break;

    case 'all_rates':
        if (empty($_SESSION['user_id'])) {
            http_response_code(401);
            echo json_encode(['error' => 'Not logged in']);
            exit;
        }
        $stmt = $pdo->prepare("SELECT role FROM users WHERE id = ?");
        $stmt->execute([$_SESSION['user_id']]);
        $user = $stmt->fetch();
        if (!$user || ($user['role'] ?? '') !== 'admin') {
            http_response_code(403);
            echo json_encode(['error' => 'Admin only']);
            exit;
        }
        echo json_encode(['streams' => $smartGrid->getAllStreamsWithEarnings()]);
        break;

    case 'my_rates':
        if (empty($_SESSION['user_id'])) {
            http_response_code(401);
            echo json_encode(['error' => 'Not logged in']);
            exit;
        }
        $stmt = $pdo->prepare("SELECT id FROM streams WHERE user_id = ?");
        $stmt->execute([$_SESSION['user_id']]);
        $streams = $stmt->fetchAll();

        $rates = [];
        foreach ($streams as $s) {
            $rates[] = $smartGrid->calculateRate($s['id']);
        }
        echo json_encode(['streams' => $rates]);
        break;

    case 'projections':
        $stream_id = (int)($_GET['stream_id'] ?? 0);
        if (!$stream_id) {
            echo json_encode(['error' => 'Stream ID required']);
            exit;
        }
        $minutes = (int)($_GET['minutes'] ?? 120);
        echo json_encode($smartGrid->getProjections($stream_id, $minutes));
        break;

    case 'admin_adjust':
        if (empty($_SESSION['user_id'])) {
            http_response_code(401);
            echo json_encode(['error' => 'Not logged in']);
            exit;
        }
        $stmt = $pdo->prepare("SELECT role FROM users WHERE id = ?");
        $stmt->execute([$_SESSION['user_id']]);
        $user = $stmt->fetch();
        if (!$user || ($user['role'] ?? '') !== 'admin') {
            http_response_code(403);
            echo json_encode(['error' => 'Admin only']);
            exit;
        }
        $data = json_decode(file_get_contents('php://input'), true);
        $multiplier = max(0.1, min(5.0, floatval($data['multiplier'] ?? 1.0)));
        $smartGrid->setPayoutMultiplier($multiplier);
        echo json_encode(['success' => true, 'multiplier' => $multiplier]);
        break;

    case 'platform_stats':
        echo json_encode($smartGrid->getPlatformStats());
        break;

    default:
        echo json_encode(['error' => 'Unknown action']);
}
