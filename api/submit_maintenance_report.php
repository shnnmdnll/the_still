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

    $description = trim($data['description'] ?? '');

    if (empty($description)) {
        throw new Exception('Description is required.');
    }

    $stmt = $pdo->prepare("SELECT assigned_unit_id FROM users WHERE id = :id");
    $stmt->execute([':id' => $staffId]);
    $unitId = $stmt->fetchColumn();

    if (!$unitId) {
        throw new Exception('You have no assigned unit.');
    }

    $stmt = $pdo->prepare("
        INSERT INTO maintenance_reports (unit_id, staff_id, description, status, reported_at)
        VALUES (:unit_id, :staff_id, :description, 'pending', NOW())
    ");
    $stmt->execute([
        ':unit_id' => $unitId,
        ':staff_id' => $staffId,
        ':description' => $description,
    ]);

    echo json_encode(['success' => true, 'message' => 'Report submitted successfully.']);

} catch (Exception $e) {
    http_response_code(400);
    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
}