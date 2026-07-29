<?php
// api/delete_property.php

error_reporting(0);
ini_set('display_errors', 0);

session_start();

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST, DELETE, GET, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit();
}

if (!isset($_SESSION['user_id'])) {
    http_response_code(401);
    echo json_encode(['success' => false, 'error' => 'You must be logged in.']);
    exit();
}

try {
    require_once __DIR__ . '/../backend/includes/db.php';
} catch (Exception $e) {
    echo json_encode(['success' => false, 'error' => 'Database connection failed: ' . $e->getMessage()]);
    exit();
}

if (!isset($pdo)) {
    echo json_encode(['success' => false, 'error' => 'Database connection not established']);
    exit();
}

try {
    // Ngayon, galing na sa totoong session, hindi na hard-coded
    $hostId = $_SESSION['user_id'];

    $unit_id = intval($_GET['id'] ?? 0);
    if ($unit_id <= 0) {
        $json = file_get_contents('php://input');
        $data = json_decode($json, true);
        $unit_id = intval($data['unit_id'] ?? 0);
    }

    if ($unit_id <= 0) {
        throw new Exception('Valid unit_id is required');
    }

    // 1. Confirm the unit exists and belongs to this host (or owner override)
    $checkSql = "SELECT host_id FROM units WHERE id = :id LIMIT 1";
    $checkStmt = $pdo->prepare($checkSql);
    $checkStmt->execute([':id' => $unit_id]);
    $existing = $checkStmt->fetch(PDO::FETCH_ASSOC);

    if (!$existing) {
        http_response_code(404);
        echo json_encode(['success' => false, 'error' => 'Unit not found']);
        exit();
    }

    $isOwner = ($_SESSION['role'] ?? '') === 'owner';
    if (!$isOwner && (int)$existing['host_id'] !== (int)$hostId) {
        http_response_code(403);
        echo json_encode(['success' => false, 'error' => 'You are not authorized to delete this unit']);
        exit();
    }

    // 2. Delete
    if ($isOwner) {
        $sql = "DELETE FROM units WHERE id = :id";
        $stmt = $pdo->prepare($sql);
        $stmt->execute([':id' => $unit_id]);
    } else {
        $sql = "DELETE FROM units WHERE id = :id AND host_id = :host_id";
        $stmt = $pdo->prepare($sql);
        $stmt->execute([':id' => $unit_id, ':host_id' => $hostId]);
    }

    echo json_encode(['success' => true, 'message' => 'Unit deleted successfully!']);

} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'error' => 'Database error: ' . $e->getMessage()]);
} catch (Exception $e) {
    http_response_code(400);
    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
}