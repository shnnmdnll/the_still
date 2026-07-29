<?php
// api/reset_password.php

error_reporting(0);
ini_set('display_errors', 0);

header('Content-Type: application/json');

require_once __DIR__ . '/../backend/includes/db.php';

try {
    $json = file_get_contents('php://input');
    $data = json_decode($json, true);

    $token = trim($data['token'] ?? '');
    $newPassword = $data['new_password'] ?? '';

    if (empty($token)) {
        throw new Exception('Invalid reset link.');
    }
    if (strlen($newPassword) < 6) {
        throw new Exception('Password must be at least 6 characters.');
    }

    $stmt = $pdo->prepare("SELECT id, reset_token_expires_at FROM users WHERE reset_token = :token LIMIT 1");
    $stmt->execute([':token' => $token]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$user) {
        throw new Exception('Invalid or expired reset link.');
    }
    if (strtotime($user['reset_token_expires_at']) < time()) {
        throw new Exception('This reset link has expired. Please request a new one.');
    }

    $hashedPassword = password_hash($newPassword, PASSWORD_DEFAULT);

    $update = $pdo->prepare("UPDATE users SET password = :password, reset_token = NULL, reset_token_expires_at = NULL WHERE id = :id");
    $update->execute([
        ':password' => $hashedPassword,
        ':id' => $user['id'],
    ]);

    echo json_encode(['success' => true, 'message' => 'Your password has been reset successfully.']);

} catch (Exception $e) {
    http_response_code(400);
    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
}