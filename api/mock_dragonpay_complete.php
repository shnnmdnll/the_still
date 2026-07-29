<?php
require_once __DIR__ . '/../backend/includes/db.php';
require_once __DIR__ . '/../backend/includes/auth_guard.php';

$txnId = trim($_POST['txnid'] ?? '');
$bookingId = intval($_POST['booking_id'] ?? 0);
$channel = trim($_POST['channel'] ?? '');

if (empty($txnId) || $bookingId <= 0) {
    die('Invalid transaction.');
}

// Gumawa ng mock reference number (parang totoong Dragonpay refno)
$mockRefNo = strtoupper(substr(md5($txnId . time()), 0, 10));

$stmt = $pdo->prepare("
    UPDATE bookings 
    SET payment_status = 'downpayment_paid', dragonpay_refno = :refno, payment_updated_at = NOW(), status = 'pending'
    WHERE id = :id AND dragonpay_txnid = :txnid
");
$stmt->execute([
    ':refno' => $mockRefNo,
    ':id' => $bookingId,
    ':txnid' => $txnId,
]);
$stmt = $pdo->prepare("SELECT total_price, downpayment_amount FROM bookings WHERE id = :id");
$stmt->execute([':id' => $bookingId]);
$bookingInfo = $stmt->fetch(PDO::FETCH_ASSOC);
$remainingBalance = $bookingInfo['total_price'] - $bookingInfo['downpayment_amount'];
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Payment Successful — Pahingahan</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@600;700&family=Inter:wght@400;500;600&display=swap" rel="stylesheet">
    <style>
        body {
            font-family: 'Inter', sans-serif;
            background: #f7f0d8;
            color: #2f2a20;
            margin: 0;
            padding: 60px 20px;
            text-align: center;
        }
        .success-box {
            max-width: 420px;
            margin: 0 auto;
            background: #fff;
            border-radius: 16px;
            padding: 40px 32px;
            box-shadow: 0 4px 20px rgba(0,0,0,.08);
        }
        .check-icon { font-size: 3rem; margin-bottom: 16px; }
        h1 { font-family: 'Poppins', sans-serif; color: #3c6b41; font-size: 1.4rem; margin: 0 0 12px; }
        p { color: #5c5646; font-size: .9rem; margin: 0 0 4px; }
        .ref { font-weight: 700; color: #2f2a20; margin: 16px 0; font-size: 1rem; }
        a {
            display: inline-block;
            margin-top: 20px;
            padding: 12px 28px;
            border-radius: 10px;
            background: #5c8a3a;
            color: #fff;
            text-decoration: none;
            font-weight: 700;
            font-size: .9rem;
        }
    </style>
</head>
<body>
    <div class="success-box">
        <div class="check-icon">✅</div>
        <h1>Payment Successful!</h1>
        <p>Paid via <?php echo htmlspecialchars($channel); ?></p>
        <div class="ref">Reference #: <?php echo htmlspecialchars($mockRefNo); ?></div>
        <p style="font-size:.85rem; color:#5c5646; margin-top:12px;">Natanggap na ang 20% downpayment mo. Naka-pending na ang booking mo, hihintayin na lang ang approval ng Host.</p>
        <p style="font-size:.85rem; color:#5c5646;">Natitirang babayaran (balance) sa check-in: <strong>₱<?php echo number_format($remainingBalance, 2); ?></strong></p>
        <p style="font-size:.75rem; color:#8a8266;">(Sandbox simulation lang ito)</p>
        <a href="../payments.php">Back to Payments</a>
    </div>
</body>
</html>