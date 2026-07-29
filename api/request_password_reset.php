<?php
// api/request_password_reset.php

error_reporting(0);
ini_set('display_errors', 0);

header('Content-Type: application/json');

require_once __DIR__ . '/../backend/includes/db.php';
require_once __DIR__ . '/../backend/includes/mailer.php';

try {
    $json = file_get_contents('php://input');
    $data = json_decode($json, true);
    $email = trim($data['email'] ?? '');

    if (empty($email) || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
        throw new Exception('Please enter a valid email address.');
    }

    $stmt = $pdo->prepare("SELECT id, name FROM users WHERE email = :email LIMIT 1");
    $stmt->execute([':email' => $email]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);

    // Kahit hindi mahanap ang email, palaging generic success message ang ibabalik
    // (para hindi malaman ng attacker kung anong emails ang registered sa system).
    if ($user) {
        $token = bin2hex(random_bytes(32));
        $expiresAt = date('Y-m-d H:i:s', strtotime('+30 minutes'));

        $update = $pdo->prepare("UPDATE users SET reset_token = :token, reset_token_expires_at = :expires WHERE id = :id");
        $update->execute([
            ':token' => $token,
            ':expires' => $expiresAt,
            ':id' => $user['id'],
        ]);

        sendPasswordResetEmail($email, $user['name'], $token);
    }

    echo json_encode([
        'success' => true,
        'message' => 'If an account with that email exists, a password reset link has been sent.',
    ]);

} catch (Exception $e) {
    http_response_code(400);
    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
}