<?php
// register_creator.php
session_start();
require_once __DIR__ . '/db.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: contribute/');
    exit;
}

$display = trim($_POST['display_name'] ?? '');
$email   = trim($_POST['email'] ?? '');
$pass    = $_POST['password'] ?? '';
$loc     = trim($_POST['location_hint'] ?? '');
$archiveOpt = !empty($_POST['archive_opt']) ? 1 : 0;

if ($display === '' || $email === '' || $pass === '') {
    header('Location: contribute/?err=reg_missing');
    exit;
}

$stmt = $pdo->prepare("SELECT id FROM users WHERE email = ? LIMIT 1");
$stmt->execute([$email]);
$exists = $stmt->fetchColumn();
if ($exists) {
    header('Location: contribute/?err=reg_exists');
    exit;
}

$hash = password_hash($pass, PASSWORD_DEFAULT);

// you can extend schema with location_hint etc later; for now keep basic
$insert = $pdo->prepare("
    INSERT INTO users (email, password_hash, display_name, role)
    VALUES (:email, :hash, :display, 'creator')
");
$insert->execute([
    ':email'   => $email,
    ':hash'    => $hash,
    ':display' => $display,
]);

$userId = $pdo->lastInsertId();
$_SESSION['user_id'] = (int)$userId;

// Optional: default settings per creator (not required for demo)

// Back to creator dashboard
header('Location: contribute/');
exit;