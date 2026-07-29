<?php
// api/update_booking_status.php
// Pinapayagan nitong i-accept (confirm) o i-decline ng HOST yung isang booking —
// pero lang para sa mga bookings na nasa unit na kanya talagang pinamamahalaan.

error_reporting(0);
ini_set('display_errors', 0);

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

header('Content-Type: application/json');

if (empty($_SESSION['logged_in']) || empty($_SESSION['user_id'])) {
    http_response_code(401);
    echo json_encode(['success' => false, 'error' => 'You must be logged in.']);
    exit();
}

require_once __DIR__ . '/../backend/includes/db.php';

try {
    $json = file_get_contents('php://input');
    $data = json_decode($json, true);

    if (!$data) {
        throw new Exception('No data received.');
    }

    $hostId     = $_SESSION['user_id'];
    $booking_id = intval($data['booking_id'] ?? 0);
    $new_status = trim($data['status'] ?? '');

    if ($booking_id <= 0) {
        throw new Exception('Valid booking_id is required.');
    }
    if (!in_array($new_status, ['confirmed', 'declined'], true)) {
        throw new Exception('Invalid status. Must be confirmed or declined.');
    }

    // Kumpirmahin na ang booking na ito ay nasa unit na pinamamahalaan mismo
    // ng naka-login na host — para hindi mai-edit ang booking ng ibang host.
    $stmt = $pdo->prepare("
        SELECT b.id
        FROM bookings b
        JOIN units un ON un.id = b.unit_id
        WHERE b.id = :booking_id AND un.host_id = :host_id
        LIMIT 1
    ");
    $stmt->execute([':booking_id' => $booking_id, ':host_id' => $hostId]);

    if (!$stmt->fetch()) {
        http_response_code(403);
        echo json_encode(['success' => false, 'error' => 'You are not authorized to update this booking.']);
        exit();
    }

    $stmt = $pdo->prepare("UPDATE bookings SET status = :status WHERE id = :id");
    $stmt->execute([':status' => $new_status, ':id' => $booking_id]);

    echo json_encode([
        'success' => true,
        'message' => 'Booking ' . $new_status . ' successfully.',
        'booking_id' => $booking_id,
        'status' => $new_status
    ]);

} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'error' => 'Database error. Please try again.']);
} catch (Exception $e) {
    http_response_code(400);
    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
}