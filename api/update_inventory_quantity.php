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
    $quantity = intval($data['quantity'] ?? -1);

    if ($itemId <= 0) {
        throw new Exception('Valid item_id is required.');
    }
    if ($quantity < 0) {
        throw new Exception('Quantity cannot be negative.');
    }

    $checkStmt = $pdo->prepare("
        SELECT i.id, i.low_threshold FROM inventory_items i
        JOIN users s ON s.assigned_unit_id = i.unit_id
        WHERE i.id = :item_id AND s.id = :staff_id
        LIMIT 1
    ");
    $checkStmt->execute([':item_id' => $itemId, ':staff_id' => $staffId]);
    $item = $checkStmt->fetch(PDO::FETCH_ASSOC);
    if (!$item) {
        throw new Exception('You are not authorized to update this item.');
    }

    $threshold = $item['low_threshold'] ?? 3;
    if ($quantity === 0) {
        $newStatus = 'out';
    } elseif ($quantity <= $threshold) {
        $newStatus = 'low';
    } else {
        $newStatus = 'available';
    }

    $stmt = $pdo->prepare("UPDATE inventory_items SET quantity = :qty, status = :status, updated_at = NOW() WHERE id = :id");
    $stmt->execute([':qty' => $quantity, ':status' => $newStatus, ':id' => $itemId]);

    echo json_encode(['success' => true, 'message' => 'Quantity updated.', 'new_status' => $newStatus]);

} catch (Exception $e) {
    http_response_code(400);
    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
}