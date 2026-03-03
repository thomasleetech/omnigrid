<?php
session_start();
header('Content-Type: application/json');

require_once '../includes/db.php';
require_once '../includes/SmartGrid.php';

$smartGrid = new SmartGrid($pdo);
$action = $_GET['action'] ?? $_POST['action'] ?? 'rate';

switch ($action) {
    case 'rate':
        // Get rate for specific stream
        $stream_id = (int)($_GET['stream_id'] ?? 0);
        if (!$stream_id) {
            echo json_encode(['error' => 'Stream ID required']);
            exit;
        }
        echo json_encode($smartGrid->getDetailedBreakdown($stream_id));
        break;
        
    case 'all_rates':
        // Get all stream rates (for admin)
        echo json_encode(['streams' => $smartGrid->getAllStreamRates()]);
        break;
        
    case 'my_rates':
        // Get logged-in user's stream rates
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
            $rates[] = $smartGrid->getDetailedBreakdown($s['id']);
        }
        echo json_encode(['streams' => $rates]);
        break;
        
    case 'admin_adjust':
        // Admin only: adjust global rate
        if (empty($_SESSION['user_id'])) {
            http_response_code(401);
            echo json_encode(['error' => 'Not logged in']);
            exit;
        }
        
        // Check if admin
        $stmt = $pdo->prepare("SELECT role FROM users WHERE id = ?");
        $stmt->execute([$_SESSION['user_id']]);
        $user = $stmt->fetch();
        
        if ($user['role'] !== 'admin') {
            http_response_code(403);
            echo json_encode(['error' => 'Admin only']);
            exit;
        }
        
        $data = json_decode(file_get_contents('php://input'), true);
        $adjustment = $data['adjustment'] ?? 0;
        $newAdj = $smartGrid->setAdminAdjustment($adjustment);
        
        echo json_encode(['success' => true, 'adjustment' => $newAdj]);
        break;
        
    case 'set_aggressiveness':
        // Admin only
        if (empty($_SESSION['user_id'])) {
            http_response_code(401);
            echo json_encode(['error' => 'Not logged in']);
            exit;
        }
        
        $stmt = $pdo->prepare("SELECT role FROM users WHERE id = ?");
        $stmt->execute([$_SESSION['user_id']]);
        $user = $stmt->fetch();
        
        if ($user['role'] !== 'admin') {
            http_response_code(403);
            echo json_encode(['error' => 'Admin only']);
            exit;
        }
        
        $data = json_decode(file_get_contents('php://input'), true);
        $level = $data['level'] ?? 1.0;
        $newLevel = $smartGrid->setAggressiveness($level);
        
        echo json_encode(['success' => true, 'aggressiveness' => $newLevel]);
        break;
        
    default:
        echo json_encode(['error' => 'Unknown action']);
}
