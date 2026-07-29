<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

header('Content-Type: application/json');

if (!isset($_SESSION['logged_in']) || $_SESSION['role'] !== 'guest') {
    http_response_code(403);
    echo json_encode(['success' => false, 'error' => 'Only guests can leave reviews.']);
    exit();
}

require_once __DIR__ . '/../backend/includes/db.php';

try {
    $userId = $_SESSION['user_id'];
    $json = file_get_contents('php://input');
    $data = json_decode($json, true);

    $bookingId = intval($data['booking_id'] ?? 0);
    $rating = intval($data['rating'] ?? 0);
    $comment = trim($data['comment'] ?? '');

    if ($bookingId <= 0) {
        throw new Exception('Valid booking_id is required.');
    }
    if ($rating < 1 || $rating > 5) {
        throw new Exception('Rating must be between 1 and 5.');
    }

    // I-verify na pag-aari ng guest ang booking na ito, at tapos na ang stay
    $checkStmt = $pdo->prepare("
        SELECT unit_id FROM bookings 
        WHERE id = :booking_id AND user_id = :user_id 
          AND (status = 'completed' OR check_out < CURRENT_DATE)
        LIMIT 1
    ");
    $checkStmt->execute([':booking_id' => $bookingId, ':user_id' => $userId]);
    $booking = $checkStmt->fetch(PDO::FETCH_ASSOC);

    if (!$booking) {
        throw new Exception('Booking not found or not eligible for review.');
    }

    // I-check kung na-review na
    $dupStmt = $pdo->prepare("SELECT id FROM reviews WHERE booking_id = :booking_id");
    $dupStmt->execute([':booking_id' => $bookingId]);
    if ($dupStmt->fetch()) {
        throw new Exception('You have already reviewed this stay.');
    }

    $stmt = $pdo->prepare("
        INSERT INTO reviews (booking_id, unit_id, user_id, rating, comment, created_at)
        VALUES (:booking_id, :unit_id, :user_id, :rating, :comment, NOW())
    ");
    $stmt->execute([
        ':booking_id' => $bookingId,
        ':unit_id' => $booking['unit_id'],
        ':user_id' => $userId,
        ':rating' => $rating,
        ':comment' => $comment,
    ]);

    echo json_encode(['success' => true, 'message' => 'Review submitted. Salamat!']);

} catch (Exception $e) {
    http_response_code(400);
    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
}