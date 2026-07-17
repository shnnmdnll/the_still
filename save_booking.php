<?php
session_start();
require 'backend/includes/db.php';

$user_id = $_SESSION['user_id']; 

$stmt = $pdo->prepare("SELECT * FROM bookings 
    WHERE property_id = ? 
    AND status IN ('pending','confirmed')
    AND (check_in < ? AND check_out > ?)");
$stmt->execute([$_POST['property_id'], $_POST['check_out'], $_POST['check_in']]);

if ($stmt->rowCount() > 0) {
    die("Sorry, na-book na ng iba yung dates habang nag-cocompirm ka.");
}

$stmt = $pdo->prepare("INSERT INTO bookings (property_id, user_id, check_in, check_out, guest_count, total_price, status) 
    VALUES (?, ?, ?, ?, ?, ?, 'pending')");
$stmt->execute([
    $_POST['property_id'],
    $user_id,
    $_POST['check_in'],
    $_POST['check_out'],
    $_POST['guest_count'],
    $_POST['total_price']
]);

header("Location: my_bookings.php");
exit;