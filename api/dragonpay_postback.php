<?php
error_reporting(E_ALL);
ini_set('display_errors', 0); // huwag ipakita ang errors dito, baka masira ang response format

require_once __DIR__ . '/../backend/includes/db.php';
require_once __DIR__ . '/../backend/includes/dragonpay_config.php';

$txnId = $_POST['txnid'] ?? $_GET['txnid'] ?? '';
$refNo = $_POST['refno'] ?? $_GET['refno'] ?? '';
$status = $_POST['status'] ?? $_GET['status'] ?? '';
$message = $_POST['message'] ?? $_GET['message'] ?? '';
$digest = $_POST['digest'] ?? $_GET['digest'] ?? '';

// I-verify ang digest para masiguro na galing talaga sa Dragonpay ang request
$expectedDigestStr = $txnId . ':' . $refNo . ':' . $status . ':' . $message . ':' . DRAGONPAY_SECRET_KEY;
$expectedDigest = sha1($expectedDigestStr);

if ($digest !== $expectedDigest) {
    http_response_code(401);
    echo 'result=FAIL';
    exit();
}

// I-map ang Dragonpay status codes papuntang atin
// S = Success, F = Failure, P = Pending, U = Unknown, R = Refund, K = Chargeback, V = Void, A = Authorized
$paymentStatus = 'unpaid';
if ($status === 'S') $paymentStatus = 'paid';
elseif ($status === 'F') $paymentStatus = 'failed';
elseif ($status === 'P') $paymentStatus = 'pending';
elseif ($status === 'V') $paymentStatus = 'voided';

$stmt = $pdo->prepare("
    UPDATE bookings 
    SET payment_status = :payment_status, dragonpay_refno = :refno, payment_updated_at = NOW()
    WHERE dragonpay_txnid = :txnid
");
$stmt->execute([
    ':payment_status' => $paymentStatus,
    ':refno' => $refNo,
    ':txnid' => $txnId,
]);

// Kung successful ang downpayment, i-move papuntang 'pending' (naghihintay ng approval ng Host)
if ($paymentStatus === 'paid') {
    $stmt = $pdo->prepare("
        UPDATE bookings 
        SET status = 'pending', payment_status = 'downpayment_paid' 
        WHERE dragonpay_txnid = :txnid AND status = 'awaiting_payment'
    ");
    $stmt->execute([':txnid' => $txnId]);
}

// Dragonpay expects "result=OK" na plain text response
echo 'result=OK';