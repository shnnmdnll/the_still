<?php
// api/upload_avatar.php

error_reporting(0);
ini_set('display_errors', 0);

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

header('Content-Type: application/json');

if (!isset($_SESSION['logged_in'])) {
    http_response_code(403);
    echo json_encode(['success' => false, 'error' => 'You must be logged in.']);
    exit();
}

require_once __DIR__ . '/../backend/includes/db.php';

try {
    $userId = $_SESSION['user_id'];

    if (!isset($_FILES['avatar']) || $_FILES['avatar']['error'] !== UPLOAD_ERR_OK) {
        throw new Exception('Please select a valid image to upload.');
    }

    $file = $_FILES['avatar'];
    $allowedTypes = ['image/jpeg', 'image/png', 'image/jpg', 'image/webp'];
    $maxSize = 5 * 1024 * 1024; // 5MB

    if (!in_array($file['type'], $allowedTypes, true)) {
        throw new Exception('Only JPG, PNG, and WEBP files are allowed.');
    }
    if ($file['size'] > $maxSize) {
        throw new Exception('File is too large. Maximum size is 5MB.');
    }

    $uploadDir = __DIR__ . '/../uploads/avatars/';
    if (!is_dir($uploadDir)) {
        mkdir($uploadDir, 0755, true);
    }

    $ext = pathinfo($file['name'], PATHINFO_EXTENSION);
    $filename = 'avatar_' . $userId . '_' . time() . '.' . $ext;
    $destination = $uploadDir . $filename;

    if (!move_uploaded_file($file['tmp_name'], $destination)) {
        throw new Exception('Failed to save the uploaded image.');
    }

    $relativePath = 'uploads/avatars/' . $filename;

    $stmt = $pdo->prepare("UPDATE users SET avatar_url = :avatar_url WHERE id = :id");
    $stmt->execute([
        ':avatar_url' => $relativePath,
        ':id' => $userId,
    ]);

    echo json_encode(['success' => true, 'avatar_url' => $relativePath, 'message' => 'Profile picture updated!']);

} catch (Exception $e) {
    http_response_code(400);
    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
}