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

    $itemName = trim($data['item_name'] ?? '');
    $category = trim($data['category'] ?? '');
    $subcategory = trim($data['subcategory'] ?? '');
    $quantity = intval($data['quantity'] ?? 0);

    if (empty($itemName)) {
        throw new Exception('Item name is required.');
    }
    if (!in_array($category, ['supply', 'appliance'], true)) {
        throw new Exception('Invalid category.');
    }

    $stmt = $pdo->prepare("SELECT assigned_unit_id FROM users WHERE id = :id");
    $stmt->execute([':id' => $staffId]);
    $unitId = $stmt->fetchColumn();

    if (!$unitId) {
        throw new Exception('You have no assigned unit.');
    }

    if ($category === 'supply') {
        $status = $quantity === 0 ? 'out' : ($quantity <= 3 ? 'low' : 'available');
        $stmt = $pdo->prepare("
            INSERT INTO inventory_items (unit_id, item_name, category, subcategory, status, quantity, low_threshold, updated_at)
            VALUES (:unit_id, :item_name, :category, :subcategory, :status, :quantity, 3, NOW())
        ");
        $stmt->execute([
            ':unit_id' => $unitId,
            ':item_name' => $itemName,
            ':category' => $category,
            ':subcategory' => $subcategory ?: 'Other',
            ':status' => $status,
            ':quantity' => $quantity,
        ]);
    } else {
    $stmt = $pdo->prepare("
        INSERT INTO inventory_items (unit_id, item_name, category, subcategory, status, updated_at)
        VALUES (:unit_id, :item_name, :category, :subcategory, 'available', NOW())
    ");
    $stmt->execute([
        ':unit_id' => $unitId,
        ':item_name' => $itemName,
        ':category' => $category,
        ':subcategory' => $subcategory ?: 'Other',
    ]);
}

    echo json_encode(['success' => true, 'message' => 'Item added.']);

} catch (Exception $e) {
    http_response_code(400);
    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
}