<?php
// db.php - OmniGrid DB connection
$DB_HOST = 'localhost';
$DB_NAME = 'thomasrlee42_omnigrid';
$DB_USER = 'thomasrlee42_omnigrid';
$DB_PASS = 'qwerpoiu0042!!';

try {
    $pdo = new PDO(
        "mysql:host={$DB_HOST};dbname={$DB_NAME};charset=utf8mb4",
        $DB_USER,
        $DB_PASS,
        [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        ]
    );
} catch (PDOException $e) {
    die('DB connection failed: ' . htmlspecialchars($e->getMessage()));
}