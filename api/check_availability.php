<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);
if (session_status() === PHP_SESSION_NONE) session_start();
header('Content-Type: application/json');

require_once __DIR__ . '/../backend/includes/db.php';

if (!isset($_SESSION['logged_in'])) {
    http_response_code(401);
    echo json_encode(['success' => false, 'error' => 'Kailangan mong mag-login muna.']);
    exit();
}

try {
    $json = json_decode(file_get_contents('php://input'), true);
    $unitId = intval($json['unit_id'] ?? 0);
    $checkIn = trim($json['check_in'] ?? '');
    $checkOut = trim($json['check_out'] ?? '');
    $guestCount = intval($json['guest_count'] ?? 1);

    if ($unitId <= 0 || empty($checkIn) || empty($checkOut)) {
        throw new Exception('Kulang ang detalye ng booking.');
    }
    if ($checkOut <= $checkIn) {
        throw new Exception('Ang check-out date ay dapat pagkatapos ng check-in.');
    }

    $stmt = $pdo->prepare("SELECT price, max_guests FROM units WHERE id = :id AND status = 'available'");
    $stmt->execute([':id' => $unitId]);
    $unit = $stmt->fetch(PDO::FETCH_ASSOC);
    if (!$unit) throw new Exception('Hindi available ang unit na ito.');

    if ($guestCount > (int)$unit['max_guests']) {
        throw new Exception('Lumagpas sa max guests (' . $unit['max_guests'] . ') ang binigay mong bilang ng guest.');
    }

    $stmt = $pdo->prepare("
        SELECT id FROM bookings 
        WHERE unit_id = :unit_id AND status IN ('pending','confirmed') 
          AND (check_in < :check_out AND check_out > :check_in)
    ");
    $stmt->execute([':unit_id' => $unitId, ':check_out' => $checkOut, ':check_in' => $checkIn]);
    if ($stmt->fetch()) {
        throw new Exception('Sorry, hindi available ang unit sa napiling dates.');
    }

    $nights = (strtotime($checkOut) - strtotime($checkIn)) / 86400;
    $totalPrice = $nights * (float)$unit['price'];

    echo json_encode([
        'success' => true,
        'nights' => (int)$nights,
        'total_price' => $totalPrice,
        'price_per_night' => (float)$unit['price'],
    ]);
} catch (Exception $e) {
    http_response_code(400);
    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
}