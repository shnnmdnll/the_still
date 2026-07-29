<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

header('Content-Type: application/json');

if (!isset($_SESSION['logged_in']) || $_SESSION['role'] !== 'owner') {
    http_response_code(403);
    echo json_encode(['success' => false, 'error' => 'Unauthorized']);
    exit();
}

require_once __DIR__ . '/../backend/includes/db.php';

try {
    $json = file_get_contents('php://input');
    $data = json_decode($json, true);

    $user_id = intval($data['user_id'] ?? 0);
    $newStatus = $data['is_active'] ?? null;

    if ($user_id <= 0) {
        throw new Exception('Valid user_id is required.');
    }
    if ($newStatus === null) {
        throw new Exception('Missing status value.');
    }

    if ($user_id === (int)$_SESSION['user_id']) {
        throw new Exception('You cannot change your own account status.');
    }

    $stmt = $pdo->prepare("UPDATE users SET is_active = :is_active WHERE id = :id");
    $stmt->execute([
        ':is_active' => $newStatus ? 'true' : 'false',
        ':id' => $user_id
    ]);

    echo json_encode([
        'success' => true,
        'message' => $newStatus ? 'Account activated.' : 'Account suspended.'
    ]);

} catch (Exception $e) {
    http_response_code(400);
    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
}