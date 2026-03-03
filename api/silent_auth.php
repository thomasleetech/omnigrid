<?php
// api/silent_auth.php - bind / login via device fingerprint
session_start();
require_once __DIR__ . '/../db.php';

header('Content-Type: application/json');

$raw = file_get_contents('php://input');
$data = json_decode($raw, true);
if (!is_array($data)) {
    echo json_encode(['ok' => false, 'error' => 'Invalid JSON']);
    exit;
}

$mode = $data['mode'] ?? 'unknown';
$fingerprint = $data['fingerprint'] ?? null;

if (!$fingerprint) {
    echo json_encode(['ok' => false, 'message' => 'No fingerprint provided.']);
    exit;
}

$userId = isset($_SESSION['user_id']) ? (int)$_SESSION['user_id'] : null;
$ip = $_SERVER['REMOTE_ADDR'] ?? '';

try {
    if ($mode === 'login') {
        // Attempt device-only login
        $stmt = $pdo->prepare("
            SELECT user_id, trust_level FROM user_device_fingerprints
            WHERE fingerprint = :fp
            ORDER BY trust_level DESC, last_seen_at DESC
            LIMIT 1
        ");
        $stmt->execute([':fp' => $fingerprint]);
        $row = $stmt->fetch();

        if ($row && (int)$row['trust_level'] >= 2) {
            $_SESSION['user_id'] = (int)$row['user_id'];

            // Update last seen
            $upd = $pdo->prepare("
                UPDATE user_device_fingerprints
                SET last_seen_ip = :ip, last_seen_at = NOW()
                WHERE user_id = :uid AND fingerprint = :fp
            ");
            $upd->execute([
                ':ip'  => $ip,
                ':uid' => (int)$row['user_id'],
                ':fp'  => $fingerprint,
            ]);

            echo json_encode(['ok' => true, 'logged_in' => true, 'message' => 'Logged in with this device.']);
            exit;
        }

        echo json_encode(['ok' => true, 'logged_in' => false, 'message' => 'Device not trusted enough yet. Use email/password.']);
        exit;
    }

    // For register/bind modes we only bind if a user is known
    if (!$userId) {
        echo json_encode(['ok' => false, 'message' => 'No logged-in user to bind this fingerprint to.']);
        exit;
    }

    // Bind or bump trust
    $sel = $pdo->prepare("
        SELECT id, trust_level FROM user_device_fingerprints
        WHERE user_id = :uid AND fingerprint = :fp
        LIMIT 1
    ");
    $sel->execute([
        ':uid' => $userId,
        ':fp'  => $fingerprint,
    ]);
    $row = $sel->fetch();

    if ($row) {
        $newTrust = (int)$row['trust_level'] + 1;
        $upd = $pdo->prepare("
            UPDATE user_device_fingerprints
            SET trust_level = :trust, last_seen_ip = :ip, last_seen_at = NOW()
            WHERE id = :id
        ");
        $upd->execute([
            ':trust' => $newTrust,
            ':ip'    => $ip,
            ':id'    => $row['id'],
        ]);
        echo json_encode(['ok' => true, 'message' => 'Fingerprint trust increased.', 'trust_level' => $newTrust]);
    } else {
        $ins = $pdo->prepare("
            INSERT INTO user_device_fingerprints (user_id, fingerprint, last_seen_ip, trust_level)
            VALUES (:uid, :fp, :ip, 1)
        ");
        $ins->execute([
            ':uid' => $userId,
            ':fp'  => $fingerprint,
            ':ip'  => $ip,
        ]);
        echo json_encode(['ok' => true, 'message' => 'Fingerprint bound to this account.', 'trust_level' => 1]);
    }
} catch (Throwable $e) {
    echo json_encode(['ok' => false, 'error' => 'DB error']);
}