<?php
// backend/api/delete_property.php

error_reporting(0);
ini_set('display_errors', 0);

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST, DELETE, GET, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit();
}

try {
    require_once __DIR__ . '/../backend/includes/db.php';
} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'error' => 'Database connection failed: ' . $e->getMessage()
    ]);
    exit();
}

if (!isset($pdo)) {
    echo json_encode([
        'success' => false,
        'error' => 'Database connection not established'
    ]);
    exit();
}

try {
    // HARD-CODED MUNA: si Host/Owner ang gumagamit (User ID = 1).
    // Sa future (multi-account login), papalitan ito ng: $_SESSION['user_id']
    $hostId = 1;

    // Accept the property id from ?id=5 (GET/DELETE) or from a JSON body
    $property_id = intval($_GET['id'] ?? 0);
    if ($property_id <= 0) {
        $json = file_get_contents('php://input');
        $data = json_decode($json, true);
        $property_id = intval($data['property_id'] ?? 0);
    }

    if ($property_id <= 0) {
        throw new Exception('Valid property_id is required');
    }

    // 1. Confirm the property exists and belongs to this host
    $checkSql = "SELECT user_id FROM properties WHERE id = :id LIMIT 1";
    $checkStmt = $pdo->prepare($checkSql);
    $checkStmt->execute([':id' => $property_id]);
    $existing = $checkStmt->fetch(PDO::FETCH_ASSOC);

    if (!$existing) {
        http_response_code(404);
        echo json_encode([
            'success' => false,
            'error' => 'Property not found'
        ]);
        exit();
    }

    if ((int)$existing['user_id'] !== $hostId) {
        http_response_code(403);
        echo json_encode([
            'success' => false,
            'error' => 'You are not authorized to delete this property'
        ]);
        exit();
    }

    // 2. Delete (scoped to this host, as an extra safety net)
    $sql = "DELETE FROM properties WHERE id = :id AND user_id = :user_id";
    $stmt = $pdo->prepare($sql);
    $stmt->execute([
        ':id' => $property_id,
        ':user_id' => $hostId
    ]);

    echo json_encode([
        'success' => true,
        'message' => 'Property deleted successfully!'
    ]);

} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'error' => 'Database error: ' . $e->getMessage()
    ]);
} catch (Exception $e) {
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage()
    ]);
}
?>