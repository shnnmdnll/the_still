<?php
// api/submit_person_rating.php
// Ginagamit ng guest para i-rate ang host, o ng host para i-rate ang guest,
// pagkatapos ng isang tapos na (completed) na stay.

error_reporting(0);
ini_set('display_errors', 0);

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

header('Content-Type: application/json');

if (!isset($_SESSION['logged_in'])) {
    http_response_code(403);
    echo json_encode(['success' => false, 'error' => 'You must be logged in.']);
    exit();
}

require_once __DIR__ . '/../backend/includes/db.php';

try {
    $json = file_get_contents('php://input');
    $data = json_decode($json, true);

    $raterId = $_SESSION['user_id'];
    $bookingId = intval($data['booking_id'] ?? 0);
    $rating = intval($data['rating'] ?? 0);
    $comment = trim($data['comment'] ?? '');

    if ($bookingId <= 0) {
        throw new Exception('Valid booking is required.');
    }
    if ($rating < 1 || $rating > 5) {
        throw new Exception('Rating must be between 1 and 5.');
    }

    // Kunin ang booking, kasama ang guest_id at yung host ng unit na na-book
    $stmt = $pdo->prepare("
        SELECT b.id, b.guest_id, b.check_out, b.status, u.host_id
        FROM bookings b
        JOIN units u ON u.id = b.unit_id
        WHERE b.id = :booking_id
        LIMIT 1
    ");
    $stmt->execute([':booking_id' => $bookingId]);
    $booking = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$booking) {
        throw new Exception('Booking not found.');
    }

    // Dapat tapos na ang stay (lumipas na ang check-out) at hindi cancelled/declined
    $isPastCheckout = strtotime($booking['check_out']) < strtotime('today');
    $isValidStatus = in_array($booking['status'], ['confirmed', 'completed'], true);

    if (!$isPastCheckout || !$isValidStatus) {
        throw new Exception('You can only rate after the stay is completed.');
    }

    // Alamin kung sino ang dapat maging "ratee" — dapat kabaligtaran ng rater
    // (guest -> host, o host -> guest), hindi puwedeng mag-rate ng sarili
    // o ng taong hindi bahagi ng booking na ito.
    if ($raterId == $booking['guest_id']) {
        $rateeId = $booking['host_id'];
    } elseif ($raterId == $booking['host_id']) {
        $rateeId = $booking['guest_id'];
    } else {
        throw new Exception('You are not part of this booking.');
    }

    $stmt = $pdo->prepare("
        INSERT INTO host_guest_ratings (booking_id, rater_id, ratee_id, rating, comment)
        VALUES (:booking_id, :rater_id, :ratee_id, :rating, :comment)
    ");
    $stmt->execute([
        ':booking_id' => $bookingId,
        ':rater_id' => $raterId,
        ':ratee_id' => $rateeId,
        ':rating' => $rating,
        ':comment' => $comment,
    ]);

    echo json_encode(['success' => true, 'message' => 'Rating submitted! Salamat sa feedback.']);

} catch (PDOException $e) {
    // Unique constraint violation -> nag-rate na siya dati para sa booking na ito
    if ($e->getCode() === '23505') {
        http_response_code(400);
        echo json_encode(['success' => false, 'error' => 'You already rated this booking.']);
    } else {
        http_response_code(500);
        echo json_encode(['success' => false, 'error' => 'Database error: ' . $e->getMessage()]);
    }
} catch (Exception $e) {
    http_response_code(400);
    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
}