<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once __DIR__ . '/../backend/includes/db.php';
require_once __DIR__ . '/../backend/includes/dragonpay_config.php';

if (!isset($_SESSION['logged_in'])) {
    header('Location: ../login.php');
    exit();
}

$bookingId = intval($_GET['booking_id'] ?? 0);
$userId = $_SESSION['user_id'];

if ($bookingId <= 0) {
    die('Invalid booking.');
}

$stmt = $pdo->prepare("
    SELECT b.*, un.name AS unit_name, g.email AS guest_email
    FROM bookings b
    JOIN units un ON un.id = b.unit_id
    JOIN users g ON g.id = b.user_id
    WHERE b.id = :id AND b.user_id = :user_id
    LIMIT 1
");
$stmt->execute([':id' => $bookingId, ':user_id' => $userId]);
$booking = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$booking) {
    die('Booking not found.');
}

$txnId = 'BK' . $bookingId . '-' . time();
$amount = number_format($booking['downpayment_amount'], 2, '.', '');
$currency = 'PHP';
$description = '20% Downpayment for ' . $booking['unit_name'] . ' (Booking #' . $bookingId . ')';

$updateStmt = $pdo->prepare("UPDATE bookings SET dragonpay_txnid = :txnid, payment_status = 'pending' WHERE id = :id");
$updateStmt->execute([':txnid' => $txnId, ':id' => $bookingId]);

if (DRAGONPAY_MOCK_MODE) {
    // ===== MOCK MODE: papunta sa sarili nating simulation page =====
    $params = http_build_query([
        'txnid' => $txnId,
        'amount' => $amount,
        'description' => $description,
        'booking_id' => $bookingId,
    ]);
    header('Location: ../mock_dragonpay.php?' . $params);
    exit();
}

// ===== TOTOONG DRAGONPAY MODE =====
$digestStr = DRAGONPAY_MERCHANT_ID . ':' . $txnId . ':' . $amount . ':' . $currency . ':' . $description . ':' . $email . ':' . DRAGONPAY_SECRET_KEY;
$digest = sha1($digestStr);

$params = http_build_query([
    'merchantid' => DRAGONPAY_MERCHANT_ID,
    'txnid' => $txnId,
    'amount' => $amount,
    'ccy' => $currency,
    'description' => $description,
    'email' => $email,
    'digest' => $digest,
]);

header('Location: ' . DRAGONPAY_PAYMENT_URL . '?' . $params);
exit();