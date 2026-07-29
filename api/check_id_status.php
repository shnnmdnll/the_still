<?php
if (session_status() === PHP_SESSION_NONE) session_start();
header('Content-Type: application/json');
require_once __DIR__ . '/../backend/includes/db.php';

if (!isset($_SESSION['logged_in'])) {
    http_response_code(401);
    echo json_encode(['success' => false, 'error' => 'Not logged in.']);
    exit();
}

$stmt = $pdo->prepare("SELECT id_verification_status FROM users WHERE id = :id");
$stmt->execute([':id' => $_SESSION['user_id']]);
$status = $stmt->fetchColumn();

echo json_encode(['success' => true, 'status' => $status ?: 'not_submitted']);