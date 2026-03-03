<?php
// tracker.php – log visits
require_once __DIR__ . '/db.php';

try {
    $pdo = seb_db();

    $stmt = $pdo->prepare("
        INSERT INTO seb_visitors (ip_address, user_agent, path, referrer)
        VALUES (:ip, :ua, :path, :ref)
    ");

    $stmt->execute([
        ':ip'   => $_SERVER['REMOTE_ADDR'] ?? '',
        ':ua'   => $_SERVER['HTTP_USER_AGENT'] ?? '',
        ':path' => $_SERVER['REQUEST_URI'] ?? '',
        ':ref'  => $_SERVER['HTTP_REFERER'] ?? '',
    ]);
} catch (Exception $e) {
    // Fail silently – tracking must never break the page
}