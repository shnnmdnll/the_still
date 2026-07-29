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
    $userId = $_SESSION['user_id'];
    $json = json_decode(file_get_contents('php://input'), true);

    $unitId = intval($json['unit_id'] ?? 0);
    $checkIn = trim($json['check_in'] ?? '');
    $checkOut = trim($json['check_out'] ?? '');
    $guestCount = intval($json['guest_count'] ?? 1);

    if ($unitId <= 0 || empty($checkIn) || empty($checkOut)) {
        throw new Exception('Kulang ang detalye ng booking.');
    }

    // ID gate check — panghuling proteksyon
    $idStmt = $pdo->prepare("SELECT id_verification_status FROM users WHERE id = :id");
    $idStmt->execute([':id' => $userId]);
    $idStatus = $idStmt->fetchColumn();
    if (empty($idStatus) || $idStatus === 'not_submitted') {
        throw new Exception('Kailangan mo munang mag-upload ng valid ID.');
    }

    // Ulit i-check ang overlap para sigurado (baka na-book na ng iba habang naghihintay)
    $stmt = $pdo->prepare("
        SELECT id FROM bookings 
        WHERE unit_id = :unit_id AND status IN ('pending','confirmed') 
          AND (check_in < :check_out AND check_out > :check_in)
    ");
    $stmt->execute([':unit_id' => $unitId, ':check_out' => $checkOut, ':check_in' => $checkIn]);
    if ($stmt->fetch()) {
        throw new Exception('Sorry, na-book na ng iba yung dates habang nag-cocompirm ka.');
    }

    $stmt = $pdo->prepare("SELECT price FROM units WHERE id = :id");
    $stmt->execute([':id' => $unitId]);
    $unit = $stmt->fetch(PDO::FETCH_ASSOC);
    if (!$unit) throw new Exception('Unit not found.');

    $nights = (strtotime($checkOut) - strtotime($checkIn)) / 86400;
    $totalPrice = $nights * (float)$unit['price'];
    $downpaymentAmount = round($totalPrice * 0.20, 2);

    $stmt = $pdo->prepare("
        INSERT INTO bookings (unit_id, user_id, check_in, check_out, guest_count, total_price, downpayment_amount, status, payment_status) 
        VALUES (:unit_id, :user_id, :check_in, :check_out, :guest_count, :total_price, :downpayment_amount, 'awaiting_payment', 'unpaid') 
        RETURNING id
    ");
    $stmt->execute([
        ':unit_id' => $unitId, ':user_id' => $userId, ':check_in' => $checkIn, ':check_out' => $checkOut,
        ':guest_count' => $guestCount, ':total_price' => $totalPrice, ':downpayment_amount' => $downpaymentAmount,
    ]);
    $newBookingId = $stmt->fetchColumn();

    echo json_encode([
        'success' => true,
        'redirect_url' => 'api/dragonpay_checkout.php?booking_id=' . $newBookingId,
    ]);
} catch (Exception $e) {
    http_response_code(400);
    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
}