<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

header('Content-Type: application/json');

if (!isset($_SESSION['logged_in']) || $_SESSION['role'] !== 'staff') {
    http_response_code(403);
    echo json_encode(['success' => false, 'error' => 'Unauthorized']);
    exit();
}

require_once __DIR__ . '/../backend/includes/db.php';

try {
    $staffId = $_SESSION['user_id'];
    $json = file_get_contents('php://input');
    $data = json_decode($json, true);

    $itemId = intval($data['item_id'] ?? 0);

    if ($itemId <= 0) {
        throw new Exception('Valid item_id is required.');
    }

    $checkStmt = $pdo->prepare("
        SELECT i.id FROM inventory_items i
        JOIN users s ON s.assigned_unit_id = i.unit_id
        WHERE i.id = :item_id AND s.id = :staff_id
        LIMIT 1
    ");
    $checkStmt->execute([':item_id' => $itemId, ':staff_id' => $staffId]);
    if (!$checkStmt->fetch()) {
        throw new Exception('You are not authorized to update this item.');
    }

    $logStmt = $pdo->prepare("INSERT INTO inventory_maintenance_log (item_id, serviced_at) VALUES (:item_id, CURRENT_DATE)");
    $logStmt->execute([':item_id' => $itemId]);

    $updateStmt = $pdo->prepare("UPDATE inventory_items SET last_maintained_date = CURRENT_DATE, status = 'available' WHERE id = :id");
    $updateStmt->execute([':id' => $itemId]);

    echo json_encode(['success' => true, 'message' => 'Marked as maintained.']);

} catch (Exception $e) {
    http_response_code(400);
    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
}