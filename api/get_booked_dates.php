<?php

error_reporting(0);
ini_set('display_errors', 0);

header('Content-Type: application/json');

require_once __DIR__ . '/../backend/includes/db.php';

try {
    $unit_id = intval($_GET['unit_id'] ?? 0);

    if ($unit_id <= 0) {
        throw new Exception('Valid unit id is required.');
    }

    $stmt = $pdo->prepare("
        SELECT check_in, check_out
        FROM bookings
        WHERE unit_id = :unit_id
          AND status != 'declined'
          AND check_out >= CURRENT_DATE
    ");
    $stmt->execute([':unit_id' => $unit_id]);
    $ranges = $stmt->fetchAll(PDO::FETCH_ASSOC);

    echo json_encode(['success' => true, 'booked_ranges' => $ranges]);

} catch (Exception $e) {
    http_response_code(400);
    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
}