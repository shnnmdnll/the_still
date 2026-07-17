<?php
session_start();
require 'backend/includes/db.php';

$property_id = $_POST['property_id'];
$check_in = $_POST['check_in'];
$check_out = $_POST['check_out'];
$guest_count = $_POST['guest_count'];

function styledError($message) {
    echo '<!DOCTYPE html><html lang="en"><head><meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Booking Error — Pahingahan</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@500;600;700&family=Inter:wght@400;500;600&display=swap" rel="stylesheet">
    <style>
      body { font-family:"Inter",sans-serif; background:#f7f0d8; color:#2f2a20; margin:0; padding:40px 20px; }
      .container { max-width:480px; margin:0 auto; background:#fff; border-radius:16px; padding:32px; text-align:center; box-shadow:0 4px 20px rgba(0,0,0,.08); }
      h1 { font-family:"Poppins",sans-serif; font-size:1.3rem; color:#c0392b; margin:0 0 12px; }
      p { color:#4a4536; font-size:.95rem; margin:0 0 24px; }
      a { display:inline-block; padding:12px 24px; border-radius:10px; background:#5c8a3a; color:#fff; text-decoration:none; font-weight:600; font-size:.9rem; }
    </style></head><body>
    <div class="container">
      <h1>⚠️ Booking Unavailable</h1>
      <p>' . htmlspecialchars($message) . '</p>
      <a href="homepage.php#top">← Back to Home</a>
    </div>
    </body></html>';
    exit();
}

if ($check_out <= $check_in) {
    styledError("Check-out date must be after check-in date.");
}

// Check overlapping bookings (pending or confirmed lang ang naka-block)
$stmt = $pdo->prepare("SELECT * FROM bookings 
    WHERE property_id = ? 
    AND status IN ('pending','confirmed')
    AND (check_in < ? AND check_out > ?)");
$stmt->execute([$property_id, $check_out, $check_in]);

if ($stmt->rowCount() > 0) {
    styledError("Sorry, hindi available ang property sa napiling dates.");
}

$stmt = $pdo->prepare("SELECT price FROM properties WHERE id = ?");
$stmt->execute([$property_id]);
$property = $stmt->fetch();

$nights = (strtotime($check_out) - strtotime($check_in)) / 86400;
$total_price = $nights * $property['price'];
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Confirm Booking — Pahingahan</title>
<link rel="preconnect" href="https://fonts.googleapis.com">
<link href="https://fonts.googleapis.com/css2?family=Poppins:wght@500;600;700&family=Inter:wght@400;500;600&display=swap" rel="stylesheet">
<style>
  * { box-sizing: border-box; }
  body {
    font-family: 'Inter', sans-serif;
    background: #f7f0d8;
    color: #2f2a20;
    margin: 0;
    padding: 40px 20px;
  }
  .booking-container {
    max-width: 480px;
    margin: 0 auto;
    background: #fff;
    border-radius: 16px;
    padding: 32px;
    box-shadow: 0 4px 20px rgba(0,0,0,.08);
  }
  h2 {
    font-family: 'Poppins', sans-serif;
    font-size: 1.4rem;
    margin: 0 0 20px;
    color: #3c6b41;
  }
  .summary-row {
    display: flex;
    justify-content: space-between;
    padding: 12px 0;
    border-bottom: 1px solid #f0ece0;
    font-size: .92rem;
  }
  .summary-row:last-of-type { border-bottom: none; }
  .summary-label { color: #8a8266; }
  .summary-value { font-weight: 600; }
  .total-row {
    display: flex;
    justify-content: space-between;
    margin-top: 16px;
    padding-top: 16px;
    border-top: 2px solid #e2ddc9;
    font-size: 1.1rem;
    font-weight: 700;
  }
  .total-row .summary-value { color: #3c6b41; }
  button[type="submit"] {
    width: 100%;
    margin-top: 24px;
    padding: 14px;
    border: none;
    border-radius: 10px;
    background: #5c8a3a;
    color: #fff;
    font-weight: 700;
    font-size: 1rem;
    cursor: pointer;
    transition: background .15s ease;
  }
  button[type="submit"]:hover {
    background: #4a7130;
  }
</style>
</head>
<body>

<div class="booking-container">
  <h2>✅ Confirm your Booking</h2>

  <div class="summary-row">
    <span class="summary-label">Check-in</span>
    <span class="summary-value"><?= htmlspecialchars($check_in) ?></span>
  </div>
  <div class="summary-row">
    <span class="summary-label">Check-out</span>
    <span class="summary-value"><?= htmlspecialchars($check_out) ?></span>
  </div>
  <div class="summary-row">
    <span class="summary-label">Nights</span>
    <span class="summary-value"><?= $nights ?></span>
  </div>
  <div class="summary-row">
    <span class="summary-label">Guests</span>
    <span class="summary-value"><?= htmlspecialchars($guest_count) ?></span>
  </div>

  <div class="total-row">
    <span>Total</span>
    <span class="summary-value">₱<?= number_format($total_price, 2) ?></span>
  </div>

  <form method="POST" action="save_booking.php">
    <input type="hidden" name="property_id" value="<?= htmlspecialchars($property_id) ?>">
    <input type="hidden" name="check_in" value="<?= htmlspecialchars($check_in) ?>">
    <input type="hidden" name="check_out" value="<?= htmlspecialchars($check_out) ?>">
    <input type="hidden" name="guest_count" value="<?= htmlspecialchars($guest_count) ?>">
    <input type="hidden" name="total_price" value="<?= $total_price ?>">
    <button type="submit">Confirm Booking</button>
  </form>
</div>

</body>
</html>