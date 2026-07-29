<?php
require_once __DIR__ . '/backend/includes/auth_guard.php';
require_once __DIR__ . '/backend/includes/db.php';

$userId = $_SESSION['user_id'];

$stmt = $pdo->prepare("
    SELECT b.*, un.name AS unit_name
    FROM bookings b
    JOIN units un ON un.id = b.unit_id
    WHERE b.user_id = :user_id
    ORDER BY b.id DESC
");
$stmt->execute([':user_id' => $userId]);
$bookings = $stmt->fetchAll(PDO::FETCH_ASSOC);

function paymentStatusColor($status) {
    switch ($status) {
        case 'downpayment_paid': return ['#e6f2e0', '#3c6b41'];
        case 'paid':    return ['#e6f2e0', '#3c6b41'];
        case 'pending': return ['#fdf3d9', '#8a6d1a'];
        case 'failed':  return ['#fbe4e1', '#c0392b'];
        case 'voided':  return ['#f0ece0', '#6b6350'];
        default:        return ['#f0ece0', '#6b6350'];
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Payments — Pahingahan</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@500;600;700&family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <style>
        * { box-sizing: border-box; }
        body {
            font-family: 'Inter', sans-serif;
            background: #f7f0d8;
            color: #2f2a20;
            margin: 0;
            padding: 40px 20px;
        }
        .page {
            max-width: 800px;
            margin: 0 auto;
        }
        .page-head {
            display: flex;
            align-items: center;
            justify-content: space-between;
            margin-bottom: 24px;
        }
        h1 {
            font-family: 'Poppins', sans-serif;
            font-size: 1.6rem;
            margin: 0;
            color: #3c6b41;
        }
        .btn-back {
            padding: 10px 18px;
            border-radius: 10px;
            background: #fff;
            color: #3c6b41;
            text-decoration: none;
            font-weight: 600;
            font-size: .85rem;
            border: 1px solid #e2ddc9;
        }
        .payment-card {
            background: #fff;
            border-radius: 14px;
            padding: 20px;
            margin-bottom: 16px;
            box-shadow: 0 2px 10px rgba(0,0,0,.06);
        }
        .payment-header {
            display: flex;
            justify-content: space-between;
            align-items: flex-start;
            margin-bottom: 12px;
        }
        .payment-title {
            font-weight: 700;
            font-size: 1.05rem;
        }
        .payment-sub {
            color: #8a8266;
            font-size: .85rem;
            margin-top: 4px;
        }
        .status-badge {
            display: inline-block;
            padding: 5px 14px;
            border-radius: 20px;
            font-size: .78rem;
            font-weight: 700;
            text-transform: capitalize;
        }
        .payment-amount {
            font-size: 1.3rem;
            font-weight: 700;
            color: #3c6b41;
            margin: 12px 0;
        }
        .btn-pay {
            display: inline-block;
            padding: 12px 24px;
            border-radius: 10px;
            background: #5c8a3a;
            color: #fff;
            text-decoration: none;
            font-weight: 700;
            font-size: .9rem;
            border: none;
            cursor: pointer;
        }
        .btn-pay:hover {
            background: #4a7130;
        }
        .ref-note {
            font-size: .8rem;
            color: #8a8266;
            margin-top: 8px;
        }
        .empty-state {
            background: #fff;
            border-radius: 14px;
            padding: 60px 20px;
            text-align: center;
            color: #8a8266;
        }
    </style>
</head>
<body>

<div class="page">
    <div class="page-head">
        <h1>💳 Payments</h1>
        <a href="homepage.php#top" class="btn-back">← Back to Home</a>
    </div>

    <?php if (count($bookings) === 0): ?>
        <div class="empty-state">Wala ka pang bookings.</div>
    <?php else: ?>
        <?php foreach ($bookings as $b):
            [$bg, $fg] = paymentStatusColor($b['payment_status'] ?? 'unpaid');
        ?>
            <div class="payment-card">
                <div class="payment-header">
                    <div>
                        <div class="payment-title"><?php echo htmlspecialchars($b['unit_name']); ?></div>
                        <div class="payment-sub"><?php echo htmlspecialchars($b['check_in']); ?> → <?php echo htmlspecialchars($b['check_out']); ?></div>
                    </div>
                    <span class="status-badge" style="background:<?php echo $bg; ?>; color:<?php echo $fg; ?>;">
                        <?php echo htmlspecialchars($b['payment_status'] ?? 'unpaid'); ?>
                    </span>
                </div>
                <div class="payment-amount">₱<?php echo number_format($b['total_price'], 2); ?></div>

               <?php if (($b['payment_status'] ?? 'unpaid') === 'unpaid' || ($b['payment_status'] ?? '') === 'failed'): ?>
                <a href="api/dragonpay_checkout.php?booking_id=<?php echo $b['id']; ?>" class="btn-pay">Pay 20% Downpayment</a>
            <?php elseif ($b['payment_status'] === 'downpayment_paid'): ?>
                <div class="ref-note">✓ Downpayment paid — Ref #: <?php echo htmlspecialchars($b['dragonpay_refno'] ?? '—'); ?></div>
                <div class="ref-note">Balance sa check-in: ₱<?php echo number_format($b['total_price'] - $b['downpayment_amount'], 2); ?></div>
            <?php elseif ($b['payment_status'] === 'paid'): ?>
                <div class="ref-note">✓ Fully Paid — Ref #: <?php echo htmlspecialchars($b['dragonpay_refno'] ?? '—'); ?></div>
            <?php endif; ?>
            </div>
        <?php endforeach; ?>
    <?php endif; ?>
</div>

</body>
</html>