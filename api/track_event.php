<?php
// api/track_event.php - log front-end events, run lightweight threat analysis
session_start();
require_once __DIR__ . '/../db.php';

header('Content-Type: application/json');

$raw = file_get_contents('php://input');
$data = json_decode($raw, true);
if (!is_array($data)) {
    echo json_encode(['ok' => false, 'error' => 'Invalid JSON']);
    exit;
}

$type = substr($data['type'] ?? 'unknown', 0, 64);
$meta = $data['meta'] ?? [];
$href = $data['href'] ?? null;

if ($href) {
    $meta['_href'] = $href;
}

$userId = isset($_SESSION['user_id']) ? (int)$_SESSION['user_id'] : null;
$ip = $_SERVER['REMOTE_ADDR'] ?? '';
$ua = $_SERVER['HTTP_USER_AGENT'] ?? '';
$fingerprint = $data['fingerprint'] ?? null; // optional if you send from JS later

$metaJson = json_encode($meta);

try {
    // Insert event
    $stmt = $pdo->prepare("
        INSERT INTO user_events (user_id, fingerprint, ip, user_agent, event_type, meta_json, created_at)
        VALUES (:user_id, :fp, :ip, :ua, :type, :meta, NOW())
    ");
    $stmt->execute([
        ':user_id' => $userId ?: null,
        ':fp'      => $fingerprint,
        ':ip'      => $ip,
        ':ua'      => $ua,
        ':type'    => $type,
        ':meta'    => $metaJson,
    ]);

    // Simple threat scoring: extremely aggressive hitting gets auto-banned
    $scoreDelta = 0;

    // 1) Crazy event rate by IP in last 60 seconds
    $recent = $pdo->prepare("
        SELECT COUNT(*) FROM user_events
        WHERE ip = :ip AND created_at > (NOW() - INTERVAL 1 MINUTE)
    ");
    $recent->execute([':ip' => $ip]);
    $recentCount = (int)$recent->fetchColumn();

    if ($recentCount > 80)  $scoreDelta += 10;
    if ($recentCount > 150) $scoreDelta += 25;

    // 2) Repeated access to sensitive endpoints (stub for now: any type starting with 'nsfw_')
    if (strpos($type, 'nsfw_') === 0) {
        $scoreDelta += 5;
    }

    if ($scoreDelta > 0) {
        // Upsert threat_scores row
        $sel = $pdo->prepare("SELECT id, score FROM threat_scores WHERE ip = :ip LIMIT 1");
        $sel->execute([':ip' => $ip]);
        $row = $sel->fetch();

        if ($row) {
            $newScore = (int)$row['score'] + $scoreDelta;
            $upd = $pdo->prepare("
                UPDATE threat_scores
                SET score = :score, last_event_at = NOW(), reason = CONCAT(COALESCE(reason,''),' | auto+",$scoreDelta,"')
                WHERE id = :id
            ");
            $upd->execute([
                ':score' => $newScore,
                ':id'    => $row['id'],
            ]);
        } else {
            $newScore = $scoreDelta;
            $ins = $pdo->prepare("
                INSERT INTO threat_scores (user_id, fingerprint, ip, score, reason, last_event_at)
                VALUES (:user_id, :fp, :ip, :score, :reason, NOW())
            ");
            $ins->execute([
                ':user_id' => $userId ?: null,
                ':fp'      => $fingerprint,
                ':ip'      => $ip,
                ':score'   => $newScore,
                ':reason'  => 'auto+' . $scoreDelta,
            ]);
        }

        //  Auto-ban if score is ridiculous
        if ($newScore >= 60) {
            // Check if already banned
            $chk = $pdo->prepare("SELECT id FROM bans WHERE ip = :ip LIMIT 1");
            $chk->execute([':ip' => $ip]);
            if (!$chk->fetchColumn()) {
                $ban = $pdo->prepare("INSERT INTO bans (ip, reason) VALUES (:ip, :reason)");
                $ban->execute([
                    ':ip' => $ip,
                    ':reason' => 'Auto-ban from threat scoring (score=' . $newScore . ')',
                ]);
            }
        }
    }

    echo json_encode(['ok' => true, 'score_delta' => $scoreDelta, 'recent' => $recentCount ?? 0]);
} catch (Throwable $e) {
    // Fail silently-ish, but don’t break the front-end
    echo json_encode(['ok' => false, 'error' => 'DB error']);
}