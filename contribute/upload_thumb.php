<?php
session_start();
header('Content-Type: application/json');

if (empty($_SESSION['user_id'])) {
    http_response_code(401);
    echo json_encode(['success' => false, 'error' => 'Not logged in']);
    exit;
}

$user_id = (int)$_SESSION['user_id'];

if (empty($_FILES['thumb'])) {
    echo json_encode(['success' => false, 'error' => 'No file uploaded']);
    exit;
}

$file = $_FILES['thumb'];
$allowed_types = ['image/jpeg', 'image/png', 'image/webp'];
$max_size = 5 * 1024 * 1024; // 5MB

// Validate
if ($file['error'] !== UPLOAD_ERR_OK) {
    echo json_encode(['success' => false, 'error' => 'Upload error']);
    exit;
}

if (!in_array($file['type'], $allowed_types)) {
    echo json_encode(['success' => false, 'error' => 'Invalid file type. Use JPG, PNG, or WebP']);
    exit;
}

if ($file['size'] > $max_size) {
    echo json_encode(['success' => false, 'error' => 'File too large. Max 5MB']);
    exit;
}

// Generate unique filename
$ext = pathinfo($file['name'], PATHINFO_EXTENSION);
$filename = 'thumb_' . $user_id . '_' . bin2hex(random_bytes(8)) . '.' . $ext;
$upload_dir = '../assets/img/thumbs/';
$upload_path = $upload_dir . $filename;

// Ensure directory exists
if (!is_dir($upload_dir)) {
    mkdir($upload_dir, 0755, true);
}

if (move_uploaded_file($file['tmp_name'], $upload_path)) {
    echo json_encode([
        'success' => true,
        'thumb_url' => 'assets/img/thumbs/' . $filename
    ]);
} else {
    http_response_code(500);
    echo json_encode(['success' => false, 'error' => 'Failed to save file']);
}
