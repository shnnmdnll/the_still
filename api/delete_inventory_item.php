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
    $itemId = intval($_GET['id'] ?? 0);

    if ($itemId <= 0) {
        throw new Exception('Valid item_id is required.');
    }

    // I-verify na ang item ay nasa unit na naka-assign sa staff na ito
    $checkStmt = $pdo->prepare("
        SELECT i.id FROM inventory_items i
        JOIN users s ON s.assigned_unit_id = i.unit_id
        WHERE i.id = :item_id AND s.id = :staff_id
        LIMIT 1
    ");
    $checkStmt->execute([':item_id' => $itemId, ':staff_id' => $staffId]);
    if (!$checkStmt->fetch()) {
        throw new Exception('You are not authorized to delete this item.');
    }

    $stmt = $pdo->prepare("DELETE FROM inventory_items WHERE id = :id");
    $stmt->execute([':id' => $itemId]);

    echo json_encode(['success' => true, 'message' => 'Item deleted.']);

} catch (Exception $e) {
    http_response_code(400);
    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
}