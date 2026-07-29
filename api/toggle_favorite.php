<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

header('Content-Type: application/json');

if (!isset($_SESSION['logged_in']) || $_SESSION['role'] !== 'guest') {
    http_response_code(403);
    echo json_encode(['success' => false, 'error' => 'Only guests can save favorites.']);
    exit();
}

require_once __DIR__ . '/../backend/includes/db.php';

try {
    $userId = $_SESSION['user_id'];
    $json = file_get_contents('php://input');
    $data = json_decode($json, true);

    $unitId = intval($data['unit_id'] ?? 0);

    if ($unitId <= 0) {
        throw new Exception('Valid unit_id is required.');
    }

    // I-check kung naka-favorite na
    $checkStmt = $pdo->prepare("SELECT id FROM favorites WHERE user_id = :user_id AND unit_id = :unit_id");
    $checkStmt->execute([':user_id' => $userId, ':unit_id' => $unitId]);
    $existing = $checkStmt->fetch();

    if ($existing) {
        // I-remove
        $stmt = $pdo->prepare("DELETE FROM favorites WHERE id = :id");
        $stmt->execute([':id' => $existing['id']]);
        echo json_encode(['success' => true, 'action' => 'removed', 'message' => 'Removed from favorites.']);
    } else {
        // I-add
        $stmt = $pdo->prepare("INSERT INTO favorites (user_id, unit_id) VALUES (:user_id, :unit_id)");
        $stmt->execute([':user_id' => $userId, ':unit_id' => $unitId]);
        echo json_encode(['success' => true, 'action' => 'added', 'message' => 'Added to favorites.']);
    }

} catch (Exception $e) {
    http_response_code(400);
    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
}