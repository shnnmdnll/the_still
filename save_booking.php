<?php
session_start();
require 'backend/includes/db.php';

$user_id = $_SESSION['user_id']; 
$unit_id = $_POST['unit_id'] ?? $_POST['property_id'] ?? null;

if (!$unit_id) {
    die("Missing unit information.");
}

// I-check kung may na-upload na ba ang guest ng valid ID bago payagang mag-book
$idCheckStmt = $pdo->prepare("SELECT id_verification_status FROM users WHERE id = :id");
$idCheckStmt->execute([':id' => $user_id]);
$idStatus = $idCheckStmt->fetchColumn();

if (empty($idStatus) || $idStatus === 'not_submitted') {
    die('<script>alert("Kailangan mo munang mag-upload ng valid ID bago makapag-book. I-uupload mo na ngayon."); window.location.href = "upload_id.php";</script>');
}

$stmt = $pdo->prepare("SELECT * FROM bookings 
    WHERE unit_id = ? 
    AND status IN ('pending','confirmed')
    AND (check_in < ? AND check_out > ?)");
$stmt->execute([$unit_id, $_POST['check_out'], $_POST['check_in']]);

if ($stmt->rowCount() > 0) {
    die("Sorry, na-book na ng iba yung dates habang nag-cocompirm ka.");
}

$totalPrice = floatval($_POST['total_price']);
$downpaymentAmount = round($totalPrice * 0.20, 2);

$stmt = $pdo->prepare("INSERT INTO bookings (unit_id, user_id, check_in, check_out, guest_count, total_price, downpayment_amount, status, payment_status) 
    VALUES (?, ?, ?, ?, ?, ?, ?, 'awaiting_payment', 'unpaid') RETURNING id");
$stmt->execute([
    $unit_id,
    $user_id,
    $_POST['check_in'],
    $_POST['check_out'],
    $_POST['guest_count'],
    $totalPrice,
    $downpaymentAmount,
]);

$newBookingId = $stmt->fetchColumn();

// Diretso papuntang payment step — hindi pa "successful" ang booking hanggang mabayaran ang downpayment
header("Location: api/dragonpay_checkout.php?booking_id=" . $newBookingId);
exit;