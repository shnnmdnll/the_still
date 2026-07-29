<?php
require_once __DIR__ . '/backend/includes/auth_guard.php';

$txnId = $_GET['txnid'] ?? '';
$amount = $_GET['amount'] ?? '0.00';
$description = $_GET['description'] ?? '';
$bookingId = intval($_GET['booking_id'] ?? 0);

if (empty($txnId) || $bookingId <= 0) {
    die('Invalid transaction.');
}

$channels = [
    ['code' => 'GCSH', 'name' => 'GCash', 'icon' => '💚'],
    ['code' => 'BPXB', 'name' => 'BPI Online Banking', 'icon' => '🏦'],
    ['code' => 'BDO', 'name' => 'BDO Online Banking', 'icon' => '🏦'],
    ['code' => 'MYNT', 'name' => 'Maya', 'icon' => '💙'],
    ['code' => '7ELEVEN', 'name' => '7-Eleven (Cash)', 'icon' => '🏪'],
];
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dragonpay — Choose Payment Channel</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@600;700&family=Inter:wght@400;500;600&display=swap" rel="stylesheet">
    <style>
        * { box-sizing: border-box; }
        body {
            font-family: 'Inter', sans-serif;
            background: #1a1a2e;
            color: #fff;
            margin: 0;
            padding: 40px 20px;
            min-height: 100vh;
        }
        .dp-container {
            max-width: 480px;
            margin: 0 auto;
            background: #fff;
            border-radius: 16px;
            overflow: hidden;
            color: #2f2a20;
        }
        .dp-header {
            background: #e8302a;
            padding: 24px;
            text-align: center;
        }
        .dp-header h1 {
            font-family: 'Poppins', sans-serif;
            color: #fff;
            font-size: 1.3rem;
            margin: 0 0 4px;
        }
        .dp-header p {
            color: rgba(255,255,255,0.85);
            font-size: .8rem;
            margin: 0;
        }
        .mock-banner {
            background: #fdf3d9;
            color: #8a6d1a;
            text-align: center;
            padding: 8px;
            font-size: .78rem;
            font-weight: 700;
        }
        .dp-body {
            padding: 24px;
        }
        .dp-summary {
            background: #f7f0d8;
            border-radius: 10px;
            padding: 16px;
            margin-bottom: 20px;
        }
        .dp-summary-row {
            display: flex;
            justify-content: space-between;
            font-size: .85rem;
            margin-bottom: 6px;
        }
        .dp-summary-row:last-child {
            margin-bottom: 0;
            font-weight: 700;
            font-size: 1rem;
            padding-top: 8px;
            border-top: 1px solid #e2ddc9;
        }
        .dp-channels h3 {
            font-size: .9rem;
            margin: 0 0 12px;
            color: #5c5646;
        }
        .channel-btn {
            display: flex;
            align-items: center;
            gap: 12px;
            width: 100%;
            padding: 14px 16px;
            border: 1.5px solid #e2ddc9;
            border-radius: 10px;
            background: #fff;
            margin-bottom: 10px;
            cursor: pointer;
            font-size: .92rem;
            font-weight: 600;
            color: #2f2a20;
            transition: border-color .15s ease;
        }
        .channel-btn:hover {
            border-color: #5c8a3a;
        }
        .channel-icon {
            font-size: 1.3rem;
        }
    </style>
</head>
<body>

<div class="dp-container">
    <div class="mock-banner">⚠ SANDBOX SIMULATION — walang totoong pera na gagalaw</div>
    <div class="dp-header">
        <h1>🐉 Dragonpay</h1>
        <p>Secure Payment Gateway</p>
    </div>
    <div class="dp-body">
        <div class="dp-summary">
            <div class="dp-summary-row">
                <span>Transaction ID</span>
                <span><?php echo htmlspecialchars($txnId); ?></span>
            </div>
            <div class="dp-summary-row">
                <span>Description</span>
                <span><?php echo htmlspecialchars($description); ?></span>
            </div>
            <div class="dp-summary-row">
                <span>Total Amount</span>
                <span>₱<?php echo number_format((float)$amount, 2); ?></span>
            </div>
        </div>

        <div class="dp-channels">
            <h3>Select Payment Channel</h3>
            <?php foreach ($channels as $ch): ?>
                <form method="POST" action="api/mock_dragonpay_complete.php" style="margin:0;">
                    <input type="hidden" name="txnid" value="<?php echo htmlspecialchars($txnId); ?>">
                    <input type="hidden" name="booking_id" value="<?php echo $bookingId; ?>">
                    <input type="hidden" name="channel" value="<?php echo htmlspecialchars($ch['name']); ?>">
                    <button type="submit" class="channel-btn">
                        <span class="channel-icon"><?php echo $ch['icon']; ?></span>
                        <?php echo htmlspecialchars($ch['name']); ?>
                    </button>
                </form>
            <?php endforeach; ?>
        </div>
    </div>
</div>

</body>
</html>