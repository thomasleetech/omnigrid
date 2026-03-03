<?php
// upgrade_20251116.php - OmniGrid schema & admin upgrade
// Run once, then delete or lock down.

require_once __DIR__ . '/db.php';

echo "<h2>OmniGrid upgrade – 2025-11-16</h2>";

try {
    // 1) user_events (behavior log)
    $pdo->exec("
        CREATE TABLE IF NOT EXISTS user_events (
            id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
            user_id INT UNSIGNED NULL,
            fingerprint VARCHAR(128) DEFAULT NULL,
            ip VARCHAR(64) DEFAULT NULL,
            user_agent VARCHAR(255) DEFAULT NULL,
            event_type VARCHAR(64) NOT NULL,
            meta_json TEXT NULL,
            created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
            INDEX idx_event_ip_created (ip, created_at),
            INDEX idx_event_fp_created (fingerprint, created_at),
            INDEX idx_event_type_created (event_type, created_at)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
    ");
    echo "<p>✔ user_events table ensured.</p>";

    // 2) threat_scores (aggregated risk per IP/fingerprint)
    $pdo->exec("
        CREATE TABLE IF NOT EXISTS threat_scores (
            id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
            user_id INT UNSIGNED NULL,
            fingerprint VARCHAR(128) DEFAULT NULL,
            ip VARCHAR(64) DEFAULT NULL,
            score INT NOT NULL DEFAULT 0,
            reason VARCHAR(255) DEFAULT NULL,
            last_event_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
            INDEX idx_threat_ip (ip),
            INDEX idx_threat_fp (fingerprint)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
    ");
    echo "<p>✔ threat_scores table ensured.</p>";

    // 3) user_device_fingerprints (silent auth / device binding)
    $pdo->exec("
        CREATE TABLE IF NOT EXISTS user_device_fingerprints (
            id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
            user_id INT UNSIGNED NOT NULL,
            fingerprint VARCHAR(128) NOT NULL,
            last_seen_ip VARCHAR(64) DEFAULT NULL,
            last_seen_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
            trust_level INT NOT NULL DEFAULT 1,
            created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
            UNIQUE KEY uq_user_fp (user_id, fingerprint),
            INDEX idx_fp (fingerprint)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
    ");
    echo "<p>✔ user_device_fingerprints table ensured.</p>";

    // 4) stream_archive_segments (stub for archive/paywall)
    $pdo->exec("
        CREATE TABLE IF NOT EXISTS stream_archive_segments (
            id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
            stream_id INT UNSIGNED NOT NULL,
            storage_key VARCHAR(255) NOT NULL,
            started_at DATETIME NOT NULL,
            ended_at DATETIME NOT NULL,
            bytes BIGINT UNSIGNED NOT NULL DEFAULT 0,
            is_paywalled TINYINT(1) NOT NULL DEFAULT 0,
            price_cents INT UNSIGNED NOT NULL DEFAULT 0,
            created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
            CONSTRAINT fk_archive_stream FOREIGN KEY (stream_id)
                REFERENCES streams(id) ON DELETE CASCADE,
            INDEX idx_stream_created (stream_id, created_at)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
    ");
    echo "<p>✔ stream_archive_segments table ensured.</p>";

    // 5) Add hero_mode / hero_loop_url to site_config if missing
    $col = $pdo->query("SHOW COLUMNS FROM site_config LIKE 'hero_mode'")->fetch();
    if (!$col) {
        $pdo->exec("
            ALTER TABLE site_config
            ADD COLUMN hero_mode ENUM('auto','live','loop') NOT NULL DEFAULT 'auto'
        ");
        echo "<p>✔ site_config.hero_mode added.</p>";
    } else {
        echo "<p>✔ site_config.hero_mode already exists.</p>";
    }

    $col = $pdo->query("SHOW COLUMNS FROM site_config LIKE 'hero_loop_url'")->fetch();
    if (!$col) {
        $pdo->exec("
            ALTER TABLE site_config
            ADD COLUMN hero_loop_url VARCHAR(255) NOT NULL DEFAULT 'assets/demo_loop.mp4'
        ");
        echo "<p>✔ site_config.hero_loop_url added.</p>";
    } else {
        echo "<p>✔ site_config.hero_loop_url already exists.</p>";
    }

    // Ensure row id=1 has sane defaults
    $stmt = $pdo->query("SELECT COUNT(*) FROM site_config WHERE id = 1");
    if (!(int)$stmt->fetchColumn()) {
        $pdo->exec("
            INSERT INTO site_config (id, rate_mode, base_ppm, smartgrid_aggressiveness, hero_mode, hero_loop_url)
            VALUES (1, 'smartgrid', 0.000500, 1.50, 'auto', 'assets/demo_loop.mp4')
        ");
        echo "<p>✔ site_config row #1 created.</p>";
    } else {
        $pdo->exec("
            UPDATE site_config
            SET hero_mode = COALESCE(hero_mode, 'auto'),
                hero_loop_url = COALESCE(hero_loop_url, 'assets/demo_loop.mp4')
            WHERE id = 1
        ");
        echo "<p>✔ site_config row #1 updated with hero defaults.</p>";
    }

    // 6) Ensure admin user
    $adminEmail = 'admin@omnigrid.test';
    $stmt = $pdo->prepare("SELECT id FROM users WHERE email = ?");
    $stmt->execute([$adminEmail]);
    $adminId = $stmt->fetchColumn();

    if (!$adminId) {
        $hash = password_hash('admin1234', PASSWORD_DEFAULT);
        $ins = $pdo->prepare("
            INSERT INTO users (email, password_hash, display_name, role)
            VALUES (:email, :hash, :name, 'admin')
        ");
        $ins->execute([
            ':email' => $adminEmail,
            ':hash'  => $hash,
            ':name'  => 'Grid Admin'
        ]);
        $adminId = $pdo->lastInsertId();
        echo "<p>✔ Admin user created: <strong>admin@omnigrid.test</strong> / <strong>admin1234</strong></p>";
    } else {
        echo "<p>✔ Admin user already exists: {$adminEmail}</p>";
    }

    echo "<hr><p><strong>Upgrade complete.</strong></p>";
    echo "<p>Admin panel: <a href='admin/'>/omnigrid/admin/</a></p>";
} catch (Throwable $e) {
    http_response_code(500);
    echo "<h3>Upgrade failed</h3>";
    echo "<pre>" . htmlspecialchars($e->getMessage()) . "</pre>";
}