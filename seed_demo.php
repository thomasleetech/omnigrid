<?php
// seed_demo.php - one-time demo seeder for OmniGrid
// Location: /omnigrid/seed_demo.php
// After running successfully, you can delete this file.

session_start();
require_once __DIR__ . '/db.php';

try {
    // --- Create tables if they don't exist ---

    // users
    $pdo->exec("
        CREATE TABLE IF NOT EXISTS users (
            id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
            email VARCHAR(255) NOT NULL UNIQUE,
            password_hash VARCHAR(255) NOT NULL,
            display_name VARCHAR(100) DEFAULT NULL,
            role ENUM('creator','admin','viewer') NOT NULL DEFAULT 'creator',
            is_banned TINYINT(1) NOT NULL DEFAULT 0,
            created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
    ");

    // bans (used by index & contribute)
    $pdo->exec("
        CREATE TABLE IF NOT EXISTS bans (
            id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
            ip VARCHAR(64) NOT NULL,
            reason VARCHAR(255) DEFAULT NULL,
            created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
    ");

    // site_config (global settings, including smartGrid toggle)
    $pdo->exec("
        CREATE TABLE IF NOT EXISTS site_config (
            id TINYINT UNSIGNED NOT NULL PRIMARY KEY,
            rate_mode ENUM('smartgrid','override') NOT NULL DEFAULT 'smartgrid',
            base_ppm DECIMAL(10,6) NOT NULL DEFAULT 0.000500,
            smartgrid_aggressiveness DECIMAL(5,2) NOT NULL DEFAULT 1.00
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
    ");

    // streams (creator feeds)
    $pdo->exec("
        CREATE TABLE IF NOT EXISTS streams (
            id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
            user_id INT UNSIGNED NOT NULL,
            title VARCHAR(255) DEFAULT NULL,
            type ENUM('public','lifestyle','nsfw') NOT NULL DEFAULT 'public',
            vibe_tag VARCHAR(64) DEFAULT NULL,
            price_per_minute DECIMAL(10,6) NOT NULL DEFAULT 0.010000,
            revenue_mode ENUM('smartgrid','override') NOT NULL DEFAULT 'smartgrid',
            smartgrid_multiplier DECIMAL(5,2) NOT NULL DEFAULT 1.00,
            geo_lat DECIMAL(10,6) DEFAULT NULL,
            geo_lng DECIMAL(10,6) DEFAULT NULL,
            archive_enabled TINYINT(1) NOT NULL DEFAULT 1,
            is_active TINYINT(1) NOT NULL DEFAULT 1,
            thumb_url VARCHAR(255) DEFAULT NULL,
            created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
            CONSTRAINT fk_streams_user FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
    ");

    // stream_metrics (aggregated metrics per stream)
    $pdo->exec("
        CREATE TABLE IF NOT EXISTS stream_metrics (
            id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
            stream_id INT UNSIGNED NOT NULL,
            tips_cents INT UNSIGNED NOT NULL DEFAULT 0,
            subs_count INT UNSIGNED NOT NULL DEFAULT 0,
            views INT UNSIGNED NOT NULL DEFAULT 0,
            avg_watch_seconds INT UNSIGNED NOT NULL DEFAULT 0,
            created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
            CONSTRAINT fk_metrics_stream FOREIGN KEY (stream_id) REFERENCES streams(id) ON DELETE CASCADE
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
    ");

    // --- Site config seed ---
    $stmt = $pdo->query("SELECT COUNT(*) FROM site_config WHERE id = 1");
    $hasConfig = (bool)$stmt->fetchColumn();
    if (!$hasConfig) {
        $pdo->prepare("
            INSERT INTO site_config (id, rate_mode, base_ppm, smartgrid_aggressiveness)
            VALUES (1, 'smartgrid', 0.000500, 1.50)
        ")->execute();
    }

    // --- Demo creator user seed ---
    $email = 'creator@omnigrid.test';
    $stmt = $pdo->prepare("SELECT id FROM users WHERE email = ?");
    $stmt->execute([$email]);
    $creatorId = $stmt->fetchColumn();

    if (!$creatorId) {
        $passwordHash = password_hash('demo1234', PASSWORD_DEFAULT);
        $insertUser = $pdo->prepare("
            INSERT INTO users (email, password_hash, display_name, role)
            VALUES (:email, :hash, :name, 'creator')
        ");
        $insertUser->execute([
            ':email' => $email,
            ':hash'  => $passwordHash,
            ':name'  => 'Demo Creator'
        ]);
        $creatorId = $pdo->lastInsertId();
    }

    // --- Demo streams seed ---
    $checkStream = $pdo->prepare("SELECT COUNT(*) FROM streams WHERE user_id = ?");
    $checkStream->execute([$creatorId]);
    $hasStreams = (bool)$checkStream->fetchColumn();

    if (!$hasStreams) {
        $insertStream = $pdo->prepare("
            INSERT INTO streams (
                user_id, title, type, vibe_tag, price_per_minute,
                revenue_mode, smartgrid_multiplier, geo_lat, geo_lng,
                archive_enabled, is_active, thumb_url
            ) VALUES (
                :user_id, :title, :type, :vibe_tag, :ppm,
                :revenue_mode, :mult, :lat, :lng,
                :archive_enabled, :is_active, :thumb
            )
        ");

        // Stream 1 – Public street cam
        $insertStream->execute([
            ':user_id' => $creatorId,
            ':title'   => 'Shibuya Crossing – Tokyo Street Cam',
            ':type'    => 'public',
            ':vibe_tag'=> 'city · ambient',
            ':ppm'     => 0.0150,
            ':revenue_mode' => 'smartgrid',
            ':mult'    => 1.4,
            ':lat'     => 35.6595,
            ':lng'     => 139.7005,
            ':archive_enabled' => 1,
            ':is_active' => 1,
            ':thumb'   => 'assets/img/demo_shibuya.jpg'
        ]);
        $stream1Id = $pdo->lastInsertId();

        // Stream 2 – Lifestyle / desk cam
        $insertStream->execute([
            ':user_id' => $creatorId,
            ':title'   => 'Studio Chill – Creator Loft',
            ':type'    => 'lifestyle',
            ':vibe_tag'=> 'lofi · cozy',
            ':ppm'     => 0.0200,
            ':revenue_mode' => 'smartgrid',
            ':mult'    => 1.8,
            ':lat'     => 34.0522,
            ':lng'     => -118.2437,
            ':archive_enabled' => 1,
            ':is_active' => 1,
            ':thumb'   => 'assets/img/demo_studio.jpg'
        ]);
        $stream2Id = $pdo->lastInsertId();

        // Stream 3 – NSFW (just to populate NSFW section; no actual content)
        $insertStream->execute([
            ':user_id' => $creatorId,
            ':title'   => 'After Hours – Private Studio',
            ':type'    => 'nsfw',
            ':vibe_tag'=> 'intimate',
            ':ppm'     => 0.0500,
            ':revenue_mode' => 'override',
            ':mult'    => 1.0,
            ':lat'     => 40.7128,
            ':lng'     => -74.0060,
            ':archive_enabled' => 0,
            ':is_active' => 1,
            ':thumb'   => 'assets/img/demo_nsfw.jpg'
        ]);
        $stream3Id = $pdo->lastInsertId();

        // Add some metrics for each stream
        $insertMetric = $pdo->prepare("
            INSERT INTO stream_metrics (stream_id, tips_cents, subs_count, views, avg_watch_seconds)
            VALUES (:sid, :tips, :subs, :views, :avg)
        ");

        $insertMetric->execute([
            ':sid'   => $stream1Id,
            ':tips'  => 35519,  // $355.19
            ':subs'  => 38,
            ':views' => 5150,
            ':avg'   => 420
        ]);
        $insertMetric->execute([
            ':sid'   => $stream2Id,
            ':tips'  => 22788,  // $227.88
            ':subs'  => 21,
            ':views' => 3120,
            ':avg'   => 560
        ]);
        $insertMetric->execute([
            ':sid'   => $stream3Id,
            ':tips'  => 81234,  // $812.34
            ':subs'  => 104,
            ':views' => 8900,
            ':avg'   => 780
        ]);
    }

    echo "<h2>OmniGrid demo seed completed.</h2>";
    echo "<p>You can now log in as:</p>";
    echo "<pre>Email: creator@omnigrid.test\nPassword: demo1234</pre>";
    echo "<p><a href='contribute/'>Go to Creator Dashboard</a></p>";
} catch (Throwable $e) {
    http_response_code(500);
    echo "<h2>Seed failed</h2>";
    echo "<pre>" . htmlspecialchars($e->getMessage()) . "</pre>";
}