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

    $report_id = intval($data['report_id'] ?? 0);
    $newStatus = trim($data['status'] ?? '');

    if ($report_id <= 0) {
        throw new Exception('Valid report_id is required.');
    }
    if (!in_array($newStatus, ['pending', 'in_progress', 'resolved'], true)) {
        throw new Exception('Invalid status.');
    }

    $resolvedAt = $newStatus === 'resolved' ? ', resolved_at = NOW()' : '';
    $stmt = $pdo->prepare("UPDATE maintenance_reports SET status = :status $resolvedAt WHERE id = :id");
    $stmt->execute([':status' => $newStatus, ':id' => $report_id]);

    echo json_encode(['success' => true, 'message' => 'Maintenance report updated.']);

} catch (Exception $e) {
    http_response_code(400);
    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
}